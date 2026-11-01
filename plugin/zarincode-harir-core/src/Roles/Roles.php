<?php
/**
 * ثبت و مدیریت نقش‌های کاربری اختصاصی حریر رویا.
 *
 * دو نقش تازه:
 *  - zch_wholesale         همکار عمده (تأییدشده) — قیمت عمده را می‌بیند و می‌خرد
 *  - zch_wholesale_pending در انتظار تأیید — هیچ دسترسی قیمتی ندارد
 *
 * @package Zarincode\Harir\Roles
 */

namespace Zarincode\Harir\Roles;

defined( 'ABSPATH' ) || exit;

/**
 * مدیر نقش‌های کاربری.
 */
final class Roles {

	/** نقش همکار عمدهٔ تأییدشده. */
	const WHOLESALE = 'zch_wholesale';

	/** نقش همکار عمدهٔ در انتظار تأیید. */
	const WHOLESALE_PENDING = 'zch_wholesale_pending';

	/** نقش مشتری عادی ووکامرس. */
	const CUSTOMER = 'customer';

	/** کلید option برای ثبت وضعیت راه‌اندازی اولیه. */
	const INSTALL_FLAG = 'zch_roles_installed';

	/**
	 * ثبت هوک‌ها.
	 */
	public function register(): void {
		add_action( 'init', [ $this, 'maybe_install' ], 1 );
		add_filter( 'woocommerce_admin_features', [ $this, 'keep_woo_features' ] );
		add_filter( 'editable_roles', [ $this, 'protect_pending_role' ] );
		add_action( 'set_user_role', [ $this, 'log_role_change' ], 10, 3 );
		add_filter( 'zch/user/roles_labels', [ $this, 'register_role_labels' ] );
	}

	/**
	 * ساخت نقش‌ها در اولین اجرا (و ترمیم در صورت حذف دستی).
	 *
	 * روی هوک `init` اجرا می‌شود نه فقط در activation، تا اگر کاربری نقش را
	 * اشتباهاً حذف کرد یا سایت را migrate کرد، ساختار خودکار بازسازی شود.
	 */
	public function maybe_install(): void {
		$this->register_roles();

		if ( ! get_option( self::INSTALL_FLAG ) ) {
			$this->assign_capabilities_to_core_roles();
			update_option( self::INSTALL_FLAG, ZCH_CORE_VERSION, false );
		}
	}

	/**
	 * ساخت نقش‌های اختصاصی.
	 */
	private function register_roles(): void {
		$base = [ 'read' => true ];

		add_role(
			self::WHOLESALE,
			__( 'همکار عمده', 'zarincode-harir-core' ),
			array_merge(
				$base,
				[
					// قابلیت‌های ووکامرس تا نقش مانند customer رفتار کند.
					'edit_posts' => false,
				]
			)
		);

		add_role(
			self::WHOLESALE_PENDING,
			__( 'همکار عمده — در انتظار تأیید', 'zarincode-harir-core' ),
			$base
		);
	}

	/**
	 * اختصاص capabilityهای اختصاصی به نقش‌های موجود.
	 */
	private function assign_capabilities_to_core_roles(): void {
		foreach ( Capabilities::map() as $role => $caps ) {
			$object = get_role( $role );

			if ( ! $object instanceof \WP_Role ) {
				continue;
			}

			foreach ( $caps as $cap ) {
				$object->add_cap( $cap );
			}
		}
	}

	/**
	 * نگاشت برچسب فارسی نقش‌ها برای نمایش در پنل و ادمین.
	 *
	 * @param array<string,string> $labels
	 * @return array<string,string>
	 */
	public function register_role_labels( array $labels ): array {
		$labels[ self::CUSTOMER ]          = __( 'مشتری', 'zarincode-harir-core' );
		$labels[ self::WHOLESALE ]         = __( 'همکار عمده', 'zarincode-harir-core' );
		$labels[ self::WHOLESALE_PENDING ] = __( 'همکار عمده (در انتظار تأیید)', 'zarincode-harir-core' );

		return $labels;
	}

	/**
	 * نقش‌های اختصاصی نباید در فهرت نقش‌های قابل‌انتساب ادمین دیده شوند
	 * تا مدیر اشتباهاً کاربری را دستی به «در انتظار تأیید» منتقل نکند.
	 *
	 * @param array<string,array> $roles
	 * @return array<string,array>
	 */
	public function protect_pending_role( array $roles ): array {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( $screen && false !== strpos( (string) $screen->id, 'user' ) ) {
			unset( $roles[ self::WHOLESALE_PENDING ] );
		}

		return $roles;
	}

	/**
	 * نگه داشتن قابلیت‌های ووکامرس برای نقش‌های تازه.
	 *
	 * @param mixed $features
	 * @return mixed
	 */
	public function keep_woo_features( $features ) {
		return $features;
	}

