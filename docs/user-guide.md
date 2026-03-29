# MBR WP Performance - Complete User Guide

Welcome to MBR WP Performance! This comprehensive guide will help you optimize your WordPress site for blazing-fast performance.

## Table of Contents

1. [Introduction](#introduction)
2. [Getting Started](#getting-started)
3. [Core Features](#core-features)
4. [JavaScript Optimization](#javascript-optimization)
5. [CSS Optimization](#css-optimization)
6. [Font Optimization](#font-optimization)
7. [Preloading](#preloading)
8. [Lazy Loading](#lazy-loading)
9. [Database Optimization](#database-optimization)
10. [WebP Image Conversion](#webp-image-conversion)
11. [Multisite Network Support](#multisite-network-support)
12. [Best Practices](#best-practices)
13. [Troubleshooting](#troubleshooting)
14. [FAQ](#faq)

---

## Introduction

### What is MBR WP Performance?

MBR WP Performance is an all-in-one WordPress performance optimization plugin designed to give you complete control over your site's speed and efficiency. Unlike many performance plugins that hide complexity, this plugin provides transparent, granular controls for every optimization technique.

### Why Performance Matters

- **User Experience**: 53% of mobile users abandon sites that take longer than 3 seconds to load
- **SEO Rankings**: Google uses site speed as a ranking factor
- **Conversion Rates**: A 1-second delay can reduce conversions by 7%
- **Server Costs**: Optimized sites use fewer server resources

### Key Features at a Glance

- ⚡ **JavaScript & CSS Optimization**: Defer, combine, and minify scripts
- 🎨 **Font Management**: Self-host Google Fonts, preload critical fonts
- 🖼️ **Smart Loading**: Lazy load images and videos, preload critical resources
- 🗄️ **Database Cleanup**: Remove bloat, optimize tables, scheduled maintenance
- 📷 **WebP Conversion**: Convert images to WebP with automatic fallback delivery
- 🌐 **Multisite Support**: Network-wide settings with per-site override control
- 🎯 **Granular Control**: Enable/disable individual optimizations
- 🔧 **Developer-Friendly**: Clean code, no vendor lock-in

---

## Getting Started

### Installation

1. **Upload the Plugin**
   - Go to `Plugins > Add New` in WordPress admin
   - Click `Upload Plugin`
   - Choose `mbr-wp-performance-v1.6.0.zip`
   - Click `Install Now`

2. **Activate**
   - Click `Activate Plugin`

3. **Access Settings**
   - Click **WP Performance** in the admin toolbar (top right)
   - Or use the dropdown to jump directly to a specific tab

### First-Time Setup

**IMPORTANT**: Before making any changes:

1. ✅ **Create a full backup** of your site
2. ✅ **Test on a staging site** if possible
3. ✅ **Enable features one at a time**
4. ✅ **Test thoroughly** after each change

### Recommended Setup Order

Follow this order for safest implementation:

1. **Database** → Clean up unnecessary data (safest, immediate impact)
2. **Fonts** → Self-host Google Fonts
3. **Lazy Loading** → Enable for images and videos
4. **WebP** → Check diagnostics, run bulk conversion, enable auto-convert
5. **Preloading** → Preload critical resources
6. **CSS** → Enable critical CSS and minification
7. **JavaScript** → Defer scripts carefully
8. **Core Features** → Fine-tune WordPress features

---

## Core Features

Access via: **WP Performance > Core Features**

This tab controls fundamental WordPress features that often add unnecessary overhead.

### Disable Emojis

**What it does**: Removes WordPress emoji support (saves ~15KB on every page)

**When to use**: 
- ✅ If you don't use emojis in content
- ✅ Almost all modern sites (emojis work natively in browsers)

**Impact**: Low risk, small improvement

```
Before: Loads wp-emoji-release.min.js (15KB)
After: No emoji script loaded
```

### Disable Embeds

**What it does**: Removes WordPress oEmbed functionality

**When to use**:
- ✅ If you don't embed YouTube, Twitter, etc. in posts
- ❌ Keep enabled if you paste URLs and expect auto-embeds

**Impact**: Medium risk if you use embeds

### Disable Dashicons (Frontend)

**What it does**: Prevents loading Dashicons font on frontend (saves ~30KB)

**When to use**:
- ✅ Almost always (Dashicons are for admin only)
- ❌ Only if a plugin uses Dashicons on frontend

**Impact**: Low risk, medium improvement

### Remove Query Strings

**What it does**: Removes `?ver=1.2.3` from CSS/JS URLs

**When to use**:
- ✅ To improve caching with some CDNs
- ❌ May cause issues after updates (users see cached old files)

**Recommendation**: Use only if you have strong cache busting elsewhere

### Disable REST API for Non-Logged Users

**What it does**: Blocks REST API access for guests

**When to use**:
- ✅ If you don't have a headless frontend
- ❌ Don't use if you have a mobile app or third-party integrations

**Security benefit**: Reduces information disclosure

### Heartbeat Control

**What it does**: Controls WordPress Heartbeat frequency

**Options**:
- **Default**: Normal (every 15-60 seconds)
- **Reduce**: Slower (every 60 seconds)
- **Disable**: Completely off

**When to use**:
- ✅ **Reduce** for most sites (saves server resources)
- ⚠️ **Disable** only if you don't use post editing (breaks auto-save)

**Impact on features**:
- Post auto-save
- Plugin/theme notifications
- Session management

### Limit Post Revisions

**What it does**: Limits how many revisions WordPress keeps

**Recommended**: 5-10 revisions
**Default**: Unlimited (can bloat database)

**How it works**:
```
Post with unlimited revisions = 50+ database rows
Post limited to 5 revisions = 5 database rows
```

### Autosave Interval

**What it does**: Changes how often WordPress auto-saves your work

**Default**: 60 seconds
**Recommended**: 120-300 seconds

**Trade-off**: Longer interval = less server load, but more work lost if browser crashes

---

## JavaScript Optimization

Access via: **WP Performance > JavaScript**

JavaScript is often the biggest performance bottleneck. These settings help you control when and how scripts load.

### Defer JavaScript

**What it does**: Delays script execution until after page render

**Impact**: ⭐⭐⭐⭐⭐ (Huge)

**How it works**:
```html
<!-- Before -->
<script src="script.js"></script>
<!-- Blocks page rendering -->

<!-- After -->
<script src="script.js" defer></script>
<!-- Loads in parallel, executes after page -->
```

**When to use**:
- ✅ Almost all scripts benefit from defer
- ⚠️ Test carefully - some scripts need to run immediately

**Exclude from defer**:
- Scripts that must run immediately (rare)
- Scripts other scripts depend on (jQuery sometimes)
- Inline scripts with external dependencies

### Move Scripts to Footer

**What it does**: Moves all scripts to bottom of page

**Benefit**: Browser renders page before processing scripts

**Caution**: Some plugins expect scripts in `<head>`

### jQuery Optimization

**Options**:
1. **Move to Footer**: Safe for most sites
2. **Disable jQuery Migrate**: Removes compatibility layer (saves ~10KB)
3. **Remove jQuery**: Nuclear option - breaks most plugins

**Recommended**:
- ✅ Move to footer
- ✅ Disable jQuery Migrate if everything still works
- ❌ Don't remove jQuery unless you know what you're doing

### Delay JavaScript Execution

**What it does**: Delays loading of non-critical scripts until user interaction

**Perfect for**:
- Analytics (Google Analytics, Facebook Pixel)
- Chat widgets (Intercom, Drift)
- Social sharing buttons
- Comment systems

**How to configure**:
```
Enter keywords or script handles:
google-analytics
gtag
facebook
intercom
```

**User triggers**:
- Mouse movement
- Scroll
- Keyboard press
- Touch (mobile)

**Impact**: ⭐⭐⭐⭐ Major improvement in initial load time

### Minify JavaScript

**What it does**: Removes whitespace, comments, and shortens variable names

**Example**:
```javascript
// Before (10 KB)
function calculateTotal( price, quantity ) {
    // Calculate the total
    return price * quantity;
}

// After (2 KB)
function c(p,q){return p*q}
```

**When to use**:
- ✅ If you don't have another minification solution
- ⚠️ Test thoroughly - aggressive minification can break code

### Combine JavaScript

**What it does**: Merges multiple scripts into one file

**Benefits**:
- Fewer HTTP requests
- Better compression

**Downsides**:
- Large combined file
- Breaks browser caching (one script change = redownload everything)

**Modern recommendation**: 
- ❌ Usually not needed with HTTP/2
- ✅ Only for HTTP/1.1 servers

---

## CSS Optimization

Access via: **WP Performance > CSS**

CSS optimization improves render speed and reduces file sizes.

### Critical CSS

**What it does**: Inlines above-the-fold CSS directly in HTML

**The Problem**:
```html
<!-- Normal CSS loading -->
<link rel="stylesheet" href="style.css">
<!-- Browser must download CSS before rendering -->
<!-- User sees blank page during download -->
```

**The Solution**:
```html
<!-- Critical CSS inline -->
<style>
    /* Above-fold styles here */
    header { ... }
    .hero { ... }
</style>
<!-- Page renders immediately -->
```

**How to use**:

1. **Auto-Generate**:
   - Click "Auto-Generate Critical CSS"
   - Plugin scans your homepage
   - Extracts above-fold styles
   - Paste result in textarea

2. **Manual**:
   - Use tools like [Critical CSS Generator](https://jonassebastianohlsson.com/criticalpathcssgenerator/)
   - Paste CSS in textarea

3. **Enable "Inline Critical CSS"** checkbox

**Impact**: ⭐⭐⭐⭐⭐ Eliminates render-blocking CSS

### Async Load CSS

**What it does**: Loads stylesheets without blocking page render

**Perfect for**:
- Non-critical stylesheets
- Footer styles
- Print stylesheets

**How it works**:
```html
<!-- Before: Blocks rendering -->
<link rel="stylesheet" href="non-critical.css">

<!-- After: Loads asynchronously -->
<link rel="preload" href="non-critical.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
```

### Minify & Combine CSS

**Minify**: Removes whitespace and comments
**Combine**: Merges multiple CSS files

**Example**:
```css
/* Before: 15 KB, well-formatted */
.button {
    background-color: #0073aa;
    padding: 10px 20px;
    /* Primary button style */
}

/* After: 8 KB, minified */
.button{background-color:#0073aa;padding:10px 20px}
```

**Recommendation**:
- ✅ **Minify**: Almost always beneficial
- ⚠️ **Combine**: Test carefully, can increase total size

### Remove Unused CSS

**What it does**: Scans pages and removes CSS selectors not found in HTML

**How to use**:

1. Click **"Scan Site for Used CSS"**
2. Wait for scan to complete
3. Review results
4. Enable "Remove Unused CSS"

**CAUTION**: ⚠️⚠️⚠️ High risk!

**Problems**:
- Dynamic content (JavaScript-generated) won't be detected
- Interactive states (hover, focus) may be removed
- Different pages may need different styles

**Recommendation**: 
- ❌ Skip for complex sites
- ⚠️ Test extensively if you enable

### Block Editor Styles

**Remove Global Styles**: Removes FSE theme CSS (~30KB)
**Load Block Styles Conditionally**: Only loads CSS for blocks actually used

**Impact**: Medium improvement for block theme users

---

## Font Optimization

Access via: **WP Performance > Fonts**

Fonts can significantly impact load time. Proper optimization is crucial.

### The Font Problem

```
Typical Google Fonts load:
1. Browser loads HTML
2. Discovers Google Fonts CSS link
3. Downloads CSS from fonts.googleapis.com
4. CSS references fonts on fonts.gstatic.com
5. Downloads font files
6. Text renders

Total: 2-3 seconds, 3 external requests, FOUT (Flash of Unstyled Text)
```

### Solution: Self-Host Google Fonts

**What it does**: Downloads Google Fonts to your server

**Benefits**:
- ✅ Faster loading (same server)
- ✅ Privacy (no Google tracking)
- ✅ Complete control
- ✅ Works offline

**How to use**:

1. **Enable "Self-Host Google Fonts"**

2. **Add Fonts Manually**:
   ```
   Format: Font Name:weights
   
   Examples:
   Poppins:400,500,700
   Open Sans:300,400,600
   Roboto
   ```

3. **Click "Download Fonts"**

4. **Verify** in "Currently Downloaded Fonts" list

**What happens**:
- Plugin downloads .woff2 files
- Creates local CSS files
- Replaces Google CDN links with local files

### Font Preloading

**What it does**: Tells browser to load fonts immediately

**Enable**: "Preload Critical Fonts" checkbox

**How to specify fonts**:
```
Enter local paths:
/wp-content/uploads/mbr-wp-performance-fonts/Poppins-400.woff2
```

**Impact**: ⭐⭐⭐⭐ Eliminates font loading delay

### Disable Google Fonts Completely

**When to use**:
- ✅ If switching to system fonts
- ✅ Maximum privacy
- ✅ Fastest option (no web fonts at all)

**Enable**: "Disable Google Fonts" checkbox

**Set fallback stack**:
```css
-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, 
Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif
```

### Google Fonts Optimization

**Options**:

1. **Default**: No optimization
2. **Combine Requests**: Merges multiple font requests into one
3. ~~Self-Host~~ (configured above)
4. ~~Disable~~ (configured above)

**Font Display Strategy**:

- **swap** (Recommended): Show fallback immediately, swap when font loads
- **block**: Wait for font, show invisible text (up to 3s)
- **fallback**: Brief block, then fallback if slow
- **optional**: Use font only if already cached

**Recommendation**: Use `swap` for best user experience

### Font Subsetting

**What it does**: Only includes characters you need

**Example**:
```
Full Poppins font: 120 KB (all Latin, Cyrillic, etc.)
English-only subset: 40 KB (just A-Z, a-z, 0-9, punctuation)
```

**Languages to include**:
- ✅ English (latin)
- Add others only if needed (latin-ext, cyrillic, etc.)

### Font Awesome Optimization

**Disable Font Awesome**: If you don't use it (saves ~80 KB)

**Benefits**: Many themes load Font Awesome but never use it

---

## Preloading

Access via: **WP Performance > Preloading**

Preloading tells the browser to fetch critical resources immediately.

### Preload Critical Images

**What it does**: Loads your hero image / LCP image early

**The Problem**:
```
Normal flow:
1. HTML loads
2. CSS loads
3. CSS references background image
4. Image loads (finally!)

User sees blank hero section for 2-3 seconds
```

**The Solution**:
```html
<link rel="preload" href="/hero-image.jpg" as="image">
<!-- Browser fetches immediately -->
```

**How to configure**:

1. Set number of images: `1-3` (usually just 1)
2. Or add specific URLs:
   ```
   https://yoursite.com/wp-content/uploads/hero.jpg
   https://yoursite.com/logo.png
   ```

**Best practice**: Preload only your largest above-fold image

### Cloudflare Early Hints

**What it does**: Sends HTTP 103 response to start loading before HTML

**Requirements**:
- ✅ Site hosted on Cloudflare
- ✅ Cloudflare plan with Early Hints

**Impact**: ⭐⭐⭐ Shaves 200-500ms off load time

### Fetch Priority

**What it does**: Adds `fetchpriority="high"` to critical images

**How it works**:
```html
<img src="hero.jpg" fetchpriority="high">
<!-- Browser prioritizes this over other images -->
```

**Options**:

1. **Automatic**: First image on page gets high priority
2. **Custom selectors**: 
   ```
   .hero-image
   #main-banner
   .featured-image
   ```

**Recommendation**: Enable automatic mode

### Speculative Loading

**What it does**: Prefetches/prerenders pages users are likely to visit

**Modes**:

1. **Prefetch**: Downloads next page's HTML in background
2. **Prerender**: Fully renders next page (instant navigation!)
3. **Auto**: Browser decides

**Eagerness levels**:

- **Conservative**: On hover (desktop) or touch (mobile)
- **Moderate**: On mouse down (before click completes)
- **Eager**: Immediately for likely links

**How it works**:
```html
<a href="/next-page" data-prefetch>
<!-- Browser downloads /next-page in background -->
<!-- Click feels instant! -->
```

**Best for**:
- Blog "next article" links
- Pagination
- Likely navigation paths

**Recommendation**: 
- Mode: **Prefetch** (prerender uses lots of bandwidth)
- Eagerness: **Conservative** (good balance)

---

## Lazy Loading

Access via: **WP Performance > Lazy Loading**

Lazy loading delays loading resources until they're needed.

### Lazy Load Images

**What it does**: Only loads images when they scroll into view

**The savings**:
```
Page with 50 images:
Without lazy loading: 50 images × 100 KB = 5 MB initial load
With lazy loading: 5 visible images × 100 KB = 500 KB initial load

90% bandwidth saved!
```

**How it works**:
```html
<img src="image.jpg" loading="lazy">
<!-- Browser loads only when image is near viewport -->
```

**Enable**: Check "Lazy Load Images"

**Impact**: ⭐⭐⭐⭐⭐ Massive improvement for image-heavy sites

### Lazy Load iFrames and Videos

**What it does**: Delays loading YouTube, Vimeo, maps, etc.

**Why it matters**:
- Single YouTube embed = ~500 KB of scripts
- Google Maps embed = ~800 KB
- Lazy loading = only when user scrolls to it

**Perfect for**:
- Video embeds
- Google Maps
- Social media embeds

### Exclusions

**Why exclude?**: Some images should NOT be lazy loaded

**Exclude by selector**:
```
.logo
#hero-image
.no-lazy
[data-no-lazy]
```

**Exclude by keyword**:
```
logo
hero
banner
header
```

**Automatic exclusions**: First few images are automatically excluded (prevent LCP issues)

**Best practice**: Exclude:
1. Logo
2. Hero/banner images
3. First 2-3 images
4. Any image in viewport on load

---

## Database Optimization

Access via: **WP Performance > Database**

Over time, WordPress databases accumulate junk. Regular cleanup is essential.

### Post Revisions

**The problem**:
```
100 posts × 50 revisions each = 5,000 database rows
Most revisions are never used
```

**How to clean**:

1. Set "Keep Revisions": `5`
2. Click **"Scan for Excess Revisions"**
3. Review count
4. Click **"Delete Excess Revisions"**

**Result**: "Deleted 4,850 revisions" (97% cleanup!)

**Recommendation**: Run monthly

### Auto-Delete Old Drafts

**What it does**: Automatically removes drafts older than X days

**Settings**:
```
Auto-delete drafts older than: 30 days
Auto-delete trash older than: 7 days
```

**Who needs this**: Sites with multiple authors, lots of drafts

### Orphaned Data Cleanup

**What is orphaned data?**:
```
Post deleted = post_id removed
But... post metadata stays in database!
Result: thousands of orphaned rows
```

**Types**:
- Post meta (custom fields from deleted posts)
- Comment meta (data from deleted comments)
- Term meta (taxonomy data from deleted terms)
- Relationships (deleted post/term links)

**How to clean**:

1. Click **"Scan [Type]"**
2. Review: "Found: 2,459 orphaned entries"
3. Click **"Delete [Type]"**

**Safety**: Very safe - only deletes data with no parent

**Recommendation**: Quarterly cleanup

### Transients

**What are transients?**: Temporary cached data

**The problem**: Expired transients stay in database

**How to manage**:

1. Click **"Get Stats"**: See count of expired/total
2. **"Delete Expired Transients"**: Safe, recommended
3. **"Delete All Transients"**: Nuclear option (may cause temporary slowdown)

**Recommendation**: Delete expired monthly

### Table Optimization

**What it does**: Defragments database tables (like defragging a hard drive)

**When to use**: After bulk deletions

**How**:
1. Click **"Optimize Tables"**
2. Wait for "Optimized 15 tables, freed 2.3 MB"

**Impact**: Small but cumulative

### Convert to InnoDB

**What it does**: Converts old MyISAM tables to modern InnoDB

**Benefits**:
- Better crash recovery
- Row-level locking (faster for concurrent access)
- Better performance

**When to use**: If you have MyISAM tables (rare in modern WP)

**CAUTION**: Test on staging first!

### Scheduled Cleanup

**What it does**: Automatically runs cleanup tasks

**Configure**:
```
Schedule: Weekly
Actions:
☑ Delete old revisions
☑ Delete expired transients
☑ Delete old auto-drafts
☐ Delete spam comments (optional)
```

**Recommendation**: Enable for hands-off maintenance

---

## WebP Image Conversion

Access via: **WP Performance > WebP**

WebP is a modern image format developed by Google that delivers significantly smaller file sizes than JPEG and PNG while maintaining equivalent visual quality. The WebP tab lets you convert your entire Media Library and serve WebP images automatically.

### Server Diagnostics

Before converting anything, the diagnostics panel at the top of the tab checks three requirements:

1. **GD Library installed** — the PHP image processing library
2. **WebP support in GD** — not all GD builds include WebP
3. **Uploads folder writable** — the plugin needs to write files alongside your originals

All three must show green ticks before conversion will work. If any fail, contact your host.

### Settings

**Automatic Conversion**

Converts new JPG, JPEG, and PNG images to WebP the moment they're uploaded through the Media Library. This includes all WordPress-generated thumbnail sizes, so every variant gets a WebP copy.

**Compression Level**

Controls the quality/size trade-off for WebP output. The scale runs from 1 (smallest file, lowest quality) to 100 (best quality, largest file). The default is 75, which gives excellent results for most sites — typically 80–95% smaller than the original with no visible quality loss.

```
Compression 75 (default):  hero-top.png 2.66 MB → hero-top.webp 125 KB (95% saving)
Compression 90 (high):     hero-top.png 2.66 MB → hero-top.webp 280 KB (89% saving)
Compression 50 (aggressive): hero-top.png 2.66 MB → hero-top.webp 65 KB (97% saving)
```

**HTML `<picture>` Tags**

Wraps images in `<picture>` elements with a WebP `<source>`. This is the recommended delivery method because it works on any server (Apache, Nginx, CDN) and falls back to the original format for browsers without WebP support.

```html
<!-- Before -->
<img src="photo.jpg" alt="Example">

<!-- After -->
<picture>
    <source type="image/webp" srcset="photo.webp">
    <img src="photo.jpg" alt="Example">
</picture>
```

Works with Gutenberg blocks, Elementor widgets, classic editor content, post thumbnails, and `wp_get_attachment_image()` output. Automatically skipped inside page builder editors and the admin area.

**.htaccess Rewrite Rules**

An alternative delivery method for Apache and LiteSpeed servers. Adds server-level rules that transparently serve the WebP file when the browser supports it, without modifying any HTML.

Not needed if you're using `<picture>` tags (which is the more reliable option). Does not work on Nginx — a warning is shown if Nginx is detected.

### Bulk Converter

Converts existing images in your Media Library that were uploaded before the auto-convert setting was enabled.

**How to use**:

1. Click **"Start Conversion"**
2. The plugin scans your uploads folder for JPG, JPEG, and PNG files that don't already have a WebP counterpart
3. Each image is converted one at a time with a progress bar
4. Results appear in the Conversion History table below

**Smart skip**: If the WebP output would be larger than the original (rare, but possible with very small or already-compressed images), the file is skipped and the original is left as-is.

### Where WebP Files Are Stored

WebP files are created alongside the originals in the same uploads folder:

```
wp-content/uploads/2025/03/hero-top.png          ← original (untouched)
wp-content/uploads/2025/03/hero-top.webp          ← WebP copy
wp-content/uploads/2025/03/hero-top-300x200.png   ← thumbnail (untouched)
wp-content/uploads/2025/03/hero-top-300x200.webp  ← thumbnail WebP copy
```

Original images are **never** modified or deleted.

### Conversion History

A table showing every image that has been converted, with original size, WebP size, and compression percentage. You can:

- **Select and bulk-remove** items from the history
- **Clear All History** to wipe the log (does not delete WebP files)
- **Revert All WebP Files** to delete every WebP file the plugin created and clear all history

### Revert All WebP Files

This is the nuclear option. It deletes every WebP file tracked in the plugin's registry, clears the conversion history, removes `.htaccess` rules, and clears Elementor cache.

**What it deletes**: Only files this plugin created (tracked in the registry).
**What it keeps**: Original JPG/PNG files (always untouched), and any images that were uploaded natively as WebP.

Use this if you want to completely undo all WebP conversion and return to serving originals only.

### Migrating from MBR WebP Converter

If you were previously using the standalone **MBR WebP Converter** plugin, upgrading to MBR WP Performance v1.6.0 will automatically migrate your conversion history and file registry. After upgrading:

1. Go to **WP Performance > WebP** and confirm your settings
2. Deactivate the standalone MBR WebP Converter plugin
3. Optionally delete the standalone plugin

All existing WebP files remain in place and continue to be served.

---

## Multisite Network Support

Available from v1.5.0 onwards. Requires **Network Activation** of the plugin.

### Overview

On a WordPress Multisite network, MBR WP Performance lets you manage performance settings centrally from the Network Admin while optionally allowing individual site admins to customise their own settings.

### Network Admin Settings

Access via: **Network Admin > Settings > WP Performance**

This page lets you configure the **network default settings** — the baseline configuration that all sites in the network will use unless they have their own overrides.

The network settings page has the same tabs and options as the regular settings page (Core Features, JavaScript, CSS, Fonts, Preloading, Lazy Loading, Database, WebP).

### Push Settings to Sites

From the Network Admin settings page, you can push the current network defaults to all sites in the network — or select specific sites from a checkbox list. This overwrites any per-site customisations on the target sites.

**When to use**:

- ✅ Rolling out a standardised configuration across all sites
- ✅ Applying a tested configuration to new or unconfigured sites
- ⚠️ Use with caution — it overwrites any per-site customisations

### Import Settings from a Site

If one of your sites already has a well-tuned configuration, you can import its settings as the new network defaults. Select the site from the dropdown and click **"Import as Network Defaults"**.

### Per-Site Overrides

**Allow site overrides** (toggle in Network Admin):

- **Enabled**: Individual site admins can customise their own performance settings. Their changes override the network defaults for that site only.
- **Disabled**: All sites use the network defaults. The settings page is read-only for non-super-admins, with save and reset buttons greyed out.

**How it works**:

1. A new site starts by using the network defaults
2. If a site admin saves their own settings, the site switches to its own custom configuration
3. Pushing network defaults to that site resets it back to the network configuration

Sites that are using network defaults show an informational notice at the top of their settings page: *"This site is currently using network default settings. Saving changes will switch this site to its own custom settings."*

### New Site Setup

When a new site is created on the network, it automatically inherits the current network default settings. No manual configuration needed.

### Network Activation and Deactivation

- **Network Activate**: Activates the plugin on all existing sites and sets up network defaults
- **Network Deactivate**: Deactivates on all sites, clears scheduled events, and removes WebP `.htaccess` rules across the network

---

## Best Practices

### Start Simple, Add Gradually

**Week 1**: Database cleanup + Lazy loading
**Week 2**: Add font optimization + WebP conversion
**Week 3**: Add preloading
**Week 4**: CSS optimization
**Week 5**: JavaScript defer (test carefully!)

### Measure Performance

**Before optimizing**:
1. Test with [GTmetrix](https://gtmetrix.com)
2. Record: Load time, page size, requests
3. Take screenshot

**After each change**:
1. Clear all caches
2. Re-test
3. Compare results
4. Keep if improved, revert if worse

### The 80/20 Rule

**20% of efforts = 80% of results**

Focus on these high-impact, low-risk optimizations:

1. ✅ Lazy load images
2. ✅ Preload hero image
3. ✅ Self-host Google Fonts
4. ✅ Convert images to WebP
5. ✅ Database cleanup
6. ✅ Defer JavaScript

Skip advanced features until you've mastered basics.

### Staging Site Testing

**Critical for**:
- JavaScript defer
- CSS combination
- Remove unused CSS

**Testing checklist**:
- ☑ Homepage loads
- ☑ Blog posts display correctly
- ☑ Forms submit
- ☑ Shopping cart works
- ☑ Mobile responsive
- ☑ All interactive elements function

### Cache Clearing

After ANY change:

1. **Plugin cache**: If using WP Rocket, WP Super Cache, etc.
2. **Server cache**: If using server-level caching
3. **CDN cache**: If using Cloudflare, etc.
4. **Browser cache**: Hard refresh (Ctrl+F5)

**Order matters**: Clear from outside in (CDN → Server → Plugin → Browser)

### Backup Before Major Changes

**Before enabling**:
- JavaScript defer
- CSS combination
- Remove unused CSS
- Database conversion to InnoDB
- WebP bulk conversion (though originals are always kept)

**Use**:
- UpdraftPlus
- BackupBuddy
- Host backup (if available)
- Or WP-CLI: `wp db export`

---

## Troubleshooting

### Site Looks Broken

**Likely causes**:
1. CSS combination/minification broke styles
2. Critical CSS is incomplete
3. Font preloading conflicts

**Solutions**:

1. **Disable CSS optimizations**:
   - Uncheck "Minify CSS"
   - Uncheck "Combine CSS"
   - Clear cache, test

2. **Remove Critical CSS**:
   - Clear the Critical CSS textarea
   - Uncheck "Inline Critical CSS"
   - Clear cache, test

3. **Disable font preloading**:
   - Uncheck "Preload Critical Fonts"
   - Clear cache, test

### JavaScript Not Working

**Symptoms**:
- Sliders don't work
- Menus don't open
- Forms don't submit

**Likely cause**: JavaScript defer is breaking execution order

**Solution**:

1. **Disable defer temporarily**:
   - Uncheck "Defer JavaScript Loading"
   - Test - if fixed, defer is the issue

2. **Exclude problematic scripts**:
   ```
   Add to "Exclude from defer":
   jquery
   slider
   [script handle name]
   ```

3. **Check browser console**:
   - Press F12
   - Look for red errors
   - Note script names
   - Add to exclusions

### Slow Admin Area

**Cause**: Heartbeat running too frequently

**Solution**:
- Set Heartbeat to "Reduce" or "Disable"
- Note: Disabling breaks post auto-save

### Fonts Not Loading

**Symptoms**: 
- Text appears in system font
- Flash of unstyled text (FOUT)

**Checks**:

1. **Are fonts downloaded?**
   - Go to Fonts tab
   - Check "Currently Downloaded Fonts" section
   - If empty, click "Download Fonts"

2. **Are fonts being blocked?**
   - Disable "Disable Google Fonts"
   - Clear cache, test

3. **Check font paths**:
   - Browser console (F12)
   - Look for 404 errors on font files

### Elementor Editor Won't Load

**Already fixed in v1.4.9!**

**If still having issues**:
1. Deactivate MBR WP Performance
2. Edit page in Elementor
3. Reactivate plugin

**Note**: Plugin automatically disables in Elementor editor mode

### Database Cleanup Deleted Too Much

**Prevention**: Always scan before deleting

**If it happened**:
1. Restore from backup (you made one, right?)
2. Or restore database from host backup
3. Plugin only deletes orphaned data (no parent) - should be safe

### Performance Didn't Improve

**Common reasons**:

1. **Server is slow**: Optimize WP, but need better hosting
2. **Theme is bloated**: Consider a faster theme
3. **Too many plugins**: Deactivate unused plugins
4. **Large images**: Convert to WebP (WebP tab) or use ShortPixel/Imagify
5. **No caching**: Add caching plugin (WP Rocket, LiteSpeed Cache)

**Next steps**:
1. Test server response time (should be <200ms)
2. Test with default theme
3. Disable all other plugins, test
4. Convert images to WebP
5. Add caching

### WebP Conversion Not Working

**Server diagnostics fail**:
- GD Library not installed → contact your host to install `php-gd`
- WebP support missing → host needs to recompile GD with WebP support
- Uploads not writable → check folder permissions (should be 755 or 775)

**Conversion runs but no WebP files appear**:
- Check the uploads folder directly via FTP/file manager
- Ensure there's enough disk space
- Very small images may be skipped if the WebP would be larger

**`<picture>` tags not appearing on the frontend**:
- Ensure "HTML `<picture>` Tags" is enabled in the WebP settings
- Clear all caches (plugin cache, server cache, CDN, browser)
- Check that you're viewing the frontend, not the admin or a page builder editor (wrapping is automatically skipped in editors)

**Images broken after enabling `.htaccess` rules**:
- If you're on Nginx, `.htaccess` rules don't apply — use `<picture>` tags instead
- Disable the `.htaccess` option, save, and clear caches

### Multisite Settings Not Applying

**Sites not using network defaults**:
- Check that the plugin is **network-activated** (not individually activated per site)
- If a site admin has saved their own settings, the site uses its own configuration — push network defaults to reset it

**Site admins can't save settings**:
- Check whether "Allow site overrides" is enabled in the Network Admin settings
- If disabled, settings are read-only for non-super-admins (this is intentional)

**New sites don't have the right settings**:
- New sites inherit defaults at the moment of creation — if you changed the network defaults after the site was created, push the updated defaults to that site

---

## FAQ

### Can I use this with other performance plugins?

**Yes**, but avoid overlapping features:

**Good combinations**:
- ✅ MBR WP Performance + WP Rocket (caching)
- ✅ MBR WP Performance + Imagify (lossy compression of originals — disable their WebP)
- ✅ MBR WP Performance + Cloudflare (CDN)

**Bad combinations**:
- ❌ MBR WP Performance + Autoptimize (both defer JS/CSS)
- ❌ MBR WP Performance + WP Super Minify (duplicate minification)
- ❌ MBR WP Performance + another WebP converter (duplicate WebP files)

**Rule**: Use MBR WP Performance for JS/CSS/fonts/WebP, use other plugins for caching/CDN

### Will this work with my page builder?

**Yes!** Compatible with:
- ✅ Elementor
- ✅ Divi
- ✅ Beaver Builder
- ✅ Oxygen
- ✅ Bricks
- ✅ WPBakery

**How it works**: Plugin detects editor mode and disables optimizations automatically

### Does it work with WordPress Multisite?

**Yes!** From v1.5.0 onwards, the plugin fully supports Multisite networks.

**What you can do**:
- ✅ Network-activate the plugin
- ✅ Set network-wide default settings from the Network Admin
- ✅ Push settings to all sites (or selected sites) in one click
- ✅ Import a site's settings as the network defaults
- ✅ Allow or lock per-site overrides
- ✅ New sites automatically inherit network defaults

**Where to find it**: Network Admin → Settings → WP Performance

### Will WebP conversion affect my original images?

**No.** Original JPG, JPEG, and PNG files are **never** modified or deleted. WebP copies are created alongside the originals in the same uploads folder. If you deactivate the plugin or click "Revert All WebP Files", the WebP copies are removed and your originals remain exactly as they were.

### What happens to WebP files if I deactivate the plugin?

On deactivation, the plugin automatically deletes all WebP files it created (tracked in its registry), removes `.htaccess` rules, and clears Elementor cache. Your original images are untouched.

If you prefer to clean up manually before deactivating, use the **"Revert All WebP Files"** button in the WebP tab.

### Can I use WebP conversion with other image optimisation plugins?

**Yes**, but avoid running two WebP converters at the same time. If you're already using ShortPixel, Imagify, or EWWW for WebP conversion, disable their WebP feature and use this plugin's converter instead — or vice versa.

**Good combinations**:
- ✅ MBR WP Performance WebP + ShortPixel (for lossy optimisation of originals)
- ✅ MBR WP Performance WebP + Imagify (for image resizing/compression)

**Bad combinations**:
- ❌ Two plugins both creating WebP copies (duplicate files, conflicting `<picture>` tags)

### I was using the standalone MBR WebP Converter — what do I do?

Upgrade to MBR WP Performance v1.6.0 and your conversion history and file registry will be migrated automatically. Then deactivate the standalone MBR WebP Converter plugin. All existing WebP files remain in place.

### How often should I run database cleanup?

**Recommended schedule**:
- **Revisions**: Monthly
- **Orphaned data**: Quarterly
- **Transients**: Monthly
- **Table optimization**: After bulk deletions

**Or**: Enable scheduled cleanup (weekly automatic)

### Is it safe to combine CSS/JS files?

**Depends on your site complexity**:

**Safe for**:
- ✅ Simple blogs
- ✅ Brochure sites
- ✅ Static content

**Risky for**:
- ⚠️ eCommerce sites
- ⚠️ Membership sites
- ⚠️ Complex plugins

**Recommendation**: Test on staging first, monitor for errors

### What's the difference between defer and async?

**Defer**:
```html
<script src="script.js" defer></script>
```
- Downloads in parallel
- Executes in order
- After page parse
- **Best for**: Scripts that depend on each other

**Async**:
```html
<script src="script.js" async></script>
```
- Downloads in parallel
- Executes immediately when ready
- Out of order
- **Best for**: Independent scripts (analytics)

**This plugin uses**: Defer (safer, more predictable)

### Can I exclude specific pages from optimization?

**Currently**: No per-page controls

**Workaround**: Use conditional exclusions
```
Exclude script handles that only load on specific pages
```

**Future version**: May add per-page controls

### How do I know which scripts to defer?

**Safe to defer** (almost always):
- Analytics (Google Analytics, GTM)
- Social widgets (Facebook, Twitter)
- Comment systems (Disqus)
- Ads (Google Ads)
- Fonts (Google Fonts JS)

**Sometimes defer**:
- jQuery (test first)
- Theme scripts (test first)
- Sliders (may need exclusion)

**Never defer**:
- Critical functionality scripts
- Scripts others depend on (if you're unsure)

### Will this break my site?

**Honest answer**: Possibly, if misconfigured

**But**:
- Start with safe features (database, lazy loading)
- Enable one feature at a time
- Test after each change
- Keep backups
- Use staging site

**Most common issues**:
1. JavaScript defer breaking sliders → Exclude slider script
2. CSS combination breaking layout → Disable combination
3. Critical CSS incomplete → Regenerate or disable

**All fixable**: Just uncheck the problematic setting

### Do I need a caching plugin too?

**Yes!** This plugin doesn't do page caching

**MBR WP Performance**: Optimizes code, assets, database
**Caching plugin**: Stores pre-generated pages

**They work together**:
1. MBR WP Performance optimizes the page
2. Caching plugin saves the optimized version
3. Visitors get fast cached page

**Recommended caching plugins**:
- WP Rocket (premium)
- LiteSpeed Cache (free)
- WP Super Cache (free)

### Can I revert all changes?

**Yes!** Several ways:

**Option 1 - Disable specific features**:
- Go to each tab
- Uncheck settings
- Save

**Option 2 - Reset to defaults**:
- Any tab → "Reset to Defaults" button
- Confirms before resetting
- Restores original settings

**Option 3 - Revert WebP files**:
- WebP tab → "Revert All WebP Files" button
- Deletes all plugin-created WebP files
- Clears history and registry
- Originals are never touched

**Database cleanups**: Irreversible (use backups!)

### How much faster will my site be?

**Honest answer**: Depends on starting point

**Typical improvements**:
- **Basic optimizations**: 20-40% faster
- **Full optimization**: 50-80% faster
- **Already optimized site**: 10-20% faster

**Biggest impacts**:
1. Lazy loading: -50% initial payload
2. WebP conversion: -60-95% image file sizes
3. Font optimization: -300ms render time
4. Defer JavaScript: -1-2s load time
5. Database cleanup: Better admin speed

**Real example**:
```
Before: 4.2s load, 3.1 MB, 87 requests
After: 1.8s load, 1.2 MB, 42 requests
Improvement: 57% faster, 61% smaller
```

**Your results will vary** based on theme, plugins, content

---

## Advanced Topics

### Custom Code Integration

**For developers**: Add manual exclusions programmatically

```php
// Exclude scripts from defer
add_filter( 'mbr_wp_performance_defer_exclusions', function( $exclusions ) {
    $exclusions[] = 'my-critical-script';
    return $exclusions;
} );

// Exclude from lazy loading
add_filter( 'mbr_wp_performance_lazy_load_exclusions', function( $exclusions ) {
    $exclusions[] = '.my-image-class';
    return $exclusions;
} );
```

### Scheduled Tasks

**View scheduled tasks**:
```bash
wp cron event list
```

**Manually trigger cleanup**:
```bash
wp cron event run mbr_wp_performance_scheduled_cleanup
```

### Database Direct Access

**Check revision count**:
```sql
SELECT COUNT(*) FROM wp_posts WHERE post_type = 'revision';
```

**Check orphaned meta**:
```sql
SELECT COUNT(*) FROM wp_postmeta pm
LEFT JOIN wp_posts p ON pm.post_id = p.ID
WHERE p.ID IS NULL;
```

---

## Support & Resources

### Getting Help

1. **Check this documentation first**
2. **Review troubleshooting section**
3. **Check browser console** for JavaScript errors
4. **Test with default theme** to isolate issue
5. **Contact support** with:
   - WordPress version
   - Active theme
   - Active plugins
   - Specific error message
   - Settings enabled

### Performance Testing Tools

**Free tools**:
- [GTmetrix](https://gtmetrix.com)
- [Google PageSpeed Insights](https://pagespeed.web.dev)
- [WebPageTest](https://webpagetest.org)
- [Pingdom](https://tools.pingdom.com)

**Browser tools**:
- Chrome DevTools (F12 → Performance tab)
- Lighthouse (F12 → Lighthouse tab)

### Further Reading

**Performance optimization**:
- [Google Web Fundamentals](https://developers.google.com/web/fundamentals/performance)
- [Web.dev Performance](https://web.dev/performance/)
- [MDN Performance](https://developer.mozilla.org/en-US/docs/Web/Performance)

**WordPress specific**:
- [WordPress Performance Team](https://make.wordpress.org/performance/)
- [WordPress Performance Handbook](https://make.wordpress.org/core/handbook/best-practices/performance/)

---

## Changelog

### Version 1.6.0 (Current)

**New Features**:
- ✨ Integrated WebP image conversion (previously the standalone MBR WebP Converter plugin)
- ✨ New "WebP" tab with settings, server diagnostics, and bulk converter
- ✨ Automatic WebP conversion on image upload
- ✨ Configurable compression level (1–100)
- ✨ HTML `<picture>` tag delivery with automatic browser fallback
- ✨ Apache/LiteSpeed `.htaccess` rewrite rules for transparent WebP serving
- ✨ Gutenberg block and Elementor widget integration for `<picture>` tags
- ✨ Conversion history with bulk management and "Revert All" functionality
- ✨ Automatic migration of data from standalone MBR WebP Converter plugin

**Improvements**:
- 🔧 Smart skip when WebP output would be larger than the original
- 🔧 Redesigned admin UI with pill-style tab navigation
- 🔧 Dark mode page background

### Version 1.5.0

**New Features**:
- ✨ Full WordPress Multisite network support
- ✨ Network Admin settings page (Settings > WP Performance)
- ✨ Network-wide default settings with one-click push to all sites
- ✨ Import settings from any site as the network defaults
- ✨ Per-site override toggle — super admins can lock or unlock site customisation
- ✨ Automatic activation and default settings for newly-created network sites
- ✨ Network Admin toolbar shortcut

**Improvements**:
- 🔧 Options resolution respects network defaults with per-site override priority
- 🔧 Save button and reset disabled when per-site overrides are locked
- 🔧 Informational notices on per-site settings pages in multisite context

### Version 1.4.9

**New Features**:
- ✨ Comprehensive lazy loading controls
- ✨ Preloading and speculative loading options
- ✨ Self-host Google Fonts with manual management
- ✨ Enhanced Google Fonts blocking
- ✨ Clear font cache functionality
- ✨ CSS scanner for unused styles
- ✨ Toolbar menu access

**Improvements**:
- 🔧 Rebuilt admin JavaScript
- 🔧 Reorganized settings
- 🔧 Page builder compatibility

**Fixes**:
- 🐛 Tooltips now work correctly
- 🐛 Action buttons functional
- 🐛 Elementor editor compatibility
- 🐛 Admin assets loading

---

## Quick Reference Card

### High-Impact, Low-Risk Optimizations

Copy this checklist for your first optimization session:

```
☐ Database → Delete old revisions (keep: 5)
☐ Database → Delete orphaned post meta
☐ Database → Delete expired transients
☐ Fonts → Enable "Self-Host Google Fonts"
☐ Fonts → Add your fonts, click "Download Fonts"
☐ Fonts → Enable "Preload Critical Fonts"
☐ Lazy Loading → Enable "Lazy Load Images"
☐ Lazy Loading → Enable "Lazy Load iFrames and Videos"
☐ WebP → Check server diagnostics (all green)
☐ WebP → Enable "Automatic Conversion"
☐ WebP → Run "Start Conversion" for existing images
☐ WebP → Enable "HTML <picture> Tags"
☐ Preloading → Set "Preload Critical Images" to 1
☐ Preloading → Enable "Fetch Priority"
☐ Core → Enable "Disable Emojis"
☐ Core → Enable "Disable Dashicons (Frontend)"
☐ Core → Set Heartbeat to "Reduce"
☐ Clear all caches
☐ Test site thoroughly
```

**Expected results**: 30-50% improvement with minimal risk

### Exclusion Patterns Reference

**JavaScript defer exclusions**:
```
jquery-core
jquery-migrate
theme-script
slider
```

**Lazy loading exclusions**:
```
.logo
.site-logo
#header-image
.hero
[data-src*="logo"]
```

**Delayed JavaScript patterns**:
```
google-analytics
gtag
facebook
fbevents
intercom
drift
```

---

## Final Thoughts

Performance optimization is a journey, not a destination. This plugin gives you the tools - use them wisely:

1. **Start small** - Enable safe features first
2. **Test everything** - Measure before and after
3. **Be patient** - Optimize gradually over weeks
4. **Keep learning** - Performance best practices evolve
5. **Backup always** - Before any major change

**Remember**: A 1-second improvement in load time can increase conversions by 7%. Every optimization counts!

**Questions?** Re-read relevant sections, check troubleshooting, then reach out for support.

**Happy optimizing!** 🚀

---

*Last updated: Version 1.6.0 - March 2026*
*Created with ❤️ by Robert Palmer*
