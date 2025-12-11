<?php
/**
 * Terms Management - VoidForge CMS
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

$taxonomySlug = $_GET['taxonomy'] ?? '';
$taxonomy = Taxonomy::get($taxonomySlug);

if (!$taxonomy) {
    redirect('taxonomies.php');
}

$pageTitle = $taxonomy['label'];
$currentPage = 'taxonomies';

// AJAX handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        echo json_encode(['success' => false, 'error' => 'Invalid token']);
        exit;
    }
    
    $action = $_POST['ajax_action'];
    
    switch ($action) {
        case 'create_term':
            $name = trim($_POST['name'] ?? '');
            if (empty($name)) {
                echo json_encode(['success' => false, 'error' => 'Name is required']);
                exit;
            }
            
            $id = Taxonomy::createTerm($taxonomySlug, [
                'name' => $name,
                'slug' => $_POST['slug'] ?? '',
                'description' => $_POST['description'] ?? '',
                'parent_id' => (int)($_POST['parent_id'] ?? 0),
            ]);
            
            $term = Taxonomy::findTerm($id);
            echo json_encode(['success' => true, 'term' => $term]);
            exit;
            
        case 'update_term':
            $id = (int)$_POST['id'];
            Taxonomy::updateTerm($id, [
                'name' => $_POST['name'] ?? '',
                'slug' => $_POST['slug'] ?? '',
                'description' => $_POST['description'] ?? '',
                'parent_id' => (int)($_POST['parent_id'] ?? 0),
            ]);
            echo json_encode(['success' => true]);
            exit;
            
        case 'delete_term':
            $id = (int)$_POST['id'];
            Taxonomy::deleteTerm($id);
            Taxonomy::updateTermCounts($taxonomySlug);
            echo json_encode(['success' => true]);
            exit;
            
        case 'get_term':
            $id = (int)$_POST['id'];
            $term = Taxonomy::findTerm($id);
            echo json_encode(['success' => true, 'term' => $term]);
            exit;
    }
    
    echo json_encode(['success' => false, 'error' => 'Unknown action']);
    exit;
}

$terms = $taxonomy['hierarchical'] 
    ? Taxonomy::getTermsTree($taxonomySlug) 
    : Taxonomy::getTerms($taxonomySlug);

$allTerms = Taxonomy::getTerms($taxonomySlug);
$termCount = count($allTerms);

include ADMIN_PATH . '/includes/header.php';
?>

<div class="structure-page" style="max-width: 1100px;">
    <div class="structure-header">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="taxonomies.php" class="btn btn-secondary btn-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1><?= esc($taxonomy['label']) ?></h1>
                <p style="margin: 0.25rem 0 0; font-size: 0.875rem; color: var(--text-muted);">
                    <?= $termCount ?> <?= $termCount === 1 ? 'term' : 'terms' ?> • 
                    <?= $taxonomy['hierarchical'] ? 'Hierarchical' : 'Flat' ?>
                </p>
            </div>
        </div>
    </div>

    <div class="terms-layout">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Add New <?= esc($taxonomy['singular']) ?></h2>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Name</label>
                    <input type="text" id="termName" class="form-input" placeholder="Enter name">
                </div>
                <div class="form-group">
                    <label class="form-label">Slug</label>
                    <input type="text" id="termSlug" class="form-input" placeholder="Auto-generated">
                    <div class="form-hint">URL-friendly identifier</div>
                </div>
                <?php if ($taxonomy['hierarchical']): ?>
                <div class="form-group">
                    <label class="form-label">Parent</label>
                    <select id="termParent" class="form-select">
                        <option value="0">— None —</option>
                        <?php 
                        function renderParentOptions($terms, $depth = 0) {
                            foreach ($terms as $term) {
                                echo '<option value="' . $term['id'] . '">' . str_repeat('— ', $depth) . esc($term['name']) . '</option>';
                                if (!empty($term['children'])) {
                                    renderParentOptions($term['children'], $depth + 1);
                                }
                            }
                        }
                        renderParentOptions($terms);
                        ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea id="termDescription" class="form-textarea" rows="3" placeholder="Optional description"></textarea>
                </div>
            </div>
            <div class="card-footer">
                <button type="button" class="btn btn-primary" onclick="addTerm()" style="width: 100%;">
                    Add <?= esc($taxonomy['singular']) ?>
                </button>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">All <?= esc($taxonomy['label']) ?></h2>
            </div>
            
            <?php if (empty($terms)): ?>
            <div class="empty-state" style="padding: 3rem 2rem;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
                    <line x1="7" y1="7" x2="7.01" y2="7"/>
                </svg>
                <h3>No <?= esc(strtolower($taxonomy['label'])) ?> yet</h3>
                <p>Add your first <?= esc(strtolower($taxonomy['singular'])) ?> using the form.</p>
            </div>
            <?php else: ?>
            <div class="terms-list" id="termsList">
                <?php 
                function renderTerms($terms, $taxonomySlug, $depth = 0) {
                    foreach ($terms as $term): 
                ?>
                <div class="term-item" data-id="<?= $term['id'] ?>" style="padding-left: <?= $depth * 1.5 ?>rem;">
                    <div class="term-info">
                        <span class="term-name"><?= esc($term['name']) ?></span>
                        <span class="term-slug"><?= esc($term['slug']) ?></span>
                    </div>
                    <span class="badge badge-info"><?= (int)$term['count'] ?></span>
                    <div class="term-actions">
                        <button class="btn btn-sm btn-secondary btn-icon" onclick="editTerm(<?= $term['id'] ?>)" title="Edit">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                        </button>
                        <button class="btn btn-sm btn-danger btn-icon" onclick="deleteTerm(<?= $term['id'] ?>, '<?= esc($term['name']) ?>')" title="Delete">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <?php 
                    if (!empty($term['children'])) {
                        renderTerms($term['children'], $taxonomySlug, $depth + 1);
                    }
                    endforeach; 
                }
                renderTerms($terms, $taxonomySlug);
                ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal-box" style="max-width: 450px;">
        <div class="modal-header">
            <h2 class="modal-title">Edit <?= esc($taxonomy['singular']) ?></h2>
            <button class="modal-close" onclick="closeModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="editTermId">
            <div class="form-group">
                <label class="form-label">Name</label>
                <input type="text" id="editTermName" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">Slug</label>
                <input type="text" id="editTermSlug" class="form-input">
            </div>
            <?php if ($taxonomy['hierarchical']): ?>
            <div class="form-group">
                <label class="form-label">Parent</label>
                <select id="editTermParent" class="form-select">
                    <option value="0">— None —</option>
                    <?php renderParentOptions($terms); ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea id="editTermDescription" class="form-textarea" rows="3"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            <button class="btn btn-primary" onclick="saveTerm()">Save Changes</button>
        </div>
    </div>
</div>

<style>
.terms-layout {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 1.5rem;
}

@media (max-width: 900px) {
    .terms-layout { grid-template-columns: 1fr; }
}

.card-footer {
    padding: 1rem 1.25rem;
    background: var(--bg-card-header);
    border-top: 1px solid var(--border-color);
}

.terms-list {
    max-height: 500px;
    overflow-y: auto;
}

.term-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 1.25rem;
    border-bottom: 1px solid var(--border-color);
    transition: background 0.15s;
}

.term-item:last-child { border-bottom: none; }
.term-item:hover { background: var(--bg-hover); }

.term-info {
    flex: 1;
    min-width: 0;
}

.term-name {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-primary);
}

.term-slug {
    display: block;
    font-size: 0.6875rem;
    color: var(--text-muted);
    font-family: 'JetBrains Mono', monospace;
}

.term-actions {
    display: flex;
    gap: 0.25rem;
}

.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal-overlay.open { display: flex; }
</style>

<script>
var csrfToken = '<?= csrfToken() ?>';
var taxonomySlug = '<?= esc($taxonomySlug) ?>';
var isHierarchical = <?= $taxonomy['hierarchical'] ? 'true' : 'false' ?>;

function addTerm() {
    var name = document.getElementById('termName').value.trim();
    if (!name) {
        showToast('Name is required', 'error');
        return;
    }
    
    var data = {
        ajax_action: 'create_term',
        name: name,
        slug: document.getElementById('termSlug').value.trim(),
        description: document.getElementById('termDescription').value.trim(),
        parent_id: isHierarchical ? document.getElementById('termParent').value : 0
    };
    
    ajaxPost(data, function(r) {
        if (r.success) {
            showToast('Term created', 'success');
            setTimeout(function() { location.reload(); }, 500);
        } else {
            showToast(r.error || 'Error', 'error');
        }
    });
}

function editTerm(id) {
    ajaxPost({ ajax_action: 'get_term', id: id }, function(r) {
        if (r.success && r.term) {
            document.getElementById('editTermId').value = r.term.id;
            document.getElementById('editTermName').value = r.term.name;
            document.getElementById('editTermSlug').value = r.term.slug;
            document.getElementById('editTermDescription').value = r.term.description || '';
            if (isHierarchical) {
                document.getElementById('editTermParent').value = r.term.parent_id || 0;
            }
            document.getElementById('editModal').classList.add('open');
        }
    });
}

function saveTerm() {
    var id = document.getElementById('editTermId').value;
    var data = {
        ajax_action: 'update_term',
        id: id,
        name: document.getElementById('editTermName').value.trim(),
        slug: document.getElementById('editTermSlug').value.trim(),
        description: document.getElementById('editTermDescription').value.trim(),
        parent_id: isHierarchical ? document.getElementById('editTermParent').value : 0
    };
    
    ajaxPost(data, function(r) {
        if (r.success) {
            showToast('Term updated', 'success');
            setTimeout(function() { location.reload(); }, 500);
        } else {
            showToast(r.error || 'Error', 'error');
        }
    });
}

function deleteTerm(id, name) {
    if (!confirm('Delete "' + name + '"? This cannot be undone.')) return;
    
    ajaxPost({ ajax_action: 'delete_term', id: id }, function(r) {
        if (r.success) {
            var el = document.querySelector('.term-item[data-id="' + id + '"]');
            if (el) el.remove();
            showToast('Term deleted', 'success');
        }
    });
}

function closeModal() {
    document.getElementById('editModal').classList.remove('open');
}

function ajaxPost(data, cb) {
    data.csrf = csrfToken;
    var fd = new FormData();
    for (var k in data) fd.append(k, data[k]);
    fetch('terms.php?taxonomy=' + taxonomySlug, { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(cb)
        .catch(function() { showToast('Error', 'error'); });
}

function showToast(msg, type) {
    var old = document.querySelector('.toast');
    if (old) old.remove();
    
    var t = document.createElement('div');
    t.className = 'toast toast-' + type;
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(function() { t.remove(); }, 3000);
}

// Enter key to add term
document.getElementById('termName').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') addTerm();
});

// Close modal on background click
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
