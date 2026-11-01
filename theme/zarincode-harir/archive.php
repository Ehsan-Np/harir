<?php
/**
 * آرشیو — دسته، برچسب، نویسنده، تاریخ. مطابق `complete-01-blog.html`.
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="container complete-page">
	<div class="inner-main">

		<header class="page-heading">
			<?php
			if ( ! ZCH_Theme::render_location( 'archive', '' ) ) :
				?>
				<span class="overline"><?php echo esc_html( zch_archive_overline() ); ?></span>
				<h1><?php the_archive_title(); ?></h1>
				<?php the_archive_description( '<p class="page-lead">', '</p>' ); ?>
			<?php endif; ?>
		</header>

		<?php get_template_part( 'template-parts/content/page', 'heading' ); ?>

		<?php if ( have_posts() ) : ?>

			<div class="journal-body">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/content/content', 'card' );
				endwhile;
				?>
			</div>

			<nav class="zch-pagination" aria-label="<?php esc_attr_e( 'صفحه‌بندی', 'zarincode-harir' ); ?>">
				<?php
				the_posts_pagination(
					[
						'mid_size'  => 2,
						'prev_text' => esc_html__( 'قبلی', 'zarincode-harir' ),
						'next_text' => esc_html__( 'بعدی', 'zarincode-harir' ),
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
