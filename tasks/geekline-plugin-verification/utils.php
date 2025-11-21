<?php
/**
 * Geekline Feed Utility Functions
 * 
 * Provides security, sanitization, and helper functions for the Geekline Feed plugin.
 * 
 * @package Geekline
 * @since 1.0.0
 */

// Security check - prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sanitizes a string for safe HTML display by encoding special characters
 * 
 * Used client-side in the JS file for author/source fields and throughout the plugin
 * for consistent HTML sanitization.
 * 
 * @since 1.0.0
 * 
 * @param string $str The input string to sanitize
 * @param string $encoding The character encoding (default: UTF-8)
 * @return string The sanitized string safe for HTML output
 */
function tgf_sanitize_html($str, $encoding = 'UTF-8') {
    // Handle null, boolean, or non-string values
    if (!is_string($str)) {
        if (is_numeric($str)) {
            $str = (string) $str;
        } elseif (is_bool($str)) {
            $str = $str ? 'true' : 'false';
        } else {
            return '';
        }
    }
    
    // Remove null bytes and other control characters except newlines and tabs
    $str = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $str);
    
    // Convert to UTF-8 if not already
    if (function_exists('mb_convert_encoding') && $encoding !== 'UTF-8') {
        $str = mb_convert_encoding($str, 'UTF-8', $encoding);
    }
    
    // Sanitize using htmlspecialchars with proper flags
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

/**
 * Sanitizes text content for use in HTML attributes
 * 
 * @since 1.0.0
 * 
 * @param string $str The input string to sanitize
 * @return string The sanitized string safe for HTML attributes
 */
function tgf_sanitize_attribute($str) {
    if (!is_string($str)) {
        return '';
    }
    
    $str = tgf_sanitize_html($str);
    
    // Additional safety for attributes
    $str = str_replace(
        ['"', "'", '`', '=', '<', '>', '&', '{', '}', '[', ']'],
        ['&quot;', '&#x27;', '&#x60;', '&#x3D;', '&lt;', '&gt;', '&amp;', '&#x7B;', '&#x7D;', '&#x5B;', '&#x5D;'],
        $str
    );
    
    return $str;
}

/**
 * Sanitizes URL for safe usage in links and redirects
 * 
 * @since 1.0.0
 * 
 * @param string $url The URL to sanitize
 * @param array $allowed_protocols Array of allowed protocols
 * @return string The sanitized URL or empty string if invalid
 */
function tgf_sanitize_url($url, $allowed_protocols = ['http', 'https', 'ftp', 'ftps', 'mailto', 'news', 'irc', 'gopher', 'nntp', 'feed', 'telnet', 'mms', 'rtsp', 'sms', 'tel']) {
    if (!is_string($url) || empty(trim($url))) {
        return '';
    }
    
    // Remove control characters and trim
    $url = preg_replace('/[\x00-\x1F\x7F]/', '', trim($url));
    
    // Check if it's a valid URL
    $sanitized_url = esc_url_raw($url, $allowed_protocols);
    
    // Additional validation for BlueSky URLs
    if (strpos($sanitized_url, 'bsky.app') !== false || strpos($sanitized_url, 'at://') === 0) {
        if (!filter_var($sanitized_url, FILTER_VALIDATE_URL) && strpos($sanitized_url, 'at://') !== 0) {
            return '';
        }
    }
    
    return $sanitized_url;
}

/**
 * Validates and sanitizes a date string
 * 
 * @since 1.0.0
 * 
 * @param string $date The date string to validate
 * @param string $format The expected date format (default: Y-m-d H:i:s)
 * @return string|false The sanitized date string or false if invalid
 */
function tgf_sanitize_date($date, $format = 'Y-m-d H:i:s') {
    if (!is_string($date) || empty(trim($date))) {
        return false;
    }
    
    $date = trim($date);
    
    // Create DateTime object
    $datetime = DateTime::createFromFormat($format, $date);
    
    if ($datetime && $datetime->format($format) === $date) {
        return $date;
    }
    
    // Try alternative parsing
    $timestamp = strtotime($date);
    if ($timestamp !== false) {
        return date($format, $timestamp);
    }
    
    return false;
}

/**
 * Sanitizes textarea content with multiple lines
 * 
 * @since 1.0.0
 * 
 * @param string $text The textarea content to sanitize
 * @param bool $preserve_line_breaks Whether to preserve line breaks
 * @return string The sanitized textarea content
 */
function tgf_sanitize_textarea($text, $preserve_line_breaks = true) {
    if (!is_string($text)) {
        return '';
    }
    
    // Remove control characters except newlines and tabs if preserving line breaks
    if ($preserve_line_breaks) {
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
    } else {
        $text = preg_replace('/[\x00-\x1F\x7F]/', '', $text);
    }
    
    // Sanitize each line individually
    $lines = explode("\n", $text);
    $sanitized_lines = array_map('tgf_sanitize_html', $lines);
    
    return implode("\n", $sanitized_lines);
}

