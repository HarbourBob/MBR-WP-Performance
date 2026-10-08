=== MBR Performance ===
Tags: performance, optimization, speed, cache, page cache, database, webp
Requires at least: 5.9
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2.1.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Comprehensive WordPress performance plugin: full page caching plus granular controls for core features, JavaScript, CSS, fonts, lazy loading, preloading, database optimization, WebP image conversion, automatic image sizing, orphaned media cleanup, and WooCommerce optimisations.

== Description ==

MBR Performance is a powerful, all-in-one performance optimization plugin that gives you complete control over your WordPress site's performance.

As of 2.0.0 it includes a full page cache, so it no longer needs a separate caching plugin alongside it. If you already run one, MBR Performance detects it and leaves page caching switched off rather than fighting it — every other optimisation continues to work as before.

A comprehensive User Guide (PDF) is bundled in the ZIP file.

= Features =

**Full Page Caching (new in 2.0.0)**
* Static HTML page cache with three serving tiers — .htaccess rewrite (no PHP at all), an advanced-cache.php drop-in (PHP without WordPress), or an in-WordPress fallback where the filesystem is locked down
* Pre-compressed gzip copies written alongside each page, so the server never compresses the same bytes twice
* Marketing parameters stripped before lookup, so a link shared with ?utm_source= hits the same entry as the clean URL
* Targeted purging — editing a post clears that post plus the archives, terms, author page, date archives and adjacent posts it appears on, not the whole cache
* Never caches logged-in visitors, and never caches any page that sets a cookie
* X-MBR-Cache response headers reporting HIT, MISS or BYPASS with the specific reason, so you can see exactly why any page is or isn't cached
* Optional separate mobile cache, 404 caching, feed caching and TTL expiry
* Refuses to run alongside another page cache, rather than producing content neither plugin can explain

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
* Used CSS (Mode A) — inline each page's critical CSS and asynchronously load the full stylesheets, eliminating render-blocking unused CSS while keeping the originals as a safety net
* Used CSS Mode B — per-template analysis that removes the analysed stylesheets outright instead of deferring them, learned from several sampled URLs per template, with a selector safelist as the guard rail
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

**Script Modules & Interactivity API (WordPress 6.5+)**
* Module preload hoisting — learns which ES modules each URL loads and emits modulepreload hints in the head on later visits, so the browser starts fetching the module graph while it is still parsing the head
* Fixes a genuine gap on classic themes: WordPress discovers modules during body rendering, so it prints the import map and every preload hint in the footer, where the hints arrive at the same moment as the scripts they were meant to front-run. Block themes already get head hints from core, and are left alone
* Walks the static dependency graph so a module's imports are hinted too; dynamic imports are deliberately excluded, since preloading them would fetch bytes that may never be used
* Optional high fetchpriority for nominated modules via the core API (WordPress 6.9+), with a per-page preload cap and an exclusion list
* Defer, delay and combine remain hands-off by design: modules defer by specification, and combining them would break the import map that resolves their imports

**Real User Monitoring (Core Web Vitals)**
* Collects real-user LCP, CLS and INP from actual visitors and stores them in a local database table — field data to sit alongside the Doctor's synthetic scan
* The only way to see INP, which does not exist without a real interaction, so a lab tool cannot measure it
* Attribution captured per metric — the element that was the LCP candidate, the handler behind a slow INP, the source of the largest layout shift
* Nightly aggregation rolls raw samples into per-template and per-URL daily p75s, then purges the raw; aggregates are trimmed on a configurable window
* Per-template breakdown (mobile vs desktop) and a worst-offenders table pointing at the specific URLs and elements to fix
* Privacy-first by construction: no cookies, no IP storage, no user-agent retention (reduced to device-class + browser-family at write time), and nothing ever leaves your server
* Configurable sample rate for high-traffic sites, optional exclusion of logged-in sessions, and a one-click Clear RUM data control
* Off by default; opt-in from the RUM tab

**OPcache Status & Control (new in 2.1.0)**
* Diagnostics panel reporting hit rate, memory use, cached files against the server's limit, restart counts and whether PHP checks for changed files
* Plain-English findings when OPcache is missing, switched off, short of memory or file slots, or running with validate_timestamps off, naming the php.ini setting to ask your host about
* One-click Flush OPcache on the Diagnostics tab and in the admin toolbar
* Files the plugin writes itself (advanced-cache.php, the cache config, wp-config.php) are refreshed in OPcache the moment they change
* OPcache is configured by your host in php.ini, so this reports and flushes; it cannot switch OPcache on or resize it

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

== Screenshots ==

1. A view of just some of the Core Features for optimization
2. The settings for self hosting and preloading Google Fonts
3. The image settings for both WebP and AVIF formats
4. The bulk converter for WebP and AVIF images

== Frequently Asked Questions ==

= Will this plugin break my site? =

The plugin is designed to be safe, but we recommend:
1. Taking a full backup before using
2. Testing features on a staging site first
3. Enabling features one at a time
4. Testing thoroughly after each change

= Can I use this with a caching plugin? =

Yes, though from 2.0.0 you may not need to. MBR Performance now has its own full page cache, and it will not run alongside another one: if WP Rocket, LiteSpeed Cache, W3 Total Cache, WP Super Cache, Cache Enabler, WP Fastest Cache, FlyingPress, Surge, Breeze, Swift Performance or SiteGround Optimizer's file cache is active, page caching stays switched off and the Cache tab tells you which plugin is blocking it. Two page caches on one site produce stale, mismatched content that neither plugin can explain, so this is a hard block rather than a warning.

Every other optimisation still works alongside those plugins exactly as before. Where a feature overlaps — Combine or Minify CSS/JS, for example — the built-in Conflict Detector flags the specific overlapping toggles on the settings screen, so you can avoid running the same pass on both sides.

= Do I need to edit wp-config.php to use page caching? =

No. Enabling page caching installs the advanced-cache.php drop-in and adds the WP_CACHE constant to wp-config.php for you, and removes both again when you switch it off or deactivate the plugin. Your wp-config.php is backed up to wp-config.php.mbrpe-backup the first time it is touched, and the edited file is syntax-checked before being written.

If your host makes wp-config.php read-only — some managed hosts do — the Cache tab says so and shows you the single line to add by hand. Until you do, caching still works; pages are served from inside WordPress instead of before it, which is slower but still a substantial improvement.

= Why isn't a particular page being cached? =

