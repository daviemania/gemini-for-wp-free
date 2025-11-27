<?php
/**
 * Freemius Freemium Gate for Gemini MCP Tools
 * Free: 37 MCP CRUD tools
 * Premium: Ollama/OpenRouter/Exa/smart-org ($29/yr)
 */

// Freemius SDK Loader (download from freemius.com/wordpress-sdk)
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Freemius' ) ) {
    require_once dirname( __FILE__ ) . '/freemius/wordpress-sdk/start.php';  // SDK folder
    $fs = fs_dynamic_init();
} else {
    $fs = Freemius::get_instance_for_plugin();
}

$fs->set_basename( false, __FILE__ );
$fs->add_filter( 'connect_message_on_update', '__return_false' );

define( 'FREEMIUM_PLAN_ID', 48051 );  // Freemius Plan ID (monthly $29/yr $275.88/lt $829.99)

$FREE_TOOLS = [ 'wp_list_posts', 'wp_create_post', 'wp_update_post', 'wp_delete_post', /* +33 MCP CRUD */ ];
$PREMIUM_TOOLS = [ 'ollama_chat', 'exa_search', 'openrouter_call', 'smart_folder_organize' ];

function freemius_gate_mcp_call( $method ) {
    global $fs;
    if ( in_array( $method, $PREMIUM_TOOLS ) && ! $fs->is_premium() ) {
        return new WP_Error( 'freemium_required', 'Premium license required for ' . $method . '. Upgrade: ' . $fs->get_upgrade_url() );
    }
    return true;
}

add_filter( 'pre_mcp_call', 'freemius_gate_mcp_call' );  // Hook to MCP endpoint in mcp.php