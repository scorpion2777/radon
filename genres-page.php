<?php
/**
 * RadioTheme — genres-page.php
 * /de/genres/ → O ülkedeki tüm genre'leri listeler.
 * Tıklanınca /de/genre/90s/ gider.
 */

get_header();

/* URL'den ülke kodunu parse et — en güvenilir yöntem */
$_gp_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
$_gp_home = rtrim( wp_parse_url( home_url(), PHP_URL_PATH ) ?? '', '/' );
if ( $_gp_home && strpos( $_gp_uri, $_gp_home ) === 0 ) {
    $_gp_uri = substr( $_gp_uri, strlen( $_gp_home ) );
}
$url_cc = '';
if ( preg_match( '#^/([a-z]{2})/#i', $_gp_uri, $_gp_m ) ) {
    $_gp_test = strtolower( $_gp_m[1] );
    if ( radiotheme_iso_to_slug( $_gp_test ) !== '' ) {
        $url_cc = $_gp_test;
    }
}
if ( ! $url_cc ) $url_cc = get_query_var( 'rt_country', '' );
$country_slug = $url_cc ? radiotheme_iso_to_slug( $url_cc ) : '';
$country_name = $country_slug ? ucwords( str_replace( '-', ' ', $country_slug ) ) : '';
$flag         = $url_cc ? radiotheme_iso_to_flag_emoji( strtoupper( $url_cc ) ) : '';

/* Ülkeye ait istasyon ID'leri (cache'li) */
$station_ids = $country_slug ? radiotheme_get_station_ids_by_country( $country_slug ) : [];

/* Genre listesi */
$genre_args = [
    'taxonomy'   => 'radio-genre',
    'orderby'    => 'name',
    'order'      => 'ASC',
    'hide_empty' => true,
    'number'     => 0,
];
if ( $station_ids ) {
    $genre_args['object_ids'] = $station_ids;
}
$genres = get_terms( $genre_args );
?>

<main class="site-main genres-page" id="main-content"
      data-country-code="<?php echo esc_attr( $url_cc ); ?>"
      data-country-slug="<?php echo esc_attr( $country_slug ); ?>">
    <div class="genres-page-wrap">

        <?php
        /* Breadcrumb: 🇩🇪 Germany → Genres */
        if ( function_exists( 'radiotheme_render_breadcrumb' ) && $url_cc && $country_name ) {
            $bc_flag  = function_exists( 'radiotheme_iso_to_flag_emoji' )
                ? radiotheme_iso_to_flag_emoji( strtoupper( $url_cc ) )
                : '';
            $bc_items = [
                [ 'label' => $country_name, 'url' => home_url( '/' . $url_cc . '/' ), 'flag' => $bc_flag ],
                [ 'label' => __( 'Genres', 'radiotheme' ), 'url' => '' ],
            ];
            radiotheme_render_breadcrumb( $bc_items );
        }
        ?>

        <div class="genres-page-header">
            <?php if ( $flag && $country_name ) : ?>
                <span class="genres-page-flag"><?php echo esc_html( $flag ); ?></span>
                <div>
                    <h1 class="genres-page-title"><?php printf( esc_html__( '%s Radio — Genres', 'radiotheme' ), esc_html( $country_name ) ); ?></h1>
                    <p class="genres-page-sub"><?php printf( esc_html__( 'Browse all %d genres', 'radiotheme' ), count( $genres ) ); ?></p>
                </div>
            <?php else : ?>
                <h1 class="genres-page-title"><?php esc_html_e( 'All Genres', 'radiotheme' ); ?></h1>
            <?php endif; ?>
        </div>

        <?php if ( ! is_wp_error( $genres ) && ! empty( $genres ) ) : ?>
        <div class="genres-grid">
            <?php foreach ( $genres as $genre ) :
                $url         = $url_cc
                    ? radiotheme_genre_url( $genre->slug, $url_cc )
                    : get_term_link( $genre );
                $real_count  = radiotheme_genre_count_for_country( (int) $genre->term_id, $country_slug );
                if ( is_wp_error( $url ) ) continue;
            ?>
            <a href="<?php echo esc_url( $url ); ?>" class="genre-card">
                <span class="genre-card-icon" aria-hidden="true">🎵</span>
                <span class="genre-card-name"><?php echo esc_html( $genre->name ); ?></span>
                <span class="genre-card-count"><?php echo esc_html( $real_count ); ?> stations</span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else : ?>
        <p class="genres-page-empty"><?php esc_html_e( 'No genres found.', 'radiotheme' ); ?></p>
        <?php endif; ?>

    </div>
</main>

<?php get_footer(); ?>
