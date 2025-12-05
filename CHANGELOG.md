# Changelog

All notable changes to Forge CMS are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.8] - 2024-12-05

### Fixed

#### Critical Bug Fixes
- **Frontend pages (500 error)** - Fixed null value handling in formatDate() and theme templates
- **Media uploads** - Fixed database column name mismatch (`filesize` vs `file_size`)
- **Post/Page templates** - Added null-safe access to all template variables
- **Database schema** - Added missing columns: `parent_id`, `menu_order`, `featured_image_id` to posts table

#### Installation Improvements
- Database table structure now matches all required columns
- Added `media_folders` table to installation
- Added sample page creation during installation
- Improved config.php detection for re-installation

### Enhanced

#### Custom Color Picker (Admin Theme)
- Added color preset palette with 6 quick-select colors per field
- Improved color picker UI with larger click targets
- Visual feedback on hover for color presets
- Better text input styling with monospace font

#### Thumbnail Page
- Redesigned action buttons with modern styling
- Better visual hierarchy with primary/danger button variants
- Improved hover states and animations

#### Plugin Code Snippet
- Fixed code formatting with proper `<pre>` tag
- Added syntax highlighting for PHP variables
- Improved monospace font rendering with JetBrains Mono

### Technical Notes
- formatDate() and formatDatetime() now handle null values gracefully
- Theme templates wrap database queries in try-catch blocks
- Added migrations for posts table columns (parent_id, menu_order, featured_image_id)

---

## [1.0.7] - 2024-12-05

### Added

#### Theme-Aware UI Components
- Admin theme colors now affect buttons, icons, and UI elements globally
- All primary buttons use CSS variables for colors (btn-primary, btn-regen, btn-new-type, btn-save, btn-install)
- Dynamic shadow colors based on theme primary color
- Form inputs focus states use theme colors
- Checkboxes use theme accent color
- Icon option selectors and card hovers use theme colors

### Fixed
- **Post Types buttons** now properly respond to theme color changes
- **Thumbnails page buttons** styling improved and uses theme colors
- **Update page** buttons and UI elements now use theme colors
- Icon stroke width now applies to all SVG icons throughout admin
- Button styling consistency across all admin pages
- Added --forge-secondary CSS variable to root stylesheet
- All hardcoded color values replaced with CSS variables in:
  - post-types.php (btn-save, btn-new-type, pt-btn-view, icon-option, etc.)
  - update.php (btn-install, drop-zone, progress-spinner, etc.)
  - admin.css (logo-icon, nav-badge, btn-primary)

### Technical Notes
- Helper functions added to header.php: adjustBrightness(), hexToRgba()
- CSS variables with fallbacks ensure backward compatibility
- Theme colors cascade to all UI elements through header.php inline styles

### Thumbnail Regeneration
- Note: If thumbnail regeneration fails, check:
  - GD library is installed and enabled (required for image processing)
  - PHP memory_limit is at least 128M
  - Upload directory is writable
  - Image file exists and is accessible

---

## [1.0.6] - 2024-12-04

### Added

#### Admin Theme System
- **Color Schemes**: 6 professionally designed color palettes
  - Indigo (default) — Purple/indigo gradient
  - Ocean — Blue and cyan tones
  - Emerald — Green and teal palette
  - Rose — Pink and red accents
  - Amber — Orange and yellow warmth
  - Slate — Neutral gray tones
- **Typography Selection**: 6 Google Fonts options
  - Inter, Poppins, Nunito, Roboto, Source Sans, DM Sans
- **Icon Styles**: 3 stroke weight options
  - Outlined (2px), Light (1.5px), Bold (2.5px)
- **Preferences Panel**: Toggle animations and compact sidebar mode
- New admin page: `/admin/admin-theme.php`

#### Navigation Improvements
- Added "Admin Theme" link under Settings submenu
- Sidebar automatically uses selected color scheme
- Dynamic CSS variables applied via header

### Changed

#### Submenu Redesign
- Removed bullet points from all submenus
- New left-border indicator for active items
- Smooth indent animation on hover
- Better spacing and typography
- Cleaner, more modern appearance

