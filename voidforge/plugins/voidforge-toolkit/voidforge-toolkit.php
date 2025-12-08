<?php
/**
 * Plugin Name: VoidForge Toolkit
 * Description: The ultimate content toolkit for VoidForge CMS — shortcodes, components, security tools, utilities, and developer helpers
 * Version: 3.0.0
 * Author: VoidForge CMS
 */

defined('CMS_ROOT') or die('Direct access not allowed');

// =========================================================================
// SECURITY SALTS API - Like WordPress secret-key service
// =========================================================================

// Register API endpoints
add_action('api_request', function($apiPath) {
    // Handle /api/salts endpoint
    if ($apiPath === 'salts' || $apiPath === 'salts/') {
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('X-VoidForge-CMS: Salt Generator v1.0');
        
        echo forge_generate_salts();
        exit;
    }
    
    // Handle /api/salts/json endpoint
    if ($apiPath === 'salts/json' || $apiPath === 'salts/json/') {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        
        echo json_encode([
            'generated' => date('c'),
            'salts' => forge_generate_salts_array()
        ], JSON_PRETTY_PRINT);
        exit;
    }
});

/**
 * Generate cryptographically secure random string
 */
function forge_generate_salt($length = 64) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+[]{}|;:,.<>?';
    $salt = '';
    $max = strlen($chars) - 1;
    
    for ($i = 0; $i < $length; $i++) {
        $salt .= $chars[random_int(0, $max)];
    }
    
    return $salt;
}

/**
 * Generate all salts as formatted PHP constants
 */
function forge_generate_salts() {
    $keys = [
        'AUTH_KEY',
        'SECURE_AUTH_KEY', 
        'LOGGED_IN_KEY',
        'NONCE_KEY',
        'AUTH_SALT',
        'SECURE_AUTH_SALT',
        'LOGGED_IN_SALT',
        'NONCE_SALT',
        'SESSION_KEY',
        'CSRF_KEY',
        'API_KEY',
        'ENCRYPTION_KEY'
    ];
    
    $output = "/**\n";
    $output .= " * VoidForge CMS Security Keys and Salts\n";
    $output .= " * Generated: " . date('Y-m-d H:i:s T') . "\n";
    $output .= " * \n";
    $output .= " * You can regenerate these at: " . SITE_URL . "/api/salts\n";
    $output .= " * Copy these to your config.php file.\n";
    $output .= " */\n\n";
    
    foreach ($keys as $key) {
        $salt = forge_generate_salt(64);
        $output .= "define('" . $key . "', '" . $salt . "');\n";
    }
    
    return $output;
}

/**
 * Generate salts as array for JSON API
 */
function forge_generate_salts_array() {
    $keys = [
        'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY',
        'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT',
        'SESSION_KEY', 'CSRF_KEY', 'API_KEY', 'ENCRYPTION_KEY'
    ];
    
    $salts = [];
    foreach ($keys as $key) {
        $salts[$key] = forge_generate_salt(64);
    }
    return $salts;
}

// =========================================================================
// ICON LIBRARY - Comprehensive SVG icon set
// =========================================================================

function getForgeIcon($name, $size = 20, $color = 'currentColor') {
    $icons = [
        // UI Icons
        'check' => '<polyline points="20 6 9 17 4 12"/>',
        'x' => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
        'plus' => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
        'minus' => '<line x1="5" y1="12" x2="19" y2="12"/>',
        'chevron-down' => '<polyline points="6 9 12 15 18 9"/>',
        'chevron-up' => '<polyline points="18 15 12 9 6 15"/>',
        'chevron-left' => '<polyline points="15 18 9 12 15 6"/>',
        'chevron-right' => '<polyline points="9 18 15 12 9 6"/>',
        'arrow-right' => '<line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>',
        'arrow-left' => '<line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>',
        'external-link' => '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>',
        'menu' => '<line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>',
        'search' => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
        
        // Content Icons
        'file' => '<path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/>',
        'file-text' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>',
        'image' => '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>',
        'video' => '<polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/>',
        'music' => '<path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>',
        'folder' => '<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>',
        'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',
        'upload' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>',
        'copy' => '<rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
        'trash' => '<polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
        'edit' => '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>',
        
        // Status Icons
        'check-circle' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
        'x-circle' => '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>',
        'alert-circle' => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>',
        'alert-triangle' => '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        'info' => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
        'help-circle' => '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        
        // Social Icons
        'mail' => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>',
        'phone' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>',
        'globe' => '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
        'share' => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>',
        'link' => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
        
        // Development Icons
        'code' => '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>',
        'terminal' => '<polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/>',
        'database' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>',
        'server' => '<rect x="2" y="2" width="20" height="8" rx="2" ry="2"/><rect x="2" y="14" width="20" height="8" rx="2" ry="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/>',
        'cpu' => '<rect x="4" y="4" width="16" height="16" rx="2" ry="2"/><rect x="9" y="9" width="6" height="6"/><line x1="9" y1="1" x2="9" y2="4"/><line x1="15" y1="1" x2="15" y2="4"/><line x1="9" y1="20" x2="9" y2="23"/><line x1="15" y1="20" x2="15" y2="23"/><line x1="20" y1="9" x2="23" y2="9"/><line x1="20" y1="14" x2="23" y2="14"/><line x1="1" y1="9" x2="4" y2="9"/><line x1="1" y1="14" x2="4" y2="14"/>',
        'git-branch' => '<line x1="6" y1="3" x2="6" y2="15"/><circle cx="18" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M18 9a9 9 0 0 1-9 9"/>',
        'zap' => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'lock' => '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'key' => '<path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/>',
        
        // Misc Icons
        'star' => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
        'heart' => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>',
        'bookmark' => '<path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>',
        'clock' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
        'user' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'home' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
        'layout' => '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/>',
        'layers' => '<polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>',
        'package' => '<line x1="16.5" y1="9.4" x2="7.5" y2="4.21"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>',
        'flag' => '<path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/>',
        'tag' => '<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/>',
        'award' => '<circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/>',
        'thumbs-up' => '<path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/>',
        'mouse-pointer' => '<path d="M3 3l7.07 16.97 2.51-7.39 7.39-2.51L3 3z"/><path d="M13 13l6 6"/>',
        'loader' => '<line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"/><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"/><line x1="2" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="22" y2="12"/><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"/><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"/>',
        'message-circle' => '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>',
        'type' => '<polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/>',
        'book' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>',
        'palette' => '<circle cx="13.5" cy="6.5" r="2.5"/><circle cx="19" cy="12" r="2.5"/><circle cx="17" cy="18.5" r="2.5"/><circle cx="8.5" cy="17.5" r="2.5"/><circle cx="5" cy="10.5" r="2.5"/>',
        'grid' => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>',
        'bell' => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
        'refresh' => '<polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>',
        'eye' => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>',
        'eye-off' => '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>',
        'play' => '<polygon points="5 3 19 12 5 21 5 3"/>',
        'pause' => '<rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/>',
        'volume-2' => '<polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/>',
        'wifi' => '<path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/>',
        'battery' => '<rect x="1" y="6" width="18" height="12" rx="2" ry="2"/><line x1="23" y1="13" x2="23" y2="11"/>',
        'bluetooth' => '<polyline points="6.5 6.5 17.5 17.5 12 23 12 1 17.5 6.5 6.5 17.5"/>',
        'sun' => '<circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>',
        'moon' => '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>',
        'cloud' => '<path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/>',
        'trending-up' => '<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>',
        'trending-down' => '<polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/>',
        'activity' => '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>',
        'bar-chart' => '<line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/>',
        'pie-chart' => '<path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/>',
    ];
    
    $path = $icons[$name] ?? $icons['star'];
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
}

