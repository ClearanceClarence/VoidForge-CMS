<div align="center">

<br>

# ⬡ VOIDFORGE

<br>

![VoidForge CMS](https://img.shields.io/badge/VoidForge-CMS-6366f1?style=for-the-badge&logo=data:image/svg+xml;base64,PHN2ZyB2aWV3Qm94PSIwIDAgMjQgMjQiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS13aWR0aD0iMi41Ij48cGF0aCBkPSJNNiA0TDEyIDIwTDE4IDQiLz48L3N2Zz4=)
![Version](https://img.shields.io/badge/version-0.2.2-8b5cf6?style=for-the-badge)
![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-10b981?style=for-the-badge)

<br>

### A modern, lightweight content management system built with pure PHP.

**No frameworks. No bloat. Just powerful features and elegant code.**

<br>

[Features](#-features) · [Installation](#-installation) · [Theme Development](#-theme-development) · [Plugin Development](#-plugin-development) · [API Reference](#-api-reference)

<br>

---

<br>

</div>

## ✨ Features

<br>

<table>
<tr>
<td valign="top" width="50%">

### 🔨 Anvil Block Editor

| Feature | Description |
|:--------|:------------|
| **15 Block Types** | Paragraph, Heading, List, Quote, Code, Table, Image, Gallery, Video, Columns, Spacer, Separator, Button, HTML, Embed |
| **Drag & Drop** | Reorder blocks with smooth animations |
| **Block Settings** | Inline configuration panel for each block |
| **Undo/Redo** | Full history support (50 levels) |
| **Class-Based Architecture** | Extensible blocks via PHP classes |
| **Media Integration** | Seamless media library connection |

<br>

### 🎨 Anvil Live — Visual Editor

| Feature | Description |
|:--------|:------------|
| **Frontend Editing** | Edit posts directly on the live site |
| **Drag & Drop** | Visual drop indicators and column targeting |
| **Inline Text Editing** | Click to edit with rich text toolbar |
| **Device Preview** | Desktop, tablet, and mobile views |
| **Columns Support** | Drag blocks into/out of columns |
| **Autosave** | Auto-save with unsaved changes warning |

<br>

### 📝 Content Management

| Feature | Description |
|:--------|:------------|
| **Custom Post Types** | Create unlimited content types with custom fields, icons, and URL structures |
| **Custom Fields** | 16+ field types including text, WYSIWYG, images, files, colors, dates, repeaters, groups |
| **Repeater Fields** | Create dynamic lists of grouped sub-fields (team members, testimonials, FAQs) |
| **Group Fields** | Combine multiple fields into a single logical unit (addresses, SEO settings) |
| **Taxonomies** | Categories, tags, and custom taxonomies with hierarchical or flat structure |
| **Menu Builder** | Visual drag-and-drop menu management with nested items and multiple locations |
| **Post Revisions** | Automatic revision history with compare and restore functionality |
| **Admin Columns** | Fully customizable column management for post listings with drag-to-resize |
| **Bulk Actions** | Select multiple posts to trash, publish, draft, or assign taxonomies in one click |
| **Quick Edit** | Inline editing of title, slug, status, date, and taxonomies with AJAX save |
| **Scheduled Publishing** | Schedule posts to publish automatically at a future date and time |
| **Enhanced Trash** | 30-day retention with days remaining display and automatic cleanup |
| **Media Library** | Grid/list views, full-screen modal editing, keyboard navigation, drag-and-drop uploads |

</td>
<td valign="top" width="50%">

### 🎨 Theme System

| Feature | Description |
|:--------|:------------|
| **Flavor Theme** | Clean, modern theme designed to showcase all Anvil block features |
| **Block Showcase** | Landing page demonstrating all 15 block types |
| **Theme Settings** | Per-theme customization with colors, content width, and display options |
| **Block Styling** | Comprehensive CSS for all 15 Anvil block types |
| **Custom CSS** | Add custom CSS per theme without editing files |
| **Live Preview** | Real-time preview of theme changes |

<br>

### 💬 Comments System

| Feature | Description |
|:--------|:------------|
| **Threaded Comments** | Nested replies with configurable depth (1-10 levels) |
| **Guest Commenting** | Allow visitors to comment with name/email |
| **Moderation** | Approve, spam, trash, and bulk moderate comments |
| **Gravatar Support** | Automatic avatars based on email address |
| **Admin Dashboard** | Full comments management at Content → Comments |

<br>

### 🖥️ Administration

| Feature | Description |
|:--------|:------------|
| **Modern Admin Interface** | Beautiful dark sidebar with customizable color schemes |
| **Admin Theme Customization** | Choose from multiple color schemes, fonts, and icon styles |
| **Login Screen Editor** | Visual editor with 80+ settings, 12 presets, and live preview |
| **Live CSS Editor** | Real-time styling with instant preview for admin and frontend |
| **Granular Font Sizes** | Separate font size controls for sidebar, header, and content areas |
| **80+ Admin Icons** | Extensive icon library for post types and navigation |

<br>

### 👥 User Management

| Feature | Description |
|:--------|:------------|
| **Role-Based Permissions** | Admin, Editor, and Subscriber roles |
| **User Profiles** | Gravatar support and customizable profile fields |
| **Secure Authentication** | Password hashing, CSRF protection, secure sessions |

</td>
</tr>
</table>

<br>

<table>
<tr>
<td valign="top" width="50%">

### 🔌 Plugin System

| Feature | Description |
|:--------|:------------|
| **WordPress-Style Hooks** | 90+ actions and filters for extending functionality |
| **Shortcodes** | `[tag]` syntax for dynamic content |
| **Settings API** | Persistent plugin settings storage |
| **AJAX Handlers** | Easy AJAX endpoint registration |
| **Asset Enqueueing** | Script and style management |
| **Admin Pages** | Add custom admin menu items |
| **REST API Extensions** | Custom REST routes |
| **Scheduled Tasks** | Cron-like task scheduling |
| **Included Plugins** | Starter Shortcodes and Social Share examples |

</td>
<td valign="top" width="50%">

### 🛡️ Security

| Feature | Description |
|:--------|:------------|
| **CSRF Protection** | Token-based form protection |
| **XSS Prevention** | Output escaping helpers |
| **Secure Sessions** | Properly configured PHP sessions |
| **Password Security** | bcrypt password hashing |

<br>

### 🔗 REST API

| Feature | Description |
|:--------|:------------|
| **Full CRUD** | Create, read, update, delete for posts, pages, media, users, taxonomies |
| **API Key Management** | Admin interface for creating and managing API credentials |
| **Granular Permissions** | Control read/write access per API key |
| **JSON Responses** | Standard REST responses with pagination support |

<br>

### 🧑‍💻 Developer Features

| Feature | Description |
|:--------|:------------|
| **Theme Support** | Simple PHP templates with full access to all data |
| **Clean Architecture** | No framework magic, just readable PHP code |
| **Auto Updates** | One-click updates with automatic backups |
| **Plugin Documentation** | Comprehensive 72KB HTML development guide |
| **Theme Documentation** | Complete theme creation guide with examples |

</td>
</tr>
</table>

<br>

---

<br>

## 🔐 Login Screen Editor

<div align="center">

*Customize your admin login page with a powerful visual editor*

</div>

<br>

<table>
<tr>
<td align="center" width="25%">

**🎨 Backgrounds**

Solid · Gradient · Image · Pattern

*5 pattern styles*

</td>
<td align="center" width="25%">

**💳 Card Styling**

Dimensions · Border · Blur · Shadow

*Glassmorphism effects*

</td>
<td align="center" width="25%">

**✏️ Typography**

Title · Subtitle · Labels · Sizes

*Full font control*

</td>
<td align="center" width="25%">

**✨ Animation**

Fade · Slide · Scale · Bounce

*12 presets included*

</td>
</tr>
</table>

<br>

---

<br>

## 🔨 Anvil Block Editor

<div align="center">

*A powerful, intuitive block-based content editor*

</div>

<br>

<table>
<tr>
<td align="center" width="25%">

**📝 Text Blocks**

Paragraph · Heading · List · Quote · Code · Table

*Drop caps & syntax highlighting*

</td>
<td align="center" width="25%">

**🖼️ Media Blocks**

Image · Gallery · Video

*Full media library integration*

</td>
<td align="center" width="25%">

**📐 Layout Blocks**

Columns · Spacer · Separator · Button

*Responsive multi-column layouts*

</td>
<td align="center" width="25%">

**🔗 Embed Blocks**

HTML · oEmbed

*YouTube, Vimeo, custom HTML*

</td>
</tr>
</table>

<br>

### Custom Block Development

```php
// Create a custom block by extending AnvilBlock
class AlertBlock extends AnvilBlock {
    public static function getType(): string { return 'alert'; }
    public static function getLabel(): string { return 'Alert'; }
    public static function getCategory(): string { return 'layout'; }
    public static function getIcon(): string { return 'alert-circle'; }
    
    public static function getAttributes(): array {
        return [
            'content' => ['type' => 'string', 'default' => ''],
            'type' => ['type' => 'string', 'default' => 'info']
        ];
    }
    
    public static function render(array $attrs): string {
        $type = esc($attrs['type']);
        $content = esc($attrs['content']);
        return "<div class=\"alert alert-{$type}\">{$content}</div>";
    }
}

// Register the block
Anvil::registerBlockClass(AlertBlock::class);
```

📚 See `/docs/plugin-development.html` for full block development documentation.

<br>

---

<br>

## 🎨 Anvil Live — Visual Frontend Editor

<div align="center">

*Edit your pages directly on the frontend with real-time preview*

</div>

<br>

<table>
<tr>
<td align="center" width="25%">

**🖱️ Drag & Drop**

Drag blocks from sidebar · Visual drop indicators · Column targeting

*Purple line shows drop position*

</td>
<td align="center" width="25%">

**✏️ Inline Editing**

Click text to edit · Rich text toolbar · Bold, italic, links

*Real-time formatting*

</td>
<td align="center" width="25%">

**📱 Device Preview**

Desktop (1200px) · Tablet (768px) · Mobile (375px)

*Responsive preview modes*

</td>
<td align="center" width="25%">

**📊 Columns**

2-6 column layouts · Nested blocks · Move between columns

*Full drag-drop support*

</td>
</tr>
</table>

<br>

### How to Use

```
1. Navigate to any post or page on the frontend
2. Add ?anvil-live=edit to the URL
3. Drag blocks from the sidebar to the canvas
4. Click text to edit inline with the rich text toolbar
5. Press Ctrl+S to save (or click Save button)
```

<br>

### Keyboard Shortcuts

| Shortcut | Action |
|:--------:|:-------|
| `Ctrl+S` | Save content |
| `Ctrl+Z` | Undo |
| `Ctrl+Shift+Z` | Redo |
| `Delete` | Delete selected block |
| `Escape` | Deselect / Close modal |

<br>

---

<br>

## 📋 Requirements

<div align="center">

| Requirement | Version |
|:-----------:|:-------:|
| PHP | 8.0+ |
| MySQL | 5.7+ |
| MariaDB | 10.3+ |
| Apache | mod_rewrite |
| GD Library | Required |

</div>

<br>

---

<br>

## 🚀 Installation

<br>

### Quick Install

```
1. Download the latest release and extract to your web directory
2. Navigate to your site URL in a browser
3. Follow the installation wizard
4. Done! Log in to your new admin dashboard
```

<br>

### Manual Installation

```bash
# Clone or download the repository
git clone https://github.com/ClearanceClarence/VoidForge-CMS.git

# Create a MySQL database
mysql -e "CREATE DATABASE voidforge_cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"

# Copy the sample config (if available) or run the installer
cp includes/config.sample.php includes/config.php

# Configure your web server to point to the project root
# Visit your domain and complete the installation wizard
```

<br>

---

<br>

## 📁 Directory Structure

```
voidforge-cms/
│
├── 📂 admin/                    Admin panel files
│   ├── 📂 assets/              CSS, JS, images
│   │   ├── 📂 css/
│   │   │   ├── 📄 admin.css    Admin panel styles
│   │   │   └── 📄 anvil.css    Block editor styles
│   │   └── 📂 js/
│   │       ├── 📄 admin.js     Admin panel scripts
│   │       └── 📄 anvil.js     Block editor (25KB)
│   ├── 📂 includes/            Header, footer, sidebar
│   ├── 📄 index.php            Admin dashboard
│   ├── 📄 posts.php            Post management
│   ├── 📄 post-edit.php        Post editor with Anvil
│   ├── 📄 menus.php            Menu builder
│   ├── 📄 media.php            Media library
│   ├── 📄 themes.php           Theme management
│   ├── 📄 theme-settings.php   Per-theme customization
│   ├── 📄 login-editor.php     Login screen visual editor
│   ├── 📄 plugins.php          Plugin management
│   └── ...
│
├── 📂 docs/                     Documentation
│   ├── 📄 plugin-development.html
│   └── 📄 theme-development.html
│
├── 📂 includes/                 Core PHP files
│   ├── 📄 config.php           Configuration (generated)
│   ├── 📄 database.php         Database class
│   ├── 📄 functions.php        Helper functions
│   ├── 📄 anvil.php            Anvil block editor core
│   ├── 📂 anvil/               Block classes
│   │   ├── 📄 AnvilBlock.php   Base block class
│   │   └── 📂 blocks/          15 block type classes
│   ├── 📄 user.php             User class
│   ├── 📄 post.php             Post class
│   ├── 📄 media.php            Media class
│   ├── 📄 menu.php             Menu class
│   ├── 📄 plugin.php           Plugin system
│   └── 📄 theme.php            Theme system
│
├── 📂 plugins/                  Plugin directory
│   └── 📂 hello-world/         Example plugin
│
├── 📂 themes/                   Theme directory
│   └── 📂 flavor/              Default theme (block showcase)
│       ├── 📄 theme.json       Theme metadata & settings
│       ├── 📄 style.css        Theme styles (17KB)
│       ├── 📄 functions.php    Theme functions
│       ├── 📄 home.php         Block showcase landing
│       ├── 📄 single.php       Single post template
│       └── ...
│
├── 📂 uploads/                  Media uploads
├── 📂 backups/                  Auto-update backups
├── 📄 index.php                 Front-end router
├── 📄 install.php               Installation wizard
└── 📄 .htaccess                 Apache configuration
```

<br>

---

<br>

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

<br>

### Theme Settings

Themes can define customizable settings that appear in the admin:

<table>
<tr>
<td>✓ Hero section toggle and content</td>
<td>✓ Color customization</td>
<td>✓ Feature sections</td>
</tr>
<tr>
<td>✓ Stats display</td>
<td>✓ Call-to-action areas</td>
<td>✓ Custom CSS</td>
</tr>
</table>

<br>

### Menu Integration

```php
// Get menu assigned to a location
$menu = Menu::getMenuByLocation('primary');
if ($menu) {
    $items = Menu::getItems($menu['id']);
    foreach ($items as $item) {
        $url = Menu::getItemUrl($item);
        echo '<a href="' . esc($url) . '">' . esc($item['title']) . '</a>';
    }
}

// Or use the display helper
echo Menu::display('primary', [
    'container' => 'nav',
    'container_class' => 'main-navigation',
    'menu_class' => 'nav-menu',
    'submenu_class' => 'dropdown-menu',
]);
```

> **Note:** Menus must be assigned to a location in the admin to appear on the frontend. If no menu is assigned, themes fall back to displaying pages.

📚 See `/docs/theme-development.html` for comprehensive documentation.

<br>

---

<br>

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
 * Requires CMS: 0.2.2
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

📚 See `/docs/plugin-development.html` for comprehensive documentation.

<br>

---

<br>

## 🖼️ Media Library

<div align="center">

| Feature | Description |
|:-------:|:------------|
| 📊 **Grid/List Views** | Toggle between visual grid and detailed list |
| 🖼️ **Full-Screen Modal** | Large preview with editing sidebar |
| ⌨️ **Keyboard Navigation** | Arrow keys to browse, Escape to close |
| ⚡ **Quick Actions** | Edit title/alt text, copy URL, delete |
| 📥 **Drag & Drop Upload** | Drop files anywhere to upload |
| 📁 **Folder Organization** | Organize media into folders |

</div>

<br>

---

<br>

## ⚙️ Configuration

### Database Settings

```php
// includes/config.php

define('DB_HOST', 'localhost');
define('DB_NAME', 'voidforge_cms');
define('DB_USER', 'username');
define('DB_PASS', 'password');
define('DB_PREFIX', 'vf_');
```

### Site Settings

```php
define('SITE_URL', 'https://yoursite.com');
define('CMS_VERSION', '0.2.2');
define('CMS_NAME', 'VoidForge');
```

<br>

---

<br>

## 🔄 Updating

### Automatic Updates

<table>
<tr>
<td width="50">1.</td>
<td>Go to <strong>Admin → Updates</strong></td>
</tr>
<tr>
<td>2.</td>
<td>Upload the new version ZIP file</td>
</tr>
<tr>
<td>3.</td>
<td>Click <strong>Install Update</strong></td>
</tr>
<tr>
<td>4.</td>
<td>VoidForge will automatically:
  <br>✓ Create a timestamped backup
  <br>✓ Extract new files
  <br>✓ Preserve your config, uploads, and customizations
  <br>✓ Run any necessary migrations
</td>
</tr>
</table>

<br>

### Manual Updates

```
1. Backup your installation
2. Replace all files except:
   • includes/config.php
   • uploads/ directory
   • Custom themes and plugins
3. Visit the admin panel to run migrations
```

<br>

---

<br>

## 🛡️ Security Best Practices

<table>
<tr>
<td align="center">🔄</td>
<td><strong>Keep Updated</strong> — Always run the latest version</td>
</tr>
<tr>
<td align="center">🔐</td>
<td><strong>Strong Passwords</strong> — Use complex passwords for all accounts</td>
</tr>
<tr>
<td align="center">📁</td>
<td><strong>File Permissions</strong> — Set appropriate permissions (755 for directories, 644 for files)</td>
</tr>
<tr>
<td align="center">🔒</td>
<td><strong>HTTPS</strong> — Always use SSL/TLS in production</td>
</tr>
<tr>
<td align="center">💾</td>
<td><strong>Backups</strong> — Regularly backup your database and files</td>
</tr>
</table>

<br>

---

<br>

## 📖 API Reference

<br>

<table>
<tr>
<td valign="top" width="50%">

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

</td>
<td valign="top" width="50%">

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

</td>
</tr>
</table>

<br>

### Taxonomies

```php
// Register a custom taxonomy
Taxonomy::register('genre', [
    'label' => 'Genres',
    'singular' => 'Genre',
    'hierarchical' => true,
    'post_types' => ['post', 'movie'],
]);

// Get terms
$genres = Taxonomy::getTerms('genre');

// Set post terms
Taxonomy::setPostTerms($postId, 'genre', [1, 2, 3]);

// Get post terms
$postGenres = Taxonomy::getPostTerms($postId, 'genre');

// Create a term
$termId = Taxonomy::createTerm('genre', [
    'name' => 'Action',
    'description' => 'Action movies'
]);
```

<br>

---

<br>

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

```bash
# 1. Fork the repository
git clone https://github.com/ClearanceClarence/VoidForge-CMS.git

# 2. Create your feature branch
git checkout -b feature/amazing-feature

# 3. Commit your changes
git commit -m 'Add amazing feature'

# 4. Push to the branch
git push origin feature/amazing-feature

# 5. Open a Pull Request
```

<br>

---

<br>

## 📄 License

This project is licensed under the **MIT License** — see the [LICENSE](LICENSE) file for details.

<br>

---

<br>

## 🙏 Credits

<div align="center">

Built with ❤️ by the VoidForge team

[Feather Icons](https://feathericons.com/) · [Google Fonts](https://fonts.google.com/)

<br>

---

<br>

**⬡ VoidForge CMS** — Modern Content Management

<br>

</div>