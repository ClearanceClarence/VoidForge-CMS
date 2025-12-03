<?php
/**
 * Front-end Entry Point
 */

define('CMS_ROOT', __DIR__);
require_once CMS_ROOT . '/includes/config.php';
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

// Route to appropriate template
if (!empty($_GET['s'])) {
    // Search results
    $searchQuery = trim($_GET['s']);
    include THEMES_PATH . '/' . CURRENT_THEME . '/search.php';
} elseif (empty($path)) {
    // Homepage - show latest posts
    include THEMES_PATH . '/' . CURRENT_THEME . '/index.php';
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
