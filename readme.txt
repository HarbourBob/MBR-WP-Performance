=== MBR WP Performance ===
Contributors: Made by Robert
Tags: performance, optimization, speed, cache, database, webp, image
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.12.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Comprehensive WordPress performance optimization plugin with controls for core features, JavaScript, CSS, fonts, lazy loading, preloading, database optimization, WebP image conversion, automatic image sizing, orphaned media cleanup, and WooCommerce optimisations.

== Description ==

MBR WP Performance is a powerful, all-in-one performance optimization plugin that gives you complete control over your WordPress site's performance.

= Features =

**Core Features**
* Disable unnecessary WordPress features (emojis, embeds, dashicons, etc.)
* Control REST API access
* Manage heartbeat, revisions, and autosave
* Remove query strings for better caching
* WooCommerce script optimization
* XML-RPC and RSS feed control

**JavaScript Optimization**
* Defer and async JavaScript loading
* Move scripts to footer
* jQuery optimization and removal options
* Minify and combine JavaScript files
* Delayed script execution for analytics
* Remove script versions

**CSS Optimization**
* Critical CSS inlining with auto-generator
* Async CSS loading
* Minify and combine CSS files
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

1. Upload the `mbr-wp-performance` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Access settings via 'WP Performance' in the WordPress admin toolbar
4. Configure features one tab at a time

== Frequently Asked Questions ==

= Will this plugin break my site? =

The plugin is designed to be safe, but we recommend:
1. Taking a full backup before using
2. Testing features on a staging site first
3. Enabling features one at a time
4. Testing thoroughly after each change

= Can I use this with a caching plugin? =

Yes! This plugin works alongside caching plugins and provides complementary optimizations.

= Does this work with page builders? =

Yes, the plugin is fully compatible with Elementor, Beaver Builder, Divi, Oxygen, Bricks, and WPBakery. Optimizations are automatically disabled in editor/preview modes.

= Does this work with WordPress Multisite? =

Yes! From v1.5.0 onwards the plugin fully supports WordPress Multisite networks. You can network-activate the plugin and manage default settings from the Network Admin (Settings > WP Performance). You can push those defaults to all sites at once, import settings from any existing site, and choose whether individual site admins are allowed to override the network defaults.

= How do I access the settings? =

Click 'WP Performance' in the WordPress admin toolbar at the top of the screen. You can also access individual tabs from the dropdown menu.

= What's the difference between Lazy Loading and Preloading? =

Lazy Loading delays loading of images/videos until they're needed (saving bandwidth), while Preloading loads critical resources early (improving perceived speed). They work together for optimal performance.

== Changelog ==

= 1.12.0 =
* Fix: JavaScript optimisations module — previously a placeholder class with UI toggles but no backend logic — is now fully wired up. Defer, Move-to-Footer, Defer jQuery, Remove jQuery (with test mode), Minify inline JS, Delay JS (with interaction-triggered runtime and configurable timeout), Disable Concatenation, and Remove Script Versions all work as advertised. Each defer/footer/delay path honours its own exclusion textarea.

* Fix: CSS optimisations module — previously a placeholder class — is now fully wired up. Inline Critical CSS, Async CSS (via preload+onload with the standard loadCSS polyfill for older browsers), Minify inline CSS, Conditional Block Styles (should_load_separate_core_block_assets), Remove CSS Versions, Disable Elementor Google Fonts, and Disable WooCommerce CSS on non-shop pages all functional.

* Fix: Database optimisations module — previously had only the WooCommerce cron listener — now drives full scheduled cleanup. The `mbr_wp_performance_database_cleanup` cron auto-reschedules to match the cleanup_schedule setting (daily/weekly/manual) and runs: auto-draft purge with configurable age, trash emptying with configurable retention, spam comment deletion with configurable age, unapproved comment deletion with configurable age, expired transient cleanup (handles site transients on multisite), and revision trimming to the keep-N setting. A "Last Auto-Cleanup" log table is displayed on the Database tab plus a "Run Auto-Cleanup Now" button.

* Feature: AVIF image conversion alongside WebP. New `<picture>` wrapper emits AVIF first, then WebP, then the JPEG/PNG fallback — browsers automatically pick the first format they support. AVIF is typically 20-30% smaller than WebP at equivalent perceived quality. Requires PHP 8.1+ with GD AVIF, or Imagick 7.0.25+. Falls back gracefully where unsupported; new tab section in WebP tab includes server capability diagnostics.

