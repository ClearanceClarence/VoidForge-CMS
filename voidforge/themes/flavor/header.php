<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc(get_page_title()); ?></title>
    <meta name="description" content="<?php echo esc(get_site_description()); ?>">
    <?php the_favicon(); ?>
    <link rel="stylesheet" href="<?php echo Theme::getUrl(); ?>/style.css">
    <?php 
    // Load Anvil frontend CSS if plugin is active
    if (defined('ANVIL_URL')) {
        echo '<link rel="stylesheet" href="' . ANVIL_URL . '/assets/css/anvil-frontend.css">' . "\n";
    } elseif (file_exists(CMS_ROOT . '/plugins/anvil/assets/css/anvil-frontend.css')) {
        echo '<link rel="stylesheet" href="' . SITE_URL . '/plugins/anvil/assets/css/anvil-frontend.css">' . "\n";
    }
    ?>
    <?php vf_head(); ?>
</head>
<body class="<?php echo body_class(); ?>">
    <div class="site-container">
        
        <header class="site-header" id="site-header">
            <div class="header-inner">
                <!-- Logo -->
                <div class="site-branding">
                    <?php if (has_site_logo()): ?>
                        <?php $logo = get_site_logo(); ?>
                        <a href="<?php echo site_url(); ?>" class="site-logo-link" aria-label="<?php echo esc(get_site_name()); ?>">
                            <img src="<?php echo esc($logo['url']); ?>" alt="<?php echo esc($logo['alt']); ?>" class="site-logo site-logo-custom"<?php 
                                if (!empty($logo['height'])) echo ' height="' . (int)$logo['height'] . '"';
                                if (!empty($logo['width'])) echo ' width="' . (int)$logo['width'] . '"';
                            ?>>
                        </a>
                    <?php else: ?>
                        <a href="<?php echo site_url(); ?>" class="site-logo-link" aria-label="<?php echo esc(get_site_name()); ?>">
                            <svg class="site-logo site-logo-default" viewBox="0 0 180 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <defs>
                                    <linearGradient id="logo-grad" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" style="stop-color:#a78bfa"/>
                                        <stop offset="50%" style="stop-color:#8b5cf6"/>
                                        <stop offset="100%" style="stop-color:#6366f1"/>
                                    </linearGradient>
                                    <linearGradient id="icon-grad" x1="0%" y1="0%" x2="0%" y2="100%">
                                        <stop offset="0%" style="stop-color:#8b5cf6"/>
                                        <stop offset="100%" style="stop-color:#4f46e5"/>
                                    </linearGradient>
                                </defs>
                                <path d="M20 32L4 24L20 18L36 24Z" fill="#4f46e5" opacity="0.6"/>
                                <path d="M20 26L4 18L20 12L36 18Z" fill="#6366f1" opacity="0.8"/>
                                <path d="M20 20L4 12L20 6L36 12Z" fill="url(#icon-grad)"/>
                                <circle cx="20" cy="3" r="2" fill="#c4b5fd"/>
                                <text x="44" y="26" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="18" font-weight="700" letter-spacing="-0.5">
                                    <tspan fill="#1e293b">Void</tspan><tspan fill="url(#logo-grad)">Forge</tspan>
                                </text>
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>
                
                <!-- Main Navigation -->
                <nav class="main-nav" id="main-nav" aria-label="Primary navigation">
                    <?php 
                    $menu = Menu::getMenuByLocation('primary');
                    $menuItems = $menu ? Menu::getItems($menu['id']) : [];
                    if (!empty($menuItems)): 
                    ?>
                    <ul class="nav-menu">
                        <?php foreach ($menuItems as $item): ?>
                        <li class="nav-item <?php echo is_current_url(Menu::getItemUrl($item)) ? 'current-menu-item' : ''; ?>">
                            <a href="<?php echo esc(Menu::getItemUrl($item)); ?>" class="nav-link"
                               <?php echo !empty($item['target']) ? 'target="' . esc($item['target']) . '"' : ''; ?>>
                                <?php echo esc($item['title']); ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <ul class="nav-menu">
                        <li class="nav-item <?php echo is_current_url(site_url()) ? 'current-menu-item' : ''; ?>">
                            <a href="<?php echo site_url(); ?>" class="nav-link">Home</a>
                        </li>
                    </ul>
                    <?php endif; ?>
                </nav>
                
                <!-- Header Actions -->
                <div class="header-actions">
                    <!-- Search Toggle -->
                    <button class="header-search-toggle" aria-label="Search" id="search-toggle">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="M21 21l-4.35-4.35"/>
                        </svg>
                    </button>
                    
                    <!-- CTA Button (optional) -->
                    <a href="<?php echo site_url(); ?>/contact" class="header-cta">
                        Get Started
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </a>
                    
                    <!-- Mobile Menu Toggle -->
                    <button class="mobile-menu-toggle" id="mobile-menu-toggle" aria-label="Toggle menu" aria-expanded="false">
                        <span class="hamburger">
                            <span></span>
                            <span></span>
                            <span></span>
                        </span>
                    </button>
                </div>
            </div>
            
            <!-- Search Overlay -->
            <div class="search-overlay" id="search-overlay">
                <div class="search-overlay-backdrop"></div>
                <div class="search-modal">
                    <div class="search-modal-header">
                        <h3>Search</h3>
                        <button type="button" class="search-close" id="search-close" aria-label="Close search">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 6L6 18M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <form class="search-form" action="<?php echo site_url(); ?>" method="get">
                        <div class="search-input-wrapper">
                            <svg class="search-input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"/>
                                <path d="M21 21l-4.35-4.35"/>
                            </svg>
                            <input type="search" name="s" placeholder="Type to search..." class="search-input" autocomplete="off" id="search-input">
                            <kbd class="search-kbd">ESC</kbd>
                        </div>
                    </form>
                    <div class="search-hints">
                        <span class="search-hint">
                            <kbd>↵</kbd> to search
                        </span>
                        <span class="search-hint">
                            <kbd>ESC</kbd> to close
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Mobile Menu -->
            <div class="mobile-menu" id="mobile-menu">
                <nav class="mobile-nav">
                    <?php if (!empty($menuItems)): ?>
                    <ul class="mobile-nav-menu">
                        <?php foreach ($menuItems as $item): ?>
                        <li class="mobile-nav-item <?php echo is_current_url(Menu::getItemUrl($item)) ? 'current-menu-item' : ''; ?>">
                            <a href="<?php echo esc(Menu::getItemUrl($item)); ?>" class="mobile-nav-link"
                               <?php echo !empty($item['target']) ? 'target="' . esc($item['target']) . '"' : ''; ?>>
                                <?php echo esc($item['title']); ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <ul class="mobile-nav-menu">
                        <li class="mobile-nav-item"><a href="<?php echo site_url(); ?>" class="mobile-nav-link">Home</a></li>
                    </ul>
                    <?php endif; ?>
                </nav>
                <div class="mobile-menu-footer">
                    <a href="<?php echo site_url(); ?>/contact" class="btn btn-primary btn-block">Get Started</a>
                </div>
            </div>
        </header>

