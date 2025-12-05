<?php
/**
 * Users Management - Forge CMS
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

$pageTitle = 'Users';

// Gravatar helper
function getGravatar($email, $size = 80) {
    $hash = md5(strtolower(trim($email)));
    return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d=mp";
}

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

// Get post count and last login for each user
$postsTable = Database::table('posts');
foreach ($users as &$user) {
    $postCount = Database::queryValue(
        "SELECT COUNT(*) FROM {$postsTable} WHERE author_id = ?",
        [$user['id']]
    );
    $user['post_count'] = (int)$postCount;
}
unset($user);

include ADMIN_PATH . '/includes/header.php';
?>

<style>
.users-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.users-header h2 {
    margin: 0;
}

.users-count {
    color: var(--text-muted);
    font-size: 0.875rem;
}

.users-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-lg);
    overflow: hidden;
}

.users-table {
    width: 100%;
    border-collapse: collapse;
}

.users-table th {
    text-align: left;
    padding: 1rem 1.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
    background: var(--bg-card-header);
    border-bottom: 1px solid var(--border-color);
}

.users-table td {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border-color);
    vertical-align: middle;
}

.users-table tbody tr:last-child td {
    border-bottom: none;
}

.users-table tbody tr:hover {
    background: var(--bg-hover);
}

.user-cell {
    display: flex;
    align-items: center;
    gap: 0.875rem;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--bg-card-header);
}

.user-info {
    display: flex;
    flex-direction: column;
}

.user-name {
    font-weight: 600;
    color: var(--text-primary);
    text-decoration: none;
}

.user-name:hover {
    color: var(--forge-primary);
}

.user-username {
    font-size: 0.8125rem;
    color: var(--text-muted);
}

.user-email {
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.role-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.25rem 0.625rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
}

.role-badge.admin {
    background: rgba(99, 102, 241, 0.1);
    color: var(--forge-primary);
}

.role-badge.editor {
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
}

.role-badge.author {
    background: rgba(245, 158, 11, 0.1);
    color: #d97706;
}

.role-badge.subscriber {
    background: var(--bg-card-header);
    color: var(--text-secondary);
}

.user-date {
    font-size: 0.8125rem;
    color: var(--text-muted);
}

.user-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

.btn-edit {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.75rem;
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--text-secondary);
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    text-decoration: none;
    transition: all 0.15s;
}

.btn-edit:hover {
    border-color: var(--forge-primary);
    color: var(--forge-primary);
}

.btn-delete {
    display: inline-flex;
    align-items: center;
    padding: 0.375rem 0.5rem;
    font-size: 0.8125rem;
    color: var(--text-muted);
    background: transparent;
    border: 1px solid transparent;
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: all 0.15s;
}

.btn-delete:hover {
    color: var(--forge-danger);
    background: rgba(239, 68, 68, 0.08);
    border-color: rgba(239, 68, 68, 0.2);
}

.empty-users {
    padding: 4rem 2rem;
    text-align: center;
}

.empty-users svg {
    width: 48px;
    height: 48px;
    color: var(--text-muted);
    margin-bottom: 1rem;
}

.empty-users h3 {
    font-size: 1rem;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.empty-users p {
    color: var(--text-muted);
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .users-table th:nth-child(4),
    .users-table td:nth-child(4),
    .users-table th:nth-child(5),
    .users-table td:nth-child(5),
    .users-table th:nth-child(6),
    .users-table td:nth-child(6) {
        display: none;
    }
}
</style>

<div class="users-header">
    <div>
        <h2>Users</h2>
        <span class="users-count"><?= count($users) ?> registered user<?= count($users) !== 1 ? 's' : '' ?></span>
    </div>
    <a href="<?= ADMIN_URL ?>/user-edit.php" class="btn btn-primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
            <circle cx="8.5" cy="7" r="4"></circle>
            <line x1="20" y1="8" x2="20" y2="14"></line>
            <line x1="23" y1="11" x2="17" y2="11"></line>
        </svg>
        Add User
    </a>
</div>

<div class="users-card">
    <?php if (empty($users)): ?>
        <div class="empty-users">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            <h3>No users found</h3>
            <p>Add your first user to get started</p>
        </div>
    <?php else: ?>
        <table class="users-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Posts</th>
                    <th>Last Login</th>
                    <th>Joined</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <div class="user-cell">
                                <img src="<?= getGravatar($user['email'], 80) ?>" alt="" class="user-avatar">
                                <div class="user-info">
                                    <a href="<?= ADMIN_URL ?>/user-edit.php?id=<?= $user['id'] ?>" class="user-name">
                                        <?= esc($user['display_name'] ?: $user['username']) ?>
                                    </a>
                                    <span class="user-username">@<?= esc($user['username']) ?></span>
                                </div>
                            </div>
                        </td>
                        <td class="user-email"><?= esc($user['email']) ?></td>
                        <td>
                            <span class="role-badge <?= esc($user['role']) ?>">
                                <?= User::getRoleLabel($user['role']) ?>
                            </span>
                        </td>
                        <td class="user-posts">
                            <?php if ($user['post_count'] > 0): ?>
                            <a href="<?= ADMIN_URL ?>/posts.php?author=<?= $user['id'] ?>" style="color: var(--forge-primary); text-decoration: none;">
                                <?= $user['post_count'] ?>
                            </a>
                            <?php else: ?>
                            <span style="color: var(--text-muted);">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="user-date">
                            <?php if (!empty($user['last_login'])): ?>
                            <?= formatDate($user['last_login'], 'M j, Y g:i A') ?>
                            <?php else: ?>
                            <span style="color: var(--text-muted);">Never</span>
                            <?php endif; ?>
                        </td>
                        <td class="user-date"><?= formatDate($user['created_at']) ?></td>
                        <td>
                            <div class="user-actions">
                                <a href="<?= ADMIN_URL ?>/user-edit.php?id=<?= $user['id'] ?>" class="btn-edit">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                    Edit
                                </a>
                                <?php if ($user['id'] !== User::current()['id']): ?>
                                    <form method="post" style="display: inline;" onsubmit="return confirm('Delete this user permanently?')">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="btn-delete" title="Delete">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            </svg>
                                        </button>
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

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
