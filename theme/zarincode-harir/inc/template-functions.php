<?php
/**
 * توابع کمکی قالب — همه بدون echo (برای استفاده در عبارت‌ها).
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

/**
 * تبدیل ارقام لاتین به فارسی.
 *
 * @param string|int|float $number
 * @return string
 */
function zch_fa_digits( $number ) {
	$latin   = [ '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ];
	$persian = [ '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ];

	/**
	 * فیلتر فعال‌سازی ارقام فارسی.
	 *
	 * @param bool $enabled
	 */
	if ( ! apply_filters( 'zch/use_persian_digits', true ) ) {
		return (string) $number;
	}

	return str_replace( $latin, $persian, (string) $number );
}

/**
 * قالب‌بندی مبلغ با ارقام فارسی و جداکنندهٔ هزارگان مطابق طرح (۳٬۸۸۸٬۰۰۰).
 *
 * @param float|int $amount
 * @param bool      $with_symbol آیا واحد پول اضافه شود.
 * @return string
 */
function zch_format_price( $amount, $with_symbol = true ) {
	$decimals = function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : 0;
	$formatted = number_format( (float) $amount, $decimals, '.', ',' );

	if ( $with_symbol && function_exists( 'get_woocommerce_currency_symbol' ) ) {
		$symbol = get_woocommerce_currency_symbol();

		/**
		 * فیلتر واژهٔ واحد پول — طرح اصلی از «تومان» استفاده می‌کند.
		 *
		 * @param string $label
		 */
		$label = apply_filters( 'zch/currency_label', __( 'تومان', 'zarincode-harir' ) );

		if ( '' !== $label ) {
			$formatted .= ' ' . $label;
		} elseif ( '' !== $symbol ) {
			$formatted .= ' ' . $symbol;
		}
	}

	return zch_fa_digits( $formatted );
}

/**
 * تبدیل تاریخ میلادی به شمسی.
 *
 * پیاده‌سازی سبک و بدون وابستگی به افزونهٔ خارجی.
 *
 * @param string|int $date timestamp یا رشتهٔ تاریخ.
 * @param string     $format قالب خروجی.
 * @return string
 */
function zch_jalali_date( $date = '', $format = 'Y/m/d' ) {
	$timestamp = is_numeric( $date ) ? (int) $date : ( '' === $date ? time() : strtotime( (string) $date ) );

	if ( ! $timestamp ) {
		return '';
	}

	if ( function_exists( 'wp_date' ) && class_exists( 'DateTime' ) ) {
		// اگر افزونه‌ای مانند WP Jalali تقویم را عوض کرده، از همان استفاده می‌کنیم.
		$maybe = wp_date( $format, $timestamp );

		if ( $maybe && false === strpos( $maybe, 'January' ) && preg_match( '/[۰-۹]/', $maybe ) ) {
			return $maybe;
		}
	}

	list( $jy, $jm, $jd ) = zch_gregorian_to_jalali( gmdate( 'Y', $timestamp ), gmdate( 'n', $timestamp ), gmdate( 'j', $timestamp ) );

	// اصلاح منطقهٔ زمانی سایت.
	$offset = (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
	list( $jy, $jm, $jd ) = zch_gregorian_to_jalali(
		gmdate( 'Y', $timestamp + $offset ),
		gmdate( 'n', $timestamp + $offset ),
		gmdate( 'j', $timestamp + $offset )
	);

	$replacements = [
		'Y' => (string) $jy,
		'y' => substr( (string) $jy, -2 ),
		'm' => str_pad( (string) $jm, 2, '0', STR_PAD_LEFT ),
		'n' => (string) $jm,
		'd' => str_pad( (string) $jd, 2, '0', STR_PAD_LEFT ),
		'j' => (string) $jd,
	];

	$output = strtr( $format, $replacements );

	return apply_filters( 'zch/jalali_date', zch_fa_digits( $output ), $timestamp, $format );
}

/**
 * الگوریتم تبدیل میلادی به شمسی.
 *
 * @param int $gy
 * @param int $gm
 * @param int $gd
 * @return array{0:int,1:int,2:int}
 */
function zch_gregorian_to_jalali( $gy, $gm, $gd ) {
	$g_d_m = [ 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334 ];

	$gy2 = ( $gm > 2 ) ? ( (int) $gy + 1 ) : (int) $gy;
	$days = 355666 + ( 365 * (int) $gy ) + (int) ( ( $gy2 + 3 ) / 4 ) - (int) ( ( $gy2 + 99 ) / 100 )
		+ (int) ( ( $gy2 + 399 ) / 400 ) + (int) $gd + $g_d_m[ (int) $gm - 1 ];

	$jy = -1595 + ( 33 * (int) ( $days / 12053 ) );
	$days %= 12053;

	$jy += 4 * (int) ( $days / 1461 );
	$days %= 1461;

	if ( $days > 365 ) {
		$jy += (int) ( ( $days - 1 ) / 365 );
		$days = ( $days - 1 ) % 365;
	}

	if ( $days < 186 ) {
		$jm = 1 + (int) ( $days / 31 );
		$jd = 1 + ( $days % 31 );
	} else {
		$jm = 7 + (int) ( ( $days - 186 ) / 30 );
		$jd = 1 + ( ( $days - 186 ) % 30 );
	}

	return [ (int) $jy, (int) $jm, (int) $jd ];
}

/**
 * نام ماه شمسی.
 *
 * @param int $month
 * @return string
 */
function zch_jalali_month_name( $month ) {
	$names = [
		1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد', 4 => 'تیر',
		5 => 'مرداد', 6 => 'شهریور', 7 => 'مهر', 8 => 'آبان',
		9 => 'آذر', 10 => 'دی', 11 => 'بهمن', 12 => 'اسفند',
	];

	$month = (int) $month;

	return isset( $names[ $month ] ) ? __( $names[ $month ], 'zarincode-harir' ) : ''; // phpcs:ignore WordPress.WP.I18n
}

/**
 * آیا کاربر جاری نقش همکار عمده دارد؟
 *
 * اگر هسته فعال نباشد، همیشه false است تا قالب مستقل هم کار کند.
 *
 * @param int|\WP_User|null $user
 * @return bool
 */
function zch_user_is_wholesale( $user = null ) {
	if ( ! class_exists( 'Zarincode\Harir\Roles\Roles' ) ) {
		return false;
	}

	return \Zarincode\Harir\Roles\Roles::is_wholesale( $user );
}

/**
 * آیا کاربر جاری اجازهٔ دیدن قیمت عمده دارد؟
 *
 * @return bool
 */
function zch_can_view_wholesale_price() {
	if ( ! class_exists( 'Zarincode\Harir\Roles\Roles' ) ) {
		return false;
	}

	return \Zarincode\Harir\Roles\Roles::can_view_wholesale_price();
}

/**
 * برچسب فارسی نقش کاربر جاری.
 *
 * @return string
 */
function zch_current_role_label() {
	if ( ! class_exists( 'Zarincode\Harir\Roles\Roles' ) ) {
		return is_user_logged_in() ? __( 'مشتری', 'zarincode-harir' ) : __( 'مهمان', 'zarincode-harir' );
	}

	if ( ! is_user_logged_in() ) {
		return __( 'مهمان', 'zarincode-harir' );
	}

	return \Zarincode\Harir\Roles\Roles::role_label();
}

/**
 * قوانین تعداد برای کاربر جاری.
 *
 * @param int $product_id
 * @return array{min:int,max:int,step:int,source:string}
 */
function zch_quantity_rule( $product_id ) {
	$fallback = [ 'min' => 1, 'max' => 0, 'step' => 1, 'source' => 'fallback' ];

	if ( ! class_exists( 'Zarincode\Harir\Wholesale\QuantityRules' ) ) {
		return $fallback;
	}

	$engine = new \Zarincode\Harir\Wholesale\QuantityRules();

	return $engine->for_product( (int) $product_id );
}

/**
 * نتیجهٔ قیمت سه‌گانه برای یک محصول.
 *
 * @param int      $product_id
 * @param int|null $variation_id
 * @return \Zarincode\Harir\Wholesale\PriceResult|null
 */
function zch_price_result( $product_id, $variation_id = null ) {
	if ( ! class_exists( 'Zarincode\Harir\Wholesale\PriceEngine' ) ) {
		return null;
	}

	$engine = new \Zarincode\Harir\Wholesale\PriceEngine();

	return $engine->resolve( (int) $product_id, $variation_id );
}

/**
 * مسیر برگشت پس از ورود.
 *
 * @return string
 */
function zch_login_redirect() {
	$redirect = wp_get_referer();

	if ( ! $redirect || false !== strpos( $redirect, 'wp-login.php' ) ) {
		$redirect = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/' );
	}

	/**
	 * فیلتر مسیر بازگشت پس از ورود.
	 *
	 * @param string $redirect
	 */
	return (string) apply_filters( 'zch/login_redirect', $redirect );
}

/**
 * آیا صفحهٔ جاری با المنتور ساخته شده؟
 *
 * @param int|null $post_id
 * @return bool
 */
function zch_is_built_with_elementor( $post_id = null ) {
	if ( ! did_action( 'elementor/loaded' ) ) {
		return false;
	}

	$post_id = $post_id ? (int) $post_id : get_the_ID();

	if ( ! $post_id ) {
		return false;
	}

	return (bool) \Elementor\Plugin::$instance->documents->get( $post_id )->is_built_with_elementor();
}

/**
 * شمارندهٔ سبد خرید برای نمایش در هدر.
 *
 * @return int
 */
function zch_cart_count() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return 0;
	}

	return (int) WC()->cart->get_cart_contents_count();
}

/**
 * تنظیمات نمایشی قالب از Customizer با fallback امن.
 *
 * @param string $key
 * @param mixed  $default
 * @return mixed
 */
function zch_option( $key, $default = '' ) {
	$value = get_theme_mod( 'zch_' . $key, null );

	if ( null === $value || '' === $value ) {
		$value = get_option( 'zch_theme_' . $key, null );
	}

	if ( null === $value || '' === $value ) {
		$value = $default;
	}

	/**
	 * فیلتر یک تنظیم قالب.
	 *
	 * @param mixed  $value
	 * @param string $key
	 */
	return apply_filters( 'zch/option', $value, $key );
}

/**
 * شمارهٔ تماس نمایشی سایت.
 *
 * @return string
 */
function zch_support_phone() {
	return (string) zch_option( 'support_phone', '۰۹۰۲۴۵۶۱۰۰۱' );
}

/**
 * نشانی وب‌سایت نویسندهٔ قالب.
 *
 * @return string
 */
function zch_author_uri() {
	return 'https://zarincode.com/';
}

/* -------------------------------------------------------------------------
 * راهنماهای محتوا
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'zch_reading_time' ) ) {
	/**
	 * زمان تقریبی مطالعه.
	 *
	 * نرخ خواندن فارسی حدود ۱۸۰ واژه در دقیقه در نظر گرفته می‌شود.
	 *
	 * @param int $post_id شناسهٔ نوشته.
	 * @return string
	 */
	function zch_reading_time( $post_id = 0 ) {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return '';
		}

		$words = count( preg_split( '/\s+/u', wp_strip_all_tags( $post->post_content ), -1, PREG_SPLIT_NO_EMPTY ) );
		$rate  = (int) apply_filters( 'zch/reading_words_per_minute', 180 );
		$mins  = max( 1, (int) ceil( $words / max( 1, $rate ) ) );

		/* translators: %d: reading time in minutes */
		return sprintf( _n( '%s دقیقه مطالعه', '%s دقیقه مطالعه', $mins, 'zarincode-harir' ), zch_fa_digits( $mins ) );
	}
}

