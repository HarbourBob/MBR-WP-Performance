<?php
/**
 * Uninstall routine for MBR Performance.
 *
 * Runs only when the plugin is deleted from Plugins → Delete, never on
 * deactivation. Deactivation is reversible and leaves settings alone so that
 * reactivating restores the site's configuration; deletion is the point at
 * which the user has said they are finished with the plugin, so everything it
 * created goes with it.
 *
 * What this removes: the plugin's own options and network options, its two RUM
 * tables and the orphan-media staging table, its scheduled events, its
 * transients, and the cache directories it created under wp-content/uploads.
 *
 * What this deliberately does NOT remove:
 *
 *   - Original media. Nothing in the Media Library is touched.
 *   - Generated WebP and AVIF files. These are derived from the user's own
 *     images and may still be referenced by a CDN, a cached page or a backup.
 *     Deactivation already offers to clean them up through the converters'
 *     own routines, which is the right place for that decision — silently
 *     deleting thousands of image files during an uninstall would be a
 *     surprise, and an expensive one to undo.
 *   - Self-hosted font files. Same reasoning: a theme may still reference
 *     them by URL after the plugin has gone, and losing them would break the
 *     site's typography rather than merely un-optimising it.
 *   - .htaccess marker blocks. Those are removed on deactivation, which
 *     always precedes deletion.
 *
 * On multisite this runs once per site, plus the network-level options, so a
 * network with many sites is cleaned completely rather than leaving per-site
 * rows behind.
 *
 * @package MBR_Performance
 */

// Only ever reached through WordPress's uninstall mechanism.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Options this plugin owns, on a single site.
 *
 * Listed explicitly rather than matched by prefix: a LIKE 'mbrpe_%' delete
 * would also catch options belonging to any other plugin that happened to
 * share the prefix, and would silently pick up anything a future version adds
 * without a maintainer noticing.
 *
 * @return string[]
 */
function mbrpe_uninstall_option_names() {
	return array(
		'mbrpe_options',
		'mbrpe_version',
		'mbrpe_css_scan',
		'mbrpe_db_last_cleanup',
		'mbrpe_fonts_dir',
		'mbrpe_local_fonts',
		'mbrpe_orphan_db_version',
		'mbrpe_orphan_scan_state',
		'mbrpe_prefix_migrated',
		'mbrpe_rum_db_version',
		'mbrpe_using_network_defaults',
		'mbrpe_wc_migration_notice_shown',
		'mbrpe_webp_converted_images',
		'mbrpe_webp_registry',
		'mbrpe_webp_registry_migrated',
		'mbrpe_avif_converted_images',
		'mbrpe_avif_registry',
	);
}

/**
 * Scheduled events this plugin registers.
 *
 * @return string[]
 */
function mbrpe_uninstall_cron_hooks() {
	return array(
		'mbrpe_database_cleanup',
		'mbrpe_orphan_purge',
		'mbrpe_rum_aggregate',
		'mbrpe_third_party_refresh',
	);
}

/**
 * Upload-directory caches this plugin creates.
 *
 * Generated images and downloaded fonts are deliberately absent — see the file
 * header for why.
 *
 * @return string[]
 */
function mbrpe_uninstall_cache_dirs() {
	return array(
		'mbr-performance-combine',
		'mbr-performance-usedcss',
		'mbr-performance-usedcss-b',
	);
}

/**
 * Delete a directory the plugin created, and its contents.
 *
 * Refuses to act on anything that is not inside the uploads directory, is not
 * a real directory, or is a symlink — an uninstaller is exactly the wrong
 * place to follow a link out of the tree it was asked to clean.
 *
 * @param string $dir Absolute path of the directory to remove.
 * @return void
 */
function mbrpe_uninstall_rmdir( $dir ) {
	$uploads = wp_upload_dir();
	if ( ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) ) {
		return;
	}

	$base = realpath( $uploads['basedir'] );
	$real = realpath( $dir );

	if ( false === $base || false === $real || ! is_dir( $real ) || is_link( $dir ) ) {
		return;
	}
	if ( 0 !== strpos( wp_normalize_path( $real ), trailingslashit( wp_normalize_path( $base ) ) ) ) {
		return;
	}

	// One level of files is all these caches ever contain (.css, .json, .lock,
	// index.html), so a flat pass is enough and cannot recurse anywhere
	// unexpected.
	$entries = glob( trailingslashit( $real ) . '*' );
	if ( is_array( $entries ) ) {
		foreach ( $entries as $entry ) {
			if ( is_file( $entry ) && ! is_link( $entry ) ) {
				wp_delete_file( $entry );
			}
		}
	}

	// Leaves the directory in place if anything unexpected remains, rather
	// than forcing it.
	@rmdir( $real ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- A non-empty directory is a deliberate no-op here.
}

/**
 * Remove everything this plugin owns on the current site.
 *
 * @return void
 */
function mbrpe_uninstall_site() {
	global $wpdb;

	foreach ( mbrpe_uninstall_option_names() as $option ) {
		delete_option( $option );
	}

	foreach ( mbrpe_uninstall_cron_hooks() as $hook ) {
		wp_clear_scheduled_hook( $hook );
	}

	// Custom tables. Table names are built from $wpdb->prefix, never from
	// user input, so they are safe to interpolate — but they still cannot be
	// passed through prepare(), which does not support identifiers.
	$tables = array(
		$wpdb->prefix . 'mbrpe_rum_raw',
		$wpdb->prefix . 'mbrpe_rum_agg',
		$wpdb->prefix . 'mbr_orphan_log',
	);
	foreach ( $tables as $table ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Identifier from $wpdb->prefix; DROP TABLE cannot be prepared.
		$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
	}

	// Transients the plugin sets. These are prefixed consistently and are
	// throwaway by nature, so a prefix match is appropriate here in a way it
	// is not for options.
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-off cleanup during uninstall.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_mbrpe_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_mbrpe_' ) . '%'
		)
	);

	$uploads = wp_upload_dir();
	if ( empty( $uploads['error'] ) && ! empty( $uploads['basedir'] ) ) {
		foreach ( mbrpe_uninstall_cache_dirs() as $dirname ) {
			mbrpe_uninstall_rmdir( trailingslashit( $uploads['basedir'] ) . $dirname );
		}
	}
}

if ( is_multisite() ) {
	// Clean every site on the network, in batches, so a large network does not
	// try to hold every blog ID in memory at once.
	$offset = 0;
	$limit  = 100;

	do {
		$site_ids = get_sites(
			array(
				'fields' => 'ids',
				'number' => $limit,
				'offset' => $offset,
			)
		);

		foreach ( $site_ids as $site_id ) {
			switch_to_blog( $site_id );
			mbrpe_uninstall_site();
			restore_current_blog();
		}

		$offset += $limit;
	} while ( count( $site_ids ) === $limit );

	delete_site_option( 'mbrpe_network_options' );
	delete_site_option( 'mbrpe_network_version' );
	delete_site_option( 'mbrpe_allow_site_overrides' );
} else {
	mbrpe_uninstall_site();
}
