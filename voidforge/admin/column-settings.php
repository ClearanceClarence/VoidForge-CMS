<?php
/**
 * Column Settings - VoidForge CMS
 * Configure which columns appear in post listings
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

Post::init();
Taxonomy::init();

User::startSession();
User::requireLogin();
User::requireRole('admin');

$postType = $_GET['type'] ?? 'post';
$typeConfig = Post::getType($postType);

if (!$typeConfig) {
    redirect(ADMIN_URL . '/');
}

$pageTitle = 'Column Settings: ' . $typeConfig['label'];

// Get available columns for this post type
function getAvailableColumns(string $postType): array {
    $columns = [
        // Built-in columns
        'id' => ['label' => 'ID', 'type' => 'builtin', 'default_width' => '60px'],
        'title' => ['label' => 'Title', 'type' => 'builtin', 'default_width' => 'auto'],
        'author' => ['label' => 'Author', 'type' => 'builtin', 'default_width' => '120px'],
        'status' => ['label' => 'Status', 'type' => 'builtin', 'default_width' => '100px'],
        'date' => ['label' => 'Date', 'type' => 'builtin', 'default_width' => '140px'],
        'modified' => ['label' => 'Last Modified', 'type' => 'builtin', 'default_width' => '140px'],
        'slug' => ['label' => 'Slug', 'type' => 'builtin', 'default_width' => '150px'],
        'featured_image' => ['label' => 'Featured Image', 'type' => 'builtin', 'default_width' => '80px'],
        'word_count' => ['label' => 'Word Count', 'type' => 'builtin', 'default_width' => '90px'],
        'comments' => ['label' => 'Comments', 'type' => 'builtin', 'default_width' => '80px'],
    ];
    
    // Add taxonomies for this post type
    $taxonomies = Taxonomy::getForPostType($postType);
    foreach ($taxonomies as $slug => $tax) {
        $columns['tax_' . $slug] = [
            'label' => $tax['label'],
            'type' => 'taxonomy',
            'taxonomy' => $slug,
            'default_width' => '120px',
        ];
    }
    
    // Add custom fields for this post type
    $customFields = get_post_type_fields($postType);
    foreach ($customFields as $field) {
        $columns['cf_' . $field['key']] = [
            'label' => $field['label'],
            'type' => 'custom_field',
            'field_key' => $field['key'],
            'field_type' => $field['type'],
            'default_width' => '120px',
        ];
    }
    
    return $columns;
}

// Get current column configuration
function getColumnConfig(string $postType): array {
    $configs = getOption('column_configs', []);
    
    if (isset($configs[$postType])) {
        return $configs[$postType];
    }
    
    // Default configuration
    return [
        'columns' => [
            ['key' => 'title', 'width' => 'auto', 'enabled' => true],
            ['key' => 'author', 'width' => '120px', 'enabled' => true],
            ['key' => 'status', 'width' => '100px', 'enabled' => true],
            ['key' => 'date', 'width' => '140px', 'enabled' => true],
        ]
    ];
}

// Save column configuration
function saveColumnConfig(string $postType, array $config): void {
    $configs = getOption('column_configs', []);
    $configs[$postType] = $config;
    setOption('column_configs', $configs);
}

$availableColumns = getAvailableColumns($postType);
$currentConfig = getColumnConfig($postType);

// Fetch a sample post for preview
$samplePost = null;
$samplePosts = Post::query(['post_type' => $postType, 'limit' => 1]);
if (!empty($samplePosts)) {
    $samplePost = $samplePosts[0];
}

// Get taxonomies and custom fields for rendering
$postTaxonomies = Taxonomy::getForPostType($postType);
$customFields = get_post_type_fields($postType);
$customFieldsByKey = [];
foreach ($customFields as $cf) {
    $customFieldsByKey[$cf['key']] = $cf;
}

/**
 * Render a preview column value
 */