if ( ! function_exists( 'zch_extract_headings' ) ) {
	/**
	 * استخراج سرفصل‌های h2/h3 از محتوا برای فهرست مطالب.
	 *
	 * شناسه‌ها به‌صورت `zch-h-{n}` تولید می‌شوند تا با
	 * `zch_anchorize_headings()` در فیلتر `the_content` هم‌خوان بمانند.
	 *
	 * @param string $content محتوای خام نوشته.
	 * @return array<int,array{level:int,text:string}>
	 */
	function zch_extract_headings( $content ) {
		$items = [];

		if ( false === preg_match_all( '/<h([23])[^>]*>(.*?)<\/h\1>/is', (string) $content, $matches, PREG_SET_ORDER ) ) {
			return $items;
		}

		foreach ( $matches as $match ) {
			$text = trim( wp_strip_all_tags( $match[2] ) );

			if ( '' === $text ) {
				continue;
			}

			$items[] = [
				'level' => (int) $match[1],
				'text'  => $text,
			];
		}

		return $items;
	}
}

if ( ! function_exists( 'zch_anchorize_headings' ) ) {
	/**
	 * افزودن شناسه به سرفصل‌های محتوا تا فهرست مطالب کار کند.
	 *
	 * @param string $content
	 * @return string
	 */
	function zch_anchorize_headings( $content ) {
		if ( is_admin() || is_feed() || ! is_singular() ) {
			return $content;
		}

		$counter = 0;

		return preg_replace_callback(
			'/<h([23])([^>]*)>(.*?)<\/h\1>/is',
			static function ( $matches ) use ( &$counter ) {
				$attrs = $matches[2];

				if ( false !== strpos( $attrs, 'id=' ) ) {
					return $matches[0];
				}

				$id = 'zch-h-' . $counter;
				$counter++;

				return '<h' . $matches[1] . $attrs . ' id="' . esc_attr( $id ) . '">' . $matches[3] . '</h' . $matches[1] . '>';
			},
			(string) $content
		);
	}

	add_filter( 'the_content', 'zch_anchorize_headings', 9 );
}