// =========================================================================
// SHORTCODE PROCESSING WITH JAVASCRIPT OUTPUT
// =========================================================================

// Add JavaScript for interactive components
add_action('frontend_footer', function() {
    ?>
    <script>
    (function() {
        'use strict';
        
        // Tab functionality
        function initTabs() {
            document.querySelectorAll('.forge-tabs').forEach(function(tabContainer) {
                var tabBtns = tabContainer.querySelectorAll('.forge-tab-btn');
                var tabPanels = tabContainer.querySelectorAll('.forge-tab-panel');
                
                tabBtns.forEach(function(btn, index) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        
                        // Remove active from all
                        tabBtns.forEach(function(b) { b.classList.remove('active'); });
                        tabPanels.forEach(function(p) { p.classList.remove('active'); p.style.display = 'none'; });
                        
                        // Add active to clicked
                        btn.classList.add('active');
                        if (tabPanels[index]) {
                            tabPanels[index].classList.add('active');
                            tabPanels[index].style.display = 'block';
                        }
                    });
                });
            });
        }
        
        // Accordion functionality
        function initAccordions() {
            document.querySelectorAll('.forge-accordion-header').forEach(function(header) {
                header.addEventListener('click', function() {
                    var item = this.parentElement;
                    var content = item.querySelector('.forge-accordion-content');
                    var icon = this.querySelector('.forge-accordion-icon');
                    var isOpen = item.classList.contains('open');
                    
                    if (isOpen) {
                        item.classList.remove('open');
                        content.style.maxHeight = '0';
                        if (icon) icon.style.transform = 'rotate(0deg)';
                    } else {
                        item.classList.add('open');
                        content.style.maxHeight = content.scrollHeight + 'px';
                        if (icon) icon.style.transform = 'rotate(180deg)';
                    }
                });
            });
        }
        
        // Dismissible alerts
        function initAlerts() {
            document.querySelectorAll('.forge-alert-dismiss').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var alert = this.closest('.forge-alert');
                    if (alert) {
                        alert.style.opacity = '0';
                        alert.style.transform = 'translateY(-10px)';
                        setTimeout(function() { alert.remove(); }, 200);
                    }
                });
            });
        }
        
        // Modal functionality
        function initModals() {
            document.querySelectorAll('[data-modal-open]').forEach(function(trigger) {
                trigger.addEventListener('click', function(e) {
                    e.preventDefault();
                    var modalId = this.getAttribute('data-modal-open');
                    var modal = document.getElementById(modalId);
                    if (modal) {
                        modal.style.display = 'flex';
                        document.body.style.overflow = 'hidden';
                    }
                });
            });
            
            document.querySelectorAll('.forge-modal-close, .forge-modal-overlay').forEach(function(el) {
                el.addEventListener('click', function() {
                    var modal = this.closest('.forge-modal');
                    if (modal) {
                        modal.style.display = 'none';
                        document.body.style.overflow = '';
                    }
                });
            });
            
            document.querySelectorAll('.forge-modal-content').forEach(function(content) {
                content.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            });
        }
        
        // Copy code functionality
        function initCodeCopy() {
            document.querySelectorAll('.forge-code-copy').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var code = this.closest('.forge-code').querySelector('code');
                    if (code) {
                        navigator.clipboard.writeText(code.textContent).then(function() {
                            btn.innerHTML = '<?= addslashes(getForgeIcon('check', 16)) ?>';
                            setTimeout(function() {
                                btn.innerHTML = '<?= addslashes(getForgeIcon('copy', 16)) ?>';
                            }, 2000);
                        });
                    }
                });
            });
        }
        
        // Tooltip functionality
        function initTooltips() {
            document.querySelectorAll('.forge-tooltip').forEach(function(el) {
                var tip = document.createElement('div');
                tip.className = 'forge-tooltip-text';
                tip.textContent = el.getAttribute('data-tooltip');
                tip.style.cssText = 'position:absolute;bottom:100%;left:50%;transform:translateX(-50%);padding:6px 12px;background:#1e293b;color:#fff;font-size:12px;border-radius:6px;white-space:nowrap;opacity:0;pointer-events:none;transition:opacity 0.2s;margin-bottom:8px;z-index:1000;';
                el.style.position = 'relative';
                el.appendChild(tip);
                
                el.addEventListener('mouseenter', function() { tip.style.opacity = '1'; });
                el.addEventListener('mouseleave', function() { tip.style.opacity = '0'; });
            });
        }
        
        // Initialize on DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
        
        function init() {
            initTabs();
            initAccordions();
            initAlerts();
            initModals();
            initCodeCopy();
            initTooltips();
        }
    })();
    </script>
    <?php
});

// =========================================================================
// BUTTON SHORTCODE
// =========================================================================
register_tag('button', function($attrs, $content) {
    $href = $attrs['href'] ?? '#';
    $style = $attrs['style'] ?? 'primary';
    $size = $attrs['size'] ?? 'md';
    $icon = $attrs['icon'] ?? '';
    $target = $attrs['target'] ?? '';
    
    $baseStyles = 'display:inline-flex;align-items:center;gap:0.5rem;text-decoration:none;font-weight:600;border-radius:8px;transition:all 0.2s;cursor:pointer;border:none;';
    
    $sizes = [
        'sm' => 'padding:0.5rem 1rem;font-size:0.8125rem;',
        'md' => 'padding:0.625rem 1.25rem;font-size:0.875rem;',
        'lg' => 'padding:0.875rem 1.75rem;font-size:1rem;',
    ];
    
    $styles = [
        'primary' => 'background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;box-shadow:0 4px 14px rgba(99,102,241,0.3);',
        'secondary' => 'background:linear-gradient(135deg,#64748b,#475569);color:#fff;',
        'success' => 'background:linear-gradient(135deg,#10b981,#059669);color:#fff;',
        'danger' => 'background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;',
        'warning' => 'background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;',
        'outline' => 'background:transparent;color:#6366f1;border:2px solid #6366f1;',
        'ghost' => 'background:transparent;color:#64748b;',
    ];
    
    $css = $baseStyles . ($sizes[$size] ?? $sizes['md']) . ($styles[$style] ?? $styles['primary']);
    $targetAttr = $target ? ' target="' . esc($target) . '"' : '';
    $iconHtml = $icon ? getForgeIcon($icon, 16) : '';
    
    return '<a href="' . esc($href) . '" style="' . $css . '"' . $targetAttr . '>' . $iconHtml . $content . '</a>';
}, ['has_content' => true]);

// =========================================================================
// ALERT SHORTCODE
// =========================================================================
register_tag('alert', function($attrs, $content) {
    $type = $attrs['type'] ?? 'info';
    $title = $attrs['title'] ?? '';
    $icon = ($attrs['icon'] ?? 'true') === 'true';
    $dismissible = ($attrs['dismissible'] ?? 'false') === 'true';
    
    $configs = [
        'info' => ['bg' => '#eff6ff', 'border' => '#3b82f6', 'text' => '#1e40af', 'icon' => 'info'],
        'success' => ['bg' => '#f0fdf4', 'border' => '#22c55e', 'text' => '#166534', 'icon' => 'check-circle'],
        'warning' => ['bg' => '#fffbeb', 'border' => '#f59e0b', 'text' => '#92400e', 'icon' => 'alert-triangle'],
        'danger' => ['bg' => '#fef2f2', 'border' => '#ef4444', 'text' => '#991b1b', 'icon' => 'x-circle'],
        'error' => ['bg' => '#fef2f2', 'border' => '#ef4444', 'text' => '#991b1b', 'icon' => 'x-circle'],
    ];
    
    $c = $configs[$type] ?? $configs['info'];
    
    $html = '<div class="forge-alert" style="display:flex;align-items:flex-start;gap:1rem;padding:1rem 1.25rem;background:' . $c['bg'] . ';border-left:4px solid ' . $c['border'] . ';border-radius:0 8px 8px 0;margin:1rem 0;transition:all 0.2s;">';
    
    if ($icon) {
        $html .= '<div style="flex-shrink:0;color:' . $c['border'] . ';">' . getForgeIcon($c['icon'], 20) . '</div>';
    }
    
    $html .= '<div style="flex:1;color:' . $c['text'] . ';">';
    if ($title) {
        $html .= '<div style="font-weight:600;margin-bottom:0.25rem;">' . esc($title) . '</div>';
    }
    $html .= '<div>' . $content . '</div></div>';
    
    if ($dismissible) {
        $html .= '<button class="forge-alert-dismiss" style="background:none;border:none;cursor:pointer;padding:0;color:' . $c['text'] . ';opacity:0.5;">' . getForgeIcon('x', 18) . '</button>';
    }
    
    return $html . '</div>';
}, ['has_content' => true]);

