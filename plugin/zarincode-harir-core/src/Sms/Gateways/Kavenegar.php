<?php
/**
 * درگاه پیامک کاوه‌نگار.
 *
 * REST با API-Key در مسیر:
 *   ارسال ساده : https://api.kavenegar.com/v1/{API-KEY}/sms/send.json
 *   ارسال OTP  : https://api.kavenegar.com/v1/{API-KEY}/verify/lookup.json
 *   وضعیت      : https://api.kavenegar.com/v1/{API-KEY}/sms/status.json
 *   اعتبار     : https://api.kavenegar.com/v1/{API-KEY}/account/info.json
 *
 * @package Zarincode\Harir\Sms\Gateways
 */

namespace Zarincode\Harir\Sms\Gateways;

defined( 'ABSPATH' ) || exit;

use Zarincode\Harir\Sms\Contracts\GatewayInterface;
use Zarincode\Harir\Sms\SmsResult;
use Zarincode\Harir\Helpers\Mobile;

/**
 * پیاده‌سازی کاوه‌نگار.
 */
final class Kavenegar implements GatewayInterface {

	const ID = 'kavenegar';

	/** @var string */
	private $base = 'https://api.kavenegar.com/v1/';

	/**
	 * @return string
	 */
	public function id() {
		return self::ID;
	}

	/**
	 * @return string
	 */
	public function label() {
		return __( 'کاوه‌نگار', 'zarincode-harir-core' );
	}

	/**
	 * @return bool
	 */
	public function supports_pattern() {
		return true;
	}

	/**
	 * کلید API از تنظیمات.
	 *
	 * @return string
	 */
	private function api_key() {
		return (string) get_option( 'zch_sms_kavenegar_api_key', '' );
	}

	/**
	 * شمارهٔ فرستندهٔ پیش‌فرض.
	 *
	 * @return string
	 */
	private function sender() {
		return (string) get_option( 'zch_sms_kavenegar_sender', '' );
	}

	/**
	 * نام الگوی ثبت‌شده برای OTP در پنل کاوه‌نگار.
	 *
	 * @return string
	 */
	private function otp_template() {
		return (string) get_option( 'zch_sms_kavenegar_otp_template', '' );
	}

	/**
	 * @param string|array<string> $to
	 * @param string               $message
	 * @param array<string,mixed>  $args
	 * @return SmsResult
	 */
	public function send( $to, $message, array $args = [] ) {
		$receptors = $this->prepare_receptors( $to );

		if ( empty( $receptors ) ) {
			return SmsResult::failure( __( 'شمارهٔ گیرنده معتبر نیست.', 'zarincode-harir-core' ) );
		}

		$sender = ! empty( $args['sender'] ) ? $args['sender'] : $this->sender();

		if ( '' === $sender ) {
			return SmsResult::failure( __( 'شمارهٔ فرستنده در تنظیمات کاوه‌نگار وارد نشده است.', 'zarincode-harir-core' ) );
		}

		$params = [
			'receptor' => implode( ',', $receptors ),
			'sender'   => $sender,
			'message'  => $message,
		];

		if ( ! empty( $args['is_flash'] ) ) {
			$params['type'] = 'flash';
		}

		return $this->request( 'sms/send.json', $params );
	}

	/**
	 * ارسال OTP از طریق سرویس Verify/Lookup.
	 *
	 * اگر الگوی ثبت‌شده تعریف نشده باشد، به‌صورت خودکار به ارسال متنی
	 * معمولی برمی‌گردیم تا سایت هرگز بی‌کد نماند.
	 *
	 * @param string              $to
	 * @param string              $code
	 * @param array<string,mixed> $args
	 * @return SmsResult
	 */
	public function send_otp( $to, $code, array $args = [] ) {
		$template = ! empty( $args['template'] ) ? $args['template'] : $this->otp_template();
		$mobile   = Mobile::normalize( $to );

		if ( '' === $mobile ) {
			return SmsResult::failure( __( 'شمارهٔ موبایل معتبر نیست.', 'zarincode-harir-core' ) );
		}

		if ( '' === $template ) {
			$body = ! empty( $args['fallback_text'] )
				? $args['fallback_text']
				: sprintf(
					/* translators: %s: one-time code */
					__( 'کد تأیید حریر رویا: %s', 'zarincode-harir-core' ),
					$code
				);

			return $this->send( $mobile, $body, $args );
		}

		$params = [
			'receptor' => $mobile,
			'template' => $template,
			'token'    => $code,
		];

		if ( ! empty( $args['token2'] ) ) {
			$params['token2'] = $args['token2'];
		}

		if ( ! empty( $args['token10'] ) ) {
			$params['token10'] = $args['token10'];
		}

		return $this->request( 'verify/lookup.json', $params );
	}

