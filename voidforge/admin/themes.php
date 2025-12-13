<?php
/**
 * Themes Management - VoidForge CMS
 */

define('CMS_ROOT', dirname(__DIR__));
require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/user.php';
require_once CMS_ROOT . '/includes/post.php';
require_once CMS_ROOT . '/includes/plugin.php';
require_once CMS_ROOT . '/includes/theme.php';

Post::init();
Plugin::init();
Theme::init();

User::startSession();
User::requireRole('admin');

$pageTitle = 'Themes';
$currentPage = 'themes';

// Handle actions
if (isset($_GET['action']) && isset($_GET['theme']) && verifyCsrf($_GET['csrf'] ?? '')) {
    $themeSlug = $_GET['theme'];
    
    if ($_GET['action'] === 'activate') {
        $result = Theme::activate($themeSlug);
        if ($result['success']) {
            setFlash('success', 'Theme activated successfully.');
        } else {
            setFlash('error', $result['error']);
        }
    } elseif ($_GET['action'] === 'delete') {
        $result = Theme::delete($themeSlug);
        if ($result['success']) {
            setFlash('success', 'Theme deleted successfully.');
        } else {
            setFlash('error', $result['error']);
        }
    }
    
    redirect(ADMIN_URL . '/themes.php');
}

// Handle theme upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['theme_zip']) && verifyCsrf()) {
    $file = $_FILES['theme_zip'];
    
    if ($file['error'] === UPLOAD_ERR_OK && pathinfo($file['name'], PATHINFO_EXTENSION) === 'zip') {
        $tempDir = sys_get_temp_dir() . '/voidforge_theme_' . uniqid();
        mkdir($tempDir);
        
        $zip = new ZipArchive();
        if ($zip->open($file['tmp_name']) === true) {
            $zip->extractTo($tempDir);
            $zip->close();
            
            $items = array_diff(scandir($tempDir), ['.', '..']);
            $themeFolder = reset($items);
            $themePath = $tempDir . '/' . $themeFolder;
            
            if (is_dir($themePath) && (file_exists($themePath . '/index.php') || file_exists($themePath . '/theme.json'))) {
                $destPath = CMS_ROOT . '/themes/' . $themeFolder;
                
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
                
                rename($themePath, $destPath);
                setFlash('success', 'Theme uploaded successfully.');
            } else {
                setFlash('error', 'Invalid theme structure.');
            }
            
            array_map('unlink', glob("$tempDir/*"));
            @rmdir($tempDir);
        } else {
            setFlash('error', 'Failed to extract ZIP file.');
        }
    } else {
        setFlash('error', 'Please upload a valid ZIP file.');
    }
    
    redirect(ADMIN_URL . '/themes.php');
}

$themes = Theme::getThemes();
$activeTheme = Theme::getActive();
$activeThemeData = $themes[$activeTheme] ?? null;

$otherThemes = array_filter($themes, function($slug) use ($activeTheme) {
    return $slug !== $activeTheme;
}, ARRAY_FILTER_USE_KEY);

include ADMIN_PATH . '/includes/header.php';
?>

<style>
/* Themes Page */
.themes-page { padding: 0; }

/* Header */
.themes-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}
.themes-header-left { display: flex; align-items: center; gap: 1rem; }
.themes-header-left h1 { font-size: 1.75rem; font-weight: 700; margin: 0; }
.themes-count {
    padding: 0.25rem 0.75rem;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 100px;
    font-size: 0.8125rem;
    color: var(--text-muted);
}
.themes-header-right { display: flex; gap: 0.5rem; align-items: center; }

/* Section Label */
.section-label {
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
    margin-bottom: 0.75rem;
}

