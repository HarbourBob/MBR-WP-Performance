<?php
/**
 * MBR Performance — advanced-cache.php drop-in.
 *
 * Installed to wp-content/advanced-cache.php by MBRPE_Page_Cache_Dropin. Runs
 * from wp-settings.php as soon as WP_CACHE is true — before the database is
 * touched, before plugins load, before the theme exists. A cache hit here costs
 * roughly a config include, a stat() and a readfile(): single-digit milliseconds
 * against the 200-800ms a cold WordPress bootstrap typically costs.
 *
 * DESIGN NOTE — why this file is self-contained.
 *
 * It cannot call into the plugin. At this point in the boot there is no
 * autoloader, no plugin API, no options table, and the plugin directory may have
 * been renamed or removed entirely. So everything this file needs is written out
 * ahead of time by the plugin into a plain PHP config array, and this file reads
 * only that. If the config is missing, unreadable or stale, this file returns
 * silently and WordPress boots normally — a broken cache must never be a broken
 * site.
 *
 * That independence has a cost: the cache-key algorithm below is duplicated in
 * class-page-cache.php, and the two MUST stay byte-identical or the writer and
 * the reader will disagree about filenames and the cache will never hit. Both
 * copies carry the MBRPE_CACHE_KEY_ALGO version marker. Bump it in both places
 * whenever the algorithm changes; the plugin compares its own marker against the
 * installed drop-in's and nags in the admin if they drift.
 *
 * @package MBRPE
 * @since   2.0.0
 */

// This file is loaded by wp-settings.php, which has already defined ABSPATH.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Drop-in contract version. Compared against MBRPE_Page_Cache_Dropin::DROPIN_VERSION.
if ( ! defined( 'MBRPE_DROPIN_VERSION' ) ) {
	define( 'MBRPE_DROPIN_VERSION', '2.0.1' );
}

// Cache-key algorithm version. Must match MBRPE_Page_Cache::KEY_ALGO.
if ( ! defined( 'MBRPE_CACHE_KEY_ALGO' ) ) {
	define( 'MBRPE_CACHE_KEY_ALGO', 1 );
}

/**
 * Entry point. Wrapped in a function so no variables leak into the global scope
 * that WordPress is about to populate — advanced-cache.php runs in global scope,
 * and a stray $path here would quietly clobber one in wp-settings.php.
 *
 * @return void
 */
