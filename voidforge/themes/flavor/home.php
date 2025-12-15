<?php
/**
 * Home Template - Anvil Block Editor Showcase
 * 
 * @package Flavor
 */

get_header();

$settings = flavor_get_settings();
?>

<main>
    
    <!-- Hero Section -->
    <section class="hero" style="padding: 100px 24px; background: var(--color-accent); color: white; text-align: center;">
        <div style="max-width: 800px; margin: 0 auto;">
            <h1 style="font-size: clamp(2.5rem, 8vw, 4.5rem); font-weight: 800; line-height: 1.1; letter-spacing: -0.03em; margin-bottom: 24px;">
                Anvil Block Editor
            </h1>
            <p style="font-size: 1.35rem; opacity: 0.9; line-height: 1.6; margin-bottom: 32px;">
                A powerful, intuitive block-based content editor with 15 block types for creating beautiful, structured content.
            </p>
            <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
                <a href="<?php echo site_url('/admin'); ?>" style="display: inline-flex; align-items: center; justify-content: center; padding: 12px 24px; font-weight: 600; border-radius: var(--radius); text-decoration: none; transition: all 0.2s; background: white; color: var(--color-accent);">
                    Open Editor
                </a>
                <a href="#blocks" style="display: inline-flex; align-items: center; justify-content: center; padding: 12px 24px; font-weight: 600; border-radius: var(--radius); text-decoration: none; transition: all 0.2s; border: 2px solid white; color: white; background: transparent;">
                    View Blocks ↓
                </a>
            </div>
        </div>
    </section>

    <!-- Block Categories Overview -->
    <section style="padding: 80px 24px; background: var(--color-bg);">
        <div style="max-width: 1100px; margin: 0 auto;">
            <h2 class="anvil-block-heading" style="text-align: center; margin-bottom: 16px;">15 Block Types. Endless Possibilities.</h2>
            <p class="anvil-block-paragraph" style="text-align: center; color: var(--color-text-muted); max-width: 600px; margin: 0 auto 48px;">
                From simple text to complex layouts, Anvil provides everything you need to create professional content.
            </p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px;">
                <!-- Text Blocks -->
                <div style="padding: 32px; background: var(--color-bg-alt); border-radius: var(--radius-lg); border: 1px solid var(--color-border);">
                    <div style="width: 48px; height: 48px; background: var(--color-accent-light); border-radius: var(--radius); display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-accent)" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
                    </div>
                    <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 8px;">Text Blocks</h3>
                    <p style="font-size: 0.9375rem; color: var(--color-text-muted); line-height: 1.6;">
                        Paragraph, Heading, List, Quote, Code, Table
                    </p>
                </div>
                
                <!-- Media Blocks -->
                <div style="padding: 32px; background: var(--color-bg-alt); border-radius: var(--radius-lg); border: 1px solid var(--color-border);">
                    <div style="width: 48px; height: 48px; background: var(--color-accent-light); border-radius: var(--radius); display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-accent)" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                    </div>
                    <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 8px;">Media Blocks</h3>
                    <p style="font-size: 0.9375rem; color: var(--color-text-muted); line-height: 1.6;">
                        Image, Gallery, Video
                    </p>
                </div>
                
                <!-- Layout Blocks -->
                <div style="padding: 32px; background: var(--color-bg-alt); border-radius: var(--radius-lg); border: 1px solid var(--color-border);">
                    <div style="width: 48px; height: 48px; background: var(--color-accent-light); border-radius: var(--radius); display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-accent)" stroke-width="2"><rect x="3" y="3" width="7" height="18" rx="1"/><rect x="14" y="3" width="7" height="18" rx="1"/></svg>
                    </div>
                    <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 8px;">Layout Blocks</h3>
                    <p style="font-size: 0.9375rem; color: var(--color-text-muted); line-height: 1.6;">
                        Columns, Spacer, Separator, Button
                    </p>
                </div>
                
                <!-- Embed Blocks -->
                <div style="padding: 32px; background: var(--color-bg-alt); border-radius: var(--radius-lg); border: 1px solid var(--color-border);">
                    <div style="width: 48px; height: 48px; background: var(--color-accent-light); border-radius: var(--radius); display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-accent)" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                    </div>
                    <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 8px;">Embed Blocks</h3>
                    <p style="font-size: 0.9375rem; color: var(--color-text-muted); line-height: 1.6;">
                        HTML, Embed (YouTube, Vimeo, etc.)
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Block Showcase -->
    <section id="blocks" style="padding: 80px 24px; background: var(--color-bg-alt);">
        <div style="max-width: var(--content-width); margin: 0 auto;">
            <h2 class="anvil-block-heading" style="text-align: center; margin-bottom: 48px;">Block Showcase</h2>
            
            <!-- Paragraph with Drop Cap -->
            <div style="margin-bottom: 64px;">
                <span style="display: inline-block; padding: 4px 12px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; background: var(--color-accent); color: white; border-radius: 100px; margin-bottom: 16px;">Paragraph Block</span>
                <p class="anvil-block-paragraph has-drop-cap">
                    The paragraph block supports drop caps, text alignment, and rich inline formatting. It's the foundation of all written content, designed with beautiful typography using the Merriweather serif font for optimal readability. Every paragraph flows naturally with carefully tuned line height and spacing.
                </p>
            </div>
            
            <!-- Headings -->
            <div style="margin-bottom: 64px;">
                <span style="display: inline-block; padding: 4px 12px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; background: var(--color-accent); color: white; border-radius: 100px; margin-bottom: 16px;">Heading Block</span>
                <h1 class="anvil-block-heading">Heading Level 1</h1>
                <h2 class="anvil-block-heading">Heading Level 2</h2>
                <h3 class="anvil-block-heading">Heading Level 3</h3>
                <h4 class="anvil-block-heading">Heading Level 4</h4>
                <h5 class="anvil-block-heading">Heading Level 5</h5>
                <h6 class="anvil-block-heading">Heading Level 6</h6>
            </div>
            
            <!-- Quote -->
            <div style="margin-bottom: 64px;">
                <span style="display: inline-block; padding: 4px 12px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; background: var(--color-accent); color: white; border-radius: 100px; margin-bottom: 16px;">Quote Block</span>
                <blockquote class="anvil-block-quote">
                    "The block editor transforms how we create content. It's intuitive, powerful, and beautiful. Every piece of content becomes a composition of carefully crafted blocks."
                    <cite>VoidForge Team</cite>
                </blockquote>
            </div>
            
            <!-- List -->
            <div style="margin-bottom: 64px;">
                <span style="display: inline-block; padding: 4px 12px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; background: var(--color-accent); color: white; border-radius: 100px; margin-bottom: 16px;">List Block</span>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px;">
                    <div>
                        <h4 style="font-size: 0.875rem; font-weight: 600; margin-bottom: 12px; color: var(--color-text-muted);">Unordered List</h4>
                        <ul class="anvil-block-list">
                            <li>Drag and drop block reordering</li>
                            <li>Real-time content preview</li>
                            <li>Inline formatting toolbar</li>
                            <li>Keyboard shortcuts support</li>
                        </ul>
                    </div>
                    <div>
                        <h4 style="font-size: 0.875rem; font-weight: 600; margin-bottom: 12px; color: var(--color-text-muted);">Ordered List</h4>
                        <ol class="anvil-block-list">
                            <li>Select block type from inserter</li>
                            <li>Add your content</li>
                            <li>Customize block settings</li>
                            <li>Publish your page</li>
                        </ol>
                    </div>
                </div>
            </div>
            
            <!-- Code -->
            <div style="margin-bottom: 64px;">
                <span style="display: inline-block; padding: 4px 12px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; background: var(--color-accent); color: white; border-radius: 100px; margin-bottom: 16px;">Code Block</span>
                <div class="anvil-block-code" data-language="php">
                    <code>&lt;?php
