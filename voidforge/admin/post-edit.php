<?php
/**
 * Post Editor - VoidForge CMS
 */

define('CMS_ROOT', dirname(__DIR__));
require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/user.php';
require_once CMS_ROOT . '/includes/post.php';
require_once CMS_ROOT . '/includes/media.php';
require_once CMS_ROOT . '/includes/plugin.php';
require_once CMS_ROOT . '/includes/taxonomy.php';

Post::init();
Taxonomy::init();

User::startSession();
User::requireLogin();

$postId = (int)($_GET['id'] ?? 0);
$postType = $_GET['type'] ?? 'post';

if ($postId) {
    $post = Post::find($postId);
    if (!$post) {
        redirect(ADMIN_URL . '/posts.php?type=' . $postType);
    }
    $postType = $post['post_type'];
} else {
    $post = null;
}

$typeConfig = Post::getType($postType);
if (!$typeConfig) {
    redirect(ADMIN_URL . '/');
}

$pageTitle = $post ? 'Edit ' . $typeConfig['singular'] : 'New ' . $typeConfig['singular'];
$errors = [];

// Handle restore revision action
if (isset($_GET['restore_revision']) && $post && verifyCsrf($_GET['csrf'] ?? '')) {
    $revisionId = (int)$_GET['restore_revision'];
    try {
        if (Post::restoreRevision($revisionId)) {
            setFlash('success', 'Revision restored successfully.');
            redirect(ADMIN_URL . '/post-edit.php?id=' . $post['id']);
        } else {
            setFlash('error', 'Failed to restore revision.');
        }
    } catch (Exception $e) {
        setFlash('error', 'Revisions feature not available. Please run system update.');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $saveAction = $_POST['save_action'] ?? 'draft';
    $scheduleDate = trim($_POST['schedule_date'] ?? '');
    $scheduleTime = trim($_POST['schedule_time'] ?? '');
    
    // Determine status based on save action
    if ($saveAction === 'schedule' && $scheduleDate && $scheduleTime) {
        $scheduledAt = $scheduleDate . ' ' . $scheduleTime . ':00';
        $status = 'scheduled';
    } elseif ($saveAction === 'publish') {
        $status = 'published';
        $scheduledAt = null;
    } else {
        $status = 'draft';
        $scheduledAt = null;
    }
    
    $data = [
        'post_type' => $postType,
        'title' => trim($_POST['title'] ?? ''),
        'slug' => trim($_POST['slug'] ?? ''),
        'content' => $_POST['content'] ?? '',
        'excerpt' => trim($_POST['excerpt'] ?? ''),
        'status' => $status,
        'featured_image_id' => !empty($_POST['featured_image_id']) ? (int)$_POST['featured_image_id'] : null,
    ];

    if ($typeConfig['hierarchical']) {
        $data['parent_id'] = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $data['menu_order'] = (int)($_POST['menu_order'] ?? 0);
    }

    if (empty($data['title'])) {
        $errors['title'] = 'Title is required';
    }

    if (empty($data['slug'])) {
        $data['slug'] = slugify($data['title']);
    } else {
        $data['slug'] = slugify($data['slug']);
    }

    if (empty($errors)) {
        if ($post) {
            // Create a revision before updating (only for existing posts)
            try {
                Post::createRevision($post['id']);
            } catch (Exception $e) {
                // Revisions table may not exist yet, continue without revision
            }
            
            if ($data['slug'] !== $post['slug']) {
                $data['slug'] = uniqueSlug($data['slug'], $postType, $post['id']);
            }
            Post::update($post['id'], $data);
            $savedPostId = $post['id'];
            
            // Handle scheduled_at
            if ($status === 'scheduled' && !empty($scheduledAt)) {
                Post::schedule($savedPostId, $scheduledAt);
            } elseif (($post['status'] ?? '') === 'scheduled' && $status !== 'scheduled') {
                // Clear scheduled_at if no longer scheduled
                try {
                    Database::execute(
                        "UPDATE " . Database::table('posts') . " SET scheduled_at = NULL WHERE id = ?",
                        [$savedPostId]
                    );
                } catch (Exception $e) {
                    // Column might not exist yet
                }
            }
            
            $message = $status === 'scheduled' 
                ? $typeConfig['singular'] . ' scheduled for ' . formatDate($scheduledAt, 'M j, Y g:i a') . '.'
                : $typeConfig['singular'] . ' updated successfully.';
            setFlash('success', $message);
        } else {
            $data['slug'] = uniqueSlug($data['slug'], $postType);
            $savedPostId = Post::create($data);
            
            // Handle scheduled_at for new posts
            if ($status === 'scheduled' && !empty($scheduledAt)) {
                Post::schedule($savedPostId, $scheduledAt);
            }
            
            $message = $status === 'scheduled' 
                ? $typeConfig['singular'] . ' scheduled for ' . formatDate($scheduledAt, 'M j, Y g:i a') . '.'
                : $typeConfig['singular'] . ' created successfully.';
            setFlash('success', $message);
        }
        
        // Save custom fields
        $customFieldsDefs = get_post_type_fields($postType);
        foreach ($customFieldsDefs as $field) {
            $fieldKey = $field['key'];
            if (isset($_POST['cf_' . $fieldKey])) {
                $fieldValue = $_POST['cf_' . $fieldKey];
                // Handle checkbox (unchecked = not sent)
                if ($field['type'] === 'checkbox') {
                    $fieldValue = $fieldValue ? '1' : '0';
                }
                set_custom_field($fieldKey, $fieldValue, $savedPostId);
            } elseif ($field['type'] === 'checkbox') {
                // Checkbox unchecked
                set_custom_field($fieldKey, '0', $savedPostId);
            }
        }
        
        // Save taxonomy terms
        $postTaxonomies = Taxonomy::getForPostType($postType);
        foreach ($postTaxonomies as $taxSlug => $tax) {
            $termIds = $_POST['tax_' . $taxSlug] ?? [];
            if (!is_array($termIds)) {
                $termIds = array_filter(array_map('intval', explode(',', $termIds)));
            }
            Taxonomy::setPostTerms($savedPostId, $taxSlug, $termIds);
        }
        
        redirect(ADMIN_URL . '/post-edit.php?id=' . $savedPostId);
    }
}

$parentOptions = [];
if ($typeConfig['hierarchical']) {
    $parentOptions = Post::getParentOptions($postType, $post['id'] ?? null);
}

$featuredImage = null;
if ($post && $post['featured_image_id']) {
    $featuredImage = Media::find($post['featured_image_id']);
}

// Get custom fields for this post type
$customFieldsDefs = get_post_type_fields($postType);
$customFieldsValues = [];
if ($post && $post['id']) {

// Get taxonomies for this post type
$postTaxonomies = Taxonomy::getForPostType($postType);
$postTermsData = [];
if ($post && $post['id']) {
    foreach ($postTaxonomies as $taxSlug => $tax) {
        $postTermsData[$taxSlug] = Taxonomy::getPostTermIds($post['id'], $taxSlug);
    }
}
    $customFieldsValues = get_all_custom_fields($post['id']);
}

$permalinkBase = SITE_URL . '/';
if ($postType !== 'page') {
    $permalinkBase .= $postType . '/';
}

include ADMIN_PATH . '/includes/header.php';
?>

<style>
/* Post Editor Styles */
.editor-layout {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 1.5rem;
    align-items: start;
}

.editor-main { min-width: 0; }

.editor-sidebar {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.form-input, .form-select, .form-textarea {
    width: 100%;
    padding: 0.625rem 0.875rem;
    font-size: 0.9375rem;
    line-height: 1.5;
    color: var(--text-primary);
    background: var(--bg-input);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    transition: all 0.15s;
    font-family: inherit;
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
    outline: none;
    border-color: var(--forge-primary);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}

.form-textarea { min-height: 100px; resize: vertical; }

/* Permalink */
.permalink-wrap {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    background: var(--bg-card-header);
    border-radius: var(--border-radius);
    font-size: 0.8125rem;
}

.permalink-wrap svg { color: var(--text-muted); flex-shrink: 0; }
.permalink-base { color: var(--text-muted); }
.permalink-slug { color: var(--forge-primary); font-weight: 500; }

.permalink-edit {
    margin-left: auto;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 4px;
    cursor: pointer;
    color: var(--text-secondary);
}

.permalink-edit:hover { border-color: var(--forge-primary); color: var(--forge-primary); }

/* Editor */
.editor-wrap {
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-lg);
    overflow: hidden;
    background: var(--bg-card);
}

.editor-tabs {
    display: flex;
    background: var(--bg-card-header);
    border-bottom: 1px solid var(--border-color);
}

.editor-tab {
    padding: 0.625rem 1rem;
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--text-secondary);
    background: none;
    border: none;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
}

