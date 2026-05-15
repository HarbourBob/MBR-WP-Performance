<?php
/**
 * HTML Minification
 *
 * Buffers the front-end response and strips HTML comments + collapses
 * whitespace before delivery. Preserves <pre>, <textarea>, <script>,
 * <style> and conditional comments.
 *
 * @package MBR_WP_Performance
 * @since   1.12.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBR_WP_Performance_HTML_Minify {

    /**
     * Single instance.
     *
     * @var MBR_WP_Performance_HTML_Minify
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
        $opts = mbr_wp_performance()->get_options( 'core' );
        if ( empty( $opts['html_minify'] ) ) {
            return;
        }
        if ( is_admin() ) {
            return;
        }
        add_action( 'template_redirect', array( $this, 'start_buffer' ), 5 );
    }

    /**
     * Start the output buffer.
     */
    public function start_buffer() {
        if ( is_feed() || is_robots() || self::is_editor() ) {
            return;
        }
        ob_start( array( __CLASS__, 'minify_html' ) );
    }

    /**
     * In a page-builder editor?
     *
     * @return bool
     */
    private static function is_editor() {
        if ( ! empty( $_GET['elementor-preview'] ) || ( isset( $_GET['action'] ) && 'elementor' === $_GET['action'] ) ) {
            return true;
        }
        if ( isset( $_GET['fl_builder'] ) || ( ! empty( $_GET['bricks'] ) && 'run' === $_GET['bricks'] ) ) {
            return true;
        }
        if ( defined( 'SHOW_CT_BUILDER' ) ) {
            return true;
        }
        return false;
    }

    /**
     * Minify an HTML buffer.
     *
     * @param string $html
     * @return string
     */
    public static function minify_html( $html ) {
        if ( ! is_string( $html ) || strlen( $html ) < 200 ) {
            return $html;
        }
        // Only minify HTML responses.
        if ( false === stripos( $html, '<html' ) && false === stripos( $html, '<!doctype' ) ) {
            return $html;
        }

        // Extract <pre>, <textarea>, <script>, <style> sections to placeholders so
        // we don't touch their internal whitespace.
        $placeholders = array();
        $index        = 0;
        $patterns     = array(
            '/<pre\b[^>]*>.*?<\/pre>/is',
            '/<textarea\b[^>]*>.*?<\/textarea>/is',
            '/<script\b[^>]*>.*?<\/script>/is',
            '/<style\b[^>]*>.*?<\/style>/is',
        );
        foreach ( $patterns as $p ) {
            $html = preg_replace_callback( $p, function ( $m ) use ( &$placeholders, &$index ) {
                $key                  = '<!--MBR_PLACEHOLDER_' . $index . '-->';
                $placeholders[ $key ] = $m[0];
                $index++;
                return $key;
            }, $html );
        }

        // Strip ordinary HTML comments (but preserve IE conditional comments).
        $html = preg_replace( '/<!--(?!\s*(?:\[if\s|<!|>))(?:(?!-->).)*-->/s', '', $html );

        // Collapse runs of whitespace between tags.
        $html = preg_replace( '/>\s+</', '><', $html );

        // Collapse multiple spaces/newlines to a single space.
        $html = preg_replace( '/[ \t]+/', ' ', $html );
        $html = preg_replace( '/\s*\n\s*/', "\n", $html );

        // Restore placeholders.
        if ( ! empty( $placeholders ) ) {
            $html = strtr( $html, $placeholders );
        }

        return $html;
    }
}
