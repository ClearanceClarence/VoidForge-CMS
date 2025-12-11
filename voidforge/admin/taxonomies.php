<?php
/**
 * Taxonomies Management - VoidForge CMS
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

$pageTitle = 'Taxonomies';
$currentPage = 'taxonomies';

// Handle delete
if (isset($_GET['delete']) && isset($_GET['csrf']) && verifyCsrf($_GET['csrf'])) {
    $id = (int)$_GET['delete'];
    $tax = Taxonomy::find($id);
    if ($tax) {
        Taxonomy::delete($id);
        redirect('taxonomies.php?deleted=1');
    }
}

$success = $_GET['success'] ?? null;
$deleted = isset($_GET['deleted']);

// Get all taxonomies
$allTaxonomies = Taxonomy::getAll();
$customTaxonomies = Taxonomy::getAllCustom();
$postTypes = Post::getTypes();

include ADMIN_PATH . '/includes/header.php';
?>

<div class="structure-page">
    <div class="structure-header">
        <h1>Taxonomies</h1>
        <a href="taxonomy-edit.php" class="btn-primary-action">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            New Taxonomy
        </a>
    </div>

    <?php if ($success === '1'): ?>
    <div class="alert alert-success">Taxonomy saved successfully.</div>
    <?php elseif ($deleted): ?>
    <div class="alert alert-success">Taxonomy deleted.</div>
    <?php endif; ?>

    <div class="section-label">Built-in Taxonomies</div>
    <div class="builtin-grid">
        <?php foreach ($allTaxonomies as $slug => $tax): ?>
        <?php if ($tax['builtin']): ?>
        <div class="builtin-card">
            <div class="builtin-icon" style="background: rgba(<?= $tax['hierarchical'] ? '99,102,241' : '16,185,129' ?>,0.1); color: <?= $tax['hierarchical'] ? '#6366f1' : '#10b981' ?>;">
                <?php if ($tax['hierarchical']): ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                </svg>
                <?php else: ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
                    <line x1="7" y1="7" x2="7.01" y2="7"/>
                </svg>
                <?php endif; ?>
            </div>
            <div class="builtin-info">
                <h4><?= esc($tax['label']) ?></h4>
                <p><?= Taxonomy::getTermCount($slug) ?> terms • <?= $tax['hierarchical'] ? 'Hierarchical' : 'Flat' ?></p>
            </div>
            <a href="terms.php?taxonomy=<?= esc($slug) ?>" class="btn btn-sm btn-secondary">Manage</a>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div class="section-label" style="margin-top: 2rem;">Custom Taxonomies</div>
    
    <?php if (empty($customTaxonomies)): ?>
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
            <line x1="7" y1="7" x2="7.01" y2="7"/>
        </svg>
        <h3>No Custom Taxonomies</h3>
        <p>Create custom taxonomies to organize your content.</p>
        <a href="taxonomy-edit.php" class="btn btn-primary">Create Taxonomy</a>
    </div>
    <?php else: ?>
    <div class="items-grid">
        <?php foreach ($customTaxonomies as $tax): ?>
        <div class="item-card">
            <div class="item-card-header">
                <div class="item-card-name">
                    <div class="item-card-icon" style="background: rgba(<?= $tax['hierarchical'] ? '99,102,241' : '16,185,129' ?>,0.1); color: <?= $tax['hierarchical'] ? '#6366f1' : '#10b981' ?>;">
                        <?php if ($tax['hierarchical']): ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                        </svg>
                        <?php else: ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
                            <line x1="7" y1="7" x2="7.01" y2="7"/>
                        </svg>
                        <?php endif; ?>
                    </div>
                    <div class="item-card-info">
                        <h3><?= esc($tax['name']) ?></h3>
                        <p class="item-card-slug"><?= esc($tax['slug']) ?></p>
                    </div>
                </div>
                <span class="badge"><?= $tax['hierarchical'] ? 'Hierarchical' : 'Flat' ?></span>
            </div>
            <div class="item-card-body">
                <div class="item-card-meta">
                    <span><strong><?= Taxonomy::getTermCount($tax['slug']) ?></strong> terms</span>
                    <?php if (!empty($tax['post_types'])): ?>
                    <span>
                        <?php 
                        $ptLabels = array_map(fn($pt) => $postTypes[$pt]['label'] ?? ucfirst($pt), $tax['post_types']);
                        echo esc(implode(', ', $ptLabels));
                        ?>
                    </span>
                    <?php endif; ?>
                </div>
                <div class="item-card-actions">
                    <a href="terms.php?taxonomy=<?= esc($tax['slug']) ?>" class="btn btn-sm btn-primary">Manage Terms</a>
                    <a href="taxonomy-edit.php?id=<?= $tax['id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteTaxonomy(<?= $tax['id'] ?>, '<?= esc($tax['name']) ?>')">Delete</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function deleteTaxonomy(id, name) {
    if (confirm('Delete taxonomy "' + name + '"?\n\nThis will also delete all terms in this taxonomy. This cannot be undone.')) {
        window.location.href = 'taxonomies.php?delete=' + id + '&csrf=<?= csrfToken() ?>';
    }
}
</script>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
