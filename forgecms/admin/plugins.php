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
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Plugin</th>
                    <th>Description</th>
                    <th>Version</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plugins as $plugin): ?>
                <tr>
                    <td>
                        <strong><?= esc($plugin['name']) ?></strong>
                        <?php if ($plugin['author']): ?>
                        <div style="font-size: 0.8125rem; color: var(--text-muted);">by <?= esc($plugin['author']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="color: var(--text-secondary);"><?= esc($plugin['description']) ?></td>
                    <td><?= esc($plugin['version']) ?></td>
                    <td>
                        <?php if ($plugin['active']): ?>
                        <span class="badge badge-success">Active</span>
                        <?php else: ?>
                        <span class="badge badge-warning">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($plugin['active']): ?>
                        <a href="?action=deactivate&plugin=<?= urlencode($plugin['slug']) ?>&csrf=<?= csrfToken() ?>" 
                           class="btn btn-secondary btn-sm">Deactivate</a>
                        <?php else: ?>
                        <a href="?action=activate&plugin=<?= urlencode($plugin['slug']) ?>&csrf=<?= csrfToken() ?>" 
                           class="btn btn-primary btn-sm">Activate</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

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