function renderPreviewValue($post, $colKey, $postTaxonomies, $customFieldsByKey) {
    if (!$post) {
        return '<span style="color: var(--text-muted);">—</span>';
    }
    
    // Handle taxonomy columns
    if (strpos($colKey, 'tax_') === 0) {
        $taxSlug = substr($colKey, 4);
        try {
            $terms = Taxonomy::getPostTerms($post['id'], $taxSlug);
            if (empty($terms)) return '<span style="color: var(--text-muted);">—</span>';
            $termNames = array_map(function($t) { return esc($t['name']); }, $terms);
            return implode(', ', $termNames);
        } catch (Exception $e) {
            return '<span style="color: var(--text-muted);">—</span>';
        }
    }
    
    // Handle custom field columns
    if (strpos($colKey, 'cf_') === 0) {
        $fieldKey = substr($colKey, 3);
        $value = get_custom_field($fieldKey, $post['id']);
        $fieldConfig = $customFieldsByKey[$fieldKey] ?? null;
        
        if ($value === null || $value === '') {
            return '<span style="color: var(--text-muted);">—</span>';
        }
        
        if ($fieldConfig) {
            switch ($fieldConfig['type']) {
                case 'checkbox':
                    return $value ? '<span style="color: #059669;">✓</span>' : '—';
                case 'image':
                    $media = Media::find((int)$value);
                    if ($media) {
                        return '<img src="' . esc($media['url']) . '" alt="">';
                    }
                    return '<span style="color: var(--text-muted);">—</span>';
                case 'color':
                    return '<span style="display: inline-block; width: 20px; height: 20px; border-radius: 4px; background: ' . esc($value) . '; border: 1px solid var(--border-color); vertical-align: middle;"></span>';
                case 'date':
                    return formatDate($value, 'M j, Y');
                case 'url':
                    $display = strlen($value) > 30 ? substr($value, 0, 30) . '...' : $value;
                    return '<a href="' . esc($value) . '" target="_blank">' . esc($display) . '</a>';
                case 'textarea':
                case 'wysiwyg':
                    $excerpt = mb_substr(strip_tags($value), 0, 40);
                    return esc($excerpt) . (mb_strlen(strip_tags($value)) > 40 ? '...' : '');
                default:
                    $display = is_array($value) ? implode(', ', $value) : $value;
                    return esc(mb_substr($display, 0, 40)) . (mb_strlen($display) > 40 ? '...' : '');
            }
        }
        
        return esc(mb_substr((string)$value, 0, 40));
    }
    
    // Built-in columns
    switch ($colKey) {
        case 'id':
            return $post['id'];
            
        case 'title':
            $title = $post['title'] ?: '(no title)';
            return '<strong>' . esc($title) . '</strong>';
            
        case 'author':
            $author = Post::getAuthor($post);
            return esc($author['display_name'] ?? 'Unknown');
            
        case 'status':
            return '<span class="status-badge status-' . $post['status'] . '">' . 
                   (Post::STATUS_LABELS[$post['status']] ?? $post['status']) . '</span>';
            
        case 'date':
            return formatDate($post['created_at'], 'M j, Y');
            
        case 'modified':
            return formatDate($post['updated_at'], 'M j, Y');
            
        case 'slug':
            return '<code style="font-size: 0.75rem; background: var(--bg-card-header); padding: 0.125rem 0.375rem; border-radius: 3px;">' . esc($post['slug']) . '</code>';
            
        case 'featured_image':
            if (!empty($post['featured_image_id'])) {
                $media = Media::find($post['featured_image_id']);
                if ($media) {
                    return '<img src="' . esc($media['url']) . '" alt="">';
                }
            }
            return '<span style="color: var(--text-muted);">—</span>';
            
        case 'word_count':
            $count = str_word_count(strip_tags($post['content'] ?? ''));
            return number_format($count);
            
        case 'comments':
            return '0';
            
        default:
            return '<span style="color: var(--text-muted);">—</span>';
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $columns = [];
    $columnKeys = $_POST['column_key'] ?? [];
    $columnWidths = $_POST['column_width'] ?? [];
    $columnLabels = $_POST['column_label'] ?? [];
    $columnEnabled = $_POST['column_enabled'] ?? [];
    
    foreach ($columnKeys as $index => $key) {
        if (isset($availableColumns[$key])) {
            $columns[] = [
                'key' => $key,
                'width' => $columnWidths[$index] ?? 'auto',
                'label' => trim($columnLabels[$index] ?? ''), // Custom label (empty = use default)
                'enabled' => isset($columnEnabled[$index]),
            ];
        }
    }
    
    saveColumnConfig($postType, ['columns' => $columns]);
    setFlash('success', 'Column settings saved successfully.');
    redirect(ADMIN_URL . '/column-settings.php?type=' . $postType);
}

// Build active and available lists
$activeColumns = [];
$usedKeys = [];
foreach ($currentConfig['columns'] as $col) {
    if (isset($availableColumns[$col['key']])) {
        $activeColumns[] = array_merge($availableColumns[$col['key']], [
            'key' => $col['key'],
            'width' => $col['width'] ?? 'auto',
            'custom_label' => $col['label'] ?? '', // Custom label override
            'enabled' => $col['enabled'] ?? true,
        ]);
        $usedKeys[] = $col['key'];
    }
}

// Available columns not yet added
$unusedColumns = [];
foreach ($availableColumns as $key => $col) {
    if (!in_array($key, $usedKeys)) {
        $unusedColumns[$key] = $col;
    }
}

include ADMIN_PATH . '/includes/header.php';
?>

<style>
/* Page header enhancement */
.page-header {
    margin-bottom: 2rem;
}

.column-manager {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 1.5rem;
    align-items: start;
}

/* Active Columns Panel */
.active-columns {
    background: var(--bg-card);
    border-radius: var(--border-radius-lg);
    border: 1px solid var(--border-color);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.active-columns-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(to bottom, rgba(99, 102, 241, 0.05), transparent);
}

.active-columns-header h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.active-columns-header h3::before {
    content: '';
    width: 4px;
    height: 18px;
    background: var(--forge-primary);
    border-radius: 2px;
}

.column-count {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.625rem;
    background: var(--forge-primary);
    color: white;
    border-radius: 9999px;
}

.column-list {
    padding: 0.75rem;
    min-height: 200px;
}

/* Column Items */
.column-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 1rem;
    background: linear-gradient(135deg, var(--bg-card-header) 0%, rgba(99, 102, 241, 0.02) 100%);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    margin-bottom: 0.5rem;
    cursor: grab;
    transition: all 0.2s ease;
}

