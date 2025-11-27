<?php
/**
 * Plugin Name: BetterLinks DB Fix
 * Description: Creates BetterLinks tables on plugin activation.
 * Version: 1.2
 * Author: Gemini
 */

function btl_db_fix_create_tables() {
    // Log that the activation hook is running
    error_log('BetterLinks DB Fix: Activation hook triggered.');

    // Check if BetterLinks Installer class exists
    if (class_exists('BetterLinks\\Installer')) {
        $installer = new BetterLinks\Installer();
        if (method_exists($installer, 'activate')) {
            $installer->activate();
            error_log('BetterLinks DB Fix: activate() method called.');
        } else {
            error_log('BetterLinks DB Fix: Installer class does not have an activate method.');
        }
    } else {
        error_log('BetterLinks DB Fix: BetterLinks\\Installer class not found.');
    }
}

register_activation_hook(__FILE__, 'btl_db_fix_create_tables');