<?php
/**
 * Plugins Management - VoidForge CMS v0.1.2
 * Enhanced plugin management with cards, settings, and upload
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
                    // Remove existing
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
            
            // Cleanup
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
$view = $_GET['view'] ?? 'cards';

if ($filter === 'active') {
    $plugins = array_filter($plugins, fn($p) => $p['active']);
} elseif ($filter === 'inactive') {
    $plugins = array_filter($plugins, fn($p) => !$p['active']);
}

include ADMIN_PATH . '/includes/header.php';
?>

<style>
.plugins-page { max-width: 1200px; margin: 0 auto; }

.plugins-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
    gap: 1rem;
    flex-wrap: wrap;
}

.plugins-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 0.75rem;
}

.btn-upload {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: linear-gradient(135deg, var(--forge-primary) 0%, var(--forge-secondary) 100%);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    box-shadow: 0 4px 15px var(--forge-shadow-color);
    transition: all 0.2s ease;
}

.btn-upload:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px var(--forge-shadow-color-hover);
}

.btn-upload svg { width: 18px; height: 18px; }

/* Toolbar */
.plugins-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
    gap: 1rem;
    flex-wrap: wrap;
}

.plugins-filters {
    display: flex;
    gap: 0.25rem;
}

.filter-link {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text-muted);
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.15s;
}

.filter-link:hover {
    color: var(--text-primary);
    background: var(--bg-card-header);
}

.filter-link.active {
    color: var(--forge-primary);
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
    font-size: 0.6875rem;
    font-weight: 600;
    background: var(--border-color);
    color: var(--text-muted);
    border-radius: 10px;
}

.filter-link.active .filter-count {
    background: var(--forge-primary);
    color: #fff;
}

.view-toggle {
    display: flex;
    background: var(--bg-card-header);
    border-radius: 8px;
    padding: 0.25rem;
}

.view-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 32px;
    border: none;
    background: transparent;
    color: var(--text-muted);
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.15s;
}

.view-btn:hover { color: var(--text-primary); }
.view-btn.active { background: var(--bg-card); color: var(--forge-primary); box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.view-btn svg { width: 18px; height: 18px; }

/* Plugin Cards Grid */
.plugins-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
    gap: 1.25rem;
}

.plugin-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.2s ease;
}

.plugin-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
}

.plugin-card.active {
    border-color: var(--forge-primary);
    box-shadow: 0 0 0 1px var(--forge-primary);
}

.plugin-card-header {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1.5rem;
    background: var(--bg-card-header);
    border-bottom: 1px solid var(--border-color);
}

.plugin-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    color: #94a3b8;
}

.plugin-card.active .plugin-icon {
    background: linear-gradient(135deg, var(--forge-primary) 0%, var(--forge-secondary) 100%);
    color: #fff;
}

.plugin-icon svg { width: 26px; height: 26px; }

.plugin-info { flex: 1; min-width: 0; }

