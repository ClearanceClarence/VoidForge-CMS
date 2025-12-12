# Changelog

All notable changes to VoidForge CMS will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [0.1.6] - 2025-12-12

### 📊 Admin Columns

Fully customizable column management for post listings, similar to Admin Columns plugin.

#### Features
- **Choose Columns** — Select which columns appear in each post type's listing
- **Column Types** — Built-in columns (ID, Title, Author, Status, Date, Slug, Featured Image, Word Count)
- **Taxonomy Columns** — Display categories, tags, and custom taxonomies as columns
- **Custom Field Columns** — Show any custom field as a column with smart formatting
- **Custom Labels** — Override default column labels with your own text
- **Adjustable Widths** — Set pixel, percentage, or auto width for each column
- **Drag-to-Resize** — Resize columns directly on the posts page by dragging column borders
- **Drag & Drop Ordering** — Reorder columns by dragging in the settings
- **Per Post Type** — Different configurations for posts, pages, and custom post types
- **Live Preview** — See how columns will look before saving
- **Enable/Disable** — Temporarily hide columns without removing them
- **Persistent Widths** — Column widths adjusted on the posts page are saved automatically

#### Column Rendering
- **Images** — Thumbnail preview for image fields and featured images  
- **Colors** — Color swatch for color picker fields
- **Checkboxes** — Checkmark or dash display
- **URLs** — Clickable truncated links
- **Long Text** — Truncated with ellipsis
- **Dates** — Formatted date display
- **Taxonomies** — Comma-separated term names

#### Technical Implementation
- Uses `<colgroup>` for independent column width control
- Table layout fixed ensures columns don't shift during resize
- Width changes applied via `<col>` elements, not `<th>` styles
- Smooth resize without layout recalculation jumps

#### Usage
1. Go to any post list (Posts, Pages, or custom post types)
2. Click the "Columns" button in the header
3. Add columns from the available list (includes all custom fields!)
4. Set custom labels, drag to reorder, set widths, enable/disable
5. Save and return to the post list
6. On the posts page, drag column borders to resize on-the-fly

### 🎨 Column Settings UI Redesign

Complete visual overhaul of the column management interface.

#### Panel Styling
- **Section Headers** — Colored accent bars (purple for Active, green for Available, orange for Preview)
- **Card Shadows** — Subtle box shadows for depth and separation
- **Gradient Backgrounds** — Subtle gradient overlays on headers
- **Sticky Sidebar** — Available columns panel stays visible while scrolling

#### Interactive Elements
- **Column Count Badge** — Shows number of enabled columns, updates in real-time
- **Column Item Hover** — Lift animation with colored border highlight
- **Type Badges** — Enhanced with borders and gradients for each type
- **Add Button** — Green gradient with hover lift effect
- **Remove Button** — Scales up on hover with red highlight

#### Preview Table
- **Thicker Header Border** — 2px bottom border for visual weight
- **Row Hover States** — Subtle purple tint on hover
- **Status Badges** — Gradient backgrounds with border accents
- **Info Tip** — Styled footer with info icon

#### Action Buttons
- **Larger Save Button** — More padding with shadow
- **Hover Effects** — Lift animation with enhanced shadow
- **Section Divider** — Border above action buttons

### 🗑️ Enhanced Trash System

Soft delete with 30-day retention and automatic cleanup.

#### Features
- **30-Day Retention** — Trashed items are kept for 30 days before automatic permanent deletion
- **Days Remaining Display** — Trash view shows how many days until each item is deleted (with red warning when ≤7 days)
- **Empty Trash Button** — One-click button to permanently delete all items in trash
- **Automatic Cleanup** — Old trashed items are cleaned up automatically on page load
- **Trashed Timestamp** — Records when items were moved to trash for accurate retention tracking