	/**
	 * @param string $message_id
	 * @return array{status:string,label:string,code:string}
	 */
	public function status( $message_id ) {
		$result = $this->request( 'sms/status.json', [ 'messageid' => $message_id ] );

		if ( ! $result->ok || empty( $result->raw['entries'][0] ) ) {
			return [
				'status' => 'unknown',
				'label'  => __( 'نامشخص', 'zarincode-harir-core' ),
				'code'   => $result->code,
			];
		}

		$entry = $result->raw['entries'][0];
		$code  = isset( $entry['status'] ) ? (int) $entry['status'] : 0;

		return [
			'status' => $this->map_status( $code ),
			'label'  => $this->status_label( $code ),
			'code'   => (string) $code,
		];
	}

	/**
	 * @return float|null
	 */
	public function credit() {
		$result = $this->request( 'account/info.json', [] );

		if ( ! $result->ok || ! isset( $result->raw['entries']['accountinfo']['credit'] ) ) {
			return null;
		}

		return (float) $result->raw['entries']['accountinfo']['credit'];
	}

	/**
	 * @return array{ok:bool,message:string}
	 */
	public function health_check() {
		if ( '' === $this->api_key() ) {
			return [
				'ok'      => false,
				'message' => __( 'کلید API کاوه‌نگار وارد نشده است.', 'zarincode-harir-core' ),
			];
		}

		$credit = $this->credit();

		if ( null === $credit ) {
			return [
				'ok'      => false,
				'message' => __( 'اتصال به کاوه‌نگار برقرار نشد. کلید API را بررسی کنید.', 'zarincode-harir-core' ),
			];
		}

		return [
			'ok'      => true,
			/* translators: %s: account credit */
			'message' => sprintf( __( 'اتصال برقرار است. اعتبار پنل: %s ریال.', 'zarincode-harir-core' ), number_format_i18n( $credit ) ),
		];
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function settings_fields() {
		return [
			[
				'id'    => 'zch_sms_kavenegar_api_key',
				'label' => __( 'کلید API', 'zarincode-harir-core' ),
				'type'  => 'password',
				'hint'  => __( 'از پنل کاوه‌نگار → بخش API کپی کنید.', 'zarincode-harir-core' ),
			],
			[
				'id'    => 'zch_sms_kavenegar_sender',
				'label' => __( 'شمارهٔ فرستنده', 'zarincode-harir-core' ),
				'type'  => 'text',
				'hint'  => __( 'خط اختصاصی یا شمارهٔ خدماتی.', 'zarincode-harir-core' ),
			],
			[
				'id'    => 'zch_sms_kavenegar_otp_template',
				'label' => __( 'الگوی OTP (Verify/Lookup)', 'zarincode-harir-core' ),
				'type'  => 'text',
				'hint'  => __( 'نام الگوی ثبت‌شده در پنل. خالی بگذارید تا کد به‌صورت پیامک متنی معمولی ارسال شود.', 'zarincode-harir-core' ),
			],
		];
	}

	/* ------------------------------------------------------------------ */

	/**
	 * اجرای یک درخواست REST.
	 *
	 * @param string               $endpoint
	 * @param array<string,mixed>  $params
	 * @return SmsResult
	 */
	private function request( $endpoint, array $params ) {
		$api_key = $this->api_key();

		if ( '' === $api_key ) {
			return SmsResult::failure( __( 'کلید API کاوه‌نگار تنظیم نشده است.', 'zarincode-harir-core' ), 'no_api_key' );
		}

		$url = $this->base . rawurlencode( $api_key ) . '/' . ltrim( $endpoint, '/' );

		/**
		 * فیلتر آدرس پایهٔ کاوه‌نگار (برای سرورهای میانی یا پروکسی).
		 *
		 * @param string $url
		 * @param string $endpoint
		 */
		$url = apply_filters( 'zch/sms/kavenegar/url', $url, $endpoint );

		$response = wp_remote_post(
			$url,
			[
				'timeout' => 15,
				'body'    => $params,
				'headers' => [ 'Accept' => 'application/json' ],
			]
		);

		if ( is_wp_error( $response ) ) {
			return SmsResult::failure( $response->get_error_message(), 'http_error' );
		}

		$http_code = (int) wp_remote_retrieve_response_code( $response );
		$body      = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) ) {
			return SmsResult::failure( __( 'پاسخ نامعتبر از کاوه‌نگار.', 'zarincode-harir-core' ), (string) $http_code );
		}

