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

// Redirect if already logged in
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .login-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .login-card {
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            border-radius: 14px;
            margin-bottom: 1.25rem;
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.25);
        }
        
        .login-logo svg {
            width: 28px;
            height: 28px;
            color: #fff;
        }
        
        .login-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.025em;
            margin-bottom: 0.25rem;
        }
        
        .login-header p {
            color: #64748b;
            font-size: 0.9375rem;
        }
        
        .login-form {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.04), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.5rem;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 0.9375rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            transition: all 0.15s;
            background: #fff;
            font-family: inherit;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }
        
        .form-input::placeholder {
            color: #94a3b8;
        }
        
        .error-message {
            background: #fef2f2;
            color: #991b1b;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 1.25rem;
            border: 1px solid #fecaca;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .error-message svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }
        
        .btn-login {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 0.9375rem;
            font-weight: 600;
            color: #fff;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
            font-family: inherit;
        }
        
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(99, 102, 241, 0.35);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.8125rem;
            color: #64748b;
        }
        
        .login-footer a {
            color: #6366f1;
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        .page-footer {
            padding: 1.5rem;
            text-align: center;
            color: #94a3b8;
            font-size: 0.8125rem;
        }
        
        .page-footer a {
            color: #64748b;
            text-decoration: none;
        }
        
        .page-footer a:hover {
            color: #6366f1;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                        <polyline points="2 17 12 22 22 17"></polyline>
                        <polyline points="2 12 12 17 22 12"></polyline>
                    </svg>
                </div>
                <h1>Welcome back</h1>
                <p>Sign in to <?= esc(CMS_NAME) ?></p>
            </div>
            
            <div class="login-form">
                <?php if ($error): ?>
                    <div class="error-message">
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
                        <input type="text" id="username" name="username" class="form-input" 
                               placeholder="Enter your username" required autofocus>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-input" 
                               placeholder="Enter your password" required>
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
