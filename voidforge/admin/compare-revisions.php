<?php
/**
 * Compare Revisions - VoidForge CMS
 */

define('CMS_ROOT', dirname(__DIR__));
require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/user.php';
require_once CMS_ROOT . '/includes/post.php';
require_once CMS_ROOT . '/includes/plugin.php';

Post::init();
User::startSession();
User::requireLogin();

$postId = (int)($_GET['post_id'] ?? 0);
$leftId = (int)($_GET['left'] ?? 0);
$rightId = (int)($_GET['right'] ?? 0); // 0 means current post

if (!$postId) {
    redirect(ADMIN_URL . '/');
}

$post = Post::find($postId);
if (!$post) {
    redirect(ADMIN_URL . '/');
}

$typeConfig = Post::getType($post['post_type']);
$pageTitle = 'Compare Revisions: ' . $post['title'];

// Get all revisions for this post
try {
    $revisions = Post::getRevisions($postId, 100);
} catch (Exception $e) {
    setFlash('error', 'Revisions feature not available.');
    redirect(ADMIN_URL . '/post-edit.php?id=' . $postId);
}

if (empty($revisions)) {
    setFlash('info', 'No revisions available for this ' . strtolower($typeConfig['singular']) . '.');
    redirect(ADMIN_URL . '/post-edit.php?id=' . $postId);
}

// Get left revision (older)
$leftRevision = null;
if ($leftId) {
    $leftRevision = Post::getRevision($leftId);
    if (!$leftRevision || $leftRevision['post_id'] != $postId) {
        $leftRevision = null;
    }
}

// Default to oldest revision if not set
if (!$leftRevision && !empty($revisions)) {
    $leftRevision = end($revisions); // Oldest
    $leftId = $leftRevision['id'];
}

// Get right revision (newer) or current post
$rightRevision = null;
if ($rightId) {
    $rightRevision = Post::getRevision($rightId);
    if (!$rightRevision || $rightRevision['post_id'] != $postId) {
        $rightRevision = null;
        $rightId = 0;
    }
}

// Prepare comparison data
$leftData = [
    'id' => $leftRevision['id'] ?? 0,
    'title' => $leftRevision['title'] ?? '',
    'content' => $leftRevision['content'] ?? '',
    'excerpt' => $leftRevision['excerpt'] ?? '',
    'created_at' => $leftRevision['created_at'] ?? '',
    'author_name' => $leftRevision['author_name'] ?? 'Unknown',
    'revision_number' => $leftRevision['revision_number'] ?? 0,
    'is_current' => false,
];

if ($rightId && $rightRevision) {
    $rightData = [
        'id' => $rightRevision['id'],
        'title' => $rightRevision['title'],
        'content' => $rightRevision['content'],
        'excerpt' => $rightRevision['excerpt'],
        'created_at' => $rightRevision['created_at'],
        'author_name' => $rightRevision['author_name'] ?? 'Unknown',
        'revision_number' => $rightRevision['revision_number'],
        'is_current' => false,
    ];
} else {
    // Compare to current post
    $author = User::find($post['author_id']);
    $rightData = [
        'id' => 0,
        'title' => $post['title'],
        'content' => $post['content'],
        'excerpt' => $post['excerpt'] ?? '',
        'created_at' => $post['updated_at'],
        'author_name' => $author['display_name'] ?? 'Unknown',
        'revision_number' => 0,
        'is_current' => true,
    ];
}

/**
 * Simple word-based diff algorithm
 */
function wordDiff($old, $new) {
    $oldWords = preg_split('/(\s+)/', $old, -1, PREG_SPLIT_DELIM_CAPTURE);
    $newWords = preg_split('/(\s+)/', $new, -1, PREG_SPLIT_DELIM_CAPTURE);
    
    $diff = computeLCS($oldWords, $newWords);
    
    return $diff;
}

/**
 * Compute LCS-based diff
 */
