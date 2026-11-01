<?php
/**
 * آرشیو محصول — فروشگاه، دسته‌بندی و برچسب.
 *
 * بازتعریف تمپلیت ووکامرس با چیدمان `complete-01-shop.html`:
 * page-heading → shop-toolbar → product-grid → pagination
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );
?>

<div class="container complete-page">
	<div class="inner-main">

		<?php
		/**
		 * هوک‌های استاندارد ووکامرس بالای آرشیو.
		 */
		do_action( 'woocommerce_before_main_content' );
		do_action( 'woocommerce_archive_description' );
		?>

		<?php get_template_part( 'template-parts/content/page', 'heading' ); ?>

		<?php if ( woocommerce_product_loop() ) : ?>

			<div class="shop-toolbar">
				<?php
				do_action( 'woocommerce_before_shop_loop' );
				?>
			</div>

			<div class="product-grid products">
				<?php
				while ( have_posts() ) {
					the_post();

					/**
					 * هوک استاندارد داخل حلقهٔ محصول.
					 */
					do_action( 'woocommerce_shop_loop' );

					wc_get_template_part( 'content', 'product' );
				}
				?>
			</div>

			<?php do_action( 'woocommerce_after_shop_loop' ); ?>

		<?php else : ?>

			<?php wc_get_template( 'loop/no-products-found.php' ); ?>

		<?php endif; ?>

		<?php do_action( 'woocommerce_after_main_content' ); ?>

	</div>

	<?php do_action( 'woocommerce_sidebar' ); ?>

</div>

<?php get_footer( 'shop' );
