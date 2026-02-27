<?php
/**
 * RadioTheme - inc/ajax-filter.php
 * Polylang uyumu: 'lang' => '' ile dil filtresi bypass edilir.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_ajax_load_radio_stations',        'radiotheme_ajax_filter_load_stations' );
add_action( 'wp_ajax_nopriv_load_radio_stations', 'radiotheme_ajax_filter_load_stations' );

function radiotheme_ajax_filter_load_stations() {

    if ( ! check_ajax_referer( 'radiotheme_ajax_nonce', 'nonce', false ) ) {
        wp_send_json_error( array( 'message' => __( 'Security check failed.', 'radiotheme' ) ), 403 );
    }

    $page         = isset( $_POST['page'] )         ? absint( $_POST['page'] )                               : 1;
    $genre        = isset( $_POST['genre'] )        ? sanitize_text_field( wp_unslash( $_POST['genre'] ) )   : '';
    $country      = isset( $_POST['country'] )      ? sanitize_text_field( wp_unslash( $_POST['country'] ) ) : '';
    $country_code = isset( $_POST['country_code'] ) ? sanitize_key( wp_unslash( $_POST['country_code'] ) )   : '';
    $search       = isset( $_POST['search'] )       ? sanitize_text_field( wp_unslash( $_POST['search'] ) )  : '';
    $sort         = isset( $_POST['sort'] )         ? sanitize_key( wp_unslash( $_POST['sort'] ) )            : 'popularity';
    $per_page     = 20;

    global $wpdb;

    $search_ids = null;

    if ( ! empty( $search ) ) {

        $like = '%' . $wpdb->esc_like( $search ) . '%';

        /* Adım 1: Radyo adına göre eşleşen ID'ler */
        $title_ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts}
             WHERE post_type = 'radio-station'
               AND post_status = 'publish'
               AND post_title LIKE %s",
            $like
        ) );

        /* Adım 2: Genre adına göre eşleşen ID'ler */
        $genre_ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT DISTINCT tr.object_id
             FROM {$wpdb->term_relationships} tr
             INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
             INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
             INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
             WHERE tt.taxonomy = 'radio-genre'
               AND t.name LIKE %s
               AND p.post_type = 'radio-station'
               AND p.post_status = 'publish'",
            $like
        ) );

        /* Adım 3: City adına göre eşleşen ID'ler */
        $city_ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT DISTINCT tr.object_id
             FROM {$wpdb->term_relationships} tr
             INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
             INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
             INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
             WHERE tt.taxonomy = 'radio-city'
               AND t.name LIKE %s
               AND p.post_type = 'radio-station'
               AND p.post_status = 'publish'",
            $like
        ) );

        /* Adım 4: Country adına göre eşleşen ID'ler */
        $ctry_ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT DISTINCT tr.object_id
             FROM {$wpdb->term_relationships} tr
             INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
             INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
             INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
             WHERE tt.taxonomy = 'radio-country'
               AND t.name LIKE %s
               AND p.post_type = 'radio-station'
               AND p.post_status = 'publish'",
            $like
        ) );

        /* Hepsini birleştir */
        $all_ids = array_unique( array_map( 'intval', array_merge(
            (array) $title_ids,
            (array) $genre_ids,
            (array) $city_ids,
            (array) $ctry_ids
        ) ) );

        /* Ülke filtresi varsa kesişim al */
        if ( ! empty( $country ) && ! empty( $all_ids ) ) {
            $ids_in     = implode( ',', $all_ids );
            $country_filtered = $wpdb->get_col( $wpdb->prepare(
                "SELECT DISTINCT tr.object_id
                 FROM {$wpdb->term_relationships} tr
                 INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                 INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
                 WHERE tt.taxonomy = 'radio-country'
                   AND t.slug = %s
                   AND tr.object_id IN ($ids_in)",
                $country
            ) );
            $all_ids = array_map( 'intval', (array) $country_filtered );
        }

        /* Genre filtresi varsa kesişim al */
        if ( ! empty( $genre ) && ! empty( $all_ids ) ) {
            $ids_in = implode( ',', $all_ids );
            $genre_filtered = $wpdb->get_col( $wpdb->prepare(
                "SELECT DISTINCT tr.object_id
                 FROM {$wpdb->term_relationships} tr
                 INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                 INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
                 WHERE tt.taxonomy = 'radio-genre'
                   AND t.slug = %s
                   AND tr.object_id IN ($ids_in)",
                $genre
            ) );
            $all_ids = array_map( 'intval', (array) $genre_filtered );
        }

        $search_ids = $all_ids;

        if ( empty( $search_ids ) ) {
            wp_send_json_success( array(
                'html'          => '',
                'has_more'      => false,
                'page'          => 1,
                'total_results' => 0,
                'total_pages'   => 0,
            ) );
        }
    }

    /* WP_Query args */
    $args = array(
        'post_type'      => 'radio-station',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $page,
        'no_found_rows'  => false,
        'lang'           => '',
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'     => 'station_active',
                'value'   => '1',
                'compare' => '=',
            ),
        ),
    );

    if ( $search_ids !== null ) {
        /* Arama sonucu — post__in kullan, tax_query yok */
        $args['post__in'] = $search_ids;
        $args['orderby']  = 'title';
        $args['order']    = 'ASC';
    } else {
        /* Normal filtre */
        $tax_query = array( 'relation' => 'AND' );
        if ( ! empty( $genre ) ) {
            $tax_query[] = array( 'taxonomy' => 'radio-genre', 'field' => 'slug', 'terms' => $genre );
        }
        if ( ! empty( $country ) ) {
            $tax_query[] = array( 'taxonomy' => 'radio-country', 'field' => 'slug', 'terms' => $country );
        }
        if ( count( $tax_query ) > 1 ) {
            $args['tax_query'] = $tax_query;
        }

        switch ( $sort ) {
            case 'name':
                $args['orderby'] = 'title';
                $args['order']   = 'ASC';
                break;
            case 'newest':
                $args['orderby'] = 'date';
                $args['order']   = 'DESC';
                break;
            case 'popularity':
            default:
                $args['orderby']  = 'meta_value_num';
                $args['order']    = 'DESC';
                $args['meta_key'] = 'station_votes';
                break;
        }
    }

    /* Polylang bypass */
    if ( function_exists( 'PLL' ) && isset( PLL()->filters_query ) ) {
        remove_filter( 'posts_join',  array( PLL()->filters_query, 'posts_join' ), 10 );
        remove_filter( 'posts_where', array( PLL()->filters_query, 'posts_where' ), 10 );
    }

    $stations = new WP_Query( $args );

    if ( function_exists( 'PLL' ) && isset( PLL()->filters_query ) ) {
        add_filter( 'posts_join',  array( PLL()->filters_query, 'posts_join' ), 10, 2 );
        add_filter( 'posts_where', array( PLL()->filters_query, 'posts_where' ), 10, 2 );
    }

    ob_start();
    if ( $stations->have_posts() ) {
        while ( $stations->have_posts() ) {
            $stations->the_post();
            $station_id           = get_the_ID();
            $rt_card_country_code = strtolower( $country_code ?: radiotheme_get_field( 'country_code', $station_id ) ?: '' );
            include RADIOTHEME_DIR . '/template-parts/radio-card.php';
        }
        wp_reset_postdata();
    }
    $html = ob_get_clean();

    wp_send_json_success( array(
        'html'          => $html,
        'has_more'      => ( $page < $stations->max_num_pages ),
        'page'          => $page,
        'total_results' => $stations->found_posts,
        'total_pages'   => $stations->max_num_pages,
    ) );
}

