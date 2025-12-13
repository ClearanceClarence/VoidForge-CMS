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
.search-box {
    display: flex;
    gap: 0.5rem;
}
.search-box input {
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    background: var(--bg-input);
    font-size: 0.875rem;
    width: 180px;
}
.search-box input:focus { border-color: var(--forge-primary); outline: none; }
.btn-search {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 0.75rem;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 6px;
    color: var(--text-secondary);
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.15s;
}
.btn-search:hover { border-color: var(--forge-primary); color: var(--forge-primary); }
.btn-search svg { width: 16px; height: 16px; }

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

/* Empty */
.empty-state { text-align: center; padding: 3rem 2rem; }
.empty-state svg { width: 48px; height: 48px; color: var(--border-color); margin-bottom: 1rem; }
.empty-state h3 { font-size: 1rem; font-weight: 600; color: var(--text-primary); margin: 0 0 0.25rem 0; }
.empty-state p { color: var(--text-muted); margin: 0; font-size: 0.875rem; }

/* Pagination */
.pagination { display: flex; align-items: center; justify-content: center; gap: 0.25rem; margin-top: 1.5rem; }
.pagination a, .pagination span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    height: 32px;
    padding: 0 0.5rem;
    font-size: 0.875rem;
    color: var(--text-secondary);
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 6px;
    text-decoration: none;
}
.pagination a:hover { border-color: var(--forge-primary); color: var(--text-primary); }
.pagination .current { background: var(--forge-primary); border-color: var(--forge-primary); color: white; }

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

            <div class="search-box">
                <input type="text" name="s" value="<?= esc($search) ?>" placeholder="Search comments...">
                <button type="button" class="btn-search" onclick="window.location.href='<?= ADMIN_URL ?>/comments.php?<?= $status ? 'status=' . esc($status) . '&' : '' ?>s=' + encodeURIComponent(this.previousElementSibling.value)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    Search
                </button>
            </div>
        </div>

        <!-- Comments Table -->
        <div class="comments-card">
            <?php if (empty($comments)): ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                <h3>No comments found</h3>
                <p>
                    <?php if ($search): ?>
                        No comments match your search.
                    <?php elseif ($status): ?>
                        No <?= esc($status) ?> comments.
                    <?php else: ?>
                        Comments will appear here once visitors start engaging with your content.
                    <?php endif; ?>
                </p>
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
    <div class="pagination">
        <?php if ($page > 1): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['paged' => $page - 1])) ?>">← Previous</a>
        <?php endif; ?>
        
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <?php if ($i === $page): ?>
            <span class="current"><?= $i ?></span>
            <?php else: ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['paged' => $i])) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        
        <?php if ($page < $totalPages): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['paged' => $page + 1])) ?>">Next →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

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
</script>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
