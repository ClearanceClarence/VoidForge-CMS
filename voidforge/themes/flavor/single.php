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
            <h1 class="entry-title"><?php echo esc($post['title']); ?></h1>
            
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
    <section class="comments-section" id="comments" style="margin-top: 64px; padding-top: 48px; border-top: 1px solid var(--color-border);">
        
        <h2 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 32px;">
            <?php echo $commentCount; ?> Comment<?php echo $commentCount !== 1 ? 's' : ''; ?>
        </h2>
        
        <?php if (!empty($comments)): ?>
        <div class="comments-list" style="margin-bottom: 48px;">
            <?php foreach ($comments as $comment): ?>
            <?php flavor_render_comment($comment); ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Comment Form -->
        <div class="comment-form-wrap" id="respond">
            <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 24px;">Leave a Comment</h3>
            
            <?php if (isset($_GET['comment_error'])): ?>
            <div style="padding: 12px 16px; background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; border-radius: var(--radius); margin-bottom: 24px;">
                <?php echo esc(urldecode($_GET['comment_error'])); ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['comment_success'])): ?>
            <div style="padding: 12px 16px; background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; border-radius: var(--radius); margin-bottom: 24px;">
                Your comment has been submitted<?php echo getOption('comment_moderation', true) ? ' and is awaiting moderation' : ''; ?>.
            </div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo site_url('/comment-submit'); ?>" style="display: flex; flex-direction: column; gap: 16px;">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                <input type="hidden" name="parent_id" id="comment_parent_id" value="0">
                
                <?php 
                $currentUser = User::current();
                if (!$currentUser): 
                ?>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div>
                        <label for="author_name" style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 6px;">Name *</label>
                        <input type="text" name="author_name" id="author_name" required
                               style="width: 100%; padding: 10px 14px; border: 1px solid var(--color-border); border-radius: var(--radius); font-size: 1rem;">
                    </div>
                    <div>
                        <label for="author_email" style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 6px;">Email *</label>
                        <input type="email" name="author_email" id="author_email" required
                               style="width: 100%; padding: 10px 14px; border: 1px solid var(--color-border); border-radius: var(--radius); font-size: 1rem;">
                    </div>
                </div>
                <?php endif; ?>
                
                <div>
                    <label for="comment_content" style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 6px;">Comment *</label>
                    <textarea name="content" id="comment_content" rows="5" required
                              style="width: 100%; padding: 10px 14px; border: 1px solid var(--color-border); border-radius: var(--radius); font-size: 1rem; resize: vertical;"></textarea>
                </div>
                
                <div>
                    <button type="submit" 
                            style="padding: 12px 24px; background: var(--color-accent); color: white; font-weight: 600; 
                                   border: none; border-radius: var(--radius); cursor: pointer; font-size: 1rem;">
                        Post Comment
                    </button>
                    <span id="cancel-reply" style="display: none; margin-left: 12px;">
                        <a href="#respond" onclick="cancelReply()" style="color: var(--color-text-muted); text-decoration: none;">Cancel Reply</a>
                    </span>
                </div>
            </form>
        </div>
        
        <script>
        function replyTo(commentId, authorName) {
            document.getElementById('comment_parent_id').value = commentId;
            document.getElementById('cancel-reply').style.display = 'inline';
            document.querySelector('.comment-form-wrap h3').textContent = 'Reply to ' + authorName;
            document.getElementById('respond').scrollIntoView({behavior: 'smooth'});
        }
        function cancelReply() {
            document.getElementById('comment_parent_id').value = '0';
            document.getElementById('cancel-reply').style.display = 'none';
            document.querySelector('.comment-form-wrap h3').textContent = 'Leave a Comment';
        }
        </script>
    </section>
    <?php endif; ?>
    
</main>

<?php get_footer(); ?>
