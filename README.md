<div align="center">

# ⚡ MBR Performance

### Comprehensive WordPress performance, granular control, zero tracking — free forever.

[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-GPL%20v2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-1.16.0-orange.svg)](https://github.com/harbourbob/mbr-wp-performance/releases)
[![Downloads](https://img.shields.io/github/downloads/harbourbob/mbr-wp-performance/total)](https://github.com/harbourbob/mbr-wp-performance/releases)
[![Buy Me A Coffee](https://img.shields.io/badge/Buy%20Me%20A%20Coffee-%E2%98%95-yellow.svg)](https://buymeacoffee.com/robertpalmer/)

**Thirteen tabs of individually-toggleable optimisations.** Image conversion, script and style delivery, font self-hosting, database cleanup, server-level caching, third-party self-hosting, plus a diagnostics suite that catches conflicts before they bite. Every option is explained in plain language, nothing phones home, and there's no "pro" tier holding features back.

[**📥 Download Latest**](https://github.com/harbourbob/mbr-wp-performance/releases) &nbsp;·&nbsp; [**🐛 Report a Bug**](https://github.com/harbourbob/mbr-wp-performance/issues) &nbsp;·&nbsp; [**💡 Request a Feature**](https://github.com/harbourbob/mbr-wp-performance/issues) &nbsp;·&nbsp; [**📖 User Guide (PDF)**](https://littlewebshack.com/wp-content/uploads/2026/05/mbr-wp-performance-user-guide-1.12.0.pdf)

</div>

---

## 🆕 What's new in 1.13.x

The 1.13.x line is a stability and feature-completion run on top of the major v1.12.0 release. Recent highlights:

- ✨ **Bulk AVIF Converter** *(1.13.8)* sits alongside the existing WebP one, with a unified conversion history table that now shows both **WebP Size** and **AVIF Size** columns side-by-side. Per-image AJAX with progress bar, registry-driven Revert All that deletes every `.avif` this plugin created without touching originals or WebP variants. Auto-convert on upload writes to the AVIF history option too, so freshly-uploaded images join the table alongside bulk-converted ones.
- 🐛 **AVIF false-positive fix** *(1.13.7)*. `function_exists('imageavif')` returns true on PHP 8.1+ even when libgd wasn't built with libavif — detection now uses `gd_info()['AVIF Support']` (the same reliable pattern the WebP detection has always used). Servers that "looked supported" but silently failed conversion now display a clear admin notice instead of pretending it's working.
- 🐛 **The real "CSS settings reset themselves" fix** *(1.13.5)*. Three font fields on the Fonts tab were scoped to `[css]` instead of `[fonts]` in their form `name` attributes — so saving the Fonts tab posted a partial `css` section that the CSS sanitiser obediently used to replace the entire stored CSS section, wiping every other CSS toggle to `false`. Two of those three fields were dead UI and have been removed; the third moved to its correct home with a migration. The trigger was reliably "click Save on the Fonts tab." Took a while to find.
- 🔧 **Critical-CSS chain redesign** *(1.13.4)*. The Auto-Generate Critical CSS button is gone — regex-based extraction across hardcoded selectors stripped `@media` wrappers off rules, matched anything starting with a single-letter target, and ignored CSS variables defined on `:root`. Paste critical CSS from a proper viewport-aware tool (Penthouse, Critical, Critters) instead. An async-CSS safety interlock also keeps the first 2 stylesheets render-blocking when there's no critical-CSS bridge in place, so async loading no longer paints unstyled content.
- 🐛 **Three audit fixes** *(1.13.3)*. The Critical CSS generator's output now persists (was writing to a sanitiser-stripped field), the Reset to Defaults button now works (was an inert `&reset=1` GET with no handler), and the Autoload Audit SQL now genuinely excludes the plugin's own options as its docblock had always claimed.
- 🎨 **UI contrast fix** *(1.13.6)*. Plugin notice text was inheriting WordPress core's near-black `.notice` colour against the dark-themed admin's tinted backgrounds, producing low-contrast pills that were hard to read.

Plus the standout 1.13.0 hook: a one-click switch to **disable WordPress 7.0's built-in AI subsystem** (AI Client, Abilities API, Connectors plumbing) via core's own `wp_supports_ai` kill switch.

[See the full changelog ↓](#-changelog)

---

## 🎯 At a glance

13 tabs, one toolbar menu, zero "Pro" tier:

- 🆓 **Free forever** — no premium gates, no upsells, no feature throttling
- 🔒 **Zero tracking** — never phones home, never sends analytics, never touches visitor data
- 🎛️ **Granular control** — toggle individual optimisations with clear explanations, not opaque "speed up my site" buttons
- 🌙 **Dark-mode admin** — lives in the WordPress toolbar, no sidebar clutter
- 🏗️ **Page-builder aware** — auto-disables inside Elementor, Bricks, Divi, Beaver Builder, Oxygen, WPBakery
- 🌐 **Multisite-ready** — full Network Admin with one-click push to all sites, plus per-site override control
- 🤝 **Plays nicely with caching plugins** — designed to complement WP Rocket, LiteSpeed, W3 Total Cache, FlyingPress, Autoptimize, Perfmatters. The built-in Conflict Detector shows exactly which options overlap with each
- 🤖 **WordPress 7.0 ready** — including the AI-subsystem kill switch

[Detailed features ↓](#-detailed-features) &nbsp;·&nbsp; [Why this plugin ↓](#-why-this-plugin) &nbsp;·&nbsp; [Quick start ↓](#-quick-start)

---

## 🏆 PageSpeed Insights warnings this plugin addresses

| Warning | How this plugin handles it |
|---|---|
| **Serve images in next-gen formats** | WebP **and** AVIF conversion with `<picture>` delivery (AVIF first, then WebP, then original) |
| **Properly size images** | Resize-on-upload at a configurable maximum, plus a Bulk Resize tool for existing libraries |
| **Defer offscreen images** | Native + IntersectionObserver lazy loading, including background-image lazy loading |
| **Image elements do not have explicit width and height** | Auto-inject missing `width`/`height` attributes from front-end `<img>` tags |
| **Eliminate render-blocking resources** | Async CSS (preload + onload), Defer JS, Delay JS until interaction, Inline Critical CSS |
| **Reduce the impact of third-party code** | Self-host gtag.js, GTM, analytics.js, Facebook Pixel; daily refresh cron keeps the local copies fresh |
| **Serve static assets with an efficient cache policy** | Server tab writes Browser Cache Headers to `.htaccess` (1 year for images, 1 month for CSS / JS) |
| **Enable text compression** | Server tab writes Brotli / Gzip rules to `.htaccess`; Nginx hosts get a copy-ready snippet |
| **Reduce JavaScript execution time** | Delay JS with an interaction-triggered runtime and configurable timeout |
| **Minify HTML / CSS / JavaScript** | Output-buffered HTML minify (preserves `<pre>`, `<textarea>`, `<script>`, `<style>`, `<svg>`, IE conditionals), plus inline JS/CSS minification |
| **Lazy load third-party resources** | YouTube / Vimeo facade — embedded iframes replaced with a thumbnail + play button until interaction |
| **Avoid an excessive DOM size** | Self-hosted Google Fonts removes render-blocking third-party DNS / TLS / fetch |

---

## 🎛️ Features at a glance

| Tab | Focus |
|-----|-------|
| ⚙️ **Core Features** | WordPress-level toggles: emojis, embeds, REST API, Heartbeat, query strings, HTML minify, disable WordPress 7.0 AI |
| 📜 **JavaScript** | Defer, defer jQuery, jQuery removal, minify, delay until interaction, disable concatenation |
| 🎨 **CSS** | Async loading, inline-CSS minification, unused-style scanner, conditional block styles, critical CSS textarea |
| 🔤 **Fonts** | Self-hosted Google Fonts, preloading, font-display, Font Awesome optimisation, Disable Elementor Google Fonts |
| 🚀 **Preloading** | LCP image preload, fetch priority, Cloudflare Early Hints, speculative loading, hover prefetch |
| 🐢 **Lazy Loading** | Native image/iFrame lazy loading, YouTube / Vimeo facade, fine-grained exclusions |
| 🗄️ **Database** | Revisions, transients, orphaned metadata, table optimisation, scheduled cleanup with last-run log |
| 🖼️ **WebP / AVIF** | WebP **and** AVIF conversion, bulk converters for both formats, `<picture>` delivery, `.htaccess` rules |
| 📐 **Image Sizing** | Resize large uploads, inject missing dimensions, `decoding="async"`, EXIF strip, bulk resize tool |
| 🖥️ **Server** | Browser cache headers, Brotli / Gzip compression, Nginx snippet for non-Apache hosts |
| 🌐 **Third-Party** | Self-host gtag.js, gtm.js, analytics.js, fbevents.js — daily refresh cron, URL rewriting |
| 🔬 **Diagnostics** | Autoload audit, WP-Cron viewer with orphan detection, caching-plugin conflict detector |
| 🗑️ **Orphaned Media** | Find and safely remove unreferenced images, videos, audio, documents, archives |
| 🛒 **WooCommerce** | Cart fragments, conditional asset loading, Action Scheduler retention |

> ℹ️ **Combine JS, Combine CSS, and Remove Unused CSS** remain visible in the UI for forward compatibility but are currently no-ops — they surface an admin notice when enabled. A safe implementation of each requires a separate engineering project. Use Defer, Delay, Inline Critical CSS, Async CSS, or [MBR Advanced Asset Manager](https://github.com/harbourbob/mbr-advanced-asset-manager) for per-asset control in the meantime.

> 🧹 **Clean uninstall:** All `.htaccess` marker blocks (*MBR AVIF*, *MBR Browser Cache*, *MBR Compression*) are removed cleanly on deactivation. The third-party script refresh cron is unscheduled. The AVIF file registry is purged.

---

## 💚 Why this plugin?

There are good performance plugins on WordPress.org and elsewhere. Here's why this one might fit better than the alternative you're currently looking at:

**You want everything in one place, but not on rails.** WP Rocket and similar are excellent if you want "click everything on and trust us." This plugin is for site owners who want to understand each toggle and choose. Every option has a plain-language explanation, every default is conservative, and every change is reversible.

**You already have a caching plugin and don't want to fight it.** The Diagnostics tab actively detects WP Rocket, LiteSpeed, W3 Total Cache, FlyingPress, WP Super Cache, Perfmatters, and Autoptimize, and tells you which MBR options overlap with each. There's deliberately no full-page caching here — this plugin complements caching plugins rather than replacing them.

**You care about privacy.** No telemetry, no remote pings, no third-party services. Free isn't a loss-leader for an upsell — it's just the entire product.

**You like a long, honest changelog.** Each release tells you what changed, why, and what edge case prompted the fix. If something broke and got fixed, the entry says so plainly. You can read the changelog as a portrait of how the plugin actually evolves.

**You run unusual stacks.** Multisite, FSE themes, custom REST APIs, page builders, AMP, mixed PHP versions — the README and changelog call out each compatibility quirk explicitly so you can decide whether a feature is right for your site before flipping it on.

---

## 📦 Quick start

### Install via WordPress Admin

1. Download the [latest release](https://github.com/harbourbob/mbr-wp-performance/releases)
2. Go to **Plugins → Add New → Upload Plugin**
3. Upload the zip and click **Install Now**
4. Activate
5. Click **WP Performance** in the admin toolbar

### Or manually

1. Extract the zip
2. Upload `mbr-wp-performance/` to `/wp-content/plugins/`
3. Activate from the Plugins screen

### Or via WP-CLI

```bash
wp plugin install mbr-wp-performance.zip --activate
```

### First-time setup, in a safe order

After activation, open **WP Performance** from the admin toolbar.

1. 🔬 **Diagnostics tab** — open this first. Check the Caching Plugin Conflicts panel; if any caching plugin is detected, decide who owns each optimisation before enabling anything below. Glance at the Autoloaded Options Audit and WP-Cron Viewer for any red flags
2. 🗄️ **Database tab** — clean up accumulated bloat (revisions, expired transients, orphaned metadata). Click **Run Auto-Cleanup Now** once to clear any backlog
3. 🖥️ **Server tab** — enable Browser Cache Headers and Brotli / Gzip Compression (Apache / LiteSpeed write to `.htaccess`; Nginx hosts get a copy-ready snippet)
4. 🐢 **Lazy Loading tab** — enable image and iFrame lazy loading; enable YouTube / Vimeo Facade if you embed videos
5. ⚙️ **Core Features tab** — disable emojis, embeds, and dashicons if you don't use them; enable **Minify HTML**
6. 🖼️ **WebP / AVIF tab** — check the server diagnostics, run the WebP bulk converter; if AVIF is supported, run that one too
7. 📐 **Image Sizing & Dimensions** — enable "Resize Large Uploads", "Add Missing Width & Height", `decoding="async"`, and Strip EXIF Metadata
8. 🚀 **Preloading tab** — enable Hover Prefetch; auto-prioritise the first image if your hero is reliably the LCP element
9. 🌐 **Third-Party tab** — enable self-hosting for whichever tracking scripts you use; click **Refresh Now** to populate the cache
10. 🗑️ **Orphaned Media tab** *(optional)* — back up first, then run a scan to identify reclaimable disk space across images, videos, audio, documents, and archives
11. 🛒 **WooCommerce tab** *(if applicable)* — disable cart fragments, dequeue shop assets on non-shop pages, set Action Scheduler retention to 14 days

> 💡 Enable features one at a time and test after each change. Page builder editors are automatically detected and bypassed.

---

## 📋 Requirements

- WordPress **5.8** or higher (tested up to **7.0**)
- PHP **7.4** or higher (**PHP 8.1+** with libavif for the GD AVIF path)
- MySQL **5.6** or higher
- GD library with WebP support for image conversion and resizing
- *(Optional)* Imagick 7.0.25+ with libheif, or GD with libavif compiled in — for AVIF conversion. Capability is auto-detected via `gd_info()['AVIF Support']`; the AVIF section is hidden in the UI if neither is present

---

## 📚 Detailed features

### ⚙️ Core Features
Disable WordPress defaults that don't earn their place — emojis, embeds, dashicons, jQuery Migrate, XML-RPC, RSS feeds, self-pingbacks, REST API links. Throttle the Heartbeat API, limit revisions, strip query strings. Three REST API access modes for tightening user enumeration without breaking the block editor, with a namespace allowlist for plugins that legitimately need public REST access. **Minify HTML** — output-buffered, comments and whitespace stripped, `<pre>` / `<textarea>` / `<script>` / `<style>` / `<svg>` and IE conditional comments preserved, and pages containing a nested/embedded HTML document automatically skipped. **Disable AI Features (WordPress 7.0+)** — switches off WordPress 7.0's built-in AI Client, Abilities API, and Connectors plumbing via core's own `wp_supports_ai` kill switch; off by default and inert on WordPress 6.x.

### 📜 JavaScript Optimisation
Defer or async script loading, defer jQuery specifically, move scripts to the footer, optionally remove jQuery entirely (with a test mode that scopes the removal to logged-out visitors only). Minify inline JS and delay execution of analytics and chat widgets until the user actually interacts with the page — configurable timeout ensures delayed scripts run eventually even if the user never interacts. Per-option exclusion lists keep your essential scripts running normally. Disable WordPress's admin-script concatenation, strip `?ver=` query strings from script URLs.

### 🎨 CSS Optimisation
Async loading (preload + onload pattern with the standard loadCSS polyfill for older browsers), inline-CSS minification, built-in scanner for unused styles. Conditionally load block styles, remove global styles for classic themes, kill duplicate Elementor Google Fonts requests, dequeue WooCommerce stylesheets on non-shop pages, strip CSS version query strings. **Async-CSS safety interlock** *(new in 1.13.4)*: when Async CSS is enabled without a critical-CSS bridge (Inline Critical CSS + Critical CSS Code populated), the first two stylesheets stay render-blocking to prevent a flash of unstyled content. Paste critical CSS produced by a proper viewport-aware tool — Penthouse, Critical, Critters, or any of the online critical-CSS generators — into the Critical CSS Code field for full async loading.

### 🔤 Font Management
Self-host Google Fonts to eliminate render-blocking third-party requests (and improve GDPR posture). Preload critical fonts with an explicit `crossorigin="anonymous"` attribute, manage manual entries for fonts the auto-scanner misses, enable subsetting, and pick your `font-display` strategy. Optimise or fully disable Font Awesome. **Disable Elementor Google Fonts** — sits in this tab (1.13.5 onwards) and switches off Elementor's separate Google Fonts requests when you've already self-hosted them.

### 🚀 Preloading & Speculative Loading
Preload your LCP image so it lands fast. Configure fetch priority manually or auto-prioritise the first image in main content. Emit Cloudflare Early Hints (HTTP 103) for edge-level preloading. The Speculation Rules API prefetches or prerenders the next page with conservative, moderate, eager, or auto eagerness. **Hover Prefetch** uses the canonical instant.page v5.2.0 runtime (MIT) — on link hover or first touchstart, the destination page is prefetched. Honours the `Save-Data: on` request header so users on metered connections aren't penalised.

### 🐢 Lazy Loading
Native browser lazy loading for images and iFrames with configurable thresholds. Background-image lazy loading via IntersectionObserver. Exclude by selector, class, ID, data attribute, filename keyword, or parent container — six different ways to keep your hero image loading early. DOM monitoring catches dynamically inserted images from carousels, infinite scroll, and AJAX content. **YouTube / Vimeo Facade** — embedded video iframes are replaced with a static thumbnail and play button; the real iframe only loads on click. Saves roughly 1.4MB of YouTube JavaScript on initial page load, prevents YouTube cookies until interaction, keyboard accessible. Vimeo thumbnails are lazy-fetched via the public API behind an IntersectionObserver so the network request only happens when the facade scrolls into view.

### 🗄️ Database Optimisation
**Scheduled cleanup runs** — auto-draft purge with configurable age, trash emptying with configurable retention, spam and unapproved comment cleanup with their own age thresholds, expired transient cleanup (multisite-aware), and revision trimming to the keep-N setting. Schedule selector (daily / weekly / manual only) re-schedules the cron automatically when changed. **Last Auto-Cleanup log** displays the time of the last run with per-action item counts. **Run Auto-Cleanup Now** button triggers the cleanup logic on demand for clearing a backlog. Plus all the existing tools: post revisions retention, one-click orphaned metadata scanners across posts / comments / terms / taxonomy relationships, transient stats and cleanup, `OPTIMIZE TABLE`, MyISAM-to-InnoDB conversion, table repair, diagnostic info panel.

### 🖼️ WebP / AVIF Image Conversion
Convert JPG, JPEG, and PNG to WebP with configurable compression (1–100). Auto-convert on upload plus a bulk converter for your existing Media Library. Serve via HTML `<picture>` tags or `.htaccess` rewrite rules. Originals are never modified — the WebP sits alongside as a parallel file. Skip-when-larger detection, full conversion history, and a "Revert All" button that cleans up every plugin-created WebP without touching originals.

**AVIF conversion** alongside WebP. The `<picture>` wrapper emits AVIF first, then WebP, then the original — browsers pick the first format they support. Configurable AVIF quality (default 60, perceptually equivalent to WebP at 75). Server capability diagnostics show whether GD AVIF (PHP 8.1+ with libavif compiled in, detected via `gd_info()['AVIF Support']`) or Imagick AVIF (7.0.25+ with libheif) is available; the AVIF section is hidden in the UI if neither is present.

**Bulk AVIF Converter** *(new in 1.13.8)* — sits alongside the WebP one with its own Start / Clear History / Revert All buttons, per-image AJAX progress, and a unified Conversion History table that now shows both **WebP Size** and **AVIF Size** columns. Each image appears as a single row with whichever format data exists. The Compression column reports savings against whichever format is smallest (AVIF when present, since it's typically 20–30% smaller than WebP at equivalent perceived quality).

### 📐 Image Sizing & Dimensions
Two PageSpeed wins: auto-resize oversized uploads (default 2560px, configurable) using the WordPress core scaling pipeline, and inject missing `width`/`height` attributes into front-end `<img>` tags to eliminate Cumulative Layout Shift. The Bulk Resize tool downscales existing Media Library images in place, regenerating sub-sizes and cleaning up stale WebP / AVIF copies along the way. **`decoding="async"`** on images — lets the browser decode off the main thread, improving INP on image-heavy pages. The LCP candidate (any image with `fetchpriority="high"`) is automatically skipped. **Strip EXIF metadata on JPEG upload** — removes camera serial, GPS coordinates, embedded thumbnails. ICC colour profiles preserved so colours stay accurate. Imagick `stripImage()` preferred, GD fallback at quality 92. Only affects new uploads; existing images are unchanged.

### 🖥️ Server
Writes server-level configuration to `.htaccess` on Apache and LiteSpeed for browser caching and text compression — two of the most common PageSpeed Insights warnings that can't be fixed from inside PHP. **Browser cache headers** with conservative expiry windows (1 year for images / fonts / video, 1 month for CSS / JS, 0 for HTML, 1 hour for feeds), `Cache-Control: public, max-age=31536000, immutable` as belt-and-braces. **Brotli / Gzip compression** with `mod_brotli` preferred where loaded, `mod_deflate` fallback. On Nginx and IIS hosts (where `.htaccess` is ignored), the tab detects the server and shows an equivalent server-config snippet to paste into your configuration. Marker blocks (*MBR Browser Cache*, *MBR Compression*) are removed cleanly when you disable the toggles or deactivate the plugin.

### 🌐 Third-Party
Self-hosts common tracking scripts and rewrites outbound `<script src=>` URLs in the page output to point at the local copies. Removes the PSI *"Reduce the impact of third-party code"* warning and stops first-paint network requests to googletagmanager.com and connect.facebook.net. Supported scripts: **Google Analytics (gtag.js)**, **Google Tag Manager (gtm.js)** (query strings like `?id=GTM-XXXXX` preserved intact so your container IDs keep working), **Google Analytics (analytics.js)** for legacy Universal Analytics, **Facebook Pixel (fbevents.js)**. Each enabled script is downloaded once on enable then refreshed daily via the `mbr_wp_performance_third_party_refresh` WP-Cron event. Cache Status panel shows last-refresh time, per-script success / failure, and a manual Refresh Now button. Pairs particularly well with Delay JavaScript Until Interaction on the JS tab — the script is served from your own domain *and* only executes when the user interacts.

### 🔬 Diagnostics
Three diagnostic tools in one tab, each targeting a common WordPress performance footgun.

- **Caching Plugin Conflicts** — detects WP Rocket, W3 Total Cache, LiteSpeed Cache, FlyingPress, WP Super Cache, Perfmatters, and Autoptimize, and lists exactly which MBR options overlap with each. Prevents the common pitfall of having Defer / Minify / cache headers enabled in two plugins at once
- **Autoloaded Options Audit** — shows total bytes currently being autoloaded plus the top 30 options by size. One-click "Disable autoload" button on each row. Around 85 protected core options (`siteurl`, `home`, `active_plugins`, `template`, `stylesheet`, etc.) cannot be modified — the toggle is replaced with an em-dash. The plugin's own options are excluded from the list *(1.13.3)*. Transients (autoloaded transients are almost always a bug) are flagged
- **WP-Cron Viewer** — lists every scheduled event with its hook, next-run time, recurrence, and a Callback? column showing whether any PHP callback is currently registered for the hook. Events with no callback are flagged "orphan" (left behind by deactivated plugins) and can be unscheduled with one click. Includes setup instructions for replacing WP-Cron with a real system cron job

### 🗑️ Orphaned Media
Find attachments no longer referenced anywhere on your site — images, videos, audio, documents, and archives. Tick the media types you want to scan, hit Run Scan, review the candidates, delete the ones you don't need. Detection covers featured images and post content (matched by attachment ID, shortcode reference, and filename stem so sized image variants and URL-only video / audio references are caught), with a string-search across postmeta values for everything else. Post parents are treated as suggestive evidence rather than definitive — attachments uploaded for a post and later removed from the content correctly surface as Review-tier candidates instead of being silently skipped. Two confidence tiers — high-confidence orphans are eligible for bulk-delete; review-tier candidates require manual inspection. For images, deletion handles the original file, every WordPress sub-size variant, and matching `.webp` and `.avif` siblings; for other media types it removes the single attached file. Pre-deletion re-verification blocks deletes if an attachment has become referenced since the last scan. Defaults to images-only on upgrade — opt in to other types via the settings checkboxes.

### 🛒 WooCommerce Optimisations
Cart fragments control — disable the admin-ajax mini-cart sync request site-wide or only on non-shop pages, often the single biggest TTFB win on cached stores. Strip WooCommerce scripts, styles, and block assets from non-shop pages. Stop the heavy `wc-admin` React bundles loading across unrelated admin screens. Configurable Action Scheduler retention (default 30 days; options for 14, 7, or 3) keeps `actionscheduler_actions` from ballooning. Weekly automated cleanup of expired sessions, transients, and Action Scheduler history. Geolocation advisory notice flags settings that interact badly with full-page caching.

### 🌐 Multisite Network Support
Network-activate and manage defaults from the Network Admin. Push settings to all (or selected) sites in one click, import settings from any existing site as the network default, and control whether site admins can override or are locked to network configuration. New sites automatically inherit network defaults on creation.

---

## 🏗️ Page builder compatibility

Optimisations are auto-disabled inside:

- Elementor (editor and preview)
- Beaver Builder
- Divi Builder
- Oxygen Builder
- Bricks Builder
- WPBakery Page Builder

No configuration needed. Upload-pipeline modules (WebP, AVIF, EXIF strip, resize) still run regardless of editor context *(fixed in 1.12.2)*, so images uploaded through Elementor's media picker get the same treatment as direct Media Library uploads.

---

## 🔄 Upgrading from earlier versions

**From v1.11.0 or earlier:** Settings are preserved through the update. v1.12.0 includes a migration block that seeds the new option sections (`preloading`, `lazy_loading`, `third_party`, `server_headers`) automatically. v1.13.5 includes a further migration that moves `disable_elementor_fonts` from `[css]` to `[fonts]` if it was previously set. No manual configuration required for the migrations themselves, but you'll want to visit the new Diagnostics, Server, and Third-Party tabs to opt in to their settings.

**From the standalone MBR WebP Converter plugin:** Deactivate it after upgrading to v1.6.0 or later — your conversion history and WebP file registry migrate automatically on first load. All existing WebP files stay in place and continue to be served.

---

## ❓ FAQ

<details>
<summary><strong>Will it break my site?</strong></summary>

It's designed to be safe, but always back up first, test on staging, and enable features one at a time. Each option is independently toggleable — anything you enable, you can disable. The **Diagnostics tab** detects active caching plugins and shows which MBR options overlap with each, which catches the most common cause of breakages when stacking performance plugins.
</details>

<details>
<summary><strong>Does it work with my caching plugin?</strong></summary>

Yes — and the Diagnostics tab has a built-in conflict detector specifically for this. There's deliberately no overlap with full-page caching; this plugin provides complementary optimisations alongside WP Rocket, LiteSpeed, W3 Total Cache, FlyingPress, WP Super Cache, Perfmatters, Autoptimize, and others. Open the Diagnostics tab to see exactly which MBR options overlap with any active caching plugin, and pick one or the other.
</details>

<details>
<summary><strong>What's the difference between WebP and AVIF?</strong></summary>

Both are next-generation image formats designed to replace JPEG and PNG. AVIF (2019) is typically 20–30% smaller than WebP (2010) at equivalent perceived quality, but needs more recent browser support (Chrome 85+, Firefox 93+, Safari 16.4+) and stricter server-side encoding (PHP 8.1+ with libavif, or Imagick 7.0.25+). Enable both — the `<picture>` wrapper sends AVIF to capable browsers, WebP to the next tier, and the original format as fallback.
</details>

<details>
<summary><strong>Does AVIF require anything special on the server?</strong></summary>

Yes — server-side AVIF encoding needs PHP 8.1+ compiled with libavif (for the GD path) or Imagick 7.0.25+ with libheif. The WebP / AVIF tab includes a capability check showing whether each is available; the AVIF section is hidden in the UI if neither is present. **A common gotcha:** `function_exists('imageavif')` returns true on PHP 8.1+ even when libgd was built *without* libavif, so historic detection was unreliable. v1.13.7 onwards uses `gd_info()['AVIF Support']` instead, which reflects what libgd was actually compiled with. If your site previously had AVIF "enabled" but no `.avif` files appearing, you were almost certainly hitting that false positive.
</details>

<details>
<summary><strong>Does it touch my original images?</strong></summary>

Never. WebP and AVIF files are parallel files (same name, different extension). Resize-on-upload uses the WordPress core scaling pipeline. EXIF stripping only affects new JPEG uploads — existing files are not touched. Only the **Bulk Resize tool** and **Orphaned Media deletion** modify or remove files on disk — both are clearly flagged as destructive and require manual confirmation.
</details>

<details>
<summary><strong>Does the Orphaned Media scanner check all media types by default?</strong></summary>

No — it defaults to images-only, matching v1.10.0 behaviour. To include videos, audio, documents, or archives, tick the relevant boxes in the tab settings and click **Save Settings** before running a scan.
</details>

<details>
<summary><strong>Is it safe to bulk-delete orphaned media?</strong></summary>

Bulk deletion is restricted to **high-confidence** orphans (no references found in any check). Review-tier candidates must be deleted individually after manual inspection. Deletions stage to a restore queue for the configured window, so you can reinstate the database record if you change your mind — though file bytes are removed at deletion time and require a backup to recover.
</details>

<details>
<summary><strong>Why do attachments with <code>post_parent</code> matches show in Review tier instead of being skipped?</strong></summary>

WordPress sets `post_parent` on attachments uploaded via the post editor, but it never clears the link when you remove the attachment from the post's content. An attachment can have a "live parent" but not actually appear anywhere in that parent's content. v1.11.0 onwards treats `post_parent` as suggestive evidence rather than definitive — those candidates surface in Review tier so you can open the parent post, confirm the attachment is genuinely unused, and delete it manually.
</details>

<details>
<summary><strong>What about page builder content?</strong></summary>

Page-builder data stores (Elementor `_elementor_data`, Bricks `_bricks_page_content_2`, Beaver Builder, Oxygen, WPBakery) aren't yet directly inspected by the Orphaned Media scanner — the postmeta string-search picks up most references but not all. URL-only references inside builder layouts can slip through, so treat candidates as Review-equivalent until you've scanned a staging copy if you rely heavily on a builder. Builder-aware detection remains on the roadmap.
</details>

<details>
<summary><strong>Why do Combine JavaScript / Combine CSS / Remove Unused CSS do nothing?</strong></summary>

Those three toggles are preserved in the UI for forward compatibility but currently act as no-ops with a clear admin notice. A safe implementation of each requires substantial engineering work scheduled for a future release. In the meantime, use Defer, Delay, Inline Critical CSS, and Async CSS for similar real-world wins, and the [MBR Advanced Asset Manager](https://github.com/harbourbob/mbr-advanced-asset-manager) plugin for per-URL stylesheet control.
</details>

<details>
<summary><strong>Why was the Auto-Generate Critical CSS button removed in 1.13.4?</strong></summary>

Because the only safe way to produce critical CSS is to render the page in a headless browser at a target viewport and extract rules that apply to elements actually above the fold. Doing that in PHP via regex extraction across hardcoded selectors — which is what the button did — has too many failure modes to ship as a default user-facing feature: it stripped `@media` wrappers off rules, matched anything starting with a single-letter target like `p` or `a`, and ignored CSS variables on `:root`. The Critical CSS Code textarea remains; paste critical CSS produced by Penthouse, Critical, Critters, or any of the online critical-CSS generators into it.
</details>

<details>
<summary><strong>Self-hosting third-party scripts — does that affect tracking?</strong></summary>

Self-hosting addresses the *delivery* cost (no DNS / TLS / round-trip to googletagmanager.com on first paint, no visitor IP sent to Google or Meta just to fetch the script). It does not change the tracking behaviour itself — once the local copy executes, Google / Meta still receive analytics events as normal. For consent-gated tracking you also need a cookie consent banner like [MBR Cookie Consent](https://github.com/harbourbob/mbr-cookie-consent).
</details>

<details>
<summary><strong>What's the difference between WebP and Image Sizing?</strong></summary>

WebP and AVIF create smaller-format copies of each image without changing dimensions. Image Sizing changes the actual pixel dimensions so browsers don't download files larger than they need. They're complementary — use both for the biggest PageSpeed wins.
</details>

<details>
<summary><strong>Does Minify HTML work with page builders?</strong></summary>

Yes. It preserves `<pre>`, `<textarea>`, `<script>`, `<style>` and `<svg>` contents intact, only collapses whitespace runs that span a line break (so inline-element spacing and attribute values are kept), and skips AMP / REST / AJAX responses. It also detects pages that embed a *complete* HTML document inside a builder HTML widget (a nested `<!doctype>` / `<html>` / `<head>`) and skips those automatically, since collapsing whitespace across a nested document can confuse the browser's parser. One honest caveat: with Brotli / Gzip compression enabled (see the Server tab), HTML minification saves very little of the *compressed* transfer — it's the lowest-impact optimisation here, so don't feel obliged to use it.
</details>

<details>
<summary><strong>What does "Disable AI Features (WordPress 7.0+)" actually do?</strong></summary>

WordPress 7.0 ships a built-in AI subsystem — an AI Client, the Abilities API, and a Settings → Connectors screen for wiring the site to AI providers. It stays dormant until a provider connector is configured, so the default-install cost is minimal. This toggle switches the whole subsystem off via core's own kill switch (`wp_supports_ai` → `__return_false` at `PHP_INT_MAX`), with `wp_ai_client_prevent_prompt` as a second guard. It's off by default, has no effect on WordPress 6.x (the filter doesn't exist there), and is safe to leave enabled across mixed-version sites. It's a surface-area control, not a dashboard-hiding tool — the Connectors screen stays in place.
</details>

---

## 📝 Changelog

### 1.13.9
- 🎨 **Fix (UI):** The "Compression" column header on the Conversion History table was 110px wide, just narrow enough that the word wrapped onto a second line at the standard wp-list-table header font weight. Bumped to 140px so the label sits cleanly on one line

### 1.13.8
- ✨ **New: Bulk AVIF Converter.** The WebP tab now has an AVIF Bulk Converter section alongside the existing WebP one (Start AVIF Conversion / Clear AVIF History / Revert All AVIF Files), and only renders when the server has a real AVIF encoder available. Mirrors the WebP converter's architecture: per-image AJAX with progress bar, history option (`mbr_avif_converted_images`) parallel to the WebP one, and a registry-driven Revert All that deletes every `.avif` this plugin created without touching originals or WebP variants
- ✨ **New: AVIF Size column** on the Conversion History table, alongside the existing WebP Size column. The table now merges records from both `mbr_webp_converted_images` and `mbr_avif_converted_images` keyed by original path, so each image appears as a single row with whichever format data exists (a dash shown where a format hasn't been generated for that image). The Compression column reports savings against whichever recorded format is smallest — AVIF when present (typically 20–30% smaller than WebP at equivalent perceived quality), otherwise WebP
- ✨ Auto-convert on upload now also writes to the AVIF history option, so newly-uploaded images appear in the table alongside bulk-converted ones

### 1.13.7
- 🐛 **Fix (AVIF false-positive detection):** Server AVIF support was being detected via `function_exists('imageavif')`, which is unreliable. From PHP 8.1 onwards the `imageavif()` function is declared whether or not libgd was actually compiled against libavif; on the very common shared-host configuration where it wasn't, the function exists but calls fail silently at runtime, no `.avif` files are produced, and visitors are served WebP (or the original) regardless of how aggressively the user has toggled AVIF on. Detection now uses `gd_info()['AVIF Support']`, which reflects what libgd was actually built with — the same reliable pattern the plugin's WebP detection has always used via `gd_info()['WebP Support']`
- ✨ When AVIF is enabled in settings but the server can't actually encode AVIF, the plugin now (a) does NOT register the upload-conversion filter (so it won't trigger per-upload warnings), (b) shows an admin notice on its own admin pages explaining the mismatch, and (c) always renders the AVIF capability diagnostic on the WebP tab — both when supported (info notice with a quick confirmation) and when unsupported (warning notice with the GD / Imagick breakdown). Previously the diagnostic was only shown in the unsupported case, and the supported case showed nothing at all

### 1.13.6
- 🎨 **Fix (UI contrast):** Inline status messages rendered by AJAX handlers — visible on the Database panel and elsewhere — appeared with WordPress core's near-black `.notice` text colour on the plugin's dark-themed success / error / warning / info backgrounds, producing a low-contrast pill that was hard to read against the green tint. The plugin's `.notice-success`, `.notice-warning`, `.notice-error` and `.notice-info` rules now set `color: var(--mbr-text-primary)` (the same light text colour already used by the equivalent `.mbr-wp-performance-message.success/error` rules) and apply it to nested `<p>` elements too so it wins over any descendant rules WP core may inject

### 1.13.5
- 🐛 **Fix (the actual root cause of the "CSS toggles revert" bug):** Three font-related fields on the Fonts tab — Optimize Google Fonts, Font Display Strategy (duplicate), and Disable Elementor Google Fonts — had their form inputs scoped to `mbr_wp_performance_options[css][...]` instead of `[fonts][...]`. Submitting the Fonts tab therefore POSTed a partial `css` section, and the CSS sanitiser — doing exactly what a section sanitiser should do — replaced the entire stored `css` section with the partial input, setting every CSS boolean not present in the form to `false` and dropping the textareas. This is what users were observing as "the CSS settings all return to default (switched off) without warning" after some unknown period of time: the unknown period was the time between saving the CSS tab and saving the Fonts tab. The earlier v1.13.4 async-CSS safety interlock papered over the visual symptom (no FOUC even when toggles got wiped); this release fixes the cause
- 🧹 **Removed:** The "Optimize Google Fonts" radio (default / combine) and the duplicate "Font Display Strategy" select on the Fonts tab. Both were dead UI — the radio had no runtime consumer and the select was a redundant duplicate of the legitimate, properly-scoped `[fonts][font_display]` select already present higher up the same tab
- 🔧 **Moved:** The Disable Elementor Google Fonts checkbox now writes to `[fonts][disable_elementor_fonts]` (was `[css][disable_elementor_fonts]`), and its runtime hook (`elementor/frontend/print_google_fonts` filter) moves from the CSS optimisations class to the font optimisations class where it belongs
- 📦 **Migration:** on upgrade, any existing `[css][disable_elementor_fonts]` value is moved to `[fonts][disable_elementor_fonts]` (only if `[fonts]` doesn't already have a deliberate value, so a real user choice isn't clobbered). Any stale `[css][font_display]` is similarly migrated. The orphan `[css][google_fonts_mode]` key is dropped. Migration is idempotent

### 1.13.4
- 🧹 **Removed: Auto-Generate Critical CSS button.** The generator used regex-based extraction across hard-coded selectors (`body`, `header`, `h1`, `a` and similar), which is structurally too crude to produce safe critical CSS for a modern WordPress site. Failure modes included stripping `@media` wrappers off rules (extracting `body { padding: 0 }` from inside a viewport-conditional block and applying it at all viewports), matching unrelated selectors when one starts with a single-letter target (`a` matched `.article`, `p` matched `.product` etc.), blind inlining of every `@font-face` block on the site, and ignoring CSS variables defined on `:root`. The Critical CSS Code textarea remains; users who want this feature should paste in critical CSS produced by a proper viewport-aware tool such as Penthouse, Critical, Critters, or any of the online critical-CSS generators
- ✨ **New: Async-CSS safety interlock.** If "Load CSS Asynchronously" is enabled WITHOUT a critical-CSS bridge (Inline Critical CSS on + Critical CSS Code populated), the first two render-blocking-eligible stylesheets now stay render-blocking and the rest are still async'd. This guarantees the page paints with real CSS at first paint regardless of how the rest of the chain is configured. With a critical-CSS bridge in place, every stylesheet is async'd as before
- ℹ️ Note on upgrade: if your site has Load CSS Asynchronously on but no critical CSS provided, the first 2 stylesheets will become render-blocking again on upgrade. This is the correct behaviour — that configuration was previously causing a flash-of-unstyled-content window on first paint

### 1.13.3
- 🐛 **Fix (Critical CSS):** The "Auto-Generate Critical CSS" button stored its output under an internal key (`[css][critical_css_content]`) that was not in the CSS sanitiser's whitelist, so the sanitiser stripped it on every save. Generated CSS only survived at all because the admin JS also drops it into the editable textarea, which does persist. The generator now writes straight to the canonical `[css][critical_css]` field
- 🐛 **Fix (Reset to Defaults):** The "Reset to Defaults" button did nothing. It navigated to `?...&reset=1`, but no handler ever read that parameter. It now runs a proper nonce- and capability-checked POST request that resets every section to its defaults and reloads the page. Implemented as POST rather than a GET link so it can't be triggered by a crawler, prefetch, or a stray bookmark
- 🐛 **Fix (Autoload Audit):** The Diagnostics → Autoloaded Options audit claimed in code to exclude the plugin's own options but the query didn't, so MBR WP Performance's own options could appear in the list and be offered for autoload-disabling. The query now excludes the `mbr_wp_performance_` namespace as documented
- 🧹 First-install defaults and the new reset routine now share a single `default_options()` definition rather than duplicating the structure

### 1.13.2
- 🐛 **Fix (HTML minify):** pages that embed a complete HTML document inside a page-builder HTML widget (e.g. an Elementor "HTML" widget holding a full `<!doctype html>…</html>` landing page) could lose their layout — the nested, doubly-declared document re-parsed differently in the browser once its whitespace was collapsed, dropping the container boundaries and letting content run full-width. The minifier now detects a nested/embedded document (a second `<!doctype>`, `<html>`, or `<head>`) and skips those pages entirely, byte-for-byte. Normal single-document pages are minified as before

### 1.13.1
- 🐛 Carries the v1.12.1–v1.12.3 fixes forward into the 1.13.x line — they were made against the v1.12.0 base and had not yet reached the v1.13.0 branch. See the 1.12.1–1.12.3 entries below for detail

### 1.13.0
- ✨ **New "Disable AI Features (WordPress 7.0+)" toggle** on the Core tab (under WordPress Features). WordPress 7.0 ships a built-in AI Client, the Abilities API, and a Settings → Connectors screen for wiring a site to AI providers. The toggle switches the whole subsystem off via core's own kill switch — `wp_supports_ai` → `__return_false` at `PHP_INT_MAX`, plus `wp_ai_client_prevent_prompt` as a second guard — so the AI Client and Abilities API never bootstrap
- ℹ️ Off by default; existing behaviour unchanged on upgrade. No effect on WordPress 6.x (the `wp_supports_ai` filter doesn't exist there), so it's safe across mixed-version sites
- ✅ Tested up to WordPress 7.0

<details>
<summary><strong>Earlier releases (1.12.x and below)</strong></summary>

### 1.12.3
- 🐛 **Fix (HTML minify):** Minify HTML broke the front end of every site it was enabled on. The placeholder used to protect `<script>` / `<style>` / `<pre>` / `<textarea>` blocks was itself an HTML comment (`<!--MBR_PLACEHOLDER_0-->`), so the comment-stripping pass deleted the placeholders and the protected blocks were never restored — every inline script and stylesheet was silently dropped. Placeholders are now a collision-free per-request token. Whitespace handling is also more conservative (only newline-spanning runs collapse to a single space) so inline-element spacing and attribute values survive; inline `<svg>` is protected; AMP / REST / AJAX responses are skipped; and each regex pass falls back to the un-minified buffer if PCRE bails

### 1.12.2
- 🐛 **Fix (Elementor uploads):** WebP / AVIF conversion, EXIF stripping, and resize-on-upload were silently bypassed when uploading through Elementor's media picker (or any page-builder media interface that triggers editor-context detection). The editor-context early-return was disabling upload-pipeline optimisations as collateral damage. Upload-pipeline modules (WebP, AVIF, Image Dimensions, Image Enhancements) now initialise regardless of editor context; each still self-gates its editor-sensitive front-end filters, so front-end optimisations remain suppressed inside editors as before

### 1.12.1
- 🐛 **Fix (Orphaned Media):** the scanner failed to detect orphan PDF, Word, video, audio, and archive files because the post_content reference check stripped the file extension before matching. Stem-only matching is correct for images (so `image-300x200.jpg` matches `image.jpg`) but for non-image media it caused unrelated URLs to match — a PDF called `pricing.pdf` was treated as referenced whenever any post linked to `/pricing-page/`. Non-image media now match against the full filename including extension; images keep the stem-based logic so sized-variant detection is unchanged
- 🐛 Fix: the Type = Documents / Videos / Audio / Archives filter on the candidate list returned nothing, because it queried `post_mime_type` against the staging table (whose column is `mime_type`). `build_mime_where()` now takes a column-name parameter and the staging-table caller passes the correct value

### 1.12.0
- ✨ **Three new tabs:** Server (browser cache headers + Brotli / Gzip via `.htaccess`, Nginx snippet for non-Apache hosts), Third-Party (self-host gtag.js / gtm.js / analytics.js / fbevents.js with daily refresh cron and URL rewriting), Diagnostics (Autoloaded Options Audit, WP-Cron Viewer with orphan detection, Caching Plugin Conflict Detector)
- ✨ **AVIF image conversion** alongside WebP. `<picture>` wrapper emits AVIF first, WebP second, original third. Configurable AVIF quality (default 60). Capability diagnostics shown on the WebP / AVIF tab; toggle disabled if neither GD AVIF (PHP 8.1+) nor Imagick AVIF (7.0.25+) is available
- 🔧 **JavaScript optimisations module fully wired up** (previously a placeholder class). Defer, Defer jQuery, Move-to-Footer, Remove jQuery (with test mode), inline-JS minification, Delay JS with interaction runtime + configurable timeout, Disable Concatenation, Remove Script Versions all functional with their own exclusion lists
- 🔧 **CSS optimisations module fully wired up** (previously a placeholder class). Inline Critical CSS, Async CSS (preload + onload + loadCSS polyfill), inline-CSS minification, Conditional Block Styles, Remove CSS Versions, Disable Elementor Google Fonts, Disable WooCommerce CSS on non-shop pages
- 🔧 **Database scheduled cleanup now runs** (cron event was registered in earlier releases but had no listener). Auto-draft purge, trash emptying, spam / unapproved comment cleanup, expired transient cleanup (multisite-aware), and revision trimming all execute on schedule
- ✨ Minify HTML toggle on the Core tab
- ✨ Hover Prefetch toggle on the Preloading tab — instant.page v5.2.0 runtime (MIT)
- ✨ YouTube / Vimeo Facade on the Lazy Loading tab — embedded video iframes replaced with thumbnail + play button; real iframe only loads on click
- ✨ `decoding="async"` toggle in the Image Sizing & Dimensions section
- ✨ Strip EXIF Metadata on JPEG Upload toggle in the Image Sizing & Dimensions section
- 🐛 Fix: `crossorigin="anonymous"` is now explicit on all preload and preconnect tags
- 📦 Migration: four new option sections (`preloading`, `lazy_loading`, `third_party`, `server_headers`) seeded on upgrade. Idempotent — safe to run repeatedly
- 🧹 On deactivation: all v1.12.0 `.htaccess` marker blocks (*MBR AVIF*, *MBR Browser Cache*, *MBR Compression*) are removed cleanly. Third-party script refresh cron is unscheduled. AVIF file registry is purged

### 1.11.0
- ✨ **Orphaned Images tab renamed to Orphaned Media** — scope expanded to cover videos, audio, documents, and archives alongside images
- Type checkbox group in tab settings — opt in per type (Images / Videos / Audio / Documents / Archives); defaults to images-only on upgrade so v1.10.0 behaviour is preserved
- New Type column in the candidate list with category icons for quick visual scanning
- Behaviour change: `post_parent` is no longer treated as definitive proof an attachment is in use. WordPress sets `post_parent` on upload via the post editor and never clears it on content edits, so attachments uploaded into a post and later removed from content stayed hidden under v1.10.0. Parent-only matches now drop to Review tier so they can be inspected and deleted manually. Featured-image and post-content matches remain definitive

### 1.10.0
- ✨ **New "Orphaned Images" tab** — scan the Media Library for image attachments no longer referenced anywhere, with a safe two-stage deletion workflow and configurable restore window
- Two confidence tiers: **High** (no references found, eligible for bulk-delete) and **Review** (matched only in postmeta, manual inspection required)
- Custom staging table records the full attachment post row, postmeta, and file manifest before deletion — restore from queue within the configured window

### 1.9.3
- REST API namespace allowlist on the Core tab — when "Disable REST API" is set to a non-default mode, admins can whitelist specific namespaces that should remain accessible
- Fix: public REST endpoints registered with `permission_callback => '__return_true'` are no longer indiscriminately blocked when their namespace is in the allowlist

### 1.9.2
- Fix: "Remove Global Styles" no longer breaks the front end of Full Site Editing (block) themes — auto-skipped when a block theme is active
- Removed the duplicate, previously non-functional "Remove Global Styles" checkbox from the CSS tab; the Core tab toggle is now the canonical home
- Migration: any existing `[css][remove_global_styles]` truthy value is automatically copied to `[core][remove_global_styles]` on update

### 1.9.1
- Weekly automated cleanup toggle in the WooCommerce tab — runs expired sessions, WooCommerce transients, and Action Scheduler cleanup on the plugin's existing weekly cron hook
- Geolocation and page cache advisory notice — warns when WooCommerce's default customer location interacts badly with full-page caching
- Fix: the `mbr_wp_performance_database_cleanup` weekly cron event now has an actual listener

### 1.9.0
- New dedicated WooCommerce tab consolidating all store-specific optimisations
- Cart fragments control — disable the admin-ajax `get_refreshed_fragments` request site-wide or only on non-shop pages
- Expanded conditional asset loading — dequeues WooCommerce scripts, styles, block assets, selectWoo, blockUI, and related libraries on non-shop pages
- Configurable Action Scheduler retention period (default 30 days, options for 14, 7, or 3)

### 1.8.0
- Bulk resize tool for existing Media Library images — scan, then downscale in place
- Two-phase workflow (Scan → Start Resize) with progress bar, live log, and running savings total
- Automatic sub-size regeneration after each resize using the WordPress core pipeline

### 1.7.0
- New "Image Sizing & Dimensions" section in the WebP tab
- Automatic resize-on-upload with configurable maximum dimension (default 2560px)
- Automatic injection of missing `width` and `height` attributes on front-end images to reduce Cumulative Layout Shift

### 1.6.0
- Integrated WebP image conversion (previously the standalone MBR WebP Converter plugin)
- New "WebP" tab with settings, server diagnostics, and bulk converter
- Automatic WebP conversion on image upload
- HTML `<picture>` tag delivery with automatic browser fallback
- Apache / LiteSpeed `.htaccess` rewrite rules for transparent WebP serving
- Automatic migration of data from the standalone MBR WebP Converter plugin
- Redesigned admin UI with pill-style tab navigation and dark mode

### 1.5.0
- Full WordPress Multisite network support
- Network Admin settings page with one-click push to all sites
- Import settings from any site as network defaults
- Per-site override toggle for super admins

### 1.4.9
- Comprehensive lazy loading controls
- Preloading and speculative loading options
- Self-host Google Fonts with manual management
- CSS scanner for unused styles
- Toolbar menu access (moved from sidebar)
- Page builder compatibility (Elementor, Divi, etc.)

### 1.0.0
- Initial release

</details>

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

## 💚 Support the project

- 🐛 **Bug reports:** [GitHub Issues](https://github.com/harbourbob/mbr-wp-performance/issues)
- 🌐 **Website:** [littlewebshack.com](https://littlewebshack.com)
- 👤 **Author:** [madebyrobert.co.uk](https://madebyrobert.co.uk)
- ☕ **Buy me a coffee:** [buymeacoffee.com/robertpalmer](https://buymeacoffee.com/robertpalmer/)

---

## 📄 License

Licensed under the [GPL v2 or later](https://www.gnu.org/licenses/gpl-2.0.html).

<div align="center">

**100% free. No premium tiers. No upsells. No tracking. Forever.**

</div>
