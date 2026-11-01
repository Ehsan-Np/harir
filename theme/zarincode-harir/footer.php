<?php
/**
 * فوتر سایت و بستن سند.
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;
?>
	</main><!-- #zch-main -->

	<?php if ( ! ZCH_Theme::render_location( 'footer', '' ) ) : ?>
		<?php get_template_part( 'template-parts/footer/site', 'footer' ); ?>
	<?php endif; ?>

</div><!-- #zch-page -->

<?php wp_footer(); ?>
</body>
</html>
