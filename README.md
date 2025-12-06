# Forge CMS

A modern, lightweight content management system built with PHP. Forge CMS provides a clean, intuitive admin interface with powerful features for managing your website content.

![Forge CMS](https://img.shields.io/badge/version-1.0.10-6366f1) ![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4) ![License](https://img.shields.io/badge/license-MIT-green)

## ✨ Features

### Content Management
- **Posts & Pages** - Create and manage blog posts and static pages with a rich text editor
- **Custom Post Types** - Define your own content types (products, portfolios, testimonials, etc.)
- **Custom Fields** - Add custom data fields to any post type (text, numbers, dates, images, etc.)
- **Media Library** - Upload, organize, and manage images and files with folder support
- **Categories & Tags** - Organize content with taxonomies

### Admin Interface
- **Modern Dashboard** - Clean, responsive admin panel with quick stats and recent activity
- **Live Preview** - Preview posts before publishing
- **Customizable Theme** - Change admin colors and branding
- **User Management** - Multiple user roles (Admin, Editor, Author)

### Developer Features
- **Theme System** - Create custom themes with PHP templates
- **Plugin Architecture** - Extend functionality with plugins
- **Custom Field Functions** - Easy programmatic access to custom data
- **Clean URLs** - SEO-friendly permalink structure
- **Auto-Updates** - Upload ZIP files to update the CMS

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Apache with mod_rewrite enabled
- PHP Extensions: PDO, PDO_MySQL, GD, JSON, ZIP

## 🚀 Installation

1. **Download** the latest release and extract to your web server
2. **Navigate** to your site in a browser (e.g., `http://localhost/forge-cms/`)
3. **Follow** the installation wizard:
   - Enter database credentials
   - Set site URL and title
   - Create admin account
4. **Done!** Access the admin panel at `/admin`

### Manual Database Setup (Optional)

If you prefer to set up the database manually:

```sql
CREATE DATABASE forge_cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## 📁 Directory Structure

```
forge-cms/
├── admin/              # Admin panel files
│   ├── assets/         # Admin CSS, JS, images
│   ├── includes/       # Header, footer, sidebar
│   └── *.php           # Admin pages
├── includes/           # Core PHP classes
│   ├── config.php      # Configuration (auto-generated)
│   ├── database.php    # Database connection
│   ├── functions.php   # Helper functions
│   ├── post.php        # Post class
│   ├── user.php        # User authentication
│   ├── media.php       # Media handling
│   └── plugin.php      # Plugin system
├── themes/             # Frontend themes
│   └── default/        # Default theme
├── plugins/            # Plugin directory
├── uploads/            # Media uploads
├── backups/            # Update backups
└── index.php           # Frontend entry point
```

## 🎨 Theming

Themes are located in the `/themes` directory. Each theme should contain:

```
themes/your-theme/
├── index.php          # Homepage template
├── single.php         # Single post template
├── page.php           # Page template
├── search.php         # Search results
├── 404.php            # Not found page
├── header.php         # Header partial
├── footer.php         # Footer partial
└── assets/            # Theme assets (CSS, JS, images)
```

### Template Tags

```php
// Get site info
<?= SITE_URL ?>
<?= CMS_NAME ?>

// Get posts
$posts = Post::query(['post_type' => 'post', 'status' => 'published', 'limit' => 10]);

// Display post data
<?= esc($post['title']) ?>
<?= $post['content'] ?>
<?= esc($post['excerpt']) ?>

// Get featured image
<?= get_featured_image_url($post['id']) ?>

// Get custom fields
<?= get_custom_field('price', $post['id']) ?>
<?= get_custom_field('sku', $post['id']) ?>

// Get all custom fields
$fields = get_all_custom_fields($post['id']);
```

### Custom Post Type Templates

Create type-specific templates by naming them `single-{post_type}.php`:

```
themes/your-theme/
├── single.php           # Default single template
├── single-product.php   # Product post type template
├── single-portfolio.php # Portfolio post type template
```

## 🔧 Custom Post Types

### Creating via Admin

1. Go to **Settings → Post Types**
2. Click **New Post Type**
3. Configure:
   - Labels (singular/plural)
   - URL slug
   - Icon
   - Features (title, editor, thumbnail, etc.)
   - Custom fields

### Custom Fields in Templates

```php
// Get a single field
$price = get_custom_field('price', $post['id']);
$color = get_custom_field('color', $post['id'], '#000000'); // with default

// Get all fields
$fields = get_all_custom_fields($post['id']);

// Set a field programmatically
set_custom_field('price', 29.99, $post['id']);

// Delete a field
delete_custom_field('old_field', $post['id']);
```

### Available Field Types

| Type | Description |
|------|-------------|
| `text` | Single line text |
| `textarea` | Multi-line text |
| `number` | Numeric input |
| `email` | Email address |
| `url` | URL/link |
| `date` | Date picker |
| `datetime` | Date and time |
| `color` | Color picker |
| `select` | Dropdown menu |
| `checkbox` | Yes/no toggle |
| `image` | Image from media library |
| `file` | File upload |
| `wysiwyg` | Rich text editor |

## 🔌 Plugins

Plugins extend Forge CMS functionality. Place plugins in `/plugins/plugin-name/`.

### Forge Toolkit (Built-in)

The **Forge Toolkit** plugin provides 30+ shortcodes for building rich content pages:

**Content Components:**
- `{button}` - Styled buttons with icons
- `{alert}` - Info, success, warning, error alerts
- `{card}` - Content cards with icons
- `{quote}` - Blockquotes (classic & modern styles)
- `{code}` - Syntax-highlighted code blocks
- `{badge}` - Inline badges and labels

**Layout Components:**
- `{tabs}` - Tabbed content panels
- `{accordion}` - Collapsible FAQ sections
- `{grid}` - Responsive grid layouts
- `{columns}` - Multi-column layouts
- `{timeline}` - Vertical timelines

**Data Display:**
- `{progress}` - Progress bars (animated/striped)
- `{stats}` - Statistics counters
- `{pricing}` - Pricing tables
- `{testimonial}` - Customer testimonials
- `{features}` - Feature lists with checkmarks

**Media & Utilities:**
- `{youtube}` / `{vimeo}` - Video embeds
- `{icon}` - 60+ SVG icons
- `{tooltip}` - Hover tooltips
- `{modal}` - Modal dialogs
- `{salts}` - Security key generator

Activate the plugin and visit `/toolkit-demo` for a complete interactive demo.

### Plugin Structure

```php
<?php
/**
 * Plugin Name: My Plugin
 * Description: What it does
 * Version: 1.0.0
 * Author: Your Name
 */

// Hook into actions
Plugin::addAction('after_post_save', function($postId) {
    // Do something after a post is saved
});

// Add filters
Plugin::addFilter('post_content', function($content) {
    return str_replace('foo', 'bar', $content);
});
```

### Available Hooks

**Actions:**
- `init` - After CMS initializes
- `after_post_save` - After a post is saved
- `after_post_delete` - After a post is deleted
- `admin_head` - In admin `<head>`
- `admin_footer` - Before admin `</body>`

**Filters:**
- `post_content` - Filter post content
- `post_title` - Filter post title
- `the_excerpt` - Filter excerpt

## ⚙️ Configuration

After installation, configuration is stored in `/includes/config.php`:

```php
// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'forge_cms');
define('DB_USER', 'username');
define('DB_PASS', 'password');
define('DB_PREFIX', 'forge_');

// Site
define('SITE_URL', 'http://example.com');
define('CURRENT_THEME', 'default');
```

## 🔄 Updates

### Automatic Updates

1. Download the new version ZIP
2. Go to **Settings → Updates**
3. Upload the ZIP file
4. Click **Install Update**

The system automatically:
- Creates a backup
- Extracts new files
- Preserves your config, uploads, and themes
- Runs database migrations

### Manual Updates (FTP)

1. Download and extract the new version
2. Upload files via FTP (skip `config.php`, `uploads/`, custom themes)
3. Visit `/admin/update.php?run_migrations=1`

## 🔒 Security

- CSRF protection on all forms
- Prepared statements for database queries
- Password hashing with bcrypt
- Session security with regeneration
- Input sanitization and output escaping

### Security Keys & Salts API

Forge CMS includes a WordPress-style security salt generator. Generate cryptographically secure keys for your configuration:

**API Endpoints** (when Forge Toolkit plugin is active):

| Endpoint | Format | Description |
|----------|--------|-------------|
| `/api/salts` | PHP | Returns `define()` constants ready for config.php |
| `/api/salts/json` | JSON | Returns keys as JSON object |

**Example Usage:**

```bash
# Get PHP constants
curl http://yoursite.com/api/salts

# Get JSON format
curl http://yoursite.com/api/salts/json
```

**Generated Keys:**
- `AUTH_KEY`, `SECURE_AUTH_KEY`, `LOGGED_IN_KEY`, `NONCE_KEY`
- `AUTH_SALT`, `SECURE_AUTH_SALT`, `LOGGED_IN_SALT`, `NONCE_SALT`
- `SESSION_KEY`, `CSRF_KEY`, `API_KEY`, `ENCRYPTION_KEY`

You can also use the `{salts}` shortcode on any page to display a salt generator with a regenerate button.

## 📖 API Reference

### Post Class

```php
// Query posts
Post::query([
    'post_type' => 'post',
    'status' => 'published',
    'limit' => 10,
    'offset' => 0,
    'orderby' => 'created_at',
    'order' => 'DESC'
]);

// Get single post
Post::find($id);
Post::findBySlug($slug, $postType);

// Count posts
Post::count(['post_type' => 'post', 'status' => 'published']);

// Save post
Post::save([
    'title' => 'My Post',
    'content' => 'Content here',
    'post_type' => 'post',
    'status' => 'published'
]);

// Delete post
Post::delete($id);
```

### Media Class

```php
// Upload file
Media::upload($_FILES['file']);

// Get media
Media::find($id);
Media::query(['folder_id' => 1, 'limit' => 20]);

// Delete media
Media::delete($id);
```

### User Class

```php
// Authentication
User::login($username, $password);
User::logout();
User::isLoggedIn();
User::current();

// Authorization
User::requireLogin();
User::requireRole('admin');
User::hasRole('editor');
```

### Helper Functions

```php
// Escaping
esc($string);           // HTML escape
esc_attr($string);      // Attribute escape

// URLs
url($path);             // Site URL
admin_url($path);       // Admin URL

// Options
getOption($key, $default);
setOption($key, $value);
deleteOption($key);

// Custom Fields
get_custom_field($key, $postId, $default);
set_custom_field($key, $value, $postId);
get_all_custom_fields($postId);

// Security
csrfToken();
verifyCsrf($token);
```

## 🐛 Troubleshooting

### Common Issues

**404 errors on posts/pages**
- Ensure mod_rewrite is enabled
- Check `.htaccess` file exists and is readable
- Verify `AllowOverride All` in Apache config

**Database connection failed**
- Verify credentials in `config.php`
- Ensure MySQL/MariaDB is running
- Check database user permissions

**Upload errors**
- Check `/uploads` directory permissions (755 or 775)
- Verify PHP `upload_max_filesize` and `post_max_size`

**White screen / 500 error**
- Enable error reporting in `config.php`
- Check PHP error logs
- Verify PHP version requirements

## 📄 License

Forge CMS is open-source software licensed under the MIT License.

## 🤝 Contributing

Contributions are welcome! Please feel free to submit issues and pull requests.

---

Built with ❤️ by the Forge CMS team
