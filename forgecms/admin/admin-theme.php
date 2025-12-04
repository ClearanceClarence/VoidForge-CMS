<?php
/**
 * Admin Theme Settings - Forge CMS v1.0.6
 * Backend color schemes, fonts, and icon styles
 */

define('CMS_ROOT', dirname(__DIR__));
require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/user.php';
require_once CMS_ROOT . '/includes/post.php';
require_once CMS_ROOT . '/includes/media.php';

Post::init();

User::startSession();
User::requireLogin();

if (!User::isAdmin()) {
    redirect(ADMIN_URL . '/');
}

$currentPage = 'admin-theme';
$pageTitle = 'Admin Theme';

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $theme = [
        'color_scheme' => $_POST['color_scheme'] ?? 'default',
        'font' => $_POST['font'] ?? 'inter',
        'icon_style' => $_POST['icon_style'] ?? 'outlined',
        'sidebar_compact' => isset($_POST['sidebar_compact']),
        'animations' => isset($_POST['animations']),
    ];
    
    setOption('admin_theme', $theme);
    setFlash('success', 'Theme settings saved successfully!');
    redirect(ADMIN_URL . '/admin-theme.php');
}

// Get current settings
$currentTheme = getOption('admin_theme', [
    'color_scheme' => 'default',
    'font' => 'inter',
    'icon_style' => 'outlined',
    'sidebar_compact' => false,
    'animations' => true,
]);

// Ensure all keys exist
$currentTheme = array_merge([
    'color_scheme' => 'default',
    'font' => 'inter',
    'icon_style' => 'outlined',
    'sidebar_compact' => false,
    'animations' => true,
], $currentTheme);

include ADMIN_PATH . '/includes/header.php';

// $colorSchemes, $fonts, $iconStyles are now defined in header.php with all needed keys

// Build Google Fonts URL for all fonts
$fontFamilies = [];
foreach ($fonts as $font) {
    $fontFamilies[] = $font['google'];
}
$fontsUrl = 'https://fonts.googleapis.com/css2?family=' . implode('&family=', $fontFamilies) . '&display=swap';
?>

<link href="<?= $fontsUrl ?>" rel="stylesheet">

<style>
.theme-page {
    max-width: 900px;
    margin: 0 auto;
}

.theme-header {
    margin-bottom: 2rem;
}

.theme-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 0.5rem 0;
}

.theme-header p {
    color: #64748b;
    margin: 0;
    font-size: 0.9375rem;
}

.theme-section {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    margin-bottom: 1.5rem;
    overflow: hidden;
}

.theme-section-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem 1.5rem;
    background: linear-gradient(135deg, #f8fafc 0%, #fff 100%);
    border-bottom: 1px solid #e2e8f0;
}

.theme-section-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: #fff;
    flex-shrink: 0;
}

.theme-section-title h3 {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 0.25rem 0;
}

.theme-section-title p {
    font-size: 0.8125rem;
    color: #64748b;
    margin: 0;
}

.theme-section-body {
    padding: 1.5rem;
}

