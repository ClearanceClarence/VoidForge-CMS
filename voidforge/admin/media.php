<?php
/**
 * Media Library - VoidForge CMS
 * Grid/List views with modal editing
 */

// Debug endpoint
if (isset($_GET['action']) && $_GET['action'] === 'debug') {
    header('Content-Type: application/json');
    define('CMS_ROOT', dirname(__DIR__));
    $debug = ['php_version' => PHP_VERSION];
    try {
        require_once CMS_ROOT . '/includes/config.php';
        require_once CMS_ROOT . '/includes/database.php';
        $table = Database::table('media');
        $count = Database::queryValue("SELECT COUNT(*) FROM {$table}");
        $debug['media_count'] = (int)$count;
        $debug['status'] = 'OK';
    } catch (Throwable $e) {
        $debug['error'] = $e->getMessage();
    }
    echo json_encode($debug, JSON_PRETTY_PRINT);
    exit;
}

// Suppress errors for AJAX
if (isset($_GET['action'])) {
    error_reporting(0);
    ini_set('display_errors', '0');
}

define('CMS_ROOT', dirname(__DIR__));
require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/user.php';
require_once CMS_ROOT . '/includes/post.php';
require_once CMS_ROOT . '/includes/media.php';
require_once CMS_ROOT . '/includes/plugin.php';

Post::init();
User::startSession();
User::requireLogin();

// AJAX: List media
if (isset($_GET['action']) && $_GET['action'] === 'list') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    try {
        $args = ['orderby' => 'created_at', 'order' => 'DESC'];
        if (isset($_GET['type']) && $_GET['type'] === 'image') $args['type'] = 'image';
        if (isset($_GET['folder_id'])) $args['folder_id'] = (int)$_GET['folder_id'];
        if (isset($_GET['search'])) $args['search'] = $_GET['search'];
        
        $media = Media::query($args);
        foreach ($media as &$item) {
            if (empty($item['url'])) $item['url'] = Media::getUrl($item);
            $item['thumbnail_url'] = $item['url'];
            if (!empty($item['mime_type']) && strpos($item['mime_type'], 'image/') === 0) {
                try {
                    $thumbUrl = Media::getThumbnailUrl($item, 'medium');
                    if ($thumbUrl) $item['thumbnail_url'] = $thumbUrl;
                } catch (Throwable $e) {}
            }
        }
        echo json_encode(['success' => true, 'media' => $media]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Regenerate thumbnails
if (isset($_GET['action']) && $_GET['action'] === 'regenerate_thumbnails') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    try {
        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            $item = Media::find($id);
            if ($item && strpos($item['mime_type'], 'image/') === 0) {
                Media::regenerateThumbnails($item);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Not an image']);
            }
        } else {
            $result = Media::regenerateAllThumbnails();
            echo json_encode(['success' => true, 'message' => "Regenerated {$result['success']} thumbnails"]);
        }
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Upload file
if (isset($_GET['ajax']) && isset($_GET['action']) && $_GET['action'] === 'upload') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    
    try {
        // Verify CSRF
        if (!verifyCsrf()) {
            echo json_encode(['success' => false, 'error' => 'Invalid security token']);
            exit;
        }
        
        // Check for file
        if (empty($_FILES['file'])) {
            echo json_encode(['success' => false, 'error' => 'No file uploaded']);
            exit;
        }
        
        $file = $_FILES['file'];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temp folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
                UPLOAD_ERR_EXTENSION => 'Upload blocked by extension',
            ];
            $errorMsg = $errors[$file['error']] ?? 'Unknown upload error';
            echo json_encode(['success' => false, 'error' => $errorMsg]);
            exit;
        }
        
        // Get folder ID if provided
        $folderId = isset($_POST['folder_id']) && $_POST['folder_id'] !== '' ? (int)$_POST['folder_id'] : 0;
        
        // Get current user ID
        $userId = User::current()['id'] ?? null;
        
        // Upload via Media class - returns array with success, id, media
        $result = Media::upload($file, $userId, $folderId);
        
        if ($result && !empty($result['success'])) {
            $mediaItem = $result['media'] ?? null;
            $mediaId = $result['id'] ?? 0;
            
            if ($mediaItem) {
                echo json_encode([
                    'success' => true,
                    'id' => $mediaId,
                    'url' => $mediaItem['url'] ?? Media::getUrl($mediaItem),
                    'filename' => $mediaItem['filename'] ?? '',
                    'mime_type' => $mediaItem['mime_type'] ?? ''
                ]);
            } else {
                echo json_encode(['success' => true, 'id' => $mediaId]);
            }
        } else {
            $error = $result['error'] ?? 'Upload failed';
            echo json_encode(['success' => false, 'error' => $error]);
        }
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        setFlash('error', 'Invalid security token.');
        redirect(ADMIN_URL . '/media.php');
    }
    
    // Upload
    if (!empty($_FILES['media_upload'])) {
        $folderId = isset($_POST['folder_id']) && $_POST['folder_id'] !== '' ? (int)$_POST['folder_id'] : null;
        $files = $_FILES['media_upload'];
        $uploaded = 0;
        
        if (is_array($files['name'])) {
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $file = ['name' => $files['name'][$i], 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i]];
                    if (Media::upload($file, $folderId)) $uploaded++;
                }
            }
        } else if ($files['error'] === UPLOAD_ERR_OK) {
            if (Media::upload($files, $folderId)) $uploaded++;
        }
        
        setFlash('success', $uploaded > 0 ? "Uploaded {$uploaded} file(s)." : 'No files uploaded.');
        redirect(ADMIN_URL . '/media.php' . ($folderId ? "?folder={$folderId}" : ''));
    }
    
    // Update
    if (isset($_POST['action']) && $_POST['action'] === 'update') {
        $id = (int)($_POST['media_id'] ?? 0);
        if ($id && Media::update($id, ['title' => $_POST['title'] ?? '', 'alt_text' => $_POST['alt_text'] ?? ''])) {
            setFlash('success', 'Media updated.');
        }
        redirect(ADMIN_URL . '/media.php');
    }
    
    // Delete
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = (int)($_POST['media_id'] ?? 0);
        if ($id && Media::delete($id)) {
            setFlash('success', 'File deleted.');
        }
        redirect(ADMIN_URL . '/media.php');
    }
    
    // Create folder
    if (isset($_POST['action']) && $_POST['action'] === 'create_folder') {
        $folderName = trim($_POST['folder_name'] ?? '');
        if ($folderName) {
            Media::createFolder($folderName);
            setFlash('success', 'Folder created.');
        }
        redirect(ADMIN_URL . '/media.php');
    }
}