function mbrpe_advanced_cache_boot() {

	// ---------------------------------------------------------------------
	// 1. Cheap disqualifiers, in ascending cost order.
	// ---------------------------------------------------------------------

	// Only GET and HEAD are ever cacheable. A POST must reach WordPress.
	$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) : 'GET';
	if ( 'GET' !== $method && 'HEAD' !== $method ) {
		return;
	}

	// WP-CLI and cron bootstrap through this file too. Serving them a cached
	// page would break `wp post list` in confusing ways.
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return;
	}
	if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
		return;
	}

	// An explicit bypass constant, for support debugging without deactivating.
	if ( defined( 'MBRPE_CACHE_DISABLE' ) && MBRPE_CACHE_DISABLE ) {
		return;
	}

	// ---------------------------------------------------------------------
	// 2. Config.
	// ---------------------------------------------------------------------

	$config_file = WP_CONTENT_DIR . '/cache/mbr-performance/config.php';
	if ( ! is_readable( $config_file ) ) {
		return;
	}

	$config = include $config_file;
	if ( ! is_array( $config ) || empty( $config['enabled'] ) ) {
		return;
	}

	// Refuse to act on a config written by a different key algorithm. Safer to
	// serve nothing than to serve the wrong page.
	if ( ! isset( $config['key_algo'] ) || (int) $config['key_algo'] !== (int) MBRPE_CACHE_KEY_ALGO ) {
		return;
	}

	$cache_dir = isset( $config['cache_dir'] ) ? (string) $config['cache_dir'] : '';
	if ( '' === $cache_dir || ! is_dir( $cache_dir ) ) {
		return;
	}

	// ---------------------------------------------------------------------
	// 3. Request analysis.
	// ---------------------------------------------------------------------

	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';
	$path        = mbrpe_cache_request_path( $request_uri );
	if ( false === $path ) {
		return; // Unsafe or undecodable path.
	}

	// Trailing-slash canonicalisation. WordPress 301s the non-canonical form;
	// if we served it from cache we would silently swallow that redirect and
	// hand search engines two live URLs for one page.
	if ( ! mbrpe_cache_path_is_canonical( $path, $config ) ) {
		return;
	}

	// Cookie exclusions — logged in, commented, password-protected, has a cart.
	if ( mbrpe_cache_cookie_excluded( $config ) ) {
		return;
	}

	// URI exclusions.
	if ( mbrpe_cache_uri_excluded( $path, $config ) ) {
		return;
	}

	// User-agent exclusions.
	if ( mbrpe_cache_agent_excluded( $config ) ) {
		return;
	}

	// Query string. Marketing parameters are stripped so ?utm_source=... serves
	// the same cache entry as the bare URL; anything left over either keys its
	// own entry or disqualifies the request, depending on configuration.
	$qs_key = mbrpe_cache_query_key( $config );
	if ( false === $qs_key ) {
		return;
	}

	// ---------------------------------------------------------------------
	// 4. Locate the entry.
	// ---------------------------------------------------------------------

	$file = mbrpe_cache_file_path( $cache_dir, $path, $qs_key, $config );
	if ( '' === $file ) {
		return;
	}

	// Signal to the plugin (which loads much later) that this request was a miss
	// and is therefore a candidate for storing. Without this the module would
	// have to redo all of the above work.
	if ( ! defined( 'MBRPE_CACHE_CHECKED' ) ) {
		define( 'MBRPE_CACHE_CHECKED', true );
	}

	$mtime = @filemtime( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- a racing purge is expected, not exceptional.
	if ( ! $mtime ) {
		return;
	}

	// TTL. A TTL of 0 means "never expire on read" — entries then live until
	// something purges them, which is the right default for a content site
	// where every meaningful change fires a purge anyway.
	$ttl = isset( $config['ttl'] ) ? (int) $config['ttl'] : 0;
	if ( $ttl > 0 && ( $mtime + $ttl ) < time() ) {
		return;
	}

	// ---------------------------------------------------------------------
	// 5. Serve.
	// ---------------------------------------------------------------------

	mbrpe_cache_serve( $file, $mtime, $config );
}

/**
 * Normalise REQUEST_URI to a safe, cache-key-ready path.
 *
 * Returns false for anything that could escape the cache directory or that we
 * cannot represent as a filesystem path. Being conservative here is free: the
 * worst case is a cache miss and a normal WordPress render.
 *
 * @param string $request_uri Raw REQUEST_URI.
 * @return string|false Path with a leading slash, or false if unusable.
 */
function mbrpe_cache_request_path( $request_uri ) {
	// Deliberately NOT parse_url(). It reads a leading "//" as a
	// protocol-relative host, so "//evil.com/x/" parses to the path "/x/" — two
	// completely different URLs collapsing onto one cache key, which is a
	// poisoning vector. Cutting at the first ? or # is unambiguous.
	$cut  = strcspn( $request_uri, '?#' );
	$path = substr( (string) $request_uri, 0, $cut );
	if ( '' === $path ) {
		$path = '/';
	}

	// Decode once so %2e%2e style traversal is caught by the checks below.
	$decoded = rawurldecode( $path );

	// Reject traversal, null bytes, backslashes and control characters.
	if ( false !== strpos( $decoded, '..' ) ) {
		return false;
	}
	if ( false !== strpos( $decoded, "\0" ) || false !== strpos( $decoded, '\\' ) ) {
		return false;
	}
	if ( preg_match( '/[\x00-\x1F\x7F]/', $decoded ) ) {
		return false;
	}

	// Collapse duplicate slashes and normalise to a leading slash.
	$decoded = '/' . ltrim( preg_replace( '#/+#', '/', $decoded ), '/' );

	// Restrict to a conservative filename charset. Anything else (spaces,
	// unicode slugs, colons) is not rejected outright — it is percent-free by
	// now, so we hash the whole path instead of mirroring it as directories.
	if ( ! preg_match( '#^[A-Za-z0-9/_.~-]*$#', $decoded ) ) {
		return '/__h/' . md5( $decoded ) . '/';
	}

	return $decoded;
}

