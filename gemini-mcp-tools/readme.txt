=== Gemini MCP Tools ===
Contributors: daviemania
Tags: mcp, ai, freemius, gemini, wordpress, ollama, exa, openrouter
Requires at least: 6.0
Tested up to: 6.6
Stable tag: 1.0.2
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
    *   `composed_exploring_dolphin`: Executes advanced automation scripts.
    *   `claude_code`: Performs code-related tasks using Anthropic's Claude AI.
    *   `github_chat`: Initiates AI-powered chat integrated with GitHub for code assistance.

**Seamless Integration & Security:**

*   Designed for robust integration with AI Engine WP, utilizing its MCP endpoint for secure and efficient communication.
*   **Freemius Integration (40/60 Model):**
    *   **Free Tier:** Enjoy full access to all 37 WordPress CRUD MCP tools.
    *   **Premium Tier:** Access advanced AI features (ollama_chat, exa_search, openrouter_call, smart_folder_organize, composed_exploring_dolphin, claude_code, github_chat) with a valid Freemius license. Premium features are intelligently gated, returning a `WP_Error` with an upgrade URL if a license is not active, ensuring a smooth upgrade path.

**Requirements:**

*   WordPress 6.0+
*   PHP 7.4+
*   AI Engine WP plugin (for MCP endpoint functionality)
*   **Gemini AI Toolkit** (required for premium features - see Installation section)
*   Node.js 18+ (for AI Toolkit features)

== Installation ==

**Step 1: Install the Plugin**

1. Upload the plugin ZIP to WP Admin → Plugins → Add New → Upload Plugin.
2. Click "Install Now" and then "Activate Plugin".
3. Connect with Freemius when prompted (or skip for free features only).

**Step 2: Install Required Gemini AI Toolkit**

⚠️ **IMPORTANT:** Premium AI features require the separate Gemini AI Toolkit to be installed.

1. Open your terminal/command prompt
2. Navigate to your WordPress content directory:
   ```
   cd /path/to/wordpress/wp-content/
   ```
3. Clone the toolkit (must be named `gemini-ai-toolkit`):
   ```
   git clone https://github.com/daviemania/gemini-for-wp-free.git gemini-ai-toolkit
   ```
4. Install toolkit dependencies:
   ```
   cd gemini-ai-toolkit
   npm install
   ```
5. Verify installation: The toolkit must be located at `wp-content/gemini-ai-toolkit/`

**Step 3: Activate Premium Features (Optional)**

1. Purchase a license from your Freemius dashboard
2. Enter your license key in the plugin settings
3. Premium AI tools will now be available

**Developer Mode (Optional - wp-config.php):**

```php
define( 'WP_FS__DEV_MODE', true );
define( 'WP_FS__SKIP_EMAIL_ACTIVATION', true );
define( 'WP_FS__gemini-for-wp_SECRET_KEY', 'your_secret_key' );
```

== Gemini AI Toolkit Details ==

The Gemini AI Toolkit is a companion Node.js application that powers the premium AI features of this plugin.

**Toolkit Location:**
The toolkit must be installed at: `wp-content/gemini-ai-toolkit/`

**What It Provides:**
*   Ollama integration for local AI models
*   Exa search capabilities
*   OpenRouter API access
*   Smart folder organization
*   Advanced automation scripts
*   Claude Code integration
*   GitHub Chat functionality

**Server Requirements:**
*   Node.js 18 or higher
*   npm or yarn package manager
*   Git (for installation)

**Troubleshooting:**
If you see a "Required Toolkit Missing" error in WordPress admin after activating the plugin, ensure:
1. The toolkit is cloned to the correct location
2. The folder is named exactly `gemini-ai-toolkit`
3. You've run `npm install` inside the toolkit directory
4. Your server has Node.js installed

== Frequently Asked Questions ==

= Do I need the AI Toolkit for free features? =

No, the 37 free WordPress CRUD MCP tools work without the toolkit. The toolkit is only required for premium AI features (Ollama, Exa, OpenRouter, Smart Folder, etc.).

= How does premium gating work? =

The `mwai_mcp_callback` filter checks `$gfw_fs()->is_premium()`. Free features work directly, while premium features return a `WP_Error` with a Freemius upgrade URL if no active license is detected.

= Can I use premium features from CLI? =

Yes, `package.json` scripts gate `ollamachat`, `openrouterchat`, and `organize:smart` via `check-license.js` (requires `FREEMIUM_LICENSE` environment variable).

= What if I can't install Git or Node.js? =

Unfortunately, premium AI features require Node.js on your server. Consider using a hosting provider that supports Node.js, or contact your hosting support to have it installed.

= Where do I get support? =

For free features, use the WordPress.org support forums. Premium license holders can access priority support through their Freemius dashboard.

= Why is the toolkit a separate installation? =

Separating the Node.js toolkit from the WordPress plugin allows for:
*   Easier updates and version management
*   Better code organization
*   Flexibility for developers who may want to customize the toolkit
*   Compliance with WordPress.org plugin directory requirements

== Screenshots ==

1. Freemius Connect/License UI
2. Premium tool blocked with upgrade prompt
3. Free MCP tool success response
4. Toolkit missing error notice with installation instructions

== Changelog ==

= 1.0.2 =
*   **Enhancement:** Improved installation documentation for Gemini AI Toolkit
*   **Enhancement:** Added comprehensive admin notices for missing toolkit
*   **Enhancement:** Added toolkit dependency checks on plugin activation
*   **Update:** Refined README.txt with clearer installation steps
*   **Fix:** Improved error messaging for missing dependencies

= 1.0.0 =
*   Fatal fix (test ZIP SDK inclusion; production Freemius injection)
*   readme.txt WordPress.org standard format
*   Premium gating enforced (ollama_chat/exa_search/openrouter_call/smart_folder_organize)

= 1.0.0 =
*   **Feature:** Integrated Freemius WordPress SDK to manage premium features and licensing
*   **Fix:** Removed unnecessary local development files and folders from repository
*   **Enhancement:** Improved readme.txt description for clarity and feature highlights
*   **Update:** Plugin version synchronized to 1.0.7, author details updated

See CHANGELOG.md for full version history.

== Upgrade Notice ==

= 1.0.2 =
This version includes improved installation documentation and better error handling for the required Gemini AI Toolkit dependency. If you're upgrading from an earlier version, please ensure the toolkit is properly installed at wp-content/gemini-ai-toolkit/.

== Additional Resources ==

*   **Toolkit Repository:** https://github.com/daviemania/gemini-for-wp-free
*   **Installation Guide:** See Installation section above
*   **Author Website:** https://maniainc.com
*   **Support:** WordPress.org forums (free) or Freemius dashboard (premium)
