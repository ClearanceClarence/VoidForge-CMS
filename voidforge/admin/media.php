<?php
/**
 * Media Library - VoidForge CMS
 * Modern masonry-style grid with elegant design
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

// AJAX: Move media to folder
if (isset($_GET['action']) && $_GET['action'] === 'move_to_folder') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    try {
        $mediaId = (int)($_POST['media_id'] ?? 0);
        $folderId = isset($_POST['folder_id']) && $_POST['folder_id'] !== '' ? (int)$_POST['folder_id'] : null;
        
        if (!$mediaId) {
            echo json_encode(['success' => false, 'error' => 'No media ID provided']);
            exit;
        }
        
        $result = Media::moveToFolder($mediaId, $folderId);
        echo json_encode($result);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Get folder tree
if (isset($_GET['action']) && $_GET['action'] === 'folder_tree') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    try {
        $folders = Media::getFolderTree();
        echo json_encode(['success' => true, 'folders' => $folders]);
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
        if (!verifyCsrf()) {
            echo json_encode(['success' => false, 'error' => 'Invalid security token']);
            exit;
        }
        
        if (empty($_FILES['file'])) {
            echo json_encode(['success' => false, 'error' => 'No file uploaded']);
            exit;
        }
        
        $file = $_FILES['file'];
        
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
        
        $folderId = isset($_POST['folder_id']) && $_POST['folder_id'] !== '' ? (int)$_POST['folder_id'] : 0;
        $userId = User::current()['id'] ?? null;
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
        $userId = User::current()['id'] ?? null;
        $files = $_FILES['media_upload'];
        $uploaded = 0;
        
        if (is_array($files['name'])) {
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $file = ['name' => $files['name'][$i], 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i]];
                    if (Media::upload($file, $userId, $folderId ?: 0)) $uploaded++;
                }
            }
        } else if ($files['error'] === UPLOAD_ERR_OK) {
            if (Media::upload($files, $userId, $folderId ?: 0)) $uploaded++;
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
        $name = trim($_POST['folder_name'] ?? '');
        $parentId = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? (int)$_POST['parent_id'] : null;
        if ($name) {
            $result = Media::createFolder($name, $parentId);
            if ($result['success']) {
                setFlash('success', 'Folder created.');
            } else {
                setFlash('error', $result['error'] ?? 'Failed to create folder.');
            }
        }
        $redirectUrl = ADMIN_URL . '/media.php';
        if ($parentId) $redirectUrl .= '?folder=' . $parentId;
        redirect($redirectUrl);
    }
    
    // Delete folder
    if (isset($_POST['action']) && $_POST['action'] === 'delete_folder') {
        $folderId = (int)($_POST['folder_id'] ?? 0);
        if ($folderId) {
            $result = Media::deleteFolder($folderId);
            if ($result['success']) {
                setFlash('success', 'Folder deleted. Files moved to parent folder.');
            }
        }
        redirect(ADMIN_URL . '/media.php');
    }
    
    // Save sidebar settings
    if (isset($_POST['action']) && $_POST['action'] === 'save_sidebar_settings') {
        $width = (int)($_POST['sidebar_width'] ?? 280);
        if ($width >= 200 && $width <= 500) {
            setOption('media_sidebar_width', $width);
            setFlash('success', 'Sidebar settings saved.');
        }
        redirect(ADMIN_URL . '/media.php');
    }
}

$pageTitle = 'Media Library';
$currentPage = 'media';

// Get filter
$currentFolder = isset($_GET['folder']) ? (int)$_GET['folder'] : null;
$typeFilter = $_GET['type'] ?? 'all';
$search = $_GET['search'] ?? '';

// Query media
$args = ['orderby' => 'created_at', 'order' => 'DESC'];
if ($currentFolder) $args['folder_id'] = $currentFolder;
if ($typeFilter === 'image') $args['type'] = 'image';
if ($search) $args['search'] = $search;

$media = Media::query($args);

// Get folders as tree (with fallback for missing parent_id column)
$folderTree = [];
try {
    // First check if parent_id column exists
    $table = Database::table('media_folders');
    $mediaTable = Database::table('media');
    $hasParentId = false;
    
    try {
        $columns = Database::query("SHOW COLUMNS FROM {$table} LIKE 'parent_id'");
        $hasParentId = !empty($columns);
    } catch (Throwable $e) {}
    
    if ($hasParentId) {
        $folderTree = Media::getFolderTree();
    } else {
        // Fallback: flat list
        $folders = Database::query(
            "SELECT f.*, (SELECT COUNT(*) FROM {$mediaTable} WHERE folder_id = f.id) as count 
             FROM {$table} f ORDER BY f.name"
        );
        foreach ($folders as &$folder) {
            $folder['children'] = [];
        }
        $folderTree = $folders;
    }
} catch (Throwable $e) {
    // Table might not exist yet
    $folderTree = [];
}

// Get current folder info and breadcrumb
$currentFolderInfo = null;
$folderBreadcrumb = [];
if ($currentFolder) {
    try {
        $table = Database::table('media_folders');
        $currentFolderInfo = Database::queryOne("SELECT * FROM {$table} WHERE id = ?", [$currentFolder]);
        
        // Build breadcrumb (only if parent_id exists)
        if ($currentFolderInfo && isset($currentFolderInfo['parent_id'])) {
            $folderBreadcrumb[] = $currentFolderInfo;
            $parentId = $currentFolderInfo['parent_id'] ?? null;
            while ($parentId) {
                $parent = Database::queryOne("SELECT * FROM {$table} WHERE id = ?", [$parentId]);
                if ($parent) {
                    array_unshift($folderBreadcrumb, $parent);
                    $parentId = $parent['parent_id'] ?? null;
                } else {
                    break;
                }
            }
        }
    } catch (Throwable $e) {}
}

// Get sidebar width setting
$sidebarWidth = (int)(getOption('media_sidebar_width', 280));
if ($sidebarWidth < 200) $sidebarWidth = 200;
if ($sidebarWidth > 500) $sidebarWidth = 500;

// Count by type
$totalCount = Media::count();
$imageCount = Media::count(['type' => 'image']);
$otherCount = $totalCount - $imageCount;

include ADMIN_PATH . '/includes/header.php';
?>

<style>
/* Page Layout */
.media-page {
    max-width: 1600px;
    margin: 0 auto;
}

