<?php
/**
 * RadioTheme — front-page.php
 * Ülke bazlı anasayfa. rt_country query var'ı varsa o ülkeye göre filtreler.
 * URL: /s/tr/  →  Türkiye kanalları
 * URL: /s/     →  IP tespiti → yönlendirilir (country-router.php yapar)
 */

get_header();

/* ── Ülke bilgisi ── */
$url_cc      = get_query_var( 'rt_country', '' );           // URL'den: 'tr'
$country_slug = $url_cc ? radiotheme_iso_to_slug( $url_cc ) : ''; // 'turkey'
$country_name = $country_slug ? ucwords( str_replace( '-', ' ', $country_slug ) ) : '';

/* ?all=1 varsa ülke filtresi sıfırla — "All Countries" sayfası */
$show_all = ! empty( $_GET['all'] );
if ( $show_all ) {
    $url_cc       = '';
    $country_slug = '';
    $country_name = '';
}

/* ── Filtreler ── */
// /de/genre/90s/ gibi taxonomy URL'den genre'yi oku
$queried_genre = get_query_var( 'radio-genre', '' );
$active_genre  = $queried_genre
    ? $queried_genre
    : ( isset( $_GET['genre'] ) ? sanitize_text_field( wp_unslash( $_GET['genre'] ) ) : '' );

// /de/city/berlin/ gibi taxonomy URL'den city'yi oku
$queried_city  = get_query_var( 'radio-city', '' );
$active_sort   = isset( $_GET['sort'] )   ? sanitize_text_field( wp_unslash( $_GET['sort'] ) )   : 'popularity';

/* ── Genre listesi — ülkeye göre filtreli ── */
$genre_args = [
    'taxonomy'   => 'radio-genre',
    'orderby'    => 'count',
    'order'      => 'DESC',
    'hide_empty' => true,
    'number'     => 12,
];
if ( $country_slug ) {
    // Sadece bu ülkedeki istasyonlarda bulunan genre'ler (cache'li)
    $genre_args['object_ids'] = radiotheme_get_station_ids_by_country( $country_slug );
}
$genres = get_terms( $genre_args );

/* ── İstasyon sayısı ── */
$count_args = [
    'post_type'   => 'radio-station',
    'post_status' => 'publish',
    'lang'        => '',
];
if ( $country_slug ) {
    $count_args['tax_query'] = [ [
        'taxonomy' => 'radio-country',
        'field'    => 'slug',
        'terms'    => $country_slug,
    ] ];
}
$total_stations = ( new WP_Query( array_merge( $count_args, [ 'posts_per_page' => 1, 'no_found_rows' => false ] ) ) )->found_posts;

/* ── Bayrak ve ülke flag ── */
$flag = $url_cc ? radiotheme_iso_to_flag_emoji( strtoupper( $url_cc ) ) : '';

/* ── Modal için: sitede kayıtlı ülkeleri al ── */
$all_countries_terms = get_terms( [
    'taxonomy'   => 'radio-country',
    'orderby'    => 'name',
    'order'      => 'ASC',
    'hide_empty' => true,
    'number'     => 0,
] );
$modal_countries = [];
if ( ! is_wp_error( $all_countries_terms ) && ! empty( $all_countries_terms ) ) {
    foreach ( $all_countries_terms as $term ) {
        $iso  = radiotheme_slug_to_iso( $term->slug );
        if ( ! $iso ) continue;
        $iso  = strtolower( $iso );
        $modal_countries[] = [
            'iso'   => $iso,
            'flag'  => radiotheme_iso_to_flag_emoji( strtoupper( $iso ) ),
            'name'  => ucwords( str_replace( '-', ' ', $term->slug ) ),
            'url'   => home_url( '/' . $iso . '/' ),
            'count' => $term->count,
        ];
    }
}
?>

