<?php
/**
 * Admin Settings Page
 * 
 * @package Geekline
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define text domain if not already defined
if (!defined('TGF_TEXT_DOMAIN')) {
    define('TGF_TEXT_DOMAIN', 'geekline-feed');
}

/**
 * Register admin menu
 */
add_action('admin_menu', 'tgf_register_admin_menu');

function tgf_register_admin_menu() {
    add_menu_page(
        esc_html__('Geekline Feed', TGF_TEXT_DOMAIN),
        esc_html__('Geekline Feed', TGF_TEXT_DOMAIN),
        'manage_options',
        'geekline-feed',
        'tgf_render_admin_page',
        'dashicons-rss',
        80
    );
    
    // Add submenu for better organization
    add_submenu_page(
        'geekline-feed',
        esc_html__('Settings', TGF_TEXT_DOMAIN),
        esc_html__('Settings', TGF_TEXT_DOMAIN),
        'manage_options',
        'geekline-feed',
        'tgf_render_admin_page'
    );
    
    add_submenu_page(
        'geekline-feed',
        esc_html__('Analytics', TGF_TEXT_DOMAIN),
        esc_html__('Analytics', TGF_TEXT_DOMAIN),
        'manage_options',
        'geekline-analytics',
        'tgf_render_analytics_page'
    );

    // ADD ARCHIVES SUBMENU
    add_submenu_page(
        'geekline-feed',
        esc_html__('Archives', TGF_TEXT_DOMAIN),
        esc_html__('Archives', TGF_TEXT_DOMAIN),
        'manage_options',
        'geekline-archives',
        'tgf_render_archives_page'
    );
}

/**
 * Render main admin page
 */
function tgf_render_admin_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', TGF_TEXT_DOMAIN));
    }
    
    // Get current tab
    $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('The Geekline Feed Settings', TGF_TEXT_DOMAIN); ?></h1>
        
        <?php settings_errors(); ?>
        
        <!-- Tabs Navigation -->
        <nav class="nav-tab-wrapper">
            <a href="?page=geekline-feed&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('General', TGF_TEXT_DOMAIN); ?>
            </a>
            <a href="?page=geekline-feed&tab=authentication" class="nav-tab <?php echo $active_tab === 'authentication' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Authentication', TGF_TEXT_DOMAIN); ?>
            </a>
            <a href="?page=geekline-feed&tab=advanced" class="nav-tab <?php echo $active_tab === 'advanced' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Advanced', TGF_TEXT_DOMAIN); ?>
            </a>
            <a href="?page=geekline-feed&tab=help" class="nav-tab <?php echo $active_tab === 'help' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Help', TGF_TEXT_DOMAIN); ?>
            </a>
        </nav>
        
        <form method="post" action="options.php">
            <?php
            switch ($active_tab) {
                case 'authentication':
                    settings_fields('tgf_auth_settings');
                    do_settings_sections('tgf_auth_settings');
                    tgf_render_authentication_tab();
                    break;
                    
                case 'advanced':
                    settings_fields('tgf_advanced_settings');
                    do_settings_sections('tgf_advanced_settings');
                    tgf_render_advanced_tab();
                    break;
                    
                case 'help':
                    tgf_render_help_tab();
                    break;
                    
                default:
                    settings_fields('tgf_settings_group');
                    do_settings_sections('tgf_settings_group');
                    tgf_render_general_tab();
                    break;
            }
            
            // Only show submit button on settings tabs
            if ($active_tab !== 'help') {
                submit_button();
            }
            ?>
        </form>
    </div>
    
    <style>
        .tgf-admin-section {
            background: #fff;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .tgf-admin-section h2 {
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .tgf-status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }
        .tgf-status-indicator.success {
            background: #46b450;
        }
        .tgf-status-indicator.error {
            background: #dc3232;
        }
        .tgf-status-indicator.warning {
            background: #ffb900;
        }
        .tgf-help-box {
            background: #f0f6fc;
            border-left: 4px solid #0073aa;
            padding: 15px;
            margin: 15px 0;
        }
        .tgf-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .tgf-stat-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            padding: 20px;
            text-align: center;
            border-radius: 4px;
        }
        .tgf-stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #0073aa;
        }
        .tgf-stat-label {
            color: #666;
            margin-top: 5px;
        }
        #tgf-cache-message,
        #tgf-prune-message,
        #tgf-archive-message {
            display:none; 
            color: #46b450; 
            margin-left: 10px;
        }
    </style>
    <?php
}

/**
 * Render General Settings Tab
 */
