<?php
/**
 * Admin interface handler.
 *
 * @package MBR_Cookie_Consent
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin class.
 */
class MBR_CC_Admin {
    
    /**
     * Single instance.
     *
     * @var MBR_CC_Admin
     */
    private static $instance = null;
    
    /**
     * Get instance.
     *
     * @return MBR_CC_Admin
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // AJAX handlers.
        add_action('wp_ajax_mbr_cc_export_logs', array($this, 'ajax_export_logs'));
        add_action('wp_ajax_mbr_cc_delete_logs', array($this, 'ajax_delete_logs'));
        add_action('wp_ajax_mbr_cc_save_form_settings', array($this, 'ajax_save_form_settings'));
        add_action('wp_ajax_mbr_cc_save_ab_enabled', array($this, 'ajax_save_ab_enabled'));
    }
    
    /**
     * Add admin menu pages.
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Cookie Consent', 'mbr-cookie-consent'),
            __('Cookie Consent', 'mbr-cookie-consent'),
            'manage_options',
            'mbr-cookie-consent',
            array($this, 'render_dashboard_page'),
            'dashicons-shield',
            30
        );
        
        add_submenu_page(
            'mbr-cookie-consent',
            __('Dashboard', 'mbr-cookie-consent'),
            __('Dashboard', 'mbr-cookie-consent'),
            'manage_options',
            'mbr-cookie-consent',
            array($this, 'render_dashboard_page')
        );
        
        add_submenu_page(
            'mbr-cookie-consent',
            __('Settings', 'mbr-cookie-consent'),
            __('Settings', 'mbr-cookie-consent'),
            'manage_options',
            'mbr-cookie-consent-settings',
            array($this, 'render_settings_page')
        );
        
        add_submenu_page(
            'mbr-cookie-consent',
            __('Cookie Scanner', 'mbr-cookie-consent'),
            __('Cookie Scanner', 'mbr-cookie-consent'),
            'manage_options',
            'mbr-cookie-consent-scanner',
            array($this, 'render_scanner_page')
        );
        
        add_submenu_page(
            'mbr-cookie-consent',
            __('Consent Logs', 'mbr-cookie-consent'),
            __('Consent Logs', 'mbr-cookie-consent'),
            'manage_options',
            'mbr-cookie-consent-logs',
            array($this, 'render_logs_page')
        );
        
        add_submenu_page(
            'mbr-cookie-consent',
            __('Cookie Categories', 'mbr-cookie-consent'),
            __('Categories', 'mbr-cookie-consent'),
            'manage_options',
            'mbr-cookie-consent-categories',
            array($this, 'render_categories_page')
        );

        add_submenu_page(
            'mbr-cookie-consent',
            __('Form Integration', 'mbr-cookie-consent'),
            __('Form Integration', 'mbr-cookie-consent'),
            'manage_options',
            'mbr-cookie-consent-forms',
            array($this, 'render_forms_page')
        );

        add_submenu_page(
            'mbr-cookie-consent',
            __('A/B Testing', 'mbr-cookie-consent'),
            __('A/B Testing', 'mbr-cookie-consent'),
            'manage_options',
            'mbr-cookie-consent-ab-testing',
            array($this, 'render_ab_testing_page')
        );
    }
    
    /**
     * Enqueue admin assets.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_admin_assets($hook) {
        // Only load on plugin pages.
        if (strpos($hook, 'mbr-cookie-consent') === false) {
            return;
        }
        
        wp_enqueue_style(
            'mbr-cc-admin',
            MBR_CC_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            MBR_CC_VERSION
        );
        
        wp_enqueue_script(
            'mbr-cc-admin',
            MBR_CC_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker'),
            MBR_CC_VERSION,
            true
        );
        
        // Always enqueue settings JS and media library on plugin pages
        wp_enqueue_media();
        wp_enqueue_script(
            'mbr-cc-admin-settings',
            MBR_CC_PLUGIN_URL . 'assets/js/admin-settings.js',
            array('jquery'),
            MBR_CC_VERSION,
            true
        );
        
        wp_enqueue_style('wp-color-picker');
        
        wp_localize_script('mbr-cc-admin', 'mbrCcAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mbr_cc_admin_nonce'),
            'confirmDelete' => __('Are you sure you want to delete this item?', 'mbr-cookie-consent'),
            'confirmClear' => __('Are you sure you want to clear all blocked scripts?', 'mbr-cookie-consent'),
        ));
    }
    
    /**
     * Register settings.
     */
    public function register_settings() {
        // Settings will be registered via the Settings class.
    }
    
