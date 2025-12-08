# Changelog

All notable changes to VoidForge CMS are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Beta 0.1.0] - 2024-12-06

### 🎉 Initial Beta Release

VoidForge CMS emerges from the void! This is the first public beta release featuring a complete content management system built with pure PHP.

### Added

#### Complete CMS Foundation
- **Posts & Pages** — Create and manage content with a rich text editor
- **Custom Post Types** — Define unlimited content types (products, portfolios, etc.)
- **Custom Fields** — 14 field types including text, images, dates, colors, and more
- **Media Library** — Upload, organize, and manage files with folder support
- **User Management** — Multiple roles (Admin, Editor, Author) with capability-based access

#### Security Keys & Salts API
- WordPress-style salt generator for configuration security
- **API Endpoints**:
  - `GET /api/salts` — Returns PHP `define()` constants
  - `GET /api/salts/json` — Returns JSON format
- 12 cryptographically secure keys generated per request
- `{salts}` shortcode with regenerate button

#### VoidForge Toolkit Plugin v3.0.0
- **60+ SVG icons** in comprehensive icon library
- **30+ shortcodes** for rich content creation:
  - Buttons, alerts, cards, blockquotes, code blocks
  - Tabs, accordions, grids, columns, timelines
  - Progress bars, stats, pricing tables, testimonials
  - Modals, tooltips, video embeds
- Comprehensive demo page at `/toolkit-demo`

#### Modern Admin Interface
- Dark sidebar with customizable color schemes
- 6 theme presets (Indigo, Ocean, Emerald, Rose, Amber, Slate)
- 6 typography options with Google Fonts
- Live CSS editor with real-time preview
- Responsive dashboard with quick stats

#### Developer Features
- Clean PHP architecture — no framework dependencies
- Hook system (actions and filters)
- Shortcode/content tag system
- Theme template hierarchy
- Auto-update system with backup preservation
- Database migrations

### Technical Details

- **PHP Version**: 7.4+
- **Database**: MySQL 5.7+ / MariaDB 10.3+
- **Architecture**: Pure PHP, PDO for database
- **Security**: CSRF protection, prepared statements, bcrypt hashing

---

## Development History

This beta release consolidates development work from pre-release versions. See the git history for detailed development notes.

---

## Version Numbering

VoidForge CMS uses Semantic Versioning with a beta prefix during initial development:

- **Beta 0.x.x**: Pre-release versions, API may change
- **1.0.0**: First stable release (planned)
- After 1.0.0: Standard semver (MAJOR.MINOR.PATCH)

---

## Roadmap

### Planned for Beta 0.2.0

- REST API endpoints
- Field groups/sections
- Conditional field logic
- Repeater field type

### Planned for 1.0.0 (Stable)

- Comprehensive testing
- Performance optimization
- Full documentation
- Import/export functionality

### Future Considerations

- Multisite support
- Advanced caching
- Media optimization
- Two-factor authentication
- Headless CMS mode

---

## Contributors

Thank you to everyone who has contributed to VoidForge CMS!

---

## Links

- [README](README.md) — Full documentation
- [License](LICENSE) — MIT License

---

*Last updated: December 6, 2024*
