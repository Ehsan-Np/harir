<?php
/**
 * کلاس اصلی قالب.
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

/**
 * Singleton — بارگذاری ماژول‌های قالب.
 */
final class ZCH_Theme {

	/** @var ZCH_Theme|null */
	private static $instance = null;

	/** @var bool */
	private $booted = false;

	/**
	 * @return ZCH_Theme
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {}

	/**
	 * بارگذاری همهٔ ماژول‌ها.
	 */
	public function boot() {
		if ( $this->booted ) {
			return;
		}

		$this->booted = true;

		$modules = [
			'setup',
			'enqueue',
			'icons-data',
			'icons',
			'class-zch-nav-walker',
			'template-functions',
			'template-tags',
			'performance',
			'woocommerce',
			'elementor/locations',
			'elementor/class-zch-elementor',
			'seo/class-zch-schema',
			'customizer/class-zch-customizer',
		];

		foreach ( $modules as $module ) {
			$path = ZCH_THEME_DIR . '/inc/' . $module . '.php';

			if ( ! file_exists( $path ) ) {
				continue;
			}

			/**
			 * فیلتر بارگذاری یک ماژول قالب.
			 *
			 * @param bool   $load
			 * @param string $module
			 */
			if ( apply_filters( 'zch/theme/load_module', true, $module ) ) {
				require_once $path;
			}
		}

		// افزونه‌های لازم از طریق TGM معرفی می‌شوند.
		if ( file_exists( ZCH_THEME_DIR . '/inc/tgm/class-tgm-plugin-activation.php' ) ) {
			require_once ZCH_THEME_DIR . '/inc/tgm/plugin-registration.php';
		}

		/**
		 * پس از بالا آمدن قالب.
		 */
		do_action( 'zch/theme/booted' );
	}

	/**
	 * آیا افزونهٔ هسته فعال است؟
	 *
	 * @return bool
	 */
	public static function core_active() {
		return class_exists( 'Zarincode\Harir\Plugin' );
	}

	/**
	 * آیا المنتور فعال است؟
	 *
	 * @return bool
	 */
	public static function elementor_active() {
		return did_action( 'elementor/loaded' ) || class_exists( 'Elementor\Plugin' );
	}

	/**
	 * آیا Elementor Pro (Theme Builder) در دسترس است؟
	 *
	 * @return bool
	 */
	public static function elementor_pro() {
		return class_exists( 'ElementorPro\Plugin' );
	}

	/**
	 * آیا برای یک Location تمپلیت المنتور وجود دارد؟
	 *
	 * اگر نه، قالب باید تمپلیت PHP بومی خودش را چاپ کند تا صفحه خالی نماند.
	 *
	 * @param string $location
	 * @return bool
	 */
	public static function has_elementor_template( $location ) {
		if ( ! self::elementor_pro() || ! function_exists( 'elementor_theme_do_location' ) ) {
			return false;
		}

		return (bool) apply_filters( 'zch/theme/has_elementor_template', true, $location );
	}

	/**
	 * چاپ یک Location المنتور یا تمپلیت بومی جایگزین.
	 *
	 * @param string $location  نام Location ثبت‌شده.
	 * @param string $fallback  مسیر template-part بومی.
	 * @return bool آیا تمپلیت المنتور چاپ شد؟
	 */
	public static function render_location( $location, $fallback = '' ) {
		if ( self::has_elementor_template( $location ) && function_exists( 'elementor_theme_do_location' ) ) {
			if ( elementor_theme_do_location( $location ) ) {
				return true;
			}
		}

		if ( '' !== $fallback ) {
			get_template_part( $fallback );
		}

		return false;
	}
}
