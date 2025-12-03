<?php
/**
 * Site Settings - Forge CMS
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
User::requireRole('admin');

$pageTitle = 'Settings';

// Handle AJAX requests for thumbnail management
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'error' => 'Invalid security token']);
        exit;
    }
    
    $action = $_POST['ajax_action'];
    
    switch ($action) {
        case 'add_thumbnail_size':
            $name = preg_replace('/[^a-z0-9_-]/', '', strtolower($_POST['name'] ?? ''));
            $width = (int)($_POST['width'] ?? 0);
            $height = (int)($_POST['height'] ?? 0);
            $crop = ($_POST['crop'] ?? '0') === '1';
            
            if (empty($name) || $width < 1 || $height < 1) {
                echo json_encode(['success' => false, 'error' => 'Invalid size parameters']);
                exit;
            }
            
            if (Media::isDefaultSize($name)) {
                echo json_encode(['success' => false, 'error' => 'Cannot modify default size name']);
                exit;
            }
            
            Media::setThumbnailSize($name, $width, $height, $crop, true);
            echo json_encode(['success' => true, 'message' => 'Thumbnail size added']);
            exit;
            
        case 'toggle_thumbnail_size':
            $name = $_POST['name'] ?? '';
            $enabled = ($_POST['enabled'] ?? '0') === '1';
            
            if (empty($name)) {
                echo json_encode(['success' => false, 'error' => 'Size name required']);
                exit;
            }
            
            Media::toggleThumbnailSize($name, $enabled);
            echo json_encode(['success' => true, 'message' => 'Thumbnail size updated']);
            exit;
            
        case 'remove_thumbnail_size':
            $name = $_POST['name'] ?? '';
            
            if (Media::isDefaultSize($name)) {
                echo json_encode(['success' => false, 'error' => 'Cannot remove default sizes']);
                exit;
            }
            
            if (Media::removeThumbnailSize($name)) {
                echo json_encode(['success' => true, 'message' => 'Thumbnail size removed']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to remove size']);
            }
            exit;
            
        case 'regenerate_all_thumbnails':
            $result = Media::regenerateAllThumbnails();
            echo json_encode([
                'success' => true, 
                'message' => "Regenerated {$result['success']} images ({$result['failed']} failed)"
            ]);
            exit;
    }
    
    echo json_encode(['success' => false, 'error' => 'Unknown action']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    setOption('site_title', trim($_POST['site_title'] ?? ''));
    setOption('site_description', trim($_POST['site_description'] ?? ''));
    setOption('posts_per_page', (int)($_POST['posts_per_page'] ?? 10));
    setOption('date_format', $_POST['date_format'] ?? 'M j, Y');
    setOption('time_format', $_POST['time_format'] ?? 'H:i');
    
    setFlash('success', 'Settings saved successfully.');
    redirect(ADMIN_URL . '/settings.php');
}

$siteTitle = getOption('site_title', '');
$siteDescription = getOption('site_description', '');
$postsPerPage = getOption('posts_per_page', 10);
$dateFormat = getOption('date_format', 'M j, Y');
$timeFormat = getOption('time_format', 'H:i');

// Get thumbnail sizes
$thumbnailSizes = Media::getThumbnailSizes(true);

include ADMIN_PATH . '/includes/header.php';
?>

<style>
.settings-container {
    max-width: 720px;
}

.settings-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-lg);
    margin-bottom: 1.5rem;
    overflow: hidden;
}

.settings-card-header {
    padding: 1rem 1.25rem;
    background: var(--bg-card-header);
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.settings-card-icon {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
    border-radius: var(--border-radius);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--forge-primary);
}

.settings-card-title {
    font-size: 0.9375rem;
    font-weight: 600;
    margin: 0;
}

.settings-card-body {
    padding: 1.25rem;
}

.form-group {
    margin-bottom: 1.25rem;
}

.form-group:last-child {
    margin-bottom: 0;
}

.form-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 0.375rem;
    color: var(--text-primary);
}

.form-input,
.form-select {
    width: 100%;
    padding: 0.625rem 0.875rem;
    font-size: 0.9375rem;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    background: var(--bg-secondary);
    color: var(--text-primary);
    transition: border-color 0.2s, box-shadow 0.2s;
}

.form-input:focus,
.form-select:focus {
    outline: none;
    border-color: var(--forge-primary);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.form-hint {
    font-size: 0.8125rem;
    color: var(--text-muted);
    margin-top: 0.375rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.btn-save {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, var(--forge-primary) 0%, var(--forge-secondary) 100%);
    color: white;
    border: none;
    border-radius: var(--border-radius);
    font-size: 0.9375rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
}

/* Thumbnail sizes table */
.thumbnail-table {
    width: 100%;
    border-collapse: collapse;
}

