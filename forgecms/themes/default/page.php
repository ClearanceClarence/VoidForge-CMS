<?php
/**
 * Page Template - Forge CMS
 */

defined('CMS_ROOT') or die('Direct access not allowed');

$pageTitle = $post['title'] ?? 'Untitled';
$featuredImage = Post::getFeaturedImage($post);

include __DIR__ . '/header.php';
?>

<div class="content-wrapper">
    <?php if ($featuredImage): ?>
    <img src="<?= esc($featuredImage['url'] ?? '') ?>" alt="<?= esc($featuredImage['alt_text'] ?? $post['title'] ?? '') ?>" style="width: 100%; height: auto; border-radius: var(--radius-lg); margin-bottom: 2rem;">
    <?php endif; ?>
    
    <article>
        <header style="margin-bottom: 2rem;">
            <h1 style="font-size: 2.5rem; font-weight: 800; line-height: 1.2;"><?= esc($post['title'] ?? 'Untitled') ?></h1>
        </header>
        
        <div class="content">
            <?= process_tags($post['content'] ?? '') ?>
        </div>
    </article>
</div>

<?php include __DIR__ . '/footer.php'; ?>
