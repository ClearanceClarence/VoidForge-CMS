<?php
/**
 * Media Library - Forge CMS v1.0.10
 * Single-click selection with slide-in panel
 */

// Debug endpoint - access with ?action=debug
if (isset($_GET['action']) && $_GET['action'] === 'debug') {
    header('Content-Type: application/json');
    define('CMS_ROOT', dirname(__DIR__));
    
    $debug = [
        'php_version' => PHP_VERSION,
        'php_sapi' => PHP_SAPI,
        'errors' => []
    ];
    
    try {
        require_once CMS_ROOT . '/includes/config.php';
        $debug['config'] = 'OK';
        $debug['db_prefix'] = defined('DB_PREFIX') ? DB_PREFIX : '(not set - using no prefix)';
    } catch (Throwable $e) {
        $debug['errors'][] = 'config.php: ' . $e->getMessage();
    }
    
    try {
        require_once CMS_ROOT . '/includes/database.php';
        $debug['database_class'] = 'OK';
        $debug['table_media'] = Database::table('media');
    } catch (Throwable $e) {
        $debug['errors'][] = 'database.php: ' . $e->getMessage();
    }
    
    try {
        require_once CMS_ROOT . '/includes/functions.php';
        $debug['functions'] = 'OK';
    } catch (Throwable $e) {
        $debug['errors'][] = 'functions.php: ' . $e->getMessage();
    }
    
    try {
        require_once CMS_ROOT . '/includes/user.php';
        $debug['user_class'] = 'OK';
    } catch (Throwable $e) {
        $debug['errors'][] = 'user.php: ' . $e->getMessage();
    }
    
    try {
        require_once CMS_ROOT . '/includes/post.php';
        $debug['post_class'] = 'OK';
    } catch (Throwable $e) {
        $debug['errors'][] = 'post.php: ' . $e->getMessage();
    }
    
    try {
        require_once CMS_ROOT . '/includes/media.php';
        $debug['media_class'] = 'OK';
    } catch (Throwable $e) {
        $debug['errors'][] = 'media.php: ' . $e->getMessage();
    }
    
    try {
        Database::getInstance();
        $debug['db_connection'] = 'OK';
    } catch (Throwable $e) {
        $debug['errors'][] = 'DB Connection: ' . $e->getMessage();
    }
    
    try {
        $table = Database::table('media');
        $count = Database::queryValue("SELECT COUNT(*) FROM {$table}");
        $debug['media_count'] = (int)$count;
    } catch (Throwable $e) {
        $debug['errors'][] = 'Media query: ' . $e->getMessage();
    }
    
    try {
        $media = Media::query(['type' => 'image', 'limit' => 1]);
        $debug['media_query'] = 'OK - found ' . count($media) . ' image(s)';
        if (!empty($media[0])) {
            $debug['sample_media'] = [
                'id' => $media[0]['id'],
                'filename' => $media[0]['filename'],
                'mime_type' => $media[0]['mime_type'] ?? 'NULL',
                'filepath' => $media[0]['filepath'] ?? 'NULL'
            ];
        }
    } catch (Throwable $e) {
        $debug['errors'][] = 'Media::query: ' . $e->getMessage();
    }
    
    echo json_encode($debug, JSON_PRETTY_PRINT);
    exit;
}

