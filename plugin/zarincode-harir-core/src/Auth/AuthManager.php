<?php
/**
 * مدیر احراز هویت — ورود با موبایل/ایمیل/نام کاربری، رمز عبور یا OTP.
 *
 * اصل حاکم: **بدون خلل**.
 * این کلاس هرگز `wp_authenticate` را جایگزین نمی‌کند. فقط روی فیلتر
 * `authenticate` با اولویت ۲۰ می‌نشیند و در سه حالت کار می‌کند:
 *   ۱. ورودی مربوط به ما نیست            → دست نمی‌زنیم
 *   ۲. وردپرس خودش موفق شده               → نتیجهٔ او را برمی‌گردانیم
 *   ۳. فقط در غیر این صورت                → مسیر اختصاصی ما
 *
 * نتیجه: کاربران قدیمی با همان username/password قبلی وارد می‌شوند و اگر
 * افزونه غیرفعال شود، هیچ‌چیز نمی‌شکند.
 *
 * @package Zarincode\Harir\Auth
 */

namespace Zarincode\Harir\Auth;

defined( 'ABSPATH' ) || exit;

use Zarincode\Harir\Helpers\Mobile;
use Zarincode\Harir\Roles\Roles;

/**
 * هستهٔ احراز هویت.
 */
final class AuthManager {

	/** نام فیلد توکن ورود OTP در فرم. */
	const TOKEN_FIELD = 'zch_login_token';

	/** پیشوند option توکن‌های یک‌بارمصرف ورود. */
	const TOKEN_PREFIX = 'zch_login_token_';

	/** عمر توکن ورود پس از تأیید OTP (ثانیه). */
	const TOKEN_TTL = 300;

	/** meta کلیدی که شمارهٔ موبایل احراز هویت در آن نگه داشته می‌شود. */
	const MOBILE_META = '_zch_mobile';

	/**
	 * ثبت هوک‌ها.
	 */
	public function register() {
		// مسیر اصلی: فیلتر authenticate با fall-through.
		add_filter( 'authenticate', [ $this, 'authenticate' ], 20, 3 );

		// اجازهٔ ورود با موبایل به‌جای نام کاربری.
		add_filter( 'wp_authenticate_user', [ $this, 'resolve_user_object' ], 20, 2 );

		// همگام‌سازی دوطرفهٔ موبایل با فیلد بومی ووکامرس.
		add_action( 'user_register', [ $this, 'sync_mobile_on_register' ], 20, 1 );
		add_action( 'profile_update', [ $this, 'sync_mobile_on_profile_update' ], 20, 2 );
		add_action( 'woocommerce_checkout_update_user_meta', [ $this, 'sync_mobile_from_checkout' ], 20, 2 );

		// افزودن موبایل به ستون کاربران ادمین.
		add_filter( 'manage_users_columns', [ $this, 'admin_columns' ] );
		add_filter( 'manage_users_custom_column', [ $this, 'admin_column_value' ], 10, 3 );

		// جست‌وجوی کاربر با موبایل از ادمین.
		add_filter( 'user_search_columns', [ $this, 'user_search_columns' ] );
	}

	/* ------------------------------------------------------------------
	 * مسیر اصلی احراز هویت
	 * ---------------------------------------------------------------- */

