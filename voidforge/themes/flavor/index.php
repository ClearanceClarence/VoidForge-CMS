<?php
/**
 * Flavor Theme - Landing Page
 * Light, clean, modern design with soft aesthetics
 */

defined('CMS_ROOT') or die;

$siteTitle = getOption('site_title', 'VoidForge CMS');
$siteTagline = getOption('site_tagline', 'A Modern Content Management System');

// Get theme settings
$ts = getOption('theme_settings_flavor', []);
$heroTitle = $ts['hero_title'] ?? '';
$heroSubtitle = $ts['hero_subtitle'] ?? '';
$primaryColor = $ts['primary_color'] ?? '#0ea5e9';
$secondaryColor = $ts['secondary_color'] ?? '#0284c7';
$accentColor = $ts['accent_color'] ?? '#e0f2fe';
$showFeatures = $ts['show_features'] ?? true;
$showPosts = $ts['show_posts'] ?? true;
$showStats = $ts['show_stats'] ?? true;
$showCta = $ts['show_cta'] ?? true;
$stat1Value = $ts['stat_1_value'] ?? '2';
$stat1Label = $ts['stat_1_label'] ?? 'Themes';
$stat2Value = $ts['stat_2_value'] ?? '∞';
$stat2Label = $ts['stat_2_label'] ?? 'Possibilities';
$stat3Value = $ts['stat_3_value'] ?? '0';
$stat3Label = $ts['stat_3_label'] ?? 'Dependencies';
$stat4Value = $ts['stat_4_value'] ?? '100%';
$stat4Label = $ts['stat_4_label'] ?? 'PHP';
$feature1Title = $ts['feature_1_title'] ?? 'Lightning Fast';
$feature1Desc = $ts['feature_1_desc'] ?? 'No bloat, no frameworks. Pure PHP that just works.';
$feature2Title = $ts['feature_2_title'] ?? 'Theme System';
$feature2Desc = $ts['feature_2_desc'] ?? 'Switch looks instantly with WordPress-like themes.';
$feature3Title = $ts['feature_3_title'] ?? 'Extendable';
$feature3Desc = $ts['feature_3_desc'] ?? 'Add features with plugins and shortcodes.';
$ctaTitle = $ts['cta_title'] ?? 'Ready to get started?';
$ctaText = $ts['cta_text'] ?? 'Head to the dashboard to create content.';
$customCss = $ts['custom_css'] ?? '';

if (empty($heroTitle)) $heroTitle = $siteTitle;
if (empty($heroSubtitle)) $heroSubtitle = $siteTagline;

