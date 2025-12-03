<?php
/**
 * Plugins Management - Forge CMS
 */

define('CMS_ROOT', dirname(__DIR__));
require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/user.php';
require_once CMS_ROOT . '/includes/post.php';
require_once CMS_ROOT . '/includes/plugin.php';

Post::init();

User::startSession();
User::requireRole('admin');

$pageTitle = 'Plugins';

// Handle activation/deactivation
if (isset($_GET['action']) && isset($_GET['plugin']) && verifyCsrf($_GET['csrf'] ?? '')) {
    $plugin = $_GET['plugin'];
    
    if ($_GET['action'] === 'activate') {
        $result = Plugin::activate($plugin);
        if ($result['success']) {
            setFlash('success', 'Plugin activated successfully.');
        } else {
            setFlash('error', $result['error']);
        }
    } elseif ($_GET['action'] === 'deactivate') {
        $result = Plugin::deactivate($plugin);
        if ($result['success']) {
            setFlash('success', 'Plugin deactivated successfully.');
        } else {
            setFlash('error', $result['error']);
        }
    }
    
    redirect(ADMIN_URL . '/plugins.php');
}

$plugins = Plugin::getAll();

include ADMIN_PATH . '/includes/header.php';
?>

<div class="page-header">
    <h2>Plugins</h2>
    <p style="color: var(--text-secondary); margin-top: 0.25rem;">Extend your site's functionality with plugins.</p>
</div>

<div class="alert alert-info" style="margin-bottom: 1.5rem; display: flex; align-items: flex-start; gap: 0.75rem;">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0; margin-top: 0.125rem;">
        <circle cx="12" cy="12" r="10"></circle>
        <line x1="12" y1="16" x2="12" y2="12"></line>
        <line x1="12" y1="8" x2="12.01" y2="8"></line>
    </svg>
    <div>
        <strong>Plugin System in Development</strong>
        <p style="margin: 0.25rem 0 0 0; opacity: 0.9;">The plugin architecture is actively being developed. Current features include hooks, filters, and content tags. More functionality coming soon!</p>
    </div>
</div>

<div class="card">
    <?php if (empty($plugins)): ?>
    <div class="card-body">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                <path d="M2 17l10 5 10-5"></path>
                <path d="M2 12l10 5 10-5"></path>
            </svg>
            <h3>No plugins installed</h3>
            <p>Upload plugins to the <code>/plugins</code> directory to get started.</p>
        </div>
    </div>
    <?php else: ?>
    <div class="plugins-grid">
        <?php foreach ($plugins as $plugin): ?>
        <div class="plugin-card <?= $plugin['active'] ? 'active' : '' ?>">
            <div class="plugin-header">
                <div class="plugin-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                        <path d="M2 17l10 5 10-5"></path>
                        <path d="M2 12l10 5 10-5"></path>
                    </svg>
                </div>
                <div class="plugin-info">
                    <h4 class="plugin-name"><?= esc($plugin['name']) ?></h4>
                    <?php if ($plugin['author']): ?>
                    <span class="plugin-author">by <?= esc($plugin['author']) ?></span>
                    <?php endif; ?>
                </div>
                <span class="plugin-version">v<?= esc($plugin['version']) ?></span>
            </div>
            <p class="plugin-description"><?= esc($plugin['description']) ?></p>
            <div class="plugin-footer">
                <?php if ($plugin['active']): ?>
                <a href="?action=deactivate&plugin=<?= urlencode($plugin['slug']) ?>&csrf=<?= csrfToken() ?>" 
                   class="btn btn-secondary btn-sm">Deactivate</a>
                <?php else: ?>
                <a href="?action=activate&plugin=<?= urlencode($plugin['slug']) ?>&csrf=<?= csrfToken() ?>" 
                   class="btn btn-primary btn-sm">Activate</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<style>
.plugins-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1rem;
    padding: 1rem;
}

.plugin-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-lg);
    padding: 1.25rem;
    transition: all 0.2s;
}

.plugin-card:hover {
    border-color: var(--forge-primary);
}

.plugin-card.active {
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(139, 92, 246, 0.05) 100%);
    border-color: var(--forge-primary);
    box-shadow: 0 0 0 1px rgba(99, 102, 241, 0.1);
}

.plugin-header {
    display: flex;
    align-items: flex-start;
    gap: 0.875rem;
    margin-bottom: 0.875rem;
}

.plugin-icon {
    width: 44px;
    height: 44px;
    border-radius: var(--border-radius);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: var(--bg-card-header);
    color: var(--text-muted);
}

.plugin-card.active .plugin-icon {
    background: linear-gradient(135deg, var(--forge-primary) 0%, var(--forge-primary-dark) 100%);
    color: #fff;
}

.plugin-info {
    flex: 1;
    min-width: 0;
}

.plugin-name {
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 0.125rem 0;
    color: var(--text-primary);
}

.plugin-author {
    font-size: 0.75rem;
    color: var(--text-muted);
}

.plugin-version {
    font-size: 0.6875rem;
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    background: var(--bg-card-header);
    border-radius: 9999px;
    color: var(--text-secondary);
    flex-shrink: 0;
}

.plugin-description {
    font-size: 0.875rem;
    color: var(--text-secondary);
    line-height: 1.6;
    margin: 0 0 1rem 0;
}

.plugin-footer {
    display: flex;
    justify-content: flex-end;
}
</style>

<div class="card" style="margin-top: 1.5rem;">
    <div class="card-header">
        <h3 class="card-title">Creating Plugins</h3>
    </div>
    <div class="card-body">
        <p style="margin-bottom: 1rem;">To create a plugin, add a folder to <code>/plugins</code> with a main PHP file matching the folder name.</p>
        <pre style="background: var(--bg-card-header); padding: 1rem; border-radius: var(--border-radius); font-size: 0.875rem; overflow-x: auto;"><code>&lt;?php
/**
 * Plugin Name: My Plugin
 * Description: A sample plugin
 * Version: 1.0.0
 * Author: Your Name
 */

add_action('plugins_loaded', function() {
    // Initialize plugin
});

add_filter('the_content', function($content) {
    return $content;
});</code></pre>
    </div>
</div>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
