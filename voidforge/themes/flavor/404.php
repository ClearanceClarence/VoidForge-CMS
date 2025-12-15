<?php
/**
 * 404 Error Template
 * 
 * @package Flavor
 */

get_header();
?>

<main class="content-area">
    
    <div class="error-404">
        <h1>404</h1>
        <h2>Page not found</h2>
        <p>The page you're looking for doesn't exist or has been moved.</p>
        <a href="<?php echo site_url(); ?>" class="anvil-button anvil-button-primary">
            Go back home
        </a>
    </div>
    
</main>

<?php get_footer(); ?>
