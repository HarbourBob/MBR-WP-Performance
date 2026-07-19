# MBR Cookie Consent - Multisite Guide

Complete guide for using MBR Cookie Consent on WordPress Multisite networks.

---

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Network Installation](#network-installation)
- [Network Admin Interface](#network-admin-interface)
- [Network Settings](#network-settings)
- [Network Reports](#network-reports)
- [Site Management](#site-management)
- [Site Override Settings](#site-override-settings)
- [Database Structure](#database-structure)
- [Best Practices](#best-practices)
- [Troubleshooting](#troubleshooting)

---

## Overview

**Version 1.5.0** introduces full Enterprise Multisite Support for WordPress networks.

### What's New

✅ **Network-Wide Configuration** - Set banner settings once for entire network
✅ **Centralized Database** - Single consent log table with blog_id tracking
✅ **Network Admin Interface** - Dedicated network admin pages
✅ **Network Reports** - Aggregate analytics across all sites
✅ **Site Management** - View and manage all sites from one dashboard
✅ **Optional Site Overrides** - Allow individual sites to customize settings
✅ **Automatic New Site Setup** - New sites automatically get plugin activated
✅ **Site Deletion Cleanup** - Consent data removed when sites are deleted

---

## Features

### Network Admin Features

#### 1. Network Settings
- Configure default banner settings for all sites
- Set network-wide colors, layout, and content
- Control which buttons appear (Accept, Reject, Customize, Close)
- Enable/disable site override capability

#### 2. Network Reports
- **Total Consents**: Aggregate count across all sites
- **Last 30 Days**: Recent consent activity
- **Acceptance Rate**: Network-wide acceptance percentage
- **Top Sites**: Sites ranked by consent volume
- **Consent Timeline**: Daily consent trends for 30 days
- **Consent Methods**: Breakdown by banner, modal, etc.
- **Export**: Download all network consent data as CSV

#### 3. Site Management
- View all sites in network with statistics
- See which sites use network vs. custom settings
- Quick links to each site's settings and logs
- Network summary statistics
- Bulk actions (coming in future update)

---

## Network Installation

### Automatic Network Activation

1. Upload plugin to `/wp-content/plugins/`
2. Navigate to **Network Admin > Plugins**
3. Click **Network Activate** on MBR Cookie Consent
4. Plugin automatically:
   - Creates network-wide database table
   - Sets default network settings
   - Activates for all existing sites
   - Prepares for new site creation

### What Happens on Network Activation

```
✓ Creates wp_mbr_cc_consent_logs table (network-wide)
✓ Sets network default options
✓ Loops through all existing sites and activates individually
✓ Sets up hooks for new site creation
✓ Adds Network Admin menu items
```

### Manual Site Activation

If you prefer to activate per-site:

1. Go to **Network Admin > Plugins**
2. Do NOT network activate
3. Visit each site's dashboard individually
4. Activate plugin per-site

**Note**: Network activation is recommended for consistency.

---

## Network Admin Interface

Access via **Network Admin > Cookie Consent**

### Menu Structure

```
Cookie Consent (Network)
├── Network Settings
├── Network Reports
└── Site Management
```

### Accessing Network Admin

1. Go to **My Sites > Network Admin > Dashboard**
2. Or add `/wp-admin/network/` to your main site URL
3. Look for **Cookie Consent** menu item (shield icon)

---

## Network Settings

Configure default settings for all sites in the network.

### Available Network Settings

#### Banner Layout
- **Layout**: Bar, Box (Left/Right), Popup (Center)
- **Position**: Top or Bottom

#### Colors
- **Primary Color**: Banner background (#0073aa default)
- **Accept Button Color**: (#00a32a default)
- **Reject Button Color**: (#d63638 default)
- **Text Color**: (#ffffff default)

#### Banner Content
- **Heading**: Default "We value your privacy"
- **Description**: Customizable message

#### Banner Options
- **Show Reject Button**: Enable/disable (GDPR recommended: enabled)
- **Show Customize Button**: Enable/disable
- **Show Close Button**: Enable/disable (required for Italian law)
- **Cookie Expiry**: Days to remember consent (1-730)

#### Site Override Settings
- **Allow Site Overrides**: When enabled, individual sites can customize their own settings
- **When disabled**: All sites forced to use network settings

### How to Save

1. Configure your desired settings
2. Click **Save Network Settings**
3. Settings apply immediately to all sites using network defaults

---

## Network Reports

Enterprise-level analytics for entire network.

### Overview Cards

#### Total Consents
- Lifetime consent count across all sites
- Updated in real-time

#### Last 30 Days
- Recent consent activity
- Shows growth/decline trends

#### Acceptance Rate
- Percentage of users accepting vs. rejecting
- Network-wide average

#### Total Sites
- Number of sites in network
- All sites shown regardless of activity

### Top Sites by Consent Volume

**Table showing:**
- Site name and URL
- Total consent count per site
- Direct links to site settings and logs

**Use case**: Identify which sites have most traffic and need attention

### Consent Timeline (Last 30 Days)

**Daily breakdown:**
- Date
- Number of consents
- Visual bar chart

**Use case**: Identify traffic patterns and busy periods

### Consent Methods

**Breakdown by method:**
- Banner (most common)
- Modal/Popup
- Custom methods

Shows absolute count and percentage for each.

### Export Network Data

**Download CSV with:**
- All consent records across entire network
- Includes: ID, Blog ID, User ID, IP Address, User Agent, Consent Given, Categories, Method, Timestamp, Hash
- Useful for: GDPR compliance audits, legal requirements, long-term storage

---

## Site Management

Central dashboard to manage all sites.

### All Sites Table

For each site, view:

#### Site Information
- Site name
- Full URL
- Blog ID (internal identifier)

#### Statistics
- Total consent count
- Last consent timestamp (human-readable)

#### Settings Status
- **Custom**: Site has custom settings
- **Network**: Site uses network defaults

**Color coding:**
- 🔴 Red dot = Custom settings
- 🟢 Green dot = Network settings

#### Quick Actions
- **Settings**: Jump to site's cookie consent settings
- **Logs**: View site's consent logs
- **Visit**: Open site in new tab

### Network Summary

**Statistics:**
- Total sites in network
- Sites with consent data
- Sites with custom settings
- Sites using network settings
- Total network consents

### Bulk Actions

**Coming in future update:**
- Reset all sites to network settings
- Clear all consent logs
- Apply settings to selected sites
- Export per-site reports

---

## Site Override Settings

### Enabling Site Overrides

1. Go to **Network Admin > Cookie Consent > Network Settings**
2. Check **"Allow individual sites to override network settings"**
3. Save Network Settings

### How Site Overrides Work

When enabled:
- Site admins can customize their own settings
- Network settings serve as defaults
- Each setting can be overridden individually
- Unset settings fall back to network defaults

When disabled:
- All sites forced to use network settings
- Site admin settings page hidden or read-only
- Ensures consistency across network

### Per-Site Customization

**Site admins can customize:**
- Banner colors
- Banner text
- Layout and position
- Button visibility
- Policy links
- Logo

**Site admins CANNOT customize:**
- Network enforcement rules
- Database structure
- Core functionality

### Best Practice

**Recommended approach:**
1. Set sensible network defaults
2. Enable site overrides
3. Let sites customize branding (colors, logo)
4. Keep core behavior consistent (buttons, expiry)

---

## Database Structure

### Network-Wide Table

**Table Name:** `wp_mbr_cc_consent_logs`

**Uses `base_prefix`** instead of `prefix` - single table for entire network.

### Schema

```sql
CREATE TABLE wp_mbr_cc_consent_logs (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    blog_id bigint(20) UNSIGNED NOT NULL DEFAULT 1,
    user_id bigint(20) UNSIGNED DEFAULT NULL,
    ip_address varchar(45) NOT NULL,
    user_agent text NOT NULL,
    consent_given tinyint(1) NOT NULL DEFAULT 0,
    categories_accepted text DEFAULT NULL,
    consent_method varchar(50) NOT NULL,
    timestamp datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    cookie_hash varchar(64) NOT NULL,
    PRIMARY KEY (id),
    KEY blog_id (blog_id),
    KEY user_id (user_id),
    KEY timestamp (timestamp),
    KEY cookie_hash (cookie_hash)
);
```

### Key Features

**blog_id column:**
- Identifies which site the consent came from
- Enables per-site filtering
- Allows network-wide aggregation

**Indexes:**
- `blog_id` for fast per-site queries
- `timestamp` for date-range queries
- `cookie_hash` for deduplication

### Querying Examples

**Get consents for specific site:**
```sql
SELECT * FROM wp_mbr_cc_consent_logs 
WHERE blog_id = 2;
```

**Get network totals:**
```sql
SELECT COUNT(*) FROM wp_mbr_cc_consent_logs;
```

**Get per-site breakdown:**
```sql
SELECT blog_id, COUNT(*) as count 
FROM wp_mbr_cc_consent_logs 
GROUP BY blog_id;
```

---

## Best Practices

### For Network Admins

✅ **Set clear defaults** - Most sites should work with network settings
✅ **Enable overrides selectively** - Only if sites need branding customization
✅ **Monitor network reports** - Check weekly for trends
✅ **Export data regularly** - For compliance and backup
✅ **Document your network policy** - Tell site admins what's allowed

### For Site Admins

✅ **Use network defaults when possible** - Less maintenance
✅ **Customize branding only** - Colors, logo, text
✅ **Keep core behavior aligned** - Don't disable required buttons
✅ **Test after changes** - Verify banner works correctly
✅ **Export site logs periodically** - For your records

### For Compliance

✅ **GDPR (EU sites)**:
   - Enable Reject button network-wide
   - Set reasonable expiry (365 days)
   - Export data every 6 months
   - Keep logs for 2+ years

✅ **CCPA (California sites)**:
   - Enable CCPA mode if needed
   - Provide opt-out mechanism
   - Log all opt-out requests

✅ **Italian Law**:
   - Enable X close button
   - Required by Italian regulations
   - Allow closing without decision

---

## Troubleshooting

### Network Settings Not Saving

**Symptoms**: Click save, settings revert

**Solutions:**
1. Check you have `manage_network_options` capability
2. Verify nonce is valid (try refreshing page)
3. Check for JavaScript errors in console
4. Increase PHP `max_input_vars` to 3000+

### Sites Not Using Network Settings

**Symptoms**: Sites show different settings than network

**Possible causes:**
1. Site has custom settings saved
2. Site override is enabled
3. Settings cached

**Solutions:**
1. Check Site Management page - shows which sites have custom settings
2. Disable site overrides if all sites should match
3. Clear site cache (WP cache plugins)

### Consent Logs Missing blog_id

**Symptoms**: Old logs don't show blog_id

**Cause**: Upgraded from single-site version

**Solution:**
```sql
-- Backfill blog_id for old records
UPDATE wp_mbr_cc_consent_logs 
SET blog_id = 1 
WHERE blog_id IS NULL OR blog_id = 0;
```

### Network Reports Show Wrong Data

**Symptoms**: Numbers don't match individual sites

**Possible causes:**
1. Cache not cleared
2. Database query timeout
3. Deleted sites still have data

**Solutions:**
1. Hard refresh page (Ctrl+Shift+R)
2. Increase PHP execution time
3. Clean up orphaned data:
```sql
DELETE FROM wp_mbr_cc_consent_logs 
WHERE blog_id NOT IN (SELECT blog_id FROM wp_blogs);
```

### New Sites Not Getting Plugin

**Symptoms**: Create new site, plugin not active

**Cause**: Plugin not network-activated

**Solution:**
1. Go to **Network Admin > Plugins**
2. Click **Network Activate** on MBR Cookie Consent
3. New sites will auto-activate plugin

### Permission Denied Errors

**Symptoms**: "You do not have permission"

**Cause**: Non-super admin trying to access network pages

**Solution:**
- Only super admins can access Network Admin
- Site admins can only access their own site's settings
- Grant super admin status if needed

---

## Migration from Single-Site

If upgrading from single-site to multisite:

### Step 1: Backup

```bash
# Backup database
wp db export backup-before-multisite.sql

# Backup plugin files
cp -r wp-content/plugins/mbr-cookie-consent backup/
```

### Step 2: Update Plugin

1. Deactivate old version
2. Upload version 1.5.0
3. Network activate

### Step 3: Migrate Data

Old table: `wp_mbr_cc_consent_logs` (single-site)
New table: `wp_mbr_cc_consent_logs` (network-wide)

Add blog_id to existing records:
```sql
UPDATE wp_mbr_cc_consent_logs 
SET blog_id = 1 
WHERE blog_id IS NULL;
```

### Step 4: Verify

1. Check Network Admin appears
2. Verify network settings saved
3. Check network reports show data
4. Test on individual sites

---

