<?php
/**
 * سرصفحهٔ داخلی — مطابق `.page-heading` در صفحات `complete-*.html`.
 *
 * breadcrumb + overline + h1 + lead
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

$heading = zch_page_heading();

if ( '' === $heading['title'] ) {
	return;
}
?>
<header class="page-heading">

	<?php if ( zch_option( 'show_breadcrumb', true ) ) : ?>
		<nav class="breadcrumb" aria-label="<?php esc_attr_e( 'مسیر صفحه', 'zarincode-harir' ); ?>">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<?php zch_icon( 'home', [ 'size' => 14 ] ); ?>
				<span><?php esc_html_e( 'خانه', 'zarincode-harir' ); ?></span>
			</a>

			<?php foreach ( $heading['trail'] as $crumb ) : ?>
				<span class="bc-sep" aria-hidden="true"><?php zch_icon( 'arrow-start', [ 'size' => 12 ] ); ?></span>

				<?php if ( ! empty( $crumb['url'] ) ) : ?>
					<a href="<?php echo esc_url( $crumb['url'] ); ?>"><?php echo esc_html( $crumb['label'] ); ?></a>
				<?php else : ?>
					<span aria-current="page"><?php echo esc_html( $crumb['label'] ); ?></span>
				<?php endif; ?>
			<?php endforeach; ?>
		</nav>
	<?php endif; ?>

	<?php if ( '' !== $heading['overline'] ) : ?>
		<span class="overline"><?php echo esc_html( $heading['overline'] ); ?></span>
	<?php endif; ?>

	<?php if ( '' !== $heading['title'] ) : ?>
		<h1><?php echo esc_html( $heading['title'] ); ?></h1>
	<?php endif; ?>

	<?php if ( '' !== $heading['lead'] ) : ?>
		<p class="page-lead"><?php echo wp_kses_post( $heading['lead'] ); ?></p>
	<?php endif; ?>

</header>
