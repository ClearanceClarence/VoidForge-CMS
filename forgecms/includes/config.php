<?php
/**
 * Forge CMS Configuration
 */

// Prevent direct access
defined('CMS_ROOT') or die('Direct access not allowed');

// Branding
define('CMS_NAME', 'Forge');
define('CMS_VERSION', '1.0.2');

// Database settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'forge_cms');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Site settings
define('SITE_URL', 'http://localhost/forge');
define('ADMIN_URL', SITE_URL . '/admin');

// Paths
define('INCLUDES_PATH', CMS_ROOT . '/includes');
define('ADMIN_PATH', CMS_ROOT . '/admin');
define('THEMES_PATH', CMS_ROOT . '/themes');
define('UPLOADS_PATH', CMS_ROOT . '/uploads');
define('UPLOADS_URL', SITE_URL . '/uploads');

// Current theme
define('CURRENT_THEME', 'default');
define('THEME_URL', SITE_URL . '/themes/' . CURRENT_THEME);

// Session settings
define('SESSION_NAME', 'forge_session');
define('SESSION_LIFETIME', 86400); // 24 hours

// Security
define('HASH_COST', 12);

// Timezone
date_default_timezone_set('Europe/Oslo');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