.column-item:hover {
    background: linear-gradient(135deg, var(--bg-hover) 0%, rgba(99, 102, 241, 0.05) 100%);
    border-color: rgba(99, 102, 241, 0.3);
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.column-item.dragging {
    opacity: 0.6;
    cursor: grabbing;
    transform: rotate(1deg);
}

.column-item.drag-over {
    border-top: 3px solid var(--forge-primary);
    margin-top: -3px;
    background: rgba(99, 102, 241, 0.08);
}

.column-drag-handle {
    color: var(--text-muted);
    cursor: grab;
    padding: 0.25rem;
    opacity: 0.5;
    transition: opacity 0.15s;
}

.column-item:hover .column-drag-handle {
    opacity: 1;
}

.column-drag-handle:hover {
    color: var(--forge-primary);
}

/* Checkbox styling */
.column-checkbox {
    flex-shrink: 0;
}

.column-checkbox input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: var(--forge-primary);
    cursor: pointer;
}

.column-info {
    flex: 1;
    min-width: 0;
}

.column-label-row {
    margin-bottom: 0.375rem;
}

.column-label-input {
    width: 100%;
    padding: 0.375rem 0.625rem;
    font-size: 0.875rem;
    font-weight: 600;
    background: var(--bg-input);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    color: var(--text-primary);
    transition: all 0.15s;
}

.column-label-input:focus {
    border-color: var(--forge-primary);
    outline: none;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}

.column-label-input::placeholder {
    color: var(--text-muted);
    font-weight: 500;
}

.column-label {
    font-weight: 600;
    font-size: 0.875rem;
    margin-bottom: 0.125rem;
}

.column-type {
    font-size: 0.75rem;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.column-key-hint {
    font-family: 'SF Mono', Monaco, 'Cascadia Code', monospace;
    font-size: 0.6875rem;
    opacity: 0.5;
    background: rgba(99, 102, 241, 0.08);
    padding: 0.125rem 0.375rem;
    border-radius: 3px;
}

.column-width {
    width: 85px;
    flex-shrink: 0;
}

.column-width input {
    width: 100%;
    padding: 0.375rem 0.5rem;
    font-size: 0.8125rem;
    font-family: 'SF Mono', Monaco, monospace;
    background: var(--bg-input);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    color: var(--text-primary);
    text-align: center;
}

.column-width input:focus {
    border-color: var(--forge-primary);
    outline: none;
}

.column-remove {
    flex-shrink: 0;
    padding: 0.5rem;
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    border-radius: var(--border-radius);
    transition: all 0.15s;
    opacity: 0.5;
}

.column-item:hover .column-remove {
    opacity: 1;
}

.column-remove:hover {
    color: #ef4444;
    background: rgba(239, 68, 68, 0.1);
    transform: scale(1.1);
}

/* Available Columns Panel */
.available-columns {
    background: var(--bg-card);
    border-radius: var(--border-radius-lg);
    border: 1px solid var(--border-color);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    position: sticky;
    top: 1rem;
}

.available-columns-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
    background: linear-gradient(to bottom, rgba(16, 185, 129, 0.05), transparent);
}

.available-columns-header h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.available-columns-header h3::before {
    content: '';
    width: 4px;
    height: 18px;
    background: #10b981;
    border-radius: 2px;
}

.available-list {
    padding: 0.75rem;
    max-height: 500px;
    overflow-y: auto;
}

.available-list::-webkit-scrollbar {
    width: 6px;
}

.available-list::-webkit-scrollbar-track {
    background: transparent;
}

.available-list::-webkit-scrollbar-thumb {
    background: var(--border-color);
    border-radius: 3px;
}

.available-group {
    margin-bottom: 1.25rem;
}

.available-group:last-child {
    margin-bottom: 0;
}

.available-group-title {
    font-size: 0.6875rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--text-muted);
    padding: 0.5rem 0.75rem;
    margin-bottom: 0.25rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.available-group-title::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--border-color);
}

.available-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.625rem 0.875rem;
    background: var(--bg-card-header);
    border: 1px solid transparent;
    border-radius: var(--border-radius);
    margin-bottom: 0.375rem;
    font-size: 0.8125rem;
    font-weight: 500;
    transition: all 0.15s;
}

