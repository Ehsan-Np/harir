<?php
/**
 * تمپلیت پایهٔ قالب — fallback نهایی.
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="container complete-page">
	<div class="inner-main">

		<?php get_template_part( 'template-parts/content/page', 'heading' ); ?>

		<?php if ( have_posts() ) : ?>

			<?php if ( is_home() && ! is_front_page() ) : ?>
				<header class="page-heading">
					<h1 class="screen-reader-text"><?php single_post_title(); ?></h1>
				</header>
			<?php endif; ?>

			<div class="zch-post-list">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/content/content', get_post_type() );
				endwhile;
				?>
			</div>

			<nav class="zch-pagination" aria-label="<?php esc_attr_e( 'صفحه‌بندی', 'zarincode-harir' ); ?>">
				<?php
				the_posts_pagination(
					[
						'mid_size'  => 2,
						'prev_text' => zch_get_icon( 'chevron-right', [ 'size' => 15 ] ) . ' ' . esc_html__( 'قبلی', 'zarincode-harir' ),
						'next_text' => esc_html__( 'بعدی', 'zarincode-harir' ) . ' ' . zch_get_icon( 'chevron-right', [ 'size' => 15 ] ),
					]
				);
				?>
			</nav>

		<?php else : ?>
			<?php get_template_part( 'template-parts/content/content', 'none' ); ?>
		<?php endif; ?>

	</div>

	<?php get_sidebar(); ?>

</div>

<?php
get_footer();
