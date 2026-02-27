<?php
/**
 * RadioTheme — single-radio-station.php  (v2 — yeni düzen)
 *
 * Düzen:
 *  [Logo]  [Radyo Adı]  [Play Btn]
 *          [♪ Şarkı Adı — kayan yazı]
 *          [Genre · Country · City · kbps]
 *          [Açıklama]
 *          [Sol: Paylaşım Butonları] [Sağ: Özel Alanlar]
 *          [Adres]
 */

get_header();

while ( have_posts() ) :
    the_post();

    $main_station_id = get_the_ID();
    $stream_url   = radiotheme_get_field( 'stream_url',        $main_station_id );
    $backup_url   = radiotheme_get_field( 'stream_url_backup', $main_station_id );
    $logo_url     = radiotheme_get_field( 'station_logo_url',  $main_station_id );
    $website      = radiotheme_get_field( 'station_website',   $main_station_id );
    $bitrate      = radiotheme_get_field( 'stream_bitrate',    $main_station_id );
    $codec        = radiotheme_get_field( 'stream_codec',      $main_station_id );
    $country_code = radiotheme_get_field( 'country_code',      $main_station_id );

    /* URL'den ülke kodunu oku: /s/tr/station/arabesk-fm/ → 'tr'
       Bu detay sayfasındaki "benzer kanallar" vb. için kullanılır. */
    $url_cc       = function_exists( 'radiotheme_get_url_country_code' )
                    ? radiotheme_get_url_country_code()
                    : strtolower( $country_code ?: '' );
    $url_cc       = $url_cc ?: strtolower( $country_code ?: '' );

    $station_address   = radiotheme_get_field( 'station_address',   $main_station_id );
    $station_phone     = radiotheme_get_field( 'station_phone',     $main_station_id );
    $station_email     = radiotheme_get_field( 'station_email',     $main_station_id );
    $station_viber     = radiotheme_get_field( 'station_viber',     $main_station_id );
    $station_whatsapp  = radiotheme_get_field( 'station_whatsapp',  $main_station_id );
    $station_facebook  = radiotheme_get_field( 'station_facebook',  $main_station_id );
    $station_twitter   = radiotheme_get_field( 'station_twitter',   $main_station_id );
    $station_instagram = radiotheme_get_field( 'station_instagram', $main_station_id );
    $station_tiktok    = radiotheme_get_field( 'station_tiktok',    $main_station_id );
    $station_youtube   = radiotheme_get_field( 'station_youtube',   $main_station_id );
    $song_title_url    = radiotheme_get_field( 'song_title_url',    $main_station_id );

    $logo_src     = get_the_post_thumbnail_url( $main_station_id, 'large' ) ?: $logo_url;
    $genres       = get_the_terms( $main_station_id, 'radio-genre' );
    $countries    = get_the_terms( $main_station_id, 'radio-country' );
    $cities       = get_the_terms( $main_station_id, 'radio-city' );
    $languages    = get_the_terms( $main_station_id, 'radio-language' );

    $country_name = ( $countries && ! is_wp_error( $countries ) ) ? $countries[0]->name : '';
    $city_name    = ( $cities    && ! is_wp_error( $cities ) )    ? $cities[0]->name    : '';
    $initial      = strtoupper( mb_substr( get_the_title(), 0, 1, 'UTF-8' ) );
?>

