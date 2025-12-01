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
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.form-input, .form-select {
    width: 100%;
    padding: 0.75rem 1rem;
    font-size: 0.9375rem;
    line-height: 1.5;
    color: var(--text-primary);
    background: var(--bg-input);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    transition: all 0.15s;
    font-family: inherit;
}

.form-input:focus, .form-select:focus {
    outline: none;
    border-color: var(--forge-primary);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}

.form-hint {
    margin-top: 0.375rem;
    font-size: 0.8125rem;
    color: var(--text-muted);
}

.form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.format-preview {
    display: inline-block;
    padding: 0.25rem 0.625rem;
    background: var(--bg-card-header);
    border-radius: 4px;
    font-size: 0.8125rem;
    color: var(--text-secondary);
    margin-left: 0.5rem;
}

.btn-save {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, var(--forge-primary) 0%, #4f46e5 100%);
    color: #fff;
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

@media (max-width: 640px) {
    .form-row {
        grid-template-columns: 1fr;
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
                    <div class="form-hint">Number of posts to show on blog pages.</div>
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
                <h3 class="settings-card-title">Date & Time</h3>
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
</div>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
