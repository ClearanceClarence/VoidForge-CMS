<?php
/**
 * Post Types Builder - Forge CMS v1.0.6
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

// Get custom post types from options
$customPostTypes = getOption('custom_post_types', []);

// Handle AJAX requests
if (isset($_POST['ajax_action']) && verifyCsrf($_POST['csrf_token'] ?? '')) {
    header('Content-Type: application/json');
    
    $action = $_POST['ajax_action'];
    
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
            
            if (empty($slug) || empty($labelSingular) || empty($labelPlural)) {
                echo json_encode(['success' => false, 'error' => 'Slug and labels are required']);
                exit;
            }
            
            // Reserved slugs
            $reserved = ['post', 'page', 'attachment', 'revision', 'nav_menu_item'];
            if (in_array($slug, $reserved)) {
                echo json_encode(['success' => false, 'error' => 'This slug is reserved']);
                exit;
            }
            
            $customPostTypes[$slug] = [
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
            
            setOption('custom_post_types', $customPostTypes);
            
            echo json_encode(['success' => true, 'message' => 'Post type saved successfully']);
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
    }
    
    echo json_encode(['success' => false, 'error' => 'Unknown action']);
    exit;
}

// Get post counts for each type
$postCounts = [];
foreach ($customPostTypes as $slug => $config) {
    $table = Database::table('posts');
    $postCounts[$slug] = Database::queryValue("SELECT COUNT(*) FROM {$table} WHERE post_type = ?", [$slug]);
}

include ADMIN_PATH . '/includes/header.php';
?>

<style>
/* Post Types Builder Styles */
.pt-page {
    max-width: 1100px;
    margin: 0 auto;
}

.pt-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 2rem;
}

.pt-header-left h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 0.375rem 0;
}

.pt-header-left p {
    color: #64748b;
    margin: 0;
}

.btn-new-type {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 0.9375rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 15px rgba(99, 102, 241, 0.35);
}

.btn-new-type:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(99, 102, 241, 0.45);
}

/* Built-in Types Info */
.builtin-types {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-bottom: 2rem;
}

.builtin-type-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem;
    background: linear-gradient(135deg, #f8fafc 0%, #fff 100%);
    border: 1px solid #e2e8f0;
    border-radius: 12px;
}

.builtin-type-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.builtin-type-info h4 {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 0.25rem 0;
}

.builtin-type-info p {
    font-size: 0.8125rem;
    color: #64748b;
    margin: 0;
}

.builtin-badge {
    font-size: 0.6875rem;
    font-weight: 600;
    padding: 0.25rem 0.625rem;
    background: #e2e8f0;
    color: #64748b;
    border-radius: 9999px;
    margin-left: auto;
}

/* Custom Post Types Grid */
.pt-section-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.25rem;
}

.pt-section-title h2 {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.pt-section-title .count {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.625rem;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
    color: #6366f1;
    border-radius: 9999px;
}

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
    transition: all 0.2s ease;
}

.pt-card:hover {
    border-color: #6366f1;
    box-shadow: 0 8px 25px rgba(99, 102, 241, 0.15);
}

.pt-card-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem;
    background: linear-gradient(135deg, #f8fafc 0%, #fff 100%);
    border-bottom: 1px solid #f1f5f9;
}

.pt-card-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(139, 92, 246, 0.15) 100%);
    color: #6366f1;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.pt-card-title {
    flex: 1;
    min-width: 0;
}

.pt-card-title h3 {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 0.25rem 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.pt-card-title code {
    font-size: 0.75rem;
    font-family: 'JetBrains Mono', monospace;
    background: #f1f5f9;
    padding: 0.125rem 0.5rem;
    border-radius: 4px;
    color: #64748b;
}

.pt-card-body {
    padding: 1.25rem;
}

.pt-card-stats {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 1rem;
}

.pt-stat {
    display: flex;
    flex-direction: column;
}

.pt-stat-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
}

.pt-stat-label {
    font-size: 0.75rem;
    color: #94a3b8;
}

.pt-card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.pt-meta-badge {
    font-size: 0.6875rem;
    font-weight: 500;
    padding: 0.25rem 0.625rem;
    border-radius: 6px;
    background: #f1f5f9;
    color: #64748b;
}

