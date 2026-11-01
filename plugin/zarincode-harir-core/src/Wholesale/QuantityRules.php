<?php
/**
 * قوانین حداقل، حداکثر و گام تعداد — به‌تفکیک نقش کاربری.
 *
 * برخلاف افزونه‌های رایج که یک قانون برای همهٔ مشتریان دارند، اینجا قانون
 * per نقش است و در سه سطح قابل تنظیم:
 *   ۱. سراسری per نقش
 *   ۲. دسته‌بندی per نقش
 *   ۳. محصول/وارییشن per نقش
 *
 * نمونهٔ خواستهٔ کارفرما: min=6, step=6 → کاربر همکار ۶ → ۱۲ → ۱۸ می‌خرد.
 *
 * @package Zarincode\Harir\Wholesale
 */

namespace Zarincode\Harir\Wholesale;

defined( 'ABSPATH' ) || exit;

use Zarincode\Harir\Roles\Roles;

/**
 * موتور قوانین تعداد.
 */
final class QuantityRules {

	const OPTION_DEFAULTS = 'zch_quantity_defaults';

	const META_RULES = '_zch_qty_rules';

	/** نقش کلیدی برای کاربر مهمان. */
	const GUEST = 'guest';

	/**
	 * ثبت هوک‌ها.
	 */
	public function register(): void {
		// رندر ورودی تعداد با min/max/step صحیح.
		add_filter( 'woocommerce_quantity_input_args', [ $this, 'input_args' ], 20, 2 );

		// اعتبارسنجی افزودن به سبد.
		add_filter( 'woocommerce_add_to_cart_validation', [ $this, 'validate_add_to_cart' ], 20, 6 );

		// اصلاح مقدار در سبد پس از تغییر.
		add_action( 'woocommerce_after_cart_item_quantity_update', [ $this, 'fix_cart_quantity' ], 20, 4 );
		add_filter( 'woocommerce_cart_item_quantity', [ $this, 'cart_item_quantity_html' ], 20, 3 );

		// اصلاح مقدار در REST/Block Checkout.
		add_filter( 'woocommerce_rest_cart_item_quantity', [ $this, 'rest_quantity' ], 20, 3 );

		// رابط ادمین.
		add_action( 'woocommerce_product_options_inventory_product_data', [ $this, 'render_admin_fields' ] );
		add_action( 'woocommerce_process_product_meta', [ $this, 'save_admin_fields' ], 30, 2 );

		// در دسترس بودن قوانین برای JS فرانت.
		add_filter( 'woocommerce_available_variation', [ $this, 'expose_to_js' ], 30, 3 );
	}

	/* ---------------------------------------------------------------------
	 * پیش‌فرض‌ها
	 * ------------------------------------------------------------------ */

	/**
	 * پیش‌فرض‌های سراسری به‌تفکیک نقش.
	 *
	 * @return array<string,array<string,int>>
	 */
	public static function defaults(): array {
		$fallback = [
			'rounding' => 'down', // down | up | nearest
			'roles'    => [
				self::GUEST          => [ 'min' => 1, 'max' => 0, 'step' => 1 ],
				Roles::CUSTOMER      => [ 'min' => 1, 'max' => 0, 'step' => 1 ],
				Roles::WHOLESALE     => [ 'min' => 6, 'max' => 0, 'step' => 6 ],
			],
		];

		$saved = get_option( self::OPTION_DEFAULTS, [] );

		if ( ! is_array( $saved ) ) {
			$saved = [];
		}

		$merged = array_replace_recursive( $fallback, $saved );

		/**
		 * فیلتر پیش‌فرض‌های سراسری قوانین تعداد.
		 *
		 * @param array $merged
		 */
		return apply_filters( 'zch/quantity/defaults', $merged );
	}

	/* ---------------------------------------------------------------------
	 * API عمومی
	 * ------------------------------------------------------------------ */

