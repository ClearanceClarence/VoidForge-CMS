<?php
/**
 * Theme Settings - VoidForge CMS
 * Customize the active theme's appearance
 */

define('CMS_ROOT', dirname(__DIR__));
require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/user.php';
require_once CMS_ROOT . '/includes/post.php';
require_once CMS_ROOT . '/includes/plugin.php';
require_once CMS_ROOT . '/includes/theme.php';

Post::init();
Plugin::init();
Theme::init();

User::startSession();
User::requireRole('admin');

$pageTitle = 'Theme Settings';
$currentPage = 'theme-settings';

$activeTheme = Theme::getActive();
$themeData = Theme::getActiveData();

// Get current theme settings
$themeSettings = getOption('theme_settings_' . $activeTheme, []);

// Default settings structure per theme
$defaultSettings = [
    'default' => [
        'hero_title' => '',
        'hero_subtitle' => '',
        'primary_color' => '#6366f1',
        'secondary_color' => '#8b5cf6',
        'accent_color' => '#06b6d4',
        'show_features' => true,
        'show_posts' => true,
        'show_stats' => true,
        'feature_1_title' => 'Theme System',
        'feature_1_desc' => 'Switch between beautiful themes instantly. Each theme transforms your entire site.',
        'feature_2_title' => 'Plugin Ready',
        'feature_2_desc' => 'Extend functionality with plugins. Add shortcodes, widgets, and custom features.',
        'feature_3_title' => 'Custom Post Types',
        'feature_3_desc' => 'Create portfolios, testimonials, products—any content type you can imagine.',
        'cta_title' => 'Ready to create?',
        'cta_text' => 'Head to the dashboard to start building your site.',
        'cta_button' => 'Open Dashboard',
        'custom_css' => '',
    ],
    'flavor' => [
        'accent_color' => '#6366f1',
        'content_width' => 'default',
        'show_entry_title' => true,
        'show_entry_meta' => true,
        'show_author' => true,
        'show_date' => true,
        'custom_css' => '',
    ],
];

// Merge defaults with saved settings
$defaults = $defaultSettings[$activeTheme] ?? $defaultSettings['default'];
$themeSettings = array_merge($defaults, $themeSettings);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $newSettings = [];
    
    foreach ($defaults as $key => $defaultValue) {
        if (is_bool($defaultValue)) {
            $newSettings[$key] = isset($_POST[$key]);
        } else {
            $newSettings[$key] = $_POST[$key] ?? $defaultValue;
        }
    }
    
    setOption('theme_settings_' . $activeTheme, $newSettings);
    setFlash('success', 'Theme settings saved successfully.');
    redirect(ADMIN_URL . '/theme-settings.php');
}

include ADMIN_PATH . '/includes/header.php';
?>

<style>
.settings-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.settings-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    margin: 0;
}

.theme-indicator {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.625rem 1rem;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 10px;
}

.theme-indicator-dot {
    width: 10px;
    height: 10px;
    background: #22c55e;
    border-radius: 50%;
}

.theme-indicator-name {
    font-weight: 600;
}

.settings-grid {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 2rem;
}

.settings-nav {
    position: sticky;
    top: 1rem;
    height: fit-content;
}

.settings-nav-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    color: var(--text-secondary);
    text-decoration: none;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.15s;
    cursor: pointer;
}

.settings-nav-item:hover {
    background: var(--bg-card);
    color: var(--text-primary);
}

.settings-nav-item.active {
    background: var(--forge-primary);
    color: #fff;
}

.settings-content {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.settings-section {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    overflow: hidden;
}

.section-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
    background: var(--bg-card-header);
}

.section-header h2 {
    font-size: 1.0625rem;
    font-weight: 700;
    margin: 0 0 0.25rem 0;
}

.section-header p {
    font-size: 0.875rem;
    color: var(--text-muted);
    margin: 0;
}

.section-body {
    padding: 1.5rem;
}

.form-row {
    margin-bottom: 1.25rem;
}

.form-row:last-child {
    margin-bottom: 0;
}

.form-label {
    display: block;
    font-weight: 600;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
    color: var(--text-primary);
}

.form-hint {
    font-size: 0.8125rem;
    color: var(--text-muted);
    margin-top: 0.375rem;
}

