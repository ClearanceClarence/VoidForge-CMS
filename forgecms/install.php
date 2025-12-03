<?php
/**
 * Forge CMS Installation Wizard
 * Beautiful step-by-step installation process
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('CMS_ROOT', __DIR__);
define('CMS_NAME', 'Forge');
define('CMS_VERSION', '1.0.4');

// Check if already installed
if (file_exists(__DIR__ . '/.installed')) {
    header('Location: admin/');
    exit;
}

// Check requirements
$requirements = [
    'php' => [
        'name' => 'PHP 7.4+ (8.1+ recommended)',
        'status' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'current' => PHP_VERSION,
        'required' => '7.4.0'
    ],
    'pdo_mysql' => [
        'name' => 'PDO MySQL',
        'status' => extension_loaded('pdo_mysql'),
        'current' => extension_loaded('pdo_mysql') ? 'Installed' : 'Not installed',
        'required' => 'Required'
    ],
    'writable' => [
        'name' => 'Directory Writable',
        'status' => is_writable(__DIR__),
        'current' => is_writable(__DIR__) ? 'Writable' : 'Not writable',
        'required' => 'Required'
    ],
    'config' => [
        'name' => 'Config File',
        'status' => file_exists(__DIR__ . '/includes/config.php') && is_writable(__DIR__ . '/includes/config.php'),
        'current' => file_exists(__DIR__ . '/includes/config.php') ? (is_writable(__DIR__ . '/includes/config.php') ? 'Ready' : 'Not writable') : 'Missing',
        'required' => 'Writable'
    ]
];

$allPassed = true;
foreach ($requirements as $req) {
    if (!$req['status']) {
        $allPassed = false;
        break;
    }
}

// Current step
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
    
    // Sanitize prefix - only allow alphanumeric and underscore
    $dbPrefix = preg_replace('/[^a-zA-Z0-9_]/', '', $dbPrefix);
    if (empty($dbPrefix)) $dbPrefix = 'forge_';
    if (substr($dbPrefix, -1) !== '_') $dbPrefix .= '_';
    
    $siteUrl = rtrim(trim($_POST['site_url'] ?? ''), '/');
    $siteTitle = trim($_POST['site_title'] ?? '');
    
    $adminUser = trim($_POST['admin_user'] ?? '');
    $adminEmail = trim($_POST['admin_email'] ?? '');
    $adminPass = $_POST['admin_pass'] ?? '';
    $adminPassConfirm = $_POST['admin_pass_confirm'] ?? '';

    // Validate
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
            // Test database connection
            $dsn = "mysql:host={$dbHost};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            // Create database if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbName}`");

            // Create tables with prefix
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

            // Create admin user
            $hashedPass = password_hash($adminPass, PASSWORD_DEFAULT, ['cost' => 12]);
            $stmt = $pdo->prepare("INSERT INTO {$dbPrefix}users (username, email, password, display_name, role, created_at) VALUES (?, ?, ?, ?, 'admin', NOW())");
            $stmt->execute([$adminUser, $adminEmail, $hashedPass, $adminUser]);

            // Insert default options
            $stmt = $pdo->prepare("INSERT INTO {$dbPrefix}options (option_name, option_value) VALUES (?, ?)");
            $stmt->execute(['site_title', $siteTitle]);
            $stmt->execute(['site_description', '']);
            $stmt->execute(['posts_per_page', '10']);
            $stmt->execute(['date_format', 'M j, Y']);
            $stmt->execute(['time_format', 'H:i']);
            $stmt->execute(['cms_version', CMS_VERSION]);
            $stmt->execute(['active_plugins', '[]']);

            // Create sample content
            $welcomeContent = '<h2>Welcome to ' . $siteTitle . '</h2>
<p>Congratulations on setting up your new Forge CMS website! This is your homepage, and you can customize it however you like.</p>

<h3>Getting Started</h3>
<p>Here are a few things you can do to get started:</p>
<ul>
<li><strong>Customize your site</strong> – Visit the Settings page to update your site title, description, and other options.</li>
<li><strong>Create content</strong> – Add new posts and pages from the admin panel to build out your site.</li>
<li><strong>Upload media</strong> – Use the Media Library to upload images and files for your content.</li>
<li><strong>Explore plugins</strong> – Check out the Plugins page to extend your site\'s functionality.</li>
</ul>

<h3>Need Help?</h3>
<p>Forge CMS is designed to be simple and intuitive. If you need assistance, check out the documentation or reach out to our community.</p>

<p>Happy building!</p>';

            $helloContent = '<p>Welcome to your new Forge CMS website! This is your first blog post, and it\'s here to help you get familiar with how posts work.</p>

<h2>What is Forge CMS?</h2>
<p>Forge CMS is a modern, lightweight content management system built with PHP. It\'s designed to be simple, fast, and developer-friendly – giving you all the tools you need without the bloat.</p>

<h2>Key Features</h2>
<p>Here\'s what makes Forge CMS special:</p>
<ul>
<li><strong>Clean Admin Interface</strong> – A modern, intuitive dashboard that\'s a pleasure to use.</li>
<li><strong>Posts &amp; Pages</strong> – Full content management with a rich text editor.</li>
<li><strong>Media Library</strong> – Drag-and-drop uploads with easy organization.</li>
<li><strong>Plugin System</strong> – Extend functionality with hooks, filters, and content tags.</li>
<li><strong>Theme Support</strong> – Customizable themes with a live CSS editor.</li>
</ul>

<h2>What\'s Next?</h2>
<p>Now that you\'ve seen your first post, why not create your own? Head over to the admin panel and click "Add New" under Posts to write something amazing.</p>

<p>You can edit or delete this post anytime – it\'s just here to get you started. Happy writing!</p>';

            $welcomeExcerpt = 'Welcome to your new Forge CMS website. Get started by customizing your site, creating content, and exploring all the features.';
            $helloExcerpt = 'Welcome to Forge CMS! This is your first post. Learn about the key features and get started with creating your own content.';

            $stmt = $pdo->prepare("INSERT INTO {$dbPrefix}posts (post_type, title, slug, content, excerpt, status, author_id, created_at, updated_at, published_at) VALUES (?, ?, ?, ?, ?, 'published', 1, NOW(), NOW(), NOW())");
            $stmt->execute(['page', 'Welcome', 'welcome', $welcomeContent, $welcomeExcerpt]);
            $stmt->execute(['post', 'Hello World', 'hello-world', $helloContent, $helloExcerpt]);

            // Update config file
            $configContent = "<?php\n";
            $configContent .= "/**\n * Forge CMS Configuration\n * Generated: " . date('Y-m-d H:i:s') . "\n */\n\n";
            $configContent .= "// Prevent direct access\n";
            $configContent .= "defined('CMS_ROOT') or die('Direct access not allowed');\n\n";
            $configContent .= "// Branding\n";
            $configContent .= "define('CMS_NAME', 'Forge');\n";
            $configContent .= "define('CMS_VERSION', '" . CMS_VERSION . "');\n\n";
            $configContent .= "// Database settings\n";
            $configContent .= "define('DB_HOST', " . var_export($dbHost, true) . ");\n";
            $configContent .= "define('DB_NAME', " . var_export($dbName, true) . ");\n";
            $configContent .= "define('DB_USER', " . var_export($dbUser, true) . ");\n";
            $configContent .= "define('DB_PASS', " . var_export($dbPass, true) . ");\n";
            $configContent .= "define('DB_CHARSET', 'utf8mb4');\n";
            $configContent .= "define('DB_PREFIX', " . var_export($dbPrefix, true) . ");\n\n";
            $configContent .= "// Site settings\n";
            $configContent .= "define('SITE_URL', " . var_export($siteUrl, true) . ");\n";
            $configContent .= "define('ADMIN_URL', SITE_URL . '/admin');\n\n";
            $configContent .= "// Paths\n";
            $configContent .= "define('INCLUDES_PATH', CMS_ROOT . '/includes');\n";
            $configContent .= "define('ADMIN_PATH', CMS_ROOT . '/admin');\n";
            $configContent .= "define('THEMES_PATH', CMS_ROOT . '/themes');\n";
            $configContent .= "define('UPLOADS_PATH', CMS_ROOT . '/uploads');\n";
            $configContent .= "define('UPLOADS_URL', SITE_URL . '/uploads');\n";
            $configContent .= "define('PLUGINS_PATH', CMS_ROOT . '/plugins');\n\n";
            $configContent .= "// Current theme\n";
            $configContent .= "define('CURRENT_THEME', 'default');\n";
            $configContent .= "define('THEME_URL', SITE_URL . '/themes/' . CURRENT_THEME);\n\n";
            $configContent .= "// Session settings\n";
            $configContent .= "define('SESSION_NAME', 'forge_session');\n";
            $configContent .= "define('SESSION_LIFETIME', 86400);\n\n";
            $configContent .= "// Security\n";
            $configContent .= "define('HASH_COST', 12);\n\n";
            $configContent .= "// Timezone\n";
            $configContent .= "date_default_timezone_set('UTC');\n";

            file_put_contents(__DIR__ . '/includes/config.php', $configContent);

            // Create uploads directory
            if (!is_dir(__DIR__ . '/uploads')) {
                mkdir(__DIR__ . '/uploads', 0755, true);
            }

            // Create installed flag
            file_put_contents(__DIR__ . '/.installed', date('Y-m-d H:i:s'));

            $success = true;
            $step = 3;

        } catch (PDOException $e) {
            $errors['database'] = 'Database error: ' . $e->getMessage();
        } catch (Exception $e) {
            $errors['general'] = 'Installation error: ' . $e->getMessage();
        }
    }
}

