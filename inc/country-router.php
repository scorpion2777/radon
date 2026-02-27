<?php
/**
 * RadioTheme — inc/country-router.php
 *
 * Ülke bazlı URL yönlendirme sistemi.
 *
 * URL Yapısı:
 *   /s/                           → IP tespiti → /s/{cc}/ yönlendir
 *   /s/{cc}/                      → Ülke ana sayfası (indexlenir)
 *   /s/{cc}/station/{slug}/       → İstasyon detay
 *   /s/{cc}/genre/{slug}/         → Ülke + genre filtresi
 *   /s/{cc}/city/{slug}/          → Ülke + şehir filtresi
 *
 * Mapping: URL'deki {cc} (ör: tr) → taxonomy slug'u (ör: turkey)
 * functions.php'deki radiotheme_get_country_map() kullanılır.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ============================================================
   1. ISO KODU → TAXONOMY SLUG MAPPING
   functions.php'deki tam listeden türetilir, burada sadece
   flipped (kod → slug) versiyonu lazım.
   ============================================================ */

/**
 * ISO kodu (tr) → taxonomy slug (turkey) döner.
 */
function radiotheme_iso_to_slug( string $iso ): string {
    static $map = null;
    if ( $map === null ) {
        // radiotheme_get_country_map(): slug → ISO  →  biz ters çeviriyoruz
        $raw = radiotheme_get_country_map(); // [ 'turkey' => 'TR', ... ]
        $map = [];
        foreach ( $raw as $slug => $code ) {
            $map[ strtolower( $code ) ] = $slug; // [ 'tr' => 'turkey', ... ]
        }
    }
    return $map[ strtolower( $iso ) ] ?? '';
}

/**
 * Taxonomy slug (turkey) → ISO kodu (TR) döner.
 */
function radiotheme_slug_to_iso( string $slug ): string {
    $raw = radiotheme_get_country_map();
    return strtolower( $raw[ strtolower( $slug ) ] ?? '' );
}

/* ============================================================
   2. MEVCUT URL'DEN ÜLKE KODUNU OKU
   /s/tr/station/arabesk-fm/ → 'tr'
   /s/tr/ → 'tr'
   ============================================================ */

function radiotheme_get_url_country_code(): string {
    static $cache = null;
    if ( $cache !== null ) return $cache;

    $uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';

    // WordPress kurulu dizini çıkar (/s/ gibi)
    $home_path = rtrim( wp_parse_url( home_url(), PHP_URL_PATH ) ?? '', '/' );
    if ( $home_path && strpos( $uri, $home_path ) === 0 ) {
        $uri = substr( $uri, strlen( $home_path ) );
    }
    $uri = '/' . ltrim( $uri, '/' );

    // /tr/ veya /tr/station/... şeklinde 2 harfli ISO kodu yakala
    if ( preg_match( '#^/([a-z]{2})(/|$)#i', $uri, $m ) ) {
        $code = strtolower( $m[1] );
        // Geçerli bir ülke kodu mu?
        if ( radiotheme_iso_to_slug( $code ) !== '' ) {
            $cache = $code;
            return $cache;
        }
    }

    $cache = '';
    return $cache;
}

/**
 * URL'deki ülke kodunu taxonomy slug'una çevirir.
 * Örn: 'tr' → 'turkey'
 */
function radiotheme_get_url_country_slug(): string {
    $code = radiotheme_get_url_country_code();
    return $code ? radiotheme_iso_to_slug( $code ) : '';
}

/* ============================================================
   3. KULLANICI ÜLKESİNİ TESPİT ET (IP bazlı, en güvenilir)
   Öncelik sırası:
   1. Cloudflare CF-IPCountry header (sunucu VPN'e rağmen çalışır)
   2. Nginx/CDN X-Country-Code header
   3. GeoIP Detect eklentisi
   4. ip-api.com (ücretsiz, fallback)
   ============================================================ */

