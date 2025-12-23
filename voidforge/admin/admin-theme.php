<?php
/**
 * Admin Theme Settings - VoidForge CMS
 * Backend color schemes, fonts, and icon styles
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
User::requireLogin();

if (!User::isAdmin()) {
    redirect(ADMIN_URL . '/');
}

$currentPage = 'admin-theme';
$pageTitle = 'Admin Theme';

// Handle save custom scheme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf() && isset($_POST['save_custom_scheme'])) {
    $schemeName = trim($_POST['scheme_name'] ?? '');
    $customPrimary = $_POST['custom_primary'] ?? '#6366f1';
    $customSecondary = $_POST['custom_secondary'] ?? '#8b5cf6';
    $customSidebar = $_POST['custom_sidebar'] ?? '#0f172a';
    
    if (!empty($schemeName)) {
        $savedSchemes = getOption('custom_color_schemes', []);
        if (count($savedSchemes) >= 10) array_shift($savedSchemes);
        
        $schemeKey = preg_replace('/[^a-z0-9]/', '', strtolower($schemeName));
        $savedSchemes[$schemeKey] = [
            'name' => $schemeName,
            'primary' => $customPrimary,
            'secondary' => $customSecondary,
            'sidebar_bg' => $customSidebar,
            'preview' => [$customPrimary, $customSecondary, $customSidebar],
            'category' => 'custom'
        ];
        
        setOption('custom_color_schemes', $savedSchemes);
        setFlash('success', 'Custom scheme "' . $schemeName . '" saved!');
    }
    redirect(ADMIN_URL . '/admin-theme.php');
}

// Handle delete custom scheme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf() && isset($_POST['delete_scheme'])) {
    $schemeKey = $_POST['delete_scheme'];
    $savedSchemes = getOption('custom_color_schemes', []);
    unset($savedSchemes[$schemeKey]);
    setOption('custom_color_schemes', $savedSchemes);
    setFlash('success', 'Custom scheme deleted.');
    redirect(ADMIN_URL . '/admin-theme.php');
}

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf() && !isset($_POST['save_custom_scheme']) && !isset($_POST['delete_scheme'])) {
    $theme = [
        'color_scheme' => $_POST['color_scheme'] ?? 'default',
        'font' => $_POST['font'] ?? 'inter',
        'font_size_sidebar' => $_POST['font_size_sidebar'] ?? 'medium',
        'font_size_header' => $_POST['font_size_header'] ?? 'medium',
        'font_size_content' => $_POST['font_size_content'] ?? 'medium',
        'icon_style' => $_POST['icon_style'] ?? 'outlined',
        'sidebar_compact' => isset($_POST['sidebar_compact']),
        'animations' => isset($_POST['animations']),
        'menu_item_padding' => $_POST['menu_item_padding'] ?? 'medium',
        'menu_item_spacing' => $_POST['menu_item_spacing'] ?? 'medium',
    ];
    
    if ($theme['color_scheme'] === 'custom') {
        $theme['custom_primary'] = $_POST['custom_primary'] ?? '#6366f1';
        $theme['custom_secondary'] = $_POST['custom_secondary'] ?? '#8b5cf6';
        $theme['custom_sidebar'] = $_POST['custom_sidebar'] ?? '#0f172a';
    }
    
    setOption('admin_theme', $theme);
    setFlash('success', 'Theme settings saved successfully!');
    redirect(ADMIN_URL . '/admin-theme.php');
}

// Get current settings
$currentTheme = getOption('admin_theme', []);
$currentTheme = array_merge([
    'color_scheme' => 'default',
    'font' => 'inter',
    'font_size_sidebar' => 'medium',
    'font_size_header' => 'medium',
    'font_size_content' => 'medium',
    'icon_style' => 'outlined',
    'sidebar_compact' => false,
    'animations' => true,
    'custom_primary' => '#6366f1',
    'custom_secondary' => '#8b5cf6',
    'custom_sidebar' => '#0f172a',
    'menu_item_padding' => 'medium',
    'menu_item_spacing' => 'medium',
], $currentTheme);

$savedCustomSchemes = getOption('custom_color_schemes', []);

include ADMIN_PATH . '/includes/header.php';

// Build Google Fonts URL
$fontFamilies = [];
foreach ($fonts as $font) {
    $fontFamilies[] = $font['google'];
}
$fontsUrl = 'https://fonts.googleapis.com/css2?family=' . implode('&family=', array_slice($fontFamilies, 0, 20)) . '&display=swap';

// Group schemes by category
$schemeCategories = [
    'blue' => ['label' => 'Blues & Purples'],
    'green' => ['label' => 'Greens & Teals'],
    'red' => ['label' => 'Reds & Pinks'],
    'orange' => ['label' => 'Oranges & Yellows'],
    'neutral' => ['label' => 'Neutrals'],
    'custom' => ['label' => 'Custom'],
];

$groupedSchemes = [];
foreach ($colorSchemes as $key => $scheme) {
    $cat = $scheme['category'] ?? 'blue';
    $groupedSchemes[$cat][$key] = $scheme;
}

// Add custom option to custom tab
$groupedSchemes['custom']['custom'] = [
    'name' => 'Create Custom',
    'preview' => [$currentTheme['custom_primary'], $currentTheme['custom_secondary'], $currentTheme['custom_sidebar']],
    'is_creator' => true
];

// Add saved custom schemes
foreach ($savedCustomSchemes as $key => $scheme) {
    $groupedSchemes['custom']['saved_' . $key] = $scheme;
}

// Find which category contains current scheme
$currentSchemeCategory = 'blue';
if ($currentTheme['color_scheme'] === 'custom' || strpos($currentTheme['color_scheme'], 'saved_') === 0) {
    $currentSchemeCategory = 'custom';
} else {
    foreach ($colorSchemes as $key => $scheme) {
        if ($key === $currentTheme['color_scheme']) {
            $currentSchemeCategory = $scheme['category'] ?? 'blue';
            break;
        }
    }
}

// Find which category contains current font
$currentFontCategory = 'sans';
if (isset($fonts[$currentTheme['font']])) {
    $currentFontCategory = $fonts[$currentTheme['font']]['category'] ?? 'sans';
}
?>

<link href="<?= $fontsUrl ?>" rel="stylesheet">

<style>
/* Color Picker Styles */
.vf-color-overlay { position: fixed; inset: 0; z-index: 10000; display: none; }
.vf-color-overlay.active { display: block; }
.vf-color-trigger { width: 48px; height: 44px; border: 2px solid var(--border-color); border-radius: 10px; cursor: pointer; position: relative; overflow: hidden; background: linear-gradient(45deg, #ccc 25%, transparent 25%), linear-gradient(-45deg, #ccc 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #ccc 75%), linear-gradient(-45deg, transparent 75%, #ccc 75%); background-size: 8px 8px; transition: border-color 0.2s; }
.vf-color-trigger:hover { border-color: var(--forge-primary); }
.vf-color-trigger-inner { position: absolute; inset: 3px; border-radius: 6px; }
.vf-color-popup { position: fixed; z-index: 10001; width: 280px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; box-shadow: 0 20px 40px rgba(0,0,0,0.3); padding: 16px; display: none; }
.vf-color-popup.active { display: block; animation: vfPopupIn 0.2s ease; }
@keyframes vfPopupIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
.vf-color-gradient { width: 100%; height: 140px; border-radius: 8px; position: relative; cursor: crosshair; margin-bottom: 12px; background: linear-gradient(to bottom, transparent, #000), linear-gradient(to right, #fff, transparent); }
.vf-color-gradient-pointer { position: absolute; width: 16px; height: 16px; border: 2px solid #fff; border-radius: 50%; box-shadow: 0 0 0 1px rgba(0,0,0,0.3), 0 2px 4px rgba(0,0,0,0.3); transform: translate(-50%, -50%); pointer-events: none; }
.vf-color-sliders { display: flex; gap: 12px; margin-bottom: 12px; }
.vf-color-slider-wrap { flex: 1; display: flex; flex-direction: column; gap: 4px; }
.vf-color-slider-label { font-size: 10px; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px; }
.vf-color-slider { height: 14px; border-radius: 7px; position: relative; cursor: pointer; }
.vf-color-hue { background: linear-gradient(to right, #ff0000 0%, #ffff00 17%, #00ff00 33%, #00ffff 50%, #0000ff 67%, #ff00ff 83%, #ff0000 100%); }
.vf-color-slider-thumb { position: absolute; top: 50%; width: 18px; height: 18px; background: #fff; border: 2px solid #fff; border-radius: 50%; box-shadow: 0 1px 4px rgba(0,0,0,0.4); transform: translate(-50%, -50%); pointer-events: none; }
.vf-color-preview-row { display: flex; gap: 12px; margin-bottom: 12px; align-items: center; }
.vf-color-preview { width: 48px; height: 48px; border-radius: 8px; overflow: hidden; border: 1px solid var(--border-color); background: linear-gradient(45deg, #ccc 25%, transparent 25%), linear-gradient(-45deg, #ccc 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #ccc 75%), linear-gradient(-45deg, transparent 75%, #ccc 75%); background-size: 8px 8px; }
.vf-color-preview-inner { width: 100%; height: 100%; }
.vf-color-hex-wrap { flex: 1; }
.vf-color-hex-input { width: 100%; padding: 0.625rem; font-size: 0.875rem; font-family: 'JetBrains Mono', monospace; border: 2px solid var(--border-color); border-radius: 8px; background: var(--bg-card-header); color: var(--text-primary); text-transform: uppercase; }
.vf-color-hex-input:focus { outline: none; border-color: var(--forge-primary); }
.vf-color-presets { display: grid; grid-template-columns: repeat(10, 1fr); gap: 4px; margin-bottom: 12px; }
.vf-color-preset { width: 100%; aspect-ratio: 1; border-radius: 4px; cursor: pointer; border: 2px solid transparent; transition: transform 0.1s, border-color 0.1s; }
.vf-color-preset:hover { transform: scale(1.15); border-color: var(--text-primary); }
.vf-color-actions { display: flex; gap: 8px; }
.vf-color-btn { flex: 1; padding: 0.5rem; border: none; border-radius: 8px; font-size: 0.8125rem; font-weight: 600; cursor: pointer; }
.vf-color-btn-clear { background: var(--bg-card-header); color: var(--text-primary); border: 1px solid var(--border-color); }
.vf-color-btn-apply { background: var(--forge-primary); color: #fff; }

/* Page Layout */
.theme-page { max-width: 1100px; margin: 0 auto; }
.theme-header { margin-bottom: 2rem; }
.theme-header h1 { font-size: 1.75rem; font-weight: 700; color: var(--text-primary); margin: 0 0 0.5rem 0; }
.theme-header p { color: var(--text-muted); margin: 0; font-size: 0.9375rem; }

/* Sections */
.theme-section { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; margin-bottom: 1.5rem; overflow: hidden; }
.theme-section-header { display: flex; align-items: center; gap: 1rem; padding: 1.25rem 1.5rem; background: var(--bg-card-header); border-bottom: 1px solid var(--border-color); }
.theme-section-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, var(--forge-primary) 0%, var(--forge-secondary) 100%); color: #fff; flex-shrink: 0; }
.theme-section-title h3 { font-size: 1rem; font-weight: 600; color: var(--text-primary); margin: 0 0 0.25rem 0; }
.theme-section-title p { font-size: 0.8125rem; color: var(--text-muted); margin: 0; }
.theme-section-body { padding: 1.5rem; }

/* Tabs */
.scheme-tabs, .font-tabs { display: flex; gap: 0.5rem; margin-bottom: 1.25rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; flex-wrap: wrap; }
.scheme-tab, .font-tab { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: transparent; border: 1px solid transparent; border-radius: 8px; font-size: 0.8125rem; font-weight: 500; color: var(--text-muted); cursor: pointer; transition: all 0.2s; }
.scheme-tab:hover, .font-tab:hover { color: var(--text-primary); background: var(--bg-card-header); }
.scheme-tab.active, .font-tab.active { color: var(--forge-primary); background: var(--forge-primary-bg); border-color: var(--forge-primary); }
.scheme-tab .count { font-size: 0.6875rem; padding: 0.125rem 0.375rem; background: var(--border-color); border-radius: 4px; }
.scheme-tab.active .count { background: var(--forge-primary); color: #fff; }

/* Scheme Grid */
.scheme-category, .font-category { display: none; }
.scheme-category.active { display: grid; grid-template-columns: repeat(6, 1fr); gap: 0.875rem; }
.font-category.active { display: grid; grid-template-columns: repeat(5, 1fr); gap: 0.75rem; }
@media (max-width: 1000px) { .scheme-category.active { grid-template-columns: repeat(4, 1fr); } .font-category.active { grid-template-columns: repeat(4, 1fr); } }
@media (max-width: 700px) { .scheme-category.active, .font-category.active { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 500px) { .scheme-category.active, .font-category.active { grid-template-columns: repeat(2, 1fr); } }

.scheme-option, .font-option { position: relative; cursor: pointer; display: block; }
.scheme-option input[type="radio"], .font-option input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }
.scheme-card, .font-card { padding: 0.75rem; background: var(--bg-card-header); border: 2px solid var(--border-color); border-radius: 10px; transition: all 0.2s ease; }
.scheme-option:hover .scheme-card, .font-option:hover .font-card { border-color: var(--text-dim); background: var(--bg-card); }
.scheme-option input[type="radio"]:checked + .scheme-card, .font-option input[type="radio"]:checked + .font-card { border-color: var(--forge-primary); background: var(--forge-primary-bg); box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15); }
.scheme-preview { display: flex; gap: 0.25rem; margin-bottom: 0.5rem; }
.scheme-preview span { flex: 1; height: 28px; border-radius: 6px; }
.scheme-name { font-size: 0.6875rem; font-weight: 600; color: var(--text-primary); text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.scheme-check { position: absolute; top: 0.5rem; right: 0.5rem; width: 20px; height: 20px; background: var(--forge-primary); border-radius: 50%; display: none; align-items: center; justify-content: center; color: #fff; }
.scheme-option input[type="radio"]:checked ~ .scheme-check { display: flex; }
.scheme-delete { position: absolute; top: 0.5rem; left: 0.5rem; width: 20px; height: 20px; background: #ef4444; border-radius: 50%; display: none; align-items: center; justify-content: center; color: #fff; border: none; cursor: pointer; z-index: 2; }
.scheme-option:hover .scheme-delete { display: flex; }
.custom-scheme-option .scheme-card { background: repeating-conic-gradient(var(--bg-card-header) 0% 25%, var(--bg-card) 0% 50%) 50% / 12px 12px; border-style: dashed; }

/* Custom Color Pickers */
.custom-color-pickers { margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color); }
.custom-color-pickers h4 { font-size: 0.9375rem; font-weight: 600; color: var(--text-primary); margin: 0 0 1rem 0; display: flex; align-items: center; gap: 0.5rem; }
.color-picker-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
@media (max-width: 700px) { .color-picker-grid { grid-template-columns: 1fr; } }
.color-picker-item { display: flex; flex-direction: column; gap: 0.75rem; }
.color-picker-item > label { font-size: 0.8125rem; font-weight: 600; color: var(--text-muted); }
.color-input-wrap { display: flex; gap: 0.75rem; align-items: center; }
.color-hex-input { flex: 1; padding: 0.75rem 1rem; font-size: 0.875rem; font-family: 'JetBrains Mono', monospace; border: 2px solid var(--border-color); border-radius: 10px; background: var(--bg-card-header); color: var(--text-primary); text-transform: uppercase; transition: all 0.2s; }
.color-hex-input:focus { outline: none; border-color: var(--forge-primary); background: var(--bg-card); box-shadow: 0 0 0 3px var(--forge-primary-bg); }
.save-scheme-row { display: flex; gap: 0.75rem; margin-top: 1.25rem; padding-top: 1.25rem; border-top: 1px solid var(--border-color); }
.save-scheme-row input[type="text"] { flex: 1; padding: 0.75rem 1rem; border: 2px solid var(--border-color); border-radius: 10px; font-size: 0.875rem; background: var(--bg-card-header); color: var(--text-primary); }
.save-scheme-row input[type="text"]:focus { outline: none; border-color: var(--forge-primary); }
.btn-save-scheme { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.25rem; background: linear-gradient(135deg, var(--forge-primary) 0%, var(--forge-secondary) 100%); color: #fff; border: none; border-radius: 10px; font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.btn-save-scheme:hover { transform: translateY(-1px); box-shadow: 0 4px 12px var(--forge-shadow-color); }

/* Font Grid */
.font-card { text-align: center; }
.font-preview { font-size: 1.375rem; font-weight: 600; color: var(--text-primary); line-height: 1.2; margin-bottom: 0.5rem; }
.font-name { font-size: 0.75rem; font-weight: 600; color: var(--text-primary); margin-bottom: 0.125rem; }
.font-desc { font-size: 0.625rem; color: var(--text-muted); }

/* Font Size Options */
.font-size-areas { display: flex; flex-direction: column; gap: 1rem; }
.font-size-area { display: flex; align-items: center; gap: 1.5rem; padding: 1rem; background: var(--bg-card-header); border: 1px solid var(--border-color); border-radius: 10px; }
.font-size-area-header { display: flex; align-items: center; gap: 0.5rem; min-width: 100px; color: var(--text-muted); font-size: 0.875rem; font-weight: 500; }
.font-size-area-header svg { width: 16px; height: 16px; }
.font-size-area-options { display: flex; gap: 0.5rem; flex: 1; }
.font-size-radio { cursor: pointer; }
.font-size-radio input[type="radio"] { position: absolute; opacity: 0; pointer-events: none; }
.font-size-radio-label { display: block; padding: 0.5rem 1rem; background: var(--bg-card); border: 2px solid var(--border-color); border-radius: 8px; color: var(--text-muted); font-size: 0.8125rem; font-weight: 500; transition: all 0.2s ease; }
.font-size-radio:hover .font-size-radio-label { border-color: var(--text-dim); color: var(--text-primary); }
.font-size-radio input[type="radio"]:checked + .font-size-radio-label { border-color: var(--forge-primary); background: var(--forge-primary-bg); color: var(--forge-primary); }

/* Menu Spacing */
.spacing-options-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
@media (max-width: 700px) { .spacing-options-grid { grid-template-columns: 1fr; } }
.spacing-option-group { padding: 1.25rem; background: var(--bg-card-header); border: 1px solid var(--border-color); border-radius: 12px; }
.spacing-option-label { display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; font-weight: 600; color: var(--text-primary); margin-bottom: 0.25rem; }
.spacing-option-label svg { color: var(--text-muted); width: 16px; height: 16px; }
.spacing-option-desc { font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.75rem; }
.spacing-radio-group { display: flex; gap: 0.5rem; }
.spacing-radio { cursor: pointer; flex: 1; }
.spacing-radio input[type="radio"] { position: absolute; opacity: 0; pointer-events: none; }
.spacing-radio-label { display: block; padding: 0.5rem; background: var(--bg-card); border: 2px solid var(--border-color); border-radius: 8px; color: var(--text-muted); font-size: 0.75rem; font-weight: 500; text-align: center; transition: all 0.2s ease; }
.spacing-radio:hover .spacing-radio-label { border-color: var(--text-dim); }
.spacing-radio input[type="radio"]:checked + .spacing-radio-label { border-color: var(--forge-primary); background: var(--forge-primary-bg); color: var(--forge-primary); }

/* Icon Styles */
.icon-options { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
.icon-option { position: relative; cursor: pointer; display: block; }
.icon-option input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }
.icon-card { padding: 1.25rem; background: var(--bg-card-header); border: 2px solid var(--border-color); border-radius: 12px; text-align: center; transition: all 0.2s ease; }
.icon-option:hover .icon-card { border-color: var(--text-dim); }
.icon-option input[type="radio"]:checked + .icon-card { border-color: var(--forge-primary); background: var(--forge-primary-bg); }
.icon-preview { display: flex; justify-content: center; gap: 0.75rem; margin-bottom: 0.75rem; color: var(--text-primary); }
.icon-name { font-size: 0.8125rem; font-weight: 600; color: var(--text-primary); }

/* Toggle Options */
.toggle-options { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
@media (max-width: 700px) { .toggle-options { grid-template-columns: 1fr; } }
.toggle-item { display: flex; align-items: center; justify-content: space-between; padding: 1rem; background: var(--bg-card-header); border: 1px solid var(--border-color); border-radius: 10px; }
.toggle-info h4 { font-size: 0.875rem; font-weight: 600; color: var(--text-primary); margin: 0 0 0.125rem 0; }
.toggle-info p { font-size: 0.75rem; color: var(--text-muted); margin: 0; }
.toggle-switch { position: relative; width: 48px; height: 26px; flex-shrink: 0; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider { position: absolute; cursor: pointer; inset: 0; background: var(--border-color); border-radius: 26px; transition: 0.3s ease; }
.toggle-slider::before { position: absolute; content: ""; height: 20px; width: 20px; left: 3px; bottom: 3px; background: #fff; border-radius: 50%; transition: 0.3s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.toggle-switch input:checked + .toggle-slider { background: var(--forge-primary); }
.toggle-switch input:checked + .toggle-slider::before { transform: translateX(22px); }

/* Save Button */
.theme-actions { display: flex; justify-content: flex-end; padding-top: 0.5rem; }
.btn-save { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.875rem 2rem; background: linear-gradient(135deg, var(--forge-primary) 0%, var(--forge-secondary) 100%); color: #fff; border: none; border-radius: 12px; font-size: 0.9375rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 4px 15px var(--forge-shadow-color); }
.btn-save:hover { transform: translateY(-2px); box-shadow: 0 6px 20px var(--forge-shadow-color); }

/* Delete Modal */
.delete-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; z-index: 1000; }
.delete-modal-overlay.active { display: flex; }
.delete-modal { background: var(--bg-card); border-radius: 16px; padding: 2rem; max-width: 400px; width: 90%; box-shadow: 0 25px 50px rgba(0,0,0,0.3); }
.delete-modal h3 { margin: 0 0 0.5rem; color: var(--text-primary); }
.delete-modal p { color: var(--text-muted); margin: 0 0 1.5rem; }
.delete-modal-actions { display: flex; gap: 0.75rem; justify-content: flex-end; }
.btn-modal-cancel { padding: 0.625rem 1.25rem; background: var(--bg-card-header); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-primary); font-weight: 500; cursor: pointer; }
.btn-modal-delete { padding: 0.625rem 1.25rem; background: #ef4444; border: none; border-radius: 8px; color: #fff; font-weight: 500; cursor: pointer; }
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
                    <p>Choose your preferred color palette</p>
                </div>
            </div>
            <div class="theme-section-body">
                <div class="scheme-tabs">
                    <?php foreach ($schemeCategories as $catKey => $catInfo): ?>
                    <button type="button" class="scheme-tab <?= $currentSchemeCategory === $catKey ? 'active' : '' ?>" data-category="<?= $catKey ?>">
                        <?= esc($catInfo['label']) ?>
                        <span class="count"><?= count($groupedSchemes[$catKey] ?? []) ?></span>
                    </button>
                    <?php endforeach; ?>
                </div>
                
                <?php foreach ($schemeCategories as $catKey => $catInfo): ?>
                <div class="scheme-category <?= $currentSchemeCategory === $catKey ? 'active' : '' ?>" data-category="<?= $catKey ?>">
                    <?php foreach (($groupedSchemes[$catKey] ?? []) as $key => $scheme): ?>
                    <label class="scheme-option <?= !empty($scheme['is_creator']) ? 'custom-scheme-option' : '' ?>">
                        <input type="radio" name="color_scheme" value="<?= $key ?>" <?= $currentTheme['color_scheme'] === $key ? 'checked' : '' ?>>
                        <div class="scheme-card">
                            <div class="scheme-preview" <?= !empty($scheme['is_creator']) ? 'id="customSchemePreview"' : '' ?>>
                                <?php foreach ($scheme['preview'] as $color): ?>
                                <span style="background: <?= $color ?>"></span>
                                <?php endforeach; ?>
                            </div>
                            <div class="scheme-name"><?= esc($scheme['name']) ?></div>
                        </div>
                        <div class="scheme-check">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        </div>
                        <?php if (strpos($key, 'saved_') === 0): ?>
                        <button type="button" class="scheme-delete" onclick="event.preventDefault(); confirmDeleteScheme('<?= str_replace('saved_', '', $key) ?>', '<?= esc($scheme['name']) ?>')">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        </button>
                        <?php endif; ?>
                    </label>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
                
                <!-- Custom Color Pickers (shown in Custom tab when custom selected) -->
                <div id="customColorPickers" class="custom-color-pickers" style="display: <?= $currentTheme['color_scheme'] === 'custom' ? 'block' : 'none' ?>;">
                    <h4>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path></svg>
                        Custom Colors
                    </h4>
                    <div class="color-picker-grid">
                        <div class="color-picker-item">
                            <label>Primary Color</label>
                            <div class="color-input-wrap">
                                <div class="vf-color-trigger" id="triggerPrimary"><div class="vf-color-trigger-inner" style="background: <?= esc($currentTheme['custom_primary']) ?>"></div></div>
                                <input type="text" id="customPrimary" name="custom_primary" class="color-hex-input" value="<?= strtoupper($currentTheme['custom_primary']) ?>">
                            </div>
                        </div>
                        <div class="color-picker-item">
                            <label>Secondary Color</label>
                            <div class="color-input-wrap">
                                <div class="vf-color-trigger" id="triggerSecondary"><div class="vf-color-trigger-inner" style="background: <?= esc($currentTheme['custom_secondary']) ?>"></div></div>
                                <input type="text" id="customSecondary" name="custom_secondary" class="color-hex-input" value="<?= strtoupper($currentTheme['custom_secondary']) ?>">
                            </div>
                        </div>
                        <div class="color-picker-item">
                            <label>Sidebar Background</label>
                            <div class="color-input-wrap">
                                <div class="vf-color-trigger" id="triggerSidebar"><div class="vf-color-trigger-inner" style="background: <?= esc($currentTheme['custom_sidebar']) ?>"></div></div>
                                <input type="text" id="customSidebar" name="custom_sidebar" class="color-hex-input" value="<?= strtoupper($currentTheme['custom_sidebar']) ?>">
                            </div>
                        </div>
                    </div>
                    <div class="save-scheme-row">
                        <input type="text" name="scheme_name" placeholder="Enter a name to save this scheme...">
                        <button type="submit" name="save_custom_scheme" value="1" class="btn-save-scheme">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                            Save Scheme
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Typography -->
        <div class="theme-section">
            <div class="theme-section-header">
                <div class="theme-section-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 7 4 4 20 4 20 7"></polyline><line x1="9" y1="20" x2="15" y2="20"></line><line x1="12" y1="4" x2="12" y2="20"></line></svg>
                </div>
                <div class="theme-section-title">
                    <h3>Typography</h3>
                    <p>Select your preferred font family</p>
                </div>
            </div>
            <div class="theme-section-body">
                <div class="font-tabs">
                    <button type="button" class="font-tab <?= $currentFontCategory === 'sans' ? 'active' : '' ?>" data-category="sans">Sans-serif</button>
                    <button type="button" class="font-tab <?= $currentFontCategory === 'serif' ? 'active' : '' ?>" data-category="serif">Serif</button>
                    <button type="button" class="font-tab <?= $currentFontCategory === 'mono' ? 'active' : '' ?>" data-category="mono">Monospace</button>
                </div>
                
                <?php 
                $fontCategories = ['sans' => [], 'serif' => [], 'mono' => []];
                foreach ($fonts as $key => $font) {
                    $fontCategories[$font['category']][$key] = $font;
                }
                ?>
                
                <?php foreach ($fontCategories as $cat => $catFonts): ?>
                <div class="font-category <?= $currentFontCategory === $cat ? 'active' : '' ?>" data-category="<?= $cat ?>">
                    <?php foreach ($catFonts as $key => $font): ?>
                    <label class="font-option">
                        <input type="radio" name="font" value="<?= $key ?>" <?= $currentTheme['font'] === $key ? 'checked' : '' ?>>
                        <div class="font-card">
                            <div class="font-preview" style="font-family: <?= $font['family'] ?>">Aa</div>
                            <div class="font-name"><?= esc($font['name']) ?></div>
                            <div class="font-desc"><?= esc($font['desc']) ?></div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Font Sizes -->
        <div class="theme-section">
            <div class="theme-section-header">
                <div class="theme-section-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                </div>
                <div class="theme-section-title">
                    <h3>Font Sizes</h3>
                    <p>Adjust text sizes for different areas</p>
                </div>
            </div>
            <div class="theme-section-body">
                <div class="font-size-areas">
                    <div class="font-size-area">
                        <div class="font-size-area-header">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="3" x2="9" y2="21"></line></svg>
                            Sidebar
                        </div>
                        <div class="font-size-area-options">
                            <?php foreach (['small' => 'Small', 'medium' => 'Medium', 'large' => 'Large'] as $size => $label): ?>
                            <label class="font-size-radio"><input type="radio" name="font_size_sidebar" value="<?= $size ?>" <?= $currentTheme['font_size_sidebar'] === $size ? 'checked' : '' ?>><span class="font-size-radio-label"><?= $label ?></span></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="font-size-area">
                        <div class="font-size-area-header">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="7" rx="1"></rect><rect x="3" y="14" width="18" height="7" rx="1"></rect></svg>
                            Header
                        </div>
                        <div class="font-size-area-options">
                            <?php foreach (['small' => 'Small', 'medium' => 'Medium', 'large' => 'Large'] as $size => $label): ?>
                            <label class="font-size-radio"><input type="radio" name="font_size_header" value="<?= $size ?>" <?= $currentTheme['font_size_header'] === $size ? 'checked' : '' ?>><span class="font-size-radio-label"><?= $label ?></span></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="font-size-area">
                        <div class="font-size-area-header">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                            Content
                        </div>
                        <div class="font-size-area-options">
                            <?php foreach (['small' => 'Small', 'medium' => 'Medium', 'large' => 'Large'] as $size => $label): ?>
                            <label class="font-size-radio"><input type="radio" name="font_size_content" value="<?= $size ?>" <?= $currentTheme['font_size_content'] === $size ? 'checked' : '' ?>><span class="font-size-radio-label"><?= $label ?></span></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Menu Spacing -->
        <div class="theme-section">
            <div class="theme-section-header">
                <div class="theme-section-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </div>
                <div class="theme-section-title">
                    <h3>Menu Spacing</h3>
                    <p>Adjust sidebar menu density</p>
                </div>
            </div>
            <div class="theme-section-body">
                <div class="spacing-options-grid">
                    <div class="spacing-option-group">
                        <div class="spacing-option-label">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="7 10 12 5 17 10"></polyline><polyline points="7 14 12 19 17 14"></polyline></svg>
                            Item Padding
                        </div>
                        <div class="spacing-option-desc">Vertical padding inside menu items</div>
                        <div class="spacing-radio-group">
                            <?php foreach (['compact' => 'Compact', 'medium' => 'Medium', 'comfortable' => 'Comfortable'] as $val => $label): ?>
                            <label class="spacing-radio"><input type="radio" name="menu_item_padding" value="<?= $val ?>" <?= $currentTheme['menu_item_padding'] === $val ? 'checked' : '' ?>><span class="spacing-radio-label"><?= $label ?></span></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="spacing-option-group">
                        <div class="spacing-option-label">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="5 12 12 19 19 12"></polyline></svg>
                            Item Spacing
                        </div>
                        <div class="spacing-option-desc">Gap between menu items</div>
                        <div class="spacing-radio-group">
                            <?php foreach (['compact' => 'Compact', 'medium' => 'Medium', 'comfortable' => 'Comfortable'] as $val => $label): ?>
                            <label class="spacing-radio"><input type="radio" name="menu_item_spacing" value="<?= $val ?>" <?= $currentTheme['menu_item_spacing'] === $val ? 'checked' : '' ?>><span class="spacing-radio-label"><?= $label ?></span></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Icon Style -->
        <div class="theme-section">
            <div class="theme-section-header">
                <div class="theme-section-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                </div>
                <div class="theme-section-title">
                    <h3>Icon Style</h3>
                    <p>Choose the weight of interface icons</p>
                </div>
            </div>
            <div class="theme-section-body">
                <div class="icon-options">
                    <?php foreach ($iconStyles as $key => $style): ?>
                    <label class="icon-option">
                        <input type="radio" name="icon_style" value="<?= $key ?>" <?= $currentTheme['icon_style'] === $key ? 'checked' : '' ?>>
                        <div class="icon-card">
                            <div class="icon-preview">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="<?= $style['stroke_width'] ?>"><rect x="3" y="3" width="7" height="9" rx="1"></rect><rect x="14" y="3" width="7" height="5" rx="1"></rect><rect x="14" y="12" width="7" height="9" rx="1"></rect><rect x="3" y="16" width="7" height="5" rx="1"></rect></svg>
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="<?= $style['stroke_width'] ?>"><circle cx="12" cy="12" r="3"></circle><circle cx="12" cy="12" r="9"></circle></svg>
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="<?= $style['stroke_width'] ?>"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            </div>
                            <div class="icon-name"><?= esc($style['name']) ?></div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Preferences -->
        <div class="theme-section">
            <div class="theme-section-header">
                <div class="theme-section-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                </div>
                <div class="theme-section-title">
                    <h3>Preferences</h3>
                    <p>Additional display options</p>
                </div>
            </div>
            <div class="theme-section-body">
                <div class="toggle-options">
                    <div class="toggle-item">
                        <div class="toggle-info"><h4>Animations</h4><p>Smooth transitions and hover effects</p></div>
                        <label class="toggle-switch"><input type="checkbox" name="animations" <?= $currentTheme['animations'] ? 'checked' : '' ?>><span class="toggle-slider"></span></label>
                    </div>
                    <div class="toggle-item">
                        <div class="toggle-info"><h4>Compact Sidebar</h4><p>Smaller icons and reduced spacing</p></div>
                        <label class="toggle-switch"><input type="checkbox" name="sidebar_compact" <?= $currentTheme['sidebar_compact'] ? 'checked' : '' ?>><span class="toggle-slider"></span></label>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="theme-actions">
            <button type="submit" class="btn-save">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                Save Theme Settings
            </button>
        </div>
    </form>
</div>

<div class="vf-color-overlay" id="vfColorOverlay"></div>

<script>
// VoidForge Color Picker
const VFColorPicker = {
    activePopup: null,
    currentColor: { h: 0, s: 100, v: 100 },
    callback: null,
    targetInput: null,
    triggerEl: null,
    
    presets: [
        '#000000', '#434343', '#666666', '#999999', '#b7b7b7', '#cccccc', '#d9d9d9', '#efefef', '#f3f3f3', '#ffffff',
        '#980000', '#ff0000', '#ff9900', '#ffff00', '#00ff00', '#00ffff', '#4a86e8', '#0000ff', '#9900ff', '#ff00ff',
        '#6366f1', '#8b5cf6', '#a855f7', '#d946ef', '#ec4899', '#f43f5e', '#ef4444', '#f97316', '#f59e0b', '#eab308',
        '#84cc16', '#22c55e', '#10b981', '#14b8a6', '#06b6d4', '#0ea5e9', '#3b82f6', '#1e293b', '#334155', '#475569'
    ],
    
    init(triggerEl, inputId, onChange) {
        this.triggerEl = triggerEl;
        this.targetInput = document.getElementById(inputId);
        this.callback = onChange;
        this.setColorFromHex(this.targetInput.value || '#000000');
        
        triggerEl.addEventListener('click', (e) => { e.stopPropagation(); this.open(); });
        this.targetInput.addEventListener('input', () => {
            if (/^#[0-9A-Fa-f]{6}$/.test(this.targetInput.value)) {
                this.setColorFromHex(this.targetInput.value);
                this.updateTrigger();
                if (this.callback) this.callback(this.targetInput.value);
            }
        });
    },
    
    open() {
        this.close();
        const popup = document.createElement('div');
        popup.className = 'vf-color-popup';
        popup.innerHTML = this.buildHTML();
        document.body.appendChild(popup);
        
        const rect = this.triggerEl.getBoundingClientRect();
        let top = rect.bottom + 8, left = rect.left;
        if (top + 380 > window.innerHeight) top = rect.top - 380 - 8;
        if (left + 280 > window.innerWidth) left = rect.right - 280;
        if (left < 8) left = 8;
        popup.style.top = top + 'px';
        popup.style.left = left + 'px';
        
        requestAnimationFrame(() => {
            popup.classList.add('active');
            document.getElementById('vfColorOverlay').classList.add('active');
        });
        
        this.activePopup = popup;
        this.bindEvents(popup);
        this.updateUI(popup);
        document.getElementById('vfColorOverlay').onclick = () => this.close();
    },
    
    close() {
        if (this.activePopup) { this.activePopup.remove(); this.activePopup = null; }
        document.getElementById('vfColorOverlay').classList.remove('active');
    },
    
    buildHTML() {
        const presetsHtml = this.presets.map(c => `<div class="vf-color-preset" style="background:${c}" data-color="${c}"></div>`).join('');
        return `<div class="vf-color-gradient" style="background-color: hsl(${this.currentColor.h}, 100%, 50%)"><div class="vf-color-gradient-pointer"></div></div>
            <div class="vf-color-sliders"><div class="vf-color-slider-wrap"><span class="vf-color-slider-label">Hue</span><div class="vf-color-slider vf-color-hue"><div class="vf-color-slider-thumb"></div></div></div></div>
            <div class="vf-color-preview-row"><div class="vf-color-preview"><div class="vf-color-preview-inner"></div></div><div class="vf-color-hex-wrap"><input type="text" class="vf-color-hex-input" value="${this.toHex()}" maxlength="7"></div></div>
            <div class="vf-color-presets">${presetsHtml}</div>
            <div class="vf-color-actions"><button type="button" class="vf-color-btn vf-color-btn-clear">Cancel</button><button type="button" class="vf-color-btn vf-color-btn-apply">Apply</button></div>`;
    },
    
    bindEvents(popup) {
        const gradient = popup.querySelector('.vf-color-gradient');
        const hueSlider = popup.querySelector('.vf-color-hue');
        
        const handleGradient = (e) => {
            const rect = gradient.getBoundingClientRect();
            this.currentColor.s = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width)) * 100;
            this.currentColor.v = (1 - Math.max(0, Math.min(1, (e.clientY - rect.top) / rect.height))) * 100;
            this.updateUI(popup);
        };
        gradient.addEventListener('mousedown', (e) => {
            handleGradient(e);
            const move = (e) => handleGradient(e);
            const up = () => { document.removeEventListener('mousemove', move); document.removeEventListener('mouseup', up); };
            document.addEventListener('mousemove', move);
            document.addEventListener('mouseup', up);
        });
        
        const handleHue = (e) => {
            const rect = hueSlider.getBoundingClientRect();
            this.currentColor.h = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width)) * 360;
            this.updateUI(popup);
        };
        hueSlider.addEventListener('mousedown', (e) => {
            handleHue(e);
            const move = (e) => handleHue(e);
            const up = () => { document.removeEventListener('mousemove', move); document.removeEventListener('mouseup', up); };
            document.addEventListener('mousemove', move);
            document.addEventListener('mouseup', up);
        });
        
        popup.querySelectorAll('.vf-color-preset').forEach(preset => {
            preset.addEventListener('click', () => { this.setColorFromHex(preset.dataset.color); this.updateUI(popup); });
        });
        
        const hexInput = popup.querySelector('.vf-color-hex-input');
        hexInput.addEventListener('change', () => {
            if (/^#[0-9A-Fa-f]{6}$/.test(hexInput.value)) { this.setColorFromHex(hexInput.value); this.updateUI(popup); }
        });
        
        popup.querySelector('.vf-color-btn-clear').addEventListener('click', () => this.close());
        popup.querySelector('.vf-color-btn-apply').addEventListener('click', () => {
            const hex = this.toHex();
            this.targetInput.value = hex.toUpperCase();
            this.updateTrigger();
            if (this.callback) this.callback(hex);
            this.close();
        });
        popup.addEventListener('click', (e) => e.stopPropagation());
    },
    
    updateUI(popup) {
        const { h, s, v } = this.currentColor;
        popup.querySelector('.vf-color-gradient').style.backgroundColor = `hsl(${h}, 100%, 50%)`;
        popup.querySelector('.vf-color-gradient-pointer').style.cssText = `left:${s}%;top:${100-v}%`;
        const hueThumb = popup.querySelector('.vf-color-hue .vf-color-slider-thumb');
        hueThumb.style.left = `${(h/360)*100}%`;
        hueThumb.style.background = `hsl(${h}, 100%, 50%)`;
        const hex = this.toHex();
        popup.querySelector('.vf-color-preview-inner').style.background = hex;
        popup.querySelector('.vf-color-hex-input').value = hex.toUpperCase();
    },
    
    updateTrigger() {
        const inner = this.triggerEl.querySelector('.vf-color-trigger-inner');
        if (inner) inner.style.background = this.toHex();
    },
    
    setColorFromHex(hex) {
        hex = hex.replace('#', '');
        if (hex.length === 3) hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
        if (hex.length !== 6) return;
        const r = parseInt(hex.substr(0,2),16)/255, g = parseInt(hex.substr(2,2),16)/255, b = parseInt(hex.substr(4,2),16)/255;
        const max = Math.max(r,g,b), min = Math.min(r,g,b), d = max - min;
        let h = 0, s = max === 0 ? 0 : d / max, v = max;
        if (max !== min) {
            switch (max) { case r: h = (g-b)/d + (g<b?6:0); break; case g: h = (b-r)/d + 2; break; case b: h = (r-g)/d + 4; break; }
            h /= 6;
        }
        this.currentColor = { h: h*360, s: s*100, v: v*100 };
    },
    
    toHex() {
        const { h, s, v } = this.currentColor;
        const hN = h/360, sN = s/100, vN = v/100;
        let r, g, b;
        const i = Math.floor(hN*6), f = hN*6 - i, p = vN*(1-sN), q = vN*(1-f*sN), t = vN*(1-(1-f)*sN);
        switch (i % 6) { case 0: r=vN;g=t;b=p; break; case 1: r=q;g=vN;b=p; break; case 2: r=p;g=vN;b=t; break; case 3: r=p;g=q;b=vN; break; case 4: r=t;g=p;b=vN; break; case 5: r=vN;g=p;b=q; break; }
        const toHex = (n) => Math.round(n*255).toString(16).padStart(2,'0');
        return `#${toHex(r)}${toHex(g)}${toHex(b)}`;
    }
};

function updateCustomPreview() {
    const preview = document.getElementById('customSchemePreview');
    if (preview) {
        const spans = preview.querySelectorAll('span');
        if (spans[0]) spans[0].style.background = document.getElementById('customPrimary').value;
        if (spans[1]) spans[1].style.background = document.getElementById('customSecondary').value;
        if (spans[2]) spans[2].style.background = document.getElementById('customSidebar').value;
    }
}

const pickerPrimary = Object.create(VFColorPicker);
pickerPrimary.init(document.getElementById('triggerPrimary'), 'customPrimary', updateCustomPreview);
const pickerSecondary = Object.create(VFColorPicker);
pickerSecondary.init(document.getElementById('triggerSecondary'), 'customSecondary', updateCustomPreview);
const pickerSidebar = Object.create(VFColorPicker);
pickerSidebar.init(document.getElementById('triggerSidebar'), 'customSidebar', updateCustomPreview);

document.querySelectorAll('.scheme-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.scheme-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        document.querySelectorAll('.scheme-category').forEach(cat => cat.classList.toggle('active', cat.dataset.category === this.dataset.category));
    });
});

document.querySelectorAll('.font-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.font-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        document.querySelectorAll('.font-category').forEach(cat => cat.classList.toggle('active', cat.dataset.category === this.dataset.category));
    });
});

document.querySelectorAll('input[name="color_scheme"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.getElementById('customColorPickers').style.display = this.value === 'custom' ? 'block' : 'none';
    });
});

function confirmDeleteScheme(key, name) {
    document.getElementById('deleteSchemeKey').value = key;
    document.getElementById('deleteSchemeName').textContent = name;
    document.getElementById('deleteSchemeModal').classList.add('active');
}
function closeDeleteModal() { document.getElementById('deleteSchemeModal').classList.remove('active'); }
document.getElementById('deleteSchemeModal').addEventListener('click', function(e) { if (e.target === this) closeDeleteModal(); });
</script>

<div class="delete-modal-overlay" id="deleteSchemeModal">
    <div class="delete-modal">
        <h3>Delete Color Scheme?</h3>
        <p>Are you sure you want to delete "<span id="deleteSchemeName"></span>"?</p>
        <div class="delete-modal-actions">
            <button type="button" class="btn-modal-cancel" onclick="closeDeleteModal()">Cancel</button>
            <form method="post" style="display: inline;"><?= csrfField() ?><input type="hidden" name="delete_scheme" id="deleteSchemeKey"><button type="submit" class="btn-modal-delete">Delete</button></form>
        </div>
    </div>
</div>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
