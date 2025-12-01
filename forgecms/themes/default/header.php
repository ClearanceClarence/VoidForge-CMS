<?php
/**
 * Theme Header - Forge CMS
 */

defined('CMS_ROOT') or die('Direct access not allowed');

$siteTitle = getOption('site_title', 'My Site');
$siteDescription = getOption('site_description', '');
$customCss = getOption('custom_frontend_css', '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? esc($pageTitle) . ' - ' : '' ?><?= esc($siteTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= THEME_URL ?>/assets/css/theme.css">
    <?php if ($customCss): ?>
    <style id="custom-frontend-css"><?= $customCss ?></style>
    <?php endif; ?>
</head>
<body>
    <div class="page-wrapper">
        <nav class="navbar scrolled">
            <div class="container">
                <a href="<?= SITE_URL ?>" class="nav-logo">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                        <polyline points="2 17 12 22 22 17"></polyline>
                        <polyline points="2 12 12 17 22 12"></polyline>
                    </svg>
                    <span><?= esc($siteTitle) ?></span>
                </a>
                <div class="nav-links">
                    <a href="<?= SITE_URL ?>">Home</a>
                    <?php
                    $pages = Post::query([
                        'post_type' => 'page',
                        'status' => 'published',
                        'orderby' => 'menu_order',
                        'order' => 'ASC',
                        'limit' => 5
                    ]);
                    foreach ($pages as $navPage):
                    ?>
                        <a href="<?= esc(Post::permalink($navPage)) ?>"><?= esc($navPage['title']) ?></a>
                    <?php endforeach; ?>
                    <a href="<?= ADMIN_URL ?>" class="btn btn-primary btn-sm">Dashboard</a>
                </div>
            </div>
        </nav>
        <main class="page-content">
            <div class="container">