	/**
	 * قوانین اعمال‌شده برای یک محصول و یک نقش.
	 *
	 * @param int         $product_id
	 * @param string|null $role        در صورت null، نقش کاربر جاری.
	 * @return array{min:int,max:int,step:int,source:string}
	 */
	public function for_product( $product_id, $role = null ): array {
		$role = $this->normalize_role( $role );

		// سطح ۳: محصول.
		$product_rule = $this->read_meta_rule( (int) $product_id, $role );
		if ( null !== $product_rule ) {
			$product_rule['source'] = 'product';
			return $this->sanitize_rule( $product_rule );
		}

		// سطح ۲: دسته‌بندی (از نزدیک‌ترین والد).
		$category_rule = $this->read_category_rule( (int) $product_id, $role );
		if ( null !== $category_rule ) {
			$category_rule['source'] = 'category';
			return $this->sanitize_rule( $category_rule );
		}

		// سطح ۱: سراسری.
		$defaults = self::defaults();
		$global   = isset( $defaults['roles'][ $role ] ) ? $defaults['roles'][ $role ] : $defaults['roles'][ self::GUEST ];

		return $this->sanitize_rule( array_merge( $global, [ 'source' => 'global' ] ) );
	}

	/**
	 * قوانین برای کاربر جاری (راحت‌ترین نقطهٔ ورود برای قالب و ویجت‌ها).
	 *
	 * @param int $product_id
	 * @return array{min:int,max:int,step:int,source:string}
	 */
	public function for_current_user( $product_id ): array {
		return $this->for_product( $product_id );
	}

	/**
	 * گرد کردن یک مقدار دلخواه به نزدیک‌ترین مقدار مجاز.
	 *
	 * @param int         $product_id
	 * @param int         $quantity
	 * @param string|null $role
	 */
	public function normalize( $product_id, $quantity, $role = null ): int {
		$rule     = $this->for_product( $product_id, $role );
		$quantity = max( 0, (int) $quantity );

		if ( 0 === $quantity ) {
			return 0;
		}

		$step = max( 1, (int) $rule['step'] );
		$min  = max( 1, (int) $rule['min'] );
		$max  = (int) $rule['max'];

		if ( $quantity < $min ) {
			$quantity = $min;
		}

		if ( $step > 1 ) {
			$rounding = self::defaults()['rounding'];

			switch ( $rounding ) {
				case 'up':
					$quantity = (int) ( ceil( $quantity / $step ) * $step );
					break;
				case 'nearest':
					$quantity = (int) ( round( $quantity / $step ) * $step );
					break;
				case 'down':
				default:
					$quantity = (int) ( floor( $quantity / $step ) * $step );
					break;
			}

			// اگر گرد‌کردن باعث شد زیر حداقل بیفتیم، به حداقل برش می‌گردانیم.
			if ( $quantity < $min ) {
				$quantity = (int) ( ceil( $min / $step ) * $step );
			}
		}

		if ( $max > 0 && $quantity > $max ) {
			$quantity = $step > 1 ? (int) ( floor( $max / $step ) * $step ) : $max;
			$quantity = max( $quantity, $min );
		}

		/**
		 * فیلتر مقدار نهایی گردشده.
		 *
		 * @param int   $quantity
		 * @param int   $product_id
		 * @param array $rule
		 */
		return (int) apply_filters( 'zch/quantity/normalized', $quantity, (int) $product_id, $rule );
	}

	/**
	 * بررسی اعتبار یک مقدار و برگرداندن پیام خطا.
	 *
	 * @param int         $product_id
	 * @param int         $quantity
	 * @param string|null $role
	 * @return true|\WP_Error
	 */
	public function validate( $product_id, $quantity, $role = null ) {
		$rule     = $this->for_product( $product_id, $role );
		$quantity = (int) $quantity;
		$product  = wc_get_product( $product_id );
		$name     = $product instanceof \WC_Product ? $product->get_name() : '';

		if ( $quantity < (int) $rule['min'] ) {
			return new \WP_Error(
				'zch_qty_min',
				sprintf(
					/* translators: 1: product name, 2: minimum quantity */
					__( 'حداقل تعداد قابل سفارش برای «%1$s» برابر %2$s عدد است.', 'zarincode-harir-core' ),
					$name,
					(int) $rule['min']
				)
			);
		}

		if ( (int) $rule['max'] > 0 && $quantity > (int) $rule['max'] ) {
			return new \WP_Error(
				'zch_qty_max',
				sprintf(
					/* translators: 1: product name, 2: maximum quantity */
					__( 'حداکثر تعداد قابل سفارش برای «%1$s» برابر %2$s عدد است.', 'zarincode-harir-core' ),
					$name,
					(int) $rule['max']
				)
			);
		}

		$step = max( 1, (int) $rule['step'] );
		if ( $step > 1 && 0 !== $quantity % $step ) {
			return new \WP_Error(
				'zch_qty_step',
				sprintf(
					/* translators: 1: product name, 2: step quantity */
					__( 'تعداد «%1$s» باید مضربی از %2$s باشد.', 'zarincode-harir-core' ),
					$name,
					$step
				)
			);
		}

		return true;
	}