$pageTitle = 'Media Library';
$currentPage = 'media';
$currentFolder = isset($_GET['folder']) ? (int)$_GET['folder'] : null;
$folders = Media::getFolders();
$media = Media::query(['folder_id' => $currentFolder, 'limit' => 200]);
$mediaCount = count($media);

include ADMIN_PATH . '/includes/header.php';
?>

<style>
/* Layout */
.media-page { padding: 0; }
.media-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}
.media-header-left { display: flex; align-items: center; gap: 1rem; }
.media-header-left h1 { font-size: 1.75rem; font-weight: 700; margin: 0; }
.media-count {
    padding: 0.25rem 0.75rem;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 100px;
    font-size: 0.8125rem;
    color: var(--text-muted);
}
.media-header-right { display: flex; gap: 0.5rem; align-items: center; }

/* View Toggle */
.view-toggle {
    display: flex;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 3px;
}
.view-toggle-btn {
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
.view-toggle-btn:hover { color: var(--text-primary); }
.view-toggle-btn.active {
    background: var(--forge-primary);
    color: #fff;
}

/* Upload Zone */
.upload-zone {
    border: 2px dashed var(--border-color);
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    margin-bottom: 1.5rem;
    cursor: pointer;
    transition: all 0.2s;
    background: var(--bg-card);
}
.upload-zone:hover, .upload-zone.dragover {
    border-color: var(--forge-primary);
    background: var(--forge-primary-bg);
}
.upload-zone-icon {
    width: 48px;
    height: 48px;
    margin: 0 auto 1rem;
    color: var(--text-muted);
}
.upload-zone h3 { font-size: 1rem; font-weight: 600; margin: 0 0 0.25rem; }
.upload-zone p { font-size: 0.875rem; color: var(--text-muted); margin: 0; }

/* Folders */
.folders-bar {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}
.folder-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.15s;
}
.folder-btn:hover { border-color: var(--forge-primary); color: var(--text-primary); }
.folder-btn.active {
    background: var(--forge-primary);
    border-color: var(--forge-primary);
    color: #fff;
}
.folder-btn svg { width: 16px; height: 16px; }