function computeLCS($old, $new) {
    $oldLen = count($old);
    $newLen = count($new);
    
    // Build LCS matrix
    $lcs = array_fill(0, $oldLen + 1, array_fill(0, $newLen + 1, 0));
    
    for ($i = 1; $i <= $oldLen; $i++) {
        for ($j = 1; $j <= $newLen; $j++) {
            if ($old[$i - 1] === $new[$j - 1]) {
                $lcs[$i][$j] = $lcs[$i - 1][$j - 1] + 1;
            } else {
                $lcs[$i][$j] = max($lcs[$i - 1][$j], $lcs[$i][$j - 1]);
            }
        }
    }
    
    // Backtrack to find diff
    $result = [];
    $i = $oldLen;
    $j = $newLen;
    
    while ($i > 0 || $j > 0) {
        if ($i > 0 && $j > 0 && $old[$i - 1] === $new[$j - 1]) {
            array_unshift($result, ['type' => 'equal', 'value' => $old[$i - 1]]);
            $i--;
            $j--;
        } elseif ($j > 0 && ($i == 0 || $lcs[$i][$j - 1] >= $lcs[$i - 1][$j])) {
            array_unshift($result, ['type' => 'add', 'value' => $new[$j - 1]]);
            $j--;
        } elseif ($i > 0 && ($j == 0 || $lcs[$i][$j - 1] < $lcs[$i - 1][$j])) {
            array_unshift($result, ['type' => 'remove', 'value' => $old[$i - 1]]);
            $i--;
        }
    }
    
    return $result;
}

/**
 * Render diff with HTML highlighting
 */
function renderDiff($diff) {
    $html = '';
    $buffer = ['type' => null, 'value' => ''];
    
    foreach ($diff as $part) {
        if ($part['type'] === $buffer['type']) {
            $buffer['value'] .= $part['value'];
        } else {
            $html .= flushBuffer($buffer);
            $buffer = $part;
        }
    }
    $html .= flushBuffer($buffer);
    
    return $html;
}

function flushBuffer($buffer) {
    if (empty($buffer['value'])) return '';
    
    $escaped = htmlspecialchars($buffer['value']);
    
    switch ($buffer['type']) {
        case 'add':
            return '<ins class="diff-add">' . $escaped . '</ins>';
        case 'remove':
            return '<del class="diff-remove">' . $escaped . '</del>';
        default:
            return $escaped;
    }
}

// Generate diffs
$titleDiff = wordDiff($leftData['title'], $rightData['title']);
$contentDiff = wordDiff($leftData['content'], $rightData['content']);
$excerptDiff = wordDiff($leftData['excerpt'], $rightData['excerpt']);

$titleChanged = $leftData['title'] !== $rightData['title'];
$contentChanged = $leftData['content'] !== $rightData['content'];
$excerptChanged = $leftData['excerpt'] !== $rightData['excerpt'];

include ADMIN_PATH . '/includes/header.php';
?>

<style>
.compare-page {
    max-width: 1400px;
    margin: 0 auto;
}

.compare-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.compare-header .back-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: var(--bg-secondary);
    color: var(--text-muted);
    text-decoration: none;
    transition: all 0.2s;
}

.compare-header .back-btn:hover {
    background: var(--primary-color);
    color: white;
}

.compare-header h1 {
    font-size: 1.5rem;
    font-weight: 700;
    flex: 1;
}

.compare-header .post-title {
    font-size: 0.875rem;
    color: var(--text-muted);
    margin-top: 0.25rem;
}

/* Revision Selector */
.revision-selector {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 1rem;
    align-items: center;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: var(--bg-secondary);
    border-radius: 12px;
    border: 1px solid var(--border-color);
}

