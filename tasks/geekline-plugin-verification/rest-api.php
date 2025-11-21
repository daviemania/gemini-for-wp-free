<?php
/**
 * Geekline Feed REST API
 * 
 * Provides REST endpoints for the Geekline Feed with pagination,
 * sorting, and caching for optimal performance.
 *
 * @package Geekline
 * @since 1.0.0
 */

// Security check - prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register REST API routes
 */
add_action('rest_api_init', 'tgf_register_rest_routes');

function tgf_register_rest_routes() {
    // Main posts endpoint with comprehensive parameters
    register_rest_route('tgf/v1', '/posts', [
        'methods'             => 'GET',
        'callback'            => 'tgf_rest_posts',
        'permission_callback' => '__return_true', // Public endpoint
        'args'                => [
            'count' => [
                'default'           => 30,
                'sanitize_callback' => 'absint',
                'validate_callback' => function($param, $request, $key) {
                    $value = absint($param);
                    return $value > 0 && $value <= 100;
                },
                'description'       => esc_html__('Number of posts to return (1-100)', 'geekline-feed')
            ],
            'offset' => [
                'default'           => 0,
                'sanitize_callback' => 'absint',
                'validate_callback' => function($param, $request, $key) {
                    $value = absint($param);
                    return $value >= 0 && $value <= 1000; // Reasonable upper limit
                },
                'description'       => esc_html__('Number of posts to skip for pagination', 'geekline-feed')
            ],
            'sort' => [
                'default'           => 'latest',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($param, $request, $key) {
                    $allowed_sorts = ['latest', 'reposts', 'most_commented', 'feed'];
                    return in_array($param, $allowed_sorts, true);
                },
                'description'       => esc_html__('Sort order: latest, reposts, most_commented, feed', 'geekline-feed')
            ],
            'cache' => [
                'default'           => true,
                'sanitize_callback' => 'rest_sanitize_boolean',
                'description'       => esc_html__('Whether to use cached posts', 'geekline-feed')
            ]
        ]
    ]);
    
    // Additional endpoint for post statistics
    register_rest_route('tgf/v1', '/stats', [
        'methods'             => 'GET',
        'callback'            => 'tgf_rest_stats',
        'permission_callback' => '__return_true',
        'args'                => [
            'cache' => [
                'default'           => true,
                'sanitize_callback' => 'rest_sanitize_boolean',
                'description'       => esc_html__('Whether to use cached data', 'geekline-feed')
            ]
        ]
    ]);
}

/**
 * REST API callback to get and sort geekline posts with pagination
 * 
 * @param WP_REST_Request $request REST API request object
 * @return WP_REST_Response|WP_Error Response object
 */
