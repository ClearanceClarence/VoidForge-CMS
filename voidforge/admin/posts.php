<?php
/**
 * Posts Listing (Posts, Pages, Custom Post Types)
 * VoidForge CMS v0.1.7 - With Bulk Actions & Quick Edit
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

// Auto-run migrations if version mismatch
$dbVersion = getOption('cms_version', '0.0.0');
if (version_compare($dbVersion, CMS_VERSION, '<')) {
    try {
        $pdo = Database::getInstance();
        require_once CMS_ROOT . '/includes/migrations.php';
    } catch (Exception $e) {
        // Migration failed, continue anyway
    }
}

// Auto-publish scheduled posts and cleanup old trash (pseudo-cron)
try {
    Post::publishScheduledPosts();
    Post::cleanupOldTrash();
} catch (Exception $e) {
    // Columns might not exist yet, ignore
}

// Get post type
$postType = $_GET['type'] ?? 'post';
$typeConfig = Post::getType($postType);

if (!$typeConfig) {
    redirect(ADMIN_URL . '/');
}

$pageTitle = $typeConfig['label'];

// Pagination
$perPage = 20;
$currentPage = max(1, (int)($_GET['paged'] ?? 1));

// Filters
$status = $_GET['status'] ?? null;
$search = $_GET['s'] ?? null;

// Get taxonomies for this post type
$postTaxonomies = Taxonomy::getForPostType($postType);

// Count totals
$totalAll = Post::count(['post_type' => $postType]);
$totalPublished = Post::count(['post_type' => $postType, 'status' => 'published']);
$totalDraft = Post::count(['post_type' => $postType, 'status' => 'draft']);
$totalTrash = Post::count(['post_type' => $postType, 'status' => 'trash']);

// Scheduled count (may fail if column/status doesn't exist yet)
$totalScheduled = 0;
try {
    $totalScheduled = Post::count(['post_type' => $postType, 'status' => 'scheduled']);
} catch (Exception $e) {
    // Status enum might not include 'scheduled' yet
}

// Get posts
$queryArgs = [
    'post_type' => $postType,
    'status' => $status ?: ['published', 'draft', 'scheduled'],
    'search' => $search,
    'limit' => $perPage,
    'offset' => ($currentPage - 1) * $perPage,
];

if ($status === 'trash') {
    $queryArgs['status'] = 'trash';
} elseif ($status === 'scheduled') {
    $queryArgs['status'] = 'scheduled';
}

$posts = Post::query($queryArgs);
$total = Post::count($queryArgs);
$pagination = paginate($total, $perPage, $currentPage);

// Handle single post actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $action = $_POST['action'] ?? '';
    $postId = (int)($_POST['post_id'] ?? 0);

    // Single post actions
    if ($postId && $action && $action !== 'bulk') {
        switch ($action) {
            case 'trash':
                Post::delete($postId);
                setFlash('success', 'Item moved to trash.');
                break;
            case 'restore':
                Post::restore($postId);
                setFlash('success', 'Item restored.');
                break;
            case 'delete':
                Post::delete($postId, true);
                setFlash('success', 'Item permanently deleted.');
                break;
            case 'duplicate':
                $newId = Post::duplicate($postId);
                if ($newId) {
                    setFlash('success', 'Item duplicated successfully.');
                    redirect(ADMIN_URL . '/post-edit.php?id=' . $newId);
                } else {
                    setFlash('error', 'Failed to duplicate item.');
                }
                break;
        }
        redirect(currentUrl());
    }
    
    // Empty trash action
    if (($action ?? '') === 'empty_trash') {
        $deleted = Post::emptyTrash($postType);
        setFlash('success', $deleted . ' item(s) permanently deleted from trash.');
        redirect(ADMIN_URL . '/posts.php?type=' . $postType);
    }
    
    // Bulk actions
    if ($action === 'bulk') {
        $bulkAction = $_POST['bulk_action'] ?? '';
        $postIds = $_POST['post_ids'] ?? [];
        
        if (!empty($postIds) && !empty($bulkAction)) {
            $postIds = array_map('intval', $postIds);
            $count = 0;
            
            switch ($bulkAction) {
                case 'trash':
                    foreach ($postIds as $pid) {
                        if (Post::delete($pid)) $count++;
                    }
                    setFlash('success', $count . ' item(s) moved to trash.');
                    break;
                    
                case 'restore':
                    foreach ($postIds as $pid) {
                        if (Post::restore($pid)) $count++;
                    }
                    setFlash('success', $count . ' item(s) restored.');
                    break;
                    
                case 'delete':
                    foreach ($postIds as $pid) {
                        if (Post::delete($pid, true)) $count++;
                    }
                    setFlash('success', $count . ' item(s) permanently deleted.');
                    break;
                    
                case 'publish':
                    foreach ($postIds as $pid) {
                        if (Post::update($pid, ['status' => 'published'])) $count++;
                    }
                    setFlash('success', $count . ' item(s) published.');
                    break;
                    
                case 'draft':
                    foreach ($postIds as $pid) {
                        if (Post::update($pid, ['status' => 'draft'])) $count++;
                    }
                    setFlash('success', $count . ' item(s) set to draft.');
                    break;
                    
                default:
                    // Check for taxonomy assignment (format: add_tax_{taxonomy} or remove_tax_{taxonomy})
                    if (preg_match('/^(add|remove)_tax_(.+)$/', $bulkAction, $m)) {
                        $taxAction = $m[1];
                        $taxSlug = $m[2];
                        $termIds = $_POST['bulk_term_ids'] ?? [];
                        
                        if (!empty($termIds)) {
                            $termIds = array_map('intval', $termIds);
                            foreach ($postIds as $pid) {
                                if ($taxAction === 'add') {
                                    Taxonomy::addPostTerms($pid, $termIds);
                                } else {
                                    Taxonomy::removePostTerms($pid, $termIds);
                                }
                                $count++;
                            }
                            // Update counts
                            Taxonomy::updateTermCounts($taxSlug);
                            setFlash('success', 'Taxonomy updated for ' . $count . ' item(s).');
                        }
                    }
            }
        }
        redirect(currentUrl());
    }
}

// Handle Quick Edit AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_edit']) && verifyCsrf()) {
    header('Content-Type: application/json');
    
    $postId = (int)($_POST['post_id'] ?? 0);
    $post = Post::find($postId);
    
    if (!$post) {
        echo json_encode(['success' => false, 'error' => 'Post not found']);
        exit;
    }
    
    $updateData = [];
    
    if (isset($_POST['title'])) {
        $updateData['title'] = trim($_POST['title']);
    }
    if (isset($_POST['slug'])) {
        $updateData['slug'] = trim($_POST['slug']);
    }
    if (isset($_POST['status']) && in_array($_POST['status'], ['draft', 'published', 'scheduled'])) {
        $updateData['status'] = $_POST['status'];
    }
    if (isset($_POST['date']) && !empty($_POST['date'])) {
        // Update created_at date
        $table = Database::table('posts');
        Database::execute("UPDATE {$table} SET created_at = ? WHERE id = ?", [$_POST['date'], $postId]);
    }
    
    // Handle taxonomies
    if (!empty($postTaxonomies)) {
        foreach ($postTaxonomies as $taxSlug => $taxConfig) {
            $fieldName = 'tax_' . $taxSlug;
            if (isset($_POST[$fieldName])) {
                $termIds = array_map('intval', $_POST[$fieldName]);
                Taxonomy::setPostTerms($postId, $taxSlug, $termIds);
            } else {
                // Clear all terms for this taxonomy
                Taxonomy::setPostTerms($postId, $taxSlug, []);
            }
        }
    }
    
    if (!empty($updateData)) {
        Post::update($postId, $updateData);
    }
    
    // Return updated post data
    $updatedPost = Post::find($postId);
    echo json_encode([
        'success' => true,
        'post' => [
            'id' => $updatedPost['id'],
            'title' => $updatedPost['title'],
            'slug' => $updatedPost['slug'],
            'status' => $updatedPost['status'],
            'status_label' => Post::STATUS_LABELS[$updatedPost['status']] ?? $updatedPost['status'],
            'date' => formatDate($updatedPost['created_at']),
        ]
    ]);
    exit;
}

// Get Quick Edit data via AJAX
if (isset($_GET['get_quick_edit']) && isset($_GET['id'])) {
    header('Content-Type: application/json');
    
    $postId = (int)$_GET['id'];
    $post = Post::find($postId);
    
    if (!$post) {
        echo json_encode(['success' => false, 'error' => 'Post not found']);
        exit;
    }
    
    // Get taxonomy terms
    $terms = [];
    foreach ($postTaxonomies as $taxSlug => $taxConfig) {
        $terms[$taxSlug] = Taxonomy::getPostTermIds($postId, $taxSlug);
    }
    
    echo json_encode([
        'success' => true,
        'post' => [
            'id' => $post['id'],
            'title' => $post['title'],
            'slug' => $post['slug'],
            'status' => $post['status'],
            'date' => substr($post['created_at'], 0, 16), // Format for datetime-local input
        ],
        'terms' => $terms,
    ]);
    exit;
}

include ADMIN_PATH . '/includes/header.php';
?>

<style>
/* Bulk Actions Bar */
.bulk-actions-bar {
    display: none;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 1rem;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
    border: 1px solid rgba(139, 92, 246, 0.3);
    border-radius: 8px;
    margin-bottom: 1rem;
}
.bulk-actions-bar.visible {
    display: flex;
    flex-wrap: wrap;
}
.bulk-actions-bar .selected-count {
    font-weight: 600;
    color: var(--forge-primary);
    min-width: 100px;
}
.bulk-actions-bar select {
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    background: var(--bg-card);
    color: var(--text-color);
    font-size: 0.875rem;
    min-width: 180px;
}
.bulk-actions-bar .term-select {
    display: none;
    max-width: 200px;
}
.bulk-actions-bar .term-select.visible {
    display: block;
}