// =========================================================================
// TABS SHORTCODE - WITH WORKING JAVASCRIPT
// =========================================================================
register_tag('tabs', function($attrs, $content) {
    static $tabsId = 0;
    $tabsId++;
    $id = 'forge-tabs-' . $tabsId;
    
    // Parse tab titles and content
    preg_match_all('/\{tab\s+title="([^"]+)"\}(.*?)\{\/tab\}/s', $content, $matches, PREG_SET_ORDER);
    
    if (empty($matches)) {
        return '<div class="forge-alert" style="padding:1rem;background:#fef2f2;border-left:4px solid #ef4444;border-radius:0 8px 8px 0;">No tabs found. Use {tab title="Name"}Content{/tab} inside {tabs}.</div>';
    }
    
    // Build tab buttons
    $buttons = '<div style="display:flex;gap:0;border-bottom:2px solid #e2e8f0;margin-bottom:1.5rem;">';
    foreach ($matches as $i => $match) {
        $active = $i === 0 ? 'active' : '';
        $activeStyle = $i === 0 ? 'color:#6366f1;border-bottom-color:#6366f1;' : 'color:#64748b;border-bottom-color:transparent;';
        $buttons .= '<button class="forge-tab-btn ' . $active . '" style="padding:0.875rem 1.5rem;font-size:0.9375rem;font-weight:600;background:none;border:none;border-bottom:2px solid;margin-bottom:-2px;cursor:pointer;transition:all 0.2s;' . $activeStyle . '">' . esc($match[1]) . '</button>';
    }
    $buttons .= '</div>';
    
    // Build tab panels
    $panels = '';
    foreach ($matches as $i => $match) {
        $active = $i === 0 ? 'active' : '';
        $display = $i === 0 ? 'block' : 'none';
        $panels .= '<div class="forge-tab-panel ' . $active . '" style="display:' . $display . ';">' . trim($match[2]) . '</div>';
    }
    
    return '<div class="forge-tabs" id="' . $id . '" style="margin:1.5rem 0;">' . $buttons . '<div class="forge-tab-panels">' . $panels . '</div></div>';
}, ['has_content' => true]);

register_tag('tab', function($a, $c) { return $c; }, ['has_content' => true]);

// =========================================================================
// ACCORDION SHORTCODE
// =========================================================================
register_tag('accordion', function($attrs, $content) {
    preg_match_all('/\{accordion-item\s+([^}]*)\}(.*?)\{\/accordion-item\}/s', $content, $matches, PREG_SET_ORDER);
    
    $html = '<div class="forge-accordion" style="margin:1.5rem 0;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;">';
    
    foreach ($matches as $i => $m) {
        preg_match_all('/(\w+)="([^"]*)"/', $m[1], $am, PREG_SET_ORDER);
        $a = [];
        foreach ($am as $at) $a[$at[1]] = $at[2];
        
        $title = $a['title'] ?? 'Accordion Item';
        $open = ($a['open'] ?? '') === 'true';
        $openClass = $open ? ' open' : '';
        $maxHeight = $open ? 'max-height:500px;' : 'max-height:0;';
        $rotate = $open ? 'transform:rotate(180deg);' : '';
        
        $html .= '<div class="forge-accordion-item' . $openClass . '" style="border-bottom:1px solid #e2e8f0;">';
        $html .= '<div class="forge-accordion-header" style="display:flex;align-items:center;justify-content:space-between;padding:1rem 1.25rem;cursor:pointer;background:#f8fafc;transition:background 0.2s;">';
        $html .= '<span style="font-weight:600;color:#1e293b;">' . esc($title) . '</span>';
        $html .= '<span class="forge-accordion-icon" style="transition:transform 0.3s;' . $rotate . '">' . getForgeIcon('chevron-down', 20, '#64748b') . '</span>';
        $html .= '</div>';
        $html .= '<div class="forge-accordion-content" style="overflow:hidden;transition:max-height 0.3s ease;' . $maxHeight . '">';
        $html .= '<div style="padding:1rem 1.25rem;color:#475569;">' . trim($m[2]) . '</div>';
        $html .= '</div></div>';
    }
    
    return $html . '</div>';
}, ['has_content' => true]);

register_tag('accordion-item', function($a, $c) { return $c; }, ['has_content' => true]);

// =========================================================================
// PROGRESS BAR SHORTCODE
// =========================================================================
register_tag('progress', function($attrs) {
    $value = (int)($attrs['value'] ?? 50);
    $label = $attrs['label'] ?? '';
    $color = $attrs['color'] ?? 'primary';
    $animated = ($attrs['animated'] ?? 'false') === 'true';
    $striped = ($attrs['striped'] ?? 'false') === 'true';
    $showValue = ($attrs['showvalue'] ?? 'true') === 'true';
    
    $colors = [
        'primary' => 'linear-gradient(135deg,#6366f1,#8b5cf6)',
        'success' => 'linear-gradient(135deg,#10b981,#059669)',
        'warning' => 'linear-gradient(135deg,#f59e0b,#d97706)',
        'danger' => 'linear-gradient(135deg,#ef4444,#dc2626)',
        'info' => 'linear-gradient(135deg,#3b82f6,#1d4ed8)',
    ];
    
    $bg = $colors[$color] ?? $colors['primary'];
    
    $stripeStyle = $striped ? 'background-image:linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-size:1rem 1rem;' : '';
    $animationStyle = $animated ? 'animation:progress-stripes 1s linear infinite;' : '';
    
    $html = '<div style="margin:1rem 0;">';
    if ($label) {
        $html .= '<div style="display:flex;justify-content:space-between;margin-bottom:0.5rem;font-size:0.875rem;">';
        $html .= '<span style="font-weight:500;color:#374151;">' . esc($label) . '</span>';
        if ($showValue) $html .= '<span style="color:#6b7280;">' . $value . '%</span>';
        $html .= '</div>';
    }
    $html .= '<div style="height:10px;background:#e5e7eb;border-radius:9999px;overflow:hidden;">';
    $html .= '<div style="width:' . $value . '%;height:100%;background:' . $bg . ';border-radius:9999px;transition:width 0.6s ease;' . $stripeStyle . $animationStyle . '"></div>';
    $html .= '</div></div>';
    
    if ($animated) {
        $html .= '<style>@keyframes progress-stripes{0%{background-position:1rem 0}100%{background-position:0 0}}</style>';
    }
    
    return $html;
}, ['has_content' => false]);

