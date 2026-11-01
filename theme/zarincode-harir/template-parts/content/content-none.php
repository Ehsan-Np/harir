<?php
/**
 * حالت خالی — وقتی نتیجه‌ای پیدا نشد.
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="no-results not-found">
	<header class="page-heading">
		<h2><?php esc_html_e( 'چیزی پیدا نشد', 'zarincode-harir' ); ?></h2>
		<p class="page-lead">
			<?php esc_html_e( 'با واژه‌های دیگری جست‌وجو کنید یا از فهرست دسته‌بندی‌ها ادامه دهید.', 'zarincode-harir' ); ?>
		</p>
	</header>

	<?php get_search_form(); ?>
</section>
