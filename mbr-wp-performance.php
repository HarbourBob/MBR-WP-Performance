<?php
/**
 * Plugin Name: MBR WP Performance
 * Plugin URI: https://littlewebshack.com/mbr-wp-performance
 * Description: Comprehensive WordPress performance optimization plugin with controls for core features, JavaScript, CSS, fonts, lazy loading, preloading, database optimization, WebP image conversion, automatic image sizing, orphaned media cleanup, and WooCommerce optimisations.
 * Version: 1.13.3
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
define( 'MBR_WP_PERFORMANCE_VERSION', '1.13.3' );
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

        // WooCommerce Optimizations
        require_once MBR_WP_PERFORMANCE_PLUGIN_DIR . 'includes/class-woocommerce-optimizations.php';

        // Image Dimensions & Sizing
        require_once MBR_WP_PERFORMANCE_PLUGIN_DIR . 'includes/class-image-dimensions.php';

        // Orphaned Images cleanup
        require_once MBR_WP_PERFORMANCE_PLUGIN_DIR . 'includes/class-orphaned-images.php';
        
        // Helper functions
        require_once MBR_WP_PERFORMANCE_PLUGIN_DIR . 'includes/functions.php';
        
        // Multisite support
        require_once MBR_WP_PERFORMANCE_PLUGIN_DIR . 'includes/class-multisite.php';

        // ====================================================================
        // v1.12.0 additions
        // ====================================================================

        // AVIF converter (sister to WebP).
        require_once MBR_WP_PERFORMANCE_PLUGIN_DIR . 'includes/class-avif-converter.php';

        // Self-hosted third-party scripts (gtag.js, gtm.js, fbevents.js).
        require_once MBR_WP_PERFORMANCE_PLUGIN_DIR . 'includes/class-third-party-scripts.php';

        // YouTube / Vimeo facade.
        require_once MBR_WP_PERFORMANCE_PLUGIN_DIR . 'includes/class-video-facade.php';

        // Browser-cache and compression .htaccess rules.
        require_once MBR_WP_PERFORMANCE_PLUGIN_DIR . 'includes/class-server-headers.php';

        // Autoloaded options audit.
        require_once MBR_WP_PERFORMANCE_PLUGIN_DIR . 'includes/class-autoload-audit.php';

        // WP-Cron viewer.
        require_once MBR_WP_PERFORMANCE_PLUGIN_DIR . 'includes/class-cron-viewer.php';

        // HTML minification.
        require_once MBR_WP_PERFORMANCE_PLUGIN_DIR . 'includes/class-html-minify.php';

        // Image enhancements (decoding="async" + EXIF strip).
        require_once MBR_WP_PERFORMANCE_PLUGIN_DIR . 'includes/class-image-enhancements.php';

        // Hover prefetch (instant.page).
        require_once MBR_WP_PERFORMANCE_PLUGIN_DIR . 'includes/class-hover-prefetch.php';

        // Caching plugin conflict detector.
        require_once MBR_WP_PERFORMANCE_PLUGIN_DIR . 'includes/class-conflict-detector.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Load plugin text domain
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

        // Run the upgrade routine on plugins_loaded so it fires reliably on
        // plugin updates, not just on manual activation. The routine itself
        // is a fast no-op when nothing needs migrating.
        add_action( 'plugins_loaded', array( $this, 'maybe_upgrade' ) );

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
     * Run version-keyed upgrade migrations.
     *
     * Compares the stored plugin version to MBR_WP_PERFORMANCE_VERSION and
     * runs any one-time migration steps for versions the user has just
     * crossed. Each step is idempotent so re-running is safe.
     */
    public function maybe_upgrade() {
        $stored = get_option( 'mbr_wp_performance_version', '0.0.0' );

        // Same version → nothing to do. Cheapest fast path.
        if ( version_compare( $stored, MBR_WP_PERFORMANCE_VERSION, '>=' ) ) {
            return;
        }

        // --- Migrations from < 1.9.2 ---
        // The CSS tab previously had a non-functional "Remove Global Styles"
        // checkbox that wrote to [css][remove_global_styles]. The working
        // toggle on the Core tab writes to [core][remove_global_styles].
        // To avoid silently losing the user's intent on update, copy any
        // truthy value from the orphaned CSS-tab key over to the Core tab,
        // then strip the orphan.
        if ( version_compare( $stored, '1.9.2', '<' ) ) {
            $opts = get_option( 'mbr_wp_performance_options', array() );
            if ( is_array( $opts ) && ! empty( $opts['css']['remove_global_styles'] ) ) {
                if ( ! isset( $opts['core'] ) || ! is_array( $opts['core'] ) ) {
                    $opts['core'] = array();
                }
                // Only overwrite if the Core-tab value isn't already set, so
                // a deliberate Core-tab false doesn't get clobbered by an
                // orphan CSS-tab true.
                if ( empty( $opts['core']['remove_global_styles'] ) ) {
                    $opts['core']['remove_global_styles'] = true;
                }
                unset( $opts['css']['remove_global_styles'] );
                update_option( 'mbr_wp_performance_options', $opts );
            } elseif ( is_array( $opts ) && isset( $opts['css']['remove_global_styles'] ) ) {
                // The key existed but was falsy — just clean it up.
                unset( $opts['css']['remove_global_styles'] );
                update_option( 'mbr_wp_performance_options', $opts );
            }
        }

        // --- Migrations from < 1.10.0 ---
        // The Orphaned Images feature ships in 1.10.0. Create its staging
        // table on first encounter with this version, and ensure the daily
        // purge cron is scheduled. Both operations are idempotent.
        if ( version_compare( $stored, '1.10.0', '<' ) ) {
            if ( class_exists( 'MBR_WP_Performance_Orphaned_Images' ) ) {
                MBR_WP_Performance_Orphaned_Images::create_table();
            }
            if ( ! wp_next_scheduled( 'mbr_wp_performance_orphan_purge' ) ) {
                wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'mbr_wp_performance_orphan_purge' );
            }

            // Seed the orphaned_images options section if missing so the tab
            // doesn't blow up reading an undefined index.
            $opts = get_option( 'mbr_wp_performance_options', array() );
            if ( is_array( $opts ) && ! isset( $opts['orphaned_images'] ) ) {
                $opts['orphaned_images'] = array(
                    'restore_days'  => MBR_WP_Performance_Orphaned_Images::DEFAULT_RESTORE_DAYS,
                    'enabled_types' => array( 'images' ),
                    'excluded_ids'  => array(),
                );
                update_option( 'mbr_wp_performance_options', $opts );
            }
        }

        // --- Migrations from < 1.11.0 ---
        // The Orphaned Media feature replaced the v1.10.0 Orphaned Images tab.
        // Seed enabled_types for sites that had v1.10.0 settings so behaviour
        // remains identical (images-only) until the user opts in to other types.
        if ( version_compare( $stored, '1.11.0', '<' ) ) {
            $opts = get_option( 'mbr_wp_performance_options', array() );
            if ( is_array( $opts ) ) {
                if ( ! isset( $opts['orphaned_images'] ) || ! is_array( $opts['orphaned_images'] ) ) {
                    $opts['orphaned_images'] = array();
                }
                if ( ! isset( $opts['orphaned_images']['enabled_types'] )
                     || ! is_array( $opts['orphaned_images']['enabled_types'] ) ) {
                    $opts['orphaned_images']['enabled_types'] = array( 'images' );
                    update_option( 'mbr_wp_performance_options', $opts );
                }
            }
        }

        // --- Migrations from < 1.12.0 ---
        // v1.12.0 introduces three new option sections (preloading, lazy_loading,
        // third_party, server_headers). Ensure they exist so admin tabs reading
        // them with $options['key'] don't trip undefined-index warnings.
        if ( version_compare( $stored, '1.12.0', '<' ) ) {
            $opts = get_option( 'mbr_wp_performance_options', array() );
            if ( ! is_array( $opts ) ) {
                $opts = array();
            }
            foreach ( array( 'preloading', 'lazy_loading', 'third_party', 'server_headers' ) as $section ) {
                if ( ! isset( $opts[ $section ] ) || ! is_array( $opts[ $section ] ) ) {
                    $opts[ $section ] = array();
                }
            }
            update_option( 'mbr_wp_performance_options', $opts );
        }

        // Stamp the version once all migrations have completed.
        update_option( 'mbr_wp_performance_version', MBR_WP_PERFORMANCE_VERSION );
    }

    /**
     * Initialize optimization modules
     */
    public function init_optimizations() {
        // Upload-pipeline modules instantiate REGARDLESS of editor context.
        // When the Media Library is opened from inside a page builder editor
        // (Elementor's image picker, Bricks' media controls, etc.), uploads
        // route through standard WordPress filters — and we need to be
        // registered on those filters whether or not the request happens to
        // originate from an editor context.
        //
        // Each of these modules:
        //   * registers its upload-time hook (wp_handle_upload /
        //     wp_generate_attachment_metadata / big_image_size_threshold)
        //     unconditionally — these don't affect editor rendering.
        //   * gates any front-end / editor-sensitive filters internally,
        //     either at registration time (e.g. ! is_admin()) or via a
        //     skip-context check inside the callback.
        //
        // Before v1.13.1 these all sat below the early-return below, which
        // meant Elementor-side uploads silently bypassed WebP / AVIF
        // conversion, EXIF stripping, and resize-on-upload.
        MBR_WP_Performance_WebP_Converter::instance();
        MBR_WP_Performance_AVIF_Converter::instance();
        MBR_WP_Performance_Image_Dimensions::instance();
        MBR_WP_Performance_Image_Enhancements::instance();

        // Front-end optimisations are skipped inside page builder editors
        // and previews so they can't interfere with the editing experience.
        if ( $this->is_elementor_editor() ) {
            return;
        }
        if ( $this->is_page_builder_editor() ) {
            return;
        }

        MBR_WP_Performance_Core_Optimizations::instance();
        MBR_WP_Performance_JavaScript_Optimizations::instance();
        MBR_WP_Performance_CSS_Optimizations::instance();
        MBR_WP_Performance_Font_Optimizations::instance();
        MBR_WP_Performance_Database_Optimizations::instance();
        MBR_WP_Performance_WooCommerce_Optimizations::instance();
        MBR_WP_Performance_Orphaned_Images::instance();

        // v1.12.0 modules (non-upload).
        MBR_WP_Performance_Third_Party_Scripts::instance();
        MBR_WP_Performance_Video_Facade::instance();
        MBR_WP_Performance_Server_Headers::instance();
        MBR_WP_Performance_HTML_Minify::instance();
        MBR_WP_Performance_Hover_Prefetch::instance();
        MBR_WP_Performance_Conflict_Detector::instance();
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
     * The default options structure used on first install and by the
     * "Reset to Defaults" action.
     *
     * Every section the admin tabs read is seeded so templates never trip an
     * undefined-index notice, and so a reset returns the plugin to a known
     * clean state.
     *
     * @return array
     */
    public function default_options() {
        return array(
            'core'             => array(),
            'javascript'       => array(),
            'css'              => array(),
            'fonts'            => array(),
            'database'         => array(),
            'webp'             => array(),
            'woocommerce'      => array(),
            'image_dimensions' => array(),
            'orphaned_images'  => array(
                'restore_days'  => MBR_WP_Performance_Orphaned_Images::DEFAULT_RESTORE_DAYS,
                'enabled_types' => array( 'images' ),
                'excluded_ids'  => array(),
            ),
            // v1.12.0 sections.
            'preloading'     => array(),
            'lazy_loading'   => array(),
            'third_party'    => array(),
            'server_headers' => array(),
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
            $default_options = $this->default_options();

            add_option( 'mbr_wp_performance_options', $default_options );
            update_option( 'mbr_wp_performance_version', MBR_WP_PERFORMANCE_VERSION );

            // Create the orphaned-images staging table on fresh install.
            MBR_WP_Performance_Orphaned_Images::create_table();
        } else {
            // Update - just update version number, preserve settings
            update_option( 'mbr_wp_performance_version', MBR_WP_PERFORMANCE_VERSION );
        }
        
        // Schedule database cleanup if needed
        if ( ! wp_next_scheduled( 'mbr_wp_performance_database_cleanup' ) ) {
            wp_schedule_event( time(), 'weekly', 'mbr_wp_performance_database_cleanup' );
        }

        // Schedule daily orphan purge (cleans up expired staging rows).
        if ( ! wp_next_scheduled( 'mbr_wp_performance_orphan_purge' ) ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'mbr_wp_performance_orphan_purge' );
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Write WebP .htaccess rules if enabled.
        $existing_opts = get_option( 'mbr_wp_performance_options', array() );
        if ( ! empty( $existing_opts['webp']['htaccess_rules'] ) ) {
            MBR_WP_Performance_WebP_Converter::add_htaccess_rules();
        }
        // Write AVIF .htaccess rules if enabled.
        if ( ! empty( $existing_opts['webp']['avif_enabled'] ) && ! empty( $existing_opts['webp']['htaccess_rules'] ) ) {
            MBR_WP_Performance_AVIF_Converter::add_htaccess_rules();
        }
        // Write Server Headers .htaccess rules if enabled.
        if ( ! empty( $existing_opts['server_headers']['browser_cache'] ) ) {
            MBR_WP_Performance_Server_Headers::instance()->ensure_rules();
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
        wp_clear_scheduled_hook( 'mbr_wp_performance_orphan_purge' );
        wp_clear_scheduled_hook( 'mbr_wp_performance_third_party_refresh' );

        // Clean up WebP files and .htaccess rules.
        MBR_WP_Performance_WebP_Converter::cleanup_on_deactivation();
        // Clean up AVIF.
        MBR_WP_Performance_AVIF_Converter::cleanup_on_deactivation();
        // Clean up third-party script cache.
        MBR_WP_Performance_Third_Party_Scripts::cleanup_on_deactivation();
        // Clean up server-header rules.
        MBR_WP_Performance_Server_Headers::cleanup_on_deactivation();
        
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
