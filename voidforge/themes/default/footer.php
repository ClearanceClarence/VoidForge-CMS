            </div>
        </main>
        <footer class="footer">
            <div class="container">
                <div class="footer-content">
                    <div class="footer-brand">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                            <polyline points="2 17 12 22 22 17"></polyline>
                            <polyline points="2 12 12 17 22 12"></polyline>
                        </svg>
                        <span><?= esc(getOption('site_title', 'My Site')) ?></span>
                    </div>
                    <p>&copy; <?= date('Y') ?> <?= esc(getOption('site_title', 'My Site')) ?>. Powered by <?= CMS_NAME ?>.</p>
                </div>
            </div>
        </footer>
    </div>
    <?php do_action('theme_footer'); ?>
</body>
</html>
