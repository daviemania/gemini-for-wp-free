# Geekline Feed Plugin

This file contains the analysis of the Geekline Feed plugin.

## Plugin Architecture
- **Structure:** The plugin is object-oriented and uses a singleton pattern. It's well-structured with separate files for different functionalities.
- **Dependencies:** It loads dependencies in a specific order: `utils.php`, `fetcher.php`, `rest-api.php`, `admin.php`, `archive-manager.php`.
- **Hooks:** It uses a wide range of WordPress hooks for activation, deactivation, uninstall, enqueueing scripts and styles, AJAX handling, shortcodes, cron jobs, and more.

## Functionality
- **Data Source:** Fetches posts from the Bluesky social network on an hourly basis using a cron job.
- **Storage:** Stores the posts in a custom database table (`wp_geekline_posts`).
- **Display:** Displays the feed on the site using a custom page template (`page-geekline.php`) and a shortcode (`[geekline_feed]`).
- **Features:** Includes features for reposting, commenting (via Disqus), and social sharing.
- **Archives:** Manages daily archives of the feed.
- **Admin:** Has a dedicated admin page for settings.

## Key Files
- **`the-geekline-feed.php`**: The main plugin file.
- **`admin.php`**: Handles the admin settings.
- **`archive-manager.php`**: Manages the daily archives.
- **`fetcher.php`**: Fetches posts from the Fediverse.
- **`rest-api.php`**: Handles custom REST API endpoints.
- **`page-templates/`**: Contains the templates for displaying the feed.
