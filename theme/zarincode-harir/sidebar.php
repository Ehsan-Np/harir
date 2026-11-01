<?php
/**
 * ستون کناری.
 *
 * قالب المنتوری است، پس سایدبار به‌صورت پیش‌فرض چاپ نمی‌شود مگر
 * ویجتی در آن ناحیه ثبت شده باشد.
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

if ( ! is_active_sidebar( 'zch-sidebar' ) ) {
	return;
}
?>
<aside id="zch-sidebar" class="detail-side widget-area" role="complementary"
	aria-label="<?php esc_attr_e( 'ستون کناری', 'zarincode-harir' ); ?>">
	<?php dynamic_sidebar( 'zch-sidebar' ); ?>
</aside>
