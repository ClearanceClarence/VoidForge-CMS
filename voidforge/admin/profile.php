<?php
/**
 * User Profile
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
User::requireLogin();

$pageTitle = 'Profile';
$user = User::current();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $data = [
        'email' => trim($_POST['email'] ?? ''),
        'display_name' => trim($_POST['display_name'] ?? ''),
        'password' => $_POST['password'] ?? '',
    ];

    // Validate email
    if (empty($data['email'])) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email address';
    } else {
        $existing = User::findByEmail($data['email']);
        if ($existing && $existing['id'] !== $user['id']) {
            $errors['email'] = 'Email already exists';
        }
    }

    // Validate password if provided
    if (!empty($data['password'])) {
        if (strlen($data['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        }
        
        $currentPassword = $_POST['current_password'] ?? '';
        if (!password_verify($currentPassword, $user['password'])) {
            $errors['current_password'] = 'Current password is incorrect';
        }
    }

    if (empty($errors)) {
        User::update($user['id'], $data);
        setFlash('success', 'Profile updated successfully.');
        redirect(ADMIN_URL . '/profile.php');
    }
}

include ADMIN_PATH . '/includes/header.php';
?>

<div style="max-width: 600px;">
    <form method="post">
        <?= csrfField() ?>
        
        <div class="card" style="margin-bottom: 1rem;">
            <div class="card-header">
                <h3 class="card-title">Profile Details</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-input" value="<?= esc($user['username']) ?>" readonly disabled>
                    <div class="form-hint">Username cannot be changed.</div>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-input" 
                           value="<?= esc($_POST['email'] ?? $user['email']) ?>" required>
                    <?php if (isset($errors['email'])): ?>
                        <div class="form-error"><?= esc($errors['email']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="display_name" class="form-label">Display Name</label>
                    <input type="text" id="display_name" name="display_name" class="form-input" 
                           value="<?= esc($_POST['display_name'] ?? $user['display_name']) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Role</label>
                    <input type="text" class="form-input" value="<?= User::getRoleLabel($user['role']) ?>" readonly disabled>
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom: 1rem;">
            <div class="card-header">
                <h3 class="card-title">Change Password</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="current_password" class="form-label">Current Password</label>
                    <input type="password" id="current_password" name="current_password" class="form-input">
                    <?php if (isset($errors['current_password'])): ?>
                        <div class="form-error"><?= esc($errors['current_password']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">New Password</label>
                    <input type="password" id="password" name="password" class="form-input">
                    <div class="form-hint">Leave blank to keep current password. Minimum 8 characters.</div>
                    <?php if (isset($errors['password'])): ?>
                        <div class="form-error"><?= esc($errors['password']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Update Profile</button>
    </form>
</div>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
