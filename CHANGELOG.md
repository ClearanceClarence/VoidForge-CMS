# Changelog

All notable changes to VoidForge CMS will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [0.1.4] - 2025-12-09

### 🧭 Menu Builder System

A complete drag-and-drop navigation menu management system.

#### Core Features
- **Visual Menu Builder** — Drag-and-drop interface for creating and organizing navigation menus
- **Nested Menu Items** — Full support for multi-level dropdown menus with unlimited depth
- **Multiple Menu Locations** — Assign menus to different theme locations (Primary, Footer, etc.)
- **Custom Links** — Add external URLs with custom link text
- **Content Integration** — Easily add Pages, Posts, and Custom Post Type archives to menus

#### Menu Item Options
- **Navigation Label** — Customize the display text for each menu item
- **Open in New Tab** — Option to open links in new tab/window
- **CSS Classes** — Add custom CSS classes for styling individual items
- **Real-time Saving** — Changes are saved automatically when reordering

#### Admin Interface
- **Two-Panel Layout** — Add items panel on left, menu structure on right
- **Collapsible Sections** — Pages, Posts, Post Type Archives, and Custom Links in expandable panels
- **Expandable Item Settings** — Click to expand each item and edit its properties
- **Live Reordering** — Drag items to reorder or nest them under other items
- **Toast Notifications** — Visual feedback for save, delete, and error actions

#### Theme Integration
- **Menu Class** — New `Menu` class for registering locations and displaying menus
- **Template Function** — Use `Menu::display('location')` in themes to output menus
- **Customizable Output** — Options for container, classes, depth limit, and more
- **Theme Locations** — Themes can register custom menu locations

#### Database
- **New Tables** — `menus` and `menu_items` tables for storing menu data
- **Efficient Structure** — Parent-child relationships with position ordering
- **Cascade Delete** — Deleting a menu removes all its items

### 🎨 UI Improvements
- **Themes Page Redesign** — Completely new layout with hero section and active theme showcase
- **Gradient Hero** — Purple gradient header with stats and action buttons
- **Active Theme Card** — Large preview with features list and quick actions
- **Hover Overlays** — Quick action buttons appear on theme card hover
- **Animated Cards** — Smooth lift and shadow effects on interaction

### 📁 New Files

```
includes/
└── menu.php              # Menu management class

admin/
└── menus.php             # Menu builder admin page
```

### 📁 Modified Files

```
includes/
├── config.php            — Version updated to 0.1.4
├── migrations.php        — Added menus and menu_items tables
└── functions.php         — Added 'menu' icon

admin/
├── themes.php            — Complete redesign with new layout
├── update.php            — Added menu tables to migrations
└── includes/sidebar.php  — Added Menus link in Design section

install.php               — Added menus and menu_items table creation
```

### 🎯 Theme Usage Example

```php
// In theme's functions.php - register a menu location
Menu::registerLocation('main-menu', 'Main Navigation');

// In theme template - display the menu
echo Menu::display('main-menu', [
    'container' => 'nav',
    'container_class' => 'main-navigation',
    'menu_class' => 'nav-menu',
    'submenu_class' => 'dropdown-menu',
]);
```

---

## [0.1.3] - 2025-12-09

### 📜 Post Revisions System

#### Core Features
- **Automatic Revisions** — Revisions are created automatically when updating any post, page, or custom post type
- **Configurable Limits** — Set maximum revisions per post type (0-100, or 0 to disable)
- **Revision Restore** — One-click restore to any previous revision with automatic backup of current state
- **Meta Data Preservation** — Custom field values are stored and restored with revisions

#### Compare Revisions Page
- **New Page: `compare-revisions.php`** — Dedicated page for comparing any two revisions
- **Inline Diff View** — Word-by-word diff highlighting additions (green) and deletions (red)
- **Side-by-Side View** — Toggle between inline and side-by-side comparison for content
- **Revision Selector** — Dropdown menus to select any two revisions or compare with current version
- **Visual Legend** — Clear indicators showing what additions and deletions look like
- **Restore Actions** — Restore either revision directly from the compare page

#### Settings Integration
- **Built-in Post Types** — Configure revision limits for Posts and Pages in Settings → Reading
- **Custom Post Types** — Each custom post type has its own max revisions setting in the post type editor
- **Default Limit** — 10 revisions per post type by default

#### Post Editor UI
- **Revisions Sidebar Card** — Shows revision count and list in the post editor
- **Revision List** — Displays up to 20 most recent revisions with timestamps and authors
- **Latest Indicator** — Visual indicator for the most recent revision
- **Restore Confirmation** — Confirmation dialog before restoring to prevent accidental changes
- **Compare Link** — "Compare Revisions" button when 2+ revisions exist

