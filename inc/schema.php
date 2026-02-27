<?php
/**
 * RadioTheme - inc/schema.php
 * Schema.org structured data for SEO
 * Implements: WebSite, BroadcastService, ItemList
 * Compatible with Yoast SEO Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Output schema on relevant pages
 * Runs in wp_head (after Yoast SEO outputs its schema)
 */
add_action( 'wp_head', 'radiotheme_output_schema', 99 );

function radiotheme_output_schema() {
    $schemas = array();

    // WebSite schema (homepage only)
    if ( is_front_page() ) {
        $schemas[] = radiotheme_get_website_schema();
    }

    // BroadcastService schema (single radio station pages)
    if ( is_singular( 'radio-station' ) ) {
        $schema = radiotheme_get_broadcast_service_schema( get_the_ID() );
        if ( $schema ) {
            $schemas[] = $schema;
        }
    }

    // ItemList schema (archive / taxonomy / homepage with radio list)
    if ( is_front_page() || is_post_type_archive( 'radio-station' ) || is_tax( 'radio-genre' ) || is_tax( 'radio-country' ) ) {
        $list_schema = radiotheme_get_item_list_schema();
        if ( $list_schema ) {
            $schemas[] = $list_schema;
        }
    }

    if ( empty( $schemas ) ) {
        return;
    }

    foreach ( $schemas as $schema ) {
        echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
    }
}

/**
 * WebSite schema with SearchAction (enables Google Sitelinks search box)
 */
function radiotheme_get_website_schema() {
    return array(
        '@context'        => 'https://schema.org',
        '@type'           => 'WebSite',
        '@id'             => home_url( '/#website' ),
        'url'             => home_url( '/' ),
        'name'            => get_bloginfo( 'name' ),
        'description'     => get_bloginfo( 'description' ),
        'inLanguage'      => get_locale(),
        'potentialAction' => array(
            '@type'       => 'SearchAction',
            'target'      => array(
                '@type'       => 'EntryPoint',
                'urlTemplate' => home_url( '/?search={search_term_string}' ),
            ),
            'query-input' => 'required name=search_term_string',
        ),
    );
}

/**
 * BroadcastService schema for a single radio station
 * Schema type: BroadcastService + https://schema.org/RadioBroadcastService
 */
function radiotheme_get_broadcast_service_schema( $station_id ) {
    if ( ! $station_id ) return null;

    $station_name = get_the_title( $station_id );
    $stream_url   = function_exists( 'get_field' ) ? get_field( 'stream_url', $station_id ) : '';
    $website      = function_exists( 'get_field' ) ? get_field( 'station_website', $station_id ) : '';
    $logo_url     = function_exists( 'get_field' ) ? get_field( 'station_logo_url', $station_id ) : '';
    $country_code = function_exists( 'get_field' ) ? get_field( 'country_code', $station_id ) : '';
    $bitrate      = function_exists( 'get_field' ) ? get_field( 'stream_bitrate', $station_id ) : '';
    $permalink    = get_permalink( $station_id );

    // Get logo
    $logo_src = get_the_post_thumbnail_url( $station_id, 'full' ) ?: $logo_url;

    // Get taxonomies
    $genres    = get_the_terms( $station_id, 'radio-genre' );
    $countries = get_the_terms( $station_id, 'radio-country' );
    $languages = get_the_terms( $station_id, 'radio-language' );
    $cities    = get_the_terms( $station_id, 'radio-city' );

    $genre_names   = ( $genres    && ! is_wp_error( $genres ) )    ? wp_list_pluck( $genres, 'name' )    : array();
    $country_names = ( $countries && ! is_wp_error( $countries ) ) ? wp_list_pluck( $countries, 'name' ) : array();
    $language_names = ( $languages && ! is_wp_error( $languages ) ) ? wp_list_pluck( $languages, 'name' ) : array();
    $city_names    = ( $cities    && ! is_wp_error( $cities ) )    ? wp_list_pluck( $cities, 'name' )    : array();

    $schema = array(
        '@context' => 'https://schema.org',
        '@type'    => array( 'RadioBroadcastService', 'BroadcastService' ),
        '@id'      => $permalink . '#broadcast-service',
        'name'     => $station_name,
        'url'      => $permalink,
    );

    if ( $website ) {
        $schema['sameAs'] = $website;
    }

    if ( $logo_src ) {
        $schema['logo'] = array(
            '@type' => 'ImageObject',
            'url'   => $logo_src,
        );

        $schema['image'] = array(
            '@type' => 'ImageObject',
            'url'   => $logo_src,
        );
    }

    if ( ! empty( $genre_names ) ) {
        $schema['genre'] = implode( ', ', $genre_names );
    }

    if ( ! empty( $language_names ) ) {
        $schema['inLanguage'] = $language_names;
    }

    if ( $country_code ) {
        $schema['areaServed'] = array(
            '@type'      => 'Country',
            'identifier' => strtoupper( $country_code ),
        );
    } elseif ( ! empty( $country_names ) ) {
        $schema['areaServed'] = array(
            '@type' => 'Country',
            'name'  => $country_names[0],
        );
    }

    // Broadcast channel with stream URL
    if ( $stream_url ) {
        $broadcast_channel = array(
            '@type'           => 'BroadcastChannel',
            'broadcastServiceTier' => 'Free',
        );

        if ( $bitrate ) {
            $broadcast_channel['broadcastFrequency'] = $bitrate . ' kbps';
        }

        $schema['hasBroadcastChannel'] = $broadcast_channel;
    }

    // Geographic area
    if ( ! empty( $city_names ) ) {
        $schema['areaServed'][] = array(
            '@type' => 'City',
            'name'  => $city_names[0],
        );
    }

    return $schema;
}

