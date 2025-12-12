<?php
/**
 * Login Screen Editor - VoidForge CMS
 * Standalone full-screen editor with live preview
 */

define('CMS_ROOT', dirname(__DIR__));
require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/user.php';

User::startSession();
User::requireRole('admin');

$defaults = [
    // Background
    'bg_type' => 'gradient',
    'bg_color' => '#f8fafc',
    'bg_gradient_start' => '#f8fafc',
    'bg_gradient_end' => '#e2e8f0',
    'bg_gradient_angle' => 180,
    'bg_image' => '',
    'bg_overlay' => false,
    'bg_overlay_color' => 'rgba(0,0,0,0.5)',
    'bg_pattern' => 'dots',
    'bg_pattern_color' => 'rgba(0,0,0,0.08)',
    'bg_pattern_size' => 20,
    // Logo
    'logo_type' => 'default',
    'logo_image' => '',
    'logo_width' => 180,
    'logo_margin_bottom' => 32,
    // Card
    'card_bg' => '#ffffff',
    'card_border' => '#e2e8f0',
    'card_border_width' => 1,
    'card_radius' => 16,
    'card_shadow' => true,
    'card_shadow_color' => 'rgba(0,0,0,0.15)',
    'card_backdrop_blur' => 0,
    'card_width' => 400,
    'card_padding' => 40,
    // Title
    'title_text' => 'Welcome Back',
    'title_color' => '#1e293b',
    'title_size' => 28,
    'title_weight' => 700,
    'subtitle_text' => 'Sign in to continue',
    'subtitle_color' => '#64748b',
    'subtitle_size' => 14,
    // Labels & Inputs
    'label_color' => '#475569',
    'label_size' => 13,
    'input_bg' => '#f8fafc',
    'input_border' => '#e2e8f0',
    'input_text' => '#1e293b',
    'input_placeholder' => '#94a3b8',
    'input_radius' => 10,
    'input_padding' => 12,
    'input_focus_color' => '#8b5cf6',
    // Button
    'button_label' => 'Sign In',
    'button_bg_type' => 'gradient',
    'button_solid_color' => '#8b5cf6',
    'button_gradient_start' => '#8b5cf6',
    'button_gradient_end' => '#06b6d4',
    'button_gradient_angle' => 135,
    'button_text' => '#ffffff',
    'button_radius' => 10,
    'button_padding' => 14,
    'button_shadow' => true,
    'button_full_width' => true,
    // Options
    'show_remember' => true,
    'show_forgot' => false,
    'show_register' => false,
    'remember_label' => 'Remember me',
    'forgot_label' => 'Forgot password?',
    'register_text' => "Don't have an account?",
    'register_label' => 'Sign up',
    // Extras
    'custom_css' => '',
    'footer_text' => '',
    'animation_type' => 'fade',
    'animation_duration' => 500,
];

$loginSettings = getOption('login_settings', []);
$settings = array_merge($defaults, $loginSettings);

// Handle AJAX save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        echo json_encode(['success' => false, 'error' => 'Invalid token']);
        exit;
    }
    
    $newSettings = [];
    foreach ($defaults as $key => $default) {
        if (isset($_POST[$key])) {
            $newSettings[$key] = $_POST[$key];
        } else {
            $newSettings[$key] = is_bool($default) ? false : $default;
        }
    }
    
    // Handle boolean fields explicitly
    $boolFields = ['bg_overlay', 'card_shadow', 'button_shadow', 'button_full_width', 'show_remember', 'show_forgot', 'show_register'];
    foreach ($boolFields as $field) {
        $newSettings[$field] = ($_POST[$field] ?? '0') === '1';
    }
    
    setOption('login_settings', $newSettings);
    echo json_encode(['success' => true]);
    exit;
}

// Handle preview rendering
if (isset($_GET['preview'])) {
    $previewSettings = isset($_GET['settings']) ? json_decode(base64_decode($_GET['settings']), true) : $settings;
    if (!$previewSettings) $previewSettings = $settings;
    $s = array_merge($defaults, $previewSettings);
    
    // Build background style
    $bgStyle = '';
    switch ($s['bg_type']) {
        case 'color':
            $bgStyle = "background: {$s['bg_color']};";
            break;
        case 'gradient':
            $bgStyle = "background: linear-gradient({$s['bg_gradient_angle']}deg, {$s['bg_gradient_start']}, {$s['bg_gradient_end']});";
            break;
        case 'image':
            $bgStyle = "background: url('{$s['bg_image']}') center/cover no-repeat;";
            break;
        case 'pattern':
            $patternColor = $s['bg_pattern_color'] ?? 'rgba(0,0,0,0.08)';
            $patternSize = (int)($s['bg_pattern_size'] ?? 20);
            switch ($s['bg_pattern']) {
                case 'dots':
                    $bgStyle = "background-color: {$s['bg_color']}; background-image: radial-gradient({$patternColor} 1px, transparent 1px); background-size: {$patternSize}px {$patternSize}px;";
                    break;
                case 'grid':
                    $bgStyle = "background-color: {$s['bg_color']}; background-image: linear-gradient({$patternColor} 1px, transparent 1px), linear-gradient(90deg, {$patternColor} 1px, transparent 1px); background-size: {$patternSize}px {$patternSize}px;";
                    break;
                case 'diagonal':
                    $half = $patternSize / 2;
                    $bgStyle = "background-color: {$s['bg_color']}; background-image: repeating-linear-gradient(45deg, transparent, transparent {$half}px, {$patternColor} {$half}px, {$patternColor} {$patternSize}px);";
                    break;
                case 'crosses':
                    $bgStyle = "background-color: {$s['bg_color']}; background-image: linear-gradient({$patternColor} 2px, transparent 2px), linear-gradient(90deg, {$patternColor} 2px, transparent 2px); background-size: {$patternSize}px {$patternSize}px; background-position: center center;";
                    break;
                case 'waves':
                    $bgStyle = "background-color: {$s['bg_color']}; background-image: repeating-linear-gradient(0deg, transparent, transparent {$half}px, {$patternColor} {$half}px, {$patternColor} " . ($half + 1) . "px);";
                    break;
                default:
                    $bgStyle = "background-color: {$s['bg_color']}; background-image: radial-gradient({$patternColor} 1px, transparent 1px); background-size: {$patternSize}px {$patternSize}px;";
            }
            break;
    }
    
    // Build card styles
    $cardBlur = (int)$s['card_backdrop_blur'] > 0 ? "backdrop-filter: blur({$s['card_backdrop_blur']}px);" : '';
    $cardShadow = $s['card_shadow'] ? "box-shadow: 0 25px 50px -12px {$s['card_shadow_color']};" : '';
    
    // Build button styles
    if ($s['button_bg_type'] === 'gradient') {
        $buttonBg = "background: linear-gradient({$s['button_gradient_angle']}deg, {$s['button_gradient_start']}, {$s['button_gradient_end']});";
    } else {
        $buttonBg = "background: {$s['button_solid_color']};";
    }
    $buttonShadow = $s['button_shadow'] ? "box-shadow: 0 4px 15px {$s['button_gradient_start']}40;" : '';
    $buttonWidth = $s['button_full_width'] ? 'width: 100%;' : '';
    
    // Animation class
    $animClass = $s['animation_type'] !== 'none' ? 'anim-' . $s['animation_type'] : '';
    $animDuration = (int)$s['animation_duration'] / 1000;
    
    // Output preview HTML
    ?><!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: Inter, system-ui, sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; <?= $bgStyle ?> }
