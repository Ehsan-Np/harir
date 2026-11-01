<?php
/**
 * مدیر ارسال پیامک — دیسپچ به درگاه فعال + ثبت لاگ + محدودسازی نرخ.
 *
 * @package Zarincode\Harir\Sms
 */

namespace Zarincode\Harir\Sms;

defined( 'ABSPATH' ) || exit;

use Zarincode\Harir\Sms\Contracts\GatewayInterface;
use Zarincode\Harir\Sms\Gateways\Kavenegar;
use Zarincode\Harir\Sms\Gateways\Melipayamak;
use Zarincode\Harir\Helpers\Mobile;

/**
 * نقطهٔ ورود واحد برای همهٔ ارسال‌های پیامکی پروژه.
 */
final class SmsManager {

	const OPTION_GATEWAY = 'zch_sms_gateway';
	const OPTION_ENABLED = 'zch_sms_enabled';

	/** @var array<string,GatewayInterface> */
	private $gateways = [];

	/**
	 * ثبت هوک‌ها.
	 */
	public function register() {
		$this->gateways = [
			Kavenegar::ID   => new Kavenegar(),
			Melipayamak::ID => new Melipayamak(),
		];

		// رویدادهای ووکامرس → پیامک.
		add_action( 'woocommerce_thankyou', [ $this, 'on_order_placed' ], 20, 1 );
		add_action( 'woocommerce_order_status_changed', [ $this, 'on_order_status_changed' ], 20, 4 );

		// رویدادهای هسته.
		add_action( 'zch/role/approved', [ $this, 'on_role_approved' ], 20, 2 );
		add_action( 'zch/role/rejected', [ $this, 'on_role_rejected' ], 20, 3 );

		// زمان‌بندی پاک‌سازی لاگ قدیمی.
		add_action( 'zch/sms/daily_prune', [ $this, 'daily_prune' ] );

		if ( ! wp_next_scheduled( 'zch/sms/daily_prune' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'zch/sms/daily_prune' );
		}
	}

	/**
	 * فهرست درگاه‌های ثبت‌شده.
	 *
	 * @return array<string,GatewayInterface>
	 */
	public function gateways() {
		/**
		 * فیلتر درگاه‌های پیامک — برای افزودن پنل سوم بدون دست‌زدن به هسته.
		 *
		 * @param array<string,GatewayInterface> $gateways
		 */
		return (array) apply_filters( 'zch/sms/gateways', $this->gateways );
	}

	/**
	 * درگاه فعال فعلی.
	 *
	 * @return GatewayInterface|null
	 */
	public function gateway() {
		$active = (string) get_option( self::OPTION_GATEWAY, Kavenegar::ID );

		/**
		 * فیلتر درگاه فعال.
		 *
		 * @param string $active
		 */
		$active   = (string) apply_filters( 'zch/sms/gateway', $active );
		$gateways = $this->gateways();

		return isset( $gateways[ $active ] ) ? $gateways[ $active ] : null;
	}

	/**
	 * آیا سرویس پیامک فعال است؟
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return (bool) apply_filters( 'zch/sms/enabled', (bool) get_option( self::OPTION_ENABLED, true ) );
	}

	/**
	 * ارسال یک پیامک با استفاده از یک الگو.
	 *
	 * @param string              $to         شمارهٔ موبایل.
	 * @param string              $template_key کلید الگو.
	 * @param array<string,mixed> $vars       متغیرهای جای‌گذاری.
	 * @param array<string,mixed> $args       گزینه‌های درگاه.
	 * @return SmsResult
	 */
	public function send_template( $to, $template_key, array $vars = [], array $args = [] ) {
		if ( ! Templates::is_enabled( $template_key ) ) {
			return SmsResult::failure( __( 'این الگوی پیامک غیرفعال است.', 'zarincode-harir-core' ), 'template_disabled' );
		}

		$body = Templates::render( $template_key, $vars );

		if ( '' === trim( $body ) ) {
			return SmsResult::failure( __( 'متن الگو خالی است.', 'zarincode-harir-core' ), 'empty_template' );
		}

		$args['template_key'] = $template_key;

		if ( Templates::is_otp( $template_key ) && isset( $vars['code'] ) ) {
			return $this->send_otp( $to, (string) $vars['code'], array_merge( $args, [ 'fallback_text' => $body ] ) );
		}

		return $this->send( $to, $body, $args );
	}

	/**
	 * ارسال پیامک متنی ساده.
	 *
	 * @param string|array<string> $to
	 * @param string               $message
	 * @param array<string,mixed>  $args
	 * @return SmsResult
	 */
	public function send( $to, $message, array $args = [] ) {
		return $this->dispatch( $to, $message, $args, false );
	}

