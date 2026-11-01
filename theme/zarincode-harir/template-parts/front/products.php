<?php
/**
 * محصولات برگزیدهٔ صفحهٔ نخست — مطابق `section.product-section`.
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

$shop_url = wc_get_page_permalink( 'shop' );
$count    = (int) zch_option( 'home_products_count', 4 );

$products = wc_get_products(
	/**
	 * فیلتر کوئری محصولات صفحهٔ نخست.
	 *
	 * @param array $args
	 */
	apply_filters( 'zch/front/products_query', [
		'status'   => 'publish',
		'limit'    => $count,
		'featured' => true,
		'orderby'  => 'date',
		'order'    => 'DESC',
	] )
);

// اگر محصول ویژه‌ای تعیین نشده، تازه‌ترین‌ها نمایش داده می‌شوند.
if ( empty( $products ) ) {
	$products = wc_get_products(
		[
			'status'  => 'publish',
			'limit'   => $count,
			'orderby' => 'date',
			'order'   => 'DESC',
		]
	);
}

if ( empty( $products ) ) {
	return;
}
?>
<section class="container section product-section">

	<div class="section-head">
		<div>
			<div class="section-label"><?php echo esc_html( zch_option( 'home_products_label', 'OUR FAVORITES' ) ); ?></div>
			<h2><?php echo esc_html( zch_option( 'home_products_title', __( 'انتخاب‌های دوست‌داشتنی', 'zarincode-harir' ) ) ); ?></h2>
			<p><?php echo esc_html( zch_option( 'home_products_text', __( 'محصولاتی برای شب‌های آرام‌تر', 'zarincode-harir' ) ) ); ?></p>
		</div>

		<a class="view-all" href="<?php echo esc_url( $shop_url ); ?>">
			<?php esc_html_e( 'مشاهده همه', 'zarincode-harir' ); ?>
			<?php zch_icon( 'arrow-start', [ 'size' => 16 ] ); ?>
		</a>
	</div>

	<div class="product-grid">
		<?php
		foreach ( $products as $zch_product ) {
			get_template_part( 'template-parts/content/product', 'card', [ 'zch_product' => $zch_product ] );
		}
		?>
	</div>

	<?php if ( zch_option( 'show_sample_note', true ) ) : ?>
		<div class="sample-note">
			<?php echo esc_html( zch_option( 'sample_note', __( '* قیمت‌ها و مشخصات در این طرح، نمونه و نمایشی هستند.', 'zarincode-harir' ) ) ); ?>
		</div>
	<?php endif; ?>

</section>
