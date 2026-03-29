# Frequently Asked Questions (FAQ)

Common questions about MBR WP Performance plugin.

## Table of Contents

- [General Questions](#general-questions)
- [Features & Functionality](#features--functionality)
- [Performance & Results](#performance--results)
- [Compatibility](#compatibility)
- [Configuration & Settings](#configuration--settings)
- [Troubleshooting](#troubleshooting)
- [Comparison to Other Plugins](#comparison-to-other-plugins)
- [Support & Development](#support--development)

---

## General Questions

### What is MBR WP Performance?

MBR WP Performance is a free, open-source WordPress performance optimization plugin that gives you granular control over every aspect of your site's speed. Unlike all-in-one solutions with "magic optimize" buttons, this plugin lets you enable/disable individual features with full transparency about what each does.

**Key differences:**
- ✅ 100% free (no premium upsells)
- ✅ Complete transparency (every feature explained)
- ✅ Granular controls (not just presets)
- ✅ No vendor lock-in
- ✅ Open source (GPL v2)

---

### Is it really free? Are there any premium features?

**Yes, completely free. Forever.**

There are:
- ❌ No premium versions
- ❌ No paid add-ons
- ❌ No feature limitations
- ❌ No time limits
- ❌ No account required
- ❌ No credit card needed

All features are available to everyone. This is a passion project built to solve real problems, not a freemium business model.

---

### Who created this plugin and why?

Created by **Rob** (Made by Robert), a freelance WordPress developer from Cleethorpes, England. 

**Why it was built:**

After years of using various performance plugins and finding them either:
1. Too simple (one-button "optimize" with no control)
2. Too expensive ($99-299/year for basic features)
3. Black boxes (you don't know what they're doing)

I built this plugin to scratch my own itch - then realized others might benefit too. It's the plugin I wanted to exist.

**Philosophy:** Performance optimization should be transparent, accessible, and free.

---

### How is this different from [WP Rocket / Autoptimize / Other Plugin]?

**vs WP Rocket:**
- WP Rocket: $59/year, all-in-one solution, great caching
- MBR WP Performance: Free, granular controls, no caching (pair with free cache plugin)
- **Best combo:** WP Rocket for caching + MBR WP Performance for optimization

**vs Autoptimize:**
- Autoptimize: Free, simpler controls, established
- MBR WP Performance: Free, more features, newer
- **Difference:** More granular control, self-hosted fonts, database cleanup

**vs Jetpack Boost:**
- Jetpack Boost: Freemium, cloud-based
- MBR WP Performance: Fully free, self-hosted
- **Privacy:** MBR doesn't phone home


---

### Can I use this on commercial/client sites?

**Yes!** Absolutely.

The GPL v2 license allows:
- ✅ Personal use
- ✅ Commercial use
- ✅ Client sites
- ✅ Unlimited sites
- ✅ Modification
- ✅ Redistribution

**You can:**
- Use on unlimited client sites
- White-label it (if you want)
- Charge clients for setup/configuration
- Include in your service packages

**You cannot:**
- Remove GPL license
- Claim you created it
- Hold me liable if something breaks

---

### Is my data safe? Do you track anything?

**Zero tracking. Zero phone-home.**

The plugin:
- ✅ Stores all data locally (in your WordPress database)
- ✅ Never sends data to external servers
- ✅ No analytics
- ✅ No "license checking"
- ✅ No update servers (updates via GitHub/WordPress.org)

**Privacy:** I literally have no idea who's using this plugin. That's by design.

---

## Features & Functionality

### What features does it include?

**Core Optimization:**
- Disable unnecessary WordPress features (emojis, embeds, etc.)
- Control REST API, Heartbeat, revisions
- WooCommerce script optimization

**JavaScript:**
- Defer/async loading
- Move to footer
- Minify & combine
- Delayed execution (analytics, chat widgets)
- jQuery optimization

**CSS:**
- Critical CSS generation & inlining
- Async loading
- Minify & combine
- CSS scanner for unused styles
- Remove global styles

**Fonts:**
- Self-host Google Fonts (one-click download)
- Font preloading
- Font subsetting
- Display strategies (swap, block, fallback)
- Font Awesome optimization

**Lazy Loading:**
- Images
- iFrames & videos
- Smart exclusions

**Preloading:**
- Critical images
- Fetch priority
- Cloudflare Early Hints
- Speculative loading (prefetch/prerender)

**Database:**
- Revision cleanup
- Orphaned data removal
- Transient management
- Table optimization
- Scheduled cleanups

**WebP Image Conversion:**
- Convert JPG, JPEG, and PNG to WebP
- Automatic conversion on upload
- Bulk converter for existing images
- Configurable compression (1–100)
- HTML `<picture>` tag delivery with fallback
- Apache/LiteSpeed `.htaccess` rewrite rules
- Gutenberg and Elementor integration
- Revert all with one click

**Multisite Network Support:**
- Network-wide default settings
- Push settings to all sites (or selected sites)
- Import settings from any site as defaults
- Per-site override control
- Automatic setup for new sites

---

### Does it include page caching?

**No.** This plugin focuses on optimization, not caching.

**Why?**
- Caching is already solved (WP Rocket, LiteSpeed, etc.)
- Optimization + Caching = Best results
- Keeps plugin focused and lightweight

**Recommended setup:**
```
MBR WP Performance (optimization + WebP)
+
WP Rocket / LiteSpeed Cache / WP Super Cache (caching)
=
Maximum performance
```

---

### Does it optimize images?

**Yes — it converts images to WebP format.** From v1.6.0, the plugin includes a full WebP converter that creates smaller WebP copies of your JPG, JPEG, and PNG images.

**What it does:**
- ✅ Converts images to WebP (typically 60–95% smaller)
- ✅ Automatic conversion on upload
- ✅ Bulk converter for existing Media Library images
- ✅ Serves WebP via `<picture>` tags with automatic fallback
- ✅ Configurable compression level (1–100)
- ✅ Server diagnostics panel
- ✅ Full revert capability (originals never touched)

**What it doesn't do:**
- ❌ Lossy compression of originals (resizing, stripping metadata)
- ❌ CDN-based image transformation

**For lossy compression of originals**, pair with:
- ShortPixel (freemium)
- Imagify (freemium)
- Smush (free)

**Best practice:** Use this plugin for WebP conversion, use a dedicated tool for lossy compression of the originals if needed. Disable their WebP feature to avoid duplicates.

---

### What is "Self-Host Google Fonts" and why should I use it?

**What it does:**
Downloads Google Fonts from Google's CDN to your server, then serves them locally.

**Benefits:**
1. **Faster:** Same server = fewer DNS lookups
2. **Private:** No Google tracking
3. **Reliable:** Works if Google CDN is blocked
4. **GDPR:** No third-party requests

**How it works:**
```
Before:
Browser → Google Fonts CSS (fonts.googleapis.com)
       → Google Font Files (fonts.gstatic.com)
       = 2 external requests, 200-500ms

After:
Browser → Your Server (both CSS and fonts)
       = 0 external requests, 50-100ms
```

**Setup:**
1. Enable "Self-Host Google Fonts"
2. Enter: `Poppins:400,700`
3. Click "Download Fonts"
4. Done!

---

### What is "Critical CSS" and should I use it?

**What it is:**
The minimum CSS needed to render the visible (above-the-fold) portion of your page.

**The problem it solves:**
```
Normal CSS loading:
1. Browser downloads HTML
2. Finds <link rel="stylesheet" href="style.css">
3. STOPS rendering to download CSS
4. Downloads CSS (500ms - 2s)
5. Renders page

User sees blank screen for 1-2 seconds!
```

**Critical CSS solution:**
```
Critical CSS:
1. Browser downloads HTML
2. CSS is INLINE in <style> tag
3. Renders page IMMEDIATELY
4. Downloads full CSS in background

User sees content in 200-300ms!
```

**Should you use it?**
- ✅ Yes for most sites (big improvement)
- ⚠️ Complex to implement correctly
- 🔧 Use auto-generator, test thoroughly

---

### What is "Lazy Loading" and is it safe?

**What it is:**
Delays loading images/videos until they're about to enter the viewport (as user scrolls).

**Benefits:**
```
Page with 50 images:
Without lazy loading: Load all 50 = 5 MB
With lazy loading: Load 5 visible = 500 KB

90% reduction in initial load!
```

**Is it safe?**
- ✅ Native browser feature (`loading="lazy"`)
- ✅ No JavaScript required
- ✅ SEO-friendly (Google supports it)
- ✅ Won't break anything if implemented correctly

**Gotchas:**
- ⚠️ Don't lazy load hero images (LCP)
- ⚠️ Don't lazy load logos
- ⚠️ Exclude first 2-3 images

Plugin automatically handles these exclusions!

---

### What does "Database Optimization" do?

**Post Revisions:**
WordPress saves every edit as a "revision". A post with 50 revisions = 50 database rows. Plugin limits to X most recent.

**Orphaned Data:**
When you delete a post, WordPress sometimes leaves metadata behind. Plugin finds and removes orphaned:
- Post meta (custom fields)
- Comment meta
- Term meta (category/tag data)
- Relationships

**Transients:**
Temporary cached data. Expired transients stay in database. Plugin removes expired ones.

**Table Optimization:**
Like defragmenting a hard drive - compacts tables, frees space.

**All safe:** Only removes data that's unused or orphaned.

---

## Performance & Results

### How much faster will my site be?

**Honest answer:** It depends on your starting point.

**Typical improvements:**
- Already optimized site: 10-20% faster
- Average WordPress site: 30-50% faster
- Slow, unoptimized site: 50-80% faster

**Real example:**
```
Before: 4.2s load, 3.1 MB, 87 requests
After:  1.8s load, 1.2 MB, 42 requests
Result: 57% faster, 61% smaller
```

**Biggest impacts:**
1. Lazy loading: -50% initial payload
2. WebP conversion: -60-95% image file sizes
3. Self-hosted fonts: -300ms
4. Defer JavaScript: -1-2s
5. Database cleanup: Better admin performance

**Remember:** If your hosting is slow (1+ second server response), optimization can only do so much. Start with decent hosting.

---

### Will it improve my Google PageSpeed score?

**Likely yes, but...**

PageSpeed Insights measures:
- ✅ First Contentful Paint (FCP) - This plugin helps
- ✅ Largest Contentful Paint (LCP) - Big improvement
- ✅ Total Blocking Time (TBT) - JavaScript defer helps
- ✅ Cumulative Layout Shift (CLS) - Font optimization helps

**Features that directly improve scores:**
- Lazy loading → Better LCP
- Critical CSS → Better FCP
- Defer JavaScript → Better TBT
- Font preloading → Better CLS
- WebP conversion → Better LCP and reduced transfer size

**Realistic expectations:**
```
Typical improvement: +10-30 points
From 60 → 80-90 (Mobile)
From 75 → 90-95 (Desktop)
```

**Getting to 100:** Nearly impossible on real sites. 90+ is excellent.

---

### How do I measure performance improvement?

**Before you start:**
1. Test with [GTmetrix](https://gtmetrix.com)
2. Record: Load time, page size, requests
3. Screenshot results

**After each change:**
1. Clear ALL caches
2. Re-test with GTmetrix
3. Compare to before
4. Keep if better, revert if worse

**Tools to use:**
- GTmetrix (best for WordPress)
- Google PageSpeed Insights
- WebPageTest
- Pingdom Tools
- Chrome Lighthouse (F12 > Lighthouse)

**What to track:**
- Total load time
- Time to First Byte (TTFB)
- First Contentful Paint (FCP)
- Largest Contentful Paint (LCP)
- Total page size
- Number of requests

---

### Why isn't my site faster after enabling everything?

**Common reasons:**

**1. Server is the bottleneck**
```
Check Time to First Byte (TTFB)
If > 500ms → Hosting is slow
Solution: Upgrade hosting
```

**2. Images not optimized**
```
Check page size in GTmetrix
If images = 80%+ of size → Compress images first
Solution: Use image optimization plugin
```

**3. No caching**
```
This plugin doesn't cache pages
Solution: Add WP Rocket / LiteSpeed Cache
```

**4. Conflicting plugins**
```
Another plugin may be slowing things down
Solution: Test with only this plugin active
```

**5. Over-optimization**
```
Too much defer/async can delay interactivity
Solution: Find balance, exclude critical scripts
```

**6. Need to clear caches**
```
Testing old cached version
Solution: Clear ALL caches (plugin, server, CDN, browser)
```

---

## Compatibility

### Will it work with my theme?

**Yes**, should work with any theme.

**Tested with:**
- ✅ Twenty Twenty-Four
- ✅ Astra
- ✅ GeneratePress
- ✅ OceanWP
- ✅ Kadence
- ✅ Neve
- ✅ Block themes
- ✅ Classic themes

**Potential issues:**
- ⚠️ JavaScript defer may break theme sliders
- ⚠️ CSS combination may cause conflicts

**Solution:** Use exclusions for problematic scripts.

---

### Does it work with Elementor / Divi / Beaver Builder?

**Yes!** Fully compatible.

**How it works:**
Plugin automatically detects when you're in editor mode and **completely disables** all optimizations.

**Supported page builders:**
- ✅ Elementor (edit mode + preview)
- ✅ Divi Builder (visual builder + frontend)
- ✅ Beaver Builder
- ✅ Oxygen
- ✅ Bricks
- ✅ WPBakery

**What if editor doesn't load?**
1. Update to v1.4.9+ (has automatic detection)
2. Or temporarily deactivate plugin while editing
3. Report issue on GitHub

---

### Does it work with WooCommerce?

**Yes**, but requires configuration.

**Recommended settings:**

**JavaScript Tab:**
```
Exclude from defer:
woocommerce
wc-cart-fragments
wc-add-to-cart
wc-checkout
select2
```

**Lazy Loading Tab:**
```
Exclude from lazy loading:
.woocommerce-product-gallery__image
.product-image
```

**Why?**
- WooCommerce has complex JavaScript
- Cart/checkout need immediate execution
- Product images often in sliders

**Test thoroughly:** Especially cart, checkout, and product pages.

---

### Can I use it with WP Rocket / Other optimization plugins?

**Yes**, but avoid overlapping features.

**Good combinations:**
```
✅ MBR WP Performance + WP Rocket (caching)
✅ MBR WP Performance + Imagify (lossy compression — disable their WebP)
✅ MBR WP Performance + ShortPixel (lossy compression — disable their WebP)
✅ MBR WP Performance + Cloudflare (CDN)
✅ MBR WP Performance + MBR Advanced Asset Manager (asset removal)
```

**Bad combinations:**
```
❌ MBR WP Performance + Autoptimize (both optimize JS/CSS)
❌ MBR WP Performance + WP Super Minify (duplicate features)
❌ MBR WP Performance + another WebP converter (duplicate WebP files)
```

**Rule:** Use MBR WP Performance for JS/CSS/fonts/WebP/database, use other plugins for caching/CDN.

**If using WP Rocket:** Disable WP Rocket's JS/CSS optimization, keep its caching.

---

### Does it work on WordPress Multisite?

**Yes!** Full Multisite network support from v1.5.0.

**What you can do:**
- ✅ Network-activate across all sites at once
- ✅ Set network-wide default settings from Network Admin
- ✅ Push settings to all sites (or selected sites) in one click
- ✅ Import a site's settings as the network defaults
- ✅ Allow or lock per-site overrides
- ✅ New sites automatically inherit network defaults

**Where to find it:** Network Admin → Settings → WP Performance

**How it works:**
```
Network Defaults (set by super admin)
    ↓
All sites use these defaults
    ↓
Site admin saves custom settings → site switches to its own config
    ↓
Super admin pushes defaults again → site resets to network config
```

**Per-site override control:**
- **Enabled:** Site admins can customise their own settings
- **Disabled:** All sites locked to network defaults (save button greyed out)

---

### How do I set up Multisite network settings?

**Step by step:**

1. **Network-activate** the plugin (Plugins → Network Activate)
2. Go to **Network Admin → Settings → WP Performance**
3. Configure your baseline settings across all tabs
4. Click **Save Changes**
5. Use **"Push to Sites"** to apply to existing sites
6. Optionally disable per-site overrides if you want all sites locked to your config

**Importing from an existing site:**
If one of your sites already has a good configuration, click **"Import as Network Defaults"** and select that site. Its settings become the new baseline for the network.

**New sites:** Automatically get the network defaults when created. No manual setup needed.

---

### Will WebP conversion work across all Multisite sites?

**Yes.** Each site in the network has its own WebP settings, conversion history, and file registry. The WebP tab works identically on each site.

If you push network defaults that include WebP settings (auto-convert enabled, compression level, etc.), all target sites will receive those settings. However, the bulk conversion itself needs to be run per-site since each site has its own Media Library.

---

### Does it work with WordPress.com?

**No.** Requires self-hosted WordPress.

WordPress.com doesn't allow plugin installation on free/personal plans. You need:
- WordPress.com Business plan or higher
- Or self-hosted WordPress (WordPress.org)

---

## Configuration & Settings

### Which features should I enable first?

**Week 1 - Safe, High-Impact:**
```
☑ Database → Clean revisions
☑ Database → Delete orphaned data
☑ Lazy Loading → Enable for images & videos
☑ Fonts → Self-host Google Fonts
☑ Fonts → Preload critical fonts
☑ WebP → Check server diagnostics
☑ WebP → Enable auto-convert + run bulk converter
☑ WebP → Enable <picture> tags
```

**Week 2 - CSS:**
```
☑ CSS → Generate critical CSS
☑ CSS → Async load CSS
☑ CSS → Minify CSS (test first)
```

**Week 3 - JavaScript:**
```
☑ JavaScript → Defer loading (test carefully!)
☑ JavaScript → Exclude problematic scripts
```

**Test after each step!**

---

### How do I know which scripts to defer?

**Safe to defer:**
- ✅ Google Analytics
- ✅ Facebook Pixel
- ✅ Social widgets
- ✅ Comment systems
- ✅ Ads
- ✅ Non-critical theme scripts

**Sometimes safe:**
- ⚠️ jQuery (test first)
- ⚠️ Sliders (may need exclusion)
- ⚠️ Menus (may need exclusion)

**Never defer:**
- ❌ Critical functionality
- ❌ Above-fold interactive elements
- ❌ Scripts other scripts depend on (if unsure)

**How to test:**
1. Enable defer for all
2. Test site thoroughly
3. Note what breaks
4. Add broken scripts to exclusions
5. Re-test

---

### Should I combine CSS/JavaScript files?

**Short answer:** Maybe. Test carefully.

**Pros:**
- Fewer HTTP requests
- Better compression

**Cons:**
- Large combined file
- Breaks browser caching
- Can increase total size
- May cause conflicts

**Modern recommendation:**
- ❌ Usually not needed with HTTP/2
- ✅ Consider for HTTP/1.1 servers
- ⚠️ Always test on staging first

**Alternative:** Minify instead of combine (safer).

---

### How often should I run database cleanup?

**Recommended schedule:**

| Task | Frequency |
|------|-----------|
| Delete old revisions | Monthly |
| Delete orphaned data | Quarterly |
| Delete expired transients | Monthly |
| Optimize tables | After bulk deletions |

**Or:** Enable "Scheduled Cleanup" to run automatically weekly.

**Safe to run:** Database cleanup only removes unused data.

---

### Will WebP conversion affect my original images?

**No.** Original JPG, JPEG, and PNG files are **never** modified or deleted. WebP copies are created alongside the originals in the same uploads folder (same name, `.webp` extension).

```
wp-content/uploads/2025/03/photo.jpg       ← original (untouched)
wp-content/uploads/2025/03/photo.webp       ← WebP copy
wp-content/uploads/2025/03/photo-300x200.jpg    ← thumbnail (untouched)
wp-content/uploads/2025/03/photo-300x200.webp   ← thumbnail WebP copy
```

If you click "Revert All WebP Files" or deactivate the plugin, all WebP copies are removed and your originals remain exactly as they were.

---

### What compression level should I use for WebP?

**Default (75) is excellent for most sites.** Here's what to expect:

| Level | Quality | Typical saving | Best for |
|-------|---------|---------------|----------|
| 50 | Good | 90-97% smaller | Maximum speed, less visual fidelity |
| 75 | Very good | 80-95% smaller | Best balance (recommended) |
| 90 | Excellent | 70-89% smaller | Photography, portfolio sites |
| 100 | Lossless | 20-40% smaller | When every pixel matters |

**If unsure:** Stick with 75, run the bulk converter, and spot-check a few images on the frontend.

---

### What happens to WebP files if I deactivate the plugin?

On deactivation, the plugin automatically deletes all WebP files it created (tracked in its registry), removes `.htaccess` rules, and clears Elementor cache. Your original images are untouched.

If you prefer to clean up manually first, use the **"Revert All WebP Files"** button in the WebP tab.

---

### Should I use `<picture>` tags or `.htaccess` rules for WebP?

**Use `<picture>` tags** (recommended). They work on any server (Apache, Nginx, CDN), provide clean browser fallback, and integrate with Gutenberg and Elementor.

`.htaccess` rules are only useful on Apache/LiteSpeed and don't work on Nginx. If you're already using `<picture>` tags, you don't need `.htaccess` rules at all.

---

### I was using the standalone MBR WebP Converter — what do I do?

1. Upgrade to MBR WP Performance v1.6.0
2. Go to **WP Performance > WebP** and confirm your settings
3. Deactivate the standalone MBR WebP Converter plugin
4. Optionally delete the standalone plugin

Your conversion history and WebP file registry are migrated automatically. All existing WebP files remain in place.

---

### Can I exclude specific pages from optimization?

**Currently:** No per-page controls built-in.

**Workarounds:**

**Option 1: Conditional exclusions**
```
Exclude scripts that only load on specific pages
Example: Exclude "checkout-script" (only on checkout)
```

**Option 2: Custom code**
```php
// In theme's functions.php
add_filter('mbr_wp_performance_defer_exclusions', function($exclusions) {
    if (is_page('contact')) {
        $exclusions[] = 'contact-form-script';
    }
    return $exclusions;
});
```

**Future:** Per-page controls may be added in future version.

---

## Troubleshooting

### Site looks broken after enabling CSS optimization

**Solution:** Disable CSS features one by one.

```
Go to: CSS Tab

Disable in this order:
1. Uncheck "Combine CSS" → Test
2. Uncheck "Minify CSS" → Test
3. Clear "Critical CSS" textarea → Test
4. Uncheck "Async Load CSS" → Test

Find which feature broke it, exclude or disable that feature.
```

See [Troubleshooting Guide](troubleshooting.md) for more solutions.

---

### JavaScript not working (sliders, forms, menus)

**Solution:** Exclude problematic scripts from defer.

```
Go to: JavaScript Tab
In "Exclude from Defer" textarea, add:

jquery
slider
menu
[your script handle]

Save and test.
```

See [JavaScript Troubleshooting](troubleshooting.md#javascript-problems).

---

### Fonts not loading

**Check:**
1. Are fonts downloaded? (Check "Currently Downloaded Fonts")
2. If empty → Click "Download Fonts"
3. Check browser console (F12) for 404 errors
4. Verify font file paths are correct

See [Font Troubleshooting](troubleshooting.md#font-loading-issues).

---

### Performance didn't improve

**Possible causes:**

1. **Slow server** → Check TTFB (Time to First Byte)
2. **Large images** → Optimize images first
3. **No caching** → Add caching plugin
4. **Testing cached version** → Clear all caches
5. **Wrong features enabled** → Follow recommended setup order

See [Performance Troubleshooting](troubleshooting.md#performance-not-improving).

---

### WebP conversion not working

**Server diagnostics show errors:**
- GD Library not installed → contact your host to install `php-gd`
- WebP support missing → host needs GD compiled with WebP support
- Uploads not writable → check folder permissions (755 or 775)

**Conversion runs but no files appear:**
- Check uploads folder via FTP for `.webp` files alongside originals
- Ensure sufficient disk space
- Very small images may be skipped (WebP would be larger)

**`<picture>` tags not showing on frontend:**
- Ensure "HTML `<picture>` Tags" is enabled in WebP settings
- Clear all caches (plugin, server, CDN, browser)
- Tags are automatically skipped in admin and page builder editors

**Images broken after `.htaccess` rules:**
- If on Nginx, `.htaccess` rules don't apply — use `<picture>` tags instead
- Disable the `.htaccess` option, save, and clear caches

---

### Multisite settings not applying to sites

**Sites not using network defaults:**
- Ensure plugin is **network-activated** (not individually per site)
- If a site admin has saved custom settings, that site uses its own config — push network defaults to reset it

**Site admins can't save settings:**
- Check "Allow site overrides" in Network Admin → Settings → WP Performance
- If disabled, settings are intentionally read-only for non-super-admins

**New sites have wrong settings:**
- New sites inherit defaults at creation time — if you changed defaults after the site was created, push the updated defaults to that site

---

### Where can I get help?

**Resources:**

1. 📚 **[User Guide](docs/user-guide.md)** - Complete documentation
2. 🔧 **[Troubleshooting Guide](troubleshooting.md)** - Common issues
3. 💬 **[GitHub Discussions](https://github.com/harbourbob/mbr-wp-performance/discussions)** - Q&A
4. 🐛 **[GitHub Issues](https://github.com/harbourbob/mbr-wp-performance/issues)** - Bug reports
5. 📖 **[FAQ](FAQ.md)** - You're here!

**Before asking:**
- Check documentation
- Search existing issues
- Try troubleshooting guide

**When asking:**
- Include WordPress/PHP version
- Describe issue clearly
- Steps to reproduce
- Error messages
- What you've tried

---

## Comparison to Other Plugins

### MBR WP Performance vs WP Rocket

| Feature | MBR WP Performance | WP Rocket |
|---------|-------------------|-----------|
| **Price** | Free | $59/year |
| **Page Caching** | ❌ | ✅ |
| **JS/CSS Optimization** | ✅ | ✅ |
| **Self-Hosted Fonts** | ✅ | ✅ |
| **Database Cleanup** | ✅ | ✅ |
| **Lazy Loading** | ✅ | ✅ |
| **Critical CSS** | ✅ | ✅ |
| **WebP Conversion** | ✅ | ❌ |
| **Multisite Network** | ✅ | ✅ |
| **CDN Integration** | ❌ | ✅ |
| **Control Level** | Granular | Medium |
| **Support** | Community | Premium |

**Best for:**
- MBR WP Performance: Budget-conscious, want control
- WP Rocket: Want all-in-one, premium support

**Use together?** Yes! WP Rocket for caching, MBR for optimization.

---

### MBR WP Performance vs Autoptimize

| Feature | MBR WP Performance | Autoptimize |
|---------|-------------------|-------------|
| **Price** | Free | Free |
| **JS/CSS Optimization** | ✅ | ✅ |
| **Self-Hosted Fonts** | ✅ | ✅ |
| **Database Cleanup** | ✅ | ❌ |
| **Lazy Loading** | ✅ | ✅ |
| **Critical CSS** | ✅ | ✅ (Pro) |
| **Preloading** | ✅ | ❌ |
| **WebP Conversion** | ✅ | ❌ |
| **Multisite Network** | ✅ | Basic |
| **Control Level** | More granular | Simpler |
| **Age** | Newer (2026) | Established (2010+) |

**Best for:**
- MBR WP Performance: Want all features, more control
- Autoptimize: Want simplicity, proven track record

---

## Support & Development

### How do I report a bug?

**GitHub Issues:** https://github.com/harbourbob/mbr-wp-performance/issues

**Before reporting:**
1. Check [existing issues](https://github.com/harbourbob/mbr-wp-performance/issues)
2. Try [troubleshooting guide](troubleshooting.md)
3. Test with default theme + only this plugin active

**Good bug report includes:**
- Plugin version
- WordPress version
- PHP version
- Active theme
- Other active plugins
- Steps to reproduce
- Expected behavior
- Actual behavior
- Error messages
- Screenshots

See [Bug Report Template](https://github.com/harbourbob/mbr-wp-performance/issues/new).

---

### How do I request a feature?

**GitHub Discussions:** https://github.com/harbourbob/mbr-wp-performance/discussions

Or **GitHub Issues:** https://github.com/harbourbob/mbr-wp-performance/issues/new?template=feature_request

**Good feature requests:**
- Explain use case
- Why current features don't solve it
- How many users would benefit
- Reference similar implementations

**Note:** Not all requests will be implemented. Plugin aims to stay focused.

---

### Can I contribute?

**Yes!** Contributions welcome.

**Ways to contribute:**
- 🐛 Report bugs
- 💡 Suggest features
- 📝 Improve documentation
- 🔧 Submit pull requests
- ⭐ Star the repository
- 📢 Share with others

See [Contributing Guide](CONTRIBUTING.md).

---

### How often is it updated?

**Update schedule:**
- Bug fixes: As needed (24-48 hours)
- Feature releases: Monthly-quarterly
- Security patches: Immediately

**Stay updated:**
- Watch repository on GitHub
- Check [Releases](https://github.com/harbourbob/mbr-wp-performance/releases)
- Subscribe to [Discussions](https://github.com/harbourbob/mbr-wp-performance/discussions)

---

### Is this plugin maintained?

**Yes!** Actively maintained as of 2026.

**Signs of maintenance:**
- ✅ Recent commits on GitHub
- ✅ Issues responded to within 48 hours
- ✅ Regular releases
- ✅ Updated for latest WordPress

**If unmaintained:** Fork it! GPL license allows this.

---

### Can I hire you for custom work?

**Yes!** Available for:
- WordPress performance optimization
- Custom plugin development
- Site speed audits
- Training/consultation

**Contact:** https://madebyrobert.co.uk

**Note:** Plugin support is free via GitHub. Custom work is paid.

---

### Where can I follow development?

**GitHub:** https://github.com/harbourbob/mbr-wp-performance

**Blog:** https://littlewebshack.com (plugin updates posted here)

**LinkedIn:** [Robert Palmer](https://www.linkedin.com/in/robert-palmer-cleethorpes/)

---

## Still Have Questions?

**Can't find your answer?**

3. 🔍 Search [GitHub Issues](https://github.com/harbourbob/mbr-wp-performance/issues)
4. 💬 Ask in [GitHub Discussions](https://github.com/harbourbob/mbr-wp-performance/discussions)
5. 🐛 Open [New Issue](https://github.com/harbourbob/mbr-wp-performance/issues/new)

---

**This FAQ is updated regularly. Last updated: March 2026**

**Found this helpful?** Star the repository: https://github.com/harbourbob/mbr-wp-performance ⭐
