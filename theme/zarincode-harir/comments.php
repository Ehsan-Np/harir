<?php
/**
 * قالب دیدگاه‌ها — مطابق بخش «دیدگاه‌های خریداران» در صفحهٔ محصول.
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	return;
}
?>
<section id="comments" class="comments-area review-summary">

	<?php if ( have_comments() ) : ?>

		<header class="section-head">
			<div>
				<h2><?php esc_html_e( 'دیدگاه‌ها', 'zarincode-harir' ); ?></h2>
				<p>
					<?php
					$count = (int) get_comments_number();
					/* translators: %d: comment count */
					printf( esc_html( _n( '%d دیدگاه', '%d دیدگاه', $count, 'zarincode-harir' ) ), esc_html( zch_fa_digits( $count ) ) );
					?>
				</p>
			</div>
		</header>

		<ol class="comment-list">
			<?php
			wp_list_comments(
				[
					'style'      => 'ol',
					'short_ping' => true,
					'avatar_size' => 44,
				]
			);
			?>
		</ol>

		<?php
		the_comments_navigation(
			[
				'prev_text' => esc_html__( 'دیدگاه‌های قبلی', 'zarincode-harir' ),
				'next_text' => esc_html__( 'دیدگاه‌های بعدی', 'zarincode-harir' ),
			]
		);
		?>

	<?php endif; ?>

	<?php if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) : ?>
		<p class="no-comments"><?php esc_html_e( 'دیدگاه‌ها بسته شده‌اند.', 'zarincode-harir' ); ?></p>
	<?php endif; ?>

	<?php
	comment_form(
		[
			'class_form'    => 'zch-comment-form field-box',
			'title_reply'   => __( 'دیدگاه خود را بنویسید', 'zarincode-harir' ),
			'label_submit'  => __( 'ثبت دیدگاه', 'zarincode-harir' ),
			'submit_button' => '<button type="submit" class="btn primary" id="%2$s">%3$s</button>',
		]
	);
	?>

</section>