.form-input {
    width: 100%;
    padding: 0.625rem 0.875rem;
    background: var(--bg-input);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    font-size: 0.9375rem;
    color: var(--text-primary);
    transition: all 0.15s;
}

.form-input:focus {
    outline: none;
    border-color: var(--forge-primary);
    box-shadow: 0 0 0 3px var(--forge-primary-bg);
}

.form-textarea {
    min-height: 120px;
    resize: vertical;
    font-family: monospace;
}

.color-input-group {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.color-preview {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    border: 2px solid var(--border-color);
    cursor: pointer;
    overflow: hidden;
}

.color-preview input {
    width: 60px;
    height: 60px;
    margin: -8px;
    cursor: pointer;
    border: none;
}

.color-hex {
    flex: 1;
    max-width: 140px;
}

.toggle-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 0;
    border-bottom: 1px solid var(--border-color);
}

.toggle-row:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.toggle-row:first-child {
    padding-top: 0;
}

.toggle-label {
    font-weight: 500;
}

.toggle-desc {
    font-size: 0.8125rem;
    color: var(--text-muted);
    margin-top: 0.125rem;
}

.toggle-switch {
    position: relative;
    width: 48px;
    height: 26px;
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
    background: var(--bg-card-header);
    border-radius: 26px;
    transition: 0.2s;
}

.toggle-slider::before {
    content: '';
    position: absolute;
    height: 20px;
    width: 20px;
    left: 3px;
    bottom: 3px;
    background: #fff;
    border-radius: 50%;
    transition: 0.2s;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}

.toggle-switch input:checked + .toggle-slider {
    background: var(--forge-primary);
}

