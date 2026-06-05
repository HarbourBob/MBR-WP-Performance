<?php
/**
 * WooCommerce Optimizations
 *
 * @package MBRPE
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WooCommerce Optimizations class
 */
class MBRPE_WooCommerce_Optimizations {

    /**
     * Single instance
     *
     * @var MBRPE_WooCommerce_Optimizations
     */
    private static $instance = null;

    /**
     * Options
     *
     * @var array
     */
    private $options = array();

    /**
     * Legacy options (from core / css tabs, kept for backward compatibility)
     *
     * @var array
     */
    private $legacy_core = array();
    private $legacy_css  = array();

    /**
     * Get instance
     *
     * @return MBRPE_WooCommerce_Optimizations
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Bail entirely if WooCommerce isn't active.
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        $this->options      = mbrpe()->get_options( 'woocommerce' );
        $this->legacy_core  = mbrpe()->get_options( 'core' );
        $this->legacy_css   = mbrpe()->get_options( 'css' );

        $this->init_optimizations();
    }

    /**
     * Get option value, with legacy fallback for migrated settings
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    private function get_option( $key, $default = false ) {
        if ( isset( $this->options[ $key ] ) ) {
            return $this->options[ $key ];
        }

        // Legacy fallbacks — honour the older options until users re-save the WC tab.
        if ( $key === 'disable_scripts_non_shop' && ! empty( $this->legacy_core['disable_woocommerce_scripts'] ) ) {
            return true;
        }

        if ( $key === 'disable_styles_non_shop' && ! empty( $this->legacy_css['disable_woocommerce_css'] ) ) {
            return true;
        }

        return $default;
    }

    /**
     * Initialize optimizations
     */
    private function init_optimizations() {
        // Frontend: cart fragments
        $mode = $this->get_option( 'cart_fragments_mode', 'default' );
        if ( $mode === 'non_shop' || $mode === 'always' ) {
            add_action( 'wp_enqueue_scripts', array( $this, 'handle_cart_fragments' ), 99 );
        }

        // Frontend: WC scripts on non-shop pages
        if ( $this->get_option( 'disable_scripts_non_shop' ) ) {
            add_action( 'wp_enqueue_scripts', array( $this, 'disable_scripts_non_shop' ), 99 );
        }

        // Frontend: WC styles on non-shop pages
        if ( $this->get_option( 'disable_styles_non_shop' ) ) {
            add_action( 'wp_enqueue_scripts', array( $this, 'disable_styles_non_shop' ), 99 );
        }

        // Frontend: WC block assets on non-shop pages
        if ( $this->get_option( 'disable_block_assets_non_shop' ) ) {
            add_action( 'wp_enqueue_scripts', array( $this, 'disable_block_assets_non_shop' ), 99 );
        }

        // Frontend: password strength meter
        if ( $this->get_option( 'disable_password_strength' ) ) {
            add_action( 'wp_print_scripts', array( $this, 'disable_password_strength' ), 100 );
        }

        // Admin: marketplace suggestions
        if ( $this->get_option( 'disable_marketplace_suggestions' ) ) {
            add_filter( 'woocommerce_allow_marketplace_suggestions', '__return_false' );
        }

        // Admin: dashboard widgets
        if ( $this->get_option( 'disable_dashboard_widgets' ) ) {
            add_action( 'wp_dashboard_setup', array( $this, 'remove_dashboard_widgets' ), 99 );
        }

        // Admin: WC admin scripts on non-WC admin pages
        if ( $this->get_option( 'disable_admin_scripts_non_wc' ) ) {
            add_action( 'admin_enqueue_scripts', array( $this, 'disable_admin_scripts_non_wc' ), 999 );
        }

        // Action Scheduler retention override
        $retention = absint( $this->get_option( 'action_scheduler_retention', 0 ) );
        if ( $retention > 0 ) {
            add_filter( 'action_scheduler_retention_period', function () use ( $retention ) {
                return $retention * DAY_IN_SECONDS;
            } );
        }

        // Weekly automated cleanup — listens on the plugin's existing cron hook.
        // (The hook is scheduled in the main plugin file's activate() method but
        // previously had no listener; we attach here so the weekly job actually runs.)
        if ( $this->get_option( 'enable_scheduled_cleanup' ) ) {
            add_action( 'mbrpe_database_cleanup', array( $this, 'run_scheduled_cleanup' ) );
        }

        // AJAX handlers for cleanup buttons are registered in class-admin.php.
    }