<main class="site-main station-detail-page" id="main-content"
      data-country-code="<?php echo esc_attr( $url_cc ); ?>"
      data-country-slug="<?php echo esc_attr( function_exists( 'radiotheme_iso_to_slug' ) ? radiotheme_iso_to_slug( $url_cc ) : '' ); ?>">
    <div class="content-area">

        <article class="radio-list-column station-detail" itemscope itemtype="https://schema.org/RadioBroadcastService">

            <!-- Custom Breadcrumb: Ülke > Şehir > Radyo Adı -->
            <nav class="breadcrumb-nav" aria-label="Breadcrumb">
                <ol class="breadcrumb-list">
                    <?php
                    /* 1. Ülke — taxonomy'den al, URL'e ülke kodu ekle */
                    if ( $countries && ! is_wp_error( $countries ) ) :
                        $bc_country      = $countries[0];
                        $bc_country_iso  = strtolower( function_exists( 'radiotheme_slug_to_iso' ) ? radiotheme_slug_to_iso( $bc_country->slug ) : $url_cc );
                        $bc_country_url  = $bc_country_iso ? home_url( '/' . $bc_country_iso . '/' ) : get_term_link( $bc_country );
                        $bc_country_name = ucwords( str_replace( '-', ' ', $bc_country->slug ) );
                        $bc_country_flag = $bc_country_iso && function_exists( 'radiotheme_iso_to_flag_emoji' )
                            ? radiotheme_iso_to_flag_emoji( strtoupper( $bc_country_iso ) )
                            : '';
                    ?>
                    <li class="breadcrumb-item">
                        <a href="<?php echo esc_url( $bc_country_url ); ?>" class="breadcrumb-link">
                            <?php if ( $bc_country_flag ) : ?><span class="breadcrumb-flag" aria-hidden="true"><?php echo esc_html( $bc_country_flag ); ?></span> <?php endif; ?>
                            <?php echo esc_html( $bc_country_name ); ?>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php
                    /* 2. Şehir — varsa */
                    if ( $cities && ! is_wp_error( $cities ) ) :
                        $bc_city     = $cities[0];
                        $bc_city_url = $bc_country_iso
                            ? home_url( '/' . $bc_country_iso . '/city/' . $bc_city->slug . '/' )
                            : get_term_link( $bc_city );
                    ?>
                    <li class="breadcrumb-item">
                        <a href="<?php echo esc_url( $bc_city_url ); ?>" class="breadcrumb-link"><?php echo esc_html( $bc_city->name ); ?></a>
                    </li>
                    <?php endif; ?>

                    <?php /* 3. Radyo Adı — aktif, link yok */ ?>
                    <li class="breadcrumb-item breadcrumb-current" aria-current="page">
                        <?php the_title(); ?>
                    </li>
                </ol>
            </nav>

            <div class="sd-card">

                <!-- HERO: Logo + Bilgi -->
                <div class="sd-hero">

                    <!-- Sol: Logo -->
                    <div class="sd-logo-wrap" id="sd-logo-wrap">
                        <?php if ( $logo_src ) : ?>
                            <img src="<?php echo esc_url( $logo_src ); ?>"
                                 alt="<?php echo esc_attr( get_the_title() ); ?>"
                                 class="sd-logo-img" id="sd-logo-img" itemprop="logo" loading="eager"
                                 data-orig-src="<?php echo esc_url( $logo_src ); ?>">
                            <div class="sd-logo-fallback" id="sd-logo-fallback" aria-hidden="true" style="display:none"><?php echo esc_html( $initial ); ?></div>
                        <?php else : ?>
                            <img src="" alt="" class="sd-logo-img" id="sd-logo-img"
                                 data-orig-src="" style="display:none">
                            <div class="sd-logo-fallback" id="sd-logo-fallback" aria-hidden="true"><?php echo esc_html( $initial ); ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Sağ: Tüm içerik -->
                    <div class="sd-info">

                        <!-- Satır 1: Radyo Adı + Play -->
                        <div class="sd-title-row">
                            <h1 class="sd-title" itemprop="name"><?php the_title(); ?></h1>
                            <?php if ( $stream_url ) : ?>
                            <button
                                class="play-btn sd-play-btn is-paused"
                                data-station-id="<?php echo esc_attr( $main_station_id ); ?>"
                                data-stream-url="<?php echo esc_attr( $stream_url ); ?>"
                                data-backup-url="<?php echo esc_attr( $backup_url ?: '' ); ?>"
                                data-station-name="<?php echo esc_attr( get_the_title() ); ?>"
                                data-logo="<?php echo esc_url( $logo_src ?: '' ); ?>"
                                data-country="<?php echo esc_attr( $country_name ); ?>"
                                data-song-url="<?php echo esc_attr( $song_title_url ?: $stream_url ); ?>"
                                aria-label="<?php printf( esc_attr__( 'Play %s', 'radiotheme' ), esc_attr( get_the_title() ) ); ?>"
                            >
                                <svg class="icon-play"  viewBox="0 0 24 24" aria-hidden="true"><polygon points="5 3 19 12 5 21 5 3" fill="currentColor"/></svg>
                                <svg class="icon-pause" viewBox="0 0 24 24" aria-hidden="true" style="display:none"><rect x="6" y="4" width="4" height="16" fill="currentColor"/><rect x="14" y="4" width="4" height="16" fill="currentColor"/></svg>
                            </button>
                            <?php endif; ?>
                        </div>

                        <!-- Satır 2: Şarkı Adı (kayan yazı) -->
                        <div class="sd-song-row" id="sd-song-row">
                            <span class="sd-song-note" aria-hidden="true">♪</span>
                            <div class="sd-song-marquee-wrap">
                                <span class="sd-song-text" id="sd-song-text">—</span>
                            </div>
                        </div>

                        <!-- Satır 3: Etiketler -->
                        <div class="sd-meta-tags">
                            <?php if ( $genres && ! is_wp_error( $genres ) ) :
                                foreach ( $genres as $g ) :
                                    /* Genre URL: ülke kodlu /s/tr/genre/haber/ */
                                    $gl = $url_cc && function_exists( 'radiotheme_genre_url' )
                                          ? radiotheme_genre_url( $g->slug, $url_cc )
                                          : get_term_link( $g );
                                    if ( is_wp_error( $gl ) ) continue; ?>
                                <a href="<?php echo esc_url( $gl ); ?>" class="sd-tag sd-tag--genre"><?php echo esc_html( $g->name ); ?></a>
                            <?php endforeach; endif; ?>

                            <?php if ( $country_name ) :
                                /* Ülke URL: ülke kodlu /s/tr/ */
                                $cl = $url_cc && function_exists( 'radiotheme_country_url' )
                                      ? radiotheme_country_url( $url_cc )
                                      : ( ( $countries && ! is_wp_error( $countries ) ) ? get_term_link( $countries[0] ) : '' ); ?>
                                <?php if ( ! is_wp_error( $cl ) && $cl ) : ?>
                                    <a href="<?php echo esc_url( $cl ); ?>" class="sd-tag sd-tag--country" itemprop="areaServed">
                                        <?php if ( $country_code ) : ?>
                                            <img src="https://flagcdn.com/16x12/<?php echo esc_attr( strtolower( $country_code ) ); ?>.png"
                                                 alt="" width="16" height="12" loading="lazy" class="sd-flag">
                                        <?php endif; ?>
                                        <?php echo esc_html( $country_name ); ?>
                                    </a>
                                <?php else : ?>
                                    <span class="sd-tag sd-tag--country"><?php echo esc_html( $country_name ); ?></span>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if ( $city_name ) :
                                /* Şehir URL: ülke kodlu /s/tr/city/istanbul/ */
                                $city_slug_val = ( $cities && ! is_wp_error( $cities ) ) ? $cities[0]->slug : '';
                                $cyl = $url_cc && $city_slug_val && function_exists( 'radiotheme_city_url' )
                                       ? radiotheme_city_url( $city_slug_val, $url_cc )
                                       : ( ( $cities && ! is_wp_error( $cities ) ) ? get_term_link( $cities[0] ) : '' );
                                if ( ! is_wp_error( $cyl ) && $cyl ) : ?>
                                    <a href="<?php echo esc_url( $cyl ); ?>" class="sd-tag sd-tag--city"><?php echo esc_html( $city_name ); ?></a>
                                <?php else : ?>
                                    <span class="sd-tag sd-tag--city"><?php echo esc_html( $city_name ); ?></span>
                                <?php endif;
                            endif; ?>

                            <?php if ( $bitrate ) : ?>
                                <span class="sd-tag sd-tag--meta">
                                    <?php echo esc_html( $bitrate ); ?> kbps<?php if ( $codec ) echo ' &middot; ' . esc_html( $codec ); ?>
                                </span>
                            <?php endif; ?>

                            <?php if ( $languages && ! is_wp_error( $languages ) ) :
                                foreach ( $languages as $lang ) : ?>
                                <span class="sd-tag sd-tag--lang"><?php echo esc_html( $lang->name ); ?></span>
                            <?php endforeach; endif; ?>
                        </div>

                        <!-- Satır 4: Açıklama -->
                        <?php if ( has_excerpt() || get_the_content() ) : ?>
                        <div class="sd-description" itemprop="description">
                            <?php if ( has_excerpt() ) : the_excerpt(); else : the_content(); endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Satır 5: Alt Grid — Sol Paylaşım | Sağ Özel Alanlar -->
                        <div class="sd-bottom-grid">

                            <!-- SOL: Sosyal + İletişim paylaşım butonları -->
                            <div class="sd-share-col">
                                <p class="sd-col-label"><?php esc_html_e( 'Share & Contact', 'radiotheme' ); ?></p>
                                <div class="sd-share-btns">

                                    <?php if ( $station_facebook ) :
                                        $fb_u = radiotheme_social_url( $station_facebook, 'https://facebook.com/' ); ?>
                                    <a href="<?php echo esc_url( $fb_u ); ?>" target="_blank" rel="noopener noreferrer"
                                       class="sd-share-btn sd-share-btn--fb" aria-label="Facebook">
                                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.07C24 5.41 18.63 0 12 0S0 5.41 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.04V9.41c0-3.02 1.8-4.7 4.54-4.7 1.31 0 2.68.24 2.68.24v2.97h-1.5c-1.5 0-1.96.93-1.96 1.89v2.26h3.32l-.53 3.5h-2.8V24C19.62 23.1 24 18.1 24 12.07z"/></svg>
                                        <span>Facebook</span>
                                    </a>
                                    <?php endif; ?>

                                    <?php if ( $station_twitter ) :
                                        $tw_u = radiotheme_social_url( $station_twitter, 'https://x.com/' ); ?>
                                    <a href="<?php echo esc_url( $tw_u ); ?>" target="_blank" rel="noopener noreferrer"
                                       class="sd-share-btn sd-share-btn--tw" aria-label="Twitter/X">
                                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                        <span>Twitter / X</span>
                                    </a>
                                    <?php endif; ?>

                                    <?php if ( $station_whatsapp ) :
                                        $wnum = preg_replace('/[^0-9]/', '', $station_whatsapp); ?>
                                    <a href="https://wa.me/<?php echo esc_attr( $wnum ); ?>" target="_blank" rel="noopener noreferrer"
                                       class="sd-share-btn sd-share-btn--wa" aria-label="WhatsApp">
                                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.4 0 0 5.4 0 12c0 2.1.6 4.2 1.6 6L0 24l6.2-1.6C8 23.4 10 24 12 24c6.6 0 12-5.4 12-12S18.6 0 12 0zm0 22c-1.9 0-3.7-.5-5.3-1.4l-.4-.2-3.7 1 1-3.6-.2-.4C2.5 15.8 2 13.9 2 12 2 6.5 6.5 2 12 2s10 4.5 10 10-4.5 10-10 10zm5.5-7.5c-.3-.2-1.8-.9-2.1-1-.3-.1-.5-.1-.7.1-.2.3-.8 1-1 1.2-.2.2-.4.2-.7.1-.3-.2-1.4-.5-2.6-1.6-1-.9-1.6-2-1.8-2.3-.2-.3 0-.5.1-.7l.5-.6.3-.6v-.6c-.1-.2-.7-1.8-1-2.4-.3-.6-.5-.5-.7-.5h-.6c-.2 0-.6.1-.9.4-.3.3-1.1 1.1-1.1 2.6 0 1.5 1.1 3 1.2 3.2.2.2 2.2 3.4 5.4 4.7 3.2 1.3 3.2.9 3.7.8.6-.1 1.8-.7 2.1-1.4.3-.7.3-1.3.2-1.4-.1-.1-.3-.2-.6-.3z"/></svg>
                                        <span>WhatsApp</span>
                                    </a>
                                    <?php endif; ?>

                                    <?php if ( $station_email ) : ?>
                                    <a href="mailto:<?php echo esc_attr( $station_email ); ?>"
                                       class="sd-share-btn sd-share-btn--email">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 7 10-7"/></svg>
                                        <span><?php echo esc_html( $station_email ); ?></span>
                                    </a>
                                    <?php endif; ?>

                                    <?php if ( $station_viber ) :
                                        $vnum = preg_replace('/\s+/', '', $station_viber); ?>
                                    <a href="viber://chat?number=<?php echo esc_attr( $vnum ); ?>"
                                       class="sd-share-btn sd-share-btn--vi" aria-label="Viber">
                                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.4 0C5.5.3 1 4.6.5 10.5c-.3 3 .5 5.8 2.1 8l-1.4 4.2 4.4-1.4c2 1.3 4.4 2 6.9 1.9 6.2-.3 11-5.5 11-11.7C23.5 4.9 18 0 11.4 0zm.6 19.5c-2 0-3.9-.6-5.4-1.6l-3.8 1.2 1.2-3.7C2.7 13.8 2 11.9 2 9.9 2 4.7 6.2.8 11.4.8c5.1 0 9.4 3.9 9.6 9 .2 5.4-4 9.7-9 9.7zm5.2-7c-.3-.2-1.7-.8-1.9-.9-.3-.1-.5-.1-.7.1-.2.3-.7.9-.9 1.1-.2.2-.3.2-.6.1-.3-.2-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.6-2.1-.2-.3 0-.5.1-.6l.5-.6c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5-.1-.2-.7-1.6-.9-2.2-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.4 0 1.4 1 2.8 1.2 3 .2.2 2 3.1 4.9 4.3 2.8 1.2 2.8.8 3.3.7.5-.1 1.7-.7 1.9-1.3.2-.7.2-1.2.1-1.3z"/></svg>
                                        <span>Viber</span>
                                    </a>
                                    <?php endif; ?>

                                    <?php if ( $station_instagram ) :
                                        $ig_u = radiotheme_social_url( $station_instagram, 'https://instagram.com/' ); ?>
                                    <a href="<?php echo esc_url( $ig_u ); ?>" target="_blank" rel="noopener noreferrer"
                                       class="sd-share-btn sd-share-btn--ig" aria-label="Instagram">
                                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>
                                        <span>Instagram</span>
                                    </a>
                                    <?php endif; ?>

                                    <?php if ( $station_tiktok ) :
                                        $tt_u = radiotheme_social_url( $station_tiktok, 'https://tiktok.com/@' ); ?>
                                    <a href="<?php echo esc_url( $tt_u ); ?>" target="_blank" rel="noopener noreferrer"
                                       class="sd-share-btn sd-share-btn--tt" aria-label="TikTok">
                                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                                        <span>TikTok</span>
                                    </a>
                                    <?php endif; ?>

                                    <?php if ( $station_youtube ) :
                                        $yt_u = radiotheme_social_url( $station_youtube, 'https://youtube.com/' ); ?>
                                    <a href="<?php echo esc_url( $yt_u ); ?>" target="_blank" rel="noopener noreferrer"
                                       class="sd-share-btn sd-share-btn--yt" aria-label="YouTube">
                                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                                        <span>YouTube</span>
                                    </a>
                                    <?php endif; ?>

                                    <?php if ( $website ) : ?>
                                    <a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener noreferrer"
                                       class="sd-share-btn sd-share-btn--web" itemprop="url">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                                        <span><?php esc_html_e( 'Website', 'radiotheme' ); ?></span>
                                    </a>
                                    <?php endif; ?>

                                    <?php if ( $station_phone ) : ?>
                                    <a href="tel:<?php echo esc_attr( preg_replace('/\s+/', '', $station_phone) ); ?>"
                                       class="sd-share-btn sd-share-btn--phone">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2.22h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                        <span><?php echo esc_html( $station_phone ); ?></span>
                                    </a>
                                    <?php endif; ?>

                                </div><!-- .sd-share-btns -->
                            </div><!-- .sd-share-col -->

                            <!-- SAĞ: Ek özel alanlar (geliştiriciler filter ile ekler) -->
                            <?php
                            $extra_fields = apply_filters( 'radiotheme_station_extra_fields', [], $main_station_id );
                            if ( ! empty( $extra_fields ) ) : ?>
                            <div class="sd-extra-col">
                                <p class="sd-col-label"><?php esc_html_e( 'Station Info', 'radiotheme' ); ?></p>
                                <dl class="sd-extra-list">
                                    <?php foreach ( $extra_fields as $label => $value ) :
                                        if ( ! $value ) continue; ?>
                                        <dt><?php echo esc_html( $label ); ?></dt>
                                        <dd><?php echo wp_kses_post( $value ); ?></dd>
                                    <?php endforeach; ?>
                                </dl>
                            </div>
                            <?php endif; ?>

                        </div><!-- .sd-bottom-grid -->

                        <!-- Adres — en altta -->
                        <?php if ( $station_address ) : ?>
                        <div class="sd-address">
                            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                <path d="M10 2C6.69 2 4 4.69 4 8c0 4.5 6 10 6 10s6-5.5 6-10c0-3.31-2.69-6-6-6zm0 8.5A2.5 2.5 0 1 1 10 5.5a2.5 2.5 0 0 1 0 5z" fill="currentColor"/>
                            </svg>
                            <span><?php echo nl2br( esc_html( $station_address ) ); ?></span>
                        </div>
                        <?php endif; ?>

                    </div><!-- .sd-info -->
                </div><!-- .sd-hero -->
            </div><!-- .sd-card -->

            <!-- Benzer İstasyonlar -->
            <?php
            $related_args = [
                'post_type'      => 'radio-station',
                'post_status'    => 'publish',
                'posts_per_page' => 10,
                'post__not_in'   => [ $main_station_id ],
                'lang'           => '',
            ];
            if ( $genres && ! is_wp_error( $genres ) ) {
                $related_args['tax_query'] = [ [ 'taxonomy' => 'radio-genre', 'field' => 'term_id', 'terms' => wp_list_pluck( $genres, 'term_id' ) ] ];
            }
            $related = new WP_Query( $related_args );
            if ( $related->have_posts() ) : ?>
            <section class="related-stations">
                <h2 class="section-title"><?php esc_html_e( 'Similar Stations', 'radiotheme' ); ?></h2>
                <div class="radio-list" role="list">
                    <?php while ( $related->have_posts() ) : $related->the_post();
                        get_template_part( 'template-parts/radio-card' );
                    endwhile; wp_reset_postdata(); ?>
                </div>
            </section>
            <?php endif; ?>

        </article>

        <?php get_sidebar(); ?>
    </div>
