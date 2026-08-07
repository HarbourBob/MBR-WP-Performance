<div align="center">

# ⚡ MBR Performance

### Comprehensive WordPress performance, granular control, zero tracking — free forever.

[![WordPress](https://img.shields.io/badge/WordPress-5.9%2B-blue.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-GPL%20v2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-1.22.1-orange.svg)](https://github.com/harbourbob/mbr-wp-performance/releases)
[![Buy Me A Coffee](https://img.shields.io/badge/Buy%20Me%20A%20Coffee-%E2%98%95-yellow.svg)](https://buymeacoffee.com/robertpalmer/)

**Fourteen tabs of individually-toggleable optimisations, a Performance Doctor that scans your site and tells you which settings it actually needs — and now Real User Monitoring, so the verdict comes from your actual visitors, not just a lab test.** Image conversion, Used CSS and render-blocking control, script-module preloading, font self-hosting, database cleanup, server-level caching, plus a diagnostics suite that catches conflicts before they bite. Every option is explained in plain language, nothing phones home, and there's no "pro" tier holding features back.

[**📥 Download Latest**](https://github.com/harbourbob/mbr-wp-performance/releases) &nbsp;·&nbsp; [**🐛 Report a Bug**](https://github.com/harbourbob/mbr-wp-performance/issues) &nbsp;·&nbsp; [**💡 Request a Feature**](https://github.com/harbourbob/mbr-wp-performance/issues) &nbsp;·&nbsp; [**📖 User Guide (PDF)**](https://littlewebshack.com/downloads/mbr-performance/MBR-Performance-User-Guide-v1.22.1.pdf)
<!-- ^ Update this URL once the v1.22.1 guide is uploaded to littlewebshack -->

</div>

---

## 🆕 What's new in 1.22.1

Two big capabilities have landed since 1.19.0 — **Real User Monitoring**, the one measurement in the plugin that comes from actual visitors rather than a lab test, and **Script Modules support**, which brings the plugin up to date with how WordPress 6.5+ loads JavaScript. This release joins them up: the Performance Doctor can now see the module layer it was previously blind to.

- 🩺 **The Doctor now sees script modules** *(1.22.1)*. It used to step over `type="module"` when counting render-blocking scripts — correct, since modules don't block rendering — and then say nothing further, so a classic theme full of Interactivity API code got a clean bill of health while every preload hint sat uselessly in the footer. The Doctor now counts the modules, reports where the import map and each hint actually landed, recommends hoisting when it would help, and flags the one case that's a genuine breakage rather than a slow page: a module printed in the head while the import map is printed in the footer, where its bare-specifier imports cannot resolve at all.
- 📡 **Real User Monitoring** *(1.21.0)*. Collects Core Web Vitals — LCP, CLS and **INP** — from real visitors and stores every reading **on your own server**. No third-party service, no cookies, no IP addresses; the measurement library is bundled and served from your own domain. A nightly job (plus on-demand roll-up whenever you open the tab) turns raw samples into per-template and per-URL p75s, a scorecard with good/needs-improvement/poor distribution, and a worst-offenders table that names the *element or handler* responsible — turning "your INP is 380ms" into "your INP is 380ms, and it's the menu toggle."
- 🩺 **The Doctor now leads with field data** *(1.21.x)*. When RUM has enough samples, the Doctor's recommendations open with what visitors actually experienced — and where field and synthetic disagree, field wins, and it says so. INP is the prize here: it only exists when a real person interacts, so no synthetic scan can ever measure it. Thin data is shown as provisional readings rather than acted on, and a passing site gets told so plainly.
- 🧩 **Script Modules & Interactivity API support** *(1.22.0)*. WordPress 6.5+ loads Interactivity-API code as ES modules, which the classic defer/delay/combine passes never touch — correctly, since modules defer by spec and combining them would break the import map. What the plugin adds is the piece core leaves on the table: on **classic themes**, WordPress prints all its `modulepreload` hints in the footer (it can't know the modules any earlier), where they're near-worthless. **Preload hoisting** learns each URL's module set on first visit and emits the hints in the head from then on, walking the static dependency graph and deliberately skipping dynamic imports. Block themes already get this right from core and are left alone. Complete no-op below WordPress 6.5.
- 🖨️ **PDF report polish** *(1.21.3)*. The Doctor's report now previews as a proper centred A4 sheet instead of stretching to the browser width, and prints with correct single margins.
- 🐛 **jQuery footer protection** *(1.20.1)*. "Move scripts to footer" no longer relocates the jQuery foundation — moving it could split the dependency graph and silently break jQuery-dependent widgets (Elementor accordions and friends). A new `mbrpe_footer_protected_handles` filter lets advanced users adjust the protected set.

[See the full changelog ↓](#-changelog)

---

## 🎯 At a glance

14 tabs, one toolbar menu, zero "Pro" tier:

- 📡 **Measured by real visitors** — self-hosted Real User Monitoring collects LCP, CLS and INP from actual traffic, entirely on your own server. The only way to see INP, which no synthetic test can measure
- 🩺 **Guided, not guesswork** — the Performance Doctor scans your site (and reads your field data) and tells you which settings to enable and which to skip, so you're not staring at a wall of toggles wondering where to start
- 🆓 **Free forever** — no premium gates, no upsells, no feature throttling
- 🔒 **Zero tracking** — never phones home, never sends analytics, never touches visitor data. Even RUM keeps every byte on your own server
- 🎛️ **Granular control** — toggle individual optimisations with clear explanations, not opaque "speed up my site" buttons
- 🌙 **Dark-mode admin** — lives in the WordPress toolbar, no sidebar clutter
- 🏗️ **Page-builder aware** — auto-disables inside Elementor, Bricks, Divi, Beaver Builder, Oxygen, WPBakery
- 🌐 **Multisite-ready** — full Network Admin with one-click push to all sites, plus per-site override control
- 🤝 **Plays nicely with caching plugins** — designed to complement WP Rocket, LiteSpeed, W3 Total Cache, FlyingPress, Autoptimize, Perfmatters. The built-in Conflict Detector shows exactly which options overlap with each
- 🧩 **WordPress 6.5–7.0 ready** — Script Modules / Interactivity API support, and the WordPress 7.0 AI-subsystem kill switch

[Detailed features ↓](#-detailed-features) &nbsp;·&nbsp; [Why this plugin ↓](#-why-this-plugin) &nbsp;·&nbsp; [Quick start ↓](#-quick-start)

---

## 🏆 PageSpeed Insights warnings this plugin addresses

| Warning | How this plugin handles it |
|---|---|
| **Serve images in next-gen formats** | WebP **and** AVIF conversion with `<picture>` delivery (AVIF first, then WebP, then original) |
| **Properly size images** | Resize-on-upload at a configurable maximum, plus a Bulk Resize tool for existing libraries |
| **Defer offscreen images** | Native + IntersectionObserver lazy loading, including background-image lazy loading |
| **Image elements do not have explicit width and height** | Auto-inject missing `width`/`height` attributes into front-end `<img>` tags |
| **Eliminate render-blocking resources** | Used CSS (Mode A — inline the critical CSS, defer the rest), Combine CSS, Async CSS, Defer JS, Delay JS until interaction |
| **Reduce JavaScript execution time** | Delay JS with an interaction-triggered runtime and configurable timeout |
| **Minimize third-party usage** | Delay JS for analytics/chat/tag managers, plus YouTube / Vimeo facades that hold back ~1.4MB of player JavaScript until click |
| **Serve static assets with an efficient cache policy** | Server tab writes Browser Cache Headers to `.htaccess` (1 year for images, 1 month for CSS / JS) |
| **Enable text compression** | Server tab writes Brotli / Gzip rules to `.htaccess`; Nginx hosts get a copy-ready snippet |
| **Minify HTML / CSS / JavaScript** | Output-buffered HTML minify (preserves `<pre>`, `<textarea>`, `<script>`, `<style>`, `<svg>`, IE conditionals), plus inline JS/CSS minification and JS/CSS combining |
| **Interaction to Next Paint (INP)** | The one no lab tool can see. RUM measures it from real visitors, names the offending handler, and the Doctor routes you to the fix |

---

## 🎛️ Features at a glance

| Tab | Focus |
|-----|-------|
| 🩺 **Doctor** | Scans a page — or your key templates in one click — and recommends, in priority order, which settings this site needs and which to leave off. Leads with real-user field data when RUM is on. Site-wide vs page-specific aggregation, branded print-ready PDF report |
| ⚙️ **Core Features** | WordPress-level toggles: emojis, embeds, REST API modes with namespace allowlist, Heartbeat, HTML minify, disable WordPress 7.0 AI |
| 📜 **JavaScript** | Defer, delay-until-interaction, move-to-footer (jQuery protected), minify, **Combine JS**, jQuery removal with test mode — plus Script Modules preload hoisting for WordPress 6.5+ |
| 🎨 **CSS** | Used CSS (Mode A), Combine CSS, async loading, inline-CSS minification, unused-style scanner, conditional block styles, critical CSS textarea |
| 🔤 **Fonts** | Self-hosted Google Fonts, preloading, subsetting, font-display, Font Awesome optimisation, hardcoded-font stripping, Disable Elementor Google Fonts |
| 🚀 **Preloading** | LCP image preload, fetch priority, Cloudflare Early Hints, speculative loading, hover prefetch |
| 🐢 **Lazy Loading** | Native image/iFrame lazy loading, YouTube / Vimeo facade, six kinds of exclusion rule |
| 🗄️ **Database** | Revisions, transients, orphaned metadata, table optimisation, scheduled cleanup with last-run log |
| 🖼️ **WebP / AVIF** | WebP **and** AVIF conversion, bulk converters, `<picture>` delivery — plus Image Sizing: resize large uploads, inject missing dimensions, `decoding="async"`, EXIF strip, bulk resize |
| 🖥️ **Server** | Browser cache headers, Brotli / Gzip compression, Nginx snippet for non-Apache hosts |
| 🔬 **Diagnostics** | Autoload audit, WP-Cron viewer with orphan detection, caching-plugin conflict detector |
| 📡 **RUM** | Real-user Core Web Vitals — scorecard, per-template breakdown, worst offenders with the culprit element named. 100% self-hosted, zero PII |
| 🗑️ **Orphaned Media** | Find and safely remove unreferenced images, videos, audio, documents, archives |
| 🛒 **WooCommerce** | Cart fragments, conditional asset loading, Action Scheduler retention |

> 🧹 **Clean uninstall:** All `.htaccess` marker blocks (*MBR AVIF*, *MBR Browser Cache*, *MBR Compression*) are removed cleanly on deactivation, and all scheduled crons (database cleanup, orphan purge, RUM aggregation) are unscheduled.

---

## 💚 Why this plugin?

There are good performance plugins on WordPress.org and elsewhere. Here's why this one might fit better than the alternative you're currently looking at:

**You want evidence, not just a lab score.** PageSpeed tests one page, once, from a data centre. RUM tells you what your slower quarter of real visitors actually experienced — per template, per device — and names the element or handler responsible. When you make a change, you get before-and-after numbers from real traffic, not a synthetic guess. And it does this without a third-party analytics service: every reading lives in your own database.

**You want everything in one place, but not on rails.** WP Rocket and similar are excellent if you want "click everything on and trust us." This plugin is for site owners who want to understand each toggle and choose. Every option has a plain-language explanation, every default is conservative, and every change is reversible.

**You already have a caching plugin and don't want to fight it.** The Diagnostics tab actively detects WP Rocket, LiteSpeed, W3 Total Cache, FlyingPress, WP Super Cache, Perfmatters, and Autoptimize, and tells you which MBR options overlap with each. There's deliberately no full-page caching here — this plugin complements caching plugins rather than replacing them.

**You care about privacy.** No telemetry, no remote pings, no third-party services. Even the Real User Monitoring — a category of feature that's almost always a SaaS subscription with your visitors' data as the product — is entirely self-hosted: bundled library, first-party endpoint, your database, no cookies, no IPs. Free isn't a loss-leader for an upsell — it's just the entire product.

**You like a long, honest changelog.** Each release tells you what changed, why, and what edge case prompted the fix. If something broke and got fixed, the entry says so plainly. You can read the changelog as a portrait of how the plugin actually evolves.

**You run unusual stacks.** Multisite, FSE themes, custom REST APIs, page builders, AMP, mixed PHP versions — the README and changelog call out each compatibility quirk explicitly so you can decide whether a feature is right for your site before flipping it on.

---

## 📦 Quick start

### Install via WordPress Admin

1. Download the [latest release](https://github.com/harbourbob/mbr-wp-performance/releases)
2. Go to **Plugins → Add New → Upload Plugin**
3. Upload the zip and click **Install Now**
4. Activate
5. Click **MBR Performance** in the admin toolbar

### Or manually

1. Extract the zip
2. Upload `mbr-performance/` to `/wp-content/plugins/`
3. Activate from the Plugins screen

### First-time setup, in a safe order

After activation, open **MBR Performance** from the admin toolbar.

1. 📡 **RUM tab** — switch on Real User Monitoring *first* and let it collect while you work through everything else. By the time you've finished, you'll have real field data to check your results against
2. 🔬 **Diagnostics tab** — check the Caching Plugin Conflicts panel; if any caching plugin is detected, decide who owns each optimisation before enabling anything below. Glance at the Autoloaded Options Audit and WP-Cron Viewer for red flags
3. 🩺 **Doctor tab** — run **Scan key templates** and let it tell you whether your render-blocking is CSS or JavaScript, and which image issues to fix. Act on its priority list rather than guessing
4. 🖼️ **WebP / AVIF tab** — check the server diagnostics, run the WebP bulk converter (and AVIF if supported); enable resize-on-upload, missing-dimension injection, `decoding="async"` and EXIF stripping
5. 📜 / 🎨 **Address render-blocking** — if the Doctor flagged CSS, enable Used CSS; if JavaScript, enable Defer and Delay. One at a time, testing between
6. 🔤 **Fonts tab** — self-host Google Fonts, set font-display to swap, preload only above-the-fold fonts
7. 🐢 **Lazy Loading tab** — lazy-load below-the-fold images and videos; exclude your logo and hero image
8. 🖥️ **Server tab** — enable Browser Cache Headers and Brotli / Gzip Compression, unless your host or CDN already provides them
9. 🗄️ **Database tab** — clean up accumulated bloat and set a sensible cleanup schedule
10. 🗑️ **Orphaned Media tab** *(optional)* — back up first, then scan for reclaimable disk space
11. 🛒 **WooCommerce tab** *(if applicable)* — disable cart fragments, dequeue shop assets on non-shop pages, cap Action Scheduler retention
12. 📡 **Back to RUM** — after a few days of real traffic, check the scorecard and re-run the Doctor to confirm real visitors are seeing the improvement

> 💡 Enable features one at a time and test after each change. Page builder editors are automatically detected and bypassed.

---

## 📋 Requirements

- WordPress **5.9** or higher (tested up to **7.0**); Script Modules features activate on **6.5+**
- PHP **7.4** or higher (**PHP 8.1+** with libavif for the GD AVIF path)
- MySQL **5.6** or higher
- GD library with WebP support for image conversion and resizing
- *(Optional)* Imagick 7.0.25+ with libheif, or GD with libavif compiled in — for AVIF conversion. Capability is auto-detected via `gd_info()['AVIF Support']`; the AVIF section is hidden in the UI if neither is present

---

## 📚 Detailed features

### 📡 Real User Monitoring
The feature that turns the plugin from a lab tool into a field instrument. A tiny beacon (built on Google's open-source `web-vitals` attribution library — bundled locally, never loaded from a CDN) reports each visitor's **LCP**, **CLS** and **INP** to a first-party REST endpoint, which stores them in your own database. Nothing leaves your server: no cookies, no IP storage, and the user agent is reduced to a coarse device class and browser family before anything is written.

A nightly job rolls raw samples into daily per-template and per-URL p75s, then purges the raw — and the roll-up also runs on demand whenever you open the tab, so fresh traffic shows up immediately. The panel gives you a **scorecard** (p75 per metric with the good/needs-improvement/poor distribution against the official thresholds), a **per-template breakdown** split by desktop and mobile — which tells you *where* the problem is, not just that one exists — and a **worst-offenders table** naming the specific URLs and the element or handler most often responsible. Attribution is the point: "your INP is 380ms" becomes "your INP is 380ms, and it's `.menu-toggle`."

Configurable sampling for high-traffic sites, logged-in sessions excluded by default, retention windows for both raw and aggregate data, and readings under ten samples are shown greyed as *provisional* rather than treated as fact. **Why INP matters:** it only exists when a real person interacts, so no synthetic tool — PageSpeed included — can measure it. If your site scores well in the lab but feels sluggish in the hand, this is the tab that shows you why.

### 🩺 MBR Performance Doctor
The advisor that answers "which of these settings does *my* site actually need?" Point it at a page (or hit **Scan key templates** to sample home, blog, a post, a page, and WooCommerce shop/product in one pass) and it analyses the rendered output: the render-blocking CSS-vs-JavaScript split, plus an image pass for missing dimensions, next-gen format candidates, and lazy-loading gaps. It produces a prioritised, plain-language worklist that links straight to the relevant setting and skips anything already enabled — and it's happy to tell you a fix *isn't* worth it.

**With RUM switched on, the Doctor leads with field data.** Real-user recommendations name the p75, name the culprit element or handler, and route to the right tab — INP problems to Delay JS, LCP to preloading, CLS to dimensions or fonts. Where field and synthetic disagree, field wins, and the Doctor says so plainly. Thin field data is reported as provisional readings rather than acted on, and if your Core Web Vitals are passing, it tells you that too — your real visitors' verdict, above the lab's.

**It also reads the module layer.** Script modules never enter the classic script queue, so the render-blocking pass steps over them by design — which used to mean a page full of Interactivity API code produced a clean report and no further comment. The Doctor now reports how many modules the page loads, where the import map landed, and how many `modulepreload` hints made it into the head, then tells you whether hoisting is off, still learning the URL, or working. Module counts are kept out of the render-blocking JavaScript figure — modules defer by spec and never block first paint, so counting them there would overstate the problem. And it flags the one module fault that's a breakage rather than a slowdown: a module in the head with the import map in the footer, where its bare-specifier imports can't resolve.

The multi-template scan rolls everything up into **site-wide vs page-specific** recommendations (field data appears once, site-wide, since a global metric isn't a property of one template), and a one-click **branded PDF report** — previewed as a proper A4 sheet, generated entirely in the browser — gives agencies a client-ready deliverable. Advisory only: the Doctor never changes a setting for you.

### ⚙️ Core Features
Disable WordPress defaults that don't earn their place — emojis, embeds, dashicons, jQuery Migrate, XML-RPC, RSS feeds, self-pingbacks, REST API links. Throttle the Heartbeat API, limit revisions, strip query strings. Three REST API access modes with a namespace allowlist — and the plugin's own `mbrpe/v1` namespace is always permitted, so the RUM beacon keeps working for logged-out visitors however hard you lock the API down. **Minify HTML** — output-buffered, with `<pre>` / `<textarea>` / `<script>` / `<style>` / `<svg>` and IE conditionals preserved, and nested-document pages skipped automatically. **Disable AI Features (WordPress 7.0+)** — switches off WordPress 7.0's built-in AI subsystem via core's own `wp_supports_ai` kill switch; inert on 6.x.

### 📜 JavaScript Optimisation
Defer or async script loading, defer jQuery specifically, move scripts to the footer (with the jQuery foundation protected from the move — relocating it could split the dependency graph and silently break page-builder widgets; adjustable via the `mbrpe_footer_protected_handles` filter), or remove jQuery entirely with a logged-out-only test mode. Minify inline JS, and delay analytics and chat widgets until the user actually interacts — with a configurable timeout so they still fire eventually. **Combine JavaScript** merges local scripts into cached head and footer bundles; for safety it only combines "pure" scripts — anything carrying inline or localised data (which routinely includes per-request nonces), an async/defer strategy, or a conditional stays separate, which is why it merges fewer files than Combine CSS. That's correct behaviour, not a fault.

**Script Modules & the Interactivity API (WordPress 6.5+).** Modules are printed by WordPress separately from ordinary scripts, so defer/delay/combine never touch them — deliberately, since modules defer by spec and combining them would destroy the import map. What the plugin adds is **preload hoisting**: on classic themes, WordPress discovers modules during body rendering and prints all its `modulepreload` hints in the footer, where they arrive at the same moment as the scripts they were meant to front-run. Hoisting learns each URL's module set on first visit and emits the hints in the head from the second visit on, walking the static dependency graph (dynamic imports deliberately excluded — they may never be needed) with a per-page cap and exclusion list. Optional high `fetchpriority` for nominated modules via the core API on 6.9+. Block themes already get head hints from core and are left alone; below 6.5 the whole feature is a no-op and the UI section explains why.

### 🎨 CSS Optimisation
**Used CSS (Mode A)** — the headline CSS feature. For each page it extracts the CSS actually used by that page's markup, inlines it into the `<head>`, and async-defers the original stylesheets as a safe fallback — no render-blocking CSS, no flash of unstyled content, and nothing ever deleted. Extraction runs through a bundled, self-hosted CSS parser (no external service, no API key), keeps custom-property, `:root`, and JS-toggled (`[aria-*]` / `[data-*]`) rules regardless of static matches, caches per URL, and coordinates with the page cache. **Combine CSS** merges runs of adjacent same-media local stylesheets into one cached bundle — automatically stood down while Used CSS is on, since Mode A already owns delivery; the Doctor recommends one or the other, never both. Plus async loading, inline-CSS minification, an unused-style scanner, conditional block styles, and a Critical CSS textarea with the async-CSS safety interlock.

### 🔤 Font Management
Self-host Google Fonts to eliminate render-blocking third-party requests (and improve GDPR posture). Preload critical fonts with explicit `crossorigin`, manage manual entries, enable subsetting, pick your `font-display` strategy, optimise or disable Font Awesome. **Disable Google Fonts** removes them site-wide — including fonts hardcoded straight into theme headers (`<link>` tags, preconnects, inline `@font-face` and `@import`) that bypass the enqueue system entirely, via a guarded final-output pass. Dedicated Elementor Google Fonts control included.

### 🚀 Preloading & Speculative Loading
Preload your LCP image so it lands fast. Configure fetch priority manually or auto-prioritise the first image in main content. Emit Cloudflare Early Hints (HTTP 103) for edge-level preloading. The Speculation Rules API prefetches or prerenders the next page with conservative, moderate, eager, or auto eagerness. **Hover Prefetch** uses the canonical instant.page runtime (MIT) and honours `Save-Data: on` so metered connections aren't penalised.

### 🐢 Lazy Loading
Native browser lazy loading for images and iFrames with configurable thresholds, background-image lazy loading via IntersectionObserver, and DOM monitoring for dynamically inserted images. Exclude by selector, class, ID, data attribute, filename keyword, or parent container — six ways to keep your hero loading early. **YouTube / Vimeo Facade** replaces embedded players with a thumbnail and play button — roughly 1.4MB of YouTube JavaScript held back until click, no provider cookies until interaction, keyboard accessible.

### 🗄️ Database Optimisation
Scheduled cleanup runs (daily / weekly / manual) covering auto-drafts, trash, spam and unapproved comments, expired transients (multisite-aware), and revision trimming — with a last-run log showing per-action counts and a **Run Auto-Cleanup Now** button. Plus orphaned-metadata scanners across posts / comments / terms / relationships, `OPTIMIZE TABLE`, MyISAM-to-InnoDB conversion, and table repair.

### 🖼️ WebP / AVIF Image Conversion & Image Sizing
Convert JPG, JPEG and PNG to WebP and AVIF with configurable quality, auto-convert on upload, and bulk converters with per-image AJAX progress. Delivery via `<picture>` (AVIF first, WebP second, original fallback) or `.htaccess` rewrite. Originals never modified; skip-when-larger detection; unified conversion history with per-format sizes and a registry-driven Revert All. Server capability diagnostics use `gd_info()['AVIF Support']` — the reliable detection — so the AVIF tools only appear when your host can actually encode it.

Image Sizing in the same tab: resize-on-upload at a configurable maximum, missing `width`/`height` injection (kills CLS), a Bulk Resize tool for existing libraries with sub-size regeneration, `decoding="async"` (LCP candidate automatically skipped), and EXIF stripping on new JPEG uploads with ICC profiles preserved.

### 🖥️ Server
Writes browser-cache and compression rules to `.htaccess` on Apache and LiteSpeed — 1 year for images/fonts, 1 month for CSS/JS, `immutable` belt-and-braces, Brotli preferred with Gzip fallback. Nginx and IIS hosts get a copy-ready snippet instead. Marker blocks removed cleanly on disable or deactivation.

### 🔬 Diagnostics
- **Caching Plugin Conflicts** — detects WP Rocket, W3 Total Cache, LiteSpeed Cache, FlyingPress, WP Super Cache, Perfmatters, and Autoptimize, and lists exactly which MBR options overlap with each
- **Autoloaded Options Audit** — total autoloaded bytes plus the top 30 options by size, one-click autoload disable, ~85 protected core options, transients flagged
- **WP-Cron Viewer** — every scheduled event with next-run, recurrence, and whether a callback is actually registered; orphan events flagged and unschedulable in one click

### 🗑️ Orphaned Media
Find attachments no longer referenced anywhere — images, videos, audio, documents, archives. Two confidence tiers (high-confidence for bulk delete, review tier for manual inspection), pre-deletion re-verification, a staging table with a configurable restore window, and deletion that handles sub-sizes and `.webp`/`.avif` siblings together.

### 🛒 WooCommerce Optimisations
Cart fragments control (often the single biggest TTFB win on cached stores), conditional asset loading on non-shop pages, wc-admin bundle suppression on unrelated admin screens, configurable Action Scheduler retention, weekly automated cleanup, and a geolocation-vs-page-cache advisory.

### 🌐 Multisite Network Support
Network-activate and manage defaults from the Network Admin. Push settings to all (or selected) sites in one click, import settings from any site as the network default, control per-site overrides, and new sites inherit network defaults automatically.

---

## 🏗️ Page builder compatibility

Optimisations are auto-disabled inside:

- Elementor (editor and preview)
- Beaver Builder
- Divi Builder
- Oxygen Builder
- Bricks Builder
- WPBakery Page Builder

No configuration needed. Upload-pipeline modules (WebP, AVIF, EXIF strip, resize) still run regardless of editor context, so images uploaded through Elementor's media picker get the same treatment as direct Media Library uploads.

---

## ❓ FAQ

<details>
<summary><strong>Will it break my site?</strong></summary>

It's designed to be safe, but always back up first, test on staging, and enable features one at a time. Each option is independently toggleable — anything you enable, you can disable. The **Diagnostics tab** detects active caching plugins and shows which MBR options overlap with each, which catches the most common cause of breakages when stacking performance plugins.
</details>

<details>
<summary><strong>Does RUM send my visitors' data anywhere?</strong></summary>

No. The measurement library is bundled with the plugin and served from your own domain, and every reading is posted to an endpoint on your own site and stored in your own database. No cookies are set, no IP addresses are stored, and the user agent is reduced to a coarse device class and browser family before anything is written. There is no external service involved at all — which also means no account to create and no subscription to pay.
</details>

<details>
<summary><strong>I enabled RUM but the tab shows no data.</strong></summary>

Three things to check. First, logged-in visits are excluded by default — browse your site logged out or in a private window. Second, readings appear once they've been rolled up, and opening the RUM tab triggers that roll-up for you, so a reload is usually enough. Third, below ten samples a metric is marked *provisional* rather than treated as a measurement. Give it real traffic for a few days.
</details>

<details>
<summary><strong>Why should I care about INP when PageSpeed already gives me a score?</strong></summary>

Because PageSpeed can't see it. INP — Interaction to Next Paint — only exists when a real person taps, clicks or types, so no synthetic test can measure it, however sophisticated. It's also the metric that best matches "this site feels sluggish." If your lab scores are green but visitors complain, INP is usually the answer, and RUM is the only way to find it — complete with the specific handler responsible.
</details>

<details>
<summary><strong>Do the defer/delay/combine settings affect Script Modules?</strong></summary>

No, and they shouldn't. Modules used by the Interactivity API are printed separately by WordPress and defer by specification; combining them would break the import map that resolves their imports. The plugin leaves them strictly alone — there's not even an exclusion list to maintain, because the classic passes structurally can't reach them. The one module setting that *does* something is preload hoisting, and only on classic themes (block themes already get head preloads from core).
</details>

<details>
<summary><strong>I enabled module preload hoisting but nothing appeared in the head.</strong></summary>

Hoisting learns before it acts: the first visit to a URL records its modules, and the hints appear from the second visit onwards. It's also a deliberate no-op on block themes and on WordPress below 6.5. If you've recently changed theme or plugins, press **Clear learned modules** so the set is relearned rather than pointing at files that have moved.
</details>

<details>
<summary><strong>Does it work with my caching plugin?</strong></summary>

Yes — and the Diagnostics tab has a built-in conflict detector specifically for this. There's deliberately no overlap with full-page caching; this plugin provides complementary optimisations alongside WP Rocket, LiteSpeed, W3 Total Cache, FlyingPress, WP Super Cache, Perfmatters, Autoptimize, and others. The RUM beacon is POST-only, so page caches never interfere with it either.
</details>

<details>
<summary><strong>Combine JavaScript merged far fewer files than Combine CSS. Is it broken?</strong></summary>

No — that's correct behaviour. CSS can be merged freely, but a script carrying inline data (which often includes per-request security nonces) can't be safely baked into a shared cached file, so it stays separate. You still get the main benefit — jQuery and the cluster of vanilla libraries folded together — just with more standalone files on the JS side. If a combined script misbehaves, add it to the Exclude list.
</details>

<details>
<summary><strong>What's the difference between WebP and AVIF?</strong></summary>

Both are next-generation image formats designed to replace JPEG and PNG. AVIF is typically 20–30% smaller than WebP at equivalent perceived quality, but needs more recent browser support and stricter server-side encoding (PHP 8.1+ with libavif, or Imagick 7.0.25+). Enable both — the `<picture>` wrapper sends AVIF to capable browsers, WebP to the next tier, and the original as fallback.
</details>

<details>
<summary><strong>Does it touch my original images?</strong></summary>

Never. WebP and AVIF files are parallel files. Resize-on-upload uses the WordPress core scaling pipeline. EXIF stripping only affects new JPEG uploads. Only the **Bulk Resize tool** and **Orphaned Media deletion** modify or remove files on disk — both are clearly flagged as destructive and require manual confirmation.
</details>

<details>
<summary><strong>Is it safe to bulk-delete orphaned media?</strong></summary>

Bulk deletion is restricted to **high-confidence** orphans (no references found in any check). Review-tier candidates must be deleted individually after manual inspection. Deletions stage to a restore queue for the configured window, so you can reinstate the database record if you change your mind — though file bytes are removed at deletion time and require a backup to recover.
</details>

<details>
<summary><strong>What does "Disable AI Features (WordPress 7.0+)" actually do?</strong></summary>

WordPress 7.0 ships a built-in AI subsystem — an AI Client, the Abilities API, and a Settings → Connectors screen. It stays dormant until a provider is configured. This toggle switches the whole subsystem off via core's own kill switch (`wp_supports_ai` → `__return_false`), is off by default, and has no effect on WordPress 6.x, so it's safe across mixed-version sites.
</details>

---

## 📝 Changelog

### 1.22.1
- 🩺 **New: the Performance Doctor understands script modules.** Until now it skipped them entirely — it stepped over `type="module"` when counting render-blocking scripts (correctly; modules don't block rendering) and then had nothing more to say. On a classic theme loading Interactivity API code it would report a clean bill of health while every preload hint sat uselessly in the footer. It now counts the modules on the page, reports where the import map and each hint actually landed, and recommends hoisting when it would help
- ⚠️ **New: import map ordering check.** An import map must be parsed before any module that depends on it. If a theme or plugin prints its own module tag into the head while WordPress prints the map in the footer, every bare-specifier import in that module fails to resolve — a genuine breakage, not a slow page. Flagged as a high-priority finding
- 🔍 The Doctor now distinguishes hoisting **off**, hoisting **on but still learning this URL**, and hoisting **working** — so "I switched it on and nothing happened" is answered on screen. Block themes are told plainly that core already handles it
- 📊 The Doctor's summary card reports module counts, preload hint placement and import map position alongside the CSS, JS and image figures
- ℹ️ Module counts stay **separate** from the render-blocking JS figure. Modules defer by spec and never block first paint, so counting them as render-blocking would overstate the problem the rest of the report describes
- ♻️ The learned module map is keyed on plugin version, so upgrading clears it by design — the first visit to each URL relearns, and hoisted hints resume from the second

### 1.22.0
- 🧩 **New: Script Modules & Interactivity API support (WordPress 6.5+).** Modules are printed by WordPress separately from ordinary scripts, so the classic defer/delay/combine passes never see them — correctly, but it meant the plugin had nothing to offer module-using pages. This release adds the piece core leaves on the table
- ✨ **New: module preload hoisting.** On classic themes WordPress discovers modules during body rendering, so it prints every `modulepreload` hint in the footer — where a hint arrives at the same moment as the script it was meant to front-run. The plugin now learns each URL's module set on first visit and emits the hints in the head from then on. Block themes already receive head hints from core and are left untouched
- ✨ Static dependency-graph walking (a module's imports are hinted too; dynamic imports deliberately excluded), optional high `fetchpriority` via the core 6.9+ API, per-page preload cap, exclusion list, and a Clear learned modules control
- ℹ️ Preload URLs are resolved exactly as core resolves them — including the `version: null` case — because a mismatched preload URL makes the browser download a module twice. Off by default; complete no-op below WordPress 6.5

### 1.21.3
- 🖨️ **Fix: the Doctor's PDF report preview** no longer stretches to the full browser width — it now previews as a centred A4 sheet on a grey backdrop and resets cleanly for printing, so `@page` margins aren't doubled
- 🎨 Fix: "Note" badge styling in reports; recommendation buttons now use proper tab names ("Open RUM settings", not "Open rum settings")

### 1.21.2
- 🐛 **Fix: real-user field data now appears in the Doctor's site-scan view.** The site roll-up strips info-tier notes by design, which silently discarded the RUM status note — a scan could report "no actionable recommendations" while RUM sat on real data. Field data is now attached once at the site level, after that filter
- 🔧 Field data reported once site-wide rather than repeated under every template (a global metric isn't a property of one template), and provisional notes now show the actual readings

### 1.21.1
- 🐛 **Fix: RUM data now reaches the Doctor and scorecard immediately** rather than waiting up to 24 hours for the nightly cron — aggregation also runs on demand (throttled) whenever the RUM tab or the Doctor is opened, plus a manual "Run aggregation now" button
- 🔧 The Doctor is never silent when RUM is enabled: it reports still-collecting, provisional, or passing status instead of an ambiguous blank. Sample threshold lowered from 20 to 10, with provisional p75s shown greyed rather than hidden

### 1.21.0
- 📡 **New: Real User Monitoring.** Collects real-user Core Web Vitals — LCP, CLS and INP — into two local tables via a first-party REST endpoint (POST-only, so page caches never touch it) and a tiny beacon built on the bundled `web-vitals` attribution library (Apache-2.0, never loaded from a CDN). Nightly aggregation into per-template and per-URL daily p75s with automatic raw purge and aggregate retention
- ✨ **New: RUM tab** — Core Web Vitals scorecard with distribution bars, per-template breakdown by device, worst-offenders table naming the culprit element or handler, data-health row, Clear control
- 🩺 **New: Doctor field integration** — recommendations lead with what real visitors experienced, including INP, which a synthetic scan cannot see at all
- 🔒 **Privacy:** no cookies, no IP storage, no UA retention (reduced to device class + browser family at write time), configurable sampling, logged-in sessions excluded by default. Nothing leaves your server
- 🔧 The plugin's own `mbrpe/v1` REST namespace is now always permitted through the Core tab's REST hardening, so the beacon keeps working for logged-out visitors even under "Disable When Logged Out"

### 1.20.1
- 🐛 **Fix: "Move scripts to footer" no longer relocates the jQuery foundation** (jquery, jquery-core, jquery-migrate). Moving these split the dependency graph — jquery-migrate could be left in the head while jquery-core dropped to the footer, producing "jQuery is not defined" and silently breaking jQuery-dependent widgets (Elementor accordions, ElementsKit, and other page-builder handlers). jQuery now always stays in the head; a new `mbrpe_footer_protected_handles` filter lets advanced users adjust the protected set

### 1.19.0
- 🩺 **New: MBR Performance Doctor.** Analyses a real front-end page and recommends, in priority order, which settings will actually help this site — including which to leave **off**. Diagnoses the render-blocking CSS-vs-JavaScript split, links each recommendation straight to the relevant setting, skips anything already enabled. Advisory only
- ✨ Doctor image pass (missing dimensions → CLS, next-gen candidates, lazy-loading gaps), multi-template scan with site-wide vs page-specific aggregation, branded client-side PDF report, and a first-run nudge
- 🐛 Fix: Disable Google Fonts now also strips hardcoded fonts (theme-header `<link>` tags, preconnects, inline `@font-face` and `@import`) via a guarded final-output pass
- 🔧 Combine CSS automatically stands down while Used CSS (Mode A) is active — they're alternatives, and the Doctor recommends one or the other, never both

### 1.18.0
- ✨ **New: Used CSS (Mode A).** Extracts the CSS each page actually uses, inlines it into the `<head>`, and async-defers the original stylesheets as a safe fallback — eliminating render-blocking CSS without a flash of unstyled content. Fully self-hosted (bundled MIT CSS parser, no external service), cached per URL, page-cache coordinated

<details>
<summary><strong>Earlier releases (1.13.x and below)</strong></summary>

### 1.13.9
- 🎨 Fix (UI): Conversion History "Compression" column widened so the header sits on one line

### 1.13.8
- ✨ New: Bulk AVIF Converter alongside the WebP one, with per-image AJAX progress, its own history and registry-driven Revert All, and a unified Conversion History table showing both WebP and AVIF sizes

### 1.13.7
- 🐛 Fix: AVIF capability detection now uses `gd_info()['AVIF Support']` instead of `function_exists('imageavif')`, which returns true on PHP 8.1+ even when libgd was built without libavif — the cause of "AVIF enabled but no .avif files ever appear"

### 1.13.6
- 🎨 Fix (UI contrast): inline AJAX status notices now use the plugin's light text colour on its dark-themed backgrounds

### 1.13.5
- 🐛 Fix (root cause of "CSS toggles revert"): three Fonts-tab fields were scoped to the `[css]` section, so saving the Fonts tab replaced the stored CSS section with a partial one, wiping every CSS boolean. Fields re-scoped to `[fonts]`, dead UI removed, idempotent migration included

### 1.13.4
- 🧹 Removed the regex-based Auto-Generate Critical CSS button (structurally unsafe); the Critical CSS textarea remains for output from proper viewport-aware tools
- ✨ New async-CSS safety interlock: without a critical-CSS bridge, the first two stylesheets stay render-blocking to prevent FOUC

### 1.13.3
- 🐛 Fixes: Critical CSS persistence, Reset to Defaults (now a nonce-checked POST), Autoload Audit excluding the plugin's own options as documented

### 1.13.2
- 🐛 Fix (HTML minify): pages embedding a complete nested HTML document are now skipped byte-for-byte

### 1.13.0
- ✨ New "Disable AI Features (WordPress 7.0+)" toggle using core's own `wp_supports_ai` kill switch. Tested up to WordPress 7.0

### 1.12.x
- ✨ Server, Diagnostics tabs; AVIF conversion; JavaScript and CSS modules fully wired; scheduled database cleanup live; HTML minify; Hover Prefetch; YouTube/Vimeo facade; `decoding="async"`; EXIF stripping — plus the fixes that hardened them (placeholder-safe minify, Elementor upload pipeline, orphaned-media extension matching)

### 1.11.0 and earlier
- Orphaned Media (expanded from Orphaned Images), REST namespace allowlist, block-theme global-styles safety, WooCommerce tab, bulk resize, image sizing, WebP integration, multisite support, initial release

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

Bundled libraries: [web-vitals](https://github.com/GoogleChrome/web-vitals) (Apache-2.0, GPL-compatible) for Real User Monitoring — vendored locally, never loaded from a CDN.

<div align="center">

**100% free. No premium tiers. No upsells. No tracking. Forever.**

</div>
