<?php
/**
 * Geekline Feed Fetcher
 * 
 * Handles BlueSky API authentication, post fetching, sanitization, caching,
 * and database storage for archives.
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
 * Multibyte-safe helper function for checking if a string ends with a needle
 * 
 * @param string $haystack The string to search in
 * @param string $needle The string to search for
 * @return bool True if haystack ends with needle
 */
if (!function_exists('tgf_mb_ends_with')) {
    function tgf_mb_ends_with($haystack, $needle) {
        if (!is_string($haystack) || !is_string($needle)) {
            return false;
        }
        
        $encoding = 'UTF-8';
        $length = mb_strlen($needle, $encoding);
        if (!$length) {
            return true;
        }
        
        // Check if the end of the haystack matches the needle
        return mb_substr($haystack, -$length, null, $encoding) === $needle;
    }
}

/**
 * Create or update plugin database tables on activation.
 */
function tgf_create_database_tables() {
    global $wpdb;
    
    // Check if we're in admin and have the required file
    if (!is_admin()) {
        return;
    }
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Ensure upgrade.php is available
    if (!function_exists('dbDelta')) {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    }

    // 1. Reposts Table (for share counts)
    $table_reposts = $wpdb->prefix . 'geekline_reposts';
    $sql_reposts = "CREATE TABLE $table_reposts (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        post_url VARCHAR(500) NOT NULL,
        reposts_count INT DEFAULT 0,
        last_reposted DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY post_url (post_url(191)),
        KEY idx_reposts_count (reposts_count),
        KEY idx_last_reposted (last_reposted)
    ) $charset_collate;";
    
    // Use dbDelta which handles table creation safely
    dbDelta($sql_reposts);
    
    // 2. Posts Table (for archive content)
    $table_posts = $wpdb->prefix . 'geekline_posts';
    $sql_posts = "CREATE TABLE $table_posts (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        post_uri VARCHAR(500) NOT NULL,
        post_url VARCHAR(500) NOT NULL,
        author VARCHAR(255) NOT NULL,
        author_handle VARCHAR(255) NOT NULL,
        content_raw TEXT NOT NULL,
        content_html LONGTEXT NOT NULL,
        post_date DATETIME NOT NULL,
        source VARCHAR(50) NOT NULL,
        media_url VARCHAR(500) DEFAULT '',
        has_media TINYINT(1) DEFAULT 0,
        link_card_data LONGTEXT DEFAULT NULL,
        embed_data LONGTEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY post_uri (post_uri(191)),
        KEY idx_post_date (post_date),
        KEY idx_author_handle (author_handle(191))
    ) $charset_collate;";
    
    dbDelta($sql_posts);
    
    // Check for errors
    if (!empty($wpdb->last_error)) {
        error_log('Geekline Database Error: ' . $wpdb->last_error);
        return false;
    }
    
    error_log('Geekline: Database tables created/verified successfully');
    return true;
}

/**
 * Check and enforce API rate limit.
 *
 * @param string $api_type Type of API being called (e.g., 'bluesky_feed', 'generic_json').
 * @param int $limit_per_hour Maximum calls allowed per hour for this API type.
 * @return bool True if call is allowed, false if rate limit exceeded.
 */
function tgf_check_api_rate_limit($api_type, $limit_per_hour) {
    $transient_key = 'tgf_api_calls_' . sanitize_key($api_type) . '_' . date('YmdH'); // Hourly key
    $current_calls = (int) get_transient($transient_key);

    if ($current_calls >= $limit_per_hour) {
        error_log(sprintf('Geekline: API rate limit exceeded for %s. Current calls: %d, Limit: %d', $api_type, $current_calls, $limit_per_hour));
        return false;
    }

    set_transient($transient_key, $current_calls + 1, HOUR_IN_SECONDS);
    return true;
}

/**
 * Refresh the BlueSky access token using a refresh token
 * 
 * @param string $refresh_token The refresh token
 * @return array|WP_Error Token data on success, WP_Error on failure
 */