.plugin-name {
    font-size: 1.0625rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0 0 0.375rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.plugin-version {
    font-size: 0.6875rem;
    font-weight: 600;
    padding: 0.125rem 0.5rem;
    background: var(--border-color);
    color: var(--text-muted);
    border-radius: 4px;
}

.plugin-author {
    font-size: 0.8125rem;
    color: var(--text-muted);
}

.plugin-author a {
    color: var(--forge-primary);
    text-decoration: none;
}

.plugin-author a:hover { text-decoration: underline; }

.plugin-status {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.plugin-status.active {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

.plugin-status.inactive {
    background: var(--bg-card-header);
    color: var(--text-muted);
}

.plugin-card-body {
    padding: 1.5rem;
}

.plugin-description {
    font-size: 0.875rem;
    color: var(--text-muted);
    line-height: 1.6;
    margin: 0 0 1rem 0;
}

.plugin-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    font-size: 0.75rem;
    color: var(--text-dim);
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.plugin-meta-item {
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

.plugin-meta-item svg { width: 14px; height: 14px; opacity: 0.6; }

.plugin-requirements {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.req-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.625rem;
    background: var(--bg-card-header);
    border-radius: 6px;
    font-size: 0.6875rem;
    color: var(--text-muted);
}

.req-badge.met { background: rgba(16, 185, 129, 0.1); color: #10b981; }
.req-badge.unmet { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

.plugin-actions {
    display: flex;
    gap: 0.5rem;
}

.plugin-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.8125rem;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.15s;
    cursor: pointer;
    border: 1px solid var(--border-color);
    background: var(--bg-card);
    color: var(--text-primary);
}

.plugin-btn:hover {
    background: var(--bg-card-header);
}

.plugin-btn svg { width: 16px; height: 16px; }

.plugin-btn.primary {
    background: var(--forge-primary);
    border-color: var(--forge-primary);
    color: #fff;
}

.plugin-btn.primary:hover {
    background: var(--forge-primary-dark);
}

.plugin-btn.danger {
    color: #ef4444;
    border-color: #fecaca;
}

.plugin-btn.danger:hover {
    background: #fef2f2;
}

.plugin-btn.settings {
    background: var(--bg-card-header);
}

/* Table View */
.plugins-table {
    width: 100%;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    overflow: hidden;
}

.plugins-table thead th {
    background: var(--bg-card-header);
    padding: 1rem 1.25rem;
    text-align: left;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 1px solid var(--border-color);
}

.plugins-table tbody tr {
    border-bottom: 1px solid var(--bg-card-header);
    transition: background 0.15s;
}

.plugins-table tbody tr:last-child { border-bottom: none; }
.plugins-table tbody tr:hover { background: var(--bg-body); }

.plugins-table tbody tr.active-row {
    background: linear-gradient(90deg, rgba(99, 102, 241, 0.04) 0%, transparent 100%);
}

.plugins-table td {
    padding: 1.25rem;
    vertical-align: middle;
}

.table-plugin-info {
    display: flex;
    align-items: center;
    gap: 0.875rem;
}

.table-plugin-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--bg-card-header);
    color: var(--text-muted);
}

.active-row .table-plugin-icon {
    background: linear-gradient(135deg, var(--forge-primary), var(--forge-secondary));
    color: #fff;
}

.table-plugin-icon svg { width: 20px; height: 20px; }

.table-plugin-name {
    font-weight: 600;
    color: var(--text-primary);
}

.table-plugin-author {
    font-size: 0.8125rem;
    color: var(--text-muted);
}

.table-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
    opacity: 0;
    transition: opacity 0.15s;
}

.plugins-table tbody tr:hover .table-actions { opacity: 1; }

/* Empty State - Enhanced Design */
.plugins-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 5rem 2rem;
    background: linear-gradient(180deg, var(--bg-card) 0%, var(--bg-card-header) 100%);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    min-height: 400px;
}

.plugins-empty-icon {
    width: 100px;
    height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
    border-radius: 50%;
    margin-bottom: 1.75rem;
    position: relative;
}

.plugins-empty-icon::before {
    content: '';
    position: absolute;
    inset: -5px;
    border-radius: 50%;
    border: 2px dashed rgba(99, 102, 241, 0.25);
    animation: spin 20s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.plugins-empty-icon svg {
    width: 48px;
    height: 48px;
    color: var(--forge-primary);
    opacity: 0.85;
}

.plugins-empty-content {
    max-width: 420px;
}

.plugins-empty-content h2 {
    font-size: 1.375rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0 0 0.625rem 0;
}

.plugins-empty-content p {
    font-size: 0.9375rem;
    color: var(--text-muted);
    line-height: 1.6;
    margin: 0 0 2rem 0;
}

.plugins-empty-content code {
    background: rgba(99, 102, 241, 0.1);
    color: var(--forge-primary);
    padding: 0.125rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8125rem;
    font-weight: 500;
}

.plugins-empty-actions {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.plugins-empty-action {
    display: inline-flex;
    align-items: center;
    gap: 0.625rem;
    padding: 0.875rem 1.5rem;
    font-size: 0.9375rem;
    font-weight: 600;
    color: #fff;
    background: linear-gradient(135deg, var(--forge-primary) 0%, var(--forge-secondary) 100%);
    border: none;
    border-radius: 10px;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
}

.plugins-empty-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
}

.plugins-empty-action svg {
    width: 20px;
    height: 20px;
}

.plugins-empty-hint {
    font-size: 0.8125rem;
    color: var(--text-dim);
}

.plugins-empty-hint a {
    color: var(--forge-primary);
    text-decoration: none;
}

.plugins-empty-hint a:hover {
    text-decoration: underline;
}

/* Upload Modal - Enhanced */
.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.7);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(6px);
    padding: 1rem;
}

