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

        $sql = "SELECT DISTINCT p.ID
                FROM {$wpdb->posts} p
                WHERE p.post_type   = 'radio-station'
                  AND p.post_status = 'publish'
                  AND (
                      p.post_title LIKE %s
                      OR EXISTS (
                          SELECT 1
                          FROM {$wpdb->term_relationships} tr
                          INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                          INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
                          WHERE tr.object_id = p.ID AND tt.taxonomy = 'radio-genre' AND t.name LIKE %s
                      )
                      OR EXISTS (
                          SELECT 1
                          FROM {$wpdb->term_relationships} tr2
                          INNER JOIN {$wpdb->term_taxonomy} tt2 ON tr2.term_taxonomy_id = tt2.term_taxonomy_id
                          INNER JOIN {$wpdb->terms} t2 ON tt2.term_id = t2.term_id
                          WHERE tr2.object_id = p.ID AND tt2.taxonomy = 'radio-city' AND t2.name LIKE %s
                      )
                      OR EXISTS (
                          SELECT 1
                          FROM {$wpdb->term_relationships} tr3
                          INNER JOIN {$wpdb->term_taxonomy} tt3 ON tr3.term_taxonomy_id = tt3.term_taxonomy_id
                          INNER JOIN {$wpdb->terms} t3 ON tt3.term_id = t3.term_id
                          WHERE tr3.object_id = p.ID AND tt3.taxonomy = 'radio-country' AND t3.name LIKE %s
                      )
                  )";

        $params = array( $like, $like, $like, $like );

        if ( ! empty( $country ) ) {
            $sql .= " AND EXISTS (
                SELECT 1
                FROM {$wpdb->term_relationships} trc
                INNER JOIN {$wpdb->term_taxonomy} ttc ON trc.term_taxonomy_id = ttc.term_taxonomy_id
                INNER JOIN {$wpdb->terms} tc ON ttc.term_id = tc.term_id
                WHERE trc.object_id = p.ID AND ttc.taxonomy = 'radio-country' AND tc.slug = %s
            )";
            $params[] = $country;
        }

        if ( ! empty( $genre ) ) {
            $sql .= " AND EXISTS (
                SELECT 1
                FROM {$wpdb->term_relationships} trg
                INNER JOIN {$wpdb->term_taxonomy} ttg ON trg.term_taxonomy_id = ttg.term_taxonomy_id
                INNER JOIN {$wpdb->terms} tg ON ttg.term_id = tg.term_id
                WHERE trg.object_id = p.ID AND ttg.taxonomy = 'radio-genre' AND tg.slug = %s
            )";
            $params[] = $genre;
        }

        $search_ids = array_map( 'intval', $wpdb->get_col( $wpdb->prepare( $sql, ...$params ) ) );

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
        $args['post__in'] = $search_ids;
        $args['orderby']  = 'title';
        $args['order']    = 'ASC';
    } else {
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

    $ids = $wpdb->get_col( $wpdb->prepare(
        "SELECT DISTINCT p.ID
         FROM {$wpdb->posts} p
         WHERE p.post_type   = 'radio-station'
           AND p.post_status = 'publish'
           AND (
               p.post_title LIKE %s
               OR EXISTS (
                   SELECT 1 FROM {$wpdb->term_relationships} tr
                   INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                   INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
                   WHERE tr.object_id = p.ID AND tt.taxonomy = 'radio-genre' AND t.name LIKE %s
               )
               OR EXISTS (
                   SELECT 1 FROM {$wpdb->term_relationships} tr2
                   INNER JOIN {$wpdb->term_taxonomy} tt2 ON tr2.term_taxonomy_id = tt2.term_taxonomy_id
                   INNER JOIN {$wpdb->terms} t2 ON tt2.term_id = t2.term_id
                   WHERE tr2.object_id = p.ID AND tt2.taxonomy = 'radio-city' AND t2.name LIKE %s
               )
           )
         LIMIT 10",
        $like, $like, $like
    ) );

    $results = array();
    if ( ! empty( $ids ) ) {
        $stations = new WP_Query( array(
            'post_type'      => 'radio-station',
            'post_status'    => 'publish',
            'post__in'       => array_map( 'intval', $ids ),
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
