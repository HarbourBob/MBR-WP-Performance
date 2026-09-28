<?php
/**
 * Full Page Cache — engine.
 *
 * Stores the finished HTML of a rendered page as a static file, so subsequent
 * visitors are served that file instead of booting WordPress again.
 *
 * Two serving paths exist, fastest first:
 *
 *   1. .htaccess (MBRPE_Page_Cache_Rules) — Apache/LiteSpeed serve the file with
 *      no PHP at all. Microseconds.
 *   2. advanced-cache.php (MBRPE_Page_Cache_Dropin) — PHP starts but WordPress
 *      does not. Single-digit milliseconds. Works on every host, including Nginx.
 *
 * This class is the third path and the only *writer*. It runs inside a fully
 * booted WordPress, which is what lets it answer questions the other two cannot:
 * is this a 404, is the post password-protected, did a plugin just set a cookie.
 *
 * WHY THE BUFFER OPENS SO EARLY.
 *
 * MBR Performance already opens output buffers in five other modules — HTML
 * minify at template_redirect 99, Used CSS at 1, Mode B at 1, Google Fonts strip
 * at 1, and so on. PHP flushes nested buffers innermost-first, so whichever
 * buffer opens *first* is the one that receives everybody else's finished
 * output. To cache the same HTML the visitor actually gets — minified, with
 * unused CSS already stripped — this buffer must be the outermost one. Hence
 * priority PHP_INT_MIN on template_redirect, and hence the cacheability decision
 * being deferred to buffer-close time rather than made at open time.
 *
 * @package MBRPE
 * @since   2.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MBRPE_Page_Cache {

	/**
	 * Cache-key algorithm version.
	 *
	 * MUST match MBRPE_CACHE_KEY_ALGO in includes/dropins/advanced-cache.php.
	 * Bump both together whenever file_path() changes shape, or the drop-in will
	 * read from one filename while this class writes to another and the hit rate
	 * silently drops to zero.
	 */
	const KEY_ALGO = 1;

	/**
	 * Directory name under wp-content/cache/.
	 */
	const CACHE_SLUG = 'mbr-performance';

	/**
	 * Default TTL in seconds. 0 = entries live until purged.
	 */
	const DEFAULT_TTL = 0;

	/**
	 * Version of the cache-root .htaccess guard. Bump when its rules change;
	 * protect_dir() rewrites any guard that lacks the current marker.
	 *
	 * @since 2.0.2
	 */
	const GUARD_VERSION = 2;

	/**
	 * Single instance.
	 *
	 * @var MBRPE_Page_Cache
	 */
	private static $instance = null;

	/**
	 * Reason another module has asked for this response not to be stored.
	 *
	 * @since 2.1.2
	 * @var string
	 */
	private static $deferred = '';

	/**
	 * Options for the 'cache' section.
	 *
	 * @var array
	 */
	private $options = array();

	/**
	 * Reason this request will not be cached, for the debug header.
	 *
	 * @var string
	 */
	private $skip_reason = '';

	/**
	 * Whether our buffer is open.
	 *
	 * @var bool
	 */
	private $buffering = false;

	/**
	 * Get instance.
	 *
	 * @return MBRPE_Page_Cache
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
		$this->options = mbrpe()->get_options( 'cache' );

		// Admin-side wiring runs even when caching is switched off, so the tab
		// can still report status and offer a purge.
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ), 100 );

		if ( ! self::is_enabled() ) {
			return;
		}

		// Outermost buffer. See the class docblock for why PHP_INT_MIN.
		add_action( 'template_redirect', array( $this, 'start_buffer' ), PHP_INT_MIN );

		// Fallback serving for installs where the drop-in could not be written
		// (no filesystem write access to wp-content, WP_CACHE locked by the
		// host). Slower than the drop-in — WordPress has already loaded — but it
		// still skips the theme, the main query and every front-end plugin hook.
		if ( ! MBRPE_Page_Cache_Dropin::is_active() ) {
			add_action( 'template_redirect', array( $this, 'maybe_serve_fallback' ), PHP_INT_MIN + 1 );
		}

		// Hourly sweep for TTL-expired entries. The drop-in checks mtime on read,
		// but .htaccess-served hits never reach PHP, so expiry has to be enforced
		// by deletion as well as by inspection.
		add_action( 'mbrpe_cache_expire', array( __CLASS__, 'purge_expired' ) );
	}

	// =====================================================================
	// Status
	// =====================================================================

	/**
	 * Is page caching switched on AND safe to run?
	 *
	 * A competing page cache is a hard block, not a warning. Two page caches on
	 * one site produce stale content that neither plugin can explain, and the
	 * support burden lands on whichever one the user installed most recently.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		$opts = mbrpe()->get_options( 'cache' );
		if ( empty( $opts['enabled'] ) ) {
			return false;
		}

		if ( self::competing_cache() ) {
			return false;
		}

		return true;
	}

	/**
	 * Detect another active full-page cache.
	 *
	 * Deliberately narrower than MBRPE_Conflict_Detector: that class lists
	 * *overlapping* optimisations for the user to reconcile at their leisure.
	 * This one lists plugins that would fight us for the same HTML, and blocks.
	 *
	 * @return string|false Label of the competing plugin, or false.
	 */
	public static function competing_cache() {
		$suspects = array(
			'WP Rocket'        => array( 'constant' => 'WP_ROCKET_VERSION' ),
			'LiteSpeed Cache'  => array( 'constant' => 'LSCWP_V' ),
			'W3 Total Cache'   => array( 'constant' => 'W3TC' ),
			'WP Super Cache'   => array( 'function' => 'wp_cache_is_enabled' ),
			'Cache Enabler'    => array( 'class' => 'Cache_Enabler' ),
			'WP Fastest Cache' => array( 'class' => 'WpFastestCache' ),
			'FlyingPress'      => array( 'constant' => 'FLYING_PRESS_VERSION' ),
			'Surge'            => array( 'function' => 'surge_is_enabled' ),
			'Breeze'           => array( 'constant' => 'BREEZE_VERSION' ),
			'Swift Performance' => array( 'class' => 'Swift_Performance' ),
		);

		foreach ( $suspects as $label => $signal ) {
			if ( ! empty( $signal['constant'] ) && defined( $signal['constant'] ) ) {
				return $label;
			}
			if ( ! empty( $signal['class'] ) && class_exists( $signal['class'] ) ) {
				return $label;
			}
			if ( ! empty( $signal['function'] ) && function_exists( $signal['function'] ) ) {
				return $label;
			}
		}

		// SiteGround Optimizer only conflicts when its own file cache is on.
		if ( defined( 'SiteGround_Optimizer\VERSION' ) && get_option( 'siteground_optimizer_file_caching', false ) ) {
			return 'SiteGround Optimizer (file caching)';
		}

		return false;
	}

	/**
	 * Absolute path to the cache root.
	 *
	 * @return string
	 */
	public static function cache_dir() {
		/**
		 * Filter the page cache directory.
		 *
		 * @since 2.0.0
		 * @param string $dir Absolute path, no trailing slash.
		 */
		return apply_filters( 'mbrpe_cache_dir', WP_CONTENT_DIR . '/cache/' . self::CACHE_SLUG );
	}

	// =====================================================================
	// Cache key — MUST mirror advanced-cache.php
	// =====================================================================

	/**
	 * Resolve the on-disk path for the current request.
	 *
	 * Deliberately a near-copy of mbrpe_cache_file_path() and its helpers in the
	 * drop-in. They cannot share code — the drop-in runs before any of this
	 * exists — so they are kept side by side and versioned together via
	 * KEY_ALGO. If you edit one, edit the other.
	 *
	 * @return string Absolute file path, or '' if this request has no valid key.
	 */
	public static function current_file_path() {
		$path = self::request_path();
		if ( false === $path ) {
			return '';
		}

		$config = MBRPE_Page_Cache_Dropin::build_config();

		if ( ! self::path_is_canonical( $path, $config ) ) {
			return '';
		}

		$qs_key = self::query_key( $config );
		if ( false === $qs_key ) {
			return '';
		}

		$host = self::resolve_host( $config );
		if ( '' === $host ) {
			return '';
		}

		$scheme = self::resolve_scheme( $config );

		$variant = 'index';
		if ( ! empty( $config['vary_mobile'] ) && self::is_mobile( $config ) ) {
			$variant .= '-mobile';
		}
		if ( '' !== $qs_key ) {
			$variant .= '-' . $qs_key;
		}

		return self::cache_dir() . '/' . $scheme . '/' . $host . '/' . trim( $path, '/' ) . '/' . $variant . '.html';
	}

	/**
	 * Resolve the scheme directory. Mirror of mbrpe_cache_resolve_scheme().
	 *
	 * Previously this side used is_ssl() while the drop-in derived the scheme
	 * from the request, forwarded proxy headers included. The two could
	 * disagree, which meant the writer populated one branch and the reader
	 * looked in the other. Both now read the scheme the plugin pinned into the
	 * config from home_url().
	 *
	 * @since 2.0.1
	 * @param array $config Compiled config.
	 * @return string 'https' or 'http'.
	 */
	public static function resolve_scheme( $config ) {
		if ( isset( $config['scheme'] ) && in_array( $config['scheme'], array( 'http', 'https' ), true ) ) {
			return $config['scheme'];
		}

		if ( ! empty( $_SERVER['HTTPS'] ) && 'off' !== strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTPS'] ) ) ) ) {
			return 'https';
		}
		if ( isset( $_SERVER['SERVER_PORT'] ) && '443' === (string) sanitize_text_field( wp_unslash( $_SERVER['SERVER_PORT'] ) ) ) {
			return 'https';
		}

		if ( ! empty( $config['trust_proxy'] ) ) {
			if ( ! empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) ) ) {
				return 'https';
			}
			if ( ! empty( $_SERVER['HTTP_X_FORWARDED_SSL'] ) && 'on' === strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_SSL'] ) ) ) ) {
				return 'https';
			}
		}

		return 'http';
	}

	/**
	 * Resolve the host directory. Mirror of mbrpe_cache_resolve_host().
	 *
	 * @since 2.0.1
	 * @param array $config Compiled config.
	 * @return string Host, or '' if this request should not be cached.
	 */
	public static function resolve_host( $config ) {
		$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) ) : '';
		if ( '' === $host || strlen( $host ) > 253 ) {
			return '';
		}

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
	 * Mobile detection. Mirror of mbrpe_cache_is_mobile().
	 *
	 * Uses the same pattern the drop-in uses rather than wp_is_mobile(), so a
	 * filtered mobile_agents value cannot make the two sides disagree about
	 * which variant a request belongs to.
	 *
	 * @since 2.0.1
	 * @param array $config Compiled config.
	 * @return bool
	 */
	public static function is_mobile( $config ) {
		$agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		if ( '' === $agent ) {
			return false;
		}

		$pattern = ! empty( $config['mobile_agents'] )
			? (string) $config['mobile_agents']
			: 'Mobile|Android|Silk/|Kindle|BlackBerry|Opera Mini|Opera Mobi';

		return (bool) preg_match( '#' . str_replace( '#', '\#', $pattern ) . '#i', $agent );
	}

	/**
	 * Normalised request path. Mirror of mbrpe_cache_request_path().
	 *
	 * @return string|false
	 */
	public static function request_path() {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';

		// Deliberately NOT wp_parse_url(). It reads a leading "//" as a
		// protocol-relative host, so "//evil.com/x/" parses to the path "/x/" —
		// two different URLs collapsing onto one cache key, which is a poisoning
		// vector. Cutting at the first ? or # is unambiguous.
		$cut  = strcspn( $request_uri, '?#' );
		$path = substr( $request_uri, 0, $cut );
		if ( '' === $path ) {
			$path = '/';
		}

		$decoded = rawurldecode( $path );

		if ( false !== strpos( $decoded, '..' ) ) {
			return false;
		}
		if ( false !== strpos( $decoded, "\0" ) || false !== strpos( $decoded, '\\' ) ) {
			return false;
		}
		if ( preg_match( '/[\x00-\x1F\x7F]/', $decoded ) ) {
			return false;
		}

		$decoded = '/' . ltrim( preg_replace( '#/+#', '/', $decoded ), '/' );

		if ( ! preg_match( '#^[A-Za-z0-9/_.~-]*$#', $decoded ) ) {
			return '/__h/' . md5( $decoded ) . '/';
		}

		return $decoded;
	}

	/**
	 * Mirror of mbrpe_cache_path_is_canonical().
	 *
	 * @param string $path   Normalised path.
	 * @param array  $config Drop-in config.
	 * @return bool
	 */
	public static function path_is_canonical( $path, $config ) {
		if ( '/' === $path ) {
			return true;
		}
		if ( ! isset( $config['trailing_slash'] ) || null === $config['trailing_slash'] ) {
			return true;
		}
		if ( false !== strpos( basename( $path ), '.' ) ) {
			return true;
		}

		$has_slash = ( '/' === substr( $path, -1 ) );

		return $config['trailing_slash'] ? $has_slash : ! $has_slash;
	}

	/**
	 * Mirror of mbrpe_cache_query_key().
	 *
	 * @param array $config Drop-in config.
	 * @return string|false
	 */
	public static function query_key( $config ) {
		$raw = isset( $_SERVER['QUERY_STRING'] ) ? sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ) : '';
		if ( '' === $raw ) {
			return '';
		}

		parse_str( $raw, $params );
		if ( empty( $params ) ) {
			return '';
		}

		if ( ! empty( $config['ignored_qs'] ) ) {
			foreach ( array_keys( $params ) as $name ) {
				foreach ( $config['ignored_qs'] as $ignored ) {
					$ignored = trim( (string) $ignored );
					if ( '' === $ignored ) {
						continue;
					}
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

		ksort( $params );

		return substr( md5( http_build_query( $params ) ), 0, 12 );
	}

	// =====================================================================
	// Serving (fallback path only)
	// =====================================================================

	/**
	 * Serve from cache inside WordPress, for installs without a working drop-in.
	 *
	 * @return void
	 */
	public function maybe_serve_fallback() {
		if ( ! $this->is_cacheable_request() ) {
			return;
		}

		$file = self::current_file_path();
		if ( '' === $file || ! is_readable( $file ) ) {
			return;
		}

		$mtime = filemtime( $file );
		$ttl   = $this->ttl();
		if ( $ttl > 0 && ( $mtime + $ttl ) < time() ) {
			return;
		}

		// Discard anything buffered so far (including our own buffer) so the
		// cached bytes are the only thing sent.
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}
		$this->buffering = false;

		header( 'Content-Type: text/html; charset=UTF-8' );
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $mtime ) . ' GMT' );
		header( 'Vary: Accept-Encoding' );
		if ( $this->debug_header() ) {
			header( 'X-MBR-Cache: HIT-PHP' );
			header( 'X-MBR-Cache-Age: ' . max( 0, time() - $mtime ) );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- streaming a known-local file; WP_Filesystem would buffer it entirely.
		readfile( $file );
		exit;
	}

	// =====================================================================
	// Buffering & storing
	// =====================================================================

	/**
	 * Open the outermost output buffer.
	 *
	 * Note that no cacheability decision is made here beyond the very cheapest
	 * ones. The interesting questions — final HTTP status, whether a plugin set
	 * a cookie, whether the theme emitted a nocache header — can only be
	 * answered once the page has actually rendered. So we buffer optimistically
	 * and decide in the callback.
	 *
	 * @return void
	 */
	public function start_buffer() {
		if ( ! $this->is_cacheable_request() ) {
			return;
		}

		if ( $this->debug_header() ) {
			header( 'X-MBR-Cache: MISS' );
		}

		$this->buffering = true;
		ob_start( array( $this, 'buffer_callback' ) );
	}

	/**
	 * Buffer callback — the last thing to touch the HTML before it goes out.
	 *
	 * ALWAYS returns $buffer unchanged. Caching is a side effect; if anything at
	 * all goes wrong here the visitor must still get their page.
	 *
	 * @param string $buffer Finished page HTML.
	 * @return string
	 */
	public function buffer_callback( $buffer ) {
		if ( ! $this->is_storable( $buffer ) ) {
			return $buffer;
		}

		$file = self::current_file_path();
		if ( '' === $file ) {
			return $buffer;
		}

		/**
		 * Filter the HTML immediately before it is written to the cache.
		 *
		 * The returned value is stored but NOT sent to the current visitor, so
		 * this is the hook for cache-only footers such as a generation
		 * timestamp. Return an empty string to abandon the write.
		 *
		 * @since 2.0.0
		 * @param string $html Page HTML.
		 * @param string $file Target path.
		 */
		$html = (string) apply_filters( 'mbrpe_cache_pre_store', $buffer, $file );
		if ( '' === trim( $html ) ) {
			return $buffer;
		}

		if ( ! empty( $this->options['signature'] ) ) {
			$html .= "\n<!-- Cached by MBR Performance on " . esc_html( gmdate( 'Y-m-d H:i:s' ) ) . " UTC -->";
		}

		self::write( $file, $html, ! empty( $this->options['gzip'] ) );

		return $buffer;
	}

	/**
	 * Ask the page cache not to store the current response.
	 *
	 * For modules whose first render of a page is deliberately not the final
	 * one. Used CSS (Mode A) serves a page untouched while it generates, and
	 * Mode B serves pages untouched while it learns; storing either would
	 * freeze the unoptimised page, and because a cache hit never reaches PHP,
	 * the optimisation would never get a chance to run again. The page is
	 * still served normally — it simply is not written — so the next request
	 * renders the optimised version and that is what gets cached.
	 *
	 * Safe to call whether or not page caching is enabled.
	 *
	 * @since 2.1.2
	 * @param string $reason Short key reported in X-MBR-Cache-Reason.
	 * @return void
	 */
	public static function defer( $reason ) {
		self::$deferred = sanitize_key( $reason );
		if ( '' === self::$deferred ) {
			self::$deferred = 'deferred';
		}

		if ( null !== self::$instance && self::$instance->buffering ) {
			self::$instance->skip( self::$deferred );
		}
	}

	/**
	 * Cheap, request-level cacheability checks.
	 *
	 * Everything here is knowable before the page renders. Anything that needs
	 * the rendered output belongs in is_storable() instead.
	 *
	 * @return bool
	 */
	private function is_cacheable_request() {
		// Never cache a logged-in session. Caching per-role is a v2.1 feature and
		// a genuinely hard one — every "logged-in caching" bug report in every
		// caching plugin traces back to someone shipping it early.
		if ( is_user_logged_in() ) {
			return $this->skip( 'logged-in' );
		}

		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET';
		if ( 'GET' !== $method && 'HEAD' !== $method ) {
			return $this->skip( 'method:' . $method );
		}

		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return $this->skip( 'admin-context' );
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return $this->skip( 'rest' );
		}

		if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
			return $this->skip( 'xmlrpc' );
		}

		// The de facto standard opt-out that themes and plugins set.
		if ( defined( 'DONOTCACHEPAGE' ) && DONOTCACHEPAGE ) {
			return $this->skip( 'DONOTCACHEPAGE' );
		}

		// Another MBR module is serving a provisional render (see defer()).
		if ( '' !== self::$deferred ) {
			return $this->skip( self::$deferred );
		}

		if ( is_preview() || is_customize_preview() ) {
			return $this->skip( 'preview' );
		}

		if ( is_search() ) {
			return $this->skip( 'search' );
		}

		if ( is_trackback() ) {
			return $this->skip( 'trackback' );
		}

		if ( is_feed() && empty( $this->options['cache_feeds'] ) ) {
			return $this->skip( 'feed' );
		}

		if ( post_password_required() ) {
			return $this->skip( 'password-protected' );
		}

		// Page builder editors and previews — same guard the rest of the plugin
		// applies, restated here because this module hooks earlier than most.
		foreach ( array( 'elementor-preview', 'fl_builder', 'bricks', 'ct_builder', 'vc_editable' ) as $flag ) {
			if ( isset( $_GET[ $flag ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return $this->skip( 'builder:' . $flag );
			}
		}

		$config = MBRPE_Page_Cache_Dropin::build_config();

		$path = self::request_path();
		if ( false === $path ) {
			return $this->skip( 'unsafe-path' );
		}

		foreach ( (array) $config['exclude_uris'] as $rule ) {
			$rule = trim( (string) $rule );
			if ( '' === $rule ) {
				continue;
			}
			if ( '(*)' === substr( $rule, -3 ) ) {
				if ( 0 === strpos( $path, substr( $rule, 0, -3 ) ) ) {
					return $this->skip( 'excluded-uri' );
				}
			} elseif ( rtrim( $path, '/' ) === rtrim( $rule, '/' ) ) {
				return $this->skip( 'excluded-uri' );
			}
		}

		if ( ! empty( $_COOKIE ) ) {
			foreach ( array_keys( $_COOKIE ) as $name ) {
				foreach ( (array) $config['exclude_cookies'] as $prefix ) {
					if ( '' !== $prefix && 0 === strpos( $name, $prefix ) ) {
						return $this->skip( 'excluded-cookie' );
					}
				}
			}
		}

		$agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		if ( '' !== $agent ) {
			foreach ( (array) $config['exclude_agents'] as $needle ) {
				$needle = trim( (string) $needle );
				if ( '' !== $needle && false !== stripos( $agent, $needle ) ) {
					return $this->skip( 'excluded-agent' );
				}
			}
		}

		/**
		 * Final say on whether this request may be cached.
		 *
		 * @since 2.0.0
		 * @param bool $cacheable Current decision.
		 */
		return (bool) apply_filters( 'mbrpe_cache_is_cacheable', true );
	}

	/**
	 * Output-level checks, run once the page has rendered.
	 *
	 * @param string $buffer Rendered HTML.
	 * @return bool
	 */
	private function is_storable( $buffer ) {
		if ( ! $this->buffering ) {
			return false;
		}

		// Re-run the request checks: is_user_logged_in() can flip mid-request
		// (a login form posting to itself), and DONOTCACHEPAGE is frequently set
		// late, from inside a shortcode or a template part.
		if ( ! $this->is_cacheable_request() ) {
			return false;
		}

		if ( function_exists( 'http_response_code' ) ) {
			$code = http_response_code();
			if ( $code && 200 !== (int) $code ) {
				// 404s are cacheable on request — they are cheap to serve and a
				// crawler hammering dead URLs is a real load source — but never
				// by default, because a misconfigured site would freeze its own
				// broken state in place.
				if ( 404 !== (int) $code || empty( $this->options['cache_404'] ) ) {
					return false;
				}
			}
		}

		if ( is_404() && empty( $this->options['cache_404'] ) ) {
			return false;
		}

		// A page that set a cookie is, by definition, personalised. Freezing it
		// would hand the next visitor somebody else's session state. This single
		// check prevents the majority of "the cache served me the wrong content"
		// reports.
		foreach ( headers_list() as $header ) {
			if ( 0 === stripos( $header, 'set-cookie:' ) ) {
				return false;
			}
			if ( 0 === stripos( $header, 'cache-control:' ) && false !== stripos( $header, 'no-store' ) ) {
				return false;
			}
		}

		// Sanity: the buffer should look like a complete HTML document. A
		// truncated response (fatal error mid-render, upstream timeout) must
		// never be frozen into the cache.
		$trimmed = trim( $buffer );
		if ( strlen( $trimmed ) < 255 ) {
			return false;
		}
		if ( ! is_feed() && false === stripos( $trimmed, '</html>' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Record why this request was skipped, and expose it as a debug header.
	 *
	 * @param string $reason Short machine-ish reason.
	 * @return false Always false, so callers can `return $this->skip(...)`.
	 */
	private function skip( $reason ) {
		$this->skip_reason = $reason;

		if ( $this->debug_header() && ! headers_sent() ) {
			header( 'X-MBR-Cache: BYPASS' );
			header( 'X-MBR-Cache-Reason: ' . $reason );
		}

		return false;
	}

	/**
	 * Should debug headers be emitted?
	 *
	 * @return bool
	 */
	private function debug_header() {
		return ! empty( $this->options['debug_header'] );
	}

	/**
	 * Effective TTL in seconds.
	 *
	 * @return int
	 */
	private function ttl() {
		return isset( $this->options['ttl'] ) ? max( 0, (int) $this->options['ttl'] ) : self::DEFAULT_TTL;
	}

	// =====================================================================
	// Filesystem
	// =====================================================================

	/**
	 * Write an entry, plus its gzip sibling.
	 *
	 * Writes go to a temporary file and are renamed into place. rename() is
	 * atomic within a filesystem, so a visitor can never read a half-written
	 * page — which matters much more than it sounds, because the window is
	 * exactly when traffic is highest.
	 *
	 * @param string $file Absolute target path.
	 * @param string $html Content.
	 * @param bool   $gzip Also write a .gz sibling.
	 * @return bool
	 */
	public static function write( $file, $html, $gzip = true ) {
		$dir = dirname( $file );

		if ( ! wp_mkdir_p( $dir ) ) {
			return false;
		}

		self::protect_dir();

		$tmp = $file . '.' . wp_generate_password( 8, false ) . '.tmp';

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- WP_Filesystem is not available on front-end requests without loading admin includes on every page view.
		if ( false === @file_put_contents( $tmp, $html, LOCK_EX ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			return false;
		}

		if ( ! @rename( $tmp, $file ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			@unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			return false;
		}

		@chmod( $file, 0644 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		if ( $gzip && function_exists( 'gzencode' ) ) {
			$gz     = gzencode( $html, 6 );
			$gz_tmp = $file . '.gz.' . wp_generate_password( 8, false ) . '.tmp';
			if ( false !== $gz ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				if ( false !== @file_put_contents( $gz_tmp, $gz, LOCK_EX ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					if ( ! @rename( $gz_tmp, $file . '.gz' ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
						@unlink( $gz_tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					} else {
						@chmod( $file . '.gz', 0644 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					}
				}
			}
		}

		/**
		 * Fires after a page has been written to the cache.
		 *
		 * @since 2.0.0
		 * @param string $file Absolute path.
		 */
		do_action( 'mbrpe_cache_stored', $file );

		return true;
	}

	/**
	 * Drop index.php and .htaccess guards into the cache root.
	 *
	 * The cache holds fully rendered pages. On a misconfigured host a directory
	 * listing of it is a site map plus, potentially, draft content that briefly
	 * went public. Cheap to prevent, so prevent it.
	 *
	 * The guard is versioned. purge_all() deliberately preserves this file, so
	 * without the version check a faulty guard from an earlier release would
	 * never be replaced.
	 *
	 * @since 2.0.0
	 * @since 2.0.2 Versioned; no longer sets X-Robots-Tag; blocks direct access
	 *              with a 403 instead; declares UTF-8 for plain .html hits.
	 * @return void
	 */
	private static function protect_dir() {
		$dir = self::cache_dir();

		if ( ! is_dir( $dir ) ) {
			return;
		}

		if ( ! file_exists( $dir . '/index.php' ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			@file_put_contents( $dir . '/index.php', "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		$marker   = '# MBRPE guard v' . self::GUARD_VERSION;
		$htaccess = $dir . '/.htaccess';

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_get_contents
		$current = file_exists( $htaccess ) ? (string) @file_get_contents( $htaccess ) : ''; // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( false !== strpos( $current, $marker ) ) {
			return;
		}

		// Match direct requests for the cache path. THE_REQUEST is the original
		// request line, which the root rewrite serving a cache hit does not
		// change, so only a visitor asking for the cache path itself matches.
		// Prefer the exact document-root-relative path; when that cannot be
		// resolved (WP-CLI, an upgrade run without DOCUMENT_ROOT) fall back to
		// the last two path segments, which is all this directory's rules can
		// ever see anyway.
		$rel = class_exists( 'MBRPE_Page_Cache_Rules' ) ? MBRPE_Page_Cache_Rules::relative_cache_path() : '';
		if ( '' !== $rel ) {
			$pattern = '\\s/' . preg_quote( $rel ) . '/';
		} else {
			$tail    = basename( dirname( $dir ) ) . '/' . basename( $dir );
			$pattern = '\\s/[^?\\s]*' . preg_quote( $tail ) . '/';
		}

		// NB: never set X-Robots-Tag (or any header meant only for direct
		// access) in this file. Directory-level headers apply to every response
		// served from this folder — including every rewritten cache hit — which
		// in 2.0.0/2.0.1 marked the whole site noindex under static serving.
		$rules = "# Generated by MBR Performance. Cached pages are served via rewrite, never browsed directly.\n"
			. $marker . "\n"
			. "<IfModule mod_autoindex.c>\n    Options -Indexes\n</IfModule>\n\n"
			. "# Plain .html hits are read off disk with no charset; the .gz copies\n"
			. "# already get theirs from the root rules.\n"
			. "<IfModule mod_mime.c>\n    AddCharset UTF-8 .html\n</IfModule>\n\n"
			. "# Refuse direct requests for cache files. Rewritten cache hits pass.\n"
			. "<IfModule mod_rewrite.c>\n"
			. "    RewriteEngine On\n"
			. '    RewriteCond %{THE_REQUEST} ' . $pattern . " [NC]\n"
			. "    RewriteRule ^ - [F]\n"
			. "</IfModule>\n";

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		@file_put_contents( $htaccess, $rules, LOCK_EX ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}

	/**
	 * Rewrite the cache-root guard files if they are missing or out of date.
	 *
	 * Public entry point for upgrade migrations. A no-op when the cache
	 * directory does not exist.
	 *
	 * @since 2.0.2
	 * @return void
	 */
	public static function refresh_guard() {
		self::protect_dir();
	}

	/**
	 * Recursively delete a directory's contents.
	 *
	 * @param string $dir       Absolute path, must sit inside the cache root.
	 * @param bool   $self      Also remove $dir itself.
	 * @return int Number of files deleted.
	 */
	public static function rmdir_recursive( $dir, $self = true ) {
		$root = self::cache_dir();

		// Refuse to operate outside the cache root under any circumstances.
		$real      = realpath( $dir );
		$real_root = realpath( $root );
		if ( ! $real || ! $real_root || 0 !== strpos( $real, $real_root ) ) {
			return 0;
		}

		$count = 0;

		$items = @scandir( $real ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( false === $items ) {
			return 0;
		}

		foreach ( $items as $item ) {
			if ( '.' === $item || '..' === $item ) {
				continue;
			}
			$target = $real . '/' . $item;
			if ( is_dir( $target ) && ! is_link( $target ) ) {
				$count += self::rmdir_recursive( $target, true );
			} else {
				if ( @unlink( $target ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					// Count HTML entries only, so "42 pages cleared" means pages.
					if ( '.html' === substr( $item, -5 ) ) {
						++$count;
					}
				}
			}
		}

		if ( $self && $real !== $real_root ) {
			@rmdir( $real ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		return $count;
	}

	/**
	 * Delete every entry whose mtime has passed the configured TTL.
	 *
	 * Runs hourly. Only relevant when a TTL is set; with TTL 0 the cache is
	 * purge-driven and this is a no-op.
	 *
	 * @return int Entries removed.
	 */
	public static function purge_expired() {
		$opts = mbrpe()->get_options( 'cache' );
		$ttl  = isset( $opts['ttl'] ) ? (int) $opts['ttl'] : 0;
		if ( $ttl <= 0 ) {
			return 0;
		}

		$root = self::cache_dir();
		if ( ! is_dir( $root ) ) {
			return 0;
		}

		$cutoff  = time() - $ttl;
		$removed = 0;

		try {
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ),
				RecursiveIteratorIterator::CHILD_FIRST
			);
		} catch ( Exception $e ) {
			return 0;
		}

		foreach ( $iterator as $item ) {
			if ( $item->isDir() ) {
				@rmdir( $item->getPathname() ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				continue;
			}
			$name = $item->getFilename();
			if ( '.html' !== substr( $name, -5 ) && '.gz' !== substr( $name, -3 ) ) {
				continue;
			}
			if ( $item->getMTime() < $cutoff ) {
				if ( @unlink( $item->getPathname() ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					if ( '.html' === substr( $name, -5 ) ) {
						++$removed;
					}
				}
			}
		}

		return $removed;
	}

	/**
	 * Cache size and entry count, cached in a transient so the admin tab does
	 * not walk the whole tree on every page load.
	 *
	 * @param bool $fresh Force a recount.
	 * @return array{files:int,bytes:int,generated:int}
	 */
	public static function stats( $fresh = false ) {
		if ( ! $fresh ) {
			$cached = get_transient( 'mbrpe_cache_stats' );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$stats = array(
			'files'     => 0,
			'bytes'     => 0,
			'generated' => time(),
		);

		$root = self::cache_dir();
		if ( ! is_dir( $root ) ) {
			set_transient( 'mbrpe_cache_stats', $stats, 5 * MINUTE_IN_SECONDS );
			return $stats;
		}

		try {
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS )
			);
			foreach ( $iterator as $item ) {
				if ( ! $item->isFile() ) {
					continue;
				}
				$stats['bytes'] += $item->getSize();
				if ( '.html' === substr( $item->getFilename(), -5 ) ) {
					++$stats['files'];
				}
			}
		} catch ( Exception $e ) {
			// A cache being purged underneath us is normal; report what we have.
			$stats['files'] = 0;
		}

		set_transient( 'mbrpe_cache_stats', $stats, 5 * MINUTE_IN_SECONDS );

		return $stats;
	}

	// =====================================================================
	// Admin bar
	// =====================================================================

	/**
	 * Add purge controls to the existing MBR Performance toolbar node.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Toolbar.
	 * @return void
	 */
	public function admin_bar_menu( $wp_admin_bar ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$wp_admin_bar->add_node(
			array(
				'parent' => 'mbr-performance',
				'id'     => 'mbr-performance-purge-all',
				'title'  => __( 'Purge Page Cache', 'mbr-performance' ),
				'href'   => wp_nonce_url( admin_url( 'admin-post.php?action=mbrpe_purge_cache&scope=all' ), 'mbrpe_purge_cache' ),
			)
		);

		// "Purge this page" only makes sense while looking at a front-end URL.
		if ( ! is_admin() && ! is_404() ) {
			$current = home_url( add_query_arg( array() ) );
			$wp_admin_bar->add_node(
				array(
					'parent' => 'mbr-performance',
					'id'     => 'mbr-performance-purge-url',
					'title'  => __( 'Purge This Page', 'mbr-performance' ),
					'href'   => wp_nonce_url(
						admin_url( 'admin-post.php?action=mbrpe_purge_cache&scope=url&url=' . rawurlencode( $current ) ),
						'mbrpe_purge_cache'
					),
				)
			);
		}
	}
}