Switch on "Send Debug Headers" on the Cache tab and load the page. Every response then carries X-MBR-Cache with one of HIT, MISS or BYPASS, and a bypass also carries X-MBR-Cache-Reason naming the specific rule — logged-in, excluded-cookie, excluded-uri, password-protected, DONOTCACHEPAGE, search, preview and so on.

The common ones are expected: logged-in visitors are never served from cache, nor is any page that sets a cookie, nor the WooCommerce cart, checkout and account pages.

= What is Used CSS (Mode A), and when should I use it? =

With "Generate Used CSS" enabled (CSS tab), the plugin works out which CSS rules each page actually uses, inlines just those in the page head for an instant first paint, and loads your full stylesheets asynchronously behind them. This removes render-blocking CSS from the critical path. It is the safe variant: the original stylesheets are never removed, only deferred, so if the analysis misses a rule (for example one used by a widget that JavaScript injects after the page loads) the full stylesheet still arrives a moment later and corrects it.

Used CSS only helps if your render-blocking is actually caused by CSS. Run the page through PageSpeed Insights and look at the "render-blocking requests" list: if it is mostly stylesheets, Used CSS will help; if it is mostly JavaScript, the win is on the JavaScript tab (Defer/Delay), not here. On a site that is already light on CSS the gain may be small. The feature earns its place on CSS-heavy sites — a large theme plus a page builder plus several plugins each loading their own stylesheets.

