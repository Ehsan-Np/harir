<?php
/**
 * صفحهٔ ۴۰۴ — مطابق `complete-01-404.html`.
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="container complete-page page-404">
	<div class="inner-main">

		<?php if ( ! ZCH_Theme::render_location( '404', '' ) ) : ?>

			<div class="page-heading not-found">
				<span class="overline"><?php echo esc_html( zch_option( '404_overline', 'PAGE NOT FOUND' ) ); ?></span>
				<h1><?php echo esc_html( zch_option( '404_title', __( 'این صفحه را پیدا نکردیم', 'zarincode-harir' ) ) ); ?></h1>
				<p class="page-lead">
					<?php echo esc_html( zch_option( '404_text', __( 'نشانی را اشتباه وارد کرده‌اید یا این صفحه جابه‌جا شده است. از جست‌وجو یا پیوندهای زیر ادامه دهید.', 'zarincode-harir' ) ) ); ?>
				</p>

				<div class="hero-actions">
					<a class="btn primary" href="<?php echo esc_url( home_url( '/' ) ); ?>">
						<?php zch_icon( 'home', [ 'size' => 17 ] ); ?>
						<span><?php esc_html_e( 'بازگشت به صفحهٔ نخست', 'zarincode-harir' ); ?></span>
					</a>

					<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
						<a class="btn outline" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
							<?php zch_icon( 'grid', [ 'size' => 17 ] ); ?>
							<span><?php esc_html_e( 'دیدن محصولات', 'zarincode-harir' ); ?></span>
						</a>
					<?php endif; ?>
				</div>
			</div>

			<?php get_search_form(); ?>

			<section class="section read-next">
				<div class="section-head">
					<div>
						<h2><?php esc_html_e( 'شاید این‌ها را بپسندید', 'zarincode-harir' ); ?></h2>
					</div>
				</div>

				<?php zch_render_suggested_products(); ?>
			</section>

		<?php endif; ?>

	</div>
</div>

<?php
get_footer();