.editor-tab:hover { color: var(--text-primary); }
.editor-tab.active { color: var(--forge-primary); border-bottom-color: var(--forge-primary); background: var(--bg-card); }

.editor-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
    padding: 0.5rem 0.75rem;
    background: var(--bg-card-header);
    border-bottom: 1px solid var(--border-color);
}

.editor-btn {
    width: 32px;
    height: 32px;
    border: none;
    background: transparent;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-secondary);
    transition: all 0.15s;
}

.editor-btn:hover { background: var(--bg-hover); color: var(--text-primary); }
.editor-btn.active { background: var(--forge-primary); color: #fff; }
.editor-btn svg { width: 16px; height: 16px; }
.editor-divider { width: 1px; height: 24px; background: var(--border-color); margin: 0 0.25rem; align-self: center; }

.editor-visual {
    min-height: 400px;
    padding: 1.25rem;
    outline: none;
    font-size: 1rem;
    line-height: 1.7;
}

.editor-visual:focus { outline: none; }
.editor-visual h1 { font-size: 2rem; margin: 0 0 1rem; }
.editor-visual h2 { font-size: 1.5rem; margin: 1.5rem 0 0.75rem; }
.editor-visual h3 { font-size: 1.25rem; margin: 1.25rem 0 0.5rem; }
.editor-visual p { margin: 0 0 1rem; }
.editor-visual ul, .editor-visual ol { margin: 0 0 1rem; padding-left: 1.5rem; }
.editor-visual blockquote { margin: 1rem 0; padding: 1rem; border-left: 4px solid var(--forge-primary); background: var(--bg-card-header); font-style: italic; }
.editor-visual a { color: var(--forge-primary); }
.editor-visual img { max-width: 100%; height: auto; border-radius: var(--border-radius); }

.editor-code {
    display: none;
    width: 100%;
    min-height: 400px;
    padding: 1.25rem;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.875rem;
    line-height: 1.6;
    border: none;
    resize: vertical;
    background: var(--bg-card);
}

.editor-code:focus { outline: none; }

/* Sidebar cards */
.sidebar-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-lg);
    overflow: hidden;
}

.sidebar-card-header {
    padding: 0.875rem 1rem;
    background: var(--bg-card-header);
    border-bottom: 1px solid var(--border-color);
    font-weight: 600;
    font-size: 0.875rem;
}

.sidebar-card-body { padding: 1rem; }

/* Featured image */
.featured-image-preview {
    margin-bottom: 0.75rem;
}

.featured-image-preview img {
    width: 100%;
    border-radius: var(--border-radius);
    display: block;
}

/* Modal */
.modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.6);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 1rem;
}

.modal-backdrop.active { display: flex; }

