<?php
/**
 * RadioTheme — template-parts/radio-card.php
 * Single station row card (list layout)
 * Context: inside WP_Query loop (the_post() called)
 */

$station_id  = get_the_ID();
$stream_url  = radiotheme_get_field( 'stream_url',        $station_id );
$backup_url  = radiotheme_get_field( 'stream_url_backup', $station_id );
$logo_url    = radiotheme_get_field( 'station_logo_url',  $station_id );
$bitrate     = radiotheme_get_field( 'stream_bitrate',    $station_id );
$codec       = radiotheme_get_field( 'stream_codec',      $station_id );
$country_code= radiotheme_get_field( 'country_code',      $station_id );
$is_active   = radiotheme_get_field( 'station_active',    $station_id );

/* Ülke kodlu URL:
   - AJAX'tan $rt_card_country_code gelebilir (ajax-filter.php set eder)
   - Yoksa istasyonun kendi country_code alanından türetilir */
$_rt_cc      = isset( $rt_card_country_code ) ? $rt_card_country_code : strtolower( $country_code ?: '' );
$station_url = ( $_rt_cc && function_exists( 'radiotheme_station_url' ) )
               ? radiotheme_station_url( $station_id, $_rt_cc )
               : $station_url;

// Logo: WP thumbnail → ACF external URL
$thumb = get_the_post_thumbnail_url( $station_id, 'radio-logo' );
$logo  = $thumb ?: $logo_url;
$initial = strtoupper( mb_substr( get_the_title(), 0, 1 ) );

// Taxonomies
$genres    = get_the_terms( $station_id, 'radio-genre' );
$countries = get_the_terms( $station_id, 'radio-country' );
$cities    = get_the_terms( $station_id, 'radio-city' );

$country_name = ( $countries && ! is_wp_error( $countries ) ) ? $countries[0]->name : '';
$city_name    = ( $cities    && ! is_wp_error( $cities ) )    ? $cities[0]->name    : '';

// Flag emoji
$flag_map = [
    'TR'=>'🇹🇷','US'=>'🇺🇸','GB'=>'🇬🇧','DE'=>'🇩🇪','FR'=>'🇫🇷',
    'ES'=>'🇪🇸','IT'=>'🇮🇹','RU'=>'🇷🇺','BR'=>'🇧🇷','NL'=>'🇳🇱',
    'CA'=>'🇨🇦','AU'=>'🇦🇺','JP'=>'🇯🇵','IN'=>'🇮🇳','MX'=>'🇲🇽',
    'PL'=>'🇵🇱','AR'=>'🇦🇷','UA'=>'🇺🇦','CN'=>'🇨🇳','KR'=>'🇰🇷',
    'SA'=>'🇸🇦','AE'=>'🇦🇪','EG'=>'🇪🇬','PT'=>'🇵🇹','SE'=>'🇸🇪',
    'NO'=>'🇳🇴','DK'=>'🇩🇰','FI'=>'🇫🇮','GR'=>'🇬🇷','RO'=>'🇷🇴',
];
$flag = $country_code ? ( $flag_map[ strtoupper( $country_code ) ] ?? '' ) : '';

// Player meta string
$player_meta    = trim( ( $flag ? $flag . ' ' : '' ) . $country_name );
// Şarkı adı URL: önce özel alan, yoksa stream URL'yi kullan
$song_title_url = radiotheme_get_field( 'song_title_url', $station_id ) ?: $stream_url;
?>
<article
    class="radio-card"
    role="listitem"
    data-station-id="<?php echo esc_attr( $station_id ); ?>"
    data-stream-url="<?php echo esc_url( $stream_url ?: '' ); ?>"
    data-backup-url="<?php echo esc_url( $backup_url ?: '' ); ?>"
    data-station-name="<?php echo esc_attr( get_the_title() ); ?>"
    data-station-logo="<?php echo esc_attr( $logo ?: '' ); ?>"
    data-station-meta="<?php echo esc_attr( $player_meta ); ?>"
    data-song-url="<?php echo esc_attr( $song_title_url ?: '' ); ?>"
    data-permalink="<?php echo esc_url( $station_url ); ?>"
    tabindex="0"
    aria-label="<?php echo esc_attr( sprintf( __( 'Listen to %s', 'radiotheme' ), get_the_title() ) ); ?>"
