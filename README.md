# VoidForge CMS

<div align="center">

![VoidForge CMS](https://img.shields.io/badge/VoidForge-CMS-6366f1?style=for-the-badge&logo=data:image/svg+xml;base64,PHN2ZyB2aWV3Qm94PSIwIDAgMjQgMjQiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS13aWR0aD0iMi41Ij48cGF0aCBkPSJNNiA0TDEyIDIwTDE4IDQiLz48L3N2Zz4=)
![Version](https://img.shields.io/badge/version-0.1.4-8b5cf6?style=for-the-badge)
![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-10b981?style=for-the-badge)

**A modern, lightweight content management system built with pure PHP.**

No frameworks. No bloat. Just powerful features and elegant code.

[Features](#-features) • [Installation](#-installation) • [Documentation](#-api-reference) • [Contributing](#-contributing)

</div>

---

## ✨ Features

### Content Management

- **Custom Post Types** — Create unlimited content types with custom fields, icons, and URL structures
- **Custom Fields** — 14+ field types including text, WYSIWYG, images, files, colors, dates, and more
- **Field Groups** — Create reusable field groups and assign them to any post type or users
- **Menu Builder** — Visual drag-and-drop menu management with nested items and multiple locations
- **Post Revisions** — Automatic revision history with compare and restore functionality
- **Media Library** — Grid/list views, full-screen modal editing, keyboard navigation, drag-and-drop uploads
- **Thumbnail Manager** — View, regenerate, and manage all image sizes with modal preview
- **Rich Text Editor** — Built-in WYSIWYG editor with formatting toolbar

### Theme System

- **Multiple Themes** — Ships with Default (dark gradient) and Flavor (light minimal) themes
- **Theme Settings** — Per-theme customization with colors, sections, features, stats, and CTAs
- **Unique Landing Pages** — Each theme has its own distinctive landing page design
- **Custom CSS** — Add custom CSS per theme without editing files
- **Live Preview** — Real-time preview of theme changes

### Administration

- **Modern Admin Interface** — Beautiful dark sidebar with customizable color schemes
- **Admin Theme Customization** — Choose from multiple color schemes, fonts, and icon styles
- **Live CSS Editor** — Real-time styling with instant preview for admin and frontend
- **Granular Font Sizes** — Separate font size controls for sidebar, header, and content areas
- **80+ Admin Icons** — Extensive icon library for post types and navigation

### User Management

- **Role-Based Permissions** — Admin, Editor, and Subscriber roles
- **User Profiles** — Gravatar support and customizable profile fields
- **Secure Authentication** — Password hashing, CSRF protection, secure sessions

### Plugin System

- **WordPress-Style Hooks** — Actions and filters for extending functionality
- **Shortcodes** — `[tag]` syntax for dynamic content
- **Settings API** — Persistent plugin settings storage
- **AJAX Handlers** — Easy AJAX endpoint registration
- **Asset Enqueueing** — Script and style management
- **Admin Pages** — Add custom admin menu items
- **REST API Extensions** — Custom REST routes
- **Scheduled Tasks** — Cron-like task scheduling
- **Included Plugins** — Starter Shortcodes and Social Share examples

### Developer Features

- **Theme Support** — Simple PHP templates with full access to all data
- **Clean Architecture** — No framework magic, just readable PHP code
- **Auto Updates** — One-click updates with automatic backups
- **Plugin Documentation** — Comprehensive 72KB HTML development guide
- **Theme Documentation** — Complete theme creation guide with examples

### Security

- **CSRF Protection** — Token-based form protection
- **XSS Prevention** — Output escaping helpers
- **Secure Sessions** — Properly configured PHP sessions
- **Password Security** — bcrypt password hashing

---

## 📋 Requirements

- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Apache with mod_rewrite (or nginx)
- GD Library (for image processing)

---

## 🚀 Installation

### Quick Install

1. **Download** the latest release and extract to your web directory
2. **Navigate** to your site URL in a browser
3. **Follow** the installation wizard
4. **Done!** Log in to your new admin dashboard

### Manual Installation

1. Clone or download the repository:

   ```bash
   git clone https://github.com/ClearanceClarence/VoidForge-CMS.git
   ```

2. Create a MySQL database:

   ```sql
   CREATE DATABASE voidforge_cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. Copy the sample config (if available) or run the installer:

   ```bash
   cp includes/config.sample.php includes/config.php
   ```

4. Configure your web server to point to the project root

5. Visit your domain and complete the installation wizard

---

## 📁 Directory Structure

```
voidforge-cms/
├── admin/                  # Admin panel files
│   ├── assets/            # Admin CSS, JS, images
│   ├── includes/          # Admin includes (header, footer, sidebar)
│   ├── index.php          # Admin dashboard
│   ├── posts.php          # Post management
│   ├── menus.php          # Menu builder
│   ├── media.php          # Media library with modal editing
│   ├── thumbnails.php     # Thumbnail manager
│   ├── themes.php         # Theme management
│   ├── theme-settings.php # Per-theme customization
│   ├── plugins.php        # Plugin management
│   └── ...
├── docs/                  # Documentation
│   ├── plugin-development.html  # Plugin dev guide
│   └── theme-development.html   # Theme dev guide
├── includes/              # Core PHP files
│   ├── config.php         # Configuration (generated)
│   ├── database.php       # Database class
│   ├── functions.php      # Helper functions
│   ├── user.php           # User class
│   ├── post.php           # Post class
│   ├── media.php          # Media class
│   ├── menu.php           # Menu class
│   ├── plugin.php         # Plugin system
│   └── theme.php          # Theme system
├── plugins/               # Plugin directory
│   ├── starter-shortcodes/# Example shortcodes
│   └── social-share/      # Example social plugin
├── themes/                # Theme directory
│   ├── default/           # Default dark theme
│   └── flavor/            # Light minimal theme
├── uploads/               # Media uploads
├── backups/               # Auto-update backups
├── index.php              # Front-end router
├── install.php            # Installation wizard
└── .htaccess              # Apache configuration
```

---

## 🎨 Theme Development

Create custom themes in the `/themes` directory:

```php
<!-- themes/mytheme/index.php -->
<?php
/**
 * Theme Name: My Theme
 * Description: A custom theme
 * Version: 1.0.0
 * Author: Your Name
 */

$settings = getThemeSettings('mytheme');
get_header();
?>

<main>
    <?php if ($settings['show_hero'] ?? true): ?>
        <section class="hero" style="background: <?= esc($settings['primary_color'] ?? '#6366f1') ?>">
            <h1><?= esc($settings['hero_title'] ?? 'Welcome') ?></h1>
        </section>
    <?php endif; ?>

    <!-- Your theme content -->
</main>

<?php get_footer(); ?>
```

### Theme Settings

Themes can define customizable settings that appear in the admin:

- Hero section toggle and content
- Color customization
- Feature sections
- Stats display
- Call-to-action areas
- Custom CSS

### Menu Integration

Display navigation menus in your theme:

```php
// Register a menu location in functions.php
Menu::registerLocation('main-menu', 'Main Navigation');

// Display the menu in your template
echo Menu::display('main-menu', [
    'container' => 'nav',
    'container_class' => 'main-navigation',
    'menu_class' => 'nav-menu',
    'submenu_class' => 'dropdown-menu',
]);
```

See `/docs/theme-development.html` for comprehensive documentation.

---

## 🔌 Plugin Development

Create plugins using WordPress-style hooks:

```php
<?php
/**
 * Plugin Name: My Plugin
 * Description: Adds custom functionality
 * Version: 1.0.0
 * Author: Your Name
 * Requires PHP: 8.0
 * Requires CMS: 0.1.4
 */

// Hook into initialization
add_action('init', function() {
    // Your code here
});

// Modify content
add_filter('the_content', function($content) {
    return $content . '<p>Added by plugin!</p>';
});

// Register a shortcode
add_shortcode('hello', function($atts) {
    $name = $atts['name'] ?? 'World';
    return "<p>Hello, {$name}!</p>";
});

// Add settings page
add_admin_page('my-settings', [
    'title' => 'My Plugin',
    'icon' => 'settings',
    'callback' => function() {
        // Render settings
    }
]);
```

See `/docs/plugin-development.html` for comprehensive documentation.

---

## 🖼️ Media Library

The media library features a modern interface with:

- **Grid/List Views** — Toggle between visual grid and detailed list
- **Full-Screen Modal** — Large preview with editing sidebar
- **Keyboard Navigation** — Arrow keys to browse, Escape to close
- **Quick Actions** — Edit title/alt text, copy URL, delete
- **Drag & Drop Upload** — Drop files anywhere to upload
- **Folder Organization** — Organize media into folders

---

## ⚙️ Configuration

### Database Settings

Edit `includes/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'voidforge_cms');
define('DB_USER', 'username');
define('DB_PASS', 'password');
define('DB_PREFIX', 'vf_');
```

### Site Settings

```php
define('SITE_URL', 'https://yoursite.com');
define('CMS_VERSION', '0.1.4');
define('CMS_NAME', 'VoidForge');
```

---

## 🔄 Updating

### Automatic Updates

1. Go to **Admin → Updates**
2. Upload the new version ZIP file
3. Click **Install Update**
4. VoidForge will:
   - Create a timestamped backup
   - Extract new files
   - Preserve your config, uploads, and customizations
   - Run any necessary migrations

### Manual Updates

1. Backup your installation
2. Replace all files except:
   - `includes/config.php`
   - `uploads/` directory
   - Custom themes and plugins
3. Visit the admin panel to run migrations

---

## 🛡️ Security Best Practices

1. **Keep Updated** — Always run the latest version
2. **Strong Passwords** — Use complex passwords for all accounts
3. **File Permissions** — Set appropriate permissions (755 for directories, 644 for files)
4. **HTTPS** — Always use SSL/TLS in production
5. **Backups** — Regularly backup your database and files

---

## 📖 API Reference

### Posts

```php
// Query posts
$posts = Post::query([
    'post_type' => 'post',
    'status' => 'published',
    'limit' => 10
]);

// Find by ID or slug
$post = Post::find($id);
$post = Post::findBySlug('hello-world', 'post');

// Create/Update/Delete
$id = Post::create(['title' => 'My Post', ...]);
Post::update($id, ['title' => 'Updated']);
Post::delete($id);
```

### Custom Fields

```php
$value = get_custom_field('field_key', $post_id);
set_custom_field('field_key', 'value', $post_id);
$fields = get_all_custom_fields($post_id);
```

### Media

```php
$media = Media::find($id);
$result = Media::upload($_FILES['file']);
$image = get_featured_image($post_id);
```

### Options

```php
$value = getOption('option_name', 'default');
setOption('option_name', $value);
```

### Theme Settings

```php
$settings = getThemeSettings('theme-slug');
saveThemeSettings('theme-slug', $settings);
```

### Menus

```php
// Register a menu location
Menu::registerLocation('main-nav', 'Main Navigation');

// Display a menu
echo Menu::display('main-nav', [
    'container' => 'nav',
    'menu_class' => 'nav-menu',
    'submenu_class' => 'dropdown',
]);

// Create menu programmatically
$menuId = Menu::create(['name' => 'My Menu']);
Menu::addItem($menuId, [
    'title' => 'Home',
    'type' => 'custom',
    'url' => '/'
]);
```

---

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 🙏 Credits

- Built with ❤️ by the VoidForge team
- Icons from [Feather Icons](https://feathericons.com/)
- Fonts from [Google Fonts](https://fonts.google.com/)

---

<div align="center">

**[VoidForge CMS](https://github.com/ClearanceClarence/VoidForge-CMS)** — Modern Content Management

</div>
