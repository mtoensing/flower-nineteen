<?php
add_action( 'wp_enqueue_scripts', 'flower_enqueue_styles' );

function flower_enqueue_styles() {
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
}

add_action( 'after_setup_theme', 'flower_theme_setup' );

function flower_theme_setup() {
	add_image_size( 'article-retina', 1480 ); // 1480 pixels wide (and unlimited height)
	add_image_size( 'yarpp', 231, 100, true ); // yarpp image
	add_image_size( 'yarpp-retina', 462, 200, true ); // yarpp image
}
?>