.toggle-switch input:checked + .toggle-slider::before {
    transform: translateX(22px);
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.form-grid-3 {
    grid-template-columns: repeat(3, 1fr);
}

.feature-card {
    background: var(--bg-card-header);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1rem;
}

.feature-card-header {
    font-weight: 600;
    font-size: 0.8125rem;
    color: var(--text-muted);
    margin-bottom: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.btn-save {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 2rem;
    background: linear-gradient(135deg, var(--forge-primary), var(--forge-secondary));
    color: #fff;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px var(--forge-shadow-color-hover);
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    padding: 1.5rem;
    background: var(--bg-card-header);
    border-top: 1px solid var(--border-color);
}

@media (max-width: 900px) {
    .settings-grid {
        grid-template-columns: 1fr;
    }
    .settings-nav {
        display: flex;
        gap: 0.5rem;
        overflow-x: auto;
        padding-bottom: 0.5rem;
    }
    .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="settings-header">
    <h1>Theme Settings</h1>
    <div class="theme-indicator">
        <span class="theme-indicator-dot"></span>
        <span class="theme-indicator-name"><?= esc($themeData['name'] ?? ucfirst($activeTheme)) ?> Theme</span>
    </div>
</div>

<?php if ($activeTheme === 'flavor'): ?>
<!-- Flavor Theme Settings - Simple Form -->
<form method="POST">
    <?= csrfField() ?>
    
    <div class="settings-grid">
        <nav class="settings-nav">
            <a href="#colors" class="settings-nav-item active" onclick="scrollToSection('colors', this)">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 2a10 10 0 0 0 0 20 10 10 0 0 0 0-20"/><path d="M12 2v20"/></svg>
                Colors
            </a>
            <a href="#layout" class="settings-nav-item" onclick="scrollToSection('layout', this)">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                Layout
            </a>
            <a href="#display" class="settings-nav-item" onclick="scrollToSection('display', this)">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                Display
            </a>
            <a href="#css" class="settings-nav-item" onclick="scrollToSection('css', this)">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                Custom CSS
            </a>
        </nav>
        
        <div class="settings-content">
            <!-- Colors -->
            <section class="settings-section" id="colors">
                <div class="section-header">
                    <h2>Accent Color</h2>
                    <p>The primary accent color used throughout the theme</p>
                </div>
                <div class="section-body">
                    <div class="form-row">
                        <label class="form-label">Accent Color</label>
                        <div class="color-input-group">
                            <div class="color-preview" style="background: <?= esc($themeSettings['accent_color']) ?>">
                                <input type="color" name="accent_color" value="<?= esc($themeSettings['accent_color']) ?>" onchange="this.parentElement.style.background = this.value; this.parentElement.nextElementSibling.value = this.value">
                            </div>
                            <input type="text" class="form-input color-hex" value="<?= esc($themeSettings['accent_color']) ?>" onchange="this.previousElementSibling.style.background = this.value; this.previousElementSibling.querySelector('input').value = this.value" pattern="^#[0-9A-Fa-f]{6}$">
                        </div>
                        <p class="form-hint">Used for buttons, links, hero background, and interactive elements</p>
                    </div>
                </div>
            </section>
            
            <!-- Layout -->
            <section class="settings-section" id="layout">
                <div class="section-header">
                    <h2>Content Width</h2>
                    <p>Control the maximum width of page content</p>
                </div>
                <div class="section-body">
                    <div class="form-row">
                        <label class="form-label">Content Width</label>
                        <select name="content_width" class="form-input">
                            <option value="narrow" <?= ($themeSettings['content_width'] ?? 'default') === 'narrow' ? 'selected' : '' ?>>Narrow (680px)</option>
                            <option value="default" <?= ($themeSettings['content_width'] ?? 'default') === 'default' ? 'selected' : '' ?>>Default (780px)</option>
                            <option value="wide" <?= ($themeSettings['content_width'] ?? 'default') === 'wide' ? 'selected' : '' ?>>Wide (920px)</option>
                        </select>
                        <p class="form-hint">Affects the maximum width of article content and block showcase sections</p>
                    </div>
                </div>
            </section>
            
            <!-- Display Options -->
            <section class="settings-section" id="display">
                <div class="section-header">
                    <h2>Post Display</h2>
                    <p>Control what information is shown on posts</p>
                </div>
                <div class="section-body">
                    <div class="toggle-row">
                        <div>
                            <div class="toggle-label">Show Entry Title</div>
                            <div class="toggle-desc">Display the page/post title in the entry header</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="show_entry_title" <?= ($themeSettings['show_entry_title'] ?? true) ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="toggle-row">
                        <div>
                            <div class="toggle-label">Show Entry Meta</div>
                            <div class="toggle-desc">Display the meta info bar (date, author, read time) on posts and pages</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="show_entry_meta" <?= ($themeSettings['show_entry_meta'] ?? true) ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="toggle-row">
                        <div>
                            <div class="toggle-label">Show Author</div>
                            <div class="toggle-desc">Display author name on posts</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="show_author" <?= ($themeSettings['show_author'] ?? true) ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="toggle-row">
                        <div>
                            <div class="toggle-label">Show Date</div>
                            <div class="toggle-desc">Display publish date on posts</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="show_date" <?= ($themeSettings['show_date'] ?? true) ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </section>
            
            <!-- Custom CSS -->
            <section class="settings-section" id="css">
                <div class="section-header">
                    <h2>Custom CSS</h2>
                    <p>Add custom styles to override theme defaults</p>
                </div>
                <div class="section-body">
                    <div class="form-row">
                        <label class="form-label">Custom Styles</label>
                        <textarea name="custom_css" class="form-textarea code" rows="12" placeholder="/* Your custom CSS here */"><?= esc($themeSettings['custom_css'] ?? '') ?></textarea>
                        <p class="form-hint">CSS will be added after theme styles. Use browser inspector to find element selectors.</p>
                    </div>
                </div>
            </section>
        </div>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save Settings</button>
    </div>
</form>

<?php else: ?>
<!-- Default/Other Themes Settings -->
<form method="POST">
    <?= csrfField() ?>
    
    <div class="settings-grid">
        <nav class="settings-nav">
            <a href="#hero" class="settings-nav-item active" onclick="scrollToSection('hero', this)">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                Hero Section
            </a>
            <a href="#colors" class="settings-nav-item" onclick="scrollToSection('colors', this)">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 2a10 10 0 0 0 0 20 10 10 0 0 0 0-20"/><path d="M12 2v20"/></svg>
                Colors
            </a>
            <a href="#sections" class="settings-nav-item" onclick="scrollToSection('sections', this)">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                Sections
            </a>
            <a href="#features" class="settings-nav-item" onclick="scrollToSection('features', this)">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                Features
            </a>
            <a href="#cta" class="settings-nav-item" onclick="scrollToSection('cta', this)">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                Call to Action
            </a>
            <a href="#css" class="settings-nav-item" onclick="scrollToSection('css', this)">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                Custom CSS
            </a>
        </nav>
        
        <div class="settings-content">
            <!-- Hero Section -->
            <section class="settings-section" id="hero">
                <div class="section-header">
                    <h2>Hero Section</h2>
                    <p>Customize the main headline and intro text</p>
                </div>
                <div class="section-body">
                    <div class="form-row">
                        <label class="form-label">Hero Title</label>
                        <input type="text" name="hero_title" class="form-input" value="<?= esc($themeSettings['hero_title']) ?>" placeholder="Leave empty to use site title">
                        <p class="form-hint">The main headline displayed in the hero section</p>
                    </div>
                    <div class="form-row">
                        <label class="form-label">Hero Subtitle</label>
                        <input type="text" name="hero_subtitle" class="form-input" value="<?= esc($themeSettings['hero_subtitle']) ?>" placeholder="Leave empty to use site tagline">
                        <p class="form-hint">Supporting text below the headline</p>
                    </div>
                </div>
            </section>
            
            <!-- Colors -->
            <section class="settings-section" id="colors">
                <div class="section-header">
                    <h2>Colors</h2>
                    <p>Customize the theme color palette</p>
                </div>
                <div class="section-body">
                    <div class="form-grid form-grid-3">
                        <div class="form-row">
                            <label class="form-label">Primary Color</label>
                            <div class="color-input-group">
                                <div class="color-preview" style="background: <?= esc($themeSettings['primary_color']) ?>">
                                    <input type="color" name="primary_color" value="<?= esc($themeSettings['primary_color']) ?>" onchange="this.parentElement.style.background = this.value; this.parentElement.nextElementSibling.value = this.value">
                                </div>
                                <input type="text" class="form-input color-hex" value="<?= esc($themeSettings['primary_color']) ?>" onchange="this.previousElementSibling.style.background = this.value; this.previousElementSibling.querySelector('input').value = this.value" pattern="^#[0-9A-Fa-f]{6}$">
                            </div>
                        </div>
                        <div class="form-row">
                            <label class="form-label">Secondary Color</label>
                            <div class="color-input-group">
                                <div class="color-preview" style="background: <?= esc($themeSettings['secondary_color']) ?>">
                                    <input type="color" name="secondary_color" value="<?= esc($themeSettings['secondary_color']) ?>" onchange="this.parentElement.style.background = this.value; this.parentElement.nextElementSibling.value = this.value">
                                </div>
                                <input type="text" class="form-input color-hex" value="<?= esc($themeSettings['secondary_color']) ?>" onchange="this.previousElementSibling.style.background = this.value; this.previousElementSibling.querySelector('input').value = this.value" pattern="^#[0-9A-Fa-f]{6}$">
                            </div>
                        </div>
                        <div class="form-row">
                            <label class="form-label">Accent Color</label>
                            <div class="color-input-group">
                                <div class="color-preview" style="background: <?= esc($themeSettings['accent_color']) ?>">
                                    <input type="color" name="accent_color" value="<?= esc($themeSettings['accent_color']) ?>" onchange="this.parentElement.style.background = this.value; this.parentElement.nextElementSibling.value = this.value">
                                </div>
                                <input type="text" class="form-input color-hex" value="<?= esc($themeSettings['accent_color']) ?>" onchange="this.previousElementSibling.style.background = this.value; this.previousElementSibling.querySelector('input').value = this.value" pattern="^#[0-9A-Fa-f]{6}$">
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Sections Toggle -->
            <section class="settings-section" id="sections">
                <div class="section-header">
                    <h2>Page Sections</h2>
                    <p>Show or hide different sections on the homepage</p>
                </div>
                <div class="section-body">
                    <div class="toggle-row">
                        <div>
                            <div class="toggle-label">Features Section</div>
                            <div class="toggle-desc">Display the features/benefits grid</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="show_features" <?= $themeSettings['show_features'] ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="toggle-row">
                        <div>
                            <div class="toggle-label">Latest Posts</div>
                            <div class="toggle-desc">Show recent blog posts on homepage</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="show_posts" <?= $themeSettings['show_posts'] ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </section>
            
            <!-- Features -->
            <section class="settings-section" id="features">
                <div class="section-header">
                    <h2>Features</h2>
                    <p>Customize the feature cards content</p>
                </div>
                <div class="section-body">
                    <div class="form-grid">
                        <div class="feature-card">
                            <div class="feature-card-header">Feature 1</div>
                            <div class="form-row">
                                <label class="form-label">Title</label>
                                <input type="text" name="feature_1_title" class="form-input" value="<?= esc($themeSettings['feature_1_title']) ?>">
                            </div>
                            <div class="form-row">
                                <label class="form-label">Description</label>
                                <input type="text" name="feature_1_desc" class="form-input" value="<?= esc($themeSettings['feature_1_desc']) ?>">
                            </div>
                        </div>
                        <div class="feature-card">
                            <div class="feature-card-header">Feature 2</div>
                            <div class="form-row">
                                <label class="form-label">Title</label>
                                <input type="text" name="feature_2_title" class="form-input" value="<?= esc($themeSettings['feature_2_title']) ?>">
                            </div>
                            <div class="form-row">
                                <label class="form-label">Description</label>
                                <input type="text" name="feature_2_desc" class="form-input" value="<?= esc($themeSettings['feature_2_desc']) ?>">
                            </div>
                        </div>
                        <div class="feature-card">
                            <div class="feature-card-header">Feature 3</div>
                            <div class="form-row">
                                <label class="form-label">Title</label>
                                <input type="text" name="feature_3_title" class="form-input" value="<?= esc($themeSettings['feature_3_title']) ?>">
                            </div>
                            <div class="form-row">
                                <label class="form-label">Description</label>
                                <input type="text" name="feature_3_desc" class="form-input" value="<?= esc($themeSettings['feature_3_desc']) ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- CTA -->
            <section class="settings-section" id="cta">
                <div class="section-header">
                    <h2>Call to Action</h2>
                    <p>Customize the CTA section</p>
                </div>
                <div class="section-body">
                    <div class="form-row">
                        <label class="form-label">CTA Title</label>
                        <input type="text" name="cta_title" class="form-input" value="<?= esc($themeSettings['cta_title']) ?>">
                    </div>
                    <div class="form-row">
                        <label class="form-label">CTA Text</label>
                        <input type="text" name="cta_text" class="form-input" value="<?= esc($themeSettings['cta_text']) ?>">
                    </div>
                    <?php if ($activeTheme === 'default'): ?>
                    <div class="form-row">
                        <label class="form-label">Button Text</label>
                        <input type="text" name="cta_button" class="form-input" value="<?= esc($themeSettings['cta_button'] ?? 'Open Dashboard') ?>">
                    </div>
                    <?php endif; ?>
                </div>
            </section>
            
            <!-- Custom CSS -->
            <section class="settings-section" id="css">
                <div class="section-header">
                    <h2>Custom CSS</h2>
                    <p>Add custom styles for this theme</p>
                </div>
                <div class="section-body">
                    <div class="form-row">
                        <label class="form-label">CSS Code</label>
                        <textarea name="custom_css" class="form-input form-textarea" placeholder="/* Add your custom CSS here */"><?= esc($themeSettings['custom_css']) ?></textarea>
                        <p class="form-hint">These styles will only apply when this theme is active</p>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-save">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        Save Settings
                    </button>
                </div>
            </section>
        </div>
    </div>
</form>
<?php endif; ?>

<script>
function scrollToSection(id, el) {
    event.preventDefault();
    document.querySelectorAll('.settings-nav-item').forEach(item => item.classList.remove('active'));
    el.classList.add('active');
    document.getElementById(id).scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Update nav on scroll
const sections = document.querySelectorAll('.settings-section');
const navItems = document.querySelectorAll('.settings-nav-item');

window.addEventListener('scroll', () => {
    let current = '';
    sections.forEach(section => {
        const sectionTop = section.offsetTop;
        if (scrollY >= sectionTop - 100) {
            current = section.getAttribute('id');
        }
    });
    
    navItems.forEach(item => {
        item.classList.remove('active');
        if (item.getAttribute('href') === '#' + current) {
            item.classList.add('active');
        }
    });
});
</script>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
