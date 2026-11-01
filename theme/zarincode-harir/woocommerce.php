<?php
/**
 * تمپلیت عمومی ووکامرس.
 *
 * همهٔ صفحات فروشگاهی از اینجا عبور می‌کنند؛ تمپلیت‌های اختصاصی‌تر
 * (single-product.php، archive-product.php و…) اولویت دارند.
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="container complete-page zch-woocommerce">
	<div class="inner-main">

		<?php
		/**
		 * هوک استاندارد ووکامرس — پیام‌ها، عنوان و محتوا.
		 */
		woocommerce_content();
		?>

	</div>
</div>

<?php
get_footer();
