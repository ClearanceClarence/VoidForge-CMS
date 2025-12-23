<?php
/**
 * Plugins Management - VoidForge CMS
 * WordPress-inspired layout with modern styling
 */

define('CMS_ROOT', dirname(__DIR__));
require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/user.php';
require_once CMS_ROOT . '/includes/post.php';
require_once CMS_ROOT . '/includes/plugin.php';

Post::init();
Plugin::init();

User::startSession();
User::requireRole('admin');

$pageTitle = 'Plugins';
$currentPage = 'plugins';

// Handle actions
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
    } elseif ($_GET['action'] === 'delete') {
        $result = Plugin::uninstall($plugin);
        if ($result['success']) {
            setFlash('success', 'Plugin deleted successfully.');
        } else {
            setFlash('error', $result['error'] ?? 'Failed to delete plugin.');
        }
    }
    
    redirect(ADMIN_URL . '/plugins.php');
}

// Handle plugin upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['plugin_zip']) && verifyCsrf()) {
    $file = $_FILES['plugin_zip'];
    
    if ($file['error'] === UPLOAD_ERR_OK && pathinfo($file['name'], PATHINFO_EXTENSION) === 'zip') {
        $tempDir = sys_get_temp_dir() . '/voidforge_plugin_' . uniqid();
        mkdir($tempDir);
        
        $zip = new ZipArchive();
        if ($zip->open($file['tmp_name']) === true) {
            $zip->extractTo($tempDir);
            $zip->close();
            
            // Find plugin folder
            $items = array_diff(scandir($tempDir), ['.', '..']);
            $pluginFolder = reset($items);
            $pluginPath = $tempDir . '/' . $pluginFolder;
            
            if (is_dir($pluginPath) && file_exists($pluginPath . '/' . $pluginFolder . '.php')) {
                $destPath = CMS_ROOT . '/plugins/' . $pluginFolder;
                
                if (is_dir($destPath)) {
                    function removeDir($dir) {
                        $files = array_diff(scandir($dir), ['.', '..']);
                        foreach ($files as $file) {
                            is_dir("$dir/$file") ? removeDir("$dir/$file") : unlink("$dir/$file");
                        }
                        return rmdir($dir);
                    }
                    removeDir($destPath);
                }
                
                rename($pluginPath, $destPath);
                setFlash('success', 'Plugin uploaded successfully.');
            } else {
                setFlash('error', 'Invalid plugin structure. Main PHP file must match folder name.');
            }
            
            array_map('unlink', glob("$tempDir/*"));
            @rmdir($tempDir);
        } else {
            setFlash('error', 'Failed to extract ZIP file.');
        }
    } else {
        setFlash('error', 'Please upload a valid ZIP file.');
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

// Check if any plugins have settings pages
$pluginPages = Plugin::getAdminPages();

include ADMIN_PATH . '/includes/header.php';
?>

<style>
/* Page Layout */
.plugins-page {
    max-width: 1400px;
    margin: 0 auto;
}

/* Page Header */
.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
    gap: 1rem;
    flex-wrap: wrap;
}

.page-header h1 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.page-header h1 svg {
    width: 28px;
    height: 28px;
    color: var(--forge-primary);
}

.btn-add-new {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    background: linear-gradient(135deg, var(--forge-primary) 0%, var(--forge-secondary) 100%);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s ease;
}

.btn-add-new:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px var(--forge-shadow-color);
}

.btn-add-new svg {
    width: 18px;
    height: 18px;
}

/* Subsubsub Navigation (WordPress-style filter links) */
.subsubsub {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
    margin: 0 0 1rem 0;
    padding: 0;
    list-style: none;
    font-size: 0.875rem;
}

.subsubsub li {
    display: flex;
    align-items: center;
}

.subsubsub li::after {
    content: "|";
    color: var(--text-dim);
    margin: 0 0.5rem;
}

.subsubsub li:last-child::after {
    display: none;
}

.subsubsub a {
    color: var(--text-muted);
    text-decoration: none;
    transition: color 0.15s;
}

.subsubsub a:hover {
    color: var(--forge-primary);
}

.subsubsub .current {
    color: var(--text-primary);
    font-weight: 600;
}