function tgf_render_general_tab() {
    ?>
    <div class="tgf-admin-section">
        <h2><?php esc_html_e('Feed Sources', TGF_TEXT_DOMAIN); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="tgf_sources"><?php esc_html_e('Sources', TGF_TEXT_DOMAIN); ?></label>
                </th>
                <td>
                    <textarea 
                        name="tgf_sources" 
                        id="tgf_sources"
                        rows="5" 
                        class="large-text code"
                        placeholder="at://did:plc:example123/app.bsky.feed.generator/tech-feed"
                    ><?php echo esc_textarea(get_option('tgf_sources', '')); ?></textarea>
                    <p class="description">
                        <?php esc_html_e('Enter BlueSky feed URIs or API endpoints, one per line.', TGF_TEXT_DOMAIN); ?><br>
                        <strong><?php esc_html_e('Example:', TGF_TEXT_DOMAIN); ?></strong> 
                        <code>at://did:plc:example123/app.bsky.feed.generator/tech-feed</code>
                    </p>
                    <?php 
                    $sources = get_option('tgf_sources', '');
                    if (!empty($sources)) {
                        $count = count(array_filter(explode("\n", $sources)));
                        echo '<p><span class="tgf-status-indicator success"></span>';
                        echo sprintf(
                            esc_html(_n('%d source configured', '%d sources configured', $count, TGF_TEXT_DOMAIN)), 
                            $count
                        );
                        echo '</p>';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tgf_max_posts"><?php esc_html_e('Max Posts to Fetch', TGF_TEXT_DOMAIN); ?></label>
                </th>
                <td>
                    <input 
                        type="number" 
                        name="tgf_max_posts" 
                        id="tgf_max_posts"
                        value="<?php echo esc_attr(get_option('tgf_max_posts', 100)); ?>" 
                        min="10" 
                        max="500"
                        class="small-text"
                    />
                    <p class="description">
                        <?php esc_html_e('Maximum number of posts to fetch and cache (10-500). Default: 100', TGF_TEXT_DOMAIN); ?>
                    </p>
                </td>
            </tr>
        </table>
    </div>

    <div class="tgf-admin-section">
        <h2><?php esc_html_e('Content Filtering', TGF_TEXT_DOMAIN); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="tgf_bad_words"><?php esc_html_e('Bad Words Filter', TGF_TEXT_DOMAIN); ?></label>
                </th>
                <td>
                    <input 
                        type="text" 
                        name="tgf_bad_words" 
                        id="tgf_bad_words"
                        value="<?php echo esc_attr(get_option('tgf_bad_words', '')); ?>" 
                        class="large-text"
                        placeholder="spam, inappropriate, offensive"
                    />
                    <p class="description">
                        <?php esc_html_e('Comma-separated list of words to filter out from posts.', TGF_TEXT_DOMAIN); ?>
                    </p>
                </td>
            </tr>
        </table>
    </div>

    <div class="tgf-admin-section">
        <h2><?php esc_html_e('Social Sharing', TGF_TEXT_DOMAIN); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="tgf_page_link"><?php esc_html_e('Page Link', TGF_TEXT_DOMAIN); ?></label>
                </th>
                <td>
                    <input 
                        type="url" 
                        name="tgf_page_link" 
                        id="tgf_page_link"
                        value="<?php echo esc_url(get_option('tgf_page_link', home_url('/thegeekline'))); ?>" 
                        class="regular-text"
                        placeholder="<?php echo esc_attr(home_url('/thegeekline')); ?>"
                    />
                    <p class="description">
                        <?php esc_html_e('URL to your Geekline page (used in social sharing).', TGF_TEXT_DOMAIN); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tgf_hashtags"><?php esc_html_e('Hashtags', TGF_TEXT_DOMAIN); ?></label>
                </th>
                <td>
                    <input 
                        type="text" 
                        name="tgf_hashtags" 
                        id="tgf_hashtags"
                        value="<?php echo esc_attr(get_option('tgf_hashtags', '#TheGeekline')); ?>" 
                        class="regular-text"
                        placeholder="#TheGeekline #Tech"
                    />
                    <p class="description">
                        <?php esc_html_e('Hashtags to include in social shares (space-separated).', TGF_TEXT_DOMAIN); ?>
                    </p>
                </td>
            </tr>
        </table>
    </div>

    <div class="tgf-admin-section">
        <h2><?php esc_html_e('Comments Integration', TGF_TEXT_DOMAIN); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="tgf_disqus_shortname"><?php esc_html_e('Disqus Shortname', TGF_TEXT_DOMAIN); ?></label>
                </th>
                <td>
                    <input 
                        type="text" 
                        name="tgf_disqus_shortname" 
                        id="tgf_disqus_shortname"
                        value="<?php echo esc_attr(get_option('tgf_disqus_shortname', '')); ?>" 
                        class="regular-text"
                        placeholder="your-site-name"
                    />
                    <p class="description">
                        <?php 
                        printf(
                            esc_html__('Optional: Enter your Disqus shortname to enable comments. Get it from %s', TGF_TEXT_DOMAIN),
                            '<a href="https://disqus.com/admin/create/" target="_blank" rel="noopener noreferrer">Disqus</a>'
                        ); 
                        ?>
                    </p>
                    <?php 
                    $disqus = get_option('tgf_disqus_shortname', '');
                    if (!empty($disqus)) {
                        echo '<p><span class="tgf-status-indicator success"></span>';
                        echo esc_html__('Disqus integration enabled', TGF_TEXT_DOMAIN);
                        echo '</p>';
                    }
                    ?>
                </td>
            </tr>
        </table>
    </div>
    <?php
}

/**
 * Render Authentication Tab
 */
function tgf_render_authentication_tab() {
    $bearer_token = get_option('tgf_bsky_bearer_token', '');
    $refresh_token = get_option('tgf_bsky_refresh_token', '');
    $has_bearer = !empty($bearer_token);
    $has_refresh = !empty($refresh_token);
    ?>
    <div class="tgf-admin-section">
        <h2><?php esc_html_e('BlueSky Authentication', TGF_TEXT_DOMAIN); ?></h2>
        
        <div class="tgf-help-box">
            <strong><?php esc_html_e('Security Note:', TGF_TEXT_DOMAIN); ?></strong>
            <?php esc_html_e('For security reasons, tokens should be set via SSH/WP-CLI, not through this admin interface.', TGF_TEXT_DOMAIN); ?>
            <p><?php esc_html_e('Use the following commands on your server:', TGF_TEXT_DOMAIN); ?></p>
            <pre style="background: #fff; padding: 10px; border: 1px solid #ddd; overflow-x: auto;">
# Set Bearer Token
wp option update tgf_bsky_bearer_token "YOUR_ACCESS_TOKEN_HERE"

# Set Refresh Token (lasts 90 days)
wp option update tgf_bsky_refresh_token "YOUR_REFRESH_TOKEN_HERE"</pre>
        </div>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Token Status', TGF_TEXT_DOMAIN); ?></th>
                <td>
                    <p>
                        <span class="tgf-status-indicator <?php echo $has_bearer ? 'success' : 'error'; ?>"></span>
                        <strong><?php esc_html_e('Bearer Token:', TGF_TEXT_DOMAIN); ?></strong>
                        <?php 
                        if ($has_bearer) {
                            echo esc_html__('Configured', TGF_TEXT_DOMAIN);
                            echo ' <em>(' . esc_html__('Expires in ~2 hours', TGF_TEXT_DOMAIN) . ')</em>';
                        } else {
                            echo '<span style="color: #dc3232;">' . esc_html__('Not set', TGF_TEXT_DOMAIN) . '</span>';
                        }
                        ?>
                    </p>
                    <p>
                        <span class="tgf-status-indicator <?php echo $has_refresh ? 'success' : 'warning'; ?>"></span>
                        <strong><?php esc_html_e('Refresh Token:', TGF_TEXT_DOMAIN); ?></strong>
                        <?php 
                        if ($has_refresh) {
                            echo esc_html__('Configured', TGF_TEXT_DOMAIN);
                            echo ' <em>(' . esc_html__('Lasts 90 days', TGF_TEXT_DOMAIN) . ')</em>';
                        } else {
                            echo '<span style="color: #ffb900;">' . esc_html__('Not set (tokens won\'t auto-refresh)', TGF_TEXT_DOMAIN) . '</span>';
                        }
                        ?>
                    </p>
                    
                    <?php if ($has_bearer || $has_refresh) : ?>
                    <p>
                        <button type="button" id="tgf-clear-tokens" class="button button-secondary">
                            <?php esc_html_e('Clear All Tokens', TGF_TEXT_DOMAIN); ?>
                        </button>
                    </p>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#tgf-clear-tokens').on('click', function() {
            if (!confirm('<?php echo esc_js(__('Are you sure you want to clear all authentication tokens?', TGF_TEXT_DOMAIN)); ?>')) {
                return;
            }
            
            var button = $(this);
            button.prop('disabled', true);
            
            $.post(ajaxurl, {
                action: 'tgf_clear_tokens',
                nonce: '<?php echo wp_create_nonce('tgf_clear_tokens'); ?>'
            }, function(response) {
                if (response.success) {
                    alert('<?php echo esc_js(__('Tokens cleared successfully. Please reload the page.', TGF_TEXT_DOMAIN)); ?>');
                    location.reload();
                } else {
                    alert('<?php echo esc_js(__('Error clearing tokens.', TGF_TEXT_DOMAIN)); ?>');
                    button.prop('disabled', false);
                }
            });
        });
    });
    </script>
    <?php
}

