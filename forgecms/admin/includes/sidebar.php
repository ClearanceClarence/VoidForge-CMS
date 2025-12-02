<?php
/**
 * Admin Sidebar - Forge CMS v1.0.3
 * Premium design with enhanced visual styling
 */

defined('CMS_ROOT') or die('Direct access not allowed');

$postTypes = Post::getTypes();
?>
<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-glow"></div>
    <div class="sidebar-glow-2"></div>
    
    <div class="sidebar-inner">
        <div class="sidebar-header">
            <a href="<?= ADMIN_URL ?>/" class="sidebar-logo">
                <div class="logo-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                        <polyline points="2 17 12 22 22 17"></polyline>
                        <polyline points="2 12 12 17 22 12"></polyline>
                    </svg>
                </div>
                <div class="logo-text">
                    <span class="logo-name"><?= CMS_NAME ?></span>
                    <span class="logo-version">v<?= CMS_VERSION ?></span>
                </div>
            </a>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">
                <a href="<?= ADMIN_URL ?>/" class="nav-item <?= $currentPage === 'index' ? 'active' : '' ?>">
                    <div class="nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="9" rx="1"></rect>
                            <rect x="14" y="3" width="7" height="5" rx="1"></rect>
                            <rect x="14" y="12" width="7" height="9" rx="1"></rect>
                            <rect x="3" y="16" width="7" height="5" rx="1"></rect>
                        </svg>
                    </div>
                    <span class="nav-label">Dashboard</span>
                    <?php if ($currentPage === 'index'): ?><div class="nav-indicator"></div><?php endif; ?>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-header">
                    <span class="nav-section-title">Content</span>
                    <div class="nav-section-line"></div>
                </div>
                
                <?php foreach ($postTypes as $type => $config): ?>
                    <?php if ($config['public']): ?>
                        <a href="<?= ADMIN_URL ?>/posts.php?type=<?= $type ?>" class="nav-item <?= $currentPage === 'posts' && ($_GET['type'] ?? 'post') === $type ? 'active' : '' ?>">
                            <div class="nav-icon">
                                <?php if ($config['icon'] === 'file-text'): ?>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                </svg>
                                <?php else: ?>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                                    <polyline points="13 2 13 9 20 9"></polyline>
                                </svg>
                                <?php endif; ?>
                            </div>
                            <span class="nav-label"><?= esc($config['label']) ?></span>
                            <?php if ($currentPage === 'posts' && ($_GET['type'] ?? 'post') === $type): ?><div class="nav-indicator"></div><?php endif; ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>

                <a href="<?= ADMIN_URL ?>/media.php" class="nav-item <?= $currentPage === 'media' ? 'active' : '' ?>">
                    <div class="nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <polyline points="21 15 16 10 5 21"></polyline>
                        </svg>
                    </div>
                    <span class="nav-label">Media</span>
                    <?php if ($currentPage === 'media'): ?><div class="nav-indicator"></div><?php endif; ?>
                </a>
            </div>

            <?php if (User::isAdmin()): ?>
            <div class="nav-section">
                <div class="nav-section-header">
                    <span class="nav-section-title">Design</span>
                    <div class="nav-section-line"></div>
                </div>
                
                <a href="<?= ADMIN_URL ?>/customize.php" class="nav-item nav-item-featured <?= $currentPage === 'customize' ? 'active' : '' ?>">
                    <div class="nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 19l7-7 3 3-7 7-3-3z"></path>
                            <path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"></path>
                            <path d="M2 2l7.586 7.586"></path>
                            <circle cx="11" cy="11" r="2"></circle>
                        </svg>
                    </div>
                    <span class="nav-label">Customize</span>
                    <span class="nav-badge">Live</span>
                    <?php if ($currentPage === 'customize'): ?><div class="nav-indicator"></div><?php endif; ?>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-header">
                    <span class="nav-section-title">Admin</span>
                    <div class="nav-section-line"></div>
                </div>
                
                <a href="<?= ADMIN_URL ?>/users.php" class="nav-item <?= $currentPage === 'users' ? 'active' : '' ?>">
                    <div class="nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <span class="nav-label">Users</span>
                    <?php if ($currentPage === 'users'): ?><div class="nav-indicator"></div><?php endif; ?>
                </a>

                <a href="<?= ADMIN_URL ?>/settings.php" class="nav-item <?= $currentPage === 'settings' ? 'active' : '' ?>">
                    <div class="nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"></path>
                        </svg>
                    </div>
                    <span class="nav-label">Settings</span>
                    <?php if ($currentPage === 'settings'): ?><div class="nav-indicator"></div><?php endif; ?>
                </a>

                <a href="<?= ADMIN_URL ?>/update.php" class="nav-item <?= $currentPage === 'update' ? 'active' : '' ?>">
                    <div class="nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="16 16 12 12 8 16"></polyline>
                            <line x1="12" y1="12" x2="12" y2="21"></line>
                            <path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"></path>
                        </svg>
                    </div>
                    <span class="nav-label">Update</span>
                    <?php if ($currentPage === 'update'): ?><div class="nav-indicator"></div><?php endif; ?>
                </a>

                <a href="<?= ADMIN_URL ?>/plugins.php" class="nav-item <?= $currentPage === 'plugins' ? 'active' : '' ?>">
                    <div class="nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                            <path d="M2 17l10 5 10-5"></path>
                            <path d="M2 12l10 5 10-5"></path>
                        </svg>
                    </div>
                    <span class="nav-label">Plugins</span>
                    <?php if ($currentPage === 'plugins'): ?><div class="nav-indicator"></div><?php endif; ?>
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
