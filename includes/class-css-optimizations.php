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
        if ( $this->get_option( 'async_css' ) ) {
            add_filter( 'style_loader_tag', array( $this, 'async_stylesheet' ), 99, 4 );
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_loadcss_polyfill' ) );
        }

        // Minify inline CSS (style tags only — external files are typically minified).
        if ( $this->get_option( 'minify_css' ) ) {
            add_filter( 'style_loader_tag', array( $this, 'minify_inline_style' ), 100, 4 );
        }

        // Combine CSS is a no-op for the same reason as Combine JS.
        if ( $this->get_option( 'combine_css' ) ) {
            add_action( 'admin_notices', array( $this, 'notice_combine_unavailable' ) );
        }

        // Remove unused CSS is a no-op — needs a proper DOM scanner.
        if ( $this->get_option( 'remove_unused_css' ) ) {
            add_action( 'admin_notices', array( $this, 'notice_remove_unused_unavailable' ) );
        }

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
     * Notice: Combine CSS not yet available.
     */
    public function notice_combine_unavailable() {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen || false === strpos( (string) $screen->id, 'mbr-performance' ) ) {
            return;
        }
        echo '<div class="notice notice-warning"><p>'
            . esc_html__( 'Combine CSS is not yet implemented in this release. The toggle is preserved for forward compatibility but has no effect. Async CSS gives similar benefits without the risk.', 'mbr-performance' )
            . '</p></div>';
    }

    /**
     * Notice: Remove Unused CSS not yet available.
     */
    public function notice_remove_unused_unavailable() {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen || false === strpos( (string) $screen->id, 'mbr-performance' ) ) {
            return;
        }
        echo '<div class="notice notice-warning"><p>'
            . esc_html__( 'Remove Unused CSS is not yet implemented in this release. For per-page asset control, see the MBR Advanced Asset Manager plugin.', 'mbr-performance' )
            . '</p></div>';
    }
}