.pt-meta-badge.active {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

.pt-card-actions {
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
    padding: 0.625rem 1rem;
    font-size: 0.8125rem;
    font-weight: 500;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
}

.pt-btn-edit {
    background: #f1f5f9;
    color: #475569;
    border: none;
}

.pt-btn-edit:hover {
    background: #e2e8f0;
}

.pt-btn-view {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: #fff;
    border: none;
}

.pt-btn-view:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.pt-btn-delete {
    flex: 0;
    width: 36px;
    background: none;
    color: #94a3b8;
    border: 1px solid #e2e8f0;
}

.pt-btn-delete:hover {
    color: #ef4444;
    border-color: #ef4444;
    background: rgba(239, 68, 68, 0.05);
}

/* Empty State */
.pt-empty {
    text-align: center;
    padding: 4rem 2rem;
    background: linear-gradient(135deg, #f8fafc 0%, #fff 100%);
    border: 2px dashed #e2e8f0;
    border-radius: 16px;
}

.pt-empty-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 1.5rem;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6366f1;
}

.pt-empty h3 {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 0.5rem 0;
}

.pt-empty p {
    color: #64748b;
    margin: 0 0 1.5rem 0;
}

/* Modal Styles */
.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}

.modal-overlay.active {
    display: flex;
}

.modal {
    width: 100%;
    max-width: 700px;
    max-height: 90vh;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    background: linear-gradient(135deg, #f8fafc 0%, #fff 100%);
}

.modal-header h2 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.modal-close {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: none;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.modal-close:hover {
    background: #f1f5f9;
    color: #64748b;
}

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

/* Form Elements */
.form-section {
    margin-bottom: 2rem;
}

.form-section:last-child {
    margin-bottom: 0;
}

.form-section-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.form-grid {
    display: grid;
    gap: 1rem;
}

.form-grid-2 {
    grid-template-columns: repeat(2, 1fr);
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.form-label {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #475569;
}

.form-input,
.form-select {
    padding: 0.75rem 1rem;
    font-size: 0.9375rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    background: #fff;
    color: #1e293b;
    transition: all 0.2s ease;
}

.form-input:focus,
.form-select:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

.form-hint {
    font-size: 0.75rem;
    color: #94a3b8;
}

.checkbox-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
}

.checkbox-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem;
    background: #f8fafc;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.checkbox-item:hover {
    background: #f1f5f9;
}

.checkbox-item input {
    width: 18px;
    height: 18px;
    accent-color: #6366f1;
}

.checkbox-item span {
    font-size: 0.875rem;
    color: #475569;
}

/* Custom Fields Section */
.fields-list {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
}

.field-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border-bottom: 1px solid #e2e8f0;
    background: #fff;
}

.field-item:last-child {
    border-bottom: none;
}

.field-item-drag {
    color: #cbd5e1;
    cursor: grab;
}

.field-item-info {
    flex: 1;
}

.field-item-name {
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.125rem;
}

.field-item-meta {
    font-size: 0.75rem;
    color: #94a3b8;
}

.field-item-actions {
    display: flex;
    gap: 0.5rem;
}

.field-item-btn {
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
    transition: all 0.2s ease;
}

.field-item-btn:hover {
    color: #64748b;
    border-color: #cbd5e1;
}

.field-item-btn.delete:hover {
    color: #ef4444;
    border-color: #ef4444;
}

.add-field-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    width: 100%;
    padding: 1rem;
    background: #f8fafc;
    border: none;
    color: #6366f1;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.add-field-btn:hover {
    background: #f1f5f9;
}

.fields-empty {
    padding: 2rem;
    text-align: center;
    color: #94a3b8;
    font-size: 0.875rem;
}

