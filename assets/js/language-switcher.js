/**
 * RadioTheme - language-switcher.js
 * Language switcher dropdown toggle
 * GeoIP-based auto redirect on first visit
 */

( function() {
    'use strict';

    /* ============================================================
       LANGUAGE SWITCHER DROPDOWN
       ============================================================ */
    function initLanguageSwitcher() {
        const switcher = document.getElementById( 'language-switcher' );
        const toggle   = document.getElementById( 'language-switcher-btn' );
        const dropdown = document.getElementById( 'language-dropdown' );

        if ( ! switcher || ! toggle || ! dropdown ) return;

        // Toggle dropdown on button click
        toggle.addEventListener( 'click', function( e ) {
            e.stopPropagation();
            const isOpen = switcher.classList.toggle( 'is-open' );
            toggle.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
        } );

        // Close on outside click
        document.addEventListener( 'click', function( e ) {
            if ( ! switcher.contains( e.target ) ) {
                switcher.classList.remove( 'is-open' );
                toggle.setAttribute( 'aria-expanded', 'false' );
            }
        } );

        // Close on Escape key
        document.addEventListener( 'keydown', function( e ) {
            if ( e.key === 'Escape' ) {
                switcher.classList.remove( 'is-open' );
                toggle.setAttribute( 'aria-expanded', 'false' );
                toggle.focus();
            }
        } );

        // Handle language option clicks (for non-WPML fallback)
        dropdown.querySelectorAll( '.language-option[data-lang]' ).forEach( function( option ) {
            option.addEventListener( 'click', function( e ) {
                e.preventDefault();
                const lang = this.dataset.lang;
                if ( lang ) {
                    setLanguageCookie( lang );
                    // Reload page with new language path
                    const newUrl = buildLanguageUrl( lang );
                    window.location.href = newUrl;
                }
            } );
        } );
    }

    /* ============================================================
       LANGUAGE URL BUILDER
       Constructs proper URL for language switch
       ============================================================ */
    function buildLanguageUrl( lang ) {
        const baseUrl   = window.radioThemeData?.homeUrl || window.location.origin;
        const path      = window.location.pathname;
        const search    = window.location.search;

        // Supported language codes
        const langCodes = [ 'tr', 'de', 'fr', 'es', 'ar', 'pt', 'ru', 'pl' ];

        // Remove existing language prefix from path
        let cleanPath = path;
        langCodes.forEach( function( code ) {
            const re = new RegExp( '^\\/' + code + '\\/' );
            if ( re.test( cleanPath ) ) {
                cleanPath = cleanPath.replace( re, '/' );
            }
        } );

        // English is default (no prefix)
        if ( lang === 'en' ) {
            return window.location.origin + cleanPath + search;
        }

        // Add language prefix
        return window.location.origin + '/' + lang + cleanPath + search;
    }

    /* ============================================================
       COOKIE HELPERS
       ============================================================ */
    function setLanguageCookie( lang ) {
        const expiry = new Date();
        expiry.setFullYear( expiry.getFullYear() + 1 );
        document.cookie = 'radio_lang=' + encodeURIComponent( lang ) +
            '; expires=' + expiry.toUTCString() +
            '; path=/; SameSite=Lax';
    }

    function getLanguageCookie() {
        const match = document.cookie.match( /(?:^|;\s*)radio_lang=([^;]*)/ );
        return match ? decodeURIComponent( match[ 1 ] ) : null;
    }

    /* ============================================================
       GEOIP AUTO-REDIRECT
       Only runs on first visit (no cookie set)
       Only redirects when there is NO language prefix in URL
       ============================================================ */
    function initGeoipRedirect() {
        // Supported languages by country code
        const countryLangMap = {
            TR: 'tr',
            DE: 'de', AT: 'de', CH: 'de',
            FR: 'fr', BE: 'fr',
            ES: 'es', MX: 'es', AR: 'es', CL: 'es', CO: 'es', PE: 'es',
            SA: 'ar', AE: 'ar', EG: 'ar', MA: 'ar', DZ: 'ar',
            BR: 'pt', PT: 'pt',
            RU: 'ru', BY: 'ru', KZ: 'ru',
            PL: 'pl',
        };

        // Language codes list
        const langCodes = [ 'tr', 'de', 'fr', 'es', 'ar', 'pt', 'ru', 'pl' ];

        // Check if user already has a language preference
        const savedLang = getLanguageCookie();
        if ( savedLang ) return;

        // Check if URL already has a language prefix
        const hasLangPrefix = langCodes.some( function( code ) {
            return window.location.pathname.startsWith( '/' + code + '/' ) ||
                   window.location.pathname === '/' + code;
        } );
        if ( hasLangPrefix ) return;

        // Get country from Cloudflare header (passed via PHP to page)
        const userCountry = window.radioThemeData?.userCountry || '';

        if ( userCountry && countryLangMap[ userCountry ] ) {
            const targetLang = countryLangMap[ userCountry ];

            // Save preference
            setLanguageCookie( targetLang );

            // Redirect (302-style behavior in JS)
            const newUrl = buildLanguageUrl( targetLang );

            // Only redirect if different from current
            if ( newUrl !== window.location.href ) {
                window.location.replace( newUrl );
            }
        } else {
            // Default: English, save to cookie so we don't check again
            setLanguageCookie( 'en' );
        }
    }

    /* ============================================================
       BOOT
       ============================================================ */
    function init() {
        initLanguageSwitcher();
        initGeoipRedirect();
    }

    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', init );
    } else {
        init();
    }

} )();
