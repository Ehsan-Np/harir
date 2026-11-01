<?php
/**
 * Walker ناوبری — خروجی مطابق مارکاپ اصلی طرح.
 *
 * در HTML مرجع، آیتم‌های منو `<span>` بودند. اینجا به `<a>` واقعی تبدیل
 * می‌شوند ولی همان کلاس‌های بصری طرح حفظ می‌شود تا خروجی ۱:۱ بماند.
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

/**
 * Walker مخصوص منوی اصلی حریر.
 */
class ZCH_Nav_Walker extends Walker_Nav_Menu {

	/**
	 * @param string   $output
	 * @param int      $depth
	 * @param stdClass $args
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ) {
		$indent  = str_repeat( "\t", $depth );
		$classes = [ 'zch-submenu', 'nav-sub' ];

		/**
		 * فیلتر کلاس‌های زیرمنو.
		 *
		 * @param string[] $classes
		 * @param int      $depth
		 */
		$classes = apply_filters( 'zch/nav/submenu_classes', $classes, $depth );

		$output .= "\n{$indent}<ul class=\"" . esc_attr( implode( ' ', $classes ) ) . "\" role=\"group\">\n";
	}

	/**
	 * @param string   $output
	 * @param WP_Post  $item
	 * @param stdClass $args
	 * @param int      $depth
	 */
	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$indent = $depth ? str_repeat( "\t", $depth ) : '';

		$classes   = empty( $item->classes ) ? [] : (array) $item->classes;
		$classes[] = 'nav-item';

		if ( in_array( 'menu-item-has-children', $classes, true ) ) {
			$classes[] = 'has-children';
		}

		if ( in_array( 'current-menu-item', $classes, true ) ) {
			$classes[] = 'active';
		}

		/**
		 * فیلتر کلاس‌های هر آیتم منو.
		 *
		 * @param string[] $classes
		 * @param WP_Post  $item
		 * @param int      $depth
		 */
		$classes = apply_filters( 'zch/nav/item_classes', array_unique( $classes ), $item, $depth );

		$class_names = trim( implode( ' ', array_map( 'sanitize_html_class', $classes ) ) );

		$link_attrs = [
			'href' => ! empty( $item->url ) ? esc_url( $item->url ) : '',
		];

		if ( ! empty( $item->attr_title ) ) {
			$link_attrs['title'] = esc_attr( $item->attr_title );
		}
		if ( ! empty( $item->target ) ) {
			$link_attrs['target'] = esc_attr( $item->target );
		}
		if ( '_blank' === $item->target ) {
			$link_attrs['rel'] = 'noopener noreferrer';
		}

		$has_children = in_array( 'menu-item-has-children', (array) $item->classes, true );

		if ( $has_children ) {
			$link_attrs['aria-haspopup'] = 'true';
			$link_attrs['aria-expanded'] = 'false';
		}

		$attributes = '';
		foreach ( $link_attrs as $attr => $value ) {
			$attributes .= sprintf( ' %s="%s"', $attr, $value );
		}

		$title = apply_filters( 'the_title', $item->title, $item->ID ); // phpcs:ignore WordPress.WP.PostFunctionsNoReturn

		$title = apply_filters( 'zch/nav/item_title', $title, $item, $depth, $args ); // phpcs:ignore

		$item_output  = $args->before;
		$item_output .= '<a' . $attributes . '>';
		$item_output .= $args->link_before . '<span class="nav-item-label">' . esc_html( wp_strip_all_tags( $title ) ) . '</span>' . $args->link_after;

		if ( $has_children ) {
			$item_output .= zch_get_icon( 'chevron-down', [ 'size' => 15, 'class' => 'nav-caret' ] );
		}

		$item_output .= '</a>';
		$item_output .= $args->after;

		$output .= sprintf(
			"\n%s<li id=\"menu-item-%d\" class=\"%s\">%s",
			$indent,
			absint( $item->ID ),
			esc_attr( $class_names ),
			$item_output
		);
	}
}
