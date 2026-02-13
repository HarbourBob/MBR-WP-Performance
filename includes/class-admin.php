<?php
/**
 * Admin functionality
 *
 * @package MBR_WP_Performance
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin class
 */
class MBR_WP_Performance_Admin {

    /**
     * Single instance
     *
     * @var MBR_WP_Performance_Admin
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
     * @return MBR_WP_Performance_Admin
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
        add_action( 'wp_ajax_mbr_wp_performance_save_settings', array( $this, 'ajax_save_settings' ) );
        add_action( 'wp_ajax_mbr_wp_performance_clean_revisions', array( $this, 'ajax_clean_revisions' ) );
        add_action( 'wp_ajax_mbr_wp_performance_scan_post_meta', array( $this, 'ajax_scan_post_meta' ) );
        add_action( 'wp_ajax_mbr_wp_performance_delete_post_meta', array( $this, 'ajax_delete_post_meta' ) );
        add_action( 'wp_ajax_mbr_wp_performance_scan_comment_meta', array( $this, 'ajax_scan_comment_meta' ) );
        add_action( 'wp_ajax_mbr_wp_performance_delete_comment_meta', array( $this, 'ajax_delete_comment_meta' ) );
        add_action( 'wp_ajax_mbr_wp_performance_scan_relationships', array( $this, 'ajax_scan_relationships' ) );
        add_action( 'wp_ajax_mbr_wp_performance_delete_relationships', array( $this, 'ajax_delete_relationships' ) );
        add_action( 'wp_ajax_mbr_wp_performance_scan_term_meta', array( $this, 'ajax_scan_term_meta' ) );
        add_action( 'wp_ajax_mbr_wp_performance_delete_term_meta', array( $this, 'ajax_delete_term_meta' ) );
        add_action( 'wp_ajax_mbr_wp_performance_transient_stats', array( $this, 'ajax_transient_stats' ) );
        add_action( 'wp_ajax_mbr_wp_performance_delete_expired_transients', array( $this, 'ajax_delete_expired_transients' ) );
        add_action( 'wp_ajax_mbr_wp_performance_delete_all_transients', array( $this, 'ajax_delete_all_transients' ) );
        add_action( 'wp_ajax_mbr_wp_performance_optimize_tables', array( $this, 'ajax_optimize_tables' ) );
        add_action( 'wp_ajax_mbr_wp_performance_convert_innodb', array( $this, 'ajax_convert_innodb' ) );
        add_action( 'wp_ajax_mbr_wp_performance_repair_tables', array( $this, 'ajax_repair_tables' ) );
        add_action( 'wp_ajax_mbr_wp_performance_db_info', array( $this, 'ajax_db_info' ) );
        add_action( 'wp_ajax_mbr_wp_performance_generate_critical_css', array( $this, 'ajax_generate_critical_css' ) );
        add_action( 'wp_ajax_mbr_wp_performance_scan_css', array( $this, 'ajax_scan_css' ) );
        add_action( 'wp_ajax_mbr_wp_performance_clear_scan_data', array( $this, 'ajax_clear_scan_data' ) );
        add_action( 'wp_ajax_mbr_wp_performance_download_fonts', array( $this, 'ajax_download_fonts' ) );
        add_action( 'wp_ajax_mbr_wp_performance_download_manual_fonts', array( $this, 'ajax_download_manual_fonts' ) );
        add_action( 'wp_ajax_mbr_wp_performance_clear_font_cache', array( $this, 'ajax_clear_font_cache' ) );
    }

    /**
     * Add toolbar menu
     */
    public function add_toolbar_menu( $wp_admin_bar ) {
        $wp_admin_bar->add_node( array(
            'id'    => 'mbr-wp-performance',
            'title' => '<span class="ab-icon dashicons-performance"></span><span class="ab-label">' . __( 'WP Performance', 'mbr-wp-performance' ) . '</span>',
            'href'  => admin_url( 'admin.php?page=mbr-wp-performance' ),
            'meta'  => array(
                'title' => __( 'WP Performance Settings', 'mbr-wp-performance' ),
            ),
        ) );
        
        // Add submenu items for each tab
        $tabs = array(
            'core' => __( 'Core Features', 'mbr-wp-performance' ),
            'javascript' => __( 'JavaScript', 'mbr-wp-performance' ),
            'css' => __( 'CSS', 'mbr-wp-performance' ),
            'fonts' => __( 'Fonts', 'mbr-wp-performance' ),
            'preloading' => __( 'Preloading', 'mbr-wp-performance' ),
            'lazy-loading' => __( 'Lazy Loading', 'mbr-wp-performance' ),
            'database' => __( 'Database', 'mbr-wp-performance' ),
        );
        
        foreach ( $tabs as $tab => $label ) {
            $wp_admin_bar->add_node( array(
                'parent' => 'mbr-wp-performance',
                'id'     => 'mbr-wp-performance-' . $tab,
                'title'  => $label,
                'href'   => admin_url( 'admin.php?page=mbr-wp-performance&tab=' . $tab ),
            ) );
        }
    }
    
