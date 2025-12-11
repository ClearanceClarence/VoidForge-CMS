<?php
/**
 * Menu Builder - VoidForge CMS
 */

define('CMS_ROOT', dirname(__DIR__));
require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/user.php';
require_once CMS_ROOT . '/includes/post.php';
require_once CMS_ROOT . '/includes/plugin.php';
require_once CMS_ROOT . '/includes/menu.php';

Post::init();
Plugin::init();
Menu::init();

User::startSession();

// Handle AJAX requests first, before any potential redirects
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    
    // Check auth for AJAX
    if (!User::isLoggedIn() || !User::hasRole('admin')) {
        echo json_encode(['success' => false, 'error' => 'Not authorized']);
        exit;
    }
    
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        echo json_encode(['success' => false, 'error' => 'Invalid token']);
        exit;
    }
    
    $action = $_POST['ajax_action'];
    $menuId = (int)($_POST['menu_id'] ?? $_GET['menu'] ?? 0);
    
    try {
        switch ($action) {
            case 'create_menu':
                $name = trim($_POST['name'] ?? '');
                if (empty($name)) {
                    echo json_encode(['success' => false, 'error' => 'Menu name is required']);
                    exit;
                }
                $id = Menu::create(['name' => $name]);
                echo json_encode(['success' => true, 'id' => $id]);
                exit;
                
            case 'update_menu':
                $id = (int)$_POST['id'];
                $name = trim($_POST['name'] ?? '');
                $location = $_POST['location'] ?? '';
                Menu::update($id, ['name' => $name, 'location' => $location]);
                echo json_encode(['success' => true]);
                exit;
                
            case 'delete_menu':
                $id = (int)$_POST['id'];
                Menu::delete($id);
                echo json_encode(['success' => true]);
                exit;
                
            case 'add_item':
                $type = $_POST['type'] ?? 'custom';
                $objectId = $_POST['object_id'] ?? null;
                
                // Check for duplicates (except custom links)
                if ($type !== 'custom' && $objectId && Menu::itemExists($menuId, $type, $objectId)) {
                    echo json_encode(['success' => false, 'error' => 'This item is already in the menu']);
                    exit;
                }
                
                $itemId = Menu::addItem($menuId, [
                    'title' => $_POST['title'] ?? 'Menu Item',
                    'type' => $type,
                    'object_id' => $objectId,
                    'url' => $_POST['url'] ?? '',
                    'target' => $_POST['target'] ?? '_self',
                ]);
                $table = Database::table('menu_items');
                $item = Database::queryOne("SELECT * FROM {$table} WHERE id = ?", [$itemId]);
                echo json_encode(['success' => true, 'item' => $item]);
                exit;
                
            case 'update_item':
                $id = (int)$_POST['id'];
                Menu::updateItem($id, [
                    'title' => $_POST['title'] ?? '',
                    'url' => $_POST['url'] ?? '',
                    'target' => $_POST['target'] ?? '_self',
                    'css_class' => $_POST['css_class'] ?? '',
                ]);
                echo json_encode(['success' => true]);
                exit;
                
            case 'delete_item':
                $id = (int)$_POST['id'];
                Menu::deleteItem($id);
                echo json_encode(['success' => true]);
                exit;
                
            case 'save_order':
                $items = json_decode($_POST['items'] ?? '[]', true);
                Menu::saveOrder($menuId, $items);
                echo json_encode(['success' => true]);
                exit;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action]);
                exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Normal page load - require admin
User::requireRole('admin');

$pageTitle = 'Menus';
$currentPage = 'menus';

$menuId = (int)($_GET['menu'] ?? 0);
$currentMenu = $menuId ? Menu::find($menuId) : null;

$menus = Menu::getAll();
$locations = Menu::getLocations();
$menuItems = $currentMenu ? Menu::getItems($menuId) : [];
$availablePages = Menu::getAvailablePages();
$availablePosts = Menu::getAvailablePosts();
$availablePostTypes = Menu::getAvailablePostTypes();

include ADMIN_PATH . '/includes/header.php';
?>

<style>
/* Menu Builder Layout */
.menu-builder {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 1.5rem;
    max-width: 1400px;
}

@media (max-width: 900px) {
    .menu-builder { grid-template-columns: 1fr; }
}