.available-item:hover {
    border-color: rgba(16, 185, 129, 0.3);
    background: rgba(16, 185, 129, 0.05);
}

.available-item-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-primary);
}

.available-item button {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 600;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border: none;
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: all 0.15s;
    box-shadow: 0 1px 2px rgba(16, 185, 129, 0.2);
}

.available-item button:hover {
    background: linear-gradient(135deg, #059669, #047857);
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);
}

/* Type Badges */
.type-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.1875rem 0.625rem;
    font-size: 0.6875rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    border-radius: 9999px;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(99, 102, 241, 0.1));
    color: #6366f1;
    border: 1px solid rgba(99, 102, 241, 0.2);
}

.type-badge.taxonomy {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(16, 185, 129, 0.1));
    color: #059669;
    border-color: rgba(16, 185, 129, 0.2);
}

.type-badge.custom_field {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.15), rgba(245, 158, 11, 0.1));
    color: #d97706;
    border-color: rgba(245, 158, 11, 0.2);
}

/* Empty State */
.empty-state-small {
    text-align: center;
    padding: 2.5rem 1.5rem;
    color: var(--text-muted);
    font-size: 0.875rem;
}

.empty-state-small::before {
    content: '✓';
    display: block;
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}

.preview-table {
    margin-top: 2rem;
    background: var(--bg-card);
    border-radius: var(--border-radius-lg);
    border: 1px solid var(--border-color);
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.preview-table-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(to bottom, rgba(245, 158, 11, 0.05), transparent);
}

.preview-table-header h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.preview-table-header h3::before {
    content: '';
    width: 4px;
    height: 18px;
    background: #f59e0b;
    border-radius: 2px;
}

.preview-table-wrapper {
    overflow-x: auto;
}

.preview-table table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.preview-table th {
    position: relative;
    padding: 0.875rem 1rem;
    text-align: left;
    font-size: 0.6875rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--text-muted);
    background: var(--bg-card-header);
    border-bottom: 2px solid var(--border-color);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.preview-table th .resize-handle {
    position: absolute;
    right: 0;
    top: 0;
    bottom: 0;
    width: 8px;
    cursor: col-resize;
    background: transparent;
    transition: background 0.15s;
}

.preview-table th .resize-handle:hover {
    background: var(--forge-primary);
}

.preview-table td {
    padding: 1rem;
    font-size: 0.875rem;
    border-bottom: 1px solid var(--border-color);
    color: var(--text-primary);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    background: var(--bg-card);
}

.preview-table tbody tr:hover td {
    background: rgba(99, 102, 241, 0.03);
}

.preview-table td img {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 6px;
    vertical-align: middle;
    border: 2px solid var(--border-color);
}

.preview-table .status-badge {
    display: inline-block;
    padding: 0.25rem 0.625rem;
    font-size: 0.6875rem;
    font-weight: 600;
    border-radius: 9999px;
    letter-spacing: 0.02em;
}

.preview-table .status-published { 
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(16, 185, 129, 0.1)); 
    color: #059669; 
    border: 1px solid rgba(16, 185, 129, 0.2);
}
.preview-table .status-draft { 
    background: linear-gradient(135deg, rgba(107, 114, 128, 0.15), rgba(107, 114, 128, 0.1)); 
    color: #6b7280; 
    border: 1px solid rgba(107, 114, 128, 0.2);
}
.preview-table .status-scheduled { 
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(99, 102, 241, 0.1)); 
    color: #6366f1; 
    border: 1px solid rgba(99, 102, 241, 0.2);
}
.preview-table .status-trash { 
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.15), rgba(239, 68, 68, 0.1)); 
    color: #ef4444; 
    border: 1px solid rgba(239, 68, 68, 0.2);
}

.preview-table .preview-actions {
    display: flex;
    gap: 0.375rem;
}

.preview-table .preview-actions .btn {
    padding: 0.375rem 0.625rem;
    font-size: 0.75rem;
    font-weight: 500;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 0.75rem;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border-color);
}

.action-buttons .btn-primary {
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    box-shadow: 0 2px 4px rgba(99, 102, 241, 0.2);
}

.action-buttons .btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(99, 102, 241, 0.3);
}

.preview-tip {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1.25rem;
    font-size: 0.8125rem;
    color: var(--text-muted);
    background: rgba(245, 158, 11, 0.05);
    border-top: 1px solid var(--border-color);
}

.preview-tip svg {
    flex-shrink: 0;
    color: #f59e0b;
}

@media (max-width: 1024px) {
    .column-manager {
        grid-template-columns: 1fr;
    }
    
    .available-columns {
        position: static;
    }
}
</style>