* Feature: Self-hosted third-party scripts. New "Third-Party" tab with per-script toggles for Google Analytics (gtag.js), Google Tag Manager (gtm.js), legacy Google Analytics (analytics.js), and Facebook Pixel (fbevents.js). Scripts are downloaded daily to /wp-content/uploads/mbr-wp-performance/third-party/ and outbound <script src=> URLs are rewritten via output buffer. Removes the PSI "Reduce the impact of third-party code" warning and stops first-paint requests to googletagmanager.com / connect.facebook.net.

* Feature: YouTube and Vimeo facade pattern. Replaces embedded video iframes with a static thumbnail and play button; the real iframe is only loaded on click. Saves ~1.4MB of YouTube JS on initial page load and stops YouTube cookies being set until user interaction. Vimeo thumbnails are hydrated lazily via the public v2 API behind an IntersectionObserver. Keyboard accessible (Enter/Space). Toggle on Lazy Loading tab.

* Feature: New "Server" tab. Browser-cache headers (Expires + Cache-Control, 1 year for images/fonts, 30 days for CSS/JS) and Brotli + Gzip text compression via .htaccess. Detects the web server and shows an equivalent Nginx snippet for Nginx hosts. Addresses two of the most common PSI warnings: "Serve static assets with an efficient cache policy" and "Enable text compression". Marker blocks "MBR Browser Cache" and "MBR Compression" — fully removed on deactivation.

* Feature: New "Diagnostics" tab containing three tools:
  - **Autoloaded Options Audit** — shows total autoloaded bytes and the top 30 options by size, with a one-click "Disable autoload" button. Protected core options (siteurl, home, active_plugins, template, stylesheet, etc.) cannot be modified. Transients are flagged. This is the single most common WordPress DB perf killer and has not been addressable from inside the plugin until now.

  - **WP-Cron Viewer** — lists every scheduled event with next-run, recurrence, and a "Callback?" column showing whether any PHP callback is currently registered. Events with no callback (left over from deactivated plugins) are flagged "orphan" and can be unscheduled with one click. Includes instructions for replacing WP-Cron with a real system cron job for performance-sensitive sites.

  - **Caching Plugin Conflict Detector** — detects WP Rocket, W3 Total Cache, LiteSpeed Cache, FlyingPress, WP Super Cache, Perfmatters, and Autoptimize, and lists which MBR options overlap with each — preventing the common pitfall of having defer/delay/minify enabled in two plugins at once.

* Feature: HTML minification (Core tab). Output-buffered, strips HTML comments (preserves IE conditionals), collapses whitespace between tags. Carefully preserves the contents of `<pre>`, `<textarea>`, `<script>`, and `<style>` via placeholder swap. Typically saves 5-15% of HTML transfer size.

* Feature: `decoding="async"` on images (WebP tab → Image Sizing & Dimensions). Lets the browser decode images off the main thread, improving INP on image-heavy pages. Auto-skips any image already carrying `fetchpriority="high"` so the LCP candidate continues to decode synchronously.

* Feature: EXIF metadata stripping on upload (WebP tab → Image Sizing & Dimensions). Removes EXIF, IPTC and XMP metadata (camera serial, GPS coordinates, embedded thumbnails) from newly uploaded JPEGs. ICC colour profiles are preserved so colours stay accurate. Uses Imagick where available (clean stripImage()) or falls back to GD. Privacy win plus typically 5-30% file size reduction with zero visible quality loss.

* Feature: Hover prefetch (Preloading tab). On link hover (or first touchstart on mobile), the destination page is prefetched so the next click feels instant. Uses the canonical instant.page v5.2.0 runtime (MIT). Server-side bail when the `Save-Data: on` header is present so users on metered connections aren't penalised.

* Improvement: `crossorigin="anonymous"` is now explicit on all preload and preconnect tags (font preloads and resource hints) — previously the bare `crossorigin` attribute was used, which is technically equivalent but flagged inconsistently by some Lighthouse audits.
* Three new option sections seeded on upgrade: `preloading`, `lazy_loading`, `third_party`, `server_headers`. Migration block handles the upgrade idempotently.

* On deactivation: all v1.12.0 .htaccess marker blocks (MBR AVIF, MBR Browser Cache, MBR Compression) are cleanly removed; the third-party script refresh cron is unscheduled; AVIF files in the registry are deleted.

* Combine JS and Combine CSS toggles remain in the UI for forward compatibility but are no-ops in this release — a safe implementation handling dependency graphs and async order is a separate engineering project. Admin notice clarifies this when either toggle is enabled. Remove Unused CSS similarly remains a UI toggle pointing users at MBR Advanced Asset Manager for per-asset control.