/* Checkbox column */
.col-checkbox {
    width: 40px !important;
    text-align: center;
}
.col-checkbox input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: var(--forge-primary);
}

/* Quick Edit Row */
.quick-edit-row {
    display: none;
}
.quick-edit-row.visible {
    display: table-row;
}
.quick-edit-row td {
    padding: 0 !important;
    background: var(--bg-card-header) !important;
}
.quick-edit-container {
    padding: 1.25rem;
    border-top: 2px solid var(--forge-primary);
    border-bottom: 2px solid var(--forge-primary);
    position: relative;
}
.quick-edit-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--border-color);
}
.quick-edit-header h4 {
    margin: 0;
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--forge-primary);
}
.quick-edit-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}
.quick-edit-field {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}
.quick-edit-field label {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.quick-edit-field input,
.quick-edit-field select {
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    background: var(--bg-card);
    color: var(--text-color);
    font-size: 0.875rem;
}
.quick-edit-field input:focus,
.quick-edit-field select:focus {
    outline: none;
    border-color: var(--forge-primary);
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
}
.quick-edit-taxonomies {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
}
.quick-edit-taxonomies h5 {
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--text-muted);
    margin: 0 0 0.75rem 0;
}
.quick-edit-tax-group {
    margin-bottom: 1rem;
}
.quick-edit-tax-group > label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.5rem;
}
.quick-edit-terms {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    max-height: 120px;
    overflow-y: auto;
    padding: 0.5rem;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 6px;
}
.quick-edit-terms label {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.25rem 0.5rem;
    background: var(--bg-page);
    border-radius: 4px;
    font-size: 0.8125rem;
    font-weight: normal;
    text-transform: none;
    letter-spacing: normal;
    cursor: pointer;
    transition: background 0.15s;
}
.quick-edit-terms label:hover {
    background: rgba(139, 92, 246, 0.1);
}
.quick-edit-terms input[type="checkbox"] {
    width: 14px;
    height: 14px;
    accent-color: var(--forge-primary);
}
.quick-edit-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
}
.quick-edit-actions .btn {
    min-width: 100px;
}

