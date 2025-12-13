<?php
/**
 * Nova Theme - Page Template
 */

defined('CMS_ROOT') or die;

if (!$post) {
    include __DIR__ . '/404.php';
    exit;
}

$bodyClass = 'single-page';
get_header();

$content = Plugin::applyFilters('the_content', $post['content'], $post);
?>

<article class="page-container fade-in">
    <header class="page-header">
        <h1><?= esc($post['title']) ?></h1>
    </header>
    
    <div class="content">
        <?= $content ?>
    </div>
</article>

<?php get_footer(); ?>