= 1.11.0 =
* Feature: Orphaned Images tab renamed and expanded to "Orphaned Media" — scope now covers images, videos, audio, documents, and archives alongside the original image-only detection
* Feature: Media-type checkbox group in tab settings — users opt in per type (Images / Videos / Audio / Documents / Archives); defaults to images-only on upgrade so v1.10.0 behaviour is preserved
* Feature: New Type column in the candidate list with category icons (image, video, audio, document, archive) for quick visual scanning
* Feature: Type filter dropdown alongside the existing Confidence and Sort filters — narrow the list to a specific media type
* Feature: Per-type stat breakdown row above the candidates table showing count and reclaimable bytes for each enabled type
* Tab slug changed from `orphaned-images` to `orphaned-media`; the legacy slug continues to work for one release for any bookmarked URLs
* Detection logic for non-image types relies on the existing filename-stem matching in `post_content` (which already catches URL-only references in `[video]`, `[audio]`, and `<a href>` tags) plus the postmeta string-search; explicit builder-aware detection lands in v1.12.0
* Non-image deletions only remove the single attached file — no sub-sizes or `.webp` siblings to clean up for those types, so the deletion path is naturally simpler
* Backward-compatible: existing v1.10.0 staging-table rows and exclusions list carry over unchanged
* Behaviour change: post_parent is no longer treated as definitive proof an attachment is in use. WordPress sets post_parent on upload via the post editor and never clears it, so attachments uploaded into a post and later removed from the content stayed hidden under v1.10.0. Parent-only matches now drop to Review tier so they can be inspected and deleted manually. Featured-image and post-content matches remain definitive.

= 1.10.0 =
* Feature: New "Orphaned Images" tab — scans the Media Library for image attachments that are no longer referenced anywhere, with a safe two-stage deletion workflow and a configurable restore window
* Feature: Detection covers post_parent, featured images (_thumbnail_id), post_content (matching by attachment ID, attachment_ shortcode reference, and filename stem so sized variants are caught too), and a string-search across postmeta values
* Feature: Two-tier confidence classifier — "High" candidates (zero references found) are eligible for bulk-delete; "Review" candidates (matched only in postmeta) must be deleted individually after manual inspection
* Feature: Staging table (`{prefix}mbr_orphan_log`) records the full attachment post row, postmeta, and file manifest before deletion — allows the database record to be restored within the configured window (7/14/30/60 days, or "keep forever")
* Feature: Per-attachment exclusions list to prevent specific IDs from ever being flagged as orphan
* Feature: Daily WP-Cron job (`mbr_wp_performance_orphan_purge`) cleans up staging records past their restore window
* Feature: File deletion handles the original file, all WordPress sub-size variants, the "scaled" full-size variant, and matching `.webp` siblings produced by the WebP converter — no orphan files left on disk
* Feature: Pre-deletion re-verification — orphan status is re-checked at delete time, blocking the action if the attachment has become referenced since the scan
* Feature: Live progress bar during scans, batched at 50 attachments per AJAX request to avoid timeouts on large libraries
* Feature: Stat cards show high-confidence count, review-required count, and total reclaimable bytes
* Note: Restore reinstates the database record only — image file bytes are physically deleted at the time of staging and must be re-uploaded if needed
* Note: This release does not yet detect references stored in page builder data (Elementor `_elementor_data`, Bricks, Beaver Builder, etc.) beyond the postmeta string-search; review tier exists partly to cover this gap

= 1.9.3 =
* Feature: Allowlist of REST API namespaces on the Core tab. When "Disable REST API" is set to a non-default mode (Disable for Non-Admins or Disable When Logged Out), admins can now whitelist specific namespaces that should remain accessible — useful for plugins exposing public REST endpoints such as front-end chat widgets, contact forms, or store APIs.
* Fix: Public REST endpoints registered with `permission_callback => '__return_true'` are no longer indiscriminately blocked by the REST hardening modes when their namespace is in the allowlist. Previously the only options were "all REST open" or "all REST blocked", which broke any third-party plugin (or sister plugin like MBR Intelligent Site Assistant) that legitimately needed public REST access for non-admin or logged-out visitors.
* Improvement: Helper text on the Core tab now explicitly lists common public namespaces (mbr-isa/v1, contact-form-7/v1, wc/store/v1) to make the configuration discoverable.

= 1.9.2 =
* Fix: "Remove Global Styles" no longer breaks the front end of Full Site Editing (block) themes — the optimisation is now auto-skipped when a block theme is active. FSE themes such as Twenty Twenty-Two through Twenty Twenty-Five rely on the inline `<style id="global-styles-inline-css">` output to render their colours, fonts, layout and spacing on the public front end. Stripping it left the Site Editor working but the front end with no design tokens.
* Fix: Removed the duplicate (and previously non-functional) "Remove Global Styles" checkbox from the CSS tab. The working toggle on the Core tab is now the canonical home. Two checkboxes wrote to two different option keys but only one had a backend handler — now there's just one.
* Migration: any existing `[css][remove_global_styles]` truthy value is automatically copied to `[core][remove_global_styles]` on update, so users who toggled the previously-orphaned CSS-tab checkbox don't lose their setting. The migration runs on `plugins_loaded` so it fires reliably on plugin update, not just manual activation.
* Improvement: Updated the Core tab tooltip for "Remove Global Styles" to clearly warn that it's incompatible with FSE themes, so the option remains available to classic-theme users without footgunning anyone running a block theme.

