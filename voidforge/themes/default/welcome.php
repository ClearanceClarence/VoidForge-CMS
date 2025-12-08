<?php
/**
 * Welcome/Demo Template - VoidForge CMS Showcase
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
    <title><?= esc($siteTitle) ?> - Modern Content Management</title>
    <link rel="icon" type="image/svg+xml" href="<?= SITE_URL ?>/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #8b5cf6;
            --accent: #06b6d4;
            --text: #0f172a;
            --text-muted: #64748b;
            --bg: #fafbfc;
            --card: #ffffff;
        }
        
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
        }
        
        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .hero-bg {
            position: absolute;
            inset: 0;
            background: 
                radial-gradient(ellipse at 20% 20%, rgba(99, 102, 241, 0.08) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 80%, rgba(6, 182, 212, 0.06) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 50%, rgba(139, 92, 246, 0.04) 0%, transparent 70%);
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 800px;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            margin-bottom: 2rem;
        }
        
        .badge {
            display: inline-block;
            padding: 0.375rem 1rem;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 100px;
            color: var(--primary);
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-bottom: 1.5rem;
        }
        
        h1 {
            font-family: 'Outfit', sans-serif;
            font-size: clamp(2.5rem, 6vw, 4rem);
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1.1;
            margin-bottom: 1.5rem;
        }
        
        .gradient-text {
            background: linear-gradient(135deg, var(--primary), var(--secondary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hero-subtitle {
            font-size: 1.25rem;
            color: var(--text-muted);
            max-width: 600px;
            margin: 0 auto 2.5rem;
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
            gap: 0.5rem;
            padding: 0.875rem 1.75rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9375rem;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.35);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.45);
        }
        
        .btn-secondary {
            background: white;
            color: var(--text);
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }
        
        .btn-secondary:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }
        
        .btn svg {
            width: 18px;
            height: 18px;
        }
        
        /* Features Section */
        .features {
            padding: 6rem 2rem;
            background: white;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }
        
        .section-header h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 2.25rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 1rem;
        }
        
        .section-header p {
            color: var(--text-muted);
            font-size: 1.125rem;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .feature-card {
            padding: 2rem;
            background: var(--bg);
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
            border-color: #cbd5e1;
        }
        
        .feature-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 12px;
            color: white;
            margin-bottom: 1.25rem;
        }
        
        .feature-card h3 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }
        
        .feature-card p {
            color: var(--text-muted);
            font-size: 0.9375rem;
        }
        
        /* Highlights Section */
        .highlights {
            padding: 6rem 2rem;
            background: linear-gradient(180deg, var(--bg) 0%, white 100%);
        }
        
        .highlight-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
        }
        
        @media (max-width: 900px) {
            .highlight-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 500px) {
            .highlight-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .highlight-item {
            text-align: center;
            padding: 1.5rem;
        }
        
        .highlight-number {
            font-family: 'Outfit', sans-serif;
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        
        .highlight-label {
            color: var(--text-muted);
            font-size: 0.9375rem;
            font-weight: 500;
        }
        
        /* CTA Section */
        .cta {
            padding: 6rem 2rem;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%);
            text-align: center;
            color: white;
        }
        
        .cta h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .cta p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 1.125rem;
            max-width: 500px;
            margin: 0 auto 2rem;
        }
        
        .cta .btn-primary {
            background: white;
            color: var(--primary);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }
        
        .cta .btn-primary:hover {
            background: #f8fafc;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4);
        }
        
        /* Footer */
        footer {
            padding: 2rem;
            text-align: center;
            background: white;
            border-top: 1px solid #e2e8f0;
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 1rem;
        }
        
        .footer-links a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.2s;
        }
        
        .footer-links a:hover {
            color: var(--primary);
        }
        
        .footer-copy {
            color: #94a3b8;
            font-size: 0.8125rem;
        }
        
        /* Animations */
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .logo {
            animation: float 4s ease-in-out infinite;
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-bg"></div>
        <div class="hero-content">
            <svg class="logo" viewBox="0 0 32 32" fill="none">
                <defs>
                    <linearGradient id="vGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#8b5cf6"/>
                        <stop offset="100%" style="stop-color:#06b6d4"/>
                    </linearGradient>
                    <linearGradient id="innerG" x1="50%" y1="0%" x2="50%" y2="100%">
                        <stop offset="0%" style="stop-color:#c4b5fd"/>
                        <stop offset="100%" style="stop-color:#8b5cf6"/>
                    </linearGradient>
                </defs>
                <path d="M5 5 L16 27 L27 5" fill="none" stroke="url(#vGrad)" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="16" cy="14" r="3.5" fill="url(#innerG)"/>
                <circle cx="16" cy="14" r="1.5" fill="#fff"/>
            </svg>
            
            <span class="badge">Open Source CMS</span>
            
            <h1>Build with <span class="gradient-text">VoidForge</span></h1>
            
            <p class="hero-subtitle">
                A modern, lightweight content management system built with pure PHP. 
                No frameworks, no bloat — just powerful, elegant functionality.
            </p>
            
            <div class="hero-actions">
                <a href="<?= ADMIN_URL ?>/" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="9" rx="1"></rect>
                        <rect x="14" y="3" width="7" height="5" rx="1"></rect>
                        <rect x="14" y="12" width="7" height="9" rx="1"></rect>
                        <rect x="3" y="16" width="7" height="5" rx="1"></rect>
                    </svg>
                    Open Dashboard
                </a>
                <a href="https://github.com/voidforge/cms" class="btn btn-secondary" target="_blank">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                    </svg>
                    View on GitHub
                </a>
            </div>
        </div>
    </section>
    
    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <div class="section-header">
                <h2>Everything You Need</h2>
                <p>Powerful features without the complexity. Build professional websites with ease.</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                            <polyline points="2 17 12 22 22 17"></polyline>
                            <polyline points="2 12 12 17 22 12"></polyline>
                        </svg>
                    </div>
                    <h3>Custom Post Types</h3>
                    <p>Create unlimited content types — products, portfolios, testimonials, and more. Each with its own custom fields.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="3" y1="9" x2="21" y2="9"></line>
                            <line x1="9" y1="21" x2="9" y2="9"></line>
                        </svg>
                    </div>
                    <h3>Custom Fields</h3>
                    <p>14 field types including text, images, dates, colors, WYSIWYG editors, and more. Build complex data structures.</p>
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
                    <p>Upload and organize files with folders. Automatic thumbnails, alt text, and metadata management.</p>
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
                    <h3>User Roles</h3>
                    <p>Admin, Editor, and Author roles with granular permissions. Secure, capability-based access control.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 19l7-7 3 3-7 7-3-3z"></path>
                            <path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"></path>
                            <path d="M2 2l7.586 7.586"></path>
                            <circle cx="11" cy="11" r="2"></circle>
                        </svg>
                    </div>
                    <h3>Live Customizer</h3>
                    <p>Real-time CSS editor with syntax highlighting. See changes instantly before publishing.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                    </div>
                    <h3>Built-in Security</h3>
                    <p>CSRF protection, prepared statements, bcrypt hashing, and secure session handling out of the box.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Highlights Section -->
    <section class="highlights">
        <div class="container">
            <div class="highlight-grid">
                <div class="highlight-item">
                    <div class="highlight-number">0</div>
                    <div class="highlight-label">Framework Dependencies</div>
                </div>
                <div class="highlight-item">
                    <div class="highlight-number">14+</div>
                    <div class="highlight-label">Custom Field Types</div>
                </div>
                <div class="highlight-item">
                    <div class="highlight-number">80+</div>
                    <div class="highlight-label">Admin Icons</div>
                </div>
                <div class="highlight-item">
                    <div class="highlight-number">∞</div>
                    <div class="highlight-label">Custom Post Types</div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <h2>Ready to Build?</h2>
            <p>Get started with VoidForge CMS today. Pure PHP, powerful features, zero bloat.</p>
            <a href="<?= ADMIN_URL ?>/" class="btn btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                </svg>
                Launch Dashboard
            </a>
        </div>
    </section>
    
    <!-- Footer -->
    <footer>
        <div class="footer-links">
            <a href="<?= ADMIN_URL ?>/">Dashboard</a>
            <a href="<?= ADMIN_URL ?>/settings.php">Settings</a>
            <a href="https://github.com/voidforge/cms" target="_blank">GitHub</a>
        </div>
        <p class="footer-copy">
            Powered by VoidForge CMS <?= CMS_VERSION ?>
        </p>
    </footer>
</body>
</html>
