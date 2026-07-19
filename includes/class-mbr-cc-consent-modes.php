<?php
/**
 * Google Consent Mode v2 and Microsoft UET Consent Mode integration.
 *
 * @package MBR_Cookie_Consent
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Consent Modes class - handles Google and Microsoft consent mode integrations.
 */
class MBR_CC_Consent_Modes {
    
    /**
     * Single instance.
     *
     * @var MBR_CC_Consent_Modes
     */
    private static $instance = null;
    
    /**
     * Get instance.
     *
     * @return MBR_CC_Consent_Modes
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor.
     */
    private function __construct() {
        // Only load on frontend.
        if (!is_admin()) {
            add_action('wp_head', array($this, 'output_consent_mode_scripts'), 1);
        }
    }
    
    /**
     * Output Google Consent Mode v2 and Microsoft UET Consent Mode scripts.
     * These must load BEFORE Google Analytics, Google Ads, and Microsoft UET tags.
     */
    public function output_consent_mode_scripts() {
        $google_enabled = get_option('mbr_cc_google_consent_mode', false);
        $microsoft_enabled = get_option('mbr_cc_microsoft_consent_mode', false);
        
        // Get current consent state.
        $consent = $this->get_current_consent();
        
        ?>
        <!-- MBR Cookie Consent - Consent Mode Integration -->
        <script data-mbr-cc-consent-mode="true">
        <?php if ($google_enabled) : ?>
        // Google Consent Mode v2 - Default state (before user interaction)
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        
        gtag('consent', 'default', {
            'ad_storage': '<?php echo esc_js($this->get_google_consent_state($consent, 'marketing')); ?>',
            'ad_user_data': '<?php echo esc_js($this->get_google_consent_state($consent, 'marketing')); ?>',
            'ad_personalization': '<?php echo esc_js($this->get_google_consent_state($consent, 'marketing')); ?>',
            'analytics_storage': '<?php echo esc_js($this->get_google_consent_state($consent, 'analytics')); ?>',
            'functionality_storage': '<?php echo esc_js($this->get_google_consent_state($consent, 'preferences')); ?>',
            'personalization_storage': '<?php echo esc_js($this->get_google_consent_state($consent, 'preferences')); ?>',
            'security_storage': 'granted', // Always granted for security cookies
            'wait_for_update': 500 // Wait 500ms for user interaction
        });
        
        <?php if (get_option('mbr_cc_google_ads_redaction', true)) : ?>
        // Enable ads data redaction if marketing consent not given
        gtag('set', 'ads_data_redaction', <?php echo $consent && $this->has_category_consent($consent, 'marketing') ? 'false' : 'true'; ?>);
        <?php endif; ?>
        
        <?php if (get_option('mbr_cc_google_url_passthrough', false)) : ?>
        // Pass through ad click information in URLs (for conversion tracking without cookies)
        gtag('set', 'url_passthrough', true);
        <?php endif; ?>
        <?php endif; ?>
        
        <?php if ($microsoft_enabled) : ?>
        // Microsoft UET Consent Mode
        window.uetq = window.uetq || [];
        window.uetq.push('consent', 'default', {
            'ad_storage': '<?php echo esc_js($this->get_microsoft_consent_state($consent, 'marketing')); ?>'
        });
        <?php endif; ?>
        
        // MBR Cookie Consent - Update consent mode when user makes a choice
        window.MbrCcConsentModes = {
            updateGoogleConsent: function(consent) {
                <?php if ($google_enabled) : ?>
                if (typeof gtag === 'function') {
                    gtag('consent', 'update', {
                        'ad_storage': consent.marketing ? 'granted' : 'denied',
                        'ad_user_data': consent.marketing ? 'granted' : 'denied',
                        'ad_personalization': consent.marketing ? 'granted' : 'denied',
                        'analytics_storage': consent.analytics ? 'granted' : 'denied',
                        'functionality_storage': consent.preferences ? 'granted' : 'denied',
                        'personalization_storage': consent.preferences ? 'granted' : 'denied'
                    });
                    
                    <?php if (get_option('mbr_cc_google_ads_redaction', true)) : ?>
                    // Update ads data redaction
                    gtag('set', 'ads_data_redaction', !consent.marketing);
                    <?php endif; ?>
                }
                <?php endif; ?>
            },
            
            updateMicrosoftConsent: function(consent) {
                <?php if ($microsoft_enabled) : ?>
                if (typeof window.uetq !== 'undefined') {
                    window.uetq.push('consent', 'update', {
                        'ad_storage': consent.marketing ? 'granted' : 'denied'
                    });
                }
                <?php endif; ?>
            },
            
            updateAllConsent: function(consent) {
                this.updateGoogleConsent(consent);
                this.updateMicrosoftConsent(consent);
            }
        };
        </script>
        <?php
    }
    
    /**
     * Get current consent from cookie.
     *
     * @return array|null Consent data or null if no consent.
     */
    private function get_current_consent() {
        if (!isset($_COOKIE['mbr_cc_consent'])) {
            return null;
        }
        
        $consent = json_decode(stripslashes($_COOKIE['mbr_cc_consent']), true);
        return is_array($consent) ? $consent : null;
    }
    
    /**
     * Check if category consent is given.
     *
     * @param array|null $consent Consent data.
     * @param string $category Category slug.
     * @return bool Has consent.
     */
    private function has_category_consent($consent, $category) {
        if (empty($consent)) {
            return false;
        }
        
        // Check for "accept all".
        if (isset($consent['all']) && $consent['all'] === true) {
            return true;
        }
        
        // Check specific category.
        return isset($consent[$category]) && $consent[$category] === true;
    }
    
    /**
     * Get Google Consent Mode state for a category.
     *
     * @param array|null $consent Consent data.
     * @param string $category Category slug.
     * @return string 'granted' or 'denied'.
     */
    private function get_google_consent_state($consent, $category) {
        // If no consent decision yet, use regional defaults
        if ($consent === null) {
            // For EEA/UK users, default to denied (you could enhance this with geo-location)
            $default_deny = get_option('mbr_cc_google_default_deny', true);
            return $default_deny ? 'denied' : 'granted';
        }
        
        return $this->has_category_consent($consent, $category) ? 'granted' : 'denied';
    }
    
    /**
     * Get Microsoft UET Consent Mode state for a category.
     *
     * @param array|null $consent Consent data.
     * @param string $category Category slug.
     * @return string 'granted' or 'denied'.
     */
    private function get_microsoft_consent_state($consent, $category) {
        // If no consent decision yet, default to denied for EU compliance
        if ($consent === null) {
            $default_deny = get_option('mbr_cc_microsoft_default_deny', true);
            return $default_deny ? 'denied' : 'granted';
        }
        
        return $this->has_category_consent($consent, $category) ? 'granted' : 'denied';
    }
    
    /**
     * Get consent mode settings for admin display.
     *
     * @return array Settings data.
     */
    public static function get_settings() {
        return array(
            'google_enabled' => get_option('mbr_cc_google_consent_mode', false),
            'google_default_deny' => get_option('mbr_cc_google_default_deny', true),
            'google_ads_redaction' => get_option('mbr_cc_google_ads_redaction', true),
            'google_url_passthrough' => get_option('mbr_cc_google_url_passthrough', false),
            'microsoft_enabled' => get_option('mbr_cc_microsoft_consent_mode', false),
            'microsoft_default_deny' => get_option('mbr_cc_microsoft_default_deny', true),
        );
    }
}
