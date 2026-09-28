<?php
/**
 * PHP 8.0 string function polyfill.
 *
 * The vendored Symfony CssSelector component calls str_contains(), which is
 * PHP 8.0+. On PHP 7.4 — which this plugin still supports — that produces a
 * fatal "Call to undefined function str_contains()" the moment a Used CSS
 * analysis runs. It escapes casual testing because the component is only
 * loaded when Used CSS Mode A or Mode B actually analyses a page, not on
 * ordinary page loads.
 *
 * Symfony is not at fault here and the library does not need downgrading: its
 * own composer.json declares "php": ">=7.2.5" together with a dependency on
 * symfony/polyfill-php80, which supplies exactly these functions. The component
 * was vendored by hand without that dependency, so this file restores the
 * missing half rather than changing either the library or the plugin's stated
 * PHP requirement.
 *
 * These are the standard reference implementations. Each is guarded by
 * function_exists(), so on PHP 8.0+ this file defines nothing and the native
 * functions are used.
 *
 * @package MBR_Performance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'str_contains' ) ) {
	/**
	 * Determine whether a string contains a given substring.
	 *
	 * @param string $haystack String to search in.
	 * @param string $needle   Substring to search for.
	 * @return bool
	 */
	function str_contains( $haystack, $needle ) {
		return '' === $needle || false !== strpos( $haystack, $needle );
	}
}

if ( ! function_exists( 'str_starts_with' ) ) {
	/**
	 * Determine whether a string starts with a given substring.
	 *
	 * @param string $haystack String to search in.
	 * @param string $needle   Prefix to look for.
	 * @return bool
	 */
	function str_starts_with( $haystack, $needle ) {
		return 0 === strncmp( $haystack, $needle, strlen( $needle ) );
	}
}

if ( ! function_exists( 'str_ends_with' ) ) {
	/**
	 * Determine whether a string ends with a given substring.
	 *
	 * @param string $haystack String to search in.
	 * @param string $needle   Suffix to look for.
	 * @return bool
	 */
	function str_ends_with( $haystack, $needle ) {
		if ( '' === $needle ) {
			return true;
		}
		$len = strlen( $needle );
		if ( $len > strlen( $haystack ) ) {
			return false;
		}
		return 0 === substr_compare( $haystack, $needle, -$len, $len );
	}
}
