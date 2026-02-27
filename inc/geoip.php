<?php
/**
 * RadioTheme - inc/geoip-redirect.php
 * 
 * v5.3 FIX: GeoIP redirect Polylang ile çakışıyordu.
 * Polylang aktifse bu dosyanın kendi redirect'i tamamen devre dışı.
 * Polylang zaten dil yönetimini yapıyor — ikisi birden çalışınca
 * bozuk URL'ler oluşuyordu.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function radiotheme_get_country_lang_map() {
    return array(
        'TR' => 'tr',
        'DE' => 'de', 'AT' => 'de', 'CH' => 'de',
        'FR' => 'fr', 'BE' => 'fr', 'LU' => 'fr',
        'ES' => 'es', 'MX' => 'es', 'AR' => 'es',
        'CL' => 'es', 'CO' => 'es', 'PE' => 'es',
        'VE' => 'es', 'EC' => 'es', 'BO' => 'es',
        'SA' => 'ar', 'AE' => 'ar', 'EG' => 'ar',
        'MA' => 'ar', 'DZ' => 'ar', 'IQ' => 'ar',
        'BR' => 'pt', 'PT' => 'pt', 'AO' => 'pt',
        'RU' => 'ru', 'BY' => 'ru', 'KZ' => 'ru',
        'PL' => 'pl',
        'US' => 'en', 'GB' => 'en', 'CA' => 'en',
        'AU' => 'en', 'NZ' => 'en', 'IE' => 'en',
        'ZA' => 'en', 'IN' => 'en', 'NG' => 'en',
    );
}

function radiotheme_get_user_country() {
    if ( ! empty( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) {
        $country = strtoupper( sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) );
        if ( $country !== 'XX' && $country !== 'T1' && strlen( $country ) === 2 ) {
            return $country;
        }
    }
    if ( ! empty( $_SERVER['HTTP_X_COUNTRY_CODE'] ) ) {
        $country = strtoupper( sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_COUNTRY_CODE'] ) ) );
        if ( strlen( $country ) === 2 && ctype_alpha( $country ) ) {
            return $country;
        }
    }
    if ( function_exists( 'geoip_detect2_get_info_from_current_ip' ) ) {
        $record = geoip_detect2_get_info_from_current_ip();
        if ( $record && isset( $record->country->isoCode ) ) {
            return strtoupper( $record->country->isoCode );
        }
    }
    return '';
}

function radiotheme_get_user_language() {
    $supported_langs = array( 'en', 'tr', 'de', 'fr', 'es', 'ar', 'pt', 'ru', 'pl' );

    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
    foreach ( $supported_langs as $lang ) {
        if ( preg_match( '#^/' . preg_quote( $lang, '#' ) . '(/|$)#', $request_uri ) ) {
            return $lang;
        }
    }
    if ( ! empty( $_COOKIE['radio_lang'] ) ) {
        $cookie_lang = sanitize_key( $_COOKIE['radio_lang'] );
        if ( in_array( $cookie_lang, $supported_langs, true ) ) {
            return $cookie_lang;
        }
    }
    $country  = radiotheme_get_user_country();
    $lang_map = radiotheme_get_country_lang_map();
    if ( $country && isset( $lang_map[ $country ] ) ) {
        return $lang_map[ $country ];
    }
    if ( ! empty( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
        $accept = sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) );
        foreach ( explode( ',', $accept ) as $part ) {
            $short = substr( strtolower( trim( explode( ';', $part )[0] ) ), 0, 2 );
            if ( in_array( $short, $supported_langs, true ) ) {
                return $short;
            }
        }
    }
    return 'en';
}

/* GeoIP verisini JS'e aktar */
add_filter( 'radiotheme_js_data', function( $data ) {
    $data['userCountry'] = radiotheme_get_user_country();
    $data['userLang']    = radiotheme_get_user_language();
    $data['siteName']    = get_bloginfo( 'name' );
    return $data;
} );

add_action( 'wp_enqueue_scripts', function() {
    $extra_data = apply_filters( 'radiotheme_js_data', array() );
    if ( ! empty( $extra_data ) && wp_script_is( 'radiotheme-language-switcher', 'enqueued' ) ) {
        wp_add_inline_script(
            'radiotheme-language-switcher',
            'window.radioThemeData = Object.assign(window.radioThemeData || {}, ' . wp_json_encode( $extra_data ) . ');',
            'before'
        );
    }
}, 20 );

/*
 * Server-side GeoIP redirect DEVRE DIŞI.
 * Dil yönlendirmesi Polylang tarafından yönetilmektedir.
 * Bu redirect Polylang ile çakışarak /tr/ döngüsü oluşturuyordu.
 */
