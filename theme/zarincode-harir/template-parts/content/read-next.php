<?php
/**
 * پیوندهای قبلی/بعدی و مطالب مرتبط — مطابق `.read-next` طرح.
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

$prev = get_previous_post();
$next = get_next_post();

if ( ! $prev && ! $next ) {
	return;
}
?>
<section class="section read-next">
	<div class="section-head">
		<div>
			<h2><?php esc_html_e( 'مطالب مرتبط', 'zarincode-harir' ); ?></h2>
			<p><?php esc_html_e( 'ادامهٔ خواندن دربارهٔ خواب راحت', 'zarincode-harir' ); ?></p>
		</div>
	</div>

	<div class="read-grid">
		<?php if ( $prev ) : ?>
			<a class="read-card" href="<?php echo esc_url( get_permalink( $prev ) ); ?>" rel="prev">
				<span class="read-dir"><?php zch_icon( 'chevron-right', [ 'size' => 15 ] ); ?></span>
				<span class="read-body">
					<small><?php esc_html_e( 'مطلب قبلی', 'zarincode-harir' ); ?></small>
					<strong><?php echo esc_html( get_the_title( $prev ) ); ?></strong>
				</span>
			</a>
		<?php endif; ?>

		<?php if ( $next ) : ?>
			<a class="read-card" href="<?php echo esc_url( get_permalink( $next ) ); ?>" rel="next">
				<span class="read-body">
					<small><?php esc_html_e( 'مطلب بعدی', 'zarincode-harir' ); ?></small>
					<strong><?php echo esc_html( get_the_title( $next ) ); ?></strong>
				</span>
				<span class="read-dir"><?php zch_icon( 'arrow-start', [ 'size' => 15 ] ); ?></span>
			</a>
		<?php endif; ?>
	</div>
</section>