	/* ---------------------------------------------------------------------
	 * هوک‌های ووکامرس
	 * ------------------------------------------------------------------ */

	/**
	 * @param array<string,mixed> $args
	 * @param \WC_Product|null    $product
	 * @return array<string,mixed>
	 */
	public function input_args( array $args, $product = null ): array {
		if ( ! $product instanceof \WC_Product ) {
			return $args;
		}

		$rule = $this->for_product( $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id() );

		$args['min_value'] = (int) $rule['min'];
		$args['step']      = max( 1, (int) $rule['step'] );

		if ( (int) $rule['max'] > 0 ) {
			$args['max_value'] = min( (int) $rule['max'], isset( $args['max_value'] ) && $args['max_value'] > 0 ? (int) $args['max_value'] : (int) $rule['max'] );
		}

		// مقدار ورودی باید از قبل معتبر باشد.
		if ( isset( $args['input_value'] ) ) {
			$product_id   = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
			$args['input_value'] = $this->normalize( $product_id, (int) $args['input_value'] );
		}

		$args['classes'] = trim( ( isset( $args['classes'] ) ? $args['classes'] : '' ) . ' zch-qty-input' );

		return $args;
	}

	/**
	 * @param bool         $passed
	 * @param int          $product_id
	 * @param int          $quantity
	 * @param int          $variation_id
	 * @param array|null   $variations
	 * @param array|null   $cart_item_data
	 * @return bool
	 */
	public function validate_add_to_cart( $passed, $product_id, $quantity, $variation_id = 0, $variations = null, $cart_item_data = null ): bool {
		$target = $variation_id ? (int) $variation_id : (int) $product_id;

		$result = $this->validate( (int) $product_id, (int) $quantity );

		if ( is_wp_error( $result ) ) {
			wc_add_notice( $result->get_error_message(), 'error' );

			/**
			 * هنگام رد شدن یک افزودن به سبد به دلیل قوانین تعداد.
			 *
			 * @param int       $target
			 * @param int       $quantity
			 * @param \WP_Error $result
			 */
			do_action( 'zch/quantity/rejected', $target, (int) $quantity, $result );

			return false;
		}

		return (bool) $passed;
	}

	/**
	 * @param \WC_Cart $cart
	 * @param string   $cart_item_key
	 * @param int      $quantity
	 * @param int      $old_quantity
	 */
	public function fix_cart_quantity( $cart, $cart_item_key, $quantity, $old_quantity ): void {
		if ( ! $cart instanceof \WC_Cart ) {
			return;
		}

		$items = $cart->get_cart();

		if ( ! isset( $items[ $cart_item_key ] ) ) {
			return;
		}

		$item       = $items[ $cart_item_key ];
		$product_id = isset( $item['product_id'] ) ? (int) $item['product_id'] : 0;
		$normalized = $this->normalize( $product_id, (int) $quantity );

		if ( $normalized === (int) $quantity || $normalized < 1 ) {
			return;
		}

		$cart->set_quantity( $cart_item_key, $normalized, false );

		wc_add_notice(
			sprintf(
				/* translators: %d: corrected quantity */
				__( 'تعداد سفارش بر اساس قوانین خرید به %d عدد اصلاح شد.', 'zarincode-harir-core' ),
				$normalized
			),
			'notice'
		);
	}

	/**
	 * @param string $quantity_html
	 * @param string $cart_item_key
	 * @param array  $cart_item
	 * @return string
	 */
	public function cart_item_quantity_html( $quantity_html, $cart_item_key, $cart_item ): string {
		if ( empty( $cart_item['data'] ) || ! $cart_item['data'] instanceof \WC_Product ) {
			return (string) $quantity_html;
		}

		$product = $cart_item['data'];
		$args    = [
			'input_value' => isset( $cart_item['quantity'] ) ? (int) $cart_item['quantity'] : 1,
			'min_value'   => 1,
			'max_value'   => 0,
		];

		$args = $this->input_args( $args, $product );

		$rendered = woocommerce_quantity_input(
			array_merge(
				$args,
				[
					'input_name'  => "cart[{$cart_item_key}][qty]",
					'placeholder' => (string) $args['min_value'],
				]
			),
			$product,
			false
		);

		return '' !== trim( (string) $rendered ) ? (string) $rendered : (string) $quantity_html;
	}