/* Grid View */
.media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 1rem;
}
.media-item {
    position: relative;
    aspect-ratio: 1;
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    background: var(--bg-card);
    border: 2px solid transparent;
    transition: all 0.15s;
}
.media-item:hover { border-color: var(--forge-primary); transform: translateY(-2px); }
.media-item.selected { border-color: var(--forge-primary); box-shadow: 0 0 0 3px var(--forge-primary-bg); }
.media-item-inner {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--bg-card-header);
    pointer-events: none;
}
.media-item-inner img { width: 100%; height: 100%; object-fit: cover; pointer-events: none; }
.media-item-icon {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-muted);
    pointer-events: none;
}
.media-item-icon svg { width: 32px; height: 32px; pointer-events: none; }
.media-item-icon span { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; pointer-events: none; }
.media-item-name {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 2rem 0.5rem 0.5rem;
    background: linear-gradient(transparent, rgba(0,0,0,0.8));
    color: #fff;
    font-size: 0.75rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    pointer-events: none;
}
.media-item-check {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 24px;
    height: 24px;
    background: var(--forge-primary);
    border-radius: 50%;
    display: none;
    align-items: center;
    justify-content: center;
    color: #fff;
    pointer-events: none;
}
.media-item.selected .media-item-check { display: flex; }

/* List View */
.media-list { display: none; flex-direction: column; gap: 0.5rem; }
.media-list.active { display: flex; }
.media-grid.active { display: grid; }
.media-grid:not(.active) { display: none; }

.media-list-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 1rem;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.15s;
    position: relative;
    z-index: 1;
}
.media-list-item:hover { border-color: var(--forge-primary); }
.media-list-item.selected { border-color: var(--forge-primary); background: var(--forge-primary-bg); }
.media-list-thumb {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
    background: var(--bg-card-header);
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
}
.media-list-thumb img { width: 100%; height: 100%; object-fit: cover; pointer-events: none; }
.media-list-thumb svg { width: 24px; height: 24px; color: var(--text-muted); pointer-events: none; }
.media-list-info { flex: 1; min-width: 0; pointer-events: none; }
.media-list-name { font-weight: 600; font-size: 0.9375rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; pointer-events: none; }
.media-list-meta { font-size: 0.8125rem; color: var(--text-muted); display: flex; gap: 1rem; margin-top: 0.125rem; pointer-events: none; }
.media-list-check {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    border: 2px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.15s;
    pointer-events: none;
}
.media-list-item.selected .media-list-check {
    background: var(--forge-primary);
    border-color: var(--forge-primary);
    color: #fff;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--text-muted);
}
.empty-state svg { width: 64px; height: 64px; margin-bottom: 1rem; opacity: 0.5; }
.empty-state h3 { font-size: 1.25rem; margin: 0 0 0.5rem; color: var(--text-primary); }
.empty-state p { margin: 0; }

/* Modal */
.media-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.85);
    backdrop-filter: blur(8px);
    z-index: 1000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}
.media-modal-backdrop.active { display: flex; }

.media-modal {
    display: grid;
    grid-template-columns: 1fr 440px;
    width: 100%;
    max-width: 1280px;
    max-height: 90vh;
    background: var(--bg-card);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 25px 50px rgba(0,0,0,0.5);
}

.media-modal-preview {
    background: #0a0a0f;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    min-height: 400px;
}
.media-modal-preview img {
    max-width: 100%;
    max-height: 70vh;
    object-fit: contain;
}
.media-modal-preview-icon {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    color: var(--text-muted);
}
.media-modal-preview-icon svg { width: 64px; height: 64px; }
.media-modal-preview-icon span { font-size: 1.25rem; font-weight: 600; text-transform: uppercase; }

