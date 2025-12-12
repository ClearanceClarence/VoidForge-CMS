<?php
/**
 * Posts Listing (Posts, Pages, Custom Post Types)
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

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $action = $_POST['action'] ?? '';
    $postId = (int)($_POST['post_id'] ?? 0);

    if ($postId && $action) {
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
    
    // Handle empty trash action (no post_id required)
    if (($action ?? '') === 'empty_trash') {
        $deleted = Post::emptyTrash($postType);
        setFlash('success', $deleted . ' item(s) permanently deleted from trash.');
        redirect(ADMIN_URL . '/posts.php?type=' . $postType);
    }
}

include ADMIN_PATH . '/includes/header.php';
?>

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
        <form method="post" style="display: inline;">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="empty_trash">
            <button type="submit" class="btn btn-danger btn-sm" data-confirm="Permanently delete all <?= $totalTrash ?> item(s) in trash? This cannot be undone.">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
                Empty Trash
            </button>
        </form>
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
        
        // Get taxonomies and custom fields for labels
        $postTaxonomies = Taxonomy::getForPostType($postType);
        $customFields = get_post_type_fields($postType);
        $customFieldsByKey = [];
        foreach ($customFields as $cf) {
            $customFieldsByKey[$cf['key']] = $cf;
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
                    // Comments not implemented yet
                    return '<span style="color: var(--text-muted);">0</span>';
                    
                default:
                    return '<span style="color: var(--text-muted);">—</span>';
            }
        }
        ?>
        
        <style>
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
        </style>
        
        <div class="table-wrapper">
            <table class="table" id="postsTable">
                <colgroup id="tableColgroup">
                    <?php foreach ($activeColumns as $colIndex => $col): ?>
                    <col data-col-key="<?= esc($col['key']) ?>" style="<?= !empty($col['width']) && $col['width'] !== 'auto' ? 'width: ' . esc($col['width']) : '' ?>">
                    <?php endforeach; ?>
                    <col style="width: 140px">
                </colgroup>
                <thead>
                    <tr>
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
                        <tr>
                            <?php foreach ($activeColumns as $col): ?>
                            <td><?= renderColumnValue($post, $col['key'], $status, $postTaxonomies, $customFieldsByKey) ?></td>
                            <?php endforeach; ?>
                            <td>
                                <div class="table-actions">
                                    <?php if ($status === 'trash'): ?>
                                        <form method="post" style="display: inline;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                            <input type="hidden" name="action" value="restore">
                                            <button type="submit" class="btn btn-secondary btn-sm">Restore</button>
                                        </form>
                                        <form method="post" style="display: inline;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn btn-danger btn-sm" data-confirm="Permanently delete this item?">Delete</button>
                                        </form>
                                    <?php else: ?>
                                        <a href="<?= ADMIN_URL ?>/post-edit.php?id=<?= $post['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                        <?php if ($post['status'] === 'published'): ?>
                                            <a href="<?= Post::permalink($post) ?>" target="_blank" class="btn btn-secondary btn-sm">View</a>
                                        <?php endif; ?>
                                        <form method="post" style="display: inline;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                            <input type="hidden" name="action" value="duplicate">
                                            <button type="submit" class="btn btn-secondary btn-sm" title="Duplicate">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                                </svg>
                                            </button>
                                        </form>
                                        <form method="post" style="display: inline;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                            <input type="hidden" name="action" value="trash">
                                            <button type="submit" class="btn btn-secondary btn-sm" data-confirm="Move this item to trash?">Trash</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

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
(function() {
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
            // Set a default width based on rendered size
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
})();
</script>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
