<?php
/**
 * نمای فهرست یک نوشته.
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'article-card' ); ?>>

	<?php if ( has_post_thumbnail() ) : ?>
		<a class="article-photo" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
			<?php the_post_thumbnail( 'zch-product-card' ); ?>
		</a>
	<?php endif; ?>

	<div class="article-body">
		<h2 class="article-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>

		<div class="article-meta">
			<span><?php echo esc_html( zch_jalali_date( get_the_date( 'U' ), 'Y/m/d' ) ); ?></span>
			<span aria-hidden="true">·</span>
			<span><?php echo esc_html( get_the_author() ); ?></span>
		</div>

		<div class="article-excerpt">
			<?php the_excerpt(); ?>
		</div>

		<a class="article-more" href="<?php the_permalink(); ?>">
			<span><?php esc_html_e( 'ادامهٔ مطلب', 'zarincode-harir' ); ?></span>
			<?php zch_icon( 'arrow-start', [ 'size' => 14 ] ); ?>
		</a>
	</div>

</article>
