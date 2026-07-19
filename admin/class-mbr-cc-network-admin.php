<?php
/**
 * Network Admin functionality for multisite.
 *
 * @package MBR_Cookie_Consent
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Network Admin class.
 */
class MBR_CC_Network_Admin {
    
    /**
     * Single instance.
     *
     * @var MBR_CC_Network_Admin
     */
    private static $instance = null;
    
    /**
     * Get instance.
     *
     * @return MBR_CC_Network_Admin
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
        if (is_multisite()) {
            add_action('network_admin_menu', array($this, 'add_network_admin_menu'));
            add_action('network_admin_edit_mbr_cc_network_settings', array($this, 'save_network_settings'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        }
    }
    
    /**
     * Add network admin menu.
     */
    public function add_network_admin_menu() {
        add_menu_page(
            __('Cookie Consent Network', 'mbr-cookie-consent'),
            __('Cookie Consent', 'mbr-cookie-consent'),
            'manage_network_options',
            'mbr-cc-network',
            array($this, 'render_network_settings_page'),
            'dashicons-shield',
            90
        );
        
        add_submenu_page(
            'mbr-cc-network',
            __('Network Settings', 'mbr-cookie-consent'),
            __('Network Settings', 'mbr-cookie-consent'),
            'manage_network_options',
            'mbr-cc-network',
            array($this, 'render_network_settings_page')
        );
        
        add_submenu_page(
            'mbr-cc-network',
            __('Network Reports', 'mbr-cookie-consent'),
            __('Network Reports', 'mbr-cookie-consent'),
            'manage_network_options',
            'mbr-cc-network-reports',
            array($this, 'render_network_reports_page')
        );
        
        add_submenu_page(
            'mbr-cc-network',
            __('Site Management', 'mbr-cookie-consent'),
            __('Site Management', 'mbr-cookie-consent'),
            'manage_network_options',
            'mbr-cc-site-management',
            array($this, 'render_site_management_page')
        );
    }
    
    /**
     * Enqueue admin assets.
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'mbr-cc-network') === false) {
            return;
        }
        
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        wp_enqueue_style(
            'mbr-cc-network-admin',
            MBR_CC_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            MBR_CC_VERSION
        );
        
        wp_enqueue_script(
            'mbr-cc-network-admin',
            MBR_CC_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker'),
            MBR_CC_VERSION,
            true
        );
    }
    
    /**
     * Render network settings page.
     */
    public function render_network_settings_page() {
        if (!current_user_can('manage_network_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'mbr-cookie-consent'));
        }
        
        require_once MBR_CC_PLUGIN_DIR . 'admin/views/network-settings.php';
    }
    
    /**
     * Render network reports page.
     */
    public function render_network_reports_page() {
        if (!current_user_can('manage_network_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'mbr-cookie-consent'));
        }
        
        require_once MBR_CC_PLUGIN_DIR . 'admin/views/network-reports.php';
    }
    
    /**
     * Render site management page.
     */
    public function render_site_management_page() {
        if (!current_user_can('manage_network_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'mbr-cookie-consent'));
        }
        
        require_once MBR_CC_PLUGIN_DIR . 'admin/views/site-management.php';
    }
    
    /**
     * Save network settings.
     */
    public function save_network_settings() {
        check_admin_referer('mbr-cc-network-settings');
        
        if (!current_user_can('manage_network_options')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'mbr-cookie-consent'));
        }
        
        // Network-wide settings
        $network_settings = array(
            'network_banner_position',
            'network_banner_layout',
            'network_primary_color',
            'network_accept_button_color',
            'network_reject_button_color',
            'network_text_color',
            'network_revisit_button_text_color',
            'network_show_reject_button',
            'network_show_customize_button',
            'network_show_close_button',
            'network_reload_on_consent',
            'network_cookie_expiry_days',
            'network_enable_ccpa',
            'network_ccpa_link_text',
            'network_banner_heading',
            'network_banner_description',
            'network_accept_button_text',
            'network_reject_button_text',
            'network_customize_button_text',
            'network_revisit_consent_enabled',
            'network_revisit_consent_text',
            'network_show_privacy_policy_link',
            'network_privacy_policy_url',
            'network_privacy_policy_text',
            'network_show_cookie_policy_link',
            'network_cookie_policy_url',
            'network_cookie_policy_text',
            'network_banner_logo_url',
            'network_allow_site_override',
        );
        
        foreach ($network_settings as $setting) {
            if (isset($_POST['mbr_cc_' . $setting])) {
                $value = $_POST['mbr_cc_' . $setting];
                
                // Sanitize based on type
                if (strpos($setting, '_color') !== false || strpos($setting, '_url') !== false) {
                    $value = sanitize_text_field($value);
                } elseif (strpos($setting, 'button') !== false || strpos($setting, 'enable') !== false || strpos($setting, 'allow') !== false) {
                    $value = (bool) $value;
                } elseif ($setting === 'network_cookie_expiry_days') {
                    $value = absint($value);
                } else {
                    $value = sanitize_text_field($value);
                }
                
                update_site_option('mbr_cc_' . $setting, $value);
            }
        }
        
        wp_redirect(add_query_arg(array(
            'page' => 'mbr-cc-network',
            'updated' => 'true'
        ), network_admin_url('admin.php')));
        exit;
    }
    
    /**
     * Get network statistics.
     *
     * @return array Network-wide statistics.
     */
    public function get_network_stats() {
        global $wpdb;
        
        $table_name = $wpdb->base_prefix . 'mbr_cc_consent_logs';
        
        // Total consents across network
        $total_consents = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        // Consents in last 30 days
        $recent_consents = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE timestamp >= %s",
            gmdate('Y-m-d H:i:s', strtotime('-30 days'))
        ));
        
        // Acceptance rate
        $accepted = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE consent_given = 1");
        $acceptance_rate = $total_consents > 0 ? ($accepted / $total_consents) * 100 : 0;
        
        // Sites with plugin active
        $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
        $total_sites = count($blog_ids);
        
        // Consents by site
        $consents_by_site = $wpdb->get_results(
            "SELECT blog_id, COUNT(*) as count 
            FROM $table_name 
            GROUP BY blog_id 
            ORDER BY count DESC 
            LIMIT 10"
        );
        
        return array(
            'total_consents' => $total_consents,
            'recent_consents' => $recent_consents,
            'acceptance_rate' => round($acceptance_rate, 2),
            'total_sites' => $total_sites,
            'consents_by_site' => $consents_by_site,
        );
    }
    
    /**
     * Export network consent data.
     */
    public function export_network_consent_data() {
        global $wpdb;
        
        $table_name = $wpdb->base_prefix . 'mbr_cc_consent_logs';
        
        $results = $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY timestamp DESC",
            ARRAY_A
        );
        
        if (empty($results)) {
            return;
        }
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="network-consent-logs-' . gmdate('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Add headers
        fputcsv($output, array(
            'ID',
            'Blog ID',
            'User ID',
            'IP Address',
            'User Agent',
            'Consent Given',
            'Categories Accepted',
            'Consent Method',
            'Timestamp',
            'Cookie Hash'
        ));
        
        // Add data
        foreach ($results as $row) {
            fputcsv($output, $row);
        }
        
        // Output stream (php://output) is used to stream the CSV download directly to the browser; WP_Filesystem operates on the local filesystem and cannot write to an output stream.
        fclose($output); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
        exit;
    }
}
