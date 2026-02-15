# Installation Guide

Complete installation instructions for MBR WP Performance plugin.

## Table of Contents

- [Requirements](#requirements)
- [Installation Methods](#installation-methods)
  - [Method 1: WordPress Admin (Recommended)](#method-1-wordpress-admin-recommended)
  - [Method 2: Manual Upload via FTP](#method-2-manual-upload-via-ftp)
  - [Method 3: WP-CLI](#method-3-wp-cli)
- [First-Time Configuration](#first-time-configuration)
- [Updating the Plugin](#updating-the-plugin)
- [Uninstallation](#uninstallation)
- [Troubleshooting Installation](#troubleshooting-installation)

---

## Requirements

Before installing, ensure your server meets these requirements:

### Minimum Requirements

| Component | Requirement |
|-----------|-------------|
| WordPress | 5.8 or higher |
| PHP | 7.4 or higher |
| MySQL | 5.6 or higher |
| Memory Limit | 64MB+ (128MB recommended) |
| Max Execution Time | 30 seconds+ |

### Recommended Environment

| Component | Recommended |
|-----------|-------------|
| WordPress | 6.4+ |
| PHP | 8.0+ |
| MySQL | 8.0+ |
| Memory Limit | 256MB |
| Max Execution Time | 60 seconds |

### How to Check Your Environment

**Via WordPress Admin:**
```
Tools > Site Health > Info > Server
```

**Via PHP:**
```php
<?php phpinfo(); ?>
```

**Via WP-CLI:**
```bash
wp cli info
```

### Browser Compatibility

The admin interface works with:
- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+

---

## Installation Methods

### Method 1: WordPress Admin (Recommended)

**Best for:** Most users, easiest method

**Step 1: Download the Plugin**

1. Go to [GitHub Releases](https://github.com/yourusername/mbr-wp-performance/releases)
2. Download the latest `mbr-wp-performance-v1.4.9.zip` file
3. Save to your computer

**Step 2: Upload to WordPress**

1. Log into your WordPress admin panel
2. Navigate to **Plugins > Add New**
3. Click the **Upload Plugin** button at the top
4. Click **Choose File**
5. Select the `mbr-wp-performance-v1.4.9.zip` file you downloaded
6. Click **Install Now**

![Upload Plugin](../assets/screenshots/install-upload.png)

**Step 3: Activate**

1. Wait for the upload and installation to complete
2. Click **Activate Plugin**
3. You'll see a success message: "Plugin activated"

**Step 4: Access Settings**

1. Look for **WP Performance** in the WordPress admin toolbar (top right)
2. Click to access settings
3. Or hover to see dropdown with individual tabs

![Admin Toolbar](../assets/screenshots/admin-toolbar.png)

✅ **Installation complete!** Proceed to [First-Time Configuration](#first-time-configuration).

---

### Method 2: Manual Upload via FTP

**Best for:** Users comfortable with FTP, when admin upload fails

**Step 1: Download and Extract**

1. Download `mbr-wp-performance-v1.4.9.zip` from [GitHub Releases](https://github.com/yourusername/mbr-wp-performance/releases)
2. Extract the ZIP file on your computer
3. You should see a folder named `mbr-wp-performance`

**Step 2: Upload via FTP**

1. Connect to your server via FTP using:
   - FileZilla (Windows/Mac/Linux)
   - Cyberduck (Mac)
   - Or your host's file manager

2. Navigate to your WordPress installation directory

3. Open the `/wp-content/plugins/` folder

4. Upload the entire `mbr-wp-performance` folder

**FTP Upload Path:**
```
your-site/
└── wp-content/
    └── plugins/
        └── mbr-wp-performance/  ← Upload here
            ├── assets/
            ├── includes/
            ├── mbr-wp-performance.php
            └── readme.txt
```

**Step 3: Set Permissions (If Needed)**

```
Folders: 755
Files: 644
```

**How to set via FTP:**
1. Right-click the `mbr-wp-performance` folder
2. Select "File Permissions"
3. Set folders to 755, files to 644
4. Check "Recurse into subdirectories"
5. Click OK

**Step 4: Activate via WordPress Admin**

1. Log into WordPress admin
2. Go to **Plugins > Installed Plugins**
3. Find "MBR WP Performance"
4. Click **Activate**

✅ **Installation complete!**

---

### Method 3: WP-CLI

**Best for:** Developers, automated deployments, server administrators

**Prerequisites:**
- WP-CLI installed on server
- SSH access to server

**Installation via WP-CLI:**

```bash
# Navigate to WordPress directory
cd /path/to/wordpress

# Download plugin from GitHub
wp plugin install https://github.com/yourusername/mbr-wp-performance/releases/download/v1.4.9/mbr-wp-performance-v1.4.9.zip

# Activate the plugin
wp plugin activate mbr-wp-performance

# Verify installation
wp plugin list --name=mbr-wp-performance
```

**Expected Output:**
```
Success: Installed 1 of 1 plugins.
Success: Activated 1 of 1 plugins.

+-----------------------+----------+-----------+---------+
| name                  | status   | update    | version |
+-----------------------+----------+-----------+---------+
| mbr-wp-performance    | active   | none      | 1.4.9   |
+-----------------------+----------+-----------+---------+
```

**Install Specific Version:**
```bash
# Replace X.X.X with desired version
wp plugin install https://github.com/yourusername/mbr-wp-performance/releases/download/vX.X.X/mbr-wp-performance-vX.X.X.zip --activate
```

**Install from Local ZIP:**
```bash
wp plugin install /path/to/mbr-wp-performance-v1.4.9.zip --activate
```

**Uninstall via WP-CLI:**
```bash
# Deactivate first
wp plugin deactivate mbr-wp-performance

# Then delete
wp plugin delete mbr-wp-performance
```

✅ **Installation complete!**

---

## First-Time Configuration

After installation, follow these steps for optimal setup.

### Step 1: Access Settings

Click **WP Performance** in the admin toolbar (top of screen)

### Step 2: Recommended Initial Settings

**For best results, enable these features in this order:**

#### Week 1: Safe, High-Impact Features

**Database Tab:**
```
☑ Navigate to Database tab
☑ Click "Scan for Excess Revisions"
☑ Set "Keep Revisions" to 5
☑ Click "Delete Excess Revisions"
☑ Click "Get Transient Stats"
☑ Click "Delete Expired Transients"
```

**Lazy Loading Tab:**
```
☑ Enable "Lazy Load Images"
☑ Enable "Lazy Load iFrames and Videos"
```

**Fonts Tab:**
```
☑ Enable "Self-Host Google Fonts"
☑ Add your fonts (e.g., "Poppins:400,700")
☑ Click "Download Fonts"
☑ Enable "Preload Critical Fonts"
```

**Test your site thoroughly after these changes.**

#### Week 2: CSS Optimization

**CSS Tab:**
```
☑ Enable "Async Load CSS"
☑ Click "Auto-Generate Critical CSS"
☑ Paste result in Critical CSS textarea
☑ Enable "Inline Critical CSS"
☑ Test site
☑ If all works: Enable "Minify CSS"
```

#### Week 3: JavaScript (Careful!)

**JavaScript Tab:**
```
☑ Enable "Defer JavaScript Loading"
☑ Test ALL site functionality
☑ If sliders/menus break: Add to exclusions
```

**Common exclusions:**
```
jquery
slider
menu
navigation
```

### Step 3: Performance Testing

**Before optimizations:**
1. Test with [GTmetrix](https://gtmetrix.com)
2. Record: Load time, page size, requests
3. Take screenshot

**After each change:**
1. Clear all caches
2. Re-test with GTmetrix
3. Compare results
4. Keep if improved, revert if worse

### Step 4: Clear Caches

After any change, clear:

**Plugin caches (if you have caching plugins):**
- WP Rocket: Toolbar > Clear Cache
- LiteSpeed: Toolbar > Purge All
- WP Super Cache: Settings > Delete Cache

**Browser cache:**
- Hard refresh: `Ctrl + F5` (Windows) or `Cmd + Shift + R` (Mac)

**CDN cache (if using Cloudflare):**
- Cloudflare dashboard > Caching > Purge Everything

---

## Updating the Plugin

### Automatic Update (When Available on WordPress.org)

```
1. Dashboard > Updates
2. Check "MBR WP Performance"
3. Click "Update Plugins"
```

### Manual Update

**Step 1: Backup**
```
CRITICAL: Always backup before updating!

- Via plugin: UpdraftPlus, BackupBuddy
- Via host: cPanel backup, host backup tool
- Via WP-CLI: wp db export
```

**Step 2: Deactivate (Don't Delete)**
```
Plugins > Installed Plugins
Find: MBR WP Performance
Click: Deactivate
```

**Step 3: Delete Old Version**
```
Click: Delete
Confirm deletion
```

**Step 4: Install New Version**
```
Follow installation steps above
Upload new version ZIP
Activate
```

**Step 5: Verify Settings**
```
Settings are stored in database (preserved)
But check that all features still work
Re-test site functionality
```

### Update via FTP

```
1. Download new version
2. Connect via FTP
3. Navigate to /wp-content/plugins/
4. Delete old mbr-wp-performance folder
5. Upload new mbr-wp-performance folder
6. Activate via WordPress admin
```

### Update via WP-CLI

```bash
# List available updates
wp plugin list --update=available

# Update to latest version
wp plugin update mbr-wp-performance

# Update and activate
wp plugin update mbr-wp-performance --activate
```

---

## Uninstallation

### Complete Removal

**Step 1: Deactivate**
```
Plugins > Installed Plugins
Find: MBR WP Performance
Click: Deactivate
```

**Step 2: Delete Plugin**
```
Click: Delete
Confirm deletion
```

**Step 3: Clean Database (Optional)**

Plugin data is stored in `wp_options`:
```sql
DELETE FROM wp_options WHERE option_name = 'mbr_wp_performance_options';
DELETE FROM wp_options WHERE option_name = 'mbr_wp_performance_version';
DELETE FROM wp_options WHERE option_name = 'mbr_wp_performance_local_fonts';
DELETE FROM wp_options WHERE option_name = 'mbr_wp_performance_fonts_dir';
```

Or via WP-CLI:
```bash
wp option delete mbr_wp_performance_options
wp option delete mbr_wp_performance_version
wp option delete mbr_wp_performance_local_fonts
wp option delete mbr_wp_performance_fonts_dir
```

**Step 4: Remove Font Files (Optional)**

Via FTP or File Manager:
```
Delete: /wp-content/uploads/mbr-wp-performance-fonts/
```

Or via WP-CLI:
```bash
wp eval 'system("rm -rf wp-content/uploads/mbr-wp-performance-fonts");'
```

---

## Troubleshooting Installation

### Upload Failed - File Too Large

**Error:** "The uploaded file exceeds the upload_max_filesize directive in php.ini"

**Solution 1: Increase Upload Limit (Recommended)**

Contact your host to increase:
```
upload_max_filesize = 64M
post_max_size = 64M
```

**Solution 2: Use FTP Method**

Upload manually via FTP (see [Method 2](#method-2-manual-upload-via-ftp))

**Solution 3: Use WP-CLI**

Install via command line (see [Method 3](#method-3-wp-cli))

### Installation Failed - Permissions Error

**Error:** "Could not create directory" or "Installation failed"

**Solution: Fix File Permissions**

Via FTP:
```
/wp-content/plugins/ → 755
/wp-content/uploads/ → 755
```

Via SSH:
```bash
chmod 755 /path/to/wp-content/plugins
chmod 755 /path/to/wp-content/uploads
```

Via WP-CLI:
```bash
wp cli eval 'system("chmod 755 wp-content/plugins");'
```

### Plugin Activated But Not Showing

**Symptoms:** Activated successfully but no "WP Performance" in toolbar

**Causes:**
- Browser cache
- Admin user doesn't have permissions
- JavaScript error

**Solutions:**

**1. Clear Browser Cache**
```
Hard refresh: Ctrl+F5 (Windows) or Cmd+Shift+R (Mac)
Or: Clear browser cache completely
```

**2. Check User Permissions**
```
Users > Your Profile
Ensure Role: Administrator
```

**3. Check Browser Console**
```
F12 > Console tab
Look for JavaScript errors
Report any errors to support
```

**4. Verify Plugin Active**
```
Plugins > Installed Plugins
Ensure "MBR WP Performance" shows "Deactivate" link
```

### White Screen After Activation

**Symptoms:** Site shows blank page after activating

**Cause:** PHP error (usually memory limit or compatibility)

**Solution: Emergency Deactivation**

**Via FTP:**
```
1. Connect via FTP
2. Navigate to /wp-content/plugins/
3. Rename: mbr-wp-performance → mbr-wp-performance-disabled
4. Site should recover
5. Check PHP error logs
6. Report error to support
```

**Via Database:**
```
phpMyAdmin:
1. Open wp_options table
2. Find: option_name = 'active_plugins'
3. Edit value
4. Remove: "mbr-wp-performance/mbr-wp-performance.php"
5. Save
```

**Via WP-CLI:**
```bash
wp plugin deactivate mbr-wp-performance
```

### Settings Not Appearing in Toolbar

**Symptoms:** Plugin active but no toolbar menu

**Solutions:**

**1. Check Admin Bar Setting**
```
Users > Your Profile
Ensure: "Show Toolbar when viewing site" is checked
```

**2. Try Different Browser**
```
Test in Chrome/Firefox/Safari
Rules out browser-specific issue
```

**3. Disable Other Plugins**
```
Deactivate all other plugins
If toolbar appears → Plugin conflict
Reactivate one by one to find culprit
```

### Database Error During Installation

**Error:** "Error establishing a database connection"

**Cause:** Database temporarily unavailable

**Solutions:**

**1. Wait and Retry**
```
Wait 5 minutes
Retry installation
```

**2. Check Database Connection**
```
wp-config.php:
Verify DB_NAME, DB_USER, DB_PASSWORD correct
```

**3. Contact Host**
```
Database server may be down
Contact hosting support
```

---

## Post-Installation Checklist

After successful installation:

```
☐ Plugin appears in Plugins list as "Active"
☐ "WP Performance" visible in admin toolbar
☐ Can access settings by clicking toolbar menu
☐ All 7 tabs load correctly (Core, JavaScript, CSS, Fonts, Preloading, Lazy Loading, Database)
☐ Tooltips show when hovering over (?) icons
☐ Buttons respond to clicks
☐ Site frontend still displays correctly
☐ No JavaScript errors in browser console (F12)
☐ No PHP errors in error logs
```

If all items checked ✅ → Installation successful!

If any issues → See [Troubleshooting Guide](TROUBLESHOOTING.md)

---

## Next Steps

Now that the plugin is installed:

1. 📚 **Read the [Complete User Guide](docs/user-guide.md)** - Learn all features
2. 🚀 **Follow the [Quick Start Guide](docs/quick-start.md)** - Optimize in 5 minutes
3. 📊 **Test Performance** - Benchmark with GTmetrix before/after
4. 💬 **Join Discussions** - Ask questions, share results
5. ⭐ **Star the Repo** - If you find it useful!

---

## Getting Help

**Installation issues?**

1. Check [Troubleshooting Guide](TROUBLESHOOTING.md)
2. Search [existing issues](https://github.com/yourusername/mbr-wp-performance/issues)
3. Ask in [GitHub Discussions](https://github.com/yourusername/mbr-wp-performance/discussions)
4. [Open new issue](https://github.com/yourusername/mbr-wp-performance/issues/new) with:
   - WordPress version
   - PHP version
   - Installation method tried
   - Full error message
   - Screenshot if applicable

---

## Compatibility Notes

### WordPress Multisite

✅ Compatible with WordPress Multisite (Network)

**Network Activation:**
```
Network Admin > Plugins
Click: Network Activate
Settings accessible per-site
```

### WordPress.com

⚠️ Not compatible with WordPress.com (requires self-hosted WordPress)

### Page Builders

✅ Fully compatible with:
- Elementor
- Divi Builder
- Beaver Builder
- Oxygen
- Bricks
- WPBakery

Plugin automatically detects editor mode and disables optimizations.

### Caching Plugins

✅ Works alongside:
- WP Rocket
- LiteSpeed Cache
- WP Super Cache
- W3 Total Cache

See [User Guide](docs/user-guide.md) for optimal configuration with caching plugins.

### Hosting

✅ Tested on:
- Shared hosting
- VPS
- Managed WordPress (WP Engine, Kinsta, Flywheel)
- Cloud hosting (AWS, Digital Ocean)

---

*Installation complete? Head to the [Quick Start Guide](docs/quick-start.md) to optimize your site in 5 minutes!*

---

**Version:** 1.4.9  
**Last Updated:** February 2026  
**Maintained by:** [Made by Robert](https://madebyrobert.co.uk)