function tgf_bsky_refresh_token($refresh_token) {
    if (empty($refresh_token)) {
        return new WP_Error('invalid_refresh_token', esc_html__('Refresh token is empty', TGF_TEXT_DOMAIN));
    }
    
    $api_url = apply_filters('tgf_bluesky_refresh_session_url', 'https://bsky.social/xrpc/com.atproto.server.refreshSession');
    
    $response = wp_remote_post(esc_url_raw($api_url), [
        'timeout' => 15,
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer " . sanitize_text_field($refresh_token)
        ],
        'sslverify' => true
    ]);

    if (is_wp_error($response)) {
        error_log("Geekline BlueSky Token Refresh Error: " . $response->get_error_message());
        return $response;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if ($response_code !== 200 || !isset($data['accessJwt'])) {
        $error_message = $data['message'] ?? esc_html__("Unknown API Error", TGF_TEXT_DOMAIN);
        error_log("Geekline BlueSky Token Refresh Failed (HTTP {$response_code}): " . $error_message);
        return new WP_Error('bsky_refresh_failed', sprintf(esc_html__("BlueSky Token Refresh Failed: %s", TGF_TEXT_DOMAIN), $error_message));
    }

    return [
        'accessJwt' => sanitize_text_field($data['accessJwt']),
        'refreshJwt' => isset($data['refreshJwt']) ? sanitize_text_field($data['refreshJwt']) : $refresh_token
    ];
}

/**
 * Get BlueSky access token, refresh if expired
 * 
 * @return string|false Access token on success, false on failure
 */
function tgf_get_bsky_access_token() {
    $token_cache_key = 'tgf_bsky_access_token';
    $cached_token = get_transient($token_cache_key);

    if ($cached_token !== false) {
        return $cached_token;
    }

    $refresh_token = get_option('tgf_bsky_refresh_token', '');
    
    if (!empty($refresh_token)) {
        error_log('Geekline: Access token missing/expired, attempting refresh...');
        $tokens = tgf_bsky_refresh_token($refresh_token);
        
        if (is_wp_error($tokens)) {
            error_log('Geekline: Failed to refresh token, cannot fetch BlueSky posts.');
            return false;
        }

        update_option('tgf_bsky_bearer_token', $tokens['accessJwt']);
        update_option('tgf_bsky_refresh_token', $tokens['refreshJwt']);
        set_transient($token_cache_key, $tokens['accessJwt'], HOUR_IN_SECONDS);

        return $tokens['accessJwt'];
    }

    error_log('Geekline: No BlueSky tokens configured.');
    return false;
}

/**
 * Fetch posts from sources (BlueSky + generic JSON), filter, sanitize, cache
 * 
 * @param int $count Number of posts to fetch
 * @return array Array of processed posts
 */
function tgf_fetch_posts($count = 100) {
    $cache_key = 'tgf_posts_cache';
    $cached = get_transient($cache_key);
    $max_posts_setting = max(10, min(500, (int)get_option('tgf_max_posts', 100)));
    $fetch_limit = min($count, $max_posts_setting);
    $cache_time = HOUR_IN_SECONDS;

    // Return cached posts if available and valid
    if ($cached !== false && is_array($cached) && !empty($cached)) {
        return $cached;
    }

    $sources_raw = get_option('tgf_sources', '');
    $sources = array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,/', $sources_raw)));
    if (empty($sources)) {
        error_log('Geekline: No feed sources configured.');
        return [];
    }

    $posts = [];
    $bsky_access_token = tgf_get_bsky_access_token();

    foreach ($sources as $source) {
        if (empty($source)) {
            continue;
        }
        
        if (strpos($source, 'at://') === 0) {
            if ($bsky_access_token === false) {
                error_log("Geekline: Skipping BlueSky source '{$source}' due to missing token.");
                continue;
            }
            $source_posts = tgf_fetch_bluesky_posts($source, $bsky_access_token, $fetch_limit);
            $posts = array_merge($posts, $source_posts);
        } else {
            $source_posts = tgf_fetch_generic_posts($source);
            $posts = array_merge($posts, $source_posts);
        }
    }

    // Filter bad words
    $posts = tgf_filter_bad_words($posts);

    // Remove duplicates
    $posts = tgf_remove_duplicate_posts($posts);

    // Sanitize HTML content for all posts
    foreach ($posts as &$post) {
        if (!empty($post['content_html'])) {
            $post['content_html'] = wp_kses(
                $post['content_html'],
                [
                    'a' => ['href' => true, 'target' => true, 'rel' => true],
                    'strong' => [], 'em' => [], 'br' => [], 'p' => [], 'span' => ['class' => true],
                    'code' => [], 'pre' => []
                ]
            );
        }
    }
    unset($post); // Break reference

    // Sort newest first
    usort($posts, function($a, $b) {
        $time_a = strtotime($a['date'] ?? 0);
        $time_b = strtotime($b['date'] ?? 0);
        return $time_b - $time_a;
    });

    $posts = array_slice($posts, 0, $max_posts_setting);

    // Save posts to database for archives
    tgf_save_posts_to_db($posts);

    set_transient($cache_key, $posts, $cache_time);

    error_log(sprintf('Geekline: Fetched and cached %d posts', count($posts)));
    return $posts;
}

