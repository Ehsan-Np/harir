<?php
/**
 * شبکهٔ دسته‌بندی‌ها — مطابق `section.categories` (۶ کارت).
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

$shop_url = wc_get_page_permalink( 'shop' );

$cats = get_terms(
	[
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
		'parent'     => 0,
		'number'     => (int) zch_option( 'home_categories_count', 6 ),
		'orderby'    => 'menu_order',
		'order'      => 'ASC',
	]
);

if ( is_wp_error( $cats ) || empty( $cats ) ) {
	return;
}
?>
<section class="container section categories">

	<div class="section-head">
		<div>
			<div class="section-label"><?php echo esc_html( zch_option( 'home_categories_label', 'EXPLORE BY CATEGORY' ) ); ?></div>
			<h2><?php echo esc_html( zch_option( 'home_categories_title', __( 'با خیال راحت انتخاب کنید', 'zarincode-harir' ) ) ); ?></h2>
			<p><?php echo esc_html( zch_option( 'home_categories_text', __( 'هر آنچه برای یک خواب دل‌نشین نیاز دارید', 'zarincode-harir' ) ) ); ?></p>
		</div>

		<a class="view-all" href="<?php echo esc_url( $shop_url ); ?>">
			<?php esc_html_e( 'مشاهده همه', 'zarincode-harir' ); ?>
			<?php zch_icon( 'arrow-start', [ 'size' => 16 ] ); ?>
		</a>
	</div>

	<div class="category-grid">
		<?php foreach ( $cats as $cat ) : ?>
			<a class="cat" href="<?php echo esc_url( get_term_link( $cat ) ); ?>">
				<div class="cat-image">
					<?php
					$thumb_id = (int) get_term_meta( $cat->term_id, 'thumbnail_id', true );

					if ( $thumb_id ) {
						echo wp_get_attachment_image( $thumb_id, 'zch-product-card', false, [ 'alt' => esc_attr( $cat->name ), 'loading' => 'lazy' ] );
					} else {
						echo '<span class="zch-placeholder"></span>';
					}
					?>
					<span><?php zch_icon( 'arrow-start', [ 'size' => 16 ] ); ?></span>
				</div>

				<strong><?php echo esc_html( $cat->name ); ?></strong>
				<small><?php echo esc_html( strtoupper( (string) get_term_meta( $cat->term_id, '_zch_cat_latin', true ) ?: 'CATEGORY' ) ); ?></small>
			</a>
		<?php endforeach; ?>
	</div>

</section>
