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

Post::init();

User::startSession();
User::requireLogin();

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

// Get posts
$queryArgs = [
    'post_type' => $postType,
    'status' => $status ?: ['published', 'draft'],
    'search' => $search,
    'limit' => $perPage,
    'offset' => ($currentPage - 1) * $perPage,
];

if ($status === 'trash') {
    $queryArgs['status'] = 'trash';
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
        }
        redirect(currentUrl());
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
        <?php if ($totalTrash > 0): ?>
            <a href="?type=<?= $postType ?>&status=trash" class="btn btn-secondary btn-sm <?= $status === 'trash' ? 'active' : '' ?>">
                Trash (<?= $totalTrash ?>)
            </a>
        <?php endif; ?>
    </div>

    <a href="<?= ADMIN_URL ?>/post-edit.php?type=<?= $postType ?>" class="btn btn-primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        Add New <?= esc($typeConfig['singular']) ?>
    </a>
</div>

<div class="card">
    <div class="card-header">
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
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $post): ?>
                        <?php $author = Post::getAuthor($post); ?>
                        <tr>
                            <td>
                                <?php if ($status !== 'trash'): ?>
                                    <a href="<?= ADMIN_URL ?>/post-edit.php?id=<?= $post['id'] ?>">
                                        <strong><?= esc($post['title'] ?: '(no title)') ?></strong>
                                    </a>
                                <?php else: ?>
                                    <strong><?= esc($post['title'] ?: '(no title)') ?></strong>
                                <?php endif; ?>
                            </td>
                            <td><?= esc($author['display_name'] ?? 'Unknown') ?></td>
                            <td>
                                <span class="status-badge status-<?= $post['status'] ?>">
                                    <?= Post::STATUS_LABELS[$post['status']] ?>
                                </span>
                            </td>
                            <td><?= formatDate($post['created_at']) ?></td>
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

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
