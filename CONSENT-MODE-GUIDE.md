# Consent Mode Integration Guide

## Overview

MBR Cookie Consent now includes full support for Google Consent Mode v2 and Microsoft UET Consent Mode, allowing your advertising and analytics tags to function compliantly within user consent parameters.

## What is Consent Mode?

Consent mode is a framework that allows advertising and analytics tags to adjust their behavior based on user consent choices. Instead of completely blocking tags when consent is denied, consent mode allows tags to operate in a "cookieless" mode that respects privacy while maintaining some measurement capabilities.

### Benefits

- **Privacy Compliant**: Respects user consent choices per GDPR/CCPA
- **Conversion Tracking**: Maintains conversion measurement even without full consent
- **Data Modeling**: Google/Microsoft use aggregated data to fill gaps when cookies denied
- **Better Insights**: More accurate reporting than completely blocking tags
- **Future-Proof**: Aligns with evolving privacy standards

---

## Google Consent Mode v2

### How It Works

1. **Initialization**: Consent mode script loads BEFORE Google tags
2. **Default State**: Sets initial consent (typically "denied" for EU compliance)
3. **Tag Behavior**: Google tags read consent signals and adjust behavior
4. **User Choice**: When user accepts/rejects, consent signals update
5. **Tag Response**: Tags immediately respond to new consent state

### Consent Types

| Consent Type | Controls | Maps to Category |
|--------------|----------|------------------|
| `ad_storage` | Advertising cookies | Marketing |
| `ad_user_data` | User data for ad targeting | Marketing |
| `ad_personalization` | Personalized ads | Marketing |
| `analytics_storage` | Analytics cookies (GA4) | Analytics |
| `functionality_storage` | Functional features | Preferences |
| `personalization_storage` | Website personalization | Preferences |
| `security_storage` | Security/fraud prevention | Necessary (always granted) |

### Setup Instructions

#### Step 1: Enable in Settings

1. Navigate to **Cookie Consent > Settings**
2. Scroll to **Consent Mode Integration**
3. Check **"Enable Google Consent Mode v2"**
4. Save settings

#### Step 2: Configure Options

**Default to "Denied"** ✅ Recommended
- Sets all consent types to "denied" initially
- Required for GDPR compliance in EU/EEA
- Google tags operate in cookieless mode until consent given

**Ads Data Redaction** ✅ Recommended
- Redacts ad-related data when marketing consent denied
- Prevents ad click IDs and user data from being collected
- Enhances privacy protection

**URL Passthrough** ⚠️ Optional
- Passes ad click information through URL parameters
- Enables conversion tracking without cookies
- May impact privacy - use only if necessary
- Consider regional regulations before enabling

#### Step 3: Verify Tag Installation

Your existing Google tags should already be installed. Consent mode works with:

- Google Analytics 4 (GA4)
- Google Ads (gtag.js)
- Google Tag Manager
- Floodlight

**No changes needed** to your existing tag implementation!

#### Step 4: Test

1. Open browser DevTools > Console
2. Refresh your page
3. Look for dataLayer messages:
   ```javascript
   // Initial state (denied)
   consent: "default", {ad_storage: "denied", analytics_storage: "denied", ...}
   ```
4. Click "Accept All" on cookie banner
5. See consent update:
   ```javascript
   // After acceptance
   consent: "update", {ad_storage: "granted", analytics_storage: "granted", ...}
   ```

#### Troubleshooting

**Google tags not receiving signals:**
- Ensure consent mode script loads BEFORE Google tags
- Check browser console for JavaScript errors
- Verify Google Tag Assistant shows consent mode active

**Consent not updating:**
- Clear cookies and test in incognito mode
- Check that MBR Cookie Consent script loaded successfully
- Verify category names match (marketing, analytics, preferences)

---

## Microsoft UET Consent Mode

### How It Works

Microsoft UET Consent Mode operates similarly to Google's implementation:

1. **Initialization**: UET consent script loads BEFORE UET tag
2. **Default State**: Sets initial consent (typically "denied" for EU)
3. **Tag Behavior**: UET tag adjusts tracking based on consent signals
4. **User Choice**: Consent updates when user accepts/rejects
5. **Tag Response**: UET immediately responds to consent changes

### Consent Types

| Consent Type | Controls | Maps to Category |
|--------------|----------|------------------|
| `ad_storage` | Advertising cookies for UET | Marketing |

Microsoft's implementation is simpler than Google's, focusing primarily on advertising consent.

### Setup Instructions

#### Step 1: Enable in Settings

1. Navigate to **Cookie Consent > Settings**
2. Scroll to **Consent Mode Integration**
3. Check **"Enable Microsoft UET Consent Mode"**
4. Save settings

#### Step 2: Configure Options

**Default to "Denied"** ✅ Recommended
- Sets ad_storage to "denied" initially
- Required for GDPR compliance in EU
- UET operates in limited mode until consent given

#### Step 3: Verify UET Tag Installation

Your Microsoft UET tag should already be installed:

```html
<script>
(function(w,d,t,r,u){
  var f,n,i;
  w[u]=w[u]||[],f=function(){
    var o={ti:"YOUR_TAG_ID"};
    o.q=w[u],w[u]=new UET(o),w[u].push("pageLoad")
  },
  n=d.createElement(t),n.src=r,n.async=1,
  n.onload=n.onreadystatechange=function(){
    var s=this.readyState;
    s&&s!=="loaded"&&s!=="complete"||(f(),n.onload=n.onreadystatechange=null)
  },
  i=d.getElementsByTagName(t)[0],i.parentNode.insertBefore(n,i)
})(window,document,"script","//bat.bing.com/bat.js","uetq");
</script>
```

