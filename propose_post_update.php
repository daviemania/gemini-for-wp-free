<?php
// Load WordPress environment
require_once('/opt/bitnami/wordpress/wp-load.php');

// Get post ID and content file path from command line arguments
$post_id = isset($argv[1]) ? intval($argv[1]) : 0;
$content_file_path = isset($argv[2]) ? $argv[2] : '';

$new_content = '';
if (!empty($content_file_path) && file_exists($content_file_path)) {
    $new_content = file_get_contents($content_file_path);
}

if ( $post_id > 0 && !empty($new_content) ) {
    $post_data = array(
        'ID'           => $post_id,
        'post_content' => $new_content,
        'post_status'  => 'pending',
    );

    // Update the post into the database
    $result = wp_update_post( $post_data, true );

    if ( is_wp_error( $result ) ) {
        echo "Error updating post: " . $result->get_error_message();
    } else {
        echo "Post " . $post_id . " updated successfully and is now pending review.";
    }
} else {
    echo "Usage: php propose_post_update.php <post_id> \"<new_content>\"\n";
}
?>