// Simple test endpoint - bypasses login to test media listing
// Access with ?action=test_list
if (isset($_GET['action']) && $_GET['action'] === 'test_list') {
    header('Content-Type: application/json');
    define('CMS_ROOT', dirname(__DIR__));
    
    // Catch ALL errors including fatal
    set_error_handler(function($severity, $message, $file, $line) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    });
    
    try {
        require_once CMS_ROOT . '/includes/config.php';
        require_once CMS_ROOT . '/includes/database.php';
        require_once CMS_ROOT . '/includes/functions.php';
        require_once CMS_ROOT . '/includes/media.php';
        
        $media = Media::query(['type' => 'image', 'limit' => 5]);
        
        // Process each item
        foreach ($media as &$item) {
            if (empty($item['url'])) {
                $item['url'] = Media::getUrl($item);
            }
            $item['thumbnail_url'] = $item['url'];
            if (!empty($item['mime_type']) && strpos($item['mime_type'], 'image/') === 0) {
                try {
                    $thumbUrl = Media::getThumbnailUrl($item, 'medium');
                    if ($thumbUrl) {
                        $item['thumbnail_url'] = $thumbUrl;
                    }
                } catch (Throwable $e) {
                    $item['thumb_error'] = $e->getMessage();
                }
            }
        }
        
        echo json_encode(['success' => true, 'media' => $media], JSON_PRETTY_PRINT);
    } catch (Throwable $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ], JSON_PRETTY_PRINT);
    }
    exit;
}

// Suppress errors for AJAX requests (they'll be caught and returned as JSON)
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

Post::init();

User::startSession();
User::requireLogin();

// AJAX endpoint for listing media (used by media selector modals)
if (isset($_GET['action']) && $_GET['action'] === 'list') {
    // Prevent any output before JSON
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    
    // Catch all errors
    set_error_handler(function($severity, $message, $file, $line) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    });
    
    try {
        $type = $_GET['type'] ?? null;
        $folderId = isset($_GET['folder_id']) ? (int)$_GET['folder_id'] : null;
        $search = $_GET['search'] ?? null;
        
        $args = [
            'orderby' => 'created_at',
            'order' => 'DESC',
        ];
        
        if ($type === 'image') {
            $args['type'] = 'image';
        }
        
        if ($folderId !== null) {
            $args['folder_id'] = $folderId;
        }
        
        if ($search) {
            $args['search'] = $search;
        }
        
        $media = Media::query($args);
        
        // Add thumbnail URLs if available
        foreach ($media as &$item) {
            // Ensure URL is set
            if (empty($item['url'])) {
                $item['url'] = Media::getUrl($item);
            }
            // Try to get thumbnail, fall back gracefully
            $item['thumbnail_url'] = $item['url'];
            if (!empty($item['mime_type']) && strpos($item['mime_type'], 'image/') === 0) {
                try {
                    $thumbUrl = Media::getThumbnailUrl($item, 'medium');
                    if ($thumbUrl) {
                        $item['thumbnail_url'] = $thumbUrl;
                    }
                } catch (Throwable $e) {
                    // Keep original URL as thumbnail
                }
            }
        }
        
        restore_error_handler();
        echo json_encode(['success' => true, 'media' => $media]);
    } catch (Throwable $e) {
        restore_error_handler();
        echo json_encode([
            'success' => false, 
            'error' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]);
    }
    exit;
}

