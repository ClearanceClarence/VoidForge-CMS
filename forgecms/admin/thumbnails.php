<?php
/**
 * Thumbnail Manager - Forge CMS v1.0.10
 * Modern redesigned interface
 */

define('CMS_ROOT', dirname(__DIR__));
require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/user.php';
require_once CMS_ROOT . '/includes/post.php';
require_once CMS_ROOT . '/includes/media.php';

Post::init();

User::startSession();
User::requireLogin();

if (!User::isAdmin()) {
    redirect(ADMIN_URL . '/');
}

$currentPage = 'thumbnails';
$pageTitle = 'Thumbnails';
$message = '';
$messageType = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['regenerate_all'])) {
        $result = Media::regenerateAllThumbnails();
        $message = "Regenerated: {$result['success']} successful, {$result['failed']} failed";
        $messageType = $result['failed'] > 0 ? 'warning' : 'success';
    } elseif (isset($_POST['delete_all'])) {
        $result = Media::deleteAllThumbnails();
        $message = "Deleted {$result['deleted']} thumbnails" . ($result['errors'] > 0 ? " ({$result['errors']} errors)" : "");
        $messageType = $result['errors'] > 0 ? 'warning' : 'success';
    } elseif (isset($_POST['regenerate_id'])) {
        $id = (int)$_POST['regenerate_id'];
        $result = Media::regenerateThumbnails($id);
        if ($result['success']) {
            $message = 'Thumbnails regenerated successfully';
            $messageType = 'success';
        } else {
            $message = $result['error'] ?? 'Failed to regenerate thumbnails';
            $messageType = 'error';
        }
    }
}

// Get diagnostics and status
$diagnostics = Media::getThumbnailDiagnostics();
$thumbnailsStatus = Media::getAllThumbnailsStatus();
$sizes = Media::getThumbnailSizes();
$sizeNames = array_keys($sizes);

// Calculate stats
$totalImages = count($thumbnailsStatus);
$totalThumbnails = 0;
$missingThumbnails = 0;
$totalThumbSize = 0;

foreach ($thumbnailsStatus as $item) {
    foreach ($item['thumbnails'] as $thumb) {
        if ($thumb['exists']) {
            $totalThumbnails++;
            $totalThumbSize += $thumb['size'] ?? 0;
        } else {
            $missingThumbnails++;
        }
    }
}

// Additional system info
$phpMemoryLimit = ini_get('memory_limit');
$maxExecutionTime = ini_get('max_execution_time');
$uploadMaxFilesize = ini_get('upload_max_filesize');
$postMaxSize = ini_get('post_max_size');
$gdVersion = '';
if (function_exists('gd_info')) {
    $gdInfo = gd_info();
    $gdVersion = $gdInfo['GD Version'] ?? 'Unknown';
}

include __DIR__ . '/includes/header.php';
?>

<style>
.thumb-page { max-width: 1200px; margin: 0 auto; }

.thumb-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 2rem;
}

.thumb-header-left h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 0.375rem 0;
}

.thumb-header-left p {
    color: #64748b;
    margin: 0;
}

.btn-action {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    border: none;
    border-radius: 12px;
    font-family: inherit;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-action.btn-primary {
    background: linear-gradient(135deg, var(--forge-primary, #6366f1) 0%, var(--forge-secondary, #8b5cf6) 100%);
    color: #fff;
    box-shadow: 0 4px 15px rgba(99, 102, 241, 0.35);
}

.btn-action.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(99, 102, 241, 0.45);
}

.btn-action.btn-danger {
    background: #fff;
    color: #dc2626;
    border: 2px solid #fecaca;
}

.btn-action.btn-danger:hover {
    background: #fef2f2;
    border-color: #dc2626;
    transform: translateY(-2px);
}

/* Stats Grid */
.stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.25rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    flex-shrink: 0;
}

.stat-info { flex: 1; }

.stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.2;
}

.stat-label {
    font-size: 0.8125rem;
    color: #64748b;
    margin-top: 0.25rem;
}

