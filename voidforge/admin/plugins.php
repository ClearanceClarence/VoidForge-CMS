<?php
/**
 * Plugins Management - VoidForge CMS v1.0.10
 * WordPress-like table design
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
$currentPage = 'plugins';

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
$activeCount = count(array_filter($plugins, fn($p) => $p['active']));
$inactiveCount = count($plugins) - $activeCount;

// Filter
$filter = $_GET['status'] ?? 'all';
if ($filter === 'active') {
    $plugins = array_filter($plugins, fn($p) => $p['active']);
} elseif ($filter === 'inactive') {
    $plugins = array_filter($plugins, fn($p) => !$p['active']);
}

include ADMIN_PATH . '/includes/header.php';
?>

<style>
.plugins-page { max-width: 1200px; }

.plugins-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
}

.plugins-header h1 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.plugins-filters {
    display: flex;
    gap: 0.25rem;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #e2e8f0;
}

.filter-link {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #64748b;
    text-decoration: none;
    border-radius: 6px;
    transition: all 0.15s;
}

.filter-link:hover {
    color: #1e293b;
    background: #f1f5f9;
}

.filter-link.active {
    color: var(--forge-primary, #6366f1);
    background: rgba(99, 102, 241, 0.1);
}

.filter-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    margin-left: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    background: #e2e8f0;
    color: #475569;
    border-radius: 10px;
}

.filter-link.active .filter-count {
    background: var(--forge-primary, #6366f1);
    color: #fff;
}

/* Plugin Table */
.plugins-table {
    width: 100%;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
}

.plugins-table thead th {
    background: #f8fafc;
    padding: 1rem 1.25rem;
    text-align: left;
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 1px solid #e2e8f0;
}

.plugins-table tbody tr {
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.15s;
}

.plugins-table tbody tr:last-child {
    border-bottom: none;
}

.plugins-table tbody tr:hover {
    background: #f8fafc;
}

.plugins-table tbody tr.active-plugin {
    background: linear-gradient(90deg, rgba(99, 102, 241, 0.04) 0%, transparent 100%);
    border-left: 3px solid var(--forge-primary, #6366f1);
}

.plugins-table td {
    padding: 1.25rem;
    vertical-align: top;
}

.plugin-name-col {
    width: 35%;
}

.plugin-desc-col {
    width: 65%;
}

/* Plugin Info */
.plugin-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.plugin-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    flex-shrink: 0;
}