/* Modal Navigation */
.modal-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 48px;
    height: 48px;
    background: rgba(255,255,255,0.1);
    border: none;
    border-radius: 50%;
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    backdrop-filter: blur(4px);
}
.modal-nav:hover { background: rgba(255,255,255,0.2); transform: translateY(-50%) scale(1.1); }
.modal-nav:disabled { opacity: 0.3; cursor: not-allowed; }
.modal-nav:disabled:hover { transform: translateY(-50%); background: rgba(255,255,255,0.1); }
.modal-nav.prev { left: 1rem; }
.modal-nav.next { right: 1rem; }
.modal-nav svg { width: 24px; height: 24px; }

.modal-counter {
    position: absolute;
    bottom: 1rem;
    left: 50%;
    transform: translateX(-50%);
    padding: 0.375rem 0.875rem;
    background: rgba(0,0,0,0.6);
    border-radius: 100px;
    font-size: 0.8125rem;
    color: rgba(255,255,255,0.8);
    backdrop-filter: blur(4px);
}

/* Modal Sidebar - Light Accessible Design */
.media-modal-sidebar {
    display: flex;
    flex-direction: column;
    background: #ffffff;
    border-left: 1px solid #e2e8f0;
    width: 440px;
    max-height: 90vh;
}

.modal-sidebar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem 1.75rem;
    background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
    border-bottom: 1px solid #e2e8f0;
}