#### Database
- **New Table** — `post_revisions` table stores all revision data
- **Automatic Cleanup** — Old revisions beyond the limit are automatically deleted
- **Cascade Delete** — Revisions are deleted when a post is permanently deleted
- **Graceful Fallback** — System works even if revisions table doesn't exist yet

### 🐛 Bug Fixes
- **Publish Button** — Fixed issue where clicking "Publish" on new posts kept them in draft status
- **Status Buttons** — Replaced confusing status dropdown with clear "Save Draft" and "Publish" buttons
- **Field Key Prefix** — Custom field keys now auto-prefix with post type slug (e.g., `product_price`)
- **Missing Table Handling** — Post editor gracefully handles missing revisions table with helpful message

### 📁 New Files

```
admin/
└── compare-revisions.php    # Revision comparison page with diff view
```

### 📁 Modified Files

```
includes/
├── config.php          — Version updated to 0.1.3
├── migrations.php      — Added post_revisions table creation
└── post.php            — Added revision methods (createRevision, getRevisions, restoreRevision, etc.)

admin/
├── post-edit.php       — Added revision creation, revisions sidebar, restore functionality, compare link
├── post-type-edit.php  — Added max_revisions field, fixed field key auto-prefix
├── settings.php        — Added revision settings for Posts and Pages
├── update.php          — Added post_revisions table to migrations

install.php             — Added post_revisions table creation
```

---

## [0.1.2] - 2025-12-09

### 🎨 Theme System

#### Theme Settings
- **Per-Theme Customization** — Each theme can now have its own settings
- New admin page: `theme-settings.php` for managing theme options
- Settings categories: Colors, Hero Section, Features, Stats, CTA, Custom CSS
- Live preview with iframe-based real-time updates
- Settings persist per-theme in database

#### Multiple Themes
- **Default Theme** — Dark gradient design with animated background grid, glowing orbs, bento grid layout
- **Flavor Theme** — Light, minimal design with clean typography and soft shadows
- Each theme has unique landing page (`welcome.php`)
- Theme switching preserves individual theme settings

#### Theme Features
- Hero section with customizable title, subtitle, and buttons
- Feature cards (up to 6) with icons, titles, and descriptions
- Stats bar with customizable metrics
- CTA section with gradient background
- Custom CSS injection without file editing

### 🖼️ Media Library Redesign

#### Full-Screen Modal
- **New Modal Interface** — Replaced inline editing with full-screen modal
- Two-column layout: large preview area + editing sidebar
- Dark preview background for better image visibility
- Keyboard navigation: Arrow keys to browse, Escape to close

#### Navigation
- Previous/Next buttons with hover effects
- Counter badge showing current position (e.g., "3 / 24")
- Disabled states at boundaries
- Smooth transitions between images

#### Sidebar Design (Light Theme)
- Clean white background with subtle gray cards
- Section cards with icons: Information, Edit Details, File URL
- Purple gradient accent bar in header
- Larger, more accessible form inputs (0.875rem padding)
- 440px width for comfortable editing

#### Grid/List Views
- Toggle between visual grid and detailed list view
- Persistent view preference
- Responsive grid layout

### 📸 Thumbnails Page Redesign

#### Full-Screen Modal
- **Converted from Slide Panel** — Now uses full-screen modal like Media Library
- Two-column layout matching Media Library design
- Dark preview area with navigation controls
- Keyboard navigation support

#### Sidebar Design (Light Theme)
- 460px width for thumbnail size list
- Meta card showing filename and dimensions
- Scrollable thumbnail sizes section (max-height: 450px)
- Each size shows: name, dimensions, status badge, URL with copy button

#### Thumbnail Size Items
- White cards with subtle borders
- Status badges: green "OK" or red "MISSING"
- Gradient copy buttons with success state
- Hover effects on cards

### 🔌 Plugin System Enhancements

#### Comprehensive Documentation
- **72KB HTML Documentation** — Complete plugin development guide
- Located at `/docs/plugin-development.html`
- Covers: Hooks, Shortcodes, Settings API, AJAX, REST API, Widgets, Cron, Database
- Code examples for every feature
- Styled with VoidForge branding

#### Plugin Features
- Shortcode system with nested support
- Settings API with persistent storage
- AJAX handler registration
- Asset enqueueing (scripts/styles)
- Admin notices system
- Widget registration
- REST API extensions
- Scheduled tasks (cron)
- Database table helpers

#### Included Plugins
- **Starter Shortcodes** — 15+ ready-to-use shortcodes
- **Social Share** — Social sharing with settings page

### 🐛 Bug Fixes

#### Critical Fixes
- **Modal Function Conflict** — Fixed global `openModal()` collision between admin.js and media.php
- Renamed to `openMediaModal()` and `openThumbModal()` for unique namespacing
- Fixed click events not firing on media gallery items