#### Thumbnails Page Redesign
- Complete UI overhaul with card-based grid layout
- Modern statistics section with gradient icons
- Improved system diagnostics display
- Image cards with thumbnail status badges
- Responsive design with better mobile support
- Cleaner action buttons

#### Settings Page
- Fixed tab switching JavaScript (event parameter issue)
- Tabs now correctly switch between General, Reading, and System Info

#### Landing Page
- Complete redesign with modern aesthetics
- Updated hero section with gradient text
- New feature cards highlighting v1.0.8 features
- Cleaner, more focused layout
- Better responsive behavior

#### Header System
- Dynamic theme loading in `/admin/includes/header.php`
- CSS variables for primary/secondary colors
- Dynamic Google Fonts loading based on theme selection
- Icon stroke width customization

### Fixed

- Settings tabs not switching (JavaScript `event` parameter)
- Admin theme page CSS selector issues
- Font preview not showing correct fonts
- Toggle switches not properly styled
- Color scheme checkmark visibility

### Technical

- Added theme settings storage in options table
- CSS variables: `--forge-primary`, `--forge-secondary`, `--sidebar-gradient`
- Fallback values for all theme variables
- Theme settings validation and defaults

---

## [1.0.5] - 2024-12-04

### Added

#### Modern Login Page
- Redesigned with dark gradient background
- Animated floating shapes for visual interest
- Forge logo with gradient styling
- Smooth input focus animations
- "Remember Me" checkbox styling
- Error message styling with red accent
- Fully responsive design

#### Custom Post Types Builder
- Visual builder interface at `/admin/post-types.php`
- Create unlimited custom content types
- Configuration options:
  - Singular and plural labels
  - URL slug (lowercase, underscores)
  - Icon selection (12 icons)
  - Public/private visibility
  - Archive support toggle
  - Feature toggles: title, editor, excerpt, thumbnail, author, comments
- Post type cards with statistics
- Edit, view, and delete actions
- Protection against deleting types with existing posts
- Built-in types (Posts, Pages) shown as read-only

#### Custom Fields System (ACF-like)
- 14 field types:
  - Text, Textarea, Number
  - Email, URL
  - Date, DateTime
  - Select, Radio, Checkbox
  - Image, File upload
  - WYSIWYG rich text editor
  - Color picker
- Field configuration:
  - Label and key
  - Type selection
  - Options (for select/radio/checkbox)
  - Required validation
- Visual field editor with add/remove
- Fields stored as JSON in options table

#### Thumbnail Management System
- New page: `/admin/thumbnails.php`
- Visual grid of all images
- Thumbnail status indicators per size
- Regenerate thumbnails individually or in bulk
- System diagnostics:
  - GD library status
  - Supported image formats
  - Uploads directory writability
  - Configured thumbnail sizes

#### Database-Based Installation Verification
- Installation check via actual database state
- Verifies users table exists with admin user
- More reliable than file-based `.installed` marker
- Prevents reinstallation on existing installations

#### Forge Toolkit Plugin v2.0.0
- Upgraded from v1.0.0
- 30+ UI components via shortcodes
- Categories: Alerts, Buttons, Cards, Layout, Media, Data Display

### Changed

#### Menu System
- New hierarchical menu system with submenus
- `registerAdminMenu()` function for top-level items
- `registerAdminSubmenu()` function for child items
- Position-based ordering
- Capability-based access control
- Badge support for menu items

#### Sidebar
- Collapsible submenu groups
- Chevron rotation animation
- Auto-expand when child page is active
- Organized into Content, Media, Design, Admin sections

#### Post Class
- `Post::loadCustomPostTypes()` — Loads custom types from database
- `Post::getCustomFields($type)` — Returns fields for post type
- Custom types automatically appear in sidebar

### Fixed

- Thumbnails page initialization error (require_once path)
- Media submenu structure
- Sidebar active state detection

---

## [1.0.4] - 2024-12-03

### Changed

#### Development Environment
- Removed Docker dependency entirely
- Focused on XAMPP development setup
- Simplified local development workflow

