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
10. [Best Practices](#best-practices)
11. [Troubleshooting](#troubleshooting)
12. [FAQ](#faq)

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
- 🎯 **Granular Control**: Enable/disable individual optimizations
- 🔧 **Developer-Friendly**: Clean code, no vendor lock-in

---

## Getting Started

### Installation

1. **Upload the Plugin**
   - Go to `Plugins > Add New` in WordPress admin
   - Click `Upload Plugin`
   - Choose `mbr-wp-performance-v1.4.9.zip`
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
4. **Preloading** → Preload critical resources
5. **CSS** → Enable critical CSS and minification
6. **JavaScript** → Defer scripts carefully
7. **Core Features** → Fine-tune WordPress features

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

## Best Practices

### Start Simple, Add Gradually

**Week 1**: Database cleanup + Lazy loading
**Week 2**: Add font optimization
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
4. ✅ Database cleanup
5. ✅ Defer JavaScript

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
4. **Large images**: Optimize images first (use ShortPixel, Imagify)
5. **No caching**: Add caching plugin (WP Rocket, LiteSpeed Cache)

**Next steps**:
1. Test server response time (should be <200ms)
2. Test with default theme
3. Disable all other plugins, test
4. Optimize images
5. Add caching

---

## FAQ

### Can I use this with other performance plugins?

**Yes**, but avoid overlapping features:

**Good combinations**:
- ✅ MBR WP Performance + WP Rocket (caching)
- ✅ MBR WP Performance + Imagify (image optimization)
- ✅ MBR WP Performance + Cloudflare (CDN)

**Bad combinations**:
- ❌ MBR WP Performance + Autoptimize (both defer JS/CSS)
- ❌ MBR WP Performance + WP Super Minify (duplicate minification)

**Rule**: Use MBR WP Performance for JS/CSS/fonts, use other plugins for caching/images/CDN

### Will this work with my page builder?

**Yes!** Compatible with:
- ✅ Elementor
- ✅ Divi
- ✅ Beaver Builder
- ✅ Oxygen
- ✅ Bricks
- ✅ WPBakery

**How it works**: Plugin detects editor mode and disables optimizations automatically

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

**Yes!** Two ways:

**Option 1 - Disable specific features**:
- Go to each tab
- Uncheck settings
- Save

**Option 2 - Reset to defaults**:
- Any tab → "Reset to Defaults" button
- Confirms before resetting
- Restores original settings

**Database cleanups**: Irreversible (use backups!)

### How much faster will my site be?

**Honest answer**: Depends on starting point

**Typical improvements**:
- **Basic optimizations**: 20-40% faster
- **Full optimization**: 50-80% faster
- **Already optimized site**: 10-20% faster

**Biggest impacts**:
1. Lazy loading: -50% initial payload
2. Font optimization: -300ms render time
3. Defer JavaScript: -1-2s load time
4. Database cleanup: Better admin speed

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

### Version 1.4.9 (Current)

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

*Last updated: Version 1.4.9 - February 2026*
*Made with ❤️ by Made by Robert*
