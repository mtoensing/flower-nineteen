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

/* SHORTSCORE */

function get_shortscore_list() {

	$args = array(
		'post_type'      => 'post',
		'orderby'        => array( 'meta_value_num' => 'DESC', 'date' => 'DESC' ),
		'meta_key'       => '_shortscore_user_rating',
		'posts_per_page' => '300',
		'order'          => 'DESC'
	);

	$the_query = new WP_Query( $args );
	$html      = '';
	$score     = '';

	while ( $the_query->have_posts() ) :
		$the_query->the_post();
		$result =  get_post_meta( get_the_ID(), "_shortscore_result", true );
		$result = json_decode(json_encode($result));
		if ( isset( $result->game ) AND isset( $result->game->title ) ) {
			$title =  $result->game->title;
		}
		$shortscore = get_post_meta( get_the_ID(), "_shortscore_user_rating", true );

		if ( $score != $shortscore AND $shortscore > 0 ) {
			if ( $score != '' ) {
				$html .= "</ul> \n";
			}
			$html .= '<h2>SHORTSCORE ' . $shortscore . '/10</h2>';
			$html .= '<ul>';
		}

		if ( $title != '' AND $shortscore != '' ) {
			$html .= '<li>';
			$html .= '[' . $shortscore . '/10] - <a href="' . get_permalink() . '">' . $title . '</a>';
			$html .= "</li> \n";
		}

		$score = $shortscore;
	endwhile;

	return $html;
}


function get_shortscore_table() {

	$args = array(
		'post_type'      => 'game',
		'orderby'        => 'meta_value_num',
		'orderby'        => array( 'meta_value_num' => 'DESC', 'date' => 'DESC' ),
		'meta_key'       => 'score_value',
		'posts_per_page' => '300',
		'order'          => 'DESC'
	);

	$the_query = new WP_Query( $args );
	$html      = '<table class="GeneratedTable">
  <thead>
    <tr>
      <th>Score</th>
      <th>Votes</th>
      <th>Game</th>
      <!-- <th>Developer</th> -->
      <th>Release Date</th>
    </tr>
  </thead>
  <tbody>';
	$score     = '';

	while ( $the_query->have_posts() ) :
		$the_query->the_post();
		$gid         = get_the_ID();
		$shortscore  = get_post_meta( $gid, "score_value", true );
		$title       = get_the_title( $gid );
		$score_count = get_post_meta( $gid, "score_count", true );
		//$developer_list = get_the_term_list( $gid, 'developer', '', ', ' );
		$releasedate = get_the_date( 'd. m. Y', $gid );

		$html .= '<tr>';
		$html .= '<td>';
		$html .= $shortscore . '/10';
		$html .= "</td> \n";
		$html .= '<td>';
		$html .= $score_count;
		$html .= "</td> \n";
		$html .= '<td>';
		$html .= '<a href="' . get_permalink() . '">' . $title . '</a>';
		$html .= "</td> \n";
		//$html .= '<td>';
		//$html .= $developer_list;
		//$html .= "</td> \n";
		$html .= '<td>';
		$html .= $releasedate;
		$html .= "</td> \n";

		$html .= '</tr>';
	endwhile;
	$html .= '  </tbody>
</table>';

	return $html;
}
?>
