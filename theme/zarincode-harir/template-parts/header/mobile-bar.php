<?php
/**
 * نوار پایین موبایل — مطابق `.mobile-bar` طرح.
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

if ( ! zch_option( 'show_mobile_bar', true ) ) {
	return;
}

$cart_url      = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/' );
$myaccount_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();
$catalog_url   = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$cart_count    = zch_cart_count();

$items = [
	[ 'icon' => 'home',  'label' => __( 'خانه', 'zarincode-harir' ),        'url' => home_url( '/' ) ],
	[ 'icon' => 'grid',  'label' => __( 'دسته‌بندی', 'zarincode-harir' ),   'url' => $catalog_url ],
	[ 'icon' => 'bag',   'label' => __( 'سبد خرید', 'zarincode-harir' ),    'url' => $cart_url, 'badge' => $cart_count ],
	[ 'icon' => 'user',  'label' => __( 'حساب من', 'zarincode-harir' ),     'url' => $myaccount_url ],
];

/**
 * فیلتر آیتم‌های نوار موبایل.
 *
 * @param array $items
 */
$items = apply_filters( 'zch/mobile_bar_items', $items );

$current_path = isset( $_SERVER['REQUEST_URI'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	: '';
?>
<div class="mobile-bar" role="navigation" aria-label="<?php esc_attr_e( 'ناوبری موبایل', 'zarincode-harir' ); ?>">
	<?php foreach ( $items as $item ) : ?>
		<a class="mobile-trade<?php echo ( isset( $item['url'] ) && '' !== $current_path && false !== strpos( $current_path, (string) wp_parse_url( $item['url'], PHP_URL_PATH ) ) ) ? ' active' : ''; ?>"
			href="<?php echo esc_url( $item['url'] ); ?>">
			<?php zch_icon( $item['icon'], [ 'size' => 20 ] ); ?>
			<span class="mobile-label"><?php echo esc_html( $item['label'] ); ?></span>
			<?php if ( ! empty( $item['badge'] ) ) : ?>
				<i aria-hidden="true"><?php echo esc_html( zch_fa_digits( (int) $item['badge'] ) ); ?></i>
			<?php endif; ?>
		</a>
	<?php endforeach; ?>
</div>