	/**
	 * فیلتر `authenticate` — با fall-through کامل.
	 *
	 * @param null|\WP_User|\WP_Error $user
	 * @param string                  $username
	 * @param string                  $password
	 * @return null|\WP_User|\WP_Error
	 */
	public function authenticate( $user, $username, $password ) {
		// ۱) اگر ورودی‌ها خالی است، این درخواست مال ما نیست.
		if ( '' === (string) $username && '' === (string) $password && ! $this->has_login_token() ) {
			return $user;
		}

		// ۲) اگر وردپرس یا افزونهٔ دیگری موفق شده، نتیجهٔ او معتبر است.
		if ( $user instanceof \WP_User ) {
			$this->after_successful_login( $user );
			return $user;
		}

		// ۳) مسیر OTP — توکن یک‌بارمصرف پس از تأیید کد.
		if ( $this->has_login_token() ) {
			$token_user = $this->consume_login_token();

			if ( $token_user instanceof \WP_User ) {
				$this->after_successful_login( $token_user );
				return $token_user;
			}

			return new \WP_Error(
				'zch_login_token_invalid',
				__( 'نشست ورود منقضی شده است. لطفاً دوباره کد تأیید دریافت کنید.', 'zarincode-harir-core' )
			);
		}

		// ۴) اگر خطای قبلی وجود دارد و ما اجازهٔ تغییر مسیر را نداریم، دست نمی‌زنیم.
		if ( is_wp_error( $user ) && ! $this->should_try_custom_path( $username ) ) {
			return $user;
		}

		// ۵) ورود با موبایل + رمز عبور.
		if ( LoginModes::allows_password() && LoginModes::login_uses_mobile() && Mobile::is_valid( Mobile::normalize( $username ) ) ) {
			$mobile_user = $this->find_user_by_mobile( $username );

			if ( $mobile_user instanceof \WP_User ) {
				$checked = wp_check_password( $password, $mobile_user->user_pass, $mobile_user->ID );

				if ( $checked ) {
					$this->after_successful_login( $mobile_user );
					return $mobile_user;
				}

				return new \WP_Error(
					'incorrect_password',
					__( '<strong>خطا</strong>: رمز عبور اشتباه است.', 'zarincode-harir-core' ),
					[ 'username' => $username ]
				);
			}
		}

		// ۶) در بقیهٔ حالات، خطای اصلی وردپرس را نگه می‌داریم.
		return $user;
	}

	/**
	 * آیا باید مسیر اختصاصی را امتحان کنیم؟
	 *
	 * اگر مدیر «حفظ ورود بومی» را خاموش نکرده باشد (پیش‌فرض: روشن)، فقط وقتی
	 * وارد مسیر خودمان می‌شویم که نام کاربری یک شمارهٔ موبایل باشد یا
	 * حالت ورود صراحتاً OTP باشد.
	 *
	 * @param string $username
	 * @return bool
	 */
	private function should_try_custom_path( $username ) {
		$mode = LoginModes::login_with();

		if ( 'username_password' === $mode ) {
			return false;
		}

		if ( Mobile::is_valid( Mobile::normalize( $username ) ) ) {
			return LoginModes::login_uses_mobile();
		}

		return 'mobile_otp' === $mode || 'any' === $mode;
	}

	/**
	 * اجازهٔ ورود با موبایل به‌جای نام کاربری.
	 *
	 * @param \WP_User|\WP_Error $user
	 * @param string             $password
	 * @return \WP_User|\WP_Error
	 */
	public function resolve_user_object( $user, $password ) {
		if ( $user instanceof \WP_User ) {
			return $user;
		}

		return $user;
	}

	/**
	 * پس از ورود موفق.
	 *
	 * @param \WP_User $user
	 */
	private function after_successful_login( $user ) {
		delete_user_meta( $user->ID, '_zch_failed_logins' );

		/**
		 * پس از ورود موفق یک کاربر.
		 *
		 * @param \WP_User $user
		 */
		do_action( 'zch/auth/login_success', $user );
	}

	/* ------------------------------------------------------------------
	 * توکن ورود یک‌بارمصرف (پس از تأیید OTP)
	 * ---------------------------------------------------------------- */

	/**
	 * ساخت توکن ورود پس از تأیید موفق کد.
	 *
	 * توکن یک‌بارمصرف است، به session متصل می‌شود و عمر کوتاهی دارد.
	 *
	 * @param int $user_id
	 * @return string
	 */
	public static function create_login_token( $user_id ) {
		$user_id = (int) $user_id;

		if ( ! $user_id ) {
			return '';
		}

		$token = bin2hex( random_bytes( 32 ) );
		$key   = self::TOKEN_PREFIX . hash( 'sha256', $token );

		set_transient(
			$key,
			[
				'user_id'    => $user_id,
				'session'    => self::session_fingerprint(),
				'created_at' => time(),
			],
			self::TOKEN_TTL
		);

		/**
		 * پس از ساخت توکن ورود.
		 *
		 * @param int $user_id
		 */
		do_action( 'zch/auth/login_token_created', $user_id );

		return $token;
	}

