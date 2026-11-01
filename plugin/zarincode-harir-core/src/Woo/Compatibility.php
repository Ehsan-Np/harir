<?php
/**
 * سازگاری با ووکامرس — HPOS، بلوک‌های سبد/پرداخت، ویرایشگر بلوکی محصول.
 *
 * @package Zarincode\Harir\Woo
 */

namespace Zarincode\Harir\Woo;

defined( 'ABSPATH' ) || exit;

/**
 * اعلام و حفظ سازگاری با قابلیت‌های نوین ووکامرس.
 */
final class Compatibility {

	/**
	 * ثبت هوک‌ها.
	 */
	public function register(): void {
		add_action( 'before_woocommerce_init', [ $this, 'declare_features' ] );
		add_action( 'before_woocommerce_init', [ $this, 'declare_incompatible_on_conflict' ], 20 );

		add_action( 'admin_notices', [ $this, 'maybe_warn_missing_requirements' ] );
		add_filter( 'plugin_row_meta', [ $this, 'plugin_row_meta' ], 10, 2 );

		// شمارهٔ سفارش با پیشوند HR- مطابق طرح HTML.
		add_filter( 'woocommerce_order_number', [ $this, 'order_number' ], 10, 2 );

		// متای نقش در زمان خرید — برای گزارش‌گیری و حسابرسی.
		add_action( 'woocommerce_checkout_create_order', [ $this, 'stamp_order' ], 10, 2 );
		add_action( 'woocommerce_new_order', [ $this, 'stamp_order_by_id' ], 10, 2 );

		// ستون‌های ادمین سفارش (سازگار با HPOS).
		add_action( 'admin_init', [ $this, 'register_order_columns' ] );
	}

	/**
	 * اعلام سازگاری با قابلیت‌های ووکامرس.
	 *
	 * بدون این اعلام، ووکامرس افزونه را «ناسازگار» علامت می‌زند حتی اگر درست کار کند.
	 */
	public function declare_features(): void {
		if ( ! class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			return;
		}

		$features = \Automattic\WooCommerce\Utilities\FeaturesUtil::class;

		$features::declare_compatibility( 'custom_order_tables', ZCH_CORE_FILE, true );
		$features::declare_compatibility( 'cart_checkout_blocks', ZCH_CORE_FILE, true );
		$features::declare_compatibility( 'product_block_editor', ZCH_CORE_FILE, true );

		/**
		 * امکان افزودن اعلام سازگاری بیشتر توسط ماژول‌های دیگر.
		 *
		 * @param string $features نام کلاس FeaturesUtil.
		 */
		do_action( 'zch/woo/declare_features', $features );
	}

	/**
	 * اگر ماژولی واقعاً با HPOS سازگار نبود، اعلام ناسازگاری کند.
	 */
	public function declare_incompatible_on_conflict(): void {
		/**
		 * فیلتر فرار (escape hatch) — اگر ماژولی در زمان اجرا نتوانست HPOS را
		 * پشتیبانی کند، false برگردانید تا مدیر سایت هشدار روشن ببیند.
		 *
		 * @param bool $compatible
		 */
		$compatible = apply_filters( 'zch/woo/hpos_compatible', true );

		if ( $compatible || ! class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			return;
		}

		$features = \Automattic\WooCommerce\Utilities\FeaturesUtil::class;
		$features::declare_compatibility( 'custom_order_tables', ZCH_CORE_FILE, false );
	}

