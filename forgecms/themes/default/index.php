<?php
/**
 * Default Theme - Landing Page
 * Forge CMS v1.0.2
 */

defined('CMS_ROOT') or die;
$siteTitle = getOption('site_title', 'Forge CMS');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($siteTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= THEME_URL ?>/style.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --accent: #8b5cf6;
            --success: #10b981;
            --dark: #0f172a;
            --darker: #020617;
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
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--gray-200);
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
        
        .nav-logo svg { color: var(--primary); }
        
        .nav-links {
            display: flex;
            align-items: center;
            gap: 2rem;
            list-style: none;
        }
        
        .nav-links a {
            color: var(--gray-600);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        
        .nav-links a:hover { color: var(--primary); }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            font-size: 0.9375rem;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: #fff;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
        }
        
        .btn-secondary {
            background: #fff;
            color: var(--gray-700);
            border: 1px solid var(--gray-300);
        }
        
        .btn-secondary:hover {
            border-color: var(--primary);
            color: var(--primary);
        }
        
        /* Hero */
        .hero {
            padding: 8rem 0 5rem;
            background: linear-gradient(180deg, var(--gray-50) 0%, #fff 100%);
        }
        
        .hero-inner {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }
        
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.375rem 1rem;
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        
        .hero h1 {
            font-size: 3.5rem;
            font-weight: 900;
            line-height: 1.1;
            letter-spacing: -0.025em;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, var(--gray-900) 0%, var(--gray-700) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .hero p {
            font-size: 1.25rem;
            color: var(--gray-600);
            margin-bottom: 2rem;
            max-width: 500px;
        }
        
        .hero-buttons { display: flex; gap: 1rem; }
        
        /* Demo Card */
        .demo-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.1);
            overflow: hidden;
            border: 1px solid var(--gray-200);
        }
        
        .demo-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 1rem;
            background: var(--gray-100);
            border-bottom: 1px solid var(--gray-200);
        }
        
        .demo-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        
        .demo-dot.red { background: #ef4444; }
        .demo-dot.yellow { background: #f59e0b; }
        .demo-dot.green { background: #10b981; }
        
        .demo-content { padding: 1.5rem; }
        
        /* Features */
        .features {
            padding: 5rem 0;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 3rem;
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
            gap: 1.5rem;
        }
        
        .feature-card {
            padding: 2rem;
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            transition: all 0.3s;
        }
        
        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.08);
            border-color: var(--primary);
        }
        
        .feature-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
            color: var(--primary);
        }
        
        .feature-card h3 {
            font-size: 1.125rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .feature-card p {
            color: var(--gray-600);
            font-size: 0.9375rem;
        }
        
        /* Form Demo */
        .form-demo {
            padding: 5rem 0;
            background: var(--gray-50);
        }
        
        .form-demo-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: start;
        }
        
        .form-demo-info h2 {
            font-size: 2.25rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }
        
        .form-demo-info p {
            color: var(--gray-600);
            margin-bottom: 2rem;
        }
        
        .form-demo-info ul {
            list-style: none;
        }
        
        .form-demo-info li {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 0;
            color: var(--gray-700);
        }
        
        .form-demo-info li svg {
            color: var(--success);
            flex-shrink: 0;
        }
        
        .demo-form {
            background: #fff;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            border: 1px solid var(--gray-200);
        }
        
        .demo-form h3 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
            font-size: 0.9375rem;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            transition: all 0.2s;
            font-family: inherit;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }
        
        textarea.form-input {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }
        
        .form-checkbox input {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
        }
        
        /* Stats */
        .stats {
            padding: 4rem 0;
            border-top: 1px solid var(--gray-200);
            border-bottom: 1px solid var(--gray-200);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
            text-align: center;
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stat-label {
            color: var(--gray-600);
            font-size: 0.9375rem;
            margin-top: 0.25rem;
        }
        
        /* CTA */
        .cta {
            padding: 5rem 0;
            text-align: center;
        }
        
        .cta h2 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }
        
        .cta p {
            color: var(--gray-600);
            font-size: 1.125rem;
            margin-bottom: 2rem;
        }
        
        .cta-buttons { display: flex; gap: 1rem; justify-content: center; }
        
        /* Footer */
        .footer {
            padding: 3rem 0;
            background: var(--dark);
            color: #fff;
            text-align: center;
        }
        
        .footer-logo {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.25rem;
            font-weight: 700;
            color: #fff;
            text-decoration: none;
            margin-bottom: 1rem;
        }
        
        .footer-logo svg { color: var(--primary); }
        
        .footer p {
            color: var(--gray-400);
            font-size: 0.875rem;
        }
        
        .footer a {
            color: var(--primary);
            text-decoration: none;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .hero-inner, .form-demo-grid { grid-template-columns: 1fr; }
            .features-grid { grid-template-columns: repeat(2, 1fr); }
            .hero h1 { font-size: 2.5rem; }
        }
        
        @media (max-width: 768px) {
            .nav-links { display: none; }
            .features-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .form-row { grid-template-columns: 1fr; }
            .hero { padding-top: 6rem; }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="nav">
        <div class="container nav-inner">
            <a href="<?= SITE_URL ?>" class="nav-logo">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                    <polyline points="2 17 12 22 22 17"></polyline>
                    <polyline points="2 12 12 17 22 12"></polyline>
                </svg>
                Forge
            </a>
            <ul class="nav-links">
                <li><a href="#features">Features</a></li>
                <li><a href="#forms">Forms</a></li>
                <li><a href="#stats">Stats</a></li>
            </ul>
            <a href="<?= ADMIN_URL ?>/" class="btn btn-primary">Open Dashboard</a>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero">
        <div class="container hero-inner">
            <div class="hero-content">
                <div class="hero-badge">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                    </svg>
                    Version <?= CMS_VERSION ?> — Now Available
                </div>
                <h1>Build Beautiful Websites with Ease</h1>
                <p>Forge is a modern, lightweight content management system built with PHP. Simple, fast, and developer-friendly.</p>
                <div class="hero-buttons">
                    <a href="<?= ADMIN_URL ?>/" class="btn btn-primary">Open Dashboard</a>
                    <a href="#features" class="btn btn-secondary">Explore Features</a>
                </div>
            </div>
            <div class="demo-card">
                <div class="demo-header">
                    <div class="demo-dot red"></div>
                    <div class="demo-dot yellow"></div>
                    <div class="demo-dot green"></div>
                </div>
                <div class="demo-content">
                    <div style="background: var(--gray-100); border-radius: 8px; padding: 1rem; margin-bottom: 1rem;">
                        <div style="width: 60%; height: 12px; background: var(--gray-300); border-radius: 4px; margin-bottom: 0.75rem;"></div>
                        <div style="width: 40%; height: 8px; background: var(--gray-200); border-radius: 4px;"></div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div style="background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%); border-radius: 8px; padding: 1.5rem; color: #fff;">
                            <div style="font-size: 1.5rem; font-weight: 700;">12</div>
                            <div style="font-size: 0.75rem; opacity: 0.8;">Posts</div>
                        </div>
                        <div style="background: var(--gray-100); border-radius: 8px; padding: 1.5rem;">
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--gray-900);">5</div>
                            <div style="font-size: 0.75rem; color: var(--gray-500);">Pages</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="features" id="features">
        <div class="container">
            <div class="section-header">
                <h2>Everything You Need</h2>
                <p>A complete content management system with all the features you'd expect, and none of the bloat.</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 19l7-7 3 3-7 7-3-3z"></path>
                            <path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"></path>
                            <path d="M2 2l7.586 7.586"></path>
                            <circle cx="11" cy="11" r="2"></circle>
                        </svg>
                    </div>
                    <h3>Visual CSS Editor</h3>
                    <p>Edit your site's styles with live preview and syntax highlighting.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                        </svg>
                    </div>
                    <h3>Rich Content Editor</h3>
                    <p>WYSIWYG editing with formatting tools and media insertion.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <polyline points="21 15 16 10 5 21"></polyline>
                        </svg>
                    </div>
                    <h3>Media Library</h3>
                    <p>Organize files with folders, edit metadata, and quick uploads.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <h3>User Management</h3>
                    <p>Role-based access control with multiple permission levels.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                            <path d="M2 17l10 5 10-5"></path>
                            <path d="M2 12l10 5 10-5"></path>
                        </svg>
                    </div>
                    <h3>Plugin System</h3>
                    <p>Extend functionality with hooks, filters, and custom plugins.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="16 16 12 12 8 16"></polyline>
                            <line x1="12" y1="12" x2="12" y2="21"></line>
                            <path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"></path>
                        </svg>
                    </div>
                    <h3>One-Click Updates</h3>
                    <p>Simple ZIP file updates with automatic backups.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Form Demo -->
    <section class="form-demo" id="forms">
        <div class="container form-demo-grid">
            <div class="form-demo-info">
                <h2>Interactive Form Demo</h2>
                <p>Forms are essential for any website. This demo shows common form patterns you can use throughout your site.</p>
                <ul>
                    <li>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        Text inputs with focus states
                    </li>
                    <li>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        Select dropdowns and textareas
                    </li>
                    <li>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        Checkbox and radio inputs
                    </li>
                    <li>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        Responsive grid layouts
                    </li>
                    <li>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        Styled submit buttons
                    </li>
                </ul>
            </div>
            <div class="demo-form">
                <h3>Contact Form</h3>
                <form onsubmit="event.preventDefault(); alert('Form submitted! (Demo only)');">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-input" placeholder="John">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-input" placeholder="Doe">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-input" placeholder="john@example.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Subject</label>
                        <select class="form-input">
                            <option>General Inquiry</option>
                            <option>Technical Support</option>
                            <option>Sales Question</option>
                            <option>Partnership</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Message</label>
                        <textarea class="form-input" placeholder="Your message..."></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-checkbox">
                            <input type="checkbox">
                            <span>I agree to the terms and conditions</span>
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Send Message</button>
                </form>
            </div>
        </div>
    </section>

    <!-- Stats -->
    <section class="stats" id="stats">
        <div class="container stats-grid">
            <div class="stat">
                <div class="stat-value">&lt;1s</div>
                <div class="stat-label">Page Load Time</div>
            </div>
            <div class="stat">
                <div class="stat-value">100%</div>
                <div class="stat-label">PHP Native</div>
            </div>
            <div class="stat">
                <div class="stat-value">0</div>
                <div class="stat-label">Dependencies</div>
            </div>
            <div class="stat">
                <div class="stat-value">&lt;500KB</div>
                <div class="stat-label">Total Size</div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta">
        <div class="container">
            <h2>Ready to Start Building?</h2>
            <p>Get started with Forge CMS today. It's free, open source, and easy to customize.</p>
            <div class="cta-buttons">
                <a href="<?= ADMIN_URL ?>/" class="btn btn-primary">Open Dashboard</a>
                <a href="<?= SITE_URL ?>/sample-page" class="btn btn-secondary">View Sample Page</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <a href="<?= SITE_URL ?>" class="footer-logo">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                    <polyline points="2 17 12 22 22 17"></polyline>
                    <polyline points="2 12 12 17 22 12"></polyline>
                </svg>
                Forge
            </a>
            <p>Version <?= CMS_VERSION ?> &bull; Built with ♥ for creators everywhere</p>
            <p style="margin-top: 0.5rem;"><a href="<?= ADMIN_URL ?>/">Admin Dashboard</a></p>
        </div>
    </footer>

    <script>
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>
</body>
</html>
