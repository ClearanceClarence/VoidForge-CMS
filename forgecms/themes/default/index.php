<?php
/**
 * Default Theme - Landing Page
 * Forge CMS v1.0.6
 */

defined('CMS_ROOT') or die;
$siteTitle = getOption('site_title', 'Forge CMS');

$latestPosts = Post::query([
    'post_type' => 'post',
    'status' => 'published',
    'limit' => 3,
    'orderby' => 'created_at',
    'order' => 'DESC'
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($siteTitle) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= SITE_URL ?>/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --accent: #8b5cf6;
            --success: #10b981;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #fff;
            color: var(--gray-900);
            line-height: 1.6;
        }
        
        .container { max-width: 1200px; margin: 0 auto; padding: 0 1.5rem; }
        
        /* Navigation */
        .nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            padding: 1rem 0;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(0,0,0,0.05);
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
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--gray-900);
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            align-items: center;
            gap: 2.5rem;
            list-style: none;
        }
        
        .nav-links a {
            color: var(--gray-600);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9375rem;
            transition: color 0.2s;
        }
        
        .nav-links a:hover { color: var(--primary); }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            font-size: 0.9375rem;
            font-weight: 600;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: #fff;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.35);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.45);
        }
        
        .btn-secondary {
            background: var(--gray-100);
            color: var(--gray-700);
        }
        
        .btn-secondary:hover { background: var(--gray-200); }
        
        /* Hero */
        .hero {
            padding: 10rem 0 6rem;
            text-align: center;
            background: linear-gradient(180deg, var(--gray-50) 0%, #fff 100%);
            position: relative;
            overflow: hidden;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 1200px;
            height: 600px;
            background: radial-gradient(ellipse at center, rgba(99, 102, 241, 0.08) 0%, transparent 70%);
            pointer-events: none;
        }
        
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        
        .hero h1 {
            font-size: 4rem;
            font-weight: 900;
            line-height: 1.1;
            letter-spacing: -0.03em;
            margin-bottom: 1.5rem;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .hero h1 span {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hero p {
            font-size: 1.25rem;
            color: var(--gray-600);
            max-width: 600px;
            margin: 0 auto 2.5rem;
        }
        
        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-bottom: 4rem;
        }
        
        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 4rem;
        }
        
        .hero-stat {
            text-align: center;
        }
        
        .hero-stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--gray-900);
        }
        
        .hero-stat-label {
            font-size: 0.8125rem;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        
        /* Features */
        .features {
            padding: 6rem 0;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }
        
        .section-header h2 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }
        
        .section-header p {
            font-size: 1.125rem;
            color: var(--gray-600);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }
        
        .feature-card {
            padding: 2rem;
            background: var(--gray-50);
            border-radius: 16px;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            background: #fff;
            box-shadow: 0 20px 40px rgba(0,0,0,0.08);
            transform: translateY(-4px);
        }
        
        .feature-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            color: #fff;
        }
        
        .feature-card h3 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }
        
        .feature-card p {
            color: var(--gray-600);
            font-size: 0.9375rem;
        }
        
        /* CTA */
        .cta {
            padding: 6rem 0;
            background: linear-gradient(135deg, var(--gray-900) 0%, #1e1b4b 100%);
            text-align: center;
            color: #fff;
        }
        
        .cta h2 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }
        
        .cta p {
            font-size: 1.125rem;
            opacity: 0.8;
            margin-bottom: 2rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta .btn-primary {
            background: #fff;
            color: var(--gray-900);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .cta .btn-primary:hover {
            box-shadow: 0 6px 25px rgba(0,0,0,0.3);
        }
        
        /* Footer */
        .footer {
            padding: 3rem 0;
            background: var(--gray-50);
            text-align: center;
        }
        
        .footer-logo {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--gray-900);
            text-decoration: none;
            margin-bottom: 1rem;
        }
        
        .footer p {
            color: var(--gray-500);
            font-size: 0.875rem;
        }
        
        .footer a { color: var(--primary); text-decoration: none; }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .features-grid { grid-template-columns: repeat(2, 1fr); }
        }
        
        @media (max-width: 768px) {
            .nav-links { display: none; }
            .hero h1 { font-size: 2.5rem; }
            .hero { padding: 8rem 0 4rem; }
            .features-grid { grid-template-columns: 1fr; }
            .hero-stats { flex-wrap: wrap; gap: 2rem; }
            .hero-buttons { flex-direction: column; align-items: center; }
        }
    </style>