<div class="page-header" style="margin-bottom: 1.5rem;">
    <div>
        <h2><?= esc($pageTitle) ?></h2>
        <p style="color: var(--text-muted); margin-top: 0.25rem; font-size: 0.875rem;">
            Customize which columns appear in the <?= esc(strtolower($typeConfig['label'])) ?> list and adjust their widths.
        </p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="<?= ADMIN_URL ?>/posts.php?type=<?= esc($postType) ?>" class="btn btn-secondary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Back to <?= esc($typeConfig['label']) ?>
        </a>
    </div>
</div>

<!-- Post Type Tabs -->
<div class="action-bar" style="margin-bottom: 1.5rem;">
    <div class="action-bar-left">
        <?php foreach (Post::getTypes() as $slug => $config): ?>
        <a href="?type=<?= esc($slug) ?>" class="btn btn-secondary btn-sm <?= $slug === $postType ? 'active' : '' ?>">
            <?= esc($config['label']) ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<form method="post" id="columnForm">
    <?= csrfField() ?>
    
    <div class="column-manager">
        <!-- Active Columns -->
        <div class="active-columns">
            <div class="active-columns-header">
                <h3>Active Columns</h3>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <span class="column-count" id="columnCount"><?= count(array_filter($activeColumns, fn($c) => $c['enabled'])) ?></span>
                    <span style="font-size: 0.75rem; color: var(--text-muted);">Drag to reorder</span>
                </div>
            </div>
            <div class="column-list" id="columnList">
                <?php if (empty($activeColumns)): ?>
                <div class="empty-state-small">
                    No columns configured. Add columns from the right panel.
                </div>
                <?php else: ?>
                <?php foreach ($activeColumns as $index => $col): ?>
                <div class="column-item" draggable="true" data-key="<?= esc($col['key']) ?>" data-default-label="<?= esc($col['label']) ?>">
                    <div class="column-drag-handle">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="9" cy="5" r="1"></circle>
                            <circle cx="9" cy="12" r="1"></circle>
                            <circle cx="9" cy="19" r="1"></circle>
                            <circle cx="15" cy="5" r="1"></circle>
                            <circle cx="15" cy="12" r="1"></circle>
                            <circle cx="15" cy="19" r="1"></circle>
                        </svg>
                    </div>
                    <div class="column-checkbox">
                        <input type="checkbox" name="column_enabled[<?= $index ?>]" <?= $col['enabled'] ? 'checked' : '' ?>>
                    </div>
                    <div class="column-info">
                        <div class="column-label-row">
                            <input type="text" name="column_label[<?= $index ?>]" value="<?= esc($col['custom_label'] ?? '') ?>" placeholder="<?= esc($col['label']) ?>" class="column-label-input" title="Custom label (leave empty for default)">
                        </div>
                        <div class="column-type">
                            <span class="type-badge <?= esc($col['type']) ?>"><?= ucfirst(str_replace('_', ' ', $col['type'])) ?></span>
                            <span class="column-key-hint"><?= esc($col['key']) ?></span>
                        </div>
                    </div>
                    <div class="column-width">
                        <input type="text" name="column_width[<?= $index ?>]" value="<?= esc($col['width']) ?>" placeholder="auto" title="Width (px, %, or auto)">
                    </div>
                    <input type="hidden" name="column_key[<?= $index ?>]" value="<?= esc($col['key']) ?>">
                    <button type="button" class="column-remove" onclick="removeColumn(this)" title="Remove column">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Available Columns -->
        <div class="available-columns">
            <div class="available-columns-header">
                <h3>Available Columns</h3>
            </div>
            <div class="available-list" id="availableList">
                <?php
                // Group columns by type
                $grouped = ['builtin' => [], 'taxonomy' => [], 'custom_field' => []];
                foreach ($unusedColumns as $key => $col) {
                    $grouped[$col['type']][$key] = $col;
                }
                ?>
                
                <?php if (!empty($grouped['builtin'])): ?>
                <div class="available-group">
                    <div class="available-group-title">Built-in</div>
                    <?php foreach ($grouped['builtin'] as $key => $col): ?>
                    <div class="available-item" data-key="<?= esc($key) ?>">
                        <span class="available-item-label"><?= esc($col['label']) ?></span>
                        <button type="button" onclick="addColumn('<?= esc($key) ?>')">+ Add</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($grouped['taxonomy'])): ?>
                <div class="available-group">
                    <div class="available-group-title">Taxonomies</div>
                    <?php foreach ($grouped['taxonomy'] as $key => $col): ?>
                    <div class="available-item" data-key="<?= esc($key) ?>">
                        <span class="available-item-label"><?= esc($col['label']) ?></span>
                        <button type="button" onclick="addColumn('<?= esc($key) ?>')">+ Add</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($grouped['custom_field'])): ?>
                <div class="available-group">
                    <div class="available-group-title">Custom Fields</div>
                    <?php foreach ($grouped['custom_field'] as $key => $col): ?>
                    <div class="available-item" data-key="<?= esc($key) ?>">
                        <span class="available-item-label"><?= esc($col['label']) ?></span>
                        <button type="button" onclick="addColumn('<?= esc($key) ?>')">+ Add</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <?php if (empty($unusedColumns)): ?>
                <div class="empty-state-small">
                    All available columns have been added.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Preview -->
    <div class="preview-table">
        <div class="preview-table-header">
            <h3>Preview</h3>
            <span style="font-size: 0.75rem; color: var(--text-muted);">How your columns will appear</span>
        </div>
        <div class="preview-table-wrapper">
            <table id="previewTable">
                <colgroup id="previewColgroup">
                    <?php foreach ($activeColumns as $index => $col): ?>
                    <?php if ($col['enabled']): ?>
                    <?php $width = !empty($col['width']) && $col['width'] !== 'auto' ? $col['width'] : '150px'; ?>
                    <col data-col-key="<?= esc($col['key']) ?>" style="width: <?= esc($width) ?>;">
                    <?php endif; ?>
                    <?php endforeach; ?>
                    <col style="width: 140px;">
                </colgroup>
                <thead>
                    <tr>
                        <?php foreach ($activeColumns as $index => $col): ?>
                        <?php if ($col['enabled']): ?>
                        <?php $displayLabel = !empty($col['custom_label']) ? $col['custom_label'] : $col['label']; ?>
                        <th data-col-key="<?= esc($col['key']) ?>">
                            <?= esc($displayLabel) ?>
                            <span class="resize-handle"></span>
                        </th>
                        <?php endif; ?>
                        <?php endforeach; ?>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($samplePost): ?>
                    <tr>
                        <?php foreach ($activeColumns as $col): ?>
                        <?php if ($col['enabled']): ?>
                        <td><?= renderPreviewValue($samplePost, $col['key'], $postTaxonomies, $customFieldsByKey) ?></td>
                        <?php endif; ?>
                        <?php endforeach; ?>
                        <td>
                            <div class="preview-actions">
                                <span class="btn btn-secondary">Edit</span>
                                <span class="btn btn-secondary">View</span>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <tr>
                        <?php foreach ($activeColumns as $col): ?>
                        <?php if ($col['enabled']): ?>
                        <td><span style="color: var(--text-muted);">No posts yet</span></td>
                        <?php endif; ?>
                        <?php endforeach; ?>
                        <td>
                            <div class="preview-actions">
                                <span class="btn btn-secondary">Edit</span>
                                <span class="btn btn-secondary">View</span>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="preview-tip">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="16" x2="12" y2="12"></line>
                <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            Drag column borders to resize. Widths will be saved when you click "Save Column Settings".
        </div>
    </div>
    
    <div class="action-buttons">
        <button type="submit" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                <polyline points="7 3 7 8 15 8"></polyline>
            </svg>
            Save Column Settings
        </button>
        <button type="button" class="btn btn-secondary" onclick="resetToDefaults()">
            Reset to Defaults
        </button>
    </div>
