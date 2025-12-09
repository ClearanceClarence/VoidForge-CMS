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
$activeThemeData = $themes[$activeTheme] ?? null;

// Separate active theme from others
$otherThemes = array_filter($themes, function($slug) use ($activeTheme) {
    return $slug !== $activeTheme;
}, ARRAY_FILTER_USE_KEY);

include ADMIN_PATH . '/includes/header.php';
?>

<style>
/* ============================================
   THEMES PAGE - UNIQUE DESIGN
   ============================================ */

.themes-page {
    max-width: 1400px;
    margin: 0 auto;
}

/* Hero Section */
.themes-hero {
    position: relative;
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4c1d95 100%);
    border-radius: 24px;
    padding: 3rem;
    margin-bottom: 2rem;
    overflow: hidden;
}

.themes-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity: 0.5;
}

.themes-hero::after {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(139, 92, 246, 0.3) 0%, transparent 70%);
    pointer-events: none;
}

.themes-hero-content {
    position: relative;
    z-index: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
}

.themes-hero-left h1 {
    font-size: 2.5rem;
    font-weight: 800;
    color: #fff;
    margin: 0 0 0.5rem 0;
    letter-spacing: -0.02em;
}

.themes-hero-left p {
    font-size: 1.125rem;
    color: rgba(255, 255, 255, 0.7);
    margin: 0 0 1.5rem 0;
}

.themes-hero-stats {
    display: flex;
    gap: 2rem;
}

.hero-stat {
    text-align: center;
}

.hero-stat-value {
    font-size: 2.5rem;
    font-weight: 800;
    color: #fff;
    line-height: 1;
}

.hero-stat-label {
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.6);
    margin-top: 0.25rem;
}

.themes-hero-right {
    display: flex;
    gap: 1rem;
}

.btn-hero {
    display: inline-flex;
    align-items: center;
    gap: 0.625rem;
    padding: 1rem 1.75rem;
    border-radius: 14px;
    font-weight: 600;
    font-size: 0.9375rem;
    text-decoration: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    border: none;
}

.btn-hero-primary {
    background: #fff;
    color: #4c1d95;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
}

.btn-hero-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
}

.btn-hero-secondary {
    background: rgba(255, 255, 255, 0.15);
    color: #fff;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.btn-hero-secondary:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: translateY(-2px);
}

/* Active Theme Section */
.active-theme-section {
    margin-bottom: 3rem;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text-color);
}

.section-title .icon {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    border-radius: 10px;
    color: #fff;
}

.active-theme-card {
    display: grid;
    grid-template-columns: 1fr 400px;
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 20px;
    overflow: hidden;
    transition: all 0.3s;
}

.active-theme-card:hover {
    border-color: var(--primary-color);
    box-shadow: 0 10px 40px rgba(99, 102, 241, 0.1);
}

.active-theme-preview {
    position: relative;
    background: linear-gradient(135deg, #0f172a, #1e293b);
    min-height: 350px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.active-theme-preview::before {
    content: '';
    position: absolute;
    inset: 0;
    background: 
        radial-gradient(circle at 20% 80%, rgba(99, 102, 241, 0.15) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(139, 92, 246, 0.15) 0%, transparent 50%);
}

.active-theme-preview img {
    width: 90%;
    height: auto;
    border-radius: 12px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    position: relative;
    z-index: 1;
}

.active-theme-preview .no-preview {
    text-align: center;
    color: rgba(255, 255, 255, 0.4);
    position: relative;
    z-index: 1;
}

.active-theme-preview .no-preview svg {
    width: 80px;
    height: 80px;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.active-badge {
    position: absolute;
    top: 1.5rem;
    left: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: #fff;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-radius: 8px;
    z-index: 2;
    box-shadow: 0 4px 15px rgba(34, 197, 94, 0.4);
}

.active-badge .pulse {
    width: 8px;
    height: 8px;
    background: #fff;
    border-radius: 50%;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.2); }
}

.active-theme-details {
    padding: 2rem;
    display: flex;
    flex-direction: column;
}

.active-theme-header {
    margin-bottom: 1.5rem;
}

.active-theme-name {
    font-size: 1.75rem;
    font-weight: 800;
    margin: 0 0 0.5rem 0;
    color: var(--text-color);
}

.active-theme-version {
    font-size: 0.9375rem;
    color: var(--text-muted);
}

.active-theme-version a {
    color: var(--primary-color);
    text-decoration: none;
}

.active-theme-description {
    font-size: 1rem;
    color: var(--text-secondary);
    line-height: 1.7;
    margin-bottom: 1.5rem;
    flex: 1;
}

.active-theme-features {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-bottom: 2rem;
}

.feature-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.feature-item .check {
    width: 22px;
    height: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(34, 197, 94, 0.1);
    color: #22c55e;
    border-radius: 6px;
}

.active-theme-actions {
    display: flex;
    gap: 0.75rem;
}

.btn-active-theme {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.875rem 1.25rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.9375rem;
    text-decoration: none;
    transition: all 0.2s;
    cursor: pointer;
    border: none;
}

.btn-customize {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #fff;
}

.btn-customize:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
}