    /**
     * Helper: is this a WooCommerce-related frontend page?
     *
     * @return bool
     */
    private function is_shop_context() {
        if ( ! function_exists( 'is_woocommerce' ) ) {
            return false;
        }

        return (
            is_woocommerce()
            || is_cart()
            || is_checkout()
            || is_account_page()
            || ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url() )
        );
    }

    /**
     * Cart fragments handler (non_shop or always)
     */
    public function handle_cart_fragments() {
        $mode = $this->get_option( 'cart_fragments_mode', 'default' );

        if ( $mode === 'always' ) {
            wp_dequeue_script( 'wc-cart-fragments' );
            return;
        }

        if ( $mode === 'non_shop' && ! $this->is_shop_context() ) {
            wp_dequeue_script( 'wc-cart-fragments' );
        }
    }

    /**
     * Disable WooCommerce scripts on non-shop pages
     */
    public function disable_scripts_non_shop() {
        if ( $this->is_shop_context() ) {
            return;
        }

        wp_dequeue_script( 'wc-cart-fragments' );
        wp_dequeue_script( 'woocommerce' );
        wp_dequeue_script( 'wc-add-to-cart' );
        wp_dequeue_script( 'wc-cart' );
        wp_dequeue_script( 'wc-checkout' );
        wp_dequeue_script( 'js-cookie' );
        wp_dequeue_script( 'jquery-blockui' );
        wp_dequeue_script( 'selectWoo' );
        wp_dequeue_script( 'select2' );
    }

    /**
     * Disable WooCommerce styles on non-shop pages
     */
    public function disable_styles_non_shop() {
        if ( $this->is_shop_context() ) {
            return;
        }

        wp_dequeue_style( 'woocommerce-general' );
        wp_dequeue_style( 'woocommerce-layout' );
        wp_dequeue_style( 'woocommerce-smallscreen' );
        wp_dequeue_style( 'woocommerce_frontend_styles' );
        wp_dequeue_style( 'woocommerce_fancybox_styles' );
        wp_dequeue_style( 'woocommerce_chosen_styles' );
        wp_dequeue_style( 'woocommerce_prettyPhoto_css' );
        wp_dequeue_style( 'select2' );
    }

    /**
     * Disable WC block editor assets on non-shop pages
     */
    public function disable_block_assets_non_shop() {
        if ( $this->is_shop_context() ) {
            return;
        }

        wp_dequeue_style( 'wc-blocks-style' );
        wp_dequeue_style( 'wc-blocks-vendors-style' );
        wp_dequeue_style( 'wc-block-style' );
        wp_dequeue_script( 'wc-blocks-checkout' );
        wp_dequeue_script( 'wc-blocks-registry' );
        wp_dequeue_script( 'wc-price-format' );
    }

    /**
     * Disable the password strength meter on front end
     */
    public function disable_password_strength() {
        if ( wp_script_is( 'wc-password-strength-meter', 'enqueued' ) ) {
            wp_dequeue_script( 'wc-password-strength-meter' );
        }
    }

    /**
     * Remove WooCommerce dashboard widgets and setup boxes
     */
    public function remove_dashboard_widgets() {
        remove_meta_box( 'woocommerce_dashboard_status', 'dashboard', 'normal' );
        remove_meta_box( 'woocommerce_dashboard_recent_reviews', 'dashboard', 'normal' );
        remove_meta_box( 'wc_admin_dashboard_setup', 'dashboard', 'normal' );
        remove_meta_box( 'woocommerce_network_orders', 'dashboard', 'normal' );
    }

    /**
     * Disable WC admin scripts & styles on non-WC admin pages
     */
    public function disable_admin_scripts_non_wc() {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen ) {
            return;
        }

        $wc_screens = function_exists( 'wc_get_screen_ids' ) ? wc_get_screen_ids() : array();

        if ( in_array( $screen->id, $wc_screens, true ) ) {
            return;
        }

        // Also allow product/order edit screens (defensive; these are usually in wc_get_screen_ids already).
        if ( isset( $screen->post_type ) && in_array( $screen->post_type, array( 'product', 'shop_order', 'shop_coupon', 'product_variation' ), true ) ) {
            return;
        }

        // Heavy wc-admin React bundles
        wp_dequeue_script( 'wc-admin-app' );
        wp_dequeue_script( 'wc-admin' );
        wp_dequeue_style( 'wc-admin-app' );
        wp_dequeue_style( 'wc-components' );

        // Legacy admin scripts
        wp_dequeue_script( 'woocommerce_admin' );
        wp_dequeue_script( 'wc-enhanced-select' );
        wp_dequeue_style( 'woocommerce_admin_styles' );
        wp_dequeue_style( 'woocommerce_admin_menu_styles' );
        wp_dequeue_style( 'woocommerce_admin_dashboard_styles' );
    }

    /**
     * Scheduled weekly cleanup callback.
     *
     * Attached to the plugin's existing `mbrpe_database_cleanup`
     * cron hook (scheduled weekly on activation). Only runs when the
     * "Enable weekly automated cleanup" option is on.
     *
     * Runs three low-risk, idempotent cleanups:
     *  - Expired WooCommerce sessions
     *  - Product/order/expired transients (via WC's own helpers)
     *  - Action Scheduler history pruning (respects the retention filter)
     *
     * Writes a small log entry to the `mbrpe_wc_cleanup_log`
     * option with the result of the last run for display in the admin.
     */
    public function run_scheduled_cleanup() {
        // Safety: only proceed if WC is still active when the cron fires.
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        $sessions    = self::clear_expired_sessions();
        $transients  = self::clear_transients();
        $as_deleted  = self::run_action_scheduler_cleanup();

        update_option(
            'mbrpe_wc_cleanup_log',
            array(
                'timestamp'            => time(),
                'sessions_deleted'     => (int) $sessions,
                'transients_cleared'   => (bool) $transients,
                'as_actions_deleted'   => (int) $as_deleted,
            ),
            false
        );
    }

    /**
     * Retrieve the timestamp and result of the last scheduled cleanup run.
     *
     * @return array|null
     */
    public static function get_last_scheduled_cleanup_log() {
        $log = get_option( 'mbrpe_wc_cleanup_log' );
        return is_array( $log ) ? $log : null;
    }

    /**
     * Detect the WooCommerce geolocation setting and return an advisory
     * message if it's likely to interact badly with a page cache.
     *
     * Two problematic values:
     *  - `geolocation`       : Server-side geolocation on every request.
     *                          Breaks full-page caching entirely because
     *                          the response varies per visitor IP.
     *  - `geolocation_ajax`  : "With page cache support" — appends
     *                          `?v=<timestamp>` to URLs, which many cache
     *                          plugins treat as a different URL and either
     *                          bypass cache or create huge cache variants.
     *
     * @return array|null  [ 'severity' => 'warning'|'notice', 'message' => string, 'current' => string ] or null if safe.
     */
    public static function get_geolocation_advisory() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return null;
        }

        $setting = get_option( 'woocommerce_default_customer_address' );

        if ( $setting === 'geolocation' ) {
            return array(
                'severity' => 'warning',
                'current'  => $setting,
                'message'  => __( 'WooCommerce is set to "Geolocate" for the default customer location. This performs server-side IP lookups on every request and is incompatible with full-page caching — every visitor will bypass the cache. If you are using a page cache plugin, switch this to "Shop base address" or "Geolocate (with page cache support)" under WooCommerce → Settings → General.', 'mbr-performance' ),
            );
        }

        if ( $setting === 'geolocation_ajax' ) {
            return array(
                'severity' => 'notice',
                'current'  => $setting,
                'message'  => __( 'WooCommerce is set to "Geolocate (with page cache support)". This appends a `?v=<timestamp>` query string to URLs, which some page cache plugins treat as a distinct URL — either bypassing the cache or creating a large number of cache variants. If caching is not behaving as expected, consider switching to "Shop base address" under WooCommerce → Settings → General, or confirm your cache plugin is configured to ignore the `v` query argument.', 'mbr-performance' ),
            );
        }

        return null;
    }

    // ---------------------------------------------------------------------
    // Cleanup routines (called by AJAX handlers in class-admin.php)
    // ---------------------------------------------------------------------

    /**
     * Delete expired WooCommerce sessions.
     *
     * @return int Rows deleted
     */
    public static function clear_expired_sessions() {
        global $wpdb;

        $table = $wpdb->prefix . 'woocommerce_sessions';

        // Guard against the table not existing on fresh/unusual installs.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
        if ( $exists !== $table ) {
            return 0;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $deleted = $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}woocommerce_sessions WHERE session_expiry < %d",
            time()
        ) );

        return $deleted ? (int) $deleted : 0;
    }

    /**
     * Count expired WooCommerce sessions.
     *
     * @return int
     */
    public static function count_expired_sessions() {
        global $wpdb;

        $table = $wpdb->prefix . 'woocommerce_sessions';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
        if ( $exists !== $table ) {
            return 0;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_sessions WHERE session_expiry < %d",
            time()
        ) );
    }

    /**
     * Clear WooCommerce transients using WC's own helpers.
     *
     * @return bool
     */
    public static function clear_transients() {
        $ran = false;

        if ( function_exists( 'wc_delete_expired_transients' ) ) {
            wc_delete_expired_transients();
            $ran = true;
        }

        if ( function_exists( 'wc_delete_product_transients' ) ) {
            wc_delete_product_transients();
            $ran = true;
        }

        if ( function_exists( 'wc_delete_shop_order_transients' ) ) {
            wc_delete_shop_order_transients();
            $ran = true;
        }

        return $ran;
    }

    /**
     * Trigger Action Scheduler's own cleanup routine.
     *
     * @return int Actions deleted (best-effort estimate)
     */
    public static function run_action_scheduler_cleanup() {
        if ( ! class_exists( 'ActionScheduler' ) ) {
            return 0;
        }

        global $wpdb;

        $table = $wpdb->prefix . 'actionscheduler_actions';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
        if ( $exists !== $table ) {
            return 0;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $before = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}actionscheduler_actions WHERE status IN ('complete','failed','canceled')"
        );

        // Trigger the scheduled cleanup hooks AS registers on itself.
        do_action( 'action_scheduler/cleanup' );
        do_action( 'action_scheduler_cleanup_actions' );
        do_action( 'action_scheduler_cleanup_logs' );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $after = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}actionscheduler_actions WHERE status IN ('complete','failed','canceled')"
        );

        $diff = $before - $after;
        return $diff > 0 ? $diff : 0;
    }

    /**
     * Get Action Scheduler table stats.
     *
     * @return array
     */
    public static function get_action_scheduler_stats() {
        global $wpdb;

        $stats = array(
            'total'    => 0,
            'complete' => 0,
            'failed'   => 0,
            'pending'  => 0,
        );

        $table = $wpdb->prefix . 'actionscheduler_actions';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
        if ( $exists !== $table ) {
            return $stats;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $rows = $wpdb->get_results(
            "SELECT status, COUNT(*) AS c FROM {$wpdb->prefix}actionscheduler_actions GROUP BY status"
        );

        foreach ( (array) $rows as $row ) {
            $stats['total'] += (int) $row->c;
            if ( isset( $stats[ $row->status ] ) ) {
                $stats[ $row->status ] = (int) $row->c;
            }
        }

        return $stats;
    }
}
