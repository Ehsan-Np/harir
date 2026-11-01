<?php
/**
 * راه‌اندازی قالب: پشتیبانی‌ها، منوها، سایدبارها و سایزهای تصویر.
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

/**
 * ثبت پشتیبانی‌های اصلی قالب.
 */
function zch_setup() {
	// ترجمه.
	load_theme_textdomain( 'zarincode-harir', ZCH_THEME_DIR . '/languages' );

	// فیدها و عنوان.
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );

	// تصویر شاخص و اندازه‌ها.
	add_theme_support( 'post-thumbnails' );
	add_theme_support(
		'html5',
		[ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ]
	);

	// لوگو و تنظیمات چیدمان.
	add_theme_support(
		'custom-logo',
		[
			'height'      => 96,
			'width'       => 240,
			'flex-height' => true,
			'flex-width'  => true,
		]
	);

	add_theme_support( 'customize-selective-refresh-widgets' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'wp-block-styles' );

	// ووکامرس.
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	// پالت رنگ ویرایشگر هم‌تراز با توکن‌های طرح.
	add_theme_support(
		'editor-color-palette',
		[
			[ 'name' => __( 'پس‌زمینه', 'zarincode-harir' ), 'slug' => 'zch-bg', 'color' => '#fbf9f7' ],
			[ 'name' => __( 'سطح کارت', 'zarincode-harir' ), 'slug' => 'zch-paper', 'color' => '#ffffff' ],
			[ 'name' => __( 'جوهر', 'zarincode-harir' ), 'slug' => 'zch-ink', 'color' => '#3b2935' ],
			[ 'name' => __( 'جوهر ملایم', 'zarincode-harir' ), 'slug' => 'zch-muted', 'color' => '#6b5963' ],
			[ 'name' => __( 'خط', 'zarincode-harir' ), 'slug' => 'zch-line', 'color' => '#eee5e7' ],
			[ 'name' => __( 'برند', 'zarincode-harir' ), 'slug' => 'zch-accent', 'color' => '#59354e' ],
			[ 'name' => __( 'برند روشن', 'zarincode-harir' ), 'slug' => 'zch-accent-2', 'color' => '#cda7b7' ],
			[ 'name' => __( 'طلایی', 'zarincode-harir' ), 'slug' => 'zch-gold', 'color' => '#b48d78' ],
			[ 'name' => __( 'ملایم', 'zarincode-harir' ), 'slug' => 'zch-soft', 'color' => '#f4e7eb' ],
			[ 'name' => __( 'عمده', 'zarincode-harir' ), 'slug' => 'zch-wholesale', 'color' => '#e9dce2' ],
			[ 'name' => __( 'فوتر', 'zarincode-harir' ), 'slug' => 'zch-footer', 'color' => '#30232e' ],
		]
	);

	add_theme_support(
		'editor-font-sizes',
		[
			[ 'name' => __( 'کوچک', 'zarincode-harir' ), 'shortName' => 'S', 'size' => 13, 'slug' => 'small' ],
			[ 'name' => __( 'متوسط', 'zarincode-harir' ), 'shortName' => 'M', 'size' => 16, 'slug' => 'medium' ],
			[ 'name' => __( 'بزرگ', 'zarincode-harir' ), 'shortName' => 'L', 'size' => 26, 'slug' => 'large' ],
			[ 'name' => __( 'خیلی بزرگ', 'zarincode-harir' ), 'shortName' => 'XL', 'size' => 49, 'slug' => 'huge' ],
		]
	);

	// منوها.
	register_nav_menus(
		[
			'zch-primary'  => __( 'منوی اصلی', 'zarincode-harir' ),
			'zch-category' => __( 'دسته‌بندی محصولات (دراپ‌داون هدر)', 'zarincode-harir' ),
			'zch-mobile'   => __( 'منوی موبایل', 'zarincode-harir' ),
			'zch-footer-1' => __( 'فوتر — محصولات خواب', 'zarincode-harir' ),
			'zch-footer-2' => __( 'فوتر — خدمات مشتریان', 'zarincode-harir' ),
			'zch-footer-3' => __( 'فوتر — خرید عمده', 'zarincode-harir' ),
			'zch-footer-4' => __( 'فوتر — حریر رویا', 'zarincode-harir' ),
			'zch-social'   => __( 'شبکه‌های اجتماعی', 'zarincode-harir' ),
		]
	);

	// سایزهای تصویر سفارشی برای srcset و جلوگیری از CLS.
	add_image_size( 'zch-product-card', 480, 480, true );
	add_image_size( 'zch-product-gallery', 900, 900, false );
	add_image_size( 'zch-hero', 1600, 900, false );
	add_image_size( 'zch-thumb', 160, 160, true );
}
add_action( 'after_setup_theme', 'zch_setup' );

