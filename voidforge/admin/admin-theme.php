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
        
        // Limit to 5 custom schemes
        if (count($savedSchemes) >= 5) {
            // Remove oldest
            array_shift($savedSchemes);
        }
        
        $schemeKey = preg_replace('/[^a-z0-9]/', '', strtolower($schemeName));
        $savedSchemes[$schemeKey] = [
            'name' => $schemeName,
            'primary' => $customPrimary,
            'secondary' => $customSecondary,
            'sidebar_bg' => $customSidebar,
            'preview' => [$customPrimary, $customSecondary, $customSidebar]
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
    
    // Handle custom colors
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
$currentTheme = getOption('admin_theme', [
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
]);

// Ensure all keys exist
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

// Get saved custom schemes for display
$savedCustomSchemes = getOption('custom_color_schemes', []);

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

/* Custom Color Pickers */
.custom-color-pickers {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e2e8f0;
}

.custom-color-pickers h4 {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 1rem 0;
}

.color-picker-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.25rem;
}

.color-picker-item {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.color-picker-item label {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #475569;
}

.color-input-group {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.color-input-group input[type="color"] {
    width: 56px;
    height: 44px;
    padding: 0;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    cursor: pointer;
    background: none;
    transition: all 0.2s ease;
}

.color-input-group input[type="color"]:hover {
    border-color: #6366f1;
    transform: scale(1.05);
}

.color-input-group input[type="color"]::-webkit-color-swatch-wrapper {
    padding: 3px;
}

.color-input-group input[type="color"]::-webkit-color-swatch {
    border-radius: 6px;
    border: none;
}

.color-text-input {
    flex: 1;
    padding: 0.75rem 0.875rem;
    font-size: 0.875rem;
    font-family: 'JetBrains Mono', 'Monaco', monospace;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    background: #f8fafc;
    color: #1e293b;
    text-transform: uppercase;
    transition: all 0.2s ease;
}

.color-text-input:focus {
    outline: none;
    border-color: #6366f1;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.color-presets {
    display: flex;
    gap: 0.375rem;
    margin-top: 0.5rem;
}

.color-preset {
    width: 24px;
    height: 24px;
    border-radius: 6px;
    border: 2px solid transparent;
    cursor: pointer;
    transition: all 0.2s ease;
}

.color-preset:hover {
    transform: scale(1.15);
    border-color: #1e293b;
}

@media (max-width: 768px) {
    .color-picker-grid {
        grid-template-columns: 1fr;
    }
}

/* Font Grid */
.font-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.25rem;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 0.75rem;
}

.font-tab {
    padding: 0.5rem 1rem;
    background: transparent;
    border: 1px solid transparent;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s;
}

.font-tab:hover {
    color: #1e293b;
    background: #f1f5f9;
}

.font-tab.active {
    color: #6366f1;
    background: #f5f3ff;
    border-color: #e0e7ff;
}

.font-options {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 0.875rem;
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
    padding: 1rem;
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 0.875rem;
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
    font-size: 1.5rem;
    font-weight: 600;
    color: #1e293b;
    line-height: 1;
    min-width: 36px;
    text-align: center;
}

.font-info {
    flex: 1;
    min-width: 0;
}

.font-name {
    font-size: 0.8125rem;
    color: #1e293b;
    font-weight: 600;
    margin-bottom: 0.125rem;
}

.font-desc {
    font-size: 0.6875rem;
    color: #94a3b8;
}

/* Font Size Areas */
.font-size-areas {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.font-size-area {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    padding: 1rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
}

.font-size-area-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    min-width: 100px;
    color: #64748b;
    font-size: 0.875rem;
    font-weight: 500;
}

.font-size-area-options {
    display: flex;
    gap: 0.5rem;
    flex: 1;
}

.font-size-radio {
    cursor: pointer;
}

.font-size-radio input[type="radio"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.font-size-radio-label {
    display: block;
    padding: 0.5rem 1rem;
    background: #fff;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    color: #64748b;
    font-weight: 500;
    transition: all 0.2s ease;
}

.font-size-radio:hover .font-size-radio-label {
    border-color: #cbd5e1;
    color: #1e293b;
}

.font-size-radio input[type="radio"]:checked + .font-size-radio-label {
    border-color: #6366f1;
    background: rgba(99, 102, 241, 0.05);
    color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

/* Menu Spacing Options */
.spacing-options-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

@media (max-width: 700px) {
    .spacing-options-grid {
        grid-template-columns: 1fr;
    }
}

.spacing-option-group {
    padding: 1.25rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
}

.spacing-option-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.25rem;
}

.spacing-option-label svg {
    color: #64748b;
}

.spacing-option-desc {
    font-size: 0.75rem;
    color: #64748b;
    margin: 0 0 1rem 0;
}

.spacing-radio-group {
    display: flex;
    gap: 0.5rem;
}

.spacing-radio {
    flex: 1;
    cursor: pointer;
}

.spacing-radio input[type="radio"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.spacing-radio-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.75rem 0.5rem;
    background: #fff;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    transition: all 0.15s ease;
}

.spacing-radio:hover .spacing-radio-box {
    border-color: #cbd5e1;
}

.spacing-radio input[type="radio"]:checked + .spacing-radio-box {
    border-color: #6366f1;
    background: rgba(99, 102, 241, 0.05);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.spacing-radio-name {
    font-size: 0.8125rem;
    font-weight: 500;
    color: #64748b;
}

.spacing-radio input[type="radio"]:checked + .spacing-radio-box .spacing-radio-name {
    color: #6366f1;
}

/* Save Scheme Box */
.save-scheme-box {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e2e8f0;
}

.save-scheme-box h5 {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #64748b;
    margin: 0 0 0.75rem 0;
}

.save-scheme-form {
    display: flex;
    gap: 0.5rem;
}

.scheme-name-input {
    flex: 1;
    padding: 0.5rem 0.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.875rem;
}

.scheme-name-input:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.btn-save-scheme {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 1rem;
    background: #6366f1;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 0.8125rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-save-scheme:hover:not(:disabled) {
    background: #4f46e5;
}

.btn-save-scheme:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.saved-schemes-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e2e8f0;
}

.saved-scheme-tag {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.625rem 0.875rem;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.8125rem;
    font-weight: 500;
    color: #1e293b;
    transition: all 0.2s ease;
}

.saved-scheme-tag:hover {
    border-color: #cbd5e1;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.scheme-colors {
    display: flex;
    gap: 2px;
}

.scheme-color-dot {
    width: 12px;
    height: 12px;
    border-radius: 3px;
}

.delete-scheme-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    background: #f1f5f9;
    border: none;
    border-radius: 5px;
    color: #94a3b8;
    cursor: pointer;
    font-size: 1rem;
    line-height: 1;
    transition: all 0.15s ease;
}

.delete-scheme-btn:hover {
    background: #fef2f2;
    color: #dc2626;
}

/* Delete Modal */
.delete-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.delete-modal-overlay.active {
    display: flex;
}

.delete-modal {
    background: #fff;
    border-radius: 16px;
    padding: 2rem;
    max-width: 400px;
    width: 90%;
    text-align: center;
}

.delete-modal h3 {
    margin: 0 0 0.5rem 0;
    color: #1e293b;
    font-size: 1.125rem;
}

.delete-modal p {
    color: #64748b;
    margin: 0 0 1.5rem 0;
    font-size: 0.875rem;
}

.delete-modal-actions {
    display: flex;
    gap: 0.75rem;
    justify-content: center;
}

.btn-modal-cancel {
    padding: 0.625rem 1.25rem;
    background: #f1f5f9;
    border: none;
    border-radius: 8px;
    color: #64748b;
    font-weight: 500;
    cursor: pointer;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.btn-modal-cancel:hover {
    background: #e2e8f0;
    color: #1e293b;
}

.btn-modal-delete {
    padding: 0.625rem 1.25rem;
    background: #dc2626;
    border: none;
    border-radius: 8px;
    color: #fff;
    font-weight: 500;
    cursor: pointer;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.btn-modal-delete:hover {
    background: #b91c1c;
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
    .font-size-options,
    .icon-options {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 480px) {
    .color-schemes,
    .font-options,
    .font-size-options,
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
                    
                    <!-- Custom Color Scheme -->
                    <label class="color-scheme-option custom-scheme-option">
                        <input type="radio" name="color_scheme" value="custom" 
                               <?= $currentTheme['color_scheme'] === 'custom' ? 'checked' : '' ?>>
                        <div class="color-scheme-card">
                            <div class="color-scheme-preview custom-preview">
                                <span style="background: <?= esc($currentTheme['custom_primary']) ?>"></span>
                                <span style="background: <?= esc($currentTheme['custom_secondary']) ?>"></span>
                                <span style="background: <?= esc($currentTheme['custom_sidebar']) ?>"></span>
                            </div>
                            <div class="color-scheme-name">Custom</div>
                        </div>
                        <div class="color-scheme-check">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </div>
                    </label>
                </div>
                
                <!-- Custom Color Pickers (shown when custom is selected) -->
                <div id="customColorPickers" class="custom-color-pickers" style="display: <?= $currentTheme['color_scheme'] === 'custom' ? 'block' : 'none' ?>;">
                    <h4>Custom Colors</h4>
                    <div class="color-picker-grid">
                        <div class="color-picker-item">
                            <label>Primary Color</label>
                            <div class="color-input-group">
                                <input type="color" name="custom_primary" id="customPrimary" 
                                       value="<?= esc($currentTheme['custom_primary']) ?>"
                                       onchange="document.getElementById('customPrimaryText').value = this.value">
                                <input type="text" value="<?= esc($currentTheme['custom_primary']) ?>" 
                                       id="customPrimaryText" class="color-text-input"
                                       oninput="document.getElementById('customPrimary').value = this.value">
                            </div>
                            <div class="color-presets">
                                <button type="button" class="color-preset" style="background: #6366f1" onclick="setColor('Primary', '#6366f1')"></button>
                                <button type="button" class="color-preset" style="background: #0ea5e9" onclick="setColor('Primary', '#0ea5e9')"></button>
                                <button type="button" class="color-preset" style="background: #10b981" onclick="setColor('Primary', '#10b981')"></button>
                                <button type="button" class="color-preset" style="background: #f43f5e" onclick="setColor('Primary', '#f43f5e')"></button>
                                <button type="button" class="color-preset" style="background: #f59e0b" onclick="setColor('Primary', '#f59e0b')"></button>
                                <button type="button" class="color-preset" style="background: #8b5cf6" onclick="setColor('Primary', '#8b5cf6')"></button>
                            </div>
                        </div>
                        <div class="color-picker-item">
                            <label>Secondary Color</label>
                            <div class="color-input-group">
                                <input type="color" name="custom_secondary" id="customSecondary" 
                                       value="<?= esc($currentTheme['custom_secondary']) ?>"
                                       onchange="document.getElementById('customSecondaryText').value = this.value">
                                <input type="text" value="<?= esc($currentTheme['custom_secondary']) ?>" 
                                       id="customSecondaryText" class="color-text-input"
                                       oninput="document.getElementById('customSecondary').value = this.value">
                            </div>
                            <div class="color-presets">
                                <button type="button" class="color-preset" style="background: #8b5cf6" onclick="setColor('Secondary', '#8b5cf6')"></button>
                                <button type="button" class="color-preset" style="background: #06b6d4" onclick="setColor('Secondary', '#06b6d4')"></button>
                                <button type="button" class="color-preset" style="background: #14b8a6" onclick="setColor('Secondary', '#14b8a6')"></button>
                                <button type="button" class="color-preset" style="background: #ec4899" onclick="setColor('Secondary', '#ec4899')"></button>
                                <button type="button" class="color-preset" style="background: #fbbf24" onclick="setColor('Secondary', '#fbbf24')"></button>
                                <button type="button" class="color-preset" style="background: #a855f7" onclick="setColor('Secondary', '#a855f7')"></button>
                            </div>
                        </div>
                        <div class="color-picker-item">
                            <label>Sidebar Background</label>
                            <div class="color-input-group">
                                <input type="color" name="custom_sidebar" id="customSidebar" 
                                       value="<?= esc($currentTheme['custom_sidebar']) ?>"
                                       onchange="document.getElementById('customSidebarText').value = this.value">
                                <input type="text" value="<?= esc($currentTheme['custom_sidebar']) ?>" 
                                       id="customSidebarText" class="color-text-input"
                                       oninput="document.getElementById('customSidebar').value = this.value">
                            </div>
                            <div class="color-presets">
                                <button type="button" class="color-preset" style="background: #0f172a" onclick="setColor('Sidebar', '#0f172a')"></button>
                                <button type="button" class="color-preset" style="background: #1e1b4b" onclick="setColor('Sidebar', '#1e1b4b')"></button>
                                <button type="button" class="color-preset" style="background: #0c4a6e" onclick="setColor('Sidebar', '#0c4a6e')"></button>
                                <button type="button" class="color-preset" style="background: #064e3b" onclick="setColor('Sidebar', '#064e3b')"></button>
                                <button type="button" class="color-preset" style="background: #4c0519" onclick="setColor('Sidebar', '#4c0519')"></button>
                                <button type="button" class="color-preset" style="background: #1e293b" onclick="setColor('Sidebar', '#1e293b')"></button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Save Custom Scheme -->
                    <div class="save-scheme-box">
                        <h5>Save as Preset (<?= count($savedCustomSchemes) ?>/5)</h5>
                        <div class="save-scheme-form">
                            <input type="text" name="scheme_name" placeholder="Scheme name..." class="scheme-name-input" maxlength="20">
                            <button type="submit" name="save_custom_scheme" value="1" class="btn-save-scheme" <?= count($savedCustomSchemes) >= 5 ? 'disabled title="Maximum 5 custom schemes"' : '' ?>>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                    <polyline points="7 3 7 8 15 8"></polyline>
                                </svg>
                                Save
                            </button>
                        </div>
                        <?php if (!empty($savedCustomSchemes)): ?>
                        <div class="saved-schemes-list">
                            <?php foreach ($savedCustomSchemes as $key => $savedScheme): ?>
                            <div class="saved-scheme-tag">
                                <div class="scheme-colors">
                                    <span class="scheme-color-dot" style="background: <?= esc($savedScheme['primary']) ?>"></span>
                                    <span class="scheme-color-dot" style="background: <?= esc($savedScheme['secondary']) ?>"></span>
                                    <span class="scheme-color-dot" style="background: <?= esc($savedScheme['sidebar_bg']) ?>"></span>
                                </div>
                                <span><?= esc($savedScheme['name']) ?></span>
                                <button type="button" class="delete-scheme-btn" onclick="confirmDeleteScheme('<?= esc($key) ?>', '<?= esc($savedScheme['name']) ?>')" title="Delete">×</button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
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
                <!-- Font Category Tabs -->
                <div class="font-tabs">
                    <button type="button" class="font-tab active" data-category="sans">Sans-Serif</button>
                    <button type="button" class="font-tab" data-category="serif">Serif</button>
                    <button type="button" class="font-tab" data-category="mono">Monospace</button>
                </div>
                
                <?php
                // Group fonts by category
                $fontsByCategory = ['sans' => [], 'serif' => [], 'mono' => []];
                foreach ($fonts as $key => $font) {
                    $cat = $font['category'] ?? 'sans';
                    $fontsByCategory[$cat][$key] = $font;
                }
                ?>
                
                <?php foreach ($fontsByCategory as $category => $categoryFonts): ?>
                <div class="font-options font-category" data-category="<?= $category ?>" style="<?= $category !== 'sans' ? 'display: none;' : '' ?>">
                    <?php foreach ($categoryFonts as $key => $font): ?>
                    <label class="font-option">
                        <input type="radio" name="font" value="<?= $key ?>" 
                               <?= $currentTheme['font'] === $key ? 'checked' : '' ?>>
                        <div class="font-card">
                            <div class="font-preview" style="font-family: <?= $font['family'] ?>">Aa</div>
                            <div class="font-info">
                                <div class="font-name"><?= esc($font['name']) ?></div>
                                <?php if (!empty($font['desc'])): ?>
                                <div class="font-desc"><?= esc($font['desc']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Font Size -->
        <div class="theme-section">
            <div class="theme-section-header">
                <div class="theme-section-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 7V4h16v3"></path>
                        <path d="M9 20h6"></path>
                        <path d="M12 4v16"></path>
                    </svg>
                </div>
                <div class="theme-section-title">
                    <h3>Font Sizes</h3>
                    <p>Customize font sizes for different areas of the admin</p>
                </div>
            </div>
            <div class="theme-section-body">
                <?php 
                $fontSizes = [
                    'small' => ['name' => 'Small', 'size' => '12px'],
                    'medium' => ['name' => 'Medium', 'size' => '14px'],
                    'large' => ['name' => 'Large', 'size' => '16px'],
                ];
                $fontAreas = [
                    'sidebar' => ['label' => 'Sidebar', 'icon' => '<rect x="3" y="3" width="7" height="18" rx="1"></rect><line x1="14" y1="3" x2="21" y2="3"></line><line x1="14" y1="9" x2="21" y2="9"></line><line x1="14" y1="15" x2="17" y2="15"></line>'],
                    'header' => ['label' => 'Header', 'icon' => '<rect x="3" y="3" width="18" height="5" rx="1"></rect><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="17" x2="15" y2="17"></line>'],
                    'content' => ['label' => 'Content', 'icon' => '<rect x="3" y="3" width="18" height="18" rx="2"></rect><line x1="7" y1="8" x2="17" y2="8"></line><line x1="7" y1="12" x2="17" y2="12"></line><line x1="7" y1="16" x2="13" y2="16"></line>'],
                ];
                ?>
                
                <div class="font-size-areas">
                    <?php foreach ($fontAreas as $areaKey => $area): ?>
                    <div class="font-size-area">
                        <div class="font-size-area-header">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?= $area['icon'] ?></svg>
                            <span><?= $area['label'] ?></span>
                        </div>
                        <div class="font-size-area-options">
                            <?php foreach ($fontSizes as $sizeKey => $sizeOption): ?>
                            <label class="font-size-radio">
                                <input type="radio" name="font_size_<?= $areaKey ?>" value="<?= $sizeKey ?>" 
                                       <?= ($currentTheme['font_size_' . $areaKey] ?? 'medium') === $sizeKey ? 'checked' : '' ?>>
                                <span class="font-size-radio-label" style="font-size: <?= $sizeOption['size'] ?>"><?= $sizeOption['name'] ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Menu Spacing -->
        <div class="theme-section">
            <div class="theme-section-header">
                <div class="theme-section-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="21" y1="10" x2="3" y2="10"></line>
                        <line x1="21" y1="6" x2="3" y2="6"></line>
                        <line x1="21" y1="14" x2="3" y2="14"></line>
                        <line x1="21" y1="18" x2="3" y2="18"></line>
                    </svg>
                </div>
                <div class="theme-section-title">
                    <h3>Menu Spacing</h3>
                    <p>Adjust padding and spacing for sidebar menu items</p>
                </div>
            </div>
            <div class="theme-section-body">
                <?php 
                $spacingSizes = [
                    'compact' => ['name' => 'Compact', 'desc' => 'Minimal spacing'],
                    'medium' => ['name' => 'Medium', 'desc' => 'Default'],
                    'comfortable' => ['name' => 'Comfortable', 'desc' => 'More breathing room'],
                ];
                ?>
                
                <div class="spacing-options-grid">
                    <div class="spacing-option-group">
                        <label class="spacing-option-label">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                                <path d="M9 9h6v6H9z"></path>
                            </svg>
                            Item Padding
                        </label>
                        <p class="spacing-option-desc">Space inside each menu item</p>
                        <div class="spacing-radio-group">
                            <?php foreach ($spacingSizes as $sizeKey => $sizeOption): ?>
                            <label class="spacing-radio">
                                <input type="radio" name="menu_item_padding" value="<?= $sizeKey ?>" 
                                       <?= ($currentTheme['menu_item_padding'] ?? 'medium') === $sizeKey ? 'checked' : '' ?>>
                                <span class="spacing-radio-box">
                                    <span class="spacing-radio-name"><?= $sizeOption['name'] ?></span>
                                </span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="spacing-option-group">
                        <label class="spacing-option-label">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 3v18"></path>
                                <path d="M8 6l4-3 4 3"></path>
                                <path d="M8 18l4 3 4-3"></path>
                            </svg>
                            Item Spacing
                        </label>
                        <p class="spacing-option-desc">Gap between menu items</p>
                        <div class="spacing-radio-group">
                            <?php foreach ($spacingSizes as $sizeKey => $sizeOption): ?>
                            <label class="spacing-radio">
                                <input type="radio" name="menu_item_spacing" value="<?= $sizeKey ?>" 
                                       <?= ($currentTheme['menu_item_spacing'] ?? 'medium') === $sizeKey ? 'checked' : '' ?>>
                                <span class="spacing-radio-box">
                                    <span class="spacing-radio-name"><?= $sizeOption['name'] ?></span>
                                </span>
                            </label>
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

<script>
// Show/hide custom color pickers when color scheme changes
document.querySelectorAll('input[name="color_scheme"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        var pickers = document.getElementById('customColorPickers');
        if (this.value === 'custom') {
            pickers.style.display = 'block';
        } else {
            pickers.style.display = 'none';
        }
    });
});

// Set color from preset
function setColor(type, color) {
    var picker = document.getElementById('custom' + type);
    var text = document.getElementById('custom' + type + 'Text');
    if (picker) picker.value = color;
    if (text) text.value = color.toUpperCase();
    updateCustomPreview();
}

// Sync color picker with text input
document.getElementById('customPrimary').addEventListener('input', function() {
    document.getElementById('customPrimaryText').value = this.value.toUpperCase();
    updateCustomPreview();
});
document.getElementById('customSecondary').addEventListener('input', function() {
    document.getElementById('customSecondaryText').value = this.value.toUpperCase();
    updateCustomPreview();
});
document.getElementById('customSidebar').addEventListener('input', function() {
    document.getElementById('customSidebarText').value = this.value.toUpperCase();
    updateCustomPreview();
});

// Update custom preview colors
function updateCustomPreview() {
    var preview = document.querySelector('.custom-scheme-option .color-scheme-preview');
    if (preview) {
        var spans = preview.querySelectorAll('span');
        if (spans[0]) spans[0].style.background = document.getElementById('customPrimary').value;
        if (spans[1]) spans[1].style.background = document.getElementById('customSecondary').value;
        if (spans[2]) spans[2].style.background = document.getElementById('customSidebar').value;
    }
}

// Font category tabs
document.querySelectorAll('.font-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
        var category = this.dataset.category;
        
        // Update tab active state
        document.querySelectorAll('.font-tab').forEach(function(t) {
            t.classList.remove('active');
        });
        this.classList.add('active');
        
        // Show/hide font categories
        document.querySelectorAll('.font-category').forEach(function(cat) {
            if (cat.dataset.category === category) {
                cat.style.display = 'grid';
            } else {
                cat.style.display = 'none';
            }
        });
    });
});