/* Row actions update */
.table-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.375rem;
}
.btn-quick-edit {
    background: linear-gradient(135deg, #6366f1, #8b5cf6) !important;
    color: #fff !important;
    border: none !important;
}
.btn-quick-edit:hover {
    filter: brightness(1.1);
}

/* Loading state */
.quick-edit-container.loading {
    opacity: 0.6;
    pointer-events: none;
}
.quick-edit-container.loading::after {
    content: 'Saving...';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: var(--forge-primary);
    color: #fff;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-weight: 600;
    z-index: 10;
}

/* Resizable columns */
#postsTable {
    table-layout: fixed;
    width: 100%;
}
#postsTable th {
    position: relative;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
#postsTable td {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
#postsTable th .resize-handle {
    position: absolute;
    right: 0;
    top: 0;
    bottom: 0;
    width: 8px;
    cursor: col-resize;
    background: transparent;
}
#postsTable th .resize-handle:hover {
    background: var(--forge-primary);
}

/* Selected row highlight */
tr.selected {
    background: rgba(139, 92, 246, 0.05) !important;
}
</style>

<div class="action-bar">
    <div class="action-bar-left">
        <a href="?type=<?= $postType ?>" class="btn btn-secondary btn-sm <?= !$status ? 'active' : '' ?>">
            All (<?= $totalAll ?>)
        </a>
        <a href="?type=<?= $postType ?>&status=published" class="btn btn-secondary btn-sm <?= $status === 'published' ? 'active' : '' ?>">
            Published (<?= $totalPublished ?>)
        </a>
        <a href="?type=<?= $postType ?>&status=draft" class="btn btn-secondary btn-sm <?= $status === 'draft' ? 'active' : '' ?>">
            Draft (<?= $totalDraft ?>)
        </a>
        <?php if ($totalScheduled > 0): ?>
            <a href="?type=<?= $postType ?>&status=scheduled" class="btn btn-secondary btn-sm <?= $status === 'scheduled' ? 'active' : '' ?>">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.25rem;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                Scheduled (<?= $totalScheduled ?>)
            </a>
        <?php endif; ?>
        <?php if ($totalTrash > 0): ?>
            <a href="?type=<?= $postType ?>&status=trash" class="btn btn-secondary btn-sm <?= $status === 'trash' ? 'active' : '' ?>">
                Trash (<?= $totalTrash ?>)
            </a>
        <?php endif; ?>
    </div>

    <div style="display: flex; gap: 0.5rem;">
        <?php if ($status === 'trash' && $totalTrash > 0): ?>
        <button type="button" class="btn btn-danger btn-sm" onclick="showConfirmModal('empty_trash', 0, '<?= $totalTrash ?> item(s)')">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
            </svg>
            Empty Trash
        </button>
        <?php endif; ?>
        <a href="<?= ADMIN_URL ?>/post-edit.php?type=<?= $postType ?>" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Add New <?= esc($typeConfig['singular']) ?>
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <form method="get" class="search-box">
            <input type="hidden" name="type" value="<?= esc($postType) ?>">
            <?php if ($status): ?>
                <input type="hidden" name="status" value="<?= esc($status) ?>">
            <?php endif; ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.35-4.35"></path>
            </svg>
            <input type="text" name="s" placeholder="Search..." value="<?= esc($search ?? '') ?>" class="form-input">
        </form>
        <a href="<?= ADMIN_URL ?>/column-settings.php?type=<?= esc($postType) ?>" class="btn btn-secondary btn-sm" title="Configure columns">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="7" height="7"></rect>
                <rect x="14" y="3" width="7" height="7"></rect>
                <rect x="14" y="14" width="7" height="7"></rect>
                <rect x="3" y="14" width="7" height="7"></rect>
            </svg>
            Columns
        </a>
    </div>

    <?php if (empty($posts)): ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
            </svg>
            <h3>No <?= strtolower(esc($typeConfig['label'])) ?> found</h3>
            <p>
                <?php if ($search): ?>
                    No results for "<?= esc($search) ?>". Try a different search.
                <?php elseif ($status === 'trash'): ?>
                    The trash is empty.
                <?php else: ?>
                    Create your first <?= strtolower(esc($typeConfig['singular'])) ?> to get started.
                <?php endif; ?>
            </p>
        </div>
    <?php else: ?>
        <?php
        // Get column configuration
        $columnConfigs = getOption('column_configs', []);
        $activeColumns = [];
        
        if (isset($columnConfigs[$postType]['columns'])) {
            foreach ($columnConfigs[$postType]['columns'] as $col) {
                if (!empty($col['enabled'])) {
                    $activeColumns[] = $col;
                }
            }
        }
        
        // Default columns if none configured
        if (empty($activeColumns)) {
            $activeColumns = [
                ['key' => 'title', 'width' => 'auto'],
                ['key' => 'author', 'width' => '120px'],
                ['key' => 'status', 'width' => '100px'],
                ['key' => 'date', 'width' => '140px'],
            ];
        }
        
        // Column labels and render functions
        $columnMeta = [
            'id' => 'ID',
            'title' => 'Title',
            'author' => 'Author',
            'status' => 'Status',
            'date' => 'Date',
            'modified' => 'Modified',
            'slug' => 'Slug',
            'featured_image' => 'Image',
            'word_count' => 'Words',
            'comments' => 'Comments',
        ];
        
        // Get custom fields
        $customFields = get_post_type_fields($postType);
        $customFieldsByKey = [];
        foreach ($customFields as $cf) {
            $customFieldsByKey[$cf['key']] = $cf;
        }
        
        // Get all taxonomy terms for bulk actions and quick edit
        $allTaxTerms = [];
        foreach ($postTaxonomies as $taxSlug => $taxConfig) {
            $allTaxTerms[$taxSlug] = Taxonomy::getTerms($taxSlug);
        }
        
        /**
         * Render a column value for a post
         */
        function renderColumnValue($post, $colKey, $status, $postTaxonomies, $customFieldsByKey) {
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
                
                // Format based on field type
                if ($fieldConfig) {
                    switch ($fieldConfig['type']) {
                        case 'checkbox':
                            return $value ? '✓' : '—';
                        case 'image':
                            $media = Media::find((int)$value);
                            if ($media) {
                                return '<img src="' . esc($media['url']) . '" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">';
                            }
                            return '<span style="color: var(--text-muted);">—</span>';
                        case 'color':
                            return '<span style="display: inline-block; width: 20px; height: 20px; border-radius: 4px; background: ' . esc($value) . '; border: 1px solid var(--border-color);"></span>';
                        case 'date':
                            return formatDate($value, 'M j, Y');
                        case 'url':
                            return '<a href="' . esc($value) . '" target="_blank" style="max-width: 150px; display: inline-block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">' . esc($value) . '</a>';
                        case 'textarea':
                        case 'wysiwyg':
                            $excerpt = mb_substr(strip_tags($value), 0, 50);
                            return esc($excerpt) . (mb_strlen($value) > 50 ? '...' : '');
                        default:
                            $display = is_array($value) ? implode(', ', $value) : $value;
                            return esc(mb_substr($display, 0, 50)) . (mb_strlen($display) > 50 ? '...' : '');
                    }
                }
                
                return esc(mb_substr((string)$value, 0, 50));
            }
            
            // Built-in columns
            switch ($colKey) {
                case 'id':
                    return $post['id'];
                    
                case 'title':
                    $title = $post['title'] ?: '(no title)';
                    if ($status !== 'trash') {
                        return '<a href="' . ADMIN_URL . '/post-edit.php?id=' . $post['id'] . '"><strong>' . esc($title) . '</strong></a>';
                    }
                    return '<strong>' . esc($title) . '</strong>';
                    
                case 'author':
                    $author = Post::getAuthor($post);
                    return esc($author['display_name'] ?? 'Unknown');
                    
                case 'status':
                    $html = '<span class="status-badge status-' . $post['status'] . '">' . 
                            (Post::STATUS_LABELS[$post['status']] ?? $post['status']) . '</span>';
                    if ($post['status'] === 'scheduled' && !empty($post['scheduled_at'])) {
                        $html .= '<div style="font-size: 0.6875rem; color: var(--text-muted); margin-top: 0.25rem;">' . 
                                 formatDate($post['scheduled_at'], 'M j, Y g:i a') . '</div>';
                    }
                    return $html;
                    
                case 'date':
                    if ($post['status'] === 'trash' && !empty($post['trashed_at'])) {
                        $daysLeft = Post::getDaysUntilDeletion($post);
                        $color = ($daysLeft !== null && $daysLeft <= 7) ? 'var(--forge-danger)' : 'var(--text-muted)';
                        $daysText = $daysLeft === null ? '' : ($daysLeft === 0 ? 'Deleting soon' : ($daysLeft === 1 ? '1 day left' : $daysLeft . ' days left'));
                        return '<div style="color: ' . $color . ';">' . $daysText . '</div>' .
                               '<div style="font-size: 0.6875rem; color: var(--text-muted);">Trashed ' . formatDate($post['trashed_at'], 'M j') . '</div>';
                    }
                    return formatDate($post['created_at']);
                    
                case 'modified':
                    return formatDate($post['updated_at']);
                    
                case 'slug':
                    return '<code style="font-size: 0.75rem; background: var(--bg-card-header); padding: 0.125rem 0.375rem; border-radius: 3px;">' . esc($post['slug']) . '</code>';
                    
                case 'featured_image':
                    if (!empty($post['featured_image_id'])) {
                        $media = Media::find($post['featured_image_id']);
                        if ($media) {
                            return '<img src="' . esc($media['url']) . '" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">';
                        }
                    }
                    return '<span style="color: var(--text-muted);">—</span>';
                    
                case 'word_count':
                    $count = str_word_count(strip_tags($post['content'] ?? ''));
                    return number_format($count);
                    
                case 'comments':
                    $commentCount = (int) ($post['comment_count'] ?? 0);
                    if ($commentCount > 0) {
                        return '<a href="' . ADMIN_URL . '/comments.php?post_id=' . $post['id'] . '" style="color: var(--forge-primary); text-decoration: none; font-weight: 500;">' . number_format($commentCount) . '</a>';
                    }
                    return '<span style="color: var(--text-muted);">0</span>';
                    
                default:
                    return '<span style="color: var(--text-muted);">—</span>';
            }
        }
        ?>
        
        <!-- Bulk Actions Bar -->
        <form method="post" id="bulkActionsForm">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="bulk">
            
            <div class="bulk-actions-bar" id="bulkActionsBar">
                <span class="selected-count"><span id="selectedCount">0</span> selected</span>
                
                <select name="bulk_action" id="bulkActionSelect">
                    <option value="">— Select Action —</option>
                    <?php if ($status === 'trash'): ?>
                        <option value="restore">Restore</option>
                        <option value="delete">Delete Permanently</option>
                    <?php else: ?>
                        <option value="publish">Publish</option>
                        <option value="draft">Set to Draft</option>
                        <option value="trash">Move to Trash</option>
                        <?php foreach ($postTaxonomies as $taxSlug => $taxConfig): ?>
                            <option value="add_tax_<?= esc($taxSlug) ?>">Add <?= esc($taxConfig['singular']) ?></option>
                            <option value="remove_tax_<?= esc($taxSlug) ?>">Remove <?= esc($taxConfig['singular']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                
                <!-- Term selectors for taxonomy actions -->
                <?php foreach ($postTaxonomies as $taxSlug => $taxConfig): ?>
                    <select name="bulk_term_ids[]" class="term-select" data-taxonomy="<?= esc($taxSlug) ?>" multiple>
                        <?php foreach ($allTaxTerms[$taxSlug] as $term): ?>
                            <option value="<?= $term['id'] ?>"><?= esc($term['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endforeach; ?>
                
                <button type="submit" class="btn btn-primary btn-sm" id="applyBulkAction">
                    Apply
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="deselectAll()">
                    Deselect All
                </button>
            </div>
        
            <div class="table-wrapper">
                <table class="table" id="postsTable">
                    <colgroup id="tableColgroup">
                        <col style="width: 40px">
                        <?php foreach ($activeColumns as $colIndex => $col): ?>
                        <col data-col-key="<?= esc($col['key']) ?>" style="<?= !empty($col['width']) && $col['width'] !== 'auto' ? 'width: ' . esc($col['width']) : '' ?>">
                        <?php endforeach; ?>
                        <col style="width: 180px">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="col-checkbox">
                                <input type="checkbox" id="selectAll" title="Select all">
                            </th>
                            <?php foreach ($activeColumns as $colIndex => $col): ?>
                            <?php
                            // Use custom label if set, otherwise default
                            $label = '';
                            if (!empty($col['label'])) {
                                $label = $col['label'];
                            } else {
                                $label = $columnMeta[$col['key']] ?? '';
                                if (strpos($col['key'], 'tax_') === 0) {
                                    $taxSlug = substr($col['key'], 4);
                                    $label = $postTaxonomies[$taxSlug]['label'] ?? ucfirst($taxSlug);
                                } elseif (strpos($col['key'], 'cf_') === 0) {
                                    $fieldKey = substr($col['key'], 3);
                                    $label = $customFieldsByKey[$fieldKey]['label'] ?? ucfirst($fieldKey);
                                }
                            }
                            ?>
                            <th data-col-index="<?= $colIndex ?>" data-col-key="<?= esc($col['key']) ?>">
                                <?= esc($label) ?>
                                <span class="resize-handle"></span>
                            </th>
                            <?php endforeach; ?>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $post): ?>
                            <tr data-post-id="<?= $post['id'] ?>">
                                <td class="col-checkbox">
                                    <input type="checkbox" name="post_ids[]" value="<?= $post['id'] ?>" class="post-checkbox">
                                </td>
                                <?php foreach ($activeColumns as $col): ?>
                                <td data-col="<?= esc($col['key']) ?>"><?= renderColumnValue($post, $col['key'], $status, $postTaxonomies, $customFieldsByKey) ?></td>
                                <?php endforeach; ?>
                                <td>
                                    <div class="table-actions">
                                        <?php if ($status === 'trash'): ?>
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="submitPostAction('restore', <?= $post['id'] ?>)">Restore</button>
                                            <button type="button" class="btn btn-danger btn-sm" onclick="showConfirmModal('delete', <?= $post['id'] ?>, '<?= esc(addslashes($post['title'] ?: 'this item')) ?>')">Delete</button>
                                        <?php else: ?>
                                            <a href="<?= ADMIN_URL ?>/post-edit.php?id=<?= $post['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                            <button type="button" class="btn btn-sm btn-quick-edit" onclick="openQuickEdit(<?= $post['id'] ?>)">Quick Edit</button>
                                            <?php if ($post['status'] === 'published'): ?>
                                                <a href="<?= Post::permalink($post) ?>" target="_blank" class="btn btn-secondary btn-sm">View</a>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-secondary btn-sm" title="Duplicate" onclick="submitPostAction('duplicate', <?= $post['id'] ?>)">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                                </svg>
                                            </button>
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="showConfirmModal('trash', <?= $post['id'] ?>, '<?= esc(addslashes($post['title'] ?: 'this item')) ?>')">Trash</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <!-- Quick Edit Row (hidden by default) -->
                            <tr class="quick-edit-row" id="quickEditRow_<?= $post['id'] ?>">
                                <td colspan="<?= count($activeColumns) + 2 ?>">
                                    <div class="quick-edit-container" id="quickEditContainer_<?= $post['id'] ?>">
                                        <div class="quick-edit-header">
                                            <h4>
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem; vertical-align: -2px;">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                </svg>
                                                Quick Edit
                                            </h4>
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="closeQuickEdit(<?= $post['id'] ?>)">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                                </svg>
                                            </button>
                                        </div>
                                        
                                        <div class="quick-edit-grid">
                                            <div class="quick-edit-field">
                                                <label for="qe_title_<?= $post['id'] ?>">Title</label>
                                                <input type="text" id="qe_title_<?= $post['id'] ?>" name="title" value="">
                                            </div>
                                            <div class="quick-edit-field">
                                                <label for="qe_slug_<?= $post['id'] ?>">Slug</label>
                                                <input type="text" id="qe_slug_<?= $post['id'] ?>" name="slug" value="">
                                            </div>
                                            <div class="quick-edit-field">
                                                <label for="qe_status_<?= $post['id'] ?>">Status</label>
                                                <select id="qe_status_<?= $post['id'] ?>" name="status">
                                                    <option value="draft">Draft</option>
                                                    <option value="published">Published</option>
                                                    <option value="scheduled">Scheduled</option>
                                                </select>
                                            </div>
                                            <div class="quick-edit-field">
                                                <label for="qe_date_<?= $post['id'] ?>">Date</label>
                                                <input type="datetime-local" id="qe_date_<?= $post['id'] ?>" name="date" value="">
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($postTaxonomies)): ?>
                                        <div class="quick-edit-taxonomies">
                                            <h5>Taxonomies</h5>
                                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                                                <?php foreach ($postTaxonomies as $taxSlug => $taxConfig): ?>
                                                <div class="quick-edit-tax-group">
                                                    <label><?= esc($taxConfig['label']) ?></label>
                                                    <div class="quick-edit-terms" id="qe_terms_<?= esc($taxSlug) ?>_<?= $post['id'] ?>">
                                                        <?php foreach ($allTaxTerms[$taxSlug] as $term): ?>
                                                        <label>
                                                            <input type="checkbox" name="tax_<?= esc($taxSlug) ?>[]" value="<?= $term['id'] ?>">
                                                            <?= esc($term['name']) ?>
                                                        </label>
                                                        <?php endforeach; ?>
                                                        <?php if (empty($allTaxTerms[$taxSlug])): ?>
                                                        <span style="color: var(--text-muted); font-size: 0.8125rem;">No terms yet</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="quick-edit-actions">
                                            <button type="button" class="btn btn-secondary" onclick="closeQuickEdit(<?= $post['id'] ?>)">Cancel</button>
                                            <button type="button" class="btn btn-primary" onclick="saveQuickEdit(<?= $post['id'] ?>)">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.25rem;">
                                                    <polyline points="20 6 9 17 4 12"></polyline>
                                                </svg>
                                                Update
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>

        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="pagination">
                <?php if ($pagination['has_previous']): ?>
                    <a href="?type=<?= $postType ?>&paged=<?= $pagination['current_page'] - 1 ?><?= $status ? '&status=' . $status : '' ?>">&laquo;</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                    <?php if ($i === $pagination['current_page']): ?>
                        <span class="active"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?type=<?= $postType ?>&paged=<?= $i ?><?= $status ? '&status=' . $status : '' ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($pagination['has_next']): ?>
                    <a href="?type=<?= $postType ?>&paged=<?= $pagination['current_page'] + 1 ?><?= $status ? '&status=' . $status : '' ?>">&raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
// Global variables for confirmation modal
var confirmAction = '';
var confirmPostId = 0;
var csrfToken = '<?= csrfToken() ?>';

(function() {
    // =========================================================================
    // Column Resize (existing functionality)
    // =========================================================================
    var table = document.getElementById('postsTable');
    var colgroup = document.getElementById('tableColgroup');
    if (!table || !colgroup) return;
    
    var postType = '<?= esc($postType) ?>';
    var cols = colgroup.querySelectorAll('col');
    var headers = table.querySelectorAll('thead th');
    var saved = {};
    
    try { saved = JSON.parse(localStorage.getItem('vf_col_' + postType) || '{}'); } catch(e) {}
    
    // Apply saved widths to col elements and set defaults
    cols.forEach(function(col, i) {
        var key = col.dataset.colKey;
        if (key && saved[key]) {
            col.style.width = saved[key];
        } else if (!col.style.width) {
            col.style.width = (headers[i] ? headers[i].offsetWidth : 150) + 'px';
        }
    });
    
    var dragging = false, startX, startW, currentCol, currentKey;
    
    document.addEventListener('mousedown', function(e) {
        if (!e.target.classList.contains('resize-handle')) return;
        var th = e.target.parentElement;
        if (!th) return;
        
        var key = th.dataset.colKey;
        currentCol = colgroup.querySelector('col[data-col-key="' + key + '"]');
        if (!currentCol) return;
        
        e.preventDefault();
        dragging = true;
        currentKey = key;
        startX = e.pageX;
        startW = currentCol.offsetWidth || parseInt(currentCol.style.width) || th.offsetWidth;
    });
    
    document.addEventListener('mousemove', function(e) {
        if (!dragging || !currentCol) return;
        var newW = Math.max(50, startW + e.pageX - startX);
        currentCol.style.width = newW + 'px';
    });
    
    document.addEventListener('mouseup', function() {
        if (!dragging) return;
        dragging = false;
        if (currentKey && currentCol) {
            saved[currentKey] = currentCol.style.width;
            localStorage.setItem('vf_col_' + postType, JSON.stringify(saved));
        }
        currentCol = null;
        currentKey = null;
    });
    
    // =========================================================================
    // Bulk Actions
    // =========================================================================
    var selectAll = document.getElementById('selectAll');
    var checkboxes = document.querySelectorAll('.post-checkbox');
    var bulkBar = document.getElementById('bulkActionsBar');
    var selectedCount = document.getElementById('selectedCount');
    var bulkActionSelect = document.getElementById('bulkActionSelect');
    var termSelects = document.querySelectorAll('.term-select');
    
    function updateBulkBar() {
        var checked = document.querySelectorAll('.post-checkbox:checked');
        var count = checked.length;
        selectedCount.textContent = count;
        
        if (count > 0) {
            bulkBar.classList.add('visible');
        } else {
            bulkBar.classList.remove('visible');
        }
        
        // Update select all state
        selectAll.checked = count === checkboxes.length && count > 0;
        selectAll.indeterminate = count > 0 && count < checkboxes.length;
        
        // Highlight selected rows
        document.querySelectorAll('tr[data-post-id]').forEach(function(row) {
            var cb = row.querySelector('.post-checkbox');
            if (cb && cb.checked) {
                row.classList.add('selected');
            } else {
                row.classList.remove('selected');
            }
        });
    }
    
    selectAll.addEventListener('change', function() {
        checkboxes.forEach(function(cb) {
            cb.checked = selectAll.checked;
        });
        updateBulkBar();
    });
    
    checkboxes.forEach(function(cb) {
        cb.addEventListener('change', updateBulkBar);
    });
    
    // Show/hide term selectors based on bulk action
    bulkActionSelect.addEventListener('change', function() {
        var val = this.value;
        termSelects.forEach(function(sel) {
            sel.classList.remove('visible');
        });
        
        var match = val.match(/^(add|remove)_tax_(.+)$/);
        if (match) {
            var taxSlug = match[2];
            var termSel = document.querySelector('.term-select[data-taxonomy="' + taxSlug + '"]');
            if (termSel) {
                termSel.classList.add('visible');
            }
        }
    });
    
    // Deselect all helper
    window.deselectAll = function() {
        checkboxes.forEach(function(cb) {
            cb.checked = false;
        });
        selectAll.checked = false;
        selectAll.indeterminate = false;
        updateBulkBar();
    };
    
    // Validate bulk action before submit
    var bulkSubmitConfirmed = false;
    document.getElementById('bulkActionsForm').addEventListener('submit', function(e) {
        // If already confirmed via modal, allow submit
        if (bulkSubmitConfirmed) {
            bulkSubmitConfirmed = false;
            return;
        }
        
        var action = bulkActionSelect.value;
        var checked = document.querySelectorAll('.post-checkbox:checked');
        
        if (!action) {
            e.preventDefault();
            showNotification('Please select an action.', 'warning');
            return;
        }
        
        if (checked.length === 0) {
            e.preventDefault();
            showNotification('Please select at least one item.', 'warning');
            return;
        }
        
        // Confirm destructive actions via modal
        if (action === 'trash' || action === 'delete') {
            e.preventDefault();
            showBulkConfirmModal(action, checked.length);
        }
    });
    
    // Expose function to set confirmed flag
    window.setBulkConfirmed = function() {
        bulkSubmitConfirmed = true;
    };
})();

// Notification helper
function showNotification(message, type) {
    var existing = document.querySelector('.toast-notification');
    if (existing) existing.remove();
    
    var toast = document.createElement('div');
    toast.className = 'toast-notification toast-' + (type || 'info');
    toast.innerHTML = '<span>' + message + '</span><button onclick="this.parentNode.remove()">&times;</button>';
    document.body.appendChild(toast);
    
    setTimeout(function() { toast.classList.add('show'); }, 10);
    setTimeout(function() { toast.remove(); }, 4000);
}

// Bulk confirm modal
var bulkConfirmAction = '';
var bulkConfirmCount = 0;

function showBulkConfirmModal(action, count) {
    bulkConfirmAction = action;
    bulkConfirmCount = count;
    
    var modal = document.getElementById('confirmModal');
    var title = document.getElementById('confirmTitle');
    var message = document.getElementById('confirmMessage');
    var confirmBtn = document.getElementById('confirmBtn');
    
    if (action === 'trash') {
        title.textContent = 'Move to Trash';
        message.innerHTML = 'Move <strong>' + count + ' item(s)</strong> to trash?';
        confirmBtn.textContent = 'Move to Trash';
        confirmBtn.className = 'btn btn-secondary';
    } else if (action === 'delete') {
        title.textContent = 'Permanently Delete';
        message.innerHTML = 'Permanently delete <strong>' + count + ' item(s)</strong>? This cannot be undone.';
        confirmBtn.textContent = 'Delete Permanently';
        confirmBtn.className = 'btn btn-danger';
    }
    
    // Switch to bulk mode
    confirmAction = 'bulk_' + action;
    confirmPostId = 0;
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

// =========================================================================
// Quick Edit
// =========================================================================
var currentQuickEditId = null;

function openQuickEdit(postId) {
    // Close any open quick edit first
    if (currentQuickEditId && currentQuickEditId !== postId) {
        closeQuickEdit(currentQuickEditId);
    }
    
    var row = document.getElementById('quickEditRow_' + postId);
    var container = document.getElementById('quickEditContainer_' + postId);
    
    if (!row || !container) return;
    
    // Show loading state
    row.classList.add('visible');
    container.classList.add('loading');
    currentQuickEditId = postId;
    
    // Fetch current post data
    fetch('?type=<?= esc($postType) ?>&get_quick_edit=1&id=' + postId)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            container.classList.remove('loading');
            
            if (!data.success) {
                showNotification(data.error || 'Failed to load post data', 'error');
                closeQuickEdit(postId);
                return;
            }
            
            // Populate fields
            document.getElementById('qe_title_' + postId).value = data.post.title || '';
            document.getElementById('qe_slug_' + postId).value = data.post.slug || '';
            document.getElementById('qe_status_' + postId).value = data.post.status || 'draft';
            document.getElementById('qe_date_' + postId).value = data.post.date || '';
            
            // Populate taxonomy checkboxes
            if (data.terms) {
                for (var taxSlug in data.terms) {
                    var termIds = data.terms[taxSlug];
                    var termsContainer = document.getElementById('qe_terms_' + taxSlug + '_' + postId);
                    if (termsContainer) {
                        var checkboxes = termsContainer.querySelectorAll('input[type="checkbox"]');
                        checkboxes.forEach(function(cb) {
                            cb.checked = termIds.indexOf(parseInt(cb.value)) !== -1;
                        });
                    }
                }
            }
        })
        .catch(function(err) {
            container.classList.remove('loading');
            showNotification('Error loading post data', 'error');
            closeQuickEdit(postId);
        });
}

