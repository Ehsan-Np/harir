<?php
/**
 * کارت محصول — مطابق `.product` در `Home.html`.
 *
 * ساختار:
 *   .product
 *     .product-image  → img + .product-tag + .fav
 *     .product-info   → .product-top (برند + .colors) + h3 + p
 *                       .price-row ( .old-price + strong + small تومان + button.add )
 *
 * قیمت‌ها از موتور قیمت حریر خوانده می‌شوند، پس برای همکار تأییدشده
 * قیمت عمده و برای مشتری قیمت خرده چاپ می‌شود.
 *
 * @var WC_Product|null $zch_product از قالب والد پاس داده می‌شود.
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

/** @var WC_Product|null $zch_product */
$zch_product = isset( $zch_product ) ? $zch_product : null;

if ( ! $zch_product && function_exists( 'wc_get_product' ) ) {
	$zch_product = wc_get_product( get_the_ID() );
}

if ( ! $zch_product instanceof WC_Product ) {
	return;
}

$product_id = $zch_product->get_id();
$permalink  = $zch_product->get_permalink();
$title      = $zch_product->get_name();

// تصویر.
$image_id = $zch_product->get_image_id();

// برچسب روی تصویر.
$tag = zch_product_card_tag( $zch_product );

// رنگ‌های در دسترس (اتریبیوت pa_color یا pa_zch-color).
$colors = zch_product_color_dots( $zch_product );

// زیرعنوان: سایز و انتخاب‌ها.
$subtitle = zch_product_card_subtitle( $zch_product );

// قیمت.
$result   = zch_price_result( $product_id );
$price    = is_array( $result ) && isset( $result['final'] ) ? (float) $result['final'] : (float) wc_get_price_to_display( $zch_product );
$regular  = (float) wc_get_price_to_display( $zch_product, [ 'price' => $zch_product->get_regular_price() ] );
$is_whole = is_array( $result ) && ! empty( $result['is_wholesale'] );

$note = '';
if ( $is_whole && is_array( $result ) && ! empty( $result['note'] ) ) {
	$note = (string) $result['note'];
}

$can_add = $zch_product->is_purchasable() && $zch_product->is_in_stock();
$rule    = zch_quantity_rule( $product_id );
?>
<div <?php echo wp_kses_post( wc_product_class( 'product', $zch_product ) ); ?> data-product-id="<?php echo esc_attr( $product_id ); ?>">

	<div class="product-image">
		<a href="<?php echo esc_url( $permalink ); ?>" tabindex="-1" aria-hidden="true">
			<?php
			if ( $image_id ) {
				echo wp_get_attachment_image( $image_id, 'zch-product-card', false, [ 'alt' => esc_attr( $title ), 'loading' => 'lazy' ] );
			} else {
				echo '<span class="zch-placeholder"></span>';
			}
			?>
		</a>

		<?php if ( '' !== $tag ) : ?>
			<span class="product-tag"><?php echo esc_html( $tag ); ?></span>
		<?php endif; ?>

		<?php if ( is_user_logged_in() ) : ?>
			<button type="button" class="fav" data-zch-wishlist="<?php echo esc_attr( $product_id ); ?>"
				aria-label="<?php esc_attr_e( 'افزودن به علاقه‌مندی‌ها', 'zarincode-harir' ); ?>" aria-pressed="false">
				<?php zch_icon( 'heart', [ 'size' => 18 ] ); ?>
			</button>
		<?php endif; ?>
	</div>

	<div class="product-info">

		<div class="product-top">
			<span><?php echo esc_html( zch_product_brand_label( $zch_product ) ); ?></span>

			<?php if ( ! empty( $colors ) ) : ?>
				<span class="colors" aria-label="<?php esc_attr_e( 'رنگ‌های موجود', 'zarincode-harir' ); ?>">
					<?php foreach ( array_slice( $colors, 0, 4 ) as $color ) : ?>
						<i style="<?php echo esc_attr( 'background:' . $color['value'] ); ?>" title="<?php echo esc_attr( $color['name'] ); ?>"></i>
					<?php endforeach; ?>
				</span>
			<?php endif; ?>
		</div>

		<h3><a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a></h3>

		<?php if ( '' !== $subtitle ) : ?>
			<p><?php echo esc_html( $subtitle ); ?></p>
		<?php endif; ?>

		<div class="price-row">
			<div>
				<?php if ( $regular > $price && $regular > 0 ) : ?>
					<div class="old-price"><?php echo esc_html( zch_format_price( $regular, false ) ); ?></div>
				<?php else : ?>
					<div class="old-price">&nbsp;</div>
				<?php endif; ?>

				<strong class="<?php echo $is_whole ? 'zch-price-wholesale' : 'zch-price-retail'; ?>">
					<?php echo esc_html( zch_format_price( $price, false ) ); ?>
				</strong>
				<small><?php echo esc_html( zch_currency_label() ); ?></small>

				<?php if ( $is_whole ) : ?>
					<span class="zch-price-badge"><?php esc_html_e( 'قیمت همکار', 'zarincode-harir' ); ?></span>
				<?php endif; ?>
			</div>

			<?php if ( $can_add ) : ?>
				<a class="add" href="<?php echo esc_url( $zch_product->add_to_cart_url() ); ?>"
					data-zch-add-to-cart="<?php echo esc_attr( $product_id ); ?>"
					<?php echo $zch_product->is_type( 'variable' ) ? 'data-variable="1"' : ''; ?>
					aria-label="<?php echo esc_attr( sprintf( __( 'افزودن %s به سبد', 'zarincode-harir' ), $title ) ); ?>"
					data-min="<?php echo esc_attr( isset( $rule['min'] ) ? (int) $rule['min'] : 1 ); ?>"
					data-step="<?php echo esc_attr( isset( $rule['step'] ) ? (int) $rule['step'] : 1 ); ?>">
					<span><?php echo esc_html( $zch_product->add_to_cart_text() ); ?></span>
					<?php zch_icon( 'bag', [ 'size' => 17 ] ); ?>
				</a>
			<?php else : ?>
				<a class="add disabled" href="<?php echo esc_url( $permalink ); ?>" aria-disabled="true">
					<span><?php esc_html_e( 'ناموجود', 'zarincode-harir' ); ?></span>
					<?php zch_icon( 'bag', [ 'size' => 17 ] ); ?>
				</a>
			<?php endif; ?>
		</div>

		<?php if ( '' !== $note ) : ?>
			<p class="zch-price-note"><?php echo wp_kses_post( $note ); ?></p>
		<?php endif; ?>

	</div>

</div>
