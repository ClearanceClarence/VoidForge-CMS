<?php
/**
 * 404 Not Found Template
 */

$pageTitle = 'Page Not Found';

include __DIR__ . '/header.php';
?>

<article style="text-align: center; padding: 4rem 2rem;">
    <h1 style="font-size: 4rem; color: #e5e7eb; margin-bottom: 1rem;">404</h1>
    <h2>Page Not Found</h2>
    <p style="color: #666; margin: 1rem 0 2rem;">The page you're looking for doesn't exist or has been moved.</p>
    <a href="<?= SITE_URL ?>" class="read-more">&larr; Back to Home</a>
</article>

<?php include __DIR__ . '/footer.php'; ?>