function closeQuickEdit(postId) {
    var row = document.getElementById('quickEditRow_' + postId);
    if (row) {
        row.classList.remove('visible');
    }
    if (currentQuickEditId === postId) {
        currentQuickEditId = null;
    }
}

function saveQuickEdit(postId) {
    var container = document.getElementById('quickEditContainer_' + postId);
    if (!container) return;
    
    container.classList.add('loading');
    
    // Gather form data
    var formData = new FormData();
    formData.append('csrf_token', csrfToken);
    formData.append('quick_edit', '1');
    formData.append('post_id', postId);
    formData.append('title', document.getElementById('qe_title_' + postId).value);
    formData.append('slug', document.getElementById('qe_slug_' + postId).value);
    formData.append('status', document.getElementById('qe_status_' + postId).value);
    formData.append('date', document.getElementById('qe_date_' + postId).value);
    
    // Gather taxonomy terms
    var taxonomySlugs = <?= json_encode(array_keys($postTaxonomies)) ?>;
    taxonomySlugs.forEach(function(taxSlug) {
        var termsContainer = document.getElementById('qe_terms_' + taxSlug + '_' + postId);
        if (termsContainer) {
            var checkboxes = termsContainer.querySelectorAll('input[type="checkbox"]:checked');
            checkboxes.forEach(function(cb) {
                formData.append('tax_' + taxSlug + '[]', cb.value);
            });
        }
    });
    
    fetch('?type=<?= esc($postType) ?>', {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        container.classList.remove('loading');
        
        if (!data.success) {
            showNotification(data.error || 'Failed to save', 'error');
            return;
        }
        
        // Update the table row with new data
        var row = document.querySelector('tr[data-post-id="' + postId + '"]');
        if (row && data.post) {
            // Update title cell
            var titleCell = row.querySelector('td[data-col="title"]');
            if (titleCell) {
                titleCell.innerHTML = '<a href="<?= ADMIN_URL ?>/post-edit.php?id=' + postId + '"><strong>' + escapeHtml(data.post.title || '(no title)') + '</strong></a>';
            }
            
            // Update slug cell
            var slugCell = row.querySelector('td[data-col="slug"]');
            if (slugCell) {
                slugCell.innerHTML = '<code style="font-size: 0.75rem; background: var(--bg-card-header); padding: 0.125rem 0.375rem; border-radius: 3px;">' + escapeHtml(data.post.slug) + '</code>';
            }
            
            // Update status cell
            var statusCell = row.querySelector('td[data-col="status"]');
            if (statusCell) {
                statusCell.innerHTML = '<span class="status-badge status-' + data.post.status + '">' + escapeHtml(data.post.status_label) + '</span>';
            }
            
            // Update date cell
            var dateCell = row.querySelector('td[data-col="date"]');
            if (dateCell) {
                dateCell.textContent = data.post.date;
            }
        }
        
        closeQuickEdit(postId);
    })
    .catch(function(err) {
        container.classList.remove('loading');
        showNotification('Error saving: ' + err.message, 'error');
    });
}

