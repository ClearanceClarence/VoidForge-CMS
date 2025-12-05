<?php
/**
 * Admin Login - Forge CMS v1.0.7
 * Light mode design
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

$siteName = CMS_NAME;
try {
    $siteName = getOption('site_name', CMS_NAME);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - <?= esc($siteName) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= ADMIN_URL ?>/assets/img/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --accent: #a855f7;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .bg-decoration {
            position: fixed;
            inset: 0;
            z-index: 0;
            overflow: hidden;
        }
        
        .bg-decoration::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(ellipse at 0% 0%, rgba(99, 102, 241, 0.08) 0%, transparent 50%),
                radial-gradient(ellipse at 100% 100%, rgba(168, 85, 247, 0.08) 0%, transparent 50%);
        }
        
        .shape {
            position: absolute;
            border-radius: 50%;
            opacity: 0.5;
        }
        
        .shape-1 {
            width: 600px;
            height: 600px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(168, 85, 247, 0.05) 100%);
            top: -200px;
            right: -200px;
            animation: float 20s ease-in-out infinite;
        }
        
        .shape-2 {
            width: 400px;
            height: 400px;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, rgba(99, 102, 241, 0.05) 100%);
            bottom: -150px;
            left: -150px;
            animation: float 25s ease-in-out infinite reverse;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(30px, -30px) scale(1.05); }
        }
        
        .grid-pattern {
            position: fixed;
            inset: 0;
            background-image: 
                linear-gradient(rgba(99, 102, 241, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(99, 102, 241, 0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            z-index: 1;
        }
        
        .login-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 440px;
            padding: 2rem;
        }
        
        .login-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            padding: 3rem;
            box-shadow: 
                0 1px 3px rgba(0, 0, 0, 0.04),
                0 6px 16px rgba(0, 0, 0, 0.04),
                0 24px 48px rgba(0, 0, 0, 0.06);
            animation: cardSlide 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }
        
        @keyframes cardSlide {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .logo-wrapper {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        
        .logo {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 32px rgba(99, 102, 241, 0.3);
        }
        
        .logo svg {
            width: 36px;
            height: 36px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.02em;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: #94a3b8;
            font-size: 0.9375rem;
        }
        
        .error-alert {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            animation: shake 0.4s ease;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .error-alert svg {
            width: 20px;
            height: 20px;
            color: #dc2626;
            flex-shrink: 0;
        }
        
        .error-alert span {
            font-size: 0.875rem;
            color: #dc2626;
            font-weight: 500;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }
        
        .input-group {
            position: relative;
        }
        
        .form-input {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 3rem;
            font-size: 0.9375rem;
            font-family: inherit;
            color: #0f172a;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            outline: none;
            transition: all 0.2s;
        }
        
        .form-input:hover {
            border-color: #cbd5e1;
        }
        
        .form-input:focus {
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }
        
        .form-input::placeholder {
            color: #94a3b8;
        }
        
        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            color: #94a3b8;
            pointer-events: none;
            transition: color 0.2s;
        }
        
        .form-input:focus + .input-icon {
            color: var(--primary);
        }
        
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #94a3b8;
            padding: 0.25rem;
            display: flex;
            transition: color 0.2s;
        }
        
        .password-toggle:hover {
            color: #475569;
        }
        
        .password-toggle svg {
            width: 20px;
            height: 20px;
        }
        
        .btn-submit {
            width: 100%;
            padding: 1rem 1.5rem;
            margin-top: 0.5rem;
            font-size: 0.9375rem;
            font-weight: 600;
            font-family: inherit;
            color: #fff;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
        }
        
        .btn-submit:active {
            transform: translateY(0);
        }
        
        .btn-submit svg {
            width: 18px;
            height: 18px;
            transition: transform 0.2s;
        }
        
        .btn-submit:hover svg {
            transform: translateX(4px);
        }
        
        .login-footer {
            margin-top: 2rem;
            text-align: center;
        }
        
        .login-footer a {
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.2s;
        }
        
        .login-footer a:hover {
            color: var(--primary);
        }
        
        .login-footer a svg {
            width: 16px;
            height: 16px;
            transition: transform 0.2s;
        }
        
        .login-footer a:hover svg {
            transform: translateX(-3px);
        }
        
        .page-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1.5rem;
            text-align: center;
            z-index: 10;
        }
        
        .page-footer span {
            font-size: 0.75rem;
            color: #94a3b8;
        }
        
        @media (max-width: 480px) {
            .login-container { padding: 1rem; }
            .login-card { padding: 2rem 1.5rem; border-radius: 20px; }
            .logo { width: 60px; height: 60px; border-radius: 16px; }
            .logo svg { width: 30px; height: 30px; }
            .login-header h1 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="bg-decoration">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
    </div>
    <div class="grid-pattern"></div>
    
    <div class="login-container">
        <div class="login-card">
            <div class="logo-wrapper">
                <div class="logo">
                    <svg viewBox="0 0 48 48" fill="none">
                        <path d="M14 12h20v5h-13v6h10v5h-10v10h-7V12z" fill="white"/>
                    </svg>
                </div>
            </div>
            
            <div class="login-header">
                <h1>Welcome back</h1>
                <p>Sign in to <?= esc($siteName) ?></p>
            </div>
            
            <?php if ($error): ?>
            <div class="error-alert">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span><?= esc($error) ?></span>
            </div>
            <?php endif; ?>
            
            <form method="post">
                <div class="form-group">
                    <label for="username" class="form-label">Username or Email</label>
                    <div class="input-group">
                        <input type="text" id="username" name="username" class="form-input" 
                               placeholder="Enter your username" required autofocus
                               value="<?= esc($_POST['username'] ?? '') ?>">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" id="password" name="password" class="form-input" 
                               placeholder="Enter your password" required style="padding-right: 3rem;">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <svg id="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit">
                    Sign In
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </button>
            </form>
            
            <div class="login-footer">
                <a href="<?= SITE_URL ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Back to site
                </a>
            </div>
        </div>
    </div>
    
    <div class="page-footer">
        <span>Powered by Forge CMS</span>
    </div>
    
    <script>
        function togglePassword() {
            var input = document.getElementById('password');
            var icon = document.getElementById('eye-icon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
            } else {
                input.type = 'password';
                icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
            }
        }
    </script>
</body>
</html>