/* Active Theme */
.active-theme-section { margin-bottom: 2rem; }
.active-theme-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-lg);
    overflow: hidden;
}
.active-theme-inner {
    display: flex;
    gap: 1.5rem;
    padding: 1.5rem;
}
.active-theme-preview {
    width: 200px;
    height: 140px;
    flex-shrink: 0;
    background: var(--bg-card-header);
    border-radius: var(--border-radius);
    overflow: hidden;
    position: relative;
}
.active-theme-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.active-theme-preview .no-preview {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: var(--text-muted);
    font-size: 0.75rem;
}
.active-theme-preview .no-preview svg {
    width: 32px;
    height: 32px;
    margin-bottom: 0.5rem;
    opacity: 0.4;
}
.active-badge {
    position: absolute;
    top: 0.5rem;
    left: 0.5rem;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    background: var(--forge-success);
    color: white;
    font-size: 0.625rem;
    font-weight: 600;
    text-transform: uppercase;
    border-radius: 4px;
}
.active-badge .pulse {
    width: 5px;
    height: 5px;
    background: white;
    border-radius: 50%;
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}
.active-theme-info { flex: 1; min-width: 0; }
.active-theme-name {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0 0 0.25rem 0;
    color: var(--text-primary);
}
.active-theme-meta {
    font-size: 0.8125rem;
    color: var(--text-muted);
    margin-bottom: 0.75rem;
}
.active-theme-meta a { color: var(--forge-primary); text-decoration: none; }
.active-theme-description {
    font-size: 0.875rem;
    color: var(--text-secondary);
    line-height: 1.5;
    margin-bottom: 1rem;
}
.active-theme-actions { display: flex; gap: 0.5rem; }

/* Themes Grid */
.available-themes-section { margin-bottom: 2rem; }
.themes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 1rem;
}

/* Theme Card */
.theme-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-lg);
    overflow: hidden;
    transition: all 0.15s;
}
.theme-card:hover { border-color: var(--forge-primary); }

.theme-card-image {
    position: relative;
    aspect-ratio: 16/10;
    background: var(--bg-card-header);
}
.theme-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.theme-card-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: var(--text-muted);
    font-size: 0.75rem;
}
.theme-card-placeholder svg {
    width: 32px;
    height: 32px;
    margin-bottom: 0.5rem;
    opacity: 0.4;
}

.theme-card-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    opacity: 0;
    transition: opacity 0.15s;
}
.theme-card:hover .theme-card-overlay { opacity: 1; }

.btn-overlay {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background: white;
    color: var(--text-primary);
    border-radius: 8px;
    text-decoration: none;
    cursor: pointer;
    border: none;
    transition: all 0.15s;
}
.btn-overlay:hover { transform: scale(1.05); }
.btn-overlay.danger { background: var(--forge-danger); color: white; }
.btn-overlay svg { width: 16px; height: 16px; }

