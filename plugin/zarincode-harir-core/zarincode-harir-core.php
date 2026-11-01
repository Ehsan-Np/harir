<?php
/**
 * Plugin Name:       Zarincode Harir Core
 * Plugin URI:        https://zarincode.com/
 * Description:       هستهٔ قالب حریر رویا — نقش‌های کاربری، قیمت‌گذاری عمده، قوانین تعداد، احراز هویت پیامکی، پنل کاربری دومنظوره، تیکت، کش‌بک و ویجت‌های المنتور.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            احسان نادری پناه
 * Author URI:        https://zarincode.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       zarincode-harir-core
 * Domain Path:       /languages
 * WC requires at least: 7.0
 * WC tested up to:      9.5
 *
 * @package Zarincode\Harir
 */

defined( 'ABSPATH' ) || exit;

define( 'ZCH_CORE_VERSION', '1.0.0' );
define( 'ZCH_CORE_FILE', __FILE__ );
define( 'ZCH_CORE_BASENAME', plugin_basename( __FILE__ ) );
define( 'ZCH_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'ZCH_CORE_URL', plugin_dir_url( __FILE__ ) );
define( 'ZCH_CORE_MIN_PHP', '7.4' );
define( 'ZCH_CORE_MIN_WP', '6.0' );
define( 'ZCH_CORE_MIN_WC', '7.0' );

require_once ZCH_CORE_PATH . 'src/autoload.php';

use Zarincode\Harir\Plugin;
use Zarincode\Harir\Db\Migrations;
use Zarincode\Harir\Roles\Roles;

/**
 * بررسی حداقل نیازمندی‌ها پیش از بالا آمدن افزونه.
 *
 * @return true|\WP_Error
 */
function zch_core_requirements_check() {
	if ( version_compare( PHP_VERSION, ZCH_CORE_MIN_PHP, '<' ) ) {
		return new WP_Error( 'php', sprintf( 'هستهٔ حریر رویا به PHP نسخهٔ %s یا بالاتر نیاز دارد.', ZCH_CORE_MIN_PHP ) );
	}
	if ( version_compare( get_bloginfo( 'version' ), ZCH_CORE_MIN_WP, '<' ) ) {
		return new WP_Error( 'wp', sprintf( 'هستهٔ حریر رویا به وردپرس %s یا بالاتر نیاز دارد.', ZCH_CORE_MIN_WP ) );
	}
	return true;
}

add_action(
	'plugins_loaded',
	static function () {
		$check = zch_core_requirements_check();
		if ( is_wp_error( $check ) ) {
			add_action( 'admin_notices', static function () use ( $check ) {
				printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $check->get_error_message() ) );
			} );
			return;
		}
		Plugin::instance()->boot();
	},
	20
);

register_activation_hook( __FILE__, [ Migrations::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ Roles::class, 'deactivate' ] );