/* Diagnostic Card */
.diag-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    margin-bottom: 2rem;
    overflow: hidden;
}

.diag-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem 1.5rem;
    background: linear-gradient(135deg, #f8fafc 0%, #fff 100%);
    border-bottom: 1px solid #e2e8f0;
}

.diag-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(139, 92, 246, 0.15));
    color: var(--forge-primary, #6366f1);
    display: flex;
    align-items: center;
    justify-content: center;
}

.diag-title {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.diag-body {
    padding: 1.5rem;
}

.diag-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 1.25rem;
}

.diag-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.diag-item-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.diag-item-value {
    font-size: 0.9375rem;
    font-weight: 500;
    color: #1e293b;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.8125rem;
    font-weight: 500;
}

.status-badge.success { background: #dcfce7; color: #166534; }
.status-badge.danger { background: #fee2e2; color: #991b1b; }
.status-badge.warning { background: #fef3c7; color: #92400e; }
.status-badge.neutral { background: #f1f5f9; color: #475569; }

.sizes-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 1.25rem;
    padding-top: 1.25rem;
    border-top: 1px solid #e2e8f0;
}

.size-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.75rem;
    background: #f1f5f9;
    border-radius: 8px;
    font-size: 0.8125rem;
    color: #475569;
}

.size-tag strong { color: #1e293b; }

/* Images Table */
.images-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    overflow: hidden;
}

.images-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    background: linear-gradient(135deg, #f8fafc 0%, #fff 100%);
    border-bottom: 1px solid #e2e8f0;
}

.images-title {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.images-count {
    font-size: 0.8125rem;
    color: #64748b;
}

.images-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
    padding: 1.5rem;
}

.image-item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s ease;
}

.image-item:hover {
    border-color: var(--forge-primary, #6366f1);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.15);
}

.image-preview {
    height: 140px;
    background: #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.image-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.image-info {
    padding: 1rem;
}

.image-name {
    font-size: 0.875rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 0.25rem 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.image-meta {
    font-size: 0.75rem;
    color: #64748b;
    margin-bottom: 0.75rem;
}

.thumb-status {
    display: flex;
    flex-wrap: wrap;
    gap: 0.375rem;
    margin-bottom: 0.75rem;
}

.thumb-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 6px;
    font-size: 0.6875rem;
    font-weight: 600;
}

.thumb-badge.ok { background: #dcfce7; color: #166534; }
.thumb-badge.missing { background: #fee2e2; color: #991b1b; }

.image-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-action {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    padding: 0.5rem;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-family: inherit;
    font-size: 0.75rem;
    font-weight: 500;
    color: #475569;
    cursor: pointer;
    transition: all 0.15s ease;
    text-decoration: none;
}

.btn-action:hover {
    border-color: var(--forge-primary, #6366f1);
    color: var(--forge-primary, #6366f1);
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: #64748b;
}

.empty-state svg {
    width: 64px;
    height: 64px;
    margin-bottom: 1rem;
    opacity: 0.5;
}

@media (max-width: 1024px) {
    .stats-row { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 640px) {
    .stats-row { grid-template-columns: 1fr; }
    .thumb-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
}

/* Detail Panel */
.panel-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.3);
    backdrop-filter: blur(4px);
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    z-index: 1000;
}

.panel-overlay.open {
    opacity: 1;
    visibility: visible;
}

.thumb-detail-panel {
    position: fixed;
    top: 0;
    right: 0;
    width: 480px;
    max-width: 95vw;
    height: 100vh;
    background: #fff;
    box-shadow: -8px 0 30px rgba(0,0,0,0.15);
    transform: translateX(100%);
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 1001;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.thumb-detail-panel.open {
    transform: translateX(0);
}

.panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    background: linear-gradient(135deg, #f8fafc 0%, #fff 100%);
}

.panel-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 300px;
}

.panel-close {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    background: #f1f5f9;
    border-radius: 10px;
    color: #64748b;
    cursor: pointer;
    transition: all 0.15s ease;
    flex-shrink: 0;
}

.panel-close:hover {
    background: #e2e8f0;
    color: #1e293b;
}

.panel-content {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
}

.panel-preview {
    width: 100%;
    aspect-ratio: 16/10;
    background: #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.panel-preview img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.panel-meta {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.meta-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.meta-label {
    font-size: 0.6875rem;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.meta-value {
    font-size: 0.875rem;
    font-weight: 500;
    color: #1e293b;
}

.thumb-sizes-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.thumb-sizes-title {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.thumb-size-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.thumb-size-item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 1rem;
    transition: all 0.15s ease;
}

.thumb-size-item:hover {
    border-color: #cbd5e1;
}

.thumb-size-item.missing {
    background: #fef2f2;
    border-color: #fecaca;
}

.thumb-size-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.thumb-size-name {
    font-size: 0.875rem;
    font-weight: 600;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.thumb-size-dims {
    font-size: 0.75rem;
    color: #64748b;
    font-weight: 500;
}

.thumb-size-status {
    font-size: 0.6875rem;
    font-weight: 600;
    padding: 0.25rem 0.625rem;
    border-radius: 100px;
}

.thumb-size-status.ok {
    background: #dcfce7;
    color: #166534;
}

.thumb-size-status.missing {
    background: #fee2e2;
    color: #991b1b;
}

.thumb-url-wrapper {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.thumb-url-input {
    flex: 1;
    padding: 0.5rem 0.75rem;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-family: 'Monaco', 'Menlo', monospace;
    font-size: 0.6875rem;
    color: #475569;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.btn-copy {
    padding: 0.5rem 0.75rem;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-family: inherit;
    font-size: 0.6875rem;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    transition: all 0.15s ease;
    display: flex;
    align-items: center;
    gap: 0.375rem;
    white-space: nowrap;
}

.btn-copy:hover {
    background: var(--forge-primary, #6366f1);
    border-color: var(--forge-primary, #6366f1);
    color: #fff;
}

.btn-copy.copied {
    background: #10b981;
    border-color: #10b981;
    color: #fff;
}

.image-item.clickable {
    cursor: pointer;
}

.image-item.clickable:hover .image-preview {
    opacity: 0.85;
}

.image-item.selected {
    border-color: var(--forge-primary, #6366f1);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
}
</style>

<div class="thumb-page">
    <div class="thumb-header">
        <div class="thumb-header-left">
            <h1>Thumbnails</h1>
            <p>Manage image thumbnails and regenerate sizes</p>
        </div>
        <div class="thumb-actions" style="display: flex; gap: 0.75rem;">
            <form method="post" style="display: inline;">
                <button type="submit" name="delete_all" class="btn-action btn-danger"
                        onclick="return confirm('Delete ALL thumbnails? You will need to regenerate them.')">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                    Delete All Thumbnails
                </button>
            </form>
            <form method="post" style="display: inline;">
                <button type="submit" name="regenerate_all" class="btn-action btn-primary"
                        onclick="return confirm('Regenerate all thumbnails? This may take a while.')">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 4 23 10 17 10"></polyline>
                        <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                    </svg>
                    Regenerate All
                </button>
            </form>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>" style="margin-bottom: 1.5rem;"><?= esc($message) ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, var(--forge-primary, #6366f1), var(--forge-secondary, #8b5cf6));">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                    <polyline points="21 15 16 10 5 21"></polyline>
                </svg>
            </div>
            <div class="stat-info">
                <div class="stat-value"><?= $totalImages ?></div>
                <div class="stat-label">Total Images</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
            </div>
            <div class="stat-info">
                <div class="stat-value"><?= $totalThumbnails ?></div>
                <div class="stat-label">Generated</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            </div>
            <div class="stat-info">
                <div class="stat-value"><?= $missingThumbnails ?></div>
                <div class="stat-label">Missing</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="3" y1="9" x2="21" y2="9"></line>
                    <line x1="9" y1="21" x2="9" y2="9"></line>
                </svg>
            </div>
            <div class="stat-info">
                <div class="stat-value"><?= count($sizes) ?></div>
                <div class="stat-label">Sizes</div>
            </div>
        </div>
    </div>

    <!-- Diagnostics -->
    <div class="diag-card">
        <div class="diag-header">
            <div class="diag-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
            </div>
            <h3 class="diag-title">System Status</h3>
        </div>
        <div class="diag-body">
            <div class="diag-grid" style="margin-bottom: 1.5rem;">
                <div class="diag-item">
                    <span class="diag-item-label">GD Library</span>
                    <span class="status-badge <?= $diagnostics['gd_loaded'] ? 'success' : 'danger' ?>">
                        <?= $diagnostics['gd_loaded'] ? '✓ Loaded' : '✗ Missing' ?>
                    </span>
                </div>
                <div class="diag-item">
                    <span class="diag-item-label">GD Version</span>
                    <span class="diag-item-value"><?= esc($gdVersion ?: 'N/A') ?></span>
                </div>
                <div class="diag-item">
                    <span class="diag-item-label">Uploads Writable</span>
                    <span class="status-badge <?= $diagnostics['uploads_writable'] ? 'success' : 'danger' ?>">
                        <?= $diagnostics['uploads_writable'] ? '✓ Yes' : '✗ No' ?>
                    </span>
                </div>
                <div class="diag-item">
                    <span class="diag-item-label">PHP Version</span>
                    <span class="diag-item-value"><?= PHP_VERSION ?></span>
                </div>
                <div class="diag-item">
                    <span class="diag-item-label">Memory Limit</span>
                    <span class="diag-item-value"><?= esc($phpMemoryLimit) ?></span>
                </div>
                <div class="diag-item">
                    <span class="diag-item-label">Max Execution</span>
                    <span class="diag-item-value"><?= esc($maxExecutionTime) ?>s</span>
                </div>
                <div class="diag-item">
                    <span class="diag-item-label">Upload Max Size</span>
                    <span class="diag-item-value"><?= esc($uploadMaxFilesize) ?></span>
                </div>
                <div class="diag-item">
                    <span class="diag-item-label">Uploads Path</span>
                    <span class="diag-item-value" style="font-size: 0.75rem; word-break: break-all;"><?= esc(UPLOADS_PATH) ?></span>
                </div>
            </div>
            
            <h4 style="font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 0.75rem;">Format Support</h4>
            <div class="diag-grid" style="margin-bottom: 1.5rem;">
                <div class="diag-item">
                    <span class="diag-item-label">JPEG</span>
                    <span class="status-badge <?= !empty($diagnostics['supported_formats']['jpeg']) ? 'success' : 'danger' ?>">
                        <?= !empty($diagnostics['supported_formats']['jpeg']) ? '✓ Supported' : '✗ No' ?>
                    </span>
                </div>
                <div class="diag-item">
                    <span class="diag-item-label">PNG</span>
                    <span class="status-badge <?= !empty($diagnostics['supported_formats']['png']) ? 'success' : 'danger' ?>">
                        <?= !empty($diagnostics['supported_formats']['png']) ? '✓ Supported' : '✗ No' ?>
                    </span>
                </div>
                <div class="diag-item">
                    <span class="diag-item-label">GIF</span>
                    <span class="status-badge <?= !empty($diagnostics['supported_formats']['gif']) ? 'success' : 'warning' ?>">
                        <?= !empty($diagnostics['supported_formats']['gif']) ? '✓ Supported' : '○ No' ?>
                    </span>
                </div>
                <div class="diag-item">
                    <span class="diag-item-label">WebP</span>
                    <span class="status-badge <?= !empty($diagnostics['supported_formats']['webp']) ? 'success' : 'warning' ?>">
                        <?= !empty($diagnostics['supported_formats']['webp']) ? '✓ Supported' : '○ No' ?>
                    </span>
                </div>
            </div>
            
            <h4 style="font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 0.75rem;">Thumbnail Sizes</h4>
            <div class="sizes-list">
                <?php foreach ($sizes as $name => $config): ?>
                <span class="size-tag">
                    <strong><?= esc($name) ?></strong>
                    <?= $config[0] ?>×<?= $config[1] ?><?= !empty($config[2]) ? ' crop' : '' ?>
                </span>
                <?php endforeach; ?>
            </div>
            
            <?php if ($totalThumbSize > 0): ?>
            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e2e8f0;">
                <span class="diag-item-label">Total Thumbnail Storage</span>
                <span class="diag-item-value" style="margin-left: 0.5rem;"><?= formatFileSize($totalThumbSize) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Images Grid -->
    <div class="images-card">
        <div class="images-header">
            <h3 class="images-title">All Images</h3>
            <span class="images-count"><?= $totalImages ?> images</span>
        </div>

        <?php if (empty($thumbnailsStatus)): ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                <polyline points="21 15 16 10 5 21"></polyline>
            </svg>
            <p>No images in the media library yet.</p>
        </div>
        <?php else: ?>
        <div class="images-grid">
            <?php foreach ($thumbnailsStatus as $item): ?>
            <div class="image-item clickable" 
                 data-id="<?= $item['id'] ?>"
                 data-filename="<?= esc($item['filename']) ?>"
                 data-url="<?= esc($item['original_url']) ?>"
                 data-dimensions="<?= esc($item['dimensions']) ?>"
                 data-thumbs='<?= json_encode($item['thumbnails']) ?>'
                 onclick="openDetailPanel(this)">
                <div class="image-preview">
                    <img src="<?= esc($item['original_url']) ?>" alt="<?= esc($item['filename']) ?>">
                </div>
                <div class="image-info">
                    <h4 class="image-name"><?= esc($item['filename']) ?></h4>
                    <div class="image-meta"><?= esc($item['dimensions']) ?></div>
                    <div class="thumb-status">
                        <?php foreach ($sizeNames as $sizeName): ?>
                        <?php $thumb = $item['thumbnails'][$sizeName] ?? null; ?>
                        <span class="thumb-badge <?= ($thumb && $thumb['exists']) ? 'ok' : 'missing' ?>" 
                              title="<?= esc($sizeName) ?>: <?= ($thumb && $thumb['exists']) ? 'OK' : 'Missing' ?>">
                            <?= strtoupper(substr($sizeName, 0, 2)) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <div class="image-actions">
                        <form method="post" style="flex: 1; display: contents;">
                            <input type="hidden" name="regenerate_id" value="<?= $item['id'] ?>">
                            <button type="submit" class="btn-action">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="23 4 23 10 17 10"></polyline>
                                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                                </svg>
                                Regen
                            </button>
                        </form>
                        <a href="media.php?view=<?= $item['id'] ?>" class="btn-action">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            View
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Detail Panel -->
<div class="panel-overlay" onclick="closeDetailPanel()"></div>
<div class="thumb-detail-panel" id="detailPanel">
    <div class="panel-header">
        <h3 class="panel-title" id="panelTitle">Image Details</h3>
        <button type="button" class="panel-close" onclick="closeDetailPanel()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>
    <div class="panel-content">
        <div class="panel-preview">
            <img id="panelImage" src="" alt="">
        </div>
        
        <div class="panel-meta">
            <div class="meta-item">
                <span class="meta-label">Filename</span>
                <span class="meta-value" id="panelFilename">-</span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Dimensions</span>
                <span class="meta-value" id="panelDimensions">-</span>
            </div>
        </div>
        
        <div class="thumb-sizes-section">
            <div class="thumb-sizes-header">
                <h4 class="thumb-sizes-title">Thumbnail Sizes</h4>
            </div>
            <div class="thumb-size-list" id="thumbSizeList">
                <!-- Populated by JavaScript -->
            </div>
        </div>
    </div>
</div>

<script>
const detailPanel = document.getElementById('detailPanel');
const overlay = document.querySelector('.panel-overlay');
let selectedImageItem = null;

// Thumbnail size definitions from PHP
const thumbnailSizes = <?= json_encode($sizes) ?>;

function openDetailPanel(element) {
    // Deselect previous
    if (selectedImageItem) {
        selectedImageItem.classList.remove('selected');
    }
    
    // Select current
    element.classList.add('selected');
    selectedImageItem = element;
    
    const data = element.dataset;
    
    // Populate panel
    document.getElementById('panelTitle').textContent = data.filename;
    document.getElementById('panelImage').src = data.url;
    document.getElementById('panelFilename').textContent = data.filename;
    document.getElementById('panelDimensions').textContent = data.dimensions;
    
    // Parse thumbnails data
    const thumbs = JSON.parse(data.thumbs);
    
    // Build thumbnail sizes list
    const listHtml = [];
    
    // Add original first
    listHtml.push(`
        <div class="thumb-size-item">
            <div class="thumb-size-header">
                <span class="thumb-size-name">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                        <polyline points="21 15 16 10 5 21"></polyline>
                    </svg>
                    Original
                </span>
                <span class="thumb-size-dims">${data.dimensions}</span>
            </div>
            <div class="thumb-url-wrapper">
                <input type="text" class="thumb-url-input" value="${data.url}" readonly>
                <button type="button" class="btn-copy" onclick="copyUrl(this, '${data.url}')">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                    </svg>
                    Copy
                </button>
            </div>
        </div>
    `);
    
    // Add each thumbnail size
    for (const [name, config] of Object.entries(thumbnailSizes)) {
        const thumb = thumbs[name];
        const exists = thumb && thumb.exists;
        const dims = config[0] + '×' + config[1] + (config[2] ? ' crop' : '');
        
        if (exists && thumb.url) {
            listHtml.push(`
                <div class="thumb-size-item">
                    <div class="thumb-size-header">
                        <span class="thumb-size-name">${escapeHtml(name)}</span>
                        <span class="thumb-size-status ok">✓ Generated</span>
                    </div>
                    <div class="thumb-size-dims">${dims}${thumb.actual_dims ? ' → ' + thumb.actual_dims : ''}</div>
                    <div class="thumb-url-wrapper">
                        <input type="text" class="thumb-url-input" value="${thumb.url}" readonly>
                        <button type="button" class="btn-copy" onclick="copyUrl(this, '${thumb.url}')">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                            </svg>
                            Copy
                        </button>
                    </div>
                </div>
            `);
        } else {
            listHtml.push(`
                <div class="thumb-size-item missing">
                    <div class="thumb-size-header">
                        <span class="thumb-size-name">${escapeHtml(name)}</span>
                        <span class="thumb-size-status missing">Missing</span>
                    </div>
                    <div class="thumb-size-dims">${dims}</div>
                </div>
            `);
        }
    }
    
    document.getElementById('thumbSizeList').innerHTML = listHtml.join('');
    
    // Open panel
    detailPanel.classList.add('open');
    overlay.classList.add('open');
}

function closeDetailPanel() {
    detailPanel.classList.remove('open');
    overlay.classList.remove('open');
    
    if (selectedImageItem) {
        selectedImageItem.classList.remove('selected');
        selectedImageItem = null;
    }
}

function copyUrl(btn, url) {
    navigator.clipboard.writeText(url).then(() => {
        const originalText = btn.innerHTML;
        btn.classList.add('copied');
        btn.innerHTML = `
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
            Copied!
        `;
        
        setTimeout(() => {
            btn.classList.remove('copied');
            btn.innerHTML = originalText;
        }, 2000);
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Keyboard shortcut to close panel
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && detailPanel.classList.contains('open')) {
        closeDetailPanel();
    }
});

// Stop propagation on form buttons to prevent opening panel
document.querySelectorAll('.image-actions button, .image-actions a').forEach(el => {
    el.addEventListener('click', (e) => e.stopPropagation());
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
