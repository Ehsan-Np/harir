<?php
/**
 * کشف رنگ و راهنمای سایز — مطابق `section.discovery`.
 *
 * دو کارت: `.discovery-card` (پالت رنگ) و `.guide-card` (راهنمای خرید).
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

$colors_url = (string) zch_option( 'colors_url', home_url( '/colors/' ) );
$guide_url  = (string) zch_option( 'guide_url', home_url( '/guide/' ) );
$photo      = (int) zch_option( 'discovery_photo', 0 );

/**
 * فیلتر پالت رنگ نمایشی.
 *
 * @param string[] $swatches کدهای هگز.
 */
$swatches = apply_filters(
	'zch/front/swatches',
	[ '#e8b4c4', '#f0e4d3', '#c8a7c9', '#b9b2b6', '#3a4660', '#b48d78' ]
);
?>
<section class="container section discovery">

	<div class="discovery-card">
		<div class="dis-copy">
			<div class="section-label"><?php echo esc_html( zch_option( 'discovery_label', 'COLOR & TEXTURE' ) ); ?></div>
			<h2><?php echo esc_html( zch_option( 'discovery_title', __( 'رنگِ آرامشِ شما چیست؟', 'zarincode-harir' ) ) ); ?></h2>
			<p><?php echo esc_html( zch_option( 'discovery_text', __( 'پالت رنگ پارچه‌ها را ببینید و با خیال راحت‌تر انتخاب کنید.', 'zarincode-harir' ) ) ); ?></p>

			<div class="swatches" role="presentation">
				<?php foreach ( array_slice( $swatches, 0, 6 ) as $swatch ) : ?>
					<i style="<?php echo esc_attr( 'background:' . $swatch ); ?>"></i>
				<?php endforeach; ?>
			</div>

			<a class="dis-link" href="<?php echo esc_url( $colors_url ); ?>">
				<?php echo esc_html( zch_option( 'discovery_link', __( 'مشاهده آلبوم رنگ', 'zarincode-harir' ) ) ); ?>
				<?php zch_icon( 'arrow-start', [ 'size' => 15 ] ); ?>
			</a>
		</div>

		<div class="dis-photo">
			<?php if ( $photo ) : ?>
				<?php echo wp_get_attachment_image( $photo, 'zch-product-card', false, [ 'loading' => 'lazy' ] ); ?>
			<?php else : ?>
				<img src="<?php echo esc_url( ZCH_THEME_URI . '/assets/images/pink-detail.jpg' ); ?>"
					alt="<?php esc_attr_e( 'بافت و رنگ پارچه', 'zarincode-harir' ); ?>"
					width="900" height="700" loading="lazy">
			<?php endif; ?>
		</div>
	</div>

	<div class="guide-card">
		<div class="guide-symbol"><?php zch_icon( 'sparkle', [ 'size' => 26 ] ); ?></div>
		<div class="section-label"><?php echo esc_html( zch_option( 'guide_label', 'A BETTER SLEEP' ) ); ?></div>
		<h2>
			<?php echo esc_html( zch_option( 'guide_title', __( 'سایز درست،', 'zarincode-harir' ) ) ); ?><br>
			<?php echo esc_html( zch_option( 'guide_title_2', __( 'خوابِ بهتر', 'zarincode-harir' ) ) ); ?>
		</h2>
		<p><?php echo esc_html( zch_option( 'guide_text', __( 'پیش از سفارش، با راهنمای انتخاب رنگ و اندازه همراه شوید.', 'zarincode-harir' ) ) ); ?></p>

		<a href="<?php echo esc_url( $guide_url ); ?>">
			<?php echo esc_html( zch_option( 'guide_link', __( 'راهنمای خرید', 'zarincode-harir' ) ) ); ?>
			<?php zch_icon( 'arrow-start', [ 'size' => 15 ] ); ?>
		</a>
	</div>

</section>