/**
 * Sanitizes an array of strings for safe usage
 * 
 * @since 1.0.0
 * 
 * @param array $array The array to sanitize
 * @param callable $sanitize_callback The sanitization callback function
 * @return array The sanitized array
 */
function tgf_sanitize_array($array, $sanitize_callback = 'tgf_sanitize_html') {
    if (!is_array($array)) {
        return [];
    }
    
    if (!is_callable($sanitize_callback)) {
        $sanitize_callback = 'tgf_sanitize_html';
    }
    
    $sanitized = [];
    foreach ($array as $key => $value) {
        $sanitized_key = tgf_sanitize_html($key);
        
        if (is_array($value)) {
            $sanitized_value = tgf_sanitize_array($value, $sanitize_callback);
        } elseif (is_string($value)) {
            $sanitized_value = call_user_func($sanitize_callback, $value);
        } else {
            $sanitized_value = $value;
        }
        
        $sanitized[$sanitized_key] = $sanitized_value;
    }
    
    return $sanitized;
}

/**
 * Validates and sanitizes a numeric value within a range
 * 
 * @since 1.0.0
 * 
 * @param mixed $value The value to validate
 * @param int $min Minimum allowed value
 * @param int $max Maximum allowed value
 * @param int $default Default value if invalid
 * @return int The sanitized numeric value
 */
function tgf_sanitize_number($value, $min = 0, $max = PHP_INT_MAX, $default = 0) {
    // Convert to integer
    $int_value = intval($value);
    
    // Validate range
    if ($int_value < $min || $int_value > $max) {
        return $default;
    }
    
    return $int_value;
}

/**
 * Sanitizes a filename for safe filesystem usage
 * 
 * @since 1.0.0
 * 
 * @param string $filename The filename to sanitize
 * @return string The sanitized filename
 */
function tgf_sanitize_filename($filename) {
    if (!is_string($filename)) {
        return 'invalid';
    }
    
    // Remove path traversal attempts
    $filename = str_replace(['../', './', '..\\', '.\\'], '', $filename);
    
    // Replace unsafe characters
    $filename = preg_replace('/[^a-zA-Z0-9\-_.]/', '_', $filename);
    
    // Remove multiple consecutive underscores
    $filename = preg_replace('/_+/', '_', $filename);
    
    // Ensure the filename isn't empty
    if (empty($filename)) {
        $filename = 'file';
    }
    
    // Limit length
    if (strlen($filename) > 255) {
        $filename = substr($filename, 0, 255);
    }
    
    return $filename;
}

/**
 * Validates an email address with strict rules
 * 
 * @since 1.0.0
 * 
 * @param string $email The email address to validate
 * @return string|false The sanitized email or false if invalid
 */
function tgf_sanitize_email($email) {
    if (!is_string($email)) {
        return false;
    }
    
    $email = trim($email);
    
    // Use WordPress built-in sanitization first
    $sanitized_email = sanitize_email($email);
    
    // Additional validation
    if (!is_email($sanitized_email)) {
        return false;
    }
    
    return $sanitized_email;
}

/**
 * Escapes content for JavaScript usage
 * 
 * @since 1.0.0
 * 
 * @param string $string The string to escape
 * @return string The escaped string safe for JavaScript
 */
function tgf_escape_js($string) {
    if (!is_string($string)) {
        return '';
    }
    
    // Use WordPress built-in function if available
    if (function_exists('esc_js')) {
        return esc_js($string);
    }
    
    // Fallback manual escaping
    $string = wp_check_invalid_utf8($string, true);
    $string = _wp_specialchars($string, ENT_COMPAT, 'UTF-8', true);
    
    return $string;
}

/**
 * Logs debug information with plugin prefix
 * 
 * @since 1.0.0
 * 
 * @param mixed $message The message to log
 * @param string $level The log level (debug, info, warning, error)
 * @return void
 */
function tgf_log($message, $level = 'debug') {
    if (!defined('TGF_DEBUG') || !TGF_DEBUG) {
        return;
    }
    
    $allowed_levels = ['debug', 'info', 'warning', 'error'];
    if (!in_array($level, $allowed_levels, true)) {
        $level = 'debug';
    }
    
    $prefix = '[Geekline Feed]';
    
    if (is_array($message) || is_object($message)) {
        $message = print_r($message, true);
    }
    
    $log_message = sprintf('%s [%s] %s', $prefix, strtoupper($level), $message);
    
    error_log($log_message);
}

/**
 * Generates a secure nonce for plugin-specific actions
 * 
 * @since 1.0.0
 * 
 * @param string $action The action name
 * @return string The generated nonce
 */
