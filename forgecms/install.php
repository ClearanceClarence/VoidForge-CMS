<?php
/**
 * Forge CMS Installation Script
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('CMS_ROOT', __DIR__);
define('CMS_NAME', 'Forge');
define('CMS_VERSION', '1.0.2');

// Check requirements
$requirements = [];
if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    $requirements[] = 'PHP 8.0 or higher required (you have ' . PHP_VERSION . ')';
}
if (!extension_loaded('pdo_mysql')) {
    $requirements[] = 'PDO MySQL extension is required';
}
if (!is_writable(__DIR__)) {
    $requirements[] = 'Installation directory is not writable';
}
if (!file_exists(__DIR__ . '/includes/config.php')) {
    $requirements[] = 'includes/config.php is missing';
} elseif (!is_writable(__DIR__ . '/includes/config.php')) {
    $requirements[] = 'includes/config.php is not writable';
}

// Check if already installed
if (file_exists(__DIR__ . '/.installed')) {
    header('Location: admin/');
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $dbHost = trim($_POST['db_host'] ?? '');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = $_POST['db_pass'] ?? '';
    
    $siteUrl = rtrim(trim($_POST['site_url'] ?? ''), '/');
    $siteTitle = trim($_POST['site_title'] ?? '');
    
    $adminUser = trim($_POST['admin_user'] ?? '');
    $adminEmail = trim($_POST['admin_email'] ?? '');
    $adminPass = $_POST['admin_pass'] ?? '';
    $adminPassConfirm = $_POST['admin_pass_confirm'] ?? '';

    // Validate
    if (empty($dbHost)) $errors[] = 'Database host is required';
    if (empty($dbName)) $errors[] = 'Database name is required';
    if (empty($dbUser)) $errors[] = 'Database user is required';
    if (empty($siteUrl)) $errors[] = 'Site URL is required';
    if (empty($siteTitle)) $errors[] = 'Site title is required';
    if (empty($adminUser)) $errors[] = 'Admin username is required';
    if (empty($adminEmail)) $errors[] = 'Admin email is required';
    if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address';
    if (empty($adminPass)) $errors[] = 'Admin password is required';
    if (strlen($adminPass) < 8) $errors[] = 'Password must be at least 8 characters';
    if ($adminPass !== $adminPassConfirm) $errors[] = 'Passwords do not match';

    if (empty($errors)) {
        // Test database connection
        try {
            $dsn = "mysql:host={$dbHost};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            // Create database if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbName}`");

            // Create tables
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS users (
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
                CREATE TABLE IF NOT EXISTS posts (
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
                    INDEX idx_author (author_id),
                    INDEX idx_parent (parent_id),
                    UNIQUE KEY unique_slug_type (slug, post_type)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS post_meta (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    post_id INT UNSIGNED NOT NULL,
                    meta_key VARCHAR(255) NOT NULL,
                    meta_value LONGTEXT,
                    INDEX idx_post_id (post_id),
                    INDEX idx_meta_key (meta_key),
                    UNIQUE KEY unique_post_meta (post_id, meta_key)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS media (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    filename VARCHAR(255) NOT NULL,
                    filepath VARCHAR(500) NOT NULL,
                    mime_type VARCHAR(100) NOT NULL,
                    file_size INT UNSIGNED NOT NULL,
                    width INT UNSIGNED,
                    height INT UNSIGNED,
                    alt_text VARCHAR(255),
                    title VARCHAR(255),
                    caption TEXT,
                    folder_id INT UNSIGNED,
                    uploaded_by INT UNSIGNED,
                    created_at DATETIME NOT NULL,
                    INDEX idx_mime_type (mime_type),
                    INDEX idx_uploaded_by (uploaded_by),
                    INDEX idx_folder_id (folder_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS media_folders (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    created_at DATETIME NOT NULL,
                    INDEX idx_name (name)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS options (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    option_name VARCHAR(255) NOT NULL UNIQUE,
                    option_value LONGTEXT,
                    autoload TINYINT(1) NOT NULL DEFAULT 1,
                    INDEX idx_autoload (autoload)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Create admin user
            $hashedPass = password_hash($adminPass, PASSWORD_DEFAULT, ['cost' => 12]);
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password, display_name, role, created_at)
                VALUES (?, ?, ?, ?, 'admin', NOW())
            ");
            $stmt->execute([$adminUser, $adminEmail, $hashedPass, $adminUser]);

            // Insert default options
            $options = [
                ['site_title', $siteTitle],
                ['site_description', ''],
                ['posts_per_page', '10'],
                ['date_format', 'M j, Y'],
                ['time_format', 'H:i'],
                ['cms_version', CMS_VERSION],
                ['last_update', date('Y-m-d H:i:s')],
            ];

            $stmt = $pdo->prepare("INSERT INTO options (option_name, option_value) VALUES (?, ?)");
            foreach ($options as $option) {
                $stmt->execute($option);
            }

            // Create sample page with comprehensive HTML showcase
            $samplePageContent = <<<'HTML'
<h2>Welcome to Your New Website</h2>
<p>This page demonstrates the various content elements you can use throughout your site. Feel free to edit or delete this page to start fresh.</p>

<h3>Text Formatting</h3>
<p>You can make text <strong>bold</strong>, <em>italic</em>, or <u>underlined</u>. You can also combine them for <strong><em>bold and italic</em></strong> text. Use <mark>highlights</mark> to draw attention to important information.</p>

<blockquote>
<p>"The best way to predict the future is to create it." — Peter Drucker</p>
</blockquote>

<h3>Lists and Organization</h3>
<p>Organize your content with ordered and unordered lists:</p>

<h4>Features of This CMS</h4>
<ul>
<li>Clean, modern admin interface</li>
<li>Built-in media library</li>
<li>User roles and permissions</li>
<li>Custom CSS editor with live preview</li>
</ul>

<h4>Getting Started Steps</h4>
<ol>
<li>Explore the admin dashboard</li>
<li>Create your first post</li>
<li>Customize your site appearance</li>
<li>Invite team members</li>
</ol>

<h3>Code Examples</h3>
<p>Display code snippets with proper formatting:</p>
<pre><code>function greet(name) {
    return `Hello, ${name}!`;
}

console.log(greet('World'));</code></pre>

<h3>Tables</h3>
<p>Present data in organized tables:</p>
<table>
<thead>
<tr>
<th>Feature</th>
<th>Status</th>
<th>Version</th>
</tr>
</thead>
<tbody>
<tr>
<td>Posts &amp; Pages</td>
<td>✓ Available</td>
<td>1.0</td>
</tr>
<tr>
<td>Media Library</td>
<td>✓ Available</td>
<td>1.0</td>
</tr>
<tr>
<td>User Management</td>
<td>✓ Available</td>
<td>1.0</td>
</tr>
<tr>
<td>Custom CSS</td>
<td>✓ Available</td>
<td>1.0</td>
</tr>
</tbody>
</table>

<h3>Links and Navigation</h3>
<p>Create <a href="#">internal links</a> to other pages or <a href="https://example.com" target="_blank">external links</a> to other websites.</p>

<hr>

<p><em>This sample page was automatically created during installation. Feel free to customize or delete it.</em></p>
HTML;

            $stmt = $pdo->prepare("
                INSERT INTO posts (post_type, title, slug, content, status, author_id, created_at, updated_at, published_at)
                VALUES ('page', 'Sample Page', 'sample-page', ?, 'published', 1, NOW(), NOW(), NOW())
            ");
            $stmt->execute([$samplePageContent]);

            // Create sample post with rich content
            $samplePostContent = <<<'HTML'
<p>Welcome to <strong>Forge CMS</strong>! This is your first post, designed to showcase what's possible with your new content management system.</p>

<h2>Getting Started with Content Creation</h2>
<p>Creating beautiful, well-structured content is easy. The visual editor supports all common formatting options, making it simple to craft professional-looking posts and pages.</p>

<h3>Rich Media Support</h3>
<p>Your content can include:</p>
<ul>
<li><strong>Images</strong> — Upload and embed images from your media library</li>
<li><strong>Videos</strong> — Embed videos from popular platforms</li>
<li><strong>Documents</strong> — Link to downloadable files</li>
</ul>

<h3>Structured Content</h3>
<p>Use headings to create a clear hierarchy. This helps both readers and search engines understand your content structure.</p>

<blockquote>
<p>Well-organized content is easier to read, easier to remember, and more likely to achieve its purpose.</p>
</blockquote>

<h3>Example: A Simple Recipe</h3>
<p>Here's how you might structure a recipe post:</p>

<h4>Ingredients</h4>
<ul>
<li>2 cups all-purpose flour</li>
<li>1 cup sugar</li>
<li>3 eggs</li>
<li>1/2 cup butter</li>
</ul>

<h4>Instructions</h4>
<ol>
<li>Preheat your oven to 350°F (175°C)</li>
<li>Mix dry ingredients in a large bowl</li>
<li>Add wet ingredients and stir until combined</li>
<li>Pour into a greased pan and bake for 30 minutes</li>
</ol>

<h3>Code and Technical Content</h3>
<p>If you're writing technical content, code blocks are fully supported:</p>
<pre><code>&lt;article class="post"&gt;
    &lt;h1&gt;{{ title }}&lt;/h1&gt;
    &lt;div class="content"&gt;
        {{ content }}
    &lt;/div&gt;
&lt;/article&gt;</code></pre>

<h3>What's Next?</h3>
<p>Now that you've seen what's possible, it's time to create your own content:</p>
<ol>
<li>Edit or delete this post</li>
<li>Create new posts and pages</li>
<li>Upload media to your library</li>
<li>Customize your site's appearance</li>
</ol>

<p><em>Happy publishing!</em> 🚀</p>
HTML;

            $stmt = $pdo->prepare("
                INSERT INTO posts (post_type, title, slug, content, status, author_id, created_at, updated_at, published_at)
                VALUES ('post', 'Hello World', 'hello-world', ?, 'published', 1, NOW(), NOW(), NOW())
            ");
            $stmt->execute([$samplePostContent]);

            // Update config file
            $configContent = file_get_contents(__DIR__ . '/includes/config.php');
            $configContent = preg_replace("/define\('DB_HOST', '.*?'\);/", "define('DB_HOST', '{$dbHost}');", $configContent);
            $configContent = preg_replace("/define\('DB_NAME', '.*?'\);/", "define('DB_NAME', '{$dbName}');", $configContent);
            $configContent = preg_replace("/define\('DB_USER', '.*?'\);/", "define('DB_USER', '{$dbUser}');", $configContent);
            $configContent = preg_replace("/define\('DB_PASS', '.*?'\);/", "define('DB_PASS', '{$dbPass}');", $configContent);
            $configContent = preg_replace("/define\('SITE_URL', '.*?'\);/", "define('SITE_URL', '{$siteUrl}');", $configContent);
            file_put_contents(__DIR__ . '/includes/config.php', $configContent);

            // Mark as installed
            file_put_contents(__DIR__ . '/.installed', date('Y-m-d H:i:s'));

            $success = true;

        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'Error: ' . $e->getMessage();
        } catch (Error $e) {
            $errors[] = 'Fatal error: ' . $e->getMessage();
        }
    }
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
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .container {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.4);
            width: 100%;
            max-width: 480px;
            padding: 2.5rem;
        }
        .header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .header .logo {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
        .header svg {
            width: 32px;
            height: 32px;
            color: #fff;
        }
        h1 {
            font-size: 1.5rem;
            color: #0f172a;
            margin-bottom: 0.25rem;
            font-weight: 700;
        }
        .subtitle {
            color: #64748b;
            font-size: 0.9375rem;
        }
        .section-title {
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin: 2rem 0 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.375rem;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="url"] {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 0.9375rem;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            transition: all 0.15s ease;
            background: #f8fafc;
        }
        input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.15);
            background: #fff;
        }
        .btn {
            display: inline-block;
            padding: 0.875rem 1.5rem;
            font-size: 0.9375rem;
            font-weight: 600;
            text-decoration: none;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .btn-primary {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: #fff;
            width: 100%;
            margin-top: 1.5rem;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #4f46e5, #4338ca);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(99,102,241,0.4);
        }
        .errors {
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        .errors ul {
            margin: 0;
            padding-left: 1.25rem;
            color: #991b1b;
            font-size: 0.875rem;
        }
        .success {
            text-align: center;
            padding: 2rem 0;
        }
        .success-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
        .success-icon svg {
            width: 32px;
            height: 32px;
            stroke: #fff;
        }
        .success h2 {
            color: #166534;
            margin-bottom: 0.5rem;
        }
        .success p {
            color: #64748b;
            margin-bottom: 1.5rem;
        }
        .success .btn {
            width: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($success): ?>
            <div class="success">
                <div class="success-icon">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h2>Installation Complete!</h2>
                <p><?= CMS_NAME ?> has been installed successfully.</p>
                <a href="admin/" class="btn btn-primary">Go to Admin Panel</a>
            </div>
        <?php else: ?>
            <div class="header">
                <div class="logo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                        <polyline points="2 17 12 22 22 17"></polyline>
                        <polyline points="2 12 12 17 22 12"></polyline>
                    </svg>
                </div>
                <h1>Install <?= CMS_NAME ?></h1>
                <p class="subtitle">Complete the form below to set up your CMS.</p>
            </div>

            <?php if (!empty($requirements)): ?>
                <div class="errors">
                    <strong>System Requirements Not Met:</strong>
                    <ul>
                        <?php foreach ($requirements as $req): ?>
                            <li><?= htmlspecialchars($req) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="errors">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" <?= !empty($requirements) ? 'style="opacity: 0.5; pointer-events: none;"' : '' ?>>
                <div class="section-title">Database Settings</div>
                
                <div class="form-group">
                    <label for="db_host">Database Host</label>
                    <input type="text" id="db_host" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required>
                </div>

                <div class="form-group">
                    <label for="db_name">Database Name</label>
                    <input type="text" id="db_name" name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? 'cms') ?>" required>
                </div>

                <div class="form-group">
                    <label for="db_user">Database User</label>
                    <input type="text" id="db_user" name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? 'root') ?>" required>
                </div>

                <div class="form-group">
                    <label for="db_pass">Database Password</label>
                    <input type="password" id="db_pass" name="db_pass">
                </div>

                <div class="section-title">Site Settings</div>

                <div class="form-group">
                    <label for="site_url">Site URL</label>
                    <input type="url" id="site_url" name="site_url" value="<?= htmlspecialchars($_POST['site_url'] ?? 'http://localhost/cms') ?>" required>
                </div>

                <div class="form-group">
                    <label for="site_title">Site Title</label>
                    <input type="text" id="site_title" name="site_title" value="<?= htmlspecialchars($_POST['site_title'] ?? 'My Site') ?>" required>
                </div>

                <div class="section-title">Admin Account</div>

                <div class="form-group">
                    <label for="admin_user">Username</label>
                    <input type="text" id="admin_user" name="admin_user" value="<?= htmlspecialchars($_POST['admin_user'] ?? 'admin') ?>" required>
                </div>

                <div class="form-group">
                    <label for="admin_email">Email</label>
                    <input type="email" id="admin_email" name="admin_email" value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="admin_pass">Password</label>
                    <input type="password" id="admin_pass" name="admin_pass" required>
                </div>

                <div class="form-group">
                    <label for="admin_pass_confirm">Confirm Password</label>
                    <input type="password" id="admin_pass_confirm" name="admin_pass_confirm" required>
                </div>

                <button type="submit" class="btn btn-primary">Install CMS</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
