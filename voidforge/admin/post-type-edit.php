<?php
/**
 * Post Type Editor - VoidForge CMS
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

// Get custom post types
$customPostTypes = getOption('custom_post_types');
if (!is_array($customPostTypes)) {
    $customPostTypes = [];
}

// Editing existing?
$editSlug = $_GET['slug'] ?? null;
$isEdit = $editSlug && isset($customPostTypes[$editSlug]);
$pageTitle = $isEdit ? 'Edit Post Type' : 'New Post Type';

// Load existing data
$data = $isEdit ? $customPostTypes[$editSlug] : [
    'slug' => '',
    'label_singular' => '',
    'label_plural' => '',
    'icon' => 'file',
    'public' => true,
    'has_archive' => true,
    'supports' => ['title', 'editor'],
    'fields' => [],
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $slug = preg_replace('/[^a-z0-9_]/', '', strtolower($_POST['slug'] ?? ''));
        $labelSingular = trim($_POST['label_singular'] ?? '');
        $labelPlural = trim($_POST['label_plural'] ?? '');
        $icon = trim($_POST['icon'] ?? 'file');
        $isPublic = isset($_POST['is_public']);
        $hasArchive = isset($_POST['has_archive']);
        $supports = $_POST['supports'] ?? [];
        $fieldsJson = $_POST['fields_json'] ?? '[]';
        $fields = json_decode($fieldsJson, true) ?: [];
        $maxRevisions = max(0, min(100, (int)($_POST['max_revisions'] ?? 10)));
        
        // Validation
        if (empty($slug)) {
            $error = 'Slug is required.';
        } elseif (empty($labelSingular)) {
            $error = 'Singular label is required.';
        } elseif (empty($labelPlural)) {
            $error = 'Plural label is required.';
        } elseif (!$isEdit && isset($customPostTypes[$slug])) {
            $error = 'A post type with this slug already exists.';
        } elseif (in_array($slug, ['post', 'page', 'attachment', 'revision', 'nav_menu_item'])) {
            $error = 'This slug is reserved.';
        } else {
            // Save
            $config = [
                'slug' => $slug,
                'label_singular' => $labelSingular,
                'label_plural' => $labelPlural,
                'icon' => $icon,
                'public' => $isPublic,
                'has_archive' => $hasArchive,
                'supports' => $supports,
                'fields' => $fields,
                'max_revisions' => $maxRevisions,
                'created_at' => $isEdit ? ($customPostTypes[$editSlug]['created_at'] ?? date('Y-m-d H:i:s')) : date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            
            // If slug changed during edit, remove old one
            if ($isEdit && $editSlug !== $slug) {
                unset($customPostTypes[$editSlug]);
            }
            
            $customPostTypes[$slug] = $config;
            setOption('custom_post_types', $customPostTypes);
            
            header('Location: post-types.php?saved=1');
            exit;
        }
        
        // Keep form data on error
        $data = [
            'slug' => $slug ?? '',
            'label_singular' => $labelSingular ?? '',
            'label_plural' => $labelPlural ?? '',
            'icon' => $icon ?? 'file',
            'public' => $isPublic ?? true,
            'has_archive' => $hasArchive ?? true,
            'supports' => $supports ?? [],
            'fields' => $fields ?? [],
        ];
    }
}

// Icons
$icons = [
    // Content
    'file' => 'Document',
    'file-text' => 'Article',
    'book' => 'Book',
    'bookmark' => 'Bookmark',
    'archive' => 'Archive',
    'folder' => 'Folder',
    'copy' => 'Copy',
    
    // Media
    'image' => 'Image',
    'video' => 'Video',
    'music' => 'Music',
    'mic' => 'Microphone',
    'camera' => 'Camera',
    
    // Commerce
    'shopping-bag' => 'Shopping',
    'package' => 'Package',
    'truck' => 'Delivery',
    'briefcase' => 'Business',
    'gift' => 'Gift',
    'tag' => 'Tag',
    
    // People & Social
    'users' => 'Users',
    'user' => 'User',
    'heart' => 'Heart',
    'thumbs-up' => 'Like',
    'share' => 'Share',
    'mail' => 'Email',
    'phone' => 'Phone',
    
    // Interface
    'star' => 'Star',
    'flag' => 'Flag',
    'award' => 'Award',
    'target' => 'Target',
    'compass' => 'Compass',
    'map-pin' => 'Location',
    
    // Objects
    'box' => 'Box',
    'layers' => 'Layers',
    'grid' => 'Grid',
    'calendar' => 'Calendar',
    'clock' => 'Clock',
    'tool' => 'Tool',
    'key' => 'Key',
    'shield' => 'Shield',
    'lock' => 'Lock',
    
    // Tech
    'code' => 'Code',
    'terminal' => 'Terminal',
    'database' => 'Database',
    'server' => 'Server',
    'cpu' => 'CPU',
    'globe' => 'Globe',
    'link' => 'Link',
    'zap' => 'Zap',
    
    // Misc
    'coffee' => 'Coffee',
    'home' => 'Home',
    'settings' => 'Settings',
    'eye' => 'Eye',
    'edit' => 'Edit',
    'printer' => 'Printer',
    'save' => 'Save',
    'paperclip' => 'Attachment',
    'headphones' => 'Headphones',
    'monitor' => 'Monitor',
    'smartphone' => 'Phone',
    'wifi' => 'WiFi',
    'cloud' => 'Cloud',
    'sun' => 'Sun',
    'moon' => 'Moon',
    'feather' => 'Feather',
    'send' => 'Send',
    'bell' => 'Bell',
    'activity' => 'Activity',
    'trending-up' => 'Trending',
    'pie-chart' => 'Chart',
    'bar-chart' => 'Bar Chart',
];

include ADMIN_PATH . '/includes/header.php';
?>

<style>
.pte-page { max-width: 900px; margin: 0 auto; }

.pte-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 2rem;
}

.pte-back {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: #f1f5f9;
    border-radius: 10px;
    color: #64748b;
    text-decoration: none;
    transition: all 0.15s;
}

.pte-back:hover { background: #e2e8f0; color: #1e293b; }

.pte-header h1 { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin: 0; }

.alert {
    padding: 1rem 1.25rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    font-size: 0.9375rem;
    background: rgba(239,68,68,0.1);
    color: #dc2626;
    border: 1px solid rgba(239,68,68,0.2);
}

/* Card layout */
.pte-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    margin-bottom: 1.5rem;
    overflow: hidden;
}

