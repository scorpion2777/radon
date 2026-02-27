<?php
/**
 * RadioTheme v2.0 — functions.php
 * Tüm CPT, taxonomy, ACF, menü, widget, script kayıtları
 * Orijinal fonksiyon isimleri korunmuştur (radiotheme_render_* → radiotheme_*)
 *
 * @package RadioTheme
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ============================================================
   CONSTANTS
   ============================================================ */
define( 'RADIOTHEME_VERSION', '2.2.0' );
define( 'RADIOTHEME_DIR',     get_template_directory() );
define( 'RADIOTHEME_URI',     get_template_directory_uri() );

/* ============================================================
   THEME SETUP
   ============================================================ */
add_action( 'after_setup_theme', function () {
    load_theme_textdomain( 'radiotheme', RADIOTHEME_DIR . '/languages' );

    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', [ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'script', 'style' ] );
    add_theme_support( 'custom-logo', [
        'height'      => 60,
        'width'       => 200,
        'flex-height' => true,
        'flex-width'  => true,
    ] );
    add_theme_support( 'yoast-seo-breadcrumbs' );
    add_theme_support( 'responsive-embeds' );

    register_nav_menus( [
        'primary' => __( 'Primary Menu',  'radiotheme' ),
        'footer'  => __( 'Footer Menu',   'radiotheme' ),
    ] );
} );

/* ============================================================
   IMAGE SIZES
   ============================================================ */
add_image_size( 'radio-logo',    50,  50,  true );
add_image_size( 'radio-logo-2x', 100, 100, true );

/* ============================================================
   PERFORMANCE — Clean wp_head
   ============================================================ */
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );
remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );

/* ============================================================
   CPT: radio-station
   ============================================================ */
add_action( 'init', function () {
    register_post_type( 'radio-station', [
        'labels' => [
            'name'               => __( 'Radio Stations',        'radiotheme' ),
            'singular_name'      => __( 'Radio Station',         'radiotheme' ),
            'menu_name'          => __( 'Radio Stations',        'radiotheme' ),
            'add_new'            => __( 'Add New',               'radiotheme' ),
            'add_new_item'       => __( 'Add New Radio Station', 'radiotheme' ),
            'edit_item'          => __( 'Edit Radio Station',    'radiotheme' ),
            'new_item'           => __( 'New Radio Station',     'radiotheme' ),
            'view_item'          => __( 'View Radio Station',    'radiotheme' ),
            'search_items'       => __( 'Search Stations',       'radiotheme' ),
            'not_found'          => __( 'No stations found',     'radiotheme' ),
            'not_found_in_trash' => __( 'No stations in Trash',  'radiotheme' ),
        ],
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => [ 'slug' => 'station', 'with_front' => false ],
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => 5,
        'menu_icon'          => 'dashicons-format-audio',
        'supports'           => [ 'title', 'editor', 'thumbnail', 'excerpt' ],
        'show_in_rest'       => true,
    ] );
}, 0 );

/* ============================================================
   TAXONOMIES
   ============================================================ */
add_action( 'init', function () {

    register_taxonomy( 'radio-genre', 'radio-station', [
        'labels'       => [
            'name'          => __( 'Genres',     'radiotheme' ),
            'singular_name' => __( 'Genre',      'radiotheme' ),
            'search_items'  => __( 'Search Genres', 'radiotheme' ),
            'all_items'     => __( 'All Genres', 'radiotheme' ),
            'edit_item'     => __( 'Edit Genre', 'radiotheme' ),
            'add_new_item'  => __( 'Add Genre',  'radiotheme' ),
        ],
        'hierarchical' => false,
        'public'       => true,
        'show_in_rest' => true,
        'rewrite'      => [ 'slug' => 'genre', 'with_front' => false ],
    ] );

    register_taxonomy( 'radio-country', 'radio-station', [
        'labels'       => [
            'name'          => __( 'Countries',   'radiotheme' ),
            'singular_name' => __( 'Country',     'radiotheme' ),
            'search_items'  => __( 'Search Countries', 'radiotheme' ),
            'all_items'     => __( 'All Countries', 'radiotheme' ),
            'edit_item'     => __( 'Edit Country','radiotheme' ),
            'add_new_item'  => __( 'Add Country', 'radiotheme' ),
        ],
        'hierarchical' => true,
        'public'       => true,
        'show_in_rest' => true,
        'rewrite'      => [ 'slug' => 'country', 'with_front' => false ],
    ] );

    register_taxonomy( 'radio-city', 'radio-station', [
        'labels'       => [
            'name'          => __( 'Cities',    'radiotheme' ),
            'singular_name' => __( 'City',      'radiotheme' ),
            'search_items'  => __( 'Search Cities', 'radiotheme' ),
            'all_items'     => __( 'All Cities','radiotheme' ),
            'edit_item'     => __( 'Edit City', 'radiotheme' ),
            'add_new_item'  => __( 'Add City',  'radiotheme' ),
        ],
        'hierarchical' => true,
        'public'       => true,
        'show_in_rest' => true,
        'rewrite'      => [ 'slug' => 'city', 'with_front' => false ],
    ] );

    register_taxonomy( 'radio-language', 'radio-station', [
        'labels'       => [
            'name'          => __( 'Languages',   'radiotheme' ),
            'singular_name' => __( 'Language',    'radiotheme' ),
            'search_items'  => __( 'Search Languages', 'radiotheme' ),
            'all_items'     => __( 'All Languages','radiotheme' ),
            'edit_item'     => __( 'Edit Language','radiotheme' ),
            'add_new_item'  => __( 'Add Language','radiotheme' ),
        ],
        'hierarchical' => false,
        'public'       => true,
        'show_in_rest' => true,
        'rewrite'      => [ 'slug' => 'language', 'with_front' => false ],
    ] );

}, 0 );

/* ============================================================
   PERMALINK FLUSH
   Tema aktifleştirildiğinde ve tema güncellendiğinde
   otomatik olarak permalink kuralları yenilenir.
   ============================================================ */
add_action( 'after_switch_theme', function () {
    flush_rewrite_rules();
} );

// Tema her güncellendiğinde versiyon değişir → permalink otomatik yenilenir
add_action( 'init', function () {
    $ver = '20.2'; /* genres sayfasi rewrite eklendi */
    if ( get_option( 'radiotheme_rewrite_ver' ) !== $ver ) {
        flush_rewrite_rules();
        update_option( 'radiotheme_rewrite_ver', $ver );
    }
}, 99 );

