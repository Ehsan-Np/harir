<?php
/**
 * فوتر کامل — مطابق ساختار `footer.footer` در طرح مرجع.
 *
 * بخش‌ها: footer-top (برند + تماس‌ها + بازگشت به بالا)
 *         footer-services (۴ سرویس)
 *         footer-grid (۴ ستون منو + خبرنامه)
 *         footer-about (معرفی + نمادها)
 *         footer-bottom (اعتبارنامه)
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

$phone   = zch_support_phone();
$email   = (string) zch_option( 'support_email', get_bloginfo( 'admin_email' ) );
$address = (string) zch_option( 'address', '' );

$services = [
	[ 'icon' => 'wholesale',     'title' => __( 'خرید تکی و عمده', 'zarincode-harir' ), 'text' => __( 'یک انتخاب برای هر نیاز', 'zarincode-harir' ) ],
	[ 'icon' => 'palette',       'title' => __( 'رنگ و سایز متنوع', 'zarincode-harir' ), 'text' => __( 'انتخابی متناسب با سلیقهٔ شما', 'zarincode-harir' ) ],
	[ 'icon' => 'truck',         'title' => __( 'ارسال سفارش', 'zarincode-harir' ),      'text' => __( 'همراه شما تا مقصد', 'zarincode-harir' ) ],
	[ 'icon' => 'headset',       'title' => __( 'پشتیبانی و مشاوره', 'zarincode-harir' ),'text' => __( 'پاسخ به پرسش‌های شما', 'zarincode-harir' ) ],
];

/**
 * فیلتر سرویس‌های فوتر.
 *
 * @param array $services
 */
$services = apply_filters( 'zch/footer/services', $services );