function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Keyboard shortcut: Escape to close quick edit
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && currentQuickEditId) {
        closeQuickEdit(currentQuickEditId);
    }
});

// =========================================================================
// Confirmation Modal
// =========================================================================

// Submit single post action without confirmation (for restore, duplicate)
function submitPostAction(action, postId) {
    var form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';
    
    var csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = 'csrf_token';
    csrf.value = csrfToken;
    form.appendChild(csrf);
    
    var actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = action;
    form.appendChild(actionInput);
    
    var postIdInput = document.createElement('input');
    postIdInput.type = 'hidden';
    postIdInput.name = 'post_id';
    postIdInput.value = postId;
    form.appendChild(postIdInput);
    
    document.body.appendChild(form);
    form.submit();
}

function showConfirmModal(action, postId, itemName) {
    confirmAction = action;
    confirmPostId = postId;
    
    var modal = document.getElementById('confirmModal');
    var title = document.getElementById('confirmTitle');
    var message = document.getElementById('confirmMessage');
    var confirmBtn = document.getElementById('confirmBtn');
    
    if (action === 'trash') {
        title.textContent = 'Move to Trash';
        message.innerHTML = 'Are you sure you want to move <strong>' + escapeHtml(itemName) + '</strong> to trash?';
        confirmBtn.textContent = 'Move to Trash';
        confirmBtn.className = 'btn btn-secondary';
    } else if (action === 'delete') {
        title.textContent = 'Permanently Delete';
        message.innerHTML = 'Are you sure you want to permanently delete <strong>' + escapeHtml(itemName) + '</strong>? This cannot be undone.';
        confirmBtn.textContent = 'Delete Permanently';
        confirmBtn.className = 'btn btn-danger';
    } else if (action === 'empty_trash') {
        title.textContent = 'Empty Trash';
        message.innerHTML = 'Are you sure you want to permanently delete <strong>' + escapeHtml(itemName) + '</strong> from trash? This cannot be undone.';
        confirmBtn.textContent = 'Empty Trash';
        confirmBtn.className = 'btn btn-danger';
    }
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function hideConfirmModal() {
    document.getElementById('confirmModal').classList.remove('active');
    document.body.style.overflow = '';
    confirmAction = '';
    confirmPostId = 0;
}

function executeConfirmAction() {
    // Check if this is a bulk action
    if (confirmAction.startsWith('bulk_')) {
        // Set flag to bypass confirmation on submit
        if (typeof setBulkConfirmed === 'function') {
            setBulkConfirmed();
        }
        // Submit the existing bulk form
        var bulkForm = document.getElementById('bulkActionsForm');
        if (bulkForm) {
            hideConfirmModal();
            bulkForm.submit();
        }
        return;
    }
    
    // Create and submit form for single actions
    var form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';
    
    // Add CSRF token
    var csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = 'csrf_token';
    csrf.value = csrfToken;
    form.appendChild(csrf);
    
    // Add action
    var actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = confirmAction;
    form.appendChild(actionInput);
    
    // Add post_id for single item actions
    if (confirmPostId > 0) {
        var postIdInput = document.createElement('input');
        postIdInput.type = 'hidden';
        postIdInput.name = 'post_id';
        postIdInput.value = confirmPostId;
        form.appendChild(postIdInput);
    }
    
    document.body.appendChild(form);
    form.submit();
}

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        hideConfirmModal();
    }
});
</script>

