<?php
/**
 * Minified copies of local stylesheets and scripts.
 *
 * Minify CSS and Minify JavaScript previously only ever tried to shorten
 * inline <style> and <script> blocks, and even that never ran: both filters
 * sat on style_loader_tag / script_loader_tag and returned early unless the
 * src was empty, and WordPress never calls either filter for a handle with no
 * src. External files, which are what PageSpeed Insights actually lists under
 * "Minify CSS" and "Minify JavaScript", were never touched at all.
 *
 * This class writes a minified copy of each eligible local file under
 * /uploads/mbr-performance-min/, mirroring the file's path relative to the
 * WordPress root so URL-fragment exclusions ("plugins/elementor", "frontend")
 * written against the original still match the copy. The copy's filename
 * carries a fingerprint of the source's path, size and modification time, so
 * an updated plugin or theme gets a fresh copy automatically.
 *
 * Files that are already minified are detected once per file version and left
 * pointing at the original — a marker file records the decision so the check
 * is not repeated on every request.
 *
 * @package MBR_Performance
 * @since   2.1.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MBRPE_Asset_Minifier {

	/**
	 * Sub-directory of uploads holding the minified copies.
	 */
	const DIRNAME = 'mbr-performance-min';

	/**
	 * Bump when the minification output changes, so every copy is rebuilt.
	 */
	const ALGO = '1';

	/**
	 * Largest source file worth minifying at request time, in bytes.
	 */
	const MAX_BYTES = 1572864; // 1.5 MB.

	/**
	 * A copy must save at least this fraction of the original to be served;
	 * anything less means the file was already minified.
	 */
	const MIN_SAVING = 0.05;

	/**
	 * Per-request memo of source URL => served URL.
	 *
	 * @var array
	 */
	private static $memo = array();

	/**
	 * Return the URL of a minified copy of a local stylesheet or script, or
	 * the original URL when no copy is appropriate.
	 *
	 * @param string $src  URL as WordPress is about to print it.
	 * @param string $type 'css' or 'js'.
	 * @return string
	 */
	public static function maybe_minified_url( $src, $type ) {
		if ( ! is_string( $src ) || '' === $src ) {
			return $src;
		}
		$type = ( 'js' === $type ) ? 'js' : 'css';

		$memo_key = $type . '|' . $src;
		if ( isset( self::$memo[ $memo_key ] ) ) {
			return self::$memo[ $memo_key ];
		}
		self::$memo[ $memo_key ] = $src;

		$path = MBRPE_CSS_Optimizations::url_to_path( $src, array( $type ) );
		if ( '' === $path ) {
			return $src; // External, unresolvable or wrong extension.
		}

		// Never re-minify our own output (combined bundles, earlier copies).
		$norm_path = wp_normalize_path( $path );
		$uploads   = wp_upload_dir();
		if ( ! empty( $uploads['basedir'] ) ) {
			$base = trailingslashit( wp_normalize_path( $uploads['basedir'] ) );
			if ( 0 === strpos( $norm_path, $base . self::DIRNAME . '/' )
				|| 0 === strpos( $norm_path, $base . MBRPE_CSS_Optimizations::COMBINE_DIRNAME . '/' ) ) {
				return $src;
			}
		}

		// Conventionally named minified files are trusted as they are.
		if ( preg_match( '/[.\-]min\.(css|js)$/i', $norm_path ) ) {
			return $src;
		}

		$size  = @filesize( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		$mtime = @filemtime( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( ! $size || $size > self::MAX_BYTES || ! $mtime ) {
			return $src;
		}

		$target = self::target_for( $norm_path, $size, $mtime, $type );
		if ( empty( $target ) ) {
			return $src;
		}

		// Already decided this version isn't worth it.
		if ( is_file( $target['skip'] ) ) {
			return $src;
		}

		if ( ! is_file( $target['path'] ) ) {
			if ( ! self::build( $path, $src, $type, $target ) ) {
				return $src;
			}
		}

		self::$memo[ $memo_key ] = $target['url'];
		return $target['url'];
	}

	/**
	 * Work out where the minified copy of a file lives.
	 *
	 * @param string $norm_path Normalised absolute source path.
	 * @param int    $size
	 * @param int    $mtime
	 * @param string $type
	 * @return array|null {path, url, skip, dir}
	 */
	private static function target_for( $norm_path, $size, $mtime, $type ) {
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) ) {
			return null;
		}

		// Mirror the path relative to the WordPress root where possible, or
		// to wp-content for installs that move it outside ABSPATH.
		$roots = array(
			trailingslashit( wp_normalize_path( ABSPATH ) ),
			trailingslashit( wp_normalize_path( dirname( WP_CONTENT_DIR ) ) ),
		);
		$rel = '';
		foreach ( $roots as $root ) {
			if ( 0 === strpos( $norm_path, $root ) ) {
				$rel = substr( $norm_path, strlen( $root ) );
				break;
			}
		}
		if ( '' === $rel || false !== strpos( $rel, '..' ) ) {
			return null;
		}

		$rel_dir = dirname( $rel );
		$rel_dir = ( '.' === $rel_dir ) ? '' : trim( $rel_dir, '/' ) . '/';
		$stem    = pathinfo( $rel, PATHINFO_FILENAME );
		$hash    = substr( md5( $norm_path . '|' . $size . '|' . $mtime . '|' . self::ALGO ), 0, 10 );
		$name    = $stem . '.' . $hash . '.min.' . $type;

		$dir = trailingslashit( wp_normalize_path( $uploads['basedir'] ) ) . self::DIRNAME . '/' . $rel_dir;
		$url = trailingslashit( $uploads['baseurl'] ) . self::DIRNAME . '/' . $rel_dir;

		return array(
			'dir'  => $dir,
			'path' => $dir . $name,
			'url'  => $url . $name,
			'skip' => $dir . $stem . '.' . $hash . '.skip',
		);
	}

	/**
	 * Minify a source file into its target, or record that it isn't worth it.
	 *
	 * @param string $path   Absolute source path.
	 * @param string $src    Source URL (for rewriting relative CSS urls).
	 * @param string $type   'css' or 'js'.
	 * @param array  $target From target_for().
	 * @return bool True if a minified copy now exists.
	 */
	private static function build( $path, $src, $type, $target ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a validated local asset; see url_to_path().
		$original = file_get_contents( $path );
		if ( false === $original || '' === $original ) {
			return false;
		}

		if ( 'js' === $type && self::locates_itself( $original ) ) {
			// A script that works out its own URL at run time — to load
			// chunks, workers or assets beside it — would go looking in the
			// cache directory. Leave it where it is.
			$min = '';
		} else {
			$min = ( 'css' === $type )
				? self::minify_css_file( $original, $src )
				: self::minify_js( $original );
		}

		if ( ! wp_mkdir_p( $target['dir'] ) ) {
			return false;
		}
		self::ensure_root_index();

		// Failed, or saved too little to be worth a different URL: remember
		// that for this version of the file and serve the original.
		if ( '' === $min || strlen( $min ) > strlen( $original ) * ( 1 - self::MIN_SAVING ) ) {
			MBRPE_CSS_Optimizations::write_file( $target['skip'], '' );
			return false;
		}

		// Write to a temporary name and rename into place, so a concurrent
		// request can never be handed a half-written file.
		$tmp = $target['path'] . '.' . wp_generate_password( 6, false ) . '.tmp';
		if ( ! MBRPE_CSS_Optimizations::write_file( $tmp, $min ) ) {
			return false;
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- Atomic replace inside the plugin's own cache directory.
		if ( ! @rename( $tmp, $target['path'] ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			wp_delete_file( $tmp );
			return is_file( $target['path'] );
		}
		return true;
	}

	/**
	 * Does a script derive paths from its own location?
	 *
	 * @param string $js
	 * @return bool
	 */
	private static function locates_itself( $js ) {
		foreach ( array( 'document.currentScript', '__webpack_require__.p', '__webpack_public_path__', 'webpackChunk', 'import.meta.url' ) as $needle ) {
			if ( false !== strpos( $js, $needle ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Prepare a stylesheet file for serving from a different directory, then
	 * minify it.
	 *
	 * @param string $css
	 * @param string $src Original URL.
	 * @return string
	 */
	private static function minify_css_file( $css, $src ) {
		$css = preg_replace( '/^\xEF\xBB\xBF/', '', $css );
		// The copy lives elsewhere, so relative url() and @import targets must
		// become absolute against the original location.
		$css = MBRPE_CSS_Optimizations::rewrite_css_urls( $css, $src );
		// A source map comment would point at the wrong place from the copy.
		$css = preg_replace( '#/\*\#\s*sourceMappingURL=[^*]*\*/#', '', $css );
		return MBRPE_CSS_Optimizations::minify_css_string( $css );
	}

	/**
	 * Minify a JavaScript string with the bundled JShrink tokeniser.
	 *
	 * Returns an empty string on any failure, which callers treat as "keep
	 * the original" — a script is never served half-processed.
	 *
	 * @param string $js
	 * @return string
	 */
	public static function minify_js( $js ) {
		if ( ! is_string( $js ) || '' === trim( $js ) ) {
			return '';
		}
		if ( ! class_exists( '\JShrink\Minifier' ) ) {
			require_once MBRPE_PLUGIN_DIR . 'includes/vendor/jshrink/Minifier.php';
		}
		$js = preg_replace( '/^\xEF\xBB\xBF/', '', $js );
		try {
			$out = \JShrink\Minifier::minify( $js, array( 'flaggedComments' => true ) );
		} catch ( \Throwable $e ) {
			return '';
		}
		if ( ! is_string( $out ) || '' === trim( $out ) ) {
			return '';
		}
		// Keep a file that ends without a semicolon from fusing with whatever
		// the browser runs next.
		return rtrim( $out ) . "\n";
	}

	/**
	 * Minify an inline JavaScript snippet, returning the original on failure
	 * or when the snippet is one a tokeniser shouldn't be trusted with.
	 *
	 * @param string $js
	 * @return string
	 */
	public static function minify_inline_js( $js ) {
		if ( ! is_string( $js ) || '' === trim( $js ) ) {
			return $js;
		}
		// Legacy HTML comment wrappers are valid in inline script and are not
		// understood by the tokeniser.
		if ( false !== strpos( $js, '<!--' ) || false !== strpos( $js, '-->' ) ) {
			return $js;
		}
		$min = self::minify_js( $js );
		if ( '' === $min || strlen( $min ) >= strlen( $js ) ) {
			return $js;
		}
		return rtrim( $min );
	}

	/**
	 * Write a blank index into the cache root so it can't be browsed.
	 *
	 * @return void
	 */
	private static function ensure_root_index() {
		$uploads = wp_upload_dir();
		if ( empty( $uploads['basedir'] ) ) {
			return;
		}
		$index = trailingslashit( $uploads['basedir'] ) . self::DIRNAME . '/index.html';
		if ( ! is_file( $index ) ) {
			MBRPE_CSS_Optimizations::write_file( $index, '' );
		}
	}

	/**
	 * Delete minified copies.
	 *
	 * @param string $type 'css', 'js' or 'all'.
	 * @return int Files removed (skip markers not counted).
	 */
	public static function purge( $type = 'all' ) {
		$uploads = wp_upload_dir();
		if ( empty( $uploads['basedir'] ) ) {
			return 0;
		}
		$root = trailingslashit( $uploads['basedir'] ) . self::DIRNAME;
		if ( ! is_dir( $root ) || is_link( $root ) ) {
			return 0;
		}

		self::$memo = array();
		$deleted    = 0;

		try {
			$it = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ),
				RecursiveIteratorIterator::CHILD_FIRST
			);
			foreach ( $it as $item ) {
				$p = $item->getPathname();
				if ( $item->isLink() ) {
					continue;
				}
				if ( $item->isDir() ) {
					if ( 'all' === $type ) {
						@rmdir( $p ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
					}
					continue;
				}
				$name = $item->getFilename();
				if ( 'index.html' === $name && dirname( $p ) === $root ) {
					continue;
				}
				$is_css = (bool) preg_match( '/\.css$/i', $name );
				$is_js  = (bool) preg_match( '/\.js$/i', $name );
				$is_mk  = (bool) preg_match( '/\.(skip|tmp)$/i', $name );
				if ( 'all' !== $type ) {
					// Skip markers for the other type are left alone; they are
					// harmless and would only be recreated.
					if ( ( 'css' === $type && ! $is_css ) || ( 'js' === $type && ! $is_js ) ) {
						continue;
					}
				}
				wp_delete_file( $p );
				if ( ! $is_mk ) {
					$deleted++;
				}
			}
		} catch ( \Exception $e ) {
			return $deleted;
		}

		return $deleted;
	}

	/**
	 * Count minified copies by type.
	 *
	 * @return array{css:int,css_bytes:int,js:int,js_bytes:int}
	 */
	public static function stats() {
		$stats   = array( 'css' => 0, 'css_bytes' => 0, 'js' => 0, 'js_bytes' => 0 );
		$uploads = wp_upload_dir();
		if ( empty( $uploads['basedir'] ) ) {
			return $stats;
		}
		$root = trailingslashit( $uploads['basedir'] ) . self::DIRNAME;
		if ( ! is_dir( $root ) ) {
			return $stats;
		}
		try {
			$it = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS )
			);
			foreach ( $it as $item ) {
				if ( ! $item->isFile() ) {
					continue;
				}
				$ext = strtolower( $item->getExtension() );
				if ( 'css' === $ext || 'js' === $ext ) {
					$stats[ $ext ]++;
					$stats[ $ext . '_bytes' ] += (int) $item->getSize();
				}
			}
		} catch ( \Exception $e ) {
			return $stats;
		}
		return $stats;
	}
}
