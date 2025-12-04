# Changelog

All notable changes to Forge CMS are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
- New feature cards highlighting v1.0.6 features
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

### Upgrading to 1.0.6

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