/* Button Styles */
.btn-cancel {
    padding: 0.75rem 1.5rem;
    background: #f1f5f9;
    color: #475569;
    border: none;
    border-radius: 10px;
    font-size: 0.9375rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-cancel:hover {
    background: #e2e8f0;
}

.btn-save {
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 0.9375rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.btn-save:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(99, 102, 241, 0.4);
}

/* Icon Selector */
.icon-grid {
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    gap: 0.5rem;
    max-height: 200px;
    overflow-y: auto;
    padding: 0.5rem;
    background: #f8fafc;
    border-radius: 10px;
    margin-top: 0.5rem;
}

.icon-option {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    cursor: pointer;
    color: #64748b;
    transition: all 0.2s ease;
}

.icon-option:hover {
    border-color: #6366f1;
    color: #6366f1;
}

.icon-option.selected {
    border-color: #6366f1;
    background: rgba(99, 102, 241, 0.1);
    color: #6366f1;
}

@media (max-width: 768px) {
    .pt-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .builtin-types {
        grid-template-columns: 1fr;
    }
    
    .form-grid-2 {
        grid-template-columns: 1fr;
    }
    
    .checkbox-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<div class="pt-page">
    <div class="pt-header">
        <div class="pt-header-left">
            <h1>Post Types</h1>
            <p>Create and manage custom content types for your site</p>
        </div>
        <button type="button" class="btn-new-type" onclick="openModal()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            New Post Type
        </button>
    </div>
    
    <!-- Built-in Types -->
    <div class="builtin-types">
        <div class="builtin-type-card">
            <div class="builtin-type-icon" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(139, 92, 246, 0.15)); color: #6366f1;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                </svg>
            </div>
            <div class="builtin-type-info">
                <h4>Posts</h4>
                <p>Blog posts and articles</p>
            </div>
            <span class="builtin-badge">Built-in</span>
        </div>
        
        <div class="builtin-type-card">
            <div class="builtin-type-icon" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(5, 150, 105, 0.15)); color: #10b981;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                    <polyline points="13 2 13 9 20 9"></polyline>
                </svg>
            </div>
            <div class="builtin-type-info">
                <h4>Pages</h4>
                <p>Static content pages</p>
            </div>
            <span class="builtin-badge">Built-in</span>
        </div>
    </div>
    
    <!-- Custom Post Types -->
    <div class="pt-section-title">
        <h2>Custom Post Types</h2>
        <span class="count"><?= count($customPostTypes) ?></span>
    </div>
    
    <?php if (empty($customPostTypes)): ?>
    <div class="pt-empty">
        <div class="pt-empty-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="12" y1="18" x2="12" y2="12"></line>
                <line x1="9" y1="15" x2="15" y2="15"></line>
            </svg>
        </div>
        <h3>No custom post types yet</h3>
        <p>Create your first custom post type to organize different types of content</p>
        <button type="button" class="btn-new-type" onclick="openModal()">
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
        <div class="pt-card" data-slug="<?= esc($slug) ?>">
            <div class="pt-card-header">
                <div class="pt-card-icon">
                    <?= getAdminMenuIcon($config['icon'] ?? 'file', 24) ?>
                </div>
                <div class="pt-card-title">
                    <h3><?= esc($config['label_plural']) ?></h3>
                    <code><?= esc($slug) ?></code>
                </div>
            </div>
            <div class="pt-card-body">
                <div class="pt-card-stats">
                    <div class="pt-stat">
                        <span class="pt-stat-value"><?= $postCounts[$slug] ?? 0 ?></span>
                        <span class="pt-stat-label">Items</span>
                    </div>
                    <div class="pt-stat">
                        <span class="pt-stat-value"><?= count($config['fields'] ?? []) ?></span>
                        <span class="pt-stat-label">Custom Fields</span>
                    </div>
                </div>
                <div class="pt-card-meta">
                    <?php if ($config['public'] ?? true): ?>
                    <span class="pt-meta-badge active">Public</span>
                    <?php else: ?>
                    <span class="pt-meta-badge">Private</span>
                    <?php endif; ?>
                    <?php if ($config['has_archive'] ?? true): ?>
                    <span class="pt-meta-badge active">Has Archive</span>
                    <?php endif; ?>
                    <?php foreach (($config['supports'] ?? []) as $support): ?>
                    <span class="pt-meta-badge"><?= esc(ucfirst($support)) ?></span>
                    <?php endforeach; ?>
                </div>
                <div class="pt-card-actions">
                    <button type="button" class="pt-btn pt-btn-edit" onclick="editPostType('<?= esc($slug) ?>')">
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
                    <button type="button" class="pt-btn pt-btn-delete" onclick="deletePostType('<?= esc($slug) ?>')" title="Delete">
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

