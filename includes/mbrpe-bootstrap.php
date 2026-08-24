<?php
/**
 * MBR Performance XL — plugin bootstrap.
 *
 * Holds the plugin class (MBRPE) and helper functions. The main plugin file loads
 * this ONLY after its collision guard has confirmed no other MBR Performance copy
 * is already active.
 *
 * Why these live here rather than in the main file: PHP binds top-level functions
 * and classes while COMPILING a file — before any runtime guard can run. If these
 * declarations sat in the main file, activating XL while the standard plugin was
 * still active would throw a fatal "cannot redeclare" during the activation
 * sandbox, before the guard could stop it. Loading them from here, behind the
 * guard, lets the guard prevent the collision cleanly.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * One-time migration of pre-1.15.0 option keys and scheduled hooks to the
 * unified "mbrpe_" prefix. Runs once per site; preserves all stored settings,
 * font caches and WebP/AVIF conversion registries.
 *
 * @return void
 */
function mbrpe_maybe_migrate_prefix() {
    if ( get_option( 'mbrpe_prefix_migrated' ) ) {
        return;
    }

    global $wpdb;

    // Single-site (and per-site on multisite) options.
    $old_names = $wpdb->get_col(
        "SELECT option_name FROM {$wpdb->options}
         WHERE option_name LIKE 'mbr\\_wp\\_performance\\_%'
            OR option_name LIKE 'mbr\\_webp\\_%'
            OR option_name LIKE 'mbr\\_avif\\_%'"
    );

    foreach ( (array) $old_names as $old ) {
        if ( 0 === strpos( $old, 'mbr_wp_performance_' ) ) {
            $new = 'mbrpe_' . substr( $old, 19 );
        } elseif ( 0 === strpos( $old, 'mbr_webp_' ) ) {
            $new = 'mbrpe_webp_' . substr( $old, 9 );
        } elseif ( 0 === strpos( $old, 'mbr_avif_' ) ) {
            $new = 'mbrpe_avif_' . substr( $old, 9 );
        } else {
            continue;
        }
        if ( false === get_option( $new, false ) ) {
            update_option( $new, get_option( $old ) );
        }
        delete_option( $old );
    }

    // Network (site) options on multisite.
    if ( is_multisite() ) {
        $site_map = array(
            'mbr_wp_performance_network_options'      => 'mbrpe_network_options',
            'mbr_wp_performance_allow_site_overrides' => 'mbrpe_allow_site_overrides',
            'mbr_wp_performance_network_version'      => 'mbrpe_network_version',
            'mbr_wp_performance_using_network_defaults' => 'mbrpe_using_network_defaults',
        );
        foreach ( $site_map as $old => $new ) {
            $val = get_site_option( $old, null );
            if ( null !== $val ) {
                if ( false === get_site_option( $new, false ) ) {
                    update_site_option( $new, $val );
                }
                delete_site_option( $old );
            }
        }
    }

    // Clear scheduled events registered under the old hook names; they are
    // re-scheduled under the new names by the plugin's normal init.
    foreach ( array( 'mbr_wp_performance_database_cleanup', 'mbr_wp_performance_orphan_purge' ) as $old_hook ) {
        $timestamp = wp_next_scheduled( $old_hook );
        while ( $timestamp ) {
            wp_unschedule_event( $timestamp, $old_hook );
            $timestamp = wp_next_scheduled( $old_hook );
        }
    }

    // The weekly database cleanup re-schedules itself on init; the daily
    // orphan purge has no init re-scheduler, so ensure it exists here.
    if ( ! wp_next_scheduled( 'mbrpe_orphan_purge' ) ) {
        wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'mbrpe_orphan_purge' );
    }

    update_option( 'mbrpe_prefix_migrated', 1 );
}
add_action( 'plugins_loaded', 'mbrpe_maybe_migrate_prefix', 1 );

/**
 * Main plugin class
 */
class MBRPE {

