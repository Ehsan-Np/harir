<?php
/**
 * کارت نوشته در آرشیو — مطابق `.article-card` طرح.
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

$cat = get_the_category();
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'article-card' ); ?>>

	<a class="article-photo" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
		<?php
		if ( has_post_thumbnail() ) {
			the_post_thumbnail( 'zch-product-card' );
		} else {
			echo '<span class="zch-placeholder"></span>';
		}
		?>
		<?php if ( ! empty( $cat ) ) : ?>
			<span class="article-cat"><?php echo esc_html( $cat[0]->name ); ?></span>
		<?php endif; ?>
	</a>

	<div class="article-body">
		<h3 class="article-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>

		<p class="article-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 24, '…' ) ); ?></p>

		<div class="article-meta">
			<span><?php echo esc_html( zch_jalali_date( get_the_date( 'U' ), 'Y/m/d' ) ); ?></span>
			<span><?php echo esc_html( zch_reading_time() ); ?></span>
		</div>
	</div>

</article>