function tgf_rest_posts(WP_REST_Request $request) {
    // Validate required functions exist
    if (!function_exists('tgf_fetch_posts')) {
        return new WP_Error(
            'missing_function',
            esc_html__('Geekline feed functionality is not available', 'geekline-feed'),
            ['status' => 503]
        );
    }
    
    $count = $request->get_param('count');
    $offset = $request->get_param('offset');
    $sort = $request->get_param('sort');
    $use_cache = $request->get_param('cache');
    
    // Get maximum posts from settings with validation
    $max_posts = max(10, min(500, (int)get_option('tgf_max_posts', 100)));
    
    // Generate cache key based on parameters
    $cache_key = 'tgf_rest_posts_' . md5(serialize([$max_posts, $sort, $use_cache]));
    $cache_ttl = $use_cache ? HOUR_IN_SECONDS : 0; // 1 hour if cache enabled
    
    // Try to get cached response
    $cached_response = $cache_ttl > 0 ? get_transient($cache_key) : false;
    
    if ($cached_response !== false && is_array($cached_response)) {
        $all_posts = $cached_response;
        error_log('Geekline REST: Serving cached posts');
    } else {
        // Fetch fresh posts
        $all_posts = tgf_fetch_posts($max_posts);
        
        if (is_wp_error($all_posts)) {
            return new WP_Error(
                'fetch_error',
                esc_html__('Failed to fetch posts from sources', 'geekline-feed'),
                ['status' => 500]
            );
        }
        
        // Validate posts structure
        if (!is_array($all_posts)) {
            error_log('Geekline REST: Invalid posts format received');
            $all_posts = [];
        }
        
        // Cache the results if caching is enabled
        if ($cache_ttl > 0 && !empty($all_posts)) {
            set_transient($cache_key, $all_posts, $cache_ttl);
            error_log(sprintf('Geekline REST: Cached %d posts for %d seconds', count($all_posts), $cache_ttl));
        }
    }
    
    if (empty($all_posts)) {
        return new WP_REST_Response([], 200, [
            'X-Total-Count' => 0,
            'X-Total-Pages' => 0,
            'X-Current-Page' => 1,
            'X-Has-More' => 'false'
        ]);
    }
    
    // Apply sorting based on the requested sort type
    $sorted_posts = tgf_apply_sorting($all_posts, $sort);
    
    if (is_wp_error($sorted_posts)) {
        return $sorted_posts;
    }
    
    // Get total count before pagination
    $total_count = count($sorted_posts);
    
    // Validate pagination parameters
    $valid_offset = min($offset, $total_count);
    $valid_count = min($count, $total_count - $valid_offset);
    
    // Apply pagination
    $paginated_posts = array_slice($sorted_posts, $valid_offset, $valid_count);
    
    // Calculate pagination metadata
    $current_page = $valid_offset > 0 ? floor($valid_offset / $count) + 1 : 1;
    $total_pages = $count > 0 ? ceil($total_count / $count) : 1;
    $has_more = ($valid_offset + $valid_count) < $total_count;
    
    // Create response with pagination headers
    $response = new WP_REST_Response($paginated_posts, 200);
    
    // Add pagination headers
    $response->header('X-Total-Count', $total_count);
    $response->header('X-Total-Pages', $total_pages);
    $response->header('X-Current-Page', $current_page);
    $response->header('X-Has-More', $has_more ? 'true' : 'false');
    $response->header('X-Items-Per-Page', $count);
    $response->header('X-Items-Returned', count($paginated_posts));
    
    // Add cache headers
    if ($cache_ttl > 0) {
        $response->header('Cache-Control', 'public, max-age=' . $cache_ttl);
        $response->header('Expires', gmdate('D, d M Y H:i:s', time() + $cache_ttl) . ' GMT');
    }
    
    error_log(sprintf(
        'Geekline REST: Returned %d posts (offset: %d, sort: %s, total: %d)',
        count($paginated_posts),
        $valid_offset,
        $sort,
        $total_count
    ));
    
    return $response;
}

/**
 * Apply sorting to posts based on sort type
 * 
 * @param array $posts Array of posts to sort
 * @param string $sort_type Sort type (latest, reposts, most_commented, feed)
 * @return array|WP_Error Sorted posts or error
 */
function tgf_apply_sorting($posts, $sort_type) {
    if (!is_array($posts)) {
        return new WP_Error(
            'invalid_posts',
            esc_html__('Invalid posts data provided for sorting', 'geekline-feed'),
            ['status' => 500]
        );
    }
    
    switch ($sort_type) {
        case 'reposts':
            $sorted_posts = tgf_sort_by_reposts($posts);
            break;
            
        case 'most_commented':
            $sorted_posts = tgf_sort_by_comments($posts);
            break;
            
        case 'latest':
        case 'feed':
        default:
            // Ensure posts are sorted by date (newest first)
            usort($posts, function($a, $b) {
                $time_a = strtotime($a['date'] ?? 0);
                $time_b = strtotime($b['date'] ?? 0);
                
                // Handle invalid dates by putting them at the end
                if ($time_a === false) $time_a = 0;
                if ($time_b === false) $time_b = 0;
                
                return $time_b - $time_a;
            });
            $sorted_posts = $posts;
            break;
    }
    
    return $sorted_posts;
}

/**
 * Sort posts by repost count (descending)
 * 
 * @param array $posts Array of posts
 * @return array Sorted posts with repost counts
 */