	/**
	 * ارسال کد یک‌بارمصرف.
	 *
	 * @param string              $to
	 * @param string              $code
	 * @param array<string,mixed> $args
	 * @return SmsResult
	 */
	public function send_otp( $to, $code, array $args = [] ) {
		return $this->dispatch( $to, $code, $args, true );
	}

	/**
	 * اجرای ارسال با همهٔ بررسی‌های امنیتی.
	 *
	 * @param string|array<string> $to
	 * @param string               $payload
	 * @param array<string,mixed>  $args
	 * @param bool                 $is_otp
	 * @return SmsResult
	 */
	private function dispatch( $to, $payload, array $args, $is_otp ) {
		if ( ! $this->is_enabled() ) {
			return SmsResult::failure( __( 'سرویس پیامک در تنظیمات غیرفعال است.', 'zarincode-harir-core' ), 'disabled' );
		}

		$gateway = $this->gateway();

		if ( ! $gateway instanceof GatewayInterface ) {
			return SmsResult::failure( __( 'هیچ درگاه پیامکی انتخاب نشده است.', 'zarincode-harir-core' ), 'no_gateway' );
		}

		$mobile = Mobile::normalize( is_array( $to ) ? reset( $to ) : $to );

		if ( '' === $mobile ) {
			return SmsResult::failure( __( 'شمارهٔ موبایل معتبر نیست.', 'zarincode-harir-core' ), 'invalid_mobile' );
		}

		// محدودسازی نرخ — فقط برای ارسال‌های تعاملی.
		$rate_error = $this->check_rate_limit( $mobile );

		if ( is_wp_error( $rate_error ) ) {
			return SmsResult::failure( $rate_error->get_error_message(), 'rate_limited' );
		}

		$template_key = isset( $args['template_key'] ) ? (string) $args['template_key'] : '';

		/**
		 * پیش از ارسال پیامک.
		 *
		 * @param bool        $send     با false برگرداندن، ارسال لغو می‌شود.
		 * @param string      $mobile
		 * @param string      $payload
		 * @param bool        $is_otp
		 * @param array       $args
		 */
		if ( ! apply_filters( 'zch/sms/should_send', true, $mobile, $payload, $is_otp, $args ) ) {
			return SmsResult::failure( __( 'ارسال توسط فیلتر لغو شد.', 'zarincode-harir-core' ), 'filtered' );
		}

		$result = $is_otp ? $gateway->send_otp( $mobile, $payload, $args ) : $gateway->send( $to, $payload, $args );

		if ( ! (bool) get_option( 'zch_sms_disable_log', false ) ) {
			Log::add(
				[
					'gateway'      => $gateway->id(),
					'template_key' => $template_key,
					'receptor'     => $mobile,
					'sender'       => isset( $args['sender'] ) ? $args['sender'] : '',
					'message'      => $is_otp ? '[OTP]' : $payload,
					'message_id'   => $result->message_id,
					'status'       => $result->ok ? 'sent' : 'failed',
					'status_code'  => $result->code,
					'cost'         => $result->cost,
					'user_id'      => isset( $args['user_id'] ) ? (int) $args['user_id'] : 0,
					'error'        => $result->ok ? null : $result->error,
				]
			);
		}

		/**
		 * پس از تلاش برای ارسال پیامک.
		 *
		 * @param SmsResult $result
		 * @param string    $mobile
		 * @param string    $template_key
		 */
		do_action( 'zch/sms/sent', $result, $mobile, $template_key );

		return $result;
	}

	/**
	 * محدودسازی نرخ ارسال برای جلوگیری از هزینهٔ ناخواسته و اسپم.
	 *
	 * @param string $mobile
	 * @return true|\WP_Error
	 */
	private function check_rate_limit( $mobile ) {
		$limits = [
			'per_2min' => (int) get_option( 'zch_sms_limit_per_2min', 1 ),
			'per_hour' => (int) get_option( 'zch_sms_limit_per_hour', 5 ),
			'per_day'  => (int) get_option( 'zch_sms_limit_per_day', 15 ),
		];

		/**
		 * فیلتر سقف‌های محدودسازی نرخ.
		 *
		 * @param array  $limits
		 * @param string $mobile
		 */
		$limits = apply_filters( 'zch/sms/rate_limits', $limits, $mobile );

		$checks = [
			'per_2min' => [ 2 * MINUTE_IN_SECONDS, __( 'لطفاً کمی صبر کنید و دوباره درخواست کد بدهید.', 'zarincode-harir-core' ) ],
			'per_hour' => [ HOUR_IN_SECONDS, __( 'سقف درخواست پیامک در این ساعت پر شده است.', 'zarincode-harir-core' ) ],
			'per_day'  => [ DAY_IN_SECONDS, __( 'سقف درخواست پیامک امروز پر شده است.', 'zarincode-harir-core' ) ],
		];

		foreach ( $checks as $key => $check ) {
			$limit = isset( $limits[ $key ] ) ? (int) $limits[ $key ] : 0;

			if ( $limit <= 0 ) {
				continue;
			}

			$count = Log::count_for_mobile( $mobile, time() - $check[0] );

			if ( $count >= $limit ) {
				return new \WP_Error( 'zch_sms_rate', $check[1] );
			}
		}

		return true;
	}