.modal-sidebar-title {
    font-weight: 700;
    font-size: 1.125rem;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.modal-sidebar-title::before {
    content: '';
    width: 4px;
    height: 22px;
    background: linear-gradient(180deg, var(--forge-primary, #6366f1), var(--forge-secondary, #8b5cf6));
    border-radius: 2px;
}

.modal-close-btn {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    border-radius: 10px;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s;
}
.modal-close-btn:hover { 
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #1e293b;
}

.modal-sidebar-body {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem 1.75rem;
}

/* Section Cards */
.modal-section {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 1.25rem;
    margin-bottom: 1.25rem;
}
.modal-section:last-child { margin-bottom: 0; }

.modal-section-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
    padding-bottom: 0.875rem;
    border-bottom: 1px solid #e2e8f0;
}

.modal-section-icon {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
    border-radius: 10px;
    color: var(--forge-primary, #6366f1);
}
.modal-section-icon svg { width: 18px; height: 18px; }

.modal-section-title {
    font-size: 0.9375rem;
    font-weight: 700;
    color: #1e293b;
    letter-spacing: -0.01em;
}

/* Info Grid */
.modal-info-grid {
    display: flex;
    flex-direction: column;
    gap: 0.625rem;
}

.modal-info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1rem;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    font-size: 0.875rem;
}

.modal-info-label {
    color: #64748b;
    font-weight: 500;
}

.modal-info-value {
    color: #1e293b;
    font-weight: 600;
    text-align: right;
    max-width: 55%;
    word-break: break-all;
}

/* Form Inputs */
.modal-form-group { margin-bottom: 1rem; }
.modal-form-group:last-child { margin-bottom: 0; }

.modal-form-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
}

.modal-form-input {
    width: 100%;
    padding: 0.875rem 1rem;
    background: #ffffff;
    border: 1px solid #d1d5db;
    border-radius: 10px;
    color: #1e293b;
    font-size: 0.9375rem;
    transition: all 0.2s;
}
.modal-form-input:focus {
    outline: none;
    border-color: var(--forge-primary, #6366f1);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}
.modal-form-input::placeholder { color: #9ca3af; }

.modal-form-hint {
    font-size: 0.8125rem;
    color: #6b7280;
    margin-top: 0.5rem;
}

/* URL Group */
.modal-url-group {
    display: flex;
    gap: 0.625rem;
    align-items: stretch;
}
.modal-url-group input {
    flex: 1;
    font-family: 'SF Mono', 'Monaco', 'Consolas', monospace;
    font-size: 0.8125rem;
    padding: 0.75rem 0.875rem;
    background: #f1f5f9;
}

.btn-copy-url {
    width: 44px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--forge-primary, #6366f1), var(--forge-secondary, #8b5cf6));
    border: none;
    border-radius: 10px;
    color: #fff;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-copy-url:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
}
.btn-copy-url.copied {
    background: linear-gradient(135deg, #10b981, #059669);
}

/* Footer */
.modal-sidebar-footer {
    display: flex;
    gap: 1rem;
    padding: 1.25rem 1.75rem;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
}

.modal-sidebar-footer .btn {
    flex: 1;
    padding: 0.875rem 1.25rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.9375rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.625rem;
    transition: all 0.2s;
}

.modal-sidebar-footer .btn-danger {
    background: #ffffff;
    border: 2px solid #fecaca;
    color: #dc2626;
}
.modal-sidebar-footer .btn-danger:hover {
    background: #fef2f2;
    border-color: #f87171;
}

.modal-sidebar-footer .btn-primary {
    background: linear-gradient(135deg, var(--forge-primary, #6366f1), var(--forge-secondary, #8b5cf6));
    border: none;
    color: #fff;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}
.modal-sidebar-footer .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(99, 102, 241, 0.4);
}

.modal-sidebar-footer {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 1.25rem 1.5rem;
    border-top: 1px solid var(--border-color);
    background: var(--bg-card-header);
}

/* Dropdown */
.dropdown { position: relative; }
.dropdown-menu {
    position: absolute;
    right: 0;
    top: 100%;
    margin-top: 0.25rem;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 0.25rem 0;
    min-width: 200px;
    z-index: 100;
    display: none;
    box-shadow: var(--shadow-lg);
}
.dropdown-menu.show { display: block; }
.dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1rem;
    width: 100%;
    border: none;
    background: none;
    color: var(--text-primary);
    font-size: 0.875rem;
    cursor: pointer;
    text-align: left;
}
.dropdown-item:hover { background: var(--bg-card-header); }

@media (max-width: 1024px) {
    .media-modal { grid-template-columns: 1fr; max-height: 95vh; max-width: 600px; }
    .media-modal-preview { min-height: 280px; }
    .media-modal-preview img { max-height: 40vh; }
    .modal-nav { width: 44px; height: 44px; }
    .media-modal-sidebar { width: 100%; max-height: 50vh; }
    .media-header { flex-direction: column; align-items: stretch; gap: 1rem; }
    .media-header-right { justify-content: space-between; }
}
</style>

<div class="media-page">
    <div class="media-header">
        <div class="media-header-left">
            <h1>Media Library</h1>
            <span class="media-count"><?= $mediaCount ?> items</span>
        </div>
        <div class="media-header-right">
            <div class="view-toggle">
                <button type="button" class="view-toggle-btn active" data-view="grid" onclick="setView('grid')" title="Grid view">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                </button>
                <button type="button" class="view-toggle-btn" data-view="list" onclick="setView('list')" title="List view">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                </button>
            </div>
            <div class="dropdown">
                <button type="button" class="btn btn-secondary" onclick="this.nextElementSibling.classList.toggle('show')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/></svg>
                </button>
                <div class="dropdown-menu">
                    <button type="button" class="dropdown-item" onclick="regenerateAllThumbnails()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                        Regenerate All Thumbnails
                    </button>
                </div>
            </div>
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('newFolderModal').classList.add('active')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
                New Folder
            </button>
            <label class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Upload
                <input type="file" id="fileInput" name="media_upload[]" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx" hidden>
            </label>
        </div>
    </div>

    <div class="upload-zone" id="uploadZone">
        <div class="upload-zone-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        </div>
        <h3>Drop files here to upload</h3>
        <p>or click to browse</p>
    </div>

    <?php if (!empty($folders) || $currentFolder): ?>
    <div class="folders-bar">
        <a href="<?= ADMIN_URL ?>/media.php" class="folder-btn <?= $currentFolder === null ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
            All Files
        </a>
        <?php foreach ($folders as $folder): ?>
        <a href="<?= ADMIN_URL ?>/media.php?folder=<?= $folder['id'] ?>" class="folder-btn <?= $currentFolder === (int)$folder['id'] ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            <?= esc($folder['name']) ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($media)): ?>
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
        <h3>No media files</h3>
        <p>Upload your first file to get started.</p>
    </div>
    <?php else: ?>
    
    <!-- Grid View -->
    <div class="media-grid active" id="mediaGrid">
        <?php $idx = 0; foreach ($media as $item): ?>
        <div class="media-item" data-index="<?= $idx ?>" data-id="<?= $item['id'] ?>">
            <div class="media-item-inner">
                <?php if (strpos($item['mime_type'], 'image/') === 0): ?>
                <img src="<?= esc($item['url']) ?>" alt="<?= esc($item['alt_text'] ?? $item['filename']) ?>" loading="lazy">
                <?php else: ?>
                <div class="media-item-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <span><?= strtoupper(pathinfo($item['filename'], PATHINFO_EXTENSION)) ?></span>
                </div>
                <?php endif; ?>
            </div>
            <div class="media-item-check">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <div class="media-item-name"><?= esc($item['filename']) ?></div>
        </div>
        <?php $idx++; endforeach; ?>
    </div>

    <!-- List View -->
    <div class="media-list" id="mediaList">
        <?php $idx = 0; foreach ($media as $item): ?>
        <div class="media-list-item" data-index="<?= $idx ?>" data-id="<?= $item['id'] ?>">
            <div class="media-list-thumb">
                <?php if (strpos($item['mime_type'], 'image/') === 0): ?>
                <img src="<?= esc($item['url']) ?>" alt="" loading="lazy">
                <?php else: ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                <?php endif; ?>
            </div>
            <div class="media-list-info">
                <div class="media-list-name"><?= esc($item['filename']) ?></div>
                <div class="media-list-meta">
                    <span><?= esc($item['mime_type']) ?></span>
                    <span><?= formatFileSize($item['filesize'] ?? 0) ?></span>
                    <span><?= date('M j, Y', strtotime($item['created_at'])) ?></span>
                </div>
            </div>
            <div class="media-list-check">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
        </div>
        <?php $idx++; endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Media Modal -->
<div class="media-modal-backdrop" id="mediaModal">
    <div class="media-modal">
        <div class="media-modal-preview">
            <button type="button" class="modal-nav prev" onclick="navigateMedia(-1)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
            <div id="modalPreview"></div>
            <button type="button" class="modal-nav next" onclick="navigateMedia(1)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
            <div class="modal-counter"><span id="modalCurrent">1</span> / <span id="modalTotal"><?= $mediaCount ?></span></div>
        </div>
        <div class="media-modal-sidebar">
            <div class="modal-sidebar-header">
                <span class="modal-sidebar-title">File Details</span>
                <button type="button" class="modal-close-btn" onclick="closeMediaModal()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="modal-sidebar-body">
                <form id="modalForm" method="post">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="media_id" id="modalMediaId">
                    
                    <!-- Info Section -->
                    <div class="modal-section">
                        <div class="modal-section-header">
                            <div class="modal-section-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                            </div>
                            <span class="modal-section-title">Information</span>
                        </div>
                        <div class="modal-info-grid">
                            <div class="modal-info-row">
                                <span class="modal-info-label">Filename</span>
                                <span class="modal-info-value" id="modalFilename">-</span>
                            </div>
                            <div class="modal-info-row">
                                <span class="modal-info-label">Type</span>
                                <span class="modal-info-value" id="modalType">-</span>
                            </div>
                            <div class="modal-info-row">
                                <span class="modal-info-label">Size</span>
                                <span class="modal-info-value" id="modalSize">-</span>
                            </div>
                            <div class="modal-info-row" id="modalDimensionsRow">
                                <span class="modal-info-label">Dimensions</span>
                                <span class="modal-info-value" id="modalDimensions">-</span>
                            </div>
                            <div class="modal-info-row">
                                <span class="modal-info-label">Uploaded</span>
                                <span class="modal-info-value" id="modalDate">-</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Edit Section -->
                    <div class="modal-section">
                        <div class="modal-section-header">
                            <div class="modal-section-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </div>
                            <span class="modal-section-title">Edit Details</span>
                        </div>
                        <div class="modal-form-group">
                            <label class="modal-form-label">Title</label>
                            <input type="text" name="title" id="modalTitle" class="modal-form-input" placeholder="Enter title...">
                        </div>
                        <div class="modal-form-group">
                            <label class="modal-form-label">Alt Text</label>
                            <input type="text" name="alt_text" id="modalAlt" class="modal-form-input" placeholder="Describe the image...">
                            <p class="modal-form-hint">Used for accessibility and SEO</p>
                        </div>
                    </div>
                    
                    <!-- URL Section -->
                    <div class="modal-section">
                        <div class="modal-section-header">
                            <div class="modal-section-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                            </div>
                            <span class="modal-section-title">File URL</span>
                        </div>
                        <div class="modal-url-group">
                            <input type="text" id="modalUrl" class="modal-form-input" readonly>
                            <button type="button" class="btn-copy-url" onclick="copyUrl()" title="Copy URL">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-sidebar-footer">
                <button type="button" class="btn btn-danger" onclick="deleteMedia()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    Delete
                </button>
                <button type="submit" form="modalForm" class="btn btn-primary">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Upload Form -->
<form id="uploadForm" method="post" enctype="multipart/form-data" hidden>
    <?= csrfField() ?>
    <input type="hidden" name="folder_id" value="<?= $currentFolder ?? '' ?>">
</form>

<!-- Delete Form -->
<form id="deleteForm" method="post" hidden>
    <?= csrfField() ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="media_id" id="deleteMediaId">
</form>

<!-- New Folder Modal -->
<div class="modal-backdrop" id="newFolderModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Create New Folder</h3>
            <button type="button" class="modal-close" onclick="document.getElementById('newFolderModal').classList.remove('active')">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form method="post">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="create_folder">
            <div class="modal-body">
                <div class="form-group mb-0">
                    <label class="form-label">Folder Name</label>
                    <input type="text" name="folder_name" class="form-input" placeholder="Enter folder name..." required autofocus>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('newFolderModal').classList.remove('active')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Folder</button>
            </div>
        </form>
    </div>
</div>

<script>
// Media data
var mediaItems = <?= json_encode(array_values($media)) ?>;
var currentIndex = 0;
var currentView = localStorage.getItem('mediaView') || 'grid';

// All functions defined first
function formatFileSize(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function setView(view) {
    currentView = view;
    localStorage.setItem('mediaView', view);
    
    document.querySelectorAll('.view-toggle-btn').forEach(function(btn) {
        btn.classList.toggle('active', btn.dataset.view === view);
    });
    
    var grid = document.getElementById('mediaGrid');
    var list = document.getElementById('mediaList');
    
    if (grid) grid.classList.toggle('active', view === 'grid');
    if (list) list.classList.toggle('active', view === 'list');
}

function openMediaModal(index) {
    currentIndex = index;
    
    var modal = document.getElementById('mediaModal');
    if (!modal) return;
    
    // Show modal
    modal.style.display = 'flex';
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // Update content
    var item = mediaItems[index];
    if (!item) return;
    
    var preview = document.getElementById('modalPreview');
    if (item.mime_type && item.mime_type.indexOf('image/') === 0) {
        preview.innerHTML = '<img src="' + item.url + '" alt="">';
    } else {
        var ext = item.filename ? item.filename.split('.').pop().toUpperCase() : 'FILE';
        preview.innerHTML = '<div class="media-modal-preview-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg><span>' + ext + '</span></div>';
    }
    
    document.getElementById('modalMediaId').value = item.id || '';
    document.getElementById('modalFilename').textContent = item.filename || '-';
    document.getElementById('modalType').textContent = item.mime_type || '-';
    document.getElementById('modalSize').textContent = formatFileSize(parseInt(item.filesize || 0));
    document.getElementById('modalTitle').value = item.title || '';
    document.getElementById('modalAlt').value = item.alt_text || '';
    document.getElementById('modalUrl').value = item.url || '';
    document.getElementById('modalDate').textContent = item.created_at ? new Date(item.created_at).toLocaleDateString('en-US', {year: 'numeric', month: 'short', day: 'numeric'}) : '-';
    
    var dimRow = document.getElementById('modalDimensionsRow');
    if (item.width && item.height) {
        document.getElementById('modalDimensions').textContent = item.width + ' × ' + item.height + ' px';
        dimRow.style.display = 'flex';
    } else {
        dimRow.style.display = 'none';
    }
    
    document.getElementById('modalCurrent').textContent = index + 1;
    document.getElementById('modalTotal').textContent = mediaItems.length;
    
    document.querySelector('.modal-nav.prev').disabled = index === 0;
    document.querySelector('.modal-nav.next').disabled = index === mediaItems.length - 1;
}

function closeMediaModal() {
    var modal = document.getElementById('mediaModal');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('active');
    }
    document.body.style.overflow = '';
}

function navigateMedia(direction) {
    var newIndex = currentIndex + direction;
    if (newIndex >= 0 && newIndex < mediaItems.length) {
        currentIndex = newIndex;
        openMediaModal(currentIndex);
    }
}

function copyUrl() {
    var url = document.getElementById('modalUrl');
    url.select();
    document.execCommand('copy');
    
    var btn = document.querySelector('.btn-copy-url');
    btn.classList.add('copied');
    btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>';
    
    setTimeout(function() {
        btn.classList.remove('copied');
        btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
    }, 1500);
}

function deleteMedia() {
    if (!confirm('Delete this file?')) return;
    document.getElementById('deleteMediaId').value = mediaItems[currentIndex].id;
    document.getElementById('deleteForm').submit();
}

// Initialize when DOM ready
document.addEventListener('DOMContentLoaded', function() {
    setView(currentView);
    
    // Grid items
    var gridItems = document.querySelectorAll('.media-item');
    for (var i = 0; i < gridItems.length; i++) {
        (function(idx) {
            gridItems[idx].addEventListener('click', function(e) {
                e.preventDefault();
                openMediaModal(idx);
            });
        })(i);
    }
    
    // List items
    var listItems = document.querySelectorAll('.media-list-item');
    for (var j = 0; j < listItems.length; j++) {
        (function(idx) {
            listItems[idx].addEventListener('click', function(e) {
                e.preventDefault();
                openMediaModal(idx);
            });
        })(j);
    }
    
    // Keyboard
    document.addEventListener('keydown', function(e) {
        var modal = document.getElementById('mediaModal');
        if (!modal || !modal.classList.contains('active')) return;
        if (e.key === 'Escape') closeMediaModal();
        if (e.key === 'ArrowLeft') navigateMedia(-1);
        if (e.key === 'ArrowRight') navigateMedia(1);
    });
    
    // Close on backdrop
    document.getElementById('mediaModal').addEventListener('click', function(e) {
        if (e.target === this) closeMediaModal();
    });
});

// Upload handling
var fileInput = document.getElementById('fileInput');
var uploadZone = document.getElementById('uploadZone');
var uploadForm = document.getElementById('uploadForm');

if (uploadZone) {
    uploadZone.addEventListener('click', function() { fileInput.click(); });
}
if (fileInput) {
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) uploadFiles(this.files);
    });
}
if (uploadZone) {
    uploadZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('dragover');
    });
    uploadZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
    });
    uploadZone.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
        if (e.dataTransfer.files.length > 0) uploadFiles(e.dataTransfer.files);
    });
}

function uploadFiles(files) {
    var formData = new FormData(uploadForm);
    for (var i = 0; i < files.length; i++) {
        formData.append('media_upload[]', files[i]);
    }
    uploadZone.innerHTML = '<p>Uploading...</p>';
    fetch('<?= ADMIN_URL ?>/media.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    }).then(function(r) {
        location.reload();
    }).catch(function(e) {
        alert('Upload failed');
        location.reload();
    });
}

function regenerateAllThumbnails() {
    document.querySelectorAll('.dropdown-menu').forEach(function(m) { m.classList.remove('show'); });
    if (!confirm('Regenerate thumbnails for all images?')) return;
    fetch('media.php?action=regenerate_thumbnails')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            alert(data.message || 'Done');
            location.reload();
        });
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown-menu').forEach(function(m) { m.classList.remove('show'); });
    }
});
</script>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