/**
 * Save fetched posts to the database for archival
 * 
 * @param array $posts Posts from tgf_fetch_posts()
 * @return int Number of posts saved
 */
function tgf_save_posts_to_db($posts) {
    if (empty($posts) || !is_array($posts)) {
        return 0;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'geekline_posts';
    
    $saved_count = 0;
    $errors = 0;
    
    foreach ($posts as $post) {
        // Use post URI for BlueSky, post URL for others as unique identifier
        $post_uri = $post['uri'] ?? $post['url'] ?? '';
        if (empty($post_uri)) {
            $errors++;
            continue;
        }

        // Prepare data for insertion/update
        $data = [
            'post_uri' => sanitize_text_field($post_uri),
            'post_url' => esc_url_raw($post['url'] ?? '#'),
            'author' => sanitize_text_field($post['author'] ?? esc_html__('Unknown', TGF_TEXT_DOMAIN)),
            'author_handle' => sanitize_text_field($post['author_handle'] ?? ''),
            'content_raw' => wp_kses_post($post['content'] ?? ''),
            'content_html' => wp_kses_post($post['content_html'] ?? ''),
            'post_date' => sanitize_text_field($post['date'] ?? current_time('mysql')),
            'source' => sanitize_text_field($post['source'] ?? esc_html__('Unknown', TGF_TEXT_DOMAIN)),
            'media_url' => esc_url_raw($post['media_url'] ?? ''),
            'has_media' => !empty($post['has_media']) ? 1 : 0,
            'link_card_data' => !empty($post['link_card']) ? serialize($post['link_card']) : null,
            'embed_data' => !empty($post['embed']) ? serialize($post['embed']) : null,
        ];

        $formats = [
            '%s', // post_uri
            '%s', // post_url
            '%s', // author
            '%s', // author_handle
            '%s', // content_raw
            '%s', // content_html
            '%s', // post_date
            '%s', // source
            '%s', // media_url
            '%d', // has_media
            '%s', // link_card_data
            '%s', // embed_data
        ];
        
        // Use INSERT ... ON DUPLICATE KEY UPDATE
        $sql = $wpdb->prepare(
            "INSERT INTO $table_name (post_uri, post_url, author, author_handle, content_raw, content_html, post_date, source, media_url, has_media, link_card_data, embed_data) 
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %d, %s, %s) 
            ON DUPLICATE KEY UPDATE 
                post_url = VALUES(post_url),
                author = VALUES(author),
                author_handle = VALUES(author_handle),
                content_raw = VALUES(content_raw),
                content_html = VALUES(content_html),
                post_date = VALUES(post_date),
                source = VALUES(source),
                media_url = VALUES(media_url),
                has_media = VALUES(has_media),
                link_card_data = VALUES(link_card_data),
                embed_data = VALUES(embed_data)",
            $data['post_uri'], $data['post_url'], $data['author'], $data['author_handle'], 
            $data['content_raw'], $data['content_html'], $data['post_date'], $data['source'],
            $data['media_url'], $data['has_media'], $data['link_card_data'], $data['embed_data']
        );
        
        $result = $wpdb->query($sql);
        
        if ($result !== false) {
            $saved_count++;
        } else {
            $errors++;
            error_log("Geekline: Failed to save post with URI: " . $data['post_uri']);
        }
    }
    
    if ($saved_count > 0) {
        error_log(sprintf('Geekline: Saved/Updated %d posts to the database archive.', $saved_count));
    }
    
    if ($errors > 0) {
        error_log(sprintf('Geekline: Failed to save %d posts to database.', $errors));
    }
    
    return $saved_count;
}

