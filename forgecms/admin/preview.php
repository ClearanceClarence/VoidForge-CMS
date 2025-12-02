<?php
/**
 * CSS Preview Page
 */

define('CMS_ROOT', dirname(__DIR__));
require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/user.php';
require_once CMS_ROOT . '/includes/post.php';
require_once CMS_ROOT . '/includes/media.php';

Post::init();

User::startSession();

$type = $_GET['type'] ?? 'frontend';
$customCss = getOption($type === 'admin' ? 'custom_admin_css' : 'custom_frontend_css', '');

if ($type === 'admin'):
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Preview</title>
    <link rel="stylesheet" href="<?= ADMIN_URL ?>/assets/css/admin.css">
    <style id="customStyles"><?= $customCss ?></style>
</head>
<body style="background: var(--bg-secondary); padding: 1.5rem;">
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Published Posts</div>
            <div class="stat-value">24</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Published Pages</div>
            <div class="stat-value">8</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Media Files</div>
            <div class="stat-value">156</div>
        </div>
    </div>

    <div class="card" style="margin-bottom: 1rem;">
        <div class="card-header">
            <h2 class="card-title">Sample Card</h2>
            <button class="btn btn-primary btn-sm">Action</button>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">Sample Input</label>
                <input type="text" class="form-input" placeholder="Enter something...">
            </div>
            <div class="form-group">
                <label class="form-label">Sample Select</label>
                <select class="form-select">
                    <option>Option 1</option>
                    <option>Option 2</option>
                </select>
            </div>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <button class="btn btn-primary">Primary</button>
                <button class="btn btn-secondary">Secondary</button>
                <button class="btn btn-danger">Danger</button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><a href="#">Sample Post Title</a></td>
                        <td><span class="status-badge status-published">Published</span></td>
                        <td>Nov 28, 2025</td>
                    </tr>
                    <tr>
                        <td><a href="#">Another Post</a></td>
                        <td><span class="status-badge status-draft">Draft</span></td>
                        <td>Nov 27, 2025</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    // Listen for CSS updates from parent
    window.addEventListener('message', function(e) {
        if (e.data && e.data.type === 'updateCSS') {
            document.getElementById('customStyles').textContent = e.data.css;
        }
    });
    </script>
</body>
</html>
<?php else: 
    // Frontend preview - show actual homepage content
    $siteTitle = getOption('site_title', 'My Site');
    $siteDescription = getOption('site_description', '');
    
    $posts = Post::query([
        'post_type' => 'post',
        'status' => 'published',
        'limit' => 3,
    ]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($siteTitle) ?></title>
    <style id="baseStyles">
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f9fafb;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        header {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        header .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .site-title {
            font-size: 1.25rem;
            font-weight: 600;
        }
        .site-title a {
            color: #111;
            text-decoration: none;
        }
        nav a {
            color: #666;
            text-decoration: none;
            margin-left: 1.5rem;
        }
        nav a:hover {
            color: #6366f1;
        }
        article {
            background: #fff;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        article h2 {
            margin-bottom: 0.5rem;
        }
        article h2 a {
            color: #111;
            text-decoration: none;
        }
        article h2 a:hover {
            color: #6366f1;
        }
        .post-meta {
            color: #666;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }
        .post-content p {
            margin-bottom: 1rem;
        }
        .read-more {
            display: inline-block;
            color: #6366f1;
            text-decoration: none;
            font-weight: 500;
        }
        .read-more:hover {
            text-decoration: underline;
        }
        footer {
            text-align: center;
            padding: 2rem;
            color: #666;
            font-size: 0.875rem;
        }
    </style>
    <style id="customStyles"><?= $customCss ?></style>
</head>
<body>
    <header>
        <div class="container">
            <h1 class="site-title">
                <a href="#"><?= esc($siteTitle) ?></a>
            </h1>
            <nav>
                <a href="#">Home</a>
                <a href="#">About</a>
                <a href="#">Contact</a>
            </nav>
        </div>
    </header>
    
    <main>
        <div class="container">
            <?php if (empty($posts)): ?>
                <article>
                    <h2>Welcome to <?= esc($siteTitle) ?></h2>
                    <div class="post-content">
                        <p>This is a preview of your site's frontend. Your custom CSS will be applied here so you can see how it looks before saving.</p>
                        <p>Try changing colors, fonts, spacing, or any other styles in the editor on the left.</p>
                    </div>
                </article>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <article>
                        <h2><a href="#"><?= esc($post['title']) ?></a></h2>
                        <div class="post-meta">
                            <?= formatDate($post['published_at'] ?? $post['created_at'], getOption('date_format', 'M j, Y')) ?>
                        </div>
                        <div class="post-content">
                            <?php if ($post['excerpt']): ?>
                                <p><?= esc($post['excerpt']) ?></p>
                            <?php else: ?>
                                <p><?= esc(truncate(strip_tags($post['content']), 200)) ?></p>
                            <?php endif; ?>
                        </div>
                        <a href="#" class="read-more">Read more →</a>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
    
    <footer>
        <p>© <?= date('Y') ?> <?= esc($siteTitle) ?>. All rights reserved.</p>
    </footer>

    <script>
    // Listen for CSS updates from parent
    window.addEventListener('message', function(e) {
        if (e.data && e.data.type === 'updateCSS') {
            document.getElementById('customStyles').textContent = e.data.css;
        }
    });
    </script>
</body>
</html>
<?php endif; ?>
