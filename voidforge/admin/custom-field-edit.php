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

.sub-fields-group {
    display: none;
}

.sub-fields-group.visible {
    display: block;
}

.sub-fields-list {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    margin-bottom: 0.75rem;
    max-height: 200px;
    overflow-y: auto;
}

.sub-fields-list:empty::before {
    content: 'No sub fields defined';
    display: block;
    padding: 1rem;
    text-align: center;
    color: #94a3b8;
    font-size: 0.8125rem;
}

.sub-field-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 0.75rem;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
}

.sub-field-item:last-child {
    border-bottom: none;
}

.sub-field-item .sub-field-info {
    flex: 1;
    min-width: 0;
}

.sub-field-item .sub-field-label {
    font-size: 0.8125rem;
    font-weight: 500;
    color: #1e293b;
}

.sub-field-item .sub-field-key {
    font-size: 0.6875rem;
    color: #64748b;
    font-family: monospace;
}

.sub-field-item .sub-field-type {
    font-size: 0.625rem;
    font-weight: 600;
    text-transform: uppercase;
    background: #e2e8f0;
    color: #64748b;
    padding: 0.125rem 0.375rem;
    border-radius: 3px;
}

.sub-field-item .sub-field-remove {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    background: transparent;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    border-radius: 4px;
}

.sub-field-item .sub-field-remove:hover {
    background: #fef2f2;
    color: #dc2626;
}

.sub-field-item .sub-field-edit {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    background: transparent;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    border-radius: 4px;
}

.sub-field-item .sub-field-edit:hover {
    background: #eff6ff;
    color: #3b82f6;
}

