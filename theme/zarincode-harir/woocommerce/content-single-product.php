<?php
/**
 * بدنهٔ صفحهٔ محصول تکی — مطابق `01-retail-product.html`.
 *
 * ساختار: breadcrumb → pd-layout (گالری + اطلاعات) → تب‌های توضیحات
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product ) {
	return;
}

/**
 * هوک استاندارد ووکامرس.
 */
do_action( 'woocommerce_before_single_product' );

if ( post_password_required() ) {
	echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput
	return;
}

$product_id = $product->get_id();
$result     = zch_price_result( $product_id );
$is_whole   = is_array( $result ) && ! empty( $result['is_wholesale'] );
$rule       = zch_quantity_rule( $product_id );
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'product-page pd-wrap', $product ); ?>>

	<?php get_template_part( 'template-parts/content/page', 'heading' ); ?>

	<div class="pd-layout">

		<div class="pd-gallery">
			<?php
			/**
			 * گالری تصاویر محصول.
			 */
			do_action( 'zch/single_product_before_gallery' );

			woocommerce_show_product_images();

			do_action( 'zch/single_product_after_gallery' );
			?>
		</div>

		<div class="pd-info">

			<?php do_action( 'zch/single_product_before_title' ); ?>

			<h1 class="pd-title"><?php the_title(); ?></h1>

			<?php do_action( 'zch/single_product_after_title' ); ?>

			<div class="pd-rating">
				<?php
				if ( ! wc_product_ratings_enabled() ) {
					// اگر ستاره‌ها غیرفعال باشند، فقط کد محصول چاپ می‌شود.
					echo '';
				} else {
					wc_get_template( 'single-product/rating.php' );
				}
				?>
				<span class="pd-sku">
					<?php esc_html_e( 'کد محصول:', 'zarincode-harir' ); ?>
					<strong dir="ltr"><?php echo esc_html( zch_product_code( $product ) ); ?></strong>
				</span>
			</div>

			<div class="pd-price <?php echo $is_whole ? 'pd-price-wholesale' : 'pd-price-retail'; ?>">
				<?php echo wp_kses_post( $product->get_price_html() ); ?>

				<?php if ( $is_whole ) : ?>
					<span class="zch-price-badge"><?php esc_html_e( 'قیمت همکار', 'zarincode-harir' ); ?></span>
				<?php endif; ?>
			</div>

			<?php if ( is_array( $result ) && ! empty( $result['note'] ) ) : ?>
				<p class="zch-price-note pd-price-note"><?php echo wp_kses_post( $result['note'] ); ?></p>
			<?php endif; ?>


			<?php if ( $product->get_short_description() ) : ?>
				<div class="pd-short zch-rich"><?php echo wp_kses_post( $product->get_short_description() ); ?></div>
			<?php endif; ?>

			<?php zch_render_attribute_chips( $product ); ?>

			<div class="pd-buy">
				<?php
				if ( ! empty( $rule ) && '' !== zch_quantity_rule_text( $rule ) ) {
					printf(
						'<p class="pd-rule">%1$s</p>',
						esc_html( zch_quantity_rule_text( $rule ) )
					);
				}

				woocommerce_template_single_add_to_cart();
				?>
			</div>

			<?php zch_render_trust_items(); ?>

		</div>

	</div>

	<div class="pd-tabs">
		<?php woocommerce_output_product_data_tabs(); ?>
	</div>

	<?php zch_render_related_products(); ?>

</div>

<?php
/**
 * هوک استاندارد ووکامرس.
 */
do_action( 'woocommerce_after_single_product' );
