<?php
/**
 * Post Types List - VoidForge CMS
 */

define('CMS_ROOT', dirname(__DIR__));
require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/user.php';
require_once CMS_ROOT . '/includes/post.php';

Post::init();
User::startSession();
User::requireRole('admin');

$currentPage = 'post-types';
$pageTitle = 'Post Types';

// Get custom post types
$customPostTypes = getOption('custom_post_types');
if (!is_array($customPostTypes)) {
    $customPostTypes = [];
}

$error = '';
$success = '';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (verifyCsrf($_POST['csrf_token'] ?? '')) {
        $slug = $_POST['slug'] ?? '';
        if (isset($customPostTypes[$slug])) {
            // Check for posts
            $table = Database::table('posts');
            $count = Database::queryValue("SELECT COUNT(*) FROM {$table} WHERE post_type = ?", [$slug]);
            
            if ($count > 0) {
                $error = "Cannot delete \"{$customPostTypes[$slug]['label_plural']}\": {$count} " . ($count == 1 ? 'post is' : 'posts are') . " using this type. Please delete or reassign them first.";
            } else {
                $deletedName = $customPostTypes[$slug]['label_plural'] ?? $slug;
                unset($customPostTypes[$slug]);
                setOption('custom_post_types', $customPostTypes);
                $success = "Post type \"{$deletedName}\" deleted successfully.";
            }
        }
    }
}

// Check for URL params
if (isset($_GET['deleted'])) {
    $success = 'Post type deleted successfully.';
}
if (isset($_GET['saved'])) {
    $success = 'Post type saved successfully.';
}

// Get post counts
$postCounts = [];
foreach ($customPostTypes as $slug => $config) {
    $table = Database::table('posts');
    $postCounts[$slug] = (int) Database::queryValue("SELECT COUNT(*) FROM {$table} WHERE post_type = ?", [$slug]);
}

include ADMIN_PATH . '/includes/header.php';
?>

<style>
.pt-page { max-width: 1000px; margin: 0 auto; }

.pt-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 2rem;
}

.pt-header h1 { font-size: 1.75rem; font-weight: 700; color: #1e293b; margin: 0; }

.btn-new {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, var(--forge-primary, #6366f1), var(--forge-secondary, #8b5cf6));
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 0.9375rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-new:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
}

.alert {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    font-size: 0.9375rem;
}

.alert-success { 
    background: rgba(16,185,129,0.1); 
    color: #059669; 
    border: 1px solid rgba(16,185,129,0.2); 
}

.alert-error { 
    background: rgba(239,68,68,0.1); 
    color: #dc2626; 
    border: 1px solid rgba(239,68,68,0.2); 
}

/* Built-in section */
.section-label {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #94a3b8;
    margin-bottom: 0.75rem;
}

.builtin-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-bottom: 2.5rem;
}

.builtin-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
}

