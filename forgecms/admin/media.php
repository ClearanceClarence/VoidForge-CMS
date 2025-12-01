<?php
/**
 * Media Library - Forge CMS
 */

define('CMS_ROOT', dirname(__DIR__));
require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/user.php';
require_once CMS_ROOT . '/includes/post.php';
require_once CMS_ROOT . '/includes/media.php';

User::startSession();
User::requireRole('editor');

$pageTitle = 'Media Library';

// Handle AJAX requests
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    // Upload file
    if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCsrf()) {
            echo json_encode(['success' => false, 'error' => 'Invalid token']);
            exit;
        }
        
        if (!isset($_FILES['file'])) {
            echo json_encode(['success' => false, 'error' => 'No file uploaded']);
            exit;
        }
        
        $folderId = isset($_POST['folder_id']) ? (int)$_POST['folder_id'] : 0;
        $result = Media::upload($_FILES['file'], User::current()['id'], $folderId);
        echo json_encode($result);
        exit;
    }
    
    // Create folder
    if ($action === 'create_folder' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCsrf()) {
            echo json_encode(['success' => false, 'error' => 'Invalid token']);
            exit;
        }
        
        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            echo json_encode(['success' => false, 'error' => 'Folder name is required']);
            exit;
        }
        
        $result = Media::createFolder($name);
        echo json_encode($result);
        exit;
    }
    
    // Update media
    if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCsrf()) {
            echo json_encode(['success' => false, 'error' => 'Invalid token']);
            exit;
        }
        
        $mediaId = (int)($_POST['media_id'] ?? 0);
        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'alt_text' => trim($_POST['alt_text'] ?? ''),
            'folder_id' => (int)($_POST['folder_id'] ?? 0)
        ];
        
        $result = Media::update($mediaId, $data);
        echo json_encode($result);
        exit;
    }
    
    // Delete media
    if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCsrf()) {
            echo json_encode(['success' => false, 'error' => 'Invalid token']);
            exit;
        }
        
        $mediaId = (int)($_POST['media_id'] ?? 0);
        $result = Media::delete($mediaId);
        echo json_encode($result);
        exit;
    }
    
    // Get media details
    if ($action === 'get' && isset($_GET['media_id'])) {
        $mediaId = (int)$_GET['media_id'];
        $media = Media::get($mediaId);
        if ($media) {
            echo json_encode(['success' => true, 'media' => $media]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Media not found']);
        }
        exit;
    }
    
    // Get media list
    if ($action === 'list') {
        $folderId = isset($_GET['folder_id']) ? (int)$_GET['folder_id'] : null;
        $media = Media::getAll($folderId);
        echo json_encode(['success' => true, 'media' => $media]);
        exit;
    }
    
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit;
}

// Get folders and media for initial load
$folders = Media::getFolders();
$currentFolder = isset($_GET['folder']) ? (int)$_GET['folder'] : null;
$media = Media::getAll($currentFolder);

include ADMIN_PATH . '/includes/header.php';
?>

<style>
/* Media page specific styles */
.media-layout {
    display: grid;
    grid-template-columns: 220px 1fr;
    gap: 1.5rem;
    min-height: calc(100vh - 200px);
}

.media-sidebar {
    background: var(--bg-card);
    border-radius: var(--border-radius-lg);
    border: 1px solid var(--border-color);
    height: fit-content;
    position: sticky;
    top: calc(var(--header-height) + 2rem);
}

.media-sidebar-header {
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.media-sidebar-title {
    font-weight: 600;
    font-size: 0.875rem;
}

.folder-list { padding: 0.5rem 0; }

.folder-item {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    padding: 0.5rem 1rem;
    color: var(--text-secondary);
    cursor: pointer;
    transition: all 0.15s;
    font-size: 0.875rem;
}

.folder-item:hover {
    background: var(--bg-hover);
    color: var(--text-primary);
}

.folder-item.active {
    background: rgba(99, 102, 241, 0.08);
    color: var(--forge-primary);
}

.folder-item svg { width: 16px; height: 16px; flex-shrink: 0; }
.folder-item span { flex: 1; }

.folder-count {
    font-size: 0.75rem;
    color: var(--text-muted);
    background: var(--bg-card-header);
    padding: 0.125rem 0.375rem;
    border-radius: 4px;
}

.media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 1rem;
}

