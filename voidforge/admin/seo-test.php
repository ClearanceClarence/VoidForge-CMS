<?php
/**
 * SEO Diagnostic Tool - VoidForge CMS
 * 
 * Hidden tool to analyze SEO output for any page.
 * Access via: /admin/seo-test.php?url=/your-page-slug
 * Or: /admin/seo-test.php?post_id=123
 * 
 * @package VoidForge
 * @since 0.3.0
 */

define('CMS_ROOT', dirname(__DIR__));
require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/user.php';
require_once CMS_ROOT . '/includes/post.php';
require_once CMS_ROOT . '/includes/media.php';
require_once CMS_ROOT . '/includes/plugin.php';
require_once CMS_ROOT . '/includes/taxonomy.php';
require_once CMS_ROOT . '/includes/seo.php';

User::startSession();
User::requireLogin();

Post::init();
Plugin::init();
Taxonomy::init();
SEO::init();

// Get the target
$postId = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
$url = $_GET['url'] ?? '';
$post = null;
$pageType = 'home';
$error = null;

// Find the post
if ($postId) {
    $post = Post::find($postId);
    if (!$post) {
        $error = "Post ID {$postId} not found.";
    } else {
        $pageType = 'single';
    }
} elseif ($url) {
    // Parse URL to find post
    $url = ltrim($url, '/');
    if (!empty($url)) {
        // Try to find by slug
        $post = Post::findBySlug($url);
        if (!$post) {
            // Try with post type prefix
            $parts = explode('/', $url);
            if (count($parts) >= 2) {
                $post = Post::findBySlug($parts[1], $parts[0]);
            }
        }
        if ($post) {
            $pageType = 'single';
        } else {
            $error = "No post found for URL: /{$url}";
        }
    }
}

// Generate SEO data
$seoData = [];
if (!$error) {
    $seoData = [
        'title' => SEO::generateTitle($post, $pageType),
        'description' => SEO::generateDescription($post, $pageType),
        'canonical' => SEO::getCanonicalUrl($post),
        'robots' => SEO::generateRobotsMeta($post, $pageType),
        'og' => SEO::generateOpenGraph($post, $pageType),
        'twitter' => SEO::generateTwitterCard($post, $pageType),
        'schema' => SEO::generateSchema($post, $pageType),
    ];
    
    if ($post) {
        $seoData['post_meta'] = SEO::getPostMeta($post['id']);
        $seoData['analysis'] = SEO::analyzeContent($post, $seoData['post_meta']);
    }
}

// Get recent posts for quick testing
$recentPosts = Post::query([
    'status' => 'published',
    'limit' => 10,
    'orderby' => 'updated_at',
    'order' => 'DESC'
]);

