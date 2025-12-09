<?php
/**
 * VoidForge CMS - Landing Page
 * Shown when no homepage is set
 */

defined('CMS_ROOT') or die;

$siteTitle = getOption('site_title', 'VoidForge CMS');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($siteTitle) ?> — Modern Content Management</title>
    <link rel="icon" type="image/svg+xml" href="<?= SITE_URL ?>/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --bg: #030712;
            --bg-subtle: #0a0f1a;
            --bg-card: rgba(255,255,255,0.03);
            --bg-card-hover: rgba(255,255,255,0.06);
            --text: #f8fafc;
            --text-muted: #94a3b8;
            --text-dim: #64748b;
            --border: rgba(255,255,255,0.08);
            --primary: #6366f1;
            --primary-light: #818cf8;
            --secondary: #8b5cf6;
            --accent: #06b6d4;
            --success: #10b981;
            --glow: rgba(99, 102, 241, 0.5);
        }
        
        html { scroll-behavior: smooth; }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        /* Animated Background */
        .bg-grid {
            position: fixed;
            inset: 0;
            background-image: 
                linear-gradient(rgba(99, 102, 241, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(99, 102, 241, 0.03) 1px, transparent 1px);
            background-size: 60px 60px;
            mask-image: radial-gradient(ellipse at center, black 0%, transparent 70%);
            pointer-events: none;
        }
        
        .bg-glow {
            position: fixed;
            width: 800px;
            height: 800px;
            border-radius: 50%;
            filter: blur(120px);
            opacity: 0.15;
            pointer-events: none;
        }
        
        .bg-glow-1 {
            top: -400px;
            left: -200px;
            background: var(--primary);
        }
        
        .bg-glow-2 {
            bottom: -400px;
            right: -200px;
            background: var(--secondary);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            position: relative;
            z-index: 1;
        }
        
        /* Navigation */
        .nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            padding: 1rem 0;
            background: rgba(3, 7, 18, 0.8);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
        }
        
        .nav-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .nav-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: var(--text);
            font-weight: 700;
            font-size: 1.25rem;
        }
        
        .nav-logo svg {
            width: 36px;
            height: 36px;
        }
        
        .nav-links {
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        
        .nav-link {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9375rem;
            font-weight: 500;
            transition: color 0.2s;
        }
        
        .nav-link:hover { color: var(--text); }
        
        .nav-cta {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            background: var(--primary);
            color: #fff;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .nav-cta:hover {
            background: var(--primary-light);
            transform: translateY(-1px);
        }
        
        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 8rem 0 6rem;
            text-align: center;
        }
        
        .hero-content {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 100px;
            font-size: 0.8125rem;
            color: var(--text-muted);
            margin-bottom: 2rem;
        }
        
        .hero-badge-dot {
            width: 8px;
            height: 8px;
            background: var(--success);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .hero h1 {
            font-size: clamp(2.5rem, 8vw, 4.5rem);
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            letter-spacing: -0.03em;
        }
        
        .hero-gradient {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 50%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hero p {
            font-size: 1.25rem;
            color: var(--text-muted);
            max-width: 600px;
            margin: 0 auto 3rem;
            line-height: 1.7;
        }
        
        .hero-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.625rem;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        
        .btn svg {
            width: 20px;
            height: 20px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            box-shadow: 0 8px 30px var(--glow);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px var(--glow);
        }
        
        .btn-secondary {
            background: var(--bg-card);
            color: var(--text);
            border: 1px solid var(--border);
        }
        
        .btn-secondary:hover {
            background: var(--bg-card-hover);
            border-color: rgba(255,255,255,0.15);
        }
        
        /* Stats Bar */
        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
            padding: 4rem 0;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            margin: 4rem 0;
        }
        
        .stat {
            text-align: center;
        }
        
        .stat-value {
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--text) 0%, var(--text-muted) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.9375rem;
            color: var(--text-dim);
        }
        
        /* Features Section */
        .features {
            padding: 6rem 0;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }
        
        .section-label {
            display: inline-block;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: var(--primary-light);
            margin-bottom: 1rem;
        }
        
        .section-title {
            font-size: clamp(2rem, 5vw, 3rem);
            font-weight: 800;
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
        }
        
        .section-desc {
            font-size: 1.125rem;
            color: var(--text-muted);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }
        
        .feature-card {
            padding: 2rem;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 20px;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            background: var(--bg-card-hover);
            border-color: rgba(255,255,255,0.12);
            transform: translateY(-4px);
        }
        
        .feature-icon {
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            margin-bottom: 1.5rem;
        }
        
        .feature-icon svg {
            width: 28px;
            height: 28px;
        }
        
        .feature-card h3 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }
        
        .feature-card p {
            color: var(--text-muted);
            font-size: 0.9375rem;
            line-height: 1.7;
        }
        
        /* Bento Grid */
        .bento {
            padding: 6rem 0;
        }
        
        .bento-grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 1.5rem;
        }
        
        .bento-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
        }
        
        .bento-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(99,102,241,0.1) 0%, transparent 50%);
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .bento-card:hover::before { opacity: 1; }
        
        .bento-large { grid-column: span 8; }
        .bento-small { grid-column: span 4; }
        .bento-half { grid-column: span 6; }
        
        .bento-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            position: relative;
        }
        
        .bento-card p {
            color: var(--text-muted);
            line-height: 1.7;
            position: relative;
        }
        
        .bento-visual {
            margin-top: 2rem;
            background: var(--bg-subtle);
            border-radius: 12px;
            padding: 1.5rem;
            font-family: 'SF Mono', Monaco, monospace;
            font-size: 0.8125rem;
            color: var(--text-muted);
            position: relative;
            overflow: hidden;
        }
        
        .bento-visual code {
            color: var(--primary-light);
        }
        
        /* Code Preview */
        .code-line {
            display: flex;
            gap: 1rem;
            padding: 0.25rem 0;
        }
        
        .code-line-num {
            color: var(--text-dim);
            min-width: 24px;
            text-align: right;
        }
        
        .code-keyword { color: #c084fc; }
        .code-string { color: #4ade80; }
        .code-func { color: #60a5fa; }
        .code-var { color: #fbbf24; }
        
        /* Tech Stack */
        .tech-stack {
            padding: 6rem 0;
        }
        
        .tech-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
        }
        
        .tech-card {
            padding: 2rem;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            text-align: center;
            transition: all 0.2s;
        }
        
        .tech-card:hover {
            border-color: var(--primary);
            background: var(--bg-card-hover);
        }
        
        .tech-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .tech-card h4 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .tech-card p {
            font-size: 0.8125rem;
            color: var(--text-dim);
        }
        
        /* CTA Section */
        .cta {
            padding: 8rem 0;
            text-align: center;
        }
        
        .cta-box {
            background: linear-gradient(135deg, rgba(99,102,241,0.15) 0%, rgba(139,92,246,0.15) 100%);
            border: 1px solid rgba(99,102,241,0.3);
            border-radius: 32px;
            padding: 5rem;
            position: relative;
            overflow: hidden;
        }
        
        .cta-box::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at center, var(--glow) 0%, transparent 60%);
            opacity: 0.3;
            animation: rotate 20s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .cta-content {
            position: relative;
            z-index: 1;
        }
        
        .cta h2 {
            font-size: clamp(2rem, 5vw, 3.5rem);
            font-weight: 800;
            margin-bottom: 1.5rem;
            letter-spacing: -0.02em;
        }
        
        .cta p {
            font-size: 1.25rem;
            color: var(--text-muted);
            margin-bottom: 2.5rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Footer */
        .footer {
            padding: 3rem 0;
            border-top: 1px solid var(--border);
        }
        
        .footer-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .footer-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--text-muted);
            font-size: 0.875rem;
        }
        
        .footer-brand svg {
            width: 24px;
            height: 24px;
            color: var(--primary);
        }
        
        .footer-links {
            display: flex;
            gap: 2rem;
        }
        
        .footer-link {
            color: var(--text-dim);
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.2s;
        }
        
        .footer-link:hover { color: var(--text); }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .features-grid { grid-template-columns: repeat(2, 1fr); }
            .bento-large, .bento-small, .bento-half { grid-column: span 12; }
            .tech-grid { grid-template-columns: repeat(2, 1fr); }
        }
        
        @media (max-width: 768px) {
            .nav-links { display: none; }
            .stats { grid-template-columns: repeat(2, 1fr); gap: 1.5rem; }
            .features-grid { grid-template-columns: 1fr; }
            .tech-grid { grid-template-columns: 1fr; }
            .cta-box { padding: 3rem 2rem; }
            .footer-inner { flex-direction: column; gap: 1.5rem; text-align: center; }
        }
    </style>
