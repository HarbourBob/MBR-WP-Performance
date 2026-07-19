<?php
/**
 * Geolocation Settings View
 *
 * @package MBR_Cookie_Consent
 * @version 2.1.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Check if geolocation is available
if (!function_exists('mbr_cc_geolocation')) {
    echo '<div class="notice notice-error"><p>Geolocation feature not loaded. Please refresh the page.</p></div>';
    return;
}

$geo = mbr_cc_geolocation();
$region_config = mbr_cc_region_config();

// Get current detection
$current_country = $geo->get_country();
$current_region = $geo->get_region();
$region_name = $geo->get_region_name();
?>

<div class="mbr-cc-settings-section">
    <h2><?php esc_html_e('Geolocation & Regional Compliance', 'mbr-cookie-consent'); ?></h2>
    <p><?php esc_html_e('Automatically detect user location and apply appropriate privacy law requirements (EU/EEA GDPR, UK DUAA, US Multi-State/GPC, Quebec Law 25, PIPEDA, Switzerland nFADP, Australia Privacy Act, Brazil LGPD, India DPDP, etc.)', 'mbr-cookie-consent'); ?></p>
    
    <!-- Current Detection Status -->
    <div class="mbr-cc-info-box" style="background: #e7f3e7; border-color: #46b450;">
        <h3 style="margin-top: 0;"><?php esc_html_e('Current Detection', 'mbr-cookie-consent'); ?></h3>
        <p><strong><?php esc_html_e('Your Country:', 'mbr-cookie-consent'); ?></strong> <?php echo esc_html($current_country ? $current_country : 'Not Detected'); ?></p>
        <p><strong><?php esc_html_e('Privacy Region:', 'mbr-cookie-consent'); ?></strong> <?php echo esc_html($region_name); ?></p>
        <p style="font-size: 12px; color: #666; margin-bottom: 0;">
            <?php esc_html_e('This is what visitors from your current IP address would see.', 'mbr-cookie-consent'); ?>
        </p>
    </div>
    
    <!-- Enable Geolocation -->
    <div class="mbr-cc-form-row" style="margin-top: 25px;">
        <div class="mbr-cc-form-field">
            <label>
                <input type="checkbox" name="mbr_cc_geolocation_enabled" value="1" <?php checked(get_option('mbr_cc_geolocation_enabled', false)); ?>>
                <?php esc_html_e('Enable Automatic Geolocation', 'mbr-cookie-consent'); ?>
            </label>
            <p class="description"><?php esc_html_e('Automatically detect user location and apply region-specific privacy requirements.', 'mbr-cookie-consent'); ?></p>
        </div>
    </div>
    
    <!-- API Provider Selection -->
    <div class="mbr-cc-form-row">
        <div class="mbr-cc-form-field">
            <label for="geolocation_provider"><?php esc_html_e('IP Lookup Provider', 'mbr-cookie-consent'); ?></label>
            <select name="mbr_cc_geolocation_provider" id="geolocation_provider">
                <option value="ip-api" <?php selected(get_option('mbr_cc_geolocation_provider', 'ip-api'), 'ip-api'); ?>>
                    ip-api.com (Free, 45 req/min)
                </option>
                <option value="ipapi" <?php selected(get_option('mbr_cc_geolocation_provider', 'ip-api'), 'ipapi'); ?>>
                    ipapi.co (Free, 1000 req/day)
                </option>
                <option value="cloudflare" <?php selected(get_option('mbr_cc_geolocation_provider', 'ip-api'), 'cloudflare'); ?>>
                    Cloudflare Headers (If using Cloudflare)
                </option>
            </select>
            <p class="description"><?php esc_html_e('Choose which service to use for IP geolocation. Cloudflare is fastest if you use Cloudflare CDN.', 'mbr-cookie-consent'); ?></p>
        </div>
        
        <div class="mbr-cc-form-field">
            <label for="geolocation_cache"><?php esc_html_e('Cache Duration', 'mbr-cookie-consent'); ?></label>
            <select name="mbr_cc_geolocation_cache" id="geolocation_cache">
                <option value="3600" <?php selected(get_option('mbr_cc_geolocation_cache', 86400), 3600); ?>>1 Hour</option>
                <option value="43200" <?php selected(get_option('mbr_cc_geolocation_cache', 86400), 43200); ?>>12 Hours</option>
                <option value="86400" <?php selected(get_option('mbr_cc_geolocation_cache', 86400), 86400); ?>>24 Hours (Recommended)</option>
                <option value="604800" <?php selected(get_option('mbr_cc_geolocation_cache', 86400), 604800); ?>>7 Days</option>
                <option value="2592000" <?php selected(get_option('mbr_cc_geolocation_cache', 86400), 2592000); ?>>30 Days</option>
            </select>
            <p class="description"><?php esc_html_e('How long to cache geolocation results per IP address.', 'mbr-cookie-consent'); ?></p>
        </div>
    </div>
    
    <!-- Default Region -->
    <div class="mbr-cc-form-row">
        <div class="mbr-cc-form-field">
            <label for="geolocation_default"><?php esc_html_e('Default Country (Fallback)', 'mbr-cookie-consent'); ?></label>
            <input type="text" name="mbr_cc_geolocation_default" id="geolocation_default" 
                   value="<?php echo esc_attr(get_option('mbr_cc_geolocation_default', 'US')); ?>" 
                   maxlength="2" style="width: 100px; text-transform: uppercase;">
            <p class="description"><?php esc_html_e('2-letter country code used when geolocation fails (e.g., US, GB, DE). Default: US', 'mbr-cookie-consent'); ?></p>
        </div>
    </div>
    
    <!-- Regional Compliance Information -->
    <h3 style="margin-top: 40px;"><?php esc_html_e('Regional Compliance Requirements', 'mbr-cookie-consent'); ?></h3>
    <p><?php esc_html_e('Understanding what each region requires for cookie consent compliance:', 'mbr-cookie-consent'); ?></p>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
        
        <!-- EU/EEA GDPR -->
        <div style="border: 1px solid #ddd; padding: 20px; border-radius: 6px; border-left: 4px solid #2271b1;">
            <h4 style="margin: 0 0 10px 0; display: flex; align-items: center; gap: 8px;">
                🇪🇺 <?php esc_html_e('EU/EEA - GDPR / ePrivacy', 'mbr-cookie-consent'); ?>
            </h4>
            <p style="font-size: 13px; color: #666; margin-bottom: 15px;">
                <?php esc_html_e('EU-27 + Iceland, Liechtenstein, Norway — strict opt-in regime', 'mbr-cookie-consent'); ?>
            </p>
            <ul style="font-size: 13px; line-height: 1.8; margin: 0;">
                <li>✓ <?php esc_html_e('Explicit opt-in required', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Reject equally prominent', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('No pre-ticked boxes', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Show cookie categories', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Easy consent withdrawal', 'mbr-cookie-consent'); ?></li>
            </ul>
            <p style="margin: 15px 0 0 0; padding: 10px; background: #fff3cd; border-radius: 4px; font-size: 12px;">
                <strong><?php esc_html_e('Penalties:', 'mbr-cookie-consent'); ?></strong> 
                <?php esc_html_e('Up to €20M or 4% of annual turnover', 'mbr-cookie-consent'); ?>
            </p>
        </div>
        
        <!-- UK DUAA -->
        <div style="border: 1px solid #ddd; padding: 20px; border-radius: 6px; border-left: 4px solid #0073aa;">
            <h4 style="margin: 0 0 10px 0; display: flex; align-items: center; gap: 8px;">
                🇬🇧 <?php esc_html_e('UK - GDPR + DUAA 2025', 'mbr-cookie-consent'); ?>
            </h4>
            <p style="font-size: 13px; color: #666; margin-bottom: 15px;">
                <?php esc_html_e('Separate regime since Feb 2026; ICO guidance finalised 29 Apr 2026', 'mbr-cookie-consent'); ?>
            </p>
            <ul style="font-size: 13px; line-height: 1.8; margin: 0;">
                <li>✓ <?php esc_html_e('5 PECR exemptions (analytics, functionality, etc.)', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('"Simple means of objecting" required', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Advertising still requires consent', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Purpose limitation enforced', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Complaints procedure by June 2026', 'mbr-cookie-consent'); ?></li>
            </ul>
            <p style="margin: 15px 0 0 0; padding: 10px; background: #fff3cd; border-radius: 4px; font-size: 12px;">
                <strong><?php esc_html_e('Penalties:', 'mbr-cookie-consent'); ?></strong> 
                <?php esc_html_e('Up to £17.5M or 4% of turnover (35x increase)', 'mbr-cookie-consent'); ?>
            </p>
        </div>
        
        <!-- US Multi-State -->
        <div style="border: 1px solid #ddd; padding: 20px; border-radius: 6px; border-left: 4px solid #d63638;">
            <h4 style="margin: 0 0 10px 0; display: flex; align-items: center; gap: 8px;">
                🇺🇸 <?php esc_html_e('US - Multi-State + GPC', 'mbr-cookie-consent'); ?>
            </h4>
            <p style="font-size: 13px; color: #666; margin-bottom: 15px;">
                <?php esc_html_e('20 states (IN, KY, RI added Jan 2026); MD MODPA effective Oct 2025', 'mbr-cookie-consent'); ?>
            </p>
            <ul style="font-size: 13px; line-height: 1.8; margin: 0;">
                <li>✓ <?php esc_html_e('"Do Not Sell or Share" link (CCPA)', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Honour GPC signals (CA, CO, CT, DE, MD, MN, MT, NE, NH, NJ, OR, TX)', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Opt-out based (not opt-in)', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Visible "Opt-Out Honored" toast (CA, mandatory Jan 2026)', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Sensitive data opt-in incl. neural data + under-16s (CA)', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('No false-urgency dark patterns (CA)', 'mbr-cookie-consent'); ?></li>
            </ul>
            <p style="margin: 15px 0 0 0; padding: 10px; background: #fff3cd; border-radius: 4px; font-size: 12px;">
                <strong><?php esc_html_e('Penalties:', 'mbr-cookie-consent'); ?></strong> 
                <?php esc_html_e('Up to $7,988 per intentional violation (CA); varies by state', 'mbr-cookie-consent'); ?>
            </p>
        </div>
        
        <!-- Brazil LGPD -->
        <div style="border: 1px solid #ddd; padding: 20px; border-radius: 6px; border-left: 4px solid #00a32a;">
            <h4 style="margin: 0 0 10px 0; display: flex; align-items: center; gap: 8px;">
                🇧🇷 <?php esc_html_e('Brazil - LGPD', 'mbr-cookie-consent'); ?>
            </h4>
            <p style="font-size: 13px; color: #666; margin-bottom: 15px;">
                <?php esc_html_e('Brazil (similar to GDPR)', 'mbr-cookie-consent'); ?>
            </p>
            <ul style="font-size: 13px; line-height: 1.8; margin: 0;">
                <li>✓ <?php esc_html_e('Clear consent required', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Show legitimate purpose', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Users can revoke consent', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Data minimization', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Similar to GDPR rules', 'mbr-cookie-consent'); ?></li>
            </ul>
            <p style="margin: 15px 0 0 0; padding: 10px; background: #fff3cd; border-radius: 4px; font-size: 12px;">
                <strong><?php esc_html_e('Penalties:', 'mbr-cookie-consent'); ?></strong> 
                <?php esc_html_e('Up to 2% of revenue (max R$50M)', 'mbr-cookie-consent'); ?>
            </p>
        </div>
        
        <!-- Canada PIPEDA -->
        <div style="border: 1px solid #ddd; padding: 20px; border-radius: 6px; border-left: 4px solid #8c5e58;">
            <h4 style="margin: 0 0 10px 0; display: flex; align-items: center; gap: 8px;">
                🇨🇦 <?php esc_html_e('Canada - PIPEDA / CASL', 'mbr-cookie-consent'); ?>
            </h4>
            <p style="font-size: 13px; color: #666; margin-bottom: 15px;">
                <?php esc_html_e('Federal regime; Quebec visitors get the stricter Law 25 config', 'mbr-cookie-consent'); ?>
            </p>
            <ul style="font-size: 13px; line-height: 1.8; margin: 0;">
                <li>✓ <?php esc_html_e('Meaningful consent required', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Purpose before collection', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Implied consent only in low-risk cases', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('CASL treats cookies as programs', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Bill C-27 (CPPA) may tighten rules', 'mbr-cookie-consent'); ?></li>
            </ul>
            <p style="margin: 15px 0 0 0; padding: 10px; background: #fff3cd; border-radius: 4px; font-size: 12px;">
                <strong><?php esc_html_e('Penalties:', 'mbr-cookie-consent'); ?></strong> 
                <?php esc_html_e('Up to $10M CAD per violation', 'mbr-cookie-consent'); ?>
            </p>
        </div>
        
        <!-- India DPDP -->
        <div style="border: 1px solid #ddd; padding: 20px; border-radius: 6px; border-left: 4px solid #ff6b35;">
            <h4 style="margin: 0 0 10px 0; display: flex; align-items: center; gap: 8px;">
                🇮🇳 <?php esc_html_e('India - DPDP Act + Rules 2025', 'mbr-cookie-consent'); ?>
            </h4>
            <p style="font-size: 13px; color: #666; margin-bottom: 15px;">
                <?php esc_html_e('Rules notified 13 Nov 2025 — phased rollout to May 2027', 'mbr-cookie-consent'); ?>
            </p>
            <ul style="font-size: 13px; line-height: 1.8; margin: 0;">
                <li>✓ <?php esc_html_e('Granular consent + one-click withdrawal', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Standalone privacy notice', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Verifiable parental consent for minors', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('72-hour breach notification', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Full compliance by 13 May 2027', 'mbr-cookie-consent'); ?></li>
            </ul>
            <p style="margin: 15px 0 0 0; padding: 10px; background: #fff3cd; border-radius: 4px; font-size: 12px;">
                <strong><?php esc_html_e('Penalties:', 'mbr-cookie-consent'); ?></strong> 
                <?php esc_html_e('Up to ₹250 crore (~£25M) per violation', 'mbr-cookie-consent'); ?>
            </p>
        </div>
        
        <!-- Vietnam PDPL -->
        <div style="border: 1px solid #ddd; padding: 20px; border-radius: 6px; border-left: 4px solid #da251d;">
            <h4 style="margin: 0 0 10px 0; display: flex; align-items: center; gap: 8px;">
                🇻🇳 <?php esc_html_e('Vietnam - PDPL (Law 91/2025)', 'mbr-cookie-consent'); ?>
            </h4>
            <p style="font-size: 13px; color: #666; margin-bottom: 15px;">
                <?php esc_html_e('In force 1 Jan 2026 — consent-centric, GDPR-like, extraterritorial', 'mbr-cookie-consent'); ?>
            </p>
            <ul style="font-size: 13px; line-height: 1.8; margin: 0;">
                <li>✓ <?php esc_html_e('Voluntary, specific, informed opt-in consent', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Silence is NOT consent', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Granular per-purpose consent (no bundling)', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Easy withdrawal at any time', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Heightened protection for children', 'mbr-cookie-consent'); ?></li>
            </ul>
            <p style="margin: 15px 0 0 0; padding: 10px; background: #fff3cd; border-radius: 4px; font-size: 12px;">
                <strong><?php esc_html_e('Penalties:', 'mbr-cookie-consent'); ?></strong> 
                <?php esc_html_e('Up to 5% of prior-year revenue; up to 10x unlawful data-trading gains', 'mbr-cookie-consent'); ?>
            </p>
        </div>
        
        <!-- Indonesia UU PDP -->
        <div style="border: 1px solid #ddd; padding: 20px; border-radius: 6px; border-left: 4px solid #ce1126;">
            <h4 style="margin: 0 0 10px 0; display: flex; align-items: center; gap: 8px;">
                🇮🇩 <?php esc_html_e('Indonesia - UU PDP (Law 27/2022)', 'mbr-cookie-consent'); ?>
            </h4>
            <p style="font-size: 13px; color: #666; margin-bottom: 15px;">
                <?php esc_html_e('Fully effective 17 Oct 2024, upheld by Constitutional Court Jan 2026 — GDPR-style, extraterritorial', 'mbr-cookie-consent'); ?>
            </p>
            <ul style="font-size: 13px; line-height: 1.8; margin: 0;">
                <li>✓ <?php esc_html_e('Explicit, informed opt-in consent', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Purpose-specific consent required', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Withdrawal must be honoured', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Cross-border transfers need safeguards', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Supervisory authority still developing — monitor', 'mbr-cookie-consent'); ?></li>
            </ul>
            <p style="margin: 15px 0 0 0; padding: 10px; background: #fff3cd; border-radius: 4px; font-size: 12px;">
                <strong><?php esc_html_e('Penalties:', 'mbr-cookie-consent'); ?></strong> 
                <?php esc_html_e('Admin fines up to 2% of annual revenue; criminal penalties up to IDR 6 billion', 'mbr-cookie-consent'); ?>
            </p>
        </div>
        
        <!-- Quebec Law 25 -->
        <div style="border: 1px solid #ddd; padding: 20px; border-radius: 6px; border-left: 4px solid #d62828;">
            <h4 style="margin: 0 0 10px 0; display: flex; align-items: center; gap: 8px;">
                🇨🇦 <?php esc_html_e('Quebec - Law 25', 'mbr-cookie-consent'); ?>
            </h4>
            <p style="font-size: 13px; color: #666; margin-bottom: 15px;">
                <?php esc_html_e('Stricter than PIPEDA — express opt-in, French-language banner', 'mbr-cookie-consent'); ?>
            </p>
            <ul style="font-size: 13px; line-height: 1.8; margin: 0;">
                <li>✓ <?php esc_html_e('Express opt-in (CAI rejects implied consent)', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('French-language banner required', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Detailed consent records kept', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Withdrawal at least as easy as consent', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Heightened protections for minors', 'mbr-cookie-consent'); ?></li>
            </ul>
            <p style="margin: 15px 0 0 0; padding: 10px; background: #fff3cd; border-radius: 4px; font-size: 12px;">
                <strong><?php esc_html_e('Penalties:', 'mbr-cookie-consent'); ?></strong> 
                <?php esc_html_e('Up to CA$25M or 4% of worldwide turnover', 'mbr-cookie-consent'); ?>
            </p>
        </div>
        
        <!-- Switzerland nFADP -->
        <div style="border: 1px solid #ddd; padding: 20px; border-radius: 6px; border-left: 4px solid #c8102e;">
            <h4 style="margin: 0 0 10px 0; display: flex; align-items: center; gap: 8px;">
                🇨🇭 <?php esc_html_e('Switzerland - revFADP / nFADP', 'mbr-cookie-consent'); ?>
            </h4>
            <p style="font-size: 13px; color: #666; margin-bottom: 15px;">
                <?php esc_html_e('Revised FADP in force since 1 September 2023 — GDPR-equivalent', 'mbr-cookie-consent'); ?>
            </p>
            <ul style="font-size: 13px; line-height: 1.8; margin: 0;">
                <li>✓ <?php esc_html_e('Consent for non-essential cookies', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Reject equally prominent', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Transparent collection notice', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Free, informed, unambiguous consent', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('EU-recognised adequate protection', 'mbr-cookie-consent'); ?></li>
            </ul>
            <p style="margin: 15px 0 0 0; padding: 10px; background: #fff3cd; border-radius: 4px; font-size: 12px;">
                <strong><?php esc_html_e('Penalties:', 'mbr-cookie-consent'); ?></strong> 
                <?php esc_html_e('Personal fines up to CHF 250,000 against responsible individuals', 'mbr-cookie-consent'); ?>
            </p>
        </div>
        
        <!-- Australia Privacy Act -->
        <div style="border: 1px solid #ddd; padding: 20px; border-radius: 6px; border-left: 4px solid #00843d;">
            <h4 style="margin: 0 0 10px 0; display: flex; align-items: center; gap: 8px;">
                🇦🇺 <?php esc_html_e('Australia - Privacy Act 1988', 'mbr-cookie-consent'); ?>
            </h4>
            <p style="font-size: 13px; color: #666; margin-bottom: 15px;">
                <?php esc_html_e('Amended by Privacy and Other Legislation Amendment Act 2024', 'mbr-cookie-consent'); ?>
            </p>
            <ul style="font-size: 13px; line-height: 1.8; margin: 0;">
                <li>✓ <?php esc_html_e('APP 5 notification at point of collection', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('APP 3 — collect only what is necessary', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Sensitive information requires opt-in', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('ADM transparency from 10 Dec 2026', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Children\'s Online Privacy Code by Dec 2026', 'mbr-cookie-consent'); ?></li>
            </ul>
            <p style="margin: 15px 0 0 0; padding: 10px; background: #fff3cd; border-radius: 4px; font-size: 12px;">
                <strong><?php esc_html_e('Penalties:', 'mbr-cookie-consent'); ?></strong> 
                <?php esc_html_e('Up to AU$50M, 30% of adjusted turnover, or 3x benefit', 'mbr-cookie-consent'); ?>
            </p>
        </div>
        
        <!-- Rest of World -->
        <div style="border: 1px solid #ddd; padding: 20px; border-radius: 6px; border-left: 4px solid #999;">
            <h4 style="margin: 0 0 10px 0; display: flex; align-items: center; gap: 8px;">
                🌍 <?php esc_html_e('Rest of World', 'mbr-cookie-consent'); ?>
            </h4>
            <p style="font-size: 13px; color: #666; margin-bottom: 15px;">
                <?php esc_html_e('Other countries and regions', 'mbr-cookie-consent'); ?>
            </p>
            <ul style="font-size: 13px; line-height: 1.8; margin: 0;">
                <li>✓ <?php esc_html_e('Best practice transparency', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Provide privacy policy', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Allow preference management', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('Respect user choices', 'mbr-cookie-consent'); ?></li>
                <li>✓ <?php esc_html_e('May use implied consent', 'mbr-cookie-consent'); ?></li>
            </ul>
            <p style="margin: 15px 0 0 0; padding: 10px; background: #f0f6fc; border-radius: 4px; font-size: 12px;">
                <strong><?php esc_html_e('Note:', 'mbr-cookie-consent'); ?></strong> 
                <?php esc_html_e('Requirements vary by jurisdiction', 'mbr-cookie-consent'); ?>
            </p>
        </div>
        
    </div>
    
    <!-- Testing Tool -->
    <h3 style="margin-top: 40px;"><?php esc_html_e('Testing & Debugging', 'mbr-cookie-consent'); ?></h3>
    
    <div class="mbr-cc-form-row">
        <div class="mbr-cc-form-field">
            <label for="test_country"><?php esc_html_e('Test with Country Code', 'mbr-cookie-consent'); ?></label>
            <input type="text" id="test_country" placeholder="e.g., GB, FR, US, CA, CH, AU" style="width: 200px;" maxlength="2">
            <input type="text" id="test_region" placeholder="<?php esc_attr_e('Region (optional, e.g. QC)', 'mbr-cookie-consent'); ?>" style="width: 220px;" maxlength="3">
            <button type="button" class="button" id="test-geolocation">
                <?php esc_html_e('Test Detection', 'mbr-cookie-consent'); ?>
            </button>
            <p class="description"><?php esc_html_e('Enter a 2-letter country code (and optional ISO 3166-2 region — e.g. CA + QC for Quebec) to see what banner configuration would be used for visitors from that location.', 'mbr-cookie-consent'); ?></p>
            <div id="test-results" style="margin-top: 15px; display: none;"></div>
        </div>
    </div>
    
    <!-- Clear Cache -->
    <div class="mbr-cc-form-row">
        <div class="mbr-cc-form-field">
            <button type="button" class="button" id="clear-geo-cache">
                <?php esc_html_e('Clear Geolocation Cache', 'mbr-cookie-consent'); ?>
            </button>
            <p class="description"><?php esc_html_e('Clear cached geolocation data to force fresh lookups for all visitors.', 'mbr-cookie-consent'); ?></p>
        </div>
    </div>
    
    <!-- Legal Disclaimer -->
    <div style="margin-top: 30px; padding: 20px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
        <h4 style="margin-top: 0;"><?php esc_html_e('⚖️ Legal Disclaimer', 'mbr-cookie-consent'); ?></h4>
        <p style="margin: 0; font-size: 13px; line-height: 1.8;">
            <?php esc_html_e('This plugin provides technical tools to help implement geolocation-based consent. It does NOT constitute legal advice. You are responsible for ensuring compliance with all applicable privacy laws. Consult with legal counsel regarding your specific requirements. Privacy laws change frequently - stay informed!', 'mbr-cookie-consent'); ?>
        </p>
    </div>
    
</div>

<script>
jQuery(document).ready(function($) {
    // Test geolocation
    $('#test-geolocation').on('click', function() {
        var country = $('#test_country').val().toUpperCase();
        var region  = $('#test_region').val().toUpperCase();
        if (country.length !== 2) {
            alert('Please enter a valid 2-letter country code');
            return;
        }
        
        $('#test-results').html('<p>Testing...</p>').show();
        
        $.post(ajaxurl, {
            action: 'mbr_cc_test_geolocation',
            country: country,
            region:  region,
            nonce: '<?php echo esc_js(wp_create_nonce("mbr_cc_geo_test")); ?>'
        }, function(response) {
            if (response.success) {
                var html = '<div style="padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 4px;">';
                var heading = country + (region ? ' / ' + region : '');
                html += '<h4 style="margin: 0 0 10px 0;">Results for ' + heading + ':</h4>';
                html += '<p><strong>Region:</strong> ' + response.data.region_name + '</p>';
                html += '<p><strong>Requires Consent:</strong> ' + (response.data.requires_consent ? 'Yes' : 'No') + '</p>';
                html += '<p><strong>Show Reject Button:</strong> ' + (response.data.show_reject ? 'Yes' : 'No') + '</p>';
                html += '<p><strong>Enable CCPA Link:</strong> ' + (response.data.enable_ccpa ? 'Yes' : 'No') + '</p>';
                if (response.data.gpc_enabled) {
                    html += '<p><strong>GPC Signal Honoured:</strong> Yes (mandated in CA, CO, CT, DE, MD, MN, MT, NE, NH, NJ, OR, TX)</p>';
                }
                if (response.data.duaa_exempt) {
                    html += '<p><strong>DUAA Exempt Categories:</strong> Analytics, Preferences (opt-out)</p>';
                }
                html += '</div>';
                $('#test-results').html(html);
            } else {
                $('#test-results').html('<p style="color: red;">Error: ' + response.data + '</p>');
            }
        });
    });
    
    // Clear cache
    $('#clear-geo-cache').on('click', function() {
        if (!confirm('Clear all geolocation cache data?')) {
            return;
        }
        
        $.post(ajaxurl, {
            action: 'mbr_cc_clear_geo_cache',
            nonce: '<?php echo esc_js(wp_create_nonce("mbr_cc_geo_cache")); ?>'
        }, function(response) {
            if (response.success) {
                alert('Geolocation cache cleared successfully!');
            } else {
                alert('Error clearing cache: ' + response.data);
            }
        });
    });
});
</script>
