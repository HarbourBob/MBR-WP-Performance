<?php
/**
 * Helper Functions
 *
 * @package MBRPE
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get plugin option
 *
 * @param string $section Section name
 * @param string $key Option key
 * @param mixed $default Default value
 * @return mixed
 */
function mbrpe_get_option( $section, $key, $default = false ) {
    $options = mbrpe()->get_options( $section );
    return isset( $options[ $key ] ) ? $options[ $key ] : $default;
}

/**
 * Update plugin option
 *
 * @param string $section Section name
 * @param string $key Option key
 * @param mixed $value Value to set
 * @return bool
 */
function mbrpe_update_option( $section, $key, $value ) {
    $all_options = mbrpe()->get_options();
    
    if ( ! isset( $all_options[ $section ] ) ) {
        $all_options[ $section ] = array();
    }
    
    $all_options[ $section ][ $key ] = $value;
    
    return mbrpe()->update_options( $all_options );
}

/**
 * Check if feature is enabled
 *
 * @param string $section Section name
 * @param string $feature Feature name
 * @return bool
 */
function mbrpe_is_enabled( $section, $feature ) {
    return (bool) mbrpe_get_option( $section, $feature, false );
}

/**
 * Log debug message
 *
 * @param string $message Message to log
 * @param string $level Log level
 */
function mbrpe_log( $message, $level = 'info' ) {
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
        error_log( sprintf( '[MBR Performance] [%s] %s', strtoupper( $level ), $message ) );
    }
}

// ------------------------------------------------------------------
//  Multisite helpers
// ------------------------------------------------------------------

/**
 * Check whether the plugin is network-activated.
 *
 * @since 1.5.0
 * @return bool
 */
function mbrpe_is_network_active() {
    if ( ! is_multisite() ) {
        return false;
    }

    if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    return is_plugin_active_for_network( MBRPE_PLUGIN_BASENAME );
}

/**
 * Check whether the current site is using network defaults.
 *
 * Returns false on single-site installs.
 *
 * @since 1.5.0
 * @return bool
 */
function mbrpe_using_network_defaults() {
    if ( ! is_multisite() || ! class_exists( 'MBRPE_Multisite' ) ) {
        return false;
    }

    return MBRPE_Multisite::site_uses_network_defaults();
}

/**
 * Check whether per-site overrides are allowed on the current network.
 *
 * Always returns true on single-site installs.
 *
 * @since 1.5.0
 * @return bool
 */
function mbrpe_site_overrides_allowed() {
    if ( ! is_multisite() || ! class_exists( 'MBRPE_Multisite' ) ) {
        return true;
    }

    return MBRPE_Multisite::allow_site_overrides();
}

/**
 * Fetch a URL on the site's own host, verifying TLS but tolerating the
 * self-signed certificates common on local and staging installs.
 *
 * Several features need to read the site's own front end: the Performance
 * Doctor, the CSS scanner and the Google Fonts detector all fetch a rendered
 * page or stylesheet over HTTP rather than reasoning about the enqueue queue.
 * These used to pass 'sslverify' => false unconditionally, which switched
 * certificate checking off on every install, production included, to solve a
 * problem that only exists in development.
 *
 * This verifies by default. Only if the request fails, and only when the URL is
 * on the site's own host, does it retry once without verification — so a
 * developer running a self-signed staging copy is not locked out of the tools,
 * while a production site keeps a verified connection.
 *
 * Nothing fetched this way is written to disk, which is what separates it from
 * the external font download; that path verifies with no fallback at all.
 *
 * @since 1.23.1
 * @param string $url  URL to fetch, expected to be on this site.
 * @param array  $args Arguments for wp_remote_get().
 * @return array|WP_Error Response array, or WP_Error on failure.
 */
function mbrpe_remote_get_self( $url, $args = array() ) {
    $args['sslverify'] = true;

    $response = wp_remote_get( $url, $args );
    if ( ! is_wp_error( $response ) ) {
        return $response;
    }

    // Only same-host URLs get the fallback. An off-site URL that fails
    // verification is a result worth honouring, not working around.
    $target = wp_parse_url( $url, PHP_URL_HOST );
    $home   = wp_parse_url( home_url(), PHP_URL_HOST );
    if ( ! $target || ! $home || strtolower( $target ) !== strtolower( $home ) ) {
        return $response;
    }

    // Only retry for a transport-level failure, which is what a certificate
    // problem produces. An HTTP error is already a valid response.
    $args['sslverify'] = false;

    return wp_remote_get( $url, $args );
}