Used CSS is generated in the background after the first visit to each page and cached per URL, so the first visitor sees the page generated and later visitors are served the optimised version. If your host runs a full-page cache (such as SiteGround's), the relevant URL is purged automatically after generation so the optimised version is the one that gets cached.

A few notes for best results: leave it off by default and test on a staging copy first; if a particular stylesheet must always load in full, add part of its URL to the "Exclude from optimization" field on the CSS tab; and there is no need to also enable the separate "Async CSS" option — Used CSS handles delivery itself and automatically suppresses the standalone async layer to avoid the two conflicting.

= What is Used CSS Mode B, and should I use it instead of Mode A? =

Mode B does the same job as Mode A but makes the opposite trade. Mode A analyses each page and keeps the original stylesheets, loading them asynchronously behind the inlined CSS — so a rule the analysis misses is corrected a moment later. Mode B analyses each template rather than each page, and then removes the stylesheets it has analysed, so those bytes are never downloaded at all. That is a real additional saving, but it also means a missed rule stays missed for that whole template, because nothing arrives behind the inlined CSS to put it right.

Everything else about Mode B follows from that. It learns each template from several distinct URLs and keeps the sum of what they all used, rather than trusting a single page — so a rule only one of your posts needs still survives for all of them. Those first few visits per template are served completely untouched while the analysis runs afterwards, and only once a template has enough samples does it start being served optimised. Logged-in views are excluded, so browse logged out to teach it.

It is also deliberately cautious about what it will remove. A stylesheet is dropped from a page only if that exact file was analysed while learning, so a plugin stylesheet that appears on only some pages of a template keeps its link everywhere. Sheets containing @import are never touched (the imported file would never arrive once the original is gone), nor are print and narrow-media sheets, external sheets, the admin bar, or anything on your exclusion lists. Requests carrying query arguments are served untouched, apart from campaign and click-ID parameters (utm_*, gclid, fbclid and similar) which change nothing about the rendered page.

Use Mode A unless you have a reason to go further. Choose Mode B when you have a staging copy, a finite set of templates, and the time to click through each one logged out — including opening menus, modals, cart drawers and anything else JavaScript adds after load, since that is precisely what a static analysis cannot see. Put the class names for those in the Selector safelist. To compare any page against its original stylesheets, add ?mbrpe_modeb=off to its URL.

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

= Real User Monitoring (RUM feature) — no external service =

The Real User Monitoring feature contacts no third party. The Core Web Vitals library is bundled with the plugin and served from your own domain, and the metrics it collects are posted only to a first-party REST endpoint on your own site and stored in your own database. No visitor data — and no data of any kind — is sent off your server.

== Credits ==

The Used CSS feature bundles two open-source libraries, loaded only while generating used CSS. Each is MIT-licensed and GPL-compatible, and the full licence text for both is included in the plugin's includes/vendor directory.

* Sabberworm PHP-CSS-Parser — Copyright (c) 2011 Raphael Schweikert (https://www.sabberworm.com/). MIT License. Used to parse stylesheets into a rule tree.
* Symfony CssSelector Component — Copyright (c) 2004-present Fabien Potencier and the Symfony contributors (https://symfony.com/). MIT License. Used to convert CSS selectors to XPath for matching against the rendered page.
* Google web-vitals — Copyright (c) Google LLC (https://github.com/GoogleChrome/web-vitals). Apache License 2.0, GPL-compatible under this plugin's "GPL v2 or later". Bundled (attribution build) and loaded locally only when Real User Monitoring is enabled; used to read Core Web Vitals in the visitor's browser.


== Changelog ==

= 2.1.5 =
No more "strip_tags(): Passing null" deprecation notice on the settings screen.

* Fix: on PHP 8.1 and later with debugging on, opening the MBR Performance screen showed "Deprecated: strip_tags(): Passing null to parameter #1 ($string)" from wp-admin/admin-header.php. The settings screen is a hidden page with no sidebar parent, and WordPress only looks for page titles in the sidebar menu, so it left the title empty and passed null to strip_tags(). The plugin now supplies the title itself before the header is printed. A side effect: the browser tab now reads "MBR Performance" rather than being blank.
* Fix: the hidden settings page is registered with an empty parent rather than null, which avoids a further PHP 8.1+ deprecation inside plugin_basename().
* Docs: the user guide is updated to 2.1.5, with two new Troubleshooting entries covering this fix and the 2.1.4 Mode B status fix.

= 2.1.4 =
A static front page no longer sits on "Still learning" forever under Used CSS Mode B.

* Fix: under Used CSS Mode B, a static front page was reported as "Still learning" (Samples 1 / 3, or whatever the target was) however much traffic it received. Samples count distinct URLs, and a static front page only ever has one, so it could never reach the target. It was in fact being served optimised from the second visit — only the status was wrong. Single-URL templates (a static front page, and WooCommerce's cart and checkout) now have a target of 1, and the CSS tab shows them as "Learned — serving". Existing caches are corrected on the spot; there is no need to clear them. A front page that lists your latest posts keeps the normal target, because its paginated URLs are genuine extra samples.
* New: the mbrpe_modeb_single_url_templates filter, for adding a custom template that only ever has one URL.
* Tweak: a tooltip on the Mode B Samples column, and a line in the "URLs sampled per template" description, explaining single-URL templates.

= 2.1.3 =
Minify CSS and Minify JavaScript now actually minify your files.

* Fix: Minify CSS and Minify JavaScript did nothing. Both were meant to shorten inline style and script blocks only, and even that never ran: the filters returned early for any handle with a src, and WordPress never calls them for a handle without one. The external files PageSpeed Insights lists under "Minify CSS" and "Minify JavaScript" were never touched, except inside a Combine CSS bundle.
* New: each local stylesheet and script that is not already minified is served from a minified copy under /uploads/mbr-performance-min/, written on first request and rebuilt automatically when the original changes. The copy mirrors the original's path, so URL-fragment exclusions still match it. Files named .min.css or .min.js, files that would save under 5%, external files, excluded files and scripts that locate themselves at run time (webpack chunks, workers) keep their original URL.
* New: JavaScript is minified with a bundled tokeniser (JShrink, BSD licence) rather than pattern matching. A script it cannot parse is served unchanged. Combined JavaScript bundles are now minified too when Minify JavaScript is on.
* New: inline CSS and script attached to enqueued handles (wp_add_inline_style, wp_add_inline_script, wp_localize_script, global styles) is minified.
* Fix: the CSS minifier broke every quoted url(). A quoted string is protected before the url() around it, and restoring them in one pass put the url() back with the string's placeholder still inside, so url('font.woff2') reached the browser as a meaningless token. This affected Combine CSS with minification on and the CSS inlined by Used CSS and Mode B: quoted font and background-image URLs in them failed to load.
* Fix: the shared CSS minifier removed the space before a colon, turning a descendant pseudo-class such as ".menu :hover" into ".menu:hover". This also affected Used CSS and Mode B output, whose caches are cleared on update.
* Fix: saving or resetting settings deleted combined bundles but left the page cache holding pages that linked to them. Saving, resetting, or clearing the CSS or JS file cache now clears the page cache as well.
* Fix: Server and Diagnostics were missing from the MBR Performance toolbar menu. The menu and the tab row now share one list. The menu is shown only to users who can manage options.
* Updating clears the combined, Used CSS, Mode B and page caches once.

= 2.1.2 =
Fixes Used CSS (Mode A and Mode B) never reaching visitors when the page cache is on.

* Fix: with page caching enabled, Used CSS never took effect. The first visit to a page is served untouched while its used CSS is generated in the background, and the page cache stored that first render, with its full, render-blocking stylesheets. From then on every visitor was served the stored copy straight from disk, so the request never reached PHP again and the optimised version was never shown or cached. The same applied to Mode B while a template was learning. Generating and learning renders are now served but not stored (X-MBR-Cache: BYPASS, reason used-css-generating or used-css-mode-b-learning), so the next visit renders the optimised page and that is what gets cached.
* Fix: the page cache now listens for Used CSS and Mode B finishing a URL, and clears any entry stored for it. Previously only SiteGround Speed Optimizer's cache was purged at that point.
* Fix: Used CSS and Mode B analysed the wrong HTML when other output buffers were open. They read the page with ob_get_clean() at shutdown, which takes the innermost buffer — HTML minify's or the Google Fonts strip's — and discards that module's processing for the request. They now capture their own buffer through a callback and analyse after WordPress has flushed every buffer, so the analysis sees exactly the delivered HTML and the other modules run normally.
* Fix: a page with nothing for Used CSS to optimise (no local stylesheets) was re-analysed on every request. It now leaves a marker, cleared by the same events as the cache itself, and is page-cached normally.
* Updating clears the page cache once, so pages stored before this fix are rebuilt through the corrected path on their next visit.

= 2.1.1 =
Fixes the OPcache panel reporting a working OPcache as switched off.

* Fix: the OPcache panel said OPcache was "installed but switched off" on servers running it in file-cache-only mode, which is the standard configuration on SiteGround and some other managed hosts. In that mode compiled PHP is stored on disk rather than in shared memory, and PHP's status report marks the shared-memory cache as disabled; 2.1.0 read that as OPcache being off. "Switched off" is now reported only when opcache.enable itself is 0, which can be read even where the OPcache functions are unavailable.
* New: file-cache mode is recognised and explained. The panel shows the mode, whether PHP checks for changed files, and whether the plugin's own files are refreshed on write. Memory and hit-rate figures do not exist in this mode and are not shown.
* Fix: Flush OPcache is no longer offered in file-cache mode, where opcache_reset() leaves the on-disk cache untouched and would have reported success while changing nothing. The plugin's own files are still invalidated individually, which does remove their entries from the disk cache.
* Fix: a host that runs OPcache but does not let plugins read its status is now reported as exactly that, rather than as OPcache being off.
* Fix: the opcache.restrict_api check compares against the requested script, which is what PHP itself checks, rather than the plugin's own file path.

= 2.1.0 =
Adds OPcache status and control to the Diagnostics tab, and makes every PHP file the plugin writes take effect immediately on servers that do not check for changed files.

* New: OPcache panel on the Diagnostics tab. Reports hit rate, memory used, wasted and free, cached files against the server's file-slot limit, out-of-memory and file-slot restarts, whether PHP checks for changed files and how often, and when OPcache last restarted. Missing data reads as unknown rather than zero.
* New: plain-English findings. The panel explains when OPcache is not installed, installed but switched off, restricted to another path by the host (opcache.restrict_api), running out of memory or file slots, or holding a large share of outdated copies, and names the php.ini setting to ask the host about. OPcache is configured at server level, so the plugin reports and flushes; it does not pretend to offer switches it cannot provide.
* New: Flush OPcache, on the Diagnostics tab and in the MBR Performance toolbar menu. Nonce-protected; requires manage_options, or super admin on multisite, where a flush affects every site on the server. The confirmation says plainly that on shared hosting the flush may cover other sites in the same PHP pool.
* Fix: advanced-cache.php and wp-config.php are now invalidated in OPcache as soon as the plugin writes them, as the cache config already was. On a host with validate_timestamps off, a refreshed drop-in or a WP_CACHE change previously sat on disk unused until OPcache was next flushed, so the old drop-in could keep pairing with the new cache writer. The drop-in and config are also invalidated before removal.
* New: on WordPress older than 6.2, plugins, themes and translations updated through WordPress are invalidated in OPcache after the update, and a core update flushes it. WordPress 6.2 and newer do this themselves, so the fallback only registers on older versions.
* New: mbrpe_opcache_reset action, fired after every flush attempt with the result and the reason.
* Fix: the Server tab could not switch its rules off. Both of its settings are checkboxes, and with both unticked the browser sent nothing for the section, so the saved values were kept and the .htaccess blocks stayed in place. The section is now always posted.
* Multisite: the OPcache panel and flush control are shown to super admins only.

= 2.0.2 =
Bug-fix release for the full page cache. Recommended for every site using static (.htaccess) serving, where it affects search indexing.

* Fix: pages served by the static (.htaccess) cache tier were sent with `X-Robots-Tag: noindex`. The guard .htaccess written into the cache directory set that header on every .html and .gz file in the folder, intending to stop search engines indexing the raw cache files if they were browsed directly. But directory rules apply to every response served from that folder, including the cache hits the root rewrite rules send there, so every `HIT-STATIC` page told search engines not to index it. Pages served by the drop-in or on a cache miss were unaffected, which is why the problem only showed on cached URLs without a query string.
* Fix: the guard .htaccess now refuses direct requests for the cache path with a 403 instead. It matches on the original request line, which the rewrite that serves a cache hit does not change, so ordinary visitors are unaffected and nobody can read a cached page by addressing the cache directly.
* Fix: plain (non-gzipped) static cache hits are now sent with `charset=UTF-8`. Only the pre-compressed copies declared a charset before, so clients that did not request gzip received `Content-Type: text/html` alone.
* Fix: the guard file is now versioned and rewritten when out of date. It was previously written only if missing, and purging deliberately preserves it, so a faulty guard could never be replaced. Updating rewrites it immediately; the header is applied when a page is served, so existing cached pages are corrected at once without a purge.
* Note: if a search engine has already dropped pages because of this, request re-indexing (for example with URL Inspection in Google Search Console) once the update is installed.

= 2.0.1 =
Security and hardening release. No feature changes; upgrading is recommended for every install, and required for anyone running MBR Performance on a multisite network.

* Security: pasted Critical CSS is now stripped of tag fragments repeatedly rather than in a single pass. A single pass could be defeated by a crafted string whose removal rejoined its neighbours into a working `</style>`, which let anyone able to save plugin settings place arbitrary JavaScript on the front end. On multisite that crossed a real boundary, because site Administrators do not hold `unfiltered_html`.
* Security: the Optimise / Repair / Convert to InnoDB tools now act only on the current site's own tables. They previously enumerated every table the database user could see, which on a multisite network meant every other site's tables, and on shared-database hosting could reach a neighbouring install. A Super Admin on the main site still gets the network-shared tables. Conversion failures now report the table name only, not the raw database error.
* Security: the RUM beacon is rate-limited per client and capped on total stored rows. The previous limit was keyed on the request path, which the caller chooses, so rotating it left an unauthenticated endpoint writing a row per request.
* Security: media deletion now resolves sub-size filenames with `basename()` and confirms every file sits inside the uploads directory before removing it, rather than trusting the path components in attachment metadata.
* Security: the stylesheet and script combiners now check file extensions before reading. Resolving inside the WordPress directory was previously sufficient, and the result is published under wp-content/uploads.
* Security: downloaded font files must now come from `fonts.gstatic.com` over HTTPS, and font family names are URL-encoded before being placed in the API request.
* Fix: the cache key's scheme and host are now derived identically by the plugin and by the advanced-cache.php drop-in. The two had drifted — the drop-in trusted `X-Forwarded-Proto` and `X-Forwarded-SSL` unconditionally while the plugin used `is_ssl()` — so on a site not behind a proxy a forged header sent the reader to a branch the writer never populated, costing a permanent cache miss for those requests. The scheme is now pinned from `home_url()`; forwarded headers are consulted only when the new `trust_proxy` config flag is set.
* Fix: the Host header is validated as a hostname and, on single-site installs, checked against the hosts the site actually answers to. It was previously character-stripped and used as-is, so varying the header alone created unlimited cache directories. Domain aliases can be added with the new `mbrpe_cache_hosts` filter.
* Fix: Google Fonts with capitalised names now download. Family names were forced to Title Case before the request, turning "IBM Plex Sans" into "Ibm Plex Sans" — a name Google answers with a 400, so the download failed with no explanation. The name is now sent as you typed it, and an all-lower-case name is retried in Title Case so "open sans" still works. This affected every family with an acronym in it, including IBM Plex Sans, IBM Plex Mono, DM Sans, PT Serif and EB Garamond.
* Fix: a font that fails to download is no longer recorded as installed, which previously left the front end requesting a stylesheet that was never written. Failures now explain that family names are case-sensitive and that the weight must be one the family publishes, instead of reporting a bare count.
* Fix: enabling Google Fonts self-hosting or removal no longer takes unrelated theme CSS with it. Matching stylesheets were deregistered as well as dequeued, and a handle is more than a URL: WordPress silently refuses to print any stylesheet that named the removed handle as a dependency, and drops any inline CSS a theme attached to it. Customizer colour rules — a semi-transparent header background, for instance — are commonly attached that way, so they vanished along with the font request. The handle is now kept and its source blanked, which stops the font loading while leaving dependents and inline CSS intact. The same change is applied to the Font Awesome removal option.
* Fix: the Disable Dashicons option no longer deregisters the dashicons handle, only stops it loading. Plenty of themes and plugins enqueue their own stylesheet with dashicons as a dependency, and deregistering it meant WordPress silently declined to print those stylesheets. The option applies to logged-out front-end visitors only, so an administrator checking the site while logged in would not have seen the missing styles.
* Fix: removing Google Fonts resource hints no longer removes every other resource hint on the page. The option previously unhooked wp_resource_hints() entirely, discarding a theme's CDN preconnect and any plugin's dns-prefetch to drop at most two Google entries. Only the Google entries are removed now.
* Fix: the Google Fonts response status is checked before the body is parsed, so a rejected request fails for the reason it actually failed.
* Hardening: admin notices render server-supplied messages as text rather than markup.
* Hardening: directory containment checks throughout now compare with a trailing separator, so a sibling directory whose name merely begins with the base cannot satisfy them.
* Hardening: the bundled autoloader refuses traversal sequences in a resolved class name.
* Internal: drop-in contract version raised to 2.0.1. Updating refreshes the installed drop-in, rewrites the cache config and clears existing cached pages once, because entries written before this release were keyed the old way.

= 2.0.0 =
* New: full page caching. The finished HTML of each page is written to disk and served to subsequent visitors instead of being rebuilt. Three serving tiers, fastest first: optional .htaccess rewrite rules that let Apache or LiteSpeed answer a hit straight from disk with no PHP at all; an advanced-cache.php drop-in that starts PHP but not WordPress, which works on every host including Nginx; and an in-WordPress fallback for installs where wp-content or wp-config.php is read-only. Each tier degrades to the next, and no failure produces an error page rather than a cache miss. Off by default.
* New: targeted purging. Editing a post clears that post, the front page, the blog index, every term it belongs to across every taxonomy, its post type archive, its author archive, its year/month/day archives and its adjacent posts — plus the first twenty paginated pages of each archive — rather than emptying the whole cache. Comments, term edits, user profile changes and WooCommerce stock movements each purge what they actually affect. Menu, widget, theme, customizer, permalink and plugin changes purge everything, because they change every page. An option is provided to purge everything on every edit for themes that cross-link too heavily for targeted purging to be reliable.
* New: X-MBR-Cache debug headers. Every response reports HIT, MISS or BYPASS, and a bypass names the specific rule that caused it. This is the difference between diagnosing a caching problem in a minute and guessing at it for an afternoon.
* New: query-string normalisation. Around twenty analytics and ad-click parameters — utm_*, fbclid, gclid, mc_cid, msclkid and the rest — are stripped before the cache is consulted, so a page shared through a newsletter or an ad campaign serves the same entry as its clean URL instead of generating a fresh miss every time. Further parameters can be added, and an allow-list is available for filtered archives that genuinely need their own entries.
* New: pre-compressed gzip copies written alongside each cached page, so the server never spends CPU compressing identical bytes on every hit.
* New: Cache tab with a status panel that reports which serving tier is active, how many pages are cached, WP_CACHE and drop-in state, and directory writability — with a repair button beside anything that is wrong.
* New: purge controls in the admin toolbar, for the whole cache or just the page you are looking at.
* Safety: logged-in visitors are never served from cache, and no page that emits a Set-Cookie header is ever stored — that single check prevents most of the ways a page cache can hand one visitor another's content. Password-protected posts, previews, search results, the WooCommerce cart, checkout and account pages, and any request carrying a login, comment, password or cart cookie are all excluded by default, as is anything a theme or plugin marks with DONOTCACHEPAGE.
* Safety: a response is only stored if it is a complete HTML document, so a fatal error or an upstream timeout mid-render cannot be frozen into the cache. Cache files are written to a temporary name and renamed into place, which is atomic, so a visitor can never read a half-written page.
* Safety: MBR Performance refuses to enable page caching while another page cache is active, naming the plugin responsible on the Cache tab. Running two produces stale content neither plugin can account for.
* Change: the cache buffer opens at the earliest possible point on template_redirect, so it wraps every other MBR optimisation. Minified HTML and Used CSS Mode B output are baked into the cached file, which means those modules cost nothing at all on a cache hit.
* Change: wp-config.php and .htaccess are both backed up before they are first modified, to wp-config.php.mbrpe-backup and .htaccess.mbrpe-backup. The edited wp-config.php is syntax-checked before being written; if it does not parse, the original is left untouched.
* Change: deactivating the plugin now removes the drop-in, the rewrite rules and every cached file. Leaving a live advanced-cache.php behind would keep serving stale pages with no plugin left to purge them.
* Change: the plugin no longer describes itself as doing no page caching of its own. The Conflict Detector's softer overlap warnings are unchanged; page-cache conflicts are handled separately and block rather than warn.
* Upgrade note: page caching is switched off after upgrading. An update should never change how your site is served without being asked, and on a site already running another caching plugin it would be actively unhelpful. Enable it on the Cache tab when you are ready.

= 1.23.1 =
* Fix: activation and deactivation now actually run. The hooks were registered against includes/mbrpe-bootstrap.php rather than the main plugin file, so WordPress never fired them — meaning a fresh install created none of its database tables and scheduled none of its cron jobs, and deactivating left the plugin's .htaccess blocks and caches behind. Both are now registered against the real plugin file.
* Fix: existing installs are repaired automatically. Because the version stamp was written on the first page load regardless, an affected install would have gone on skipping table creation even if it were reactivated. This release recreates the RUM and orphan-media tables and reschedules the cron jobs on upgrade. The repair is idempotent, so a healthy install is unaffected.
* Fix: WebP and AVIF delivery via .htaccess now works when WordPress is installed in a subdirectory. The rewrite tested DOCUMENT_ROOT against a path that is relative to the .htaccess file's own directory, so on a subdirectory or subdirectory-multisite install the condition never matched and every visitor silently received the original JPEG despite the rules being present. Now matched against REQUEST_FILENAME, which is correct wherever WordPress lives. If you had the option enabled on a subdirectory site, switch it off and on again to write the corrected rules.
* Fix: images could be wrapped in a <picture> element twice, producing nested <picture> markup. A featured image passes through both the wp_get_attachment_image and post_thumbnail_html filters, and a Gutenberg image block passes through render_block and then the_content, so more than one pass could see the same image. Wrapping is now idempotent, and existing <picture> elements — including hand-written ones — are left alone.
* Fix: the Gutenberg block image filter was missing the guard that every other wrapping filter honours, so block images were being wrapped inside the block editor and other contexts the module deliberately steps out of.
* Fix: PHP 7.4 compatibility. The bundled Symfony CssSelector component calls str_contains(), which is PHP 8.0+, and would fatal on PHP 7.4 the moment a Used CSS analysis ran. Symfony declares a dependency on symfony/polyfill-php80 for exactly this, which had not been vendored alongside it; that polyfill is now supplied. The library itself is unchanged and the stated PHP 7.4 requirement stands.
* Security: TLS certificate verification is no longer disabled on outbound requests. The Google Fonts downloader in particular fetched a stylesheet and font files with verification off and wrote the results straight into the uploads directory. Those requests now verify with no fallback. Requests to the site's own front end — the Performance Doctor, the CSS scanner, the font detector — verify by default and fall back only for the site's own host, so self-signed staging certificates keep working.
* Security: the bulk WebP and AVIF converters now resolve the submitted image path and confirm it lands inside the uploads directory and is a supported image type. Both endpoints already required a valid nonce and manage_options, so this was never remotely reachable; it is defence in depth.
* New: uninstall.php. Deleting the plugin from Plugins → Delete now removes its options, network options, database tables, scheduled events, transients and cache directories. Your Media Library is never touched, and generated WebP/AVIF files and self-hosted fonts are deliberately left in place, since a cached page or CDN may still reference them.
* Housekeeping: removed three unreferenced JavaScript files and an obsolete bundled copy of the Plugin Update Checker (v5.6), which was superseded by v5.7 and loaded by nothing.

= 1.23.0 =
* New: Used CSS Mode B — per-template critical CSS that genuinely removes the unused stylesheets. Mode A caches per URL and keeps the originals as a deferred safety net; Mode B caches per template and deletes the sheets it has analysed, so the bytes stop being downloaded at all. A site with ten thousand posts keeps one cache entry for "single posts" rather than ten thousand. Off by default.
* New: multi-sample template learning. Because a wrong drop is permanent under Mode B, a template is not analysed from a single page. Mode B samples several distinct URLs of the same template and keeps the union of what they used, so a rule that only one of your posts needs still survives for all of them. The number of samples is configurable (default three); those learning visits are served completely untouched.
* New: selector safelist. The analysis reads the page as the server delivers it, so anything JavaScript adds afterwards — a consent banner, a cart drawer, a modal, a class toggled at a scroll position — looks unused. Class names and prefixes listed here are kept regardless. This is the guard rail, and it is where almost every Mode B problem is fixed.
* Safety: a stylesheet is only ever removed from a page if that exact file was analysed while learning. Sheets a plugin loads on only some pages of a template, sheets containing @import, print and narrow-media sheets, external sheets, the admin bar and Dashicons, and anything on the exclusion lists are all left exactly as WordPress emitted them. Leaving a sheet in place costs a request; removing one that was never examined costs an unstyled page.
* Safety: the inlined CSS is substituted in place of the first stylesheet it replaces rather than printed at the top of the head, so a theme's inline style that previously lost to a stylesheet below it still loses. Printing at wp_head would have silently inverted that cascade.
* Safety: media="screen" sheets have their rules re-wrapped in @media screen so they cannot leak into print; requests carrying query arguments are served untouched, except for campaign and click-ID parameters (utm_*, gclid, fbclid and similar) which change nothing about the rendered page.
* Change: the Mode B cache clears itself on content save, theme switch, plugin activation/deactivation/update, Customizer save, and any settings change on the CSS tab. Deliberately blunter than Mode A's per-URL purge — a stale Mode A page is slightly wrong, whereas a stale Mode B page is unstyled.
* Change: Mode A stands down entirely when Mode B is enabled, as do Async CSS and Combine CSS. The two modes are alternatives and only one can own CSS delivery. Hand-pasted Critical CSS still takes precedence over both on any page with a matching slot.
* New: the Performance Doctor understands both modes. It recommends one or the other and never both, and when Mode B is on it reports whether the scanned page is actually free of render-blocking stylesheets, still learning, or left with sheets Mode B declines to remove.
* New: ?mbrpe_modeb=off serves a single request with its original stylesheets, for comparing the optimised page against the original without switching the feature off site-wide.
* New: per-template cache table on the CSS tab showing samples collected, stylesheets replaced, inlined size and learning status, plus a Clear template cache control.

= 1.22.1 =
* New: the Performance Doctor now understands script modules. Until now it skipped them entirely — it stepped over `type="module"` when counting render-blocking scripts (correctly, since modules do not block rendering) and then had nothing further to say about them. On a classic theme loading Interactivity API code it would report a clean bill of health while every module preload hint sat uselessly in the footer. It now counts the modules on the page, reports where the import map and each preload hint actually landed, and recommends preload hoisting when it would help.
* New: import map ordering check. An import map has to be parsed before any module that depends on it. If a theme or plugin prints its own module tag directly into the head while WordPress prints the map in the footer, every bare specifier import in that module fails to resolve — a real breakage that produces console errors rather than a slow page. The Doctor now flags this as a high-priority finding.
* New: the Doctor distinguishes between hoisting being off, hoisting being on but still learning the URL, and hoisting working — so "I enabled it and nothing happened" is answered on screen rather than left to guesswork. Block themes are told plainly that core already handles this and there is nothing to change.
* New: the Doctor's summary card now reports module counts, preload hint placement and import map position alongside the existing CSS, JS and image figures.
* Note: module counts are kept separate from the render-blocking JavaScript figure rather than folded into it. Modules defer by specification and never block first paint, so counting them as render-blocking would overstate the problem the rest of the report is describing.
* Note: the learned module map is keyed on the plugin version, so upgrading clears it by design. The first front-end visit to each URL after this update relearns its module set, and hoisted hints resume from the second visit.

= 1.22.0 =
* New: Script Modules and Interactivity API support for WordPress 6.5+. Modules are printed by WordPress separately from ordinary scripts, so the classic defer / delay / combine passes never see them — which is correct, but it also meant the plugin had nothing to offer pages that use them. This release adds the piece core leaves on the table.
* New: module preload hoisting. On classic themes WordPress discovers modules while the body renders, so it prints the import map and all modulepreload hints in the footer — by which point a hint arrives at the same moment as the script it was meant to front-run. The plugin now learns each URL's module set on first visit and emits the hints in the head from then on. Block themes already receive head hints from core and are left untouched.
* New: static dependency graph walking, so a module's imports are hinted alongside it. Dynamic imports are excluded on purpose — they load on demand, and preloading them would fetch bytes that may never be needed.
* New: optional high fetchpriority for nominated module IDs, applied through the core API on WordPress 6.9+ and ignored safely on earlier versions, plus a per-page preload cap and an exclusion list.
* Note: preload URLs are resolved exactly as core resolves them, including the three version cases (explicit version, core version, and no version at all). A mismatched URL would cause the browser to download a module twice, so this is matched precisely rather than approximated.
* Off by default, and the whole feature is a complete no-op on WordPress below 6.5.

= 1.21.3 =
* Fix: the Doctor's PDF report preview no longer stretches to the full browser width. The A4 page margin only applies at print time, so on screen the report had no page container at all — it now previews as a proper centred A4 sheet on a grey backdrop, and resets cleanly for printing so the page margin is not doubled up.
* Fix: added the missing "Note" badge styling in the PDF report, so real-user field notes render correctly rather than as an unstyled badge.
* Fix: settings links on Doctor recommendation cards now use proper tab names ("Open RUM settings" rather than "Open rum settings").

= 1.21.2 =
* Fix: real-user field data now appears in the Doctor's "Scan key templates" view. The site roll-up strips info-tier notes by design (they are per-page context, not site recommendations), which silently discarded the RUM status note — so a site scan reported "no actionable recommendations" while RUM was sitting on real data. Field data is now attached once at the site level, after that filter, since it is site-wide rather than per-template.
* Change: field data is no longer repeated under every template in a site scan. A global metric like INP is not a property of one template, so it is reported once for the site.
* Change: the provisional field note now shows the actual readings (for example "LCP 1.47s (4 samples), INP 8ms (3 samples)") instead of only saying the data is thin — provisional numbers are still worth seeing.
* Fix: a site scan with nothing actionable now shows both the all-clear card and any field-data notes, rather than one suppressing the other. Field notes also render with a proper "Note" label in the PDF report.

= 1.21.1 =
* Fix: RUM field data now appears immediately instead of waiting up to 24 hours for the nightly cron. The Performance Doctor and the RUM scorecard both read the daily aggregates table, which previously only the cron populated — so after enabling RUM you could watch raw samples accumulate while the Doctor stayed silent. Aggregation now also runs on demand (throttled to once a minute) whenever the RUM tab or the Doctor is opened and new raw samples are waiting.
* New: "Run aggregation now" button on the RUM tab for an immediate refresh.
* Fix: the Doctor is no longer silent when RUM is enabled but has no usable aggregates. It now reports what is actually happening — still collecting, provisional data, or Core Web Vitals passing — so an unhelpful blank is never mistaken for a broken feature.
* Change: the sample threshold for acting on field data drops from 20 to 10, and provisional p75s are now shown greyed and marked rather than hidden behind a dash. Low-traffic and staging sites get useful numbers far sooner.
* Fix: the RUM module now initialises before the page-builder editor early-return, so its aggregation cron and admin handlers register on every request rather than being skipped in editor contexts.

= 1.21.0 =
* New: Real User Monitoring (RUM). Collects real-user Core Web Vitals — LCP, CLS and INP — from actual visitors and stores them in two local tables (`{prefix}mbrpe_rum_raw` and `{prefix}mbrpe_rum_agg`). A tiny front-end beacon (built on Google's open-source web-vitals attribution library, vendored locally, never from a CDN) posts each metric to a first-party REST route (`mbrpe/v1/rum`, POST-only so full-page caches never cache it); a nightly cron rolls raw samples into per-template and per-URL daily p75s and purges the raw. This is field data to complement the Performance Doctor's synthetic render — and the only way to observe INP, which cannot exist without a real interaction.
* New: RUM admin tab with a Core Web Vitals scorecard (p75 plus good/needs-improvement/poor distribution and pass thresholds), a per-template breakdown split by device, a worst-offenders table naming the specific URLs and the element or handler most often responsible, a data-health row, and a Clear RUM data control.
* New: the Performance Doctor now folds real-user field data into its recommendations. When RUM has enough samples it leads with what visitors actually experienced — including INP, which the synthetic scan cannot see at all — names the p75 and the most common culprit element or handler, and points at the right settings tab. Falls back cleanly to synthetic-only when RUM is off or sparse.
* Privacy: no cookies, no IP storage, no user-agent retention (reduced to a coarse device-class and browser-family at write time), configurable sampling and optional exclusion of logged-in sessions. Nothing leaves your server — RUM makes no external requests.
* Hardening: the plugin's own `mbrpe/v1` REST namespace is now always permitted through the Core tab's REST API hardening modes, so the RUM beacon keeps working for logged-out visitors even when "Disable When Logged Out" is set.
* Off by default. On upgrade the tables are created and the nightly aggregation cron is scheduled; enable collection from the RUM tab. Test on staging first.


= 1.20.1 =
* Fixed: "Move scripts to footer" no longer relocates the jQuery foundation (jquery, jquery-core, jquery-migrate). Moving these split the dependency graph — jquery-migrate could be left in the head while jquery-core dropped to the footer, producing "jQuery is not defined" and silently breaking jQuery-dependent widgets (Elementor accordions, ElementsKit, and other page-builder handlers). jQuery now always stays in the head; the exclusion list continues to apply to every other script. A new mbrpe_footer_protected_handles filter lets advanced users adjust the protected set.

= 1.19.0 =
* New: MBR Performance Doctor. Analyses a real front-end page and recommends, in priority order, which settings will actually help this site — including telling you which to leave off — instead of presenting a wall of switches. v1 diagnoses the render-blocking CSS-vs-JavaScript split (the most decisive factor) and links each recommendation straight to the relevant setting, skipping anything already enabled. Advisory only; it never changes settings automatically.
* New: Doctor image pass — flags images missing width/height (layout shift), JPEG/PNG that next-gen formats would shrink, and below-the-fold images not lazy-loaded, each routed to the setting that fixes it.
* New: Doctor multi-template scan — auto-detects key templates (home, blog, a post, a page, WooCommerce shop/product) and aggregates findings into site-wide vs page-specific recommendations.
* New: Branded, print-ready PDF report of the site scan for client hand-off; generated client-side, so it adds no plugin weight and works on any host.
* New: First-run nudge steering newcomers to the Doctor; per-user dismissal, auto-dismisses once a scan is run.
* Fix: Disable Google Fonts now also strips Google Fonts hardcoded directly into the page (theme header <link> tags, preconnects, inline @font-face and @import) that bypass WordPress's enqueue system, via a guarded final-output pass.
* Improvement: Combine CSS is now automatically stood down when Used CSS (Mode A) is active — the two are alternatives, and Mode A already owns CSS delivery. The CSS panel notes this, and the Doctor recommends one or the other rather than both.

= 1.18.0 =
* New: Used CSS (Mode A). For each page, MBR Performance extracts only the CSS the delivered page actually uses, inlines it in the head, and loads the full stylesheets asynchronously as a fallback — so render-blocking unused CSS is eliminated without hard-breaking JavaScript-driven styles, because the originals are deferred rather than removed. Used CSS is generated in the background after the first visit to each page and cached per URL. The former "Remove Unused CSS" toggle is relabelled "Generate Used CSS" and now drives this feature.
* New: Per-page used-CSS cache with size/count display and a Clear used CSS cache control on the CSS tab; cache is purged automatically on settings save, post edit, and theme/plugin updates.
* New: After generating a page's used CSS, that single URL is purged from the host's full-page cache so the optimised version is the one re-cached — supports SiteGround Speed Optimizer via sg_cachepress_purge_cache(), with an mbrpe_usedcss_purge_url action for other hosts.
* Improvement: When Used CSS is on it owns CSS delivery — the standalone Async CSS layer is suppressed automatically to prevent the two from double-deferring the same stylesheets.
* Bundles the Sabberworm PHP-CSS-Parser and Symfony CssSelector components (loaded only during generation) to match selectors against the rendered DOM.

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

= 1.13.0 =
* Feature: New "Disable AI Features (WordPress 7.0+)" toggle on the Core tab, under WordPress Features. WordPress 7.0 ships a built-in AI Client, the Abilities API, and a Settings -> Connectors screen for wiring a site to AI providers. That infrastructure stays dormant until a provider connector is configured, so the front-end cost on a default install is minimal — but for site owners who want nothing to do with it, this toggle switches the whole subsystem off rather than leaving it idling. It hooks core's own kill switch (`add_filter( 'wp_supports_ai', '__return_false' )`) at PHP_INT_MAX priority so the AI Client and Abilities API never bootstrap, plus `wp_ai_client_prevent_prompt` as a second guard against any prompt execution that slips through.
* Note: The toggle is off by default and existing behaviour is unchanged on upgrade. It has no effect on WordPress 6.x, where the `wp_supports_ai` filter does not exist, so it is safe to leave enabled across mixed-version sites. The Connectors admin screen is intentionally left in place — this is a performance and surface-area control, not a dashboard-hiding tool.
* Tested up to WordPress 7.0.

= 1.12.0 =
* Fix: JavaScript optimisations module — previously a placeholder class with UI toggles but no backend logic — is now fully wired up. Defer, Move-to-Footer, Defer jQuery, Remove jQuery (with test mode), Minify inline JS, Delay JS (with interaction-triggered runtime and configurable timeout), Disable Concatenation, and Remove Script Versions all work as advertised. Each defer/footer/delay path honours its own exclusion textarea.

* Fix: CSS optimisations module — previously a placeholder class — is now fully wired up. Inline Critical CSS, Async CSS (via preload+onload with the standard loadCSS polyfill for older browsers), Minify inline CSS, Conditional Block Styles (should_load_separate_core_block_assets), Remove CSS Versions, Disable Elementor Google Fonts, and Disable WooCommerce CSS on non-shop pages all functional.

* Fix: Database optimisations module — previously had only the WooCommerce cron listener — now drives full scheduled cleanup. The `mbr_wp_performance_database_cleanup` cron auto-reschedules to match the cleanup_schedule setting (daily/weekly/manual) and runs: auto-draft purge with configurable age, trash emptying with configurable retention, spam comment deletion with configurable age, unapproved comment deletion with configurable age, expired transient cleanup (handles site transients on multisite), and revision trimming to the keep-N setting. A "Last Auto-Cleanup" log table is displayed on the Database tab plus a "Run Auto-Cleanup Now" button.

* Feature: AVIF image conversion alongside WebP. New `<picture>` wrapper emits AVIF first, then WebP, then the JPEG/PNG fallback — browsers automatically pick the first format they support. AVIF is typically 20-30% smaller than WebP at equivalent perceived quality. Requires PHP 8.1+ with GD AVIF, or Imagick 7.0.25+. Falls back gracefully where unsupported; new tab section in WebP tab includes server capability diagnostics.

* Feature: Self-hosted third-party scripts. New "Third-Party" tab with per-script toggles for Google Analytics (gtag.js), Google Tag Manager (gtm.js), legacy Google Analytics (analytics.js), and Facebook Pixel (fbevents.js). Scripts are downloaded daily to /wp-content/uploads/mbr-performance/third-party/ and outbound <script src=> URLs are rewritten via output buffer. Removes the PSI "Reduce the impact of third-party code" warning and stops first-paint requests to googletagmanager.com / connect.facebook.net.

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

= 2.1.5 =
Removes a PHP 8.1+ "strip_tags(): Passing null" deprecation notice on the settings screen, and gives the screen a proper browser-tab title.

= 2.1.4 =
Used CSS Mode B: a static front page, cart and checkout now show as learned instead of "Still learning" forever. Display fix only; nothing to clear.

= 2.1.3 =
Minify CSS and Minify JavaScript now minify your stylesheet and script files; previously they had no effect. Updating clears the page cache once.

= 2.1.2 =
Fixes Used CSS never reaching visitors when the page cache is enabled. Updating clears the page cache once. Recommended for every site using Used CSS with page caching.

= 2.1.1 =
Fixes the OPcache panel reporting OPcache as switched off on servers running it in file-cache-only mode, including SiteGround. Recommended for everyone on 2.1.0.

= 2.1.0 =
Adds OPcache status and a Flush OPcache button to the Diagnostics tab, makes the plugin's own PHP files take effect immediately on hosts that do not check for changes, and fixes the Server tab being unable to switch its rules off.

= 2.0.2 =
Fixes static (.htaccess) cache hits being sent with X-Robots-Tag: noindex, which asked search engines not to index cached pages. Recommended for every site using static serving. No purge or settings change needed.

= 1.22.0 =
Adds Script Modules and Interactivity API support, including module preload hoisting that fixes late preload hints on classic themes. Off by default.

= 1.21.3 =
Fixes the PDF report preview stretching to browser width, plus report and label polish.

= 1.21.2 =
Fixes real-user field data not appearing in the Doctor's site-scan view. Recommended for anyone running 1.21.0 or 1.21.1.

= 1.21.1 =
Fixes RUM field data not reaching the Performance Doctor until the nightly cron ran. Aggregation now happens on demand, with a manual button and clearer status reporting. Recommended for anyone running 1.21.0.

= 1.21.0 =
Adds self-hosted Real User Monitoring: collects real-user Core Web Vitals (LCP, CLS, INP) into local tables and shows field data per template and per URL. No cookies, no IPs, nothing leaves your server. Off by default — enable from the RUM tab. Creates two database tables and a nightly aggregation cron on upgrade.


= 1.18.0 =
Adds Used CSS (Mode A): inlines per-page critical CSS and async-loads the full stylesheets. Enable via CSS > Generate Used CSS. Test on staging first.

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

= 1.5.0 =
Adds full WordPress Multisite support — manage performance settings across your entire network from one place. Backup before upgrading.

= 1.4.9 =
Major update with lazy loading, preloading, improved font management, and better page builder compatibility. Backup before upgrading and test features individually.