// AJAX endpoint for regenerating thumbnails
if (isset($_GET['action']) && $_GET['action'] === 'regenerate_thumbnails') {
    header('Content-Type: application/json');
    
    try {
        $mediaId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($mediaId) {
            // Regenerate for single item
            $result = Media::regenerateThumbnails($mediaId);
            echo json_encode($result);
        } else {
            // Regenerate all
            $result = Media::regenerateAllThumbnails();
            echo json_encode([
                'success' => true,
                'regenerated' => $result['success'],
                'failed' => $result['failed'],
                'message' => "Regenerated thumbnails for {$result['success']} images" . ($result['failed'] > 0 ? ", {$result['failed']} failed" : "")
            ]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

$pageTitle = 'Media Library';

// Handle uploads
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['media_upload'])) {
        if (!verifyCsrf()) {
            setFlash('error', 'Invalid security token. Please try again.');
            redirect(ADMIN_URL . '/media.php');
        }
        $folderId = (int)($_POST['folder_id'] ?? 0);
        $currentUser = User::current();
        $userId = $currentUser ? $currentUser['id'] : null;
        
        $files = $_FILES['media_upload'];
        $uploaded = 0;
        $errors = [];
        
        // Handle both single and multiple file uploads
        if (is_array($files['name'])) {
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $files['name'][$i],
                        'type' => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'size' => $files['size'][$i],
                        'error' => $files['error'][$i],
                    ];
                    
                    $result = Media::upload($file, $userId, $folderId);
                    if ($result['success']) {
                        $uploaded++;
                    } else {
                        $errors[] = $files['name'][$i] . ': ' . ($result['error'] ?? 'Unknown error');
                    }
                }
            }
        } else {
            // Single file upload
            if ($files['error'] === UPLOAD_ERR_OK) {
                $result = Media::upload($files, $userId, $folderId);
                if ($result['success']) {
                    $uploaded++;
                } else {
                    $errors[] = $result['error'] ?? 'Unknown error';
                }
            }
        }
        
        if ($uploaded > 0) {
            setFlash('success', $uploaded . ' file(s) uploaded successfully.');
        } elseif (!empty($errors)) {
            setFlash('error', 'Upload failed: ' . implode(', ', $errors));
        } else {
            setFlash('error', 'No files were uploaded. Please check file size and type.');
        }
        redirect(ADMIN_URL . '/media.php');
    }
    
    // Handle media update
    if (isset($_POST['action']) && $_POST['action'] === 'update') {
        if (!verifyCsrf()) {
            setFlash('error', 'Invalid security token.');
            redirect(ADMIN_URL . '/media.php');
        }
        $mediaId = (int)$_POST['media_id'];
        Media::update($mediaId, [
            'title' => $_POST['title'] ?? '',
            'alt_text' => $_POST['alt_text'] ?? '',
        ]);
        setFlash('success', 'Media updated successfully.');
        redirect(ADMIN_URL . '/media.php');
    }
    
    // Handle media delete
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        if (!verifyCsrf()) {
            setFlash('error', 'Invalid security token.');
            redirect(ADMIN_URL . '/media.php');
        }
        $mediaId = (int)$_POST['media_id'];
        Media::delete($mediaId);
        setFlash('success', 'Media deleted successfully.');
        redirect(ADMIN_URL . '/media.php');
    }
    
    // Handle folder creation
    if (isset($_POST['action']) && $_POST['action'] === 'create_folder') {
        if (!verifyCsrf()) {
            setFlash('error', 'Invalid security token.');
            redirect(ADMIN_URL . '/media.php');
        }
        $folderName = trim($_POST['folder_name'] ?? '');
        if ($folderName) {
            Media::createFolder($folderName);
            setFlash('success', 'Folder created successfully.');
        }
        redirect(ADMIN_URL . '/media.php');
    }
    
    // Handle bulk regenerate thumbnails
    if (isset($_POST['action']) && $_POST['action'] === 'regenerate_all_thumbnails') {
        if (!verifyCsrf()) {
            setFlash('error', 'Invalid security token.');
            redirect(ADMIN_URL . '/media.php');
        }
        $result = Media::regenerateAllThumbnails();
        setFlash('success', "Regenerated thumbnails for {$result['success']} images" . ($result['failed'] > 0 ? " ({$result['failed']} failed)" : ""));
        redirect(ADMIN_URL . '/media.php');
    }
}

// Get current folder
$currentFolder = isset($_GET['folder']) ? (int)$_GET['folder'] : null;

// Get folders and media
$folders = Media::getFolders();
$media = Media::query(['folder_id' => $currentFolder, 'limit' => 100]);
$mediaCount = count($media);

include ADMIN_PATH . '/includes/header.php';
?>

