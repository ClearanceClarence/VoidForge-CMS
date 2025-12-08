<?php
/**
 * 404 Not Found Template - VoidForge CMS
 * Light mode with search functionality
 */

$siteTitle = getOption('site_title', 'VoidForge CMS');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found — <?= esc($siteTitle) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= SITE_URL ?>/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --bg: #fafbfc;
            --surface: #ffffff;
            --text: #0f172a;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --accent: #6366f1;
            --accent-2: #8b5cf6;
            --accent-3: #06b6d4;
            --border: #e2e8f0;
        }
        
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }
        
        /* Background */
        .bg-decoration {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
        }
        
        .bg-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.4;
        }
        
        .bg-orb-1 {
            width: 500px;
            height: 500px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(139, 92, 246, 0.15));
            top: -150px;
            left: 50%;
            transform: translateX(-50%);
        }
        
        .bg-orb-2 {
            width: 400px;
            height: 400px;
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.15), rgba(99, 102, 241, 0.1));
            bottom: -100px;
            right: -100px;
        }
        
        /* Main Content */
        main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            z-index: 1;
        }
        
        .error-container {
            text-align: center;
            max-width: 580px;
            animation: fadeIn 0.6s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* 404 Number */
        .error-code {
            font-family: 'Outfit', sans-serif;
            font-size: clamp(6rem, 20vw, 12rem);
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.05em;
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-2) 50%, var(--accent-3) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .error-code::after {
            content: '404';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(6, 182, 212, 0.1));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            transform: translate(4px, 4px);
            z-index: -1;
        }
        
        .error-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 0.75rem;
        }
        
        .error-message {
            font-size: 1.0625rem;
            color: var(--text-secondary);
            margin-bottom: 2.5rem;
            line-height: 1.6;
        }
        
        /* Search Box */
        .search-box {
            margin-bottom: 2rem;
        }
        
        .search-form {
            display: flex;
            gap: 0.75rem;
            max-width: 420px;
            margin: 0 auto;
        }
        
        .search-input-wrap {
            flex: 1;
            position: relative;
        }
        
        .search-input-wrap svg {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: var(--text-muted);
            pointer-events: none;
        }
        
        .search-input {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 2.75rem;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 1rem;
            font-family: inherit;
            color: var(--text);
            background: var(--surface);
            transition: all 0.2s;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }
        
        .search-input::placeholder {
            color: var(--text-muted);
        }
        
        .search-btn {
            padding: 0.875rem 1.5rem;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 0.9375rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.25);
        }
        
        .search-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(99, 102, 241, 0.35);
        }
        
        /* Action Buttons */
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
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-size: 0.9375rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            font-family: inherit;
        }
        
        .btn svg {
            width: 18px;
            height: 18px;
        }
        
        .btn-home {
            background: var(--surface);
            color: var(--text);
            border: 2px solid var(--border);
        }
        
        .btn-home:hover {
            border-color: var(--accent);
            color: var(--accent);
        }
        
        .btn-back {
            background: transparent;
            color: var(--text-secondary);
        }
        
        .btn-back:hover {
            color: var(--text);
        }
        
        /* Footer */
        footer {
            padding: 1.5rem 2rem;
            text-align: center;
            border-top: 1px solid var(--border);
            background: var(--surface);
            position: relative;
            z-index: 1;
        }
        
        .footer-brand {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        .footer-brand svg {
            width: 20px;
            height: 20px;
        }
        
        .footer-brand a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
        }
        
        .footer-brand a:hover {
            text-decoration: underline;
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            .search-form {
                flex-direction: column;
            }
            
            .error-actions {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <div class="bg-decoration">
        <div class="bg-orb bg-orb-1"></div>
        <div class="bg-orb bg-orb-2"></div>
    </div>
    
    <main>
        <div class="error-container">
            <div class="error-code">404</div>
            <h1 class="error-title">Page Not Found</h1>
            <p class="error-message">The page you're looking for doesn't exist or has been moved. Try searching or return to the homepage.</p>
            
            <div class="search-box">
                <form action="<?= SITE_URL ?>/" method="get" class="search-form">
                    <div class="search-input-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="text" name="s" class="search-input" placeholder="Search for content..." autocomplete="off">
                    </div>
                    <button type="submit" class="search-btn">Search</button>
                </form>
            </div>
            
            <div class="error-actions">
                <a href="<?= SITE_URL ?>" class="btn btn-home">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    Go to Homepage
                </a>
                <button onclick="history.back()" class="btn btn-back">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Go Back
                </button>
            </div>
        </div>
    </main>
    
    <footer>
        <div class="footer-brand">
            <svg viewBox="0 0 32 32" fill="none">
                <defs>
                    <linearGradient id="fg" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#8b5cf6"/>
                        <stop offset="100%" style="stop-color:#06b6d4"/>
                    </linearGradient>
                </defs>
                <path d="M5 5 L16 27 L27 5" fill="none" stroke="url(#fg)" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="16" cy="14" r="3" fill="url(#fg)"/>
            </svg>
            Powered by <a href="https://github.com/voidforge/cms">VoidForge CMS</a>
        </div>
    </footer>
</body>
</html>
