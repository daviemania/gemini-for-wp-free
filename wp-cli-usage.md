# WP-CLI Usage Guide

This document outlines common WP-CLI commands and best practices for interacting with the WordPress multisite environment.

## General Usage

Always use the `wp` alias (configured in ~/.zshrc; see Zsh Alias section) or the full golden template to ensure commands target the correct WordPress installation safely (WC-safe).

**Use the `wp` alias (see Zsh Alias section) or full golden template below for WC-safe execution.**

## Common Commands

### Site Management

- **List all subsites:**
  ```bash
  WP_PATH=/opt/bitnami/wordpress WP_CLI=/opt/bitnami/wp-cli/bin/wp WP_CLI_DISABLE_AUTOLOAD=1 sudo -u bitnami "$WP_CLI" --path="$WP_PATH" --allow-root --skip-plugins --skip-themes site list --field=url
  ```

### Post Management

- **List posts for a specific subsite:**
  ```bash
  WP_PATH=/opt/bitnami/wordpress WP_CLI=/opt/bitnami/wp-cli/bin/wp WP_CLI_DISABLE_AUTOLOAD=1 sudo -u bitnami "$WP_CLI" --path="$WP_PATH" --allow-root --skip-plugins --skip-themes post list --url=<subsite-url>
  ```

- **Update a post (example):**
  ```bash
  WP_PATH=/opt/bitnami/wordpress WP_CLI=/opt/bitnami/wp-cli/bin/wp WP_CLI_DISABLE_AUTOLOAD=1 sudo -u bitnami "$WP_CLI" --path="$WP_PATH" --allow-root --skip-plugins --skip-themes post update <post-id> --post_title="New Title" --post_content="New content" --post_status="pending" --blog_id=<blog-id>
  ```

### Plugin Management

- **List installed plugins:**
  ```bash
  WP_PATH=/opt/bitnami/wordpress WP_CLI=/opt/bitnami/wp-cli/bin/wp WP_CLI_DISABLE_AUTOLOAD=1 sudo -u bitnami "$WP_CLI" --path="$WP_PATH" --allow-root --skip-plugins --skip-themes plugin list
  ```

- **Activate a plugin (example):**
  ```bash
  WP_PATH=/opt/bitnami/wordpress WP_CLI=/opt/bitnami/wp-cli/bin/wp WP_CLI_DISABLE_AUTOLOAD=1 sudo -u bitnami "$WP_CLI" --path="$WP_PATH" --allow-root --skip-plugins --skip-themes plugin activate <plugin-name>
  ```

- **Deactivate a plugin (example):**
  ```bash
  WP_PATH=/opt/bitnami/wordpress WP_CLI=/opt/bitnami/wp-cli/bin/wp WP_CLI_DISABLE_AUTOLOAD=1 sudo -u bitnami "$WP_CLI" --path="$WP_PATH" --allow-root --skip-plugins --skip-themes plugin deactivate <plugin-name>
  ```

### User Management

- **List users:**
  ```bash
  WP_PATH=/opt/bitnami/wordpress WP_CLI=/opt/bitnami/wp-cli/bin/wp WP_CLI_DISABLE_AUTOLOAD=1 sudo -u bitnami "$WP_CLI" --path="$WP_PATH" --allow-root --skip-plugins --skip-themes user list
  ```

### Database Management

- **Run a direct database query (use with extreme caution on live sites):**
  ```bash
  WP_PATH=/opt/bitnami/wordpress WP_CLI=/opt/bitnami/wp-cli/bin/wp WP_CLI_DISABLE_AUTOLOAD=1 sudo -u bitnami "$WP_CLI" --path="$WP_PATH" --allow-root --skip-plugins --skip-themes db query "SELECT option_value FROM wp_options WHERE option_name = 'blogname';"
  ```

## WC-Safe Optimal Flags (Avoid WooCommerce Fatals)

| Flag/Env | Purpose | WC Impact |
|----------|---------|-----------|
| `--path=/opt/bitnami/wordpress` | Targets Bitnami WP. | N/A |
| `--allow-root` | Runs as root/sudo (Bitnami req). | N/A |
| `--skip-plugins --skip-themes` | **Skips WC load** (prevents after_wp_load REST fatal). | ✅ Core WP only |
| `WP_CLI_DISABLE_AUTOLOAD=1` | No Composer/WC deps (no REST_Server). | ✅ No early hooks |
| `sudo -u bitnami` | Correct perms (bitnami:daemon post-permissions_fix). | ✅ Writable dirs |
| `WP_PATH=/opt/bitnami/wordpress WP_CLI=/opt/bitnami/wp-cli/bin/wp` | Explicit paths. | ✅ Stable |

**Golden Template**:
```bash
WP_PATH=/opt/bitnami/wordpress WP_CLI=/opt/bitnami/wp-cli/bin/wp WP_CLI_DISABLE_AUTOLOAD=1 sudo -u bitnami "$WP_CLI" --path="$WP_PATH" --allow-root --skip-plugins --skip-themes <command> [args]
```

## Zsh Alias (Add to ~/.zshrc)
```bash
alias wp='WP_PATH=/opt/bitnami/wordpress WP_CLI=/opt/bitnami/wp-cli/bin/wp WP_CLI_DISABLE_AUTOLOAD=1 sudo -u bitnami "$WP_CLI" --path="$WP_PATH" --allow-root --skip-plugins --skip-themes'
# Reload: source ~/.zshrc
# Usage: wp site list
```

## Important Notes

- **Full WC-safe template + alias**: See "WC-Safe Optimal Flags" above—use `wp` alias in zsh.
- **Multisite**: Add `--url=<subsite>` or `--network` (e.g., `wp plugin list --network`).
- **Verbose/debug**: Add `-vvv` for troubleshooting (e.g., `wp eval '...' -vvv`).
- **Live Site Caution**: Extreme caution on mods—backup first (`wp db export`).
