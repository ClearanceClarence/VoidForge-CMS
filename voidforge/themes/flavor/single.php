<?php
/**
 * Single Post Template
 * 
 * @package Flavor
 * @version 2.0.0
 */

get_header();

global $post;
$author = User::find($post['author_id']);
$readTime = flavor_reading_time($post);
$thumbnail = Post::featuredImage($post);

// Comments
$commentsEnabled = Comment::areOpen($post);
$comments = $commentsEnabled ? Comment::getForPost($post['id']) : [];
$commentCount = count($comments);
?>

<main class="content-area">
    
    <article class="single-post animate-fade-in">
        
        <header class="entry-header">
            <?php if (flavor_show_entry_title()): ?>
            <h1 class="entry-title"><?php echo esc($post['title']); ?></h1>
            <?php endif; ?>
            
            <?php if (flavor_show_entry_meta()): ?>
            <div class="entry-meta">
                <?php if (flavor_show_date()): ?>
                <span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    <?php echo flavor_date($post['created_at']); ?>
                </span>
                <?php endif; ?>
                
                <?php if (flavor_show_author() && $author): ?>
                <span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    <?php echo esc($author['display_name'] ?? $author['username']); ?>
                </span>
                <?php endif; ?>
                
                <span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <?php echo $readTime; ?> min read
                </span>
            </div>
            <?php endif; ?>
        </header>
        
        <?php if ($thumbnail): ?>
        <figure class="entry-thumbnail">
            <img src="<?php echo esc($thumbnail); ?>" alt="<?php echo esc($post['title']); ?>">
        </figure>
        <?php endif; ?>
        
        <div class="entry-content clearfix">
            <?php echo the_content(); ?>
        </div>
        
        <?php
        // Get post tags/categories if available
        $terms = Taxonomy::getPostTerms($post['id']);
        if (!empty($terms)):
        ?>
        <footer class="entry-footer">
            <div class="entry-tags">
                <?php foreach ($terms as $term): ?>
                <a href="<?php echo Taxonomy::getTermUrl($term); ?>" class="entry-tag">
                    <?php echo esc($term['name']); ?>
                </a>
                <?php endforeach; ?>
            </div>
        </footer>
        <?php endif; ?>
        
    </article>
    
    <?php if ($commentsEnabled): ?>
    <!-- Comments Section -->
    <section class="comments-section" id="comments">
        <div class="comments-header">
            <h3>Comments</h3>
            <span class="comments-count"><?php echo $commentCount; ?></span>
        </div>
        
        <?php if (!empty($comments)): ?>
        <div class="comments-list">
            <?php foreach ($comments as $comment): ?>
                <?php flavor_render_comment($comment); ?>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="comments-empty">
            <div class="comments-empty-icon">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
            </div>
            <p>No comments yet. Be the first to share your thoughts!</p>
        </div>
        <?php endif; ?>
        
        <!-- Comment Form -->
        <div class="comment-form-wrapper" id="respond">
            <div class="comment-form-header">
                <h4>Leave a Comment</h4>
                <p>Your email address will not be published.</p>
            </div>
            
            <form class="comment-form" method="post" action="<?php echo site_url('/comments/submit'); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                <input type="hidden" name="parent_id" id="comment-parent-id" value="0">
                
                <?php if (!User::isLoggedIn()): ?>
                <div class="form-grid">
                    <div class="form-row">
                        <label for="comment-name">Name *</label>
                        <input type="text" id="comment-name" name="author_name" required placeholder="Your name">
                    </div>
                    <div class="form-row">
                        <label for="comment-email">Email *</label>
                        <input type="email" id="comment-email" name="author_email" required placeholder="your@email.com">
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="form-row">
                    <label for="comment-content">Comment *</label>
                    <textarea id="comment-content" name="content" required placeholder="Share your thoughts..."></textarea>
                </div>
                
                <button type="submit">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="22" y1="2" x2="11" y2="13"/>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                    </svg>
                    Post Comment
                </button>
            </form>
        </div>
    </section>
    <?php endif; ?>
    
</main>

<style>
.comments-empty {
    text-align: center;
    padding: var(--space-12) var(--space-6);
    background: var(--color-bg-subtle);
    border-radius: var(--radius-xl);
    margin-bottom: var(--space-8);
}

.comments-empty-icon {
    color: var(--color-text-muted);
    margin-bottom: var(--space-4);
}

.comments-empty p {
    color: var(--color-text-muted);
    margin: 0;
}
</style>

<script>
function replyTo(commentId, authorName) {
    document.getElementById('comment-parent-id').value = commentId;
    document.getElementById('comment-content').placeholder = 'Reply to ' + authorName + '...';
    document.getElementById('comment-content').focus();
}
</script>

<?php get_footer(); ?>