/* ============================================================
   ACF FIELD GROUP
   ============================================================ */
add_action( 'acf/init', function () {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-error is-dismissible"><p><strong>' . esc_html__( 'RadioTheme:', 'radiotheme' ) . '</strong> ' .
                 esc_html__( 'Advanced Custom Fields plugin is required.', 'radiotheme' ) .
                 ' <a href="' . esc_url( admin_url( 'plugin-install.php?s=advanced+custom+fields&tab=search&type=term' ) ) . '">' .
                 esc_html__( 'Install ACF', 'radiotheme' ) . '</a></p></div>';
        } );
        return;
    }

    acf_add_local_field_group( [
        'key'    => 'group_radio_station_details',
        'title'  => 'Radio Station Details',
        'fields' => [
            [
                'key'          => 'field_stream_url',
                'label'        => 'Stream URL',
                'name'         => 'stream_url',
                'type'         => 'url',
                'required'     => 1,
                'instructions' => 'Direct stream URL (MP3, AAC, HLS)',
                'placeholder'  => 'https://stream.example.com/live',
            ],
            [
                'key'          => 'field_stream_url_backup',
                'label'        => 'Backup Stream URL',
                'name'         => 'stream_url_backup',
                'type'         => 'url',
                'required'     => 0,
            ],
            [
                'key'          => 'field_station_logo_url',
                'label'        => 'Station Logo URL',
                'name'         => 'station_logo_url',
                'type'         => 'url',
                'required'     => 0,
                'instructions' => 'External logo URL (for API-imported stations)',
            ],
            [
                'key'      => 'field_station_website',
                'label'    => 'Station Website',
                'name'     => 'station_website',
                'type'     => 'url',
                'required' => 0,
            ],
            [
                'key'         => 'field_stream_bitrate',
                'label'       => 'Stream Bitrate (kbps)',
                'name'        => 'stream_bitrate',
                'type'        => 'number',
                'required'    => 0,
                'min'         => 0,
                'max'         => 320,
                'placeholder' => '128',
            ],
            [
                'key'           => 'field_stream_codec',
                'label'         => 'Stream Codec',
                'name'          => 'stream_codec',
                'type'          => 'select',
                'choices'       => [ 'MP3'=>'MP3','AAC'=>'AAC','AAC+'=>'AAC+','OGG'=>'OGG','HLS'=>'HLS','other'=>'Other' ],
                'default_value' => 'MP3',
                'required'      => 0,
            ],
            [
                'key'          => 'field_country_code',
                'label'        => 'Country Code (ISO)',
                'name'         => 'country_code',
                'type'         => 'text',
                'required'     => 0,
                'instructions' => 'ISO 3166-1 alpha-2 (TR, DE, US…)',
                'maxlength'    => 2,
                'placeholder'  => 'TR',
            ],
            [
                'key'          => 'field_radiobrowser_id',
                'label'        => 'RadioBrowser Station UUID',
                'name'         => 'radiobrowser_id',
                'type'         => 'text',
                'required'     => 0,
                'instructions' => 'Auto-filled on import from radio-browser.info',
                'readonly'     => 1,
            ],
            [
                'key'           => 'field_station_votes',
                'label'         => 'Popularity (votes)',
                'name'          => 'station_votes',
                'type'          => 'number',
                'required'      => 0,
                'default_value' => 0,
            ],
            [
                'key'           => 'field_station_active',
                'label'         => 'Station Active',
                'name'          => 'station_active',
                'type'          => 'true_false',
                'default_value' => 1,
                'ui'            => 1,
                'ui_on_text'    => 'Active',
                'ui_off_text'   => 'Inactive',
            ],
            [
                'key'      => 'field_last_checked',
                'label'    => 'Last Checked',
                'name'     => 'last_checked',
                'type'     => 'date_time_picker',
                'required' => 0,
            ],
            [
                'key'          => 'field_song_title_url',
                'label'        => 'Şarkı Adı — Stream URL',
                'name'         => 'song_title_url',
                'type'         => 'url',
                'required'     => 0,
                'instructions' => 'Sadece stream adresini girin (ör: https://stream.radyo45lik.com:4545/). Şarkı adı otomatik çekilir.',
                'placeholder'  => 'https://stream.example.com:8000/',
            ],

            // ---- İletişim Bilgileri ----
            [
                'key'          => 'field_contact_tab',
                'label'        => '— İletişim & Sosyal Medya —',
                'name'         => '',
                'type'         => 'tab',
                'placement'    => 'top',
            ],
            [
                'key'          => 'field_station_address',
                'label'        => 'Adres',
                'name'         => 'station_address',
                'type'         => 'textarea',
                'required'     => 0,
                'rows'         => 3,
                'placeholder'  => 'Radyonun fiziksel adresi',
            ],
            [
                'key'          => 'field_station_phone',
                'label'        => 'Telefon',
                'name'         => 'station_phone',
                'type'         => 'text',
                'required'     => 0,
                'placeholder'  => '+90 000 000 00 00',
            ],
            [
                'key'          => 'field_station_email',
                'label'        => 'E-posta',
                'name'         => 'station_email',
                'type'         => 'email',
                'required'     => 0,
                'placeholder'  => 'radyo@example.com',
            ],
            [
                'key'          => 'field_station_viber',
                'label'        => 'Viber',
                'name'         => 'station_viber',
                'type'         => 'text',
                'required'     => 0,
                'placeholder'  => '+90 000 000 00 00',
                'instructions' => 'Viber numarası',
            ],
            [
                'key'          => 'field_station_whatsapp',
                'label'        => 'WhatsApp',
                'name'         => 'station_whatsapp',
                'type'         => 'text',
                'required'     => 0,
                'placeholder'  => '+90 000 000 00 00',
                'instructions' => 'WhatsApp numarası',
            ],

            // ---- Sosyal Medya ----
            [
                'key'          => 'field_station_facebook',
                'label'        => 'Facebook',
                'name'         => 'station_facebook',
                'type'         => 'text',
                'required'     => 0,
                'placeholder'  => '@sayfaadi veya tam URL',
                'instructions' => 'Örn: @RadioKuiaBue veya https://facebook.com/...',
            ],
            [
                'key'          => 'field_station_twitter',
                'label'        => 'Twitter / X',
                'name'         => 'station_twitter',
                'type'         => 'text',
                'required'     => 0,
                'placeholder'  => '@kullaniciadi',
            ],
            [
                'key'          => 'field_station_instagram',
                'label'        => 'Instagram',
                'name'         => 'station_instagram',
                'type'         => 'text',
                'required'     => 0,
                'placeholder'  => '@kullaniciadi',
            ],
            [
                'key'          => 'field_station_tiktok',
                'label'        => 'TikTok',
                'name'         => 'station_tiktok',
                'type'         => 'text',
                'required'     => 0,
                'placeholder'  => '@kullaniciadi',
            ],
            [
                'key'          => 'field_station_youtube',
                'label'        => 'YouTube',
                'name'         => 'station_youtube',
                'type'         => 'text',
                'required'     => 0,
                'placeholder'  => '@kanal veya https://youtube.com/...',
                'instructions' => 'Örn: @UC0zfqsziYN7NNvTttGKzEwg veya tam URL',
            ],
        ],
        'location'  => [ [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'radio-station' ] ] ],
        'menu_order'=> 0,
        'position'  => 'normal',
    ] );
} );

