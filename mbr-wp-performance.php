<?php
/**
 * Plugin Name: MBR WP Performance
 * Plugin URI: https://littlewebshack.com/mbr-wp-performance
 * Description: Comprehensive WordPress performance optimization plugin with controls for core features, JavaScript, CSS, fonts, lazy loading, preloading, database optimization, and WebP image conversion.
 * Version: 1.6.0
 * Author: Made by Robert
 * Author URI: https://madebyrobert.co.uk
 * Text Domain: mbr-wp-performance
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
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
        esc_attr( sprintf( __( 'Buy %s a coffee', 'mbr-wp-performance' ), isset( $data['AuthorName'] ) ? $data['AuthorName'] : __( 'the author', 'mbr-wp-performance' ) ) ),
        esc_html__( 'Buy me a coffee', 'mbr-wp-performance' )
    );

    return $links;
}, 10, 3 );

// Define plugin constants
define( 'MBR_WP_PERFORMANCE_VERSION', '1.6.0' );
define( 'MBR_WP_PERFORMANCE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MBR_WP_PERFORMANCE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MBR_WP_PERFORMANCE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class
 */
class MBR_WP_Performance {

    /**
     * Single instance of the class
     *
     * @var MBR_WP_Performance
     */
    private static $instance = null;

    /**
     * Plugin options
     *
     * @var array
     */
    private $options = array();

    /**
     * Get single instance
     *
     * @return MBR_WP_Performance
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
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load required files
     */
    private function load_dependencies() {
        // Admin functionality
        require_once MBR_WP_PERFORMANCE_PLUGIN_DIR . 'includes/class-admin.php';
        
        // Core optimizations
        require_once MBR_WP_PERFORMANCE_PLUGIN_DIR . 'includes/class-core-optimizations.php';
        
        // JavaScript optimizations
        require_once MBR_WP_PERFORMANCE_PLUGIN_DIR . 'includes/class-javascript-optimizations.php';
        
        // CSS optimizations
        require_once MBR_WP_PERFORMANCE_PLUGIN_DIR . 'includes/class-css-optimizations.php';
        
        // Font optimizations
        require_once MBR_WP_PERFORMANCE_PLUGIN_DIR . 'includes/class-font-optimizations.php';
        
        // Database optimizations
        require_once MBR_WP_PERFORMANCE_PLUGIN_DIR . 'includes/class-database-optimizations.php';
        
        // WebP Converter
        require_once MBR_WP_PERFORMANCE_PLUGIN_DIR . 'includes/class-webp-converter.php';
        
        // Helper functions
        require_once MBR_WP_PERFORMANCE_PLUGIN_DIR . 'includes/functions.php';
        
        // Multisite support
        require_once MBR_WP_PERFORMANCE_PLUGIN_DIR . 'includes/class-multisite.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Load plugin text domain
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
        
        // Initialize optimization modules on init hook
        // Priority 999 to run after most plugins, including page builders
        add_action( 'init', array( $this, 'init_optimizations' ), 999 );
        
        // Initialize admin
        if ( is_admin() ) {
            MBR_WP_Performance_Admin::instance();
        }
        
        // Multisite: initialise network admin functionality
        if ( is_multisite() ) {
            MBR_WP_Performance_Multisite::instance();

            // Activate plugin on newly-created sites (WP 5.1+).
            add_action( 'wp_initialize_site', array( 'MBR_WP_Performance_Multisite', 'on_new_site' ), 900 );
        }
        
        // Activation/deactivation hooks
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
    }

    /**
     * Initialize optimization modules
     */
    public function init_optimizations() {
        // Don't run optimizations in Elementor editor or preview mode
        if ( $this->is_elementor_editor() ) {
            return;
        }
        
        // Don't run optimizations in any page builder editor
        if ( $this->is_page_builder_editor() ) {
            return;
        }
        
        MBR_WP_Performance_Core_Optimizations::instance();
        MBR_WP_Performance_JavaScript_Optimizations::instance();
        MBR_WP_Performance_CSS_Optimizations::instance();
        MBR_WP_Performance_Font_Optimizations::instance();
        MBR_WP_Performance_Database_Optimizations::instance();
        MBR_WP_Performance_WebP_Converter::instance();
    }
    
