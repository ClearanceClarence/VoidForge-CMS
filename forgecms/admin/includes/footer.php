            </div><!-- .admin-content -->
            
            <footer class="admin-footer">
                <div class="footer-inner">
                    <div class="footer-left">
                        <span class="footer-brand">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                                <polyline points="2 17 12 22 22 17"></polyline>
                                <polyline points="2 12 12 17 22 12"></polyline>
                            </svg>
                            <?= CMS_NAME ?>
                        </span>
                        <span class="footer-version">v<?= CMS_VERSION ?></span>
                    </div>
                    <div class="footer-right">
                        <a href="https://github.com" target="_blank" class="footer-link">Documentation</a>
                        <span class="footer-sep">•</span>
                        <a href="<?= SITE_URL ?>" target="_blank" class="footer-link">View Site</a>
                    </div>
                </div>
            </footer>
        </main>
    </div>

    <script src="<?= ADMIN_URL ?>/assets/js/admin.js"></script>
    <?php if (!empty($pageScripts)): ?>
        <?php foreach ($pageScripts as $script): ?>
            <script src="<?= ADMIN_URL ?>/assets/js/<?= $script ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