    /**
     * Single instance of the class
     *
     * @var MBRPE
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
     * @return MBRPE
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
        require_once MBRPE_PLUGIN_DIR . 'includes/class-admin.php';
        
        // Core optimizations
        require_once MBRPE_PLUGIN_DIR . 'includes/class-core-optimizations.php';
        
        // JavaScript optimizations
        require_once MBRPE_PLUGIN_DIR . 'includes/class-javascript-optimizations.php';
        
        // CSS optimizations
        require_once MBRPE_PLUGIN_DIR . 'includes/class-css-optimizations.php';

        // Used CSS (Mode A) — depends on CSS optimisation helpers above.
        require_once MBRPE_PLUGIN_DIR . 'includes/class-used-css.php';

        // Used CSS Mode B — per-template analysis that removes the unused
        // stylesheets outright. The engines themselves are loaded lazily, only
        // on a request that is actually learning a template, since parsing CSS
        // is the expensive part and most requests only ever serve a cached file.
        require_once MBRPE_PLUGIN_DIR . 'includes/class-used-css-mode-b.php';

        // Critical CSS (XL) — pasted critical CSS with full-sheet async fallback.
        // Guarded by file_exists so the wp.org "lite" build, which omits this one
        // file, shares this exact bootstrap unchanged.
        if ( file_exists( MBRPE_PLUGIN_DIR . 'includes/class-critical-css.php' ) ) {
            require_once MBRPE_PLUGIN_DIR . 'includes/class-critical-css.php';
        }

        // Performance Doctor — diagnostic advisor.
        require_once MBRPE_PLUGIN_DIR . 'includes/class-performance-doctor.php';
        
        // Font optimizations
        require_once MBRPE_PLUGIN_DIR . 'includes/class-font-optimizations.php';
        
        // Database optimizations
        require_once MBRPE_PLUGIN_DIR . 'includes/class-database-optimizations.php';
        
        // WebP Converter
        require_once MBRPE_PLUGIN_DIR . 'includes/class-webp-converter.php';

        // WooCommerce Optimizations
        require_once MBRPE_PLUGIN_DIR . 'includes/class-woocommerce-optimizations.php';

        // Image Dimensions & Sizing
        require_once MBRPE_PLUGIN_DIR . 'includes/class-image-dimensions.php';

        // Orphaned Images cleanup
        require_once MBRPE_PLUGIN_DIR . 'includes/class-orphaned-images.php';
        
        // Helper functions
        require_once MBRPE_PLUGIN_DIR . 'includes/functions.php';
        
        // Multisite support
        require_once MBRPE_PLUGIN_DIR . 'includes/class-multisite.php';

        // ====================================================================
        // v1.12.0 additions
        // ====================================================================

        // AVIF converter (sister to WebP).
        require_once MBRPE_PLUGIN_DIR . 'includes/class-avif-converter.php';

        // YouTube / Vimeo facade.
        require_once MBRPE_PLUGIN_DIR . 'includes/class-video-facade.php';

        // Browser-cache and compression .htaccess rules.
        require_once MBRPE_PLUGIN_DIR . 'includes/class-server-headers.php';

        // Autoloaded options audit.
        require_once MBRPE_PLUGIN_DIR . 'includes/class-autoload-audit.php';

        // WP-Cron viewer.
        require_once MBRPE_PLUGIN_DIR . 'includes/class-cron-viewer.php';

        // Image enhancements (decoding="async" + EXIF strip).
        require_once MBRPE_PLUGIN_DIR . 'includes/class-image-enhancements.php';

        // Hover prefetch (instant.page).
        require_once MBRPE_PLUGIN_DIR . 'includes/class-hover-prefetch.php';

        // Caching plugin conflict detector.
        require_once MBRPE_PLUGIN_DIR . 'includes/class-conflict-detector.php';

        // ====================================================================
        // v1.21.0 additions
        // ====================================================================

        // Real User Monitoring (self-hosted Core Web Vitals).
        require_once MBRPE_PLUGIN_DIR . 'includes/class-rum.php';

        // ====================================================================
        // v1.22.0 additions
        // ====================================================================

        // Script Modules / Interactivity API support (WP 6.5+).
        require_once MBRPE_PLUGIN_DIR . 'includes/class-module-scripts.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Run the upgrade routine on plugins_loaded so it fires reliably on
        // plugin updates, not just on manual activation. The routine itself
        // is a fast no-op when nothing needs migrating.
        add_action( 'plugins_loaded', array( $this, 'maybe_upgrade' ) );

        // Initialize optimization modules on init hook
        // Priority 999 to run after most plugins, including page builders
        add_action( 'init', array( $this, 'init_optimizations' ), 999 );
        
        // Initialize admin
        if ( is_admin() ) {
            MBRPE_Admin::instance();
        }
        
        // Multisite: initialise network admin functionality
        if ( is_multisite() ) {
            MBRPE_Multisite::instance();

            // Activate plugin on newly-created sites (WP 5.1+).
            add_action( 'wp_initialize_site', array( 'MBRPE_Multisite', 'on_new_site' ), 900 );
        }
        
        // Activation/deactivation hooks
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
    }

    /**
     * Run version-keyed upgrade migrations.
     *
     * Compares the stored plugin version to MBRPE_VERSION and
     * runs any one-time migration steps for versions the user has just
     * crossed. Each step is idempotent so re-running is safe.
     */
    public function maybe_upgrade() {
        $stored = get_option( 'mbrpe_version', '0.0.0' );

        // Same version → nothing to do. Cheapest fast path.
        if ( version_compare( $stored, MBRPE_VERSION, '>=' ) ) {
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
            $opts = get_option( 'mbrpe_options', array() );
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
                update_option( 'mbrpe_options', $opts );
            } elseif ( is_array( $opts ) && isset( $opts['css']['remove_global_styles'] ) ) {
                // The key existed but was falsy — just clean it up.
                unset( $opts['css']['remove_global_styles'] );
                update_option( 'mbrpe_options', $opts );
            }
        }

