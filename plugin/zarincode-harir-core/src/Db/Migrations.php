<?php
/**
 * ساخت و ارتقای جدول‌های دیتابیس.
 *
 * @package Zarincode\Harir\Db
 */

namespace Zarincode\Harir\Db;

defined( 'ABSPATH' ) || exit;

use Zarincode\Harir\Roles\Roles;
use Zarincode\Harir\Roles\Capabilities;

/**
 * مهاجرت‌های دیتابیس.
 */
final class Migrations {

	/**
	 * اجرای activation hook.
	 */
	public static function activate(): void {
		self::create_tables();
		self::seed_roles();
		self::seed_terms_and_attributes();

		update_option( Tables::VERSION_OPTION, Tables::SCHEMA_VERSION );

		// برای صفحهٔ پنل و endpointهای تازه.
		flush_rewrite_rules();

		/**
		 * پس از فعال‌سازی افزونه.
		 */
		do_action( 'zch/activated' );
	}

	/**
	 * ساخت/به‌روزرسانی جدول‌ها با dbDelta.
	 */
	public static function create_tables(): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		foreach ( Tables::schema() as $sql ) {
			dbDelta( $sql );
		}
	}

	/**
	 * بررسی نیاز به migration در هر بار بالا آمدن ادمین.
	 */
	public static function maybe_upgrade(): void {
		if ( get_option( Tables::VERSION_OPTION ) === Tables::SCHEMA_VERSION ) {
			return;
		}

		self::activate();
	}

	/**
	 * ساخت نقش‌ها و capabilityها.
	 */
	private static function seed_roles(): void {
		$roles = new Roles();
		$roles->maybe_install();

		// اطمینان از اینکه نقش‌های موجود هم capabilityها را دارند.
		foreach ( Capabilities::map() as $role => $caps ) {
			$object = get_role( $role );

			if ( ! $object instanceof \WP_Role ) {
				continue;
			}

			foreach ( $caps as $cap ) {
				if ( ! $object->has_cap( $cap ) ) {
					$object->add_cap( $cap );
				}
			}
		}
	}

	/**
	 * ساخت ویژگی‌های محصول مورد نیاز طرح (رنگ، سایز، برند، مدل، تعداد تکه).
	 *
	 * فقط در صورت نبود ساخته می‌شوند تا دادهٔ موجود کاربر دست‌نخورده بماند.
	 */
	private static function seed_terms_and_attributes(): void {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return;
		}

		$attributes = [
			'pa_zch-color'  => [ 'label' => __( 'رنگ', 'zarincode-harir-core' ), 'type' => 'select' ],
			'pa_zch-size'   => [ 'label' => __( 'سایز', 'zarincode-harir-core' ), 'type' => 'select' ],
			'pa_zch-brand'  => [ 'label' => __( 'برند', 'zarincode-harir-core' ), 'type' => 'select' ],
			'pa_zch-model'  => [ 'label' => __( 'مدل', 'zarincode-harir-core' ), 'type' => 'select' ],
			'pa_zch-pieces' => [ 'label' => __( 'تعداد تکه', 'zarincode-harir-core' ), 'type' => 'select' ],
		];

		foreach ( $attributes as $slug => $config ) {
			$taxonomy = wc_attribute_taxonomy_name_by_id( wc_attribute_taxonomy_id_by_name( $slug ) );

			if ( $taxonomy && taxonomy_exists( $taxonomy ) ) {
				continue;
			}

			global $wpdb;

			$exists = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prepare( "SELECT attribute_id FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = %s", str_replace( 'pa_', '', $slug ) )
			);

			if ( $exists ) {
				continue;
			}

			$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prefix . 'woocommerce_attribute_taxonomies',
				[
					'attribute_name'    => str_replace( 'pa_', '', $slug ),
					'attribute_label'   => $config['label'],
					'attribute_type'    => $config['type'],
					'attribute_orderby' => 'menu_order',
					'attribute_public'  => 1,
				],
				[ '%s', '%s', '%s', '%s', '%d' ]
			);
		}

		delete_transient( 'wc_attribute_taxonomies' );

		/**
		 * پس از ساخت ویژگی‌های اولیه.
		 */
		do_action( 'zch/demo/attributes_seeded' );
	}
}
