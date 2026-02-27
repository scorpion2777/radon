<?php
// ===============================================
// iTUNES ALBUM ARTWORK API - Cache'li Sürüm
// ===============================================
// Kullanım: itunes_artwork.php?title=Sanatçı - Şarkı Adı
// Döner:    JSON { artwork: "https://...", artist: "...", track: "..." }
//           veya { artwork: null } bulunamazsa
// ===============================================

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

// --- Önbellek Ayarları ---
$cache_dir  = __DIR__ . '/cache/artwork/';
$cache_time = 3600; // 1 saat

if (!is_dir($cache_dir)) {
    @mkdir($cache_dir, 0755, true);
}

// --- Parametre ---
$raw_title = isset($_GET['title']) ? trim($_GET['title']) : '';

if (empty($raw_title) || $raw_title === 'Şimdi Çalıyor' || $raw_title === '-') {
    echo json_encode(['artwork' => null]);
    exit;
}

// --- Cache Kontrolü ---
$cache_key  = md5(strtolower($raw_title));
$cache_file = $cache_dir . $cache_key . '.json';

if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_time)) {
    $cached = file_get_contents($cache_file);
    if ($cached !== false) {
        echo $cached;
        exit;
    }
}

// --- iTunes Search API ---
// "Sanatçı - Şarkı" formatını ayrıştırmaya çalış
$parts    = explode(' - ', $raw_title, 2);
$artist   = count($parts) === 2 ? trim($parts[0]) : '';
$track    = count($parts) === 2 ? trim($parts[1]) : trim($raw_title);

// Arama terimi oluştur
$query = $artist ? "$artist $track" : $track;
$query = preg_replace('/\s+/', ' ', $query);

$api_url = 'https://itunes.apple.com/search?'
    . http_build_query([
        'term'       => $query,
        'media'      => 'music',
        'entity'     => 'song',
        'limit'      => 3,
        'country'    => 'tr',
    ]);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $api_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 5,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_USERAGENT      => 'Mozilla/5.0',
    CURLOPT_SSL_VERIFYPEER => false,
]);
$response  = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = ['artwork' => null];

if ($response && $http_code === 200) {
    $data = json_decode($response, true);
    if (!empty($data['results'])) {
        $best = null;

        // En iyi eşleşmeyi bul
        foreach ($data['results'] as $item) {
            if (!isset($item['artworkUrl100'])) continue;

            $item_artist = strtolower($item['artistName'] ?? '');
            $item_track  = strtolower($item['trackName']  ?? '');

            // Sanatçı eşleşmesi varsa öncelik ver
            if ($artist && stripos($item_artist, strtolower($artist)) !== false) {
                $best = $item;
                break;
            }

            // En azından şarkı adı eşleşsin
            if (stripos($item_track, strtolower($track)) !== false) {
                $best = $item;
                break;
            }

            // Hiç eşleşme yoksa ilki al
            if (!$best) $best = $item;
        }

        if ($best && isset($best['artworkUrl100'])) {
            // 100x100 yerine 600x600 kapak iste
            $artwork_url = str_replace('100x100bb', '600x600bb', $best['artworkUrl100']);

            $result = [
                'artwork' => $artwork_url,
                'artist'  => $best['artistName']  ?? '',
                'track'   => $best['trackName']   ?? '',
                'album'   => $best['collectionName'] ?? '',
            ];
        }
    }
}

$json = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// Cache'e yaz (sadece bulunduysa)
if ($result['artwork']) {
    @file_put_contents($cache_file, $json);
}

echo $json;
exit;
?>
