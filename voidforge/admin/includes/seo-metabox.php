<?php
/**
 * SEO Section - Post Editor Component
 * 
 * Full-width SEO card displayed below the excerpt in the post editor.
 * 
 * Expected variables:
 * - $post: The current post array
 * - $seoMeta: The SEO meta data array (loaded in post-edit.php)
 * 
 * @package VoidForge
 * @since 0.3.0
 */

defined('CMS_ROOT') or die('Direct access not allowed');

// Get SEO analysis if post exists
$seoAnalysis = null;
if (!empty($post['id'])) {
    $seoAnalysis = SEO::analyzeContent($post, $seoMeta);
}

$seoScore = $seoAnalysis['score'] ?? 0;
$scoreColor = SEO::getScoreColor($seoScore);
$scoreLabel = SEO::getScoreLabel($seoScore);
?>

<div class="card seo-card">
    <div class="card-header" style="display: flex; align-items: center; justify-content: space-between;">
        <h3 class="card-title" style="display: flex; align-items: center; gap: 0.5rem;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.35-4.35"></path>
            </svg>
            SEO
        </h3>
        <?php if ($seoAnalysis): ?>
        <div class="seo-score-badge" style="background: <?= $scoreColor ?>;">
            <span class="score-value"><?= $seoScore ?></span>
            <span class="score-label"><?= $scoreLabel ?></span>
        </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <!-- Google Preview -->
        <div class="seo-google-preview">
            <div class="preview-label">Search Preview</div>
            <div class="google-result">
                <div class="google-title" id="seoPreviewTitle"><?= esc(!empty($seoMeta['seo_title']) ? $seoMeta['seo_title'] : ($post['title'] ?? 'Page Title')) ?></div>
                <div class="google-url"><?= esc(SITE_URL) ?>/<span id="seoPreviewSlug"><?= esc($post['slug'] ?? 'page-url') ?></span></div>
                <div class="google-desc" id="seoPreviewDesc"><?= esc(!empty($seoMeta['seo_description']) ? $seoMeta['seo_description'] : 'Add a meta description to improve click-through rates from search results.') ?></div>
            </div>
        </div>

        <!-- Main SEO Fields -->
        <div class="seo-fields-grid">
            <div class="seo-field">
                <label class="seo-label">Focus Keyword</label>
                <input type="text" name="seo_focus_keyword" id="seo_focus_keyword" class="form-input" value="<?= esc($seoMeta['seo_focus_keyword'] ?? '') ?>" placeholder="Enter your target keyword">
            </div>
            <div class="seo-field">
                <label class="seo-label">
                    SEO Title
                    <span class="char-indicator" id="seoTitleIndicator"><?= strlen($seoMeta['seo_title'] ?? '') ?>/60</span>
                </label>
                <input type="text" name="seo_title" id="seo_title" class="form-input" value="<?= esc($seoMeta['seo_title'] ?? '') ?>" placeholder="Leave empty to use post title" maxlength="70">
                <div class="char-bar"><div class="char-progress" id="seoTitleBar"></div></div>
            </div>
            <div class="seo-field seo-field-full">
                <label class="seo-label">
                    Meta Description
                    <span class="char-indicator" id="seoDescIndicator"><?= strlen($seoMeta['seo_description'] ?? '') ?>/160</span>
                </label>
                <textarea name="seo_description" id="seo_description" class="form-textarea" rows="2" placeholder="Write a compelling description for search results..." maxlength="200"><?= esc($seoMeta['seo_description'] ?? '') ?></textarea>
                <div class="char-bar"><div class="char-progress" id="seoDescBar"></div></div>
            </div>
        </div>

        <!-- SEO Analysis -->
        <?php if ($seoAnalysis && !empty($seoAnalysis['issues'])): ?>
        <div class="seo-analysis">
            <div class="analysis-header">
                <span>Analysis</span>
                <span class="analysis-stats"><?= $seoAnalysis['stats']['word_count'] ?> words</span>
            </div>
            <div class="analysis-issues">
                <?php foreach ($seoAnalysis['issues'] as $issue): ?>
                <div class="issue issue-<?= esc($issue['type']) ?>">
                    <?php if ($issue['type'] === 'error'): ?>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                    <?php elseif ($issue['type'] === 'warning'): ?>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    <?php else: ?>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                    <?php endif; ?>
                    <span><?= esc($issue['message']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Advanced Settings Toggle -->
        <button type="button" class="seo-advanced-toggle" onclick="toggleSeoAdvanced()">
            <span>Advanced Settings</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" id="seoAdvancedChevron"><polyline points="6 9 12 15 18 9"></polyline></svg>
        </button>

        <!-- Advanced Settings Panel -->
        <div class="seo-advanced-panel" id="seoAdvancedPanel">
            <div class="seo-fields-grid">
                <!-- Canonical URL -->
                <div class="seo-field seo-field-full">
                    <label class="seo-label">Canonical URL</label>
                    <input type="url" name="seo_canonical" class="form-input" value="<?= esc($seoMeta['seo_canonical'] ?? '') ?>" placeholder="Leave empty to use default URL">
                </div>
                
                <!-- Robots -->
                <div class="seo-field">
                    <label class="seo-label">Search Engines</label>
                    <select name="seo_robots_index" class="form-select">
                        <option value="index" <?= ($seoMeta['seo_robots_index'] ?? 'index') === 'index' ? 'selected' : '' ?>>Index this page</option>
                        <option value="noindex" <?= ($seoMeta['seo_robots_index'] ?? '') === 'noindex' ? 'selected' : '' ?>>Don't index this page</option>
                    </select>
                </div>
                <div class="seo-field">
                    <label class="seo-label">Follow Links</label>
                    <select name="seo_robots_follow" class="form-select">
                        <option value="follow" <?= ($seoMeta['seo_robots_follow'] ?? 'follow') === 'follow' ? 'selected' : '' ?>>Follow links</option>
                        <option value="nofollow" <?= ($seoMeta['seo_robots_follow'] ?? '') === 'nofollow' ? 'selected' : '' ?>>Don't follow links</option>
                    </select>
                </div>
            </div>

            <!-- Social Media Section -->
            <div class="seo-section-title">Social Media</div>
            <div class="seo-fields-grid">
                <div class="seo-field">
                    <label class="seo-label">Open Graph Title</label>
                    <input type="text" name="seo_og_title" class="form-input" value="<?= esc($seoMeta['seo_og_title'] ?? '') ?>" placeholder="Leave empty to use SEO title">
                </div>
                <div class="seo-field">
                    <label class="seo-label">Open Graph Image</label>
                    <input type="hidden" name="seo_og_image" id="seo_og_image" value="<?= esc($seoMeta['seo_og_image'] ?? '') ?>">
                    <div class="og-image-picker">
                        <div class="og-image-preview" id="seoOgImagePreview">
                            <?php if (!empty($seoMeta['seo_og_image'])): 
                                $ogMedia = Media::find((int)$seoMeta['seo_og_image']);
                                if ($ogMedia):
                            ?>
                            <img src="<?= esc(Media::url($ogMedia)) ?>" alt="">
                            <?php else: ?>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                            <?php endif; else: ?>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn-choose-image" onclick="openSeoMediaPicker()">Choose</button>
                        <?php if (!empty($seoMeta['seo_og_image'])): ?>
                        <button type="button" class="btn-remove-image" onclick="removeSeoOgImage()">×</button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="seo-field seo-field-full">
                    <label class="seo-label">Open Graph Description</label>
                    <textarea name="seo_og_description" class="form-textarea" rows="2" placeholder="Leave empty to use meta description"><?= esc($seoMeta['seo_og_description'] ?? '') ?></textarea>
                </div>
                
                <!-- Keywords (optional) -->
                <div class="seo-field seo-field-full">
                    <label class="seo-label">Meta Keywords <span style="color: var(--text-muted); font-weight: 400;">(optional)</span></label>
                    <input type="text" name="seo_keywords" class="form-input" value="<?= esc($seoMeta['seo_keywords'] ?? '') ?>" placeholder="keyword1, keyword2, keyword3">
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.seo-card {
    margin-top: 1.5rem;
}

.seo-score-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.375rem 0.75rem;
    border-radius: 20px;
    color: white;
    font-size: 0.8125rem;
}