/* ================================================================
   AUTOCOMPLETE SEARCH (dropdown)
================================================================ */
add_action( 'wp_ajax_search_radio_stations',        'radiotheme_ajax_search_stations' );
add_action( 'wp_ajax_nopriv_search_radio_stations', 'radiotheme_ajax_search_stations' );

function radiotheme_ajax_search_stations() {

    if ( ! check_ajax_referer( 'radiotheme_ajax_nonce', 'nonce', false ) ) {
        wp_send_json_error( array( 'message' => __( 'Security check failed.', 'radiotheme' ) ), 403 );
    }

    $query = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';

    if ( strlen( $query ) < 2 ) {
        wp_send_json_success( array( 'results' => array() ) );
    }

    global $wpdb;
    $like = '%' . $wpdb->esc_like( $query ) . '%';

    $title_ids = $wpdb->get_col( $wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts}
         WHERE post_type = 'radio-station' AND post_status = 'publish'
           AND post_title LIKE %s LIMIT 10",
        $like
    ) );

    $genre_ids = $wpdb->get_col( $wpdb->prepare(
        "SELECT DISTINCT tr.object_id
         FROM {$wpdb->term_relationships} tr
         INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
         INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
         INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
         WHERE tt.taxonomy = 'radio-genre' AND t.name LIKE %s
           AND p.post_type = 'radio-station' AND p.post_status = 'publish' LIMIT 10",
        $like
    ) );

    $city_ids = $wpdb->get_col( $wpdb->prepare(
        "SELECT DISTINCT tr.object_id
         FROM {$wpdb->term_relationships} tr
         INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
         INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
         INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
         WHERE tt.taxonomy = 'radio-city' AND t.name LIKE %s
           AND p.post_type = 'radio-station' AND p.post_status = 'publish' LIMIT 10",
        $like
    ) );

    $ids = array_unique( array_map( 'intval', array_merge(
        (array) $title_ids,
        (array) $genre_ids,
        (array) $city_ids
    ) ) );

    $results = array();
    if ( ! empty( $ids ) ) {
        $stations = new WP_Query( array(
            'post_type'      => 'radio-station',
            'post_status'    => 'publish',
            'post__in'       => $ids,
            'posts_per_page' => 10,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'lang'           => '',
            'meta_query'     => array( array( 'key' => 'station_active', 'value' => '1', 'compare' => '=' ) ),
        ) );
        if ( $stations->have_posts() ) {
            while ( $stations->have_posts() ) {
                $stations->the_post();
                $station_id = get_the_ID();
                $logo_url   = radiotheme_get_field( 'station_logo_url', $station_id );
                $logo       = get_the_post_thumbnail_url( $station_id, 'radio-logo' ) ?: $logo_url;
                $countries  = get_the_terms( $station_id, 'radio-country' );
                $country_n  = ( $countries && ! is_wp_error( $countries ) ) ? $countries[0]->name : '';
                $s_cc       = strtolower( radiotheme_get_field( 'country_code', $station_id ) ?: '' );
                $s_url      = $s_cc && function_exists( 'radiotheme_station_url' )
                              ? radiotheme_station_url( $station_id, $s_cc )
                              : get_permalink();
                $results[]  = array(
                    'id'         => $station_id,
                    'name'       => get_the_title(),
                    'url'        => $s_url,
                    'stream_url' => radiotheme_get_field( 'stream_url', $station_id ),
                    'logo'       => $logo,
                    'country'    => $country_n,
                );
            }
            wp_reset_postdata();
        }
    }

    wp_send_json_success( array( 'results' => $results ) );
}
