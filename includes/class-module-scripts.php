<?php
/**
 * MBR Performance — Script Modules & Interactivity API support.
 *
 * WordPress 6.5+ ships the Script Modules API (`wp_enqueue_script_module`) and
 * the Interactivity API. These emit `<script type="module">` tags and an import
 * map printed directly by WP_Script_Modules — they never pass through
 * `wp_scripts()`, so the classic defer / delay / combine passes cannot see them
 * (and must not try to: modules defer by spec, and combining them would break
 * the import map's bare-specifier resolution).
 *
 * What this module adds is the piece core leaves on the table:
 *
 *   In a BLOCK theme, core prints the import map and its `modulepreload` hints
 *   in `wp_head` — early, where hints are useful.
 *
 *   In a CLASSIC theme (Elementor, and most non-block themes), modules are
 *   discovered while the body renders, so core defers all of it to `wp_footer`.
 *   The preload hints then arrive at the same moment as the scripts they were
 *   meant to front-run, which makes them close to worthless.
 *
 * This module closes that gap by learning which modules a URL actually enqueues,
 * caching that set, and emitting `<link rel="modulepreload">` in the head on
 * subsequent requests — so the browser starts fetching the module graph while it
 * is still parsing the head, rather than at the bottom of the document.
 *
 * @package MBRPE
 * @since   1.22.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBRPE_Module_Scripts {

    /**
     * Single instance.
     *
     * @var MBRPE_Module_Scripts
     */
    private static $instance = null;

    /**
     * Option holding the learned per-URL module map.
     * Non-autoloaded; a single bounded row rather than transient sprawl.
     */
    const CACHE_OPTION = 'mbrpe_module_map';

    /**
     * Maximum number of URLs retained in the learned map.
     */
    const CACHE_LIMIT = 300;

    /**
     * Cache key for the current request, computed once.
     *
     * @var string
     */
    private $key = '';

    /**
     * Get instance.
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
        // Hard requirement: the Script Modules API (WP 6.5+). On anything older
        // this module is a complete no-op.
        if ( ! self::api_available() ) {
            return;
        }

        add_action( 'wp', array( $this, 'maybe_init' ) );

        if ( is_admin() ) {
            add_action( 'admin_post_mbrpe_modules_clear', array( $this, 'handle_clear' ) );
        }
    }

    /**
     * Handle the "Clear learned modules" admin-post action.
     *
     * @return void
     */
    public function handle_clear() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to do this.', 'mbr-performance' ) );
        }
        check_admin_referer( 'mbrpe_modules_clear' );

        self::clear_cache();

        wp_safe_redirect(
            add_query_arg(
                array(
                    'page'            => 'mbr-performance',
                    'tab'             => 'javascript',
                    'modules_cleared' => '1',
                ),
                admin_url( 'admin.php' )
            )
        );
        exit;
    }

    /**
     * Whether the Script Modules API exists on this WordPress version.
     *
     * @return bool
     */
    public static function api_available() {
        return function_exists( 'wp_script_modules' ) && class_exists( 'WP_Script_Modules' );
    }

    /**
     * Wire the front-end hooks once the query is known.
     *
     * @return void
     */
    public function maybe_init() {
        if ( $this->should_skip() ) {
            return;
        }

        $this->key = $this->compute_key();

        // Apply fetchpriority hints early, before core prints anything.
        add_action( 'wp_enqueue_scripts', array( $this, 'apply_fetchpriority' ), 99 );

        // Emit learned preloads high in the head.
        if ( $this->enabled( 'preload_hoist' ) ) {
            add_action( 'wp_head', array( $this, 'print_hoisted_preloads' ), 2 );
        }

        // Learn this request's module set at the very end, once every module
        // that is going to be enqueued has been.
        add_action( 'wp_footer', array( $this, 'record_modules' ), 99 );
    }

    /**
     * Contexts where we never touch anything.
     *
     * @return bool
     */
    private function should_skip() {
        if ( is_admin() || is_feed() || is_embed() || is_preview() ) {
            return true;
        }
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return true;
        }
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            return true;
        }
        if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
            return true;
        }
        // Page-builder editors and previews.
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        foreach ( array( 'elementor-preview', 'vcv-editable', 'fl_builder', 'ct_builder', 'bricks-is-builder' ) as $flag ) {
            if ( isset( $_GET[ $flag ] ) ) {
                return true;
            }
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        return false;
    }

    /**
     * Read a module option.
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    private function opt( $key, $default = null ) {
        $opts = mbrpe()->get_options( 'modules' );
        return isset( $opts[ $key ] ) ? $opts[ $key ] : $default;
    }

    /**
     * Whether a module toggle is on.
     *
     * @param string $key
     * @return bool
     */
    private function enabled( $key ) {
        return ! empty( $this->opt( $key ) );
    }

    /**
     * Cache key for the current request: normalised path plus plugin version,
     * matching the Used CSS convention so both bust together on upgrade.
     *
     * @return string
     */
    private function compute_key() {
        $path = '/';
        if ( isset( $_SERVER['REQUEST_URI'] ) ) {
            $uri  = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
            $p    = wp_parse_url( $uri, PHP_URL_PATH );
            $path = $p ? $p : '/';
        }
        $path = strtolower( rtrim( $path, '/' ) );
        if ( '' === $path ) {
            $path = '/';
        }
        return md5( $path . '|' . MBRPE_VERSION );
    }

    /* ---------------------------------------------------------------------
     * Inventory
     * ------------------------------------------------------------------- */

    /**
     * The modules enqueued on this request, with their resolved sources and
     * static dependencies. Used both for preload learning and for diagnostics.
     *
     * @return array[] id => array( 'src', 'deps', 'in_footer', 'fetchpriority' )
     */
    public function inventory() {
        if ( ! self::api_available() ) {
            return array();
        }

        $modules = wp_script_modules();
        if ( ! is_object( $modules ) || ! method_exists( $modules, 'get_queue' ) ) {
            return array();
        }

        $queue = $modules->get_queue();
        if ( empty( $queue ) ) {
            return array();
        }

        $out = array();
        foreach ( $queue as $id ) {
            $reg = method_exists( $modules, 'get_registered' ) ? $modules->get_registered( $id ) : null;
            if ( ! is_array( $reg ) ) {
                continue;
            }
            $out[ $id ] = array(
                'src'           => $this->resolve_src( $id, $reg ),
                'deps'          => isset( $reg['dependencies'] ) ? (array) $reg['dependencies'] : array(),
                'in_footer'     => ! empty( $reg['in_footer'] ),
                'fetchpriority' => isset( $reg['fetchpriority'] ) ? $reg['fetchpriority'] : 'auto',
            );
        }

        return $out;
    }

    /**
     * Resolve a module's final URL the same way core does: append the version
     * query arg, then run the core filter so any src rewriting is honoured.
     *
     * Core's own get_src() is private, so this mirrors it rather than calling it.
     *
     * @param string $id
     * @param array  $reg Registered module data.
     * @return string
     */
    private function resolve_src( $id, $reg ) {
        $src = isset( $reg['src'] ) ? (string) $reg['src'] : '';
        if ( '' === $src ) {
            return '';
        }

        // array_key_exists, not isset: a registered version of null means
        // "add no version at all", and isset() reports false for null — which
        // would fall through to the default and append the core version. A
        // preload URL that doesn't match the real script URL causes the browser
        // to download the module twice, so this distinction matters.
        $version = array_key_exists( 'version', $reg ) ? $reg['version'] : false;
        if ( false === $version ) {
            $src = add_query_arg( 'ver', get_bloginfo( 'version' ), $src );
        } elseif ( null !== $version ) {
            $src = add_query_arg( 'ver', $version, $src );
        }

        /** This filter is documented in wp-includes/class-wp-script-modules.php */
        $src = apply_filters( 'script_module_loader_src', $src, $id );

        return is_string( $src ) ? $src : '';
    }

    /**
     * Walk the static dependency graph of the enqueued modules and resolve each
     * dependency's URL. Dynamic imports are deliberately excluded — they are
     * loaded on demand, so preloading them would fetch bytes that may never be
     * needed.
     *
     * @param array $inventory Result of inventory().
     * @return string[] Resolved dependency URLs.
     */
    private function resolve_static_deps( $inventory ) {
        if ( ! self::api_available() || empty( $inventory ) ) {
            return array();
        }

        $modules = wp_script_modules();
        if ( ! method_exists( $modules, 'get_registered' ) ) {
            return array();
        }

        $seen  = array();
        $srcs  = array();
        $stack = array();

        foreach ( $inventory as $data ) {
            foreach ( (array) $data['deps'] as $dep ) {
                $stack[] = $dep;
            }
        }

        // Iterative walk with a hard depth guard against pathological graphs.
        $guard = 0;
        while ( ! empty( $stack ) && $guard < 500 ) {
            $guard++;
            $dep = array_pop( $stack );

            // Dependencies are array( 'id' => ..., 'import' => 'static'|'dynamic' ).
            $dep_id   = is_array( $dep ) && isset( $dep['id'] ) ? $dep['id'] : ( is_string( $dep ) ? $dep : '' );
            $dep_type = is_array( $dep ) && isset( $dep['import'] ) ? $dep['import'] : 'static';

            if ( '' === $dep_id || 'static' !== $dep_type || isset( $seen[ $dep_id ] ) ) {
                continue;
            }
            $seen[ $dep_id ] = true;

            $reg = $modules->get_registered( $dep_id );
            if ( ! is_array( $reg ) ) {
                continue;
            }

            $src = $this->resolve_src( $dep_id, $reg );
            if ( '' !== $src ) {
                $srcs[ $dep_id ] = $src;
            }

            foreach ( (array) ( isset( $reg['dependencies'] ) ? $reg['dependencies'] : array() ) as $sub ) {
                $stack[] = $sub;
            }
        }

        return $srcs;
    }

    /* ---------------------------------------------------------------------
     * fetchpriority
     * ------------------------------------------------------------------- */

    /**
     * Mark user-nominated modules as high fetchpriority. Uses the core API
     * (WP 6.9+) so core prints the attribute itself; silently no-ops on older
     * versions rather than hand-writing attributes core would not understand.
     *
     * @return void
     */
    public function apply_fetchpriority() {
        $ids = $this->id_list( 'fetchpriority_high' );
        if ( empty( $ids ) ) {
            return;
        }

        $modules = wp_script_modules();
        if ( ! method_exists( $modules, 'set_fetchpriority' ) ) {
            return; // Pre-6.9: no API, nothing safe to do.
        }

        foreach ( $ids as $id ) {
            $modules->set_fetchpriority( $id, 'high' );
        }
    }

    /**
     * Parse a newline-separated module ID list from settings.
     *
     * @param string $key
     * @return string[]
     */
    private function id_list( $key ) {
        $raw = (string) $this->opt( $key, '' );
        if ( '' === trim( $raw ) ) {
            return array();
        }
        $out = array();
        foreach ( preg_split( '/[\r\n]+/', $raw ) as $line ) {
            $line = trim( $line );
            if ( '' !== $line && 0 !== strpos( $line, '#' ) ) {
                $out[] = $line;
            }
        }
        return array_unique( $out );
    }

    /* ---------------------------------------------------------------------
     * Preload hoisting
     * ------------------------------------------------------------------- */

    /**
     * Print learned `modulepreload` hints in the head.
     *
     * Only meaningful on classic themes: block themes already get core's hints
     * in the head, so hoisting there would duplicate work core does correctly.
     *
     * @return void
     */
    public function print_hoisted_preloads() {
        if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
            return; // Core already prints these in the head.
        }

        $urls = $this->get_cached_urls();
        if ( empty( $urls ) ) {
            return; // Nothing learned for this URL yet — this request will teach it.
        }

        $exclude = $this->id_list( 'exclude' );
        $limit   = (int) $this->opt( 'max_preloads', 10 );
        $limit   = max( 1, min( 50, $limit ) );
        $printed = 0;

        echo "\n<!-- MBR Performance: script module preloads -->\n";
        foreach ( $urls as $id => $url ) {
            if ( $printed >= $limit ) {
                break;
            }
            if ( in_array( $id, $exclude, true ) ) {
                continue;
            }
            printf(
                '<link rel="modulepreload" href="%s" data-mbrpe-module="%s">' . "\n",
                esc_url( $url ),
                esc_attr( $id )
            );
            $printed++;
        }
    }

    /**
     * Learn this request's module set and store it for subsequent requests.
     *
     * Runs late in wp_footer, by which point every module that is going to be
     * enqueued has been — which is precisely why core cannot print these hints
     * in the head on a classic theme, and why learning is necessary.
     *
     * @return void
     */
    public function record_modules() {
        $inventory = $this->inventory();
        if ( empty( $inventory ) ) {
            // No modules here. Record the empty result too, so we don't re-learn
            // this URL on every hit.
            $this->store_urls( array() );
            return;
        }

        $urls = array();

        // The enqueued modules themselves. Core does NOT preload these — in a
        // block theme their script tags are already in the head, so a hint would
        // be redundant. On a classic theme those tags are in the footer, so
        // hinting them in the head is exactly the win we are after.
        foreach ( $inventory as $id => $data ) {
            if ( '' !== $data['src'] ) {
                $urls[ $id ] = $data['src'];
            }
        }

        // Their static dependencies.
        foreach ( $this->resolve_static_deps( $inventory ) as $id => $src ) {
            if ( ! isset( $urls[ $id ] ) ) {
                $urls[ $id ] = $src;
            }
        }

        $this->store_urls( $urls );
    }

    /* ---------------------------------------------------------------------
     * Learned-map storage
     * ------------------------------------------------------------------- */

    /**
     * Read the whole learned map.
     *
     * @return array
     */
    private function get_map() {
        $map = get_option( self::CACHE_OPTION, array() );
        return is_array( $map ) ? $map : array();
    }

    /**
     * Cached module URLs for the current request, or an empty array.
     *
     * @return array id => url
     */
    private function get_cached_urls() {
        if ( '' === $this->key ) {
            return array();
        }
        $map = $this->get_map();
        if ( ! isset( $map[ $this->key ] ) || ! is_array( $map[ $this->key ] ) ) {
            return array();
        }
        $entry = $map[ $this->key ];
        return isset( $entry['urls'] ) && is_array( $entry['urls'] ) ? $entry['urls'] : array();
    }

    /**
     * Store the learned URL set for the current request, trimming the map when
     * it grows past the cap (oldest entries first).
     *
     * @param array $urls
     * @return void
     */
    private function store_urls( $urls ) {
        if ( '' === $this->key ) {
            return;
        }

        $map      = $this->get_map();
        $existing = isset( $map[ $this->key ]['urls'] ) ? $map[ $this->key ]['urls'] : null;

        // Nothing changed — skip the write entirely. Most requests hit this.
        if ( is_array( $existing ) && $existing === $urls ) {
            return;
        }

        $map[ $this->key ] = array(
            'urls' => $urls,
            'time' => time(),
        );

        if ( count( $map ) > self::CACHE_LIMIT ) {
            uasort(
                $map,
                function ( $a, $b ) {
                    $at = isset( $a['time'] ) ? (int) $a['time'] : 0;
                    $bt = isset( $b['time'] ) ? (int) $b['time'] : 0;
                    return $at - $bt;
                }
            );
            $map = array_slice( $map, count( $map ) - self::CACHE_LIMIT, null, true );
        }

        update_option( self::CACHE_OPTION, $map, false );
    }

    /**
     * Clear the learned map.
     *
     * @return void
     */
    public static function clear_cache() {
        delete_option( self::CACHE_OPTION );
    }

    /**
     * Number of URLs currently learned, for the admin panel.
     *
     * @return int
     */
    public static function learned_count() {
        $map = get_option( self::CACHE_OPTION, array() );
        return is_array( $map ) ? count( $map ) : 0;
    }
}