    /**
     * Add hidden admin page (not in sidebar, only accessible via toolbar)
     */
    public function add_hidden_admin_page() {
        add_submenu_page(
            null, // No parent = hidden from sidebar menu
            __( 'WP Performance', 'mbr-wp-performance' ),
            __( 'WP Performance', 'mbr-wp-performance' ),
            'manage_options',
            'mbr-wp-performance',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'mbr_wp_performance_options',
            'mbr_wp_performance_options',
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
        $existing = get_option( 'mbr_wp_performance_options', array() );
        
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
            'disable_woocommerce_scripts',
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
            'disable_concatenation',
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
            'inline_critical_css',
            'async_css',
            'minify_css',
            'combine_css',
            'remove_unused_css',
            'remove_global_styles',
            'load_block_styles_conditionally',
            'remove_css_versions',
            'disable_elementor_fonts',
            'disable_woocommerce_css',
        );
        
        foreach ( $boolean_fields as $field ) {
            $sanitized[ $field ] = isset( $options[ $field ] ) ? (bool) $options[ $field ] : false;
        }
        
        // Textarea options
        $textarea_fields = array(
            'critical_css',
            'exclude_async',
            'exclude_optimization',
        );
        
        foreach ( $textarea_fields as $field ) {
            if ( isset( $options[ $field ] ) ) {
                $sanitized[ $field ] = sanitize_textarea_field( $options[ $field ] );
            }
        }
        
        // Select options
        if ( isset( $options['google_fonts_mode'] ) ) {
            $sanitized['google_fonts_mode'] = sanitize_text_field( $options['google_fonts_mode'] );
        }
        
        if ( isset( $options['font_display'] ) ) {
            $sanitized['font_display'] = sanitize_text_field( $options['font_display'] );
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
     * Enqueue admin assets
     *
     * @param string $hook
     */
    public function enqueue_admin_assets( $hook ) {
        // Check if we're on the WP Performance settings page
        // Changed from 'toplevel_page_mbr-wp-performance' to 'admin_page_mbr-wp-performance'
        // because we moved from a top-level menu to a hidden submenu page
        if ( strpos( $hook, 'mbr-wp-performance' ) === false ) {
            return;
        }
        
        // Enqueue styles
        wp_enqueue_style(
            'mbr-wp-performance-admin',
            MBR_WP_PERFORMANCE_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            MBR_WP_PERFORMANCE_VERSION
        );
        
        // Enqueue scripts - Using clean rebuilt version
        wp_enqueue_script(
            'mbr-wp-performance-admin',
            MBR_WP_PERFORMANCE_PLUGIN_URL . 'assets/js/admin-clean.js',
            array( 'jquery' ),
            MBR_WP_PERFORMANCE_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script(
            'mbr-wp-performance-admin',
            'mbrWpPerformance',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'mbr_wp_performance_nonce' ),
                'i18n' => array(
                    'saveSuccess' => __( 'Settings saved successfully.', 'mbr-wp-performance' ),
                    'saveError' => __( 'Error saving settings. Please try again.', 'mbr-wp-performance' ),
                    'confirmReset' => __( 'Are you sure you want to reset all settings to defaults?', 'mbr-wp-performance' ),
                ),
            )
        );
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Get current tab
        $this->current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'core';
        
        // Get options
        $options = mbr_wp_performance()->get_options();
        
        ?>
        <div class="wrap mbr-wp-performance-wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <?php settings_errors( 'mbr_wp_performance_options' ); ?>
            
            <?php $this->render_tabs(); ?>
            
            <form method="post" action="options.php" class="mbr-wp-performance-form">
                <?php
                settings_fields( 'mbr_wp_performance_options' );
                
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
                }
                ?>
                
                <div class="mbr-wp-performance-actions">
                    <?php submit_button( __( 'Save Changes', 'mbr-wp-performance' ), 'primary', 'submit', false ); ?>
                    <button type="button" class="button button-secondary mbr-wp-performance-reset">
                        <?php esc_html_e( 'Reset to Defaults', 'mbr-wp-performance' ); ?>
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
            'core' => __( 'Core Features', 'mbr-wp-performance' ),
            'javascript' => __( 'JavaScript', 'mbr-wp-performance' ),
            'css' => __( 'CSS', 'mbr-wp-performance' ),
            'fonts' => __( 'Fonts', 'mbr-wp-performance' ),
            'preloading' => __( 'Preloading', 'mbr-wp-performance' ),
            'lazy-loading' => __( 'Lazy Loading', 'mbr-wp-performance' ),
            'database' => __( 'Database', 'mbr-wp-performance' ),
        );
        
