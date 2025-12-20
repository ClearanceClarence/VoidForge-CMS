<?php
/**
 * Anvil Live - Frontend Visual Editor
 * 
 * A visual frontend page builder similar to Elementor/Thrive Architect.
 * Allows users to edit pages directly on the frontend with drag-and-drop,
 * inline editing, and real-time preview.
 * 
 * @package VoidForge
 * @subpackage AnvilLive
 * @version 1.0.0
 * @since 0.2.2
 */

defined('CMS_ROOT') or die('Direct access not allowed');

class AnvilLive
{
    /** @var bool Whether Anvil Live has been initialized */
    private static bool $initialized = false;
    
    /** @var bool Whether we're in editor mode */
    private static bool $editorMode = false;
    
    /** @var array|null Current post being edited */
    private static ?array $currentPost = null;
    
    /** @var string Editor version for cache busting */
    public const VERSION = '1.1.0';
    
    /**
     * Initialize Anvil Live
     */
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }
        
        // Register hooks - use VoidForge's vf_head/vf_footer hooks
        if (class_exists('Plugin')) {
            Plugin::addAction('vf_head', [self::class, 'enqueueAssets'], 99);
            Plugin::addAction('vf_footer', [self::class, 'renderEditorUI'], 99);
            Plugin::addAction('vf_footer', [self::class, 'maybeRenderEditBar'], 100);
            // Hook into the_content filter to wrap content for editing
            Plugin::addFilter('the_content', [self::class, 'filterContent'], 9999, 2);
        }
        
        // Register REST endpoints for editor
        self::registerEndpoints();
        
        self::$initialized = true;
    }
    
    /**
     * Filter the content for Anvil Live editing
     */
    public static function filterContent(string $content, ?array $post = null): string
    {
        if (!self::$editorMode || !self::$currentPost) {
            return $content;
        }
        
        return self::wrapContent($content);
    }
    
    /**
     * Check if we should enter editor mode
     * Called from loadTemplate in index.php
     */
    public static function checkEditorMode(string $template = '', array $data = []): void
    {
        // Check for anvil-live query parameter
        if (!isset($_GET['anvil-live']) || $_GET['anvil-live'] !== 'edit') {
            return;
        }
        
        // Must be logged in
        $user = User::current();
        if (!$user) {
            // Redirect to login
            header('Location: ' . SITE_URL . '/admin/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
        
        // Must have edit capability
        if (!in_array($user['role'], ['admin', 'editor', 'author'])) {
            return;
        }
        
        // Get current post from the data array
        $post = $data['post'] ?? null;
        
        if (!$post || empty($post['id'])) {
            return;
        }
        
        // Check edit permission for this specific post
        if ($user['role'] === 'author' && ($post['author_id'] ?? 0) !== $user['id']) {
            return;
        }
        
        // Check if post type is available for Anvil Live
        $postType = $post['post_type'] ?? 'post';
        if (!self::isAvailable($postType)) {
            return;
        }
        
        // Enable editor mode
        self::$editorMode = true;
        self::$currentPost = $post;
        
        // Add body class via filter
        if (class_exists('Plugin')) {
            Plugin::addFilter('body_class', function($classes) {
                $classes[] = 'anvil-live-editing';
                $classes[] = 'anvil-live-active';
                return $classes;
            });
        }
    }
    
    /**
     * Check if editor mode is active
     */
    public static function isEditorMode(): bool
    {
        return self::$editorMode;
    }
    
    /**
     * Get current post being edited
     */
    public static function getCurrentPost(): ?array
    {
        return self::$currentPost;
    }
    
    /**
     * Check if currently in editor mode
     */
    public static function isEditing(): bool
    {
        return self::$editorMode;
    }
    
    /**
     * Enqueue editor assets
     */
    public static function enqueueAssets(): void
    {
        if (!self::$editorMode) {
            return;
        }
        
        $baseUrl = SITE_URL . '/includes/anvil-live/assets';
        
        // Output editor styles
        echo '<link rel="stylesheet" href="' . esc($baseUrl) . '/css/anvil-live.css?v=' . self::VERSION . '">' . "\n";
        
        // Load SortableJS for drag and drop
        echo '<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>' . "\n";
        
        // Get page settings from post meta
        $pageSettings = self::getPageSettings(self::$currentPost['id']);
        
        // Editor configuration
        echo '<script>
        window.AnvilLiveConfig = ' . json_encode([
            'postId' => self::$currentPost['id'],
            'postType' => self::$currentPost['post_type'],
            'postTitle' => self::$currentPost['title'],
            'nonce' => csrfToken(),
            'apiUrl' => SITE_URL . '/api/v1',
            'siteUrl' => SITE_URL,
            'adminUrl' => SITE_URL . '/admin',
            'blocks' => Anvil::getEditorData(),
            'exitUrl' => Post::permalink(self::$currentPost),
            'editUrl' => SITE_URL . '/admin/post-edit.php?id=' . self::$currentPost['id'],
            'pageSettings' => $pageSettings,
        ]) . ';
        </script>' . "\n";
    }
    
    /**
     * Get page settings for a post
     */
    public static function getPageSettings(int $postId): array
    {
        $defaults = [
            'contentWidth' => '1200',
            'contentWidthUnit' => 'px',
            'contentWidthFull' => false,
            'paddingTop' => '0',
            'paddingRight' => '0',
            'paddingBottom' => '0',
            'paddingLeft' => '0',
            'paddingUnit' => 'px',
            'marginTop' => '0',
            'marginRight' => 'auto',
            'marginBottom' => '0',
            'marginLeft' => 'auto',
            'marginUnit' => 'px',
        ];
        
        $saved = Post::getMeta($postId, 'anvil_page_settings');
        if ($saved && is_array($saved)) {
            return array_merge($defaults, $saved);
        }
        
        return $defaults;
    }
    
    /**
     * Save page settings for a post
     */
    public static function savePageSettings(int $postId, array $settings): bool
    {
        $allowedKeys = [
            'contentWidth', 'contentWidthUnit', 'contentWidthFull',
            'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft', 'paddingUnit',
            'marginTop', 'marginRight', 'marginBottom', 'marginLeft', 'marginUnit',
        ];
        
        $filtered = [];
        foreach ($allowedKeys as $key) {
            if (isset($settings[$key])) {
                $filtered[$key] = $settings[$key];
            }
        }
        
        try {
            Post::setMeta($postId, 'anvil_page_settings', $filtered);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Wrap content for editing
     */
    public static function wrapContent(string $content): string
    {
        if (!self::$editorMode || !self::$currentPost) {
            return $content;
        }
        
        // Get blocks data from post content
        $blocksJson = self::$currentPost['content'] ?? '';
        $blocks = [];
        
        // Check if content is JSON (block editor format)
        if (!empty($blocksJson) && is_string($blocksJson)) {
            $trimmed = trim($blocksJson);
            if (!empty($trimmed) && ($trimmed[0] === '[' || $trimmed[0] === '{')) {
                $decoded = json_decode($blocksJson, true);
                if (is_array($decoded)) {
                    $blocks = $decoded;
                }
            }
        }
        
        // If no blocks and we have HTML content, convert to paragraph block
        if (empty($blocks) && !empty($content)) {
            // Check if Anvil has htmlToBlocks method
            if (method_exists('Anvil', 'htmlToBlocks')) {
                $blocks = Anvil::htmlToBlocks($content);
            } else {
                // Fallback: wrap content in a paragraph block
                $blocks = [[
                    'id' => Anvil::generateBlockId(),
                    'type' => 'paragraph',
                    'attributes' => ['content' => $content]
                ]];
            }
        }
        
        // Get page settings and generate inline styles
        $pageSettings = self::getPageSettings(self::$currentPost['id']);
        $blocksStyle = self::generateCanvasStyle($pageSettings);
        $fullWidthClass = !empty($pageSettings['contentWidthFull']) ? ' anvil-live-full-width' : '';
        
        // Build editable content area
        $output = '<div id="anvil-live-canvas" class="anvil-live-canvas" data-post-id="' . esc((string)self::$currentPost['id']) . '">';
        $output .= '<div class="anvil-live-blocks' . $fullWidthClass . '" id="anvil-live-blocks" style="' . esc($blocksStyle) . '">';
        
        if (empty($blocks)) {
            // Empty state - show prompt to add blocks
            $output .= '<div class="anvil-live-empty-state">';
            $output .= '<div class="anvil-live-empty-icon">';
            $output .= '<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">';
            $output .= '<rect x="3" y="3" width="18" height="18" rx="2"/>';
            $output .= '<path d="M12 8v8M8 12h8"/>';
            $output .= '</svg>';
            $output .= '</div>';
            $output .= '<h3 style="margin: 0 0 8px; font-size: 18px; font-weight: 600;">Start Building</h3>';
            $output .= '<p style="margin: 0; color: #64748b;">Click a block in the sidebar or drag one here</p>';
            $output .= '</div>';
        } else {
            // Render each block with editing wrapper
            foreach ($blocks as $index => $block) {
                $output .= self::renderEditableBlock($block, $index);
            }
        }
        
        $output .= '</div>'; // .anvil-live-blocks
        $output .= '</div>'; // .anvil-live-canvas
        
        // Output blocks data for JavaScript
        $output .= '<script>window.AnvilLiveBlocks = ' . json_encode($blocks, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ';</script>';
        
        return $output;
    }
    
    /**
     * Generate inline CSS for canvas based on page settings
     */
    private static function generateCanvasStyle(array $settings): string
    {
        $styles = [];
        
        // Content width
        if (!empty($settings['contentWidthFull'])) {
            $styles[] = 'max-width: 100%';
            $styles[] = 'width: 100%';
        } else {
            $width = $settings['contentWidth'] ?? '1200';
            $unit = $settings['contentWidthUnit'] ?? 'px';
            $styles[] = 'max-width: ' . $width . $unit;
            $styles[] = 'width: 100%';
        }
        
        // Padding
        $pUnit = $settings['paddingUnit'] ?? 'px';
        $pTop = $settings['paddingTop'] ?? '0';
        $pRight = $settings['paddingRight'] ?? '0';
        $pBottom = $settings['paddingBottom'] ?? '0';
        $pLeft = $settings['paddingLeft'] ?? '0';
        
        if ($pTop !== '0' || $pRight !== '0' || $pBottom !== '0' || $pLeft !== '0') {
            $styles[] = 'padding: ' . $pTop . $pUnit . ' ' . $pRight . $pUnit . ' ' . $pBottom . $pUnit . ' ' . $pLeft . $pUnit;
        }
        
        // Margin
        $mUnit = $settings['marginUnit'] ?? 'px';
        $mTop = $settings['marginTop'] ?? '0';
        $mRight = $settings['marginRight'] ?? 'auto';
        $mBottom = $settings['marginBottom'] ?? '0';
        $mLeft = $settings['marginLeft'] ?? 'auto';
        
        // Build margin value
        $mTopVal = $mTop === 'auto' ? 'auto' : $mTop . $mUnit;
        $mRightVal = $mRight === 'auto' ? 'auto' : $mRight . $mUnit;
        $mBottomVal = $mBottom === 'auto' ? 'auto' : $mBottom . $mUnit;
        $mLeftVal = $mLeft === 'auto' ? 'auto' : $mLeft . $mUnit;
        
        $styles[] = 'margin: ' . $mTopVal . ' ' . $mRightVal . ' ' . $mBottomVal . ' ' . $mLeftVal;
        
        return implode('; ', $styles);
    }
    
    /**
     * Render a block in editable mode
     */
    public static function renderEditableBlock(array $block, int $index): string
    {
        $type = $block['type'] ?? 'paragraph';
        $id = $block['id'] ?? Anvil::generateBlockId();
        $attrs = $block['attributes'] ?? [];
        
        $blockDef = Anvil::getBlock($type);
        $icon = $blockDef['icon'] ?? 'square';
        $label = $blockDef['label'] ?? ucfirst($type);
        
        // Render the actual block content
        $content = Anvil::renderBlock($block);
        
        $output = '<div class="anvil-live-block" data-block-id="' . esc($id) . '" data-block-type="' . esc($type) . '" data-block-index="' . $index . '">';
        
        // Block toolbar
        $output .= '<div class="anvil-live-block-toolbar">';
        $output .= '<div class="anvil-live-block-toolbar-left">';
        $output .= '<span class="anvil-live-block-handle" title="Drag to reorder"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg></span>';
        $output .= '<span class="anvil-live-block-type">' . esc($label) . '</span>';
        $output .= '</div>';
        $output .= '<div class="anvil-live-block-toolbar-right">';
        $output .= '<button type="button" class="anvil-live-block-action" data-action="edit" title="Edit Block"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>';
        $output .= '<button type="button" class="anvil-live-block-action" data-action="duplicate" title="Duplicate"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg></button>';
        $output .= '<button type="button" class="anvil-live-block-action anvil-live-block-action-delete" data-action="delete" title="Delete"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3,6 5,6 21,6"/><path d="M19,6v14a2,2,0,0,1-2,2H7a2,2,0,0,1-2-2V6m3,0V4a2,2,0,0,1,2-2h4a2,2,0,0,1,2,2v2"/></svg></button>';
        $output .= '</div>';
        $output .= '</div>';
        
        // Block content wrapper (for inline editing)
        $output .= '<div class="anvil-live-block-content" data-editable="' . (self::isInlineEditable($type) ? 'true' : 'false') . '">';
        $output .= $content;
        $output .= '</div>';
        
        // Add block button (between blocks)
        $output .= '<div class="anvil-live-add-between">';
        $output .= '<button type="button" class="anvil-live-add-between-btn" data-after-index="' . $index . '"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></button>';
        $output .= '</div>';
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Check if a block type supports inline editing
     */
    private static function isInlineEditable(string $type): bool
    {
        return in_array($type, ['paragraph', 'heading', 'quote', 'list', 'button']);
    }
    
    /**
     * Render the editor UI (sidebar, toolbar, modals)
     */
    public static function renderEditorUI(): void
    {
        if (!self::$editorMode) {
            return;
        }
        
        // Include the editor UI template
        include CMS_ROOT . '/includes/anvil-live/editor-ui.php';
        
        // Load editor JavaScript
        $baseUrl = SITE_URL . '/includes/anvil-live/assets';
        echo '<script src="' . esc($baseUrl) . '/js/anvil-live.js?v=' . self::VERSION . '"></script>' . "\n";
    }
    
    /**
     * Register REST API endpoints for the editor
     */
    private static function registerEndpoints(): void
    {
        if (!class_exists('Plugin')) {
            return;
        }
        
        // Save endpoint
        Plugin::registerRestRoute('v1', 'anvil-live/save', [
            'methods' => ['POST'],
            'callback' => [self::class, 'handleSave'],
            'permission_callback' => [self::class, 'canEdit'],
        ]);
        
        // Get block preview
        Plugin::registerRestRoute('v1', 'anvil-live/preview', [
            'methods' => ['POST'],
            'callback' => [self::class, 'handlePreview'],
            'permission_callback' => [self::class, 'canEdit'],
        ]);
        
        // Auto-save endpoint
        Plugin::registerRestRoute('v1', 'anvil-live/autosave', [
            'methods' => ['POST'],
            'callback' => [self::class, 'handleAutosave'],
            'permission_callback' => [self::class, 'canEdit'],
        ]);
    }
    
    /**
     * Check if current user can edit
     */
    public static function canEdit(): bool
    {
        $user = User::current();
        if (!$user) {
            return false;
        }
        
        return in_array($user['role'], ['admin', 'editor', 'author']);
    }
    
    /**
     * Handle save request
     */
    public static function handleSave(array $request): array
    {
        $postId = (int)($request['post_id'] ?? 0);
        $blocks = $request['blocks'] ?? [];
        $title = $request['title'] ?? null;
        $pageSettings = $request['pageSettings'] ?? null;
        
        if (!$postId) {
            return ['success' => false, 'error' => 'Invalid post ID'];
        }
        
        $post = Post::find($postId);
        if (!$post) {
            return ['success' => false, 'error' => 'Post not found'];
        }
        
        // Check permission
        $user = User::current();
        if ($user['role'] === 'author' && $post['author_id'] !== $user['id']) {
            return ['success' => false, 'error' => 'Permission denied'];
        }
        
        // Prepare update data
        $updateData = [
            'content' => json_encode($blocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
        
        if ($title !== null) {
            $updateData['title'] = $title;
        }
        
        // Update post
        $result = Post::update($postId, $updateData);
        
        // Save page settings if provided
        if ($pageSettings !== null && is_array($pageSettings)) {
            self::savePageSettings($postId, $pageSettings);
        }
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Changes saved successfully',
                'timestamp' => date('Y-m-d H:i:s'),
            ];
        }
        
        return ['success' => false, 'error' => 'Failed to save changes'];
    }
    
    /**
     * Handle preview request (render a single block)
     */
    public static function handlePreview(array $request): array
    {
        $block = $request['block'] ?? [];
        
        if (empty($block['type'])) {
            return ['success' => false, 'error' => 'Invalid block data'];
        }
        
        // Ensure block has an ID
        if (empty($block['id'])) {
            $block['id'] = Anvil::generateBlockId();
        }
        
        $html = Anvil::renderBlock($block);
        
        return [
            'success' => true,
            'html' => $html,
            'block' => $block,
        ];
    }
    
    /**
     * Handle autosave request
     */
    public static function handleAutosave(array $request): array
    {
        // Store autosave as post meta
        $postId = (int)($request['post_id'] ?? 0);
        $blocks = $request['blocks'] ?? [];
        
        if (!$postId) {
            return ['success' => false, 'error' => 'Invalid post ID'];
        }
        
        $post = Post::find($postId);
        if (!$post) {
            return ['success' => false, 'error' => 'Post not found'];
        }
        
        // Save autosave data to post meta
        Post::setMeta($postId, '_anvil_live_autosave', json_encode([
            'blocks' => $blocks,
            'timestamp' => time(),
            'user_id' => User::current()['id'] ?? 0,
        ]));
        
        return [
            'success' => true,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }
    
    /**
     * Get edit URL for a post
     */
    public static function getEditUrl(array $post): string
    {
        return Post::permalink($post) . '?anvil-live=edit';
    }
    
    /**
     * Check if Anvil Live is available for a post type
     */
    public static function isAvailable(string $postType): bool
    {
        // Available for all post types by default
        $allowed = array_keys(Post::getTypes());
        
        // If no post types registered yet, allow common ones
        if (empty($allowed)) {
            $allowed = ['post', 'page'];
        }
        
        // Allow filtering
        if (class_exists('Plugin')) {
            $allowed = Plugin::applyFilters('anvil_live_post_types', $allowed);
        }
        
        return in_array($postType, $allowed);
    }
    
    /**
     * Render the backend "Edit with Anvil Live" button
     * Call this in admin post-edit.php
     */
    public static function renderBackendButton(array $post): string
    {
        if (!self::isAvailable($post['post_type'] ?? 'post')) {
            return '';
        }
        
        // Only show for published or draft posts (not new)
        if (empty($post['id'])) {
            return '';
        }
        
        $editUrl = self::getEditUrl($post);
        
        return '
        <a href="' . esc($editUrl) . '" class="anvil-live-backend-btn" target="_blank" title="Edit with Anvil Live - Frontend Visual Editor">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                <path d="M2 17l10 5 10-5"/>
                <path d="M2 12l10 5 10-5"/>
            </svg>
            <span>Edit with Anvil Live</span>
        </a>
        <style>
            .anvil-live-backend-btn {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 10px 16px;
                background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
                color: white !important;
                text-decoration: none !important;
                border-radius: 6px;
                font-size: 14px;
                font-weight: 500;
                transition: all 0.2s ease;
                box-shadow: 0 2px 4px rgba(124, 58, 237, 0.3);
            }
            .anvil-live-backend-btn:hover {
                background: linear-gradient(135deg, #6d28d9 0%, #5b21b6 100%);
                transform: translateY(-1px);
                box-shadow: 0 4px 8px rgba(124, 58, 237, 0.4);
            }
            .anvil-live-backend-btn svg {
                flex-shrink: 0;
            }
        </style>';
    }
    
    /**
     * Render the frontend edit bar (appears at bottom of page for logged-in editors)
     */
    public static function renderFrontendEditBar(): string
    {
        // Must be logged in with edit permissions
        $user = User::current();
        if (!$user || !in_array($user['role'], ['admin', 'editor', 'author'])) {
            return '';
        }
        
        // Don't show if already in editor mode
        if (self::$editorMode) {
            return '';
        }
        
        // Get current post from global context
        global $post;
        if (!$post || empty($post['id'])) {
            return '';
        }
        
        // Check if Anvil Live is available for this post type
        if (!self::isAvailable($post['post_type'] ?? 'post')) {
            return '';
        }
        
        // Check author permission
        if ($user['role'] === 'author' && ($post['author_id'] ?? 0) !== $user['id']) {
            return '';
        }
        
        $editUrl = self::getEditUrl($post);
        $adminEditUrl = SITE_URL . '/admin/post-edit.php?id=' . $post['id'];
        
        return '
        <div id="anvil-live-edit-bar" class="anvil-live-edit-bar">
            <div class="anvil-live-edit-bar-inner">
                <span class="anvil-live-edit-bar-label">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                    <strong>' . esc($post['title']) . '</strong>
                </span>
                <div class="anvil-live-edit-bar-actions">
                    <a href="' . esc($adminEditUrl) . '" class="anvil-live-edit-bar-btn anvil-live-edit-bar-btn-secondary">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                            <path d="M9 3v18"/>
                        </svg>
                        Backend
                    </a>
                    <a href="' . esc($editUrl) . '" class="anvil-live-edit-bar-btn anvil-live-edit-bar-btn-primary">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                            <path d="M2 17l10 5 10-5"/>
                            <path d="M2 12l10 5 10-5"/>
                        </svg>
                        Anvil Live
                    </a>
                </div>
            </div>
        </div>
        <style>
            .anvil-live-edit-bar {
                position: fixed;
                bottom: 20px;
                right: 20px;
                z-index: 99990;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            }
            .anvil-live-edit-bar-inner {
                display: flex;
                align-items: center;
                gap: 16px;
                padding: 10px 14px;
                background: #1e293b;
                border-radius: 10px;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            }
            .anvil-live-edit-bar-label {
                display: flex;
                align-items: center;
                gap: 8px;
                color: #94a3b8;
                font-size: 13px;
            }
            .anvil-live-edit-bar-label strong {
                color: #f1f5f9;
                font-weight: 500;
                max-width: 180px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .anvil-live-edit-bar-label svg {
                color: #64748b;
                flex-shrink: 0;
            }
            .anvil-live-edit-bar-actions {
                display: flex;
                gap: 6px;
            }
            .anvil-live-edit-bar-btn {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 7px 12px;
                border-radius: 6px;
                font-size: 12px;
                font-weight: 500;
                text-decoration: none !important;
                transition: all 0.15s ease;
            }
            .anvil-live-edit-bar-btn svg {
                flex-shrink: 0;
            }
            .anvil-live-edit-bar-btn-secondary {
                background: #334155;
                color: #e2e8f0 !important;
            }
            .anvil-live-edit-bar-btn-secondary:hover {
                background: #475569;
            }
            .anvil-live-edit-bar-btn-primary {
                background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
                color: #ffffff !important;
            }
            .anvil-live-edit-bar-btn-primary:hover {
                background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            }
            @media (max-width: 540px) {
                .anvil-live-edit-bar { right: 10px; bottom: 10px; left: 10px; }
                .anvil-live-edit-bar-inner { flex-wrap: wrap; justify-content: center; }
                .anvil-live-edit-bar-label { width: 100%; justify-content: center; }
            }
        </style>';
    }
    
    /**
     * Output frontend edit bar if applicable
     * Hook this to vf_footer
     */
    public static function maybeRenderEditBar(): void
    {
        echo self::renderFrontendEditBar();
    }
}
