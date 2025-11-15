# WordPress Development Setup

This document outlines the best practices and methodologies for WordPress development in this environment.

## Plugin Conflict Resolution

When integrating new plugins, it's crucial to identify and resolve potential conflicts with existing plugins. This section details the methodology for resolving such conflicts, using the BetterLinks and Rank Math integration as an example.

### Methodology

1. **Analyze Plugin Features:** Before installing a new plugin, thoroughly analyze its features to identify any potential overlap with existing plugins. In our example, we identified that both BetterLinks and Rank Math have redirection modules.

2. **Use `wp-cli` for Plugin Management:** Use `wp plugin list` to get a comprehensive list of all installed plugins. This is essential for identifying potential conflicts.

3. **Isolate and Test:** Whenever possible, test new plugins in a staging environment. If a staging environment is not available, conduct targeted tests to verify the plugin's functionality and its impact on the site.

4. **Resolve Conflicts:** When a conflict is identified, you have two primary options:
   - **Disable Conflicting Features:** The simplest solution is to disable the conflicting feature in one of the plugins. In our example, we could have disabled the redirection module in Rank Math.
   - **Create a Compatibility Layer:** For more complex scenarios, you can create a compatibility layer, such as a custom mu-plugin, to make the plugins coexist.

### Example: BetterLinks and Rank Math Compatibility Mu-Plugin

To resolve the conflict between BetterLinks and Rank Math, we created the following mu-plugin. This plugin uses the `wp_redirect_location` filter to prevent Rank Math from interfering with BetterLinks redirects.

**File:** `/wp-content/mu-plugins/rankmath-betterlinks-compat.php`

```php
<?php
/**
 * Plugin Name: Rank Math & BetterLinks Compatibility
 * Description: Prevents Rank Math from interfering with BetterLinks redirects.
 * Version: 1.0
 * Author: Gemini
 */

add_filter( 'wp_redirect_location', 'btl_rank_math_compatibility', 10, 2 );

function btl_rank_math_compatibility( $location, $status ) {
    // Get the path from the requested URL
    $request_path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';

    // If the path is empty, do nothing.
    if ( empty( $request_path ) ) {
        return $location;
    }

    // Check if a post with this slug exists and if it's a BetterLinks post type.
    $post = get_page_by_path( trim( $request_path, '/' ), OBJECT, 'betterlinks' );

    // If it is a BetterLinks post, prevent the redirect.
    if ( $post ) {
        return false;
    }

    // Otherwise, return the original location.
    return $location;
}
```

### WP-CLI Best Practices

- When working with `wp-cli`, always use the following flags to ensure commands run correctly in this environment:
  - `--path=/opt/bitnami/wordpress/`
  - `--allow-root`
  - `--skip-plugins` (especially when installing or activating plugins to avoid conflicts during the process)