/* Media Layout with Sidebar */
.media-layout {
    display: grid;
    grid-template-columns: <?= $sidebarWidth ?>px 1fr;
    gap: 1.5rem;
    align-items: start;
}

@media (max-width: 900px) {
    .media-layout {
        grid-template-columns: 1fr;
    }
    .folder-sidebar {
        display: none;
    }
}

/* Folder Sidebar */
.folder-sidebar {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    overflow: hidden;
    position: sticky;
    top: 1rem;
    max-height: calc(100vh - 2rem);
    display: flex;
    flex-direction: column;
}

.folder-sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    background: var(--bg-card-header);
    border-bottom: 1px solid var(--border-color);
}

.folder-sidebar-header h3 {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.folder-sidebar-header h3 svg {
    width: 16px;
    height: 16px;
    color: var(--forge-primary);
}

.folder-header-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-folder-action {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--forge-primary-bg);
    color: var(--forge-primary);
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-folder-action:hover {
    background: var(--forge-primary);
    color: #fff;
}

.btn-folder-action svg {
    width: 14px;
    height: 14px;
}

.folder-list {
    padding: 0.5rem;
    overflow-y: auto;
    flex: 1;
}

.folder-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.15s;
    color: var(--text-secondary);
    text-decoration: none;
    position: relative;
    border: 2px solid transparent;
}

.folder-item:hover {
    background: var(--bg-card-header);
    color: var(--text-primary);
}

.folder-item.active {
    background: var(--forge-primary-bg);
    color: var(--forge-primary);
}

.folder-item.drop-target {
    border-color: var(--forge-primary);
    background: var(--forge-primary-bg);
}

.folder-item svg.folder-icon {
    width: 18px;
    height: 18px;
    flex-shrink: 0;
}

.folder-item-toggle {
    width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    cursor: pointer;
    color: var(--text-muted);
    transition: transform 0.2s;
}

.folder-item-toggle:hover {
    color: var(--text-primary);
}

.folder-item-toggle.expanded {
    transform: rotate(90deg);
}

.folder-item-toggle svg {
    width: 12px;
    height: 12px;
}

.folder-item-toggle.hidden {
    visibility: hidden;
}