.modal-box {
    background: var(--bg-card);
    border-radius: var(--border-radius-lg);
    width: 100%;
    max-width: 700px;
    max-height: 80vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.modal-header {
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal-title { font-weight: 600; }

.modal-close {
    width: 32px;
    height: 32px;
    border: none;
    background: transparent;
    cursor: pointer;
    font-size: 1.5rem;
    line-height: 1;
    color: var(--text-muted);
}

.modal-body {
    padding: 1rem;
    overflow-y: auto;
    flex: 1;
}

.modal-footer {
    padding: 1rem;
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}

.media-select-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 0.75rem;
}

.media-select-item {
    aspect-ratio: 1;
    border-radius: var(--border-radius);
    overflow: hidden;
    cursor: pointer;
    border: 2px solid var(--border-color);
    transition: all 0.15s;
}

.media-select-item:hover { border-color: var(--forge-primary); }
.media-select-item.selected { border-color: var(--forge-primary); box-shadow: 0 0 0 2px rgba(99,102,241,0.2); }
.media-select-item img { width: 100%; height: 100%; object-fit: cover; }

@media (max-width: 1024px) {
    .editor-layout { grid-template-columns: 1fr; }
    .editor-sidebar { order: -1; }
}
</style>

<form method="post">
    <?= csrfField() ?>
    
    <div class="page-header" style="margin-bottom: 1.5rem;">
        <h2><?= esc($pageTitle) ?></h2>
    </div>
    
    <div class="editor-layout">
        <div class="editor-main">
            <!-- Title -->
            <div class="card" style="margin-bottom: 1rem;">
                <div class="card-body">
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <input type="text" id="title" name="title" class="form-input" 
                               value="<?= esc($post['title'] ?? $_POST['title'] ?? '') ?>"
                               placeholder="Enter title..." 
                               style="font-size: 1.25rem; font-weight: 600; padding: 0.75rem 1rem;"
                               autofocus>
                        <?php if (isset($errors['title'])): ?>
                            <div class="form-error" style="margin-top: 0.5rem; color: var(--forge-danger); font-size: 0.875rem;"><?= esc($errors['title']) ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <input type="hidden" id="slug" name="slug" value="<?= esc($post['slug'] ?? $_POST['slug'] ?? '') ?>">
                    <div class="permalink-wrap">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                        </svg>
                        <span class="permalink-base"><?= esc($permalinkBase) ?></span>
                        <span class="permalink-slug" id="permalinkSlug"><?= esc($post['slug'] ?? 'your-url') ?></span>
                        <button type="button" class="permalink-edit" onclick="editPermalink()">Edit</button>
                    </div>
                </div>
            </div>
            
            <?php if (Post::typeSupports($postType, 'editor')): ?>
            <!-- Editor -->
            <div class="editor-wrap" style="margin-bottom: 1rem;">
                <div class="editor-tabs">
                    <button type="button" class="editor-tab active" onclick="switchEditor('visual')">Visual</button>
                    <button type="button" class="editor-tab" onclick="switchEditor('code')">Code</button>
                </div>
                <div class="editor-toolbar" id="editorToolbar">
                    <button type="button" class="editor-btn" data-command="bold" title="Bold">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 4h8a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"></path><path d="M6 12h9a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"></path></svg>
                    </button>
                    <button type="button" class="editor-btn" data-command="italic" title="Italic">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="4" x2="10" y2="4"></line><line x1="14" y1="20" x2="5" y2="20"></line><line x1="15" y1="4" x2="9" y2="20"></line></svg>
                    </button>
                    <button type="button" class="editor-btn" data-command="underline" title="Underline">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 3v7a6 6 0 0 0 6 6 6 6 0 0 0 6-6V3"></path><line x1="4" y1="21" x2="20" y2="21"></line></svg>
                    </button>
                    <div class="editor-divider"></div>
                    <button type="button" class="editor-btn" data-command="formatBlock" data-value="h2" title="Heading 2">H2</button>
                    <button type="button" class="editor-btn" data-command="formatBlock" data-value="h3" title="Heading 3">H3</button>
                    <button type="button" class="editor-btn" data-command="formatBlock" data-value="p" title="Paragraph">P</button>
                    <div class="editor-divider"></div>
                    <button type="button" class="editor-btn" data-command="insertUnorderedList" title="Bullet List">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="9" y1="6" x2="20" y2="6"></line><line x1="9" y1="12" x2="20" y2="12"></line><line x1="9" y1="18" x2="20" y2="18"></line><circle cx="4" cy="6" r="1.5" fill="currentColor"></circle><circle cx="4" cy="12" r="1.5" fill="currentColor"></circle><circle cx="4" cy="18" r="1.5" fill="currentColor"></circle></svg>
                    </button>
                    <button type="button" class="editor-btn" data-command="insertOrderedList" title="Numbered List">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="10" y1="6" x2="21" y2="6"></line><line x1="10" y1="12" x2="21" y2="12"></line><line x1="10" y1="18" x2="21" y2="18"></line><path d="M4 6h1v4M4 10h2M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"></path></svg>
                    </button>
                    <div class="editor-divider"></div>
                    <button type="button" class="editor-btn" data-command="formatBlock" data-value="blockquote" title="Quote">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V21z"></path><path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2h.75c0 2.25.25 4-2.75 4v3z"></path></svg>
                    </button>
                    <button type="button" class="editor-btn" data-command="createLink" title="Insert Link">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                    </button>
                </div>
                <div id="editorVisual" class="editor-visual" contenteditable="true"></div>
                <textarea id="editorCode" class="editor-code" placeholder="Enter HTML code..."></textarea>
                <input type="hidden" id="content" name="content" value="<?= esc($post['content'] ?? $_POST['content'] ?? '') ?>">
            </div>
            <?php endif; ?>
            
            <?php if (Post::typeSupports($postType, 'excerpt')): ?>
            <div class="card">
                <div class="card-header"><h3 class="card-title">Excerpt</h3></div>
                <div class="card-body">
                    <textarea name="excerpt" class="form-textarea" rows="3" placeholder="Write a short summary..."><?= esc($post['excerpt'] ?? $_POST['excerpt'] ?? '') ?></textarea>
                    <div class="form-hint" style="margin-top: 0.5rem; font-size: 0.8125rem; color: var(--text-muted);">A brief summary shown in listings.</div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="editor-sidebar">
            <!-- Publish -->
            <div class="sidebar-card">
                <div class="sidebar-card-header">Publish</div>
                <div class="sidebar-card-body">
                    <?php 
                    $currentStatus = $post['status'] ?? 'draft';
                    $statusColors = [
                        'draft' => '#f59e0b',
                        'published' => '#22c55e',
                        'scheduled' => '#6366f1',
                        'trash' => '#dc2626',
                    ];
                    ?>
                    <div style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1rem;">
                        <strong>Status:</strong> 
                        <span style="color: <?= $statusColors[$currentStatus] ?? '#6b7280' ?>;">
                            <?= Post::STATUS_LABELS[$currentStatus] ?? ucfirst($currentStatus) ?>
                        </span>
                    </div>
                    
                    <?php if ($post): ?>
                    <div style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1rem; line-height: 1.6;">
                        <?php if ($currentStatus === 'scheduled' && !empty($post['scheduled_at'])): ?>
                        <div style="color: #6366f1;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline; vertical-align: -2px; margin-right: 0.25rem;">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            Scheduled: <?= formatDate($post['scheduled_at'], 'M j, Y g:i a') ?>
                        </div>
                        <?php elseif ($post['published_at']): ?>
                        <div>Published: <?= formatDate($post['published_at'], 'M j, Y g:i a') ?></div>
                        <?php endif; ?>
                        <?php if ($post['updated_at'] && $post['updated_at'] !== $post['created_at']): ?>
                        <div>Updated: <?= formatDate($post['updated_at'], 'M j, Y g:i a') ?></div>
                        <?php endif; ?>
                        <div style="color: var(--text-muted); opacity: 0.7;">Created: <?= formatDate($post['created_at'], 'M j, Y') ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Schedule Options (for draft or scheduled posts) -->
                    <?php if (!$post || $currentStatus !== 'published'): ?>
                    <div id="scheduleSection" style="margin-bottom: 1rem; padding: 0.75rem; background: var(--bg-card-header); border-radius: var(--border-radius);">
                        <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.8125rem; cursor: pointer; margin-bottom: 0.75rem;">
                            <input type="checkbox" id="scheduleToggle" <?= $currentStatus === 'scheduled' ? 'checked' : '' ?> style="accent-color: var(--forge-primary);">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            Schedule for later
                        </label>
                        <div id="scheduleFields" style="display: <?= $currentStatus === 'scheduled' ? 'flex' : 'none' ?>; gap: 0.5rem;">
                            <input type="date" name="schedule_date" id="scheduleDate" class="form-input" style="flex: 1; font-size: 0.8125rem; padding: 0.5rem;"
                                   value="<?= $currentStatus === 'scheduled' && !empty($post['scheduled_at']) ? date('Y-m-d', strtotime($post['scheduled_at'])) : date('Y-m-d', strtotime('+1 day')) ?>"
                                   min="<?= date('Y-m-d') ?>">
                            <input type="time" name="schedule_time" id="scheduleTime" class="form-input" style="width: 100px; font-size: 0.8125rem; padding: 0.5rem;"
                                   value="<?= $currentStatus === 'scheduled' && !empty($post['scheduled_at']) ? date('H:i', strtotime($post['scheduled_at'])) : '09:00' ?>">
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Action Buttons -->
                    <div style="display: flex; gap: 0.5rem;">
                        <?php if (!$post || $currentStatus !== 'published'): ?>
                        <button type="submit" name="save_action" value="draft" class="btn btn-secondary" style="flex: 1;">
                            Save Draft
                        </button>
                        <button type="submit" name="save_action" value="publish" id="publishBtn" class="btn btn-primary" style="flex: 1; <?= $currentStatus === 'scheduled' ? 'display: none;' : '' ?>">
                            Publish
                        </button>
                        <button type="submit" name="save_action" value="schedule" id="scheduleBtn" class="btn btn-primary" style="flex: 1; <?= $currentStatus === 'scheduled' ? '' : 'display: none;' ?>">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.25rem;">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            Schedule
                        </button>
                        <?php else: ?>
                        <button type="submit" name="save_action" value="publish" class="btn btn-primary" style="flex: 1;">
                            Update
                        </button>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($post && $currentStatus === 'published'): ?>
                    <div style="margin-top: 0.5rem; display: flex; gap: 0.5rem;">
                        <button type="submit" name="save_action" value="draft" class="btn btn-secondary" style="flex: 1; font-size: 0.75rem;">
                            Revert to Draft
                        </button>
                        <a href="<?= esc(Post::permalink($post)) ?>" target="_blank" class="btn btn-secondary" style="flex: 1; font-size: 0.75rem; text-align: center;">View <?= esc($typeConfig['singular']) ?></a>
                    </div>
                    <?php elseif ($post && $currentStatus === 'scheduled'): ?>
                    <div style="margin-top: 0.5rem;">
                        <button type="submit" name="save_action" value="publish" class="btn btn-secondary" style="width: 100%; font-size: 0.75rem;">
                            Publish Now
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php 
            // Revisions card - only show for existing posts
            if ($post):
                // Wrap in try-catch in case revisions table doesn't exist yet
                try {
                    $revisions = Post::getRevisions($post['id'], 20);
                    $revisionCount = Post::getRevisionCount($post['id']);
                    $maxRevisions = Post::getMaxRevisions($postType);
                    $revisionsEnabled = true;
                } catch (Exception $e) {
                    $revisions = [];
                    $revisionCount = 0;
                    $maxRevisions = 10;
                    $revisionsEnabled = false;
                }
            ?>
            <div class="sidebar-card">
                <div class="sidebar-card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <span>Revisions</span>
                    <?php if ($revisionCount > 0): ?>
                    <span style="background: var(--primary-color); color: white; font-size: 0.6875rem; padding: 0.125rem 0.5rem; border-radius: 9999px;"><?= $revisionCount ?></span>
                    <?php endif; ?>
                </div>
                <div class="sidebar-card-body">
                    <?php if (!$revisionsEnabled): ?>
                    <div style="font-size: 0.8125rem; color: var(--text-muted);">
                        Revisions table not found. Please run <a href="update.php" style="color: var(--primary-color);">system update</a> to enable revisions.
                    </div>
                    <?php elseif ($maxRevisions === 0): ?>
                    <div style="font-size: 0.8125rem; color: var(--text-muted);">
                        Revisions are disabled for this post type.
                    </div>
                    <?php elseif (empty($revisions)): ?>
                    <div style="font-size: 0.8125rem; color: var(--text-muted);">
                        No revisions yet. Revisions are created when you update the <?= strtolower($typeConfig['singular']) ?>.
                    </div>
                    <?php else: ?>
                    <div style="max-height: 250px; overflow-y: auto; margin: -0.5rem; padding: 0.5rem;">
                        <?php foreach ($revisions as $i => $rev): ?>
                        <div class="revision-item" style="padding: 0.5rem; border-radius: 6px; margin-bottom: 0.5rem; background: var(--bg-tertiary); <?= $i === 0 ? 'border-left: 3px solid var(--primary-color);' : '' ?>">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 0.5rem;">
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-size: 0.75rem; font-weight: 600; color: var(--text-color);">
                                        #<?= $rev['revision_number'] ?><?= $i === 0 ? ' (Latest)' : '' ?>
                                    </div>
                                    <div style="font-size: 0.6875rem; color: var(--text-muted); margin-top: 0.125rem;">
                                        <?= formatDate($rev['created_at'], 'M j, Y g:i a') ?>
                                    </div>
                                    <div style="font-size: 0.6875rem; color: var(--text-muted);">
                                        by <?= esc($rev['author_name'] ?? 'Unknown') ?>
                                    </div>
                                </div>
                                <div style="flex-shrink: 0;">
                                    <button type="button" 
                                            onclick="restoreRevision(<?= $rev['id'] ?>, <?= $rev['revision_number'] ?>)" 
                                            class="btn btn-secondary" 
                                            style="font-size: 0.6875rem; padding: 0.25rem 0.5rem;">
                                        Restore
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($revisionCount > 20): ?>
                    <div style="font-size: 0.75rem; color: var(--text-muted); text-align: center; margin-top: 0.5rem;">
                        Showing latest 20 of <?= $revisionCount ?> revisions
                    </div>
                    <?php endif; ?>
                    <?php if ($revisionCount >= 2): ?>
                    <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid var(--border-color);">
                        <a href="<?= ADMIN_URL ?>/compare-revisions.php?post_id=<?= $post['id'] ?>" 
                           class="btn btn-secondary" 
                           style="width: 100%; font-size: 0.75rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="20" x2="18" y2="10"></line>
                                <line x1="12" y1="20" x2="12" y2="4"></line>
                                <line x1="6" y1="20" x2="6" y2="14"></line>
                            </svg>
                            Compare Revisions
                        </a>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($typeConfig['hierarchical']): ?>
            <div class="sidebar-card">
                <div class="sidebar-card-header">Page Attributes</div>
                <div class="sidebar-card-body">
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label" style="font-size: 0.8125rem; margin-bottom: 0.375rem; display: block;">Parent</label>
                        <select name="parent_id" class="form-select">
                            <option value="">(no parent)</option>
                            <?php foreach ($parentOptions as $opt): ?>
                            <option value="<?= $opt['id'] ?>" <?= ($post['parent_id'] ?? '') == $opt['id'] ? 'selected' : '' ?>><?= esc($opt['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" style="font-size: 0.8125rem; margin-bottom: 0.375rem; display: block;">Order</label>
                        <input type="number" name="menu_order" class="form-input" value="<?= esc($post['menu_order'] ?? 0) ?>" min="0">
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (Post::typeSupports($postType, 'featured_image')): ?>
            <div class="sidebar-card">
                <div class="sidebar-card-header">Featured Image</div>
                <div class="sidebar-card-body">
                    <input type="hidden" id="featured_image_id" name="featured_image_id" value="<?= $post['featured_image_id'] ?? '' ?>">
                    
                    <div id="featuredPreview" style="<?= $featuredImage ? '' : 'display:none;' ?>">
                        <?php if ($featuredImage): ?>
                        <div class="featured-image-preview">
                            <img src="<?= esc($featuredImage['url']) ?>" alt="">
                        </div>
                        <?php endif; ?>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="removeFeatured()" style="width: 100%;">Remove</button>
                    </div>
                    
                    <button type="button" class="btn btn-secondary" onclick="openMediaModal()" id="setFeaturedBtn" style="width: 100%; <?= $featuredImage ? 'display:none;' : '' ?>">
                        Set Featured Image
                    </button>
                </div>
            </div>
            <?php endif; ?>
            
            <?php foreach ($postTaxonomies as $taxSlug => $tax): 
                $allTerms = Taxonomy::getTerms($taxSlug);
                $selectedTerms = $postTermsData[$taxSlug] ?? [];
            ?>
            <div class="sidebar-card">
                <div class="sidebar-card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <?= esc($tax['label']) ?>
                    <a href="terms.php?taxonomy=<?= esc($taxSlug) ?>" style="font-size: 0.6875rem; color: var(--text-muted); text-decoration: none;">+ Add New</a>
                </div>
                <div class="sidebar-card-body" style="max-height: 200px; overflow-y: auto;">
                    <?php if (empty($allTerms)): ?>
                    <p style="color: var(--text-muted); font-size: 0.8125rem; margin: 0;">No <?= esc(strtolower($tax['label'])) ?> yet. <a href="terms.php?taxonomy=<?= esc($taxSlug) ?>">Create one</a>.</p>
                    <?php elseif ($tax['hierarchical']): ?>
                    <?php 
                    $termsTree = Taxonomy::getTermsTree($taxSlug);
                    function renderTermCheckboxes($terms, $taxSlug, $selected, $depth = 0) {
                        foreach ($terms as $term): 
                    ?>
                    <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.25rem 0; padding-left: <?= $depth * 1.25 ?>rem; cursor: pointer;">
                        <input type="checkbox" name="tax_<?= esc($taxSlug) ?>[]" value="<?= $term['id'] ?>" <?= in_array($term['id'], $selected) ? 'checked' : '' ?> style="accent-color: var(--forge-primary);">
                        <span style="font-size: 0.8125rem; color: var(--text-primary);"><?= esc($term['name']) ?></span>
                    </label>
                    <?php 
                        if (!empty($term['children'])) {
                            renderTermCheckboxes($term['children'], $taxSlug, $selected, $depth + 1);
                        }
                        endforeach;
                    }
                    renderTermCheckboxes($termsTree, $taxSlug, $selectedTerms);
                    ?>
                    <?php else: ?>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.375rem;">
                        <?php foreach ($allTerms as $term): ?>
                        <label style="display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.25rem 0.625rem; background: <?= in_array($term['id'], $selectedTerms) ? 'rgba(99,102,241,0.15)' : 'var(--bg-card-header)' ?>; border: 1px solid <?= in_array($term['id'], $selectedTerms) ? 'var(--forge-primary)' : 'var(--border-color)' ?>; border-radius: 100px; cursor: pointer; transition: all 0.15s;" onmouseover="this.style.borderColor='var(--forge-primary)'" onmouseout="this.style.borderColor=this.querySelector('input').checked?'var(--forge-primary)':'var(--border-color)'">
                            <input type="checkbox" name="tax_<?= esc($taxSlug) ?>[]" value="<?= $term['id'] ?>" <?= in_array($term['id'], $selectedTerms) ? 'checked' : '' ?> style="display: none;" onchange="this.parentElement.style.background=this.checked?'rgba(99,102,241,0.15)':'var(--bg-card-header)'; this.parentElement.style.borderColor=this.checked?'var(--forge-primary)':'var(--border-color)';">
                            <span style="font-size: 0.75rem; color: var(--text-primary);"><?= esc($term['name']) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (!empty($customFieldsDefs)): ?>
            <div class="sidebar-card">
                <div class="sidebar-card-header">Custom Fields</div>
                <div class="sidebar-card-body">
                    <?php foreach ($customFieldsDefs as $field): 
                        $fieldKey = $field['key'];
                        $fieldValue = $customFieldsValues[$fieldKey] ?? $_POST['cf_' . $fieldKey] ?? '';
                        $fieldId = 'cf_' . $fieldKey;
                        $isRequired = !empty($field['required']);
                    ?>
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label" style="font-size: 0.8125rem; margin-bottom: 0.375rem; display: block;">
                            <?= esc($field['label']) ?>
                            <?php if ($isRequired): ?><span style="color: var(--forge-danger);">*</span><?php endif; ?>
                        </label>
                        
                        <?php if ($field['type'] === 'textarea'): ?>
                        <textarea name="<?= $fieldId ?>" id="<?= $fieldId ?>" class="form-textarea" rows="3" <?= $isRequired ? 'required' : '' ?>><?= esc($fieldValue) ?></textarea>
                        
                        <?php elseif ($field['type'] === 'select' && !empty($field['options'])): ?>
                        <select name="<?= $fieldId ?>" id="<?= $fieldId ?>" class="form-select" <?= $isRequired ? 'required' : '' ?>>
                            <option value="">— Select —</option>
                            <?php foreach ($field['options'] as $opt): ?>
                            <option value="<?= esc($opt) ?>" <?= $fieldValue === $opt ? 'selected' : '' ?>><?= esc($opt) ?></option>
                            <?php endforeach; ?>
                        </select>
                        
                        <?php elseif ($field['type'] === 'checkbox'): ?>
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" name="<?= $fieldId ?>" id="<?= $fieldId ?>" value="1" <?= $fieldValue ? 'checked' : '' ?>>
                            <span style="font-size: 0.875rem; color: var(--text-secondary);">Yes</span>
                        </label>
                        
                        <?php elseif ($field['type'] === 'number'): ?>
                        <input type="number" name="<?= $fieldId ?>" id="<?= $fieldId ?>" class="form-input" value="<?= esc($fieldValue) ?>" step="any" <?= $isRequired ? 'required' : '' ?>>
                        
                        <?php elseif ($field['type'] === 'email'): ?>
                        <input type="email" name="<?= $fieldId ?>" id="<?= $fieldId ?>" class="form-input" value="<?= esc($fieldValue) ?>" <?= $isRequired ? 'required' : '' ?>>
                        
                        <?php elseif ($field['type'] === 'url'): ?>
                        <input type="url" name="<?= $fieldId ?>" id="<?= $fieldId ?>" class="form-input" value="<?= esc($fieldValue) ?>" <?= $isRequired ? 'required' : '' ?>>
                        
                        <?php elseif ($field['type'] === 'date'): ?>
                        <input type="date" name="<?= $fieldId ?>" id="<?= $fieldId ?>" class="form-input" value="<?= esc($fieldValue) ?>" <?= $isRequired ? 'required' : '' ?>>
                        
                        <?php elseif ($field['type'] === 'datetime'): ?>
                        <input type="datetime-local" name="<?= $fieldId ?>" id="<?= $fieldId ?>" class="form-input" value="<?= esc($fieldValue) ?>" <?= $isRequired ? 'required' : '' ?>>
                        
                        <?php elseif ($field['type'] === 'color'): ?>
                        <input type="color" name="<?= $fieldId ?>" id="<?= $fieldId ?>" class="form-input" value="<?= esc($fieldValue ?: '#000000') ?>" style="height: 40px; padding: 0.25rem;">
                        
                        <?php elseif ($field['type'] === 'image'): ?>
                        <div class="cf-image-field" data-field="<?= $fieldId ?>">
                            <input type="hidden" name="<?= $fieldId ?>" id="<?= $fieldId ?>" value="<?= esc($fieldValue) ?>">
                            <div class="cf-image-preview" style="<?= $fieldValue ? '' : 'display:none;' ?> margin-bottom: 0.5rem;">
                                <?php if ($fieldValue): 
                                    $cfImg = Media::find((int)$fieldValue);
                                    if ($cfImg): ?>
                                <img src="<?= esc($cfImg['url']) ?>" style="max-width: 100%; border-radius: var(--border-radius);">
                                <?php endif; endif; ?>
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="openCfImageModal('<?= $fieldId ?>')" style="width: 100%;">
                                <?= $fieldValue ? 'Change Image' : 'Select Image' ?>
                            </button>
                            <?php if ($fieldValue): ?>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="removeCfImage('<?= $fieldId ?>')" style="width: 100%; margin-top: 0.25rem;">Remove</button>
                            <?php endif; ?>
                        </div>
                        
                        <?php elseif ($field['type'] === 'wysiwyg'): ?>
                        <textarea name="<?= $fieldId ?>" id="<?= $fieldId ?>" class="form-textarea" rows="6" <?= $isRequired ? 'required' : '' ?>><?= esc($fieldValue) ?></textarea>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">HTML allowed</div>
                        
                        <?php else: ?>
                        <input type="text" name="<?= $fieldId ?>" id="<?= $fieldId ?>" class="form-input" value="<?= esc($fieldValue) ?>" <?= $isRequired ? 'required' : '' ?>>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</form>

<!-- Media Modal -->
<div class="modal-backdrop" id="mediaModal">
    <div class="modal-box">
        <div class="modal-header">
            <span class="modal-title">Select Image</span>
            <button type="button" class="modal-close" onclick="closeMediaModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="media-select-grid" id="mediaGrid"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeMediaModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="selectMedia()">Select</button>
        </div>
    </div>
</div>

<!-- Permalink Modal -->
<div class="modal-backdrop" id="permalinkModal">
    <div class="modal-box" style="max-width: 400px;">
        <div class="modal-header">
            <span class="modal-title">Edit Permalink</span>
            <button type="button" class="modal-close" onclick="closePermalinkModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" style="font-size: 0.8125rem; margin-bottom: 0.375rem; display: block;">URL Slug</label>
                <input type="text" id="slugInput" class="form-input" placeholder="your-url-slug">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closePermalinkModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="savePermalink()">Save</button>
        </div>
    </div>
</div>

<script>
const titleInput = document.getElementById('title');
const slugHidden = document.getElementById('slug');
const permalinkSlug = document.getElementById('permalinkSlug');
const editorVisual = document.getElementById('editorVisual');
const editorCode = document.getElementById('editorCode');
const contentInput = document.getElementById('content');
let currentMode = 'visual';
let selectedMediaId = null;
let selectedMediaUrl = null;

// Initialize editor
if (contentInput && editorVisual) {
    editorVisual.innerHTML = contentInput.value;
    editorCode.value = contentInput.value;
}

// Auto-generate slug
if (titleInput) {
    titleInput.addEventListener('input', function() {
        if (!slugHidden.dataset.manual) {
            const slug = slugify(this.value);
            slugHidden.value = slug;
            permalinkSlug.textContent = slug || 'your-url';
        }
    });
}

// Toolbar buttons
document.querySelectorAll('.editor-btn[data-command]').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const cmd = this.dataset.command;
        const val = this.dataset.value || null;
        
        if (cmd === 'createLink') {
            const url = prompt('Enter URL:');
            if (url) document.execCommand(cmd, false, url);
        } else {
            document.execCommand(cmd, false, val);
        }
        editorVisual.focus();
        syncContent();
    });
});