</form>

<script>
// Column data from PHP
var availableColumns = <?= json_encode($availableColumns) ?>;
var columnIndex = <?= count($activeColumns) ?>;

// Drag and drop
var draggedItem = null;

document.querySelectorAll('.column-item').forEach(function(item) {
    item.addEventListener('dragstart', handleDragStart);
    item.addEventListener('dragend', handleDragEnd);
    item.addEventListener('dragover', handleDragOver);
    item.addEventListener('dragleave', handleDragLeave);
    item.addEventListener('drop', handleDrop);
});

function handleDragStart(e) {
    draggedItem = this;
    this.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
}

function handleDragEnd(e) {
    this.classList.remove('dragging');
    document.querySelectorAll('.column-item').forEach(function(item) {
        item.classList.remove('drag-over');
    });
    updateIndices();
}

function handleDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    if (this !== draggedItem) {
        this.classList.add('drag-over');
    }
}

function handleDragLeave(e) {
    this.classList.remove('drag-over');
}

function handleDrop(e) {
    e.preventDefault();
    if (this !== draggedItem) {
        var list = document.getElementById('columnList');
        var items = Array.from(list.querySelectorAll('.column-item'));
        var draggedIndex = items.indexOf(draggedItem);
        var dropIndex = items.indexOf(this);
        
        if (draggedIndex < dropIndex) {
            this.parentNode.insertBefore(draggedItem, this.nextSibling);
        } else {
            this.parentNode.insertBefore(draggedItem, this);
        }
    }
    this.classList.remove('drag-over');
}

function updateIndices() {
    var items = document.querySelectorAll('.column-item');
    items.forEach(function(item, index) {
        var inputs = item.querySelectorAll('input');
        inputs.forEach(function(input) {
            var name = input.name;
            input.name = name.replace(/\[\d+\]/, '[' + index + ']');
        });
    });
    updatePreview();
}

