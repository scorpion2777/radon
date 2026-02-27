/**
 * RadioTheme - ajax-navigation.js  v5.4-FIXED
 *
 * DÜZELTMELER v5.4:
 *  1. Ana sayfaya dönünce sayfa değişmiyordu:
 *     Sebep: go() sadece #main-content innerHTML'ini değiştiriyordu.
 *     Ama front-page.php'de #main-content class'ı "site-main" iken
 *     single sayfada "site-main station-detail-page".
 *     CSS layout farkları body/main class'a bağlıysa değişmez.
 *     Çözüm: className da güncelleniyor + body class da aktarılıyor.
 *
 *  2. Ana sayfaya dönünce RadioThemeFilter.init() çağrılıyor ama
 *     #radio-list-container yeni DOM'da olmadığı an (race condition).
 *     Çözüm: requestAnimationFrame ile bir tick sonra init() çağır.
 *
 *  3. data-stream-url olan linkler navigation'dan muaf.
 *  4. data-no-ajax olan linkler navigation'dan muaf.
 *  5. Geri butonu için replaceState her zaman yapılıyor.
 */

( function() {
    'use strict';

    var busy = false;

    function isInternal( href ) {
        if ( ! href ) return false;
        try {
            var u = new URL( href, location.href );
            return u.hostname === location.hostname
                && href.indexOf( '/wp-admin' ) === -1
                && href.indexOf( '/wp-login' ) === -1
                && href.charAt( 0 ) !== '#';
        } catch(e) { return false; }
    }

    /* -------------------------------------------------------
       Yükleme göstergesi (opsiyonel — body class ile CSS)
    ------------------------------------------------------- */
    function setLoading( on ) {
        document.body.classList.toggle( 'rt-navigating', on );
    }

    /* -------------------------------------------------------
       Ana navigasyon fonksiyonu
    ------------------------------------------------------- */
    function go( href, push ) {
        if ( busy ) return;
        if ( ! href ) return;
        busy = true;
        setLoading( true );

        fetch( href, { credentials: 'same-origin' } )
            .then( function( r ) {
                if ( ! r.ok ) throw new Error( r.status );
                return r.text();
            } )
            .then( function( html ) {
                var parser = new DOMParser();
                var doc    = parser.parseFromString( html, 'text/html' );

                /* 1. radioThemeData nonce'unu güncelle */
                doc.querySelectorAll( 'script:not([src])' ).forEach( function( s ) {
                    if ( s.textContent.indexOf( 'radioThemeData' ) === -1 ) return;
                    try {
                        var m = s.textContent.match( /var\s+radioThemeData\s*=\s*(\{[\s\S]*?\});/ );
                        if ( m ) {
                            window.radioThemeData = Object.assign(
                                window.radioThemeData || {},
                                JSON.parse( m[1] )
                            );
                        }
                    } catch(e) {}
                } );

                /* 2. #main-content içeriğini ve class'ını güncelle */
                var newMain = doc.querySelector( '#main-content' );
                var curMain = document.querySelector( '#main-content' );

                if ( newMain && curMain ) {
                    curMain.innerHTML  = newMain.innerHTML;
                    curMain.className  = newMain.className;
                    curMain.id         = newMain.id;   /* id her zaman aynı ama güvenlik için */
                }

                /* 3. body class'ını güncelle (WP body_class değişiyor) */
                var newBody = doc.querySelector( 'body' );
                if ( newBody ) {
                    /* player-bar, rt-navigating gibi dinamik class'ları koru */
                    var keepClasses = [];
                    [ 'rt-navigating', 'radio-player-bar--active' ].forEach( function( cls ) {
                        if ( document.body.classList.contains( cls ) ) keepClasses.push( cls );
                    } );

                    document.body.className = newBody.className;

                    keepClasses.forEach( function( cls ) {
                        document.body.classList.add( cls );
                    } );
                }

                /* 4. Başlık */
                document.title = doc.title;

                /* 5. URL geçmişi */
                if ( push ) {
                    history.pushState( { href: href }, '', href );
                }

                /* 6. Scroll sıfırla */
                window.scrollTo( 0, 0 );

                /* 7. Filter + player senkronizasyonu — bir tick sonra
                   (innerHTML atanması renderlanana kadar bekle) */
                requestAnimationFrame( function() {
                    if ( window.RadioThemeFilter ) {
                        window.RadioThemeFilter.init();
                    }

                    /* Yeni sayfanın ülke kodunu main'den oku */
                    var newMain = document.querySelector( '#main-content' );
                    var newCountryCode = newMain ? ( newMain.dataset.countryCode || '' ) : '';
                    var newCountrySlug = newMain ? ( newMain.dataset.countrySlug || '' ) : '';

                    /* radioThemeData'yı güncelle — AJAX filtre doğru ülkede çalışsın */
                    if ( window.radioThemeData ) {
                        window.radioThemeData.countryCode = newCountryCode;
                        window.radioThemeData.countrySlug = newCountrySlug;
                    }

                    /* Header bayrak + genres linki güncelle */
                    updateHeader( newCountryCode, newCountrySlug, href );

                    document.dispatchEvent( new CustomEvent( 'rt:navigation-done', {
                        detail: { href: href, countryCode: newCountryCode, countrySlug: newCountrySlug }
                    } ) );
                } );
            } )
            .catch( function() {
                /* Fetch başarısız → normal navigasyon */
                location.href = href;
            } )
            .finally( function() {
                busy = false;
                setLoading( false );
            } );
    }

    /* -------------------------------------------------------
       Link click delegation
    ------------------------------------------------------- */
    document.addEventListener( 'click', function( e ) {
        /* --- Normal link navigasyonu --- */
        var a = e.target.closest( 'a[href]' );
        if ( ! a ) return;

        var href = a.getAttribute( 'href' );
        if ( ! isInternal( href ) )                    return;
        if ( a.getAttribute( 'target' ) === '_blank' ) return;
        if ( a.hasAttribute( 'download' ) )            return;
        if ( e.metaKey || e.ctrlKey || e.shiftKey )    return;
        if ( a.dataset.streamUrl )                     return;
        if ( a.hasAttribute( 'data-no-ajax' ) )        return;

        e.preventDefault();
        go( href, true );
    } );

    /* -------------------------------------------------------
       Geri / İleri butonu
    ------------------------------------------------------- */
    window.addEventListener( 'popstate', function( e ) {
        var href = ( e.state && e.state.href ) ? e.state.href : location.href;
        go( href, false );
    } );

    /* -------------------------------------------------------
       Header bayrak + genres linki güncelle
    ------------------------------------------------------- */
    function updateHeader( cc, slug, href ) {
        var flagEl    = document.getElementById( 'header-country-flag' );
        var nameEl    = document.getElementById( 'header-country-name' );
        var infoEl    = document.getElementById( 'header-country-info' );
        var genresEl  = document.getElementById( 'header-genres-link' );

        if ( ! cc || ! slug ) {
            /* Ülkesiz sayfa (örn. /genres/) — değişiklik yapma */
            return;
        }

        var countryName = slug.replace( /-/g, ' ' ).replace( /\b\w/g, function(c){ return c.toUpperCase(); } );
        var base = window.location.origin;

        /* Bayrak: radioThemeData'daki flag map'ten al, yoksa boş bırak */
        var flagMap = ( window.radioThemeData && window.radioThemeData.flagMap ) ? window.radioThemeData.flagMap : {};
        var flag = flagMap[ cc.toLowerCase() ] || ( flagEl ? flagEl.dataset.baseFlag || '' : '' );

        if ( flagEl ) flagEl.textContent = flag;
        if ( infoEl ) infoEl.href = base + '/' + cc.toLowerCase() + '/';

        /* Genres linki: /de/genres/ */
        if ( genresEl ) {
            genresEl.href = base + '/' + cc.toLowerCase() + '/genres/';
        }
    }

    /* -------------------------------------------------------
       İlk state — geri butonunun ilk sayfaya dönebilmesi için
    ------------------------------------------------------- */
    history.replaceState( { href: location.href }, '', location.href );

    /* rt:navigate eventi - player.js kart tiklamasindan gelir, audio durmadan sayfa degisir */
    document.addEventListener( 'rt:navigate', function( e ) {
        if ( e.detail && e.detail.href ) {
            go( e.detail.href, true );
        }
    } );

} )();
