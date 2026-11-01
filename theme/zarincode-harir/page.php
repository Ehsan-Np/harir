<?php
/**
 * صفحات ایستا.
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	if ( zch_is_built_with_elementor( get_the_ID() ) ) :
		the_content();
	else :
		?>
		<div class="container complete-page">
			<div class="inner-main">

				<?php get_template_part( 'template-parts/content/page', 'heading' ); ?>

				<article id="post-<?php the_ID(); ?>" <?php post_class( 'policy-layout' ); ?>>
					<div class="entry-content zch-rich detail-copy">
						<?php
						the_content();

						wp_link_pages(
							[
								'before' => '<div class="page-links">' . esc_html__( 'صفحه‌ها:', 'zarincode-harir' ),
								'after'  => '</div>',
							]
						);
						?>
					</div>
				</article>

				<?php
				if ( comments_open() || get_comments_number() ) {
					comments_template();
				}
				?>

			</div>

			<?php get_sidebar(); ?>

		</div>
		<?php
	endif;

endwhile;

get_footer();
