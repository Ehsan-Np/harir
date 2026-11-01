<?php
/**
 * هیروی صفحهٔ نخست — مطابق `section.hero.dream-hero` در `Home.html`.
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

$shop_url      = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$wholesale_url = (string) zch_option( 'wholesale_url', home_url( '/wholesale/' ) );
$support_url   = (string) zch_option( 'support_url', home_url( '/contact/' ) );

$kicker  = zch_option( 'hero_kicker', __( 'نرمی، آرامش و خیالِ خوش', 'zarincode-harir' ) );
$en      = zch_option( 'hero_en', 'SOFT DREAMS COLLECTION' );
$title   = zch_option( 'hero_title', __( 'خوابِ خوش،', 'zarincode-harir' ) );
$em      = zch_option( 'hero_em', __( 'رویای نرمِ شما', 'zarincode-harir' ) );
$text    = zch_option( 'hero_text', __( 'برای خانه‌ای آرام یا فروشگاهی خوش‌سلیقه؛ کالای خواب را تکی و عمده کنار هم انتخاب کنید.', 'zarincode-harir' ) );
$label   = zch_option( 'hero_photo_label_en', 'THE SOFTEST MOMENTS' );
$label2  = zch_option( 'hero_photo_label_fa', __( 'آرامش را زندگی کن', 'zarincode-harir' ) );
$photo   = (int) zch_option( 'hero_photo', 0 );
?>
<section class="container hero dream-hero">

	<div class="hero-copy">

		<?php if ( '' !== $kicker ) : ?>
			<div class="hero-kicker"><span class="dot"></span><?php echo esc_html( $kicker ); ?></div>
		<?php endif; ?>

		<?php if ( '' !== $en ) : ?>
			<div class="hero-en"><?php echo esc_html( $en ); ?></div>
		<?php endif; ?>

		<h1><?php echo esc_html( $title ); ?><br><em><?php echo esc_html( $em ); ?></em></h1>

		<?php if ( '' !== $text ) : ?>
			<p><?php echo esc_html( $text ); ?></p>
		<?php endif; ?>

		<div class="hero-actions">
			<a class="btn primary" href="<?php echo esc_url( $shop_url ); ?>">
				<?php esc_html_e( 'خرید تکی', 'zarincode-harir' ); ?>
				<?php zch_icon( 'arrow-start', [ 'size' => 18 ] ); ?>
			</a>

			<a class="btn outline" href="<?php echo esc_url( $wholesale_url ); ?>">
				<?php esc_html_e( 'خرید عمده', 'zarincode-harir' ); ?>
				<span><?php esc_html_e( '/ همکاران', 'zarincode-harir' ); ?></span>
				<?php zch_icon( 'arrow-start', [ 'size' => 17 ] ); ?>
			</a>
		</div>

		<div class="hero-tail">
			<a href="<?php echo esc_url( $support_url ); ?>">
				<?php zch_icon( 'chat', [ 'size' => 15 ] ); ?>
				<?php echo esc_html( zch_option( 'support_note', __( 'از حریر یار بپرس', 'zarincode-harir' ) ) ); ?>
			</a>

			<div class="slider-dots" aria-hidden="true"><i></i><i></i><i></i></div>
		</div>

	</div>

	<div class="hero-photo">
		<?php if ( $photo ) : ?>
			<?php echo wp_get_attachment_image( $photo, 'zch-hero', false, [ 'fetchpriority' => 'high' ] ); ?>
		<?php elseif ( is_front_page() && have_posts() && has_post_thumbnail() ) : ?>
			<?php the_post_thumbnail( 'zch-hero', [ 'fetchpriority' => 'high' ] ); ?>
		<?php else : ?>
			<img src="<?php echo esc_url( ZCH_THEME_URI . '/assets/images/pink-hero.jpg' ); ?>"
				alt="<?php esc_attr_e( 'اتاق خواب با کالای خواب صورتی ملایم', 'zarincode-harir' ); ?>"
				width="1200" height="900" fetchpriority="high">
		<?php endif; ?>

		<div class="photo-label">
			<span><?php echo esc_html( $label ); ?></span>
			<strong><?php echo esc_html( $label2 ); ?></strong>
		</div>
	</div>

</section>
