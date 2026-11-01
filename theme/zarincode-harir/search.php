<?php
/**
 * نتایج جست‌وجو.
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="container complete-page">
	<div class="inner-main">

		<header class="page-heading">
			<h1>
				<?php
				/* translators: %s: search query */
				printf( esc_html__( 'نتایج جست‌وجو برای: %s', 'zarincode-harir' ), '<span>' . esc_html( get_search_query() ) . '</span>' );
				?>
			</h1>
			<p class="page-lead">
				<?php
				global $wp_query;
				/* translators: %d: number of results */
				printf( esc_html( _n( '%d نتیجه یافت شد', '%d نتیجه یافت شد', (int) $wp_query->found_posts, 'zarincode-harir' ) ), esc_html( zch_fa_digits( (int) $wp_query->found_posts ) ) );
				?>
			</p>
		</header>

		<?php get_search_form(); ?>

		<?php if ( have_posts() ) : ?>
			<div class="journal-body">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/content/content', 'card' );
				endwhile;
				?>
			</div>

			<nav class="zch-pagination"><?php the_posts_pagination(); ?></nav>
		<?php else : ?>
			<?php get_template_part( 'template-parts/content/content', 'none' ); ?>
		<?php endif; ?>

	</div>
</div>

<?php
get_footer();