.folder-item-name {
    flex: 1;
    font-size: 0.8125rem;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.folder-item-count {
    font-size: 0.6875rem;
    padding: 0.125rem 0.5rem;
    background: var(--border-color);
    border-radius: 100px;
    color: var(--text-muted);
    flex-shrink: 0;
    margin-right: 0.25rem;
}

.folder-item.active .folder-item-count {
    background: var(--forge-primary);
    color: #fff;
}

.folder-item-delete {
    width: 20px;
    height: 20px;
    display: none;
    align-items: center;
    justify-content: center;
    background: #ef4444;
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    flex-shrink: 0;
    opacity: 0;
    transition: opacity 0.15s;
}

.folder-item:hover .folder-item-delete {
    display: flex;
    opacity: 1;
}

.folder-item-delete svg {
    width: 12px;
    height: 12px;
}

/* New Folder Form */
.new-folder-form {
    display: none;
    padding: 0.75rem;
    border-top: 1px solid var(--border-color);
    background: var(--bg-card-header);
}

.new-folder-form.active {
    display: block;
}

.new-folder-input-wrap {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.new-folder-input-wrap input {
    flex: 1;
    min-width: 100px;
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    font-size: 0.8125rem;
    background: var(--bg-card);
    color: var(--text-primary);
}

.new-folder-input-wrap input:focus {
    outline: none;
    border-color: var(--forge-primary);
}

.btn-create-folder {
    padding: 0.5rem 0.75rem;
    background: var(--forge-primary);
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
}

.btn-cancel-folder {
    padding: 0.5rem 0.75rem;
    background: var(--bg-card);
    color: var(--text-muted);
    border: 1px solid var(--border-color);
    border-radius: 6px;
    font-size: 0.75rem;
    cursor: pointer;
}

/* Subfolder Styles */
.folder-children {
    display: none;
    margin-left: 0.5rem;
    padding-left: 0.75rem;
    border-left: 1px solid var(--border-color);
}

.folder-children.expanded {
    display: block;
}

/* Sidebar Settings Modal */
.sidebar-settings-modal {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.sidebar-settings-modal.active {
    display: flex;
}

.sidebar-settings-content {
    background: var(--bg-card);
    border-radius: 12px;
    padding: 1.5rem;
    width: 320px;
    max-width: 90vw;
}

.sidebar-settings-content h4 {
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 1rem;
    color: var(--text-primary);
}

.sidebar-settings-field {
    margin-bottom: 1rem;
}

.sidebar-settings-field label {
    display: block;
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--text-secondary);
    margin-bottom: 0.5rem;
}

.sidebar-settings-field input[type="range"] {
    width: 100%;
}

.sidebar-settings-field .range-value {
    text-align: center;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--forge-primary);
    margin-top: 0.25rem;
}

.sidebar-settings-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

.sidebar-settings-actions button {
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.8125rem;
    font-weight: 600;
    cursor: pointer;
}

.btn-settings-cancel {
    background: var(--bg-card-header);
    border: 1px solid var(--border-color);
    color: var(--text-primary);
}

.btn-settings-save {
    background: var(--forge-primary);
    border: none;
    color: #fff;
}

/* Drag and Drop Styles */
.media-card.drag-ghost {
    position: fixed;
    pointer-events: none;
    z-index: 9999;
    opacity: 0.8;
    transform: rotate(3deg);
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.folder-item.drag-over {
    background: var(--forge-primary-bg);
    border-color: var(--forge-primary);
}

/* Media Content Area */
.media-content {
    min-width: 0;
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

.header-actions {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

/* Upload Button */
.btn-upload {
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
    transition: all 0.2s ease;
}

.btn-upload:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px var(--forge-shadow-color);
}

.btn-upload svg {
    width: 18px;
    height: 18px;
}

/* Toolbar */
.media-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.toolbar-left {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.toolbar-right {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

/* Filter Tabs */
.filter-tabs {
    display: flex;
    gap: 0.25rem;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    padding: 0.25rem;
}

.filter-tab {
    padding: 0.5rem 1rem;
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--text-muted);
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.15s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-tab:hover {
    color: var(--text-primary);
    background: var(--bg-card-header);
}

.filter-tab.active {
    color: #fff;
    background: linear-gradient(135deg, var(--forge-primary) 0%, var(--forge-secondary) 100%);
}

.filter-tab .count {
    font-size: 0.6875rem;
    padding: 0.125rem 0.375rem;
    background: rgba(255,255,255,0.2);
    border-radius: 4px;
}

.filter-tab:not(.active) .count {
    background: var(--border-color);
}

/* Search */
.search-box {
    position: relative;
}

.search-box input {
    width: 240px;
    padding: 0.625rem 1rem 0.625rem 2.5rem;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    font-size: 0.875rem;
    color: var(--text-primary);
    transition: all 0.15s;
}

.search-box input:focus {
    outline: none;
    border-color: var(--forge-primary);
    box-shadow: 0 0 0 3px var(--forge-primary-bg);
}

.search-box svg {
    position: absolute;
    left: 0.875rem;
    top: 50%;
    transform: translateY(-50%);
    width: 16px;
    height: 16px;
    color: var(--text-muted);
}

/* View Toggle */
.view-toggle {
    display: flex;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
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

.view-btn:hover {
    color: var(--text-primary);
}

.view-btn.active {
    background: var(--forge-primary);
    color: #fff;
}

.view-btn svg {
    width: 18px;
    height: 18px;
}

/* ===== MODERN MEDIA GRID ===== */
.media-grid-container {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 1.5rem;
    min-height: 400px;
}

.media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}

.media-grid.active {
    display: grid;
}

.media-grid:not(.active) {
    display: none;
}

/* Media Card */
.media-card {
    position: relative;
    background: var(--bg-card-header);
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    border: 2px solid transparent;
    aspect-ratio: 1;
    user-select: none;
}

.media-card img {
    -webkit-user-drag: none;
    user-drag: none;
}

.media-card.dragging {
    opacity: 0.5;
    transform: scale(0.95);
    cursor: grabbing;
}

.media-card::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, transparent 50%, rgba(0,0,0,0.7) 100%);
    opacity: 0;
    transition: opacity 0.25s;
    z-index: 1;
    pointer-events: none;
}

.media-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15);
    border-color: var(--forge-primary);
}

.media-card:hover::before {
    opacity: 1;
}

.media-card.selected {
    border-color: var(--forge-primary);
    box-shadow: 0 0 0 3px var(--forge-primary-bg);
}

/* Media Thumbnail */
.media-thumb {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.media-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.media-card:hover .media-thumb img {
    transform: scale(1.05);
}

/* File Icon */
.media-file-icon {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    padding: 1.5rem;
    height: 100%;
}

.media-file-icon svg {
    width: 48px;
    height: 48px;
    color: var(--text-dim);
}

.media-file-ext {
    display: inline-flex;
    padding: 0.25rem 0.625rem;
    background: var(--border-color);
    border-radius: 6px;
    font-size: 0.6875rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
}

/* Media Info Overlay */
.media-info {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 1rem;
    z-index: 2;
    opacity: 0;
    transform: translateY(8px);
    transition: all 0.25s;
}

.media-card:hover .media-info {
    opacity: 1;
    transform: translateY(0);
}

.media-name {
    color: #fff;
    font-size: 0.8125rem;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 0.25rem;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
}

.media-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.6875rem;
    color: rgba(255,255,255,0.8);
}

/* Selection Check */
.media-check {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    width: 28px;
    height: 28px;
    background: rgba(255,255,255,0.95);
    border: 2px solid var(--border-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 3;
    opacity: 0;
    transform: scale(0.8);
    transition: all 0.2s;
}

.media-card:hover .media-check {
    opacity: 1;
    transform: scale(1);
}

.media-card.selected .media-check {
    opacity: 1;
    transform: scale(1);
    background: var(--forge-primary);
    border-color: var(--forge-primary);
    color: #fff;
}

.media-check svg {
    width: 14px;
    height: 14px;
}

/* Quick Delete Button */
.media-quick-delete {
    position: absolute;
    top: 0.75rem;
    left: 0.75rem;
    width: 28px;
    height: 28px;
    background: rgba(239, 68, 68, 0.9);
    border: none;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 3;
    opacity: 0;
    transform: scale(0.8);
    transition: all 0.2s;
    cursor: pointer;
    color: #fff;
}

.media-quick-delete:hover {
    background: #dc2626;
    transform: scale(1.1) !important;
}

.media-card:hover .media-quick-delete {
    opacity: 1;
    transform: scale(1);
}

.media-quick-delete svg {
    width: 14px;
    height: 14px;
}

/* ===== LIST VIEW ===== */
.media-list {
    display: none;
    flex-direction: column;
    gap: 0.5rem;
}

.media-list.active {
    display: flex;
}

.media-list-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.875rem 1rem;
    background: var(--bg-card-header);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.15s;
    user-select: none;
}

.media-list-item:hover {
    border-color: var(--forge-primary);
    background: var(--bg-card);
}

.media-list-item.selected {
    border-color: var(--forge-primary);
    background: var(--forge-primary-bg);
}

.media-list-item.dragging {
    opacity: 0.5;
    background: var(--forge-primary-bg);
}

.media-list-item img {
    -webkit-user-drag: none;
    user-drag: none;
}

.media-list-thumb {
    width: 56px;
    height: 56px;
    border-radius: 10px;
    overflow: hidden;
    flex-shrink: 0;
    background: var(--bg-card);
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--border-color);
}

.media-list-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.media-list-thumb svg {
    width: 24px;
    height: 24px;
    color: var(--text-muted);
}

.media-list-info {
    flex: 1;
    min-width: 0;
}

.media-list-name {
    font-weight: 600;
    font-size: 0.9375rem;
    color: var(--text-primary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 0.25rem;
}

.media-list-details {
    display: flex;
    gap: 1rem;
    font-size: 0.8125rem;
    color: var(--text-muted);
}

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
}

.media-list-item.selected .media-list-check {
    background: var(--forge-primary);
    border-color: var(--forge-primary);
    color: #fff;
}

.media-list-check svg {
    width: 14px;
    height: 14px;
}

/* List View Delete Button */
.media-list-delete {
    width: 32px;
    height: 32px;
    background: transparent;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    cursor: pointer;
    color: var(--text-muted);
    transition: all 0.15s;
    opacity: 0;
    margin-right: 0.5rem;
}

.media-list-item:hover .media-list-delete {
    opacity: 1;
}

.media-list-delete:hover {
    background: #fef2f2;
    border-color: #fecaca;
    color: #ef4444;
}

