<?php
/**
 * Admin Header - Forge CMS
 */

defined('CMS_ROOT') or die('Direct access not allowed');

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle ?? 'Dashboard') ?> - <?= CMS_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= ADMIN_URL ?>/assets/css/admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include ADMIN_PATH . '/includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <header class="admin-header">
                <div class="header-left">
                    <button class="btn btn-secondary btn-sm sidebar-toggle" id="sidebarToggle">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <line x1="3" y1="18" x2="21" y2="18"></line>
                        </svg>
                    </button>
                </div>
                <div class="header-right">
                    <a href="<?= SITE_URL ?>" target="_blank" class="header-link">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                            <polyline points="15 3 21 3 21 9"></polyline>
                            <line x1="10" y1="14" x2="21" y2="3"></line>
                        </svg>
                        View Site
                    </a>
                    <div class="dropdown" id="userDropdown">
                        <button class="user-menu" onclick="this.parentElement.classList.toggle('active')">
                            <span class="user-avatar"><?= strtoupper(substr(User::current()['display_name'] ?? 'U', 0, 1)) ?></span>
                            <div class="user-info">
                                <span class="user-name"><?= esc(User::current()['display_name'] ?? 'User') ?></span>
                                <span class="user-role"><?= ucfirst(User::current()['role'] ?? 'User') ?></span>
                            </div>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>
                        <div class="dropdown-menu">
                            <a href="<?= ADMIN_URL ?>/profile.php" class="dropdown-item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                Profile
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="<?= ADMIN_URL ?>/logout.php" class="dropdown-item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                    <polyline points="16 17 21 12 16 7"></polyline>
                                    <line x1="21" y1="12" x2="9" y2="12"></line>
                                </svg>
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <div class="admin-content">
                <?php if ($flash): ?>
                    <div class="alert alert-<?= esc($flash['type']) ?>">
                        <?= esc($flash['message']) ?>
                    </div>
                <?php endif; ?>
