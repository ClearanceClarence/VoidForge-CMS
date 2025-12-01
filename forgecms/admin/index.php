<?php
/**
 * Admin Dashboard - Forge CMS
 */

define('CMS_ROOT', dirname(__DIR__));
require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/user.php';
require_once CMS_ROOT . '/includes/post.php';
require_once CMS_ROOT . '/includes/media.php';

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

<div class="page-header" style="margin-bottom: 2rem;">
    <h2>Welcome back, <?= esc($currentUser['display_name'] ?? $currentUser['username']) ?>!</h2>
    <p style="color: var(--text-secondary); margin-top: 0.25rem;">Here's what's happening with your site today.</p>
</div>

<!-- Quick Actions -->
<div class="quick-actions" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
    <a href="<?= ADMIN_URL ?>/post-edit.php?type=post" class="quick-action-card">
        <div class="quick-action-icon" style="background: rgba(99, 102, 241, 0.1); color: var(--forge-primary);">
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
        <div class="quick-action-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--forge-success);">
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
        <div class="quick-action-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--forge-warning);">
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
        <div class="quick-action-icon" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
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

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-top: 1.5rem;">
    <!-- Recent Posts -->
    <div class="card">
        <div class="card-header" style="display: flex; align-items: center; justify-content: space-between;">
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
                                    <a href="<?= ADMIN_URL ?>/post-edit.php?id=<?= $post['id'] ?>" style="font-weight: 500;">
                                        <?= esc($post['title'] ?: '(no title)') ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $post['status'] === 'published' ? 'success' : 'warning' ?>">
                                        <?= ucfirst($post['status']) ?>
                                    </span>
                                </td>
                                <td style="color: var(--text-muted); font-size: 0.8125rem;">
                                    <?= formatDate($post['created_at'], 'M j') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <!-- System Info -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">System Info</h3>
            </div>
            <div class="card-body" style="font-size: 0.875rem;">
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--border-color);">
                    <span style="color: var(--text-secondary);">Forge CMS</span>
                    <span style="font-weight: 600;"><?= CMS_VERSION ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--border-color);">
                    <span style="color: var(--text-secondary);">PHP Version</span>
                    <span style="font-weight: 600;"><?= PHP_VERSION ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--border-color);">
                    <span style="color: var(--text-secondary);">Users</span>
                    <span style="font-weight: 600;"><?= $usersCount ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                    <span style="color: var(--text-secondary);">Your Role</span>
                    <span class="badge badge-info"><?= ucfirst($currentUser['role']) ?></span>
                </div>
            </div>
        </div>

        <!-- Recent Pages -->
        <div class="card">
            <div class="card-header" style="display: flex; align-items: center; justify-content: space-between;">
                <h3 class="card-title">Pages</h3>
                <a href="<?= ADMIN_URL ?>/posts.php?type=page" class="btn btn-secondary btn-sm">All</a>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (empty($recentPages)): ?>
                    <div style="padding: 1.5rem; text-align: center; color: var(--text-muted);">
                        No pages created yet
                    </div>
                <?php else: ?>
                    <?php foreach ($recentPages as $page): ?>
                    <a href="<?= ADMIN_URL ?>/post-edit.php?id=<?= $page['id'] ?>" style="display: flex; align-items: center; padding: 0.75rem 1.5rem; border-bottom: 1px solid var(--border-color); text-decoration: none; color: var(--text-primary); transition: background 0.15s;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.75rem; color: var(--text-muted);">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                        <span style="font-size: 0.875rem;"><?= esc($page['title'] ?: '(no title)') ?></span>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($recentMedia)): ?>
<!-- Recent Media -->
<div class="card" style="margin-top: 1.5rem;">
    <div class="card-header" style="display: flex; align-items: center; justify-content: space-between;">
        <h3 class="card-title">Recent Media</h3>
        <a href="<?= ADMIN_URL ?>/media.php" class="btn btn-secondary btn-sm">View Library</a>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 1rem;">
            <?php foreach ($recentMedia as $item): ?>
            <a href="<?= ADMIN_URL ?>/media.php" style="aspect-ratio: 1; border-radius: var(--border-radius); overflow: hidden; background: var(--bg-card-header); display: flex; align-items: center; justify-content: center;">
                <?php if (strpos($item['mime_type'], 'image/') === 0): ?>
                <img src="<?= esc($item['url']) ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--text-muted);">
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

<style>
.quick-action-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-lg);
    text-decoration: none;
    transition: all 0.2s ease;
}

.quick-action-card:hover {
    border-color: var(--forge-primary);
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

.quick-action-icon {
    width: 48px;
    height: 48px;
    border-radius: var(--border-radius);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.quick-action-text {
    display: flex;
    flex-direction: column;
}

.quick-action-text strong {
    font-weight: 600;
    color: var(--text-primary);
}

.quick-action-text span {
    font-size: 0.8125rem;
    color: var(--text-muted);
}

@media (max-width: 1024px) {
    div[style*="grid-template-columns: 2fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}

@media (max-width: 768px) {
    div[style*="grid-template-columns: repeat(6"] {
        grid-template-columns: repeat(3, 1fr) !important;
    }
}
</style>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