// =========================================================================
// TIMELINE SHORTCODE
// =========================================================================
register_tag('timeline', function($attrs, $content) {
    preg_match_all('/\{timeline-item\s+([^}]*)\}(.*?)\{\/timeline-item\}/s', $content, $matches, PREG_SET_ORDER);
    
    $html = '<div class="forge-timeline" style="position:relative;padding-left:2rem;margin:2rem 0;">';
    $html .= '<div style="position:absolute;left:7px;top:0;bottom:0;width:2px;background:linear-gradient(to bottom,#6366f1,#8b5cf6);"></div>';
    
    foreach ($matches as $m) {
        preg_match_all('/(\w+)="([^"]*)"/', $m[1], $am, PREG_SET_ORDER);
        $a = [];
        foreach ($am as $at) $a[$at[1]] = $at[2];
        
        $date = $a['date'] ?? '';
        $title = $a['title'] ?? '';
        $icon = $a['icon'] ?? '';
        $status = $a['status'] ?? '';
        
        $dotStyle = $status === 'current' 
            ? 'background:linear-gradient(135deg,#6366f1,#8b5cf6);box-shadow:0 0 0 4px rgba(99,102,241,0.2);' 
            : 'background:#fff;border:2px solid #6366f1;';
        
        $html .= '<div style="position:relative;padding-bottom:2rem;">';
        $html .= '<div style="position:absolute;left:-2rem;width:16px;height:16px;border-radius:50%;' . $dotStyle . '"></div>';
        $html .= '<div style="padding-left:1rem;">';
        if ($date) $html .= '<div style="font-size:0.8125rem;color:#6366f1;font-weight:600;margin-bottom:0.25rem;">' . esc($date) . '</div>';
        if ($title) $html .= '<div style="font-size:1.125rem;font-weight:600;color:#1e293b;margin-bottom:0.5rem;">' . esc($title) . '</div>';
        $html .= '<div style="color:#64748b;">' . trim($m[2]) . '</div>';
        $html .= '</div></div>';
    }
    
    return $html . '</div>';
}, ['has_content' => true]);

register_tag('timeline-item', function($a, $c) { return $c; }, ['has_content' => true]);

// =========================================================================
// GRID & CARD SHORTCODES
// =========================================================================
register_tag('grid', function($attrs, $content) {
    $cols = $attrs['cols'] ?? '3';
    $gap = $attrs['gap'] ?? '1.5rem';
    return '<div style="display:grid;grid-template-columns:repeat(' . esc($cols) . ',1fr);gap:' . esc($gap) . ';margin:1.5rem 0;">'. $content . '</div>';
}, ['has_content' => true]);

register_tag('card', function($attrs, $content) {
    $title = $attrs['title'] ?? '';
    $icon = $attrs['icon'] ?? '';
    $image = $attrs['image'] ?? '';
    
    $html = '<div style="background:#fff;border-radius:16px;box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);overflow:hidden;height:100%;transition:transform 0.2s,box-shadow 0.2s;" onmouseover="this.style.transform=\'translateY(-4px)\';this.style.boxShadow=\'0 20px 40px rgba(0,0,0,0.1)\';" onmouseout="this.style.transform=\'\';this.style.boxShadow=\'0 4px 6px -1px rgba(0,0,0,0.1)\';">';
    
    if ($image) {
        $html .= '<div style="aspect-ratio:16/9;overflow:hidden;"><img src="' . esc($image) . '" style="width:100%;height:100%;object-fit:cover;"></div>';
    }
    
    $html .= '<div style="padding:1.5rem;">';
    
    if ($icon) {
        $html .= '<div style="width:48px;height:48px;background:linear-gradient(135deg,#eff6ff,#dbeafe);border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:1rem;">' . getForgeIcon($icon, 24, '#6366f1') . '</div>';
    }
    
    if ($title) $html .= '<h3 style="margin:0 0 0.75rem;font-size:1.125rem;color:#1e293b;">' . esc($title) . '</h3>';
    $html .= '<div style="color:#64748b;line-height:1.6;">' . $content . '</div>';
    $html .= '</div></div>';
    
    return $html;
}, ['has_content' => true]);

// =========================================================================
// CODE BLOCK SHORTCODE
// =========================================================================
register_tag('code', function($attrs, $content) {
    $lang = $attrs['lang'] ?? '';
    $title = $attrs['title'] ?? '';
    $copy = ($attrs['copy'] ?? 'true') === 'true';
    
    $html = '<div class="forge-code" style="margin:1rem 0;border-radius:12px;overflow:hidden;box-shadow:0 4px 6px rgba(0,0,0,0.1);">';
    
    // Header
    $html .= '<div style="background:#1e293b;padding:0.75rem 1rem;display:flex;justify-content:space-between;align-items:center;">';
    $html .= '<span style="color:#94a3b8;font-size:0.8125rem;font-weight:500;">' . esc($title ?: $lang) . '</span>';
    $html .= '<div style="display:flex;align-items:center;gap:12px;">';
    if ($copy) {
        $html .= '<button class="forge-code-copy" style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px;display:flex;">' . getForgeIcon('copy', 16) . '</button>';
    }
    $html .= '<div style="display:flex;gap:6px;">';
    $html .= '<span style="width:12px;height:12px;border-radius:50%;background:#ef4444;"></span>';
    $html .= '<span style="width:12px;height:12px;border-radius:50%;background:#f59e0b;"></span>';
    $html .= '<span style="width:12px;height:12px;border-radius:50%;background:#22c55e;"></span>';
    $html .= '</div></div></div>';
    
    // Code body
    $html .= '<pre style="background:#0f172a;color:#e2e8f0;padding:1.25rem;margin:0;overflow-x:auto;font-family:\'JetBrains Mono\',monospace;font-size:0.875rem;line-height:1.7;"><code>' . esc($content) . '</code></pre>';
    
    return $html . '</div>';
}, ['has_content' => true]);

// =========================================================================
// QUOTE SHORTCODE
// =========================================================================
register_tag('quote', function($attrs, $content) {
    $author = $attrs['author'] ?? '';
    $source = $attrs['source'] ?? '';
    $style = $attrs['style'] ?? 'classic';
    
    if ($style === 'modern') {
        $html = '<figure style="margin:2rem 0;padding:2rem;background:linear-gradient(135deg,#f8fafc,#f1f5f9);border-radius:16px;position:relative;">';
        $html .= '<div style="position:absolute;top:1rem;left:1.5rem;font-size:4rem;color:#6366f1;opacity:0.2;font-family:Georgia;">"</div>';
        $html .= '<blockquote style="margin:0;padding-left:1rem;font-size:1.25rem;font-style:italic;color:#334155;">' . $content . '</blockquote>';
        if ($author) {
            $html .= '<figcaption style="margin-top:1.5rem;display:flex;align-items:center;gap:1rem;">';
            $html .= '<div style="width:48px;height:48px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:600;">' . strtoupper(substr($author, 0, 1)) . '</div>';
            $html .= '<div><div style="font-weight:600;color:#1e293b;">' . esc($author) . '</div>';
            if ($source) $html .= '<div style="font-size:0.875rem;color:#64748b;">' . esc($source) . '</div>';
            $html .= '</div></figcaption>';
        }
        return $html . '</figure>';
    }
    
    $html = '<blockquote style="border-left:4px solid #6366f1;padding:1rem 1.5rem;margin:1.5rem 0;background:#f8fafc;border-radius:0 8px 8px 0;font-style:italic;">';
    $html .= '<p style="margin:0;font-size:1.125rem;color:#334155;">' . $content . '</p>';
    if ($author) {
        $html .= '<footer style="margin-top:0.75rem;font-style:normal;font-size:0.875rem;color:#64748b;">— <strong>' . esc($author) . '</strong>';
        if ($source) $html .= ', <cite>' . esc($source) . '</cite>';
        $html .= '</footer>';
    }
    return $html . '</blockquote>';
}, ['has_content' => true]);

