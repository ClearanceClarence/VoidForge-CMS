<?php
/**
 * Homepage Template - VoidForge CMS
 * Beautiful light mode landing page
 */

defined('CMS_ROOT') or die;

$siteTitle = getOption('site_title', 'VoidForge CMS');
$siteDescription = getOption('site_description', '');

// $post is already set from index.php
$pageTitle = $post['title'] ?? $siteTitle;
$pageContent = $post['content'] ?? '';

// Process shortcodes/tags in content
$pageContent = Plugin::processContent($pageContent);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle) ?> — <?= esc($siteTitle) ?></title>
    <meta name="description" content="<?= esc($siteDescription) ?>">
    <link rel="icon" type="image/svg+xml" href="<?= SITE_URL ?>/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <?php
    $frontendCss = getOption('frontend_custom_css', '');
    if (!empty($frontendCss)): ?>
    <style><?= $frontendCss ?></style>
    <?php endif; ?>
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --bg-primary: #fafbfc;
            --bg-secondary: #ffffff;
            --bg-accent: #f0f4ff;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #94a3b8;
            --accent-1: #6366f1;
            --accent-2: #8b5cf6;
            --accent-3: #06b6d4;
            --border: #e2e8f0;
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.04);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.06);
            --shadow-lg: 0 12px 40px rgba(0,0,0,0.08);
            --radius-sm: 8px;
            --radius-md: 16px;
            --radius-lg: 24px;
        }
        
        html { scroll-behavior: smooth; }
        
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.7;
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        /* Background decoration */
        .bg-decoration {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }
        
        .bg-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.5;
        }
        
        .bg-orb-1 {
            width: 600px;
            height: 600px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(139, 92, 246, 0.1));
            top: -200px;
            right: -100px;
        }
        
        .bg-orb-2 {
            width: 500px;
            height: 500px;
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.12), rgba(99, 102, 241, 0.08));
            bottom: -150px;
            left: -100px;
        }
        
        .bg-grid {
            position: absolute;
            inset: 0;
            background-image: 
                linear-gradient(rgba(99, 102, 241, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(99, 102, 241, 0.03) 1px, transparent 1px);
            background-size: 60px 60px;
        }
        
        /* Header */
        .site-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            padding: 1rem 2rem;
            background: rgba(250, 251, 252, 0.85);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
        }
        
        .header-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .site-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: var(--text-primary);
        }
        
        .site-logo svg {
            width: 36px;
            height: 36px;
        }
        
        .site-logo-text {
            font-family: 'Outfit', sans-serif;
            font-size: 1.375rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        
        .site-logo-text span {
            background: linear-gradient(135deg, var(--accent-1), var(--accent-3));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .header-nav {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .header-nav a {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9375rem;
            transition: color 0.2s;
        }
        
        .header-nav a:hover {
            color: var(--text-primary);
        }
        
        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            color: white;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 0.9375rem;
            text-decoration: none;
            transition: all 0.2s;
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.25);
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(99, 102, 241, 0.35);
        }
        
        /* Main Content */
        main {
            position: relative;
            z-index: 1;
            padding-top: 5rem;
        }
        
        .page-content {
            max-width: 900px;
            margin: 0 auto;
            padding: 4rem 2rem;
        }
        
        /* Typography */
        .page-content h1 {
            font-family: 'Outfit', sans-serif;
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -0.03em;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
        }
        
        .page-content h1 .gradient-text {
            background: linear-gradient(135deg, var(--accent-1) 0%, var(--accent-2) 50%, var(--accent-3) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .page-content h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin: 3rem 0 1.25rem;
            color: var(--text-primary);
        }
        
        .page-content h3 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.375rem;
            font-weight: 600;
            margin: 2.5rem 0 1rem;
        }
        
        .page-content p {
            margin-bottom: 1.5rem;
            color: var(--text-secondary);
            font-size: 1.125rem;
        }
        
        .page-content a {
            color: var(--accent-1);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        
        .page-content a:hover {
            color: var(--accent-2);
            text-decoration: underline;
        }
        
        .page-content ul, .page-content ol {
            margin: 1rem 0 2rem 0;
            padding-left: 0;
            list-style: none;
        }
        
        .page-content li {
            position: relative;
            padding-left: 2rem;
            margin-bottom: 0.875rem;
            color: var(--text-secondary);
            font-size: 1.0625rem;
        }
        
        .page-content li::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0.625rem;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-1), var(--accent-3));
        }
        
        .page-content li strong {
            color: var(--text-primary);
            font-weight: 600;
        }
        
        .page-content hr {
            border: none;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--border), transparent);
            margin: 3rem 0;
        }
        
        .page-content blockquote {
            border-left: 4px solid var(--accent-1);
            padding: 1.25rem 1.5rem;
            margin: 2rem 0;
            background: var(--bg-accent);
            border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
            color: var(--text-secondary);
            font-style: italic;
        }
        
        .page-content code {
            background: var(--bg-accent);
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.9em;
            color: var(--accent-1);
        }
        
        .page-content pre {
            background: var(--text-primary);
            color: #e2e8f0;
            border-radius: var(--radius-md);
            padding: 1.5rem;
            overflow-x: auto;
            margin: 2rem 0;
        }
        
        .page-content pre code {
            background: none;
            color: inherit;
            padding: 0;
        }
        
        .page-content img {
            max-width: 100%;
            height: auto;
            border-radius: var(--radius-md);
            margin: 2rem 0;
            box-shadow: var(--shadow-lg);
        }
        
        /* Feature cards in content */
        .page-content .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        
        .page-content .feature-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 1.75rem;
            transition: all 0.3s;
        }
        
        .page-content .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: rgba(99, 102, 241, 0.3);
        }
        
        /* Footer */
        .site-footer {
            position: relative;
            z-index: 1;
            border-top: 1px solid var(--border);
            padding: 2rem;
            text-align: center;
            background: var(--bg-secondary);
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .footer-brand {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .footer-brand svg {
            width: 24px;
            height: 24px;
        }
        
        .footer-links {
            display: flex;
            gap: 1.5rem;
        }
        
        .footer-links a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.2s;
        }
        
        .footer-links a:hover {
            color: var(--accent-1);
        }
        
        .footer-copy {
            color: var(--text-muted);
            font-size: 0.875rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header-nav { display: none; }
            .page-content { padding: 3rem 1.5rem; }
            .page-content h1 { font-size: 2.25rem; }
            .footer-content { flex-direction: column; text-align: center; }
        }
        
        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .page-content {
            animation: fadeInUp 0.6s ease-out;
        }
    </style>
</head>
<body>
    <div class="bg-decoration">
        <div class="bg-grid"></div>
        <div class="bg-orb bg-orb-1"></div>
        <div class="bg-orb bg-orb-2"></div>
    </div>
    
    <header class="site-header">
        <div class="header-inner">
            <a href="<?= SITE_URL ?>" class="site-logo">
                <svg viewBox="0 0 32 32" fill="none">
                    <defs>
                        <linearGradient id="logoGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#8b5cf6"/>
                            <stop offset="100%" style="stop-color:#06b6d4"/>
                        </linearGradient>
                        <linearGradient id="innerGlow" x1="50%" y1="0%" x2="50%" y2="100%">
                            <stop offset="0%" style="stop-color:#c4b5fd"/>
                            <stop offset="100%" style="stop-color:#8b5cf6"/>
                        </linearGradient>
                    </defs>
                    <path d="M5 5 L16 27 L27 5" fill="none" stroke="url(#logoGrad)" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="16" cy="14" r="3.5" fill="url(#innerGlow)"/>
                    <circle cx="16" cy="14" r="1.5" fill="#fff"/>
                </svg>
                <span class="site-logo-text">Void<span>Forge</span></span>
            </a>
            <nav class="header-nav">
                <a href="<?= ADMIN_URL ?>/" class="btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    Dashboard
                </a>
            </nav>
        </div>
    </header>
    
    <main>
        <div class="page-content">
            <?= $pageContent ?>
        </div>
    </main>
    
    <footer class="site-footer">
        <div class="footer-content">
            <div class="footer-brand">
                <svg viewBox="0 0 32 32" fill="none">
                    <defs>
                        <linearGradient id="footerGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#8b5cf6"/>
                            <stop offset="100%" style="stop-color:#06b6d4"/>
                        </linearGradient>
                    </defs>
                    <path d="M5 5 L16 27 L27 5" fill="none" stroke="url(#footerGrad)" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="16" cy="14" r="3" fill="url(#footerGrad)"/>
                </svg>
                VoidForge CMS
            </div>
            <div class="footer-links">
                <a href="<?= ADMIN_URL ?>/">Dashboard</a>
                <a href="https://github.com/voidforge/cms" target="_blank">GitHub</a>
            </div>
            <div class="footer-copy">
                © <?= date('Y') ?> <?= esc($siteTitle) ?>
            </div>
        </div>
    </footer>
    
    <?php do_action('frontend_footer'); ?>
</body>
</html>