#### Usage
- Trash items as normal (they're now soft-deleted with a timestamp)
- View trash to see items and their days remaining
- Click "Empty Trash" to permanently delete all trashed items at once
- Items older than 30 days are automatically removed

### ⏰ Scheduled Publishing

Schedule posts to publish automatically at a future date and time.

#### Features
- **Schedule Toggle** — Checkbox in publish panel to enable scheduling
- **Date & Time Pickers** — Native date and time inputs for precise scheduling
- **Auto-Publish** — Scheduled posts automatically publish when their time arrives
- **Scheduled Status** — New purple "Scheduled" status badge with clock icon
- **Scheduled Filter** — Filter posts list to show only scheduled items
- **Flexible Options** — "Publish Now" button to immediately publish scheduled posts

#### Usage
1. In the post editor, check "Schedule for later" in the Publish panel
2. Set the date and time for publication
3. Click "Schedule" button
4. Post will automatically publish at the scheduled time

#### Technical Details
- Auto-publish runs on every page load (pseudo-cron)
- Scheduled posts show their publish date in the posts list
- Can reschedule or publish immediately at any time

### 🐛 Bug Fixes

- **Column Settings Fatal Error** — Fixed `Post::find()` being called with array instead of `Post::query()`
- **Column Resize Jumping** — Fixed columns shifting/jumping when clicking resize handle
- **Independent Column Widths** — Columns now resize independently without affecting others

---

## [0.1.5] - 2025-12-11

### 📋 Duplicate Post Feature

One-click post duplication with full content preservation.

#### Features
- **Clone Any Content** — Duplicate posts, pages, and custom post type entries
- **Complete Copy** — Copies title (with "Copy" suffix), content, excerpt, featured image
- **Custom Fields** — All meta data / custom field values are duplicated
- **Taxonomy Terms** — Categories, tags, and custom taxonomy assignments are preserved
- **Draft Status** — Duplicates are always created as drafts for review before publishing
- **Instant Edit** — Redirects to the new post editor immediately after duplication

#### Usage
- Click the copy icon button in the post list actions
- The duplicate opens in the editor ready to customize

### 🏷️ Taxonomies System

A complete taxonomy management system for organizing content with categories, tags, and custom taxonomies.

#### Core Features
- **Built-in Taxonomies** — Categories (hierarchical) and Tags (flat) for posts out of the box
- **Custom Taxonomies** — Create unlimited taxonomies for any post type
- **Hierarchical Support** — Parent/child relationships for category-like taxonomies
- **Flat Taxonomies** — Tag-like flat structure for simple grouping
- **Multi Post Type** — Assign taxonomies to multiple post types

#### Admin Interface
- **Taxonomies Page** — Manage all built-in and custom taxonomies
- **Terms Management** — Add, edit, delete terms with AJAX for smooth UX
- **Post Editor Integration** — Taxonomy selectors in post sidebar
- **Hierarchical Checkboxes** — Nested checkbox tree for categories
- **Tag-Style Pills** — Compact pill UI for flat taxonomies

#### Taxonomy Class API
- `Taxonomy::register()` — Register custom taxonomies
- `Taxonomy::getForPostType()` — Get taxonomies for a post type
- `Taxonomy::createTerm()` / `updateTerm()` / `deleteTerm()` — Term CRUD
- `Taxonomy::getTerms()` — Get all terms for a taxonomy
- `Taxonomy::getTermsTree()` — Get hierarchical term tree
- `Taxonomy::setPostTerms()` — Set terms for a post
- `Taxonomy::getPostTerms()` — Get terms assigned to a post
- `Taxonomy::getTermPosts()` — Get posts with a specific term

### 🧭 Menu Builder Improvements

#### Bug Fixes
- **AJAX Save/Delete** — Fixed Database method signatures causing save and delete errors
- **Post::permalink()** — Fixed undefined method error in `Menu::getItemUrl()`
- **Duplicate Prevention** — Menu items can no longer be added twice (except custom links)
- **Delete Modal** — Replaced JavaScript `confirm()` with styled modal dialog

#### Frontend Integration
- **Theme Support** — Both default and flavor themes now use the Menu system
- **Location Assignment** — Menus must be assigned to "Primary Navigation" location to display
- **Fallback** — Themes fall back to showing pages if no menu is assigned

#### UI Enhancements
- **Save Feedback** — Button shows "Saving..." with spinner, then success toast
- **Improved Toasts** — Larger, more visible notifications with gradient backgrounds
- **CPT Individual Posts** — Custom post types now show individual posts instead of archives

### 🎨 Admin Navigation Redesign

Compact, user-friendly sidebar navigation:

- **Smaller Sidebar** — Width reduced from 260px to 220px
- **Inline Icons** — Removed bulky icon boxes, icons now inline at 18-20px
- **Reduced Spacing** — More compact padding and gaps throughout
- **Cleaner Active State** — Simple colored icon instead of gradient box
- **Thinner Scrollbar** — 4px width with hover effects
- **Updated Spacing Settings** — Compact/Medium/Comfortable options refined

### 📁 New Files

```
includes/
└── taxonomy.php          # Taxonomy management class

admin/
├── taxonomies.php        # Taxonomies list page
├── taxonomy-edit.php     # Create/edit taxonomy
└── terms.php             # Terms management page
```

### 📁 Modified Files

```
includes/
├── config.php            — Version updated to 0.1.5
├── migrations.php        — Added taxonomy tables
├── menu.php              — Fixed Database method calls, permalink method
└── install.php           — Added taxonomy tables

admin/
├── menus.php             — Fixed AJAX, improved UI, delete modal
├── post-edit.php         — Added taxonomy selectors in sidebar
├── assets/css/admin.css  — Compact navigation styles
└── includes/sidebar.php  — Added Taxonomies link, smaller logo

index.php                 — Added Menu class for frontend

themes/
├── default/header.php    — Uses Menu system with fallback
└── flavor/functions.php  — flavor_nav_menu() uses Menu system
```

### 🎯 Theme Usage Example

```php
// Get categories for a post
$categories = Taxonomy::getPostTerms($post['id'], 'category');

// Display as links
foreach ($categories as $cat) {
    echo '<a href="' . Taxonomy::getTermUrl($cat) . '">' . esc($cat['name']) . '</a>';
}

// Display menu in theme (assign to "Primary Navigation" location in admin)
$menu = Menu::getMenuByLocation('primary');
if ($menu) {
    $items = Menu::getItems($menu['id']);
    foreach ($items as $item) {
        echo '<a href="' . Menu::getItemUrl($item) . '">' . esc($item['title']) . '</a>';
    }
}
```

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
| 0.1.6 | 2025-12-12 | Admin columns manager, column settings UI redesign, enhanced trash (30-day retention), scheduled publishing, column resize fix |
| 0.1.5 | 2025-12-11 | Duplicate post, taxonomies system, menu builder fixes, compact admin navigation |
| 0.1.4 | 2025-12-09 | Menu builder system, themes page redesign |
| 0.1.3 | 2025-12-09 | Post revisions system, publish button fix, field key prefix |
| 0.1.2 | 2025-12-09 | Theme system, Media/Thumbnails modal redesign, Plugin docs |
| 0.1.1 | 2025-12-08 | VoidForge rebrand, Custom fields, 80+ icons |
| 0.1.0 | 2025-12-08 | Initial release |

---

**VoidForge CMS** — Modern Content Management
