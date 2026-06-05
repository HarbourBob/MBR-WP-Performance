<?php
/**
 * Multisite Network Support
 *
 * Handles network-wide activation, settings propagation,
 * per-site overrides, and the Network Admin settings page.
 *
 * @package MBRPE
 * @since   1.5.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Multisite class
 */
class MBRPE_Multisite {

    /**
     * Single instance.
     *
     * @var MBRPE_Multisite
     */
    private static $instance = null;

    /**
     * Get instance.
     *
     * @return MBRPE_Multisite
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialise hooks.
     */
    private function init_hooks() {
        // Network admin menu.
        add_action( 'network_admin_menu', array( $this, 'add_network_admin_menu' ) );

        // Enqueue assets on the network settings page.
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_network_admin_assets' ) );

        // Network admin AJAX handlers.
        add_action( 'wp_ajax_mbrpe_save_network_settings', array( $this, 'ajax_save_network_settings' ) );
        add_action( 'wp_ajax_mbrpe_push_to_sites', array( $this, 'ajax_push_to_sites' ) );

        // Toolbar link in network admin.
        add_action( 'admin_bar_menu', array( $this, 'add_network_toolbar_menu' ), 100 );
    }

    // ------------------------------------------------------------------
    //  Network Admin Menu
    // ------------------------------------------------------------------

    /**
     * Register the Network Admin settings page.
     */
    public function add_network_admin_menu() {
        add_submenu_page(
            'settings.php',
            __( 'MBR Performance – Network', 'mbr-performance' ),
            __( 'MBR Performance', 'mbr-performance' ),
            'manage_network_options',
            'mbr-performance-network',
            array( $this, 'render_network_settings_page' )
        );
    }

    /**
     * Add a toolbar shortcut visible in Network Admin.
     *
     * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
     */
    public function add_network_toolbar_menu( $wp_admin_bar ) {
        if ( ! is_network_admin() ) {
            return;
        }

        $wp_admin_bar->add_node( array(
            'id'    => 'mbr-performance-network',
            'title' => '<span class="ab-icon dashicons-performance"></span><span class="ab-label">' . __( 'MBR Performance (Network)', 'mbr-performance' ) . '</span>',
            'href'  => network_admin_url( 'settings.php?page=mbr-performance-network' ),
            'meta'  => array(
                'title' => __( 'MBR Performance Network Settings', 'mbr-performance' ),
            ),
        ) );
    }

    // ------------------------------------------------------------------
    //  Assets
    // ------------------------------------------------------------------

    /**
     * Enqueue admin assets on the network settings page.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_network_admin_assets( $hook ) {
        if ( strpos( $hook, 'mbr-performance-network' ) === false ) {
            return;
        }

        wp_enqueue_style(
            'mbr-performance-admin',
            MBRPE_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            MBRPE_VERSION
        );

        wp_enqueue_script(
            'mbr-performance-network-admin',
            MBRPE_PLUGIN_URL . 'assets/js/admin-clean.js',
            array( 'jquery' ),
            MBRPE_VERSION,
            true
        );

        wp_localize_script(
            'mbr-performance-network-admin',
            'mbrpeData',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'mbrpe_nonce' ),
                'i18n'    => array(
                    'saveSuccess'  => __( 'Network settings saved successfully.', 'mbr-performance' ),
                    'saveError'    => __( 'Error saving settings. Please try again.', 'mbr-performance' ),
                    'confirmReset' => __( 'Are you sure you want to reset all network settings to defaults?', 'mbr-performance' ),
                ),
            )
        );
    }

    // ------------------------------------------------------------------
    //  Render
    // ------------------------------------------------------------------

    /**
     * Render the network settings page.
     */
    public function render_network_settings_page() {
        if ( ! current_user_can( 'manage_network_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'mbr-performance' ) );
        }

        $network_options = self::get_network_options();
        $sites           = get_sites( array( 'number' => 0 ) );
        ?>
        <div class="wrap mbr-performance-wrap">
            <h1><?php esc_html_e( 'MBR Performance – Network Settings', 'mbr-performance' ); ?></h1>

            <?php require_once MBRPE_PLUGIN_DIR . 'includes/admin/tabs/network.php'; ?>
        </div>
        <?php
    }

    // ------------------------------------------------------------------
    //  AJAX handlers
    // ------------------------------------------------------------------

    /**
     * Save network-wide default settings.
     */
    public function ajax_save_network_settings() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_network_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-performance' ) ) );
        }

        $options = isset( $_POST['options'] ) ? $_POST['options'] : array();

        // Sanitize using the same method as per-site settings.
        $admin     = MBRPE_Admin::instance();
        $sanitized = $admin->sanitize_options( $options );

        // Network-level flag: allow per-site overrides.
        $allow_overrides = isset( $_POST['allow_site_overrides'] ) ? (bool) $_POST['allow_site_overrides'] : true;

        update_site_option( 'mbrpe_network_options', $sanitized );
        update_site_option( 'mbrpe_allow_site_overrides', $allow_overrides );

