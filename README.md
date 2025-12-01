# Forge CMS

<p align="center">
  <img src="https://img.shields.io/badge/version-1.0.2-blue.svg" alt="Version">
  <img src="https://img.shields.io/badge/PHP-8.0+-purple.svg" alt="PHP 8.0+">
  <img src="https://img.shields.io/badge/MySQL-5.7+-orange.svg" alt="MySQL 5.7+">
  <img src="https://img.shields.io/badge/license-MIT-green.svg" alt="License">
</p>

<p align="center">
A modern, lightweight content management system built with PHP. Simple, fast, and developer-friendly.
</p>

---

## ✨ Features

- **📝 Rich Content Editor** - Visual and code editor with formatting tools
- **🖼️ Media Library** - Upload, organize, and manage media files with folder support
- **👥 User Management** - Multiple user roles with Gravatar integration
- **🎨 Live CSS Editor** - Customize your theme with real-time preview
- **🔌 Plugin System** - Extend functionality with hooks and filters
- **📱 Responsive Design** - Modern admin interface that works on all devices
- **🚀 Lightweight** - No dependencies, under 500KB total
- **⚡ Fast** - Built for performance with clean, efficient code

## 📋 Requirements

- PHP 8.0 or higher
- MySQL 5.7 or higher (or MariaDB 10.2+)
- PDO MySQL extension
- Apache with mod_rewrite (or Nginx)

## 🚀 Quick Start

### Option 1: Download Release

1. Download the latest release
2. Extract to your web server directory
3. Navigate to `http://yoursite.com/install.php`
4. Follow the installation wizard

### Option 2: Clone Repository

```bash
git clone https://github.com/ClearanceClarence/Forge-CMS.git
cd forge-cms
```

Then visit `http://yoursite.com/install.php` in your browser.

## 📁 Directory Structure

```
forge-cms/
├── admin/              # Admin dashboard
│   ├── assets/         # CSS, JS, images
│   ├── includes/       # Header, footer, sidebar
│   └── *.php          # Admin pages
├── includes/           # Core PHP classes
│   ├── config.php     # Configuration (generated)
│   ├── database.php   # Database wrapper
│   ├── functions.php  # Helper functions
│   ├── user.php       # User management
│   ├── post.php       # Post/page management
│   ├── media.php      # Media management
│   └── plugin.php     # Plugin system
├── plugins/            # Plugin directory
├── themes/             # Theme directory
│   └── default/       # Default theme
├── uploads/            # Media uploads
├── index.php          # Front controller
└── install.php        # Installation wizard
```

## 🔧 Configuration

After installation, the configuration file is located at `includes/config.php`:

```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'forge_cms');
define('DB_USER', 'root');
define('DB_PASS', '');

define('SITE_URL', 'http://localhost/forge-cms');
define('ADMIN_URL', SITE_URL . '/admin');

define('CMS_NAME', 'Forge');
define('CMS_VERSION', '1.0.2');
```

## 👥 User Roles

| Role | Capabilities |
|------|-------------|
| **Admin** | Full access to all features |
| **Editor** | Manage all posts and media |
| **Author** | Create and manage own posts |
| **Subscriber** | View content only |

## 🔌 Plugin Development

Create a plugin in `plugins/your-plugin/your-plugin.php`:

```php
<?php
/**
 * Plugin Name: Your Plugin
 * Description: A description of your plugin
 * Version: 1.0.0
 * Author: Your Name
 */

// Add action hook
Plugin::addAction('init', function() {
    // Your code here
});

// Add filter
Plugin::addFilter('the_content', function($content) {
    return $content . '<p>Added by plugin!</p>';
});
```

### Available Hooks

**Actions:**
- `init` - Fires on initialization
- `admin_init` - Fires on admin initialization
- `plugins_loaded` - Fires after all plugins are loaded
- `save_post` - Fires when a post is saved
- `delete_post` - Fires when a post is deleted

**Filters:**
- `the_content` - Filter post content
- `the_title` - Filter post title
- `the_excerpt` - Filter post excerpt

## 🎨 Theme Development

Themes are located in the `themes/` directory. The default theme structure:

```
themes/default/
├── index.php      # Homepage template
├── single.php     # Single post template
├── page.php       # Page template
├── header.php     # Header partial
├── footer.php     # Footer partial
├── 404.php        # Not found template
└── assets/
    └── css/
        └── theme.css
```

## 🔒 Security

- CSRF protection on all forms
- Password hashing with bcrypt (cost 12)
- SQL injection prevention with prepared statements
- XSS protection with output escaping
- File upload validation and restrictions

## 📝 API Reference

### Database

```php
// Query multiple rows
$posts = Database::query("SELECT * FROM posts WHERE status = ?", ['published']);

// Query single row
$post = Database::queryOne("SELECT * FROM posts WHERE id = ?", [$id]);

// Insert and get ID
$id = Database::insert("INSERT INTO posts (title) VALUES (?)", [$title]);

// Update/Delete
Database::execute("UPDATE posts SET title = ? WHERE id = ?", [$title, $id]);
```

### Posts

```php
// Get all posts
$posts = Post::getAll(['post_type' => 'post', 'status' => 'published']);

// Get single post
$post = Post::find($id);
$post = Post::findBySlug('hello-world', 'post');

// Create post
$id = Post::create([
    'title' => 'My Post',
    'content' => 'Content here',
    'status' => 'published'
]);

// Update post
Post::update($id, ['title' => 'Updated Title']);

// Delete post
Post::delete($id);
```

### Users

```php
// Get current user
$user = User::current();

// Check login status
if (User::isLoggedIn()) { ... }

// Check role
if (User::hasRole('admin')) { ... }

// Login
User::login($username, $password);

// Logout
User::logout();
```

### Media

```php
// Upload file
$result = Media::upload($_FILES['file'], $userId, $folderId);

// Get media
$media = Media::get($id);

// Get all media
$items = Media::getAll($folderId);

// Delete media
Media::delete($id);
```

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- [Inter Font](https://rsms.me/inter/) - Beautiful UI font
- [Gravatar](https://gravatar.com/) - Profile images

---

<p align="center">
Made with ❤️ for creators everywhere
</p>