<main class="site-main<?php echo $url_cc ? ' country-page' : ''; ?>" id="main-content"
      data-country-code="<?php echo esc_attr( $url_cc ); ?>"
      data-country-slug="<?php echo esc_attr( $country_slug ); ?>">

    <!-- ═══ ÜLKE SEÇİCİ MODAL ═══ -->
    <?php if ( ! empty( $modal_countries ) ) : ?>
    <div id="country-picker" class="country-picker-overlay" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Select Country', 'radiotheme' ); ?>" hidden>
        <div class="country-picker-dialog">
            <div class="country-picker-header">
                <h2 class="country-picker-title"><?php esc_html_e( 'Select Country', 'radiotheme' ); ?></h2>
                <button class="country-picker-close" aria-label="<?php esc_attr_e( 'Close', 'radiotheme' ); ?>">
                    <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M5 5l10 10M15 5L5 15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                </button>
            </div>
            <div class="country-picker-search-wrap">
                <input type="search" class="country-picker-search" placeholder="<?php esc_attr_e( 'Search country\xe2\x80\xa6', 'radiotheme' ); ?>" autocomplete="off">
            </div>
            <ul class="country-picker-list" role="listbox">
                <?php foreach ( $modal_countries as $mc ) : ?>
                <li class="country-picker-item<?php echo ( $mc['iso'] === $url_cc ) ? ' is-active' : ''; ?>" role="option" aria-selected="<?php echo ( $mc['iso'] === $url_cc ) ? 'true' : 'false'; ?>">
                    <a href="<?php echo esc_url( $mc['url'] ); ?>" class="country-picker-link" data-no-ajax="1">
                        <span class="country-picker-flag" aria-hidden="true"><?php echo esc_html( $mc['flag'] ); ?></span>
                        <span class="country-picker-name"><?php echo esc_html( $mc['name'] ); ?></span>
                        <span class="country-picker-count"><?php echo esc_html( $mc['count'] ); ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <?php
    /* Breadcrumb — sadece genre veya city alt sayfasındayken göster */
    if ( function_exists( 'radiotheme_render_breadcrumb' ) && $url_cc && $country_name && ( $queried_genre || $queried_city ) ) :
        $bc_flag  = function_exists( 'radiotheme_iso_to_flag_emoji' )
            ? radiotheme_iso_to_flag_emoji( strtoupper( $url_cc ) )
            : '';
        $bc_items = [
            [ 'label' => $country_name, 'url' => home_url( '/' . $url_cc . '/' ), 'flag' => $bc_flag ],
        ];
        if ( $queried_genre ) {
            $genre_term  = get_term_by( 'slug', $queried_genre, 'radio-genre' );
            $genre_label = $genre_term ? $genre_term->name : ucfirst( $queried_genre );
            $bc_items[]  = [ 'label' => $genre_label, 'url' => '' ];
        } elseif ( $queried_city ) {
            $city_term   = get_term_by( 'slug', $queried_city, 'radio-city' );
            $city_label  = $city_term ? $city_term->name : ucfirst( $queried_city );
            $bc_items[]  = [ 'label' => $city_label, 'url' => '' ];
        }
        radiotheme_render_breadcrumb( $bc_items );
    endif;
    ?>

    <!-- Filter Bar -->
    <div class="filter-bar" role="search" aria-label="<?php esc_attr_e( 'Filter radio stations', 'radiotheme' ); ?>">

        <span class="filter-bar-label" aria-hidden="true"><?php esc_html_e( 'Genre', 'radiotheme' ); ?></span>

        <div class="filter-chips" role="group" aria-label="<?php esc_attr_e( 'Filter by genre', 'radiotheme' ); ?>">
            <button class="filter-chip <?php echo empty( $active_genre ) ? 'is-active' : ''; ?>"
                    data-filter="genre" data-value=""
                    aria-pressed="<?php echo empty( $active_genre ) ? 'true' : 'false'; ?>">
                <?php esc_html_e( 'All', 'radiotheme' ); ?>
            </button>
            <?php if ( ! is_wp_error( $genres ) && ! empty( $genres ) ) :
                foreach ( $genres as $genre ) :
                    /* Genre URL'i ülke kodlu olmalı */
                    $genre_url = $url_cc
                        ? radiotheme_genre_url( $genre->slug, $url_cc )
                        : get_term_link( $genre );
                    ?>
                <button class="filter-chip <?php echo ( $active_genre === $genre->slug ) ? 'is-active' : ''; ?>"
                        data-filter="genre"
                        data-value="<?php echo esc_attr( $genre->slug ); ?>"
                        data-url="<?php echo esc_url( $genre_url ); ?>"
                        aria-pressed="<?php echo ( $active_genre === $genre->slug ) ? 'true' : 'false'; ?>">
                    <?php echo esc_html( $genre->name ); ?>
                    <span class="filter-count">(<?php echo esc_html( radiotheme_genre_count_for_country( (int) $genre->term_id, $country_slug ) ); ?>)</span>
                </button>
            <?php endforeach; endif; ?>
        </div>

        <select class="sort-select" id="station-sort" aria-label="<?php esc_attr_e( 'Sort stations by', 'radiotheme' ); ?>">
            <option value="popularity" <?php selected( $active_sort, 'popularity' ); ?>><?php esc_html_e( 'Most Popular', 'radiotheme' ); ?></option>
            <option value="name"       <?php selected( $active_sort, 'name' ); ?>><?php esc_html_e( 'Name A–Z', 'radiotheme' ); ?></option>
            <option value="newest"     <?php selected( $active_sort, 'newest' ); ?>><?php esc_html_e( 'Newest First', 'radiotheme' ); ?></option>
        </select>

    </div><!-- .filter-bar -->

    <!-- Three Column Layout -->
    <div class="content-area">

        <!-- ====================================================
             SOL KOLON: Radyo listesi
             ==================================================== -->
        <section class="radio-list-column" aria-label="<?php esc_attr_e( 'Radio stations list', 'radiotheme' ); ?>">

            <div class="radio-list-header">
                <h2 class="section-title">
                    <?php
                    if ( ! empty( $active_genre ) && $country_name ) {
                        printf( esc_html__( '%1$s — %2$s', 'radiotheme' ), esc_html( $country_name ), esc_html( ucfirst( $active_genre ) ) );
                    } elseif ( ! empty( $active_genre ) ) {
                        printf( esc_html__( '%s Radio Stations', 'radiotheme' ), esc_html( ucfirst( $active_genre ) ) );
                    } elseif ( $country_name ) {
                        printf( esc_html__( '%s Radio Stations', 'radiotheme' ), esc_html( $country_name ) );
                    } else {
                        esc_html_e( 'Live Radio Stations', 'radiotheme' );
                    }
                    ?>
                </h2>
                <p class="radio-list-count" id="radio-list-count" aria-live="polite">
                    <span id="visible-station-count"><?php echo esc_html( $total_stations ); ?></span>
                    <?php esc_html_e( 'stations', 'radiotheme' ); ?>
                </p>
            </div>

            <!-- İstasyon Arama Kutusu -->
            <div class="station-search-wrap" role="search">
                <svg class="search-icon" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <circle cx="9" cy="9" r="6" stroke="currentColor" stroke-width="1.5"/>
                    <path d="M13.5 13.5L17 17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                <input
                    type="search"
                    id="station-search-input"
                    class="station-search-input"
                    placeholder="<?php esc_attr_e( 'Search stations...', 'radiotheme' ); ?>"
                    autocomplete="off"
                    spellcheck="false"
                    aria-label="<?php esc_attr_e( 'Search radio stations', 'radiotheme' ); ?>"
                    aria-autocomplete="list"
                    aria-controls="search-results-dropdown"
                >
                <div id="search-results-dropdown" class="search-dropdown" role="listbox" aria-label="<?php esc_attr_e( 'Search results', 'radiotheme' ); ?>"></div>
            </div>

            <div
                class="radio-list"
                id="radio-list-container"
                data-genre="<?php echo esc_attr( $active_genre ); ?>"
                data-country="<?php echo esc_attr( $country_slug ); ?>"
                data-country-code="<?php echo esc_attr( $url_cc ); ?>"
                data-sort="<?php echo esc_attr( $active_sort ); ?>"
                data-page="1"
                data-loading="false"
                data-no-more="false"
                aria-label="<?php esc_attr_e( 'Radio stations', 'radiotheme' ); ?>"
                role="list"
            >
                <?php for ( $i = 0; $i < 10; $i++ ) : ?>
                <div class="radio-card-skeleton" aria-hidden="true">
                    <div class="skeleton skeleton-logo"></div>
                    <div class="skeleton-content">
                        <div class="skeleton skeleton-title"></div>
                        <div class="skeleton skeleton-subtitle"></div>
                        <div class="skeleton skeleton-tags"></div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>

            <div class="loading-spinner" id="radio-list-spinner" style="display:none" aria-label="<?php esc_attr_e( 'Loading stations', 'radiotheme' ); ?>">
                <div class="spinner" role="status">
                    <span class="visually-hidden"><?php esc_html_e( 'Loading...', 'radiotheme' ); ?></span>
                </div>
            </div>

            <nav class="radio-pagination" id="radio-pagination" aria-label="<?php esc_attr_e( 'Station pages', 'radiotheme' ); ?>" style="display:none">
                <button class="pagination-btn" id="pagination-prev" aria-label="<?php esc_attr_e( 'Previous page', 'radiotheme' ); ?>">&#8592; <?php esc_html_e( 'Prev', 'radiotheme' ); ?></button>
                <div class="pagination-numbers" id="pagination-numbers" role="list"></div>
                <button class="pagination-btn" id="pagination-next" aria-label="<?php esc_attr_e( 'Next page', 'radiotheme' ); ?>"><?php esc_html_e( 'Next', 'radiotheme' ); ?> &#8594;</button>
            </nav>

        </section>

        <!-- ====================================================
             ORTA KOLON: Sol sidebar
             ==================================================== -->
        <aside class="sidebar-left-column sidebar-column" role="complementary"
               aria-label="<?php esc_attr_e( 'Sidebar', 'radiotheme' ); ?>"
               id="ajax-sidebar-l">
            <div class="sidebar-sticky">

                <div class="ad-zone ad-zone-300x250" id="ad-zone-left-top">
                    <?php if ( is_active_sidebar( 'sidebar-left-top' ) ) :
                        dynamic_sidebar( 'sidebar-left-top' );
                    else : ?>
                        <div class="ad-zone-placeholder"><span>Advertisement</span><small>300 × 250</small></div>
                    <?php endif; ?>
                </div>

                <!-- Popüler istasyonlar — ülkeye göre filtreli -->
                <div class="sidebar-widget widget-popular-stations">
                    <h2 class="widget-title">
                        <?php echo $country_name
                            ? sprintf( esc_html__( 'Popular in %s', 'radiotheme' ), esc_html( $country_name ) )
                            : esc_html__( 'Popular Stations', 'radiotheme' ); ?>
                    </h2>
                    <?php radiotheme_popular_stations_by_country( 8, $country_slug ); ?>
                </div>

                <div class="ad-zone ad-zone-300x600" id="ad-zone-left-bottom">
                    <?php if ( is_active_sidebar( 'sidebar-left-bottom' ) ) :
                        dynamic_sidebar( 'sidebar-left-bottom' );
                    else : ?>
                        <div class="ad-zone-placeholder"><span>Advertisement</span><small>300 × 600</small></div>
                    <?php endif; ?>
                </div>

            </div>
        </aside>

        <!-- ====================================================
             SAĞ KOLON: Sağ sidebar
             ==================================================== -->
        <aside class="sidebar-right-column sidebar-column" role="complementary"
               aria-label="<?php esc_attr_e( 'Sidebar right', 'radiotheme' ); ?>">
            <div class="sidebar-sticky">

                <div class="ad-zone ad-zone-300x250" id="ad-zone-right-top">
                    <?php if ( is_active_sidebar( 'sidebar-right-top' ) ) :
                        dynamic_sidebar( 'sidebar-right-top' );
                    else : ?>
                        <div class="ad-zone-placeholder"><span>Advertisement</span><small>300 × 250</small></div>
                    <?php endif; ?>
                </div>

                <!-- Genre cloud — ülkeye göre filtreli -->
                <div class="sidebar-widget widget-genre-cloud">
                    <h2 class="widget-title"><?php esc_html_e( 'Browse by Genre', 'radiotheme' ); ?></h2>
                    <?php radiotheme_genre_cloud_by_country( 20, $country_slug, $url_cc ); ?>
                </div>

                <!-- Ülke listesi -->
                <div class="sidebar-widget widget-countries">
                    <h2 class="widget-title"><?php esc_html_e( 'Top Countries', 'radiotheme' ); ?></h2>
                    <?php radiotheme_country_list_linked( 10 ); ?>
                </div>

                <div class="ad-zone ad-zone-160x600" id="ad-zone-right-bottom">
                    <?php if ( is_active_sidebar( 'sidebar-right-bottom' ) ) :
                        dynamic_sidebar( 'sidebar-right-bottom' );
                    else : ?>
                        <div class="ad-zone-placeholder"><span>Advertisement</span><small>160 × 600</small></div>
                    <?php endif; ?>
                </div>

            </div>
        </aside>

    </div><!-- .content-area -->

