<?php
/**
 * تولید، ذخیره و اعتبارسنجی کد یک‌بارمصرف.
 *
 * اصول امنیتی رعایت‌شده:
 *  - کد خام هرگز ذخیره نمی‌شود؛ فقط hash('sha256', code + salt)
 *  - TTL کوتاه (پیش‌فرض ۱۲۰ ثانیه)
 *  - سقف تلاش (پیش‌فرض ۵) سپس باطل‌سازی
 *  - قفل موقت پس از تلاش‌های زیاد
 *  - پاسخ یکسان برای «شماره نامعتبر» و «کد اشتباه» تا user enumeration ممکن نباشد
 *  - پاک‌سازی خودکار رکوردهای منقضی
 *
 * @package Zarincode\Harir\Auth
 */

namespace Zarincode\Harir\Auth;

defined( 'ABSPATH' ) || exit;

use Zarincode\Harir\Db\Tables;
use Zarincode\Harir\Helpers\Mobile;
use Zarincode\Harir\Sms\SmsManager;

/**
 * موتور OTP.
 */
final class Otp {

	/** صحنه‌های استفاده. */
	const SCENE_LOGIN    = 'login';
	const SCENE_REGISTER = 'register';
	const SCENE_RESET    = 'reset';
	const SCENE_PHONE    = 'phone_change';
	const SCENE_WHOLESALE = 'wholesale';

	/**
	 * @return string
	 */
	public static function table() {
		return Tables::name( 'otp' );
	}

	/**
	 * طول کد (۴ تا ۸).
	 *
	 * @return int
	 */
	public static function code_length() {
		$length = (int) get_option( 'zch_otp_length', 5 );

		return min( 8, max( 4, $length ) );
	}

	/**
	 * زمان اعتبار به ثانیه.
	 *
	 * @return int
	 */
	public static function ttl() {
		$ttl = (int) get_option( 'zch_otp_ttl', 120 );

		return min( 900, max( 60, $ttl ) );
	}

	/**
	 * حداکثر تلاش برای یک کد.
	 *
	 * @return int
	 */
	public static function max_attempts() {
		return min( 10, max( 2, (int) get_option( 'zch_otp_max_attempts', 5 ) ) );
	}

	/**
	 * ساخت و ارسال یک کد تازه.
	 *
	 * @param string $identifier شمارهٔ موبایل یا ایمیل.
	 * @param string $type       'mobile' | 'email'.
	 * @param string $scene      یکی از ثابت‌های SCENE_*.
	 * @param array<string,mixed> $args گزینه‌های اضافی (user_id, name).
	 * @return true|\WP_Error
	 */
	public static function issue( $identifier, $type = 'mobile', $scene = self::SCENE_LOGIN, array $args = [] ) {
		global $wpdb;

		$identifier = self::normalize_identifier( $identifier, $type );

		if ( '' === $identifier ) {
			return new \WP_Error( 'zch_otp_invalid', self::generic_error() );
		}

		// اگر کد معتبر و مصرف‌نشده‌ای از قبل وجود دارد، همان را دوباره ارسال می‌کنیم
		// تا با هر کلیک یک رکورد تازه و یک پیامک تازه ساخته نشود.
		$existing = self::find_active( $identifier, $type, $scene );

		if ( $existing ) {
			$code = self::generate_code();
			self::update_hash( (int) $existing['id'], $code );
		} else {
			$code = self::generate_code();
			self::insert( $identifier, $type, $scene, $code );
		}

		self::cleanup();

		$template_key = self::template_for_scene( $scene );

		$sms = new SmsManager();
		$result = $sms->send_template(
			$identifier,
			$template_key,
			[
				'code' => $code,
				'name' => isset( $args['name'] ) ? $args['name'] : '',
			],
			[ 'user_id' => isset( $args['user_id'] ) ? (int) $args['user_id'] : 0 ]
		);

		if ( ! $result->ok ) {
			/**
			 * هنگام شکست ارسال کد.
			 *
			 * @param string    $identifier
			 * @param string    $scene
			 * @param SmsResult $result
			 */
			do_action( 'zch/otp/send_failed', $identifier, $scene, $result );

			return new \WP_Error( 'zch_otp_send_failed', $result->error ? $result->error : self::generic_error() );
		}

		/**
		 * پس از ارسال موفق کد.
		 *
		 * @param string $identifier
		 * @param string $scene
		 */
		do_action( 'zch/otp/issued', $identifier, $scene );

		return true;
	}