/* ============================================================
   SAFE get_field HELPER
   Hem ACF hem de get_post_meta fallback
   ============================================================ */
function radiotheme_get_field( string $key, $post_id = false ) {
    $pid = ( $post_id === false ) ? get_the_ID() : $post_id;
    if ( function_exists( 'get_field' ) ) {
        return get_field( $key, $pid );
    }
    return get_post_meta( $pid, $key, true );
}

/* ============================================================
   ENQUEUE SCRIPTS & STYLES
   ============================================================ */
add_action( 'wp_enqueue_scripts', function () {

    /* ---- Google Fonts ---- */
    wp_enqueue_style(
        'radiotheme-fonts',
        'https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&display=swap',
        [],
        null
    );

    /* ---- Styles ---- */
    $theme_path = get_stylesheet_directory();

    wp_enqueue_style( 'radiotheme-style',  get_stylesheet_uri(), [ 'radiotheme-fonts' ], filemtime( $theme_path . '/style.css' ) );
    wp_enqueue_style( 'radiotheme-main',   RADIOTHEME_URI . '/assets/css/main.css',          [ 'radiotheme-style' ], filemtime( $theme_path . '/assets/css/main.css' ) );
    wp_enqueue_style( 'radiotheme-cards',  RADIOTHEME_URI . '/assets/css/radio-card.css',    [ 'radiotheme-main' ],  filemtime( $theme_path . '/assets/css/radio-card.css' ) );
    wp_enqueue_style( 'radiotheme-player', RADIOTHEME_URI . '/assets/css/player.css',        [ 'radiotheme-main' ],  filemtime( $theme_path . '/assets/css/player.css' ) );
    wp_enqueue_style( 'radiotheme-sidebar',RADIOTHEME_URI . '/assets/css/sidebar.css',       [ 'radiotheme-main' ],  filemtime( $theme_path . '/assets/css/sidebar.css' ) );
    wp_enqueue_style( 'radiotheme-single', RADIOTHEME_URI . '/assets/css/single-station.css',[ 'radiotheme-main' ],  filemtime( $theme_path . '/assets/css/single-station.css' ) );

    /* ---- Scripts (footer) ---- */
    wp_enqueue_script( 'hls-js', 'https://cdn.jsdelivr.net/npm/hls.js@latest/dist/hls.min.js', [], null, true );
    wp_enqueue_script( 'radiotheme-player',  RADIOTHEME_URI . '/assets/js/player.js',           [ 'hls-js' ],            filemtime( $theme_path . '/assets/js/player.js' ), true );
    wp_enqueue_script( 'radiotheme-filter',  RADIOTHEME_URI . '/assets/js/ajax-filter.js',      [ 'radiotheme-player' ], filemtime( $theme_path . '/assets/js/ajax-filter.js' ), true );
    wp_enqueue_script( 'radiotheme-ajaxnav', RADIOTHEME_URI . '/assets/js/ajax-navigation.js',  [ 'radiotheme-player' ], filemtime( $theme_path . '/assets/js/ajax-navigation.js' ), true );
    wp_enqueue_script( 'radiotheme-lang',    RADIOTHEME_URI . '/assets/js/language-switcher.js',[ 'radiotheme-player' ], filemtime( $theme_path . '/assets/js/language-switcher.js' ), true );

    /* ---- Localise ---- */
    wp_localize_script( 'radiotheme-player', 'radioThemeData', [
        'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
        'nonce'         => wp_create_nonce( 'radiotheme_ajax_nonce' ),
        'postsPerPage'  => 30,
        'homeUrl'       => home_url(),
        'loadingText'   => __( 'Loading…',         'radiotheme' ),
        'noMoreText'    => __( 'No more stations', 'radiotheme' ),
        'errorText'     => __( 'Error loading stations. Please try again.', 'radiotheme' ),
        'currentLang'   => defined( 'ICL_LANGUAGE_CODE' ) ? ICL_LANGUAGE_CODE : get_locale(),
        'siteName'      => get_bloginfo( 'name' ),
        'proxyUrl'      => get_stylesheet_directory_uri() . '/radio-proxy.php?url=',
        /* HTTP subdomain proxy — stream.siteniz.com üzerinden tampon sıfır aktarım */
        /* 'siteniz.com' kısmını kendi alan adınızla değiştirin */
        'nativeProxyUrl' => 'http://stream.' . preg_replace('#^https?://(www\.)?#i', '', home_url('/')) . '?url=',
        'songtitleUrl'  => get_template_directory_uri() . '/songtitle_api.php',
        'itunesArtworkUrl' => get_template_directory_uri() . '/itunes_artwork.php',
        'flagMap'       => ( function() {
            $terms = get_terms( [ 'taxonomy' => 'radio-country', 'hide_empty' => true, 'number' => 0 ] );
            $map   = [];
            if ( ! is_wp_error( $terms ) ) {
                foreach ( $terms as $t ) {
                    $iso = function_exists( 'radiotheme_slug_to_iso' ) ? radiotheme_slug_to_iso( $t->slug ) : '';
                    if ( $iso ) {
                        $map[ strtolower( $iso ) ] = function_exists( 'radiotheme_iso_to_flag_emoji' )
                            ? radiotheme_iso_to_flag_emoji( strtoupper( $iso ) )
                            : '';
                    }
                }
            }
            return $map;
        } )(),
    ] );
} );

