<?php
/**
 * Admin Dashboard - VoidForge CMS
 * Modern, clean dashboard design
 */

define('CMS_ROOT', dirname(__DIR__));
require_once CMS_ROOT . '/includes/config.php';

/**
 * Show install required page and exit
 */
function showInstallRequired() {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Install Required - VoidForge CMS</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: #f1f5f9;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 2rem;
            }
            .box { background: #fff; border-radius: 12px; padding: 2rem; text-align: center; max-width: 400px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
            h1 { font-size: 1.25rem; color: #1e293b; margin-bottom: 0.5rem; }
            p { color: #64748b; margin-bottom: 1.5rem; }
            .btn { display: inline-block; padding: 0.75rem 1.5rem; background: #6366f1; color: #fff; text-decoration: none; border-radius: 8px; font-weight: 500; }
            .btn:hover { background: #4f46e5; }
        </style>
    </head>
    <body>
        <div class="box">
            <h1>Installation Required</h1>
            <p>VoidForge CMS is not installed yet. Please run the installer first.</p>
            <a href="../install.php" class="btn">Go to Installer</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Check if CMS is installed - config check
if (!defined('DB_NAME') || DB_NAME === '' || !defined('DB_HOST') || DB_HOST === '') {
    showInstallRequired();
}

// Try to connect to database - if it fails, show installer
try {
    require_once CMS_ROOT . '/includes/database.php';
    // Test connection by checking if users table exists
    Database::query("SELECT 1 FROM " . Database::table('users') . " LIMIT 1");
} catch (Exception $e) {
    showInstallRequired();
}
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/user.php';
require_once CMS_ROOT . '/includes/post.php';
require_once CMS_ROOT . '/includes/media.php';
require_once CMS_ROOT . '/includes/plugin.php';
require_once CMS_ROOT . '/includes/comment.php';

Post::init();

User::startSession();
User::requireLogin();

$pageTitle = 'Dashboard';
$currentPage = 'dashboard';
$currentUser = User::current();

Plugin::doAction('dashboard_setup');

// Get stats
$stats = [
    'posts' => Post::count(['post_type' => 'post', 'status' => 'published']),
    'pages' => Post::count(['post_type' => 'page', 'status' => 'published']),
    'drafts' => Post::count(['status' => 'draft']),
    'media' => Media::count(),
    'users' => (int)Database::queryValue("SELECT COUNT(*) FROM " . Database::table('users')),
    'comments' => Comment::count(['status' => 'approved']),
    'pending' => Comment::count(['status' => 'pending']),
];

// Recent content
$recentPosts = Post::query(['post_type' => 'post', 'status' => ['published', 'draft'], 'limit' => 5]);
$recentPages = Post::query(['post_type' => 'page', 'status' => ['published', 'draft'], 'limit' => 5]);
$recentComments = Comment::query(['limit' => 4]);
$recentMedia = Media::query(['limit' => 6]);

// Greeting
$hour = (int)date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

include ADMIN_PATH . '/includes/header.php';
?>

<style>
.dash { padding: 0; }

/* Hero Section */
.dash-hero {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a855f7 100%);
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 1.5rem;
    position: relative;
    overflow: hidden;
}
.dash-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 60%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    pointer-events: none;
}
.dash-hero-content {
    position: relative;
    z-index: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}
.dash-hero h1 {
    color: white;
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0 0 0.25rem;
}
.dash-hero p {
    color: rgba(255,255,255,0.8);
    margin: 0;
    font-size: 0.9375rem;
}
.dash-hero-actions {
    display: flex;
    gap: 0.75rem;
}
.btn-hero {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    background: rgba(255,255,255,0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.3);
    border-radius: 8px;
    color: white;
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s;
}
.btn-hero:hover {
    background: rgba(255,255,255,0.3);
    text-decoration: none;
    color: white;
}
.btn-hero svg { width: 18px; height: 18px; }
.btn-hero-primary {
    background: white;
    color: #6366f1;
    border-color: white;
}
.btn-hero-primary:hover {
    background: #f8fafc;
    color: #4f46e5;
}

/* Stats Row */
.stats-row {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}
@media (max-width: 1200px) { .stats-row { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 640px) { .stats-row { grid-template-columns: repeat(2, 1fr); } }

.stat-box {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.25rem;
    text-align: center;
    transition: all 0.2s;
}
.stat-box:hover {
    border-color: var(--forge-primary);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.1);
}
.stat-num {
    font-size: 2rem;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 0.25rem;
}
.stat-num.blue { color: #6366f1; }
.stat-num.green { color: #10b981; }
.stat-num.amber { color: #f59e0b; }
.stat-num.pink { color: #ec4899; }
.stat-num.cyan { color: #06b6d4; }
.stat-num.slate { color: #64748b; }
.stat-lbl {
    font-size: 0.75rem;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 600;
}

/* Quick Actions */
.quick-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}
@media (max-width: 900px) { .quick-row { grid-template-columns: repeat(2, 1fr); } }

.quick-btn {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    text-decoration: none;
    transition: all 0.2s;
}
.quick-btn:hover {
    border-color: var(--forge-primary);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.1);
    text-decoration: none;
}
.quick-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.quick-icon svg { width: 24px; height: 24px; }
.quick-icon.blue { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; }
.quick-icon.green { background: linear-gradient(135deg, #10b981, #34d399); color: white; }
.quick-icon.amber { background: linear-gradient(135deg, #f59e0b, #fbbf24); color: white; }
.quick-icon.pink { background: linear-gradient(135deg, #ec4899, #f472b6); color: white; }
.quick-info h3 {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 0.125rem;
}
.quick-info span {
    font-size: 0.8125rem;
    color: var(--text-muted);
}

/* Main Grid */
.dash-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1.5rem;
}
@media (max-width: 1024px) { .dash-grid { grid-template-columns: 1fr; } }

/* Cards */
.dash-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 1.5rem;
}
.dash-card:last-child { margin-bottom: 0; }
.dash-card-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border-color);
}
.dash-card-head h2 {
    font-size: 0.9375rem;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-primary);
}
.dash-card-head h2 svg {
    width: 18px;
    height: 18px;
    color: var(--forge-primary);
}
.link-all {
    font-size: 0.8125rem;
    color: var(--forge-primary);
    text-decoration: none;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}
.link-all:hover { text-decoration: underline; }
.link-all svg { width: 16px; height: 16px; }

/* Content List */
.content-list { list-style: none; margin: 0; padding: 0; }
.content-list li {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.875rem 1.25rem;
    border-bottom: 1px solid var(--border-color);
    transition: background 0.15s;
}
.content-list li:last-child { border-bottom: none; }
.content-list li:hover { background: var(--bg-hover); }
.content-list .title {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text-primary);
    text-decoration: none;
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-right: 1rem;
}
.content-list .title:hover { color: var(--forge-primary); }
.content-list .meta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-shrink: 0;
}
.content-list .date {
    font-size: 0.75rem;
    color: var(--text-muted);
}
.pill {
    display: inline-flex;
    padding: 0.1875rem 0.5rem;
    border-radius: 9999px;
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}
.pill.green { background: #d1fae5; color: #059669; }
.pill.amber { background: #fef3c7; color: #d97706; }
.pill.blue { background: #dbeafe; color: #2563eb; }

/* Empty State */
.empty-box {
    padding: 3rem 1.5rem;
    text-align: center;
}
.empty-box svg {
    width: 48px;
    height: 48px;
    color: #FFFFFF;
    }
.empty-box p {
    color: var(--text-muted);
    margin: 0 0 1rem;
    font-size: 0.875rem;
}
.empty-box .btn-sm {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 1rem;
    background: var(--forge-primary);
    color: white;
    border-radius: 6px;
    font-size: 0.8125rem;
    font-weight: 500;
    text-decoration: none;
}
.empty-box .btn-sm:hover { opacity: 0.9; }
.empty-box .btn-sm svg { width: 14px; height: 14px; }

/* Comment List */
.comment-list { list-style: none; margin: 0; padding: 0; }
.comment-list li {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border-color);
}
.comment-list li:last-child { border-bottom: none; }
.comment-list li:hover { background: var(--bg-hover); }
.cmt-head {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    margin-bottom: 0.5rem;
}
.cmt-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 0.875rem;
    flex-shrink: 0;
}
.cmt-info { flex: 1; min-width: 0; }
.cmt-author {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-primary);
}
.cmt-date {
    font-size: 0.75rem;
    color: var(--text-muted);
}
.cmt-text {
    font-size: 0.8125rem;
    color: var(--text-secondary);
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.cmt-post {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin-top: 0.5rem;
}
.cmt-post a { color: var(--forge-primary); text-decoration: none; }
.cmt-post a:hover { text-decoration: underline; }

/* Media Grid */
.media-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
    padding: 1rem 1.25rem;
}
.media-thumb {
    aspect-ratio: 1;
    border-radius: 8px;
    overflow: hidden;
    background: var(--bg-body);
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--border-color);
}
.media-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.media-thumb svg {
    width: 24px;
    height: 24px;
    color: var(--text-muted);
}

/* Info Card */
.info-rows { padding: 0; }
.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1.25rem;
    border-bottom: 1px solid var(--border-color);
}
.info-row:last-child { border-bottom: none; }
.info-row .lbl {
    font-size: 0.8125rem;
    color: var(--text-muted);
}
.info-row .val {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text-primary);
}
.info-row .badge {
    display: inline-flex;
    padding: 0.25rem 0.625rem;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
}

/* Activity */
.activity-list { list-style: none; margin: 0; padding: 0; }
.activity-list li {
    display: flex;
    gap: 0.875rem;
    padding: 0.875rem 1.25rem;
    border-bottom: 1px solid var(--border-color);
}
.activity-list li:last-child { border-bottom: none; }
.activity-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.activity-icon svg { width: 16px; height: 16px; }
.activity-icon.blue { background: rgba(99, 102, 241, 0.1); color: #6366f1; }
.activity-icon.green { background: rgba(16, 185, 129, 0.1); color: #10b981; }
.activity-icon.amber { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
.activity-icon.pink { background: rgba(236, 72, 153, 0.1); color: #ec4899; }
.activity-content { flex: 1; min-width: 0; }
.activity-text {
    font-size: 0.8125rem;
    color: var(--text-primary);
    line-height: 1.4;
}
.activity-text strong { font-weight: 600; }
.activity-time {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin-top: 0.125rem;
}
</style>

<div class="dash">
    
    <!-- Hero -->
    <div class="dash-hero">
        <div class="dash-hero-content">
            <div>
                <h1><?= $greeting ?>, <?= esc($currentUser['display_name'] ?? $currentUser['username']) ?>!</h1>
                <p><?= date('l, F j, Y') ?> — Here's your site overview</p>
            </div>
            <div class="dash-hero-actions">
                <a href="<?= SITE_URL ?>" target="_blank" class="btn-hero">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    View Site
                </a>
                <a href="<?= ADMIN_URL ?>/post-edit.php?type=post" class="btn-hero btn-hero-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    Write Post
                </a>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-box">
            <div class="stat-num blue"><?= $stats['posts'] ?></div>
            <div class="stat-lbl">Posts</div>
        </div>
        <div class="stat-box">
            <div class="stat-num green"><?= $stats['pages'] ?></div>
            <div class="stat-lbl">Pages</div>
        </div>
        <div class="stat-box">
            <div class="stat-num amber"><?= $stats['drafts'] ?></div>
            <div class="stat-lbl">Drafts</div>
        </div>
        <div class="stat-box">
            <div class="stat-num pink"><?= $stats['media'] ?></div>
            <div class="stat-lbl">Media</div>
        </div>
        <div class="stat-box">
            <div class="stat-num cyan"><?= $stats['comments'] ?></div>
            <div class="stat-lbl">Comments</div>
        </div>
        <div class="stat-box">
            <div class="stat-num slate"><?= $stats['users'] ?></div>
            <div class="stat-lbl">Users</div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-row">
        <a href="<?= ADMIN_URL ?>/post-edit.php?type=post" class="quick-btn">
            <div class="quick-icon blue">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            </div>
            <div class="quick-info">
                <h3>New Post</h3>
                <span>Create content</span>
            </div>
        </a>
        <a href="<?= ADMIN_URL ?>/post-edit.php?type=page" class="quick-btn">
            <div class="quick-icon green">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
            </div>
            <div class="quick-info">
                <h3>New Page</h3>
                <span>Add a page</span>
            </div>
        </a>
        <a href="<?= ADMIN_URL ?>/media.php" class="quick-btn">
            <div class="quick-icon amber">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            </div>
            <div class="quick-info">
                <h3>Upload</h3>
                <span>Add media files</span>
            </div>
        </a>
        <a href="<?= ADMIN_URL ?>/settings.php" class="quick-btn">
            <div class="quick-icon pink">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            </div>
            <div class="quick-info">
                <h3>Settings</h3>
                <span>Configure site</span>
            </div>
        </a>
    </div>

    <!-- Main Grid -->
    <div class="dash-grid">
        <div class="dash-left">
            <!-- Recent Posts -->
            <div class="dash-card">
                <div class="dash-card-head">
                    <h2>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                        Recent Posts
                    </h2>
                    <a href="<?= ADMIN_URL ?>/posts.php?type=post" class="link-all">
                        View all
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                </div>
                <?php if (empty($recentPosts)): ?>
                    <div class="empty-box">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                        <p>No posts yet</p>
                        <a href="<?= ADMIN_URL ?>/post-edit.php?type=post" class="btn-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Create Post
                        </a>
                    </div>
                <?php else: ?>
                    <ul class="content-list">
                        <?php foreach ($recentPosts as $post): ?>
                            <li>
                                <a href="<?= ADMIN_URL ?>/post-edit.php?id=<?= $post['id'] ?>" class="title"><?= esc($post['title'] ?: '(no title)') ?></a>
                                <div class="meta">
                                    <span class="date"><?= formatDate($post['created_at'], 'M j') ?></span>
                                    <span class="pill <?= $post['status'] === 'published' ? 'green' : 'amber' ?>"><?= $post['status'] ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <!-- Recent Pages -->
            <div class="dash-card">
                <div class="dash-card-head">
                    <h2>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        Recent Pages
                    </h2>
                    <a href="<?= ADMIN_URL ?>/posts.php?type=page" class="link-all">
                        View all
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                </div>
                <?php if (empty($recentPages)): ?>
                    <div class="empty-box">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        <p>No pages yet</p>
                        <a href="<?= ADMIN_URL ?>/post-edit.php?type=page" class="btn-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Create Page
                        </a>
                    </div>
                <?php else: ?>
                    <ul class="content-list">
                        <?php foreach ($recentPages as $page): ?>
                            <li>
                                <a href="<?= ADMIN_URL ?>/post-edit.php?id=<?= $page['id'] ?>" class="title"><?= esc($page['title'] ?: '(no title)') ?></a>
                                <div class="meta">
                                    <span class="date"><?= formatDate($page['created_at'], 'M j') ?></span>
                                    <span class="pill <?= $page['status'] === 'published' ? 'green' : 'amber' ?>"><?= $page['status'] ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="dash-right">
            <!-- Comments -->
            <?php if (!empty($recentComments)): ?>
            <div class="dash-card">
                <div class="dash-card-head">
                    <h2>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                        Comments
                        <?php if ($stats['pending'] > 0): ?>
                            <span class="pill amber" style="margin-left: 0.5rem;"><?= $stats['pending'] ?> pending</span>
                        <?php endif; ?>
                    </h2>
                    <a href="<?= ADMIN_URL ?>/comments.php" class="link-all">
                        View
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                </div>
                <ul class="comment-list">
                    <?php foreach ($recentComments as $comment): 
                        $post = Post::find($comment['post_id']);
                        $initial = strtoupper(substr($comment['author_name'] ?? 'A', 0, 1));
                    ?>
                        <li>
                            <div class="cmt-head">
                                <div class="cmt-avatar"><?= $initial ?></div>
                                <div class="cmt-info">
                                    <div class="cmt-author"><?= esc($comment['author_name'] ?? 'Anonymous') ?></div>
                                    <div class="cmt-date"><?= formatDate($comment['created_at'] ?? '', 'M j, Y') ?></div>
                                </div>
                            </div>
                            <div class="cmt-text"><?= esc(substr(strip_tags($comment['content'] ?? ''), 0, 100)) ?></div>
                            <?php if ($post): ?>
                                <div class="cmt-post">on <a href="<?= ADMIN_URL ?>/post-edit.php?id=<?= $post['id'] ?>"><?= esc($post['title']) ?></a></div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Media -->
            <?php if (!empty($recentMedia)): ?>
            <div class="dash-card">
                <div class="dash-card-head">
                    <h2>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        Media
                    </h2>
                    <a href="<?= ADMIN_URL ?>/media.php" class="link-all">
                        Library
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                </div>
                <div class="media-grid">
                    <?php foreach ($recentMedia as $item): ?>
                        <div class="media-thumb">
                            <?php if (strpos($item['mime_type'], 'image/') === 0): ?>
                                <img src="<?= esc($item['url']) ?>" alt="">
                            <?php else: ?>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- System Info -->
            <div class="dash-card">
                <div class="dash-card-head">
                    <h2>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                        System
                    </h2>
                </div>
                <div class="info-rows">
                    <div class="info-row">
                        <span class="lbl">VoidForge</span>
                        <span class="val">v<?= CMS_VERSION ?></span>
                    </div>
                    <div class="info-row">
                        <span class="lbl">PHP</span>
                        <span class="val"><?= PHP_VERSION ?></span>
                    </div>
                    <div class="info-row">
                        <span class="lbl">Your Role</span>
                        <span class="badge"><?= ucfirst($currentUser['role']) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