	/**
	 * مصرف توکن ورود — فقط یک بار.
	 *
	 * @param string|null $token
	 * @return \WP_User|null
	 */
	public function consume_login_token( $token = null ) {
		if ( null === $token ) {
			$token = isset( $_REQUEST[ self::TOKEN_FIELD ] ) // phpcs:ignore WordPress.Security.NonceVerification
				? sanitize_text_field( wp_unslash( $_REQUEST[ self::TOKEN_FIELD ] ) ) // phpcs:ignore WordPress.Security.NonceVerification
				: '';
		}

		if ( '' === $token || ! preg_match( '/^[a-f0-9]{64}$/', $token ) ) {
			return null;
		}

		$key  = self::TOKEN_PREFIX . hash( 'sha256', $token );
		$data = get_transient( $key );

		// یک‌بارمصرف: بلافاصله حذف می‌شود.
		delete_transient( $key );

		if ( ! is_array( $data ) || empty( $data['user_id'] ) ) {
			return null;
		}

		// اتصال به session — جلوگیری از سرقت توکن بین مرورگرها.
		if ( isset( $data['session'] ) && ! hash_equals( (string) $data['session'], self::session_fingerprint() ) ) {
			return null;
		}

		$user = get_user_by( 'id', (int) $data['user_id'] );

		return $user instanceof \WP_User ? $user : null;
	}

	/**
	 * آیا در این درخواست توکن ورود ارسال شده؟
	 *
	 * @return bool
	 */
	public function has_login_token() {
		return ! empty( $_REQUEST[ self::TOKEN_FIELD ] ); // phpcs:ignore WordPress.Security.NonceVerification
	}

	/**
	 * اثر انگشت session برای اتصال توکن.
	 *
	 * @return string
	 */
	private static function session_fingerprint() {
		$agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		return hash( 'sha256', $agent . '|' . wp_get_session_token() . '|' . self::client_ip() );
	}

	/**
	 * @return string
	 */
	private static function client_ip() {
		$ip = '';

		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return (string) apply_filters( 'zch/client_ip', $ip );
	}

	/* ------------------------------------------------------------------
	 * نگاشت موبایل ↔ کاربر
	 * ---------------------------------------------------------------- */

	/**
	 * یافتن کاربر از روی شمارهٔ موبایل.
	 *
	 * ترتیب جست‌وجو:
	 *   ۱. `_zch_mobile` (meta اختصاصی احراز هویت)
	 *   ۲. `billing_phone` (فیلد بومی ووکامرس)
	 *   ۳. `user_login` یا `user_email` اگر عددی باشد
	 *
	 * @param string $mobile
	 * @return \WP_User|null
	 */
	public function find_user_by_mobile( $mobile ) {
		$mobile = Mobile::normalize( $mobile );

		if ( '' === $mobile ) {
			return null;
		}

		$found = self::cached_lookup( $mobile );

		if ( false !== $found ) {
			return $found instanceof \WP_User ? $found : null;
		}

		$user = $this->query_user_by_meta( self::MOBILE_META, $mobile );

		if ( ! $user instanceof \WP_User ) {
			$user = $this->query_user_by_meta( 'billing_phone', $mobile );
		}

		if ( ! $user instanceof \WP_User ) {
			// سازگاری با سایت‌هایی که موبایل را در user_login ذخیره کرده‌اند.
			$by_login = get_user_by( 'login', $mobile );
			if ( $by_login instanceof \WP_User ) {
				$user = $by_login;
			}
		}

		self::cache_lookup( $mobile, $user instanceof \WP_User ? $user : 0 );

		/**
		 * فیلتر نتیجهٔ یافتن کاربر با موبایل.
		 *
		 * @param \WP_User|null $user
		 * @param string        $mobile
		 */
		return apply_filters( 'zch/auth/user_by_mobile', $user instanceof \WP_User ? $user : null, $mobile );
	}

