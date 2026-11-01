<?php
/**
 * بلوک همکاران — مطابق `section.wholesale-wrap` در `Home.html`.
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

$wholesale_url = (string) zch_option( 'wholesale_url', home_url( '/wholesale/' ) );
$apply_url     = (string) zch_option( 'wholesale_apply_url', home_url( '/my-account/wholesale/' ) );
$photo         = (int) zch_option( 'wholesale_photo', 0 );

$benefits = [
	[ 'icon' => 'grid',      'text' => __( 'انتخاب متنوع', 'zarincode-harir' ) ],
	[ 'icon' => 'cube-check', 'text' => __( 'سفارش در تعداد', 'zarincode-harir' ) ],
	[ 'icon' => 'headset',   'text' => __( 'مشاوره خرید', 'zarincode-harir' ) ],
];

/**
 * فیلتر مزیت‌های بلوک همکاران.
 *
 * @param array $benefits
 */
$benefits = apply_filters( 'zch/front/wholesale_benefits', $benefits );
?>
<section class="container section wholesale-wrap">

	<div class="wholesale">

		<div class="wh-copy">
			<div class="wh-kicker">
				<?php echo esc_html( zch_option( 'wholesale_kicker_en', 'FOR OUR PARTNERS' ) ); ?>
				<span aria-hidden="true">✦</span>
				<?php echo esc_html( zch_option( 'wholesale_kicker_fa', __( 'ویژه همکاران', 'zarincode-harir' ) ) ); ?>
			</div>

			<h2>
				<?php echo esc_html( zch_option( 'wholesale_title', __( 'برای کسب‌وکار شما،', 'zarincode-harir' ) ) ); ?><br>
				<?php echo esc_html( zch_option( 'wholesale_title_2', __( 'همان‌قدر رؤیایی', 'zarincode-harir' ) ) ); ?>
			</h2>

			<p><?php echo esc_html( zch_option( 'wholesale_text', __( 'برای فروشگاه‌ها، هتل‌ها و سفارش‌های سازمانی؛ مسیر خرید عمده از همین‌جا آغاز می‌شود.', 'zarincode-harir' ) ) ); ?></p>

			<div class="wh-benefits">
				<?php foreach ( $benefits as $benefit ) : ?>
					<span>
						<?php zch_icon( $benefit['icon'], [ 'size' => 15 ] ); ?>
						<?php echo esc_html( $benefit['text'] ); ?>
					</span>
				<?php endforeach; ?>
			</div>

			<div class="wh-actions">
				<a class="btn wh-main" href="<?php echo esc_url( $wholesale_url ); ?>">
					<?php echo esc_html( zch_option( 'wholesale_cta', __( 'دریافت قیمت عمده', 'zarincode-harir' ) ) ); ?>
					<?php zch_icon( 'arrow-start', [ 'size' => 17 ] ); ?>
				</a>

				<a href="<?php echo esc_url( $apply_url ); ?>">
					<?php echo esc_html( zch_option( 'wholesale_cta_2', __( 'ثبت درخواست همکاری', 'zarincode-harir' ) ) ); ?>
					<?php zch_icon( 'arrow-start', [ 'size' => 15 ] ); ?>
				</a>
			</div>
		</div>

		<div class="wh-photo">
			<?php if ( $photo ) : ?>
				<?php echo wp_get_attachment_image( $photo, 'zch-product-card', false, [ 'loading' => 'lazy' ] ); ?>
			<?php else : ?>
				<img src="<?php echo esc_url( ZCH_THEME_URI . '/assets/images/pink-detail.jpg' ); ?>"
					alt="<?php esc_attr_e( 'نمونهٔ کالای خواب حریر رویا', 'zarincode-harir' ); ?>"
					width="900" height="700" loading="lazy">
			<?php endif; ?>

			<div class="wh-photo-tag">
				<b><?php echo esc_html( zch_option( 'wholesale_photo_title', __( 'همکاری با حریر رویا', 'zarincode-harir' ) ) ); ?></b>
				<small><?php echo esc_html( zch_option( 'wholesale_photo_latin', 'WHOLESALE PARTNERS' ) ); ?></small>
			</div>
		</div>

	</div>

</section>