.subsubsub .count {
    color: var(--text-dim);
    font-weight: 400;
}

/* Plugin Table */
.plugins-table-wrap {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    overflow: hidden;
}

.plugins-table {
    width: 100%;
    border-collapse: collapse;
}

.plugins-table th {
    text-align: left;
    padding: 1rem 1.25rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.03em;
    background: var(--bg-card-header);
    border-bottom: 1px solid var(--border-color);
}

.plugins-table th:first-child {
    padding-left: 1.5rem;
}

.plugins-table td {
    padding: 1.25rem;
    vertical-align: top;
    border-bottom: 1px solid var(--border-color);
}

.plugins-table td:first-child {
    padding-left: 1.5rem;
}

.plugins-table tbody tr:last-child td {
    border-bottom: none;
}

.plugins-table tbody tr {
    transition: background-color 0.15s;
}

.plugins-table tbody tr:hover {
    background: var(--bg-card-header);
}

/* Plugin row states */
.plugins-table tbody tr.active {
    background: linear-gradient(90deg, rgba(99, 102, 241, 0.04) 0%, transparent 100%);
    border-left: 4px solid var(--forge-primary);
}

.plugins-table tbody tr.active td:first-child {
    padding-left: calc(1.5rem - 4px);
}

.plugins-table tbody tr.inactive {
    opacity: 0.85;
}

/* Plugin Info Column */
.plugin-info-cell {
    min-width: 300px;
}

.plugin-title {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    margin-bottom: 0.5rem;
}

.plugin-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    color: #94a3b8;
}

.active .plugin-icon {
    background: linear-gradient(135deg, var(--forge-primary) 0%, var(--forge-secondary) 100%);
    color: #fff;
}

.plugin-icon svg {
    width: 20px;
    height: 20px;
}

.plugin-name {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
}

.plugin-description {
    font-size: 0.8125rem;
    color: var(--text-muted);
    line-height: 1.5;
    margin: 0.5rem 0;
    max-width: 500px;
}

/* Row Actions (WordPress-style) */
.row-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.125rem;
    margin-top: 0.5rem;
    font-size: 0.8125rem;
    visibility: hidden;
    opacity: 0;
    transition: opacity 0.15s;
}

.plugins-table tbody tr:hover .row-actions {
    visibility: visible;
    opacity: 1;
}

.row-actions span {
    display: flex;
    align-items: center;
}

.row-actions span::after {
    content: "|";
    color: var(--text-dim);
    margin: 0 0.5rem;
}

.row-actions span:last-child::after {
    display: none;
}

.row-actions a {
    color: var(--forge-primary);
    text-decoration: none;
    transition: color 0.15s;
}

.row-actions a:hover {
    color: var(--forge-primary-dark);
    text-decoration: underline;
}

.row-actions .activate a {
    color: #16a34a;
}

.row-actions .activate a:hover {
    color: #15803d;
}

.row-actions .deactivate a {
    color: #dc2626;
}

.row-actions .deactivate a:hover {
    color: #b91c1c;
}

.row-actions .delete a {
    color: #dc2626;
}

.row-actions .delete a:hover {
    color: #b91c1c;
}

/* Meta columns */
.plugin-meta-cell {
    font-size: 0.8125rem;
    color: var(--text-muted);
    white-space: nowrap;
}

.plugin-meta-cell a {
    color: var(--forge-primary);
    text-decoration: none;
}

.plugin-meta-cell a:hover {
    text-decoration: underline;
}

.version-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.625rem;
    background: var(--bg-card-header);
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--text-muted);
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-badge.active {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

.status-badge.inactive {
    background: var(--bg-card-header);
    color: var(--text-muted);
}

.status-badge svg {
    width: 12px;
    height: 12px;
}

/* Requirements */
.requirements-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.375rem;
}

.req-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.6875rem;
    font-weight: 500;
}

