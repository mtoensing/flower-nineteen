<?php

function bm_get_post_gallery( $gallery, $post ) {

	// Already found a gallery so lets quit.
	if ( $gallery ) {
		return $gallery;
	}

	// Check the post exists.
	$post = get_post( $post );
	if ( ! $post ) {
		return $gallery;
	}

	// Not using Gutenberg so let's quit.
	if ( ! function_exists( 'has_blocks' ) ) {
		return $gallery;
	}

	// Not using blocks so let's quit.
	if ( ! has_blocks( $post->post_content ) ) {
		return $gallery;
	}

	/**
	 * Search for gallery blocks and then, if found, return the html from the
	 * first gallery block.
	 *
	 * Thanks to Gabor for help with the regex:
	 * https://twitter.com/javorszky/status/1043785500564381696.
	 */
	$pattern = "/<!--\ wp:gallery.*-->([\s\S]*?)<!--\ \/wp:gallery -->/i";
	preg_match_all( $pattern, $post->post_content, $the_galleries );
	// Check a gallery was found and if so change the gallery html.
	if ( ! empty( $the_galleries[1] ) ) {
		$gallery = reset( $the_galleries[1] );
	}

	return $gallery;

}

add_filter( 'get_post_gallery', 'bm_get_post_gallery', 10, 2 );

add_action('after_setup_theme', 'flower_theme_setup', 111);

function flower_theme_setup()
{
    add_image_size('yarpp', 460, 200, true); // yarpp image
    add_image_size('yarpp-retina', 920, 400, true); // yarpp image
    set_post_thumbnail_size(1920, 9999);
}

add_filter('jpeg_quality', function ($arg) {
    return 90;
});

add_action('wp_enqueue_scripts', 'flower_enqueue_styles');

function flower_enqueue_styles()
{
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
}

/* Galeries */
function get_gallery_list()
{
    $args = array(
        'post_type'      => 'post',
        'orderby'        => array( 'meta_value_num' => 'DESC', 'date' => 'DESC' ),
        'meta_key'       => '_shortscore_user_rating',
        'posts_per_page' => '300',
        'order'          => 'DESC',
        'tag'						 => 'kunstpixel'
    );

    $the_query = new WP_Query($args);
    $html      = '';

    while ($the_query->have_posts()) :

        $the_query->the_post();
        $title 		= '';
        $pid = get_the_ID();
        $post_title  = get_the_title($pid);
        $result =  get_post_meta($pid, "_shortscore_result", true);

        if (isset($result->game) and isset($result->game->title)) {
            $title =  $result->game->title;
            echo '<h2>' . $title . '</h2>';
            echo get_post_gallery($pid);
						echo '<a href="' . get_permalink() . '">Zum Artikel "' . $title . '"</a>';
        }

    endwhile;

    return $html;
}

/* SHORTSCORE */

function get_shortscore_list()
{
    $args = array(
        'post_type'      => 'post',
        'orderby'        => array( 'meta_value_num' => 'DESC', 'date' => 'DESC' ),
        'meta_key'       => '_shortscore_user_rating',
        'posts_per_page' => '300',
        'order'          => 'DESC'
    );

    $the_query = new WP_Query($args);
    $html      = '';
    $score     = '';

    while ($the_query->have_posts()) :
        $the_query->the_post();
    $result =  get_post_meta(get_the_ID(), "_shortscore_result", true);
    $result = json_decode(json_encode($result));
    if (isset($result->game) and isset($result->game->title)) {
        $title =  $result->game->title;
    }
    $shortscore = get_post_meta(get_the_ID(), "_shortscore_user_rating", true);

    if ($score != $shortscore and $shortscore > 0) {
        if ($score != '') {
            $html .= "</ul> \n";
        }
        $html .= '<h2>SHORTSCORE ' . $shortscore . '/10</h2>';
        $html .= '<ul>';
    }

    if ($title != '' and $shortscore != '') {
        $html .= '<li>';
        $html .= '[' . $shortscore . '/10] - <a href="' . get_permalink() . '">' . $title . '</a>';
        $html .= "</li> \n";
    }

    $score = $shortscore;
    endwhile;

    return $html;
}


/* more link */
function new_excerpt_more($more)
{
    return '';
}
add_filter('excerpt_more', 'new_excerpt_more', 21);