function addColumn(key) {
    if (!availableColumns[key]) return;
    
    var col = availableColumns[key];
    var list = document.getElementById('columnList');
    
    // Remove empty state if present
    var emptyState = list.querySelector('.empty-state-small');
    if (emptyState) emptyState.remove();
    
    var item = document.createElement('div');
    item.className = 'column-item';
    item.draggable = true;
    item.dataset.key = key;
    
    var typeClass = col.type || 'builtin';
    
    item.innerHTML = 
        '<div class="column-drag-handle">' +
            '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                '<circle cx="9" cy="5" r="1"></circle><circle cx="9" cy="12" r="1"></circle><circle cx="9" cy="19" r="1"></circle>' +
                '<circle cx="15" cy="5" r="1"></circle><circle cx="15" cy="12" r="1"></circle><circle cx="15" cy="19" r="1"></circle>' +
            '</svg>' +
        '</div>' +
        '<div class="column-checkbox">' +
            '<input type="checkbox" name="column_enabled[' + columnIndex + ']" checked>' +
        '</div>' +
        '<div class="column-info">' +
            '<div class="column-label-row">' +
                '<input type="text" name="column_label[' + columnIndex + ']" value="" placeholder="' + escapeHtml(col.label) + '" class="column-label-input" title="Custom label (leave empty for default)">' +
            '</div>' +
            '<div class="column-type"><span class="type-badge ' + typeClass + '">' + col.type.replace('_', ' ') + '</span><span class="column-key-hint">' + key + '</span></div>' +
        '</div>' +
        '<div class="column-width">' +
            '<input type="text" name="column_width[' + columnIndex + ']" value="' + (col.default_width || 'auto') + '" placeholder="auto">' +
        '</div>' +
        '<input type="hidden" name="column_key[' + columnIndex + ']" value="' + key + '">' +
        '<button type="button" class="column-remove" onclick="removeColumn(this)" title="Remove column">' +
            '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                '<line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line>' +
            '</svg>' +
        '</button>';
    
    item.dataset.defaultLabel = col.label;
    
    // Add drag event listeners
    item.addEventListener('dragstart', handleDragStart);
    item.addEventListener('dragend', handleDragEnd);
    item.addEventListener('dragover', handleDragOver);
    item.addEventListener('dragleave', handleDragLeave);
    item.addEventListener('drop', handleDrop);
    
    list.appendChild(item);
    columnIndex++;
    
    // Remove from available list
    var availableItem = document.querySelector('.available-item[data-key="' + key + '"]');
    if (availableItem) availableItem.remove();
    
    // Check if group is empty
    document.querySelectorAll('.available-group').forEach(function(group) {
        if (group.querySelectorAll('.available-item').length === 0) {
            group.remove();
        }
    });
    
    // Show empty state if no more available
    var availableList = document.getElementById('availableList');
    if (availableList.querySelectorAll('.available-item').length === 0) {
        availableList.innerHTML = '<div class="empty-state-small">All available columns have been added.</div>';
    }
    
    updatePreview();
}

function removeColumn(btn) {
    var item = btn.closest('.column-item');
    var key = item.dataset.key;
    var col = availableColumns[key];
    
    item.remove();
    updateIndices();
    
    // Add back to available list
    var availableList = document.getElementById('availableList');
    var emptyState = availableList.querySelector('.empty-state-small');
    if (emptyState) emptyState.remove();
    
    // Find or create the appropriate group
    var groupType = col.type;
    var groupTitle = groupType === 'builtin' ? 'Built-in' : 
                     groupType === 'taxonomy' ? 'Taxonomies' : 'Custom Fields';
    
    var group = availableList.querySelector('.available-group[data-type="' + groupType + '"]');
    if (!group) {
        group = document.createElement('div');
        group.className = 'available-group';
        group.dataset.type = groupType;
        group.innerHTML = '<div class="available-group-title">' + groupTitle + '</div>';
        availableList.appendChild(group);
    }
    
    var availableItem = document.createElement('div');
    availableItem.className = 'available-item';
    availableItem.dataset.key = key;
    availableItem.innerHTML = 
        '<span class="available-item-label">' + escapeHtml(col.label) + '</span>' +
        '<button type="button" onclick="addColumn(\'' + key + '\')">+ Add</button>';
    
    group.appendChild(availableItem);
}