	/**
	 * @param string $key
	 * @param string $value
	 * @return \WP_User|null
	 */
	private function query_user_by_meta( $key, $value ) {
		$query = new \WP_User_Query(
			[
				'meta_key'   => $key, // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value' => $value, // phpcs:ignore WordPress.DB.SlowDBQuery
				'number'     => 1,
				'fields'     => 'ID',
				'orderby'    => 'registered',
				'order'      => 'ASC',
			]
		);

		$ids = $query->get_results();

		if ( empty( $ids ) ) {
			return null;
		}

		$user = get_user_by( 'id', (int) reset( $ids ) );

		return $user instanceof \WP_User ? $user : null;
	}

	/**
	 * کش درون‌درخواستی برای جلوگیری از WP_User_Query تکراری.
	 *
	 * @var array<string,mixed>
	 */
	private static $lookup_cache = [];

	/**
	 * @param string $mobile
	 * @return mixed false یعنی هنوز کش نشده.
	 */
	private static function cached_lookup( $mobile ) {
		return isset( self::$lookup_cache[ $mobile ] ) ? self::$lookup_cache[ $mobile ] : false;
	}

	/**
	 * @param string          $mobile
	 * @param \WP_User|int    $value
	 */
	private static function cache_lookup( $mobile, $value ) {
		self::$lookup_cache[ $mobile ] = $value;
	}

	/**
	 * ذخیرهٔ موبایل احراز هویت برای یک کاربر.
	 *
	 * @param int    $user_id
	 * @param string $mobile
	 * @return bool
	 */
	public static function set_mobile( $user_id, $mobile ) {
		$user_id = (int) $user_id;
		$mobile  = Mobile::normalize( $mobile );

		if ( ! $user_id || '' === $mobile ) {
			return false;
		}

		// جلوگیری از تکرار: اگر موبایل به کاربر دیگری وصل است، اجازه نمی‌دهیم.
		$existing = self::lookup_mobile_owner( $mobile );

		if ( $existing && (int) $existing !== $user_id ) {
			/**
			 * هنگام تلاش برای ثبت موبایلی که به کاربر دیگری تعلق دارد.
			 *
			 * @param int    $user_id
			 * @param string $mobile
			 * @param int    $existing
			 */
			do_action( 'zch/auth/mobile_conflict', $user_id, $mobile, (int) $existing );

			return false;
		}

		update_user_meta( $user_id, self::MOBILE_META, $mobile );

		// همگام‌سازی با فیلد بومی ووکامرس تا checkout و حمل بدون تغییر کار کنند.
		if ( class_exists( '\WooCommerce' ) && '' === (string) get_user_meta( $user_id, 'billing_phone', true ) ) {
			update_user_meta( $user_id, 'billing_phone', $mobile );
		}

		self::$lookup_cache = [];

		/**
		 * پس از ثبت موبایل یک کاربر.
		 *
		 * @param int    $user_id
		 * @param string $mobile
		 */
		do_action( 'zch/auth/mobile_set', $user_id, $mobile );

		return true;
	}

	/**
	 * شمارهٔ موبایل ثبت‌شدهٔ یک کاربر.
	 *
	 * @param int|\WP_User $user
	 * @return string
	 */
	public static function get_mobile( $user ) {
		$user_id = $user instanceof \WP_User ? $user->ID : (int) $user;

		if ( ! $user_id ) {
			return '';
		}

		$mobile = Mobile::normalize( get_user_meta( $user_id, self::MOBILE_META, true ) );

		if ( '' === $mobile ) {
			$mobile = Mobile::normalize( get_user_meta( $user_id, 'billing_phone', true ) );
		}

		return $mobile;
	}

