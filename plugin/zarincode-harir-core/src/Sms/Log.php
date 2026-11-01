<?php
/**
 * گزارش پیامک‌های ارسال‌شده.
 *
 * @package Zarincode\Harir\Sms
 */

namespace Zarincode\Harir\Sms;

defined( 'ABSPATH' ) || exit;

use Zarincode\Harir\Db\Tables;
use Zarincode\Harir\Helpers\Mobile;

/**
 * ثبت و بازیابی لاگ پیامک.
 */
final class Log {

	/**
	 * نام جدول.
	 *
	 * @return string
	 */
	public static function table() {
		return Tables::name( 'sms_log' );
	}

	/**
	 * ثبت یک ارسال.
	 *
	 * @param array<string,mixed> $data
	 * @return int شناسهٔ رکورد یا ۰.
	 */
	public static function add( array $data ) {
		global $wpdb;

		$receptor = isset( $data['receptor'] ) ? Mobile::normalize( $data['receptor'] ) : '';

		// ذخیرهٔ شماره به‌صورت پوشیده اگر مدیر خواسته باشد (حریم خصوصی).
		$mask = (bool) get_option( 'zch_sms_mask_log', false );

		$row = [
			'gateway'      => isset( $data['gateway'] ) ? sanitize_key( $data['gateway'] ) : '',
			'template_key' => isset( $data['template_key'] ) ? sanitize_key( $data['template_key'] ) : '',
			'receptor'     => $mask ? Mobile::mask( $receptor ) : $receptor,
			'sender'       => isset( $data['sender'] ) ? sanitize_text_field( $data['sender'] ) : '',
			'message'      => isset( $data['message'] ) ? sanitize_textarea_field( $data['message'] ) : '',
			'message_id'   => isset( $data['message_id'] ) ? sanitize_text_field( $data['message_id'] ) : '',
			'status'       => isset( $data['status'] ) ? sanitize_key( $data['status'] ) : 'sent',
			'status_code'  => isset( $data['status_code'] ) ? sanitize_text_field( $data['status_code'] ) : '',
			'cost'         => isset( $data['cost'] ) ? (float) $data['cost'] : 0,
			'user_id'      => isset( $data['user_id'] ) ? (int) $data['user_id'] : 0,
			'error'        => isset( $data['error'] ) ? sanitize_textarea_field( $data['error'] ) : null,
			'created_at'   => time(),
		];

		$format = [ '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%d', '%s', '%d' ];

		$inserted = $wpdb->insert( self::table(), $row, $format ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		/**
		 * پس از ثبت یک پیامک در لاگ.
		 *
		 * @param int|false $inserted
		 * @param array     $row
		 */
		do_action( 'zch/sms/logged', $inserted, $row );

		return $inserted ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * تعداد ارسال‌های اخیر برای یک شماره (برای محدودسازی نرخ).
	 *
	 * @param string $mobile
	 * @param int    $since  timestamp.
	 * @return int
	 */
	public static function count_for_mobile( $mobile, $since ) {
		global $wpdb;

		$mobile = Mobile::normalize( $mobile );

		if ( '' === $mobile ) {
			return 0;
		}

		$table = self::table();

		return (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE receptor = %s AND created_at >= %d", $mobile, (int) $since )
		);
	}

	/**
	 * صفحه‌بندی لاگ‌ها.
	 *
	 * @param int $page
	 * @param int $per_page
	 * @return array{items:array<int,array<string,mixed>>,total:int}
	 */
	public static function paginate( $page = 1, $per_page = 20 ) {
		global $wpdb;

		$table    = self::table();
		$page     = max( 1, (int) $page );
		$per_page = min( 100, max( 1, (int) $per_page ) );
		$offset   = ( $page - 1 ) * $per_page;

		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		$items = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d", $per_page, $offset ), // phpcs:ignore WordPress.DB.PreparedSQL
			ARRAY_A
		);

		return [
			'items' => is_array( $items ) ? $items : [],
			'total' => $total,
		];
	}

	/**
	 * حذف لاگ‌های قدیمی‌تر از تعداد روزهای مشخص.
	 *
	 * @param int $days
	 * @return int تعداد حذف‌شده.
	 */
	public static function prune( $days = 90 ) {
		global $wpdb;

		$table = self::table();
		$limit = time() - ( (int) $days * DAY_IN_SECONDS );

		return (int) $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare( "DELETE FROM {$table} WHERE created_at < %d", $limit )
		);
	}
}