<!-- Modal -->
<div class="modal-overlay" id="postTypeModal">
    <div class="modal">
        <div class="modal-header">
            <h2 id="modalTitle">New Post Type</h2>
            <button type="button" class="modal-close" onclick="closeModal()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <form id="postTypeForm">
                <input type="hidden" id="editingSlug" value="">
                
                <div class="form-section">
                    <div class="form-section-title">Basic Information</div>
                    <div class="form-grid form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Singular Label</label>
                            <input type="text" id="labelSingular" class="form-input" placeholder="e.g. Product">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Plural Label</label>
                            <input type="text" id="labelPlural" class="form-input" placeholder="e.g. Products">
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 1rem;">
                        <label class="form-label">Slug (URL identifier)</label>
                        <input type="text" id="ptSlug" class="form-input" placeholder="e.g. product" pattern="[a-z0-9_]+">
                        <span class="form-hint">Lowercase letters, numbers, and underscores only</span>
                    </div>
                </div>
                
                <div class="form-section">
                    <div class="form-section-title">Settings</div>
                    <div class="form-grid form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Visibility</label>
                            <select id="isPublic" class="form-select">
                                <option value="1">Public (shown in admin menu)</option>
                                <option value="0">Private (hidden from menu)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Archive</label>
                            <select id="hasArchive" class="form-select">
                                <option value="1">Has archive page</option>
                                <option value="0">No archive</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <div class="form-section-title">Icon</div>
                    <div class="icon-grid" id="iconGrid">
                        <!-- Icons will be populated by JavaScript -->
                    </div>
                    <input type="hidden" id="selectedIcon" value="file">
                </div>
                
                <div class="form-section">
                    <div class="form-section-title">Supports</div>
                    <div class="checkbox-grid">
                        <label class="checkbox-item">
                            <input type="checkbox" name="supports[]" value="title" checked>
                            <span>Title</span>
                        </label>
                        <label class="checkbox-item">
                            <input type="checkbox" name="supports[]" value="editor" checked>
                            <span>Editor</span>
                        </label>
                        <label class="checkbox-item">
                            <input type="checkbox" name="supports[]" value="excerpt">
                            <span>Excerpt</span>
                        </label>
                        <label class="checkbox-item">
                            <input type="checkbox" name="supports[]" value="thumbnail">
                            <span>Thumbnail</span>
                        </label>
                        <label class="checkbox-item">
                            <input type="checkbox" name="supports[]" value="author">
                            <span>Author</span>
                        </label>
                        <label class="checkbox-item">
                            <input type="checkbox" name="supports[]" value="comments">
                            <span>Comments</span>
                        </label>
                    </div>
                </div>
                
                <div class="form-section">
                    <div class="form-section-title">Custom Fields</div>
                    <div class="fields-list" id="fieldsList">
                        <div class="fields-empty" id="fieldsEmpty">No custom fields added yet</div>
                    </div>
                    <button type="button" class="add-field-btn" onclick="addField()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Add Custom Field
                    </button>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
            <button type="button" class="btn-save" onclick="savePostType()">Save Post Type</button>
        </div>
    </div>
</div>

<!-- Field Modal -->
<div class="modal-overlay" id="fieldModal">
    <div class="modal" style="max-width: 500px;">
        <div class="modal-header">
            <h2>Add Custom Field</h2>
            <button type="button" class="modal-close" onclick="closeFieldModal()">
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
                <input type="text" id="fieldKey" class="form-input" placeholder="e.g. price" pattern="[a-z0-9_]+">
                <span class="form-hint">Lowercase letters, numbers, and underscores only</span>
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
                    <option value="datetime">Date & Time</option>
                    <option value="select">Select Dropdown</option>
                    <option value="checkbox">Checkbox</option>
                    <option value="radio">Radio Buttons</option>
                    <option value="image">Image</option>
                    <option value="file">File</option>
                    <option value="wysiwyg">Rich Text Editor</option>
                    <option value="color">Color Picker</option>
                </select>
            </div>
            <div class="form-group" id="optionsGroup" style="display: none;">
                <label class="form-label">Options (one per line)</label>
                <textarea id="fieldOptions" class="form-input" style="min-height: 80px;" placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
            </div>
            <div class="form-group">
                <label class="checkbox-item" style="background: none; padding: 0;">
                    <input type="checkbox" id="fieldRequired">
                    <span>Required field</span>
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-cancel" onclick="closeFieldModal()">Cancel</button>
            <button type="button" class="btn-save" onclick="saveField()">Add Field</button>
        </div>
    </div>
</div>

<script>
const csrfToken = '<?= csrfToken() ?>';
let customFields = [];
let editingFieldIndex = -1;

// Available icons
const icons = ['file', 'file-text', 'image', 'box', 'layers', 'grid', 'tool', 'users', 'settings', 'dashboard', 'palette'];

// Initialize icon grid
function initIconGrid() {
    const grid = document.getElementById('iconGrid');
    grid.innerHTML = icons.map(icon => `
        <div class="icon-option ${icon === 'file' ? 'selected' : ''}" data-icon="${icon}" onclick="selectIcon('${icon}')">
            ${getIconSvg(icon)}
        </div>
    `).join('');
}

