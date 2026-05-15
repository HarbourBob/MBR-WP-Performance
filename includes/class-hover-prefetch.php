<?php
/**
 * Hover Prefetch (instant.page-style)
 *
 * On link hover/touchstart, prefetches the destination so the click feels
 * instant. Complements the existing Speculative Loading (Speculation Rules API)
 * for browsers that don't yet support it.
 *
 * Runtime is the canonical instant.page script (MIT licensed by Alexandre Dieulot).
 *
 * @package MBR_WP_Performance
 * @since   1.12.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBR_WP_Performance_Hover_Prefetch {

    /**
     * Single instance.
     *
     * @var MBR_WP_Performance_Hover_Prefetch
     */
    private static $instance = null;

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
        $opts = mbr_wp_performance()->get_options( 'preloading' );
        if ( empty( $opts['hover_prefetch'] ) ) {
            return;
        }
        if ( is_admin() ) {
            return;
        }
        add_action( 'wp_footer', array( $this, 'output_runtime' ), 99 );
    }

    /**
     * Output the runtime.
     *
     * Honours Save-Data: when the visitor has Save-Data on, skip prefetching.
     */
    public function output_runtime() {
        // Server-side bail if browser sent Save-Data: on.
        if ( isset( $_SERVER['HTTP_SAVE_DATA'] ) && 'on' === strtolower( (string) $_SERVER['HTTP_SAVE_DATA'] ) ) {
            return;
        }
        ?>
        <script id="mbr-hover-prefetch">
        /*! instant.page v5.2.0 - (C) 2019-2023 Alexandre Dieulot - https://instant.page/license MIT */
        (function(){var t,e,n,o,i,a=null,s=65,c=new Set;function r(t){var e=t.target.closest("a");p(e)&&u(e.href)}function l(t){var e=t.target.closest("a");p(e)&&(a=setTimeout(function(){u(e.href);a=null},s))}function d(t){if(a){var e=t.target.closest("a");e&&clearTimeout(a)}}function p(t){if(!t||!t.href)return false;if(c.has(t.href))return false;var e=new URL(t.href);if(e.origin!==location.origin)return false;if(["http:","https:"].indexOf(e.protocol)===-1)return false;if(e.protocol==="http:"&&location.protocol==="https:")return false;if(!o&&e.search&&!e.pathname.match(/\.html?$/i))return false;if(e.hash&&e.pathname+e.search===location.pathname+location.search)return false;if("instantAllowQueryString"in t.dataset===false&&!o&&e.search)return false;if("instantAllowExternalLinks"in t.dataset===false&&!i&&e.origin!==location.origin)return false;if(!HTMLAnchorElement.prototype.hasOwnProperty("noModule"))return false;return true}function u(t){if(c.has(t))return;var e=document.createElement("link");e.rel="prefetch";e.href=t;e.as="document";document.head.appendChild(e);c.add(t)}var m=document.createElement("link");var f=m.relList&&m.relList.supports&&m.relList.supports("prefetch")&&window.IntersectionObserver&&"isIntersecting"in IntersectionObserverEntry.prototype;if(!f)return;var v=navigator.connection;if(v){if(v.saveData)return;if(/2g/.test(v.effectiveType))return}var h=document.body;var b=document.body.dataset;t="instantWhitelist"in b;e="instantMousedownShortcut"in b;n=true;o="instantAllowQueryString"in b;i="instantAllowExternalLinks"in b;var w=true;if("instantIntensity"in b){var x=b.instantIntensity;if(x.substr(0,9)=="viewport"){n=false;if("viewport"==x){if(document.documentElement.clientWidth*document.documentElement.clientHeight<450000){w=true}}else if("viewport-all"==x){w=true}}else if(x.substr(0,5)=="mousedown"){n=false;if("mousedown-only"==x){e=false}}else if(!isNaN(parseInt(x))){s=parseInt(x)}}if(n){h.addEventListener("touchstart",r,{capture:true,passive:true});h.addEventListener("mouseover",l,{capture:true,passive:true});h.addEventListener("mouseout",d,{capture:true,passive:true})}if(e){h.addEventListener("mousedown",r,{capture:true,passive:true})}if(w){var y=new IntersectionObserver(function(e){e.forEach(function(e){if(e.isIntersecting){var n=e.target;y.unobserve(n);u(n.href)}})},{threshold:0.0});document.addEventListener("DOMContentLoaded",function(){var e=document.querySelectorAll("a");Array.from(e).forEach(function(e){if(t&&!("instant"in e.dataset))return;if(p(e))y.observe(e)})})}})();
        </script>
        <?php
    }
}