        echo '<h2 class="nav-tab-wrapper">';
        foreach ( $tabs as $tab => $label ) {
            $active = $this->current_tab === $tab ? ' nav-tab-active' : '';
            printf(
                '<a href="?page=mbr-wp-performance&tab=%s" class="nav-tab%s">%s</a>',
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
        require_once MBR_WP_PERFORMANCE_PLUGIN_DIR . 'includes/admin/tabs/core.php';
    }

    /**
     * Render JavaScript tab
     *
     * @param array $options
     */
    private function render_javascript_tab( $options ) {
        require_once MBR_WP_PERFORMANCE_PLUGIN_DIR . 'includes/admin/tabs/javascript.php';
    }

    /**
     * Render CSS tab
     *
     * @param array $options
     */
    private function render_css_tab( $options ) {
        require_once MBR_WP_PERFORMANCE_PLUGIN_DIR . 'includes/admin/tabs/css.php';
    }

    /**
     * Render fonts tab
     *
     * @param array $options
     */
    private function render_fonts_tab( $options ) {
        require_once MBR_WP_PERFORMANCE_PLUGIN_DIR . 'includes/admin/tabs/fonts.php';
    }

    /**
     * Render preloading tab
     *
     * @param array $options
     */
    private function render_preloading_tab( $options ) {
        require_once MBR_WP_PERFORMANCE_PLUGIN_DIR . 'includes/admin/tabs/preloading.php';
    }

    /**
     * Render lazy loading tab
     *
     * @param array $options
     */
    private function render_lazy_loading_tab( $options ) {
        require_once MBR_WP_PERFORMANCE_PLUGIN_DIR . 'includes/admin/tabs/lazy-loading.php';
    }

    /**
     * Render database tab
     *
     * @param array $options
     */
    private function render_database_tab( $options ) {
        require_once MBR_WP_PERFORMANCE_PLUGIN_DIR . 'includes/admin/tabs/database.php';
    }

    /**
     * AJAX save settings
     */
    public function ajax_save_settings() {
        check_ajax_referer( 'mbr_wp_performance_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-wp-performance' ) ) );
        }
        
        // Get posted data
        $options = isset( $_POST['options'] ) ? $_POST['options'] : array();
        
        // Sanitize and save
        $sanitized = $this->sanitize_options( $options );
        mbr_wp_performance()->update_options( $sanitized );
        
        wp_send_json_success( array( 'message' => __( 'Settings saved successfully.', 'mbr-wp-performance' ) ) );
    }
    
    /**
     * AJAX clean revisions
     */
    public function ajax_clean_revisions() {
        check_ajax_referer( 'mbr_wp_performance_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-wp-performance' ) ) );
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
        
        wp_send_json_success( array( 'message' => sprintf( __( 'Deleted %d excess revisions.', 'mbr-wp-performance' ), $deleted ) ) );
    }
    
    /**
     * AJAX scan post meta
     */
    public function ajax_scan_post_meta() {
        check_ajax_referer( 'mbr_wp_performance_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-wp-performance' ) ) );
        }
        
        global $wpdb;
        $count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id NOT IN (SELECT ID FROM {$wpdb->posts})" );
        
        wp_send_json_success( array( 'count' => $count ) );
    }
    
    /**
     * AJAX delete post meta
     */
    public function ajax_delete_post_meta() {
        check_ajax_referer( 'mbr_wp_performance_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-wp-performance' ) ) );
        }
        
        global $wpdb;
        $deleted = $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE post_id NOT IN (SELECT ID FROM {$wpdb->posts})" );
        
        wp_send_json_success( array( 'message' => sprintf( __( 'Deleted %d orphaned post meta entries.', 'mbr-wp-performance' ), $deleted ) ) );
    }
    
    /**
     * AJAX scan comment meta
     */
    public function ajax_scan_comment_meta() {
        check_ajax_referer( 'mbr_wp_performance_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-wp-performance' ) ) );
        }
        
        global $wpdb;
        $count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->commentmeta} WHERE comment_id NOT IN (SELECT comment_ID FROM {$wpdb->comments})" );
        
        wp_send_json_success( array( 'count' => $count ) );
    }
    
    /**
     * AJAX delete comment meta
     */
    public function ajax_delete_comment_meta() {
        check_ajax_referer( 'mbr_wp_performance_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-wp-performance' ) ) );
        }
        
        global $wpdb;
        $deleted = $wpdb->query( "DELETE FROM {$wpdb->commentmeta} WHERE comment_id NOT IN (SELECT comment_ID FROM {$wpdb->comments})" );
        
        wp_send_json_success( array( 'message' => sprintf( __( 'Deleted %d orphaned comment meta entries.', 'mbr-wp-performance' ), $deleted ) ) );
    }
    
    /**
     * AJAX scan relationships
     */
    public function ajax_scan_relationships() {
        check_ajax_referer( 'mbr_wp_performance_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-wp-performance' ) ) );
        }
        
        global $wpdb;
        $count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->term_relationships} WHERE object_id NOT IN (SELECT ID FROM {$wpdb->posts})" );
        
        wp_send_json_success( array( 'count' => $count ) );
    }
    
    /**
     * AJAX delete relationships
     */
    public function ajax_delete_relationships() {
        check_ajax_referer( 'mbr_wp_performance_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-wp-performance' ) ) );
        }
        
        global $wpdb;
        $deleted = $wpdb->query( "DELETE FROM {$wpdb->term_relationships} WHERE object_id NOT IN (SELECT ID FROM {$wpdb->posts})" );
        
        wp_send_json_success( array( 'message' => sprintf( __( 'Deleted %d orphaned relationships.', 'mbr-wp-performance' ), $deleted ) ) );
    }
    
    /**
     * AJAX scan term meta
     */
    public function ajax_scan_term_meta() {
        check_ajax_referer( 'mbr_wp_performance_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-wp-performance' ) ) );
        }
        
        global $wpdb;
        $count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->termmeta} WHERE term_id NOT IN (SELECT term_id FROM {$wpdb->terms})" );
        
        wp_send_json_success( array( 'count' => $count ) );
    }
    
    /**
     * AJAX delete term meta
     */
    public function ajax_delete_term_meta() {
        check_ajax_referer( 'mbr_wp_performance_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-wp-performance' ) ) );
        }
        
        global $wpdb;
        $deleted = $wpdb->query( "DELETE FROM {$wpdb->termmeta} WHERE term_id NOT IN (SELECT term_id FROM {$wpdb->terms})" );
        
        wp_send_json_success( array( 'message' => sprintf( __( 'Deleted %d orphaned term meta entries.', 'mbr-wp-performance' ), $deleted ) ) );
    }
    
    /**
     * AJAX get transient stats
     */
    public function ajax_transient_stats() {
        check_ajax_referer( 'mbr_wp_performance_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-wp-performance' ) ) );
        }
        
        global $wpdb;
        $total = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'" );
        $expired = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_%' AND option_value < UNIX_TIMESTAMP()" );
        
        wp_send_json_success( array( 
            'message' => sprintf( __( 'Total Transients: %d | Expired: %d', 'mbr-wp-performance' ), $total, $expired ) 
        ) );
    }
    
    /**
     * AJAX delete expired transients
     */
    public function ajax_delete_expired_transients() {
        check_ajax_referer( 'mbr_wp_performance_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-wp-performance' ) ) );
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
        
        wp_send_json_success( array( 'message' => sprintf( __( 'Deleted %d expired transients.', 'mbr-wp-performance' ), $deleted ) ) );
    }
    
    /**
     * AJAX delete all transients
     */
    public function ajax_delete_all_transients() {
        check_ajax_referer( 'mbr_wp_performance_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-wp-performance' ) ) );
        }
        
        global $wpdb;
        $deleted = $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'" );
        
        wp_send_json_success( array( 'message' => sprintf( __( 'Deleted %d transients.', 'mbr-wp-performance' ), $deleted ) ) );
    }
    
    /**
     * AJAX optimize tables
     */
    public function ajax_optimize_tables() {
        check_ajax_referer( 'mbr_wp_performance_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-wp-performance' ) ) );
        }
        
        global $wpdb;
        $tables = $wpdb->get_col( "SHOW TABLES" );
        $optimized = 0;
        
        foreach ( $tables as $table ) {
            $wpdb->query( "OPTIMIZE TABLE `{$table}`" );
            $optimized++;
        }
        
        wp_send_json_success( array( 'message' => sprintf( __( 'Optimized %d tables.', 'mbr-wp-performance' ), $optimized ) ) );
    }
    
    /**
     * AJAX convert to InnoDB
     */
    public function ajax_convert_innodb() {
        check_ajax_referer( 'mbr_wp_performance_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-wp-performance' ) ) );
        }
        
        global $wpdb;
        
        // Get all tables with their engine type
        $tables = $wpdb->get_results( "SHOW TABLE STATUS" );
        
        if ( empty( $tables ) ) {
            wp_send_json_error( array( 'message' => __( 'No tables found.', 'mbr-wp-performance' ) ) );
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
            wp_send_json_success( array( 'message' => __( 'No MyISAM tables found. All tables are already InnoDB.', 'mbr-wp-performance' ) ) );
        } elseif ( ! empty( $errors ) ) {
            wp_send_json_error( array( 
                'message' => sprintf( 
                    __( 'Converted %d tables. Errors: %s', 'mbr-wp-performance' ), 
                    $converted, 
                    implode( ', ', $errors ) 
                ) 
            ) );
        } else {
            wp_send_json_success( array( 'message' => sprintf( __( 'Successfully converted %d MyISAM tables to InnoDB.', 'mbr-wp-performance' ), $converted ) ) );
        }
    }
    
    /**
     * AJAX repair tables
     */
    public function ajax_repair_tables() {
        check_ajax_referer( 'mbr_wp_performance_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-wp-performance' ) ) );
        }
        
        global $wpdb;
        $tables = $wpdb->get_col( "SHOW TABLES" );
        $repaired = 0;
        
        foreach ( $tables as $table ) {
            $wpdb->query( "REPAIR TABLE `{$table}`" );
            $repaired++;
        }
        
        wp_send_json_success( array( 'message' => sprintf( __( 'Checked and repaired %d tables.', 'mbr-wp-performance' ), $repaired ) ) );
    }
    
    /**
     * AJAX get database info
     */
    public function ajax_db_info() {
        check_ajax_referer( 'mbr_wp_performance_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-wp-performance' ) ) );
        }
        
        global $wpdb;
        
        $size = $wpdb->get_var( "SELECT SUM(data_length + index_length) / 1024 / 1024 FROM information_schema.TABLES WHERE table_schema = '{$wpdb->dbname}'" );
        $tables = $wpdb->get_var( "SELECT COUNT(*) FROM information_schema.TABLES WHERE table_schema = '{$wpdb->dbname}'" );
        
        $html = '<ul>';
        $html .= '<li>' . sprintf( __( 'Database Size: %.2f MB', 'mbr-wp-performance' ), $size ) . '</li>';
        $html .= '<li>' . sprintf( __( 'Total Tables: %d', 'mbr-wp-performance' ), $tables ) . '</li>';
        $html .= '</ul>';
        
        wp_send_json_success( array( 'html' => $html ) );
    }
    
    /**
     * AJAX generate critical CSS
     */
    public function ajax_generate_critical_css() {
        check_ajax_referer( 'mbr_wp_performance_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-wp-performance' ) ) );
        }
        
        // Get homepage URL
        $home_url = home_url( '/' );
        
        // Fetch the homepage HTML
        $response = wp_remote_get( $home_url, array(
            'timeout' => 30,
            'sslverify' => false,
        ) );
        
        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => __( 'Failed to fetch homepage.', 'mbr-wp-performance' ) ) );
        }
        
        $html = wp_remote_retrieve_body( $response );
        
        // Extract all CSS file URLs from the HTML
        preg_match_all( '/<link[^>]+rel=["\']stylesheet["\'][^>]+href=["\'](https?:\/\/[^"\']+)["\']/', $html, $css_matches );
        preg_match_all( '/<link[^>]+href=["\'](https?:\/\/[^"\']+)["\'][^>]+rel=["\']stylesheet["\']/', $html, $css_matches2 );
        
        $css_urls = array_merge( $css_matches[1], $css_matches2[1] );
        $css_urls = array_unique( $css_urls );
        
        if ( empty( $css_urls ) ) {
            wp_send_json_error( array( 'message' => __( 'No CSS files found on homepage.', 'mbr-wp-performance' ) ) );
        }
        
        // Combine all CSS
        $all_css = '';
        foreach ( $css_urls as $css_url ) {
            $css_response = wp_remote_get( $css_url, array(
                'timeout' => 15,
                'sslverify' => false,
            ) );
            
            if ( ! is_wp_error( $css_response ) ) {
                $all_css .= wp_remote_retrieve_body( $css_response ) . "\n";
            }
        }
        
        // Extract critical CSS (above-the-fold selectors)
        // This is a simplified approach - gets CSS for common above-fold elements
        $critical_selectors = array(
            'body', 'html', 
            'header', '.header', '#header', '.site-header',
            'nav', '.nav', '.navigation', '.menu', '.main-navigation',
            '.hero', '.banner', '#banner',
            'h1', 'h2', 'h3',
            'p', 'a',
            '.logo', '#logo',
            '.container', '.wrapper',
            '#content', '.content', '.main-content',
        );
        
        $critical_css = $this->extract_css_for_selectors( $all_css, $critical_selectors );
        
        // Minify the critical CSS
        $critical_css = $this->minify_css( $critical_css );
        
        // Store in options
        $options = get_option( 'mbr_wp_performance_options', array() );
        if ( ! isset( $options['css'] ) ) {
            $options['css'] = array();
        }
        $options['css']['critical_css_content'] = $critical_css;
        update_option( 'mbr_wp_performance_options', $options );
        
        wp_send_json_success( array( 
            'css' => $critical_css,
            'message' => sprintf( 
                __( 'Generated %d bytes of critical CSS from %d stylesheets.', 'mbr-wp-performance' ),
                strlen( $critical_css ),
                count( $css_urls )
            )
        ) );
    }
    
    /**
     * Extract CSS rules for specific selectors
     */
    private function extract_css_for_selectors( $css, $selectors ) {
        $critical_css = '';
        
        // Remove comments
        $css = preg_replace( '/\/\*.*?\*\//s', '', $css );
        
        // Extract @import and @font-face rules (always critical)
        preg_match_all( '/@import[^;]+;/i', $css, $imports );
        preg_match_all( '/@font-face\s*\{[^}]+\}/si', $css, $font_faces );
        
        $critical_css .= implode( "\n", $imports[0] );
        $critical_css .= implode( "\n", $font_faces[0] );
        
        // Extract rules for each selector
        foreach ( $selectors as $selector ) {
            $pattern = '/' . preg_quote( $selector, '/' ) . '[^{]*\{[^}]+\}/i';
            preg_match_all( $pattern, $css, $matches );
            if ( ! empty( $matches[0] ) ) {
                $critical_css .= implode( "\n", $matches[0] ) . "\n";
            }
        }
        
        return $critical_css;
    }
    
    /**
     * Minify CSS
     */
    private function minify_css( $css ) {
        // Remove comments
        $css = preg_replace( '/\/\*.*?\*\//s', '', $css );
        
        // Remove whitespace
        $css = preg_replace( '/\s+/', ' ', $css );
        
        // Remove spaces around special characters
        $css = preg_replace( '/\s*([{}:;,])\s*/', '$1', $css );
        
        return trim( $css );
    }
    
    /**
     * AJAX scan CSS
     */
    public function ajax_scan_css() {
        check_ajax_referer( 'mbr_wp_performance_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-wp-performance' ) ) );
        }
        
        // Get homepage URL
        $home_url = home_url( '/' );
        
        // Fetch the homepage HTML
        $response = wp_remote_get( $home_url, array(
            'timeout' => 30,
            'sslverify' => false,
        ) );
        
        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => __( 'Failed to fetch homepage.', 'mbr-wp-performance' ) ) );
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
        
        update_option( 'mbr_wp_performance_css_scan', $scan_data );
        
        // Build HTML report
        $html_report = '<div class="mbr-scan-results">';
        $html_report .= '<h3>' . __( 'CSS Scan Results', 'mbr-wp-performance' ) . '</h3>';
        $html_report .= '<p><strong>' . sprintf( __( 'Total CSS Size: %s', 'mbr-wp-performance' ), size_format( $total_css_size ) ) . '</strong></p>';
        $html_report .= '<p>' . sprintf( __( 'Total Rules: %d', 'mbr-wp-performance' ), $total_rules ) . '</p>';
        $html_report .= '<p>' . sprintf( __( 'Used Selectors: %d', 'mbr-wp-performance' ), $used_selectors ) . '</p>';
        $html_report .= '<p style="color: #d63638;">' . sprintf( __( 'Potentially Unused: %d', 'mbr-wp-performance' ), $unused_selectors ) . '</p>';
        
        if ( $total_rules > 0 ) {
            $overall_usage = round( ( $used_selectors / $total_rules ) * 100 );
            $html_report .= '<p><strong>' . sprintf( __( 'Overall Usage: %d%%', 'mbr-wp-performance' ), $overall_usage ) . '</strong></p>';
        }
        
        $html_report .= '<h4>' . __( 'Files:', 'mbr-wp-performance' ) . '</h4>';
        $html_report .= '<table class="widefat striped"><thead><tr>';
        $html_report .= '<th>' . __( 'File', 'mbr-wp-performance' ) . '</th>';
        $html_report .= '<th>' . __( 'Size', 'mbr-wp-performance' ) . '</th>';
        $html_report .= '<th>' . __( 'Rules', 'mbr-wp-performance' ) . '</th>';
        $html_report .= '<th>' . __( 'Usage', 'mbr-wp-performance' ) . '</th>';
        $html_report .= '</tr></thead><tbody>';
        
        foreach ( $css_files_info as $file ) {
            $filename = basename( parse_url( $file['url'], PHP_URL_PATH ) );
            $color = $file['usage_percent'] < 30 ? '#d63638' : ( $file['usage_percent'] < 60 ? '#dba617' : '#00a32a' );
            
            $html_report .= '<tr>';
            $html_report .= '<td title="' . esc_attr( $file['url'] ) . '">' . esc_html( $filename ) . '</td>';
            $html_report .= '<td>' . size_format( $file['size'] ) . '</td>';
            $html_report .= '<td>' . $file['rules'] . '</td>';
            $html_report .= '<td style="color: ' . $color . '; font-weight: bold;">' . $file['usage_percent'] . '%</td>';
            $html_report .= '</tr>';
        }
        
        $html_report .= '</tbody></table>';
        $html_report .= '<p class="description" style="margin-top: 15px;">' . __( 'Note: This scan only checks the homepage. Some CSS may be used on other pages.', 'mbr-wp-performance' ) . '</p>';
        $html_report .= '</div>';
        
        wp_send_json_success( array( 
            'html' => $html_report,
            'message' => sprintf( 
                __( 'Scanned %d CSS files totaling %s', 'mbr-wp-performance' ),
                count( $css_urls ),
                size_format( $total_css_size )
            )
        ) );
    }
    
    /**
     * AJAX clear scan data
     */
    public function ajax_clear_scan_data() {
        check_ajax_referer( 'mbr_wp_performance_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-wp-performance' ) ) );
        }
        
        delete_option( 'mbr_wp_performance_css_scan' );
        
        wp_send_json_success( array( 'message' => __( 'Scan data cleared.', 'mbr-wp-performance' ) ) );
    }
    
    /**
     * AJAX download fonts
     */
    public function ajax_download_fonts() {
        check_ajax_referer( 'mbr_wp_performance_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-wp-performance' ) ) );
        }
        
        // Get detected Google Fonts
        $fonts = $this->detect_google_fonts();
        
        if ( empty( $fonts ) ) {
            wp_send_json_error( array( 'message' => __( 'No Google Fonts detected on your site. Try using the manual input option below.', 'mbr-wp-performance' ) ) );
        }
        
        $result = $this->download_fonts_to_local( $fonts );
        
        wp_send_json_success( array( 'message' => $result['message'] ) );
    }
    
    /**
     * AJAX download manual fonts
     */
    public function ajax_download_manual_fonts() {
        check_ajax_referer( 'mbr_wp_performance_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-wp-performance' ) ) );
        }
        
        $manual_fonts = isset( $_POST['manual_fonts'] ) ? sanitize_textarea_field( $_POST['manual_fonts'] ) : '';
        
        if ( empty( $manual_fonts ) ) {
            wp_send_json_error( array( 'message' => __( 'Please enter fonts to download.', 'mbr-wp-performance' ) ) );
        }
        
        // Parse manual fonts
        $fonts = $this->parse_manual_fonts( $manual_fonts );
        
        if ( empty( $fonts ) ) {
            wp_send_json_error( array( 'message' => __( 'Could not parse font input. Format: FontFamily:400,700', 'mbr-wp-performance' ) ) );
        }
        
        $result = $this->download_fonts_to_local( $fonts );
        
        wp_send_json_success( array( 'message' => $result['message'] ) );
    }
    
    /**
     * AJAX clear font cache
     */
    public function ajax_clear_font_cache() {
        check_ajax_referer( 'mbr_wp_performance_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-wp-performance' ) ) );
        }
        
        // Get fonts directory
        $upload_dir = wp_upload_dir();
        $fonts_dir = $upload_dir['basedir'] . '/mbr-wp-performance-fonts';
        
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
                    if ( unlink( $filepath ) ) {
                        $deleted_files++;
                    }
                }
            }
            
            // Remove the directory itself
            @rmdir( $fonts_dir );
        }
        
        // Clear the font configuration
        delete_option( 'mbr_wp_performance_local_fonts' );
        delete_option( 'mbr_wp_performance_fonts_dir' );
        
        if ( $deleted_files > 0 ) {
            $message = sprintf( 
                __( 'Successfully cleared font cache: Deleted %d font files and reset configuration.', 'mbr-wp-performance' ),
                $deleted_files
            );
        } else {
            $message = __( 'Font cache cleared. No font files found to delete.', 'mbr-wp-performance' );
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
        $fonts_dir = $upload_dir['basedir'] . '/mbr-wp-performance-fonts';
        
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
        update_option( 'mbr_wp_performance_local_fonts', $fonts );
        update_option( 'mbr_wp_performance_fonts_dir', $fonts_dir );
        
        $message = sprintf( 
            __( 'Downloaded %d font variants. Failed: %d', 'mbr-wp-performance' ), 
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
                unlink( $filepath );
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
        $local_url = $upload_dir['baseurl'] . '/mbr-wp-performance-fonts/' . $filename;
        
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
}