		$status = isset( $body['return']['status'] ) ? (int) $body['return']['status'] : 0;
		$msg    = isset( $body['return']['message'] ) ? (string) $body['return']['message'] : '';

		if ( 200 !== $status ) {
			return SmsResult::failure( $msg ? $msg : __( 'ارسال از طریق کاوه‌نگار ناموفق بود.', 'zarincode-harir-core' ), (string) $status, $body );
		}

		$result          = SmsResult::success( $this->extract_message_id( $body ), $body );
		$result->code    = (string) $status;
		$result->raw['entries'] = isset( $body['entries'] ) ? $body['entries'] : [];

		return $result;
	}

	/**
	 * @param array<string,mixed> $body
	 * @return string
	 */
	private function extract_message_id( array $body ) {
		if ( isset( $body['entries'][0]['messageid'] ) ) {
			return (string) $body['entries'][0]['messageid'];
		}

		if ( isset( $body['entries']['messageid'] ) ) {
			return (string) $body['entries']['messageid'];
		}

		return '';
	}

	/**
	 * @param string|array<string> $to
	 * @return string[]
	 */
	private function prepare_receptors( $to ) {
		$list = is_array( $to ) ? $to : [ $to ];
		$out  = [];

		foreach ( $list as $item ) {
			$normalized = Mobile::normalize( $item );

			if ( '' !== $normalized ) {
				$out[] = $normalized;
			}
		}

		return array_values( array_unique( $out ) );
	}

	/**
	 * @param int $code
	 * @return string
	 */
	private function map_status( $code ) {
		if ( 1 === $code ) {
			return 'queued';
		}
		if ( in_array( $code, [ 4, 5, 6, 11 ], true ) ) {
			return 'sent';
		}
		if ( 10 === $code ) {
			return 'delivered';
		}
		if ( in_array( $code, [ 2, 3, 7, 8, 9, 12, 13, 14 ], true ) ) {
			return 'failed';
		}

		return 'unknown';
	}

	/**
	 * @param int $code
	 * @return string
	 */
	private function status_label( $code ) {
		$labels = [
			1  => __( 'در صف ارسال', 'zarincode-harir-core' ),
			2  => __( 'ارسال شد', 'zarincode-harir-core' ),
			3  => __( 'تحویل شد', 'zarincode-harir-core' ),
			4  => __( 'تحویل داده نشد', 'zarincode-harir-core' ),
			5  => __( 'رد شده', 'zarincode-harir-core' ),
			6  => __( 'لغو شده', 'zarincode-harir-core' ),
			10 => __( 'تحویل قطعی', 'zarincode-harir-core' ),
			11 => __( 'در انتظار تحویل', 'zarincode-harir-core' ),
		];

		return isset( $labels[ $code ] ) ? $labels[ $code ] : __( 'نامشخص', 'zarincode-harir-core' );
	}
}
