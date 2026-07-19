<?php
/**
 * Plugin Name: MBR Cookie Consent
 * Plugin URI: https://littlewebshack.com/mbr-cookie-consent/
 * Description: GDPR/EEA, UK DUAA, CCPA/US multi-state, LGPD, PIPEDA, Quebec Law 25, Swiss nFADP, Australia Privacy Act, India DPDP, Vietnam PDPL, and global privacy law compliant cookie consent management with GPC signal support, automatic script blocking, and consent logging.
 * Version: 2.1.2
 * Author: Robert Palmer
 * Author URI: https://littlewebshack.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mbr-cookie-consent
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Network: true
 *
 * LEGAL DISCLAIMER: This plugin provides technical tools to help implement cookie consent
 * mechanisms. It does not constitute legal advice. Users are responsible for ensuring
 * their use of this plugin complies with applicable laws and should consult with legal
 * counsel regarding their specific compliance requirements.
 *
 */


if (!defined('ABSPATH')) {
    exit;
}

// Buy Me a Coffee
add_filter( 'plugin_row_meta', function ( $links, $file, $data ) {
    if ( ! function_exists( 'plugin_basename' ) || $file !== plugin_basename( __FILE__ ) ) {
        return $links;
    }

    $url = 'https://buymeacoffee.com/robertpalmer/';
    $links[] = sprintf(
	// translators: %s: The name of the plugin author.
        '<a href="%s" target="_blank" rel="noopener nofollow" aria-label="%s">☕ %s</a>',
        esc_url( $url ),
		// translators: %s: The name of the plugin author.
        esc_attr( sprintf( __( 'Buy %s a coffee', 'mbr-cookie-consent' ), isset( $data['AuthorName'] ) ? $data['AuthorName'] : __( 'the author', 'mbr-cookie-consent' ) ) ),
        esc_html__( 'Buy me a coffee', 'mbr-cookie-consent' )
    );

    return $links;
}, 10, 3 );

// Define plugin constants.
define('MBR_CC_VERSION', '2.1.2');
define('MBR_CC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MBR_CC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MBR_CC_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Self-hosted update delivery via Plugin Update Checker (PUC).
 *
 * Checks littlewebshack.com for new releases so updates appear in the standard
 * WordPress "Plugins" screen. No telemetry is sent; PUC only requests the
 * metadata JSON below and, when an update is available, the packaged ZIP.
 */
require_once MBR_CC_PLUGIN_DIR . 'lib/plugin-update-checker/plugin-update-checker.php';

$mbr_cc_update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
    'https://raw.githubusercontent.com/HarbourBob/mbr-updates/main/mbr-cookie-consent.json',
    __FILE__,
    'mbr-cookie-consent'
);

/**
 * Main plugin class.
 */
class MBR_Cookie_Consent {
    
    /**
     * Single instance of the class.
     *
     * @var MBR_Cookie_Consent
     */
    private static $instance = null;
    
