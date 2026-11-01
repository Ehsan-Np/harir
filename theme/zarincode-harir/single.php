<?php
/**
 * نوشتهٔ تکی وبلاگ — مطابق `complete-01-article.html`.
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

				<article id="post-<?php the_ID(); ?>" <?php post_class( 'article-wrap' ); ?>>

					<?php if ( has_post_thumbnail() ) : ?>
						<div class="article-cover">
							<?php the_post_thumbnail( 'zch-hero', [ 'loading' => 'eager', 'fetchpriority' => 'high' ] ); ?>
						</div>
					<?php endif; ?>

					<header class="page-heading">
						<h1><?php the_title(); ?></h1>
						<div class="article-meta">
							<span class="overline"><?php echo esc_html( zch_jalali_date( get_the_date( 'U' ), 'Y/m/d' ) ); ?></span>
							<span><?php echo esc_html( get_the_author() ); ?></span>
							<span><?php echo esc_html( zch_reading_time() ); ?></span>
						</div>
					</header>

					<?php get_template_part( 'template-parts/content/toc' ); ?>

					<div class="article-body entry-content zch-rich">
						<?php the_content(); ?>
					</div>

					<footer class="article-foot">
						<?php the_tags( '<div class="article-tags">', ' ', '</div>' ); ?>
					</footer>

				</article>

				<?php get_template_part( 'template-parts/content/read', 'next' ); ?>

				<?php
				if ( comments_open() || get_comments_number() ) {
					comments_template();
				}
				?>

			</div>

			<aside class="detail-side">
				<?php dynamic_sidebar( 'zch-sidebar' ); ?>
			</aside>

		</div>
		<?php
	endif;

endwhile;

get_footer();