	/**
	 * @param mixed $quantity
	 * @param mixed $key
	 * @param array $item
	 * @return mixed
	 */
	public function rest_quantity( $quantity, $key, $item ) {
		if ( empty( $item['product_id'] ) ) {
			return $quantity;
		}

		return $this->normalize( (int) $item['product_id'], (int) $quantity );
	}

	/**
	 * @param array<string,mixed> $data
	 * @param \WC_Product         $product
	 * @param \WC_Product         $variation
	 * @return array<string,mixed>
	 */
	public function expose_to_js( array $data, $product, $variation ): array {
		if ( ! $variation instanceof \WC_Product ) {
			return $data;
		}

		$data['zch_quantity_rule'] = $this->for_product( $product->get_id() );

		return $data;
	}

	/* ---------------------------------------------------------------------
	 * خواندن قواعد
	 * ------------------------------------------------------------------ */

	/**
	 * @param int    $object_id
	 * @param string $role
	 * @return array<string,int>|null
	 */
	private function read_meta_rule( $object_id, $role ): ?array {
		if ( ! $object_id ) {
			return null;
		}

		$rules = get_post_meta( $object_id, self::META_RULES, true );

		if ( ! is_array( $rules ) || empty( $rules ) ) {
			return null;
		}

		// جست‌وجوی دقیق نقش، سپس نقش «همه».
		if ( isset( $rules[ $role ] ) && is_array( $rules[ $role ] ) ) {
			return $rules[ $role ];
		}

		if ( isset( $rules['all'] ) && is_array( $rules['all'] ) ) {
			return $rules['all'];
		}

		return null;
	}

	/**
	 * @param int    $product_id
	 * @param string $role
	 * @return array<string,int>|null
	 */
	private function read_category_rule( $product_id, $role ): ?array {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return null;
		}