if ( ! function_exists( 'zch_archive_overline' ) ) {
	/**
	 * متن ریزِ بالای عنوان در صفحات آرشیو.
	 *
	 * @return string
	 */
	function zch_archive_overline() {
		if ( is_category() ) {
			return __( 'CATEGORY', 'zarincode-harir' );
		}
		if ( is_tag() ) {
			return __( 'TAG', 'zarincode-harir' );
		}
		if ( is_author() ) {
			return __( 'AUTHOR', 'zarincode-harir' );
		}
		if ( is_day() || is_month() || is_year() ) {
			return __( 'ARCHIVE', 'zarincode-harir' );
		}

		return __( 'BLOG', 'zarincode-harir' );
	}
}

if ( ! function_exists( 'zch_page_heading' ) ) {
	/**
	 * داده‌های سرصفحهٔ داخلی (overline، عنوان، lead و breadcrumb).
	 *
	 * @return array{overline:string,title:string,lead:string,trail:array}
	 */
	function zch_page_heading() {
		$overline = '';
		$title    = '';
		$lead     = '';
		$trail    = [];

		if ( is_singular( 'post' ) ) {
			$overline = __( 'ARTICLE', 'zarincode-harir' );
			$title    = get_the_title();

			$cats = get_the_category();
			if ( ! empty( $cats ) ) {
				$trail[] = [ 'label' => __( 'وبلاگ', 'zarincode-harir' ), 'url' => get_permalink( (int) get_option( 'page_for_posts' ) ) ];
				$trail[] = [ 'label' => $cats[0]->name, 'url' => get_category_link( $cats[0] ) ];
			}
			$trail[] = [ 'label' => $title, 'url' => '' ];

		} elseif ( is_page() ) {
			$overline = (string) get_post_meta( get_the_ID(), '_zch_page_overline', true );
			$title    = get_the_title();
			$lead     = (string) get_post_meta( get_the_ID(), '_zch_page_lead', true );

			$ancestors = array_reverse( get_post_ancestors( get_the_ID() ) );
			foreach ( $ancestors as $ancestor ) {
				$trail[] = [ 'label' => get_the_title( $ancestor ), 'url' => get_permalink( $ancestor ) ];
			}
			$trail[] = [ 'label' => $title, 'url' => '' ];

		} elseif ( is_singular() ) {
			$title = get_the_title();
			$trail[] = [ 'label' => $title, 'url' => '' ];

		} elseif ( is_archive() ) {
			$overline = zch_archive_overline();
			$title    = wp_strip_all_tags( get_the_archive_title() );

		} elseif ( is_search() ) {
			$overline = __( 'SEARCH', 'zarincode-harir' );
			$title    = get_search_query();

		} elseif ( is_home() ) {
			$overline = __( 'BLOG', 'zarincode-harir' );
			$title    = get_the_title( (int) get_option( 'page_for_posts' ) );
		}

		/**
		 * فیلتر داده‌های سرصفحهٔ داخلی.
		 *
		 * @param array $heading
		 */
		return apply_filters( 'zch/page_heading', [
			'overline' => (string) $overline,
			'title'    => (string) $title,
			'lead'     => (string) $lead,
			'trail'    => $trail,
		] );
	}
}

