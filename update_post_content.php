<?php
// Load WordPress environment
require_once('/opt/bitnami/wordpress/wp-load.php');

// Get post ID and content file path from command line arguments
$post_id = isset($argv[1]) ? intval($argv[1]) : 0;
$content_file_path = isset($argv[2]) ? $argv[2] : '';

if ( $post_id > 0 && !empty($content_file_path) && file_exists($content_file_path) ) {
    $new_content = file_get_contents($content_file_path);
    if ($new_content === false) {
        echo "Error: Could not read content from file: " . $content_file_path . "\n";
        exit(1);
    }
    $post_data = array(
        'ID'           => $post_id,
        'post_content' => $new_content,
    );

    // Update the post into the database
    $result = wp_update_post( $post_data, true );

    if ( is_wp_error( $result ) ) {
        echo "Error updating post: " . $result->get_error_message();
    } else {
        echo "Post " . $post_id . " updated successfully.";
    }
} else {
    echo "Usage: php update_post_content.php <post_id> <path_to_content_file>\n";
}
?>