// Auto-detect site URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$path = dirname($_SERVER['SCRIPT_NAME']);
$defaultUrl = $protocol . '://' . $host . ($path !== '/' ? $path : '');
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
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            color: #0f172a;
            line-height: 1.6;
        }
        
        .install-container {
            max-width: 640px;
            margin: 0 auto;
            padding: 3rem 1.5rem;
        }
        
        .install-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        
        .install-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            border-radius: 20px;
            margin-bottom: 1.5rem;
            box-shadow: 0 12px 24px rgba(99, 102, 241, 0.3);
        }
        
        .install-logo svg {
            width: 36px;
            height: 36px;
            color: #fff;
        }
        
        .install-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .install-header p {
            color: #64748b;
        }
        
        /* Steps indicator */
        .steps {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }
        
        .step {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .step-number {
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
            flex-shrink: 0;
        }
        
        .step.active .step-number {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: #fff;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }
        
        .step.completed .step-number {
            background: #10b981;
            color: #fff;
        }
        
        .step-label {
            font-size: 0.8125rem;
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
            flex-shrink: 0;
            align-self: center;
        }
        
        /* Card */
        .install-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            overflow: hidden;
        }
        
        .card-header {
            padding: 1.25rem 1.5rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .card-header h2 {
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .card-header svg {
            width: 20px;
            height: 20px;
            color: #6366f1;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        /* Requirements */
        .req-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .req-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.875rem 1rem;
            background: #f8fafc;
            border-radius: 8px;
        }
        
        .req-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .req-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .req-icon.pass {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }
        
        .req-icon.fail {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        
        .req-name {
            font-weight: 500;
        }
        
        .req-value {
            font-size: 0.875rem;
            color: #64748b;
        }
        
        /* Form */
        .form-section {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .form-section:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .form-section-title {
            font-size: 0.8125rem;
            font-weight: 600;
            color: #6366f1;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.375rem;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 0.9375rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            transition: all 0.15s;
            font-family: inherit;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }
        
        .form-input.error {
            border-color: #ef4444;
        }
        
        .form-hint {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.25rem;
        }
        
        .form-error {
            font-size: 0.75rem;
            color: #ef4444;
            margin-top: 0.25rem;
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
            padding: 0.75rem 1.5rem;
            font-size: 0.9375rem;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: #fff;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
        }
        
        .btn-secondary {
            background: #fff;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }
        
        .btn-secondary:hover {
            border-color: #6366f1;
            color: #6366f1;
        }
        
        .btn-block {
            width: 100%;
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }
        
        .card-footer {
            padding: 1rem 1.5rem;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }
        
        /* Success */
        .success-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 12px 24px rgba(16, 185, 129, 0.3);
        }
        
        .success-icon svg {
            width: 40px;
            height: 40px;
            color: #fff;
        }
        
        .success-content {
            text-align: center;
            padding: 2rem;
        }
        
        .success-content h2 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .success-content p {
            color: #64748b;
            margin-bottom: 1.5rem;
        }
        
        .success-links {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        
        /* Error box */
        .error-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }
        
        @media (max-width: 640px) {
            .form-row { grid-template-columns: 1fr; }
            .step-label { display: none; }
            .success-links { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-header">
            <div class="install-logo">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                    <polyline points="2 17 12 22 22 17"></polyline>
                    <polyline points="2 12 12 17 22 12"></polyline>
                </svg>
            </div>
            <h1>Install <?= CMS_NAME ?></h1>
            <p>Version <?= CMS_VERSION ?></p>
        </div>
        
        <div class="steps">
            <div class="step <?= $step >= 1 ? ($step > 1 ? 'completed' : 'active') : '' ?>">
                <div class="step-number"><?= $step > 1 ? '✓' : '1' ?></div>
                <span class="step-label">Requirements</span>
            </div>
            <div class="step-line"></div>
            <div class="step <?= $step >= 2 ? ($step > 2 ? 'completed' : 'active') : '' ?>">
                <div class="step-number"><?= $step > 2 ? '✓' : '2' ?></div>
                <span class="step-label">Configuration</span>
            </div>
            <div class="step-line"></div>
            <div class="step <?= $step >= 3 ? 'active' : '' ?>">
                <div class="step-number">3</div>
                <span class="step-label">Complete</span>
            </div>
        </div>
        
        <?php if ($step === 1): ?>
        <!-- Step 1: Requirements -->
        <div class="install-card">
            <div class="card-header">
                <h2>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 11l3 3L22 4"></path>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                    </svg>
                    System Requirements
                </h2>
            </div>
            <div class="card-body">
                <div class="req-list">
                    <?php foreach ($requirements as $key => $req): ?>
                    <div class="req-item">
                        <div class="req-info">
                            <div class="req-icon <?= $req['status'] ? 'pass' : 'fail' ?>">
                                <?php if ($req['status']): ?>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                                <?php else: ?>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                                <?php endif; ?>
                            </div>
                            <span class="req-name"><?= $req['name'] ?></span>
                        </div>
                        <span class="req-value"><?= $req['current'] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="card-footer">
                <?php if ($allPassed): ?>
                <a href="?step=2" class="btn btn-primary">
                    Continue
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </a>
                <?php else: ?>
                <button class="btn btn-primary" disabled>Fix Requirements First</button>
                <?php endif; ?>
            </div>
        </div>
        
        <?php elseif ($step === 2): ?>
        <!-- Step 2: Configuration -->
        <div class="install-card">
            <div class="card-header">
                <h2>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                    Configuration
                </h2>
            </div>
            
            <form method="post">
                <div class="card-body">
                    <?php if (!empty($errors['database']) || !empty($errors['general'])): ?>
                    <div class="error-box">
                        <?= htmlspecialchars($errors['database'] ?? $errors['general']) ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-section">
                        <div class="form-section-title">Database</div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Host</label>
                                <input type="text" name="db_host" class="form-input <?= isset($errors['db_host']) ? 'error' : '' ?>" 
                                       value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>">
                                <?php if (isset($errors['db_host'])): ?>
                                <div class="form-error"><?= $errors['db_host'] ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Database Name</label>
                                <input type="text" name="db_name" class="form-input <?= isset($errors['db_name']) ? 'error' : '' ?>" 
                                       value="<?= htmlspecialchars($_POST['db_name'] ?? 'forge_cms') ?>">
                                <?php if (isset($errors['db_name'])): ?>
                                <div class="form-error"><?= $errors['db_name'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Username</label>
                                <input type="text" name="db_user" class="form-input <?= isset($errors['db_user']) ? 'error' : '' ?>" 
                                       value="<?= htmlspecialchars($_POST['db_user'] ?? 'root') ?>">
                                <?php if (isset($errors['db_user'])): ?>
                                <div class="form-error"><?= $errors['db_user'] ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Password</label>
                                <input type="password" name="db_pass" class="form-input" 
                                       value="<?= htmlspecialchars($_POST['db_pass'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Table Prefix</label>
                                <input type="text" name="db_prefix" class="form-input" 
                                       value="<?= htmlspecialchars($_POST['db_prefix'] ?? 'forge_') ?>"
                                       pattern="[a-zA-Z0-9_]+" style="max-width: 200px;">
                                <div class="form-hint">Letters, numbers, underscores only. End with underscore. Default: forge_</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <div class="form-section-title">Site Settings</div>
                        <div class="form-group">
                            <label class="form-label">Site URL</label>
                            <input type="url" name="site_url" class="form-input <?= isset($errors['site_url']) ? 'error' : '' ?>" 
                                   value="<?= htmlspecialchars($_POST['site_url'] ?? $defaultUrl) ?>">
                            <div class="form-hint">No trailing slash. Example: https://example.com</div>
                            <?php if (isset($errors['site_url'])): ?>
                            <div class="form-error"><?= $errors['site_url'] ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Site Title</label>
                            <input type="text" name="site_title" class="form-input <?= isset($errors['site_title']) ? 'error' : '' ?>" 
                                   value="<?= htmlspecialchars($_POST['site_title'] ?? 'My Website') ?>">
                            <?php if (isset($errors['site_title'])): ?>
                            <div class="form-error"><?= $errors['site_title'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <div class="form-section-title">Admin Account</div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Username</label>
                                <input type="text" name="admin_user" class="form-input <?= isset($errors['admin_user']) ? 'error' : '' ?>" 
                                       value="<?= htmlspecialchars($_POST['admin_user'] ?? 'admin') ?>">
                                <?php if (isset($errors['admin_user'])): ?>
                                <div class="form-error"><?= $errors['admin_user'] ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" name="admin_email" class="form-input <?= isset($errors['admin_email']) ? 'error' : '' ?>" 
                                       value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>">
                                <?php if (isset($errors['admin_email'])): ?>
                                <div class="form-error"><?= $errors['admin_email'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Password</label>
                                <input type="password" name="admin_pass" class="form-input <?= isset($errors['admin_pass']) ? 'error' : '' ?>">
                                <div class="form-hint">Minimum 8 characters</div>
                                <?php if (isset($errors['admin_pass'])): ?>
                                <div class="form-error"><?= $errors['admin_pass'] ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="admin_pass_confirm" class="form-input <?= isset($errors['admin_pass_confirm']) ? 'error' : '' ?>">
                                <?php if (isset($errors['admin_pass_confirm'])): ?>
                                <div class="form-error"><?= $errors['admin_pass_confirm'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-footer">
                    <a href="?step=1" class="btn btn-secondary">Back</a>
                    <button type="submit" class="btn btn-primary">
                        Install Now
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
        
        <?php elseif ($step === 3): ?>
        <!-- Step 3: Success -->
        <div class="install-card">
            <div class="success-content">
                <div class="success-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </div>
                <h2>Installation Complete!</h2>
                <p><?= CMS_NAME ?> has been successfully installed. You can now log in to your admin dashboard and start creating content.</p>
                <div class="success-links">
                    <a href="admin/" class="btn btn-primary">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="3" y1="9" x2="21" y2="9"></line>
                            <line x1="9" y1="21" x2="9" y2="9"></line>
                        </svg>
                        Go to Dashboard
                    </a>
                    <a href="./" class="btn btn-secondary">View Site</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