if ( ! function_exists( 'zch_render_suggested_products' ) ) {
	/**
	 * چاپ محصولات پیشنهادی (برای صفحهٔ ۴۰۴ و انتهای صفحهٔ محصول).
	 *
	 * @param int $limit تعداد محصول.
	 */
	function zch_render_suggested_products( $limit = 4 ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$products = wc_get_products(
			[
				'status'  => 'publish',
				'limit'   => (int) $limit,
				'orderby' => 'popularity',
				'order'   => 'DESC',
			]
		);

		if ( empty( $products ) ) {
			return;
		}

		echo '<div class="products">';

		foreach ( $products as $product ) {
			wc_get_template_part( 'content', 'product' );
		}

		echo '</div>';
	}
}

/* -------------------------------------------------------------------------
 * راهنماهای ووکامرس (کارت محصول)
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'zch_currency_label' ) ) {
	/**
	 * برچسب واحد پول.
	 *
	 * @return string
	 */
	function zch_currency_label() {
		/**
		 * فیلتر برچسب واحد پول.
		 *
		 * @param string $label
		 */
		return (string) apply_filters( 'zch/currency_label', __( 'تومان', 'zarincode-harir' ) );
	}
}

if ( ! function_exists( 'zch_product_brand_label' ) ) {
	/**
	 * متن بالای کارت محصول (برند یا نام فروشگاه).
	 *
	 * @param WC_Product $product
	 * @return string
	 */
	function zch_product_brand_label( $product ) {
		$default = zch_option( 'product_card_kicker', get_bloginfo( 'name' ) );

		if ( ! $product instanceof WC_Product ) {
			return (string) $default;
		}

		$slugs = [ 'pa_zch-brand', 'pa_brand', 'pa_zch-برند' ];

		foreach ( $slugs as $slug ) {
			$terms = wc_get_product_terms( $product->get_id(), $slug, [ 'fields' => 'names' ] );

			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				return (string) $terms[0];
			}
		}

		return (string) $default;
	}
}

