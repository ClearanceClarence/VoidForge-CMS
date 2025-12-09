<?php
/**
 * Front-end Entry Point - VoidForge CMS
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
require_once CMS_ROOT . '/includes/theme.php';

// Initialize
Post::init();
Plugin::init();
Theme::init();

// Load active theme functions
Theme::loadFunctions();

// Start session for user
User::startSession();

// Fire init action
Plugin::doAction('init');

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
    
    // REST API - check for registered routes (will exit if matched)
    Plugin::handleRestRequest($apiPath);
    
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

// Process scheduled tasks (cron)
Plugin::processCronJobs();

// Get custom post types for routing
$customPostTypes = getOption('custom_post_types');
if (!is_array($customPostTypes)) {
    $customPostTypes = [];
}

// Helper to include theme template
function loadTemplate(string $template, array $data = []): void
{
    $path = Theme::getTemplate($template);
    if ($path) {
        extract($data);
        include $path;
    } else {
        // Fallback to index
        $indexPath = Theme::getTemplate('index');
        if ($indexPath) {
            extract($data);
            include $indexPath;
        } else {
            echo '<h1>Theme Error</h1><p>No template found for: ' . esc($template) . '</p>';
        }
    }
}

// Route to appropriate template
if (!empty($_GET['s'])) {
    // Search results
    $searchQuery = trim($_GET['s']);
    loadTemplate('search', ['searchQuery' => $searchQuery]);
    
} elseif (empty($path)) {
    // Homepage
    $homepageId = getOption('homepage_id', 0);
    
    if ($homepageId > 0) {
        $post = Post::find($homepageId);
        if ($post && $post['status'] === 'published') {
            // Check for home.php template first, then page.php, then index.php
            if (Theme::hasTemplate('home')) {
                loadTemplate('home', ['post' => $post]);
            } elseif (Theme::hasTemplate('page')) {
                loadTemplate('page', ['post' => $post]);
            } else {
                loadTemplate('index', ['post' => $post]);
            }
        } else {
            http_response_code(404);
            loadTemplate('404');
        }
    } else {
        // No homepage set - show posts listing (index.php)
        loadTemplate('index');
    }
    
} elseif (preg_match('#^post/([^/]+)$#', $path, $matches)) {
    // Single post with /post/slug format
    $slug = $matches[1];
    $post = Post::findBySlug($slug, 'post');
    
    if ($post && $post['status'] === 'published') {
        loadTemplate('single', ['post' => $post]);
    } else {
        http_response_code(404);
        loadTemplate('404');
    }
    
} elseif (preg_match('#^([^/]+)/([^/]+)$#', $path, $matches)) {
    // Check if first segment is a custom post type
    $postType = $matches[1];
    $slug = $matches[2];
    
    if (isset($customPostTypes[$postType])) {
        // Custom post type single
        $post = Post::findBySlug($slug, $postType);
        
        if ($post && $post['status'] === 'published') {
            // Try post-type specific template first
            if (Theme::hasTemplate('single-' . $postType)) {
                loadTemplate('single-' . $postType, ['post' => $post, 'postType' => $postType]);
            } else {
                loadTemplate('single', ['post' => $post, 'postType' => $postType]);
            }
        } else {
            http_response_code(404);
            loadTemplate('404');
        }
    } else {
        // Not a custom post type, try as nested page slug
        $post = Post::findBySlug($path, 'page');
        
        if ($post && $post['status'] === 'published') {
            loadTemplate('page', ['post' => $post]);
        } else {
            http_response_code(404);
            loadTemplate('404');
        }
    }
    
} else {
    // Try to find a post with this slug first (for simple URLs)
    $post = Post::findBySlug($path, 'post');
    
    if ($post && $post['status'] === 'published') {
        loadTemplate('single', ['post' => $post]);
    } else {
        // Try as a page
        $post = Post::findBySlug($path, 'page');
        
        if ($post && $post['status'] === 'published') {
            loadTemplate('page', ['post' => $post]);
        } else {
            // Check custom post types with direct slug access
            foreach ($customPostTypes as $type => $config) {
                $post = Post::findBySlug($path, $type);
                if ($post && $post['status'] === 'published') {
                    if (Theme::hasTemplate('single-' . $type)) {
                        loadTemplate('single-' . $type, ['post' => $post, 'postType' => $type]);
                    } else {
                        loadTemplate('single', ['post' => $post, 'postType' => $type]);
                    }
                    exit;
                }
            }
            
            // Nothing found
            http_response_code(404);
            loadTemplate('404');
        }
    }
}
