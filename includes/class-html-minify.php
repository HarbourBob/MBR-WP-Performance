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
        // Never minify AMP responses — AMP has strict markup rules and
        // minification can invalidate the page.
        if ( function_exists( 'amp_is_request' ) && amp_is_request() ) {
            return;
        }
        if ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() ) {
            return;
        }
        // Don't run on REST or AJAX responses (these generally don't reach
        // template_redirect, but guard defensively).
        if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || wp_doing_ajax() ) {
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

        // Skip pages that contain a nested / embedded HTML document — i.e.
        // a second <!doctype>, <html>, or <head>. Some sites embed a
        // complete HTML document inside a page-builder HTML widget (for
        // example an Elementor "HTML" widget holding a full
        // <!doctype html>...</html> landing page). The rendered response is
        // then a malformed, doubly-nested document. Browsers tolerate it by
        // leniently re-parsing, but collapsing the whitespace across that
        // nested structure changes how the parser resolves it and can break
        // the layout — which is exactly the symptom seen on such pages while
        // natively-built pages are unaffected. These pages are uncommon and
        // the only safe thing to do is leave them untouched.
        $lower = strtolower( $html );
        if ( substr_count( $lower, '<!doctype' ) > 1
            || (int) preg_match_all( '/<html[\s>]/i', $html ) > 1
            || (int) preg_match_all( '/<head[\s>]/i', $html ) > 1 ) {
            return $html;
        }

        // Extract <pre>, <textarea>, <script>, <style> and inline <svg>
        // sections to placeholders so we never touch their internal
        // whitespace or contents.
        //
        // The placeholder token is a per-request random alphanumeric string.
        // It must NOT look like an HTML comment: the comment-stripping step
        // below would otherwise match and delete the placeholders, and the
        // subsequent restore would have nothing to put back — wiping every
        // script and style on the page. (That was the bug that broke sites
        // before v1.13.1, when the placeholder was '<!--MBR_PLACEHOLDER_X-->'.)
        $placeholders = array();
        $index        = 0;
        $token        = 'MBRMINIFYPLACEHOLDER' . substr( md5( uniqid( (string) wp_rand(), true ) ), 0, 16 );
        $patterns     = array(
            '/<pre\b[^>]*>.*?<\/pre>/is',
            '/<textarea\b[^>]*>.*?<\/textarea>/is',
            '/<script\b[^>]*>.*?<\/script>/is',
            '/<style\b[^>]*>.*?<\/style>/is',
            '/<svg\b[^>]*>.*?<\/svg>/is',
        );
        foreach ( $patterns as $p ) {
            $result = preg_replace_callback(
                $p,
                function ( $m ) use ( &$placeholders, &$index, $token ) {
                    $key                  = $token . $index . 'END';
                    $placeholders[ $key ] = $m[0];
                    $index++;
                    return $key;
                },
                $html
            );
            // If PCRE bails (e.g. backtrack limit on a very large buffer),
            // preg_replace_callback returns null. Keep the pre-pass HTML
            // rather than nuking the entire page.
            if ( null !== $result ) {
                $html = $result;
            }
        }

        // Strip ordinary HTML comments, but preserve IE conditional comments
        // ('<!--[if ...]>'), downlevel-revealed close markers ('<!--<![endif]-->')
        // and the reveal hack ('<!-->').
        $stripped = preg_replace( '/<!--(?!\s*(?:\[if\b|<!|>))(?:(?!-->).)*-->/s', '', $html );
        if ( null !== $stripped ) {
            $html = $stripped;
        }

        // Collapse only whitespace runs that span a line break, replacing each
        // with a single space. This removes indentation and newlines — the
        // bulk of the saving — while deliberately leaving same-line whitespace
        // untouched. That keeps:
        //   * the single significant space between inline elements
        //     (e.g. '</a> <a>' would otherwise render as 'onetwo'), and
        //   * whitespace inside attribute values (e.g. value="a   b").
        // A single space (not empty string) is used so inter-element spacing
        // is preserved rather than destroyed.
        $collapsed = preg_replace( '/\s*\n\s*/', ' ', $html );
        if ( null !== $collapsed ) {
            $html = $collapsed;
        }

        // Restore the preserved sections.
        if ( ! empty( $placeholders ) ) {
            $html = strtr( $html, $placeholders );
        }

        return $html;
    }
}