// Sync content on input
if (editorVisual) {
    editorVisual.addEventListener('input', syncContent);
}
if (editorCode) {
    editorCode.addEventListener('input', function() {
        contentInput.value = this.value;
    });
}

function syncContent() {
    if (currentMode === 'visual') {
        contentInput.value = editorVisual.innerHTML;
        editorCode.value = editorVisual.innerHTML;
    }
}

function switchEditor(mode) {
    currentMode = mode;
    document.querySelectorAll('.editor-tab').forEach(t => t.classList.remove('active'));
    event.target.classList.add('active');
    
    if (mode === 'visual') {
        editorVisual.innerHTML = editorCode.value;
        contentInput.value = editorCode.value;
        editorVisual.style.display = 'block';
        editorCode.style.display = 'none';
        document.getElementById('editorToolbar').style.display = 'flex';
    } else {
        editorCode.value = editorVisual.innerHTML;
        contentInput.value = editorVisual.innerHTML;
        editorVisual.style.display = 'none';
        editorCode.style.display = 'block';
        document.getElementById('editorToolbar').style.display = 'none';
    }
}

function slugify(text) {
    return text.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').substring(0, 200);
}

// Permalink
function editPermalink() {
    document.getElementById('slugInput').value = slugHidden.value;
    document.getElementById('permalinkModal').classList.add('active');
}

