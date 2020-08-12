<?php

add_action('restrict_manage_posts', 'rudr_filter_by_the_author');

function rudr_filter_by_the_author() {
	$params = array(
		'name' => 'author', // this is the "name" attribute for filter <select>
		'show_option_all' => 'All authors' // label for all authors (display posts without filter)
	);

	if ( isset($_GET['user']) )
		$params['selected'] = $_GET['user']; // choose selected user by $_GET variable

	wp_dropdown_users( $params ); // print the ready author list
}


add_filter( 'comment_form_defaults', 'remove_pre_comment_text' );

function remove_pre_comment_text( $arg ) {
  $arg['comment_notes_before'] = "";
  return $arg;
}

add_filter( 'wp_calculate_image_srcset', 'flower_custom_image_srcset', 10, 5);

function flower_custom_image_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id) {
    // The following code is an adaption from wp-includes/media.php:1061-1180
    $image_sizes = $image_meta['sizes'];

    // Get the width and height of the image.
    $image_width = (int) $size_array[0];
    $image_height = (int) $size_array[1];

    $image_basename = wp_basename( $image_meta['file'] );

    /*
     * WordPress flattens animated GIFs into one frame when generating intermediate sizes.
     * To avoid hiding animation in user content, if src is a full size GIF, a srcset attribute is not generated.
     * If src is an intermediate size GIF, the full size is excluded from srcset to keep a flattened GIF from becoming animated.
     */
    if ( ! isset( $image_sizes['thumbnail']['mime-type'] ) || 'image/gif' !== $image_sizes['thumbnail']['mime-type'] ) {
        $image_sizes[] = array(
            'width'  => $image_meta['width'],
            'height' => $image_meta['height'],
            'file'   => $image_basename,
        );
    } elseif ( strpos( $image_src, $image_meta['file'] ) ) {
        return false;
    }

    // Retrieve the uploads sub-directory from the full size image.
    $dirname = _wp_get_attachment_relative_path( $image_meta['file'] );

    if ( $dirname ) {
        $dirname = trailingslashit( $dirname );
    }

    $upload_dir = wp_get_upload_dir();
    $image_baseurl = trailingslashit( $upload_dir['baseurl'] ) . $dirname;

    /*
     * If currently on HTTPS, prefer HTTPS URLs when we know they're supported by the domain
     * (which is to say, when they share the domain name of the current request).
     */
    if ( is_ssl() && 'https' !== substr( $image_baseurl, 0, 5 ) && parse_url( $image_baseurl, PHP_URL_HOST ) === $_SERVER['HTTP_HOST'] ) {
        $image_baseurl = set_url_scheme( $image_baseurl, 'https' );
    }

    /*
     * Images that have been edited in WordPress after being uploaded will
     * contain a unique hash. Look for that hash and use it later to filter
     * out images that are leftovers from previous versions.
     */
    $image_edited = preg_match( '/-e[0-9]{13}/', wp_basename( $image_src ), $image_edit_hash );

    /**
     * Filters the maximum image width to be included in a 'srcset' attribute.
     *
     * @since 4.4.0
     *
     * @param int   $max_width  The maximum image width to be included in the 'srcset'. Default '1600'.
     * @param array $size_array Array of width and height values in pixels (in that order).
     */
    $max_srcset_image_width = apply_filters( 'max_srcset_image_width', 2000, $size_array );

    // Array to hold URL candidates.
    $sources = array();

    /**
     * To make sure the ID matches our image src, we will check to see if any sizes in our attachment
     * meta match our $image_src. If no matches are found we don't return a srcset to avoid serving
     * an incorrect image. See #35045.
     */
    $src_matched = false;

    /*
     * Loop through available images. Only use images that are resized
     * versions of the same edit.
     */
    foreach ( $image_sizes as $identifier => $image ) {
        // Continue if identifier is unwanted
        if (!in_array($identifier, array('large','medium','medium-large','thumbnail','post-thumbnail','thumbnail','yarpp','yarpp-retina'))) {
            continue;
        }

        $is_src = false;

        // Check if image meta isn't corrupted.
        if ( ! is_array( $image ) ) {
            continue;
        }

        // If the file name is part of the `src`, we've confirmed a match.
        if ( ! $src_matched && false !== strpos( $image_src, $dirname . $image['file'] ) ) {
            $src_matched = $is_src = true;
        }

        // Filter out images that are from previous edits.
        if ( $image_edited && ! strpos( $image['file'], $image_edit_hash[0] ) ) {
            continue;
        }

        /*
         * Filters out images that are wider than '$max_srcset_image_width' unless
         * that file is in the 'src' attribute.
         */
        if ( $max_srcset_image_width && $image['width'] > $max_srcset_image_width && ! $is_src ) {
            continue;
        }

        // If the image dimensions are within 1px of the expected size, use it.
        if ( wp_image_matches_ratio( $image_width, $image_height, $image['width'], $image['height'] ) ) {
            // Add the URL, descriptor, and value to the sources array to be returned.
            $source = array(
                'url'        => $image_baseurl . $image['file'],
                'descriptor' => 'w',
                'value'      => $image['width'],
            );

            // The 'src' image has to be the first in the 'srcset', because of a bug in iOS8. See #35030.
            if ( $is_src ) {
                $sources = array( $image['width'] => $source ) + $sources;
            } else {
                $sources[ $image['width'] ] = $source;
            }
        }
    }

    return $sources;
}