.media-list-delete svg {
    width: 16px;
    height: 16px;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 5rem 2rem;
}

.empty-state-icon {
    width: 100px;
    height: 100px;
    margin: 0 auto 1.5rem;
    background: linear-gradient(135deg, var(--forge-primary) 0%, var(--forge-secondary) 100%);
    border-radius: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.empty-state-icon svg {
    width: 48px;
    height: 48px;
    color: #fff;
}

.empty-state h3 {
    font-size: 1.375rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 0.5rem;
}

.empty-state p {
    font-size: 0.9375rem;
    color: var(--text-muted);
    margin: 0 0 1.5rem;
}

/* Upload Zone */
/* Upload Zone */
.upload-zone-inline {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border: 2px dashed var(--border-color);
    border-radius: 12px;
    padding: 2.5rem 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    background: var(--bg-card);
    margin-bottom: 1.5rem;
}

.upload-zone-inline:hover,
.upload-zone-inline.dragover {
    border-color: var(--forge-primary);
    background: rgba(99, 102, 241, 0.05);
}

.upload-zone-inline svg {
    width: 48px;
    height: 48px;
    color: var(--forge-primary);
    margin-bottom: 1rem;
    display: block;
}

.upload-zone-inline h4 {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 0.375rem 0;
}

.upload-zone-inline p {
    font-size: 0.875rem;
    color: var(--text-muted);
    margin: 0;
}

/* ===== MODAL ===== */
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

.media-modal-backdrop.active {
    display: flex;
}

.media-modal {
    display: grid;
    grid-template-columns: 1fr 420px;
    width: 100%;
    max-width: 1200px;
    max-height: 85vh;
    background: var(--bg-card);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5);
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
    max-height: 75vh;
    object-fit: contain;
}

.media-modal-preview-icon {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    color: #64748b;
}

.media-modal-preview-icon svg {
    width: 80px;
    height: 80px;
}

.media-modal-preview-icon span {
    font-size: 1.5rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
}

/* Modal Navigation */
.modal-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 52px;
    height: 52px;
    background: rgba(255, 255, 255, 0.1);
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

.modal-nav:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-50%) scale(1.1);
}

.modal-nav:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

.modal-nav:disabled:hover {
    transform: translateY(-50%);
    background: rgba(255, 255, 255, 0.1);
}

.modal-nav.prev {
    left: 1.5rem;
}

.modal-nav.next {
    right: 1.5rem;
}

.modal-nav svg {
    width: 24px;
    height: 24px;
}

.modal-counter {
    position: absolute;
    bottom: 1.5rem;
    left: 50%;
    transform: translateX(-50%);
    padding: 0.5rem 1rem;
    background: rgba(0, 0, 0, 0.6);
    border-radius: 100px;
    font-size: 0.8125rem;
    color: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(4px);
}

/* Modal Sidebar */
.media-modal-sidebar {
    display: flex;
    flex-direction: column;
    background: #fff;
    border-left: 1px solid #e2e8f0;
}

.modal-sidebar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    background: #f8fafc;
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
    background: linear-gradient(180deg, var(--forge-primary), var(--forge-secondary));
    border-radius: 2px;
}

.modal-close-btn {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #e2e8f0;
    background: #fff;
    border-radius: 10px;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s;
}

.modal-close-btn:hover {
    background: #f1f5f9;
    color: #1e293b;
}

.modal-close-btn svg {
    width: 18px;
    height: 18px;
}

.modal-sidebar-body {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
}

/* Modal Sections */
.modal-section {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.25rem;
    margin-bottom: 1.25rem;
}

.modal-section:last-child {
    margin-bottom: 0;
}

.modal-section-title {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 1rem;
}

.modal-info-row {
    display: flex;
    justify-content: space-between;
    padding: 0.625rem 0;
    border-bottom: 1px solid #e2e8f0;
}

.modal-info-row:last-child {
    border-bottom: none;
}

.modal-info-label {
    font-size: 0.8125rem;
    color: #64748b;
}

