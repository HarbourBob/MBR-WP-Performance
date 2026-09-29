<?php
/**
 * Minimal PSR-4 autoloader for the vendored libraries used by the
 * Used CSS preview tool. No Composer required.
 */

// Symfony CssSelector depends on symfony/polyfill-php80 for str_contains().
// That dependency was not vendored alongside it, so supply it here — before
// any vendored class can be loaded — or the component fatals on PHP 7.4.
require_once __DIR__ . '/php80-polyfill.php';

spl_autoload_register(
	function ( $class ) {
		$prefixes = array(
			'Sabberworm\\CSS\\'                 => __DIR__ . '/sabberworm/php-css-parser-src/',
			'Symfony\\Component\\CssSelector\\' => __DIR__ . '/symfony/css-selector/',
		);
		foreach ( $prefixes as $prefix => $base ) {
			$len = strlen( $prefix );
			if ( 0 !== strncmp( $class, $prefix, $len ) ) {
				continue;
			}
			$relative = substr( $class, $len );
			// Nothing here resolves a class name from input, but a loader that
			// concatenates one into a path should refuse traversal on principle.
			if ( false !== strpos( $relative, '..' ) || false !== strpos( $relative, '/' ) ) {
				continue;
			}
			$file = $base . str_replace( '\\', '/', $relative ) . '.php';
			if ( is_file( $file ) ) {
				require $file;
				return;
			}
		}
	}
);
