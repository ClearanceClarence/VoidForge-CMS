<?php
/**
 * Comments Management - VoidForge CMS
 */

define('CMS_ROOT', dirname(__DIR__));
require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/user.php';
require_once CMS_ROOT . '/includes/post.php';
require_once CMS_ROOT . '/includes/media.php';
require_once CMS_ROOT . '/includes/plugin.php';
require_once CMS_ROOT . '/includes/comment.php';

Post::init();

User::startSession();
User::requireRole('editor');

$currentPage = 'comments';
$pageTitle = 'Comments';

// Get filter parameters
$status = $_GET['status'] ?? '';
$postId = (int) ($_GET['post_id'] ?? 0);
$search = trim($_GET['s'] ?? '');
$page = max(1, (int) ($_GET['paged'] ?? 1));
$perPage = 20;

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $action = $_POST['action'] ?? '';
    $commentId = (int) ($_POST['comment_id'] ?? 0);
    $bulkIds = $_POST['comment_ids'] ?? [];
    $bulkAction = $_POST['bulk_action'] ?? '';

    // Single comment actions
    if ($action && $commentId) {
        $success = false;
        $message = '';

        switch ($action) {
            case 'approve':
                $success = Comment::approve($commentId);
                $message = $success ? 'Comment approved.' : 'Failed to approve comment.';
                break;
            case 'spam':
                $success = Comment::markSpam($commentId);
                $message = $success ? 'Comment marked as spam.' : 'Failed to mark comment as spam.';
                break;
            case 'trash':
                $success = Comment::trash($commentId);
                $message = $success ? 'Comment moved to trash.' : 'Failed to trash comment.';
                break;
            case 'restore':
                $success = Comment::restore($commentId);
                $message = $success ? 'Comment restored.' : 'Failed to restore comment.';
                break;
            case 'delete':
                $success = Comment::delete($commentId);
                $message = $success ? 'Comment permanently deleted.' : 'Failed to delete comment.';
                break;
        }

        setFlash($success ? 'success' : 'error', $message);
        redirect(ADMIN_URL . '/comments.php' . ($status ? '?status=' . $status : ''));
    }

    // Bulk actions
    if ($bulkAction && !empty($bulkIds)) {
        $affected = Comment::bulkAction($bulkIds, $bulkAction);
        $actionLabels = [
            'approve' => 'approved',
            'spam' => 'marked as spam',
            'trash' => 'moved to trash',
            'restore' => 'restored',
            'delete' => 'permanently deleted',
        ];
        $label = $actionLabels[$bulkAction] ?? $bulkAction;
        setFlash('success', "{$affected} comment(s) {$label}.");
        redirect(ADMIN_URL . '/comments.php' . ($status ? '?status=' . $status : ''));
    }
}

// Handle inline edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_comment']) && verifyCsrf()) {
    $commentId = (int) $_POST['comment_id'];
    $content = $_POST['content'] ?? '';
    
    if ($commentId && $content) {
        Comment::update($commentId, ['content' => $content]);
        setFlash('success', 'Comment updated.');
    }
    redirect(ADMIN_URL . '/comments.php' . ($status ? '?status=' . $status : ''));
}

// Handle orphan cleanup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cleanup_orphans']) && verifyCsrf()) {
    try {
        $commentsTable = Database::table('comments');
        $postsTable = Database::table('posts');
        
        // Get orphaned post IDs
        $orphaned = Database::query(
            "SELECT DISTINCT c.post_id FROM {$commentsTable} c 
             WHERE NOT EXISTS (SELECT 1 FROM {$postsTable} p WHERE p.id = c.post_id)"
        );
        
        $deleted = 0;
        foreach ($orphaned as $row) {
            $deleted += Database::delete($commentsTable, 'post_id = ?', [(int)$row['post_id']]);
        }
        
        if ($deleted > 0) {
            setFlash('success', "{$deleted} orphaned comment(s) permanently deleted.");
        } else {
            setFlash('info', 'No orphaned comments found.');
        }
    } catch (Exception $e) {
        setFlash('error', 'Error cleaning up orphans: ' . $e->getMessage());
    }
    redirect(ADMIN_URL . '/comments.php');
}

