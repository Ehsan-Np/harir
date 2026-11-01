<?php
/**
 * فهرست مطالب نوشته (Table of Contents).
 *
 * از `<!--wp:heading-->`ها و تگ‌های h2/h3 داخل محتوا ساخته می‌شود.
 * مطابق `.toc-card` در `complete-01-article.html`.
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

if ( ! zch_option( 'show_toc', true ) ) {
	return;
}

$items = zch_extract_headings( get_the_content() );

if ( count( $items ) < 2 ) {
	return;
}
?>
<nav class="toc-card" aria-label="<?php esc_attr_e( 'فهرست مطالب', 'zarincode-harir' ); ?>">
	<span class="toc-title">
		<?php zch_icon( 'menu', [ 'size' => 15 ] ); ?>
		<strong><?php esc_html_e( 'در این مقاله', 'zarincode-harir' ); ?></strong>
	</span>

	<div class="toc-list">
		<?php foreach ( $items as $index => $item ) : ?>
			<a href="#zch-h-<?php echo esc_attr( $index ); ?>"
				class="toc-item<?php echo 3 === (int) $item['level'] ? ' toc-sub' : ''; ?>"
				data-zch-toc>
				<span class="toc-index"><?php echo esc_html( zch_fa_digits( $index + 1 ) ); ?></span>
				<?php echo esc_html( $item['text'] ); ?>
			</a>
		<?php endforeach; ?>
	</div>
</nav>
