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
            
            <style>
            .admin-footer {
                padding: 1rem 1.5rem;
                background: var(--bg-card);
                border-top: 1px solid var(--border-color);
                margin-top: auto;
            }
            
            .footer-inner {
                display: flex;
                align-items: center;
                justify-content: space-between;
                flex-wrap: wrap;
                gap: 1rem;
            }
            
            .footer-left {
                display: flex;
                align-items: center;
                gap: 0.75rem;
            }
            
            .footer-brand {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                font-weight: 600;
                font-size: 0.875rem;
                color: var(--text-primary);
            }
            
            .footer-brand svg {
                color: var(--forge-primary);
            }
            
            .footer-version {
                font-size: 0.75rem;
                color: var(--text-muted);
                background: var(--bg-card-header);
                padding: 0.25rem 0.5rem;
                border-radius: 4px;
            }
            
            .footer-right {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                font-size: 0.8125rem;
            }
            
            .footer-link {
                color: var(--text-muted);
                text-decoration: none;
                transition: color 0.15s;
            }
            
            .footer-link:hover {
                color: var(--forge-primary);
            }
            
            .footer-sep {
                color: var(--border-color);
            }
            
            @media (max-width: 640px) {
                .footer-inner {
                    flex-direction: column;
                    text-align: center;
                }
            }
            </style>
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