		$terms = get_the_terms( $product_id, 'product_cat' );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return null;
		}

		foreach ( $terms as $term ) {
			$chain = array_merge( [ $term->term_id ], get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) );

			foreach ( $chain as $term_id ) {
				$rules = get_term_meta( (int) $term_id, self::META_RULES, true );

				if ( ! is_array( $rules ) ) {
					continue;
				}

				if ( isset( $rules[ $role ] ) && is_array( $rules[ $role ] ) ) {
					return $rules[ $role ];
				}

				if ( isset( $rules['all'] ) && is_array( $rules['all'] ) ) {
					return $rules['all'];
				}
			}
		}

		return null;
	}

	/**
	 * @param array<string,mixed> $rule
	 * @return array{min:int,max:int,step:int,source:string}
	 */
	private function sanitize_rule( array $rule ): array {
		$min  = isset( $rule['min'] ) ? max( 0, (int) $rule['min'] ) : 1;
		$max  = isset( $rule['max'] ) ? max( 0, (int) $rule['max'] ) : 0;
		$step = isset( $rule['step'] ) ? max( 1, (int) $rule['step'] ) : 1;

		if ( $max > 0 && $min > $max ) {
			$min = $max;
		}

		return [
			'min'    => $min < 1 ? 1 : $min,
			'max'    => $max,
			'step'   => $step,
			'source' => isset( $rule['source'] ) ? (string) $rule['source'] : 'unknown',
		];
	}

	/**
	 * تعیین نقش مؤثر برای قوانین تعداد.
	 *
	 * @param string|null $role
	 */
	private function normalize_role( $role = null ): string {
		if ( $role ) {
			return (string) $role;
		}

		$user = wp_get_current_user();

		if ( ! $user instanceof \WP_User || ! $user->exists() ) {
			return self::GUEST;
		}

		if ( Roles::is_wholesale( $user ) ) {
			return Roles::WHOLESALE;
		}

		if ( in_array( Roles::CUSTOMER, (array) $user->roles, true ) ) {
			return Roles::CUSTOMER;
		}

		// مدیر و مدیر فروشگاه باید بتوانند با شرایط مشتری خرید آزمایشی کنند.
		/**
		 * نقش مؤثر برای قوانین تعداد.
		 *
		 * @param string   $role
		 * @param \WP_User $user
		 */
		return (string) apply_filters( 'zch/quantity/effective_role', Roles::CUSTOMER, $user );
	}

	/* ---------------------------------------------------------------------
	 * رابط ادمین
	 * ------------------------------------------------------------------ */

	/**
	 * چاپ فیلدهای قوانین تعداد در تب «موجودی» محصول.
	 */
	public function render_admin_fields(): void {
		global $post;

		$product_id = $post instanceof \WP_Post ? (int) $post->ID : 0;
		$saved      = get_post_meta( $product_id, self::META_RULES, true );
		$saved      = is_array( $saved ) ? $saved : [];
		$defaults   = self::defaults();

		wp_nonce_field( 'zch_qty_save', 'zch_qty_nonce' );

		echo '<div class="options_group zch-quantity-fields">';
		echo '<p class="form-row form-row-full"><strong>' . esc_html__( 'قوانین تعداد به‌تفکیک نقش کاربری', 'zarincode-harir-core' ) . '</strong></p>';
		echo '<p class="description">' . esc_html__( 'حداکثر = ۰ یعنی بدون محدودیت. گام = عددی که با هر کلیک به تعداد اضافه می‌شود.', 'zarincode-harir-core' ) . '</p>';

		$rows = [
			self::GUEST      => __( 'مهمان', 'zarincode-harir-core' ),
			Roles::CUSTOMER  => __( 'مشتری', 'zarincode-harir-core' ),
			Roles::WHOLESALE => __( 'همکار عمده', 'zarincode-harir-core' ),
		];

		foreach ( $rows as $role => $label ) {
			$current = isset( $saved[ $role ] ) && is_array( $saved[ $role ] ) ? $saved[ $role ] : [];
			$fallback = isset( $defaults['roles'][ $role ] ) ? $defaults['roles'][ $role ] : [ 'min' => 1, 'max' => 0, 'step' => 1 ];

			echo '<table class="widefat zch-qty-table"><thead><tr><th colspan="3">' . esc_html( $label ) . '</th></tr>';
			echo '<tr><th>' . esc_html__( 'حداقل', 'zarincode-harir-core' ) . '</th><th>' . esc_html__( 'حداکثر', 'zarincode-harir-core' ) . '</th><th>' . esc_html__( 'گام', 'zarincode-harir-core' ) . '</th></tr></thead><tbody><tr>';

			foreach ( [ 'min', 'max', 'step' ] as $field ) {
				$value = isset( $current[ $field ] ) && '' !== $current[ $field ] ? (int) $current[ $field ] : '';
				printf(
					'<td><input type="number" min="0" step="1" class="small-text" name="zch_qty[%1$s][%2$s]" value="%3$s" placeholder="%4$d" /></td>',
					esc_attr( $role ),
					esc_attr( $field ),
					esc_attr( (string) $value ),
					(int) $fallback[ $field ]
				);
			}

			echo '</tr></tbody></table>';
		}

		if ( $product_id ) {
			$preview = $this->for_product( $product_id, Roles::WHOLESALE );
			printf(
				'<p class="description">%s <code>min=%d · max=%s · step=%d · source=%s</code></p>',
				esc_html__( 'پیش‌نمایش قانون همکار:', 'zarincode-harir-core' ),
				(int) $preview['min'],
				0 === (int) $preview['max'] ? '∞' : (int) $preview['max'],
				(int) $preview['step'],
				esc_html( $preview['source'] )
			);
		}

		echo '</div>';
	}

	/**
	 * @param int $post_id
	 */
	public function save_admin_fields( $post_id ): void {
		$post_id = (int) $post_id;

		if ( ! $post_id ) {
			return;
		}

		if ( ! isset( $_POST['zch_qty_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['zch_qty_nonce'] ) ), 'zch_qty_save' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_product', $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST['zch_qty'] ) || ! is_array( $_POST['zch_qty'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			delete_post_meta( $post_id, self::META_RULES );
			return;
		}

		$clean = [];

		foreach ( wp_unslash( $_POST['zch_qty'] ) as $role => $fields ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			if ( ! is_array( $fields ) ) {
				continue;
			}

			$role = sanitize_key( $role );
			$row  = [];
			$has  = false;

			foreach ( [ 'min', 'max', 'step' ] as $field ) {
				$raw = isset( $fields[ $field ] ) ? sanitize_text_field( $fields[ $field ] ) : '';

				if ( '' === $raw ) {
					continue;
				}

				$row[ $field ] = max( 0, (int) $raw );
				$has           = true;
			}

			if ( $has ) {
				$clean[ $role ] = $row;
			}
		}

		if ( empty( $clean ) ) {
			delete_post_meta( $post_id, self::META_RULES );
			return;
		}

		update_post_meta( $post_id, self::META_RULES, $clean );
	}
}
