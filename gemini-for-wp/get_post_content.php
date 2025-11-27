<?php
// Load WordPress environment
require_once('/opt/bitnami/wordpress/wp-load.php');

// Get post ID from command line argument
$post_id = isset($argv[1]) ? intval($argv[1]) : 0;

<?php
// Load WordPress environment
require_once('/opt/bitnami/wordpress/wp-load.php');

// Get post ID from command line argument
$post_id = isset($argv[1]) ? intval($argv[1]) : 0;

if ( $post_id > 0 ) {
    $post = get_post( $post_id );
    if ( $post ) {
        echo $post->post_content;
    }
}


