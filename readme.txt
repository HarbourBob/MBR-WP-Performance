=== MBR WP Performance ===
Contributors: Made by Robert
Tags: performance, optimization, speed, cache, database, webp, image
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.9.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Comprehensive WordPress performance optimization plugin with controls for core features, JavaScript, CSS, fonts, lazy loading, preloading, database optimization, WebP image conversion, automatic image sizing, and WooCommerce optimisations.

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