.modal-info-value {
    font-size: 0.8125rem;
    font-weight: 500;
    color: #1e293b;
    text-align: right;
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Form Fields */
.modal-field {
    margin-bottom: 1rem;
}

.modal-field:last-child {
    margin-bottom: 0;
}

.modal-field label {
    display: block;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.5rem;
}

.modal-field input,
.modal-field textarea {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.875rem;
    color: #1e293b;
    background: #fff;
    transition: all 0.15s;
}

.modal-field input:focus,
.modal-field textarea:focus {
    outline: none;
    border-color: var(--forge-primary);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.modal-field textarea {
    resize: vertical;
    min-height: 80px;
}

/* URL Field */
.url-field-wrapper {
    display: flex;
    gap: 0.5rem;
}

.url-field-wrapper input {
    flex: 1;
}

.btn-copy {
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #e2e8f0;
    background: #fff;
    border-radius: 8px;
    color: #64748b;
    cursor: pointer;
    transition: all 0.15s;
    flex-shrink: 0;
}

.btn-copy:hover {
    background: #f1f5f9;
    color: var(--forge-primary);
}

.btn-copy.copied {
    background: #10b981;
    border-color: #10b981;
    color: #fff;
}

.btn-copy svg {
    width: 18px;
    height: 18px;
}

/* Modal Actions */
.modal-sidebar-footer {
    display: flex;
    gap: 0.75rem;
    padding: 1.5rem;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
}

.modal-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s;
    border: none;
}

.modal-btn.primary {
    background: var(--forge-primary);
    color: #fff;
}

.modal-btn.primary:hover {
    background: var(--forge-primary-dark);
}

.modal-btn.danger {
    background: #fef2f2;
    color: #ef4444;
    border: 1px solid #fecaca;
}

.modal-btn.danger:hover {
    background: #fee2e2;
}

.modal-btn svg {
    width: 16px;
    height: 16px;
}

/* Responsive */
@media (max-width: 900px) {
    .media-modal {
        grid-template-columns: 1fr;
        max-height: 95vh;
    }
    
    .media-modal-preview {
        min-height: 250px;
    }
    
    .media-modal-preview img {
        max-height: 40vh;
    }
}

@media (max-width: 640px) {
    .media-toolbar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .toolbar-left,
    .toolbar-right {
        justify-content: space-between;
    }
    
    .search-box input {
        width: 100%;
    }
    
    .media-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<div class="media-page">
    <!-- Page Header -->
    <div class="page-header">
        <h1>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                <circle cx="8.5" cy="8.5" r="1.5"/>
                <polyline points="21 15 16 10 5 21"/>
            </svg>
            Media Library
            <?php if ($currentFolder && $currentFolderInfo): ?>
            <span style="color: var(--text-muted); font-weight: 400;"> / <?php echo esc($currentFolderInfo['name']); ?></span>
            <?php endif; ?>
        </h1>
        <div class="header-actions">
            <button type="button" class="btn-upload" onclick="document.getElementById('fileInput').click()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="17 8 12 3 7 8"/>
                    <line x1="12" y1="3" x2="12" y2="15"/>
                </svg>
                Upload Files
            </button>
        </div>
    </div>
    
    <!-- Media Layout with Sidebar -->
    <div class="media-layout">
        <!-- Folder Sidebar -->
        <div class="folder-sidebar">
            <div class="folder-sidebar-header">
                <h3>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                    </svg>
                    Folders
                </h3>
                <div class="folder-header-actions">
                    <button type="button" class="btn-folder-action" onclick="toggleNewFolderForm()" title="New Folder">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"/>
                            <line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                    </button>
                    <button type="button" class="btn-folder-action" onclick="openSidebarSettings()" title="Sidebar Settings">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- New Folder Form -->
            <form method="POST" class="new-folder-form" id="newFolderForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create_folder">
                <input type="hidden" name="parent_id" id="newFolderParentId" value="<?= $currentFolder ?: '' ?>">
                <div class="new-folder-input-wrap">
                    <input type="text" name="folder_name" placeholder="<?= $currentFolder ? 'Subfolder name...' : 'Folder name...' ?>" required>
                    <button type="submit" class="btn-create-folder">Create</button>
                    <button type="button" class="btn-cancel-folder" onclick="toggleNewFolderForm()">Cancel</button>
                </div>
            </form>
            
            <div class="folder-list">
                <!-- All Media -->
                <a href="<?= ADMIN_URL ?>/media.php<?= $typeFilter !== 'all' ? '?type=' . $typeFilter : '' ?>" 
                   class="folder-item <?= !$currentFolder ? 'active' : '' ?>"
                   data-folder-id=""
                   ondragover="handleFolderDragOver(event)" 
                   ondragleave="handleFolderDragLeave(event)"
                   ondrop="handleFolderDrop(event)">
                    <span class="folder-item-toggle hidden"></span>
                    <svg class="folder-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                        <circle cx="8.5" cy="8.5" r="1.5"/>
                        <polyline points="21 15 16 10 5 21"/>
                    </svg>
                    <span class="folder-item-name">All Media</span>
                    <span class="folder-item-count"><?= $totalCount ?></span>
                </a>
                
                <!-- Folder Tree -->
                <?php 
                function renderFolderTree($folders, $currentFolder, $typeFilter, $level = 0) {
                    foreach ($folders as $folder):
                        $hasChildren = !empty($folder['children']);
                        $isExpanded = false;
                        
                        // Check if this folder or any child is active
                        if ($currentFolder == $folder['id']) {
                            $isExpanded = true;
                        } else if ($hasChildren) {
                            $checkExpanded = function($children, $targetId) use (&$checkExpanded) {
                                foreach ($children as $child) {
                                    if ($child['id'] == $targetId) return true;
                                    if (!empty($child['children']) && $checkExpanded($child['children'], $targetId)) return true;
                                }
                                return false;
                            };
                            $isExpanded = $checkExpanded($folder['children'], $currentFolder);
                        }
                ?>
                <div class="folder-tree-item">
                    <a href="<?= ADMIN_URL ?>/media.php?folder=<?= $folder['id'] ?><?= $typeFilter !== 'all' ? '&type=' . $typeFilter : '' ?>" 
                       class="folder-item <?= $currentFolder == $folder['id'] ? 'active' : '' ?>"
                       data-folder-id="<?= $folder['id'] ?>"
                       ondragover="handleFolderDragOver(event)" 
                       ondragleave="handleFolderDragLeave(event)"
                       ondrop="handleFolderDrop(event)">
                        <span class="folder-item-toggle <?= $hasChildren ? ($isExpanded ? 'expanded' : '') : 'hidden' ?>" 
                              onclick="event.preventDefault(); event.stopPropagation(); toggleFolderChildren(this)">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"/>
                            </svg>
                        </span>
                        <svg class="folder-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                        </svg>
                        <span class="folder-item-name"><?= esc($folder['name']) ?></span>
                        <span class="folder-item-count"><?= $folder['count'] ?? 0 ?></span>
                        <button type="button" class="folder-item-delete" onclick="event.preventDefault(); event.stopPropagation(); deleteFolder(<?= $folder['id'] ?>, '<?= esc($folder['name']) ?>')" title="Delete folder">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"/>
                                <line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                        </button>
                    </a>
                    <?php if ($hasChildren): ?>
                    <div class="folder-children <?= $isExpanded ? 'expanded' : '' ?>">
                        <?php renderFolderTree($folder['children'], $currentFolder, $typeFilter, $level + 1); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php 
                    endforeach;
                }
                renderFolderTree($folderTree, $currentFolder, $typeFilter);
                ?>
            </div>
        </div>
        
        <!-- Media Content -->
        <div class="media-content">
            <!-- Breadcrumb -->
            <?php if (!empty($folderBreadcrumb)): ?>
            <div class="folder-breadcrumb" style="margin-bottom: 1rem; font-size: 0.875rem; color: var(--text-muted);">
                <a href="<?= ADMIN_URL ?>/media.php" style="color: var(--forge-primary); text-decoration: none;">All Media</a>
                <?php foreach ($folderBreadcrumb as $bc): ?>
                <span style="margin: 0 0.5rem;">/</span>
                <a href="<?= ADMIN_URL ?>/media.php?folder=<?= $bc['id'] ?>" style="color: var(--forge-primary); text-decoration: none;"><?= esc($bc['name']) ?></a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- Toolbar -->
            <div class="media-toolbar">
                <div class="toolbar-left">
                    <div class="filter-tabs">
                        <a href="?type=all<?= $currentFolder ? '&folder=' . $currentFolder : '' ?>" class="filter-tab <?= $typeFilter === 'all' ? 'active' : '' ?>">
                            All
                            <span class="count"><?= $totalCount ?></span>
                        </a>
                        <a href="?type=image<?= $currentFolder ? '&folder=' . $currentFolder : '' ?>" class="filter-tab <?= $typeFilter === 'image' ? 'active' : '' ?>">
                            Images
                            <span class="count"><?= $imageCount ?></span>
                        </a>
                    </div>
                    
                    <form method="GET" class="search-box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.35-4.35"/>
                        </svg>
                        <input type="text" name="search" placeholder="Search files..." value="<?= esc($search) ?>">
                        <?php if ($typeFilter !== 'all'): ?>
                        <input type="hidden" name="type" value="<?= esc($typeFilter) ?>">
                        <?php endif; ?>
                        <?php if ($currentFolder): ?>
                        <input type="hidden" name="folder" value="<?= $currentFolder ?>">
                        <?php endif; ?>
                    </form>
                </div>
                
                <div class="toolbar-right">
                    <div class="view-toggle">
                        <button type="button" class="view-btn active" data-view="grid" onclick="setView('grid')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="7" height="7"/>
                                <rect x="14" y="3" width="7" height="7"/>
                                <rect x="3" y="14" width="7" height="7"/>
                                <rect x="14" y="14" width="7" height="7"/>
                            </svg>
                        </button>
                        <button type="button" class="view-btn" data-view="list" onclick="setView('list')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="8" y1="6" x2="21" y2="6"/>
                                <line x1="8" y1="12" x2="21" y2="12"/>
                                <line x1="8" y1="18" x2="21" y2="18"/>
                                <line x1="3" y1="6" x2="3.01" y2="6"/>
                                <line x1="3" y1="12" x2="3.01" y2="12"/>
                                <line x1="3" y1="18" x2="3.01" y2="18"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
    
    <!-- Upload Zone -->
    <form id="uploadForm" method="POST" enctype="multipart/form-data" style="display:none;" onsubmit="return false;">
        <?= csrfField() ?>
        <?php if ($currentFolder): ?>
        <input type="hidden" name="folder_id" value="<?= $currentFolder ?>">
        <?php endif; ?>
        <input type="file" id="fileInput" name="media_upload[]" multiple accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip">
    </form>
    
    <label class="upload-zone-inline" id="uploadZone" for="fileInput">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <polyline points="17 8 12 3 7 8"/>
            <line x1="12" y1="3" x2="12" y2="15"/>
        </svg>
        <h4>Drop files here or click to upload<?= $currentFolder ? ' to this folder' : '' ?></h4>
        <p>Supports images, videos, documents, and more</p>
    </label>
    
    <!-- Media Container -->
    <div class="media-grid-container">
        <?php if (empty($media)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                    <circle cx="8.5" cy="8.5" r="1.5"/>
                    <polyline points="21 15 16 10 5 21"/>
                </svg>
            </div>
            <h3>No media files yet</h3>
            <p>Upload your first file to get started.</p>
        </div>
        <?php else: ?>
        
        <!-- Grid View -->
        <div class="media-grid active" id="mediaGrid">
            <?php foreach ($media as $index => $item): 
                $isImage = strpos($item['mime_type'] ?? '', 'image/') === 0;
                $ext = strtoupper(pathinfo($item['filename'], PATHINFO_EXTENSION));
            ?>
            <div class="media-card" data-index="<?= $index ?>" data-media-id="<?= $item['id'] ?>" draggable="true" ondragstart="handleMediaDragStart(event)" ondragend="handleMediaDragEnd(event)">
                <div class="media-check">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                </div>
                <button type="button" class="media-quick-delete" onclick="event.stopPropagation(); quickDeleteMedia(<?= $item['id'] ?>, '<?= esc(addslashes($item['filename'])) ?>')" title="Delete">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                    </svg>
                </button>
                <div class="media-thumb">
                    <?php if ($isImage): ?>
                        <img src="<?= esc($item['url']) ?>" alt="<?= esc($item['alt_text'] ?? '') ?>" loading="lazy" draggable="false">
                    <?php else: ?>
                        <div class="media-file-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                            </svg>
                            <span class="media-file-ext"><?= $ext ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="media-info">
                    <div class="media-name"><?= esc($item['filename']) ?></div>
                    <div class="media-meta">
                        <span><?= formatFileSize((int)($item['size'] ?? 0)) ?></span>
                        <?php if (!empty($item['width']) && !empty($item['height'])): ?>
                        <span><?= $item['width'] ?>×<?= $item['height'] ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- List View -->
        <div class="media-list" id="mediaList">
            <?php foreach ($media as $index => $item): 
                $isImage = strpos($item['mime_type'] ?? '', 'image/') === 0;
                $ext = strtoupper(pathinfo($item['filename'], PATHINFO_EXTENSION));
            ?>
            <div class="media-list-item" data-index="<?= $index ?>" data-media-id="<?= $item['id'] ?>" draggable="true" ondragstart="handleMediaDragStart(event)" ondragend="handleMediaDragEnd(event)">
                <div class="media-list-thumb">
                    <?php if ($isImage): ?>
                        <img src="<?= esc($item['url']) ?>" alt="" draggable="false">
                    <?php else: ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                    <?php endif; ?>
                </div>
                <div class="media-list-info">
                    <div class="media-list-name"><?= esc($item['filename']) ?></div>
                    <div class="media-list-details">
                        <span><?= esc($item['mime_type'] ?? 'Unknown') ?></span>
                        <span><?= formatFileSize((int)($item['size'] ?? 0)) ?></span>
                        <span><?= date('M j, Y', strtotime($item['created_at'])) ?></span>
                    </div>
                </div>
                <button type="button" class="media-list-delete" onclick="event.stopPropagation(); quickDeleteMedia(<?= $item['id'] ?>, '<?= esc(addslashes($item['filename'])) ?>')" title="Delete">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                    </svg>
                </button>
                <div class="media-list-check">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php endif; ?>
    </div>
    <!-- End media-grid-container -->
    </div>
    <!-- End media-content -->
    </div>
    <!-- End media-layout -->
</div>
<!-- End media-page -->

<!-- Media Modal -->
<div class="media-modal-backdrop" id="mediaModal">
    <div class="media-modal">
        <div class="media-modal-preview" id="modalPreview">
            <!-- Preview content -->
        </div>
        
        <button class="modal-nav prev" onclick="navigateMedia(-1)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
        </button>
        <button class="modal-nav next" onclick="navigateMedia(1)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
        </button>
        
        <div class="modal-counter">
            <span id="modalCurrent">1</span> / <span id="modalTotal">1</span>
        </div>
        
        <div class="media-modal-sidebar">
            <div class="modal-sidebar-header">
                <span class="modal-sidebar-title">File Details</span>
                <button type="button" class="modal-close-btn" onclick="closeMediaModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            
            <div class="modal-sidebar-body">
                <div class="modal-section">
                    <h4 class="modal-section-title">File Information</h4>
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
                
                <form method="POST" id="updateForm">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="media_id" id="modalMediaId">
                    
                    <div class="modal-section">
                        <h4 class="modal-section-title">Details</h4>
                        <div class="modal-field">
                            <label for="modalTitle">Title</label>
                            <input type="text" name="title" id="modalTitle" placeholder="Enter title...">
                        </div>
                        <div class="modal-field">
                            <label for="modalAlt">Alt Text</label>
                            <textarea name="alt_text" id="modalAlt" placeholder="Describe this image for accessibility..."></textarea>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <h4 class="modal-section-title">URL</h4>
                        <div class="url-field-wrapper">
                            <input type="text" id="modalUrl" readonly>
                            <button type="button" class="btn-copy" onclick="copyUrl()">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="modal-sidebar-footer">
                <button type="button" class="modal-btn danger" onclick="deleteMedia()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                    </svg>
                    Delete
                </button>
                <button type="submit" form="updateForm" class="modal-btn primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Sidebar Settings Modal -->
<div class="sidebar-settings-modal" id="sidebarSettingsModal" onclick="if(event.target === this) closeSidebarSettings()">
    <div class="sidebar-settings-content">
        <h4>Folder Sidebar Settings</h4>
        <form method="POST" id="sidebarSettingsForm">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="save_sidebar_settings">
            <div class="sidebar-settings-field">
                <label>Sidebar Width</label>
                <input type="range" name="sidebar_width" id="sidebarWidthSlider" min="200" max="500" step="10" value="<?= $sidebarWidth ?>">
                <div class="range-value"><span id="sidebarWidthValue"><?= $sidebarWidth ?></span>px</div>
            </div>
            <div class="sidebar-settings-actions">
                <button type="button" class="btn-settings-cancel" onclick="closeSidebarSettings()">Cancel</button>
                <button type="submit" class="btn-settings-save">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Form -->
<form id="deleteForm" method="POST" style="display:none;">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="media_id" id="deleteMediaId">
</form>

<!-- Move to Folder Form -->
<form id="moveToFolderForm" style="display:none;">
    <?= csrfField() ?>
</form>

<script>
var mediaItems = <?= json_encode(array_values($media)) ?>;
var currentIndex = 0;
var currentView = localStorage.getItem('mediaView') || 'grid';
var draggedMediaId = null;
var uploadInProgress = false;

// Folder functions
function toggleNewFolderForm() {
    var form = document.getElementById('newFolderForm');
    form.classList.toggle('active');
    if (form.classList.contains('active')) {
        form.querySelector('input[name="folder_name"]').focus();
    }
}

function toggleFolderChildren(toggleEl) {
    toggleEl.classList.toggle('expanded');
    var children = toggleEl.closest('.folder-tree-item').querySelector('.folder-children');
    if (children) {
        children.classList.toggle('expanded');
    }
}

function deleteFolder(id, name) {
    if (confirm('Delete folder "' + name + '"? Files and subfolders will be moved to the parent folder.')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<?= str_replace("'", "\\'", csrfField()) ?>' +
            '<input type="hidden" name="action" value="delete_folder">' +
            '<input type="hidden" name="folder_id" value="' + id + '">';
        document.body.appendChild(form);
        form.submit();
    }
}

// Sidebar Settings
function openSidebarSettings() {
    var modal = document.getElementById('sidebarSettingsModal');
    if (modal) modal.classList.add('active');
}

function closeSidebarSettings() {
    var modal = document.getElementById('sidebarSettingsModal');
    if (modal) modal.classList.remove('active');
}

var sidebarWidthSlider = document.getElementById('sidebarWidthSlider');
if (sidebarWidthSlider) {
    sidebarWidthSlider.addEventListener('input', function() {
        document.getElementById('sidebarWidthValue').textContent = this.value;
    });
}

// Drag and Drop for Media
var isDragging = false;

function handleMediaDragStart(e) {
    console.log('Drag start', e.target);
    isDragging = true;
    
    // Find the media card or list item
    var item = e.target;
    if (!item.classList.contains('media-card') && !item.classList.contains('media-list-item')) {
        item = item.closest('.media-card') || item.closest('.media-list-item');
    }
    
    if (!item) {
        console.log('No item found');
        return;
    }
    
    draggedMediaId = item.dataset.mediaId;
    console.log('Dragging media ID:', draggedMediaId);
    
    item.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', draggedMediaId);
}

function handleMediaDragEnd(e) {
    console.log('Drag end');
    var item = e.target;
    if (!item.classList.contains('media-card') && !item.classList.contains('media-list-item')) {
        item = item.closest('.media-card') || item.closest('.media-list-item');
    }
    if (item) item.classList.remove('dragging');
    draggedMediaId = null;
    
    // Delay resetting isDragging to prevent click from firing
    setTimeout(function() {
        isDragging = false;
    }, 100);
    
    // Remove all drag-over states
    document.querySelectorAll('.folder-item.drag-over').forEach(function(el) {
        el.classList.remove('drag-over');
    });
}

function handleMediaClick(e) {
    // Don't open modal if we were dragging
    if (isDragging) {
        e.preventDefault();
        e.stopPropagation();
        return;
    }
    
    var item = e.target.closest('.media-card') || e.target.closest('.media-list-item');
    if (item) {
        var index = parseInt(item.dataset.index);
        openMediaModal(index);
    }
}

function handleFolderDragOver(e) {
    e.preventDefault();
    e.stopPropagation();
    e.dataTransfer.dropEffect = 'move';
    var folder = e.target.closest('.folder-item');
    if (folder) folder.classList.add('drag-over');
}

function handleFolderDragLeave(e) {
    e.preventDefault();
    var folder = e.target.closest('.folder-item');
    if (folder) folder.classList.remove('drag-over');
}

function handleFolderDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    console.log('Drop event');
    
    var folder = e.target.closest('.folder-item');
    if (folder) folder.classList.remove('drag-over');
    
    var folderId = folder ? (folder.dataset.folderId || '') : '';
    var mediaId = e.dataTransfer.getData('text/plain');
    
    console.log('Drop - folderId:', folderId, 'mediaId:', mediaId);
    
    if (!mediaId) {
        console.log('No media ID in drop');
        return;
    }
    
    // Send AJAX request to move media
    var formData = new FormData();
    formData.append('media_id', mediaId);
    formData.append('folder_id', folderId);
    formData.append('csrf_token', '<?= csrfToken() ?>');
    
    fetch('<?= ADMIN_URL ?>/media.php?action=move_to_folder', {
        method: 'POST',
        body: formData
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        console.log('Move response:', data);
        if (data.success) {
            // Remove the card from current view
            var card = document.querySelector('[data-media-id="' + mediaId + '"]');
            if (card) {
                card.style.transition = 'opacity 0.3s, transform 0.3s';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.8)';
                setTimeout(function() {
                    card.remove();
                }, 300);
            }
        } else {
            alert('Failed to move file: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(function(err) {
        console.error('Move error:', err);
        alert('Failed to move file');
    });
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 B';
    var k = 1024;
    var sizes = ['B', 'KB', 'MB', 'GB'];
    var i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function setView(view) {
    currentView = view;
    localStorage.setItem('mediaView', view);
    
    document.querySelectorAll('.view-btn').forEach(function(btn) {
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
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    var item = mediaItems[index];
    if (!item) return;
    
    var preview = document.getElementById('modalPreview');
    if (item.mime_type && item.mime_type.indexOf('image/') === 0) {
        preview.innerHTML = '<img src="' + item.url + '" alt="">';
    } else {
        var ext = item.filename ? item.filename.split('.').pop().toUpperCase() : 'FILE';
        preview.innerHTML = '<div class="media-modal-preview-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg><span>' + ext + '</span></div>';
    }
    
    document.getElementById('modalMediaId').value = item.id || '';
    document.getElementById('modalFilename').textContent = item.filename || '-';
    document.getElementById('modalType').textContent = item.mime_type || '-';
    document.getElementById('modalSize').textContent = formatFileSize(parseInt(item.size || 0));
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
    if (modal) modal.classList.remove('active');
    document.body.style.overflow = '';
}

function navigateMedia(direction) {
    var newIndex = currentIndex + direction;
    if (newIndex >= 0 && newIndex < mediaItems.length) {
        openMediaModal(newIndex);
    }
}

function copyUrl() {
    var url = document.getElementById('modalUrl');
    url.select();
    document.execCommand('copy');
    
    var btn = document.querySelector('.btn-copy');
    btn.classList.add('copied');
    btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>';
    
    setTimeout(function() {
        btn.classList.remove('copied');
        btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
    }, 1500);
}

function deleteMedia() {
    if (!confirm('Delete this file permanently?')) return;
    document.getElementById('deleteMediaId').value = mediaItems[currentIndex].id;
    document.getElementById('deleteForm').submit();
}

function quickDeleteMedia(id, filename) {
    if (!confirm('Delete "' + filename + '" permanently?')) return;
    document.getElementById('deleteMediaId').value = id;
    document.getElementById('deleteForm').submit();
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    setView(currentView);
    
    // Grid items - use handleMediaClick to check drag state
    document.querySelectorAll('.media-card').forEach(function(card) {
        card.addEventListener('click', handleMediaClick);
    });
    
    // List items - use handleMediaClick to check drag state
    document.querySelectorAll('.media-list-item').forEach(function(item) {
        item.addEventListener('click', handleMediaClick);
    });
    
    // Keyboard
    document.addEventListener('keydown', function(e) {
        var modal = document.getElementById('mediaModal');
        if (!modal || !modal.classList.contains('active')) return;
        if (e.key === 'Escape') closeMediaModal();
        if (e.key === 'ArrowLeft') navigateMedia(-1);
        if (e.key === 'ArrowRight') navigateMedia(1);
    });
    
    // Close on backdrop
    var modal = document.getElementById('mediaModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) closeMediaModal();
        });
    }
});

// File upload
var fileInput = document.getElementById('fileInput');
var uploadZone = document.getElementById('uploadZone');
var uploadForm = document.getElementById('uploadForm');

if (fileInput) {
    fileInput.addEventListener('change', function(e) {
        e.preventDefault();
        if (uploadInProgress) return;
        if (this.files.length > 0) {
            uploadInProgress = true;
            uploadFiles(this.files);
        }
    });
}

if (uploadZone) {
    uploadZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.add('dragover');
    });
    uploadZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.remove('dragover');
    });
    uploadZone.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.remove('dragover');
        if (uploadInProgress) return;
        if (e.dataTransfer.files.length > 0) {
            uploadInProgress = true;
            uploadFiles(e.dataTransfer.files);
        }
    });
    // Prevent clicking label from triggering if upload in progress
    uploadZone.addEventListener('click', function(e) {
        if (uploadInProgress) {
            e.preventDefault();
            e.stopPropagation();
        }
    });
}

