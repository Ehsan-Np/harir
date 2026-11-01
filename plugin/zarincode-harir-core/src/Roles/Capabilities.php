<?php
/**
 * تعریف و نگاشت قابلیت‌های اختصاصی پروژه.
 *
 * همهٔ قابلیت‌ها با پیشوند `zch_` ثبت می‌شوند تا با هیچ افزونهٔ دیگری تداخل نکنند.
 *
 * @package Zarincode\Harir\Roles
 */

namespace Zarincode\Harir\Roles;

defined( 'ABSPATH' ) || exit;

/**
 * registry مرکزی capabilityها.
 */
final class Capabilities {

	/** دیدن قیمت عمده. */
	const VIEW_WHOLESALE_PRICE = 'zch_view_wholesale_price';

	/** خرید با شرایط عمده (min/max/step عمده). */
	const BUY_WHOLESALE = 'zch_buy_wholesale';

	/** ثبت درخواست قیمت (RFQ). */
	const SUBMIT_RFQ = 'zch_submit_rfq';

	/** دیدن پیش‌فاکتورهای عمده. */
	const VIEW_INVOICES = 'zch_view_invoices';

	/** ثبت درخواست ارتقاء به حساب همکار. */
	const REQUEST_UPGRADE = 'zch_request_upgrade';

	/** ثبت درخواست تنزیل به حساب مشتری. */
	const REQUEST_DOWNGRADE = 'zch_request_downgrade';

	/** مدیریت و ویرایش اطلاعات کسب‌وکار. */
	const MANAGE_BUSINESS_INFO = 'zch_manage_business_info';

	/** باز کردن تیکت پشتیبانی. */
	const OPEN_TICKET = 'zch_open_ticket';

	/** استفاده از کش‌بک در سبد خرید. */
	const REDEEM_CASHBACK = 'zch_redeem_cashback';

	/** مدیریت لینک همکاری در فروش. */
	const MANAGE_AFFILIATE = 'zch_manage_affiliate';

	/** دسترسی به ابزارهای ورود محتوای دمو. */
	const IMPORT_DEMO = 'zch_import_demo';

	/** مدیریت تنظیمات هسته. */
	const MANAGE_SETTINGS = 'zch_manage_settings';

	/** تأیید یا رد درخواست‌های تغییر نقش. */
	const REVIEW_ROLE_REQUESTS = 'zch_review_role_requests';

	/**
	 * فهرست همهٔ capabilityهای اختصاصی.
	 *
	 * @return string[]
	 */
	public static function all(): array {
		return [
			self::VIEW_WHOLESALE_PRICE,
			self::BUY_WHOLESALE,
			self::SUBMIT_RFQ,
			self::VIEW_INVOICES,
			self::REQUEST_UPGRADE,
			self::REQUEST_DOWNGRADE,
			self::MANAGE_BUSINESS_INFO,
			self::OPEN_TICKET,
			self::REDEEM_CASHBACK,
			self::MANAGE_AFFILIATE,
			self::IMPORT_DEMO,
			self::MANAGE_SETTINGS,
			self::REVIEW_ROLE_REQUESTS,
		];
	}

	/**
	 * نگاشت قابلیت‌ها به نقش‌ها.
	 *
	 * نقش `zch_wholesale_pending` عمداً هیچ دسترسی قیمتی ندارد؛
	 * چون حساب همکار تا تأیید مدیر فعال نمی‌شود.
	 *
	 * @return array<string,string[]>
	 */
	public static function map(): array {
		$shared_customer = [
			self::OPEN_TICKET,
			self::REDEEM_CASHBACK,
			self::MANAGE_AFFILIATE,
			self::REQUEST_UPGRADE,
		];

		$shared_wholesale = [
			self::OPEN_TICKET,
			self::REDEEM_CASHBACK,
			self::MANAGE_AFFILIATE,
			self::REQUEST_DOWNGRADE,
			self::SUBMIT_RFQ,
			self::VIEW_INVOICES,
			self::MANAGE_BUSINESS_INFO,
		];

		/**
		 * فیلتر نگاشت قابلیت‌ها به نقش‌ها.
		 *
		 * @param array $map آرایهٔ نقش => capabilityها.
		 */
		return apply_filters(
			'zch/capabilities/map',
			[
				'customer'              => $shared_customer,
				'zch_wholesale'         => array_merge(
					$shared_wholesale,
					[
						self::VIEW_WHOLESALE_PRICE,
						self::BUY_WHOLESALE,
					]
				),
				'zch_wholesale_pending' => [
					self::OPEN_TICKET,
				],
				'administrator'         => array_merge(
					self::all(),
					[
						self::VIEW_WHOLESALE_PRICE,
						self::BUY_WHOLESALE,
					]
				),
				'shop_manager'          => [
					self::VIEW_WHOLESALE_PRICE,
					self::BUY_WHOLESALE,
					self::VIEW_INVOICES,
					self::SUBMIT_RFQ,
					self::REVIEW_ROLE_REQUESTS,
					self::IMPORT_DEMO,
					self::MANAGE_BUSINESS_INFO,
				],
			]
		);
	}
}
