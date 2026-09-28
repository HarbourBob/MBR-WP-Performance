<?php
/**
 * Full Page Cache — drop-in and configuration management.
 *
 * Owns the three pieces of state that live outside the plugin directory:
 *
 *   1. wp-content/advanced-cache.php — the drop-in itself.
 *   2. The WP_CACHE constant in wp-config.php, without which WordPress never
 *      loads the drop-in.
 *   3. wp-content/cache/mbr-performance/config.php — the compiled settings the
 *      drop-in reads, since it cannot reach the options table.
 *
 * All three are best-effort. Managed hosts routinely make wp-config.php
 * read-only and some ship their own advanced-cache.php; none of that is an
 * error, it just means the engine falls back to serving from inside WordPress.
 * Every method here reports honestly rather than throwing, and the admin tab
 * turns those reports into plain-English guidance.
 *
 * @package MBRPE
 * @since   2.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MBRPE_Page_Cache_Dropin {

	/**
	 * Contract version. Must match MBRPE_DROPIN_VERSION in the drop-in template.
	 */
	const DROPIN_VERSION = '2.0.1';

	/**
	 * Absolute path to the installed drop-in.
	 *
	 * @return string
	 */
	public static function dropin_path() {
		return WP_CONTENT_DIR . '/advanced-cache.php';
	}

	/**
	 * Absolute path to the bundled template.
	 *
	 * @return string
	 */
	public static function template_path() {
		return MBRPE_PLUGIN_DIR . 'includes/dropins/advanced-cache.php';
	}

	/**
	 * Absolute path to the compiled config.
	 *
	 * @return string
	 */
	public static function config_path() {
		return MBRPE_Page_Cache::cache_dir() . '/config.php';
	}

	// =====================================================================
	// Status
	// =====================================================================

	/**
	 * Is our drop-in installed, current, and actually being loaded?
	 *
	 * @return bool
	 */
	public static function is_active() {
		return defined( 'WP_CACHE' ) && WP_CACHE && 'ours-current' === self::dropin_state();
	}

	/**
	 * Classify whatever is sitting at wp-content/advanced-cache.php.
	 *
	 * @return string One of: 'missing', 'foreign', 'ours-stale', 'ours-current'.
	 */
	public static function dropin_state() {
		$path = self::dropin_path();

		if ( ! file_exists( $path ) ) {
			return 'missing';
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_get_contents -- reading our own drop-in; WP_Filesystem needs credentials we may not have.
		$contents = @file_get_contents( $path, false, null, 0, 4096 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( false === $contents ) {
			return 'foreign';
		}

		if ( false === strpos( $contents, 'MBRPE_DROPIN_VERSION' ) ) {
			return 'foreign';
		}

		if ( preg_match( "/define\(\s*'MBRPE_DROPIN_VERSION'\s*,\s*'([^']+)'/", $contents, $m ) ) {
			return version_compare( $m[1], self::DROPIN_VERSION, '>=' ) ? 'ours-current' : 'ours-stale';
		}

		return 'ours-stale';
	}

	/**
	 * Full health report for the admin tab.
	 *
	 * @return array
	 */
	public static function health() {
		$dropin_state = self::dropin_state();

		return array(
			'wp_cache_defined'  => defined( 'WP_CACHE' ) && WP_CACHE,
			'wp_config_writable' => self::wp_config_is_writable(),
			'content_writable'  => wp_is_writable( WP_CONTENT_DIR ),
			'cache_writable'    => self::cache_dir_is_writable(),
			'dropin_state'      => $dropin_state,
			'dropin_active'     => self::is_active(),
			'config_present'    => file_exists( self::config_path() ),
			'competing'         => MBRPE_Page_Cache::competing_cache(),
			'server'            => class_exists( 'MBRPE_Server_Headers' ) ? MBRPE_Server_Headers::detect_server() : 'other',
		);
	}

	/**
	 * Is the cache root present and writable?
	 *
	 * @return bool
	 */
	public static function cache_dir_is_writable() {
		$dir = MBRPE_Page_Cache::cache_dir();
		if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
			return false;
		}
		return wp_is_writable( $dir );
	}

	/**
	 * Locate wp-config.php the same way wp-load.php does.
	 *
	 * @return string|false
	 */
	public static function wp_config_path() {
		$candidate = ABSPATH . 'wp-config.php';
		if ( file_exists( $candidate ) ) {
			return $candidate;
		}

		// One level up, but only if that directory is not another WP install.
		$candidate = dirname( ABSPATH ) . '/wp-config.php';
		if ( file_exists( $candidate ) && ! file_exists( dirname( ABSPATH ) . '/wp-settings.php' ) ) {
			return $candidate;
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public static function wp_config_is_writable() {
		$path = self::wp_config_path();
		return $path && wp_is_writable( $path );
	}

	// =====================================================================
	// Install / uninstall
	// =====================================================================

	/**
	 * Install (or refresh) the drop-in and the WP_CACHE constant.
	 *
	 * @param bool $force Overwrite a foreign advanced-cache.php.
	 * @return true|WP_Error
	 */
	public static function install( $force = false ) {
		$competing = MBRPE_Page_Cache::competing_cache();
		if ( $competing ) {
			return new WP_Error(
				'mbrpe_cache_conflict',
				sprintf(
					/* translators: %s: name of the competing caching plugin */
					__( '%s is active and already caches pages. Running two page caches at once produces stale and mismatched content, so MBR page caching will stay off until one of them is disabled.', 'mbr-performance' ),
					$competing
				)
			);
		}

		$state = self::dropin_state();
		if ( 'foreign' === $state && ! $force ) {
			return new WP_Error(
				'mbrpe_dropin_foreign',
				__( 'Another plugin already owns wp-content/advanced-cache.php. MBR Performance will not overwrite it automatically — remove the other caching plugin first, or use the Replace button to take ownership.', 'mbr-performance' )
			);
		}

		if ( ! is_readable( self::template_path() ) ) {
			return new WP_Error( 'mbrpe_dropin_template', __( 'The bundled advanced-cache.php template is missing from the plugin. Try reinstalling MBR Performance.', 'mbr-performance' ) );
		}

		// Write the config first. A drop-in that loads before its config exists
		// simply returns and the site renders normally, but there is no reason
		// to leave even that window open.
		$config_written = self::write_config();
		if ( is_wp_error( $config_written ) ) {
			return $config_written;
		}

		if ( ! wp_is_writable( WP_CONTENT_DIR ) ) {
			return new WP_Error(
				'mbrpe_content_readonly',
				__( 'wp-content is not writable, so advanced-cache.php cannot be installed. Page caching will still work, but pages will be served from inside WordPress rather than before it — noticeably slower, though still a large improvement.', 'mbr-performance' )
			);
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy
		if ( ! @copy( self::template_path(), self::dropin_path() ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			return new WP_Error( 'mbrpe_dropin_copy', __( 'Could not write wp-content/advanced-cache.php.', 'mbr-performance' ) );
		}

		@chmod( self::dropin_path(), 0644 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		// A refreshed drop-in is invisible on a validate_timestamps=0 host until
		// OPcache is flushed, so the old reader would keep pairing with the new
		// writer. Invalidate it the moment it lands.
		MBRPE_OPcache::invalidate( self::dropin_path() );

		$const = self::set_wp_cache_constant( true );
		if ( is_wp_error( $const ) ) {
			// The drop-in is in place but dormant. Report it; the tab explains
			// the one-line manual fix.
			return $const;
		}

		return true;
	}

	/**
	 * Remove the drop-in and unset WP_CACHE.
	 *
	 * Never touches a foreign advanced-cache.php.
	 *
	 * @return true|WP_Error
	 */
	public static function uninstall() {
		$state = self::dropin_state();

		if ( in_array( $state, array( 'ours-current', 'ours-stale' ), true ) ) {
			// Invalidate before deleting: OPcache resolves the path to find the
			// entry, which it cannot do once the file is gone.
			MBRPE_OPcache::invalidate( self::dropin_path() );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_unlink
			@unlink( self::dropin_path() ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		// Only clear WP_CACHE if no other cache plugin needs it. Stripping it
		// from under WP Super Cache would break somebody else's site.
		if ( ! MBRPE_Page_Cache::competing_cache() && 'foreign' !== self::dropin_state() ) {
			self::set_wp_cache_constant( false );
		}

		MBRPE_OPcache::invalidate( self::config_path() );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_unlink
		@unlink( self::config_path() ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		return true;
	}

	/**
	 * Add or remove `define( 'WP_CACHE', true );` in wp-config.php.
	 *
	 * The regex approach is borrowed from how every caching plugin has done this
	 * for a decade. It is unglamorous but it is the only way: WordPress reads
	 * WP_CACHE before any code of ours could filter it.
	 *
	 * @param bool $enable Desired state.
	 * @return true|WP_Error
	 */
	public static function set_wp_cache_constant( $enable ) {
		// Already in the desired state.
		if ( $enable && defined( 'WP_CACHE' ) && WP_CACHE ) {
			return true;
		}
		if ( ! $enable && ( ! defined( 'WP_CACHE' ) || ! WP_CACHE ) ) {
			return true;
		}

		/**
		 * Allow hosts and site owners to forbid wp-config.php edits.
		 *
		 * @since 2.0.0
		 * @param bool $readonly
		 */
		if ( apply_filters( 'mbrpe_wpconfig_readonly', false ) ) {
			return new WP_Error( 'mbrpe_wpconfig_readonly', __( 'Editing wp-config.php is disabled by a filter on this site.', 'mbr-performance' ) );
		}

		$path = self::wp_config_path();
		if ( ! $path ) {
			return new WP_Error( 'mbrpe_wpconfig_missing', __( 'wp-config.php could not be located.', 'mbr-performance' ) );
		}

		if ( ! wp_is_writable( $path ) ) {
			return new WP_Error(
				'mbrpe_wpconfig_readonly_file',
				__( 'wp-config.php is not writable. Add this line just below the opening &lt;?php tag to finish enabling the cache: define( \'WP_CACHE\', true );', 'mbr-performance' )
			);
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_get_contents
		$content = @file_get_contents( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( false === $content || '' === $content ) {
			return new WP_Error( 'mbrpe_wpconfig_unreadable', __( 'wp-config.php could not be read.', 'mbr-performance' ) );
		}

		// Keep a one-off backup. Editing a site's wp-config.php deserves one.
		$backup = $path . '.mbrpe-backup';
		if ( ! file_exists( $backup ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy
			@copy( $path, $backup ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		// Remove any existing definition, ours or otherwise.
		$updated = preg_replace( '/^[ \t]*define\(\s*([\'"])WP_CACHE\1\s*,\s*[^)]*\)\s*;.*$\R?/mi', '', $content );
		if ( null === $updated ) {
			return new WP_Error( 'mbrpe_wpconfig_regex', __( 'wp-config.php could not be parsed safely, so it was left untouched.', 'mbr-performance' ) );
		}

		if ( $enable ) {
			$line    = "define( 'WP_CACHE', true ); // Added by MBR Performance" . PHP_EOL;
			$updated = preg_replace( '/^<\?php\s*$/m', '<?php' . PHP_EOL . $line, $updated, 1, $count );
			if ( empty( $count ) ) {
				return new WP_Error( 'mbrpe_wpconfig_noopen', __( 'The opening PHP tag in wp-config.php was not in the expected position, so the file was left untouched.', 'mbr-performance' ) );
			}
		}

		// Refuse to write anything that is not valid PHP. A corrupt wp-config.php
		// is a white screen with no route back in.
		if ( ! self::is_valid_php( $updated ) ) {
			return new WP_Error( 'mbrpe_wpconfig_invalid', __( 'The edited wp-config.php did not pass a syntax check, so the original was left untouched.', 'mbr-performance' ) );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		if ( false === @file_put_contents( $path, $updated, LOCK_EX ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			return new WP_Error( 'mbrpe_wpconfig_write', __( 'wp-config.php could not be written.', 'mbr-performance' ) );
		}

		// Without this, a validate_timestamps=0 host keeps executing the old
		// wp-config.php — WP_CACHE added or removed on disk, but not in effect.
		MBRPE_OPcache::invalidate( $path );

		return true;
	}

	/**
	 * Syntax-check a PHP source string without executing it.
	 *
	 * Uses the tokeniser where php -l is unavailable, which on shared hosting is
	 * most of the time.
	 *
	 * @param string $source PHP source.
	 * @return bool
	 */
	private static function is_valid_php( $source ) {
		if ( function_exists( 'token_get_all' ) ) {
			try {
				// PHP 7+ throws ParseError from token_get_all() with TOKEN_PARSE.
				token_get_all( $source, TOKEN_PARSE );
				return true;
			} catch ( ParseError $e ) {
				return false;
			} catch ( Error $e ) {
				return false;
			} catch ( Exception $e ) {
				return false;
			}
		}

		// Without the tokeniser, fall back to a structural sanity check.
		return false !== strpos( $source, '<?php' );
	}

	// =====================================================================
	// Compiled config
	// =====================================================================

	/**
	 * Build the settings array the drop-in consumes.
	 *
	 * Also used by MBRPE_Page_Cache at runtime, so the writer and the reader
	 * agree on exclusions without duplicating the defaults.
	 *
	 * Memoised, because it is consulted several times per front-end request.
	 * Pass $force after saving settings, when the memo is stale by definition.
	 *
	 * @param bool $force Rebuild even if already memoised.
	 * @return array
	 */
	public static function build_config( $force = false ) {
		static $config = null;
		if ( null !== $config && ! $force ) {
			return $config;
		}

		$opts = mbrpe()->get_options( 'cache' );

		$structure      = get_option( 'permalink_structure' );
		$trailing_slash = null;
		if ( ! empty( $structure ) ) {
			$trailing_slash = ( '/' === substr( $structure, -1 ) );
		}

		$config = array(
			'version'             => MBRPE_VERSION,
			'key_algo'            => MBRPE_Page_Cache::KEY_ALGO,
			'enabled'             => ! empty( $opts['enabled'] ) && ! MBRPE_Page_Cache::competing_cache(),
			'cache_dir'           => MBRPE_Page_Cache::cache_dir(),
			'ttl'                 => isset( $opts['ttl'] ) ? max( 0, (int) $opts['ttl'] ) : MBRPE_Page_Cache::DEFAULT_TTL,
			'gzip'                => ! empty( $opts['gzip'] ),
			'vary_mobile'         => ! empty( $opts['vary_mobile'] ),
			'trailing_slash'      => $trailing_slash,
			'debug_header'        => ! empty( $opts['debug_header'] ),
			'cache_query_strings' => ! empty( $opts['cache_query_strings'] ),
			'mobile_agents'       => 'Mobile|Android|Silk/|Kindle|BlackBerry|Opera Mini|Opera Mobi|iPhone|iPod',
			'scheme'              => self::canonical_scheme(),
			'trust_proxy'         => false,
			'hosts'               => self::known_hosts(),
			'exclude_uris'        => self::exclude_uris( $opts ),
			'exclude_cookies'     => self::exclude_cookies( $opts ),
			'exclude_agents'      => self::exclude_agents( $opts ),
			'ignored_qs'          => self::ignored_qs( $opts ),
			'allowed_qs'          => self::lines_to_array( isset( $opts['allowed_qs'] ) ? $opts['allowed_qs'] : '' ),
		);

		/**
		 * Filter the compiled drop-in configuration.
		 *
		 * @since 2.0.0
		 * @param array $config Compiled config.
		 */
		$config = apply_filters( 'mbrpe_cache_config', $config );

		return $config;
	}

	/**
	 * The scheme every cacheable page on this site is served under.
	 *
	 * Pinned from home_url() rather than sniffed per request, so the drop-in
	 * and the plugin cannot disagree and no forwarded header can steer either
	 * of them. A site genuinely serving different content over http and https
	 * is not a thing WordPress supports, so one branch is the right answer.
	 *
	 * @since 2.0.1
	 * @return string 'https', 'http', or '' to fall back to request detection.
	 */
	public static function canonical_scheme() {
		$scheme = wp_parse_url( home_url(), PHP_URL_SCHEME );

		return in_array( $scheme, array( 'http', 'https' ), true ) ? $scheme : '';
	}

	/**
	 * Hosts this install answers to, used to reject forged Host headers.
	 *
	 * Empty on multisite: config.php is a single file shared by every blog on
	 * the network, so any list built from one blog's home_url() would lock the
	 * others out of the cache. The drop-in's syntax validation still applies
	 * there; it just has no allow-list to consult.
	 *
	 * @since 2.0.1
	 * @return string[] Lowercase hosts, with port where one is in use.
	 */
	public static function known_hosts() {
		if ( is_multisite() ) {
			return array();
		}

		$hosts = array();
		foreach ( array( home_url(), site_url() ) as $url ) {
			$host = wp_parse_url( $url, PHP_URL_HOST );
			if ( ! $host ) {
				continue;
			}
			$host = strtolower( $host );
			$port = wp_parse_url( $url, PHP_URL_PORT );

			// Accept the www/bare counterpart too. A site that answers on both
			// without redirecting is common, and the failure mode otherwise is
			// a silently uncached hostname, which is miserable to diagnose.
			$variants = array( $host );
			if ( 0 === strpos( $host, 'www.' ) ) {
				$variants[] = substr( $host, 4 );
			} else {
				$variants[] = 'www.' . $host;
			}

			foreach ( $variants as $variant ) {
				$hosts[] = $variant;
				if ( $port ) {
					$hosts[] = $variant . ':' . (int) $port;
				}
			}
		}

		/**
		 * Filter the hosts eligible for caching.
		 *
		 * Add entries here for domain aliases or a staging hostname that should
		 * keep its own cache branch.
		 *
		 * @since 2.0.1
		 * @param string[] $hosts Lowercase hosts.
		 */
		return array_values( array_unique( (array) apply_filters( 'mbrpe_cache_hosts', $hosts ) ) );
	}

	/**
	 * Default plus user-configured URI exclusions.
	 *
	 * The defaults are the paths that are wrong to cache on essentially every
	 * WordPress site. WooCommerce and EDD entries are harmless when those
	 * plugins are absent, so they are unconditional.
	 *
	 * @param array $opts Cache options.
	 * @return array
	 */
	public static function exclude_uris( $opts ) {
		$defaults = array(
			'/wp-admin(*)',
			'/wp-login.php(*)',
			'/wp-cron.php(*)',
			'/wp-json(*)',
			'/xmlrpc.php(*)',
			// WooCommerce.
			'/cart(*)',
			'/checkout(*)',
			'/my-account(*)',
			// Easy Digital Downloads.
			'/purchase-confirmation(*)',
			'/purchase-history(*)',
			'/transaction-failed(*)',
		);

		// Prefer the real permalinks where WooCommerce is present — a shop whose
		// checkout lives at /kassa/ gets no protection from a hardcoded /checkout.
		if ( function_exists( 'wc_get_page_id' ) ) {
			foreach ( array( 'cart', 'checkout', 'myaccount' ) as $wc_page ) {
				$page_id = wc_get_page_id( $wc_page );
				if ( $page_id > 0 ) {
					$permalink = get_permalink( $page_id );
					if ( $permalink ) {
						$path = wp_parse_url( $permalink, PHP_URL_PATH );
						if ( $path && '/' !== $path ) {
							$defaults[] = rtrim( $path, '/' ) . '(*)';
						}
					}
				}
			}
		}

		$user = self::lines_to_array( isset( $opts['exclude_uris'] ) ? $opts['exclude_uris'] : '' );

		/**
		 * Filter the URI exclusion list.
		 *
		 * @since 2.0.0
		 * @param array $rules Exclusion rules.
		 */
		return apply_filters( 'mbrpe_cache_exclude_uris', array_values( array_unique( array_merge( $defaults, $user ) ) ) );
	}

	/**
	 * Cookie prefixes that disqualify a request.
	 *
	 * @param array $opts Cache options.
	 * @return array
	 */
	public static function exclude_cookies( $opts ) {
		$defaults = array(
			'wordpress_logged_in_',
			'comment_author_',
			'wp-postpass_',
			'wp_woocommerce_session_',
			'woocommerce_items_in_cart',
			'woocommerce_cart_hash',
			'edd_items_in_cart',
			'wp-resetpass-',
			'no_cache',
		);

		$user = self::lines_to_array( isset( $opts['exclude_cookies'] ) ? $opts['exclude_cookies'] : '' );

		/**
		 * Filter the cookie exclusion list.
		 *
		 * @since 2.0.0
		 * @param array $prefixes Cookie name prefixes.
		 */
		return apply_filters( 'mbrpe_cache_exclude_cookies', array_values( array_unique( array_merge( $defaults, $user ) ) ) );
	}

	/**
	 * User-agent substrings that disqualify a request.
	 *
	 * Empty by default. Excluding bots is a common instinct and almost always
	 * wrong: crawlers are exactly the traffic a cache serves most cheaply, and
	 * Google explicitly wants to see the same page a visitor gets.
	 *
	 * @param array $opts Cache options.
	 * @return array
	 */
	public static function exclude_agents( $opts ) {
		$user = self::lines_to_array( isset( $opts['exclude_agents'] ) ? $opts['exclude_agents'] : '' );

		/**
		 * Filter the user-agent exclusion list.
		 *
		 * @since 2.0.0
		 * @param array $agents Substrings.
		 */
		return apply_filters( 'mbrpe_cache_exclude_agents', $user );
	}

	/**
	 * Query parameters stripped before keying.
	 *
	 * This is the single highest-leverage setting in the module. Without it,
	 * every newsletter and every ad campaign generates a fresh cache miss for a
	 * page that is byte-for-byte identical.
	 *
	 * @param array $opts Cache options.
	 * @return array
	 */
	public static function ignored_qs( $opts ) {
		$defaults = array(
			'utm_*',
			'fbclid',
			'gclid',
			'gbraid',
			'wbraid',
			'msclkid',
			'dclid',
			'twclid',
			'ttclid',
			'igshid',
			'mc_cid',
			'mc_eid',
			'_ga',
			'_gl',
			'mtm_*',
			'pk_*',
			'ref',
			'referrer',
			'yclid',
			'epik',
			'age-verified',
			'usqp',
		);

		$user = self::lines_to_array( isset( $opts['ignored_qs'] ) ? $opts['ignored_qs'] : '' );

		/**
		 * Filter the ignored query-parameter list.
		 *
		 * @since 2.0.0
		 * @param array $params Parameter names, trailing * allowed.
		 */
		return apply_filters( 'mbrpe_cache_ignored_qs', array_values( array_unique( array_merge( $defaults, $user ) ) ) );
	}

	/**
	 * Split a textarea value into a clean array.
	 *
	 * @param string $value Raw textarea content.
	 * @return array
	 */
	public static function lines_to_array( $value ) {
		if ( is_array( $value ) ) {
			return array_values( array_filter( array_map( 'trim', $value ) ) );
		}
		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return array();
		}

		$lines = preg_split( '/\R/', $value );

		return array_values( array_filter( array_map( 'trim', (array) $lines ) ) );
	}

	/**
	 * Compile and write config.php.
	 *
	 * @param bool $force Rebuild the memoised config first.
	 * @return true|WP_Error
	 */
	public static function write_config( $force = false ) {
		$dir = MBRPE_Page_Cache::cache_dir();
		if ( ! wp_mkdir_p( $dir ) ) {
			return new WP_Error( 'mbrpe_cache_mkdir', __( 'The cache directory could not be created under wp-content/cache/.', 'mbr-performance' ) );
		}

		$config = self::build_config( $force );

		$source = "<?php\n"
			. "/**\n"
			. " * Generated by MBR Performance " . MBRPE_VERSION . ". Do not edit.\n"
			. " * Rewritten whenever the cache settings are saved.\n"
			. " */\n"
			. "defined( 'ABSPATH' ) || exit;\n\n"
			. 'return ' . var_export( $config, true ) . ";\n"; // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export

		$tmp = $dir . '/config.php.tmp';

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		if ( false === @file_put_contents( $tmp, $source, LOCK_EX ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			return new WP_Error( 'mbrpe_cache_config_write', __( 'The cache configuration file could not be written.', 'mbr-performance' ) );
		}

		if ( ! @rename( $tmp, self::config_path() ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			@unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			return new WP_Error( 'mbrpe_cache_config_rename', __( 'The cache configuration file could not be put into place.', 'mbr-performance' ) );
		}

		@chmod( self::config_path(), 0644 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		// OPcache holds the previous config in memory. On SiteGround, where
		// validate_timestamps is 0, a rewritten config would otherwise be
		// invisible until the next OPcache flush — which is exactly the class of
		// bug that is maddening to diagnose.
		MBRPE_OPcache::invalidate( self::config_path() );

		return true;
	}

	/**
	 * Re-compile config whenever plugin settings are saved.
	 *
	 * Hooked in the bootstrap so it runs regardless of whether the cache module
	 * itself instantiated.
	 *
	 * @return void
	 */
	public static function on_options_updated() {
		// Force a rebuild: the memoised copy predates this save.
		self::write_config( true );

		// If the operator just switched caching on, put the drop-in in place
		// without making them find a second button.
		$opts = mbrpe()->get_options( 'cache' );
		if ( ! empty( $opts['enabled'] ) && ! self::is_active() && 'foreign' !== self::dropin_state() ) {
			self::install();
		} elseif ( empty( $opts['enabled'] ) && self::is_active() ) {
			self::uninstall();
		}

		// Settings changes alter what pages look like, so anything already
		// stored is suspect.
		if ( class_exists( 'MBRPE_Page_Cache_Purge' ) ) {
			MBRPE_Page_Cache_Purge::purge_all( 'settings-saved' );
		}
	}

	// =====================================================================
	// Admin actions
	// =====================================================================

	/**
	 * Register the admin-post routes used by the Cache tab's repair buttons.
	 *
	 * Registered from the bootstrap rather than a constructor, because these
	 * must work even when the cache module itself declined to instantiate —
	 * which is precisely the situation the repair buttons exist to fix.
	 *
	 * @return void
	 */
	public static function register_admin_actions() {
		add_action( 'admin_post_mbrpe_cache_action', array( __CLASS__, 'handle_admin_action' ) );
	}

	/**
	 * Dispatch a maintenance action from the Cache tab.
	 *
	 * @return void
	 */
	public static function handle_admin_action() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'mbr-performance' ) );
		}
		check_admin_referer( 'mbrpe_cache_action' );

		$do     = isset( $_GET['do'] ) ? sanitize_key( wp_unslash( $_GET['do'] ) ) : '';
		$result = true;

		switch ( $do ) {
			case 'install_dropin':
				$result = self::install();
				break;

			case 'replace_dropin':
				// Explicit operator consent to take ownership of a foreign
				// advanced-cache.php. Never automatic.
				$result = self::install( true );
				break;

			case 'remove_dropin':
				$result = self::uninstall();
				break;

			case 'install_rules':
				$result = MBRPE_Page_Cache_Rules::install();
				break;

			case 'remove_rules':
				MBRPE_Page_Cache_Rules::uninstall();
				break;

			case 'rewrite_config':
				$result = self::write_config( true );
				break;
		}

		$args = array( 'page' => 'mbr-performance', 'tab' => 'cache' );

		if ( is_wp_error( $result ) ) {
			set_transient( 'mbrpe_cache_action_error', $result->get_error_message(), 60 );
			$args['mbrpe_cache_result'] = 'error';
		} else {
			$args['mbrpe_cache_result'] = 'ok';
		}

		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Build a nonced URL for one of the maintenance actions.
	 *
	 * @param string $do Action key.
	 * @return string
	 */
	public static function action_url( $do ) {
		return wp_nonce_url(
			admin_url( 'admin-post.php?action=mbrpe_cache_action&do=' . rawurlencode( $do ) ),
			'mbrpe_cache_action'
		);
	}
}