>

    <!-- Detail Page Link (kart genelinde tıklama alanı) -->
    <a
        href="<?php echo esc_url( $station_url ); ?>"
        class="radio-card-link"
        aria-label="<?php echo esc_attr( sprintf( __( '%s detay sayfası', 'radiotheme' ), get_the_title() ) ); ?>"
        tabindex="-1"
    ></a>

    <!-- Station Logo -->
    <a class="station-logo-wrap" href="<?php echo esc_url( $station_url ); ?>" tabindex="-1" aria-hidden="true">
        <?php if ( $logo ) : ?>
            <img
                src="<?php echo esc_url( $logo ); ?>"
                alt="<?php echo esc_attr( get_the_title() ); ?>"
                class="station-logo"
                width="50"
                height="50"
                loading="lazy"
                decoding="async"
            >
        <?php else : ?>
            <div class="station-logo-fallback" aria-hidden="true"><?php echo esc_html( $initial ); ?></div>
        <?php endif; ?>

        <?php if ( $is_active ) : ?>
            <span class="station-live-dot" aria-hidden="true"></span>
        <?php endif; ?>
    </a>

    <!-- Station Info -->
    <div class="station-info">
        <a class="station-name" href="<?php echo esc_url( $station_url ); ?>"><?php the_title(); ?></a>

        <div class="station-location">
            <?php if ( $flag ) : ?>
                <span class="country-flag" aria-hidden="true"><?php echo esc_html( $flag ); ?></span>
            <?php endif; ?>
            <?php if ( $city_name && $country_name ) : ?>
                <span><?php echo esc_html( $city_name ); ?></span>
                <span class="location-separator" aria-hidden="true">·</span>
                <span><?php echo esc_html( $country_name ); ?></span>
            <?php elseif ( $country_name ) : ?>
                <span><?php echo esc_html( $country_name ); ?></span>
            <?php endif; ?>
        </div>

        <?php if ( $genres && ! is_wp_error( $genres ) ) : ?>
            <div class="station-genres">
                <?php
                $shown = 0;
                foreach ( $genres as $genre ) :
                    if ( $shown >= 3 ) break;
                    $genre_url = get_term_link( $genre );
                ?>
                    <a
                        href="<?php echo is_wp_error( $genre_url ) ? '#' : esc_url( $genre_url ); ?>"
                        class="genre-tag"
                        data-no-ajax
                        onclick="event.stopPropagation()"
                    ><?php echo esc_html( $genre->name ); ?></a>
                <?php
                    $shown++;
                endforeach;
                if ( count( $genres ) > 3 ) :
                ?>
                    <span class="genres-more">+<?php echo esc_html( count( $genres ) - 3 ); ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Station Meta -->
    <div class="station-meta">
        <?php if ( $bitrate ) : ?>
            <span class="bitrate-badge" aria-label="<?php echo esc_attr( $bitrate . ' kbps' ); ?>">
                <?php echo esc_html( $bitrate ); ?>k
            </span>
        <?php endif; ?>
    </div>

    <!-- Equalizer (playing state) -->
    <div class="equalizer" aria-hidden="true">
        <div class="eq-bar"></div>
        <div class="eq-bar"></div>
        <div class="eq-bar"></div>
        <div class="eq-bar"></div>
    </div>

    <!-- Play Button -->
    <?php if ( $stream_url ) : ?>
        <button
            class="play-btn"
            type="button"
            data-station-id="<?php echo esc_attr( $station_id ); ?>"
            data-stream-url="<?php echo esc_url( $stream_url ); ?>"
            data-station-name="<?php echo esc_attr( get_the_title() ); ?>"
            data-logo="<?php echo esc_url( $logo ?: '' ); ?>"
            data-country="<?php echo esc_attr( $country_name ); ?>"
            data-song-url="<?php echo esc_attr( $song_title_url ?: '' ); ?>"
            aria-label="<?php echo esc_attr( sprintf( __( 'Play %s', 'radiotheme' ), get_the_title() ) ); ?>"
            tabindex="-1"
        >
            <svg class="icon-play" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M8 5v14l11-7z" fill="currentColor"/></svg>
            <svg class="icon-pause" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="display:none"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" fill="currentColor"/></svg>
        </button>
    <?php else : ?>
        <a
            href="<?php the_permalink(); ?>"
            class="play-btn"
            aria-label="<?php echo esc_attr( sprintf( __( 'View %s', 'radiotheme' ), get_the_title() ) ); ?>"
            data-no-ajax
        >
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </a>
    <?php endif; ?>

</article>