/**
 * Render Advanced Tab - UPDATED WITH ARCHIVE FEATURES
 */
function tgf_render_advanced_tab() {
    ?>
    <div class="tgf-admin-section">
        <h2><?php esc_html_e('Cache Management', TGF_TEXT_DOMAIN); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Clear Cache', TGF_TEXT_DOMAIN); ?></th>
                <td>
                    <button type="button" id="tgf-clear-cache" class="button button-secondary">
                        <?php esc_html_e('Clear Posts Cache', TGF_TEXT_DOMAIN); ?>
                    </button>
                    <p class="description">
                        <?php esc_html_e('Force refresh of cached posts from sources. Caches are automatically cleared every hour.', TGF_TEXT_DOMAIN); ?>
                    </p>
                    <span id="tgf-cache-message" style="display:none; color: #46b450; margin-left: 10px;"></span>
                </td>
            </tr>
        </table>
    </div>

    <div class="tgf-admin-section">
        <h2><?php esc_html_e('Database Maintenance', TGF_TEXT_DOMAIN); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Repost Data', TGF_TEXT_DOMAIN); ?></th>
                <td>
                    <?php
                    global $wpdb;
                    $table = $wpdb->prefix . 'geekline_reposts';
                    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
                    $total_reposts = $wpdb->get_var("SELECT SUM(reposts_count) FROM $table");
                    ?>
                    <p>
                        <?php 
                        printf(
                            esc_html__('Database contains %1$d posts with %2$d total shares.', TGF_TEXT_DOMAIN),
                            number_format_i18n($count),
                            number_format_i18n($total_reposts)
                        ); 
                        ?>
                    </p>
                    <button type="button" id="tgf-reset-reposts" class="button button-secondary">
                        <?php esc_html_e('Reset All Repost Counts', TGF_TEXT_DOMAIN); ?>
                    </button>
                    <p class="description">
                        <?php esc_html_e('Warning: This will reset all repost/share counts to zero.', TGF_TEXT_DOMAIN); ?>
                    </p>
                </td>
            </tr>
            
            <!-- ADD ARCHIVED POSTS MAINTENANCE -->
            <tr>
                <th scope="row"><?php esc_html_e('Archived Posts Data', TGF_TEXT_DOMAIN); ?></th>
                <td>
                    <?php
                    $table_posts = $wpdb->prefix . 'geekline_posts';
                    $count_posts = $wpdb->get_var("SELECT COUNT(*) FROM $table_posts");
                    ?>
                    <p>
                        <?php 
                        printf(
                            esc_html__('Archived posts table contains %s posts.', TGF_TEXT_DOMAIN),
                            number_format_i18n($count_posts)
                        ); 
                        ?>
                    </p>
                    <button type="button" id="tgf-prune-posts" class="button button-secondary">
                        <?php esc_html_e('Prune Old Archived Posts', TGF_TEXT_DOMAIN); ?>
                    </button>
                    <p class="description">
                        <?php esc_html_e('Deletes archived posts from the database older than the "Retention Period" (set below).', TGF_TEXT_DOMAIN); ?>
                    </p>
                    <span id="tgf-prune-message" style="display:none; color: #46b450; margin-left: 10px;"></span>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- ADD ARCHIVE SETTINGS SECTION -->
    <div class="tgf-admin-section">
        <h2><?php esc_html_e('Archive Settings', TGF_TEXT_DOMAIN); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Retention Period', TGF_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="number" name="tgf_archive_retention_days" 
                           value="<?php echo esc_attr(get_option('tgf_archive_retention_days', 90)); ?>" 
                           min="30" max="365" class="small-text"> <?php esc_html_e('days', TGF_TEXT_DOMAIN); ?>
                    <p class="description"><?php esc_html_e('How long to keep static HTML archives AND database post records (30-365 days).', TGF_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Posts Per Archive Page', TGF_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="number" name="tgf_archive_posts_per_page" 
                           value="<?php echo esc_attr(get_option('tgf_archive_posts_per_page', 50)); ?>" 
                           min="10" max="200" class="small-text">
                    <p class="description"><?php esc_html_e('Number of posts to display per static archive page (for future pagination).', TGF_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
        </table>
    </div>

    <div class="tgf-admin-section">
        <h2><?php esc_html_e('Debug Information', TGF_TEXT_DOMAIN); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('System Info', TGF_TEXT_DOMAIN); ?></th>
                <td>
                    <textarea readonly class="large-text code" rows="10"><?php
                        echo 'Plugin Version: ' . TGF_VERSION . "\n";
                        echo 'WordPress Version: ' . get_bloginfo('version') . "\n";
                        echo 'PHP Version: ' . PHP_VERSION . "\n";
                        echo 'MySQL Version: ' . $wpdb->db_version() . "\n";
                        echo 'Server: ' . $_SERVER['SERVER_SOFTWARE'] . "\n";
                        echo 'Max Execution Time: ' . ini_get('max_execution_time') . 's' . "\n";
                        echo 'Memory Limit: ' . ini_get('memory_limit') . "\n";
                        echo 'Timezone: ' . wp_timezone_string() . "\n";
                    ?></textarea>
                    <p class="description">
                        <?php esc_html_e('Copy this information when requesting support.', TGF_TEXT_DOMAIN); ?>
                    </p>
                </td>
            </tr>
        </table>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#tgf-clear-cache').on('click', function() {
            var button = $(this);
            button.prop('disabled', true).text('<?php echo esc_js(__('Clearing...', TGF_TEXT_DOMAIN)); ?>');
            
            $.post(ajaxurl, {
                action: 'tgf_clear_cache',
                nonce: '<?php echo wp_create_nonce('tgf_clear_cache'); ?>'
            }, function(response) {
                button.prop('disabled', false).text('<?php echo esc_js(__('Clear Posts Cache', TGF_TEXT_DOMAIN)); ?>');
                
                if (response.success) {
                    $('#tgf-cache-message')
                        .text('<?php echo esc_js(__('Cache cleared successfully!', TGF_TEXT_DOMAIN)); ?>')
                        .fadeIn().delay(3000).fadeOut();
                } else {
                    alert('<?php echo esc_js(__('Error clearing cache', TGF_TEXT_DOMAIN)); ?>');
                }
            });
        });
        
        $('#tgf-reset-reposts').on('click', function() {
            if (!confirm('<?php echo esc_js(__('Are you sure? This will reset ALL repost counts to zero.', TGF_TEXT_DOMAIN)); ?>')) {
                return;
            }
            
            var button = $(this);
            button.prop('disabled', true);
            
            $.post(ajaxurl, {
                action: 'tgf_reset_reposts',
                nonce: '<?php echo wp_create_nonce('tgf_reset_reposts'); ?>'
            }, function(response) {
                if (response.success) {
                    alert('<?php echo esc_js(__('Repost counts reset successfully.', TGF_TEXT_DOMAIN)); ?>');
                    location.reload();
                } else {
                    alert('<?php echo esc_js(__('Error resetting reposts.', TGF_TEXT_DOMAIN)); ?>');
                    button.prop('disabled', false);
                }
            });
        });

        // ADD PRUNE POSTS FUNCTIONALITY
        $('#tgf-prune-posts').on('click', function() {
            if (!confirm('<?php echo esc_js(__('Are you sure? This will permanently delete old posts from the database based on your retention settings.', TGF_TEXT_DOMAIN)); ?>')) {
                return;
            }
            
            var button = $(this);
            button.prop('disabled', true).text('<?php echo esc_js(__('Pruning...', TGF_TEXT_DOMAIN)); ?>');
            
            $.post(ajaxurl, {
                action: 'tgf_prune_posts',
                nonce: '<?php echo wp_create_nonce('tgf_prune_posts'); ?>'
            }, function(response) {
                button.prop('disabled', false).text('<?php echo esc_js(__('Prune Old Archived Posts', TGF_TEXT_DOMAIN)); ?>');
                
                if (response.success) {
                    $('#tgf-prune-message')
                        .text(response.data.message || '<?php echo esc_js(__('Pruning successful!', TGF_TEXT_DOMAIN)); ?>')
                        .fadeIn().delay(3000).fadeOut();
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    alert('<?php echo esc_js(__('Error pruning posts:', TGF_TEXT_DOMAIN)); ?> ' + (response.data.message || 'Unknown error'));
                }
            });
        });
    });
    </script>
    <?php
}