.req-tag.met {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

.req-tag.unmet {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
}

.req-tag svg {
    width: 10px;
    height: 10px;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-state-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 1.5rem;
    background: linear-gradient(135deg, var(--forge-primary) 0%, var(--forge-secondary) 100%);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.empty-state-icon svg {
    width: 40px;
    height: 40px;
    color: #fff;
}

.empty-state h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 0.5rem 0;
}

.empty-state p {
    font-size: 0.875rem;
    color: var(--text-muted);
    margin: 0 0 1.5rem 0;
}

/* Documentation Card */
.docs-card {
    margin-top: 2rem;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    overflow: hidden;
}

.docs-card-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    background: var(--bg-card-header);
    border-bottom: 1px solid var(--border-color);
    cursor: pointer;
    user-select: none;
    transition: background 0.15s;
}

.docs-card-header:hover {
    background: var(--border-color);
}

.docs-card-header svg {
    width: 20px;
    height: 20px;
    color: var(--forge-primary);
}

.docs-card-header h3 {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
    flex: 1;
}

.docs-card-header .chevron {
    color: var(--text-muted);
    transition: transform 0.2s;
}

.docs-card.open .docs-card-header .chevron {
    transform: rotate(180deg);
}

.docs-card-body {
    display: none;
    padding: 1.5rem;
}

.docs-card.open .docs-card-body {
    display: block;
}

.docs-card-body p {
    font-size: 0.875rem;
    color: var(--text-muted);
    margin: 0 0 1rem 0;
    line-height: 1.6;
}

.code-example {
    background: #1e293b;
    border-radius: 10px;
    padding: 1.25rem;
    overflow-x: auto;
    font-family: 'SF Mono', 'Monaco', 'Inconsolata', monospace;
    font-size: 0.8125rem;
    line-height: 1.7;
    margin: 0 0 1.5rem 0;
}

