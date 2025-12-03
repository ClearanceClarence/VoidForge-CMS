<?php
/**
 * User Edit/Add - Forge CMS
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

$userId = (int)($_GET['id'] ?? 0);
$user = $userId ? User::find($userId) : null;

if ($userId && !$user) {
    redirect(ADMIN_URL . '/users.php');
}

$pageTitle = $user ? 'Edit User' : 'Add New User';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $data = [
        'username' => trim($_POST['username'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'display_name' => trim($_POST['display_name'] ?? ''),
        'role' => $_POST['role'] ?? 'subscriber',
        'password' => $_POST['password'] ?? '',
    ];

    $errors = User::validate($data, $user['id'] ?? null);

    if (empty($errors)) {
        if ($user) {
            User::update($user['id'], $data);
            setFlash('success', 'User updated successfully.');
        } else {
            $userId = User::create($data);
            setFlash('success', 'User created successfully.');
            redirect(ADMIN_URL . '/user-edit.php?id=' . $userId);
        }
        
        redirect(ADMIN_URL . '/user-edit.php?id=' . ($user['id'] ?? $userId));
    }
}

include ADMIN_PATH . '/includes/header.php';
?>

<style>
.user-form-container {
    max-width: 640px;
}

.user-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-lg);
    margin-bottom: 1.5rem;
    overflow: hidden;
}

.user-card-header {
    padding: 1rem 1.25rem;
    background: var(--bg-card-header);
    border-bottom: 1px solid var(--border-color);
}

.user-card-title {
    font-size: 0.9375rem;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.user-card-title svg {
    width: 18px;
    height: 18px;
    color: var(--forge-primary);
}

.user-card-body {
    padding: 1.25rem;
}

.user-avatar-section {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    padding-bottom: 1.25rem;
    margin-bottom: 1.25rem;
    border-bottom: 1px solid var(--border-color);
}

.user-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--forge-primary) 0%, var(--forge-primary-dark) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 2rem;
    font-weight: 700;
    flex-shrink: 0;
}

.user-avatar-info {
    flex: 1;
}

.user-avatar-name {
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.user-avatar-email {
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-group {
    margin-bottom: 1.25rem;
}

.form-group:last-child {
    margin-bottom: 0;
}

.form-label {
    display: block;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.form-input, .form-select {
    width: 100%;
    padding: 0.75rem 1rem;
    font-size: 0.9375rem;
    line-height: 1.5;
    color: var(--text-primary);
    background: var(--bg-input);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    transition: all 0.15s;
    font-family: inherit;
}

.form-input:focus, .form-select:focus {
    outline: none;
    border-color: var(--forge-primary);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}

.form-hint {
    margin-top: 0.375rem;
    font-size: 0.8125rem;
    color: var(--text-muted);
}

.form-error {
    margin-top: 0.375rem;
    font-size: 0.8125rem;
    color: var(--forge-danger);
}

.role-options {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
}

.role-option {
    position: relative;
}

.role-option input {
    position: absolute;
    opacity: 0;
}

.role-option label {
    display: block;
    padding: 0.875rem 1rem;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: all 0.15s;
}

.role-option label:hover {
    border-color: var(--forge-primary);
}

.role-option input:checked + label {
    border-color: var(--forge-primary);
    background: rgba(99, 102, 241, 0.05);
}

.role-option .role-name {
    font-weight: 600;
    font-size: 0.875rem;
    margin-bottom: 0.125rem;
}

.role-option .role-desc {
    font-size: 0.75rem;
    color: var(--text-muted);
}

.btn-group {
    display: flex;
    gap: 0.75rem;
}

@media (max-width: 640px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    .role-options {
        grid-template-columns: 1fr;
    }
}

/* Password Strength Indicator */
.password-strength {
    margin-top: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.strength-bar {
    flex: 1;
    height: 6px;
    background: var(--border-color);
    border-radius: 3px;
    overflow: hidden;
}

.strength-fill {
    height: 100%;
    width: 0%;
    border-radius: 3px;
    transition: all 0.3s ease;
}

.strength-fill.weak { width: 25%; background: #ef4444; }
.strength-fill.fair { width: 50%; background: #f59e0b; }
.strength-fill.good { width: 75%; background: #10b981; }
.strength-fill.strong { width: 100%; background: #22c55e; }

.strength-text {
    font-size: 0.75rem;
    font-weight: 600;
    min-width: 60px;
}

.strength-text.weak { color: #ef4444; }
.strength-text.fair { color: #f59e0b; }
.strength-text.good { color: #10b981; }
.strength-text.strong { color: #22c55e; }

/* Password Match Indicator */
.password-match {
    margin-top: 0.375rem;
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.8125rem;
}

.password-match.match {
    color: #10b981;
}

.password-match.no-match {
    color: #ef4444;
}

.password-match .match-icon {
    flex-shrink: 0;
}
</style>

<div class="page-header" style="margin-bottom: 1.5rem;">
    <h2><?= esc($pageTitle) ?></h2>
</div>

<div class="user-form-container">
    <form method="post">
        <?= csrfField() ?>
        
        <div class="user-card">
            <div class="user-card-header">
                <h3 class="user-card-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    Account Information
                </h3>
            </div>
            <div class="user-card-body">
                <?php if ($user): ?>
                <div class="user-avatar-section">
                    <div class="user-avatar">
                        <?= strtoupper(substr($user['display_name'] ?: $user['username'], 0, 1)) ?>
                    </div>
                    <div class="user-avatar-info">
                        <div class="user-avatar-name"><?= esc($user['display_name'] ?: $user['username']) ?></div>
                        <div class="user-avatar-email"><?= esc($user['email']) ?></div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" id="username" name="username" class="form-input" 
                               value="<?= esc($user['username'] ?? $_POST['username'] ?? '') ?>" 
                               placeholder="Enter username" required>
                        <?php if (isset($errors['username'])): ?>
                            <div class="form-error"><?= esc($errors['username']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="display_name" class="form-label">Display Name</label>
                        <input type="text" id="display_name" name="display_name" class="form-input" 
                               value="<?= esc($user['display_name'] ?? $_POST['display_name'] ?? '') ?>"
                               placeholder="Enter display name">
                    </div>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" id="email" name="email" class="form-input" 
                           value="<?= esc($user['email'] ?? $_POST['email'] ?? '') ?>" 
                           placeholder="name@example.com" required>
                    <?php if (isset($errors['email'])): ?>
                        <div class="form-error"><?= esc($errors['email']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="user-card">
            <div class="user-card-header">
                <h3 class="user-card-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                    Role & Permissions
                </h3>
            </div>
            <div class="user-card-body">
                <div class="form-group">
                    <label class="form-label">User Role</label>
                    <div class="role-options">
                        <?php 
                        $roleDescriptions = [
                            'subscriber' => 'Can view content only',
                            'author' => 'Can create and edit own posts',
                            'editor' => 'Can edit all posts and pages',
                            'admin' => 'Full access to all features'
                        ];
                        foreach (User::ROLE_LABELS as $value => $label): 
                        ?>
                        <div class="role-option">
                            <input type="radio" name="role" id="role_<?= $value ?>" value="<?= $value ?>" 
                                   <?= ($user['role'] ?? 'subscriber') === $value ? 'checked' : '' ?>>
                            <label for="role_<?= $value ?>">
                                <div class="role-name"><?= $label ?></div>
                                <div class="role-desc"><?= $roleDescriptions[$value] ?? '' ?></div>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="user-card">
            <div class="user-card-header">
                <h3 class="user-card-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    <?= $user ? 'Change Password' : 'Password' ?>
                </h3>
            </div>
            <div class="user-card-body">
                <div class="form-group">
                    <label for="password" class="form-label">
                        <?= $user ? 'New Password' : 'Password' ?>
                    </label>
                    <input type="password" id="password" name="password" class="form-input" 
                           placeholder="<?= $user ? 'Leave blank to keep current' : 'Enter password' ?>"
                           <?= !$user ? 'required' : '' ?> autocomplete="new-password">
                    
                    <!-- Password Strength Indicator -->
                    <div class="password-strength" id="passwordStrength" style="display: none;">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                        <span class="strength-text" id="strengthText"></span>
                    </div>
                    
                    <?php if ($user): ?>
                        <div class="form-hint">Leave blank to keep current password.</div>
                    <?php endif; ?>
                    <?php if (isset($errors['password'])): ?>
                        <div class="form-error"><?= esc($errors['password']) ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="password_confirm" class="form-label">
                        Confirm Password
                    </label>
                    <input type="password" id="password_confirm" name="password_confirm" class="form-input" 
                           placeholder="Confirm password" autocomplete="new-password">
                    <div class="password-match" id="passwordMatch" style="display: none;">
                        <svg class="match-icon" id="matchIcon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span id="matchText"></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                <?= $user ? 'Save Changes' : 'Create User' ?>
            </button>
            <a href="<?= ADMIN_URL ?>/users.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('password_confirm');
    const strengthDiv = document.getElementById('passwordStrength');
    const strengthFill = document.getElementById('strengthFill');
    const strengthText = document.getElementById('strengthText');
    const matchDiv = document.getElementById('passwordMatch');
    const matchIcon = document.getElementById('matchIcon');
    const matchText = document.getElementById('matchText');
    const form = document.querySelector('form');
    
    // Password strength checker
    function checkStrength(password) {
        let score = 0;
        
        if (password.length >= 8) score++;
        if (password.length >= 12) score++;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
        if (/\d/.test(password)) score++;
        if (/[^a-zA-Z0-9]/.test(password)) score++;
        
        if (score <= 1) return { level: 'weak', text: 'Weak' };
        if (score === 2) return { level: 'fair', text: 'Fair' };
        if (score === 3) return { level: 'good', text: 'Good' };
        return { level: 'strong', text: 'Strong' };
    }
    
    // Update strength indicator
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        
        if (password.length === 0) {
            strengthDiv.style.display = 'none';
            return;
        }
        
        strengthDiv.style.display = 'flex';
        const result = checkStrength(password);
        
        strengthFill.className = 'strength-fill ' + result.level;
        strengthText.className = 'strength-text ' + result.level;
        strengthText.textContent = result.text;
        
        // Also check match if confirm has value
        if (confirmInput.value) {
            checkMatch();
        }
    });
    
    // Check password match
    function checkMatch() {
        const password = passwordInput.value;
        const confirm = confirmInput.value;
        
        if (confirm.length === 0) {
            matchDiv.style.display = 'none';
            return;
        }
        
        matchDiv.style.display = 'flex';
        
        if (password === confirm) {
            matchDiv.className = 'password-match match';
            matchIcon.innerHTML = '<polyline points="20 6 9 17 4 12"></polyline>';
            matchText.textContent = 'Passwords match';
        } else {
            matchDiv.className = 'password-match no-match';
            matchIcon.innerHTML = '<line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line>';
            matchText.textContent = 'Passwords do not match';
        }
    }
    
    confirmInput.addEventListener('input', checkMatch);
    
    // Form validation
    form.addEventListener('submit', function(e) {
        const password = passwordInput.value;
        const confirm = confirmInput.value;
        const isNewUser = <?= $user ? 'false' : 'true' ?>;
        
        // Only validate if password is being set
        if (password.length > 0 || isNewUser) {
            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match. Please check and try again.');
                confirmInput.focus();
                return false;
            }
            
            if (password.length > 0 && password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long.');
                passwordInput.focus();
                return false;
            }
        }
    });
});
</script>
