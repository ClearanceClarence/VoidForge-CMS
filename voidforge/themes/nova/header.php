<?php
/**
 * Nova Theme - Header
 */
defined('CMS_ROOT') or die;

$siteTitle = getOption('site_title', 'VoidForge');
$siteDescription = getOption('site_description', '');
$pageTitle = $post['title'] ?? $siteTitle;
$themeUrl = THEMES_URL . '/' . Theme::getActive();
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
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $themeUrl ?>/style.css">
    <?php
    $frontendCss = getOption('frontend_custom_css', '');
    if (!empty($frontendCss)): ?>
    <style><?= $frontendCss ?></style>
    <?php endif; ?>
    <?php Plugin::doAction('vf_head'); ?>
</head>
<body class="<?= isset($bodyClass) ? esc($bodyClass) : '' ?>">
    <!-- Cosmic Background -->
    <div class="cosmic-bg">
        <div class="nebula nebula-1"></div>
        <div class="nebula nebula-2"></div>
    </div>
    
    <!-- Header -->
    <header class="site-header" id="header">
        <div class="header-inner">
            <a href="<?= SITE_URL ?>" class="logo">
                <svg viewBox="0 0 32 32" fill="none" width="36" height="36">
                    <defs>
                        <linearGradient id="logoGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#a855f7"/>
                            <stop offset="100%" stop-color="#06b6d4"/>
                        </linearGradient>
                        <linearGradient id="logoInner" x1="50%" y1="0%" x2="50%" y2="100%">
                            <stop offset="0%" stop-color="#e9d5ff"/>
                            <stop offset="100%" stop-color="#a855f7"/>
                        </linearGradient>
                    </defs>
                    <path d="M5 5 L16 27 L27 5" fill="none" stroke="url(#logoGrad)" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="16" cy="14" r="3.5" fill="url(#logoInner)"/>
                    <circle cx="16" cy="14" r="1.5" fill="#fff"/>
                </svg>
                <span class="logo-text"><?= esc($siteTitle) ?></span>
            </a>
            
            <nav class="nav-links">
                <a href="<?= ADMIN_URL ?>/" class="btn btn-primary btn-sm">Dashboard</a>
            </nav>
            
            <button class="mobile-menu" id="mobileMenu" aria-label="Menu">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
        </div>
    </header>
    
    <main class="site-main">