// Activate the tab containing the currently selected font
(function() {
    var checkedFont = document.querySelector('.font-option input[type="radio"]:checked');
    if (checkedFont) {
        var container = checkedFont.closest('.font-category');
        if (container) {
            var category = container.dataset.category;
            // Activate the right tab
            document.querySelectorAll('.font-tab').forEach(function(tab) {
                tab.classList.toggle('active', tab.dataset.category === category);
            });
            // Show the right category
            document.querySelectorAll('.font-category').forEach(function(cat) {
                cat.style.display = cat.dataset.category === category ? 'grid' : 'none';
            });
        }
    }
})();

// Delete scheme modal
function confirmDeleteScheme(key, name) {
    document.getElementById('deleteSchemeKey').value = key;
    document.getElementById('deleteSchemeName').textContent = name;
    document.getElementById('deleteSchemeModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteSchemeModal').classList.remove('active');
}

document.getElementById('deleteSchemeModal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});
</script>

<!-- Delete Scheme Modal -->
<div class="delete-modal-overlay" id="deleteSchemeModal">
    <div class="delete-modal">
        <h3>Delete Color Scheme?</h3>
        <p>Are you sure you want to delete "<span id="deleteSchemeName"></span>"? This cannot be undone.</p>
        <div class="delete-modal-actions">
            <button type="button" class="btn-modal-cancel" onclick="closeDeleteModal()">Cancel</button>
            <form method="post" style="display: inline;">
                <?= csrfField() ?>
                <input type="hidden" name="delete_scheme" id="deleteSchemeKey">
                <button type="submit" class="btn-modal-delete">Delete</button>
            </form>
        </div>
    </div>
</div>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
