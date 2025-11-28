<?php
/**
 * Plugin Name: Gemini MCP Tools
 * Description: Integrates custom tools with AI Engine's Media Control Protocol.
 * Version: 1.0.7
 * Author: David Mania
 * Author URI: https://maniainc.com
 * Requires Toolkit: https://github.com/daviemania/gemini-for-wp-free
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
                'has_premium_version' => true,
                'has_addons'          => false,
                'has_paid_plans'      => true,
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

define( 'GEMINI_AI_TOOLKIT_PATH', ABSPATH . 'wp-content/gemini-ai-toolkit/' );

/**
 * Check if the required AI toolkit is installed
 */
function gemini_mcp_check_toolkit() {
    $toolkit_path = GEMINI_AI_TOOLKIT_PATH;
    
    // Check for package.json as indicator that toolkit exists
    if ( ! file_exists( $toolkit_path . 'package.json' ) ) {
        add_action( 'admin_notices', function() use ( $toolkit_path ) {
            $class = 'notice notice-error';
            $install_path = str_replace( ABSPATH, '', $toolkit_path );
            ?>
            <div class="<?php echo esc_attr( $class ); ?>">
                <h3>⚠️ Gemini MCP Tools: Required Toolkit Missing</h3>
                <p><strong>This plugin requires the Gemini AI Toolkit to function.</strong></p>
                
                <h4>Quick Installation:</h4>
                <ol style="margin-left: 20px;">
                    <li>Open terminal/command prompt</li>
                    <li>Navigate to: <code><?php echo esc_html( ABSPATH . 'wp-content/' ); ?></code></li>
                    <li>Run: <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;">git clone https://github.com/daviemania/gemini-for-wp-free.git gemini-ai-toolkit</code></li>
                    <li>Then: <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;">cd gemini-ai-toolkit && npm install</code></li>
                    <li>Refresh this page</li>
                </ol>
                
                <p style="padding: 10px; background: #fff3cd; border-left: 4px solid #ffc107;">
                    <strong>Note:</strong> The toolkit must be installed at: <code><?php echo esc_html( $toolkit_path ); ?></code>
                </p>
                
                <p><a href="https://github.com/daviemania/gemini-for-wp-free#installation" target="_blank" class="button button-secondary">View Full Installation Guide →</a></p>
            </div>
            <?php
        });
        return false;
    }
    
    // Optional: Check if node_modules exists (indicates npm install was run)
    if ( ! is_dir( $toolkit_path . 'node_modules' ) ) {
        add_action( 'admin_notices', function() use ( $toolkit_path ) {
            ?>
            <div class="notice notice-warning">
                <h3>⚠️ Gemini MCP Tools: Incomplete Toolkit Installation</h3>
                <p>The toolkit is installed but dependencies are missing.</p>
                <p>Please run: <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;">cd <?php echo esc_html( $toolkit_path ); ?> && npm install</code></p>
            </div>
            <?php
        });
        return false;
    }
    
    return true;
}

// Only load plugin functionality if toolkit is present
if ( ! gemini_mcp_check_toolkit() ) {
    return;
}

require_once plugin_dir_path(__FILE__) . "mcp.php";
require_once __DIR__ . "/freemius-gate.php";

// Initialize the MCP tools
function gemini_mcp_tools_init() {
    global $gemini_mcp_tools_mcp;
    $gemini_mcp_tools_mcp = new Gemini_MCP_Tools_MCP();
}
add_action("plugins_loaded", "gemini_mcp_tools_init");
