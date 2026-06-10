<?php
/**
 * Admin functionality
 *
 * @package MBRPE
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin class
 */
class MBRPE_Admin {

    /**
     * Single instance
     *
     * @var MBRPE_Admin
     */
    private static $instance = null;

    /**
     * Current tab
     *
     * @var string
     */
    private $current_tab = 'core';

    /**
     * Get instance
     *
     * @return MBRPE_Admin
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
        add_action( 'admin_bar_menu', array( $this, 'add_toolbar_menu' ), 100 );
        add_action( 'admin_menu', array( $this, 'add_hidden_admin_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        
        // AJAX handlers
        add_action( 'wp_ajax_mbrpe_save_settings', array( $this, 'ajax_save_settings' ) );
        add_action( 'wp_ajax_mbrpe_reset_settings', array( $this, 'ajax_reset_settings' ) );
        add_action( 'wp_ajax_mbrpe_clean_revisions', array( $this, 'ajax_clean_revisions' ) );
        add_action( 'wp_ajax_mbrpe_scan_post_meta', array( $this, 'ajax_scan_post_meta' ) );
        add_action( 'wp_ajax_mbrpe_delete_post_meta', array( $this, 'ajax_delete_post_meta' ) );
        add_action( 'wp_ajax_mbrpe_scan_comment_meta', array( $this, 'ajax_scan_comment_meta' ) );
        add_action( 'wp_ajax_mbrpe_delete_comment_meta', array( $this, 'ajax_delete_comment_meta' ) );
        add_action( 'wp_ajax_mbrpe_scan_relationships', array( $this, 'ajax_scan_relationships' ) );
        add_action( 'wp_ajax_mbrpe_delete_relationships', array( $this, 'ajax_delete_relationships' ) );
        add_action( 'wp_ajax_mbrpe_scan_term_meta', array( $this, 'ajax_scan_term_meta' ) );
        add_action( 'wp_ajax_mbrpe_delete_term_meta', array( $this, 'ajax_delete_term_meta' ) );
        add_action( 'wp_ajax_mbrpe_transient_stats', array( $this, 'ajax_transient_stats' ) );
        add_action( 'wp_ajax_mbrpe_delete_expired_transients', array( $this, 'ajax_delete_expired_transients' ) );
        add_action( 'wp_ajax_mbrpe_delete_all_transients', array( $this, 'ajax_delete_all_transients' ) );
        add_action( 'wp_ajax_mbrpe_optimize_tables', array( $this, 'ajax_optimize_tables' ) );
        add_action( 'wp_ajax_mbrpe_convert_innodb', array( $this, 'ajax_convert_innodb' ) );
        add_action( 'wp_ajax_mbrpe_repair_tables', array( $this, 'ajax_repair_tables' ) );
        add_action( 'wp_ajax_mbrpe_db_info', array( $this, 'ajax_db_info' ) );
        add_action( 'wp_ajax_mbrpe_scan_css', array( $this, 'ajax_scan_css' ) );
        add_action( 'wp_ajax_mbrpe_clear_scan_data', array( $this, 'ajax_clear_scan_data' ) );
        add_action( 'wp_ajax_mbrpe_download_fonts', array( $this, 'ajax_download_fonts' ) );
        add_action( 'wp_ajax_mbrpe_download_manual_fonts', array( $this, 'ajax_download_manual_fonts' ) );
        add_action( 'wp_ajax_mbrpe_clear_font_cache', array( $this, 'ajax_clear_font_cache' ) );
        
        // WebP Converter AJAX handlers
        add_action( 'wp_ajax_mbrpe_webp_get_images', array( $this, 'ajax_webp_get_images' ) );
        add_action( 'wp_ajax_mbrpe_webp_process_image', array( $this, 'ajax_webp_process_image' ) );
        add_action( 'wp_ajax_mbrpe_webp_clear_history', array( $this, 'ajax_webp_clear_history' ) );
        add_action( 'wp_ajax_mbrpe_webp_bulk_delete', array( $this, 'ajax_webp_bulk_delete' ) );
        add_action( 'wp_ajax_mbrpe_webp_revert_all', array( $this, 'ajax_webp_revert_all' ) );
        add_action( 'wp_ajax_mbrpe_avif_get_images', array( $this, 'ajax_avif_get_images' ) );
        add_action( 'wp_ajax_mbrpe_avif_process_image', array( $this, 'ajax_avif_process_image' ) );
        add_action( 'wp_ajax_mbrpe_avif_clear_history', array( $this, 'ajax_avif_clear_history' ) );
        add_action( 'wp_ajax_mbrpe_avif_revert_all', array( $this, 'ajax_avif_revert_all' ) );

        // Image Dimensions — bulk resize AJAX handlers
        add_action( 'wp_ajax_mbrpe_image_dimensions_scan', array( $this, 'ajax_image_dimensions_scan' ) );
        add_action( 'wp_ajax_mbrpe_image_dimensions_resize', array( $this, 'ajax_image_dimensions_resize' ) );

        // Orphaned Images AJAX handlers
        add_action( 'wp_ajax_mbrpe_orphan_scan_init', array( $this, 'ajax_orphan_scan_init' ) );
        add_action( 'wp_ajax_mbrpe_orphan_scan_batch', array( $this, 'ajax_orphan_scan_batch' ) );
        add_action( 'wp_ajax_mbrpe_orphan_get_candidates', array( $this, 'ajax_orphan_get_candidates' ) );
        add_action( 'wp_ajax_mbrpe_orphan_delete', array( $this, 'ajax_orphan_delete' ) );
        add_action( 'wp_ajax_mbrpe_orphan_get_staged', array( $this, 'ajax_orphan_get_staged' ) );
        add_action( 'wp_ajax_mbrpe_orphan_restore', array( $this, 'ajax_orphan_restore' ) );
        add_action( 'wp_ajax_mbrpe_orphan_exclude', array( $this, 'ajax_orphan_exclude' ) );
        
        // Multisite: import site settings into network defaults
        add_action( 'wp_ajax_mbrpe_import_site_settings', array( $this, 'ajax_import_site_settings' ) );

        // v1.12.0 AJAX handlers.
        add_action( 'wp_ajax_mbrpe_autoload_top',       array( $this, 'ajax_autoload_top' ) );
        add_action( 'wp_ajax_mbrpe_autoload_toggle',    array( $this, 'ajax_autoload_toggle' ) );
        add_action( 'wp_ajax_mbrpe_cron_unschedule',    array( $this, 'ajax_cron_unschedule' ) );
        add_action( 'wp_ajax_mbrpe_cron_clear_hook',    array( $this, 'ajax_cron_clear_hook' ) );
        add_action( 'wp_ajax_mbrpe_db_cleanup_run',     array( $this, 'ajax_db_cleanup_run' ) );

        // WooCommerce cleanup AJAX handlers
        add_action( 'wp_ajax_mbrpe_wc_clear_sessions', array( $this, 'ajax_wc_clear_sessions' ) );
        add_action( 'wp_ajax_mbrpe_wc_clear_transients', array( $this, 'ajax_wc_clear_transients' ) );
        add_action( 'wp_ajax_mbrpe_wc_cleanup_as', array( $this, 'ajax_wc_cleanup_action_scheduler' ) );

        // WooCommerce migration notice (shown once after upgrade if legacy WC options are in use)
        add_action( 'admin_init', array( $this, 'maybe_flag_wc_migration_notice' ) );
        add_action( 'admin_notices', array( $this, 'maybe_render_wc_migration_notice' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_wc_migration_assets' ) );
        add_action( 'wp_ajax_mbrpe_dismiss_wc_migration_notice', array( $this, 'ajax_dismiss_wc_migration_notice' ) );
    }

    /**
     * Add toolbar menu
     */
    public function add_toolbar_menu( $wp_admin_bar ) {
        $wp_admin_bar->add_node( array(
            'id'    => 'mbr-performance',
            'title' => '<span class="ab-icon dashicons-performance"></span><span class="ab-label">' . __( 'MBR Performance', 'mbr-performance' ) . '</span>',
            'href'  => admin_url( 'admin.php?page=mbr-performance' ),
            'meta'  => array(
                'title' => __( 'MBR Performance Settings', 'mbr-performance' ),
            ),
        ) );
        
        // Add submenu items for each tab
        $tabs = array(
            'core' => __( 'Core Features', 'mbr-performance' ),
            'javascript' => __( 'JavaScript', 'mbr-performance' ),
            'css' => __( 'CSS', 'mbr-performance' ),
            'fonts' => __( 'Fonts', 'mbr-performance' ),
            'preloading' => __( 'Preloading', 'mbr-performance' ),
            'lazy-loading' => __( 'Lazy Loading', 'mbr-performance' ),
            'database' => __( 'Database', 'mbr-performance' ),
            'webp' => __( 'WebP', 'mbr-performance' ),
            'orphaned-media' => __( 'Orphaned Media', 'mbr-performance' ),
            'woocommerce' => __( 'WooCommerce', 'mbr-performance' ),
        );
        
        foreach ( $tabs as $tab => $label ) {
            $wp_admin_bar->add_node( array(
                'parent' => 'mbr-performance',
                'id'     => 'mbr-performance-' . $tab,
                'title'  => $label,
                'href'   => admin_url( 'admin.php?page=mbr-performance&tab=' . $tab ),
            ) );
        }
    }
    
    /**
     * Add hidden admin page (not in sidebar, only accessible via toolbar)
     */
    public function add_hidden_admin_page() {
        add_submenu_page(
            null, // No parent = hidden from sidebar menu
            __( 'MBR Performance', 'mbr-performance' ),
            __( 'MBR Performance', 'mbr-performance' ),
            'manage_options',
            'mbr-performance',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'mbrpe_options',
            'mbrpe_options',
            array(
                'type' => 'array',
                'sanitize_callback' => array( $this, 'sanitize_options' ),
            )
        );
    }

    /**
     * Sanitize options
     *
     * @param array $options
     * @return array
     */
    public function sanitize_options( $options ) {
        // Get existing options to merge with
        $existing = get_option( 'mbrpe_options', array() );
        
        // Start with existing options
        $sanitized = is_array( $existing ) ? $existing : array();
        
        // Sanitize and merge core options
        if ( isset( $options['core'] ) && is_array( $options['core'] ) ) {
            $sanitized['core'] = $this->sanitize_core_options( $options['core'] );
        }
        
        // Sanitize and merge JavaScript options
        if ( isset( $options['javascript'] ) && is_array( $options['javascript'] ) ) {
            $sanitized['javascript'] = $this->sanitize_javascript_options( $options['javascript'] );
        }
        
        // Sanitize and merge CSS options
        if ( isset( $options['css'] ) && is_array( $options['css'] ) ) {
            $sanitized['css'] = $this->sanitize_css_options( $options['css'] );
        }
        
        // Sanitize and merge font options
        if ( isset( $options['fonts'] ) && is_array( $options['fonts'] ) ) {
            $sanitized['fonts'] = $this->sanitize_font_options( $options['fonts'] );
        }
        
        // Sanitize and merge preloading options
        if ( isset( $options['preloading'] ) && is_array( $options['preloading'] ) ) {
            $sanitized['preloading'] = $this->sanitize_preloading_options( $options['preloading'] );
        }
        
        // Sanitize and merge lazy loading options
        if ( isset( $options['lazy_loading'] ) && is_array( $options['lazy_loading'] ) ) {
            $sanitized['lazy_loading'] = $this->sanitize_lazy_loading_options( $options['lazy_loading'] );
        }
        
        // Sanitize and merge database options
        if ( isset( $options['database'] ) && is_array( $options['database'] ) ) {
            $sanitized['database'] = $this->sanitize_database_options( $options['database'] );
        }
        
        // Sanitize and merge WebP options
        if ( isset( $options['webp'] ) && is_array( $options['webp'] ) ) {
            $sanitized['webp'] = $this->sanitize_webp_options( $options['webp'] );
        }

        // Sanitize and merge WooCommerce options
        if ( isset( $options['woocommerce'] ) && is_array( $options['woocommerce'] ) ) {
            $sanitized['woocommerce'] = $this->sanitize_woocommerce_options( $options['woocommerce'] );
        }

        // Sanitize and merge image dimensions options
        if ( isset( $options['image_dimensions'] ) && is_array( $options['image_dimensions'] ) ) {
            $sanitized['image_dimensions'] = $this->sanitize_image_dimensions_options( $options['image_dimensions'] );
        }

        // Sanitize and merge orphaned images options
        if ( isset( $options['orphaned_images'] ) && is_array( $options['orphaned_images'] ) ) {
            $sanitized['orphaned_images'] = $this->sanitize_orphaned_images_options( $options['orphaned_images'] );
        }

        // v1.12.0 — server headers.
        if ( isset( $options['server_headers'] ) && is_array( $options['server_headers'] ) ) {
            $sanitized['server_headers'] = $this->sanitize_server_headers_options( $options['server_headers'] );
        }

        return $sanitized;
    }

    /**
     * Sanitize core options
     *
     * @param array $options
     * @return array
     */
    private function sanitize_core_options( $options ) {
        $sanitized = array();
        
        // Boolean options
        $boolean_fields = array(
            'disable_emojis',
            'disable_dashicons',
            'disable_embeds',
            'disable_xmlrpc',
            'remove_jquery_migrate',
            'hide_wp_version',
            'remove_rsd_link',
            'remove_shortlink',
            'disable_rss_feeds',
            'remove_rss_feed_links',
            'disable_self_pingbacks',
            'remove_rest_api_links',
            'disable_google_maps',
            'disable_password_strength',
            'disable_comments',
            'remove_comment_urls',
            'remove_global_styles',
            'separate_block_styles',
            'lazy_load_images',
            'remove_query_strings',
            'minify_html',
            'disable_woocommerce_scripts',
            'disable_ai_support',
        );
        
        foreach ( $boolean_fields as $field ) {
            $sanitized[ $field ] = isset( $options[ $field ] ) ? (bool) $options[ $field ] : false;
        }
        
        // Radio/select options
        if ( isset( $options['rest_api_mode'] ) ) {
            $sanitized['rest_api_mode'] = sanitize_text_field( $options['rest_api_mode'] );
        }
        
        if ( isset( $options['heartbeat_mode'] ) ) {
            $sanitized['heartbeat_mode'] = sanitize_text_field( $options['heartbeat_mode'] );
        }
        
        if ( isset( $options['heartbeat_frequency'] ) ) {
            $sanitized['heartbeat_frequency'] = absint( $options['heartbeat_frequency'] );
        }
        
        if ( isset( $options['post_revisions'] ) ) {
            $sanitized['post_revisions'] = sanitize_text_field( $options['post_revisions'] );
        }
        
        if ( isset( $options['autosave_interval'] ) ) {
            $sanitized['autosave_interval'] = absint( $options['autosave_interval'] );
        }
        
        if ( isset( $options['lazy_load_mode'] ) ) {
            $sanitized['lazy_load_mode'] = sanitize_text_field( $options['lazy_load_mode'] );
        }
        
        // Textarea options
        if ( isset( $options['preload_resources'] ) ) {
            $sanitized['preload_resources'] = sanitize_textarea_field( $options['preload_resources'] );
        }

        if ( isset( $options['rest_api_allowed_namespaces'] ) ) {
            $sanitized['rest_api_allowed_namespaces'] = sanitize_textarea_field( $options['rest_api_allowed_namespaces'] );
        }

        return $sanitized;
    }