function the_excerpt_more_link($excerpt)
{
    $post = get_post();
    $readmore = sprintf(
            wp_kses(
                /* translators: %s: Name of current post. Only visible to screen readers */
                __('Continue reading<span class="screen-reader-text"> "%s"</span>', 'twentynineteen'),
                array(
                    'span' => array(
                        'class' => array(),
                    ),
                )
            ),
            get_the_title()
        );
    $excerpt .= '<a class="readmorelink" href="'. get_permalink($post->ID) .'">' . $readmore . '</a>';
    return $excerpt;
}
add_filter('the_excerpt', 'the_excerpt_more_link', 21);

class FlowerTwentyNineteen_Walker_Comment extends Walker_Comment
{

    /**
     * Outputs a comment in the HTML5 format.
     *
     * @see wp_list_comments()
     *
     * @param WP_Comment $comment Comment to display.
     * @param int        $depth   Depth of the current comment.
     * @param array      $args    An array of arguments.
     */
    protected function html5_comment($comment, $depth, $args)
    {
        $tag = ('div' === $args['style']) ? 'div' : 'li'; ?>
		<<?php echo $tag; ?> id="comment-<?php comment_ID(); ?>" <?php comment_class($this->has_children ? 'parent' : '', $comment); ?>>
			<article id="div-comment-<?php comment_ID(); ?>" class="comment-body">
				<footer class="comment-meta">
					<div class="comment-author vcard">
						<?php
                        $comment_author_url = get_comment_author_url($comment);
        $comment_author     = get_comment_author($comment);
        $avatar             = get_avatar($comment, $args['avatar_size']);
        if (0 != $args['avatar_size']) {
            if (empty($comment_author_url)) {
                echo $avatar;
            } else {
                printf('<a href="%s" rel="external nofollow" class="url">', $comment_author_url);
                echo $avatar;
            }
        }
        /*
         * Using the `check` icon instead of `check_circle`, since we can't add a
         * fill color to the inner check shape when in circle form.
         */
        if (twentynineteen_is_comment_by_post_author($comment)) {
            printf('<span class="post-author-badge" aria-hidden="true">%s</span>', twentynineteen_get_icon_svg('check', 24));
        }

        /*
         * Using the `check` icon instead of `check_circle`, since we can't add a
         * fill color to the inner check shape when in circle form.
         */
        if (twentynineteen_is_comment_by_post_author($comment)) {
            printf('<span class="post-author-badge" aria-hidden="true">%s</span>', twentynineteen_get_icon_svg('check', 24));
        }

        printf(
                            /* translators: %s: comment author link */
                            wp_kses(
                                __('%s <span class="screen-reader-text says">says:</span>', 'twentynineteen'),
                                array(
                                    'span' => array(
                                        'class' => array(),
                                    ),
                                )
                            ),
                            '<b class="fn">' . $comment_author . '</b>'
                        );

        if (! empty($comment_author_url)) {
            echo '</a>';
        } ?>
					</div><!-- .comment-author -->

					<div class="comment-metadata">
						<a href="<?php echo esc_url(get_comment_link($comment, $args)); ?>">
							<?php
                                /* translators: 1: comment date, 2: comment time */
                                $comment_timestamp = sprintf(__('%s', 'twentynineteen'), get_comment_date('', $comment)); ?>
							<time datetime="<?php comment_time('c'); ?>" title="<?php echo $comment_timestamp; ?>">
								<?php echo $comment_timestamp; ?>
							</time>
						</a>
						<?php
                            $edit_comment_icon = twentynineteen_get_icon_svg('edit', 16);
        edit_comment_link(__('Edit', 'twentynineteen'), '<span class="edit-link-sep">&mdash;</span> <span class="edit-link">' . $edit_comment_icon, '</span>'); ?>
					</div><!-- .comment-metadata -->

					<?php if ('0' == $comment->comment_approved) : ?>
					<p class="comment-awaiting-moderation"><?php _e('Your comment is awaiting moderation.', 'twentynineteen'); ?></p>
					<?php endif; ?>
				</footer><!-- .comment-meta -->

				<div class="comment-content">
					<?php comment_text(); ?>
				</div><!-- .comment-content -->

			</article><!-- .comment-body -->

			<?php
            comment_reply_link(
                array_merge(
                    $args,
                    array(
                        'add_below' => 'div-comment',
                        'depth'     => $depth,
                        'max_depth' => $args['max_depth'],
                        'before'    => '<div class="comment-reply">',
                        'after'     => '</div>',
                    )
                )
            ); ?>
		<?php
    }
}

?>
