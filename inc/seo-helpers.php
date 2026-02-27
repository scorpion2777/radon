<?php
/**
 * RadioTheme - inc/seo-helpers.php
 * Yoast SEO Premium integration helpers
 * hreflang tags, meta descriptions, sitemaps, canonical URLs
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register custom post type and taxonomies with Yoast SEO
 * Ensures radio-station CPT appears in Yoast sitemap
 */
add_action( 'init', 'radiotheme_register_yoast_cpt', 20 );

function radiotheme_register_yoast_cpt() {
    if ( ! class_exists( 'WPSEO_Options' ) ) {
        return;
    }

    // Ensure radio-station is included in sitemap
    $options = get_option( 'wpseo' );
    if ( $options ) {
        $options['noindex-radio-station'] = false;
        update_option( 'wpseo', $options );
    }
}

/**
 * Add hreflang alternate links for multilingual SEO
 * Works when WPML is NOT active (manual implementation)
 * When WPML is active, it handles this automatically
 */
// hreflang devre dışı — tek dil site, WPML kullanılmıyor.
// Çok dilli yapıya geçilirse WPML veya Yoast Multilingual bu tag'leri otomatik üretir.
// add_action( 'wp_head', 'radiotheme_output_hreflang', 1 );

/**
 * Custom Yoast SEO meta description for radio stations
 * Auto-generates if no custom description is set in Yoast
 */
add_filter( 'wpseo_metadesc', 'radiotheme_yoast_metadesc', 10, 1 );

function radiotheme_yoast_metadesc( $meta_description ) {
    if ( ! is_singular( 'radio-station' ) ) {
        return $meta_description;
    }

    // If Yoast has a custom description set, use it
    if ( ! empty( $meta_description ) ) {
        return $meta_description;
    }

    $station_id   = get_the_ID();
    $station_name = get_the_title( $station_id );

    // Get taxonomies for description
    $genres    = get_the_terms( $station_id, 'radio-genre' );
    $countries = get_the_terms( $station_id, 'radio-country' );
    $cities    = get_the_terms( $station_id, 'radio-city' );

    $genre_str   = ( $genres    && ! is_wp_error( $genres ) )    ? wp_list_pluck( $genres, 'name' )[0]    : '';
    $country_str = ( $countries && ! is_wp_error( $countries ) ) ? wp_list_pluck( $countries, 'name' )[0] : '';
    $city_str    = ( $cities    && ! is_wp_error( $cities ) )    ? wp_list_pluck( $cities, 'name' )[0]    : '';

    // Build auto description
    $parts = array();

    if ( $genre_str && $country_str ) {
        $parts[] = sprintf(
            /* translators: %1$s: station name, %2$s: genre, %3$s: country */
            __( 'Listen to %1$s live - %2$s radio from %3$s.', 'radiotheme' ),
            $station_name,
            $genre_str,
            $country_str
        );
    } elseif ( $country_str ) {
        $parts[] = sprintf(
            /* translators: %1$s: station name, %2$s: country */
            __( 'Listen to %1$s live - online radio from %2$s.', 'radiotheme' ),
            $station_name,
            $country_str
        );
    } else {
        $parts[] = sprintf(
            /* translators: %s: station name */
            __( 'Listen to %s live online radio.', 'radiotheme' ),
            $station_name
        );
    }

    $parts[] = __( 'Free streaming, no download required.', 'radiotheme' );

    return implode( ' ', $parts );
}

/**
 * Custom Yoast SEO title for radio station pages
 */
add_filter( 'wpseo_title', 'radiotheme_yoast_title', 10, 1 );

function radiotheme_yoast_title( $title ) {
    if ( ! is_singular( 'radio-station' ) ) {
        return $title;
    }

    // If Yoast has a custom title set, use it
    $yoast_title = get_post_meta( get_the_ID(), '_yoast_wpseo_title', true );
    if ( ! empty( $yoast_title ) ) {
        return $title;
    }

    $station_name = get_the_title();
    $site_name    = get_bloginfo( 'name' );

    $countries = get_the_terms( get_the_ID(), 'radio-country' );
    $country   = ( $countries && ! is_wp_error( $countries ) ) ? $countries[0]->name : '';

    if ( $country ) {
        return sprintf( '%s - Live Online Radio | %s', $station_name, $site_name );
    }

    return sprintf( '%s - Free Live Radio Stream | %s', $station_name, $site_name );
}

/**
 * Add radio station CPT to Yoast XML sitemap
 */
add_filter( 'wpseo_sitemap_exclude_post_type', 'radiotheme_include_in_sitemap', 10, 2 );

function radiotheme_include_in_sitemap( $excluded, $post_type ) {
    if ( 'radio-station' === $post_type ) {
        return false; // Do NOT exclude
    }
    return $excluded;
}

/**
 * Add taxonomy archives to Yoast sitemap
 */
add_filter( 'wpseo_sitemap_exclude_taxonomy', 'radiotheme_include_taxonomy_in_sitemap', 10, 2 );