    /**
     * Sanitize JavaScript options
     *
     * @param array $options
     * @return array
     */
    private function sanitize_javascript_options( $options ) {
        $sanitized = array();
        
        // Boolean options
        $boolean_fields = array(
            'defer_javascript',
            'move_scripts_footer',
            'defer_jquery',
            'remove_jquery',
            'jquery_test_mode',
            'minify_javascript',
            'combine_javascript',
            'delay_javascript',
            'remove_script_versions',
        );
        
        foreach ( $boolean_fields as $field ) {
            $sanitized[ $field ] = isset( $options[ $field ] ) ? (bool) $options[ $field ] : false;
        }
        
        // Textarea options
        $textarea_fields = array(
            'exclude_defer',
            'exclude_footer',
            'exclude_optimization',
            'delay_scripts',
        );
        
        foreach ( $textarea_fields as $field ) {
            if ( isset( $options[ $field ] ) ) {
                $sanitized[ $field ] = sanitize_textarea_field( $options[ $field ] );
            }
        }
        
        // Select options
        if ( isset( $options['delay_timeout'] ) ) {
            $sanitized['delay_timeout'] = absint( $options['delay_timeout'] );
        }
        
        return $sanitized;
    }

    /**
     * Sanitize CSS options
     *
     * @param array $options
     * @return array
     */
    private function sanitize_css_options( $options ) {
        $sanitized = array();
        
        // Boolean options
        $boolean_fields = array(
            'async_css',
            'minify_css',
            'combine_css',
            'remove_unused_css',
            'remove_global_styles',
            'load_block_styles_conditionally',
            'remove_css_versions',
            'disable_woocommerce_css',
        );
        
        foreach ( $boolean_fields as $field ) {
            $sanitized[ $field ] = isset( $options[ $field ] ) ? (bool) $options[ $field ] : false;
        }
        
        // Textarea options
        $textarea_fields = array(
            'exclude_async',
            'exclude_optimization',
        );
        
        foreach ( $textarea_fields as $field ) {
            if ( isset( $options[ $field ] ) ) {
                $sanitized[ $field ] = sanitize_textarea_field( $options[ $field ] );
            }
        }
        
        return $sanitized;
    }

    /**
     * Sanitize font options
     *
     * @param array $options
     * @return array
     */
    private function sanitize_font_options( $options ) {
        $sanitized = array();
        
        // Boolean options
        $boolean_fields = array(
            'preload_fonts',
            'self_host_google_fonts',
            'preload_local_fonts',
            'disable_google_fonts',
            'enable_font_subsetting',
            'preconnect_domains',
            'dns_prefetch',
            'disable_font_awesome',
            'async_font_awesome',
            'disable_local_fallback',
            'remove_font_display',
            'disable_elementor_fonts',
        );
        
        foreach ( $boolean_fields as $field ) {
            $sanitized[ $field ] = isset( $options[ $field ] ) ? (bool) $options[ $field ] : false;
        }
        
        // Textarea options
        $textarea_fields = array(
            'preload_font_urls',
            'font_domains',
            'fallback_fonts',
            'manual_fonts',
        );
        
        foreach ( $textarea_fields as $field ) {
            if ( isset( $options[ $field ] ) ) {
                $sanitized[ $field ] = sanitize_textarea_field( $options[ $field ] );
            }
        }
        
        // Select/checkbox array options
        if ( isset( $options['font_display'] ) ) {
            $sanitized['font_display'] = sanitize_text_field( $options['font_display'] );
        }
        
        if ( isset( $options['character_sets'] ) && is_array( $options['character_sets'] ) ) {
            $sanitized['character_sets'] = array_map( 'sanitize_text_field', $options['character_sets'] );
        }
        
        return $sanitized;
    }

    /**
     * Sanitize preloading options
     *
     * @param array $options
     * @return array
     */
    private function sanitize_preloading_options( $options ) {
        $sanitized = array();
        
        // Number option
        if ( isset( $options['preload_critical_images_count'] ) ) {
            $sanitized['preload_critical_images_count'] = absint( $options['preload_critical_images_count'] );
        }
        
        // Array of URLs
        if ( isset( $options['preload_images'] ) && is_array( $options['preload_images'] ) ) {
            $sanitized['preload_images'] = array_filter( array_map( 'esc_url_raw', $options['preload_images'] ) );
        }
        
        // Boolean options
        $boolean_fields = array(
            'cloudflare_early_hints',
            'fetch_priority',
            'disable_core_fetch_priority',
            'hover_prefetch',
        );
        
        foreach ( $boolean_fields as $field ) {
            $sanitized[ $field ] = isset( $options[ $field ] ) ? (bool) $options[ $field ] : false;
        }
        
        // Fetch priority selectors array
        if ( isset( $options['fetch_priority_selectors'] ) && is_array( $options['fetch_priority_selectors'] ) ) {
            $sanitized['fetch_priority_selectors'] = array_filter( array_map( 'sanitize_text_field', $options['fetch_priority_selectors'] ) );
        }
        
        // Select options
        if ( isset( $options['speculative_mode'] ) ) {
            $sanitized['speculative_mode'] = sanitize_text_field( $options['speculative_mode'] );
        }
        
        if ( isset( $options['speculative_eagerness'] ) ) {
            $sanitized['speculative_eagerness'] = sanitize_text_field( $options['speculative_eagerness'] );
        }
        
        // Preconnect domains array
        if ( isset( $options['preconnect_domains'] ) && is_array( $options['preconnect_domains'] ) ) {
            $sanitized['preconnect_domains'] = array_filter( array_map( 'esc_url_raw', $options['preconnect_domains'] ) );
        }
        
        // DNS prefetch textarea
        if ( isset( $options['dns_prefetch_domains'] ) ) {
            $sanitized['dns_prefetch_domains'] = sanitize_textarea_field( $options['dns_prefetch_domains'] );
        }
        
        return $sanitized;
    }

    /**
     * Sanitize lazy loading options
     *
     * @param array $options
     * @return array
     */
    private function sanitize_lazy_loading_options( $options ) {
        $sanitized = array();
        
        // Boolean options
        $boolean_fields = array(
            'lazy_load_images',
            'lazy_load_iframes',
            'dom_monitoring',
            'add_missing_dimensions',
            'lazy_fade_in',
            'lazy_background_images',
            'video_facade',
        );
        
        foreach ( $boolean_fields as $field ) {
            $sanitized[ $field ] = isset( $options[ $field ] ) ? (bool) $options[ $field ] : false;
        }
        
        // Textarea options
        $textarea_fields = array(
            'exclude_lazy_load',
            'exclude_parent_selector',
            'lazy_elements',
        );
        
        foreach ( $textarea_fields as $field ) {
            if ( isset( $options[ $field ] ) ) {
                $sanitized[ $field ] = sanitize_textarea_field( $options[ $field ] );
            }
        }
        
        // Number options
        if ( isset( $options['lazy_threshold'] ) ) {
            $sanitized['lazy_threshold'] = absint( $options['lazy_threshold'] );
        }
        
        if ( isset( $options['lazy_fade_duration'] ) ) {
            $sanitized['lazy_fade_duration'] = absint( $options['lazy_fade_duration'] );
        }
        
        return $sanitized;
    }

    /**
     * Sanitize database options
     *
     * @param array $options
     * @return array
     */
    private function sanitize_database_options( $options ) {
        $sanitized = array();
        
        // Boolean options
        $boolean_fields = array(
            'auto_delete_drafts',
            'auto_empty_trash',
            'delete_spam_comments',
            'delete_unapproved_comments',
            'auto_delete_transients',
            'schedule_optimization',
            'convert_to_innodb',
        );
        
        foreach ( $boolean_fields as $field ) {
            $sanitized[ $field ] = isset( $options[ $field ] ) ? (bool) $options[ $field ] : false;
        }
        
        // Number options
        $number_fields = array(
            'keep_revisions' => 5,
            'draft_age' => 7,
            'trash_age' => 30,
            'spam_age' => 14,
            'unapproved_age' => 30,
        );
        
        foreach ( $number_fields as $field => $default ) {
            $sanitized[ $field ] = isset( $options[ $field ] ) ? absint( $options[ $field ] ) : $default;
        }
        
        // Select options
        if ( isset( $options['cleanup_schedule'] ) ) {
            $sanitized['cleanup_schedule'] = sanitize_text_field( $options['cleanup_schedule'] );
        }
        
        if ( isset( $options['optimization_day'] ) ) {
            $sanitized['optimization_day'] = sanitize_text_field( $options['optimization_day'] );
        }
        
        if ( isset( $options['optimization_time'] ) ) {
            $sanitized['optimization_time'] = sanitize_text_field( $options['optimization_time'] );
        }
        
        return $sanitized;
    }

