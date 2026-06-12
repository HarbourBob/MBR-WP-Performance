=== MBR Performance ===
Contributors: robinmorgan2059
Tags: performance, optimization, speed, cache, database, webp, image
Requires at least: 5.9
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.17.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Comprehensive WordPress performance optimization plugin with controls for core features, JavaScript, CSS, fonts, lazy loading, preloading, database optimization, WebP image conversion, automatic image sizing, orphaned media cleanup, and WooCommerce optimisations.

== Description ==

MBR Performance is a powerful, all-in-one performance optimization plugin that gives you complete control over your WordPress site's performance.

= Features =

**Core Features**
* Disable unnecessary WordPress features (emojis, embeds, dashicons, etc.)
* Control REST API access
* Manage heartbeat, revisions, and autosave
* Remove query strings for better caching
* Minify HTML output (with automatic skip for embedded/nested documents)
* WooCommerce script optimization
* XML-RPC and RSS feed control
* Disable the WordPress 7.0 AI Client, Abilities API and Connectors

**JavaScript Optimization**
* Defer and async JavaScript loading
* Move scripts to footer
* jQuery optimization and removal options
* Minify and combine JavaScript files
* Delayed script execution for analytics
* Remove script versions

**CSS Optimization**
* Async CSS loading
* Minify and combine CSS files
* Preload combined CSS for earlier fetch (skipped when Async CSS is on)
* CSS scanner for unused styles
* Google Fonts optimization and combining
* Conditional block styles loading
* Remove global styles and CSS versions

**Font Optimization**
* Preload critical fonts
* Self-host Google Fonts with auto-download
* Manual font management
* Font subsetting for reduced file sizes
* Preconnect to font domains
* Font Awesome optimization
* Font display strategies (swap, block, fallback, optional)
* Disable Google Fonts completely
* Elementor Google Fonts control
* Clear font cache functionality

**Lazy Loading**
* Native lazy loading for images
* Lazy load iFrames and embedded videos (YouTube, Vimeo, etc.)
* Exclude specific images from lazy loading by:
  - CSS selectors
  - Class names and IDs
  - Data attributes
  - Keywords in src or class
  - Parent element selectors
* Smart exclusions to prevent breaking critical images

**Preloading & Speculative Loading**
* Preload critical images (LCP, hero images)
* Cloudflare Early Hints support (HTTP 103)
* Fetch Priority optimization
  - Automatic high priority for first image
  - Custom selectors for critical images
  - Disable core WordPress fetch priority
* Speculative Loading for faster navigation
  - Prefetch mode (fetch next page resources)
  - Prerender mode (fully render next page in background)
  - Configurable eagerness levels (conservative, moderate, eager)
  - Auto mode for optimal performance

**Database Optimization**
* Post revision cleanup with configurable limits
* Auto-delete old drafts and trash
* Spam comment removal
* Orphaned metadata cleanup (posts, comments, terms, relationships)
* Transient management and cleanup
* Database table optimization
* Convert MyISAM tables to InnoDB
* Table repair functionality
* Scheduled automatic cleanups

**WebP Image Conversion**
* Convert JPG, JPEG, and PNG images to WebP format
* Automatic conversion on upload
* Bulk converter for existing Media Library images
* Configurable compression level (1–100)
* HTML <picture> tag delivery with automatic fallback
* Apache/LiteSpeed .htaccess rewrite rules
* Gutenberg and Elementor integration
* Server diagnostics panel
* Conversion history with bulk management
* Smart skip when WebP would be larger than original

**AVIF Image Conversion**
* Convert JPG, JPEG, and PNG images to AVIF — typically 20–30% smaller than WebP at equivalent perceived quality
* Automatic conversion on upload, plus a Bulk AVIF Converter for existing Media Library images
* Delivered through the same HTML <picture> wrapper: AVIF first, then WebP, then the original JPEG/PNG fallback — each browser picks the first format it supports
* Reliable server capability detection via gd_info()['AVIF Support'] (or Imagick), so the AVIF converter only appears when the host can actually encode it — never enabled to no effect
* Conversion history with separate WebP and AVIF size columns, and a registry-driven Revert All that removes only the .avif files this plugin created, leaving originals and WebP variants untouched

