        <!-- Pre-footer CTA Section -->
        <section class="pre-footer">
            <div class="pre-footer-inner">
                <div class="pre-footer-content">
                    <h2 class="pre-footer-title">Ready to get started?</h2>
                    <p class="pre-footer-text">Join thousands of creators building amazing websites.</p>
                </div>
                <div class="pre-footer-actions">
                    <a href="<?php echo site_url(); ?>/contact" class="btn btn-primary btn-lg">
                        Start Building
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </section>

        <footer class="site-footer">
            <!-- Main Footer -->
            <div class="footer-main">
                <div class="footer-inner">
                    <!-- Brand Column -->
                    <div class="footer-brand">
                        <div class="footer-logo">
                            <?php if (has_site_logo()): ?>
                                <?php $logo = get_site_logo(); ?>
                                <a href="<?php echo site_url(); ?>" aria-label="<?php echo esc(get_site_name()); ?>">
                                    <img src="<?php echo esc($logo['url']); ?>" alt="<?php echo esc($logo['alt']); ?>" class="footer-logo-img">
                                </a>
                            <?php else: ?>
                                <a href="<?php echo site_url(); ?>" aria-label="<?php echo esc(get_site_name()); ?>">
                                    <svg class="footer-logo-svg" viewBox="0 0 180 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <defs>
                                            <linearGradient id="footer-grad" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" style="stop-color:#a78bfa"/>
                                                <stop offset="50%" style="stop-color:#8b5cf6"/>
                                                <stop offset="100%" style="stop-color:#6366f1"/>
                                            </linearGradient>
                                            <linearGradient id="footer-icon" x1="0%" y1="0%" x2="0%" y2="100%">
                                                <stop offset="0%" style="stop-color:#8b5cf6"/>
                                                <stop offset="100%" style="stop-color:#4f46e5"/>
                                            </linearGradient>
                                        </defs>
                                        <path d="M20 32L4 24L20 18L36 24Z" fill="#4f46e5" opacity="0.6"/>
                                        <path d="M20 26L4 18L20 12L36 18Z" fill="#6366f1" opacity="0.8"/>
                                        <path d="M20 20L4 12L20 6L36 12Z" fill="url(#footer-icon)"/>
                                        <circle cx="20" cy="3" r="2" fill="#c4b5fd"/>
                                        <text x="44" y="26" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="18" font-weight="700" letter-spacing="-0.5">
                                            <tspan fill="#ffffff">Void</tspan><tspan fill="url(#footer-grad)">Forge</tspan>
                                        </text>
                                    </svg>
                                </a>
                            <?php endif; ?>
                        </div>
                        <p class="footer-tagline"><?php echo esc(get_site_description()); ?></p>
                        
                        <!-- Social Links -->
                        <div class="footer-social">
                            <a href="#" class="social-link" aria-label="Twitter">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                </svg>
                            </a>
                            <a href="#" class="social-link" aria-label="GitHub">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2C6.477 2 2 6.477 2 12c0 4.42 2.865 8.17 6.839 9.49.5.092.682-.217.682-.482 0-.237-.008-.866-.013-1.7-2.782.604-3.369-1.34-3.369-1.34-.454-1.156-1.11-1.464-1.11-1.464-.908-.62.069-.608.069-.608 1.003.07 1.531 1.03 1.531 1.03.892 1.529 2.341 1.087 2.91.831.092-.646.35-1.086.636-1.336-2.22-.253-4.555-1.11-4.555-4.943 0-1.091.39-1.984 1.029-2.683-.103-.253-.446-1.27.098-2.647 0 0 .84-.269 2.75 1.025A9.578 9.578 0 0112 6.836c.85.004 1.705.114 2.504.336 1.909-1.294 2.747-1.025 2.747-1.025.546 1.377.203 2.394.1 2.647.64.699 1.028 1.592 1.028 2.683 0 3.842-2.339 4.687-4.566 4.935.359.309.678.919.678 1.852 0 1.336-.012 2.415-.012 2.743 0 .267.18.578.688.48C19.138 20.167 22 16.418 22 12c0-5.523-4.477-10-10-10z"/>
                                </svg>
                            </a>
                            <a href="#" class="social-link" aria-label="LinkedIn">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                </svg>
                            </a>
                            <a href="#" class="social-link" aria-label="YouTube">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Navigation Column -->
                    <div class="footer-column">
                        <h4 class="footer-heading">Navigation</h4>
                        <ul class="footer-links">
                            <?php 
                            $menu = Menu::getMenuByLocation('primary');
                            $menuItems = $menu ? Menu::getItems($menu['id']) : [];
                            if (!empty($menuItems)): 
                                foreach (array_slice($menuItems, 0, 6) as $item):
                            ?>
                            <li>
                                <a href="<?php echo esc(Menu::getItemUrl($item)); ?>">
                                    <?php echo esc($item['title']); ?>
                                </a>
                            </li>
                            <?php 
                                endforeach;
                            else: 
                            ?>
                            <li><a href="<?php echo site_url(); ?>">Home</a></li>
                            <li><a href="<?php echo site_url(); ?>/about">About</a></li>
                            <li><a href="<?php echo site_url(); ?>/blog">Blog</a></li>
                            <li><a href="<?php echo site_url(); ?>/contact">Contact</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    
                    <!-- Resources Column -->
                    <div class="footer-column">
                        <h4 class="footer-heading">Resources</h4>
                        <ul class="footer-links">
                            <li><a href="<?php echo site_url(); ?>/docs">Documentation</a></li>
                            <li><a href="<?php echo site_url(); ?>/guides">Guides</a></li>
                            <li><a href="<?php echo site_url(); ?>/support">Support</a></li>
                            <li><a href="<?php echo site_url(); ?>/changelog">Changelog</a></li>
                        </ul>
                    </div>
                    
                    <!-- Newsletter Column -->
                    <div class="footer-column footer-newsletter">
                        <h4 class="footer-heading">Stay Updated</h4>
                        <p class="newsletter-text">Get the latest updates and news delivered to your inbox.</p>
                        <form class="newsletter-form" action="#" method="post">
                            <div class="newsletter-input-wrap">
                                <input type="email" name="email" placeholder="Enter your email" required class="newsletter-input">
                                <button type="submit" class="newsletter-btn" aria-label="Subscribe">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M5 12h14M12 5l7 7-7 7"/>
                                    </svg>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Footer Bottom -->
            <div class="footer-bottom">
                <div class="footer-bottom-inner">
                    <p class="footer-copyright">
                        &copy; <?php echo date('Y'); ?> <?php echo esc(get_site_name()); ?>. All rights reserved.
                    </p>
                    <nav class="footer-legal">
                        <a href="<?php echo site_url(); ?>/privacy">Privacy Policy</a>
                        <span class="divider">&middot;</span>
                        <a href="<?php echo site_url(); ?>/terms">Terms of Service</a>
                        <span class="divider">&middot;</span>
                        <a href="<?php echo site_url(); ?>/cookies">Cookies</a>
                    </nav>
                </div>
            </div>
        </footer>
        
    </div><!-- .site-container -->
    
    <?php vf_footer(); ?>
</body>
</html>