/* Color Scheme Grid */
.color-schemes {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

.color-scheme-option {
    position: relative;
    cursor: pointer;
    display: block;
}

.color-scheme-option input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.color-scheme-card {
    padding: 1rem;
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    transition: all 0.2s ease;
}

.color-scheme-option:hover .color-scheme-card {
    border-color: #cbd5e1;
    background: #fff;
}

.color-scheme-option input[type="radio"]:checked + .color-scheme-card {
    border-color: #6366f1;
    background: #f5f3ff;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.color-scheme-preview {
    display: flex;
    gap: 0.375rem;
    margin-bottom: 0.75rem;
}

.color-scheme-preview span {
    flex: 1;
    height: 36px;
    border-radius: 8px;
}

.color-scheme-name {
    font-size: 0.875rem;
    font-weight: 600;
    color: #1e293b;
    text-align: center;
}

.color-scheme-check {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    width: 24px;
    height: 24px;
    background: #6366f1;
    border-radius: 50%;
    display: none;
    align-items: center;
    justify-content: center;
    color: #fff;
    box-shadow: 0 2px 4px rgba(99, 102, 241, 0.3);
}

.color-scheme-option input[type="radio"]:checked ~ .color-scheme-check {
    display: flex;
}

/* Font Grid */
.font-options {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

.font-option {
    position: relative;
    cursor: pointer;
    display: block;
}

.font-option input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.font-card {
    padding: 1.5rem 1rem;
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    text-align: center;
    transition: all 0.2s ease;
}

.font-option:hover .font-card {
    border-color: #cbd5e1;
    background: #fff;
}

.font-option input[type="radio"]:checked + .font-card {
    border-color: #6366f1;
    background: #f5f3ff;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.font-preview {
    font-size: 2rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.5rem;
    line-height: 1.2;
}

.font-name {
    font-size: 0.8125rem;
    color: #64748b;
    font-weight: 500;
}

/* Icon Style Grid */
.icon-options {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

.icon-option {
    position: relative;
    cursor: pointer;
    display: block;
}

.icon-option input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.icon-card {
    padding: 1.5rem;
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    text-align: center;
    transition: all 0.2s ease;
}

.icon-option:hover .icon-card {
    border-color: #cbd5e1;
    background: #fff;
}

.icon-option input[type="radio"]:checked + .icon-card {
    border-color: #6366f1;
    background: #f5f3ff;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.icon-preview {
    display: flex;
    justify-content: center;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
    color: #475569;
}

.icon-name {
    font-size: 0.875rem;
    font-weight: 600;
    color: #1e293b;
}

/* Toggle Options */
.toggle-options {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.toggle-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
}

.toggle-info h4 {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 0.25rem 0;
}

.toggle-info p {
    font-size: 0.8125rem;
    color: #64748b;
    margin: 0;
}

.toggle-switch {
    position: relative;
    width: 52px;
    height: 28px;
    flex-shrink: 0;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    inset: 0;
    background: #cbd5e1;
    border-radius: 28px;
    transition: 0.3s ease;
}

.toggle-slider::before {
    position: absolute;
    content: "";
    height: 22px;
    width: 22px;
    left: 3px;
    bottom: 3px;
    background: #fff;
    border-radius: 50%;
    transition: 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.toggle-switch input:checked + .toggle-slider {
    background: #6366f1;
}

.toggle-switch input:checked + .toggle-slider::before {
    transform: translateX(24px);
}

/* Save Button */
.theme-actions {
    display: flex;
    justify-content: flex-end;
    padding-top: 0.5rem;
}

.btn-save {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 2rem;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 0.9375rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 15px rgba(99, 102, 241, 0.35);
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(99, 102, 241, 0.45);
}

.btn-save:active {
    transform: translateY(0);
}

/* Responsive */
@media (max-width: 768px) {
    .color-schemes,
    .font-options,
    .icon-options {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 480px) {
    .color-schemes,
    .font-options,
    .icon-options {
        grid-template-columns: 1fr;
    }
    
    .toggle-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
}
</style>

<div class="theme-page">
    <div class="theme-header">
        <h1>Admin Theme</h1>
        <p>Customize the look and feel of your admin dashboard</p>
    </div>
    
    <form method="post">
        <?= csrfField() ?>
        
        <!-- Color Schemes -->
        <div class="theme-section">
            <div class="theme-section-header">
                <div class="theme-section-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M12 2a10 10 0 0 1 0 20 10 10 0 0 0 0-20"></path>
                    </svg>
                </div>
                <div class="theme-section-title">
                    <h3>Color Scheme</h3>
                    <p>Choose your preferred color palette for the admin interface</p>
                </div>
            </div>
            <div class="theme-section-body">
                <div class="color-schemes">
                    <?php foreach ($colorSchemes as $key => $scheme): ?>
                    <label class="color-scheme-option">
                        <input type="radio" name="color_scheme" value="<?= $key ?>" 
                               <?= $currentTheme['color_scheme'] === $key ? 'checked' : '' ?>>
                        <div class="color-scheme-card">
                            <div class="color-scheme-preview">
                                <?php foreach ($scheme['preview'] as $color): ?>
                                <span style="background: <?= $color ?>"></span>
                                <?php endforeach; ?>
                            </div>
                            <div class="color-scheme-name"><?= esc($scheme['name']) ?></div>
                        </div>
                        <div class="color-scheme-check">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Fonts -->
        <div class="theme-section">
            <div class="theme-section-header">
                <div class="theme-section-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="4 7 4 4 20 4 20 7"></polyline>
                        <line x1="9" y1="20" x2="15" y2="20"></line>
                        <line x1="12" y1="4" x2="12" y2="20"></line>
                    </svg>
                </div>
                <div class="theme-section-title">
                    <h3>Typography</h3>
                    <p>Select a font family for the admin interface</p>
                </div>
            </div>
            <div class="theme-section-body">
                <div class="font-options">
                    <?php foreach ($fonts as $key => $font): ?>
                    <label class="font-option">
                        <input type="radio" name="font" value="<?= $key ?>" 
                               <?= $currentTheme['font'] === $key ? 'checked' : '' ?>>
                        <div class="font-card">
                            <div class="font-preview" style="font-family: <?= $font['family'] ?>">Aa</div>
                            <div class="font-name"><?= esc($font['name']) ?></div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Icon Style -->
        <div class="theme-section">
            <div class="theme-section-header">
                <div class="theme-section-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                </div>
                <div class="theme-section-title">
                    <h3>Icon Style</h3>
                    <p>Choose the weight of icons throughout the admin</p>
                </div>
            </div>
            <div class="theme-section-body">
                <div class="icon-options">
                    <?php foreach ($iconStyles as $key => $style): ?>
                    <label class="icon-option">
                        <input type="radio" name="icon_style" value="<?= $key ?>" 
                               <?= $currentTheme['icon_style'] === $key ? 'checked' : '' ?>>
                        <div class="icon-card">
                            <div class="icon-preview">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="<?= $style['stroke_width'] ?>">
                                    <rect x="3" y="3" width="7" height="9" rx="1"></rect>
                                    <rect x="14" y="3" width="7" height="5" rx="1"></rect>
                                    <rect x="14" y="12" width="7" height="9" rx="1"></rect>
                                    <rect x="3" y="16" width="7" height="5" rx="1"></rect>
                                </svg>
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="<?= $style['stroke_width'] ?>">
                                    <circle cx="12" cy="12" r="3"></circle>
                                    <circle cx="12" cy="12" r="9"></circle>
                                </svg>
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="<?= $style['stroke_width'] ?>">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </div>
                            <div class="icon-name"><?= esc($style['name']) ?></div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Additional Options -->
        <div class="theme-section">
            <div class="theme-section-header">
                <div class="theme-section-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </div>
                <div class="theme-section-title">
                    <h3>Preferences</h3>
                    <p>Additional display options</p>
                </div>
            </div>
            <div class="theme-section-body">
                <div class="toggle-options">
                    <div class="toggle-item">
                        <div class="toggle-info">
                            <h4>Animations</h4>
                            <p>Enable smooth transitions and hover effects throughout the interface</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="animations" <?= $currentTheme['animations'] ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="toggle-item">
                        <div class="toggle-info">
                            <h4>Compact Sidebar</h4>
                            <p>Use smaller icons and reduced spacing in the sidebar navigation</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="sidebar_compact" <?= $currentTheme['sidebar_compact'] ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="theme-actions">
            <button type="submit" class="btn-save">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                Save Theme Settings
            </button>
        </div>
    </form>
</div>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
