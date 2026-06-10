<?php
/**
 * Caching Plugin Conflict Detector
 *
 * MBR Performance is designed to play nicely alongside dedicated caching
 * plugins (it deliberately does NOT do page caching). But several of its
 * optimisations (defer/delay JS, async CSS, browser-cache headers, gzip)
 * overlap with features in WP Rocket, W3 Total Cache, LiteSpeed Cache,
 * FlyingPress, Autoptimize, Perfmatters and WP Super Cache. When the same
 * thing is enabled in two places at once the result is often broken JS, no
 * CSS, or PageSpeed scores that don't improve.
 *
 * This module detects known caching plugins and surfaces a single admin
 * notice listing which overlapping options to disable in MBR Performance.
 *
 * @package MBRPE
 * @since   1.12.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBRPE_Conflict_Detector {

    /**
     * Single instance.
     *
     * @var MBRPE_Conflict_Detector
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
        add_action( 'admin_notices', array( $this, 'maybe_show_notice' ) );
    }

    /**
     * Catalog of known caching/optimisation plugins.
     *
     * Each entry: plugin slug → detection signal + label + which MBR options it overlaps.
     *
     * @return array
     */
    public static function catalog() {
        return array(
            'wp-rocket' => array(
                'label'      => 'WP Rocket',
                'class'      => 'WP_Rocket\Plugin',
                'constant'   => 'WP_ROCKET_VERSION',
                'function'   => null,
                'overlaps'   => array(
                    'css.async_css'                  => 'Async CSS (Rocket: Load CSS Asynchronously)',
                    'css.minify_css'                 => 'Minify CSS (Rocket: Minify CSS files)',
                    'css.remove_unused_css'          => 'Remove Unused CSS (Rocket: Remove Unused CSS)',
                    'javascript.defer_javascript'    => 'Defer JS (Rocket: Load JS Deferred)',
                    'javascript.delay_javascript'    => 'Delay JS (Rocket: Delay JavaScript Execution)',
                    'javascript.minify_javascript'   => 'Minify JS (Rocket: Minify JavaScript files)',
                    'lazy_loading.video_facade'      => 'Video Facade (Rocket: LazyLoad for iframes/videos)',
                    'server_headers.browser_cache'   => 'Browser Cache (Rocket: handled internally)',
                    'server_headers.gzip_compression'=> 'GZIP (Rocket: handled internally)',
                ),
            ),
            'w3-total-cache' => array(
                'label'      => 'W3 Total Cache',
                'class'      => null,
                'constant'   => 'W3TC',
                'function'   => null,
                'overlaps'   => array(
                    'css.minify_css'                 => 'Minify CSS (W3TC: Minify CSS)',
                    'javascript.minify_javascript'   => 'Minify JS (W3TC: Minify JS)',
                    'core.minify_html'               => 'Minify HTML (W3TC: Minify HTML & XML)',
                    'javascript.defer_javascript'    => 'Defer JS (W3TC: JS Embed Method = Async/Defer)',
                    'server_headers.browser_cache'   => 'Browser Cache (W3TC: Browser Cache)',
                    'server_headers.gzip_compression'=> 'GZIP (W3TC: HTTP compression)',
                ),
            ),
            'litespeed-cache' => array(
                'label'      => 'LiteSpeed Cache',
                'class'      => 'LiteSpeed\Core',
                'constant'   => 'LSCWP_V',
                'function'   => null,
                'overlaps'   => array(
                    'css.minify_css'                 => 'Minify CSS (LSCache: CSS Minify)',
                    'css.async_css'                  => 'Async CSS (LSCache: CSS Async Loading)',
                    'javascript.defer_javascript'    => 'Defer JS (LSCache: JS Defer)',
                    'javascript.delay_javascript'    => 'Delay JS (LSCache: JS Delayed Load)',
                    'javascript.minify_javascript'   => 'Minify JS (LSCache: JS Minify)',
                    'core.minify_html'               => 'Minify HTML (LSCache: HTML Minify)',
                    'webp.auto_convert'              => 'WebP conversion (LSCache: Image WebP Replacement)',
                    'server_headers.browser_cache'   => 'Browser Cache (LSCache: handled internally)',
                ),
            ),
            'flying-press' => array(
                'label'      => 'FlyingPress',
                'class'      => 'FlyingPress\Plugin',
                'constant'   => 'FLYING_PRESS_VERSION',
                'function'   => null,
                'overlaps'   => array(
                    'css.async_css'                  => 'Async CSS (FlyingPress: Load CSS Async)',
                    'javascript.defer_javascript'    => 'Defer JS (FlyingPress: Defer JavaScript)',
                    'javascript.delay_javascript'    => 'Delay JS (FlyingPress: Delay JavaScript)',
                    'lazy_loading.video_facade'      => 'Video Facade (FlyingPress: Self-host YouTube Placeholder)',
                ),
            ),
            'wp-super-cache' => array(
                'label'      => 'WP Super Cache',
                'class'      => null,
                'constant'   => 'WPSC_VERSION_NUM',
                'function'   => 'wpsc_init',
                'overlaps'   => array(
                    'server_headers.gzip_compression'=> 'GZIP (Super Cache: Compress pages)',
                ),
            ),
            'perfmatters' => array(
                'label'      => 'Perfmatters',
                'class'      => null,
                'constant'   => 'PERFMATTERS_VERSION',
                'function'   => null,
                'overlaps'   => array(
                    'javascript.defer_javascript'    => 'Defer JS (Perfmatters: Defer JavaScript)',
                    'javascript.delay_javascript'    => 'Delay JS (Perfmatters: Delay JavaScript)',
                    'css.remove_unused_css'          => 'Remove Unused CSS (Perfmatters)',
                    'javascript.remove_jquery'       => 'Remove jQuery (Perfmatters: Disable jQuery)',
                    'javascript.remove_script_versions' => 'Remove ?ver= (Perfmatters: Remove query strings)',
                ),
            ),
            'autoptimize' => array(
                'label'      => 'Autoptimize',
                'class'      => null,
                'constant'   => 'AUTOPTIMIZE_PLUGIN_VERSION',
                'function'   => 'autoptimize',
                'overlaps'   => array(
                    'css.minify_css'                 => 'Minify CSS (Autoptimize: Optimize CSS Code)',
                    'javascript.defer_javascript'    => 'Defer JS (Autoptimize: Optimize JavaScript Code)',
                    'javascript.minify_javascript'   => 'Minify JS (Autoptimize)',
                    'core.minify_html'               => 'Minify HTML (Autoptimize: Optimize HTML Code)',
                ),
            ),
        );
    }

    /**
     * Return active conflicting plugins.
     *
     * @return array slug → entry
     */
    public static function get_active_conflicts() {
        $active = array();
        foreach ( self::catalog() as $slug => $entry ) {
            if ( self::plugin_is_active( $entry ) ) {
                $active[ $slug ] = $entry;
            }
        }
        return $active;
    }

    /**
     * Detect plugin presence via class / constant / function.
     *
     * @param array $entry
     * @return bool
     */
    private static function plugin_is_active( $entry ) {
        if ( ! empty( $entry['class'] ) && class_exists( $entry['class'] ) ) {
            return true;
        }
        if ( ! empty( $entry['constant'] ) && defined( $entry['constant'] ) ) {
            return true;
        }
        if ( ! empty( $entry['function'] ) && function_exists( $entry['function'] ) ) {
            return true;
        }
        return false;
    }

    /**
     * Filter overlaps to those actually enabled in MBR settings.
     *
     * @param array $entry
     * @return array
     */
    public static function active_overlaps( $entry ) {
        $hit = array();
        foreach ( $entry['overlaps'] as $opt_path => $label ) {
            list( $section, $key ) = explode( '.', $opt_path, 2 );
            $opts = mbrpe()->get_options( $section );
            if ( ! empty( $opts[ $key ] ) ) {
                $hit[ $opt_path ] = $label;
            }
        }
        return $hit;
    }

    /**
     * Show the conflict notice on MBR settings pages.
     */
    public function maybe_show_notice() {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen || false === strpos( (string) $screen->id, 'mbr-performance' ) ) {
            return;
        }

        $conflicts = self::get_active_conflicts();
        if ( empty( $conflicts ) ) {
            return;
        }

        $messages = array();
        foreach ( $conflicts as $entry ) {
            $hits = self::active_overlaps( $entry );
            if ( empty( $hits ) ) {
                continue;
            }
            $list = '<ul style="margin:.4em 0 .4em 1.5em;list-style:disc;">';
            foreach ( $hits as $label ) {
                $list .= '<li>' . esc_html( $label ) . '</li>';
            }
            $list .= '</ul>';
            $messages[] = sprintf(
                /* translators: %s = plugin label */
                '<p><strong>' . esc_html__( '%s is active.', 'mbr-performance' ) . '</strong> '
                . esc_html__( 'The following MBR Performance options overlap with it — pick one or the other, not both:', 'mbr-performance' )
                . '</p>%s',
                esc_html( $entry['label'] ),
                $list
            );
        }

        if ( empty( $messages ) ) {
            return;
        }

        echo '<div class="notice notice-warning">' . wp_kses_post( implode( '', $messages ) ) . '</div>';
    }
}