</main>

<!-- Şarkı adı → detay sayfası senkronizasyonu -->
<?php if ( $stream_url ) : ?>
<script>
(function() {
    'use strict';

    /* ── Sabitler — URL'ler PHP'den direkt gömülü, hiçbir JS değişkenine bağımlılık yok ── */
    var STATION_ID = '<?php echo esc_js( (string) $main_station_id ); ?>';
    var STREAM_URL = <?php echo json_encode( $song_title_url ?: $stream_url ); ?>;
    var ART_URL    = '<?php echo esc_js( get_template_directory_uri() . '/itunes_artwork.php' ); ?>';
    var SONG_URL   = '<?php echo esc_js( get_template_directory_uri() . '/songtitle_api.php' ); ?>';

    function getArtBase()  { return ART_URL; }
    function getSongBase() { return SONG_URL; }

    /* ── DOM refs ── */
    var songRow      = document.getElementById('sd-song-row');
    var songText     = document.getElementById('sd-song-text');
    var sdLogoImg    = document.getElementById('sd-logo-img');
    var sdLogoFall   = document.getElementById('sd-logo-fallback');

    /* ── Şarkı adı göster ── */
    function updateDetailSong(title) {
        if (!songText || !songRow) return;
        if (title) {
            songText.textContent = title;
            songRow.classList.add('has-song');
            requestAnimationFrame(function() {
                var wrap = songText.parentElement;
                songText.classList.toggle('is-marquee', !!(wrap && songText.scrollWidth > wrap.clientWidth + 4));
            });
        } else {
            songText.textContent = '—';
            songRow.classList.remove('has-song');
            songText.classList.remove('is-marquee');
        }
    }

    /* ── Albüm kapağını logo alanına uygula ── */
    function applyArtwork(url) {
        if (!url || !sdLogoImg) return;
        sdLogoImg.src = url;
        sdLogoImg.style.display = 'block';
        if (sdLogoFall) sdLogoFall.style.display = 'none';
    }

    /* ── Şarkı adından iTunes kapak çek ── */
    function fetchArtworkForTitle(title) {
        var base = getArtBase();
        if (!base || !title) return;
        fetch(base + '?title=' + encodeURIComponent(title), { cache: 'no-store' })
            .then(function(r) { return r.ok ? r.json() : null; })
            .then(function(data) { if (data && data.artwork) applyArtwork(data.artwork); })
            .catch(function() {});
    }

    /* ── Stream'den şarkı adını doğrudan çek → artwork uygula ── */
    function fetchTitleFromStream(callback) {
        var base = getSongBase();
        if (!base || !STREAM_URL) return;
        fetch(base + '?stream_url=' + encodeURIComponent(STREAM_URL), { cache: 'no-store' })
            .then(function(r) { return r.ok ? r.text() : ''; })
            .then(function(t) {
                t = (t || '').trim();
                var bad = ['', 'şimdi çalıyor', 'simdi caliyor', '-'];
                if (t && bad.indexOf(t.toLowerCase()) === -1 && t.length > 1) {
                    if (callback) callback(t);
                    updateDetailSong(t);
                    if (_lastAppliedTitle !== t) {
                        var art = window.RadioThemePlayer ? window.RadioThemePlayer.getArtwork(t) : null;
                        if (art) {
                            applyArtwork(art);
                        } else {
                            fetchArtworkForTitle(t);
                        }
                        _lastAppliedTitle = t;
                    }
                }
            })
            .catch(function() {});
    }

    /* ── Player bar'dan oku ── */
    function readPlayerBar() {
        var np = document.getElementById('player-now-playing');
        return np ? (np.textContent || '').replace(/^[♪\s]+/, '').trim() : '';
    }

    /* ── Ana senkronizasyon: her çağrıda durumu güncelle ── */
    var _lastAppliedTitle = '';
    function sync() {
        /* 1. Player zaten çalışıyorsa cache'den direkt al */
        if (window.RadioThemePlayer) {
            var cachedTitle = window.RadioThemePlayer.getCurrentTitle();
            if (cachedTitle && cachedTitle !== '—') {
                updateDetailSong(cachedTitle);
                var cachedArt = window.RadioThemePlayer.getArtwork(cachedTitle);
                if (cachedArt) {
                    applyArtwork(cachedArt);
                    _lastAppliedTitle = cachedTitle;
                    return;
                }
                /* Artwork cache'de yoksa çek (bir kez) */
                if (_lastAppliedTitle !== cachedTitle) {
                    fetchArtworkForTitle(cachedTitle);
                    _lastAppliedTitle = cachedTitle;
                }
                return;
            }
        }
        /* 2. Player bar'dan oku */
        var barTitle = readPlayerBar();
        if (barTitle && barTitle !== '—') {
            updateDetailSong(barTitle);
            if (_lastAppliedTitle !== barTitle) {
                fetchArtworkForTitle(barTitle);
                _lastAppliedTitle = barTitle;
            }
            return;
        }
        /* 3. Yoksa stream'den çek */
        fetchTitleFromStream();
    }

    /* ── Sayfa yüklenince anında çalıştır ── */
    sync();

    /* ── Player bar değişince anında yakala (MutationObserver) ── */
    function attachObserver() {
        var np = document.getElementById('player-now-playing');
        if (np) {
            new MutationObserver(function() {
                var t = readPlayerBar();
                if (t && t !== '—') {
                    updateDetailSong(t);
                    if (_lastAppliedTitle !== t) {
                        /* Önce player cache'e bak */
                        var art = window.RadioThemePlayer ? window.RadioThemePlayer.getArtwork(t) : null;
                        if (art) {
                            applyArtwork(art);
                        } else {
                            fetchArtworkForTitle(t);
                        }
                        _lastAppliedTitle = t;
                    }
                }
            }).observe(np, { childList: true, subtree: true, characterData: true });
        } else {
            setTimeout(attachObserver, 300);
        }
    }
    attachObserver();

    /* ── rt:songtitle event (player.js'den) ── */
    document.addEventListener('rt:songtitle', function(e) {
        var d = e.detail || {};
        if (!d.stationId || String(d.stationId) === STATION_ID) {
            if (d.title) {
                updateDetailSong(d.title);
                /* Artwork'ü rt:artwork event'i zaten getirecek; sadece cache'de varsa uygula */
                if (window.RadioThemePlayer) {
                    var art = window.RadioThemePlayer.getArtwork(d.title);
                    if (art) { applyArtwork(art); _lastAppliedTitle = d.title; return; }
                }
                /* Cache'de yoksa bir kez çek */
                if (_lastAppliedTitle !== d.title) {
                    fetchArtworkForTitle(d.title);
                    _lastAppliedTitle = d.title;
                }
            } else {
                updateDetailSong('');
            }
        }
    });

    /* ── rt:artwork event (player.js'den) — en güvenilir kaynak ── */
    document.addEventListener('rt:artwork', function(e) {
        var d = e.detail || {};
        if ((!d.stationId || String(d.stationId) === STATION_ID) && d.artworkUrl) {
            applyArtwork(d.artworkUrl);
        }
    });

    /* ── Polling: 10 saniyede bir stream'den kontrol et (şarkı değişmesini yakala) ── */
    setInterval(function() {
        fetchTitleFromStream();
    }, 10000);

})();
</script>
<?php endif; ?>

<?php endwhile; ?>
<?php get_footer(); ?>
