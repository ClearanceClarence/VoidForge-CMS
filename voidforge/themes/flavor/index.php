<?php
/**
 * Archive / Index Template
 * 
 * @package Flavor
 * @version 2.0.0
 */

get_header();

// Get posts
$posts = Post::query([
    'post_type' => 'post',
    'status' => 'published',
    'limit' => 12,
    'orderby' => 'created_at',
    'order' => 'DESC'
]);
?>

<main class="content-area wide">
    
    <header class="archive-header animate-fade-in">
        <h1 class="archive-title">Blog</h1>
        <p class="archive-description">
            Thoughts, ideas, and stories from our team.
        </p>
    </header>
    
    <?php if (!empty($posts)): ?>
    <div class="posts-grid animate-slide-up">
        <?php foreach ($posts as $post): 
            $author = User::find($post['author_id']);
            $thumbnail = Post::featuredImage($post);
            $readTime = flavor_reading_time($post);
        ?>
        <article class="card">
            <?php if ($thumbnail): ?>
            <div class="card-image">
                <a href="<?php echo Post::permalink($post); ?>">
                    <img src="<?php echo esc($thumbnail); ?>" alt="<?php echo esc($post['title']); ?>">
                </a>
            </div>
            <?php endif; ?>
            
            <div class="card-content">
                <div class="card-meta">
                    <span><?php echo flavor_date($post['created_at']); ?></span>
                    <span>&middot;</span>
                    <span><?php echo $readTime; ?> min read</span>
                </div>
                
                <h2 class="card-title">
                    <a href="<?php echo Post::permalink($post); ?>">
                        <?php echo esc($post['title']); ?>
                    </a>
                </h2>
                
                <p class="card-excerpt">
                    <?php echo esc(flavor_excerpt($post, 140)); ?>
                </p>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-icon">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/>
            </svg>
        </div>
        <h2>No Posts Yet</h2>
        <p>Check back soon for new content!</p>
    </div>
    <?php endif; ?>
    
</main>

<style>
.empty-state {
    text-align: center;
    padding: var(--space-24) var(--space-6);
}

.empty-icon {
    color: var(--color-text-muted);
    margin-bottom: var(--space-6);
}

.empty-state h2 {
    font-size: var(--text-2xl);
    margin-bottom: var(--space-2);
}

.empty-state p {
    color: var(--color-text-muted);
}
</style>

<?php get_footer(); ?>