/**
 * Render Help Tab
 */
function tgf_render_help_tab() {
    ?>
    <div class="tgf-admin-section">
        <h2><?php esc_html_e('Usage Instructions', TGF_TEXT_DOMAIN); ?></h2>
        
        <h3><?php esc_html_e('Shortcode', TGF_TEXT_DOMAIN); ?></h3>
        <p><?php esc_html_e('Add the feed to any page or post using:', TGF_TEXT_DOMAIN); ?></p>
        <pre style="background: #f5f5f5; padding: 15px; border-left: 4px solid #0073aa;"><code>[geekline_feed]</code></pre>
        
        <h4><?php esc_html_e('Shortcode Attributes', TGF_TEXT_DOMAIN); ?></h4>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php esc_html_e('Attribute', TGF_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Description', TGF_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Example', TGF_TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>show_tabs</code></td>
                    <td><?php esc_html_e('Show or hide filter tabs', TGF_TEXT_DOMAIN); ?></td>
                    <td><code>[geekline_feed show_tabs="false"]</code></td>
                </tr>
                <tr>
                    <td><code>default_tab</code></td>
                    <td><?php esc_html_e('Set default active tab', TGF_TEXT_DOMAIN); ?></td>
                    <td><code>[geekline_feed default_tab="reposts"]</code></td>
                </tr>
                <tr>
                    <td><code>title</code></td>
                    <td><?php esc_html_e('Custom feed title', TGF_TEXT_DOMAIN); ?></td>
                    <td><code>[geekline_feed title="Tech Feed"]</code></td>
                </tr>
                <tr>
                    <td><code>description</code></td>
                    <td><?php esc_html_e('Custom feed description', TGF_TEXT_DOMAIN); ?></td>
                    <td><code>[geekline_feed description="Latest posts"]</code></td>
                </tr>
            </tbody>
        </table>

        <h3 style="margin-top: 30px;"><?php esc_html_e('Page Template', TGF_TEXT_DOMAIN); ?></h3>
        <ol>
            <li><?php esc_html_e('Create a new page in WordPress', TGF_TEXT_DOMAIN); ?></li>
            <li><?php esc_html_e('Select "Geekline Feed Page" from the Page Attributes > Template dropdown', TGF_TEXT_DOMAIN); ?></li>
            <li><?php esc_html_e('Publish the page', TGF_TEXT_DOMAIN); ?></li>
        </ol>

        <h3 style="margin-top: 30px;"><?php esc_html_e('Authentication Setup', TGF_TEXT_DOMAIN); ?></h3>
        <p><?php esc_html_e('To connect to BlueSky, you need to set authentication tokens via SSH:', TGF_TEXT_DOMAIN); ?></p>
        <pre style="background: #2c3338; color: #fff; padding: 15px; overflow-x: auto;">
# 1. Generate tokens via API
curl -X POST "https://bsky.social/xrpc/com.atproto.server.createSession" \
     -H "Content-Type: application/json" \
     -d '{"identifier":"your-email@example.com","password":"your-app-password"}'

# 2. Set tokens in WordPress
wp option update tgf_bsky_bearer_token "YOUR_ACCESS_TOKEN"
wp option update tgf_bsky_refresh_token "YOUR_REFRESH_TOKEN"</pre>
    </div>

    <div class="tgf-admin-section">
        <h2><?php esc_html_e('Troubleshooting', TGF_TEXT_DOMAIN); ?></h2>
        <dl>
            <dt><strong><?php esc_html_e('No posts showing?', TGF_TEXT_DOMAIN); ?></strong></dt>
            <dd>
                <ul>
                    <li><?php esc_html_e('Check that you have configured at least one source', TGF_TEXT_DOMAIN); ?></li>
                    <li><?php esc_html_e('Verify authentication tokens are set correctly', TGF_TEXT_DOMAIN); ?></li>
                    <li><?php esc_html_e('Try clearing the cache in the Advanced tab', TGF_TEXT_DOMAIN); ?></li>
                    <li><?php esc_html_e('Check browser console for JavaScript errors', TGF_TEXT_DOMAIN); ?></li>
                </ul>
            </dd>
            
            <dt style="margin-top: 20px;"><strong><?php esc_html_e('Authentication errors?', TGF_TEXT_DOMAIN); ?></strong></dt>
            <dd>
                <ul>
                    <li><?php esc_html_e('Tokens expire after 2 hours (bearer) or 90 days (refresh)', TGF_TEXT_DOMAIN); ?></li>
                    <li><?php esc_html_e('The refresh token automatically renews the bearer token', TGF_TEXT_DOMAIN); ?></li>
                    <li><?php esc_html_e('Regenerate tokens if you see 401 errors in logs', TGF_TEXT_DOMAIN); ?></li>
                </ul>
            </dd>
            
            <dt style="margin-top: 20px;"><strong><?php esc_html_e('Performance issues?', TGF_TEXT_DOMAIN); ?></strong></dt>
            <dd>
                <ul>
                    <li><?php esc_html_e('Reduce the "Max Posts to Fetch" setting', TGF_TEXT_DOMAIN); ?></li>
                    <li><?php esc_html_e('Enable object caching (Redis, Memcached)', TGF_TEXT_DOMAIN); ?></li>
                    <li><?php esc_html_e('Check your server PHP memory limit', TGF_TEXT_DOMAIN); ?></li>
                </ul>
            </dd>
        </dl>
    </div>

    <div class="tgf-admin-section">
        <h2><?php esc_html_e('Support & Documentation', TGF_TEXT_DOMAIN); ?></h2>
        <p><?php esc_html_e('Need help? Here are some resources:', TGF_TEXT_DOMAIN); ?></p>
        <ul>
            <li><a href="https://maniainc.com/thegeekline" target="_blank"><?php esc_html_e('Official Plugin Page', TGF_TEXT_DOMAIN); ?></a></li>
            <li><a href="https://docs.bsky.app/" target="_blank"><?php esc_html_e('BlueSky API Documentation', TGF_TEXT_DOMAIN); ?></a></li>
            <li><a href="https://wordpress.org/support/" target="_blank"><?php esc_html_e('WordPress Support Forums', TGF_TEXT_DOMAIN); ?></a></li>
        </ul>
    </div>
    <?php
}

/**
 * Render Analytics Page
 */
function tgf_render_analytics_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', TGF_TEXT_DOMAIN));
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'geekline_reposts';
    
    // Get statistics
    $total_posts = $wpdb->get_var("SELECT COUNT(*) FROM $table");
    $total_reposts = $wpdb->get_var("SELECT SUM(reposts_count) FROM $table");
    $avg_reposts = $wpdb->get_var("SELECT AVG(reposts_count) FROM $table");
    $top_posts = $wpdb->get_results("SELECT * FROM $table ORDER BY reposts_count DESC LIMIT 10");
    
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Geekline Analytics', TGF_TEXT_DOMAIN); ?></h1>
        
        <div class="tgf-stats-grid">
            <div class="tgf-stat-card">
                <div class="tgf-stat-number"><?php echo number_format_i18n($total_posts); ?></div>
                <div class="tgf-stat-label"><?php esc_html_e('Total Posts', TGF_TEXT_DOMAIN); ?></div>
            </div>
            
            <div class="tgf-stat-card">
                <div class="tgf-stat-number"><?php echo number_format_i18n($total_reposts); ?></div>
                <div class="tgf-stat-label"><?php esc_html_e('Total Shares', TGF_TEXT_DOMAIN); ?></div>
            </div>
            
            <div class="tgf-stat-card">
                <div class="tgf-stat-number"><?php echo number_format_i18n($avg_reposts, 1); ?></div>
                <div class="tgf-stat-label"><?php esc_html_e('Avg. Shares per Post', TGF_TEXT_DOMAIN); ?></div>
            </div>
            
            <div class="tgf-stat-card">
                <div class="tgf-stat-number">
                    <?php 
                    $cache = get_transient('tgf_posts_cache');
                    echo $cache ? number_format_i18n(count($cache)) : '0';
                    ?>
                </div>
                <div class="tgf-stat-label"><?php esc_html_e('Cached Posts', TGF_TEXT_DOMAIN); ?></div>
            </div>
        </div>
        
        <div class="tgf-admin-section">
            <h2><?php esc_html_e('Most Shared Posts', TGF_TEXT_DOMAIN); ?></h2>
            <?php if ($top_posts) : ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Rank', TGF_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Post URL', TGF_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Shares', TGF_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Last Shared', TGF_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_posts as $index => $post) : ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <a href="<?php echo esc_url($post->post_url); ?>" target="_blank" rel="noopener noreferrer">
                                        <?php echo esc_html(wp_trim_words($post->post_url, 8, '...')); ?>
                                    </a>
                                </td>
                                <td><strong><?php echo number_format_i18n($post->reposts_count); ?></strong></td>
                                <td><?php echo esc_html(human_time_diff(strtotime($post->last_reposted), current_time('timestamp'))); ?> <?php esc_html_e('ago', TGF_TEXT_DOMAIN); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php esc_html_e('No data available yet. Start sharing posts!', TGF_TEXT_DOMAIN); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="tgf-admin-section">
            <h2><?php esc_html_e('Recent Activity', TGF_TEXT_DOMAIN); ?></h2>
            <?php
            $recent_posts = $wpdb->get_results("SELECT * FROM $table ORDER BY last_reposted DESC LIMIT 10");
            if ($recent_posts) :
            ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Post URL', TGF_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Total Shares', TGF_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Last Activity', TGF_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_posts as $post) : ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url($post->post_url); ?>" target="_blank" rel="noopener noreferrer">
                                        <?php echo esc_html(wp_trim_words($post->post_url, 8, '...')); ?>
                                    </a>
                                </td>
                                <td><?php echo number_format_i18n($post->reposts_count); ?></td>
                                <td><?php echo esc_html(human_time_diff(strtotime($post->last_reposted), current_time('timestamp'))); ?> <?php esc_html_e('ago', TGF_TEXT_DOMAIN); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php esc_html_e('No recent activity.', TGF_TEXT_DOMAIN); ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Render Archives Page
 */