function closePermalinkModal() {
    document.getElementById('permalinkModal').classList.remove('active');
}

function savePermalink() {
    const val = slugify(document.getElementById('slugInput').value);
    slugHidden.value = val;
    slugHidden.dataset.manual = 'true';
    permalinkSlug.textContent = val || 'your-url';
    closePermalinkModal();
}

// Media Modal
function openMediaModal() {
    document.getElementById('mediaModal').classList.add('active');
    loadMedia();
}

function closeMediaModal() {
    document.getElementById('mediaModal').classList.remove('active');
}

async function loadMedia() {
    const grid = document.getElementById('mediaGrid');
    grid.innerHTML = '<p style="text-align:center;color:var(--text-muted);padding:2rem;">Loading...</p>';
    
    try {
        // Use relative URL to avoid cross-origin issues
        const res = await fetch('media.php?action=list&type=image', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        });
        
        // Check if response is OK
        if (!res.ok) {
            throw new Error('Server returned ' + res.status + ': ' + res.statusText);
        }
        
        // Get response text first for debugging
        const text = await res.text();
        
        // Try to parse as JSON
        let data;
        try {
            data = JSON.parse(text);
        } catch (parseError) {
            console.error('JSON parse error. Response:', text.substring(0, 1000));
            // Check if there's a PHP error
            if (text.includes('Fatal error') || text.includes('Warning') || text.includes('Notice')) {
                throw new Error('PHP error occurred. Check server logs.');
            }
            throw new Error('Invalid response from server. Please refresh the page.');
        }
        
        if (!data.success) {
            grid.innerHTML = '<p style="text-align:center;color:var(--forge-danger);padding:2rem;">Error: ' + (data.error || 'Unknown error') + '</p>';
            return;
        }
        
        if (!data.media || data.media.length === 0) {
            grid.innerHTML = '<p style="text-align:center;color:var(--text-muted);padding:2rem;">No images found. <a href="media.php" target="_blank">Upload media</a></p>';
            return;
        }
        
        // Filter and render images
        const images = data.media.filter(m => m.mime_type && m.mime_type.startsWith('image/'));
        
        if (images.length === 0) {
            grid.innerHTML = '<p style="text-align:center;color:var(--text-muted);padding:2rem;">No images found. <a href="media.php" target="_blank">Upload media</a></p>';
            return;
        }
        
        grid.innerHTML = images.map(m => {
            const thumbUrl = m.thumbnail_url || m.url;
            const displayUrl = thumbUrl || m.url;
            return `<div class="media-select-item" data-id="${m.id}" data-url="${m.url}" onclick="selectMediaItem(this)">
                <img src="${displayUrl}" alt="${m.alt_text || ''}" loading="lazy" onerror="this.onerror=null; this.src='${m.url}';">
            </div>`;
        }).join('');
        
    } catch (e) {
        console.error('Media load error:', e);
        grid.innerHTML = `<div style="text-align:center;padding:2rem;">
            <p style="color:var(--forge-danger);margin-bottom:1rem;">${e.message}</p>
            <button type="button" class="btn btn-secondary btn-sm" onclick="loadMedia()">Try Again</button>
        </div>`;
    }
}