**Image Sizing & Dimensions**
* Automatically resize oversized uploads to a configurable maximum dimension (default 2560px) to help fix the "Properly size images" PageSpeed Insights warning
* Preserves aspect ratio using the WordPress core scaling pipeline
* Adds missing width and height attributes to front-end images to help fix the "Ensure images have explicit width and height" warning
* Reduces Cumulative Layout Shift (CLS) by giving browsers aspect ratios up front
* Works on post content, Gutenberg blocks, Elementor widgets, attachment images and post thumbnails
* Dimension lookups cached per URL for a week (in-memory + transient) to keep the filter cheap
* Skips external images, SVGs and data URIs — only local files are measured
* Bulk resize tool for existing Media Library images — scan first, then downscale in place with progress bar and live log
* Automatic sub-size regeneration and stale WebP cleanup after each bulk resize

**Orphaned Media Cleanup**
* Scans the Media Library for attachments no longer referenced anywhere on the site — covering images, videos, audio, documents, and archives (configurable per scan)
* Detection covers post parents, featured images, post content (matching by attachment ID, shortcode reference and filename stem so sized variants and URL-only references are caught), and a string-search across postmeta values
* Two-tier confidence classifier — high-confidence orphans are eligible for bulk-delete, review-tier candidates require manual inspection
* Configurable restore window (7, 14, 30 or 60 days, or "keep forever") with a daily cron purge of expired records
* Staging table records the full attachment post row, postmeta and file manifest before deletion — database records can be restored within the configured window
* Per-attachment exclusions list to permanently keep specific IDs off the orphan list
* For images: deletes the original file, all WordPress sub-size variants, the "scaled" full-size variant and matching `.webp` siblings; for other media types, removes the single attached file
* Pre-deletion re-verification blocks the action if an attachment has become referenced since the last scan
* Live progress bar during scans, batched in 50-attachment chunks to avoid timeouts on large libraries
* Defaults to images-only on upgrade from v1.10.0 — broader media types are opt-in via settings checkboxes

**WooCommerce Optimisations**
* Dedicated tab that only activates when WooCommerce is installed
* Cart fragments control — disable the admin-ajax request that fires on every page load site-wide or only on non-shop pages
* Expanded conditional asset loading for WooCommerce scripts, styles, block assets, selectWoo and blockUI
* Disable the zxcvbn password strength meter on the frontend
* Disable marketplace suggestions and WooCommerce dashboard widgets
* Prevent heavy wc-admin React bundles from loading on non-WooCommerce admin pages
* Configurable Action Scheduler retention period to stop `actionscheduler_actions` ballooning on busy stores
* One-click cleanup for expired WooCommerce sessions and product/order/expired transients
* Full backward compatibility with the previous WooCommerce script and style toggles

**Multisite Network Support**
* Network-wide activation and deactivation
* Network default settings managed from the Network Admin
* Push settings to all sites (or selected sites) in one click
* Import settings from any existing site as network defaults
* Per-site override control — super admins can lock or unlock site-level customisation
* Automatic setup for newly-created sites using network defaults

== Installation ==

1. Upload the `mbr-performance` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Access settings via 'MBR Performance' in the WordPress admin toolbar
4. Configure features one tab at a time

== Frequently Asked Questions ==

= Will this plugin break my site? =

The plugin is designed to be safe, but we recommend:
1. Taking a full backup before using
2. Testing features on a staging site first
3. Enabling features one at a time
4. Testing thoroughly after each change

= Can I use this with a caching plugin? =

Yes. This plugin provides complementary optimisations and deliberately does no page caching of its own. Where a feature overlaps with a caching/optimisation plugin you already run — for example Combine or Minify CSS/JS in WP Rocket, LiteSpeed Cache, Autoptimize, W3 Total Cache, FlyingPress or SiteGround Optimizer — a built-in Conflict Detector flags the specific overlapping toggles on the settings screen, so you can avoid running the same combine/minify pass on both sides.

= Why does Combine JavaScript merge fewer files than Combine CSS? =

By design, and it's working correctly. Combine CSS can safely merge any local stylesheet, but Combine JavaScript only merges "pure" scripts — ones with no inline or localised data attached. Many WordPress scripts ship a small block of configuration alongside them (via wp_localize_script or wp_add_inline_script), and that data frequently contains per-request, per-user security nonces. Baking those into a shared, cached file would be both fragile and unsafe, so any script carrying inline data is left on its own and breaks the combine run around it. The upshot is fewer merged files on the JS side than on the CSS side — that's expected. You'll still get the biggest win (jQuery and the cluster of vanilla libraries folded together). Scripts in your Defer or Delay lists are also left alone so those features keep working.