// =========================================================================
// TYPOGRAPHY SHORTCODES
// =========================================================================
register_tag('lead', function($a, $c) {
    return '<p style="font-size:1.25rem;line-height:1.8;color:#475569;margin-bottom:1.5rem;">' . $c . '</p>';
}, ['has_content' => true]);

register_tag('highlight', function($attrs, $content) {
    $colors = ['yellow' => '#fef08a', 'green' => '#bbf7d0', 'blue' => '#bfdbfe', 'pink' => '#fbcfe8'];
    $bg = $colors[$attrs['color'] ?? 'yellow'] ?? $colors['yellow'];
    return '<mark style="background:linear-gradient(to bottom,transparent 50%,' . $bg . ' 50%);padding:0 0.25rem;">' . $content . '</mark>';
}, ['has_content' => true]);

register_tag('badge', function($attrs, $content) {
    $color = $attrs['color'] ?? 'blue';
    $pill = ($attrs['pill'] ?? '') === 'true';
    
    $colors = [
        'blue' => ['bg' => '#dbeafe', 'text' => '#1e40af'],
        'green' => ['bg' => '#dcfce7', 'text' => '#166534'],
        'red' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
        'yellow' => ['bg' => '#fef3c7', 'text' => '#92400e'],
        'purple' => ['bg' => '#f3e8ff', 'text' => '#7c3aed'],
        'gray' => ['bg' => '#f1f5f9', 'text' => '#475569'],
        'gradient' => ['bg' => 'linear-gradient(135deg,#6366f1,#8b5cf6)', 'text' => '#fff'],
    ];
    
    $c = $colors[$color] ?? $colors['blue'];
    $bgStyle = strpos($c['bg'], 'gradient') !== false ? $c['bg'] : 'background:' . $c['bg'];
    
    return '<span style="display:inline-block;padding:0.25rem 0.75rem;font-size:0.75rem;font-weight:600;border-radius:' . ($pill ? '9999px' : '6px') . ';' . $bgStyle . ';color:' . $c['text'] . ';">' . $content . '</span>';
}, ['has_content' => true]);

// =========================================================================
// UTILITY SHORTCODES
// =========================================================================
register_tag('divider', function($attrs) {
    $style = $attrs['style'] ?? 'solid';
    $spacing = $attrs['spacing'] ?? '2rem';
    
    if ($style === 'gradient') {
        return '<hr style="border:none;height:2px;background:linear-gradient(to right,transparent,#6366f1,#8b5cf6,transparent);margin:' . esc($spacing) . ' 0;">';
    }
    return '<hr style="border:none;border-top:1px ' . esc($style) . ' #e2e8f0;margin:' . esc($spacing) . ' 0;">';
}, ['has_content' => false]);

register_tag('icon', function($attrs) {
    return getForgeIcon($attrs['name'] ?? 'star', $attrs['size'] ?? '20', $attrs['color'] ?? 'currentColor');
}, ['has_content' => false]);

register_tag('tooltip', function($attrs, $content) {
    return '<span class="forge-tooltip" data-tooltip="' . esc($attrs['text'] ?? '') . '" style="cursor:help;border-bottom:1px dashed #6366f1;">' . $content . '</span>';
}, ['has_content' => true]);

register_tag('spacer', function($attrs) {
    return '<div style="height:' . esc($attrs['height'] ?? '2rem') . ';"></div>';
}, ['has_content' => false]);

register_tag('year', function() { return date('Y'); }, ['has_content' => false]);
register_tag('sitename', function() { return esc(getOption('site_title', 'VoidForge CMS')); }, ['has_content' => false]);

// =========================================================================
// EMBED SHORTCODES
// =========================================================================
register_tag('youtube', function($attrs) {
    $id = $attrs['id'] ?? '';
    if (empty($id) && !empty($attrs['url'])) {
        preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $attrs['url'], $m);
        $id = $m[1] ?? '';
    }
    if (empty($id)) return '<!-- YouTube: Invalid ID -->';
    return '<div style="position:relative;padding-bottom:56.25%;height:0;margin:1.5rem 0;border-radius:12px;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,0.2);"><iframe src="https://www.youtube.com/embed/' . esc($id) . '" style="position:absolute;inset:0;width:100%;height:100%;border:0;" allowfullscreen></iframe></div>';
}, ['has_content' => false]);

register_tag('vimeo', function($attrs) {
    $id = $attrs['id'] ?? '';
    if (empty($id)) return '<!-- Vimeo: Invalid ID -->';
    return '<div style="position:relative;padding-bottom:56.25%;height:0;margin:1.5rem 0;border-radius:12px;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,0.2);"><iframe src="https://player.vimeo.com/video/' . esc($id) . '" style="position:absolute;inset:0;width:100%;height:100%;border:0;" allowfullscreen></iframe></div>';
}, ['has_content' => false]);

register_tag('map', function($attrs) {
    $lat = $attrs['lat'] ?? '40.7128';
    $lng = $attrs['lng'] ?? '-74.0060';
    $zoom = $attrs['zoom'] ?? '14';
    return '<div style="margin:1.5rem 0;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.1);"><iframe width="100%" height="400" frameborder="0" style="border:0" src="https://www.openstreetmap.org/export/embed.html?bbox=' . ($lng-0.01) . '%2C' . ($lat-0.01) . '%2C' . ($lng+0.01) . '%2C' . ($lat+0.01) . '&layer=mapnik" allowfullscreen></iframe></div>';
}, ['has_content' => false]);

// =========================================================================
// COLUMNS SHORTCODE
// =========================================================================
register_tag('columns', function($attrs, $content) {
    $gap = $attrs['gap'] ?? '1.5rem';
    preg_match_all('/\{col\}/', $content, $m);
    $cols = count($m[0]) ?: 2;
    $content = preg_replace('/\{col\}/', '<div style="min-width:0;">', $content);
    $content = preg_replace('/\{\/col\}/', '</div>', $content);
    return '<div style="display:grid;grid-template-columns:repeat(' . $cols . ',1fr);gap:' . esc($gap) . ';margin:1rem 0;">' . $content . '</div>';
}, ['has_content' => true]);

// =========================================================================
// MODAL SHORTCODE
// =========================================================================
register_tag('modal', function($attrs, $content) {
    $id = $attrs['id'] ?? 'forge-modal-' . uniqid();
    $title = $attrs['title'] ?? '';
    
    $html = '<div id="' . esc($id) . '" class="forge-modal" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;">';
    $html .= '<div class="forge-modal-overlay" style="position:absolute;inset:0;background:rgba(0,0,0,0.5);"></div>';
    $html .= '<div class="forge-modal-content" style="position:relative;background:#fff;border-radius:16px;box-shadow:0 25px 50px rgba(0,0,0,0.25);max-width:500px;width:90%;max-height:90vh;overflow:auto;">';
    
    $html .= '<div style="display:flex;align-items:center;justify-content:space-between;padding:1.25rem 1.5rem;border-bottom:1px solid #e2e8f0;">';
    $html .= '<h3 style="margin:0;font-size:1.125rem;color:#1e293b;">' . esc($title) . '</h3>';
    $html .= '<button class="forge-modal-close" style="background:none;border:none;cursor:pointer;padding:4px;color:#64748b;">' . getForgeIcon('x', 20) . '</button>';
    $html .= '</div>';
    
    $html .= '<div style="padding:1.5rem;">' . $content . '</div>';
    $html .= '</div></div>';
    
    return $html;
}, ['has_content' => true]);

register_tag('modal-trigger', function($attrs, $content) {
    $target = $attrs['target'] ?? '';
    return '<span data-modal-open="' . esc($target) . '" style="cursor:pointer;">' . $content . '</span>';
}, ['has_content' => true]);

