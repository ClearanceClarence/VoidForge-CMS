<?php
/**
 * Admin Sidebar - Forge CMS v1.0.8
 * With submenu support and dynamic menu registration
 */

defined('CMS_ROOT') or die('Direct access not allowed');

// Initialize default menus
initDefaultAdminMenus();

// Get post types for content menu
$postTypes = Post::getTypes();
?>
<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-glow"></div>
    <div class="sidebar-glow-2"></div>
    
    <div class="sidebar-inner">
        <div class="sidebar-header">
            <a href="<?= ADMIN_URL ?>/" class="sidebar-logo">
                <div class="logo-icon">
                    <svg width="28" height="28" viewBox="0 0 32 32" fill="none">
                        <defs>
                            <linearGradient id="sidebarLogoGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#6366f1"/>
                                <stop offset="100%" style="stop-color:#8b5cf6"/>
                            </linearGradient>
                        </defs>
                        <rect x="2" y="2" width="28" height="28" rx="6" fill="url(#sidebarLogoGradient)"/>
                        <path d="M9 7 L9 25 L13 25 L13 17 L21 17 L21 13 L13 13 L13 11 L23 11 L23 7 Z" fill="white"/>
                        <circle cx="22" cy="21" r="2" fill="white" opacity="0.8"/>
                        <circle cx="25" cy="18" r="1.5" fill="white" opacity="0.6"/>
                    </svg>
                </div>
                <div class="logo-text">
                    <span class="logo-name"><?= CMS_NAME ?></span>
                    <span class="logo-version">v<?= CMS_VERSION ?></span>
                </div>
            </a>
        </div>

        <nav class="sidebar-nav">
            <!-- Dashboard -->
            <div class="nav-section">
                <a href="<?= ADMIN_URL ?>/" class="nav-item <?= $currentPage === 'index' ? 'active' : '' ?>">
                    <div class="nav-icon">
                        <?= getAdminMenuIcon('dashboard') ?>
                    </div>
                    <span class="nav-label">Dashboard</span>
                    <?php if ($currentPage === 'index'): ?><div class="nav-indicator"></div><?php endif; ?>
                </a>
            </div>

            <!-- Content Section -->
            <div class="nav-section">
                <div class="nav-section-header">
                    <span class="nav-section-title">Content</span>
                    <div class="nav-section-line"></div>
                </div>
                
                <?php foreach ($postTypes as $type => $config): ?>
                    <?php if ($config['public']): ?>
                        <a href="<?= ADMIN_URL ?>/posts.php?type=<?= $type ?>" class="nav-item <?= $currentPage === 'posts' && ($_GET['type'] ?? 'post') === $type ? 'active' : '' ?>">
                            <div class="nav-icon">
                                <?= getAdminMenuIcon($config['icon'] ?? 'file') ?>
                            </div>
                            <span class="nav-label"><?= esc($config['label']) ?></span>
                            <?php if ($currentPage === 'posts' && ($_GET['type'] ?? 'post') === $type): ?><div class="nav-indicator"></div><?php endif; ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>

                <!-- Media with submenu -->
                <?php 
                $mediaExpanded = in_array($currentPage, ['media', 'thumbnails']);
                ?>
                <div class="nav-item-group <?= $mediaExpanded ? 'expanded' : '' ?>">
                    <button type="button" class="nav-item nav-item-parent <?= $mediaExpanded ? 'active' : '' ?>" onclick="toggleSubmenu(this)">
                        <div class="nav-icon">
                            <?= getAdminMenuIcon('image') ?>
                        </div>
                        <span class="nav-label">Media</span>
                        <svg class="nav-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <div class="nav-submenu">
                        <a href="<?= ADMIN_URL ?>/media.php" class="nav-subitem <?= $currentPage === 'media' ? 'active' : '' ?>">
                            Library
                        </a>
                        <?php if (User::isAdmin()): ?>
                        <a href="<?= ADMIN_URL ?>/thumbnails.php" class="nav-subitem <?= $currentPage === 'thumbnails' ? 'active' : '' ?>">
                            Thumbnails
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (User::isAdmin()): ?>
            <!-- Design Section -->
            <div class="nav-section">
                <div class="nav-section-header">
                    <span class="nav-section-title">Design</span>
                    <div class="nav-section-line"></div>
                </div>
                
                <a href="<?= ADMIN_URL ?>/customize.php" class="nav-item nav-item-featured <?= $currentPage === 'customize' ? 'active' : '' ?>">
                    <div class="nav-icon">
                        <?= getAdminMenuIcon('palette') ?>
                    </div>
                    <span class="nav-label">Customize</span>
                    <span class="nav-badge">Live</span>
                    <?php if ($currentPage === 'customize'): ?><div class="nav-indicator"></div><?php endif; ?>
                </a>
            </div>

            <!-- Admin Section -->
            <div class="nav-section">
                <div class="nav-section-header">
                    <span class="nav-section-title">Admin</span>
                    <div class="nav-section-line"></div>
                </div>
                
                <a href="<?= ADMIN_URL ?>/users.php" class="nav-item <?= $currentPage === 'users' ? 'active' : '' ?>">
                    <div class="nav-icon">
                        <?= getAdminMenuIcon('users') ?>
                    </div>
                    <span class="nav-label">Users</span>
                    <?php if ($currentPage === 'users'): ?><div class="nav-indicator"></div><?php endif; ?>
                </a>

                <!-- Settings with submenu -->
                <?php 
                $settingsExpanded = in_array($currentPage, ['settings', 'post-types', 'admin-theme']);
                ?>
                <div class="nav-item-group <?= $settingsExpanded ? 'expanded' : '' ?>">
                    <button type="button" class="nav-item nav-item-parent <?= $settingsExpanded ? 'active' : '' ?>" onclick="toggleSubmenu(this)">
                        <div class="nav-icon">
                            <?= getAdminMenuIcon('settings') ?>
                        </div>
                        <span class="nav-label">Settings</span>
                        <svg class="nav-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <div class="nav-submenu">
                        <a href="<?= ADMIN_URL ?>/settings.php" class="nav-subitem <?= $currentPage === 'settings' ? 'active' : '' ?>">
                            General
                        </a>
                        <a href="<?= ADMIN_URL ?>/post-types.php" class="nav-subitem <?= $currentPage === 'post-types' ? 'active' : '' ?>">
                            Post Types
                        </a>
                        <a href="<?= ADMIN_URL ?>/admin-theme.php" class="nav-subitem <?= $currentPage === 'admin-theme' ? 'active' : '' ?>">
                            Admin Theme
                        </a>
                    </div>
                </div>

                <!-- Tools with submenu -->
                <?php 
                $toolsExpanded = in_array($currentPage, ['update', 'plugins']);
                ?>
                <div class="nav-item-group <?= $toolsExpanded ? 'expanded' : '' ?>">
                    <button type="button" class="nav-item nav-item-parent <?= $toolsExpanded ? 'active' : '' ?>" onclick="toggleSubmenu(this)">
                        <div class="nav-icon">
                            <?= getAdminMenuIcon('tool') ?>
                        </div>
                        <span class="nav-label">Tools</span>
                        <svg class="nav-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <div class="nav-submenu">
                        <a href="<?= ADMIN_URL ?>/update.php" class="nav-subitem <?= $currentPage === 'update' ? 'active' : '' ?>">
                            Update
                        </a>
                        <a href="<?= ADMIN_URL ?>/plugins.php" class="nav-subitem <?= $currentPage === 'plugins' ? 'active' : '' ?>">
                            Plugins
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </nav>
        
        <div class="sidebar-footer">
            <a href="<?= SITE_URL ?>" target="_blank" class="sidebar-footer-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="2" y1="12" x2="22" y2="12"></line>
                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                </svg>
                <span>View Site</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="external-icon">
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                    <polyline points="15 3 21 3 21 9"></polyline>
                    <line x1="10" y1="14" x2="21" y2="3"></line>
                </svg>
            </a>
        </div>
    </div>
</aside>

<script>
function toggleSubmenu(btn) {
    const group = btn.closest('.nav-item-group');
    group.classList.toggle('expanded');
}
</script>
