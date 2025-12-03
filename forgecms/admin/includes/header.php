<?php
/**
 * Admin Header - Forge CMS v1.0.4
 */

defined('CMS_ROOT') or die('Direct access not allowed');

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentUser = User::current();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle ?? 'Admin') ?> - <?= CMS_NAME ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= ADMIN_URL ?>/assets/images/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= ADMIN_URL ?>/assets/css/admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <div class="sidebar-overlay" onclick="toggleSidebar()"></div>
        
        <?php include ADMIN_PATH . '/includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <header class="admin-header">
                <div class="header-left">
                    <button type="button" class="sidebar-toggle" onclick="toggleSidebar()">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <line x1="3" y1="18" x2="21" y2="18"></line>
                        </svg>
                    </button>
                    <div class="header-breadcrumb">
                        <a href="<?= ADMIN_URL ?>/">Dashboard</a>
                        <?php if ($currentPage !== 'index'): ?>
                            <span> / <?= esc($pageTitle ?? ucfirst($currentPage)) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="header-right">
                    <a href="<?= SITE_URL ?>" target="_blank" class="header-action" title="View Site">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                            <polyline points="15 3 21 3 21 9"></polyline>
                            <line x1="10" y1="14" x2="21" y2="3"></line>
                        </svg>
                    </a>
                    
                    <div class="user-dropdown" id="userDropdown">
                        <div class="user-dropdown-trigger" onclick="toggleUserDropdown()">
                            <img src="<?= getGravatarUrl($currentUser['email'] ?? '', 68) ?>" alt="<?= esc($currentUser['display_name'] ?? $currentUser['username']) ?>" class="user-avatar-img">
                            <div class="user-dropdown-info">
                                <span class="user-dropdown-name"><?= esc($currentUser['display_name'] ?? $currentUser['username']) ?></span>
                                <span class="user-dropdown-role"><?= esc($currentUser['role']) ?></span>
                            </div>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="user-dropdown-chevron">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </div>
                        <div class="user-dropdown-menu">
                            <div class="user-dropdown-header">
                                <img src="<?= getGravatarUrl($currentUser['email'] ?? '', 88) ?>" alt="">
                                <div class="user-dropdown-header-info">
                                    <h4><?= esc($currentUser['display_name'] ?? $currentUser['username']) ?></h4>
                                    <span><?= esc($currentUser['email']) ?></span>
                                </div>
                            </div>
                            <a href="<?= ADMIN_URL ?>/profile.php">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                Edit Profile
                            </a>
                            <a href="<?= ADMIN_URL ?>/settings.php">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="3"></circle>
                                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4"></path>
                                </svg>
                                Settings
                            </a>
                            <div class="user-dropdown-divider"></div>
                            <a href="<?= ADMIN_URL ?>/logout.php" class="logout">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                    <polyline points="16 17 21 12 16 7"></polyline>
                                    <line x1="21" y1="12" x2="9" y2="12"></line>
                                </svg>
                                Sign Out
                            </a>
                        </div>
                    </div>
                </div>
            </header>
            
            <div class="admin-content">
                <?php if ($flash = getFlash('success')): ?>
                    <div class="alert alert-success"><?= esc($flash) ?></div>
                <?php endif; ?>
                <?php if ($flash = getFlash('error')): ?>
                    <div class="alert alert-error"><?= esc($flash) ?></div>
                <?php endif; ?>