/* ============================================================
   WIDGET AREAS
   ============================================================ */
add_action( 'widgets_init', function () {
    $defaults = [
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ];

    register_sidebar( $defaults + [
        'name'        => __( 'Sidebar Left — Top',    'radiotheme' ),
        'id'          => 'sidebar-left-top',
        'description' => __( 'Left sidebar top (300×250)', 'radiotheme' ),
    ] );

    register_sidebar( $defaults + [
        'name'        => __( 'Sidebar Left — Bottom', 'radiotheme' ),
        'id'          => 'sidebar-left-bottom',
        'description' => __( 'Left sidebar bottom (300×600)', 'radiotheme' ),
    ] );

    register_sidebar( $defaults + [
        'name'        => __( 'Sidebar Right — Top',   'radiotheme' ),
        'id'          => 'sidebar-right-top',
        'description' => __( 'Right sidebar top (300×250)', 'radiotheme' ),
    ] );

    register_sidebar( $defaults + [
        'name'        => __( 'Sidebar Right — Bottom', 'radiotheme' ),
        'id'          => 'sidebar-right-bottom',
        'description' => __( 'Right sidebar bottom (160×600)', 'radiotheme' ),
    ] );
} );

/* ============================================================
   TEMPLATE ROUTING
   ============================================================ */

/* front-page.php — homepage (Polylang uyumlu) */
add_filter( 'template_include', function ( $template ) {
    // Genres sayfası zaten country-router tarafından handle ediliyor — dokunma
    if ( basename( $template ) === 'genres-page.php' ) return $template;

    $is_home = is_home() || is_front_page();
    if ( ! $is_home && function_exists( 'pll_is_home' ) ) {
        $is_home = pll_is_home();
    }
    if ( $is_home ) {
        $fp = RADIOTHEME_DIR . '/front-page.php';
        if ( file_exists( $fp ) ) return $fp;
    }
    return $template;
}, 99 );

/* single-radio-station.php — istasyon detay */
add_filter( 'single_template', function ( $template ) {
    if ( ! is_singular( 'radio-station' ) ) return $template;

    $candidates = [
        RADIOTHEME_DIR . '/single-radio-station.php',
        RADIOTHEME_DIR . '/single-radio.php',
    ];
    foreach ( $candidates as $f ) {
        if ( file_exists( $f ) ) return $f;
    }
    return $template;
} );

/* ============================================================
   BODY CLASS
   ============================================================ */
add_filter( 'body_class', function ( $classes ) {
    $classes[] = 'radio-platform';
    return $classes;
} );

/* ============================================================
   EXCERPT
   ============================================================ */
add_filter( 'excerpt_length', fn() => 25 );
add_filter( 'excerpt_more',   fn() => '…' );

/* ============================================================
   POLYLANG — CPT & string registration
   ============================================================ */
add_action( 'init', function () {
    if ( function_exists( 'pll_register_string' ) ) {
        $strings = [
            'all_stations'   => 'All Stations',
            'popular'        => 'Popular Stations',
            'genres'         => 'Genres',
            'countries'      => 'Countries',
            'listen_now'     => 'Listen Now',
            'playing_now'    => 'Playing Now',
            'search_ph'      => 'Search stations…',
        ];
        foreach ( $strings as $key => $val ) {
            pll_register_string( 'radiotheme_' . $key, $val, 'radiotheme' );
        }
    }

    if ( function_exists( 'pll_register_post_type' ) ) {
        pll_register_post_type( 'radio-station' );
    }
}, 100 );


/* ============================================================
   ULKE BAZLI SIDEBAR WIDGET FONKSIYONLARI
   ============================================================ */

/**
 * Popüler istasyonlar — ülkeye göre filtreli.
 */
function radiotheme_popular_stations_by_country( int $count = 8, string $country_slug = '' ): void {
    $args = [
        'post_type'      => 'radio-station',
        'posts_per_page' => $count,
        'post_status'    => 'publish',
        'lang'           => '',
        'meta_key'       => 'station_votes',
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
        'meta_query'     => [ [
            'key'     => 'station_active',
            'value'   => '1',
            'compare' => '=',
        ] ],
    ];
    if ( $country_slug ) {
        $args['tax_query'] = [ [
            'taxonomy' => 'radio-country',
            'field'    => 'slug',
            'terms'    => $country_slug,
        ] ];
    }
    $stations = new WP_Query( $args );
    if ( ! $stations->have_posts() ) {
        echo '<p class="no-stations-msg">' . esc_html__( 'No stations found.', 'radiotheme' ) . '</p>';
        wp_reset_postdata();
        return;
    }
    $rank = 1;
    while ( $stations->have_posts() ) :
        $stations->the_post();
        $station_id  = get_the_ID();
        $stream_url  = radiotheme_get_field( 'stream_url', $station_id );
        $logo_url    = radiotheme_get_field( 'station_logo_url', $station_id );
        $cc          = strtolower( radiotheme_get_field( 'country_code', $station_id ) ?: '' );
        $station_url = function_exists( 'radiotheme_station_url' ) ? radiotheme_station_url( $station_id, $cc ) : get_permalink();
        $thumb       = get_the_post_thumbnail_url( $station_id, 'radio-logo' );
        $logo        = $thumb ?: $logo_url;
        $initial     = strtoupper( mb_substr( get_the_title(), 0, 1 ) );
        ?>
        <a href="<?php echo esc_url( $station_url ); ?>" class="popular-station-item"
           data-station-id="<?php echo esc_attr( $station_id ); ?>"
           data-stream-url="<?php echo esc_url( $stream_url ?: '' ); ?>"
           data-song-url="<?php echo esc_attr( radiotheme_get_field( 'song_title_url', $station_id ) ?: $stream_url ); ?>"
           data-station-name="<?php echo esc_attr( get_the_title() ); ?>"
           data-logo="<?php echo esc_url( $logo ?: '' ); ?>"
           data-no-ajax title="<?php echo esc_attr( get_the_title() ); ?>">
            <span class="popular-station-rank"><?php echo esc_html( $rank ); ?></span>
            <?php if ( $logo ) : ?>
                <img src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>"
                     class="popular-station-logo" width="36" height="36" loading="lazy">
            <?php else : ?>
                <div class="station-logo-fallback" style="width:36px;height:36px;font-size:.875rem" aria-hidden="true"><?php echo esc_html( $initial ); ?></div>
            <?php endif; ?>
            <div class="popular-station-info">
                <div class="popular-station-name"><?php the_title(); ?></div>
            </div>
        </a>
        <?php
        $rank++;
    endwhile;
    wp_reset_postdata();
}