.pte-card-header {
    padding: 1.25rem 1.5rem;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.pte-card-header h2 {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.pte-card-header p {
    font-size: 0.8125rem;
    color: #64748b;
    margin: 0.25rem 0 0 0;
}

.pte-card-body { padding: 1.5rem; }

/* Form */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.25rem;
}

.form-group { margin-bottom: 1.25rem; }
.form-group:last-child { margin-bottom: 0; }

.form-label {
    display: block;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #475569;
    margin-bottom: 0.5rem;
}

.form-label .required { color: #ef4444; }

.form-input, .form-select {
    width: 100%;
    padding: 0.75rem 1rem;
    font-size: 0.9375rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    background: #fff;
    color: #1e293b;
    transition: all 0.15s;
    box-sizing: border-box;
}

.form-input:focus, .form-select:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}

.form-input:disabled { background: #f1f5f9; color: #64748b; }

.form-hint { font-size: 0.75rem; color: #94a3b8; margin-top: 0.375rem; }

/* Checkboxes */
.checkbox-group {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.checkbox-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.875rem;
    color: #475569;
    transition: all 0.15s;
}

.checkbox-item:hover { border-color: #6366f1; }
.checkbox-item input { accent-color: #6366f1; }

.checkbox-single {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-size: 0.9375rem;
    color: #475569;
}

/* Icon picker */
.icon-grid {
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    gap: 0.5rem;
}

.icon-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.375rem;
    padding: 0.75rem 0.5rem;
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    cursor: pointer;
    color: #64748b;
    transition: all 0.15s;
}

.icon-option:hover { border-color: #6366f1; color: #6366f1; }
.icon-option.selected { border-color: #6366f1; background: rgba(99,102,241,0.1); color: #6366f1; }
.icon-option span { font-size: 0.625rem; text-align: center; }

/* Custom Fields */
.fields-list {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
}

.field-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.25rem;
    background: #fff;
    border-bottom: 1px solid #f1f5f9;
}

.field-item:last-child { border-bottom: none; }

.field-drag {
    color: #cbd5e1;
    cursor: grab;
}

.field-info { flex: 1; }
.field-name { font-weight: 600; color: #1e293b; }
.field-meta { font-size: 0.75rem; color: #94a3b8; margin-top: 0.125rem; }

.field-type {
    font-size: 0.6875rem;
    padding: 0.25rem 0.625rem;
    background: #f1f5f9;
    border-radius: 6px;
    color: #64748b;
    text-transform: uppercase;
}

.field-actions { display: flex; gap: 0.375rem; }

.field-btn {
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
    transition: all 0.15s;
}

.field-btn:hover { color: #6366f1; border-color: #6366f1; }
.field-btn.delete:hover { color: #ef4444; border-color: #ef4444; }

.fields-empty {
    padding: 3rem;
    text-align: center;
    color: #94a3b8;
}

.fields-empty svg { width: 48px; height: 48px; margin-bottom: 0.75rem; opacity: 0.5; }
.fields-empty p { margin: 0; }

.btn-add-field {
    width: 100%;
    padding: 1rem;
    background: #f8fafc;
    border: none;
    border-top: 1px solid #e2e8f0;
    color: #6366f1;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all 0.15s;
}

.btn-add-field:hover { background: #f1f5f9; }

/* Footer */
.pte-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    border-radius: 0 0 16px 16px;
    margin-top: -1px;
}

.btn-cancel {
    padding: 0.75rem 1.5rem;
    background: #fff;
    color: #64748b;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    font-size: 0.9375rem;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.15s;
}

.btn-cancel:hover { background: #f8fafc; border-color: #cbd5e1; }

.btn-save {
    padding: 0.75rem 2rem;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 0.9375rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-save:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(99,102,241,0.3);
}

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
    max-width: 480px;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 25px 50px rgba(0,0,0,0.25);
    overflow: hidden;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.modal-header h3 { font-size: 1.125rem; font-weight: 700; color: #1e293b; margin: 0; }

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

.modal-body { padding: 1.5rem; }

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1.25rem 1.5rem;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
}

.btn-modal-cancel {
    padding: 0.625rem 1.25rem;
    background: #fff;
    color: #64748b;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.875rem;
    cursor: pointer;
}

.btn-modal-save {
    padding: 0.625rem 1.25rem;
    background: #6366f1;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
}

@media (max-width: 768px) {
    .form-row { grid-template-columns: 1fr; }
    .icon-grid { grid-template-columns: repeat(4, 1fr); }
    .pte-footer { flex-direction: column; gap: 1rem; }
    .pte-footer a, .pte-footer button { width: 100%; text-align: center; }
}
</style>

<form method="POST" id="postTypeForm">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <input type="hidden" name="fields_json" id="fieldsJson" value="<?= esc(json_encode($data['fields'])) ?>">
    
    <div class="pte-page">
        <div class="pte-header">
            <a href="post-types.php" class="pte-back">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </a>
            <h1><?= $pageTitle ?></h1>
        </div>
        
        <?php if (!empty($error)): ?>
        <div class="alert"><?= esc($error) ?></div>
        <?php endif; ?>
        
        <!-- Basic Settings -->
        <div class="pte-card">
            <div class="pte-card-header">
                <h2>Basic Settings</h2>
                <p>Define the name and URL structure for this content type</p>
            </div>
            <div class="pte-card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Singular Label <span class="required">*</span></label>
                        <input type="text" name="label_singular" class="form-input" value="<?= esc($data['label_singular']) ?>" placeholder="e.g. Product" required>
                        <div class="form-hint">Name for a single item (e.g. "Add New Product")</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Plural Label <span class="required">*</span></label>
                        <input type="text" name="label_plural" class="form-input" value="<?= esc($data['label_plural']) ?>" placeholder="e.g. Products" required>
                        <div class="form-hint">Name for multiple items (e.g. "All Products")</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Slug <span class="required">*</span></label>
                    <input type="text" name="slug" id="slugInput" class="form-input" value="<?= esc($isEdit ? $editSlug : $data['slug']) ?>" placeholder="e.g. product" required <?= $isEdit ? 'disabled' : '' ?>>
                    <?php if ($isEdit): ?>
                    <input type="hidden" name="slug" value="<?= esc($editSlug) ?>">
                    <?php endif; ?>
                    <div class="form-hint">URL identifier. Lowercase letters, numbers, and underscores only.</div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="checkbox-single">
                            <input type="checkbox" name="is_public" <?= ($data['public'] ?? true) ? 'checked' : '' ?>>
                            <span>Public (visible on frontend)</span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-single">
                            <input type="checkbox" name="has_archive" <?= ($data['has_archive'] ?? true) ? 'checked' : '' ?>>
                            <span>Has archive page</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Icon Selection -->
        <div class="pte-card">
            <div class="pte-card-header">
                <h2>Icon</h2>
                <p>Choose an icon for the admin menu</p>
            </div>
            <div class="pte-card-body">
                <input type="hidden" name="icon" id="iconInput" value="<?= esc($data['icon'] ?? 'file') ?>">
                <div class="icon-grid" id="iconGrid">
                    <?php foreach ($icons as $iconName => $iconLabel): ?>
                    <div class="icon-option<?= ($data['icon'] ?? 'file') === $iconName ? ' selected' : '' ?>" data-icon="<?= $iconName ?>">
                        <?= getAdminMenuIcon($iconName, 24) ?>
                        <span><?= $iconLabel ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Supports -->
        <div class="pte-card">
            <div class="pte-card-header">
                <h2>Features</h2>
                <p>Select which editing features to enable</p>
            </div>
            <div class="pte-card-body">
                <div class="checkbox-group">
                    <?php
                    $supportOptions = [
                        'title' => 'Title',
                        'editor' => 'Content Editor',
                        'excerpt' => 'Excerpt',
                        'thumbnail' => 'Featured Image',
                        'author' => 'Author',
                        'comments' => 'Comments',
                    ];
                    $currentSupports = $data['supports'] ?? [];
                    foreach ($supportOptions as $key => $label):
                    ?>
                    <label class="checkbox-item">
                        <input type="checkbox" name="supports[]" value="<?= $key ?>" <?= in_array($key, $currentSupports) ? 'checked' : '' ?>>
                        <?= $label ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Revisions -->
        <div class="pte-card">
            <div class="pte-card-header">
                <h2>Revisions</h2>
                <p>Control how many revisions to keep for this content type</p>
            </div>
            <div class="pte-card-body">
                <div class="form-group">
                    <label class="form-label">Maximum Revisions</label>
                    <input type="number" name="max_revisions" class="form-input" value="<?= esc($data['max_revisions'] ?? 10) ?>" min="0" max="100" style="max-width: 150px;">
                    <div class="form-hint">Set to 0 to disable revisions. Recommended: 5-20 revisions.</div>
                </div>
            </div>
        </div>
        
        <!-- Custom Fields -->
        <div class="pte-card">
            <div class="pte-card-header">
                <h2>Custom Fields</h2>
                <p>Add custom data fields for this content type</p>
            </div>
            <div class="fields-list" id="fieldsList">
                <!-- Fields rendered by JS -->
            </div>
            <button type="button" class="btn-add-field" id="btnAddField">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Add Custom Field
            </button>
        </div>
        
        <!-- Footer -->
        <div class="pte-card" style="margin-bottom: 0;">
            <div class="pte-footer">
                <a href="post-types.php" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-save">
                    <?= $isEdit ? 'Update Post Type' : 'Create Post Type' ?>
                </button>
            </div>
        </div>
    </div>
</form>

<!-- Field Modal -->
<div class="modal-overlay" id="fieldModal">
    <div class="modal">
        <div class="modal-header">
            <h3 id="fieldModalTitle">Add Field</h3>
            <button type="button" class="modal-close" id="fieldModalClose">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Field Label <span class="required">*</span></label>
                <input type="text" id="fieldLabel" class="form-input" placeholder="e.g. Price">
            </div>
            <div class="form-group">
                <label class="form-label">Field Key <span class="required">*</span></label>
                <input type="text" id="fieldKey" class="form-input" placeholder="e.g. product_price">
                <div class="form-hint">Auto-prefixed with post type slug. Use lowercase and underscores.</div>
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
                    <option value="color">Color</option>
                    <option value="select">Dropdown Select</option>
                    <option value="checkbox">Checkbox</option>
                    <option value="image">Image</option>
                    <option value="file">File</option>
                    <option value="wysiwyg">Rich Text Editor</option>
                </select>
            </div>
            <div class="form-group" id="selectOptionsGroup" style="display: none;">
                <label class="form-label">Options (one per line)</label>
                <textarea id="fieldOptions" class="form-input" rows="4" placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
            </div>
            <div class="form-group">
                <label class="checkbox-single">
                    <input type="checkbox" id="fieldRequired">
                    <span>Required field</span>
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-modal-cancel" id="fieldCancel">Cancel</button>
            <button type="button" class="btn-modal-save" id="fieldSave">Save Field</button>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    // State
    let fields = <?= json_encode($data['fields']) ?>;
    let editingFieldIndex = null;
    
    // Elements
    const fieldsList = document.getElementById('fieldsList');
    const fieldsJson = document.getElementById('fieldsJson');
    const fieldModal = document.getElementById('fieldModal');
    const iconGrid = document.getElementById('iconGrid');
    const iconInput = document.getElementById('iconInput');
    const slugInput = document.getElementById('slugInput');
    const singularInput = document.querySelector('input[name="label_singular"]');
    
    // Auto-generate slug from singular label
    if (singularInput && slugInput && !slugInput.disabled) {
        let autoSlug = true;
        
        singularInput.addEventListener('input', function() {
            if (autoSlug) {
                slugInput.value = this.value.toLowerCase()
                    .replace(/[^a-z0-9]+/g, '_')
                    .replace(/^_|_$/g, '');
            }
        });
        
        slugInput.addEventListener('input', function() {
            autoSlug = this.value === '';
        });
    }
    
    // Icon selection
    iconGrid.addEventListener('click', function(e) {
        const option = e.target.closest('.icon-option');
        if (!option) return;
        
        iconGrid.querySelectorAll('.icon-option').forEach(el => el.classList.remove('selected'));
        option.classList.add('selected');
        iconInput.value = option.dataset.icon;
    });
    
    // Render fields
    function renderFields() {
        if (fields.length === 0) {
            fieldsList.innerHTML = `
                <div class="fields-empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="3" y1="9" x2="21" y2="9"></line>
                        <line x1="9" y1="21" x2="9" y2="9"></line>
                    </svg>
                    <p>No custom fields yet. Click "Add Custom Field" to create one.</p>
                </div>
            `;
        } else {
            let html = '';
            fields.forEach((field, index) => {
                html += `
                    <div class="field-item" data-index="${index}">
                        <div class="field-drag">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="8" y1="6" x2="8" y2="6"></line>
                                <line x1="16" y1="6" x2="16" y2="6"></line>
                                <line x1="8" y1="12" x2="8" y2="12"></line>
                                <line x1="16" y1="12" x2="16" y2="12"></line>
                                <line x1="8" y1="18" x2="8" y2="18"></line>
                                <line x1="16" y1="18" x2="16" y2="18"></line>
                            </svg>
                        </div>
                        <div class="field-info">
                            <div class="field-name">${escapeHtml(field.label)}${field.required ? ' <span style="color:#ef4444">*</span>' : ''}</div>
                            <div class="field-meta">${escapeHtml(field.key)}</div>
                        </div>
                        <span class="field-type">${escapeHtml(field.type)}</span>
                        <div class="field-actions">
                            <button type="button" class="field-btn edit" data-index="${index}" title="Edit">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </button>
                            <button type="button" class="field-btn delete" data-index="${index}" title="Delete">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                `;
            });
            fieldsList.innerHTML = html;
        }
        
        // Update hidden field
        fieldsJson.value = JSON.stringify(fields);
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Field modal
    function openFieldModal(index = null) {
        editingFieldIndex = index;
        
        document.getElementById('fieldModalTitle').textContent = index !== null ? 'Edit Field' : 'Add Field';
        document.getElementById('fieldLabel').value = '';
        document.getElementById('fieldKey').value = '';
        document.getElementById('fieldType').value = 'text';
        document.getElementById('fieldOptions').value = '';
        document.getElementById('fieldRequired').checked = false;
        document.getElementById('selectOptionsGroup').style.display = 'none';
        
        if (index !== null && fields[index]) {
            const field = fields[index];
            document.getElementById('fieldLabel').value = field.label || '';
            document.getElementById('fieldKey').value = field.key || '';
            document.getElementById('fieldType').value = field.type || 'text';
            document.getElementById('fieldOptions').value = (field.options || []).join('\n');
            document.getElementById('fieldRequired').checked = field.required || false;
            
            if (field.type === 'select') {
                document.getElementById('selectOptionsGroup').style.display = 'block';
            }
        }
        
        fieldModal.classList.add('active');
        document.getElementById('fieldLabel').focus();
    }
    
    function closeFieldModal() {
        fieldModal.classList.remove('active');
        editingFieldIndex = null;
    }
    
    function saveField() {
        const label = document.getElementById('fieldLabel').value.trim();
        const key = document.getElementById('fieldKey').value.trim().toLowerCase().replace(/[^a-z0-9_]/g, '');
        const type = document.getElementById('fieldType').value;
        const optionsText = document.getElementById('fieldOptions').value.trim();
        const required = document.getElementById('fieldRequired').checked;
        
        if (!label) {
            alert('Field label is required');
            return;
        }
        
        if (!key) {
            alert('Field key is required');
            return;
        }
        
        // Check for duplicate keys
        const existingIndex = fields.findIndex(f => f.key === key);
        if (existingIndex !== -1 && existingIndex !== editingFieldIndex) {
            alert('A field with this key already exists');
            return;
        }
        
        const field = {
            label: label,
            key: key,
            type: type,
            required: required
        };
        
        if (type === 'select' && optionsText) {
            field.options = optionsText.split('\n').map(o => o.trim()).filter(o => o);
        }
        
        if (editingFieldIndex !== null) {
            fields[editingFieldIndex] = field;
        } else {
            fields.push(field);
        }
        
        renderFields();
        closeFieldModal();
    }
    
    // Event listeners
    document.getElementById('btnAddField').addEventListener('click', () => openFieldModal());
    document.getElementById('fieldModalClose').addEventListener('click', closeFieldModal);
    document.getElementById('fieldCancel').addEventListener('click', closeFieldModal);
    document.getElementById('fieldSave').addEventListener('click', saveField);
    
    fieldModal.addEventListener('click', function(e) {
        if (e.target === this) closeFieldModal();
    });
    
    document.getElementById('fieldType').addEventListener('change', function() {
        document.getElementById('selectOptionsGroup').style.display = this.value === 'select' ? 'block' : 'none';
    });
    
    // Auto-generate key from label (with post type slug prefix)
    document.getElementById('fieldLabel').addEventListener('input', function() {
        const keyInput = document.getElementById('fieldKey');
        if (!keyInput.value || keyInput.dataset.auto === 'true') {
            const slug = slugInput.value.trim();
            const fieldKey = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
            keyInput.value = (slug && fieldKey) ? slug + '_' + fieldKey : fieldKey;
            keyInput.dataset.auto = 'true';
        }
    });
    
    document.getElementById('fieldKey').addEventListener('input', function() {
        this.dataset.auto = 'false';
    });
    
    // Field actions (edit/delete)
    fieldsList.addEventListener('click', function(e) {
        const editBtn = e.target.closest('.field-btn.edit');
        const deleteBtn = e.target.closest('.field-btn.delete');
        
        if (editBtn) {
            const index = parseInt(editBtn.dataset.index);
            openFieldModal(index);
        }
        
        if (deleteBtn) {
            const index = parseInt(deleteBtn.dataset.index);
            if (confirm('Delete this field?')) {
                fields.splice(index, 1);
                renderFields();
            }
        }
    });
    
    // Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && fieldModal.classList.contains('active')) {
            closeFieldModal();
        }
    });
    
    // Initial render
    renderFields();
})();
</script>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