if ( ! function_exists( 'zch_product_color_dots' ) ) {
	/**
	 * نقطه‌های رنگ کارت محصول.
	 *
	 * مقدار رنگ از meta ترم (`_zch_color`) یا نام رنگ حدس زده می‌شود.
	 *
	 * @param WC_Product $product
	 * @return array<int,array{name:string,value:string}>
	 */
	function zch_product_color_dots( $product ) {
		$dots = [];

		if ( ! $product instanceof WC_Product ) {
			return $dots;
		}

		$slugs = [ 'pa_zch-color', 'pa_color', 'pa_zch-رنگ' ];

		foreach ( $slugs as $slug ) {
			$terms = get_the_terms( $product->get_id(), $slug );

			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				continue;
			}

			foreach ( $terms as $term ) {
				$hex = (string) get_term_meta( $term->term_id, '_zch_color', true );

				if ( '' === $hex || ! preg_match( '/^#[0-9a-fA-F]{3,8}$/', $hex ) ) {
					$hex = zch_guess_color_hex( $term->name );
				}

				$dots[] = [
					'name'  => $term->name,
					'value' => $hex,
				];
			}

			break;
		}

		/**
		 * فیلتر نقطه‌های رنگ کارت محصول.
		 *
		 * @param array      $dots
		 * @param WC_Product $product
		 */
		return apply_filters( 'zch/product/color_dots', $dots, $product );
	}
}

