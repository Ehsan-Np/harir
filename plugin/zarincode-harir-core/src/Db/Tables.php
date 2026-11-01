<?php
/**
 * تعریف جدول‌های اختصاصی هسته.
 *
 * همهٔ جدول‌ها با پیشوند `zch_` و داخل prefix وردپرس ساخته می‌شوند.
 *
 * @package Zarincode\Harir\Db
 */

namespace Zarincode\Harir\Db;

defined( 'ABSPATH' ) || exit;

/**
 * ثبت‌کنندهٔ ساختار جدول‌ها.
 */
final class Tables {

	/** نسخهٔ ساختار دیتابیس — برای اجرای migration در ارتقاها. */
	const SCHEMA_VERSION = '1.0.0';

	/** کلید option نسخهٔ نصب‌شده. */
	const VERSION_OPTION = 'zch_db_version';

	/**
	 * نام کامل یک جدول.
	 *
	 * @param string $name نام کوتاه بدون پیشوند.
	 * @return string
	 */
	public static function name( $name ) {
		global $wpdb;

		return $wpdb->prefix . 'zch_' . $name;
	}

	/**
	 * تعریف SQL همهٔ جدول‌ها (برای dbDelta).
	 *
	 * @return array<string,string> نام جدول => CREATE TABLE
	 */
	public static function schema() {
		global $wpdb;

		$charset = $wpdb->get_charset_collate();
		$schemas = [];

		// کدهای یک‌بارمصرف.
		$t = self::name( 'otp' );
		$schemas[ $t ] = "CREATE TABLE {$t} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			identifier VARCHAR(190) NOT NULL,
			identifier_type VARCHAR(20) NOT NULL DEFAULT 'mobile',
			code_hash CHAR(64) NOT NULL,
			scene VARCHAR(40) NOT NULL DEFAULT 'login',
			attempts TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
			ip VARCHAR(45) NOT NULL DEFAULT '',
			ua_hash CHAR(64) NOT NULL DEFAULT '',
			expires_at INT(11) UNSIGNED NOT NULL,
			consumed_at INT(11) UNSIGNED NULL DEFAULT NULL,
			created_at INT(11) UNSIGNED NOT NULL,
			PRIMARY KEY  (id),
			KEY lookup (identifier,identifier_type,scene,consumed_at),
			KEY expires_at (expires_at),
			KEY ip (ip)
		) {$charset};";

