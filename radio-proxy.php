<?php
error_reporting(0);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: *');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(); }

$url = $_GET['url'] ?? '';
if (empty($url)) { http_response_code(400); exit('URL eksik'); }

$host  = parse_url($url, PHP_URL_HOST);
$port  = (int) parse_url($url, PHP_URL_PORT);
$scheme = parse_url($url, PHP_URL_SCHEME);

if (!$host) { http_response_code(400); exit('Geçersiz URL'); }

/* ------------------------------------------------------------------
 * Whitelist: Sadece HTTP stream'ler için (mixed-content engeli).
 * HTTPS stream'ler tarayıcı tarafından doğrudan çalınır — proxy'ye
 * sadece SSL sertifikası olmayan (non-std port/IP) HTTPS URL'ler gelir.
 * ------------------------------------------------------------------ */
$allowed = [
    'live.radyotvonline.com', 'stream.zeno.fm', 'radyovetv.com',
    'herkesmobil.com', 'alternatifradyo.radyotvyayini.com',
    'yayin.netyayin.net', 'arabeskalemi.net', 'radyo.yayin.com.tr',
    'yayin.arabeskturk.com', 'arabeskturkiye.xyz',
    'newserver.radyosfer.net', 'live4.radyotvonline.com',
    // Eklenen host'lar
    '29103.live.streamtheworld.com',
    'mp3channels.webradio.rockantenne.de',
    'wdr-1live-live.icecast.wdr.de',
    'stream.sunshine-live.de', 'wdr-wdr5-live.icecast.wdr.de', 'mp3channels.webradio.antenne.de'
];

// WordPress DB dinamik liste
$dir = __DIR__;
for ($i = 0; $i < 8; $i++) {
    if (file_exists($dir . '/wp-config.php')) {
        try {
            $cfg = file_get_contents($dir . '/wp-config.php');
            preg_match("/define\(\s*['\"]DB_NAME['\"]\s*,\s*['\"]([^'\"]+)/",     $cfg, $n);
            preg_match("/define\(\s*['\"]DB_USER['\"]\s*,\s*['\"]([^'\"]+)/",     $cfg, $u);
            preg_match("/define\(\s*['\"]DB_PASSWORD['\"]\s*,\s*['\"]([^'\"]+)/", $cfg, $p);
            preg_match("/define\(\s*['\"]DB_HOST['\"]\s*,\s*['\"]([^'\"]+)/",     $cfg, $h);
            preg_match("/\\\$table_prefix\s*=\s*['\"]([^'\"]+)/",                 $cfg, $t);
            if (!empty($n[1])) {
                $pdo = new PDO(
                    'mysql:host=' . ($h[1] ?? 'localhost') . ';dbname=' . $n[1] . ';charset=utf8mb4',
                    $u[1] ?? '', $p[1] ?? '',
                    [PDO::ATTR_TIMEOUT => 2, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                $prefix = $t[1] ?? 'wp_';
                $row = $pdo->query(
                    "SELECT option_value FROM {$prefix}options WHERE option_name = 'radiotheme_proxy_hosts' LIMIT 1"
                )->fetch(PDO::FETCH_COLUMN);
                if ($row) {
                    $dynamic = json_decode($row, true);
                    if (is_array($dynamic)) {
                        $allowed = array_unique(array_merge($allowed, $dynamic));
                    }
                }
            }
        } catch (Exception $e) {}
        break;
    }
    $dir = dirname($dir);
}

$isIP   = filter_var($host, FILTER_VALIDATE_IP) !== false;
$nonStd = $port > 0 && $port !== 80 && $port !== 443;

// http:// → whitelist kontrolü zorunlu
// https:// non-std/IP → SSL sertifikası yok, her zaman izin ver
if ($scheme === 'http' && !$isIP && !$nonStd && !in_array($host, $allowed)) {
    http_response_code(403);
    exit('Desteklenmeyen akış');
}

@set_time_limit(0);
@ini_set('max_execution_time', 0);
while (ob_get_level()) ob_end_clean();

/* ------------------------------------------------------------------
 * CURL ile bağlan — fopen'dan çok daha güvenilir, SSL kontrolünü
 * kapatabiliyoruz, Content-Type'ı stream'den okuyabiliyoruz.
 * curl yoksa fopen fallback.
 * ------------------------------------------------------------------ */
if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_CONNECTTIMEOUT => 8,      // 8sn bağlantı timeout
        CURLOPT_TIMEOUT        => 0,      // okuma süresiz
        CURLOPT_SSL_VERIFYPEER => false,  // SSL sertifika doğrulama kapalı
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT      => 'RadioApp/1.0',
        CURLOPT_HTTPHEADER     => [
            'Accept: audio/mpeg, audio/aac, audio/aacp, audio/ogg, audio/*, */*',
            'Icy-MetaData: 0',
        ],
        CURLOPT_HEADERFUNCTION => function($ch, $headerLine) {
            // Stream'in Content-Type'ını al, tarayıcıya ilet
            if (stripos($headerLine, 'content-type:') === 0) {
                $ct = trim(substr($headerLine, 13));
                if ($ct) header('Content-Type: ' . $ct, true);
            }
            return strlen($headerLine);
        },
        CURLOPT_WRITEFUNCTION => function($ch, $data) {
            echo $data;
            flush();
            return strlen($data);
        },
        CURLOPT_BUFFERSIZE     => 8192,
    ]);

    // Content-Type varsayılanı — stream'den gelirse üzerine yazar
    header('Content-Type: audio/mpeg');
    header('Cache-Control: no-cache, no-store');
    header('X-Accel-Buffering: no');

    $result = curl_exec($ch);
    $errno  = curl_errno($ch);
    curl_close($ch);

    if ($errno && $errno !== CURLE_WRITE_ERROR) {
        // Henüz hiç veri gitmediyse 502 dön
        if (!headers_sent()) {
            http_response_code(502);
            echo 'Akışa bağlanılamadı (curl errno: ' . $errno . ')';
        }
    }
    exit();
}