function getIconSvg(icon) {
    const iconPaths = {
        'file': '<path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline>',
        'file-text': '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line>',
        'image': '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline>',
        'box': '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line>',
        'layers': '<polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline>',
        'grid': '<rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect>',
        'tool': '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>',
        'users': '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>',
        'settings': '<circle cx="12" cy="12" r="3"></circle><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"></path>',
        'dashboard': '<rect x="3" y="3" width="7" height="9" rx="1"></rect><rect x="14" y="3" width="7" height="5" rx="1"></rect><rect x="14" y="12" width="7" height="9" rx="1"></rect><rect x="3" y="16" width="7" height="5" rx="1"></rect>',
        'palette': '<path d="M12 19l7-7 3 3-7 7-3-3z"></path><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"></path><path d="M2 2l7.586 7.586"></path><circle cx="11" cy="11" r="2"></circle>'
    };
    return `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">${iconPaths[icon] || iconPaths['file']}</svg>`;
}

function selectIcon(icon) {
    document.querySelectorAll('.icon-option').forEach(opt => opt.classList.remove('selected'));
    document.querySelector(`.icon-option[data-icon="${icon}"]`).classList.add('selected');
    document.getElementById('selectedIcon').value = icon;
}

function openModal(slug = null) {
    document.getElementById('postTypeModal').classList.add('active');
    document.getElementById('modalTitle').textContent = slug ? 'Edit Post Type' : 'New Post Type';
    document.getElementById('editingSlug').value = slug || '';
    
    if (!slug) {
        // Reset form
        document.getElementById('labelSingular').value = '';
        document.getElementById('labelPlural').value = '';
        document.getElementById('ptSlug').value = '';
        document.getElementById('isPublic').value = '1';
        document.getElementById('hasArchive').value = '1';
        document.querySelectorAll('input[name="supports[]"]').forEach(cb => {
            cb.checked = ['title', 'editor'].includes(cb.value);
        });
        selectIcon('file');
        customFields = [];
        renderFields();
    }
}

function closeModal() {
    document.getElementById('postTypeModal').classList.remove('active');
}

async function editPostType(slug) {
    const formData = new FormData();
    formData.append('ajax_action', 'get_post_type');
    formData.append('csrf_token', csrfToken);
    formData.append('slug', slug);
    
    try {
        const res = await fetch('', { method: 'POST', body: formData });
        const result = await res.json();
        
        if (result.success) {
            const data = result.data;
            openModal(slug);
            
            document.getElementById('labelSingular').value = data.label_singular || '';
            document.getElementById('labelPlural').value = data.label_plural || '';
            document.getElementById('ptSlug').value = slug;
            document.getElementById('ptSlug').disabled = true;
            document.getElementById('isPublic').value = data.public ? '1' : '0';
            document.getElementById('hasArchive').value = data.has_archive ? '1' : '0';
            
            document.querySelectorAll('input[name="supports[]"]').forEach(cb => {
                cb.checked = (data.supports || []).includes(cb.value);
            });
            
            selectIcon(data.icon || 'file');
            customFields = data.fields || [];
            renderFields();
        } else {
            alert('Error: ' + result.error);
        }
    } catch (e) {
        alert('Error loading post type');
    }
}

async function savePostType() {
    const slug = document.getElementById('editingSlug').value || document.getElementById('ptSlug').value;
    const supports = Array.from(document.querySelectorAll('input[name="supports[]"]:checked')).map(cb => cb.value);
    
    const formData = new FormData();
    formData.append('ajax_action', 'save_post_type');
    formData.append('csrf_token', csrfToken);
    formData.append('slug', slug);
    formData.append('label_singular', document.getElementById('labelSingular').value);
    formData.append('label_plural', document.getElementById('labelPlural').value);
    formData.append('icon', document.getElementById('selectedIcon').value);
    formData.append('is_public', document.getElementById('isPublic').value);
    formData.append('has_archive', document.getElementById('hasArchive').value);
    formData.append('fields', JSON.stringify(customFields));
    supports.forEach(s => formData.append('supports[]', s));
    
    try {
        const res = await fetch('', { method: 'POST', body: formData });
        const result = await res.json();
        
        if (result.success) {
            location.reload();
        } else {
            alert('Error: ' + result.error);
        }
    } catch (e) {
        alert('Error saving post type');
    }
}