/* Left Panel - Add Items */
.menu-add-panel {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.add-panel-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

.add-panel-header {
    padding: 0.75rem 1rem;
    background: var(--bg-card-header);
    border-bottom: 1px solid var(--border-color);
    font-weight: 600;
    font-size: 0.8125rem;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    transition: background var(--transition);
}

.add-panel-header:hover {
    background: var(--bg-hover);
}

.add-panel-header svg {
    color: var(--text-muted);
    transition: transform var(--transition);
}

.add-panel-card.collapsed .add-panel-header svg {
    transform: rotate(-90deg);
}

.add-panel-body {
    padding: 0.75rem;
    max-height: 250px;
    overflow-y: auto;
    background: var(--bg-card);
}

.add-panel-card.collapsed .add-panel-body,
.add-panel-card.collapsed .add-panel-footer {
    display: none;
}

.add-item-list {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.add-item-checkbox {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    padding: 0.5rem;
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: background var(--transition);
}

.add-item-checkbox:hover {
    background: var(--bg-hover);
}

.add-item-checkbox input {
    width: 15px;
    height: 15px;
    accent-color: var(--forge-primary);
}

.add-item-checkbox label {
    font-size: 0.8125rem;
    color: var(--text-secondary);
    cursor: pointer;
}

.add-panel-footer {
    padding: 0.625rem 0.75rem;
    border-top: 1px solid var(--border-color);
    background: var(--bg-card-header);
}

.btn-add-to-menu {
    width: 100%;
    padding: 0.5rem;
    background: var(--forge-primary);
    color: var(--text-inverse);
    border: none;
    border-radius: var(--border-radius);
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    transition: background var(--transition);
}

.btn-add-to-menu:hover {
    background: var(--forge-primary-dark);
}

.btn-add-to-menu:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.custom-link-form {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.custom-link-form input {
    padding: 0.5rem 0.625rem;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    background: var(--bg-input);
    color: var(--text-primary);
    font-size: 0.8125rem;
    transition: border-color var(--transition), box-shadow var(--transition);
}

.custom-link-form input:focus {
    outline: none;
    border-color: var(--forge-primary);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

/* Right Panel */
.menu-structure-panel {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.menu-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}

.menu-header h1 {
    font-size: 1.375rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
}

.menu-selector {
    display: flex;
    align-items: center;
    gap: 0.625rem;
}

.menu-selector select {
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    background: var(--bg-input);
    color: var(--text-primary);
    font-size: 0.8125rem;
    min-width: 160px;
    transition: border-color var(--transition);
}

.menu-selector select:focus {
    outline: none;
    border-color: var(--forge-primary);
}

.btn-new-menu {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 0.875rem;
    background: var(--forge-primary);
    color: var(--text-inverse);
    border: none;
    border-radius: var(--border-radius);
    font-size: 0.8125rem;
    font-weight: 600;
    cursor: pointer;
    transition: background var(--transition);
}

.btn-new-menu:hover {
    background: var(--forge-primary-dark);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    background: var(--bg-card);
    border: 2px dashed var(--border-color);
    border-radius: var(--border-radius-xl);
}

.empty-state svg {
    width: 56px;
    height: 56px;
    color: var(--text-muted);
    margin-bottom: 1rem;
}

.empty-state h2 {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0 0 0.5rem 0;
}

.empty-state p {
    color: var(--text-muted);
    margin: 0 0 1.5rem 0;
    font-size: 0.875rem;
}

.btn-create-menu {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    background: var(--forge-primary);
    color: var(--text-inverse);
    border: none;
    border-radius: var(--border-radius);
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: background var(--transition);
}

.btn-create-menu:hover {
    background: var(--forge-primary-dark);
}

/* Menu Structure Card */
.menu-structure-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

.menu-structure-header {
    padding: 0.875rem 1rem;
    background: var(--bg-card-header);
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.menu-structure-header h2 {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.menu-structure-header span {
    font-size: 0.75rem;
    color: var(--text-muted);
}

.menu-structure-body {
    padding: 1rem;
    min-height: 120px;
    background: var(--bg-card);
}

.menu-empty {
    text-align: center;
    padding: 1.5rem;
    color: var(--text-muted);
}

.menu-empty svg {
    width: 36px;
    height: 36px;
    margin-bottom: 0.5rem;
}

.menu-empty h3 {
    font-size: 0.875rem;
    color: var(--text-secondary);
    margin: 0 0 0.25rem 0;
}

.menu-empty p {
    font-size: 0.75rem;
    margin: 0;
}

/* Menu Items */
.menu-items {
    list-style: none;
    padding: 0;
    margin: 0;
}

.menu-items .menu-items {
    margin-left: 1.25rem;
    margin-top: 0.375rem;
    padding-left: 0.75rem;
    border-left: 2px solid var(--border-color);
}

.menu-item {
    margin-bottom: 0.375rem;
}

.menu-item-content {
    background: var(--bg-card-header);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    transition: border-color var(--transition);
}

.menu-item-content:hover {
    border-color: var(--forge-primary);
}

.menu-item-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 0.75rem;
}

.menu-item-drag {
    color: var(--text-muted);
    cursor: grab;
    display: flex;
}

.menu-item-title {
    flex: 1;
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--text-primary);
}

.menu-item-type {
    font-size: 0.625rem;
    text-transform: uppercase;
    color: var(--forge-primary);
    background: rgba(99, 102, 241, 0.1);
    padding: 0.125rem 0.375rem;
    border-radius: 3px;
    font-weight: 600;
}

.menu-item-toggle {
    padding: 0.25rem;
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    display: flex;
    border-radius: 4px;
    transition: background var(--transition);
}

.menu-item-toggle:hover {
    background: var(--border-color);
}

.menu-item-toggle svg {
    transition: transform var(--transition);
}

.menu-item-content.expanded .menu-item-toggle svg {
    transform: rotate(180deg);
}

.menu-item-details {
    display: none;
    padding: 0.75rem;
    border-top: 1px solid var(--border-color);
    background: var(--bg-card);
}

.menu-item-content.expanded .menu-item-details {
    display: block;
}

.menu-item-field {
    margin-bottom: 0.625rem;
}

.menu-item-field label {
    display: block;
    font-size: 0.625rem;
    font-weight: 600;
    color: var(--text-muted);
    margin-bottom: 0.25rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.menu-item-field input,
.menu-item-field select {
    width: 100%;
    padding: 0.4375rem 0.625rem;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    background: var(--bg-input);
    color: var(--text-primary);
    font-size: 0.8125rem;
    transition: border-color var(--transition);
}

.menu-item-field input:focus,
.menu-item-field select:focus {
    outline: none;
    border-color: var(--forge-primary);
}

.menu-item-actions {
    display: flex;
    gap: 0.375rem;
    margin-top: 0.75rem;
    padding-top: 0.625rem;
    border-top: 1px solid var(--border-color);
}

.btn-item-save {
    padding: 0.375rem 0.625rem;
    background: var(--forge-primary);
    color: var(--text-inverse);
    border: none;
    border-radius: var(--border-radius);
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    transition: background var(--transition);
}

.btn-item-save:hover {
    background: var(--forge-primary-dark);
}

.btn-item-delete {
    padding: 0.375rem 0.625rem;
    background: rgba(239, 68, 68, 0.1);
    color: var(--forge-danger);
    border: none;
    border-radius: var(--border-radius);
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    margin-left: auto;
    transition: background var(--transition);
}

.btn-item-delete:hover {
    background: rgba(239, 68, 68, 0.15);
}

/* Menu Settings */
.menu-settings-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

.menu-settings-header {
    padding: 0.875rem 1rem;
    background: var(--bg-card-header);
    border-bottom: 1px solid var(--border-color);
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--text-primary);
}

.menu-settings-body {
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    background: var(--bg-card);
}

.menu-setting-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.menu-setting-row label {
    font-size: 0.8125rem;
    font-weight: 500;
    min-width: 100px;
    color: var(--text-secondary);
}

.menu-setting-row input,
.menu-setting-row select {
    flex: 1;
    padding: 0.4375rem 0.625rem;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    background: var(--bg-input);
    color: var(--text-primary);
    font-size: 0.8125rem;
}

.menu-actions {
    display: flex;
    gap: 0.625rem;
    padding: 0.875rem 1rem;
    border-top: 1px solid var(--border-color);
    background: var(--bg-card-header);
}

.btn-save-menu {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 1rem;
    background: var(--forge-success);
    color: var(--text-inverse);
    border: none;
    border-radius: var(--border-radius);
    font-size: 0.8125rem;
    font-weight: 600;
    cursor: pointer;
    transition: filter var(--transition);
}

.btn-save-menu:hover {
    filter: brightness(0.9);
}

.btn-delete-menu {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 1rem;
    background: rgba(239, 68, 68, 0.1);
    color: var(--forge-danger);
    border: 1px solid rgba(239, 68, 68, 0.2);
    border-radius: var(--border-radius);
    font-size: 0.8125rem;
    font-weight: 600;
    cursor: pointer;
    margin-left: auto;
    transition: background var(--transition);
}

.btn-delete-menu:hover {
    background: rgba(239, 68, 68, 0.15);
}

/* Modal */
.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal-overlay.open {
    display: flex;
}

.modal {
    background: var(--bg-card);
    border-radius: var(--border-radius-lg);
    width: 100%;
    max-width: 380px;
    border: 1px solid var(--border-color);
    box-shadow: var(--shadow-xl);
}

.modal-header {
    padding: 0.875rem 1rem;
    background: var(--bg-card-header);
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal-header h2 {
    font-size: 0.9375rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    padding: 0.25rem;
    display: flex;
    transition: color var(--transition);
}

.modal-close:hover {
    color: var(--text-primary);
}

.modal-body {
    padding: 1rem;
    background: var(--bg-card);
}

.modal-footer {
    padding: 0.75rem 1rem;
    border-top: 1px solid var(--border-color);
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
    background: var(--bg-card-header);
}

.btn-modal-cancel {
    padding: 0.4375rem 0.875rem;
    background: var(--bg-card-header);
    color: var(--text-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    font-size: 0.8125rem;
    font-weight: 600;
    cursor: pointer;
    transition: background var(--transition);
}

.btn-modal-cancel:hover {
    background: var(--border-color);
}

.btn-modal-confirm {
    padding: 0.4375rem 0.875rem;
    background: var(--forge-primary);
    color: var(--text-inverse);
    border: none;
    border-radius: var(--border-radius);
    font-size: 0.8125rem;
    font-weight: 600;
    cursor: pointer;
    transition: background var(--transition);
}

.btn-modal-confirm:hover {
    background: var(--forge-primary-dark);
}

.btn-modal-danger {
    background: var(--forge-danger);
}

.btn-modal-danger:hover {
    filter: brightness(0.9);
}

/* Toast */
.toast {
    position: fixed;
    bottom: 1.5rem;
    right: 1.5rem;
    padding: 0.875rem 1.25rem;
    background: var(--bg-card);
    border: 2px solid var(--border-color);
    border-radius: var(--border-radius-lg);
    display: flex;
    align-items: center;
    gap: 0.625rem;
    z-index: 1001;
    animation: toastIn 0.3s ease;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text-primary);
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
}

.toast.success { 
    border-color: var(--forge-success); 
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, var(--bg-card) 100%);
}
.toast.error { 
    border-color: var(--forge-danger); 
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, var(--bg-card) 100%);
}

@keyframes toastIn {
    from { transform: translateY(1rem); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.spin { animation: spin 1s linear infinite; }

.sortable-ghost { opacity: 0.4; }
</style>

<div class="menu-builder">
    <div class="menu-add-panel">
        <div class="add-panel-card">
            <div class="add-panel-header" onclick="this.parentElement.classList.toggle('collapsed')">
                <span>Pages</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            </div>
            <div class="add-panel-body">
                <?php if (empty($availablePages)): ?>
                <p style="color:var(--text-muted);font-size:0.75rem;margin:0">No pages available</p>
                <?php else: ?>
                <div class="add-item-list" id="pagesList">
                    <?php foreach ($availablePages as $page): ?>
                    <div class="add-item-checkbox">
                        <input type="checkbox" id="page_<?= $page['id'] ?>" value="<?= $page['id'] ?>" data-title="<?= esc($page['title']) ?>" data-type="page">
                        <label for="page_<?= $page['id'] ?>"><?= esc($page['title']) ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php if (!empty($availablePages)): ?>
            <div class="add-panel-footer">
                <button class="btn-add-to-menu" onclick="addSelectedItems('pagesList')" <?= !$currentMenu ? 'disabled' : '' ?>>Add to Menu</button>
            </div>
            <?php endif; ?>
        </div>

        <div class="add-panel-card collapsed">
            <div class="add-panel-header" onclick="this.parentElement.classList.toggle('collapsed')">
                <span>Posts</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            </div>
            <div class="add-panel-body">
                <?php if (empty($availablePosts)): ?>
                <p style="color:var(--text-muted);font-size:0.75rem;margin:0">No posts available</p>
                <?php else: ?>
                <div class="add-item-list" id="postsList">
                    <?php foreach ($availablePosts as $post): ?>
                    <div class="add-item-checkbox">
                        <input type="checkbox" id="post_<?= $post['id'] ?>" value="<?= $post['id'] ?>" data-title="<?= esc($post['title']) ?>" data-type="post">
                        <label for="post_<?= $post['id'] ?>"><?= esc($post['title']) ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php if (!empty($availablePosts)): ?>
            <div class="add-panel-footer">
                <button class="btn-add-to-menu" onclick="addSelectedItems('postsList')" <?= !$currentMenu ? 'disabled' : '' ?>>Add to Menu</button>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($availablePostTypes)): ?>
        <?php foreach ($availablePostTypes as $cptInfo): ?>
        <div class="add-panel-card collapsed">
            <div class="add-panel-header" onclick="this.parentElement.classList.toggle('collapsed')">
                <span><?= esc($cptInfo['name']) ?></span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            </div>
            <div class="add-panel-body">
                <?php if (empty($cptInfo['posts'])): ?>
                <p style="color:var(--text-muted);font-size:0.75rem;margin:0">No <?= esc(strtolower($cptInfo['name'])) ?> available</p>
                <?php else: ?>
                <div class="add-item-list" id="cpt_<?= esc($cptInfo['slug']) ?>_list">
                    <?php foreach ($cptInfo['posts'] as $cptPost): ?>
                    <div class="add-item-checkbox">
                        <input type="checkbox" id="cpt_<?= $cptInfo['slug'] ?>_<?= $cptPost['id'] ?>" value="<?= $cptPost['id'] ?>" data-title="<?= esc($cptPost['title']) ?>" data-type="<?= esc($cptInfo['slug']) ?>">
                        <label for="cpt_<?= $cptInfo['slug'] ?>_<?= $cptPost['id'] ?>"><?= esc($cptPost['title']) ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php if (!empty($cptInfo['posts'])): ?>
            <div class="add-panel-footer">
                <button class="btn-add-to-menu" onclick="addSelectedItems('cpt_<?= esc($cptInfo['slug']) ?>_list')" <?= !$currentMenu ? 'disabled' : '' ?>>Add to Menu</button>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <div class="add-panel-card">
            <div class="add-panel-header" onclick="this.parentElement.classList.toggle('collapsed')">
                <span>Custom Link</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            </div>
            <div class="add-panel-body">
                <div class="custom-link-form">
                    <input type="url" id="customLinkUrl" placeholder="https://" value="https://">
                    <input type="text" id="customLinkText" placeholder="Link Text">
                </div>
            </div>
            <div class="add-panel-footer">
                <button class="btn-add-to-menu" onclick="addCustomLink()" <?= !$currentMenu ? 'disabled' : '' ?>>Add to Menu</button>
            </div>
        </div>
    </div>

    <div class="menu-structure-panel">
        <div class="menu-header">
            <h1>Menu Builder</h1>
            <div class="menu-selector">
                <select id="menuSelect" onchange="location.href='?menu='+this.value">
                    <option value="">Select a menu...</option>
                    <?php foreach ($menus as $menu): ?>
                    <option value="<?= $menu['id'] ?>" <?= $menuId === (int)$menu['id'] ? 'selected' : '' ?>>
                        <?= esc($menu['name']) ?><?php if ($menu['location']): ?> (<?= esc($locations[$menu['location']] ?? $menu['location']) ?>)<?php endif; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button class="btn-new-menu" onclick="openModal('newMenuModal')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    New Menu
                </button>
            </div>
        </div>

        <?php if (!$currentMenu): ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            <h2>No Menu Selected</h2>
            <p>Select an existing menu or create a new one.</p>
            <button class="btn-create-menu" onclick="openModal('newMenuModal')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Create New Menu
            </button>
        </div>
        <?php else: ?>
        
        <div class="menu-structure-card">
            <div class="menu-structure-header">
                <h2><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg> Menu Structure</h2>
                <span>Drag to reorder</span>
            </div>
            <div class="menu-structure-body">
                <?php if (empty($menuItems)): ?>
                <div class="menu-empty" id="menuEmpty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="9" x2="15" y2="9"/><line x1="9" y1="13" x2="15" y2="13"/></svg>
                    <h3>Menu is empty</h3>
                    <p>Add items from the left panel</p>
                </div>
                <?php endif; ?>
                
                <ul class="menu-items" id="menuItems">
                    <?php 
                    function renderMenuItems($items) {
                        foreach ($items as $item): 
                    ?>
                    <li class="menu-item" data-id="<?= $item['id'] ?>">
                        <div class="menu-item-content">
                            <div class="menu-item-header">
                                <span class="menu-item-drag"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg></span>
                                <span class="menu-item-title"><?= esc($item['title']) ?></span>
                                <span class="menu-item-type"><?= ucfirst($item['type']) ?></span>
                                <button class="menu-item-toggle" onclick="this.closest('.menu-item-content').classList.toggle('expanded')"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></button>
                            </div>
                            <div class="menu-item-details">
                                <div class="menu-item-field">
                                    <label>Navigation Label</label>
                                    <input type="text" class="item-title" value="<?= esc($item['title']) ?>">
                                </div>
                                <?php if ($item['type'] === 'custom'): ?>
                                <div class="menu-item-field">
                                    <label>URL</label>
                                    <input type="url" class="item-url" value="<?= esc($item['url']) ?>">
                                </div>
                                <?php endif; ?>
                                <div class="menu-item-field">
                                    <label>Open In</label>
                                    <select class="item-target">
                                        <option value="_self" <?= $item['target'] === '_self' ? 'selected' : '' ?>>Same Tab</option>
                                        <option value="_blank" <?= $item['target'] === '_blank' ? 'selected' : '' ?>>New Tab</option>
                                    </select>
                                </div>
                                <div class="menu-item-field">
                                    <label>CSS Class</label>
                                    <input type="text" class="item-css" value="<?= esc($item['css_class'] ?? '') ?>" placeholder="optional">
                                </div>
                                <div class="menu-item-actions">
                                    <button class="btn-item-save" onclick="saveItem(<?= $item['id'] ?>, this)">Save</button>
                                    <button class="btn-item-delete" onclick="deleteItem(<?= $item['id'] ?>, <?= htmlspecialchars(json_encode($item['title']), ENT_QUOTES, 'UTF-8') ?>)">Delete</button>
                                </div>
                            </div>
                        </div>
                        <?php if (!empty($item['children'])): ?>
                        <ul class="menu-items"><?php renderMenuItems($item['children']); ?></ul>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; }
                    renderMenuItems($menuItems);
                    ?>
                </ul>
            </div>
        </div>

        <div class="menu-settings-card">
            <div class="menu-settings-header">Menu Settings</div>
            <div class="menu-settings-body">
                <div class="menu-setting-row">
                    <label>Menu Name</label>
                    <input type="text" id="menuName" value="<?= esc($currentMenu['name']) ?>">
                </div>
                <div class="menu-setting-row">
                    <label>Location</label>
                    <select id="menuLocation">
                        <option value="">— None —</option>
                        <?php foreach ($locations as $slug => $name): ?>
                        <option value="<?= esc($slug) ?>" <?= $currentMenu['location'] === $slug ? 'selected' : '' ?>><?= esc($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="menu-actions">
                <button class="btn-save-menu" onclick="saveMenu()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
                    Save Menu
                </button>
                <button class="btn-delete-menu" onclick="openModal('deleteMenuModal')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    Delete
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal-overlay" id="newMenuModal" onclick="if(event.target===this)closeModal('newMenuModal')">
    <div class="modal">
        <div class="modal-header">
            <h2>Create New Menu</h2>
            <button class="modal-close" onclick="closeModal('newMenuModal')"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
        </div>
        <div class="modal-body">
            <div class="menu-item-field">
                <label>Menu Name</label>
                <input type="text" id="newMenuName" placeholder="e.g. Main Navigation">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-modal-cancel" onclick="closeModal('newMenuModal')">Cancel</button>
            <button class="btn-modal-confirm" onclick="createMenu()">Create Menu</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="deleteMenuModal" onclick="if(event.target===this)closeModal('deleteMenuModal')">
    <div class="modal">
        <div class="modal-header">
            <h2>Delete Menu</h2>
            <button class="modal-close" onclick="closeModal('deleteMenuModal')"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
        </div>
        <div class="modal-body">
            <p style="margin:0;color:var(--text-secondary)">Delete "<strong style="color:var(--text-primary)"><?= esc($currentMenu['name'] ?? '') ?></strong>"? This cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button class="btn-modal-cancel" onclick="closeModal('deleteMenuModal')">Cancel</button>
            <button class="btn-modal-confirm btn-modal-danger" onclick="deleteMenu()">Delete</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="deleteItemModal" onclick="if(event.target===this)closeModal('deleteItemModal')">
    <div class="modal">
        <div class="modal-header">
            <h2>Delete Item</h2>
            <button class="modal-close" onclick="closeModal('deleteItemModal')"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
        </div>
        <div class="modal-body">
            <p style="margin:0;color:var(--text-secondary)">Delete "<strong style="color:var(--text-primary)" id="deleteItemName"></strong>" from the menu?</p>
        </div>
        <div class="modal-footer">
            <button class="btn-modal-cancel" onclick="closeModal('deleteItemModal')">Cancel</button>
            <button class="btn-modal-confirm btn-modal-danger" onclick="confirmDeleteItem()">Delete</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
var menuId = <?= $menuId ?: 'null' ?>;
var csrfToken = '<?= csrfToken() ?>';
var deleteItemId = null;

function openModal(id) { document.getElementById(id).classList.add('open'); if(id==='newMenuModal') setTimeout(()=>document.getElementById('newMenuName').focus(),100); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function createMenu() {
    var name = document.getElementById('newMenuName').value.trim();
    if (!name) return showToast('Enter a menu name', 'error');
    ajaxPost({ ajax_action: 'create_menu', name: name }, function(d) {
        if (d && d.success) {
            location.href = '?menu=' + d.id;
        } else {
            showToast(d?.error || 'Error creating menu', 'error');
        }
    });
}

function saveMenu() {
    var nameEl = document.getElementById('menuName');
    var locationEl = document.getElementById('menuLocation');
    var saveBtn = document.querySelector('.btn-save-menu');
    
    if (!nameEl || !locationEl) {
        showToast('Error: Form elements not found', 'error');
        return;
    }
    
    var name = nameEl.value.trim();
    if (!name) {
        showToast('Menu name required', 'error');
        return;
    }
    
    if (!menuId) {
        showToast('No menu selected', 'error');
        return;
    }
    
    // Show saving state
    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="spin"><circle cx="12" cy="12" r="10" stroke-dasharray="32" stroke-dashoffset="12"/></svg> Saving...';
    }
    
    // First save the order
    var itemsEl = document.getElementById('menuItems');
    var items = itemsEl ? getOrder(itemsEl) : [];
    
    ajaxPost({ ajax_action: 'save_order', menu_id: menuId, items: JSON.stringify(items) }, function(orderResult) {
        if (!orderResult || !orderResult.success) {
            console.error('Order save failed:', orderResult);
        }
        // Then save the menu settings
        ajaxPost({ 
            ajax_action: 'update_menu', 
            id: menuId, 
            name: name, 
            location: locationEl.value 
        }, function(d) {
            // Restore button
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg> Save Menu';
            }
            
            if (d && d.success) {
                showToast('Menu saved successfully!', 'success');
            } else {
                showToast(d?.error || 'Error saving menu', 'error');
                console.error('Menu save failed:', d);
            }
        });
    });
}

function deleteMenu() {
    ajaxPost({ ajax_action: 'delete_menu', id: menuId }, function(d) {
        if (d && d.success) {
            location.href = 'menus.php';
        } else {
            showToast(d?.error || 'Error deleting menu', 'error');
        }
    });
}

function addSelectedItems(listId) {
    if (!menuId) return showToast('Select a menu first', 'error');
    var cbs = document.querySelectorAll('#'+listId+' input:checked');
    if (!cbs.length) return showToast('Select items', 'error');
    
    var addedCount = 0;
    var skippedCount = 0;
    var total = cbs.length;
    var processed = 0;
    
    cbs.forEach(cb => {
        var data = { 
            ajax_action: 'add_item', 
            menu_id: menuId, 
            title: cb.dataset.title, 
            type: cb.dataset.type,
            object_id: cb.value
        };
        
        ajaxPost(data, function(r) { 
            processed++;
            if (r && r.success) { 
                addItemToUI(r.item); 
                addedCount++;
                cb.checked = false;
            } else {
                skippedCount++;
            }
            
            // Show result after all processed
            if (processed === total) {
                if (addedCount > 0 && skippedCount > 0) {
                    showToast(addedCount + ' added, ' + skippedCount + ' already in menu', 'success');
                } else if (addedCount > 0) {
                    showToast(addedCount + ' item' + (addedCount > 1 ? 's' : '') + ' added', 'success');
                } else {
                    showToast('Items already in menu', 'error');
                }
            }
        });
    });
}

function addCustomLink() {
    if (!menuId) return showToast('Select a menu first', 'error');
    var url = document.getElementById('customLinkUrl').value.trim();
    var text = document.getElementById('customLinkText').value.trim();
    if (!url || !text) return showToast('Enter URL and text', 'error');
    ajaxPost({ ajax_action: 'add_item', menu_id: menuId, title: text, type: 'custom', url: url }, function(d) {
        if (d && d.success) { 
            addItemToUI(d.item); 
            document.getElementById('customLinkUrl').value='https://'; 
            document.getElementById('customLinkText').value=''; 
            showToast('Link added','success'); 
        } else {
            showToast(d?.error || 'Failed to add link', 'error');
        }
    });
}

function addItemToUI(item) {
    var e = document.getElementById('menuEmpty'); if(e) e.style.display='none';
    var li = document.createElement('li'); li.className='menu-item'; li.dataset.id=item.id;
    var safeTitle = JSON.stringify(item.title || '');
    li.innerHTML = `<div class="menu-item-content"><div class="menu-item-header"><span class="menu-item-drag"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg></span><span class="menu-item-title">${esc(item.title)}</span><span class="menu-item-type">${item.type.charAt(0).toUpperCase()+item.type.slice(1)}</span><button class="menu-item-toggle" onclick="this.closest('.menu-item-content').classList.toggle('expanded')"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></button></div><div class="menu-item-details"><div class="menu-item-field"><label>Navigation Label</label><input type="text" class="item-title" value="${esc(item.title)}"></div>${item.type==='custom'?'<div class="menu-item-field"><label>URL</label><input type="url" class="item-url" value="'+esc(item.url||'')+'"></div>':''}<div class="menu-item-field"><label>Open In</label><select class="item-target"><option value="_self">Same Tab</option><option value="_blank">New Tab</option></select></div><div class="menu-item-field"><label>CSS Class</label><input type="text" class="item-css" value="" placeholder="optional"></div><div class="menu-item-actions"><button class="btn-item-save" onclick="saveItem(${item.id},this)">Save</button><button class="btn-item-delete" onclick="deleteItem(${item.id},${safeTitle})">Delete</button></div></div></div>`;
    document.getElementById('menuItems').appendChild(li);
    initSortable(document.getElementById('menuItems'));
}

function saveItem(id, btn) {
    var c = btn.closest('.menu-item-content');
    var data = { ajax_action: 'update_item', id: id, title: c.querySelector('.item-title').value, target: c.querySelector('.item-target').value, css_class: c.querySelector('.item-css').value };
    var u = c.querySelector('.item-url'); if(u) data.url = u.value;
    ajaxPost(data, function(r) { 
        if(r && r.success) { 
            c.querySelector('.menu-item-title').textContent=data.title; 
            showToast('Saved','success'); 
        } else {
            showToast(r?.error || 'Failed to save', 'error');
        }
    });
}

function deleteItem(id, title) {
    deleteItemId = id;
    document.getElementById('deleteItemName').textContent = title || 'this item';
    openModal('deleteItemModal');
}

function confirmDeleteItem() {
    if (!deleteItemId) return;
    ajaxPost({ ajax_action: 'delete_item', id: deleteItemId }, function(d) { 
        if(d && d.success) { 
            document.querySelector('.menu-item[data-id="'+deleteItemId+'"]')?.remove(); 
            showToast('Deleted','success'); 
        } else {
            showToast(d?.error || 'Failed to delete', 'error');
        }
        closeModal('deleteItemModal');
        deleteItemId = null;
    });
}

function saveMenuOrder(cb) {
    var items = getOrder(document.getElementById('menuItems'));
    ajaxPost({ ajax_action: 'save_order', menu_id: menuId, items: JSON.stringify(items) }, cb||function(){});
}

function getOrder(ul) {
    if(!ul) return [];
    return [...ul.children].filter(li=>li.classList.contains('menu-item')).map(li=>({id:+li.dataset.id,children:getOrder(li.querySelector('.menu-items'))}));
}

function initSortable(el) {
    if(!el || el.sortableInitialized) return;
    el.sortableInitialized = true;
    new Sortable(el, { group:'menu-items', animation:150, handle:'.menu-item-drag', ghostClass:'sortable-ghost', fallbackOnBody:true, swapThreshold:0.65, onEnd:function(){} });
}
document.querySelectorAll('.menu-items').forEach(initSortable);

function ajaxPost(data, cb) {
    data.csrf = csrfToken;
    var fd = new FormData(); for(var k in data) fd.append(k, data[k]);
    fetch('menus.php', {method:'POST', body:fd})
        .then(r => {
            if (!r.ok) {
                throw new Error('HTTP ' + r.status);
            }
            return r.text();
        })
        .then(text => {
            console.log('Response:', text.substring(0, 200));
            try {
                return JSON.parse(text);
            } catch(e) {
                console.error('Invalid JSON. Full response:', text);
                throw new Error('Server error');
            }
        })
        .then(cb)
        .catch(e => {
            console.error('AJAX Error:', e);
            showToast('Error: ' + e.message, 'error');
        });
}

function esc(s) { var d=document.createElement('div'); d.textContent=s||''; return d.innerHTML; }

function showToast(msg, type) {
    document.querySelector('.toast')?.remove();
    var t = document.createElement('div'); t.className='toast '+type;
    t.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="${type==='success'?'var(--forge-success)':'var(--forge-danger)'}" stroke-width="2">${type==='success'?'<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>':'<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/>'}</svg>${msg}`;
    document.body.appendChild(t); setTimeout(()=>t.remove(), 3000);
}

document.getElementById('newMenuName')?.addEventListener('keypress', e => { if(e.key==='Enter') createMenu(); });
</script>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