function uploadFiles(files) {
    // Create fresh FormData with only necessary fields
    var formData = new FormData();
    formData.append('csrf_token', '<?= csrfToken() ?>');
    <?php if ($currentFolder): ?>
    formData.append('folder_id', '<?= $currentFolder ?>');
    <?php endif; ?>
    
    for (var i = 0; i < files.length; i++) {
        formData.append('media_upload[]', files[i]);
    }
    
    uploadZone.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:40px;height:40px;color:var(--forge-primary);animation:spin 1s linear infinite"><circle cx="12" cy="12" r="10" stroke-dasharray="32" stroke-linecap="round"/></svg><h4>Uploading...</h4>';
    
    fetch('<?= ADMIN_URL ?>/media.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    }).then(function(response) {
        if (!response.ok) {
            throw new Error('Upload failed with status: ' + response.status);
        }
        return response.text();
    }).then(function(text) {
        // Check if response contains error
        if (text.includes('error') && text.includes('"success":false')) {
            try {
                var json = JSON.parse(text);
                alert('Upload error: ' + (json.error || 'Unknown error'));
            } catch(e) {}
        }
        location.reload();
    }).catch(function(err) {
        uploadInProgress = false;
        console.error('Upload error:', err);
        alert('Upload failed: ' + err.message);
        location.reload();
    });
}
</script>

<style>
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