= Does this work with page builders? =

Yes, the plugin is fully compatible with Elementor, Beaver Builder, Divi, Oxygen, Bricks, and WPBakery. Optimizations are automatically disabled in editor/preview modes.

= Does this work with WordPress Multisite? =

Yes! From v1.5.0 onwards the plugin fully supports WordPress Multisite networks. You can network-activate the plugin and manage default settings from the Network Admin (Settings > MBR Performance). You can push those defaults to all sites at once, import settings from any existing site, and choose whether individual site admins are allowed to override the network defaults.

= How do I access the settings? =

Click 'MBR Performance' in the WordPress admin toolbar at the top of the screen. You can also access individual tabs from the dropdown menu.

= What's the difference between Lazy Loading and Preloading? =

Lazy Loading delays loading of images/videos until they're needed (saving bandwidth), while Preloading loads critical resources early (improving perceived speed). They work together for optimal performance.

= Does this disable WordPress 7.0's built-in AI? =

It can. WordPress 7.0 added a core AI Client, the Abilities API and a Settings -> Connectors screen. They stay dormant until you connect an AI provider, so they don't slow a default site down — but if you'd rather the subsystem never loaded at all, tick "Disable AI Features (WordPress 7.0+)" on the Core tab. It uses WordPress's own wp_supports_ai kill switch and has no effect on WordPress 6.x.

== External services ==

This plugin can connect to the third-party services listed below. Each is optional and is only contacted when you enable the relevant feature; with these features off, the plugin makes no external requests.

= Google Fonts (Self-host Google Fonts feature) =

When you enable "Self-host Google Fonts" and download a font from the Fonts tab, your server contacts the Google Fonts API at fonts.googleapis.com to retrieve the font's stylesheet, and Google's font CDN at fonts.gstatic.com to download the font files. The files are then stored on your own server and served locally, so your visitors' browsers do not contact Google. The request is made by your server only at the moment you trigger a download in the admin area. It sends the requested font family name(s) and the information present in any standard HTTP request (such as your server's IP address and user agent); no website-visitor data is sent.

This service is provided by Google. Google Terms of Service: https://policies.google.com/terms — Google Privacy Policy: https://policies.google.com/privacy — Google Fonts privacy details: https://developers.google.com/fonts/faq/privacy

= YouTube (Video facade feature) =

When the video facade is enabled and a page contains an embedded YouTube video, the plugin shows a lightweight placeholder instead of the full embed. To display a preview image, the visitor's browser loads the video's thumbnail from YouTube's image server at i.ytimg.com. The full YouTube player, and any YouTube cookies, are loaded only if the visitor clicks to play. The thumbnail request is made by the visitor's browser and includes the visitor's IP address and the YouTube video ID.

This service is provided by Google/YouTube. YouTube Terms of Service: https://www.youtube.com/t/terms — Google Privacy Policy: https://policies.google.com/privacy

= Vimeo (Video facade feature) =

When the video facade is enabled and a page contains an embedded Vimeo video, the plugin shows a lightweight placeholder instead of the full embed. To display a preview image, the visitor's browser requests the video's public metadata from Vimeo's API at vimeo.com/api/v2/video/{id}.json, which returns the thumbnail URL. The full Vimeo player is loaded only if the visitor clicks to play. The request is made by the visitor's browser and includes the visitor's IP address and the Vimeo video ID.

This service is provided by Vimeo. Vimeo Terms of Service: https://vimeo.com/terms — Vimeo Privacy Policy: https://vimeo.com/privacy

== Changelog ==