.active-plugin .plugin-icon {
    background: linear-gradient(135deg, var(--forge-primary, #6366f1) 0%, var(--forge-secondary, #8b5cf6) 100%);
    color: #fff;
}

.plugin-name {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 0.25rem 0;
}

.plugin-name a {
    color: inherit;
    text-decoration: none;
}

.plugin-name a:hover {
    color: var(--forge-primary, #6366f1);
}

.plugin-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.5rem;
    opacity: 0;
    transition: opacity 0.15s;
}

.plugins-table tbody tr:hover .plugin-actions {
    opacity: 1;
}

.plugin-action {
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--forge-primary, #6366f1);
    text-decoration: none;
    padding: 0.25rem 0;
    transition: color 0.15s;
}

.plugin-action:hover {
    color: #4f46e5;
}

.plugin-action.deactivate {
    color: #dc2626;
}

.plugin-action.deactivate:hover {
    color: #b91c1c;
}

.action-sep {
    color: #e2e8f0;
}

/* Description */
.plugin-description {
    font-size: 0.875rem;
    color: #475569;
    line-height: 1.6;
    margin: 0 0 0.75rem 0;
}

.plugin-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    font-size: 0.8125rem;
    color: #94a3b8;
}

.plugin-meta-item {
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

.plugin-meta-item strong {
    color: #64748b;
    font-weight: 500;
}

/* Status Badge */
.status-active {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.25rem 0.625rem;
    background: #dcfce7;
    color: #166534;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 9999px;
}

/* Empty State */
.empty-plugins {
    text-align: center;
    padding: 4rem 2rem;
    color: #64748b;
}

.empty-plugins svg {
    width: 64px;
    height: 64px;
    margin-bottom: 1rem;
    opacity: 0.4;
}

.empty-plugins h3 {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 0.5rem 0;
}

.empty-plugins p {
    margin: 0;
}

/* Info Card */
.info-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    margin-top: 2rem;
    overflow: hidden;
}

.info-card-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.info-card-header h3 {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.info-card-body {
    padding: 1.25rem;
}

.info-card-body p {
    font-size: 0.875rem;
    color: #475569;
    margin: 0 0 1rem 0;
}

.code-block {
    background: #0f172a;
    color: #e2e8f0;
    padding: 1.25rem;
    border-radius: 8px;
    font-family: 'JetBrains Mono', 'Monaco', 'Menlo', monospace;
    font-size: 0.8125rem;
    line-height: 1.7;
    overflow-x: auto;
    white-space: pre;
    margin: 0;
}

.code-block .comment { color: #64748b; }
.code-block .keyword { color: #f472b6; }
.code-block .string { color: #a5f3fc; }
.code-block .function { color: #fbbf24; }
.code-block .variable { color: #c4b5fd; }

@media (max-width: 768px) {
    .plugins-table thead { display: none; }
    .plugins-table tbody tr {
        display: block;
        padding: 1rem;
    }
    .plugins-table td {
        display: block;
        width: 100%;
        padding: 0.5rem 0;
    }
    .plugin-actions { opacity: 1; }
}
</style>

<div class="plugins-page">
    <div class="plugins-header">
        <h1>Plugins</h1>
    </div>

    <div class="plugins-filters">
        <a href="?status=all" class="filter-link <?= $filter === 'all' ? 'active' : '' ?>">
            All <span class="filter-count"><?= count(Plugin::getAll()) ?></span>
        </a>
        <a href="?status=active" class="filter-link <?= $filter === 'active' ? 'active' : '' ?>">
            Active <span class="filter-count"><?= $activeCount ?></span>
        </a>
        <a href="?status=inactive" class="filter-link <?= $filter === 'inactive' ? 'active' : '' ?>">
            Inactive <span class="filter-count"><?= $inactiveCount ?></span>
        </a>
    </div>

    <?php if (empty($plugins)): ?>
    <div class="plugins-table">
        <div class="empty-plugins">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                <path d="M2 17l10 5 10-5"></path>
                <path d="M2 12l10 5 10-5"></path>
            </svg>
            <h3>No plugins found</h3>
            <p>Upload plugins to the <code>/plugins</code> directory to get started.</p>
        </div>
    </div>
    <?php else: ?>
    <table class="plugins-table">
        <thead>
            <tr>
                <th class="plugin-name-col">Plugin</th>
                <th class="plugin-desc-col">Description</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($plugins as $plugin): ?>
            <tr class="<?= $plugin['active'] ? 'active-plugin' : '' ?>">
                <td class="plugin-name-col">
                    <div class="plugin-title">
                        <div class="plugin-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                                <path d="M2 17l10 5 10-5"></path>
                                <path d="M2 12l10 5 10-5"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="plugin-name">
                                <?= esc($plugin['name']) ?>
                                <?php if ($plugin['active']): ?>
                                <span class="status-active">Active</span>
                                <?php endif; ?>
                            </h4>
                            <div class="plugin-actions">
                                <?php if ($plugin['active']): ?>
                                <a href="?action=deactivate&plugin=<?= urlencode($plugin['slug']) ?>&csrf=<?= csrfToken() ?>" 
                                   class="plugin-action deactivate">Deactivate</a>
                                <?php else: ?>
                                <a href="?action=activate&plugin=<?= urlencode($plugin['slug']) ?>&csrf=<?= csrfToken() ?>" 
                                   class="plugin-action">Activate</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </td>
                <td class="plugin-desc-col">
                    <p class="plugin-description"><?= esc($plugin['description']) ?></p>
                    <div class="plugin-meta">
                        <span class="plugin-meta-item">
                            <strong>Version:</strong> <?= esc($plugin['version']) ?>
                        </span>
                        <?php if ($plugin['author']): ?>
                        <span class="plugin-meta-item">
                            <strong>Author:</strong> <?= esc($plugin['author']) ?>
                        </span>
                        <?php endif; ?>
                        <?php if (!empty($plugin['author_uri'])): ?>
                        <span class="plugin-meta-item">
                            <a href="<?= esc($plugin['author_uri']) ?>" target="_blank" rel="noopener">Visit author site</a>
                        </span>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <div class="info-card">
        <div class="info-card-header">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="16" x2="12" y2="12"></line>
                <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            <h3>Creating Plugins</h3>
        </div>
        <div class="info-card-body">
            <p>To create a plugin, add a folder to <code>/plugins</code> with a main PHP file matching the folder name.</p>
            <pre class="code-block"><span class="keyword">&lt;?php</span>
<span class="comment">/**
 * Plugin Name: My Plugin
 * Description: A sample plugin for VoidForge CMS
 * Version: 1.0.0
 * Author: Your Name
 */</span>

<span class="function">add_action</span>(<span class="string">'plugins_loaded'</span>, <span class="keyword">function</span>() {
    <span class="comment">// Initialize your plugin here</span>
});

<span class="function">add_filter</span>(<span class="string">'the_content'</span>, <span class="keyword">function</span>(<span class="variable">$content</span>) {
    <span class="keyword">return</span> <span class="variable">$content</span>;
});

<span class="function">add_shortcode</span>(<span class="string">'hello'</span>, <span class="keyword">function</span>(<span class="variable">$atts</span>) {
    <span class="keyword">return</span> <span class="string">'Hello, World!'</span>;
});</pre>
        </div>
    </div>
</div>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
