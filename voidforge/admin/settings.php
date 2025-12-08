<?php
/**
 * Site Settings - VoidForge CMS
 * Modern, fluid design
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
User::requireRole('admin');

$currentPage = 'settings';
$pageTitle = 'Settings';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    setOption('site_title', trim($_POST['site_title'] ?? ''));
    setOption('site_description', trim($_POST['site_description'] ?? ''));
    setOption('homepage_id', (int)($_POST['homepage_id'] ?? 0));
    setOption('posts_per_page', (int)($_POST['posts_per_page'] ?? 10));
    setOption('excerpt_length', (int)($_POST['excerpt_length'] ?? 55));
    
    // Handle date format - check for custom
    $dateFormat = $_POST['date_format'] ?? 'M j, Y';
    if ($dateFormat === 'custom' && !empty($_POST['custom_date_format'])) {
        $dateFormat = trim($_POST['custom_date_format']);
    }
    setOption('date_format', $dateFormat);
    
    // Handle time format - check for custom
    $timeFormat = $_POST['time_format'] ?? 'H:i';
    if ($timeFormat === 'custom' && !empty($_POST['custom_time_format'])) {
        $timeFormat = trim($_POST['custom_time_format']);
    }
    setOption('time_format', $timeFormat);
    
    setFlash('success', 'Settings saved successfully.');
    redirect(ADMIN_URL . '/settings.php');
}

$siteTitle = getOption('site_title', '');
$siteDescription = getOption('site_description', '');
$homepageId = getOption('homepage_id', 0);
$postsPerPage = getOption('posts_per_page', 10);
$excerptLength = getOption('excerpt_length', 55);
$dateFormat = getOption('date_format', 'M j, Y');
$timeFormat = getOption('time_format', 'H:i');

// Get all pages for homepage dropdown
$allPages = Post::query([
    'post_type' => 'page',
    'status' => 'published',
    'limit' => 100,
    'orderby' => 'title',
    'order' => 'ASC'
]);

include ADMIN_PATH . '/includes/header.php';
?>

<style>
/* Modern Settings Page Styles */
.settings-page {
    max-width: 900px;
    margin: 0 auto;
}

.settings-header {
    margin-bottom: 2rem;
}

.settings-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 0.5rem 0;
}

.settings-header p {
    color: #64748b;
    margin: 0;
}

/* Tab Navigation */
.settings-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 0;
}

.settings-tab {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1.25rem;
    background: none;
    border: none;
    font-size: 0.9375rem;
    font-weight: 500;
    color: #64748b;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
    transition: all 0.2s ease;
}

.settings-tab:hover {
    color: #1e293b;
}

.settings-tab.active {
    color: #6366f1;
    border-bottom-color: #6366f1;
}

.settings-tab svg {
    opacity: 0.7;
}

.settings-tab.active svg {
    opacity: 1;
}

/* Settings Sections */
.settings-section {
    display: none;
    animation: fadeIn 0.3s ease;
}

.settings-section.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Settings Cards */
.settings-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    margin-bottom: 1.5rem;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}

.settings-card-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem 1.5rem;
    background: linear-gradient(135deg, #f8fafc 0%, #fff 100%);
    border-bottom: 1px solid #e2e8f0;
}

.settings-card-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.settings-card-icon.purple {
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(139, 92, 246, 0.15) 100%);
    color: #6366f1;
}

.settings-card-icon.blue {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(37, 99, 235, 0.15) 100%);
    color: #3b82f6;
}

.settings-card-icon.green {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(5, 150, 105, 0.15) 100%);
    color: #10b981;
}

.settings-card-icon.orange {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.15) 0%, rgba(217, 119, 6, 0.15) 100%);
    color: #f59e0b;
}

.settings-card-title {
    flex: 1;
}

.settings-card-title h3 {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 0.25rem 0;
}

.settings-card-title p {
    font-size: 0.8125rem;
    color: #64748b;
    margin: 0;
}