= 1.17.0 =
* Added: Combine CSS is now fully implemented. With the toggle on, the plugin walks the stylesheet queue in print order and merges contiguous runs of adjacent, same-media, same-origin local stylesheets into a single cached bundle under /uploads/mbr-performance-combine/, cutting HTTP requests. Cascade order is preserved exactly: external/CDN, conditional, alternate, print/media-query and excluded stylesheets break the run and are left untouched. Relative `url()` and `@import` targets are rewritten to absolute against each source sheet's own directory, `@charset` is de-duplicated, inline styles attached via wp_add_inline_style are carried across, and RTL is handled. Bundles are fingerprinted on file contents/versions and rebuilt only when something changes; the cache is purged on settings save, reset and deactivation. If Minify CSS is also enabled, the bundle is minified with a string/url()-safe pass. A path-traversal guard ensures only files inside the site root are ever read. Off by default.
* Added: Combine JavaScript is now fully implemented, using the same queue-level, position-preserving approach, processed per group (head and footer handled separately so late-enqueued footer scripts are still caught). For safety it combines only "pure" scripts — any script carrying inline or localised data (which routinely includes per-request nonces), an async/core-defer load strategy, or a conditional, breaks the run and is left alone; files are joined with a newline+semicolon to prevent automatic-semicolon-insertion fusion. This is deliberately more conservative than Combine CSS, so expect fewer files to merge on the JS side — that is correct behaviour, not a fault (see FAQ). Scripts in the Defer, Delay and Exclude lists are all respected so combine can never quietly undo those features. The JS bundle is not minified (vendor scripts are typically pre-minified, and regex minification of arbitrary JavaScript is unsafe). Off by default.
* Added: the Caching Plugin Conflict Detector now flags Combine CSS and Combine JS overlaps with WP Rocket, LiteSpeed Cache, Autoptimize, W3 Total Cache and FlyingPress, and a new SiteGround Optimizer entry has been added to the catalogue (covering its Combine, Minify, Defer, Minify HTML, WebP, Browser Cache and GZIP overlaps).
* Added: a "Preload Combined CSS" option (CSS tab). When on, the plugin emits an early `<link rel="preload" as="style">` hint in the head for each combined bundle so the browser starts fetching it sooner. It only applies when Combine CSS is on and is automatically skipped when Async CSS is enabled (which already preloads).
* Added: each tab now shows how many combined files are currently cached (with total size) and a one-click "Clear combined cache" button — CSS bundles on the CSS tab, JS bundles on the JavaScript tab. Bundles still rebuild automatically when settings or assets change; this is just a manual flush.
* Improved: the caching-plugin conflict notice is now dismissible. Dismissing it hides it per-user until the overlap actually changes (a new conflicting plugin, or a newly-overlapping option brings it back), and it no longer appears on the Diagnostics tab, which already lists the same conflicts in a permanent panel.
* Note: a known limitation shared by all JS combiners — a library that locates its own workers or chunks via document.currentScript.src will see the bundle URL instead of its original path. If a script behaves oddly once combined, add it to the Exclude list (same as you would a cookie-consent or chat-widget script).

= 1.16.0 =
* Added: Minify HTML returns to the Core Features tab (Advanced Performance section), rebuilt with every hardening fix from the 1.13.x line included from day one: collision-free alphanumeric placeholder tokens (never HTML comments), exact preservation of `script` / `style` / `pre` / `textarea` / inline `svg` blocks and IE conditional comments, conservative whitespace collapsing (only runs spanning a newline), automatic skip of pages embedding a nested complete HTML document (e.g. a full landing page inside a page-builder HTML widget), and AMP / REST / AJAX / feed / embed / customizer-preview responses are never touched. Each regex pass falls back to the un-minified buffer if PCRE bails, and the original output is restored wholesale if any placeholder fails to round-trip. Off by default.
* Added: the Caching Plugin Conflict Detector now flags the Minify HTML overlap with W3 Total Cache, LiteSpeed Cache and Autoptimize.

= 1.15.0 =
* Changed: every internal identifier now uses the unified `mbrpe` prefix (classes, constants, options, hooks, AJAX actions and script objects) to meet the WordPress.org four-character prefix requirement and avoid collisions.
* Added: a one-time, automatic migration that moves existing settings, font caches and WebP/AVIF conversion registries to the new option names on upgrade — no reconfiguration needed.
* Hardening: review-compliance pass — additional input sanitisation ($_SERVER and decoded AJAX payloads), late output escaping (wp_kses_post / esc_js), explicit prepared-SQL placeholders, and more robust URL-to-path resolution for subdirectory installs.

= 1.14.1 =
* Fixed: inline admin script strings are now escaped at the point of output (esc_js) for full WordPress.Security.EscapeOutput compliance. No functional change.
* Internal: removed all heredoc/nowdoc syntax (disallowed by Plugin Check); inline scripts now build their content via local, immediately-closed output buffers.

