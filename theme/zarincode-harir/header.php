<?php
/**
 * سربرگ سند و هدر سایت.
 *
 * ساختار دقیقاً مطابق `Home.html` مرجع است:
 * topline → header(masthead + mobile-search + nav)
 *
 * اگر برای location «header» تمپلیت المنتور وجود داشته باشد، المنتور
 * کنترل را به دست می‌گیرد و مارکاپ بومی چاپ نمی‌شود.
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<meta name="theme-color" content="#59354e">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class( 'dream' ); ?>>
<?php wp_body_open(); ?>

<a class="zch-skip-link screen-reader-text" href="#zch-main">
	<?php esc_html_e( 'پرش به محتوای اصلی', 'zarincode-harir' ); ?>
</a>

<div id="zch-page" class="zch-page">

	<?php
	// اگر المنتور Pro تمپلیت هدر دارد، فقط همان چاپ می‌شود.
	if ( ! ZCH_Theme::render_location( 'header', '' ) ) :
		get_template_part( 'template-parts/header/topline' );
		get_template_part( 'template-parts/header/masthead' );
		get_template_part( 'template-parts/header/nav' );
		get_template_part( 'template-parts/header/mobile-bar' );
	endif;
	?>

	<main id="zch-main" class="zch-main" role="main">
