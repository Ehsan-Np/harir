<?php
/**
 * تابع کمکی چاپ آیکون.
 *
 * در طرح اصلی ۸۹۰ بار SVG درون‌خطی تکرار شده بود. اینجا همه از یک
 * registry مشترک می‌آیند و با کش ثابت، هزینهٔ تکرار صفر می‌شود.
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

require_once ZCH_THEME_DIR . '/inc/icons-data.php';

/**
 * چاپ یک آیکون SVG.
 *
 * @param string $name نام آیکون در registry.
 * @param array  $args {
 *     @type int    $size       اندازهٔ مربعی به پیکسل. پیش‌فرض ۲۰.
 *     @type string $class      کلاس اضافی.
 *     @type string $stroke_width ضخامت خط. پیش‌فرض 1.65 (مطابق طرح).
 *     @type bool   $aria_hidden آیا از صفحه‌خوان پنهان شود. پیش‌فرض true.
 *     @type string $title      عنوان قابل‌دسترس (aria_hidden را false می‌کند).
 * }
 */
function zch_icon( $name, $args = [] ) {
	echo zch_get_icon( $name, $args ); // phpcs:ignore WordPress.Security.EscapeOutput -- خروجی در zch_get_icon escape می‌شود.
}

/**
 * بازگرداندن markup یک آیکون.
 *
 * @param string $name
 * @param array  $args
 * @return string
 */
function zch_get_icon( $name, $args = [] ) {
	$icons = zch_icons();

	if ( ! isset( $icons[ $name ] ) ) {
		return '';
	}

	$defaults = [
		'size'         => 20,
		'class'        => '',
		'stroke_width' => 1.65,
		'aria_hidden'  => true,
		'title'        => '',
	];

	$args = wp_parse_args( $args, $defaults );

	if ( '' !== $args['title'] ) {
		$args['aria_hidden'] = false;
	}

	$classes = trim( 'zch-icon zch-icon--' . sanitize_html_class( $name ) . ' ' . $args['class'] );
	$size    = absint( $args['size'] );

	$title_markup = '';
	$role         = 'aria-hidden="true"';
	$title_id     = '';

	if ( ! $args['aria_hidden'] && '' !== $args['title'] ) {
		$title_id     = 'zch-icon-title-' . substr( md5( $name . $args['title'] . wp_rand() ), 0, 8 );
		$role         = 'role="img" aria-labelledby="' . esc_attr( $title_id ) . '"';
		$title_markup = '<title id="' . esc_attr( $title_id ) . '">' . esc_html( $args['title'] ) . '</title>';
	}

	$body = wp_kses(
		$icons[ $name ]['body'],
		[
			'path'   => [ 'd' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'opacity' => true ],
			'circle' => [ 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ],
			'rect'   => [ 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ],
			'line'   => [ 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true ],
			'polygon' => [ 'points' => true, 'fill' => true, 'stroke' => true ],
			'g'      => [ 'fill' => true, 'stroke' => true, 'stroke-width' => true ],
		]
	);

	return sprintf(
		'<svg class="%1$s" width="%2$d" height="%2$d" viewBox="%3$s" fill="none" stroke="currentColor" stroke-width="%4$s" stroke-linecap="round" stroke-linejoin="round" %5$s focusable="false">%6$s%7$s</svg>',
		esc_attr( $classes ),
		$size,
		esc_attr( $icons[ $name ]['viewBox'] ),
		esc_attr( (string) $args['stroke_width'] ),
		$role, // phpcs:ignore WordPress.Security.EscapeOutput -- بالا escape شده.
		$title_markup, // phpcs:ignore WordPress.Security.EscapeOutput
		$body // phpcs:ignore WordPress.Security.EscapeOutput -- با wp_kses پاک‌سازی شده.
	);
}

/**
 * آیا یک آیکون در registry وجود دارد؟
 *
 * @param string $name
 * @return bool
 */
function zch_has_icon( $name ) {
	$icons = zch_icons();

	return isset( $icons[ $name ] );
}

/**
 * نام همهٔ آیکون‌های ثبت‌شده.
 *
 * @return string[]
 */
function zch_icon_names() {
	return array_keys( zch_icons() );
}
