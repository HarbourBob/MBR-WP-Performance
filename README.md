# ⚡ MBR WP Performance

[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg)](https://wordpress.org)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-GPL%20v2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-1.13.0-orange.svg)](https://github.com/harbourbob/mbr-wp-performance/releases)
[![Downloads](https://img.shields.io/github/downloads/harbourbob/mbr-wp-performance/total)](https://github.com/harbourbob/mbr-wp-performance/releases)
[![Buy Me A Coffee](https://img.shields.io/badge/Buy%20Me%20A%20Coffee-%E2%98%95-yellow.svg)](https://buymeacoffee.com/robertpalmer/)

> **A comprehensive WordPress performance plugin with granular controls across thirteen dedicated tabs.** Every optimisation is individually toggleable, every option is explained in plain language, and the whole thing is free forever — no premium tiers, no upsells, no tracking.

[**📥 Download Latest**](https://github.com/harbourbob/mbr-wp-performance/releases) · [**🐛 Report a Bug**](https://github.com/harbourbob/mbr-wp-performance/issues) · [**💡 Request a Feature**](https://github.com/harbourbob/mbr-wp-performance/issues) · [**📖 User Guide (PDF)**](https://littlewebshack.com/wp-content/uploads/2026/05/mbr-wp-performance-user-guide-1.12.0.pdf)

---

## ✨ New in v1.12.3 — Three new tabs, AVIF, and modules now fully wired up

The largest single release the plugin has had. Three placeholder modules from earlier versions are now fully implemented, three new tabs have been added, AVIF conversion lands alongside WebP, and a handful of new toggles slot into existing tabs.

### 🆕 Three new tabs

- 🖥️ **Server** — Browser-cache headers and Brotli / Gzip compression rules written to `.htaccess` on Apache and LiteSpeed. Directly clears two of the most common PageSpeed Insights warnings: *"Serve static assets with an efficient cache policy"* and *"Enable text compression"*. Nginx hosts get a copy-ready server-config snippet instead
- 🌐 **Third-Party** — Self-hosts Google Analytics (gtag.js), Google Tag Manager (gtm.js), legacy analytics.js, and Facebook Pixel (fbevents.js). Daily refresh cron keeps the cached copies fresh. Removes the *"Reduce the impact of third-party code"* PSI warning and stops first-paint requests to googletagmanager.com and connect.facebook.net
- 🔬 **Diagnostics** — Three tools in one tab: the **Autoloaded Options Audit** (the single most common WordPress DB performance killer — shows top 30 autoloaded options by size with one-click disable, around 85 core options protected from accidental modification), the **WP-Cron Viewer** with orphan-event detection (events left behind by deactivated plugins), and the **Caching Plugin Conflict Detector** (WP Rocket, W3 Total Cache, LiteSpeed Cache, FlyingPress, WP Super Cache, Perfmatters, Autoptimize) showing which MBR options overlap with each

### 🖼️ AVIF + image enhancements

- **AVIF** image conversion alongside WebP. Typically 20-30% smaller than WebP at equivalent perceived quality. The `<picture>` wrapper emits AVIF first, then WebP, then the original — browsers pick the first format they support. Requires PHP 8.1+ with libavif or Imagick 7.0.25+; capability auto-detected and toggle disabled if neither is present
- **`decoding="async"`** on images — lets the browser decode off the main thread, improving INP on image-heavy pages. The LCP candidate (any image carrying `fetchpriority="high"`) is auto-skipped so it still decodes synchronously
- **Strip EXIF metadata on upload** — removes EXIF, IPTC and XMP from new JPEG uploads (camera serial, GPS coordinates, embedded thumbnails). ICC colour profiles preserved. Privacy win plus typically 5-30% smaller files. Imagick `stripImage()` preferred, GD fallback at quality 92

### 🔧 Modules now fully wired up

The JavaScript, CSS, and Database optimisation modules were partial in earlier releases — admin tabs displayed toggles but several toggles had no backend logic. In v1.12.0 every toggle on those three tabs does real work:

- 📜 **JavaScript** — Defer, Defer jQuery, Move-to-Footer, Remove jQuery (with test mode for logged-out-only scoping), inline-JS minification, Delay JS with an interaction-triggered runtime and configurable timeout, Disable Concatenation, Remove Script Versions
- 🎨 **CSS** — Inline Critical CSS, Async CSS (preload + onload with the standard loadCSS polyfill), inline-CSS minification, Conditional Block Styles, Remove CSS Versions, Disable Elementor Google Fonts, Disable WooCommerce CSS on non-shop pages
- 🗄️ **Database** — Scheduled cleanup cron actually runs: auto-draft purge, trash emptying, spam/unapproved comment cleanup, expired transient deletion (multisite-aware), revision trimming. Schedule selector (daily / weekly / manual) re-schedules the cron automatically. **Last Auto-Cleanup log** plus a **Run Auto-Cleanup Now** button

### ➕ New settings on existing tabs

- ⚙️ **Core tab:** Minify HTML — output-buffered, preserves `<pre>`, `<textarea>`, `<script>`, `<style>` and IE conditional comments
- 🚀 **Preloading tab:** Hover Prefetch — instant.page v5.2.0 runtime; honours `Save-Data: on`
- 🐢 **Lazy Loading tab:** YouTube / Vimeo Facade — replaces embedded video iframes with a static thumbnail + play button. Real iframe only loads on click. Saves ~1.4MB of YouTube JS on initial page load. Keyboard accessible. No third-party cookies until interaction

> ℹ️ **Combine JS, Combine CSS, and Remove Unused CSS** remain visible in the UI for forward compatibility but are no-ops in v1.12.0 — they surface an admin notice when enabled. A safe implementation of each requires a separate engineering project. Use Defer, Delay, Inline Critical CSS, Async CSS, or [MBR Advanced Asset Manager](https://github.com/harbourbob/mbr-advanced-asset-manager) for per-asset control in the meantime.

> 🧹 **Clean uninstall:** All v1.12.0 `.htaccess` marker blocks (*MBR AVIF*, *MBR Browser Cache*, *MBR Compression*) are removed cleanly on deactivation. The third-party script refresh cron is unscheduled. The AVIF file registry is purged.

---

## 💚 Why MBR WP Performance?

- 🆓 **Free forever** — no premium tiers, no upsells, no feature gates, no "pro" version
- 🔒 **Zero tracking** — never phones home, never sends analytics, never touches visitor data
- 🎛️ **Granular control** — toggle individual optimisations with clear explanations, not opaque "speed up my site" buttons
- 🌙 **Dark-mode admin** — lives in the WordPress toolbar, no extra sidebar clutter
- 🏗️ **Page-builder aware** — automatically disables itself inside Elementor, Bricks, Divi, Beaver Builder, Oxygen, and WPBakery editors
- 🌐 **Multisite-ready** — full network admin with per-site override control
- 🤝 **Plays nicely with caching** — designed to complement WP Rocket, LiteSpeed, W3 Total Cache, and friends, not replace them — and now actively detects them via the Diagnostics tab

---

## 🎛️ Features at a Glance

| Tab | Focus |
|-----|-------|
| ⚙️ **Core Features** | WordPress-level toggles: emojis, embeds, REST API, Heartbeat, query strings, HTML minify |
| 📜 **JavaScript** | Defer, defer jQuery, jQuery removal, minify, delay until interaction *(now fully wired in v1.12.0)* |
| 🎨 **CSS** | Critical CSS generator, async loading, minify, unused-style scanner *(now fully wired in v1.12.0)* |
| 🔤 **Fonts** | Self-hosted Google Fonts, preloading, font-display, Font Awesome optimisation |
| 🚀 **Preloading** | LCP image preload, fetch priority, Cloudflare Early Hints, speculative loading, hover prefetch *(new)* |
| 🐢 **Lazy Loading** | Native image/iFrame lazy loading, YouTube / Vimeo facade *(new)*, fine-grained exclusions |
| 🗄️ **Database** | Revisions, transients, orphaned metadata, table optimisation, scheduled cleanup *(now functional)* |
| 🖼️ **WebP / AVIF** | WebP **and AVIF** *(new)* conversion, bulk converter, `<picture>` delivery, `.htaccess` rules |
| 📐 **Image Sizing** | Resize large uploads, inject missing dimensions, `decoding="async"` *(new)*, EXIF strip *(new)*, bulk resize tool |
| 🖥️ **Server** *(new in v1.12.0)* | Browser cache headers, Brotli / Gzip compression, Nginx snippet for non-Apache hosts |
| 🌐 **Third-Party** *(new in v1.12.0)* | Self-host gtag.js, gtm.js, analytics.js, fbevents.js — daily refresh cron, URL rewriting |
| 🔬 **Diagnostics** *(new in v1.12.0)* | Autoload audit, WP-Cron viewer, caching plugin conflict detector |
| 🗑️ **Orphaned Media** | Find and safely remove unreferenced images, videos, audio, documents, archives |
| 🛒 **WooCommerce** | Cart fragments, conditional asset loading, Action Scheduler retention |

---

## 📚 Detailed Features

### ⚙️ Core Features
Disable WordPress defaults that don't earn their place — emojis, embeds, dashicons, jQuery Migrate, XML-RPC, RSS feeds, self-pingbacks, REST API links. Throttle the Heartbeat API, limit revisions, strip query strings. Three REST API access modes for tightening user enumeration without breaking the block editor, with a namespace allowlist for plugins that legitimately need public REST access. **New in v1.12.0:** Minify HTML — output-buffered, comments and whitespace stripped, `<pre>` / `<textarea>` / `<script>` / `<style>` and IE conditional comments preserved.

### 📜 JavaScript Optimisation
**Now fully implemented in v1.12.0** — previous releases had a placeholder class with admin UI but partial backend logic. Defer or async script loading, defer jQuery specifically, move scripts to the footer, optionally remove jQuery entirely (with a test mode that scopes the removal to logged-out visitors only). Minify inline JS and delay execution of analytics and chat widgets until the user actually interacts with the page — configurable timeout ensures delayed scripts run eventually even if the user never interacts. Per-option exclusion lists keep your essential scripts running normally. Disable WordPress's admin-script concatenation, strip `?ver=` query strings from script URLs.

### 🎨 CSS Optimisation
**Now fully implemented in v1.12.0** — previous releases had a placeholder class with admin UI but no backend logic. One-click critical CSS generator, async loading (preload + onload pattern with the standard loadCSS polyfill for older browsers), minify inline CSS, built-in scanner for unused styles. Conditionally load block styles, remove global styles for classic themes, kill duplicate Elementor Google Fonts requests, dequeue WooCommerce stylesheets on non-shop pages, strip CSS version query strings.

### 🔤 Font Management
Self-host Google Fonts to eliminate render-blocking third-party requests (and improve GDPR posture). Preload critical fonts with an explicit `crossorigin="anonymous"` attribute *(fixed in v1.12.0)*, manage manual entries for fonts the auto-scanner misses, enable subsetting, and pick your `font-display` strategy. Optimise or fully disable Font Awesome.

### 🚀 Preloading & Speculative Loading
Preload your LCP image so it lands fast. Configure fetch priority manually or auto-prioritise the first image in main content. Emit Cloudflare Early Hints (HTTP 103) for edge-level preloading. The Speculation Rules API prefetches or prerenders the next page with conservative, moderate, eager, or auto eagerness. **New in v1.12.0:** Hover Prefetch using the canonical instant.page v5.2.0 runtime (MIT) — on link hover or first touchstart, the destination page is prefetched. Honours the `Save-Data: on` request header so users on metered connections aren't penalised.

### 🐢 Lazy Loading
Native browser lazy loading for images and iFrames with configurable thresholds. Background-image lazy loading via IntersectionObserver. Exclude by selector, class, ID, data attribute, filename keyword, or parent container — six different ways to keep your hero image loading early. DOM monitoring catches dynamically inserted images from carousels, infinite scroll, and AJAX content. **New in v1.12.0:** YouTube / Vimeo Facade — embedded video iframes are replaced with a static thumbnail and play button; the real iframe only loads on click. Saves roughly 1.4MB of YouTube JavaScript on initial page load, prevents YouTube cookies until interaction, keyboard accessible. Vimeo thumbnails are lazy-fetched via the public API behind an IntersectionObserver so the network request only happens when the facade scrolls into view.

### 🗄️ Database Optimisation
**Scheduled cleanup now functional in v1.12.0** — the weekly cron event was registered in earlier releases but had no listener. v1.12.0 wires up auto-draft purge with configurable age, trash emptying with configurable retention, spam and unapproved comment cleanup with their own age thresholds, expired transient cleanup (multisite-aware), and revision trimming to the keep-N setting. Schedule selector (daily / weekly / manual only) re-schedules the cron automatically when changed. **Last Auto-Cleanup log** displays the time of the last run with per-action item counts. **Run Auto-Cleanup Now** button triggers the cleanup logic on demand for clearing a backlog. Plus all the existing tools: post revisions retention, one-click orphaned metadata scanners across posts/comments/terms/taxonomy relationships, transient stats and cleanup, `OPTIMIZE TABLE`, MyISAM-to-InnoDB conversion, table repair, diagnostic info panel.

### 🖼️ WebP / AVIF Image Conversion
Convert JPG, JPEG, and PNG to WebP with configurable compression (1–100). Auto-convert on upload plus a bulk converter for your existing Media Library. Serve via HTML `<picture>` tags or `.htaccess` rewrite rules. Originals are never modified — the WebP sits alongside as a parallel file. Skip-when-larger detection, full conversion history, and a "Revert All" button that cleans up every plugin-created WebP without touching originals. **New in v1.12.0:** AVIF conversion alongside WebP. The `<picture>` wrapper emits AVIF first, then WebP, then the original — browsers pick the first format they support. Configurable AVIF quality (default 60, perceptually equivalent to WebP at 75). Server capability diagnostics show whether GD AVIF (PHP 8.1+ with libavif) or Imagick AVIF (7.0.25+ with libheif) is available; toggle is disabled in the UI if neither is present.

### 📐 Image Sizing & Dimensions
Two PageSpeed wins from earlier releases: auto-resize oversized uploads (default 2560px, configurable) using the WordPress core scaling pipeline, and inject missing `width`/`height` attributes into front-end `<img>` tags to eliminate Cumulative Layout Shift. The Bulk Resize tool downscales existing Media Library images in place, regenerating sub-sizes and cleaning up stale WebP / AVIF copies along the way. **New in v1.12.0:** Add `decoding="async"` to images — lets the browser decode off the main thread, improving INP on image-heavy pages. The LCP candidate (any image with `fetchpriority="high"`) is automatically skipped. **Also new:** Strip EXIF metadata on JPEG upload — removes camera serial, GPS coordinates, embedded thumbnails. ICC colour profiles preserved so colours stay accurate. Imagick `stripImage()` preferred, GD fallback at quality 92. Only affects new uploads; existing images are unchanged.

### 🖥️ Server *(new in v1.12.0)*
Writes server-level configuration to `.htaccess` on Apache and LiteSpeed for browser caching and text compression — two of the most common PageSpeed Insights warnings that can't be fixed from inside PHP. **Browser cache headers** with conservative expiry windows (1 year for images / fonts / video, 1 month for CSS / JS, 0 for HTML, 1 hour for feeds), `Cache-Control: public, max-age=31536000, immutable` as belt-and-braces. **Brotli / Gzip compression** with `mod_brotli` preferred where loaded, `mod_deflate` fallback. On Nginx and IIS hosts (where `.htaccess` is ignored), the tab detects the server and shows an equivalent server-config snippet to paste into your configuration. Marker blocks (*MBR Browser Cache*, *MBR Compression*) are removed cleanly when you disable the toggles or deactivate the plugin.

### 🌐 Third-Party *(new in v1.12.0)*
Self-hosts common tracking scripts and rewrites outbound `<script src=>` URLs in the page output to point at the local copies. Removes the PSI *"Reduce the impact of third-party code"* warning and stops first-paint network requests to googletagmanager.com and connect.facebook.net. Supported scripts: **Google Analytics (gtag.js)**, **Google Tag Manager (gtm.js)** (query strings like `?id=GTM-XXXXX` preserved intact so your container IDs keep working), **Google Analytics (analytics.js)** for legacy Universal Analytics, **Facebook Pixel (fbevents.js)**. Each enabled script is downloaded once on enable then refreshed daily via the `mbr_wp_performance_third_party_refresh` WP-Cron event. Cache Status panel shows last-refresh time, per-script success/failure, and a manual Refresh Now button. Pairs particularly well with Delay JavaScript Until Interaction on the JS tab — the script is served from your own domain *and* only executes when the user interacts.

### 🔬 Diagnostics *(new in v1.12.0)*
Three diagnostic tools in one tab, each targeting a common WordPress performance footgun.

- **Caching Plugin Conflicts** — detects WP Rocket, W3 Total Cache, LiteSpeed Cache, FlyingPress, WP Super Cache, Perfmatters, and Autoptimize, and lists exactly which MBR options overlap with each. Prevents the common pitfall of having Defer / Minify / cache headers enabled in two plugins at once
- **Autoloaded Options Audit** — shows total bytes currently being autoloaded plus the top 30 options by size. One-click "Disable autoload" button on each row. Around 85 protected core options (`siteurl`, `home`, `active_plugins`, `template`, `stylesheet`, etc.) cannot be modified — the toggle is replaced with an em-dash. Transients (autoloaded transients are almost always a bug) are flagged. Consistently the single most common WordPress database performance killer; this is the first tool inside the plugin that addresses it
- **WP-Cron Viewer** — lists every scheduled event with its hook, next-run time, recurrence, and a Callback? column showing whether any PHP callback is currently registered for the hook. Events with no callback are flagged "orphan" (left behind by deactivated plugins) and can be unscheduled with one click. Includes setup instructions for replacing WP-Cron with a real system cron job

### 🗑️ Orphaned Media
Find attachments no longer referenced anywhere on your site — images, videos, audio, documents, and archives. Tick the media types you want to scan, hit Run Scan, review the candidates, delete the ones you don't need. Detection covers featured images and post content (matched by attachment ID, shortcode reference, and filename stem so sized image variants and URL-only video/audio references are caught), with a string-search across postmeta values for everything else. Post parents are treated as suggestive evidence rather than definitive — attachments uploaded for a post and later removed from the content correctly surface as Review-tier candidates instead of being silently skipped. Two confidence tiers — high-confidence orphans are eligible for bulk-delete; review-tier candidates require manual inspection. For images, deletion handles the original file, every WordPress sub-size variant, and matching `.webp` and `.avif` siblings; for other media types it removes the single attached file. Pre-deletion re-verification blocks deletes if an attachment has become referenced since the last scan. Defaults to images-only on upgrade — opt in to other types via the settings checkboxes.

### 🛒 WooCommerce Optimisations
Cart fragments control — disable the admin-ajax mini-cart sync request site-wide or only on non-shop pages, often the single biggest TTFB win on cached stores. Strip WooCommerce scripts, styles, and block assets from non-shop pages. Stop the heavy `wc-admin` React bundles loading across unrelated admin screens. Configurable Action Scheduler retention (default 30 days; options for 14, 7, or 3) keeps `actionscheduler_actions` from ballooning. Weekly automated cleanup of expired sessions, transients, and Action Scheduler history. Geolocation advisory notice flags settings that interact badly with full-page caching.

### 🌐 Multisite Network Support
Network-activate and manage defaults from the Network Admin. Push settings to all (or selected) sites in one click, import settings from any existing site as the network default, and control whether site admins can override or are locked to network configuration. New sites automatically inherit network defaults on creation.

---

## 📋 Requirements

- WordPress **5.8** or higher
- PHP **7.4** or higher (**PHP 8.1+** for AVIF encoding)
- MySQL **5.6** or higher
- GD library with WebP support (for image conversion and resizing)
- *(Optional)* Imagick 7.0.25+ with libheif, or GD with libavif — for AVIF conversion. Capability is auto-detected; the AVIF toggle is disabled in the UI if neither is present

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

1. 🔬 **Diagnostics tab** — open this first. Check the Caching Plugin Conflicts panel; if any caching plugin is detected, decide who owns each optimisation before enabling anything below. Have a quick look at the Autoloaded Options Audit and WP-Cron Viewer for any red flags
2. 🗄️ **Database tab** — clean up accumulated bloat (revisions, expired transients, orphaned metadata). Click **Run Auto-Cleanup Now** once to clear any backlog
3. 🖥️ **Server tab** — enable Browser Cache Headers and Brotli / Gzip Compression (Apache / LiteSpeed write to `.htaccess`; Nginx hosts get a copy-ready snippet)
4. 🐢 **Lazy Loading tab** — enable image and iFrame lazy loading; enable YouTube / Vimeo Facade if you embed videos
5. ⚙️ **Core Features tab** — disable emojis, embeds, and dashicons if you don't use them; enable **Minify HTML**
6. 🖼️ **WebP / AVIF tab** — check server diagnostics, run the bulk converter; if AVIF is supported, enable that too
7. 📐 **Image Sizing & Dimensions** — enable "Resize Large Uploads", "Add Missing Width & Height", `decoding="async"`, and Strip EXIF Metadata
8. 🚀 **Preloading tab** — enable Hover Prefetch; auto-prioritise the first image if your hero is reliably the LCP element
9. 🌐 **Third-Party tab** — enable self-hosting for whichever tracking scripts you use; click **Refresh Now** to populate the cache
10. 🗑️ **Orphaned Media tab** *(optional)* — back up first, then run a scan to identify reclaimable disk space across images, videos, audio, documents, and archives
11. 🛒 **WooCommerce tab** *(if applicable)* — disable cart fragments, dequeue shop assets on non-shop pages, set Action Scheduler retention to 14 days

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

## 🔄 Upgrading from earlier versions

**From v1.11.0 or earlier:** Settings are preserved through the update. v1.12.0 includes a migration block that seeds the new option sections (`preloading`, `lazy_loading`, `third_party`, `server_headers`) automatically. No manual configuration required for the migration itself, but you'll want to visit the new Diagnostics, Server, and Third-Party tabs to opt in to their settings.

**From the standalone MBR WebP Converter plugin:** Deactivate it after upgrading to v1.6.0 or later — your conversion history and WebP file registry migrate automatically on first load. All existing WebP files stay in place and continue to be served.

---

## ❓ FAQ

**Will it break my site?**
It's designed to be safe, but always back up first, test on staging, and enable features one at a time. Each option is independently toggleable — anything you enable, you can disable. The new **Diagnostics tab** detects active caching plugins and shows which MBR options overlap with each, which catches the most common cause of breakages when stacking performance plugins.

**Does it work with my caching plugin?**
Yes — and v1.12.0's Diagnostics tab has a built-in conflict detector specifically for this. There's deliberately no overlap with full-page caching; this plugin provides complementary optimisations alongside WP Rocket, LiteSpeed, W3 Total Cache, FlyingPress, WP Super Cache, Perfmatters, Autoptimize, and others. Open the Diagnostics tab to see exactly which MBR options overlap with any active caching plugin, and pick one or the other.

**What's the difference between WebP and AVIF?**
Both are next-generation image formats designed to replace JPEG and PNG. AVIF (2019) is typically 20-30% smaller than WebP (2010) at equivalent perceived quality, but needs more recent browser support (Chrome 85+, Firefox 93+, Safari 16.4+) and stricter server-side encoding (PHP 8.1+ with libavif, or Imagick 7.0.25+). Enable both — the `<picture>` wrapper sends AVIF to capable browsers, WebP to the next tier, and the original format as fallback.

**Does it touch my original images?**
Never. WebP and AVIF files are parallel files (same name, different extension). Resize-on-upload uses the WordPress core scaling pipeline. EXIF stripping only affects new JPEG uploads — existing files are not touched. Only the **Bulk Resize tool** and **Orphaned Media deletion** modify or remove files on disk — both are clearly flagged as destructive and require manual confirmation.

**Does the Orphaned Media scanner check all media types by default?**
No — it defaults to images-only, matching v1.10.0 behaviour. To include videos, audio, documents, or archives, tick the relevant boxes in the tab settings and click **Save Settings** before running a scan.

**Is it safe to bulk-delete orphaned media?**
Bulk deletion is restricted to **high-confidence** orphans (no references found in any check). Review-tier candidates must be deleted individually after manual inspection. Deletions stage to a restore queue for the configured window, so you can reinstate the database record if you change your mind — though file bytes are removed at deletion time and require a backup to recover.

**Why do attachments with `post_parent` matches show in Review tier instead of being skipped?**
WordPress sets `post_parent` on attachments uploaded via the post editor, but it never clears the link when you remove the attachment from the post's content. An attachment can have a "live parent" but not actually appear anywhere in that parent's content. v1.11.0 onwards treats `post_parent` as suggestive evidence rather than definitive — those candidates surface in Review tier so you can open the parent post, confirm the attachment is genuinely unused, and delete it manually.

**What about page builder content?**
Page-builder data stores (Elementor `_elementor_data`, Bricks `_bricks_page_content_2`, Beaver Builder, Oxygen, WPBakery) aren't yet directly inspected by the Orphaned Media scanner — the postmeta string-search picks up most references but not all. URL-only references inside builder layouts can slip through, so treat candidates as Review-equivalent until you've scanned a staging copy if you rely heavily on a builder. Builder-aware detection remains on the roadmap.

**Why do Combine JavaScript / Combine CSS / Remove Unused CSS do nothing in v1.12.0?**
Those three toggles are preserved in the UI for forward compatibility but currently act as no-ops with a clear admin notice. A safe implementation of each requires substantial engineering work scheduled for a future release. In the meantime, use Defer, Delay, Inline Critical CSS and Async CSS for similar real-world wins, and the [MBR Advanced Asset Manager](https://github.com/harbourbob/mbr-advanced-asset-manager) plugin for per-URL stylesheet control.

**Does AVIF require anything special on the server?**
Yes — server-side AVIF encoding needs PHP 8.1+ compiled with libavif (for the GD path) or Imagick 7.0.25+ with libheif. The WebP / AVIF tab includes a capability check showing whether each is available; the AVIF toggle is disabled in the UI if neither is present, so enabling AVIF on an unsupported server is impossible. Browser-side AVIF support is Chrome 85+, Firefox 93+, and Safari 16.4+.

**Self-hosting third-party scripts — does that affect tracking?**
Self-hosting addresses the *delivery* cost (no DNS / TLS / round-trip to googletagmanager.com on first paint, no visitor IP sent to Google or Meta just to fetch the script). It does not change the tracking behaviour itself — once the local copy executes, Google / Meta still receive analytics events as normal. For consent-gated tracking you also need a cookie consent banner like [MBR Cookie Consent](https://github.com/harbourbob/mbr-cookie-consent).

**What's the difference between WebP and Image Sizing?**
WebP and AVIF create smaller-format copies of each image without changing dimensions. Image Sizing changes the actual pixel dimensions so browsers don't download files larger than they need. They're complementary — use both for the biggest PageSpeed wins.

---

## 📝 Changelog

### 1.12.0
- ✨ **Three new tabs:** Server (browser cache headers + Brotli/Gzip via `.htaccess`, Nginx snippet for non-Apache hosts), Third-Party (self-host gtag.js / gtm.js / analytics.js / fbevents.js with daily refresh cron and URL rewriting), Diagnostics (Autoloaded Options Audit, WP-Cron Viewer with orphan detection, Caching Plugin Conflict Detector)
- ✨ **AVIF image conversion** alongside WebP. `<picture>` wrapper emits AVIF first, WebP second, original third. Configurable AVIF quality (default 60). Capability diagnostics shown on the WebP / AVIF tab; toggle disabled if neither GD AVIF (PHP 8.1+) nor Imagick AVIF (7.0.25+) is available
- 🔧 **JavaScript optimisations module fully wired up** (previously a placeholder class). Defer, Defer jQuery, Move-to-Footer, Remove jQuery (with test mode), inline-JS minification, Delay JS with interaction runtime + configurable timeout, Disable Concatenation, Remove Script Versions all functional with their own exclusion lists
- 🔧 **CSS optimisations module fully wired up** (previously a placeholder class). Inline Critical CSS, Async CSS (preload + onload + loadCSS polyfill), inline-CSS minification, Conditional Block Styles, Remove CSS Versions, Disable Elementor Google Fonts, Disable WooCommerce CSS on non-shop pages
- 🔧 **Database scheduled cleanup now runs** (cron event was registered in earlier releases but had no listener). Auto-draft purge, trash emptying, spam / unapproved comment cleanup, expired transient cleanup (multisite-aware), and revision trimming all execute on schedule. Schedule selector (daily / weekly / manual) re-schedules the cron automatically. New "Last Auto-Cleanup" log table and "Run Auto-Cleanup Now" button on the Database tab
- ✨ Minify HTML toggle on the Core tab — output-buffered, preserves `<pre>`, `<textarea>`, `<script>`, `<style>` and IE conditional comments
- ✨ Hover Prefetch toggle on the Preloading tab — instant.page v5.2.0 runtime (MIT). Honours `Save-Data: on`
- ✨ YouTube / Vimeo Facade on the Lazy Loading tab — embedded video iframes replaced with thumbnail + play button; real iframe only loads on click. Vimeo thumbnails lazy-fetched via the public API behind IntersectionObserver. Keyboard accessible
- ✨ `decoding="async"` toggle in the Image Sizing & Dimensions section — LCP candidate (any image with `fetchpriority="high"`) auto-skipped
- ✨ Strip EXIF Metadata on JPEG Upload toggle in the Image Sizing & Dimensions section — Imagick `stripImage()` preferred (ICC profiles preserved), GD fallback at quality 92
- 🐛 Fix: `crossorigin="anonymous"` is now explicit on all preload and preconnect tags — previously the bare `crossorigin` attribute was used, which is equivalent but flagged inconsistently by some Lighthouse audits
- 📦 Migration: four new option sections (`preloading`, `lazy_loading`, `third_party`, `server_headers`) seeded on upgrade. Idempotent — safe to run repeatedly
- 🧹 On deactivation: all v1.12.0 `.htaccess` marker blocks (*MBR AVIF*, *MBR Browser Cache*, *MBR Compression*) are removed cleanly. Third-party script refresh cron is unscheduled. AVIF file registry is purged
- ℹ️ Combine JavaScript, Combine CSS, and Remove Unused CSS toggles remain in the UI for forward compatibility but are no-ops in v1.12.0 — admin notice surfaces when enabled. Safe implementation of each is a separate engineering project planned for a future release

### 1.11.0
- ✨ **Orphaned Images tab renamed to Orphaned Media** — scope expanded to cover videos, audio, documents, and archives alongside images
- Type checkbox group in tab settings — opt in per type (Images / Videos / Audio / Documents / Archives); defaults to images-only on upgrade so v1.10.0 behaviour is preserved
- New Type column in the candidate list with category icons for quick visual scanning
- Type filter dropdown alongside the existing Confidence and Sort filters
- Per-type stat breakdown row showing count and reclaimable bytes per category
- In-panel **Save Settings** button — save the tab's settings without scrolling to the bottom of the page (the scanner reads from saved settings, not live form state)
- Behaviour change: `post_parent` is no longer treated as definitive proof an attachment is in use. WordPress sets `post_parent` on upload via the post editor and never clears it on content edits, so attachments uploaded into a post and later removed from content stayed hidden under v1.10.0. Parent-only matches now drop to Review tier so they can be inspected and deleted manually. Featured-image and post-content matches remain definitive
- Tab slug changed from `orphaned-images` to `orphaned-media`; the legacy slug continues to work for one release for any bookmarked URLs
- Backward-compatible: existing v1.10.0 staging-table rows and exclusions list carry over unchanged

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