.btn-view-site {
    background: var(--bg-tertiary);
    color: var(--text-color);
    border: 1px solid var(--border-color);
}

.btn-view-site:hover {
    background: var(--bg-secondary);
    border-color: var(--primary-color);
}

/* Available Themes Section */
.available-themes-section {
    margin-bottom: 2rem;
}

.themes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

/* Theme Card - Unique Design */
.theme-card {
    position: relative;
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 20px;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.theme-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    border-color: var(--primary-color);
}

.theme-card-image {
    position: relative;
    aspect-ratio: 16/10;
    background: linear-gradient(135deg, #1e293b, #334155);
    overflow: hidden;
}

.theme-card-image::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, transparent 50%, rgba(0,0,0,0.6) 100%);
    z-index: 1;
    opacity: 0;
    transition: opacity 0.3s;
}

.theme-card:hover .theme-card-image::before {
    opacity: 1;
}

.theme-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.theme-card:hover .theme-card-image img {
    transform: scale(1.05);
}

.theme-card-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: rgba(255, 255, 255, 0.3);
}

.theme-card-placeholder svg {
    width: 48px;
    height: 48px;
    margin-bottom: 0.5rem;
}

.theme-card-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    z-index: 2;
    opacity: 0;
    transition: opacity 0.3s;
}

.theme-card:hover .theme-card-overlay {
    opacity: 1;
}

.btn-overlay {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    background: rgba(255, 255, 255, 0.95);
    color: #1e293b;
    border-radius: 12px;
    text-decoration: none;
    transition: all 0.2s;
    cursor: pointer;
    border: none;
}

.btn-overlay:hover {
    transform: scale(1.1);
    background: #fff;
}

.btn-overlay.danger {
    background: rgba(239, 68, 68, 0.95);
    color: #fff;
}

.btn-overlay.danger:hover {
    background: #ef4444;
}

.theme-card-body {
    padding: 1.5rem;
}

.theme-card-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 0.75rem;
}

.theme-card-name {
    font-size: 1.125rem;
    font-weight: 700;
    margin: 0;
    color: var(--text-color);
}

.theme-card-badge {
    font-size: 0.6875rem;
    font-weight: 600;
    padding: 0.25rem 0.625rem;
    border-radius: 6px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.theme-card-badge.bundled {
    background: rgba(99, 102, 241, 0.1);
    color: var(--primary-color);
}

.theme-card-meta {
    font-size: 0.8125rem;
    color: var(--text-muted);
    margin-bottom: 1rem;
}

.theme-card-meta a {
    color: var(--primary-color);
    text-decoration: none;
}

.theme-card-description {
    font-size: 0.875rem;
    color: var(--text-secondary);
    line-height: 1.6;
    margin-bottom: 1.25rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.theme-card-footer {
    display: flex;
    gap: 0.5rem;
}

.btn-card {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.8125rem;
    text-decoration: none;
    transition: all 0.2s;
    cursor: pointer;
    border: none;
}

.btn-card-primary {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #fff;
}

.btn-card-primary:hover {
    box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
}

.btn-card-secondary {
    background: var(--bg-tertiary);
    color: var(--text-color);
}

.btn-card-secondary:hover {
    background: var(--border-color);
}

/* Empty State */
.themes-empty {
    text-align: center;
    padding: 5rem 2rem;
    background: var(--bg-secondary);
    border: 2px dashed var(--border-color);
    border-radius: 24px;
}

.themes-empty svg {
    width: 80px;
    height: 80px;
    color: var(--text-muted);
    margin-bottom: 1.5rem;
    opacity: 0.5;
}

.themes-empty h2 {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 0 0.5rem 0;
}

.themes-empty p {
    color: var(--text-muted);
    margin: 0 0 2rem 0;
}

/* Upload Modal */
.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(8px);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}

.modal-overlay.open {
    display: flex;
}

.modal {
    background: var(--bg-secondary);
    border-radius: 24px;
    width: 100%;
    max-width: 500px;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
    overflow: hidden;
}

.modal-header {
    padding: 1.5rem 2rem;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal-header h2 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 700;
}

.modal-close {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.2);
    border: none;
    border-radius: 10px;
    color: #fff;
    cursor: pointer;
    transition: all 0.2s;
}