#### Package Generation
- Clean ZIP generation process
- Excludes development files
- Excludes uploads and backups
- Ready for deployment

### Removed

- Docker configuration files
- Docker-related documentation
- Container setup scripts

---

## [1.0.3] - 2024-12-02

### Added

#### Live CSS Editor
- Real-time preview using iframe
- Separate admin and frontend CSS editors
- Syntax-highlighted textarea
- JavaScript-based style injection
- Save without page reload
- CSS stored in options table

#### Auto-Update System
- ZIP file upload for updates
- Automatic timestamped backups
- File preservation for:
  - `includes/config.php`
  - `uploads/` directory
  - `backups/` directory
  - `plugins/` directory
- Database migration execution
- Detailed operation logging
- Rollback capability

#### Enhanced Media Library
- Improved upload interface
- Better thumbnail handling
- File type icons
- Size information display

### Changed

- Settings page organization
- Admin CSS improvements
- Dashboard layout refinements

---

## [1.0.2] - 2024-12-01

### Added

#### Editable Slugs
- Slug field independent from title
- Auto-generation from title (optional)
- Manual override capability
- Validation for URL-safe characters
- Duplicate slug prevention

#### Excerpt Support
- Manual excerpt field
- Auto-generation from content
- Configurable excerpt length
- Strip HTML tags option

### Changed

- Post editor layout
- Form validation messages
- Database schema for slugs

### Fixed

- Post save redirect issues
- Media picker z-index
- Mobile responsive issues

---

## [1.0.1] - 2024-11-30

### Added

#### User Profile Page
- Edit own profile information
- Change password functionality
- Display name customization
- Avatar via Gravatar

#### Plugin System Foundation
- Plugin activation/deactivation
- Hook system (add_action, do_action)
- Filter system (add_filter, apply_filters)
- Shortcode registration

### Changed

- Dashboard statistics queries
- User dropdown menu
- Admin header layout

### Fixed

- Session handling issues
- Login redirect loops
- Password reset flow

---

## [1.0.0] - 2024-11-28

### Added

#### Core CMS
- Post and Page management
- WYSIWYG editor integration
- Media upload and management
- User authentication
- Role-based access control

#### Admin Interface
- Modern dark sidebar design
- Responsive dashboard
- Flash message system
- Breadcrumb navigation

#### Database Layer
- PDO-based database wrapper
- Migration system
- Options table for settings

#### Security
- CSRF protection
- bcrypt password hashing
- Prepared statements
- Input sanitization

#### Theming
- Template hierarchy
- Theme folder structure
- Header/footer includes

---

## Version Numbering

Forge CMS uses Semantic Versioning:

- **MAJOR** (1.x.x): Incompatible API changes
- **MINOR** (x.1.x): New functionality, backwards compatible
- **PATCH** (x.x.1): Bug fixes, backwards compatible

---

## Upgrade Notes

### Upgrading to 1.0.7

1. Backup your installation
2. Upload new files (preserving config.php and uploads/)
3. Access admin to trigger any migrations
4. Clear browser cache
5. Configure admin theme at Settings → Admin Theme

### Upgrading to 1.0.5

1. Backup your installation
2. Upload new files
3. Access admin to trigger migrations
4. Custom post types are immediately available
5. Existing posts/pages unaffected

### From 1.0.3 to 1.0.4

1. No database changes required
2. Remove any Docker files if present
3. Standard file upload

---

## Roadmap

### Planned for 1.0.7

- Custom field rendering in post editor
- Custom field value storage as post meta
- Frontend template tags for custom fields
- Field validation on post save

### Planned for 1.1.0

- Field groups/sections
- Conditional field logic
- Repeater field type
- Flexible content fields
- Import/export post types

### Future Considerations

- REST API
- Multisite support
- Advanced caching
- Media optimization
- Two-factor authentication

---

## Contributors

Thank you to everyone who has contributed to Forge CMS!

---

## Links

- [README](README.md) — Full documentation
- [License](LICENSE) — MIT License

---

*Last updated: December 4, 2024*