/**
 * ItemList schema for station list pages
 * Shows top stations as a list in search results
 */
function radiotheme_get_item_list_schema() {
    $args = array(
        'post_type'      => 'radio-station',
        'post_status'    => 'publish',
        'posts_per_page' => 10,
        'meta_key'       => 'station_votes',
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
        'no_found_rows'  => true,
    );

    // Apply taxonomy context
    if ( is_tax( 'radio-genre' ) || is_tax( 'radio-country' ) ) {
        $term = get_queried_object();
        if ( $term ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => $term->taxonomy,
                    'field'    => 'term_id',
                    'terms'    => $term->term_id,
                ),
            );
        }
    }

    $stations = new WP_Query( $args );

    if ( ! $stations->have_posts() ) {
        wp_reset_postdata();
        return null;
    }

    $list_items = array();
    $position   = 1;

    while ( $stations->have_posts() ) {
        $stations->the_post();

        $station_id   = get_the_ID();
        $station_name = get_the_title();
        $permalink    = get_permalink();
        $logo_url     = function_exists( 'get_field' ) ? get_field( 'station_logo_url', $station_id ) : '';
        $logo         = get_the_post_thumbnail_url( $station_id, 'full' ) ?: $logo_url;

        $item = array(
            '@type'    => 'ListItem',
            'position' => $position,
            'item'     => array(
                '@type'  => array( 'RadioBroadcastService', 'BroadcastService' ),
                '@id'    => $permalink . '#broadcast-service',
                'name'   => $station_name,
                'url'    => $permalink,
            ),
        );

        if ( $logo ) {
            $item['item']['image'] = $logo;
        }

        $list_items[] = $item;
        $position++;
    }

    wp_reset_postdata();

    if ( empty( $list_items ) ) {
        return null;
    }

    return array(
        '@context'        => 'https://schema.org',
        '@type'           => 'ItemList',
        'name'            => is_tax() ? single_term_title( '', false ) : get_bloginfo( 'name' ),
        'description'     => is_tax() ? term_description() : get_bloginfo( 'description' ),
        'url'             => get_pagenum_link( 1 ),
        'numberOfItems'   => count( $list_items ),
        'itemListElement' => $list_items,
    );
}

/**
 * Add breadcrumb schema (works alongside Yoast SEO breadcrumbs)
 */
add_action( 'wp_head', 'radiotheme_breadcrumb_schema', 100 );

function radiotheme_breadcrumb_schema() {
    // Skip if Yoast SEO handles breadcrumbs
    if ( class_exists( 'WPSEO_Breadcrumbs' ) ) {
        return;
    }

    $breadcrumbs = array();
    $position    = 1;

    // Home
    $breadcrumbs[] = array(
        '@type'    => 'ListItem',
        'position' => $position++,
        'item'     => array(
            '@id'  => home_url( '/' ),
            'name' => get_bloginfo( 'name' ),
        ),
    );

    // Radio archive
    if ( ! is_front_page() ) {
        $breadcrumbs[] = array(
            '@type'    => 'ListItem',
            'position' => $position++,
            'item'     => array(
                '@id'  => home_url( '/' ),
                'name' => __( 'Radio Stations', 'radiotheme' ),
            ),
        );
    }

    // Taxonomy term
    if ( is_tax() ) {
        $term          = get_queried_object();
        $breadcrumbs[] = array(
            '@type'    => 'ListItem',
            'position' => $position++,
            'item'     => array(
                '@id'  => get_term_link( $term ),
                'name' => $term->name,
            ),
        );
    }

    // Single station
    if ( is_singular( 'radio-station' ) ) {
        $breadcrumbs[] = array(
            '@type'    => 'ListItem',
            'position' => $position++,
            'item'     => array(
                '@id'  => get_permalink(),
                'name' => get_the_title(),
            ),
        );
    }

    if ( count( $breadcrumbs ) < 2 ) {
        return;
    }

    $schema = array(
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => $breadcrumbs,
    );

    echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}

/**
 * Yoast SEO Schema Integration
 * Custom schema class for radio stations
 */
if ( ! class_exists( 'RadioTheme_Radio_Schema' ) ) {
    class RadioTheme_Radio_Schema {
        private $context;

        public function __construct( $context ) {
            $this->context = $context;
        }

        public function is_needed() {
            return is_singular( 'radio-station' );
        }

        public function generate() {
            $station_id = get_the_ID();
            return radiotheme_get_broadcast_service_schema( $station_id );
        }
    }
}
