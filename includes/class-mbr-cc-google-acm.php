<?php
/**
 * Google Additional Consent Mode (ACM)
 *
 * @package MBR_Cookie_Consent
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Google ACM class.
 */
class MBR_CC_Google_ACM {
    
    /**
     * Single instance.
     *
     * @var MBR_CC_Google_ACM
     */
    private static $instance = null;
    
    /**
     * ACM API version.
     *
     * @var int
     */
    const ACM_VERSION = 1;
    
    /**
     * Get instance.
     *
     * @return MBR_CC_Google_ACM
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
        // Output ACM initialization.
        add_action('wp_head', array($this, 'output_acm_initialization'), 2);
    }
    
    /**
     * Output ACM initialization.
     */
    public function output_acm_initialization() {
        if (!get_option('mbr_cc_google_acm_enabled', false)) {
            return;
        }
        
        ?>
        <!-- Google Additional Consent Mode -->
        <script>
        window.googlefc = window.googlefc || {};
        window.googlefc.callbackQueue = window.googlefc.callbackQueue || [];
        
        // Google ACM Callback
        window.googlefc.callbackQueue.push({
            'INITIAL_CCPA_DATA_READY': function() {
                // Handle CCPA data ready
                console.log('Google ACM: CCPA data ready');
            },
            'CONSENT_DATA_READY': function() {
                // Handle consent data ready
                console.log('Google ACM: Consent data ready');
            }
        });
        </script>
        <?php
    }
    
    /**
     * Get Google ATP (Ad Tech Provider) IDs.
     * These are Google's ad tech providers not in the IAB GVL.
     *
     * @return array ATP provider IDs and names.
     */
    public static function get_google_atp_providers() {
        // This is a sample list - in production, this should be fetched from Google's API.
        // Reference: https://support.google.com/admanager/answer/9681920
        return array(
            // Google ATP IDs (examples - these change frequently)
            '1' => 'Google Ads',
            '2' => 'DoubleClick',
            '3' => 'Google Analytics',
            '4' => 'Google Tag Manager',
            '5' => 'AdSense',
            '6' => 'AdMob',
            '7' => 'Google Ad Manager',
            '8' => 'Campaign Manager 360',
            '9' => 'Display & Video 360',
            '10' => 'Search Ads 360',
            // More providers would be listed here in production
        );
    }
    
    /**
     * Generate Additional Consent String (AC String).
     *
     * @param array $consented_providers Array of consented provider IDs.
     * @return string AC String.
     */
    public function generate_ac_string($consented_providers) {
        // AC String format: 1~{provider_ids}
        // Example: 1~1.2.3.4.5
        
        if (empty($consented_providers)) {
            return '';
        }
        
        // Sort provider IDs.
        sort($consented_providers, SORT_NUMERIC);
        
        // Build AC String.
        $ac_string = self::ACM_VERSION . '~' . implode('.', $consented_providers);
        
        return $ac_string;
    }
    
    /**
     * Parse AC String.
     *
     * @param string $ac_string AC String.
     * @return array Consented provider IDs.
     */
    public function parse_ac_string($ac_string) {
        if (empty($ac_string)) {
            return array();
        }
        
        // Parse AC String.
        $parts = explode('~', $ac_string);
        
        if (count($parts) !== 2) {
            return array();
        }
        
        $version = intval($parts[0]);
        $providers = $parts[1];
        
        if ($version !== self::ACM_VERSION) {
            return array();
        }
        
        // Get provider IDs.
        return array_map('intval', explode('.', $providers));
    }
    
    /**
     * Update Google tags with AC String.
     *
     * @param string $ac_string AC String.
     */
    public function update_google_tags($ac_string) {
        // This would be handled in JavaScript to update Google tags.
        // See assets/js/google-acm.js
    }
    
    /**
     * Check if provider is consented.
     *
     * @param int $provider_id Provider ID.
     * @param string $ac_string AC String.
     * @return bool Is consented.
     */
    public function is_provider_consented($provider_id, $ac_string) {
        $consented = $this->parse_ac_string($ac_string);
        return in_array($provider_id, $consented, true);
    }
    
    /**
     * Get AC String from cookie.
     *
     * @return string AC String.
     */
    public function get_ac_string_from_cookie() {
        if (isset($_COOKIE['mbr_cc_ac_string'])) {
            return sanitize_text_field($_COOKIE['mbr_cc_ac_string']);
        }
        return '';
    }
    
    /**
     * Save AC String to cookie.
     *
     * @param string $ac_string AC String.
     */
    public function save_ac_string_to_cookie($ac_string) {
        $expiry = time() + (get_option('mbr_cc_cookie_expiry_days', 365) * DAY_IN_SECONDS);
        $domain = apply_filters('mbr_cc_cookie_domain', '');
        
        setcookie(
            'mbr_cc_ac_string',
            $ac_string,
            $expiry,
            '/',
            $domain,
            is_ssl(),
            true // httponly
        );
    }
}
