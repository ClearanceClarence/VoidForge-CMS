# Changelog

All notable changes to VoidForge CMS will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
| 0.1.2 | 2025-12-09 | Theme system, Media/Thumbnails modal redesign, Plugin docs |
| 0.1.1 | 2025-12-08 | VoidForge rebrand, Custom fields, 80+ icons |
| 0.1.0 | 2025-12-08 | Initial release |

---

**VoidForge CMS** — Modern Content Management