    /**
     * Get single instance.
     *
     * @return MBR_Cookie_Consent
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
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Load required files.
     */
    private function load_dependencies() {
        require_once MBR_CC_PLUGIN_DIR . 'includes/class-mbr-cc-database.php';
        require_once MBR_CC_PLUGIN_DIR . 'includes/class-mbr-cc-geolocation.php';
        require_once MBR_CC_PLUGIN_DIR . 'includes/class-mbr-cc-region-config.php';
        require_once MBR_CC_PLUGIN_DIR . 'includes/class-mbr-cc-gpc-handler.php';
        require_once MBR_CC_PLUGIN_DIR . 'includes/class-mbr-cc-script-blocker.php';
        require_once MBR_CC_PLUGIN_DIR . 'includes/class-mbr-cc-blocked-placeholder.php';
        require_once MBR_CC_PLUGIN_DIR . 'includes/class-mbr-cc-banner.php';
        require_once MBR_CC_PLUGIN_DIR . 'includes/class-mbr-cc-consent-manager.php';
        require_once MBR_CC_PLUGIN_DIR . 'includes/class-mbr-cc-consent-modes.php';
        require_once MBR_CC_PLUGIN_DIR . 'includes/class-mbr-cc-i18n-accessibility.php';
        require_once MBR_CC_PLUGIN_DIR . 'includes/class-mbr-cc-enhanced-customization.php';
        require_once MBR_CC_PLUGIN_DIR . 'includes/class-mbr-cc-subdomain-consent.php';
        require_once MBR_CC_PLUGIN_DIR . 'includes/class-mbr-cc-iab-tcf.php';
        require_once MBR_CC_PLUGIN_DIR . 'includes/class-mbr-cc-google-acm.php';
        require_once MBR_CC_PLUGIN_DIR . 'includes/class-mbr-cc-cookie-scanner.php';
        require_once MBR_CC_PLUGIN_DIR . 'includes/class-mbr-cc-policy-generator.php';
        require_once MBR_CC_PLUGIN_DIR . 'includes/class-mbr-cc-privacy-policy-generator.php';
        require_once MBR_CC_PLUGIN_DIR . 'includes/class-mbr-cc-form-integration.php';
        require_once MBR_CC_PLUGIN_DIR . 'includes/class-mbr-cc-ab-testing.php';
        require_once MBR_CC_PLUGIN_DIR . 'admin/class-mbr-cc-admin.php';
        require_once MBR_CC_PLUGIN_DIR . 'admin/class-mbr-cc-settings.php';
        require_once MBR_CC_PLUGIN_DIR . 'admin/geolocation-ajax.php';
        
        // Load network admin for multisite
        if (is_multisite()) {
            require_once MBR_CC_PLUGIN_DIR . 'admin/class-mbr-cc-network-admin.php';
        }
    }
    
    /**
     * Initialize WordPress hooks.
     */
    private function init_hooks() {
        // Multisite-aware activation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Handle new site creation in multisite
        add_action('wpmu_new_blog', array($this, 'activate_new_site'), 10, 1);
        
        // Handle site deletion in multisite
        add_action('delete_blog', array($this, 'delete_site_data'), 10, 1);
        
        add_action('plugins_loaded', array($this, 'init'));
        add_action('init', array($this, 'load_textdomain'));
    }
    
    /**
     * Initialize plugin components.
     */
    public function init() {
        // Initialize database handler.
        MBR_CC_Database::get_instance();
        
        // Initialize script blocker (must run early).
        MBR_CC_Script_Blocker::get_instance();
        
        // Initialize consent manager.
        MBR_CC_Consent_Manager::get_instance();
        
        // Initialize consent modes (Google & Microsoft).
        MBR_CC_Consent_Modes::get_instance();
        
        // Initialize i18n and accessibility.
        MBR_CC_I18n_Accessibility::get_instance();
        
        // Initialize enhanced customization.
        MBR_CC_Enhanced_Customization::get_instance();
        
        // Initialize subdomain consent sharing.
        MBR_CC_Subdomain_Consent::get_instance();
        
        // Initialize IAB TCF v2.3.
        MBR_CC_IAB_TCF::get_instance();
        
        // Initialize Google ACM.
        MBR_CC_Google_ACM::get_instance();
        
        // Initialize geolocation and regional config (must run before banner).
        MBR_CC_Geolocation::get_instance();
        MBR_CC_Region_Config::get_instance();
        
        // Initialize GPC (Global Privacy Control) handler (must run before banner).
        MBR_CC_GPC_Handler::get_instance();
        
        // Initialize banner display.
        MBR_CC_Banner::get_instance();
        
        // Initialize network admin (multisite only)
        if (is_multisite() && is_network_admin()) {
            MBR_CC_Network_Admin::get_instance();
        }
        
        // Initialize admin interface.
        if (is_admin()) {
            MBR_CC_Admin::get_instance();
            MBR_CC_Settings::get_instance();
        }
        
        // Initialize cookie scanner.
        MBR_CC_Cookie_Scanner::get_instance();
        
        // Initialize policy generator.
        MBR_CC_Policy_Generator::get_instance();
        
        // Initialize privacy policy generator.
        MBR_CC_Privacy_Policy_Generator::get_instance();
        MBR_CC_Form_Integration::get_instance();
        MBR_CC_AB_Testing::get_instance();
    }
    