.thumbnail-table th,
.thumbnail-table td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid var(--border-color);
}

.thumbnail-table th {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
    font-weight: 600;
}

.thumbnail-table td {
    font-size: 0.875rem;
}

.thumbnail-table tr:last-child td {
    border-bottom: none;
}

.size-name {
    font-family: monospace;
    background: var(--bg-secondary);
    padding: 0.125rem 0.375rem;
    border-radius: 4px;
    font-size: 0.8125rem;
}

.size-badge {
    display: inline-block;
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
}

.size-badge-default {
    background: rgba(99, 102, 241, 0.1);
    color: var(--forge-primary);
}

.size-badge-custom {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

.toggle-switch {
    position: relative;
    width: 44px;
    height: 24px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: var(--border-color);
    transition: 0.3s;
    border-radius: 24px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
}

.toggle-switch input:checked + .toggle-slider {
    background: linear-gradient(135deg, var(--forge-primary) 0%, var(--forge-secondary) 100%);
}

.toggle-switch input:checked + .toggle-slider:before {
    transform: translateX(20px);
}

.btn-remove-size {
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 4px;
    transition: all 0.2s;
}

.btn-remove-size:hover {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
}

.btn-remove-size:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

.btn-remove-size:disabled:hover {
    background: none;
    color: var(--text-muted);
}

/* Add size form */
.add-size-form {
    display: grid;
    grid-template-columns: 1fr 80px 80px 80px auto;
    gap: 0.75rem;
    align-items: end;
    padding: 1rem;
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
    margin-top: 1rem;
}

.add-size-form .form-group {
    margin-bottom: 0;
}

.add-size-form .form-label {
    font-size: 0.75rem;
    margin-bottom: 0.25rem;
}

.add-size-form .form-input {
    padding: 0.5rem 0.625rem;
    font-size: 0.875rem;
}

.btn-add-size {
    padding: 0.5rem 1rem;
    background: var(--forge-primary);
    color: white;
    border: none;
    border-radius: var(--border-radius);
    font-size: 0.8125rem;
    font-weight: 500;
    cursor: pointer;
    white-space: nowrap;
}

.btn-add-size:hover {
    background: var(--forge-secondary);
}

.btn-regenerate {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    font-size: 0.8125rem;
    color: var(--text-primary);
    cursor: pointer;
    transition: all 0.2s;
}

.btn-regenerate:hover {
    background: var(--bg-card-header);
    border-color: var(--forge-primary);
}

.checkbox-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.checkbox-group input[type="checkbox"] {
    width: 16px;
    height: 16px;
    accent-color: var(--forge-primary);
}

@media (max-width: 768px) {
    .add-size-form {
        grid-template-columns: 1fr 1fr;
    }
    
    .add-size-form .form-group:first-child {
        grid-column: span 2;
    }
}

@media (max-width: 640px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .thumbnail-table th:nth-child(4),
    .thumbnail-table td:nth-child(4) {
        display: none;
    }
}
</style>

<div class="page-header" style="margin-bottom: 1.5rem;">
    <h2>Settings</h2>
    <p style="color: var(--text-secondary); margin-top: 0.25rem;">Configure your site's core settings.</p>
</div>

<div class="settings-container">
    <form method="post">
        <?= csrfField() ?>
        
        <div class="settings-card">
            <div class="settings-card-header">
                <div class="settings-card-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="2" y1="12" x2="22" y2="12"></line>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                    </svg>
                </div>
                <h3 class="settings-card-title">General</h3>
            </div>
            <div class="settings-card-body">
                <div class="form-group">
                    <label for="site_title" class="form-label">Site Title</label>
                    <input type="text" id="site_title" name="site_title" class="form-input" 
                           value="<?= esc($siteTitle) ?>" placeholder="My Awesome Site">
                </div>

                <div class="form-group">
                    <label for="site_description" class="form-label">Tagline</label>
                    <input type="text" id="site_description" name="site_description" class="form-input" 
                           value="<?= esc($siteDescription) ?>" placeholder="Just another Forge CMS site">
                    <div class="form-hint">In a few words, explain what this site is about.</div>
                </div>
            </div>
        </div>

        <div class="settings-card">
            <div class="settings-card-header">
                <div class="settings-card-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                </div>
                <h3 class="settings-card-title">Reading</h3>
            </div>
            <div class="settings-card-body">
                <div class="form-group">
                    <label for="posts_per_page" class="form-label">Posts per page</label>
                    <input type="number" id="posts_per_page" name="posts_per_page" class="form-input" 
                           value="<?= esc($postsPerPage) ?>" min="1" max="100" style="max-width: 120px;">
                </div>
            </div>
        </div>

        <div class="settings-card">
            <div class="settings-card-header">
                <div class="settings-card-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                </div>
                <h3 class="settings-card-title">Date &amp; Time</h3>
            </div>
            <div class="settings-card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="date_format" class="form-label">Date Format</label>
                        <select name="date_format" id="date_format" class="form-select">
                            <option value="M j, Y" <?= $dateFormat === 'M j, Y' ? 'selected' : '' ?>>
                                <?= date('M j, Y') ?>
                            </option>
                            <option value="F j, Y" <?= $dateFormat === 'F j, Y' ? 'selected' : '' ?>>
                                <?= date('F j, Y') ?>
                            </option>
                            <option value="Y-m-d" <?= $dateFormat === 'Y-m-d' ? 'selected' : '' ?>>
                                <?= date('Y-m-d') ?>
                            </option>
                            <option value="d/m/Y" <?= $dateFormat === 'd/m/Y' ? 'selected' : '' ?>>
                                <?= date('d/m/Y') ?>
                            </option>
                            <option value="m/d/Y" <?= $dateFormat === 'm/d/Y' ? 'selected' : '' ?>>
                                <?= date('m/d/Y') ?>
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="time_format" class="form-label">Time Format</label>
                        <select name="time_format" id="time_format" class="form-select">
                            <option value="H:i" <?= $timeFormat === 'H:i' ? 'selected' : '' ?>>
                                <?= date('H:i') ?> (24-hour)
                            </option>
                            <option value="g:i A" <?= $timeFormat === 'g:i A' ? 'selected' : '' ?>>
                                <?= date('g:i A') ?> (12-hour)
                            </option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn-save">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                <polyline points="7 3 7 8 15 8"></polyline>
            </svg>
            Save Settings
        </button>
    </form>
    
    <!-- Thumbnail Sizes (separate from main form) -->
    <div class="settings-card" style="margin-top: 2rem;">
        <div class="settings-card-header">
            <div class="settings-card-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                    <polyline points="21 15 16 10 5 21"></polyline>
                </svg>
            </div>
            <h3 class="settings-card-title">Image Thumbnail Sizes</h3>
            <button type="button" class="btn-regenerate" onclick="regenerateAllThumbnails(this)" style="margin-left: auto;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23 4 23 10 17 10"></polyline>
                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                </svg>
                Regenerate All
            </button>
        </div>
        <div class="settings-card-body" style="padding: 0;">
            <table class="thumbnail-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Dimensions</th>
                        <th>Crop</th>
                        <th>Type</th>
                        <th>Enabled</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="thumbnailSizesBody">
                    <?php foreach ($thumbnailSizes as $name => $config): 
                        $width = $config[0];
                        $height = $config[1];
                        $crop = isset($config[2]) ? $config[2] : false;
                        $enabled = isset($config[3]) ? $config[3] : true;
                        $isDefault = Media::isDefaultSize($name);
                    ?>
                    <tr data-size="<?= esc($name) ?>">
                        <td><code class="size-name"><?= esc($name) ?></code></td>
                        <td><?= $width ?> × <?= $height ?>px</td>
                        <td><?= $crop ? 'Yes' : 'No' ?></td>
                        <td>
                            <?php if ($isDefault): ?>
                                <span class="size-badge size-badge-default">Default</span>
                            <?php else: ?>
                                <span class="size-badge size-badge-custom">Custom</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <label class="toggle-switch">
                                <input type="checkbox" <?= $enabled ? 'checked' : '' ?> 
                                       onchange="toggleThumbnailSize('<?= esc($name) ?>', this.checked)">
                                <span class="toggle-slider"></span>
                            </label>
                        </td>
                        <td>
                            <button type="button" class="btn-remove-size" 
                                    onclick="removeThumbnailSize('<?= esc($name) ?>')"
                                    <?= $isDefault ? 'disabled title="Cannot remove default sizes"' : '' ?>>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="add-size-form">
                <div class="form-group">
                    <label class="form-label">Name</label>
                    <input type="text" id="newSizeName" class="form-input" placeholder="e.g. hero">
                </div>
                <div class="form-group">
                    <label class="form-label">Width</label>
                    <input type="number" id="newSizeWidth" class="form-input" placeholder="1920" min="1">
                </div>
                <div class="form-group">
                    <label class="form-label">Height</label>
                    <input type="number" id="newSizeHeight" class="form-input" placeholder="1080" min="1">
                </div>
                <div class="form-group">
                    <label class="form-label">Crop</label>
                    <div class="checkbox-group" style="height: 38px; align-items: center;">
                        <input type="checkbox" id="newSizeCrop">
                        <span style="font-size: 0.8125rem;">Center crop</span>
                    </div>
                </div>
                <button type="button" class="btn-add-size" onclick="addThumbnailSize()">
                    Add Size
                </button>
            </div>
        </div>
    </div>
    
    <div class="form-hint" style="margin-top: 1rem;">
        <strong>Note:</strong> After adding or modifying thumbnail sizes, use "Regenerate All" to update existing images.
        You can also regenerate thumbnails for individual images in the Media Library.
    </div>
</div>

<script>
const csrfToken = '<?= getCsrfToken() ?>';

async function ajaxAction(action, data = {}) {
    const formData = new FormData();
    formData.append('ajax_action', action);
    formData.append('csrf_token', csrfToken);
    
    for (const [key, value] of Object.entries(data)) {
        formData.append(key, value);
    }
    
    try {
        const res = await fetch('', { method: 'POST', body: formData });
        return await res.json();
    } catch (e) {
        return { success: false, error: e.message };
    }
}

async function toggleThumbnailSize(name, enabled) {
    const result = await ajaxAction('toggle_thumbnail_size', { name, enabled: enabled ? '1' : '0' });
    if (!result.success) {
        alert('Error: ' + result.error);
    }
}

async function removeThumbnailSize(name) {
    if (!confirm('Remove this thumbnail size? This will not delete existing thumbnail files.')) {
        return;
    }
    
    const result = await ajaxAction('remove_thumbnail_size', { name });
    if (result.success) {
        document.querySelector(`tr[data-size="${name}"]`)?.remove();
    } else {
        alert('Error: ' + result.error);
    }
}

async function addThumbnailSize() {
    const name = document.getElementById('newSizeName').value.trim().toLowerCase().replace(/[^a-z0-9_-]/g, '');
    const width = parseInt(document.getElementById('newSizeWidth').value);
    const height = parseInt(document.getElementById('newSizeHeight').value);
    const crop = document.getElementById('newSizeCrop').checked ? '1' : '0';
    
    if (!name || !width || !height) {
        alert('Please fill in all fields');
        return;
    }
    
    const result = await ajaxAction('add_thumbnail_size', { name, width, height, crop });
    if (result.success) {
        location.reload();
    } else {
        alert('Error: ' + result.error);
    }
}

async function regenerateAllThumbnails(btn) {
    if (!confirm('Regenerate all thumbnails? This may take a while for large media libraries.')) {
        return;
    }
    
    const originalText = btn.innerHTML;
    btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="spin"><polyline points="23 4 23 10 17 10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg> Working...';
    btn.disabled = true;
    
    const result = await ajaxAction('regenerate_all_thumbnails');
    
    btn.innerHTML = originalText;
    btn.disabled = false;
    
    if (result.success) {
        alert(result.message);
    } else {
        alert('Error: ' + result.error);
    }
}
</script>

<style>
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.spin {
    animation: spin 1s linear infinite;
}
</style>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