function tgf_sort_by_reposts($posts) {
    global $wpdb;
    
    if (empty($posts) || !is_array($posts)) {
        return $posts;
    }
    
    $table = $wpdb->prefix . 'geekline_reposts';
    
    // Verify table exists
    $table_exists = $wpdb->get_var($wpdb->prepare(
        "SHOW TABLES LIKE %s", 
        $table
    ));
    
    if (!$table_exists) {
        error_log('Geekline REST: Reposts table does not exist, falling back to date sorting');
        return tgf_apply_sorting($posts, 'latest');
    }
    
    // Extract all post URLs safely
    $post_urls = [];
    foreach ($posts as $post) {
        if (!empty($post['url']) && is_string($post['url'])) {
            $post_urls[] = esc_url_raw($post['url']);
        }
    }
    
    if (empty($post_urls)) {
        return $posts;
    }
    
    // Remove duplicates and limit to reasonable number
    $post_urls = array_slice(array_unique($post_urls), 0, 1000);
    
    // Fetch all repost counts in a single optimized query
    $placeholders = implode(', ', array_fill(0, count($post_urls), '%s'));
    
    $reposts_data = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT post_url, reposts_count FROM {$table} WHERE post_url IN ({$placeholders})",
            $post_urls
        ),
        ARRAY_A
    );
    
    if ($wpdb->last_error) {
        error_log('Geekline REST: Database error fetching reposts: ' . $wpdb->last_error);
        return tgf_apply_sorting($posts, 'latest');
    }
    
    // Create lookup array for O(1) access
    $reposts_lookup = [];
    foreach ($reposts_data as $row) {
        if (!empty($row['post_url']) && isset($row['reposts_count'])) {
            $reposts_lookup[sanitize_text_field($row['post_url'])] = (int)$row['reposts_count'];
        }
    }
    
    // Sort posts by repost count (descending), then by date
    usort($posts, function($a, $b) use ($reposts_lookup) {
        $count_a = $reposts_lookup[$a['url']] ?? 0;
        $count_b = $reposts_lookup[$b['url']] ?? 0;
        
        // Primary sort: repost count (descending)
        if ($count_a !== $count_b) {
            return $count_b - $count_a;
        }
        
        // Secondary sort: date (newest first)
        $time_a = strtotime($a['date'] ?? 0);
        $time_b = strtotime($b['date'] ?? 0);
        
        if ($time_a === false) $time_a = 0;
        if ($time_b === false) $time_b = 0;
        
        return $time_b - $time_a;
    });
    
    // Add repost count to each post for frontend display
    foreach ($posts as &$post) {
        $post['repost_count'] = $reposts_lookup[$post['url']] ?? 0;
    }
    unset($post); // Break reference
    
    error_log(sprintf('Geekline REST: Sorted %d posts by repost count', count($posts)));
    
    return $posts;
}

/**
 * Sort posts by comment count (placeholder implementation)
 * 
 * @param array $posts Array of posts
 * @return array Sorted posts
 */
function tgf_sort_by_comments($posts) {
    if (empty($posts) || !is_array($posts)) {
        return $posts;
    }
    
    // TODO: Implement Disqus API integration or WordPress comment counting
    // For now, use date sorting as fallback
    
    error_log('Geekline REST: Comment-based sorting not yet implemented, using date sorting');
    
    // Fallback to date sorting
    usort($posts, function($a, $b) {
        $time_a = strtotime($a['date'] ?? 0);
        $time_b = strtotime($b['date'] ?? 0);
        
        if ($time_a === false) $time_a = 0;
        if ($time_b === false) $time_b = 0;
        
        return $time_b - $time_a;
    });
    
    return $posts;
}

/**
 * REST API callback for feed statistics
 * 
 * @param WP_REST_Request $request REST API request object
 * @return WP_REST_Response Response object with statistics
 */
