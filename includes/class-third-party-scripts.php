<?php
/**
 * Self-hosted Third-Party Scripts
 *
 * Fetches Google Analytics (gtag.js), Google Tag Manager (gtm.js) and
 * Facebook Pixel (fbevents.js) to local storage on a daily cron, then
 * rewrites <script src="..."> URLs in the page output to point at the
 * local copies.
 *
 * Benefits:
 *  - Removes "Reduce the impact of third-party code" PSI warning.
 *  - Improves LCP/TBT — no extra DNS lookup, TLS handshake or external fetch.
 *  - Privacy: requests no longer go to googletagmanager.com / connect.facebook.net
 *    on first paint (still go there from the script itself once executed).
 *
 * Cache files live in /wp-content/uploads/mbr-wp-performance/ and refresh daily.
 *
 * @package MBR_WP_Performance
 * @since   1.12.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBR_WP_Performance_Third_Party_Scripts {

    /**
     * Single instance.
     *
     * @var MBR_WP_Performance_Third_Party_Scripts
     */
    private static $instance = null;

    /**
     * Subdirectory under uploads for cached scripts.
     */
    const CACHE_DIR = 'mbr-wp-performance/third-party';

    /**
     * Cron hook for refresh.
     */
    const CRON_HOOK = 'mbr_wp_performance_third_party_refresh';

    /**
     * Map of supported scripts.
     *
     * Each entry: option key → remote URL + local filename.
     *
     * @var array
     */
    private $catalog;

    /**
     * Options.
     *
     * @var array
     */
    private $options = array();

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
        $this->options = mbr_wp_performance()->get_options( 'third_party' );

        $this->catalog = array(
            'host_gtag' => array(
                'remote' => 'https://www.googletagmanager.com/gtag/js',
                'local'  => 'gtag.js',
                'match'  => array( 'googletagmanager.com/gtag/js' ),
                'label'  => 'Google Analytics (gtag.js)',
            ),
            'host_gtm' => array(
                'remote' => 'https://www.googletagmanager.com/gtm.js',
                'local'  => 'gtm.js',
                'match'  => array( 'googletagmanager.com/gtm.js' ),
                'label'  => 'Google Tag Manager (gtm.js)',
            ),
            'host_analytics' => array(
                'remote' => 'https://www.google-analytics.com/analytics.js',
                'local'  => 'analytics.js',
                'match'  => array( 'google-analytics.com/analytics.js' ),
                'label'  => 'Google Analytics (analytics.js — legacy)',
            ),
            'host_fbpixel' => array(
                'remote' => 'https://connect.facebook.net/en_US/fbevents.js',
                'local'  => 'fbevents.js',
                'match'  => array( 'connect.facebook.net/en_US/fbevents.js', 'connect.facebook.net/en_GB/fbevents.js' ),
                'label'  => 'Facebook Pixel (fbevents.js)',
            ),
        );

        $any_enabled = false;
        foreach ( array_keys( $this->catalog ) as $opt_key ) {
            if ( ! empty( $this->options[ $opt_key ] ) ) {
                $any_enabled = true;
                break;
            }
        }
        if ( ! $any_enabled ) {
            return;
        }

        // Cron schedule for refresh.
        add_action( 'init', array( $this, 'ensure_cron_scheduled' ) );
        add_action( self::CRON_HOOK, array( $this, 'refresh_all' ) );

        // Output buffer rewrite (front-end only).
        if ( ! is_admin() ) {
            add_action( 'template_redirect', array( $this, 'maybe_start_buffer' ), 1 );
        }
    }

    /**
     * Get catalog of supported scripts.
     *
     * @return array
     */
    public function get_catalog() {
        return $this->catalog;
    }

    /**
     * Ensure the daily refresh cron is scheduled.
     */
    public function ensure_cron_scheduled() {
        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
        }
    }

    /**
     * Cache directory absolute path.
     *
     * @return string
     */
    private function cache_dir() {
        $uploads = wp_get_upload_dir();
        return trailingslashit( $uploads['basedir'] ) . self::CACHE_DIR;
    }

    /**
     * Cache directory URL.
     *
     * @return string
     */
    private function cache_url() {
        $uploads = wp_get_upload_dir();
        return trailingslashit( $uploads['baseurl'] ) . self::CACHE_DIR;
    }

    /**
     * Ensure the cache directory exists.
     *
     * @return bool
     */
    private function ensure_cache_dir() {
        $dir = $this->cache_dir();
        if ( ! file_exists( $dir ) ) {
            return wp_mkdir_p( $dir );
        }
        return true;
    }

    /**
     * Refresh all enabled scripts.
     *
     * @return array Per-key results.
     */
    public function refresh_all() {
        $this->ensure_cache_dir();
        $results = array();
        foreach ( $this->catalog as $key => $entry ) {
            if ( empty( $this->options[ $key ] ) ) {
                continue;
            }
            $results[ $key ] = $this->refresh_one( $key );
        }
        update_option( 'mbr_wp_performance_third_party_last_refresh', array(
            'time'    => time(),
            'results' => $results,
        ), false );
        return $results;
    }

    /**
     * Refresh a single script.
     *
     * @param string $key
     * @return string Status string.
     */
    public function refresh_one( $key ) {
        if ( ! isset( $this->catalog[ $key ] ) ) {
            return 'unknown';
        }
        $entry = $this->catalog[ $key ];
        $resp  = wp_remote_get( $entry['remote'], array( 'timeout' => 15 ) );
        if ( is_wp_error( $resp ) ) {
            return 'error: ' . $resp->get_error_message();
        }
        $code = (int) wp_remote_retrieve_response_code( $resp );
        if ( 200 !== $code ) {
            return 'http_' . $code;
        }
        $body = wp_remote_retrieve_body( $resp );
        if ( '' === $body || strlen( $body ) < 100 ) {
            return 'empty';
        }
        $local = trailingslashit( $this->cache_dir() ) . $entry['local'];
        $bytes = @file_put_contents( $local, $body );
        if ( false === $bytes ) {
            return 'write_failed';
        }
        return 'ok:' . $bytes;
    }

    /**
     * Start an output buffer for the page so we can rewrite outbound script URLs.
     */
    public function maybe_start_buffer() {
        // Skip in editors and feeds.
        if ( is_feed() || is_admin() ) {
            return;
        }
        if ( ! empty( $_GET['elementor-preview'] ) ) {
            return;
        }
        ob_start( array( $this, 'rewrite_buffer' ) );
    }

    /**
     * Rewrite outbound third-party script URLs in the page HTML.
     *
     * @param string $html
     * @return string
     */
    public function rewrite_buffer( $html ) {
        if ( ! is_string( $html ) || '' === $html ) {
            return $html;
        }

        $cache_url = $this->cache_url();
        $cache_dir = $this->cache_dir();

        foreach ( $this->catalog as $key => $entry ) {
            if ( empty( $this->options[ $key ] ) ) {
                continue;
            }
            $local_path = trailingslashit( $cache_dir ) . $entry['local'];
            if ( ! file_exists( $local_path ) ) {
                continue; // Not yet cached.
            }
            $local_url = trailingslashit( $cache_url ) . $entry['local'];

            // Each script may match more than one remote URL pattern.
            foreach ( $entry['match'] as $needle ) {
                // Replace inside src="..." or src='...' or unquoted.
                // Replace the prefix everywhere it appears in src=. We keep any
                // query string (e.g. ?id=GTM-XXXXX) intact by only rewriting the host+path.
                $remote_prefix = 'https://' . $needle;
                $html          = str_replace( $remote_prefix, $local_url, $html );
                $remote_prefix2 = 'http://' . $needle;
                $html           = str_replace( $remote_prefix2, $local_url, $html );
                $html           = str_replace( '//' . $needle, $local_url, $html );
            }
        }
        return $html;
    }

    /**
     * Last refresh log.
     *
     * @return array
     */
    public static function get_last_refresh() {
        return get_option( 'mbr_wp_performance_third_party_last_refresh', array() );
    }

    /**
     * Cleanup on deactivation.
     */
    public static function cleanup_on_deactivation() {
        wp_clear_scheduled_hook( self::CRON_HOOK );
        delete_option( 'mbr_wp_performance_third_party_last_refresh' );
        // Leave the cached files on disk in case reactivated — they're harmless.
    }
}
