<?php
/**
 * SEO Settings Page - VoidForge CMS
 * 
 * Global SEO configuration including meta tags, sitemaps,
 * social media settings, and schema markup.
 * 
 * @package VoidForge
 * @since 0.3.0
 */

define('CMS_ROOT', dirname(__DIR__));
require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/user.php';
require_once CMS_ROOT . '/includes/post.php';
require_once CMS_ROOT . '/includes/media.php';
require_once CMS_ROOT . '/includes/plugin.php';
require_once CMS_ROOT . '/includes/seo.php';

User::startSession();
User::requireLogin();
User::requireRole('admin');

Post::init();
Plugin::init();

$currentPage = 'seo-settings';
$pageTitle = 'SEO Settings';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    // General
    setOption('seo_title_separator', $_POST['seo_title_separator'] ?? '|');
    setOption('seo_title_format', $_POST['seo_title_format'] ?? 'post_first');
    setOption('seo_home_title', trim($_POST['seo_home_title'] ?? ''));
    setOption('seo_home_description', trim($_POST['seo_home_description'] ?? ''));
    setOption('seo_noindex_site', isset($_POST['seo_noindex_site']));
    
    // Social
    setOption('seo_og_default_image', trim($_POST['seo_og_default_image'] ?? ''));
    setOption('seo_twitter_card_type', $_POST['seo_twitter_card_type'] ?? 'summary_large_image');
    setOption('seo_twitter_site', trim($_POST['seo_twitter_site'] ?? ''));
    setOption('seo_locale', trim($_POST['seo_locale'] ?? 'en_US'));
    
    // Schema
    setOption('seo_schema_org_type', $_POST['seo_schema_org_type'] ?? 'Organization');
    setOption('seo_schema_org_name', trim($_POST['seo_schema_org_name'] ?? ''));
    setOption('seo_schema_org_logo', trim($_POST['seo_schema_org_logo'] ?? ''));
    
    // Sitemap
    setOption('seo_sitemap_enabled', isset($_POST['seo_sitemap_enabled']));
    $postTypes = $_POST['seo_sitemap_post_types'] ?? [];
    setOption('seo_sitemap_post_types', array_values($postTypes));
    setOption('seo_sitemap_taxonomies', isset($_POST['seo_sitemap_taxonomies']));
    
    // Robots
    setOption('seo_robots_txt', trim($_POST['seo_robots_txt'] ?? ''));
    
    setFlash('success', 'SEO settings saved successfully.');
    redirect(ADMIN_URL . '/seo-settings.php');
}

// Get current settings
$settings = [
    'seo_title_separator' => getOption('seo_title_separator', '|'),
    'seo_title_format' => getOption('seo_title_format', 'post_first'),
    'seo_home_title' => getOption('seo_home_title', ''),
    'seo_home_description' => getOption('seo_home_description', ''),
    'seo_noindex_site' => getOption('seo_noindex_site', false),
    'seo_og_default_image' => getOption('seo_og_default_image', ''),
    'seo_twitter_card_type' => getOption('seo_twitter_card_type', 'summary_large_image'),
    'seo_twitter_site' => getOption('seo_twitter_site', ''),
    'seo_locale' => getOption('seo_locale', 'en_US'),
    'seo_schema_org_type' => getOption('seo_schema_org_type', 'Organization'),
    'seo_schema_org_name' => getOption('seo_schema_org_name', ''),
    'seo_schema_org_logo' => getOption('seo_schema_org_logo', ''),
    'seo_sitemap_enabled' => getOption('seo_sitemap_enabled', true),
    'seo_sitemap_post_types' => getOption('seo_sitemap_post_types', ['post', 'page']),
    'seo_sitemap_taxonomies' => getOption('seo_sitemap_taxonomies', true),
    'seo_robots_txt' => getOption('seo_robots_txt', ''),
];

// Ensure array for post types
if (!is_array($settings['seo_sitemap_post_types'])) {
    $settings['seo_sitemap_post_types'] = ['post', 'page'];
}

