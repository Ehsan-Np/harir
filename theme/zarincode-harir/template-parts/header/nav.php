<?php
/**
 * ناوبری اصلی + منوی دسته‌بندی + یادداشت پشتیبانی.
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

$catalog_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$support_url = (string) zch_option( 'support_url', home_url( '/contact/' ) );
?>
<nav class="container nav" id="zch-nav-drawer" aria-label="<?php esc_attr_e( 'ناوبری اصلی', 'zarincode-harir' ); ?>">

	<div class="nav-links">

		<?php if ( class_exists( 'WooCommerce' ) ) : ?>
			<a class="all-cats" href="<?php echo esc_url( $catalog_url ); ?>"
				aria-haspopup="true" aria-expanded="false" data-zch-dropdown="categories">
				<?php zch_icon( 'menu', [ 'size' => 18 ] ); ?>
				<span><?php esc_html_e( 'دسته‌بندی محصولات', 'zarincode-harir' ); ?></span>
				<?php zch_icon( 'chevron-down', [ 'size' => 15, 'class' => 'nav-caret' ] ); ?>
			</a>

			<?php
			$cats = get_terms(
				[
					'taxonomy'   => 'product_cat',
					'hide_empty' => true,
					'parent'     => 0,
					'number'     => 12,
					'orderby'    => 'menu_order',
					'order'      => 'ASC',
				]
			);

			if ( ! is_wp_error( $cats ) && ! empty( $cats ) ) :
				?>
				<div class="zch-cat-dropdown" id="zch-cat-dropdown" role="group" hidden>
					<?php foreach ( $cats as $cat ) : ?>
						<a href="<?php echo esc_url( get_term_link( $cat ) ); ?>">
							<?php echo esc_html( $cat->name ); ?>
							<small><?php echo esc_html( zch_fa_digits( $cat->count ) ); ?></small>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>

		<?php
		if ( has_nav_menu( 'zch-primary' ) ) {
			wp_nav_menu(
				[
					'theme_location' => 'zch-primary',
					'container'      => false,
					'items_wrap'     => '%3$s',
					'depth'          => 2,
					'walker'         => new ZCH_Nav_Walker(),
					'fallback_cb'    => false,
				]
			);
		} else {
			// اگر منویی ساخته نشده، دسته‌بندی‌های محصول به‌عنوان fallback.
			if ( class_exists( 'WooCommerce' ) && ! empty( $cats ) ) {
				foreach ( array_slice( $cats, 0, 6 ) as $cat ) {
					printf(
						'<a class="nav-item" href="%1$s"><span class="nav-item-label">%2$s</span></a>',
						esc_url( get_term_link( $cat ) ),
						esc_html( $cat->name )
					);
				}
			}
		}
		?>
	</div>

	<?php if ( zch_option( 'show_support_note', true ) ) : ?>
		<a class="nav-note" href="<?php echo esc_url( $support_url ); ?>">
			<?php zch_icon( 'chat', [ 'size' => 17 ] ); ?>
			<span><?php echo esc_html( zch_option( 'support_note', __( 'از حریر یار بپرس', 'zarincode-harir' ) ) ); ?></span>
		</a>
	<?php endif; ?>

</nav>