.modal-close:hover {
    background: rgba(255, 255, 255, 0.3);
}

.modal-body {
    padding: 2rem;
}

.upload-zone {
    border: 2px dashed var(--border-color);
    border-radius: 16px;
    padding: 3rem 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    background: var(--bg-tertiary);
}

.upload-zone:hover,
.upload-zone.dragover {
    border-color: var(--primary-color);
    background: rgba(99, 102, 241, 0.05);
}

.upload-zone .upload-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
    border-radius: 16px;
    color: var(--primary-color);
}

.upload-zone h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.125rem;
    font-weight: 600;
}

.upload-zone p {
    margin: 0;
    font-size: 0.875rem;
    color: var(--text-muted);
}

.upload-zone input {
    display: none;
}

/* Delete Modal */
.delete-modal-body {
    text-align: center;
}

.delete-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    border-radius: 16px;
}

.delete-modal-body h3 {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0 0 0.5rem 0;
}

.delete-modal-body p {
    color: var(--text-muted);
    margin: 0 0 2rem 0;
}

.delete-actions {
    display: flex;
    gap: 0.75rem;
}

.btn-modal {
    flex: 1;
    padding: 0.875rem 1.5rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.9375rem;
    text-decoration: none;
    transition: all 0.2s;
    cursor: pointer;
    border: none;
    text-align: center;
}

.btn-modal-cancel {
    background: var(--bg-tertiary);
    color: var(--text-color);
}

.btn-modal-cancel:hover {
    background: var(--border-color);
}

.btn-modal-danger {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: #fff;
}

.btn-modal-danger:hover {
    box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
}

/* Responsive */
@media (max-width: 1024px) {
    .themes-hero-content {
        flex-direction: column;
        text-align: center;
    }
    
    .themes-hero-stats {
        justify-content: center;
    }
    
    .active-theme-card {
        grid-template-columns: 1fr;
    }
    
    .active-theme-preview {
        min-height: 250px;
    }
}