    /**
     * Render dashboard page.
     */
    public function render_dashboard_page() {
        $db = MBR_CC_Database::get_instance();
        $total_logs = $db->get_consent_count();
        $recent_logs = $db->get_consent_logs(array('limit' => 10));
        
        $blocker = MBR_CC_Script_Blocker::get_instance();
        $blocked_scripts = $blocker->get_blocked_scripts();
        
        $policy_page_id = MBR_CC_Policy_Generator::get_instance()->get_policy_page_id();
        
        require_once MBR_CC_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
    
    /**
     * Render settings page.
     */
    public function render_settings_page() {
        require_once MBR_CC_PLUGIN_DIR . 'admin/views/settings.php';
    }
    
    /**
     * Render scanner page.
     */
    public function render_scanner_page() {
        require_once MBR_CC_PLUGIN_DIR . 'admin/views/scanner.php';
    }
    
    /**
     * Render logs page.
     */
    public function render_logs_page() {
        require_once MBR_CC_PLUGIN_DIR . 'admin/views/logs.php';
    }
    
    /**
     * Render categories page.
     */
    public function render_categories_page() {
        require_once MBR_CC_PLUGIN_DIR . 'admin/views/categories.php';
    }

    public function render_forms_page() {
        require_once MBR_CC_PLUGIN_DIR . 'admin/views/form-integration.php';
    }

    public function render_ab_testing_page() {
        require_once MBR_CC_PLUGIN_DIR . 'admin/views/ab-testing.php';
    }
    
    /**
     * AJAX: Export consent logs to CSV.
     */
    public function ajax_export_logs() {
        check_ajax_referer('mbr_cc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $db = MBR_CC_Database::get_instance();
        
        $args = array();
        
        if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
            $args['date_from'] = sanitize_text_field($_GET['date_from']);
        }
        
        if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
            $args['date_to'] = sanitize_text_field($_GET['date_to']);
        }
        
        $csv = $db->export_to_csv($args);
        
        if (empty($csv)) {
            wp_die('No logs to export');
        }
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="cookie-consent-logs-' . gmdate('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // CSV is generated internally from sanitized log data and streamed as a file attachment, not rendered as HTML; standard output escaping functions are not applicable to CSV content.
        echo $csv; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }
    
    /**
     * AJAX: Delete old consent logs.
     */
    public function ajax_delete_logs() {
        check_ajax_referer('mbr_cc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
        }
        
        $days = isset($_POST['days']) ? absint($_POST['days']) : 365;
        
        // Refuse days < 1: strtotime("-0 days") resolves to "now", which
        // would delete every consent log in the table.
        if ($days < 1) {
            wp_send_json_error(array('message' => __('Days must be 1 or more.', 'mbr-cookie-consent')));
        }
        
        $db = MBR_CC_Database::get_instance();
        $deleted = $db->delete_old_logs($days);
        
        if ($deleted === false) {
            wp_send_json_error(array('message' => 'Failed to delete logs.'));
        }
        
        wp_send_json_success(array(
            /* translators: %d: number of consent log entries that were deleted. */
            'message' => sprintf(__('Deleted %d log entries.', 'mbr-cookie-consent'), $deleted),
            'deleted' => $deleted,
        ));
    }

    /**
     * AJAX: Save form integration settings.
     */
    public function ajax_save_form_settings() {
        check_ajax_referer('mbr_cc_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
        }
        update_option('mbr_cc_form_integration_enabled', !empty($_POST['enabled']));
        update_option('mbr_cc_form_integration_message', sanitize_text_field($_POST['message'] ?? ''));
        update_option('mbr_cc_form_required_category', sanitize_key($_POST['category'] ?? 'necessary'));
        wp_send_json_success(array('message' => __('Form integration settings saved.', 'mbr-cookie-consent')));
    }

    /**
     * AJAX: Save A/B testing enabled state.
     */
    public function ajax_save_ab_enabled() {
        check_ajax_referer('mbr_cc_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
        }
        update_option('mbr_cc_ab_testing_enabled', !empty($_POST['enabled']));
        wp_send_json_success(array('message' => __('A/B testing setting saved.', 'mbr-cookie-consent')));
    }
}