/**
 * Check the request path against the site's canonical trailing-slash style.
 *
 * @param string $path   Normalised path.
 * @param array  $config Drop-in config.
 * @return bool True if this path is the canonical form (or the site has no rule).
 */
function mbrpe_cache_path_is_canonical( $path, $config ) {
	if ( '/' === $path ) {
		return true;
	}

	// null = no permalink structure / unknown; do not enforce.
	if ( ! isset( $config['trailing_slash'] ) || null === $config['trailing_slash'] ) {
		return true;
	}

	// Paths that look like a real file (sitemap.xml, robots.txt) are exempt.
	if ( false !== strpos( basename( $path ), '.' ) ) {
		return true;
	}

	$has_slash = ( '/' === substr( $path, -1 ) );

	return $config['trailing_slash'] ? $has_slash : ! $has_slash;
}

/**
 * Is any cache-disqualifying cookie present?
 *
 * Matched as prefixes, because WordPress suffixes most of its cookies with a
 * site hash (wordpress_logged_in_a1b2c3...).
 *
 * @param array $config Drop-in config.
 * @return bool
 */
function mbrpe_cache_cookie_excluded( $config ) {
	if ( empty( $_COOKIE ) || empty( $config['exclude_cookies'] ) ) {
		return false;
	}

	foreach ( array_keys( $_COOKIE ) as $name ) {
		foreach ( $config['exclude_cookies'] as $prefix ) {
			if ( '' !== $prefix && 0 === strpos( $name, $prefix ) ) {
				return true;
			}
		}
	}

	return false;
}

/**
 * Is the path excluded by an admin-configured URI rule?
 *
 * Rules are plain paths, optionally ending in (*) for a prefix match.
 *
 * @param string $path   Normalised path.
 * @param array  $config Drop-in config.
 * @return bool
 */
