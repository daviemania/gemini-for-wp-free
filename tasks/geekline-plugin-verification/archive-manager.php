<?php
/**
 * Geekline Archive Manager
 * 
 * Creates static HTML archives of feed posts for:
 * - SEO optimization
 * - Historical preservation
 * - Fast page loads
 * - Reduced API calls
 * 
 * @package Geekline
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Geekline_Archive_Manager {
    
    /**
     * Archive directory path
     * 
     * @var string
     */
    private $archive_dir;
    
    /**
     * Archive URL base
     * 
     * @var string
     */
    private $archive_url;
    
    /**
     * Archive retention days (default: 90)
     * 
     * @var int
     */
    private $retention_days;
    
    /**
     * Posts per archive page
     * 
     * @var int
     */
    private $posts_per_page;
    
    /**
     * Constructor
     */
    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->archive_dir = $upload_dir['basedir'] . '/geekline-archives';
        $this->archive_url = $upload_dir['baseurl'] . '/geekline-archives';
        $this->retention_days = (int) get_option('tgf_archive_retention_days', 90);
        $this->posts_per_page = (int) get_option('tgf_archive_posts_per_page', 50);
        
        $this->init_hooks();
        $this->ensure_archive_directory();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Cron schedules
        add_action('tgf_generate_daily_archive', [$this, 'generate_daily_archive']);
        add_action('tgf_cleanup_old_archives', [$this, 'cleanup_old_archives']);
        
        // Register cron schedules if not exists
        if (!wp_next_scheduled('tgf_generate_daily_archive')) {
            wp_schedule_event(strtotime('01:00:00'), 'daily', 'tgf_generate_daily_archive');
        }
        
        if (!wp_next_scheduled('tgf_cleanup_old_archives')) {
            // Run weekly to clean up old archive files
            wp_schedule_event(time(), 'weekly', 'tgf_cleanup_old_archives');
        }
        
        // AJAX handlers
        add_action('wp_ajax_tgf_generate_archive_now', [$this, 'ajax_generate_archive']);
        add_action('wp_ajax_tgf_delete_archive', [$this, 'ajax_delete_archive']);
        
        // Rewrite rules for pretty URLs
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('template_redirect', [$this, 'serve_archive']);
        
        // Flush rewrite rules on plugin activation/deactivation
        register_activation_hook(__FILE__, [$this, 'flush_rewrite_rules']);
        register_deactivation_hook(__FILE__, [$this, 'flush_rewrite_rules']);
    }
    
    /**
     * Flush rewrite rules
     */
    public function flush_rewrite_rules() {
        $this->add_rewrite_rules();
        flush_rewrite_rules();
    }
    
    /**
     * Ensure archive directory exists and is writable
     */
    private function ensure_archive_directory() {
        if (!file_exists($this->archive_dir)) {
            wp_mkdir_p($this->archive_dir);
        }
        
        // Create .htaccess for security
        $htaccess = $this->archive_dir . '/.htaccess';
        if (!file_exists($htaccess)) {
            $htaccess_content = "Options -Indexes\n";
            $htaccess_content .= "Deny from all\n";
            $htaccess_content .= "<FilesMatch \"\\.(html|css|js)$\">\n";
            $htaccess_content .= "    Allow from all\n";
            $htaccess_content .= "</FilesMatch>\n";
            file_put_contents($htaccess, $htaccess_content);
        }
        
        // Create index.html for root archive directory
        $index = $this->archive_dir . '/index.html';
        if (!file_exists($index)) {
            $this->create_archive_index();
        }
    }
    
    /**
     * Add rewrite rules for archive URLs
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^geekline-archive/([0-9]{4})/([0-9]{2})/([0-9]{2})/?$',
            'index.php?geekline_archive_date=$matches[1]-$matches[2]-$matches[3]',
            'top'
        );
        add_rewrite_rule(
            '^geekline-archive/([0-9]{4})/([0-9]{2})/?$',
            'index.php?geekline_archive_month=$matches[1]-$matches[2]',
            'top'
        );
        add_rewrite_rule(
            '^geekline-archive/?$',
            'index.php?geekline_archive_list=1',
            'top'
        );
    }
    
    /**
     * Add custom query vars
     * 
     * @param array $vars Existing query vars
     * @return array Modified query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'geekline_archive_date';
        $vars[] = 'geekline_archive_month';
        $vars[] = 'geekline_archive_list';
        return $vars;
    }
    
    /**
     * Serve archive page
     */
    public function serve_archive() {
        $date = get_query_var('geekline_archive_date');
        $month = get_query_var('geekline_archive_month');
        $list = get_query_var('geekline_archive_list');
        
        if ($date) {
            $this->serve_daily_archive($date);
        } elseif ($month) {
            $this->serve_monthly_archive($month);
        } elseif ($list) {
            $this->serve_archive_list();
        }
    }
    
    /**
     * Serve daily archive
     * 
     * @param string $date Date string in YYYY-MM-DD format
     */
    private function serve_daily_archive($date) {
        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            wp_die(
                esc_html__('Invalid date format.', 'geekline-feed'),
                esc_html__('Archive Error', 'geekline-feed'),
                ['response' => 400]
            );
        }

        $file = $this->get_archive_file_path($date);
        if (file_exists($file)) {
            header('Content-Type: text/html; charset=utf-8');
            readfile($file);
            exit;
        } else {
            // Generate on-demand if missing
            $posts = $this->get_posts_for_date($date);
            if (!empty($posts)) {
                $this->generate_archive_file($date, $posts);
                if (file_exists($file)) {
                    header('Content-Type: text/html; charset=utf-8');
                    readfile($file);
                    exit;
                }
            }
        }
        
        wp_die(
            esc_html__('Archive not found', 'geekline-feed'),
            esc_html__('Archive Not Found', 'geekline-feed'),
            ['response' => 404]
        );
    }
    
    /**
     * Serve monthly archive index
     * 
     * @param string $month Date string in YYYY-MM format
     */
    private function serve_monthly_archive($month) {
        // Validate input
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            wp_die(
                esc_html__('Invalid month format.', 'geekline-feed'),
                esc_html__('Archive Error', 'geekline-feed'),
                ['response' => 400]
            );
        }

        $month_start_date = date('Y-m-01', strtotime($month));
        $month_end_date = date('Y-m-t', strtotime($month));
        
        $site_name = get_bloginfo('name');
        $site_url = home_url('/');
        $archive_title = date('F Y', strtotime($month));
        $live_feed_url = apply_filters('tgf_live_feed_url', 'https://maniainc.com/the-geekline-feed');
        $main_archive_url = home_url('/geekline-archive/');
        
        // Filter existing archives for the month
        $all_archives = $this->list_archives();
        $monthly_archives = array_filter($all_archives, function($archive) use ($month_start_date, $month_end_date) {
            return ($archive['date'] >= $month_start_date && $archive['date'] <= $month_end_date);
        });
        
        if (empty($monthly_archives)) {
            wp_die(
                sprintf(esc_html__('Archive for %s not found.', 'geekline-feed'), $archive_title),
                esc_html__('Archive Not Found', 'geekline-feed'),
                ['response' => 404]
            );
        }
        
        header('Content-Type: text/html; charset=utf-8');
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php 
                printf(
                    esc_html__('Geekline Archive - %s | %s', 'geekline-feed'),
                    esc_html($archive_title),
                    esc_html($site_name)
                ); 
            ?></title>
            <meta name="robots" content="index, noarchive">
            <style>
                <?php echo $this->get_archive_css(); ?>
                .monthly-index-list {
                    list-style: none;
                    padding: 0;
                }
                .monthly-index-list li {
                    border-bottom: 1px solid #eee;
                    padding: 15px 0;
                }
                .monthly-index-list a {
                    font-size: 1.2rem;
                    text-decoration: none;
                    color: #0073aa;
                    font-weight: 600;
                }
                .list-meta {
                    display: block;
                    font-size: 0.9rem;
                    color: #999;
                    margin-top: 5px;
                }
            </style>
        </head>
        <body>
            <div class="archive-container">
                <header class="archive-header">
                    <div class="archive-branding">
                         <h1><a href="<?php echo esc_url($site_url); ?>"><?php echo esc_html($site_name); ?></a></h1>
                        <p class="archive-subtitle"><?php esc_html_e('Monthly Archive Index', 'geekline-feed'); ?></p>
                    </div>
                    <div class="archive-meta">
                        <time><?php echo esc_html($archive_title); ?></time>
                        <span class="post-count">
                            <?php 
                            printf(
                                esc_html(_n('%d day archived', '%d days archived', count($monthly_archives), 'geekline-feed')),
                                count($monthly_archives)
                            );
                            ?>
                        </span>
                    </div>
                </header>
                <!-- ADD NAVIGATION HERE -->
            <div class="archive-navigation">
                <div class="nav-links">
                    <a href="<?php echo esc_url($main_archive_url); ?>">← Main Archive</a>
                    <a href="<?php echo esc_url($live_feed_url); ?>" class="live-feed" target="_blank">📻 Live Feed</a>
                    <span class="nav-current">Monthly: <?php echo esc_html($archive_title); ?></span>
                </div>
            </div>
                <main class="archive-content">
                    <h2>
                        <?php
                        printf(
                            esc_html__('Available Daily Archives for %s', 'geekline-feed'),
                            esc_html($archive_title)
                        );
                        ?>
                    </h2>
                    <ul class="monthly-index-list">
                        <?php foreach ($monthly_archives as $archive) : ?>
                            <li>
                                <a href="<?php echo esc_url($archive['url']); ?>">
                                    <?php echo esc_html(date('F j, Y', strtotime($archive['date']))); ?>
                                </a>
                                <span class="list-meta">
                                    <?php
                                    printf(
                                        esc_html__('%s posts | File Size: %s', 'geekline-feed'),
                                        number_format_i18n($archive['post_count']),
                                        size_format($archive['size'])
                                    );
                                    ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </main>
                
                <footer class="archive-footer">
                    <p><a href="<?php echo esc_url(home_url('/geekline-archive/')); ?>"><?php esc_html_e('View All Monthly Archives', 'geekline-feed'); ?></a></p>
                    <p><a href="<?php echo esc_url($site_url); ?>">
                        <?php
                        printf(
                            esc_html__('Return to %s', 'geekline-feed'),
                            esc_html($site_name)
                        );
                        ?>
                    </a></p>
                </footer>
            </div>
        </body>
        </html>
        <?php
        echo ob_get_clean();
        exit;
    }
    
    /**
     * AJAX: Generate archive now
     */
    public function ajax_generate_archive() {
        check_ajax_referer('tgf_archive_action', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('Insufficient permissions', 'geekline-feed')]);
        }
        
        $result = $this->generate_daily_archive();
        if ($result) {
            wp_send_json_success(['message' => esc_html__('Archive generated successfully', 'geekline-feed')]);
        } else {
            wp_send_json_error(['message' => esc_html__('Failed to generate archive. No new posts found for today.', 'geekline-feed')]);
        }
    }
    
    /**
     * AJAX: Delete archive
     */
    public function ajax_delete_archive() {
        check_ajax_referer('tgf_archive_action', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('Insufficient permissions', 'geekline-feed')]);
        }
        
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        
        if (empty($date)) {
            wp_send_json_error(['message' => esc_html__('No date specified', 'geekline-feed')]);
        }
        
        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            wp_send_json_error(['message' => esc_html__('Invalid date format', 'geekline-feed')]);
        }
        
        $file = $this->get_archive_file_path($date);
        
        if (file_exists($file) && unlink($file)) {
            wp_send_json_success(['message' => esc_html__('Archive deleted successfully', 'geekline-feed')]);
        } else {
            wp_send_json_error(['message' => esc_html__('Failed to delete archive', 'geekline-feed')]);
        }
    }
    
    /**
     * Generate daily archive
     * 
     * @param string|null $date Date string in YYYY-MM-DD format, null for current date
     * @return bool Success status
     */
    public function generate_daily_archive($date = null) {
        if (!$date) {
            $date = current_time('Y-m-d');
        }
        
        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            error_log("Geekline Archive: Invalid date format: {$date}");
            return false;
        }
        
        // Check if archive already exists and is from today
        $existing_file = $this->get_archive_file_path($date);
        if (file_exists($existing_file) && $date === current_time('Y-m-d')) {
            $file_time = filemtime($existing_file);
            // If file was modified in the last hour, skip regeneration
            if ($file_time > (time() - 3600)) {
                error_log("Geekline Archive: Archive for {$date} was recently generated, skipping");
                return true;
            }
        }
        
        // Fetch posts for the date
        $posts = $this->get_posts_for_date($date);
        if (empty($posts)) {
            error_log("Geekline Archive: No posts found in database for {$date}");
            return false;
        }
        
        // Generate HTML file
        return $this->generate_archive_file($date, $posts);
    }
    
    /**
     * Get posts for a specific date
     * 
     * @param string $date Date string in YYYY-MM-DD format
     * @return array Array of posts
     */
    private function get_posts_for_date($date) {
        // Use the database function (RECOMMENDED)
        if (function_exists('tgf_get_posts_for_date')) {
            $posts = tgf_get_posts_for_date($date);
            if (!empty($posts)) {
                error_log("Geekline Archive: Retrieved " . count($posts) . " posts from database for {$date}");
                return $posts;
            }
        }
        
        // Fallback if function doesn't exist or returns empty
        error_log("Geekline Archive: tgf_get_posts_for_date function not found or returned no posts for {$date}");
        
        // Additional fallback: Try to get from cache
        $cached_posts = get_transient('tgf_posts_cache');
        if ($cached_posts && is_array($cached_posts)) {
            $filtered_posts = array_filter($cached_posts, function($post) use ($date) {
                return isset($post['date']) && date('Y-m-d', strtotime($post['date'])) === $date;
            });
            if (!empty($filtered_posts)) {
                error_log("Geekline Archive: Retrieved " . count($filtered_posts) . " posts from cache for {$date}");
                return array_values($filtered_posts);
            }
        }
        
        return [];
    }
    
    /**
     * Generate archive HTML file
     * 
     * @param string $date Date string
     * @param array $posts Array of posts
     * @return bool Success status
     */
    private function generate_archive_file($date, $posts) {
        $file_path = $this->get_archive_file_path($date);
        $html = $this->build_archive_html($date, $posts);
        
        $result = file_put_contents($file_path, $html);
        
        if ($result !== false) {
            error_log("Geekline Archive: Generated archive for {$date} with " . count($posts) . " posts");
            return true;
        }
        
        error_log("Geekline Archive: Failed to write file for {$date}");
        return false;
    }
    
    /**
     * Build archive HTML
     * 
     * @param string $date Date string
     * @param array $posts Array of posts
     * @return string HTML content
     */
    private function build_archive_html($date, $posts) {
        $site_name = get_bloginfo('name');
        $site_url = home_url('/');
        $archive_date = date('F j, Y', strtotime($date));
        $post_count = count($posts);
		
		// Calculate navigation URLs
        $year = date('Y', strtotime($date));
        $month = date('m', strtotime($date));
        $monthly_archive_url = home_url("/geekline-archive/{$year}/{$month}/");
        $main_archive_url = home_url('/geekline-archive/');
        $live_feed_url = apply_filters('tgf_live_feed_url', 'https://maniainc.com/the-geekline-feed');
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php
                printf(
                    esc_html__('Geekline Archive - %s | %s', 'geekline-feed'),
                    esc_html($archive_date),
                    esc_html($site_name)
                );
            ?></title>
            <meta name="description" content="<?php
                printf(
                    esc_attr__('Archived tech feed from %s featuring %d posts from the fediverse.', 'geekline-feed'),
                    esc_attr($archive_date),
                    $post_count
                );
            ?>">
            <meta name="robots" content="index, follow">
            <link rel="canonical" href="<?php echo esc_url($this->get_archive_url($date)); ?>">
            
            <style>
                <?php echo $this->get_archive_css(); ?>
            </style>
        </head>
        <body>
            <div class="archive-container">
                <header class="archive-header">
                    <div class="archive-branding">
                         <h1><a href="<?php echo esc_url($site_url); ?>"><?php echo esc_html($site_name); ?></a></h1>
                        <p class="archive-subtitle"><?php esc_html_e('Geekline Feed Archive', 'geekline-feed'); ?></p>
                    </div>
                    <div class="archive-meta">
                        <time datetime="<?php echo esc_attr($date); ?>"><?php echo esc_html($archive_date); ?></time>
                        <span class="post-count">
                            <?php
                            printf(
                                esc_html(_n('%s post', '%s posts', $post_count, 'geekline-feed')),
                                number_format_i18n($post_count)
                            );
                            ?>
                        </span>
                    </div>
                </header>
                <!-- ADD NAVIGATION HERE -->
            <div class="archive-navigation">
                <div class="nav-links">
                    <a href="<?php echo esc_url($monthly_archive_url); ?>">← Monthly Archive</a>
                    <a href="<?php echo esc_url($main_archive_url); ?>">← Main Archive</a>
                    <a href="<?php echo esc_url($live_feed_url); ?>" class="live-feed" target="_blank">📻 Live Feed</a>
                    <span class="nav-current">Daily: <?php echo esc_html($archive_date); ?></span>
                </div>
            </div>
                <main class="archive-content">
                    <?php foreach ($posts as $index => $post) : ?>
                        <article class="archive-post" id="post-<?php echo (int) $index; ?>">
                            <div class="post-meta">
                                <strong class="post-author"><?php echo esc_html($post['author']); ?></strong>
                                <span class="post-handle">@<?php echo esc_html($post['author_handle']); ?></span>
                                <time datetime="<?php echo esc_attr($post['date']); ?>"><?php echo esc_html(date('g:i A', strtotime($post['date']))); ?></time>
                            </div>
                            
                            <div class="post-content">
                                 <?php echo wp_kses_post($post['content_html']); ?>
                            </div>
                            
                            <?php if ($post['has_media'] && !empty($post['media_url'])) : ?>
                                <div class="post-media">
                                    <img src="<?php echo esc_url($post['media_url']); ?>"
                                         alt="<?php
                                            printf(
                                                esc_attr__('Media from %s', 'geekline-feed'),
                                                esc_attr($post['author'])
                                            );
                                         ?>"
                                         loading="lazy">
                                </div>
                            <?php endif; ?>
                            
                            <div class="post-actions">
                                <a href="<?php echo esc_url($post['url']); ?>"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="post-link"><?php esc_html_e('View Original', 'geekline-feed'); ?></a>
                                <span class="post-source"><?php echo esc_html($post['source']); ?></span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </main>
                
                <footer class="archive-footer">
                    <p>
                        <?php
                        printf(
                            esc_html__('This archive was generated on %s at %s.', 'geekline-feed'),
                            current_time('F j, Y'),
                            current_time('g:i A')
                        );
                        ?>
                    </p>
                    <p><a href="<?php echo esc_url($site_url); ?>">
                        <?php
                        printf(
                            esc_html__('Return to %s', 'geekline-feed'),
                            esc_html($site_name)
                        );
                        ?>
                    </a></p>
                </footer>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get archive CSS
     * 
     * @return string CSS content
     */
    private function get_archive_css() {
        return <<<CSS
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    line-height: 1.6;
    color: #333;
    background: #f5f5f5;
}

.archive-container {
    max-width: 800px;
    margin: 0 auto;
    background: #fff;
    min-height: 100vh;
}

.archive-header {
    background: linear-gradient(135deg, #0073aa 0%, #005a87 100%);
    color: #fff;
    padding: 40px 20px;
    text-align: center;
}

.archive-header h1 {
    font-size: 2rem;
    margin-bottom: 10px;
}

.archive-header h1 a {
    color: #fff;
    text-decoration: none;
}

.archive-subtitle {
    font-size: 0.9rem;
    opacity: 0.9;
    margin-bottom: 20px;
}

.archive-meta {
    display: flex;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
    margin-top: 20px;
}

.archive-meta time {
    font-size: 1.2rem;
    font-weight: 600;
}

.post-count {
    background: rgba(255,255,255,0.2);
    padding: 5px 15px;
    border-radius: 20px;
}

.archive-content {
    padding: 20px;
}

/* Navigation Styles */
.archive-navigation {
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    padding: 15px 20px;
    margin: 0;
}
.nav-links {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: center;
}
.nav-links a {
    color: #0073aa;
    text-decoration: none;
    font-size: 14px;
    padding: 5px 10px;
    border-radius: 3px;
    transition: background-color 0.2s;
}
.nav-links a:hover {
    background: rgba(0, 115, 170, 0.1);
    text-decoration: underline;
}
.nav-links .live-feed {
    background: #28a745;
    color: white;
    font-weight: 600;
    padding: 5px 12px;
}
.nav-links .live-feed:hover {
    background: #218838;
    text-decoration: none;
}
.nav-current {
    font-size: 13px;
    color: #666;
    margin-left: auto;
}
@media (max-width: 768px) {
    .nav-links {
        gap: 10px;
    }
    .nav-current {
        margin-left: 0;
        margin-top: 10px;
        width: 100%;
    }
}

.archive-post {
    background: #fafafa;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 8px;
    border-left: 4px solid #0073aa;
}

.post-meta {
    margin-bottom: 10px;
    color: #666;
    font-size: 0.9rem;
}

.post-author {
    color: #0073aa;
    font-weight: 600;
}

.post-handle {
    opacity: 0.7;
}

.post-content {
    margin: 15px 0;
    line-height: 1.7;
}

.post-content a {
    color: #0073aa;
    text-decoration: none;
}

.post-content a:hover {
    text-decoration: underline;
}

.post-media {
    margin: 15px 0;
    border-radius: 8px;
    overflow: hidden;
}

.post-media img {
    max-width: 100%;
    height: auto;
    display: block;
}

.post-actions {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.85rem;
}

.post-link {
    color: #0073aa;
    text-decoration: none;
    font-weight: 600;
}

.post-link:hover {
    text-decoration: underline;
}

.post-source {
    color: #999;
}

.archive-footer {
    background: #f0f0f0;
    padding: 30px 20px;
    text-align: center;
    color: #666;
    font-size: 0.9rem;
}

.archive-footer a {
    color: #0073aa;
    text-decoration: none;
}

@media (max-width: 768px) {
    .archive-header {
        padding: 20px 15px;
    }
    
    .archive-header h1 {
        font-size: 1.5rem;
    }
    
    .archive-content {
        padding: 10px;
    }
    
    .archive-post {
        padding: 15px;
    }
    
    .post-actions {
        flex-direction: column;
        gap: 10px;
        align-items: flex-start;
    }
}
CSS;
    }
    
    /**
     * Get archive file path
     * 
     * @param string $date Date string in YYYY-MM-DD format
     * @return string File path
     */
    private function get_archive_file_path($date) {
        $year = date('Y', strtotime($date));
        $month = date('m', strtotime($date));
        
        $dir = $this->archive_dir . "/{$year}/{$month}";
        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
        }
        
        return "{$dir}/archive-{$date}.html";
    }
    
    /**
     * Get archive URL
     * 
     * @param string $date Date string in YYYY-MM-DD format
     * @return string Archive URL
     */
    private function get_archive_url($date) {
        $year = date('Y', strtotime($date));
        $month = date('m', strtotime($date));
        
        return "{$this->archive_url}/{$year}/{$month}/archive-{$date}.html";
    }
    
    /**
     * List all archives
     * 
     * @return array Array of archive information
     */
    public function list_archives() {
        $archives = [];
        if (!file_exists($this->archive_dir)) {
            return $archives;
        }
        
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->archive_dir, RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::FOLLOW_SYMLINKS)
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'html' && strpos($file->getFilename(), 'archive-') === 0) {
                    preg_match('/archive-([\d-]+)\.html/', $file->getFilename(), $matches);
                    if (isset($matches[1])) {
                        $date = $matches[1];
                        // Count posts in archive
                        $content = @file_get_contents($file->getPathname());
                        preg_match_all('/<article class="archive-post"/', $content, $post_matches);
                        $post_count = count($post_matches[0]);
                        
                        $archives[] = [
                            'date' => $date,
                            'file' => $file->getPathname(),
                            'url' => $this->get_archive_url($date),
                            'size' => $file->getSize(),
                            'created' => $file->getMTime(),
                            'post_count' => $post_count
                        ];
                    }
                }
            }
        } catch (Exception $e) {
            error_log('Geekline Archive List Error: ' . $e->getMessage());
            return [];
        }
        
        // Sort by date descending
        usort($archives, function($a, $b) {
            return strcmp($b['date'], $a['date']);
        });
        return $archives;
    }
    
    /**
     * Get archive statistics
     * 
     * @return array Archive statistics
     */
    public function get_archive_stats() {
        $archives = $this->list_archives();
        $total_size = 0;
        $total_posts = 0;
        
        foreach ($archives as $archive) {
            $total_size += $archive['size'];
            $total_posts += $archive['post_count'];
        }
        
        return [
            'total_archives' => count($archives),
            'total_size' => $total_size,
            'total_posts' => $total_posts,
            'oldest_archive' => !empty($archives) ? end($archives)['date'] : null,
            'newest_archive' => !empty($archives) ? $archives[0]['date'] : null
        ];
    }
    
    /**
     * Cleanup old archives
     * 
     * @return int Number of archives deleted
     */
    public function cleanup_old_archives() {
        $archives = $this->list_archives();
        // Get retention days again in case it was changed
        $this->retention_days = (int) get_option('tgf_archive_retention_days', 90);
        $cutoff_date = date('Y-m-d', strtotime("-{$this->retention_days} days"));
        
        $deleted = 0;
        
        foreach ($archives as $archive) {
            if ($archive['date'] < $cutoff_date) {
                if (unlink($archive['file'])) {
                    $deleted++;
                    error_log("Geekline Archive: Deleted old archive {$archive['date']}");
                } else {
                    error_log("Geekline Archive: Failed to delete old archive {$archive['date']}");
                }
            }
        }
        
        if ($deleted > 0) {
            error_log("Geekline Archive: Cleaned up {$deleted} old archives");
        }
        
        return $deleted;
    }
    
    /**
     * Create archive index page
     */
    private function create_archive_index() {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Geekline Archives</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            max-width: 900px; 
            margin: 40px auto; 
            padding: 20px; 
        }
        h1 { 
            color: #0073aa; 
        }
        .archive-list { 
            list-style: none; 
            padding: 0; 
        }
        .archive-list li { 
            padding: 10px; 
            border-bottom: 1px solid #eee; 
        }
        .archive-list a { 
            color: #0073aa; 
            text-decoration: none; 
            font-weight: 600; 
        }
        .archive-list a:hover { 
            text-decoration: underline; 
        }
    </style>
