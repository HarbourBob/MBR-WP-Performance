<?php
/**
 * JavaScript Optimizations
 *
 * Implements the backend logic for the JavaScript tab toggles.
 *
 * Each optimisation is conditionally hooked based on its option, and
 * every front-end filter no-ops while the user is in a page builder
 * editor (Elementor, Beaver, Divi, Oxygen, Bricks, WPBakery) to avoid
 * breaking edit-mode tooling.
 *
 * @package MBRPE
 * @since   1.12.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBRPE_JavaScript_Optimizations {

    /**
     * Single instance.
     *
     * @var MBRPE_JavaScript_Optimizations
     */
    private static $instance = null;

    /**
     * Options.
     *
     * @var array
     */
    private $options = array();

    /**
     * Get instance.
     *
     * @return MBRPE_JavaScript_Optimizations
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
        $this->options = mbrpe()->get_options( 'javascript' );
        $this->init_optimizations();
    }

    /**
     * Get an option value with a default.
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    private function get_option( $key, $default = false ) {
        return isset( $this->options[ $key ] ) ? $this->options[ $key ] : $default;
    }

    /**
     * Convert a textarea of newline-separated exclusion entries into an array.
     *
     * @param string $key
     * @return string[]
     */
    private function get_exclusion_list( $key ) {
        $raw = $this->get_option( $key, '' );
        if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
            return array();
        }
        $lines = preg_split( '/\r\n|\r|\n/', $raw );
        $out   = array();
        foreach ( $lines as $line ) {
            $line = trim( (string) $line );
            if ( '' !== $line ) {
                $out[] = $line;
            }
        }
        return array_values( array_unique( $out ) );
    }

    /**
     * Decide whether a script tag (by handle or src) matches an exclusion list.
     *
     * @param string   $handle
     * @param string   $src
     * @param string[] $exclusions
     * @return bool
     */
    private function is_excluded( $handle, $src, $exclusions ) {
        if ( empty( $exclusions ) ) {
            return false;
        }
        $haystack = strtolower( $handle . '|' . $src );
        foreach ( $exclusions as $needle ) {
            if ( '' === $needle ) {
                continue;
            }
            if ( false !== strpos( $haystack, strtolower( $needle ) ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Should front-end JS optimisations be suppressed for the current request?
     *
     * @return bool
     */
    private function should_skip() {
        if ( is_admin() ) {
            return true;
        }
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            return true;
        }
        if ( ! empty( $_GET['elementor-preview'] ) || ( isset( $_GET['action'] ) && 'elementor' === $_GET['action'] ) ) {
            return true;
        }
        if ( isset( $_GET['fl_builder'] ) || ( ! empty( $_GET['bricks'] ) && 'run' === $_GET['bricks'] ) ) {
            return true;
        }
        if ( function_exists( 'et_core_is_fb_enabled' ) && et_core_is_fb_enabled() ) {
            return true;
        }
        if ( defined( 'SHOW_CT_BUILDER' ) ) {
            return true;
        }
        if ( function_exists( 'is_customize_preview' ) && is_customize_preview() ) {
            return true;
        }
        return false;
    }

    /**
     * Initialise all enabled optimisations.
     */
    private function init_optimizations() {
        if ( $this->get_option( 'defer_javascript' )
            || $this->get_option( 'defer_jquery' )
            || $this->get_option( 'delay_javascript' ) ) {
            add_filter( 'script_loader_tag', array( $this, 'filter_script_tag' ), 20, 3 );
        }

        if ( $this->get_option( 'move_scripts_footer' ) ) {
            add_action( 'wp_enqueue_scripts', array( $this, 'move_scripts_to_footer' ), 9999 );
        }

        if ( $this->get_option( 'remove_jquery' ) ) {
            add_action( 'wp_enqueue_scripts', array( $this, 'remove_jquery' ), 100 );
        }

        if ( $this->get_option( 'minify_javascript' ) ) {
            add_filter( 'script_loader_tag', array( $this, 'minify_inline_script' ), 99, 3 );
        }

        // Combine JS: merge contiguous runs of "pure" external scripts (no
        // inline or localized data, no async / core defer strategy, not
        // conditional) within the same group into a single cached bundle,
        // cutting HTTP requests while preserving execution order. Head and
        // footer are processed at different points so that late-enqueued
        // footer scripts are still caught.
        if ( $this->get_option( 'combine_javascript' ) ) {
            add_action( 'wp_enqueue_scripts', array( $this, 'combine_head_scripts' ), 10000 );
            add_action( 'wp_footer', array( $this, 'combine_footer_scripts' ), 9 );
        }

        if ( $this->get_option( 'remove_script_versions' ) ) {
            add_filter( 'script_loader_src', array( $this, 'remove_script_version' ), 10, 1 );
        }

        if ( $this->get_option( 'delay_javascript' ) ) {
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_delay_runtime' ) );
        }
    }

    /**
     * Rewrite a single script tag: apply defer, footer-move marker, delay
     * attribute or jQuery defer as appropriate.
     *
     * @param string $tag
     * @param string $handle
     * @param string $src
     * @return string
     */
    public function filter_script_tag( $tag, $handle, $src ) {
        if ( $this->should_skip() ) {
            return $tag;
        }
        if ( '' === $src || false === stripos( $tag, '<script' ) ) {
            return $tag;
        }
        if ( preg_match( '/\s(async|type=["\']module["\'])/i', $tag ) ) {
            return $tag;
        }

        // Delay path: takes priority over defer because it changes the script
        // type and would otherwise be undone.
        if ( $this->get_option( 'delay_javascript' ) ) {
            $delay_list = $this->get_exclusion_list( 'delay_scripts' );
            if ( $this->is_excluded( $handle, $src, $delay_list ) ) {
                $tag = preg_replace( '/\stype=["\'][^"\']*["\']/i', '', $tag );
                $tag = str_replace( '<script ', '<script type="mbr-delayed" data-mbr-src="' . esc_attr( $src ) . '" ', $tag ); // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- Rewrites the tag of a script already enqueued via wp_enqueue_script(); this script_loader_tag filter only transforms existing markup and cannot enqueue.
                $tag = preg_replace( '/\ssrc=["\'][^"\']+["\']/i', '', $tag );
                return $tag;
            }
        }

        if ( $this->get_option( 'defer_javascript' ) ) {
            $defer_excl = $this->get_exclusion_list( 'exclude_defer' );
            if ( ! $this->is_excluded( $handle, $src, $defer_excl ) && false === stripos( $tag, ' defer' ) ) {
                $tag = str_replace( '<script ', '<script defer ', $tag );
            }
        }

        if ( $this->get_option( 'defer_jquery' ) ) {
            if ( in_array( $handle, array( 'jquery', 'jquery-core', 'jquery-migrate' ), true ) && false === stripos( $tag, ' defer' ) ) {
                $tag = str_replace( '<script ', '<script defer ', $tag );
            }
        }

        return $tag;
    }

    /**
     * The jQuery foundation. These handles are never moved to the footer.
     *
     * Relocating jQuery core/migrate breaks the dependency graph: WordPress
     * resolves script groups against each handle's dependents, so moving one
     * of the pair without the other (or ahead of a dependant that stays in
     * the head) strands jquery-migrate in the head with nothing to attach to,
     * yielding "jQuery is not defined" and taking down every downstream
     * handler (Elementor, ElementsKit, page-builder widgets, etc.). Almost
     * nothing benefits from deferring jQuery itself, so it is protected
     * unconditionally regardless of the user's exclusion list.
     *
     * @return array List of protected script handles.
     */
    protected function get_footer_protected_handles() {
        /**
         * Filter the handles that are never moved to the footer.
         *
         * @param array $handles Protected script handles.
         */
        return apply_filters(
            'mbrpe_footer_protected_handles',
            array( 'jquery', 'jquery-core', 'jquery-migrate' )
        );
    }

    /**
     * Move all enqueued scripts to the footer (with exclusions).
     *
     * The jQuery foundation (see get_footer_protected_handles) is always left
     * in the head so the dependency graph stays intact; user exclusions apply
     * to everything else.
     */
    public function move_scripts_to_footer() {
        if ( $this->should_skip() ) {
            return;
        }

        global $wp_scripts;
        if ( ! ( $wp_scripts instanceof WP_Scripts ) ) {
            return;
        }

        $excludes  = $this->get_exclusion_list( 'exclude_footer' );
        $protected = $this->get_footer_protected_handles();

        foreach ( $wp_scripts->registered as $handle => $script ) {
            if ( in_array( $handle, $protected, true ) ) {
                continue;
            }
            if ( $this->is_excluded( $handle, isset( $script->src ) ? (string) $script->src : '', $excludes ) ) {
                continue;
            }
            $wp_scripts->add_data( $handle, 'group', 1 );
        }
    }

    /**
     * Remove jQuery on the front end.
     */
    public function remove_jquery() {
        if ( $this->should_skip() ) {
            return;
        }
        if ( $this->get_option( 'jquery_test_mode' ) && is_user_logged_in() ) {
            return;
        }
        wp_deregister_script( 'jquery' );
        wp_deregister_script( 'jquery-core' );
        wp_deregister_script( 'jquery-migrate' );
    }

    /**
     * Strip the ?ver= query string from script URLs.
     *
     * @param string $src
     * @return string
     */
    public function remove_script_version( $src ) {
        if ( is_string( $src ) && false !== strpos( $src, 'ver=' ) ) {
            $src = remove_query_arg( 'ver', $src );
        }
        return $src;
    }

    /**
     * Lightly minify inline scripts.
     *
     * Only touches inline <script>...</script> bodies. External script files
     * are left alone — they are typically already minified.
     *
     * @param string $tag
     * @param string $handle
     * @param string $src
     * @return string
     */
    public function minify_inline_script( $tag, $handle, $src ) {
        if ( $this->should_skip() || '' !== $src ) {
            return $tag;
        }
        return preg_replace_callback( '/(<script[^>]*>)(.*?)(<\/script>)/is', function ( $m ) {
            $body = $m[2];
            $body = preg_replace( '#/\*(?!!).*?\*/#s', '', $body );
            $body = preg_replace( '#(^|\s)//[^\n\r]*#', '$1', $body );
            $body = preg_replace( '/^[ \t]+|[ \t]+$/m', '', $body );
            $body = preg_replace( '/\n{2,}/', "\n", $body );
            return $m[1] . $body . $m[3];
        }, $tag );
    }

    /**
     * Output the delay-execution runtime.
     *
     * On first user interaction (or after the configured timeout) the runtime
     * swaps every <script type="mbr-delayed"> back to a real script element.
     */
    public function enqueue_delay_runtime() {
        if ( $this->should_skip() ) {
            return;
        }
        $timeout = (int) $this->get_option( 'delay_timeout', 3 );
        if ( $timeout < 0 ) {
            $timeout = 0;
        }
        $timeout_ms = $timeout * 1000;
        ob_start();
        ?>
        (function(){
            var fired=false;
            function loadAll(){
                if(fired) return;
                fired=true;
                document.querySelectorAll('script[type="mbr-delayed"]').forEach(function(s){
                    var n=document.createElement('script');
                    for(var i=0;i<s.attributes.length;i++){
                        var a=s.attributes[i];
                        if(a.name==='type'||a.name==='data-mbr-src') continue;
                        n.setAttribute(a.name,a.value);
                    }
                    var src=s.getAttribute('data-mbr-src');
                    if(src){ n.src=src; } else { n.text=s.text||s.textContent||''; }
                    s.parentNode.insertBefore(n,s);
                    s.parentNode.removeChild(s);
                });
                window.dispatchEvent(new Event('mbr-delayed-loaded'));
            }
            var ev=['mousemove','touchstart','keydown','scroll','wheel','click'];
            ev.forEach(function(e){ window.addEventListener(e,loadAll,{once:true,passive:true}); });
__MBR_TIMEOUT__
        })();
        <?php
        $runtime = ob_get_clean();
        $runtime = str_replace( '__MBR_TIMEOUT__', $timeout_ms > 0 ? 'setTimeout(loadAll,' . absint( $timeout_ms ) . ');' : '', $runtime );
        wp_register_script( 'mbr-performance-delay', false, array(), MBRPE_VERSION, true );
        wp_enqueue_script( 'mbr-performance-delay' );
        wp_add_inline_script( 'mbr-performance-delay', $runtime );
    }

    /**
     * Combine the head script group. Hooked late on wp_enqueue_scripts so the
     * head queue is fully known, and after move-to-footer (priority 9999) so
     * any relocated scripts have already left the head group.
     *
     * @return void
     */
    public function combine_head_scripts() {
        $this->combine_scripts( 0 );
    }

    /**
     * Combine the footer script group. Hooked early on wp_footer so that
     * scripts enqueued during the body render are included, but before the
     * footer scripts are printed.
     *
     * @return void
     */
    public function combine_footer_scripts() {
        $this->combine_scripts( 1 );
    }

    /**
     * Merge contiguous runs of pure external scripts within a single group.
     *
     * Resolves the dependency order, keeps only the handles in the target
     * group, partitions them into runs of combinable scripts (a run is broken
     * by any script that carries inline/localized data, a load strategy, a
     * conditional, an exclusion match, or that can't be resolved on disk), and
     * folds each run of two or more into a single cached bundle. The run's
     * first handle (the carrier) is repointed at the bundle and the rest are
     * removed. Because only pure scripts are combined, there is never any
     * inline data to relocate.
     *
     * @param int $group_id 0 for head, 1 for footer.
     * @return void
     */
    private function combine_scripts( $group_id ) {
        if ( $this->should_skip() ) {
            return;
        }
        if ( is_feed() || is_embed() ) {
            return;
        }

        $wp_scripts = wp_scripts();
        if ( ! ( $wp_scripts instanceof WP_Scripts ) || empty( $wp_scripts->queue ) ) {
            return;
        }

        // Resolve the dependency order, then keep only handles in this group.
        // Scripts from the other group print elsewhere in the document, so
        // they can never be execution-adjacent to this group's scripts.
        $wp_scripts->all_deps( $wp_scripts->queue );
        $ordered = array();
        foreach ( $wp_scripts->to_do as $handle ) {
            if ( isset( $wp_scripts->registered[ $handle ] )
                && $this->script_group( $wp_scripts->registered[ $handle ] ) === $group_id ) {
                $ordered[] = $handle;
            }
        }
        if ( empty( $ordered ) ) {
            return;
        }

        $exclusions = $this->get_combine_exclusions();

        // Partition into contiguous runs of combinable scripts.
        $runs    = array();
        $current = array();
        foreach ( $ordered as $handle ) {
            $info = $this->combinable_script_info( $handle, $exclusions, $wp_scripts );
            if ( false === $info ) {
                if ( count( $current ) > 0 ) {
                    $runs[]  = $current;
                    $current = array();
                }
                continue;
            }
            $current[] = $info;
        }
        if ( count( $current ) > 0 ) {
            $runs[] = $current;
        }

        foreach ( $runs as $run ) {
            if ( count( $run ) < 2 ) {
                continue;
            }
            $this->merge_script_run( $run, $wp_scripts );
        }

        // Force a fresh to-do recompute at print time from the modified queue.
        $wp_scripts->to_do = array();
    }

    /**
     * Resolve the print group for a registered script (0 = head, 1 = footer).
     *
     * @param object $obj
     * @return int
     */
    private function script_group( $obj ) {
        if ( isset( $obj->extra['group'] ) ) {
            return (int) $obj->extra['group'];
        }
        return 0;
    }

    /**
     * Build the combined exclusion set.
     *
     * Combine respects not only its own exclusion list but also the Defer and
     * Delay lists, so that folding a script into a bundle can never silently
     * undo per-script defer/delay handling the user has configured.
     *
     * @return string[]
     */
    private function get_combine_exclusions() {
        $merged = array();
        foreach ( array( 'exclude_optimization', 'exclude_defer', 'delay_scripts' ) as $key ) {
            foreach ( $this->get_exclusion_list( $key ) as $entry ) {
                $merged[] = $entry;
            }
        }
        return array_values( array_unique( $merged ) );
    }

    /**
     * Decide whether a script handle can be folded into a combined bundle,
     * returning the metadata needed to build it, or false if it cannot.
     *
     * @param string     $handle
     * @param string[]   $exclusions
     * @param WP_Scripts $wp_scripts
     * @return array|false
     */
    private function combinable_script_info( $handle, $exclusions, $wp_scripts ) {
        if ( ! isset( $wp_scripts->registered[ $handle ] ) ) {
            return false;
        }
        $obj = $wp_scripts->registered[ $handle ];

        $src = isset( $obj->src ) ? (string) $obj->src : '';
        if ( '' === $src ) {
            return false; // Inline-only / meta handle.
        }

        // Conditional (IE) scripts are left alone.
        if ( ! empty( $obj->extra['conditional'] ) ) {
            return false;
        }

        // "Pure" scripts only. Anything carrying inline ("before"/"after")
        // code, localized data, or a load strategy (async / core defer) is too
        // order-sensitive — and, in the case of localized nonces, too
        // request-specific — to fuse into a shared cached file. Such a script
        // simply breaks the run.
        if ( ! empty( $obj->extra['before'] )
            || ! empty( $obj->extra['after'] )
            || ! empty( $obj->extra['data'] )
            || ! empty( $obj->extra['strategy'] ) ) {
            return false;
        }

        // Honour the combined exclusion set (handle or URL substring).
        if ( $this->is_excluded( $handle, $src, $exclusions ) ) {
            return false;
        }

        // Resolve to a readable same-origin local file; anything else (external
        // / CDN, unresolvable) breaks the run. Reuses the shared, path-traversal
        // -guarded mapper that the CSS combiner uses.
        $path = MBRPE_CSS_Optimizations::url_to_path( $src );
        if ( '' === $path || ! is_readable( $path ) ) {
            return false;
        }

        return array(
            'handle' => $handle,
            'obj'    => $obj,
            'src'    => $src,
            'path'   => $path,
            'ver'    => isset( $obj->ver ) ? (string) $obj->ver : '',
        );
    }

    /**
     * Fold a run of two or more pure scripts into a single cached bundle and
     * repoint the run's carrier handle at it.
     *
     * @param array      $run        Ordered list of combinable_script_info() entries.
     * @param WP_Scripts $wp_scripts
     * @return void
     */
    private function merge_script_run( $run, $wp_scripts ) {
        $carrier_obj = $run[0]['obj'];

        $fingerprint = $this->script_run_fingerprint( $run );
        $filename    = 'mbrpe-combined-' . $fingerprint . '.js';

        $dir = MBRPE_CSS_Optimizations::get_combine_dir();
        if ( '' === $dir ) {
            return; // Couldn't prepare the cache directory; leave scripts as-is.
        }
        $filepath = $dir['path'] . '/' . $filename;
        $fileurl  = $dir['url'] . '/' . $filename;

        // Build the bundle only if it isn't already cached on disk.
        if ( ! is_file( $filepath ) ) {
            $bundle = $this->build_script_bundle( $run );
            if ( '' === $bundle ) {
                return; // Build produced nothing — leave the originals alone.
            }
            if ( ! MBRPE_CSS_Optimizations::write_file( $filepath, $bundle ) ) {
                return; // Write failed — leave the original scripts untouched.
            }
        }

        // Repoint the carrier (a pure script, so no inline data to carry) at
        // the bundle. The md5 filename already cache-busts, so drop the ver.
        $carrier_obj->src = $fileurl;
        $carrier_obj->ver = null;

        // Remove every merged member from the queue, the registry, and any
        // remaining dependency lists so nothing re-adds them. Their code lives
        // in the bundle, which prints at the carrier's (earlier) position.
        $count = count( $run );
        for ( $i = 1; $i < $count; $i++ ) {
            $mh = $run[ $i ]['handle'];
            foreach ( $wp_scripts->registered as $reg ) {
                if ( ! empty( $reg->deps ) && in_array( $mh, $reg->deps, true ) ) {
                    $reg->deps = array_values( array_diff( $reg->deps, array( $mh ) ) );
                }
            }
            $wp_scripts->dequeue( $mh );
            $wp_scripts->remove( $mh );
        }
    }

    /**
     * Build a stable fingerprint for a script run from its members' paths,
     * modified times and versions, plus the plugin version.
     *
     * @param array $run
     * @return string
     */
    private function script_run_fingerprint( $run ) {
        $parts = array( MBRPE_VERSION, 'js' );
        foreach ( $run as $member ) {
            $mtime   = is_file( $member['path'] ) ? filemtime( $member['path'] ) : 0;
            $parts[] = $member['handle'] . '|' . $member['path'] . '|' . ( $mtime ? $mtime : '0' ) . '|' . $member['ver'];
        }
        return md5( implode( "\n", $parts ) );
    }

    /**
     * Concatenate a run's scripts into a single JavaScript string.
     *
     * Files are separated by a newline and a semicolon so that a file which
     * omits its trailing semicolon can't fuse with the next under automatic
     * semicolon insertion. The bundle is intentionally not minified: vendor
     * scripts are typically pre-minified, and regex minification of arbitrary
     * JavaScript is unsafe.
     *
     * @param array $run
     * @return string Combined JavaScript, or an empty string on failure.
     */
    private function build_script_bundle( $run ) {
        $chunks = array();
        foreach ( $run as $member ) {
            // The path was validated and confirmed readable in combinable_script_info().
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading the plugin's own validated local script from disk; wp_remote_get is for remote HTTP and WP_Filesystem::get_contents would force credential prompts for a routine local read.
            $js = file_get_contents( $member['path'] );
            if ( false === $js ) {
                continue;
            }
            // Strip a leading UTF-8 byte-order mark.
            $js = preg_replace( '/^\xEF\xBB\xBF/', '', $js );

            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                $chunks[] = "/* mbrpe: {$member['handle']} */\n" . $js;
            } else {
                $chunks[] = $js;
            }
        }

        if ( empty( $chunks ) ) {
            return '';
        }

        return implode( "\n;\n", $chunks );
    }
}