	/**
	 * ثبت تغییر نقش کاربر برای حسابرسی.
	 *
	 * @param int      $user_id
	 * @param string   $new_role
	 * @param string[] $old_roles
	 */
	public function log_role_change( $user_id, $new_role, $old_roles ): void {
		if ( ! is_array( $old_roles ) ) {
			$old_roles = (array) $old_roles;
		}

		$relevant = array_values(
			array_filter(
				array_merge( $old_roles, [ $new_role ] ),
				static function ( $role ) {
					return in_array( $role, [ self::CUSTOMER, self::WHOLESALE, self::WHOLESALE_PENDING ], true );
				}
			)
		);

		if ( empty( $relevant ) ) {
			return;
		}

		update_user_meta(
			$user_id,
			'_zch_last_role_change',
			[
				'from'  => implode( ',', $old_roles ),
				'to'    => $new_role,
				'time'  => time(),
				'actor' => get_current_user_id(),
			]
		);

		/**
		 * پس از تغییر نقش یک کاربر بین نقش‌های حریر.
		 *
		 * @param int      $user_id
		 * @param string   $new_role
		 * @param string[] $old_roles
		 */
		do_action( 'zch/role/changed', (int) $user_id, $new_role, $old_roles );
	}

	/**
	 * آیا کاربر نقش همکار عمدهٔ **تأییدشده** دارد؟
	 *
	 * @param int|\WP_User|null $user
	 */
	public static function is_wholesale( $user = null ): bool {
		$user = $user instanceof \WP_User ? $user : ( $user ? get_user_by( 'id', $user ) : wp_get_current_user() );

		if ( ! $user instanceof \WP_User || ! $user->exists() ) {
			return false;
		}

		/**
		 * فیلتر تشخیص نقش همکار.
		 *
		 * @param bool     $is
		 * @param \WP_User $user
		 */
		return (bool) apply_filters( 'zch/user/is_wholesale', in_array( self::WHOLESALE, (array) $user->roles, true ), $user );
	}

	/**
	 * آیا کاربر در انتظار تأیید همکاری است؟
	 *
	 * @param int|\WP_User|null $user
	 */
	public static function is_pending_wholesale( $user = null ): bool {
		$user = $user instanceof \WP_User ? $user : ( $user ? get_user_by( 'id', $user ) : wp_get_current_user() );

		return $user instanceof \WP_User && in_array( self::WHOLESALE_PENDING, (array) $user->roles, true );
	}

	/**
	 * آیا کاربر اجازهٔ دیدن قیمت عمده را دارد؟
	 *
	 * تنها منبع حقیقت برای کل افزونه — هر ماژول دیگر باید همین را صدا بزند.
	 *
	 * @param int|\WP_User|null $user
	 */
	public static function can_view_wholesale_price( $user = null ): bool {
		if ( ! is_user_logged_in() && null === $user ) {
			return false;
		}

		$user = $user instanceof \WP_User ? $user : ( $user ? get_user_by( 'id', $user ) : wp_get_current_user() );

		if ( ! $user instanceof \WP_User || ! $user->exists() ) {
			return false;
		}

		// نقش در انتظار تأیید عمداً قیمت را نمی‌بیند.
		if ( self::is_pending_wholesale( $user ) ) {
			return false;
		}

		$can = $user->has_cap( Capabilities::VIEW_WHOLESALE_PRICE );

		/**
		 * فیلتر نهایی دسترسی به قیمت عمده.
		 *
		 * @param bool     $can
		 * @param \WP_User $user
		 */
		return (bool) apply_filters( 'zch/user/can_view_wholesale_price', $can, $user );
	}

	/**
	 * آیا کاربر اجازهٔ خرید با شرایط عمده را دارد؟
	 *
	 * @param int|\WP_User|null $user
	 */
	public static function can_buy_wholesale( $user = null ): bool {
		$user = $user instanceof \WP_User ? $user : ( $user ? get_user_by( 'id', $user ) : wp_get_current_user() );

		if ( ! $user instanceof \WP_User || ! $user->exists() ) {
			return false;
		}

		return (bool) $user->has_cap( Capabilities::BUY_WHOLESALE );
	}

	/**
	 * برچسب فارسی نقش کاربر برای نمایش.
	 *
	 * @param int|\WP_User|null $user
	 */
	public static function role_label( $user = null ): string {
		if ( self::is_wholesale( $user ) ) {
			return __( 'همکار عمده', 'zarincode-harir-core' );
		}
		if ( self::is_pending_wholesale( $user ) ) {
			return __( 'در انتظار تأیید همکاری', 'zarincode-harir-core' );
		}

		return __( 'مشتری', 'zarincode-harir-core' );
	}

	/**
	 * غیرفعال‌سازی افزونه.
	 *
	 * عمداً **هیچ نقشی حذف نمی‌شود** و هیچ داده‌ای پاک نمی‌گردد؛ چون اگر
	 * مدیر افزونه را موقتاً غیرفعال کند، کاربران همکار نباید دسترسی و داده‌های
	 * خود را از دست بدهند. پاک‌سازی کامل فقط در `uninstall.php` انجام می‌شود.
	 */
	public static function deactivate(): void {
		flush_rewrite_rules();

		/**
		 * هنگام غیرفعال‌سازی افزونه.
		 */
		do_action( 'zch/deactivated' );
	}
}