// Register a custom block
Anvil::registerBlockClass(AlertBlock::class);

// The block will automatically appear in the editor
// with its own settings and render method</code>
                </div>
            </div>
            
            <!-- Table -->
            <div style="margin-bottom: 64px;">
                <span style="display: inline-block; padding: 4px 12px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; background: var(--color-accent); color: white; border-radius: 100px; margin-bottom: 16px;">Table Block</span>
                <table class="anvil-block-table">
                    <thead>
                        <tr>
                            <th>Block Type</th>
                            <th>Category</th>
                            <th>Features</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Paragraph</td>
                            <td>Text</td>
                            <td>Drop cap, alignment, inline styles</td>
                        </tr>
                        <tr>
                            <td>Image</td>
                            <td>Media</td>
                            <td>Alignment, captions, lightbox</td>
                        </tr>
                        <tr>
                            <td>Columns</td>
                            <td>Layout</td>
                            <td>2-6 columns, responsive, nested blocks</td>
                        </tr>
                        <tr>
                            <td>Embed</td>
                            <td>Embed</td>
                            <td>YouTube, Vimeo, responsive iframe</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Buttons -->
            <div style="margin-bottom: 64px;">
                <span style="display: inline-block; padding: 4px 12px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; background: var(--color-accent); color: white; border-radius: 100px; margin-bottom: 16px;">Button Block</span>
                <div style="display: flex; gap: 16px; flex-wrap: wrap;">
                    <a href="#" class="anvil-button anvil-button-primary">Primary Button</a>
                    <a href="#" class="anvil-button anvil-button-secondary">Secondary Button</a>
                    <a href="#" class="anvil-button anvil-button-outline">Outline Button</a>
                </div>
            </div>
            
            <!-- Separators -->
            <div style="margin-bottom: 64px;">
                <span style="display: inline-block; padding: 4px 12px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; background: var(--color-accent); color: white; border-radius: 100px; margin-bottom: 16px;">Separator Block</span>
                <p style="font-size: 0.875rem; color: var(--color-text-muted); margin-bottom: 24px;">Default Style:</p>
                <hr class="anvil-block-separator separator-default">
                <p style="font-size: 0.875rem; color: var(--color-text-muted); margin-bottom: 24px;">Wide Style:</p>
                <hr class="anvil-block-separator separator-wide">
                <p style="font-size: 0.875rem; color: var(--color-text-muted); margin-bottom: 24px;">Dots Style:</p>
                <hr class="anvil-block-separator separator-dots">
            </div>
        </div>
    </section>
    
    <!-- Columns Demo -->
    <section style="padding: 80px 24px; background: var(--color-bg);">
        <div style="max-width: 1100px; margin: 0 auto;">
            <span style="display: inline-block; padding: 4px 12px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; background: var(--color-accent); color: white; border-radius: 100px; margin-bottom: 16px;">Columns Block</span>
            <h2 class="anvil-block-heading" style="margin-bottom: 32px;">Flexible Multi-Column Layouts</h2>
            
            <div class="anvil-block-columns" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 32px;">
                <div class="anvil-column" style="padding: 24px; background: var(--color-bg-alt); border-radius: var(--radius-lg);">
                    <h3 class="anvil-block-heading" style="font-size: 1.25rem;">Column One</h3>
                    <p class="anvil-block-paragraph">Create 2 to 6 column layouts with custom width ratios. Each column can contain any other block type.</p>
                </div>
                <div class="anvil-column" style="padding: 24px; background: var(--color-bg-alt); border-radius: var(--radius-lg);">
                    <h3 class="anvil-block-heading" style="font-size: 1.25rem;">Column Two</h3>
                    <p class="anvil-block-paragraph">Columns automatically stack on mobile devices for a responsive experience across all screen sizes.</p>
                </div>
                <div class="anvil-column" style="padding: 24px; background: var(--color-bg-alt); border-radius: var(--radius-lg);">
                    <h3 class="anvil-block-heading" style="font-size: 1.25rem;">Column Three</h3>
                    <p class="anvil-block-paragraph">Perfect for feature grids, comparison tables, team sections, and any multi-column content.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Features Grid -->
    <section style="padding: 80px 24px; background: var(--color-bg-alt);">
        <div style="max-width: 1100px; margin: 0 auto;">
            <h2 class="anvil-block-heading" style="text-align: center; margin-bottom: 16px;">Editor Features</h2>
            <p class="anvil-block-paragraph" style="text-align: center; color: var(--color-text-muted); max-width: 600px; margin: 0 auto 48px;">
                Everything you need to create professional content, built right into VoidForge CMS.
            </p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;">
                <div style="display: flex; gap: 16px;">
                    <div style="flex-shrink: 0; width: 40px; height: 40px; background: var(--color-accent-light); border-radius: var(--radius); display: flex; align-items: center; justify-content: center;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--color-accent)" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                    </div>
                    <div>
                        <h4 style="font-weight: 600; margin-bottom: 4px;">Drag & Drop</h4>
                        <p style="font-size: 0.9375rem; color: var(--color-text-muted);">Reorder blocks by dragging them to new positions</p>
                    </div>
                </div>
                
                <div style="display: flex; gap: 16px;">
                    <div style="flex-shrink: 0; width: 40px; height: 40px; background: var(--color-accent-light); border-radius: var(--radius); display: flex; align-items: center; justify-content: center;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--color-accent)" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                    </div>
                    <div>
                        <h4 style="font-weight: 600; margin-bottom: 4px;">Block Settings</h4>
                        <p style="font-size: 0.9375rem; color: var(--color-text-muted);">Configure each block with its own settings panel</p>
                    </div>
                </div>
                
                <div style="display: flex; gap: 16px;">
                    <div style="flex-shrink: 0; width: 40px; height: 40px; background: var(--color-accent-light); border-radius: var(--radius); display: flex; align-items: center; justify-content: center;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--color-accent)" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </div>
                    <div>
                        <h4 style="font-weight: 600; margin-bottom: 4px;">Inline Editing</h4>
                        <p style="font-size: 0.9375rem; color: var(--color-text-muted);">Edit text directly with formatting toolbar</p>
                    </div>
                </div>
                
                <div style="display: flex; gap: 16px;">
                    <div style="flex-shrink: 0; width: 40px; height: 40px; background: var(--color-accent-light); border-radius: var(--radius); display: flex; align-items: center; justify-content: center;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--color-accent)" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                    </div>
                    <div>
                        <h4 style="font-weight: 600; margin-bottom: 4px;">Live Preview</h4>
                        <p style="font-size: 0.9375rem; color: var(--color-text-muted);">See exactly how your content will look</p>
                    </div>
                </div>
                
                <div style="display: flex; gap: 16px;">
                    <div style="flex-shrink: 0; width: 40px; height: 40px; background: var(--color-accent-light); border-radius: var(--radius); display: flex; align-items: center; justify-content: center;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--color-accent)" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg>
                    </div>
                    <div>
                        <h4 style="font-weight: 600; margin-bottom: 4px;">Block Library</h4>
                        <p style="font-size: 0.9375rem; color: var(--color-text-muted);">15 built-in blocks, extensible via plugins</p>
                    </div>
                </div>
                
                <div style="display: flex; gap: 16px;">
                    <div style="flex-shrink: 0; width: 40px; height: 40px; background: var(--color-accent-light); border-radius: var(--radius); display: flex; align-items: center; justify-content: center;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--color-accent)" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
                    </div>
                    <div>
                        <h4 style="font-weight: 600; margin-bottom: 4px;">Custom Blocks</h4>
                        <p style="font-size: 0.9375rem; color: var(--color-text-muted);">Create your own blocks with the plugin API</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- CTA Section -->
    <section style="padding: 100px 24px; background: var(--color-accent); color: white; text-align: center;">
        <div style="max-width: 700px; margin: 0 auto;">
            <h2 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 16px;">Start Creating</h2>
            <p style="font-size: 1.125rem; opacity: 0.8; margin-bottom: 32px;">
                Experience the power of block-based editing. Create your first post with Anvil today.
            </p>
            <a href="<?php echo site_url('/admin/post-edit.php?post_type=post'); ?>" style="display: inline-flex; align-items: center; justify-content: center; padding: 16px 32px; font-size: 1.125rem; font-weight: 600; border-radius: var(--radius); text-decoration: none; transition: all 0.2s; background: white; color: var(--color-accent);">
                Create New Post →
            </a>
        </div>
    </section>
    
    <?php
    // Show recent posts if any exist
    $recentPosts = Post::query([
        'post_type' => 'post',
        'status' => 'published',
        'orderby' => 'created_at',
        'order' => 'DESC',
        'limit' => 3
    ]);
    
    if (!empty($recentPosts)):
    ?>
    <!-- Recent Posts -->
    <section style="padding: 80px 24px; background: var(--color-bg);">
        <div style="max-width: 1100px; margin: 0 auto;">
            <h2 class="anvil-block-heading" style="text-align: center; margin-bottom: 48px;">Latest Posts</h2>
            
            <div class="posts-grid">
                <?php foreach ($recentPosts as $post): ?>
                <article class="post-card">
                    <?php 
                    $thumbnail = Post::featuredImage($post);
                    if ($thumbnail):
                    ?>
                    <a href="<?php echo Post::permalink($post); ?>" class="post-card-image">
                        <img src="<?php echo esc($thumbnail); ?>" alt="<?php echo esc($post['title']); ?>">
                    </a>
                    <?php endif; ?>
                    
                    <div class="post-card-content">
                        <h3 class="post-card-title">
                            <a href="<?php echo Post::permalink($post); ?>">
                                <?php echo esc($post['title']); ?>
                            </a>
                        </h3>
                        
                        <p class="post-card-excerpt">
                            <?php echo esc(flavor_excerpt($post, 100)); ?>
                        </p>
                        
                        <div class="post-card-meta">
                            <?php echo flavor_date($post['created_at']); ?>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
</main>

<?php get_footer(); ?>
