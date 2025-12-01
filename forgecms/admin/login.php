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
    <title>Login - <?= CMS_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .login-box {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.4);
            width: 100%;
            max-width: 420px;
            padding: 2.5rem;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-logo {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.25rem;
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }
        .login-logo svg {
            width: 32px;
            height: 32px;
            color: #fff;
        }
        .login-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.025em;
        }
        .login-header p {
            color: #64748b;
            font-size: 0.9375rem;
            margin-top: 0.375rem;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.5rem;
        }
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            transition: all 0.15s ease;
            background: #f8fafc;
        }
        .form-input:focus {
            outline: none;
            border-color: #6366f1;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }
        .error {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            padding: 0.875rem 1rem;
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
        }
        .btn {
            width: 100%;
            padding: 0.875rem;
            font-size: 1rem;
            font-weight: 600;
            color: #fff;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.15s ease;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.35);
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
        }
        .btn:active {
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="login-header">
            <div class="login-logo">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                    <polyline points="2 17 12 22 22 17"></polyline>
                    <polyline points="2 12 12 17 22 12"></polyline>
                </svg>
            </div>
            <h1><?= CMS_NAME ?></h1>
            <p>Sign in to your account</p>
        </div>

        <?php if ($error): ?>
            <div class="error"><?= esc($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="username" class="form-label">Username or Email</label>
                <input type="text" id="username" name="username" class="form-input" required autofocus>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-input" required>
            </div>

            <button type="submit" class="btn">Sign In</button>
        </form>
    </div>
</body>
</html>