.theme-card-body { padding: 1rem; }
.theme-card-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.5rem;
    margin-bottom: 0.375rem;
}
.theme-card-name {
    font-size: 0.9375rem;
    font-weight: 600;
    margin: 0;
    color: var(--text-primary);
}
.theme-card-badge {
    font-size: 0.5625rem;
    font-weight: 600;
    padding: 0.125rem 0.375rem;
    border-radius: 3px;
    text-transform: uppercase;
    background: rgba(99, 102, 241, 0.1);
    color: var(--forge-primary);
}
.theme-card-meta {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin-bottom: 0.5rem;
}
.theme-card-meta a { color: var(--forge-primary); text-decoration: none; }
.theme-card-description {
    font-size: 0.8125rem;
    color: var(--text-secondary);
    line-height: 1.5;
    margin-bottom: 0.75rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.theme-card-footer { display: flex; gap: 0.5rem; }
.btn-card {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    font-size: 0.8125rem;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    border: none;
    transition: all 0.15s;
}
.btn-card-primary {
    background: linear-gradient(135deg, var(--forge-primary), var(--forge-secondary));
    color: white;
}
.btn-card-primary:hover { opacity: 0.9; }
.btn-card-secondary {
    background: var(--bg-card-header);
    color: var(--text-secondary);
}
.btn-card-secondary:hover { background: var(--border-color); }
.btn-card svg { width: 14px; height: 14px; }

/* Empty */
.themes-empty {
    text-align: center;
    padding: 3rem 2rem;
    background: var(--bg-card);
    border: 2px dashed var(--border-color);
    border-radius: var(--border-radius-lg);
}
.themes-empty svg {
    width: 48px;
    height: 48px;
    color: var(--text-muted);
    margin-bottom: 1rem;
    opacity: 0.4;
}
.themes-empty h2 {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0 0 0.5rem 0;
}
.themes-empty p {
    color: var(--text-muted);
    margin: 0 0 1.5rem 0;
    font-size: 0.875rem;
}

/* Modal */
.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}
.modal-overlay.open { display: flex; }
.modal {
    background: var(--bg-card);
    border-radius: var(--border-radius-lg);
    width: 100%;
    max-width: 420px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    overflow: hidden;
}
.modal-header {
    padding: 1rem 1.25rem;
    background: var(--bg-card-header);
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.modal-header h2 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
}
.modal-close {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: none;
    border-radius: 6px;
    color: var(--text-muted);
    cursor: pointer;
}
.modal-close:hover { background: var(--border-color); color: var(--text-primary); }
.modal-body { padding: 1.25rem; }

.upload-zone {
    border: 2px dashed var(--border-color);
    border-radius: var(--border-radius);
    padding: 2rem 1.5rem;
    text-align: center;
    cursor: pointer;
    background: var(--bg-card-header);
}
.upload-zone:hover, .upload-zone.dragover {
    border-color: var(--forge-primary);
    background: rgba(99, 102, 241, 0.05);
}
.upload-zone .upload-icon {
    width: 48px;
    height: 48px;
    margin: 0 auto 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(99, 102, 241, 0.1);
    border-radius: 12px;
    color: var(--forge-primary);
}
.upload-zone h3 { margin: 0 0 0.25rem 0; font-size: 0.9375rem; font-weight: 600; }
.upload-zone p { margin: 0; font-size: 0.8125rem; color: var(--text-muted); }
.upload-zone input { display: none; }

.delete-modal-body { text-align: center; }
.delete-icon {
    width: 48px;
    height: 48px;
    margin: 0 auto 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(239, 68, 68, 0.1);
    color: var(--forge-danger);
    border-radius: 12px;
}
.delete-modal-body h3 { font-size: 1rem; font-weight: 600; margin: 0 0 0.5rem 0; }
.delete-modal-body p { color: var(--text-muted); margin: 0 0 1.5rem 0; font-size: 0.875rem; }
.delete-actions { display: flex; gap: 0.5rem; }
.btn-modal {
    flex: 1;
    padding: 0.625rem 1rem;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.875rem;
    text-decoration: none;
    text-align: center;
    cursor: pointer;
    border: none;
}
.btn-modal-cancel { background: var(--bg-card-header); color: var(--text-secondary); }
.btn-modal-cancel:hover { background: var(--border-color); }
.btn-modal-danger { background: var(--forge-danger); color: white; }
.btn-modal-danger:hover { opacity: 0.9; }

@media (max-width: 768px) {
    .themes-header { flex-direction: column; align-items: flex-start; }
    .active-theme-inner { flex-direction: column; }
    .active-theme-preview { width: 100%; height: 160px; }
    .themes-grid { grid-template-columns: 1fr; }
}
</style>