	/**
	 * @param string $mobile
	 * @return int|null شناسهٔ کاربر مالک یا null.
	 */
	private static function lookup_mobile_owner( $mobile ) {
		$mobile = Mobile::normalize( $mobile );

		if ( '' === $mobile ) {
			return null;
		}

		$query = new \WP_User_Query(
			[
				'meta_key'   => self::MOBILE_META, // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value' => $mobile, // phpcs:ignore WordPress.DB.SlowDBQuery
				'number'     => 1,
				'fields'     => 'ID',
			]
		);

		$ids = $query->get_results();

		return empty( $ids ) ? null : (int) reset( $ids );
	}

	/**
	 * @param int $user_id
	 */
	public function sync_mobile_on_register( $user_id ) {
		$user = get_user_by( 'id', (int) $user_id );

		if ( ! $user instanceof \WP_User ) {
			return;
		}

		$existing = get_user_meta( $user_id, self::MOBILE_META, true );

		if ( '' !== $existing ) {
			return;
		}

		$billing = Mobile::normalize( get_user_meta( $user_id, 'billing_phone', true ) );

		if ( '' !== $billing ) {
			update_user_meta( $user_id, self::MOBILE_META, $billing );
		}
	}

	/**
	 * @param int      $user_id
	 * @param \WP_User $user
	 */
	public function sync_mobile_on_profile_update( $user_id, $user ) {
		if ( ! $user instanceof \WP_User ) {
			return;
		}

		$mobile = self::get_mobile( $user );

		if ( '' !== $mobile ) {
			update_user_meta( $user_id, self::MOBILE_META, $mobile );

			if ( '' === (string) get_user_meta( $user_id, 'billing_phone', true ) ) {
				update_user_meta( $user_id, 'billing_phone', $mobile );
			}
		}
	}

	/**
	 * همگام‌سازی از checkout — تا کاربر مهمانی که موبایل می‌دهد قابل شناسایی شود.
	 *
	 * @param int   $customer_id
	 * @param array $data
	 */
	public function sync_mobile_from_checkout( $customer_id, $data ) {
		if ( ! $customer_id || empty( $data['billing_phone'] ) ) {
			return;
		}

		self::set_mobile( (int) $customer_id, $data['billing_phone'] );
	}

	/* ------------------------------------------------------------------
	 * ادمین
	 * ---------------------------------------------------------------- */

	/**
	 * @param array<string,string> $columns
	 * @return array<string,string>
	 */
	public function admin_columns( array $columns ) {
		$new = [];

		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;

			if ( 'name' === $key ) {
				$new['zch_mobile'] = __( 'موبایل', 'zarincode-harir-core' );
				$new['zch_type']   = __( 'نوع حساب', 'zarincode-harir-core' );
			}
		}

		return $new;
	}

	/**
	 * @param string $output
	 * @param string $column
	 * @param int    $user_id
	 * @return string
	 */
	public function admin_column_value( $output, $column, $user_id ) {
		if ( 'zch_mobile' === $column ) {
			$mobile = self::get_mobile( (int) $user_id );

			return '' !== $mobile ? '<code dir="ltr">' . esc_html( $mobile ) . '</code>' : '<span aria-hidden="true">—</span>';
		}

		if ( 'zch_type' === $column ) {
			$user = get_user_by( 'id', (int) $user_id );

			if ( ! $user instanceof \WP_User ) {
				return '';
			}

			if ( Roles::is_wholesale( $user ) ) {
				return '<span class="zch-badge zch-badge--wholesale">' . esc_html__( 'همکار عمده', 'zarincode-harir-core' ) . '</span>';
			}

			if ( Roles::is_pending_wholesale( $user ) ) {
				return '<span class="zch-badge zch-badge--pending">' . esc_html__( 'در انتظار تأیید', 'zarincode-harir-core' ) . '</span>';
			}

			if ( in_array( Roles::CUSTOMER, (array) $user->roles, true ) ) {
				return '<span class="zch-badge zch-badge--customer">' . esc_html__( 'مشتری', 'zarincode-harir-core' ) . '</span>';
			}

			return '';
		}

		return $output;
	}

	/**
	 * @param string[] $columns
	 * @return string[]
	 */
	public function user_search_columns( array $columns ) {
		$columns[] = self::MOBILE_META;

		return $columns;
	}
}