/* ------------------------------------------------------------------
 * FALLBACK: curl yoksa fopen kullan
 * ------------------------------------------------------------------ */
$context = stream_context_create([
    'http' => [
        'timeout'         => 8,
        'follow_location' => true,
        'max_redirects'   => 5,
        'header'          => implode("\r\n", [
            'User-Agent: RadioApp/1.0',
            'Accept: audio/mpeg, audio/aac, audio/aacp, audio/ogg, audio/*, */*',
            'Icy-MetaData: 0',
        ]),
    ],
    'ssl' => [
        'verify_peer'      => false,
        'verify_peer_name' => false,
    ],
]);

$stream = @fopen($url, 'rb', false, $context);
if (!$stream) { http_response_code(502); exit('Akışa bağlanılamadı'); }

// fopen'da response header'lardan Content-Type bul
$meta = stream_get_meta_data($stream);
$ct   = 'audio/mpeg';
foreach (($meta['wrapper_data'] ?? []) as $h) {
    if (stripos($h, 'content-type:') === 0) {
        $ct = trim(substr($h, 13));
        break;
    }
}
header('Content-Type: ' . $ct);
header('Cache-Control: no-cache, no-store');
header('X-Accel-Buffering: no');

stream_set_timeout($stream, 0);

$lastActivity = time();
while (!feof($stream) && !connection_aborted()) {
    $data = fread($stream, 8192);
    if ($data === false || strlen($data) === 0) {
        $info = stream_get_meta_data($stream);
        if ($info['timed_out'] || $info['eof']) break;
        usleep(50000);
        if (time() - $lastActivity > 30) break;
        continue;
    }
    $lastActivity = time();
    echo $data;
    flush();
}
fclose($stream);