function radiotheme_include_taxonomy_in_sitemap( $excluded, $taxonomy ) {
    $radio_taxonomies = array( 'radio-genre', 'radio-country', 'radio-city', 'radio-language' );
    if ( in_array( $taxonomy, $radio_taxonomies, true ) ) {
        return false; // Do NOT exclude
    }
    return $excluded;
}

/**
 * Set canonical URL for taxonomy pages
 * Prevents duplicate content when same station appears in multiple archives
 */
add_filter( 'wpseo_canonical', 'radiotheme_canonical_url' );

function radiotheme_canonical_url( $canonical ) {
    if ( is_tax() && ! $canonical ) {
        $term     = get_queried_object();
        $term_url = get_term_link( $term );
        if ( ! is_wp_error( $term_url ) ) {
            return $term_url;
        }
    }
    return $canonical;
}

/**
 * Add Yoast SEO Primary Category support
 * Maps to radio-country taxonomy for better SEO
 */
add_filter( 'wpseo_primary_term_taxonomies', function( $taxonomies, $post_type ) {
    if ( 'radio-station' === $post_type ) {
        $taxonomies['radio-country'] = array(
            'name'     => __( 'Countries', 'radiotheme' ),
            'singular' => __( 'Country', 'radiotheme' ),
        );
        $taxonomies['radio-genre'] = array(
            'name'     => __( 'Genres', 'radiotheme' ),
            'singular' => __( 'Genre', 'radiotheme' ),
        );
    }
    return $taxonomies;
}, 10, 2 );

/**
 * ============================================================
 * PAGINATION SEO
 * ============================================================
 *
 * Anasayfada ?page=2, ?genre=jazz&page=3 gibi URL'ler için:
 *  - Canonical URL: her zaman kendi sayfasına işaret eder
 *  - rel="prev" / rel="next": Google sayfa zincirini anlar
 *  - Yoast ile çakışmayı önlemek için yalnızca ana sayfa + page > 1'de devreye girer
 *  - Arama (?search=) canonical'dan çıkarılır (duplicate content olmasın)
 */
add_action( 'wp_head', 'radiotheme_pagination_seo_tags', 5 );

function radiotheme_pagination_seo_tags() {

    /* Yalnızca anasayfada çalış */
    if ( ! is_front_page() ) {
        return;
    }

    $page    = isset( $_GET['page'] )    ? absint( $_GET['page'] )                               : 1;
    $genre   = isset( $_GET['genre'] )   ? sanitize_text_field( wp_unslash( $_GET['genre'] ) )   : '';
    $country = isset( $_GET['country'] ) ? sanitize_text_field( wp_unslash( $_GET['country'] ) ) : '';
    $sort    = isset( $_GET['sort'] )    ? sanitize_text_field( wp_unslash( $_GET['sort'] ) )    : 'popularity';

    /* Sayfa başına adet — ajax-filter.php ile senkron kalmalı */
    $per_page = 20;

    /* Toplam radyo sayısını hesapla (filtreli) */
    $count_args = array(
        'post_type'      => 'radio-station',
        'post_status'    => 'publish',
        'posts_per_page' => 1,        /* sadece found_posts lazım */
        'no_found_rows'  => false,
        'lang'           => '',
        'meta_query'     => array(
            array(
                'key'     => 'station_active',
                'value'   => '1',
                'compare' => '=',
            ),
        ),
    );

    if ( ! empty( $genre ) ) {
        $count_args['tax_query'][] = array(
            'taxonomy' => 'radio-genre',
            'field'    => 'slug',
            'terms'    => $genre,
        );
    }

    if ( ! empty( $country ) ) {
        $count_args['tax_query'][] = array(
            'taxonomy' => 'radio-country',
            'field'    => 'slug',
            'terms'    => $country,
        );
    }

    $count_query = new WP_Query( $count_args );
    $total_pages = max( 1, (int) ceil( $count_query->found_posts / $per_page ) );
    wp_reset_postdata();

    /* Canonical ve prev/next için temiz parametre seti oluştur */
    $build_url = function( $p ) use ( $genre, $country, $sort ) {
        $params = array();
        if ( ! empty( $genre ) )                    $params['genre']   = $genre;
        if ( ! empty( $country ) )                  $params['country'] = $country;
        if ( $sort && $sort !== 'popularity' )      $params['sort']    = $sort;
        if ( $p > 1 )                               $params['page']    = $p;
        $base = home_url( '/' );
        return $base . ( count( $params ) ? '?' . http_build_query( $params ) : '' );
    };

    $canonical = $build_url( $page );

    /* Canonical — Yoast aktifse onun filtresi zaten çalışır,
       biz sadece kendi canonical'ımızı override ederiz.
       Yoast yoksa manuel olarak basıyoruz. */
    if ( ! class_exists( 'WPSEO_Options' ) ) {
        echo '<link rel="canonical" href="' . esc_url( $canonical ) . '" />' . "\n";
    }

    /* rel="prev" — Google hâlâ destekliyor, Bing de */
    if ( $page > 1 ) {
        echo '<link rel="prev" href="' . esc_url( $build_url( $page - 1 ) ) . '" />' . "\n";
    }

    /* rel="next" */
    if ( $page < $total_pages ) {
        echo '<link rel="next" href="' . esc_url( $build_url( $page + 1 ) ) . '" />' . "\n";
    }
}