		// گزارش پیامک‌ها.
		$t = self::name( 'sms_log' );
		$schemas[ $t ] = "CREATE TABLE {$t} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			gateway VARCHAR(40) NOT NULL,
			template_key VARCHAR(60) NOT NULL DEFAULT '',
			receptor VARCHAR(30) NOT NULL,
			sender VARCHAR(40) NOT NULL DEFAULT '',
			message TEXT NULL,
			message_id VARCHAR(190) NOT NULL DEFAULT '',
			status VARCHAR(30) NOT NULL DEFAULT 'sent',
			status_code VARCHAR(40) NOT NULL DEFAULT '',
			cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
			user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			error TEXT NULL,
			created_at INT(11) UNSIGNED NOT NULL,
			PRIMARY KEY  (id),
			KEY receptor (receptor),
			KEY user_id (user_id),
			KEY status (status),
			KEY created_at (created_at),
			KEY message_id (message_id)
		) {$charset};";

		// درخواست‌های تغییر نقش (ارتقاء/تنزیل).
		$t = self::name( 'role_requests' );
		$schemas[ $t ] = "CREATE TABLE {$t} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			direction VARCHAR(12) NOT NULL DEFAULT 'upgrade',
			from_role VARCHAR(60) NOT NULL DEFAULT '',
			to_role VARCHAR(60) NOT NULL DEFAULT '',
			business_name VARCHAR(190) NOT NULL DEFAULT '',
			business_type VARCHAR(60) NOT NULL DEFAULT '',
			contact_person VARCHAR(190) NOT NULL DEFAULT '',
			national_id VARCHAR(30) NOT NULL DEFAULT '',
			city VARCHAR(100) NOT NULL DEFAULT '',
			phone VARCHAR(20) NOT NULL DEFAULT '',
			note TEXT NULL,
			documents LONGTEXT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			admin_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			admin_note TEXT NULL,
			reviewed_at INT(11) UNSIGNED NULL DEFAULT NULL,
			created_at INT(11) UNSIGNED NOT NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY status (status),
			KEY created_at (created_at)
		) {$charset};";

		// تیکت‌های پشتیبانی.
		$t = self::name( 'tickets' );
		$schemas[ $t ] = "CREATE TABLE {$t} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			ticket_key VARCHAR(30) NOT NULL,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			department VARCHAR(40) NOT NULL DEFAULT 'general',
			priority VARCHAR(20) NOT NULL DEFAULT 'normal',
			subject VARCHAR(255) NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'open',
			order_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			product_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			last_reply_at INT(11) UNSIGNED NULL DEFAULT NULL,
			last_actor VARCHAR(20) NOT NULL DEFAULT 'user',
			created_at INT(11) UNSIGNED NOT NULL,
			updated_at INT(11) UNSIGNED NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY ticket_key (ticket_key),
			KEY user_id (user_id),
			KEY status (status),
			KEY department (department),
			KEY updated_at (updated_at)
		) {$charset};";

		// پیام‌های تیکت.
		$t = self::name( 'ticket_messages' );
		$schemas[ $t ] = "CREATE TABLE {$t} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			ticket_id BIGINT(20) UNSIGNED NOT NULL,
			author_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			author_role VARCHAR(20) NOT NULL DEFAULT 'user',
			body LONGTEXT NOT NULL,
			attachments LONGTEXT NULL,
			is_internal TINYINT(1) NOT NULL DEFAULT 0,
			created_at INT(11) UNSIGNED NOT NULL,
			PRIMARY KEY  (id),
			KEY ticket_id (ticket_id),
			KEY created_at (created_at)
		) {$charset};";

		// کیف پول کش‌بک.
		$t = self::name( 'cashback_tx' );
		$schemas[ $t ] = "CREATE TABLE {$t} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
			balance_after DECIMAL(14,2) NOT NULL DEFAULT 0.00,
			type VARCHAR(20) NOT NULL DEFAULT 'earn',
			source VARCHAR(40) NOT NULL DEFAULT '',
			ref_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			note VARCHAR(255) NOT NULL DEFAULT '',
			expires_at INT(11) UNSIGNED NULL DEFAULT NULL,
			created_at INT(11) UNSIGNED NOT NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY type (type),
			KEY ref (source,ref_id),
			KEY created_at (created_at)
		) {$charset};";

		// چرخش‌های گردونهٔ شانس.
		$t = self::name( 'wheel_spins' );
		$schemas[ $t ] = "CREATE TABLE {$t} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			prize_key VARCHAR(60) NOT NULL,
			prize_label VARCHAR(190) NOT NULL DEFAULT '',
			coupon_code VARCHAR(60) NOT NULL DEFAULT '',
			value DECIMAL(14,2) NOT NULL DEFAULT 0.00,
			order_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			used_at INT(11) UNSIGNED NULL DEFAULT NULL,
			created_at INT(11) UNSIGNED NOT NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY created_at (created_at)
		) {$charset};";

		// کلیک‌ها و ارجاع‌های همکاری در فروش.
		$t = self::name( 'affiliate' );
		$schemas[ $t ] = "CREATE TABLE {$t} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			affiliate_user_id BIGINT(20) UNSIGNED NOT NULL,
			referral_code VARCHAR(40) NOT NULL,
			referred_user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			order_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			clicks INT(11) UNSIGNED NOT NULL DEFAULT 0,
			commission DECIMAL(14,2) NOT NULL DEFAULT 0.00,
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			ip VARCHAR(45) NOT NULL DEFAULT '',
			created_at INT(11) UNSIGNED NOT NULL,
			PRIMARY KEY  (id),
			KEY affiliate_user_id (affiliate_user_id),
			KEY referral_code (referral_code),
			KEY order_id (order_id),
			KEY status (status)
		) {$charset};";

		// اعلان‌های درون‌سایتی.
		$t = self::name( 'notifications' );
		$schemas[ $t ] = "CREATE TABLE {$t} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			type VARCHAR(40) NOT NULL DEFAULT 'info',
			title VARCHAR(190) NOT NULL,
			body TEXT NULL,
			url VARCHAR(255) NOT NULL DEFAULT '',
			is_read TINYINT(1) NOT NULL DEFAULT 0,
			created_at INT(11) UNSIGNED NOT NULL,
			PRIMARY KEY  (id),
			KEY user_read (user_id,is_read),
			KEY created_at (created_at)
		) {$charset};";

		// درخواست‌های قیمت عمده (RFQ).
		$t = self::name( 'rfq_items' );
		$schemas[ $t ] = "CREATE TABLE {$t} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			rfq_id BIGINT(20) UNSIGNED NOT NULL,
			product_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			product_title VARCHAR(255) NOT NULL DEFAULT '',
			color VARCHAR(60) NOT NULL DEFAULT '',
			size VARCHAR(60) NOT NULL DEFAULT '',
			quantity INT(11) UNSIGNED NOT NULL DEFAULT 1,
			unit_price DECIMAL(14,2) NOT NULL DEFAULT 0.00,
			note TEXT NULL,
			PRIMARY KEY  (id),
			KEY rfq_id (rfq_id)
		) {$charset};";

		return $schemas;
	}

	/**
	 * فهرست نام کوتاه جدول‌ها (برای پاک‌سازی در uninstall).
	 *
	 * @return string[]
	 */
	public static function short_names() {
		return [
			'otp',
			'sms_log',
			'role_requests',
			'tickets',
			'ticket_messages',
			'cashback_tx',
			'wheel_spins',
			'affiliate',
			'notifications',
			'rfq_items',
		];
	}
}
