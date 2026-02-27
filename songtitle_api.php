<?php
// ===============================================
// SONG TITLE API PROXY - KESİN ÇÖZÜM VE CACHING SÜRÜMÜ
// ===============================================

// 1. CORS ve Başlık Ayarları
header('Access-Control-Allow-Origin: *');
header('Content-Type: text/plain; charset=utf-8'); 

// 2. Gerekli Parametre Kontrolü
if (!isset($_GET['stream_url']) || empty($_GET['stream_url'])) {
    http_response_code(400); 
    die('Hata: stream_url parametresi eksik.');
}

$stream_url = filter_var($_GET['stream_url'], FILTER_SANITIZE_URL);
$base_url = rtrim($stream_url, '/'); 
$default_title = "Şimdi Çalıyor"; 

// ----------------------------------------------------
// Önbellek Ayarları
// ----------------------------------------------------
$cache_dir = 'cache/';
$cache_time = 10; // Önbellek süresi (saniye). 10 saniye önerilir.
$cache_key = md5($base_url);
$cache_file = $cache_dir . $cache_key . '.txt';

// ----------------------------------------------------
// Fonksiyon: Önbelleği Kontrol Et ve Kullan
// ----------------------------------------------------
if (file_exists($cache_file) && (time() - $cache_time < filemtime($cache_file))) {
    $song_title = file_get_contents($cache_file);
    if (!empty($song_title)) {
        echo $song_title;
        die();
    }
}

// ----------------------------------------------------
// Fonksiyon: Hedef URL'den veriyi çeker
// ----------------------------------------------------
function fetch_data($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0'); 

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // HTTP kodu 200 veya başarılı bir yönlendirme (3xx) ise başarılı kabul et
    if ($response !== false && ($http_code >= 200 && $http_code < 400)) {
        return $response;
    }
    return null;
}

// ----------------------------------------------------
// Fonksiyon: Gelen veriyi (HTML/JSON/Metin) ayrıştırır (FIX)
// ----------------------------------------------------
function parse_metadata($response) {
    if (!$response) return null;
    
    // Tüm HTML etiketlerini kaldır ve HTML entity'lerini çöz
    $clean_response = strip_tags($response); 
    $clean_response = html_entity_decode($clean_response); 
    $clean_response = trim($clean_response); 
    
    if (empty($clean_response)) {
        return null;
    }
    
    // 1. JSON formatını dene (En doğru ayrıştırma yöntemi)
    $data = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        
        // Shoutcast v2 özel anahtarı kontrolü (Sizin gördüğünüz hatayı düzeltecek)
        if (preg_match('/"yp_currently_playing"\s*:\s*"(.*?)"/i', $clean_response, $matches)) {
            return trim($matches[1]);
        }
        
        $metadata = $data;
        // Icecast v2 özel JSON yapısı
        if (isset($data['icestats']['source'])) {
            $source = $data['icestats']['source'];
            $metadata = is_array($source) ? $source[0] : $source;
        }

        $possible_keys = ['title', 'song', 'stream_title', 'current_song', 'track', 'data', 'currentsong'];
        
        $search_space = (is_array($metadata) || is_object($metadata)) ? $metadata : ['root' => $metadata];
        
        foreach ($search_space as $key => $value) {
            if (in_array(strtolower($key), $possible_keys) && is_string($value) && trim($value) !== '' && !is_numeric(trim($value))) {
                return trim($value);
            }
        }
    }
    
    // 2. Virgülle Ayrılmış (CSV benzeri) Metin Ayrıştırma (Shoutcast v1 7.html formatı)
    $parts = explode(',', $clean_response);
    
    // Eğer 4'ten fazla parça varsa ve son parça sayısal değilse, büyük ihtimalle şarkı adıdır.
    if (count($parts) >= 4) {
        $potential_title = trim(end($parts));
        
        // Ayrıca, sadece ilk birkaç parçayı atıp geri kalanı birleştirmeyi dene (daha güvenli)
        if (count($parts) >= 6) {
             // İlk 6 parçayı at (genellikle sayısal verilerdir)
             $title_parts = array_slice($parts, 6); 
             $potential_title = trim(implode(',', $title_parts));
        }

        if (!empty($potential_title) && !is_numeric($potential_title)) {
             return $potential_title;
        }
    }
    
    // 3. Düz metin olarak geri döndür (Son çare)
    if (!empty($clean_response) && !is_numeric($clean_response)) {
        return $clean_response;
    }

    return null;
}


// ----------------------------------------------------
// DENEME LİSTESİ VE ÖN BELLEK YAZMA
// ----------------------------------------------------
$metadata_paths = [
    '/7.html',                // Shoutcast v1 (CSV formatı)
    '/currentsong?sid=1',     // Shoutcast v2
    '/status-json.xsl',       // Icecast JSON
    '/stats?json=1',          // Alternatif JSON
    '/'                       // Stream URL'sinin kendisi
];

$final_title = $default_title;

foreach ($metadata_paths as $path) {
    $url = $base_url . $path;
    $response = fetch_data($url);
    
    if ($response) {
        $result = parse_metadata($response);
        
        if ($result) {
            $final_title = $result;
            break; // Geçerli bir sonuç bulduğumuz an döngüyü kır
        }
    }
}

// ----------------------------------------------------
// Önbelleğe Yazma ve Sonuç Çıktısı
// ----------------------------------------------------

// Başarılı bir başlık bulduysak önbelleğe yaz
if ($final_title !== $default_title) {
    if (!is_dir($cache_dir)) {
        @mkdir($cache_dir, 0777, true);
    }
    @file_put_contents($cache_file, $final_title);
}

echo $final_title;
die();
?>