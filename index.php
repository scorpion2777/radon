<?php
/**
 * RadioTheme — index.php
 * Fallback template
 */
get_header();
?>
<main class="site-main" id="main-content">
    <div class="content-area">
        <section class="radio-list-column">
            <?php if ( have_posts() ) : ?>
                <div class="radio-list" role="list">
                    <?php while ( have_posts() ) :
                        the_post();
                        if ( get_post_type() === 'radio-station' ) :
                            get_template_part( 'template-parts/radio-card' );
                        else : ?>
                            <article style="padding:1rem;border-bottom:1px solid var(--color-border-light)">
                                <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                                <?php the_excerpt(); ?>
                            </article>
                        <?php endif;
                    endwhile; ?>
                </div>
                <?php the_posts_pagination(); ?>
            <?php else : ?>
                <p style="padding:2rem;text-align:center;color:var(--color-text-muted)"><?php esc_html_e( 'Nothing found.', 'radiotheme' ); ?></p>
            <?php endif; ?>
        </section>
        <?php get_sidebar(); ?>
    </div>
</main>
<?php get_footer(); ?>
