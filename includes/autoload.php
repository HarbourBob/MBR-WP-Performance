<?php
/**
 * Minimal PSR-4 autoloader for the vendored libraries used by the
 * Used CSS preview tool. No Composer required.
 */
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
			$file     = $base . str_replace( '\\', '/', $relative ) . '.php';
			if ( is_file( $file ) ) {
				require $file;
				return;
			}
		}
	}
);