**No changes needed** to your existing UET implementation!

#### Step 4: Test

1. Open browser DevTools > Console
2. Refresh page
3. Look for UET consent commands:
   ```javascript
   // Initial state
   uetq.push("consent", "default", {ad_storage: "denied"})
   ```
4. Accept cookies
5. See update:
   ```javascript
   // After acceptance
   uetq.push("consent", "update", {ad_storage: "granted"})
   ```

---

## Category Mapping

MBR Cookie Consent maps its cookie categories to consent mode signals:

| MBR Category | Google Consent Types | Microsoft Consent |
|--------------|---------------------|-------------------|
| **Necessary** | security_storage (always granted) | N/A |
| **Analytics** | analytics_storage | N/A |
| **Marketing** | ad_storage, ad_user_data, ad_personalization | ad_storage |
| **Preferences** | functionality_storage, personalization_storage | N/A |

### How It Works

When a user accepts or rejects categories:

1. User clicks "Accept All" → All consent types set to "granted"
2. User clicks "Reject All" → Only necessary/security set to "granted"
3. User customizes:
   - Accepts Analytics → `analytics_storage: granted`
   - Accepts Marketing → `ad_storage: granted`, `ad_user_data: granted`, etc.
   - Accepts Preferences → `functionality_storage: granted`, `personalization_storage: granted`

---

## Advanced Configuration

### Regional Defaults

The plugin defaults to "denied" for privacy compliance. If you operate in regions without strict consent requirements (e.g., US), you might consider:

```php
// In your theme's functions.php (advanced users only)
add_filter('mbr_cc_google_default_deny', function($default) {
    // Change default to "granted" for specific regions
    // Note: This bypasses GDPR protections - use carefully
    return false; // Changes default from denied to granted
});
```

**Warning**: Only change defaults if you're certain consent is not required in your jurisdiction.

### Custom Consent Signals

Developers can manually trigger consent updates:

```javascript
// Update Google consent
window.MbrCcConsentModes.updateGoogleConsent({
    marketing: true,
    analytics: true,
    preferences: false
});

// Update Microsoft consent
window.MbrCcConsentModes.updateMicrosoftConsent({
    marketing: true
});

// Update both
window.MbrCcConsentModes.updateAllConsent({
    marketing: true,
    analytics: true,
    preferences: true
});
```

---

## Compliance Considerations

### GDPR (EU/EEA)

✅ **Recommended Settings:**
- Google: Default to "Denied" - **Enabled**
- Google: Ads Data Redaction - **Enabled**
- Microsoft: Default to "Denied" - **Enabled**

These settings ensure:
- No cookies set before consent
- Cookieless measurement possible
- Full compliance with opt-in requirements

### CCPA (California)

The consent mode settings work with CCPA's "Do Not Sell" option:
- "Do Not Sell" link → Denies marketing consent
- Marketing consent denied → ad_storage denied
- Limited data collection for opted-out users

### Other Regions

For regions without specific consent requirements:
- Consider keeping defaults as "denied" for privacy-first approach
- Or adjust defaults based on local regulations
- Consult legal counsel for specific requirements

---

## Performance & Loading Order

### Critical Loading Sequence

1. **Consent Mode Script** (in `<head>`, priority 1)
   - Loaded by MBR Cookie Consent automatically
   - Sets default consent state
   - Must load first!

2. **Google/Microsoft Tags** (normal position)
   - Your existing GA4, Google Ads, UET tags
   - Load in their normal positions
   - Automatically receive consent signals

3. **User Interaction** (on consent choice)
   - Consent signals update
   - Tags respond in real-time

### Performance Impact

- **Minimal**: Consent mode script is <2KB
- **No blocking**: Tags still load (just behave differently)
- **Better than blocking**: Maintains conversion measurement

---

## FAQ

**Q: Do I need to modify my existing Google Analytics or Google Ads code?**
A: No! The consent mode integration works with your existing tags without modification.

**Q: Will this affect my conversion tracking?**
A: With consent mode, Google can still measure conversions even when cookies are denied, using conversion modeling and aggregated data.

**Q: What happens if a user denies all cookies?**
A: Google and Microsoft tags operate in a "cookieless" mode, sending anonymized pings without storing cookies. Conversion data is modeled.

**Q: Is consent mode required for GDPR compliance?**
A: No, but it's recommended. It provides better measurement than completely blocking tags while still respecting user choices.

**Q: Can I use consent mode with other cookie plugins?**
A: No, MBR Cookie Consent handles both script blocking AND consent mode. Using multiple consent solutions will cause conflicts.

**Q: Does consent mode replace cookie blocking?**
A: No, MBR Cookie Consent still blocks non-consented scripts. Consent mode is an additional layer that signals consent state to tags that do load.

**Q: How do I verify consent mode is working?**
A: Use Google Tag Assistant (Chrome extension) or check browser console for dataLayer/uetq consent commands.

---

## Support Resources

- **Google Consent Mode Documentation**: https://support.google.com/google-ads/answer/10000067
- **Microsoft UET Consent Mode**: https://help.ads.microsoft.com/apex/index/3/en/56916
- **GDPR Guidelines**: https://gdpr.eu/cookies/

---

## Developer API

For developers integrating with the consent mode functionality:

```javascript
// Check if consent modes are initialized
if (typeof window.MbrCcConsentModes !== 'undefined') {
    // Consent mode is active
}

// Listen for consent changes (custom event)
document.addEventListener('mbr_cc_consent_updated', function(e) {
    console.log('Consent updated:', e.detail);
    // e.detail contains: {marketing: true/false, analytics: true/false, etc.}
});

// Get current consent state
var consent = JSON.parse(getCookie('mbr_cc_consent'));
```

---

