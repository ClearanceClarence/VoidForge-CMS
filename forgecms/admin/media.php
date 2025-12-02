<?php
/**
 * Media Library - Forge CMS v1.0.3
 * Single-click selection with slide-in panel
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

        <div class="media-upload-zone" id="uploadZone">
            <div class="media-upload-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
            </div>
            <h3>Drop files here to upload</h3>
            <p>or click "Upload" button above</p>
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
                 data-size="<?= $item['file_size'] ?>"
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
            <button type="button" class="btn btn-danger" onclick="deleteMedia()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
                Delete
            </button>
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
    if (data.mime.startsWith('image/')) {
        preview.innerHTML = '<img src="' + data.url + '" alt="">';
    } else {
        preview.innerHTML = '<div class="media-panel-preview-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg><span>' + data.filename.split('.').pop().toUpperCase() + '</span></div>';
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
</script>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
