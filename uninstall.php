<?php
/**
 * Uninstall handler for MBR WP Performance.
 *
 * Runs only when the user genuinely deletes the plugin (not on
 * deactivate). Removes the persistent user-data artefacts that the
 * running plugin no longer needs:
 *
 *  - Generated .webp files registered in the WebP registry, plus the
 *    related option keys (mbr_webp_registry, mbr_webp_converted_images,
 *    mbr_webp_registry_migrated).
 *  - Generated .avif files registered in the AVIF registry, plus the
 *    related option key (mbr_avif_registry).
 *
 * Runtime side-effects (.htaccess marker blocks, Elementor cache flush,
 * scheduled cron events) are handled by the deactivation hook in the
 * main plugin file, which runs before uninstall.
 *
 * The main settings option (mbr_wp_performance_options) and version
 * stamp (mbr_wp_performance_version) are deliberately left in place —
 * users who reinstall after a delete typically want their settings back.
 *
 * @package MBR_WP_Performance
 * @since   1.12.2
 */

// Guard: only run when invoked by WordPress as an uninstall handler.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Load the converter class files. They only declare classes — no
// top-level side-effects, no hook registrations at file-load time.
require_once __DIR__ . '/includes/class-webp-converter.php';
require_once __DIR__ . '/includes/class-avif-converter.php';

if ( class_exists( 'MBR_WP_Performance_WebP_Converter' ) ) {
    MBR_WP_Performance_WebP_Converter::cleanup_on_uninstall();
}

if ( class_exists( 'MBR_WP_Performance_AVIF_Converter' ) ) {
    MBR_WP_Performance_AVIF_Converter::cleanup_on_uninstall();
}