    /**
     * Sanitize WebP options
     *
     * @param array $options
     * @return array
     */
    private function sanitize_webp_options( $options ) {
        $sanitized = array();

        // Boolean options.
        $boolean_fields = array(
            'auto_convert',
            'picture_tags',
            'htaccess_rules',
            'avif_enabled',
        );

        foreach ( $boolean_fields as $field ) {
            $sanitized[ $field ] = isset( $options[ $field ] ) ? (bool) $options[ $field ] : false;
        }

        // Compression level (1–100).
        $sanitized['compression_level'] = isset( $options['compression_level'] )
            ? max( 1, min( 100, absint( $options['compression_level'] ) ) )
            : 75;

        // AVIF quality (1-100, default 60 — lower than WebP, AVIF tolerates it).
        $sanitized['avif_quality'] = isset( $options['avif_quality'] )
            ? max( 1, min( 100, absint( $options['avif_quality'] ) ) )
            : 60;

        // Write or remove .htaccess rules based on the setting.
        if ( $sanitized['htaccess_rules'] ) {
            MBRPE_WebP_Converter::add_htaccess_rules();
        } else {
            MBRPE_WebP_Converter::remove_htaccess_rules();
        }

        // AVIF .htaccess rules — only when both AVIF and the WebP htaccess
        // toggle are on, since they share the same delivery pattern.
        if ( class_exists( 'MBRPE_AVIF_Converter' ) ) {
            if ( $sanitized['avif_enabled'] && $sanitized['htaccess_rules'] ) {
                MBRPE_AVIF_Converter::add_htaccess_rules();
            } else {
                MBRPE_AVIF_Converter::remove_htaccess_rules();
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize image dimensions options
     *
     * @param array $options
     * @return array
     */
    private function sanitize_image_dimensions_options( $options ) {
        $sanitized = array();

        // Boolean options.
        $boolean_fields = array(
            'resize_on_upload',
            'add_missing_dimensions',
            'decoding_async',
            'strip_exif',
        );

        foreach ( $boolean_fields as $field ) {
            $sanitized[ $field ] = isset( $options[ $field ] ) ? (bool) $options[ $field ] : false;
        }

        // Max dimension (clamped to the class constants).
        $max_default = class_exists( 'MBRPE_Image_Dimensions' ) ? MBRPE_Image_Dimensions::DEFAULT_MAX_DIMENSION : 2560;
        $min_allowed = class_exists( 'MBRPE_Image_Dimensions' ) ? MBRPE_Image_Dimensions::MIN_MAX_DIMENSION : 100;
        $max_allowed = class_exists( 'MBRPE_Image_Dimensions' ) ? MBRPE_Image_Dimensions::MAX_MAX_DIMENSION : 10000;

        $raw_max = isset( $options['max_dimension'] ) ? absint( $options['max_dimension'] ) : $max_default;
        if ( $raw_max < 1 ) {
            $raw_max = $max_default;
        }
        $sanitized['max_dimension'] = max( $min_allowed, min( $max_allowed, $raw_max ) );

        // Flush cached dimensions when the user re-saves settings — handy if
        // they've replaced files and need the filter to re-measure.
        if ( ! empty( $sanitized['add_missing_dimensions'] ) ) {
            $this->flush_image_dimension_transients();
        }

        return $sanitized;
    }

    /**
     * Flush any cached image-dimension transients created by the
     * Image Dimensions module. Safe to call — no-op if none exist.
     */
    private function flush_image_dimension_transients() {
        global $wpdb;

        // Best-effort cleanup of mbr_imgdim_* transients.
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_mbr_imgdim_%'
                OR option_name LIKE '_transient_timeout_mbr_imgdim_%'"
        );
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook
     */
    public function enqueue_admin_assets( $hook ) {
        // Check if we're on the MBR Performance settings page
        // Changed from 'toplevel_page_mbr-performance' to 'admin_page_mbr-performance'
        // because we moved from a top-level menu to a hidden submenu page
        if ( strpos( $hook, 'mbr-performance' ) === false ) {
            return;
        }
        
        // Enqueue styles
        wp_enqueue_style(
            'mbr-performance-admin',
            MBRPE_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            MBRPE_VERSION
        );
        
        // Inject dark background inline — bypasses any specificity issues
        wp_add_inline_style(
            'mbr-performance-admin',
            'html, body.wp-admin, #wpwrap, #wpcontent, #wpbody, #wpbody-content, .wrap { background-color: #1a1d23 !important; } #wpfooter { background-color: #1a1d23 !important; color: #6c7080; } #wpfooter a { color: #6c7080; }'
        );
        
        // Enqueue scripts - Using clean rebuilt version
        wp_enqueue_script(
            'mbr-performance-admin',
            MBRPE_PLUGIN_URL . 'assets/js/admin-clean.js',
            array( 'jquery' ),
            MBRPE_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script(
            'mbr-performance-admin',
            'mbrpeData',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'mbrpe_nonce' ),
                'uploadUrl' => wp_upload_dir()['baseurl'],
                'i18n' => array(
                    'saveSuccess' => __( 'Settings saved successfully.', 'mbr-performance' ),
                    'saveError' => __( 'Error saving settings. Please try again.', 'mbr-performance' ),
                    'confirmReset' => __( 'Are you sure you want to reset all settings to defaults?', 'mbr-performance' ),
                ),
            )
        );

        // Orphaned Media tab — load its dedicated JS only on that tab.
        // Both the new slug (v1.11.0+) and the legacy v1.10.0 slug are
        // accepted so any bookmarked URLs continue to work.
        $current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'core';
        if ( in_array( $current_tab, array( 'orphaned-media', 'orphaned-images' ), true ) ) {
            wp_enqueue_script(
                'mbr-performance-orphaned-media',
                MBRPE_PLUGIN_URL . 'assets/js/orphaned-media.js',
                array( 'jquery', 'mbr-performance-admin' ),
                MBRPE_VERSION,
                true
            );
            wp_localize_script(
                'mbr-performance-orphaned-media',
                'mbrpeOrphanedImages',
                array(
                    'i18n' => array(
                        'scanning'         => __( 'Scanning…', 'mbr-performance' ),
                        'scanComplete'     => __( 'Scan complete.', 'mbr-performance' ),
                        'scanFailed'       => __( 'Scan failed.', 'mbr-performance' ),
                        'noResults'        => __( 'No orphaned media found.', 'mbr-performance' ),
                        'confirmDelete'    => __( 'Delete this attachment? It will be moved to staging and can be restored within the configured window.', 'mbr-performance' ),
                        'confirmBulkDel'   => __( 'Delete the selected attachments? Files will be removed and only the database records can be restored.', 'mbr-performance' ),
                        'confirmRestore'   => __( 'Restore this attachment record? The file itself was deleted and will need to be re-uploaded.', 'mbr-performance' ),
                        'deletingItem'     => __( 'Deleting…', 'mbr-performance' ),
                        'restoringItem'    => __( 'Restoring…', 'mbr-performance' ),
                        'genericError'     => __( 'An unexpected error occurred.', 'mbr-performance' ),
                        'noSelection'      => __( 'Select at least one attachment first.', 'mbr-performance' ),
                        'reviewBlocked'    => __( 'Bulk-delete is restricted to high-confidence orphans. Delete review items individually.', 'mbr-performance' ),
                    ),
                )
            );
        }
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Get current tab
        $this->current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'core';
        
        // Get options
        $options = mbrpe()->get_options();
        
        ?>
        <div class="wrap mbr-performance-wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <?php settings_errors( 'mbrpe_options' ); ?>
            
            <?php
            // Multisite: show notice when per-site overrides are disabled
            if ( is_multisite() && class_exists( 'MBRPE_Multisite' ) ) {
                if ( ! MBRPE_Multisite::allow_site_overrides() && ! is_super_admin() ) {
                    echo '<div class="notice notice-warning"><p>';
                    esc_html_e( 'Per-site overrides are disabled by the network administrator. Settings shown below are read-only and managed at the network level.', 'mbr-performance' );
                    echo '</p></div>';
                } elseif ( MBRPE_Multisite::site_uses_network_defaults() ) {
                    echo '<div class="notice notice-info"><p>';
                    esc_html_e( 'This site is currently using network default settings. Saving changes will switch this site to its own custom settings.', 'mbr-performance' );
                    echo '</p></div>';
                }
            }
            ?>
            
            <?php $this->render_tabs(); ?>
            
            <form method="post" action="options.php" class="mbr-performance-form">
                <?php
                settings_fields( 'mbrpe_options' );
                
                switch ( $this->current_tab ) {
                    case 'core':
                        $this->render_core_tab( $options );
                        break;
                    case 'javascript':
                        $this->render_javascript_tab( $options );
                        break;
                    case 'css':
                        $this->render_css_tab( $options );
                        break;
                    case 'fonts':
                        $this->render_fonts_tab( $options );
                        break;
                    case 'preloading':
                        $this->render_preloading_tab( $options );
                        break;
                    case 'lazy-loading':
                        $this->render_lazy_loading_tab( $options );
                        break;
                    case 'database':
                        $this->render_database_tab( $options );
                        break;
                    case 'webp':
                        $this->render_webp_tab( $options );
                        break;
                    case 'server-headers':
                        $this->render_server_headers_tab( $options );
                        break;
                    case 'diagnostics':
                        $this->render_diagnostics_tab( $options );
                        break;
                    case 'orphaned-media':
                    case 'orphaned-images':
                        // Old slug aliased to the new tab for one release so
                        // any bookmarked v1.10.0 URLs keep working. Slated for
                        // removal in v1.12.0.
                        $this->render_orphaned_media_tab( $options );
                        break;
                    case 'woocommerce':
                        $this->render_woocommerce_tab( $options );
                        break;
                }
                ?>
                
                <div class="mbr-performance-actions">
                    <?php
                    $readonly = is_multisite()
                        && class_exists( 'MBRPE_Multisite' )
                        && ! MBRPE_Multisite::allow_site_overrides()
                        && ! is_super_admin();

                    submit_button(
                        __( 'Save Changes', 'mbr-performance' ),
                        'primary',
                        'submit',
                        false,
                        $readonly ? array( 'disabled' => 'disabled' ) : array()
                    );
                    ?>
                    <button type="button" class="button button-secondary mbr-performance-reset" <?php echo $readonly ? 'disabled' : ''; ?>>
                        <?php esc_html_e( 'Reset to Defaults', 'mbr-performance' ); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Render tabs
     */
    private function render_tabs() {
        $tabs = array(
            'core' => __( 'Core Features', 'mbr-performance' ),
            'javascript' => __( 'JavaScript', 'mbr-performance' ),
            'css' => __( 'CSS', 'mbr-performance' ),
            'fonts' => __( 'Fonts', 'mbr-performance' ),
            'preloading' => __( 'Preloading', 'mbr-performance' ),
            'lazy-loading' => __( 'Lazy Loading', 'mbr-performance' ),
            'database' => __( 'Database', 'mbr-performance' ),
            'webp' => __( 'WebP / AVIF', 'mbr-performance' ),
            'server-headers' => __( 'Server', 'mbr-performance' ),
            'diagnostics' => __( 'Diagnostics', 'mbr-performance' ),
            'orphaned-media' => __( 'Orphaned Media', 'mbr-performance' ),
            'woocommerce' => __( 'WooCommerce', 'mbr-performance' ),
        );
        
        echo '<h2 class="nav-tab-wrapper">';
        foreach ( $tabs as $tab => $label ) {
            $active = $this->current_tab === $tab ? ' nav-tab-active' : '';
            printf(
                '<a href="?page=mbr-performance&tab=%s" class="nav-tab%s">%s</a>',
                esc_attr( $tab ),
                esc_attr( $active ),
                esc_html( $label )
            );
        }
        echo '</h2>';
    }

    /**
     * Render core tab
     *
     * @param array $options
     */
    private function render_core_tab( $options ) {
        require_once MBRPE_PLUGIN_DIR . 'includes/admin/tabs/core.php';
    }

    /**
     * Render JavaScript tab
     *
     * @param array $options
     */
    private function render_javascript_tab( $options ) {
        require_once MBRPE_PLUGIN_DIR . 'includes/admin/tabs/javascript.php';
    }

    /**
     * Render CSS tab
     *
     * @param array $options
     */
    private function render_css_tab( $options ) {
        require_once MBRPE_PLUGIN_DIR . 'includes/admin/tabs/css.php';
    }

    /**
     * Render fonts tab
     *
     * @param array $options
     */
    private function render_fonts_tab( $options ) {
        require_once MBRPE_PLUGIN_DIR . 'includes/admin/tabs/fonts.php';
    }

    /**
     * Render preloading tab
     *
     * @param array $options
     */
    private function render_preloading_tab( $options ) {
        require_once MBRPE_PLUGIN_DIR . 'includes/admin/tabs/preloading.php';
    }

    /**
     * Render lazy loading tab
     *
     * @param array $options
     */
    private function render_lazy_loading_tab( $options ) {
        require_once MBRPE_PLUGIN_DIR . 'includes/admin/tabs/lazy-loading.php';
    }

    /**
     * Render database tab
     *
     * @param array $options
     */
    private function render_database_tab( $options ) {
        require_once MBRPE_PLUGIN_DIR . 'includes/admin/tabs/database.php';
    }

    /**
     * Render WebP tab
     *
     * @param array $options
     */
    private function render_webp_tab( $options ) {
        require_once MBRPE_PLUGIN_DIR . 'includes/admin/tabs/webp.php';
    }

    /**
     * Render Server Headers tab (v1.12.0).
     *
     * @param array $options
     */
    private function render_server_headers_tab( $options ) {
        require_once MBRPE_PLUGIN_DIR . 'includes/admin/tabs/server-headers.php';
    }

    /**
     * Render Diagnostics tab (v1.12.0).
     *
     * @param array $options
     */
    private function render_diagnostics_tab( $options ) {
        require_once MBRPE_PLUGIN_DIR . 'includes/admin/tabs/diagnostics.php';
    }

    /**
     * Render Orphaned Media tab. Renamed from render_orphaned_images_tab()
     * in v1.11.0 when scope expanded beyond images.
     *
     * @param array $options
     */
    private function render_orphaned_media_tab( $options ) {
        require_once MBRPE_PLUGIN_DIR . 'includes/admin/tabs/orphaned-media.php';
    }

    /**
     * Render WooCommerce tab
     *
     * @param array $options
     */
    private function render_woocommerce_tab( $options ) {
        require_once MBRPE_PLUGIN_DIR . 'includes/admin/tabs/woocommerce.php';
    }

    /**
     * Sanitize WooCommerce options
     *
     * @param array $options
     * @return array
     */
    private function sanitize_woocommerce_options( $options ) {
        $sanitized = array();

        $boolean_fields = array(
            'disable_scripts_non_shop',
            'disable_styles_non_shop',
            'disable_block_assets_non_shop',
            'disable_password_strength',
            'disable_marketplace_suggestions',
            'disable_dashboard_widgets',
            'disable_admin_scripts_non_wc',
            'enable_scheduled_cleanup',
        );

        foreach ( $boolean_fields as $field ) {
            $sanitized[ $field ] = isset( $options[ $field ] ) ? (bool) $options[ $field ] : false;
        }

        // Cart fragments mode
        $valid_modes = array( 'default', 'non_shop', 'always' );
        $mode = isset( $options['cart_fragments_mode'] ) ? sanitize_text_field( $options['cart_fragments_mode'] ) : 'default';
        $sanitized['cart_fragments_mode'] = in_array( $mode, $valid_modes, true ) ? $mode : 'default';

        // Action Scheduler retention (days)
        $sanitized['action_scheduler_retention'] = isset( $options['action_scheduler_retention'] ) ? absint( $options['action_scheduler_retention'] ) : 0;

        // Defensive: if the user has just enabled scheduled cleanup and the weekly
        // cron is not registered (e.g. site missed the activation hook, or the
        // event was cleared by another plugin), re-schedule it now.
        if ( $sanitized['enable_scheduled_cleanup'] && ! wp_next_scheduled( 'mbrpe_database_cleanup' ) ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, 'weekly', 'mbrpe_database_cleanup' );
        }

        return $sanitized;
    }

    /**
     * Sanitize Server Headers options.
     *
     * @since 1.12.0
     * @param array $options
     * @return array
     */
    private function sanitize_server_headers_options( $options ) {
        $sanitized = array(
            'browser_cache'    => ! empty( $options['browser_cache'] ) ? 1 : 0,
            'gzip_compression' => ! empty( $options['gzip_compression'] ) ? 1 : 0,
        );
        return $sanitized;
    }

    /**
     * AJAX: clear expired WooCommerce sessions
     */
    public function ajax_wc_clear_sessions() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-performance' ) ) );
        }

        if ( ! class_exists( 'MBRPE_WooCommerce_Optimizations' ) ) {
            wp_send_json_error( array( 'message' => __( 'WooCommerce module not loaded.', 'mbr-performance' ) ) );
        }

        $deleted = MBRPE_WooCommerce_Optimizations::clear_expired_sessions();

        wp_send_json_success( array(
            /* translators: %d: number of sessions cleared */
            'message' => sprintf( __( 'Cleared %d expired sessions.', 'mbr-performance' ), $deleted ),
        ) );
    }