function updatePreview() {
    var colgroup = document.getElementById('previewColgroup');
    var thead = document.querySelector('#previewTable thead tr');
    var tbody = document.querySelector('#previewTable tbody tr');
    
    if (!colgroup || !thead || !tbody) return;
    
    colgroup.innerHTML = '';
    thead.innerHTML = '';
    tbody.innerHTML = '';
    
    document.querySelectorAll('.column-item').forEach(function(item, idx) {
        var checkbox = item.querySelector('input[type="checkbox"]');
        if (checkbox && checkbox.checked) {
            var labelInput = item.querySelector('.column-label-input');
            var label = labelInput.value || labelInput.placeholder || item.dataset.defaultLabel;
            var widthInput = item.querySelector('.column-width input');
            var width = widthInput.value || '150px';
            if (width === 'auto') width = '150px';
            var key = item.dataset.key;
            
            // Add col to colgroup
            var col = document.createElement('col');
            col.dataset.colKey = key;
            col.style.width = width;
            colgroup.appendChild(col);
            
            // Add th
            var th = document.createElement('th');
            th.dataset.colKey = key;
            th.innerHTML = escapeHtml(label) + '<span class="resize-handle"></span>';
            thead.appendChild(th);
            
            // Add td
            var td = document.createElement('td');
            if (typeof previewData !== 'undefined' && previewData[key]) {
                td.innerHTML = previewData[key];
            } else {
                td.innerHTML = '<span style="color: var(--text-muted);">Sample</span>';
            }
            tbody.appendChild(td);
        }
    });
    
    // Add actions column
    var colActions = document.createElement('col');
    colActions.style.width = '140px';
    colgroup.appendChild(colActions);
    
    var thActions = document.createElement('th');
    thActions.textContent = 'Actions';
    thead.appendChild(thActions);
    
    var tdActions = document.createElement('td');
    tdActions.innerHTML = '<div class="preview-actions"><span class="btn btn-secondary">Edit</span><span class="btn btn-secondary">View</span></div>';
    tbody.appendChild(tdActions);
}

function resetToDefaults() {
    if (confirm('Reset columns to default configuration? This will remove all customizations.')) {
        // Clear storage for this post type and reload
        window.location.href = window.location.pathname + '?type=<?= esc($postType) ?>&reset=1';
    }
}

function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Preview data from PHP
var previewData = <?= json_encode([
    'id' => $samplePost ? (string)$samplePost['id'] : '—',
    'title' => $samplePost ? '<strong>' . esc($samplePost['title'] ?: '(no title)') . '</strong>' : '<span style="color: var(--text-muted);">—</span>',
    'author' => $samplePost ? esc(Post::getAuthor($samplePost)['display_name'] ?? 'Unknown') : '—',
    'status' => $samplePost ? '<span class="status-badge status-' . $samplePost['status'] . '">' . (Post::STATUS_LABELS[$samplePost['status']] ?? $samplePost['status']) . '</span>' : '—',
    'date' => $samplePost ? formatDate($samplePost['created_at'], 'M j, Y') : '—',
    'modified' => $samplePost ? formatDate($samplePost['updated_at'], 'M j, Y') : '—',
    'slug' => $samplePost ? '<code style="font-size: 0.75rem; background: var(--bg-card-header); padding: 0.125rem 0.375rem; border-radius: 3px;">' . esc($samplePost['slug']) . '</code>' : '—',
    'word_count' => $samplePost ? number_format(str_word_count(strip_tags($samplePost['content'] ?? ''))) : '0',
]) ?>;

// Preview table resize using colgroup
(function() {
    var colgroup = document.getElementById('previewColgroup');
    if (!colgroup) return;
    
    var dragging = false, startX, startW, currentCol, currentKey;
    
    document.addEventListener('mousedown', function(e) {
        if (!e.target.classList.contains('resize-handle')) return;
        if (!e.target.closest('#previewTable')) return;
        
        var th = e.target.parentElement;
        if (!th) return;
        
        var key = th.dataset.colKey;
        currentCol = colgroup.querySelector('col[data-col-key="' + key + '"]');
        if (!currentCol) return;
        
        e.preventDefault();
        dragging = true;
        currentKey = key;
        startX = e.pageX;
        startW = parseInt(currentCol.style.width) || 150;
    });
    
    document.addEventListener('mousemove', function(e) {
        if (!dragging || !currentCol) return;
        var newW = Math.max(50, startW + e.pageX - startX);
        currentCol.style.width = newW + 'px';
    });
    
    document.addEventListener('mouseup', function() {
        if (!dragging) return;
        dragging = false;
        // Update width input
        if (currentKey && currentCol) {
            var input = document.querySelector('.column-item[data-key="' + currentKey + '"] .column-width input');
            if (input) input.value = currentCol.style.width;
        }
        currentCol = null;
        currentKey = null;
    });
})();

// Listen for checkbox changes to update preview and count
document.getElementById('columnList').addEventListener('change', function(e) {
    if (e.target.type === 'checkbox') {
        updatePreview();
        updateColumnCount();
    }
});

// Listen for width and label changes to update preview
document.getElementById('columnList').addEventListener('input', function(e) {
    updatePreview();
});

// Update column count badge
function updateColumnCount() {
    var count = document.querySelectorAll('#columnList .column-item input[type="checkbox"]:checked').length;
    var badge = document.getElementById('columnCount');
    if (badge) badge.textContent = count;
}
</script>

<?php
// Handle reset
if (isset($_GET['reset'])) {
    $configs = getOption('column_configs', []);
    unset($configs[$postType]);
    setOption('column_configs', $configs);
    redirect(ADMIN_URL . '/column-settings.php?type=' . $postType);
}
?>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
