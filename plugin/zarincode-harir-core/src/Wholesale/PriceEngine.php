<?php
/**
 * موتور قیمت‌گذاری عمده — قلب تجاری حریر رویا.
 *
 * سه قیمت را برای هر محصول مدیریت می‌کند:
 *   ۱. قیمت بدون تخفیف (خط‌خورده)
 *   ۲. قیمت نهایی مشتری عام
 *   ۳. قیمت همکار عمده
 *
 * قانون قیمت عمده در چهار سطح قابل تنظیم است و زنجیرهٔ تصمیم به این ترتیب است:
 *   وارییشن → محصول → دسته‌بندی (نزدیک‌ترین والد) → پیش‌فرض سراسری
 *
 * @package Zarincode\Harir\Wholesale
 */

namespace Zarincode\Harir\Wholesale;

defined( 'ABSPATH' ) || exit;

use Zarincode\Harir\Roles\Roles;

/**
 * موتور محاسبهٔ قیمت عمده.
 */
final class PriceEngine {

	/** کلید option پیش‌فرض‌های سراسری. */
	const OPTION_DEFAULTS = 'zch_wholesale_defaults';

	/** meta محصول/وارییشن. */
	const META_ENABLED = '_zch_wholesale_enabled';
	const META_MODE    = '_zch_wholesale_mode';
	const META_BASE    = '_zch_wholesale_base';
	const META_VALUE   = '_zch_wholesale_value';
	const META_PRICE   = '_zch_wholesale_price';
	const META_NOTE    = '_zch_wholesale_note';

	/** کش درون‌درخواستی برای جلوگیری از محاسبهٔ تکراری در فهرست‌ها. */
	private $cache = [];

	/**
	 * ثبت هوک‌ها.
	 */
	public function register(): void {
		// جایگزینی قیمت برای نقش همکار.
		add_filter( 'woocommerce_product_get_price', [ $this, 'filter_price' ], 20, 2 );
		add_filter( 'woocommerce_product_get_sale_price', [ $this, 'filter_sale_price' ], 20, 2 );
		add_filter( 'woocommerce_product_variation_get_price', [ $this, 'filter_price' ], 20, 2 );
		add_filter( 'woocommerce_product_variation_get_sale_price', [ $this, 'filter_sale_price' ], 20, 2 );

		// محدودهٔ قیمت در محصولات متغیر.
		add_filter( 'woocommerce_variation_prices', [ $this, 'filter_variation_prices' ], 20, 3 );
		add_filter( 'woocommerce_available_variation', [ $this, 'filter_available_variation' ], 20, 3 );

		// رندر markup قیمت.
		add_filter( 'woocommerce_get_price_html', [ $this, 'filter_price_html' ], 20, 2 );

		// سرچشمهٔ حقیقت برای سبد، پرداخت و همهٔ درگاه‌ها.
		add_action( 'woocommerce_before_calculate_totals', [ $this, 'apply_to_cart' ], 20, 1 );

		// جلوگیری از نشت قیمت عمده به اسکیما و خوراک‌ها.
		add_filter( 'woocommerce_structured_data_product_offer', [ $this, 'protect_structured_data' ], 20, 2 );

		// متای محصول در ادمین.
		add_action( 'woocommerce_product_options_pricing', [ $this, 'render_admin_fields' ] );
		add_action( 'woocommerce_process_product_meta', [ $this, 'save_admin_fields' ], 20, 2 );
		add_action( 'woocommerce_variation_options_pricing', [ $this, 'render_variation_fields' ], 10, 3 );
		add_action( 'woocommerce_save_product_variation', [ $this, 'save_variation_fields' ], 20, 2 );

		// پاک‌سازی کش قیمت محاسبه‌شده هنگام تغییر قیمت پایه.
		add_action( 'woocommerce_process_product_meta', [ $this, 'flush_cached_price' ], 999, 1 );
		add_action( 'woocommerce_save_product_variation', [ $this, 'flush_cached_price' ], 999, 1 );
	}