.login-overlay { position: fixed; inset: 0; background: <?= $s['bg_overlay_color'] ?>; z-index: 1; }
.login-card {
    position: relative; z-index: 10;
    width: <?= (int)$s['card_width'] ?>px; max-width: 90vw;
    padding: <?= (int)$s['card_padding'] ?>px;
    background: <?= $s['card_bg'] ?>;
    border: <?= (int)$s['card_border_width'] ?>px solid <?= $s['card_border'] ?>;
    border-radius: <?= (int)$s['card_radius'] ?>px;
    <?= $cardBlur ?> <?= $cardShadow ?>
}
.login-logo { text-align: center; margin-bottom: <?= (int)$s['logo_margin_bottom'] ?>px; }
.login-logo-default { display: inline-flex; align-items: center; gap: 12px; }
.login-logo-default span { font-size: 24px; font-weight: 700; background: linear-gradient(135deg, #8b5cf6, #06b6d4); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.login-logo-custom { max-width: <?= (int)$s['logo_width'] ?>px; height: auto; }
.login-title { font-size: <?= (int)$s['title_size'] ?>px; font-weight: <?= (int)$s['title_weight'] ?>; color: <?= $s['title_color'] ?>; margin-bottom: 8px; text-align: center; }
.login-subtitle { font-size: <?= (int)$s['subtitle_size'] ?>px; color: <?= $s['subtitle_color'] ?>; text-align: center; margin-bottom: 32px; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; font-size: <?= (int)$s['label_size'] ?>px; font-weight: 500; color: <?= $s['label_color'] ?>; margin-bottom: 8px; }
.form-group input {
    width: 100%; padding: <?= (int)$s['input_padding'] ?>px 16px;
    background: <?= $s['input_bg'] ?>; border: 1px solid <?= $s['input_border'] ?>;
    border-radius: <?= (int)$s['input_radius'] ?>px; color: <?= $s['input_text'] ?>;
    font-size: 14px; font-family: inherit; transition: all 0.2s;
}
.form-group input::placeholder { color: <?= $s['input_placeholder'] ?>; }
.form-group input:focus { outline: none; border-color: <?= $s['input_focus_color'] ?>; box-shadow: 0 0 0 3px <?= $s['input_focus_color'] ?>20; }
.form-options { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; font-size: 13px; }
.remember-me { display: flex; align-items: center; gap: 8px; color: <?= $s['label_color'] ?>; }
.remember-me input { width: 16px; height: 16px; accent-color: <?= $s['input_focus_color'] ?>; }
.forgot-link { color: <?= $s['input_focus_color'] ?>; text-decoration: none; }
.forgot-link:hover { text-decoration: underline; }
.login-btn {
    <?= $buttonWidth ?> padding: <?= (int)$s['button_padding'] ?>px 24px;
    <?= $buttonBg ?> color: <?= $s['button_text'] ?>;
    border: none; border-radius: <?= (int)$s['button_radius'] ?>px;
    font-size: 15px; font-weight: 600; font-family: inherit;
    cursor: pointer; transition: all 0.2s; <?= $buttonShadow ?>
}
.login-btn:hover { transform: translateY(-2px); filter: brightness(1.05); }
.login-footer { text-align: center; margin-top: 24px; font-size: 13px; color: <?= $s['subtitle_color'] ?>; }
.login-footer a { color: <?= $s['input_focus_color'] ?>; text-decoration: none; }
.login-footer a:hover { text-decoration: underline; }
/* Animations */
.anim-fade { animation: fadeIn <?= $animDuration ?>s ease; }
.anim-slide { animation: slideIn <?= $animDuration ?>s ease; }
.anim-scale { animation: scaleIn <?= $animDuration ?>s ease; }
.anim-bounce { animation: bounceIn <?= $animDuration ?>s cubic-bezier(0.68, -0.55, 0.265, 1.55); }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideIn { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
@keyframes scaleIn { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
@keyframes bounceIn { from { opacity: 0; transform: scale(0.3); } to { opacity: 1; transform: scale(1); } }
<?= $s['custom_css'] ?? '' ?>
</style>
</head>
<body>
<?php if ($s['bg_overlay']): ?><div class="login-overlay"></div><?php endif; ?>
<div class="login-card <?= $animClass ?>">
<?php if ($s['logo_type'] === 'default'): ?>
<div class="login-logo">
    <div class="login-logo-default">
        <svg width="48" height="48" viewBox="0 0 32 32" fill="none">
            <defs><linearGradient id="logoGrad" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" style="stop-color:#8b5cf6"/><stop offset="100%" style="stop-color:#06b6d4"/></linearGradient></defs>
            <path d="M5 5 L16 27 L27 5" fill="none" stroke="url(#logoGrad)" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="16" cy="14" r="3.5" fill="#c4b5fd"/>
        </svg>
        <span>VoidForge</span>
    </div>
</div>
<?php elseif ($s['logo_type'] === 'custom' && $s['logo_image']): ?>
<div class="login-logo"><img src="<?= htmlspecialchars($s['logo_image']) ?>" class="login-logo-custom" alt="Logo"></div>
<?php endif; ?>
<h1 class="login-title"><?= htmlspecialchars($s['title_text']) ?></h1>
<?php if ($s['subtitle_text']): ?><p class="login-subtitle"><?= htmlspecialchars($s['subtitle_text']) ?></p><?php endif; ?>
<form>
    <div class="form-group"><label>Username or Email</label><input type="text" placeholder="Enter your username"></div>
    <div class="form-group"><label>Password</label><input type="password" placeholder="Enter your password"></div>
    <?php if ($s['show_remember'] || $s['show_forgot']): ?>
    <div class="form-options">
        <?php if ($s['show_remember']): ?><label class="remember-me"><input type="checkbox"> <?= htmlspecialchars($s['remember_label']) ?></label><?php else: ?><span></span><?php endif; ?>
        <?php if ($s['show_forgot']): ?><a href="#" class="forgot-link"><?= htmlspecialchars($s['forgot_label']) ?></a><?php endif; ?>
    </div>
    <?php endif; ?>
    <button type="button" class="login-btn"><?= htmlspecialchars($s['button_label']) ?></button>
    <?php if ($s['show_register']): ?><div class="login-footer"><?= htmlspecialchars($s['register_text']) ?> <a href="#"><?= htmlspecialchars($s['register_label']) ?></a></div><?php endif; ?>
    <?php if ($s['footer_text']): ?><div class="login-footer"><?= $s['footer_text'] ?></div><?php endif; ?>
</form>
</div>
</body>
</html>
<?php
    exit;
}

$siteTitle = getOption('site_name', CMS_NAME);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Editor - <?= esc($siteTitle) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: #f8fafc; color: #1e293b; height: 100vh; overflow: hidden; }
        
        /* Layout */
        .editor-layout { display: flex; height: 100vh; }
        .editor-panel { width: 380px; background: #fff; display: flex; flex-direction: column; border-right: 1px solid #e2e8f0; }
        
        /* Header */
        .panel-header { padding: 0.875rem 1rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; }
        .panel-brand { display: flex; align-items: center; gap: 0.625rem; }
        .panel-brand svg { color: #8b5cf6; }
        .panel-brand span { font-weight: 600; font-size: 0.9375rem; color: #1e293b; }
        .panel-close { display: flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.75rem; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 6px; color: #64748b; font-size: 0.75rem; font-weight: 500; cursor: pointer; text-decoration: none; transition: all 0.15s; }
        .panel-close:hover { background: #e2e8f0; color: #1e293b; }
        
        /* Tabs */
        .panel-tabs { display: flex; padding: 0.375rem; gap: 2px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
        .panel-tab { flex: 1; padding: 0.5rem 0.25rem; background: transparent; border: none; border-radius: 5px; color: #64748b; font-size: 0.6875rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.03em; cursor: pointer; transition: all 0.15s; }
        .panel-tab:hover { color: #475569; background: #f1f5f9; }
        .panel-tab.active { background: linear-gradient(135deg, #8b5cf6, #06b6d4); color: #fff; }
        
        /* Presets */
        .presets-section { padding: 0.75rem 1rem; border-bottom: 1px solid #e2e8f0; background: #fafafa; }
        .presets-title { font-size: 0.625rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; margin-bottom: 0.5rem; }
        .presets-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; }
        .preset-btn { padding: 0.625rem 0.5rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.6875rem; font-weight: 600; color: #64748b; cursor: pointer; transition: all 0.2s; text-align: center; }
        .preset-btn:hover { border-color: #8b5cf6; color: #8b5cf6; background: #faf5ff; }
        
        /* Panel Body */
        .panel-body { flex: 1; overflow-y: auto; padding: 1rem; background: #fff; }
        .settings-section { display: none; }
        .settings-section.active { display: block; }
        
        /* Setting Groups */
        .setting-group { margin-bottom: 1.25rem; }
        .setting-group-title { font-size: 0.625rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; margin-bottom: 0.625rem; padding-bottom: 0.375rem; border-bottom: 1px solid #f1f5f9; }
        
        /* Setting Rows */
        .setting-row { margin-bottom: 0.75rem; }
        .setting-row label { display: block; font-size: 0.6875rem; font-weight: 500; color: #64748b; margin-bottom: 0.25rem; }
        .setting-row input[type="text"],
        .setting-row input[type="number"],
        .setting-row select,
        .setting-row textarea { width: 100%; padding: 0.5rem 0.625rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; color: #1e293b; font-size: 0.8125rem; font-family: inherit; transition: all 0.15s; }
        .setting-row input:focus, .setting-row select:focus, .setting-row textarea:focus { outline: none; border-color: #8b5cf6; box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.1); background: #fff; }
        .setting-row textarea { min-height: 70px; resize: vertical; font-family: 'SF Mono', Monaco, monospace; font-size: 0.6875rem; }
        .setting-row input[type="color"] { width: 100%; height: 32px; padding: 2px; border: 1px solid #e2e8f0; border-radius: 6px; background: #f8fafc; cursor: pointer; }
        .setting-row-inline { display: grid; grid-template-columns: 1fr 1fr; gap: 0.625rem; }
        .setting-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.5rem; }
        
        /* Checkboxes */
        .setting-checkbox { display: flex; align-items: center; gap: 0.5rem; padding: 0.375rem 0; }
        .setting-checkbox input { width: 16px; height: 16px; accent-color: #8b5cf6; cursor: pointer; }
        .setting-checkbox label { font-size: 0.75rem; color: #475569; margin: 0; cursor: pointer; }
        
        /* Range */
        .range-row { display: flex; align-items: center; gap: 0.625rem; }
        .range-row input[type="range"] { flex: 1; accent-color: #8b5cf6; }
        .range-row span { min-width: 40px; font-size: 0.6875rem; color: #64748b; text-align: right; font-family: 'SF Mono', Monaco, monospace; }
        
        /* Color Input */
        .color-input-row { display: flex; gap: 0.375rem; }
        .color-input-row input[type="color"] { width: 40px; height: 32px; flex-shrink: 0; }
        .color-input-row input[type="text"] { flex: 1; }
        
        /* Footer */
        .panel-footer { padding: 0.875rem 1rem; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; gap: 0.625rem; }
        .btn-save { flex: 1; display: flex; align-items: center; justify-content: center; gap: 0.375rem; padding: 0.625rem 1rem; background: linear-gradient(135deg, #8b5cf6, #06b6d4); border: none; border-radius: 8px; color: #fff; font-size: 0.8125rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
        .btn-save:hover { transform: translateY(-1px); box-shadow: 0 4px 15px rgba(139, 92, 246, 0.35); }
        .btn-reset { padding: 0.625rem 0.875rem; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 8px; color: #64748b; font-size: 0.8125rem; font-weight: 500; cursor: pointer; transition: all 0.15s; }
        .btn-reset:hover { background: #fee2e2; border-color: #fca5a5; color: #dc2626; }
        
        /* Preview Panel */
        .preview-panel { flex: 1; background: #e2e8f0; display: flex; flex-direction: column; }
        .preview-header { padding: 0.625rem 1rem; background: #f1f5f9; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; }
        .preview-title { display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; font-weight: 600; color: #64748b; }
        .preview-title svg { width: 14px; height: 14px; color: #8b5cf6; }
        .preview-actions { display: flex; gap: 0.375rem; }
        .preview-btn { display: flex; align-items: center; gap: 0.25rem; padding: 0.375rem 0.625rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 5px; color: #64748b; font-size: 0.6875rem; font-weight: 500; cursor: pointer; transition: all 0.15s; text-decoration: none; }
        .preview-btn:hover { background: #f8fafc; color: #1e293b; border-color: #cbd5e1; }
        .preview-btn svg { width: 12px; height: 12px; }
        .preview-container { flex: 1; display: flex; align-items: center; justify-content: center; padding: 2rem; background: repeating-conic-gradient(#e2e8f0 0% 25%, #f1f5f9 0% 50%) 50% / 20px 20px; }
        .preview-frame { width: 100%; height: 100%; border: none; border-radius: 10px; box-shadow: 0 20px 40px rgba(0,0,0,0.12); background: #fff; }
        
        /* Toast */
        .toast { position: fixed; bottom: 1.5rem; left: 50%; transform: translateX(-50%) translateY(80px); padding: 0.75rem 1.25rem; background: #1e293b; border-radius: 8px; color: #fff; font-size: 0.8125rem; font-weight: 500; box-shadow: 0 10px 30px rgba(0,0,0,0.15); opacity: 0; transition: all 0.3s; z-index: 1000; }
        .toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }
        .toast.success { background: #059669; }
        .toast.error { background: #dc2626; }
        
        /* Scrollbar */
        .panel-body::-webkit-scrollbar { width: 5px; }
        .panel-body::-webkit-scrollbar-track { background: transparent; }
        .panel-body::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 3px; }
        .panel-body::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
        
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="editor-layout">
        <div class="editor-panel">
            <div class="panel-header">
                <div class="panel-brand">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <span>Login Editor</span>
                </div>
                <a href="<?= ADMIN_URL ?>/" class="panel-close">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                    Close
                </a>
            </div>
            
            <div class="panel-tabs">
                <button class="panel-tab active" data-tab="background">BG</button>
                <button class="panel-tab" data-tab="card">Card</button>
                <button class="panel-tab" data-tab="form">Form</button>
                <button class="panel-tab" data-tab="button">Button</button>
                <button class="panel-tab" data-tab="extras">More</button>
            </div>
            
            <div class="presets-section">
                <div class="presets-title">Quick Presets</div>
                <div class="presets-grid">
                    <button type="button" class="preset-btn" onclick="applyPreset('default')">Default</button>
                    <button type="button" class="preset-btn" onclick="applyPreset('aurora')">Aurora</button>
                    <button type="button" class="preset-btn" onclick="applyPreset('minimal')">Minimal</button>
                    <button type="button" class="preset-btn" onclick="applyPreset('ocean')">Ocean</button>
                    <button type="button" class="preset-btn" onclick="applyPreset('nature')">Nature</button>
                    <button type="button" class="preset-btn" onclick="applyPreset('rose')">Rose</button>
                    <button type="button" class="preset-btn" onclick="applyPreset('soft')">Soft</button>
                    <button type="button" class="preset-btn" onclick="applyPreset('corporate')">Corporate</button>
                    <button type="button" class="preset-btn" onclick="applyPreset('sunset')">Sunset</button>
                    <button type="button" class="preset-btn" onclick="applyPreset('lavender')">Lavender</button>
                    <button type="button" class="preset-btn" onclick="applyPreset('slate')">Slate</button>
                    <button type="button" class="preset-btn" onclick="applyPreset('fresh')">Fresh</button>
                </div>
            </div>
            
            <div class="panel-body">
                <!-- Background Section -->
                <div class="settings-section active" data-section="background">
                    <div class="setting-group">
                        <div class="setting-group-title">Background Type</div>
                        <div class="setting-row">
                            <select name="bg_type" id="bg_type" onchange="toggleBgOptions();updatePreview()">
                                <option value="color" <?= $settings['bg_type'] === 'color' ? 'selected' : '' ?>>Solid Color</option>
                                <option value="gradient" <?= $settings['bg_type'] === 'gradient' ? 'selected' : '' ?>>Gradient</option>
                                <option value="image" <?= $settings['bg_type'] === 'image' ? 'selected' : '' ?>>Image</option>
                                <option value="pattern" <?= $settings['bg_type'] === 'pattern' ? 'selected' : '' ?>>Pattern</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="setting-group" id="bgColorGroup">
                        <div class="setting-group-title">Base Color</div>
                        <div class="setting-row">
                            <div class="color-input-row">
                                <input type="color" name="bg_color" value="<?= esc($settings['bg_color']) ?>" onchange="this.nextElementSibling.value=this.value;updatePreview()">
                                <input type="text" value="<?= esc($settings['bg_color']) ?>" onchange="this.previousElementSibling.value=this.value;updatePreview()">
                            </div>
                        </div>
                    </div>
                    
                    <div class="setting-group" id="bgGradientGroup">
                        <div class="setting-group-title">Gradient</div>
                        <div class="setting-row-inline">
                            <div class="setting-row">
                                <label>Start</label>
                                <input type="color" name="bg_gradient_start" value="<?= esc($settings['bg_gradient_start']) ?>" onchange="updatePreview()">
                            </div>
                            <div class="setting-row">
                                <label>End</label>
                                <input type="color" name="bg_gradient_end" value="<?= esc($settings['bg_gradient_end']) ?>" onchange="updatePreview()">
                            </div>
                        </div>
                        <div class="setting-row">
                            <label>Angle</label>
                            <div class="range-row">
                                <input type="range" name="bg_gradient_angle" min="0" max="360" value="<?= (int)$settings['bg_gradient_angle'] ?>" oninput="this.nextElementSibling.textContent=this.value+'°';updatePreview()">
                                <span><?= (int)$settings['bg_gradient_angle'] ?>°</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="setting-group" id="bgImageGroup" style="display:none;">
                        <div class="setting-group-title">Image</div>
                        <div class="setting-row">
                            <label>Image URL</label>
                            <input type="text" name="bg_image" value="<?= esc($settings['bg_image']) ?>" placeholder="https://..." onchange="updatePreview()">
                        </div>
                    </div>
                    
                    <div class="setting-group" id="bgPatternGroup" style="display:none;">
                        <div class="setting-group-title">Pattern</div>
                        <div class="setting-row">
                            <label>Pattern Type</label>
                            <select name="bg_pattern" onchange="updatePreview()">
                                <option value="dots" <?= $settings['bg_pattern'] === 'dots' ? 'selected' : '' ?>>Dots</option>
                                <option value="grid" <?= $settings['bg_pattern'] === 'grid' ? 'selected' : '' ?>>Grid</option>
                                <option value="diagonal" <?= $settings['bg_pattern'] === 'diagonal' ? 'selected' : '' ?>>Diagonal</option>
                                <option value="crosses" <?= $settings['bg_pattern'] === 'crosses' ? 'selected' : '' ?>>Crosses</option>
                                <option value="waves" <?= $settings['bg_pattern'] === 'waves' ? 'selected' : '' ?>>Waves</option>
                            </select>
                        </div>
                        <div class="setting-row">
                            <label>Pattern Color</label>
                            <input type="text" name="bg_pattern_color" value="<?= esc($settings['bg_pattern_color']) ?>" placeholder="rgba(0,0,0,0.08)" onchange="updatePreview()">
                        </div>
                        <div class="setting-row">
                            <label>Pattern Size</label>
                            <div class="range-row">
                                <input type="range" name="bg_pattern_size" min="10" max="50" value="<?= (int)$settings['bg_pattern_size'] ?>" oninput="this.nextElementSibling.textContent=this.value+'px';updatePreview()">
                                <span><?= (int)$settings['bg_pattern_size'] ?>px</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="setting-group">
                        <div class="setting-group-title">Overlay</div>
                        <div class="setting-checkbox">
                            <input type="checkbox" name="bg_overlay" id="bg_overlay" value="1" <?= $settings['bg_overlay'] ? 'checked' : '' ?> onchange="updatePreview()">
                            <label for="bg_overlay">Enable dark overlay</label>
                        </div>
                        <div class="setting-row">
                            <label>Overlay Color</label>
                            <input type="text" name="bg_overlay_color" value="<?= esc($settings['bg_overlay_color']) ?>" placeholder="rgba(0,0,0,0.5)" onchange="updatePreview()">
                        </div>
                    </div>
                    
                    <div class="setting-group">
                        <div class="setting-group-title">Logo</div>
                        <div class="setting-row">
                            <select name="logo_type" id="logo_type" onchange="toggleLogoOptions();updatePreview()">
                                <option value="default" <?= $settings['logo_type'] === 'default' ? 'selected' : '' ?>>VoidForge Logo</option>
                                <option value="custom" <?= $settings['logo_type'] === 'custom' ? 'selected' : '' ?>>Custom Image</option>
                                <option value="none" <?= $settings['logo_type'] === 'none' ? 'selected' : '' ?>>No Logo</option>
                            </select>
                        </div>
                        <div id="logoCustomGroup" style="display: <?= $settings['logo_type'] === 'custom' ? 'block' : 'none' ?>;">
                            <div class="setting-row">
                                <label>Logo URL</label>
                                <input type="text" name="logo_image" value="<?= esc($settings['logo_image']) ?>" placeholder="https://..." onchange="updatePreview()">
                            </div>
                            <div class="setting-row">
                                <label>Logo Width</label>
                                <div class="range-row">
                                    <input type="range" name="logo_width" min="60" max="300" value="<?= (int)$settings['logo_width'] ?>" oninput="this.nextElementSibling.textContent=this.value+'px';updatePreview()">
                                    <span><?= (int)$settings['logo_width'] ?>px</span>
                                </div>
                            </div>
                        </div>
                        <div class="setting-row">
                            <label>Logo Bottom Margin</label>
                            <div class="range-row">
                                <input type="range" name="logo_margin_bottom" min="0" max="60" value="<?= (int)$settings['logo_margin_bottom'] ?>" oninput="this.nextElementSibling.textContent=this.value+'px';updatePreview()">
                                <span><?= (int)$settings['logo_margin_bottom'] ?>px</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Card Section -->
                <div class="settings-section" data-section="card">
                    <div class="setting-group">
                        <div class="setting-group-title">Card Colors</div>
                        <div class="setting-row">
                            <label>Background</label>
                            <input type="text" name="card_bg" value="<?= esc($settings['card_bg']) ?>" placeholder="rgba or hex" onchange="updatePreview()">
                        </div>
                        <div class="setting-row-inline">
                            <div class="setting-row">
                                <label>Border Color</label>
                                <input type="text" name="card_border" value="<?= esc($settings['card_border']) ?>" onchange="updatePreview()">
                            </div>
                            <div class="setting-row">
                                <label>Border Width</label>
                                <input type="number" name="card_border_width" value="<?= (int)$settings['card_border_width'] ?>" min="0" max="5" onchange="updatePreview()">
                            </div>
                        </div>
                    </div>
                    
                    <div class="setting-group">
                        <div class="setting-group-title">Dimensions</div>
                        <div class="setting-row-inline">
                            <div class="setting-row">
                                <label>Width</label>
                                <input type="number" name="card_width" value="<?= (int)$settings['card_width'] ?>" min="300" max="600" onchange="updatePreview()">
                            </div>
                            <div class="setting-row">
                                <label>Padding</label>
                                <input type="number" name="card_padding" value="<?= (int)$settings['card_padding'] ?>" min="20" max="80" onchange="updatePreview()">
                            </div>
                        </div>
                        <div class="setting-row">
                            <label>Border Radius</label>
                            <div class="range-row">
                                <input type="range" name="card_radius" min="0" max="40" value="<?= (int)$settings['card_radius'] ?>" oninput="this.nextElementSibling.textContent=this.value+'px';updatePreview()">
                                <span><?= (int)$settings['card_radius'] ?>px</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="setting-group">
                        <div class="setting-group-title">Effects</div>
                        <div class="setting-checkbox">
                            <input type="checkbox" name="card_shadow" id="card_shadow" value="1" <?= $settings['card_shadow'] ? 'checked' : '' ?> onchange="updatePreview()">
                            <label for="card_shadow">Drop shadow</label>
                        </div>
                        <div class="setting-row">
                            <label>Shadow Color</label>
                            <input type="text" name="card_shadow_color" value="<?= esc($settings['card_shadow_color']) ?>" placeholder="rgba(0,0,0,0.15)" onchange="updatePreview()">
                        </div>
                        <div class="setting-row">
                            <label>Backdrop Blur</label>
                            <div class="range-row">
                                <input type="range" name="card_backdrop_blur" min="0" max="30" value="<?= (int)$settings['card_backdrop_blur'] ?>" oninput="this.nextElementSibling.textContent=this.value+'px';updatePreview()">
                                <span><?= (int)$settings['card_backdrop_blur'] ?>px</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Form Section -->
                <div class="settings-section" data-section="form">
                    <div class="setting-group">
                        <div class="setting-group-title">Title</div>
                        <div class="setting-row">
                            <label>Title Text</label>
                            <input type="text" name="title_text" value="<?= esc($settings['title_text']) ?>" onchange="updatePreview()">
                        </div>
                        <div class="setting-row-3">
                            <div class="setting-row">
                                <label>Color</label>
                                <input type="color" name="title_color" value="<?= esc($settings['title_color']) ?>" onchange="updatePreview()">
                            </div>
                            <div class="setting-row">
                                <label>Size</label>
                                <input type="number" name="title_size" value="<?= (int)$settings['title_size'] ?>" min="18" max="48" onchange="updatePreview()">
                            </div>
                            <div class="setting-row">
                                <label>Weight</label>
                                <select name="title_weight" onchange="updatePreview()">
                                    <option value="400" <?= $settings['title_weight'] == 400 ? 'selected' : '' ?>>Normal</option>
                                    <option value="500" <?= $settings['title_weight'] == 500 ? 'selected' : '' ?>>Medium</option>
                                    <option value="600" <?= $settings['title_weight'] == 600 ? 'selected' : '' ?>>Semi</option>
                                    <option value="700" <?= $settings['title_weight'] == 700 ? 'selected' : '' ?>>Bold</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="setting-group">
                        <div class="setting-group-title">Subtitle</div>
                        <div class="setting-row">
                            <label>Subtitle Text</label>
                            <input type="text" name="subtitle_text" value="<?= esc($settings['subtitle_text']) ?>" onchange="updatePreview()">
                        </div>
                        <div class="setting-row-inline">
                            <div class="setting-row">
                                <label>Color</label>
                                <input type="text" name="subtitle_color" value="<?= esc($settings['subtitle_color']) ?>" onchange="updatePreview()">
                            </div>
                            <div class="setting-row">
                                <label>Size</label>
                                <input type="number" name="subtitle_size" value="<?= (int)$settings['subtitle_size'] ?>" min="10" max="24" onchange="updatePreview()">
                            </div>
                        </div>
                    </div>
                    
                    <div class="setting-group">
                        <div class="setting-group-title">Labels</div>
                        <div class="setting-row-inline">
                            <div class="setting-row">
                                <label>Label Color</label>
                                <input type="text" name="label_color" value="<?= esc($settings['label_color']) ?>" onchange="updatePreview()">
                            </div>
                            <div class="setting-row">
                                <label>Label Size</label>
                                <input type="number" name="label_size" value="<?= (int)$settings['label_size'] ?>" min="10" max="18" onchange="updatePreview()">
                            </div>
                        </div>
                    </div>
                    
                    <div class="setting-group">
                        <div class="setting-group-title">Input Fields</div>
                        <div class="setting-row-inline">
                            <div class="setting-row">
                                <label>Background</label>
                                <input type="text" name="input_bg" value="<?= esc($settings['input_bg']) ?>" onchange="updatePreview()">
                            </div>
                            <div class="setting-row">
                                <label>Border</label>
                                <input type="text" name="input_border" value="<?= esc($settings['input_border']) ?>" onchange="updatePreview()">
                            </div>
                        </div>
                        <div class="setting-row-inline">
                            <div class="setting-row">
                                <label>Text Color</label>
                                <input type="color" name="input_text" value="<?= esc($settings['input_text']) ?>" onchange="updatePreview()">
                            </div>
                            <div class="setting-row">
                                <label>Placeholder</label>
                                <input type="text" name="input_placeholder" value="<?= esc($settings['input_placeholder']) ?>" onchange="updatePreview()">
                            </div>
                        </div>
                        <div class="setting-row-inline">
                            <div class="setting-row">
                                <label>Radius</label>
                                <input type="number" name="input_radius" value="<?= (int)$settings['input_radius'] ?>" min="0" max="20" onchange="updatePreview()">
                            </div>
                            <div class="setting-row">
                                <label>Padding</label>
                                <input type="number" name="input_padding" value="<?= (int)$settings['input_padding'] ?>" min="6" max="20" onchange="updatePreview()">
                            </div>
                        </div>
                        <div class="setting-row">
                            <label>Focus Color</label>
                            <input type="color" name="input_focus_color" value="<?= esc($settings['input_focus_color']) ?>" onchange="updatePreview()">
                        </div>
                    </div>
                </div>
                
                <!-- Button Section -->
                <div class="settings-section" data-section="button">
                    <div class="setting-group">
                        <div class="setting-group-title">Button Style</div>
                        <div class="setting-row">
                            <label>Button Label</label>
                            <input type="text" name="button_label" value="<?= esc($settings['button_label']) ?>" onchange="updatePreview()">
                        </div>
                        <div class="setting-row">
                            <label>Background Type</label>
                            <select name="button_bg_type" id="button_bg_type" onchange="toggleButtonOptions();updatePreview()">
                                <option value="gradient" <?= $settings['button_bg_type'] === 'gradient' ? 'selected' : '' ?>>Gradient</option>
                                <option value="solid" <?= $settings['button_bg_type'] === 'solid' ? 'selected' : '' ?>>Solid</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="setting-group" id="buttonGradientGroup">
                        <div class="setting-group-title">Gradient</div>
                        <div class="setting-row-inline">
                            <div class="setting-row">
                                <label>Start</label>
                                <input type="color" name="button_gradient_start" value="<?= esc($settings['button_gradient_start']) ?>" onchange="updatePreview()">
                            </div>
                            <div class="setting-row">
                                <label>End</label>
                                <input type="color" name="button_gradient_end" value="<?= esc($settings['button_gradient_end']) ?>" onchange="updatePreview()">
                            </div>
                        </div>
                        <div class="setting-row">
                            <label>Angle</label>
                            <div class="range-row">
                                <input type="range" name="button_gradient_angle" min="0" max="360" value="<?= (int)$settings['button_gradient_angle'] ?>" oninput="this.nextElementSibling.textContent=this.value+'°';updatePreview()">
                                <span><?= (int)$settings['button_gradient_angle'] ?>°</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="setting-group" id="buttonSolidGroup" style="display:none;">
                        <div class="setting-group-title">Solid Color</div>
                        <div class="setting-row">
                            <label>Button Color</label>
                            <input type="color" name="button_solid_color" value="<?= esc($settings['button_solid_color']) ?>" onchange="updatePreview()">
                        </div>
                    </div>
                    
                    <div class="setting-group">
                        <div class="setting-group-title">Text</div>
                        <div class="setting-row">
                            <label>Text Color</label>
                            <input type="color" name="button_text" value="<?= esc($settings['button_text']) ?>" onchange="updatePreview()">
                        </div>
                    </div>
                    
                    <div class="setting-group">
                        <div class="setting-group-title">Shape</div>
                        <div class="setting-row-inline">
                            <div class="setting-row">
                                <label>Radius</label>
                                <input type="number" name="button_radius" value="<?= (int)$settings['button_radius'] ?>" min="0" max="30" onchange="updatePreview()">
                            </div>
                            <div class="setting-row">
                                <label>Padding</label>
                                <input type="number" name="button_padding" value="<?= (int)$settings['button_padding'] ?>" min="8" max="24" onchange="updatePreview()">
                            </div>
                        </div>
                        <div class="setting-checkbox">
                            <input type="checkbox" name="button_full_width" id="button_full_width" value="1" <?= $settings['button_full_width'] ? 'checked' : '' ?> onchange="updatePreview()">
                            <label for="button_full_width">Full width button</label>
                        </div>
                        <div class="setting-checkbox">
                            <input type="checkbox" name="button_shadow" id="button_shadow" value="1" <?= $settings['button_shadow'] ? 'checked' : '' ?> onchange="updatePreview()">
                            <label for="button_shadow">Glow shadow</label>
                        </div>
                    </div>
                </div>
                
                <!-- Extras Section -->
                <div class="settings-section" data-section="extras">
                    <div class="setting-group">
                        <div class="setting-group-title">Form Options</div>
                        <div class="setting-checkbox">
                            <input type="checkbox" name="show_remember" id="show_remember" value="1" <?= $settings['show_remember'] ? 'checked' : '' ?> onchange="updatePreview()">
                            <label for="show_remember">Show "Remember me"</label>
                        </div>
                        <div class="setting-row">
                            <label>Remember Label</label>
                            <input type="text" name="remember_label" value="<?= esc($settings['remember_label']) ?>" onchange="updatePreview()">
                        </div>
                        <div class="setting-checkbox">
                            <input type="checkbox" name="show_forgot" id="show_forgot" value="1" <?= $settings['show_forgot'] ? 'checked' : '' ?> onchange="updatePreview()">
                            <label for="show_forgot">Show "Forgot password?"</label>
                        </div>
                        <div class="setting-row">
                            <label>Forgot Label</label>
                            <input type="text" name="forgot_label" value="<?= esc($settings['forgot_label']) ?>" onchange="updatePreview()">
                        </div>
                        <div class="setting-checkbox">
                            <input type="checkbox" name="show_register" id="show_register" value="1" <?= $settings['show_register'] ? 'checked' : '' ?> onchange="updatePreview()">
                            <label for="show_register">Show "Sign up" link</label>
                        </div>
                        <div class="setting-row">
                            <label>Register Text</label>
                            <input type="text" name="register_text" value="<?= esc($settings['register_text']) ?>" onchange="updatePreview()">
                        </div>
                        <div class="setting-row">
                            <label>Register Label</label>
                            <input type="text" name="register_label" value="<?= esc($settings['register_label']) ?>" onchange="updatePreview()">
                        </div>
                    </div>
                    
                    <div class="setting-group">
                        <div class="setting-group-title">Animation</div>
                        <div class="setting-row-inline">
                            <div class="setting-row">
                                <label>Type</label>
                                <select name="animation_type" onchange="updatePreview()">
                                    <option value="none" <?= $settings['animation_type'] === 'none' ? 'selected' : '' ?>>None</option>
                                    <option value="fade" <?= $settings['animation_type'] === 'fade' ? 'selected' : '' ?>>Fade</option>
                                    <option value="slide" <?= $settings['animation_type'] === 'slide' ? 'selected' : '' ?>>Slide Up</option>
                                    <option value="scale" <?= $settings['animation_type'] === 'scale' ? 'selected' : '' ?>>Scale</option>
                                    <option value="bounce" <?= $settings['animation_type'] === 'bounce' ? 'selected' : '' ?>>Bounce</option>
                                </select>
                            </div>
                            <div class="setting-row">
                                <label>Duration (ms)</label>
                                <input type="number" name="animation_duration" value="<?= (int)$settings['animation_duration'] ?>" min="200" max="1500" step="100" onchange="updatePreview()">
                            </div>
                        </div>
                    </div>
                    
                    <div class="setting-group">
                        <div class="setting-group-title">Footer</div>
                        <div class="setting-row">
                            <label>Footer Text (HTML allowed)</label>
                            <input type="text" name="footer_text" value="<?= esc($settings['footer_text']) ?>" placeholder="Powered by..." onchange="updatePreview()">
                        </div>
                    </div>
                    
                    <div class="setting-group">
                        <div class="setting-group-title">Custom CSS</div>
                        <div class="setting-row">
                            <textarea name="custom_css" placeholder="/* Your custom styles */"><?= esc($settings['custom_css']) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="panel-footer">
                <button type="button" class="btn-save" onclick="saveSettings()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
                    Publish
                </button>
                <button type="button" class="btn-reset" onclick="resetSettings()">Reset</button>
            </div>
        </div>
        
        <div class="preview-panel">
            <div class="preview-header">
                <div class="preview-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    Live Preview
                </div>
                <div class="preview-actions">
                    <button class="preview-btn" onclick="refreshPreview()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                        Refresh
                    </button>
                    <a href="<?= ADMIN_URL ?>/login.php" target="_blank" class="preview-btn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        Open
                    </a>
                </div>
            </div>
            <div class="preview-container">
                <iframe id="previewFrame" class="preview-frame" src="?preview=1"></iframe>
            </div>
        </div>
    </div>
    
    <div class="toast" id="toast"></div>
    
    <script>
    var csrfToken = '<?= csrfToken() ?>';
    var previewTimeout = null;
    
    var presets = {
        default: {
            bg_type: 'gradient', bg_gradient_start: '#f8fafc', bg_gradient_end: '#e2e8f0', bg_gradient_angle: 180,
            bg_overlay: false, card_bg: '#ffffff', card_border: '#e2e8f0', card_backdrop_blur: 0, card_shadow: true,
            title_color: '#1e293b', subtitle_color: '#64748b', label_color: '#475569',
            input_bg: '#f8fafc', input_border: '#e2e8f0', input_text: '#1e293b', input_focus_color: '#8b5cf6',
            button_bg_type: 'gradient', button_gradient_start: '#8b5cf6', button_gradient_end: '#06b6d4', button_text: '#ffffff'
        },
        aurora: {
            bg_type: 'gradient', bg_gradient_start: '#e0e7ff', bg_gradient_end: '#fae8ff', bg_gradient_angle: 135,
            card_bg: '#ffffff', card_border: '#e2e8f0', title_color: '#1e293b',
            button_gradient_start: '#8b5cf6', button_gradient_end: '#ec4899'
        },
        minimal: {
            bg_type: 'color', bg_color: '#ffffff', card_bg: '#ffffff', card_border: '#f1f5f9', card_shadow: true,
            title_color: '#1e293b', input_bg: '#f8fafc', input_border: '#e2e8f0', input_focus_color: '#6366f1',
            button_bg_type: 'solid', button_solid_color: '#6366f1', button_text: '#ffffff'
        },
        ocean: {
            bg_type: 'gradient', bg_gradient_start: '#e0f2fe', bg_gradient_end: '#cffafe', bg_gradient_angle: 135,
            card_bg: '#ffffff', card_border: '#bae6fd', title_color: '#0c4a6e', subtitle_color: '#0369a1',
            label_color: '#0284c7', input_bg: '#f0f9ff', input_border: '#bae6fd', input_text: '#0c4a6e',
            input_focus_color: '#0ea5e9', button_gradient_start: '#0ea5e9', button_gradient_end: '#06b6d4'
        },
        nature: {
            bg_type: 'gradient', bg_gradient_start: '#dcfce7', bg_gradient_end: '#d1fae5', bg_gradient_angle: 135,
            card_bg: '#ffffff', card_border: '#bbf7d0', title_color: '#14532d', subtitle_color: '#166534',
            label_color: '#15803d', input_bg: '#f0fdf4', input_border: '#bbf7d0', input_text: '#14532d',
            input_focus_color: '#22c55e', button_gradient_start: '#22c55e', button_gradient_end: '#10b981'
        },
        rose: {
            bg_type: 'gradient', bg_gradient_start: '#ffe4e6', bg_gradient_end: '#fce7f3', bg_gradient_angle: 135,
            card_bg: '#ffffff', card_border: '#fecdd3', title_color: '#881337', subtitle_color: '#9f1239',
            label_color: '#be123c', input_bg: '#fff1f2', input_border: '#fecdd3', input_text: '#881337',
            input_focus_color: '#f43f5e', button_gradient_start: '#f43f5e', button_gradient_end: '#ec4899'
        },
        soft: {
            bg_type: 'gradient', bg_gradient_start: '#fef3c7', bg_gradient_end: '#fce7f3', bg_gradient_angle: 135,
            card_bg: '#ffffff', card_border: '#fde68a', title_color: '#78350f', subtitle_color: '#92400e',
            label_color: '#a16207', input_bg: '#fffbeb', input_border: '#fde68a', input_text: '#78350f',
            input_focus_color: '#f59e0b', button_bg_type: 'solid', button_solid_color: '#f59e0b', button_text: '#ffffff'
        },
        corporate: {
            bg_type: 'gradient', bg_gradient_start: '#f1f5f9', bg_gradient_end: '#e2e8f0', bg_gradient_angle: 180,
            card_bg: '#ffffff', card_border: '#cbd5e1', title_color: '#1e293b', subtitle_color: '#64748b',
            label_color: '#475569', input_bg: '#f8fafc', input_border: '#cbd5e1', input_text: '#1e293b',
            input_focus_color: '#2563eb', button_bg_type: 'solid', button_solid_color: '#2563eb', button_text: '#ffffff'
        },
        sunset: {
            bg_type: 'gradient', bg_gradient_start: '#fef3c7', bg_gradient_end: '#fed7aa', bg_gradient_angle: 135,
            card_bg: '#ffffff', card_border: '#fdba74', title_color: '#7c2d12', subtitle_color: '#9a3412',
            label_color: '#c2410c', input_bg: '#fff7ed', input_border: '#fed7aa', input_text: '#7c2d12',
            input_focus_color: '#f97316', button_gradient_start: '#f97316', button_gradient_end: '#ef4444'
        },
        lavender: {
            bg_type: 'gradient', bg_gradient_start: '#ede9fe', bg_gradient_end: '#e0e7ff', bg_gradient_angle: 135,
            card_bg: '#ffffff', card_border: '#c4b5fd', title_color: '#4c1d95', subtitle_color: '#5b21b6',
            label_color: '#6d28d9', input_bg: '#f5f3ff', input_border: '#c4b5fd', input_text: '#4c1d95',
            input_focus_color: '#8b5cf6', button_gradient_start: '#8b5cf6', button_gradient_end: '#a78bfa'
        },
        slate: {
            bg_type: 'color', bg_color: '#f1f5f9', card_bg: '#ffffff', card_border: '#e2e8f0',
            title_color: '#334155', subtitle_color: '#64748b', label_color: '#475569',
            input_bg: '#f8fafc', input_border: '#e2e8f0', input_text: '#334155', input_focus_color: '#475569',
            button_bg_type: 'solid', button_solid_color: '#334155', button_text: '#ffffff'
        },
        fresh: {
            bg_type: 'gradient', bg_gradient_start: '#ecfdf5', bg_gradient_end: '#f0fdfa', bg_gradient_angle: 135,
            card_bg: '#ffffff', card_border: '#a7f3d0', title_color: '#065f46', subtitle_color: '#047857',
            label_color: '#059669', input_bg: '#ecfdf5', input_border: '#a7f3d0', input_text: '#065f46',
            input_focus_color: '#10b981', button_gradient_start: '#10b981', button_gradient_end: '#14b8a6'
        }
    };
    
    // Tab switching
    document.querySelectorAll('.panel-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.panel-tab').forEach(function(t) { t.classList.remove('active'); });
            document.querySelectorAll('.settings-section').forEach(function(s) { s.classList.remove('active'); });
            this.classList.add('active');
            document.querySelector('.settings-section[data-section="' + this.dataset.tab + '"]').classList.add('active');
        });
    });
    
    function toggleBgOptions() {
        var type = document.getElementById('bg_type').value;
        document.getElementById('bgColorGroup').style.display = (type === 'color' || type === 'pattern') ? 'block' : 'none';
        document.getElementById('bgGradientGroup').style.display = type === 'gradient' ? 'block' : 'none';
        document.getElementById('bgImageGroup').style.display = type === 'image' ? 'block' : 'none';
        document.getElementById('bgPatternGroup').style.display = type === 'pattern' ? 'block' : 'none';
    }
    
    function toggleLogoOptions() {
        var type = document.getElementById('logo_type').value;
        document.getElementById('logoCustomGroup').style.display = type === 'custom' ? 'block' : 'none';
    }
    
    function toggleButtonOptions() {
        var type = document.getElementById('button_bg_type').value;
        document.getElementById('buttonSolidGroup').style.display = type === 'solid' ? 'block' : 'none';
        document.getElementById('buttonGradientGroup').style.display = type === 'gradient' ? 'block' : 'none';
    }
    
    // Initialize toggles
    toggleBgOptions();
    toggleLogoOptions();
    toggleButtonOptions();
    
    function updatePreview() {
        clearTimeout(previewTimeout);
        previewTimeout = setTimeout(function() {
            var settings = {};
            document.querySelectorAll('[name]').forEach(function(el) {
                settings[el.name] = el.type === 'checkbox' ? el.checked : el.value;
            });
            document.getElementById('previewFrame').src = '?preview=1&settings=' + btoa(JSON.stringify(settings)) + '&t=' + Date.now();
        }, 300);
    }
    
    function refreshPreview() {
        updatePreview();
    }
    
    function applyPreset(name) {
        var preset = presets[name];
        if (!preset) return;
        for (var key in preset) {
            var el = document.querySelector('[name="' + key + '"]');
            if (el) {
                if (el.type === 'checkbox') {
                    el.checked = preset[key];
                } else {
                    el.value = preset[key];
                }
            }
        }
        toggleBgOptions();
        toggleLogoOptions();
        toggleButtonOptions();
        updatePreview();
    }
    
    async function saveSettings() {
        var btn = document.querySelector('.btn-save');
        btn.disabled = true;
        btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;"><circle cx="12" cy="12" r="10"/></svg> Saving...';
        
        var formData = new FormData();
        formData.append('ajax', '1');
        formData.append('csrf', csrfToken);
        
        document.querySelectorAll('[name]').forEach(function(el) {
            formData.append(el.name, el.type === 'checkbox' ? (el.checked ? '1' : '0') : el.value);
        });
        
        try {
            var response = await fetch(window.location.href, { method: 'POST', body: formData });
            var result = await response.json();
            if (result.success) {
                showToast('Changes published!', 'success');
            } else {
                showToast('Failed: ' + (result.error || 'Unknown error'), 'error');
            }
        } catch (e) {
            showToast('Failed to save changes', 'error');
        }
        
        btn.disabled = false;
        btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg> Publish';
    }
    
    function resetSettings() {
        if (confirm('Reset to default settings?')) {
            applyPreset('default');
        }
    }
    
    function showToast(message, type) {
        var toast = document.getElementById('toast');
        toast.textContent = message;
        toast.className = 'toast ' + type + ' show';
        setTimeout(function() { toast.classList.remove('show'); }, 3000);
    }
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            saveSettings();
        }
    });
    </script>
</body>
</html>