if ( ! function_exists( 'zch_guess_color_hex' ) ) {
	/**
	 * حدس کد رنگ از نام فارسی/انگلیسی آن.
	 *
	 * @param string $name
	 * @return string
	 */
	function zch_guess_color_hex( $name ) {
		$map = [
			'صورتی'   => '#e8b4c4',
			'pink'    => '#e8b4c4',
			'کرم'     => '#f0e4d3',
			'cream'   => '#f0e4d3',
			'سفید'    => '#ffffff',
			'white'   => '#ffffff',
			'طوسی'    => '#b9b2b6',
			'خاکستری' => '#b9b2b6',
			'gray'    => '#b9b2b6',
			'grey'    => '#b9b2b6',
			'سرمه‌ای' => '#3a4660',
			'navy'    => '#3a4660',
			'بنفش'    => '#7b5d84',
			'purple'  => '#7b5d84',
			'یاسی'    => '#c8a7c9',
			'سبز'     => '#8fa48a',
			'green'   => '#8fa48a',
			'قهوه‌ای' => '#8b6f5c',
			'brown'   => '#8b6f5c',
			'طلایی'   => '#b48d78',
			'gold'    => '#b48d78',
			'مشکی'    => '#3b2935',
			'black'   => '#3b2935',
			'زرشکی'  => '#8c3b4a',
			'نقره‌ای' => '#c9c9cf',
			'silver'  => '#c9c9cf',
		];

		$key = mb_strtolower( trim( (string) $name ) );

		foreach ( $map as $word => $hex ) {
			if ( false !== mb_strpos( $key, $word ) ) {
				return $hex;
			}
		}

		return 'var(--zch-accent-2)';
	}
}

if ( ! function_exists( 'zch_product_card_subtitle' ) ) {
	/**
	 * زیرعنوان کارت محصول — ترکیب سایز/تکه و اشاره به انتخاب رنگ.
	 *
	 * @param WC_Product $product
	 * @return string
	 */
	function zch_product_card_subtitle( $product ) {
		if ( ! $product instanceof WC_Product ) {
			return '';
		}

		$parts = [];

		foreach ( [ 'pa_zch-size', 'pa_size', 'pa_zch-pieces', 'pa_pieces' ] as $slug ) {
			$terms = wc_get_product_terms( $product->get_id(), $slug, [ 'fields' => 'names' ] );

			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				$parts[] = (string) $terms[0];
			}
		}

		if ( $product->is_type( 'variable' ) ) {
			$parts[] = __( 'انتخاب رنگ و سایز', 'zarincode-harir' );
		}

		$parts = array_values( array_unique( array_filter( $parts ) ) );

		if ( empty( $parts ) ) {
			$excerpt = $product->get_short_description();

			return '' !== $excerpt ? wp_trim_words( wp_strip_all_tags( $excerpt ), 7, '…' ) : '';
		}

		/**
		 * فیلتر زیرعنوان کارت محصول.
		 *
		 * @param string     $subtitle
		 * @param WC_Product $product
		 */
		return apply_filters( 'zch/product/card_subtitle', implode( ' · ', array_slice( $parts, 0, 2 ) ), $product );
	}
}

