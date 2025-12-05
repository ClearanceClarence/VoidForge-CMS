<?php
/**
 * Forge CMS Installation Wizard
 * Light mode design matching login page
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('CMS_ROOT', __DIR__);
define('CMS_NAME', 'Forge');
define('CMS_VERSION', '1.0.7');

/**
 * Check if CMS is already installed
 */
function isInstalled(): bool {
    $configFile = __DIR__ . '/includes/config.php';
    
    if (!file_exists($configFile)) {
        return false;
    }
    
    $configContent = file_get_contents($configFile);
    
    if (strpos($configContent, "define('DB_HOST', '');") !== false ||
        strpos($configContent, "define('DB_NAME', '');") !== false) {
        return false;
    }
    
    try {
        require_once $configFile;
        
        if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') ||
            empty(DB_HOST) || empty(DB_NAME)) {
            return false;
        }
        
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        
        $prefix = defined('DB_PREFIX') ? DB_PREFIX : '';
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$prefix . 'users']);
        
        if ($stmt->rowCount() === 0) {
            return false;
        }
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}users WHERE role = 'admin'");
        $stmt->execute();
        
        if ($stmt->fetchColumn() == 0) {
            return false;
        }
        
        return true;
        
    } catch (Exception $e) {
        return false;
    }
}

if (isInstalled()) {
    header('Location: admin/');
    exit;
}

// Check requirements
$requirements = [
    'php' => [
        'name' => 'PHP 7.4+',
        'status' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'current' => PHP_VERSION,
    ],
    'pdo_mysql' => [
        'name' => 'PDO MySQL',
        'status' => extension_loaded('pdo_mysql'),
        'current' => extension_loaded('pdo_mysql') ? 'Installed' : 'Missing',
    ],
    'writable' => [
        'name' => 'Directory Writable',
        'status' => is_writable(__DIR__),
        'current' => is_writable(__DIR__) ? 'Yes' : 'No',
    ],
    'config' => [
        'name' => 'Config File',
        'status' => file_exists(__DIR__ . '/includes/config.php') && is_writable(__DIR__ . '/includes/config.php'),
        'current' => file_exists(__DIR__ . '/includes/config.php') ? (is_writable(__DIR__ . '/includes/config.php') ? 'Ready' : 'Not writable') : 'Missing',
    ]
];

