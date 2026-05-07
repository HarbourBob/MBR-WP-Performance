# ⚡ MBR WP Performance

[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg)](https://wordpress.org)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-GPL%20v2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-1.10.0-orange.svg)](https://github.com/harbourbob/mbr-wp-performance/releases)
[![Made by Robert](https://img.shields.io/badge/Made%20by-Robert-brightgreen.svg)](https://madebyrobert.co.uk)
[![Buy Me A Coffee](https://img.shields.io/badge/Buy%20Me%20A%20Coffee-%E2%98%95-yellow.svg)](https://buymeacoffee.com/robertpalmer/)

> **A comprehensive WordPress performance plugin with granular controls across ten dedicated tabs.** Every optimisation is individually toggleable, every option is explained in plain language, and the whole thing is free forever — no premium tiers, no upsells, no tracking.

[**📥 Download Latest**](https://github.com/harbourbob/mbr-wp-performance/releases) · [**🐛 Report a Bug**](https://github.com/harbourbob/mbr-wp-performance/issues) · [**💡 Request a Feature**](https://github.com/harbourbob/mbr-wp-performance/issues) · [**📖 User Guide (PDF)**](https://littlewebshack.com/)

---

## ✨ New in v1.10.0 — Orphaned Images

Reclaim disk space by safely removing image attachments that are no longer referenced anywhere on your site. Detection covers post parents, featured images, post content, and postmeta — with a configurable restore window for everything that gets deleted.

- 🔍 **Smart scanner** — finds unused images across the whole Media Library, batched to handle thousands of attachments without timing out
- 🎯 **Two-tier confidence** — high-confidence orphans are eligible for bulk-delete; review-tier candidates require manual inspection
- ⏰ **Configurable restore window** — staged deletions remain restorable for 7, 14, 30, 60 days, or forever
- 🧹 **Complete file cleanup** — removes the original, every WordPress sub-size variant, and matching `.webp` siblings — no orphan files left on disk
- 🛡️ **Pre-deletion re-verification** — re-runs the orphan check at delete time so a stale scan can't take out an image you just used

> ⚠️ This is the most destructive tool in the plugin. Always test on a staging copy and back up `/wp-content/uploads/` before running deletions on a live site.

---

## 💚 Why MBR WP Performance?

- 🆓 **Free forever** — no premium tiers, no upsells, no feature gates, no "pro" version
- 🔒 **Zero tracking** — never phones home, never sends analytics, never touches visitor data
- 🎛️ **Granular control** — toggle individual optimisations with clear explanations, not opaque "speed up my site" buttons
- 🌙 **Dark-mode admin** — lives in the WordPress toolbar, no extra sidebar clutter
- 🏗️ **Page-builder aware** — automatically disables itself inside Elementor, Bricks, Divi, Beaver Builder, Oxygen, and WPBakery editors
- 🌐 **Multisite-ready** — full network admin with per-site override control
- 🤝 **Plays nicely with caching** — designed to complement WP Rocket, LiteSpeed, W3 Total Cache, and friends, not replace them

---

## 🎛️ Features at a Glance

| Tab | Focus |
|-----|-------|
| ⚙️ **Core Features** | WordPress-level toggles: emojis, embeds, REST API, Heartbeat, query strings |
| 📜 **JavaScript** | Defer, async, jQuery removal, minify/combine, delay until interaction |
| 🎨 **CSS** | Critical CSS generator, async loading, minify/combine, unused-style scanner |
| 🔤 **Fonts** | Self-hosted Google Fonts, preloading, font-display, Font Awesome optimisation |
| 🚀 **Preloading** | LCP image preload, fetch priority, Cloudflare Early Hints, speculative loading |
| 🐢 **Lazy Loading** | Native image/iFrame lazy loading with fine-grained exclusions |
| 🗄️ **Database** | Revisions, transients, orphaned metadata, table optimisation |
| 🖼️ **WebP** | Conversion on upload, bulk converter, `<picture>` delivery, `.htaccess` rules |
| 📐 **Image Sizing** | Resize large uploads, inject missing dimensions, bulk resize tool |
| 🗑️ **Orphaned Images** | Find and safely remove unreferenced images *(new in v1.10.0)* |
| 🛒 **WooCommerce** | Cart fragments, conditional asset loading, Action Scheduler retention |

---

## 📚 Detailed Features

### ⚙️ Core Features
Disable WordPress defaults that don't earn their place — emojis, embeds, dashicons, jQuery Migrate, XML-RPC, RSS feeds, self-pingbacks, REST API links. Throttle the Heartbeat API, limit revisions, strip query strings. Three REST API access modes for tightening user enumeration without breaking the block editor.

### 📜 JavaScript Optimisation
Defer or async script loading, move scripts to the footer, optionally remove jQuery entirely. Minify, combine, and delay execution of analytics and chat widgets until the user actually interacts with the page. Per-option exclusion lists keep your essential scripts running normally.

### 🎨 CSS Optimisation
One-click critical CSS generator, async loading for the rest, minify and combine. Built-in scanner identifies unused styles. Conditionally load block styles, remove global styles for classic themes, kill duplicate Elementor Google Fonts requests.

### 🔤 Font Management
Self-host Google Fonts to eliminate render-blocking third-party requests (and improve GDPR posture). Preload critical fonts, manage manual entries for fonts the auto-scanner misses, enable subsetting, and pick your `font-display` strategy. Optimise or fully disable Font Awesome.

### 🚀 Preloading & Speculative Loading
Preload your LCP image so it lands fast. Configure fetch priority manually or auto-prioritise the first image in main content. Emit Cloudflare Early Hints (HTTP 103) for edge-level preloading. The Speculation Rules API prefetches or prerenders the next page with conservative, moderate, eager, or auto eagerness.

### 🐢 Lazy Loading
Native browser lazy loading for images and iFrames with configurable thresholds. Background-image lazy loading via IntersectionObserver. Exclude by selector, class, ID, data attribute, filename keyword, or parent container — six different ways to keep your hero image loading early. DOM monitoring catches dynamically inserted images from carousels, infinite scroll, and AJAX content.

### 🗄️ Database Optimisation
Prune the bloat that slows queries: post revisions with retention limits, auto-drafts, trashed posts, spam comments, orphaned metadata across posts/comments/terms/taxonomy relationships, expired and stale transients. One-click `OPTIMIZE TABLE`, MyISAM-to-InnoDB conversion, table repair, and a diagnostic info panel showing where your bytes have gone.

### 🖼️ WebP Image Conversion
Convert JPG, JPEG, and PNG to WebP with configurable compression (1–100). Auto-convert on upload plus a bulk converter for your existing Media Library. Serve via HTML `<picture>` tags or `.htaccess` rewrite rules. Originals are never modified — the WebP sits alongside as a parallel file. Skip-when-larger detection, full conversion history, and a "Revert All" button that cleans up every plugin-created WebP without touching originals.

### 📐 Image Sizing & Dimensions
Two PageSpeed wins in one section: auto-resize oversized uploads (default 2560px, configurable) using the WordPress core scaling pipeline, and inject missing `width`/`height` attributes into front-end `<img>` tags to eliminate Cumulative Layout Shift. The Bulk Resize tool downscales existing Media Library images in place, regenerating sub-sizes and cleaning up stale WebP copies along the way.

### 🗑️ Orphaned Images *(new in v1.10.0)*
Find image attachments no longer referenced anywhere on your site, with a safe two-stage workflow and configurable restore window. Detection covers post parents, featured images, post content (matched by attachment ID, shortcode reference, and filename stem so sized variants are caught), and postmeta. Two confidence tiers — high-confidence orphans are eligible for bulk-delete; review-tier candidates require manual inspection. Deletion handles the original, every sub-size, and matching `.webp` siblings. Pre-deletion re-verification blocks deletes if an attachment has become referenced since the last scan.

### 🛒 WooCommerce Optimisations
Cart fragments control — disable the admin-ajax mini-cart sync request site-wide or only on non-shop pages, often the single biggest TTFB win on cached stores. Strip WooCommerce scripts, styles, and block assets from non-shop pages. Stop the heavy `wc-admin` React bundles loading across unrelated admin screens. Configurable Action Scheduler retention (default 30 days; options for 14, 7, or 3) keeps `actionscheduler_actions` from ballooning. Weekly automated cleanup of expired sessions, transients, and Action Scheduler history. Geolocation advisory notice flags settings that interact badly with full-page caching.

### 🌐 Multisite Network Support
Network-activate and manage defaults from the Network Admin. Push settings to all (or selected) sites in one click, import settings from any existing site as the network default, and control whether site admins can override or are locked to network configuration. New sites automatically inherit network defaults on creation.

---

## 📋 Requirements

- WordPress **5.8** or higher
- PHP **7.4** or higher
- MySQL **5.6** or higher
- GD library with WebP support (for image conversion and resizing)

---

## 📦 Installation

### Via WordPress Admin

1. Download the [latest release](https://github.com/harbourbob/mbr-wp-performance/releases)
2. Go to **Plugins → Add New → Upload Plugin**
3. Upload the zip and click **Install Now**
4. Activate
5. Click **WP Performance** in the admin toolbar

### Manual

1. Extract the zip
2. Upload `mbr-wp-performance/` to `/wp-content/plugins/`
3. Activate from the Plugins screen

### WP-CLI

```bash
wp plugin install mbr-wp-performance.zip --activate
```

---

## 🎯 Getting Started

After activation, open **WP Performance** from the admin toolbar. A safe first-time setup, in order:

1. 🗄️ **Database tab** — clean up accumulated bloat (revisions, expired transients, orphaned metadata)
2. 🐢 **Lazy Loading tab** — enable image and iFrame lazy loading
3. ⚙️ **Core Features tab** — disable emojis, embeds, and dashicons if you don't use them
4. 🖼️ **WebP tab** — check server diagnostics, then run the bulk converter
5. 📐 **Image Sizing & Dimensions** — enable "Resize Large Uploads" and "Add Missing Width & Height" for an easy PageSpeed Insights win
6. 🗑️ **Orphaned Images tab** *(optional)* — back up first, then run a scan to identify reclaimable disk space
7. 🛒 **WooCommerce tab** *(if applicable)* — disable cart fragments, dequeue shop assets on non-shop pages, set Action Scheduler retention to 14 days

> 💡 Enable features one at a time and test after each change. Page builder editors are automatically detected and bypassed.

---

## 🏗️ Page Builder Compatibility

Optimisations are auto-disabled inside:

- Elementor (editor and preview)
- Beaver Builder
- Divi Builder
- Oxygen Builder
- Bricks Builder
- WPBakery Page Builder

No configuration needed.

---

## 🔄 Upgrading from MBR WebP Converter

Were you using the standalone **MBR WebP Converter** plugin? Deactivate it after upgrading to v1.6.0 or later — your conversion history and WebP file registry migrate automatically on first load. All existing WebP files stay in place and continue to be served.

---

## ❓ FAQ

**Will it break my site?**
It's designed to be safe, but always back up first, test on staging, and enable features one at a time. Each option is independently toggleable — anything you enable, you can disable.

**Does it work with my caching plugin?**
Yes. There's deliberately no overlap with full-page caching — this plugin provides complementary optimisations alongside WP Rocket, LiteSpeed, W3 Total Cache, WP Super Cache, and others.

**Does it touch my original images?**
Never. WebP files are parallel files (same name, `.webp` extension). Resize-on-upload uses the WordPress core scaling pipeline. Only the **Bulk Resize tool** and **Orphaned Images deletion** modify or remove files on disk — both are clearly flagged as destructive and require manual confirmation.

**Is it safe to bulk-delete orphaned images?**
Bulk deletion is restricted to **high-confidence** orphans (no references found in any check). Review-tier candidates must be deleted individually after manual inspection. Deletions stage to a restore queue for the configured window, so you can reinstate the database record if you change your mind — though file bytes are removed at deletion time and require a backup to recover.

**What about page builder content?**
Page-builder data stores (Elementor `_elementor_data`, Bricks, etc.) aren't yet directly inspected by the Orphaned Images scanner — the postmeta string-search picks up most references but not all. Treat candidates as Review-equivalent until you've scanned a staging copy if you rely heavily on a builder.

**What's the difference between WebP and Image Sizing?**
WebP creates a smaller-format copy of each image without changing dimensions. Image Sizing changes the actual pixel dimensions so browsers don't download files larger than they need. They're complementary — use both for the biggest PageSpeed wins.

---

## 📝 Changelog

### 1.10.0
- ✨ **New "Orphaned Images" tab** — scan the Media Library for image attachments no longer referenced anywhere, with a safe two-stage deletion workflow and configurable restore window
- Detection covers `post_parent`, `_thumbnail_id` featured images, `post_content` (matching by attachment ID, shortcode reference, and filename stem), and a string-search across postmeta values
- Two confidence tiers: **High** (no references found, eligible for bulk-delete) and **Review** (matched only in postmeta, manual inspection required)
- Custom staging table records the full attachment post row, postmeta, and file manifest before deletion — restore from queue within the configured window
- Configurable restore window: 7, 14, 30, or 60 days, or "keep forever"
- Per-attachment exclusions list with one-click "Keep" action from the candidate list
- Daily WP-Cron event (`mbr_wp_performance_orphan_purge`) cleans up expired staging rows
- File deletion handles the original, all WordPress sub-size variants, the "scaled" full-size copy, and matching `.webp` siblings — no orphan files left on disk
- Pre-deletion re-verification blocks deletion if an attachment has become referenced since the scan
- Live progress bar during scans, batched in 50-attachment chunks to avoid timeouts on large libraries

### 1.9.3
- REST API namespace allowlist on the Core tab — when "Disable REST API" is set to a non-default mode, admins can whitelist specific namespaces that should remain accessible (useful for plugins exposing public REST endpoints like front-end chat widgets, contact forms, or store APIs)
- Fix: public REST endpoints registered with `permission_callback => '__return_true'` are no longer indiscriminately blocked when their namespace is in the allowlist
- Helper text on the Core tab now lists common public namespaces (`mbr-isa/v1`, `contact-form-7/v1`, `wc/store/v1`) for discoverability

### 1.9.2
- Fix: "Remove Global Styles" no longer breaks the front end of Full Site Editing (block) themes — auto-skipped when a block theme is active
- Removed the duplicate, previously non-functional "Remove Global Styles" checkbox from the CSS tab; the Core tab toggle is now the canonical home
- Migration: any existing `[css][remove_global_styles]` truthy value is automatically copied to `[core][remove_global_styles]` on update
- Updated Core tab tooltip to clearly warn about FSE theme incompatibility

### 1.9.1
- Weekly automated cleanup toggle in the WooCommerce tab — runs expired sessions, WooCommerce transients, and Action Scheduler cleanup on the plugin's existing weekly cron hook
- Geolocation and page cache advisory notice — warns when WooCommerce's default customer location interacts badly with full-page caching
- Last-run log display showing when the scheduled cleanup last ran and what it cleared
- Fix: the `mbr_wp_performance_database_cleanup` weekly cron event now has an actual listener
- Defensive re-scheduling of the weekly cron when the user enables automated cleanup

### 1.9.0
- New dedicated WooCommerce tab consolidating all store-specific optimisations
- Cart fragments control — disable the admin-ajax `get_refreshed_fragments` request site-wide or only on non-shop pages
- Expanded conditional asset loading — dequeues WooCommerce scripts, styles, block assets, selectWoo, blockUI, and related libraries on non-shop pages
- Disable the zxcvbn password strength meter on the frontend
- Disable WooCommerce marketplace suggestions and dashboard status widgets
- Prevent the heavy wc-admin React bundles from loading on non-WooCommerce admin screens
- Configurable Action Scheduler retention period (default 30 days, options for 14, 7, or 3)
- One-click cleanup buttons for expired WooCommerce sessions and product, order, and expired transients
- Legacy `core.disable_woocommerce_scripts` and `css.disable_woocommerce_css` options remain fully backward-compatible

### 1.8.0
- Bulk resize tool for existing Media Library images — scan, then downscale in place
- Two-phase workflow (Scan → Start Resize) with progress bar, live log, and running savings total
- Automatic sub-size regeneration after each resize using the WordPress core pipeline
- Elementor CSS cache cleared automatically after a bulk resize
- Stale WebP files deleted automatically before sub-sizes are regenerated
- Skips images already within the configured maximum, writes a clear "skipped" reason to the log
- Paginated scan (batches of 200) to keep memory use reasonable on large libraries

### 1.7.0
- New "Image Sizing & Dimensions" section in the WebP tab
- Automatic resize-on-upload with configurable maximum dimension (default 2560px)
- Automatic injection of missing `width` and `height` attributes on front-end images to reduce Cumulative Layout Shift
- Works on post content, Gutenberg blocks, Elementor widgets, attachment images, and post thumbnails
- Per-URL dimension cache (in-memory + weekly transient) to keep the filter cheap
- Skips external images, SVGs, and data URIs automatically

### 1.6.0
- Integrated WebP image conversion (previously the standalone MBR WebP Converter plugin)
- New "WebP" tab with settings, server diagnostics, and bulk converter
- Automatic WebP conversion on image upload
- Configurable compression level (1–100)
- HTML `<picture>` tag delivery with automatic browser fallback
- Apache/LiteSpeed `.htaccess` rewrite rules for transparent WebP serving
- Conversion history with bulk management and "Revert All" functionality
- Automatic migration of data from standalone MBR WebP Converter plugin
- Smart skip when WebP output would be larger than the original
- Redesigned admin UI with pill-style tab navigation and dark mode

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
- CSS scanner for unused styles
- Toolbar menu access (moved from sidebar)
- Page builder compatibility (Elementor, Divi, etc.)

### 1.0.0
- Initial release

---

## 🤝 Contributing

Contributions welcome. Found a bug or have a feature request? [Open an issue](https://github.com/harbourbob/mbr-wp-performance/issues).

For code contributions:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/your-feature`)
3. Commit your changes
4. Push to the branch
5. Open a Pull Request

---

## 💚 Support

- 🐛 **Bug reports:** [GitHub Issues](https://github.com/harbourbob/mbr-wp-performance/issues)
- 🌐 **Website:** [littlewebshack.com](https://littlewebshack.com)
- 👤 **Author:** [madebyrobert.co.uk](https://madebyrobert.co.uk)
- ☕ **Coffee:** [buymeacoffee.com/robertpalmer](https://buymeacoffee.com/robertpalmer/)

---

## 📄 License

Licensed under the [GPL v2 or later](https://www.gnu.org/licenses/gpl-2.0.html).

**100% free. No premium tiers. No upsells. No tracking.** Forever.