<script>
(function() {
    const header = document.getElementById('site-header');
    const mobileToggle = document.getElementById('mobile-menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    const searchToggle = document.getElementById('search-toggle');
    const searchOverlay = document.getElementById('search-overlay');
    const searchClose = document.getElementById('search-close');
    const searchInput = document.getElementById('search-input');
    
    // Scroll effect
    let lastScroll = 0;
    window.addEventListener('scroll', function() {
        const currentScroll = window.scrollY;
        if (currentScroll > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
        lastScroll = currentScroll;
    });
    
    // Mobile menu toggle
    if (mobileToggle && mobileMenu) {
        mobileToggle.addEventListener('click', function() {
            const isOpen = mobileMenu.classList.contains('active');
            mobileMenu.classList.toggle('active');
            mobileToggle.classList.toggle('active');
            mobileToggle.setAttribute('aria-expanded', !isOpen);
            document.body.classList.toggle('mobile-menu-open');
        });
    }
    
    // Search overlay
    function openSearch() {
        if (searchOverlay) {
            searchOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
            setTimeout(() => {
                if (searchInput) searchInput.focus();
            }, 100);
        }
    }
    
    function closeSearch() {
        if (searchOverlay) {
            searchOverlay.classList.remove('active');
            document.body.style.overflow = '';
            if (searchInput) searchInput.value = '';
        }
    }
    
    if (searchToggle) {
        searchToggle.addEventListener('click', openSearch);
    }
    
    if (searchClose) {
        searchClose.addEventListener('click', closeSearch);
    }
    
    // Close on escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && searchOverlay && searchOverlay.classList.contains('active')) {
            closeSearch();
        }
        // Ctrl/Cmd + K to open search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            if (searchOverlay && searchOverlay.classList.contains('active')) {
                closeSearch();
            } else {
                openSearch();
            }
        }
    });
    
    // Close on backdrop click
    if (searchOverlay) {
        searchOverlay.addEventListener('click', function(e) {
            if (e.target.classList.contains('search-overlay-backdrop')) {
                closeSearch();
            }
        });
    }
})();
</script>
