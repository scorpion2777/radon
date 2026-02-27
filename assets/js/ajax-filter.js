/**
 * RadioTheme - ajax-filter.js  v6.1
 *
 * SEO & UX odaklı sayfalandırma:
 *  - Her sayfa/filtre değişiminde URL güncellenir (history.pushState)
 *  - URL parametreleri: ?genre=jazz&sort=popularity&page=2
 *  - Tarayıcı geri/ileri tuşu çalışır (popstate)
 *  - Sayfa yüklendiğinde URL'deki parametreler okunarak doğru içerik yüklenir
 *  - Sayfa başına 20 radyo
 */

( function() {
    'use strict';

    var SEARCH_DELAY = 350;   // ms — arama debounce

    var searchTimer = null;
    var state       = null;   // null ise anasayfada değiliz
    var eventsBound = false;
    var skipPush    = false;  // popstate sırasında pushState yapılmasın

    /* =========================================================
       URL YARDIMCI FONKSİYONLAR
    ========================================================= */

    /**
     * Breadcrumb'ı genre/city state'e göre günceller.
     */
    function syncBreadcrumb() {
        if ( ! state ) return;

        var nav = document.querySelector( '.site-main > .breadcrumb-nav' );
        if ( ! nav ) return;

        var cc          = state.countryCode || '';
        var countrySlug = state.country     || '';
        var flagMap     = ( window.radioThemeData && window.radioThemeData.flagMap ) || {};
        var homeUrl     = ( window.radioThemeData && window.radioThemeData.homeUrl ) || '';

        if ( ! cc || ! countrySlug ) return;

        /* Capitalize: "united-kingdom" -> "United Kingdom" */
        var countryName = countrySlug.replace( /-/g, ' ' ).split(' ').map( function(w) {
            return w.charAt(0).toUpperCase() + w.slice(1);
        } ).join(' ');
        var flag       = flagMap[ cc.toLowerCase() ] || '';
        var countryUrl = homeUrl + '/' + cc + '/';
        var titleEl    = document.querySelector( '.radio-list-header .section-title' );

        /* All secili (genre/city yok) - breadcrumb gizle, basligi sifirla */
        if ( ! state.genre && ! state.city ) {
            nav.style.display = 'none';
            if ( titleEl && countryName ) {
                titleEl.textContent = countryName + ' Radio Stations';
            }
            return;
        }

        nav.style.display = '';

        /* Gorunen genre adini chipten al */
        var lastLabel = '';
        if ( state.genre ) {
            var chip = document.querySelector( '.filter-chip[data-filter="genre"][data-value="' + state.genre + '"]' );
            lastLabel = chip ? chip.textContent.replace( /\s*\(.*\)\s*$/, '' ).trim() : state.genre;
        }

        nav.innerHTML =
            '<ol class="breadcrumb-list">' +
                '<li class="breadcrumb-item">' +
                    '<a href="' + countryUrl + '" class="breadcrumb-link">' +
                        ( flag ? '<span class="breadcrumb-flag" aria-hidden="true">' + flag + '</span> ' : '' ) +
                        countryName +
                    '</a>' +
                '</li>' +
                '<li class="breadcrumb-item breadcrumb-current" aria-current="page">' +
                    lastLabel +
                '</li>' +
            '</ol>';

        /* Basligi guncelle */
        if ( titleEl ) {
            titleEl.textContent = countryName + ' — ' + lastLabel;
        }
    }

    /**
     * Mevcut URL'den filtre parametrelerini okur.
     * ?genre=jazz&country=tr&sort=name&page=3&search=trt
     */
    function readUrlParams() {
        var sp = new URLSearchParams( location.search );
        return {
            page:    parseInt( sp.get( 'page' ) || '1', 10 ),
            genre:   sp.get( 'genre' )   || '',
            country: sp.get( 'country' ) || '',
            sort:    sp.get( 'sort' )    || 'popularity',
            search:  sp.get( 'search' )  || '',
        };
    }

    /**
     * State'i URL'e yazar.
     * Varsayılan değerler URL'den çıkarılır (temiz URL).
     * push=true → pushState, push=false → replaceState
     */
    function syncUrl( push ) {
        if ( ! state ) return;

        var sp = new URLSearchParams();

        /* Yalnızca varsayılandan farklı değerleri ekle (temiz URL).
           country URL path'inde (/tr/) zaten var — querystring'e ekleme.
           genre /genre/slug/ şeklinde path'te varsa querystring'e ekleme. */
        if ( state.genre && ! /\/genre\/[^/]+\//.test( location.pathname ) ) sp.set( 'genre', state.genre );
        if ( state.sort && state.sort !== 'popularity' ) sp.set( 'sort', state.sort );
        if ( state.search )                            sp.set( 'search', state.search );
        if ( state.page > 1 )                          sp.set( 'page',   state.page );

        var qs      = sp.toString();
        var newUrl  = location.pathname + ( qs ? '?' + qs : '' );
        var curUrl  = location.pathname + location.search;

        /* Aynıysa dokunma */
        if ( newUrl === curUrl ) return;

        if ( push ) {
            history.pushState( { rtFilter: true }, '', newUrl );
        } else {
            history.replaceState( { rtFilter: true }, '', newUrl );
        }
    }

    /* =========================================================
       WP VERİSİ
    ========================================================= */
    function cfg() {
        var d = window.radioThemeData || {};
        return {
            ajaxUrl: d.ajaxUrl || '/wp-admin/admin-ajax.php',
            nonce:   d.nonce   || '',
        };
    }

    /* =========================================================
       INIT
       ajax-navigation.js her navigasyon sonrasında çağırır.
       popstate da çağırır.
    ========================================================= */
    function init() {
        var container = document.getElementById( 'radio-list-container' );

        if ( ! container ) {
            state = null;
            hidePagination();
            return;
        }

        /* URL'deki parametreleri oku */
        var params = readUrlParams();

        /* Ülke kodunu container'dan oku (front-page.php data-country-code yazar) */
        /* showAll=true ise tüm ülkeler — countryCode/countrySlug boş kalmalı */
        var _showAll    = window.radioThemeData && window.radioThemeData.showAll;
        var countryCode = _showAll ? '' : ( container.dataset.countryCode
                       || ( window.radioThemeData && window.radioThemeData.countryCode )
                       || '' );
        var countrySlug = _showAll ? '' : ( container.dataset.country
                       || ( window.radioThemeData && window.radioThemeData.countrySlug )
                       || params.country
                       || '' );

        state = {
            page:        params.page,
            totalPages:  1,
            /* Önce URL param, sonra taxonomy sayfasından gelen activeGenre */
            genre:       params.genre
                      || ( window.radioThemeData && window.radioThemeData.activeGenre )
                      || container.dataset.genre
                      || '',
            country:     countrySlug,      /* taxonomy slug (turkey) */
            countryCode: countryCode,      /* ISO kodu (tr) */
            sort:        params.sort,
            search:      params.search,
            isLoading:   false,
        };

        /* Filtre UI'ını URL parametrelerine göre senkronize et */
        syncFilterUi();

        /* İlk yükleme — URL zaten doğru, pushState yapma */
        load( state.page, false );

        if ( ! eventsBound ) {
            bindEvents();
            eventsBound = true;
        }
    }

    /**
     * Filtre chip'lerini, sort select'i ve search input'u
     * mevcut state ile senkronize eder.
     * (Örn: /anasayfa?genre=jazz açıldığında jazz chip aktif görünsün)
     */
    function syncFilterUi() {
        if ( ! state ) return;

        /* Genre chip'leri */
        document.querySelectorAll( '.filter-chip[data-filter="genre"]' ).forEach( function( c ) {
            var active = ( c.dataset.value === state.genre );
            c.classList.toggle( 'is-active', active );
            c.setAttribute( 'aria-pressed', active ? 'true' : 'false' );
        } );

        /* Country chip'leri */
        document.querySelectorAll( '.filter-chip[data-filter="country"]' ).forEach( function( c ) {
            var active = ( c.dataset.value === state.country );
            c.classList.toggle( 'is-active', active );
            c.setAttribute( 'aria-pressed', active ? 'true' : 'false' );
        } );

        /* Sort select */
        var sortEl = document.getElementById( 'station-sort' );
        if ( sortEl ) sortEl.value = state.sort;

        /* Search input */
        var searchEl = document.querySelector( '.station-search-input' );
        if ( searchEl ) searchEl.value = state.search;
    }

    /* =========================================================
       LOAD — AJAX isteği
       pushUrl: true → pushState, false → replaceState (veya değiştirme)
    ========================================================= */
    function load( page, pushUrl ) {
        if ( ! state ) return;
        if ( state.isLoading ) return;

        var container = document.getElementById( 'radio-list-container' );
        var spinner   = document.getElementById( 'radio-list-spinner' );
        var countEl   = document.getElementById( 'visible-station-count' );
        if ( ! container ) return;

        state.isLoading = true;
        state.page      = page;

        /* URL'i güncelle */
        if ( ! skipPush ) {
            syncUrl( pushUrl !== false );
        }

        /* Breadcrumb'ı güncelle */
        syncBreadcrumb();

        if ( spinner ) spinner.style.display = 'flex';
        hidePagination();

        /* Sayfa 1'den farklıysa listeye kaydır */
        if ( page > 1 ) {
            var section = container.closest( '.radio-list-column' );
            if ( section ) {
                section.scrollIntoView( { behavior: 'smooth', block: 'start' } );
            }
        }

        var c  = cfg();
        var fd = new FormData();
        fd.append( 'action',       'load_radio_stations' );
        fd.append( 'nonce',        c.nonce );
        fd.append( 'page',         state.page );
        fd.append( 'genre',        state.genre );
        fd.append( 'country',      state.country );
        fd.append( 'country_code', state.countryCode || '' );
        fd.append( 'sort',         state.sort );
        fd.append( 'search',       state.search );
        fd.append( 'lang',         '' );

        fetch( c.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' } )
            .then( function( r ) { return r.json(); } )
            .then( function( data ) {
                state.isLoading = false;
                if ( spinner ) spinner.style.display = 'none';
                if ( ! data.success ) return;

                var res = data.data;

                container.innerHTML = res.html || '';
                state.totalPages    = res.total_pages || 1;

                if ( countEl ) countEl.textContent = ( res.total_results || 0 ).toLocaleString();

                if ( ! res.total_results ) {
                    container.innerHTML =
                        '<div style="padding:2rem;text-align:center;color:var(--color-text-muted)">' +
                        ( ( window.radioThemeData && window.radioThemeData.noResultsText ) || 'No stations found.' ) +
                        '</div>';
                    hidePagination();
                } else {
                    renderPagination( state.page, state.totalPages );
                }

                if ( window.RadioThemePlayer && window.RadioThemePlayer.syncButtons ) {
                    window.RadioThemePlayer.syncButtons();
                }
            } )
            .catch( function() {
                if ( state ) state.isLoading = false;
                if ( spinner ) spinner.style.display = 'none';
                var c2 = document.getElementById( 'radio-list-container' );
                if ( c2 ) {
                    c2.innerHTML = '<div style="padding:2rem;text-align:center">Error loading stations. Please refresh.</div>';
                }
                hidePagination();
            } );
    }

    /* =========================================================
       PAGINATION RENDER
    ========================================================= */
    function renderPagination( current, total ) {
        var nav     = document.getElementById( 'radio-pagination' );
        var numbers = document.getElementById( 'pagination-numbers' );
        var prev    = document.getElementById( 'pagination-prev' );
        var next    = document.getElementById( 'pagination-next' );

        if ( ! nav || ! numbers || total <= 1 ) {
            hidePagination();
            return;
        }

        nav.style.display = 'flex';
        prev.disabled     = ( current <= 1 );
        next.disabled     = ( current >= total );

        numbers.innerHTML = '';

        getPageRange( current, total ).forEach( function( p ) {
            if ( p === '...' ) {
                var ellipsis       = document.createElement( 'span' );
                ellipsis.className = 'pagination-ellipsis';
                ellipsis.textContent = '…';
                numbers.appendChild( ellipsis );
            } else {
                var btn = document.createElement( 'button' );
                btn.className    = 'pagination-btn pagination-number' + ( p === current ? ' is-active' : '' );
                btn.textContent  = p;
                btn.setAttribute( 'data-page', p );
                btn.setAttribute( 'aria-current', p === current ? 'page' : 'false' );
                btn.setAttribute( 'role', 'listitem' );
                if ( p === current ) btn.disabled = true;
                numbers.appendChild( btn );
            }
        } );
    }

    function getPageRange( current, total ) {
        if ( total <= 7 ) {
            var all = [];
            for ( var i = 1; i <= total; i++ ) all.push( i );
            return all;
        }
        var range = [];
        var left  = Math.max( 2, current - 1 );
        var right = Math.min( total - 1, current + 1 );
        range.push( 1 );
        if ( left > 2 )          range.push( '...' );
        for ( var j = left; j <= right; j++ ) range.push( j );
        if ( right < total - 1 ) range.push( '...' );
        range.push( total );
        return range;
    }

    function hidePagination() {
        var nav = document.getElementById( 'radio-pagination' );
        if ( nav ) nav.style.display = 'none';
    }

    /* =========================================================
       BIND EVENTS — document üzerinde tek sefer
    ========================================================= */
    function bindEvents() {

        /* ---- Click delegation ---- */
        document.addEventListener( 'click', function( e ) {
            if ( ! state ) return;

            /* Pagination prev */
            if ( e.target && e.target.id === 'pagination-prev' ) {
                if ( state.page > 1 ) load( state.page - 1, true );
                return;
            }

            /* Pagination next */
            if ( e.target && e.target.id === 'pagination-next' ) {
                if ( state.page < state.totalPages ) load( state.page + 1, true );
                return;
            }

            /* Pagination sayfa numarası */
            var numBtn = e.target.closest( '.pagination-number' );
            if ( numBtn ) {
                var p = parseInt( numBtn.getAttribute( 'data-page' ), 10 );
                if ( p && p !== state.page ) load( p, true );
                return;
            }

            /* Filter chip */
            var chip = e.target.closest( '.filter-chip' );
            if ( chip ) {
                var type = chip.dataset.filter;
                var val  = chip.dataset.value;

                if ( type === 'genre' ) {
                    /* Genre chip → AJAX navigation ile temiz URL'e git */
                    var cc      = state.countryCode || ( window.radioThemeData && window.radioThemeData.countryCode ) || '';
                    var homeUrl = ( window.radioThemeData && window.radioThemeData.homeUrl ) || '';

                    if ( val && cc ) {
                        /* Belirli bir genre seçildi → /de/genre/rock/ */
                        var targetUrl = homeUrl + '/' + cc + '/genre/' + val + '/';
                        document.dispatchEvent( new CustomEvent( 'rt:navigate', { detail: { href: targetUrl } } ) );
                    } else if ( cc ) {
                        /* All seçildi → /de/ ülke ana sayfasına dön */
                        var homeTarget = homeUrl + '/' + cc + '/';
                        document.dispatchEvent( new CustomEvent( 'rt:navigate', { detail: { href: homeTarget } } ) );
                    }
                    return;
                }

                if ( type === 'country' ) state.country = val;
                load( 1, true );
                return;
            }

            /* Sidebar genre cloud linkleri */
            var genreTag = e.target.closest( '.genre-cloud-tag' );
            if ( genreTag ) {
                e.preventDefault();
                var genreVal = genreTag.dataset.genre || '';
                if ( ! genreVal ) return;

                var cc2      = state.countryCode || ( window.radioThemeData && window.radioThemeData.countryCode ) || '';
                var homeUrl2 = ( window.radioThemeData && window.radioThemeData.homeUrl ) || '';

                if ( cc2 ) {
                    var tagUrl = homeUrl2 + '/' + cc2 + '/genre/' + genreVal + '/';
                    document.dispatchEvent( new CustomEvent( 'rt:navigate', { detail: { href: tagUrl } } ) );
                }
                return;
            }
        } );

        /* ---- Sort select ---- */
        document.addEventListener( 'change', function( e ) {
            if ( ! state ) return;
            if ( e.target && e.target.id === 'station-sort' ) {
                state.sort = e.target.value;
                load( 1, true );
            }
        } );

        /* ---- Search input ---- */
        document.addEventListener( 'input', function( e ) {
            if ( ! state ) return;
            if ( e.target && e.target.classList.contains( 'station-search-input' ) ) {
                clearTimeout( searchTimer );
                var val = e.target.value.trim();
                searchTimer = setTimeout( function() {
                    state.search = val;
                    /* Arama URL'e yansır ama replaceState ile (pushState spam olmasın) */
                    load( 1, false );
                }, SEARCH_DELAY );
            }
        } );

        document.addEventListener( 'keydown', function( e ) {
            if ( ! state ) return;
            if ( ! e.target || ! e.target.classList.contains( 'station-search-input' ) ) return;
            if ( e.key === 'Escape' ) {
                e.target.value = '';
                state.search   = '';
                load( 1, true );
            }
            if ( e.key === 'Enter' ) {
                clearTimeout( searchTimer );
                state.search = e.target.value.trim();
                load( 1, true );
            }
        } );

        /* ---- Popstate — tarayıcı geri/ileri ---- */
        window.addEventListener( 'popstate', function() {
            /* Sadece anasayfadaysak (radio-list-container var) */
            if ( ! document.getElementById( 'radio-list-container' ) ) return;

            /* URL'deki parametreleri state'e aktar */
            var params   = readUrlParams();
            state.page    = params.page;
            state.genre   = params.genre;
            state.country = params.country;
            state.sort    = params.sort;
            state.search  = params.search;

            syncFilterUi();

            /* URL zaten doğru — pushState yapma */
            skipPush = true;
            load( state.page, false );
            skipPush = false;
        } );
    }

    /* =========================================================
       BOOT
    ========================================================= */
    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', init );
    } else {
        init();
    }

    window.RadioThemeFilter = { init: init };

} )();
