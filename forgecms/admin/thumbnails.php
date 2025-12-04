<?php
/**
 * Thumbnail Manager - Forge CMS v1.0.6
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

foreach ($thumbnailsStatus as $item) {
    foreach ($item['thumbnails'] as $thumb) {
        $thumb['exists'] ? $totalThumbnails++ : $missingThumbnails++;
    }
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

.btn-regen {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, var(--forge-primary, #6366f1) 0%, var(--forge-secondary, #8b5cf6) 100%);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 0.9375rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 15px rgba(99, 102, 241, 0.35);
}

.btn-regen:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(99, 102, 241, 0.45);
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
</style>

<div class="thumb-page">
    <div class="thumb-header">
        <div class="thumb-header-left">
            <h1>Thumbnails</h1>
            <p>Manage image thumbnails and regenerate sizes</p>
        </div>
        <form method="post">
            <button type="submit" name="regenerate_all" class="btn-regen"
                    onclick="return confirm('Regenerate all thumbnails? This may take a while.')">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23 4 23 10 17 10"></polyline>
                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                </svg>
                Regenerate All
            </button>
        </form>
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
            <div class="diag-grid">
                <div class="diag-item">
                    <span class="diag-item-label">GD Library</span>
                    <span class="status-badge <?= $diagnostics['gd_loaded'] ? 'success' : 'danger' ?>">
                        <?= $diagnostics['gd_loaded'] ? '✓ Loaded' : '✗ Missing' ?>
                    </span>
                </div>
                <div class="diag-item">
                    <span class="diag-item-label">Uploads Writable</span>
                    <span class="status-badge <?= $diagnostics['uploads_writable'] ? 'success' : 'danger' ?>">
                        <?= $diagnostics['uploads_writable'] ? '✓ Yes' : '✗ No' ?>
                    </span>
                </div>
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
                    <span class="diag-item-label">WebP</span>
                    <span class="status-badge <?= !empty($diagnostics['supported_formats']['webp']) ? 'success' : 'warning' ?>">
                        <?= !empty($diagnostics['supported_formats']['webp']) ? '✓ Supported' : '○ No' ?>
                    </span>
                </div>
            </div>

            <div class="sizes-list">
                <?php foreach ($sizes as $name => $config): ?>
                <span class="size-tag">
                    <strong><?= esc($name) ?></strong>
                    <?= $config[0] ?>×<?= $config[1] ?><?= !empty($config[2]) ? ' crop' : '' ?>
                </span>
                <?php endforeach; ?>
            </div>
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
            <div class="image-item">
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

<?php include __DIR__ . '/includes/footer.php'; ?>
