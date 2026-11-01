<?php
/**
 * بوت‌استرپ اصلی افزونه.
 *
 * @package Zarincode\Harir
 */

namespace Zarincode\Harir;

defined( 'ABSPATH' ) || exit;

use Zarincode\Harir\Roles\Roles;
use Zarincode\Harir\Woo\Compatibility as Woo_Compatibility;
use Zarincode\Harir\Wholesale\PriceEngine;
use Zarincode\Harir\Wholesale\QuantityRules;
use Zarincode\Harir\Sms\SmsManager;
use Zarincode\Harir\Db\Migrations;

/**
 * کلاس اصلی افزونه — Singleton.
 *
 * هر ماژول باید بتواند با فیلتر `zch/module/enabled` غیرفعال شود تا
 * در صورت تداخل با افزونهٔ دیگر، سایت از کار نیفتد.
 */
final class Plugin {

	/** @var self|null */
	private static $instance = null;

	/** @var array<string,object> نمونهٔ ساخته‌شدهٔ ماژول‌ها. */
	private $services = [];

	/** @var bool */
	private $booted = false;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/**
	 * آیا ووکامرس فعال و آماده است؟
	 */
	public static function woocommerce_active(): bool {
		return class_exists( \WooCommerce::class ) && did_action( 'woocommerce_init' );
	}

	/**
	 * آیا المنتور فعال است؟
	 */
	public static function elementor_active(): bool {
		return did_action( 'elementor/loaded' ) || class_exists( \Elementor\Plugin::class );
	}

	/**
	 * بالا آوردن همهٔ ماژول‌ها.
	 */
	public function boot(): void {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;

		load_plugin_textdomain( 'zarincode-harir-core', false, dirname( ZCH_CORE_BASENAME ) . '/languages' );

		/**
		 * پیش از مقداردهی هر ماژول.
		 *
		 * @param self $this
		 */
		do_action( 'zch/before_boot', $this );

		// ماژول‌های همیشه فعال.
		$this->module( 'roles', Roles::class );
		$this->module( 'sms', SmsManager::class );

		// بررسی ارتقای ساختار دیتابیس.
		if ( is_admin() ) {
			add_action( 'admin_init', [ Migrations::class, 'maybe_upgrade' ] );
		}

		// ماژول‌های وابسته به ووکامرس.
		add_action(
			'woocommerce_init',
			function () {
				$this->module( 'woo_compat', Woo_Compatibility::class );
				$this->module( 'price_engine', PriceEngine::class );
				$this->module( 'quantity_rules', QuantityRules::class );

				/**
				 * پس از بالا آمدن ماژول‌های ووکامرسی.
				 *
				 * @param self $this
				 */
				do_action( 'zch/woocommerce_booted', $this );
			}
		);

		/**
		 * پس از مقداردهی افزونه.
		 *
		 * @param self $this
		 */
		do_action( 'zch/booted', $this );
	}

	/**
	 * ساخت یا بازیابی یک ماژول.
	 *
	 * هر ماژول باید متد عمومی `register()` داشته باشد.
	 *
	 * @param string $key   شناسهٔ یکتای ماژول.
	 * @param string $class نام کامل کلاس.
	 */
	public function module( string $key, string $class ) {
		if ( isset( $this->services[ $key ] ) ) {
			return $this->services[ $key ];
		}

		/**
		 * فعال/غیرفعال کردن یک ماژول.
		 *
		 * @param bool   $enabled
		 * @param string $key
		 */
		if ( ! apply_filters( 'zch/module/enabled', true, $key ) ) {
			return null;
		}

		if ( ! class_exists( $class ) ) {
			return null;
		}

		$object = new $class();

		if ( method_exists( $object, 'register' ) ) {
			$object->register();
		}

		$this->services[ $key ] = $object;

		return $object;
	}

	/**
	 * بازیابی یک سرویس ساخته‌شده.
	 */
	public function get( string $key ) {
		return $this->services[ $key ] ?? null;
	}

	/**
	 * آیا افزونه در حالت دیباگ است؟
	 */
	public static function is_debug(): bool {
		return defined( 'WP_DEBUG' ) && WP_DEBUG;
	}
}
