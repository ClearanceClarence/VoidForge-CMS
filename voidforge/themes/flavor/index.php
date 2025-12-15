<?php
/**
 * Index / Archive Template
 * 
 * @package Flavor
 */

get_header();

$posts = Post::query([
    'post_type' => 'post',
    'status' => 'published',
    'orderby' => 'created_at',
    'order' => 'DESC',
    'limit' => 12
]);
?>

<main class="content-area wide">
    
    <header class="archive-header" style="text-align: center; margin-bottom: 48px;">
        <h1 style="font-size: 2rem; font-weight: 800; margin-bottom: 8px;">Latest Posts</h1>
        <p style="color: var(--color-text-muted);">Thoughts, stories, and ideas</p>
    </header>
    
    <?php if (!empty($posts)): ?>
    <div class="posts-grid">
        <?php foreach ($posts as $post): ?>
        <article class="post-card">
            <?php 
            $thumbnail = Post::featuredImage($post);
            if ($thumbnail):
            ?>
            <a href="<?php echo Post::permalink($post); ?>" class="post-card-image">
                <img src="<?php echo esc($thumbnail); ?>" 
                     alt="<?php echo esc($post['title']); ?>">
            </a>
            <?php endif; ?>
            
            <div class="post-card-content">
                <h2 class="post-card-title">
                    <a href="<?php echo Post::permalink($post); ?>">
                        <?php echo esc($post['title']); ?>
                    </a>
                </h2>
                
                <p class="post-card-excerpt">
                    <?php echo esc(flavor_excerpt($post, 120)); ?>
                </p>
                
                <div class="post-card-meta">
                    <?php if (flavor_show_date()): ?>
                        <?php echo flavor_date($post['created_at']); ?>
                    <?php endif; ?>
                    <?php if (flavor_show_date() && flavor_show_author()): ?> · <?php endif; ?>
                    <?php if (flavor_show_author()): ?>
                        <?php 
                        $author = User::find($post['author_id']);
                        echo esc($author['display_name'] ?? $author['username'] ?? 'Unknown');
                        ?>
                    <?php endif; ?>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="text-align: center; padding: 60px 0; color: var(--color-text-muted);">
        <p>No posts found.</p>
    </div>
    <?php endif; ?>
    
</main>

<?php get_footer(); ?>
