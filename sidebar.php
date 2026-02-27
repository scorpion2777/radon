<?php
/**
 * RadioTheme — sidebar.php
 * Renders left and right sidebar columns (called from single page)
 */
?>

<!-- Left Sidebar Column -->
<aside class="sidebar-left-column sidebar-column" role="complementary" aria-label="<?php esc_attr_e( 'Left sidebar', 'radiotheme' ); ?>">
    <div class="sidebar-sticky">

        <div class="ad-zone ad-zone-300x250" id="sidebar-ad-left-top" aria-label="<?php esc_attr_e( 'Advertisement', 'radiotheme' ); ?>">
            <?php if ( is_active_sidebar( 'sidebar-left-top' ) ) :
                dynamic_sidebar( 'sidebar-left-top' );
            else : ?>
                <div class="ad-zone-placeholder">
                    <span><?php esc_html_e( 'Advertisement', 'radiotheme' ); ?></span>
                    <small>300 × 250</small>
                </div>
            <?php endif; ?>
        </div>

        <?php if ( is_active_sidebar( 'sidebar-left-bottom' ) ) : ?>
            <div class="ad-zone ad-zone-300x600" id="sidebar-ad-left-bottom" aria-label="<?php esc_attr_e( 'Advertisement', 'radiotheme' ); ?>">
                <?php dynamic_sidebar( 'sidebar-left-bottom' ); ?>
            </div>
        <?php endif; ?>

    </div>
</aside>

<!-- Right Sidebar Column -->
<aside class="sidebar-right-column sidebar-column" role="complementary" aria-label="<?php esc_attr_e( 'Right sidebar', 'radiotheme' ); ?>">
    <div class="sidebar-sticky">

        <div class="ad-zone ad-zone-300x250" id="sidebar-ad-right-top" aria-label="<?php esc_attr_e( 'Advertisement', 'radiotheme' ); ?>">
            <?php if ( is_active_sidebar( 'sidebar-right-top' ) ) :
                dynamic_sidebar( 'sidebar-right-top' );
            else : ?>
                <div class="ad-zone-placeholder">
                    <span><?php esc_html_e( 'Advertisement', 'radiotheme' ); ?></span>
                    <small>300 × 250</small>
                </div>
            <?php endif; ?>
        </div>

        <?php if ( is_active_sidebar( 'sidebar-right-bottom' ) ) : ?>
            <div class="ad-zone ad-zone-160x600" id="sidebar-ad-right-bottom" aria-label="<?php esc_attr_e( 'Advertisement', 'radiotheme' ); ?>">
                <?php dynamic_sidebar( 'sidebar-right-bottom' ); ?>
            </div>
        <?php endif; ?>

    </div>
</aside>