function radiotheme_detect_user_country(): string {
    static $cache = null;
    if ( $cache !== null ) return $cache;

    // 1. Cloudflare
    if ( ! empty( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) {
        $c = strtoupper( sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) );
        if ( strlen( $c ) === 2 && ctype_alpha( $c ) && $c !== 'XX' && $c !== 'T1' ) {
            $cache = strtolower( $c );
            return $cache;
        }
    }

    // 2. CDN/Proxy header
    if ( ! empty( $_SERVER['HTTP_X_COUNTRY_CODE'] ) ) {
        $c = strtoupper( sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_COUNTRY_CODE'] ) ) );
        if ( strlen( $c ) === 2 && ctype_alpha( $c ) ) {
            $cache = strtolower( $c );
            return $cache;
        }
    }

    // 3. GeoIP Detect eklentisi
    if ( function_exists( 'geoip_detect2_get_info_from_current_ip' ) ) {
        $record = geoip_detect2_get_info_from_current_ip();
        if ( $record && ! empty( $record->country->isoCode ) ) {
            $cache = strtolower( $record->country->isoCode );
            return $cache;
        }
    }

    // 4. ip-api.com (ücretsiz, 45 req/dk limit — session cache ile yeterli)
    $ip = radiotheme_get_real_ip();
    if ( $ip && ! in_array( $ip, [ '127.0.0.1', '::1' ], true ) ) {
        $cached_country = get_transient( 'rt_geoip_' . md5( $ip ) );
        if ( $cached_country !== false ) {
            $cache = $cached_country;
            return $cache;
        }

        $response = wp_remote_get(
            'http://ip-api.com/json/' . $ip . '?fields=countryCode',
            [ 'timeout' => 2, 'sslverify' => false ]
        );

        if ( ! is_wp_error( $response ) ) {
            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( ! empty( $body['countryCode'] ) ) {
                $country = strtolower( $body['countryCode'] );
                set_transient( 'rt_geoip_' . md5( $ip ), $country, HOUR_IN_SECONDS * 6 );
                $cache = $country;
                return $cache;
            }
        }
    }

    // Varsayılan: global/en
    $cache = 'us';
    return $cache;
}

/**
 * Gerçek IP adresini al (proxy arkasında da çalışır).
 */
function radiotheme_get_real_ip(): string {
    $keys = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'REMOTE_ADDR',
    ];
    foreach ( $keys as $key ) {
        if ( ! empty( $_SERVER[ $key ] ) ) {
            $ip = trim( explode( ',', wp_unslash( $_SERVER[ $key ] ) )[0] );
            if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                return $ip;
            }
        }
    }
    return '';
}

/* ============================================================
   4. REWRITE RULES
   WordPress'e yeni URL yapısını öğret.
   ============================================================ */

add_action( 'init', 'radiotheme_register_country_rewrites', 5 );

function radiotheme_register_country_rewrites(): void {

    // Desteklenen tüm ISO kodlarını al
    $iso_codes   = array_map( 'strtolower', array_values( radiotheme_get_country_map() ) );
    $iso_pattern = implode( '|', array_unique( $iso_codes ) );

    /*
     * /tr/station/arabesk-fm/  →  single radio-station
     * WP post_type + name ile single post'u çözer
     */
    add_rewrite_rule(
        '^(' . $iso_pattern . ')/station/([^/]+)/?$',
        'index.php?radio-station=$matches[2]&post_type=radio-station&name=$matches[2]&rt_country=$matches[1]',
        'top'
    );

    /*
     * /tr/genres/  →  ülke genre listesi sayfası
     */
    add_rewrite_rule(
        '^(' . $iso_pattern . ')/genres/?$',
        'index.php?rt_country=$matches[1]&rt_genres_page=1',
        'top'
    );

    /*
     * /tr/genre/news/  →  genre taxonomy arşivi
     */
    add_rewrite_rule(
        '^(' . $iso_pattern . ')/genre/([^/]+)/?$',
        'index.php?radio-genre=$matches[2]&rt_country=$matches[1]',
        'top'
    );

    /*
     * /tr/city/istanbul/  →  city taxonomy arşivi
     */
    add_rewrite_rule(
        '^(' . $iso_pattern . ')/city/([^/]+)/?$',
        'index.php?radio-city=$matches[2]&rt_country=$matches[1]',
        'top'
    );

    /*
     * /tr/  →  anasayfa + ülke filtresi
     */
    add_rewrite_rule(
        '^(' . $iso_pattern . ')/?$',
        'index.php?rt_country=$matches[1]',
        'top'
    );
}

