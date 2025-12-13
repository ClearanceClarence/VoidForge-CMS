<?php
/**
 * Nova Theme - Footer
 */
defined('CMS_ROOT') or die;

$siteTitle = getOption('site_title', 'VoidForge');
$footerText = 'Built with VoidForge CMS';
?>
    </main>
    
    <!-- Footer -->
    <footer class="site-footer">
        <div class="footer-inner">
            <div class="footer-brand">
                <svg viewBox="0 0 32 32" fill="none" width="28" height="28">
                    <defs>
                        <linearGradient id="footerGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#a855f7"/>
                            <stop offset="100%" stop-color="#06b6d4"/>
                        </linearGradient>
                    </defs>
                    <path d="M5 5 L16 27 L27 5" fill="none" stroke="url(#footerGrad)" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="16" cy="14" r="3" fill="url(#footerGrad)"/>
                </svg>
                <span><?= esc($siteTitle) ?></span>
            </div>
            
            <div class="footer-links">
                <a href="<?= ADMIN_URL ?>/">Dashboard</a>
                <a href="https://github.com/ClearanceClarence/VoidForge-CMS" target="_blank">GitHub</a>
            </div>
            
            <div class="footer-copy">
                © <?= date('Y') ?> <?= esc($siteTitle) ?>. <?= esc($footerText) ?>
            </div>
        </div>
    </footer>
    
    <script>
        // Header scroll effect
        window.addEventListener('scroll', () => {
            const header = document.getElementById('header');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    </script>
    
    <?php Plugin::doAction('vf_footer'); ?>
</body>
</html>
