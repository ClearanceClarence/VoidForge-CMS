<?php
/**
 * Admin Sidebar - VoidForge CMS
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
                    <svg width="24" height="24" viewBox="0 0 32 32" fill="none">
                        <defs>
                            <linearGradient id="sidebarLogoGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#8b5cf6"/>
                                <stop offset="100%" style="stop-color:#06b6d4"/>
                            </linearGradient>
                            <linearGradient id="sidebarInnerGlow" x1="50%" y1="0%" x2="50%" y2="100%">
                                <stop offset="0%" style="stop-color:#c4b5fd"/>
                                <stop offset="100%" style="stop-color:#8b5cf6"/>
                            </linearGradient>
                        </defs>
                        <path d="M5 5 L16 27 L27 5" fill="none" stroke="url(#sidebarLogoGradient)" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="16" cy="14" r="3.5" fill="url(#sidebarInnerGlow)"/>
                        <circle cx="16" cy="14" r="1.5" fill="#fff" opacity="0.9"/>
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
            <!-- Structure Section -->
            <div class="nav-section">
                <div class="nav-section-header">
                    <span class="nav-section-title">Structure</span>
                    <div class="nav-section-line"></div>
                </div>
                
                <a href="<?= ADMIN_URL ?>/post-types.php" class="nav-item <?= $currentPage === 'post-types' ? 'active' : '' ?>">
                    <div class="nav-icon">
                        <?= getAdminMenuIcon('layers') ?>
                    </div>
                    <span class="nav-label">Post Types</span>
                    <?php if ($currentPage === 'post-types'): ?><div class="nav-indicator"></div><?php endif; ?>
                </a>
                
                <a href="<?= ADMIN_URL ?>/custom-fields.php" class="nav-item <?= in_array($currentPage, ['custom-fields', 'custom-field-edit']) ? 'active' : '' ?>">
                    <div class="nav-icon">
                        <?= getAdminMenuIcon('grid') ?>
                    </div>
                    <span class="nav-label">Custom Fields</span>
                    <?php if (in_array($currentPage, ['custom-fields', 'custom-field-edit'])): ?><div class="nav-indicator"></div><?php endif; ?>
                </a>
                
                <a href="<?= ADMIN_URL ?>/taxonomies.php" class="nav-item <?= in_array($currentPage, ['taxonomies', 'taxonomy-edit', 'terms']) ? 'active' : '' ?>">
                    <div class="nav-icon">
                        <?= getAdminMenuIcon('tag') ?>
                    </div>
                    <span class="nav-label">Taxonomies</span>
                    <?php if (in_array($currentPage, ['taxonomies', 'taxonomy-edit', 'terms'])): ?><div class="nav-indicator"></div><?php endif; ?>
                </a>
            </div>

            <!-- Design Section -->
            <div class="nav-section">
                <div class="nav-section-header">
                    <span class="nav-section-title">Design</span>
                    <div class="nav-section-line"></div>
                </div>
                
                <!-- Themes with submenu -->
                <div class="nav-item-group <?= in_array($currentPage, ['themes', 'theme-settings']) ? 'expanded' : '' ?>">
                    <a href="#" class="nav-item nav-item-parent" onclick="event.preventDefault(); this.parentElement.classList.toggle('expanded');">
                        <div class="nav-icon">
                            <?= getAdminMenuIcon('layers') ?>
                        </div>
                        <span class="nav-label">Themes</span>
                        <svg class="nav-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                    </a>
                    <div class="nav-submenu">
                        <a href="<?= ADMIN_URL ?>/themes.php" class="nav-item <?= $currentPage === 'themes' ? 'active' : '' ?>">
                            <span class="nav-label">Manage Themes</span>
                            <?php if ($currentPage === 'themes'): ?><div class="nav-indicator"></div><?php endif; ?>
                        </a>
                        <a href="<?= ADMIN_URL ?>/theme-settings.php" class="nav-item <?= $currentPage === 'theme-settings' ? 'active' : '' ?>">
                            <span class="nav-label">Theme Settings</span>
                            <?php if ($currentPage === 'theme-settings'): ?><div class="nav-indicator"></div><?php endif; ?>
                        </a>
                    </div>
                </div>
                
                <a href="<?= ADMIN_URL ?>/menus.php" class="nav-item <?= $currentPage === 'menus' ? 'active' : '' ?>">
                    <div class="nav-icon">
                        <?= getAdminMenuIcon('menu') ?>
                    </div>
                    <span class="nav-label">Menus</span>
                    <?php if ($currentPage === 'menus'): ?><div class="nav-indicator"></div><?php endif; ?>
                </a>
                
                <a href="<?= ADMIN_URL ?>/customize.php" class="nav-item nav-item-featured <?= $currentPage === 'customize' ? 'active' : '' ?>">
                    <div class="nav-icon">
                        <?= getAdminMenuIcon('palette') ?>
                    </div>
                    <span class="nav-label">Customize</span>
                    <span class="nav-badge">Live</span>
                    <?php if ($currentPage === 'customize'): ?><div class="nav-indicator"></div><?php endif; ?>
                </a>
                
                <a href="<?= ADMIN_URL ?>/admin-theme.php" class="nav-item <?= $currentPage === 'admin-theme' ? 'active' : '' ?>">
                    <div class="nav-icon">
                        <?= getAdminMenuIcon('sliders') ?>
                    </div>
                    <span class="nav-label">Admin Theme</span>
                    <?php if ($currentPage === 'admin-theme'): ?><div class="nav-indicator"></div><?php endif; ?>
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
                
                <?php 
                // Get plugin pages that are children of 'plugins'
                $pluginPages = Plugin::getAdminPages();
                $pluginSubpages = array_filter($pluginPages, fn($p) => ($p['parent'] ?? '') === 'plugins');
                $pluginsExpanded = $currentPage === 'plugins' || str_starts_with($currentPage, 'plugin-');
                ?>
                
                <!-- Plugins with submenu -->
                <div class="nav-item-group <?= $pluginsExpanded ? 'expanded' : '' ?>">
                    <button type="button" class="nav-item nav-item-parent <?= $currentPage === 'plugins' ? 'active' : '' ?>" onclick="toggleSubmenu(this)">
                        <div class="nav-icon">
                            <?= getAdminMenuIcon('puzzle') ?>
                        </div>
                        <span class="nav-label">Plugins</span>
                        <?php if (!empty($pluginSubpages)): ?>
                        <svg class="nav-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                        <?php endif; ?>
                    </button>
                    <div class="nav-submenu">
                        <a href="<?= ADMIN_URL ?>/plugins.php" class="nav-subitem <?= $currentPage === 'plugins' ? 'active' : '' ?>">
                            Manage Plugins
                        </a>
                        <?php foreach ($pluginSubpages as $slug => $page): ?>
                        <a href="<?= ADMIN_URL ?>/plugin-page.php?page=<?= esc($slug) ?>" class="nav-subitem <?= $currentPage === 'plugin-' . $slug ? 'active' : '' ?>">
                            <?= esc($page['menu_title']) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <?php 
                // Show plugin-registered pages that want top-level placement
                // Authors must explicitly set parent=null or parent='' to appear here
                foreach ($pluginPages as $slug => $page): 
                    $parent = $page['parent'] ?? 'plugins';
                    if ($parent === '' || $parent === null): // Only show explicitly top-level pages
                ?>
                <a href="<?= ADMIN_URL ?>/plugin-page.php?page=<?= esc($slug) ?>" class="nav-item <?= $currentPage === 'plugin-' . $slug ? 'active' : '' ?>">
                    <div class="nav-icon">
                        <?= getAdminMenuIcon($page['icon'] ?? 'puzzle') ?>
                    </div>
                    <span class="nav-label"><?= esc($page['menu_title']) ?></span>
                    <?php if ($currentPage === 'plugin-' . $slug): ?><div class="nav-indicator"></div><?php endif; ?>
                </a>
                <?php 
                    endif;
                endforeach; 
                ?>

                <a href="<?= ADMIN_URL ?>/settings.php" class="nav-item <?= $currentPage === 'settings' ? 'active' : '' ?>">
                    <div class="nav-icon">
                        <?= getAdminMenuIcon('settings') ?>
                    </div>
                    <span class="nav-label">Settings</span>
                    <?php if ($currentPage === 'settings'): ?><div class="nav-indicator"></div><?php endif; ?>
                </a>
                
                <a href="<?= ADMIN_URL ?>/update.php" class="nav-item <?= $currentPage === 'update' ? 'active' : '' ?>">
                    <div class="nav-icon">
                        <?= getAdminMenuIcon('download') ?>
                    </div>
                    <span class="nav-label">Update</span>
                    <?php if ($currentPage === 'update'): ?><div class="nav-indicator"></div><?php endif; ?>
                </a>
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
