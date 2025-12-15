<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc(get_page_title()); ?></title>
    <meta name="description" content="<?php echo esc(get_site_description()); ?>">
    <link rel="icon" href="<?php echo site_url('/favicon.svg'); ?>">
    <link rel="stylesheet" href="<?php echo Theme::getUrl(); ?>/style.css">
    <?php vf_head(); ?>
</head>
<body class="<?php echo body_class(); ?>">
    <div class="site-container">
        
        <header class="site-header">
            <div class="header-inner">
                <div class="site-branding">
                    <svg class="site-logo" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="40" height="40" rx="8" fill="currentColor" style="color: var(--color-accent);"/>
                        <path d="M12 14h16M12 20h16M12 26h10" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                    </svg>
                    <h1 class="site-title">
                        <a href="<?php echo site_url(); ?>"><?php echo esc(get_site_name()); ?></a>
                    </h1>
                </div>
                
                <nav class="main-nav">
                    <?php 
                    $menu = Menu::getMenuByLocation('primary');
                    $menuItems = $menu ? Menu::getItems($menu['id']) : [];
                    if (!empty($menuItems)): 
                    ?>
                    <ul>
                        <?php foreach ($menuItems as $item): ?>
                        <li class="<?php echo is_current_url(Menu::getItemUrl($item)) ? 'current-menu-item' : ''; ?>">
                            <a href="<?php echo esc(Menu::getItemUrl($item)); ?>"
                               <?php echo !empty($item['target']) ? 'target="' . esc($item['target']) . '"' : ''; ?>>
                                <?php echo esc($item['title']); ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <ul>
                        <li><a href="<?php echo site_url(); ?>">Home</a></li>
                    </ul>
                    <?php endif; ?>
                </nav>
            </div>
        </header>
