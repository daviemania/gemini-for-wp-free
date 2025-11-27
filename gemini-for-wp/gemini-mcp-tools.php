<?php
/**
 * Plugin Name: Gemini MCP Tools
 * Description: Integrates custom tools with AI Engine's Media Control Protocol.
 * Version: 1.0.0
 * Author: Gemini
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

require_once plugin_dir_path( __FILE__ ) . 'mcp.php';

// Initialize the MCP tools
function gemini_mcp_tools_init() {
    global $gemini_mcp_tools_mcp;
    $gemini_mcp_tools_mcp = new Gemini_MCP_Tools_MCP();
}
add_action( 'plugins_loaded', 'gemini_mcp_tools_init' );
