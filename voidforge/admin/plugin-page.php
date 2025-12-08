<?php
/**
 * Plugin Page Handler - VoidForge CMS
 * Renders pages registered by plugins via add_admin_page()
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
Plugin::init();

User::startSession();
User::requireLogin();

$pageSlug = $_GET['page'] ?? '';
$pluginPage = Plugin::getAdminPage($pageSlug);

if (!$pluginPage) {
    setFlash('error', 'Plugin page not found.');
    redirect(ADMIN_URL . '/');
}

// Check capability
if ($pluginPage['capability'] === 'admin' && !User::isAdmin()) {
    setFlash('error', 'You do not have permission to access this page.');
    redirect(ADMIN_URL . '/');
}

$currentPage = 'plugin-' . $pageSlug;
$pageTitle = $pluginPage['title'];

include ADMIN_PATH . '/includes/header.php';
?>

<div class="plugin-page" style="max-width: 1200px; margin: 0 auto;">
    <?php
    // Render the plugin's page content
    if (is_callable($pluginPage['callback'])) {
        call_user_func($pluginPage['callback']);
    } else {
        echo '<div class="alert alert-warning">This plugin page has no content to display.</div>';
    }
    ?>
</div>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