.code-example .comment { color: #64748b; }
.code-example .keyword { color: #c084fc; }
.code-example .function { color: #38bdf8; }
.code-example .string { color: #4ade80; }
.code-example .variable { color: #fbbf24; }

.hooks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 0.75rem;
    margin-top: 1rem;
}

.hook-item {
    padding: 0.875rem 1rem;
    background: var(--bg-card-header);
    border-radius: 8px;
    border: 1px solid var(--border-color);
}

.hook-item h5 {
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--forge-primary);
    font-family: 'SF Mono', 'Monaco', monospace;
    margin: 0 0 0.25rem 0;
}

.hook-item p {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin: 0;
}

/* Bundled Plugin Badge */
.bundled-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.125rem 0.5rem;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
    border: 1px solid rgba(99, 102, 241, 0.2);
    border-radius: 4px;
    font-size: 0.625rem;
    font-weight: 600;
    color: var(--forge-primary);
    text-transform: uppercase;
    letter-spacing: 0.03em;
    margin-left: 0.5rem;
}

/* Modal Styles */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.2s;
}

.modal-overlay.active {
    opacity: 1;
    visibility: visible;
}

.modal-box {
    background: var(--bg-card);
    border-radius: 16px;
    width: 100%;
    max-width: 480px;
    margin: 1rem;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    transform: scale(0.95) translateY(10px);
    transition: transform 0.2s;
}

.modal-overlay.active .modal-box {
    transform: scale(1) translateY(0);
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.modal-header h3 {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.modal-header h3 svg {
    width: 22px;
    height: 22px;
    color: var(--forge-primary);
}

.modal-close {
    width: 32px;
    height: 32px;
    border: none;
    background: transparent;
    color: var(--text-muted);
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s;
}

.modal-close:hover {
    background: var(--bg-card-header);
    color: var(--text-primary);
}

.modal-close svg {
    width: 18px;
    height: 18px;
}

.modal-body {
    padding: 1.5rem;
}

/* Upload Zone */
.upload-zone {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 2rem;
    border: 2px dashed var(--border-color);
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
}

.upload-zone:hover,
.upload-zone.dragover {
    border-color: var(--forge-primary);
    background: rgba(99, 102, 241, 0.05);
}

.upload-zone svg {
    width: 48px;
    height: 48px;
    color: var(--forge-primary);
    margin-bottom: 1rem;
}

.upload-zone h4 {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 0.375rem 0;
}

.upload-zone p {
    font-size: 0.875rem;
    color: var(--text-muted);
    margin: 0 0 1rem 0;
}

.upload-zone .hint {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.75rem;
    color: var(--text-dim);
}

.upload-zone .hint svg {
    width: 14px;
    height: 14px;
    margin: 0;
    color: var(--text-dim);
}

.upload-zone input[type="file"] {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    border: 0;
}

/* Delete Modal */
.delete-modal {
    text-align: center;
    padding: 2rem;
}

.delete-modal-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 1.25rem;
    background: rgba(239, 68, 68, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.delete-modal-icon svg {
    width: 28px;
    height: 28px;
    color: #ef4444;
}

.delete-modal h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 0.75rem 0;
}

.delete-modal p {
    font-size: 0.875rem;
    color: var(--text-muted);
    margin: 0 0 1.5rem 0;
    line-height: 1.5;
}

.delete-modal-actions {
    display: flex;
    gap: 0.75rem;
    justify-content: center;
}

.delete-modal-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.15s;
    border: none;
}

.delete-modal-btn.cancel {
    background: var(--bg-card-header);
    color: var(--text-primary);
}

.delete-modal-btn.cancel:hover {
    background: var(--border-color);
}

.delete-modal-btn.delete {
    background: #ef4444;
    color: #fff;
}

.delete-modal-btn.delete:hover {
    background: #dc2626;
}

.delete-modal-btn svg {
    width: 16px;
    height: 16px;
}

/* Responsive */
@media (max-width: 900px) {
    .plugins-table th:nth-child(3),
    .plugins-table td:nth-child(3),
    .plugins-table th:nth-child(4),
    .plugins-table td:nth-child(4) {
        display: none;
    }
}

@media (max-width: 640px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .plugins-table th:nth-child(2),
    .plugins-table td:nth-child(2) {
        display: none;
    }
    
    .row-actions {
        visibility: visible;
        opacity: 1;
    }
}
</style>

<div class="plugins-page">
    <!-- Page Header -->
    <div class="page-header">
        <h1>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                <path d="M2 17l10 5 10-5"/>
                <path d="M2 12l10 5 10-5"/>
            </svg>
            Plugins
        </h1>
        <button type="button" class="btn-add-new" onclick="openUploadModal()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="17 8 12 3 7 8"/>
                <line x1="12" y1="3" x2="12" y2="15"/>
            </svg>
            Upload Plugin
        </button>
    </div>
    
    <!-- Filter Navigation -->
    <ul class="subsubsub">
        <li>
            <a href="?status=all" class="<?= $filter === 'all' ? 'current' : '' ?>">
                All <span class="count">(<?= count(Plugin::getAll()) ?>)</span>
            </a>
        </li>
        <li>
            <a href="?status=active" class="<?= $filter === 'active' ? 'current' : '' ?>">
                Active <span class="count">(<?= $activeCount ?>)</span>
            </a>
        </li>
        <li>
            <a href="?status=inactive" class="<?= $filter === 'inactive' ? 'current' : '' ?>">
                Inactive <span class="count">(<?= $inactiveCount ?>)</span>
            </a>
        </li>
    </ul>
    
    <!-- Plugins Table -->
    <div class="plugins-table-wrap">
        <?php if (empty($plugins)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                    <path d="M2 17l10 5 10-5"/>
                    <path d="M2 12l10 5 10-5"/>
                </svg>
            </div>
            <h3>No plugins found</h3>
            <p>Upload a plugin to extend your site's functionality.</p>
            <button type="button" class="btn-add-new" onclick="openUploadModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="17 8 12 3 7 8"/>
                    <line x1="12" y1="3" x2="12" y2="15"/>
                </svg>
                Upload Plugin
            </button>
        </div>
        <?php else: ?>
        <table class="plugins-table">
            <thead>
                <tr>
                    <th>Plugin</th>
                    <th>Version</th>
                    <th>Author</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plugins as $plugin): 
                    $isBundled = in_array($plugin['slug'], ['anvil']);
                    $hasSettings = isset($pluginPages[$plugin['slug']]);
                ?>
                <tr class="<?= $plugin['active'] ? 'active' : 'inactive' ?>">
                    <td class="plugin-info-cell">
                        <div class="plugin-title">
                            <div class="plugin-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                                    <path d="M2 17l10 5 10-5"/>
                                    <path d="M2 12l10 5 10-5"/>
                                </svg>
                            </div>
                            <h4 class="plugin-name">
                                <?= esc($plugin['name']) ?>
                                <?php if ($isBundled): ?>
                                <span class="bundled-badge">Bundled</span>
                                <?php endif; ?>
                            </h4>
                        </div>
                        <p class="plugin-description"><?= esc($plugin['description'] ?? 'No description available.') ?></p>
                        <div class="row-actions">
                            <?php if ($plugin['active']): ?>
                                <span class="deactivate">
                                    <a href="?action=deactivate&plugin=<?= urlencode($plugin['slug']) ?>&csrf=<?= csrfToken() ?>">Deactivate</a>
                                </span>
                                <?php if ($hasSettings): ?>
                                <span class="settings">
                                    <a href="<?= ADMIN_URL ?>/plugin-page.php?page=<?= urlencode($plugin['slug']) ?>">Settings</a>
                                </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="activate">
                                    <a href="?action=activate&plugin=<?= urlencode($plugin['slug']) ?>&csrf=<?= csrfToken() ?>">Activate</a>
                                </span>
                            <?php endif; ?>
                            <?php if (!$isBundled): ?>
                            <span class="delete">
                                <a href="#" onclick="confirmDelete('<?= esc($plugin['slug']) ?>', '<?= esc($plugin['name']) ?>'); return false;">Delete</a>
                            </span>
                            <?php endif; ?>
                            <?php if (!empty($plugin['plugin_uri'])): ?>
                            <span class="view-details">
                                <a href="<?= esc($plugin['plugin_uri']) ?>" target="_blank" rel="noopener">View details</a>
                            </span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="plugin-meta-cell">
                        <span class="version-badge"><?= esc($plugin['version'] ?? '1.0.0') ?></span>
                        <?php if (!empty($plugin['requires_cms'])): ?>
                        <div class="requirements-list" style="margin-top: 0.5rem;">
                            <?php 
                            $meetsReq = version_compare(CMS_VERSION, $plugin['requires_cms'], '>=');
                            ?>
                            <span class="req-tag <?= $meetsReq ? 'met' : 'unmet' ?>">
                                <?php if ($meetsReq): ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                <?php else: ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                <?php endif; ?>
                                CMS <?= esc($plugin['requires_cms']) ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="plugin-meta-cell">
                        <?php if (!empty($plugin['author_uri'])): ?>
                            <a href="<?= esc($plugin['author_uri']) ?>" target="_blank" rel="noopener"><?= esc($plugin['author'] ?? 'Unknown') ?></a>
                        <?php else: ?>
                            <?= esc($plugin['author'] ?? 'Unknown') ?>
                        <?php endif; ?>
                    </td>
                    <td class="plugin-meta-cell">
                        <?php if ($plugin['active']): ?>
                        <span class="status-badge active">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            Active
                        </span>
                        <?php else: ?>
                        <span class="status-badge inactive">Inactive</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    
    <!-- Developer Documentation -->
    <div class="docs-card" id="docsCard">
        <div class="docs-card-header" onclick="toggleDocs()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/>
            </svg>
            <h3>Plugin Development Guide</h3>
            <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </div>
        <div class="docs-card-body">
            <p>Create plugins by adding a folder to <code>/plugins</code> with a main PHP file matching the folder name. For comprehensive documentation, visit the <a href="<?= SITE_URL ?>/docs/plugin-development.html" target="_blank">Plugin Development Guide</a>.</p>
            
            <pre class="code-example"><span class="keyword">&lt;?php</span>
<span class="comment">/**
 * Plugin Name: My Awesome Plugin
 * Description: Adds amazing features to VoidForge
 * Version: 1.0.0
 * Author: Your Name
 * Requires: 0.2.5
 */</span>

<span class="comment">// Add a shortcode: [hello name="World"]</span>
<span class="function">add_shortcode</span>(<span class="string">'hello'</span>, <span class="keyword">function</span>(<span class="variable">$atts</span>) {
    <span class="variable">$name</span> = <span class="variable">$atts</span>[<span class="string">'name'</span>] ?? <span class="string">'World'</span>;
    <span class="keyword">return</span> <span class="string">"&lt;p&gt;Hello, {$name}!&lt;/p&gt;"</span>;
});

<span class="comment">// Filter content</span>
<span class="function">add_filter</span>(<span class="string">'the_content'</span>, <span class="keyword">function</span>(<span class="variable">$content</span>) {
    <span class="keyword">return</span> <span class="variable">$content</span> . <span class="string">'&lt;p&gt;Powered by my plugin&lt;/p&gt;'</span>;
});</pre>

            <h4 style="margin: 0 0 0.75rem; font-size: 0.875rem; font-weight: 600; color: var(--text-primary);">Available Hooks</h4>
            <div class="hooks-grid">
                <div class="hook-item">
                    <h5>plugins_loaded</h5>
                    <p>After all plugins load</p>
                </div>
                <div class="hook-item">
                    <h5>init</h5>
                    <p>During initialization</p>
                </div>
                <div class="hook-item">
                    <h5>the_content</h5>
                    <p>Filter post content</p>
                </div>
                <div class="hook-item">
                    <h5>the_title</h5>
                    <p>Filter post titles</p>
                </div>
                <div class="hook-item">
                    <h5>save_post</h5>
                    <p>When a post is saved</p>
                </div>
                <div class="hook-item">
                    <h5>admin_init</h5>
                    <p>On admin pages</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal-overlay" id="uploadModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="17 8 12 3 7 8"/>
                    <line x1="12" y1="3" x2="12" y2="15"/>
                </svg>
                Upload Plugin
            </h3>
            <button type="button" class="modal-close" onclick="closeUploadModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <form method="POST" enctype="multipart/form-data">
                <?= csrfField() ?>
                <label class="upload-zone" id="uploadZone">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                        <path d="M2 17l10 5 10-5"/>
                        <path d="M2 12l10 5 10-5"/>
                    </svg>
                    <h4>Drop your plugin ZIP here</h4>
                    <p>or click to browse your computer</p>
                    <span class="hint">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="16" x2="12" y2="12"/>
                            <line x1="12" y1="8" x2="12.01" y2="8"/>
                        </svg>
                        ZIP file containing plugin folder
                    </span>
                    <input type="file" name="plugin_zip" accept=".zip" onchange="this.form.submit()">
                </label>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box delete-modal">
        <div class="delete-modal-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                <line x1="10" y1="11" x2="10" y2="17"></line>
                <line x1="14" y1="11" x2="14" y2="17"></line>
            </svg>
        </div>
        <h3>Delete Plugin?</h3>
        <p>Are you sure you want to delete "<strong id="deletePluginName"></strong>"? This will permanently remove all plugin files and cannot be undone.</p>
        <div class="delete-modal-actions">
            <button type="button" class="delete-modal-btn cancel" onclick="closeDeleteModal()">Cancel</button>
            <a href="#" id="deletePluginLink" class="delete-modal-btn delete">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
                Delete Plugin
            </a>
        </div>
    </div>
</div>

<script>
function openUploadModal() {
    document.getElementById('uploadModal').classList.add('active');
}

function closeUploadModal() {
    document.getElementById('uploadModal').classList.remove('active');
}

function confirmDelete(slug, name) {
    document.getElementById('deletePluginName').textContent = name;
    document.getElementById('deletePluginLink').href = '?action=delete&plugin=' + encodeURIComponent(slug) + '&csrf=<?= csrfToken() ?>';
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
}

function toggleDocs() {
    document.getElementById('docsCard').classList.toggle('open');
}

// Modal close on outside click
document.querySelectorAll('.modal-overlay').forEach(function(modal) {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('active');
        }
    });
});

// Drag and drop for upload zone
var uploadZone = document.getElementById('uploadZone');
if (uploadZone) {
    ['dragenter', 'dragover'].forEach(function(event) {
        uploadZone.addEventListener(event, function(e) {
            e.preventDefault();
            uploadZone.classList.add('dragover');
        });
    });
    
    ['dragleave', 'drop'].forEach(function(event) {
        uploadZone.addEventListener(event, function(e) {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
        });
    });
    
    uploadZone.addEventListener('drop', function(e) {
        var files = e.dataTransfer.files;
        if (files.length) {
            uploadZone.querySelector('input[type="file"]').files = files;
            uploadZone.closest('form').submit();
        }
    });
}

// Escape to close modals
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(function(m) {
            m.classList.remove('active');
        });
    }
});
</script>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