<div class="themes-page">
    <div class="themes-header">
        <div class="themes-header-left">
            <h1>Themes</h1>
            <span class="themes-count"><?= count($themes) ?> installed</span>
        </div>
        <div class="themes-header-right">
            <button class="btn btn-primary" onclick="document.getElementById('uploadModal').classList.add('open')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="17 8 12 3 7 8"/>
                    <line x1="12" y1="3" x2="12" y2="15"/>
                </svg>
                Upload Theme
            </button>
            <a href="<?= SITE_URL ?>" target="_blank" class="btn btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                    <polyline points="15 3 21 3 21 9"/>
                    <line x1="10" y1="14" x2="21" y2="3"/>
                </svg>
                View Site
            </a>
        </div>
    </div>

    <?php if ($activeThemeData): 
        $screenshot = Theme::getScreenshot($activeTheme);
    ?>
    <div class="active-theme-section">
        <div class="section-label">Active Theme</div>
        <div class="active-theme-card">
            <div class="active-theme-inner">
                <div class="active-theme-preview">
                    <span class="active-badge"><span class="pulse"></span> Live</span>
                    <?php if ($screenshot): ?>
                    <img src="<?= esc($screenshot) ?>" alt="<?= esc($activeThemeData['name']) ?>">
                    <?php else: ?>
                    <div class="no-preview">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                            <circle cx="8.5" cy="8.5" r="1.5"/>
                            <path d="M21 15l-5-5L5 21"/>
                        </svg>
                        <div>No preview</div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="active-theme-info">
                    <h3 class="active-theme-name"><?= esc($activeThemeData['name']) ?></h3>
                    <div class="active-theme-meta">
                        Version <?= esc($activeThemeData['version'] ?? '1.0.0') ?>
                        <?php if ($activeThemeData['author']): ?>
                        · by <?php if ($activeThemeData['author_uri']): ?><a href="<?= esc($activeThemeData['author_uri']) ?>" target="_blank"><?= esc($activeThemeData['author']) ?></a><?php else: ?><?= esc($activeThemeData['author']) ?><?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <p class="active-theme-description"><?= esc($activeThemeData['description'] ?? 'A theme for your VoidForge website.') ?></p>
                    <div class="active-theme-actions">
                        <a href="<?= ADMIN_URL ?>/theme-settings.php" class="btn btn-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="3"/>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                            </svg>
                            Customize
                        </a>
                        <a href="<?= SITE_URL ?>" target="_blank" class="btn btn-secondary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                <polyline points="15 3 21 3 21 9"/>
                                <line x1="10" y1="14" x2="21" y2="3"/>
                            </svg>
                            View Site
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($otherThemes)): ?>
    <div class="available-themes-section">
        <div class="section-label">Available Themes</div>
        <div class="themes-grid">
            <?php foreach ($otherThemes as $slug => $theme): 
                $screenshot = Theme::getScreenshot($slug);
                $isBundled = in_array($slug, ['default', 'flavor']);
            ?>
            <div class="theme-card">
                <div class="theme-card-image">
                    <?php if ($screenshot): ?>
                    <img src="<?= esc($screenshot) ?>" alt="<?= esc($theme['name']) ?>">
                    <?php else: ?>
                    <div class="theme-card-placeholder">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                            <circle cx="8.5" cy="8.5" r="1.5"/>
                            <path d="M21 15l-5-5L5 21"/>
                        </svg>
                        <div>No preview</div>
                    </div>
                    <?php endif; ?>
                    <div class="theme-card-overlay">
                        <a href="?action=activate&theme=<?= esc($slug) ?>&csrf=<?= csrfToken() ?>" class="btn-overlay" title="Activate">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        </a>
                        <a href="<?= SITE_URL ?>?preview_theme=<?= esc($slug) ?>" target="_blank" class="btn-overlay" title="Preview">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </a>
                        <?php if (!$isBundled): ?>
                        <button class="btn-overlay danger" onclick="confirmDelete('<?= esc($slug) ?>', '<?= esc($theme['name']) ?>')" title="Delete">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="theme-card-body">
                    <div class="theme-card-header">
                        <h3 class="theme-card-name"><?= esc($theme['name']) ?></h3>
                        <?php if ($isBundled): ?><span class="theme-card-badge">Bundled</span><?php endif; ?>
                    </div>
                    <div class="theme-card-meta">
                        v<?= esc($theme['version'] ?? '1.0.0') ?>
                        <?php if ($theme['author']): ?>· <?php if ($theme['author_uri']): ?><a href="<?= esc($theme['author_uri']) ?>" target="_blank"><?= esc($theme['author']) ?></a><?php else: ?><?= esc($theme['author']) ?><?php endif; ?><?php endif; ?>
                    </div>
                    <?php if ($theme['description']): ?>
                    <p class="theme-card-description"><?= esc($theme['description']) ?></p>
                    <?php endif; ?>
                    <div class="theme-card-footer">
                        <a href="?action=activate&theme=<?= esc($slug) ?>&csrf=<?= csrfToken() ?>" class="btn-card btn-card-primary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                            Activate
                        </a>
                        <a href="<?= SITE_URL ?>?preview_theme=<?= esc($slug) ?>" target="_blank" class="btn-card btn-card-secondary">Preview</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php elseif (empty($themes)): ?>
    <div class="themes-empty">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
        </svg>
        <h2>No Themes Installed</h2>
        <p>Upload a theme ZIP file to get started.</p>
        <button class="btn btn-primary" onclick="document.getElementById('uploadModal').classList.add('open')">Upload Theme</button>
    </div>
    <?php endif; ?>