.seo-score-badge .score-value {
    font-weight: 700;
}

.seo-score-badge .score-label {
    font-weight: 500;
    opacity: 0.9;
}

.seo-google-preview {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.preview-label {
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #94a3b8;
    margin-bottom: 0.75rem;
}

.google-result {
    background: white;
    border-radius: 8px;
    padding: 0.875rem;
}

.google-title {
    color: #1a0dab;
    font-size: 1.125rem;
    font-weight: 400;
    line-height: 1.3;
    margin-bottom: 0.25rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.google-url {
    color: #006621;
    font-size: 0.8125rem;
    margin-bottom: 0.25rem;
}

.google-desc {
    color: #545454;
    font-size: 0.8125rem;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.seo-fields-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.seo-field {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.seo-field-full {
    grid-column: 1 / -1;
}

.seo-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #374151;
}

.char-indicator {
    font-weight: 500;
    color: #94a3b8;
    font-size: 0.75rem;
}

.char-bar {
    height: 3px;
    background: #e2e8f0;
    border-radius: 2px;
    margin-top: 0.25rem;
    overflow: hidden;
}

.char-progress {
    height: 100%;
    background: linear-gradient(90deg, #6366f1, #8b5cf6);
    border-radius: 2px;
    transition: width 0.2s;
    width: 0%;
}

.char-progress.warning { background: linear-gradient(90deg, #f59e0b, #d97706); }
.char-progress.error { background: linear-gradient(90deg, #ef4444, #dc2626); }

.seo-analysis {
    margin-top: 1.5rem;
    padding-top: 1.25rem;
    border-top: 1px solid #e2e8f0;
}

.analysis-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.75rem;
}

.analysis-stats {
    font-weight: 500;
    color: #94a3b8;
}

.analysis-issues {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.issue {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.625rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 500;
}

.issue svg { flex-shrink: 0; }
.issue-error { background: rgba(239, 68, 68, 0.1); color: #dc2626; }
.issue-warning { background: rgba(245, 158, 11, 0.1); color: #d97706; }
.issue-info { background: rgba(59, 130, 246, 0.1); color: #2563eb; }

.seo-advanced-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 0.75rem 1rem;
    margin-top: 1.5rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    cursor: pointer;
    transition: all 0.2s;
}

.seo-advanced-toggle:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
}

.seo-advanced-toggle.active svg {
    transform: rotate(180deg);
}

.seo-advanced-panel {
    display: none;
    padding-top: 1.5rem;
    margin-top: 1rem;
    border-top: 1px solid #e2e8f0;
}

.seo-advanced-panel.active {
    display: block;
}

.seo-section-title {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #94a3b8;
    margin: 1.5rem 0 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e2e8f0;
}

.og-image-picker {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.og-image-preview {
    width: 60px;
    height: 60px;
    background: #f1f5f9;
    border: 1px dashed #cbd5e1;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    color: #94a3b8;
}

.og-image-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.btn-choose-image {
    padding: 0.5rem 0.875rem;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 0.8125rem;
    font-weight: 500;
    color: #374151;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-choose-image:hover {
    border-color: #6366f1;
    color: #6366f1;
}

.btn-remove-image {
    width: 28px;
    height: 28px;
    background: #fee2e2;
    border: none;
    border-radius: 6px;
    color: #dc2626;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-remove-image:hover {
    background: #fecaca;
}

@media (max-width: 640px) {
    .seo-fields-grid {
        grid-template-columns: 1fr;
    }
    
    .seo-field-full {
        grid-column: 1;
    }
}
</style>

<script>
(function() {
    // Character counters
    function updateCharCounter(input, indicatorId, barId, maxLen, warnAt) {
        const len = input.value.length;
        const indicator = document.getElementById(indicatorId);
        const bar = document.getElementById(barId);
        
        if (indicator) indicator.textContent = len + '/' + maxLen;
        
        if (bar) {
            const pct = Math.min(100, (len / maxLen) * 100);
            bar.style.width = pct + '%';
            bar.classList.remove('warning', 'error');
            if (len > maxLen) bar.classList.add('error');
            else if (len >= warnAt) bar.classList.add('warning');
        }
    }
    
    // Update preview
    function updatePreview() {
        const seoTitle = document.getElementById('seo_title');
        const seoDesc = document.getElementById('seo_description');
        const postTitle = document.getElementById('post-title') || document.querySelector('input[name="title"]');
        const postSlug = document.getElementById('post-slug') || document.querySelector('input[name="slug"]');
        
        const previewTitle = document.getElementById('seoPreviewTitle');
        const previewSlug = document.getElementById('seoPreviewSlug');
        const previewDesc = document.getElementById('seoPreviewDesc');
        
        if (previewTitle) {
            previewTitle.textContent = (seoTitle && seoTitle.value) || (postTitle && postTitle.value) || 'Page Title';
        }
        if (previewSlug && postSlug) {
            previewSlug.textContent = postSlug.value || 'page-url';
        }
        if (previewDesc && seoDesc) {
            previewDesc.textContent = seoDesc.value || 'Add a meta description to improve click-through rates from search results.';
        }
    }
    
    // Init
    const seoTitle = document.getElementById('seo_title');
    const seoDesc = document.getElementById('seo_description');
    const postTitle = document.getElementById('post-title') || document.querySelector('input[name="title"]');
    const postSlug = document.getElementById('post-slug') || document.querySelector('input[name="slug"]');
    
    if (seoTitle) {
        updateCharCounter(seoTitle, 'seoTitleIndicator', 'seoTitleBar', 60, 55);
        seoTitle.addEventListener('input', function() {
            updateCharCounter(this, 'seoTitleIndicator', 'seoTitleBar', 60, 55);
            updatePreview();
        });
    }
    
    if (seoDesc) {
        updateCharCounter(seoDesc, 'seoDescIndicator', 'seoDescBar', 160, 150);
        seoDesc.addEventListener('input', function() {
            updateCharCounter(this, 'seoDescIndicator', 'seoDescBar', 160, 150);
            updatePreview();
        });
    }
    
    if (postTitle) postTitle.addEventListener('input', updatePreview);
    if (postSlug) postSlug.addEventListener('input', updatePreview);
})();

function toggleSeoAdvanced() {
    const panel = document.getElementById('seoAdvancedPanel');
    const toggle = document.querySelector('.seo-advanced-toggle');
    const chevron = document.getElementById('seoAdvancedChevron');
    
    panel.classList.toggle('active');
    toggle.classList.toggle('active');
}

function openSeoMediaPicker() {
    if (typeof openMediaModal === 'function') {
        openMediaModal(function(media) {
            document.getElementById('seo_og_image').value = media.id;
            const preview = document.getElementById('seoOgImagePreview');
            preview.innerHTML = '<img src="' + media.url + '" alt="">';
        });
    }
}

function removeSeoOgImage() {
    document.getElementById('seo_og_image').value = '';
    document.getElementById('seoOgImagePreview').innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>';
}
</script>