        // --- Migrations from < 1.10.0 ---
        // The Orphaned Images feature ships in 1.10.0. Create its staging
        // table on first encounter with this version, and ensure the daily
        // purge cron is scheduled. Both operations are idempotent.
        if ( version_compare( $stored, '1.10.0', '<' ) ) {
            if ( class_exists( 'MBRPE_Orphaned_Images' ) ) {
                MBRPE_Orphaned_Images::create_table();
            }
            if ( ! wp_next_scheduled( 'mbrpe_orphan_purge' ) ) {
                wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'mbrpe_orphan_purge' );
            }

            // Seed the orphaned_images options section if missing so the tab
            // doesn't blow up reading an undefined index.
            $opts = get_option( 'mbrpe_options', array() );
            if ( is_array( $opts ) && ! isset( $opts['orphaned_images'] ) ) {
                $opts['orphaned_images'] = array(
                    'restore_days'  => MBRPE_Orphaned_Images::DEFAULT_RESTORE_DAYS,
                    'enabled_types' => array( 'images' ),
                    'excluded_ids'  => array(),
                );
                update_option( 'mbrpe_options', $opts );
            }
        }

        // --- Migrations from < 1.11.0 ---
        // The Orphaned Media feature replaced the v1.10.0 Orphaned Images tab.
        // Seed enabled_types for sites that had v1.10.0 settings so behaviour
        // remains identical (images-only) until the user opts in to other types.
        if ( version_compare( $stored, '1.11.0', '<' ) ) {
            $opts = get_option( 'mbrpe_options', array() );
            if ( is_array( $opts ) ) {
                if ( ! isset( $opts['orphaned_images'] ) || ! is_array( $opts['orphaned_images'] ) ) {
                    $opts['orphaned_images'] = array();
                }
                if ( ! isset( $opts['orphaned_images']['enabled_types'] )
                     || ! is_array( $opts['orphaned_images']['enabled_types'] ) ) {
                    $opts['orphaned_images']['enabled_types'] = array( 'images' );
                    update_option( 'mbrpe_options', $opts );
                }
            }
        }

        // --- Migrations from < 1.12.0 ---
        // v1.12.0 introduces new option sections (preloading, lazy_loading,
        // server_headers). Ensure they exist so admin tabs reading
        // them with $options['key'] don't trip undefined-index warnings.
        if ( version_compare( $stored, '1.12.0', '<' ) ) {
            $opts = get_option( 'mbrpe_options', array() );
            if ( ! is_array( $opts ) ) {
                $opts = array();
            }
            foreach ( array( 'preloading', 'lazy_loading', 'server_headers' ) as $section ) {
                if ( ! isset( $opts[ $section ] ) || ! is_array( $opts[ $section ] ) ) {
                    $opts[ $section ] = array();
                }
            }
            update_option( 'mbrpe_options', $opts );
        }

        // --- Migrations from < 1.13.5 ---
        // Three font-related fields were rendered on the Fonts tab but their
        // form inputs were scoped to mbrpe_options[css][...]
        // instead of [fonts][...]. Submitting the Fonts tab therefore POSTed
        // a partial css section, and sanitize_css_options — doing exactly
        // what it should do for the css tab — replaced the whole stored css
        // section, clearing every other CSS toggle.
        //
        // Fix: each tab posts under its own section, the keys move to
        // [fonts], and we migrate any existing [css][...] values across so
        // user intent is preserved. google_fonts_mode is dropped entirely
        // (the radio had no runtime consumer and was effectively dead UI);
        // font_display already had a canonical entry in [fonts] from the
        // legitimate select earlier on the same tab, so the [css] copy is
        // only adopted if [fonts] doesn't already have one.
        if ( version_compare( $stored, '1.13.5', '<' ) ) {
            $opts = get_option( 'mbrpe_options', array() );
            if ( is_array( $opts ) ) {
                if ( ! isset( $opts['fonts'] ) || ! is_array( $opts['fonts'] ) ) {
                    $opts['fonts'] = array();
                }
                if ( isset( $opts['css'] ) && is_array( $opts['css'] ) ) {
                    // Move disable_elementor_fonts only if fonts doesn't already have a deliberate value.
                    if ( isset( $opts['css']['disable_elementor_fonts'] )
                         && ! isset( $opts['fonts']['disable_elementor_fonts'] ) ) {
                        $opts['fonts']['disable_elementor_fonts'] = (bool) $opts['css']['disable_elementor_fonts'];
                    }
                    unset( $opts['css']['disable_elementor_fonts'] );

                    // Same pattern for font_display.
                    if ( isset( $opts['css']['font_display'] )
                         && ! isset( $opts['fonts']['font_display'] ) ) {
                        $opts['fonts']['font_display'] = $opts['css']['font_display'];
                    }
                    unset( $opts['css']['font_display'] );

                    // google_fonts_mode is dropped — no UI, no consumer.
                    unset( $opts['css']['google_fonts_mode'] );
                }
                update_option( 'mbrpe_options', $opts );
            }
        }

        // --- Migrations from < 1.21.0 ---
        // Real User Monitoring: create the raw + aggregate tables, schedule the
        // nightly aggregation cron, and seed the rum options section so the tab
        // never reads an undefined index. All idempotent.
        if ( version_compare( $stored, '1.21.0', '<' ) ) {
            if ( class_exists( 'MBRPE_RUM' ) ) {
                MBRPE_RUM::create_tables();
                MBRPE_RUM::schedule_cron();
            }

            $opts = get_option( 'mbrpe_options', array() );
            if ( is_array( $opts ) && ! isset( $opts['rum'] ) ) {
                $opts['rum'] = array(
                    'enabled'            => false,
                    'sample_rate'        => 100,
                    'exclude_logged_in'  => true,
                    'raw_retention_days' => 3,
                    'agg_retention_days' => 60,
                    'metrics'            => array( 'LCP', 'CLS', 'INP' ),
                );
                update_option( 'mbrpe_options', $opts );
            }
        }

        // --- Migrations from < 1.22.0 ---
        // Script Modules support: seed the options section so the tab never
        // reads an undefined index. The feature itself stays off by default.
        if ( version_compare( $stored, '1.22.0', '<' ) ) {
            $opts = get_option( 'mbrpe_options', array() );
            if ( is_array( $opts ) && ! isset( $opts['modules'] ) ) {
                $opts['modules'] = array(
                    'preload_hoist'      => false,
                    'max_preloads'       => 10,
                    'fetchpriority_high' => '',
                    'exclude'            => '',
                );
                update_option( 'mbrpe_options', $opts );
            }
        }

        // --- Migrations from < 1.23.0 ---
        // Used CSS Mode B: seed its keys so the CSS tab never reads an
        // undefined index. The feature itself stays off — an update must never
        // start removing stylesheets from a site that did not ask for it.
        if ( version_compare( $stored, '1.23.0', '<' ) ) {
            $opts = get_option( 'mbrpe_options', array() );
            if ( is_array( $opts ) ) {
                if ( ! isset( $opts['css'] ) || ! is_array( $opts['css'] ) ) {
                    $opts['css'] = array();
                }
                $defaults = array(
                    'modeb_enabled'     => false,
                    'modeb_samples'     => MBRPE_Used_CSS_Mode_B::DEFAULT_SAMPLES,
                    'modeb_safelist'    => '',
                    'modeb_keep_sheets' => '',
                );
                foreach ( $defaults as $key => $value ) {
                    if ( ! isset( $opts['css'][ $key ] ) ) {
                        $opts['css'][ $key ] = $value;
                    }
                }
                update_option( 'mbrpe_options', $opts );
            }
        }

        // Stamp the version once all migrations have completed.
        update_option( 'mbrpe_version', MBRPE_VERSION );
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
        MBRPE_WebP_Converter::instance();
        MBRPE_AVIF_Converter::instance();
        MBRPE_Image_Dimensions::instance();
        MBRPE_Image_Enhancements::instance();

        // RUM instantiates before the editor early-return below. Its front-end
        // capture gates itself on builder/preview context internally, and its
        // cron callback plus admin-post handlers must be registered on every
        // request — otherwise the nightly aggregation silently never fires in
        // some contexts.
        MBRPE_RUM::instance();

        // Script Modules support gates itself internally (WP version, context).
        MBRPE_Module_Scripts::instance();

        // Front-end optimisations are skipped inside page builder editors
        // and previews so they can't interfere with the editing experience.
        if ( $this->is_elementor_editor() ) {
            return;
        }
        if ( $this->is_page_builder_editor() ) {
            return;
        }

        MBRPE_Core_Optimizations::instance();
        MBRPE_JavaScript_Optimizations::instance();
        MBRPE_CSS_Optimizations::instance();
        MBRPE_Used_CSS::instance();
        MBRPE_Used_CSS_Mode_B::instance();
        if ( class_exists( 'MBRPE_Critical_CSS' ) ) {
            MBRPE_Critical_CSS::instance();
        }
        MBRPE_Font_Optimizations::instance();
        MBRPE_Database_Optimizations::instance();
        MBRPE_WooCommerce_Optimizations::instance();
        MBRPE_Orphaned_Images::instance();

        // v1.12.0 modules (non-upload).
        MBRPE_Video_Facade::instance();
        MBRPE_Server_Headers::instance();
        MBRPE_Hover_Prefetch::instance();
        MBRPE_Conflict_Detector::instance();
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
            'css'              => array(
                // Used CSS Mode B — off by default, like everything that
                // rewrites output. See class-used-css-mode-b.php.
                'modeb_enabled'    => false,
                'modeb_samples'    => MBRPE_Used_CSS_Mode_B::DEFAULT_SAMPLES,
                'modeb_safelist'   => '',
                'modeb_keep_sheets' => '',
            ),
            'fonts'            => array(),
            'database'         => array(),
            'webp'             => array(),
            'woocommerce'      => array(),
            'image_dimensions' => array(),
            'orphaned_images'  => array(
                'restore_days'  => MBRPE_Orphaned_Images::DEFAULT_RESTORE_DAYS,
                'enabled_types' => array( 'images' ),
                'excluded_ids'  => array(),
            ),
            // v1.12.0 sections.
            'preloading'     => array(),
            'lazy_loading'   => array(),
            'server_headers' => array(),
            'modules'        => array(
                'preload_hoist'      => false,
                'max_preloads'       => 10,
                'fetchpriority_high' => '',
                'exclude'            => '',
            ),
            'rum'            => array(
                'enabled'            => false,
                'sample_rate'        => 100,
                'exclude_logged_in'  => true,
                'raw_retention_days' => 3,
                'agg_retention_days' => 60,
                'metrics'            => array( 'LCP', 'CLS', 'INP' ),
            ),
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
            MBRPE_Multisite::network_activate();
            return;
        }

        // Single-site (or per-site on multisite) activation
        $installed_version = get_option( 'mbrpe_version', false );
        
        if ( false === $installed_version ) {
            // First time installation
            $default_options = $this->default_options();

            add_option( 'mbrpe_options', $default_options );
            update_option( 'mbrpe_version', MBRPE_VERSION );

            // Create the orphaned-images staging table on fresh install.
            MBRPE_Orphaned_Images::create_table();

            // Create the RUM tables on fresh install.
            if ( class_exists( 'MBRPE_RUM' ) ) {
                MBRPE_RUM::create_tables();
            }
        } else {
            // Update - just update version number, preserve settings
            update_option( 'mbrpe_version', MBRPE_VERSION );
        }
        
        // Schedule database cleanup if needed
        if ( ! wp_next_scheduled( 'mbrpe_database_cleanup' ) ) {
            wp_schedule_event( time(), 'weekly', 'mbrpe_database_cleanup' );
        }

        // Schedule daily orphan purge (cleans up expired staging rows).
        if ( ! wp_next_scheduled( 'mbrpe_orphan_purge' ) ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'mbrpe_orphan_purge' );
        }

        // Schedule the daily RUM aggregation cron.
        if ( class_exists( 'MBRPE_RUM' ) ) {
            MBRPE_RUM::schedule_cron();
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Write WebP .htaccess rules if enabled.
        $existing_opts = get_option( 'mbrpe_options', array() );
        if ( ! empty( $existing_opts['webp']['htaccess_rules'] ) ) {
            MBRPE_WebP_Converter::add_htaccess_rules();
        }
        // Write AVIF .htaccess rules if enabled.
        if ( ! empty( $existing_opts['webp']['avif_enabled'] ) && ! empty( $existing_opts['webp']['htaccess_rules'] ) ) {
            MBRPE_AVIF_Converter::add_htaccess_rules();
        }
        // Write Server Headers .htaccess rules if enabled.
        if ( ! empty( $existing_opts['server_headers']['browser_cache'] ) ) {
            MBRPE_Server_Headers::instance()->ensure_rules();
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
            MBRPE_Multisite::network_deactivate();
            return;
        }

        // Clear scheduled events
        wp_clear_scheduled_hook( 'mbrpe_database_cleanup' );
        wp_clear_scheduled_hook( 'mbrpe_orphan_purge' );
        wp_clear_scheduled_hook( 'mbrpe_third_party_refresh' );
        if ( class_exists( 'MBRPE_RUM' ) ) {
            MBRPE_RUM::unschedule_cron();
        }

        // Clean up WebP files and .htaccess rules.
        MBRPE_WebP_Converter::cleanup_on_deactivation();
        // Clean up AVIF.
        MBRPE_AVIF_Converter::cleanup_on_deactivation();
        // Clean up server-header rules.
        MBRPE_Server_Headers::cleanup_on_deactivation();

        // Clear any cached combined-CSS bundles.
        MBRPE_CSS_Optimizations::purge_combine_cache();

        // ...and the cached used-CSS files, which were previously left behind
        // and would be served again on reactivation even if the site's CSS had
        // changed in the meantime.
        if ( class_exists( 'MBRPE_Used_CSS' ) ) {
            MBRPE_Used_CSS::purge_all();
        }

        // ...and the per-template Mode B cache, for the same reason.
        if ( class_exists( 'MBRPE_Used_CSS_Mode_B' ) ) {
            MBRPE_Used_CSS_Mode_B::purge_all();
        }
        
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
            if ( is_multisite() && class_exists( 'MBRPE_Multisite' ) ) {
                $this->options = MBRPE_Multisite::get_effective_options();
            } else {
                $this->options = get_option( 'mbrpe_options', array() );
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
            update_option( 'mbrpe_using_network_defaults', false );
        }

        return update_option( 'mbrpe_options', $options );
    }
}

/**
 * Get main plugin instance
 *
 * @return MBRPE
 */
function mbrpe() {
    return MBRPE::instance();
}

// Initialize plugin
mbrpe();
