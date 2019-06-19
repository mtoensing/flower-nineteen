<?php
/**
 * Template Name: List Galleries
 *
 * Description: A custom game ranking template
 *
 */

get_header();
?>

	<section id="primary" class="content-area">
		<main id="main" class="site-main">
			<div class="entry-content">
				<?php

				/* Start the Loop */
				while ( have_posts() ) :
					the_post();
?>
					<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
						<?php if ( ! twentynineteen_can_show_post_thumbnail() ) : ?>
						<header class="entry-header">
							<?php get_template_part( 'template-parts/header/entry', 'header' ); ?>
						</header>
						<?php endif; ?>

						<div class="entry-content">
							<?php
							the_content();
							echo get_gallery_list();

							wp_link_pages(
								array(
									'before' => '<div class="page-links">' . __( 'Pages:', 'twentynineteen' ),
									'after'  => '</div>',
								)
							);
							?>
						</div><!-- .entry-content -->

						<?php if ( get_edit_post_link() ) : ?>
							<footer class="entry-footer">
								<?php
								edit_post_link(
									sprintf(
										wp_kses(
											/* translators: %s: Name of current post. Only visible to screen readers */
											__( 'Edit <span class="screen-reader-text">%s</span>', 'twentynineteen' ),
											array(
												'span' => array(
													'class' => array(),
												),
											)
										),
										get_the_title()
									),
									'<span class="edit-link">',
									'</span>'
								);
								?>
							</footer><!-- .entry-footer -->
						<?php endif; ?>
					</article><!-- #post-<?php the_ID(); ?> -->
					 <?php


				endwhile; // End of the loop.
				?>

			</div>
		</main><!-- #main -->
	</section><!-- #primary -->

<?php
get_footer();
?>