@media (max-width: 768px) {
    .themes-hero {
        padding: 2rem;
    }
    
    .themes-hero-left h1 {
        font-size: 1.75rem;
    }
    
    .themes-hero-right {
        flex-direction: column;
        width: 100%;
    }
    
    .btn-hero {
        justify-content: center;
    }
    
    .themes-grid {
        grid-template-columns: 1fr;
    }
    
    .active-theme-features {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="themes-page">
    <!-- Hero Section -->
    <div class="themes-hero">
        <div class="themes-hero-content">
            <div class="themes-hero-left">
                <h1>Theme Gallery</h1>
                <p>Customize the look and feel of your website</p>
                <div class="themes-hero-stats">
                    <div class="hero-stat">
                        <div class="hero-stat-value"><?= count($themes) ?></div>
                        <div class="hero-stat-label">Installed</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-value">1</div>
                        <div class="hero-stat-label">Active</div>
                    </div>
                </div>
            </div>
            <div class="themes-hero-right">
                <button class="btn-hero btn-hero-primary" onclick="document.getElementById('uploadModal').classList.add('open')">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                    Upload Theme
                </button>
                <a href="<?= SITE_URL ?>" target="_blank" class="btn-hero btn-hero-secondary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                        <polyline points="15 3 21 3 21 9"/>
                        <line x1="10" y1="14" x2="21" y2="3"/>
                    </svg>
                    View Site
                </a>
            </div>
        </div>
    </div>

    <?php if ($activeThemeData): 
        $screenshot = Theme::getScreenshot($activeTheme);
    ?>
    <!-- Active Theme Section -->
    <div class="active-theme-section">
        <div class="section-header">
            <h2 class="section-title">
                <span class="icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </span>
                Active Theme
            </h2>
        </div>
        
        <div class="active-theme-card">
            <div class="active-theme-preview">
                <span class="active-badge">
                    <span class="pulse"></span>
                    Live
                </span>
                <?php if ($screenshot): ?>
                <img src="<?= esc($screenshot) ?>" alt="<?= esc($activeThemeData['name']) ?>">
                <?php else: ?>
                <div class="no-preview">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                        <circle cx="8.5" cy="8.5" r="1.5"/>
                        <path d="M21 15l-5-5L5 21"/>
                    </svg>
                    <div>No preview available</div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="active-theme-details">
                <div class="active-theme-header">
                    <h3 class="active-theme-name"><?= esc($activeThemeData['name']) ?></h3>
                    <div class="active-theme-version">
                        Version <?= esc($activeThemeData['version'] ?? '1.0.0') ?>
                        <?php if ($activeThemeData['author']): ?>
                        &middot; by 
                        <?php if ($activeThemeData['author_uri']): ?>
                        <a href="<?= esc($activeThemeData['author_uri']) ?>" target="_blank"><?= esc($activeThemeData['author']) ?></a>
                        <?php else: ?>
                        <?= esc($activeThemeData['author']) ?>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <p class="active-theme-description">
                    <?= esc($activeThemeData['description'] ?? 'A beautiful theme for your VoidForge website.') ?>
                </p>
                
                <div class="active-theme-features">
                    <div class="feature-item">
                        <span class="check">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </span>
                        Responsive Design
                    </div>
                    <div class="feature-item">
                        <span class="check">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </span>
                        SEO Optimized
                    </div>
                    <div class="feature-item">
                        <span class="check">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </span>
                        Fast Loading
                    </div>
                    <div class="feature-item">
                        <span class="check">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </span>
                        Customizable
                    </div>
                </div>
                
                <div class="active-theme-actions">
                    <a href="<?= ADMIN_URL ?>/theme-settings.php" class="btn-active-theme btn-customize">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                        </svg>
                        Customize
                    </a>
                    <a href="<?= SITE_URL ?>" target="_blank" class="btn-active-theme btn-view-site">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
    <?php endif; ?>

    <!-- Available Themes Section -->
    <?php if (!empty($otherThemes)): ?>
    <div class="available-themes-section">
        <div class="section-header">
            <h2 class="section-title">
                <span class="icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                    </svg>
                </span>
                Available Themes
            </h2>
        </div>
        
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
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </a>
                        <a href="<?= SITE_URL ?>?preview_theme=<?= esc($slug) ?>" target="_blank" class="btn-overlay" title="Preview">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </a>
                        <?php if (!$isBundled): ?>
                        <button class="btn-overlay danger" onclick="confirmDelete('<?= esc($slug) ?>', '<?= esc($theme['name']) ?>')" title="Delete">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                            </svg>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="theme-card-body">
                    <div class="theme-card-header">
                        <h3 class="theme-card-name"><?= esc($theme['name']) ?></h3>
                        <?php if ($isBundled): ?>
                        <span class="theme-card-badge bundled">Bundled</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="theme-card-meta">
                        v<?= esc($theme['version'] ?? '1.0.0') ?>
                        <?php if ($theme['author']): ?>
                        &middot; <?php if ($theme['author_uri']): ?><a href="<?= esc($theme['author_uri']) ?>" target="_blank"><?= esc($theme['author']) ?></a><?php else: ?><?= esc($theme['author']) ?><?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($theme['description']): ?>
                    <p class="theme-card-description"><?= esc($theme['description']) ?></p>
                    <?php endif; ?>
                    
                    <div class="theme-card-footer">
                        <a href="?action=activate&theme=<?= esc($slug) ?>&csrf=<?= csrfToken() ?>" class="btn-card btn-card-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            Activate
                        </a>
                        <a href="<?= SITE_URL ?>?preview_theme=<?= esc($slug) ?>" target="_blank" class="btn-card btn-card-secondary">
                            Preview
                        </a>
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
        <p>Upload a theme ZIP file to get started with customizing your site.</p>
        <button class="btn-hero btn-hero-primary" onclick="document.getElementById('uploadModal').classList.add('open')" style="background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="17 8 12 3 7 8"/>
                <line x1="12" y1="3" x2="12" y2="15"/>
            </svg>
            Upload Your First Theme
        </button>
    </div>
    <?php endif; ?>
</div>

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
                    <div class="upload-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
        <div class="modal-body delete-modal-body">
            <div class="delete-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                    <line x1="10" y1="11" x2="10" y2="17"/>
                    <line x1="14" y1="11" x2="14" y2="17"/>
                </svg>
            </div>
            <h3>Delete "<span id="deleteThemeName"></span>"?</h3>
            <p>This will permanently remove all theme files. This action cannot be undone.</p>
            <div class="delete-actions">
                <button class="btn-modal btn-modal-cancel" onclick="document.getElementById('deleteModal').classList.remove('open')">Cancel</button>
                <a href="#" id="deleteConfirmBtn" class="btn-modal btn-modal-danger">Delete Theme</a>
            </div>
        </div>
    </div>
</div>

<script>
// Drag and drop
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

// Delete confirmation
function confirmDelete(slug, name) {
    document.getElementById('deleteThemeName').textContent = name;
    document.getElementById('deleteConfirmBtn').href = '?action=delete&theme=' + slug + '&csrf=<?= csrfToken() ?>';
    document.getElementById('deleteModal').classList.add('open');
}
</script>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