    /**
     * Check if we're in Elementor editor mode
     */
    private function is_elementor_editor() {
        // Check if Elementor is in edit mode
        if ( ! empty( $_GET['elementor-preview'] ) ) {
            return true;
        }
        
        // Check if in Elementor editor
        if ( ! empty( $_GET['action'] ) && $_GET['action'] === 'elementor' ) {
            return true;
        }
        
        // Check using Elementor's own function if available
        if ( defined( 'ELEMENTOR_VERSION' ) && class_exists( '\Elementor\Plugin' ) ) {
            if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
                return true;
            }
            if ( \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if we're in any page builder editor
     */
    private function is_page_builder_editor() {
        // Beaver Builder
        if ( isset( $_GET['fl_builder'] ) ) {
            return true;
        }
        
        // Divi Builder
        if ( function_exists( 'et_core_is_fb_enabled' ) && et_core_is_fb_enabled() ) {
            return true;
        }
        
        // Oxygen Builder
        if ( defined( 'SHOW_CT_BUILDER' ) ) {
            return true;
        }
        
        // Bricks Builder
        if ( ! empty( $_GET['bricks'] ) && $_GET['bricks'] === 'run' ) {
            return true;
        }
        
        // WPBakery Page Builder
        if ( function_exists( 'vc_is_inline' ) && vc_is_inline() ) {
            return true;
        }
        
        return false;
    }

    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'mbr-wp-performance',
            false,
            dirname( MBR_WP_PERFORMANCE_PLUGIN_BASENAME ) . '/languages'
        );
    }

    /**
     * Plugin activation
     *
     * @param bool $network_wide Whether the plugin is being activated network-wide.
     */
    public function activate( $network_wide = false ) {
        // Network-wide activation on multisite
        if ( is_multisite() && $network_wide ) {
            MBR_WP_Performance_Multisite::network_activate();
            return;
        }

        // Single-site (or per-site on multisite) activation
        $installed_version = get_option( 'mbr_wp_performance_version', false );
        
        if ( false === $installed_version ) {
            // First time installation
            $default_options = array(
                'core' => array(),
                'javascript' => array(),
                'css' => array(),
                'fonts' => array(),
                'database' => array(),
                'webp' => array(),
            );
            
            add_option( 'mbr_wp_performance_options', $default_options );
            update_option( 'mbr_wp_performance_version', MBR_WP_PERFORMANCE_VERSION );
        } else {
            // Update - just update version number, preserve settings
            update_option( 'mbr_wp_performance_version', MBR_WP_PERFORMANCE_VERSION );
        }
        
        // Schedule database cleanup if needed
        if ( ! wp_next_scheduled( 'mbr_wp_performance_database_cleanup' ) ) {
            wp_schedule_event( time(), 'weekly', 'mbr_wp_performance_database_cleanup' );
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Write WebP .htaccess rules if enabled.
        $existing_opts = get_option( 'mbr_wp_performance_options', array() );
        if ( ! empty( $existing_opts['webp']['htaccess_rules'] ) ) {
            MBR_WP_Performance_WebP_Converter::add_htaccess_rules();
        }
    }

    /**
     * Plugin deactivation
     *
     * @param bool $network_wide Whether the plugin is being deactivated network-wide.
     */
    public function deactivate( $network_wide = false ) {
        // Network-wide deactivation on multisite
        if ( is_multisite() && $network_wide ) {
            MBR_WP_Performance_Multisite::network_deactivate();
            return;
        }

        // Clear scheduled events
        wp_clear_scheduled_hook( 'mbr_wp_performance_database_cleanup' );
        
        // Clean up WebP files and .htaccess rules.
        MBR_WP_Performance_WebP_Converter::cleanup_on_deactivation();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Get plugin options
     *
     * On multisite, respects network defaults and per-site overrides.
     *
     * @param string $section Optional section to retrieve.
     * @return array|mixed
     */
    public function get_options( $section = '' ) {
        if ( empty( $this->options ) ) {
            if ( is_multisite() && class_exists( 'MBR_WP_Performance_Multisite' ) ) {
                $this->options = MBR_WP_Performance_Multisite::get_effective_options();
            } else {
                $this->options = get_option( 'mbr_wp_performance_options', array() );
            }
        }
        
        if ( ! empty( $section ) && isset( $this->options[ $section ] ) ) {
            return $this->options[ $section ];
        }
        
        return $this->options;
    }

    /**
     * Update plugin options
     *
     * On multisite, also marks the site as having its own overrides.
     *
     * @param array $options Options to save.
     * @return bool
     */
    public function update_options( $options ) {
        $this->options = $options;

        // When a site admin saves settings on multisite, mark the site
        // as no longer using network defaults (it now has its own).
        if ( is_multisite() && ! is_network_admin() ) {
            update_option( 'mbr_wp_performance_using_network_defaults', false );
        }

        return update_option( 'mbr_wp_performance_options', $options );
    }
}

/**
 * Get main plugin instance
 *
 * @return MBR_WP_Performance
 */
function mbr_wp_performance() {
    return MBR_WP_Performance::instance();
}

// Initialize plugin
mbr_wp_performance();
