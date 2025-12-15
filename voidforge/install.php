<?php
/**
 * VoidForge CMS Installer
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('CMS_VERSION', '0.2.0');
define('CMS_NAME', 'VoidForge CMS');
define('CMS_ROOT', __DIR__);

// Prevent re-installation - check if already configured
$isConfigured = false;
if (file_exists(CMS_ROOT . '/includes/config.php')) {
    $config = file_get_contents(CMS_ROOT . '/includes/config.php');
    // Check if DB_NAME has an actual value (not empty)
    if (preg_match("/define\s*\(\s*['\"]DB_NAME['\"]\s*,\s*['\"](.+)['\"]\s*\)/", $config, $matches)) {
        if (!empty($matches[1])) {
            $isConfigured = true;
        }
    }
}
if ($isConfigured) {
    header('Location: admin/');
    exit;
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';

// Check requirements
function checkRequirements() {
    $checks = array();
    $checks['php'] = array('name' => 'PHP 7.4+', 'current' => PHP_VERSION, 'pass' => version_compare(PHP_VERSION, '7.4.0', '>='));
    $checks['pdo'] = array('name' => 'PDO MySQL', 'current' => extension_loaded('pdo_mysql') ? 'Enabled' : 'Disabled', 'pass' => extension_loaded('pdo_mysql'));
    $checks['gd'] = array('name' => 'GD Library', 'current' => extension_loaded('gd') ? 'Enabled' : 'Disabled', 'pass' => extension_loaded('gd'), 'warning' => true);
    $checks['json'] = array('name' => 'JSON', 'current' => extension_loaded('json') ? 'Enabled' : 'Disabled', 'pass' => extension_loaded('json'));
    $checks['uploads'] = array('name' => 'Uploads Dir', 'current' => is_writable(CMS_ROOT . '/uploads') ? 'Writable' : 'Not Writable', 'pass' => is_writable(CMS_ROOT . '/uploads'));
    $checks['includes'] = array('name' => 'Config Dir', 'current' => is_writable(CMS_ROOT . '/includes') ? 'Writable' : 'Not Writable', 'pass' => is_writable(CMS_ROOT . '/includes'));
    return $checks;
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['check_requirements'])) {
        $step = 2;
    } elseif (isset($_POST['install'])) {
        $dbHost = trim($_POST['db_host']);
        $dbName = trim($_POST['db_name']);
        $dbUser = trim($_POST['db_user']);
        $dbPass = $_POST['db_pass'];
        $dbPrefix = trim($_POST['db_prefix']);
        $siteUrl = rtrim(trim($_POST['site_url']), '/');
        $siteTitle = trim($_POST['site_title']);
        $adminUser = trim($_POST['admin_user']);
        $adminEmail = trim($_POST['admin_email']);
        $adminPass = $_POST['admin_pass'];
        $adminPassConfirm = $_POST['admin_pass_confirm'];
        
        if (empty($dbName) || empty($dbUser)) {
            $error = 'Database name and username are required.';
        } elseif (empty($adminUser) || empty($adminEmail) || empty($adminPass)) {
            $error = 'All admin account fields are required.';
        } elseif (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($adminPass) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($adminPass !== $adminPassConfirm) {
            $error = 'Passwords do not match.';
        } else {
            try {
                $pdo = new PDO("mysql:host={$dbHost};charset=utf8mb4", $dbUser, $dbPass, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `{$dbName}`");
                
                // Create tables
                $pdo->exec("CREATE TABLE IF NOT EXISTS `{$dbPrefix}posts` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `title` VARCHAR(255) NOT NULL,
                    `slug` VARCHAR(255) NOT NULL,
                    `content` LONGTEXT,
                    `excerpt` TEXT,
                    `status` ENUM('draft','published','scheduled','trash') DEFAULT 'draft',
                    `post_type` VARCHAR(50) DEFAULT 'post',
                    `author_id` INT,
                    `parent_id` INT DEFAULT NULL,
                    `menu_order` INT DEFAULT 0,
                    `featured_image_id` INT DEFAULT NULL,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    `published_at` DATETIME,
                    `trashed_at` DATETIME DEFAULT NULL,
                    `scheduled_at` DATETIME DEFAULT NULL,
                    `comment_count` INT UNSIGNED NOT NULL DEFAULT 0,
                    UNIQUE KEY `slug` (`slug`),
                    KEY `status` (`status`),
                    KEY `post_type` (`post_type`),
                    KEY `author_id` (`author_id`),
                    KEY `parent_id` (`parent_id`),
                    KEY `trashed_at` (`trashed_at`),
                    KEY `scheduled_at` (`scheduled_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                
                $pdo->exec("CREATE TABLE IF NOT EXISTS `{$dbPrefix}postmeta` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `post_id` INT NOT NULL,
                    `meta_key` VARCHAR(255) NOT NULL,
                    `meta_value` LONGTEXT,
                    KEY `post_id` (`post_id`),
                    KEY `meta_key` (`meta_key`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                
                $pdo->exec("CREATE TABLE IF NOT EXISTS `{$dbPrefix}users` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `username` VARCHAR(50) NOT NULL,
                    `email` VARCHAR(100) NOT NULL,
                    `password` VARCHAR(255) NOT NULL,
                    `display_name` VARCHAR(100),
                    `role` ENUM('admin','editor','author','subscriber') DEFAULT 'subscriber',
                    `avatar` VARCHAR(255),
                    `bio` TEXT,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    `last_login` DATETIME,
                    UNIQUE KEY `username` (`username`),
                    UNIQUE KEY `email` (`email`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                
                $pdo->exec("CREATE TABLE IF NOT EXISTS `{$dbPrefix}media` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `filename` VARCHAR(255) NOT NULL,
                    `filepath` VARCHAR(500) NOT NULL,
                    `mime_type` VARCHAR(100),
                    `filesize` INT,
                    `width` INT,
                    `height` INT,
                    `alt_text` VARCHAR(255),
                    `title` VARCHAR(255) DEFAULT NULL,
                    `caption` TEXT,
                    `folder_id` INT UNSIGNED DEFAULT NULL,
                    `uploaded_by` INT,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    KEY `uploaded_by` (`uploaded_by`),
                    KEY `folder_id` (`folder_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                
                $pdo->exec("CREATE TABLE IF NOT EXISTS `{$dbPrefix}media_folders` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(255) NOT NULL,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    KEY `name` (`name`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                
                $pdo->exec("CREATE TABLE IF NOT EXISTS `{$dbPrefix}options` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `option_name` VARCHAR(100) NOT NULL,
                    `option_value` LONGTEXT,
                    UNIQUE KEY `option_name` (`option_name`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                
                $pdo->exec("CREATE TABLE IF NOT EXISTS `{$dbPrefix}custom_post_types` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `slug` VARCHAR(50) NOT NULL,
                    `config` LONGTEXT NOT NULL,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY `slug` (`slug`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                
                $pdo->exec("CREATE TABLE IF NOT EXISTS `{$dbPrefix}post_revisions` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `post_id` INT UNSIGNED NOT NULL,
                    `post_type` VARCHAR(50) NOT NULL,
                    `title` VARCHAR(255) NOT NULL,
                    `slug` VARCHAR(255) NOT NULL,
                    `content` LONGTEXT,
                    `excerpt` TEXT,
                    `meta_data` LONGTEXT,
                    `author_id` INT UNSIGNED NOT NULL,
                    `revision_number` INT UNSIGNED NOT NULL DEFAULT 1,
                    `created_at` DATETIME NOT NULL,
                    INDEX `idx_post_id` (`post_id`),
                    INDEX `idx_post_type` (`post_type`),
                    INDEX `idx_created_at` (`created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                
                $pdo->exec("CREATE TABLE IF NOT EXISTS `{$dbPrefix}menus` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(255) NOT NULL,
                    `slug` VARCHAR(255) NOT NULL,
                    `location` VARCHAR(50) DEFAULT NULL,
                    `created_at` DATETIME NOT NULL,
                    INDEX `idx_slug` (`slug`),
                    INDEX `idx_location` (`location`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                
                $pdo->exec("CREATE TABLE IF NOT EXISTS `{$dbPrefix}menu_items` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `menu_id` INT UNSIGNED NOT NULL,
                    `parent_id` INT UNSIGNED NOT NULL DEFAULT 0,
                    `title` VARCHAR(255) NOT NULL,
                    `type` VARCHAR(50) NOT NULL DEFAULT 'custom',
                    `object_id` INT UNSIGNED DEFAULT NULL,
                    `url` VARCHAR(500) DEFAULT NULL,
                    `target` VARCHAR(20) DEFAULT '_self',
                    `css_class` VARCHAR(255) DEFAULT NULL,
                    `position` INT UNSIGNED NOT NULL DEFAULT 0,
                    `created_at` DATETIME NOT NULL,
                    INDEX `idx_menu_id` (`menu_id`),
                    INDEX `idx_parent_id` (`parent_id`),
                    INDEX `idx_position` (`position`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                
                $pdo->exec("CREATE TABLE IF NOT EXISTS `{$dbPrefix}taxonomies` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(255) NOT NULL,
                    `slug` VARCHAR(100) NOT NULL,
                    `singular` VARCHAR(255) DEFAULT NULL,
                    `description` TEXT,
                    `hierarchical` TINYINT(1) NOT NULL DEFAULT 0,
                    `post_types` TEXT,
                    `created_at` DATETIME NOT NULL,
                    UNIQUE INDEX `idx_slug` (`slug`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                
                $pdo->exec("CREATE TABLE IF NOT EXISTS `{$dbPrefix}terms` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `taxonomy` VARCHAR(100) NOT NULL,
                    `name` VARCHAR(255) NOT NULL,
                    `slug` VARCHAR(255) NOT NULL,
                    `description` TEXT,
                    `parent_id` INT UNSIGNED NOT NULL DEFAULT 0,
                    `count` INT UNSIGNED NOT NULL DEFAULT 0,
                    `created_at` DATETIME NOT NULL,
                    INDEX `idx_taxonomy` (`taxonomy`),
                    INDEX `idx_slug` (`slug`),
                    INDEX `idx_parent` (`parent_id`),
                    UNIQUE INDEX `idx_taxonomy_slug` (`taxonomy`, `slug`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                
                $pdo->exec("CREATE TABLE IF NOT EXISTS `{$dbPrefix}term_relationships` (
                    `post_id` INT UNSIGNED NOT NULL,
                    `term_id` INT UNSIGNED NOT NULL,
                    PRIMARY KEY (`post_id`, `term_id`),
                    INDEX `idx_term_id` (`term_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                
                // Comments table
                $pdo->exec("CREATE TABLE IF NOT EXISTS `{$dbPrefix}comments` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `post_id` INT UNSIGNED NOT NULL,
                    `parent_id` INT UNSIGNED NOT NULL DEFAULT 0,
                    `user_id` INT UNSIGNED DEFAULT NULL,
                    `author_name` VARCHAR(255) NOT NULL DEFAULT '',
                    `author_email` VARCHAR(255) NOT NULL DEFAULT '',
                    `author_url` VARCHAR(500) DEFAULT '',
                    `author_ip` VARCHAR(45) DEFAULT '',
                    `content` TEXT NOT NULL,
                    `status` ENUM('pending','approved','spam','trash') DEFAULT 'pending',
                    `created_at` DATETIME NOT NULL,
                    INDEX `idx_post_id` (`post_id`),
                    INDEX `idx_parent_id` (`parent_id`),
                    INDEX `idx_user_id` (`user_id`),
                    INDEX `idx_status` (`status`),
                    INDEX `idx_created_at` (`created_at`),
                    INDEX `idx_post_status` (`post_id`, `status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                
                // Create admin user
                $hashedPass = password_hash($adminPass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO `{$dbPrefix}users` (username, email, password, display_name, role) VALUES (?, ?, ?, ?, 'admin')");
                $stmt->execute(array($adminUser, $adminEmail, $hashedPass, $adminUser));
                
                // Insert default options
                $options = array(
                    'site_title' => $siteTitle,
                    'site_tagline' => 'Another VoidForge CMS Site',
                    'site_url' => $siteUrl,
                    'admin_email' => $adminEmail,
                    'posts_per_page' => '10',
                    'date_format' => 'F j, Y',
                    'time_format' => 'g:i a',
                    'timezone' => 'UTC',
                    'active_theme' => 'flavor',
                    'cms_version' => CMS_VERSION,
                    'comments_enabled' => '1',
                    'comment_moderation' => 'manual',
                    'comment_post_types' => json_encode(['post']),
                    'comment_max_depth' => '3'
                );
                
                $stmt = $pdo->prepare("INSERT INTO `{$dbPrefix}options` (option_name, option_value) VALUES (?, ?)");
                foreach ($options as $name => $value) {
                    $stmt->execute(array($name, $value));
                }
                
                // Create welcome post
                $stmt = $pdo->prepare("INSERT INTO `{$dbPrefix}posts` (title, slug, content, status, post_type, author_id, published_at) VALUES (?, ?, ?, 'published', 'post', 1, NOW())");
                $stmt->execute(array('Welcome to VoidForge CMS', 'welcome-to-forge-cms', '<p>Congratulations! You have successfully installed VoidForge CMS. This is your first post. Edit or delete it to get started.</p>'));
                
                // Create Home page with landing content
                $homeContent = '<h2>About Us</h2>

<p>Welcome to our website! This is a sample page created during installation. You can edit this content or create new pages from the admin dashboard.</p>

<p>VoidForge CMS makes it easy to manage your content with a modern, intuitive interface.</p>';

                $stmt = $pdo->prepare("INSERT INTO `{$dbPrefix}posts` (title, slug, content, status, post_type, author_id, published_at) VALUES (?, ?, ?, 'published', 'page', 1, NOW())");
                $stmt->execute(array('About', 'about', $homeContent));
                
                // Note: homepage_id is NOT set - welcome.php demo page will show on frontpage
                // Users can set a homepage in Settings → Reading
                
                // Create config file
                $salt1 = bin2hex(random_bytes(32));
                $salt2 = bin2hex(random_bytes(32));
                
                $configLines = array();
                $configLines[] = '<' . '?php';
                $configLines[] = '// VoidForge CMS Configuration - Generated ' . date('Y-m-d H:i:s');
                $configLines[] = '';
                $configLines[] = "define('DB_HOST', " . var_export($dbHost, true) . ");";
                $configLines[] = "define('DB_NAME', " . var_export($dbName, true) . ");";
                $configLines[] = "define('DB_USER', " . var_export($dbUser, true) . ");";
                $configLines[] = "define('DB_PASS', " . var_export($dbPass, true) . ");";
                $configLines[] = "define('DB_PREFIX', " . var_export($dbPrefix, true) . ");";
                $configLines[] = "define('DB_CHARSET', 'utf8mb4');";
                $configLines[] = "";
                $configLines[] = "define('SITE_URL', " . var_export($siteUrl, true) . ");";
                $configLines[] = "define('CMS_VERSION', '0.2.0');";
                $configLines[] = "define('CMS_NAME', 'VoidForge');";
                $configLines[] = "";
                $configLines[] = "define('ADMIN_PATH', CMS_ROOT . '/admin');";
                $configLines[] = "define('ADMIN_URL', SITE_URL . '/admin');";
                $configLines[] = "define('INCLUDES_PATH', CMS_ROOT . '/includes');";
                $configLines[] = "define('THEMES_PATH', CMS_ROOT . '/themes');";
                $configLines[] = "define('THEMES_URL', SITE_URL . '/themes');";
                $configLines[] = "define('UPLOADS_PATH', CMS_ROOT . '/uploads');";
                $configLines[] = "define('UPLOADS_URL', SITE_URL . '/uploads');";
                $configLines[] = "define('PLUGINS_PATH', CMS_ROOT . '/plugins');";
                $configLines[] = "define('PLUGINS_URL', SITE_URL . '/plugins');";
                $configLines[] = "";
                $configLines[] = "define('CURRENT_THEME', 'flavor');";
                $configLines[] = "define('THEME_URL', SITE_URL . '/themes/' . CURRENT_THEME);";
                $configLines[] = "";
                $configLines[] = "define('SESSION_NAME', 'voidforge_session');";
                $configLines[] = "define('SESSION_LIFETIME', 86400);";
                $configLines[] = "define('HASH_COST', 12);";
                $configLines[] = "";
                $configLines[] = "define('AUTH_SALT', " . var_export($salt1, true) . ");";
                $configLines[] = "define('SECURE_AUTH_SALT', " . var_export($salt2, true) . ");";
                $configLines[] = "";
                $configLines[] = "// Debug mode - set to false in production";
                $configLines[] = "define('CMS_DEBUG', true);";
                $configLines[] = "";
                $configLines[] = "date_default_timezone_set('UTC');";
                $configLines[] = "";
                $configLines[] = "// Error display - set both to 0 in production";
                $configLines[] = "error_reporting(E_ALL);";
                $configLines[] = "ini_set('display_errors', 1);";
                
                $configContent = implode("\n", $configLines);
                
                if (file_put_contents(CMS_ROOT . '/includes/config.php', $configContent)) {
                    $step = 3;
                } else {
                    $error = 'Could not write configuration file.';
                }
                
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

$requirements = checkRequirements();
$allPassed = true;
foreach ($requirements as $req) {
    if (!$req['pass'] && empty($req['warning'])) {
        $allPassed = false;
    }
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$path = dirname($_SERVER['SCRIPT_NAME']);
$detectedUrl = $protocol . '://' . $host . ($path !== '/' ? $path : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install VoidForge CMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{--primary:#6366f1;--secondary:#8b5cf6;--success:#10b981;--warning:#f59e0b;--danger:#ef4444;--dark:#0f172a;--g50:#f8fafc;--g100:#f1f5f9;--g200:#e2e8f0;--g300:#cbd5e1;--g400:#94a3b8;--g500:#64748b;--g600:#475569;--g700:#334155;--g800:#1e293b;--g900:#0f172a}
        body{font-family:'Inter',system-ui,sans-serif;background:var(--dark);min-height:100vh;color:var(--g700);line-height:1.6}
        .installer{display:flex;min-height:100vh}
        .installer-brand{width:400px;background:linear-gradient(135deg,var(--dark) 0%,#1e1b4b 50%,#312e81 100%);padding:2.5rem;display:flex;flex-direction:column;position:relative;overflow:hidden}
        .installer-brand::before{content:'';position:absolute;top:-50%;left:-50%;width:200%;height:200%;background:radial-gradient(circle at 30% 70%,rgba(99,102,241,0.15) 0%,transparent 50%),radial-gradient(circle at 70% 30%,rgba(139,92,246,0.1) 0%,transparent 50%);animation:float 20s ease-in-out infinite}
        @keyframes float{0%,100%{transform:translate(0,0)}50%{transform:translate(2%,2%)}}
        .brand-content{position:relative;z-index:1;flex:1;display:flex;flex-direction:column}
        .brand-logo{display:flex;align-items:center;gap:0.75rem;margin-bottom:2.5rem}
        .brand-logo svg{width:44px;height:44px}
        .brand-logo span{font-size:1.375rem;font-weight:800;color:#fff}
        .brand-title{font-size:2.25rem;font-weight:800;color:#fff;line-height:1.2;margin-bottom:0.75rem}
        .brand-title em{font-style:normal;background:linear-gradient(135deg,var(--primary),var(--secondary));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
        .brand-subtitle{font-size:1rem;color:var(--g400);margin-bottom:2.5rem}
        .steps{margin-top:auto}
        .step-item{display:flex;align-items:center;gap:1rem;padding:0.875rem 0;position:relative}
        .step-item:not(:last-child)::after{content:'';position:absolute;left:17px;top:100%;width:2px;height:0.875rem;background:var(--g700)}
        .step-item.completed:not(:last-child)::after{background:var(--success)}
        .step-number{width:34px;height:34px;border-radius:50%;background:var(--g800);border:2px solid var(--g700);display:flex;align-items:center;justify-content:center;font-weight:600;font-size:0.8125rem;color:var(--g500);flex-shrink:0}
        .step-item.active .step-number{background:linear-gradient(135deg,var(--primary),var(--secondary));border-color:transparent;color:#fff;box-shadow:0 0 20px rgba(99,102,241,0.5)}
        .step-item.completed .step-number{background:var(--success);border-color:var(--success);color:#fff}
        .step-info h4{font-size:0.875rem;font-weight:600;color:var(--g500)}
        .step-item.active .step-info h4,.step-item.completed .step-info h4{color:#fff}
        .step-info p{font-size:0.75rem;color:var(--g600)}
        .installer-content{flex:1;background:var(--g50);padding:2.5rem;display:flex;align-items:center;justify-content:center}
        .installer-form{width:100%;max-width:500px}
        .form-header{margin-bottom:1.75rem}
        .form-header h2{font-size:1.5rem;font-weight:700;color:var(--g900);margin-bottom:0.375rem}
        .form-header p{color:var(--g500);font-size:0.9375rem}
        .requirements-grid{display:grid;gap:0.625rem;margin-bottom:1.75rem}
        .req-item{display:flex;align-items:center;justify-content:space-between;padding:0.875rem 1rem;background:#fff;border:1px solid var(--g200);border-radius:10px}
        .req-info{display:flex;align-items:center;gap:0.625rem}
        .req-icon{width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center}
        .req-icon.pass{background:rgba(16,185,129,0.1);color:var(--success)}
        .req-icon.fail{background:rgba(239,68,68,0.1);color:var(--danger)}
        .req-icon.warn{background:rgba(245,158,11,0.1);color:var(--warning)}
        .req-name{font-weight:500;font-size:0.875rem;color:var(--g800)}
        .req-value{font-size:0.75rem;color:var(--g500)}
        .req-badge{padding:0.25rem 0.625rem;border-radius:20px;font-size:0.6875rem;font-weight:600}
        .req-badge.pass{background:rgba(16,185,129,0.1);color:var(--success)}
        .req-badge.fail{background:rgba(239,68,68,0.1);color:var(--danger)}
        .req-badge.warn{background:rgba(245,158,11,0.1);color:var(--warning)}
        .form-section{margin-bottom:1.5rem}
        .form-section-title{font-size:0.75rem;font-weight:600;color:var(--g500);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:0.875rem;padding-bottom:0.5rem;border-bottom:1px solid var(--g200)}
        .form-grid{display:grid;gap:0.875rem}
        .form-grid.cols-2{grid-template-columns:repeat(2,1fr)}
        .form-group{display:flex;flex-direction:column;gap:0.375rem}
        .form-label{font-size:0.8125rem;font-weight:500;color:var(--g700)}
        .form-input{padding:0.625rem 0.875rem;border:1px solid var(--g300);border-radius:8px;font-size:0.875rem;font-family:inherit;background:#fff}
        .form-input:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(99,102,241,0.1)}
        .form-hint{font-size:0.75rem;color:var(--g500)}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:0.5rem;padding:0.75rem 1.25rem;border:none;border-radius:10px;font-size:0.875rem;font-weight:600;font-family:inherit;cursor:pointer;text-decoration:none}
        .btn-primary{background:linear-gradient(135deg,var(--primary),var(--secondary));color:#fff;box-shadow:0 4px 15px rgba(99,102,241,0.35)}
        .btn-primary:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(99,102,241,0.45)}
        .btn-primary:disabled{opacity:0.5;cursor:not-allowed;transform:none}
        .btn-block{width:100%}
        .alert{padding:0.875rem 1rem;border-radius:10px;margin-bottom:1.25rem;display:flex;align-items:flex-start;gap:0.625rem;font-size:0.875rem}
        .alert-error{background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);color:var(--danger)}
        .alert svg{flex-shrink:0;width:18px;height:18px;margin-top:1px}
        .success-content{text-align:center}
        .success-icon{width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,var(--success),#059669);display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;animation:pulse 2s ease-in-out infinite}
        @keyframes pulse{0%,100%{box-shadow:0 0 0 0 rgba(16,185,129,0.4)}50%{box-shadow:0 0 0 20px rgba(16,185,129,0)}}
        .success-icon svg{width:36px;height:36px;color:#fff}
        .success-title{font-size:1.5rem;font-weight:700;color:var(--g900);margin-bottom:0.375rem}
        .success-message{color:var(--g500);margin-bottom:1.75rem;font-size:0.9375rem}
        .success-actions{display:flex;gap:0.875rem;justify-content:center}
        .btn-outline{background:#fff;border:2px solid var(--g200);color:var(--g700)}
        .btn-outline:hover{border-color:var(--primary);color:var(--primary)}
        @media(max-width:900px){.installer-brand{width:320px;padding:2rem}.brand-title{font-size:1.875rem}}
        @media(max-width:768px){.installer{flex-direction:column}.installer-brand{width:100%;padding:1.5rem}.brand-title{font-size:1.5rem}.steps{display:flex;gap:0.75rem;overflow-x:auto;margin-top:1.5rem}.step-item{flex-direction:column;text-align:center;min-width:90px;padding:0.5rem 0}.step-item:not(:last-child)::after{display:none}.step-info p{display:none}.installer-content{padding:1.5rem}.form-grid.cols-2{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="installer">
    <div class="installer-brand">
        <div class="brand-content">
            <div class="brand-logo">
                <svg viewBox="0 0 48 48" fill="none"><rect width="48" height="48" rx="12" fill="url(#g)"/><path d="M14 16h20M14 24h14M14 32h18" stroke="#fff" stroke-width="3" stroke-linecap="round"/><defs><linearGradient id="g" x1="0" y1="0" x2="48" y2="48"><stop stop-color="#6366f1"/><stop offset="1" stop-color="#8b5cf6"/></linearGradient></defs></svg>
                <span>VoidForge CMS</span>
            </div>
            <h1 class="brand-title">Setup your <em>content platform</em></h1>
            <p class="brand-subtitle">A modern, lightweight CMS built for speed and simplicity.</p>
            <div class="steps">
                <div class="step-item <?php echo $step >= 1 ? ($step > 1 ? 'completed' : 'active') : ''; ?>">
                    <div class="step-number"><?php echo $step > 1 ? '&#10003;' : '1'; ?></div>
                    <div class="step-info"><h4>Requirements</h4><p>System check</p></div>
                </div>
                <div class="step-item <?php echo $step >= 2 ? ($step > 2 ? 'completed' : 'active') : ''; ?>">
                    <div class="step-number"><?php echo $step > 2 ? '&#10003;' : '2'; ?></div>
                    <div class="step-info"><h4>Configuration</h4><p>Database &amp; admin</p></div>
                </div>
                <div class="step-item <?php echo $step >= 3 ? 'active' : ''; ?>">
                    <div class="step-number">3</div>
                    <div class="step-info"><h4>Complete</h4><p>Ready to go</p></div>
                </div>
            </div>
        </div>
    </div>
    <div class="installer-content">
        <div class="installer-form">
<?php if ($step === 1): ?>
            <div class="form-header">
                <h2>System Requirements</h2>
                <p>Making sure your server is ready.</p>
            </div>
            <div class="requirements-grid">
<?php foreach ($requirements as $key => $req): ?>
                <div class="req-item">
                    <div class="req-info">
                        <div class="req-icon <?php echo $req['pass'] ? 'pass' : (!empty($req['warning']) ? 'warn' : 'fail'); ?>">
<?php if ($req['pass']): ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
<?php elseif (!empty($req['warning'])): ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
<?php else: ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
<?php endif; ?>
                        </div>
                        <div>
                            <div class="req-name"><?php echo htmlspecialchars($req['name']); ?></div>
                            <div class="req-value"><?php echo htmlspecialchars($req['current']); ?></div>
                        </div>
                    </div>
                    <span class="req-badge <?php echo $req['pass'] ? 'pass' : (!empty($req['warning']) ? 'warn' : 'fail'); ?>"><?php echo $req['pass'] ? 'Pass' : (!empty($req['warning']) ? 'Warning' : 'Required'); ?></span>
                </div>
<?php endforeach; ?>
            </div>
            <form method="post">
                <button type="submit" name="check_requirements" class="btn btn-primary btn-block" <?php echo !$allPassed ? 'disabled' : ''; ?>>
                    <?php echo $allPassed ? 'Continue' : 'Fix requirements'; ?>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </button>
            </form>
<?php elseif ($step === 2): ?>
            <div class="form-header">
                <h2>Configuration</h2>
                <p>Enter your database and admin details.</p>
            </div>
<?php if ($error): ?>
            <div class="alert alert-error">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
<?php endif; ?>
            <form method="post">
                <div class="form-section">
                    <div class="form-section-title">Database</div>
                    <div class="form-grid cols-2">
                        <div class="form-group"><label class="form-label">Host</label><input type="text" name="db_host" class="form-input" value="<?php echo htmlspecialchars(isset($_POST['db_host']) ? $_POST['db_host'] : 'localhost'); ?>"></div>
                        <div class="form-group"><label class="form-label">Database Name *</label><input type="text" name="db_name" class="form-input" value="<?php echo htmlspecialchars(isset($_POST['db_name']) ? $_POST['db_name'] : ''); ?>" required></div>
                        <div class="form-group"><label class="form-label">Username *</label><input type="text" name="db_user" class="form-input" value="<?php echo htmlspecialchars(isset($_POST['db_user']) ? $_POST['db_user'] : ''); ?>" required></div>
                        <div class="form-group"><label class="form-label">Password</label><input type="password" name="db_pass" class="form-input"></div>
                    </div>
                    <div class="form-group" style="margin-top:0.875rem"><label class="form-label">Table Prefix</label><input type="text" name="db_prefix" class="form-input" value="<?php echo htmlspecialchars(isset($_POST['db_prefix']) ? $_POST['db_prefix'] : 'forge_'); ?>"><span class="form-hint">For multiple installs</span></div>
                </div>
                <div class="form-section">
                    <div class="form-section-title">Site</div>
                    <div class="form-grid">
                        <div class="form-group"><label class="form-label">Site URL</label><input type="url" name="site_url" class="form-input" value="<?php echo htmlspecialchars(isset($_POST['site_url']) ? $_POST['site_url'] : $detectedUrl); ?>"></div>
                        <div class="form-group"><label class="form-label">Site Title</label><input type="text" name="site_title" class="form-input" value="<?php echo htmlspecialchars(isset($_POST['site_title']) ? $_POST['site_title'] : 'My Website'); ?>"></div>
                    </div>
                </div>
                <div class="form-section">
                    <div class="form-section-title">Admin Account</div>
                    <div class="form-grid cols-2">
                        <div class="form-group"><label class="form-label">Username *</label><input type="text" name="admin_user" class="form-input" value="<?php echo htmlspecialchars(isset($_POST['admin_user']) ? $_POST['admin_user'] : ''); ?>" required></div>
                        <div class="form-group"><label class="form-label">Email *</label><input type="email" name="admin_email" class="form-input" value="<?php echo htmlspecialchars(isset($_POST['admin_email']) ? $_POST['admin_email'] : ''); ?>" required></div>
                        <div class="form-group"><label class="form-label">Password *</label><input type="password" name="admin_pass" class="form-input" required><span class="form-hint">Min 8 characters</span></div>
                        <div class="form-group"><label class="form-label">Confirm Password *</label><input type="password" name="admin_pass_confirm" class="form-input" required></div>
                    </div>
                </div>
                <button type="submit" name="install" class="btn btn-primary btn-block">Install VoidForge CMS <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></button>
            </form>
<?php elseif ($step === 3): ?>
            <div class="success-content">
                <div class="success-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></div>
                <h2 class="success-title">Installation Complete!</h2>
                <p class="success-message">VoidForge CMS is ready. Start creating content!</p>
                <div class="success-actions">
                    <a href="<?php echo htmlspecialchars($detectedUrl); ?>" class="btn btn-outline"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg> View Site</a>
                    <a href="admin/" class="btn btn-primary"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg> Dashboard</a>
                </div>
            </div>
<?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
