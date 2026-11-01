<?php
/**
 * قرارداد درگاه پیامک.
 *
 * @package Zarincode\Harir\Sms\Contracts
 */

namespace Zarincode\Harir\Sms\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * هر درگاه پیامک باید این قرارداد را پیاده کند.
 */
interface GatewayInterface {

	/**
	 * شناسهٔ یکتای درگاه (مثلاً `kavenegar`).
	 *
	 * @return string
	 */
	public function id();

	/**
	 * نام نمایشی در تنظیمات.
	 *
	 * @return string
	 */
	public function label();

	/**
	 * ارسال پیامک متنی.
	 *
	 * @param string|array<string> $to      یک شماره یا آرایهٔ شماره‌ها.
	 * @param string               $message متن پیامک.
	 * @param array<string,mixed>  $args    گزینه‌های اضافی (sender, is_flash).
	 * @return \Zarincode\Harir\Sms\SmsResult
	 */
	public function send( $to, $message, array $args = [] );

	/**
	 * ارسال کد یک‌بارمصرف.
	 *
	 * درگاه‌هایی که سرویس OTP اختصاصی دارند (کاوه‌نگار: verify/lookup،
	 * ملی‌پیامک: SendOtp) باید از همان مسیر استفاده کنند تا پیامک از فیلتر
	 * تبلیغاتی عبور کند و سریع‌تر تحویل شود.
	 *
	 * @param string              $to
	 * @param string              $code
	 * @param array<string,mixed> $args
	 * @return \Zarincode\Harir\Sms\SmsResult
	 */
	public function send_otp( $to, $code, array $args = [] );

	/**
	 * وضعیت یک پیامک ارسال‌شده.
	 *
	 * @param string $message_id
	 * @return array{status:string,label:string,code:string}
	 */
	public function status( $message_id );

	/**
	 * اعتبار باقی‌ماندهٔ پنل (در صورت پشتیبانی).
	 *
	 * @return float|null
	 */
	public function credit();

	/**
	 * فیلدهای تنظیمات این درگاه برای فرم ادمین.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function settings_fields();

	/**
	 * بررسی اتصال — برای دکمهٔ «تست» در تنظیمات.
	 *
	 * @return array{ok:bool,message:string}
	 */
	public function health_check();

	/**
	 * آیا این درگاه از ارسال با الگوی ثبت‌شده (Pattern/Template) پشتیبانی می‌کند؟
	 *
	 * @return bool
	 */
	public function supports_pattern();
}
