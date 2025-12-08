<?php
/**
 * Front-end Entry Point
 */

// Handle install.php requests that might be routed here by .htaccess
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
if (preg_match('/install\.php/', $requestUri)) {
    include __DIR__ . '/install.php';
    exit;
}

define('CMS_ROOT', __DIR__);
require_once CMS_ROOT . '/includes/config.php';

// Check if CMS is installed - redirect to install if not
if (!defined('DB_NAME') || DB_NAME === '' || !defined('DB_HOST') || DB_HOST === '') {
    header('Location: install.php');
    exit;
}

require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/user.php';
require_once CMS_ROOT . '/includes/post.php';
require_once CMS_ROOT . '/includes/media.php';
require_once CMS_ROOT . '/includes/plugin.php';

// Initialize plugins
Post::init();
Plugin::init();

// Get the base path from SITE_URL config
$siteUrlPath = parse_url(SITE_URL, PHP_URL_PATH);
$basePath = $siteUrlPath ? rtrim($siteUrlPath, '/') : '';

// Get the request URI and remove base path
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = $requestUri;

// Remove the base path prefix
if ($basePath && strpos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
}

$path = trim($path, '/');

// API Routes - handle before page routing
if (preg_match('#^api/(.+)$#', $path, $apiMatches)) {
    $apiPath = $apiMatches[1];
    
    // Built-in: Security Salts API
    if ($apiPath === 'salts' || $apiPath === 'salts/') {
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('X-VoidForge-CMS: Salt Generator v1.0');
        echo generateSecuritySalts();
        exit;
    }
    
    if ($apiPath === 'salts/json' || $apiPath === 'salts/json/') {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        echo json_encode([
            'generated' => date('c'),
            'salts' => generateSecuritySaltsArray()
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
    // Fire API hook for plugins to handle additional endpoints
    do_action('api_request', $apiPath);
    
    // If we get here, no handler found
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'API endpoint not found', 'path' => $apiPath]);
    exit;
}

// Get custom post types for routing
$customPostTypes = getOption('custom_post_types');
if (!is_array($customPostTypes)) {
    $customPostTypes = [];
}

// Route to appropriate template
if (!empty($_GET['s'])) {
    // Search results
    $searchQuery = trim($_GET['s']);
    include THEMES_PATH . '/' . CURRENT_THEME . '/search.php';
} elseif (empty($path)) {
    // Homepage - load selected page or show welcome
    $homepageId = getOption('homepage_id', 0);
    
    if ($homepageId > 0) {
        $post = Post::find($homepageId);
        if ($post && $post['status'] === 'published') {
            // Check for home.php template first, then page.php
            $homeTemplate = THEMES_PATH . '/' . CURRENT_THEME . '/home.php';
            if (file_exists($homeTemplate)) {
                include $homeTemplate;
            } else {
                include THEMES_PATH . '/' . CURRENT_THEME . '/page.php';
            }
        } else {
            // Homepage not found or not published - show 404
            http_response_code(404);
            include THEMES_PATH . '/' . CURRENT_THEME . '/404.php';
        }
    } else {
        // No homepage set - show welcome/setup page
        include THEMES_PATH . '/' . CURRENT_THEME . '/welcome.php';
    }
} elseif (preg_match('#^post/([^/]+)$#', $path, $matches)) {
    // Single post
    $slug = $matches[1];
    $post = Post::findBySlug($slug, 'post');
    
    if ($post && $post['status'] === 'published') {
        include THEMES_PATH . '/' . CURRENT_THEME . '/single.php';
    } else {
        http_response_code(404);
        include THEMES_PATH . '/' . CURRENT_THEME . '/404.php';
    }
} elseif (preg_match('#^([^/]+)/([^/]+)$#', $path, $matches)) {
    // Check if first segment is a custom post type
    $postType = $matches[1];
    $slug = $matches[2];
    
    if (isset($customPostTypes[$postType])) {
        // Custom post type single
        $post = Post::findBySlug($slug, $postType);
        
        if ($post && $post['status'] === 'published') {
            // Try post-type specific template first, then fall back to single.php
            $templateFile = THEMES_PATH . '/' . CURRENT_THEME . '/single-' . $postType . '.php';
            if (file_exists($templateFile)) {
                include $templateFile;
            } else {
                include THEMES_PATH . '/' . CURRENT_THEME . '/single.php';
            }
        } else {
            http_response_code(404);
            include THEMES_PATH . '/' . CURRENT_THEME . '/404.php';
        }
    } else {
        // Not a custom post type, try as page
        $post = Post::findBySlug($path, 'page');
        
        if ($post && $post['status'] === 'published') {
            include THEMES_PATH . '/' . CURRENT_THEME . '/page.php';
        } else {
            http_response_code(404);
            include THEMES_PATH . '/' . CURRENT_THEME . '/404.php';
        }
    }
} else {
    // Try to find a page with this slug
    $post = Post::findBySlug($path, 'page');
    
    if ($post && $post['status'] === 'published') {
        include THEMES_PATH . '/' . CURRENT_THEME . '/page.php';
    } else {
        http_response_code(404);
        include THEMES_PATH . '/' . CURRENT_THEME . '/404.php';
    }
}
