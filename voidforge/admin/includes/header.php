<?php
/**
 * Admin Header - VoidForge CMS v1.0.10
 * With dynamic theme support
 */

defined('CMS_ROOT') or die('Direct access not allowed');

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentUser = User::current();

// Helper function to adjust color brightness
function adjustBrightness($hex, $steps) {
    $hex = ltrim($hex, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    $r = max(0, min(255, $r + $steps));
    $g = max(0, min(255, $g + $steps));
    $b = max(0, min(255, $b + $steps));
    
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

// Helper function to convert hex to rgba
function hexToRgba($hex, $alpha = 1) {
    $hex = ltrim($hex, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    return "rgba($r, $g, $b, $alpha)";
}

// Load admin theme settings
$adminTheme = getOption('admin_theme', [
    'color_scheme' => 'default',
    'font' => 'inter',
    'icon_style' => 'outlined',
    'sidebar_compact' => false,
    'animations' => true,
    'custom_primary' => '#6366f1',
    'custom_secondary' => '#8b5cf6',
    'custom_sidebar' => '#0f172a',
]);

// Color schemes - all use dark sidebar gradients for good text contrast
$colorSchemes = [
    'default' => ['name' => 'Indigo', 'primary' => '#6366f1', 'secondary' => '#8b5cf6', 'sidebar_bg' => 'linear-gradient(180deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%)', 'preview' => ['#6366f1', '#8b5cf6', '#1e1b4b']],
    'ocean' => ['name' => 'Ocean', 'primary' => '#0ea5e9', 'secondary' => '#06b6d4', 'sidebar_bg' => 'linear-gradient(180deg, #0c1929 0%, #0c4a6e 50%, #164e63 100%)', 'preview' => ['#0ea5e9', '#06b6d4', '#0c4a6e']],
    'emerald' => ['name' => 'Emerald', 'primary' => '#10b981', 'secondary' => '#14b8a6', 'sidebar_bg' => 'linear-gradient(180deg, #022c22 0%, #064e3b 50%, #065f46 100%)', 'preview' => ['#10b981', '#14b8a6', '#064e3b']],
    'rose' => ['name' => 'Rose', 'primary' => '#f43f5e', 'secondary' => '#ec4899', 'sidebar_bg' => 'linear-gradient(180deg, #1a0a10 0%, #4c0519 50%, #701a2e 100%)', 'preview' => ['#f43f5e', '#ec4899', '#4c0519']],
    'amber' => ['name' => 'Amber', 'primary' => '#f59e0b', 'secondary' => '#fbbf24', 'sidebar_bg' => 'linear-gradient(180deg, #1a1207 0%, #451a03 50%, #5c2a0a 100%)', 'preview' => ['#f59e0b', '#fbbf24', '#451a03']],
    'slate' => ['name' => 'Slate', 'primary' => '#64748b', 'secondary' => '#94a3b8', 'sidebar_bg' => 'linear-gradient(180deg, #0f172a 0%, #1e293b 50%, #334155 100%)', 'preview' => ['#64748b', '#94a3b8', '#1e293b']],
];

// Handle custom color scheme
if ($adminTheme['color_scheme'] === 'custom') {
    $customPrimary = $adminTheme['custom_primary'] ?? '#6366f1';
    $customSecondary = $adminTheme['custom_secondary'] ?? '#8b5cf6';
    $customSidebar = $adminTheme['custom_sidebar'] ?? '#0f172a';
    $scheme = [
        'name' => 'Custom',
        'primary' => $customPrimary,
        'secondary' => $customSecondary,
        'sidebar_bg' => $customSidebar,
        'preview' => [$customPrimary, $customSecondary, $customSidebar]
    ];
} else {
    $scheme = $colorSchemes[$adminTheme['color_scheme']] ?? $colorSchemes['default'];
}

$fonts = [
    'inter' => ['name' => 'Inter', 'family' => "'Inter', -apple-system, BlinkMacSystemFont, sans-serif", 'google' => 'Inter:wght@400;500;600;700;800'],
    'poppins' => ['name' => 'Poppins', 'family' => "'Poppins', sans-serif", 'google' => 'Poppins:wght@400;500;600;700'],
    'nunito' => ['name' => 'Nunito', 'family' => "'Nunito', sans-serif", 'google' => 'Nunito:wght@400;500;600;700'],
    'roboto' => ['name' => 'Roboto', 'family' => "'Roboto', sans-serif", 'google' => 'Roboto:wght@400;500;700'],
    'source-sans' => ['name' => 'Source Sans', 'family' => "'Source Sans 3', sans-serif", 'google' => 'Source+Sans+3:wght@400;500;600;700'],
    'dm-sans' => ['name' => 'DM Sans', 'family' => "'DM Sans', sans-serif", 'google' => 'DM+Sans:wght@400;500;600;700'],
];

$iconStyles = [
    'outlined' => ['name' => 'Outlined', 'stroke_width' => '2'],
    'light' => ['name' => 'Light', 'stroke_width' => '1.5'],
    'bold' => ['name' => 'Bold', 'stroke_width' => '2.5'],
];

$font = $fonts[$adminTheme['font']] ?? $fonts['inter'];
$iconWeight = $iconStyles[$adminTheme['icon_style']]['stroke_width'] ?? '2';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= esc($adminTheme['color_scheme']) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle ?? 'Admin') ?> - <?= CMS_NAME ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= ADMIN_URL ?>/assets/images/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=<?= $font['google'] ?>&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= ADMIN_URL ?>/assets/css/admin.css">
    <style>
        :root {
            --forge-primary: <?= $scheme['primary'] ?>;
            --forge-secondary: <?= $scheme['secondary'] ?>;
            --forge-primary-dark: <?= adjustBrightness($scheme['primary'], -20) ?>;
            --forge-primary-light: <?= adjustBrightness($scheme['primary'], 30) ?>;
            --forge-shadow-color: <?= hexToRgba($scheme['primary'], 0.25) ?>;
            --forge-shadow-color-hover: <?= hexToRgba($scheme['primary'], 0.35) ?>;
            --sidebar-gradient: <?= $scheme['sidebar_bg'] ?>;
            --font-family: <?= $font['family'] ?>;
            --icon-stroke-width: <?= $iconWeight ?>;
        }
        body { font-family: var(--font-family); }
        
        /* Apply icon stroke width to all SVG icons */
        .admin-sidebar svg:not(.logo-icon svg),
        .nav-icon svg,
        .admin-header svg,
        .settings-card-icon svg,
        .theme-section-icon svg,
        .btn svg,
        .action-btn svg,
        .stat-icon svg,
        .diag-icon svg,
        .upload-card-title svg,
        .card-title svg,
        .pt-card-icon svg,
        .pt-btn svg,
        .btn-regen svg,
        .btn-delete svg,
        .btn-new-type svg,
        .btn-action svg {
            stroke-width: var(--icon-stroke-width, 2) !important;
        }
        
        /* Theme-aware buttons */
        .btn-primary,
        .btn-regen,
        .btn-new-type,
        .btn-save,
        .btn-install {
            background: linear-gradient(135deg, var(--forge-primary) 0%, var(--forge-secondary) 100%) !important;
            box-shadow: 0 4px 15px <?= hexToRgba($scheme['primary'], 0.35) ?> !important;
        }
        .btn-primary:hover,
        .btn-regen:hover,
        .btn-new-type:hover,
        .btn-save:hover,
        .btn-install:hover {
            box-shadow: 0 6px 20px <?= hexToRgba($scheme['primary'], 0.45) ?> !important;
        }
        
        /* Theme-aware stat icons */
        .stat-icon:first-of-type,
        .stat-card:first-child .stat-icon {
            background: linear-gradient(135deg, var(--forge-primary), var(--forge-secondary)) !important;
        }
        
        /* Theme-aware secondary buttons */
        .btn-secondary:hover,
        .btn-cancel:hover {
            border-color: var(--forge-primary) !important;
            color: var(--forge-primary) !important;
        }
        
        <?php if (!($adminTheme['animations'] ?? true)): ?>
        *, *::before, *::after { transition: none !important; animation: none !important; }
        <?php endif; ?>
        <?php if ($adminTheme['sidebar_compact'] ?? false): ?>
        .admin-sidebar { --sidebar-width: 220px; }
        .nav-item { padding: 0.5rem 0.75rem; }
        .sidebar-logo .logo-icon svg { width: 24px; height: 24px; }
        <?php endif; ?>
    </style>
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
