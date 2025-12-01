<?php
/**
 * Site Settings
 */

define('CMS_ROOT', dirname(__DIR__));
require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/user.php';
require_once CMS_ROOT . '/includes/post.php';
require_once CMS_ROOT . '/includes/media.php';

User::startSession();
User::requireRole('admin');

$pageTitle = 'Settings';

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

include ADMIN_PATH . '/includes/header.php';
?>

<div style="max-width: 600px;">
    <form method="post">
        <?= csrfField() ?>
        
        <div class="card" style="margin-bottom: 1rem;">
            <div class="card-header">
                <h3 class="card-title">General Settings</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="site_title" class="form-label">Site Title</label>
                    <input type="text" id="site_title" name="site_title" class="form-input" 
                           value="<?= esc($siteTitle) ?>">
                </div>

                <div class="form-group">
                    <label for="site_description" class="form-label">Tagline</label>
                    <input type="text" id="site_description" name="site_description" class="form-input" 
                           value="<?= esc($siteDescription) ?>">
                    <div class="form-hint">In a few words, explain what this site is about.</div>
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom: 1rem;">
            <div class="card-header">
                <h3 class="card-title">Reading Settings</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="posts_per_page" class="form-label">Posts per page</label>
                    <input type="number" id="posts_per_page" name="posts_per_page" class="form-input" 
                           value="<?= esc($postsPerPage) ?>" min="1" max="100" style="width: 100px;">
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom: 1rem;">
            <div class="card-header">
                <h3 class="card-title">Date & Time</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="date_format" class="form-label">Date Format</label>
                    <select name="date_format" id="date_format" class="form-select" style="width: auto;">
                        <option value="M j, Y" <?= $dateFormat === 'M j, Y' ? 'selected' : '' ?>>
                            <?= date('M j, Y') ?>
                        </option>
                        <option value="F j, Y" <?= $dateFormat === 'F j, Y' ? 'selected' : '' ?>>
                            <?= date('F j, Y') ?>
                        </option>
                        <option value="d/m/Y" <?= $dateFormat === 'd/m/Y' ? 'selected' : '' ?>>
                            <?= date('d/m/Y') ?>
                        </option>
                        <option value="m/d/Y" <?= $dateFormat === 'm/d/Y' ? 'selected' : '' ?>>
                            <?= date('m/d/Y') ?>
                        </option>
                        <option value="Y-m-d" <?= $dateFormat === 'Y-m-d' ? 'selected' : '' ?>>
                            <?= date('Y-m-d') ?>
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="time_format" class="form-label">Time Format</label>
                    <select name="time_format" id="time_format" class="form-select" style="width: auto;">
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

        <button type="submit" class="btn btn-primary">Save Settings</button>
    </form>
</div>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