/**
 * Belirli bir ülkedeki istasyonları ID olarak döndürür (cache'li).
 */
function radiotheme_get_station_ids_by_country( string $country_slug ): array {
    if ( ! $country_slug ) return [];
    $cache_key = 'rt_station_ids_' . $country_slug;
    $cached    = wp_cache_get( $cache_key, 'radiotheme' );
    if ( false !== $cached ) return $cached;

    $ids = get_posts( [
        'post_type'      => 'radio-station',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'post_status'    => 'publish',
        'lang'           => '',
        'tax_query'      => [ [
            'taxonomy' => 'radio-country',
            'field'    => 'slug',
            'terms'    => $country_slug,
        ] ],
    ] );
    $ids = $ids ?: [];
    wp_cache_set( $cache_key, $ids, 'radiotheme', 300 );
    return $ids;
}

/**
 * Bir genre'nin belirli bir ülkedeki gerçek istasyon sayısını döndürür.
 */
function radiotheme_genre_count_for_country( int $genre_term_id, string $country_slug ): int {
    if ( ! $country_slug ) {
        $term = get_term( $genre_term_id, 'radio-genre' );
        return ( $term && ! is_wp_error( $term ) ) ? (int) $term->count : 0;
    }
    $cache_key = 'rt_genre_count_' . $genre_term_id . '_' . $country_slug;
    $cached    = wp_cache_get( $cache_key, 'radiotheme' );
    if ( false !== $cached ) return (int) $cached;

    $station_ids = radiotheme_get_station_ids_by_country( $country_slug );
    if ( empty( $station_ids ) ) return 0;

    // Bu genre'ye ait ve bu ülkedeki istasyonların kesişimi
    $count = count( get_posts( [
        'post_type'      => 'radio-station',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'post_status'    => 'publish',
        'lang'           => '',
        'post__in'       => $station_ids,
        'tax_query'      => [ [
            'taxonomy' => 'radio-genre',
            'field'    => 'term_id',
            'terms'    => $genre_term_id,
        ] ],
    ] ) );

    wp_cache_set( $cache_key, $count, 'radiotheme', 300 );
    return $count;
}

/**
 * Genre cloud — ülkeye göre filtreli, ülke kodlu URL.
 * count değeri o ülkedeki gerçek istasyon sayısını yansıtır.
 */
function radiotheme_genre_cloud_by_country( int $limit = 20, string $country_slug = '', string $cc = '' ): void {
    $args = [
        'taxonomy'   => 'radio-genre',
        'orderby'    => 'count',
        'order'      => 'DESC',
        'hide_empty' => true,
        'number'     => $limit,
    ];
    if ( $country_slug ) {
        $station_ids = radiotheme_get_station_ids_by_country( $country_slug );
        if ( empty( $station_ids ) ) return;
        $args['object_ids'] = $station_ids;
    }
    $genres = get_terms( $args );
    if ( is_wp_error( $genres ) || empty( $genres ) ) return;

    echo '<div class="genre-cloud" role="list">';
    foreach ( $genres as $genre ) {
        $url = $cc && function_exists( 'radiotheme_genre_url' )
            ? radiotheme_genre_url( $genre->slug, $cc )
            : get_term_link( $genre );
        if ( is_wp_error( $url ) ) continue;

        // Ülkeye göre gerçek sayıyı hesapla
        $real_count = radiotheme_genre_count_for_country( (int) $genre->term_id, $country_slug );

        printf(
            '<a href="%s" class="genre-cloud-tag" data-genre="%s" data-count="%d" role="listitem">%s</a>',
            esc_url( $url ),
            esc_attr( $genre->slug ),
            esc_attr( $real_count ),
            esc_html( $genre->name )
        );
    }
    echo '</div>';
}

/**
 * Ülke listesi — ülke kodlu URL ile.
 */
function radiotheme_country_list_linked( int $count = 10 ): void {
    $countries = get_terms( [
        'taxonomy'   => 'radio-country',
        'orderby'    => 'count',
        'order'      => 'DESC',
        'hide_empty' => true,
        'number'     => $count,
        'parent'     => 0,
    ] );
    if ( is_wp_error( $countries ) || empty( $countries ) ) return;

    echo '<nav class="country-list" aria-label="' . esc_attr__( 'Radio stations by country', 'radiotheme' ) . '">';
    foreach ( $countries as $c ) {
        $iso  = radiotheme_country_name_to_code( $c->name );
        $iso_lower = strtolower( $iso );
        $url  = $iso_lower && function_exists( 'radiotheme_country_url' )
                ? radiotheme_country_url( $iso_lower )
                : get_term_link( $c );
        if ( is_wp_error( $url ) ) continue;
        $flag = $iso ? radiotheme_iso_to_flag_emoji( $iso ) : '🌍';
        printf(
            '<a href="%s" class="country-list-item" data-no-ajax="1">'
            . '<div class="country-list-item-left"><span class="country-flag-emoji">%s</span><span>%s</span></div>'
            . '<span class="country-station-count">%d</span>'
            . '</a>',
            esc_url( $url ),
            esc_html( $flag ),
            esc_html( $c->name ),
            esc_html( $c->count )
        );
    }
    echo '</nav>';
}

/* ============================================================
   SIDEBAR WIDGET HELPERS
   ============================================================ */

/**
 * Popular stations widget
 */
function radiotheme_popular_stations( int $count = 8 ): void {
    // Backward compat alias
    radiotheme_render_popular_stations( $count );
}

