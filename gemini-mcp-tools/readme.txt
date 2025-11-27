=== Gemini MCP Tools ===
Contributors: daviemania
Tags: mcp, ai, freemius, gemini, wordpress, ollama, exa, openrouter
Requires at least: 6.0
Tested up to: 6.6
Stable tag: 1.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Freemium MCP tools for AI Engine WP plugin. 37 free WP CRUD tools; premium Ollama/OpenRouter/Exa/smart-folder ($29/mo Freemius Plan 36767).

== Description ==
Gemini MCP Tools integrates 37 free WordPress MCP CRUD tools (posts/users/media/comments/taxonomies/options) with AI Engine WP.

**Freemium Model (40/60):**
- **Free**: All 37 WP CRUD MCP tools (wp_list_posts, wp_create_post, etc.).
- **Premium**: ollama_chat, exa_search, openrouter_call, smart_folder_organize (gated via Freemius Plan 36767).

Enforcement: pre_mcp_call hook returns WP_Error for premium without license + upgrade URL.

Requires AI Engine WP plugin (MCP endpoint).

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
= 1.0.3 =
* Fatal fix (test ZIP SDK incl.; prod Freemius inject)
* readme.txt WP.org standard
* Premium gating enforced (ollama_chat/exa_search/openrouter_call/smart_folder_organize)

= 1.0.2 =
* CHANGELOG v1.0.3 prep

See CHANGELOG.md full history.

== Upgrade Notice ==
= 1.0.3 =
SDK auto-injected by Freemius (prod ZIP lean).

Product 22000, Freemius Plan 36767 ($29/mo).