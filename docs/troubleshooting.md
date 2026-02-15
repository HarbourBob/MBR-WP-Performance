# Troubleshooting Guide

This guide helps you resolve common issues with MBR WP Performance. Issues are organized by symptom for quick diagnosis.

## Table of Contents

- [Quick Diagnostic Checklist](#quick-diagnostic-checklist)
- [Site Appearance Issues](#site-appearance-issues)
- [JavaScript Problems](#javascript-problems)
- [Font Loading Issues](#font-loading-issues)
- [Performance Not Improving](#performance-not-improving)
- [Page Builder Issues](#page-builder-issues)
- [Database Problems](#database-problems)
- [Admin Area Issues](#admin-area-issues)
- [Compatibility Issues](#compatibility-issues)
- [Getting Help](#getting-help)

---

## Quick Diagnostic Checklist

Before diving into specific issues, try these steps:

```
☐ Clear ALL caches (plugin, server, CDN, browser)
☐ Disable MBR WP Performance temporarily - does issue go away?
☐ Check browser console (F12) for JavaScript errors
☐ Test with default WordPress theme (Twenty Twenty-Four)
☐ Disable all other plugins except MBR WP Performance
☐ Check PHP error logs
☐ Ensure WordPress, PHP, and MySQL meet minimum requirements
```

**How to clear browser cache:**
- Chrome/Edge: `Ctrl + Shift + Delete` (Windows) or `Cmd + Shift + Delete` (Mac)
- Firefox: `Ctrl + Shift + Delete` (Windows) or `Cmd + Shift + Delete` (Mac)
- Safari: `Cmd + Option + E`

**Or do a hard refresh:**
- `Ctrl + F5` (Windows)
- `Cmd + Shift + R` (Mac)

---

## Site Appearance Issues

### Broken Layout / CSS Not Loading

**Symptoms:**
- Site looks unstyled
- Elements misaligned
- Missing colors/fonts
- Mobile responsive broken

**Causes:**
- CSS minification broke styles
- CSS combination created conflicts
- Critical CSS is incomplete
- Async CSS loading issue

**Solutions:**

**1. Disable CSS Optimizations**
```
Navigate to: WP Performance > CSS

Uncheck:
☐ Minify CSS
☐ Combine CSS Files
☐ Async Load CSS
☐ Inline Critical CSS

Save Changes
Clear all caches
Test site
```

**2. If Site Still Broken - Check Critical CSS**
```
Go to: WP Performance > CSS
Scroll to: Critical CSS Code textarea
Click: Clear the entire textarea
Uncheck: Inline Critical CSS
Save Changes
```

**3. If Still Broken - Check For CSS Conflicts**
```
Open Browser Console (F12)
Look for errors like:
- "Failed to load resource: style.css"
- "Unexpected token in CSS"

Add problematic CSS files to exclusions
```

**4. Progressive Re-Enable**
```
After site works:
1. Enable Minify CSS → Test → If works, keep it
2. Enable Async Load CSS → Test → If works, keep it
3. Skip "Combine CSS" for now (often causes issues)
4. Regenerate Critical CSS → Test carefully
```

---

### Flash of Unstyled Content (FOUC)

**Symptoms:**
- Site loads without styles briefly
- Then styles "pop in"
- Text appears in default font then changes

**Causes:**
- CSS loading too late
- Critical CSS missing or incomplete

**Solutions:**

**1. Regenerate Critical CSS**
```
WP Performance > CSS
Click: "Auto-Generate Critical CSS"
Wait for completion
Paste result in Critical CSS textarea
Enable: Inline Critical CSS
Save
```

**2. Preload Critical CSS Files**
```
WP Performance > Preloading
Add CSS files to preload:
/wp-content/themes/your-theme/style.css
/wp-content/themes/your-theme/main.css
```

**3. Disable Async CSS (Temporary Fix)**
```
WP Performance > CSS
Uncheck: Async Load CSS
This will block rendering but eliminate FOUC
```

---

### Images Not Displaying

**Symptoms:**
- Broken image icons
- Images don't load
- Lazy loaded images stuck

**Causes:**
- Lazy loading interfering with slider/gallery
- Images excluded incorrectly
- JavaScript conflict

**Solutions:**

**1. Check If Lazy Loading Is The Issue**
```
WP Performance > Lazy Loading
Uncheck: Lazy Load Images
Save and test

If images now work → Lazy loading is the issue
```

**2. Exclude Problematic Images**
```
WP Performance > Lazy Loading
In "Exclude from Lazy Loading" textarea, add:

.slider
.gallery
.hero-image
[data-src]
```

**3. Check Browser Console**
```
Press F12
Look for errors like:
- "Failed to load resource: image.jpg"
- "Lazy load script error"
```

**4. Parent Element Exclusion**
```
If images in specific sections don't load:

WP Performance > Lazy Loading
In "Exclude by Parent Selector":

.elementor-widget-image
.wp-block-gallery
#hero-section
```

---

## JavaScript Problems

### Sliders Not Working

**Symptoms:**
- Slider doesn't slide
- Images don't rotate
- Navigation buttons don't work

**Causes:**
- JavaScript defer breaking execution order
- Slider script depends on jQuery loading first

**Solutions:**

**1. Disable JavaScript Defer**
```
WP Performance > JavaScript
Uncheck: Defer JavaScript Loading
Save and test

If slider works → Defer is the issue
```

**2. Exclude Slider Scripts from Defer**
```
If defer is needed for other benefits:

WP Performance > JavaScript
In "Exclude from Defer" textarea, add:

jquery
jquery-core
slider
swiper
slick
owl-carousel
[your slider plugin handle]

Save and test
```

**3. Find Script Handle Name**
```
View page source (Ctrl+U)
Search for your slider script filename
Look for handle in <script id="handle-name-js">

Example:
<script id="swiper-js" src="...">
Handle name is: swiper
```

---

### Forms Not Submitting

**Symptoms:**
- Contact forms don't submit
- Submit button doesn't respond
- No error messages appear

**Causes:**
- JavaScript defer breaking form validation
- jQuery loaded too late
- AJAX conflicts

**Solutions:**

**1. Exclude Form Scripts**
```
WP Performance > JavaScript
Exclude from defer:

jquery
contact-form-7
wpcf7
gravity-forms
wpforms
ninja-forms
[your form plugin handle]
```

**2. Disable Delayed JavaScript**
```
If forms use AJAX:

WP Performance > JavaScript
Clear "Delay JavaScript Execution" textarea
Or remove form-related keywords
```

**3. Check Console for Errors**
```
F12 > Console tab
Look for:
- "$ is not defined" → jQuery issue
- "ReferenceError" → Script loading order issue
```

---

### Menu Not Opening (Mobile/Dropdown)

**Symptoms:**
- Mobile menu doesn't toggle
- Dropdown menus don't work
- Hamburger icon not clickable

**Causes:**
- Menu JavaScript deferred
- jQuery dependency issue

**Solutions:**

**1. Exclude Menu Scripts**
```
WP Performance > JavaScript
Add to exclusions:

jquery
menu
navigation
mobile-menu
responsive-menu
[theme-name]-navigation
```

**2. Move Menu Scripts to Footer**
```
Try disabling:
WP Performance > JavaScript
Uncheck: Move Scripts to Footer

Some menus need scripts in <head>
```

---

### Analytics Not Tracking

**Symptoms:**
- Google Analytics not recording
- Facebook Pixel not firing
- No tracking data

**Causes:**
- Delayed execution working correctly (analytics should be delayed)
- Or delayed execution breaking tracking

**Solutions:**

**1. This Is Expected Behavior**
```
Analytics SHOULD be delayed for performance
Users are still tracked (after interaction)

Check in Google Analytics after 24-48 hours
Data may appear delayed but still accurate
```

**2. If Analytics Must Load Immediately**
```
WP Performance > JavaScript
Clear "Delay JavaScript Execution" textarea
Or remove:
analytics
gtag
google-analytics

Note: This reduces performance benefits
```

---

## Font Loading Issues

### Fonts Not Loading (System Fonts Showing)

**Symptoms:**
- Text appears in Arial/Times
- Google Fonts not applied
- Custom fonts missing

**Causes:**
- Fonts not downloaded
- Self-hosting misconfigured
- Font files blocked

**Solutions:**

**1. Check Downloaded Fonts**
```
WP Performance > Fonts
Scroll to: "Currently Downloaded Fonts"

If empty:
- Add fonts to manual entry
- Click "Download Fonts"
- Wait for success message
```

**2. Verify Font Files Exist**
```
Via FTP/File Manager:
Check: /wp-content/uploads/mbr-wp-performance-fonts/

Should contain:
- FontName-400.woff2
- FontName-700.woff2
- FontName-400.css
etc.
```

**3. Check Browser Console**
```
F12 > Network tab
Reload page
Filter by "font"
Look for 404 errors on font files

If 404: Fonts not downloaded correctly
Click "Download Fonts" again
```

**4. Disable Google Fonts Blocking**
```
If you're NOT self-hosting:

WP Performance > Fonts
Uncheck: Disable Google Fonts
Save

This allows Google CDN fonts
```

---

### Flash of Unstyled Text (FOUT)

**Symptoms:**
- Text appears in system font
- Then switches to web font
- Brief "flash" during load

**Causes:**
- Fonts loading asynchronously
- Font preloading not configured

**Solutions:**

**1. Enable Font Preloading**
```
WP Performance > Fonts
Check: Preload Critical Fonts

Add font file paths:
/wp-content/uploads/mbr-wp-performance-fonts/Poppins-400.woff2
/wp-content/uploads/mbr-wp-performance-fonts/Poppins-700.woff2
```

**2. Change Font Display Strategy**
```
WP Performance > Fonts > Google Fonts Optimization
Font Display Strategy: block

Note: This causes invisible text briefly
Better than FOUT for many sites
```

**3. Use Fallback Fonts Wisely**
```
WP Performance > Fonts
Set Fallback Font Stack to similar system font:

For Poppins (rounded sans):
-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto

For Playfair (serif):
Georgia, "Times New Roman", serif
```

---

### Wrong Font Weights Loading

**Symptoms:**
- Only regular weight shows
- Bold text not bolding
- Italic not working

**Causes:**
- Font weights not downloaded
- Incorrect font format

**Solutions:**

**1. Check Weights in Manual Entry**
```
WP Performance > Fonts
Manual entry format:

Poppins:400,500,700
(not just "Poppins")

Include all weights you need:
300 = Light
400 = Regular
500 = Medium
600 = Semi-bold
700 = Bold
```

**2. Verify Downloaded Weights**
```
Check: "Currently Downloaded Fonts"
Should show: Poppins (400, 500, 700)

If wrong, clear cache and re-download
```

---

## Performance Not Improving

### Page Load Time Still Slow

**Symptoms:**
- GTmetrix shows no improvement
- Site still feels slow
- Optimization enabled but no change

**Causes:**
- Server response time slow
- Images not optimized
- Caching not configured
- Database bloated

**Solutions:**

**1. Identify the Bottleneck**
```
Run GTmetrix on your site
Look at waterfall chart

If first bar (server response) is >500ms:
→ Problem is hosting, not plugin
→ Upgrade hosting or optimize server

If many large images:
→ Problem is image size
→ Optimize images first (use ShortPixel/Imagify)

If many HTTP requests:
→ Use this plugin's combination features
→ Enable lazy loading
```

**2. Ensure High-Impact Features Enabled**
```
☑ Lazy Loading > Lazy Load Images
☑ Lazy Loading > Lazy Load iFrames
☑ Fonts > Self-Host Google Fonts
☑ Preloading > Preload Critical Images (set to 1-2)
☑ JavaScript > Defer JavaScript
☑ Database > Delete old revisions

These give 80% of benefits
```

**3. Add Caching Plugin**
```
This plugin doesn't do page caching!

Add one of:
- WP Rocket (premium)
- LiteSpeed Cache (free)
- WP Super Cache (free)

Caching + MBR WP Performance = Best combo
```

**4. Optimize Images Separately**
```
This plugin doesn't compress images

Use:
- ShortPixel (freemium)
- Imagify (freemium)
- Smush (free)
- Or pre-optimize before upload
```

---

### Scores Improved But Site Feels Slow

**Symptoms:**
- GTmetrix shows better score
- But site doesn't "feel" faster to users

**Causes:**
- Over-optimization causing delays
- Server still slow
- Critical resources delayed

**Solutions:**

**1. Check Perceived Performance**
```
What matters:
- Time to First Contentful Paint (FCP)
- Largest Contentful Paint (LCP)
- Time to Interactive (TTI)

Not just total load time!
```

**2. Ensure Critical Resources Load Fast**
```
WP Performance > Preloading
Preload hero image
Enable fetch priority

WP Performance > CSS
Add critical CSS
Don't defer critical styles
```

**3. Don't Over-Defer**
```
If JavaScript defer breaks interactivity:

Exclude critical scripts:
- Menu/navigation
- Interactive elements
- Above-fold functionality
```

---

## Page Builder Issues

### Elementor Editor Won't Load

**Symptoms:**
- Elementor edit screen blank/frozen
- Can't edit pages
- Preview not working

**Solutions:**

**Already Fixed in v1.4.9!**

Plugin automatically detects Elementor editor and disables optimizations.

**If still having issues:**

**1. Verify Plugin Version**
```
Plugins > Installed Plugins
Check: MBR WP Performance version
Should be: 1.4.9 or higher

If older: Update plugin
```

**2. Temporary Workaround**
```
Deactivate MBR WP Performance
Edit page in Elementor
Save changes
Reactivate plugin

Plugin won't interfere with saved page
```

**3. Clear Elementor Cache**
```
Elementor > Tools > Regenerate CSS
Elementor > Tools > Clear Cache
Then try editing again
```

---

### Divi Builder Broken

**Symptoms:**
- Divi visual builder not loading
- Can't drag/drop modules
- Builder interface broken

**Solutions:**

**1. Plugin Should Auto-Detect**
```
v1.4.9+ automatically detects Divi builder
Optimizations disabled in builder mode

Update to latest version if old
```

**2. Manual Fix**
```
If Divi builder loads with ?et_fb=1 in URL
And still broken:

Deactivate plugin
Edit in Divi
Reactivate plugin
```

---

### Beaver Builder Issues

**Symptoms:**
- Builder won't load
- Frontend editor broken

**Solutions:**

```
Plugin detects Beaver Builder (?fl_builder parameter)
Should work automatically in v1.4.9+

If not:
1. Update plugin
2. Or deactivate while editing
3. Reactivate after saving
```

---

## Database Problems

### Deleted Too Many Revisions

**Symptoms:**
- Worried you deleted important versions
- Want to restore old content

**Solutions:**

**1. Check Backups**
```
If you have site backup from before cleanup:
→ Restore database from backup

Most hosts keep daily backups:
- cPanel: "Backup Wizard"
- Managed WordPress: Contact support
```

**2. Revisions vs Drafts**
```
Plugin only deletes EXCESS revisions
Keeps X most recent (you set this number)

Example:
Post had 50 revisions
You set "Keep: 5"
Deleted 45 old revisions
Kept 5 most recent

Recent versions are safe!
```

**3. Check Trash**
```
Posts > All Posts
Click "Trash" tab

Plugin doesn't delete posts
Only cleans revisions and metadata
```

**Prevention:**
```
Always click "Scan" before "Delete"
Review count before confirming
Keep backups before database cleanup
```

---

### "Orphaned Data" - What Did It Delete?

**Symptoms:**
- Concerned about orphaned data deletion
- Want to know what was removed

**Solutions:**

**1. Orphaned Data Explained**
```
Orphaned = metadata with no parent

Example:
- Post deleted (ID: 123)
- But post_meta for ID: 123 still in database
- This is orphaned (safe to delete)

Plugin ONLY deletes data that references
non-existent posts/comments/terms
```

**2. What's Safe to Delete**
```
✓ Post meta for deleted posts
✓ Comment meta for deleted comments  
✓ Term meta for deleted categories/tags
✓ Relationships to deleted posts

✗ NEVER deletes active post data
✗ NEVER deletes published content
✗ NEVER deletes media files
```

**3. If You Deleted By Mistake**
```
Orphaned data is already "orphaned"
It wasn't connected to anything
Deleting it doesn't affect live content

But if concerned:
Restore from backup before the cleanup
```

---

### Database Optimization Failed

**Symptoms:**
- "Optimize Tables" button errors
- "Convert to InnoDB" fails
- Timeout errors

**Causes:**
- Large database
- Insufficient permissions
- PHP timeout

**Solutions:**

**1. Increase PHP Timeout**
```
Add to wp-config.php:
set_time_limit(300);

Or contact host to increase:
max_execution_time = 300
```

**2. Do Operations Via phpMyAdmin**
```
cPanel > phpMyAdmin
Select database
Check tables
Click "Optimize table"

More reliable for large databases
```

**3. Convert InnoDB Manually**
```
phpMyAdmin > SQL tab

ALTER TABLE wp_posts ENGINE=InnoDB;
ALTER TABLE wp_postmeta ENGINE=InnoDB;

Do one table at a time
```

---

## Admin Area Issues

### Settings Won't Save

**Symptoms:**
- Click "Save Changes"
- Settings revert to previous values
- No success message

**Causes:**
- PHP max_input_vars too low
- Server security blocking
- Caching issue

**Solutions:**

**1. Increase max_input_vars**
```
Contact hosting support:
Ask to increase max_input_vars to 3000

Or add to .htaccess:
php_value max_input_vars 3000

Or add to php.ini:
max_input_vars = 3000
```

**2. Check ModSecurity**
```
If host uses ModSecurity:
May block form submissions

Contact host:
"ModSecurity blocking WP admin form saves"
Ask to whitelist your IP or disable rule
```

**3. Clear Browser Cache**
```
Hard refresh: Ctrl+F5
Clear cookies for your domain
Try incognito mode
```

---

### Tooltips Not Showing

**Symptoms:**
- Hover over (?) icons
- No tooltip appears

**Solutions:**

**1. Already Fixed in v1.4.9**
```
Update plugin to latest version
Clear browser cache
Tooltips should work
```

**2. Check CSS Loading**
```
F12 > Network tab
Reload page
Search for: admin.css

If not found:
Plugin CSS not loading
Report as bug
```

---

### Buttons Not Working

**Symptoms:**
- Click "Scan", "Generate", "Delete"
- Nothing happens
- No loading indicator

**Solutions:**

**1. Fixed in v1.4.9**
```
Update to latest version
Clear all caches
Should work correctly
```

**2. Check JavaScript Console**
```
F12 > Console tab
Look for errors
Report error message to support
```

---

## Compatibility Issues

### Conflict With Other Plugins

**Symptoms:**
- Both plugins try to optimize same thing
- Features not working
- Unexpected behavior

**Common Conflicts:**

**Autoptimize**
```
Problem: Both minify/combine CSS/JS
Solution: Use ONE plugin for JS/CSS optimization
Recommended: Disable Autoptimize JS/CSS features
            Keep MBR WP Performance
```

**WP Rocket**
```
Problem: Some overlapping features
Solution: WP Rocket = Caching
          MBR WP Performance = Optimization
          
In WP Rocket, disable:
☐ Minify CSS
☐ Minify JavaScript  
☐ Defer JavaScript

Keep WP Rocket for:
☑ Page caching
☑ Browser caching
☑ GZIP compression
```

**WP Super Minify / Fast Velocity Minify**
```
Problem: Duplicate minification
Solution: Deactivate one
          Recommended: Use MBR WP Performance
```

**Asset CleanUp / Perfmatters**
```
Compatible! These remove unused assets
MBR WP Performance optimizes loaded assets
Use together for best results
```

---

### Theme Conflicts

**Symptoms:**
- Theme features broken after activation
- Theme-specific sliders/animations broken

**Solutions:**

**1. Exclude Theme Scripts**
```
Find theme script handles:
View source > Search for theme name

Add to JavaScript exclusions:
themename-scripts
themename-main
themename-custom
```

**2. Test With Default Theme**
```
Temporarily switch to Twenty Twenty-Four
If issue gone → Theme conflict
If issue persists → Not theme related
```

**3. Contact Theme Developer**
```
Some themes have known conflicts
Check theme documentation
Contact support with details
```

---

### WooCommerce Issues

**Symptoms:**
- Cart not updating
- Checkout broken
- Product filters not working

**Solutions:**

**1. Exclude WooCommerce Scripts**
```
WP Performance > JavaScript
Add to exclusions:

woocommerce
wc-cart-fragments
wc-add-to-cart
wc-checkout
wc-ajax
select2
```

**2. Disable Features on Checkout**
```
If checkout specifically broken:

Create custom exclusion:
- Don't defer scripts on /checkout page
- Don't lazy load on /checkout page

Contact support for code snippet
```

---

## Getting Help

### Before Requesting Support

**Collect This Information:**

```
1. Plugin version: _____
2. WordPress version: _____
3. PHP version: _____
4. Active theme: _____
5. Other active plugins: _____
6. Specific error message: _____
7. Steps to reproduce: _____
8. Browser console errors: _____
9. URL to affected page: _____
10. What you've already tried: _____
```

### How to Get Browser Console Errors

```
1. Press F12 (or right-click > Inspect)
2. Click "Console" tab
3. Reload the page
4. Look for RED error messages
5. Right-click error > Copy message
6. Paste into support request
```

### Where to Get Help

**GitHub Issues** (Best for bugs):
```
https://github.com/yourusername/mbr-wp-performance/issues

Click: "New Issue"
Choose: Bug Report or Feature Request
Fill out template
```

**GitHub Discussions** (Best for questions):
```
https://github.com/yourusername/mbr-wp-performance/discussions

Click: "New Discussion"
Choose: Q&A category
Ask your question
```

**WordPress.org Support** (If listed there):
```
https://wordpress.org/support/plugin/mbr-wp-performance/

Click: "Get Support"
Create new topic
Include all details above
```

---

## Emergency Recovery

### Nothing Works - Need to Disable Plugin

**Via WordPress Admin:**
```
Plugins > Installed Plugins
Find: MBR WP Performance
Click: Deactivate
```

**Can't Access Admin:**

**Method 1: Via FTP/File Manager**
```
1. Connect to site via FTP
2. Navigate to: /wp-content/plugins/
3. Rename: mbr-wp-performance to mbr-wp-performance-disabled
4. Plugin is now deactivated
5. Access admin
6. Troubleshoot issue
7. Rename back when ready
```

**Method 2: Via Database**
```
1. phpMyAdmin > wp_options table
2. Find row: option_name = 'active_plugins'
3. Click Edit
4. Remove: mbr-wp-performance/mbr-wp-performance.php
5. Save
6. Plugin deactivated
```

**Method 3: Via WP-CLI**
```
wp plugin deactivate mbr-wp-performance
```

---

## Reporting Bugs

### How to Report Effectively

**Good Bug Report:**
```
Title: "JavaScript defer breaks Slick slider on homepage"

Description:
- Plugin version: 1.4.9
- WordPress: 6.4
- Theme: Astra 4.0
- Issue: Homepage slider doesn't rotate
- Steps to reproduce:
  1. Enable "Defer JavaScript"
  2. Visit homepage
  3. Slider shows first image only
- Console error: "$ is not defined"
- Expected: Slider should auto-rotate
- Actual: Static image
- Temporary fix: Excluded 'slick' from defer
```

**Poor Bug Report:**
```
Title: "Doesn't work"

Description:
My site is broken
```

---

## Still Need Help?

**If this guide didn't solve your issue:**

1. Search [existing GitHub issues](https://github.com/yourusername/mbr-wp-performance/issues)
2. Check [GitHub Discussions Q&A](https://github.com/yourusername/mbr-wp-performance/discussions)
3. [Open a new issue](https://github.com/yourusername/mbr-wp-performance/issues/new) with complete details

**Response Time Expectations:**
- Bug reports: 24-48 hours
- Feature requests: 1-2 weeks
- Questions: 24-72 hours

---

*Last updated: Version 1.4.9*
*Found an issue not covered here? [Submit it](https://github.com/yourusername/mbr-wp-performance/issues) so we can add it!*