= 1.14.0 =
This release prepares the plugin for the WordPress.org plugin directory and includes several feature removals — please read the Upgrade Notice before updating.
* Renamed: the plugin is now "MBR Performance" with the slug `mbr-performance` (previously "MBR WP Performance" / `mbr-wp-performance`). Your saved settings are preserved — the stored option keys are unchanged.
* Removed: the Critical CSS field. Async CSS loading continues to work on its own.
* Removed: the Third-Party tab that self-hosted Google Analytics, Google Tag Manager and Facebook Pixel. Filter-based removal of enqueued Google Fonts is unaffected.
* Removed: HTML minification. The saving is marginal once gzip/brotli compression is in play, and it was a recurring source of edge-case layout breakage.
* Removed: the "Disable Concatenation" toggle (admin-only; it had no effect on front-end performance).
* Changed: limiting post revisions now uses the `wp_revisions_to_keep` filter rather than defining the global `WP_POST_REVISIONS` constant at runtime. As a result the setting now takes effect reliably, which the constant approach did not.
* Changed: the autosave-interval setting now re-localises the core autosave script instead of defining `AUTOSAVE_INTERVAL`, so the longer-interval options take effect.
* Internal: every inline `<script>`/`<style>` block is now registered through `wp_enqueue_*` / `wp_add_inline_*`; removed `load_plugin_textdomain()` (not required on WordPress 4.6+); added an "External services" section to this readme documenting the Google Fonts, YouTube and Vimeo connections.

= 1.13.9 =
* Fix (UI): The "Compression" column header on the Conversion History table was set to 110px wide, which is just narrow enough that the word wraps onto a second line at the standard wp-list-table header font weight. Bumped to 140px so the label sits cleanly on one line.