// =========================================================================
// STATS / COUNTER SHORTCODE
// =========================================================================
register_tag('stats', function($attrs, $content) {
    preg_match_all('/\{stat\s+([^}]*)\}/', $content, $matches, PREG_SET_ORDER);
    
    $html = '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:2rem;padding:2rem;background:linear-gradient(135deg,#f8fafc,#f1f5f9);border-radius:16px;margin:1.5rem 0;text-align:center;">';
    
    foreach ($matches as $m) {
        preg_match_all('/(\w+)="([^"]*)"/', $m[1], $am, PREG_SET_ORDER);
        $a = [];
        foreach ($am as $at) $a[$at[1]] = $at[2];
        
        $value = $a['value'] ?? '0';
        $label = $a['label'] ?? '';
        $prefix = $a['prefix'] ?? '';
        $suffix = $a['suffix'] ?? '';
        
        $html .= '<div>';
        $html .= '<div style="font-size:2.5rem;font-weight:800;color:#6366f1;line-height:1.2;">' . esc($prefix) . esc($value) . esc($suffix) . '</div>';
        $html .= '<div style="font-size:0.875rem;color:#64748b;margin-top:0.5rem;text-transform:uppercase;letter-spacing:0.05em;">' . esc($label) . '</div>';
        $html .= '</div>';
    }
    
    return $html . '</div>';
}, ['has_content' => true]);

register_tag('stat', function($a, $c) { return ''; }, ['has_content' => false]);

// =========================================================================
// PRICING TABLE SHORTCODE
// =========================================================================
register_tag('pricing', function($attrs, $content) {
    preg_match_all('/\{plan\s+([^}]*)\}(.*?)\{\/plan\}/s', $content, $matches, PREG_SET_ORDER);
    
    $html = '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.5rem;margin:2rem 0;">';
    
    foreach ($matches as $m) {
        preg_match_all('/(\w+)="([^"]*)"/', $m[1], $am, PREG_SET_ORDER);
        $a = [];
        foreach ($am as $at) $a[$at[1]] = $at[2];
        
        $name = $a['name'] ?? 'Plan';
        $price = $a['price'] ?? '0';
        $period = $a['period'] ?? '/mo';
        $featured = ($a['featured'] ?? '') === 'true';
        $cta = $a['cta'] ?? 'Get Started';
        $href = $a['href'] ?? '#';
        
        $borderStyle = $featured ? 'border:2px solid #6366f1;' : 'border:1px solid #e2e8f0;';
        $headerBg = $featured ? 'background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;' : 'background:#f8fafc;color:#1e293b;';
        
        $html .= '<div style="background:#fff;border-radius:16px;overflow:hidden;' . $borderStyle . '">';
        
        // Header
        $html .= '<div style="padding:1.5rem;text-align:center;' . $headerBg . '">';
        $html .= '<div style="font-size:1.125rem;font-weight:600;">' . esc($name) . '</div>';
        $html .= '<div style="font-size:2.5rem;font-weight:800;margin:0.5rem 0;">$' . esc($price) . '<span style="font-size:1rem;font-weight:400;">' . esc($period) . '</span></div>';
        $html .= '</div>';
        
        // Features
        $html .= '<div style="padding:1.5rem;">' . trim($m[2]) . '</div>';
        
        // CTA
        $btnStyle = $featured ? 'background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;' : 'background:#f1f5f9;color:#374151;';
        $html .= '<div style="padding:0 1.5rem 1.5rem;">';
        $html .= '<a href="' . esc($href) . '" style="display:block;text-align:center;padding:0.875rem;border-radius:8px;font-weight:600;text-decoration:none;' . $btnStyle . '">' . esc($cta) . '</a>';
        $html .= '</div></div>';
    }
    
    return $html . '</div>';
}, ['has_content' => true]);

register_tag('plan', function($a, $c) { return $c; }, ['has_content' => true]);

// =========================================================================
// FEATURE LIST SHORTCODE
// =========================================================================
register_tag('features', function($attrs, $content) {
    preg_match_all('/\{feature(?:\s+([^}]*))?\}(.*?)\{\/feature\}/s', $content, $matches, PREG_SET_ORDER);
    
    $html = '<ul style="list-style:none;padding:0;margin:1rem 0;">';
    
    foreach ($matches as $m) {
        preg_match('/icon="([^"]*)"/', $m[1] ?? '', $iconMatch);
        $icon = $iconMatch[1] ?? 'check';
        
        $html .= '<li style="display:flex;align-items:flex-start;gap:0.75rem;padding:0.625rem 0;">';
        $html .= '<span style="flex-shrink:0;color:#10b981;">' . getForgeIcon($icon, 20) . '</span>';
        $html .= '<span style="color:#374151;">' . trim($m[2]) . '</span>';
        $html .= '</li>';
    }
    
    return $html . '</ul>';
}, ['has_content' => true]);

register_tag('feature', function($a, $c) { return $c; }, ['has_content' => true]);

// =========================================================================
// TESTIMONIAL SHORTCODE
// =========================================================================
register_tag('testimonial', function($attrs, $content) {
    $author = $attrs['author'] ?? '';
    $role = $attrs['role'] ?? '';
    $avatar = $attrs['avatar'] ?? '';
    $rating = (int)($attrs['rating'] ?? 5);
    
    $html = '<div style="background:#fff;border-radius:16px;padding:2rem;box-shadow:0 4px 20px rgba(0,0,0,0.08);margin:1.5rem 0;">';
    
    // Stars
    $html .= '<div style="display:flex;gap:4px;margin-bottom:1rem;">';
    for ($i = 0; $i < 5; $i++) {
        $color = $i < $rating ? '#f59e0b' : '#e5e7eb';
        $html .= '<svg width="20" height="20" viewBox="0 0 24 24" fill="' . $color . '"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
    }
    $html .= '</div>';
    
    // Quote
    $html .= '<p style="font-size:1.0625rem;color:#374151;line-height:1.7;margin:0 0 1.5rem;font-style:italic;">"' . $content . '"</p>';
    
    // Author
    $html .= '<div style="display:flex;align-items:center;gap:1rem;">';
    if ($avatar) {
        $html .= '<img src="' . esc($avatar) . '" style="width:48px;height:48px;border-radius:50%;object-fit:cover;">';
    } else {
        $html .= '<div style="width:48px;height:48px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:600;">' . strtoupper(substr($author, 0, 1)) . '</div>';
    }
    $html .= '<div>';
    $html .= '<div style="font-weight:600;color:#1e293b;">' . esc($author) . '</div>';
    if ($role) $html .= '<div style="font-size:0.875rem;color:#64748b;">' . esc($role) . '</div>';
    $html .= '</div></div></div>';
    
    return $html;
}, ['has_content' => true]);

// =========================================================================
// SECURITY SALT DISPLAY SHORTCODE
// =========================================================================
register_tag('salts', function($attrs) {
    $format = $attrs['format'] ?? 'php';
    
    $html = '<div style="margin:1.5rem 0;">';
    $html .= '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">';
    $html .= '<h3 style="margin:0;font-size:1.125rem;color:#1e293b;">' . getForgeIcon('key', 20, '#6366f1') . ' Security Keys & Salts</h3>';
    $html .= '<button onclick="location.reload()" style="display:flex;align-items:center;gap:0.5rem;padding:0.5rem 1rem;background:#f1f5f9;border:none;border-radius:8px;cursor:pointer;font-weight:500;color:#475569;">' . getForgeIcon('refresh', 16) . ' Regenerate</button>';
    $html .= '</div>';
    $html .= '<div style="background:#0f172a;border-radius:12px;padding:1.25rem;font-family:monospace;font-size:0.8125rem;color:#e2e8f0;overflow-x:auto;line-height:1.8;">';
    $html .= '<pre style="margin:0;white-space:pre-wrap;word-break:break-all;">' . esc(forge_generate_salts()) . '</pre>';
    $html .= '</div>';
    $html .= '<p style="margin-top:0.75rem;font-size:0.875rem;color:#64748b;">API Endpoints: <code style="background:#f1f5f9;padding:2px 6px;border-radius:4px;">/api/salts</code> (PHP) • <code style="background:#f1f5f9;padding:2px 6px;border-radius:4px;">/api/salts/json</code> (JSON)</p>';
    $html .= '</div>';
    
    return $html;
}, ['has_content' => false]);