.modal-overlay.active { 
    display: flex;
    animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-box {
    background: var(--bg-card);
    border-radius: 20px;
    padding: 0;
    max-width: 480px;
    width: 100%;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    animation: slideUp 0.25s ease;
    overflow: hidden;
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    background: var(--bg-card-header);
    border-bottom: 1px solid var(--border-color);
}

.modal-header h3 {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.625rem;
}

.modal-header h3 svg {
    width: 20px;
    height: 20px;
    color: var(--forge-primary);
}

.modal-close {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: 1px solid var(--border-color);
    border-radius: 10px;
    color: var(--text-muted);
    cursor: pointer;
    transition: all 0.15s;
}

.modal-close:hover {
    background: rgba(239, 68, 68, 0.1);
    border-color: rgba(239, 68, 68, 0.3);
    color: #ef4444;
}

.modal-close svg {
    width: 18px;
    height: 18px;
}

.modal-body {
    padding: 1.5rem;
}

.upload-zone {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border: 2px dashed var(--border-color);
    border-radius: 16px;
    padding: 3rem 2rem;
    text-align: center;
    transition: all 0.2s;
    cursor: pointer;
    background: linear-gradient(180deg, transparent 0%, var(--bg-card-header) 100%);
}

.upload-zone:hover,
.upload-zone.dragover {
    border-color: var(--forge-primary);
    background: linear-gradient(180deg, rgba(99, 102, 241, 0.03) 0%, rgba(99, 102, 241, 0.08) 100%);
}

.upload-zone svg {
    width: 52px;
    height: 52px;
    color: var(--forge-primary);
    opacity: 0.6;
    margin-bottom: 1.25rem;
    transition: all 0.2s;
}

.upload-zone:hover svg,
.upload-zone.dragover svg {
    opacity: 1;
    transform: translateY(-3px);
}

.upload-zone h4 {
    font-size: 1.0625rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 0.375rem 0;
}

.upload-zone p {
    font-size: 0.875rem;
    color: var(--text-muted);
    margin: 0 0 1rem 0;
}

.upload-zone .upload-hint {
    font-size: 0.75rem;
    color: var(--text-dim);
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

.upload-zone .upload-hint svg {
    width: 14px;
    height: 14px;
    margin: 0;
    opacity: 0.5;
}

.upload-zone input[type="file"] {
    display: none;
}

/* Developer Info Card */
.info-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    margin-top: 2rem;
    overflow: hidden;
}

.info-card-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1.25rem 1.5rem;
    background: var(--bg-card-header);
    border-bottom: 1px solid var(--border-color);
    cursor: pointer;
}

.info-card-header h3 {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
    flex: 1;
}

.info-card-header svg { width: 20px; height: 20px; color: var(--text-muted); }

.info-card-body {
    padding: 1.5rem;
    display: none;
}

.info-card.open .info-card-body { display: block; }

.info-card-body p {
    font-size: 0.875rem;
    color: var(--text-muted);
    margin: 0 0 1rem 0;
}

.code-block {
    background: #0f172a;
    color: #e2e8f0;
    padding: 1.25rem;
    border-radius: 12px;
    font-family: 'JetBrains Mono', 'Monaco', monospace;
    font-size: 0.8125rem;
    line-height: 1.8;
    overflow-x: auto;
    white-space: pre;
}