/**
 * عرض محتوای پیش‌فرض برای embedها.
 */
function zch_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'zch/content_width', 1280 );
}
add_action( 'after_setup_theme', 'zch_content_width', 0 );

/**
 * ثبت سایدبارها.
 *
 * قالب المنتوری است، اما برای سازگاری با افزونه‌هایی که سایدبار انتظار
 * دارند (الزام ThemeForest: «widget ready در همهٔ مکان‌های اعلام‌شده»)
 * چند ناحیهٔ ویجت ثبت می‌کنیم.
 */
function zch_widgets_init() {
	$areas = [
		'zch-sidebar'      => [ __( 'ستون کناری', 'zarincode-harir' ), __( 'ناحیهٔ کناری صفحات وبلاگ و فروشگاه.', 'zarincode-harir' ) ],
		'zch-footer-1'     => [ __( 'فوتر — ستون ۱', 'zarincode-harir' ), '' ],
		'zch-footer-2'     => [ __( 'فوتر — ستون ۲', 'zarincode-harir' ), '' ],
		'zch-footer-3'     => [ __( 'فوتر — ستون ۳', 'zarincode-harir' ), '' ],
		'zch-footer-4'     => [ __( 'فوتر — ستون ۴', 'zarincode-harir' ), '' ],
		'zch-shop-sidebar' => [ __( 'ستون کناری فروشگاه', 'zarincode-harir' ), __( 'فیلترها در صفحات دسته‌بندی محصول.', 'zarincode-harir' ) ],
	];

	foreach ( $areas as $id => $args ) {
		register_sidebar(
			[
				'name'          => $args[0],
				'id'            => $id,
				'description'   => $args[1],
				'before_widget' => '<section id="%1$s" class="widget zch-widget %2$s">',
				'after_widget'  => '</section>',
				'before_title'  => '<h4 class="widget-title">',
				'after_title'   => '</h4>',
			]
		);
	}
}
add_action( 'widgets_init', 'zch_widgets_init' );

/**
 * افزودن کلاس‌های اختصاصی به بدنه — مطابق منبع HTML (`body.dream`).
 *
 * @param string[] $classes
 * @return string[]
 */
function zch_body_classes( $classes ) {
	$classes[] = 'dream';

	if ( is_rtl() ) {
		$classes[] = 'zch-rtl';
	}

	if ( class_exists( 'WooCommerce' ) ) {
		if ( is_cart() ) {
			$classes[] = 'zch-cart';
		}
		if ( is_checkout() ) {
			$classes[] = 'zch-checkout';
		}
		if ( is_account_page() ) {
			$classes[] = 'zch-account';
		}
		if ( is_product() ) {
			$classes[] = 'product-page';
		}
		if ( is_shop() || is_product_taxonomy() ) {
			$classes[] = 'zch-catalog';
		}
	}

	if ( ! is_active_sidebar( 'zch-sidebar' ) ) {
		$classes[] = 'zch-no-sidebar';
	}

	// کلاس نقش کاربری — برای استایل‌های نقش‌آگاه بدون نشت اطلاعات.
	if ( is_user_logged_in() ) {
		$classes[] = 'zch-logged-in';

		if ( function_exists( 'zch_user_is_wholesale' ) && zch_user_is_wholesale() ) {
			$classes[] = 'zch-role-wholesale';
		}
	}

	/**
	 * فیلتر کلاس‌های بدنه.
	 *
	 * @param string[] $classes
	 */
	return apply_filters( 'zch/body_classes', $classes );
}
add_filter( 'body_class', 'zch_body_classes' );

/**
 * اصلاح زبان و جهت سند.
 *
 * @param string $output
 * @return string
 */
function zch_language_attributes( $output ) {
	if ( false === strpos( $output, 'dir=' ) ) {
		$output .= ' dir="rtl"';
	}

	return $output;
}
add_filter( 'language_attributes', 'zch_language_attributes' );
