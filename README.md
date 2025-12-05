# Forge CMS

**A modern, lightweight content management system built with pure PHP.**

Forge CMS is designed for developers who want full control without the bloat. Zero external dependencies, clean architecture, and a beautiful admin interface.

---

## Table of Contents

1. [Features](#features)
2. [Requirements](#requirements)
3. [Installation](#installation)
4. [Quick Start](#quick-start)
5. [Directory Structure](#directory-structure)
6. [Admin Interface](#admin-interface)
7. [Content Management](#content-management)
8. [Custom Post Types](#custom-post-types)
9. [Custom Fields](#custom-fields)
10. [Media Library](#media-library)
11. [Themes](#themes)
12. [Plugins](#plugins)
13. [Admin Theming](#admin-theming)
14. [User Roles](#user-roles)
15. [Security](#security)
16. [API Reference](#api-reference)
17. [Troubleshooting](#troubleshooting)
18. [Contributing](#contributing)
19. [License](#license)

---

## Features

### Core CMS
- **Posts & Pages** — Full CRUD with revisions, drafts, and scheduling
- **Custom Post Types** — Visual builder for any content structure
- **Custom Fields** — 14 field types including WYSIWYG, repeaters, and file uploads
- **Media Library** — Drag-and-drop uploads with automatic thumbnail generation
- **User Management** — Role-based access control (Admin, Editor, Author)
- **SEO-Friendly URLs** — Customizable slugs independent from titles

### Admin Interface
- **Modern Dashboard** — Clean, responsive design with quick stats
- **Admin Themes** — 6 color schemes, 6 fonts, 3 icon styles
- **Live CSS Editor** — Real-time preview for admin and frontend styles
- **Collapsible Navigation** — Organized sidebar with expandable submenus

### Developer Features
- **Plugin System** — Hooks, filters, and shortcodes
- **Theme System** — Clean template hierarchy
- **Menu API** — Dynamically register sidebar items
- **Auto Updates** — ZIP upload with backup and rollback
- **Database Migrations** — Automatic schema updates

### Performance
- **< 200KB** — Total package size
- **0 Dependencies** — No Composer, no npm, no frameworks
- **< 50ms** — Typical response time
- **PHP 8+ Native** — Modern, type-safe code

---

## Requirements

| Component | Minimum Version |
|-----------|----------------|
| PHP | 8.0+ |
| MySQL | 5.7+ |
| MariaDB | 10.3+ (alternative to MySQL) |
| Apache/Nginx | With mod_rewrite enabled |
| GD Library | For image processing |

### PHP Extensions Required
- `pdo_mysql` — Database connectivity
- `gd` — Image manipulation
- `json` — Data handling
- `mbstring` — String functions

---

## Installation

### Method 1: Quick Install

1. Download `forge-cms-v1.0.7.zip`
2. Extract to your web directory (e.g., `/var/www/html/` or `htdocs/`)
3. Create a MySQL database
4. Navigate to `http://yoursite.com/install.php`
5. Enter database credentials and create admin account
6. Delete `install.php` after installation

### Method 2: Manual Setup

1. Extract files to web directory
2. Copy `includes/config.sample.php` to `includes/config.php`
3. Edit `config.php` with your database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'forge_cms');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_PREFIX', 'forge_');

define('SITE_URL', 'http://yoursite.com');
define('SITE_NAME', 'My Website');
```

4. Import the database schema from `install.php` or run migrations
5. Create an admin user in the `users` table

### Post-Installation

- Set proper permissions:
  ```bash
  chmod 755 /path/to/forge-cms
  chmod -R 755 uploads/
  chmod 644 includes/config.php
  ```
- Configure `.htaccess` for pretty URLs (included by default)
- Remove `install.php` for security

---

## Quick Start

### Accessing the Admin

Navigate to `http://yoursite.com/admin/` and log in with your credentials.

### Creating Your First Post

1. Go to **Content → Posts**
2. Click **New Post**
3. Enter title and content
4. Set status to **Published**
5. Click **Save**

### Customizing Your Site

1. Go to **Design → Customize**
2. Edit frontend CSS in the live editor
3. Preview changes in real-time
4. Click **Save Changes**

---

## Directory Structure

```
forge-cms/
├── admin/                    # Admin panel
│   ├── assets/              # Admin CSS, JS, images
│   │   ├── css/
│   │   │   └── admin.css    # Main admin stylesheet
│   │   ├── js/
│   │   │   └── admin.js     # Admin JavaScript
│   │   └── images/
│   ├── includes/            # Admin partials
│   │   ├── header.php       # Admin header with theme loading
│   │   ├── footer.php       # Admin footer
│   │   └── sidebar.php      # Navigation sidebar
│   ├── index.php            # Dashboard
│   ├── posts.php            # Post listing
│   ├── post-edit.php        # Post editor
│   ├── media.php            # Media library
│   ├── thumbnails.php       # Thumbnail manager
│   ├── users.php            # User management
│   ├── settings.php         # Site settings
│   ├── post-types.php       # Custom post type builder
│   ├── admin-theme.php      # Admin theme customizer
│   ├── customize.php        # Live CSS editor
│   ├── plugins.php          # Plugin manager
│   ├── update.php           # System updater
│   └── login.php            # Authentication
│
├── includes/                 # Core PHP classes
│   ├── config.php           # Configuration constants
│   ├── database.php         # PDO wrapper class
│   ├── functions.php        # Helper functions
│   ├── user.php             # User class
│   ├── post.php             # Post class
│   ├── media.php            # Media class
│   ├── plugin.php           # Plugin system
│   └── migrations.php       # Database migrations
│
├── plugins/                  # Plugin directory
│   └── forge-toolkit/       # Example plugin
│       └── forge-toolkit.php
│
├── themes/                   # Theme directory
│   └── default/             # Default theme
│       ├── index.php        # Homepage template
│       ├── single.php       # Single post template
│       ├── page.php         # Page template
│       ├── header.php       # Theme header
│       ├── footer.php       # Theme footer
│       ├── 404.php          # Not found template
│       ├── search.php       # Search results
│       └── assets/          # Theme assets
│
├── uploads/                  # User uploads
│   └── [year]/[month]/      # Organized by date
│
├── backups/                  # System backups
│
├── index.php                 # Front controller
├── install.php               # Installer (delete after use)
├── .htaccess                 # Apache rewrite rules
├── README.md                 # This file
└── CHANGELOG.md              # Version history
```

---

## Admin Interface

### Dashboard

The dashboard provides an overview of your site:
- Post count with status breakdown
- Page count
- Media library size
- User count
- Recent activity
- Quick action buttons

### Navigation

The sidebar organizes features into logical groups:

**Content**
- Dashboard
- Posts (with count)
- Pages (with count)
- Custom Post Types

**Media**
- Library
- Thumbnails (admin only)

**Design**
- Customize (Live CSS editor)

**Admin**
- Users
- Settings
  - General
  - Post Types
  - Admin Theme
- Tools
  - Update
  - Plugins

---

## Content Management

### Posts

Posts are the primary content type. Each post has:

| Field | Description |
|-------|-------------|
| Title | The post headline |
| Slug | URL-friendly identifier (auto-generated or custom) |
| Content | Rich text content with WYSIWYG editor |
| Excerpt | Summary text (manual or auto-generated) |
| Status | draft, published, or trash |
| Featured Image | Thumbnail for listings |
| Author | Post creator |
| Created/Updated | Timestamps |

### Pages

Pages work like posts but are designed for static content (About, Contact, etc.).

### Statuses

- **Draft** — Work in progress, not visible on frontend
- **Published** — Live and visible
- **Trash** — Soft-deleted, can be restored

### Slugs

Slugs are URL-friendly identifiers. By default, they're generated from the title, but you can customize them:

```
Title: "My Amazing Post!"
Auto-slug: my-amazing-post
Custom slug: awesome-post → yoursite.com/awesome-post
```

---

## Custom Post Types

Create custom content structures without writing code.

### Creating a Post Type

1. Go to **Settings → Post Types**
2. Click **New Post Type**
3. Configure:
   - **Singular Label**: e.g., "Product"
   - **Plural Label**: e.g., "Products"
   - **Slug**: e.g., "product" (URL identifier)
   - **Icon**: Choose from 12 icons
   - **Public**: Whether to show on frontend
   - **Has Archive**: Enable archive pages
   - **Supports**: Title, Editor, Excerpt, Thumbnail, Author, Comments

### Example: Products

```
Singular: Product
Plural: Products
Slug: product
Icon: box
Supports: Title, Editor, Thumbnail
Custom Fields: Price, SKU, Stock Status
```

Results in URLs like: `yoursite.com/product/blue-widget`

---

## Custom Fields

Add structured data to any post type with 14 field types.

### Available Field Types

| Type | Description | Example Use |
|------|-------------|-------------|
| `text` | Single line text | Product SKU |
| `textarea` | Multi-line text | Short description |
| `number` | Numeric input | Price, quantity |
| `email` | Email validation | Contact email |
| `url` | URL validation | External link |
| `date` | Date picker | Event date |
| `datetime` | Date and time | Event start time |
| `select` | Dropdown menu | Category selection |
| `radio` | Radio buttons | Size options |
| `checkbox` | Checkboxes | Features list |
| `image` | Image upload | Product gallery |
| `file` | File upload | Downloadable PDF |
| `wysiwyg` | Rich text editor | Detailed description |
| `color` | Color picker | Product color |

### Adding Custom Fields

1. Edit a post type in **Settings → Post Types**
2. Click **Add Field**
3. Configure:
   - **Label**: Display name
   - **Key**: Database identifier (lowercase, underscores)
   - **Type**: Select from dropdown
   - **Options**: For select/radio/checkbox (one per line)
   - **Required**: Validation flag

### Field Configuration Example

```
Label: Price
Key: price
Type: number
Required: Yes

Label: Product Color
Key: product_color
Type: select
Options:
  Red
  Blue
  Green
  Black
```

---

## Media Library

### Uploading Files

- Drag and drop files onto the media library
- Click "Upload" to select files
- Supports: JPG, PNG, GIF, WebP, PDF, and more

### Automatic Thumbnails

When you upload an image, Forge CMS automatically generates:

| Size | Dimensions | Crop |
|------|------------|------|
| thumbnail | 150×150 | Yes |
| medium | 300×300 | No |
| large | 1024×1024 | No |

### Thumbnail Manager

Access via **Media → Thumbnails**:
- View all images and their thumbnail status
- Regenerate thumbnails individually or in bulk
- System diagnostics (GD library, supported formats)

### Using Media in Content

Click the media button in the editor to:
- Browse existing uploads
- Upload new files
- Insert images with size selection
- Copy URLs for manual use

---

## Themes

### Template Hierarchy

Forge CMS looks for templates in this order:

**Single Post:**
1. `single-{post_type}.php`
2. `single.php`

**Page:**
1. `page-{slug}.php`
2. `page.php`

**Archive:**
1. `archive-{post_type}.php`
2. `archive.php`

**Homepage:**
1. `front-page.php`
2. `index.php`

### Creating a Theme

1. Create a folder in `/themes/`
2. Add required files:
   - `index.php` — Main template
   - `style.css` — Theme info header

```php
<?php
/**
 * Theme Name: My Theme
 * Description: A custom theme
 * Version: 1.0.0
 * Author: Your Name
 */
```

### Theme Functions

```php
// Get site info
<?= SITE_NAME ?>
<?= SITE_URL ?>

// Get posts
$posts = Post::query([
    'post_type' => 'post',
    'status' => 'published',
    'limit' => 10
]);

// Display post
<?= $post['title'] ?>
<?= $post['content'] ?>
<?= $post['excerpt'] ?>

// Get thumbnail
<?= Post::getThumbnailUrl($post['id'], 'medium') ?>

// Get custom field
<?= Post::getMeta($post['id'], 'price') ?>
```

---

## Plugins

### Plugin Structure

```
plugins/
└── my-plugin/
    └── my-plugin.php
```

### Plugin Header

```php
<?php
/**
 * Plugin Name: My Plugin
 * Description: What it does
 * Version: 1.0.0
 * Author: Your Name
 */
```

### Hooks and Filters

```php
// Action hook
add_action('init', function() {
    // Runs on initialization
});

// Filter hook
add_filter('the_content', function($content) {
    return $content . '<p>Added by plugin</p>';
});

// Activation hook
register_activation_hook(__FILE__, function() {
    // Setup code
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    // Cleanup code
});
```

### Shortcodes

```php
add_shortcode('greeting', function($atts) {
    $atts = shortcode_atts([
        'name' => 'World'
    ], $atts);
    
    return "<p>Hello, {$atts['name']}!</p>";
});

// Usage: [greeting name="Adrian"]
```

### Admin Menu Registration

```php
// Add top-level menu
registerAdminMenu('my-plugin', [
    'label' => 'My Plugin',
    'icon' => 'box',
    'url' => ADMIN_URL . '/my-plugin.php',
    'capability' => 'admin',
    'position' => 60
]);

// Add submenu
registerAdminSubmenu('settings', 'my-settings', [
    'label' => 'My Settings',
    'url' => ADMIN_URL . '/my-settings.php',
    'capability' => 'admin'
]);
```

---

## Admin Theming

Personalize your admin experience at **Settings → Admin Theme**.

### Color Schemes

| Scheme | Primary | Secondary | Description |
|--------|---------|-----------|-------------|
| Indigo | #6366f1 | #8b5cf6 | Default purple theme |
| Ocean | #0ea5e9 | #06b6d4 | Blue and cyan |
| Emerald | #10b981 | #14b8a6 | Green and teal |
| Rose | #f43f5e | #ec4899 | Pink and red |
| Amber | #f59e0b | #eab308 | Orange and yellow |
| Slate | #64748b | #94a3b8 | Neutral gray |

### Typography Options

- Inter (default)
- Poppins
- Nunito
- Roboto
- Source Sans
- DM Sans

### Icon Styles

| Style | Stroke Width | Description |
|-------|-------------|-------------|
| Outlined | 2px | Default, balanced |
| Light | 1.5px | Thinner, elegant |
| Bold | 2.5px | Heavier, prominent |

### Preferences

- **Animations**: Toggle transitions and hover effects
- **Compact Sidebar**: Reduce spacing for more content area

---

## User Roles

### Role Capabilities

| Capability | Admin | Editor | Author |
|------------|-------|--------|--------|
| Manage all posts | ✓ | ✓ | Own only |
| Manage pages | ✓ | ✓ | ✗ |
| Manage media | ✓ | ✓ | ✓ |
| Manage users | ✓ | ✗ | ✗ |
| Manage settings | ✓ | ✗ | ✗ |
| Manage plugins | ✓ | ✗ | ✗ |
| Access thumbnails | ✓ | ✗ | ✗ |
| System updates | ✓ | ✗ | ✗ |

### Creating Users

1. Go to **Admin → Users**
2. Click **Add User**
3. Enter username, email, password
4. Select role
5. Save

---

## Security

### Built-in Protection

- **CSRF Tokens**: All forms include token validation
- **Password Hashing**: bcrypt with automatic rehashing
- **SQL Injection Prevention**: Prepared statements throughout
- **XSS Prevention**: Output escaping helpers
- **Session Security**: Secure session handling

### Security Functions

```php
// Generate CSRF field
<?= csrfField() ?>

// Verify CSRF token
if (verifyCsrf()) {
    // Process form
}

// Escape output
<?= esc($userInput) ?>

// Escape for attributes
<input value="<?= escAttr($value) ?>">
```

### Recommendations

1. Delete `install.php` after installation
2. Use strong passwords
3. Keep PHP and MySQL updated
4. Set proper file permissions
5. Use HTTPS in production
6. Regular backups

---

## API Reference

### Database Class

```php
// Get instance
$db = Database::getInstance();

// Query
$results = $db->query("SELECT * FROM posts WHERE status = ?", ['published']);

// Single row
$post = $db->fetch("SELECT * FROM posts WHERE id = ?", [$id]);

// Insert
$id = $db->insert('posts', [
    'title' => 'New Post',
    'content' => 'Content here'
]);

// Update
$db->update('posts', ['title' => 'Updated'], 'id = ?', [$id]);

// Delete
$db->delete('posts', 'id = ?', [$id]);
```

### Post Class

```php
// Query posts
$posts = Post::query([
    'post_type' => 'post',
    'status' => 'published',
    'limit' => 10,
    'offset' => 0,
    'orderby' => 'created_at',
    'order' => 'DESC',
    'search' => 'keyword'
]);

// Get single post
$post = Post::find($id);
$post = Post::findBySlug('my-post');

// Create post
$id = Post::create([
    'title' => 'Title',
    'content' => 'Content',
    'post_type' => 'post',
    'status' => 'published'
]);

// Update post
Post::update($id, ['title' => 'New Title']);

// Delete post
Post::delete($id);

// Post meta
Post::setMeta($id, 'key', 'value');
$value = Post::getMeta($id, 'key');
```

### User Class

```php
// Authentication
User::login($username, $password);
User::logout();
User::requireLogin();
User::requireRole('admin');

// Current user
$user = User::current();
User::isLoggedIn();
User::isAdmin();

// User management
User::create(['username' => '...', 'email' => '...', 'password' => '...']);
User::update($id, ['display_name' => '...']);
User::delete($id);
```

### Helper Functions

```php
// Options
setOption('key', 'value');
$value = getOption('key', 'default');

// Flash messages
setFlash('success', 'Saved!');
$message = getFlash('success');

// Redirects
redirect('/admin/');

// URLs
siteUrl('/page');
adminUrl('/posts');

// Escaping
esc($string);
escAttr($string);

// Formatting
formatDate($timestamp);
formatFileSize($bytes);
```

---

## Troubleshooting

### Common Issues

**"Cannot connect to database"**
- Verify database credentials in `config.php`
- Ensure MySQL service is running
- Check that the database exists

**"Permission denied" on uploads**
```bash
chmod -R 755 uploads/
chown -R www-data:www-data uploads/  # Linux
```

**"Thumbnails not generating"**
- Verify GD library is installed: `php -m | grep gd`
- Check **Media → Thumbnails** for diagnostics

**"CSS not updating"**
- Clear browser cache
- Check file permissions on `assets/css/`

**"Blank page / 500 error"**
- Enable PHP error display temporarily
- Check PHP error logs
- Verify PHP version is 8.0+

### Debug Mode

Add to `config.php`:
```php
define('DEBUG', true);
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

---

## Contributing

We welcome contributions! Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

### Code Style

- PSR-12 coding standard
- Meaningful variable names
- Comment complex logic
- Test all changes

---

## License

Forge CMS is released under the MIT License.

```
MIT License

Copyright (c) 2024 Forge CMS

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

---

## Links

- **Admin Dashboard**: `/admin/`
- **Documentation**: This README
- **Changelog**: See `CHANGELOG.md`

---

**Built with ❤️ for developers who appreciate simplicity.**
