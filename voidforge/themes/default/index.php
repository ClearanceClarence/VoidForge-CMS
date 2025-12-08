<?php
/**
 * Default Theme - Homepage (Light Mode)
 * VoidForge CMS v0.1.0-beta
 */

defined('CMS_ROOT') or die;

$siteTitle = getOption('site_title', 'VoidForge CMS');
$siteDescription = getOption('site_description', 'A modern content management system');

// Get latest posts
$latestPosts = Post::query([
    'post_type' => 'post',
    'status' => 'published',
    'limit' => 3,
    'orderby' => 'created_at',
    'order' => 'DESC'
]);

// Get pages for nav
$pages = Post::query([
    'post_type' => 'page',
    'status' => 'published',
    'limit' => 5,
    'orderby' => 'title',
    'order' => 'ASC'
]);

// Get stats
$totalPosts = count(Post::query(['post_type' => 'post', 'limit' => 1000]));
$totalPages = count(Post::query(['post_type' => 'page', 'limit' => 1000]));
$totalMedia = count(Media::getAll());

// Get custom post types
$customPostTypes = getOption('custom_post_types', []);
if (is_string($customPostTypes)) {
    $customPostTypes = json_decode($customPostTypes, true) ?: [];
}
$totalCustomTypes = count($customPostTypes);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($siteTitle) ?></title>
    <meta name="description" content="<?= esc($siteDescription) ?>">
    <link rel="icon" type="image/svg+xml" href="<?= SITE_URL ?>/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <?php
    $frontendCss = getOption('frontend_custom_css', '');
    if (!empty($frontendCss)): ?>
    <style><?= $frontendCss ?></style>
    <?php endif; ?>
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #8b5cf6;
            --accent: #06b6d4;
            --accent-pink: #ec4899;
            --success: #10b981;
            --warning: #f59e0b;
            --white: #ffffff;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
        }

        html { scroll-behavior: smooth; }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--white);
            color: var(--gray-900);
            line-height: 1.6;
        }

        .container { max-width: 1280px; margin: 0 auto; padding: 0 2rem; }

        /* Header - Always visible with white bg */
        header {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            padding: 0.875rem 0;
            background: var(--white);
            border-bottom: 1px solid var(--gray-200);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            text-decoration: none;
            color: var(--gray-900);
        }
        .logo-icon {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 10px;
            display: grid;
            place-items: center;
            font-size: 1.125rem;
            color: white;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
        }
        .logo-text { font-size: 1.375rem; font-weight: 800; letter-spacing: -0.02em; }
        
        nav { display: flex; align-items: center; gap: 2rem; }
        nav a {
            color: var(--gray-600);
            text-decoration: none;
            font-size: 0.9375rem;
            font-weight: 500;
            transition: color 0.2s;
        }
        nav a:hover { color: var(--primary); }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: var(--white);
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.3);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
        }
        .btn-lg { padding: 0.875rem 1.75rem; font-size: 1rem; }
        .btn-outline {
            background: transparent;
            color: var(--gray-700);
            border: 2px solid var(--gray-300);
        }
        .btn-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: rgba(99, 102, 241, 0.05);
        }
        .btn-white {
            background: var(--white);
            color: var(--gray-900);
            box-shadow: 0 4px 14px rgba(0,0,0,0.1);
        }
        .btn-white:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        /* Hero */
        .hero {
            padding: 10rem 0 6rem;
            background: linear-gradient(180deg, var(--gray-50) 0%, var(--white) 100%);
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: 0; left: 50%;
            transform: translateX(-50%);
            width: 1400px; height: 700px;
            background: radial-gradient(ellipse at center, rgba(99, 102, 241, 0.08) 0%, transparent 70%);
            pointer-events: none;
        }
        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem 0.5rem 0.625rem;
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: 100px;
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--gray-600);
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .hero-badge-dot {
            width: 8px; height: 8px;
            background: var(--success);
            border-radius: 50%;
            animation: pulse-dot 2s ease-in-out infinite;
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(0.9); }
        }
        .hero-badge strong { color: var(--gray-900); }
        
        .hero h1 {
            font-size: clamp(2.75rem, 6vw, 4.5rem);
            font-weight: 900;
            line-height: 1.1;
            letter-spacing: -0.03em;
            margin-bottom: 1.25rem;
            color: var(--gray-900);
        }
        .hero h1 .gradient {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 50%, var(--accent-pink) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero-desc {
            font-size: 1.25rem;
            color: var(--gray-600);
            max-width: 600px;
            margin: 0 auto 2.5rem;
            line-height: 1.7;
        }
        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-bottom: 4rem;
        }
        
        /* Hero Stats */
        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 4rem;
            padding-top: 3rem;
            border-top: 1px solid var(--gray-200);
            max-width: 700px;
            margin: 0 auto;
        }
        .hero-stat { text-align: center; }
        .hero-stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary);
            line-height: 1.2;
        }
        .hero-stat-label {
            font-size: 0.875rem;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 500;
        }

        /* Dashboard Preview */
        .dashboard-preview {
            padding: 0 2rem 6rem;
            margin-top: -1rem;
        }
        .browser-frame {
            max-width: 1100px;
            margin: 0 auto;
            background: var(--white);
            border-radius: 16px;
            border: 1px solid var(--gray-200);
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.15);
        }
        .browser-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 14px 18px;
            background: var(--gray-100);
            border-bottom: 1px solid var(--gray-200);
        }
        .browser-dot { width: 12px; height: 12px; border-radius: 50%; }
        .browser-dot.r { background: #ff5f57; }
        .browser-dot.y { background: #febc2e; }
        .browser-dot.g { background: #28c840; }
        .browser-url {
            margin-left: 16px;
            padding: 6px 14px;
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: 6px;
            font-size: 0.75rem;
            color: var(--gray-500);
            font-family: 'JetBrains Mono', monospace;
        }
        .browser-body {
            display: grid;
            grid-template-columns: 220px 1fr;
            min-height: 420px;
        }
        .browser-sidebar {
            background: linear-gradient(180deg, #1e1b4b 0%, #312e81 100%);
            padding: 1.5rem 1rem;
            color: white;
        }
        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-logo-icon {
            width: 28px; height: 28px;
            background: rgba(255,255,255,0.15);
            border-radius: 6px;
            display: grid;
            place-items: center;
            font-size: 0.875rem;
        }
        .sidebar-logo span { font-weight: 700; font-size: 0.9375rem; }
        .sidebar-menu { display: flex; flex-direction: column; gap: 4px; }
        .sidebar-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 0.8125rem;
            color: rgba(255,255,255,0.6);
        }
        .sidebar-item.active {
            background: rgba(255,255,255,0.15);
            color: var(--white);
        }
        .sidebar-item svg { width: 16px; height: 16px; }
        .sidebar-section {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255,255,255,0.08);
        }
        .sidebar-section-title {
            font-size: 0.6875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: rgba(255,255,255,0.4);
            padding: 0 12px;
            margin-bottom: 0.5rem;
        }
        
        .browser-main {
            padding: 1.5rem;
            background: var(--gray-50);
        }
        .browser-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }
        .browser-title { font-size: 1.375rem; font-weight: 700; color: var(--gray-900); }
        .browser-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 8px;
            font-size: 0.8125rem;
            font-weight: 600;
        }
        .browser-btn svg { width: 14px; height: 14px; }
        
        .stat-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .stat-card {
            background: var(--white);
            border-radius: 12px;
            padding: 1.25rem;
            border: 1px solid var(--gray-200);
        }
        .stat-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }
        .stat-card-icon {
            width: 36px; height: 36px;
            border-radius: 8px;
            display: grid;
            place-items: center;
        }
        .stat-card-icon svg { width: 18px; height: 18px; }
        .stat-card-icon.purple { background: rgba(99, 102, 241, 0.1); color: var(--primary); }
        .stat-card-icon.green { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .stat-card-icon.orange { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
        .stat-card-icon.pink { background: rgba(236, 72, 153, 0.1); color: var(--accent-pink); }
        .stat-card-trend {
            font-size: 0.6875rem;
            font-weight: 600;
            color: var(--success);
            display: flex;
            align-items: center;
            gap: 2px;
        }
        .stat-card-trend svg { width: 12px; height: 12px; }
        .stat-card-value { font-size: 1.75rem; font-weight: 700; color: var(--gray-900); line-height: 1; }
        .stat-card-label { font-size: 0.75rem; color: var(--gray-500); margin-top: 0.25rem; }
        
        .browser-table {
            background: var(--white);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--gray-200);
        }
        .browser-table-header {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 100px;
            padding: 12px 16px;
            background: var(--gray-50);
            font-size: 0.6875rem;
            font-weight: 600;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--gray-200);
        }
        .browser-table-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 100px;
            padding: 14px 16px;
            font-size: 0.8125rem;
            color: var(--gray-700);
            border-top: 1px solid var(--gray-100);
            align-items: center;
        }
        .browser-table-row:first-child { border-top: none; }
        .table-title { font-weight: 600; color: var(--gray-900); }
        .table-status {
            display: inline-flex;
            padding: 3px 10px;
            border-radius: 100px;
            font-size: 0.6875rem;
            font-weight: 600;
        }
        .table-status.published { background: rgba(16, 185, 129, 0.1); color: #059669; }
        .table-status.draft { background: rgba(245, 158, 11, 0.1); color: #d97706; }

        /* Features Section */
        .features {
            padding: 7rem 0;
            background: var(--white);
        }
        .section-header {
            text-align: center;
            max-width: 700px;
            margin: 0 auto 4rem;
        }
        .section-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8125rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        .section-title {
            font-size: clamp(2rem, 4vw, 2.75rem);
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -0.02em;
            margin-bottom: 1rem;
            color: var(--gray-900);
        }
        .section-desc {
            font-size: 1.125rem;
            color: var(--gray-600);
            line-height: 1.7;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }
        .feature-card {
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: 16px;
            padding: 2rem;
            transition: all 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-4px);
            border-color: var(--primary);
            box-shadow: 0 20px 40px rgba(99, 102, 241, 0.1);
        }
        .feature-icon {
            width: 52px; height: 52px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            margin-bottom: 1.25rem;
        }
        .feature-icon svg { width: 24px; height: 24px; color: white; }
        .feature-icon.purple { background: linear-gradient(135deg, var(--primary), var(--secondary)); }
        .feature-icon.cyan { background: linear-gradient(135deg, var(--accent), #0891b2); }
        .feature-icon.green { background: linear-gradient(135deg, var(--success), #059669); }
        .feature-icon.pink { background: linear-gradient(135deg, var(--accent-pink), #db2777); }
        .feature-icon.orange { background: linear-gradient(135deg, var(--warning), #d97706); }
        .feature-icon.blue { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
        .feature-title { font-size: 1.125rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--gray-900); }
        .feature-desc { color: var(--gray-600); line-height: 1.6; font-size: 0.9375rem; }

        /* Code Demo Section */
        .code-demo {
            padding: 7rem 0;
            background: var(--gray-50);
        }
        .code-demo-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }
        .code-demo-content h2 {
            font-size: clamp(1.75rem, 3vw, 2.5rem);
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -0.02em;
            margin-bottom: 1.25rem;
            color: var(--gray-900);
        }
        .code-demo-content > p {
            font-size: 1.0625rem;
            color: var(--gray-600);
            line-height: 1.7;
            margin-bottom: 2rem;
        }
        .code-features {
            display: flex;
            flex-direction: column;
            gap: 0.875rem;
        }
        .code-feature {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem;
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: 12px;
        }
        .code-feature-icon {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 10px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
        }
        .code-feature-icon svg { width: 20px; height: 20px; color: white; }
        .code-feature-text h4 { font-size: 0.9375rem; font-weight: 600; margin-bottom: 0.25rem; color: var(--gray-900); }
        .code-feature-text p { font-size: 0.875rem; color: var(--gray-600); line-height: 1.5; }
        
        .code-window {
            background: #1e1e2e;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0,0,0,0.2);
        }
        .code-tabs {
            display: flex;
            background: #181825;
            border-bottom: 1px solid #313244;
        }
        .code-tab {
            padding: 12px 20px;
            font-size: 0.8125rem;
            font-weight: 500;
            color: #6c7086;
            border-bottom: 2px solid transparent;
            cursor: pointer;
        }
        .code-tab.active {
            color: #cdd6f4;
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.1);
        }
        .code-body {
            padding: 1.5rem;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.8125rem;
            line-height: 1.8;
            color: #cdd6f4;
            overflow-x: auto;
        }
        .code-line { display: flex; }
        .code-line-num {
            width: 40px;
            color: #6c7086;
            user-select: none;
            text-align: right;
            padding-right: 1rem;
        }
        .code-body .c { color: #6c7086; }
        .code-body .k { color: #cba6f7; }
        .code-body .f { color: #89b4fa; }
        .code-body .s { color: #a6e3a1; }
        .code-body .v { color: #f5c2e7; }
        .code-body .n { color: #fab387; }
        .code-body .t { color: #94e2d5; }

        /* Custom Post Types Demo */
        .cpt-demo {
            padding: 7rem 0;
            background: var(--white);
        }
        .cpt-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-top: 3rem;
        }
        .cpt-card {
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: 16px;
            padding: 1.75rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        .cpt-card:hover {
            transform: translateY(-4px);
            border-color: var(--primary);
            box-shadow: 0 20px 40px rgba(99, 102, 241, 0.1);
        }
        .cpt-icon {
            width: 64px; height: 64px;
            margin: 0 auto 1rem;
            background: linear-gradient(135deg, var(--gray-100), var(--gray-50));
            border-radius: 16px;
            display: grid;
            place-items: center;
            font-size: 1.75rem;
            border: 1px solid var(--gray-200);
        }
        .cpt-title { font-size: 1.0625rem; font-weight: 700; margin-bottom: 0.375rem; color: var(--gray-900); }
        .cpt-desc { font-size: 0.875rem; color: var(--gray-600); margin-bottom: 1rem; line-height: 1.5; }
        .cpt-fields {
            display: flex;
            flex-wrap: wrap;
            gap: 0.375rem;
            justify-content: center;
        }
        .cpt-field {
            padding: 4px 10px;
            background: rgba(99, 102, 241, 0.08);
            border-radius: 100px;
            font-size: 0.6875rem;
            color: var(--primary);
            font-weight: 600;
        }

        /* Posts Section */
        .posts-section {
            padding: 7rem 0;
            background: var(--gray-50);
        }
        .posts-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            margin-bottom: 2.5rem;
        }
        .posts-header h2 {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--gray-900);
        }
        .posts-header p {
            color: var(--gray-600);
            margin-top: 0.375rem;
        }
        .posts-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: gap 0.2s;
        }
        .posts-link:hover { gap: 0.75rem; }
        .posts-link svg { width: 18px; height: 18px; }
        
        .posts-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }
        .post-card {
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .post-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.08);
        }
        .post-card-image {
            aspect-ratio: 16/9;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(6, 182, 212, 0.1));
            display: grid;
            place-items: center;
        }
        .post-card-image img { width: 100%; height: 100%; object-fit: cover; }
        .post-card-image svg { width: 48px; height: 48px; color: var(--gray-300); }
        .post-card-body { padding: 1.5rem; }
        .post-card-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.625rem;
            font-size: 0.8125rem;
            color: var(--gray-500);
        }
        .post-card-meta span { display: flex; align-items: center; gap: 0.375rem; }
        .post-card-meta svg { width: 14px; height: 14px; }
        .post-card-title { font-size: 1.125rem; font-weight: 700; margin-bottom: 0.5rem; line-height: 1.4; }
        .post-card-title a { color: var(--gray-900); text-decoration: none; transition: color 0.2s; }
        .post-card-title a:hover { color: var(--primary); }
        .post-card-excerpt {
            color: var(--gray-600);
            font-size: 0.9375rem;
            line-height: 1.6;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .empty-posts {
            grid-column: 1 / -1;
            text-align: center;
            padding: 4rem;
            background: var(--white);
            border: 2px dashed var(--gray-200);
            border-radius: 16px;
        }
        .empty-posts svg { width: 64px; height: 64px; color: var(--gray-300); margin-bottom: 1.5rem; }
        .empty-posts h3 { font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--gray-900); }
        .empty-posts p { color: var(--gray-600); margin-bottom: 1.5rem; }

        /* Tech Stack */
        .tech-stack {
            padding: 5rem 0;
            background: var(--white);
            border-top: 1px solid var(--gray-200);
            border-bottom: 1px solid var(--gray-200);
        }
        .tech-grid {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4rem;
            flex-wrap: wrap;
        }
        .tech-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.625rem;
            opacity: 0.5;
            transition: opacity 0.3s;
        }
        .tech-item:hover { opacity: 1; }
        .tech-item svg { width: 40px; height: 40px; color: var(--gray-600); }
        .tech-item span { font-size: 0.8125rem; font-weight: 600; color: var(--gray-600); }

        /* CTA Section */
        .cta {
            padding: 7rem 0;
            background: var(--gray-50);
        }
        .cta-box {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 50%, #7c3aed 100%);
            border-radius: 24px;
            padding: 4rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .cta-box::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.06'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            pointer-events: none;
        }
        .cta-box > * { position: relative; z-index: 1; }
        .cta h2 {
            font-size: clamp(1.75rem, 4vw, 2.75rem);
            font-weight: 900;
            margin-bottom: 0.875rem;
            color: white;
        }
        .cta p {
            font-size: 1.125rem;
            color: rgba(255,255,255,0.9);
            max-width: 500px;
            margin: 0 auto 2rem;
        }
        .cta-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }
        .cta-note {
            margin-top: 1.5rem;
            font-size: 0.875rem;
            color: rgba(255,255,255,0.7);
        }

        /* Footer */
        footer {
            padding: 4rem 0 2.5rem;
            background: var(--gray-900);
            color: var(--gray-400);
        }
        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }
        .footer-brand {
            max-width: 280px;
        }
        .footer-logo {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            margin-bottom: 1rem;
        }
        .footer-logo-icon {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 10px;
            display: grid;
            place-items: center;
            font-size: 1rem;
            color: white;
        }
        .footer-logo span { font-size: 1.25rem; font-weight: 800; color: white; }
        .footer-brand p { color: var(--gray-400); font-size: 0.9375rem; line-height: 1.7; }
        
        .footer-col h4 {
            font-size: 0.875rem;
            font-weight: 700;
            margin-bottom: 1.25rem;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .footer-col ul { list-style: none; display: flex; flex-direction: column; gap: 0.75rem; }
        .footer-col a {
            color: var(--gray-400);
            text-decoration: none;
            font-size: 0.9375rem;
            transition: color 0.2s;
        }
        .footer-col a:hover { color: white; }
        
        .footer-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 2rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        .footer-copy { color: var(--gray-500); font-size: 0.875rem; }
        .footer-links {
            display: flex;
            gap: 2rem;
        }
        .footer-links a {
            color: var(--gray-500);
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.2s;
        }
        .footer-links a:hover { color: white; }

        /* Responsive */
        @media (max-width: 1024px) {
            .browser-body { grid-template-columns: 180px 1fr; }
            .stat-cards { grid-template-columns: repeat(2, 1fr); }
            .features-grid { grid-template-columns: repeat(2, 1fr); }
            .code-demo-grid { grid-template-columns: 1fr; gap: 3rem; }
            .cpt-grid { grid-template-columns: repeat(2, 1fr); }
            .posts-grid { grid-template-columns: repeat(2, 1fr); }
            .footer-grid { grid-template-columns: 1fr 1fr; gap: 2rem; }
        }
        @media (max-width: 768px) {
            nav { display: none; }
            .hero { padding: 8rem 0 5rem; }
            .hero-stats { flex-direction: column; gap: 1.5rem; }
            .browser-body { grid-template-columns: 1fr; }
            .browser-sidebar { display: none; }
            .stat-cards { grid-template-columns: 1fr 1fr; }
            .features-grid { grid-template-columns: 1fr; }
            .cpt-grid { grid-template-columns: 1fr; }
            .posts-grid { grid-template-columns: 1fr; }
            .posts-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
            .cta-box { padding: 3rem 2rem; }
            .cta-buttons { flex-direction: column; }
            .footer-grid { grid-template-columns: 1fr; gap: 2rem; }
            .footer-bottom { flex-direction: column; gap: 1.5rem; text-align: center; }
            .tech-grid { gap: 2rem; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container header-inner">
            <a href="<?= SITE_URL ?>" class="logo">
                <div class="logo-icon">◉</div>
                <span class="logo-text"><?= esc($siteTitle) ?></span>
            </a>
            <nav>
                <a href="<?= SITE_URL ?>">Home</a>
                <a href="#features">Features</a>
                <a href="#posts">Blog</a>
                <?php foreach (array_slice($pages, 0, 2) as $page): ?>
                    <a href="<?= SITE_URL ?>/<?= esc($page['slug']) ?>"><?= esc($page['title']) ?></a>
                <?php endforeach; ?>
            </nav>
            <a href="<?= ADMIN_URL ?>/" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                Dashboard
            </a>
        </div>
    </header>

    <!-- Hero -->
    <section class="hero">
        <div class="container hero-content">
            <div class="hero-badge">
                <span class="hero-badge-dot"></span>
                <strong>v<?= CMS_VERSION ?></strong> — Custom post types, fields & more
            </div>
            <h1>Build anything with <span class="gradient">VoidForge CMS</span></h1>
            <p class="hero-desc"><?= esc($siteDescription) ?> — A modern, lightning-fast content management system designed for developers and creators.</p>
            <div class="hero-buttons">
                <a href="<?= ADMIN_URL ?>/" class="btn btn-primary btn-lg">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    Open Dashboard
                </a>
                <a href="#features" class="btn btn-outline btn-lg">Explore Features</a>
            </div>
            <div class="hero-stats">
                <div class="hero-stat">
                    <div class="hero-stat-value"><?= $totalPosts ?></div>
                    <div class="hero-stat-label">Posts</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-value"><?= $totalPages ?></div>
                    <div class="hero-stat-label">Pages</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-value"><?= $totalMedia ?></div>
                    <div class="hero-stat-label">Media</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-value"><?= $totalCustomTypes ?></div>
                    <div class="hero-stat-label">Custom Types</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Dashboard Preview -->
    <section class="dashboard-preview">
        <div class="browser-frame">
            <div class="browser-bar">
                <span class="browser-dot r"></span>
                <span class="browser-dot y"></span>
                <span class="browser-dot g"></span>
                <span class="browser-url"><?= $_SERVER['HTTP_HOST'] ?? 'localhost' ?>/admin</span>
            </div>
            <div class="browser-body">
                <div class="browser-sidebar">
                    <div class="sidebar-logo">
                        <div class="sidebar-logo-icon">◉</div>
                        <span>VoidForge</span>
                    </div>
                    <div class="sidebar-menu">
                        <div class="sidebar-item active">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                            Dashboard
                        </div>
                        <div class="sidebar-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            Posts
                        </div>
                        <div class="sidebar-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            Media
                        </div>
                        <div class="sidebar-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                            Users
                        </div>
                    </div>
                    <div class="sidebar-section">
                        <div class="sidebar-section-title">Content</div>
                        <div class="sidebar-menu">
                            <div class="sidebar-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                            Post Types
                        </div>
                        </div>
                    </div>
                </div>
                <div class="browser-main">
                    <div class="browser-header">
                        <div class="browser-title">Dashboard</div>
                        <div class="browser-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            New Post
                        </div>
                    </div>
                    <div class="stat-cards">
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <div class="stat-card-icon purple">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>
                                </div>
                                <div class="stat-card-trend">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg>
                                    +12%
                                </div>
                            </div>
                            <div class="stat-card-value"><?= $totalPosts ?></div>
                            <div class="stat-card-label">Total Posts</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <div class="stat-card-icon green">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                                </div>
                            </div>
                            <div class="stat-card-value"><?= $totalPages ?></div>
                            <div class="stat-card-label">Pages</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <div class="stat-card-icon orange">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                </div>
                            </div>
                            <div class="stat-card-value"><?= $totalMedia ?></div>
                            <div class="stat-card-label">Media Files</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <div class="stat-card-icon pink">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                                </div>
                            </div>
                            <div class="stat-card-value"><?= $totalCustomTypes ?></div>
                            <div class="stat-card-label">Custom Types</div>
                        </div>
                    </div>
                    <div class="browser-table">
                        <div class="browser-table-header">
                            <div>Title</div>
                            <div>Author</div>
                            <div>Date</div>
                            <div>Status</div>
                        </div>
                        <?php if (!empty($latestPosts)): ?>
                            <?php foreach (array_slice($latestPosts, 0, 3) as $post): ?>
                            <div class="browser-table-row">
                                <div class="table-title"><?= esc(substr($post['title'], 0, 30)) ?><?= strlen($post['title']) > 30 ? '...' : '' ?></div>
                                <div>Admin</div>
                                <div><?= date('M j', strtotime($post['created_at'])) ?></div>
                                <div><span class="table-status <?= $post['status'] ?>"><?= ucfirst($post['status']) ?></span></div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="browser-table-row">
                                <div class="table-title">Getting Started with VoidForge</div>
                                <div>Admin</div>
                                <div><?= date('M j') ?></div>
                                <div><span class="table-status published">Published</span></div>
                            </div>
                            <div class="browser-table-row">
                                <div class="table-title">Custom Post Types Guide</div>
                                <div>Admin</div>
                                <div><?= date('M j', strtotime('-1 day')) ?></div>
                                <div><span class="table-status draft">Draft</span></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="features" id="features">
        <div class="container">
            <div class="section-header">
                <div class="section-eyebrow">✨ Features</div>
                <h2 class="section-title">Everything you need to build amazing websites</h2>
                <p class="section-desc">VoidForge CMS combines powerful features with an intuitive interface. No bloat, no complexity — just the tools you need.</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon purple">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                    </div>
                    <h3 class="feature-title">Custom Post Types</h3>
                    <p class="feature-desc">Create products, portfolios, testimonials, or any content structure. Visual builder with automatic admin menus and frontend routing.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon cyan">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                    </div>
                    <h3 class="feature-title">Custom Fields</h3>
                    <p class="feature-desc">14 field types including text, images, dates, colors, selects, and WYSIWYG editors. Attach to any post type instantly.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon green">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                    </div>
                    <h3 class="feature-title">Theme System</h3>
                    <p class="feature-desc">Build themes with pure PHP templates. Template hierarchy, custom tags, and real-time CSS editing with live preview.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon pink">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    </div>
                    <h3 class="feature-title">Media Library</h3>
                    <p class="feature-desc">Drag-and-drop uploads, automatic thumbnails, image optimization, and folder organization. Full diagnostics included.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon orange">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                    </div>
                    <h3 class="feature-title">Developer API</h3>
                    <p class="feature-desc">Hooks, filters, and a plugin architecture. Query posts, access custom fields, and extend functionality with standard PHP.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon blue">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                    </div>
                    <h3 class="feature-title">Auto Updates</h3>
                    <p class="feature-desc">One-click updates with automatic backups and database migrations. ZIP upload support for offline installations.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Code Demo -->
    <section class="code-demo">
        <div class="container">
            <div class="code-demo-grid">
                <div class="code-demo-content">
                    <h2>Built for developers who ship fast</h2>
                    <p>Simple, intuitive APIs that get out of your way. Access custom fields, query posts, and build templates with familiar PHP — no proprietary languages or complex abstractions.</p>
                    <div class="code-features">
                        <div class="code-feature">
                            <div class="code-feature-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                            </div>
                            <div class="code-feature-text">
                                <h4>Custom Fields API</h4>
                                <p>Get, set, and delete custom field values with simple functions.</p>
                            </div>
                        </div>
                        <div class="code-feature">
                            <div class="code-feature-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                            </div>
                            <div class="code-feature-text">
                                <h4>Template Hierarchy</h4>
                                <p>single-product.php → single.php → index.php fallback chain.</p>
                            </div>
                        </div>
                        <div class="code-feature">
                            <div class="code-feature-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                            </div>
                            <div class="code-feature-text">
                                <h4>Zero Dependencies</h4>
                                <p>Pure PHP, no composer, no npm. Just upload and run.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="code-visual">
                    <div class="code-window">
                        <div class="code-tabs">
                            <div class="code-tab active">single-product.php</div>
                            <div class="code-tab">functions.php</div>
                        </div>
                        <div class="code-body">
                            <div class="code-line"><span class="code-line-num">1</span><span class="c">&lt;?php // Get product custom fields</span></div>
                            <div class="code-line"><span class="code-line-num">2</span><span class="v">$price</span> = <span class="f">get_custom_field</span>(<span class="s">'price'</span>, <span class="v">$post</span>[<span class="s">'id'</span>]);</div>
                            <div class="code-line"><span class="code-line-num">3</span><span class="v">$sku</span> = <span class="f">get_custom_field</span>(<span class="s">'sku'</span>, <span class="v">$post</span>[<span class="s">'id'</span>]);</div>
                            <div class="code-line"><span class="code-line-num">4</span><span class="v">$gallery</span> = <span class="f">get_custom_field</span>(<span class="s">'gallery'</span>, <span class="v">$post</span>[<span class="s">'id'</span>]);</div>
                            <div class="code-line"><span class="code-line-num">5</span><span class="k">?&gt;</span></div>
                            <div class="code-line"><span class="code-line-num">6</span></div>
                            <div class="code-line"><span class="code-line-num">7</span><span class="t">&lt;article</span> <span class="k">class</span>=<span class="s">"product"</span><span class="t">&gt;</span></div>
                            <div class="code-line"><span class="code-line-num">8</span>  <span class="t">&lt;h1&gt;</span><span class="k">&lt;?=</span> <span class="f">esc</span>(<span class="v">$post</span>[<span class="s">'title'</span>]) <span class="k">?&gt;</span><span class="t">&lt;/h1&gt;</span></div>
                            <div class="code-line"><span class="code-line-num">9</span>  <span class="t">&lt;p</span> <span class="k">class</span>=<span class="s">"price"</span><span class="t">&gt;</span>$<span class="k">&lt;?=</span> <span class="v">$price</span> <span class="k">?&gt;</span><span class="t">&lt;/p&gt;</span></div>
                            <div class="code-line"><span class="code-line-num">10</span>  <span class="t">&lt;span</span> <span class="k">class</span>=<span class="s">"sku"</span><span class="t">&gt;</span>SKU: <span class="k">&lt;?=</span> <span class="v">$sku</span> <span class="k">?&gt;</span><span class="t">&lt;/span&gt;</span></div>
                            <div class="code-line"><span class="code-line-num">11</span><span class="t">&lt;/article&gt;</span></div>
                            <div class="code-line"><span class="code-line-num">12</span></div>
                            <div class="code-line"><span class="code-line-num">13</span><span class="c">&lt;?php // Query related products</span></div>
                            <div class="code-line"><span class="code-line-num">14</span><span class="v">$related</span> = <span class="f">Post::query</span>([</div>
                            <div class="code-line"><span class="code-line-num">15</span>  <span class="s">'post_type'</span> => <span class="s">'product'</span>,</div>
                            <div class="code-line"><span class="code-line-num">16</span>  <span class="s">'limit'</span> => <span class="n">4</span></div>
                            <div class="code-line"><span class="code-line-num">17</span>]); <span class="k">?&gt;</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Custom Post Types Demo -->
    <section class="cpt-demo">
        <div class="container">
            <div class="section-header">
                <div class="section-eyebrow">🧩 Custom Post Types</div>
                <h2 class="section-title">Build any content structure</h2>
                <p class="section-desc">Create custom post types with custom fields in minutes. Products, portfolios, team members, testimonials — the possibilities are endless.</p>
            </div>
            <div class="cpt-grid">
                <div class="cpt-card">
                    <div class="cpt-icon">🛍️</div>
                    <h3 class="cpt-title">Products</h3>
                    <p class="cpt-desc">E-commerce catalog with pricing and inventory</p>
                    <div class="cpt-fields">
                        <span class="cpt-field">Price</span>
                        <span class="cpt-field">SKU</span>
                        <span class="cpt-field">Gallery</span>
                        <span class="cpt-field">Stock</span>
                    </div>
                </div>
                <div class="cpt-card">
                    <div class="cpt-icon">💼</div>
                    <h3 class="cpt-title">Portfolio</h3>
                    <p class="cpt-desc">Showcase your work with case studies</p>
                    <div class="cpt-fields">
                        <span class="cpt-field">Client</span>
                        <span class="cpt-field">Date</span>
                        <span class="cpt-field">URL</span>
                        <span class="cpt-field">Gallery</span>
                    </div>
                </div>
                <div class="cpt-card">
                    <div class="cpt-icon">👥</div>
                    <h3 class="cpt-title">Team</h3>
                    <p class="cpt-desc">Team members with roles and social links</p>
                    <div class="cpt-fields">
                        <span class="cpt-field">Role</span>
                        <span class="cpt-field">Email</span>
                        <span class="cpt-field">LinkedIn</span>
                        <span class="cpt-field">Photo</span>
                    </div>
                </div>
                <div class="cpt-card">
                    <div class="cpt-icon">⭐</div>
                    <h3 class="cpt-title">Testimonials</h3>
                    <p class="cpt-desc">Customer reviews and social proof</p>
                    <div class="cpt-fields">
                        <span class="cpt-field">Rating</span>
                        <span class="cpt-field">Company</span>
                        <span class="cpt-field">Avatar</span>
                        <span class="cpt-field">Quote</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Posts Section -->
    <section class="posts-section" id="posts">
        <div class="container">
            <div class="posts-header">
                <div>
                    <h2>Latest from the blog</h2>
                    <p>Insights, tutorials, and updates</p>
                </div>
                <?php if (!empty($latestPosts)): ?>
                <a href="#" class="posts-link">
                    View all posts
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </a>
                <?php endif; ?>
            </div>
            <div class="posts-grid">
                <?php if (empty($latestPosts)): ?>
                    <div class="empty-posts">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                        <h3>No posts yet</h3>
                        <p>Create your first blog post to see it here.</p>
                        <a href="<?= ADMIN_URL ?>/post-edit.php" class="btn btn-primary">Create Post</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($latestPosts as $post): 
                        $featuredImage = get_featured_image_url($post['id']);
                        $excerpt = !empty($post['excerpt']) ? $post['excerpt'] : substr(strip_tags($post['content']), 0, 120);
                    ?>
                    <article class="post-card">
                        <div class="post-card-image">
                            <?php if ($featuredImage): ?>
                                <img src="<?= esc($featuredImage) ?>" alt="<?= esc($post['title']) ?>">
                            <?php else: ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            <?php endif; ?>
                        </div>
                        <div class="post-card-body">
                            <div class="post-card-meta">
                                <span>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                    <?= date('M j, Y', strtotime($post['created_at'])) ?>
                                </span>
                            </div>
                            <h3 class="post-card-title">
                                <a href="<?= SITE_URL ?>/post/<?= esc($post['slug']) ?>"><?= esc($post['title']) ?></a>
                            </h3>
                            <p class="post-card-excerpt"><?= esc($excerpt) ?>...</p>
                        </div>
                    </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Tech Stack -->
    <section class="tech-stack">
        <div class="container">
            <div class="tech-grid">
                <div class="tech-item">
                    <svg viewBox="0 0 128 128"><path fill="currentColor" d="M64 0C28.7 0 0 28.7 0 64s28.7 64 64 64 64-28.7 64-64S99.3 0 64 0zm0 118c-29.8 0-54-24.2-54-54S34.2 10 64 10s54 24.2 54 54-24.2 54-54 54z"/></svg>
                    <span>PHP 7.4+</span>
                </div>
                <div class="tech-item">
                    <svg viewBox="0 0 128 128"><path fill="currentColor" d="M64 0C28.7 0 0 28.7 0 64s28.7 64 64 64 64-28.7 64-64S99.3 0 64 0zm0 118c-29.8 0-54-24.2-54-54S34.2 10 64 10s54 24.2 54 54-24.2 54-54 54z"/></svg>
                    <span>MySQL 5.7+</span>
                </div>
                <div class="tech-item">
                    <svg viewBox="0 0 128 128"><path fill="currentColor" d="M64 0C28.7 0 0 28.7 0 64s28.7 64 64 64 64-28.7 64-64S99.3 0 64 0zm0 118c-29.8 0-54-24.2-54-54S34.2 10 64 10s54 24.2 54 54-24.2 54-54 54z"/></svg>
                    <span>HTML5</span>
                </div>
                <div class="tech-item">
                    <svg viewBox="0 0 128 128"><path fill="currentColor" d="M64 0C28.7 0 0 28.7 0 64s28.7 64 64 64 64-28.7 64-64S99.3 0 64 0zm0 118c-29.8 0-54-24.2-54-54S34.2 10 64 10s54 24.2 54 54-24.2 54-54 54z"/></svg>
                    <span>CSS3</span>
                </div>
                <div class="tech-item">
                    <svg viewBox="0 0 128 128"><path fill="currentColor" d="M64 0C28.7 0 0 28.7 0 64s28.7 64 64 64 64-28.7 64-64S99.3 0 64 0zm0 118c-29.8 0-54-24.2-54-54S34.2 10 64 10s54 24.2 54 54-24.2 54-54 54z"/></svg>
                    <span>JavaScript</span>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta">
        <div class="container">
            <div class="cta-box">
                <h2>Ready to start building?</h2>
                <p>Jump into the dashboard and create something amazing. No limits, no restrictions.</p>
                <div class="cta-buttons">
                    <a href="<?= ADMIN_URL ?>/" class="btn btn-white btn-lg">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        Open Dashboard
                    </a>
                </div>
                <p class="cta-note">VoidForge CMS v<?= CMS_VERSION ?> • 100% Free & Open Source</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <div class="footer-logo">
                        <div class="footer-logo-icon">◉</div>
                        <span><?= esc($siteTitle) ?></span>
                    </div>
                    <p><?= esc($siteDescription) ?></p>
                </div>
                <div class="footer-col">
                    <h4>Navigation</h4>
                    <ul>
                        <li><a href="<?= SITE_URL ?>">Home</a></li>
                        <li><a href="#features">Features</a></li>
                        <li><a href="#posts">Blog</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Pages</h4>
                    <ul>
                        <?php foreach (array_slice($pages, 0, 4) as $page): ?>
                        <li><a href="<?= SITE_URL ?>/<?= esc($page['slug']) ?>"><?= esc($page['title']) ?></a></li>
                        <?php endforeach; ?>
                        <?php if (empty($pages)): ?>
                        <li><a href="<?= ADMIN_URL ?>/post-edit.php?type=page">Create a page</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Admin</h4>
                    <ul>
                        <li><a href="<?= ADMIN_URL ?>/">Dashboard</a></li>
                        <li><a href="<?= ADMIN_URL ?>/posts.php">Posts</a></li>
                        <li><a href="<?= ADMIN_URL ?>/media.php">Media</a></li>
                        <li><a href="<?= ADMIN_URL ?>/settings.php">Settings</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p class="footer-copy">© <?= date('Y') ?> <?= esc($siteTitle) ?>. Powered by VoidForge CMS v<?= CMS_VERSION ?>.</p>
                <div class="footer-links">
                    <a href="<?= ADMIN_URL ?>/">Admin</a>
                    <a href="#">Privacy</a>
                    <a href="#">Terms</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(a => {
            a.addEventListener('click', e => {
                e.preventDefault();
                const target = document.querySelector(a.getAttribute('href'));
                if (target) {
                    const offset = 80;
                    const top = target.getBoundingClientRect().top + window.pageYOffset - offset;
                    window.scrollTo({ top, behavior: 'smooth' });
                }
            });
        });
    </script>
</body>
</html>