function selectMediaItem(el) {
    document.querySelectorAll('.media-select-item').forEach(i => i.classList.remove('selected'));
    el.classList.add('selected');
    selectedMediaId = el.dataset.id;
    selectedMediaUrl = el.dataset.url;
}

function selectMedia() {
    if (!selectedMediaId) return;
    
    document.getElementById('featured_image_id').value = selectedMediaId;
    document.getElementById('featuredPreview').innerHTML = `<div class="featured-image-preview"><img src="${selectedMediaUrl}" alt=""></div><button type="button" class="btn btn-secondary btn-sm" onclick="removeFeatured()" style="width:100%;">Remove</button>`;
    document.getElementById('featuredPreview').style.display = 'block';
    document.getElementById('setFeaturedBtn').style.display = 'none';
    closeMediaModal();
}

function removeFeatured() {
    document.getElementById('featured_image_id').value = '';
    document.getElementById('featuredPreview').style.display = 'none';
    document.getElementById('setFeaturedBtn').style.display = 'block';
}

// Custom field image picker
let currentCfImageField = null;

function openCfImageModal(fieldId) {
    currentCfImageField = fieldId;
    document.getElementById('mediaModal').classList.add('active');
    loadMedia();
}

function removeCfImage(fieldId) {
    document.getElementById(fieldId).value = '';
    const wrapper = document.querySelector('.cf-image-field[data-field="' + fieldId + '"]');
    if (wrapper) {
        const preview = wrapper.querySelector('.cf-image-preview');
        if (preview) {
            preview.style.display = 'none';
            preview.innerHTML = '';
        }
        // Update buttons
        const buttons = wrapper.querySelectorAll('button');
        buttons.forEach((btn, i) => {
            if (i === 0) btn.textContent = 'Select Image';
            if (i === 1) btn.style.display = 'none';
        });
    }
}

