<?php
/**
 * Consent Manager - handles user consent actions.
 *
 * @package MBR_Cookie_Consent
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Consent Manager class.
 */
class MBR_CC_Consent_Manager {
    
    /**
     * Single instance.
     *
     * @var MBR_CC_Consent_Manager
     */
    private static $instance = null;
    
    /**
     * Get instance.
     *
     * @return MBR_CC_Consent_Manager
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
        // AJAX handlers for consent actions.
        add_action('wp_ajax_mbr_cc_save_consent', array($this, 'ajax_save_consent'));
        add_action('wp_ajax_nopriv_mbr_cc_save_consent', array($this, 'ajax_save_consent'));
        
        add_action('wp_ajax_mbr_cc_get_consent', array($this, 'ajax_get_consent'));
        add_action('wp_ajax_nopriv_mbr_cc_get_consent', array($this, 'ajax_get_consent'));
        
        add_action('wp_ajax_mbr_cc_revoke_consent', array($this, 'ajax_revoke_consent'));
        add_action('wp_ajax_nopriv_mbr_cc_revoke_consent', array($this, 'ajax_revoke_consent'));
        
        // Enqueue frontend scripts.
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Enqueue consent management scripts.
     */
    public function enqueue_scripts() {
        // Don't load on admin pages.
        if (is_admin()) {
            return;
        }
        
        wp_enqueue_script(
            'mbr-cc-consent',
            MBR_CC_PLUGIN_URL . 'assets/js/consent-manager.js',
            array('jquery'),
            MBR_CC_VERSION,
            true
        );
        
        wp_localize_script('mbr-cc-consent', 'mbrCcConsent', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mbr_cc_consent_nonce'),
            'cookieName' => 'mbr_cc_consent',
            'cookieExpiry' => (int) get_option('mbr_cc_cookie_expiry_days', 365),
            'reloadOnConsent' => (bool) get_option('mbr_cc_reload_on_consent', false),
            'cookieDomain' => apply_filters('mbr_cc_cookie_domain', ''),
            'cookiePath' => apply_filters('mbr_cc_cookie_path', '/'),
        ));
    }
    
    /**
     * AJAX: Save user consent.
     */
    public function ajax_save_consent() {
        // Use a soft nonce check rather than check_ajax_referer() which dies on
        // failure. On cached sites the nonce baked into the page HTML can be
        // stale (caches persist beyond the 12-hour nonce lifetime). A failed
        // nonce must not block the consent log — the cookie is already set in JS
        // regardless of whether this AJAX call succeeds.
        $nonce_valid = isset( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'], 'mbr_cc_consent_nonce' );
        if ( ! $nonce_valid ) {
            // Log the issue but continue — do not bail out.
            error_log( 'MBR Cookie Consent: stale or missing nonce on consent save. Consent cookie already set client-side.' );
        }
        
        $consent_data = isset($_POST['consent']) ? json_decode(stripslashes($_POST['consent']), true) : array();
        $consent_method = isset($_POST['method']) ? sanitize_text_field($_POST['method']) : 'banner';
        
        if (empty($consent_data)) {
            wp_send_json_error(array('message' => 'Invalid consent data.'));
        }
        
        // Determine if consent was given.
        $consent_given = false;
        $categories_accepted = array();
        
        if (isset($consent_data['all']) && $consent_data['all'] === true) {
            $consent_given = true;
            $categories_accepted = array('necessary', 'analytics', 'marketing', 'preferences');
        } elseif (isset($consent_data['necessary']) && $consent_data['necessary'] === true) {
            $consent_given = true;
            
            // Collect accepted categories.
            foreach ($consent_data as $category => $accepted) {
                if ($accepted === true) {
                    $categories_accepted[] = $category;
                }
            }
        }
        
        // Log consent to database.
        $db = MBR_CC_Database::get_instance();
        $log_id = $db->log_consent(array(
            'consent_given' => $consent_given,
            'categories_accepted' => $categories_accepted,
            'consent_method' => $consent_method,
        ));
        
        if ($log_id) {
            wp_send_json_success(array(
                'message' => 'Consent saved successfully.',
                'log_id' => $log_id,
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to save consent.'));
        }
    }
    
    /**
     * AJAX: Get current consent.
     */
    public function ajax_get_consent() {
        check_ajax_referer('mbr_cc_consent_nonce', 'nonce');
        
        $consent = $this->get_user_consent();
        
        wp_send_json_success(array(
            'consent' => $consent,
            'hasConsent' => !empty($consent),
        ));
    }
    
    /**
     * AJAX: Revoke consent.
     */
    public function ajax_revoke_consent() {
        check_ajax_referer('mbr_cc_consent_nonce', 'nonce');
        
        // Log revocation.
        $db = MBR_CC_Database::get_instance();
        $db->log_consent(array(
            'consent_given' => false,
            'categories_accepted' => array(),
            'consent_method' => 'revoked',
        ));
        
        wp_send_json_success(array('message' => 'Consent revoked successfully.'));
    }
    
    /**
     * Get user consent from cookie.
     *
     * @return array Consent preferences.
     */
    public function get_user_consent() {
        if (!isset($_COOKIE['mbr_cc_consent'])) {
            return array();
        }
        
        $consent = json_decode(stripslashes($_COOKIE['mbr_cc_consent']), true);
        
        return is_array($consent) ? $consent : array();
    }
    
    /**
     * Check if user has given consent.
     *
     * @return bool Has consent.
     */
    public function has_consent() {
        return !empty($this->get_user_consent());
    }
    
    /**
     * Check if user consented to specific category.
     *
     * @param string $category Category slug.
     * @return bool Has category consent.
     */
    public function has_category_consent($category) {
        $consent = $this->get_user_consent();
        
        // Check for "accept all".
        if (isset($consent['all']) && $consent['all'] === true) {
            return true;
        }
        
        // Check specific category.
        return isset($consent[$category]) && $consent[$category] === true;
    }
    
    /**
     * Get cookie categories.
     *
     * @return array Categories.
     */
    public function get_categories() {
        return get_option('mbr_cc_cookie_categories', array());
    }
    
    /**
     * Update cookie categories.
     *
     * @param array $categories Categories data.
     * @return bool Success.
     */
    public function update_categories($categories) {
        return update_option('mbr_cc_cookie_categories', $categories);
    }
    
    /**
     * Get category by slug.
     *
     * @param string $slug Category slug.
     * @return array|false Category data or false.
     */
    public function get_category($slug) {
        $categories = $this->get_categories();
        return isset($categories[$slug]) ? $categories[$slug] : false;
    }
}