= 1.9.1 =
* Feature: Weekly automated cleanup toggle in the WooCommerce tab — runs expired sessions, transients and Action Scheduler cleanup on the existing weekly cron hook
* Feature: Geolocation and page cache advisory notice — warns when WooCommerce's default customer location is set to "Geolocate" (breaks full-page caching entirely) or "Geolocate (with page cache support)" (appends `?v=<timestamp>` query string that some cache plugins mishandle)
* Feature: Last-run log display showing when the scheduled cleanup last ran and what it removed
* Feature: Direct link from the advisory notice to the WooCommerce General settings page for quick resolution
* Fix: The `mbr_wp_performance_database_cleanup` weekly cron event now has an actual listener — previously the event was scheduled on activation but fired into the void with nothing attached
* Improvement: Defensive re-scheduling of the weekly cron when the user enables automated cleanup, in case the event was cleared by another plugin or missed during activation

= 1.9.0 =
* Feature: New dedicated WooCommerce tab consolidating all store-specific optimisations
* Feature: Cart fragments control — disable the admin-ajax `get_refreshed_fragments` request site-wide or only on non-shop pages (major TTFB win on cached sites)
* Feature: Expanded conditional asset loading — dequeues WC scripts, styles, block assets, selectWoo, blockUI and related libraries on non-shop pages
* Feature: Disable the zxcvbn password strength meter on the frontend
* Feature: Disable WooCommerce marketplace suggestions and dashboard status widgets
* Feature: Prevent the heavy wc-admin React bundles from loading on non-WooCommerce admin screens
* Feature: Configurable Action Scheduler retention period (default 30 days, options for 14/7/3) — stops `actionscheduler_actions` ballooning on busy stores
* Feature: One-click cleanup buttons for expired WooCommerce sessions and product/order/expired transients
* Feature: One-time admin notice on upgrade informing users that their existing WooCommerce settings have moved to the new tab (dismissible)
* Improvement: Legacy `core.disable_woocommerce_scripts` and `css.disable_woocommerce_css` options remain fully backward-compatible — existing sites keep their behaviour without re-saving
* Improvement: Tab gracefully shows an inactive state when WooCommerce is not installed, so the capability remains discoverable

= 1.8.0 =
* Feature: Bulk resize tool for existing Media Library images — scan for JPEGs and PNGs exceeding the configured maximum dimension, then downscale them in place
* Feature: Two-phase workflow (Scan → Start Resize) with progress bar, live log, and running savings total
* Feature: Automatic sub-size regeneration after each resize using the WordPress core pipeline
* Feature: Elementor CSS cache is cleared automatically after a bulk resize so widgets re-render with the new dimensions
* Improvement: Stale WebP files are deleted automatically before sub-sizes are regenerated, and their entries are stripped from the WebP registry — prevents old WebP content being served after a resize
* Improvement: Skips images that are already within the configured maximum, writes a clear "skipped" reason to the log
* Improvement: Clear warning in the UI that bulk resize permanently overwrites files on disk and cannot be undone automatically
* Improvement: Paginated scan (batches of 200) to keep memory use reasonable on large libraries

= 1.7.0 =
* Feature: New "Image Sizing & Dimensions" section in the WebP tab
* Feature: Automatic resize-on-upload with configurable maximum dimension (uses the WordPress `big_image_size_threshold` filter, default 2560px)
* Feature: Automatic injection of missing width and height attributes on front-end images to reduce Cumulative Layout Shift (CLS)
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
* Feature: Automatic migration of conversion history from standalone MBR WebP Converter plugin
* Improvement: Smart skip when WebP output would be larger than the original

= 1.5.0 =
* Feature: Full WordPress Multisite network support
* Feature: Network Admin settings page (Settings > WP Performance)
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
WebP image conversion is now built in. If you were using the standalone MBR WebP Converter plugin, you can deactivate it after upgrading — your conversion history will be migrated automatically. Backup before upgrading.

= 1.5.0 =
Adds full WordPress Multisite support — manage performance settings across your entire network from one place. Backup before upgrading.

= 1.4.9 =
Major update with lazy loading, preloading, improved font management, and better page builder compatibility. Backup before upgrading and test features individually.

