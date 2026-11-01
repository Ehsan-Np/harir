<?php
/**
 * نرمال‌سازی و اعتبارسنجی شمارهٔ موبایل ایرانی.
 *
 * @package Zarincode\Harir\Helpers
 */

namespace Zarincode\Harir\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * ابزار شمارهٔ موبایل.
 */
final class Mobile {

	/**
	 * تبدیل ارقام فارسی/عربی به لاتین.
	 *
	 * @param string $input
	 * @return string
	 */
	public static function digits_to_latin( $input ) {
		$persian = [ '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩' ];
		$latin   = [ '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ];

		return str_replace( $persian, $latin, (string) $input );
	}

	/**
	 * نرمال‌سازی به قالب ۰۹xxxxxxxxx.
	 *
	 * ورودی‌های پذیرفته‌شده: 09121234567 · +989121234567 · 989121234567 ·
	 * 9121234567 · ۰۹۱۲۱۲۳۴۵۶۷
	 *
	 * @param string $input
	 * @return string رشتهٔ خالی اگر معتبر نباشد.
	 */
	public static function normalize( $input ) {
		$value = self::digits_to_latin( $input );

		// حذف هر کاراکتر غیررقمی.
		$value = preg_replace( '/\D+/', '', (string) $value );

		if ( '' === $value ) {
			return '';
		}

		// حذف کد کشور.
		if ( 0 === strpos( $value, '98' ) && strlen( $value ) > 10 ) {
			$value = '0' . substr( $value, 2 );
		}

		// بدون صفر ابتدایی.
		if ( 0 === strpos( $value, '9' ) && 10 === strlen( $value ) ) {
			$value = '0' . $value;
		}

		// حذف صفرهای اضافی ابتدایی.
		if ( strlen( $value ) > 11 && 0 === strpos( $value, '00' ) ) {
			$value = ltrim( $value, '0' );
			$value = '0' . $value;
		}

		if ( ! self::is_valid( $value ) ) {
			return '';
		}

		/**
		 * فیلتر شمارهٔ نرمال‌شده.
		 *
		 * @param string $value
		 */
		return (string) apply_filters( 'zch/mobile/normalized', $value );
	}

	/**
	 * اعتبارسنجی قالب ۰۹xxxxxxxxx.
	 *
	 * @param string $value
	 * @return bool
	 */
	public static function is_valid( $value ) {
		$value = self::digits_to_latin( $value );

		return (bool) preg_match( '/^09\d{9}$/', $value );
	}

	/**
	 * قالب بین‌المللی ۹۸۹xxxxxxxxx (برای درگاه‌هایی که این قالب را می‌خواهند).
	 *
	 * @param string $input
	 * @return string
	 */
	public static function to_international( $input ) {
		$value = self::normalize( $input );

		return '' === $value ? '' : '98' . substr( $value, 1 );
	}

	/**
	 * پوشاندن شماره برای نمایش در لاگ و رابط کاربری (حریم خصوصی).
	 *
	 * @param string $input
	 * @return string
	 */
	public static function mask( $input ) {
		$value = self::normalize( $input );

		if ( '' === $value ) {
			return '';
		}

		return substr( $value, 0, 4 ) . '***' . substr( $value, -3 );
	}
}
