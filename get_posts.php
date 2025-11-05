<?php
// Load WordPress environment
require_once('/opt/bitnami/wordpress/wp-load.php');

// WP_Query arguments
$args = array(
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => 'date',
    'order'          => 'ASC',
);

// The Query
$query = new WP_Query( $args );

// The Loop
if ( $query->have_posts() ) {
    foreach ( $query->posts as $post ) {
        echo $post->ID . ' | ' . $post->post_title . ' | ' . $post->post_date . "\n";
    }
} else {
    // no posts found
}

// Restore original Post Data
wp_reset_postdata();