if ( ! function_exists( 'zch_product_card_tag' ) ) {
	/**
	 * برچسب گوشهٔ تصویر محصول.
	 *
	 * ترتیب اولویت: درصد تخفیف → ویژه همکار → محصول ویژه → موجودی کم.
	 *
	 * @param WC_Product $product
	 * @return string
	 */
	function zch_product_card_tag( $product ) {
		if ( ! $product instanceof WC_Product ) {
			return '';
		}

		$regular = (float) $product->get_regular_price();
		$sale    = (float) $product->get_sale_price();

		if ( $regular > 0 && $sale > 0 && $sale < $regular ) {
			$percent = (int) round( ( ( $regular - $sale ) / $regular ) * 100 );

			/* translators: %s: discount percent in Persian digits */
			return sprintf( __( '%s تخفیف', 'zarincode-harir' ), zch_fa_digits( $percent ) . '٪' );
		}

		if ( $product->is_featured() ) {
			return (string) zch_option( 'tag_featured', __( 'انتخاب محبوب', 'zarincode-harir' ) );
		}

		if ( ! $product->is_in_stock() ) {
			return (string) zch_option( 'tag_out_of_stock', __( 'ناموجود', 'zarincode-harir' ) );
		}

		if ( $product->is_on_backorder() ) {
			return (string) zch_option( 'tag_backorder', __( 'پیش‌سفارش', 'zarincode-harir' ) );
		}

		$stock = $product->get_stock_quantity();

		if ( null !== $stock && (int) $stock > 0 && (int) $stock <= (int) zch_option( 'low_stock_threshold', 5 ) ) {
			/* translators: %s: remaining stock */
			return sprintf( __( 'فقط %s عدد', 'zarincode-harir' ), zch_fa_digits( (int) $stock ) );
		}

		/**
		 * فیلتر برچسب کارت محصول.
		 *
		 * @param string     $tag
		 * @param WC_Product $product
		 */
		return (string) apply_filters( 'zch/product/card_tag', '', $product );
	}
}

/* -------------------------------------------------------------------------
 * راهنماهای صفحهٔ محصول
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'zch_product_code' ) ) {
	/**
	 * کد نمایشی محصول — الگوی «HR-XXXX».
	 *
	 * اگر SKU تعریف شده باشد همان چاپ می‌شود، در غیر این صورت کد از
	 * شناسهٔ محصول ساخته می‌شود تا در دمو هم خالی نماند.
	 *
	 * @param WC_Product $product
	 * @return string
	 */
	function zch_product_code( $product ) {
		if ( ! $product instanceof WC_Product ) {
			return '';
		}

		$sku = $product->get_sku();

		if ( '' !== $sku ) {
			return $sku;
		}

		$prefix = (string) zch_option( 'order_prefix', 'HR-' );
		$id     = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();

		return $prefix . str_pad( (string) $id, 4, '0', STR_PAD_LEFT );
	}
}

if ( ! function_exists( 'zch_quantity_rule_text' ) ) {
	/**
	 * متن توضیحی قاعدهٔ تعداد برای کاربر جاری.
	 *
	 * @param array $rule خروجی `zch_quantity_rule()`.
	 * @return string
	 */
	function zch_quantity_rule_text( $rule ) {
		if ( empty( $rule ) || ! is_array( $rule ) ) {
			return '';
		}

		$min  = isset( $rule['min'] ) ? (int) $rule['min'] : 1;
		$max  = isset( $rule['max'] ) ? (int) $rule['max'] : 0;
		$step = isset( $rule['step'] ) ? (int) $rule['step'] : 1;
		$bits = [];

		/* translators: %s: minimum quantity */
		$bits[] = sprintf( __( 'حداقل سفارش %s عدد', 'zarincode-harir' ), zch_fa_digits( $min ) );

		if ( $step > 1 ) {
			/* translators: %s: quantity step */
			$bits[] = sprintf( __( 'گام افزایش %s عدد', 'zarincode-harir' ), zch_fa_digits( $step ) );
		}

		if ( $max > 0 ) {
			/* translators: %s: maximum quantity */
			$bits[] = sprintf( __( 'حداکثر %s عدد', 'zarincode-harir' ), zch_fa_digits( $max ) );
		}

		/**
		 * فیلتر متن قاعدهٔ تعداد.
		 *
		 * @param string $text
		 * @param array  $rule
		 */
		return apply_filters( 'zch/quantity_rule_text', implode( ' · ', $bits ), $rule );
	}
}

