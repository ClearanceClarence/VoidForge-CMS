<?php
/**
 * Plugin AJAX Handler - VoidForge CMS
 * Handles AJAX requests from plugins
 */

define('CMS_ROOT', dirname(__DIR__));
require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/user.php';
require_once CMS_ROOT . '/includes/post.php';
require_once CMS_ROOT . '/includes/plugin.php';

// Set JSON headers
header('Content-Type: application/json');

// Initialize
Post::init();
Plugin::init();
User::startSession();

// Get action
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (empty($action)) {
    Plugin::sendJsonError(['message' => 'No action specified'], 400);
}

// Check for registered handler
$handlers = Plugin::getAjaxHandlers();

if (isset($handlers[$action])) {
    $handler = $handlers[$action];
    
    // Check authentication if required
    if (!$handler['nopriv'] && !User::isLoggedIn()) {
        Plugin::sendJsonError(['message' => 'Authentication required'], 401);
    }
    
    // Verify CSRF for authenticated requests
    if (User::isLoggedIn()) {
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!verifyCsrf($token)) {
            Plugin::sendJsonError(['message' => 'Invalid security token'], 403);
        }
    }
    
    try {
        call_user_func($handler['callback']);
    } catch (\Throwable $e) {
        Plugin::sendJsonError(['message' => $e->getMessage()], 500);
    }
} else {
    // Fire action hook for custom handling
    Plugin::doAction('ajax_' . $action);
    Plugin::doAction('ajax_nopriv_' . $action);
    
    // If we get here, no handler responded
    Plugin::sendJsonError(['message' => 'Unknown action: ' . $action], 404);
}