// =========================================================================
// PLUGIN ACTIVATION - CREATE COMPREHENSIVE DEMO PAGE
// =========================================================================
add_action('plugin_activate_voidforge-toolkit', function() {
    $existingPage = Post::findBySlug('toolkit-demo', 'page');
    if ($existingPage) {
        Post::delete($existingPage['id']);
    }
    
    $content = <<<'HTML'
{lead}Welcome to the <strong>VoidForge Toolkit</strong> — a comprehensive collection of shortcodes, components, and developer tools to build beautiful, interactive pages.{/lead}

{alert type="info" title="Pro Tip" icon="true"}Refresh this page anytime to see newly generated security salts. All shortcodes on this page are fully functional and can be copied for your own use.{/alert}

{divider style="gradient"}

<h2>{icon name="key"} Security Keys & Salts</h2>

<p>Generate cryptographically secure keys and salts for your application, just like WordPress's secret-key service.</p>

{salts}

{divider style="gradient"}

<h2>{icon name="mouse-pointer"} Buttons</h2>

<h4>Button Styles</h4>
<p>
{button href="#" style="primary"}Primary{/button}
{button href="#" style="secondary"}Secondary{/button}
{button href="#" style="success"}Success{/button}
{button href="#" style="danger"}Danger{/button}
{button href="#" style="warning"}Warning{/button}
{button href="#" style="outline"}Outline{/button}
{button href="#" style="ghost"}Ghost{/button}
</p>

<h4>Button Sizes</h4>
<p>
{button href="#" style="primary" size="sm"}Small{/button}
{button href="#" style="primary" size="md"}Medium{/button}
{button href="#" style="primary" size="lg"}Large{/button}
</p>

<h4>Buttons with Icons</h4>
<p>
{button href="#" style="primary" icon="download"}Download{/button}
{button href="#" style="success" icon="check"}Confirm{/button}
{button href="#" style="danger" icon="x"}Cancel{/button}
{button href="#" style="outline" icon="external-link"}External Link{/button}
</p>

{code lang="html" title="Button Syntax"}{button href="/contact" style="primary" size="lg" icon="mail"}Contact Us{/button}{/code}

{divider style="gradient"}

<h2>{icon name="bell"} Alerts & Notices</h2>

{alert type="info" title="Information" icon="true"}This is an informational alert for helpful tips and notes. Perfect for documentation.{/alert}

{alert type="success" title="Success!" icon="true"}Great job! Your action was completed successfully. Everything is working perfectly.{/alert}

{alert type="warning" title="Warning" icon="true"}Be careful! This action might have consequences. Please review before proceeding.{/alert}

{alert type="danger" title="Error" icon="true"}Something went wrong. Please check your input and try again.{/alert}

{alert type="info" dismissible="true"}This alert can be dismissed by clicking the X button on the right →{/alert}

{divider style="gradient"}

<h2>{icon name="layout"} Tabs</h2>

{tabs}
{tab title="Overview"}
<h4>Welcome to Tabs</h4>
<p>Tabs help organize content into logical sections that users can switch between without leaving the page. They're perfect for documentation, product features, or any content that can be categorized.</p>
{button href="#" style="primary" icon="arrow-right"}Learn More{/button}
{/tab}
{tab title="Features"}
<h4>Key Features</h4>
{features}
{feature}Smooth transitions between tabs{/feature}
{feature}Accessible keyboard navigation{/feature}
{feature}Responsive design for mobile devices{/feature}
{feature}Support for any HTML content inside tabs{/feature}
{feature}Unlimited number of tabs{/feature}
{/features}
{/tab}
{tab title="Usage"}
{code lang="html" title="Tab Syntax"}{tabs}
{tab title="First Tab"}
Your content here...
{/tab}
{tab title="Second Tab"}
More content...
{/tab}
{/tabs}{/code}
{/tab}
{/tabs}

{divider style="gradient"}

<h2>{icon name="chevron-down"} Accordions</h2>

{accordion}
{accordion-item title="What is VoidForge CMS?" open="true"}
VoidForge CMS is a modern, lightweight content management system built with pure PHP. It features a clean admin interface, powerful plugin system, custom post types, custom fields, and a flexible theming engine. No frameworks, no dependencies — just fast, reliable content management.
{/accordion-item}
{accordion-item title="How do I install plugins?"}
Navigate to the <strong>Plugins</strong> page in your admin panel. You can activate built-in plugins with a single click or upload new plugin ZIP files. Plugins are automatically detected from the <code>/plugins</code> directory.
{/accordion-item}
{accordion-item title="Can I customize the theme?"}
Absolutely! Themes are located in the <code>/themes</code> directory. You can modify existing themes or create your own using standard PHP templates. The system supports template hierarchy (like WordPress), custom CSS injection, and real-time preview.
{/accordion-item}
{accordion-item title="Is VoidForge CMS free?"}
Yes! VoidForge CMS is 100% free and open-source under the MIT license. You can use it for personal projects, client work, or commercial applications without any restrictions.
{/accordion-item}
{/accordion}

{divider style="gradient"}

<h2>{icon name="loader"} Progress Bars</h2>

{progress value="85" label="Project Completion" color="primary"}
{progress value="92" label="Customer Satisfaction" color="success"}
{progress value="67" label="Tasks Completed" color="warning"}
{progress value="45" label="Bug Fixes Remaining" color="danger"}

<h4>Animated & Striped</h4>
{progress value="75" label="Loading..." color="primary" animated="true" striped="true"}
{progress value="60" label="Processing..." color="success" animated="true" striped="true"}

{divider style="gradient"}

<h2>{icon name="trending-up"} Statistics</h2>

{stats}
{stat value="10K+" label="Downloads" prefix=""}
{stat value="99.9" label="Uptime" suffix="%"}
{stat value="500" label="Happy Users" prefix="+"}
{stat value="24/7" label="Support"}
{/stats}

{divider style="gradient"}

<h2>{icon name="clock"} Timeline</h2>

{timeline}
{timeline-item date="January 2024" title="Project Inception" icon="flag"}
Initial planning and research phase began. We defined the project scope, gathered requirements, and assembled the core team.
{/timeline-item}
{timeline-item date="March 2024" title="Development Kickoff" icon="code"}
Core development started. Built the foundation architecture, admin interface, and plugin system from the ground up.
{/timeline-item}
{timeline-item date="June 2024" title="Beta Release" icon="package"}
Released the first public beta version. Gathered community feedback, squashed bugs, and refined the user experience.
{/timeline-item}
{timeline-item date="September 2024" title="Version 1.0 Launch" icon="check-circle" status="current"}
Official stable release with comprehensive documentation, full feature set, and production-ready codebase.
{/timeline-item}
{/timeline}

{divider style="gradient"}

<h2>{icon name="grid"} Cards & Grids</h2>

{grid cols="3" gap="1.5rem"}
{card title="Getting Started" icon="book"}
Learn the basics of VoidForge CMS and create your first page in minutes. Our quick-start guide walks you through installation and setup.
{button href="#" style="outline" size="sm"}Read Guide{/button}
{/card}
{card title="Customization" icon="palette"}
Discover how to customize themes, add plugins, and extend functionality. Make VoidForge CMS truly yours.
{button href="#" style="outline" size="sm"}Explore{/button}
{/card}
{card title="API Reference" icon="code"}
Complete documentation for developers. Learn about hooks, filters, shortcodes, and the plugin API.
{button href="#" style="outline" size="sm"}View Docs{/button}
{/card}
{/grid}

{divider style="gradient"}

<h2>{icon name="star"} Testimonials</h2>

{grid cols="2" gap="1.5rem"}
<div>
{testimonial author="Sarah Johnson" role="Web Developer" rating="5"}
VoidForge CMS is exactly what I've been looking for. Clean code, fast performance, and incredibly easy to extend. It's become my go-to CMS for client projects.
{/testimonial}
</div>
<div>
{testimonial author="Mike Chen" role="Agency Owner" rating="5"}
We've moved all our client sites to VoidForge CMS. The custom post types and fields feature is a game-changer. Our development time has been cut in half!
{/testimonial}
</div>
{/grid}

{divider style="gradient"}

<h2>{icon name="tag"} Pricing Tables</h2>

{pricing}
{plan name="Starter" price="0" period="/forever" cta="Get Started" href="#"}
{features}
{feature}Unlimited pages & posts{/feature}
{feature}Basic themes{/feature}
{feature}Core plugins{/feature}
{feature}Community support{/feature}
{/features}
{/plan}
{plan name="Professional" price="29" period="/month" featured="true" cta="Start Free Trial" href="#"}
{features}
{feature}Everything in Starter{/feature}
{feature}Premium themes{/feature}
{feature}Priority support{/feature}
{feature}Custom fields builder{/feature}
{feature}Advanced analytics{/feature}
{/features}
{/plan}
{plan name="Enterprise" price="99" period="/month" cta="Contact Sales" href="#"}
{features}
{feature}Everything in Professional{/feature}
{feature}White-label option{/feature}
{feature}Dedicated support{/feature}
{feature}Custom development{/feature}
{feature}SLA guarantee{/feature}
{/features}
{/plan}
{/pricing}

{divider style="gradient"}

<h2>{icon name="type"} Typography</h2>

{lead}This is a lead paragraph with larger, more prominent text. Perfect for article introductions or important statements.{/lead}

<p>Regular paragraph with <strong>bold text</strong>, <em>italic text</em>, and <code>inline code</code>. You can also use {tooltip text="This appears on hover!"}tooltips{/tooltip} for additional context.</p>

<h4>Text Highlighting</h4>
<p>
{highlight color="yellow"}Yellow highlighted text for important information.{/highlight}
</p>
<p>
{highlight color="green"}Green highlight for success messages or positive notes.{/highlight}
</p>
<p>
{highlight color="blue"}Blue highlight for tips, notes, or informational content.{/highlight}
</p>
<p>
{highlight color="pink"}Pink highlight for special attention or emphasis.{/highlight}
</p>

<h4>Badges</h4>
<p>
{badge color="blue"}New{/badge}
{badge color="green"}Success{/badge}
{badge color="red"}Error{/badge}
{badge color="yellow"}Warning{/badge}
{badge color="purple"}Beta{/badge}
{badge color="gradient" pill="true"}Featured{/badge}
</p>

{divider style="gradient"}

<h2>{icon name="message-circle"} Blockquotes</h2>

<h4>Classic Style</h4>
{quote author="Albert Einstein"}
Imagination is more important than knowledge. Knowledge is limited. Imagination encircles the world.
{/quote}

<h4>Modern Style</h4>
{quote author="Steve Jobs" source="Stanford Commencement, 2005" style="modern"}
Your time is limited, don't waste it living someone else's life. Don't be trapped by dogma, which is living the result of other people's thinking.
{/quote}

{divider style="gradient"}

<h2>{icon name="code"} Code Blocks</h2>

{code lang="php" title="Custom Post Type Query"}
<?php
// Query products with custom fields
$products = Post::query([
    'post_type' => 'product',
    'status' => 'published',
    'limit' => 10,
    'orderby' => 'created_at',
    'order' => 'DESC'
]);

foreach ($products as $product) {
    $price = get_custom_field('price', $product['id']);
    $sku = get_custom_field('sku', $product['id']);
    
    echo "<div class='product'>";
    echo "<h3>" . esc($product['title']) . "</h3>";
    echo "<p class='price'>$" . esc($price) . "</p>";
    echo "</div>";
}
{/code}

{code lang="html" title="Shortcode Example"}
{grid cols="3" gap="1.5rem"}
    {card title="Feature 1" icon="star"}
        Description of feature one.
    {/card}
    {card title="Feature 2" icon="zap"}
        Description of feature two.
    {/card}
    {card title="Feature 3" icon="shield"}
        Description of feature three.
    {/card}
{/grid}
{/code}

{divider style="gradient"}

<h2>{icon name="layers"} Columns Layout</h2>

{columns gap="2rem"}
{col}
<h4>Left Column</h4>
<p>This is the left column content. Columns automatically divide the available space equally.</p>
{button href="#" style="primary" size="sm"}Action{/button}
{/col}
{col}
<h4>Right Column</h4>
<p>This is the right column content. You can put any HTML or shortcodes inside columns.</p>
{button href="#" style="outline" size="sm"}Learn More{/button}
{/col}
{/columns}

{divider style="gradient"}

<h2>{icon name="video"} Media Embeds</h2>

<p>Embed videos from YouTube or Vimeo with responsive containers:</p>

{code lang="html" title="Embed Syntax"}{youtube id="dQw4w9WgXcQ"}
{vimeo id="123456789"}
{map lat="40.7128" lng="-74.0060" zoom="14"}{/code}

{divider style="gradient"}

<h2>{icon name="list"} Complete Shortcode Reference</h2>

{grid cols="2" gap="1.5rem"}
{card title="Content" icon="file-text"}
<code>{button}</code> <code>{alert}</code> <code>{card}</code> <code>{quote}</code> <code>{code}</code> <code>{lead}</code> <code>{highlight}</code> <code>{badge}</code>
{/card}
{card title="Layout" icon="layout"}
<code>{tabs}</code> <code>{accordion}</code> <code>{grid}</code> <code>{columns}</code> <code>{spacer}</code> <code>{divider}</code>
{/card}
{card title="Data Display" icon="bar-chart"}
<code>{progress}</code> <code>{timeline}</code> <code>{stats}</code> <code>{pricing}</code> <code>{testimonial}</code> <code>{features}</code>
{/card}
{card title="Utilities" icon="settings"}
<code>{icon}</code> <code>{tooltip}</code> <code>{modal}</code> <code>{year}</code> <code>{sitename}</code> <code>{salts}</code>
{/card}
{card title="Media" icon="image"}
<code>{youtube}</code> <code>{vimeo}</code> <code>{map}</code>
{/card}
{card title="Security" icon="shield"}
<code>{salts}</code> — Generate secure keys<br>
<code>/api/salts</code> — PHP format<br>
<code>/api/salts/json</code> — JSON format
{/card}
{/grid}

{spacer height="2rem"}

{alert type="success" title="That's the Forge Toolkit!" icon="true"}
You've explored all the components available in the Forge Toolkit. Start using these shortcodes in your pages and posts to create beautiful, interactive content. All shortcodes are fully documented and customizable.
{/alert}
HTML;
    
    Post::create([
        'title' => 'Toolkit Demo',
        'slug' => 'toolkit-demo',
        'content' => $content,
        'excerpt' => 'Explore all the components, shortcodes, and developer tools available in the Forge Toolkit plugin.',
        'post_type' => 'page',
        'status' => 'published',
        'author_id' => 1
    ]);
});