.revision-select-box {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.revision-select-box label {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
}

.revision-select-box select {
    padding: 0.75rem 1rem;
    border-radius: 8px;
    border: 1px solid var(--border-color);
    background: var(--bg-color);
    color: var(--text-color);
    font-size: 0.875rem;
    cursor: pointer;
}

.revision-select-box select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.compare-arrow {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: var(--primary-color);
    color: white;
}

.compare-btn {
    padding: 0.75rem 1.5rem;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.compare-btn:hover {
    background: var(--primary-hover);
    transform: translateY(-1px);
}

/* Diff Sections */
.diff-section {
    margin-bottom: 2rem;
    background: var(--bg-secondary);
    border-radius: 12px;
    border: 1px solid var(--border-color);
    overflow: hidden;
}

.diff-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.5rem;
    background: var(--bg-tertiary);
    border-bottom: 1px solid var(--border-color);
}

.diff-section-header h3 {
    font-size: 0.9375rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.diff-section-header .status {
    font-size: 0.75rem;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-weight: 500;
}

.diff-section-header .status.changed {
    background: #fef3c7;
    color: #92400e;
}

.diff-section-header .status.unchanged {
    background: #d1fae5;
    color: #065f46;
}

.diff-content {
    padding: 1.5rem;
}

/* Side by Side View */
.diff-side-by-side {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1px;
    background: var(--border-color);
}

.diff-side {
    padding: 1rem;
    background: var(--bg-color);
    min-height: 100px;
}

.diff-side-header {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
    margin-bottom: 0.75rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--border-color);
}

.diff-side-content {
    font-size: 0.875rem;
    line-height: 1.7;
    white-space: pre-wrap;
    word-wrap: break-word;
}

/* Inline Diff View */
.diff-inline {
    padding: 1.5rem;
    font-size: 0.875rem;
    line-height: 1.8;
    white-space: pre-wrap;
    word-wrap: break-word;
}

.diff-add {
    background: #d1fae5;
    color: #065f46;
    text-decoration: none;
    padding: 0.125rem 0;
    border-radius: 2px;
}

.diff-remove {
    background: #fee2e2;
    color: #991b1b;
    text-decoration: line-through;
    padding: 0.125rem 0;
    border-radius: 2px;
}

/* View Toggle */
.view-toggle {
    display: flex;
    gap: 0.25rem;
    background: var(--bg-tertiary);
    padding: 0.25rem;
    border-radius: 8px;
}

.view-toggle button {
    padding: 0.5rem 1rem;
    border: none;
    background: transparent;
    color: var(--text-muted);
    font-size: 0.8125rem;
    font-weight: 500;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
}

.view-toggle button.active {
    background: var(--bg-color);
    color: var(--text-color);
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

/* Revision Info Cards */
.revision-info-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 2rem;
}

.revision-info-card {
    padding: 1rem 1.5rem;
    background: var(--bg-secondary);
    border-radius: 12px;
    border: 1px solid var(--border-color);
}

.revision-info-card.left {
    border-left: 4px solid #ef4444;
}

.revision-info-card.right {
    border-left: 4px solid #22c55e;
}

.revision-info-card h4 {
    font-size: 0.8125rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.revision-info-card .meta {
    font-size: 0.8125rem;
    color: var(--text-muted);
    line-height: 1.6;
}

.revision-info-card .current-badge {
    background: var(--primary-color);
    color: white;
    font-size: 0.6875rem;
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
    font-weight: 600;
}

/* Actions */
.compare-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid var(--border-color);
}

.btn-restore {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    text-decoration: none;
    transition: all 0.2s;
    cursor: pointer;
    border: none;
}

.btn-restore.left {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

.btn-restore.left:hover {
    background: #fee2e2;
}

.btn-restore.right {
    background: #f0fdf4;
    color: #16a34a;
    border: 1px solid #bbf7d0;
}

.btn-restore.right:hover {
    background: #dcfce7;
}

/* Legend */
.diff-legend {
    display: flex;
    gap: 1.5rem;
    padding: 1rem 1.5rem;
    background: var(--bg-tertiary);
    border-radius: 8px;
    font-size: 0.8125rem;
    margin-bottom: 1.5rem;
}

.diff-legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.diff-legend-item .sample {
    padding: 0.125rem 0.5rem;
    border-radius: 4px;
}

.diff-legend-item .sample.add {
    background: #d1fae5;
    color: #065f46;
}

.diff-legend-item .sample.remove {
    background: #fee2e2;
    color: #991b1b;
    text-decoration: line-through;
}

/* No Changes */
.no-changes {
    text-align: center;
    padding: 3rem;
    color: var(--text-muted);
}

.no-changes svg {
    width: 48px;
    height: 48px;
    margin-bottom: 1rem;
    opacity: 0.5;
}

@media (max-width: 768px) {
    .revision-selector {
        grid-template-columns: 1fr;
    }
    
    .compare-arrow {
        transform: rotate(90deg);
        margin: 0 auto;
    }
    
    .diff-side-by-side {
        grid-template-columns: 1fr;
    }
    
    .revision-info-row {
        grid-template-columns: 1fr;
    }
    
    .compare-actions {
        flex-direction: column;
    }
}
</style>

<div class="compare-page">
    <div class="compare-header">
        <a href="<?= ADMIN_URL ?>/post-edit.php?id=<?= $postId ?>" class="back-btn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <div>
            <h1>Compare Revisions</h1>
            <div class="post-title"><?= esc($post['title']) ?></div>
        </div>
    </div>
    
    <!-- Revision Selector -->
    <form method="get" class="revision-selector">
        <input type="hidden" name="post_id" value="<?= $postId ?>">
        
        <div class="revision-select-box">
            <label>From (Older)</label>
            <select name="left" onchange="this.form.submit()">
                <?php foreach ($revisions as $rev): ?>
                <option value="<?= $rev['id'] ?>" <?= $leftId == $rev['id'] ? 'selected' : '' ?>>
                    #<?= $rev['revision_number'] ?> — <?= formatDate($rev['created_at'], 'M j, Y g:i a') ?> — <?= esc($rev['author_name'] ?? 'Unknown') ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="compare-arrow">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="5" y1="12" x2="19" y2="12"></line>
                <polyline points="12 5 19 12 12 19"></polyline>
            </svg>
        </div>
        
        <div class="revision-select-box">
            <label>To (Newer)</label>
            <select name="right" onchange="this.form.submit()">
                <option value="0" <?= $rightId == 0 ? 'selected' : '' ?>>
                    Current Version — <?= formatDate($post['updated_at'], 'M j, Y g:i a') ?>
                </option>
                <?php foreach ($revisions as $rev): ?>
                <?php if ($rev['id'] != $leftId): ?>
                <option value="<?= $rev['id'] ?>" <?= $rightId == $rev['id'] ? 'selected' : '' ?>>
                    #<?= $rev['revision_number'] ?> — <?= formatDate($rev['created_at'], 'M j, Y g:i a') ?> — <?= esc($rev['author_name'] ?? 'Unknown') ?>
                </option>
                <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
    
    <!-- Revision Info Cards -->
    <div class="revision-info-row">
        <div class="revision-info-card left">
            <h4>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 8 14"></polyline>
                </svg>
                Revision #<?= $leftData['revision_number'] ?>
            </h4>
            <div class="meta">
                <?= formatDate($leftData['created_at'], 'F j, Y \a\t g:i a') ?><br>
                by <?= esc($leftData['author_name']) ?>
            </div>
        </div>
        
        <div class="revision-info-card right">
            <h4>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 8 14"></polyline>
                </svg>
                <?php if ($rightData['is_current']): ?>
                    Current Version
                    <span class="current-badge">LIVE</span>
                <?php else: ?>
                    Revision #<?= $rightData['revision_number'] ?>
                <?php endif; ?>
            </h4>
            <div class="meta">
                <?= formatDate($rightData['created_at'], 'F j, Y \a\t g:i a') ?><br>
                by <?= esc($rightData['author_name']) ?>
            </div>
        </div>
    </div>
    
    <!-- Legend -->
    <div class="diff-legend">
        <div class="diff-legend-item">
            <span class="sample add">Added text</span>
            <span>New content</span>
        </div>
        <div class="diff-legend-item">
            <span class="sample remove">Removed text</span>
            <span>Deleted content</span>
        </div>
    </div>
    
    <?php if (!$titleChanged && !$contentChanged && !$excerptChanged): ?>
    <div class="diff-section">
        <div class="no-changes">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <h3>No Differences Found</h3>
            <p>These two versions are identical.</p>
        </div>
    </div>
    <?php else: ?>
    
    <!-- Title Diff -->
    <div class="diff-section">
        <div class="diff-section-header">
            <h3>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                Title
            </h3>
            <span class="status <?= $titleChanged ? 'changed' : 'unchanged' ?>">
                <?= $titleChanged ? 'Changed' : 'Unchanged' ?>
            </span>
        </div>
        <?php if ($titleChanged): ?>
        <div class="diff-content">
            <div class="diff-inline"><?= renderDiff($titleDiff) ?></div>
        </div>
        <?php else: ?>
        <div class="diff-content">
            <div class="diff-side-content" style="color: var(--text-muted);"><?= esc($leftData['title']) ?></div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Content Diff -->
    <div class="diff-section">
        <div class="diff-section-header">
            <h3>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                </svg>
                Content
            </h3>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <span class="status <?= $contentChanged ? 'changed' : 'unchanged' ?>">
                    <?= $contentChanged ? 'Changed' : 'Unchanged' ?>
                </span>
                <?php if ($contentChanged): ?>
                <div class="view-toggle">
                    <button type="button" class="active" onclick="setView('inline', this)">Inline</button>
                    <button type="button" onclick="setView('side', this)">Side by Side</button>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($contentChanged): ?>
        <div class="diff-content">
            <div id="contentInline" class="diff-inline"><?= renderDiff($contentDiff) ?></div>
            <div id="contentSide" class="diff-side-by-side" style="display: none;">
                <div class="diff-side">
                    <div class="diff-side-header">Revision #<?= $leftData['revision_number'] ?></div>
                    <div class="diff-side-content"><?= nl2br(esc($leftData['content'])) ?></div>
                </div>
                <div class="diff-side">
                    <div class="diff-side-header"><?= $rightData['is_current'] ? 'Current Version' : 'Revision #' . $rightData['revision_number'] ?></div>
                    <div class="diff-side-content"><?= nl2br(esc($rightData['content'])) ?></div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="diff-content">
            <div class="diff-side-content" style="color: var(--text-muted); max-height: 200px; overflow-y: auto;">
                <?= nl2br(esc($leftData['content'])) ?: '<em>No content</em>' ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Excerpt Diff -->
    <?php if ($leftData['excerpt'] || $rightData['excerpt']): ?>
    <div class="diff-section">
        <div class="diff-section-header">
            <h3>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="17" y1="10" x2="3" y2="10"></line>
                    <line x1="21" y1="6" x2="3" y2="6"></line>
                    <line x1="21" y1="14" x2="3" y2="14"></line>
                    <line x1="17" y1="18" x2="3" y2="18"></line>
                </svg>
                Excerpt
            </h3>
            <span class="status <?= $excerptChanged ? 'changed' : 'unchanged' ?>">
                <?= $excerptChanged ? 'Changed' : 'Unchanged' ?>
            </span>
        </div>
        <?php if ($excerptChanged): ?>
        <div class="diff-content">
            <div class="diff-inline"><?= renderDiff($excerptDiff) ?></div>
        </div>
        <?php else: ?>
        <div class="diff-content">
            <div class="diff-side-content" style="color: var(--text-muted);">
                <?= esc($leftData['excerpt']) ?: '<em>No excerpt</em>' ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <?php endif; ?>
    
    <!-- Actions -->
    <div class="compare-actions">
        <button type="button" class="btn-restore left" onclick="restoreRevision(<?= $leftData['id'] ?>, <?= $leftData['revision_number'] ?>)">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="1 4 1 10 7 10"></polyline>
                <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
            </svg>
            Restore Revision #<?= $leftData['revision_number'] ?>
        </button>
        
        <?php if (!$rightData['is_current'] && $rightData['id']): ?>
        <button type="button" class="btn-restore right" onclick="restoreRevision(<?= $rightData['id'] ?>, <?= $rightData['revision_number'] ?>)">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="1 4 1 10 7 10"></polyline>
                <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
            </svg>
            Restore Revision #<?= $rightData['revision_number'] ?>
        </button>
        <?php endif; ?>
        
        <a href="<?= ADMIN_URL ?>/post-edit.php?id=<?= $postId ?>" class="btn-restore" style="background: var(--bg-tertiary); color: var(--text-color); border: 1px solid var(--border-color); margin-left: auto;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
            Cancel
        </a>
    </div>
</div>

<script>
function setView(view, btn) {
    var buttons = btn.parentElement.querySelectorAll('button');
    buttons.forEach(function(b) { b.classList.remove('active'); });
    btn.classList.add('active');
    
    var inlineView = document.getElementById('contentInline');
    var sideView = document.getElementById('contentSide');
    
    if (view === 'inline') {
        inlineView.style.display = 'block';
        sideView.style.display = 'none';
    } else {
        inlineView.style.display = 'none';
        sideView.style.display = 'grid';
    }
}

function restoreRevision(revisionId, revisionNumber) {
    if (confirm('Restore revision #' + revisionNumber + '?\n\nThis will create a backup of the current version and restore the selected revision.')) {
        var csrfToken = '<?= csrfToken() ?>';
        window.location.href = 'post-edit.php?id=<?= $postId ?>&restore_revision=' + revisionId + '&csrf=' + encodeURIComponent(csrfToken);
    }
}
</script>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
