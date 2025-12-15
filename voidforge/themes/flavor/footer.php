        <footer class="site-footer">
            <div class="footer-inner">
                <p class="footer-copy">
                    &copy; <?php echo date('Y'); ?> <?php echo esc(get_site_name()); ?>. 
                    Powered by <a href="https://github.com/voidforge/cms">VoidForge CMS</a>
                </p>
                
                <?php 
                $footerMenuData = Menu::getMenuByLocation('footer');
                $footerMenu = $footerMenuData ? Menu::getItems($footerMenuData['id']) : [];
                if (!empty($footerMenu)): 
                ?>
                <ul class="footer-links">
                    <?php foreach ($footerMenu as $item): ?>
                    <li>
                        <a href="<?php echo esc(Menu::getItemUrl($item)); ?>"
                           <?php echo !empty($item['target']) ? 'target="' . esc($item['target']) . '"' : ''; ?>>
                            <?php echo esc($item['title']); ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </footer>
        
    </div><!-- .site-container -->
    
    <?php vf_footer(); ?>
</body>
</html>