function tgf_render_archives_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', TGF_TEXT_DOMAIN));
    }
    
    $upload_dir = wp_upload_dir();
    $archive_dir = $upload_dir['basedir'] . '/geekline-archives';
    $archive_url = $upload_dir['baseurl'] . '/geekline-archives';
    $retention_days = (int) get_option('tgf_archive_retention_days', 90);
    
    $archives = tgf_admin_list_archives($archive_dir, $archive_url);
    $stats = tgf_admin_get_archive_stats($archives, $retention_days);
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Geekline Archives', TGF_TEXT_DOMAIN); ?></h1>
        
        <div class="tgf-admin-section">
            <h2><?php esc_html_e('Archive Management', TGF_TEXT_DOMAIN); ?></h2>
            <p><?php esc_html_e('Static archives preserve your feed content for SEO and historical reference.', TGF_TEXT_DOMAIN); ?></p>
            
            <p>
                <button type="button" id="tgf-generate-archive" class="button button-primary">
                    <?php esc_html_e('Generate Today\'s Archive', TGF_TEXT_DOMAIN); ?>
                </button>
                <button type="button" id="tgf-refresh-archives" class="button button-secondary">
                    <?php esc_html_e('Refresh Archive List', TGF_TEXT_DOMAIN); ?>
                </button>
            </p>
            <p class="description">
                <?php esc_html_e('Archives are generated automatically overnight. You can force-generate today\'s archive here.', TGF_TEXT_DOMAIN); ?>
            </p>
            
            <div id="tgf-archive-progress" style="display:none; margin-top: 15px;">
                <div class="notice notice-info"><p><?php esc_html_e('Generating archive...', TGF_TEXT_DOMAIN); ?></p></div>
            </div>
            <span id="tgf-archive-message" style="display:none; color: #46b450; margin-left: 10px;"></span>
        </div>
        
        <div class="tgf-stats-grid">
            <div class="tgf-stat-card">
                <div class="tgf-stat-number"><?php echo number_format_i18n($stats['total_archives']); ?></div>
                <div class="tgf-stat-label"><?php esc_html_e('Total Archives', TGF_TEXT_DOMAIN); ?></div>
            </div>
            <div class="tgf-stat-card">
                <div class="tgf-stat-number"><?php echo size_format($stats['total_size']); ?></div>
                <div class="tgf-stat-label"><?php esc_html_e('Total Size', TGF_TEXT_DOMAIN); ?></div>
            </div>
            <div class="tgf-stat-card">
                <div class="tgf-stat-number"><?php echo number_format_i18n($stats['total_posts']); ?></div>
                <div class="tgf-stat-label"><?php esc_html_e('Archived Posts (in files)', TGF_TEXT_DOMAIN); ?></div>
            </div>
            <div class="tgf-stat-card">
                <div class="tgf-stat-number"><?php echo number_format_i18n($stats['retention_days']); ?></div>
                <div class="tgf-stat-label"><?php esc_html_e('Days Retained', TGF_TEXT_DOMAIN); ?></div>
            </div>
        </div>
        
        <div class="tgf-admin-section">
            <h2><?php esc_html_e('Available Archives', TGF_TEXT_DOMAIN); ?></h2>
            <p class="description">
                <?php esc_html_e('View public archives at:', TGF_TEXT_DOMAIN); ?>
                <a href="<?php echo esc_url(home_url('/geekline-archive/')); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_url(home_url('/geekline-archive/')); ?></a>
            </p>
            <table class="widefat striped" id="tgf-archives-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Date', TGF_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Posts', TGF_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Size', TGF_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Created', TGF_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Actions', TGF_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($archives)) : ?>
                        <tr><td colspan="5"><?php esc_html_e('No archives yet. Click "Generate Today\'s Archive" to create one.', TGF_TEXT_DOMAIN); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ($archives as $archive) : ?>
                            <tr>
                                <td><strong><?php echo esc_html($archive['date']); ?></strong></td>
                                <td><?php echo number_format_i18n($archive['post_count']); ?></td>
                                <td><?php echo size_format($archive['size']); ?></td>
                                <td><?php echo esc_html(human_time_diff($archive['created'], current_time('timestamp'))); ?> <?php esc_html_e('ago', TGF_TEXT_DOMAIN); ?></td>
                                <td>
                                    <a href="<?php echo esc_url($archive['url']); ?>" target="_blank" rel="noopener noreferrer" class="button button-small"><?php esc_html_e('View', TGF_TEXT_DOMAIN); ?></a>
                                    <button type="button" class="button button-small tgf-delete-archive" data-date="<?php echo esc_attr($archive['date']); ?>"><?php esc_html_e('Delete', TGF_TEXT_DOMAIN); ?></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Generate Archive
        $('#tgf-generate-archive').on('click', function() {
            var button = $(this);
            button.prop('disabled', true).text('<?php echo esc_js(__('Generating...', TGF_TEXT_DOMAIN)); ?>');
            $('#tgf-archive-progress').show();
            
            $.post(ajaxurl, {
                action: 'tgf_generate_archive_now',
                nonce: '<?php echo wp_create_nonce('tgf_archive_action'); ?>'
            }, function(response) {
                button.prop('disabled', false).text('<?php echo esc_js(__('Generate Today\'s Archive', TGF_TEXT_DOMAIN)); ?>');
                $('#tgf-archive-progress').hide();
                
                if (response.success) {
                    $('#tgf-archive-message')
                        .text('<?php echo esc_js(__('Archive generated successfully!', TGF_TEXT_DOMAIN)); ?>')
                        .fadeIn().delay(3000).fadeOut();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert('<?php echo esc_js(__('Error generating archive', TGF_TEXT_DOMAIN)); ?>: ' + (response.data.message || 'Unknown error'));
                }
            });
        });
        
        // Refresh List
        $('#tgf-refresh-archives').on('click', function() {
            location.reload();
        });

        // Delete Archive
        $('#tgf-archives-table').on('click', '.tgf-delete-archive', function() {
            if (!confirm('<?php echo esc_js(__('Are you sure you want to delete this HTML archive file?', TGF_TEXT_DOMAIN)); ?>')) {
                return;
            }
            
            var button = $(this);
            var date = button.data('date');
            
            button.prop('disabled', true);
            
            $.post(ajaxurl, {
                action: 'tgf_delete_archive',
                date: date,
                nonce: '<?php echo wp_create_nonce('tgf_archive_action'); ?>'
            }, function(response) {
                if (response.success) {
                    button.closest('tr').fadeOut();
                } else {
                    alert('<?php echo esc_js(__('Error deleting archive', TGF_TEXT_DOMAIN)); ?>');
                    button.prop('disabled', false);
                }
            });
        });
    });
    </script>
    <?php
}

