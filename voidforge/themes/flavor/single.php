<?php
/**
 * Single Post Template
 * 
 * @package Flavor
 */

get_header();

global $post;

$author = User::find($post['author_id']);
$readTime = flavor_reading_time($post);
?>

<main class="content-area">
    
    <article class="single-post">
        
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
        
        <?php 
        $thumbnail = Post::featuredImage($post);
        if ($thumbnail):
        ?>
        <figure class="entry-thumbnail" style="margin-bottom: 48px;">
            <img src="<?php echo esc($thumbnail); ?>" 
                 alt="<?php echo esc($post['title']); ?>"
                 style="width: 100%; border-radius: var(--radius-lg); box-shadow: var(--shadow-lg);">
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
        <footer class="entry-footer" style="margin-top: 48px; padding-top: 24px; border-top: 1px solid var(--color-border);">
            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                <?php foreach ($terms as $term): ?>
                <a href="<?php echo Taxonomy::getTermUrl($term); ?>" 
                   style="display: inline-block; padding: 4px 12px; font-size: 0.8125rem; font-weight: 500; 
                          background: var(--color-bg-alt); color: var(--color-text-muted); 
                          border-radius: 100px; transition: all 0.2s;">
                    <?php echo esc($term['name']); ?>
                </a>
                <?php endforeach; ?>
            </div>
        </footer>
        <?php endif; ?>
        
    </article>
    
    <?php
    // Previous/Next navigation
    $prevPost = Post::getAdjacent($post['id'], 'prev', $post['post_type']);
    $nextPost = Post::getAdjacent($post['id'], 'next', $post['post_type']);
    
    if ($prevPost || $nextPost):
    ?>
    <nav class="post-navigation" style="margin-top: 64px; display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
        <?php if ($prevPost): ?>
        <a href="<?php echo Post::permalink($prevPost); ?>" 
           style="padding: 24px; background: var(--color-bg-alt); border-radius: var(--radius-lg); 
                  text-decoration: none; transition: all 0.2s;"
           onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='var(--shadow-lg)'"
           onmouseout="this.style.transform='';this.style.boxShadow=''">
            <span style="display: block; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; 
                         letter-spacing: 0.05em; color: var(--color-text-light); margin-bottom: 8px;">
                ← Previous
            </span>
            <span style="display: block; font-weight: 600; color: var(--color-text);">
                <?php echo esc($prevPost['title']); ?>
            </span>
        </a>
        <?php else: ?>
        <div></div>
        <?php endif; ?>
        
        <?php if ($nextPost): ?>
        <a href="<?php echo Post::permalink($nextPost); ?>" 
           style="padding: 24px; background: var(--color-bg-alt); border-radius: var(--radius-lg); 
                  text-decoration: none; text-align: right; transition: all 0.2s;"
           onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='var(--shadow-lg)'"
           onmouseout="this.style.transform='';this.style.boxShadow=''">
            <span style="display: block; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; 
                         letter-spacing: 0.05em; color: var(--color-text-light); margin-bottom: 8px;">
                Next →
            </span>
            <span style="display: block; font-weight: 600; color: var(--color-text);">
                <?php echo esc($nextPost['title']); ?>
            </span>
        </a>
        <?php endif; ?>
    </nav>
    <?php endif; ?>
    
    <?php
    // Comments Section
    if (Comment::areOpen($post)):
        $comments = Comment::getForPost($post['id'], ['parent_id' => 0, 'status' => Comment::STATUS_APPROVED]);
        $commentCount = (int) ($post['comment_count'] ?? 0);
    ?>
    <section class="comments-section" id="comments">
        <style>
        .comments-section {
            margin-top: 64px;
            padding-top: 48px;
            border-top: 1px solid var(--color-border);
        }
        
        .comments-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 32px;
        }
        
        .comments-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
        }
        
        .comments-header .comment-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 28px;
            height: 28px;
            padding: 0 8px;
            background: var(--color-accent);
            color: white;
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: 14px;
        }
        
        .comments-list {
            margin-bottom: 48px;
        }
        
        /* Individual Comment */
        .comment {
            position: relative;
            margin-bottom: 24px;
            padding: 24px;
            background: var(--color-bg-alt);
            border-radius: 16px;
            border: 1px solid var(--color-border);
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        .comment:hover {
            border-color: rgba(99, 102, 241, 0.3);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }
        
        .comment.reply {
            margin-left: 48px;
            background: var(--color-bg);
            border-left: 3px solid var(--color-accent);
        }
        
        .comment.reply.depth-2 {
            margin-left: 96px;
        }
        
        .comment.reply.depth-3 {
            margin-left: 120px;
        }
        
        @media (max-width: 640px) {
            .comment.reply,
            .comment.reply.depth-2,
            .comment.reply.depth-3 {
                margin-left: 16px;
            }
        }
        
        .comment-inner {
            display: flex;
            gap: 16px;
        }
        
        .comment-avatar {
            flex-shrink: 0;
        }
        
        .comment-avatar img {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            border: 2px solid var(--color-border);
            transition: border-color 0.2s;
        }
        
        .comment:hover .comment-avatar img {
            border-color: var(--color-accent);
        }
        
        .comment-body {
            flex: 1;
            min-width: 0;
        }
        
        .comment-meta {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px 12px;
            margin-bottom: 12px;
        }
        
        .comment-author {
            font-weight: 600;
            color: var(--color-text);
            font-size: 1rem;
        }
        
        .comment-date {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.8125rem;
            color: var(--color-text-light);
        }
        
        .comment-date svg {
            width: 14px;
            height: 14px;
            opacity: 0.7;
        }
        
        .comment-content {
            line-height: 1.7;
            color: var(--color-text);
        }
        
        .comment-content p {
            margin: 0 0 12px 0;
        }
        
        .comment-content p:last-child {
            margin-bottom: 0;
        }
        
        .comment-actions {
            display: flex;
            gap: 16px;
            margin-top: 16px;
            padding-top: 12px;
            border-top: 1px solid var(--color-border);
        }
        
        .comment-reply-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--color-accent);
            background: transparent;
            border: 1px solid var(--color-accent);
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .comment-reply-btn:hover {
            background: var(--color-accent);
            color: white;
        }
        
        .comment-reply-btn svg {
            width: 14px;
            height: 14px;
        }
        
        /* Comment Form */
        .comment-form-wrap {
            background: var(--color-bg-alt);
            border-radius: 16px;
            padding: 32px;
            border: 1px solid var(--color-border);
        }
        
        .comment-form-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }
        
        .comment-form-header .icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--color-accent) 0%, #8b5cf6 100%);
            border-radius: 12px;
        }
        
        .comment-form-header .icon svg {
            width: 24px;
            height: 24px;
            color: white;
        }
        
        .comment-form-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }
        
        .comment-form-header p {
            font-size: 0.875rem;
            color: var(--color-text-light);
            margin: 4px 0 0 0;
        }
        
        .comment-alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9375rem;
        }
        
        .comment-alert svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }
        
        .comment-alert.error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }
        
        .comment-alert.success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
        }
        
        .comment-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .comment-form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        
        @media (max-width: 640px) {
            .comment-form-row {
                grid-template-columns: 1fr;
            }
        }
        
        .comment-form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .comment-form-group label {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--color-text);
        }
        
        .comment-form-group label span {
            color: var(--color-accent);
        }
        
        .comment-form-group input,
        .comment-form-group textarea {
            width: 100%;
            padding: 12px 16px;
            font-size: 1rem;
            color: var(--color-text);
            background: var(--color-bg);
            border: 1px solid var(--color-border);
            border-radius: 10px;
            transition: all 0.2s;
            font-family: inherit;
        }
        
        .comment-form-group input:focus,
        .comment-form-group textarea:focus {
            outline: none;
            border-color: var(--color-accent);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }
        
        .comment-form-group input::placeholder,
        .comment-form-group textarea::placeholder {
            color: var(--color-text-light);
        }
        
        .comment-form-group textarea {
            resize: vertical;
            min-height: 140px;
        }
        
        .comment-form-footer {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        
        .comment-submit-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 28px;
            font-size: 1rem;
            font-weight: 600;
            color: white;
            background: linear-gradient(135deg, var(--color-accent) 0%, #8b5cf6 100%);
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        }
        
        .comment-submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
        }
        
        .comment-submit-btn svg {
            width: 18px;
            height: 18px;
        }
        
        .comment-cancel-reply {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 16px;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--color-text-muted);
            background: var(--color-bg);
            border: 1px solid var(--color-border);
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .comment-cancel-reply:hover {
            border-color: #ef4444;
            color: #ef4444;
        }
        
        .comment-cancel-reply svg {
            width: 14px;
            height: 14px;
        }
        
        /* Empty State */
        .comments-empty {
            text-align: center;
            padding: 48px 24px;
            background: var(--color-bg-alt);
            border-radius: 16px;
            margin-bottom: 32px;
        }
        
        .comments-empty-icon {
            width: 72px;
            height: 72px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
            border-radius: 50%;
            margin: 0 auto 16px;
        }
        
        .comments-empty-icon svg {
            width: 32px;
            height: 32px;
            color: var(--color-accent);
        }
        
        .comments-empty h4 {
            font-size: 1.125rem;
            font-weight: 600;
            margin: 0 0 8px 0;
        }
        
        .comments-empty p {
            font-size: 0.9375rem;
            color: var(--color-text-light);
            margin: 0;
        }
        </style>
        
        <div class="comments-header">
            <h2>Comments</h2>
            <span class="comment-count"><?= $commentCount ?></span>
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
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
            </div>
            <h4>No comments yet</h4>
            <p>Be the first to share your thoughts!</p>
        </div>
        <?php endif; ?>
        
        <!-- Comment Form -->
        <div class="comment-form-wrap" id="respond">
            <div class="comment-form-header">
                <div class="icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
                    </svg>
                </div>
                <div>
                    <h3 id="comment-form-title">Leave a Comment</h3>
                    <p>Your email address will not be published.</p>
                </div>
            </div>
            
            <?php if (isset($_GET['comment_error'])): ?>
            <div class="comment-alert error">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="15" y1="9" x2="9" y2="15"/>
                    <line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
                <?= esc(urldecode($_GET['comment_error'])) ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['comment_success'])): ?>
            <div class="comment-alert success">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                Your comment has been submitted<?= getOption('comment_moderation', true) ? ' and is awaiting moderation' : '' ?>.
            </div>
            <?php endif; ?>
            
            <form method="POST" action="<?= site_url('/comment-submit') ?>" class="comment-form">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                <input type="hidden" name="parent_id" id="comment_parent_id" value="0">
                
                <?php 
                $currentUser = User::current();
                if (!$currentUser): 
                ?>
                <div class="comment-form-row">
                    <div class="comment-form-group">
                        <label for="author_name">Name <span>*</span></label>
                        <input type="text" name="author_name" id="author_name" placeholder="Your name" required>
                    </div>
                    <div class="comment-form-group">
                        <label for="author_email">Email <span>*</span></label>
                        <input type="email" name="author_email" id="author_email" placeholder="your@email.com" required>
                    </div>
                </div>
                <?php else: ?>
                <div class="comment-form-group" style="padding: 12px 16px; background: var(--color-bg); border-radius: 10px; display: flex; align-items: center; gap: 12px;">
                    <img src="<?= esc(Comment::getGravatar(['author_email' => $currentUser['email']], 36)) ?>" 
                         alt="" style="width: 36px; height: 36px; border-radius: 50%;">
                    <div>
                        <div style="font-weight: 600; font-size: 0.9375rem;"><?= esc($currentUser['display_name'] ?? $currentUser['username']) ?></div>
                        <div style="font-size: 0.8125rem; color: var(--color-text-light);">Logged in</div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="comment-form-group">
                    <label for="comment_content">Comment <span>*</span></label>
                    <textarea name="content" id="comment_content" rows="5" placeholder="Write your comment here..." required></textarea>
                </div>
                
                <div class="comment-form-footer">
                    <button type="submit" class="comment-submit-btn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="22" y1="2" x2="11" y2="13"/>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                        </svg>
                        Post Comment
                    </button>
                    <a href="#respond" id="cancel-reply" class="comment-cancel-reply" onclick="cancelReply()" style="display: none;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                        Cancel Reply
                    </a>
                </div>
            </form>
        </div>
        
        <script>
        function replyTo(commentId, authorName) {
            document.getElementById('comment_parent_id').value = commentId;
            document.getElementById('cancel-reply').style.display = 'inline-flex';
            document.getElementById('comment-form-title').textContent = 'Reply to ' + authorName;
            document.getElementById('respond').scrollIntoView({behavior: 'smooth', block: 'center'});
            document.getElementById('comment_content').focus();
        }
        function cancelReply() {
            document.getElementById('comment_parent_id').value = '0';
            document.getElementById('cancel-reply').style.display = 'none';
            document.getElementById('comment-form-title').textContent = 'Leave a Comment';
        }
        </script>
    </section>
    <?php endif; ?>
    
</main>

<?php get_footer(); ?>
