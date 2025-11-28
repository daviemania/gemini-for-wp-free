<?php
/**
 * Freemius Freemium Gate for Gemini MCP Tools
 * Free: 37 MCP CRUD tools
 * Premium: Ollama/OpenRouter/Exa/smart-org/dolphin ($29/yr)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$gfw_fs = gfw_fs(); // Use main plugin's Freemius instance

define( 'FREEMIUM_PLAN_ID', 36767 );  // Freemius Premium Plan ID (overall premium: monthly $29/yr $275.88/lt $829.99)

$FREE_TOOLS = [ 'wp_list_posts', 'wp_create_post', 'wp_update_post', 'wp_delete_post', /* +33 MCP CRUD */ ];
$PREMIUM_TOOLS = [ 'ollama_chat', 'exa_search', 'openrouter_call', 'smart_folder_organize', 'composed_exploring_dolphin', 'claude_code', 'github_chat' ];

function freemius_gate_mcp_call( $method ) {
    global $gfw_fs;
    if ( in_array( $method, $PREMIUM_TOOLS ) && ! $gfw_fs->is_premium() ) {
        return new WP_Error( 'freemium_required', 'Premium license required for ' . $method . '. Upgrade: ' . $gfw_fs->get_upgrade_url() );
    }
    return true;
}

add_filter( 'mwai_mcp_callback', 'freemius_gate_mcp_call' );  // Hook to MCP endpoint in mcp.php