    /**
     * AJAX: clear WooCommerce transients
     */
    public function ajax_wc_clear_transients() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-performance' ) ) );
        }

        if ( ! class_exists( 'MBRPE_WooCommerce_Optimizations' ) ) {
            wp_send_json_error( array( 'message' => __( 'WooCommerce module not loaded.', 'mbr-performance' ) ) );
        }

        $ran = MBRPE_WooCommerce_Optimizations::clear_transients();

        if ( ! $ran ) {
            wp_send_json_error( array( 'message' => __( 'WooCommerce functions not available.', 'mbr-performance' ) ) );
        }

        wp_send_json_success( array( 'message' => __( 'WooCommerce transients cleared.', 'mbr-performance' ) ) );
    }

    /**
     * AJAX: trigger Action Scheduler cleanup
     */
    public function ajax_wc_cleanup_action_scheduler() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-performance' ) ) );
        }

        if ( ! class_exists( 'MBRPE_WooCommerce_Optimizations' ) ) {
            wp_send_json_error( array( 'message' => __( 'WooCommerce module not loaded.', 'mbr-performance' ) ) );
        }

        $deleted = MBRPE_WooCommerce_Optimizations::run_action_scheduler_cleanup();

        wp_send_json_success( array(
            /* translators: %d: number of actions deleted */
            'message' => sprintf( __( 'Action Scheduler cleanup triggered. Removed %d historical actions.', 'mbr-performance' ), $deleted ),
        ) );
    }

    /**
     * Detect plugin upgrades and, if the user had the legacy WooCommerce
     * options enabled, set a one-time admin notice pointing them at the
     * new WooCommerce tab. Runs on admin_init.
     */
    public function maybe_flag_wc_migration_notice() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Don't re-flag if already flagged or permanently dismissed.
        if ( get_option( 'mbrpe_wc_migration_notice_shown' ) ) {
            return;
        }

        $installed = get_option( 'mbrpe_version' );
        if ( ! $installed ) {
            // Fresh install — no upgrade to notify about.
            return;
        }

        // Only trigger when upgrading from a version older than 1.9.0.
        if ( version_compare( $installed, '1.9.0', '>=' ) ) {
            return;
        }

        // Only show the notice if WooCommerce is active and the user had
        // at least one of the legacy WC options enabled.
        $opts = get_option( 'mbrpe_options', array() );
        $had_legacy_wc = ! empty( $opts['core']['disable_woocommerce_scripts'] )
            || ! empty( $opts['css']['disable_woocommerce_css'] );

        if ( $had_legacy_wc && class_exists( 'WooCommerce' ) ) {
            update_option( 'mbrpe_wc_migration_notice_shown', 1 );
        }

        // Always bump the stored version so we don't re-check every admin load.
        update_option( 'mbrpe_version', MBRPE_VERSION );
    }

    /**
     * Render the WooCommerce settings migration notice.
     */
    public function maybe_render_wc_migration_notice() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // 1 = show, 2 = dismissed. Anything else: nothing to render.
        if ( (int) get_option( 'mbrpe_wc_migration_notice_shown' ) !== 1 ) {
            return;
        }

        $settings_url = admin_url( 'admin.php?page=mbr-performance&tab=woocommerce' );
        $nonce        = wp_create_nonce( 'mbrpe_nonce' );
        ?>
        <div class="notice notice-info is-dismissible" id="mbr-performance-wc-migration-notice" data-nonce="<?php echo esc_attr( $nonce ); ?>">
            <p>
                <strong><?php esc_html_e( 'MBR Performance:', 'mbr-performance' ); ?></strong>
                <?php
                printf(
                    /* translators: %s: link to the new WooCommerce tab */
                    esc_html__( 'WooCommerce settings have moved — check the new %s for cart fragments, Action Scheduler retention, session cleanup and more.', 'mbr-performance' ),
                    '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'WooCommerce tab', 'mbr-performance' ) . '</a>'
                );
                ?>
                <?php esc_html_e( 'Your existing settings are still active.', 'mbr-performance' ); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Enqueue the dismiss handler for the WooCommerce migration notice.
     *
     * Enqueued on whichever admin page the notice appears on (gated on the
     * same condition as the notice), rather than printed inline. Uses the
     * core ajaxurl global and the nonce carried on the notice's data attribute.
     */
    public function enqueue_wc_migration_assets() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        if ( (int) get_option( 'mbrpe_wc_migration_notice_shown' ) !== 1 ) {
            return;
        }
        ob_start();
        ?>
