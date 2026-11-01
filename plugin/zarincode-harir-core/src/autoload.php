<?php
/**
 * بارگذاری خودکار PSR-4 بدون نیاز به Composer.
 *
 * Zarincode\Harir\Foo\Bar  →  src/Foo/Bar.php
 *
 * @package Zarincode\Harir
 */

defined( 'ABSPATH' ) || exit;

spl_autoload_register(
	static function ( $class ) {
		$prefix = 'Zarincode\\Harir\\';

		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}

		$relative = substr( $class, strlen( $prefix ) );
		$path     = __DIR__ . '/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
);
