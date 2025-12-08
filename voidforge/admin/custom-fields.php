<?php
/**
 * Custom Fields Manager - VoidForge CMS
 */

define('CMS_ROOT', dirname(__DIR__));
require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/user.php';
require_once CMS_ROOT . '/includes/post.php';
require_once CMS_ROOT . '/includes/plugin.php';

Post::init();
User::startSession();
User::requireRole('admin');

$currentPage = 'custom-fields';
$pageTitle = 'Custom Fields';

// Get field groups
$fieldGroups = getOption('custom_field_groups', []);

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_group']) && verifyCsrf()) {
    $groupId = $_POST['delete_group'];
    if (isset($fieldGroups[$groupId])) {
        unset($fieldGroups[$groupId]);
        setOption('custom_field_groups', $fieldGroups);
        setFlash('success', 'Field group deleted successfully.');
        redirect(ADMIN_URL . '/custom-fields.php');
    }
}

include ADMIN_PATH . '/includes/header.php';
?>

<div class="structure-page">
    <div class="structure-header">
        <h1>Custom Fields</h1>
        <a href="<?= ADMIN_URL ?>/custom-field-edit.php" class="btn-primary-action">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            New Field Group
        </a>
    </div>
    
    <div class="info-box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
        </svg>
        <div class="info-box-content">
            <h4>About Field Groups</h4>
            <p>Field groups let you add custom fields to posts, pages, and users. Create a group, add fields, then assign it to content types.</p>
        </div>
    </div>
    
    <?php if (empty($fieldGroups)): ?>
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <rect x="3" y="3" width="7" height="7"></rect>
            <rect x="14" y="3" width="7" height="7"></rect>
            <rect x="14" y="14" width="7" height="7"></rect>
            <rect x="3" y="14" width="7" height="7"></rect>
        </svg>
        <h2>No Field Groups Yet</h2>
        <p>Create your first field group to add custom fields to your content types.</p>
        <a href="<?= ADMIN_URL ?>/custom-field-edit.php" class="btn-primary-action">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Create Field Group
        </a>
    </div>
    <?php else: ?>
    
    <div class="section-label">Field Groups (<?= count($fieldGroups) ?>)</div>
    <div class="items-grid">
        <?php foreach ($fieldGroups as $groupId => $group): ?>
        <div class="item-card">
            <div class="item-card-header">
                <div class="item-card-info">
                    <h3><?= esc($group['title']) ?></h3>
                    <p><?= count($group['fields'] ?? []) ?> field<?= count($group['fields'] ?? []) !== 1 ? 's' : '' ?></p>
                </div>
                <div class="item-actions">
                    <a href="<?= ADMIN_URL ?>/custom-field-edit.php?id=<?= esc($groupId) ?>" class="item-btn" title="Edit">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </a>
                    <button type="button" class="item-btn delete" onclick="confirmDelete('<?= esc($groupId) ?>', '<?= esc(addslashes($group['title'])) ?>')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="item-card-body">
                <!-- Locations -->
                <div class="item-tags" style="margin-bottom: 1rem;">
                    <?php 
                    $locations = $group['locations'] ?? [];
                    $postTypes = getOption('custom_post_types', []);
                    foreach ($locations as $loc): 
                        $label = $loc;
                        if ($loc === 'post') $label = 'Posts';
                        elseif ($loc === 'page') $label = 'Pages';
                        elseif ($loc === 'user') $label = 'Users';
                        elseif (isset($postTypes[$loc])) $label = $postTypes[$loc]['label_plural'] ?? $loc;
                    ?>
                    <span class="item-tag primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <?php if ($loc === 'user'): ?>
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                            <?php else: ?>
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <?php endif; ?>
                        </svg>
                        <?= esc($label) ?>
                    </span>
                    <?php endforeach; ?>
                    <?php if (empty($locations)): ?>
                    <span class="item-tag">No locations assigned</span>
                    <?php endif; ?>
                </div>
                
                <!-- Fields Preview -->
                <div class="item-tags">
                    <?php 
                    $fields = $group['fields'] ?? [];
                    $displayFields = array_slice($fields, 0, 5);
                    foreach ($displayFields as $field): 
                    ?>
                    <span class="item-tag">
                        <?= esc($field['label']) ?>
                        <span class="item-tag-type"><?= esc($field['type']) ?></span>
                    </span>
                    <?php endforeach; ?>
                    <?php if (count($fields) > 5): ?>
                    <span class="item-tag">+<?= count($fields) - 5 ?> more</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
        <h3>Delete Field Group?</h3>
        <p>Are you sure you want to delete "<span id="deleteGroupName"></span>"? This cannot be undone.</p>
        <div class="modal-actions">
            <button type="button" class="btn-modal-cancel" onclick="closeDeleteModal()">Cancel</button>
            <form method="POST" id="deleteForm" style="display: inline;">
                <?= csrfField() ?>
                <input type="hidden" name="delete_group" id="deleteGroupId">
                <button type="submit" class="btn-modal-danger">Delete</button>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDelete(groupId, name) {
    document.getElementById('deleteGroupId').value = groupId;
    document.getElementById('deleteGroupName').textContent = name;
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
}

document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeDeleteModal();
});
</script>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