</head>
<body>
    <nav class="nav">
        <div class="container nav-inner">
            <a href="<?= SITE_URL ?>" class="nav-logo">
                <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
                    <rect x="2" y="2" width="28" height="28" rx="6" fill="url(#logoGrad)"/>
                    <path d="M9 7L9 25L13 25L13 17L21 17L21 13L13 13L13 11L23 11L23 7Z" fill="white"/>
                    <defs>
                        <linearGradient id="logoGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#6366f1"/>
                            <stop offset="100%" style="stop-color:#8b5cf6"/>
                        </linearGradient>
                    </defs>
                </svg>
                Forge
            </a>
            <ul class="nav-links">
                <li><a href="#features">Features</a></li>
                <li><a href="<?= ADMIN_URL ?>/">Dashboard</a></li>
            </ul>
            <a href="<?= ADMIN_URL ?>/" class="btn btn-primary">Get Started</a>
        </div>
    </nav>

    <section class="hero">
        <div class="container">
            <div class="hero-badge">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                </svg>
                Version <?= CMS_VERSION ?> Released
            </div>
            <h1>The Modern CMS for <span>Developers</span></h1>
            <p>A lightweight, fast, and extensible content management system. Zero dependencies, pure PHP, infinitely customizable.</p>
            <div class="hero-buttons">
                <a href="<?= ADMIN_URL ?>/" class="btn btn-primary" style="padding: 1rem 2rem;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="9" rx="1"></rect>
                        <rect x="14" y="3" width="7" height="5" rx="1"></rect>
                        <rect x="14" y="12" width="7" height="9" rx="1"></rect>
                        <rect x="3" y="16" width="7" height="5" rx="1"></rect>
                    </svg>
                    Open Dashboard
                </a>
                <a href="#features" class="btn btn-secondary" style="padding: 1rem 2rem;">Learn More</a>
            </div>
            <div class="hero-stats">
                <div class="hero-stat">
                    <div class="hero-stat-value">&lt;200KB</div>
                    <div class="hero-stat-label">Package Size</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-value">0</div>
                    <div class="hero-stat-label">Dependencies</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-value">PHP 8+</div>
                    <div class="hero-stat-label">Native Code</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-value">&lt;50ms</div>
                    <div class="hero-stat-label">Response Time</div>
                </div>
            </div>
        </div>
    </section>

    <section class="features" id="features">
        <div class="container">
            <div class="section-header">
                <h2>Everything You Need</h2>
                <p>Built from the ground up with modern development practices and a focus on simplicity.</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon" style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                    </div>
                    <h3>Custom Post Types</h3>
                    <p>Create any content structure with the visual builder. Add custom fields, set labels, and configure display options.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <polyline points="21 15 16 10 5 21"></polyline>
                        </svg>
                    </div>
                    <h3>Media Management</h3>
                    <p>Upload, organize, and transform images with automatic thumbnail generation and detailed diagnostics.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M12 2a10 10 0 0 1 0 20"></path>
                        </svg>
                    </div>
                    <h3>Admin Themes</h3>
                    <p>Personalize your dashboard with 6 color schemes, multiple fonts, and icon styles. Make it yours.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon" style="background: linear-gradient(135deg, #ec4899, #db2777);">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 19l7-7 3 3-7 7-3-3z"></path>
                            <path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"></path>
                        </svg>
                    </div>
                    <h3>Live CSS Editor</h3>
                    <p>Real-time preview as you customize styles. Separate admin and frontend CSS with instant feedback.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8);">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="16 18 22 12 16 6"></polyline>
                            <polyline points="8 6 2 12 8 18"></polyline>
                        </svg>
                    </div>
                    <h3>Plugin System</h3>
                    <p>Extend functionality with hooks, filters, and shortcodes. Clean architecture for easy development.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 4 23 10 17 10"></polyline>
                            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                        </svg>
                    </div>
                    <h3>Auto Updates</h3>
                    <p>One-click updates with automatic backups, database migrations, and rollback capability.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="cta">
        <div class="container">
            <h2>Ready to Build?</h2>
            <p>Start creating with Forge CMS today. It's free, open source, and designed for developers.</p>
            <a href="<?= ADMIN_URL ?>/" class="btn btn-primary" style="padding: 1rem 2.5rem;">
                Launch Dashboard
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
            </a>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <a href="<?= SITE_URL ?>" class="footer-logo">
                <svg width="24" height="24" viewBox="0 0 32 32" fill="none">
                    <rect x="2" y="2" width="28" height="28" rx="6" fill="url(#footerGrad)"/>
                    <path d="M9 7L9 25L13 25L13 17L21 17L21 13L13 13L13 11L23 11L23 7Z" fill="white"/>
                    <defs>
                        <linearGradient id="footerGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#6366f1"/>
                            <stop offset="100%" style="stop-color:#8b5cf6"/>
                        </linearGradient>
                    </defs>
                </svg>
                Forge CMS
            </a>
            <p>Version <?= CMS_VERSION ?> • <a href="<?= ADMIN_URL ?>/">Admin Dashboard</a></p>
        </div>
    </footer>
</body>
</html>
