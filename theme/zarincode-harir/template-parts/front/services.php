<?php
/**
 * ردیف سرویس‌ها — مطابق `div.container.services` (۴ آیتم).
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

$services = [
	[ 'icon' => 'wholesale', 'title' => __( 'خرید تکی و عمده', 'zarincode-harir' ),   'text' => __( 'یک انتخاب برای هر نیاز', 'zarincode-harir' ) ],
	[ 'icon' => 'palette',   'title' => __( 'رنگ و سایز متنوع', 'zarincode-harir' ),  'text' => __( 'انتخابی متناسب با سلیقه شما', 'zarincode-harir' ) ],
	[ 'icon' => 'truck',     'title' => __( 'ارسال سفارش', 'zarincode-harir' ),       'text' => __( 'همراه شما تا مقصد', 'zarincode-harir' ) ],
	[ 'icon' => 'headset',   'title' => __( 'پشتیبانی و مشاوره', 'zarincode-harir' ), 'text' => __( 'پاسخ به پرسش‌های شما', 'zarincode-harir' ) ],
];

/**
 * فیلتر سرویس‌های صفحهٔ نخست.
 *
 * @param array $services
 */
$services = apply_filters( 'zch/front/services', $services );

if ( empty( $services ) ) {
	return;
}
?>
<div class="container services">
	<?php foreach ( $services as $service ) : ?>
		<div class="service">
			<div class="service-icon"><?php zch_icon( $service['icon'], [ 'size' => 22 ] ); ?></div>
			<div>
				<strong><?php echo esc_html( $service['title'] ); ?></strong>
				<small><?php echo esc_html( $service['text'] ); ?></small>
			</div>
		</div>
	<?php endforeach; ?>
</div>
