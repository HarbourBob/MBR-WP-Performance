<?php
/**
 * CSS Optimizations
 *
 * Implements the backend logic for the CSS tab toggles.
 *
 * @package MBR_WP_Performance
 * @since   1.12.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBR_WP_Performance_CSS_Optimizations {

    /**
     * Single instance.
     *
     * @var MBR_WP_Performance_CSS_Optimizations
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
     * @return MBR_WP_Performance_CSS_Optimizations
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
        $this->options = mbr_wp_performance()->get_options( 'css' );
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
        // Inline critical CSS in <head>, then async the rest.
        if ( $this->get_option( 'inline_critical_css' ) ) {
            add_action( 'wp_head', array( $this, 'output_critical_css' ), 1 );
        }

        // Async non-critical stylesheets via the preload+onload pattern.
        if ( $this->get_option( 'async_css' ) ) {
            add_filter( 'style_loader_tag', array( $this, 'async_stylesheet' ), 99, 4 );
            add_action( 'wp_head', array( $this, 'output_loadcss_polyfill' ), 99 );
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

        // Disable Elementor's Google Fonts requests entirely.
        if ( $this->get_option( 'disable_elementor_fonts' ) && defined( 'ELEMENTOR_VERSION' ) ) {
            add_filter( 'elementor/frontend/print_google_fonts', '__return_false' );
        }

        // Disable WooCommerce stylesheets on non-shop pages.
        if ( $this->get_option( 'disable_woocommerce_css' ) && class_exists( 'WooCommerce' ) ) {
            add_action( 'wp_enqueue_scripts', array( $this, 'disable_woocommerce_css' ), 99 );
        }
    }

    /**
     * Output the saved critical CSS inline at the top of <head>.
     *
     * Both the auto-generator (in class-admin.php) and the user-editable
     * textarea write to options[css][critical_css]. We emit that here, well
     * before any <link rel="stylesheet"> tags, so above-the-fold styling
     * paints immediately even when the full stylesheets are async-loaded.
     */
    public function output_critical_css() {
        if ( $this->should_skip() ) {
            return;
        }
        $css = $this->get_option( 'critical_css', '' );
        // Backwards-compatibility: emit any legacy generator output that may
        // still be stored under the old key on sites upgraded mid-cycle.
        if ( ! is_string( $css ) || '' === trim( $css ) ) {
            $css = $this->get_option( 'critical_css_content', '' );
        }
        if ( ! is_string( $css ) || '' === trim( $css ) ) {
            return;
        }
        echo "<style id=\"mbr-wp-performance-critical-css\">\n" . $css . "\n</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
        // Don't async the critical CSS we just inlined, or admin-bar styles.
        $always_skip = array( 'mbr-wp-performance-critical-css', 'admin-bar', 'dashicons' );
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

        $media_attr = $media ? esc_attr( $media ) : 'all';

        $preload = sprintf(
            '<link rel="preload" as="style" href="%1$s" media="%2$s" onload="this.onload=null;this.rel=\'stylesheet\'">',
            esc_url( $href ),
            $media_attr
        );
        $noscript = sprintf(
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
    public function output_loadcss_polyfill() {
        if ( $this->should_skip() ) {
            return;
        }
        ?>
        <script id="mbr-wp-performance-loadcss-polyfill">
        /*! loadCSS rel=preload polyfill. [c]2017 Filament Group, Inc. MIT */
        (function(w){"use strict";if(!w.loadCSS){w.loadCSS=function(){}}
        var rp=loadCSS.relpreload={};rp.support=(function(){var ret;try{ret=w.document.createElement("link").relList.supports("preload")}catch(e){ret=!1}
        return function(){return ret}})();rp.bindMediaToggle=function(link){var finalMedia=link.media||"all";function enableStylesheet(){if(link.addEventListener){link.removeEventListener("load",enableStylesheet)}else if(link.attachEvent){link.detachEvent("onload",enableStylesheet)}
        link.setAttribute("onload",null);link.media=finalMedia}
        if(link.addEventListener){link.addEventListener("load",enableStylesheet)}else if(link.attachEvent){link.attachEvent("onload",enableStylesheet)}
        setTimeout(function(){link.rel="stylesheet";link.media="only x"});setTimeout(enableStylesheet,3000)};rp.poly=function(){if(rp.support()){return}
        var links=w.document.getElementsByTagName("link");for(var i=0;i<links.length;i++){var link=links[i];if(link.rel==="preload"&&link.getAttribute("as")==="style"&&!link.getAttribute("data-loadcss")){link.setAttribute("data-loadcss",!0);rp.bindMediaToggle(link)}}};if(!rp.support()){rp.poly();var run=w.setInterval(rp.poly,500);if(w.addEventListener){w.addEventListener("load",function(){rp.poly();w.clearInterval(run)})}else if(w.attachEvent){w.attachEvent("onload",function(){rp.poly();w.clearInterval(run)})}}})(this);
        </script>
        <?php
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
        if ( ! $screen || false === strpos( (string) $screen->id, 'mbr-wp-performance' ) ) {
            return;
        }
        echo '<div class="notice notice-warning"><p>'
            . esc_html__( 'Combine CSS is not yet implemented in this release. The toggle is preserved for forward compatibility but has no effect. Async CSS and Inline Critical CSS together give similar benefits without the risk.', 'mbr-wp-performance' )
            . '</p></div>';
    }

    /**
     * Notice: Remove Unused CSS not yet available.
     */
    public function notice_remove_unused_unavailable() {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen || false === strpos( (string) $screen->id, 'mbr-wp-performance' ) ) {
            return;
        }
        echo '<div class="notice notice-warning"><p>'
            . esc_html__( 'Remove Unused CSS is not yet implemented in this release. For per-page asset control, see the MBR Advanced Asset Manager plugin.', 'mbr-wp-performance' )
            . '</p></div>';
    }
}