#### JavaScript Improvements
- ES5 syntax for maximum browser compatibility
- Traditional for loops with closures for event handlers
- All functions defined before use
- Removed debug console.log statements

### 🎯 UI/UX Improvements

#### Accessibility
- Light theme sidebars (not dark) for better readability
- Larger touch targets (40px+ buttons)
- Higher contrast text (#1e293b on white)
- Larger font sizes throughout (0.875rem - 0.9375rem base)

#### Responsive Design
- Breakpoint at 1024px for modal layouts
- Stacked layout on smaller screens
- Sidebar becomes scrollable bottom panel on mobile
- Navigation buttons resize appropriately

#### Copy URL Feature
- Gradient purple copy buttons
- Success state with green color and checkmark
- Auto-reset after 1.5 seconds

### 📁 New Files

```
docs/
└── plugin-development.html    # 72KB plugin dev documentation

themes/
├── default/
│   └── welcome.php           # Dark gradient landing page
└── flavor/
    ├── index.php             # Theme entry point
    ├── header.php
    ├── footer.php
    ├── home.php
    ├── single.php
    ├── page.php
    ├── archive.php
    ├── welcome.php           # Light minimal landing page
    └── 404.php

admin/
├── themes.php                # Theme management
└── theme-settings.php        # Per-theme customization
```

### 📝 Modified Files

- `includes/config.php` — Version updated to 0.1.2
- `includes/functions.php` — Added `getThemeSettings()`, `saveThemeSettings()`
- `admin/media.php` — Complete modal redesign, light sidebar
- `admin/thumbnails.php` — Converted to modal, light sidebar
- `admin/includes/sidebar.php` — Added Themes menu section
- `README.md` — Updated for 0.1.2 features

---

## [0.1.1] - 2025-12-08

### 🎨 Major Rebrand
- **Renamed from Forge CMS to VoidForge CMS** — Complete rebrand with new identity
- New logo design featuring the distinctive "V" icon
- Updated color scheme with indigo/purple gradient (#6366f1 → #8b5cf6)
- New favicon (SVG format)
- Updated all references throughout the codebase

### ✨ New Features

#### Custom Fields System
- **New Custom Field Groups** — Create reusable field groups
- Assign field groups to any post type or users
- 14 field types supported
- Field groups automatically appear in post editor when assigned
- New admin pages: `custom-fields.php` and `custom-field-edit.php`

#### Admin Theme Enhancements
- **Granular Font Size Controls** — Separate settings for sidebar, header, and content
- Font sizes: Small (12px), Medium (14px), Large (16px)
- **Custom Color Scheme Management** — Save up to 5 custom color schemes
- Delete confirmation modal for color schemes

#### Icon Library Expansion
- **80+ Admin Icons** — Expanded from 16 to 80+ icons
- Icons organized by category

#### Landing Page
- **Stunning New Welcome Page** — Complete overhaul
- Dark theme with animated background grid
- Glowing orb effects and gradient text
- Feature showcase with 6 feature cards
- Stats bar and bento grid layout

### 🔧 Improvements

#### UI/UX Consistency
- **Unified Structure Pages** — Post Types and Custom Fields share CSS classes
- Removed inline `<style>` blocks from structure pages
- Consistent button styling across all pages

#### Delete Confirmations
- All delete actions now use proper modal dialogs
- No more JavaScript `confirm()` alerts

### 🐛 Bug Fixes
- **Plugin Class Error** — Added missing `require_once` for Plugin class
- **Update System Network Errors** — Fixed curl configuration
- **Date Format Display** — Fixed corrupted date format
- **Homepage Setting** — Fresh installs no longer set a homepage by default
- **Custom Fields Integration** — Fixed `get_post_type_fields()` to include field groups

---

## [0.1.0] - 2025-12-08

### Initial Release
- Core CMS functionality
- Custom post types with custom fields
- Media library with folder organization
- User management with roles (Admin, Editor, Subscriber)
- Plugin system with WordPress-style hooks/filters
- Theme system with PHP templates
- Admin dashboard with stats
- WYSIWYG content editor
- Auto-update system with backups
- Security features (CSRF, XSS protection, secure sessions)
- Live CSS customizer for admin and frontend
- Thumbnail generation system
- Search functionality
- Homepage selection

---

## Version History

| Version | Date | Highlights |
|---------|------|------------|
| 0.1.4 | 2025-12-09 | Menu builder system, themes page redesign |
| 0.1.3 | 2025-12-09 | Post revisions system, publish button fix, field key prefix |
| 0.1.2 | 2025-12-09 | Theme system, Media/Thumbnails modal redesign, Plugin docs |
| 0.1.1 | 2025-12-08 | VoidForge rebrand, Custom fields, 80+ icons |
| 0.1.0 | 2025-12-08 | Initial release |

---

**VoidForge CMS** — Modern Content Management