/**
 * Helper function to list archives
 */
function tgf_admin_list_archives($archive_dir, $archive_url) {
    $archives = [];
    if (!file_exists($archive_dir)) return $archives;

    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($archive_dir, RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::FOLLOW_SYMLINKS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'html' && strpos($file->getFilename(), 'archive-') === 0) {
                preg_match('/archive-([\d-]+)\.html/', $file->getFilename(), $matches);
                if (isset($matches[1])) {
                    $date = $matches[1];
                    $content = @file_get_contents($file->getPathname());
                    preg_match_all('/<article class="archive-post"/', $content, $post_matches);
                    $post_count = count($post_matches[0]);
                    
                    // Rebuild URL
                    $year = date('Y', strtotime($date));
                    $month = date('m', strtotime($date));
                    $url = "{$archive_url}/{$year}/{$month}/archive-{$date}.html";

                    $archives[] = [
                        'date' => $date,
                        'file' => $file->getPathname(),
                        'url' => $url,
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
    
    usort($archives, function($a, $b) {
        return strcmp($b['date'], $a['date']);
    });
    
    return $archives;
}

/**
 * Helper function to get archive statistics
 */
function tgf_admin_get_archive_stats($archives, $retention_days) {
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
        'retention_days' => $retention_days,
    ];
}

/**
 * Register settings - UPDATED WITH ARCHIVE SETTINGS
 */
add_action('admin_init', 'tgf_register_settings');

function tgf_register_settings() {
    // General settings
    register_setting('tgf_settings_group', 'tgf_sources', [
        'sanitize_callback' => 'sanitize_textarea_field',
        'default' => ''
    ]);
    
    register_setting('tgf_settings_group', 'tgf_max_posts', [
        'sanitize_callback' => function($value) {
            $value = absint($value);
            return max(10, min(500, $value)); // Clamp between 10 and 500
        },
        'default' => 100
    ]);
    
    register_setting('tgf_settings_group', 'tgf_bad_words', [
        'sanitize_callback' => 'sanitize_text_field',
        'default' => ''
    ]);
    
    register_setting('tgf_settings_group', 'tgf_page_link', [
        'sanitize_callback' => 'esc_url_raw',
        'default' => home_url('/thegeekline')
    ]);
    
    register_setting('tgf_settings_group', 'tgf_hashtags', [
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '#TheGeekline'
    ]);
    
    register_setting('tgf_settings_group', 'tgf_disqus_shortname', [
        'sanitize_callback' => 'sanitize_text_field',
        'default' => ''
    ]);

   // ARCHIVE SETTINGS - FIXED: These should be in tgf_advanced_settings group
    register_setting('tgf_advanced_settings', 'tgf_archive_retention_days', [
        'sanitize_callback' => function($value) {
            return max(30, min(365, absint($value)));
        },
        'default' => 90
    ]);
    
    register_setting('tgf_advanced_settings', 'tgf_archive_posts_per_page', [
        'sanitize_callback' => function($value) {
            return max(10, min(200, absint($value)));
        },
        'default' => 50
    ]);
 
    /**
     * Whitelist advanced settings for options.php submission
     */
    add_filter('whitelist_options', 'tgf_whitelist_advanced_settings');

    function tgf_whitelist_advanced_settings($whitelist_options) {
        // Add our advanced settings to the whitelist
        $whitelist_options['tgf_advanced_settings'] = array(
            'tgf_archive_retention_days',
            'tgf_archive_posts_per_page'
        );
        return $whitelist_options;
    }

    // Authentication settings (read-only in UI)
    register_setting('tgf_auth_settings', 'tgf_bsky_bearer_token', [
        'sanitize_callback' => 'sanitize_text_field',
        'default' => ''
    ]);
    
    register_setting('tgf_auth_settings', 'tgf_bsky_refresh_token', [
        'sanitize_callback' => 'sanitize_text_field',
        'default' => ''
    ]);
}

/**
 * AJAX handler for clearing cache
 */
add_action('wp_ajax_tgf_clear_cache', 'tgf_ajax_clear_cache');

function tgf_ajax_clear_cache() {
    check_ajax_referer('tgf_clear_cache', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Insufficient permissions', TGF_TEXT_DOMAIN));
    }
    
    delete_transient('tgf_posts_cache');
    delete_transient('tgf_bsky_access_token');
    
    // Clear object cache if available
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
    
    wp_send_json_success([
        'message' => __('Cache cleared successfully!', TGF_TEXT_DOMAIN)
    ]);
}

/**
 * AJAX handler for clearing tokens
 */
add_action('wp_ajax_tgf_clear_tokens', 'tgf_ajax_clear_tokens');

function tgf_ajax_clear_tokens() {
    check_ajax_referer('tgf_clear_tokens', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Insufficient permissions', TGF_TEXT_DOMAIN));
    }
    
    delete_option('tgf_bsky_bearer_token');
    delete_option('tgf_bsky_refresh_token');
    delete_transient('tgf_bsky_access_token');
    
    wp_send_json_success([
        'message' => __('Tokens cleared successfully!', TGF_TEXT_DOMAIN)
    ]);
}

/**
 * AJAX handler for resetting reposts
 */
add_action('wp_ajax_tgf_reset_reposts', 'tgf_ajax_reset_reposts');

function tgf_ajax_reset_reposts() {
    check_ajax_referer('tgf_reset_reposts', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Insufficient permissions', TGF_TEXT_DOMAIN));
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'geekline_reposts';
    
    $result = $wpdb->query("TRUNCATE TABLE $table");
    
    if ($result !== false) {
        // Clear cache as well
        delete_transient('tgf_posts_cache');
        
        wp_send_json_success([
            'message' => __('Repost counts reset successfully!', TGF_TEXT_DOMAIN)
        ]);
    } else {
        wp_send_json_error(__('Database error', TGF_TEXT_DOMAIN));
    }
}

/**
 * AJAX handler for pruning old database posts
 */
add_action('wp_ajax_tgf_prune_posts', 'tgf_ajax_prune_posts');

function tgf_ajax_prune_posts() {
    check_ajax_referer('tgf_prune_posts', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Insufficient permissions', TGF_TEXT_DOMAIN)]);
    }
    
    global $wpdb;
    $table_posts = $wpdb->prefix . 'geekline_posts';
    $retention_days = (int) get_option('tgf_archive_retention_days', 90);
    
    // Calculate cutoff date
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
    
    // Delete posts older than the cutoff date
    $result = $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM $table_posts WHERE post_date < %s",
            $cutoff_date
        )
    );
    
    if ($result === false) {
        wp_send_json_error(['message' => __('Database error during pruning.', TGF_TEXT_DOMAIN)]);
    }
    
    $message = sprintf(
        esc_html__('%d old posts pruned successfully.', TGF_TEXT_DOMAIN),
        $result
    );
    
    wp_send_json_success(['message' => $message]);
}

/**
 * AJAX handler for generating archives
 */
add_action('wp_ajax_tgf_generate_archive_now', 'tgf_ajax_generate_archive_now');

function tgf_ajax_generate_archive_now() {
    check_ajax_referer('tgf_archive_action', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Insufficient permissions', TGF_TEXT_DOMAIN)]);
    }
    
    // Check if archive manager class exists
    if (!class_exists('Geekline_Archive_Manager')) {
        wp_send_json_error(['message' => __('Archive manager class not available', TGF_TEXT_DOMAIN)]);
    }
    
    $archive_manager = new Geekline_Archive_Manager();
    $result = $archive_manager->generate_daily_archive();
    
    if ($result) {
        wp_send_json_success(['message' => __('Archive generated successfully', TGF_TEXT_DOMAIN)]);
    } else {
        wp_send_json_error(['message' => __('Failed to generate archive', TGF_TEXT_DOMAIN)]);
    }
}

/**
 * AJAX handler for deleting archives
 */
add_action('wp_ajax_tgf_delete_archive', 'tgf_ajax_delete_archive');

function tgf_ajax_delete_archive() {
    check_ajax_referer('tgf_archive_action', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Insufficient permissions', TGF_TEXT_DOMAIN)]);
    }
    
    $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
    
    if (empty($date)) {
        wp_send_json_error(['message' => __('No date specified', TGF_TEXT_DOMAIN)]);
    }
    
    $upload_dir = wp_upload_dir();
    $archive_dir = $upload_dir['basedir'] . '/geekline-archives';
    
    // Construct file path
    $year = date('Y', strtotime($date));
    $month = date('m', strtotime($date));
    $file_path = $archive_dir . '/' . $year . '/' . $month . '/archive-' . $date . '.html';
    
    if (file_exists($file_path) && unlink($file_path)) {
        wp_send_json_success(['message' => __('Archive deleted successfully', TGF_TEXT_DOMAIN)]);
    } else {
        wp_send_json_error(['message' => __('Failed to delete archive file', TGF_TEXT_DOMAIN)]);
    }
}
