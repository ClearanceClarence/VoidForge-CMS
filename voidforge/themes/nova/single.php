<?php
/**
 * Nova Theme - Single Post
 */

defined('CMS_ROOT') or die;

if (!$post) {
    include __DIR__ . '/404.php';
    exit;
}

$bodyClass = 'single-post';
get_header();

$content = Plugin::applyFilters('the_content', $post['content'], $post);
$featuredImage = Post::featuredImage($post);
?>

<article class="page-container single-article fade-in">
    <header class="article-header">
        <?php if ($featuredImage): ?>
            <div class="article-image">
                <img src="<?= esc($featuredImage) ?>" alt="<?= esc($post['title']) ?>">
            </div>
        <?php endif; ?>
        
        <h1><?= esc($post['title']) ?></h1>
        
        <div class="article-meta">
            <time datetime="<?= date('c', strtotime($post['created_at'])) ?>">
                <?= date('F j, Y', strtotime($post['created_at'])) ?>
            </time>
            
            <?php
            $categories = Taxonomy::getPostTerms($post['id'], 'category');
            if (!empty($categories)):
            ?>
                <span class="meta-sep">·</span>
                <span class="article-categories">
                    <?php foreach ($categories as $i => $cat): ?>
                        <a href="<?= SITE_URL ?>/category/<?= esc($cat['slug']) ?>"><?= esc($cat['name']) ?></a><?= $i < count($categories) - 1 ? ', ' : '' ?>
                    <?php endforeach; ?>
                </span>
            <?php endif; ?>
        </div>
    </header>
    
    <div class="content">
        <?= $content ?>
    </div>
    
    <?php
    $tags = Taxonomy::getPostTerms($post['id'], 'tag');
    if (!empty($tags)):
    ?>
        <footer class="article-footer">
            <div class="article-tags">
                <?php foreach ($tags as $tag): ?>
                    <a href="<?= SITE_URL ?>/tag/<?= esc($tag['slug']) ?>" class="tag"><?= esc($tag['name']) ?></a>
                <?php endforeach; ?>
            </div>
        </footer>
    <?php endif; ?>
    
    <nav class="post-navigation">
        <?php
        $prevPost = Post::getAdjacent($post['id'], 'prev', $post['post_type']);
        $nextPost = Post::getAdjacent($post['id'], 'next', $post['post_type']);
        ?>
        
        <?php if ($prevPost): ?>
            <a href="<?= Post::permalink($prevPost) ?>" class="post-nav-link post-nav-prev">
                <span class="nav-label">← Previous</span>
                <span class="nav-title"><?= esc(truncate($prevPost['title'], 40)) ?></span>
            </a>
        <?php endif; ?>
        
        <?php if ($nextPost): ?>
            <a href="<?= Post::permalink($nextPost) ?>" class="post-nav-link post-nav-next">
                <span class="nav-label">Next →</span>
                <span class="nav-title"><?= esc(truncate($nextPost['title'], 40)) ?></span>
            </a>
        <?php endif; ?>
    </nav>
</article>

<style>
.single-article { max-width: 800px; }

.article-header { margin-bottom: 2.5rem; text-align: center; }

.article-image {
    margin: -3rem -2rem 2rem;
    border-radius: 16px;
    overflow: hidden;
}

.article-image img {
    width: 100%;
    height: auto;
    display: block;
}

.article-header h1 {
    font-family: 'Sora', sans-serif;
    font-size: clamp(2rem, 5vw, 2.75rem);
    font-weight: 700;
    letter-spacing: -0.02em;
    line-height: 1.2;
    margin-bottom: 1rem;
}

.article-meta {
    color: var(--text-muted);
    font-size: 0.9375rem;
}

.article-meta a {
    color: var(--nova-cyan);
    text-decoration: none;
}

.meta-sep { margin: 0 0.5rem; }

.article-footer {
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 1px solid var(--glass-border);
}

.article-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.tag {
    display: inline-block;
    padding: 0.375rem 0.875rem;
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: 100px;
    color: var(--text-secondary);
    font-size: 0.8125rem;
    text-decoration: none;
    transition: all 0.2s;
}

.tag:hover {
    background: rgba(255,255,255,0.08);
    color: var(--text);
}

.post-navigation {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 1px solid var(--glass-border);
}

.post-nav-link {
    display: block;
    padding: 1.25rem;
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: 12px;
    text-decoration: none;
    transition: all 0.2s;
}

.post-nav-link:hover {
    background: rgba(255,255,255,0.06);
    border-color: rgba(255,255,255,0.12);
}

.post-nav-next { text-align: right; }

.nav-label {
    display: block;
    font-size: 0.75rem;
    color: var(--text-muted);
    margin-bottom: 0.25rem;
}

.nav-title {
    display: block;
    color: var(--text);
    font-weight: 500;
}

@media (max-width: 768px) {
    .article-image { margin: -2rem -1.5rem 1.5rem; }
    .post-navigation { grid-template-columns: 1fr; }
}
</style>

<?php get_footer(); ?>
