<?php
/**
 * RadioTheme — footer.php
 */
?>
    <!-- SITE FOOTER -->
    <footer class="site-footer" role="contentinfo">
        <div class="footer-inner">
            <div class="footer-grid">

                <!-- Brand -->
                <div class="footer-brand">
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="footer-logo">
                        <?php
                        $name  = get_bloginfo( 'name' );
                        $first = mb_substr( $name, 0, 1 );
                        $rest  = mb_substr( $name, 1 );
                        echo '<span>' . esc_html( $first ) . '</span>' . esc_html( $rest );
                        ?>
                    </a>
                    <?php $desc = get_bloginfo( 'description' ); if ( $desc ) : ?>
                        <p><?php echo esc_html( $desc ); ?></p>
                    <?php endif; ?>
                </div>

                <!-- Genres -->
                <div class="footer-col">
                    <h4 class="footer-col-title"><?php esc_html_e( 'Genres', 'radiotheme' ); ?></h4>
                    <div class="footer-links">
                        <?php
                        $genres = get_terms( [ 'taxonomy' => 'radio-genre', 'orderby' => 'count', 'order' => 'DESC', 'hide_empty' => true, 'number' => 8 ] );
                        if ( $genres && ! is_wp_error( $genres ) ) :
                            foreach ( $genres as $g ) :
                                $url = get_term_link( $g );
                                if ( ! is_wp_error( $url ) ) :
                        ?>
                            <a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $g->name ); ?></a>
                        <?php endif; endforeach; endif; ?>
                    </div>
                </div>

                <!-- Countries -->
                <div class="footer-col">
                    <h4 class="footer-col-title"><?php esc_html_e( 'Countries', 'radiotheme' ); ?></h4>
                    <div class="footer-links">
                        <?php
                        $countries = get_terms( [ 'taxonomy' => 'radio-country', 'orderby' => 'count', 'order' => 'DESC', 'hide_empty' => true, 'number' => 8, 'parent' => 0 ] );
                        if ( $countries && ! is_wp_error( $countries ) ) :
                            foreach ( $countries as $c ) :
                                $url = get_term_link( $c );
                                if ( ! is_wp_error( $url ) ) :
                        ?>
                            <a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $c->name ); ?></a>
                        <?php endif; endforeach; endif; ?>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="footer-col">
                    <h4 class="footer-col-title"><?php esc_html_e( 'Quick Links', 'radiotheme' ); ?></h4>
                    <div class="footer-links">
                        <?php if ( has_nav_menu( 'footer' ) ) : ?>
                            <?php wp_nav_menu( [
                                'theme_location' => 'footer',
                                'container'      => false,
                                'items_wrap'     => '%3$s',
                                'depth'          => 1,
                                'fallback_cb'    => false,
                                'walker'         => new class extends Walker_Nav_Menu {
                                    public function start_el( &$output, $data_object, $depth = 0, $args = null, $id = 0 ) {
                                        $output .= '<a href="' . esc_url( $data_object->url ) . '">' . esc_html( $data_object->title ) . '</a>';
                                    }
                                    public function start_lvl( &$o, $d = 0, $a = null ) {}
                                    public function end_lvl( &$o, $d = 0, $a = null ) {}
                                    public function end_el( &$o, $d = 0, $a = null, $i = 0 ) {}
                                },
                            ] ); ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div><!-- .footer-grid -->

            <div class="footer-bottom">
                <p>&copy; <?php echo esc_html( date( 'Y' ) ); ?> <a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="color:rgba(255,255,255,.4);text-decoration:none"><?php bloginfo( 'name' ); ?></a>. <?php esc_html_e( 'All rights reserved.', 'radiotheme' ); ?></p>
                <nav aria-label="<?php esc_attr_e( 'Legal navigation', 'radiotheme' ); ?>">
                    <?php if ( get_privacy_policy_url() ) : ?>
                        <a href="<?php echo esc_url( get_privacy_policy_url() ); ?>" style="color:rgba(255,255,255,.4);text-decoration:none;font-size:.75rem;margin-left:1rem"><?php esc_html_e( 'Privacy', 'radiotheme' ); ?></a>
                    <?php endif; ?>
                </nav>
            </div>

        </div><!-- .footer-inner -->
    </footer><!-- .site-footer -->

</div><!-- .site-wrapper -->

<?php /* Gizli Audio Element - RadioTheme Player */ ?>
<audio id="radio-audio-element" preload="none" style="display:none" aria-hidden="true"></audio>

<?php wp_footer(); ?>
</body>
</html>