$columns = [
	[ 'menu' => 'zch-footer-1', 'title' => __( 'محصولات خواب', 'zarincode-harir' ) ],
	[ 'menu' => 'zch-footer-2', 'title' => __( 'خدمات مشتریان', 'zarincode-harir' ) ],
	[ 'menu' => 'zch-footer-3', 'title' => __( 'خرید عمده', 'zarincode-harir' ) ],
	[ 'menu' => 'zch-footer-4', 'title' => __( 'حریر رویا', 'zarincode-harir' ) ],
];
?>
<footer class="footer" role="contentinfo">

	<div class="container footer-top">

		<div class="brand brand-foot">
			<?php zch_icon( 'brand', [ 'size' => 45, 'class' => 'brand-mark', 'stroke_width' => 0 ] ); ?>
			<span class="brand-name">
				<strong><?php bloginfo( 'name' ); ?></strong>
				<small><?php echo esc_html( zch_option( 'brand_latin', 'HARIR ROYA · HOME' ) ); ?></small>
			</span>
		</div>

		<div class="ft-connect">
			<?php if ( '' !== $phone ) : ?>
				<a class="ft-contact" href="tel:<?php echo esc_attr( preg_replace( '/\D+/', '', $phone ) ); ?>">
					<span class="ft-icon"><?php zch_icon( 'phone', [ 'size' => 17 ] ); ?></span>
					<span>
						<small><?php esc_html_e( 'مشاوره و سفارش', 'zarincode-harir' ); ?></small>
						<strong dir="ltr"><?php echo esc_html( zch_fa_digits( $phone ) ); ?></strong>
					</span>
				</a>
			<?php endif; ?>

			<?php if ( '' !== $email ) : ?>
				<a class="ft-contact" href="mailto:<?php echo esc_attr( antispambot( $email ) ); ?>">
					<span class="ft-icon"><?php zch_icon( 'mail', [ 'size' => 17 ] ); ?></span>
					<span>
						<small><?php esc_html_e( 'ایمیل پشتیبانی', 'zarincode-harir' ); ?></small>
						<strong dir="ltr"><?php echo esc_html( antispambot( $email ) ); ?></strong>
					</span>
				</a>
			<?php endif; ?>

			<a class="ft-contact ft-highlight" href="<?php echo esc_url( home_url( '/wholesale/' ) ); ?>">
				<span class="ft-icon"><?php zch_icon( 'wholesale', [ 'size' => 17 ] ); ?></span>
				<span>
					<small><?php esc_html_e( 'ویژه فروشگاه‌ها', 'zarincode-harir' ); ?></small>
					<strong><?php esc_html_e( 'همکاری عمده', 'zarincode-harir' ); ?></strong>
				</span>
			</a>
		</div>

		<button type="button" class="to-top" data-zch-scroll-top>
			<?php zch_icon( 'chevron-up', [ 'size' => 16 ] ); ?>
			<span><?php esc_html_e( 'بازگشت به بالا', 'zarincode-harir' ); ?></span>
		</button>

	</div>

	<div class="container footer-services">
		<?php foreach ( $services as $service ) : ?>
			<div class="service">
				<span class="service-icon"><?php zch_icon( $service['icon'], [ 'size' => 19 ] ); ?></span>
				<span>
					<strong><?php echo esc_html( $service['title'] ); ?></strong>
					<small><?php echo esc_html( $service['text'] ); ?></small>
				</span>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="container footer-grid">

		<?php foreach ( $columns as $column ) : ?>
			<div class="footer-column">
				<h4><?php echo esc_html( $column['title'] ); ?></h4>

				<?php
				if ( has_nav_menu( $column['menu'] ) ) {
					wp_nav_menu(
						[
							'theme_location' => $column['menu'],
							'container'      => false,
							'items_wrap'     => '%3$s',
							'depth'          => 1,
							'fallback_cb'    => false,
						]
					);
				} else {
					// fallback: پیوندهای مفید بر اساس نوع صفحهٔ موجود.
					$fallback = [];

					if ( function_exists( 'wc_get_page_permalink' ) ) {
						$fallback[] = [ __( 'فروشگاه', 'zarincode-harir' ), wc_get_page_permalink( 'shop' ) ];
						$fallback[] = [ __( 'سبد خرید', 'zarincode-harir' ), wc_get_page_permalink( 'cart' ) ];
						$fallback[] = [ __( 'حساب کاربری', 'zarincode-harir' ), wc_get_page_permalink( 'myaccount' ) ];
					}

					$fallback[] = [ __( 'درباره ما', 'zarincode-harir' ), home_url( '/about/' ) ];
					$fallback[] = [ __( 'تماس با ما', 'zarincode-harir' ), home_url( '/contact/' ) ];
					$fallback[] = [ __( 'پرسش‌های متداول', 'zarincode-harir' ), home_url( '/faq/' ) ];
					$fallback[] = [ __( 'قوانین و شرایط', 'zarincode-harir' ), home_url( '/terms/' ) ];
					$fallback[] = [ __( 'حریم خصوصی', 'zarincode-harir' ), home_url( '/privacy/' ) ];

					/**
					 * فیلتر پیوندهای جایگزین ستون‌های فوتر.
					 *
					 * @param array  $fallback
					 * @param string $menu
					 */
					$fallback = apply_filters( 'zch/footer/fallback_links', $fallback, $column['menu'] );

					foreach ( array_slice( $fallback, 0, 6 ) as $link ) {
						printf(
							'<a href="%1$s">%2$s</a>',
							esc_url( $link[1] ),
							esc_html( $link[0] )
						);
					}
				}
				?>
			</div>
		<?php endforeach; ?>

		<div class="newsletter">
			<span class="newsletter-symbol"><?php zch_icon( 'mail', [ 'size' => 18 ] ); ?></span>
			<h4><?php echo esc_html( zch_option( 'newsletter_title', __( 'از رویاها باخبر باشید', 'zarincode-harir' ) ) ); ?></h4>
			<p><?php echo esc_html( zch_option( 'newsletter_text', __( 'تازه‌ترین محصولات، راهنمای انتخاب و پیشنهادهای ویژه را زودتر از دیگران دریافت کنید.', 'zarincode-harir' ) ) ); ?></p>

			<form class="news-input" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" data-zch-newsletter>
				<label class="screen-reader-text" for="zch-newsletter-email">
					<?php esc_html_e( 'ایمیل شما', 'zarincode-harir' ); ?>
				</label>
				<input id="zch-newsletter-email" type="email" name="zch_newsletter" dir="ltr"
					placeholder="<?php esc_attr_e( 'ایمیل شما', 'zarincode-harir' ); ?>" required>
				<button type="submit"><?php esc_html_e( 'عضویت', 'zarincode-harir' ); ?></button>
			</form>

			<?php
			$socials = (string) zch_option( 'socials', '' );
			if ( has_nav_menu( 'zch-social' ) ) :
				?>
				<p class="social-title"><?php esc_html_e( 'حریر رویا در شبکه‌ها', 'zarincode-harir' ); ?></p>
				<div class="socials">
					<?php
					wp_nav_menu(
						[
							'theme_location' => 'zch-social',
							'container'      => false,
							'items_wrap'     => '%3$s',
							'depth'          => 1,
							'fallback_cb'    => false,
						]
					);
					?>
				</div>
			<?php endif; ?>
		</div>

	</div>

	<?php if ( '' !== $address || zch_option( 'show_seals', true ) ) : ?>
		<div class="container footer-about">
			<div>
				<strong><?php bloginfo( 'name' ); ?></strong>
				<p>
					<?php
					$about = zch_option( 'footer_about', get_bloginfo( 'description' ) );
					echo esc_html( $about );
					if ( '' !== $address ) {
						echo '<br>' . esc_html( $address );
					}
					?>
				</p>
			</div>

			<?php if ( zch_option( 'show_seals', true ) ) : ?>
				<div class="seals">
					<?php
					$seals = [
						[ 'icon' => 'shield-check', 'label' => __( 'پرداخت امن', 'zarincode-harir' ) ],
						[ 'icon' => 'refresh',      'label' => __( '۷ روز بازگشت', 'zarincode-harir' ) ],
						[ 'icon' => 'ticket',       'label' => __( 'ضمانت اصالت', 'zarincode-harir' ) ],
					];

					/**
					 * فیلتر نمادهای اعتماد فوتر.
					 *
					 * @param array $seals
					 */
					$seals = apply_filters( 'zch/footer/seals', $seals );

					foreach ( $seals as $seal ) :
						?>
						<div>
							<?php zch_icon( $seal['icon'], [ 'size' => 22 ] ); ?>
							<span><?php echo esc_html( $seal['label'] ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="container footer-bottom">
		<span>
			<?php zch_icon( 'sparkle', [ 'size' => 13 ] ); ?>
			<?php
			printf(
				/* translators: 1: year, 2: site name */
				esc_html__( '© %1$s %2$s — همهٔ حقوق محفوظ است.', 'zarincode-harir' ),
				esc_html( zch_fa_digits( gmdate( 'Y' ) ) ),
				esc_html( get_bloginfo( 'name' ) )
			);
			?>
		</span>

		<span class="site-credit">
			<?php
			printf(
				/* translators: %s: author link */
				esc_html__( 'طراحی و توسعه: %s', 'zarincode-harir' ),
				'<a href="' . esc_url( zch_author_uri() ) . '" rel="nofollow noopener" target="_blank">' . esc_html__( 'زارین‌کد — احسان نادری پناه', 'zarincode-harir' ) . '</a>'
			);
			?>
		</span>
	</div>

</footer>
