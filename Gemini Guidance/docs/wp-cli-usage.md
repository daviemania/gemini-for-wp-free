# WP-CLI Usage Guide

This document outlines common WP-CLI commands and best practices for interacting with the WordPress multisite environment.

## General Usage

Always use the `wpb` alias (or `wp --path=/opt/bitnami/wordpress/`) to ensure commands target the correct WordPress installation.

```bash
wpb <command> [options]
```

## Common Commands

### Site Management

- **List all subsites:**
  ```bash
  sudo wpb site list --skip-plugins
  ```

### Post Management

- **List posts for a specific subsite:**
  ```bash
  sudo wpb post list --url=<subsite-url> --skip-plugins
  ```

- **Update a post (example):**
  ```bash
  sudo wpb post update <post-id> --post_title="New Title" --post_content="New content" --post_status="pending" --blog_id=<blog-id> --skip-plugins
  ```

### Plugin Management

- **List installed plugins:**
  ```bash
  sudo wpb plugin list --skip-plugins
  ```

- **Activate a plugin (example):**
  ```bash
  sudo wpb plugin activate <plugin-name> --skip-plugins
  ```

- **Deactivate a plugin (example):**
  ```bash
  sudo wpb plugin deactivate <plugin-name> --skip-plugins
  ```

### User Management

- **List users:**
  ```bash
  sudo wpb user list --skip-plugins
  ```

### Database Management

- **Run a direct database query (use with extreme caution on live sites):**
  ```bash
  sudo wpb db query "SELECT option_value FROM wp_options WHERE option_name = 'blogname';" --skip-plugins
  ```

## Important Notes

- **`--skip-plugins`**: Always use this flag to prevent conflicts with active plugins, especially when performing critical operations.
- **`--allow-root`**: Required when running WP-CLI commands as the root user.
- **`sudo`**: Many WP-CLI commands require `sudo` due to file permissions in the Bitnami stack.
- **Live Site Caution**: Exercise extreme caution when running any commands that modify data on a live site. Prefer read-only operations unless explicitly performing a development task that requires modification and you understand the implications.