</head>
<body>
    <!-- Background Effects -->
    <div class="bg-grid"></div>
    <div class="bg-glow bg-glow-1"></div>
    <div class="bg-glow bg-glow-2"></div>
    
    <!-- Navigation -->
    <nav class="nav">
        <div class="container nav-inner">
            <a href="<?= SITE_URL ?>" class="nav-logo">
                <svg viewBox="0 0 40 40" fill="none">
                    <rect width="40" height="40" rx="10" fill="url(#navGrad)"/>
                    <path d="M12 10L20 28L28 10" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    <defs>
                        <linearGradient id="navGrad" x1="0" y1="0" x2="40" y2="40">
                            <stop stop-color="#6366f1"/>
                            <stop offset="1" stop-color="#8b5cf6"/>
                        </linearGradient>
                    </defs>
                </svg>
                VoidForge
            </a>
            <div class="nav-links">
                <a href="#features" class="nav-link">Features</a>
                <a href="#technology" class="nav-link">Technology</a>
                <a href="https://github.com" class="nav-link" target="_blank">GitHub</a>
                <a href="<?= ADMIN_URL ?>" class="nav-cta">
                    Dashboard
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <section class="hero">
        <div class="container hero-content">
            <div class="hero-badge">
                <span class="hero-badge-dot"></span>
                Version <?= CMS_VERSION ?> — Now Available
            </div>
            <h1>
                Build Better With<br>
                <span class="hero-gradient">VoidForge CMS</span>
            </h1>
            <p>
                A modern, lightweight content management system built with pure PHP. 
                No frameworks, no bloat — just powerful features and elegant code.
            </p>
            <div class="hero-actions">
                <a href="<?= ADMIN_URL ?>" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                        <polyline points="10 17 15 12 10 7"/>
                        <line x1="15" y1="12" x2="3" y2="12"/>
                    </svg>
                    Open Dashboard
                </a>
                <a href="#features" class="btn btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <polygon points="10 8 16 12 10 16 10 8"/>
                    </svg>
                    Explore Features
                </a>
            </div>
        </div>
    </section>
    
    <!-- Stats -->
    <div class="container">
        <div class="stats">
            <div class="stat">
                <div class="stat-value">0</div>
                <div class="stat-label">Framework Dependencies</div>
            </div>
            <div class="stat">
                <div class="stat-value">14+</div>
                <div class="stat-label">Custom Field Types</div>
            </div>
            <div class="stat">
                <div class="stat-value">80+</div>
                <div class="stat-label">Admin Icons</div>
            </div>
            <div class="stat">
                <div class="stat-value">∞</div>
                <div class="stat-label">Possibilities</div>
            </div>
        </div>
    </div>
    
    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Features</span>
                <h2 class="section-title">Everything You Need</h2>
                <p class="section-desc">
                    Powerful features designed for developers and content creators alike.
                </p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon" style="background: rgba(99,102,241,0.15); color: #818cf8;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="12" y1="18" x2="12" y2="12"/>
                            <line x1="9" y1="15" x2="15" y2="15"/>
                        </svg>
                    </div>
                    <h3>Custom Post Types</h3>
                    <p>Create unlimited content types with custom fields, icons, and URL structures. Perfect for portfolios, products, events, and more.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon" style="background: rgba(16,185,129,0.15); color: #34d399;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7"/>
                            <rect x="14" y="3" width="7" height="7"/>
                            <rect x="14" y="14" width="7" height="7"/>
                            <rect x="3" y="14" width="7" height="7"/>
                        </svg>
                    </div>
                    <h3>Custom Fields</h3>
                    <p>14+ field types including text, WYSIWYG, images, files, colors, dates, and more. Create reusable field groups for any content type.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon" style="background: rgba(245,158,11,0.15); color: #fbbf24;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                            <circle cx="8.5" cy="8.5" r="1.5"/>
                            <polyline points="21 15 16 10 5 21"/>
                        </svg>
                    </div>
                    <h3>Media Library</h3>
                    <p>Organize uploads with folders, automatic thumbnails, and a beautiful gallery interface. Drag-and-drop support included.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon" style="background: rgba(236,72,153,0.15); color: #f472b6;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </div>
                    <h3>User Management</h3>
                    <p>Role-based permissions with admin, editor, and subscriber roles. Gravatar support and customizable profiles.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon" style="background: rgba(6,182,212,0.15); color: #22d3ee;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4"/>
                        </svg>
                    </div>
                    <h3>Live Customizer</h3>
                    <p>Real-time CSS editor with instant preview for both admin and frontend. Theme colors, fonts, and styles at your fingertips.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon" style="background: rgba(139,92,246,0.15); color: #a78bfa;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                    </div>
                    <h3>Built-in Security</h3>
                    <p>CSRF protection, secure sessions, password hashing, and XSS prevention. Your content stays safe.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Bento Grid -->
    <section class="bento">
        <div class="container">
            <div class="bento-grid">
                <div class="bento-card bento-large">
                    <h3>Developer-First Architecture</h3>
                    <p>Clean, readable code with no framework magic. Easy to understand, extend, and customize. Built for developers who want full control.</p>
                    <div class="bento-visual">
                        <div class="code-line"><span class="code-line-num">1</span><span class="code-keyword">$post</span> = <span class="code-func">Post::create</span>([</div>
                        <div class="code-line"><span class="code-line-num">2</span>    <span class="code-string">'title'</span> => <span class="code-string">'Hello World'</span>,</div>
                        <div class="code-line"><span class="code-line-num">3</span>    <span class="code-string">'content'</span> => <span class="code-var">$content</span>,</div>
                        <div class="code-line"><span class="code-line-num">4</span>    <span class="code-string">'post_type'</span> => <span class="code-string">'post'</span></div>
                        <div class="code-line"><span class="code-line-num">5</span>]);</div>
                    </div>
                </div>
                
                <div class="bento-card bento-small">
                    <h3>Auto Updates</h3>
                    <p>One-click updates with automatic backups. Upload a ZIP and VoidForge handles the rest — preserving your config, uploads, and customizations.</p>
                </div>
                
                <div class="bento-card bento-half">
                    <h3>Plugin System</h3>
                    <p>Extend functionality with hooks and filters. Create custom features without touching core files. Full WordPress-style action/filter API.</p>
                </div>
                
                <div class="bento-card bento-half">
                    <h3>Theme Support</h3>
                    <p>Build beautiful frontends with simple PHP templates. Full access to all post types, custom fields, and media. No template engine required.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Tech Stack -->
    <section class="tech-stack" id="technology">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Technology</span>
                <h2 class="section-title">Simple Yet Powerful</h2>
                <p class="section-desc">
                    Built on proven technologies. No complex build tools required.
                </p>
            </div>
            
            <div class="tech-grid">
                <div class="tech-card">
                    <div class="tech-icon">🐘</div>
                    <h4>Pure PHP</h4>
                    <p>No frameworks, just clean PHP 8+ code</p>
                </div>
                <div class="tech-card">
                    <div class="tech-icon">🗄️</div>
                    <h4>MySQL / MariaDB</h4>
                    <p>Reliable, battle-tested database</p>
                </div>
                <div class="tech-card">
                    <div class="tech-icon">🎨</div>
                    <h4>Modern CSS</h4>
                    <p>CSS variables, flexbox, grid</p>
                </div>
                <div class="tech-card">
                    <div class="tech-icon">⚡</div>
                    <h4>Vanilla JS</h4>
                    <p>No jQuery, no build step required</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <div class="cta-box">
                <div class="cta-content">
                    <h2>Ready to Build?</h2>
                    <p>Start creating amazing content with VoidForge CMS today.</p>
                    <a href="<?= ADMIN_URL ?>" class="btn btn-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                        </svg>
                        Launch Dashboard
                    </a>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container footer-inner">
            <div class="footer-brand">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M6 4L12 20L18 4"/>
                </svg>
                <span>VoidForge CMS v<?= CMS_VERSION ?></span>
            </div>
            <div class="footer-links">
                <a href="<?= ADMIN_URL ?>" class="footer-link">Dashboard</a>
                <a href="<?= ADMIN_URL ?>/settings.php" class="footer-link">Settings</a>
                <a href="https://github.com" class="footer-link" target="_blank">GitHub</a>
            </div>
        </div>
    </footer>
</body>
</html>