/* Query var kayıt */
add_filter( 'query_vars', function( $vars ) {
    $vars[] = 'rt_country';
    $vars[] = 'rt_genres_page';
    return $vars;
} );

/* ============================================================
   5. ANASAYFA YÖNLENDİRME
   /s/ → IP tespiti → /s/tr/ gibi
   ============================================================ */

add_action( 'template_redirect', 'radiotheme_country_redirect', 1 );

function radiotheme_country_redirect(): void {
    // Sadece anasayfada çalış ve zaten ülke kodu yoksa
    if ( ! is_front_page() && ! is_home() ) return;
    if ( radiotheme_get_url_country_code() !== '' ) return;

    // ?all=1 parametresi varsa yönlendirme — "All Countries" sayfası
    if ( ! empty( $_GET['all'] ) ) return;

    // Bot kontrolü — botları yönlendirme
    $ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
    $bots = [ 'googlebot', 'bingbot', 'slurp', 'duckduckbot', 'baiduspider', 'yandex', 'facebot', 'ia_archiver' ];
    foreach ( $bots as $bot ) {
        if ( strpos( $ua, $bot ) !== false ) return;
    }

    $country_code = radiotheme_detect_user_country();

    // Taxonomy'de bu ülke var mı?
    $slug = radiotheme_iso_to_slug( $country_code );
    if ( ! $slug ) {
        $country_code = 'us'; // fallback
    }

    $redirect_url = home_url( '/' . $country_code . '/' );
    wp_safe_redirect( $redirect_url, 302 );
    exit;
}

/* ============================================================
   6. TEMPLATE ROUTING
   ============================================================ */

add_filter( 'template_include', 'radiotheme_country_template', 98 );

function radiotheme_country_template( string $template ): string {

    /* URL'den ülke kodunu parse et */
    $uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
    $home_path = rtrim( wp_parse_url( home_url(), PHP_URL_PATH ) ?? '', '/' );
    if ( $home_path && strpos( $uri, $home_path ) === 0 ) {
        $uri = substr( $uri, strlen( $home_path ) );
    }
    $uri = '/' . ltrim( $uri, '/' );

    /* URI'den ülke kodu var mı? */
    $url_cc = '';
    if ( preg_match( '#^/([a-z]{2})(/|$)#i', $uri, $m ) ) {
        $test = strtolower( $m[1] );
        if ( radiotheme_iso_to_slug( $test ) !== '' ) {
            $url_cc = $test;
        }
    }

    /* /de/genres/ → genres-page.php — is_home() olsa bile önce kontrol et */
    if ( preg_match( '#^/[a-z]{2}/genres/?(\?.*)?$#i', $uri ) ) {
        $gp = get_template_directory() . '/genres-page.php';
        if ( file_exists( $gp ) ) return $gp;
    }

    /* WP query var da dene */
    $country_code = get_query_var( 'rt_country', '' ) ?: $url_cc;
    if ( ! $country_code ) return $template;

    /* İstasyon detay sayfası — dokunma */
    if ( is_singular( 'radio-station' ) ) return $template;

    /* Genre veya city taxonomy arşivi → front-page.php */
    if ( is_tax( 'radio-genre' ) || is_tax( 'radio-city' ) ) {
        $fp = get_template_directory() . '/front-page.php';
        if ( file_exists( $fp ) ) return $fp;
    }

    /* Diğer taxonomy arşivleri — dokunma */
    if ( is_tax() ) return $template;

    /* Ülke ana sayfası veya diğer ülke sayfaları */
    $fp = get_template_directory() . '/front-page.php';
    if ( file_exists( $fp ) ) return $fp;

    return $template;
}

