<?php
/**
 * Performance & Optimization Configuration
 * Honar Contract Plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add cache headers for static assets
 */
add_action('wp_enqueue_scripts', function() {
    if (!is_admin()) {
        header('Cache-Control: public, max-age=31536000');
    }
}, 1);

/**
 * Optimize PDF generation memory usage
 */
add_filter('dompdf_options', function($options) {
    if (is_array($options)) {
        $options['isFontSubsettingEnabled'] = true;
        $options['isPhpEnabled'] = false; // Security
    }
    return $options;
});

/**
 * Clean up old contracts (older than 30 days)
 */
add_action('wp_scheduled_delete', function() {
    $upload_dir = wp_upload_dir();
    $contracts_dir = trailingslashit($upload_dir['basedir']) . HONAR_CONTRACT_DIR;
    
    if (file_exists($contracts_dir)) {
        $files = glob($contracts_dir . '/*.{pdf,png}', GLOB_BRACE);
        $current_time = time();
        $thirty_days = 30 * 24 * 60 * 60;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                if ($current_time - filemtime($file) >= $thirty_days) {
                    unlink($file);
                }
            }
        }
    }
});

/**
 * Add cleanup schedule
 */
if (!wp_next_scheduled('wp_scheduled_delete')) {
    wp_schedule_event(time(), 'daily', 'wp_scheduled_delete');
}

/**
 * Dequeue unnecessary scripts on contract page
 */
add_action('wp_print_scripts', function() {
    if (is_page() && has_shortcode(get_the_content(), 'honar_maghz_contract')) {
        // Remove unnecessary scripts for better performance
        wp_dequeue_script('comment-reply');
    }
}, 100);

/**
 * Add preload for critical assets
 */
add_action('wp_head', function() {
    if (is_page() && has_shortcode(get_the_content(), 'honar_maghz_contract')) {
        echo '<link rel="preload" href="' . plugin_dir_url(__FILE__) . 'honar-contract.css" as="style">';
        echo '<link rel="preload" href="' . plugin_dir_url(__FILE__) . 'honar-contract.js" as="script">';
    }
}, 1);

/**
 * Optimize database queries
 */
add_filter('posts_clauses', function($clauses, $query) {
    // Add index hints if needed
    return $clauses;
}, 10, 2);

/**
 * Add support for WebP images for signatures
 */
add_filter('honar_signature_format', function($format) {
    if (function_exists('imagewebp')) {
        return 'webp';
    }
    return 'png';
});

/**
 * Rate limiting for contract submissions
 */
add_action('wp_ajax_honar_contract_submit', function() {
    $ip = $_SERVER['REMOTE_ADDR'];
    $transient_key = 'honar_contract_limit_' . md5($ip);
    $count = get_transient($transient_key);
    
    if ($count && $count > 5) {
        wp_send_json_error(['msg' => 'تعداد درخواست‌ها بیش از حد مجاز است. لطفاً چند دقیقه صبر کنید.']);
        exit;
    }
    
    set_transient($transient_key, ($count ? $count + 1 : 1), HOUR_IN_SECONDS);
}, 1);

/**
 * Error logging for debugging
 */
if (WP_DEBUG) {
    add_action('honar_contract_error', function($error) {
        error_log('Honar Contract Error: ' . print_r($error, true));
    });
}
