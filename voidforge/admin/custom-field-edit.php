<?php
/**
 * Custom Field Group Editor - VoidForge CMS
 */

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
User::requireRole('admin');

$groupId = $_GET['id'] ?? null;
$isEdit = !empty($groupId);
$fieldGroups = getOption('custom_field_groups', []);
$group = $isEdit && isset($fieldGroups[$groupId]) ? $fieldGroups[$groupId] : null;

if ($isEdit && !$group) {
    setFlash('error', 'Field group not found.');
    redirect(ADMIN_URL . '/custom-fields.php');
}

$pageTitle = $isEdit ? 'Edit Field Group' : 'New Field Group';

// Get post types for location assignment
$postTypes = Post::getTypes();

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $title = trim($_POST['title'] ?? '');
    $locations = $_POST['locations'] ?? [];
    $fieldsJson = $_POST['fields_json'] ?? '[]';
    
    if (empty($title)) {
        $error = 'Title is required.';
    } else {
        $fields = json_decode($fieldsJson, true) ?: [];
        
        // Generate ID if new
        if (!$isEdit) {
            $groupId = 'group_' . time() . '_' . bin2hex(random_bytes(4));
        }
        
        $fieldGroups[$groupId] = [
            'title' => $title,
            'locations' => $locations,
            'fields' => $fields,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        
        if (!$isEdit) {
            $fieldGroups[$groupId]['created_at'] = date('Y-m-d H:i:s');
        }
        
        setOption('custom_field_groups', $fieldGroups);
        setFlash('success', $isEdit ? 'Field group updated!' : 'Field group created!');
        redirect(ADMIN_URL . '/custom-fields.php');
    }
}

// Data for form
$data = $group ?: [
    'title' => '',
    'locations' => [],
    'fields' => [],
];

include ADMIN_PATH . '/includes/header.php';
?>

<style>
.cfe-page { max-width: 900px; margin: 0 auto; }

.cfe-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 2rem;
}

.cfe-back {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: #f1f5f9;
    border-radius: 10px;
    color: #64748b;
    text-decoration: none;
    transition: all 0.2s ease;
}

.cfe-back:hover {
    background: #e2e8f0;
    color: #1e293b;
}

.cfe-title h1 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.cfe-title p {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0.25rem 0 0 0;
}

/* Cards */
.cfe-card {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    margin-bottom: 1.5rem;
}

.cfe-card-header {
    padding: 1.25rem;
    border-bottom: 1px solid #e2e8f0;
}

.cfe-card-header h2 {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.cfe-card-header p {
    font-size: 0.8125rem;
    color: #64748b;
    margin: 0.25rem 0 0 0;
}

.cfe-card-body {
    padding: 1.25rem;
}

/* Form */
.form-group {
    margin-bottom: 1.25rem;
}

.form-group:last-child {
    margin-bottom: 0;
}

.form-label {
    display: block;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
}

.form-input {
    width: 100%;
    padding: 0.625rem 0.875rem;
    font-size: 0.875rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.form-input:focus {
    outline: none;
    border-color: var(--forge-primary);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

/* Location Checkboxes */
.location-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 0.75rem;
}

.location-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.location-item:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
}

.location-item input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: var(--forge-primary);
}

.location-item input[type="checkbox"]:checked + .location-info {
    color: var(--forge-primary);
}

.location-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.location-info svg {
    color: #64748b;
}

.location-name {
    font-size: 0.875rem;
    font-weight: 500;
}

/* Fields List */
.fields-list {
    min-height: 100px;
}

.fields-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    text-align: center;
    color: #94a3b8;
}

.fields-empty svg {
    width: 48px;
    height: 48px;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.field-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    margin-bottom: 0.5rem;
}

.field-drag {
    cursor: grab;
    color: #94a3b8;
}

.field-info {
    flex: 1;
}

.field-name {
    font-size: 0.875rem;
    font-weight: 500;
    color: #1e293b;
}