	/* ---------------------------------------------------------------------
	 * API عمومی
	 * ------------------------------------------------------------------ */

	/**
	 * پیش‌فرض‌های سراسری قیمت عمده.
	 *
	 * پیش‌فرض کارخانه مطابق خواستهٔ کارفرما: ۳٪ تخفیف روی قیمت نهایی مشتری.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		$fallback = [
			'enabled'    => true,
			'mode'       => 'percent',        // percent | fixed | price
			'base'       => 'customer_final', // regular | sale | customer_final
			'value'      => 3,
			'note_tpl'   => 'قیمت این محصول {regular} بوده که برای مشتریان {customer} محاسبه می‌شود و برای شما به‌عنوان همکار، با {percent}٪ تخفیف، {wholesale} ارائه می‌گردد.',
			'show_note'  => true,
			'badge'      => 'قیمت همکاری',
			'round_to'   => 0,                // گرد کردن به مضرب (۰ = خاموش)
		];

		$saved = get_option( self::OPTION_DEFAULTS, [] );

		if ( ! is_array( $saved ) ) {
			$saved = [];
		}

		/**
		 * فیلتر پیش‌فرض‌های سراسری قیمت عمده.
		 *
		 * @param array $defaults
		 */
		return apply_filters( 'zch/wholesale/defaults', array_merge( $fallback, $saved ) );
	}

	/**
	 * یافتن قانون اعمال‌شده برای یک محصول/وارییشن.
	 *
	 * زنجیرهٔ چهارسطحی: وارییشن → محصول → دسته → سراسری.
	 *
	 * @param int      $product_id
	 * @param int|null $variation_id
	 * @return array<string,mixed> شامل کلید `source` برای دیباگ.
	 */
	public function find_rule( $product_id, $variation_id = null ): array {
		$defaults         = self::defaults();
		$defaults['source'] = 'global';

		$candidates = [];

		if ( $variation_id ) {
			$candidates[] = [ 'variation', (int) $variation_id ];
		}
		$candidates[] = [ 'product', (int) $product_id ];

		foreach ( $candidates as $candidate ) {
			list( $level, $id ) = $candidate;

			if ( ! $this->rule_is_defined( $id ) ) {
				continue;
			}

			$rule           = $this->read_rule( $id );
			$rule['source'] = $level;

			return $rule;
		}

		$category_rule = $this->find_category_rule( $product_id );
		if ( null !== $category_rule ) {
			$category_rule['source'] = 'category';
			return $category_rule;
		}

		return $defaults;
	}

	/**
	 * آیا برای این ID قانون عمده‌ای صراحتاً تعریف شده؟
	 *
	 * @param int $object_id product یا variation ID.
	 */
	private function rule_is_defined( $object_id ): bool {
		if ( ! $object_id ) {
			return false;
		}

		$enabled = get_post_meta( $object_id, self::META_ENABLED, true );

		return '' !== $enabled && null !== $enabled && 'inherit' !== $enabled;
	}

	/**
	 * خواندن قانون از meta یک محصول/وارییشن.
	 *
	 * @param int $object_id
	 * @return array<string,mixed>
	 */
	private function read_rule( $object_id ): array {
		return [
			'enabled' => 'yes' === get_post_meta( $object_id, self::META_ENABLED, true )
						|| '1' === (string) get_post_meta( $object_id, self::META_ENABLED, true ),
			'mode'    => $this->sanitize_mode( get_post_meta( $object_id, self::META_MODE, true ) ),
			'base'    => $this->sanitize_base( get_post_meta( $object_id, self::META_BASE, true ) ),
			'value'   => (float) get_post_meta( $object_id, self::META_VALUE, true ),
			'note'    => (string) get_post_meta( $object_id, self::META_NOTE, true ),
		];
	}

	/**
	 * جست‌وجوی قانون در دسته‌بندی‌های محصول (از نزدیک‌ترین والد).
	 *
	 * @param int $product_id
	 * @return array<string,mixed>|null
	 */
	private function find_category_rule( $product_id ): ?array {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return null;
		}

		$terms = get_the_terms( $product_id, 'product_cat' );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return null;
		}

		// ترتیب از عمیق‌ترین دسته به سمت ریشه.
		usort(
			$terms,
			static function ( $a, $b ) {
				$depth_a = count( get_ancestors( $a->term_id, 'product_cat', 'taxonomy' ) );
				$depth_b = count( get_ancestors( $b->term_id, 'product_cat', 'taxonomy' ) );
				return $depth_b <=> $depth_a;
			}
		);

		foreach ( $terms as $term ) {
			$chain = array_merge( [ $term->term_id ], get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) );

			foreach ( $chain as $term_id ) {
				$enabled = get_term_meta( $term_id, self::META_ENABLED, true );

				if ( '' === $enabled || null === $enabled ) {
					continue;
				}

				return [
					'enabled'  => 'yes' === $enabled || '1' === (string) $enabled,
					'mode'     => $this->sanitize_mode( get_term_meta( $term_id, self::META_MODE, true ) ),
					'base'     => $this->sanitize_base( get_term_meta( $term_id, self::META_BASE, true ) ),
					'value'    => (float) get_term_meta( $term_id, self::META_VALUE, true ),
					'note'     => (string) get_term_meta( $term_id, self::META_NOTE, true ),
					'term_id'  => (int) $term_id,
				];
			}
		}

		return null;
	}

	/**
	 * محاسبهٔ کامل قیمت‌های یک محصول.
	 *
	 * @param int      $product_id
	 * @param int|null $variation_id
	 */
	public function resolve( $product_id, $variation_id = null ): PriceResult {
		$cache_key = $variation_id ? $variation_id : $product_id;

		if ( isset( $this->cache[ $cache_key ] ) ) {
			return $this->cache[ $cache_key ];
		}

		$product = wc_get_product( $variation_id ? $variation_id : $product_id );

		if ( ! $product instanceof \WC_Product ) {
			return PriceResult::not_available();
		}

		$regular = (float) $product->get_regular_price();
		$sale    = (float) $product->get_sale_price();

		if ( $regular <= 0 ) {
			return PriceResult::not_available();
		}

		$customer = $sale > 0 ? $sale : $regular;
		$rule     = $this->find_rule( $product_id, $variation_id );

		if ( empty( $rule['enabled'] ) ) {
			$result            = new PriceResult(
				[
					'regular'   => $regular,
					'customer'  => $customer,
					'wholesale' => $customer,
					'available' => false,
					'rule'      => $rule,
				]
			);
			$this->cache[ $cache_key ] = $result;
			return $result;
		}

		$base = $this->resolve_base_price( $rule, $regular, $sale, $customer );
		$raw  = $this->apply_mode( $rule, $base );

		$decimals  = function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : 0;
		$wholesale = round( max( 0, $raw ), $decimals );

		$round_to = (float) self::defaults()['round_to'];
		if ( $round_to > 0 && $wholesale > 0 ) {
			$wholesale = (float) ( floor( $wholesale / $round_to ) * $round_to );
		}

		// قیمت عمده هرگز نباید از قیمت مشتری بیشتر شود.
		$wholesale = min( $wholesale, $customer );

		$percent = $customer > 0 ? (int) round( ( $customer - $wholesale ) / $customer * 100 ) : 0;
		$percent_of_regular = $regular > 0 ? (int) round( ( $regular - $wholesale ) / $regular * 100 ) : 0;

		$result = new PriceResult(
			[
				'regular'            => $regular,
				'customer'           => $customer,
				'wholesale'          => $wholesale,
				'saved'              => round( $customer - $wholesale, $decimals ),
				'percent'            => $percent,
				'percent_of_regular' => $percent_of_regular,
				'available'          => true,
				'rule'               => $rule,
			]
		);

		$this->cache[ $cache_key ] = $result;

		return $result;
	}

	/**
	 * قیمت عمدهٔ نهایی برای یک کاربر مشخص.
	 *
	 * اگر کاربر اجازهٔ دیدن قیمت عمده نداشته باشد، قیمت عمومی برمی‌گردد.
	 *
	 * @param int                   $product_id
	 * @param int|null              $variation_id
	 * @param int|\WP_User|null     $user
	 */
	public function price_for_user( $product_id, $variation_id = null, $user = null ): float {
		$result = $this->resolve( $product_id, $variation_id );

		if ( ! $result->available || ! Roles::can_view_wholesale_price( $user ) ) {
			return $result->customer > 0 ? $result->customer : $result->regular;
		}

		return $result->wholesale;
	}

	/**
	 * متن توضیحی تخفیف همکار (بر اساس الگوی قابل ویرایش).
	 *
	 * @param PriceResult $result
	 */
	public function discount_note( PriceResult $result ): string {
		$defaults = self::defaults();

		if ( ! $result->available || ! $result->has_discount() ) {
			return '';
		}

		$template = ! empty( $result->rule['note'] ) ? $result->rule['note'] : $defaults['note_tpl'];

		$note = strtr(
			(string) $template,
			[
				'{regular}'   => $this->format_money( $result->regular ),
				'{customer}'  => $this->format_money( $result->customer ),
				'{wholesale}' => $this->format_money( $result->wholesale ),
				'{saved}'     => $this->format_money( $result->saved ),
				'{percent}'   => $this->format_number( $result->percent ),
			]
		);

		/**
		 * فیلتر متن توضیحی تخفیف همکار.
		 *
		 * @param string      $note
		 * @param PriceResult $result
		 */
		return (string) apply_filters( 'zch/wholesale/discount_note', $note, $result );
	}

	/**
	 * قالب‌بندی مبلغ با جداکنندهٔ هزارگان و ارقام مطابق تنظیمات ووکامرس.
	 *
	 * @param float $amount
	 */
	public function format_money( $amount ): string {
		$suffix = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '';
		$html   = function_exists( 'wc_price' )
			? wp_strip_all_tags( wc_price( $amount ) )
			: number_format( (float) $amount );

		return trim( $html . ( $suffix && false === strpos( $html, $suffix ) ? ' ' . $suffix : '' ) );
	}

	/**
	 * @param float|int $number
	 */
	public function format_number( $number ): string {
		$decimals = function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : 0;

		return (string) ( 0 === (int) $decimals ? number_format( (float) $number ) : number_format( (float) $number, $decimals ) );
	}

	/* ---------------------------------------------------------------------
	 * منطق محاسبه
	 * ------------------------------------------------------------------ */

	/**
	 * @param array<string,mixed> $rule
	 * @param float               $regular
	 * @param float               $sale
	 * @param float               $customer
	 */
	private function resolve_base_price( array $rule, $regular, $sale, $customer ): float {
		switch ( $rule['base'] ) {
			case 'regular':
				return (float) $regular;
			case 'sale':
				return $sale > 0 ? (float) $sale : (float) $regular;
			case 'customer_final':
			default:
				return (float) $customer;
		}
	}

	/**
	 * @param array<string,mixed> $rule
	 * @param float               $base
	 */
	private function apply_mode( array $rule, $base ): float {
		$value = (float) $rule['value'];

		switch ( $rule['mode'] ) {
			case 'fixed':
				return $base - $value;

			case 'price':
				// مبلغ مطلق — مستقل از پایه.
				return $value;

			case 'percent':
			default:
				return $base - ( $base * $value / 100 );
		}
	}

	private function sanitize_mode( $mode ): string {
		$mode = (string) $mode;
		return in_array( $mode, [ 'percent', 'fixed', 'price' ], true ) ? $mode : 'percent';
	}

	private function sanitize_base( $base ): string {
		$base = (string) $base;
		return in_array( $base, [ 'regular', 'sale', 'customer_final' ], true ) ? $base : 'customer_final';
	}

	/* ---------------------------------------------------------------------
	 * هوک‌های ووکامرس
	 * ------------------------------------------------------------------ */

	/**
	 * @param mixed                $price
	 * @param \WC_Product          $product
	 * @return mixed
	 */
	public function filter_price( $price, $product ) {
		if ( ! $this->should_apply() || ! $product instanceof \WC_Product ) {
			return $price;
		}

		$parent_id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
		$var_id    = $product->is_type( 'variation' ) ? $product->get_id() : null;

		$wholesale = $this->resolve( $parent_id, $var_id )->wholesale;

		return $wholesale > 0 ? $wholesale : $price;
	}

	/**
	 * برای نقش همکار، «قیمت حراج» را همان قیمت عمده قرار می‌دهیم تا
	 * ووکامرس آن را به‌درستی در `before_calculate_totals` و درگاه‌ها ببیند.
	 *
	 * @param mixed       $sale_price
	 * @param \WC_Product $product
	 * @return mixed
	 */
	public function filter_sale_price( $sale_price, $product ) {
		if ( ! $this->should_apply() || ! $product instanceof \WC_Product ) {
			return $sale_price;
		}

		$result = $this->filter_price( $product->get_price(), $product );

		return is_numeric( $result ) ? $result : $sale_price;
	}

	/**
	 * @param array<string,mixed> $prices
	 * @param \WC_Product         $product
	 * @param bool                $for_display
	 * @return array<string,mixed>
	 */
	public function filter_variation_prices( array $prices, $product, $for_display = false ): array {
		if ( ! $this->should_apply() || ! $product instanceof \WC_Product ) {
			return $prices;
		}

		foreach ( [ 'min_price', 'max_price', 'min_variation_price', 'max_variation_price', 'min_variation_regular_price', 'max_variation_regular_price' ] as $key ) {
			if ( isset( $prices[ $key ] ) && is_numeric( $prices[ $key ] ) && $prices[ $key ] > 0 ) {
				$prices[ $key ] = $this->price_for_user( $product->get_id(), null );
			}
		}

		if ( isset( $prices['variation_prices'] ) && is_array( $prices['variation_prices'] ) ) {
			foreach ( $prices['variation_prices'] as $variation_id => $variation_price ) {
				$resolved = $this->resolve( $product->get_id(), (int) $variation_id );
				if ( $resolved->available ) {
					$prices['variation_prices'][ $variation_id ] = $resolved->wholesale;
				}
			}
		}

		return $prices;
	}

	/**
	 * @param array<string,mixed> $data
	 * @param \WC_Product         $product
	 * @param \WC_Product         $variation
	 * @return array<string,mixed>
	 */
	public function filter_available_variation( array $data, $product, $variation ): array {
		if ( ! $this->should_apply() || ! $variation instanceof \WC_Product ) {
			return $data;
		}

		$result = $this->resolve( $product->get_id(), $variation->get_id() );

		if ( $result->available ) {
			$data['display_price']         = $result->wholesale;
			$data['display_regular_price'] = $result->regular;
			$data['zch_wholesale']         = $result->to_array();
		}

		return $data;
	}

	/**
	 * رندر markup قیمت مطابق طرح HTML: قیمت خط‌خورده + قیمت نهایی + برچسب + توضیح.
	 *
	 * @param string      $price_html
	 * @param \WC_Product $product
	 */
	public function filter_price_html( $price_html, $product ): string {
		if ( ! $product instanceof \WC_Product || ! $this->should_apply() ) {
			return (string) $price_html;
		}

		$parent_id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
		$var_id    = $product->is_type( 'variation' ) ? $product->get_id() : null;
		$result    = $this->resolve( $parent_id, $var_id );
		$defaults  = self::defaults();

		if ( ! $result->available || ! $result->has_discount() ) {
			return (string) $price_html;
		}

		$parts = [];

		// قیمت بدون تخفیف، خط‌خورده.
		if ( $result->regular > $result->wholesale ) {
			$parts[] = sprintf(
				'<del class="zch-price-regular" aria-hidden="true">%s</del>',
				wc_price( $result->regular ) // phpcs:ignore WordPress.Security.EscapeOutput -- wc_price خروجی امن می‌سازد.
			);
		}

		$parts[] = sprintf(
			'<ins class="zch-price-wholesale">%s</ins>',
			wc_price( $result->wholesale ) // phpcs:ignore WordPress.Security.EscapeOutput
		);

		if ( ! empty( $defaults['badge'] ) ) {
			$parts[] = sprintf(
				'<span class="zch-price-badge">%s</span>',
				esc_html( $defaults['badge'] )
			);
		}

		if ( ! empty( $defaults['show_note'] ) ) {
			$note = $this->discount_note( $result );
			if ( '' !== $note ) {
				$parts[] = sprintf( '<p class="zch-price-note">%s</p>', esc_html( $note ) );
			}
		}

		$html = sprintf(
			'<span class="zch-price zch-price--wholesale" data-regular="%1$s" data-customer="%2$s" data-wholesale="%3$s" data-percent="%4$s">%5$s</span>',
			esc_attr( (string) $result->regular ),
			esc_attr( (string) $result->customer ),
			esc_attr( (string) $result->wholesale ),
			esc_attr( (string) $result->percent ),
			implode( '', $parts ) // phpcs:ignore WordPress.Security.EscapeOutput -- اجزا بالا escape شده‌اند.
		);

		/**
		 * فیلتر markup نهایی قیمت عمده.
		 *
		 * @param string      $html
		 * @param PriceResult $result
		 * @param \WC_Product $product
		 */
		return (string) apply_filters( 'zch/wholesale/price_html', $html, $result, $product );
	}

	/**
	 * اعمال قیمت عمده روی آیتم‌های سبد.
	 *
	 * این همان نقطه‌ای است که همهٔ درگاه‌های پرداخت ووکامرس مبلغ نهایی را از آن
	 * می‌خوانند؛ پس نیازی به تغییر هیچ درگاهی نیست.
	 *
	 * @param \WC_Cart $cart
	 */
	public function apply_to_cart( $cart ): void {
		if ( ! $cart instanceof \WC_Cart || ! $this->should_apply() || is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		foreach ( $cart->get_cart() as $item ) {
			if ( empty( $item['data'] ) || ! $item['data'] instanceof \WC_Product ) {
				continue;
			}

			$product   = $item['data'];
			$parent_id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
			$var_id    = $product->is_type( 'variation' ) ? $product->get_id() : null;
			$result    = $this->resolve( $parent_id, $var_id );

			if ( ! $result->available || $result->wholesale <= 0 ) {
				continue;
			}

			$product->set_price( $result->wholesale );
		}
	}

	/**
	 * جلوگیری از نشت قیمت عمده به دادهٔ ساختاریافته (Schema.org).
	 *
	 * خزنده‌ها باید همیشه قیمت عمومی را ببینند؛ هم برای امنیت تجاری و هم
	 * برای جلوگیری از مغایرت قیمت در Google Merchant.
	 *
	 * @param array<string,mixed> $offer
	 * @param mixed               $product
	 * @return array<string,mixed>
	 */
	public function protect_structured_data( array $offer, $product ): array {
		if ( ! $product instanceof \WC_Product ) {
			return $offer;
		}

		$result = $this->resolve( $product->get_id() );

		if ( $result->customer > 0 ) {
			$offer['price'] = wc_format_decimal( $result->customer );
		}

		return $offer;
	}

	/**
	 * آیا اکنون باید قیمت عمده اعمال شود؟
	 */
	private function should_apply(): bool {
		if ( ! Roles::can_view_wholesale_price() ) {
			return false;
		}

		/**
		 * خاموش/روشن کردن کلی موتور قیمت در یک درخواست.
		 *
		 * @param bool $apply
		 */
		return (bool) apply_filters( 'zch/wholesale/should_apply', true );
	}

	/* ---------------------------------------------------------------------
	 * رابط ادمین
	 * ------------------------------------------------------------------ */

	/**
	 * چاپ فیلدهای قیمت عمده در متاباکس محصول.
	 */
	public function render_admin_fields(): void {
		$this->render_fields_for( 0 );
	}

	/**
	 * @param int      $loop
	 * @param array    $variation_data
	 * @param \WP_Post $variation
	 */
	public function render_variation_fields( $loop, $variation_data, $variation ): void {
		$this->render_fields_for( (int) $variation->ID, 'variation' );
	}

	/**
	 * @param int    $object_id
	 * @param string $context 'product' | 'variation'
	 */
	private function render_fields_for( $object_id, $context = 'product' ): void {
		$defaults = self::defaults();
		$name     = 'variation' === $context ? "variable_wholesale_{$context}" : 'zch_wholesale';

		$enabled = get_post_meta( $object_id, self::META_ENABLED, true );
		$mode    = get_post_meta( $object_id, self::META_MODE, true );
		$base    = get_post_meta( $object_id, self::META_BASE, true );
		$value   = get_post_meta( $object_id, self::META_VALUE, true );
		$note    = get_post_meta( $object_id, self::META_NOTE, true );

		$enabled = '' === $enabled ? 'inherit' : $enabled;
		$mode    = '' === $mode ? $defaults['mode'] : $mode;
		$base    = '' === $base ? $defaults['base'] : $base;
		$value   = '' === $value ? $defaults['value'] : $value;

		wp_nonce_field( 'zch_wholesale_save', 'zch_wholesale_nonce' );

		echo '<div class="options_group zch-wholesale-fields">';
		echo '<p class="form-row form-row-full"><strong>' . esc_html__( 'قیمت‌گذاری همکار عمده', 'zarincode-harir-core' ) . '</strong></p>';

		woocommerce_wp_select(
			[
				'id'          => $name . '_enabled',
				'label'       => __( 'وضعیت', 'zarincode-harir-core' ),
				'value'       => $enabled,
				'options'     => [
					'inherit' => __( 'پیروی از دسته/تنظیمات سراسری', 'zarincode-harir-core' ),
					'yes'     => __( 'فعال — قانون اختصاصی', 'zarincode-harir-core' ),
					'no'      => __( 'غیرفعال برای این محصول', 'zarincode-harir-core' ),
				],
				'desc_tip'    => true,
				'description' => __( 'اگر «پیروی» را انتخاب کنید، قانون دسته‌بندی و سپس تنظیمات سراسری اعمال می‌شود.', 'zarincode-harir-core' ),
			]
		);

		woocommerce_wp_select(
			[
				'id'      => $name . '_base',
				'label'   => __( 'مبنای محاسبه', 'zarincode-harir-core' ),
				'value'   => $base,
				'options' => [
					'customer_final' => __( 'قیمت نهایی مشتری', 'zarincode-harir-core' ),
					'regular'        => __( 'قیمت بدون تخفیف', 'zarincode-harir-core' ),
					'sale'           => __( 'قیمت حراج (در صورت وجود)', 'zarincode-harir-core' ),
				],
			]
		);

		woocommerce_wp_select(
			[
				'id'      => $name . '_mode',
				'label'   => __( 'نوع تخفیف همکار', 'zarincode-harir-core' ),
				'value'   => $mode,
				'options' => [
					'percent' => __( 'درصدی از مبنای محاسبه', 'zarincode-harir-core' ),
					'fixed'   => __( 'مبلغ ثابت کسر شود', 'zarincode-harir-core' ),
					'price'   => __( 'قیمت مطلق همکار', 'zarincode-harir-core' ),
				],
			]
		);

		woocommerce_wp_text_input(
			[
				'id'                => $name . '_value',
				'label'             => __( 'مقدار', 'zarincode-harir-core' ),
				'value'             => $value,
				'data_type'         => 'price',
				'desc_tip'          => true,
				'description'       => __( 'برای حالت درصدی: عدد درصد. برای مبلغ ثابت: مبلغ کسر. برای قیمت مطلق: قیمت نهایی همکار.', 'zarincode-harir-core' ),
			]
		);

		woocommerce_wp_textarea_input(
			[
				'id'          => $name . '_note',
				'label'       => __( 'توضیح کنار قیمت', 'zarincode-harir-core' ),
				'value'       => $note,
				'placeholder' => $defaults['note_tpl'],
				'desc_tip'    => true,
				'description' => __( 'متغیرها: {regular} {customer} {wholesale} {saved} {percent}. خالی بگذارید تا الگوی سراسری استفاده شود.', 'zarincode-harir-core' ),
			]
		);

		if ( $object_id ) {
			$result = $this->resolve( $object_id, 'variation' === $context ? $object_id : null );
			printf(
				'<p class="description zch-price-preview">%s <strong>%s</strong></p>',
				esc_html__( 'پیش‌نمایش قیمت همکار:', 'zarincode-harir-core' ),
				esc_html( $result->available ? $this->format_money( $result->wholesale ) : '—' )
			);
		}

		echo '</div>';
	}

	/**
	 * ذخیرهٔ فیلدهای محصول.
	 *
	 * @param int $post_id
	 */
	public function save_admin_fields( $post_id ): void {
		$this->save_fields_for( (int) $post_id, 'zch_wholesale' );
	}

	/**
	 * @param int $variation_id
	 * @param int $loop
	 */
	public function save_variation_fields( $variation_id, $loop ): void {
		$this->save_fields_for( (int) $variation_id, 'variable_wholesale_variation' );
	}

	/**
	 * @param int    $object_id
	 * @param string $prefix
	 */
	private function save_fields_for( $object_id, $prefix ): void {
		if ( ! $object_id ) {
			return;
		}

		if ( ! isset( $_POST['zch_wholesale_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['zch_wholesale_nonce'] ) ), 'zch_wholesale_save' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_product', $object_id ) ) {
			return;
		}

		$enabled = isset( $_POST[ $prefix . '_enabled' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $prefix . '_enabled' ] ) ) : 'inherit';
		$enabled = in_array( $enabled, [ 'inherit', 'yes', 'no' ], true ) ? $enabled : 'inherit';

		update_post_meta( $object_id, self::META_ENABLED, $enabled );

		if ( 'inherit' === $enabled ) {
			delete_post_meta( $object_id, self::META_MODE );
			delete_post_meta( $object_id, self::META_BASE );
			delete_post_meta( $object_id, self::META_VALUE );
			return;
		}

		$base  = isset( $_POST[ $prefix . '_base' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $prefix . '_base' ] ) ) : 'customer_final';
		$mode  = isset( $_POST[ $prefix . '_mode' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $prefix . '_mode' ] ) ) : 'percent';
		$value = isset( $_POST[ $prefix . '_value' ] ) ? wc_format_decimal( wp_unslash( $_POST[ $prefix . '_value' ] ) ) : 0; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$note  = isset( $_POST[ $prefix . '_note' ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $prefix . '_note' ] ) ) : '';

		update_post_meta( $object_id, self::META_BASE, $this->sanitize_base( $base ) );
		update_post_meta( $object_id, self::META_MODE, $this->sanitize_mode( $mode ) );
		update_post_meta( $object_id, self::META_VALUE, (float) $value );
		update_post_meta( $object_id, self::META_NOTE, $note );
	}

	/**
	 * پاک‌سازی قیمت کش‌شده پس از ذخیرهٔ محصول.
	 *
	 * @param int $object_id
	 */
	public function flush_cached_price( $object_id ): void {
		delete_post_meta( (int) $object_id, self::META_PRICE );
		$this->cache = [];
	}
}