</div>

<!-- Upload Modal -->
<div class="modal-overlay" id="uploadModal" onclick="if(event.target === this) this.classList.remove('open')">
    <div class="modal">
        <div class="modal-header">
            <h2>Upload Theme</h2>
            <button class="modal-close" onclick="document.getElementById('uploadModal').classList.remove('open')">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <?= csrfField() ?>
                <label class="upload-zone" id="uploadZone">
                    <div class="upload-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="17 8 12 3 7 8"/>
                            <line x1="12" y1="3" x2="12" y2="15"/>
                        </svg>
                    </div>
                    <h3>Drop your theme here</h3>
                    <p>or click to browse for a ZIP file</p>
                    <input type="file" name="theme_zip" accept=".zip" onchange="this.form.submit()">
                </label>
            </form>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal-overlay" id="deleteModal" onclick="if(event.target === this) this.classList.remove('open')">
    <div class="modal">
        <div class="modal-header">
            <h2>Delete Theme</h2>
            <button class="modal-close" onclick="document.getElementById('deleteModal').classList.remove('open')">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="modal-body delete-modal-body">
            <div class="delete-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                </svg>
            </div>
            <h3>Delete "<span id="deleteThemeName"></span>"?</h3>
            <p>This will permanently remove all theme files.</p>
            <div class="delete-actions">
                <button class="btn-modal btn-modal-cancel" onclick="document.getElementById('deleteModal').classList.remove('open')">Cancel</button>
                <a href="#" id="deleteConfirmBtn" class="btn-modal btn-modal-danger">Delete</a>
            </div>
        </div>
    </div>
</div>

<script>
var uploadZone = document.getElementById('uploadZone');
['dragenter', 'dragover'].forEach(function(e) {
    uploadZone.addEventListener(e, function() { uploadZone.classList.add('dragover'); });
});
['dragleave', 'drop'].forEach(function(e) {
    uploadZone.addEventListener(e, function() { uploadZone.classList.remove('dragover'); });
});
uploadZone.addEventListener('drop', function(e) {
    e.preventDefault();
    var file = e.dataTransfer.files[0];
    if (file && file.name.endsWith('.zip')) {
        var input = uploadZone.querySelector('input');
        var dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        document.getElementById('uploadForm').submit();
    }
});
uploadZone.addEventListener('dragover', function(e) { e.preventDefault(); });

function confirmDelete(slug, name) {
    document.getElementById('deleteThemeName').textContent = name;
    document.getElementById('deleteConfirmBtn').href = '?action=delete&theme=' + slug + '&csrf=<?= csrfToken() ?>';
    document.getElementById('deleteModal').classList.add('open');
}
</script>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
