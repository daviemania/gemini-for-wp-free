=== Gemini MCP Tools ===
Contributors: daviemania
Tags: mcp, ai, freemius, gemini, wordpress, ollama, exa, openrouter
Requires at least: 6.0
Tested up to: 6.6
Stable tag: 1.0.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Freemium MCP tools for AI Engine WP plugin. 37 free WP CRUD tools; premium Ollama/OpenRouter/Exa/smart-folder ($29/mo Freemius Plan 36767).

== Description ==
Unlock the full potential of your WordPress site with **Gemini MCP Tools**, a powerful plugin that seamlessly integrates with AI Engine WP via its Media Control Protocol (MCP). This plugin empowers developers and content creators with an extensive suite of tools for dynamic WordPress management and cutting-edge AI capabilities.

**Key Features:**

*   **37 Free WordPress CRUD MCP Tools:** Gain direct control over your WordPress content, users, media, comments, taxonomies, and options with a comprehensive set of free MCP functions. Perform actions like `wp_list_posts`, `wp_create_post`, and many more.
*   **Freemium AI-Powered Premium Tools:** Elevate your workflow with advanced AI functionalities, gated securely via Freemius:
    *   `ollama_chat`: Engage in powerful conversational AI.
    *   `exa_search`: Perform intelligent, context-aware web searches.
    *   `openrouter_call`: Access various AI models through the OpenRouter API.
    *   `smart_folder_organize`: Automate content organization with AI-driven smart folders.

**Seamless Integration & Security:**

*   Designed for robust integration with AI Engine WP, utilizing its MCP endpoint for secure and efficient communication.
*   **Freemius Integration (40/60 Model):**
    *   **Free Tier:** Enjoy full access to all 37 WordPress CRUD MCP tools.
    *   **Premium Tier:** Access advanced AI features (ollama_chat, exa_search, openrouter_call, smart_folder_organize) with a valid Freemius license. Premium features are intelligently gated, returning a `WP_Error` with an upgrade URL if a license is not active, ensuring a smooth upgrade path.

**Requirements:**

*   AI Engine WP plugin must be installed and active for MCP endpoint functionality.

Empower your WordPress site with intelligence and efficiency. Upgrade to Premium for the full suite of AI capabilities!

== Installation ==
1. Upload ZIP to WP Admin → Plugins → Add New.
2. Activate.
3. Connect Freemius (dev mode instant if constants set).
4. License key from Freemius dashboard.
5. Test: npm run chatwmcp (free ok, premium upgrade prompt).

Dev Mode (wp-config.php):
```
define( 'WP_FS__DEV_MODE', true );
define( 'WP_FS__SKIP_EMAIL_ACTIVATION', true );
define( 'WP_FS__gemini-for-wp_SECRET_KEY', 'your_secret_key' );
```

== Frequently Asked Questions ==
= How does gating work? =
pre_mcp_call filter checks $gfw_fs()->is_premium() – free direct, premium WP_Error + Freemius upgrade.

= CLI Premium? =
package.json gates ollamachat/openrouterchat/organize:smart via check-license.js (FREEMIUM_LICENSE env).

== Screenshots ==
1. Freemius Connect/License UI
2. Premium tool blocked (upgrade nudge)
3. Free MCP success

== Changelog ==
= 1.0.7 =
*   **Feature:** Integrated Freemius WordPress SDK to manage premium features and licensing.
*   **Fix:** Removed unnecessary local development files and folders from the repository to streamline the distribution.
*   **Enhancement:** Improved `readme.txt` description for clarity and feature highlights.
*   **Update:** Plugin version synchronized to 1.0.7, author details updated.

= 1.0.3 =
* Fatal fix (test ZIP SDK incl.; prod Freemius inject)
* readme.txt WP.org standard
* Premium gating enforced (ollama_chat/exa_search/openrouter_call/smart_folder_organize)

= 1.0.2 =
* CHANGELOG v1.0.3 prep

See CHANGELOG.md full history.

== Upgrade Notice ==
= 1.0.7 =
This release includes the official Freemius WordPress SDK, enabling robust premium feature gating and streamlined licensing. Several unnecessary development files have been removed from the distribution for a cleaner package. Please ensure your AI Engine WP plugin is up to date for optimal compatibility.