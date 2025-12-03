# Forge CMS

A modern, lightweight content management system built with PHP. Simple, fast, and developer-friendly.

![Version](https://img.shields.io/badge/version-1.0.4-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4+-purple.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

## Features

- **Clean Admin Interface** - Modern dark sidebar with purple accents
- **Posts & Pages** - Full content management with rich text editor
- **Custom Post Types** - Create your own content types
- **Media Library** - Drag-and-drop uploads with folder organization
- **User Management** - Multiple roles with password strength validation
- **Plugin System** - Extend functionality with hooks, filters, and content tags
- **Theme Support** - Customizable themes with live CSS editor
- **Search** - Built-in frontend search with highlighted results
- **Auto Updates** - Upload ZIP to update, preserves your data
- **No Framework Required** - Pure PHP, easy to understand and modify

## Requirements

- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB 10.3+
- PDO MySQL extension
- ZipArchive extension (for updates)
- Apache with mod_rewrite or Nginx

## Installation

1. **Download** the latest release ZIP file

2. **Extract** to your web server directory:
   ```
   /var/www/html/      (Linux)
   C:\xampp\htdocs\    (Windows/XAMPP)
   ```

3. **Create a database** (optional - installer can create it):
   ```sql
   CREATE DATABASE forge_cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

4. **Visit your site** in a browser:
   ```
   http://localhost/cms/install.php
   ```

5. **Follow the wizard** to configure:
   - Database connection
   - Site URL and title
   - Admin account

6. **Login** to the admin panel:
   ```
   http://localhost/cms/admin/
   ```

## Directory Structure

```
forge-cms/
├── admin/                  # Admin panel
│   ├── assets/            # CSS, JS, images
│   ├── includes/          # Header, footer, sidebar
│   └── *.php              # Admin pages
├── includes/              # Core PHP files
│   ├── config.php         # Configuration (auto-generated)
│   ├── database.php       # Database class
│   ├── functions.php      # Helper functions
│   ├── user.php           # User management
│   ├── post.php           # Post/page management
│   ├── media.php          # Media library
│   └── plugin.php         # Plugin system
├── plugins/               # Plugin directory
│   └── forge-toolkit/     # Built-in toolkit plugin
├── themes/                # Theme directory
│   └── default/           # Default theme
├── uploads/               # Media uploads
├── backups/               # Update backups
└── index.php              # Front-end entry point
```

## Plugin Development

### Creating a Plugin

Create a folder in `/plugins/` with a main PHP file:

```
plugins/
└── my-plugin/
    └── my-plugin.php
```

Add the plugin header:

```php
<?php
/**
 * Plugin Name: My Plugin
 * Description: A short description of what the plugin does
 * Version: 1.0.0
 * Author: Your Name
 */

defined('CMS_ROOT') or die('Direct access not allowed');

// Your plugin code here
```

### Hooks & Filters

```php
// Add an action
add_action('plugins_loaded', function() {
    // Runs when all plugins are loaded
});

// Add to theme
add_action('theme_footer', function() {
    echo '<p>Added to footer</p>';
});

// Filter content
add_filter('the_content', function($content) {
    return $content . '<p>Added after content</p>';
});
```

### Content Tags

Plugins can register content tags for use in posts/pages:

```php
// Simple tag: {greeting}
register_tag('greeting', function($attrs) {
    $name = $attrs['name'] ?? 'World';
    return '<p>Hello, ' . esc($name) . '!</p>';
});

// Tag with content: {box}Content here{/box}
register_tag('box', function($attrs, $content) {
    $color = $attrs['color'] ?? 'blue';
    return '<div class="box box-' . esc($color) . '">' . $content . '</div>';
}, ['has_content' => true]);
```

**Usage in content:**
```
{greeting name="Adrian"}

{box color="red"}This is inside a red box{/box}
```

### Creating Pages on Activation

Plugins can create demo/settings pages when activated:

```php
add_action('plugin_activate_my-plugin', function() {
    // Check if page exists
    $existing = Post::findBySlug('my-plugin-demo', 'page');
    if ($existing) return;
    
    // Create the page
    Post::create([
        'post_type' => 'page',
        'title' => 'My Plugin Demo',
        'slug' => 'my-plugin-demo',
        'content' => '<p>Demo content with {tags}</p>',
        'status' => 'published',
        'author_id' => 1,
    ]);
});

// Optionally remove on deactivation
add_action('plugin_deactivate_my-plugin', function() {
    $page = Post::findBySlug('my-plugin-demo', 'page');
    if ($page) {
        Post::update($page['id'], ['status' => 'trash']);
    }
});
```

## Forge Toolkit Plugin

The built-in Forge Toolkit provides these content tags:

| Tag | Description | Example |
|-----|-------------|---------|
| `{button}` | Styled button | `{button href="/contact" style="primary"}Click{/button}` |
| `{alert}` | Alert box | `{alert type="info" title="Note"}Message{/alert}` |
| `{columns}` | Column layout | `{columns}{col}One{/col}{col}Two{/col}{/columns}` |
| `{code}` | Code block | `{code lang="php"}echo "hi";{/code}` |
| `{quote}` | Blockquote | `{quote author="Name"}Quote{/quote}` |
| `{card}` | Content card | `{card title="Title"}Content{/card}` |
| `{badge}` | Colored badge | `{badge color="green"}New{/badge}` |
| `{icon}` | SVG icon | `{icon name="star" color="#f00"}` |
| `{youtube}` | YouTube embed | `{youtube id="VIDEO_ID"}` |
| `{divider}` | Horizontal line | `{divider}` |
| `{spacer}` | Vertical space | `{spacer height="2rem"}` |
| `{year}` | Current year | `© {year} Company` |
| `{sitename}` | Site name | `Welcome to {sitename}` |

**Button styles:** `primary`, `secondary`, `success`, `danger`, `outline`

**Alert types:** `info`, `success`, `warning`, `danger`

**Badge colors:** `blue`, `green`, `red`, `yellow`, `purple`, `gray`

**Icons:** `star`, `heart`, `check`, `x`, `arrow-right`, `mail`, `phone`, `location`

## Theme Development

Themes are located in `/themes/`. Each theme needs:

```
themes/my-theme/
├── index.php          # Homepage template
├── single.php         # Single post template
├── page.php           # Page template
├── header.php         # Header include
├── footer.php         # Footer include
├── 404.php            # 404 error page
└── assets/
    └── css/
        └── theme.css  # Theme styles
```

### Processing Content Tags

In your templates, use `process_tags()` to render content tags:

```php
<div class="content">
    <?= process_tags($post['content']) ?>
</div>

// Or use the helper function:
<?= content($post['content']) ?>
```

## Updating

1. Go to **Admin → System Update**
2. Upload the new Forge CMS ZIP file
3. Click **Install Update**
4. Wait for completion
5. Click **Refresh Page**

**Preserved during updates:**
- Database and all content
- `includes/config.php`
- `uploads/` directory
- `backups/` directory
- `.htaccess` file

## Configuration

After installation, configuration is stored in `/includes/config.php`:

```php
// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'forge_cms');
define('DB_USER', 'root');
define('DB_PASS', '');

// URLs
define('SITE_URL', 'http://localhost/cms');
define('ADMIN_URL', SITE_URL . '/admin');

// Paths
define('ADMIN_PATH', CMS_ROOT . '/admin');
define('THEMES_PATH', CMS_ROOT . '/themes');
define('UPLOADS_PATH', CMS_ROOT . '/uploads');
define('PLUGINS_PATH', CMS_ROOT . '/plugins');

// Theme
define('CURRENT_THEME', 'default');
```

## API Reference

### Post Functions

```php
// Find post by ID
$post = Post::find($id);

// Find by slug
$post = Post::findBySlug('hello-world', 'post');
$page = Post::findBySlug('about', 'page');

// Query posts
$posts = Post::query([
    'post_type' => 'post',
    'status' => 'published',
    'limit' => 10,
    'orderby' => 'created_at',
    'order' => 'DESC',
]);

// Create post
$id = Post::create([
    'title' => 'My Post',
    'content' => 'Content here',
    'status' => 'published',
]);

// Update post
Post::update($id, ['title' => 'New Title']);

// Delete post
Post::delete($id);
```

### User Functions

```php
// Get current user
$user = User::current();

// Check role
if (User::hasRole('admin')) { ... }

// Require login
User::requireLogin();

// Require specific role
User::requireRole('editor');
```

### Media Functions

```php
// Upload file
$media = Media::upload($_FILES['file']);

// Get media by ID
$media = Media::find($id);

// Get all media
$media = Media::getAll();

// Delete media
Media::delete($id);
```

### Helper Functions

```php
// Escape output
echo esc($string);

// Generate URL-friendly slug
$slug = slugify('My Post Title');

// Format date
echo formatDate($date, 'M j, Y');

// Get/set options
$value = getOption('site_name', 'Default');
setOption('site_name', 'New Name');

// Redirect
redirect('/admin/');

// Flash messages
setFlash('success', 'Post saved!');
$message = getFlash('success');
```

## Security

- CSRF protection on all forms
- Password hashing with `password_hash()`
- Prepared statements for all database queries
- Input escaping with `htmlspecialchars()`
- Role-based access control

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## License

MIT License - feel free to use for personal and commercial projects.

## Credits

Built with ❤️ using:
- [Inter Font](https://rsms.me/inter/)
- [Lucide Icons](https://lucide.dev/) (icon style)

---

**Forge CMS** - Simple, Fast, Flexible
