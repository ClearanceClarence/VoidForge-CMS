<?php
/**
 * Default Theme - Landing Page
 * Dark, bold design with animated gradients and glassmorphism
 */

defined('CMS_ROOT') or die;

$siteTitle = getOption('site_title', 'VoidForge CMS');
$siteTagline = getOption('site_tagline', 'A Modern Content Management System');

// Get theme settings
$ts = getOption('theme_settings_default', []);
$heroTitle = $ts['hero_title'] ?? '';
$heroSubtitle = $ts['hero_subtitle'] ?? '';
$primaryColor = $ts['primary_color'] ?? '#6366f1';
$secondaryColor = $ts['secondary_color'] ?? '#8b5cf6';
$accentColor = $ts['accent_color'] ?? '#06b6d4';
$showFeatures = $ts['show_features'] ?? true;
$showPosts = $ts['show_posts'] ?? true;
$feature1Title = $ts['feature_1_title'] ?? 'Theme System';
$feature1Desc = $ts['feature_1_desc'] ?? 'Switch between beautiful themes instantly.';
$feature2Title = $ts['feature_2_title'] ?? 'Plugin Ready';
$feature2Desc = $ts['feature_2_desc'] ?? 'Extend functionality with plugins.';
$feature3Title = $ts['feature_3_title'] ?? 'Custom Post Types';
$feature3Desc = $ts['feature_3_desc'] ?? 'Create any content type you can imagine.';
$ctaTitle = $ts['cta_title'] ?? 'Ready to create?';
$ctaText = $ts['cta_text'] ?? 'Head to the dashboard to start building.';
$ctaButton = $ts['cta_button'] ?? 'Open Dashboard';
$customCss = $ts['custom_css'] ?? '';

// Use theme settings or fallback to site settings
if (empty($heroTitle)) $heroTitle = $siteTitle;
if (empty($heroSubtitle)) $heroSubtitle = $siteTagline;

// Get posts
$posts = [];
if ($showPosts) {
    $posts = Post::query([
        'type' => 'post',
        'status' => 'published',
        'limit' => 3,
        'orderby' => 'created_at',
        'order' => 'DESC'
    ]);
}

