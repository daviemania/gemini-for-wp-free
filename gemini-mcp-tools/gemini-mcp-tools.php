<?php
/**
 * Plugin Name: Gemini MCP Tools
 * Description: Integrates custom tools with AI Engine's Media Control Protocol.
 * Version: 1.0.2
 * Author: David Mania
 * Author URI: https://maniainc.com
 * Requires Toolkit: https://github.com/daviemania/gemini-for-wp-free
 * Text Domain: gemini-mcp-tools
 */

// Security: Prevent direct access
if (!defined("ABSPATH")) {
    exit();
}

// Freemius Integration
if (!function_exists("gfw_fs")) {
    function gfw_fs()
    {
        global $gfw_fs;
        if (!isset($gfw_fs)) {
            // Activate multisite network integration.
            if (!defined("WP_FS__PRODUCT_22000_MULTISITE")) {
                define("WP_FS__PRODUCT_22000_MULTISITE", true);
            }
            // Include Freemius SDK.
            require_once dirname(__FILE__) . "/vendor/freemius/start.php";
            $gfw_fs = fs_dynamic_init([
                "id" => "22000",
                "slug" => "gemini-for-wp",
                "type" => "plugin",
                "public_key" => "pk_d2f0b07deef9f60a6a8400169b555",
                "is_premium" => true,
                "premium_suffix" => "Premium",
                "has_premium_version" => true,
                "has_addons" => false,
                "has_paid_plans" => true,
                "wp_org_gatekeeper" => 'OA7#BoRiBNqdf52FvzEf!!074aRLPs8fspif$7K1#4u4Csys1fQlCecVcUTOs2mcpeVHi#C2j9d09fOTvbC0HloPT7fFee5WdS3G',
                "trial" => [
                    "days" => 3,
                    "is_require_payment" => true,
                ],
                "menu" => [
                    "first-path" => "plugins.php",
                    "support" => false,
                ],
            ]);
        }
        return $gfw_fs;
    }

    // Init Freemius.
    gfw_fs();
    do_action("gfw_fs_loaded");
}

define("GEMINI_AI_TOOLKIT_PATH", ABSPATH . "wp-content/gemini-ai-toolkit/");
define("GEMINI_MCP_VERSION", "1.0.2");

/**
 * Check if the required AI toolkit is installed
 */
function gemini_mcp_check_toolkit()
{
    $toolkit_path = GEMINI_AI_TOOLKIT_PATH;

    // Check for package.json as indicator that toolkit exists
    if (!file_exists($toolkit_path . "package.json")) {
        add_action("admin_notices", function () use ($toolkit_path) {
            if (!current_user_can("activate_plugins")) {
                return;
            }

            $class = "notice notice-error";
            $install_path = str_replace(ABSPATH, "", $toolkit_path);
            ?>
            <div class="<?php echo esc_attr($class); ?>">
                <h3>⚠️ Gemini MCP Tools: Required Toolkit Missing</h3>
                <p><strong>This plugin requires the Gemini AI Toolkit to function.</strong></p>

                <h4>Quick Installation:</h4>
                <ol style="margin-left: 20px;">
                    <li>Open terminal/command prompt</li>
                    <li>Navigate to: <code><?php echo esc_html(ABSPATH . "wp-content/"); ?></code></li>
                    <li>Run: <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;">git clone https://github.com/daviemania/gemini-for-wp-free.git gemini-ai-toolkit</code></li>
                    <li>Then: <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;">cd gemini-ai-toolkit && npm install</code></li>
                    <li>Refresh this page</li>
                </ol>

                <p style="padding: 10px; background: #fff3cd; border-left: 4px solid #ffc107;">
                    <strong>Note:</strong> The toolkit must be installed at: <code><?php echo esc_html($toolkit_path); ?></code>
                </p>

                <p><a href="https://github.com/daviemania/gemini-for-wp-free#installation" target="_blank" class="button button-secondary">View Full Installation Guide →</a></p>
            </div>
            <?php
        });
        return false;
    }

    // Optional: Check if node_modules exists
    if (!is_dir($toolkit_path . "node_modules")) {
        add_action("admin_notices", function () use ($toolkit_path) {
            if (!current_user_can("activate_plugins")) {
                return;
            } ?>
            <div class="notice notice-warning">
                <h3>⚠️ Gemini MCP Tools: Incomplete Toolkit Installation</h3>
                <p>The toolkit is installed but dependencies are missing.</p>
                <p>Please run: <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;">cd <?php echo esc_html($toolkit_path); ?> && npm install</code></p>
            </div>
            <?php
        });
        return false;
    }

    return true;
}

// Security: Add capability checks and nonce verification
function gemini_mcp_verify_security($tool, $args)
{
    // 1. Verify user authentication
    if (!is_user_logged_in()) {
         return [
            "success" => false,
            "error" => "Authentication required.",
        ];
    }

    // 2. Verify nonce if provided (Optional based on implementation)
    if (isset($args["nonce"])) {
        if (!wp_verify_nonce($args["nonce"], "mcp_tool_execution")) {
            return [
                "success" => false,
                "error" => "Security verification failed (Invalid Nonce).",
            ];
        }
    }

    // 3. Rate limiting
    $current_user_id = get_current_user_id();
    $transient_key = "mcp_rate_limit_" . $current_user_id;
    if (get_transient($transient_key)) {
        return [
            "success" => false,
            "error" => "Rate limit exceeded. Please wait a few seconds.",
        ];
    }
    set_transient($transient_key, true, 2); // 2 second rate limit

    return true;
}

// Only load plugin functionality if toolkit is present
if (!gemini_mcp_check_toolkit()) {
    return;
}

// Load core files
require_once plugin_dir_path(__FILE__) . "mcp.php";
require_once plugin_dir_path(__FILE__) . "freemius-gate.php";

// Initialize the MCP tools
function gemini_mcp_tools_init()
{
    global $gemini_mcp_tools_mcp;
    $gemini_mcp_tools_mcp = new Gemini_MCP_Tools_MCP();
}
add_action("plugins_loaded", "gemini_mcp_tools_init");

// Security: Clean up on deactivation
function gemini_mcp_deactivate()
{
    // Clear any transients
    $users = get_users(["fields" => ["ID"]]);
    foreach ($users as $user) {
        delete_transient("mcp_rate_limit_" . $user->ID);
    }
}
register_deactivation_hook(__FILE__, "gemini_mcp_deactivate");