.settings-card-body {
    padding: 1.5rem;
}

/* Form Elements */
.form-grid {
    display: grid;
    gap: 1.5rem;
}

.form-grid-2 {
    grid-template-columns: repeat(2, 1fr);
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
}

.form-label-hint {
    font-weight: 400;
    color: #9ca3af;
    margin-left: 0.5rem;
}

.form-input,
.form-select,
.form-textarea {
    padding: 0.75rem 1rem;
    font-size: 0.9375rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    background: #fff;
    color: #1e293b;
    transition: all 0.2s ease;
    font-family: inherit;
}

.form-input:hover,
.form-select:hover,
.form-textarea:hover {
    border-color: #cbd5e1;
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

.form-textarea {
    resize: vertical;
    min-height: 100px;
}

.form-hint {
    font-size: 0.8125rem;
    color: #9ca3af;
}

/* Preview Card */
.preview-card {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 12px;
    padding: 1.25rem;
    margin-top: 0.5rem;
}

.preview-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.5rem;
}

.preview-value {
    font-size: 1rem;
    color: #1e293b;
    font-weight: 500;
}

/* Custom Format Input */
.custom-format-input input {
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.875rem;
}

/* Format Help Box */
.format-help-box {
    margin-top: 1.5rem;
    padding: 1rem 1.25rem;
    background: linear-gradient(135deg, #eff6ff 0%, #f0f9ff 100%);
    border: 1px solid #bfdbfe;
    border-radius: 12px;
}

.format-help-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #1e40af;
}

.format-help-header svg {
    flex-shrink: 0;
}

.format-help-link {
    margin-left: auto;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    color: #3b82f6;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.75rem;
}

.format-help-link:hover {
    text-decoration: underline;
}

.format-help-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.625rem;
}

.format-group {
    font-size: 0.75rem;
    color: #475569;
    line-height: 1.6;
}

.format-group strong {
    color: #1e293b;
    font-weight: 600;
    display: inline-block;
    min-width: 50px;
}

.format-group code {
    background: #fff;
    padding: 0.125rem 0.375rem;
    border-radius: 4px;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.6875rem;
    color: #6366f1;
    border: 1px solid #e2e8f0;
}

/* Quick Links */
.quick-links {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-top: 1rem;
}

.quick-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    text-decoration: none;
    color: #1e293b;
    transition: all 0.2s ease;
}

.quick-link:hover {
    border-color: #6366f1;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.15);
    transform: translateY(-2px);
}

.quick-link-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.quick-link-text {
    flex: 1;
}

.quick-link-text span {
    display: block;
    font-size: 0.9375rem;
    font-weight: 600;
}

.quick-link-text small {
    font-size: 0.75rem;
    color: #94a3b8;
}

/* Save Button */
.settings-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #e2e8f0;
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

/* Info Cards - Compact Design */
.info-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.75rem;
}

.info-card {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 1rem;
    text-align: center;
}

.info-card-label {
    font-size: 0.6875rem;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.25rem;
}

.info-card-value {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #1e293b;
    word-break: break-word;
}

.info-card-value code {
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.75rem;
    background: #e2e8f0;
    padding: 0.15rem 0.35rem;
    border-radius: 4px;
    display: inline-block;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
}

.info-card-value .status-ok {
    color: #10b981;
}

.info-card-value .status-warn {
    color: #f59e0b;
}

/* System Info Table Style */
.sys-info-table {
    width: 100%;
    border-collapse: collapse;
}

.sys-info-table tr {
    border-bottom: 1px solid #f1f5f9;
}

.sys-info-table tr:last-child {
    border-bottom: none;
}

.sys-info-table td {
    padding: 0.625rem 0;
}

.sys-info-table .sys-label {
    font-size: 0.8125rem;
    font-weight: 500;
    color: #64748b;
    width: 40%;
}

.sys-info-table .sys-value {
    font-size: 0.875rem;
    font-weight: 600;
    color: #1e293b;
}