// Override selectMedia to handle both featured image and custom fields
const originalSelectMedia = selectMedia;
function selectMedia() {
    if (!selectedMediaId) return;
    
    if (currentCfImageField) {
        // Custom field image
        document.getElementById(currentCfImageField).value = selectedMediaId;
        const wrapper = document.querySelector('.cf-image-field[data-field="' + currentCfImageField + '"]');
        if (wrapper) {
            const preview = wrapper.querySelector('.cf-image-preview');
            if (preview) {
                preview.innerHTML = '<img src="' + selectedMediaUrl + '" style="max-width: 100%; border-radius: var(--border-radius);">';
                preview.style.display = 'block';
            }
            // Update buttons
            const buttons = wrapper.querySelectorAll('button');
            buttons.forEach((btn, i) => {
                if (i === 0) btn.textContent = 'Change Image';
                if (i === 1) btn.style.display = 'block';
            });
        }
        currentCfImageField = null;
    } else {
        // Featured image
        document.getElementById('featured_image_id').value = selectedMediaId;
        document.getElementById('featuredPreview').innerHTML = '<div class="featured-image-preview"><img src="' + selectedMediaUrl + '" alt=""></div><button type="button" class="btn btn-secondary btn-sm" onclick="removeFeatured()" style="width:100%;">Remove</button>';
        document.getElementById('featuredPreview').style.display = 'block';
        document.getElementById('setFeaturedBtn').style.display = 'none';
    }
    closeMediaModal();
}

