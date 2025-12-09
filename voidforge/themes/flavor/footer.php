</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-inner">
            <div class="footer-brand">
                <a href="<?= SITE_URL ?>" class="footer-logo">
                    <?= esc(getOption('site_title', 'VoidForge')) ?>
                </a>
                <?php if ($tagline = getOption('site_tagline')): ?>
                <p class="footer-tagline"><?= esc($tagline) ?></p>
                <?php endif; ?>
            </div>
            
            <div class="footer-links">
                <nav class="footer-nav">
                    <?php foreach (flavor_nav_menu() as $item): ?>
                    <a href="<?= esc($item['url']) ?>"><?= esc($item['label']) ?></a>
                    <?php endforeach; ?>
                </nav>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= esc(getOption('site_title', 'VoidForge')) ?>. All rights reserved.</p>
            <p class="powered-by">
                Powered by <a href="https://voidforge.dev" target="_blank" rel="noopener">VoidForge CMS</a>
            </p>
        </div>
    </div>
</footer>

<?php Plugin::renderScripts(true); ?>
<?php Plugin::doAction('wp_footer'); ?>

</body>
</html>
