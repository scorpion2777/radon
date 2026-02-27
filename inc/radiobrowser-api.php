<?php
/**
 * RadioTheme - inc/radiobrowser-api.php
 * Radio-Browser.info API integration
 * Imports radio stations in bulk from the public API
 * API docs: https://api.radio-browser.info/
 *
 * v2.0 — Optimizasyonlar:
 *  - UUID bulk lookup: tek WP_Query ile tüm mevcut UUID'ler çekilir,
 *    her istasyon için ayrı DB sorgusu yapılmaz.
 *  - Ülke istatistikleri: bayraklı tablo, import öncesi ve sonrası sayılar.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ============================================================
   CONSTANTS
   ============================================================ */
define( 'RADIOBROWSER_API_BASE',  'https://de1.api.radio-browser.info/json' );
define( 'RADIOBROWSER_BATCH_SIZE', 50 );
define( 'RADIOBROWSER_TIMEOUT',    30 );
define( 'RADIOBROWSER_CACHE_TTL',  3600 );

/* ============================================================
   ISO → Bayrak emoji yardımcısı
   ============================================================ */
function radiotheme_country_flag( $iso2 ) {
    $iso2 = strtoupper( trim( $iso2 ) );
    if ( strlen( $iso2 ) !== 2 ) return '🌐';
    $flag = '';
    foreach ( str_split( $iso2 ) as $ch ) {
        $flag .= mb_chr( ord( $ch ) - ord( 'A' ) + 0x1F1E6, 'UTF-8' );
    }
    return $flag;
}

/* ============================================================
   ADMIN MENU
   ============================================================ */
add_action( 'admin_menu', 'radiotheme_radiobrowser_admin_menu' );

function radiotheme_radiobrowser_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=radio-station',
        __( 'Import from RadioBrowser', 'radiotheme' ),
        __( 'Import Stations', 'radiotheme' ),
        'manage_options',
        'radiobrowser-import',
        'radiotheme_radiobrowser_import_page'
    );
}

/* ============================================================
   ADMIN IMPORT PAGE
   ============================================================ */
