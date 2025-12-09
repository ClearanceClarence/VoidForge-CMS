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
            
            // Find theme folder
            $items = array_diff(scandir($tempDir), ['.', '..']);
            $themeFolder = reset($items);
            $themePath = $tempDir . '/' . $themeFolder;
            
            // Validate theme (must have index.php or theme.json)
            if (is_dir($themePath) && (file_exists($themePath . '/index.php') || file_exists($themePath . '/theme.json'))) {
                $destPath = CMS_ROOT . '/themes/' . $themeFolder;
                
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
                
                rename($themePath, $destPath);
                setFlash('success', 'Theme uploaded successfully.');
            } else {
                setFlash('error', 'Invalid theme structure. Theme must have an index.php or theme.json file.');
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
    
    redirect(ADMIN_URL . '/themes.php');
}

$themes = Theme::getThemes();
$activeTheme = Theme::getActive();

include ADMIN_PATH . '/includes/header.php';
?>

<style>
.themes-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.themes-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    margin: 0;
}

.btn-upload {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, var(--forge-primary), var(--forge-secondary));
    color: #fff;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-upload:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px var(--forge-shadow-color-hover);
}

.themes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
}

.theme-card {
    background: var(--bg-card);
    border: 2px solid var(--border-color);
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.2s;
}

.theme-card.active {
    border-color: var(--forge-primary);
}

.theme-screenshot {
    aspect-ratio: 16/10;
    background: linear-gradient(135deg, var(--bg-card-header), var(--border-color));
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.theme-screenshot img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.theme-screenshot-placeholder {
    color: var(--text-muted);
    text-align: center;
}

.theme-screenshot-placeholder svg {
    width: 48px;
    height: 48px;
    opacity: 0.5;
    margin-bottom: 0.5rem;
}

.theme-info {
    padding: 1.25rem;
}

.theme-name {
    font-size: 1.125rem;
    font-weight: 700;
    margin: 0 0 0.25rem 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.theme-badge {
    font-size: 0.6875rem;
    font-weight: 600;
    padding: 0.25rem 0.625rem;
    border-radius: 20px;
    text-transform: uppercase;
}

.theme-badge.active {
    background: var(--forge-primary);
    color: #fff;
}

.theme-badge.default {
    background: var(--bg-card-header);
    color: var(--text-muted);
}

.theme-version {
    font-size: 0.8125rem;
    color: var(--text-muted);
    margin-bottom: 0.75rem;
}

.theme-description {
    font-size: 0.9375rem;
    color: var(--text-secondary);
    margin: 0 0 1rem 0;
    line-height: 1.5;
}

.theme-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.8125rem;
    color: var(--text-muted);
    margin-bottom: 1rem;
}

.theme-meta a {
    color: var(--forge-primary);
}

.theme-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.theme-tag {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    background: var(--bg-card-header);
    color: var(--text-muted);
    border-radius: 4px;
}

.theme-actions {
    display: flex;
    gap: 0.5rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
}

.btn-theme {
    flex: 1;
    padding: 0.625rem 1rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.15s;
    text-align: center;
    text-decoration: none;
}

.btn-activate {
    background: var(--forge-primary);
    color: #fff;
}

.btn-activate:hover {
    background: var(--forge-primary-dark);
    color: #fff;
}

.btn-preview {
    background: var(--bg-card-header);
    color: var(--text-primary);
}

.btn-preview:hover {
    background: var(--border-color);
}

.btn-delete {
    background: transparent;
    color: var(--text-muted);
    flex: 0;
    padding: 0.625rem;
}

.btn-delete:hover {
    background: #fee2e2;
    color: #dc2626;
}

.btn-current {
    background: var(--bg-card-header);
    color: var(--text-muted);
    cursor: default;
}

/* Upload Modal */
.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(4px);
}

.modal-overlay.open {
    display: flex;
}

.modal {
    background: var(--bg-card);
    border-radius: 16px;
    width: 100%;
    max-width: 480px;
    max-height: 90vh;
    overflow: auto;
}

.modal-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal-header h2 {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 700;
}

.modal-close {
    background: none;
    border: none;
    padding: 0.5rem;
    cursor: pointer;
    color: var(--text-muted);
    border-radius: 8px;
}

.modal-close:hover {
    background: var(--bg-card-header);
}

.modal-body {
    padding: 1.5rem;
}

.upload-zone {
    border: 2px dashed var(--border-color);
    border-radius: 12px;
    padding: 2.5rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
}

.upload-zone:hover,
.upload-zone.dragover {
    border-color: var(--forge-primary);
    background: var(--forge-primary-bg);
}

.upload-zone svg {
    width: 48px;
    height: 48px;
    color: var(--text-muted);
    margin-bottom: 1rem;
}

.upload-zone h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1rem;
}

.upload-zone p {
    margin: 0;
    font-size: 0.875rem;
    color: var(--text-muted);
}

.upload-zone input {
    display: none;
}
</style>

<div class="themes-header">
    <h1>Themes</h1>
    <button class="btn-upload" onclick="document.getElementById('uploadModal').classList.add('open')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <polyline points="17 8 12 3 7 8"/>
            <line x1="12" y1="3" x2="12" y2="15"/>
        </svg>
        Upload Theme
    </button>
</div>