.sys-info-table .sys-value code {
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.75rem;
    background: #f1f5f9;
    padding: 0.2rem 0.4rem;
    border-radius: 4px;
}

/* Responsive */
@media (max-width: 768px) {
    .form-grid-2 {
        grid-template-columns: 1fr;
    }
    
    .quick-links {
        grid-template-columns: 1fr;
    }
    
    .info-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .settings-tabs {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
}
</style>

<script>
function switchTab(tabId, evt) {
    // Get the clicked button
    var clickedTab = null;
    if (evt) {
        clickedTab = evt.currentTarget || evt.target;
        // Make sure we have the button, not a child element
        while (clickedTab && !clickedTab.classList.contains('settings-tab')) {
            clickedTab = clickedTab.parentElement;
        }
    }
    
    // Update tabs - remove active from all
    var tabs = document.querySelectorAll('.settings-tab');
    for (var i = 0; i < tabs.length; i++) {
        tabs[i].classList.remove('active');
    }
    
    // Add active to clicked tab
    if (clickedTab) {
        clickedTab.classList.add('active');
    }
    
    // Update sections - hide all
    var sections = document.querySelectorAll('.settings-section');
    for (var j = 0; j < sections.length; j++) {
        sections[j].classList.remove('active');
    }
    
    // Show target section
    var targetSection = document.getElementById('section-' + tabId);
    if (targetSection) {
        targetSection.classList.add('active');
    }
    
    return false;
}
</script>

<div class="settings-page">
    <div class="settings-header">
        <h1>Settings</h1>
        <p>Configure your site settings and preferences</p>
    </div>
    
    <!-- Tab Navigation -->
    <div class="settings-tabs">
        <button type="button" class="settings-tab active" onclick="switchTab('general', event)">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="3"></circle>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
            </svg>
            General
        </button>
        <button type="button" class="settings-tab" onclick="switchTab('reading', event)">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
            </svg>
            Reading
        </button>
        <button type="button" class="settings-tab" onclick="switchTab('system', event)">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                <line x1="8" y1="21" x2="16" y2="21"></line>
                <line x1="12" y1="17" x2="12" y2="21"></line>
            </svg>
            System Info
        </button>
    </div>
    
    <form method="post">
        <?= csrfField() ?>
        
        <!-- General Settings -->
        <div id="section-general" class="settings-section active">
            <div class="settings-card">
                <div class="settings-card-header">
                    <div class="settings-card-icon purple">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="2" y1="12" x2="22" y2="12"></line>
                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                        </svg>
                    </div>
                    <div class="settings-card-title">
                        <h3>Site Identity</h3>
                        <p>Basic information about your website</p>
                    </div>
                </div>
                <div class="settings-card-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Site Title</label>
                            <input type="text" name="site_title" class="form-input" 
                                   value="<?= esc($siteTitle) ?>" placeholder="My Awesome Site">
                            <span class="form-hint">Displayed in browser tabs and search results</span>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Site Description <span class="form-label-hint">(Tagline)</span></label>
                            <textarea name="site_description" class="form-textarea" 
                                      placeholder="A short description of your site..."><?= esc($siteDescription) ?></textarea>
                            <span class="form-hint">A brief explanation of what your site is about</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="settings-card">
                <div class="settings-card-header">
                    <div class="settings-card-icon blue">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                        </svg>
                    </div>
                    <div class="settings-card-title">
                        <h3>Quick Actions</h3>
                        <p>Frequently used settings pages</p>
                    </div>
                </div>
                <div class="settings-card-body">
                    <div class="quick-links">
                        <a href="<?= ADMIN_URL ?>/post-types.php" class="quick-link">
                            <div class="quick-link-icon" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(139, 92, 246, 0.15)); color: #6366f1;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="12" y1="18" x2="12" y2="12"></line>
                                    <line x1="9" y1="15" x2="15" y2="15"></line>
                                </svg>
                            </div>
                            <div class="quick-link-text">
                                <span>Post Types</span>
                                <small>Create custom content types</small>
                            </div>
                        </a>
                        <a href="<?= ADMIN_URL ?>/customize.php" class="quick-link">
                            <div class="quick-link-icon" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(5, 150, 105, 0.15)); color: #10b981;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 19l7-7 3 3-7 7-3-3z"></path>
                                    <path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"></path>
                                    <path d="M2 2l7.586 7.586"></path>
                                    <circle cx="11" cy="11" r="2"></circle>
                                </svg>
                            </div>
                            <div class="quick-link-text">
                                <span>Customize</span>
                                <small>Theme & CSS editor</small>
                            </div>
                        </a>
                        <a href="<?= ADMIN_URL ?>/thumbnails.php" class="quick-link">
                            <div class="quick-link-icon" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.15), rgba(217, 119, 6, 0.15)); color: #f59e0b;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="7" height="7"></rect>
                                    <rect x="14" y="3" width="7" height="7"></rect>
                                    <rect x="14" y="14" width="7" height="7"></rect>
                                    <rect x="3" y="14" width="7" height="7"></rect>
                                </svg>
                            </div>
                            <div class="quick-link-text">
                                <span>Thumbnails</span>
                                <small>Manage image sizes</small>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Reading Settings -->
        <div id="section-reading" class="settings-section">
            <div class="settings-card">
                <div class="settings-card-header">
                    <div class="settings-card-icon purple">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                    </div>
                    <div class="settings-card-title">
                        <h3>Homepage</h3>
                        <p>Select which page displays as your homepage</p>
                    </div>
                </div>
                <div class="settings-card-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Homepage</label>
                            <select name="homepage_id" class="form-select">
                                <option value="0">— None (show demo page) —</option>
                                <?php foreach ($allPages as $page): ?>
                                <option value="<?= $page['id'] ?>" <?= $homepageId == $page['id'] ? 'selected' : '' ?>>
                                    <?= esc($page['title']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="form-hint">Select a page to display on your homepage, or choose "None" to show the demo landing page</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="settings-card">
                <div class="settings-card-header">
                    <div class="settings-card-icon green">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="8" y1="6" x2="21" y2="6"></line>
                            <line x1="8" y1="12" x2="21" y2="12"></line>
                            <line x1="8" y1="18" x2="21" y2="18"></line>
                            <line x1="3" y1="6" x2="3.01" y2="6"></line>
                            <line x1="3" y1="12" x2="3.01" y2="12"></line>
                            <line x1="3" y1="18" x2="3.01" y2="18"></line>
                        </svg>
                    </div>
                    <div class="settings-card-title">
                        <h3>Content Display</h3>
                        <p>How your content is displayed to visitors</p>
                    </div>
                </div>
                <div class="settings-card-body">
                    <div class="form-grid form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Posts Per Page</label>
                            <input type="number" name="posts_per_page" class="form-input" 
                                   value="<?= $postsPerPage ?>" min="1" max="100">
                            <span class="form-hint">Number of posts shown on archive pages</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Excerpt Length</label>
                            <input type="number" name="excerpt_length" class="form-input" 
                                   value="<?= $excerptLength ?>" min="10" max="500">
                            <span class="form-hint">Number of words in auto-generated excerpts</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="settings-card">
                <div class="settings-card-header">
                    <div class="settings-card-icon orange">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <div class="settings-card-title">
                        <h3>Date & Time Format</h3>
                        <p>How dates and times are displayed</p>
                    </div>
                </div>
                <div class="settings-card-body">
                    <div class="form-grid form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Date Format</label>
                            <select name="date_format" class="form-select" id="dateFormatSelect" onchange="toggleCustomDate(this)">
                                <option value="M j, Y" <?= $dateFormat === 'M j, Y' ? 'selected' : '' ?>>
                                    <?= date('M j, Y') ?> — M j, Y
                                </option>
                                <option value="F j, Y" <?= $dateFormat === 'F j, Y' ? 'selected' : '' ?>>
                                    <?= date('F j, Y') ?> — F j, Y
                                </option>
                                <option value="m/d/Y" <?= $dateFormat === 'm/d/Y' ? 'selected' : '' ?>>
                                    <?= date('m/d/Y') ?> — m/d/Y
                                </option>
                                <option value="d/m/Y" <?= $dateFormat === 'd/m/Y' ? 'selected' : '' ?>>
                                    <?= date('d/m/Y') ?> — d/m/Y
                                </option>
                                <option value="Y-m-d" <?= $dateFormat === 'Y-m-d' ? 'selected' : '' ?>>
                                    <?= date('Y-m-d') ?> — Y-m-d
                                </option>
                                <option value="d.m.Y" <?= $dateFormat === 'd.m.Y' ? 'selected' : '' ?>>
                                    <?= date('d.m.Y') ?> — d.m.Y
                                </option>
                                <option value="j F Y" <?= $dateFormat === 'j F Y' ? 'selected' : '' ?>>
                                    <?= date('j F Y') ?> — j F Y
                                </option>
                                <option value="l, F j, Y" <?= $dateFormat === 'l, F j, Y' ? 'selected' : '' ?>>
                                    <?= date('l, F j, Y') ?> — l, F j, Y
                                </option>
                                <?php 
                                $standardFormats = ['M j, Y', 'F j, Y', 'm/d/Y', 'd/m/Y', 'Y-m-d', 'd.m.Y', 'j F Y', 'l, F j, Y'];
                                if (!in_array($dateFormat, $standardFormats)): 
                                ?>
                                <option value="<?= esc($dateFormat) ?>" selected>
                                    <?= date($dateFormat) ?> — <?= esc($dateFormat) ?> (Custom)
                                </option>
                                <?php endif; ?>
                                <option value="custom" <?= !in_array($dateFormat, $standardFormats) ? '' : '' ?>>
                                    ✎ Custom format...
                                </option>
                            </select>
                            <div id="customDateInput" class="custom-format-input" style="display: none; margin-top: 0.5rem;">
                                <input type="text" name="custom_date_format" class="form-input" 
                                       placeholder="e.g. Y-m-d or F j, Y" 
                                       value="<?= !in_array($dateFormat, $standardFormats) ? esc($dateFormat) : '' ?>"
                                       oninput="updateDatePreview(this.value)">
                            </div>
                            <div class="preview-card">
                                <div class="preview-label">Preview</div>
                                <div class="preview-value" id="datePreview"><?= date($dateFormat) ?></div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Time Format</label>
                            <select name="time_format" class="form-select" id="timeFormatSelect" onchange="toggleCustomTime(this)">
                                <option value="H:i" <?= $timeFormat === 'H:i' ? 'selected' : '' ?>>
                                    <?= date('H:i') ?> — H:i (24-hour)
                                </option>
                                <option value="g:i A" <?= $timeFormat === 'g:i A' ? 'selected' : '' ?>>
                                    <?= date('g:i A') ?> — g:i A (12-hour)
                                </option>
                                <option value="g:i a" <?= $timeFormat === 'g:i a' ? 'selected' : '' ?>>
                                    <?= date('g:i a') ?> — g:i a (12-hour lowercase)
                                </option>
                                <option value="H:i:s" <?= $timeFormat === 'H:i:s' ? 'selected' : '' ?>>
                                    <?= date('H:i:s') ?> — H:i:s (with seconds)
                                </option>
                                <?php 
                                $standardTimeFormats = ['H:i', 'g:i A', 'g:i a', 'H:i:s'];
                                if (!in_array($timeFormat, $standardTimeFormats)): 
                                ?>
                                <option value="<?= esc($timeFormat) ?>" selected>
                                    <?= date($timeFormat) ?> — <?= esc($timeFormat) ?> (Custom)
                                </option>
                                <?php endif; ?>
                                <option value="custom">
                                    ✎ Custom format...
                                </option>
                            </select>
                            <div id="customTimeInput" class="custom-format-input" style="display: none; margin-top: 0.5rem;">
                                <input type="text" name="custom_time_format" class="form-input" 
                                       placeholder="e.g. H:i:s or g:i A" 
                                       value="<?= !in_array($timeFormat, $standardTimeFormats) ? esc($timeFormat) : '' ?>"
                                       oninput="updateTimePreview(this.value)">
                            </div>
                            <div class="preview-card">
                                <div class="preview-label">Preview</div>
                                <div class="preview-value" id="timePreview"><?= date($timeFormat) ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="format-help-box">
                        <div class="format-help-header">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="16" x2="12" y2="12"></line>
                                <line x1="12" y1="8" x2="12.01" y2="8"></line>
                            </svg>
                            <span>Format Reference</span>
                            <a href="https://www.php.net/manual/en/datetime.format.php" target="_blank" rel="noopener" class="format-help-link">
                                Full documentation
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                    <polyline points="15 3 21 3 21 9"></polyline>
                                    <line x1="10" y1="14" x2="21" y2="3"></line>
                                </svg>
                            </a>
                        </div>
                        <div class="format-help-grid">
                            <div class="format-group">
                                <strong>Day</strong>
                                <code>d</code> 01-31 &nbsp;
                                <code>j</code> 1-31 &nbsp;
                                <code>D</code> Mon &nbsp;
                                <code>l</code> Monday
                            </div>
                            <div class="format-group">
                                <strong>Month</strong>
                                <code>m</code> 01-12 &nbsp;
                                <code>n</code> 1-12 &nbsp;
                                <code>M</code> Jan &nbsp;
                                <code>F</code> January
                            </div>
                            <div class="format-group">
                                <strong>Year</strong>
                                <code>Y</code> 2025 &nbsp;
                                <code>y</code> 25
                            </div>
                            <div class="format-group">
                                <strong>Time</strong>
                                <code>H</code> 00-23 &nbsp;
                                <code>h</code> 01-12 &nbsp;
                                <code>i</code> 00-59 &nbsp;
                                <code>s</code> 00-59 &nbsp;
                                <code>A</code> AM/PM
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- System Info -->
        <div id="section-system" class="settings-section">
            <div class="settings-card">
                <div class="settings-card-header">
                    <div class="settings-card-icon blue">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                            <line x1="8" y1="21" x2="16" y2="21"></line>
                            <line x1="12" y1="17" x2="12" y2="21"></line>
                        </svg>
                    </div>
                    <div class="settings-card-title">
                        <h3>System Information</h3>
                        <p>Technical details about your installation</p>
                    </div>
                </div>
                <div class="settings-card-body">
                    <div class="info-grid">
                        <div class="info-card">
                            <div class="info-card-label">CMS</div>
                            <div class="info-card-value"><?= CMS_VERSION ?></div>
                        </div>
                        <div class="info-card">
                            <div class="info-card-label">PHP</div>
                            <div class="info-card-value"><?= PHP_VERSION ?></div>
                        </div>
                        <div class="info-card">
                            <div class="info-card-label">MySQL</div>
                            <div class="info-card-value"><?= Database::queryValue("SELECT VERSION()") ?></div>
                        </div>
                        <div class="info-card">
                            <div class="info-card-label">Upload Limit</div>
                            <div class="info-card-value"><?= ini_get('upload_max_filesize') ?></div>
                        </div>
                    </div>
                    
                    <table class="sys-info-table" style="margin-top: 1.25rem;">
                        <tr>
                            <td class="sys-label">Server Software</td>
                            <td class="sys-value"><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></td>
                        </tr>
                        <tr>
                            <td class="sys-label">Site URL</td>
                            <td class="sys-value"><code><?= SITE_URL ?></code></td>
                        </tr>
                        <tr>
                            <td class="sys-label">Uploads Directory</td>
                            <td class="sys-value"><code><?= str_replace(CMS_ROOT, '', UPLOADS_PATH) ?></code></td>
                        </tr>
                        <tr>
                            <td class="sys-label">GD Library</td>
                            <td class="sys-value"><span class="<?= extension_loaded('gd') ? 'status-ok' : 'status-warn' ?>"><?= extension_loaded('gd') ? '✓ Enabled' : '✗ Disabled' ?></span></td>
                        </tr>
                        <tr>
                            <td class="sys-label">cURL Extension</td>
                            <td class="sys-value"><span class="<?= extension_loaded('curl') ? 'status-ok' : 'status-warn' ?>"><?= extension_loaded('curl') ? '✓ Enabled' : '✗ Disabled' ?></span></td>
                        </tr>
                        <tr>
                            <td class="sys-label">Memory Limit</td>
                            <td class="sys-value"><?= ini_get('memory_limit') ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="settings-actions">
            <button type="submit" class="btn-save">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                Save Settings
            </button>
        </div>
    </form>
</div>

<script>
// Date format previews (pre-calculated from PHP)
var dateFormats = {
    'M j, Y': '<?= date('M j, Y') ?>',
    'F j, Y': '<?= date('F j, Y') ?>',
    'm/d/Y': '<?= date('m/d/Y') ?>',
    'd/m/Y': '<?= date('d/m/Y') ?>',
    'Y-m-d': '<?= date('Y-m-d') ?>',
    'd.m.Y': '<?= date('d.m.Y') ?>',
    'j F Y': '<?= date('j F Y') ?>',
    'l, F j, Y': '<?= date('l, F j, Y') ?>'
};

var timeFormats = {
    'H:i': '<?= date('H:i') ?>',
    'g:i A': '<?= date('g:i A') ?>',
    'g:i a': '<?= date('g:i a') ?>',
    'H:i:s': '<?= date('H:i:s') ?>'
};

// Toggle custom date input
function toggleCustomDate(select) {
    var customInput = document.getElementById('customDateInput');
    var preview = document.getElementById('datePreview');
    
    if (select.value === 'custom') {
        customInput.style.display = 'block';
        var customField = customInput.querySelector('input');
        if (customField.value) {
            preview.textContent = 'Custom: ' + customField.value;
        }
    } else {
        customInput.style.display = 'none';
        preview.textContent = dateFormats[select.value] || select.value;
    }
}

// Toggle custom time input
function toggleCustomTime(select) {
    var customInput = document.getElementById('customTimeInput');
    var preview = document.getElementById('timePreview');
    
    if (select.value === 'custom') {
        customInput.style.display = 'block';
        var customField = customInput.querySelector('input');
        if (customField.value) {
            preview.textContent = 'Custom: ' + customField.value;
        }
    } else {
        customInput.style.display = 'none';
        preview.textContent = timeFormats[select.value] || select.value;
    }
}

// Update date preview for custom input
function updateDatePreview(format) {
    var preview = document.getElementById('datePreview');
    if (format) {
        // Show the format pattern as preview (actual date will be processed server-side)
        preview.textContent = 'Format: ' + format;
    } else {
        preview.textContent = 'Enter a format...';
    }
}

// Update time preview for custom input
function updateTimePreview(format) {
    var preview = document.getElementById('timePreview');
    if (format) {
        preview.textContent = 'Format: ' + format;
    } else {
        preview.textContent = 'Enter a format...';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    var dateSelect = document.getElementById('dateFormatSelect');
    var timeSelect = document.getElementById('timeFormatSelect');
    
    // Check if custom format is already selected
    if (dateSelect && dateSelect.value === 'custom') {
        document.getElementById('customDateInput').style.display = 'block';
    }
    if (timeSelect && timeSelect.value === 'custom') {
        document.getElementById('customTimeInput').style.display = 'block';
    }
});
</script>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