// Build query args
$queryArgs = [
    'limit' => $perPage,
    'offset' => ($page - 1) * $perPage,
];

if ($status) {
    $queryArgs['status'] = $status;
}
if ($postId) {
    $queryArgs['post_id'] = $postId;
}
if ($search) {
    $queryArgs['search'] = $search;
}

// Get comments
$comments = Comment::query($queryArgs);

// Get counts for tabs
$counts = [
    'all' => Comment::count([]),
    'pending' => Comment::count(['status' => Comment::STATUS_PENDING]),
    'approved' => Comment::count(['status' => Comment::STATUS_APPROVED]),
    'spam' => Comment::count(['status' => Comment::STATUS_SPAM]),
    'trash' => Comment::count(['status' => Comment::STATUS_TRASH]),
];

// Total for pagination
$countArgs = [];
if ($status) $countArgs['status'] = $status;
if ($postId) $countArgs['post_id'] = $postId;
$totalComments = Comment::count($countArgs);
$totalPages = ceil($totalComments / $perPage);

// Cache posts for display
$postCache = [];
foreach ($comments as $comment) {
    if (!isset($postCache[$comment['post_id']])) {
        $postCache[$comment['post_id']] = Post::find($comment['post_id']);
    }
}

// Count orphaned comments
$orphanedCount = 0;

// Count orphaned comments directly with SQL
try {
    $commentsTable = Database::table('comments');
    $postsTable = Database::table('posts');
    
    $orphanResult = Database::query(
        "SELECT COUNT(*) as cnt FROM {$commentsTable} c 
         WHERE NOT EXISTS (SELECT 1 FROM {$postsTable} p WHERE p.id = c.post_id)"
    );
    
    $orphanedCount = (int) ($orphanResult[0]['cnt'] ?? 0);
} catch (Exception $e) {
    $orphanedCount = 0;
}

include ADMIN_PATH . '/includes/header.php';
?>

<style>
/* Comments Page - Matching VoidForge Design */
.comments-page { padding: 0; }

/* Header - same as media */
.comments-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}
.comments-header h1 { font-size: 1.75rem; font-weight: 700; margin: 0; }

/* Cleanup Orphans Button */
.btn-cleanup-orphans {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1rem;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.25);
}

.btn-cleanup-orphans:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.35);
}

.btn-cleanup-orphans.no-orphans {
    background: var(--bg-card-header);
    color: var(--text-muted);
    box-shadow: none;
    border: 1px solid var(--border-color);
}

.btn-cleanup-orphans.no-orphans:hover {
    transform: none;
    box-shadow: none;
    border-color: var(--text-muted);
}

.btn-cleanup-orphans svg {
    width: 18px;
    height: 18px;
}

.btn-cleanup-orphans .orphan-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    background: rgba(255, 255, 255, 0.25);
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 700;
}

/* Cleanup Modal */
.cleanup-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.7);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(6px);
    padding: 1rem;
}

.cleanup-modal-overlay.active {
    display: flex;
    animation: cleanupFadeIn 0.2s ease;
}

@keyframes cleanupFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.cleanup-modal {
    background: var(--bg-card);
    border-radius: 16px;
    padding: 2rem;
    max-width: 420px;
    width: 100%;
    text-align: center;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    animation: cleanupSlideUp 0.25s ease;
}

@keyframes cleanupSlideUp {
    from { opacity: 0; transform: translateY(20px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.cleanup-modal-icon {
    width: 72px;
    height: 72px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(245, 158, 11, 0.1);
    border-radius: 50%;
    margin: 0 auto 1.25rem;
}

.cleanup-modal-icon svg {
    width: 36px;
    height: 36px;
    color: #f59e0b;
}

.cleanup-modal h3 {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0 0 0.5rem 0;
}

.cleanup-modal p {
    font-size: 0.9375rem;
    color: var(--text-muted);
    line-height: 1.6;
    margin: 0 0 1.5rem 0;
}

.cleanup-modal p strong {
    color: #f59e0b;
    font-weight: 600;
}

.cleanup-modal-actions {
    display: flex;
    gap: 0.75rem;
    justify-content: center;
}

.cleanup-modal-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 600;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}

.cleanup-modal-btn.cancel {
    background: var(--bg-card-header);
    border: 1px solid var(--border-color);
    color: var(--text-secondary);
}

.cleanup-modal-btn.cancel:hover {
    background: var(--bg-card);
    border-color: var(--text-muted);
}

.cleanup-modal-btn.confirm {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    border: none;
    color: #fff;
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.25);
}

.cleanup-modal-btn.confirm:hover {
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.35);
    transform: translateY(-1px);
}