= 1.13.8 =
* New: Bulk AVIF converter. The WebP tab now has an AVIF Bulk Converter section alongside the existing WebP one (Start AVIF Conversion, Clear AVIF History, Revert All AVIF Files), and only renders when the server has a real AVIF encoder available — so it can't be enabled to no effect on hosts that lack libavif/libheif. Mirrors the WebP converter's architecture: per-image AJAX with progress bar, history option (`mbr_avif_converted_images`) parallel to the WebP one, and a registry-driven Revert All that deletes every .avif this plugin created without touching originals or WebP variants.
* New: An "AVIF Size" column has been added to the Conversion History table, alongside the existing WebP Size column. The table now merges records from both `mbr_webp_converted_images` and `mbr_avif_converted_images` keyed by original path, so each image appears as a single row with whichever format data exists (a dash shown where a format hasn't been generated for that image). The Compression column now reports the savings against whichever recorded format is smallest — AVIF when present, since it's typically 20–30% smaller than WebP at equivalent perceived quality, otherwise WebP.
* New: Auto-convert on upload now also writes to the AVIF history option, so newly-uploaded images appear in the table alongside bulk-converted ones. Previously the auto-upload AVIF path only populated the file registry; the size data wasn't kept anywhere.
of missing width and height attributes on front-end images to reduce Cumulative Layout Shift (CLS)
* Feature: Dimension lookups work on post content, Gutenberg blocks (image, gallery, media-text, cover), Elementor widgets, attachment images and post thumbnails
* Improvement: Per-URL dimension cache (in-memory + weekly transient) to keep the filter cheap on image-heavy pages
* Improvement: Skips external images, SVGs and data URIs automatically — only measures local files
* Improvement: Transient cache is cleared when settings are re-saved, so replaced files are re-measured

= 1.6.0 =
* Feature: Integrated WebP image conversion (previously the standalone MBR WebP Converter plugin)
* Feature: New "WebP" tab in the settings panel
* Feature: Automatic WebP conversion on image upload
* Feature: Bulk converter for existing Media Library images
* Feature: Configurable compression level (1–100)
* Feature: HTML <picture> tag delivery with automatic browser fallback
* Feature: Apache/LiteSpeed .htaccess rewrite rules for transparent WebP serving
* Feature: Server diagnostics panel (GD library, WebP support, folder permissions)
* Feature: Conversion history with bulk management actions
* Feature: Gutenberg block and Elementor widget integration for <picture> tags
* Improvement: Smart skip when WebP output would be larger than the original

= 1.5.0 =
* Feature: Full WordPress Multisite network support
* Feature: Network Admin settings page (Settings > MBR Performance)
* Feature: Network-wide default settings with one-click push to all sites
* Feature: Import settings from any site as the network defaults
* Feature: Per-site override toggle — super admins can lock or unlock site customisation
* Feature: Automatic activation and default settings for newly-created network sites
* Feature: Network Admin toolbar shortcut
* Improvement: Options resolution now respects network defaults with per-site override priority
* Improvement: Save button and reset are disabled when per-site overrides are locked
* Improvement: Informational notices on per-site settings pages in multisite context

= 1.4.9 =
* Feature: Added comprehensive lazy loading controls
* Feature: Added preloading and speculative loading options
* Feature: Self-host Google Fonts with manual management
* Feature: Enhanced Google Fonts blocking (both googleapis.com and gstatic.com)
* Feature: Clear font cache functionality
* Feature: CSS scanner for unused styles
* Feature: Toolbar menu access (moved from sidebar)
* Improvement: Rebuilt admin JavaScript for better reliability
* Improvement: Reorganized Google Fonts settings to Fonts tab
* Improvement: Page builder compatibility (Elementor, Divi, etc.)
* Fix: Tooltips and action buttons now work correctly
* Fix: Elementor editor compatibility
* Fix: Admin CSS and JavaScript loading

= 1.0.0 =
* Initial release
* Core features optimization
* JavaScript optimization
* CSS optimization
* Font optimization
* Database optimization

== Upgrade Notice ==

= 1.17.0 =
Adds working Combine CSS and Combine JavaScript, plus an optional Preload Combined CSS hint. All are off by default, so nothing changes on upgrade unless you enable them. If you already run a caching plugin's own combine (WP Rocket, LiteSpeed, Autoptimize, W3 Total Cache, FlyingPress or SiteGround Optimizer), use one or the other — not both — and the built-in conflict notice will flag any overlap.

= 1.14.0 =
The plugin is renamed to "MBR Performance" (slug `mbr-performance`); your settings carry over. This release removes the Critical CSS field, the Third-Party self-hosting tab, HTML minification and the Disable Concatenation toggle. Because the plugin folder name changes, if you installed via direct download you should deactivate and delete the old "MBR WP Performance" copy after installing this one.

= 1.13.0 =
Adds a Core-tab toggle to switch off the new WordPress 7.0 AI subsystem (AI Client, Abilities API and Connectors) using core's native kill switch. Off by default, so nothing changes on upgrade unless you enable it. Harmless on WordPress 6.x. Marks the plugin tested up to WordPress 7.0.

= 1.11.0 =
The Orphaned Images tab is renamed to Orphaned Media and the scanner now supports videos, audio, documents, and archives in addition to images. Existing sites default to images-only on upgrade — tick the additional media-type checkboxes in tab settings to expand the scan. The legacy `orphaned-images` URL still works for one release.

= 1.10.0 =
Adds an Orphaned Images tab that scans the Media Library for unused images and removes them with a configurable restore window. Detection covers post parents, featured images, post content and postmeta — page builder data stores (Elementor, Bricks etc.) are not yet covered, so review the candidate list carefully before bulk-deleting. Test on a staging copy first; deletion physically removes files from disk.

= 1.9.1 =
Adds a weekly automated cleanup toggle (now actually wired to the existing weekly cron), and a page-cache advisory notice when WooCommerce geolocation is configured in a way that interacts badly with full-page caching. Also adds a last-run log for the scheduled cleanup.

= 1.9.0 =
Adds a dedicated WooCommerce tab with cart fragments control, Action Scheduler retention, session and transient cleanup, and expanded conditional asset loading. Your existing WooCommerce settings continue to work unchanged. Test on a staging copy before enabling cart fragments site-wide if your theme relies on a live-updating mini-cart.

= 1.8.0 =
Adds a bulk resize tool for existing Media Library images — downscale oversized originals in place with a two-phase scan-then-resize workflow. The operation permanently overwrites files on disk, so take a full backup before running it.

= 1.7.0 =
Adds automatic image resizing on upload and auto-injection of missing width/height attributes to help fix common PageSpeed Insights warnings. New settings live under the WebP tab. Backup before upgrading.

= 1.6.0 =
WebP image conversion is now built in. 

= 1.5.0 =
Adds full WordPress Multisite support — manage performance settings across your entire network from one place. Backup before upgrading.

= 1.4.9 =
Major update with lazy loading, preloading, improved font management, and better page builder compatibility. Backup before upgrading and test features individually.

