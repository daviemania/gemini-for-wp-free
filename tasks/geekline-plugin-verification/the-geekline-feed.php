<?php
/**
 * Plugin Name: The Geekline Feed
 * Plugin URI: https://maniainc.com/thegeekline
 * Description: Live tech feed from the fediverse with reposts, comments, social interactions, and archive management.
 * Version: 1.2.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: David Mania
 * Author URI: https://maniainc.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: the-geekline-feed
 * Domain Path: /languages
 * 
 * @package Geekline
 * @since 1.0.0
 */

// Security: Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin Constants
 */
define('TGF_VERSION', '1.2.0');
define('TGF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TGF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TGF_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('TGF_MIN_PHP_VERSION', '7.4');
define('TGF_MIN_WP_VERSION', '5.8');

// Text Domain for internationalization
if (!defined('TGF_TEXT_DOMAIN')) {
    define('TGF_TEXT_DOMAIN', 'geekline-feed');
}

/**
 * Main Plugin Class
 * 
 * @since 1.0.0
 */
class Geekline_Feed_Plugin {
    
    /**
     * Single instance of the class
     * 
     * @var Geekline_Feed_Plugin|null
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     * 
     * @return Geekline_Feed_Plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Check requirements before initializing
        if (!$this->check_requirements()) {
            return;
        }
        
        $this->load_dependencies();
        $this->init_hooks();
        
        // Log initialization
       //  error_log('Geekline Feed Plugin initialized (v' . TGF_VERSION . ')');
    }
    
    /**
     * Check system requirements
     * 
     * @return bool
     */
    private function check_requirements() {
        $errors = [];
        
        // Check PHP version
        if (version_compare(PHP_VERSION, TGF_MIN_PHP_VERSION, '<')) {
            $errors[] = sprintf(
                esc_html__('The Geekline Feed requires PHP %s or higher. You are running PHP %s.', TGF_TEXT_DOMAIN),
                TGF_MIN_PHP_VERSION,
                PHP_VERSION
            );
        }
        
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), TGF_MIN_WP_VERSION, '<')) {
            $errors[] = sprintf(
                esc_html__('The Geekline Feed requires WordPress %s or higher. You are running WordPress %s.', TGF_TEXT_DOMAIN),
                TGF_MIN_WP_VERSION,
                get_bloginfo('version')
            );
        }
        
        // Display errors if any
        if (!empty($errors)) {
            add_action('admin_notices', function() use ($errors) {
                foreach ($errors as $error) {
                    printf(
                        '<div class="error"><p>%s</p></div>',
                        esc_html($error)
                    );
                }
            });
            
            // Deactivate plugin if already active
            if (function_exists('deactivate_plugins')) {
                deactivate_plugins(TGF_PLUGIN_BASENAME);
            }
            
            return false;
        }
        
        return true;
    }
    
    /**
 * Load required files with error handling
 */
private function load_dependencies() {
    $files = [
        'utils.php',
        'fetcher.php',        // Must be loaded before rest-api.php and archive-manager.php
        'rest-api.php',
        'admin.php',
        'archive-manager.php'
    ];
    
    foreach ($files as $file) {
        $file_path = TGF_PLUGIN_DIR . $file;
        if (file_exists($file_path)) {
            require_once $file_path;
        } else {
            // Don't throw fatal error, just log it
            error_log('Geekline Feed: Missing required file: ' . $file);
            // If it's a critical file, we might need to deactivate
            if (in_array($file, ['fetcher.php', 'utils.php'])) {
                add_action('admin_notices', function() use ($file) {
                    echo '<div class="error"><p>' . 
                         sprintf(esc_html__('Geekline Feed critical file missing: %s. Plugin may not function correctly.', TGF_TEXT_DOMAIN), esc_html($file)) . 
                         '</p></div>';
                });
            }
        }
    }
}
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Activation/Deactivation
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        register_uninstall_hook(__FILE__, [__CLASS__, 'uninstall']);
		
		// ADD THESE NEW HOOKS FOR TEMPLATE LOADING
        add_filter('theme_page_templates', [$this, 'register_page_template']);
        add_filter('template_include', [$this, 'load_page_template']);
        
        // Scripts and Styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // AJAX Actions
        add_action('wp_ajax_tgf_repost', [$this, 'handle_repost']);
        add_action('wp_ajax_nopriv_tgf_repost', [$this, 'handle_repost']);
        
        // Shortcodes
        add_shortcode('geekline_feed', [$this, 'render_feed_shortcode']);
        
        // Load text domain
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        
        // Add settings link to plugins page
        add_filter('plugin_action_links_' . TGF_PLUGIN_BASENAME, [$this, 'add_settings_link']);
        
        // Cron jobs for maintenance
        add_action('tgf_daily_maintenance', [$this, 'run_daily_maintenance']);
        // Schedule hourly post fetching
        if (!wp_next_scheduled('tgf_fetch_posts_hourly')) {
            wp_schedule_event(time(), 'hourly', 'tgf_fetch_posts_hourly');
        }
        // Add rewrite rules on init
        add_action('init', [$this, 'add_rewrite_rules']);
        
        // Plugin row meta
        add_filter('plugin_row_meta', [$this, 'add_plugin_row_meta'], 10, 2);
    }
    
	/**
 * Register custom page template
 * 
 * @param array $templates Existing page templates
 * @return array Modified templates array
 */
