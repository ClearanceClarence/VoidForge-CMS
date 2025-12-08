# VoidForge CMS

<div align="center">

![VoidForge CMS](https://img.shields.io/badge/VoidForge-CMS-6366f1?style=for-the-badge&logo=data:image/svg+xml;base64,PHN2ZyB2aWV3Qm94PSIwIDAgMjQgMjQiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS13aWR0aD0iMi41Ij48cGF0aCBkPSJNNiA0TDEyIDIwTDE4IDQiLz48L3N2Zz4=)
![Version](https://img.shields.io/badge/version-0.1.1--beta-8b5cf6?style=for-the-badge)
![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-10b981?style=for-the-badge)

**A modern, lightweight content management system built with pure PHP.**

No frameworks. No bloat. Just powerful features and elegant code.

[Features](#features) • [Installation](#installation) • [Documentation](#documentation) • [Contributing](#contributing)

</div>

---

## ✨ Features

### Content Management
- **Custom Post Types** — Create unlimited content types with custom fields, icons, and URL structures
- **Custom Fields** — 14+ field types including text, WYSIWYG, images, files, colors, dates, and more
- **Field Groups** — Create reusable field groups and assign them to any post type or users
- **Media Library** — Organize uploads with folders, automatic thumbnails, and drag-and-drop support
- **Rich Text Editor** — Built-in WYSIWYG editor with formatting toolbar

### Administration
- **Modern Admin Interface** — Beautiful dark sidebar with customizable color schemes
- **Theme Customization** — Choose from multiple color schemes, fonts, and icon styles
- **Live CSS Editor** — Real-time styling with instant preview for admin and frontend
- **Granular Font Sizes** — Separate font size controls for sidebar, header, and content areas
- **80+ Admin Icons** — Extensive icon library for post types and navigation

### User Management
- **Role-Based Permissions** — Admin, Editor, and Subscriber roles
- **User Profiles** — Gravatar support and customizable profile fields
- **Secure Authentication** — Password hashing, CSRF protection, secure sessions

### Developer Features
- **Plugin System** — WordPress-style hooks and filters for extending functionality
- **Theme Support** — Simple PHP templates with full access to all data
- **REST API** — Built-in API endpoints for security salts and more
- **Auto Updates** — One-click updates with automatic backups
- **Clean Architecture** — No framework magic, just readable PHP code

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
   git clone https://github.com/yourusername/voidforge-cms.git
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
│   │   ├── css/
│   │   │   └── admin.css  # Main admin stylesheet
│   │   └── js/
│   │       └── admin.js   # Admin JavaScript
│   ├── includes/          # Admin includes (header, footer, sidebar)
│   ├── index.php          # Admin dashboard
│   ├── posts.php          # Post management
│   ├── post-edit.php      # Post editor
│   ├── post-types.php     # Custom post types
│   ├── custom-fields.php  # Custom field groups
│   ├── media.php          # Media library
│   ├── users.php          # User management
│   ├── settings.php       # Site settings
│   ├── admin-theme.php    # Admin theme settings
│   └── ...
├── includes/              # Core PHP files
│   ├── config.php         # Database configuration (generated)
│   ├── database.php       # Database class
│   ├── functions.php      # Helper functions
│   ├── user.php           # User class
│   ├── post.php           # Post class
│   ├── media.php          # Media class
│   └── plugin.php         # Plugin system
├── plugins/               # Plugin directory
├── themes/                # Theme directory
│   └── default/           # Default theme
│       ├── header.php
│       ├── footer.php
│       ├── home.php
│       ├── single.php
│       ├── page.php
│       ├── welcome.php    # Landing page
│       └── 404.php
├── uploads/               # Media uploads
├── backups/               # Auto-update backups
├── index.php              # Front-end router
├── install.php            # Installation wizard
└── .htaccess              # Apache configuration
```

---

## 🎨 Custom Post Types

Create custom post types from the admin panel or programmatically:

```php
// Register via admin: Structure → Post Types → New Post Type

// Or use the API:
$customTypes = getOption('custom_post_types', []);
$customTypes['portfolio'] = [
    'label_singular' => 'Project',
    'label_plural' => 'Portfolio',
    'icon' => 'briefcase',
    'public' => true,
    'fields' => [
        ['label' => 'Client', 'key' => 'client', 'type' => 'text'],
        ['label' => 'URL', 'key' => 'project_url', 'type' => 'url'],
    ]
];
setOption('custom_post_types', $customTypes);
```

### Available Field Types

| Type | Description |
|------|-------------|
| `text` | Single-line text input |
| `textarea` | Multi-line text area |
| `number` | Numeric input |
| `email` | Email address |
| `url` | URL/link |
| `date` | Date picker |
| `datetime` | Date and time picker |
| `color` | Color picker |
| `select` | Dropdown selection |
| `checkbox` | Boolean checkbox |
| `image` | Image upload/select |
| `file` | File upload/select |
| `wysiwyg` | Rich text editor |

---

## 🔌 Plugin Development

Create plugins using WordPress-style hooks:

```php
<?php
/**
 * Plugin Name: My Custom Plugin
 * Description: Adds custom functionality
 * Version: 1.0.0
 * Author: Your Name
 */

// Hook into initialization
add_action('init', function() {
    // Your code here
});

// Modify content
add_filter('the_content', function($content) {
    return $content . '<p>Added by plugin!</p>';
});

// Add admin menu item
add_action('admin_menu', function() {
    // Register menu items
});
```

### Available Hooks

**Actions:**
- `init` — After core loads
- `admin_init` — Admin initialization
- `admin_menu` — Register admin menus
- `save_post` — After post is saved
- `delete_post` — Before post deletion
- `api_request` — Handle API endpoints

**Filters:**
- `the_content` — Modify post content
- `the_title` — Modify post title
- `post_query` — Modify post queries

---

## 🎭 Theming

Create custom themes in the `/themes` directory:

```php
<!-- themes/mytheme/single.php -->
<?php get_header(); ?>

<article class="post">
    <h1><?= esc($post['title']) ?></h1>
    
    <?php if ($featuredImage = get_featured_image($post['id'])): ?>
        <img src="<?= esc($featuredImage['url']) ?>" alt="">
    <?php endif; ?>
    
    <div class="content">
        <?= $post['content'] ?>
    </div>
    
    <?php 
    // Get custom field
    $client = get_custom_field('client', $post['id']);
    if ($client): ?>
        <p>Client: <?= esc($client) ?></p>
    <?php endif; ?>
</article>

<?php get_footer(); ?>
```

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
define('CMS_VERSION', '0.1.1-beta');
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
// Get all posts
$posts = Post::all();

// Query posts
$posts = Post::query([
    'post_type' => 'post',
    'status' => 'published',
    'limit' => 10,
    'orderBy' => 'created_at',
    'order' => 'DESC'
]);

// Find by ID
$post = Post::find($id);

// Find by slug
$post = Post::findBySlug('hello-world', 'post');

// Create post
$id = Post::create([
    'title' => 'My Post',
    'content' => 'Content here',
    'post_type' => 'post',
    'status' => 'published'
]);

// Update post
Post::update($id, ['title' => 'Updated Title']);

// Delete post
Post::delete($id);
```

### Custom Fields

```php
// Get single field
$value = get_custom_field('field_key', $post_id);

// Get all fields
$fields = get_all_custom_fields($post_id);

// Set field
set_custom_field('field_key', 'value', $post_id);

// Delete field
delete_custom_field('field_key', $post_id);
```

### Media

```php
// Get media item
$media = Media::find($id);

// Upload file
$result = Media::upload($_FILES['file'], $folder_id);

// Get featured image
$image = get_featured_image($post_id);

// Get thumbnails
$thumbs = Media::getThumbnails($media_id);
```

### Options

```php
// Get option
$value = getOption('option_name', 'default');

// Set option
setOption('option_name', $value);

// Delete option
deleteOption('option_name');
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

**[VoidForge CMS](https://github.com/yourusername/voidforge-cms)** — Modern Content Management

</div>