	/* ------------------------------------------------------------------
	 * رویدادها
	 * ---------------------------------------------------------------- */

	/**
	 * @param int $order_id
	 */
	public function on_order_placed( $order_id ) {
		$order = wc_get_order( (int) $order_id );

		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		// جلوگیری از ارسال تکراری.
		if ( $order->get_meta( '_zch_sms_order_placed' ) ) {
			return;
		}

		$this->send_order_template( $order, 'order_received' );
		$order->update_meta_data( '_zch_sms_order_placed', time() );
		$order->save();
	}

	/**
	 * @param int       $order_id
	 * @param string    $from
	 * @param string    $to
	 * @param mixed     $order
	 */
	public function on_order_status_changed( $order_id, $from, $to, $order = null ) {
		$order = $order instanceof \WC_Order ? $order : wc_get_order( (int) $order_id );

		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$map = [
			'processing' => 'order_paid',
			'completed'  => 'order_completed',
			'cancelled'  => 'order_cancelled',
		];

		$to_key = str_replace( 'wc-', '', (string) $to );

		if ( ! isset( $map[ $to_key ] ) ) {
			return;
		}

		$meta_key = '_zch_sms_' . $to_key;

		if ( $order->get_meta( $meta_key ) ) {
			return;
		}

		$this->send_order_template( $order, $map[ $to_key ] );
		$order->update_meta_data( $meta_key, time() );
		$order->save();
	}

	/**
	 * @param \WC_Order $order
	 * @param string    $template_key
	 */
	private function send_order_template( $order, $template_key ) {
		$mobile = Mobile::normalize( $order->get_billing_phone() );

		if ( '' === $mobile ) {
			return;
		}

		$name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );

		$this->send_template(
			$mobile,
			$template_key,
			[
				'name'         => '' !== $name ? $name : __( 'مشتری گرامی', 'zarincode-harir-core' ),
				'order_number' => $order->get_order_number(),
				'total'        => wp_strip_all_tags( wc_price( $order->get_total() ) ),
				'tracking'     => (string) $order->get_meta( '_zch_tracking_code' ),
			],
			[ 'user_id' => $order->get_user_id() ]
		);
	}

	/**
	 * @param int    $user_id
	 * @param string $new_role
	 */
	public function on_role_approved( $user_id, $new_role ) {
		$user = get_user_by( 'id', (int) $user_id );

		if ( ! $user instanceof \WP_User ) {
			return;
		}

		$mobile = Mobile::normalize( get_user_meta( $user->ID, '_zch_mobile', true ) );

		if ( '' === $mobile ) {
			return;
		}

		$this->send_template(
			$mobile,
			'wholesale_approved',
			[ 'name' => $user->display_name ],
			[ 'user_id' => $user->ID ]
		);
	}

	/**
	 * @param int    $user_id
	 * @param string $reason
	 * @param string $old_role
	 */
	public function on_role_rejected( $user_id, $reason = '', $old_role = '' ) {
		$user = get_user_by( 'id', (int) $user_id );

		if ( ! $user instanceof \WP_User ) {
			return;
		}

		$mobile = Mobile::normalize( get_user_meta( $user->ID, '_zch_mobile', true ) );

		if ( '' === $mobile ) {
			return;
		}

		$template = \Zarincode\Harir\Roles\Roles::WHOLESALE === $old_role ? 'role_downgraded' : 'wholesale_rejected';

		$this->send_template(
			$mobile,
			$template,
			[
				'name'   => $user->display_name,
				'reason' => '' !== $reason ? $reason : __( 'تکمیل اطلاعات', 'zarincode-harir-core' ),
			],
			[ 'user_id' => $user->ID ]
		);
	}

	/**
	 * پاک‌سازی روزانهٔ لاگ قدیمی.
	 */
	public function daily_prune() {
		$days = (int) get_option( 'zch_sms_log_keep_days', 90 );

		if ( $days > 0 ) {
			Log::prune( $days );
		}
	}
}
