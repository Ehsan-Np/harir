<?php
/**
 * دکمهٔ افزودن به سبد در حلقهٔ فروشگاهی.
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

printf(
	'<a href="%1$s" data-quantity="%2$s" class="%3$s" data-zch-add-to-cart="%4$s" %5$s><span>%6$s</span>%7$s</a>',
	esc_url( $product->add_to_cart_url() ),
	esc_attr( isset( $args['quantity'] ) ? $args['quantity'] : 1 ),
	esc_attr( isset( $args['class'] ) ? $args['class'] : 'add' ),
	esc_attr( $product->get_id() ),
	isset( $args['attributes'] ) ? wc_implode_html_attributes( $args['attributes'] ) : '',
	esc_html( $product->add_to_cart_text() ),
	zch_get_icon( 'bag', [ 'size' => 17 ] ) // phpcs:ignore WordPress.Security.EscapeOutput
);
