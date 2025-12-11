<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle ?? flavor_site_title(true)) ?></title>
    <meta name="description" content="<?= esc($pageDescription ?? getOption('site_tagline', '')) ?>">
    
    <!-- Favicon -->
    <link rel="icon" href="<?= SITE_URL ?>/favicon.ico">
    
    <!-- Preconnect for performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Theme Styles -->
    <link rel="stylesheet" href="<?= get_theme_url() ?>/style.css?ver=1.0.0">
    <?= Plugin::renderStyles() ?>
    
    <!-- Custom CSS from admin -->
    <?php if ($customCss = getOption('custom_css')): ?>
    <style><?= $customCss ?></style>
    <?php endif; ?>
    
    <?php Plugin::doAction('wp_head'); ?>
</head>
<body class="<?= $bodyClass ?? '' ?>">
    
<header class="site-header">
    <div class="container">
        <div class="header-inner">
            <a href="<?= SITE_URL ?>" class="site-logo">
                <svg class="logo-icon" width="32" height="32" viewBox="0 0 32 32" fill="none">
                    <defs>
                        <linearGradient id="logoGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#8b5cf6"/>
                            <stop offset="100%" style="stop-color:#06b6d4"/>
                        </linearGradient>
                    </defs>
                    <path d="M5 5 L16 27 L27 5" fill="none" stroke="url(#logoGradient)" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="16" cy="14" r="3" fill="url(#logoGradient)"/>
                </svg>
                <span class="logo-text"><?= esc(getOption('site_title', 'VoidForge')) ?></span>
            </a>
            
            <nav class="site-nav">
                <button class="nav-toggle" aria-label="Toggle menu" onclick="document.querySelector('.nav-menu').classList.toggle('open')">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <line x1="3" y1="12" x2="21" y2="12"/>
                        <line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>
                
                <ul class="nav-menu">
                    <?php foreach (flavor_nav_menu() as $item): ?>
                    <li>
                        <a href="<?= esc($item['url']) ?>" <?= $item['active'] ? 'class="active"' : '' ?><?= isset($item['target']) && $item['target'] !== '_self' ? ' target="' . esc($item['target']) . '"' : '' ?>>
                            <?= esc($item['label']) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </div>
    </div>
</header>

<main class="site-main">
