<?php
/**
 * OPcache — status, flushing and invalidation.
 *
 * OPcache is configured in php.ini at server level. Nothing here can switch it
 * on, size it or tune it, and the Diagnostics panel says so rather than
 * offering switches that cannot exist. What a plugin can do is:
 *
 *   1. Report what the server is doing, in plain English.
 *   2. Flush it on demand, so a host with validate_timestamps=0 does not need a
 *      trip to the control panel after every code change.
 *   3. Invalidate the PHP files this plugin itself writes (advanced-cache.php,
 *      wp-config.php, the cache config), so a settings change takes effect on
 *      the next request instead of whenever the host next restarts PHP.
 *   4. Invalidate updated plugins and themes on WordPress older than 6.2, which
 *      does not do this itself. 6.2+ invalidates after every upgrade in core,
 *      so the fallback steps aside there.
 *
 * Everything is defensive. OPcache may be missing, disabled, restricted to
 * another path by opcache.restrict_api, or have its functions listed in
 * disable_functions. Each of those degrades to "not available", never to a
 * warning or an error.
 *
 * @package MBRPE
 * @since   2.1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MBRPE_OPcache {

	/**
	 * Network-wide option recording the last manual flush. OPcache is per
	 * server, not per site, so on multisite this is a site (network) option.
	 */
	const LAST_RESET_OPTION = 'mbrpe_opcache_last_reset';

	/**
	 * Singleton instance.
	 *
	 * @var MBRPE_OPcache|null
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return MBRPE_OPcache
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'admin_post_mbrpe_opcache_reset', array( $this, 'handle_reset_request' ) );
		add_action( 'admin_notices', array( $this, 'maybe_show_reset_notice' ) );
		add_action( 'network_admin_notices', array( $this, 'maybe_show_reset_notice' ) );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ), 101 );

		// Core invalidates the installed directory after every upgrade from
		// 6.2. Before that it does not, so on a validate_timestamps=0 host an
		// update would keep running the old code until PHP restarted.
		// Checked by version rather than function_exists(): the core helper
		// lives in wp-admin/includes/file.php, which is not loaded yet here.
		global $wp_version;
		if ( version_compare( (string) $wp_version, '6.2', '<' ) ) {
			add_action( 'upgrader_process_complete', array( $this, 'on_upgrade' ), 10, 2 );
		}
	}

	// =====================================================================
	// Capability checks
	// =====================================================================

	/**
	 * Can the current user flush OPcache?
	 *
	 * On multisite a flush affects every site on the server, so it is a
	 * super-admin action there, not a site-admin one.
	 *
	 * @return bool
	 */
	public static function current_user_can_flush() {
		if ( is_multisite() ) {
			return is_super_admin();
		}
		return current_user_can( 'manage_options' );
	}

	// =====================================================================
	// Availability
	// =====================================================================

	/**
	 * Does opcache.restrict_api stop this request using the OPcache API?
	 *
	 * The setting holds a path prefix. PHP compares it with the script that
	 * was requested (index.php, wp-admin/admin.php and so on), not the file
	 * making the call, so SCRIPT_FILENAME is the better approximation. Falls
	 * back to this file's path, as core's wp_opcache_invalidate() does.
	 *
	 * @since 2.1.0
	 * @since 2.1.1 Compares against the requested script.
	 * @return bool
	 */
	public static function is_restricted() {
		$restrict = (string) ini_get( 'opcache.restrict_api' );
		if ( '' === $restrict ) {
			return false;
		}
		$script = ! empty( $_SERVER['SCRIPT_FILENAME'] ) ? (string) $_SERVER['SCRIPT_FILENAME'] : __FILE__; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		return 0 !== strpos( wp_normalize_path( $script ), wp_normalize_path( $restrict ) );
	}

	/**
	 * Read a boolean ini directive.
	 *
	 * ini_get() works even when every opcache_* function is disabled, which
	 * makes it the only reliable way to tell "off" from "hidden".
	 *
	 * @param string $name Directive.
	 * @return bool|null Null when the directive does not exist.
	 */
	private static function ini_bool( $name ) {
		$value = ini_get( $name );
		if ( false === $value ) {
			return null;
		}
		return (bool) filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Is OPcache compiling into files on disk only, with no shared memory?
	 *
	 * @since 2.1.1
	 * @return bool
	 */
	public static function is_file_cache_only() {
		return true === self::ini_bool( 'opcache.file_cache_only' );
	}

	/**
	 * Is the shared-memory cache running, with its status readable here?
	 *
	 * This is the only state with memory and hit-rate figures, and the only
	 * one in which opcache_reset() does anything useful.
	 *
	 * @return bool
	 */
	public static function is_available() {
		return 'available' === self::state();
	}

	/**
	 * Can a whole-cache flush actually take effect?
	 *
	 * Only in shared-memory mode. In file-cache-only mode opcache_reset()
	 * leaves the on-disk cache untouched, so a button offering it would
	 * report success while changing nothing.
	 *
	 * @return bool
	 */
	public static function can_reset() {
		return self::is_available() && function_exists( 'opcache_reset' );
	}

	/**
	 * Can individual files be invalidated?
	 *
	 * Works in both shared-memory and file-cache modes: in file-cache mode
	 * opcache_invalidate() removes the file's entry from the disk cache.
	 *
	 * @since 2.1.1
	 * @return bool
	 */
	public static function can_invalidate() {
		return in_array( self::state(), array( 'available', 'file-cache-only' ), true )
			&& ( function_exists( 'opcache_invalidate' ) || function_exists( 'wp_opcache_invalidate' ) );
	}

	/**
	 * What OPcache is doing, as a machine-readable state.
	 *
	 * Only 'not-loaded' and 'disabled' mean OPcache is not running. Every
	 * other state is OPcache working, with more or less visibility. In
	 * 2.1.0 anything the plugin could not read was reported as 'disabled',
	 * which told SiteGround customers — whose servers run file-cache-only —
	 * that a working OPcache was off.
	 *
	 * @since 2.1.0
	 * @since 2.1.1 'disabled' now requires opcache.enable to be off; added
	 *              'file-cache-only' and 'hidden'.
	 * @return string 'available' | 'file-cache-only' | 'restricted' | 'hidden' | 'disabled' | 'not-loaded'
	 */
	public static function state() {
		static $state = null;
		if ( null !== $state ) {
			return $state;
		}

		if ( ! extension_loaded( 'Zend OPcache' ) && ! function_exists( 'opcache_get_status' ) ) {
			return $state = 'not-loaded';
		}

		// The only reliable test for "switched off": the directive itself.
		if ( false === self::ini_bool( 'opcache.enable' ) ) {
			return $state = 'disabled';
		}

		if ( self::is_file_cache_only() ) {
			return $state = 'file-cache-only';
		}

		if ( self::is_restricted() ) {
			return $state = 'restricted';
		}

		if ( ! function_exists( 'opcache_get_status' ) ) {
			return $state = 'hidden';
		}

		$status = @opcache_get_status( false ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( ! is_array( $status ) ) {
			// Enabled by ini, but the status call was refused — typically a
			// restrict_api path our approximation did not catch.
			return $state = 'hidden';
		}

		return $state = ( ! empty( $status['opcache_enabled'] ) ? 'available' : 'hidden' );
	}

	/**
	 * Is the OPcache extension loaded at all?
	 *
	 * @return bool
	 */
	public static function is_loaded() {
		return 'not-loaded' !== self::state();
	}

	// =====================================================================
	// Status
	// =====================================================================

	/**
	 * Read one OPcache directive.
	 *
	 * Prefers opcache_get_configuration(), which reports the effective value,
	 * and falls back to ini_get() where that function is unavailable.
	 *
	 * @param array  $directives Result of opcache_get_configuration()['directives'], or empty.
	 * @param string $name       Directive name, e.g. 'opcache.validate_timestamps'.
	 * @return mixed|null
	 */
	private static function directive( $directives, $name ) {
		if ( isset( $directives[ $name ] ) ) {
			return $directives[ $name ];
		}
		$value = ini_get( $name );
		return ( false === $value ) ? null : $value;
	}

	/**
	 * Normalised status for the Diagnostics panel.
	 *
	 * Every key is always present, so the template never needs isset().
	 * Missing data reads as null and renders as "unknown". The file-check
	 * directives are read in every running state, since ini_get() needs no
	 * API access and they matter most where the statistics are unavailable.
	 *
	 * @return array
	 */
	public static function status() {
		$out = array(
			'state'               => self::state(),
			'can_reset'           => false,
			'can_invalidate'      => false,
			'memory_total'        => null,
			'memory_used'         => null,
			'memory_free'         => null,
			'memory_wasted'       => null,
			'wasted_percent'      => null,
			'hit_rate'            => null,
			'hits'                => null,
			'misses'              => null,
			'cached_scripts'      => null,
			'cached_keys'         => null,
			'max_keys'            => null,
			'oom_restarts'        => null,
			'hash_restarts'       => null,
			'manual_restarts'     => null,
			'start_time'          => null,
			'last_restart_time'   => null,
			'cache_full'          => false,
			'restart_pending'     => false,
			'validate_timestamps' => null,
			'revalidate_freq'     => null,
			'jit'                 => false,
			'last_manual_reset'   => get_site_option( self::LAST_RESET_OPTION, array() ),
		);

		if ( in_array( $out['state'], array( 'not-loaded', 'disabled' ), true ) ) {
			return $out;
		}

		$out['can_reset']      = self::can_reset();
		$out['can_invalidate'] = self::can_invalidate();

		$dirs = array();
		if ( 'available' === $out['state'] && function_exists( 'opcache_get_configuration' ) ) {
			$config = @opcache_get_configuration(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( is_array( $config ) && isset( $config['directives'] ) && is_array( $config['directives'] ) ) {
				$dirs = $config['directives'];
			}
		}

		$vt = self::directive( $dirs, 'opcache.validate_timestamps' );
		if ( null !== $vt ) {
			$out['validate_timestamps'] = (bool) filter_var( $vt, FILTER_VALIDATE_BOOLEAN );
		}
		$rf = self::directive( $dirs, 'opcache.revalidate_freq' );
		if ( null !== $rf && '' !== $rf ) {
			$out['revalidate_freq'] = (int) $rf;
		}

		if ( 'available' !== $out['state'] ) {
			return $out;
		}

		$status = @opcache_get_status( false ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( ! is_array( $status ) ) {
			return $out;
		}

		$mem = ( isset( $status['memory_usage'] ) && is_array( $status['memory_usage'] ) ) ? $status['memory_usage'] : array();
		if ( isset( $mem['used_memory'], $mem['free_memory'] ) ) {
			$out['memory_used']   = (int) $mem['used_memory'];
			$out['memory_free']   = (int) $mem['free_memory'];
			$out['memory_wasted'] = isset( $mem['wasted_memory'] ) ? (int) $mem['wasted_memory'] : 0;
			$out['memory_total']  = $out['memory_used'] + $out['memory_free'] + $out['memory_wasted'];
		}
		if ( isset( $mem['current_wasted_percentage'] ) ) {
			$out['wasted_percent'] = (float) $mem['current_wasted_percentage'];
		}

		$stats = ( isset( $status['opcache_statistics'] ) && is_array( $status['opcache_statistics'] ) ) ? $status['opcache_statistics'] : array();
		$map   = array(
			'hit_rate'          => 'opcache_hit_rate',
			'hits'              => 'hits',
			'misses'            => 'misses',
			'cached_scripts'    => 'num_cached_scripts',
			'cached_keys'       => 'num_cached_keys',
			'max_keys'          => 'max_cached_keys',
			'oom_restarts'      => 'oom_restarts',
			'hash_restarts'     => 'hash_restarts',
			'manual_restarts'   => 'manual_restarts',
			'start_time'        => 'start_time',
			'last_restart_time' => 'last_restart_time',
		);
		foreach ( $map as $key => $source ) {
			if ( isset( $stats[ $source ] ) ) {
				$out[ $key ] = ( 'hit_rate' === $key ) ? (float) $stats[ $source ] : (int) $stats[ $source ];
			}
		}

		$out['cache_full']      = ! empty( $status['cache_full'] );
		$out['restart_pending'] = ! empty( $status['restart_pending'] );
		$out['jit']             = ! empty( $status['jit']['enabled'] );

		return $out;
	}

	/**
	 * Plain-English findings for the panel, most serious first.
	 *
	 * @param array $status Result of status().
	 * @return array[] Each: array( 'level' => 'error'|'warning'|'info'|'success', 'message' => string ).
	 */
	public static function findings( $status ) {
		$findings = array();

		switch ( $status['state'] ) {
			case 'not-loaded':
				$findings[] = array(
					'level'   => 'warning',
					'message' => __( 'OPcache is not installed on this server. It stores compiled PHP so WordPress is not recompiled on every request, and is usually the single biggest PHP speed win available. Ask your host to enable it — it cannot be switched on from a plugin.', 'mbr-performance' ),
				);
				return $findings;

			case 'disabled':
				$findings[] = array(
					'level'   => 'warning',
					'message' => __( 'OPcache is installed but switched off (opcache.enable = 0). Ask your host to enable it — it cannot be switched on from a plugin.', 'mbr-performance' ),
				);
				return $findings;

			case 'file-cache-only':
				$findings[] = array(
					'level'   => 'success',
					'message' => __( 'OPcache is running in file-cache mode: compiled PHP is stored on disk rather than in shared memory. This is a normal configuration on some managed hosts. Memory and hit-rate figures do not exist in this mode, and a whole-cache flush does not clear the disk cache, so neither is offered here. Files this plugin writes are still refreshed individually as they change.', 'mbr-performance' ),
				);
				break;

			case 'restricted':
			case 'hidden':
				$findings[] = array(
					'level'   => 'success',
					'message' => __( 'OPcache is switched on, but your host does not let plugins read its statistics or flush it. That is a deliberate host setting, not a fault — your host\'s control panel usually offers a flush if you need one.', 'mbr-performance' ),
				);
				break;
		}

		// File-check behaviour is known from ini in every running state.
		if ( false === $status['validate_timestamps'] ) {
			$findings[] = array(
				'level'   => 'info',
				'message' => ( 'available' === $status['state'] )
					? __( 'This server does not check whether PHP files have changed (validate_timestamps is off). That is fast, but code you edit or upload outside WordPress keeps running the old version until OPcache is flushed. Updates through WordPress and this plugin\'s own files are handled automatically; for anything else, use Flush OPcache.', 'mbr-performance' )
					: __( 'This server does not check whether PHP files have changed (validate_timestamps is off). Code you edit or upload outside WordPress keeps running the old version until the host clears OPcache. Updates through WordPress and this plugin\'s own files are handled automatically.', 'mbr-performance' ),
			);
		}

		if ( 'available' !== $status['state'] ) {
			return $findings;
		}

		if ( $status['cache_full'] || ( null !== $status['oom_restarts'] && $status['oom_restarts'] > 0 ) ) {
			$findings[] = array(
				'level'   => 'error',
				'message' => __( 'OPcache has run out of memory. When it fills, PHP stops caching new files — or restarts the whole cache — until it is flushed. Ask your host to raise opcache.memory_consumption.', 'mbr-performance' ),
			);
		} elseif ( null !== $status['memory_total'] && $status['memory_total'] > 0 && ( $status['memory_free'] / $status['memory_total'] ) < 0.10 ) {
			$findings[] = array(
				'level'   => 'warning',
				'message' => __( 'OPcache memory is over 90% full. It will start evicting compiled files soon. Ask your host to raise opcache.memory_consumption.', 'mbr-performance' ),
			);
		}

		if ( null !== $status['max_keys'] && $status['max_keys'] > 0 && null !== $status['cached_keys'] && ( $status['cached_keys'] / $status['max_keys'] ) > 0.90 ) {
			$findings[] = array(
				'level'   => 'warning',
				'message' => __( 'OPcache is close to its limit on the number of files it can hold. Ask your host to raise opcache.max_accelerated_files.', 'mbr-performance' ),
			);
		}

		if ( null !== $status['hash_restarts'] && $status['hash_restarts'] > 0 ) {
			$findings[] = array(
				'level'   => 'warning',
				'message' => __( 'OPcache has restarted because it ran out of room for file entries. Ask your host to raise opcache.max_accelerated_files.', 'mbr-performance' ),
			);
		}

		if ( null !== $status['wasted_percent'] && $status['wasted_percent'] >= 10 ) {
			$findings[] = array(
				'level'   => 'info',
				'message' => __( 'Over 10% of OPcache memory is held by outdated copies of files that have since changed. A flush reclaims it.', 'mbr-performance' ),
			);
		}

		if ( null !== $status['hit_rate'] && null !== $status['hits'] && null !== $status['misses']
			&& ( $status['hits'] + $status['misses'] ) > 10000 && $status['hit_rate'] < 90 ) {
			$findings[] = array(
				'level'   => 'info',
				'message' => __( 'The hit rate is lower than usual for a settled server. That is normal shortly after a flush or restart; if it stays low, the memory or file limits above are the likely cause.', 'mbr-performance' ),
			);
		}

		return $findings;
	}

	// =====================================================================
	// Invalidation and reset
	// =====================================================================

	/**
	 * Invalidate one PHP file.
	 *
	 * Uses core's wp_opcache_invalidate() where it exists (WP 5.5+), which
	 * honours restrict_api and the wp_opcache_invalidate_file filter.
	 * Always forced: callers are telling us the file has just changed, and
	 * on a validate_timestamps=0 host an unforced invalidation is ignored.
	 *
	 * Safe to call when OPcache is absent — returns false and does nothing.
	 *
	 * @param string $file Absolute path.
	 * @return bool
	 */
	public static function invalidate( $file ) {
		if ( ! is_string( $file ) || '' === $file || '.php' !== strtolower( substr( $file, -4 ) ) ) {
			return false;
		}

		if ( function_exists( 'wp_opcache_invalidate' ) ) {
			return (bool) wp_opcache_invalidate( $file, true );
		}

		if ( ! function_exists( 'opcache_invalidate' ) || self::is_restricted() ) {
			return false;
		}

		return (bool) @opcache_invalidate( $file, true ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}

	/**
	 * Invalidate every PHP file beneath a directory.
	 *
	 * @param string $dir Absolute path.
	 * @return int Files invalidated.
	 */
	public static function invalidate_dir( $dir ) {
		if ( ! is_string( $dir ) || '' === $dir || ! is_dir( $dir ) ) {
			return 0;
		}
		if ( ! self::is_loaded() || self::is_restricted() ) {
			return 0;
		}

		$count = 0;

		try {
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ),
				RecursiveIteratorIterator::LEAVES_ONLY
			);
			foreach ( $iterator as $item ) {
				if ( $item->isFile() && 'php' === strtolower( $item->getExtension() ) ) {
					if ( self::invalidate( $item->getPathname() ) ) {
						++$count;
					}
				}
			}
		} catch ( Exception $e ) {
			// Unreadable subdirectory: invalidate what we reached and move on.
			unset( $e );
		}

		return $count;
	}

	/**
	 * Flush the whole cache.
	 *
	 * opcache_reset() schedules the reset; it takes effect on the next
	 * request. On some shared hosts the cache is shared by a pool of sites,
	 * so prefer invalidate() wherever a specific file is known.
	 *
	 * @param string $reason Short label recorded for the panel.
	 * @return bool
	 */
	public static function reset( $reason = 'manual' ) {
		if ( ! self::can_reset() ) {
			return false;
		}

		$ok = (bool) @opcache_reset(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		if ( $ok ) {
			update_site_option(
				self::LAST_RESET_OPTION,
				array(
					'time'   => time(),
					'reason' => sanitize_key( $reason ),
					'user'   => get_current_user_id(),
				)
			);
		}

		/**
		 * Fires after an OPcache flush is attempted.
		 *
		 * @since 2.1.0
		 * @param bool   $ok     Whether opcache_reset() reported success.
		 * @param string $reason Why.
		 */
		do_action( 'mbrpe_opcache_reset', $ok, $reason );

		return $ok;
	}

	// =====================================================================
	// Upgrade fallback (WordPress < 6.2)
	// =====================================================================

	/**
	 * Invalidate updated code after an upgrade.
	 *
	 * Only hooked on WordPress older than 6.2; newer versions do this in
	 * WP_Upgrader::install_package() themselves.
	 *
	 * @param WP_Upgrader $upgrader   Upgrader instance.
	 * @param array       $hook_extra Details of what was upgraded.
	 * @return void
	 */
	public function on_upgrade( $upgrader, $hook_extra ) {
		unset( $upgrader );

		if ( ! is_array( $hook_extra ) || empty( $hook_extra['type'] ) ) {
			return;
		}

		switch ( $hook_extra['type'] ) {
			case 'plugin':
				$plugins = array();
				if ( ! empty( $hook_extra['plugins'] ) && is_array( $hook_extra['plugins'] ) ) {
					$plugins = $hook_extra['plugins'];
				} elseif ( ! empty( $hook_extra['plugin'] ) ) {
					$plugins = array( $hook_extra['plugin'] );
				}
				foreach ( $plugins as $plugin ) {
					$plugin = (string) $plugin;
					$folder = dirname( $plugin );
					if ( '.' === $folder ) {
						self::invalidate( WP_PLUGIN_DIR . '/' . $plugin );
					} else {
						self::invalidate_dir( WP_PLUGIN_DIR . '/' . $folder );
					}
				}
				break;

			case 'theme':
				$themes = array();
				if ( ! empty( $hook_extra['themes'] ) && is_array( $hook_extra['themes'] ) ) {
					$themes = $hook_extra['themes'];
				} elseif ( ! empty( $hook_extra['theme'] ) ) {
					$themes = array( $hook_extra['theme'] );
				}
				foreach ( $themes as $theme ) {
					self::invalidate_dir( get_theme_root( $theme ) . '/' . $theme );
				}
				break;

			case 'translation':
				if ( defined( 'WP_LANG_DIR' ) ) {
					self::invalidate_dir( WP_LANG_DIR );
				}
				break;

			case 'core':
				// Core replaces too many files to list; flush the lot.
				self::reset( 'core-update' );
				break;
		}
	}

	// =====================================================================
	// Admin actions
	// =====================================================================

	/**
	 * URL that flushes OPcache, for buttons and the toolbar.
	 *
	 * @return string
	 */
	public static function reset_url() {
		return wp_nonce_url( admin_url( 'admin-post.php?action=mbrpe_opcache_reset' ), 'mbrpe_opcache_reset' );
	}

	/**
	 * Handle the flush request.
	 *
	 * @return void
	 */
	public function handle_reset_request() {
		if ( ! self::current_user_can_flush() ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'mbr-performance' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'mbrpe_opcache_reset' );

		$result = self::reset( 'manual' ) ? 'reset' : 'failed';

		$back = wp_get_referer();
		if ( ! $back ) {
			$back = admin_url( 'admin.php?page=mbr-performance&tab=diagnostics' );
		}

		wp_safe_redirect( add_query_arg( 'mbrpe_opcache', $result, $back ) );
		exit;
	}

	/**
	 * Confirm the flush after the redirect.
	 *
	 * @return void
	 */
	public function maybe_show_reset_notice() {
		if ( ! isset( $_GET['mbrpe_opcache'] ) || ! self::current_user_can_flush() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$result = sanitize_key( wp_unslash( $_GET['mbrpe_opcache'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'reset' === $result ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html__( 'OPcache flushed. PHP files will be recompiled from disk as they are next used.', 'mbr-performance' )
			);
		} elseif ( 'failed' === $result ) {
			printf(
				'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
				esc_html__( 'OPcache could not be flushed. Your host may have disabled the flush function; its control panel usually offers the same action.', 'mbr-performance' )
			);
		}
	}

	/**
	 * Add "Flush OPcache" to the MBR Performance toolbar menu.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Toolbar.
	 * @return void
	 */
	public function admin_bar_menu( $wp_admin_bar ) {
		if ( ! self::current_user_can_flush() || ! self::can_reset() ) {
			return;
		}

		$wp_admin_bar->add_node(
			array(
				'parent' => 'mbr-performance',
				'id'     => 'mbr-performance-opcache-reset',
				'title'  => __( 'Flush OPcache', 'mbr-performance' ),
				'href'   => self::reset_url(),
			)
		);
	}
}
