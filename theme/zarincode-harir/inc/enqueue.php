<?php
/**
 * بارگذاری دارایی‌ها: فونت‌های محلی، CSS و JS.
 *
 * همه با wp_enqueue_* — بدون هیچ <link> یا <script> دستی (الزام ThemeForest).
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

/**
 * فونت‌های محلی Arad و Ario.
 *
 * نسخهٔ WOFF2 با font-display: swap برای CLS بهتر؛ TTF به‌عنوان fallback.
 * unicode-range باعث می‌شود Ario فقط برای لاتین بارگذاری شود.
 */
function zch_fonts_face_css() {
	$dir = trailingslashit( ZCH_THEME_DIR ) . 'assets/fonts/';
	$uri = trailingslashit( ZCH_THEME_URI ) . 'assets/fonts/';

	$faces = [];

	if ( file_exists( $dir . 'Arad-VF.woff2' ) ) {
		$faces[] = sprintf(
			'@font-face{font-family:"Arad";src:url("%1$s") format("woff2-variations"),url("%2$s") format("truetype");font-weight:100 800;font-style:normal;font-display:swap;}',
			esc_url( $uri . 'Arad-VF.woff2' ),
			esc_url( $uri . 'Arad-VF.ttf' )
		);
	}

	if ( file_exists( $dir . 'Ario-Dots1.woff2' ) ) {
		// Ario فقط برای overlineهای لاتین استفاده می‌شود → unicode-range محدود.
		$faces[] = sprintf(
			'@font-face{font-family:"Ario";src:url("%1$s") format("woff2"),url("%2$s") format("truetype");font-weight:400;font-style:normal;font-display:swap;unicode-range:U+0020-007F,U+200C-200F,U+2028-202F;}',
			esc_url( $uri . 'Ario-Dots1.woff2' ),
			esc_url( $uri . 'Ario-Dots1.ttf' )
		);
	}

	if ( empty( $faces ) ) {
		return '';
	}

	return implode( "\n", $faces );
}

/**
 * افزودن @font-face به CSS توکن‌ها با wp_add_inline_style.
 *
 * به‌جای ساخت فایل جدا، این‌طور کش وردپرس آن را یک‌جا مدیریت می‌کند.
 */
function zch_add_font_faces() {
	$css = zch_fonts_face_css();

	if ( '' !== $css ) {
		wp_add_inline_style( 'zch-tokens', $css );
	}
}
add_action( 'wp_enqueue_scripts', 'zch_add_font_faces', 20 );

/**
 * بارگذاری CSS قالب.
 */
function zch_enqueue_styles() {
	$version = ZCH_THEME_VERSION;
	$debug   = defined( 'WP_DEBUG' ) && WP_DEBUG;

	wp_enqueue_style( 'zch-tokens', ZCH_THEME_URI . '/assets/css/tokens.css', [], $version );

	$styles = [
		'zch-base'        => [ 'base.css', [ 'zch-tokens' ] ],
		'zch-components'  => [ 'components.css', [ 'zch-base' ] ],
		'zch-header'      => [ 'header.css', [ 'zch-base' ] ],
		'zch-footer'      => [ 'footer.css', [ 'zch-base' ] ],
		'zch-a11y'        => [ 'a11y.css', [ 'zch-base' ] ],
	];

	// ووکامرس فقط در صفحات فروشگاهی.
	if ( zch_is_woocommerce_page() ) {
		$styles['zch-woocommerce'] = [ 'woocommerce.css', [ 'zch-base' ] ];
	}

	// پنل کاربری فقط در حساب کاربری.
	if ( function_exists( 'is_account_page' ) && is_account_page() ) {
		$styles['zch-panel'] = [ 'panel.css', [ 'zch-base' ] ];
	}

	foreach ( $styles as $handle => $file ) {
		if ( ! file_exists( ZCH_THEME_DIR . '/assets/css/' . $file[0] ) ) {
			continue;
		}

		wp_enqueue_style( $handle, ZCH_THEME_URI . '/assets/css/' . $file[0], $file[1], $debug ? (string) filemtime( ZCH_THEME_DIR . '/assets/css/' . $file[0] ) : $version );
	}

	// برگهٔ اصلی style.css برای سازگاری با Child Theme و Theme-Check.
	wp_enqueue_style( 'zch-theme', get_stylesheet_uri(), [], $version );

	// Child Theme.
	if ( is_child_theme() ) {
		wp_enqueue_style( 'zch-child', get_stylesheet_directory_uri() . '/style.css', [ 'zch-theme' ], $version );
	}

	// فونت بالای صفحه را preload می‌کنیم (LCP).
	$font_uri = ZCH_THEME_URI . '/assets/fonts/Arad-VF.woff2';
	if ( file_exists( ZCH_THEME_DIR . '/assets/fonts/Arad-VF.woff2' ) ) {
		wp_enqueue_style( 'zch-font-preload', $font_uri, [], null ); // phpcs:ignore
		wp_style_add_data( 'zch-font-preload', 'preload', true );
	}
}
add_action( 'wp_enqueue_scripts', 'zch_enqueue_styles' );