/* ============================================================
   7. CANONICAL URL — SEO
   /s/tr/ sayfası için doğru canonical üret
   ============================================================ */

add_filter( 'wpseo_canonical', 'radiotheme_country_canonical' );

function radiotheme_country_canonical( string $canonical ): string {
    $cc = get_query_var( 'rt_country', '' );
    if ( ! $cc ) return $canonical;
    return home_url( '/' . $cc . '/' );
}

/* ============================================================
   8. İSTASYON DETAY SAYFASI URL'İ
   get_permalink() yerine ülke kodlu URL üret.
   ============================================================ */

/**
 * Bir istasyonun ülke kodlu URL'ini döner.
 * Örn: /s/tr/station/arabesk-fm/
 *
 * @param int    $post_id  İstasyon post ID
 * @param string $cc       Ülke kodu (ör: 'tr'). Boş ise istasyonun country_code alanından alır.
 */
function radiotheme_station_url( int $post_id, string $cc = '' ): string {
    if ( ! $cc ) {
        $cc = strtolower( radiotheme_get_field( 'country_code', $post_id ) ?: '' );
    } else {
        $cc = strtolower( $cc ); // Dışarıdan büyük harf gelebilir
    }

    $post = get_post( $post_id );
    if ( ! $post ) return get_permalink( $post_id );

    $slug = $post->post_name;

    if ( $cc && radiotheme_iso_to_slug( $cc ) !== '' ) {
        return home_url( '/' . $cc . '/station/' . $slug . '/' );
    }

    // Ülke kodu yoksa taxonomy'den dene
    $countries = get_the_terms( $post_id, 'radio-country' );
    if ( $countries && ! is_wp_error( $countries ) ) {
        $country_slug = $countries[0]->slug;
        $iso          = strtolower( radiotheme_slug_to_iso( $country_slug ) );
        if ( $iso ) {
            return home_url( '/' . $iso . '/station/' . $slug . '/' );
        }
    }

    return get_permalink( $post_id );
}

/**
 * Bir genre'nin ülke kodlu URL'ini döner.
 * Örn: /s/tr/genre/haber/
 */
function radiotheme_genre_url( string $genre_slug, string $cc = '' ): string {
    if ( ! $cc ) {
        $cc = radiotheme_get_url_country_code();
    }
    if ( $cc ) {
        return home_url( '/' . $cc . '/genre/' . $genre_slug . '/' );
    }
    return get_term_link( $genre_slug, 'radio-genre' );
}

/**
 * Bir şehrin ülke kodlu URL'ini döner.
 * Örn: /s/tr/city/istanbul/
 */
function radiotheme_city_url( string $city_slug, string $cc = '' ): string {
    if ( ! $cc ) {
        $cc = radiotheme_get_url_country_code();
    }
    if ( $cc ) {
        return home_url( '/' . $cc . '/city/' . $city_slug . '/' );
    }
    return get_term_link( $city_slug, 'radio-city' );
}

/**
 * Ülke ana sayfası URL'i.
 * Örn: /s/tr/
 */
function radiotheme_country_url( string $cc ): string {
    return home_url( '/' . strtolower( $cc ) . '/' );
}

/* ============================================================
   9. JS'E VERİ AKTAR
   Frontend AJAX istekleri için ülke kodunu JS'e gönder.
   ============================================================ */

