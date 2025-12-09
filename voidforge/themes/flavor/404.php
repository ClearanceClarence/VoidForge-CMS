<?php
/**
 * 404 Not Found Template - Flavor Theme
 */

defined('CMS_ROOT') or die;

$pageTitle = 'Page Not Found — ' . getOption('site_title', 'VoidForge');
http_response_code(404);

get_header();
?>

<div class="container">
    <section class="error-page">
        <div class="error-content">
            <h1 class="error-code">404</h1>
            <h2 class="error-title">Page not found</h2>
            <p class="error-message">The page you're looking for doesn't exist or has been moved.</p>
            <a href="<?= SITE_URL ?>" class="error-button">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back to Home
            </a>
        </div>
    </section>
</div>

<?php get_footer(); ?>
