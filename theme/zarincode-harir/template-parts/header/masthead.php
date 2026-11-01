<?php
/**
 * مَستهِد — لوگو، جست‌وجو و اکشن‌ها.
 *
 * مارکاپ دقیقاً مطابق `.masthead` در طرح مرجع، با این تفاوت که
 * `<span>`ها به پیوند واقعی تبدیل شده‌اند.
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

$cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/' );
$myaccount_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();
$wholesale_url = (string) zch_option( 'wholesale_url', home_url( '/wholesale/' ) );
$cart_count    = zch_cart_count();
?>
<header class="header" role="banner">
	<div class="container masthead">

		<button type="button" class="mob-menu" data-zch-toggle="nav-drawer"
			aria-expanded="false" aria-controls="zch-nav-drawer"
			aria-label="<?php esc_attr_e( 'باز کردن منو', 'zarincode-harir' ); ?>">
			<?php zch_icon( 'menu', [ 'size' => 23 ] ); ?>
		</button>

		<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<?php zch_icon( 'brand', [ 'size' => 50, 'class' => 'brand-mark', 'stroke_width' => 0 ] ); ?>
				<span class="brand-name">
					<strong><?php bloginfo( 'name' ); ?></strong>
					<small><?php echo esc_html( zch_option( 'brand_latin', 'HARIR ROYA · HOME' ) ); ?></small>
				</span>
			<?php endif; ?>
		</a>

		<a class="searchbox" href="<?php echo esc_url( home_url( '/' ) ); ?>?s=" data-zch-search-open
			role="search" aria-label="<?php esc_attr_e( 'جست‌وجوی محصول', 'zarincode-harir' ); ?>">
			<?php zch_icon( 'search', [ 'size' => 20 ] ); ?>
			<span><?php echo esc_html( zch_option( 'search_placeholder', __( 'به دنبال چه رویایی هستید؟ محصول یا دسته‌بندی...', 'zarincode-harir' ) ) ); ?></span>
			<kbd><?php esc_html_e( 'جست‌وجو', 'zarincode-harir' ); ?></kbd>
		</a>

		<div class="head-actions">

			<?php if ( zch_option( 'show_wholesale_chip', true ) ) : ?>
				<a class="trade-chip" href="<?php echo esc_url( $wholesale_url ); ?>">
					<?php zch_icon( 'wholesale', [ 'size' => 17 ] ); ?>
					<span><?php echo esc_html( zch_option( 'wholesale_chip_label', __( 'خرید عمده', 'zarincode-harir' ) ) ); ?></span>
				</a>
			<?php endif; ?>

			<a class="action-user" href="<?php echo esc_url( is_user_logged_in() ? $myaccount_url : wp_login_url() ); ?>">
				<?php zch_icon( 'user', [ 'size' => 21 ] ); ?>
				<span>
					<?php if ( is_user_logged_in() ) : ?>
						<?php
						$current = wp_get_current_user();
						/* translators: %s: user display name */
						echo esc_html( sprintf( __( '%s عزیز', 'zarincode-harir' ), $current->display_name ) );
						?>
					<?php else : ?>
						<?php esc_html_e( 'ورود / ثبت‌نام', 'zarincode-harir' ); ?>
					<?php endif; ?>
				</span>
			</a>

			<?php if ( class_exists( 'WooCommerce' ) ) : ?>
				<a class="action-bag" href="<?php echo esc_url( $cart_url ); ?>"
					aria-label="<?php esc_attr_e( 'سبد خرید', 'zarincode-harir' ); ?>">
					<?php zch_icon( 'bag', [ 'size' => 21 ] ); ?>
					<i aria-hidden="true"><?php echo esc_html( zch_fa_digits( $cart_count ) ); ?></i>
					<span class="screen-reader-text">
						<?php
						/* translators: %d: cart items count */
						echo esc_html( sprintf( _n( '%d کالا در سبد', '%d کالا در سبد', $cart_count, 'zarincode-harir' ), $cart_count ) );
						?>
					</span>
				</a>
			<?php endif; ?>

		</div>
	</div>

	<div class="container mobile-search">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>?s=" data-zch-search-open>
			<?php zch_icon( 'search', [ 'size' => 18 ] ); ?>
			<span><?php esc_html_e( 'جست‌وجو در دنیای خواب...', 'zarincode-harir' ); ?></span>
		</a>
	</div>
</header>
