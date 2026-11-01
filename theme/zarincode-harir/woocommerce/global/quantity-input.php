<?php
/**
 * ورودی تعداد — نسخهٔ آگاه از min/max/step نقش کاربر.
 *
 * تفاوت با نسخهٔ اصلی ووکامرس: دکمه‌های ± اضافه شده و `step`،
 * `min` و `max` از قواعد نقش (مشتری/همکار) خوانده می‌شوند، نه فقط
 * از تنظیمات سراسری.
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

if ( $max_value && $min_value === $max_value ) {
	?>
	<div class="quantity hidden">
		<input type="hidden" id="<?php echo esc_attr( $input_id ); ?>" class="qty"
			name="<?php echo esc_attr( $input_name ); ?>" value="<?php echo esc_attr( $min_value ); ?>">
	</div>
	<?php
	return;
}
?>
<div class="quantity zch-qty" data-zch-qty>

	<button type="button" class="qty-btn qty-minus" data-zch-qty-step="-1"
		aria-label="<?php esc_attr_e( 'کاهش تعداد', 'zarincode-harir' ); ?>">
		<?php zch_icon( 'plus', [ 'size' => 14, 'class' => 'zch-rotate' ] ); ?>
	</button>

	<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>">
		<?php esc_html_e( 'تعداد', 'zarincode-harir' ); ?>
	</label>

	<input type="number"
		id="<?php echo esc_attr( $input_id ); ?>"
		class="<?php echo esc_attr( join( ' ', (array) $classes ) ); ?>"
		step="<?php echo esc_attr( $step ); ?>"
		min="<?php echo esc_attr( $min_value ); ?>"
		max="<?php echo esc_attr( 0 < $max_value ? $max_value : '' ); ?>"
		name="<?php echo esc_attr( $input_name ); ?>"
		value="<?php echo esc_attr( $input_value ); ?>"
		title="<?php esc_attr_e( 'تعداد', 'zarincode-harir' ); ?>"
		size="4"
		inputmode="numeric"
		autocomplete="off"
		data-min="<?php echo esc_attr( $min_value ); ?>"
		data-max="<?php echo esc_attr( $max_value ); ?>"
		data-step="<?php echo esc_attr( $step ); ?>">

	<button type="button" class="qty-btn qty-plus" data-zch-qty-step="1"
		aria-label="<?php esc_attr_e( 'افزایش تعداد', 'zarincode-harir' ); ?>">
		<?php zch_icon( 'plus', [ 'size' => 14 ] ); ?>
	</button>

	<?php if ( (int) $step > 1 ) : ?>
		<small class="zch-qty-hint">
			<?php
			/* translators: %s: quantity step */
			printf( esc_html__( 'مضربی از %s', 'zarincode-harir' ), esc_html( zch_fa_digits( $step ) ) );
			?>
		</small>
	<?php endif; ?>

</div>
