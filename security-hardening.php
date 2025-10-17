<?php
/**
 * Security Hardening Configuration
 * Honar Contract Plugin - Enhanced Security
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Disable plugin/theme file editor for security
 */
if (!defined('DISALLOW_FILE_EDIT')) {
    define('DISALLOW_FILE_EDIT', true);
}

/**
 * Add security headers
 */
add_action('send_headers', function() {
    if (!is_admin()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
});

/**
 * Enhanced nonce verification with time check
 */
function honar_verify_nonce_strict($nonce, $action) {
    if (!wp_verify_nonce($nonce, $action)) {
        return false;
    }
    
    // Additional time-based verification
    $nonce_life = apply_filters('nonce_life', DAY_IN_SECONDS);
    $created = wp_verify_nonce($nonce, $action);
    
    if ($created && ($created + $nonce_life < time())) {
        return false;
    }
    
    return true;
}

/**
 * Sanitize file uploads with additional checks
 */
function honar_sanitize_file_upload($file_data) {
    // Check file size
    $max_size = 512000; // 500KB
    if (strlen($file_data) > $max_size) {
        return false;
    }
    
    // Verify base64 encoding
    if (!preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $file_data)) {
        return false;
    }
    
    // Decode and verify image
    $data = base64_decode(substr($file_data, strpos($file_data, ',') + 1));
    
    // Check if it's a valid image
    if (@imagecreatefromstring($data) === false) {
        return false;
    }
    
    return $data;
}

/**
 * IP-based request limiting
 */
function honar_check_rate_limit($ip, $max_requests = 10, $time_window = 3600) {
    $transient_key = 'honar_rate_' . md5($ip);
    $requests = get_transient($transient_key);
    
    if ($requests === false) {
        set_transient($transient_key, 1, $time_window);
        return true;
    }
    
    if ($requests >= $max_requests) {
        return false;
    }
    
    set_transient($transient_key, $requests + 1, $time_window);
    return true;
}

/**
 * Email validation with domain check
 */
function honar_validate_email_strict($email) {
    if (!is_email($email)) {
        return false;
    }
    
    // Check for disposable email domains
    $disposable_domains = ['tempmail.com', 'throwaway.email', '10minutemail.com'];
    $domain = substr(strrchr($email, "@"), 1);
    
    if (in_array($domain, $disposable_domains)) {
        return false;
    }
    
    // Verify DNS MX record
    if (function_exists('checkdnsrr')) {
        if (!checkdnsrr($domain, 'MX')) {
            return false;
        }
    }
    
    return true;
}

/**
 * SQL injection prevention for custom queries
 */
function honar_safe_query($query, $args = []) {
    global $wpdb;
    
    if (empty($args)) {
        return $wpdb->get_results($query);
    }
    
    return $wpdb->get_results($wpdb->prepare($query, $args));
}

/**
 * XSS protection for output
 */
function honar_safe_output($text, $context = 'html') {
    switch ($context) {
        case 'html':
            return esc_html($text);
        case 'attr':
            return esc_attr($text);
        case 'url':
            return esc_url($text);
        case 'js':
            return esc_js($text);
        case 'textarea':
            return esc_textarea($text);
        default:
            return esc_html($text);
    }
}

/**
 * File path traversal prevention
 */
function honar_safe_path($path, $base_dir) {
    $real_base = realpath($base_dir);
    $real_path = realpath($path);
    
    if ($real_path === false || strpos($real_path, $real_base) !== 0) {
        return false;
    }
    
    return $real_path;
}

/**
 * Secure file deletion
 */
function honar_secure_delete($file_path) {
    if (!file_exists($file_path)) {
        return false;
    }
    
    // Overwrite file content before deletion (for sensitive data)
    $size = filesize($file_path);
    $handle = fopen($file_path, 'w');
    
    if ($handle) {
        fwrite($handle, str_repeat('0', $size));
        fclose($handle);
    }
    
    return unlink($file_path);
}

/**
 * Password strength checker (for future admin features)
 */
function honar_check_password_strength($password) {
    $strength = 0;
    
    if (strlen($password) >= 8) $strength++;
    if (strlen($password) >= 12) $strength++;
    if (preg_match('/[a-z]/', $password)) $strength++;
    if (preg_match('/[A-Z]/', $password)) $strength++;
    if (preg_match('/[0-9]/', $password)) $strength++;
    if (preg_match('/[^a-zA-Z0-9]/', $password)) $strength++;
    
    return $strength;
}

/**
 * CSRF token generation and verification
 */
function honar_generate_csrf_token() {
    if (!session_id()) {
        session_start();
    }
    
    $token = bin2hex(random_bytes(32));
    $_SESSION['honar_csrf_token'] = $token;
    $_SESSION['honar_csrf_time'] = time();
    
    return $token;
}

function honar_verify_csrf_token($token) {
    if (!session_id()) {
        session_start();
    }
    
    if (!isset($_SESSION['honar_csrf_token']) || !isset($_SESSION['honar_csrf_time'])) {
        return false;
    }
    
    // Check token expiry (10 minutes)
    if (time() - $_SESSION['honar_csrf_time'] > 600) {
        return false;
    }
    
    return hash_equals($_SESSION['honar_csrf_token'], $token);
}

/**
 * Content Security Policy header
 */
add_action('send_headers', function() {
    if (!is_admin()) {
        $csp = "default-src 'self'; ";
        $csp .= "script-src 'self' 'unsafe-inline' 'unsafe-eval'; ";
        $csp .= "style-src 'self' 'unsafe-inline'; ";
        $csp .= "img-src 'self' data: https:; ";
        $csp .= "font-src 'self' data:; ";
        $csp .= "connect-src 'self'; ";
        $csp .= "frame-ancestors 'self';";
        
        header("Content-Security-Policy: " . $csp);
    }
});

/**
 * Log security events
 */
function honar_log_security_event($event_type, $details = []) {
    $log_entry = [
        'timestamp' => current_time('mysql'),
        'event_type' => $event_type,
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        'details' => $details
    ];
    
    $upload_dir = wp_upload_dir();
    $log_file = trailingslashit($upload_dir['basedir']) . 'honar-security.log';
    
    file_put_contents(
        $log_file,
        date('Y-m-d H:i:s') . ' - ' . json_encode($log_entry) . PHP_EOL,
        FILE_APPEND
    );
}

/**
 * Protect against brute force attacks
 */
function honar_check_brute_force($identifier) {
    $attempts_key = 'honar_login_attempts_' . md5($identifier);
    $lockout_key = 'honar_login_lockout_' . md5($identifier);
    
    // Check if locked out
    if (get_transient($lockout_key)) {
        return false;
    }
    
    $attempts = get_transient($attempts_key);
    
    if ($attempts === false) {
        set_transient($attempts_key, 1, 300); // 5 minutes
        return true;
    }
    
    if ($attempts >= 5) {
        set_transient($lockout_key, true, 900); // 15 minutes lockout
        honar_log_security_event('brute_force_lockout', ['identifier' => $identifier]);
        return false;
    }
    
    set_transient($attempts_key, $attempts + 1, 300);
    return true;
}