// Restore revision function
function restoreRevision(revisionId, revisionNumber) {
    if (confirm('Restore revision #' + revisionNumber + '?\n\nThis will create a backup of the current version and restore the selected revision.')) {
        const csrfToken = document.querySelector('input[name="csrf_token"]').value;
        window.location.href = 'post-edit.php?id=<?= $post['id'] ?? 0 ?>&restore_revision=' + revisionId + '&csrf=' + encodeURIComponent(csrfToken);
    }
}

// Schedule toggle functionality
(function() {
    var scheduleToggle = document.getElementById('scheduleToggle');
    var scheduleFields = document.getElementById('scheduleFields');
    var publishBtn = document.getElementById('publishBtn');
    var scheduleBtn = document.getElementById('scheduleBtn');
    
    if (scheduleToggle) {
        scheduleToggle.addEventListener('change', function() {
            if (this.checked) {
                scheduleFields.style.display = 'flex';
                if (publishBtn) publishBtn.style.display = 'none';
                if (scheduleBtn) scheduleBtn.style.display = 'flex';
            } else {
                scheduleFields.style.display = 'none';
                if (publishBtn) publishBtn.style.display = 'flex';
                if (scheduleBtn) scheduleBtn.style.display = 'none';
            }
        });
    }
})();
</script>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
