<?php
/**
 * کارت محصول داخل حلقهٔ فروشگاهی.
 *
 * به‌جای هوک‌های پراکندهٔ ووکامرس، از کارت بومی حریر استفاده می‌شود تا
 * خروجی با طرح مرجع یکسان بماند. هوک‌های اصلی ووکامرس هم فراخوانی
 * می‌شوند تا افزونه‌های سازگار بتوانند محتوا تزریق کنند.
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product || ! $product->is_visible() ) {
	return;
}

$zch_product = $product;

/**
 * قبل از چاپ کارت محصول.
 *
 * @param WC_Product $product
 */
do_action( 'zch/before_product_card', $product );

get_template_part( 'template-parts/content/product', 'card', [ 'zch_product' => $zch_product ] );

/**
 * بعد از چاپ کارت محصول.
 *
 * @param WC_Product $product
 */
do_action( 'zch/after_product_card', $product );
