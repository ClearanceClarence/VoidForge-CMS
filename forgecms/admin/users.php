<?php
/**
 * Users Management
 */

define('CMS_ROOT', dirname(__DIR__));
require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/user.php';
require_once CMS_ROOT . '/includes/post.php';
require_once CMS_ROOT . '/includes/media.php';

User::startSession();
User::requireRole('admin');

$pageTitle = 'Users';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);

    if ($action === 'delete' && $userId) {
        if ($userId === User::current()['id']) {
            setFlash('error', 'You cannot delete your own account.');
        } elseif (User::delete($userId)) {
            setFlash('success', 'User deleted successfully.');
        } else {
            setFlash('error', 'Cannot delete the last administrator.');
        }
        redirect(ADMIN_URL . '/users.php');
    }
}

$users = User::all();

include ADMIN_PATH . '/includes/header.php';
?>

<div class="action-bar">
    <div class="action-bar-left">
        <span style="color: var(--color-gray-500);"><?= count($users) ?> users</span>
    </div>
    <a href="<?= ADMIN_URL ?>/user-edit.php" class="btn btn-primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        Add New User
    </a>
</div>

<div class="card">
    <?php if (empty($users)): ?>
        <div class="empty-state">
            <h3>No users found</h3>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Registered</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <a href="<?= ADMIN_URL ?>/user-edit.php?id=<?= $user['id'] ?>">
                                    <strong><?= esc($user['username']) ?></strong>
                                </a>
                            </td>
                            <td><?= esc($user['display_name']) ?></td>
                            <td><?= esc($user['email']) ?></td>
                            <td>
                                <span class="status-badge status-<?= $user['role'] === 'admin' ? 'published' : 'draft' ?>">
                                    <?= User::getRoleLabel($user['role']) ?>
                                </span>
                            </td>
                            <td><?= formatDate($user['created_at']) ?></td>
                            <td>
                                <div class="table-actions">
                                    <a href="<?= ADMIN_URL ?>/user-edit.php?id=<?= $user['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                    <?php if ($user['id'] !== User::current()['id']): ?>
                                        <form method="post" style="display: inline;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn btn-secondary btn-sm" 
                                                    data-confirm="Delete this user permanently?">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
