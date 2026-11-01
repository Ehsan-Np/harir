<?php
/**
 * گردش ارتقاء و تنزیل حساب کاربری.
 *
 * مشتری → درخواست ارتقاء → بررسی مدیر → همکار عمده
 * همکار → درخواست تنزیل  → تأیید (خودکار یا با بررسی) → مشتری
 *
 * اصل مهم: **هیچ داده‌ای پاک نمی‌شود**. سفارش‌ها، علاقه‌مندی‌ها، کش‌بک،
 * تیکت‌ها و متاهای کسب‌وکار همه حفظ می‌شوند؛ فقط `wp_user_role` عوض می‌شود.
 *
 * @package Zarincode\Harir\Roles
 */

namespace Zarincode\Harir\Roles;

defined( 'ABSPATH' ) || exit;

use Zarincode\Harir\Db\Tables;

/**
 * مدیریت درخواست‌های تغییر نقش.
 */
final class RoleSwitch {

	const STATUS_PENDING  = 'pending';
	const STATUS_APPROVED = 'approved';
	const STATUS_REJECTED = 'rejected';
	const STATUS_NEEDS_MORE = 'needs_more_info';

	const DIRECTION_UPGRADE   = 'upgrade';
	const DIRECTION_DOWNGRADE = 'downgrade';

	/**
	 * ثبت هوک‌ها.
	 */
	public function register() {
		add_action( 'zch/role/approved', [ $this, 'flush_price_cache' ], 30, 2 );
		add_action( 'zch/role/rejected', [ $this, 'flush_price_cache_on_reject' ], 30, 3 );
	}

	/**
	 * @return string
	 */
	public static function table() {
		return Tables::name( 'role_requests' );
	}

	/**
	 * ثبت یک درخواست تغییر نقش.
	 *
	 * @param array<string,mixed> $data
	 * @return int|\WP_Error شناسهٔ درخواست.
	 */
	public function create( array $data ) {
		global $wpdb;

		$user_id = isset( $data['user_id'] ) ? (int) $data['user_id'] : get_current_user_id();

		if ( ! $user_id ) {
			return new \WP_Error( 'zch_no_user', __( 'کاربر شناسایی نشد.', 'zarincode-harir-core' ) );
		}

		$user = get_user_by( 'id', $user_id );

		if ( ! $user instanceof \WP_User ) {
			return new \WP_Error( 'zch_no_user', __( 'کاربر یافت نشد.', 'zarincode-harir-core' ) );
		}

		$direction = isset( $data['direction'] ) && self::DIRECTION_DOWNGRADE === $data['direction']
			? self::DIRECTION_DOWNGRADE
			: self::DIRECTION_UPGRADE;

		// جلوگیری از درخواست تکراری باز.
		if ( $this->has_open_request( $user_id ) ) {
			return new \WP_Error( 'zch_request_exists', __( 'یک درخواست باز از قبل دارید. تا بررسی آن صبر کنید.', 'zarincode-harir-core' ) );
		}

		$from_role = Roles::is_wholesale( $user ) ? Roles::WHOLESALE : Roles::CUSTOMER;
		$to_role   = self::DIRECTION_UPGRADE === $direction ? Roles::WHOLESALE : Roles::CUSTOMER;

		if ( self::DIRECTION_UPGRADE === $direction && ! $user->has_cap( Capabilities::REQUEST_UPGRADE ) ) {
			return new \WP_Error( 'zch_forbidden', __( 'شما اجازهٔ ثبت درخواست ارتقاء را ندارید.', 'zarincode-harir-core' ) );
		}

		if ( self::DIRECTION_DOWNGRADE === $direction && ! $user->has_cap( Capabilities::REQUEST_DOWNGRADE ) ) {
			return new \WP_Error( 'zch_forbidden', __( 'شما اجازهٔ ثبت درخواست تنزیل را ندارید.', 'zarincode-harir-core' ) );
		}

		// برای ارتقاء، تکمیل اطلاعات کسب‌وکار الزامی است.
		if ( self::DIRECTION_UPGRADE === $direction ) {
			$required = [ 'business_name', 'contact_person', 'phone' ];

			foreach ( $required as $field ) {
				if ( empty( $data[ $field ] ) ) {
					return new \WP_Error(
						'zch_missing_' . $field,
						sprintf(
							/* translators: %s: field label */
							__( 'تکمیل «%s» برای درخواست همکاری الزامی است.', 'zarincode-harir-core' ),
							$this->field_label( $field )
						)
					);
				}
			}
		}

		$documents = isset( $data['documents'] ) && is_array( $data['documents'] )
			? wp_json_encode( array_map( 'absint', $data['documents'] ) )
			: null;

		$row = [
			'user_id'        => $user_id,
			'direction'      => $direction,
			'from_role'      => $from_role,
			'to_role'        => $to_role,
			'business_name'  => isset( $data['business_name'] ) ? sanitize_text_field( $data['business_name'] ) : '',
			'business_type'  => isset( $data['business_type'] ) ? sanitize_text_field( $data['business_type'] ) : '',
			'contact_person' => isset( $data['contact_person'] ) ? sanitize_text_field( $data['contact_person'] ) : '',
			'national_id'    => isset( $data['national_id'] ) ? sanitize_text_field( $data['national_id'] ) : '',
			'city'           => isset( $data['city'] ) ? sanitize_text_field( $data['city'] ) : '',
			'phone'          => isset( $data['phone'] ) ? sanitize_text_field( $data['phone'] ) : '',
			'note'           => isset( $data['note'] ) ? sanitize_textarea_field( $data['note'] ) : null,
			'documents'      => $documents,
			'status'         => self::STATUS_PENDING,
			'created_at'     => time(),
		];

		$inserted = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table(),
			$row,
			[ '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d' ]
		);