if ( ! function_exists( 'zch_render_attribute_chips' ) ) {
	/**
	 * چاپ چیپس‌های ویژگی محصول (برند، مدل، سایز، رنگ، تکه).
	 *
	 * @param WC_Product $product
	 */
	function zch_render_attribute_chips( $product ) {
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$attributes = $product->get_attributes();

		if ( empty( $attributes ) ) {
			return;
		}

		echo '<div class="pd-attrs">';

		foreach ( $attributes as $attribute ) {
			if ( ! $attribute instanceof WC_Product_Attribute ) {
				continue;
			}

			if ( ! $attribute['visible'] ) {
				continue;
			}

			$name  = wc_attribute_label( $attribute->get_taxonomy() ?: '' );
			$terms = $attribute->get_terms();

			if ( empty( $terms ) ) {
				$options = $attribute->get_options();

				if ( empty( $options ) ) {
					continue;
				}

				echo '<span class="pd-attr"><small>' . esc_html( $name ) . '</small><strong>' . esc_html( implode( '، ', array_map( 'sanitize_text_field', $options ) ) ) . '</strong></span>';
				continue;
			}

			$labels = [];

			foreach ( $terms as $term ) {
				if ( isset( $term->name ) ) {
					$labels[] = $term->name;
				}
			}

			if ( empty( $labels ) ) {
				continue;
			}

			echo '<span class="pd-attr"><small>' . esc_html( $name ) . '</small><strong>' . esc_html( implode( '، ', $labels ) ) . '</strong></span>';
		}

		echo '</div>';
	}
}

if ( ! function_exists( 'zch_render_trust_items' ) ) {
	/**
	 * چاپ نمادهای اطمینان زیر دکمهٔ خرید.
	 */
	function zch_render_trust_items() {
		$items = [
			[ 'icon' => 'shield-check', 'text' => __( 'ضمانت اصالت کالا', 'zarincode-harir' ) ],
			[ 'icon' => 'refresh',      'text' => __( '۷ روز ضمانت بازگشت', 'zarincode-harir' ) ],
			[ 'icon' => 'truck',        'text' => __( 'ارسال به سراسر کشور', 'zarincode-harir' ) ],
		];

		/**
		 * فیلتر نمادهای اطمینان صفحهٔ محصول.
		 *
		 * @param array $items
		 */
		$items = apply_filters( 'zch/product/trust_items', $items );

		if ( empty( $items ) ) {
			return;
		}

		echo '<div class="pd-trust">';

		foreach ( $items as $item ) {
			echo '<span>' . zch_get_icon( $item['icon'], [ 'size' => 15 ] ) . esc_html( $item['text'] ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}

		echo '</div>';
	}
}

if ( ! function_exists( 'zch_render_related_products' ) ) {
	/**
	 * چاپ محصولات مرتبط با چیدمان بومی حریر.
	 *
	 * @param int $limit
	 */
	function zch_render_related_products( $limit = 4 ) {
		if ( ! function_exists( 'wc_get_related_products' ) ) {
			return;
		}

		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$related_ids = wc_get_related_products( $product->get_id(), (int) $limit );

		if ( empty( $related_ids ) ) {
			return;
		}

		echo '<section class="section pd-related">';
		echo '<div class="section-head"><div>';
		echo '<div class="section-label">' . esc_html( zch_option( 'related_label', 'YOU MAY ALSO LIKE' ) ) . '</div>';
		echo '<h2>' . esc_html( zch_option( 'related_title', __( 'محصولات مرتبط', 'zarincode-harir' ) ) ) . '</h2>';
		echo '</div></div>';
		echo '<div class="product-grid">';

		foreach ( $related_ids as $related_id ) {
			$related = wc_get_product( $related_id );

			if ( $related instanceof WC_Product && $related->is_visible() ) {
				get_template_part( 'template-parts/content/product', 'card', [ 'zch_product' => $related ] );
			}
		}

		echo '</div></section>';
	}
}