	/**
	 * اعتبارسنجی یک کد.
	 *
	 * @param string $identifier
	 * @param string $code
	 * @param string $type
	 * @param string $scene
	 * @return true|\WP_Error
	 */
	public static function verify( $identifier, $code, $type = 'mobile', $scene = self::SCENE_LOGIN ) {
		global $wpdb;

		$identifier = self::normalize_identifier( $identifier, $type );
		$code       = preg_replace( '/\D+/', '', (string) $code );

		if ( '' === $identifier || '' === $code ) {
			return new \WP_Error( 'zch_otp_invalid', self::generic_error() );
		}

		// قفل موقت پس از تلاش‌های زیاد.
		if ( self::is_locked( $identifier, $type ) ) {
			return new \WP_Error( 'zch_otp_locked', __( 'به دلیل تلاش‌های ناموفق زیاد، حساب موقتاً قفل شده است. لطفاً ۱۵ دقیقه دیگر تلاش کنید.', 'zarincode-harir-core' ) );
		}

		$row = self::find_active( $identifier, $type, $scene );

		if ( ! $row ) {
			self::register_failed_attempt( $identifier, $type );

			return new \WP_Error( 'zch_otp_invalid', self::generic_error() );
		}

		if ( ! hash_equals( (string) $row['code_hash'], self::hash_code( $code ) ) ) {
			$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				self::table(),
				[ 'attempts' => (int) $row['attempts'] + 1 ],
				[ 'id' => (int) $row['id'] ],
				[ '%d' ],
				[ '%d' ]
			);

			self::register_failed_attempt( $identifier, $type );

			if ( (int) $row['attempts'] + 1 >= self::max_attempts() ) {
				$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					self::table(),
					[ 'consumed_at' => time() ],
					[ 'id' => (int) $row['id'] ],
					[ '%d' ],
					[ '%d' ]
				);

				return new \WP_Error( 'zch_otp_exhausted', __( 'تعداد تلاش‌های مجاز تمام شد. لطفاً کد تازه‌ای درخواست کنید.', 'zarincode-harir-core' ) );
			}

