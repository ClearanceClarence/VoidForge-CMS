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
require_once CMS_ROOT . '/includes/plugin.php';

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
            $table = Database::table('posts');
            $count = Database::queryValue("SELECT COUNT(*) FROM {$table} WHERE post_type = ?", [$slug]);
            
            if ($count > 0) {
                $error = "Cannot delete \"{$customPostTypes[$slug]['label_plural']}\": {$count} " . ($count == 1 ? 'post is' : 'posts are') . " using this type.";
            } else {
                $deletedName = $customPostTypes[$slug]['label_plural'] ?? $slug;
                unset($customPostTypes[$slug]);
                setOption('custom_post_types', $customPostTypes);
                $success = "Post type \"{$deletedName}\" deleted successfully.";
            }
        }
    }
}

if (isset($_GET['deleted'])) $success = 'Post type deleted successfully.';
if (isset($_GET['saved'])) $success = 'Post type saved successfully.';

// Get post counts
$postCounts = [];
foreach ($customPostTypes as $slug => $config) {
    $table = Database::table('posts');
    $postCounts[$slug] = (int) Database::queryValue("SELECT COUNT(*) FROM {$table} WHERE post_type = ?", [$slug]);
}

include ADMIN_PATH . '/includes/header.php';
?>

<div class="structure-page">
    <div class="structure-header">
        <h1>Post Types</h1>
        <a href="post-type-edit.php" class="btn-primary-action">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
    <div class="alert alert-error"><?= esc($error) ?></div>
    <?php endif; ?>
    
    <!-- Built-in Types -->
    <div class="section-label">Built-in Types</div>
    <div class="builtin-grid">
        <div class="builtin-card">
            <div class="builtin-icon" style="background: rgba(99,102,241,0.1); color: #6366f1;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                </svg>
            </div>
            <div class="builtin-info">
                <h4>Posts</h4>
                <p>Blog posts and articles</p>
            </div>
            <span class="builtin-badge">Built-in</span>
        </div>
        <div class="builtin-card">
            <div class="builtin-icon" style="background: rgba(16,185,129,0.1); color: #10b981;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                    <polyline points="13 2 13 9 20 9"></polyline>
                </svg>
            </div>
            <div class="builtin-info">
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
        <h2>No Custom Post Types</h2>
        <p>Create your first custom post type to organize different types of content.</p>
        <a href="post-type-edit.php" class="btn-primary-action">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Create Post Type
        </a>
    </div>
    <?php else: ?>
    <table class="data-table">
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
                    <div class="item-card-name">
                        <div class="item-card-icon">
                            <?= getAdminMenuIcon($config['icon'] ?? 'file', 20) ?>
                        </div>
                        <div>
                            <div class="item-card-label"><?= esc($config['label_plural'] ?? $slug) ?></div>
                            <div class="item-card-slug"><?= esc($slug) ?></div>
                        </div>
                    </div>
                </td>
                <td><?= $postCounts[$slug] ?? 0 ?></td>
                <td><?= count($config['fields'] ?? []) ?></td>
                <td>
                    <?php if ($config['public'] ?? true): ?>
                    <span class="status-badge success">Public</span>
                    <?php else: ?>
                    <span class="status-badge">Private</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="item-actions" style="justify-content: flex-end;">
                        <a href="post-type-edit.php?slug=<?= esc($slug) ?>" class="item-btn item-btn-text">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                            Edit
                        </a>
                        <a href="posts.php?type=<?= esc($slug) ?>" class="item-btn item-btn-text item-btn-primary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            View
                        </a>
                        <button type="button" class="item-btn delete" onclick="confirmDelete('<?= esc($slug) ?>', '<?= esc(addslashes($config['label_plural'] ?? $slug)) ?>')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
        <h3>Delete Post Type?</h3>
        <p>Are you sure you want to delete "<span id="deleteTypeName"></span>"? This cannot be undone.</p>
        <div class="modal-actions">
            <button type="button" class="btn-modal-cancel" onclick="closeDeleteModal()">Cancel</button>
            <form method="POST" id="deleteForm" style="display: inline;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="slug" id="deleteSlug">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <button type="submit" class="btn-modal-danger">Delete</button>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDelete(slug, name) {
    document.getElementById('deleteSlug').value = slug;
    document.getElementById('deleteTypeName').textContent = name;
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
}

document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeDeleteModal();
});
</script>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
