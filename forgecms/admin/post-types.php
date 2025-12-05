<?php
/**
 * Post Types Builder - Forge CMS v1.0.9
 * Create and manage custom post types with custom fields
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
User::requireRole('admin');

$currentPage = 'post-types';
$pageTitle = 'Post Types';

// Debug mode
$debug = isset($_GET['debug']);

// Get custom post types from options
$customPostTypes = getOption('custom_post_types');
if (!is_array($customPostTypes)) {
    $customPostTypes = [];
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    
    // CSRF check
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }
    
    $action = $_POST['ajax_action'];
    
    try {
        switch ($action) {
            case 'save_post_type':
                $slug = preg_replace('/[^a-z0-9_]/', '', strtolower($_POST['slug'] ?? ''));
                $labelSingular = trim($_POST['label_singular'] ?? '');
                $labelPlural = trim($_POST['label_plural'] ?? '');
                $icon = trim($_POST['icon'] ?? 'file');
                $isPublic = ($_POST['is_public'] ?? '1') === '1';
                $hasArchive = ($_POST['has_archive'] ?? '1') === '1';
                $supports = $_POST['supports'] ?? ['title', 'editor'];
                $fields = json_decode($_POST['fields'] ?? '[]', true) ?: [];
                
                if (empty($slug)) {
                    echo json_encode(['success' => false, 'error' => 'Slug is required']);
                    exit;
                }
                
                if (empty($labelSingular)) {
                    echo json_encode(['success' => false, 'error' => 'Singular label is required']);
                    exit;
                }
                
                if (empty($labelPlural)) {
                    echo json_encode(['success' => false, 'error' => 'Plural label is required']);
                    exit;
                }
                
                // Reserved slugs
                $reserved = ['post', 'page', 'attachment', 'revision', 'nav_menu_item'];
                if (in_array($slug, $reserved)) {
                    echo json_encode(['success' => false, 'error' => 'This slug is reserved']);
                    exit;
                }
                
                // Build config
                $config = [
                    'slug' => $slug,
                    'label_singular' => $labelSingular,
                    'label_plural' => $labelPlural,
                    'icon' => $icon,
                    'public' => $isPublic,
                    'has_archive' => $hasArchive,
                    'supports' => $supports,
                    'fields' => $fields,
                    'created_at' => $customPostTypes[$slug]['created_at'] ?? date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                
                $customPostTypes[$slug] = $config;
                setOption('custom_post_types', $customPostTypes);
                
                echo json_encode(['success' => true, 'message' => 'Post type saved successfully', 'slug' => $slug]);
                exit;
                
            case 'delete_post_type':
                $slug = $_POST['slug'] ?? '';
                
                if (empty($slug) || !isset($customPostTypes[$slug])) {
                    echo json_encode(['success' => false, 'error' => 'Post type not found']);
                    exit;
                }
                
                // Check if there are posts using this type
                $table = Database::table('posts');
                $count = Database::queryValue("SELECT COUNT(*) FROM {$table} WHERE post_type = ?", [$slug]);
                
                if ($count > 0) {
                    echo json_encode(['success' => false, 'error' => "Cannot delete: {$count} posts are using this type"]);
                    exit;
                }
                
                unset($customPostTypes[$slug]);
                setOption('custom_post_types', $customPostTypes);
                
                echo json_encode(['success' => true, 'message' => 'Post type deleted']);
                exit;
                
            case 'get_post_type':
                $slug = $_POST['slug'] ?? '';
                
                if (empty($slug) || !isset($customPostTypes[$slug])) {
                    echo json_encode(['success' => false, 'error' => 'Post type not found']);
                    exit;
                }
                
                echo json_encode(['success' => true, 'data' => $customPostTypes[$slug]]);
                exit;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action]);
                exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
        exit;
    }
}

// Get post counts for each type
$postCounts = [];
foreach ($customPostTypes as $slug => $config) {
    $table = Database::table('posts');
    $postCounts[$slug] = (int) Database::queryValue("SELECT COUNT(*) FROM {$table} WHERE post_type = ?", [$slug]);
}

include ADMIN_PATH . '/includes/header.php';
?>

<style>
.pt-page { max-width: 1100px; margin: 0 auto; }

.pt-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 2rem;
}

.pt-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 0.25rem 0;
}

.pt-header p {
    color: #64748b;
    margin: 0;
}

.btn-primary {
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
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
}

/* Debug Panel */
.debug-panel {
    background: #1e293b;
    color: #e2e8f0;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    font-family: monospace;
    font-size: 0.8125rem;
    max-height: 300px;
    overflow-y: auto;
}