function tgf_rest_stats(WP_REST_Request $request) {
    $use_cache = $request->get_param('cache');
    $cache_key = 'tgf_rest_stats';
    $cache_ttl = $use_cache ? HOUR_IN_SECONDS : 0;
    
    // Try to get cached stats
    $cached_stats = $cache_ttl > 0 ? get_transient($cache_key) : false;
    
    if ($cached_stats !== false && is_array($cached_stats)) {
        $stats = $cached_stats;
        error_log('Geekline REST: Serving cached statistics');
    } else {
        // Generate fresh statistics
        $stats = tgf_generate_feed_statistics();
        
        // Cache the results if caching is enabled
        if ($cache_ttl > 0 && !empty($stats)) {
            set_transient($cache_key, $stats, $cache_ttl);
        }
    }
    
    $response = new WP_REST_Response($stats, 200);
    
    // Add cache headers
    if ($cache_ttl > 0) {
        $response->header('Cache-Control', 'public, max-age=' . $cache_ttl);
    }
    
    return $response;
}

/**
 * Generate comprehensive feed statistics
 * 
 * @return array Statistics data
 */
function tgf_generate_feed_statistics() {
    global $wpdb;
    
    $stats = [
        'total_posts' => 0,
        'sources_count' => 0,
        'last_updated' => null,
        'cache_status' => 'unknown',
        'reposts_total' => 0,
        'database_tables' => []
    ];
    
    // Get cached posts count
    $cached_posts = get_transient('tgf_posts_cache');
    if (is_array($cached_posts)) {
        $stats['total_posts'] = count($cached_posts);
        $stats['cache_status'] = 'valid';
    } else {
        $stats['cache_status'] = 'expired';
    }
    
    // Get sources count
    $sources_raw = get_option('tgf_sources', '');
    $sources = array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,/', $sources_raw)));
    $stats['sources_count'] = count($sources);
    
    // Get last updated time
    $stats['last_updated'] = get_option('tgf_feed_last_updated', current_time('mysql'));
    
    // Get reposts statistics
    $table_reposts = $wpdb->prefix . 'geekline_reposts';
    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_exists));
    
    if ($table_exists) {
        $reposts_total = $wpdb->get_var("SELECT SUM(reposts_count) FROM {$table_reposts}");
        $stats['reposts_total'] = $reposts_total ? (int)$reposts_total : 0;
        $stats['database_tables'][] = 'reposts';
    }
    
    // Check posts table
    $table_posts = $wpdb->prefix . 'geekline_posts';
    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_posts));
    
    if ($table_exists) {
        $posts_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_posts}");
        $stats['archived_posts'] = (int)$posts_count;
        $stats['database_tables'][] = 'posts';
    }
    
    // Add memory usage information
    $stats['memory_usage'] = [
        'current' => memory_get_usage(true),
        'peak' => memory_get_peak_usage(true)
    ];
    
    error_log('Geekline REST: Generated feed statistics');
    
    return $stats;
}

/**
 * Helper function to get repost count for a specific URL
 * 
 * @param string $url Post URL
 * @return int Repost count
 */
function tgf_get_repost_count($url) {
    global $wpdb;
    
    if (empty($url) || !is_string($url)) {
        return 0;
    }
    
    $table = $wpdb->prefix . 'geekline_reposts';
    $clean_url = esc_url_raw($url);
    
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT reposts_count FROM {$table} WHERE post_url = %s",
        $clean_url
    ));
    
    return $count !== null ? (int)$count : 0;
}

/**
 * Flush REST API cache programmatically
 * 
 * @param string $cache_type Type of cache to flush (posts, stats, all)
 * @return bool Success status
 */
function tgf_flush_rest_cache($cache_type = 'all') {
    $flushed = false;
    
    if ($cache_type === 'posts' || $cache_type === 'all') {
        // Delete all posts cache transients
        global $wpdb;
        $result = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_tgf_rest_posts_%'
            )
        );
        if ($result !== false) {
            $flushed = true;
            error_log('Geekline REST: Flushed posts cache');
        }
    }
    
    if ($cache_type === 'stats' || $cache_type === 'all') {
        $result = delete_transient('tgf_rest_stats');
        if ($result) {
            $flushed = true;
            error_log('Geekline REST: Flushed statistics cache');
        }
    }
    
    return $flushed;
}