<?php
/**
 * حریر رویا — نقطهٔ ورود قالب.
 *
 * این فایل عمداً کوتاه است: فقط bootstrap. همهٔ منطق در inc/ قرار دارد.
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

define( 'ZCH_THEME_VERSION', '1.0.0' );
define( 'ZCH_THEME_FILE', __FILE__ );
define( 'ZCH_THEME_DIR', get_template_directory() );
define( 'ZCH_THEME_URI', get_template_directory_uri() );

/**
 * بارگذاری هستهٔ قالب.
 */
require_once ZCH_THEME_DIR . '/inc/class-zch-theme.php';

/**
 * بالا آوردن قالب.
 *
 * @return ZCH_Theme
 */
function zch_theme() {
	return ZCH_Theme::instance();
}

zch_theme()->boot();
