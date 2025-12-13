<?php
/**
 * Nova Theme - 404 Error Page
 */

defined('CMS_ROOT') or die;

http_response_code(404);

$siteTitle = getOption('site_title', 'VoidForge');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Page Not Found — <?= esc($siteTitle) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= SITE_URL ?>/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --void: #05030a;
            --nova-purple: #a855f7;
            --nova-cyan: #06b6d4;
            --text: #f8fafc;
            --text-secondary: #94a3b8;
            --glass-border: rgba(255, 255, 255, 0.08);
        }
        
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--void);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .cosmic-bg {
            position: fixed;
            inset: 0;
            pointer-events: none;
        }
        
        .nebula {
            position: absolute;
            border-radius: 50%;
            filter: blur(120px);
            opacity: 0.4;
        }
        
        .nebula-1 {
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(168, 85, 247, 0.4) 0%, transparent 70%);
            top: -200px;
            right: -100px;
        }
        
        .nebula-2 {
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(6, 182, 212, 0.4) 0%, transparent 70%);
            bottom: -150px;
            left: -100px;
        }
        
        .error-container {
            position: relative;
            z-index: 1;
            text-align: center;
            padding: 2rem;
            animation: fadeIn 0.6s ease-out;
        }
        
        .error-code {
            font-family: 'Sora', sans-serif;
            font-size: clamp(8rem, 25vw, 14rem);
            font-weight: 800;
            line-height: 1;
            background: linear-gradient(135deg, var(--nova-purple), var(--nova-cyan));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }
        
        .error-title {
            font-family: 'Sora', sans-serif;
            font-size: clamp(1.5rem, 4vw, 2rem);
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .error-message {
            color: var(--text-secondary);
            font-size: 1.125rem;
            max-width: 400px;
            margin: 0 auto 2rem;
        }
        
        .error-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 1.75rem;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--nova-purple), #6366f1);
            color: white;
            box-shadow: 0 4px 20px rgba(168, 85, 247, 0.4);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(168, 85, 247, 0.5);
        }
        
        .btn-ghost {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--glass-border);
            color: var(--text);
        }
        
        .btn-ghost:hover {
            background: rgba(255,255,255,0.08);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="cosmic-bg">
        <div class="nebula nebula-1"></div>
        <div class="nebula nebula-2"></div>
    </div>
    
    <div class="error-container">
        <div class="error-code">404</div>
        <h1 class="error-title">Lost in the Void</h1>
        <p class="error-message">The page you're looking for has drifted into the cosmic unknown. Let's get you back on track.</p>
        <div class="error-actions">
            <a href="<?= SITE_URL ?>" class="btn btn-primary">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                Go Home
            </a>
            <a href="javascript:history.back()" class="btn btn-ghost">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Go Back
            </a>
        </div>
    </div>
</body>
</html>