.debug-panel h4 {
    color: #f59e0b;
    margin: 0 0 0.5rem 0;
}

.debug-log {
    color: #94a3b8;
}

.debug-log .error { color: #ef4444; }
.debug-log .success { color: #10b981; }
.debug-log .info { color: #3b82f6; }

/* Built-in Types */
.builtin-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-bottom: 2rem;
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

.builtin-card .icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.builtin-card h4 { margin: 0 0 0.25rem 0; font-size: 0.9375rem; color: #1e293b; }
.builtin-card p { margin: 0; font-size: 0.8125rem; color: #64748b; }
.builtin-card .badge { margin-left: auto; font-size: 0.6875rem; padding: 0.25rem 0.625rem; background: #e2e8f0; border-radius: 9999px; color: #64748b; }

/* Custom Post Types */
.section-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.25rem;
}

.section-title h2 { font-size: 1rem; font-weight: 600; color: #1e293b; margin: 0; }
.section-title .count { font-size: 0.75rem; padding: 0.25rem 0.625rem; background: rgba(99,102,241,0.1); color: #6366f1; border-radius: 9999px; }

.pt-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1rem;
}

.pt-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.2s;
}

.pt-card:hover {
    border-color: var(--forge-primary, #6366f1);
    box-shadow: 0 8px 25px rgba(99,102,241,0.15);
}

.pt-card-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem;
    background: #f8fafc;
    border-bottom: 1px solid #f1f5f9;
}

.pt-card-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: rgba(99,102,241,0.1);
    color: #6366f1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.pt-card-header h3 { margin: 0 0 0.25rem 0; font-size: 1rem; font-weight: 600; color: #1e293b; }
.pt-card-header code { font-size: 0.75rem; background: #e2e8f0; padding: 0.125rem 0.5rem; border-radius: 4px; color: #64748b; }

.pt-card-body { padding: 1.25rem; }

.pt-stats { display: flex; gap: 2rem; margin-bottom: 1rem; }
.pt-stat-value { font-size: 1.25rem; font-weight: 700; color: #1e293b; }
.pt-stat-label { font-size: 0.75rem; color: #94a3b8; }

.pt-badges { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem; }
.pt-badge { font-size: 0.6875rem; padding: 0.25rem 0.625rem; border-radius: 6px; background: #f1f5f9; color: #64748b; }
.pt-badge.active { background: rgba(16,185,129,0.1); color: #10b981; }

.pt-actions {
    display: flex;
    gap: 0.5rem;
    padding-top: 1rem;
    border-top: 1px solid #f1f5f9;
}

.pt-btn {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    padding: 0.625rem;
    border-radius: 8px;
    font-size: 0.8125rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}

.pt-btn-edit { background: #f1f5f9; color: #475569; border: none; }
.pt-btn-edit:hover { background: #e2e8f0; }

.pt-btn-view { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; border: none; }
.pt-btn-view:hover { box-shadow: 0 4px 12px rgba(99,102,241,0.3); }

.pt-btn-delete { flex: 0; width: 36px; background: none; color: #94a3b8; border: 1px solid #e2e8f0; }
.pt-btn-delete:hover { color: #ef4444; border-color: #ef4444; }

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: #f8fafc;
    border: 2px dashed #e2e8f0;
    border-radius: 16px;
}

.empty-state svg { width: 64px; height: 64px; color: #94a3b8; margin-bottom: 1rem; }
.empty-state h3 { font-size: 1.125rem; color: #1e293b; margin: 0 0 0.5rem 0; }
.empty-state p { color: #64748b; margin: 0 0 1.5rem 0; }

/* Modal */
.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15,23,42,0.6);
    backdrop-filter: blur(4px);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}

.modal-overlay.active { display: flex; }

.modal {
    width: 100%;
    max-width: 600px;
    max-height: 90vh;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 25px 50px rgba(0,0,0,0.25);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.modal-header h2 { font-size: 1.25rem; font-weight: 700; color: #1e293b; margin: 0; }

.modal-close {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    border: none;
    border-radius: 8px;
    color: #64748b;
    cursor: pointer;
}

.modal-close:hover { background: #e2e8f0; }

.modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1.25rem 1.5rem;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
}

/* Form */
.form-group { margin-bottom: 1.25rem; }
.form-group:last-child { margin-bottom: 0; }

.form-label {
    display: block;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #475569;
    margin-bottom: 0.5rem;
}

.form-input, .form-select {
    width: 100%;
    padding: 0.75rem 1rem;
    font-size: 0.9375rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    background: #fff;
    color: #1e293b;
    transition: all 0.2s;
    box-sizing: border-box;
}

.form-input:focus, .form-select:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}

.form-hint { font-size: 0.75rem; color: #94a3b8; margin-top: 0.375rem; }

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.checkbox-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.5rem;
}

.checkbox-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem;
    background: #f8fafc;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.875rem;
    color: #475569;
}

.checkbox-item input { accent-color: #6366f1; }

/* Icon Grid */
.icon-grid {
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    gap: 0.5rem;
    padding: 0.75rem;
    background: #f8fafc;
    border-radius: 8px;
}

.icon-option {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    cursor: pointer;
    color: #64748b;
    transition: all 0.15s;
}

.icon-option:hover { border-color: #6366f1; color: #6366f1; }
.icon-option.selected { border-color: #6366f1; background: rgba(99,102,241,0.1); color: #6366f1; }

/* Custom Fields */
.fields-list {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
}

.field-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.875rem 1rem;
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
}

.field-item:last-child { border-bottom: none; }
.field-info { flex: 1; }
.field-label { font-weight: 600; color: #1e293b; }
.field-meta { font-size: 0.75rem; color: #94a3b8; }

.field-remove {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: none;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    color: #94a3b8;
    cursor: pointer;
}

.field-remove:hover { color: #ef4444; border-color: #ef4444; }

.fields-empty {
    padding: 2rem;
    text-align: center;
    color: #94a3b8;
    font-size: 0.875rem;
}

.btn-add-field {
    width: 100%;
    padding: 0.875rem;
    background: #f8fafc;
    border: none;
    color: #6366f1;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.btn-add-field:hover { background: #f1f5f9; }

.btn-cancel {
    padding: 0.75rem 1.5rem;
    background: #f1f5f9;
    color: #475569;
    border: none;
    border-radius: 8px;
    font-size: 0.9375rem;
    font-weight: 500;
    cursor: pointer;
}

.btn-cancel:hover { background: #e2e8f0; }

.btn-save {
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 0.9375rem;
    font-weight: 600;
    cursor: pointer;
}

.btn-save:hover { box-shadow: 0 4px 12px rgba(99,102,241,0.3); }
.btn-save:disabled { opacity: 0.6; cursor: not-allowed; }

@media (max-width: 768px) {
    .pt-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
    .builtin-grid { grid-template-columns: 1fr; }
    .form-row { grid-template-columns: 1fr; }
    .checkbox-grid { grid-template-columns: repeat(2, 1fr); }
    .icon-grid { grid-template-columns: repeat(6, 1fr); }
}
</style>

<div class="pt-page">
    <div class="pt-header">
        <div>
            <h1>Post Types</h1>
            <p>Create and manage custom content types</p>
        </div>
        <button type="button" class="btn-primary" id="btnNewType">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            New Post Type
        </button>
    </div>
    
    <?php if ($debug): ?>
    <div class="debug-panel">
        <h4>🔧 Debug Mode</h4>
        <div class="debug-log" id="debugLog">
            <div class="info">[Init] Page loaded</div>
            <div class="info">[Data] CSRF Token: <?= substr(csrfToken(), 0, 16) ?>...</div>
            <div class="info">[Data] Custom Post Types: <?= count($customPostTypes) ?></div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Built-in Types -->
    <div class="builtin-grid">
        <div class="builtin-card">
            <div class="icon" style="background: rgba(99,102,241,0.1); color: #6366f1;">
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
            <span class="badge">Built-in</span>
        </div>
        <div class="builtin-card">
            <div class="icon" style="background: rgba(16,185,129,0.1); color: #10b981;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                    <polyline points="13 2 13 9 20 9"></polyline>
                </svg>
            </div>
            <div>
                <h4>Pages</h4>
                <p>Static content pages</p>
            </div>
            <span class="badge">Built-in</span>
        </div>
    </div>
    
    <!-- Custom Post Types Section -->
    <div class="section-title">
        <h2>Custom Post Types</h2>
        <span class="count"><?= count($customPostTypes) ?></span>
    </div>
    
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
        <button type="button" class="btn-primary" id="btnNewTypeEmpty">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Create Post Type
        </button>
    </div>
    <?php else: ?>
    <div class="pt-grid">
        <?php foreach ($customPostTypes as $slug => $config): ?>
        <div class="pt-card">
            <div class="pt-card-header">
                <div class="pt-card-icon">
                    <?= getAdminMenuIcon($config['icon'] ?? 'file', 24) ?>
                </div>
                <div>
                    <h3><?= esc($config['label_plural'] ?? $slug) ?></h3>
                    <code><?= esc($slug) ?></code>
                </div>
            </div>
            <div class="pt-card-body">
                <div class="pt-stats">
                    <div>
                        <div class="pt-stat-value"><?= $postCounts[$slug] ?? 0 ?></div>
                        <div class="pt-stat-label">Items</div>
                    </div>
                    <div>
                        <div class="pt-stat-value"><?= count($config['fields'] ?? []) ?></div>
                        <div class="pt-stat-label">Custom Fields</div>
                    </div>
                </div>
                <div class="pt-badges">
                    <?php if ($config['public'] ?? true): ?>
                    <span class="pt-badge active">Public</span>
                    <?php else: ?>
                    <span class="pt-badge">Private</span>
                    <?php endif; ?>
                    <?php if ($config['has_archive'] ?? true): ?>
                    <span class="pt-badge active">Has Archive</span>
                    <?php endif; ?>
                </div>
                <div class="pt-actions">
                    <button type="button" class="pt-btn pt-btn-edit" data-slug="<?= esc($slug) ?>">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        Edit
                    </button>
                    <a href="<?= ADMIN_URL ?>/posts.php?type=<?= esc($slug) ?>" class="pt-btn pt-btn-view">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        View
                    </a>
                    <button type="button" class="pt-btn pt-btn-delete" data-slug="<?= esc($slug) ?>" title="Delete">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Main Modal -->
<div class="modal-overlay" id="modal">
    <div class="modal">
        <div class="modal-header">
            <h2 id="modalTitle">New Post Type</h2>
            <button type="button" class="modal-close" id="modalClose">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Singular Label *</label>
                    <input type="text" id="inputSingular" class="form-input" placeholder="e.g. Product">
                </div>
                <div class="form-group">
                    <label class="form-label">Plural Label *</label>
                    <input type="text" id="inputPlural" class="form-input" placeholder="e.g. Products">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Slug (URL identifier) *</label>
                <input type="text" id="inputSlug" class="form-input" placeholder="e.g. product">
                <div class="form-hint">Lowercase letters, numbers, and underscores only</div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Visibility</label>
                    <select id="inputPublic" class="form-select">
                        <option value="1">Public</option>
                        <option value="0">Private</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Archive Page</label>
                    <select id="inputArchive" class="form-select">
                        <option value="1">Has archive</option>
                        <option value="0">No archive</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Icon</label>
                <div class="icon-grid" id="iconGrid"></div>
                <input type="hidden" id="inputIcon" value="file">
            </div>
            
            <div class="form-group">
                <label class="form-label">Supports</label>
                <div class="checkbox-grid">
                    <label class="checkbox-item"><input type="checkbox" value="title" checked> Title</label>
                    <label class="checkbox-item"><input type="checkbox" value="editor" checked> Editor</label>
                    <label class="checkbox-item"><input type="checkbox" value="excerpt"> Excerpt</label>
                    <label class="checkbox-item"><input type="checkbox" value="thumbnail"> Thumbnail</label>
                    <label class="checkbox-item"><input type="checkbox" value="author"> Author</label>
                    <label class="checkbox-item"><input type="checkbox" value="comments"> Comments</label>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Custom Fields</label>
                <div class="fields-list" id="fieldsList">
                    <div class="fields-empty" id="fieldsEmpty">No custom fields added</div>
                </div>
                <button type="button" class="btn-add-field" id="btnAddField">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Add Field
                </button>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-cancel" id="btnCancel">Cancel</button>
            <button type="button" class="btn-save" id="btnSave">Save Post Type</button>
        </div>
    </div>
</div>

<!-- Field Modal -->
<div class="modal-overlay" id="fieldModal">
    <div class="modal" style="max-width: 450px;">
        <div class="modal-header">
            <h2>Add Field</h2>
            <button type="button" class="modal-close" id="fieldModalClose">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Field Label</label>
                <input type="text" id="fieldLabel" class="form-input" placeholder="e.g. Price">
            </div>
            <div class="form-group">
                <label class="form-label">Field Key</label>
                <input type="text" id="fieldKey" class="form-input" placeholder="e.g. price">
                <div class="form-hint">Lowercase, no spaces</div>
            </div>
            <div class="form-group">
                <label class="form-label">Field Type</label>
                <select id="fieldType" class="form-select">
                    <option value="text">Text</option>
                    <option value="textarea">Textarea</option>
                    <option value="number">Number</option>
                    <option value="email">Email</option>
                    <option value="url">URL</option>
                    <option value="date">Date</option>
                    <option value="select">Select</option>
                    <option value="checkbox">Checkbox</option>
                    <option value="image">Image</option>
                    <option value="wysiwyg">Rich Editor</option>
                </select>
            </div>
            <div class="form-group">
                <label class="checkbox-item" style="background: none; padding: 0;">
                    <input type="checkbox" id="fieldRequired">
                    <span>Required field</span>
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-cancel" id="fieldCancel">Cancel</button>
            <button type="button" class="btn-save" id="fieldSave">Add Field</button>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    // === CONFIG ===
    const CSRF_TOKEN = '<?= csrfToken() ?>';
    const DEBUG = <?= $debug ? 'true' : 'false' ?>;
    
    // === STATE ===
    let editingSlug = null;
    let customFields = [];
    
    // === ICONS ===
    const ICONS = {
        'file': '<path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline>',
        'file-text': '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line>',
        'image': '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline>',
        'box': '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>',
        'layers': '<polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline>',
        'grid': '<rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect>',
        'users': '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle>',
        'tag': '<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line>',
        'star': '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>',
        'heart': '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>'
    };
    
    // === DEBUG LOGGING ===
    function log(message, type = 'info') {
        const timestamp = new Date().toLocaleTimeString();
        const prefix = '[PostTypes]';
        
        if (type === 'error') {
            console.error(prefix, message);
        } else if (type === 'success') {
            console.log('%c' + prefix + ' ' + message, 'color: #10b981');
        } else {
            console.log(prefix, message);
        }
        
        if (DEBUG) {
            const logEl = document.getElementById('debugLog');
            if (logEl) {
                logEl.innerHTML += '<div class="' + type + '">[' + timestamp + '] ' + message + '</div>';
                logEl.scrollTop = logEl.scrollHeight;
            }
        }
    }
    
    // === DOM HELPERS ===
    function $(id) {
        const el = document.getElementById(id);
        if (!el) {
            log('Element not found: #' + id, 'error');
        }
        return el;
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // === ICON GRID ===
    function initIconGrid() {
        log('Initializing icon grid');
        const grid = $('iconGrid');
        if (!grid) return;
        
        let html = '';
        for (const [name, path] of Object.entries(ICONS)) {
            html += '<div class="icon-option' + (name === 'file' ? ' selected' : '') + '" data-icon="' + name + '">';
            html += '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' + path + '</svg>';
            html += '</div>';
        }
        grid.innerHTML = html;
        
        grid.addEventListener('click', function(e) {
            const option = e.target.closest('.icon-option');
            if (!option) return;
            
            grid.querySelectorAll('.icon-option').forEach(function(el) {
                el.classList.remove('selected');
            });
            option.classList.add('selected');
            $('inputIcon').value = option.dataset.icon;
            log('Icon selected: ' + option.dataset.icon);
        });
        
        log('Icon grid ready with ' + Object.keys(ICONS).length + ' icons', 'success');
    }
    
    // === FIELDS MANAGEMENT ===
    function renderFields() {
        const list = $('fieldsList');
        if (!list) return;
        
        if (customFields.length === 0) {
            list.innerHTML = '<div class="fields-empty">No custom fields added</div>';
            return;
        }
        
        let html = '';
        for (let i = 0; i < customFields.length; i++) {
            const field = customFields[i];
            html += '<div class="field-item">';
            html += '<div class="field-info">';
            html += '<div class="field-label">' + escapeHtml(field.label) + '</div>';
            html += '<div class="field-meta">' + escapeHtml(field.key) + ' &bull; ' + escapeHtml(field.type);
            if (field.required) html += ' &bull; Required';
            html += '</div></div>';
            html += '<button type="button" class="field-remove" data-index="' + i + '">';
            html += '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">';
            html += '<polyline points="3 6 5 6 21 6"></polyline>';
            html += '<path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>';
            html += '</svg></button></div>';
        }
        list.innerHTML = html;
        
        // Attach remove handlers
        list.querySelectorAll('.field-remove').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const index = parseInt(this.dataset.index);
                if (confirm('Remove this field?')) {
                    customFields.splice(index, 1);
                    renderFields();
                    log('Field removed at index ' + index);
                }
            });
        });
    }
    
    // === MODAL FUNCTIONS ===
    function openModal(slug) {
        log('Opening modal' + (slug ? ' for: ' + slug : ' for new'));
        
        const modal = $('modal');
        if (!modal) return;
        
        editingSlug = slug || null;
        $('modalTitle').textContent = slug ? 'Edit Post Type' : 'New Post Type';
        
        // Reset form
        $('inputSingular').value = '';
        $('inputPlural').value = '';
        $('inputSlug').value = '';
        $('inputSlug').disabled = false;
        $('inputPublic').value = '1';
        $('inputArchive').value = '1';
        $('inputIcon').value = 'file';
        customFields = [];
        
        // Reset checkboxes
        document.querySelectorAll('.checkbox-grid input').forEach(function(cb) {
            cb.checked = (cb.value === 'title' || cb.value === 'editor');
        });
        
        // Reset icon selection
        document.querySelectorAll('.icon-option').forEach(function(el) {
            el.classList.toggle('selected', el.dataset.icon === 'file');
        });
        
        renderFields();
        
        if (slug) {
            loadPostType(slug);
        }
        
        modal.classList.add('active');
        log('Modal opened', 'success');
    }
    
    function closeModal() {
        log('Closing modal');
        var modal = $('modal');
        if (modal) modal.classList.remove('active');
        editingSlug = null;
    }
    
    function openFieldModal() {
        log('Opening field modal');
        $('fieldLabel').value = '';
        $('fieldKey').value = '';
        $('fieldType').value = 'text';
        $('fieldRequired').checked = false;
        $('fieldModal').classList.add('active');
    }
    
    function closeFieldModal() {
        var modal = $('fieldModal');
        if (modal) modal.classList.remove('active');
    }
    
    // === API CALLS ===
    function apiCall(action, data) {
        log('API call: ' + action);
        
        return new Promise(function(resolve, reject) {
            var formData = new FormData();
            formData.append('ajax_action', action);
            formData.append('csrf_token', CSRF_TOKEN);
            
            if (data) {
                for (var key in data) {
                    if (data.hasOwnProperty(key)) {
                        var value = data[key];
                        if (Array.isArray(value)) {
                            for (var i = 0; i < value.length; i++) {
                                formData.append(key + '[]', value[i]);
                            }
                        } else {
                            formData.append(key, value);
                        }
                    }
                }
            }
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.pathname, true);
            
            xhr.onload = function() {
                log('Response status: ' + xhr.status);
                log('Response text: ' + xhr.responseText.substring(0, 500));
                
                if (xhr.status === 200) {
                    try {
                        var result = JSON.parse(xhr.responseText);
                        if (result.success) {
                            log('API success: ' + action, 'success');
                            resolve(result);
                        } else {
                            log('API error: ' + (result.error || 'Unknown'), 'error');
                            reject(new Error(result.error || 'Unknown error'));
                        }
                    } catch (e) {
                        log('JSON parse error: ' + e.message, 'error');
                        log('Raw response: ' + xhr.responseText, 'error');
                        reject(new Error('Invalid JSON response'));
                    }
                } else {
                    log('HTTP error: ' + xhr.status, 'error');
                    reject(new Error('HTTP error: ' + xhr.status));
                }
            };
            
            xhr.onerror = function() {
                log('Network error', 'error');
                reject(new Error('Network error'));
            };
            
            xhr.send(formData);
        });
    }
    
    function loadPostType(slug) {
        log('Loading post type: ' + slug);
        
        apiCall('get_post_type', { slug: slug })
            .then(function(result) {
                var data = result.data;
                
                $('inputSingular').value = data.label_singular || '';
                $('inputPlural').value = data.label_plural || '';
                $('inputSlug').value = slug;
                $('inputSlug').disabled = true;
                $('inputPublic').value = data.public ? '1' : '0';
                $('inputArchive').value = data.has_archive ? '1' : '0';
                $('inputIcon').value = data.icon || 'file';
                
                // Set icon
                document.querySelectorAll('.icon-option').forEach(function(el) {
                    el.classList.toggle('selected', el.dataset.icon === (data.icon || 'file'));
                });
                
                // Set supports
                var supports = data.supports || [];
                document.querySelectorAll('.checkbox-grid input').forEach(function(cb) {
                    cb.checked = supports.indexOf(cb.value) !== -1;
                });
                
                // Set fields
                customFields = data.fields || [];
                renderFields();
                
                log('Post type loaded: ' + slug, 'success');
            })
            .catch(function(error) {
                alert('Error loading: ' + error.message);
            });
    }
    
    function savePostType() {
        log('Saving post type...');
        
        var singular = $('inputSingular').value.trim();
        var plural = $('inputPlural').value.trim();
        var slug = $('inputSlug').value.trim().toLowerCase().replace(/[^a-z0-9_]/g, '');
        
        // Validation
        if (!singular) {
            alert('Singular label is required');
            $('inputSingular').focus();
            return;
        }
        if (!plural) {
            alert('Plural label is required');
            $('inputPlural').focus();
            return;
        }
        if (!slug) {
            alert('Slug is required');
            $('inputSlug').focus();
            return;
        }
        
        // Get supports
        var supports = [];
        document.querySelectorAll('.checkbox-grid input:checked').forEach(function(cb) {
            supports.push(cb.value);
        });
        
        var data = {
            slug: editingSlug || slug,
            label_singular: singular,
            label_plural: plural,
            icon: $('inputIcon').value,
            is_public: $('inputPublic').value,
            has_archive: $('inputArchive').value,
            supports: supports,
            fields: JSON.stringify(customFields)
        };
        
        log('Save data: ' + JSON.stringify(data));
        
        var btnSave = $('btnSave');
        btnSave.disabled = true;
        btnSave.textContent = 'Saving...';
        
        apiCall('save_post_type', data)
            .then(function(result) {
                log('Saved successfully!', 'success');
                window.location.reload();
            })
            .catch(function(error) {
                alert('Error: ' + error.message);
                btnSave.disabled = false;
                btnSave.textContent = 'Save Post Type';
            });
    }
    
    function deletePostType(slug) {
        if (!confirm('Delete "' + slug + '"? This cannot be undone.')) {
            return;
        }
        
        log('Deleting: ' + slug);
        
        apiCall('delete_post_type', { slug: slug })
            .then(function(result) {
                log('Deleted: ' + slug, 'success');
                window.location.reload();
            })
            .catch(function(error) {
                alert('Error: ' + error.message);
            });
    }
    
    function saveField() {
        var label = $('fieldLabel').value.trim();
        var key = $('fieldKey').value.trim().toLowerCase().replace(/[^a-z0-9_]/g, '');
        var type = $('fieldType').value;
        var required = $('fieldRequired').checked;
        
        if (!label) {
            alert('Field label is required');
            $('fieldLabel').focus();
            return;
        }
        if (!key) {
            alert('Field key is required');
            $('fieldKey').focus();
            return;
        }
        
        customFields.push({
            label: label,
            key: key,
            type: type,
            required: required
        });
        
        renderFields();
        closeFieldModal();
        log('Field added: ' + key, 'success');
    }
    
    // === AUTO SLUG ===
    function setupAutoSlug() {
        var singularInput = $('inputSingular');
        var slugInput = $('inputSlug');
        
        if (!singularInput || !slugInput) return;
        
        var autoSlug = true;
        
        singularInput.addEventListener('input', function() {
            if (autoSlug && !slugInput.disabled) {
                slugInput.value = this.value.toLowerCase()
                    .replace(/[^a-z0-9]+/g, '_')
                    .replace(/^_|_$/g, '');
            }
        });
        
        slugInput.addEventListener('input', function() {
            autoSlug = false;
        });
        
        log('Auto-slug setup complete');
    }
    
    // === EVENT BINDING ===
    function bindEvents() {
        log('Binding events...');
        
        // New Post Type buttons
        var btnNew = $('btnNewType');
        if (btnNew) {
            btnNew.addEventListener('click', function() {
                log('New button clicked');
                openModal();
            });
        }
        
        var btnNewEmpty = $('btnNewTypeEmpty');
        if (btnNewEmpty) {
            btnNewEmpty.addEventListener('click', function() {
                log('New (empty state) button clicked');
                openModal();
            });
        }
        
        // Modal controls
        var modalClose = $('modalClose');
        if (modalClose) {
            modalClose.addEventListener('click', closeModal);
        }
        
        var btnCancel = $('btnCancel');
        if (btnCancel) {
            btnCancel.addEventListener('click', closeModal);
        }
        
        var btnSave = $('btnSave');
        if (btnSave) {
            btnSave.addEventListener('click', savePostType);
        }
        
        // Field modal
        var btnAddField = $('btnAddField');
        if (btnAddField) {
            btnAddField.addEventListener('click', openFieldModal);
        }
        
        var fieldModalClose = $('fieldModalClose');
        if (fieldModalClose) {
            fieldModalClose.addEventListener('click', closeFieldModal);
        }
        
        var fieldCancel = $('fieldCancel');
        if (fieldCancel) {
            fieldCancel.addEventListener('click', closeFieldModal);
        }
        
        var fieldSave = $('fieldSave');
        if (fieldSave) {
            fieldSave.addEventListener('click', saveField);
        }
        
        // Edit buttons
        document.querySelectorAll('.pt-btn-edit').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var slug = this.dataset.slug;
                log('Edit clicked: ' + slug);
                openModal(slug);
            });
        });
        
        // Delete buttons
        document.querySelectorAll('.pt-btn-delete').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var slug = this.dataset.slug;
                log('Delete clicked: ' + slug);
                deletePostType(slug);
            });
        });
        
        // Close modal on overlay click
        var modal = $('modal');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === this) closeModal();
            });
        }
        
        var fieldModal = $('fieldModal');
        if (fieldModal) {
            fieldModal.addEventListener('click', function(e) {
                if (e.target === this) closeFieldModal();
            });
        }
        
        // Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                var fm = $('fieldModal');
                var m = $('modal');
                if (fm && fm.classList.contains('active')) {
                    closeFieldModal();
                } else if (m && m.classList.contains('active')) {
                    closeModal();
                }
            }
        });
        
        log('Events bound', 'success');
    }
    
    // === INIT ===
    function init() {
        log('=== Post Types Initializing ===');
        
        try {
            initIconGrid();
            setupAutoSlug();
            bindEvents();
            log('=== Initialization Complete ===', 'success');
        } catch (e) {
            log('Init error: ' + e.message, 'error');
            console.error(e);
        }
    }
    
    // Run when DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
})();
</script>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