// Get posts
$posts = [];
if ($showPosts) {
    $posts = Post::query([
        'type' => 'post',
        'status' => 'published',
        'limit' => 4,
        'orderby' => 'created_at',
        'order' => 'DESC'
    ]);
}

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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: <?= esc($primaryColor) ?>;
            --primary-dark: <?= esc($secondaryColor) ?>;
            --accent-light: <?= esc($accentColor) ?>;
        }
        
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
            background: #fafbfc;
            color: #1e293b;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1140px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }
        
        /* Floating shapes */
        .shapes {
            position: fixed;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }
        
        .shape {
            position: absolute;
            border-radius: 50%;
            opacity: 0.5;
            filter: blur(80px);
        }
        
        .shape-1 {
            width: 400px;
            height: 400px;
            background: var(--accent-light);
            top: -100px;
            right: -100px;
            animation: float 15s ease-in-out infinite;
        }
        
        .shape-2 {
            width: 300px;
            height: 300px;
            background: color-mix(in srgb, var(--primary) 15%, transparent);
            bottom: 10%;
            left: -50px;
            animation: float 20s ease-in-out infinite reverse;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(30px, 30px) rotate(10deg); }
        }
        
        /* Header */
        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            padding: 0.875rem 0;
            background: rgba(250, 251, 252, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid #e2e8f0;
        }
        
        .header-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            text-decoration: none;
            color: #1e293b;
            font-weight: 700;
            font-size: 1.125rem;
        }
        
        .logo-box {
            width: 34px;
            height: 34px;
            background: var(--primary);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .logo-box svg { width: 20px; height: 20px; color: #fff; }
        
        nav ul {
            display: flex;
            gap: 0.25rem;
            list-style: none;
        }
        
        nav a {
            padding: 0.5rem 0.875rem;
            color: #64748b;
            text-decoration: none;
            font-size: 0.9375rem;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.15s;
        }
        
        nav a:hover { color: #1e293b; background: #f1f5f9; }
        
        .nav-cta {
            background: var(--primary) !important;
            color: #fff !important;
        }
        
        .nav-cta:hover { background: var(--primary-dark) !important; }
        
        /* Hero */
        .hero {
            padding: 10rem 0 5rem;
            position: relative;
            z-index: 1;
        }
        
        .hero-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }
        
        .hero-content { max-width: 520px; }
        
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.75rem 0.375rem 0.5rem;
            background: var(--accent-light);
            color: var(--primary-dark);
            border-radius: 100px;
            font-size: 0.8125rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        
        .hero-badge-icon {
            width: 20px;
            height: 20px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .hero-badge-icon svg { width: 12px; height: 12px; color: #fff; }
        
        .hero h1 {
            font-size: clamp(2.25rem, 5vw, 3.25rem);
            font-weight: 800;
            line-height: 1.15;
            margin-bottom: 1.25rem;
            color: #0f172a;
            letter-spacing: -0.02em;
        }
        
        .hero p {
            font-size: 1.125rem;
            color: #64748b;
            margin-bottom: 2rem;
            line-height: 1.7;
        }
        
        .hero-buttons {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8125rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9375rem;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: var(--primary);
            color: #fff;
            box-shadow: 0 4px 14px color-mix(in srgb, var(--primary) 35%, transparent);
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px color-mix(in srgb, var(--primary) 40%, transparent);
        }
        
        .btn-outline {
            background: #fff;
            color: #475569;
            border: 1.5px solid #e2e8f0;
        }
        
        .btn-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
        }
        
        /* Hero Visual */
        .hero-visual {
            position: relative;
        }
        
        .hero-card {
            background: #fff;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.1);
            border: 1px solid #e2e8f0;
        }
        
        .hero-card-header {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.25rem;
        }
        
        .hero-card-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        
        .hero-card-dot-1 { background: #ef4444; }
        .hero-card-dot-2 { background: #eab308; }
        .hero-card-dot-3 { background: #22c55e; }
        
        .hero-card-content {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .hero-card-line {
            height: 12px;
            border-radius: 6px;
            background: #f1f5f9;
        }
        
        .hero-card-line:nth-child(1) { width: 85%; }
        .hero-card-line:nth-child(2) { width: 65%; background: var(--accent-light); }
        .hero-card-line:nth-child(3) { width: 75%; }
        .hero-card-line:nth-child(4) { width: 50%; background: var(--accent-light); }
        
        .hero-accent {
            position: absolute;
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 24px;
            right: -30px;
            bottom: -30px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 20px 40px color-mix(in srgb, var(--primary) 30%, transparent);
        }
        
        .hero-accent svg { width: 48px; height: 48px; color: #fff; }
        
        /* Stats */
        .stats {
            padding: 4rem 0;
            background: #fff;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            position: relative;
            z-index: 1;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 2.75rem;
            font-weight: 800;
            color: var(--primary);
            line-height: 1;
            margin-bottom: 0.375rem;
        }
        
        .stat-label {
            color: #64748b;
            font-size: 0.9375rem;
            font-weight: 500;
        }
        
        /* Features */
        .features {
            padding: 6rem 0;
            position: relative;
            z-index: 1;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 3.5rem;
        }
        
        .section-header h2 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.75rem;
            color: #0f172a;
        }
        
        .section-header p {
            color: #64748b;
            font-size: 1.0625rem;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }
        
        .feature-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 1.75rem;
            transition: all 0.2s;
        }
        
        .feature-card:hover {
            border-color: var(--primary);
            box-shadow: 0 8px 30px rgba(14, 165, 233, 0.1);
            transform: translateY(-4px);
        }
        
        .feature-icon {
            width: 44px;
            height: 44px;
            background: var(--accent-light);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.25rem;
            color: var(--primary);
        }
        
        .feature-icon svg { width: 22px; height: 22px; }
        
        .feature-card h3 {
            font-size: 1.0625rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #0f172a;
        }
        
        .feature-card p {
            color: #64748b;
            font-size: 0.9375rem;
            line-height: 1.6;
        }
        
        /* Posts */
        .posts-section {
            padding: 6rem 0;
            background: #fff;
            position: relative;
            z-index: 1;
        }
        
        .posts-list {
            display: grid;
            gap: 1rem;
        }
        
        .post-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 1.5rem;
            background: #fafbfc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            text-decoration: none;
            transition: all 0.15s;
        }
        
        .post-item:hover {
            border-color: var(--primary);
            background: #fff;
            transform: translateX(6px);
        }
        
        .post-title {
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.125rem;
        }
        
        .post-meta {
            font-size: 0.8125rem;
            color: #94a3b8;
        }
        
        .post-arrow {
            color: #cbd5e1;
            transition: all 0.15s;
        }
        
        .post-item:hover .post-arrow {
            color: var(--primary);
            transform: translateX(4px);
        }
        
        /* CTA */
        .cta {
            padding: 6rem 0;
            position: relative;
            z-index: 1;
        }
        
        .cta-card {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 24px;
            padding: 4rem 3rem;
            text-align: center;
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        
        .cta-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 30% 50%, rgba(255,255,255,0.15) 0%, transparent 50%);
        }
        
        .cta h2 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.75rem;
            position: relative;
        }
        
        .cta p {
            font-size: 1.0625rem;
            opacity: 0.9;
            margin-bottom: 2rem;
            position: relative;
        }
        
        .cta .btn {
            background: #fff;
            color: var(--primary-dark);
            position: relative;
        }
        
        .cta .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        /* Footer */
        footer {
            padding: 2.5rem 0;
            border-top: 1px solid #e2e8f0;
            position: relative;
            z-index: 1;
        }
        
        .footer-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .footer-brand {
            font-weight: 700;
            color: #0f172a;
        }
        
        .footer-text {
            color: #94a3b8;
            font-size: 0.875rem;
        }
        
        .footer-text a { color: var(--primary); text-decoration: none; }
        
        /* Mobile */
        @media (max-width: 900px) {
            nav ul { display: none; }
            .hero-grid { grid-template-columns: 1fr; gap: 3rem; }
            .hero-visual { display: none; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .features-grid { grid-template-columns: 1fr; }
            .footer-inner { flex-direction: column; gap: 1rem; text-align: center; }
        }
        
        <?= $customCss ?>
    </style>
</head>
<body>
    <div class="shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
    </div>
    
    <header>
        <div class="container">
            <div class="header-inner">
                <a href="<?= SITE_URL ?>" class="logo">
                    <div class="logo-box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 4L12 20L18 4"/><circle cx="12" cy="10" r="2" fill="currentColor"/></svg>
                    </div>
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
                <div class="hero-grid">
                    <div class="hero-content">
                        <div class="hero-badge">
                            <span class="hero-badge-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                            </span>
                            Flavor Theme
                        </div>
                        <h1><?= esc($heroTitle) ?></h1>
                        <p><?= esc($heroSubtitle) ?></p>
                        <div class="hero-buttons">
                            <a href="<?= ADMIN_URL ?>" class="btn btn-primary">
                                Get Started
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                            </a>
                            <a href="<?= ADMIN_URL ?>/themes.php" class="btn btn-outline">
                                Change Theme
                            </a>
                        </div>
                    </div>
                    <div class="hero-visual">
                        <div class="hero-card">
                            <div class="hero-card-header">
                                <div class="hero-card-dot hero-card-dot-1"></div>
                                <div class="hero-card-dot hero-card-dot-2"></div>
                                <div class="hero-card-dot hero-card-dot-3"></div>
                            </div>
                            <div class="hero-card-content">
                                <div class="hero-card-line"></div>
                                <div class="hero-card-line"></div>
                                <div class="hero-card-line"></div>
                                <div class="hero-card-line"></div>
                            </div>
                        </div>
                        <div class="hero-accent">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <?php if ($showStats): ?>
        <section class="stats">
            <div class="container">
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-value"><?= esc($stat1Value) ?></div>
                        <div class="stat-label"><?= esc($stat1Label) ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= esc($stat2Value) ?></div>
                        <div class="stat-label"><?= esc($stat2Label) ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= esc($stat3Value) ?></div>
                        <div class="stat-label"><?= esc($stat3Label) ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= esc($stat4Value) ?></div>
                        <div class="stat-label"><?= esc($stat4Label) ?></div>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>
        
        <?php if ($showFeatures): ?>
        <section class="features">
            <div class="container">
                <div class="section-header">
                    <h2>Why VoidForge?</h2>
                    <p>Modern CMS built for simplicity and speed</p>
                </div>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                        </div>
                        <h3><?= esc($feature1Title) ?></h3>
                        <p><?= esc($feature1Desc) ?></p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                        </div>
                        <h3><?= esc($feature2Title) ?></h3>
                        <p><?= esc($feature2Desc) ?></p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
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
                    <p>Fresh content from the blog</p>
                </div>
                <div class="posts-list">
                    <?php foreach ($posts as $p): 
                        $slug = $p['slug'] ?? sanitizeSlug($p['title']);
                    ?>
                    <a href="<?= SITE_URL ?>/<?= esc($slug) ?>" class="post-item">
                        <div>
                            <div class="post-title"><?= esc($p['title']) ?></div>
                            <div class="post-meta"><?= date('M j, Y', strtotime($p['created_at'])) ?></div>
                        </div>
                        <svg class="post-arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
        
        <?php if ($showCta): ?>
        <section class="cta">
            <div class="container">
                <div class="cta-card">
                    <h2><?= esc($ctaTitle) ?></h2>
                    <p><?= esc($ctaText) ?></p>
                    <a href="<?= ADMIN_URL ?>" class="btn">
                        Open Dashboard
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </main>
    
    <footer>
        <div class="container">
            <div class="footer-inner">
                <div class="footer-brand"><?= esc($siteTitle) ?></div>
                <p class="footer-text">© <?= date('Y') ?>. Powered by <a href="https://voidforge.dev">VoidForge</a></p>
            </div>
        </div>
    </footer>
</body>
</html>
