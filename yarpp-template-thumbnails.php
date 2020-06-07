<?php
/*
YARPP Template: Thumbnails Flower
Description: Requires a theme with support for post thumbnails
Author: Marc Tönsing
*/ ?>

<?php if (have_posts()):?>
    <div class="teaserbox" style="display: block;">
        <h3 class="teaserbox-headline"><em>Ähnliche Beiträge</em></h3>
        <div class="teaserbox-items teaserbox-items-visual teaserbox-grid ">
			<?php $i = 0; ?>
			<?php while (have_posts()) : the_post(); ?>
				<?php if (has_post_thumbnail()):?>
					<?php
            $size = "yarpp";
            $size_retina = "yarpp-retina";
          ?>
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

        </div>
    </div>

<?php endif; ?>
