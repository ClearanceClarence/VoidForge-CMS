<?php
/**
 * Admin Login - Forge CMS
 */

define('CMS_ROOT', dirname(__DIR__));
require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/user.php';

User::startSession();

if (User::isLoggedIn()) {
    redirect(ADMIN_URL . '/');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (User::login($username, $password)) {
        $redirect = $_GET['redirect'] ?? ADMIN_URL . '/';
        redirect($redirect);
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - <?= CMS_NAME ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= ADMIN_URL ?>/assets/img/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= ADMIN_URL ?>/assets/css/login.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <svg viewBox="0 0 48 48" fill="none">
                        <path d="M14 12h20v5h-13v6h10v5h-10v10h-7V12z" fill="white" opacity="0.95"/>
                    </svg>
                </div>
                <h1>Welcome back</h1>
                <p>Sign in to <?= esc(CMS_NAME) ?></p>
            </div>
            
            <div class="login-form">
                <?php if ($error): ?>
                    <div class="login-error">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <?= esc($error) ?>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <div class="form-group">
                        <label for="username" class="form-label">Username or Email</label>
                        <input type="text" id="username" name="username" class="form-input" placeholder="Enter your username" required autofocus>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-input" placeholder="Enter your password" required>
                    </div>

                    <button type="submit" class="btn-login">Sign In</button>
                </form>
            </div>
            
            <div class="login-footer">
                <a href="<?= SITE_URL ?>">← Back to site</a>
            </div>
        </div>
    </div>
    
    <div class="page-footer">
        <a href="<?= SITE_URL ?>"><?= esc(CMS_NAME) ?></a> v<?= CMS_VERSION ?>
    </div>
</body>
</html>