function radiotheme_radiobrowser_import_page() {

    $result = null;

    if ( isset( $_POST['radiobrowser_import_nonce'] ) ) {
        check_admin_referer( 'radiobrowser_import_action', 'radiobrowser_import_nonce' );

        if ( current_user_can( 'manage_options' ) ) {
            $country   = isset( $_POST['import_country'] )   ? sanitize_text_field( wp_unslash( $_POST['import_country'] ) )   : '';
            $genre     = isset( $_POST['import_genre'] )     ? sanitize_text_field( wp_unslash( $_POST['import_genre'] ) )     : '';
            $limit     = isset( $_POST['import_limit'] )     ? absint( $_POST['import_limit'] )                                : 100;
            $min_votes = isset( $_POST['import_min_votes'] ) ? absint( $_POST['import_min_votes'] )                            : 0;

            $result = radiotheme_radiobrowser_import( array(
                'country'   => $country,
                'genre'     => $genre,
                'limit'     => min( $limit, 1000 ),
                'min_votes' => $min_votes,
            ) );
        }
    }

    /* --- Mevcut ülke istatistikleri --- */
    $country_stats = radiotheme_get_country_stats();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Import Radio Stations from RadioBrowser.info', 'radiotheme' ); ?></h1>
        <p><?php esc_html_e( 'Import radio stations in bulk from the free RadioBrowser.info API.', 'radiotheme' ); ?></p>

        <?php if ( $result !== null ) : ?>
            <?php if ( is_wp_error( $result ) ) : ?>
                <div class="notice notice-error"><p><?php echo esc_html( $result->get_error_message() ); ?></p></div>
            <?php else : ?>
                <div class="notice notice-success">
                    <p>
                        <?php printf(
                            /* translators: %1$d: imported, %2$d: skipped, %3$d: errors */
                            esc_html__( 'Import complete — %1$d imported, %2$d skipped (already exists), %3$d errors.', 'radiotheme' ),
                            (int) $result['imported'],
                            (int) $result['skipped'],
                            (int) $result['errors']
                        ); ?>
                    </p>
                </div>
                <?php /* Import sonrası ülke tablosunu yenile */ ?>
                <?php $country_stats = radiotheme_get_country_stats(); ?>
            <?php endif; ?>
        <?php endif; ?>

        <div style="display:flex;gap:32px;align-items:flex-start;flex-wrap:wrap;">

            <!-- Import Formu -->
            <div style="flex:1;min-width:320px;">
                <h2><?php esc_html_e( 'Import Settings', 'radiotheme' ); ?></h2>
                <form method="post" action="">
                    <?php wp_nonce_field( 'radiobrowser_import_action', 'radiobrowser_import_nonce' ); ?>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row">
                                <label for="import_country"><?php esc_html_e( 'Country Code (ISO)', 'radiotheme' ); ?></label>
                            </th>
                            <td>
                                <input type="text" id="import_country" name="import_country" class="regular-text"
                                       placeholder="TR" maxlength="2"
                                       value="<?php echo esc_attr( $_POST['import_country'] ?? '' ); ?>">
                                <p class="description"><?php esc_html_e( 'ISO 3166-1 alpha-2 code (e.g. TR, DE, FR). Leave empty for all countries.', 'radiotheme' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="import_genre"><?php esc_html_e( 'Genre / Tag', 'radiotheme' ); ?></label>
                            </th>
                            <td>
                                <input type="text" id="import_genre" name="import_genre" class="regular-text"
                                       placeholder="pop"
                                       value="<?php echo esc_attr( $_POST['import_genre'] ?? '' ); ?>">
                                <p class="description"><?php esc_html_e( 'Filter by genre/tag. Leave empty for all genres.', 'radiotheme' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="import_limit"><?php esc_html_e( 'Max Stations to Import', 'radiotheme' ); ?></label>
                            </th>
                            <td>
                                <input type="number" id="import_limit" name="import_limit" min="1" max="1000"
                                       value="<?php echo esc_attr( $_POST['import_limit'] ?? '100' ); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="import_min_votes"><?php esc_html_e( 'Minimum Votes', 'radiotheme' ); ?></label>
                            </th>
                            <td>
                                <input type="number" id="import_min_votes" name="import_min_votes" min="0"
                                       value="<?php echo esc_attr( $_POST['import_min_votes'] ?? '10' ); ?>">
                                <p class="description"><?php esc_html_e( 'Only import stations with at least this many votes. Filters out low-quality stations.', 'radiotheme' ); ?></p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button( __( 'Start Import', 'radiotheme' ), 'primary', 'submit', false ); ?>
                </form>
            </div>

            <!-- Ülke İstatistikleri -->
            <div style="flex:0 0 280px;">
                <h2>
                    <?php esc_html_e( 'Stations by Country', 'radiotheme' ); ?>
                    <span style="font-size:13px;font-weight:400;color:#666;margin-left:8px;">
                        (<?php echo esc_html( array_sum( array_column( $country_stats, 'count' ) ) ); ?> <?php esc_html_e( 'total', 'radiotheme' ); ?>)
                    </span>
                </h2>

                <?php if ( empty( $country_stats ) ) : ?>
                    <p style="color:#999;"><?php esc_html_e( 'No stations imported yet.', 'radiotheme' ); ?></p>
                <?php else : ?>
                    <table class="widefat striped" style="max-width:280px;">
                        <thead>
                            <tr>
                                <th style="width:36px;padding:6px 8px;"></th>
                                <th style="padding:6px 8px;"><?php esc_html_e( 'Country', 'radiotheme' ); ?></th>
                                <th style="padding:6px 8px;text-align:right;"><?php esc_html_e( 'Stations', 'radiotheme' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $country_stats as $row ) : ?>
                                <tr>
                                    <td style="padding:5px 8px;font-size:20px;line-height:1;">
                                        <?php echo esc_html( radiotheme_country_flag( $row['code'] ) ); ?>
                                    </td>
                                    <td style="padding:5px 8px;">
                                        <?php echo esc_html( $row['name'] ); ?>
                                    </td>
                                    <td style="padding:5px 8px;text-align:right;font-weight:600;">
                                        <?php echo esc_html( number_format_i18n( $row['count'] ) ); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

        </div><!-- flex wrap -->
    </div>
    <?php
}

/* ============================================================
   ÜLKE İSTATİSTİKLERİ
   Taxonomy'den ülke bazında radyo sayılarını çeker.
   ============================================================ */
function radiotheme_get_country_stats() {
    $terms = get_terms( array(
        'taxonomy'   => 'radio-country',
        'hide_empty' => true,
        'orderby'    => 'count',
        'order'      => 'DESC',
        'number'     => 0,
    ) );

    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return array();
    }

    $stats = array();
    foreach ( $terms as $term ) {
        /* ISO kodu: önce term slug'a bak, sonra radiotheme_english_to_country_code */
        $code = radiotheme_country_name_to_iso( $term->name );
        $stats[] = array(
            'name'  => $term->name,
            'code'  => $code,
            'count' => (int) $term->count,
        );
    }

    return $stats;
}

/**
 * Ülke adından ISO2 kodu tahmin et.
 * Tam eşleşme listesi — eksikler için '' döner, flag fonksiyonu 🌐 gösterir.
 */
function radiotheme_country_name_to_iso( $name ) {
    static $map = array(
        'Afghanistan'=>'AF','Albania'=>'AL','Algeria'=>'DZ','Argentina'=>'AR',
        'Armenia'=>'AM','Australia'=>'AU','Austria'=>'AT','Azerbaijan'=>'AZ',
        'Bangladesh'=>'BD','Belarus'=>'BY','Belgium'=>'BE','Bolivia'=>'BO',
        'Bosnia and Herzegovina'=>'BA','Brazil'=>'BR','Bulgaria'=>'BG',
        'Cambodia'=>'KH','Cameroon'=>'CM','Canada'=>'CA','Chile'=>'CL',
        'China'=>'CN','Colombia'=>'CO','Croatia'=>'HR','Cuba'=>'CU',
        'Czech Republic'=>'CZ','Denmark'=>'DK','Dominican Republic'=>'DO',
        'Ecuador'=>'EC','Egypt'=>'EG','El Salvador'=>'SV','Estonia'=>'EE',
        'Ethiopia'=>'ET','Finland'=>'FI','France'=>'FR','Georgia'=>'GE',
        'Germany'=>'DE','Ghana'=>'GH','Greece'=>'GR','Guatemala'=>'GT',
        'Haiti'=>'HT','Honduras'=>'HN','Hungary'=>'HU','India'=>'IN',
        'Indonesia'=>'ID','Iran'=>'IR','Iraq'=>'IQ','Ireland'=>'IE',
        'Israel'=>'IL','Italy'=>'IT','Jamaica'=>'JM','Japan'=>'JP',
        'Jordan'=>'JO','Kazakhstan'=>'KZ','Kenya'=>'KE','Kosovo'=>'XK',
        'Kuwait'=>'KW','Kyrgyzstan'=>'KG','Latvia'=>'LV','Lebanon'=>'LB',
        'Libya'=>'LY','Lithuania'=>'LT','Luxembourg'=>'LU','Malaysia'=>'MY',
        'Mexico'=>'MX','Moldova'=>'MD','Montenegro'=>'ME','Morocco'=>'MA',
        'Mozambique'=>'MZ','Myanmar'=>'MM','Nepal'=>'NP','Netherlands'=>'NL',
        'New Zealand'=>'NZ','Nicaragua'=>'NI','Nigeria'=>'NG','North Macedonia'=>'MK',
        'Norway'=>'NO','Pakistan'=>'PK','Palestine'=>'PS','Panama'=>'PA',
        'Paraguay'=>'PY','Peru'=>'PE','Philippines'=>'PH','Poland'=>'PL',
        'Portugal'=>'PT','Puerto Rico'=>'PR','Qatar'=>'QA','Romania'=>'RO',
        'Russia'=>'RU','Saudi Arabia'=>'SA','Senegal'=>'SN','Serbia'=>'RS',
        'Slovakia'=>'SK','Slovenia'=>'SI','Somalia'=>'SO','South Africa'=>'ZA',
        'South Korea'=>'KR','Spain'=>'ES','Sri Lanka'=>'LK','Sudan'=>'SD',
        'Sweden'=>'SE','Switzerland'=>'CH','Syria'=>'SY','Taiwan'=>'TW',
        'Tajikistan'=>'TJ','Tanzania'=>'TZ','Thailand'=>'TH','Tunisia'=>'TN',
        'Turkey'=>'TR','Turkmenistan'=>'TM','Uganda'=>'UG','Ukraine'=>'UA',
        'United Arab Emirates'=>'AE','United Kingdom'=>'GB','United States'=>'US',
        'Uruguay'=>'UY','Uzbekistan'=>'UZ','Venezuela'=>'VE','Vietnam'=>'VN',
        'Yemen'=>'YE','Zimbabwe'=>'ZW',
    );
    return $map[ $name ] ?? '';
}

/* ============================================================
   IMPORT FUNCTION
   ============================================================ */
function radiotheme_radiobrowser_import( $params = array() ) {
    $params = wp_parse_args( $params, array(
        'country'   => '',
        'genre'     => '',
        'limit'     => 100,
        'min_votes' => 0,
        'offset'    => 0,
    ) );

    $stations = radiotheme_radiobrowser_fetch_stations( $params );

    if ( is_wp_error( $stations ) ) return $stations;
    if ( empty( $stations ) ) return array( 'imported' => 0, 'skipped' => 0, 'errors' => 0, 'total' => 0 );

    /* -------------------------------------------------------
       OPTİMİZASYON: Tek sorguda mevcut UUID'leri çek
       Her istasyon için ayrı get_posts yapmak yerine
       gelen UUID listesini bir kerede sorgula.
    ------------------------------------------------------- */
    $incoming_uuids = array_filter( array_column( $stations, 'stationuuid' ) );

    /* Mevcut UUID → post_id haritası */
    $existing_map = array();

    if ( ! empty( $incoming_uuids ) ) {
        $existing_posts = get_posts( array(
            'post_type'      => 'radio-station',
            'post_status'    => 'any',
            'posts_per_page' => count( $incoming_uuids ),
            'no_found_rows'  => true,
            'fields'         => 'ids',
            'meta_query'     => array(
                array(
                    'key'     => 'radiobrowser_id',
                    'value'   => $incoming_uuids,
                    'compare' => 'IN',
                ),
            ),
        ) );

        foreach ( $existing_posts as $pid ) {
            $uuid = get_post_meta( $pid, 'radiobrowser_id', true );
            if ( $uuid ) $existing_map[ $uuid ] = $pid;
        }
    }

    $imported = 0;
    $skipped  = 0;
    $errors   = 0;

    foreach ( $stations as $station ) {
        $uuid = sanitize_text_field( $station['stationuuid'] ?? '' );

        /* Zaten varsa atla — DB'ye dokunma */
        if ( isset( $existing_map[ $uuid ] ) ) {
            $skipped++;
            continue;
        }

        $result = radiotheme_radiobrowser_insert_station( $station );

        if ( $result === 'imported' )      $imported++;
        elseif ( $result === 'skipped' )   $skipped++;
        else                               $errors++;
    }

    return array(
        'imported' => $imported,
        'skipped'  => $skipped,
        'errors'   => $errors,
        'total'    => count( $stations ),
    );
}

/* ============================================================
   FETCH STATIONS FROM API
   ============================================================ */
function radiotheme_radiobrowser_fetch_stations( $params ) {
    $api_params = array(
        'limit'      => min( absint( $params['limit'] ), 1000 ),
        'offset'     => absint( $params['offset'] ),
        'order'      => 'votes',
        'reverse'    => 'true',
        'hidebroken' => 'true',
    );

    if ( ! empty( $params['country'] ) ) {
        $api_params['countrycode'] = strtoupper( sanitize_text_field( $params['country'] ) );
    }
    if ( ! empty( $params['genre'] ) ) {
        $api_params['tag'] = sanitize_text_field( $params['genre'] );
    }
    if ( ! empty( $params['min_votes'] ) ) {
        $api_params['minvotes'] = absint( $params['min_votes'] );
    }

    $api_url   = RADIOBROWSER_API_BASE . '/stations/search?' . http_build_query( $api_params );
    $cache_key = 'radiobrowser_' . md5( $api_url );
    $cached    = get_transient( $cache_key );
    if ( $cached !== false ) return $cached;

    $response = wp_remote_get( $api_url, array(
        'timeout'    => RADIOBROWSER_TIMEOUT,
        'user-agent' => 'RadioTheme/1.0 (WordPress; ' . home_url() . ')',
        'headers'    => array( 'Accept' => 'application/json' ),
    ) );

    if ( is_wp_error( $response ) ) return $response;

    $status_code = wp_remote_retrieve_response_code( $response );
    if ( $status_code !== 200 ) {
        return new WP_Error( 'api_error',
            sprintf( __( 'RadioBrowser API returned status code %d.', 'radiotheme' ), $status_code )
        );
    }

    $data = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( json_last_error() !== JSON_ERROR_NONE ) {
        return new WP_Error( 'json_error', __( 'Failed to parse RadioBrowser API response.', 'radiotheme' ) );
    }

    set_transient( $cache_key, $data, RADIOBROWSER_CACHE_TTL );
    return $data;
}

/* ============================================================
   INSERT / UPDATE SINGLE STATION
   ============================================================ */
function radiotheme_radiobrowser_insert_station( $station_data ) {
    if ( empty( $station_data['stationuuid'] ) || empty( $station_data['url_resolved'] ) ) {
        return 'skipped';
    }

    $rb_id        = sanitize_text_field( $station_data['stationuuid'] );
    $name         = sanitize_text_field( $station_data['name'] ?? 'Unknown Station' );
    $stream_url   = esc_url_raw( $station_data['url_resolved'] );
    $logo_url     = esc_url_raw( $station_data['favicon'] ?? '' );
    $country_code = strtoupper( sanitize_text_field( $station_data['countrycode'] ?? '' ) );
    $country      = $country_code
                    ? radiotheme_country_code_to_english( $country_code )
                    : sanitize_text_field( $station_data['country'] ?? '' );
    $city         = sanitize_text_field( $station_data['state'] ?? '' );
    $language     = sanitize_text_field( $station_data['language'] ?? '' );
    $tags         = sanitize_text_field( $station_data['tags'] ?? '' );
    $votes        = absint( $station_data['votes'] ?? 0 );
    $bitrate      = absint( $station_data['bitrate'] ?? 0 );
    $codec        = sanitize_text_field( $station_data['codec'] ?? 'MP3' );

    /* Yeni kayıt ekle */
    $post_id = wp_insert_post( array(
        'post_title'   => $name,
        'post_type'    => 'radio-station',
        'post_status'  => 'publish',
        'post_content' => '',
    ) );

    if ( is_wp_error( $post_id ) ) return 'error';

    /* ACF / meta alanları */
    update_field( 'stream_url',       $stream_url,          $post_id );
    radiotheme_register_proxy_host( $stream_url ); // HTTP ise whitelist'e ekle
    update_field( 'station_logo_url', $logo_url,            $post_id );
    update_field( 'country_code',     $country_code,        $post_id );
    update_field( 'stream_bitrate',   $bitrate,             $post_id );
    update_field( 'stream_codec',     strtoupper( $codec ), $post_id );
    update_field( 'station_votes',    $votes,               $post_id );
    update_field( 'station_active',   1,                    $post_id );
    update_field( 'radiobrowser_id',  $rb_id,               $post_id );
    update_field( 'last_checked',     current_time( 'mysql' ), $post_id );

    /* Taxonomy: ülke */
    if ( $country ) {
        $term = radiotheme_get_or_create_term( $country, 'radio-country' );
        if ( $term ) wp_set_object_terms( $post_id, array( $term ), 'radio-country', false );
    }

    /* Taxonomy: şehir */
    if ( $city ) {
        $term = radiotheme_get_or_create_term( $city, 'radio-city' );
        if ( $term ) wp_set_object_terms( $post_id, array( $term ), 'radio-city', false );
    }

    /* Taxonomy: dil */
    if ( $language ) {
        $lang_ids = array();
        foreach ( array_map( 'trim', explode( ',', $language ) ) as $lang ) {
            if ( empty( $lang ) ) continue;
            $term = radiotheme_get_or_create_term( ucfirst( $lang ), 'radio-language' );
            if ( $term ) $lang_ids[] = $term;
        }
        if ( ! empty( $lang_ids ) ) wp_set_object_terms( $post_id, $lang_ids, 'radio-language', false );
    }

    /* Taxonomy: tür (tags'ten, max 5) */
    if ( $tags ) {
        $genre_ids = array();
        foreach ( array_map( 'trim', explode( ',', $tags ) ) as $tag ) {
            if ( empty( $tag ) || strlen( $tag ) > 50 ) continue;
            $term = radiotheme_get_or_create_term( ucfirst( $tag ), 'radio-genre' );
            if ( $term ) $genre_ids[] = $term;
            if ( count( $genre_ids ) >= 5 ) break;
        }
        if ( ! empty( $genre_ids ) ) wp_set_object_terms( $post_id, $genre_ids, 'radio-genre', false );
    }

    return 'imported';
}

/* ============================================================
   YARDIMCI: Taxonomy term al veya oluştur
   ============================================================ */
function radiotheme_get_or_create_term( $name, $taxonomy ) {
    if ( empty( $name ) || empty( $taxonomy ) ) return null;
    $term = get_term_by( 'name', $name, $taxonomy );
    if ( $term ) return $term->term_id;
    $result = wp_insert_term( $name, $taxonomy );
    return is_wp_error( $result ) ? null : $result['term_id'];
}

/* ============================================================
   WP-CRON: Haftalık otomatik sync
   ============================================================ */
add_filter( 'cron_schedules', function( $schedules ) {
    $schedules['weekly'] = array(
        'interval' => 7 * DAY_IN_SECONDS,
        'display'  => __( 'Weekly', 'radiotheme' ),
    );
    return $schedules;
} );

add_action( 'after_switch_theme', function() {
    if ( ! wp_next_scheduled( 'radiotheme_radiobrowser_sync' ) ) {
        wp_schedule_event( time(), 'weekly', 'radiotheme_radiobrowser_sync' );
    }
} );

add_action( 'switch_theme', function() {
    $timestamp = wp_next_scheduled( 'radiotheme_radiobrowser_sync' );
    if ( $timestamp ) wp_unschedule_event( $timestamp, 'radiotheme_radiobrowser_sync' );
} );

add_action( 'radiotheme_radiobrowser_sync', function() {
    if ( ! get_option( 'radiotheme_auto_sync', false ) ) return;
    radiotheme_radiobrowser_import( array(
        'country'   => get_option( 'radiotheme_sync_country', '' ),
        'limit'     => get_option( 'radiotheme_sync_limit', 500 ),
        'min_votes' => 5,
    ) );
} );