	/**
	 * هشدار در صورت نبود ووکامرس یا نسخهٔ قدیمی.
	 */
	public function maybe_warn_missing_requirements(): void {
		if ( class_exists( \WooCommerce::class ) ) {
			if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, ZCH_CORE_MIN_WC, '<' ) ) {
				printf(
					'<div class="notice notice-warning"><p>%s</p></div>',
					esc_html(
						sprintf(
							/* translators: %s: minimum WooCommerce version */
							__( 'هستهٔ حریر رویا به ووکامرس %s یا بالاتر نیاز دارد. برخی قابلیت‌ها کار نخواهند کرد.', 'zarincode-harir-core' ),
							ZCH_CORE_MIN_WC
						)
					)
				);
			}
			return;
		}

		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html__( 'هستهٔ حریر رویا برای کارکرد کامل به افزونهٔ ووکامرس نیاز دارد. لطفاً ووکامرس را نصب و فعال کنید.', 'zarincode-harir-core' )
		);
	}

	/**
	 * @param string[] $meta
	 * @param string   $file
	 * @return string[]
	 */
	public function plugin_row_meta( array $meta, $file ): array {
		if ( ZCH_CORE_BASENAME !== $file ) {
			return $meta;
		}

		$meta[] = sprintf(
			'<a href="%s" target="_blank" rel="noopener">%s</a>',
			esc_url( 'https://zarincode.com/' ),
			esc_html__( 'زارین‌کد', 'zarincode-harir-core' )
		);

		return $meta;
	}

	/**
	 * پیشوند شمارهٔ سفارش مطابق طرح (`HR-۱۰۴۸`).
	 *
	 * فقط نمایش را تغییر می‌دهد و ID واقعی سفارش دست‌نخورده می‌ماند تا
	 * هیچ افزونهٔ درگاه پرداختی دچار مشکل نشود.
	 *
	 * @param string    $order_number
	 * @param \WC_Order $order
	 */
	public function order_number( $order_number, $order ): string {
		if ( ! $order instanceof \WC_Order ) {
			return (string) $order_number;
		}

		$existing = $order->get_meta( '_zch_order_number' );

		if ( $existing ) {
			return (string) $existing;
		}

		/**
		 * فیلتر پیشوند شمارهٔ سفارش.
		 *
		 * @param string $prefix
		 */
		$prefix = (string) apply_filters( 'zch/order/number_prefix', 'HR-' );

		if ( '' === $prefix ) {
			return (string) $order_number;
		}

		$formatted = $prefix . $order->get_id();

		/**
		 * فیلتر شمارهٔ نمایشی سفارش.
		 *
		 * @param string    $formatted
		 * @param \WC_Order $order
		 */
		return (string) apply_filters( 'zch/order/number', $formatted, $order );
	}

	/**
	 * ثبت نقش کاربر در لحظهٔ خرید روی سفارش.
	 *
	 * برای اینکه بعداً اگر نقش کاربر عوض شد، قیمت‌های تاریخی سفارش تغییر نکند.
	 *
	 * @param \WC_Order $order
	 * @param mixed     $data
	 */
	public function stamp_order( $order, $data = null ): void {
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		if ( $order->get_meta( '_zch_role_at_purchase' ) ) {
			return;
		}

		$user = $order->get_user();

		$order->update_meta_data( '_zch_role_at_purchase', $user instanceof \WP_User ? implode( ',', (array) $user->roles ) : 'guest' );
		$order->update_meta_data( '_zch_is_wholesale', \Zarincode\Harir\Roles\Roles::is_wholesale( $user ) ? 'yes' : 'no' );
		$order->update_meta_data( '_zch_core_version', ZCH_CORE_VERSION );
	}

	/**
	 * @param int    $order_id
	 * @param mixed  $order
	 */
	public function stamp_order_by_id( $order_id, $order = null ): void {
		$object = $order instanceof \WC_Order ? $order : wc_get_order( (int) $order_id );

		if ( $object instanceof \WC_Order ) {
			$this->stamp_order( $object );
			$object->save();
		}
	}

	/**
	 * ثبت ستون‌های ادمین با شناسهٔ پویای صفحه (سازگار با HPOS).
	 */
	public function register_order_columns(): void {
		if ( ! class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) ) {
			add_filter( 'manage_edit-shop_order_columns', [ $this, 'add_columns' ] );
			add_action( 'manage_shop_order_posts_custom_column', [ $this, 'render_column' ], 10, 2 );
			return;
		}

		$order_util = \Automattic\WooCommerce\Utilities\OrderUtil::class;
		$screen     = $order_util::get_order_admin_screen();

		if ( ! $screen ) {
			return;
		}

		add_filter( "manage_{$screen}_columns", [ $this, 'add_columns' ] );
		add_action( "manage_{$screen}_custom_column", [ $this, 'render_column_hpos' ], 10, 3 );
	}

	/**
	 * @param array<string,string> $columns
	 * @return array<string,string>
	 */
	public function add_columns( array $columns ): array {
		$new = [];

		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;

			if ( 'order_date' === $key ) {
				$new['zch_customer_type'] = __( 'نوع خریدار', 'zarincode-harir-core' );
			}
		}

		return $new;
	}

	/**
	 * @param string $column
	 * @param int    $post_id
	 */
	public function render_column( $column, $post_id ): void {
		if ( 'zch_customer_type' !== $column ) {
			return;
		}

		$order = wc_get_order( (int) $post_id );
		echo $this->column_html( $order ); // phpcs:ignore WordPress.Security.EscapeOutput
	}

	/**
	 * @param string $column
	 * @param string $id
	 * @param mixed  $order
	 */
	public function render_column_hpos( $column, $id, $order ): void {
		if ( 'zch_customer_type' !== $column ) {
			return;
		}

		echo $this->column_html( $order ); // phpcs:ignore WordPress.Security.EscapeOutput
	}

	/**
	 * @param mixed $order
	 */
	private function column_html( $order ): string {
		if ( ! $order instanceof \WC_Order ) {
			return '';
		}

		$is_wholesale = 'yes' === $order->get_meta( '_zch_is_wholesale' );

		return sprintf(
			'<span class="zch-order-type zch-order-type--%1$s">%2$s</span>',
			$is_wholesale ? 'wholesale' : 'retail',
			esc_html( $is_wholesale ? __( 'همکار عمده', 'zarincode-harir-core' ) : __( 'مشتری', 'zarincode-harir-core' ) )
		);
	}
}
