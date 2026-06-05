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

        // Combine JS is intentionally a no-op — a safe implementation is a
        // substantial separate engineering effort. The UI option is preserved
        // for forward compatibility; we surface an admin notice if a user
        // enables it so they aren't misled.
        if ( $this->get_option( 'combine_javascript' ) ) {
            add_action( 'admin_notices', array( $this, 'notice_combine_unavailable' ) );
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
     * Move all enqueued scripts to the footer (with exclusions).
     */
    public function move_scripts_to_footer() {
        if ( $this->should_skip() ) {
            return;
        }

        global $wp_scripts;
        if ( ! ( $wp_scripts instanceof WP_Scripts ) ) {
            return;
        }

        $excludes = $this->get_exclusion_list( 'exclude_footer' );

        foreach ( $wp_scripts->registered as $handle => $script ) {
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
     * Admin notice: Combine JS isn't implemented.
     */
    public function notice_combine_unavailable() {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen || false === strpos( (string) $screen->id, 'mbr-performance' ) ) {
            return;
        }
        echo '<div class="notice notice-warning"><p>'
            . esc_html__( 'Combine JavaScript is not yet implemented in this release. The toggle is preserved for forward compatibility but has no effect. Use Defer, Delay or Move-to-Footer instead.', 'mbr-performance' )
            . '</p></div>';
    }
}