function radiotheme_render_popular_stations( int $count = 8 ): void {
    $stations = new WP_Query( [
        'post_type'      => 'radio-station',
        'posts_per_page' => $count,
        'post_status'    => 'publish',
        'lang'           => '',
        'meta_key'       => 'station_votes',
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
        'meta_query'     => [ [
            'key'     => 'station_active',
            'value'   => '1',
            'compare' => '=',
        ] ],
    ] );

    if ( ! $stations->have_posts() ) {
        echo '<p class="no-stations-msg">' . esc_html__( 'No stations found.', 'radiotheme' ) . '</p>';
        wp_reset_postdata();
        return;
    }

    $rank = 1;
    while ( $stations->have_posts() ) :
        $stations->the_post();
        $station_id  = get_the_ID();
        $stream_url  = radiotheme_get_field( 'stream_url',       $station_id );
        $logo_url    = radiotheme_get_field( 'station_logo_url', $station_id );
        $station_url = get_permalink();
        $countries   = get_the_terms( $station_id, 'radio-country' );
        $country     = ( $countries && ! is_wp_error( $countries ) ) ? $countries[0]->name : '';
        $thumb       = get_the_post_thumbnail_url( $station_id, 'radio-logo' );
        $logo        = $thumb ?: $logo_url;
        $initial     = strtoupper( mb_substr( get_the_title(), 0, 1 ) );
        ?>
        <a
            href="<?php echo esc_url( $station_url ); ?>"
            class="popular-station-item"
            data-station-id="<?php echo esc_attr( $station_id ); ?>"
            data-stream-url="<?php echo esc_url( $stream_url ?: '' ); ?>"
            data-song-url="<?php echo esc_attr( radiotheme_get_field( 'song_title_url', $station_id ) ?: $stream_url ); ?>"
            data-no-ajax
            data-station-name="<?php echo esc_attr( get_the_title() ); ?>"
            data-logo="<?php echo esc_url( $logo ?: '' ); ?>"
            title="<?php echo esc_attr( get_the_title() ); ?>"
        >
            <span class="popular-station-rank"><?php echo esc_html( $rank ); ?></span>

            <?php if ( $logo ) : ?>
                <img
                    src="<?php echo esc_url( $logo ); ?>"
                    alt="<?php echo esc_attr( get_the_title() ); ?>"
                    class="popular-station-logo"
                    width="36"
                    height="36"
                    loading="lazy"
                >
            <?php else : ?>
                <div class="station-logo-fallback" style="width:36px;height:36px;font-size:.875rem" aria-hidden="true"><?php echo esc_html( $initial ); ?></div>
            <?php endif; ?>

            <div class="popular-station-info">
                <div class="popular-station-name"><?php the_title(); ?></div>
                <?php if ( $country ) : ?>
                    <div class="popular-station-country"><?php echo esc_html( $country ); ?></div>
                <?php endif; ?>
            </div>
        </a>
        <?php
        $rank++;
    endwhile;
    wp_reset_postdata();
}

/**
 * Genre cloud widget
 */
function radiotheme_genre_cloud( int $limit = 20 ): void {
    radiotheme_render_genre_cloud( $limit );
}

function radiotheme_render_genre_cloud( int $limit = 20 ): void {
    $genres = get_terms( [
        'taxonomy'   => 'radio-genre',
        'orderby'    => 'count',
        'order'      => 'DESC',
        'hide_empty' => true,
        'number'     => $limit,
    ] );

    if ( is_wp_error( $genres ) || empty( $genres ) ) return;

    echo '<div class="genre-cloud" role="list">';
    foreach ( $genres as $genre ) {
        $url = get_term_link( $genre );
        if ( is_wp_error( $url ) ) continue;
        printf(
            '<a href="%s" class="genre-cloud-tag" data-count="%d" role="listitem" title="%s">%s</a>',
            esc_url( $url ),
            esc_attr( $genre->count ),
            esc_attr( sprintf( __( '%1$s: %2$d stations', 'radiotheme' ), $genre->name, $genre->count ) ),
            esc_html( $genre->name )
        );
    }
    echo '</div>';
}

/**
 * Country list widget
 */
function radiotheme_country_list( int $count = 10 ): void {
    radiotheme_render_country_list( $count );
}

function radiotheme_render_country_list( int $count = 10 ): void {
    // (Removed hardcoded flag map – now uses radiotheme_iso_to_flag_emoji() instead)

    $countries = get_terms( [
        'taxonomy'   => 'radio-country',
        'orderby'    => 'count',
        'order'      => 'DESC',
        'hide_empty' => true,
        'number'     => $count,
        'parent'     => 0,
    ] );

    if ( is_wp_error( $countries ) || empty( $countries ) ) return;

    echo '<nav class="country-list" aria-label="' . esc_attr__( 'Radio stations by country', 'radiotheme' ) . '">';
    foreach ( $countries as $c ) {
        $url = get_term_link( $c );
        if ( is_wp_error( $url ) ) continue;

        // Derive flag emoji dynamically from the English country name → ISO code → emoji.
        $iso_code = radiotheme_country_name_to_code( $c->name );
        $flag     = $iso_code ? radiotheme_iso_to_flag_emoji( $iso_code ) : '🌍';

        printf(
            '<a href="%s" class="country-list-item" data-no-ajax="1">'
            . '<div class="country-list-item-left"><span class="country-flag-emoji">%s</span><span>%s</span></div>'
            . '<span class="country-station-count">%d</span>'
            . '</a>',
            esc_url( $url ),
            esc_html( $flag ),
            esc_html( $c->name ),
            esc_html( $c->count )
        );
    }
    echo '</nav>';
}

/**
 * Convert an ISO 3166-1 alpha-2 code to a flag emoji.
 * Works by converting each letter to its Regional Indicator Symbol.
 * Does NOT require a hardcoded list — covers all 200+ countries automatically.
 *
 * @param string $code Two-letter ISO code, e.g. "TR"
 * @return string Flag emoji, e.g. "🇹🇷"
 */
function radiotheme_iso_to_flag_emoji( string $code ): string {
    $code = strtoupper( trim( $code ) );
    if ( strlen( $code ) !== 2 || ! ctype_alpha( $code ) ) return '🌍';
    $offset = 0x1F1E6 - ord( 'A' );
    return mb_chr( ord( $code[0] ) + $offset, 'UTF-8' ) . mb_chr( ord( $code[1] ) + $offset, 'UTF-8' );
}

/**
 * Convert an English country name to its ISO 3166-1 alpha-2 code.
 *
 * @param string $name English country name
 * @return string ISO code or empty string
 */
function radiotheme_country_name_to_code( string $name ): string {
    $map = radiotheme_get_country_map();
    return $map[ strtolower( trim( $name ) ) ] ?? '';
}

/**
 * Convert an ISO code to an English country name.
 * Used during import to normalise localised names (e.g. "Türkiye" → "Turkey").
 *
 * @param string $code ISO 3166-1 alpha-2 code
 * @return string English country name or empty string
 */
