<?php
/**
 * Admin Sidebar - Forge CMS
 * Premium design with enhanced visual styling
 */

defined('CMS_ROOT') or die('Direct access not allowed');

$postTypes = Post::getTypes();
?>
<aside class="admin-sidebar" id="adminSidebar">
    <!-- Ambient glow effects -->
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
            <!-- Main -->
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
                    <?php if ($currentPage === 'index'): ?>
                    <div class="nav-indicator"></div>
                    <?php endif; ?>
                </a>
            </div>

            <!-- Content -->
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
                            <?php if ($currentPage === 'posts' && ($_GET['type'] ?? 'post') === $type): ?>
                            <div class="nav-indicator"></div>
                            <?php endif; ?>
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
                    <?php if ($currentPage === 'media'): ?>
                    <div class="nav-indicator"></div>
                    <?php endif; ?>
                </a>
            </div>

            <?php if (User::isAdmin()): ?>
            <!-- Design -->
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
                    <?php if ($currentPage === 'customize'): ?>
                    <div class="nav-indicator"></div>
                    <?php endif; ?>
                </a>
            </div>

            <!-- Administration -->
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
                    <?php if ($currentPage === 'users'): ?>
                    <div class="nav-indicator"></div>
                    <?php endif; ?>
                </a>

                <a href="<?= ADMIN_URL ?>/settings.php" class="nav-item <?= $currentPage === 'settings' ? 'active' : '' ?>">
                    <div class="nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"></path>
                        </svg>
                    </div>
                    <span class="nav-label">Settings</span>
                    <?php if ($currentPage === 'settings'): ?>
                    <div class="nav-indicator"></div>
                    <?php endif; ?>
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
                    <?php if ($currentPage === 'update'): ?>
                    <div class="nav-indicator"></div>
                    <?php endif; ?>
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
                    <?php if ($currentPage === 'plugins'): ?>
                    <div class="nav-indicator"></div>
                    <?php endif; ?>
                </a>
            </div>
            <?php endif; ?>
        </nav>
        
        <!-- Sidebar footer -->
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

<style>
/* Enhanced Sidebar Styles */
.admin-sidebar {
    width: var(--sidebar-width);
    background: linear-gradient(180deg, #0f172a 0%, #020617 100%);
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    z-index: 100;
    transition: transform var(--transition);
    overflow: hidden;
}

.sidebar-inner {
    position: relative;
    z-index: 2;
    height: 100%;
    display: flex;
    flex-direction: column;
}

/* Ambient glow effects */
.sidebar-glow {
    position: absolute;
    top: -100px;
    left: -100px;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, transparent 70%);
    pointer-events: none;
    z-index: 1;
}

.sidebar-glow-2 {
    position: absolute;
    bottom: -50px;
    right: -50px;
    width: 200px;
    height: 200px;
    background: radial-gradient(circle, rgba(139, 92, 246, 0.1) 0%, transparent 70%);
    pointer-events: none;
    z-index: 1;
}

/* Logo */
.sidebar-header {
    padding: 1.25rem;
    border-bottom: 1px solid rgba(255,255,255,0.06);
}

.sidebar-logo {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    text-decoration: none;
}

.logo-icon {
    width: 42px;
    height: 42px;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
    transition: all 0.3s ease;
}

.sidebar-logo:hover .logo-icon {
    transform: scale(1.05);
    box-shadow: 0 6px 20px rgba(99, 102, 241, 0.5);
}

.logo-text {
    display: flex;
    flex-direction: column;
}

.logo-name {
    font-size: 1.25rem;
    font-weight: 700;
    color: #fff;
    letter-spacing: -0.025em;
    line-height: 1.2;
}

.logo-version {
    font-size: 0.6875rem;
    color: #64748b;
    font-weight: 500;
}

/* Navigation */
.sidebar-nav {
    flex: 1;
    overflow-y: auto;
    padding: 0.75rem;
}

.nav-section {
    margin-bottom: 0.5rem;
}

.nav-section-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 0.75rem 0.5rem;
}

.nav-section-title {
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #475569;
    white-space: nowrap;
}

.nav-section-line {
    flex: 1;
    height: 1px;
    background: linear-gradient(90deg, rgba(71, 85, 105, 0.5) 0%, transparent 100%);
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    color: #94a3b8;
    text-decoration: none;
    border-radius: 10px;
    transition: all 0.2s ease;
    position: relative;
    margin-bottom: 2px;
}

.nav-item:hover {
    color: #e2e8f0;
    background: rgba(255,255,255,0.05);
}

.nav-item.active {
    color: #fff;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(139, 92, 246, 0.1) 100%);
}

.nav-icon {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.03);
    border-radius: 8px;
    transition: all 0.2s ease;
}

.nav-item:hover .nav-icon {
    background: rgba(255,255,255,0.08);
}

.nav-item.active .nav-icon {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: #fff;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.nav-label {
    font-size: 0.9375rem;
    font-weight: 500;
    flex: 1;
}

.nav-indicator {
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 24px;
    background: linear-gradient(180deg, #6366f1 0%, #8b5cf6 100%);
    border-radius: 0 4px 4px 0;
    box-shadow: 0 0 10px rgba(99, 102, 241, 0.5);
}

/* Featured nav item */
.nav-item-featured {
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.05) 100%);
    border: 1px solid rgba(99, 102, 241, 0.2);
}

.nav-item-featured:hover {
    border-color: rgba(99, 102, 241, 0.3);
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(139, 92, 246, 0.1) 100%);
}

.nav-badge {
    font-size: 0.625rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 0.25rem 0.5rem;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: #fff;
    border-radius: 6px;
    box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
}

/* Footer */
.sidebar-footer {
    padding: 1rem 1.25rem;
    border-top: 1px solid rgba(255,255,255,0.06);
}

.sidebar-footer-link {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    padding: 0.75rem 1rem;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 10px;
    color: #94a3b8;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.sidebar-footer-link:hover {
    background: rgba(255,255,255,0.06);
    color: #e2e8f0;
    border-color: rgba(255,255,255,0.1);
}

.sidebar-footer-link span {
    flex: 1;
}

.sidebar-footer-link .external-icon {
    opacity: 0.5;
    transition: all 0.2s ease;
}

.sidebar-footer-link:hover .external-icon {
    opacity: 1;
    transform: translate(2px, -2px);
}

/* Scrollbar */
.sidebar-nav::-webkit-scrollbar {
    width: 6px;
}

.sidebar-nav::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar-nav::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.1);
    border-radius: 3px;
}

.sidebar-nav::-webkit-scrollbar-thumb:hover {
    background: rgba(255,255,255,0.2);
}

/* Mobile */
@media (max-width: 768px) {
    .admin-sidebar {
        transform: translateX(-100%);
    }
    
    .admin-sidebar.open {
        transform: translateX(0);
    }
}
</style>
