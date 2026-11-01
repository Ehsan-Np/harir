<?php
/**
 * صفحهٔ نخست.
 *
 * اگر صفحه با المنتور ساخته شده باشد، خروجی المنتور چاپ می‌شود و قالب
 * هیچ مارکاپ اضافی حول آن نمی‌پیچد (تا چیدمان سازنده نشکند).
 * در غیر این صورت تمپلیت بومی مطابق `Home.html` مرجع اجرا می‌شود.
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( zch_is_built_with_elementor() ) :

	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;

else :

	$sections = [
		'hero',
		'services',
		'categories',
		'products',
		'wholesale',
		'discovery',
		'journal',
	];

	/**
	 * فیلتر بخش‌های صفحهٔ نخست بومی.
	 *
	 * @param string[] $sections
	 */
	$sections = apply_filters( 'zch/front_page_sections', $sections );

	foreach ( $sections as $section ) {
		$template = ZCH_THEME_DIR . '/template-parts/front/' . $section . '.php';

		if ( file_exists( $template ) ) {
			get_template_part( 'template-parts/front/' . $section );
		}
	}

endif;

get_footer();
