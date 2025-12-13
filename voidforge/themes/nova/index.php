<?php
/**
 * Nova Theme - Index (Blog Listing)
 */

defined('CMS_ROOT') or die;

$bodyClass = 'blog-page';
get_header();

$siteTitle = getOption('site_title', 'VoidForge');
?>

<div class="page-container">
    <header class="page-header fade-in">
        <h1>Latest Posts</h1>
        <p class="page-meta">Stories, updates, and insights</p>
    </header>
    
    <?php if (!empty($posts)): ?>
        <div class="posts-grid">
            <?php foreach ($posts as $post): ?>
                <article class="post-card fade-in">
                    <?php if ($featuredImage = Post::featuredImage($post)): ?>
                        <div class="post-card-image">
                            <a href="<?= Post::permalink($post) ?>">
                                <img src="<?= esc($featuredImage) ?>" alt="<?= esc($post['title']) ?>">
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="post-card-content">
                        <h2 class="post-card-title">
                            <a href="<?= Post::permalink($post) ?>"><?= esc($post['title']) ?></a>
                        </h2>
                        
                        <?php if (!empty($post['excerpt'])): ?>
                            <p class="post-card-excerpt"><?= esc(truncate($post['excerpt'], 120)) ?></p>
                        <?php elseif (!empty($post['content'])): ?>
                            <p class="post-card-excerpt"><?= esc(truncate(strip_tags($post['content']), 120)) ?></p>
                        <?php endif; ?>
                        
                        <div class="post-card-meta">
                            <?= date('M j, Y', strtotime($post['created_at'])) ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        
        <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
            <nav class="pagination">
                <?php if ($pagination['current_page'] > 1): ?>
                    <a href="?page=<?= $pagination['current_page'] - 1 ?>" class="btn btn-ghost">← Previous</a>
                <?php endif; ?>
                
                <span class="pagination-info">
                    Page <?= $pagination['current_page'] ?> of <?= $pagination['total_pages'] ?>
                </span>
                
                <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                    <a href="?page=<?= $pagination['current_page'] + 1 ?>" class="btn btn-ghost">Next →</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
        
    <?php else: ?>
        <div class="empty-state">
            <h2>No posts yet</h2>
            <p>Check back soon for new content!</p>
            <a href="<?= ADMIN_URL ?>/post-edit.php?type=post" class="btn btn-primary">Create First Post</a>
        </div>
    <?php endif; ?>
</div>

<style>
.pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 1px solid var(--glass-border);
}

.pagination-info {
    color: var(--text-muted);
    font-size: 0.9375rem;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: 20px;
}

.empty-state h2 {
    font-family: 'Sora', sans-serif;
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: var(--text-muted);
    margin-bottom: 1.5rem;
}
</style>

<?php get_footer(); ?>
