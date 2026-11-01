<?php
/**
 * نوار اعلان بالای صفحه (.topline).
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

/**
 * فیلتر نمایش نوار بالایی.
 *
 * @param bool $show
 */
if ( ! apply_filters( 'zch/show_topline', true ) ) {
	return;
}

$right_text = zch_option( 'topline_right', __( 'برای خانه‌ها و کسب‌وکارها؛ خرید تکی و عمده در حریر رویا', 'zarincode-harir' ) );
$left_text  = zch_option( 'topline_left', '' );
$left_link  = (string) zch_option( 'topline_link', '' );

if ( '' === $left_text ) {
	$tracking = function_exists( 'wc_get_page_permalink' ) ? '' : home_url( '/' );
	$left_text = sprintf(
		/* translators: 1: order tracking, 2: consultation */
		__( '%1$s <b>·</b> %2$s', 'zarincode-harir' ),
		esc_html__( 'پیگیری سفارش', 'zarincode-harir' ),
		esc_html__( 'مشاوره خرید', 'zarincode-harir' )
	);
}
?>
<div class="topline">
	<div class="container top-inner">
		<span>
			<?php zch_icon( 'sparkle', [ 'size' => 13 ] ); ?>
			<?php echo wp_kses_post( $right_text ); ?>
		</span>
		<div>
			<?php if ( '' !== $left_link ) : ?>
				<a href="<?php echo esc_url( $left_link ); ?>"><?php echo wp_kses_post( $left_text ); ?></a>
			<?php else : ?>
				<?php echo wp_kses_post( $left_text ); ?>
			<?php endif; ?>
		</div>
	</div>
</div>
