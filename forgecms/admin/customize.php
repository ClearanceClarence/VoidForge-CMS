<?php
/**
 * Frontend Customizer - Live CSS Editor with Syntax Highlighting
 * Forge CMS
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

// Handle AJAX save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    if (!verifyCsrf()) {
        echo json_encode(['success' => false, 'error' => 'Invalid token']);
        exit;
    }
    
    setOption('custom_frontend_css', $_POST['custom_css'] ?? '');
    echo json_encode(['success' => true]);
    exit;
}

$customCss = getOption('custom_frontend_css', '');
$siteTitle = getOption('site_title', 'My Site');
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
    <!-- CodeMirror CSS -->
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
        
        .customizer {
            display: flex;
            height: 100vh;
        }
        
        /* Editor Panel */
        .editor-panel {
            width: 420px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(0,0,0,0.1);
            position: relative;
        }
        
        .panel-header {
            padding: 1rem 1.25rem;
            background: rgba(0,0,0,0.05);
            border-bottom: 1px solid rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .panel-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .panel-brand svg {
            color: #818cf8;
        }
        
        .panel-brand span {
            font-weight: 700;
            font-size: 1.125rem;
            letter-spacing: -0.025em;
        }
        
        .panel-close {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.875rem;
            background: rgba(0,0,0,0.1);
            border: none;
            border-radius: 8px;
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        
        .panel-close:hover {
            background: rgba(0,0,0,0.1);
            color: #1e293b;
        }
        
        .panel-section {
            padding: 1.25rem;
            border-bottom: 1px solid rgba(0,0,0,0.06);
        }
        
        .panel-section-title {
            font-size: 0.6875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #64748b;
            margin-bottom: 0.75rem;
        }
        
        .site-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem;
            background: rgba(0,0,0,0.03);
            border-radius: 10px;
        }
        
        .site-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .site-icon svg {
            color: #fff;
        }
        
        .site-details h3 {
            font-size: 0.9375rem;
            font-weight: 600;
            color: #0f172a;
        }
        
        .site-details p {
            font-size: 0.8125rem;
            color: #64748b;
        }
        
        /* Code Editor */
        .editor-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .editor-header {
            padding: 0.875rem 1.25rem;
            background: rgba(0,0,0,0.03);
            border-bottom: 1px solid rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .editor-title {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #1e293b;
        }
        
        .editor-title svg {
            color: #818cf8;
        }
        
        .editor-hint {
            font-size: 0.75rem;
            color: #64748b;
        }
        
        .editor-wrap {
            flex: 1;
            overflow: hidden;
        }
        
        /* CodeMirror customizations */
        .CodeMirror {
            height: 100%;
            font-family: 'JetBrains Mono', 'Monaco', 'Menlo', monospace;
            font-size: 13px;
            line-height: 1.6;
        }
        
        .CodeMirror-gutters {
            background: #f1f5f9;
            border-right: 1px solid rgba(0,0,0,0.06);
        }
        
        .CodeMirror-linenumber {
            color: #475569;
        }
        
        /* Panel Footer */
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
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(99, 102, 241, 0.5);
        }
        
        .btn-primary:disabled {
            opacity: 0.6;
            transform: none;
            cursor: not-allowed;
        }
        
        .btn-secondary {
            background: rgba(0,0,0,0.1);
            color: #64748b;
            border: 1px solid rgba(0,0,0,0.1);
        }
        
        .btn-secondary:hover {
            background: rgba(0,0,0,0.1);
            color: #1e293b;
        }
        
        /* Preview Frame */
        .preview-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #ffffff;
        }
        
        .preview-toolbar {
            padding: 0.75rem 1rem;
            background: #f8fafc;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .device-switcher {
            display: flex;
            gap: 0.25rem;
            background: rgba(0,0,0,0.03);
            padding: 0.25rem;
            border-radius: 8px;
        }
        
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
        
        .device-btn:hover {
            color: #64748b;
        }
        
        .device-btn.active {
            background: rgba(99, 102, 241, 0.1);
            color: #6366f1;
        }
        
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
        
        .preview-url svg {
            flex-shrink: 0;
        }
        
        .preview-url span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .preview-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .preview-action {
            padding: 0.5rem;
            background: rgba(0,0,0,0.03);
            border: none;
            border-radius: 6px;
            color: #64748b;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        
        .preview-action:hover {
            background: rgba(0,0,0,0.1);
            color: #1e293b;
        }
        
        .preview-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 1.5rem;
            overflow: auto;
            background: 
                radial-gradient(circle at 50% 50%, rgba(99, 102, 241, 0.03) 0%, transparent 50%),
                linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        }
        
        .preview-frame-wrapper {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .preview-frame-wrapper.desktop {
            width: 100%;
            max-width: 100%;
        }
        
        .preview-frame-wrapper.tablet {
            width: 768px;
        }
        
        .preview-frame-wrapper.mobile {
            width: 375px;
        }
        
        .preview-frame {
            width: 100%;
            height: 100%;
            border: none;
        }
        
        /* Toast notification */
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
        
        .toast.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }
        
        .toast.success {
            border-left: 4px solid #22c55e;
        }
        
        .toast.error {
            border-left: 4px solid #ef4444;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="customizer">
        <!-- Editor Panel -->
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
            
            <div class="panel-section">
                <div class="panel-section-title">Editing</div>
                <div class="site-info">
                    <div class="site-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="2" y1="12" x2="22" y2="12"></line>
                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                        </svg>
                    </div>
                    <div class="site-details">
                        <h3><?= esc($siteTitle) ?></h3>
                        <p><?= esc(parse_url(SITE_URL, PHP_URL_HOST)) ?></p>
                    </div>
                </div>
            </div>
            
            <div class="editor-container">
                <div class="editor-header">
                    <div class="editor-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="16 18 22 12 16 6"></polyline>
                            <polyline points="8 6 2 12 8 18"></polyline>
                        </svg>
                        Custom CSS
                    </div>
                    <span class="editor-hint">Ctrl+S to save</span>
                </div>
                <div class="editor-wrap">
                    <textarea id="cssEditor"><?= esc($customCss) ?></textarea>
                </div>
            </div>
            
            <div class="panel-footer">
                <button type="button" class="btn btn-secondary" onclick="resetCSS()">
                    Reset
                </button>
                <button type="button" class="btn btn-primary" id="saveBtn" onclick="saveCSS()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    Publish
                </button>
            </div>
        </div>
        
        <!-- Preview Panel -->
        <div class="preview-panel">
            <div class="preview-toolbar">
                <div class="device-switcher">
                    <button class="device-btn active" data-device="desktop" title="Desktop">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                            <line x1="8" y1="21" x2="16" y2="21"></line>
                            <line x1="12" y1="17" x2="12" y2="21"></line>
                        </svg>
                    </button>
                    <button class="device-btn" data-device="tablet" title="Tablet">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect>
                            <line x1="12" y1="18" x2="12.01" y2="18"></line>
                        </svg>
                    </button>
                    <button class="device-btn" data-device="mobile" title="Mobile">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
                            <line x1="12" y1="18" x2="12.01" y2="18"></line>
                        </svg>
                    </button>
                </div>
                
                <div class="preview-url">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="2" y1="12" x2="22" y2="12"></line>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                    </svg>
                    <span><?= esc(SITE_URL) ?></span>
                </div>
                
                <div class="preview-actions">
                    <button class="preview-action" onclick="refreshPreview()" title="Refresh">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 4 23 10 17 10"></polyline>
                            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                        </svg>
                    </button>
                    <a href="<?= SITE_URL ?>" target="_blank" class="preview-action" title="Open in new tab">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                            <polyline points="15 3 21 3 21 9"></polyline>
                            <line x1="10" y1="14" x2="21" y2="3"></line>
                        </svg>
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
    
    <div class="toast" id="toast"></div>
    
    <!-- CodeMirror JS -->
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
        const saveBtn = document.getElementById('saveBtn');
        const toast = document.getElementById('toast');
        const csrfToken = '<?= csrfToken() ?>';
        
        // Initialize CodeMirror
        const editor = CodeMirror.fromTextArea(document.getElementById('cssEditor'), {
            mode: 'css',
            theme: 'eclipse',
            lineNumbers: true,
            autoCloseBrackets: true,
            matchBrackets: true,
            indentUnit: 2,
            tabSize: 2,
            lineWrapping: true,
            extraKeys: {
                "Ctrl-Space": "autocomplete",
                "Tab": function(cm) {
                    if (cm.somethingSelected()) {
                        cm.indentSelection("add");
                    } else {
                        cm.replaceSelection("  ", "end");
                    }
                }
            },
            hintOptions: {
                completeSingle: false
            },
            placeholder: '/* Add your custom CSS here */'
        });
        
        // Auto-show hints when typing
        editor.on('inputRead', function(cm, change) {
            if (change.text[0].match(/[a-z-:]/i)) {
                cm.showHint({completeSingle: false});
            }
        });
        
        let originalCSS = editor.getValue();
        let hasChanges = false;
        
        // Live preview updates
        editor.on('change', debounce(function() {
            updatePreview(editor.getValue());
            hasChanges = editor.getValue() !== originalCSS;
        }, 150));
        
        function updatePreview(css) {
            try {
                const iframeDoc = previewFrame.contentDocument || previewFrame.contentWindow.document;
                let styleEl = iframeDoc.getElementById('forge-customizer-css');
                
                if (!styleEl) {
                    styleEl = iframeDoc.createElement('style');
                    styleEl.id = 'forge-customizer-css';
                    iframeDoc.head.appendChild(styleEl);
                }
                
                styleEl.textContent = css;
            } catch (e) {
                console.log('Preview update failed (cross-origin):', e);
            }
        }
        
        // Initialize preview when iframe loads
        previewFrame.addEventListener('load', function() {
            if (editor.getValue()) {
                updatePreview(editor.getValue());
            }
        });
        
        // Save CSS
        async function saveCSS() {
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;"><circle cx="12" cy="12" r="10"></circle></svg> Saving...';
            
            try {
                const formData = new FormData();
                formData.append('ajax', '1');
                formData.append('csrf_token', csrfToken);
                formData.append('custom_css', editor.getValue());
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    originalCSS = editor.getValue();
                    hasChanges = false;
                    showToast('Changes published successfully!', 'success');
                } else {
                    showToast('Failed to save: ' + (result.error || 'Unknown error'), 'error');
                }
            } catch (e) {
                showToast('Failed to save changes', 'error');
            }
            
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg> Publish';
        }
        
        // Reset CSS
        function resetCSS() {
            if (confirm('Reset all custom CSS? This cannot be undone.')) {
                editor.setValue('');
                updatePreview('');
                hasChanges = true;
            }
        }
        
        // Refresh preview
        function refreshPreview() {
            previewFrame.src = previewFrame.src;
        }
        
        // Device switcher
        document.querySelectorAll('.device-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.device-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                previewWrapper.className = 'preview-frame-wrapper ' + this.dataset.device;
            });
        });
        
        // Toast notification
        function showToast(message, type = 'success') {
            toast.textContent = message;
            toast.className = 'toast ' + type + ' show';
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }
        
        // Debounce helper
        function debounce(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }
        
        // Ctrl/Cmd + S to save
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                saveCSS();
            }
        });
        
        // Warn before leaving with unsaved changes
        window.addEventListener('beforeunload', function(e) {
            if (hasChanges) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    </script>
</body>
</html>
