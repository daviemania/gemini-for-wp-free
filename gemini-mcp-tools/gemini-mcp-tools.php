<?php
/**
 * Plugin Name: Gemini MCP Tools
 * Description: Integrates custom tools with AI Engine's Media Control Protocol.
 * Version: 1.0.7
 * Author: David Mania
 * Author URI: https://maniainc.com
 */

if ( ! function_exists( 'gfw_fs' ) ) {
    // Create a helper function for easy SDK access.
    function gfw_fs() {
        global $gfw_fs;

        if ( ! isset( $gfw_fs ) ) {
            // Activate multisite network integration.
            if ( ! defined( 'WP_FS__PRODUCT_22000_MULTISITE' ) ) {
                define( 'WP_FS__PRODUCT_22000_MULTISITE', true );
            }

            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/vendor/freemius/start.php';

            $gfw_fs = fs_dynamic_init( array(
                'id'                  => '22000',
                'slug'                => 'gemini-for-wp',
                'type'                => 'plugin',
                'public_key'          => 'pk_d2f0b07deef9f60a6a8400169b555',
                'is_premium'          => true,
                'premium_suffix'      => 'Premium',
                // If your plugin is a serviceware, set this option to false.
                'has_premium_version' => true,
                'has_addons'          => false,
                'has_paid_plans'      => true,
                // Automatically removed in the free version. If you're not using the
                // auto-generated free version, delete this line before uploading to wp.org.
                'wp_org_gatekeeper'   => 'OA7#BoRiBNqdf52FvzEf!!074aRLPs8fspif$7K1#4u4Csys1fQlCecVcUTOs2mcpeVHi#C2j9d09fOTvbC0HloPT7fFee5WdS3G',
                'trial'               => array(
                    'days'               => 3,
                    'is_require_payment' => true,
                ),
                'menu'                => array(
                    'first-path'     => 'plugins.php',
                    'support'        => false,
                ),
            ) );
        }

        return $gfw_fs;
    }

    // Init Freemius.
    gfw_fs();
    // Signal that SDK was initiated.
    do_action( 'gfw_fs_loaded' );
}

if (!defined("ABSPATH")) {
    exit(); // Exit if accessed directly
}

require_once plugin_dir_path(__FILE__) . "mcp.php";
require_once __DIR__ . "/freemius-gate.php";

// Initialize the MCP tools
function gemini_mcp_tools_init()
{
    global $gemini_mcp_tools_mcp;
    $gemini_mcp_tools_mcp = new Gemini_MCP_Tools_MCP();
}
add_action("plugins_loaded", "gemini_mcp_tools_init");