/**
 * Yoast SEO aktifse canonical'ı pagination URL'ine yönlendir.
 * (Önceki fonksiyondaki yoast bloğunu tamamlar.)
 */
add_filter( 'wpseo_canonical', 'radiotheme_pagination_canonical_yoast' );

function radiotheme_pagination_canonical_yoast( $canonical ) {

    if ( ! is_front_page() ) {
        return $canonical;
    }

    $page    = isset( $_GET['page'] )    ? absint( $_GET['page'] )                               : 1;
    $genre   = isset( $_GET['genre'] )   ? sanitize_text_field( wp_unslash( $_GET['genre'] ) )   : '';
    $country = isset( $_GET['country'] ) ? sanitize_text_field( wp_unslash( $_GET['country'] ) ) : '';
    $sort    = isset( $_GET['sort'] )    ? sanitize_text_field( wp_unslash( $_GET['sort'] ) )    : 'popularity';

    $params = array();
    if ( ! empty( $genre ) )               $params['genre']   = $genre;
    if ( ! empty( $country ) )             $params['country'] = $country;
    if ( $sort && $sort !== 'popularity' ) $params['sort']    = $sort;
    if ( $page > 1 )                       $params['page']    = $page;

    $base = home_url( '/' );
    return $base . ( count( $params ) ? '?' . http_build_query( $params ) : '' );
}

/**
 * Sayfalı anasayfa için dinamik <title> üret.
 * Örn: "Jazz Radio Stations – Page 3 | SiteName"
 */
add_filter( 'wpseo_title', 'radiotheme_pagination_title_yoast', 20 );
add_filter( 'pre_get_document_title', 'radiotheme_pagination_title_native', 20 );

function radiotheme_pagination_build_title() {

    if ( ! is_front_page() ) {
        return '';
    }

    $page    = isset( $_GET['page'] )  ? absint( $_GET['page'] ) : 1;
    $genre   = isset( $_GET['genre'] ) ? sanitize_text_field( wp_unslash( $_GET['genre'] ) ) : '';
    $site    = get_bloginfo( 'name' );

    $base = $genre
        ? sprintf( __( '%s Radio Stations', 'radiotheme' ), ucfirst( $genre ) )
        : __( 'Live Radio Stations', 'radiotheme' );

    if ( $page > 1 ) {
        /* translators: %1$s: section title, %2$d: page number, %3$s: site name */
        return sprintf( __( '%1$s – Page %2$d | %3$s', 'radiotheme' ), $base, $page, $site );
    }

    return '';
}

function radiotheme_pagination_title_yoast( $title ) {
    $t = radiotheme_pagination_build_title();
    return $t ?: $title;
}

function radiotheme_pagination_title_native( $title ) {
    $t = radiotheme_pagination_build_title();
    return $t ?: $title;
}

/**
 * Breadcrumb HTML render fonksiyonu.
 * Tüm sayfalarda kullanılabilir.
 *
 * $items = [
 *   ['label' => 'Germany', 'url' => 'https://...', 'flag' => '🇩🇪'],  // ülke — link + bayrak
 *   ['label' => 'Rock',    'url' => ''],                               // aktif (son öğe)
 * ]
 */
function radiotheme_render_breadcrumb( array $items ): void {
    if ( count( $items ) < 2 ) return;
    ?>
    <nav class="breadcrumb-nav" aria-label="Breadcrumb">
        <ol class="breadcrumb-list">
            <?php foreach ( $items as $i => $item ) :
                $is_last = ( $i === count( $items ) - 1 );
                $flag    = ! empty( $item['flag'] ) ? $item['flag'] : '';
            ?>
            <?php if ( $is_last ) : ?>
            <li class="breadcrumb-item breadcrumb-current" aria-current="page">
                <?php if ( $flag ) : ?><span class="breadcrumb-flag" aria-hidden="true"><?php echo esc_html( $flag ); ?></span> <?php endif; ?>
                <?php echo esc_html( $item['label'] ); ?>
            </li>
            <?php else : ?>
            <li class="breadcrumb-item">
                <a href="<?php echo esc_url( $item['url'] ); ?>" class="breadcrumb-link">
                    <?php if ( $flag ) : ?><span class="breadcrumb-flag" aria-hidden="true"><?php echo esc_html( $flag ); ?></span> <?php endif; ?>
                    <?php echo esc_html( $item['label'] ); ?>
                </a>
            </li>
            <?php endif; ?>
            <?php endforeach; ?>
        </ol>
    </nav>
    <?php
}