async function deletePostType(slug) {
    if (!confirm('Delete this post type? This cannot be undone.')) return;
    
    const formData = new FormData();
    formData.append('ajax_action', 'delete_post_type');
    formData.append('csrf_token', csrfToken);
    formData.append('slug', slug);
    
    try {
        const res = await fetch('', { method: 'POST', body: formData });
        const result = await res.json();
        
        if (result.success) {
            location.reload();
        } else {
            alert('Error: ' + result.error);
        }
    } catch (e) {
        alert('Error deleting post type');
    }
}

// Custom Fields
function renderFields() {
    const list = document.getElementById('fieldsList');
    const empty = document.getElementById('fieldsEmpty');
    
    if (customFields.length === 0) {
        empty.style.display = 'block';
        list.innerHTML = '<div class="fields-empty" id="fieldsEmpty">No custom fields added yet</div>';
        return;
    }
    
    list.innerHTML = customFields.map((field, index) => `
        <div class="field-item">
            <div class="field-item-drag">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="8" y1="6" x2="8" y2="6.01"></line>
                    <line x1="8" y1="12" x2="8" y2="12.01"></line>
                    <line x1="8" y1="18" x2="8" y2="18.01"></line>
                    <line x1="16" y1="6" x2="16" y2="6.01"></line>
                    <line x1="16" y1="12" x2="16" y2="12.01"></line>
                    <line x1="16" y1="18" x2="16" y2="18.01"></line>
                </svg>
            </div>
            <div class="field-item-info">
                <div class="field-item-name">${escapeHtml(field.label)}</div>
                <div class="field-item-meta">${escapeHtml(field.key)} • ${field.type}${field.required ? ' • Required' : ''}</div>
            </div>
            <div class="field-item-actions">
                <button type="button" class="field-item-btn delete" onclick="removeField(${index})">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                </button>
            </div>
        </div>
    `).join('');
}

function addField() {
    document.getElementById('fieldModal').classList.add('active');
    document.getElementById('fieldLabel').value = '';
    document.getElementById('fieldKey').value = '';
    document.getElementById('fieldType').value = 'text';
    document.getElementById('fieldOptions').value = '';
    document.getElementById('fieldRequired').checked = false;
    document.getElementById('optionsGroup').style.display = 'none';
    editingFieldIndex = -1;
}

function closeFieldModal() {
    document.getElementById('fieldModal').classList.remove('active');
}

function saveField() {
    const label = document.getElementById('fieldLabel').value.trim();
    const key = document.getElementById('fieldKey').value.trim().toLowerCase().replace(/[^a-z0-9_]/g, '');
    const type = document.getElementById('fieldType').value;
    const options = document.getElementById('fieldOptions').value.split('\n').filter(o => o.trim());
    const required = document.getElementById('fieldRequired').checked;
    
    if (!label || !key) {
        alert('Label and key are required');
        return;
    }
    
    const field = { label, key, type, required };
    if (['select', 'radio', 'checkbox'].includes(type)) {
        field.options = options;
    }
    
    if (editingFieldIndex >= 0) {
        customFields[editingFieldIndex] = field;
    } else {
        customFields.push(field);
    }
    
    renderFields();
    closeFieldModal();
}

function removeField(index) {
    if (confirm('Remove this field?')) {
        customFields.splice(index, 1);
        renderFields();
    }
}

// Show/hide options based on field type
document.getElementById('fieldType')?.addEventListener('change', function() {
    const optionsGroup = document.getElementById('optionsGroup');
    optionsGroup.style.display = ['select', 'radio'].includes(this.value) ? 'block' : 'none';
});

// Auto-generate key from label
document.getElementById('fieldLabel')?.addEventListener('input', function() {
    const keyInput = document.getElementById('fieldKey');
    if (!keyInput.value || keyInput.dataset.auto === 'true') {
        keyInput.value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
        keyInput.dataset.auto = 'true';
    }
});

document.getElementById('fieldKey')?.addEventListener('input', function() {
    this.dataset.auto = 'false';
});

// Auto-generate slug from singular label
document.getElementById('labelSingular')?.addEventListener('input', function() {
    const slugInput = document.getElementById('ptSlug');
    if (!slugInput.disabled && (!slugInput.value || slugInput.dataset.auto === 'true')) {
        slugInput.value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
        slugInput.dataset.auto = 'true';
    }
});

document.getElementById('ptSlug')?.addEventListener('input', function() {
    this.dataset.auto = 'false';
});

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initialize
initIconGrid();
</script>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