function flowerfield_recent_posts()
{
    $cpid = get_the_ID();
    $excludes[] = $cpid;
    $related_posts_array = array();

    if (function_exists('yarpp_get_related')) {
    	$related_posts = yarpp_get_related(array(), $cpid);
    } else {
			return;
		}

    foreach ($related_posts as $posts) {
        $related_posts_array[] = $posts->ID;
    }

    $excludes = array_merge($excludes, $related_posts_array);

    $args = array(
            'post_type'      => 'post',
            'post_status' 	 => 'publish',
            'posts_per_page' => '4',
            'post__not_in'   => $excludes,
            'order'          => 'DESC'
    );
    $i = 0;
    $the_query = new WP_Query($args); ?>

	<div class="teaserbox-wrapper recentpostsbox">
	<div class="teaserbox" style="display: block;">
		<h3 class="teaserbox-headline"><em>Neue Beiträge</em></h3>
		<div class="teaserbox-items teaserbox-items-visual teaserbox-grid ">
	  <?php while ($the_query->have_posts()) :
      $the_query->the_post();
      if (has_post_thumbnail()):?>
			<?php
      $size = "yarpp";
      $size_retina = "yarpp-retina"; ?>
			<?php $permalink = get_the_permalink(); ?>
						<div class="teaserbox-post teaserbox-post<?php echo $i?> teaserbox-post-thumbs">
								<a class="teaserbox-post-a" href="<?php echo $permalink; ?>" title="<?php the_title_attribute(); ?>" rel="nofollow" >
								<img loading="lazy" src="<?php the_post_thumbnail_url($size); ?>" srcset="<?php the_post_thumbnail_url($size_retina); ?> 2x">
								</a>
								<h4 data-date="<?php the_date(); ?>" class="teaserbox-post-title">
										<a class="teaserbox-post-a" href="<?php echo $permalink; ?>" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a>
								</h4>
						</div>
				<?php $i++ ?>
			<?php endif; ?>
	 <?php endwhile; ?>
   <?php wp_reset_postdata(); ?>
	 </div>
	</div>
	</div>

<?php
}


function flowerfield_get_post_gallery($gallery, $post)
{

    // Already found a gallery so lets quit.
    if ($gallery) {
        return $gallery;
    }

    // Check the post exists.
    $post = get_post($post);
    if (! $post) {
        return $gallery;
    }

    // Not using Gutenberg so let's quit.
    if (! function_exists('has_blocks')) {
        return $gallery;
    }

    // Not using blocks so let's quit.
    if (! has_blocks($post->post_content)) {
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
    preg_match_all($pattern, $post->post_content, $the_galleries);
    // Check a gallery was found and if so change the gallery html.
    if (! empty($the_galleries[1])) {
        $gallery = reset($the_galleries[1]);
    }

    return $gallery;
}

add_filter('get_post_gallery', 'flowerfield_get_post_gallery', 10, 2);

add_action('after_setup_theme', 'flower_theme_setup', 111);

function flower_theme_setup()
{
    add_image_size('yarpp', 460, 200, true); // yarpp image
    add_image_size('yarpp-retina', 920, 400, true); // yarpp image
    add_image_size('medium_large', 692, 376, true); // m image
    set_post_thumbnail_size(1416, 9999);
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
        echo '<h2><a href="' . get_permalink() . '">' . $title . '</a></h2>';
        echo get_post_gallery($pid);
    }

    endwhile;
    wp_reset_postdata();
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
    wp_reset_postdata();

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