function radiotheme_country_code_to_english( string $code ): string {
    $flipped = array_flip( radiotheme_get_country_map() );
    $key     = strtoupper( trim( $code ) );
    return isset( $flipped[ $key ] ) ? ucwords( $flipped[ $key ] ) : '';
}

/**
 * Central country name → ISO code lookup table (lowercase name → uppercase code).
 * Covers all countries commonly found in RadioBrowser data.
 *
 * @return array<string, string>
 */
function radiotheme_get_country_map(): array {
    return [
        'afghanistan'                          => 'AF',
        'albania'                              => 'AL',
        'algeria'                              => 'DZ',
        'andorra'                              => 'AD',
        'angola'                               => 'AO',
        'argentina'                            => 'AR',
        'armenia'                              => 'AM',
        'australia'                            => 'AU',
        'austria'                              => 'AT',
        'azerbaijan'                           => 'AZ',
        'bahrain'                              => 'BH',
        'bangladesh'                           => 'BD',
        'belarus'                              => 'BY',
        'belgium'                              => 'BE',
        'bolivia'                              => 'BO',
        'bosnia and herzegovina'               => 'BA',
        'brazil'                               => 'BR',
        'bulgaria'                             => 'BG',
        'cambodia'                             => 'KH',
        'cameroon'                             => 'CM',
        'canada'                               => 'CA',
        'chile'                                => 'CL',
        'china'                                => 'CN',
        'colombia'                             => 'CO',
        'costa rica'                           => 'CR',
        'croatia'                              => 'HR',
        'cuba'                                 => 'CU',
        'cyprus'                               => 'CY',
        'czech republic'                       => 'CZ',
        'czechia'                              => 'CZ',
        'denmark'                              => 'DK',
        'dominican republic'                   => 'DO',
        'ecuador'                              => 'EC',
        'egypt'                                => 'EG',
        'el salvador'                          => 'SV',
        'estonia'                              => 'EE',
        'ethiopia'                             => 'ET',
        'finland'                              => 'FI',
        'france'                               => 'FR',
        'georgia'                              => 'GE',
        'germany'                              => 'DE',
        'ghana'                                => 'GH',
        'greece'                               => 'GR',
        'guatemala'                            => 'GT',
        'honduras'                             => 'HN',
        'hong kong'                            => 'HK',
        'hungary'                              => 'HU',
        'iceland'                              => 'IS',
        'india'                                => 'IN',
        'indonesia'                            => 'ID',
        'iran'                                 => 'IR',
        'iraq'                                 => 'IQ',
        'ireland'                              => 'IE',
        'israel'                               => 'IL',
        'italy'                                => 'IT',
        'jamaica'                              => 'JM',
        'japan'                                => 'JP',
        'jordan'                               => 'JO',
        'kazakhstan'                           => 'KZ',
        'kenya'                                => 'KE',
        'kosovo'                               => 'XK',
        'kuwait'                               => 'KW',
        'kyrgyzstan'                           => 'KG',
        'latvia'                               => 'LV',
        'lebanon'                              => 'LB',
        'libya'                                => 'LY',
        'lithuania'                            => 'LT',
        'luxembourg'                           => 'LU',
        'malaysia'                             => 'MY',
        'malta'                                => 'MT',
        'mexico'                               => 'MX',
        'moldova'                              => 'MD',
        'mongolia'                             => 'MN',
        'montenegro'                           => 'ME',
        'morocco'                              => 'MA',
        'myanmar'                              => 'MM',
        'nepal'                                => 'NP',
        'netherlands'                          => 'NL',
        'new zealand'                          => 'NZ',
        'nicaragua'                            => 'NI',
        'nigeria'                              => 'NG',
        'north korea'                          => 'KP',
        'north macedonia'                      => 'MK',
        'norway'                               => 'NO',
        'oman'                                 => 'OM',
        'pakistan'                             => 'PK',
        'palestine'                            => 'PS',
        'panama'                               => 'PA',
        'paraguay'                             => 'PY',
        'peru'                                 => 'PE',
        'philippines'                          => 'PH',
        'poland'                               => 'PL',
        'portugal'                             => 'PT',
        'qatar'                                => 'QA',
        'romania'                              => 'RO',
        'russia'                               => 'RU',
        'saudi arabia'                         => 'SA',
        'senegal'                              => 'SN',
        'serbia'                               => 'RS',
        'singapore'                            => 'SG',
        'slovakia'                             => 'SK',
        'slovenia'                             => 'SI',
        'south africa'                         => 'ZA',
        'south korea'                          => 'KR',
        'spain'                                => 'ES',
        'sri lanka'                            => 'LK',
        'sudan'                                => 'SD',
        'sweden'                               => 'SE',
        'switzerland'                          => 'CH',
        'syria'                                => 'SY',
        'taiwan'                               => 'TW',
        'tajikistan'                           => 'TJ',
        'tanzania'                             => 'TZ',
        'thailand'                             => 'TH',
        'tunisia'                              => 'TN',
        'turkey'                               => 'TR',
        'turkmenistan'                         => 'TM',
        'uganda'                               => 'UG',
        'ukraine'                              => 'UA',
        'united arab emirates'                 => 'AE',
        'united kingdom'                       => 'GB',
        'united states'                        => 'US',
        'united states of america'             => 'US',
        'uruguay'                              => 'UY',
        'uzbekistan'                           => 'UZ',
        'venezuela'                            => 'VE',
        'vietnam'                              => 'VN',
        'yemen'                                => 'YE',
        'zimbabwe'                             => 'ZW',
    ];
}

/* ============================================================
   INCLUDE MODULES
   ============================================================ */
$radiotheme_modules = [
    '/inc/country-router.php',   /* Ulke bazli URL routing - once yuklenmeli */
    '/inc/ajax-filter.php',
    '/inc/radiobrowser-api.php',
    '/inc/schema.php',
    '/inc/seo-helpers.php',
    '/inc/geoip.php',
];

foreach ( $radiotheme_modules as $m ) {
    $path = RADIOTHEME_DIR . $m;
    if ( file_exists( $path ) ) {
        require_once $path;
    }
}


/* ============================================================
   NAVIGATION HELPERS
   Polylang dil değiştiricisi + fallback navigasyon
   ============================================================ */

/**
 * Fallback nav
 */
