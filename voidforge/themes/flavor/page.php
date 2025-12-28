<?php
/**
 * Page Template
 * 
 * @package Flavor
 * @version 2.0.1
 */

get_header();

global $post;

// Comments - check if enabled for pages
$commentsEnabled = Comment::areOpen($post);
$comments = $commentsEnabled ? Comment::getForPost($post['id']) : [];
$commentCount = count($comments);
?>

<main class="content-area">
    
    <article class="single-page animate-fade-in">
        
        <header class="entry-header">
            <?php if (flavor_show_entry_title()): ?>
            <h1 class="entry-title"><?php echo esc($post['title']); ?></h1>
            <?php endif; ?>
        </header>
        
        <div class="entry-content clearfix">
            <?php echo the_content(); ?>
        </div>
        
    </article>
    
    <?php if ($commentsEnabled): ?>
    <!-- Comments Section -->
    <section class="comments-section" id="comments">
        <div class="comments-header">
            <h3>Comments</h3>
            <span class="comments-count"><?php echo $commentCount; ?></span>
        </div>
        
        <?php if (isset($_GET['comment_success'])): ?>
        <div class="comment-message comment-success">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
            Your comment has been submitted successfully!
        </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['comment_error'])): ?>
        <div class="comment-message comment-error">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <?php echo esc(urldecode($_GET['comment_error'])); ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($comments)): ?>
        <div class="comments-list">
            <?php foreach ($comments as $comment): ?>
                <?php flavor_render_comment($comment); ?>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="comments-empty">
            <div class="comments-empty-decoration">
                <span></span><span></span><span></span>
            </div>
            <div class="comments-empty-icon">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    <path d="M8 9h8" opacity="0.5"/>
                    <path d="M8 13h5" opacity="0.5"/>
                </svg>
            </div>
            <h4 class="comments-empty-title">No comments yet</h4>
            <p class="comments-empty-text">Be the first to share your thoughts on this page!</p>
        </div>
        <?php endif; ?>
        
        <!-- Comment Form -->
        <div class="comment-form-wrapper" id="respond">
            <div class="comment-form-header">
                <h4>
                    <span class="comment-form-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        </svg>
                    </span>
                    Leave a Comment
                </h4>
                <p>Your email address will not be published. Required fields are marked *</p>
            </div>
            
            <div id="reply-indicator" class="replying-to" style="display: none;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 17 4 12 9 7"/>
                    <path d="M20 18v-2a4 4 0 0 0-4-4H4"/>
                </svg>
                <span>Replying to <strong id="reply-to-name"></strong></span>
                <button type="button" onclick="cancelReply()">✕</button>
            </div>
            
            <form class="comment-form" method="post" action="<?php echo site_url('/comment-submit'); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                <input type="hidden" name="parent_id" id="comment-parent-id" value="0">
                
                <?php if (!User::isLoggedIn()): ?>
                <div class="form-grid">
                    <div class="form-row">
                        <label for="comment-name">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                            Name *
                        </label>
                        <input type="text" id="comment-name" name="author_name" required placeholder="Your name">
                    </div>
                    <div class="form-row">
                        <label for="comment-email">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                            Email *
                        </label>
                        <input type="email" id="comment-email" name="author_email" required placeholder="your@email.com">
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="form-row">
                    <label for="comment-content">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        </svg>
                        Comment *
                    </label>
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
.comment-message {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-4) var(--space-5);
    border-radius: var(--radius-lg);
    margin-bottom: var(--space-6);
    font-size: 0.9375rem;
}

.comment-success {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
}

.comment-error {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

.comments-empty {
    position: relative;
    text-align: center;
    padding: var(--space-16, 4rem) var(--space-8, 2rem);
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 50%, #e2e8f0 100%);
    border-radius: var(--radius-2xl, 1.5rem);
    margin-bottom: var(--space-8);
    border: 1px solid rgba(148, 163, 184, 0.2);
    overflow: hidden;
}

.comments-empty::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #6366f1, #8b5cf6, #a855f7);
    opacity: 0.7;
}

.comments-empty-decoration {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    display: flex;
    gap: 120px;
    pointer-events: none;
}

.comments-empty-decoration span {
    width: 200px;
    height: 200px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.03) 0%, rgba(139, 92, 246, 0.05) 100%);
}

.comments-empty-decoration span:nth-child(1) {
    transform: translate(-60px, -40px);
}

.comments-empty-decoration span:nth-child(2) {
    transform: translate(0, 20px);
}

.comments-empty-decoration span:nth-child(3) {
    transform: translate(40px, -30px);
}

.comments-empty-icon {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    border-radius: 50%;
    margin-bottom: var(--space-6, 1.5rem);
    box-shadow: 0 10px 40px rgba(99, 102, 241, 0.3);
}

.comments-empty-icon svg {
    color: #fff;
}

.comments-empty-title {
    position: relative;
    font-size: 1.375rem;
    font-weight: 700;
    color: var(--color-text-primary, #1e293b);
    margin: 0 0 var(--space-2, 0.5rem) 0;
    letter-spacing: -0.02em;
}

.comments-empty-text {
    position: relative;
    color: var(--color-text-secondary, #64748b);
    margin: 0;
    font-size: 1rem;
    max-width: 280px;
    margin-left: auto;
    margin-right: auto;
    line-height: 1.6;
}
</style>

<script>
function replyTo(commentId, authorName) {
    document.getElementById('comment-parent-id').value = commentId;
    document.getElementById('reply-to-name').textContent = authorName;
    document.getElementById('reply-indicator').style.display = 'flex';
    document.getElementById('comment-content').placeholder = 'Write your reply...';
    document.getElementById('respond').scrollIntoView({ behavior: 'smooth', block: 'center' });
    setTimeout(function() {
        document.getElementById('comment-content').focus();
    }, 300);
}

function cancelReply() {
    document.getElementById('comment-parent-id').value = '0';
    document.getElementById('reply-indicator').style.display = 'none';
    document.getElementById('comment-content').placeholder = 'Share your thoughts...';
}
</script>

<?php get_footer(); ?>