        wp_send_json_success( array( 'message' => __( 'Network settings saved successfully.', 'mbr-performance' ) ) );
    }

    /**
     * Push network settings to all sites (or selected sites).
     */
    public function ajax_push_to_sites() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_network_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-performance' ) ) );
        }

        $site_ids = isset( $_POST['site_ids'] ) ? array_map( 'absint', (array) $_POST['site_ids'] ) : array();
        $mode     = isset( $_POST['push_mode'] ) ? sanitize_text_field( $_POST['push_mode'] ) : 'all';

        $network_options = self::get_network_options();

        if ( empty( $network_options ) ) {
            wp_send_json_error( array( 'message' => __( 'No network settings found. Save network settings first.', 'mbr-performance' ) ) );
        }

        $sites = array();

        if ( 'all' === $mode ) {
            $sites = get_sites( array( 'number' => 0 ) );
        } elseif ( ! empty( $site_ids ) ) {
            foreach ( $site_ids as $id ) {
                $site = get_site( $id );
                if ( $site ) {
                    $sites[] = $site;
                }
            }
        }

        $updated = 0;
        $errors  = 0;

        foreach ( $sites as $site ) {
            switch_to_blog( $site->blog_id );

            $result = update_option( 'mbrpe_options', $network_options );

            // Mark that the site is using network defaults.
            update_option( 'mbrpe_using_network_defaults', true );

            if ( false !== $result || get_option( 'mbrpe_options' ) === $network_options ) {
                $updated++;
            } else {
                $errors++;
            }

            restore_current_blog();
        }

        wp_send_json_success( array(
            'message' => sprintf(
                // translators: 1: number of sites updated, 2: number of errors.
                __( 'Settings pushed to %1$d sites. Errors: %2$d', 'mbr-performance' ),
                $updated,
                $errors
            ),
        ) );
    }

    // ------------------------------------------------------------------
    //  Network activation / deactivation helpers
    // ------------------------------------------------------------------

    /**
     * Run activation routine across every site in the network.
     */
    public static function network_activate() {
        $sites = get_sites( array( 'number' => 0 ) );

        foreach ( $sites as $site ) {
            switch_to_blog( $site->blog_id );
            mbrpe()->activate();
            restore_current_blog();
        }

        // Store network-level version.
        update_site_option( 'mbrpe_network_version', MBRPE_VERSION );
    }

    /**
     * Run deactivation routine across every site in the network.
     */
    public static function network_deactivate() {
        $sites = get_sites( array( 'number' => 0 ) );

        foreach ( $sites as $site ) {
            switch_to_blog( $site->blog_id );
            mbrpe()->deactivate();
            restore_current_blog();
        }

        // Clean up network-level options (leave per-site data intact).
        delete_site_option( 'mbrpe_network_version' );
    }

    /**
     * Activate the plugin on a newly-created site (fired by wp_initialize_site).
     *
     * @param WP_Site $new_site New site object.
     */
    public static function on_new_site( $new_site ) {
        if ( ! is_plugin_active_for_network( MBRPE_PLUGIN_BASENAME ) ) {
            return;
        }

        switch_to_blog( $new_site->blog_id );

        mbrpe()->activate();

        // If network defaults exist, apply them.
        $network_options = self::get_network_options();
        if ( ! empty( $network_options ) ) {
            update_option( 'mbrpe_options', $network_options );
            update_option( 'mbrpe_using_network_defaults', true );
        }

        restore_current_blog();
    }

    // ------------------------------------------------------------------
    //  Option helpers
    // ------------------------------------------------------------------

    /**
     * Retrieve network-level default options.
     *
     * @return array
     */
    public static function get_network_options() {
        return get_site_option( 'mbrpe_network_options', array() );
    }

    /**
     * Whether per-site overrides are allowed by the super admin.
     *
     * @return bool
     */
    public static function allow_site_overrides() {
        return (bool) get_site_option( 'mbrpe_allow_site_overrides', true );
    }

    /**
     * Whether the current site is using network defaults (i.e. hasn't overridden).
     *
     * @return bool
     */
    public static function site_uses_network_defaults() {
        return (bool) get_option( 'mbrpe_using_network_defaults', false );
    }

    /**
     * Get the effective options for the current site.
     *
     * Priority:
     *  1. Per-site options (if overrides are allowed AND the site has opted out of defaults).
     *  2. Network-level defaults.
     *  3. Empty array (fallback).
     *
     * @return array
     */
    public static function get_effective_options() {
        if ( ! is_multisite() ) {
            return get_option( 'mbrpe_options', array() );
        }

        // If overrides are allowed and the site has its own settings, use them.
        if ( self::allow_site_overrides() && ! self::site_uses_network_defaults() ) {
            $site_options = get_option( 'mbrpe_options', array() );
            if ( ! empty( $site_options ) ) {
                return $site_options;
            }
        }

        // Fall back to network defaults.
        $network_options = self::get_network_options();
        if ( ! empty( $network_options ) ) {
            return $network_options;
        }

        // Absolute fallback.
        return get_option( 'mbrpe_options', array() );
    }
}
