<?php
/**
 * Used CSS (Mode A): per-page critical-CSS with async full-sheet fallback.
 *
 * On the first hit to an uncached page the full page is served normally, then —
 * after the response is flushed to the visitor — the rendered HTML is analysed
 * against its local stylesheets, the "used" CSS is extracted, minified and
 * cached. On every subsequent hit that used CSS is inlined in the head and the
 * original stylesheets are loaded asynchronously as a safety net, so anything
 * the static pass missed still applies a beat later and nothing hard-breaks.
 *
 * @package MBR_Performance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MBRPE_Used_CSS {

	const DIRNAME = 'mbr-performance-usedcss';

	/**
	 * Transient holding the installed plugin/theme fingerprint.
	 */
	const EPOCH_TRANSIENT = 'mbrpe_used_css_epoch';

	/**
	 * @var MBRPE_Used_CSS
	 */
	private static $instance;

	/**
	 * Cached CSS-tab options.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Resolved cache file path for the current request (serve path).
	 *
	 * @var string
	 */
	private $cache_file = '';

	/**
	 * Whether we opened an output buffer to generate on this request.
	 *
	 * @var bool
	 */
	private $buffering = false;

	/**
	 * Absolute URL of the page being generated (for cache purging).
	 *
	 * @var string
	 */
	private $current_url = '';

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$opts          = get_option( 'mbrpe_options', array() );
		$this->options = isset( $opts['css'] ) && is_array( $opts['css'] ) ? $opts['css'] : array();

		if ( empty( $this->options['remove_unused_css'] ) ) {
			return;
		}

		// Invalidation (runs in admin too).
		add_action( 'save_post', array( $this, 'purge_for_post' ), 10, 1 );
		add_action( 'switch_theme', array( __CLASS__, 'purge_all' ) );
		add_action( 'upgrader_process_complete', array( __CLASS__, 'purge_all' ) );
		// Activating or deactivating any plugin can add or remove CSS that the
		// static pass has already decided about, so treat it as invalidating.
		add_action( 'activated_plugin', array( __CLASS__, 'purge_all' ) );
		add_action( 'deactivated_plugin', array( __CLASS__, 'purge_all' ) );

		// Front-end serve/generate.
		add_action( 'template_redirect', array( $this, 'on_template_redirect' ), 1 );
	}

	private function get_exclusions() {
		$raw = isset( $this->options['exclude_optimization'] ) ? (string) $this->options['exclude_optimization'] : '';
		$out = array();
		foreach ( preg_split( '/[\r\n,]+/', $raw ) as $line ) {
			$line = trim( $line );
			if ( '' !== $line ) {
				$out[] = $line;
			}
		}
		return $out;
	}

	/**
	 * Contexts where used CSS must not be served or generated.
	 */
	private function should_skip() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return true;
		}
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'GET' !== strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) ) {
			return true;
		}
		// Logged-in users get the admin bar and other per-user CSS — don't
		// cache or serve a shared used-CSS file to them.
		if ( is_user_logged_in() ) {
			return true;
		}
		if ( is_404() || is_search() || is_feed() || is_preview() || is_embed() ) {
			return true;
		}
		if ( function_exists( 'is_customize_preview' ) && is_customize_preview() ) {
			return true;
		}
		// Page builders / front-end editors.
		if ( ! empty( $_GET['elementor-preview'] ) || ( isset( $_GET['action'] ) && 'elementor' === $_GET['action'] ) ) {
			return true;
		}
		if ( isset( $_GET['fl_builder'] ) || ( ! empty( $_GET['bricks'] ) && 'run' === $_GET['bricks'] ) ) {
			return true;
		}
		if ( function_exists( 'et_core_is_fb_enabled' ) && et_core_is_fb_enabled() ) {
			return true;
		}
		if ( defined( 'SHOW_CT_BUILDER' ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Fingerprint of the installed plugin and theme set.
	 *
	 * The cache key was previously the page path plus this plugin's own
	 * version, which does not change when *another* plugin's CSS changes — so
	 * used CSS could keep being served after an update, with rules the static
	 * pass had stripped as "unused" still missing. `upgrader_process_complete`
	 * covers updates made through the WordPress updater, but nothing fires at
	 * all when files are replaced over SFTP or a host file manager.
	 *
	 * This hashes the top-level plugin and theme directory names and their
	 * modification times, which change when a plugin or theme is added,
	 * removed, or replaced wholesale — including extracting a ZIP over it.
	 * Deliberately shallow: it stays cheap, and it will not thrash when a
	 * plugin writes into its own subdirectories at runtime.
	 *
	 * Cached briefly, so this costs one directory stat every few minutes
	 * rather than one per request.
	 *
	 * @return string
	 */
	private static function asset_epoch() {
		$cached = get_transient( self::EPOCH_TRANSIENT );
		if ( is_string( $cached ) && '' !== $cached ) {
			return $cached;
		}

		$parts = array();
		$roots = array( WP_PLUGIN_DIR, get_theme_root() );

		foreach ( $roots as $root ) {
			if ( ! is_string( $root ) || '' === $root || ! is_dir( $root ) ) {
				continue;
			}
			$items = scandir( $root );
			if ( ! is_array( $items ) ) {
				continue;
			}
			sort( $items );
			foreach ( $items as $item ) {
				if ( '.' === $item || '..' === $item ) {
					continue;
				}
				$parts[] = $item . ':' . (int) filemtime( $root . '/' . $item );
			}
		}

		$epoch = substr( md5( implode( '|', $parts ) ), 0, 12 );
		set_transient( self::EPOCH_TRANSIENT, $epoch, 5 * MINUTE_IN_SECONDS );

		return $epoch;
	}

	/**
	 * Cache key for a normalised path.
	 *
	 * Single source of truth: the serve path and the per-post purge path must
	 * agree, or a purge silently deletes nothing.
	 *
	 * @param string $path Normalised, lower-cased path.
	 * @return string
	 */
	private static function cache_key_for_path( $path ) {
		return md5( $path . '|' . MBRPE_VERSION . '|' . self::asset_epoch() );
	}

	/**
	 * Cache key for the current request.
	 */
	private function compute_key() {
		$path = '/';
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$uri  = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
			$path = wp_parse_url( $uri, PHP_URL_PATH );
			$path = $path ? $path : '/';
		}
		$path = strtolower( rtrim( $path, '/' ) );
		if ( '' === $path ) {
			$path = '/';
		}
		return self::cache_key_for_path( $path );
	}

	public function on_template_redirect() {
		if ( $this->should_skip() ) {
			return;
		}

		// Critical CSS (XL) owns pages with a matching slot — stand down there.
		// class_exists keeps this file identical in the lite build, where the
		// critical-CSS class is absent and this guard is simply false.
		if ( class_exists( 'MBRPE_Critical_CSS' ) && MBRPE_Critical_CSS::is_active() ) {
			return;
		}

		$dir = self::get_dir();
		if ( ! is_array( $dir ) ) {
			return;
		}

		$this->cache_file = $dir['path'] . '/' . $this->compute_key() . '.css';

		if ( is_readable( $this->cache_file ) && filesize( $this->cache_file ) > 0 ) {
			// SERVE: inline the cached used CSS, then async the original
			// stylesheets by rewriting the final HTML. Rewriting the buffer
			// (rather than filtering style_loader_tag) catches every local
			// stylesheet regardless of how it was emitted — enqueued, merged
			// into a combine bundle, or printed directly by the theme or a
			// page builder.
			add_action( 'wp_head', array( $this, 'print_inline_used_css' ), 2 );
			ob_start( array( $this, 'async_links_buffer' ) );
			return;
		}

		// GENERATE: serve normally now, build the cache after the response ships.
		$this->buffering   = true;
		$this->current_url = $this->current_url();
		ob_start();
		add_action( 'shutdown', array( $this, 'generate_after_response' ), 0 );
	}

	/**
	 * Absolute URL of the current request, used to purge just this page from
	 * the host page cache after its used CSS is generated.
	 *
	 * @return string
	 */
	private function current_url() {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return home_url( '/' );
		}
		$uri = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		return home_url( $uri );
	}

	/**
	 * Inline the cached used CSS in the document head.
	 */
	public function print_inline_used_css() {
		if ( '' === $this->cache_file || ! is_readable( $this->cache_file ) ) {
			return;
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading the plugin's own cached CSS file from wp-uploads.
		$css = file_get_contents( $this->cache_file );
		if ( false === $css || '' === $css ) {
			return;
		}
		echo "\n<style id=\"mbrpe-used-css\">" . $css . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Cached, plugin-generated CSS; escaping would corrupt it. Not user input.
	}

	/**
	 * Rewrite local, render-blocking <link rel="stylesheet"> tags in the final
	 * HTML into a preload+onload async pattern (with a <noscript> fallback), so
	 * the inline used CSS handles first paint and the full sheets load after.
	 * Works on the rendered HTML, so it catches sheets however they were output.
	 *
	 * @param string $html
	 * @return string
	 */
	public function async_links_buffer( $html ) {
		if ( '' === $html ) {
			return $html;
		}
		$exclusions = $this->get_exclusions();

		// Protect existing <noscript> blocks so we never rewrite a fallback
		// <link> inside one (which another async layer may have added).
		$shelf = array();
		$html  = preg_replace_callback(
			'#<noscript\b[^>]*>.*?</noscript>#is',
			function ( $m ) use ( &$shelf ) {
				$key           = '<!--mbrpe-ns-' . count( $shelf ) . '-->';
				$shelf[ $key ] = $m[0];
				return $key;
			},
			$html
		);

		$result = preg_replace_callback(
			'#<link\b[^>]*\brel=(["\'])stylesheet\1[^>]*>#i',
			function ( $m ) use ( $exclusions ) {
				$tag = $m[0];

				// Print-media sheets don't block render — leave them.
				if ( preg_match( '/\bmedia=(["\'])\s*print\s*\1/i', $tag ) ) {
					return $tag;
				}
				if ( ! preg_match( '/\bhref=(["\'])(.*?)\1/i', $tag, $h ) ) {
					return $tag;
				}
				$href = trim( html_entity_decode( $h[2] ) );
				if ( '' === $href ) {
					return $tag;
				}
				// Skip the admin bar / dashicons and anything not local.
				if ( false !== strpos( $href, 'wp-includes/css/dashicons' ) || false !== strpos( $href, 'admin-bar' ) ) {
					return $tag;
				}
				if ( '' === MBRPE_CSS_Optimizations::url_to_path( $href ) ) {
					return $tag;
				}
				foreach ( $exclusions as $needle ) {
					if ( false !== strpos( $href, $needle ) ) {
						return $tag;
					}
				}

				$media = 'all';
				if ( preg_match( '/\bmedia=(["\'])(.*?)\1/i', $tag, $mm ) && '' !== trim( $mm[2] ) ) {
					$media = $mm[2];
				}
				$esc_href  = esc_url( $href );
				$esc_media = esc_attr( $media );

				return '<link rel="preload" as="style" href="' . $esc_href . '" media="' . $esc_media
					. '" onload="this.onload=null;this.rel=\'stylesheet\'">'
					. '<noscript><link rel="stylesheet" href="' . $esc_href . '" media="' . $esc_media . '"></noscript>';
			},
			$html
		);

		if ( null === $result ) {
			$result = $html;
		}

		// Restore the protected <noscript> blocks.
		if ( ! empty( $shelf ) ) {
			$result = strtr( $result, $shelf );
		}

		return $result;
	}

	/**
	 * After the page has been sent to the visitor, analyse it and cache the
	 * used CSS so the next hit can be served from cache.
	 */
	public function generate_after_response() {
		if ( ! $this->buffering ) {
			return;
		}
		$this->buffering = false;

		$html = '';
		if ( ob_get_level() > 0 ) {
			$html = ob_get_clean();
		}

		// Ship the page to the visitor and release the connection before doing
		// the (heavier) analysis work.
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Re-emitting the already-rendered page buffer unchanged.
		if ( function_exists( 'fastcgi_finish_request' ) ) {
			fastcgi_finish_request();
		}

		if ( '' === $html || '' === $this->cache_file ) {
			return;
		}
		// A lock avoids two simultaneous first-hits both generating.
		$lock = $this->cache_file . '.lock';
		if ( file_exists( $lock ) && ( time() - (int) filemtime( $lock ) ) < 120 ) {
			return;
		}
		MBRPE_CSS_Optimizations::write_file( $lock, (string) time() );

		try {
			$used = $this->build_used_css( $html );
			if ( '' !== $used ) {
				MBRPE_CSS_Optimizations::write_file( $this->cache_file, $used );

				// The full-CSS page is now sitting in the host page cache. Purge
				// just this URL so the next hit re-renders through PHP — serving
				// (and letting the host cache) the optimised inline+async version.
				$this->purge_page_cache( $this->current_url );
			}
		} catch ( \Throwable $e ) {
			// Never let generation affect the (already-sent) page.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'MBRPE Used CSS generation failed: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
		}

		if ( file_exists( $lock ) ) {
			wp_delete_file( $lock );
		}
	}

	/**
	 * Build the used CSS for a rendered page: match each local stylesheet
	 * against the delivered DOM, absolutise its url()s, and concatenate.
	 *
	 * @param string $html Rendered page HTML.
	 * @return string Minified used CSS (empty on failure).
	 */
	private function build_used_css( $html ) {
		require_once MBRPE_PLUGIN_DIR . 'includes/vendor/autoload.php';
		require_once MBRPE_PLUGIN_DIR . 'includes/class-used-css-engine.php';

		// Collect local stylesheet hrefs in document order.
		$sheets = $this->extract_local_sheets( $html );
		if ( empty( $sheets ) ) {
			return '';
		}

		$engine = new MBRPE_Used_CSS_Engine();
		$engine->load_html( $html );

		$combined = '';
		foreach ( $sheets as $href ) {
			$path = MBRPE_CSS_Optimizations::url_to_path( $href );
			if ( '' === $path || ! is_readable( $path ) ) {
				continue;
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a local stylesheet from disk for analysis.
			$css = file_get_contents( $path );
			if ( false === $css || '' === $css ) {
				continue;
			}
			// Absolutise url()/@import against the stylesheet's own location so
			// they still resolve once the CSS is inlined in the document.
			$css   = MBRPE_CSS_Optimizations::rewrite_css_urls( $css, $href );
			$res   = $engine->analyse( $css, $href );
			$combined .= $res['used_css'];
		}

		$combined = trim( $combined );
		if ( '' === $combined ) {
			return '';
		}
		return MBRPE_CSS_Optimizations::minify_css_string( $combined );
	}

	/**
	 * Extract same-host stylesheet hrefs from rendered HTML, in order.
	 *
	 * @param string $html
	 * @return string[]
	 */
	private function extract_local_sheets( $html ) {
		$sheets = array();
		if ( ! class_exists( 'DOMDocument' ) ) {
			return $sheets;
		}
		$dom  = new DOMDocument();
		$prev = libxml_use_internal_errors( true );
		$dom->loadHTML( '<?xml encoding="UTF-8">' . $html, LIBXML_NOWARNING | LIBXML_NOERROR );
		libxml_clear_errors();
		libxml_use_internal_errors( $prev );

		foreach ( $dom->getElementsByTagName( 'link' ) as $link ) {
			$rel = strtolower( $link->getAttribute( 'rel' ) );
			if ( false === strpos( $rel, 'stylesheet' ) ) {
				continue;
			}
			$media = strtolower( $link->getAttribute( 'media' ) );
			if ( 'print' === $media ) {
				continue;
			}
			$href = $link->getAttribute( 'href' );
			if ( '' === $href ) {
				continue;
			}
			if ( '' === MBRPE_CSS_Optimizations::url_to_path( $href ) ) {
				continue; // external / unresolvable
			}
			foreach ( $this->get_exclusions() as $needle ) {
				if ( false !== strpos( $href, $needle ) ) {
					continue 2;
				}
			}
			$sheets[ $href ] = true;
		}
		return array_keys( $sheets );
	}

	// --- Cache plumbing ----------------------------------------------------

	/**
	 * Ensure the used-CSS cache directory exists; return path+url or ''.
	 *
	 * @return array|string
	 */
	public static function get_dir() {
		$upload = wp_upload_dir();
		if ( ! empty( $upload['error'] ) || empty( $upload['basedir'] ) ) {
			return '';
		}
		$path = trailingslashit( $upload['basedir'] ) . self::DIRNAME;
		$url  = trailingslashit( $upload['baseurl'] ) . self::DIRNAME;
		if ( ! is_dir( $path ) ) {
			if ( ! wp_mkdir_p( $path ) ) {
				return '';
			}
			MBRPE_CSS_Optimizations::write_file( $path . '/index.html', '' );
		}
		return array(
			'path' => untrailingslashit( $path ),
			'url'  => untrailingslashit( $url ),
		);
	}

	/**
	 * Delete every cached used-CSS file.
	 *
	 * @return int Files removed.
	 */
	public static function purge_all() {
		$dir = self::get_dir();
		if ( ! is_array( $dir ) ) {
			return 0;
		}
		$count = 0;
		foreach ( (array) glob( $dir['path'] . '/*.css' ) as $file ) {
			if ( is_file( $file ) && wp_delete_file( $file ) !== false ) {
				$count++;
			}
		}
		foreach ( (array) glob( $dir['path'] . '/*.lock' ) as $lock ) {
			wp_delete_file( $lock );
		}
		// Force the plugin/theme fingerprint to be recalculated on the next
		// request, so a manual purge cannot be undone by a stale epoch.
		delete_transient( self::EPOCH_TRANSIENT );
		return $count;
	}

	/**
	 * Purge the cached used CSS for a single post's permalink.
	 *
	 * @param int $post_id
	 */
	public function purge_for_post( $post_id ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		$url = get_permalink( $post_id );
		if ( ! $url ) {
			return;
		}
		$path = wp_parse_url( $url, PHP_URL_PATH );
		$path = strtolower( rtrim( $path ? $path : '/', '/' ) );
		if ( '' === $path ) {
			$path = '/';
		}
		$dir = self::get_dir();
		if ( ! is_array( $dir ) ) {
			return;
		}
		$file = $dir['path'] . '/' . self::cache_key_for_path( $path ) . '.css';
		if ( is_file( $file ) ) {
			wp_delete_file( $file );
		}
	}

	/**
	 * Purge a single URL from the host's full-page cache so the next request
	 * re-renders through PHP. Currently handles SiteGround's Speed Optimizer
	 * via its public sg_cachepress_purge_cache() function; other caches can
	 * hook the mbrpe_usedcss_purge_url action.
	 *
	 * @param string $url Absolute URL to purge.
	 */
	private function purge_page_cache( $url ) {
		if ( '' === $url ) {
			return;
		}
		// SiteGround Speed Optimizer: documented public function, accepts a URL
		// to purge just that page rather than the whole site.
		if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
			sg_cachepress_purge_cache( $url );
		}
		/**
		 * Fires after Mode A generates used CSS for a URL, so other page caches
		 * can purge just this URL and let the optimised render be re-cached.
		 *
		 * @param string $url Absolute URL that was just (re)generated.
		 */
		do_action( 'mbrpe_usedcss_purge_url', $url );
	}

	/**
	 * Count and total size of cached used-CSS files.
	 *
	 * @return array{count:int,bytes:int}
	 */
	public static function cache_stats() {
		$dir = self::get_dir();
		$out = array(
			'count' => 0,
			'bytes' => 0,
		);
		if ( ! is_array( $dir ) ) {
			return $out;
		}
		foreach ( (array) glob( $dir['path'] . '/*.css' ) as $file ) {
			if ( is_file( $file ) ) {
				$out['count']++;
				$out['bytes'] += (int) filesize( $file );
			}
		}
		return $out;
	}
}
