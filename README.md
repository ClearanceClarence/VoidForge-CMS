<div align="center">

<br><br>

<img src="https://img.shields.io/badge/⬡-000000?style=for-the-badge&logoColor=white" height="80"/>

# VOIDFORGE

<br>

### The CMS That Doesn't Get In Your Way

<br>

[![Version](https://img.shields.io/badge/v0.2.2-6366f1?style=for-the-badge&labelColor=1e1b4b)](https://github.com/ClearanceClarence/VoidForge-CMS/releases)
[![PHP](https://img.shields.io/badge/PHP_8.0+-a855f7?style=for-the-badge&labelColor=1e1b4b&logo=php&logoColor=white)](https://php.net)
[![License](https://img.shields.io/badge/MIT-10b981?style=for-the-badge&labelColor=1e1b4b)](LICENSE)
[![Size](https://img.shields.io/badge/~350KB-f59e0b?style=for-the-badge&labelColor=1e1b4b)](.)

<br>

```
Zero frameworks. Zero bloat. Zero compromises.
```

<br>

[**Get Started →**](#-quick-start) &nbsp;&nbsp;·&nbsp;&nbsp; [Features](#-why-voidforge) &nbsp;&nbsp;·&nbsp;&nbsp; [Documentation](#-documentation) &nbsp;&nbsp;·&nbsp;&nbsp; [API](#-rest-api)

<br>

---

<br>

</div>

## ⚡ Why VoidForge?

<br>

<table>
<tr>
<td align="center" width="33%">
<br>

### 🚀

**Sub-50ms Page Loads**

While others ship megabytes, we ship results. Pure PHP means your server does exactly what it needs to—nothing more.

<br>
</td>
<td align="center" width="33%">
<br>

### 🧠

**WordPress DNA, Modern Soul**

90+ hooks and filters you already know. If you've built for WordPress, you'll feel right at home—but faster.

<br>
</td>
<td align="center" width="33%">
<br>

### 🔓

**Actually Readable Code**

No framework abstractions. No magic. Just clean, documented PHP you can understand, debug, and extend in minutes.

<br>
</td>
</tr>
</table>

<br><br>

---

<br>

<div align="center">

## 🔨 Meet Anvil

### The Block Editor That Respects Your Time

<br>

*15 powerful blocks. Drag-and-drop everything. No page builder bloat.*

</div>

<br>

<table>
<tr>
<td width="50%" valign="top">

### ✍️ Content Blocks

| | |
|:--|:--|
| **Paragraph** | Rich text with drop caps |
| **Heading** | H1-H6 with anchor links |
| **List** | Ordered, unordered, nested |
| **Quote** | Styled blockquotes |
| **Code** | Syntax highlighting |
| **Table** | Full table support |

</td>
<td width="50%" valign="top">

### 📐 Layout Blocks

| | |
|:--|:--|
| **Columns** | 2-6 responsive columns |
| **Spacer** | Precise vertical spacing |
| **Separator** | Styled dividers |
| **Button** | CTA buttons with styles |
| **Image** | Full media library |
| **Gallery** | Lightbox galleries |

</td>
</tr>
</table>

<br>

<div align="center">

**Plus:** Video embeds · YouTube/Vimeo oEmbed · Raw HTML · 50-level undo/redo · Keyboard shortcuts

</div>

<br>

### Anvil Live — Edit On The Frontend

<br>

> 🎯 Click any element. Edit inline. Drag blocks between columns. Preview on any device. Ship it.

<br>

No more admin panel ↔ frontend tab switching. See your changes as your visitors will.

<br>

---

<br>

<div align="center">

## 📝 Content That Scales

### Custom Post Types · Custom Fields · Custom Everything

</div>

<br>

<table>
<tr>
<td width="50%" valign="top">

### 16+ Field Types

```
text        │  textarea    │  wysiwyg
number      │  email       │  url
date        │  color       │  select
checkbox    │  radio       │  image
file        │  gallery     │  repeater
group       │  relationship
```

### Repeater Fields

Build dynamic content: team members, testimonials, FAQs, pricing tables—all from the admin.

</td>
<td width="50%" valign="top">

### Post Management

| Feature | |
|:--|:--|
| **Revisions** | Compare & restore any version |
| **Scheduling** | Publish at any future date |
| **Bulk Actions** | Modify hundreds of posts at once |
| **Quick Edit** | Inline editing without page loads |
| **Custom Columns** | Drag-to-resize, show what matters |
| **30-Day Trash** | Recover deleted content |

</td>
</tr>
</table>

<br>

---

<br>

<div align="center">

## 🎨 Beautiful Admin. Out of the Box.

</div>

<br>

<table>
<tr>
<td align="center" width="25%">

**🌙**

Dark mode interface with purple gradients

</td>
<td align="center" width="25%">

**🎨**

Multiple color schemes

</td>
<td align="center" width="25%">

**🔤**

Customizable fonts & sizes

</td>
<td align="center" width="25%">

**80+**

Built-in icons

</td>
</tr>
</table>

<br>

### 🔐 Login Screen Editor

Design your login page with 80+ settings, 12 presets, and live preview:

<table>
<tr>
<td align="center" width="25%">

**Backgrounds**

Solid · Gradient · Image · Patterns

</td>
<td align="center" width="25%">

**Glassmorphism**

Blur · Transparency · Shadows

</td>
<td align="center" width="25%">

**Typography**

Titles · Labels · Custom fonts

</td>
<td align="center" width="25%">

**Animations**

Fade · Slide · Scale · Bounce

</td>
</tr>
</table>

<br>

---

<br>

<div align="center">

## 🔌 Plugin System

### WordPress-Compatible Architecture

</div>

<br>

```php
<?php
/**
 * Plugin Name: My Plugin
 * Version: 1.0.0
 */

// Actions & Filters — just like WordPress
add_action('init', fn() => /* your code */);
add_filter('the_content', fn($content) => $content . '<p>Modified!</p>');

// Shortcodes
add_shortcode('greeting', fn($atts) => "Hello, {$atts['name']}!");

// Admin pages, settings, AJAX handlers, REST endpoints...
// Everything you'd expect. Nothing you wouldn't.
```

<br>

<div align="center">

**90+ hooks** · **Shortcodes** · **Settings API** · **AJAX handlers** · **Custom REST routes** · **Scheduled tasks**

</div>

<br>

---

<br>

<div align="center">

## 🔗 REST API

### Full CRUD · API Keys · Rate Limiting

</div>

<br>

```bash
# Fetch posts
curl -H "X-API-Key: your_key" https://yoursite.com/api/posts

# Create content
curl -X POST -H "X-API-Key: your_key" \
  -d '{"title":"Hello World","content":"..."}' \
  https://yoursite.com/api/posts

# Everything else: /api/pages, /api/media, /api/users, /api/terms...
```

<br>

<table>
<tr>
<td width="33%" align="center">

**🔑 API Key Management**

Generate keys in admin with granular permissions

</td>
<td width="33%" align="center">

**⚡ Rate Limiting**

Built-in protection against abuse

</td>
<td width="33%" align="center">

**📄 JSON Responses**

Standard REST with pagination

</td>
</tr>
</table>

<br>

---

<br>

<div align="center">

## 💬 Comments System

### Threaded · Moderated · Gravatar-Ready

</div>

<br>

<table>
<tr>
<td align="center" width="25%">

**🧵 Threading**

Up to 10 levels deep

</td>
<td align="center" width="25%">

**👤 Guest Comments**

Or require login

</td>
<td align="center" width="25%">

**🛡️ Moderation**

Approve, spam, trash

</td>
<td align="center" width="25%">

**🖼️ Gravatars**

Automatic avatars

</td>
</tr>
</table>

<br>

---

<br>

## 🚀 Quick Start

<br>

```bash
# 1. Clone or download
git clone https://github.com/ClearanceClarence/VoidForge-CMS.git

# 2. Point your server to the directory

# 3. Visit the site — the installer handles the rest
```

<br>

**Requirements:** PHP 8.0+ · MySQL 5.7+ · That's it.

<br>

The installation wizard will:

- ✓ Create your database tables
- ✓ Set up your admin account
- ✓ Configure your site settings
- ✓ Get you publishing in under 2 minutes

<br>

---

<br>

## ⚙️ Configuration

<br>

```php
// includes/config.php

define('DB_HOST', 'localhost');
define('DB_NAME', 'voidforge');
define('DB_USER', 'your_user');
define('DB_PASS', 'your_password');
define('DB_PREFIX', 'vf_');

define('SITE_URL', 'https://yoursite.com');
```

<br>

---

<br>

## 🔄 Effortless Updates

<br>

<table>
<tr>
<td width="60">

**1**

</td>
<td>

Go to **Admin → Updates**

</td>
</tr>
<tr>
<td>

**2**

</td>
<td>

Upload the new version ZIP

</td>
</tr>
<tr>
<td>

**3**

</td>
<td>

Click **Install Update**

</td>
</tr>
</table>

<br>

VoidForge automatically:

- Creates a timestamped backup
- Preserves your config, uploads, themes, and plugins
- Runs database migrations
- Gets you back to work

<br>

---

<br>

## 📚 Documentation

<br>

| Guide | Description |
|:------|:------------|
| [`/docs/theme-development.html`](docs/theme-development.html) | Complete theme creation guide |
| [`/docs/plugin-development.html`](docs/plugin-development.html) | 72KB comprehensive plugin docs |
| [`/docs/block-development.html`](docs/plugin-development.html) | Custom Anvil block creation |

<br>

---

<br>

## 🛡️ Security

<br>

<table>
<tr>
<td align="center" width="25%">

**🔒**

CSRF Protection

</td>
<td align="center" width="25%">

**🛡️**

XSS Prevention

</td>
<td align="center" width="25%">

**🔐**

bcrypt Passwords

</td>
<td align="center" width="25%">

**🍪**

Secure Sessions

</td>
</tr>
</table>

<br>

---

<br>

## 🤝 Contributing

<br>

```bash
# Fork → Clone → Branch → Code → Push → PR

git clone https://github.com/ClearanceClarence/VoidForge-CMS.git
git checkout -b feature/your-feature
git commit -m "Add your feature"
git push origin feature/your-feature
```

<br>

---

<br>

<div align="center">

## 📄 License

**MIT** — Use it however you want.

<br>

---

<br><br>

<img src="https://img.shields.io/badge/⬡-000000?style=for-the-badge" height="40"/>

<br>

### Built for developers who ship.

<br>

**VoidForge** — Modern content management without the baggage.

<br>

[⬆ Back to top](#voidforge)

<br><br>

</div>