</head>
<body>
    <h1>Geekline Feed Archives</h1>
    <p>Browse historical archives of The Geekline feed.</p>
    <p><a href="/">Return to main site</a></p>
</body>
</html>
HTML;
        file_put_contents($this->archive_dir . '/index.html', $html);
    }
    
    /**
     * Serve archive list page
     */
    private function serve_archive_list() {
        $archives = $this->list_archives();
        $site_name = get_bloginfo('name');
		        $live_feed_url = apply_filters('tgf_live_feed_url', 'https://maniainc.com/the-geekline-feed');        
        header('Content-Type: text/html; charset=utf-8');
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php
                printf(
                    esc_html__('Geekline Archives | %s', 'geekline-feed'),
                    esc_html($site_name)
                );
            ?></title>
            <style>
                <?php echo $this->get_archive_css(); ?>
                .archive-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                    gap: 20px;
                    padding: 20px;
                }
                .archive-card {
                    background: #fff;
                    border: 1px solid #ddd;
                    border-radius: 8px;
                    padding: 20px;
                    text-align: center;
                    transition: transform 0.2s ease, box-shadow 0.2s ease;
                }
                .archive-card:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                }
                .archive-card a {
                    color: #0073aa;
                    text-decoration: none;
                    font-weight: 600;
                    font-size: 1.1rem;
                }
                .archive-card-meta {
                    color: #666;
                    font-size: 0.85rem;
                    margin-top: 10px;
                }
            </style>
        </head>
        <body>
            <div class="archive-container">
                <header class="archive-header">
                    <h1><?php esc_html_e('Geekline Feed Archives', 'geekline-feed'); ?></h1>
                     <p><?php esc_html_e('Browse historical archives of tech posts from the fediverse', 'geekline-feed'); ?></p>
                </header>
                <!-- ADD NAVIGATION HERE -->
            <div class="archive-navigation">
                <div class="nav-links">
                    <a href="<?php echo esc_url($live_feed_url); ?>" class="live-feed" target="_blank">📻 Live Feed</a>
                    <span class="nav-current">Main Archive</span>
                </div>
            </div>
                <div class="archive-grid">
                    <?php if (empty($archives)) : ?>
                         <p style="grid-column: 1/-1; text-align: center;"><?php esc_html_e('No archives available yet.', 'geekline-feed'); ?></p>
                    <?php else : ?>
                        <?php foreach ($archives as $archive) : ?>
                            <div class="archive-card">
                                  <a href="<?php echo esc_url($archive['url']); ?>">
                                    <?php echo esc_html(date('F j, Y', strtotime($archive['date']))); ?>
                                </a>
                                <div class="archive-card-meta">
                                     <?php
                                     printf(
                                         esc_html__('%s posts', 'geekline-feed'),
                                         number_format_i18n($archive['post_count'])
                                     );
                                     ?>
                                    <br>
                                    <?php echo size_format($archive['size']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <footer class="archive-footer">
                    <p><a href="<?php echo esc_url(home_url('/')); ?>">
                        <?php
                        printf(
                            esc_html__('Return to %s', 'geekline-feed'),
                            esc_html($site_name)
                        );
                        ?>
                    </a></p>
                </footer>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

/**
 * Initialize the archive manager
 */
function tgf_init_archive_manager() {
    // Ensure fetcher functions are loaded first
    if (!function_exists('tgf_get_posts_for_date')) {
        error_log('Geekline Archive: Fetcher functions not loaded! Archive manager cannot function.');
        add_action('admin_notices', function() {
            echo '<div class="error"><p>' . esc_html__('Geekline Archive Manager Error: Fetcher module not loaded. Please ensure fetcher.php is included before archive-manager.php', 'geekline-feed') . '</p></div>';
        });
        return null;
    }
    
    return new Geekline_Archive_Manager();
}

// Priority 15 to load after fetcher
add_action('plugins_loaded', 'tgf_init_archive_manager', 15);