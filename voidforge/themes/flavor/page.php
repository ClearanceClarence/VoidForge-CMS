<?php
/**
 * Page Template - Flavor Theme
 */

defined('CMS_ROOT') or die;

// $post should be passed from the router
if (!isset($post) || !$post) {
    include Theme::getTemplate('404');
    return;
}

$pageTitle = $post['title'] . ' — ' . getOption('site_title', 'VoidForge');
$pageDescription = $post['excerpt'] ?? flavor_excerpt($post['content'], 160);
$featuredImage = $post['featured_image'] ?? null;

// Process content
$content = $post['content'];
$content = Plugin::applyFilters('the_content', $content);
$content = do_shortcode($content);

get_header();
?>

<article class="single-page">
    <div class="container container-narrow">
        <header class="page-header">
            <h1 class="page-title"><?= esc($post['title']) ?></h1>
            
            <?php if (User::isLoggedIn() && (User::isAdmin() || User::current()['id'] === $post['author_id'])): ?>
            <div class="page-meta">
                <a href="<?= ADMIN_URL ?>/post-edit.php?id=<?= $post['id'] ?>" class="edit-link">Edit page</a>
            </div>
            <?php endif; ?>
        </header>
        
        <?php if ($featuredImage): ?>
        <figure class="page-featured-image">
            <img src="<?= esc($featuredImage) ?>" alt="<?= esc($post['title']) ?>">
        </figure>
        <?php endif; ?>
        
        <div class="page-content prose">
            <?= $content ?>
        </div>
    </div>
</article>

<?php get_footer(); ?>