.field-meta {
    font-size: 0.75rem;
    color: #64748b;
    font-family: monospace;
}

.field-type {
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
    background: #e2e8f0;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
}

.field-actions {
    display: flex;
    gap: 0.25rem;
}

.field-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    color: #64748b;
    cursor: pointer;
    transition: all 0.15s ease;
}

.field-btn:hover {
    background: #f1f5f9;
    color: #1e293b;
}

.field-btn.delete:hover {
    background: #fef2f2;
    border-color: #fecaca;
    color: #dc2626;
}

/* Add Field Button */
.btn-add-field {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.875rem;
    background: #f8fafc;
    border: 2px dashed #e2e8f0;
    border-radius: 8px;
    color: #64748b;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-add-field:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #1e293b;
}

/* Footer */
.cfe-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1.25rem;
    border-top: 1px solid #e2e8f0;
}

.btn-cancel {
    padding: 0.75rem 1.5rem;
    background: #f1f5f9;
    border: none;
    border-radius: 8px;
    color: #64748b;
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-cancel:hover {
    background: #e2e8f0;
    color: #1e293b;
}

.btn-save {
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, var(--forge-primary) 0%, var(--forge-secondary) 100%);
    border: none;
    border-radius: 8px;
    color: #fff;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 15px var(--forge-shadow-color);
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px var(--forge-shadow-color-hover);
}

/* Field Modal */
.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal-overlay.active {
    display: flex;
}

.modal {
    background: #fff;
    border-radius: 16px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem;
    border-bottom: 1px solid #e2e8f0;
}