$allPassed = true;
foreach ($requirements as $req) {
    if (!$req['status']) {
        $allPassed = false;
        break;
    }
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
if (!$allPassed && $step > 1) $step = 1;

$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 2) {
    $dbHost = trim($_POST['db_host'] ?? '');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = $_POST['db_pass'] ?? '';
    $dbPrefix = trim($_POST['db_prefix'] ?? 'forge_');
    
    $dbPrefix = preg_replace('/[^a-zA-Z0-9_]/', '', $dbPrefix);
    if (empty($dbPrefix)) $dbPrefix = 'forge_';
    if (substr($dbPrefix, -1) !== '_') $dbPrefix .= '_';
    
    $siteUrl = rtrim(trim($_POST['site_url'] ?? ''), '/');
    $siteTitle = trim($_POST['site_title'] ?? '');
    
    $adminUser = trim($_POST['admin_user'] ?? '');
    $adminEmail = trim($_POST['admin_email'] ?? '');
    $adminPass = $_POST['admin_pass'] ?? '';
    $adminPassConfirm = $_POST['admin_pass_confirm'] ?? '';

    if (empty($dbHost)) $errors['db_host'] = 'Database host is required';
    if (empty($dbName)) $errors['db_name'] = 'Database name is required';
    if (empty($dbUser)) $errors['db_user'] = 'Database user is required';
    if (empty($siteUrl)) $errors['site_url'] = 'Site URL is required';
    if (empty($siteTitle)) $errors['site_title'] = 'Site title is required';
    if (empty($adminUser)) $errors['admin_user'] = 'Username is required';
    if (empty($adminEmail)) $errors['admin_email'] = 'Email is required';
    if (!empty($adminEmail) && !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) $errors['admin_email'] = 'Invalid email address';
    if (empty($adminPass)) $errors['admin_pass'] = 'Password is required';
    if (strlen($adminPass) < 8) $errors['admin_pass'] = 'Password must be at least 8 characters';
    if ($adminPass !== $adminPassConfirm) $errors['admin_pass_confirm'] = 'Passwords do not match';

    if (empty($errors)) {
        try {
            $dsn = "mysql:host={$dbHost};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbName}`");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS {$dbPrefix}users (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) NOT NULL UNIQUE,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    display_name VARCHAR(100),
                    role ENUM('subscriber', 'author', 'editor', 'admin') NOT NULL DEFAULT 'subscriber',
                    last_login DATETIME,
                    created_at DATETIME NOT NULL,
                    INDEX idx_role (role)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS {$dbPrefix}posts (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    post_type VARCHAR(50) NOT NULL DEFAULT 'post',
                    title VARCHAR(255) NOT NULL,
                    slug VARCHAR(255) NOT NULL,
                    content LONGTEXT,
                    excerpt TEXT,
                    status ENUM('draft', 'published', 'trash') NOT NULL DEFAULT 'draft',
                    author_id INT UNSIGNED,
                    parent_id INT UNSIGNED,
                    menu_order INT NOT NULL DEFAULT 0,
                    featured_image_id INT UNSIGNED,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL,
                    published_at DATETIME,
                    INDEX idx_type_status (post_type, status),
                    INDEX idx_slug (slug),
                    UNIQUE KEY unique_slug_type (slug, post_type)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS {$dbPrefix}post_meta (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    post_id INT UNSIGNED NOT NULL,
                    meta_key VARCHAR(255) NOT NULL,
                    meta_value LONGTEXT,
                    UNIQUE KEY unique_post_meta (post_id, meta_key)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS {$dbPrefix}media (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    filename VARCHAR(255) NOT NULL,
                    filepath VARCHAR(500) NOT NULL,
                    mime_type VARCHAR(100) NOT NULL,
                    file_size INT UNSIGNED NOT NULL,
                    width INT UNSIGNED,
                    height INT UNSIGNED,
                    alt_text VARCHAR(255),
                    title VARCHAR(255),
                    folder_id INT UNSIGNED,
                    uploaded_by INT UNSIGNED,
                    created_at DATETIME NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS {$dbPrefix}media_folders (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    created_at DATETIME NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS {$dbPrefix}options (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    option_name VARCHAR(255) NOT NULL UNIQUE,
                    option_value LONGTEXT,
                    autoload TINYINT(1) NOT NULL DEFAULT 1
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS {$dbPrefix}custom_post_types (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    slug VARCHAR(50) NOT NULL UNIQUE,
                    config JSON NOT NULL,
                    created_at DATETIME NOT NULL,
                    INDEX idx_slug (slug)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $hashedPass = password_hash($adminPass, PASSWORD_DEFAULT, ['cost' => 12]);
            $stmt = $pdo->prepare("INSERT INTO {$dbPrefix}users (username, email, password, display_name, role, created_at) VALUES (?, ?, ?, ?, 'admin', NOW())");
            $stmt->execute([$adminUser, $adminEmail, $hashedPass, $adminUser]);

            $stmt = $pdo->prepare("INSERT INTO {$dbPrefix}options (option_name, option_value) VALUES (?, ?)");
            $stmt->execute(['site_title', $siteTitle]);
            $stmt->execute(['site_description', '']);
            $stmt->execute(['posts_per_page', '10']);
            $stmt->execute(['date_format', 'M j, Y']);
            $stmt->execute(['time_format', 'H:i']);
            $stmt->execute(['cms_version', CMS_VERSION]);
            $stmt->execute(['active_plugins', '[]']);

            $welcomeContent = '<h2>Welcome to ' . htmlspecialchars($siteTitle) . '</h2>
<p>Congratulations on setting up your new Forge CMS website!</p>
<h3>Getting Started</h3>
<ul>
<li><strong>Customize your site</strong> – Visit Settings to update your site options.</li>
<li><strong>Create content</strong> – Add posts and pages from the admin panel.</li>
<li><strong>Upload media</strong> – Use the Media Library to manage images and files.</li>
</ul>';

            $helloContent = '<p>Welcome to Forge CMS! This is your first blog post.</p>
<h2>Key Features</h2>
<ul>
<li><strong>Clean Admin Interface</strong> – Modern and intuitive dashboard.</li>
<li><strong>Posts &amp; Pages</strong> – Full content management with rich editing.</li>
<li><strong>Media Library</strong> – Easy file uploads and organization.</li>
<li><strong>Plugin System</strong> – Extend functionality with hooks and filters.</li>
</ul>';

            $stmt = $pdo->prepare("INSERT INTO {$dbPrefix}posts (post_type, title, slug, content, excerpt, status, author_id, created_at, updated_at, published_at) VALUES (?, ?, ?, ?, ?, 'published', 1, NOW(), NOW(), NOW())");
            $stmt->execute(['page', 'Welcome', 'welcome', $welcomeContent, 'Welcome to your new website.']);
            $stmt->execute(['post', 'Hello World', 'hello-world', $helloContent, 'Your first blog post on Forge CMS.']);

            $configContent = "<?php\n";
            $configContent .= "/**\n * Forge CMS Configuration\n * Generated: " . date('Y-m-d H:i:s') . "\n */\n\n";
            $configContent .= "defined('CMS_ROOT') or die('Direct access not allowed');\n\n";
            $configContent .= "define('CMS_NAME', 'Forge');\n";
            $configContent .= "define('CMS_VERSION', '" . CMS_VERSION . "');\n\n";
            $configContent .= "define('DB_HOST', " . var_export($dbHost, true) . ");\n";
            $configContent .= "define('DB_NAME', " . var_export($dbName, true) . ");\n";
            $configContent .= "define('DB_USER', " . var_export($dbUser, true) . ");\n";
            $configContent .= "define('DB_PASS', " . var_export($dbPass, true) . ");\n";
            $configContent .= "define('DB_CHARSET', 'utf8mb4');\n";
            $configContent .= "define('DB_PREFIX', " . var_export($dbPrefix, true) . ");\n\n";
            $configContent .= "define('SITE_URL', " . var_export($siteUrl, true) . ");\n";
            $configContent .= "define('ADMIN_URL', SITE_URL . '/admin');\n\n";
            $configContent .= "define('INCLUDES_PATH', CMS_ROOT . '/includes');\n";
            $configContent .= "define('ADMIN_PATH', CMS_ROOT . '/admin');\n";
            $configContent .= "define('THEMES_PATH', CMS_ROOT . '/themes');\n";
            $configContent .= "define('UPLOADS_PATH', CMS_ROOT . '/uploads');\n";
            $configContent .= "define('UPLOADS_URL', SITE_URL . '/uploads');\n";
            $configContent .= "define('PLUGINS_PATH', CMS_ROOT . '/plugins');\n\n";
            $configContent .= "define('CURRENT_THEME', 'default');\n";
            $configContent .= "define('THEME_URL', SITE_URL . '/themes/' . CURRENT_THEME);\n\n";
            $configContent .= "define('SESSION_NAME', 'forge_session');\n";
            $configContent .= "define('SESSION_LIFETIME', 86400);\n";
            $configContent .= "define('HASH_COST', 12);\n\n";
            $configContent .= "date_default_timezone_set('UTC');\n";

            file_put_contents(__DIR__ . '/includes/config.php', $configContent);

            if (!is_dir(__DIR__ . '/uploads')) {
                mkdir(__DIR__ . '/uploads', 0755, true);
            }

            $success = true;
            $step = 3;

        } catch (PDOException $e) {
            $errors['database'] = 'Database error: ' . $e->getMessage();
        } catch (Exception $e) {
            $errors['general'] = 'Installation error: ' . $e->getMessage();
        }
    }
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$path = dirname($_SERVER['SCRIPT_NAME']);
$defaultUrl = $protocol . '://' . $host . ($path !== '/' ? $path : '');

function esc($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install <?= CMS_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --accent: #a855f7;
            --success: #10b981;
            --danger: #ef4444;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            color: #0f172a;
            line-height: 1.6;
            position: relative;
            overflow-x: hidden;
        }
        
        .bg-decoration {
            position: fixed;
            inset: 0;
            z-index: 0;
            overflow: hidden;
            pointer-events: none;
        }
        
        .bg-decoration::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(ellipse at 0% 0%, rgba(99, 102, 241, 0.08) 0%, transparent 50%),
                radial-gradient(ellipse at 100% 100%, rgba(168, 85, 247, 0.08) 0%, transparent 50%);
        }
        
        .shape {
            position: absolute;
            border-radius: 50%;
            opacity: 0.4;
        }
        
        .shape-1 {
            width: 500px;
            height: 500px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(168, 85, 247, 0.05) 100%);
            top: -150px;
            right: -150px;
        }
        
        .shape-2 {
            width: 350px;
            height: 350px;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, rgba(99, 102, 241, 0.05) 100%);
            bottom: -100px;
            left: -100px;
        }
        
        .grid-pattern {
            position: fixed;
            inset: 0;
            background-image: 
                linear-gradient(rgba(99, 102, 241, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(99, 102, 241, 0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            z-index: 1;
            pointer-events: none;
        }
        
        .install-container {
            position: relative;
            z-index: 10;
            max-width: 580px;
            margin: 0 auto;
            padding: 3rem 1.5rem;
        }
        
        .install-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 32px rgba(99, 102, 241, 0.3);
        }
        
        .logo svg {
            width: 36px;
            height: 36px;
        }
        
        .install-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .install-header p {
            color: #64748b;
        }
        
        /* Steps */
        .steps {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }
        
        .step {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .step-num {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            font-weight: 600;
            background: #e2e8f0;
            color: #64748b;
            transition: all 0.2s;
        }
        
        .step.active .step-num {
            background: var(--primary);
            color: #fff;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }
        
        .step.completed .step-num {
            background: var(--success);
            color: #fff;
        }
        
        .step-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: #64748b;
        }
        
        .step.active .step-label {
            color: #0f172a;
        }
        
        .step-line {
            width: 40px;
            height: 2px;
            background: #e2e8f0;
        }
        
        /* Card */
        .card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 
                0 1px 3px rgba(0, 0, 0, 0.04),
                0 6px 16px rgba(0, 0, 0, 0.04),
                0 20px 40px rgba(0, 0, 0, 0.04);
        }
        
        .card-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .card-title svg {
            color: var(--primary);
        }
        
        /* Requirements */
        .requirements {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }
        
        .req-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.875rem 1rem;
            background: #f8fafc;
            border-radius: 10px;
        }
        
        .req-name {
            font-weight: 500;
        }
        
        .req-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .req-status.pass { color: var(--success); }
        .req-status.fail { color: var(--danger); }
        
        /* Form */
        .form-section {
            margin-bottom: 2rem;
        }
        
        .form-section-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.375rem;
            color: #0f172a;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 0.9375rem;
            font-family: inherit;
            color: #0f172a;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            outline: none;
            transition: all 0.2s;
        }
        
        .form-input:hover {
            border-color: #cbd5e1;
        }
        
        .form-input:focus {
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }
        
        .form-input::placeholder {
            color: #94a3b8;
        }
        
        .form-input.error {
            border-color: var(--danger);
        }
        
        .form-error {
            font-size: 0.8125rem;
            color: var(--danger);
            margin-top: 0.375rem;
        }
        
        .form-hint {
            font-size: 0.8125rem;
            color: #94a3b8;
            margin-top: 0.375rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.875rem 1.5rem;
            font-size: 0.9375rem;
            font-weight: 600;
            font-family: inherit;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            border: none;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
        }
        
        .btn-secondary {
            background: #fff;
            color: #475569;
            border: 2px solid #e2e8f0;
        }
        
        .btn-secondary:hover {
            border-color: var(--primary);
            color: var(--primary);
        }
        
        .btn svg {
            width: 18px;
            height: 18px;
        }
        
        .btn-group {
            display: flex;
            gap: 1rem;
            justify-content: space-between;
            margin-top: 1.5rem;
        }
        
        /* Alert */
        .alert {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
        
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }
        
        .alert svg {
            flex-shrink: 0;
            width: 20px;
            height: 20px;
        }
        
        /* Success */
        .success-content {
            text-align: center;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 32px rgba(16, 185, 129, 0.3);
        }
        
        .success-icon svg {
            width: 40px;
            height: 40px;
            color: #fff;
        }
        
        .success-content h2 {
            font-size: 1.5rem;
            margin-bottom: 0.75rem;
        }
        
        .success-content p {
            color: #64748b;
            margin-bottom: 2rem;
        }
        
        .success-links {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        
        /* Footer */
        .install-footer {
            text-align: center;
            margin-top: 2rem;
            color: #94a3b8;
            font-size: 0.875rem;
        }
        
        @media (max-width: 640px) {
            .form-row { grid-template-columns: 1fr; }
            .step-label { display: none; }
            .btn-group { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="bg-decoration">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
    </div>
    <div class="grid-pattern"></div>
    
    <div class="install-container">
        <div class="install-header">
            <div class="logo">
                <svg viewBox="0 0 48 48" fill="none">
                    <path d="M14 12h20v5h-13v6h10v5h-10v10h-7V12z" fill="white"/>
                </svg>
            </div>
            <h1>Install <?= CMS_NAME ?></h1>
            <p>Let's get your site up and running</p>
        </div>
        
        <div class="steps">
            <div class="step <?= $step >= 1 ? ($step > 1 ? 'completed' : 'active') : '' ?>">
                <span class="step-num"><?= $step > 1 ? '✓' : '1' ?></span>
                <span class="step-label">Requirements</span>
            </div>
            <div class="step-line"></div>
            <div class="step <?= $step >= 2 ? ($step > 2 ? 'completed' : 'active') : '' ?>">
                <span class="step-num"><?= $step > 2 ? '✓' : '2' ?></span>
                <span class="step-label">Configuration</span>
            </div>
            <div class="step-line"></div>
            <div class="step <?= $step >= 3 ? 'active' : '' ?>">
                <span class="step-num">3</span>
                <span class="step-label">Complete</span>
            </div>
        </div>
        
        <div class="card">
            <?php if ($step === 1): ?>
            <!-- Step 1: Requirements -->
            <h3 class="card-title">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                System Requirements
            </h3>
            
            <div class="requirements">
                <?php foreach ($requirements as $key => $req): ?>
                <div class="req-item">
                    <span class="req-name"><?= esc($req['name']) ?></span>
                    <span class="req-status <?= $req['status'] ? 'pass' : 'fail' ?>">
                        <?php if ($req['status']): ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <?= esc($req['current']) ?>
                        <?php else: ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                        <?= esc($req['current']) ?>
                        <?php endif; ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($allPassed): ?>
            <a href="?step=2" class="btn btn-primary" style="width: 100%;">
                Continue
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
            </a>
            <?php else: ?>
            <div class="alert alert-error">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <div>Please fix the requirements above before continuing.</div>
            </div>
            <?php endif; ?>
            
            <?php elseif ($step === 2): ?>
            <!-- Step 2: Configuration -->
            
            <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <div>
                    <?php if (isset($errors['database'])): ?>
                        <?= esc($errors['database']) ?>
                    <?php elseif (isset($errors['general'])): ?>
                        <?= esc($errors['general']) ?>
                    <?php else: ?>
                        Please fix the errors below.
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <form method="post">
                <div class="form-section">
                    <div class="form-section-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                            <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
                            <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                        </svg>
                        Database
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Host</label>
                            <input type="text" name="db_host" class="form-input <?= isset($errors['db_host']) ? 'error' : '' ?>" 
                                   value="<?= esc($_POST['db_host'] ?? 'localhost') ?>" placeholder="localhost">
                            <?php if (isset($errors['db_host'])): ?>
                            <div class="form-error"><?= esc($errors['db_host']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Database Name</label>
                            <input type="text" name="db_name" class="form-input <?= isset($errors['db_name']) ? 'error' : '' ?>" 
                                   value="<?= esc($_POST['db_name'] ?? '') ?>" placeholder="forge_cms">
                            <?php if (isset($errors['db_name'])): ?>
                            <div class="form-error"><?= esc($errors['db_name']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Username</label>
                            <input type="text" name="db_user" class="form-input <?= isset($errors['db_user']) ? 'error' : '' ?>" 
                                   value="<?= esc($_POST['db_user'] ?? 'root') ?>" placeholder="root">
                            <?php if (isset($errors['db_user'])): ?>
                            <div class="form-error"><?= esc($errors['db_user']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Password</label>
                            <input type="password" name="db_pass" class="form-input" 
                                   value="<?= esc($_POST['db_pass'] ?? '') ?>" placeholder="Optional">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Table Prefix</label>
                        <input type="text" name="db_prefix" class="form-input" 
                               value="<?= esc($_POST['db_prefix'] ?? 'forge_') ?>" placeholder="forge_">
                        <div class="form-hint">Useful for multiple installations in one database</div>
                    </div>
                </div>
                
                <div class="form-section">
                    <div class="form-section-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="2" y1="12" x2="22" y2="12"></line>
                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                        </svg>
                        Site
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Site URL</label>
                        <input type="url" name="site_url" class="form-input <?= isset($errors['site_url']) ? 'error' : '' ?>" 
                               value="<?= esc($_POST['site_url'] ?? $defaultUrl) ?>">
                        <?php if (isset($errors['site_url'])): ?>
                        <div class="form-error"><?= esc($errors['site_url']) ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Site Title</label>
                        <input type="text" name="site_title" class="form-input <?= isset($errors['site_title']) ? 'error' : '' ?>" 
                               value="<?= esc($_POST['site_title'] ?? 'My Website') ?>" placeholder="My Website">
                        <?php if (isset($errors['site_title'])): ?>
                        <div class="form-error"><?= esc($errors['site_title']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-section">
                    <div class="form-section-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        Admin Account
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Username</label>
                            <input type="text" name="admin_user" class="form-input <?= isset($errors['admin_user']) ? 'error' : '' ?>" 
                                   value="<?= esc($_POST['admin_user'] ?? '') ?>" placeholder="admin">
                            <?php if (isset($errors['admin_user'])): ?>
                            <div class="form-error"><?= esc($errors['admin_user']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="admin_email" class="form-input <?= isset($errors['admin_email']) ? 'error' : '' ?>" 
                                   value="<?= esc($_POST['admin_email'] ?? '') ?>" placeholder="admin@example.com">
                            <?php if (isset($errors['admin_email'])): ?>
                            <div class="form-error"><?= esc($errors['admin_email']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Password</label>
                            <input type="password" name="admin_pass" class="form-input <?= isset($errors['admin_pass']) ? 'error' : '' ?>" 
                                   placeholder="Min. 8 characters">
                            <?php if (isset($errors['admin_pass'])): ?>
                            <div class="form-error"><?= esc($errors['admin_pass']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="admin_pass_confirm" class="form-input <?= isset($errors['admin_pass_confirm']) ? 'error' : '' ?>" 
                                   placeholder="Repeat password">
                            <?php if (isset($errors['admin_pass_confirm'])): ?>
                            <div class="form-error"><?= esc($errors['admin_pass_confirm']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="btn-group">
                    <a href="?step=1" class="btn btn-secondary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="19" y1="12" x2="5" y2="12"></line>
                            <polyline points="12 19 5 12 12 5"></polyline>
                        </svg>
                        Back
                    </a>
                    <button type="submit" class="btn btn-primary">
                        Install <?= CMS_NAME ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </button>
                </div>
            </form>
            
            <?php elseif ($step === 3): ?>
            <!-- Step 3: Success -->
            <div class="success-content">
                <div class="success-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </div>
                <h2>Installation Complete!</h2>
                <p><?= CMS_NAME ?> has been installed successfully. You can now log in to your admin panel.</p>
                <div class="success-links">
                    <a href="admin/login.php" class="btn btn-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                            <polyline points="10 17 15 12 10 7"></polyline>
                            <line x1="15" y1="12" x2="3" y2="12"></line>
                        </svg>
                        Log In
                    </a>
                    <a href="index.php" class="btn btn-secondary">
                        View Site
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="install-footer">
            Powered by <?= CMS_NAME ?> v<?= CMS_VERSION ?>
        </div>
    </div>
</body>
</html>