public function register_page_template($templates) {
    $templates['page-geekline.php'] = __('Geekline Feed Page', TGF_TEXT_DOMAIN);
    return $templates;
}

/**
 * Load custom page template from plugin
 * 
 * @param string $template The path of the template to include
 * @return string Modified template path
 */
public function load_page_template($template) {
    global $post;
    
    // Check if we're on a page using our template
    if ($post && get_page_template_slug($post->ID) === 'page-geekline.php') {
        $plugin_template = TGF_PLUGIN_DIR . 'page-geekline.php';
        
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
    }
    
    return $template;
}
	
    /**
 * Plugin activation with comprehensive error handling
 */
public function activate() {
    // Double-check requirements
    if (!$this->check_requirements()) {
        deactivate_plugins(TGF_PLUGIN_BASENAME);
        wp_die(
            esc_html__('The Geekline Feed cannot be activated due to unmet requirements.', TGF_TEXT_DOMAIN),
            esc_html__('Plugin Activation Error', TGF_TEXT_DOMAIN),
            ['back_link' => true]
        );
    }
    
    try {
        // Load critical files directly for activation
        $critical_files = ['utils.php', 'fetcher.php'];
        foreach ($critical_files as $file) {
            $file_path = TGF_PLUGIN_DIR . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                throw new Exception(sprintf('Critical file missing: %s', $file));
            }
        }
        
        // Create/Update all plugin database tables
        if (function_exists('tgf_create_database_tables')) {
            $db_success = tgf_create_database_tables();
            if (!$db_success) {
                error_log('Geekline: Database table creation failed');
                // Don't fail activation for DB errors, just log them
            }
        }
        
        // Set default options if they don't exist
        $this->set_default_options();
        
        // Clear any existing caches
        $this->clear_all_caches();
        
        // Schedule daily maintenance
        if (!wp_next_scheduled('tgf_daily_maintenance')) {
            wp_schedule_event(time() + 3600, 'daily', 'tgf_daily_maintenance'); // Schedule 1 hour from now
        }
		
		// Schedule hourly post fetching
        if (!wp_next_scheduled('tgf_fetch_posts_hourly')) {
            wp_schedule_event(time(), 'hourly', 'tgf_fetch_posts_hourly');
        }
        
        // Flush rewrite rules (important for archive pretty URLs)
        flush_rewrite_rules();
        
        // Set activation flag for admin notice
        update_option('tgf_plugin_activated', true);
        
        // Log activation
        error_log('Geekline Feed Plugin activated successfully (v' . TGF_VERSION . ')');
        
    } catch (Exception $e) {
        // Log the error and deactivate gracefully
        error_log('Geekline Feed activation error: ' . $e->getMessage());
        deactivate_plugins(TGF_PLUGIN_BASENAME);
        wp_die(
            sprintf(esc_html__('Geekline Feed activation failed: %s', TGF_TEXT_DOMAIN), esc_html($e->getMessage())),
            esc_html__('Plugin Activation Error', TGF_TEXT_DOMAIN),
            ['back_link' => true]
        );
    }
}
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear all transients/caches
        $this->clear_all_caches();
        
        // Clear scheduled events
        wp_clear_scheduled_hook('tgf_daily_maintenance');
        wp_clear_scheduled_hook('tgf_generate_daily_archive');
        wp_clear_scheduled_hook('tgf_cleanup_old_archives');
        wp_clear_scheduled_hook('tgf_fetch_posts_hourly');
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Remove activation flag
        delete_option('tgf_plugin_activated');
        
        // Log deactivation
        error_log('Geekline Feed Plugin deactivated');
    }
    
    /**
     * Plugin uninstallation
     */
    public static function uninstall() {
        // Security check
        if (!defined('WP_UNINSTALL_PLUGIN')) {
            exit;
        }
        
        // Only remove data if option is set
        $remove_data = get_option('tgf_remove_data_on_uninstall', false);
        
        if ($remove_data) {
            self::remove_plugin_data();
        }
        
        // Log uninstallation
        error_log('Geekline Feed Plugin uninstalled');
    }
    
    /**
     * Remove all plugin data
     */
    private static function remove_plugin_data() {
        global $wpdb;
        
        // Remove options
        $options = [
            'tgf_sources',
            'tgf_max_posts',
            'tgf_bad_words',
            'tgf_page_link',
            'tgf_hashtags',
            'tgf_disqus_shortname',
            'tgf_version',
            'tgf_archive_retention_days',
            'tgf_archive_posts_per_page',
            'tgf_bsky_bearer_token',
            'tgf_bsky_refresh_token',
            'tgf_feed_last_updated',
            'tgf_remove_data_on_uninstall',
            'tgf_plugin_activated'
        ];
        
        foreach ($options as $option) {
            delete_option($option);
        }
        
        // Remove database tables
        $tables = [
            $wpdb->prefix . 'geekline_reposts',
            $wpdb->prefix . 'geekline_posts'
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        // Remove all transients
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_tgf_%',
                '_transient_timeout_tgf_%'
            )
        );
        
        // Remove archive files
        $upload_dir = wp_upload_dir();
        $archive_dir = $upload_dir['basedir'] . '/geekline-archives';
        
        if (file_exists($archive_dir)) {
            self::recursive_remove_directory($archive_dir);
        }
    }
    
    /**
     * Recursively remove a directory
     * 
     * @param string $dir Directory path
     */
    private static function recursive_remove_directory($dir) {
        if (!file_exists($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                self::recursive_remove_directory($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }
    
    /**
     * Set default plugin options
     */
    private function set_default_options() {
        $defaults = [
            'tgf_sources' => '',
            'tgf_max_posts' => 100,
            'tgf_bad_words' => '',
            'tgf_page_link' => home_url('/thegeekline'),
            'tgf_hashtags' => '#TheGeekline',
            'tgf_disqus_shortname' => '',
            'tgf_version' => TGF_VERSION,
            'tgf_archive_retention_days' => 90,
            'tgf_archive_posts_per_page' => 50,
            'tgf_remove_data_on_uninstall' => false
        ];
        
        foreach ($defaults as $key => $value) {
            if (false === get_option($key)) {
                add_option($key, $value);
            }
        }
    }
    
    /**
     * Load plugin text domain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            TGF_TEXT_DOMAIN,
            false,
            dirname(TGF_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_assets() {
        // Only load on pages that need it
        if (!$this->should_load_assets()) {
            return;
        }
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'geekline-widget',
            TGF_PLUGIN_URL . 'js/geekline-widget.js',
            ['jquery'],
            TGF_VERSION,
            true
        );
        
        // Localize script with data
        wp_localize_script('geekline-widget', 'tgf_ajax_object', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tgf_repost_nonce'),
            'page_link' => esc_url(get_option('tgf_page_link', home_url('/thegeekline'))),
            'hashtags' => sanitize_text_field(get_option('tgf_hashtags', '#TheGeekline')),
            'disqus_shortname' => sanitize_text_field(get_option('tgf_disqus_shortname', '')),
            'rest_url' => rest_url('tgf/v1/posts'),
            'i18n' => [
                'view_original' => esc_html__('View Original', TGF_TEXT_DOMAIN),
                'comment' => esc_html__('Comment', TGF_TEXT_DOMAIN),
                'share' => esc_html__('Share', TGF_TEXT_DOMAIN),
                'error_message' => esc_html__('An error occurred. Please try again.', TGF_TEXT_DOMAIN),
                'repost_success' => esc_html__('Successfully shared!', TGF_TEXT_DOMAIN),
                'loading' => esc_html__('Loading...', TGF_TEXT_DOMAIN),
                'just_now' => esc_html__('Just now', TGF_TEXT_DOMAIN),
                'minutes_ago' => esc_html__('m ago', TGF_TEXT_DOMAIN),
                'hours_ago' => esc_html__('h ago', TGF_TEXT_DOMAIN),
                'days_ago' => esc_html__('d ago', TGF_TEXT_DOMAIN),
                'unknown_author' => esc_html__('unknown', TGF_TEXT_DOMAIN),
                'media_alt' => esc_html__('Media from', TGF_TEXT_DOMAIN),
                'comment_aria' => esc_html__('Comment on this post', TGF_TEXT_DOMAIN),
                'share_aria' => esc_html__('Share this post', TGF_TEXT_DOMAIN),
                'no_posts' => esc_html__('No posts available yet. Check back soon!', TGF_TEXT_DOMAIN),
                'end_of_feed' => esc_html__('You\'ve reached the end!', TGF_TEXT_DOMAIN),
                'loading_comments' => esc_html__('Loading comments...', TGF_TEXT_DOMAIN),
                'comments_coming_soon' => esc_html__('Comments feature coming soon! Please set your Disqus shortname in the plugin settings.', TGF_TEXT_DOMAIN),
                'share_text' => esc_html__('Check out this post by', TGF_TEXT_DOMAIN),
            ],
        ]);
        
        // Enqueue CSS
        wp_enqueue_style(
            'geekline-widget-css',
            TGF_PLUGIN_URL . 'css/geekline-widget.css',
            [],
            TGF_VERSION
        );
        
        // Add inline styles for critical loading states
        $inline_css = "
            .geekline-loading {
                text-align: center;
                padding: 40px 20px;
                color: #666;
            }
            .geekline-loading-spinner {
                border: 3px solid #f3f3f3;
                border-top: 3px solid #0073aa;
                border-radius: 50%;
                width: 40px;
                height: 40px;
                animation: spin 1s linear infinite;
                margin: 0 auto 20px;
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        ";
        
        wp_add_inline_style('geekline-widget-css', $inline_css);
    }
    
    /**
     * Enqueue admin scripts and styles
     * 
     * @param string $hook_suffix The current admin page
     */
    public function enqueue_admin_assets($hook_suffix) {
        // Only load on our admin pages
        if (strpos($hook_suffix, 'geekline') === false) {
            return;
        }
        
        wp_enqueue_style(
            'geekline-admin-css',
            TGF_PLUGIN_URL . 'css/geekline-admin.css',
            [],
            TGF_VERSION
        );
        
        wp_enqueue_script(
            'geekline-admin-js',
            TGF_PLUGIN_URL . 'js/geekline-admin.js',
            ['jquery'],
            TGF_VERSION,
            true
        );
        
        // Localize admin script
        wp_localize_script('geekline-admin-js', 'tgf_admin_object', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tgf_admin_nonce'),
            'i18n' => [
                'confirm_clear_tokens' => esc_html__('Are you sure you want to clear all authentication tokens?', TGF_TEXT_DOMAIN),
                'confirm_reset_reposts' => esc_html__('Are you sure? This will reset ALL repost counts to zero.', TGF_TEXT_DOMAIN),
                'confirm_prune_posts' => esc_html__('Are you sure? This will permanently delete old posts from the database based on your retention settings.', TGF_TEXT_DOMAIN),
                'confirm_delete_archive' => esc_html__('Are you sure you want to delete this HTML archive file?', TGF_TEXT_DOMAIN),
                'working' => esc_html__('Working...', TGF_TEXT_DOMAIN),
            ],
        ]);
    }
    
    /**
     * Check if assets should be loaded on current page
     * 
     * @return bool
     */
    private function should_load_assets() {
        global $post;
        
        // Load on pages with shortcode
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'geekline_feed')) {
            return true;
        }
        
        // Load on specific page template
        if (is_page_template('page-geekline.php')) {
            return true;
        }
        
        // Allow filtering
        return apply_filters('geekline_should_load_assets', false);
    }
    
    /**
     * Handle repost AJAX request
     */
    public function handle_repost() {
        // Verify nonce
        if (!check_ajax_referer('tgf_repost_nonce', 'security', false)) {
            wp_send_json_error(esc_html__('Security verification failed.', TGF_TEXT_DOMAIN));
        }
        
        // Validate URL parameter
        if (!isset($_POST['url']) || empty($_POST['url'])) {
            wp_send_json_error(esc_html__('No URL provided.', TGF_TEXT_DOMAIN));
        }
        
        $url = esc_url_raw(wp_unslash($_POST['url']));
        
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            wp_send_json_error(esc_html__('Invalid URL provided.', TGF_TEXT_DOMAIN));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'geekline_reposts';
        
        // Verify table exists
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        if (!$table_exists) {
            error_log('Geekline: Reposts table does not exist');
            wp_send_json_error(esc_html__('Database error. Please try again.', TGF_TEXT_DOMAIN));
        }
        
        // Use atomic INSERT ... ON DUPLICATE KEY UPDATE
        $result = $wpdb->query($wpdb->prepare(
            "INSERT INTO $table (post_url, reposts_count, last_reposted) 
             VALUES (%s, 1, %s) 
             ON DUPLICATE KEY UPDATE 
             reposts_count = reposts_count + 1, 
             last_reposted = %s",
            $url,
            current_time('mysql'),
            current_time('mysql')
        ));
        
        if ($result === false) {
            error_log("Geekline Repost DB Error: " . $wpdb->last_error);
            wp_send_json_error(esc_html__('Database error. Please try again.', TGF_TEXT_DOMAIN));
        }
        
        // Get the updated count
        $new_count = $wpdb->get_var($wpdb->prepare(
            "SELECT reposts_count FROM $table WHERE post_url = %s",
            $url
        ));
        
        // Clear cache when repost happens
        delete_transient('tgf_posts_cache');
        
        // Update last updated timestamp
        update_option('tgf_feed_last_updated', current_time('mysql'));
        
        // Log repost action
        do_action('geekline_after_repost', $url, $new_count);
        
        wp_send_json_success([
            'count' => (int) $new_count,
            'message' => esc_html__('Successfully shared!', TGF_TEXT_DOMAIN)
        ]);
    }
    
    /**
     * Render feed shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function render_feed_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts([
            'show_tabs' => 'true',
            'default_tab' => 'feed',
            'title' => __('The Geekline', TGF_TEXT_DOMAIN),
            'description' => ''
        ], $atts, 'geekline_feed');
        
        $show_tabs = filter_var($atts['show_tabs'], FILTER_VALIDATE_BOOLEAN);
        $default_tab = sanitize_text_field($atts['default_tab']);
        $title = sanitize_text_field($atts['title']);
        $description = wp_kses_post($atts['description']);
        
        // Validate default tab
        $allowed_tabs = ['feed', 'reposts', 'most_commented'];
        if (!in_array($default_tab, $allowed_tabs, true)) {
            $default_tab = 'feed';
        }
        
        // Default description if empty
        if (empty($description)) {
            $description = __('A live feed of tech thoughts, tips, and discoveries from everyday geeks across the fediverse.', TGF_TEXT_DOMAIN);
        }
        
        // Start output buffering
        ob_start();
        
        // Allow filtering the output
        do_action('geekline_before_feed', $atts);
        ?>
        <div class="geekline-page-container">
            <h1 class="geekline-title"><?php echo esc_html($title); ?></h1>
            
            <?php if (!empty($description)) : ?>
            <p class="geekline-description">
                <?php echo wp_kses_post($description); ?>
            </p>
            <?php endif; ?>
            
            <?php if ($show_tabs) : ?>
            <div class="geekline-tabs" role="tablist" aria-label="<?php esc_attr_e('Feed filter tabs', TGF_TEXT_DOMAIN); ?>">
                <button 
                    data-tab="feed" 
                    class="geekline-tab-button <?php echo $default_tab === 'feed' ? 'active' : ''; ?>" 
                    role="tab" 
                    aria-selected="<?php echo $default_tab === 'feed' ? 'true' : 'false'; ?>"
                    aria-controls="geekline-content">
                    <?php esc_html_e('Feed', TGF_TEXT_DOMAIN); ?>
                </button>
                <button 
                    data-tab="reposts" 
                    class="geekline-tab-button <?php echo $default_tab === 'reposts' ? 'active' : ''; ?>" 
                    role="tab" 
                    aria-selected="<?php echo $default_tab === 'reposts' ? 'true' : 'false'; ?>"
                    aria-controls="geekline-content">
                    <?php esc_html_e('Most Shared', TGF_TEXT_DOMAIN); ?>
                </button>
                <button 
                    data-tab="most_commented" 
                    class="geekline-tab-button <?php echo $default_tab === 'most_commented' ? 'active' : ''; ?>" 
                    role="tab" 
                    aria-selected="<?php echo $default_tab === 'most_commented' ? 'true' : 'false'; ?>"
                    aria-controls="geekline-content">
                    <?php esc_html_e('Most Commented', TGF_TEXT_DOMAIN); ?>
                </button>
            </div>
            <?php endif; ?>

            <div 
                id="geekline-content"
                class="geekline-widget-container" 
                data-default-tab="<?php echo esc_attr($default_tab); ?>"
                role="region"
                aria-live="polite"
                aria-label="<?php esc_attr_e('Social media feed', TGF_TEXT_DOMAIN); ?>">
                <div class="geekline-loading">
                    <div class="geekline-loading-spinner"></div>
                    <p><?php esc_html_e('Loading feed...', TGF_TEXT_DOMAIN); ?></p>
                </div>
            </div>
			<!-- ARCHIVE NAVIGATION - ADDED HERE -->
        <nav class="geekline-archive-navigation">
            <div class="geekline-nav-inner">
                <a href="<?php echo esc_url( home_url( '/geekline-archive' ) ); ?>" class="geekline-archive-link">
                    <?php echo esc_html__('📁 Browse Archive', TGF_TEXT_DOMAIN); ?>
                </a>
                <span class="geekline-nav-description">
                    <?php echo esc_html__('Explore historical posts from the fediverse', TGF_TEXT_DOMAIN); ?>
                </span>
            </div>
        </nav>
        
        <!-- DISCLAIMER SECTION - ADDED HERE -->
        <div class="geekline-content-disclaimer">
            <h4><?php echo esc_html__('Content Usage & Privacy', TGF_TEXT_DOMAIN); ?></h4>
            <p><strong><?php echo esc_html__('Copyright Notice:', TGF_TEXT_DOMAIN); ?></strong> <?php echo esc_html__('All content displayed in this feed remains the intellectual property of the original publishers and authors. Content is aggregated for reference and educational purposes only.', TGF_TEXT_DOMAIN); ?></p>
            <p><strong><?php echo esc_html__('Privacy Compliance:', TGF_TEXT_DOMAIN); ?></strong> <?php echo esc_html__('This feed operates in compliance with Bluesky\'s privacy policy and data usage guidelines. We respect user privacy and content ownership.', TGF_TEXT_DOMAIN); ?></p>
            <p><strong><?php echo esc_html__('Content Disclaimer:', TGF_TEXT_DOMAIN); ?></strong> <?php echo esc_html__('The content provided here is for reference and informational purposes only. Views and opinions expressed in the aggregated content belong to their respective authors and do not necessarily reflect the views of Mania Inc.', TGF_TEXT_DOMAIN); ?></p>
            
            <div class="geekline-policy-links">
    <a href="<?php echo esc_url( home_url( '/privacy-policy' ) ); ?>" target="_blank" rel="noopener noreferrer">
        <?php echo esc_html__('🔒 Privacy Policy', TGF_TEXT_DOMAIN); ?>
    </a>
    <a href="<?php echo esc_url( home_url( '/termsfeed/terms-and-conditions/' ) ); ?>" target="_blank" rel="noopener noreferrer">
        <?php echo esc_html__('📄 Terms & Conditions', TGF_TEXT_DOMAIN); ?>
    </a>
    <a href="https://bsky.social/about/support/privacy-policy" target="_blank" rel="noopener noreferrer">
        <?php echo esc_html__('🦋 Bluesky Privacy Policy', TGF_TEXT_DOMAIN); ?>
    </a>
</div>
        </div>
        </div>
        <?php
        do_action('geekline_after_feed', $atts);
        
        return ob_get_clean();
    }
    
    /**
     * Add settings link to plugins page
     * 
     * @param array $links Existing plugin action links
     * @return array Modified plugin action links
     */
    public function add_settings_link($links) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=geekline-feed'),
            esc_html__('Settings', TGF_TEXT_DOMAIN)
        );
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Add plugin row meta
     * 
     * @param array $plugin_meta Existing plugin meta
     * @param string $plugin_file Plugin file
     * @return array Modified plugin meta
     */
    public function add_plugin_row_meta($plugin_meta, $plugin_file) {
        if (TGF_PLUGIN_BASENAME === $plugin_file) {
            $plugin_meta[] = sprintf(
                '<a href="%s" target="_blank">%s</a>',
                esc_url('https://maniainc.com/thegeekline'),
                esc_html__('Documentation', TGF_TEXT_DOMAIN)
            );
            $plugin_meta[] = sprintf(
                '<a href="%s" target="_blank">%s</a>',
                esc_url('https://docs.bsky.app/'),
                esc_html__('BlueSky API Docs', TGF_TEXT_DOMAIN)
            );
        }
        
        return $plugin_meta;
    }
    
    /**
     * Clear all plugin caches
     */
    private function clear_all_caches() {
        delete_transient('tgf_posts_cache');
        delete_transient('tgf_bsky_access_token');
        
        // Clear REST API cache
        if (function_exists('tgf_flush_rest_cache')) {
            tgf_flush_rest_cache('all');
        }
        
        // Clear any object cache if available
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        // Clear archive manager cache if available
        if (class_exists('Geekline_Archive_Manager')) {
            // Archive manager handles its own caching
        }
    }
    
    /**
     * Run daily maintenance tasks
     */
    public function run_daily_maintenance() {
        // Clear expired transients
        $this->clear_expired_transients();
        
        // Update feed last updated timestamp
        update_option('tgf_feed_last_updated', current_time('mysql'));
        
        // Log maintenance run
        error_log('Geekline Feed: Daily maintenance completed');
    }
    
    /**
     * Clear expired transients
     */
    private function clear_expired_transients() {
        global $wpdb;
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} 
                 WHERE option_name LIKE %s 
                 AND option_value < %d",
                '_transient_timeout_tgf_%',
                current_time('timestamp')
            )
        );
    }
    
    /**
     * Add rewrite rules for archive URLs
     */
    public function add_rewrite_rules() {
        // These will be handled by the archive manager
        // This is a fallback in case archive manager isn't loaded
        add_rewrite_rule(
            '^geekline-archive/([0-9]{4})/([0-9]{2})/([0-9]{2})/?$',
            'index.php?geekline_archive_date=$matches[1]-$matches[2]-$matches[3]',
            'top'
        );
    }
    
    /**
     * Get plugin data
     * 
     * @return array Plugin data
     */
    public static function get_plugin_data() {
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        return get_plugin_data(__FILE__);
    }
}

/**
 * Initialize the plugin
 * 
 * @return Geekline_Feed_Plugin
 */
function geekline_feed_init() {
    return Geekline_Feed_Plugin::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'geekline_feed_init');

/**
 * Helper function to get plugin instance
 * 
 * @return Geekline_Feed_Plugin
 */
function geekline_feed() {
    return Geekline_Feed_Plugin::get_instance();
}
/**
 * Hook for hourly post fetching cron
 */
add_action('tgf_fetch_posts_hourly', 'tgf_cron_fetch_posts');

/**
 * Cron callback to fetch posts
 */
function tgf_cron_fetch_posts() {
    if (!function_exists('tgf_fetch_posts')) {
        error_log('Geekline Cron: tgf_fetch_posts function not found');
        return;
    }
    
    error_log('Geekline Cron: Starting hourly post fetch');
    $posts = tgf_fetch_posts(100);
    error_log('Geekline Cron: Fetched ' . count($posts) . ' posts');
}