function radiotheme_fallback_nav(): void {
    $cc       = function_exists( 'radiotheme_get_url_country_code' ) ? radiotheme_get_url_country_code() : '';
    $nav_url  = $cc ? home_url( '/' . $cc . '/' ) : home_url( '/' );
    echo '<ul class="primary-nav-menu">';
    echo '<li><a href="' . esc_url( $nav_url ) . '">' . esc_html__( 'All Stations', 'radiotheme' ) . '</a></li>';
    echo '</ul>';
}

/**
 * Language switcher — Polylang or WPML
 */
function radiotheme_render_language_switcher(): void {
    $flag_map = [
        'en' => '🇬🇧', 'tr' => '🇹🇷', 'de' => '🇩🇪', 'fr' => '🇫🇷', 'es' => '🇪🇸',
        'ar' => '🇸🇦', 'pt' => '🇧🇷', 'ru' => '🇷🇺', 'pl' => '🇵🇱',
    ];

    // Polylang
    if ( function_exists( 'pll_the_languages' ) ) {
        $languages = pll_the_languages( array( 'raw' => 1 ) );
        if ( empty( $languages ) ) return;

        $current_lang = '';
        $current_flag = '🌍';
        foreach ( $languages as $lang ) {
            if ( $lang['current_lang'] ) {
                $current_lang = strtoupper( $lang['slug'] );
                $current_flag = $flag_map[ $lang['slug'] ] ?? '🌍';
                break;
            }
        }

        echo '<div class="language-switcher" id="language-switcher">';
        echo '<button class="language-switcher-toggle" id="language-switcher-toggle"'
           . ' aria-haspopup="listbox" aria-expanded="false" aria-controls="language-dropdown">';
        echo '<span class="flag-icon" aria-hidden="true">' . esc_html( $current_flag ) . '</span>';
        echo '<span>' . esc_html( $current_lang ) . '</span>';
        echo '<svg class="chevron-icon" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">'
           . '<path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
           . '</svg>';
        echo '</button>';
        echo '<div class="language-switcher-dropdown" id="language-dropdown" role="listbox">';

        foreach ( $languages as $lang ) {
            $flag = $flag_map[ $lang['slug'] ] ?? '🌍';
            $is_active = $lang['current_lang'] ? ' is-active' : '';
            echo '<a href="' . esc_url( $lang['url'] ) . '"'
               . ' class="language-option' . esc_attr( $is_active ) . '"'
               . ' hreflang="' . esc_attr( $lang['slug'] ) . '"'
               . ' role="option"'
               . ' aria-selected="' . ( $lang['current_lang'] ? 'true' : 'false' ) . '">';
            echo '<span class="flag-icon" aria-hidden="true">' . esc_html( $flag ) . '</span>';
            echo esc_html( $lang['name'] );
            echo '</a>';
        }

        echo '</div></div>';
        return;
    }

    // Fallback: no multilingual plugin
    echo '<div class="language-switcher" style="display:none"></div>';
}


/* ============================================================
   PROXY HOST YÖNETİMİ
   
   HTTP stream URL'lerin host'larını otomatik olarak
   radio-proxy.php whitelist'ine (wp_options) kaydeder.
   
   - Import sırasında otomatik çalışır
   - Admin'den manuel radyo eklenince de çalışır
   ============================================================ */

/**
 * Bir stream URL'nin host'unu proxy whitelist'ine ekle.
 * Sadece http:// URL'ler için çalışır.
 */
function radiotheme_register_proxy_host( string $stream_url ): void {
    if ( strpos( $stream_url, 'http://' ) !== 0 ) return; // https veya boş ise atla

    $host = strtolower( parse_url( $stream_url, PHP_URL_HOST ) ?? '' );
    if ( ! $host ) return;

    $hosts = get_option( 'radiotheme_proxy_hosts', [] );
    if ( ! is_array( $hosts ) ) $hosts = [];

    if ( ! in_array( $host, $hosts, true ) ) {
        $hosts[] = $host;
        update_option( 'radiotheme_proxy_hosts', array_unique( $hosts ), false );
    }
}

/**
 * Admin'den radio-station kaydedilince stream URL host'unu kaydet.
 * ACF alanı kaydedildiğinde tetiklenir.
 */
add_action( 'acf/save_post', function ( $post_id ) {
    if ( get_post_type( $post_id ) !== 'radio-station' ) return;

    $stream_url        = get_field( 'stream_url',        $post_id ) ?: '';
    $stream_url_backup = get_field( 'stream_url_backup', $post_id ) ?: '';

    radiotheme_register_proxy_host( $stream_url );
    radiotheme_register_proxy_host( $stream_url_backup );
}, 20 );

/**
 * ACF kullanılmıyorsa standart post meta hook'u (yedek).
 */
add_action( 'updated_post_meta', function ( $meta_id, $post_id, $meta_key, $meta_value ) {
    if ( ! in_array( $meta_key, [ 'stream_url', 'stream_url_backup' ], true ) ) return;
    if ( get_post_type( $post_id ) !== 'radio-station' ) return;
    radiotheme_register_proxy_host( (string) $meta_value );
}, 10, 4 );

/* ============================================================
   SOSYAL MEDYA URL YARDIMCISI
   @handle veya tam URL'i kabul eder, tam URL döner.
   ============================================================ */
function radiotheme_social_url( string $handle, string $base ): string {
    $handle = trim( $handle );
    if ( empty( $handle ) ) return '';
    if ( strpos( $handle, 'http' ) === 0 ) return $handle;
    $clean = ltrim( $handle, '@' );
    return $base . $clean;
}

/* ============================================================
   NAV MENÜ — Ülke kodunu koru
   Ana menüdeki home_url('/') linkleri, aktif ülke kodu varsa
   /cc/ şeklinde yönlendirilir. Böylece logo ve nav linkleri
   kullanıcının seçtiği ülkeyi kaybetmez.
   ============================================================ */
add_filter( 'nav_menu_link_attributes', function ( $atts, $item, $args, $depth ) {
    if ( empty( $atts['href'] ) ) return $atts;

    $cc = function_exists( 'radiotheme_get_url_country_code' ) ? radiotheme_get_url_country_code() : '';
    if ( ! $cc ) return $atts;

    $home = trailingslashit( home_url( '/' ) );

    // Sadece ana sayfaya giden linkleri dönüştür
    if ( trailingslashit( $atts['href'] ) === $home ) {
        $atts['href'] = home_url( '/' . $cc . '/' );
    }

    return $atts;
}, 10, 4 );