function mbrpe_cache_uri_excluded( $path, $config ) {
	if ( empty( $config['exclude_uris'] ) ) {
		return false;
	}

	foreach ( $config['exclude_uris'] as $rule ) {
		$rule = trim( (string) $rule );
		if ( '' === $rule ) {
			continue;
		}

		if ( '(*)' === substr( $rule, -3 ) ) {
			$prefix = substr( $rule, 0, -3 );
			if ( 0 === strpos( $path, $prefix ) ) {
				return true;
			}
			continue;
		}

		// Exact match, slash-insensitive.
		if ( rtrim( $path, '/' ) === rtrim( $rule, '/' ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Is the user agent excluded (or absent)?
 *
 * @param array $config Drop-in config.
 * @return bool
 */
function mbrpe_cache_agent_excluded( $config ) {
	if ( empty( $config['exclude_agents'] ) ) {
		return false;
	}

	$agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
	if ( '' === $agent ) {
		return false;
	}

	foreach ( $config['exclude_agents'] as $needle ) {
		$needle = trim( (string) $needle );
		if ( '' !== $needle && false !== stripos( $agent, $needle ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Build the query-string component of the cache key.
 *
 * Marketing and analytics parameters are dropped entirely, so a link shared with
 * ?utm_source=newsletter hits the same entry as the clean URL. That single
 * behaviour is usually the difference between a 90% hit rate and a 20% one.
 *
 * @param array $config Drop-in config.
 * @return string|false Key fragment ('' for no query string), or false to bypass.
 */
function mbrpe_cache_query_key( $config ) {
	$raw = isset( $_SERVER['QUERY_STRING'] ) ? (string) $_SERVER['QUERY_STRING'] : '';
	if ( '' === $raw ) {
		return '';
	}

	parse_str( $raw, $params );
	if ( empty( $params ) ) {
		return '';
	}

	// Strip ignored parameters.
	if ( ! empty( $config['ignored_qs'] ) ) {
		foreach ( array_keys( $params ) as $name ) {
			foreach ( $config['ignored_qs'] as $ignored ) {
				$ignored = trim( (string) $ignored );
				if ( '' === $ignored ) {
					continue;
				}
				// Support a trailing * for families like utm_*.
				if ( '*' === substr( $ignored, -1 ) ) {
					if ( 0 === strpos( $name, substr( $ignored, 0, -1 ) ) ) {
						unset( $params[ $name ] );
						break;
					}
				} elseif ( $name === $ignored ) {
					unset( $params[ $name ] );
					break;
				}
			}
		}
	}

	if ( empty( $params ) ) {
		return '';
	}

	// Something meaningful is left. Either it is on the allow-list and earns its
	// own cache entry, or the request bypasses the cache.
	if ( empty( $config['cache_query_strings'] ) ) {
		return false;
	}

	if ( ! empty( $config['allowed_qs'] ) ) {
		foreach ( array_keys( $params ) as $name ) {
			if ( ! in_array( $name, $config['allowed_qs'], true ) ) {
				return false;
			}
		}
	}

	// Sort so ?a=1&b=2 and ?b=2&a=1 share an entry.
	ksort( $params );

	return substr( md5( http_build_query( $params ) ), 0, 12 );
}

/**
 * Resolve the on-disk path for this request.
 *
 * Layout:
 *   {cache_dir}/{scheme}/{host}/{path}/{variant}.html
 *
 * Scheme and host are directories rather than filename parts so that a multisite
 * or multi-domain install can be purged per-domain with a single rmdir, and so
 * the .htaccess rules can address them with %{HTTP_HOST}.
 *
 * @param string $cache_dir Cache root.
 * @param string $path      Normalised request path.
 * @param string $qs_key    Query-string key fragment.
 * @param array  $config    Drop-in config.
 * @return string Absolute file path, or '' if it cannot be built.
 */
function mbrpe_cache_file_path( $cache_dir, $path, $qs_key, $config ) {
	$host = mbrpe_cache_resolve_host( $config );
	if ( '' === $host ) {
		return '';
	}

	$scheme = mbrpe_cache_resolve_scheme( $config );

	$variant = 'index';
	if ( ! empty( $config['vary_mobile'] ) && mbrpe_cache_is_mobile( $config ) ) {
		$variant .= '-mobile';
	}
	if ( '' !== $qs_key ) {
		$variant .= '-' . $qs_key;
	}

	return rtrim( $cache_dir, '/' ) . '/' . $scheme . '/' . $host . '/' . trim( $path, '/' ) . '/' . $variant . '.html';
}

/**
 * Resolve the scheme directory for this request.
 *
 * Mirror of MBRPE_Page_Cache::resolve_scheme(). Keep the two identical.
 *
 * The scheme is normally pinned by the plugin from home_url() and carried in
 * the config, which is what makes reader and writer agree by construction. The
 * previous version derived it here from the request — including forwarded
 * proxy headers — while the writer used is_ssl(), so on a site not behind a
 * proxy anyone could send X-Forwarded-Proto: https and steer this side to a
 * branch the writer never populated.
 *
 * Forwarded headers are therefore only consulted when the config says the site
 * is behind a proxy, and only when no pinned scheme is available at all.
 *
 * @since 2.0.1
 * @param array $config Drop-in config.
 * @return string 'https' or 'http'.
 */
function mbrpe_cache_resolve_scheme( $config ) {
	if ( isset( $config['scheme'] ) && in_array( $config['scheme'], array( 'http', 'https' ), true ) ) {
		return $config['scheme'];
	}

	if ( ! empty( $_SERVER['HTTPS'] ) && 'off' !== strtolower( (string) $_SERVER['HTTPS'] ) ) {
		return 'https';
	}
	if ( isset( $_SERVER['SERVER_PORT'] ) && '443' === (string) $_SERVER['SERVER_PORT'] ) {
		return 'https';
	}

	if ( ! empty( $config['trust_proxy'] ) ) {
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === strtolower( (string) $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) {
			return 'https';
		}
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_SSL'] ) && 'on' === strtolower( (string) $_SERVER['HTTP_X_FORWARDED_SSL'] ) ) {
			return 'https';
		}
	}

	return 'http';
}

/**
 * Resolve the host directory for this request.
 *
 * Mirror of MBRPE_Page_Cache::resolve_host(). Keep the two identical.
 *
 * Host is attacker-supplied on every request. The previous version stripped
 * disallowed characters from it and used whatever remained, so any value at
 * all produced a directory — an unauthenticated client could grow the cache
 * sideways indefinitely by varying the header alone.
 *
 * Now it must be a syntactically valid host, and on a single site it must also
 * be one the site actually answers to. On multisite the config is shared by
 * every blog on the network, so no single allow-list can be correct there and
 * the syntax check stands alone.
 *
 * @since 2.0.1
 * @param array $config Drop-in config.
 * @return string Host, or '' to bypass the cache.
 */
function mbrpe_cache_resolve_host( $config ) {
	$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( (string) $_SERVER['HTTP_HOST'] ) : '';
	if ( '' === $host || strlen( $host ) > 253 ) {
		return '';
	}

	// A hostname, optionally with a port. Rejects rather than strips, so no
	// value can be edited into something that looks like a path component.
	if ( ! preg_match( '/^[a-z0-9]([a-z0-9.-]*[a-z0-9])?(:[0-9]{1,5})?$/', $host ) ) {
		return '';
	}
	if ( false !== strpos( $host, '..' ) ) {
		return '';
	}

	if ( ! empty( $config['hosts'] ) && is_array( $config['hosts'] ) ) {
		if ( ! in_array( $host, $config['hosts'], true ) ) {
			return '';
		}
	}

	return $host;
}

/**
 * Mobile detection, used only when the site opts into a separate mobile cache.
 *
 * Deliberately crude. A responsive theme needs no mobile variant at all, and the
 * sites that do need one are served fine by a broad regex — this is not a device
 * database and should not become one.
 *
 * @param array $config Drop-in config.
 * @return bool
 */
function mbrpe_cache_is_mobile( $config ) {
	$agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
	if ( '' === $agent ) {
		return false;
	}

	$pattern = ! empty( $config['mobile_agents'] )
		? (string) $config['mobile_agents']
		: 'Mobile|Android|Silk/|Kindle|BlackBerry|Opera Mini|Opera Mobi';

	return (bool) preg_match( '#' . str_replace( '#', '\#', $pattern ) . '#i', $agent );
}

/**
 * Emit a cached entry and stop.
 *
 * Handles conditional requests, gzip negotiation and HEAD. Anything unexpected
 * falls through to a normal render rather than risking a corrupt response.
 *
 * @param string $file   Absolute path to the cached HTML.
 * @param int    $mtime  File mtime.
 * @param array  $config Drop-in config.
 * @return void
 */
function mbrpe_cache_serve( $file, $mtime, $config ) {
	$debug = ! empty( $config['debug_header'] );

	// Conditional GET. Cheapest possible hit: 304 and no body at all.
	$ims = isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ? (string) $_SERVER['HTTP_IF_MODIFIED_SINCE'] : '';
	if ( '' !== $ims ) {
		$since = strtotime( $ims );
		if ( $since && $since >= $mtime ) {
			header( 'HTTP/1.1 304 Not Modified' );
			if ( $debug ) {
				header( 'X-MBR-Cache: HIT-304' );
			}
			exit;
		}
	}

	header( 'Content-Type: text/html; charset=UTF-8' );
	header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $mtime ) . ' GMT' );
	header( 'Vary: Accept-Encoding' );
	if ( $debug ) {
		header( 'X-MBR-Cache: HIT' );
		header( 'X-MBR-Cache-Age: ' . max( 0, time() - $mtime ) );
	}

	// Serve the pre-compressed sibling when the client accepts it. This saves
	// the CPU cost of compressing the same bytes on every single request, which
	// on a busy shared host is the whole ballgame.
	$accepts_gzip = isset( $_SERVER['HTTP_ACCEPT_ENCODING'] )
		&& false !== stripos( (string) $_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip' );

	if ( $accepts_gzip && ! empty( $config['gzip'] ) && is_readable( $file . '.gz' ) ) {
		header( 'Content-Encoding: gzip' );
		$serve = $file . '.gz';
	} else {
		$serve = $file;
	}

	$size = @filesize( $serve ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	if ( $size ) {
		header( 'Content-Length: ' . $size );
	}

	// HEAD gets the headers and nothing else.
	if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'HEAD' === strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) ) {
		exit;
	}

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- WP_Filesystem does not exist yet.
	readfile( $serve );
	exit;
}

mbrpe_advanced_cache_boot();