.btn-add-subfield {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    width: 100%;
    padding: 0.5rem;
    background: #f1f5f9;
    border: 1px dashed #cbd5e1;
    border-radius: 6px;
    color: #64748b;
    font-size: 0.75rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-add-subfield:hover {
    background: #e2e8f0;
    border-color: #94a3b8;
    color: #475569;
}

/* Field type badges for repeater/group */
.field-type.repeater {
    background: linear-gradient(135deg, #ddd6fe, #c4b5fd);
    color: #6d28d9;
}

.field-type.group {
    background: linear-gradient(135deg, #cffafe, #a5f3fc);
    color: #0e7490;
}

.field-subcount {
    font-size: 0.625rem;
    color: #94a3b8;
    margin-left: 0.25rem;
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
                    <optgroup label="Basic">
                        <option value="text">Text</option>
                        <option value="textarea">Textarea</option>
                        <option value="number">Number</option>
                        <option value="email">Email</option>
                        <option value="url">URL</option>
                    </optgroup>
                    <optgroup label="Date & Time">
                        <option value="date">Date</option>
                        <option value="datetime">Date & Time</option>
                    </optgroup>
                    <optgroup label="Choice">
                        <option value="select">Dropdown Select</option>
                        <option value="checkbox">Checkbox</option>
                        <option value="radio">Radio Buttons</option>
                    </optgroup>
                    <optgroup label="Media">
                        <option value="color">Color</option>
                        <option value="image">Image</option>
                        <option value="file">File</option>
                    </optgroup>
                    <optgroup label="Content">
                        <option value="wysiwyg">Rich Text Editor</option>
                    </optgroup>
                    <optgroup label="Layout">
                        <option value="repeater">Repeater</option>
                        <option value="group">Group</option>
                    </optgroup>
                </select>
            </div>
            <div class="form-group options-group" id="optionsGroup">
                <label class="form-label">Options</label>
                <textarea id="fieldOptions" class="form-input" rows="3" placeholder="One option per line"></textarea>
                <div class="form-help">Enter each option on a new line</div>
            </div>
            <div class="form-group sub-fields-group" id="subFieldsGroup">
                <label class="form-label">Sub Fields</label>
                <div class="sub-fields-list" id="subFieldsList"></div>
                <button type="button" class="btn-add-subfield" onclick="addSubField()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Add Sub Field
                </button>
                <div class="form-help" style="margin-top: 0.5rem;">Define the fields that will appear in each row (repeater) or as a group</div>
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

<!-- Sub Field Modal -->
<div class="modal-overlay" id="subFieldModal">
    <div class="modal">
        <div class="modal-header">
            <h3 id="subFieldModalTitle">Add Sub Field</h3>
            <button type="button" class="modal-close" onclick="closeSubFieldModal()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Label</label>
                <input type="text" id="subFieldLabel" class="form-input" placeholder="e.g., Name, Description">
            </div>
            <div class="form-group">
                <label class="form-label">Field Key</label>
                <input type="text" id="subFieldKey" class="form-input" placeholder="e.g., name, description" style="font-family: monospace;">
                <div class="form-help">Unique identifier used in code. Lowercase with underscores.</div>
            </div>
            <div class="form-group">
                <label class="form-label">Type</label>
                <select id="subFieldType" class="form-select" onchange="toggleSubFieldOptions()">
                    <optgroup label="Basic">
                        <option value="text">Text</option>
                        <option value="textarea">Textarea</option>
                        <option value="number">Number</option>
                        <option value="email">Email</option>
                        <option value="url">URL</option>
                    </optgroup>
                    <optgroup label="Date & Time">
                        <option value="date">Date</option>
                        <option value="datetime">Date & Time</option>
                    </optgroup>
                    <optgroup label="Choice">
                        <option value="select">Dropdown Select</option>
                        <option value="checkbox">Checkbox</option>
                        <option value="radio">Radio Buttons</option>
                    </optgroup>
                    <optgroup label="Media">
                        <option value="color">Color</option>
                        <option value="image">Image</option>
                        <option value="file">File</option>
                    </optgroup>
                    <optgroup label="Content">
                        <option value="wysiwyg">Rich Text Editor</option>
                    </optgroup>
                </select>
            </div>
            <div class="form-group options-group" id="subFieldOptionsGroup">
                <label class="form-label">Options</label>
                <textarea id="subFieldOptions" class="form-input" rows="3" placeholder="One option per line"></textarea>
                <div class="form-help">Enter each option on a new line</div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-cancel" onclick="closeSubFieldModal()">Cancel</button>
            <button type="button" class="btn-save" onclick="saveSubField()">Save Sub Field</button>
        </div>
    </div>
</div>

<script>
let fields = <?= json_encode($data['fields']) ?>;
let editingFieldIndex = null;
let currentSubFields = [];

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
            const isLayout = field.type === 'repeater' || field.type === 'group';
            const subCount = isLayout && field.sub_fields ? field.sub_fields.length : 0;
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
                        <div class="field-meta">${escapeHtml(field.key)}${isLayout ? ' <span class="field-subcount">(' + subCount + ' sub fields)</span>' : ''}</div>
                    </div>
                    <span class="field-type ${field.type}">${escapeHtml(field.type)}</span>
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
    const subFieldsGroup = document.getElementById('subFieldsGroup');
    
    optionsGroup.classList.toggle('visible', type === 'select' || type === 'radio');
    subFieldsGroup.classList.toggle('visible', type === 'repeater' || type === 'group');
}

function renderSubFields() {
    const list = document.getElementById('subFieldsList');
    if (currentSubFields.length === 0) {
        list.innerHTML = '';
        return;
    }
    
    let html = '';
    currentSubFields.forEach((sf, i) => {
        html += `
            <div class="sub-field-item">
                <div class="sub-field-info">
                    <div class="sub-field-label">${escapeHtml(sf.label)}</div>
                    <div class="sub-field-key">${escapeHtml(sf.key)}</div>
                </div>
                <span class="sub-field-type">${escapeHtml(sf.type)}</span>
                <button type="button" class="sub-field-edit" onclick="editSubField(${i})" title="Edit">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </button>
                <button type="button" class="sub-field-remove" onclick="removeSubField(${i})" title="Remove">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
        `;
    });
    list.innerHTML = html;
}

let editingSubFieldIndex = null;

function addSubField() {
    openSubFieldModal();
}

function editSubField(index) {
    openSubFieldModal(index);
}

function openSubFieldModal(index = null) {
    editingSubFieldIndex = index;
    
    document.getElementById('subFieldModalTitle').textContent = index !== null ? 'Edit Sub Field' : 'Add Sub Field';
    document.getElementById('subFieldLabel').value = '';
    document.getElementById('subFieldKey').value = '';
    document.getElementById('subFieldType').value = 'text';
    document.getElementById('subFieldOptions').value = '';
    
    if (index !== null && currentSubFields[index]) {
        const sf = currentSubFields[index];
        document.getElementById('subFieldLabel').value = sf.label || '';
        document.getElementById('subFieldKey').value = sf.key || '';
        document.getElementById('subFieldType').value = sf.type || 'text';
        document.getElementById('subFieldOptions').value = (sf.options || []).join('\n');
    }
    
    toggleSubFieldOptions();
    document.getElementById('subFieldModal').classList.add('active');
    document.getElementById('subFieldLabel').focus();
}

function closeSubFieldModal() {
    document.getElementById('subFieldModal').classList.remove('active');
    editingSubFieldIndex = null;
}

function toggleSubFieldOptions() {
    const type = document.getElementById('subFieldType').value;
    const optionsGroup = document.getElementById('subFieldOptionsGroup');
    optionsGroup.classList.toggle('visible', type === 'select' || type === 'radio');
}

function saveSubField() {
    const label = document.getElementById('subFieldLabel').value.trim();
    const key = document.getElementById('subFieldKey').value.trim();
    const type = document.getElementById('subFieldType').value;
    const options = document.getElementById('subFieldOptions').value.split('\n').map(o => o.trim()).filter(o => o);
    
    if (!label || !key) {
        document.getElementById('subFieldLabel').focus();
        return;
    }
    
    const subField = { label, key, type };
    
    if (type === 'select' || type === 'radio') {
        subField.options = options;
    }
    
    if (editingSubFieldIndex !== null) {
        currentSubFields[editingSubFieldIndex] = subField;
    } else {
        currentSubFields.push(subField);
    }
    
    renderSubFields();
    closeSubFieldModal();
}

// Auto-generate key from label for sub-fields
document.getElementById('subFieldLabel').addEventListener('input', function() {
    if (editingSubFieldIndex === null) {
        const key = this.value.toLowerCase()
            .replace(/[^a-z0-9\s]/g, '')
            .replace(/\s+/g, '_');
        document.getElementById('subFieldKey').value = key;
    }
});

function removeSubField(index) {
    currentSubFields.splice(index, 1);
    renderSubFields();
}

document.getElementById('btnAddField').addEventListener('click', function() {
    openFieldModal();
});

function openFieldModal(index = null) {
    editingFieldIndex = index;
    currentSubFields = [];
    
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
        currentSubFields = field.sub_fields ? JSON.parse(JSON.stringify(field.sub_fields)) : [];
    }
    
    toggleOptionsField();
    renderSubFields();
    document.getElementById('fieldModal').classList.add('active');
    document.getElementById('fieldLabel').focus();
}

function closeFieldModal() {
    document.getElementById('fieldModal').classList.remove('active');
    editingFieldIndex = null;
    currentSubFields = [];
}

function editField(index) {
    openFieldModal(index);
}

function deleteField(index) {
    fields.splice(index, 1);
    renderFields();
}

function saveField() {
    const label = document.getElementById('fieldLabel').value.trim();
    const key = document.getElementById('fieldKey').value.trim();
    const type = document.getElementById('fieldType').value;
    const options = document.getElementById('fieldOptions').value.split('\n').map(o => o.trim()).filter(o => o);
    const required = document.getElementById('fieldRequired').checked;
    
    if (!label || !key) {
        document.getElementById('fieldLabel').focus();
        return;
    }
    
    // Validate sub-fields for repeater/group
    if ((type === 'repeater' || type === 'group') && currentSubFields.length === 0) {
        document.getElementById('subFieldsGroup').scrollIntoView({ behavior: 'smooth' });
        return;
    }
    
    const field = { label, key, type, required };
    
    if (type === 'select' || type === 'radio') {
        field.options = options;
    }
    
    if (type === 'repeater' || type === 'group') {
        field.sub_fields = currentSubFields;
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

document.getElementById('subFieldModal').addEventListener('click', function(e) {
    if (e.target === this) closeSubFieldModal();
});

// Close modal on Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        if (document.getElementById('subFieldModal').classList.contains('active')) {
            closeSubFieldModal();
        } else {
            closeFieldModal();
        }
    }
});
</script>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