$recentPages = Post::query([
    'post_type' => 'page',
    'status' => 'published',
    'limit' => 10,
    'orderby' => 'updated_at',
    'order' => 'DESC'
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>SEO Diagnostic Tool — <?= esc(CMS_NAME) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            line-height: 1.6;
            min-height: 100vh;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #1e293b;
        }
        h1 {
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        h1 svg { color: #6366f1; }
        .back-link {
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .back-link:hover { color: #e2e8f0; }
        
        /* Search Form */
        .search-form {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .search-input {
            flex: 1;
            padding: 0.75rem 1rem;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 8px;
            color: #e2e8f0;
            font-size: 0.9375rem;
        }
        .search-input:focus {
            outline: none;
            border-color: #6366f1;
        }
        .search-input::placeholder { color: #64748b; }
        .search-btn {
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .search-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }
        
        /* Quick Links */
        .quick-links {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 2rem;
        }
        .quick-link {
            padding: 0.5rem 0.875rem;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 6px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.8125rem;
            transition: all 0.2s;
        }
        .quick-link:hover {
            background: #334155;
            color: #e2e8f0;
            border-color: #6366f1;
        }
        .quick-link.active {
            background: #6366f1;
            border-color: #6366f1;
            color: white;
        }
        
        /* Main Grid */
        .main-grid {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 2rem;
        }
        @media (max-width: 1024px) {
            .main-grid { grid-template-columns: 1fr; }
        }
        
        /* Cards */
        .card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        .card-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
            background: #0f172a;
            border-bottom: 1px solid #334155;
            font-weight: 600;
            font-size: 0.875rem;
        }
        .card-header svg { color: #6366f1; opacity: 0.8; }
        .card-body { padding: 1.25rem; }
        
        /* Preview Cards */
        .google-preview {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            color: #202124;
        }
        .google-title {
            color: #1a0dab;
            font-size: 1.125rem;
            line-height: 1.3;
            margin-bottom: 0.25rem;
        }
        .google-url {
            color: #006621;
            font-size: 0.8125rem;
            margin-bottom: 0.25rem;
        }
        .google-desc {
            color: #545454;
            font-size: 0.8125rem;
            line-height: 1.5;
        }
        
        .social-preview {
            background: #f0f2f5;
            border-radius: 8px;
            overflow: hidden;
            color: #1c1e21;
        }
        .social-preview-image {
            height: 160px;
            background: #e4e6eb;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #65676b;
        }
        .social-preview-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .social-preview-content {
            padding: 0.875rem;
        }
        .social-preview-domain {
            font-size: 0.75rem;
            color: #65676b;
            text-transform: uppercase;
        }
        .social-preview-title {
            font-size: 0.9375rem;
            font-weight: 600;
            margin: 0.25rem 0;
            line-height: 1.3;
        }
        .social-preview-desc {
            font-size: 0.8125rem;
            color: #65676b;
            line-height: 1.4;
        }
        
        /* Data Tables */
        .data-table {
            width: 100%;
        }
        .data-row {
            display: flex;
            border-bottom: 1px solid #334155;
            padding: 0.625rem 0;
        }
        .data-row:last-child { border-bottom: none; }
        .data-key {
            width: 180px;
            flex-shrink: 0;
            font-size: 0.8125rem;
            color: #64748b;
            font-weight: 500;
        }
        .data-value {
            flex: 1;
            font-size: 0.8125rem;
            color: #e2e8f0;
            word-break: break-word;
        }
        .data-value code {
            background: #0f172a;
            padding: 0.125rem 0.375rem;
            border-radius: 4px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.75rem;
        }
        
        /* Code Block */
        .code-block {
            background: #0f172a;
            border-radius: 8px;
            padding: 1rem;
            overflow-x: auto;
        }
        .code-block pre {
            margin: 0;
            font-family: 'JetBrains Mono', ui-monospace, monospace;
            font-size: 0.75rem;
            line-height: 1.6;
            color: #a5f3fc;
        }
        
        /* Analysis */
        .score-display {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #0f172a;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .score-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            font-weight: 700;
            color: white;
        }
        .score-info h3 {
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }
        .score-info p {
            font-size: 0.8125rem;
            color: #94a3b8;
        }
        
        .issues-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .issue {
            display: flex;
            align-items: flex-start;
            gap: 0.625rem;
            padding: 0.625rem 0.875rem;
            border-radius: 6px;
            font-size: 0.8125rem;
        }
        .issue svg { flex-shrink: 0; margin-top: 1px; }
        .issue-error { background: rgba(239, 68, 68, 0.15); color: #fca5a5; }
        .issue-warning { background: rgba(245, 158, 11, 0.15); color: #fcd34d; }
        .issue-info { background: rgba(59, 130, 246, 0.15); color: #93c5fd; }
        
        /* Error */
        .error-box {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 8px;
            padding: 1rem 1.25rem;
            color: #fca5a5;
            margin-bottom: 1.5rem;
        }
        
        /* Sidebar */
        .sidebar-section {
            margin-bottom: 1.5rem;
        }
        .sidebar-title {
            font-size: 0.6875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin-bottom: 0.75rem;
        }
        .sidebar-list {
            display: flex;
            flex-direction: column;
            gap: 0.375rem;
        }
        .sidebar-item {
            display: block;
            padding: 0.625rem 0.875rem;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 6px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.8125rem;
            transition: all 0.2s;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .sidebar-item:hover {
            background: #334155;
            color: #e2e8f0;
            border-color: #6366f1;
        }
        .sidebar-item small {
            color: #64748b;
            margin-left: 0.5rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #64748b;
        }
        .empty-state svg {
            width: 48px;
            height: 48px;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        .empty-state p {
            font-size: 0.9375rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                SEO Diagnostic Tool
            </h1>
            <a href="<?= ADMIN_URL ?>" class="back-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m15 18-6-6 6-6"></path>
                </svg>
                Back to Admin
            </a>
        </header>

        <form method="GET" class="search-form">
            <input type="text" name="url" class="search-input" placeholder="Enter URL path (e.g., /about or /post/hello-world)" value="<?= esc($url) ?>">
            <button type="submit" class="search-btn">Analyze</button>
        </form>

        <div class="quick-links">
            <a href="?url=" class="quick-link <?= empty($url) && !$postId ? 'active' : '' ?>">Homepage</a>
            <?php foreach (array_slice($recentPosts, 0, 5) as $p): ?>
            <a href="?post_id=<?= $p['id'] ?>" class="quick-link <?= $postId === $p['id'] ? 'active' : '' ?>"><?= esc(mb_substr($p['title'], 0, 20)) ?><?= mb_strlen($p['title']) > 20 ? '...' : '' ?></a>
            <?php endforeach; ?>
        </div>

        <?php if ($error): ?>
        <div class="error-box"><?= esc($error) ?></div>
        <?php endif; ?>

        <div class="main-grid">
            <div class="main-content">
                <?php if (!$error): ?>
                
                <!-- Page Info -->
                <?php if ($post): ?>
                <div class="card">
                    <div class="card-header">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                        Page Information
                    </div>
                    <div class="card-body">
                        <div class="data-table">
                            <div class="data-row">
                                <div class="data-key">Title</div>
                                <div class="data-value"><?= esc($post['title']) ?></div>
                            </div>
                            <div class="data-row">
                                <div class="data-key">Type</div>
                                <div class="data-value"><code><?= esc($post['post_type']) ?></code></div>
                            </div>
                            <div class="data-row">
                                <div class="data-key">Status</div>
                                <div class="data-value"><code><?= esc($post['status']) ?></code></div>
                            </div>
                            <div class="data-row">
                                <div class="data-key">URL</div>
                                <div class="data-value"><a href="<?= esc(Post::permalink($post)) ?>" target="_blank" style="color: #6366f1;"><?= esc(Post::permalink($post)) ?></a></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Google Preview -->
                <div class="card">
                    <div class="card-header">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg>
                        Google Search Preview
                    </div>
                    <div class="card-body">
                        <div class="google-preview">
                            <div class="google-title"><?= esc($seoData['title']) ?></div>
                            <div class="google-url"><?= esc($seoData['canonical']) ?></div>
                            <div class="google-desc"><?= esc($seoData['description']) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Social Preview -->
                <div class="card">
                    <div class="card-header">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle></svg>
                        Social Media Preview (Facebook/LinkedIn)
                    </div>
                    <div class="card-body">
                        <div class="social-preview">
                            <div class="social-preview-image">
                                <?php if (!empty($seoData['og']['og:image'])): ?>
                                <img src="<?= esc($seoData['og']['og:image']) ?>" alt="">
                                <?php else: ?>
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                                No image set
                                <?php endif; ?>
                            </div>
                            <div class="social-preview-content">
                                <div class="social-preview-domain"><?= esc(parse_url(SITE_URL, PHP_URL_HOST)) ?></div>
                                <div class="social-preview-title"><?= esc($seoData['og']['og:title'] ?? $seoData['title']) ?></div>
                                <div class="social-preview-desc"><?= esc($seoData['og']['og:description'] ?? $seoData['description']) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Meta Tags -->
                <div class="card">
                    <div class="card-header">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
                        Meta Tags Output
                    </div>
                    <div class="card-body">
                        <div class="data-table">
                            <div class="data-row">
                                <div class="data-key">Title</div>
                                <div class="data-value"><?= esc($seoData['title']) ?></div>
                            </div>
                            <div class="data-row">
                                <div class="data-key">Description</div>
                                <div class="data-value"><?= esc($seoData['description']) ?></div>
                            </div>
                            <div class="data-row">
                                <div class="data-key">Canonical</div>
                                <div class="data-value"><code><?= esc($seoData['canonical']) ?></code></div>
                            </div>
                            <div class="data-row">
                                <div class="data-key">Robots</div>
                                <div class="data-value"><code><?= esc($seoData['robots']) ?></code></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Open Graph -->
                <div class="card">
                    <div class="card-header">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
                        Open Graph Tags
                    </div>
                    <div class="card-body">
                        <div class="data-table">
                            <?php foreach ($seoData['og'] as $prop => $value): ?>
                            <div class="data-row">
                                <div class="data-key"><?= esc($prop) ?></div>
                                <div class="data-value"><?= esc($value) ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Twitter Card -->
                <div class="card">
                    <div class="card-header">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path></svg>
                        Twitter Card Tags
                    </div>
                    <div class="card-body">
                        <div class="data-table">
                            <?php foreach ($seoData['twitter'] as $name => $value): ?>
                            <div class="data-row">
                                <div class="data-key"><?= esc($name) ?></div>
                                <div class="data-value"><?= esc($value) ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- JSON-LD Schema -->
                <div class="card">
                    <div class="card-header">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                        JSON-LD Schema
                    </div>
                    <div class="card-body">
                        <?php foreach ($seoData['schema'] as $schema): ?>
                        <div class="code-block" style="margin-bottom: 1rem;">
                            <pre><?= esc(json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?></pre>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php else: ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <p>Enter a URL or select a page to analyze its SEO output.</p>
                </div>
                <?php endif; ?>
            </div>

            <div class="sidebar">
                <?php if (!$error && $post && !empty($seoData['analysis'])): ?>
                <!-- Analysis -->
                <div class="card">
                    <div class="card-header">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        SEO Analysis
                    </div>
                    <div class="card-body">
                        <?php 
                        $score = $seoData['analysis']['score'];
                        $scoreColor = SEO::getScoreColor($score);
                        $scoreLabel = SEO::getScoreLabel($score);
                        ?>
                        <div class="score-display">
                            <div class="score-circle" style="background: <?= $scoreColor ?>;"><?= $score ?></div>
                            <div class="score-info">
                                <h3><?= $scoreLabel ?></h3>
                                <p><?= $seoData['analysis']['stats']['word_count'] ?> words</p>
                            </div>
                        </div>
                        
                        <?php if (!empty($seoData['analysis']['issues'])): ?>
                        <div class="issues-list">
                            <?php foreach ($seoData['analysis']['issues'] as $issue): ?>
                            <div class="issue issue-<?= esc($issue['type']) ?>">
                                <?php if ($issue['type'] === 'error'): ?>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                                <?php elseif ($issue['type'] === 'warning'): ?>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                                <?php else: ?>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                                <?php endif; ?>
                                <span><?= esc($issue['message']) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p style="color: #94a3b8; font-size: 0.8125rem;">No issues found!</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Recent Posts -->
                <div class="sidebar-section">
                    <div class="sidebar-title">Recent Posts</div>
                    <div class="sidebar-list">
                        <?php foreach ($recentPosts as $p): ?>
                        <a href="?post_id=<?= $p['id'] ?>" class="sidebar-item">
                            <?= esc(mb_substr($p['title'], 0, 30)) ?><?= mb_strlen($p['title']) > 30 ? '...' : '' ?>
                            <small><?= esc($p['post_type']) ?></small>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Recent Pages -->
                <?php if (!empty($recentPages)): ?>
                <div class="sidebar-section">
                    <div class="sidebar-title">Recent Pages</div>
                    <div class="sidebar-list">
                        <?php foreach ($recentPages as $p): ?>
                        <a href="?post_id=<?= $p['id'] ?>" class="sidebar-item">
                            <?= esc(mb_substr($p['title'], 0, 30)) ?><?= mb_strlen($p['title']) > 30 ? '...' : '' ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Quick Actions -->
                <div class="sidebar-section">
                    <div class="sidebar-title">Quick Actions</div>
                    <div class="sidebar-list">
                        <a href="<?= SITE_URL ?>/sitemap.xml" target="_blank" class="sidebar-item">View Sitemap</a>
                        <a href="<?= SITE_URL ?>/robots.txt" target="_blank" class="sidebar-item">View Robots.txt</a>
                        <a href="<?= ADMIN_URL ?>/seo-settings.php" class="sidebar-item">SEO Settings</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
