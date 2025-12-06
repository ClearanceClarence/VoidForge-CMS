<?php
/**
 * Forge CMS Configuration
 * 
 * This file will be automatically configured during installation.
 * Do not modify unless you know what you're doing.
 */

// Prevent direct access
defined('CMS_ROOT') or die('Direct access not allowed');

// Branding
define('CMS_NAME', 'Forge');
define('CMS_VERSION', '1.0.10');

// Database settings - configured during installation
define('DB_HOST', '');
define('DB_NAME', '');
define('DB_USER', '');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
define('DB_PREFIX', '');

// Site settings - configured during installation
define('SITE_URL', '');
define('ADMIN_URL', SITE_URL . '/admin');

// Paths
define('INCLUDES_PATH', CMS_ROOT . '/includes');
define('ADMIN_PATH', CMS_ROOT . '/admin');
define('THEMES_PATH', CMS_ROOT . '/themes');
define('UPLOADS_PATH', CMS_ROOT . '/uploads');
define('UPLOADS_URL', SITE_URL . '/uploads');
define('PLUGINS_PATH', CMS_ROOT . '/plugins');
define('PLUGINS_URL', SITE_URL . '/plugins');
define('THEMES_URL', SITE_URL . '/themes');

// Current theme
define('CURRENT_THEME', 'default');
define('THEME_URL', SITE_URL . '/themes/' . CURRENT_THEME);

// Session settings
define('SESSION_NAME', 'forge_session');
define('SESSION_LIFETIME', 86400); // 24 hours

// Security
define('HASH_COST', 12);
define('AUTH_SALT', '');
define('SECURE_AUTH_SALT', '');

// Timezone
date_default_timezone_set('UTC');

// Error reporting (disable in production)
error_reporting(0);
ini_set('display_errors', 0);