.modal-header h3 {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.modal-close {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: #f1f5f9;
    border: none;
    border-radius: 8px;
    color: #64748b;
    cursor: pointer;
}

.modal-body {
    padding: 1.25rem;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1.25rem;
    border-top: 1px solid #e2e8f0;
}

.form-select {
    width: 100%;
    padding: 0.625rem 0.875rem;
    font-size: 0.875rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #fff;
}

.form-help {
    font-size: 0.75rem;
    color: #94a3b8;
    margin-top: 0.375rem;
}

.options-group {
    display: none;
}

.options-group.visible {
    display: block;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #374151;
    cursor: pointer;
}

.checkbox-label input {
    width: 16px;
    height: 16px;
    accent-color: var(--forge-primary);
}

.error-message {
    background: #fef2f2;
    color: #dc2626;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    font-size: 0.875rem;
}
</style>

<div class="cfe-page">
    <form method="post">
        <?= csrfField() ?>
        <input type="hidden" name="fields_json" id="fieldsJson" value="<?= esc(json_encode($data['fields'])) ?>">
        
        <div class="cfe-header">
            <a href="<?= ADMIN_URL ?>/custom-fields.php" class="cfe-back">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </a>
            <div class="cfe-title">
                <h1><?= $isEdit ? 'Edit Field Group' : 'New Field Group' ?></h1>
                <p><?= $isEdit ? 'Modify your field group settings' : 'Create a reusable set of custom fields' ?></p>
            </div>
        </div>
        
        <?php if (!empty($error)): ?>
        <div class="error-message"><?= esc($error) ?></div>
        <?php endif; ?>
        
        <!-- Basic Info -->
        <div class="cfe-card">
            <div class="cfe-card-header">
                <h2>Basic Information</h2>
            </div>
            <div class="cfe-card-body">
                <div class="form-group">
                    <label class="form-label">Title <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="title" class="form-input" value="<?= esc($data['title']) ?>" placeholder="e.g., Product Details, Author Info" required>
                </div>
            </div>
        </div>
        
        <!-- Location Assignment -->
        <div class="cfe-card">
            <div class="cfe-card-header">
                <h2>Show This Field Group For</h2>
                <p>Select where these fields should appear</p>
            </div>
            <div class="cfe-card-body">
                <div class="location-grid">
                    <?php foreach ($postTypes as $typeSlug => $typeConfig): ?>
                    <?php if ($typeConfig['public']): ?>
                    <label class="location-item">
                        <input type="checkbox" name="locations[]" value="<?= esc($typeSlug) ?>" <?= in_array($typeSlug, $data['locations']) ? 'checked' : '' ?>>
                        <div class="location-info">
                            <?= getAdminMenuIcon($typeConfig['icon'] ?? 'file', 18) ?>
                            <span class="location-name"><?= esc($typeConfig['label']) ?></span>
                        </div>
                    </label>
                    <?php endif; ?>
                    <?php endforeach; ?>
                    
                    <label class="location-item">
                        <input type="checkbox" name="locations[]" value="user" <?= in_array('user', $data['locations']) ? 'checked' : '' ?>>
                        <div class="location-info">
                            <?= getAdminMenuIcon('user', 18) ?>
                            <span class="location-name">Users</span>
                        </div>
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Custom Fields -->
        <div class="cfe-card">
            <div class="cfe-card-header">
                <h2>Fields</h2>
                <p>Define the custom fields in this group</p>
            </div>
            <div class="cfe-card-body">
                <div class="fields-list" id="fieldsList">
                    <!-- Fields rendered by JS -->
                </div>
                <button type="button" class="btn-add-field" id="btnAddField">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Add Field
                </button>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="cfe-card" style="margin-bottom: 0;">
            <div class="cfe-footer">
                <a href="<?= ADMIN_URL ?>/custom-fields.php" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-save">
                    <?= $isEdit ? 'Update Field Group' : 'Create Field Group' ?>
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Field Modal -->
<div class="modal-overlay" id="fieldModal">
    <div class="modal">
        <div class="modal-header">
            <h3 id="fieldModalTitle">Add Field</h3>
            <button type="button" class="modal-close" onclick="closeFieldModal()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Label</label>
                <input type="text" id="fieldLabel" class="form-input" placeholder="e.g., Price, Author Name">
            </div>
            <div class="form-group">
                <label class="form-label">Field Key</label>
                <input type="text" id="fieldKey" class="form-input" placeholder="e.g., price, author_name" style="font-family: monospace;">
                <div class="form-help">Unique identifier used in code. Lowercase with underscores.</div>
            </div>
            <div class="form-group">
                <label class="form-label">Type</label>
                <select id="fieldType" class="form-select" onchange="toggleOptionsField()">
                    <option value="text">Text</option>
                    <option value="textarea">Textarea</option>
                    <option value="number">Number</option>
                    <option value="email">Email</option>
                    <option value="url">URL</option>
                    <option value="date">Date</option>
                    <option value="datetime">Date & Time</option>
                    <option value="color">Color</option>
                    <option value="select">Dropdown Select</option>
                    <option value="checkbox">Checkbox</option>
                    <option value="image">Image</option>
                    <option value="file">File</option>
                    <option value="wysiwyg">Rich Text Editor</option>
                </select>
            </div>
            <div class="form-group options-group" id="optionsGroup">
                <label class="form-label">Options</label>
                <textarea id="fieldOptions" class="form-input" rows="3" placeholder="One option per line"></textarea>
                <div class="form-help">Enter each option on a new line</div>
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="fieldRequired">
                    Required field
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-cancel" onclick="closeFieldModal()">Cancel</button>
            <button type="button" class="btn-save" onclick="saveField()">Save Field</button>
        </div>
    </div>
</div>

<script>
let fields = <?= json_encode($data['fields']) ?>;
let editingFieldIndex = null;

const fieldsList = document.getElementById('fieldsList');
const fieldsJson = document.getElementById('fieldsJson');

// Initial render
renderFields();

// Auto-generate key from label
document.getElementById('fieldLabel').addEventListener('input', function() {
    if (editingFieldIndex === null) {
        const key = this.value.toLowerCase()
            .replace(/[^a-z0-9\s]/g, '')
            .replace(/\s+/g, '_');
        document.getElementById('fieldKey').value = key;
    }
});

function renderFields() {
    if (fields.length === 0) {
        fieldsList.innerHTML = `
            <div class="fields-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="3" y1="9" x2="21" y2="9"></line>
                    <line x1="9" y1="21" x2="9" y2="9"></line>
                </svg>
                <p>No fields yet. Click "Add Field" to create one.</p>
            </div>
        `;
    } else {
        let html = '';
        fields.forEach((field, index) => {
            html += `
                <div class="field-item" data-index="${index}">
                    <div class="field-drag">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="8" cy="6" r="1.5"></circle>
                            <circle cx="16" cy="6" r="1.5"></circle>
                            <circle cx="8" cy="12" r="1.5"></circle>
                            <circle cx="16" cy="12" r="1.5"></circle>
                            <circle cx="8" cy="18" r="1.5"></circle>
                            <circle cx="16" cy="18" r="1.5"></circle>
                        </svg>
                    </div>
                    <div class="field-info">
                        <div class="field-name">${escapeHtml(field.label)}${field.required ? ' <span style="color:#ef4444">*</span>' : ''}</div>
                        <div class="field-meta">${escapeHtml(field.key)}</div>
                    </div>
                    <span class="field-type">${escapeHtml(field.type)}</span>
                    <div class="field-actions">
                        <button type="button" class="field-btn edit" onclick="editField(${index})" title="Edit">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </button>
                        <button type="button" class="field-btn delete" onclick="deleteField(${index})" title="Delete">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            `;
        });
        fieldsList.innerHTML = html;
    }
    
    fieldsJson.value = JSON.stringify(fields);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function toggleOptionsField() {
    const type = document.getElementById('fieldType').value;
    const optionsGroup = document.getElementById('optionsGroup');
    optionsGroup.classList.toggle('visible', type === 'select');
}

document.getElementById('btnAddField').addEventListener('click', function() {
    openFieldModal();
});

function openFieldModal(index = null) {
    editingFieldIndex = index;
    
    document.getElementById('fieldModalTitle').textContent = index !== null ? 'Edit Field' : 'Add Field';
    document.getElementById('fieldLabel').value = '';
    document.getElementById('fieldKey').value = '';
    document.getElementById('fieldType').value = 'text';
    document.getElementById('fieldOptions').value = '';
    document.getElementById('fieldRequired').checked = false;
    
    if (index !== null && fields[index]) {
        const field = fields[index];
        document.getElementById('fieldLabel').value = field.label || '';
        document.getElementById('fieldKey').value = field.key || '';
        document.getElementById('fieldType').value = field.type || 'text';
        document.getElementById('fieldOptions').value = (field.options || []).join('\n');
        document.getElementById('fieldRequired').checked = field.required || false;
    }
    
    toggleOptionsField();
    document.getElementById('fieldModal').classList.add('active');
    document.getElementById('fieldLabel').focus();
}

function closeFieldModal() {
    document.getElementById('fieldModal').classList.remove('active');
    editingFieldIndex = null;
}

function editField(index) {
    openFieldModal(index);
}

function deleteField(index) {
    if (confirm('Delete this field?')) {
        fields.splice(index, 1);
        renderFields();
    }
}

function saveField() {
    const label = document.getElementById('fieldLabel').value.trim();
    const key = document.getElementById('fieldKey').value.trim();
    const type = document.getElementById('fieldType').value;
    const options = document.getElementById('fieldOptions').value.split('\n').map(o => o.trim()).filter(o => o);
    const required = document.getElementById('fieldRequired').checked;
    
    if (!label || !key) {
        alert('Label and key are required');
        return;
    }
    
    const field = { label, key, type, required };
    if (type === 'select') {
        field.options = options;
    }
    
    if (editingFieldIndex !== null) {
        fields[editingFieldIndex] = field;
    } else {
        fields.push(field);
    }
    
    renderFields();
    closeFieldModal();
}

// Close modal on overlay click
document.getElementById('fieldModal').addEventListener('click', function(e) {
    if (e.target === this) closeFieldModal();
});

// Close modal on Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeFieldModal();
});
</script>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
