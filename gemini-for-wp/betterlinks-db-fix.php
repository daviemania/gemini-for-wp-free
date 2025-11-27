<?php
/**
 * Plugin Name: BetterLinks DB Fix
 * Description: Checks if BetterLinks tables exist and tries to create them if they don't.
 * Version: 1.0
 * Author: Gemini
 */

function btl_db_fix_check_and_create_tables() {
    // Check if BetterLinks plugin is active
    if ( ! is_plugin_active( 'betterlinks/betterlinks.php' ) ) {
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'betterlinks';

    // Check if the table exists
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
        // Log that the table is missing
        error_log( 'BetterLinks table not found. Attempting to create it.' );

        // Try to trigger the table creation
        if ( class_exists( 'BetterLinks\\Installer' ) ) {
            $installer = new BetterLinks\Installer();
            if ( method_exists( $installer, 'create_tables' ) ) {
                $installer->create_tables();
                error_log( 'BetterLinks table creation process triggered.' );
            } else {
                error_log( 'BetterLinks\\Installer class does not have a create_tables method.' );
            }
        } else {
            error_log( 'BetterLinks\\Installer class not found.' );
        }
    }
}

add_action( 'plugins_loaded', 'btl_db_fix_check_and_create_tables' );

