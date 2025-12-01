<?php
/**
 * Admin Header - Forge CMS
 */

defined('CMS_ROOT') or die('Direct access not allowed');

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$flash = getFlash();
$currentUser = User::current();

// Get gravatar URL
function getGravatar($email, $size = 80) {
    $hash = md5(strtolower(trim($email)));
    return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d=mp";
}
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
    <style>
    /* Enhanced Header Styles */
    .admin-header {
        height: var(--header-height);
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 1.5rem;
        position: sticky;
        top: 0;
        z-index: 50;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    
    .header-left {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .sidebar-toggle {
        display: none;
        width: 40px;
        height: 40px;
        border: none;
        background: var(--bg-card);
        border-radius: var(--border-radius);
        cursor: pointer;
        color: var(--text-secondary);
        align-items: center;
        justify-content: center;
        transition: all 0.15s;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    }
    
    .sidebar-toggle:hover {
        background: var(--forge-primary);
        color: #fff;
    }
    
    @media (max-width: 1024px) {
        .sidebar-toggle { display: flex; }
    }
    
    .header-breadcrumb {
        font-size: 0.875rem;
        color: var(--text-secondary);
    }
    
    .header-breadcrumb a {
        color: var(--text-muted);
        text-decoration: none;
    }
    
    .header-breadcrumb a:hover {
        color: var(--forge-primary);
    }
    
    .header-right {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .header-action {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: var(--border-radius);
        color: var(--text-secondary);
        text-decoration: none;
        transition: all 0.15s;
        position: relative;
    }
    
    .header-action:hover {
        background: rgba(99, 102, 241, 0.1);
        color: var(--forge-primary);
    }
    
    .header-action svg {
        width: 20px;
        height: 20px;
    }
    
    /* User Dropdown */
    .user-dropdown {
        position: relative;
    }
    
    .user-dropdown-trigger {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.375rem 0.75rem 0.375rem 0.375rem;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 50px;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .user-dropdown-trigger:hover {
        border-color: var(--forge-primary);
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.15);
    }
    
    .user-dropdown.active .user-dropdown-trigger {
        border-color: var(--forge-primary);
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.2);
    }
    
    .user-avatar-img {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--bg-card-header);
    }
    
    .user-dropdown-info {
        display: flex;
        flex-direction: column;
        text-align: left;
        line-height: 1.3;
    }
    
    .user-dropdown-name {
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--text-primary);
    }
    
    .user-dropdown-role {
        font-size: 0.6875rem;
        color: var(--text-muted);
        text-transform: capitalize;
    }
    
    .user-dropdown-chevron {
        color: var(--text-muted);
        transition: transform 0.2s;
    }
    
    .user-dropdown.active .user-dropdown-chevron {
        transform: rotate(180deg);
    }
    
    .user-dropdown-menu {
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        width: 220px;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius-lg);
        box-shadow: 0 10px 40px rgba(0,0,0,0.12);
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.2s;
        z-index: 100;
        overflow: hidden;
    }
    
    .user-dropdown.active .user-dropdown-menu {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
    
    .user-dropdown-header {
        padding: 1rem;
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.08) 0%, rgba(139, 92, 246, 0.05) 100%);
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .user-dropdown-header img {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .user-dropdown-header-info h4 {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.125rem;
    }
    
    .user-dropdown-header-info span {
        font-size: 0.75rem;
        color: var(--text-muted);
    }
    
    .user-dropdown-menu a {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        color: var(--text-secondary);
        text-decoration: none;
        font-size: 0.875rem;
        transition: all 0.15s;
    }
    
    .user-dropdown-menu a:hover {
        background: var(--bg-hover);
        color: var(--forge-primary);
    }
    
    .user-dropdown-menu a svg {
        width: 18px;
        height: 18px;
        opacity: 0.7;
    }
    
    .user-dropdown-menu a:hover svg {
        opacity: 1;
    }
    
    .user-dropdown-divider {
        height: 1px;
        background: var(--border-color);
        margin: 0.25rem 0;
    }
    
    .user-dropdown-menu a.logout {
        color: var(--forge-danger);
    }
    
    .user-dropdown-menu a.logout:hover {
        background: rgba(239, 68, 68, 0.08);
    }
    
    @media (max-width: 640px) {
        .user-dropdown-info { display: none; }
        .user-dropdown-trigger { padding: 0.25rem; border-radius: 50%; }
    }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="admin-wrapper">
        <?php include ADMIN_PATH . '/includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <header class="admin-header">
                <div class="header-left">
                    <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Toggle sidebar">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <line x1="3" y1="18" x2="21" y2="18"></line>
                        </svg>
                    </button>
                    <div class="header-breadcrumb">
                        <a href="<?= ADMIN_URL ?>/">Dashboard</a>
                        <?php if ($currentPage !== 'index'): ?>
                            <span style="margin: 0 0.5rem; color: var(--text-muted);">/</span>
                            <span><?= esc($pageTitle ?? ucfirst($currentPage)) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="header-right">
                    <a href="<?= SITE_URL ?>" target="_blank" class="header-action" title="View Site">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="2" y1="12" x2="22" y2="12"></line>
                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                        </svg>
                    </a>
                    
                    <div class="user-dropdown" id="userDropdown">
                        <div class="user-dropdown-trigger" onclick="toggleUserDropdown()">
                            <img src="<?= getGravatar($currentUser['email'] ?? '', 68) ?>" alt="" class="user-avatar-img">
                            <div class="user-dropdown-info">
                                <span class="user-dropdown-name"><?= esc($currentUser['display_name'] ?? 'User') ?></span>
                                <span class="user-dropdown-role"><?= esc($currentUser['role'] ?? 'User') ?></span>
                            </div>
                            <svg class="user-dropdown-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </div>
                        <div class="user-dropdown-menu">
                            <div class="user-dropdown-header">
                                <img src="<?= getGravatar($currentUser['email'] ?? '', 88) ?>" alt="">
                                <div class="user-dropdown-header-info">
                                    <h4><?= esc($currentUser['display_name'] ?? 'User') ?></h4>
                                    <span><?= esc($currentUser['email'] ?? '') ?></span>
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
                                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
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
                <?php if ($flash): ?>
                    <div class="alert alert-<?= esc($flash['type']) ?>">
                        <?= esc($flash['message']) ?>
                    </div>
                <?php endif; ?>
<script>
// Sidebar toggle
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('adminSidebar');
const sidebarOverlay = document.getElementById('sidebarOverlay');

if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('open');
        document.body.classList.toggle('sidebar-open');
    });
}

if (sidebarOverlay) {
    sidebarOverlay.addEventListener('click', function() {
        sidebar.classList.remove('open');
        document.body.classList.remove('sidebar-open');
    });
}

// User dropdown
function toggleUserDropdown() {
    document.getElementById('userDropdown').classList.toggle('active');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('userDropdown');
    if (!dropdown.contains(e.target)) {
        dropdown.classList.remove('active');
    }
});
</script>