(function() {
    var notice = document.getElementById('mbr-performance-wc-migration-notice');
    if (!notice) return;
    notice.addEventListener('click', function(e) {
        if (!e.target.classList.contains('notice-dismiss')) return;
        var data = new FormData();
        data.append('action', 'mbrpe_dismiss_wc_migration_notice');
        data.append('nonce', notice.getAttribute('data-nonce'));
        fetch(ajaxurl, { method: 'POST', body: data, credentials: 'same-origin' });
    });
})();
        <?php
        $js = ob_get_clean();
        wp_register_script( 'mbr-performance-wc-migration', false, array(), MBRPE_VERSION, true );
        wp_enqueue_script( 'mbr-performance-wc-migration' );
        wp_add_inline_script( 'mbr-performance-wc-migration', $js );
    }

    /**
     * AJAX: dismiss the WooCommerce migration notice permanently.
     */
    public function ajax_dismiss_wc_migration_notice() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-performance' ) ) );
        }

        update_option( 'mbrpe_wc_migration_notice_shown', 2 );
        wp_send_json_success();
    }

    /**
     * AJAX save settings
     */
    public function ajax_save_settings() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-performance' ) ) );
        }

        // Multisite: block saves when per-site overrides are disabled (unless super admin)
        if ( is_multisite()
            && class_exists( 'MBRPE_Multisite' )
            && ! MBRPE_Multisite::allow_site_overrides()
            && ! is_super_admin()
        ) {
            wp_send_json_error( array( 'message' => __( 'Per-site overrides are disabled by the network administrator.', 'mbr-performance' ) ) );
        }
        
        // Get posted data
        $options = isset( $_POST['options'] ) ? $_POST['options'] : array();
        
        // Sanitize and save
        $sanitized = $this->sanitize_options( $options );
        mbrpe()->update_options( $sanitized );
        
        wp_send_json_success( array( 'message' => __( 'Settings saved successfully.', 'mbr-performance' ) ) );
    }
    
    /**
     * AJAX clean revisions
     */
    public function ajax_clean_revisions() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-performance' ) ) );
        }
        
        global $wpdb;
        $keep = isset( $_POST['keep'] ) ? absint( $_POST['keep'] ) : 5;
        
        // Get all posts with revisions
        $posts = $wpdb->get_results( "SELECT ID FROM {$wpdb->posts} WHERE post_type != 'revision'" );
        $deleted = 0;
        
        foreach ( $posts as $post ) {
            $revisions = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT ID FROM {$wpdb->posts} WHERE post_parent = %d AND post_type = 'revision' ORDER BY post_date DESC",
                    $post->ID
                )
            );
            
            if ( count( $revisions ) > $keep ) {
                $to_delete = array_slice( $revisions, $keep );
                foreach ( $to_delete as $revision ) {
                    wp_delete_post_revision( $revision->ID );
                    $deleted++;
                }
            }
        }
        
        // translators: %d = number of revisions deleted.
        wp_send_json_success( array( 'message' => sprintf( __( 'Deleted %d excess revisions.', 'mbr-performance' ), $deleted ) ) );
    }
    
    /**
     * AJAX scan post meta
     */
    public function ajax_scan_post_meta() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-performance' ) ) );
        }
        
        global $wpdb;
        $count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id NOT IN (SELECT ID FROM {$wpdb->posts})" );
        
        wp_send_json_success( array( 'count' => $count ) );
    }
    
    /**
     * AJAX delete post meta
     */
    public function ajax_delete_post_meta() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-performance' ) ) );
        }
        
        global $wpdb;
        $deleted = $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE post_id NOT IN (SELECT ID FROM {$wpdb->posts})" );
        
        // translators: %d = number of orphaned post meta entries deleted.
        wp_send_json_success( array( 'message' => sprintf( __( 'Deleted %d orphaned post meta entries.', 'mbr-performance' ), $deleted ) ) );
    }
    
    /**
     * AJAX scan comment meta
     */
    public function ajax_scan_comment_meta() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-performance' ) ) );
        }
        
        global $wpdb;
        $count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->commentmeta} WHERE comment_id NOT IN (SELECT comment_ID FROM {$wpdb->comments})" );
        
        wp_send_json_success( array( 'count' => $count ) );
    }
    
    /**
     * AJAX delete comment meta
     */
    public function ajax_delete_comment_meta() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-performance' ) ) );
        }
        
        global $wpdb;
        $deleted = $wpdb->query( "DELETE FROM {$wpdb->commentmeta} WHERE comment_id NOT IN (SELECT comment_ID FROM {$wpdb->comments})" );
        
        // translators: %d = number of orphaned comment meta entries deleted.
        wp_send_json_success( array( 'message' => sprintf( __( 'Deleted %d orphaned comment meta entries.', 'mbr-performance' ), $deleted ) ) );
    }
    
    /**
     * AJAX scan relationships
     */
    public function ajax_scan_relationships() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-performance' ) ) );
        }
        
        global $wpdb;
        $count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->term_relationships} WHERE object_id NOT IN (SELECT ID FROM {$wpdb->posts})" );
        
        wp_send_json_success( array( 'count' => $count ) );
    }
    
    /**
     * AJAX delete relationships
     */
    public function ajax_delete_relationships() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-performance' ) ) );
        }
        
        global $wpdb;
        $deleted = $wpdb->query( "DELETE FROM {$wpdb->term_relationships} WHERE object_id NOT IN (SELECT ID FROM {$wpdb->posts})" );
        
        // translators: %d = number of orphaned relationships deleted.
        wp_send_json_success( array( 'message' => sprintf( __( 'Deleted %d orphaned relationships.', 'mbr-performance' ), $deleted ) ) );
    }
    
    /**
     * AJAX scan term meta
     */
    public function ajax_scan_term_meta() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-performance' ) ) );
        }
        
        global $wpdb;
        $count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->termmeta} WHERE term_id NOT IN (SELECT term_id FROM {$wpdb->terms})" );
        
        wp_send_json_success( array( 'count' => $count ) );
    }
    
    /**
     * AJAX delete term meta
     */
    public function ajax_delete_term_meta() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-performance' ) ) );
        }
        
        global $wpdb;
        $deleted = $wpdb->query( "DELETE FROM {$wpdb->termmeta} WHERE term_id NOT IN (SELECT term_id FROM {$wpdb->terms})" );
        
        // translators: %d = number of orphaned term meta entries deleted.
        wp_send_json_success( array( 'message' => sprintf( __( 'Deleted %d orphaned term meta entries.', 'mbr-performance' ), $deleted ) ) );
    }
    
    /**
     * AJAX get transient stats
     */
    public function ajax_transient_stats() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-performance' ) ) );
        }
        
        global $wpdb;
        $total = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'" );
        $expired = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_%' AND option_value < UNIX_TIMESTAMP()" );
        
        wp_send_json_success( array( 
            // translators: 1 = total transient count, 2 = expired transient count.
            'message' => sprintf( __( 'Total Transients: %1$d | Expired: %2$d', 'mbr-performance' ), $total, $expired ) 
        ) );
    }
    
    /**
     * AJAX delete expired transients
     */
    public function ajax_delete_expired_transients() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-performance' ) ) );
        }
        
        global $wpdb;
        
        // Get expired transient timeout keys
        $expired = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_%' AND option_value < UNIX_TIMESTAMP()" );
        
        $deleted = 0;
        foreach ( $expired as $transient ) {
            $key = str_replace( '_transient_timeout_', '', $transient );
            delete_transient( $key );
            $deleted++;
        }
        
        // translators: %d = number of expired transients deleted.
        wp_send_json_success( array( 'message' => sprintf( __( 'Deleted %d expired transients.', 'mbr-performance' ), $deleted ) ) );
    }
    
    /**
     * AJAX delete all transients
     */
    public function ajax_delete_all_transients() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-performance' ) ) );
        }
        
        global $wpdb;
        $deleted = $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'" );
        
        // translators: %d = number of transients deleted.
        wp_send_json_success( array( 'message' => sprintf( __( 'Deleted %d transients.', 'mbr-performance' ), $deleted ) ) );
    }
    
    /**
     * AJAX optimize tables
     */
    public function ajax_optimize_tables() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-performance' ) ) );
        }
        
        global $wpdb;
        $tables = $wpdb->get_col( "SHOW TABLES" );
        $optimized = 0;
        
        foreach ( $tables as $table ) {
            $wpdb->query( "OPTIMIZE TABLE `{$table}`" );
            $optimized++;
        }
        
        // translators: %d = number of database tables optimized.
        wp_send_json_success( array( 'message' => sprintf( __( 'Optimized %d tables.', 'mbr-performance' ), $optimized ) ) );
    }
    
    /**
     * AJAX convert to InnoDB
     */
    public function ajax_convert_innodb() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-performance' ) ) );
        }
        
        global $wpdb;
        
        // Get all tables with their engine type
        $tables = $wpdb->get_results( "SHOW TABLE STATUS" );
        
        if ( empty( $tables ) ) {
            wp_send_json_error( array( 'message' => __( 'No tables found.', 'mbr-performance' ) ) );
        }
        
        $converted = 0;
        $errors = array();
        
        foreach ( $tables as $table ) {
            if ( strtolower( $table->Engine ) === 'myisam' ) {
                $result = $wpdb->query( "ALTER TABLE `{$table->Name}` ENGINE=InnoDB" );
                
                if ( $result === false ) {
                    $errors[] = $table->Name . ': ' . $wpdb->last_error;
                } else {
                    $converted++;
                }
            }
        }
        
        if ( $converted === 0 && empty( $errors ) ) {
            wp_send_json_success( array( 'message' => __( 'No MyISAM tables found. All tables are already InnoDB.', 'mbr-performance' ) ) );
        } elseif ( ! empty( $errors ) ) {
            wp_send_json_error( array( 
                'message' => sprintf( 
                    // translators: 1 = number of tables converted, 2 = comma-separated list of errors.
                    __( 'Converted %1$d tables. Errors: %2$s', 'mbr-performance' ), 
                    $converted, 
                    implode( ', ', $errors ) 
                ) 
            ) );
        } else {
            // translators: %d = number of MyISAM tables converted to InnoDB.
            wp_send_json_success( array( 'message' => sprintf( __( 'Successfully converted %d MyISAM tables to InnoDB.', 'mbr-performance' ), $converted ) ) );
        }
    }
    
    /**
     * AJAX repair tables
     */
    public function ajax_repair_tables() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-performance' ) ) );
        }
        
        global $wpdb;
        $tables = $wpdb->get_col( "SHOW TABLES" );
        $repaired = 0;
        
        foreach ( $tables as $table ) {
            $wpdb->query( "REPAIR TABLE `{$table}`" );
            $repaired++;
        }
        
        // translators: %d = number of database tables repaired.
        wp_send_json_success( array( 'message' => sprintf( __( 'Checked and repaired %d tables.', 'mbr-performance' ), $repaired ) ) );
    }
    
    /**
     * AJAX get database info
     */
    public function ajax_db_info() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-performance' ) ) );
        }
        
        global $wpdb;
        
        $size = $wpdb->get_var( "SELECT SUM(data_length + index_length) / 1024 / 1024 FROM information_schema.TABLES WHERE table_schema = '{$wpdb->dbname}'" );
        $tables = $wpdb->get_var( "SELECT COUNT(*) FROM information_schema.TABLES WHERE table_schema = '{$wpdb->dbname}'" );
        
        $html = '<ul>';
        // translators: %.2f = database size in megabytes.
        $html .= '<li>' . sprintf( __( 'Database Size: %.2f MB', 'mbr-performance' ), $size ) . '</li>';
        // translators: %d = total number of database tables.
        $html .= '<li>' . sprintf( __( 'Total Tables: %d', 'mbr-performance' ), $tables ) . '</li>';
        $html .= '</ul>';
        
        wp_send_json_success( array( 'html' => $html ) );
    }
    
    /**
     * AJAX scan CSS
     */
    public function ajax_scan_css() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-performance' ) ) );
        }
        
        // Get homepage URL
        $home_url = home_url( '/' );
        
        // Fetch the homepage HTML
        $response = wp_remote_get( $home_url, array(
            'timeout' => 30,
            'sslverify' => false,
        ) );
        
        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => __( 'Failed to fetch homepage.', 'mbr-performance' ) ) );
        }
        
        $html = wp_remote_retrieve_body( $response );
        
        // Extract all CSS file URLs
        preg_match_all( '/<link[^>]+rel=["\']stylesheet["\'][^>]+href=["\'](https?:\/\/[^"\']+)["\']/', $html, $css_matches );
        preg_match_all( '/<link[^>]+href=["\'](https?:\/\/[^"\']+)["\'][^>]+rel=["\']stylesheet["\']/', $html, $css_matches2 );
        
        $css_urls = array_merge( $css_matches[1], $css_matches2[1] );
        $css_urls = array_unique( $css_urls );
        
        // Extract all HTML classes and IDs
        preg_match_all( '/class=["\']([\w\s\-_]+)["\']/', $html, $class_matches );
        preg_match_all( '/id=["\']([\w\-_]+)["\']/', $html, $id_matches );
        
        $html_classes = array();
        foreach ( $class_matches[1] as $classes ) {
            $html_classes = array_merge( $html_classes, explode( ' ', $classes ) );
        }
        $html_classes = array_unique( array_filter( $html_classes ) );
        
        $html_ids = array_unique( $id_matches[1] );
        
        // Fetch and analyze CSS
        $total_css_size = 0;
        $total_rules = 0;
        $used_selectors = 0;
        $unused_selectors = 0;
        $css_files_info = array();
        
        foreach ( $css_urls as $css_url ) {
            $css_response = wp_remote_get( $css_url, array(
                'timeout' => 15,
                'sslverify' => false,
            ) );
            
            if ( is_wp_error( $css_response ) ) {
                continue;
            }
            
            $css_content = wp_remote_retrieve_body( $css_response );
            $size = strlen( $css_content );
            $total_css_size += $size;
            
            // Count rules
            preg_match_all( '/[^}]+\{[^}]+\}/', $css_content, $rules );
            $rule_count = count( $rules[0] );
            $total_rules += $rule_count;
            
            // Extract selectors
            preg_match_all( '/([^\s{]+)\s*\{/', $css_content, $selector_matches );
            $selectors = $selector_matches[1];
            
            $file_used = 0;
            $file_unused = 0;
            
            foreach ( $selectors as $selector ) {
                // Check if selector is used in HTML
                $is_used = false;
                
                // Check for class selectors
                if ( preg_match( '/\.([\w\-_]+)/', $selector, $class_match ) ) {
                    if ( in_array( $class_match[1], $html_classes ) ) {
                        $is_used = true;
                    }
                }
                
                // Check for ID selectors
                if ( preg_match( '/#([\w\-_]+)/', $selector, $id_match ) ) {
                    if ( in_array( $id_match[1], $html_ids ) ) {
                        $is_used = true;
                    }
                }
                
                // Check for element selectors (assume used)
                if ( preg_match( '/^(body|html|div|span|p|a|h\d|ul|li|img|header|footer|nav|section)/', $selector ) ) {
                    $is_used = true;
                }
                
                if ( $is_used ) {
                    $file_used++;
                    $used_selectors++;
                } else {
                    $file_unused++;
                    $unused_selectors++;
                }
            }
            
            $usage_percent = $rule_count > 0 ? round( ( $file_used / $rule_count ) * 100 ) : 0;
            
            $css_files_info[] = array(
                'url' => $css_url,
                'size' => $size,
                'rules' => $rule_count,
                'used' => $file_used,
                'unused' => $file_unused,
                'usage_percent' => $usage_percent,
            );
        }
        
        // Store scan results
        $scan_data = array(
            'timestamp' => current_time( 'mysql' ),
            'total_size' => $total_css_size,
            'total_rules' => $total_rules,
            'used_selectors' => $used_selectors,
            'unused_selectors' => $unused_selectors,
            'files' => $css_files_info,
        );
        
        update_option( 'mbrpe_css_scan', $scan_data );
        
        // Build HTML report
        $html_report = '<div class="mbr-scan-results">';
        $html_report .= '<h3>' . __( 'CSS Scan Results', 'mbr-performance' ) . '</h3>';
        // translators: %s = total CSS size, human-readable (e.g. 1.2 MB).
        $html_report .= '<p><strong>' . sprintf( __( 'Total CSS Size: %s', 'mbr-performance' ), size_format( $total_css_size ) ) . '</strong></p>';
        // translators: %d = total number of CSS rules.
        $html_report .= '<p>' . sprintf( __( 'Total Rules: %d', 'mbr-performance' ), $total_rules ) . '</p>';
        // translators: %d = number of CSS selectors found in use.
        $html_report .= '<p>' . sprintf( __( 'Used Selectors: %d', 'mbr-performance' ), $used_selectors ) . '</p>';
        // translators: %d = number of potentially unused CSS selectors.
        $html_report .= '<p style="color: #d63638;">' . sprintf( __( 'Potentially Unused: %d', 'mbr-performance' ), $unused_selectors ) . '</p>';
        
        if ( $total_rules > 0 ) {
            $overall_usage = round( ( $used_selectors / $total_rules ) * 100 );
            // translators: %d%% = overall CSS usage percentage.
            $html_report .= '<p><strong>' . sprintf( __( 'Overall Usage: %d%%', 'mbr-performance' ), $overall_usage ) . '</strong></p>';
        }
        
        $html_report .= '<h4>' . __( 'Files:', 'mbr-performance' ) . '</h4>';
        $html_report .= '<table class="widefat striped"><thead><tr>';
        $html_report .= '<th>' . __( 'File', 'mbr-performance' ) . '</th>';
        $html_report .= '<th>' . __( 'Size', 'mbr-performance' ) . '</th>';
        $html_report .= '<th>' . __( 'Rules', 'mbr-performance' ) . '</th>';
        $html_report .= '<th>' . __( 'Usage', 'mbr-performance' ) . '</th>';
        $html_report .= '</tr></thead><tbody>';
        
        foreach ( $css_files_info as $file ) {
            $filename = basename( wp_parse_url( $file['url'], PHP_URL_PATH ) );
            $color = $file['usage_percent'] < 30 ? '#d63638' : ( $file['usage_percent'] < 60 ? '#dba617' : '#00a32a' );
            
            $html_report .= '<tr>';
            $html_report .= '<td title="' . esc_attr( $file['url'] ) . '">' . esc_html( $filename ) . '</td>';
            $html_report .= '<td>' . size_format( $file['size'] ) . '</td>';
            $html_report .= '<td>' . $file['rules'] . '</td>';
            $html_report .= '<td style="color: ' . $color . '; font-weight: bold;">' . $file['usage_percent'] . '%</td>';
            $html_report .= '</tr>';
        }
        
        $html_report .= '</tbody></table>';
        $html_report .= '<p class="description" style="margin-top: 15px;">' . __( 'Note: This scan only checks the homepage. Some CSS may be used on other pages.', 'mbr-performance' ) . '</p>';
        $html_report .= '</div>';
        
        wp_send_json_success( array( 
            'html' => $html_report,
            'message' => sprintf( 
                // translators: 1 = number of CSS files scanned, 2 = total size, human-readable.
                __( 'Scanned %1$d CSS files totaling %2$s', 'mbr-performance' ),
                count( $css_urls ),
                size_format( $total_css_size )
            )
        ) );
    }
    
    /**
     * AJAX clear scan data
     */
    public function ajax_clear_scan_data() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-performance' ) ) );
        }
        
        delete_option( 'mbrpe_css_scan' );
        
        wp_send_json_success( array( 'message' => __( 'Scan data cleared.', 'mbr-performance' ) ) );
    }

    /**
     * AJAX reset all settings to defaults.
     *
     * Replaces the entire options array with the canonical defaults. Guarded
     * by nonce and capability check; runs as a POST request (never a GET) so
     * it can't be triggered by a crawler, prefetch, or a link the user lands
     * on by accident.
     */
    public function ajax_reset_settings() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-performance' ) ) );
        }

        $defaults = mbrpe()->default_options();

        // update_options() both persists to the DB (via update_option, which
        // runs the registered sanitiser) and refreshes the in-memory cache.
        mbrpe()->update_options( $defaults );

        wp_send_json_success( array( 'message' => __( 'Settings reset to defaults.', 'mbr-performance' ) ) );
    }
    
    /**
     * AJAX download fonts
     */
    public function ajax_download_fonts() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-performance' ) ) );
        }
        
        // Get detected Google Fonts
        $fonts = $this->detect_google_fonts();
        
        if ( empty( $fonts ) ) {
            wp_send_json_error( array( 'message' => __( 'No Google Fonts detected on your site. Try using the manual input option below.', 'mbr-performance' ) ) );
        }
        
        $result = $this->download_fonts_to_local( $fonts );
        
        wp_send_json_success( array( 'message' => $result['message'] ) );
    }
    
    /**
     * AJAX download manual fonts
     */
    public function ajax_download_manual_fonts() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-performance' ) ) );
        }
        
        $manual_fonts = isset( $_POST['manual_fonts'] ) ? sanitize_textarea_field( $_POST['manual_fonts'] ) : '';
        
        if ( empty( $manual_fonts ) ) {
            wp_send_json_error( array( 'message' => __( 'Please enter fonts to download.', 'mbr-performance' ) ) );
        }
        
        // Parse manual fonts
        $fonts = $this->parse_manual_fonts( $manual_fonts );
        
        if ( empty( $fonts ) ) {
            wp_send_json_error( array( 'message' => __( 'Could not parse font input. Format: FontFamily:400,700', 'mbr-performance' ) ) );
        }
        
        $result = $this->download_fonts_to_local( $fonts );
        
        wp_send_json_success( array( 'message' => $result['message'] ) );
    }
    
    /**
     * AJAX clear font cache
     */
    public function ajax_clear_font_cache() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-performance' ) ) );
        }
        
        // Get fonts directory
        $upload_dir = wp_upload_dir();
        $fonts_dir = $upload_dir['basedir'] . '/mbr-performance-fonts';
        
        $deleted_files = 0;
        
        // Delete all files in the fonts directory
        if ( is_dir( $fonts_dir ) ) {
            $files = scandir( $fonts_dir );
            
            foreach ( $files as $file ) {
                if ( $file === '.' || $file === '..' ) {
                    continue;
                }
                
                $filepath = $fonts_dir . '/' . $file;
                
                if ( is_file( $filepath ) ) {
                    wp_delete_file( $filepath );
                    if ( ! file_exists( $filepath ) ) {
                        $deleted_files++;
                    }
                }
            }
            
            // Remove the directory itself
            @rmdir( $fonts_dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Best-effort removal of the plugin's own now-empty fonts directory under uploads; core has no rmdir wrapper.
        }
        
        // Clear the font configuration
        delete_option( 'mbrpe_local_fonts' );
        delete_option( 'mbrpe_fonts_dir' );
        
        if ( $deleted_files > 0 ) {
            $message = sprintf( 
                // translators: %d = number of font files deleted.
                __( 'Successfully cleared font cache: Deleted %d font files and reset configuration.', 'mbr-performance' ),
                $deleted_files
            );
        } else {
            $message = __( 'Font cache cleared. No font files found to delete.', 'mbr-performance' );
        }
        
        wp_send_json_success( array( 
            'message' => $message
        ) );
    }
    
    /**
     * Parse manual fonts input
     */
    private function parse_manual_fonts( $input ) {
        $fonts = array();
        $lines = array_filter( array_map( 'trim', explode( "\n", $input ) ) );
        
        foreach ( $lines as $line ) {
            if ( strpos( $line, ':' ) !== false ) {
                list( $family, $weights ) = explode( ':', $line, 2 );
                $family = trim( $family );
                
                // Make font name Title Case to match Google Fonts format
                // e.g., "open sans" or "OPEN SANS" becomes "Open Sans"
                $family = ucwords( strtolower( $family ) );
                
                $variants = array_map( 'trim', explode( ',', $weights ) );
                $fonts[ $family ] = $variants;
            } else {
                // Just family name, use regular weight
                $family = trim( $line );
                
                // Make font name Title Case
                $family = ucwords( strtolower( $family ) );
                
                $fonts[ $family ] = array( '400' );
            }
        }
        
        return $fonts;
    }
    
    /**
     * Download fonts to local storage
     */
    private function download_fonts_to_local( $fonts ) {
        // Create fonts directory
        $upload_dir = wp_upload_dir();
        $fonts_dir = $upload_dir['basedir'] . '/mbr-performance-fonts';
        
        if ( ! file_exists( $fonts_dir ) ) {
            wp_mkdir_p( $fonts_dir );
        }
        
        // CRITICAL: Clean up old font files before downloading new ones
        // This prevents loading fonts that are no longer configured
        $this->cleanup_old_fonts( $fonts_dir, $fonts );
        
        $downloaded = array();
        $failed = array();
        
        foreach ( $fonts as $font_family => $variants ) {
            foreach ( $variants as $variant ) {
                $result = $this->download_google_font( $font_family, $variant, $fonts_dir );
                
                if ( $result ) {
                    $downloaded[] = $font_family . ' (' . $variant . ')';
                } else {
                    $failed[] = $font_family . ' (' . $variant . ')';
                }
            }
        }
        
        // REPLACE (not merge) - only keep the fonts we just downloaded
        update_option( 'mbrpe_local_fonts', $fonts );
        update_option( 'mbrpe_fonts_dir', $fonts_dir );
        
        $message = sprintf( 
            // translators: 1 = number of font variants downloaded, 2 = number of failed downloads.
            __( 'Downloaded %1$d font variants. Failed: %2$d', 'mbr-performance' ), 
            count( $downloaded ), 
            count( $failed ) 
        );
        
        if ( ! empty( $downloaded ) ) {
            $message .= '<br><strong>Downloaded:</strong> ' . implode( ', ', $downloaded );
        }
        
        if ( ! empty( $failed ) ) {
            $message .= '<br><strong>Failed:</strong> ' . implode( ', ', $failed );
        }
        
        return array( 'message' => $message );
    }
    
    /**
     * Clean up old font files that are no longer in use
     *
     * @param string $fonts_dir Directory containing font files
     * @param array $current_fonts Array of currently configured fonts
     * @return int Number of files deleted
     */
    private function cleanup_old_fonts( $fonts_dir, $current_fonts ) {
        if ( ! is_dir( $fonts_dir ) ) {
            return 0;
        }
        
        // Build list of current font file prefixes we want to keep
        $keep_prefixes = array();
        foreach ( $current_fonts as $family => $variants ) {
            foreach ( $variants as $variant ) {
                $prefix = sanitize_file_name( $family . '-' . $variant );
                $keep_prefixes[] = $prefix;
            }
        }
        
        // Scan directory and remove files not in the keep list
        $files = scandir( $fonts_dir );
        $deleted = 0;
        
        foreach ( $files as $file ) {
            if ( $file === '.' || $file === '..' ) {
                continue;
            }
            
            $filepath = $fonts_dir . '/' . $file;
            
            if ( ! is_file( $filepath ) ) {
                continue;
            }
            
            // Check if this file should be kept
            $should_keep = false;
            foreach ( $keep_prefixes as $prefix ) {
                // Keep both the .css file and the font files (.woff2, .woff, .ttf)
                if ( strpos( $file, $prefix ) === 0 ) {
                    $should_keep = true;
                    break;
                }
            }
            
            // Delete if not in current configuration
            if ( ! $should_keep ) {
                wp_delete_file( $filepath );
                $deleted++;
            }
        }
        
        return $deleted;
    }
    
    /**
     * Detect Google Fonts actually used on the homepage
     * Only detects fonts that are LOADED in the page HTML, not just registered
     *
     * @return array
     */
    private function detect_google_fonts() {
        $fonts = array();
        
        // Fetch the ACTUAL homepage HTML
        $home_url = home_url( '/' );
        $response = wp_remote_get( $home_url, array(
            'timeout' => 30,
            'sslverify' => false,
        ) );
        
        if ( is_wp_error( $response ) ) {
            return $fonts;
        }
        
        $html = wp_remote_retrieve_body( $response );
        
        // Method 1: Find Google Font <link> tags in the ACTUAL rendered HTML
        preg_match_all( '/<link[^>]+href=["\']([^"\']*fonts\.googleapis\.com[^"\']*)["\'][^>]*>/i', $html, $link_matches );
        
        if ( ! empty( $link_matches[1] ) ) {
            foreach ( $link_matches[1] as $url ) {
                $url = html_entity_decode( $url );
                $parsed = $this->parse_google_font_url( $url );
                if ( ! empty( $parsed ) ) {
                    $fonts = array_merge_recursive( $fonts, $parsed );
                }
            }
        }
        
        // Method 2: Find @import rules in inline <style> blocks
        preg_match_all( '/@import\s+url\(["\']?([^"\']*fonts\.googleapis\.com[^"\']*)["\']?\)/i', $html, $import_matches );
        
        if ( ! empty( $import_matches[1] ) ) {
            foreach ( $import_matches[1] as $url ) {
                $url = html_entity_decode( $url );
                $parsed = $this->parse_google_font_url( $url );
                if ( ! empty( $parsed ) ) {
                    $fonts = array_merge_recursive( $fonts, $parsed );
                }
            }
        }
        
        // Method 3: Fetch and scan all CSS files linked in the page
        // This catches fonts loaded by themes/plugins via their own CSS
        preg_match_all( '/<link[^>]+rel=["\']stylesheet["\'][^>]+href=["\'](https?:\/\/[^"\']+\.css[^"\']*)["\']/', $html, $css_links );
        
        if ( ! empty( $css_links[1] ) ) {
            foreach ( $css_links[1] as $css_url ) {
                // Only check local CSS files (not CDN) to avoid false positives
                if ( strpos( $css_url, home_url() ) === 0 ) {
                    $css_response = wp_remote_get( $css_url, array(
                        'timeout' => 10,
                        'sslverify' => false,
                    ) );
                    
                    if ( ! is_wp_error( $css_response ) ) {
                        $css_content = wp_remote_retrieve_body( $css_response );
                        
                        // Find @import rules in the CSS file
                        preg_match_all( '/@import\s+url\(["\']?([^"\']*fonts\.googleapis\.com[^"\']*)["\']?\)/i', $css_content, $css_imports );
                        
                        if ( ! empty( $css_imports[1] ) ) {
                            foreach ( $css_imports[1] as $url ) {
                                $parsed = $this->parse_google_font_url( $url );
                                if ( ! empty( $parsed ) ) {
                                    $fonts = array_merge_recursive( $fonts, $parsed );
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // Remove duplicates
        foreach ( $fonts as $family => $variants ) {
            if ( is_array( $variants ) ) {
                $fonts[ $family ] = array_unique( $variants );
            }
        }
        
        return $fonts;
    }
    
    /**
     * Parse Google Font URL
     *
     * @param string $url
     * @return array
     */
    private function parse_google_font_url( $url ) {
        $fonts = array();
        
        // Decode URL
        $url = html_entity_decode( $url );
        
        // Parse query string
        $parsed_url = wp_parse_url( $url );
        
        if ( isset( $parsed_url['query'] ) ) {
            parse_str( $parsed_url['query'], $params );
            
            // Handle family parameter
            if ( isset( $params['family'] ) ) {
                $families = is_array( $params['family'] ) ? $params['family'] : array( $params['family'] );
                
                foreach ( $families as $family_string ) {
                    // Format: "Roboto:400,700" or "Roboto:wght@400;700" or "Roboto"
                    if ( strpos( $family_string, ':' ) !== false ) {
                        list( $family, $variants_string ) = explode( ':', $family_string, 2 );
                        
                        // Clean family name
                        $family = trim( str_replace( '+', ' ', $family ) );
                        
                        // Parse variants
                        $variants = array();
                        
                        // Handle new format: wght@400;700
                        if ( strpos( $variants_string, '@' ) !== false ) {
                            $variants_string = explode( '@', $variants_string )[1];
                            $variants = explode( ';', $variants_string );
                        } else {
                            // Handle old format: 400,700
                            $variants = explode( ',', $variants_string );
                        }
                        
                        // Clean variants
                        $variants = array_map( 'trim', $variants );
                        
                        // Convert weights to standard format
                        $standard_variants = array();
                        foreach ( $variants as $variant ) {
                            // Handle italic
                            if ( strpos( $variant, 'italic' ) !== false || strpos( $variant, 'i' ) === strlen( $variant ) - 1 ) {
                                $weight = str_replace( array( 'italic', 'i' ), '', $variant );
                                $standard_variants[] = $weight . 'italic';
                            } else {
                                $standard_variants[] = $variant;
                            }
                        }
                        
                        $fonts[ $family ] = $standard_variants;
                    } else {
                        // No variants specified, use regular (400)
                        $family = trim( str_replace( '+', ' ', $family_string ) );
                        $fonts[ $family ] = array( '400' );
                    }
                }
            }
        }
        
        return $fonts;
    }
    
    /**
     * Download a single Google Font variant
     *
     * @param string $family
     * @param string $variant
     * @param string $fonts_dir
     * @return bool
     */
    private function download_google_font( $family, $variant, $fonts_dir ) {
        // Build Google Fonts API URL - always use wght syntax for API v2
        $family_encoded = str_replace( ' ', '+', $family );
        
        // Handle italic
        if ( strpos( $variant, 'italic' ) !== false ) {
            $weight = str_replace( 'italic', '', $variant );
            $weight = $weight ? $weight : '400'; // Default to 400 if just 'italic'
            $api_url = "https://fonts.googleapis.com/css2?family={$family_encoded}:ital,wght@1,{$weight}&display=swap";
        } else {
            // Regular weight
            $api_url = "https://fonts.googleapis.com/css2?family={$family_encoded}:wght@{$variant}&display=swap";
        }
        
        // Fetch the CSS with user agent for WOFF2
        $response = wp_remote_get( $api_url, array(
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'timeout' => 30,
            'sslverify' => false,
        ) );
        
        if ( is_wp_error( $response ) ) {
            return false;
        }
        
        $css = wp_remote_retrieve_body( $response );
        
        if ( empty( $css ) || strpos( $css, '@font-face' ) === false ) {
            // No font-face found, API request might have failed
            return false;
        }
        
        // CRITICAL: Extract ONLY the first @font-face block (Latin subset)
        // Google Fonts CSS contains multiple @font-face blocks for different Unicode ranges
        // We only want the main Latin one (usually the last one in the list)
        
        // Match all @font-face blocks
        preg_match_all( '/@font-face\s*\{[^}]*\}/s', $css, $all_font_faces );
        
        if ( empty( $all_font_faces[0] ) ) {
            // Try a more greedy pattern that handles nested braces
            preg_match_all( '/@font-face\s*\{(?:[^{}]|\{[^}]*\})*\}/s', $css, $all_font_faces );
        }
        
        if ( empty( $all_font_faces[0] ) ) {
            return false;
        }
        
        // Use the LAST @font-face (typically the main Latin one without unicode-range restriction)
        $css = end( $all_font_faces[0] );
        
        // Extract font URL from this @font-face only
        preg_match( '/url\(([^)]+)\)/', $css, $matches );
        
        if ( empty( $matches[1] ) ) {
            return false;
        }
        
        $local_css = $css;
        $font_url = trim( $matches[1], " \t\n\r\0\x0B\"'" ); // Remove quotes and whitespace
        
        // Skip if not a URL
        if ( strpos( $font_url, 'http' ) !== 0 ) {
            return false;
        }
        
        // Download the font file
        $font_response = wp_remote_get( $font_url, array(
            'timeout' => 30,
            'sslverify' => false,
        ) );
        
        if ( is_wp_error( $font_response ) ) {
            return false;
        }
        
        $font_data = wp_remote_retrieve_body( $font_response );
        
        // Check if we actually got font data
        if ( empty( $font_data ) || strlen( $font_data ) < 100 ) {
            return false;
        }
        
        // Generate local filename
        $extension = 'woff2'; // Default to woff2
        if ( strpos( $font_url, '.woff2' ) !== false ) {
            $extension = 'woff2';
        } elseif ( strpos( $font_url, '.woff' ) !== false ) {
            $extension = 'woff';
        } elseif ( strpos( $font_url, '.ttf' ) !== false ) {
            $extension = 'ttf';
        }
        
        $filename = sanitize_file_name( $family . '-' . $variant . '-' . md5( $font_url ) . '.' . $extension );
        $filepath = $fonts_dir . '/' . $filename;
        
        // Save font file
        file_put_contents( $filepath, $font_data );
        
        // Get URL for the local file
        $upload_dir = wp_upload_dir();
        $local_url = $upload_dir['baseurl'] . '/mbr-performance-fonts/' . $filename;
        
        // Replace in CSS (handle both quoted and unquoted URLs)
        $local_css = str_replace( $font_url, $local_url, $local_css );
        $local_css = str_replace( "url({$font_url})", "url({$local_url})", $local_css );
        $local_css = str_replace( "url('{$font_url}')", "url('{$local_url}')", $local_css );
        $local_css = str_replace( "url(\"{$font_url}\")", "url(\"{$local_url}\")", $local_css );
        
        // Save the modified CSS with ONLY the first @font-face (Latin)
        $css_filename = sanitize_file_name( $family . '-' . $variant . '.css' );
        $css_filepath = $fonts_dir . '/' . $css_filename;
        file_put_contents( $css_filepath, $local_css );
        
        return true;
    }

    /* ======================================================================
     * WebP Converter AJAX Handlers
     * ==================================================================== */

    /**
     * AJAX: Get all unconverted images.
     */
    public function ajax_webp_get_images() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Insufficient permissions.', 'mbr-performance' ) );
        }

        $upload_dir  = wp_upload_dir();
        $image_paths = array();
        $iterator    = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $upload_dir['basedir'] ) );

        foreach ( $iterator as $file ) {
            if ( $file->isDir() ) {
                continue;
            }
            $extension = strtolower( $file->getExtension() );
            if ( in_array( $extension, array( 'jpg', 'jpeg', 'png' ), true ) ) {
                $webp_path = preg_replace( '/\.(jpe?g|png)$/i', '.webp', $file->getPathname() );
                if ( ! file_exists( $webp_path ) ) {
                    $image_paths[] = str_replace( $upload_dir['basedir'] . '/', '', $file->getPathname() );
                }
            }
        }

        wp_send_json_success( $image_paths );
    }

    /**
     * AJAX: Process (convert) a single image.
     */
    public function ajax_webp_process_image() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Insufficient permissions.', 'mbr-performance' ) );
        }

        if ( ! isset( $_POST['image_path'] ) ) {
            wp_send_json_error( __( 'Missing image path.', 'mbr-performance' ) );
        }

        $image_path_relative = sanitize_text_field( wp_unslash( $_POST['image_path'] ) );
        $upload_dir          = wp_upload_dir();
        $full_path           = $upload_dir['basedir'] . '/' . $image_path_relative;

        $converter = MBRPE_WebP_Converter::instance();
        $result    = $converter->convert_single_image( $full_path );

        if ( ! $result ) {
            wp_send_json_error( __( 'Server failed to convert the file.', 'mbr-performance' ) );
        }

        if ( 'skipped' === $result['status'] ) {
            wp_send_json_success( array(
                'original_path' => $image_path_relative,
                'original_size' => size_format( $result['original_size'], 2 ),
                'webp_size'     => size_format( $result['webp_size'], 2 ),
                'compression'   => __( 'Skipped (larger)', 'mbr-performance' ),
            ) );
            return;
        }

        if ( 'success' === $result['status'] ) {
            $converted_images   = get_option( 'mbrpe_webp_converted_images', array() );
            $converted_images[] = array(
                'original_path' => $image_path_relative,
                'webp_path'     => str_replace( $upload_dir['basedir'] . '/', '', $result['webp_path'] ),
                'original_size' => $result['original_size'],
                'webp_size'     => $result['webp_size'],
            );
            update_option( 'mbrpe_webp_converted_images', $converted_images );

            $compression = ( ( $result['original_size'] - $result['webp_size'] ) / $result['original_size'] ) * 100;

            wp_send_json_success( array(
                'original_path' => $image_path_relative,
                'original_size' => size_format( $result['original_size'], 2 ),
                'webp_size'     => size_format( $result['webp_size'], 2 ),
                'compression'   => number_format( $compression, 2 ) . '%',
            ) );
        }
    }

    /**
     * AJAX: Clear all conversion history.
     */
    public function ajax_webp_clear_history() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-performance' ) ) );
        }

        if ( false === get_option( 'mbrpe_webp_converted_images' ) ) {
            wp_send_json_success( __( 'History was already empty.', 'mbr-performance' ) );
            return;
        }

        if ( delete_option( 'mbrpe_webp_converted_images' ) ) {
            wp_send_json_success( __( 'Conversion history cleared successfully.', 'mbr-performance' ) );
        } else {
            wp_send_json_error( __( 'Could not clear the history due to a database error.', 'mbr-performance' ) );
        }
    }

    /**
     * AJAX: Bulk-delete items from conversion history.
     */
    public function ajax_webp_bulk_delete() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Insufficient permissions.', 'mbr-performance' ) );
        }

        if ( ! isset( $_POST['items'] ) || ! is_array( $_POST['items'] ) || empty( $_POST['items'] ) ) {
            wp_send_json_error( __( 'No items were selected.', 'mbr-performance' ) );
        }

        $items_to_delete   = array_map( 'sanitize_text_field', wp_unslash( $_POST['items'] ) );
        $converted_images  = get_option( 'mbrpe_webp_converted_images', array() );

        if ( empty( $converted_images ) ) {
            wp_send_json_success( __( 'History was already empty.', 'mbr-performance' ) );
            return;
        }

        $new_list      = array();
        $deleted_count = 0;
        $delete_lookup = array_flip( $items_to_delete );

        foreach ( $converted_images as $image ) {
            if ( ! isset( $delete_lookup[ $image['original_path'] ] ) ) {
                $new_list[] = $image;
            } else {
                $deleted_count++;
            }
        }

        update_option( 'mbrpe_webp_converted_images', $new_list );

        wp_send_json_success(
            /* translators: %d: number of items removed from history */
            sprintf( __( 'Successfully removed %d item(s) from history.', 'mbr-performance' ), $deleted_count )
        );
    }

    /**
     * AJAX: Revert all WebP files — delete every plugin-created WebP and clear history.
     *
     * Only files tracked in the registry are removed.
     * Files that were uploaded as WebP (not created by this plugin) are left alone.
     */
    public function ajax_webp_revert_all() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-performance' ) ) );
        }

        $upload_dir    = wp_upload_dir();
        $registry      = get_option( 'mbrpe_webp_registry', array() );
        $deleted_count = 0;
        $failed_count  = 0;

        if ( ! empty( $registry ) && is_array( $registry ) ) {
            foreach ( $registry as $webp_relative_path ) {
                $full_path = $upload_dir['basedir'] . '/' . $webp_relative_path;
                if ( file_exists( $full_path ) ) {
                    if ( wp_delete_file( $full_path ) || ! file_exists( $full_path ) ) {
                        $deleted_count++;
                    } else {
                        $failed_count++;
                    }
                } else {
                    // Already gone — count as success.
                    $deleted_count++;
                }
            }
        }

        // Clear registry and history.
        delete_option( 'mbrpe_webp_registry' );
        delete_option( 'mbrpe_webp_converted_images' );
        delete_option( 'mbrpe_webp_registry_migrated' );

        // Also clear old standalone plugin data if present.
        delete_option( 'itwc_converted_images' );
        delete_option( 'itwc_webp_registry' );
        delete_option( 'itwc_registry_migrated' );

        // Remove .htaccess rules.
        MBRPE_WebP_Converter::remove_htaccess_rules();

        // Clear Elementor cache if active.
        if ( class_exists( '\Elementor\Plugin' ) ) {
            \Elementor\Plugin::instance()->files_manager->clear_cache();
        }

        if ( $failed_count > 0 ) {
            wp_send_json_success(
                sprintf(
                    /* translators: 1: deleted count, 2: failed count */
                    __( 'Deleted %1$d WebP file(s). %2$d file(s) could not be removed — check folder permissions.', 'mbr-performance' ),
                    $deleted_count,
                    $failed_count
                )
            );
        } else {
            wp_send_json_success(
                sprintf(
                    /* translators: %d: number of files deleted */
                    __( 'Successfully reverted %d WebP file(s). All originals remain untouched.', 'mbr-performance' ),
                    $deleted_count
                )
            );
        }
    }

    /**
     * AJAX: List candidate images in the uploads directory that do not yet
     * have a matching .avif sibling.
     *
     * Mirrors ajax_webp_get_images, but with the .avif probe.
     */
    public function ajax_avif_get_images() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Insufficient permissions.', 'mbr-performance' ) );
        }

        if ( ! class_exists( 'MBRPE_AVIF_Converter' )
             || ! MBRPE_AVIF_Converter::avif_supported() ) {
            wp_send_json_error( __( 'Server-side AVIF encoding is not available on this site.', 'mbr-performance' ) );
        }

        $upload_dir  = wp_upload_dir();
        $image_paths = array();
        $iterator    = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $upload_dir['basedir'] ) );

        foreach ( $iterator as $file ) {
            if ( $file->isDir() ) {
                continue;
            }
            $extension = strtolower( $file->getExtension() );
            if ( in_array( $extension, array( 'jpg', 'jpeg', 'png' ), true ) ) {
                $avif_path = preg_replace( '/\.(jpe?g|png)$/i', '.avif', $file->getPathname() );
                if ( ! file_exists( $avif_path ) ) {
                    $image_paths[] = str_replace( $upload_dir['basedir'] . '/', '', $file->getPathname() );
                }
            }
        }

        wp_send_json_success( $image_paths );
    }

    /**
     * AJAX: Convert a single image to AVIF.
     */
    public function ajax_avif_process_image() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Insufficient permissions.', 'mbr-performance' ) );
        }

        if ( ! class_exists( 'MBRPE_AVIF_Converter' ) ) {
            wp_send_json_error( __( 'AVIF converter not available.', 'mbr-performance' ) );
        }

        if ( ! isset( $_POST['image_path'] ) ) {
            wp_send_json_error( __( 'Missing image path.', 'mbr-performance' ) );
        }

        $image_path_relative = sanitize_text_field( wp_unslash( $_POST['image_path'] ) );
        $upload_dir          = wp_upload_dir();
        $full_path           = $upload_dir['basedir'] . '/' . $image_path_relative;

        $converter = MBRPE_AVIF_Converter::instance();
        $result    = $converter->convert_single_image( $full_path );

        if ( ! $result ) {
            wp_send_json_error( __( 'Server failed to convert the file.', 'mbr-performance' ) );
        }

        // Look up any existing WebP size for the same original so the row
        // we send back to the browser shows both formats at once instead of
        // a separate AVIF-only row that visually duplicates the WebP one.
        $webp_size = $this->lookup_webp_size_for( $image_path_relative );

        if ( 'skipped' === $result['status'] ) {
            wp_send_json_success( array(
                'original_path' => $image_path_relative,
                'original_size' => size_format( $result['original_size'], 2 ),
                'webp_size'     => null !== $webp_size ? size_format( $webp_size, 2 ) : '—',
                'avif_size'     => size_format( $result['avif_size'], 2 ),
                'compression'   => __( 'Skipped (larger)', 'mbr-performance' ),
            ) );
            return;
        }

        if ( 'success' === $result['status'] ) {
            $converted_images   = get_option( 'mbrpe_avif_converted_images', array() );
            $converted_images[] = array(
                'original_path' => $image_path_relative,
                'avif_path'     => str_replace( $upload_dir['basedir'] . '/', '', $result['avif_path'] ),
                'original_size' => $result['original_size'],
                'avif_size'     => $result['avif_size'],
            );
            update_option( 'mbrpe_avif_converted_images', $converted_images );

            // Compression metric shows the best available format (AVIF if
            // present, since it's smaller; otherwise WebP).
            $smaller     = ( null !== $webp_size && $webp_size < $result['avif_size'] ) ? $webp_size : $result['avif_size'];
            $compression = ( ( $result['original_size'] - $smaller ) / max( 1, $result['original_size'] ) ) * 100;

            wp_send_json_success( array(
                'original_path' => $image_path_relative,
                'original_size' => size_format( $result['original_size'], 2 ),
                'webp_size'     => null !== $webp_size ? size_format( $webp_size, 2 ) : '—',
                'avif_size'     => size_format( $result['avif_size'], 2 ),
                'compression'   => number_format( $compression, 2 ) . '%',
            ) );
        }
    }

    /**
     * Lookup helper: return the recorded WebP size for an original path,
     * or null if there is no recorded conversion.
     *
     * @param string $original_path Relative path under uploads.
     * @return int|null
     */
    private function lookup_webp_size_for( $original_path ) {
        $webp_history = get_option( 'mbrpe_webp_converted_images', array() );
        if ( ! is_array( $webp_history ) ) {
            return null;
        }
        foreach ( $webp_history as $entry ) {
            if ( isset( $entry['original_path'], $entry['webp_size'] )
                 && $entry['original_path'] === $original_path ) {
                return (int) $entry['webp_size'];
            }
        }
        return null;
    }

    /**
     * AJAX: Clear all AVIF conversion history (records only, files left alone).
     */
    public function ajax_avif_clear_history() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-performance' ) ) );
        }

        delete_option( 'mbrpe_avif_converted_images' );
        wp_send_json_success( array( 'message' => __( 'AVIF history cleared.', 'mbr-performance' ) ) );
    }

    /**
     * AJAX: Delete every .avif file this plugin created and clear history.
     */
    public function ajax_avif_revert_all() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-performance' ) ) );
        }

        $upload_dir    = wp_upload_dir();
        $registry      = get_option( 'mbrpe_avif_registry', array() );
        $deleted_count = 0;
        $failed_count  = 0;

        if ( ! empty( $registry ) && is_array( $registry ) ) {
            foreach ( $registry as $rel ) {
                $full_path = $upload_dir['basedir'] . '/' . $rel;
                if ( file_exists( $full_path ) ) {
                    if ( wp_delete_file( $full_path ) || ! file_exists( $full_path ) ) {
                        $deleted_count++;
                    } else {
                        $failed_count++;
                    }
                } else {
                    $deleted_count++;
                }
            }
        }

        delete_option( 'mbrpe_avif_registry' );
        delete_option( 'mbrpe_avif_converted_images' );

        if ( class_exists( 'MBRPE_AVIF_Converter' ) ) {
            MBRPE_AVIF_Converter::remove_htaccess_rules();
        }

        if ( $failed_count > 0 ) {
            wp_send_json_success(
                sprintf(
                    /* translators: 1: deleted count, 2: failed count */
                    __( 'Deleted %1$d AVIF file(s). %2$d file(s) could not be removed — check folder permissions.', 'mbr-performance' ),
                    $deleted_count,
                    $failed_count
                )
            );
        } else {
            wp_send_json_success(
                sprintf(
                    /* translators: %d: number of files deleted */
                    __( 'Successfully reverted %d AVIF file(s). Originals and WebP variants remain untouched.', 'mbr-performance' ),
                    $deleted_count
                )
            );
        }
    }

    /**
     * AJAX import a site's settings as network defaults.
     *
     * @since 1.5.0
     */
    public function ajax_import_site_settings() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_network_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-performance' ) ) );
        }

        $site_id = isset( $_POST['site_id'] ) ? absint( $_POST['site_id'] ) : 0;

        if ( ! $site_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid site ID.', 'mbr-performance' ) ) );
        }

        $site = get_site( $site_id );

        if ( ! $site ) {
            wp_send_json_error( array( 'message' => __( 'Site not found.', 'mbr-performance' ) ) );
        }

        switch_to_blog( $site_id );
        $site_options = get_option( 'mbrpe_options', array() );
        restore_current_blog();

        if ( empty( $site_options ) ) {
            wp_send_json_error( array(
                'message' => sprintf(
                    // translators: %s: site domain/path.
                    __( 'No MBR Performance settings found on %s.', 'mbr-performance' ),
                    $site->domain . $site->path
                ),
            ) );
        }

        update_site_option( 'mbrpe_network_options', $site_options );

        wp_send_json_success( array(
            'message' => sprintf(
                // translators: %s: site domain/path.
                __( 'Successfully imported settings from %s as network defaults.', 'mbr-performance' ),
                $site->domain . $site->path
            ),
        ) );
    }

    /* ======================================================================
     * Image Dimensions — Bulk Resize AJAX Handlers
     * ==================================================================== */

    /**
     * AJAX: Scan the media library for images exceeding the configured
     * maximum dimension. Returns the list of attachment IDs.
     */
    public function ajax_image_dimensions_scan() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Insufficient permissions.', 'mbr-performance' ) );
        }

        if ( ! class_exists( 'MBRPE_Image_Dimensions' ) ) {
            wp_send_json_error( __( 'Image Dimensions module unavailable.', 'mbr-performance' ) );
        }

        $options = mbrpe()->get_options();
        $dim     = ( is_array( $options ) && isset( $options['image_dimensions'] ) && is_array( $options['image_dimensions'] ) )
            ? $options['image_dimensions']
            : array();

        $max_dim = isset( $dim['max_dimension'] )
            ? absint( $dim['max_dimension'] )
            : MBRPE_Image_Dimensions::DEFAULT_MAX_DIMENSION;

        $ids = MBRPE_Image_Dimensions::get_resize_candidates( $max_dim );

        wp_send_json_success( array(
            'ids'     => array_values( array_map( 'intval', $ids ) ),
            'count'   => count( $ids ),
            'max_dim' => $max_dim,
        ) );
    }

    /**
     * AJAX: Resize a single attachment in place.
     */
    public function ajax_image_dimensions_resize() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Insufficient permissions.', 'mbr-performance' ) );
        }

        if ( ! class_exists( 'MBRPE_Image_Dimensions' ) ) {
            wp_send_json_error( __( 'Image Dimensions module unavailable.', 'mbr-performance' ) );
        }

        $attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;
        if ( ! $attachment_id ) {
            wp_send_json_error( __( 'Missing attachment ID.', 'mbr-performance' ) );
        }

        $options = mbrpe()->get_options();
        $dim     = ( is_array( $options ) && isset( $options['image_dimensions'] ) && is_array( $options['image_dimensions'] ) )
            ? $options['image_dimensions']
            : array();

        $max_dim = isset( $dim['max_dimension'] )
            ? absint( $dim['max_dimension'] )
            : MBRPE_Image_Dimensions::DEFAULT_MAX_DIMENSION;

        $result = MBRPE_Image_Dimensions::bulk_resize_attachment( $attachment_id, $max_dim );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array(
                'id'      => $attachment_id,
                'message' => $result->get_error_message(),
                'code'    => $result->get_error_code(),
            ) );
        }

        // Add formatted size strings for display.
        if ( isset( $result['original_size'] ) ) {
            $result['original_size_h'] = size_format( $result['original_size'], 2 );
        }
        if ( isset( $result['new_size'] ) ) {
            $result['new_size_h'] = size_format( $result['new_size'], 2 );
        }
        if ( isset( $result['saved_bytes'] ) ) {
            $result['saved_h'] = size_format( $result['saved_bytes'], 2 );
        }

        wp_send_json_success( $result );
    }

    /* ===================================================================
     * Orphaned Images: sanitize + AJAX handlers
     * =================================================================== */

    /**
     * Sanitize orphaned images options.
     *
     * @param array $options
     * @return array
     */
    private function sanitize_orphaned_images_options( $options ) {
        $sanitized = array();

        // restore_days: whitelist allowed values.
        $restore_days = isset( $options['restore_days'] ) ? (int) $options['restore_days'] : 30;
        $allowed_days = array( 0, 7, 14, 30, 60 );
        $sanitized['restore_days'] = in_array( $restore_days, $allowed_days, true ) ? $restore_days : 30;

        // enabled_types (since 1.11.0): array of media-type keys. Defaults to
        // images-only so v1.10.0 upgraders see no behavioural change. An empty
        // submission (all checkboxes off) also falls back to images-only — the
        // scanner cannot do anything useful with zero types enabled.
        $valid_types = array_keys( MBRPE_Orphaned_Images::media_type_map() );
        $raw_types   = isset( $options['enabled_types'] ) && is_array( $options['enabled_types'] )
            ? $options['enabled_types']
            : array();
        $clean_types = array_values( array_intersect( $raw_types, $valid_types ) );
        $sanitized['enabled_types'] = $clean_types ?: array( 'images' );

        // excluded_ids: comma-separated string from textarea, or array.
        $raw_excluded = isset( $options['excluded_ids'] ) ? $options['excluded_ids'] : array();
        if ( is_string( $raw_excluded ) ) {
            $raw_excluded = preg_split( '/[\s,]+/', $raw_excluded );
        }
        $excluded = array();
        foreach ( (array) $raw_excluded as $id ) {
            $id = (int) $id;
            if ( $id > 0 ) {
                $excluded[] = $id;
            }
        }
        $sanitized['excluded_ids'] = array_values( array_unique( $excluded ) );

        return $sanitized;
    }

    /**
     * AJAX: Initialize a new scan. Resets candidate state and returns
     * the full list of attachment IDs to process.
     */
    public function ajax_orphan_scan_init() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Insufficient permissions.', 'mbr-performance' ) );
        }

        if ( ! class_exists( 'MBRPE_Orphaned_Images' ) ) {
            wp_send_json_error( __( 'Orphaned Images module unavailable.', 'mbr-performance' ) );
        }

        MBRPE_Orphaned_Images::reset_scan();

        $ids = MBRPE_Orphaned_Images::get_all_attachment_ids();

        MBRPE_Orphaned_Images::update_scan_state( array(
            'total'       => count( $ids ),
            'processed'   => 0,
            'started_at'  => time(),
            'finished_at' => 0,
        ) );

        wp_send_json_success( array(
            'ids'        => array_values( array_map( 'intval', $ids ) ),
            'total'      => count( $ids ),
            'batch_size' => MBRPE_Orphaned_Images::SCAN_BATCH_SIZE,
        ) );
    }

    /**
     * AJAX: Process a single batch of attachment IDs.
     */
    public function ajax_orphan_scan_batch() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Insufficient permissions.', 'mbr-performance' ) );
        }

        if ( ! class_exists( 'MBRPE_Orphaned_Images' ) ) {
            wp_send_json_error( __( 'Orphaned Images module unavailable.', 'mbr-performance' ) );
        }

        $raw_ids = isset( $_POST['ids'] ) ? wp_unslash( $_POST['ids'] ) : array();
        if ( ! is_array( $raw_ids ) ) {
            $raw_ids = array();
        }
        $ids = array_filter( array_map( 'intval', $raw_ids ) );

        $found = MBRPE_Orphaned_Images::process_batch( $ids );

        $state = MBRPE_Orphaned_Images::get_scan_state();
        $state['processed'] = (int) $state['processed'] + count( $ids );
        if ( $state['processed'] >= $state['total'] ) {
            $state['finished_at'] = time();
        }
        MBRPE_Orphaned_Images::update_scan_state( $state );

        wp_send_json_success( array(
            'processed_in_batch' => count( $ids ),
            'orphans_in_batch'   => $found,
            'total_processed'    => $state['processed'],
            'total'              => $state['total'],
            'finished'           => ! empty( $state['finished_at'] ),
        ) );
    }

    /**
     * AJAX: Get current candidate list with filter & pagination.
     */
    public function ajax_orphan_get_candidates() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Insufficient permissions.', 'mbr-performance' ) );
        }

        $args = array(
            'confidence' => isset( $_POST['confidence'] ) ? sanitize_key( wp_unslash( $_POST['confidence'] ) ) : '',
            'media_type' => isset( $_POST['media_type'] ) ? sanitize_key( wp_unslash( $_POST['media_type'] ) ) : '',
            'per_page'   => isset( $_POST['per_page'] ) ? (int) $_POST['per_page'] : 25,
            'page'       => isset( $_POST['page'] ) ? (int) $_POST['page'] : 1,
            'orderby'    => isset( $_POST['orderby'] ) ? sanitize_key( wp_unslash( $_POST['orderby'] ) ) : 'file_size',
            'order'      => isset( $_POST['order'] ) ? sanitize_key( wp_unslash( $_POST['order'] ) ) : 'DESC',
        );

        $result = MBRPE_Orphaned_Images::get_candidates( $args );
        $stats  = MBRPE_Orphaned_Images::get_candidate_stats();

        // Decorate items with display-friendly fields and a thumb URL.
        foreach ( $result['items'] as &$row ) {
            $row['attachment_id'] = (int) $row['attachment_id'];
            $row['file_size']     = (int) $row['file_size'];
            $row['file_size_h']   = size_format( (int) $row['file_size'], 2 );
            $row['edit_link']     = admin_url( 'post.php?post=' . $row['attachment_id'] . '&action=edit' );
            $row['media_type']    = MBRPE_Orphaned_Images::categorise_mime( $row['mime_type'] );
            // Thumbnails only meaningful for image attachments — non-images
            // get a category icon rendered client-side instead.
            $row['thumb_url']     = ( 'images' === $row['media_type'] )
                ? wp_get_attachment_image_url( (int) $row['attachment_id'], 'thumbnail' )
                : '';
            $matches              = $row['match_summary'] ? json_decode( $row['match_summary'], true ) : array();
            $row['matches']       = is_array( $matches ) ? $matches : array();
        }
        unset( $row );

        wp_send_json_success( array(
            'items' => $result['items'],
            'total' => $result['total'],
            'stats' => $stats,
        ) );
    }

    /**
     * AJAX: Delete a single attachment (move to staging).
     */
    public function ajax_orphan_delete() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Insufficient permissions.', 'mbr-performance' ) );
        }

        $id = isset( $_POST['attachment_id'] ) ? (int) $_POST['attachment_id'] : 0;
        if ( $id <= 0 ) {
            wp_send_json_error( __( 'Missing attachment ID.', 'mbr-performance' ) );
        }

        $result = MBRPE_Orphaned_Images::stage_delete( $id );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array(
                'id'      => $id,
                'message' => $result->get_error_message(),
                'code'    => $result->get_error_code(),
            ) );
        }

        $result['bytes_freed_h'] = isset( $result['bytes_freed'] ) ? size_format( (int) $result['bytes_freed'], 2 ) : '';
        wp_send_json_success( $result );
    }

    /**
     * AJAX: Get staged-for-deletion list (for the restore UI).
     */
    public function ajax_orphan_get_staged() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Insufficient permissions.', 'mbr-performance' ) );
        }

        $per_page = isset( $_POST['per_page'] ) ? (int) $_POST['per_page'] : 25;
        $page     = isset( $_POST['page'] ) ? (int) $_POST['page'] : 1;

        $result = MBRPE_Orphaned_Images::get_staged( $per_page, $page );
        $stats  = MBRPE_Orphaned_Images::get_staged_stats();

        foreach ( $result['items'] as &$row ) {
            $row['id']            = (int) $row['id'];
            $row['attachment_id'] = (int) $row['attachment_id'];
            $row['file_size']     = (int) $row['file_size'];
            $row['file_size_h']   = size_format( (int) $row['file_size'], 2 );
        }
        unset( $row );

        wp_send_json_success( array(
            'items' => $result['items'],
            'total' => $result['total'],
            'stats' => $stats,
        ) );
    }

    /**
     * AJAX: Restore a staged-for-deletion attachment record.
     */
    public function ajax_orphan_restore() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Insufficient permissions.', 'mbr-performance' ) );
        }

        $row_id = isset( $_POST['row_id'] ) ? (int) $_POST['row_id'] : 0;
        if ( $row_id <= 0 ) {
            wp_send_json_error( __( 'Missing staging row ID.', 'mbr-performance' ) );
        }

        $result = MBRPE_Orphaned_Images::restore( $row_id );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array(
                'message' => $result->get_error_message(),
                'code'    => $result->get_error_code(),
            ) );
        }

        wp_send_json_success( $result );
    }

    /**
     * AJAX: Add an attachment to the exclusion list.
     */
    public function ajax_orphan_exclude() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Insufficient permissions.', 'mbr-performance' ) );
        }

        $id = isset( $_POST['attachment_id'] ) ? (int) $_POST['attachment_id'] : 0;
        if ( $id <= 0 ) {
            wp_send_json_error( __( 'Missing attachment ID.', 'mbr-performance' ) );
        }

        MBRPE_Orphaned_Images::exclude_attachment( $id );

        wp_send_json_success( array( 'attachment_id' => $id ) );
    }

    /* ======================================================================
     * v1.12.0 AJAX HANDLERS
     * ==================================================================== */

    /**
     * Top autoloaded options.
     */
    public function ajax_autoload_top() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Insufficient permissions.', 'mbr-performance' ) );
        }
        $limit = isset( $_POST['limit'] ) ? (int) $_POST['limit'] : 30;
        $rows  = MBRPE_Autoload_Audit::top_autoloaded( $limit );
        wp_send_json_success( array(
            'rows'        => $rows,
            'total_bytes' => MBRPE_Autoload_Audit::total_autoloaded_size(),
            'total_count' => MBRPE_Autoload_Audit::total_autoloaded_count(),
        ) );
    }

    /**
     * Toggle autoload on a single option.
     */
    public function ajax_autoload_toggle() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Insufficient permissions.', 'mbr-performance' ) );
        }
        $name     = isset( $_POST['option_name'] ) ? sanitize_text_field( wp_unslash( $_POST['option_name'] ) ) : '';
        $autoload = ! empty( $_POST['autoload'] );
        if ( '' === $name ) {
            wp_send_json_error( __( 'Missing option name.', 'mbr-performance' ) );
        }
        if ( MBRPE_Autoload_Audit::is_protected_option( $name ) ) {
            wp_send_json_error( __( 'That option is protected and cannot be modified.', 'mbr-performance' ) );
        }
        $ok = MBRPE_Autoload_Audit::set_autoload( $name, $autoload );
        if ( ! $ok ) {
            wp_send_json_error( __( 'Unable to update autoload flag.', 'mbr-performance' ) );
        }
        wp_send_json_success( array( 'option_name' => $name, 'autoload' => $autoload ) );
    }

    /**
     * Unschedule one cron event.
     */
    public function ajax_cron_unschedule() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Insufficient permissions.', 'mbr-performance' ) );
        }
        $hook      = isset( $_POST['hook'] ) ? sanitize_text_field( wp_unslash( $_POST['hook'] ) ) : '';
        $timestamp = isset( $_POST['timestamp'] ) ? (int) $_POST['timestamp'] : 0;
        $args_raw  = isset( $_POST['args'] ) ? json_decode( wp_unslash( $_POST['args'] ), true ) : array();
        $args      = is_array( $args_raw ) ? map_deep( $args_raw, 'sanitize_text_field' ) : array();
        if ( '' === $hook || $timestamp <= 0 ) {
            wp_send_json_error( __( 'Missing hook or timestamp.', 'mbr-performance' ) );
        }
        $ok = MBRPE_Cron_Viewer::unschedule( $hook, $timestamp, is_array( $args ) ? $args : array() );
        if ( ! $ok ) {
            wp_send_json_error( __( 'Could not unschedule that event.', 'mbr-performance' ) );
        }
        wp_send_json_success();
    }

    /**
     * Clear every scheduled instance of a hook.
     */
    public function ajax_cron_clear_hook() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Insufficient permissions.', 'mbr-performance' ) );
        }
        $hook = isset( $_POST['hook'] ) ? sanitize_text_field( wp_unslash( $_POST['hook'] ) ) : '';
        if ( '' === $hook ) {
            wp_send_json_error( __( 'Missing hook.', 'mbr-performance' ) );
        }
        $count = MBRPE_Cron_Viewer::clear_all_for_hook( $hook );
        wp_send_json_success( array( 'cleared' => $count ) );
    }

    /**
     * Run the database auto-cleanup on demand.
     */
    public function ajax_db_cleanup_run() {
        check_ajax_referer( 'mbrpe_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Insufficient permissions.', 'mbr-performance' ) );
        }
        MBRPE_Database_Optimizations::instance()->run_scheduled_cleanup();
        $log = get_option( 'mbrpe_db_last_cleanup', array() );
        wp_send_json_success( array( 'log' => $log ) );
    }
}
