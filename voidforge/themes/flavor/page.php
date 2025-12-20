<?php
/**
 * Page Template
 * 
 * @package Flavor
 */

get_header();

global $post;
?>

<main class="content-area">
    
    <article class="single-page">
        
        <header class="entry-header">
            <?php if (flavor_show_entry_title()): ?>
            <h1 class="entry-title"><?php echo esc($post['title']); ?></h1>
            <?php endif; ?>
        </header>
        
        <div class="entry-content clearfix">
            <?php echo the_content(); ?>
        </div>
        
    </article>
    
</main>

<?php get_footer(); ?>
