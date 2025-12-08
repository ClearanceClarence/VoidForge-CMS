<?php
/**
 * Single Post Template - VoidForge CMS
 */

defined('CMS_ROOT') or die('Direct access not allowed');

$pageTitle = $post['title'] ?? 'Untitled';
$featuredImage = Post::getFeaturedImage($post);
$author = Post::getAuthor($post);
$publishedDate = $post['published_at'] ?? $post['created_at'] ?? null;

include __DIR__ . '/header.php';
?>

<div class="content-wrapper">
    <?php if ($featuredImage): ?>
    <img src="<?= esc($featuredImage['url'] ?? '') ?>" alt="<?= esc($featuredImage['alt_text'] ?? $post['title'] ?? '') ?>" style="width: 100%; height: auto; border-radius: var(--radius-lg); margin-bottom: 2rem;">
    <?php endif; ?>
    
    <article>
        <header style="margin-bottom: 2rem;">
            <h1 style="font-size: 2.5rem; font-weight: 800; line-height: 1.2; margin-bottom: 1rem;"><?= esc($post['title'] ?? 'Untitled') ?></h1>
            <div style="display: flex; align-items: center; gap: 1.5rem; color: var(--text-secondary); font-size: 0.9375rem;">
                <?php if ($publishedDate): ?>
                <span style="display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <?= formatDate($publishedDate, getOption('date_format', 'M j, Y')) ?>
                </span>
                <?php endif; ?>
                <?php if ($author): ?>
                <span style="display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <?= esc($author['display_name'] ?? $author['username'] ?? 'Unknown') ?>
                </span>
                <?php endif; ?>
            </div>
        </header>
        
        <div class="content">
            <?= process_tags($post['content'] ?? '') ?>
        </div>
    </article>
    
    <div style="margin-top: 3rem; padding-top: 2rem; border-top: 1px solid var(--border-color);">
        <a href="<?= SITE_URL ?>" style="display: inline-flex; align-items: center; gap: 0.5rem; color: var(--primary); text-decoration: none; font-weight: 500;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Back to Home
        </a>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