add_action( 'wp_enqueue_scripts', function() {
    /* ?all=1 → tüm ülkeler sayfası — ülke kodu boş olmalı */
    $show_all = ! empty( $_GET['all'] );

    $cc      = $show_all ? '' : ( radiotheme_get_url_country_code() ?: radiotheme_detect_user_country() );
    $cc_slug = $cc ? radiotheme_iso_to_slug( $cc ) : '';

    // Genre/city taxonomy URL'lerinden aktif filtreyi al
    $active_genre = get_query_var( 'radio-genre', '' );
    $active_city  = get_query_var( 'radio-city', '' );

    // Tüm ülke → bayrak eşlemesini JS'e gönder
    $flag_map = [];
    $country_map = radiotheme_get_country_map(); // [ 'turkey' => 'TR', ... ]
    foreach ( $country_map as $slug => $iso ) {
        $iso_lower = strtolower( $iso );
        $flag_map[ $iso_lower ] = radiotheme_iso_to_flag_emoji( $iso );
    }

    wp_add_inline_script(
        'radiotheme-player',
        'window.radioThemeData = Object.assign(window.radioThemeData || {}, ' . wp_json_encode( [
            'countryCode'   => $cc,
            'countrySlug'   => $cc_slug,
            'countryUrl'    => $cc ? home_url( '/' . $cc . '/' ) : home_url( '/?all=1' ),
            'userCountry'   => radiotheme_detect_user_country(),
            'showAll'       => $show_all,
            'activeGenre'   => $active_genre,
            'activeCity'    => $active_city,
            'flagMap'       => $flag_map,
            'homeUrl'       => rtrim( home_url(), '/' ),
        ] ) . ');',
        'after'
    );

    // AJAX navigation sonrası header bayrağını güncelleyen JS
    wp_add_inline_script( 'radiotheme-player', "
(function() {
    function updateHeaderCountry(cc) {
        var flagEl  = document.getElementById('header-country-flag');
        var nameEl  = document.getElementById('header-country-name');
        var linkEl  = document.getElementById('header-country-info');
        var genresA = document.querySelector('.header-nav-link[href*=\"/genres/\"]');
        if (!flagEl || !nameEl) return;

        var flagMap  = (window.radioThemeData && window.radioThemeData.flagMap) || {};
        var homeUrl  = (window.radioThemeData && window.radioThemeData.homeUrl) || '/';

        if (cc) {
            var flag = flagMap[cc] || '🌍';
            flagEl.textContent = flag;
            if (linkEl) linkEl.href = homeUrl + '/' + cc + '/';
            if (genresA) genresA.href = homeUrl + '/' + cc + '/genres/';
        } else {
            flagEl.textContent = '🌍';
            if (linkEl) linkEl.href = homeUrl + '/';
            if (genresA) genresA.href = homeUrl + '/genres/';
        }
        nameEl.textContent = 'All Stations';
    }

    document.addEventListener('rt:navigation-done', function(e) {
        var cc = (e.detail && e.detail.countryCode) || '';
        updateHeaderCountry(cc);
    });
})();
", 'after' );
}, 25 );

/* ============================================================
   10. SAYFA BAŞLIĞI (SEO) — ülke, genre, city, istasyon
   ============================================================ */

add_filter( 'wpseo_title', 'radiotheme_page_title', 25 );
add_filter( 'pre_get_document_title', 'radiotheme_page_title', 25 );

function radiotheme_page_title( string $title ): string {
    $cc        = get_query_var( 'rt_country', '' );
    $site_name = get_bloginfo( 'name' );

    $country_name = '';
    if ( $cc ) {
        $slug         = radiotheme_iso_to_slug( $cc );
        $country_name = $slug ? ucwords( str_replace( '-', ' ', $slug ) ) : strtoupper( $cc );
    }

    /* --- İstasyon detay sayfası --- */
    if ( is_singular( 'radio-station' ) ) {
        $station_name = get_the_title();
        if ( $country_name ) {
            return sprintf( '%s | %s Radio | %s', $station_name, $country_name, $site_name );
        }
        return sprintf( '%s | %s', $station_name, $site_name );
    }

    /* --- Genre + ülke sayfası: /de/genre/90s/ --- */
    $genre_slug = get_query_var( 'radio-genre', '' );
    if ( $genre_slug ) {
        $genre_term  = get_term_by( 'slug', $genre_slug, 'radio-genre' );
        $genre_name  = $genre_term ? $genre_term->name : ucfirst( $genre_slug );
        if ( $country_name ) {
            return sprintf( '%s Radio in %s | %s', $genre_name, $country_name, $site_name );
        }
        return sprintf( '%s Radio Stations | %s', $genre_name, $site_name );
    }

    /* --- City + ülke sayfası: /de/city/berlin/ --- */
    $city_slug = get_query_var( 'radio-city', '' );
    if ( $city_slug ) {
        $city_term  = get_term_by( 'slug', $city_slug, 'radio-city' );
        $city_name  = $city_term ? $city_term->name : ucfirst( $city_slug );
        if ( $country_name ) {
            return sprintf( '%s Radio Stations in %s | %s', $city_name, $country_name, $site_name );
        }
        return sprintf( '%s Radio Stations | %s', $city_name, $site_name );
    }

    /* --- Ülke ana sayfası: /de/ --- */
    if ( $country_name ) {
        return sprintf( '%s Radio Stations | %s', $country_name, $site_name );
    }

    return $title;
}

/* ============================================================
   11. ÜLKE SAYFASI META AÇIKLAMASI
   ============================================================ */

add_filter( 'wpseo_metadesc', 'radiotheme_country_page_metadesc', 25 );

function radiotheme_country_page_metadesc( string $desc ): string {
    if ( ! empty( $desc ) ) return $desc;

    $cc           = get_query_var( 'rt_country', '' );
    $site_name    = get_bloginfo( 'name' );
    $country_name = '';

    if ( $cc ) {
        $slug         = radiotheme_iso_to_slug( $cc );
        $country_name = $slug ? ucwords( str_replace( '-', ' ', $slug ) ) : strtoupper( $cc );
    }

    /* İstasyon detay */
    if ( is_singular( 'radio-station' ) ) {
        $name = get_the_title();
        if ( $country_name ) {
            return sprintf( 'Listen to %s live online. Stream free %s radio at %s.', $name, $country_name, $site_name );
        }
        return sprintf( 'Listen to %s live online. Free radio streaming at %s.', $name, $site_name );
    }

    /* Genre sayfası */
    $genre_slug = get_query_var( 'radio-genre', '' );
    if ( $genre_slug ) {
        $genre_term = get_term_by( 'slug', $genre_slug, 'radio-genre' );
        $genre_name = $genre_term ? $genre_term->name : ucfirst( $genre_slug );
        if ( $country_name ) {
            return sprintf( 'Stream free %s radio stations from %s. Listen live online at %s.', $genre_name, $country_name, $site_name );
        }
        return sprintf( 'Listen to free %s radio stations online. Stream live at %s.', $genre_name, $site_name );
    }

    /* City sayfası */
    $city_slug = get_query_var( 'radio-city', '' );
    if ( $city_slug ) {
        $city_term = get_term_by( 'slug', $city_slug, 'radio-city' );
        $city_name = $city_term ? $city_term->name : ucfirst( $city_slug );
        if ( $country_name ) {
            return sprintf( 'Stream local radio stations from %s, %s. Listen live online at %s.', $city_name, $country_name, $site_name );
        }
        return sprintf( 'Listen to radio stations from %s live online at %s.', $city_name, $site_name );
    }

    /* Ülke ana sayfası */
    if ( $country_name ) {
        return sprintf(
            'Listen to live radio stations from %s online. Stream free %s radio - news, music, sports and more.',
            $country_name, $country_name
        );
    }

    return $desc;
}