.cleanup-modal-btn svg {
    width: 16px;
    height: 16px;
}

/* Tabs - same as folder buttons in media */
.comments-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}
.comments-tab {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.15s;
}
.comments-tab:hover { border-color: var(--forge-primary); color: var(--text-primary); text-decoration: none; }
.comments-tab.active {
    background: var(--forge-primary);
    border-color: var(--forge-primary);
    color: #fff;
}
.comments-tab .count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    background: rgba(0, 0, 0, 0.1);
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 600;
}
.comments-tab.active .count { background: rgba(255, 255, 255, 0.25); }
.comments-tab.pending .count { background: var(--forge-warning); color: white; }
.comments-tab.pending.active .count { background: rgba(255, 255, 255, 0.3); }

/* Toolbar - clean simple row */
.comments-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
    padding: 0.75rem 1rem;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-lg);
    flex-wrap: wrap;
}
.bulk-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.bulk-actions input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: var(--forge-primary);
}
.bulk-actions select {
    padding: 0.5rem 2rem 0.5rem 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    background: var(--bg-input);
    color: var(--text-primary);
    font-size: 0.875rem;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.5rem center;
}
.bulk-actions select:hover, .bulk-actions select:focus { border-color: var(--forge-primary); outline: none; }
.btn-apply {
    padding: 0.5rem 1rem;
    background: linear-gradient(135deg, var(--forge-primary), var(--forge-secondary));
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s;
}
.btn-apply:hover { opacity: 0.9; }

/* Search Box - Enhanced Design */
.comments-search-box {
    display: flex;
    gap: 0.5rem;
}

.comments-search-input-wrapper {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    background: var(--bg-input);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    transition: all 0.2s ease;
    min-width: 200px;
}

.comments-search-input-wrapper:focus-within {
    border-color: var(--forge-primary);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.comments-search-input-wrapper svg {
    width: 16px;
    height: 16px;
    color: var(--text-muted);
    flex-shrink: 0;
}

.comments-search-input-wrapper input {
    flex: 1;
    border: none;
    background: transparent;
    font-size: 0.875rem;
    color: var(--text-primary);
    outline: none;
    min-width: 0;
    padding: 0;
}

.comments-search-input-wrapper input::placeholder {
    color: var(--text-muted);
}

.comments-search-clear {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    color: var(--text-muted);
    transition: all 0.15s ease;
}

.comments-search-clear:hover {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
}

.comments-search-clear svg {
    width: 12px;
    height: 12px;
}

.comments-search-btn {
    padding: 0.5rem 1rem;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    color: var(--text-secondary);
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s;
    white-space: nowrap;
}

.comments-search-btn:hover {
    border-color: var(--forge-primary);
    color: var(--forge-primary);
}

/* Card */
.comments-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-lg);
    overflow: hidden;
}

/* Table */
.comments-table { width: 100%; border-collapse: collapse; }
.comments-table th {
    text-align: left;
    padding: 0.75rem 1rem;
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
    background: var(--bg-card-header);
    border-bottom: 1px solid var(--border-color);
}
.comments-table th.cb { width: 48px; text-align: center; }
.comments-table td {
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
    vertical-align: top;
}
.comments-table tr:last-child td { border-bottom: none; }
.comments-table tr:hover td { background: var(--bg-hover); }
.comments-table td.cb { text-align: center; vertical-align: middle; }
.comments-table td.cb input[type="checkbox"] {
    width: 16px;
    height: 16px;
    accent-color: var(--forge-primary);
    cursor: pointer;
}

/* Author */
.comment-author { display: flex; align-items: flex-start; gap: 0.75rem; }
.comment-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--bg-card-header);
    flex-shrink: 0;
    border: 2px solid var(--border-color);
}
.comment-author-info { min-width: 0; }
.comment-author-name { font-weight: 600; color: var(--text-primary); font-size: 0.875rem; }
.comment-author-email { font-size: 0.75rem; color: var(--text-muted); word-break: break-all; }

