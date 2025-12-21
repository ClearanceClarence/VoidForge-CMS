<?php
/**
 * Frontend Customizer - Site Identity, Logo, Favicon & Custom CSS
 * VoidForge CMS
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

// Handle AJAX save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    if (!verifyCsrf()) {
        echo json_encode(['success' => false, 'error' => 'Invalid token']);
        exit;
    }
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'save_css':
            setOption('custom_frontend_css', $_POST['custom_css'] ?? '');
            echo json_encode(['success' => true]);
            break;
            
        case 'save_identity':
            // Save site identity settings
            if (isset($_POST['site_logo'])) {
                setOption('site_logo', $_POST['site_logo']);
            }
            if (isset($_POST['site_favicon'])) {
                setOption('site_favicon', $_POST['site_favicon']);
            }
            if (isset($_POST['site_logo_width'])) {
                setOption('site_logo_width', (int)$_POST['site_logo_width']);
            }
            if (isset($_POST['site_logo_height'])) {
                setOption('site_logo_height', (int)$_POST['site_logo_height']);
            }
            echo json_encode(['success' => true]);
            break;
            
        case 'remove_logo':
            setOption('site_logo', '');
            echo json_encode(['success' => true]);
            break;
            
        case 'remove_favicon':
            setOption('site_favicon', '');
            echo json_encode(['success' => true]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
    }
    exit;
}

$customCss = getOption('custom_frontend_css', '');
$siteTitle = getOption('site_title', 'My Site');
$siteLogo = getOption('site_logo', '');
$siteFavicon = getOption('site_favicon', '');
$siteLogoWidth = getOption('site_logo_width', 0);
$siteLogoHeight = getOption('site_logo_height', 0);

// Get all media for the media library modal
$allMedia = Media::query(['orderby' => 'created_at', 'order' => 'DESC', 'limit' => 100]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customize - <?= CMS_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/eclipse.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            height: 100vh;
            overflow: hidden;
        }
        
        .customizer { display: flex; height: 100vh; }
        
        .editor-panel {
            width: 420px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(0,0,0,0.1);
        }
        
        .panel-header {
            padding: 1rem 1.25rem;
            background: rgba(0,0,0,0.05);
            border-bottom: 1px solid rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .panel-brand { display: flex; align-items: center; gap: 0.75rem; }
        .panel-brand svg { color: #818cf8; }
        .panel-brand span { font-weight: 700; font-size: 1.125rem; letter-spacing: -0.025em; }
        
        .panel-close {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.875rem;
            background: rgba(0,0,0,0.05);
            border: none;
            border-radius: 8px;
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .panel-close:hover { background: rgba(0,0,0,0.1); color: #1e293b; }
        
        .panel-tabs {
            display: flex;
            background: rgba(0,0,0,0.03);
            border-bottom: 1px solid rgba(0,0,0,0.06);
        }
        
        .panel-tab {
            flex: 1;
            padding: 0.875rem 1rem;
            background: none;
            border: none;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.15s ease;
            position: relative;
        }
        .panel-tab:hover { color: #475569; }
        .panel-tab.active { color: #6366f1; background: #fff; }
        .panel-tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #6366f1, #818cf8);
        }
        
        .tab-content { display: none; flex: 1; flex-direction: column; overflow: hidden; }
        .tab-content.active { display: flex; }
        
        .scrollable-content { flex: 1; overflow-y: auto; padding: 1.25rem; }
        
        .upload-section { margin-bottom: 1.5rem; }
        .upload-section:last-child { margin-bottom: 0; }
        
        .upload-label { display: block; font-size: 0.875rem; font-weight: 600; color: #1e293b; margin-bottom: 0.5rem; }
        .upload-hint { font-size: 0.75rem; color: #64748b; margin-bottom: 0.75rem; }
        
        .upload-area {
            border: 2px dashed rgba(99, 102, 241, 0.3);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            background: rgba(99, 102, 241, 0.02);
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .upload-area:hover { border-color: rgba(99, 102, 241, 0.5); background: rgba(99, 102, 241, 0.05); }
        .upload-area.has-image { padding: 1rem; border-style: solid; border-color: rgba(99, 102, 241, 0.2); }
        
        .upload-placeholder { color: #64748b; }
        .upload-placeholder svg { margin-bottom: 0.5rem; color: #a5b4fc; }
        .upload-placeholder p { font-size: 0.8125rem; margin-bottom: 0.25rem; }
        .upload-placeholder small { font-size: 0.75rem; color: #94a3b8; }
        
        .upload-preview { position: relative; display: inline-block; }
        .upload-preview img { max-width: 100%; max-height: 120px; object-fit: contain; border-radius: 8px; }
        
        .upload-preview-actions { position: absolute; top: -8px; right: -8px; display: flex; gap: 4px; }
        
        .upload-preview-btn {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .upload-preview-btn.change { background: #6366f1; color: #fff; }
        .upload-preview-btn.change:hover { background: #4f46e5; }
        .upload-preview-btn.remove { background: #ef4444; color: #fff; }
        .upload-preview-btn.remove:hover { background: #dc2626; }
        
        .dimension-inputs { display: flex; gap: 0.75rem; margin-top: 0.75rem; }
        .dimension-group { flex: 1; }
        .dimension-group label { display: block; font-size: 0.75rem; color: #64748b; margin-bottom: 0.25rem; }
        .dimension-group input {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 6px;
            font-size: 0.8125rem;
            background: #fff;
            transition: all 0.15s ease;
        }
        .dimension-group input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }
        .dimension-hint { font-size: 0.6875rem; color: #94a3b8; margin-top: 0.5rem; }
        
        .editor-container { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        
        .editor-header {
            padding: 0.875rem 1.25rem;
            background: rgba(0,0,0,0.03);
            border-bottom: 1px solid rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .editor-title { display: flex; align-items: center; gap: 0.625rem; font-size: 0.875rem; font-weight: 600; color: #1e293b; }
        .editor-title svg { color: #818cf8; }
        .editor-hint { font-size: 0.75rem; color: #64748b; }
        .editor-wrap { flex: 1; overflow: hidden; }
        
        .CodeMirror { height: 100%; font-family: 'JetBrains Mono', 'Monaco', monospace; font-size: 13px; line-height: 1.6; }
        .CodeMirror-gutters { background: #f1f5f9; border-right: 1px solid rgba(0,0,0,0.06); }
        .CodeMirror-linenumber { color: #475569; }
        
        .panel-footer {
            padding: 1rem 1.25rem;
            background: rgba(0,0,0,0.05);
            border-top: 1px solid rgba(0,0,0,0.1);
            display: flex;
            gap: 0.75rem;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .btn-primary {
            flex: 1;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: #fff;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(99, 102, 241, 0.5); }
        .btn-primary:disabled { opacity: 0.6; transform: none; cursor: not-allowed; }
        .btn-secondary { background: rgba(0,0,0,0.05); color: #64748b; border: 1px solid rgba(0,0,0,0.1); }
        .btn-secondary:hover { background: rgba(0,0,0,0.1); color: #1e293b; }
        
        .preview-panel { flex: 1; display: flex; flex-direction: column; background: #ffffff; }
        
        .preview-toolbar {
            padding: 0.75rem 1rem;
            background: #f8fafc;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .device-switcher { display: flex; gap: 0.25rem; background: rgba(0,0,0,0.03); padding: 0.25rem; border-radius: 8px; }
        .device-btn {
            padding: 0.5rem 0.75rem;
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .device-btn:hover { color: #475569; }
        .device-btn.active { background: rgba(99, 102, 241, 0.1); color: #6366f1; }
        
        .preview-url {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(0,0,0,0.03);
            border-radius: 8px;
            font-size: 0.8125rem;
            color: #64748b;
            max-width: 400px;
        }
        .preview-url svg { flex-shrink: 0; }
        .preview-url span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        
        .preview-actions { display: flex; gap: 0.5rem; }
        .preview-action {
            padding: 0.5rem;
            background: rgba(0,0,0,0.03);
            border: none;
            border-radius: 6px;
            color: #64748b;
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none;
        }
        .preview-action:hover { background: rgba(0,0,0,0.1); color: #1e293b; }
        
        .preview-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 1.5rem;
            overflow: auto;
            background: radial-gradient(circle at 50% 50%, rgba(99, 102, 241, 0.03) 0%, transparent 50%), linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        }
        
        .preview-frame-wrapper {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            height: 100%;
        }
        .preview-frame-wrapper.desktop { width: 100%; max-width: 100%; }
        .preview-frame-wrapper.tablet { width: 768px; }
        .preview-frame-wrapper.mobile { width: 375px; }
        .preview-frame { width: 100%; height: 100%; border: none; }
        
        .toast {
            position: fixed;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: #1e293b;
            color: #fff;
            padding: 0.875rem 1.5rem;
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 500;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            opacity: 0;
            transition: all 0.3s ease;
            z-index: 1000;
        }
        .toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }
        .toast.success { border-left: 4px solid #22c55e; }
        .toast.error { border-left: 4px solid #ef4444; }
        .toast.info { border-left: 4px solid #6366f1; }
        
        .media-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
            z-index: 10000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .media-modal-overlay.active { display: flex; }
        
        .media-modal {
            background: #fff;
            border-radius: 16px;
            width: 100%;
            max-width: 900px;
            max-height: 80vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0,0,0,0.25);
        }
        
        .media-modal-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .media-modal-title { font-size: 1.125rem; font-weight: 600; color: #1e293b; }
        .media-modal-close {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: none;
            background: rgba(0,0,0,0.05);
            color: #64748b;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
        }
        .media-modal-close:hover { background: rgba(0,0,0,0.1); color: #1e293b; }
        
        .media-modal-body { flex: 1; overflow-y: auto; padding: 1.5rem; }
        
        .media-tabs { display: flex; gap: 0.5rem; margin-bottom: 1.25rem; }
        .media-tab {
            padding: 0.5rem 1rem;
            background: rgba(0,0,0,0.05);
            border: none;
            border-radius: 6px;
            font-size: 0.8125rem;
            font-weight: 500;
            color: #64748b;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .media-tab:hover { background: rgba(0,0,0,0.08); }
        .media-tab.active { background: #6366f1; color: #fff; }
        
        .media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 1rem; }
        
        .media-item {
            aspect-ratio: 1;
            border-radius: 10px;
            overflow: hidden;
            cursor: pointer;
            position: relative;
            border: 3px solid transparent;
            transition: all 0.15s ease;
            background: #f1f5f9;
        }
        .media-item:hover { border-color: rgba(99, 102, 241, 0.5); }
        .media-item.selected { border-color: #6366f1; }
        .media-item img { width: 100%; height: 100%; object-fit: cover; }
        
        .media-modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .media-modal-hint { font-size: 0.8125rem; color: #64748b; }
        .media-modal-actions { display: flex; gap: 0.75rem; }
        
        .upload-drop-zone {
            border: 2px dashed rgba(99, 102, 241, 0.3);
            border-radius: 12px;
            padding: 3rem;
            text-align: center;
            background: rgba(99, 102, 241, 0.02);
            transition: all 0.2s ease;
        }
        .upload-drop-zone.dragover { border-color: #6366f1; background: rgba(99, 102, 241, 0.1); }
        .upload-drop-zone svg { color: #a5b4fc; margin-bottom: 1rem; }
        .upload-drop-zone p { color: #64748b; margin-bottom: 0.5rem; }
        .upload-drop-zone small { color: #94a3b8; font-size: 0.75rem; }
        .upload-drop-zone input { display: none; }
        
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="customizer">
        <div class="editor-panel">
            <div class="panel-header">
                <div class="panel-brand">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                        <polyline points="2 17 12 22 22 17"></polyline>
                        <polyline points="2 12 12 17 22 12"></polyline>
                    </svg>
                    <span>Customize</span>
                </div>
                <a href="<?= ADMIN_URL ?>/" class="panel-close">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                    Close
                </a>
            </div>
            
            <div class="panel-tabs">
                <button class="panel-tab active" data-tab="identity">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                        <polyline points="21 15 16 10 5 21"></polyline>
                    </svg>
                    Site Identity
                </button>
                <button class="panel-tab" data-tab="css">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="16 18 22 12 16 6"></polyline>
                        <polyline points="8 6 2 12 8 18"></polyline>
                    </svg>
                    Custom CSS
                </button>
            </div>
            
            <!-- Site Identity Tab -->
            <div class="tab-content active" data-tab="identity">
                <div class="scrollable-content">
                    <div class="upload-section">
                        <label class="upload-label">Site Logo</label>
                        <p class="upload-hint">Recommended: SVG for best quality at any size. Also supports PNG, JPG, GIF, WebP.</p>
                        
                        <div class="upload-area <?= $siteLogo ? 'has-image' : '' ?>" id="logoUploadArea" onclick="openMediaModal('logo')">
                            <?php if ($siteLogo): ?>
                                <div class="upload-preview">
                                    <img src="<?= esc($siteLogo) ?>" alt="Site Logo" id="logoPreviewImg">
                                    <div class="upload-preview-actions">
                                        <button type="button" class="upload-preview-btn change" onclick="event.stopPropagation(); openMediaModal('logo')" title="Change">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                        </button>
                                        <button type="button" class="upload-preview-btn remove" onclick="event.stopPropagation(); removeLogo()" title="Remove">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                        </button>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="upload-placeholder" id="logoPlaceholder">
                                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                                    <p>Click to select logo</p>
                                    <small>SVG, PNG, JPG, GIF, WebP</small>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="dimension-inputs">
                            <div class="dimension-group">
                                <label for="logoWidth">Width (px)</label>
                                <input type="number" id="logoWidth" value="<?= $siteLogoWidth ?: '' ?>" placeholder="Auto" min="0" max="1000">
                            </div>
                            <div class="dimension-group">
                                <label for="logoHeight">Height (px)</label>
                                <input type="number" id="logoHeight" value="<?= $siteLogoHeight ?: '' ?>" placeholder="Auto" min="0" max="500">
                            </div>
                        </div>
                        <p class="dimension-hint">Leave empty for automatic sizing. Set one dimension for proportional scaling.</p>
                    </div>
                    
                    <div class="upload-section">
                        <label class="upload-label">Site Favicon</label>
                        <p class="upload-hint">Recommended: SVG or 512×512 PNG. Also supports ICO, JPG, GIF.</p>
                        
                        <div class="upload-area <?= $siteFavicon ? 'has-image' : '' ?>" id="faviconUploadArea" onclick="openMediaModal('favicon')">
                            <?php if ($siteFavicon): ?>
                                <div class="upload-preview">
                                    <img src="<?= esc($siteFavicon) ?>" alt="Site Favicon" id="faviconPreviewImg" style="max-height: 64px;">
                                    <div class="upload-preview-actions">
                                        <button type="button" class="upload-preview-btn change" onclick="event.stopPropagation(); openMediaModal('favicon')" title="Change">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                        </button>
                                        <button type="button" class="upload-preview-btn remove" onclick="event.stopPropagation(); removeFavicon()" title="Remove">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                        </button>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="upload-placeholder" id="faviconPlaceholder">
                                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="2" width="20" height="20" rx="2"></rect><circle cx="8" cy="8" r="1"></circle><circle cx="12" cy="8" r="1"></circle><circle cx="16" cy="8" r="1"></circle><circle cx="8" cy="12" r="1"></circle><circle cx="12" cy="12" r="1"></circle><circle cx="16" cy="12" r="1"></circle><circle cx="8" cy="16" r="1"></circle><circle cx="12" cy="16" r="1"></circle><circle cx="16" cy="16" r="1"></circle></svg>
                                    <p>Click to select favicon</p>
                                    <small>SVG, PNG, ICO, JPG, GIF</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="panel-footer">
                    <button type="button" class="btn btn-primary" id="saveIdentityBtn" onclick="saveIdentity()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                        Publish
                    </button>
                </div>
            </div>
            
            <!-- Custom CSS Tab -->
            <div class="tab-content" data-tab="css">
                <div class="editor-container">
                    <div class="editor-header">
                        <div class="editor-title">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
                            Custom CSS
                        </div>
                        <span class="editor-hint">Ctrl+S to save</span>
                    </div>
                    <div class="editor-wrap">
                        <textarea id="cssEditor"><?= esc($customCss) ?></textarea>
                    </div>
                </div>
                
                <div class="panel-footer">
                    <button type="button" class="btn btn-secondary" onclick="resetCSS()">Reset</button>
                    <button type="button" class="btn btn-primary" id="saveCssBtn" onclick="saveCSS()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                        Publish
                    </button>
                </div>
            </div>
        </div>
        
        <div class="preview-panel">
            <div class="preview-toolbar">
                <div class="device-switcher">
                    <button class="device-btn active" data-device="desktop" title="Desktop">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                    </button>
                    <button class="device-btn" data-device="tablet" title="Tablet">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
                    </button>
                    <button class="device-btn" data-device="mobile" title="Mobile">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
                    </button>
                </div>
                
                <div class="preview-url">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                    <span><?= esc(SITE_URL) ?></span>
                </div>
                
                <div class="preview-actions">
                    <button class="preview-action" onclick="refreshPreview()" title="Refresh">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg>
                    </button>
                    <a href="<?= SITE_URL ?>" target="_blank" class="preview-action" title="Open in new tab">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                    </a>
                </div>
            </div>
            
            <div class="preview-container">
                <div class="preview-frame-wrapper desktop" id="previewWrapper">
                    <iframe id="previewFrame" class="preview-frame" src="<?= SITE_URL ?>"></iframe>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Media Library Modal -->
    <div class="media-modal-overlay" id="mediaModal">
        <div class="media-modal">
            <div class="media-modal-header">
                <h3 class="media-modal-title">Select Image</h3>
                <button type="button" class="media-modal-close" onclick="closeMediaModal()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            <div class="media-modal-body">
                <div class="media-tabs">
                    <button class="media-tab active" data-media-tab="library">Media Library</button>
                    <button class="media-tab" data-media-tab="upload">Upload New</button>
                </div>
                
                <div id="mediaLibraryTab">
                    <div class="media-grid" id="mediaGrid">
                        <?php foreach ($allMedia as $media): ?>
                            <?php if (strpos($media['mime_type'], 'image/') === 0): ?>
                            <div class="media-item" data-url="<?= esc($media['url']) ?>" data-mime="<?= esc($media['mime_type']) ?>">
                                <img src="<?= esc($media['url']) ?>" alt="<?= esc($media['alt_text'] ?? $media['filename']) ?>" loading="lazy">
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div id="mediaUploadTab" style="display: none;">
                    <div class="upload-drop-zone" id="dropZone">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                        <p>Drag & drop image here, or click to browse</p>
                        <small>SVG, PNG, JPG, GIF, WebP, ICO (max 10MB)</small>
                        <input type="file" id="fileInput" accept="image/*,.ico,.svg">
                    </div>
                </div>
            </div>
            <div class="media-modal-footer">
                <div class="media-modal-hint" id="selectedHint">Select an image from the library</div>
                <div class="media-modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeMediaModal()">Cancel</button>
                    <button type="button" class="btn btn-primary" id="selectMediaBtn" onclick="selectMedia()" disabled>Select</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="toast" id="toast"></div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/css/css.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/edit/closebrackets.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/edit/matchbrackets.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/hint/show-hint.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/hint/css-hint.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/hint/show-hint.min.css">
    
    <script>
        const previewFrame = document.getElementById('previewFrame');
        const previewWrapper = document.getElementById('previewWrapper');
        const toast = document.getElementById('toast');
        const csrfToken = '<?= csrfToken() ?>';
        
        let currentLogoUrl = '<?= esc($siteLogo) ?>';
        let currentFaviconUrl = '<?= esc($siteFavicon) ?>';
        let mediaModalTarget = null;
        let selectedMediaUrl = null;
        
        // Tab switching
        document.querySelectorAll('.panel-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.panel-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                document.querySelector(`.tab-content[data-tab="${this.dataset.tab}"]`).classList.add('active');
                if (this.dataset.tab === 'css' && window.editor) setTimeout(() => editor.refresh(), 10);
            });
        });
        
        // CodeMirror
        const editor = CodeMirror.fromTextArea(document.getElementById('cssEditor'), {
            mode: 'css', theme: 'eclipse', lineNumbers: true, autoCloseBrackets: true, matchBrackets: true,
            indentUnit: 2, tabSize: 2, lineWrapping: true,
            extraKeys: { "Ctrl-Space": "autocomplete", "Tab": cm => cm.somethingSelected() ? cm.indentSelection("add") : cm.replaceSelection("  ", "end") }
        });
        
        editor.on('inputRead', (cm, change) => { if (change.text[0].match(/[a-z-:]/i)) cm.showHint({completeSingle: false}); });
        
        let originalCSS = editor.getValue();
        let hasChanges = false;
        
        editor.on('change', debounce(() => { updatePreview(editor.getValue()); hasChanges = editor.getValue() !== originalCSS; }, 150));
        
        function updatePreview(css) {
            try {
                const doc = previewFrame.contentDocument || previewFrame.contentWindow.document;
                let el = doc.getElementById('forge-customizer-css');
                if (!el) { el = doc.createElement('style'); el.id = 'forge-customizer-css'; doc.head.appendChild(el); }
                el.textContent = css;
            } catch (e) {}
        }
        
        previewFrame.addEventListener('load', () => { if (editor.getValue()) updatePreview(editor.getValue()); });
        
        async function saveCSS() {
            const btn = document.getElementById('saveCssBtn');
            btn.disabled = true;
            btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;"><circle cx="12" cy="12" r="10"></circle></svg> Saving...';
            
            try {
                const fd = new FormData();
                fd.append('ajax', '1'); fd.append('action', 'save_css'); fd.append('csrf_token', csrfToken); fd.append('custom_css', editor.getValue());
                const res = await fetch(location.href, { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) { originalCSS = editor.getValue(); hasChanges = false; showToast('CSS published!', 'success'); }
                else showToast('Failed: ' + (data.error || 'Unknown'), 'error');
            } catch (e) { showToast('Failed to save', 'error'); }
            
            btn.disabled = false;
            btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg> Publish';
        }
        
        async function saveIdentity() {
            const btn = document.getElementById('saveIdentityBtn');
            btn.disabled = true;
            btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;"><circle cx="12" cy="12" r="10"></circle></svg> Saving...';
            
            try {
                const fd = new FormData();
                fd.append('ajax', '1'); fd.append('action', 'save_identity'); fd.append('csrf_token', csrfToken);
                fd.append('site_logo', currentLogoUrl); fd.append('site_favicon', currentFaviconUrl);
                fd.append('site_logo_width', document.getElementById('logoWidth').value || 0);
                fd.append('site_logo_height', document.getElementById('logoHeight').value || 0);
                const res = await fetch(location.href, { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) { showToast('Site identity published!', 'success'); refreshPreview(); }
                else showToast('Failed: ' + (data.error || 'Unknown'), 'error');
            } catch (e) { showToast('Failed to save', 'error'); }
            
            btn.disabled = false;
            btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg> Publish';
        }
        
        async function removeLogo() {
            if (!confirm('Remove the site logo?')) return;
            currentLogoUrl = '';
            updateLogoPreview('');
            const fd = new FormData(); fd.append('ajax', '1'); fd.append('action', 'remove_logo'); fd.append('csrf_token', csrfToken);
            try { await fetch(location.href, { method: 'POST', body: fd }); showToast('Logo removed', 'success'); refreshPreview(); } catch(e) { showToast('Failed', 'error'); }
        }
        
        async function removeFavicon() {
            if (!confirm('Remove the site favicon?')) return;
            currentFaviconUrl = '';
            updateFaviconPreview('');
            const fd = new FormData(); fd.append('ajax', '1'); fd.append('action', 'remove_favicon'); fd.append('csrf_token', csrfToken);
            try { await fetch(location.href, { method: 'POST', body: fd }); showToast('Favicon removed', 'success'); refreshPreview(); } catch(e) { showToast('Failed', 'error'); }
        }
        
        function updateLogoPreview(url) {
            const area = document.getElementById('logoUploadArea');
            if (url) {
                area.classList.add('has-image');
                area.innerHTML = `<div class="upload-preview"><img src="${escapeHtml(url)}" alt="Logo"><div class="upload-preview-actions"><button type="button" class="upload-preview-btn change" onclick="event.stopPropagation(); openMediaModal('logo')" title="Change"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg></button><button type="button" class="upload-preview-btn remove" onclick="event.stopPropagation(); removeLogo()" title="Remove"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button></div></div>`;
            } else {
                area.classList.remove('has-image');
                area.innerHTML = `<div class="upload-placeholder"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg><p>Click to select logo</p><small>SVG, PNG, JPG, GIF, WebP</small></div>`;
            }
        }
        
        function updateFaviconPreview(url) {
            const area = document.getElementById('faviconUploadArea');
            if (url) {
                area.classList.add('has-image');
                area.innerHTML = `<div class="upload-preview"><img src="${escapeHtml(url)}" alt="Favicon" style="max-height:64px"><div class="upload-preview-actions"><button type="button" class="upload-preview-btn change" onclick="event.stopPropagation(); openMediaModal('favicon')" title="Change"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg></button><button type="button" class="upload-preview-btn remove" onclick="event.stopPropagation(); removeFavicon()" title="Remove"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button></div></div>`;
            } else {
                area.classList.remove('has-image');
                area.innerHTML = `<div class="upload-placeholder"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="2" width="20" height="20" rx="2"></rect><circle cx="8" cy="8" r="1"></circle><circle cx="12" cy="8" r="1"></circle><circle cx="16" cy="8" r="1"></circle><circle cx="8" cy="12" r="1"></circle><circle cx="12" cy="12" r="1"></circle><circle cx="16" cy="12" r="1"></circle><circle cx="8" cy="16" r="1"></circle><circle cx="12" cy="16" r="1"></circle><circle cx="16" cy="16" r="1"></circle></svg><p>Click to select favicon</p><small>SVG, PNG, ICO, JPG, GIF</small></div>`;
            }
        }
        
        function openMediaModal(target) {
            mediaModalTarget = target;
            selectedMediaUrl = null;
            document.getElementById('mediaModal').classList.add('active');
            document.getElementById('selectMediaBtn').disabled = true;
            document.getElementById('selectedHint').textContent = 'Select an image from the library';
            document.querySelectorAll('.media-item').forEach(i => i.classList.remove('selected'));
        }
        
        function closeMediaModal() { document.getElementById('mediaModal').classList.remove('active'); mediaModalTarget = null; selectedMediaUrl = null; }
        
        document.querySelectorAll('.media-item').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.media-item').forEach(i => i.classList.remove('selected'));
                this.classList.add('selected');
                selectedMediaUrl = this.dataset.url;
                document.getElementById('selectMediaBtn').disabled = false;
                document.getElementById('selectedHint').textContent = 'Image selected';
            });
        });
        
        function selectMedia() {
            if (!selectedMediaUrl || !mediaModalTarget) return;
            if (mediaModalTarget === 'logo') { currentLogoUrl = selectedMediaUrl; updateLogoPreview(selectedMediaUrl); }
            else if (mediaModalTarget === 'favicon') { currentFaviconUrl = selectedMediaUrl; updateFaviconPreview(selectedMediaUrl); }
            closeMediaModal();
        }
        
        document.querySelectorAll('.media-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.media-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('mediaLibraryTab').style.display = this.dataset.mediaTab === 'library' ? 'block' : 'none';
                document.getElementById('mediaUploadTab').style.display = this.dataset.mediaTab === 'upload' ? 'block' : 'none';
            });
        });
        
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        
        dropZone.addEventListener('click', () => fileInput.click());
        dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragover'); });
        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
        dropZone.addEventListener('drop', e => { e.preventDefault(); dropZone.classList.remove('dragover'); if (e.dataTransfer.files.length) uploadFile(e.dataTransfer.files[0]); });
        fileInput.addEventListener('change', e => { if (e.target.files.length) uploadFile(e.target.files[0]); });
        
        async function uploadFile(file) {
            const allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml', 'image/x-icon', 'image/vnd.microsoft.icon'];
            if (!allowed.includes(file.type) && !file.name.endsWith('.ico') && !file.name.endsWith('.svg')) { showToast('Invalid file type', 'error'); return; }
            if (file.size > 10 * 1024 * 1024) { showToast('File too large (max 10MB)', 'error'); return; }
            
            const fd = new FormData(); fd.append('file', file); fd.append('csrf_token', csrfToken);
            dropZone.innerHTML = '<p>Uploading...</p>';
            
            try {
                const res = await fetch('<?= ADMIN_URL ?>/media.php?ajax=1&action=upload', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    const grid = document.getElementById('mediaGrid');
                    const item = document.createElement('div');
                    item.className = 'media-item selected';
                    item.dataset.url = data.url;
                    item.innerHTML = `<img src="${escapeHtml(data.url)}" alt="Uploaded">`;
                    item.addEventListener('click', function() {
                        document.querySelectorAll('.media-item').forEach(i => i.classList.remove('selected'));
                        this.classList.add('selected');
                        selectedMediaUrl = this.dataset.url;
                        document.getElementById('selectMediaBtn').disabled = false;
                    });
                    grid.insertBefore(item, grid.firstChild);
                    document.querySelectorAll('.media-item').forEach(i => i.classList.remove('selected'));
                    item.classList.add('selected');
                    selectedMediaUrl = data.url;
                    document.getElementById('selectMediaBtn').disabled = false;
                    document.querySelector('.media-tab[data-media-tab="library"]').click();
                    showToast('Uploaded!', 'success');
                } else showToast('Upload failed: ' + (data.error || 'Unknown'), 'error');
            } catch (e) { showToast('Upload failed', 'error'); }
            
            dropZone.innerHTML = `<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg><p>Drag & drop image here, or click to browse</p><small>SVG, PNG, JPG, GIF, WebP, ICO (max 10MB)</small><input type="file" id="fileInput" accept="image/*,.ico,.svg">`;
            document.getElementById('fileInput').addEventListener('change', e => { if (e.target.files.length) uploadFile(e.target.files[0]); });
        }
        
        function resetCSS() { if (confirm('Reset all custom CSS?')) { editor.setValue(''); updatePreview(''); hasChanges = true; } }
        function refreshPreview() { previewFrame.src = previewFrame.src; }
        
        document.querySelectorAll('.device-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.device-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                previewWrapper.className = 'preview-frame-wrapper ' + this.dataset.device;
            });
        });
        
        function showToast(msg, type = 'success') { toast.textContent = msg; toast.className = 'toast ' + type + ' show'; setTimeout(() => toast.classList.remove('show'), 3000); }
        function debounce(fn, wait) { let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn.apply(this, args), wait); }; }
        function escapeHtml(text) { const d = document.createElement('div'); d.textContent = text; return d.innerHTML; }
        
        document.addEventListener('keydown', e => {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') { e.preventDefault(); document.querySelector('.tab-content[data-tab="css"]').classList.contains('active') ? saveCSS() : saveIdentity(); }
            if (e.key === 'Escape') closeMediaModal();
        });
        
        document.getElementById('mediaModal').addEventListener('click', e => { if (e.target === document.getElementById('mediaModal')) closeMediaModal(); });
        window.addEventListener('beforeunload', e => { if (hasChanges) { e.preventDefault(); e.returnValue = ''; } });
    </script>
</body>
</html>
