<?php
/**
 * Create/Edit Taxonomy - VoidForge CMS
 */

define('CMS_ROOT', dirname(__DIR__));
require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/user.php';
require_once CMS_ROOT . '/includes/post.php';
require_once CMS_ROOT . '/includes/plugin.php';
require_once CMS_ROOT . '/includes/taxonomy.php';

Post::init();
Plugin::init();
Taxonomy::init();

User::startSession();
User::requireRole('admin');

// Check if taxonomy tables exist
if (!Taxonomy::tablesExist()) {
    setFlash('warning', 'Taxonomy tables not found. Please run the system update.');
    redirect('update.php');
}

$id = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;
$taxonomy = $isEdit ? Taxonomy::find($id) : null;

if ($isEdit && !$taxonomy) {
    redirect('taxonomies.php');
}

$pageTitle = $isEdit ? 'Edit Taxonomy' : 'New Taxonomy';
$currentPage = 'taxonomies';

$errors = [];
$formData = [
    'name' => $taxonomy['name'] ?? '',
    'singular' => $taxonomy['singular'] ?? '',
    'slug' => $taxonomy['slug'] ?? '',
    'description' => $taxonomy['description'] ?? '',
    'hierarchical' => $taxonomy['hierarchical'] ?? 0,
    'post_types' => $taxonomy['post_types'] ?? [],
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid security token.';
    } else {
        $formData = [
            'name' => trim($_POST['name'] ?? ''),
            'singular' => trim($_POST['singular'] ?? ''),
            'slug' => trim($_POST['slug'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'hierarchical' => isset($_POST['hierarchical']),
            'post_types' => $_POST['post_types'] ?? [],
        ];
        
        if (empty($formData['name'])) {
            $errors[] = 'Taxonomy name is required.';
        }
        
        if (empty($formData['singular'])) {
            $formData['singular'] = rtrim($formData['name'], 's');
        }
        
        if (empty($formData['post_types'])) {
            $errors[] = 'Select at least one post type.';
        }
        
        // Check for reserved slugs
        $reservedSlugs = ['category', 'tag', 'post', 'page', 'admin', 'wp-admin', 'login'];
        $checkSlug = strtolower($formData['slug'] ?: preg_replace('/[^a-zA-Z0-9]+/', '_', $formData['name']));
        if (!$isEdit && in_array($checkSlug, $reservedSlugs)) {
            $errors[] = 'This slug is reserved. Please choose a different name or slug.';
        }
        
        if (empty($errors)) {
            try {
                if ($isEdit) {
                    Taxonomy::update($id, $formData);
                } else {
                    $id = Taxonomy::create($formData);
                }
                redirect('taxonomies.php?success=1');
            } catch (Exception $e) {
                $errors[] = 'Failed to save taxonomy: ' . $e->getMessage();
            }
        }
    }
}

$postTypes = Post::getTypes();

include ADMIN_PATH . '/includes/header.php';
?>

<div class="structure-page">
    <div class="structure-header">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="taxonomies.php" class="btn btn-secondary btn-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1><?= $pageTitle ?></h1>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <strong>Please fix the following errors:</strong>
        <ul style="margin: 0.5rem 0 0 1.25rem; padding: 0;">
            <?php foreach ($errors as $error): ?>
            <li><?= esc($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
        
        <div class="card" style="margin-bottom: 1.5rem;">
            <div class="card-header">
                <h2 class="card-title">Basic Information</h2>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Name (Plural) <span class="required">*</span></label>
                        <input type="text" name="name" class="form-input" value="<?= esc($formData['name']) ?>" placeholder="e.g. Genres" required>
                        <div class="form-hint">Plural name shown in admin menus</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Singular Name</label>
                        <input type="text" name="singular" id="singular" class="form-input" value="<?= esc($formData['singular']) ?>" placeholder="e.g. Genre">
                        <div class="form-hint">Used for single term references</div>
                    </div>
                </div>
                
                <?php if (!$isEdit): ?>
                <div class="form-group">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" class="form-input" value="<?= esc($formData['slug']) ?>" placeholder="auto-generated from name">
                    <div class="form-hint">URL-friendly identifier. Leave blank to auto-generate.</div>
                </div>
                <?php else: ?>
                <div class="form-group">
                    <label class="form-label">Slug</label>
                    <input type="text" class="form-input" value="<?= esc($taxonomy['slug']) ?>" disabled style="opacity: 0.6;">
                    <div class="form-hint">Slug cannot be changed after creation.</div>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-textarea" rows="3" placeholder="Optional description..."><?= esc($formData['description']) ?></textarea>
                </div>
            </div>
        </div>
        
        <div class="card" style="margin-bottom: 1.5rem;">
            <div class="card-header">
                <h2 class="card-title">Settings</h2>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="checkbox-option">
                        <input type="checkbox" name="hierarchical" value="1" <?= $formData['hierarchical'] ? 'checked' : '' ?>>
                        <div class="checkbox-option-content">
                            <strong>Hierarchical</strong>
                            <span>Allow parent/child relationships like categories. Uncheck for flat structure like tags.</span>
                        </div>
                    </label>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Post Types <span class="required">*</span></label>
                    <div class="form-hint" style="margin-bottom: 0.75rem;">Select which post types can use this taxonomy.</div>
                    <div class="checkbox-grid">
                        <?php foreach ($postTypes as $slug => $pt): ?>
                        <label class="checkbox-option compact">
                            <input type="checkbox" name="post_types[]" value="<?= esc($slug) ?>" <?= in_array($slug, $formData['post_types']) ? 'checked' : '' ?>>
                            <span><?= esc($pt['label']) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-lg">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                    <polyline points="17 21 17 13 7 13 7 21"/>
                    <polyline points="7 3 7 8 15 8"/>
                </svg>
                <?= $isEdit ? 'Save Changes' : 'Create Taxonomy' ?>
            </button>
            <a href="taxonomies.php" class="btn btn-secondary btn-lg">Cancel</a>
        </div>
    </form>
</div>

<style>
.checkbox-option {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 1rem;
    background: var(--bg-card-header);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: border-color 0.15s;
}

.checkbox-option:hover {
    border-color: var(--forge-primary);
}

.checkbox-option input[type="checkbox"] {
    margin-top: 0.125rem;
    width: 18px;
    height: 18px;
    accent-color: var(--forge-primary);
}

.checkbox-option-content {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.checkbox-option-content strong {
    font-size: 0.875rem;
    color: var(--text-primary);
}

.checkbox-option-content span {
    font-size: 0.8125rem;
    color: var(--text-muted);
}

.checkbox-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 0.5rem;
}

.checkbox-option.compact {
    padding: 0.625rem 0.875rem;
    align-items: center;
}

.checkbox-option.compact span {
    font-size: 0.8125rem;
    color: var(--text-primary);
}

.form-actions {
    display: flex;
    gap: 0.75rem;
    padding-top: 0.5rem;
}

.required { color: var(--forge-danger); }
</style>

<script>
// Auto-fill singular from name
document.querySelector('input[name="name"]').addEventListener('input', function() {
    const singular = document.getElementById('singular');
    if (!singular.dataset.touched) {
        let name = this.value.trim();
        if (name.endsWith('ies')) {
            singular.value = name.slice(0, -3) + 'y';
        } else if (name.endsWith('es')) {
            singular.value = name.slice(0, -2);
        } else if (name.endsWith('s')) {
            singular.value = name.slice(0, -1);
        } else {
            singular.value = name;
        }
    }
});

document.getElementById('singular').addEventListener('input', function() {
    this.dataset.touched = 'true';
});
</script>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
