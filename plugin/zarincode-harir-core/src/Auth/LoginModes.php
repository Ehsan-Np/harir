<?php
/**
 * حالت‌های ورود و ثبت‌نام — قابل تنظیم کامل توسط مدیر.
 *
 * @package Zarincode\Harir\Auth
 */

namespace Zarincode\Harir\Auth;

defined( 'ABSPATH' ) || exit;

/**
 * تنظیمات حالت‌های احراز هویت.
 */
final class LoginModes {

	/** گزینهٔ «با چه چیزی ثبت‌نام شود». */
	const OPT_REGISTER_WITH = 'zch_register_with';

	/** گزینهٔ «با چه چیزی وارد شود». */
	const OPT_LOGIN_WITH = 'zch_login_with';

	/**
	 * گزینه‌های مجاز برای ثبت‌نام.
	 *
	 * @return array<string,string>
	 */
	public static function register_options() {
		return [
			'mobile'          => __( 'فقط شمارهٔ موبایل', 'zarincode-harir-core' ),
			'email'           => __( 'فقط ایمیل', 'zarincode-harir-core' ),
			'mobile_or_email' => __( 'موبایل یا ایمیل (انتخاب کاربر)', 'zarincode-harir-core' ),
			'mobile_and_email' => __( 'موبایل و ایمیل (هر دو الزامی)', 'zarincode-harir-core' ),
		];
	}

	/**
	 * گزینه‌های مجاز برای ورود.
	 *
	 * @return array<string,string>
	 */
	public static function login_options() {
		return [
			'mobile_otp'               => __( 'موبایل + رمز یک‌بارمصرف', 'zarincode-harir-core' ),
			'mobile_password'          => __( 'موبایل + رمز عبور', 'zarincode-harir-core' ),
			'email_password'           => __( 'ایمیل + رمز عبور', 'zarincode-harir-core' ),
			'username_password'        => __( 'نام کاربری + رمز عبور (حالت اصلی وردپرس)', 'zarincode-harir-core' ),
			'mobile_otp_or_password'   => __( 'موبایل + رمز یک‌بارمصرف یا رمز عبور (انتخاب کاربر)', 'zarincode-harir-core' ),
			'any'                      => __( 'هرکدام (موبایل/ایمیل/نام کاربری + رمز عبور یا OTP)', 'zarincode-harir-core' ),
		];
	}

	/**
	 * حالت ثبت‌نام فعال.
	 *
	 * @return string
	 */
	public static function register_with() {
		$mode = (string) get_option( self::OPT_REGISTER_WITH, 'mobile' );

		return array_key_exists( $mode, self::register_options() ) ? $mode : 'mobile';
	}

	/**
	 * حالت ورود فعال.
	 *
	 * @return string
	 */
	public static function login_with() {
		$mode = (string) get_option( self::OPT_LOGIN_WITH, 'mobile_otp' );

		return array_key_exists( $mode, self::login_options() ) ? $mode : 'mobile_otp';
	}

	/**
	 * آیا ورود با OTP ممکن است؟
	 *
	 * @return bool
	 */
	public static function allows_otp() {
		return in_array( self::login_with(), [ 'mobile_otp', 'mobile_otp_or_password', 'any' ], true );
	}

	/**
	 * آیا ورود با رمز عبور ممکن است؟
	 *
	 * @return bool
	 */
	public static function allows_password() {
		return in_array( self::login_with(), [ 'mobile_password', 'email_password', 'username_password', 'mobile_otp_or_password', 'any' ], true );
	}

	/**
	 * آیا موبایل در ورود الزامی/مجاز است؟
	 *
	 * @return bool
	 */
	public static function login_uses_mobile() {
		return in_array( self::login_with(), [ 'mobile_otp', 'mobile_password', 'mobile_otp_or_password', 'any' ], true );
	}

	/**
	 * آیا ایمیل در ورود مجاز است؟
	 *
	 * @return bool
	 */
	public static function login_uses_email() {
		return in_array( self::login_with(), [ 'email_password', 'any' ], true );
	}

	/**
	 * آیا ثبت‌نام باز است؟
	 *
	 * @return bool
	 */
	public static function registration_open() {
		return (bool) get_option( 'users_can_register', false ) || (bool) get_option( 'zch_force_open_registration', true );
	}

	/**
	 * آیا انتخاب نقش در لحظهٔ ثبت‌نام نمایش داده شود؟
	 *
	 * @return bool
	 */
	public static function show_role_selector() {
		return (bool) get_option( 'zch_register_role_selector', true );
	}

	/**
	 * آیا حساب همکار نیاز به تأیید مدیر دارد؟
	 *
	 * @return bool
	 */
	public static function wholesale_requires_approval() {
		return (bool) get_option( 'zch_wholesale_requires_approval', true );
	}

	/**
	 * آیا مشتری عادی بلافاصله فعال شود؟
	 *
	 * همیشه true است مگر مدیر صراحتاً خاموشش کند.
	 *
	 * @return bool
	 */
	public static function customer_auto_approve() {
		return (bool) get_option( 'zch_customer_auto_approve', true );
	}

	/**
	 * در یک صحنهٔ مشخص، OTP لازم است؟
	 *
	 * @param string $scene register | login | reset | phone_change | wholesale
	 * @return bool
	 */
	public static function otp_required_for( $scene ) {
		$required = get_option( 'zch_otp_required_for', [ 'register', 'login', 'reset', 'phone_change', 'wholesale' ] );

		if ( ! is_array( $required ) ) {
			$required = [ 'register', 'login' ];
		}

		/**
		 * فیلتر الزام OTP برای یک صحنه.
		 *
		 * @param bool   $required
		 * @param string $scene
		 */
		return (bool) apply_filters( 'zch/auth/otp_required', in_array( $scene, $required, true ), $scene );
	}

	/**
	 * آیا ورود با نام کاربری/رمز عبور اصلی وردپرس هم‌زمان مجاز بماند؟
	 *
	 * این کلید «بدون خلل» بودن سیستم است: اگر true باشد، کاربران قدیمی
	 * با همان روش قبلی وارد می‌شوند و هیچ‌کس قفل نمی‌شود.
	 *
	 * @return bool
	 */
	public static function keep_native_login() {
		/**
		 * فیلتر حفظ ورود بومی وردپرس.
		 *
		 * @param bool $keep
		 */
		return (bool) apply_filters( 'zch/auth/keep_native_login', (bool) get_option( 'zch_keep_native_login', true ) );
	}
}