    /**
     * Load plugin textdomain for translations.
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'mbr-cookie-consent',
            false,
            dirname(MBR_CC_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Plugin activation.
     */
    public function activate($network_wide = false) {
        global $wpdb;
        
        if (is_multisite() && $network_wide) {
            // Network activation - activate for all sites
            $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
            
            foreach ($blog_ids as $blog_id) {
                switch_to_blog($blog_id);
                $this->activate_single_site();
                restore_current_blog();
            }
            
            // Set network-wide default options
            $this->set_network_default_options();
        } else {
            // Single site activation
            $this->activate_single_site();
        }
    }
    
    /**
     * Activate plugin for a single site.
     */
    private function activate_single_site() {
        // Create database tables (network-wide tables)
        MBR_CC_Database::create_tables();
        
        // Set default options for this site
        $this->set_default_options();
        
        // Update existing installations
        $this->maybe_update_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Activate plugin for newly created site in network.
     *
     * @param int $blog_id Blog ID of the new site.
     */
    public function activate_new_site($blog_id) {
        if (is_plugin_active_for_network(MBR_CC_PLUGIN_BASENAME)) {
            switch_to_blog($blog_id);
            $this->activate_single_site();
            restore_current_blog();
        }
    }
    
    /**
     * Delete site data when a site is deleted from network.
     *
     * @param int $blog_id Blog ID being deleted.
     */
    public function delete_site_data($blog_id) {
        global $wpdb;
        
        // Delete consent logs for this site
        $table_name = $wpdb->base_prefix . 'mbr_cc_consent_logs';
        $wpdb->delete($table_name, array('blog_id' => $blog_id), array('%d'));
        
        // Note: We don't delete the tables themselves as they're network-wide
    }
    
    /**
     * Maybe update options for existing installations.
     */
    private function maybe_update_options() {
        $current_version = get_option('mbr_cc_version', '0.0.0');
        
        // Version 1.0.3 updates
        if (version_compare($current_version, '1.0.3', '<')) {
            // Update revisit button text color to black if it's still white
            $revisit_color = get_option('mbr_cc_revisit_button_text_color', '#ffffff');
            if ($revisit_color === '#ffffff') {
                update_option('mbr_cc_revisit_button_text_color', '#000000');
            }
            
            // Update version
            update_option('mbr_cc_version', MBR_CC_VERSION);
        }
    }
    
    /**
     * Plugin deactivation.
     */
    public function deactivate() {
        // Flush rewrite rules.
        flush_rewrite_rules();
    }
    
    /**
     * Set default plugin options.
     */
    private function set_default_options() {
        $defaults = array(
            'banner_position' => 'bottom',
            'banner_layout' => 'bar',
            'primary_color' => '#0073aa',
            'accept_button_color' => '#00a32a',
            'reject_button_color' => '#d63638',
            'text_color' => '#ffffff',
            'revisit_button_text_color' => '#000000',
            'show_reject_button' => true,
            'show_customize_button' => true,
            'show_close_button' => false,
            'reload_on_consent' => false,
            'cookie_expiry_days' => 365,
            'enable_ccpa' => false,
            'ccpa_link_text' => 'Do Not Sell or Share My Personal Information',
            'banner_heading' => 'We value your privacy',
            'banner_description' => 'We use cookies to enhance your browsing experience, serve personalized content, and analyze our traffic. By clicking "Accept All", you consent to our use of cookies.',
            'accept_button_text' => 'Accept All',
            'reject_button_text' => 'Reject All',
            'customize_button_text' => 'Customize',
            'revisit_consent_enabled' => true,
            'revisit_consent_text' => 'Cookie Settings',
            'show_privacy_policy_link' => false,
            'privacy_policy_url' => '',
            'privacy_policy_text' => 'Privacy Policy',
            'show_cookie_policy_link' => false,
            'cookie_policy_url' => '',
            'cookie_policy_text' => 'Cookie Policy',
            'banner_logo_url' => '',
            'google_consent_mode' => false,
            'google_default_deny' => true,
            'google_ads_redaction' => true,
            'google_url_passthrough' => false,
            'microsoft_consent_mode' => false,
            'microsoft_default_deny' => true,
            'auto_translate' => true,
            'wcag_compliance' => true,
            'excluded_pages' => array(),
            'excluded_url_patterns' => '',
            'exclude_login' => false,
            'exclude_checkout' => false,
            'exclude_cart' => false,
            'exclude_account' => false,
            'custom_css' => '',
            'subdomain_sharing' => false,
            'subdomain_root_domain' => '',
            'iab_tcf_enabled' => false,
            'publisher_country_code' => '',
            'purpose_one_treatment' => false,
            'gdpr_applies' => 'auto',
            'google_acm_enabled' => false,
            // Blocked content overlay (v1.7.0).
            'blocked_overlay_enabled'  => false,
            'blocked_overlay_heading'  => '',
            'blocked_overlay_message'  => '',
            'blocked_overlay_btn_text' => '',
            'blocked_overlay_logo_url' => '',
        );
        
        foreach ($defaults as $key => $value) {
            if (false === get_option('mbr_cc_' . $key)) {
                add_option('mbr_cc_' . $key, $value);
            }
        }
        
        // Create default cookie categories if they don't exist.
        $this->create_default_categories();
    }
    
    /**
     * Set network-wide default options (multisite).
     */
    private function set_network_default_options() {
        // Enable multisite mode
        if (false === get_site_option('mbr_cc_multisite_enabled')) {
            add_site_option('mbr_cc_multisite_enabled', true);
        }
        
        // Network-wide settings (can be overridden per-site)
        $network_defaults = array(
            'network_banner_position' => 'bottom',
            'network_banner_layout' => 'bar',
            'network_primary_color' => '#0073aa',
            'network_accept_button_color' => '#00a32a',
            'network_reject_button_color' => '#d63638',
            'network_text_color' => '#ffffff',
            'network_show_reject_button' => true,
            'network_show_customize_button' => true,
            'network_show_close_button' => false,
            'network_cookie_expiry_days' => 365,
            'network_enable_ccpa' => false,
            'network_banner_heading' => 'We value your privacy',
            'network_banner_description' => 'We use cookies to enhance your browsing experience, serve personalized content, and analyze our traffic. By clicking "Accept All", you consent to our use of cookies.',
        );
        
        foreach ($network_defaults as $key => $value) {
            if (false === get_site_option('mbr_cc_' . $key)) {
                add_site_option('mbr_cc_' . $key, $value);
            }
        }
    }
    
    /**
     * Create default cookie categories.
     */
    private function create_default_categories() {
        $categories = get_option('mbr_cc_cookie_categories', array());
        
        if (empty($categories)) {
            $categories = array(
                'necessary' => array(
                    'name' => 'Necessary',
                    'description' => 'Necessary cookies are essential for the website to function properly. These cookies ensure basic functionalities and security features of the website.',
                    'required' => true,
                    'enabled' => true,
                ),
                'analytics' => array(
                    'name' => 'Analytics',
                    'description' => 'Analytics cookies help us understand how visitors interact with our website by collecting and reporting information anonymously.',
                    'required' => false,
                    'enabled' => false,
                ),
                'marketing' => array(
                    'name' => 'Marketing',
                    'description' => 'Marketing cookies are used to track visitors across websites to display relevant advertisements and encourage user engagement.',
                    'required' => false,
                    'enabled' => false,
                ),
                'preferences' => array(
                    'name' => 'Preferences',
                    'description' => 'Preference cookies enable a website to remember information that changes the way the website behaves or looks.',
                    'required' => false,
                    'enabled' => false,
                ),
            );
            
            update_option('mbr_cc_cookie_categories', $categories);
        }
    }
}

/**
 * Initialize the plugin.
 */
function mbr_cookie_consent() {
    return MBR_Cookie_Consent::get_instance();
}

// Start the plugin.
mbr_cookie_consent();