.code-block .comment { color: #64748b; }
.code-block .keyword { color: #f472b6; }
.code-block .string { color: #a5f3fc; }
.code-block .function { color: #fbbf24; }
.code-block .variable { color: #c4b5fd; }

.hooks-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-top: 1.5rem;
}

.hook-card {
    padding: 1rem;
    background: var(--bg-card-header);
    border-radius: 10px;
}

.hook-card h5 {
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 0.5rem 0;
    font-family: monospace;
}

.hook-card p {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin: 0;
}

/* Delete Confirmation Modal */
.delete-modal {
    text-align: center;
    padding: 2rem;
    max-width: 400px;
}

.delete-modal-icon {
    width: 72px;
    height: 72px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(239, 68, 68, 0.1);
    border-radius: 50%;
    margin: 0 auto 1.25rem;
}

.delete-modal-icon svg {
    width: 36px;
    height: 36px;
    color: #ef4444;
}

.delete-modal-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0 0 0.625rem 0;
}

.delete-modal-text {
    font-size: 0.9375rem;
    color: var(--text-muted);
    line-height: 1.6;
    margin: 0 0 1.75rem 0;
}

.delete-modal-text strong {
    color: var(--text-primary);
    font-weight: 600;
}

.delete-modal-actions {
    display: flex;
    gap: 0.75rem;
    justify-content: center;
}

.delete-modal-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 600;
    border-radius: 10px;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s;
}

.delete-modal-btn.cancel {
    background: var(--bg-card-header);
    border: 1px solid var(--border-color);
    color: var(--text-secondary);
}

.delete-modal-btn.cancel:hover {
    background: var(--bg-card);
    border-color: var(--text-muted);
}

.delete-modal-btn.delete {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    border: none;
    color: #fff;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.delete-modal-btn.delete:hover {
    box-shadow: 0 6px 16px rgba(239, 68, 68, 0.4);
    transform: translateY(-1px);
}

.delete-modal-btn svg {
    width: 16px;
    height: 16px;
}

@media (max-width: 768px) {
    .plugins-header { flex-direction: column; align-items: flex-start; }
    .plugins-toolbar { flex-direction: column; align-items: flex-start; }
    .plugins-grid { grid-template-columns: 1fr; }
    .hooks-grid { grid-template-columns: 1fr; }
    
    .delete-modal-actions {
        flex-direction: column;
    }
    
    .delete-modal-btn {
        width: 100%;
    }
}
</style>

<div class="plugins-page">
    <div class="plugins-header">
        <h1>Plugins</h1>
        <div class="header-actions">
            <button type="button" class="btn-upload" onclick="openUploadModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="17 8 12 3 7 8"/>
                    <line x1="12" y1="3" x2="12" y2="15"/>
                </svg>
                Upload Plugin
            </button>
        </div>
    </div>

    <div class="plugins-toolbar">
        <div class="plugins-filters">
            <a href="?status=all&view=<?= $view ?>" class="filter-link <?= $filter === 'all' ? 'active' : '' ?>">
                All <span class="filter-count"><?= count(Plugin::getAll()) ?></span>
            </a>
            <a href="?status=active&view=<?= $view ?>" class="filter-link <?= $filter === 'active' ? 'active' : '' ?>">
                Active <span class="filter-count"><?= $activeCount ?></span>
            </a>
            <a href="?status=inactive&view=<?= $view ?>" class="filter-link <?= $filter === 'inactive' ? 'active' : '' ?>">
                Inactive <span class="filter-count"><?= $inactiveCount ?></span>
            </a>
        </div>
        
        <div class="view-toggle">
            <a href="?status=<?= $filter ?>&view=cards" class="view-btn <?= $view === 'cards' ? 'active' : '' ?>" title="Card View">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"/>
                    <rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/>
                    <rect x="3" y="14" width="7" height="7"/>
                </svg>
            </a>
            <a href="?status=<?= $filter ?>&view=table" class="view-btn <?= $view === 'table' ? 'active' : '' ?>" title="Table View">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="8" y1="6" x2="21" y2="6"/>
                    <line x1="8" y1="12" x2="21" y2="12"/>
                    <line x1="8" y1="18" x2="21" y2="18"/>
                    <line x1="3" y1="6" x2="3.01" y2="6"/>
                    <line x1="3" y1="12" x2="3.01" y2="12"/>
                    <line x1="3" y1="18" x2="3.01" y2="18"/>
                </svg>
            </a>
        </div>
    </div>

    <?php if (empty($plugins)): ?>
    <div class="plugins-empty-state">
        <div class="plugins-empty-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                <path d="M2 17l10 5 10-5"/>
                <path d="M2 12l10 5 10-5"/>
            </svg>
        </div>
        <div class="plugins-empty-content">
            <h2>No Plugins Installed</h2>
            <p>Extend VoidForge with plugins to add new features, integrations, and functionality. Upload a ZIP file or place plugins in the <code>/plugins</code> directory.</p>
            <div class="plugins-empty-actions">
                <button type="button" class="plugins-empty-action" onclick="openUploadModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                    Upload Plugin
                </button>
                <span class="plugins-empty-hint">or <a href="#devDocs" onclick="document.getElementById('devDocs').classList.add('open')">learn to create your own</a></span>
            </div>
        </div>
    </div>
    
    <?php elseif ($view === 'cards'): ?>
    <div class="plugins-grid">
        <?php foreach ($plugins as $plugin): ?>
        <div class="plugin-card <?= $plugin['active'] ? 'active' : '' ?>">
            <div class="plugin-card-header">
                <div class="plugin-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                        <path d="M2 17l10 5 10-5"/>
                        <path d="M2 12l10 5 10-5"/>
                    </svg>
                </div>
                <div class="plugin-info">
                    <h3 class="plugin-name">
                        <?= esc($plugin['name']) ?>
                        <span class="plugin-version">v<?= esc($plugin['version']) ?></span>
                    </h3>
                    <div class="plugin-author">
                        <?php if ($plugin['author_uri']): ?>
                        By <a href="<?= esc($plugin['author_uri']) ?>" target="_blank"><?= esc($plugin['author']) ?></a>
                        <?php elseif ($plugin['author']): ?>
                        By <?= esc($plugin['author']) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="plugin-status <?= $plugin['active'] ? 'active' : 'inactive' ?>">
                    <?= $plugin['active'] ? 'Active' : 'Inactive' ?>
                </span>
            </div>
            <div class="plugin-card-body">
                <p class="plugin-description"><?= esc($plugin['description']) ?: 'No description available.' ?></p>
                
                <?php if ($plugin['requires_php'] || $plugin['requires_cms']): ?>
                <div class="plugin-requirements">
                    <?php if ($plugin['requires_php']): 
                        $phpMet = version_compare(PHP_VERSION, $plugin['requires_php'], '>=');
                    ?>
                    <span class="req-badge <?= $phpMet ? 'met' : 'unmet' ?>">
                        PHP <?= esc($plugin['requires_php']) ?>+
                    </span>
                    <?php endif; ?>
                    <?php if ($plugin['requires_cms']): 
                        $cmsMet = version_compare(CMS_VERSION, $plugin['requires_cms'], '>=');
                    ?>
                    <span class="req-badge <?= $cmsMet ? 'met' : 'unmet' ?>">
                        CMS <?= esc($plugin['requires_cms']) ?>+
                    </span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="plugin-actions">
                    <?php if ($plugin['active']): ?>
                    <a href="?action=deactivate&plugin=<?= urlencode($plugin['slug']) ?>&csrf=<?= csrfToken() ?>" 
                       class="plugin-btn danger">Deactivate</a>
                    <?php 
                    // Check for settings page
                    $settingsPage = Plugin::getAdminPage($plugin['slug'] . '-settings');
                    if ($settingsPage): ?>
                    <a href="<?= ADMIN_URL ?>/plugin-page.php?page=<?= urlencode($plugin['slug']) ?>-settings" 
                       class="plugin-btn settings">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4"/>
                        </svg>
                        Settings
                    </a>
                    <?php endif; ?>
                    <?php else: ?>
                    <a href="?action=activate&plugin=<?= urlencode($plugin['slug']) ?>&csrf=<?= csrfToken() ?>" 
                       class="plugin-btn primary">Activate</a>
                    <button type="button" class="plugin-btn danger" 
                            onclick="confirmDelete('<?= esc($plugin['slug']) ?>', '<?= esc(addslashes($plugin['name'])) ?>')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        </svg>
                        Delete
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php else: ?>
    <table class="plugins-table">
        <thead>
            <tr>
                <th>Plugin</th>
                <th>Description</th>
                <th>Version</th>
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($plugins as $plugin): ?>
            <tr class="<?= $plugin['active'] ? 'active-row' : '' ?>">
                <td>
                    <div class="table-plugin-info">
                        <div class="table-plugin-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                                <path d="M2 17l10 5 10-5"/>
                                <path d="M2 12l10 5 10-5"/>
                            </svg>
                        </div>
                        <div>
                            <div class="table-plugin-name"><?= esc($plugin['name']) ?></div>
                            <div class="table-plugin-author">
                                <?php if ($plugin['author']): ?>By <?= esc($plugin['author']) ?><?php endif; ?>
                            </div>
                        </div>
                    </div>
                </td>
                <td style="color: var(--text-muted); font-size: 0.875rem;">
                    <?= esc(truncate($plugin['description'], 100)) ?>
                </td>
                <td>
                    <span class="plugin-version">v<?= esc($plugin['version']) ?></span>
                </td>
                <td>
                    <div class="table-actions">
                        <?php if ($plugin['active']): ?>
                        <a href="?action=deactivate&plugin=<?= urlencode($plugin['slug']) ?>&csrf=<?= csrfToken() ?>" 
                           class="plugin-btn danger">Deactivate</a>
                        <?php else: ?>
                        <a href="?action=activate&plugin=<?= urlencode($plugin['slug']) ?>&csrf=<?= csrfToken() ?>" 
                           class="plugin-btn primary">Activate</a>
                        <button type="button" class="plugin-btn danger" 
                                onclick="confirmDelete('<?= esc($plugin['slug']) ?>', '<?= esc(addslashes($plugin['name'])) ?>')">Delete</button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <!-- Developer Documentation -->
    <div class="info-card" id="devDocs">
        <div class="info-card-header" onclick="toggleDevDocs()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/>
            </svg>
            <h3>Plugin Development Guide</h3>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-left: auto;">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </div>
        <div class="info-card-body">
            <p>Create plugins by adding a folder to <code>/plugins</code> with a main PHP file matching the folder name.</p>
            
            <pre class="code-block"><span class="keyword">&lt;?php</span>
<span class="comment">/**
 * Plugin Name: My Awesome Plugin
 * Description: Adds amazing features to VoidForge
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yoursite.com
 * Requires PHP: 8.0
 * Requires CMS: 0.1.2
 */</span>

<span class="comment">// Activation hook</span>
<span class="function">add_action</span>(<span class="string">'plugin_activate_my-plugin'</span>, <span class="keyword">function</span>() {
    <span class="comment">// Run on activation</span>
});

<span class="comment">// Add shortcode: [hello name="World"]</span>
<span class="function">add_shortcode</span>(<span class="string">'hello'</span>, <span class="keyword">function</span>(<span class="variable">$atts</span>) {
    <span class="variable">$name</span> = <span class="variable">$atts</span>[<span class="string">'name'</span>] ?? <span class="string">'World'</span>;
    <span class="keyword">return</span> <span class="string">"&lt;p&gt;Hello, {$name}!&lt;/p&gt;"</span>;
});

<span class="comment">// Filter content</span>
<span class="function">add_filter</span>(<span class="string">'the_content'</span>, <span class="keyword">function</span>(<span class="variable">$content</span>) {
    <span class="keyword">return</span> <span class="variable">$content</span> . <span class="string">'&lt;p&gt;Powered by my plugin&lt;/p&gt;'</span>;
});

<span class="comment">// Register settings page</span>
<span class="function">add_admin_page</span>(<span class="string">'my-plugin-settings'</span>, [
    <span class="string">'title'</span> => <span class="string">'My Plugin Settings'</span>,
    <span class="string">'icon'</span> => <span class="string">'settings'</span>,
    <span class="string">'callback'</span> => <span class="keyword">function</span>() {
        <span class="keyword">echo</span> <span class="string">'&lt;h2&gt;My Plugin Settings&lt;/h2&gt;'</span>;
    }
]);</pre>

            <h4 style="margin: 1.5rem 0 1rem; font-size: 0.9375rem; color: var(--text-primary);">Available Hooks</h4>
            <div class="hooks-grid">
                <div class="hook-card">
                    <h5>plugins_loaded</h5>
                    <p>Fires after all plugins are loaded</p>
                </div>
                <div class="hook-card">
                    <h5>init</h5>
                    <p>Fires during initialization</p>
                </div>
                <div class="hook-card">
                    <h5>the_content</h5>
                    <p>Filter post/page content</p>
                </div>
                <div class="hook-card">
                    <h5>the_title</h5>
                    <p>Filter post/page titles</p>
                </div>
                <div class="hook-card">
                    <h5>save_post</h5>
                    <p>Fires when a post is saved</p>
                </div>
                <div class="hook-card">
                    <h5>admin_init</h5>
                    <p>Fires on admin pages</p>
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
                    <span class="upload-hint">
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
        <h3 class="delete-modal-title">Delete Plugin?</h3>
        <p class="delete-modal-text">Are you sure you want to delete "<strong id="deletePluginName"></strong>"? This will permanently remove all plugin files and cannot be undone.</p>
        <div class="delete-modal-actions">
            <button type="button" class="delete-modal-btn cancel" onclick="closeDeleteModal()">
                Cancel
            </button>
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

function toggleDevDocs() {
    document.getElementById('devDocs').classList.toggle('open');
}

// Modal close on outside click
document.querySelectorAll('.modal-overlay').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('active');
        }
    });
});

// Drag and drop
const uploadZone = document.getElementById('uploadZone');
if (uploadZone) {
    ['dragenter', 'dragover'].forEach(event => {
        uploadZone.addEventListener(event, e => {
            e.preventDefault();
            uploadZone.classList.add('dragover');
        });
    });
    
    ['dragleave', 'drop'].forEach(event => {
        uploadZone.addEventListener(event, e => {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
        });
    });
    
    uploadZone.addEventListener('drop', e => {
        const files = e.dataTransfer.files;
        if (files.length) {
            uploadZone.querySelector('input[type="file"]').files = files;
            uploadZone.closest('form').submit();
        }
    });
}

// Escape to close modals
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active'));
    }
});
</script>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