<!-- Confirmation Modal -->
<div id="confirmModal" class="modal-backdrop" onclick="if(event.target === this) hideConfirmModal()">
    <div class="modal-dialog modal-sm">
        <div class="modal-header">
            <h3 id="confirmTitle">Confirm Action</h3>
            <button type="button" class="modal-close" onclick="hideConfirmModal()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <p id="confirmMessage">Are you sure?</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="hideConfirmModal()">Cancel</button>
            <button type="button" id="confirmBtn" class="btn btn-danger" onclick="executeConfirmAction()">Confirm</button>
        </div>
    </div>
</div>

<style>
/* Confirmation Modal Styles */
.modal-backdrop {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
.modal-backdrop.active {
    display: flex;
}
.modal-dialog {
    background: var(--bg-card);
    border-radius: 12px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    width: 100%;
    max-width: 400px;
    animation: modalSlideIn 0.2s ease-out;
}
.modal-sm { max-width: 400px; }
@keyframes modalSlideIn {
    from { opacity: 0; transform: scale(0.95) translateY(-10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border-color);
}
.modal-header h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
}
.modal-close {
    background: none;
    border: none;
    padding: 0.25rem;
    cursor: pointer;
    color: var(--text-muted);
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.modal-close:hover {
    color: var(--text-primary);
    background: var(--bg-hover);
}
.modal-body {
    padding: 1.25rem;
}
.modal-body p {
    margin: 0;
    color: var(--text-secondary);
    line-height: 1.5;
}
.modal-body strong {
    color: var(--text-primary);
}
.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    border-top: 1px solid var(--border-color);
    background: var(--bg-body);
    border-radius: 0 0 12px 12px;
}

/* Toast Notifications */
.toast-notification {
    position: fixed;
    bottom: 1.5rem;
    right: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 1rem;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    font-size: 0.875rem;
    z-index: 10000;
    transform: translateY(100px);
    opacity: 0;
    transition: all 0.3s ease;
}
.toast-notification.show {
    transform: translateY(0);
    opacity: 1;
}
.toast-notification button {
    background: none;
    border: none;
    font-size: 1.25rem;
    color: var(--text-muted);
    cursor: pointer;
    padding: 0;
    line-height: 1;
}
.toast-notification button:hover { color: var(--text-primary); }
.toast-warning {
    border-left: 3px solid var(--forge-warning);
}
.toast-error {
    border-left: 3px solid var(--forge-danger);
}
.toast-success {
    border-left: 3px solid var(--forge-success);
}
</style>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
