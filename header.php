<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div class="site-wrapper">

    <header class="site-header" role="banner">

        <!-- SATIR 1: Logo + Site Adı + Language Switcher -->
        <div class="header-inner">

            <!-- Site Logo -->
            <div class="site-logo">
                <?php
                $_logo_cc  = function_exists( 'radiotheme_get_url_country_code' ) ? radiotheme_get_url_country_code() : '';
                $_logo_url = $_logo_cc ? home_url( '/' . $_logo_cc . '/' ) : home_url( '/' );
                ?>
                <?php if ( has_custom_logo() ) :
                    $custom_logo_id  = get_theme_mod( 'custom_logo' );
                    $custom_logo_img = wp_get_attachment_image( $custom_logo_id, 'full', false, [
                        'class'   => 'custom-logo',
                        'loading' => 'eager',
                    ] );
                    if ( $custom_logo_img ) : ?>
                    <a href="<?php echo esc_url( $_logo_url ); ?>" rel="home" aria-label="<?php bloginfo( 'name' ); ?>">
                        <?php echo $custom_logo_img; ?>
                    </a>
                    <?php else :
                        the_custom_logo();
                    endif;
                ?>
                <?php else : ?>
                    <a href="<?php echo esc_url( $_logo_url ); ?>" rel="home" aria-label="<?php bloginfo( 'name' ); ?>">
                        <svg class="logo-icon" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <circle cx="16" cy="16" r="14" fill="currentColor" opacity="0.15"/>
                            <circle cx="16" cy="16" r="5" fill="currentColor"/>
                            <path d="M16 2 A14 14 0 0 1 30 16" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round"/>
                            <path d="M16 2 A14 14 0 0 0 2 16" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" opacity="0.4"/>
                        </svg>
                        <span><?php bloginfo( 'name' ); ?></span>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Header Actions: Language Switcher -->
            <div class="header-actions">
                <?php radiotheme_render_language_switcher(); ?>
            </div>

        </div><!-- .header-inner -->

        <!-- SATIR 2: Ülke bilgisi + Genres + Favorites -->
        <?php
        // REQUEST_URI'den doğrudan parse et — en güvenilir yöntem
        $_nav_cc = '';
        $_nav_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
        $_nav_home_path = rtrim( wp_parse_url( home_url(), PHP_URL_PATH ) ?? '', '/' );
        if ( $_nav_home_path && strpos( $_nav_uri, $_nav_home_path ) === 0 ) {
            $_nav_uri = substr( $_nav_uri, strlen( $_nav_home_path ) );
        }
        $_nav_uri = '/' . ltrim( $_nav_uri, '/' );
        if ( preg_match( '#^/([a-z]{2})(/|$)#i', $_nav_uri, $_nav_m ) ) {
            $_test_cc = strtolower( $_nav_m[1] );
            if ( function_exists( 'radiotheme_iso_to_slug' ) && radiotheme_iso_to_slug( $_test_cc ) !== '' ) {
                $_nav_cc = $_test_cc;
            }
        }
        // Fallback: WP query var
        if ( ! $_nav_cc ) {
            $_nav_cc = get_query_var( 'rt_country', '' );
        }
        $_nav_slug    = $_nav_cc && function_exists( 'radiotheme_iso_to_slug' ) ? radiotheme_iso_to_slug( $_nav_cc ) : '';
        $_nav_name    = $_nav_slug ? ucwords( str_replace( '-', ' ', $_nav_slug ) ) : '';
        $_nav_flag    = $_nav_cc && function_exists( 'radiotheme_iso_to_flag_emoji' ) ? radiotheme_iso_to_flag_emoji( strtoupper( $_nav_cc ) ) : '';
        $_genres_url  = $_nav_cc ? home_url( '/' . $_nav_cc . '/genres/' ) : home_url( '/genres/' );
        $_country_url = $_nav_cc ? home_url( '/' . $_nav_cc . '/' ) : home_url( '/' );

        // İstasyon sayısını al
        $_nav_count = 0;
        if ( $_nav_slug ) {
            $_nav_count_q = new WP_Query( [
                'post_type'      => 'radio-station',
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'no_found_rows'  => false,
                'lang'           => '',
                'tax_query'      => [ [ 'taxonomy' => 'radio-country', 'field' => 'slug', 'terms' => $_nav_slug ] ],
            ] );
            $_nav_count = $_nav_count_q->found_posts;
        }
        ?>
        <div class="header-nav-bar">
            <div class="header-nav-inner">

                <!-- Ülke bilgisi -->
                <a href="<?php echo esc_url( $_country_url ); ?>" class="header-country-info" id="header-country-info">
                    <?php if ( $_nav_flag && $_nav_name ) : ?>
                        <span class="header-country-flag" id="header-country-flag" data-base-flag="<?php echo esc_attr( $_nav_flag ); ?>"><?php echo esc_html( $_nav_flag ); ?></span>
                        <span class="header-country-name" id="header-country-name"><?php esc_html_e( 'All Stations', 'radiotheme' ); ?></span>
                    <?php else : ?>
                        <span class="header-country-flag" id="header-country-flag">🌍</span>
                        <span class="header-country-name" id="header-country-name"><?php esc_html_e( 'All Stations', 'radiotheme' ); ?></span>
                    <?php endif; ?>
                </a>

                <!-- Navigasyon linkleri -->
                <nav class="header-nav-links" aria-label="<?php esc_attr_e( 'Secondary Navigation', 'radiotheme' ); ?>">

                    <!-- Genres -->
                    <a href="<?php echo esc_url( $_genres_url ); ?>" class="header-nav-link" id="header-genres-link">
                        <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M3 5h14M3 10h10M3 15h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                        <?php esc_html_e( 'Genres', 'radiotheme' ); ?>
                    </a>

                    <!-- Favorites (ileride aktif edilecek) -->
                    <a href="#" class="header-nav-link header-nav-link--disabled" aria-disabled="true">
                        <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M10 17s-7-4.5-7-9a4 4 0 0 1 7-2.65A4 4 0 0 1 17 8c0 4.5-7 9-7 9z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                        </svg>
                        <?php esc_html_e( 'Favorites', 'radiotheme' ); ?>
                    </a>

                </nav>

            </div>
        </div><!-- .header-nav-bar -->

    </header><!-- .site-header -->

    <!-- ============================================================
         FIXED AUDIO PLAYER BAR
         ============================================================ -->
    <div class="radio-player-bar" id="radio-player-bar" role="region" aria-label="<?php esc_attr_e( 'Audio Player', 'radiotheme' ); ?>">

        <!-- Station Info (sol) -->
        <div class="player-station-info">
            <div class="player-station-logo-wrap">
                <img src="" id="player-logo-img" class="player-station-logo" alt="" style="display:none" loading="lazy">
                <div id="player-logo-fallback" class="player-station-logo-fallback" aria-hidden="true"></div>
            </div>
            <div class="player-station-text">
                <div class="player-station-name" id="player-station-name"></div>
                <div class="player-now-playing" id="player-now-playing" aria-live="polite"></div>
            </div>
        </div>

        <!-- Equalizer + Play/Pause (sağ) -->
        <div class="player-controls">
            <div class="player-equalizer" id="player-equalizer" aria-hidden="true">
                <span class="eq-bar"></span>
                <span class="eq-bar"></span>
                <span class="eq-bar"></span>
                <span class="eq-bar"></span>
            </div>
            <button class="player-btn-play" id="player-btn-play" aria-label="<?php esc_attr_e( 'Play', 'radiotheme' ); ?>">
                <svg class="icon-play" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M8 5v14l11-7z" fill="currentColor"/></svg>
                <svg class="icon-pause" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="display:none"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" fill="currentColor"/></svg>
            </button>
        </div>

    </div><!-- .radio-player-bar -->