function tgf_create_nonce($action = '') {
    $action = 'tgf_' . ($action ?: 'default_action');
    return wp_create_nonce($action);
}

/**
 * Verifies a plugin-specific nonce
 * 
 * @since 1.0.0
 * 
 * @param string $nonce The nonce to verify
 * @param string $action The action name
 * @return bool Whether the nonce is valid
 */
function tgf_verify_nonce($nonce, $action = '') {
    $action = 'tgf_' . ($action ?: 'default_action');
    return wp_verify_nonce($nonce, $action);
}

/**
 * Checks if the current user has plugin management capabilities
 * 
 * @since 1.0.0
 * 
 * @return bool Whether the user has capability
 */
function tgf_current_user_can_manage() {
    return current_user_can('manage_options');
}

/**
 * Gets a plugin option with sanitization
 * 
 * @since 1.0.0
 * 
 * @param string $option The option name
 * @param mixed $default The default value if option doesn't exist
 * @param string $sanitize_callback The sanitization callback
 * @return mixed The sanitized option value
 */
function tgf_get_option($option, $default = '', $sanitize_callback = '') {
    $value = get_option($option, $default);
    
    if ($sanitize_callback && is_callable($sanitize_callback)) {
        $value = call_user_func($sanitize_callback, $value);
    }
    
    return $value;
}

/**
 * Updates a plugin option with validation
 * 
 * @since 1.0.0
 * 
 * @param string $option The option name
 * @param mixed $value The value to set
 * @param callable $validate_callback The validation callback
 * @return bool True if option was updated, false otherwise
 */
function tgf_update_option($option, $value, $validate_callback = null) {
    if ($validate_callback && is_callable($validate_callback)) {
        $validated_value = call_user_func($validate_callback, $value);
        if ($validated_value === false) {
            return false;
        }
        $value = $validated_value;
    }
    
    return update_option($option, $value);
}

/**
 * Safe JSON encoding with error handling
 * 
 * @since 1.0.0
 * 
 * @param mixed $data The data to encode
 * @param int $options JSON encoding options
 * @param int $depth Maximum depth
 * @return string|false JSON string or false on error
 */
function tgf_json_encode($data, $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE, $depth = 512) {
    try {
        $json = json_encode($data, $options, $depth);
        
        if ($json === false && json_last_error() !== JSON_ERROR_NONE) {
            tgf_log('JSON encode error: ' . json_last_error_msg(), 'error');
            return false;
        }
        
        return $json;
    } catch (Exception $e) {
        tgf_log('JSON encode exception: ' . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Safe JSON decoding with error handling
 * 
 * @since 1.0.0
 * 
 * @param string $json JSON string to decode
 * @param bool $assoc Whether to return associative arrays
 * @param int $depth Maximum depth
 * @param int $options JSON decoding options
 * @return mixed Decoded data or null on error
 */
function tgf_json_decode($json, $assoc = true, $depth = 512, $options = 0) {
    if (!is_string($json) || empty(trim($json))) {
        return null;
    }
    
    try {
        $data = json_decode($json, $assoc, $depth, $options);
        
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            tgf_log('JSON decode error: ' . json_last_error_msg(), 'error');
            return null;
        }
        
        return $data;
    } catch (Exception $e) {
        tgf_log('JSON decode exception: ' . $e->getMessage(), 'error');
        return null;
    }
}

/**
 * Truncates text to specified length with proper UTF-8 support
 * 
 * @since 1.0.0
 * 
 * @param string $text The text to truncate
 * @param int $length Maximum length
 * @param string $suffix Suffix to append if truncated
 * @return string Truncated text
 */
function tgf_truncate_text($text, $length = 100, $suffix = '...') {
    if (!is_string($text)) {
        return '';
    }
    
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($text, 'UTF-8') > $length) {
            return mb_substr($text, 0, $length, 'UTF-8') . $suffix;
        }
    } else {
        if (strlen($text) > $length) {
            return substr($text, 0, $length) . $suffix;
        }
    }
    
    return $text;
}

/**
 * Checks if a string contains any of the specified bad words
 * 
 * @since 1.0.0
 * 
 * @param string $content The content to check
 * @param array $bad_words Array of bad words to check for
 * @return bool True if content contains bad words, false otherwise
 */
function tgf_contains_bad_words($content, $bad_words = []) {
    if (!is_string($content) || empty($content) || empty($bad_words)) {
        return false;
    }
    
    $content_lower = strtolower($content);
    
    foreach ($bad_words as $word) {
        if (is_string($word) && !empty(trim($word))) {
            $word_lower = strtolower(trim($word));
            if (strpos($content_lower, $word_lower) !== false) {
                return true;
            }
        }
    }
    
    return false;
}