/**
 * Get posts for a specific date from the database for the archive manager
 * 
 * @param string $date Date in 'Y-m-d' format
 * @return array Array of posts
 */
function tgf_get_posts_for_date($date) {
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        error_log("Geekline: Invalid date format for archive retrieval: {$date}");
        return [];
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'geekline_posts';
    
    // Query for all posts on that specific date, oldest first for the archive page
    $sql = $wpdb->prepare(
        "SELECT * FROM $table_name WHERE DATE(post_date) = %s ORDER BY post_date ASC",
        $date
    );
    
    $results = $wpdb->get_results($sql, ARRAY_A);
    
    if (empty($results)) {
        return [];
    }
    
    // Re-hydrate the data to match the format expected by the feed
    $posts = [];
    foreach ($results as $row) {
        $posts[] = [
            'url' => $row['post_url'],
            'uri' => $row['post_uri'],
            'content' => $row['content_raw'],
            'content_html' => $row['content_html'],
            'author' => $row['author'],
            'author_handle' => $row['author_handle'],
            'date' => $row['post_date'],
            'source' => $row['source'],
            'has_media' => (bool)$row['has_media'],
            'media_url' => $row['media_url'],
            'embed' => !empty($row['embed_data']) ? maybe_unserialize($row['embed_data']) : null,
            'link_card' => !empty($row['link_card_data']) ? maybe_unserialize($row['link_card_data']) : null,
        ];
    }
    
    error_log(sprintf('Geekline: Retrieved %d posts from database for date %s', count($posts), $date));
    return $posts;
}

/**
 * Fetch BlueSky posts
 * 
 * @param string $source BlueSky feed URI
 * @param string $access_token Access token for authentication
 * @param int $limit Number of posts to fetch
 * @return array Array of parsed posts
 */