/**
 * بارگذاری JS قالب — بدون وابستگی به jQuery.
 */
function zch_enqueue_scripts() {
	$version = ZCH_THEME_VERSION;
	$debug   = defined( 'WP_DEBUG' ) && WP_DEBUG;

	$scripts = [
		'zch-navigation' => [ 'navigation.js', [], true ],
		'zch-search'     => [ 'search.js', [], true ],
	];

	if ( function_exists( 'is_product' ) && is_product() ) {
		$scripts['zch-product'] = [ 'product.js', [], true ];
	}

	if ( function_exists( 'is_account_page' ) && is_account_page() ) {
		$scripts['zch-panel'] = [ 'panels.js', [], true ];
	}

	$scripts['zch-theme'] = [ 'theme.js', [], true ];

	foreach ( $scripts as $handle => $file ) {
		if ( ! file_exists( ZCH_THEME_DIR . '/assets/js/' . $file[0] ) ) {
			continue;
		}

		wp_enqueue_script(
			$handle,
			ZCH_THEME_URI . '/assets/js/' . $file[0],
			$file[1],
			$debug ? (string) filemtime( ZCH_THEME_DIR . '/assets/js/' . $file[0] ) : $version,
			[ 'strategy' => $file[2] ? 'defer' : 'in_footer', 'in_footer' => $file[2] ]
		);
	}

	// داده‌های مورد نیاز JS — با wp_localize_script نه متغیر سراسری.
	if ( wp_script_is( 'zch-theme', 'enqueued' ) ) {
		wp_localize_script(
			'zch-theme',
			'zchData',
			[
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'restUrl'   => esc_url_raw( rest_url( 'zch/v1/' ) ),
				'nonce'     => wp_create_nonce( 'zch_front' ),
				'restNonce' => wp_create_nonce( 'wp_rest' ),
				'cartUrl'   => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '',
				'homeUrl'   => home_url( '/' ),
				'i18n'      => [
					'searching'  => __( 'در حال جست‌وجو…', 'zarincode-harir' ),
					'noResults'  => __( 'نتیجه‌ای یافت نشد', 'zarincode-harir' ),
					'addedToCart' => __( 'به سبد خرید افزوده شد', 'zarincode-harir' ),
					'error'      => __( 'خطایی رخ داد. دوباره تلاش کنید.', 'zarincode-harir' ),
					'close'      => __( 'بستن', 'zarincode-harir' ),
					'menu'       => __( 'منو', 'zarincode-harir' ),
				],
			]
		);
	}
}
add_action( 'wp_enqueue_scripts', 'zch_enqueue_scripts' );

/**
 * CSS اختصاصی برای ویرایشگر بلوک.
 */
function zch_editor_assets() {
	if ( file_exists( ZCH_THEME_DIR . '/assets/css/editor.css' ) ) {
		add_editor_style( 'assets/css/editor.css' );
	}

	if ( file_exists( ZCH_THEME_DIR . '/assets/css/tokens.css' ) ) {
		add_editor_style( 'assets/css/tokens.css' );
	}
}
add_action( 'after_setup_theme', 'zch_editor_assets' );

/**
 * تشخیص صفحات فروشگاهی برای بارگذاری شرطی CSS.
 *
 * @return bool
 */
function zch_is_woocommerce_page() {
	if ( ! function_exists( 'is_woocommerce' ) ) {
		return false;
	}

	return is_woocommerce() || is_cart() || is_checkout() || is_account_page();
}

/**
 * حذف نسخهٔ jQuery قدیمی و جایگزینی آن — مجاز نیست (الزام ThemeForest).
 * ما فقط jQuery را deregister نمی‌کنیم؛ صرفاً قالب بدون jQuery نوشته می‌شود.
 */
function zch_remove_jquery_migrate( $scripts ) {
	if ( is_admin() || ! isset( $scripts->registered['jquery'] ) ) {
		return;
	}

	$jquery = $scripts->registered['jquery'];

	if ( $jquery->deps ) {
		$jquery->deps = array_diff( $jquery->deps, [ 'jquery-migrate' ] );
	}
}
add_action( 'wp_default_scripts', 'zch_remove_jquery_migrate' );