.builtin-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.builtin-card h4 { margin: 0 0 0.25rem 0; font-size: 0.9375rem; color: #1e293b; }
.builtin-card p { margin: 0; font-size: 0.8125rem; color: #64748b; }
.builtin-badge { margin-left: auto; font-size: 0.6875rem; padding: 0.25rem 0.625rem; background: #e2e8f0; border-radius: 9999px; color: #64748b; }

/* Custom post types table */
.pt-table {
    width: 100%;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
}

.pt-table th {
    text-align: left;
    padding: 1rem 1.25rem;
    background: #f8fafc;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
    border-bottom: 1px solid #e2e8f0;
}

.pt-table td {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

.pt-table tr:last-child td { border-bottom: none; }

.pt-table tr:hover td { background: #fafbfc; }

.pt-name {
    display: flex;
    align-items: center;
    gap: 0.875rem;
}

.pt-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: rgba(99,102,241,0.1);
    color: #6366f1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.pt-label { font-weight: 600; color: #1e293b; }
.pt-slug { font-size: 0.8125rem; color: #94a3b8; font-family: monospace; }

.pt-badge {
    display: inline-block;
    font-size: 0.6875rem;
    padding: 0.25rem 0.625rem;
    border-radius: 6px;
    background: #f1f5f9;
    color: #64748b;
}

.pt-badge.public { background: rgba(16,185,129,0.1); color: #10b981; }

.pt-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

.pt-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    padding: 0.5rem 0.875rem;
    border-radius: 8px;
    font-size: 0.8125rem;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.15s;
}

.pt-btn-edit { background: #f1f5f9; color: #475569; border: none; }
.pt-btn-edit:hover { background: #e2e8f0; }

.pt-btn-view { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; border: none; }
.pt-btn-view:hover { box-shadow: 0 4px 12px rgba(99,102,241,0.3); }

.pt-btn-delete {
    width: 34px;
    padding: 0.5rem;
    background: none;
    color: #94a3b8;
    border: 1px solid #e2e8f0;
    cursor: pointer;
}

.pt-btn-delete:hover { color: #ef4444; border-color: #ef4444; }

/* Empty state */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: #f8fafc;
    border: 2px dashed #e2e8f0;
    border-radius: 16px;
}

.empty-state > svg { width: 48px; height: 48px; color: var(--text-muted); }
.empty-state h3 { font-size: 1.125rem; color: #1e293b; margin: 0 0 0.5rem 0; }
.empty-state p { color: #64748b; margin: 0 0 1.5rem 0; }

.empty-state .btn-new {
    display: inline-flex;
    padding: 0.625rem 1.25rem;
    font-size: 0.875rem;
}

.empty-state .btn-new svg {
    width: 16px;
    height: 16px;
    color: #fff;
}

@media (max-width: 768px) {
    .pt-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
    .builtin-grid { grid-template-columns: 1fr; }
    .pt-table { display: block; overflow-x: auto; }
}
</style>

<div class="pt-page">
    <div class="pt-header">
        <h1>Post Types</h1>
        <a href="post-type-edit.php" class="btn-new">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            New Post Type
        </a>
    </div>
    
    <?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= esc($success) ?></div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
    <div class="alert alert-error">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0;">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <span><?= esc($error) ?></span>
    </div>
    <?php endif; ?>
    
    <!-- Built-in Types -->
    <div class="section-label">Built-in Types</div>
    <div class="builtin-grid">
        <div class="builtin-card">
            <div class="builtin-icon" style="background: rgba(99,102,241,0.1); color: #6366f1;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                </svg>
            </div>
            <div>
                <h4>Posts</h4>
                <p>Blog posts and articles</p>
            </div>
            <span class="builtin-badge">Built-in</span>
        </div>
        <div class="builtin-card">
            <div class="builtin-icon" style="background: rgba(16,185,129,0.1); color: #10b981;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                    <polyline points="13 2 13 9 20 9"></polyline>
                </svg>
            </div>
            <div>
                <h4>Pages</h4>
                <p>Static content pages</p>
            </div>
            <span class="builtin-badge">Built-in</span>
        </div>
    </div>
    
    <!-- Custom Post Types -->
    <div class="section-label">Custom Post Types (<?= count($customPostTypes) ?>)</div>
    
    <?php if (empty($customPostTypes)): ?>
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
            <line x1="12" y1="18" x2="12" y2="12"></line>
            <line x1="9" y1="15" x2="15" y2="15"></line>
        </svg>
        <h3>No custom post types yet</h3>
        <p>Create your first custom post type to organize different types of content</p>
        <a href="post-type-edit.php" class="btn-new">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Create Post Type
        </a>
    </div>
    <?php else: ?>
    <table class="pt-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Posts</th>
                <th>Fields</th>
                <th>Status</th>
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($customPostTypes as $slug => $config): ?>
            <tr>
                <td>
                    <div class="pt-name">
                        <div class="pt-icon">
                            <?= getAdminMenuIcon($config['icon'] ?? 'file', 20) ?>
                        </div>
                        <div>
                            <div class="pt-label"><?= esc($config['label_plural'] ?? $slug) ?></div>
                            <div class="pt-slug"><?= esc($slug) ?></div>
                        </div>
                    </div>
                </td>
                <td><?= $postCounts[$slug] ?? 0 ?></td>
                <td><?= count($config['fields'] ?? []) ?></td>
                <td>
                    <?php if ($config['public'] ?? true): ?>
                    <span class="pt-badge public">Public</span>
                    <?php else: ?>
                    <span class="pt-badge">Private</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="pt-actions">
                        <a href="post-type-edit.php?slug=<?= esc($slug) ?>" class="pt-btn pt-btn-edit">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                            Edit
                        </a>
                        <a href="posts.php?type=<?= esc($slug) ?>" class="pt-btn pt-btn-view">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            View
                        </a>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this post type?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="slug" value="<?= esc($slug) ?>">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <button type="submit" class="pt-btn pt-btn-delete" title="Delete">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
