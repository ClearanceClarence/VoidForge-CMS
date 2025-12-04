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

// Get site name for display
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
            --bg-dark: #0f0f23;
            --bg-card: rgba(255, 255, 255, 0.03);
            --border: rgba(255, 255, 255, 0.08);
            --text: #ffffff;
            --text-muted: rgba(255, 255, 255, 0.5);
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }
        
        /* Animated background */
        .bg-gradient {
            position: fixed;
            inset: 0;
            z-index: 0;
            overflow: hidden;
        }
        
        .bg-gradient::before {
            content: '';
            position: absolute;
            width: 150%;
            height: 150%;
            top: -25%;
            left: -25%;
            background: 
                radial-gradient(ellipse at 20% 20%, rgba(99, 102, 241, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 80%, rgba(168, 85, 247, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 40% 60%, rgba(59, 130, 246, 0.1) 0%, transparent 40%);
            animation: bgShift 20s ease-in-out infinite;
        }
        
        @keyframes bgShift {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(2%, 2%) rotate(1deg); }
            66% { transform: translate(-1%, 1%) rotate(-1deg); }
        }
        
        /* Floating orbs */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.5;
            animation: float 15s ease-in-out infinite;
        }
        
        .orb-1 {
            width: 400px;
            height: 400px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            top: -10%;
            right: -5%;
            animation-delay: 0s;
        }
        
        .orb-2 {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, #3b82f6, var(--primary));
            bottom: -10%;
            left: -5%;
            animation-delay: -5s;
        }
        
        .orb-3 {
            width: 200px;
            height: 200px;
            background: linear-gradient(135deg, var(--accent), #ec4899);
            top: 50%;
            left: 50%;
            animation-delay: -10s;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(20px, -20px) scale(1.05); }
            50% { transform: translate(-10px, 10px) scale(0.95); }
            75% { transform: translate(15px, 15px) scale(1.02); }
        }
        
        /* Grid overlay */
        .grid-overlay {
            position: fixed;
            inset: 0;
            background-image: 
                linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
            background-size: 60px 60px;
            z-index: 1;
        }
        
        /* Main container */
        .login-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 420px;
            padding: 2rem;
        }
        
        /* Glass card */
        .login-card {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 3rem 2.5rem;
            box-shadow: 
                0 25px 50px -12px rgba(0, 0, 0, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.05);
            animation: cardEnter 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
        
        @keyframes cardEnter {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.96);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        /* Logo */
        .logo-container {
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
            box-shadow: 
                0 10px 40px rgba(99, 102, 241, 0.4),
                0 0 0 1px rgba(255, 255, 255, 0.1);
            position: relative;
            animation: logoPulse 3s ease-in-out infinite;
        }
        
        @keyframes logoPulse {
            0%, 100% { box-shadow: 0 10px 40px rgba(99, 102, 241, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.1); }
            50% { box-shadow: 0 15px 50px rgba(99, 102, 241, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.15); }
        }
        
        .logo svg {
            width: 36px;
            height: 36px;
        }
        
        /* Header text */
        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        
        .login-header h1 {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--text);
            letter-spacing: -0.02em;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: var(--text-muted);
            font-size: 0.9375rem;
            font-weight: 400;
        }
        
        /* Form */
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 0.5rem;
            letter-spacing: 0.01em;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper .icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: var(--text-muted);
            pointer-events: none;
            transition: color 0.2s;
        }
        
        .form-input {
            width: 100%;
            padding: 0.9375rem 1rem 0.9375rem 2.75rem;
            font-size: 0.9375rem;
            font-family: inherit;
            color: var(--text);
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border);
            border-radius: 12px;
            outline: none;
            transition: all 0.2s;
        }
        
        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }
        
        .form-input:hover {
            border-color: rgba(255, 255, 255, 0.15);
            background: rgba(255, 255, 255, 0.06);
        }
        
        .form-input:focus {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.08);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }
        
        .form-input:focus + .icon,
        .input-wrapper:focus-within .icon {
            color: var(--primary);
        }
        
        /* Password toggle */
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s;
        }
        
        .password-toggle:hover {
            color: var(--text);
        }
        
        .password-toggle svg {
            width: 18px;
            height: 18px;
        }
        
        /* Error message */
        .error-message {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 12px;
            margin-bottom: 1.5rem;
            animation: shake 0.5s cubic-bezier(0.36, 0.07, 0.19, 0.97);
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-4px); }
            20%, 40%, 60%, 80% { transform: translateX(4px); }
        }
        
        .error-message svg {
            width: 20px;
            height: 20px;
            color: #f87171;
            flex-shrink: 0;
        }
        
        .error-message span {
            font-size: 0.875rem;
            color: #fca5a5;
            font-weight: 500;
        }
        
        /* Submit button */
        .btn-submit {
            width: 100%;
            padding: 1rem;
            font-size: 0.9375rem;
            font-weight: 600;
            font-family: inherit;
            color: white;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            border-radius: 12px;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: all 0.3s;
            box-shadow: 0 4px 20px rgba(99, 102, 241, 0.4);
        }
        
        .btn-submit::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, var(--accent) 0%, var(--primary) 100%);
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(99, 102, 241, 0.5);
        }
        
        .btn-submit:hover::before {
            opacity: 1;
        }
        
        .btn-submit:active {
            transform: translateY(0);
        }
        
        .btn-submit span {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-submit .arrow {
            transition: transform 0.3s;
        }
        
        .btn-submit:hover .arrow {
            transform: translateX(4px);
        }
        
        /* Footer */
        .login-footer {
            margin-top: 2rem;
            text-align: center;
        }
        
        .login-footer a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: color 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .login-footer a:hover {
            color: var(--text);
        }
        
        .login-footer a svg {
            width: 16px;
            height: 16px;
            transition: transform 0.2s;
        }
        
        .login-footer a:hover svg {
            transform: translateX(-3px);
        }
        
        /* Page footer */
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
            color: rgba(255, 255, 255, 0.3);
            font-weight: 400;
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            .login-wrapper {
                padding: 1rem;
            }
            
            .login-card {
                padding: 2rem 1.5rem;
                border-radius: 20px;
            }
            
            .logo {
                width: 60px;
                height: 60px;
                border-radius: 16px;
            }
            
            .logo svg {
                width: 30px;
                height: 30px;
            }
            
            .login-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Background effects -->
    <div class="bg-gradient">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>
    <div class="grid-overlay"></div>
    
    <!-- Login form -->
    <div class="login-wrapper">
        <div class="login-card">
            <div class="logo-container">
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
            <div class="error-message">
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
                    <div class="input-wrapper">
                        <input type="text" id="username" name="username" class="form-input" 
                               placeholder="Enter your username" required autofocus
                               value="<?= esc($_POST['username'] ?? '') ?>">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" class="form-input" 
                               placeholder="Enter your password" required>
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
                    <span>
                        Sign In
                        <svg class="arrow" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </span>
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
        <span><?= esc($siteName) ?> · Powered by Forge CMS</span>
    </div>
    
    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('eye-icon');
            
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
