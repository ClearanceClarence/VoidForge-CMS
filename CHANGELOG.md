# Changelog

All notable changes to VoidForge CMS will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [0.1.1-beta] - 2025-12-08

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
- 14 field types supported: text, textarea, number, email, url, date, datetime, color, select, checkbox, image, file, wysiwyg
- Field groups now automatically appear in post editor when assigned
- New admin pages: `custom-fields.php` and `custom-field-edit.php`

#### Admin Theme Enhancements
- **Granular Font Size Controls** — Separate settings for sidebar, header, and content
- Font sizes: Small (12px), Medium (14px), Large (16px)
- **Custom Color Scheme Management** — Save up to 5 custom color schemes
- Delete confirmation modal for color schemes (replaces JS alert)
- Color scheme cards show preview dots for primary, secondary, and sidebar colors

#### Icon Library Expansion
- **80+ Admin Icons** — Expanded from 16 to 80+ icons
- Icons organized by category:
  - Content: document, article, book, bookmark, archive, folder, copy
  - Media: image, video, music, mic, camera
  - Commerce: shopping-bag, package, truck, briefcase, gift, tag
  - People & Social: users, user, heart, thumbs-up, share, mail, phone
  - Interface: star, flag, award, target, compass, map-pin
  - Objects: box, layers, grid, calendar, clock, tool, key, shield, lock
  - Tech: code, terminal, database, server, cpu, globe, link, zap
  - Misc: coffee, home, settings, eye, edit, printer, save, and more

#### Post Date Tracking
- Enhanced date display in post editor sidebar
- Shows: Published date, Updated date (when different), Created date
- All timestamps with time display (M j, Y g:i a format)

#### Landing Page
- **Stunning New Welcome Page** — Complete overhaul
- Dark theme with animated background grid
- Glowing orb effects and gradient text
- Feature showcase with 6 feature cards
- Stats bar (0 Dependencies, 14+ Field Types, 80+ Icons, ∞ Possibilities)
- Bento grid layout for highlighting capabilities
- Technology stack section
- Call-to-action sections with animated background
- Fully responsive design

### 🔧 Improvements

#### UI/UX Consistency
- **Unified Structure Pages** — Post Types and Custom Fields now share CSS classes
- New shared classes in `admin.css`:
  - `.structure-page` — Page container
  - `.structure-header` — Page header with title and action
  - `.btn-primary-action` — Primary gradient button
  - `.info-box` — Blue info/help box
  - `.items-grid` / `.item-card` — Card-based layouts
  - `.item-actions` / `.item-btn` — Action buttons
  - `.modal-overlay` / `.modal-box` — Confirmation modals
  - `.data-table` — Table styling
  - `.empty-state` — Empty state displays
- Removed inline `<style>` blocks from structure pages
- Consistent button styling across all pages

#### Delete Confirmations
- All delete actions now use proper modal dialogs
- No more JavaScript `confirm()` alerts
- Cancel and Delete buttons with proper styling
- Click outside or press Escape to close

#### Button Styling
- Improved `.btn-primary-action` with box-shadow and hover effects
- Fixed border-radius consistency (12px)
- Better SVG icon sizing (18x18)

#### Footer Positioning
- Fixed admin footer not staying at bottom on short pages
- Updated `.admin-content` to use `flex: 1 0 auto`

### 🐛 Bug Fixes

#### Critical Fixes
- **Plugin Class Error** — Added missing `require_once` for Plugin class in 15 admin files
- **Update System Network Errors** — Fixed curl configuration for proper error handling
- **Package Structure** — Fixed missing directories in update packages

#### Database & Data
- **Date Format Display** — Fixed corrupted date format
- `formatDate()` now uses site's configured date format by default
- Added format sanitization to prevent invalid characters
- Falls back to 'M j, Y' if format is invalid

#### Installation
- **Homepage Setting** — Fresh installs no longer set a homepage by default
- Demo landing page (welcome.php) shows on new installations
- Homepage dropdown now clearly shows "— None (show demo page) —" option
- Creates "About" sample page instead of "Home" page

#### Custom Fields Integration
- **Field Groups Not Showing** — Fixed `get_post_type_fields()` to include fields from Custom Field Groups
- Fields from assigned groups now appear in post editor
- Fields include source tracking (post_type vs field_group)

### 📝 Documentation
- **Comprehensive README.md** — Complete rewrite
- **CHANGELOG.md** — This file, documenting all changes

### 🗂️ File Changes

#### New Files
- `admin/custom-fields.php` — Field group listing
- `admin/custom-field-edit.php` — Field group editor
- `CHANGELOG.md` — This changelog

#### Modified Files
- `admin/assets/css/admin.css` — Added 200+ lines of shared structure page styles
- `admin/includes/header.php` — Granular font size CSS variables
- `admin/includes/sidebar.php` — Added Custom Fields nav link
- `admin/admin-theme.php` — Font size controls, delete modal
- `admin/post-types.php` — Rewritten with shared classes
- `admin/settings.php` — Updated homepage dropdown text
- `includes/functions.php` — Updated `get_post_type_fields()`, `formatDate()`
- `install.php` — Removed auto homepage setting, renamed sample page
- `themes/default/welcome.php` — Complete redesign
- `README.md` — Complete rewrite
- Multiple admin files — Added Plugin class include

---

## [0.1.0-beta] - 2025-12-08

### Initial Beta Release
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

| Version | Date | Codename |
|---------|------|----------|
| 0.1.1-beta | 2025-12-08 | VoidForge |
| 0.1.0-beta | 2025-12-08 | Genesis |

---

**VoidForge CMS** — Modern Content Management