// Get all post types for sitemap settings
$allPostTypes = Post::getTypes();

include CMS_ROOT . '/admin/includes/header.php';
?>

<style>
/* SEO Settings Page Styles */
.settings-page { max-width: 1200px; margin: 0 auto; }
.settings-header { margin-bottom: 2rem; }
.settings-header h1 { font-size: 1.75rem; font-weight: 700; color: #1e293b; margin: 0 0 0.5rem 0; }
.settings-header p { color: #64748b; margin: 0; }

/* Tabs */
.settings-tabs { display: flex; gap: 0.5rem; margin-bottom: 2rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0; }
.settings-tab { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.875rem 1.25rem; background: none; border: none; font-size: 0.9375rem; font-weight: 500; color: #64748b; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -1px; transition: all 0.2s ease; white-space: nowrap; }
.settings-tab:hover { color: #1e293b; }
.settings-tab.active { color: #6366f1; border-bottom-color: #6366f1; }
.settings-tab svg { opacity: 0.7; }
.settings-tab.active svg { opacity: 1; }

/* Sections */
.settings-section { display: none; animation: fadeIn 0.3s ease; }
.settings-section.active { display: block; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

/* Cards */
.settings-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; margin-bottom: 1.5rem; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
.settings-card-header { display: flex; align-items: center; gap: 1rem; padding: 1.25rem 1.5rem; background: linear-gradient(135deg, #f8fafc 0%, #fff 100%); border-bottom: 1px solid #e2e8f0; }
.settings-card-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.settings-card-icon.purple { background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(139, 92, 246, 0.15) 100%); color: #6366f1; }
.settings-card-icon.blue { background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(37, 99, 235, 0.15) 100%); color: #3b82f6; }
.settings-card-icon.green { background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(5, 150, 105, 0.15) 100%); color: #10b981; }
.settings-card-icon.orange { background: linear-gradient(135deg, rgba(245, 158, 11, 0.15) 0%, rgba(217, 119, 6, 0.15) 100%); color: #f59e0b; }
.settings-card-icon.pink { background: linear-gradient(135deg, rgba(236, 72, 153, 0.15) 0%, rgba(219, 39, 119, 0.15) 100%); color: #ec4899; }
.settings-card-icon.cyan { background: linear-gradient(135deg, rgba(6, 182, 212, 0.15) 0%, rgba(8, 145, 178, 0.15) 100%); color: #06b6d4; }
.settings-card-title { flex: 1; }
.settings-card-title h3 { font-size: 1rem; font-weight: 600; color: #1e293b; margin: 0 0 0.25rem 0; }
.settings-card-title p { font-size: 0.8125rem; color: #64748b; margin: 0; }
.settings-card-body { padding: 1.5rem; }

/* Forms */
.form-grid { display: grid; gap: 1.5rem; }
.form-grid-2 { grid-template-columns: repeat(2, 1fr); }
@media (max-width: 640px) { .form-grid-2 { grid-template-columns: 1fr; } }
.form-group { display: flex; flex-direction: column; gap: 0.5rem; }
.form-label { font-size: 0.875rem; font-weight: 600; color: #374151; }
.form-input, .form-select, .form-textarea { padding: 0.75rem 1rem; font-size: 0.9375rem; border: 1.5px solid #e2e8f0; border-radius: 10px; background: #fff; color: #1e293b; transition: all 0.2s ease; font-family: inherit; }
.form-input:hover, .form-select:hover, .form-textarea:hover { border-color: #cbd5e1; }
.form-input:focus, .form-select:focus, .form-textarea:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }
.form-textarea { resize: vertical; min-height: 100px; }
.form-textarea.code { font-family: 'JetBrains Mono', ui-monospace, monospace; font-size: 0.8125rem; }
.form-hint { font-size: 0.8125rem; color: #9ca3af; }

/* Character Counter */
.char-counter { display: flex; align-items: center; justify-content: space-between; margin-top: 0.5rem; }
.char-bar { flex: 1; height: 4px; background: #e2e8f0; border-radius: 2px; margin-right: 0.75rem; overflow: hidden; }
.char-progress { height: 100%; background: linear-gradient(90deg, #6366f1, #8b5cf6); border-radius: 2px; transition: width 0.2s ease; }
.char-progress.warning { background: linear-gradient(90deg, #f59e0b, #d97706); }
.char-progress.error { background: linear-gradient(90deg, #ef4444, #dc2626); }
.char-count { font-size: 0.75rem; color: #9ca3af; font-weight: 500; min-width: 60px; text-align: right; }

/* Toggle */
.toggle-row { display: flex; align-items: center; gap: 0.75rem; cursor: pointer; }
.toggle-row input[type="checkbox"] { display: none; }
.toggle-switch { position: relative; width: 44px; height: 24px; background: #e2e8f0; border-radius: 12px; transition: all 0.3s ease; flex-shrink: 0; }
.toggle-switch::after { content: ''; position: absolute; top: 2px; left: 2px; width: 20px; height: 20px; background: white; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,0.2); transition: all 0.3s ease; }
.toggle-row input:checked + .toggle-switch { background: linear-gradient(135deg, #6366f1, #8b5cf6); }
.toggle-row input:checked + .toggle-switch::after { left: 22px; }
.toggle-label { font-size: 0.9375rem; font-weight: 500; color: #1e293b; }
.toggle-label small { display: block; font-size: 0.8125rem; font-weight: 400; color: #64748b; margin-top: 0.125rem; }
.toggle-row.warning .toggle-switch { background: #fde68a; }
.toggle-row.warning input:checked + .toggle-switch { background: linear-gradient(135deg, #f59e0b, #d97706); }

/* Checkbox Grid */
.checkbox-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 0.75rem; }
.checkbox-item { display: flex; align-items: center; gap: 0.625rem; padding: 0.75rem 1rem; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 10px; cursor: pointer; transition: all 0.2s ease; }
.checkbox-item:hover { border-color: #cbd5e1; background: #f1f5f9; }
.checkbox-item input[type="checkbox"] { accent-color: #6366f1; width: 16px; height: 16px; flex-shrink: 0; }
.checkbox-item span { font-size: 0.875rem; font-weight: 500; color: #1e293b; }

/* URL Box */
.url-box { display: flex; align-items: center; gap: 0.75rem; padding: 0.875rem 1rem; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border: 1px solid #e2e8f0; border-radius: 10px; margin-bottom: 1.25rem; }
.url-box code { flex: 1; font-family: 'JetBrains Mono', ui-monospace, monospace; font-size: 0.8125rem; color: #6366f1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* Preview */
.preview-card { background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-radius: 12px; padding: 1.25rem; margin-top: 1rem; }
.preview-label { font-size: 0.6875rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.75rem; }
.google-preview { background: #fff; border-radius: 8px; padding: 1rem; border: 1px solid #e2e8f0; }
.google-preview-title { color: #1a0dab; font-size: 1.125rem; font-weight: 400; line-height: 1.3; margin-bottom: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.google-preview-url { color: #006621; font-size: 0.8125rem; margin-bottom: 4px; }
.google-preview-desc { color: #545454; font-size: 0.8125rem; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.schema-preview { background: #1e293b; border-radius: 10px; padding: 1rem 1.25rem; overflow-x: auto; }
.schema-preview code { font-family: 'JetBrains Mono', ui-monospace, monospace; font-size: 0.75rem; color: #a5f3fc; white-space: pre; line-height: 1.6; }

/* Image Picker */
.image-picker { display: flex; align-items: flex-start; gap: 1rem; }
.image-preview { width: 120px; height: 63px; background: #f1f5f9; border: 2px dashed #e2e8f0; border-radius: 10px; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative; color: #94a3b8; }
.image-preview img { width: 100%; height: 100%; object-fit: cover; }
.image-preview.has-image { border-style: solid; border-color: #e2e8f0; }

/* Buttons */
.btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1rem; font-size: 0.875rem; font-weight: 500; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; transition: all 0.2s ease; }
.btn-secondary { background: #fff; color: #374151; border: 1.5px solid #e2e8f0; }
.btn-secondary:hover { border-color: #6366f1; color: #6366f1; }
.btn-sm { padding: 0.5rem 0.75rem; font-size: 0.8125rem; }
.settings-actions { display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e2e8f0; }
.btn-save { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.875rem 2rem; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: #fff; border: none; border-radius: 12px; font-size: 0.9375rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 4px 15px rgba(99, 102, 241, 0.35); }
.btn-save:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(99, 102, 241, 0.45); }

/* Alert */
.alert { display: flex; align-items: center; gap: 0.75rem; padding: 1rem 1.25rem; border-radius: 12px; margin-bottom: 1.5rem; font-size: 0.9375rem; font-weight: 500; }
.alert-success { background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.1) 100%); border: 1px solid rgba(16, 185, 129, 0.2); color: #059669; }
</style>

<div class="admin-content">
    <?php include CMS_ROOT . '/admin/includes/sidebar.php'; ?>

    <main class="admin-main">
        <div class="settings-page">
            <div class="settings-header">
                <h1>SEO Settings</h1>
                <p>Optimize your site for search engines and social media</p>
            </div>

            <?php if ($flash = getFlash('success')): ?>
            <div class="alert alert-success">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <?= esc($flash) ?>
            </div>
            <?php endif; ?>

            <!-- Tabs -->
            <div class="settings-tabs">
                <button type="button" class="settings-tab active" data-tab="general">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg>
                    General
                </button>
                <button type="button" class="settings-tab" data-tab="social">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>
                    Social Media
                </button>
                <button type="button" class="settings-tab" data-tab="schema">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
                    Schema
                </button>
                <button type="button" class="settings-tab" data-tab="sitemap">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                    Sitemap
                </button>
                <button type="button" class="settings-tab" data-tab="robots">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                    Robots.txt
                </button>
            </div>

            <form method="POST">
                <?= csrfField() ?>

                <!-- General Tab -->
                <div class="settings-section active" data-section="general">
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <div class="settings-card-icon purple">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7V4h16v3"></path><path d="M9 20h6"></path><path d="M12 4v16"></path></svg>
                            </div>
                            <div class="settings-card-title">
                                <h3>Title Settings</h3>
                                <p>Configure how page titles appear in search results</p>
                            </div>
                        </div>
                        <div class="settings-card-body">
                            <div class="form-grid form-grid-2">
                                <div class="form-group">
                                    <label class="form-label">Title Separator</label>
                                    <select name="seo_title_separator" class="form-select">
                                        <?php foreach (SEO::TITLE_SEPARATORS as $value => $label): ?>
                                        <option value="<?= esc($value) ?>" <?= $settings['seo_title_separator'] === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="form-hint">Character between post title and site name</span>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Title Format</label>
                                    <select name="seo_title_format" class="form-select">
                                        <option value="post_first" <?= $settings['seo_title_format'] === 'post_first' ? 'selected' : '' ?>>Post Title | Site Name</option>
                                        <option value="site_first" <?= $settings['seo_title_format'] === 'site_first' ? 'selected' : '' ?>>Site Name | Post Title</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="settings-card">
                        <div class="settings-card-header">
                            <div class="settings-card-icon blue">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                            </div>
                            <div class="settings-card-title">
                                <h3>Homepage SEO</h3>
                                <p>Meta information for your homepage</p>
                            </div>
                        </div>
                        <div class="settings-card-body">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Homepage Title</label>
                                    <input type="text" name="seo_home_title" id="seo_home_title" class="form-input" value="<?= esc($settings['seo_home_title']) ?>" maxlength="70" placeholder="Leave empty to use Site Name | Tagline">
                                    <div class="char-counter">
                                        <div class="char-bar"><div class="char-progress" id="homeTitleProgress"></div></div>
                                        <span class="char-count"><span id="homeTitleCount">0</span>/60</span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Homepage Meta Description</label>
                                    <textarea name="seo_home_description" id="seo_home_description" class="form-textarea" rows="3" maxlength="200" placeholder="Write a compelling description for your homepage..."><?= esc($settings['seo_home_description']) ?></textarea>
                                    <div class="char-counter">
                                        <div class="char-bar"><div class="char-progress" id="homeDescProgress"></div></div>
                                        <span class="char-count"><span id="homeDescCount">0</span>/160</span>
                                    </div>
                                </div>
                            </div>
                            <div class="preview-card">
                                <div class="preview-label">Search Preview</div>
                                <div class="google-preview">
                                    <div class="google-preview-title" id="previewTitle"><?= esc($settings['seo_home_title'] ?: get_site_name()) ?></div>
                                    <div class="google-preview-url"><?= esc(SITE_URL) ?></div>
                                    <div class="google-preview-desc" id="previewDesc"><?= esc($settings['seo_home_description'] ?: get_site_description() ?: 'Your homepage description will appear here...') ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="settings-card">
                        <div class="settings-card-header">
                            <div class="settings-card-icon orange">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                            </div>
                            <div class="settings-card-title">
                                <h3>Search Visibility</h3>
                                <p>Control how search engines index your site</p>
                            </div>
                        </div>
                        <div class="settings-card-body">
                            <label class="toggle-row warning">
                                <input type="checkbox" name="seo_noindex_site" value="1" <?= $settings['seo_noindex_site'] ? 'checked' : '' ?>>
                                <span class="toggle-switch"></span>
                                <span class="toggle-label">
                                    Discourage search engines from indexing this site
                                    <small>Adds noindex to all pages. Use only for staging or development sites.</small>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Social Media Tab -->
                <div class="settings-section" data-section="social">
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <div class="settings-card-icon blue">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
                            </div>
                            <div class="settings-card-title">
                                <h3>Open Graph Settings</h3>
                                <p>Control how your content appears when shared on Facebook and other platforms</p>
                            </div>
                        </div>
                        <div class="settings-card-body">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Default Share Image</label>
                                    <div class="image-picker">
                                        <div class="image-preview <?= $settings['seo_og_default_image'] ? 'has-image' : '' ?>" id="ogImagePreview">
                                            <?php if ($settings['seo_og_default_image']): ?>
                                            <img src="<?= esc($settings['seo_og_default_image']) ?>" alt="">
                                            <?php else: ?>
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <input type="text" name="seo_og_default_image" id="seo_og_default_image" class="form-input" value="<?= esc($settings['seo_og_default_image']) ?>" placeholder="https://..." style="margin-bottom: 0.5rem;">
                                            <span class="form-hint">1200×630px recommended. Used when posts don't have a featured image.</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Site Locale</label>
                                    <input type="text" name="seo_locale" class="form-input" value="<?= esc($settings['seo_locale']) ?>" placeholder="en_US">
                                    <span class="form-hint">e.g., en_US, en_GB, de_DE, fr_FR</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="settings-card">
                        <div class="settings-card-header">
                            <div class="settings-card-icon cyan">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path></svg>
                            </div>
                            <div class="settings-card-title">
                                <h3>Twitter Card Settings</h3>
                                <p>Control how your content appears when shared on Twitter/X</p>
                            </div>
                        </div>
                        <div class="settings-card-body">
                            <div class="form-grid form-grid-2">
                                <div class="form-group">
                                    <label class="form-label">Card Type</label>
                                    <select name="seo_twitter_card_type" class="form-select">
                                        <?php foreach (SEO::TWITTER_CARD_TYPES as $value => $label): ?>
                                        <option value="<?= esc($value) ?>" <?= $settings['seo_twitter_card_type'] === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Twitter Username</label>
                                    <input type="text" name="seo_twitter_site" class="form-input" value="<?= esc($settings['seo_twitter_site']) ?>" placeholder="@username">
                                    <span class="form-hint">Your site's Twitter handle</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Schema Tab -->
                <div class="settings-section" data-section="schema">
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <div class="settings-card-icon purple">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
                            </div>
                            <div class="settings-card-title">
                                <h3>Schema.org Markup</h3>
                                <p>Structured data for rich search results</p>
                            </div>
                        </div>
                        <div class="settings-card-body">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Organization Type</label>
                                    <select name="seo_schema_org_type" class="form-select">
                                        <?php foreach (SEO::SCHEMA_TYPES as $value => $label): ?>
                                        <option value="<?= esc($value) ?>" <?= $settings['seo_schema_org_type'] === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-grid form-grid-2">
                                    <div class="form-group">
                                        <label class="form-label">Organization/Person Name</label>
                                        <input type="text" name="seo_schema_org_name" class="form-input" value="<?= esc($settings['seo_schema_org_name']) ?>" placeholder="Leave empty to use site name">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Logo URL</label>
                                        <input type="text" name="seo_schema_org_logo" class="form-input" value="<?= esc($settings['seo_schema_org_logo']) ?>" placeholder="https://...">
                                        <span class="form-hint">Square logo, minimum 112×112px</span>
                                    </div>
                                </div>
                            </div>
                            <div class="preview-card">
                                <div class="preview-label">Schema Preview</div>
                                <div class="schema-preview"><code><?php
                                    $previewSchema = [
                                        '@context' => 'https://schema.org',
                                        '@type' => $settings['seo_schema_org_type'] ?: 'Organization',
                                        'name' => $settings['seo_schema_org_name'] ?: get_site_name(),
                                        'url' => SITE_URL,
                                    ];
                                    if ($settings['seo_schema_org_logo']) {
                                        $previewSchema['logo'] = $settings['seo_schema_org_logo'];
                                    }
                                    echo esc(json_encode($previewSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                                ?></code></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sitemap Tab -->
                <div class="settings-section" data-section="sitemap">
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <div class="settings-card-icon green">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                            </div>
                            <div class="settings-card-title">
                                <h3>XML Sitemap</h3>
                                <p>Help search engines discover your content</p>
                            </div>
                        </div>
                        <div class="settings-card-body">
                            <label class="toggle-row" style="margin-bottom: 1.5rem;">
                                <input type="checkbox" name="seo_sitemap_enabled" value="1" <?= $settings['seo_sitemap_enabled'] ? 'checked' : '' ?>>
                                <span class="toggle-switch"></span>
                                <span class="toggle-label">Enable XML Sitemap</span>
                            </label>
                            
                            <?php if ($settings['seo_sitemap_enabled']): ?>
                            <div class="url-box">
                                <code><?= SITE_URL ?>/sitemap.xml</code>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="navigator.clipboard.writeText('<?= SITE_URL ?>/sitemap.xml')">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                    Copy
                                </button>
                                <a href="<?= SITE_URL ?>/sitemap.xml" target="_blank" class="btn btn-secondary btn-sm">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                                    View
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="settings-card">
                        <div class="settings-card-header">
                            <div class="settings-card-icon blue">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                            </div>
                            <div class="settings-card-title">
                                <h3>Sitemap Content</h3>
                                <p>Select which content types to include</p>
                            </div>
                        </div>
                        <div class="settings-card-body">
                            <div class="form-group">
                                <label class="form-label">Post Types</label>
                                <div class="checkbox-grid">
                                    <?php foreach ($allPostTypes as $slug => $config): ?>
                                    <?php if ($config['public'] ?? true): ?>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="seo_sitemap_post_types[]" value="<?= esc($slug) ?>" <?= in_array($slug, $settings['seo_sitemap_post_types']) ? 'checked' : '' ?>>
                                        <span><?= esc($config['label']) ?></span>
                                    </label>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="form-group" style="margin-top: 1.5rem;">
                                <label class="toggle-row">
                                    <input type="checkbox" name="seo_sitemap_taxonomies" value="1" <?= $settings['seo_sitemap_taxonomies'] ? 'checked' : '' ?>>
                                    <span class="toggle-switch"></span>
                                    <span class="toggle-label">
                                        Include Taxonomy Archives
                                        <small>Category and tag archive pages</small>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Robots.txt Tab -->
                <div class="settings-section" data-section="robots">
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <div class="settings-card-icon pink">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                            </div>
                            <div class="settings-card-title">
                                <h3>Robots.txt</h3>
                                <p>Control how search engines crawl your site</p>
                            </div>
                        </div>
                        <div class="settings-card-body">
                            <div class="url-box" style="margin-bottom: 1.5rem;">
                                <code><?= SITE_URL ?>/robots.txt</code>
                                <a href="<?= SITE_URL ?>/robots.txt" target="_blank" class="btn btn-secondary btn-sm">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                                    View
                                </a>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Custom Robots.txt Content</label>
                                <textarea name="seo_robots_txt" class="form-textarea code" rows="10" placeholder="Leave empty to use default..."><?= esc($settings['seo_robots_txt']) ?></textarea>
                                <span class="form-hint">Leave empty to use the default robots.txt. The default includes your sitemap URL.</span>
                            </div>
                            <div class="preview-card">
                                <div class="preview-label">Default Robots.txt</div>
                                <div class="schema-preview"><code><?= esc(SEO::generateRobotsTxt()) ?></code></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="settings-actions">
                    <button type="submit" class="btn-save">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
// Tab switching
document.querySelectorAll('.settings-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        const targetSection = this.dataset.tab;
        
        document.querySelectorAll('.settings-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.settings-section').forEach(s => s.classList.remove('active'));
        
        this.classList.add('active');
        document.querySelector(`[data-section="${targetSection}"]`).classList.add('active');
    });
});

// Character counters
function updateCharCounter(input, countId, progressId, maxLen, warnAt) {
    const len = input.value.length;
    document.getElementById(countId).textContent = len;
    const progress = document.getElementById(progressId);
    const pct = Math.min(100, (len / maxLen) * 100);
    progress.style.width = pct + '%';
    progress.classList.remove('warning', 'error');
    if (len > maxLen) progress.classList.add('error');
    else if (len >= warnAt) progress.classList.add('warning');
}

const homeTitle = document.getElementById('seo_home_title');
const homeDesc = document.getElementById('seo_home_description');

if (homeTitle) {
    updateCharCounter(homeTitle, 'homeTitleCount', 'homeTitleProgress', 60, 55);
    homeTitle.addEventListener('input', function() {
        updateCharCounter(this, 'homeTitleCount', 'homeTitleProgress', 60, 55);
        document.getElementById('previewTitle').textContent = this.value || '<?= esc(get_site_name()) ?>';
    });
}

if (homeDesc) {
    updateCharCounter(homeDesc, 'homeDescCount', 'homeDescProgress', 160, 150);
    homeDesc.addEventListener('input', function() {
        updateCharCounter(this, 'homeDescCount', 'homeDescProgress', 160, 150);
        document.getElementById('previewDesc').textContent = this.value || 'Your homepage description will appear here...';
    });
}

// Image preview
const ogImageInput = document.getElementById('seo_og_default_image');
if (ogImageInput) {
    ogImageInput.addEventListener('input', function() {
        const preview = document.getElementById('ogImagePreview');
        if (this.value) {
            preview.innerHTML = '<img src="' + this.value + '" alt="" onerror="this.parentElement.classList.remove(\'has-image\'); this.outerHTML=\'<svg width=24 height=24 viewBox=\'0 0 24 24\' fill=none stroke=currentColor stroke-width=2><rect x=3 y=3 width=18 height=18 rx=2 ry=2></rect><circle cx=8.5 cy=8.5 r=1.5></circle><polyline points=\'21 15 16 10 5 21\'></polyline></svg>\'">';
            preview.classList.add('has-image');
        } else {
            preview.innerHTML = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>';
            preview.classList.remove('has-image');
        }
    });
}
</script>

<?php include CMS_ROOT . '/admin/includes/footer.php'; ?>