/* Content */
.comment-content { font-size: 0.875rem; color: var(--text-secondary); line-height: 1.6; max-width: 400px; }
.comment-content p { margin: 0 0 0.5rem 0; }
.comment-meta { font-size: 0.75rem; color: var(--text-muted); margin-top: 0.5rem; }

/* Post */
.comment-post a { color: var(--forge-primary); text-decoration: none; font-weight: 500; font-size: 0.875rem; }
.comment-post a:hover { text-decoration: underline; }
.comment-post-view { font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem; }
.comment-post-view a { color: var(--text-muted); text-decoration: none; }
.comment-post-view a:hover { color: var(--forge-primary); }
.comment-post-deleted { color: var(--text-muted); font-style: italic; font-size: 0.875rem; }

/* Actions */
.comment-actions { display: flex; gap: 0.25rem; flex-wrap: wrap; }
.comment-action {
    padding: 0.25rem 0.5rem;
    font-size: 0.6875rem;
    font-weight: 600;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    transition: all 0.15s;
    text-decoration: none;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}
.comment-action.approve { background: #dcfce7; color: #16a34a; }
.comment-action.approve:hover { background: #bbf7d0; }
.comment-action.spam { background: #fef3c7; color: #b45309; }
.comment-action.spam:hover { background: #fde68a; }
.comment-action.trash { background: #fee2e2; color: #dc2626; }
.comment-action.trash:hover { background: #fecaca; }
.comment-action.restore { background: #e0e7ff; color: #6366f1; }
.comment-action.restore:hover { background: #c7d2fe; }
.comment-action.edit { background: var(--bg-card-header); color: var(--text-secondary); }
.comment-action.edit:hover { background: var(--border-color); }
.comment-action.delete { background: #dc2626; color: white; }
.comment-action.delete:hover { background: #b91c1c; }

/* Status */
.status-badge {
    display: inline-flex;
    padding: 0.25rem 0.5rem;
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
    border-radius: 4px;
}
.status-badge.pending { background: #fef3c7; color: #b45309; }
.status-badge.approved { background: #dcfce7; color: #16a34a; }
.status-badge.spam { background: #fed7aa; color: #c2410c; }
.status-badge.trash { background: #fee2e2; color: #dc2626; }

/* Empty State - Enhanced Design */
.comments-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 4rem 2rem;
    background: linear-gradient(180deg, var(--bg-card) 0%, var(--bg-card-header) 100%);
    min-height: 320px;
}

.comments-empty-icon {
    width: 88px;
    height: 88px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
    border-radius: 50%;
    margin-bottom: 1.5rem;
    position: relative;
}

.comments-empty-icon::before {
    content: '';
    position: absolute;
    inset: -4px;
    border-radius: 50%;
    border: 2px dashed rgba(99, 102, 241, 0.2);
}

.comments-empty-icon svg {
    width: 40px;
    height: 40px;
    color: var(--forge-primary);
    opacity: 0.8;
}

.comments-empty-content {
    max-width: 400px;
}

.comments-empty-content h3 {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 0.5rem 0;
}

.comments-empty-content p {
    font-size: 0.875rem;
    color: var(--text-muted);
    line-height: 1.6;
    margin: 0 0 1.5rem 0;
}

.comments-empty-content p strong {
    color: var(--text-secondary);
    font-weight: 500;
}

.comments-empty-content p a {
    color: var(--forge-primary);
    text-decoration: none;
    font-weight: 500;
}

.comments-empty-content p a:hover {
    text-decoration: underline;
}

.comments-empty-action {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #fff;
    background: linear-gradient(135deg, var(--forge-primary) 0%, var(--forge-secondary) 100%);
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
}

.comments-empty-action:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(99, 102, 241, 0.35);
    text-decoration: none;
}

.comments-empty-action-secondary {
    background: var(--bg-card);
    color: var(--text-secondary);
    border: 1px solid var(--border-color);
    box-shadow: none;
}

.comments-empty-action-secondary:hover {
    background: var(--bg-card-header);
    border-color: var(--text-muted);
    color: var(--text-primary);
    transform: none;
    box-shadow: none;
}

@media (max-width: 640px) {
    .comments-empty-state {
        padding: 3rem 1.5rem;
        min-height: 280px;
    }
    
    .comments-empty-icon {
        width: 72px;
        height: 72px;
    }
    
    .comments-empty-icon svg {
        width: 32px;
        height: 32px;
    }
}

/* Pagination - Enhanced Design */
.comments-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-top: 1.5rem;
    padding: 1rem 1.25rem;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    flex-wrap: wrap;
}

.comments-pagination-info {
    font-size: 0.875rem;
    color: var(--text-muted);
}

.comments-pagination-info strong {
    color: var(--text-primary);
    font-weight: 600;
}

.comments-pagination-nav {
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

.comments-pagination-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    min-width: 36px;
    height: 36px;
    padding: 0 0.75rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text-secondary);
    background: var(--bg-card-header);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.2s ease;
}

.comments-pagination-btn:hover {
    border-color: var(--forge-primary);
    color: var(--forge-primary);
    background: rgba(99, 102, 241, 0.05);
    text-decoration: none;
}

.comments-pagination-btn svg {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
}

.comments-pagination-btn.prev svg {
    margin-right: 0.125rem;
}

.comments-pagination-btn.next svg {
    margin-left: 0.125rem;
}

.comments-pagination-pages {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.comments-pagination-page {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 36px;
    padding: 0 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text-secondary);
    background: transparent;
    border: 1px solid transparent;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.2s ease;
}

.comments-pagination-page:hover {
    color: var(--forge-primary);
    background: rgba(99, 102, 241, 0.05);
    text-decoration: none;
}

.comments-pagination-page.current {
    background: linear-gradient(135deg, var(--forge-primary) 0%, var(--forge-secondary) 100%);
    color: white;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(99, 102, 241, 0.25);
}

.comments-pagination-ellipsis {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    font-size: 0.875rem;
    color: var(--text-muted);
}

@media (max-width: 640px) {
    .comments-pagination {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
    }
    
    .comments-pagination-info {
        text-align: center;
        order: 2;
    }
    
    .comments-pagination-nav {
        justify-content: center;
        order: 1;
    }
    
    .comments-pagination-btn span {
        display: none;
    }
    
    .comments-pagination-btn {
        padding: 0;
        min-width: 36px;
    }
}

/* Reply indicator */
.reply-indicator { display: flex; align-items: center; gap: 0.25rem; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.25rem; }
.reply-indicator svg { width: 12px; height: 12px; }

/* Edit form */
.comment-edit-form { display: none; margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid var(--border-color); }
.comment-edit-form.active { display: block; }
.comment-edit-form textarea {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    font-size: 0.875rem;
    font-family: inherit;
    resize: vertical;
    min-height: 80px;
}
.comment-edit-form textarea:focus { outline: none; border-color: var(--forge-primary); }
.comment-edit-actions { display: flex; gap: 0.5rem; margin-top: 0.5rem; }
</style>

<div class="comments-page">
    <div class="comments-header">
        <h1>Comments</h1>
        <?php if ($orphanedCount > 0): ?>
        <button type="button" class="btn-cleanup-orphans" onclick="confirmCleanupOrphans()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 6h18"/>
                <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                <line x1="10" y1="11" x2="10" y2="17"/>
                <line x1="14" y1="11" x2="14" y2="17"/>
            </svg>
            Clean Up Orphans
            <span class="orphan-count"><?= $orphanedCount ?></span>
        </button>
        <?php endif; ?>
    </div>

    <!-- Status Tabs -->
    <div class="comments-tabs">
        <a href="<?= ADMIN_URL ?>/comments.php" class="comments-tab <?= !$status ? 'active' : '' ?>">
            All <span class="count"><?= number_format($counts['all']) ?></span>
        </a>
        <a href="<?= ADMIN_URL ?>/comments.php?status=pending" class="comments-tab pending <?= $status === 'pending' ? 'active' : '' ?>">
            Pending <span class="count"><?= number_format($counts['pending']) ?></span>
        </a>
        <a href="<?= ADMIN_URL ?>/comments.php?status=approved" class="comments-tab <?= $status === 'approved' ? 'active' : '' ?>">
            Approved <span class="count"><?= number_format($counts['approved']) ?></span>
        </a>
        <a href="<?= ADMIN_URL ?>/comments.php?status=spam" class="comments-tab <?= $status === 'spam' ? 'active' : '' ?>">
            Spam <span class="count"><?= number_format($counts['spam']) ?></span>
        </a>
        <a href="<?= ADMIN_URL ?>/comments.php?status=trash" class="comments-tab <?= $status === 'trash' ? 'active' : '' ?>">
            Trash <span class="count"><?= number_format($counts['trash']) ?></span>
        </a>
    </div>

    <!-- Toolbar -->
    <form method="post" id="commentsForm">
        <?= csrfField() ?>
        <div class="comments-toolbar">
            <div class="bulk-actions">
                <input type="checkbox" id="selectAll" onchange="toggleAllComments(this)" title="Select All">
                <select name="bulk_action">
                    <option value="">— Select Action —</option>
                    <?php if ($status !== 'approved'): ?>
                    <option value="approve">Approve</option>
                    <?php endif; ?>
                    <?php if ($status !== 'spam'): ?>
                    <option value="spam">Mark as Spam</option>
                    <?php endif; ?>
                    <?php if ($status !== 'trash'): ?>
                    <option value="trash">Move to Trash</option>
                    <?php endif; ?>
                    <?php if ($status === 'trash' || $status === 'spam'): ?>
                    <option value="restore">Restore</option>
                    <option value="delete">Delete Permanently</option>
                    <?php endif; ?>
                </select>
                <button type="submit" class="btn-apply">Apply</button>
            </div>

            <div class="comments-search-box">
                <div class="comments-search-input-wrapper">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <input type="text" name="s" value="<?= esc($search) ?>" placeholder="Search comments...">
                    <?php if ($search): ?>
                        <a href="<?= ADMIN_URL ?>/comments.php<?= $status ? '?status=' . esc($status) : '' ?>" class="comments-search-clear" title="Clear search">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>
                <button type="button" class="comments-search-btn" onclick="window.location.href='<?= ADMIN_URL ?>/comments.php?<?= $status ? 'status=' . esc($status) . '&' : '' ?>s=' + encodeURIComponent(document.querySelector('.comments-search-input-wrapper input').value)">
                    Search
                </button>
            </div>
        </div>

        <!-- Comments Table -->
        <div class="comments-card">
            <?php if (empty($comments)): ?>
            <div class="comments-empty-state">
                <div class="comments-empty-icon">
                    <?php if ($status === 'trash'): ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            <line x1="10" y1="11" x2="10" y2="17"></line>
                            <line x1="14" y1="11" x2="14" y2="17"></line>
                        </svg>
                    <?php elseif ($status === 'spam'): ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"></path>
                            <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
                        </svg>
                    <?php elseif ($search): ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                            <line x1="8" y1="11" x2="14" y2="11"></line>
                        </svg>
                    <?php elseif ($status === 'pending'): ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    <?php else: ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            <line x1="9" y1="9" x2="15" y2="9"></line>
                            <line x1="9" y1="13" x2="13" y2="13"></line>
                        </svg>
                    <?php endif; ?>
                </div>
                <div class="comments-empty-content">
                    <h3>
                        <?php if ($search): ?>
                            No matching comments
                        <?php elseif ($status === 'trash'): ?>
                            Trash is empty
                        <?php elseif ($status === 'spam'): ?>
                            No spam comments
                        <?php elseif ($status === 'pending'): ?>
                            No pending comments
                        <?php elseif ($status === 'approved'): ?>
                            No approved comments yet
                        <?php else: ?>
                            No comments yet
                        <?php endif; ?>
                    </h3>
                    <p>
                        <?php if ($search): ?>
                            No comments match "<strong><?= esc($search) ?></strong>". Try different search terms or <a href="<?= ADMIN_URL ?>/comments.php<?= $status ? '?status=' . esc($status) : '' ?>">clear the search</a>.
                        <?php elseif ($status === 'trash'): ?>
                            Comments you delete will appear here before being permanently removed.
                        <?php elseif ($status === 'spam'): ?>
                            Comments marked as spam will appear here. Good news — your content is spam-free!
                        <?php elseif ($status === 'pending'): ?>
                            Comments awaiting moderation will appear here. All caught up!
                        <?php elseif ($status === 'approved'): ?>
                            Approved comments will appear here once you start moderating.
                        <?php else: ?>
                            When visitors engage with your content, their comments will appear here for moderation.
                        <?php endif; ?>
                    </p>
                    <?php if ($search): ?>
                        <a href="<?= ADMIN_URL ?>/comments.php<?= $status ? '?status=' . esc($status) : '' ?>" class="comments-empty-action comments-empty-action-secondary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                            Clear Search
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <table class="comments-table">
                <thead>
                    <tr>
                        <th class="cb"></th>
                        <th>Author</th>
                        <th>Comment</th>
                        <th>In Response To</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($comments as $comment): ?>
                    <?php $post = $postCache[$comment['post_id']] ?? null; ?>
                    <tr>
                        <td class="cb">
                            <input type="checkbox" name="comment_ids[]" value="<?= $comment['id'] ?>" class="comment-checkbox">
                        </td>
                        <td>
                            <div class="comment-author">
                                <img src="<?= Comment::getGravatar($comment, 40) ?>" alt="" class="comment-avatar">
                                <div class="comment-author-info">
                                    <div class="comment-author-name"><?= esc(Comment::getAuthorName($comment)) ?></div>
                                    <?php if ($comment['author_email']): ?>
                                    <div class="comment-author-email"><?= esc($comment['author_email']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if ($comment['parent_id'] > 0): ?>
                            <div class="reply-indicator">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 14 4 9 9 4"></polyline>
                                    <path d="M20 20v-7a4 4 0 0 0-4-4H4"></path>
                                </svg>
                                Reply
                            </div>
                            <?php endif; ?>
                            <div class="comment-content">
                                <?= nl2br(esc(mb_substr($comment['content'], 0, 200))) ?><?= strlen($comment['content']) > 200 ? '...' : '' ?>
                            </div>
                            <div class="comment-meta">
                                <?php 
                                $dateFormat = getOption('date_format', 'M j, Y');
                                $timeFormat = getOption('time_format', 'g:i a');
                                echo formatDate($comment['created_at'], $dateFormat) . ' at ' . date($timeFormat, strtotime($comment['created_at']));
                                ?>
                                <?php if ($comment['author_ip']): ?>
                                 · IP: <?= esc($comment['author_ip']) ?>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Inline Edit Form -->
                            <div class="comment-edit-form" id="editForm<?= $comment['id'] ?>">
                                <form method="post">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="edit_comment" value="1">
                                    <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                    <textarea name="content"><?= esc($comment['content']) ?></textarea>
                                    <div class="comment-edit-actions">
                                        <button type="submit" class="comment-action approve">Save</button>
                                        <button type="button" class="comment-action edit" onclick="toggleEdit(<?= $comment['id'] ?>)">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </td>
                        <td>
                            <div class="comment-post">
                                <?php if ($post): ?>
                                <a href="<?= ADMIN_URL ?>/post-edit.php?id=<?= $post['id'] ?>">
                                    <?= esc(mb_substr($post['title'], 0, 40)) ?><?= strlen($post['title']) > 40 ? '...' : '' ?>
                                </a>
                                <div class="comment-post-view">
                                    <a href="<?= Post::permalink($post) ?>" target="_blank">View Post →</a>
                                </div>
                                <?php else: ?>
                                <span class="comment-post-deleted">Post deleted</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="status-badge <?= esc($comment['status']) ?>">
                                <?= esc(Comment::STATUS_LABELS[$comment['status']] ?? $comment['status']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="comment-actions">
                                <?php if ($comment['status'] !== Comment::STATUS_APPROVED): ?>
                                <form method="post" style="display: inline;">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                    <button type="submit" class="comment-action approve">Approve</button>
                                </form>
                                <?php endif; ?>
                                
                                <?php if ($comment['status'] !== Comment::STATUS_SPAM): ?>
                                <form method="post" style="display: inline;">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="spam">
                                    <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                    <button type="submit" class="comment-action spam">Spam</button>
                                </form>
                                <?php endif; ?>
                                
                                <button type="button" class="comment-action edit" onclick="toggleEdit(<?= $comment['id'] ?>)">Edit</button>
                                
                                <?php if ($comment['status'] === Comment::STATUS_TRASH): ?>
                                <form method="post" style="display: inline;">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="restore">
                                    <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                    <button type="submit" class="comment-action restore">Restore</button>
                                </form>
                                <form method="post" style="display: inline;" onsubmit="return confirm('Permanently delete this comment?')">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                    <button type="submit" class="comment-action delete">Delete</button>
                                </form>
                                <?php else: ?>
                                <form method="post" style="display: inline;">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="trash">
                                    <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                    <button type="submit" class="comment-action trash">Trash</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </form>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="comments-pagination">
        <div class="comments-pagination-info">
            Showing <strong><?= number_format(($page - 1) * $perPage + 1) ?></strong> to <strong><?= number_format(min($page * $perPage, $totalComments)) ?></strong> of <strong><?= number_format($totalComments) ?></strong> comments
        </div>
        
        <div class="comments-pagination-nav">
            <?php if ($page > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['paged' => $page - 1])) ?>" class="comments-pagination-btn prev">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                <span>Previous</span>
            </a>
            <?php endif; ?>
            
            <div class="comments-pagination-pages">
                <?php
                // Smart pagination with ellipsis
                $showPages = [];
                $showPages[] = 1; // Always show first page
                
                // Pages around current
                for ($i = max(2, $page - 1); $i <= min($totalPages - 1, $page + 1); $i++) {
                    $showPages[] = $i;
                }
                
                if ($totalPages > 1) {
                    $showPages[] = $totalPages; // Always show last page
                }
                
                $showPages = array_unique($showPages);
                sort($showPages);
                
                $prevShown = 0;
                foreach ($showPages as $p):
                    if ($prevShown && $p > $prevShown + 1):
                ?>
                    <span class="comments-pagination-ellipsis">…</span>
                <?php endif; ?>
                
                <?php if ($p === $page): ?>
                    <span class="comments-pagination-page current"><?= $p ?></span>
                <?php else: ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['paged' => $p])) ?>" class="comments-pagination-page"><?= $p ?></a>
                <?php endif; ?>
                
                <?php
                    $prevShown = $p;
                endforeach;
                ?>
            </div>
            
            <?php if ($page < $totalPages): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['paged' => $page + 1])) ?>" class="comments-pagination-btn next">
                <span>Next</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Cleanup Orphans Modal -->
<?php if ($orphanedCount > 0): ?>
<div class="cleanup-modal-overlay" id="cleanupModal">
    <div class="cleanup-modal">
        <div class="cleanup-modal-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 8v4"/>
                <path d="M12 16h.01"/>
            </svg>
        </div>
        <h3>Clean Up Orphaned Comments?</h3>
        <p>Found <strong><?= $orphanedCount ?> orphaned comment<?= $orphanedCount !== 1 ? 's' : '' ?></strong> — comments attached to posts that no longer exist. This action will permanently delete them and cannot be undone.</p>
        <div class="cleanup-modal-actions">
            <button type="button" class="cleanup-modal-btn cancel" onclick="closeCleanupModal()">
                Cancel
            </button>
            <form method="post" style="display: inline;">
                <?= csrfField() ?>
                <input type="hidden" name="cleanup_orphans" value="1">
                <button type="submit" class="cleanup-modal-btn confirm">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 6h18"/>
                        <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                    </svg>
                    Delete <?= $orphanedCount ?> Comment<?= $orphanedCount !== 1 ? 's' : '' ?>
                </button>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function toggleAllComments(checkbox) {
    var checkboxes = document.querySelectorAll('.comment-checkbox');
    for (var i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = checkbox.checked;
    }
}

function toggleEdit(commentId) {
    var form = document.getElementById('editForm' + commentId);
    if (form) {
        form.classList.toggle('active');
    }
}

function confirmCleanupOrphans() {
    document.getElementById('cleanupModal').classList.add('active');
}

function closeCleanupModal() {
    document.getElementById('cleanupModal').classList.remove('active');
}

// Close modal on outside click
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('cleanupModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeCleanupModal();
            }
        });
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeCleanupModal();
    }
});
</script>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
