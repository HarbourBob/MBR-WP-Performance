<?php
/**
 * CSS Optimizations
 *
 * Implements the backend logic for the CSS tab toggles.
 *
 * @package MBRPE
 * @since   1.12.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBRPE_CSS_Optimizations {

    /**
     * Number of stylesheets to keep render-blocking when async_css is enabled
     * without a critical-CSS bridge.
     *
     * Async-loading every stylesheet without an inline-critical-CSS bridge in
     * place causes a Flash of Unstyled Content / broken-paint window before
     * the async stylesheets resolve. Keeping the first couple of eligible
     * stylesheets render-blocking guarantees the page paints with real CSS
     * regardless of how the user has configured the rest of the chain.
     */
    const ASYNC_SAFETY_THRESHOLD = 2;

    /**
     * Sub-directory, under the WordPress uploads folder, where combined CSS
     * bundles are written and cached.
     */
    const COMBINE_DIRNAME = 'mbr-performance-combine';

    /**
     * URLs of combined CSS bundles to emit early preload hints for, collected
     * during the combine pass and printed in the document head.
     *
     * @var string[]
     */
    private $combine_preload_urls = array();

    /**
     * Whether combined-CSS preloading is active for this request.
     *
     * @var bool
     */
    private $do_combine_preload = false;

    /**
     * Count of async-eligible stylesheets seen so far in this request.
     *
     * Used by the safety interlock; only incremented for stylesheets that
     * would otherwise be async'd, so the threshold isn't burned by excluded
     * handles or print stylesheets.
     *
     * @var int
     */
    private $async_count = 0;

    /**
     * Single instance.
     *
     * @var MBRPE_CSS_Optimizations
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
     * @return MBRPE_CSS_Optimizations
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
        $this->options = mbrpe()->get_options( 'css' );
        $this->init_optimizations();
    }

    /**
     * Get an option value.
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    private function get_option( $key, $default = false ) {
        return isset( $this->options[ $key ] ) ? $this->options[ $key ] : $default;
    }

    /**
     * Read a textarea option as an array of trimmed non-empty lines.
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
     * Match a handle/src against substring exclusions.
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
     * Should CSS rewriting be suppressed for the current request?
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
     * Initialise enabled optimisations.
     */
    private function init_optimizations() {
        // Async non-critical stylesheets via the preload+onload pattern.
        // When Used CSS (Mode A) is active it owns CSS delivery — it inlines the
        // used CSS and defers every sheet itself — so the standalone Async CSS
        // layer is redundant and would double-wrap links. Suppress it here.
        if ( $this->get_option( 'async_css' ) && ! $this->get_option( 'remove_unused_css' ) ) {
            add_filter( 'style_loader_tag', array( $this, 'async_stylesheet' ), 99, 4 );
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_loadcss_polyfill' ) );
        }

        // Minify inline CSS (style tags only — external files are typically minified).
        if ( $this->get_option( 'minify_css' ) ) {
            add_filter( 'style_loader_tag', array( $this, 'minify_inline_style' ), 100, 4 );
        }

        // Combine CSS: merge contiguous, same-media, local stylesheets into a
        // single cached bundle to cut HTTP requests. Hooked late on
        // wp_enqueue_scripts so the full queue is known before we walk it, and
        // only ever merges runs of adjacent eligible sheets so the cascade
        // order of the page is preserved exactly.
        //
        // Like Async CSS above, Combine is stood down when Used CSS (Mode A) is
        // active: Mode A inlines the used CSS and defers every sheet itself, so
        // combining would only build a second cache to produce Mode A's deferred
        // fallback — redundant work for no gain. The two are alternatives.
        if ( $this->get_option( 'combine_css' ) && ! $this->get_option( 'remove_unused_css' ) ) {
            add_action( 'wp_enqueue_scripts', array( $this, 'combine_styles' ), 9999 );

            // Optionally emit early <link rel="preload"> hints for the combined
            // bundles so the browser starts fetching them before the parser
            // reaches the stylesheet link. Skipped when Async CSS is enabled,
            // since that path already loads the stylesheet via preload+onload.
            if ( $this->get_option( 'preload_combined_css' ) && ! $this->get_option( 'async_css' ) ) {
                $this->do_combine_preload = true;
                add_action( 'wp_head', array( $this, 'print_combine_preloads' ), 2 );
            }
        }

        // Remove unused CSS (Mode A) is handled by MBRPE_Used_CSS, which is
        // instantiated separately and reads the same remove_unused_css option.

        // Conditionally load Gutenberg block stylesheets (only those used on the page).
        if ( $this->get_option( 'load_block_styles_conditionally' ) ) {
            add_filter( 'should_load_separate_core_block_assets', '__return_true' );
        }

        // Strip ?ver= from style URLs.
        if ( $this->get_option( 'remove_css_versions' ) ) {
            add_filter( 'style_loader_src', array( $this, 'remove_style_version' ), 10, 1 );
        }

        // Disable WooCommerce stylesheets on non-shop pages.
        if ( $this->get_option( 'disable_woocommerce_css' ) && class_exists( 'WooCommerce' ) ) {
            add_action( 'wp_enqueue_scripts', array( $this, 'disable_woocommerce_css' ), 99 );
        }
    }

    /**
     * Convert a <link rel="stylesheet"> into a preload+onload async load.
     *
     * Uses the standard pattern:
     *   <link rel="preload" as="style" href="..." onload="this.rel='stylesheet'">
     *   <noscript><link rel="stylesheet" href="..."></noscript>
     *
     * @param string $tag
     * @param string $handle
     * @param string $href
     * @param string $media
     * @return string
     */
    public function async_stylesheet( $tag, $handle, $href, $media ) {
        if ( $this->should_skip() ) {
            return $tag;
        }
        // Critical CSS (XL) owns this page when active — it defers sheets itself,
        // so don't also rewrite them here. class_exists keeps this identical in
        // the lite build.
        if ( class_exists( 'MBRPE_Critical_CSS' ) && MBRPE_Critical_CSS::is_active() ) {
            return $tag;
        }
        if ( '' === $href ) {
            return $tag;
        }
        // Don't async admin-bar or dashicons styles.
        $always_skip = array( 'admin-bar', 'dashicons' );
        if ( in_array( $handle, $always_skip, true ) ) {
            return $tag;
        }
        $excludes = $this->get_exclusion_list( 'exclude_async' );
        if ( $this->is_excluded( $handle, $href, $excludes ) ) {
            return $tag;
        }
        // Skip print stylesheets (already non-blocking).
        if ( 'print' === $media ) {
            return $tag;
        }

        // Safety interlock.
        //
        // Async-loading every stylesheet causes a Flash of Unstyled Content
        // while the preloaded links resolve. We keep the first
        // ASYNC_SAFETY_THRESHOLD eligible stylesheets render-blocking so the
        // page always has real CSS at first paint, and async the remainder for
        // partial benefit. The counter is only incremented for stylesheets
        // that would otherwise be async'd — so excluded handles and print
        // stylesheets don't burn the budget.
        $this->async_count++;
        if ( $this->async_count <= self::ASYNC_SAFETY_THRESHOLD ) {
            return $tag;
        }

        $media_attr = $media ? esc_attr( $media ) : 'all';

        $preload = sprintf(
            '<link rel="preload" as="style" href="%1$s" media="%2$s" onload="this.onload=null;this.rel=\'stylesheet\'">',
            esc_url( $href ),
            $media_attr
        );
        $noscript = sprintf(
            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- noscript fallback for a stylesheet already enqueued via wp_enqueue_style(); this style_loader_tag filter only rewrites its tag, and a noscript fallback cannot be enqueued.
            '<noscript><link rel="stylesheet" href="%1$s" media="%2$s"></noscript>',
            esc_url( $href ),
            $media_attr
        );

        return $preload . "\n" . $noscript . "\n";
    }

    /**
     * Tiny polyfill for older browsers that don't fire onload on preload links.
     *
     * Modern Chrome/Firefox/Safari/Edge all support this natively; the polyfill
     * is small and only activates where needed.
     */
    public function enqueue_loadcss_polyfill() {
        if ( $this->should_skip() ) {
            return;
        }
        ob_start();
        ?>
        /*! loadCSS rel=preload polyfill. [c]2017 Filament Group, Inc. MIT */
        (function(w){"use strict";if(!w.loadCSS){w.loadCSS=function(){}}
        var rp=loadCSS.relpreload={};rp.support=(function(){var ret;try{ret=w.document.createElement("link").relList.supports("preload")}catch(e){ret=!1}
        return function(){return ret}})();rp.bindMediaToggle=function(link){var finalMedia=link.media||"all";function enableStylesheet(){if(link.addEventListener){link.removeEventListener("load",enableStylesheet)}else if(link.attachEvent){link.detachEvent("onload",enableStylesheet)}
        link.setAttribute("onload",null);link.media=finalMedia}
        if(link.addEventListener){link.addEventListener("load",enableStylesheet)}else if(link.attachEvent){link.attachEvent("onload",enableStylesheet)}
        setTimeout(function(){link.rel="stylesheet";link.media="only x"});setTimeout(enableStylesheet,3000)};rp.poly=function(){if(rp.support()){return}
        var links=w.document.getElementsByTagName("link");for(var i=0;i<links.length;i++){var link=links[i];if(link.rel==="preload"&&link.getAttribute("as")==="style"&&!link.getAttribute("data-loadcss")){link.setAttribute("data-loadcss",!0);rp.bindMediaToggle(link)}}};if(!rp.support()){rp.poly();var run=w.setInterval(rp.poly,500);if(w.addEventListener){w.addEventListener("load",function(){rp.poly();w.clearInterval(run)})}else if(w.attachEvent){w.attachEvent("onload",function(){rp.poly();w.clearInterval(run)})}}})(this);
        <?php
        $polyfill = ob_get_clean();
        wp_register_script( 'mbr-performance-loadcss', false, array(), MBRPE_VERSION );
        wp_enqueue_script( 'mbr-performance-loadcss' );
        wp_add_inline_script( 'mbr-performance-loadcss', $polyfill );
    }

    /**
     * Lightly minify inline style tags.
     *
     * @param string $tag
     * @param string $handle
     * @param string $href
     * @param string $media
     * @return string
     */
    public function minify_inline_style( $tag, $handle, $href, $media ) {
        if ( $this->should_skip() || '' !== $href ) {
            return $tag;
        }
        return preg_replace_callback( '/(<style[^>]*>)(.*?)(<\/style>)/is', function ( $m ) {
            $body = $m[2];
            // Strip CSS comments.
            $body = preg_replace( '#/\*.*?\*/#s', '', $body );
            // Collapse whitespace.
            $body = preg_replace( '/\s+/', ' ', $body );
            // Tighten around braces, colons, semicolons.
            $body = preg_replace( '/\s*([{}:;,])\s*/', '$1', $body );
            $body = preg_replace( '/;}/', '}', $body );
            return $m[1] . trim( $body ) . $m[3];
        }, $tag );
    }

    /**
     * Strip ?ver= from style URLs.
     *
     * @param string $src
     * @return string
     */
    public function remove_style_version( $src ) {
        if ( is_string( $src ) && false !== strpos( $src, 'ver=' ) ) {
            $src = remove_query_arg( 'ver', $src );
        }
        return $src;
    }

    /**
     * Dequeue WooCommerce stylesheets on non-shop pages.
     */
    public function disable_woocommerce_css() {
        if ( ! function_exists( 'is_woocommerce' ) ) {
            return;
        }
        if ( ! is_woocommerce() && ! is_cart() && ! is_checkout() && ! is_account_page() ) {
            wp_dequeue_style( 'woocommerce-general' );
            wp_dequeue_style( 'woocommerce-layout' );
            wp_dequeue_style( 'woocommerce-smallscreen' );
            wp_dequeue_style( 'wc-blocks-style' );
            wp_dequeue_style( 'wc-block-style' );
        }
    }

    /**
     * Combine contiguous, same-media, local stylesheets into cached bundles.
     *
     * Walks the dependency-resolved style queue in print order and groups
     * adjacent eligible stylesheets into "runs". A run is broken by any sheet
     * that cannot be combined (external/CDN, conditional, alternate, excluded,
     * a non-screen media type, or one whose file can't be resolved on disk),
     * so the cascade order of the page is preserved exactly. Each run of two
     * or more sheets is concatenated into a single file under
     * uploads/mbr-performance-combine/, the run's first handle (the "carrier")
     * is repointed at that bundle, and the remaining handles are removed.
     * Inline styles attached to merged handles are relocated onto the carrier
     * so nothing is lost.
     *
     * @return void
     */
    public function combine_styles() {
        if ( $this->should_skip() ) {
            return;
        }
        if ( class_exists( 'MBRPE_Critical_CSS' ) && MBRPE_Critical_CSS::is_active() ) {
            return;
        }
        if ( is_feed() || is_embed() ) {
            return;
        }

        $wp_styles = wp_styles();
        if ( ! ( $wp_styles instanceof WP_Styles ) || empty( $wp_styles->queue ) ) {
            return;
        }

        // Resolve the full print order (the queue expanded with dependencies).
        $wp_styles->all_deps( $wp_styles->queue );
        $ordered = $wp_styles->to_do;
        if ( empty( $ordered ) ) {
            return;
        }

        $exclusions = $this->get_exclusion_list( 'exclude_optimization' );
        $is_rtl     = ( 'rtl' === $wp_styles->text_direction );

        // Partition the ordered handles into runs of combinable sheets.
        $runs      = array();
        $current   = array();
        $run_media = null;

        foreach ( $ordered as $handle ) {
            $info = $this->combinable_info( $handle, $exclusions, $is_rtl, $wp_styles );

            if ( false === $info ) {
                // Not combinable: close any open run.
                if ( count( $current ) > 0 ) {
                    $runs[]  = $current;
                    $current = array();
                }
                $run_media = null;
                continue;
            }

            // Combinable: break the run if the media class changes.
            if ( null !== $run_media && $info['media'] !== $run_media && count( $current ) > 0 ) {
                $runs[]  = $current;
                $current = array();
            }
            $run_media = $info['media'];
            $current[] = $info;
        }
        if ( count( $current ) > 0 ) {
            $runs[] = $current;
        }

        // Build a bundle for each run of two or more sheets.
        foreach ( $runs as $run ) {
            if ( count( $run ) < 2 ) {
                continue;
            }
            $this->merge_run( $run, $wp_styles );
        }

        // Force WordPress to recompute the to-do list at print time from the
        // (now-modified) queue and registry.
        $wp_styles->to_do = array();
    }

    /**
     * Decide whether a style handle can be folded into a combined bundle,
     * returning the metadata needed to build it, or false if it cannot.
     *
     * @param string    $handle
     * @param string[]  $exclusions
     * @param bool      $is_rtl
     * @param WP_Styles $wp_styles
     * @return array|false
     */
    private function combinable_info( $handle, $exclusions, $is_rtl, $wp_styles ) {
        if ( ! isset( $wp_styles->registered[ $handle ] ) ) {
            return false;
        }
        $obj = $wp_styles->registered[ $handle ];

        // Never touch the admin bar or dashicons.
        if ( in_array( $handle, array( 'admin-bar', 'dashicons' ), true ) ) {
            return false;
        }

        $src = isset( $obj->src ) ? (string) $obj->src : '';
        if ( '' === $src ) {
            return false; // Inline-only / meta handle (e.g. global-styles).
        }

        // Conditional (IE) and alternate stylesheets are left alone.
        if ( ! empty( $obj->extra['conditional'] ) || ! empty( $obj->extra['alt'] ) ) {
            return false;
        }

        // Honour the shared exclusion list (handle or URL substring).
        if ( $this->is_excluded( $handle, $src, $exclusions ) ) {
            return false;
        }

        // Only combine "screen" stylesheets; print and explicit media queries
        // are left untouched to avoid unsafe @media wrapping.
        $media = $this->normalise_media( isset( $obj->args ) ? $obj->args : 'all' );
        if ( null === $media ) {
            return false;
        }

        // Resolve the href WordPress would actually print (RTL-aware), then map
        // it to a readable local file. Anything we can't resolve breaks the run.
        $href = $this->resolve_href( $obj, $src, $is_rtl );
        $path = self::url_to_path( $href );
        if ( '' === $path || ! is_readable( $path ) ) {
            return false;
        }

        return array(
            'handle' => $handle,
            'obj'    => $obj,
            'href'   => $href,
            'path'   => $path,
            'media'  => $media,
            'ver'    => isset( $obj->ver ) ? (string) $obj->ver : '',
        );
    }

    /**
     * Normalise a stylesheet media attribute to a combinable class.
     *
     * Returns 'all' for screen-applicable sheets (empty, "all" or "screen"),
     * or null for print stylesheets and explicit media queries, which are
     * never combined.
     *
     * @param string $media
     * @return string|null
     */
    private function normalise_media( $media ) {
        $media = strtolower( trim( (string) $media ) );
        if ( '' === $media || 'all' === $media || 'screen' === $media ) {
            return 'all';
        }
        return null;
    }

    /**
     * Resolve the href WordPress would print for a stylesheet, accounting for
     * the right-to-left replacement it performs on RTL sites.
     *
     * @param object $obj    Registered style object.
     * @param string $src    Registered src.
     * @param bool   $is_rtl Whether the site is being rendered RTL.
     * @return string
     */
    private function resolve_href( $obj, $src, $is_rtl ) {
        $href = $src;
        if ( $is_rtl && ! empty( $obj->extra['rtl'] ) ) {
            $rtl    = $obj->extra['rtl'];
            $suffix = isset( $obj->extra['suffix'] ) ? (string) $obj->extra['suffix'] : '';
            if ( 'replace' === $rtl ) {
                $href = str_replace( "{$suffix}.css", "-rtl{$suffix}.css", $src );
            } elseif ( is_string( $rtl ) ) {
                $href = $rtl;
            }
        }
        return $href;
    }

    /**
     * Map a same-origin stylesheet URL to a readable local filesystem path.
     *
     * Returns an empty string for external URLs, unresolvable URLs, or any
     * path that escapes the mapped base directory (path-traversal guard).
     *
     * @param string $url
     * @return string
     */
    public static function url_to_path( $url ) {
        if ( ! is_string( $url ) || '' === $url ) {
            return '';
        }
        // Drop any query string or fragment.
        $url = preg_replace( '/[?#].*$/', '', $url );

        // Protocol-relative → adopt the current request scheme.
        if ( 0 === strpos( $url, '//' ) ) {
            $url = ( is_ssl() ? 'https:' : 'http:' ) . $url;
        } elseif ( 0 === strpos( $url, '/' ) ) {
            // Root-relative → resolve against the site root.
            $url = site_url( $url );
        }

        // Compare scheme-insensitively so http/https mismatches still map.
        $strip_scheme = static function ( $value ) {
            return preg_replace( '#^https?:#i', '', (string) $value );
        };
        $url_ns = $strip_scheme( $url );

        $pairs = array(
            array( $strip_scheme( content_url() ),   WP_CONTENT_DIR ),
            array( $strip_scheme( includes_url() ),  ABSPATH . WPINC ),
            array( $strip_scheme( site_url( '/' ) ), ABSPATH ),
        );

        foreach ( $pairs as $pair ) {
            $base_url = rtrim( $pair[0], '/' );
            $base_dir = $pair[1];
            if ( '' === $base_url || 0 !== strpos( $url_ns, $base_url ) ) {
                continue;
            }
            $rel  = substr( $url_ns, strlen( $base_url ) );
            $path = rtrim( $base_dir, '/\\' ) . str_replace( '/', DIRECTORY_SEPARATOR, $rel );
            $real = realpath( $path );
            if ( false === $real || ! is_file( $real ) ) {
                continue;
            }
            // Containment guard: the resolved file must live inside the base.
            $base_real = realpath( $base_dir );
            if ( false !== $base_real && 0 === strpos( $real, $base_real ) ) {
                return $real;
            }
        }

        return '';
    }

    /**
     * Fold a run of two or more stylesheets into a single cached bundle and
     * repoint the run's carrier handle at it.
     *
     * @param array     $run       Ordered list of combinable_info() entries.
     * @param WP_Styles $wp_styles
     * @return void
     */
    private function merge_run( $run, $wp_styles ) {
        $carrier_obj = $run[0]['obj'];

        // Fingerprint the run so the bundle is cached and only rebuilt when a
        // member's file (or version) actually changes.
        $fingerprint = $this->run_fingerprint( $run );
        $filename    = 'mbrpe-combined-' . $fingerprint . '.css';

        $dir = self::get_combine_dir();
        if ( '' === $dir ) {
            return; // Couldn't prepare the cache directory; leave sheets as-is.
        }
        $filepath = $dir['path'] . '/' . $filename;
        $fileurl  = $dir['url'] . '/' . $filename;

        // Build the bundle only if it isn't already cached on disk.
        if ( ! is_file( $filepath ) ) {
            $bundle = $this->build_bundle( $run );
            if ( '' === $bundle ) {
                return; // Build produced nothing — leave the originals alone.
            }
            if ( ! self::write_file( $filepath, $bundle ) ) {
                return; // Write failed — leave the original sheets untouched.
            }
        }

        // Relocate inline ("after") styles from merged members onto the
        // carrier so they still print, then collect the merged handles.
        $merged_handles = array();
        $count          = count( $run );
        for ( $i = 1; $i < $count; $i++ ) {
            $member = $run[ $i ]['obj'];
            $merged_handles[] = $run[ $i ]['handle'];

            if ( ! empty( $member->extra['after'] ) ) {
                if ( empty( $carrier_obj->extra['after'] ) ) {
                    $carrier_obj->extra['after'] = array();
                }
                foreach ( (array) $member->extra['after'] as $after ) {
                    $carrier_obj->extra['after'][] = $after;
                }
            }
        }

        // Repoint the carrier at the bundle.
        $carrier_obj->src  = $fileurl;
        $carrier_obj->ver  = null;  // The md5 filename already cache-busts.
        $carrier_obj->args = 'all'; // Broadest safe media for a general bundle.
        unset( $carrier_obj->extra['rtl'], $carrier_obj->extra['suffix'] );

        // Record the bundle for an early preload hint, if preloading is on.
        if ( $this->do_combine_preload ) {
            $this->combine_preload_urls[] = $fileurl;
        }

        // Remove every merged member from the queue, the registry, and any
        // remaining dependency lists so nothing re-adds them.
        foreach ( $merged_handles as $mh ) {
            foreach ( $wp_styles->registered as $reg ) {
                if ( ! empty( $reg->deps ) && in_array( $mh, $reg->deps, true ) ) {
                    $reg->deps = array_values( array_diff( $reg->deps, array( $mh ) ) );
                }
            }
            $wp_styles->dequeue( $mh );
            $wp_styles->remove( $mh );
        }
    }

    /**
     * Build a stable fingerprint for a run from its members' paths, modified
     * times and versions, the minify flag and the plugin version.
     *
     * @param array $run
     * @return string
     */
    private function run_fingerprint( $run ) {
        $parts   = array( MBRPE_VERSION );
        $parts[] = $this->get_option( 'minify_css' ) ? 'min1' : 'min0';
        foreach ( $run as $member ) {
            $mtime   = is_file( $member['path'] ) ? filemtime( $member['path'] ) : 0;
            $parts[] = $member['handle'] . '|' . $member['path'] . '|' . ( $mtime ? $mtime : '0' ) . '|' . $member['ver'];
        }
        return md5( implode( "\n", $parts ) );
    }

    /**
     * Concatenate a run's stylesheets into a single CSS string.
     *
     * Strips BOMs and @charset rules, rewrites relative url()/@import targets
     * to absolute, hoists @import statements to the top, optionally minifies,
     * and prepends a single UTF-8 @charset.
     *
     * @param array $run
     * @return string Combined CSS, or an empty string on failure.
     */
    private function build_bundle( $run ) {
        $minify  = (bool) $this->get_option( 'minify_css' );
        $imports = array();
        $bodies  = array();

        foreach ( $run as $member ) {
            // The path was validated and confirmed readable in combinable_info().
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading the plugin's own validated local stylesheet from disk; wp_remote_get is for remote HTTP and WP_Filesystem::get_contents would force credential prompts for a routine local read.
            $css = file_get_contents( $member['path'] );
            if ( false === $css || '' === $css ) {
                continue;
            }

            // Strip a leading UTF-8 byte-order mark.
            $css = preg_replace( '/^\xEF\xBB\xBF/', '', $css );

            // Drop @charset rules; one canonical UTF-8 charset is emitted below.
            $css = preg_replace( '/@charset\s+["\'][^"\']*["\']\s*;/i', '', $css );

            // Rewrite relative url()/@import targets against this sheet's dir.
            $css = self::rewrite_css_urls( $css, $member['href'] );

            // Hoist @import statements — they must precede all normal rules.
            $css = preg_replace_callback(
                '/@import\b[^;]+;/i',
                static function ( $match ) use ( &$imports ) {
                    $imports[] = trim( $match[0] );
                    return '';
                },
                $css
            );

            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                $bodies[] = "/* mbrpe: {$member['handle']} */\n" . $css;
            } else {
                $bodies[] = $css;
            }
        }

        if ( empty( $bodies ) ) {
            return '';
        }

        $out = '';
        if ( ! empty( $imports ) ) {
            $out .= implode( "\n", array_unique( $imports ) ) . "\n";
        }
        $out .= implode( "\n", $bodies );

        if ( $minify ) {
            $out = self::minify_css_string( $out );
        }

        // A single UTF-8 @charset must lead the file, before any rule.
        return '@charset "UTF-8";' . "\n" . $out;
    }

    /**
     * Rewrite relative url() and quoted @import targets in a stylesheet to
     * absolute URLs, based on the source stylesheet's own directory.
     *
     * Absolute, root-relative, protocol-relative, data: and anchor targets are
     * left untouched.
     *
     * @param string $css
     * @param string $base_href Source stylesheet URL.
     * @return string
     */
    public static function rewrite_css_urls( $css, $base_href ) {
        $base     = preg_replace( '/[?#].*$/', '', (string) $base_href );
        $base_dir = preg_replace( '#/[^/]*$#', '/', $base ); // Strip filename → dir URL.

        $is_absolute = static function ( $target ) {
            return '' === $target
                || 0 === strpos( $target, 'data:' )
                || 0 === strpos( $target, '#' )
                || 0 === strpos( $target, 'http://' )
                || 0 === strpos( $target, 'https://' )
                || 0 === strpos( $target, '//' )
                || 0 === strpos( $target, '/' );
        };

        // url( ... )
        $css = preg_replace_callback(
            '/url\(\s*([\'"]?)([^\'")]+)\1\s*\)/i',
            function ( $match ) use ( $base_dir, $is_absolute ) {
                $target = trim( $match[2] );
                if ( $is_absolute( $target ) ) {
                    return $match[0];
                }
                return 'url(' . $match[1] . self::resolve_relative_url( $base_dir, $target ) . $match[1] . ')';
            },
            $css
        );

        // @import "..."  (bare-string form, without url()).
        $css = preg_replace_callback(
            '/@import\s+([\'"])([^\'"]+)\1/i',
            function ( $match ) use ( $base_dir, $is_absolute ) {
                $target = trim( $match[2] );
                if ( $is_absolute( $target ) ) {
                    return $match[0];
                }
                return '@import ' . $match[1] . self::resolve_relative_url( $base_dir, $target ) . $match[1];
            },
            $css
        );

        return $css;
    }

    /**
     * Resolve a relative URL against a base directory URL, collapsing any
     * ./ and ../ segments.
     *
     * @param string $base_dir_url Directory URL ending in a slash.
     * @param string $rel          Relative target.
     * @return string
     */
    public static function resolve_relative_url( $base_dir_url, $rel ) {
        $abs   = $base_dir_url . $rel;
        $parts = wp_parse_url( $abs );
        if ( empty( $parts ) || empty( $parts['path'] ) ) {
            return $abs;
        }

        $scheme = isset( $parts['scheme'] ) ? $parts['scheme'] . '://' : '//';
        $host   = isset( $parts['host'] ) ? $parts['host'] : '';
        $port   = isset( $parts['port'] ) ? ':' . $parts['port'] : '';

        $stack = array();
        foreach ( explode( '/', $parts['path'] ) as $segment ) {
            if ( '..' === $segment ) {
                array_pop( $stack );
            } elseif ( '.' !== $segment && '' !== $segment ) {
                $stack[] = $segment;
            }
        }
        $path  = '/' . implode( '/', $stack );
        $query = isset( $parts['query'] ) ? '?' . $parts['query'] : '';
        $frag  = isset( $parts['fragment'] ) ? '#' . $parts['fragment'] : '';

        return $scheme . $host . $port . $path . $query . $frag;
    }

    /**
     * Conservatively minify a CSS string.
     *
     * Strings and url() values are token-protected before the comment and
     * whitespace passes so they can't be corrupted, then restored. Only
     * whitespace around structural punctuation is tightened — spacing inside
     * values (e.g. calc()) is preserved.
     *
     * @param string $css
     * @return string
     */
    public static function minify_css_string( $css ) {
        $tokens = array();
        $index  = 0;

        $protect = static function ( $pattern, $buffer ) use ( &$tokens, &$index ) {
            $result = preg_replace_callback(
                $pattern,
                static function ( $match ) use ( &$tokens, &$index ) {
                    $key            = "\0MBRPECSS{$index}\0";
                    $tokens[ $key ] = $match[0];
                    $index++;
                    return $key;
                },
                $buffer
            );
            return null === $result ? $buffer : $result;
        };

        // Protect quoted strings and url() values.
        $css = $protect( '/"(?:\\\\.|[^"\\\\])*"/s', $css );
        $css = $protect( "/'(?:\\\\.|[^'\\\\])*'/s", $css );
        $css = $protect( '/url\(\s*[^)]*\)/i', $css );

        // Strip comments, collapse whitespace, tighten around punctuation.
        $css = preg_replace( '#/\*.*?\*/#s', '', $css );
        $css = preg_replace( '/\s+/', ' ', $css );
        $css = preg_replace( '/\s*([{}:;,])\s*/', '$1', $css );
        $css = str_replace( ';}', '}', $css );
        $css = trim( (string) $css );

        if ( ! empty( $tokens ) ) {
            $css = strtr( $css, $tokens );
        }

        return $css;
    }

    /**
     * Ensure the combine cache directory exists and return its path and URL.
     *
     * @return array|string array{path:string,url:string} on success, or an
     *                      empty string on failure.
     */
    public static function get_combine_dir() {
        $upload = wp_upload_dir();
        if ( ! empty( $upload['error'] ) || empty( $upload['basedir'] ) ) {
            return '';
        }

        $path = trailingslashit( $upload['basedir'] ) . self::COMBINE_DIRNAME;
        $url  = trailingslashit( $upload['baseurl'] ) . self::COMBINE_DIRNAME;

        if ( ! is_dir( $path ) ) {
            if ( ! wp_mkdir_p( $path ) ) {
                return '';
            }
            // Drop a blank index file so the directory can't be browsed.
            self::write_file( $path . '/index.html', '' );
        }

        return array(
            'path' => untrailingslashit( $path ),
            'url'  => untrailingslashit( $url ),
        );
    }

    /**
     * Write a file to the plugin's own cache directory.
     *
     * @param string $path
     * @param string $content
     * @return bool
     */
    public static function write_file( $path, $content ) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Writing the plugin's own generated CSS bundle to its private cache directory under wp-uploads; WP_Filesystem::put_contents would force credential prompts on some hosts for a routine front-end cache write.
        $bytes = file_put_contents( $path, $content, LOCK_EX );
        return false !== $bytes;
    }

    /**
     * Delete cached bundles in the combine directory.
     *
     * Called on settings save/reset and plugin deactivation so stale bundles
     * are never served after the configuration or stylesheets change, and from
     * the admin "clear cache" controls.
     *
     * @param string $type 'css', 'js', or 'all' (default) — which bundles to delete.
     * @return int Number of files deleted.
     */
    public static function purge_combine_cache( $type = 'all' ) {
        $upload = wp_upload_dir();
        if ( empty( $upload['basedir'] ) ) {
            return 0;
        }
        $dir = trailingslashit( $upload['basedir'] ) . self::COMBINE_DIRNAME;
        if ( ! is_dir( $dir ) ) {
            return 0;
        }

        $files = scandir( $dir );
        if ( false === $files ) {
            return 0;
        }

        $deleted = 0;
        foreach ( $files as $file ) {
            if ( '.' === $file || '..' === $file ) {
                continue;
            }
            $filepath = $dir . '/' . $file;
            if ( ! is_file( $filepath ) ) {
                continue;
            }
            if ( 'all' !== $type
                && strtolower( pathinfo( $file, PATHINFO_EXTENSION ) ) !== $type ) {
                continue;
            }
            wp_delete_file( $filepath );
            $deleted++;
        }
        return $deleted;
    }

    /**
     * Count cached combined bundles by type, with total bytes per type.
     *
     * @return array{css:int,css_bytes:int,js:int,js_bytes:int}
     */
    public static function combine_cache_stats() {
        $stats = array(
            'css'       => 0,
            'css_bytes' => 0,
            'js'        => 0,
            'js_bytes'  => 0,
        );

        $upload = wp_upload_dir();
        if ( empty( $upload['basedir'] ) ) {
            return $stats;
        }
        $dir = trailingslashit( $upload['basedir'] ) . self::COMBINE_DIRNAME;
        if ( ! is_dir( $dir ) ) {
            return $stats;
        }

        $files = scandir( $dir );
        if ( false === $files ) {
            return $stats;
        }

        foreach ( $files as $file ) {
            if ( '.' === $file || '..' === $file ) {
                continue;
            }
            $filepath = $dir . '/' . $file;
            if ( ! is_file( $filepath ) ) {
                continue;
            }
            $ext = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
            if ( 'css' === $ext ) {
                $stats['css']++;
                $stats['css_bytes'] += (int) filesize( $filepath );
            } elseif ( 'js' === $ext ) {
                $stats['js']++;
                $stats['js_bytes'] += (int) filesize( $filepath );
            }
        }
        return $stats;
    }

    /**
     * Print early preload hints for the combined CSS bundles built this
     * request. Hooked into wp_head before the stylesheet links are printed.
     *
     * @return void
     */
    public function print_combine_preloads() {
        if ( class_exists( 'MBRPE_Critical_CSS' ) && MBRPE_Critical_CSS::is_active() ) {
            return;
        }
        if ( empty( $this->combine_preload_urls ) ) {
            return;
        }
        foreach ( array_unique( $this->combine_preload_urls ) as $url ) {
            echo '<link rel="preload" href="' . esc_url( $url ) . '" as="style" />' . "\n";
        }
    }
}