</main>

<?php get_footer(); ?>
<!-- Country Picker Modal JS -->
<script>
(function () {
    'use strict';

    /* Modal mantığını başlat — AJAX navigation sonrası yeniden çağrılır */
    function initCountryPicker() {
        var overlay = document.getElementById('country-picker');
        if (!overlay) return;

        /* Daha önce listener eklendiyse tekrar ekleme */
        if (overlay._cpInit) return;
        overlay._cpInit = true;

        function openModal() {
            overlay.hidden = false;
            document.body.classList.add('modal-open');
            var search = overlay.querySelector('.country-picker-search');
            if (search) { search.value = ''; filterList(''); search.focus(); }
            var active = overlay.querySelector('.country-picker-item.is-active');
            if (active) setTimeout(function () { active.scrollIntoView({ block: 'center' }); }, 50);
        }

        function closeModal() {
            overlay.hidden = true;
            document.body.classList.remove('modal-open');
        }

        function filterList(q) {
            var items = overlay.querySelectorAll('.country-picker-item');
            q = q.toLowerCase().trim();
            items.forEach(function (li) {
                var name = li.querySelector('.country-picker-name');
                li.style.display = (!q || (name && name.textContent.toLowerCase().indexOf(q) !== -1)) ? '' : 'none';
            });
        }

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-modal="country-picker"]');
            if (btn) { e.preventDefault(); openModal(); return; }
            if (e.target === overlay) { closeModal(); return; }
            if (e.target.closest('.country-picker-close')) { closeModal(); }
        });

        document.addEventListener('keydown', function (e) {
            if (!overlay.hidden && e.key === 'Escape') closeModal();
        });

        var searchInput = overlay.querySelector('.country-picker-search');
        if (searchInput) {
            searchInput.addEventListener('input', function () { filterList(this.value); });
        }
    }

    /* İlk yükleme */
    initCountryPicker();

    /* AJAX navigation sonrası yeniden başlat — overlay DOM'a yeni inject edilmiş olabilir */
    document.addEventListener('rt:navigation-done', function () {
        /* overlay flag'ini sıfırla ki yeni DOM'daki element yeniden init edilebilsin */
        var overlay = document.getElementById('country-picker');
        if (overlay) overlay._cpInit = false;
        initCountryPicker();
    });
})();
</script>
