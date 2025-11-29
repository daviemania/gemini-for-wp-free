<?php
// Ensure WordPress is loaded
define('WP_USE_THEMES', false);
require('/opt/bitnami/wordpress/wp-load.php');

// Get posts
$args = array(
    'posts_per_page' => 5, // Get 5 latest posts
    'post_status'    => 'publish',
    'suppress_filters' => true // Suppress all filters
);
$posts = get_posts($args);

if (!empty($posts)) {
    echo "Latest 5 Published Posts:\n";
    foreach ($posts as $post) {
        echo "- " . $post->post_title . "\n";
    }
} else {
    echo "No published posts found.\n";
}
?>