<?php if (empty($themes)): ?>
<div style="text-align: center; padding: 4rem 2rem; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px;">
    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="color: var(--text-muted); margin-bottom: 1rem;">
        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
    </svg>
    <h2 style="margin: 0 0 0.5rem 0;">No themes installed</h2>
    <p style="color: var(--text-muted); margin: 0;">Upload a theme to get started.</p>
</div>
<?php else: ?>
<div class="themes-grid">
    <?php foreach ($themes as $slug => $theme): 
        $isActive = $slug === $activeTheme;
        $isDefault = $slug === 'flavor';
        $screenshot = Theme::getScreenshot($slug);
    ?>
    <div class="theme-card <?= $isActive ? 'active' : '' ?>">
        <div class="theme-screenshot">
            <?php if ($screenshot): ?>
            <img src="<?= esc($screenshot) ?>" alt="<?= esc($theme['name']) ?>">
            <?php else: ?>
            <div class="theme-screenshot-placeholder">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <circle cx="8.5" cy="8.5" r="1.5"/>
                    <path d="M21 15l-5-5L5 21"/>
                </svg>
                <div>No preview</div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="theme-info">
            <h3 class="theme-name">
                <?= esc($theme['name']) ?>
                <?php if ($isActive): ?>
                <span class="theme-badge active">Active</span>
                <?php elseif ($isDefault): ?>
                <span class="theme-badge default">Default</span>
                <?php endif; ?>
            </h3>
            
            <div class="theme-version">
                Version <?= esc($theme['version'] ?? '1.0.0') ?>
                <?php if ($theme['author']): ?>
                by <?php if ($theme['author_uri']): ?><a href="<?= esc($theme['author_uri']) ?>" target="_blank"><?= esc($theme['author']) ?></a><?php else: ?><?= esc($theme['author']) ?><?php endif; ?>
                <?php endif; ?>
            </div>
            
            <?php if ($theme['description']): ?>
            <p class="theme-description"><?= esc($theme['description']) ?></p>
            <?php endif; ?>
            
            <?php if (!empty($theme['tags'])): ?>
            <div class="theme-tags">
                <?php foreach ($theme['tags'] as $tag): ?>
                <span class="theme-tag"><?= esc($tag) ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <div class="theme-actions">
                <?php if ($isActive): ?>
                <span class="btn-theme btn-current">Current Theme</span>
                <?php else: ?>
                <a href="?action=activate&theme=<?= esc($slug) ?>&csrf=<?= csrfToken() ?>" class="btn-theme btn-activate">Activate</a>
                <?php endif; ?>
                
                <a href="<?= SITE_URL ?>?preview_theme=<?= esc($slug) ?>" target="_blank" class="btn-theme btn-preview">Preview</a>
                
                <?php if (!$isActive && !$isDefault): ?>
                <button class="btn-theme btn-delete" onclick="confirmDelete('<?= esc($slug) ?>', '<?= esc($theme['name']) ?>')" title="Delete">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                    </svg>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Upload Modal -->
<div class="modal-overlay" id="uploadModal" onclick="if(event.target === this) this.classList.remove('open')">
    <div class="modal">
        <div class="modal-header">
            <h2>Upload Theme</h2>
            <button class="modal-close" onclick="document.getElementById('uploadModal').classList.remove('open')">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <?= csrfField() ?>
                <label class="upload-zone" id="uploadZone">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                    <h3>Drop theme ZIP here</h3>
                    <p>or click to browse</p>
                    <input type="file" name="theme_zip" accept=".zip" onchange="this.form.submit()">
                </label>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal-overlay" id="deleteModal" onclick="if(event.target === this) this.classList.remove('open')">
    <div class="modal">
        <div class="modal-header">
            <h2>Delete Theme</h2>
            <button class="modal-close" onclick="document.getElementById('deleteModal').classList.remove('open')">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete <strong id="deleteThemeName"></strong>? This will permanently remove all theme files.</p>
            <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                <button class="btn-theme btn-preview" style="flex: 1;" onclick="document.getElementById('deleteModal').classList.remove('open')">Cancel</button>
                <a href="#" id="deleteConfirmBtn" class="btn-theme" style="flex: 1; background: #dc2626; color: #fff;">Delete</a>
            </div>
        </div>
    </div>
</div>

<script>
// Drag and drop
const uploadZone = document.getElementById('uploadZone');

['dragenter', 'dragover'].forEach(e => {
    uploadZone.addEventListener(e, () => uploadZone.classList.add('dragover'));
});

['dragleave', 'drop'].forEach(e => {
    uploadZone.addEventListener(e, () => uploadZone.classList.remove('dragover'));
});

uploadZone.addEventListener('drop', e => {
    e.preventDefault();
    const file = e.dataTransfer.files[0];
    if (file && file.name.endsWith('.zip')) {
        const input = uploadZone.querySelector('input');
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        document.getElementById('uploadForm').submit();
    }
});

uploadZone.addEventListener('dragover', e => e.preventDefault());

// Delete confirmation
function confirmDelete(slug, name) {
    document.getElementById('deleteThemeName').textContent = name;
    document.getElementById('deleteConfirmBtn').href = '?action=delete&theme=' + slug + '&csrf=<?= csrfToken() ?>';
    document.getElementById('deleteModal').classList.add('open');
}
</script>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