.media-item {
    background: var(--bg-card);
    border-radius: var(--border-radius);
    border: 2px solid var(--border-color);
    overflow: hidden;
    cursor: pointer;
    transition: all 0.15s;
}

.media-item:hover {
    border-color: var(--forge-primary);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.media-item.selected {
    border-color: var(--forge-primary);
    box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
}

.media-item-preview {
    aspect-ratio: 1;
    background: var(--bg-card-header);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.media-item-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.media-item-preview svg {
    width: 32px;
    height: 32px;
    color: var(--text-muted);
}

.media-item-info {
    padding: 0.625rem;
    border-top: 1px solid var(--border-color);
}

.media-item-name {
    font-size: 0.75rem;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.media-item-meta {
    font-size: 0.6875rem;
    color: var(--text-muted);
    margin-top: 0.125rem;
}

.upload-zone {
    border: 2px dashed var(--border-color);
    border-radius: var(--border-radius-lg);
    padding: 2.5rem 1.5rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.15s;
    background: var(--bg-card-header);
}

.upload-zone:hover, .upload-zone.dragover {
    border-color: var(--forge-primary);
    background: rgba(99, 102, 241, 0.05);
}

.upload-zone svg {
    width: 40px;
    height: 40px;
    color: var(--text-muted);
    margin-bottom: 0.75rem;
}

.upload-zone p { margin: 0.25rem 0; color: var(--text-secondary); font-size: 0.875rem; }
.upload-zone .highlight { color: var(--forge-primary); font-weight: 600; }

/* Modal with high z-index */
.media-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 1rem;
}

.media-modal-backdrop.active {
    display: flex;
}

.media-modal {
    background: var(--bg-card);
    border-radius: var(--border-radius-lg);
    width: 100%;
    max-width: 500px;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 25px 50px rgba(0,0,0,0.25);
}

.media-modal-header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.media-modal-title {
    font-size: 1rem;
    font-weight: 600;
}

.media-modal-close {
    width: 32px;
    height: 32px;
    border: none;
    background: transparent;
    cursor: pointer;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--border-radius);
}

.media-modal-close:hover {
    background: var(--bg-hover);
    color: var(--text-primary);
}

.media-modal-body {
    padding: 1.25rem;
    overflow-y: auto;
    max-height: calc(90vh - 140px);
}

.media-modal-footer {
    padding: 1rem 1.25rem;
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
}

.media-modal .form-group {
    margin-bottom: 1rem;
}

.media-modal .form-label {
    display: block;
    font-size: 0.8125rem;
    font-weight: 600;
    margin-bottom: 0.375rem;
}

.media-modal .form-input,
.media-modal select {
    width: 100%;
    padding: 0.625rem 0.875rem;
    font-size: 0.9375rem;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    background: var(--bg-input);
    color: var(--text-primary);
    font-family: inherit;
}

.media-modal .form-input:focus,
.media-modal select:focus {
    outline: none;
    border-color: var(--forge-primary);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}

.media-modal .form-hint {
    margin-top: 0.25rem;
    font-size: 0.75rem;
    color: var(--text-muted);
}

.media-preview-img {
    width: 100%;
    max-height: 200px;
    object-fit: contain;
    border-radius: var(--border-radius);
    background: var(--bg-card-header);
    margin-bottom: 1rem;
}

@media (max-width: 768px) {
    .media-layout { grid-template-columns: 1fr; }
    .media-sidebar { display: none; }
}
</style>

<div class="page-header">
    <h2>Media Library</h2>
</div>

<div class="media-layout">
    <!-- Sidebar -->
    <aside class="media-sidebar">
        <div class="media-sidebar-header">
            <span class="media-sidebar-title">Folders</span>
            <button type="button" class="btn btn-sm btn-secondary" onclick="openModal('newFolderModal')" title="New Folder">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
            </button>
        </div>
        <div class="folder-list">
            <div class="folder-item <?= $currentFolder === null ? 'active' : '' ?>" onclick="selectFolder(null)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                    <polyline points="21 15 16 10 5 21"></polyline>
                </svg>
                <span>All Media</span>
                <span class="folder-count"><?= count(Media::getAll()) ?></span>
            </div>
            <?php foreach ($folders as $folder): ?>
            <div class="folder-item <?= $currentFolder === $folder['id'] ? 'active' : '' ?>" onclick="selectFolder(<?= $folder['id'] ?>)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                </svg>
                <span><?= esc($folder['name']) ?></span>
                <span class="folder-count"><?= $folder['count'] ?? 0 ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </aside>

    <!-- Main -->
    <div class="media-main">
        <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; align-items: center;">
            <button type="button" class="btn btn-primary" onclick="openModal('uploadModal')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                Upload Files
            </button>
            <button type="button" class="btn btn-danger" id="deleteSelectedBtn" style="display: none;" onclick="deleteSelected()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
                Delete Selected
            </button>
            <span style="color: var(--text-muted); margin-left: auto;" id="mediaCount"><?= count($media) ?> items</span>
        </div>

        <div class="card">
            <div class="card-body">
                <?php if (empty($media)): ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                        <polyline points="21 15 16 10 5 21"></polyline>
                    </svg>
                    <h3>No media files</h3>
                    <p>Upload your first file to get started</p>
                    <button type="button" class="btn btn-primary" onclick="openModal('uploadModal')">Upload Files</button>
                </div>
                <?php else: ?>
                <div class="media-grid" id="mediaGrid">
                    <?php foreach ($media as $item): ?>
                    <div class="media-item" data-id="<?= $item['id'] ?>" onclick="selectItem(this, event)">
                        <div class="media-item-preview">
                            <?php if (strpos($item['mime_type'], 'image/') === 0): ?>
                            <img src="<?= esc($item['url']) ?>" alt="<?= esc($item['alt_text'] ?? '') ?>" loading="lazy">
                            <?php else: ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                            </svg>
                            <?php endif; ?>
                        </div>
                        <div class="media-item-info">
                            <div class="media-item-name"><?= esc($item['title'] ?: $item['filename']) ?></div>
                            <div class="media-item-meta"><?= formatFileSize($item['file_size']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div class="media-modal-backdrop" id="uploadModal">
    <div class="media-modal" style="max-width: 550px;">
        <div class="media-modal-header">
            <h3 class="media-modal-title">Upload Files</h3>
            <button type="button" class="media-modal-close" onclick="closeModal('uploadModal')">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="media-modal-body">
            <div class="upload-zone" id="uploadZone">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                <p>Drag and drop files here</p>
                <p>or <span class="highlight">click to browse</span></p>
            </div>
            <input type="file" id="fileInput" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip" style="display: none;">
            <div id="uploadProgress" style="margin-top: 1rem;"></div>
        </div>
    </div>
</div>

<!-- New Folder Modal -->
<div class="media-modal-backdrop" id="newFolderModal">
    <div class="media-modal" style="max-width: 400px;">
        <div class="media-modal-header">
            <h3 class="media-modal-title">New Folder</h3>
            <button type="button" class="media-modal-close" onclick="closeModal('newFolderModal')">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="media-modal-body">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Folder Name</label>
                <input type="text" class="form-input" id="folderName" placeholder="Enter folder name">
            </div>
        </div>
        <div class="media-modal-footer" style="justify-content: flex-end;">
            <button type="button" class="btn btn-secondary" onclick="closeModal('newFolderModal')">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="createFolder()">Create</button>
        </div>
    </div>
</div>

<!-- Edit Media Modal -->
<div class="media-modal-backdrop" id="editMediaModal">
    <div class="media-modal">
        <div class="media-modal-header">
            <h3 class="media-modal-title">Edit Media</h3>
            <button type="button" class="media-modal-close" onclick="closeModal('editMediaModal')">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="media-modal-body">
            <input type="hidden" id="editMediaId">
            <div id="editMediaPreview" style="text-align: center; margin-bottom: 1rem;"></div>
            
            <div class="form-group">
                <label class="form-label">Title</label>
                <input type="text" class="form-input" id="editMediaTitle" placeholder="Enter title">
            </div>
            
            <div class="form-group">
                <label class="form-label">Alt Text</label>
                <input type="text" class="form-input" id="editMediaAlt" placeholder="Describe this image">
                <div class="form-hint">For accessibility and SEO</div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Folder</label>
                <select class="form-input" id="editMediaFolder">
                    <option value="0">No Folder</option>
                    <?php foreach ($folders as $folder): ?>
                    <option value="<?= $folder['id'] ?>"><?= esc($folder['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">URL</label>
                <div style="display: flex; gap: 0.5rem;">
                    <input type="text" class="form-input" id="editMediaUrl" readonly style="font-size: 0.8125rem;">
                    <button type="button" class="btn btn-secondary" onclick="copyUrl()">Copy</button>
                </div>
            </div>
        </div>
        <div class="media-modal-footer">
            <button type="button" class="btn btn-danger" onclick="deleteCurrentMedia()">Delete</button>
            <div style="display: flex; gap: 0.5rem;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editMediaModal')">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveMedia()">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
const csrfToken = '<?= csrfToken() ?>';
let currentFolder = <?= $currentFolder !== null ? $currentFolder : 'null' ?>;
let selectedItems = new Set();
let lastClickTime = 0;
let lastClickId = null;

// Modal functions
function openModal(id) {
    document.getElementById(id).classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
    document.body.style.overflow = '';
}

// Close on backdrop click
document.querySelectorAll('.media-modal-backdrop').forEach(backdrop => {
    backdrop.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
});

// Upload zone
const uploadZone = document.getElementById('uploadZone');
const fileInput = document.getElementById('fileInput');

uploadZone.addEventListener('click', () => fileInput.click());
uploadZone.addEventListener('dragover', e => { e.preventDefault(); uploadZone.classList.add('dragover'); });
uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('dragover'));
uploadZone.addEventListener('drop', e => {
    e.preventDefault();
    uploadZone.classList.remove('dragover');
    if (e.dataTransfer.files.length) uploadFiles(e.dataTransfer.files);
});
fileInput.addEventListener('change', () => {
    if (fileInput.files.length) uploadFiles(fileInput.files);
});

// Upload files
async function uploadFiles(files) {
    const progress = document.getElementById('uploadProgress');
    progress.innerHTML = '';
    
    for (const file of files) {
        const item = document.createElement('div');
        item.style.cssText = 'display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 0.75rem; background: var(--bg-card-header); border-radius: 6px; margin-bottom: 0.5rem; font-size: 0.875rem;';
        item.innerHTML = `<span style="flex:1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${escapeHtml(file.name)}</span><span class="status"><span class="spinner spinner-dark"></span></span>`;
        progress.appendChild(item);
        
        try {
            const formData = new FormData();
            formData.append('action', 'upload');
            formData.append('file', file);
            formData.append('csrf_token', csrfToken);
            if (currentFolder) formData.append('folder_id', currentFolder);
            
            const res = await fetch(location.href, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });
            const data = await res.json();
            
            item.querySelector('.status').innerHTML = data.success 
                ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>'
                : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
        } catch (e) {
            item.querySelector('.status').innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><circle cx="12" cy="12" r="10"></circle></svg>';
        }
    }
    
    setTimeout(() => location.reload(), 800);
}

// Select folder
function selectFolder(id) {
    currentFolder = id;
    document.querySelectorAll('.folder-item').forEach(el => el.classList.remove('active'));
    event.currentTarget.classList.add('active');
    loadMedia();
}

// Load media
async function loadMedia() {
    let url = location.pathname + '?action=list';
    if (currentFolder !== null) url += '&folder_id=' + currentFolder;
    
    const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json();
    
    if (data.success) renderMedia(data.media);
}

// Render media
function renderMedia(media) {
    const grid = document.getElementById('mediaGrid');
    document.getElementById('mediaCount').textContent = media.length + ' items';
    
    if (!grid) return location.reload();
    
    if (media.length === 0) {
        grid.innerHTML = '<div class="empty-state" style="grid-column: 1/-1;"><h3>No files</h3><p>Upload files to this folder</p></div>';
        return;
    }
    
    grid.innerHTML = media.map(item => `
        <div class="media-item" data-id="${item.id}" onclick="selectItem(this, event)">
            <div class="media-item-preview">
                ${item.mime_type.startsWith('image/') 
                    ? `<img src="${escapeHtml(item.url)}" alt="" loading="lazy">`
                    : `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>`}
            </div>
            <div class="media-item-info">
                <div class="media-item-name">${escapeHtml(item.title || item.filename)}</div>
                <div class="media-item-meta">${formatFileSize(item.file_size)}</div>
            </div>
        </div>
    `).join('');
    
    selectedItems.clear();
    updateDeleteButton();
}

// Select item (double-click to edit)
function selectItem(el, e) {
    const id = parseInt(el.dataset.id);
    const now = Date.now();
    
    // Double click detection
    if (lastClickId === id && now - lastClickTime < 400) {
        editMedia(id);
        return;
    }
    
    lastClickTime = now;
    lastClickId = id;
    
    // Single click - toggle selection
    if (e.ctrlKey || e.metaKey) {
        el.classList.toggle('selected');
        if (el.classList.contains('selected')) {
            selectedItems.add(id);
        } else {
            selectedItems.delete(id);
        }
    } else {
        document.querySelectorAll('.media-item').forEach(item => item.classList.remove('selected'));
        selectedItems.clear();
        el.classList.add('selected');
        selectedItems.add(id);
    }
    
    updateDeleteButton();
}

function updateDeleteButton() {
    document.getElementById('deleteSelectedBtn').style.display = selectedItems.size > 0 ? 'inline-flex' : 'none';
}

// Edit media
async function editMedia(id) {
    try {
        const res = await fetch(`?action=get&media_id=${id}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await res.json();
        
        if (!data.success) return alert(data.error);
        
        const m = data.media;
        document.getElementById('editMediaId').value = m.id;
        document.getElementById('editMediaTitle').value = m.title || '';
        document.getElementById('editMediaAlt').value = m.alt_text || '';
        document.getElementById('editMediaFolder').value = m.folder_id || 0;
        document.getElementById('editMediaUrl').value = m.url;
        
        const preview = document.getElementById('editMediaPreview');
        if (m.mime_type.startsWith('image/')) {
            preview.innerHTML = `<img src="${escapeHtml(m.url)}" class="media-preview-img">`;
        } else {
            preview.innerHTML = `<div style="padding:2rem;background:var(--bg-card-header);border-radius:8px;"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg></div>`;
        }
        
        openModal('editMediaModal');
    } catch (e) {
        alert('Failed to load media');
    }
}

// Save media
async function saveMedia() {
    const id = document.getElementById('editMediaId').value;
    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('media_id', id);
    formData.append('title', document.getElementById('editMediaTitle').value);
    formData.append('alt_text', document.getElementById('editMediaAlt').value);
    formData.append('folder_id', document.getElementById('editMediaFolder').value);
    formData.append('csrf_token', csrfToken);
    
    const res = await fetch(location.href, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    });
    const data = await res.json();
    
    if (data.success) {
        closeModal('editMediaModal');
        loadMedia();
    } else {
        alert(data.error || 'Failed to save');
    }
}

// Delete current media
async function deleteCurrentMedia() {
    if (!confirm('Delete this file?')) return;
    
    const id = document.getElementById('editMediaId').value;
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('media_id', id);
    formData.append('csrf_token', csrfToken);
    
    const res = await fetch(location.href, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    });
    
    closeModal('editMediaModal');
    loadMedia();
}

// Delete selected
async function deleteSelected() {
    if (!confirm(`Delete ${selectedItems.size} file(s)?`)) return;
    
    for (const id of selectedItems) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('media_id', id);
        formData.append('csrf_token', csrfToken);
        await fetch(location.href, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });
    }
    
    loadMedia();
}

// Create folder
async function createFolder() {
    const name = document.getElementById('folderName').value.trim();
    if (!name) return alert('Enter folder name');
    
    const formData = new FormData();
    formData.append('action', 'create_folder');
    formData.append('name', name);
    formData.append('csrf_token', csrfToken);
    
    const res = await fetch(location.href, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    });
    const data = await res.json();
    
    if (data.success) {
        location.reload();
    } else {
        alert(data.error || 'Failed');
    }
}

// Copy URL
function copyUrl() {
    const url = document.getElementById('editMediaUrl').value;
    navigator.clipboard.writeText(url);
    alert('URL copied!');
}

// Helpers
function formatFileSize(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
