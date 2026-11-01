<?php
/**
 * فرم جست‌وجو — مطابق `.searchbox` طرح.
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

$unique_id = wp_unique_id( 'zch-search-' );
?>
<form role="search" method="get" class="search-form searchbox zch-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $unique_id ); ?>">
		<?php esc_html_e( 'جست‌وجو برای:', 'zarincode-harir' ); ?>
	</label>

	<?php zch_icon( 'search', [ 'size' => 20 ] ); ?>

	<input type="search" id="<?php echo esc_attr( $unique_id ); ?>" class="search-field"
		name="s" value="<?php echo get_search_query(); ?>"
		placeholder="<?php echo esc_attr( zch_option( 'search_placeholder', __( 'به دنبال چه رویایی هستید؟ محصول یا دسته‌بندی...', 'zarincode-harir' ) ) ); ?>"
		autocomplete="off">

	<?php if ( class_exists( 'WooCommerce' ) ) : ?>
		<input type="hidden" name="post_type" value="product">
	<?php endif; ?>

	<button type="submit" class="zch-search-submit">
		<kbd><?php esc_html_e( 'جست‌وجو', 'zarincode-harir' ); ?></kbd>
	</button>
</form>