function tgf_fetch_bluesky_posts($source, $access_token, $limit = 100) {
    if (empty($source) || empty($access_token)) {
        return [];
    }

    $api_type = 'bluesky_feed';
    $limit_per_hour = 120; // Example: 120 calls per hour for BlueSky feeds

    if (!tgf_check_api_rate_limit($api_type, $limit_per_hour)) {
        return [];
    }
    
    $api_limit = min($limit, 100);
    $encoded = urlencode($source);
    $api_url = apply_filters('tgf_bluesky_feed_url', "https://bsky.social/xrpc/app.bsky.feed.getFeed?feed={$encoded}&limit={$api_limit}", $source, $api_limit);
    
    $response = wp_remote_get(esc_url_raw($api_url), [
        'timeout' => 15,
        'headers' => [
            'Accept' => 'application/json',
            'Authorization' => "Bearer " . sanitize_text_field($access_token)
        ],
        'sslverify' => true
    ]);

    $code = wp_remote_retrieve_response_code($response);
    if (is_wp_error($response) || $code !== 200) {
        if (is_wp_error($response)) {
            error_log("Geekline BlueSky Fetch Error: " . $response->get_error_message());
        } else {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            $error_msg = $data['message'] ?? wp_remote_retrieve_response_message($response);
            error_log("Geekline BlueSky Fetch Failed (HTTP {$code}): " . $error_msg);
        }
        return [];
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (!isset($data['feed']) || !is_array($data['feed'])) {
        error_log("Geekline: Invalid BlueSky API response structure");
        return [];
    }

    $posts = [];
    foreach ($data['feed'] as $item) {
        $post = tgf_parse_bluesky_post($item);
        if ($post) {
            $posts[] = $post;
        }
    }
    
    error_log(sprintf('Geekline: Fetched %d posts from BlueSky source: %s', count($posts), $source));
    return $posts;
}

/**
 * Parse a single BlueSky post
 * 
 * @param array $item Raw post data from BlueSky API
 * @return array|null Parsed post data or null if invalid
 */
function tgf_parse_bluesky_post($item) {
    if (!is_array($item) || empty($item['post'])) {
        return null;
    }
    
    $content = $item['post']['record']['text'] ?? $item['post']['text'] ?? $item['record']['text'] ?? '';
    
    $author_handle = $item['post']['author']['handle'] ?? $item['author']['handle'] ?? 'unknown';
    $author_display = $item['post']['author']['displayName'] ?? $item['post']['author']['display_name'] ?? $author_handle;

    $post_uri = $item['post']['uri'] ?? '#';
    $post_url = tgf_convert_at_uri_to_url($post_uri, $author_handle);

    $created_at = $item['post']['record']['createdAt'] ?? $item['post']['createdAt'] ?? $item['post']['indexedAt'] ?? current_time('mysql');

    $embed = $item['post']['embed'] ?? null;
    $has_media = false;
    $media_url = '';
    $link_card_data = null;

    if ($embed && is_array($embed)) {
        if (!empty($embed['images'][0])) {
            $has_media = true;
            $media_url = $embed['images'][0]['thumb'] ?? $embed['images'][0]['fullsize'] ?? '';
            $media_url = esc_url_raw($media_url);
        } elseif (!empty($embed['external']['thumb'])) {
            $has_media = true;
            $media_url = esc_url_raw($embed['external']['thumb']);
            $link_card_data = [
                'uri' => esc_url_raw($embed['external']['uri'] ?? ''),
                'title' => sanitize_text_field($embed['external']['title'] ?? ''),
                'description' => wp_kses_post($embed['external']['description'] ?? ''),
                'thumb' => $media_url,
            ];
        }
    }
    
    // If content was empty but we have media, set content to a placeholder
    if (empty($content) && $has_media) {
        $content = esc_html__('[Image]', TGF_TEXT_DOMAIN);
    } elseif (empty($content) && $link_card_data) {
        $content = esc_html__('[Link]', TGF_TEXT_DOMAIN);
    } elseif (empty($content)) {
        // If still no content and no media, it's likely a reply/quote we can't parse
        return null;
    }

    // External Embed Content Cleanup
    if ($embed && ($embed['$type'] ?? null) === 'app.bsky.embed.external#view' && !empty($embed['external']['uri'])) {
        $external_uri = $embed['external']['uri'];
        $external_title = $embed['external']['title'] ?? '';
        $external_description = $embed['external']['description'] ?? '';
        
        $content = str_replace($external_uri, '', $content);
        $trimmed_content = trim($content);
        
        if (!empty($external_description) && tgf_mb_ends_with($trimmed_content, $external_description)) {
            $desc_len = mb_strlen($external_description, 'UTF-8');
            $content_len = mb_strlen($trimmed_content, 'UTF-8');
            $trimmed_content = mb_substr($trimmed_content, 0, $content_len - $desc_len, 'UTF-8');
            $trimmed_content = trim($trimmed_content);
        }

        if (!empty($external_title) && tgf_mb_ends_with($trimmed_content, $external_title)) {
            $title_len = mb_strlen($external_title, 'UTF-8');
            $content_len = mb_strlen($trimmed_content, 'UTF-8');
            $trimmed_content = mb_substr($trimmed_content, 0, $content_len - $title_len, 'UTF-8');
            $trimmed_content = trim($trimmed_content);
        }
        
        $content = $trimmed_content;

        $content = trim(
            preg_replace(
                '/(\s*|LINK\s*)$/i',
                '',
                $content
            )
        );
    }

    // Process the cleaned raw content into HTML
    $content_html = tgf_process_content_links($content, $item['post']['record']['facets'] ?? []);
    
    return [
        'url' => $post_url,
        'uri' => $post_uri,
        'content' => $content,
        'content_html' => $content_html,
        'author' => $author_display,
        'author_handle' => $author_handle,
        'date' => $created_at,
        'source' => 'BlueSky',
        'has_media' => $has_media,
        'media_url' => $media_url,
        'embed' => $embed,
        'link_card' => $link_card_data
    ];
}

/**
 * Fetch generic JSON posts
 * 
 * @param string $source JSON feed URL
 * @return array Array of processed posts
 */
function tgf_fetch_generic_posts($source) {
    if (empty($source) || !filter_var($source, FILTER_VALIDATE_URL)) {
        error_log("Geekline: Invalid JSON source URL: {$source}");
        return [];
    }

    $api_type = 'generic_json';
    $limit_per_hour = 60; // Example: 60 calls per hour for generic JSON feeds

    if (!tgf_check_api_rate_limit($api_type, $limit_per_hour)) {
        return [];
    }
    
    $response = wp_remote_get(esc_url_raw($source), [
        'timeout' => 15, 
        'sslverify' => true
    ]);
    
    if (is_wp_error($response)) {
        error_log("Geekline JSON Fetch Error: " . $response->get_error_message());
        return [];
    }
    
    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) {
        error_log("Geekline JSON Fetch Failed (HTTP {$code}) for source: {$source}");
        return [];
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (!is_array($data)) {
        error_log("Geekline: Invalid JSON response from source: {$source}");
        return [];
    }

    $processed_data = [];
    foreach ($data as $post) {
        if (!is_array($post)) {
            continue;
        }
        
        if (!empty($post['content']) && empty($post['content_html'])) {
            $post['content_html'] = wp_kses_post($post['content']);
        }
        
        // Ensure all keys exist for database consistency
        $processed_post = [
            'url' => esc_url_raw($post['url'] ?? '#'),
            'uri' => esc_url_raw($post['url'] ?? '#'), // Use URL as URI for JSON sources
            'content' => wp_kses_post($post['content'] ?? ''),
            'content_html' => wp_kses_post($post['content_html'] ?? ''),
            'author' => sanitize_text_field($post['author'] ?? esc_html__('Unknown', TGF_TEXT_DOMAIN)),
            'author_handle' => sanitize_text_field($post['author_handle'] ?? ''),
            'date' => sanitize_text_field($post['date'] ?? current_time('mysql')),
            'source' => sanitize_text_field($post['source'] ?? 'JSON'),
            'has_media' => !empty($post['has_media']),
            'media_url' => esc_url_raw($post['media_url'] ?? ''),
            'embed' => $post['embed'] ?? null,
            'link_card' => $post['link_card'] ?? null
        ];
        $processed_data[] = $processed_post;
    }

    error_log(sprintf('Geekline: Fetched %d posts from JSON source: %s', count($processed_data), $source));
    return $processed_data;
}

/**
 * Filter bad words from posts
 * 
 * @param array $posts Array of posts to filter
 * @return array Filtered posts
 */
function tgf_filter_bad_words($posts) {
    $bad_words_raw = get_option('tgf_bad_words', '');
    $bad_words = array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,/', $bad_words_raw)));
    $bad_words_lc = array_map('strtolower', $bad_words);

    if (empty($bad_words_lc)) {
        return $posts;
    }

    $filtered_posts = array_filter($posts, function($post) use ($bad_words_lc) {
        $content = strtolower($post['content'] ?? '');
        if (empty($content)) {
            return true;
        }

        foreach ($bad_words_lc as $word) {
            if (strpos($content, $word) !== false) {
                return false;
            }
        }
        return true;
    });

    $removed_count = count($posts) - count($filtered_posts);
    if ($removed_count > 0) {
        error_log(sprintf('Geekline: Filtered out %d posts containing bad words', $removed_count));
    }
    
    return array_values($filtered_posts);
}

/**
 * Remove duplicate posts based on URL or URI
 * 
 * @param array $posts Array of posts to deduplicate
 * @return array Unique posts
 */
function tgf_remove_duplicate_posts($posts) {
    $unique = [];
    $seen_keys = [];
    
    foreach ($posts as $post) {
        // Prioritize URI, fall back to URL
        $key = $post['uri'] ?? $post['url'] ?? '';
        if (empty($key) || isset($seen_keys[$key])) {
            continue;
        }
        
        $seen_keys[$key] = true;
        $unique[] = $post;
    }
    
    $removed_count = count($posts) - count($unique);
    if ($removed_count > 0) {
        error_log(sprintf('Geekline: Removed %d duplicate posts', $removed_count));
    }
    
    return $unique;
}

/**
 * Convert BlueSky AT URI to web URL
 * 
 * @param string $uri BlueSky AT URI
 * @param string $handle Author handle
 * @return string Web URL
 */
function tgf_convert_at_uri_to_url($uri, $handle) {
    if (strpos($uri, 'at://') !== 0) {
        return esc_url_raw($uri);
    }
    
    $parts = explode('/', $uri);
    $post_id = end($parts);
    $clean_handle = sanitize_text_field($handle);
    $clean_post_id = sanitize_text_field($post_id);
    
    return apply_filters('tgf_bluesky_profile_url', "https://bsky.app/profile/" . $clean_handle . "/post/" . $clean_post_id, $clean_handle, $clean_post_id);
}

/**
 * Process content to convert facets (links, mentions, hashtags) to HTML
 * 
 * This function is critical for security and correct rendering. It ensures:
 * 1. All non-link text is escaped (XSS prevention).
 * 2. Links are correctly rendered using byte offsets from the raw content.
 *
 * @param string $content Raw text content
 * @param array $facets Array of facets from BlueSky API
 * @return string HTML content with links, ready for final nl2br/kses
 */
function tgf_process_content_links($content, $facets = []) {
    if (empty($content)) {
        return '';
    }
    
    if (empty($facets) || !is_array($facets)) {
        // If no facets, escape the whole text and convert newlines.
        return nl2br(esc_html($content));
    }

    // Sort facets by byte position in ascending order to process sequentially
    usort($facets, function($a, $b) {
        return ($a['index']['byteStart'] ?? 0) - ($b['index']['byteStart'] ?? 0);
    });

    $final_html = '';
    $current_byte_position = 0;
    $content_length = strlen($content);

    foreach ($facets as $facet) {
        $byte_start = $facet['index']['byteStart'] ?? null;
        $byte_end = $facet['index']['byteEnd'] ?? null;
        
        if ($byte_start === null || $byte_end === null || $byte_start >= $byte_end || $byte_start >= $content_length) {
            continue;
        }
        
        // Process the raw text BEFORE the facet (non-link content)
        if ($byte_start > $current_byte_position) {
            $raw_segment = substr($content, $current_byte_position, $byte_start - $current_byte_position);
            // SECURITY: ESCAPE the raw text segment
            $final_html .= esc_html($raw_segment);
        }

        // Process the FACET (link content)
        $length = min($byte_end - $byte_start, $content_length - $byte_start);
        $link_text_raw = substr($content, $byte_start, $length);
        
        if (empty($link_text_raw)) {
            $current_byte_position = $byte_end;
            continue;
        }

        $link_html = null;
        if (isset($facet['features']) && is_array($facet['features'])) {
            foreach ($facet['features'] as $feature) {
                $type = $feature['$type'] ?? '';
                
                // Generate the link HTML, ensuring all components are safely escaped/validated
                if ($type === 'app.bsky.richtext.facet#link') {
                    $url = $feature['uri'] ?? '';
                    if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
                        $link_html = '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($link_text_raw) . '</a>';
                    }
                } elseif ($type === 'app.bsky.richtext.facet#mention') {
                    $handle = ltrim($link_text_raw, '@');
                    $clean_handle = sanitize_text_field($handle);
                    $link_html = '<a href="https://bsky.app/profile/' . esc_attr($clean_handle) . '" target="_blank" rel="noopener noreferrer">' . esc_html($link_text_raw) . '</a>';
                } elseif ($type === 'app.bsky.richtext.facet#tag') {
                    $tag = ltrim($link_text_raw, '#');
                    $clean_tag = sanitize_text_field($tag);
                    $link_html = '<a href="https://bsky.app/search?q=%23' . urlencode($clean_tag) . '" target="_blank" rel="noopener noreferrer">' . esc_html($link_text_raw) . '</a>';
                }
                
                if ($link_html) {
                    break;
                }
            }
        }
        
        // Append the safely generated link HTML, or just the escaped raw text if no link was generated
        $final_html .= $link_html ?? esc_html($link_text_raw);

        // Update position
        $current_byte_position = $byte_end;
    }

    // Process the raw text AFTER the last facet
    if ($current_byte_position < $content_length) {
        $raw_segment = substr($content, $current_byte_position);
        // SECURITY: ESCAPE the final raw text segment
        $final_html .= esc_html($raw_segment);
    }
    
    // Convert newlines in the fully built, safely escaped HTML string
    return nl2br($final_html);
}