<?php
/**
 * مجله خواب — مطابق `section.journal` (۳ کارت).
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

$count = (int) zch_option( 'home_journal_count', 3 );

$posts = get_posts(
	/**
	 * فیلتر کوئری مجلهٔ صفحهٔ نخست.
	 *
	 * @param array $args
	 */
	apply_filters( 'zch/front/journal_query', [
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => $count,
		'no_found_rows'  => true,
		'ignore_sticky_posts' => true,
	] )
);

if ( empty( $posts ) ) {
	return;
}

$blog_url = get_permalink( (int) get_option( 'page_for_posts' ) );

if ( ! $blog_url ) {
	$blog_url = home_url( '/blog/' );
}
?>
<section class="container section journal">

	<div class="section-head">
		<div>
			<div class="section-label"><?php echo esc_html( zch_option( 'home_journal_label', 'THE SLEEP JOURNAL' ) ); ?></div>
			<h2><?php echo esc_html( zch_option( 'home_journal_title', __( 'مجله خوابِ حریر رویا', 'zarincode-harir' ) ) ); ?></h2>
			<p><?php echo esc_html( zch_option( 'home_journal_text', __( 'ایده‌ها و راهنماهایی برای انتخاب بهتر', 'zarincode-harir' ) ) ); ?></p>
		</div>

		<a class="view-all" href="<?php echo esc_url( $blog_url ); ?>">
			<?php esc_html_e( 'مشاهده همه', 'zarincode-harir' ); ?>
			<?php zch_icon( 'arrow-start', [ 'size' => 16 ] ); ?>
		</a>
	</div>

	<div class="article-grid">
		<?php foreach ( $posts as $post ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride ?>
			<?php setup_postdata( $post ); ?>

			<?php
			$cats = get_the_category( $post->ID );
			?>
			<article <?php post_class( 'article', $post->ID ); ?>>
				<a class="article-img" href="<?php echo esc_url( get_permalink( $post ) ); ?>" tabindex="-1" aria-hidden="true">
					<?php
					if ( has_post_thumbnail( $post ) ) {
						echo get_the_post_thumbnail( $post, 'zch-product-card', [ 'loading' => 'lazy' ] );
					} else {
						echo '<span class="zch-placeholder"></span>';
					}
					?>
				</a>

				<div class="article-body">
					<small><?php echo esc_html( ! empty( $cats ) ? $cats[0]->name : zch_jalali_date( get_post_time( 'U', false, $post ), 'Y/m/d' ) ); ?></small>
					<strong><a href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a></strong>
					<a href="<?php echo esc_url( get_permalink( $post ) ); ?>">
						<?php esc_html_e( 'مطالعه مطلب', 'zarincode-harir' ); ?>
						<?php zch_icon( 'arrow-start', [ 'size' => 15 ] ); ?>
					</a>
				</div>
			</article>
		<?php endforeach; ?>

		<?php wp_reset_postdata(); ?>
	</div>

</section>
