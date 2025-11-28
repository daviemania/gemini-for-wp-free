<?php
/**
 * Freemius Freemium Gate for Gemini MCP Tools
 * Free: 37 MCP CRUD tools
 * Premium: Ollama/OpenRouter/Exa/smart-org/dolphin ($29/yr)
 */

if (!defined("ABSPATH")) {
    exit();
}

// Use main plugin's Freemius instance
if (function_exists("gfw_fs")) {
    $gfw_fs = gfw_fs();
} else {
    return; // Exit if Freemius not available
}

define("FREEMIUM_PLAN_ID", 36767);

$GLOBALS["PREMIUM_TOOLS"] = [
    "ollama_chat",
    "exa_search",
    "openrouter_call",
    "smart_folder_organize",
    "composed_exploring_dolphin",
    "claude_code",
    "github_chat",
];

function freemius_gate_mcp_call($result, $tool, $args, $id)
{
    if (!function_exists("gfw_fs")) {
        return $result;
    }

    $gfw_fs = gfw_fs();
    $premium_tools = $GLOBALS["PREMIUM_TOOLS"];

    // Check if this is a premium tool and user doesn't have premium
    if (in_array($tool, $premium_tools) && !$gfw_fs->is_premium()) {
        return [
            "success" => false,
            "error" =>
                "Premium license required for " .
                $tool .
                ". Upgrade: " .
                $gfw_fs->get_upgrade_url(),
            "premium_required" => true,
        ];
    }

    return $result;
}

// Hook to MCP endpoint with proper parameters
add_filter("mwai_mcp_callback", "freemius_gate_mcp_call", 5, 4);
