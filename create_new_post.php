<?php
// Define server variables for multisite
$_SERVER['HTTP_HOST'] = 'maniainc.com';
$_SERVER['REQUEST_URI'] = '/';

// Load WordPress environment
require_once('/opt/bitnami/wordpress/wp-load.php');

// Get arguments from command line
$site_slug = isset($argv[1]) ? $argv[1] : '';
$post_title = isset($argv[2]) ? $argv[2] : '';
$content_file_path = isset($argv[3]) ? $argv[3] : '';

// Get the ID of the site from the slug
$site_id = get_id_from_blogname($site_slug);
if (!$site_id) {
    echo "Error: Site with slug '" . $site_slug . "' not found.\n";
    exit(1);
}

// Switch to the specified site
switch_to_blog($site_id);

$new_content = '';
if (!empty($content_file_path) && file_exists($content_file_path)) {
    $new_content = file_get_contents($content_file_path);
}

if (!empty($post_title) && !empty($new_content)) {
    $post_data = array(
        'post_title'   => $post_title,
        'post_content' => $new_content,
        'post_status'  => 'pending',
        'post_author'  => 1, // Assuming admin user with ID 1
    );

    // Insert the post into the database
    $result = wp_insert_post($post_data, true);

    if (is_wp_error($result)) {
        echo "Error creating post: " . $result->get_error_message() . "\n";
    } else {
        echo "Post '" . $post_title . "' created successfully on site '" . $site_slug . "' and is now pending review. Post ID is " . $result . "\n";
    }
} else {
    echo "Usage: php create_new_post.php <site_slug> \"<post_title>\" <content_file_path>\n";
}

// Restore the original site
restore_current_blog();
?>