		if ( ! $inserted ) {
			return new \WP_Error( 'zch_db_error', __( 'ثبت درخواست ناموفق بود. دوباره تلاش کنید.', 'zarincode-harir-core' ) );
		}

		$request_id = (int) $wpdb->insert_id;

		// ذخیرهٔ اطلاعات کسب‌وکار روی پروفایل کاربر (حتی پیش از تأیید).
		$this->store_business_info( $user_id, $data );

		$this->notify_admins( $request_id, $user );

		/**
		 * پس از ثبت یک درخواست تغییر نقش.
		 *
		 * @param int      $request_id
		 * @param int      $user_id
		 * @param string   $direction
		 */
		do_action( 'zch/role/request_created', $request_id, $user_id, $direction );

		return $request_id;
	}

	/**
	 * تأیید یک درخواست.
	 *
	 * @param int    $request_id
	 * @param string $admin_note
	 * @return true|\WP_Error
	 */
	public function approve( $request_id, $admin_note = '' ) {
		global $wpdb;

		$request = $this->get( (int) $request_id );

		if ( ! $request ) {
			return new \WP_Error( 'zch_not_found', __( 'درخواست یافت نشد.', 'zarincode-harir-core' ) );
		}

		if ( self::STATUS_PENDING !== $request['status'] && self::STATUS_NEEDS_MORE !== $request['status'] ) {
			return new \WP_Error( 'zch_already_reviewed', __( 'این درخواست قبلاً بررسی شده است.', 'zarincode-harir-core' ) );
		}

		$user = get_user_by( 'id', (int) $request['user_id'] );

		if ( ! $user instanceof \WP_User ) {
			return new \WP_Error( 'zch_no_user', __( 'کاربر یافت نشد.', 'zarincode-harir-core' ) );
		}

		$new_role = $request['to_role'];
		$old_role = $request['from_role'];

		// ⚠️ فقط نقش عوض می‌شود — هیچ متا، سفارش یا داده‌ای حذف نمی‌شود.
		$user->remove_role( $old_role );
		$user->remove_role( Roles::WHOLESALE_PENDING );
		$user->add_role( $new_role );

		update_user_meta( $user->ID, '_zch_wholesale_approved_at', time() );
		update_user_meta( $user->ID, '_zch_wholesale_approved_by', get_current_user_id() );

		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table(),
			[
				'status'      => self::STATUS_APPROVED,
				'admin_id'    => get_current_user_id(),
				'admin_note'  => sanitize_textarea_field( $admin_note ),
				'reviewed_at' => time(),
			],
			[ 'id' => (int) $request_id ],
			[ '%s', '%d', '%s', '%d' ],
			[ '%d' ]
		);

		/**
		 * پس از تأیید یک درخواست تغییر نقش.
		 *
		 * @param int    $user_id
		 * @param string $new_role
		 * @param array  $request
		 */
		do_action( 'zch/role/approved', (int) $request['user_id'], $new_role, $request );

		return true;
	}

	/**
	 * رد یک درخواست.
	 *
	 * @param int    $request_id
	 * @param string $reason
	 * @param bool   $fallback_to_customer آیا کاربر به نقش مشتری برگردد؟
	 * @return true|\WP_Error
	 */
	public function reject( $request_id, $reason = '', $fallback_to_customer = true ) {
		global $wpdb;

		$request = $this->get( (int) $request_id );

		if ( ! $request ) {
			return new \WP_Error( 'zch_not_found', __( 'درخواست یافت نشد.', 'zarincode-harir-core' ) );
		}

		$user = get_user_by( 'id', (int) $request['user_id'] );

		if ( ! $user instanceof \WP_User ) {
			return new \WP_Error( 'zch_no_user', __( 'کاربر یافت نشد.', 'zarincode-harir-core' ) );
		}

		// برای ارتقاء: کاربر در نقش «در انتظار تأیید» است → بازگشت به مشتری.
		// برای تنزیل: کاربر همکار است → اگر fallback خاموش باشد، همکار می‌ماند.
		if ( $fallback_to_customer && self::DIRECTION_UPGRADE === $request['direction'] ) {
			$user->remove_role( Roles::WHOLESALE_PENDING );

			if ( empty( array_intersect( [ Roles::CUSTOMER, Roles::WHOLESALE ], (array) $user->roles ) ) ) {
				$user->add_role( Roles::CUSTOMER );
			}
		}

		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table(),
			[
				'status'      => self::STATUS_REJECTED,
				'admin_id'    => get_current_user_id(),
				'admin_note'  => sanitize_textarea_field( $reason ),
				'reviewed_at' => time(),
			],
			[ 'id' => (int) $request_id ],
			[ '%s', '%d', '%s', '%d' ],
			[ '%d' ]
		);

		/**
		 * پس از رد یک درخواست تغییر نقش.
		 *
		 * @param int    $user_id
		 * @param string $reason
		 * @param string $old_role
		 * @param array  $request
		 */
		do_action( 'zch/role/rejected', (int) $request['user_id'], $reason, $request['from_role'], $request );

		return true;
	}

	/**
	 * درخواست تکمیل اطلاعات.
	 *
	 * @param int    $request_id
	 * @param string $note
	 * @return true|\WP_Error
	 */
	public function request_more_info( $request_id, $note = '' ) {
		global $wpdb;

		$request = $this->get( (int) $request_id );

		if ( ! $request ) {
			return new \WP_Error( 'zch_not_found', __( 'درخواست یافت نشد.', 'zarincode-harir-core' ) );
		}

		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table(),
			[
				'status'     => self::STATUS_NEEDS_MORE,
				'admin_id'   => get_current_user_id(),
				'admin_note' => sanitize_textarea_field( $note ),
			],
			[ 'id' => (int) $request_id ],
			[ '%s', '%d', '%s' ],
			[ '%d' ]
		);

		/**
		 * پس از درخواست تکمیل اطلاعات.
		 *
		 * @param int    $user_id
		 * @param string $note
		 */
		do_action( 'zch/role/needs_more_info', (int) $request['user_id'], $note );

		return true;
	}

	/**
	 * @param int $request_id
	 * @return array<string,mixed>|null
	 */
	public function get( $request_id ) {
		global $wpdb;

		$table = self::table();

		$row = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $request_id ), // phpcs:ignore WordPress.DB.PreparedSQL
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	/**
	 * آخرین درخواست یک کاربر.
	 *
	 * @param int $user_id
	 * @return array<string,mixed>|null
	 */
	public function latest_for_user( $user_id ) {
		global $wpdb;

		$table = self::table();

		$row = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare( "SELECT * FROM {$table} WHERE user_id = %d ORDER BY id DESC LIMIT 1", (int) $user_id ), // phpcs:ignore WordPress.DB.PreparedSQL
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	/**
	 * آیا کاربر درخواست باز دارد؟
	 *
	 * @param int $user_id
	 * @return bool
	 */
	public function has_open_request( $user_id ) {
		global $wpdb;

		$table = self::table();

		$count = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND status IN (%s, %s)", // phpcs:ignore WordPress.DB.PreparedSQL
				(int) $user_id,
				self::STATUS_PENDING,
				self::STATUS_NEEDS_MORE
			)
		);

		return $count > 0;
	}

	/**
	 * شمارش درخواست‌های در انتظار — برای badge ادمین.
	 *
	 * @return int
	 */
	public function count_pending() {
		global $wpdb;

		$table = self::table();

		return (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s", self::STATUS_PENDING ) // phpcs:ignore WordPress.DB.PreparedSQL
		);
	}

	/**
	 * صفحه‌بندی درخواست‌ها.
	 *
	 * @param string $status
	 * @param int    $page
	 * @param int    $per_page
	 * @return array{items:array<int,array<string,mixed>>,total:int}
	 */
	public function paginate( $status = '', $page = 1, $per_page = 20 ) {
		global $wpdb;

		$table    = self::table();
		$offset   = ( max( 1, (int) $page ) - 1 ) * min( 100, max( 1, (int) $per_page ) );
		$per_page = min( 100, max( 1, (int) $per_page ) );

		if ( '' !== $status ) {
			$total = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s", $status ) // phpcs:ignore WordPress.DB.PreparedSQL
			);
			$items = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prepare( "SELECT * FROM {$table} WHERE status = %s ORDER BY id DESC LIMIT %d OFFSET %d", $status, $per_page, $offset ), // phpcs:ignore WordPress.DB.PreparedSQL
				ARRAY_A
			);
		} else {
			$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$items = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d", $per_page, $offset ), // phpcs:ignore WordPress.DB.PreparedSQL
				ARRAY_A
			);
		}

		return [
			'items' => is_array( $items ) ? $items : [],
			'total' => $total,
		];
	}

	/* ------------------------------------------------------------------
	 * داخلی
	 * ---------------------------------------------------------------- */

	/**
	 * @param int                  $user_id
	 * @param array<string,mixed>  $data
	 */
	private function store_business_info( $user_id, array $data ) {
		$fields = [
			'business_name'  => '_zch_business_name',
			'business_type'  => '_zch_business_type',
			'contact_person' => '_zch_contact_person',
			'national_id'    => '_zch_national_id',
			'city'           => '_zch_business_city',
		];

		foreach ( $fields as $key => $meta_key ) {
			if ( isset( $data[ $key ] ) ) {
				update_user_meta( $user_id, $meta_key, sanitize_text_field( $data[ $key ] ) );
			}
		}

		if ( isset( $data['phone'] ) ) {
			\Zarincode\Harir\Auth\AuthManager::set_mobile( $user_id, $data['phone'] );
		}
	}

	/**
	 * @param int      $request_id
	 * @param \WP_User $user
	 */
	private function notify_admins( $request_id, $user ) {
		$recipients = get_users(
			[
				'role__in' => [ 'administrator', 'shop_manager' ],
				'fields'   => [ 'user_email' ],
			]
		);

		if ( empty( $recipients ) ) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: business or display name */
			__( 'درخواست همکاری تازه: %s', 'zarincode-harir-core' ),
			(string) get_user_meta( $user->ID, '_zch_business_name', true ) ?: $user->display_name
		);

		$body = sprintf(
			/* translators: 1: request id, 2: user email, 3: admin URL */
			__( "درخواست تغییر نقش #%1\$s ثبت شد.\nکاربر: %2\$s\nبررسی: %3\$s", 'zarincode-harir-core' ),
			$request_id,
			$user->user_email,
			admin_url( 'admin.php?page=zch-role-requests&request=' . $request_id )
		);

		foreach ( $recipients as $recipient ) {
			wp_mail( $recipient->user_email, $subject, $body ); // phpcs:ignore WordPressVIPMinimum
		}

		/**
		 * پس از اطلاع‌رسانی به مدیران.
		 *
		 * @param int $request_id
		 */
		do_action( 'zch/role/admin_notified', $request_id );
	}

	/**
	 * پس از تأیید نقش، کش‌های قیمت باید پاک شوند.
	 *
	 * @param int    $user_id
	 * @param string $new_role
	 */
	public function flush_price_cache( $user_id, $new_role ) {
		$this->flush_caches();
	}

	/**
	 * @param int    $user_id
	 * @param string $reason
	 * @param string $old_role
	 */
	public function flush_price_cache_on_reject( $user_id, $reason = '', $old_role = '' ) {
		$this->flush_caches();
	}

	/**
	 * پاک‌سازی کش‌های مرتبط با قیمت تا کاربر قیمت تازه را ببیند.
	 */
	private function flush_caches() {
		// سبد باید دوباره محاسبه شود.
		if ( function_exists( 'WC' ) && WC()->cart ) {
			WC()->cart->empty_cart();
		}

		if ( function_exists( 'wc_delete_product_transients' ) ) {
			wc_delete_product_transients();
		}

		wp_cache_flush_group( 'zch_prices' );

		/**
		 * پس از تغییر نقش، برای پاک‌سازی کش افزونه‌های دیگر.
		 */
		do_action( 'zch/role/flush_caches' );
	}

	/**
	 * @param string $field
	 * @return string
	 */
	private function field_label( $field ) {
		$labels = [
			'business_name'  => __( 'نام کسب‌وکار', 'zarincode-harir-core' ),
			'business_type'  => __( 'نوع همکاری', 'zarincode-harir-core' ),
			'contact_person' => __( 'نام مسئول پیگیری', 'zarincode-harir-core' ),
			'national_id'    => __( 'شناسهٔ ملی / کد ملی', 'zarincode-harir-core' ),
			'city'           => __( 'شهر', 'zarincode-harir-core' ),
			'phone'          => __( 'شمارهٔ موبایل', 'zarincode-harir-core' ),
		];

		return isset( $labels[ $field ] ) ? $labels[ $field ] : $field;
	}
}
