<?php
/**
 * Plugin Name: MBR Performance
 * Plugin URI: https://littlewebshack.com/mbr-performance/
 * Description: Granular, transparent WordPress performance optimisation — core, scripts, fonts, preloading, database, images, WooCommerce, multisite and pasted Critical CSS. Free and GPL; self-hosted updates delivered from Little Web Shack.
 * Version: 1.23.0
 * Author: Robert Palmer
 * Author URI: https://madebyrobert.co.uk
 * Text Domain: mbr-performance
 * Domain Path: /languages
 * Requires at least: 5.9
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*
 * (Removed) The former lite->XL activation switchover hook has been dropped.
 * This build IS the canonical "mbr-performance", so a hook that deactivated
 * mbr-performance/mbr-performance.php would now deactivate this plugin itself.
 * Same-prefix duplicates (e.g. a leftover mbr-performance-xl folder) are still
 * handled safely by the collision guard below.
 */

// XL and the wp.org "lite" build share the same class names and "mbrpe_" prefix,
// so they cannot run together. MBRPE_VERSION is defined at runtime (below), so it
// is only set if another copy has already loaded — making it a reliable collision
// signal. (Do NOT also test class_exists('MBRPE'): that class is declared later in
// THIS file and is hoisted at compile time, so it is always "defined" and would
// make this guard bail on every load.)
if ( defined( 'MBRPE_VERSION' ) ) {
    add_action(
        'admin_notices',
        function () {
            echo '<div class="notice notice-error"><p>'
                . esc_html__( 'Another copy of MBR Performance is already active. Only one copy can run at a time — please deactivate the duplicate.', 'mbr-performance' )
                . '</p></div>';
        }
    );
    return;
}

// Buy Me a Coffee
add_filter( 'plugin_row_meta', function ( $links, $file, $data ) {
    if ( ! function_exists( 'plugin_basename' ) || $file !== plugin_basename( __FILE__ ) ) {
        return $links;
    }

    $url = 'https://buymeacoffee.com/robertpalmer/';
    $links[] = sprintf(
	// translators: %s: The name of the plugin author.
        '<a href="%s" target="_blank" rel="noopener nofollow" aria-label="%s">☕ %s</a>',
        esc_url( $url ),
		// translators: %s: The name of the plugin author.
        esc_attr( sprintf( __( 'Buy %s a coffee', 'mbr-performance' ), isset( $data['AuthorName'] ) ? $data['AuthorName'] : __( 'the author', 'mbr-performance' ) ) ),
        esc_html__( 'Buy me a coffee', 'mbr-performance' )
    );

    return $links;
}, 10, 3 );

// Define plugin constants
define( 'MBRPE_VERSION', '1.23.0' );
define( 'MBRPE_IS_XL', true );
define( 'MBRPE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MBRPE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MBRPE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/*
 * Self-hosted updates via the Plugin Update Checker library.
 *
 * MBRPE_XL_UPDATE_URL is the metadata endpoint the plugin polls for updates. You
 * can also define it in wp-config.php to keep it out of the plugin file, so a
 * plugin update never overwrites your URL. (The constant name is kept for
 * backward compatibility with any existing wp-config override.)
 *
 * Remember: bump the Version header above each time you publish, or PUC has no
 * higher version to offer and no update notice appears.
 */
if ( ! defined( 'MBRPE_XL_UPDATE_URL' ) ) {
    define( 'MBRPE_XL_UPDATE_URL', 'https://raw.githubusercontent.com/HarbourBob/mbr-updates/main/mbr-performance.json' );
}
if ( '' !== MBRPE_XL_UPDATE_URL && file_exists( MBRPE_PLUGIN_DIR . 'includes/vendor/plugin-update-checker/plugin-update-checker.php' ) ) {
    require_once MBRPE_PLUGIN_DIR . 'includes/vendor/plugin-update-checker/plugin-update-checker.php';
    if ( class_exists( '\\YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory' ) ) {
        $GLOBALS['mbrpe_xl_update_checker'] = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
            MBRPE_XL_UPDATE_URL,
            __FILE__,
            'mbr-performance'
        );
    }
}

/*
 * Load the plugin itself.
 *
 * Everything below the collision guard lives in the bootstrap so that the main
 * file declares no class or function that the wp.org "lite" build also declares.
 * Because this require sits AFTER the guard, a site running both copies never
 * reaches it on the XL side — so PHP never tries to redeclare anything and the
 * dreaded "Plugin could not be activated because it triggered a fatal error"
 * never appears. The second copy simply activates dormant and the notice above
 * guides the user to deactivate the duplicate.
 */
require_once MBRPE_PLUGIN_DIR . 'includes/mbrpe-bootstrap.php';
