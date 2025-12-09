<?php
/**
 * Single Post Template - Flavor Theme
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

<article class="single-post">
    <div class="container container-narrow">
        <header class="post-header">
            <h1 class="post-title"><?= esc($post['title']) ?></h1>
            
            <div class="post-meta">
                <time datetime="<?= $post['created_at'] ?>"><?= flavor_date($post['created_at']) ?></time>
                <span class="meta-sep">·</span>
                <span class="reading-time"><?= flavor_reading_time($post['content']) ?></span>
                <?php if (User::isLoggedIn() && (User::isAdmin() || User::current()['id'] === $post['author_id'])): ?>
                <span class="meta-sep">·</span>
                <a href="<?= ADMIN_URL ?>/post-edit.php?id=<?= $post['id'] ?>" class="edit-link">Edit</a>
                <?php endif; ?>
            </div>
        </header>
        
        <?php if ($featuredImage): ?>
        <figure class="post-featured-image">
            <img src="<?= esc($featuredImage) ?>" alt="<?= esc($post['title']) ?>">
        </figure>
        <?php endif; ?>
        
        <div class="post-content prose">
            <?= $content ?>
        </div>
        
        <footer class="post-footer">
            <a href="<?= SITE_URL ?>" class="back-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back to all posts
            </a>
        </footer>
    </div>
</article>

<?php get_footer(); ?>