			return new \WP_Error( 'zch_otp_invalid', self::generic_error() );
		}

		// مصرف‌شدن کد — بلافاصله و یک‌بار.
		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table(),
			[ 'consumed_at' => time() ],
			[ 'id' => (int) $row['id'] ],
			[ '%d' ],
			[ '%d' ]
		);

		self::clear_lock( $identifier, $type );

		/**
		 * پس از اعتبارسنجی موفق یک کد.
		 *
		 * @param string $identifier
		 * @param string $scene
		 * @param array  $row
		 */
		do_action( 'zch/otp/verified', $identifier, $scene, $row );

		return true;
	}

	/**
	 * آیا برای این شناسه کد معتبر و مصرف‌نشده‌ای وجود دارد؟
	 *
	 * برای نمایش وضعیت «کد ارسال شد» و زمان‌باقی‌ماندهٔ تایمر.
	 *
	 * @param string $identifier
	 * @param string $type
	 * @param string $scene
	 * @return array{id:int,expires_in:int}|null
	 */
	public static function pending( $identifier, $type = 'mobile', $scene = self::SCENE_LOGIN ) {
		$identifier = self::normalize_identifier( $identifier, $type );
		$row        = self::find_active( $identifier, $type, $scene );

		if ( ! $row ) {
			return null;
		}

		return [
			'id'         => (int) $row['id'],
			'expires_in' => max( 0, (int) $row['expires_at'] - time() ),
		];
	}

	/**
	 * باطل‌سازی همهٔ کدهای فعال یک شناسه (مثلاً پس از ورود موفق).
	 *
	 * @param string $identifier
	 * @param string $type
	 * @return int
	 */
	public static function revoke_all( $identifier, $type = 'mobile' ) {
		global $wpdb;

		$identifier = self::normalize_identifier( $identifier, $type );

		if ( '' === $identifier ) {
			return 0;
		}

		$table = self::table();

		return (int) $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"UPDATE {$table} SET consumed_at = %d WHERE identifier = %s AND identifier_type = %s AND consumed_at IS NULL", // phpcs:ignore WordPress.DB.PreparedSQL
				time(),
				$identifier,
				$type
			)
		);
	}

	/**
	 * پاک‌سازی رکوردهای منقضی و مصرف‌شدهٔ قدیمی.
	 *
	 * @return int
	 */
	public static function cleanup() {
		global $wpdb;

		$table = self::table();
		$cutoff = time() - DAY_IN_SECONDS;

		return (int) $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE expires_at < %d OR (consumed_at IS NOT NULL AND consumed_at < %d)", // phpcs:ignore WordPress.DB.PreparedSQL
				time() - HOUR_IN_SECONDS,
				$cutoff
			)
		);
	}

	/* ------------------------------------------------------------------
	 * داخلی
	 * ---------------------------------------------------------------- */

	/**
	 * تولید کد تصادفی امن.
	 *
	 * @return string
	 */
	private static function generate_code() {
		$length = self::code_length();
		$min    = (int) pow( 10, $length - 1 );
		$max    = (int) pow( 10, $length ) - 1;

		try {
			$code = random_int( $min, $max );
		} catch ( \Exception $e ) {
			$code = wp_rand( $min, $max );
		}

		/**
		 * فیلتر کد تولیدشده.
		 *
		 * @param string $code
		 */
		return (string) apply_filters( 'zch/otp/code', (string) $code );
	}

	/**
	 * @param string $code
	 * @return string
	 */
	private static function hash_code( $code ) {
		$salt = wp_salt( 'auth' );

		return hash_hmac( 'sha256', (string) $code, $salt );
	}

	/**
	 * @param string $identifier
	 * @param string $type
	 * @param string $scene
	 * @param string $code
	 * @return int
	 */
	private static function insert( $identifier, $type, $scene, $code ) {
		global $wpdb;

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table(),
			[
				'identifier'      => $identifier,
				'identifier_type' => $type,
				'code_hash'       => self::hash_code( $code ),
				'scene'           => $scene,
				'attempts'        => 0,
				'ip'              => self::client_ip(),
				'ua_hash'         => hash( 'sha256', isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '' ),
				'expires_at'      => time() + self::ttl(),
				'consumed_at'     => null,
				'created_at'      => time(),
			],
			[ '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%d' ]
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * @param int    $id
	 * @param string $code
	 */
	private static function update_hash( $id, $code ) {
		global $wpdb;

		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table(),
			[
				'code_hash'  => self::hash_code( $code ),
				'attempts'   => 0,
				'expires_at' => time() + self::ttl(),
			],
			[ 'id' => $id ],
			[ '%s', '%d', '%d' ],
			[ '%d' ]
		);
	}

	/**
	 * @param string $identifier
	 * @param string $type
	 * @param string $scene
	 * @return array<string,mixed>|null
	 */
	private static function find_active( $identifier, $type, $scene ) {
		global $wpdb;

		$table = self::table();

		$row = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE identifier = %s AND identifier_type = %s AND scene = %s AND consumed_at IS NULL AND expires_at > %d ORDER BY id DESC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL
				$identifier,
				$type,
				$scene,
				time()
			),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	/**
	 * @param string $identifier
	 * @param string $type
	 * @return bool
	 */
	private static function is_locked( $identifier, $type = 'mobile' ) {
		$key  = self::lock_key( $identifier, $type );
		$lock = get_transient( $key );

		return false !== $lock && (int) $lock >= self::lock_threshold();
	}

	/**
	 * @param string $identifier
	 * @param string $type
	 */
	private static function register_failed_attempt( $identifier, $type = 'mobile' ) {
		$key  = self::lock_key( $identifier, $type );
		$lock = (int) get_transient( $key );

		set_transient( $key, $lock + 1, self::lock_duration() );

		/**
		 * پس از یک تلاش ناموفق اعتبارسنجی.
		 *
		 * @param string $identifier
		 * @param int    $lock
		 */
		do_action( 'zch/otp/failed_attempt', $identifier, $lock + 1 );
	}

	/**
	 * @param string $identifier
	 * @param string $type
	 */
	private static function clear_lock( $identifier, $type = 'mobile' ) {
		delete_transient( self::lock_key( $identifier, $type ) );
	}

	/**
	 * @return int
	 */
	private static function lock_threshold() {
		return min( 20, max( 3, (int) get_option( 'zch_otp_lock_threshold', 10 ) ) );
	}

	/**
	 * @return int
	 */
	private static function lock_duration() {
		return min( DAY_IN_SECONDS, max( 60, (int) get_option( 'zch_otp_lock_minutes', 15 ) * MINUTE_IN_SECONDS ) );
	}

	/**
	 * @param string $identifier
	 * @param string $type
	 * @return string
	 */
	private static function lock_key( $identifier, $type ) {
		return 'zch_otp_lock_' . substr( hash( 'sha256', $type . ':' . $identifier ), 0, 32 );
	}

	/**
	 * @param string $identifier
	 * @param string $type
	 * @return string
	 */
	private static function normalize_identifier( $identifier, $type ) {
		if ( 'mobile' === $type ) {
			return Mobile::normalize( $identifier );
		}

		$email = sanitize_email( (string) $identifier );

		return is_email( $email ) ? strtolower( $email ) : '';
	}

	/**
	 * @param string $scene
	 * @return string
	 */
	private static function template_for_scene( $scene ) {
		$map = [
			self::SCENE_LOGIN     => 'otp_login',
			self::SCENE_REGISTER  => 'otp_register',
			self::SCENE_RESET     => 'otp_reset',
			self::SCENE_PHONE     => 'otp_login',
			self::SCENE_WHOLESALE => 'otp_register',
		];

		return isset( $map[ $scene ] ) ? $map[ $scene ] : 'otp_login';
	}

	/**
	 * پیام خطای یکسان برای همهٔ حالت‌های شکست — جلوگیری از user enumeration.
	 *
	 * @return string
	 */
	public static function generic_error() {
		return __( 'کد واردشده معتبر نیست یا منقضی شده است.', 'zarincode-harir-core' );
	}

	/**
	 * @return string
	 */
	private static function client_ip() {
		$ip = '';

		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		/**
		 * فیلتر IP کلاینت (برای سرورهای پشت پروکسی).
		 *
		 * @param string $ip
		 */
		return (string) apply_filters( 'zch/client_ip', $ip );
	}
}
