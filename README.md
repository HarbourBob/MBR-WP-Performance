# MBR WP Performance

[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg)](https://wordpress.org)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-GPL%20v2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-1.6.0-orange.svg)](https://github.com/harbourbob/mbr-wp-performance/releases)
[![Made by Robert](https://img.shields.io/badge/Made%20by-Robert-brightgreen.svg)](https://madebyrobert.co.uk)
[![Buy Me A Coffee](https://img.shields.io/badge/Buy%20Me%20A%20Coffee-%E2%98%95-yellow.svg)](https://buymeacoffee.com/robertpalmer/)

A comprehensive WordPress performance optimisation plugin with granular controls across eight dedicated tabs — covering core features, JavaScript, CSS, fonts, lazy loading, preloading, database cleanup, and WebP image conversion. Completely free, no upsells, no premium tiers.

[**Download Latest Release**](https://github.com/harbourbob/mbr-wp-performance/releases) · [**Report a Bug**](https://github.com/harbourbob/mbr-wp-performance/issues) · [**Request a Feature**](https://github.com/harbourbob/mbr-wp-performance/issues)

---

## What It Does

MBR WP Performance gives you fine-grained control over every aspect of your site's speed. Rather than hiding everything behind presets, each optimisation is individually toggled with clear explanations of what it does and why you'd want it.

The dark-mode admin UI is accessed from the WordPress toolbar — no sidebar clutter. Settings are organised across eight tabs, each focused on a specific performance area.

---

## Features

### Core Features
Disable unnecessary WordPress defaults that slow your site down: emojis, embeds, dashicons, jQuery Migrate, XML-RPC, RSS feeds, self-pingbacks, REST API links, oEmbeds, and more. Control the Heartbeat API frequency, limit post revisions, adjust autosave intervals, and remove query strings for better caching. Includes WooCommerce script optimisation for shops that don't need cart/checkout assets on every page.

### JavaScript Optimisation
Defer or async JavaScript loading, move scripts to the footer, and optionally remove jQuery entirely. Minify and combine JS files, delay execution of third-party scripts (analytics, chat widgets) until user interaction, and strip version query strings. Exclusion lists let you protect scripts that need to load normally.

### CSS Optimisation
Inline critical CSS with an auto-generator, load remaining stylesheets asynchronously, and minify/combine CSS files. The built-in CSS scanner identifies unused styles across your homepage. Remove global styles, load block styles conditionally, disable Elementor's Google Fonts output, and strip WooCommerce CSS from non-shop pages.

### Font Management
Self-host Google Fonts with automatic download and local serving — no more render-blocking requests to googleapis.com and gstatic.com. Preload critical fonts, manage manual font files, enable font subsetting for smaller payloads, and control font-display strategies (swap, block, fallback, optional). Optimise or completely disable Font Awesome. Includes a clear font cache button for when you change your font stack.

### Lazy Loading
Native lazy loading for images with configurable viewport thresholds. Lazy load iFrames and embedded videos from YouTube, Vimeo, and other providers. Exclude specific images by CSS selector, class name, ID, data attribute, keyword, or parent container. DOM monitoring catches dynamically added images from carousels, infinite scroll, and AJAX content. Optional fade-in animation and automatic addition of missing image dimensions to prevent layout shift.

### Preloading & Speculative Loading
Preload critical images including LCP and hero images. Cloudflare Early Hints (HTTP 103) support for edge-level preloading. Fetch Priority optimisation with automatic high priority for the first image, custom selectors for additional critical images, and the option to disable WordPress core's fetch priority. Speculative Loading prefetches or prerenders the next page in the background with configurable eagerness levels (conservative, moderate, eager, auto).

### Database Optimisation
Clean up post revisions with configurable retention limits. Auto-delete old drafts, empty trash, and remove spam/unapproved comments on schedule. Scan for and remove orphaned metadata across posts, comments, terms, and taxonomy relationships. Full transient management with stats, expired cleanup, and nuclear option. Optimise database tables, convert MyISAM to InnoDB, and repair corrupted tables. Schedule automatic maintenance runs.

### WebP Image Conversion
Convert JPG, JPEG, and PNG images to WebP format with configurable compression (1–100). Automatic conversion on upload, plus a bulk converter for your existing Media Library. Images are served via HTML `<picture>` tags with automatic browser fallback, with Gutenberg block and Elementor widget integration built in. Optional Apache/LiteSpeed `.htaccess` rewrite rules for transparent server-level delivery. Server diagnostics panel checks GD library, WebP support, and folder permissions before you start. Smart skip when WebP output would be larger than the original. Full conversion history with bulk management, and a "Revert All" button that deletes every plugin-created WebP file while leaving originals untouched.

### Multisite Network Support
Full WordPress Multisite compatibility from v1.5.0 onwards. Network-activate the plugin and manage default settings from the Network Admin. Push defaults to all sites (or selected sites) in one click, import settings from any existing site, and control whether individual site admins can override the network configuration. New sites automatically inherit network defaults on creation.

---

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher
- GD library with WebP support (for image conversion)

---

## Installation

### Upload via WordPress Admin

1. Download the [latest release](https://github.com/harbourbob/mbr-wp-performance/releases)
2. Go to **Plugins → Add New → Upload Plugin**
3. Upload the zip file and click **Install Now**
4. Activate the plugin
5. Click **WP Performance** in the admin toolbar

### Manual Installation

1. Extract the zip file
2. Upload the `mbr-wp-performance` folder to `/wp-content/plugins/`
3. Activate via the Plugins screen
4. Access settings from the admin toolbar

### WP-CLI

```bash
wp plugin install mbr-wp-performance.zip --activate
```

---

## Getting Started

After activation, click **WP Performance** in the WordPress admin toolbar. The settings page opens on the Core Features tab.

**Recommended first steps:**

1. **Database tab** — Run a cleanup to remove accumulated bloat (revisions, spam, orphaned data)
2. **Lazy Loading tab** — Enable native lazy loading for images and iFrames
3. **Core Features tab** — Disable emojis, embeds, and dashicons if you don't need them
4. **WebP tab** — Check the server diagnostics panel, then run the bulk converter

Enable features one at a time and test your site after each change. The plugin automatically disables all optimisations inside page builder editors (Elementor, Beaver Builder, Divi, Oxygen, Bricks, WPBakery) to prevent conflicts.

---

## Page Builder Compatibility

Optimisations are automatically bypassed when editing in:

- Elementor (editor and preview modes)
- Beaver Builder
- Divi Builder
- Oxygen Builder
- Bricks Builder
- WPBakery Page Builder

No configuration needed — detection is automatic.

---

## Upgrading from MBR WebP Converter

If you were using the standalone **MBR WebP Converter** plugin, you can deactivate it after upgrading to v1.6.0. Your conversion history and WebP file registry will be migrated automatically the first time the new version loads. All existing WebP files remain in place and continue to be served.

---

## Frequently Asked Questions

**Will this break my site?**
The plugin is designed to be safe, but always take a backup first, test on staging, and enable features one at a time.

**Can I use this with a caching plugin?**
Yes. This plugin provides complementary optimisations that work alongside any caching solution.

**Does it work with WooCommerce?**
Yes. Dedicated WooCommerce options let you disable cart/checkout scripts and styles on non-shop pages.

**What happens to my images if I deactivate?**
Original images are never modified. The "Revert All" button in the WebP tab deletes all plugin-created WebP files. On deactivation, WebP files tracked in the registry are cleaned up automatically.

**Does WebP conversion affect my originals?**
No. WebP files are created alongside the originals (same folder, same name, `.webp` extension). The originals are never touched.

---

## Changelog

### 1.6.0
- Integrated WebP image conversion (previously the standalone MBR WebP Converter plugin)
- New "WebP" tab with settings, server diagnostics, and bulk converter
- Automatic WebP conversion on image upload
- Configurable compression level (1–100)
- HTML `<picture>` tag delivery with automatic browser fallback
- Apache/LiteSpeed `.htaccess` rewrite rules for transparent WebP serving
- Gutenberg block and Elementor widget integration for `<picture>` tags
- Conversion history with bulk management and "Revert All" functionality
- Automatic migration of data from standalone MBR WebP Converter plugin
- Smart skip when WebP output would be larger than the original
- Redesigned admin UI with pill-style tab navigation
- Dark mode page background via inline style injection

### 1.5.0
- Full WordPress Multisite network support
- Network Admin settings page with one-click push to all sites
- Import settings from any site as network defaults
- Per-site override toggle for super admins
- Automatic setup for newly-created network sites

### 1.4.9
- Comprehensive lazy loading controls
- Preloading and speculative loading options
- Self-host Google Fonts with manual management
- Enhanced Google Fonts blocking
- CSS scanner for unused styles
- Toolbar menu access (moved from sidebar)
- Rebuilt admin JavaScript
- Page builder compatibility (Elementor, Divi, etc.)

### 1.0.0
- Initial release

---

## Contributing

Contributions are welcome. If you find a bug or have a feature request, please [open an issue](https://github.com/harbourbob/mbr-wp-performance/issues).

For code contributions:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/your-feature`)
3. Commit your changes
4. Push to the branch
5. Open a Pull Request

---

## Support

- **Bug reports:** [GitHub Issues](https://github.com/harbourbob/mbr-wp-performance/issues)
- **Website:** [littlewebshack.com](https://littlewebshack.com)
- **Author:** [madebyrobert.co.uk](https://madebyrobert.co.uk)
- **Coffee:** [buymeacoffee.com/robertpalmer](https://buymeacoffee.com/robertpalmer/)

---

## License

This plugin is licensed under the [GPL v2 or later](https://www.gnu.org/licenses/gpl-2.0.html).

100% free. No premium tiers. No upsells. No tracking.