<div class="media-layout">
    <div class="media-main" id="mediaMain">
        <div class="media-header">
            <div class="media-header-left">
                <h2>Media Library</h2>
                <span class="media-count"><?= $mediaCount ?> items</span>
            </div>
            <div class="d-flex gap-1">
                <div class="dropdown" style="position: relative;">
                    <button type="button" class="btn btn-secondary" onclick="this.nextElementSibling.classList.toggle('show')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="1"></circle>
                            <circle cx="12" cy="5" r="1"></circle>
                            <circle cx="12" cy="19" r="1"></circle>
                        </svg>
                    </button>
                    <div class="dropdown-menu" style="position: absolute; right: 0; top: 100%; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--border-radius); padding: 0.25rem 0; min-width: 200px; z-index: 100; display: none; box-shadow: var(--shadow-lg);">
                        <button type="button" class="dropdown-item" onclick="regenerateAllThumbnails()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="23 4 23 10 17 10"></polyline>
                                <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                            </svg>
                            Regenerate All Thumbnails
                        </button>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('newFolderModal').classList.add('active')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                        <line x1="12" y1="11" x2="12" y2="17"></line>
                        <line x1="9" y1="14" x2="15" y2="14"></line>
                    </svg>
                    New Folder
                </button>
                <label class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    Upload
                    <input type="file" id="fileInput" name="media_upload[]" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx" hidden>
                </label>
            </div>
        </div>

        <div class="media-upload-zone" id="uploadZone" onclick="document.getElementById('fileInput').click()" style="cursor: pointer;">
            <div class="media-upload-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
            </div>
            <h3>Drop files here to upload</h3>
            <p>or click anywhere in this area</p>
        </div>

        <?php if (!empty($folders) || $currentFolder): ?>
        <div class="media-folders">
            <a href="<?= ADMIN_URL ?>/media.php" class="media-folder <?= $currentFolder === null ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                </svg>
                All Files
            </a>
            <?php foreach ($folders as $folder): ?>
            <a href="<?= ADMIN_URL ?>/media.php?folder=<?= $folder['id'] ?>" class="media-folder <?= $currentFolder === (int)$folder['id'] ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                </svg>
                <?= esc($folder['name']) ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (empty($media)): ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                <polyline points="21 15 16 10 5 21"></polyline>
            </svg>
            <h3>No media files</h3>
            <p>Upload your first file to get started.</p>
        </div>
        <?php else: ?>
        <div class="media-grid" id="mediaGrid">
            <?php foreach ($media as $item): ?>
            <div class="media-item" 
                 data-id="<?= $item['id'] ?>"
                 data-filename="<?= esc($item['filename']) ?>"
                 data-title="<?= esc($item['title'] ?? '') ?>"
                 data-alt="<?= esc($item['alt_text'] ?? '') ?>"
                 data-url="<?= esc($item['url']) ?>"
                 data-mime="<?= esc($item['mime_type']) ?>"
                 data-size="<?= $item['filesize'] ?? 0 ?>"
                 data-width="<?= $item['width'] ?? '' ?>"
                 data-height="<?= $item['height'] ?? '' ?>"
                 data-date="<?= $item['created_at'] ?>"
                 onclick="selectMedia(this)">
                <div class="media-item-inner">
                    <?php if (strpos($item['mime_type'], 'image/') === 0): ?>
                    <img src="<?= esc($item['url']) ?>" alt="<?= esc($item['alt_text'] ?? $item['filename']) ?>" loading="lazy">
                    <?php else: ?>
                    <div class="media-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                        <span><?= strtoupper(pathinfo($item['filename'], PATHINFO_EXTENSION)) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="media-item-check">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </div>
                <div class="media-item-name"><?= esc($item['filename']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="media-panel" id="mediaPanel">
        <div class="media-panel-header">
            <div class="media-panel-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                    <polyline points="21 15 16 10 5 21"></polyline>
                </svg>
                File Details
            </div>
            <button type="button" class="media-panel-close" onclick="closePanel()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        
        <div class="media-panel-preview">
            <div class="media-panel-preview-inner" id="panelPreview"></div>
        </div>
        
        <div class="media-panel-body">
            <form id="mediaEditForm" method="post">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="media_id" id="panelMediaId">
                
                <div class="media-panel-section">
                    <div class="media-panel-section-title">File Information</div>
                    <div class="media-panel-info">
                        <div class="media-panel-info-row">
                            <span class="media-panel-info-label">Filename</span>
                            <span class="media-panel-info-value" id="panelFilename">-</span>
                        </div>
                        <div class="media-panel-info-row">
                            <span class="media-panel-info-label">Type</span>
                            <span class="media-panel-info-value" id="panelType">-</span>
                        </div>
                        <div class="media-panel-info-row">
                            <span class="media-panel-info-label">Size</span>
                            <span class="media-panel-info-value" id="panelSize">-</span>
                        </div>
                        <div class="media-panel-info-row" id="dimensionsRow">
                            <span class="media-panel-info-label">Dimensions</span>
                            <span class="media-panel-info-value" id="panelDimensions">-</span>
                        </div>
                        <div class="media-panel-info-row">
                            <span class="media-panel-info-label">Uploaded</span>
                            <span class="media-panel-info-value" id="panelDate">-</span>
                        </div>
                    </div>
                </div>
                
                <div class="media-panel-section">
                    <div class="media-panel-section-title">Edit Details</div>
                    <div class="form-group">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" id="panelTitle" class="form-input" placeholder="Enter title...">
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label">Alt Text</label>
                        <input type="text" name="alt_text" id="panelAlt" class="form-input" placeholder="Describe the image...">
                        <p class="form-hint">Describe the image for accessibility.</p>
                    </div>
                </div>
                
                <div class="media-panel-section">
                    <div class="media-panel-section-title">File URL</div>
                    <div class="d-flex gap-1">
                        <input type="text" id="panelUrl" class="form-input" readonly>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="copyUrl()" title="Copy URL">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="media-panel-footer">
            <div class="media-panel-footer-left">
                <button type="button" class="btn btn-danger" onclick="deleteMedia()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                    Delete
                </button>
                <button type="button" class="btn btn-secondary" id="btnRegenerateThumbs" onclick="regenerateThumbnails()" style="display: none;" title="Regenerate thumbnails for this image">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 4 23 10 17 10"></polyline>
                        <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                    </svg>
                    Thumbnails
                </button>
            </div>
            <button type="submit" form="mediaEditForm" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                Save Changes
            </button>
        </div>
    </div>
</div>

<form id="uploadForm" method="post" enctype="multipart/form-data" hidden>
    <?= csrfField() ?>
    <input type="hidden" name="folder_id" value="<?= $currentFolder ?? '' ?>">
</form>

<form id="deleteForm" method="post" hidden>
    <?= csrfField() ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="media_id" id="deleteMediaId">
</form>

<div class="modal-backdrop" id="newFolderModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Create New Folder</h3>
            <button type="button" class="modal-close" onclick="document.getElementById('newFolderModal').classList.remove('active')">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
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
let selectedMedia = null;
const panel = document.getElementById('mediaPanel');
const mainArea = document.getElementById('mediaMain');

function selectMedia(element) {
    document.querySelectorAll('.media-item.selected').forEach(el => el.classList.remove('selected'));
    element.classList.add('selected');
    selectedMedia = element;
    
    const data = element.dataset;
    document.getElementById('panelMediaId').value = data.id;
    document.getElementById('panelFilename').textContent = data.filename;
    document.getElementById('panelType').textContent = data.mime;
    document.getElementById('panelSize').textContent = formatFileSize(parseInt(data.size));
    document.getElementById('panelTitle').value = data.title;
    document.getElementById('panelAlt').value = data.alt;
    document.getElementById('panelUrl').value = data.url;
    document.getElementById('panelDate').textContent = new Date(data.date).toLocaleDateString('en-US', {
        year: 'numeric', month: 'short', day: 'numeric'
    });
    
    const dimensionsRow = document.getElementById('dimensionsRow');
    if (data.width && data.height) {
        document.getElementById('panelDimensions').textContent = data.width + ' × ' + data.height + ' px';
        dimensionsRow.style.display = 'flex';
    } else {
        dimensionsRow.style.display = 'none';
    }
    
    const preview = document.getElementById('panelPreview');
    const regenBtn = document.getElementById('btnRegenerateThumbs');
    
    if (data.mime.startsWith('image/') && !data.mime.includes('svg')) {
        preview.innerHTML = '<img src="' + data.url + '" alt="">';
        regenBtn.style.display = 'inline-flex';
    } else {
        preview.innerHTML = '<div class="media-panel-preview-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg><span>' + data.filename.split('.').pop().toUpperCase() + '</span></div>';
        regenBtn.style.display = 'none';
    }
    
    openPanel();
}

function openPanel() {
    panel.classList.add('open');
    mainArea.classList.add('panel-open');
}

function closePanel() {
    panel.classList.remove('open');
    mainArea.classList.remove('panel-open');
    document.querySelectorAll('.media-item.selected').forEach(el => el.classList.remove('selected'));
    selectedMedia = null;
}

function copyUrl() {
    const url = document.getElementById('panelUrl');
    url.select();
    document.execCommand('copy');
    const btn = event.target.closest('button');
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>';
    setTimeout(() => btn.innerHTML = originalHTML, 1500);
}

function deleteMedia() {
    if (!selectedMedia) return;
    if (confirm('Are you sure you want to delete this file?')) {
        document.getElementById('deleteMediaId').value = selectedMedia.dataset.id;
        document.getElementById('deleteForm').submit();
    }
}

async function regenerateThumbnails() {
    if (!selectedMedia) return;
    
    const btn = document.getElementById('btnRegenerateThumbs');
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;"><polyline points="23 4 23 10 17 10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg> Regenerating...';
    btn.disabled = true;
    
    try {
        const res = await fetch('media.php?action=regenerate_thumbnails&id=' + selectedMedia.dataset.id);
        const result = await res.json();
        
        if (result.success) {
            // Flash success
            btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg> Done!';
            setTimeout(() => {
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            }, 1500);
        } else {
            alert('Error: ' + (result.error || 'Failed to regenerate thumbnails'));
            btn.innerHTML = originalHTML;
            btn.disabled = false;
        }
    } catch (e) {
        alert('Error: ' + e.message);
        btn.innerHTML = originalHTML;
        btn.disabled = false;
    }
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

const fileInput = document.getElementById('fileInput');
const uploadZone = document.getElementById('uploadZone');
const uploadForm = document.getElementById('uploadForm');

fileInput.addEventListener('change', function() {
    if (this.files.length > 0) uploadFiles(this.files);
});

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

function uploadFiles(files) {
    const formData = new FormData(uploadForm);
    for (let i = 0; i < files.length; i++) {
        formData.append('media_upload[]', files[i]);
    }
    uploadZone.innerHTML = '<div class="animate-pulse"><p>Uploading...</p></div>';
    fetch('<?= ADMIN_URL ?>/media.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    }).then(response => {
        if (response.ok) {
            window.location.reload();
        } else {
            alert('Upload failed. Please try again.');
            window.location.reload();
        }
    }).catch(error => {
        console.error('Upload error:', error);
        alert('Upload failed. Please try again.');
        window.location.reload();
    });
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && panel.classList.contains('open')) closePanel();
});

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('show'));
    }
});

// Regenerate all thumbnails
async function regenerateAllThumbnails() {
    document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('show'));
    
    if (!confirm('This will regenerate thumbnails for all images. This may take a while. Continue?')) {
        return;
    }
    
    // Show loading state
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;"><polyline points="23 4 23 10 17 10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg> Processing...';
    btn.disabled = true;
    
    try {
        const res = await fetch('media.php?action=regenerate_thumbnails');
        const data = await res.json();
        
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
        }
    } catch (e) {
        alert('Failed to regenerate thumbnails: ' + e.message);
    }
    
    btn.innerHTML = originalText;
    btn.disabled = false;
}
</script>

<style>
.dropdown-menu.show { display: block !important; }
.dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    width: 100%;
    border: none;
    background: none;
    color: var(--text-primary);
    font-size: 0.875rem;
    cursor: pointer;
    text-align: left;
}
.dropdown-item:hover { background: var(--bg-card-header); }
.media-panel-footer { display: flex; justify-content: space-between; align-items: center; }
.media-panel-footer-left { display: flex; gap: 0.5rem; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