// Get pages for nav
$pages = Post::query([
    'type' => 'page',
    'status' => 'published',
    'limit' => 5,
    'orderby' => 'title',
    'order' => 'ASC'
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($siteTitle) ?></title>
    <meta name="description" content="<?= esc($siteTagline) ?>">
    <link rel="icon" href="<?= SITE_URL ?>/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: <?= esc($primaryColor) ?>;
            --secondary: <?= esc($secondaryColor) ?>;
            --accent: <?= esc($accentColor) ?>;
        }
        
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: #050508;
            color: #fff;
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        /* Animated gradient background */
        .bg-gradient {
            position: fixed;
            inset: 0;
            background: 
                radial-gradient(circle at 20% 20%, color-mix(in srgb, var(--primary) 20%, transparent) 0%, transparent 40%),
                radial-gradient(circle at 80% 30%, color-mix(in srgb, var(--secondary) 15%, transparent) 0%, transparent 40%),
                radial-gradient(circle at 40% 80%, color-mix(in srgb, var(--accent) 10%, transparent) 0%, transparent 40%);
            animation: bgMove 20s ease-in-out infinite;
            pointer-events: none;
            z-index: 0;
        }
        
        @keyframes bgMove {
            0%, 100% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.1) rotate(3deg); }
        }
        
        /* Noise texture */
        .noise {
            position: fixed;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)'/%3E%3C/svg%3E");
            opacity: 0.03;
            pointer-events: none;
            z-index: 1;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            position: relative;
            z-index: 10;
        }
        
        /* Header */
        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            padding: 1rem 0;
            background: rgba(5, 5, 8, 0.7);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        
        .header-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: #fff;
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            font-size: 1.25rem;
        }
        
        .logo svg { width: 36px; height: 36px; }
        
        nav ul {
            display: flex;
            gap: 0.5rem;
            list-style: none;
        }
        
        nav a {
            padding: 0.5rem 1rem;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 0.9375rem;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.2s;
        }
        
        nav a:hover { color: #fff; background: rgba(255,255,255,0.1); }
        
        .nav-cta {
            background: linear-gradient(135deg, var(--primary), var(--secondary)) !important;
            color: #fff !important;
        }
        
        /* Hero */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 8rem 0 6rem;
        }
        
        .hero-content { max-width: 900px; }
        
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1.25rem;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 100px;
            font-size: 0.875rem;
            color: rgba(255,255,255,0.8);
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
        }
        
        .hero-badge::before {
            content: '';
            width: 8px;
            height: 8px;
            background: #22c55e;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.2); }
        }
        
        .hero h1 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: clamp(3rem, 10vw, 6rem);
            font-weight: 700;
            line-height: 1.05;
            margin-bottom: 1.5rem;
            letter-spacing: -0.03em;
        }
        
        .hero h1 span {
            background: linear-gradient(135deg, var(--primary), var(--secondary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hero p {
            font-size: 1.25rem;
            color: rgba(255,255,255,0.6);
            max-width: 600px;
            margin: 0 auto 2.5rem;
        }
        
        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            box-shadow: 0 8px 32px color-mix(in srgb, var(--primary) 40%, transparent);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 40px color-mix(in srgb, var(--primary) 50%, transparent);
        }
        
        .btn-ghost {
            background: rgba(255,255,255,0.05);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
        }
        
        .btn-ghost:hover {
            background: rgba(255,255,255,0.1);
            border-color: rgba(255,255,255,0.25);
        }
        
        /* Features */
        .features {
            padding: 8rem 0;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }
        
        .section-header h2 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .section-header p {
            color: rgba(255,255,255,0.6);
            font-size: 1.125rem;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }
        
        .feature-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 20px;
            padding: 2rem;
            transition: all 0.3s;
            backdrop-filter: blur(10px);
        }
        
        .feature-card:hover {
            background: rgba(255,255,255,0.06);
            border-color: var(--primary);
            transform: translateY(-8px);
        }
        
        .feature-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }
        
        .feature-icon svg { width: 28px; height: 28px; color: #fff; }
        
        .feature-card h3 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }
        
        .feature-card p {
            color: rgba(255,255,255,0.6);
            font-size: 0.9375rem;
            line-height: 1.6;
        }
        
        /* Posts */
        .posts-section {
            padding: 8rem 0;
            background: rgba(255,255,255,0.02);
            border-top: 1px solid rgba(255,255,255,0.05);
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        
        .posts-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }
        
        .post-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            padding: 1.75rem;
            transition: all 0.2s;
        }
        
        .post-card:hover {
            border-color: var(--secondary);
            background: rgba(255,255,255,0.05);
        }
        
        .post-card h3 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .post-card h3 a { color: #fff; text-decoration: none; }
        .post-card h3 a:hover { color: var(--primary); }
        
        .post-meta {
            font-size: 0.8125rem;
            color: rgba(255,255,255,0.4);
            margin-bottom: 0.75rem;
        }
        
        .post-excerpt {
            color: rgba(255,255,255,0.6);
            font-size: 0.9375rem;
        }
        
        /* CTA */
        .cta {
            padding: 8rem 0;
            text-align: center;
        }
        
        .cta-card {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 24px;
            padding: 4rem 3rem;
            position: relative;
            overflow: hidden;
        }
        
        .cta-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        
        .cta h2 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            position: relative;
        }
        
        .cta p {
            font-size: 1.125rem;
            opacity: 0.9;
            margin-bottom: 2rem;
            position: relative;
        }
        
        .cta .btn {
            background: #fff;
            color: var(--primary);
            position: relative;
        }
        
        .cta .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        }
        
        /* Footer */
        footer {
            padding: 3rem 0;
            text-align: center;
            border-top: 1px solid rgba(255,255,255,0.05);
        }
        
        .footer-brand {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .footer-text {
            color: rgba(255,255,255,0.4);
            font-size: 0.875rem;
        }
        
        .footer-text a { color: var(--primary); text-decoration: none; }
        
        /* Mobile */
        @media (max-width: 900px) {
            nav ul { display: none; }
            .features-grid, .posts-grid { grid-template-columns: 1fr; }
            .hero h1 { font-size: 2.5rem; }
        }
        
        <?= $customCss ?>
    </style>
</head>
<body>
    <div class="bg-gradient"></div>
    <div class="noise"></div>
    
    <header>
        <div class="container">
            <div class="header-inner">
                <a href="<?= SITE_URL ?>" class="logo">
                    <svg viewBox="0 0 36 36" fill="none">
                        <defs>
                            <linearGradient id="lg" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="<?= esc($primaryColor) ?>"/>
                                <stop offset="100%" stop-color="<?= esc($accentColor) ?>"/>
                            </linearGradient>
                        </defs>
                        <path d="M6 6L18 30L30 6" stroke="url(#lg)" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="18" cy="15" r="3" fill="url(#lg)"/>
                    </svg>
                    <?= esc($siteTitle) ?>
                </a>
                <nav>
                    <ul>
                        <li><a href="<?= SITE_URL ?>">Home</a></li>
                        <?php foreach ($pages as $page): ?>
                        <li><a href="<?= SITE_URL ?>/<?= esc($page['slug']) ?>"><?= esc($page['title']) ?></a></li>
                        <?php endforeach; ?>
                        <li><a href="<?= ADMIN_URL ?>" class="nav-cta">Dashboard</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>
    
    <main>
        <section class="hero">
            <div class="container">
                <div class="hero-content">
                    <div class="hero-badge">Default Theme</div>
                    <h1><span><?= esc($heroTitle) ?></span></h1>
                    <p><?= esc($heroSubtitle) ?></p>
                    <div class="hero-buttons">
                        <a href="<?= ADMIN_URL ?>" class="btn btn-primary">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                            <?= esc($ctaButton) ?>
                        </a>
                        <a href="<?= ADMIN_URL ?>/themes.php" class="btn btn-ghost">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                            Browse Themes
                        </a>
                    </div>
                </div>
            </div>
        </section>
        
        <?php if ($showFeatures): ?>
        <section class="features">
            <div class="container">
                <div class="section-header">
                    <h2>Why Choose Us</h2>
                    <p>Everything you need to build amazing websites</p>
                </div>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                        </div>
                        <h3><?= esc($feature1Title) ?></h3>
                        <p><?= esc($feature1Desc) ?></p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                        </div>
                        <h3><?= esc($feature2Title) ?></h3>
                        <p><?= esc($feature2Desc) ?></p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                        </div>
                        <h3><?= esc($feature3Title) ?></h3>
                        <p><?= esc($feature3Desc) ?></p>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>
        
        <?php if ($showPosts && !empty($posts)): ?>
        <section class="posts-section">
            <div class="container">
                <div class="section-header">
                    <h2>Latest Posts</h2>
                    <p>Fresh content from our blog</p>
                </div>
                <div class="posts-grid">
                    <?php foreach ($posts as $p): 
                        $slug = $p['slug'] ?? sanitizeSlug($p['title']);
                        $excerpt = $p['excerpt'] ?? '';
                        if (!$excerpt && $p['content']) {
                            $text = strip_tags($p['content']);
                            $excerpt = strlen($text) > 100 ? substr($text, 0, 100) . '...' : $text;
                        }
                    ?>
                    <article class="post-card">
                        <h3><a href="<?= SITE_URL ?>/<?= esc($slug) ?>"><?= esc($p['title']) ?></a></h3>
                        <div class="post-meta"><?= date('M j, Y', strtotime($p['created_at'])) ?></div>
                        <p class="post-excerpt"><?= esc($excerpt) ?></p>
                    </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
        
        <section class="cta">
            <div class="container">
                <div class="cta-card">
                    <h2><?= esc($ctaTitle) ?></h2>
                    <p><?= esc($ctaText) ?></p>
                    <a href="<?= ADMIN_URL ?>" class="btn"><?= esc($ctaButton) ?> →</a>
                </div>
            </div>
        </section>
    </main>
    
    <footer>
        <div class="container">
            <div class="footer-brand"><?= esc($siteTitle) ?></div>
            <p class="footer-text">© <?= date('Y') ?>. Powered by <a href="https://voidforge.dev">VoidForge</a></p>
        </div>
    </footer>
</body>
</html>
