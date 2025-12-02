<?php
/**
 * Admin Dashboard - Forge CMS v1.0.3
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
User::requireLogin();

$pageTitle = 'Dashboard';
$currentUser = User::current();

// Get stats
$postsCount = Post::count(['post_type' => 'post', 'status' => 'published']);
$pagesCount = Post::count(['post_type' => 'page', 'status' => 'published']);
$draftsCount = Post::count(['status' => 'draft']);
$mediaCount = Media::count();
$usersCount = (int)Database::queryValue("SELECT COUNT(*) FROM users");

// Get recent posts
$recentPosts = Post::query([
    'post_type' => 'post',
    'status' => ['published', 'draft'],
    'limit' => 5,
]);

// Get recent pages
$recentPages = Post::query([
    'post_type' => 'page',
    'status' => ['published', 'draft'],
    'limit' => 3,
]);

// Get recent media
$recentMedia = Media::query(['limit' => 6]);

include ADMIN_PATH . '/includes/header.php';
?>

<div class="page-header mb-4">
    <h2>Welcome back, <?= esc($currentUser['display_name'] ?? $currentUser['username']) ?>!</h2>
    <p class="text-secondary">Here's what's happening with your site today.</p>
</div>

<!-- Quick Actions -->
<div class="quick-actions">
    <a href="<?= ADMIN_URL ?>/post-edit.php?type=post" class="quick-action-card">
        <div class="quick-action-icon stat-icon primary">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
        </div>
        <div class="quick-action-text">
            <strong>New Post</strong>
            <span>Write a blog post</span>
        </div>
    </a>
    <a href="<?= ADMIN_URL ?>/post-edit.php?type=page" class="quick-action-card">
        <div class="quick-action-icon stat-icon success">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
            </svg>
        </div>
        <div class="quick-action-text">
            <strong>New Page</strong>
            <span>Create a page</span>
        </div>
    </a>
    <a href="<?= ADMIN_URL ?>/media.php" class="quick-action-card">
        <div class="quick-action-icon stat-icon warning">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                <polyline points="21 15 16 10 5 21"></polyline>
            </svg>
        </div>
        <div class="quick-action-text">
            <strong>Upload Media</strong>
            <span>Add images & files</span>
        </div>
    </a>
    <a href="<?= ADMIN_URL ?>/customize.php" class="quick-action-card">
        <div class="quick-action-icon stat-icon accent">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="3"></circle>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
            </svg>
        </div>
        <div class="quick-action-text">
            <strong>Customize</strong>
            <span>Edit site appearance</span>
        </div>
    </a>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= $postsCount ?></div>
            <div class="stat-label">Published Posts</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= $pagesCount ?></div>
            <div class="stat-label">Pages</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= $draftsCount ?></div>
            <div class="stat-label">Drafts</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon danger">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                <polyline points="21 15 16 10 5 21"></polyline>
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= $mediaCount ?></div>
            <div class="stat-label">Media Files</div>
        </div>
    </div>
</div>

<div class="dashboard-grid">
    <!-- Recent Posts -->
    <div class="dashboard-main">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Posts</h3>
                <a href="<?= ADMIN_URL ?>/posts.php?type=post" class="btn btn-secondary btn-sm">View All</a>
            </div>
            <?php if (empty($recentPosts)): ?>
                <div class="card-body">
                    <div class="empty-state">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                        <h3>No posts yet</h3>
                        <p>Create your first post to get started.</p>
                        <a href="<?= ADMIN_URL ?>/post-edit.php?type=post" class="btn btn-primary">Create Post</a>
                    </div>
                </div>
            <?php else: ?>
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
                            <?php foreach ($recentPosts as $post): ?>
                                <tr>
                                    <td>
                                        <a href="<?= ADMIN_URL ?>/post-edit.php?id=<?= $post['id'] ?>" class="table-title-link">
                                            <?= esc($post['title'] ?: '(no title)') ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $post['status'] === 'published' ? 'success' : 'warning' ?>">
                                            <?= ucfirst($post['status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-muted text-sm">
                                        <?= formatDate($post['created_at'], 'M j') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="dashboard-sidebar">
        <!-- System Info -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">System Info</h3>
            </div>
            <div class="card-body">
                <div class="info-list">
                    <div class="info-row">
                        <span class="info-label">Forge CMS</span>
                        <span class="info-value"><?= CMS_VERSION ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">PHP Version</span>
                        <span class="info-value"><?= PHP_VERSION ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Users</span>
                        <span class="info-value"><?= $usersCount ?></span>
                    </div>
                    <div class="info-row no-border">
                        <span class="info-label">Your Role</span>
                        <span class="badge badge-info"><?= ucfirst($currentUser['role']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Pages -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Pages</h3>
                <a href="<?= ADMIN_URL ?>/posts.php?type=page" class="btn btn-secondary btn-sm">All</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentPages)): ?>
                    <div class="empty-message">No pages created yet</div>
                <?php else: ?>
                    <div class="page-list">
                        <?php foreach ($recentPages as $page): ?>
                        <a href="<?= ADMIN_URL ?>/post-edit.php?id=<?= $page['id'] ?>" class="page-list-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                            </svg>
                            <span><?= esc($page['title'] ?: '(no title)') ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($recentMedia)): ?>
<!-- Recent Media -->
<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title">Recent Media</h3>
        <a href="<?= ADMIN_URL ?>/media.php" class="btn btn-secondary btn-sm">View Library</a>
    </div>
    <div class="card-body">
        <div class="media-preview-grid">
            <?php foreach ($recentMedia as $item): ?>
            <a href="<?= ADMIN_URL ?>/media.php" class="media-preview-item">
                <?php if (strpos($item['mime_type'], 'image/') === 0): ?>
                <img src="<?= esc($item['url']) ?>" alt="">
                <?php else: ?>
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
