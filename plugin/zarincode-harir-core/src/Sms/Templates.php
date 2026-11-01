<?php
/**
 * الگوهای متن پیامک — قابل ویرایش کامل در ادمین.
 *
 * @package Zarincode\Harir\Sms
 */

namespace Zarincode\Harir\Sms;

defined( 'ABSPATH' ) || exit;

/**
 * مدیریت الگوهای پیامکی.
 */
final class Templates {

	const OPTION = 'zch_sms_templates';

	/**
	 * الگوهای پیش‌فرض.
	 *
	 * متغیرها با {نام} جای‌گذاری می‌شوند.
	 *
	 * @return array<string,array{label:string,body:string,enabled:bool,otp:bool}>
	 */
	public static function defaults() {
		return [
			'otp_login'          => [
				'label'   => __( 'کد تأیید ورود', 'zarincode-harir-core' ),
				'body'    => 'کد تأیید حریر رویا: {code}',
				'enabled' => true,
				'otp'     => true,
			],
			'otp_register'       => [
				'label'   => __( 'کد تأیید ثبت‌نام', 'zarincode-harir-core' ),
				'body'    => '{name} عزیز، کد تأیید شما: {code}',
				'enabled' => true,
				'otp'     => true,
			],
			'otp_reset'          => [
				'label'   => __( 'کد بازیابی رمز عبور', 'zarincode-harir-core' ),
				'body'    => 'کد بازیابی رمز عبور حریر رویا: {code}',
				'enabled' => true,
				'otp'     => true,
			],
			'welcome'            => [
				'label'   => __( 'خوش‌آمدگویی', 'zarincode-harir-core' ),
				'body'    => '{name} عزیز، به حریر رویا خوش آمدید.',
				'enabled' => true,
				'otp'     => false,
			],
			'wholesale_approved' => [
				'label'   => __( 'تأیید حساب همکار', 'zarincode-harir-core' ),
				'body'    => '{name} عزیز، حساب همکاری شما در حریر رویا فعال شد. اکنون قیمت‌های عمده را مشاهده می‌کنید.',
				'enabled' => true,
				'otp'     => false,
			],
			'wholesale_rejected' => [
				'label'   => __( 'نیاز به تکمیل مدارک همکاری', 'zarincode-harir-core' ),
				'body'    => '{name} عزیز، درخواست همکاری شما نیاز به تکمیل دارد: {reason}',
				'enabled' => true,
				'otp'     => false,
			],
			'role_downgraded'    => [
				'label'   => __( 'تنزیل حساب به مشتری', 'zarincode-harir-core' ),
				'body'    => '{name} عزیز، حساب شما به حساب مشتری تغییر یافت.',
				'enabled' => false,
				'otp'     => false,
			],
			'order_received'     => [
				'label'   => __( 'ثبت سفارش', 'zarincode-harir-core' ),
				'body'    => 'سفارش {order_number} در حریر رویا ثبت شد. مبلغ: {total}',
				'enabled' => true,
				'otp'     => false,
			],
			'order_paid'         => [
				'label'   => __( 'پرداخت موفق', 'zarincode-harir-core' ),
				'body'    => 'پرداخت سفارش {order_number} تأیید شد. سپاس از اعتماد شما.',
				'enabled' => true,
				'otp'     => false,
			],
			'order_shipped'      => [
				'label'   => __( 'ارسال سفارش', 'zarincode-harir-core' ),
				'body'    => 'سفارش {order_number} ارسال شد. کد رهگیری: {tracking}',
				'enabled' => true,
				'otp'     => false,
			],
			'order_completed'    => [
				'label'   => __( 'تحویل سفارش', 'zarincode-harir-core' ),
				'body'    => 'سفارش {order_number} تحویل شد. خوشحال می‌شویم نظر خود را ثبت کنید.',
				'enabled' => false,
				'otp'     => false,
			],
			'order_cancelled'    => [
				'label'   => __( 'لغو سفارش', 'zarincode-harir-core' ),
				'body'    => 'سفارش {order_number} لغو شد.',
				'enabled' => false,
				'otp'     => false,
			],
			'rfq_answered'       => [
				'label'   => __( 'پاسخ استعلام قیمت عمده', 'zarincode-harir-core' ),
				'body'    => 'پیش‌فاکتور استعلام {rfq_code} در حریر رویا آمادهٔ مشاهده است.',
				'enabled' => true,
				'otp'     => false,
			],
			'ticket_reply'       => [
				'label'   => __( 'پاسخ تیکت پشتیبانی', 'zarincode-harir-core' ),
				'body'    => 'تیکت #{ticket_id} پاسخ دریافت کرد.',
				'enabled' => true,
				'otp'     => false,
			],
			'cashback'           => [
				'label'   => __( 'افزایش کش‌بک', 'zarincode-harir-core' ),
				'body'    => '{amount} به کیف پول حریر رویای شما اضافه شد. موجودی: {balance}',
				'enabled' => false,
				'otp'     => false,
			],
			'wheel_win'          => [
				'label'   => __( 'جایزهٔ گردونه شانس', 'zarincode-harir-core' ),
				'body'    => 'تبریک {name}! جایزهٔ شما در گردونهٔ حریر رویا: {prize}',
				'enabled' => false,
				'otp'     => false,
			],
		];
	}

	/**
	 * الگوهای ذخیره‌شده (ادغام با پیش‌فرض).
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function all() {
		$saved = get_option( self::OPTION, [] );

		if ( ! is_array( $saved ) ) {
			$saved = [];
		}

		$merged = self::defaults();

		foreach ( $saved as $key => $data ) {
			if ( ! isset( $merged[ $key ] ) || ! is_array( $data ) ) {
				continue;
			}

			$merged[ $key ] = array_merge( $merged[ $key ], $data );
		}

		/**
		 * فیلتر الگوهای پیامکی.
		 *
		 * @param array $merged
		 */
		return apply_filters( 'zch/sms/templates', $merged );
	}

	/**
	 * آیا یک الگو فعال است؟
	 *
	 * @param string $key
	 * @return bool
	 */
	public static function is_enabled( $key ) {
		$all = self::all();

		return isset( $all[ $key ] ) && ! empty( $all[ $key ]['enabled'] );
	}

	/**
	 * آیا الگو باید از سرویس OTP اختصاصی درگاه ارسال شود؟
	 *
	 * @param string $key
	 * @return bool
	 */
	public static function is_otp( $key ) {
		$all = self::all();

		return isset( $all[ $key ] ) && ! empty( $all[ $key ]['otp'] );
	}

	/**
	 * ساخت متن نهایی یک الگو.
	 *
	 * @param string               $key
	 * @param array<string,mixed>  $vars
	 * @return string
	 */
	public static function render( $key, array $vars = [] ) {
		$all = self::all();

		if ( ! isset( $all[ $key ] ) ) {
			return '';
		}

		$body = (string) $all[ $key ]['body'];

		$replacements = [];
		foreach ( $vars as $name => $value ) {
			$replacements[ '{' . $name . '}' ] = (string) $value;
		}

		$body = strtr( $body, $replacements );

		// متغیرهای جای‌گذاری‌نشده حذف می‌شوند تا در پیامک دیده نشوند.
		$body = preg_replace( '/\{[a-z_]+\}/i', '', $body );

		$body = trim( preg_replace( '/\s{2,}/u', ' ', (string) $body ) );

		/**
		 * فیلتر متن نهایی پیامک.
		 *
		 * @param string $body
		 * @param string $key
		 * @param array  $vars
		 */
		return (string) apply_filters( 'zch/sms/rendered', $body, $key, $vars );
	}
}
