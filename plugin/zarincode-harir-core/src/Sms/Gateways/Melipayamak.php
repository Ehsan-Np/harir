<?php
/**
 * درگاه پیامک ملی پیامک.
 *
 * سه مسیر پشتیبانی می‌شود:
 *  ۱. REST با Basic Auth (username/password) → recommended
 *       ارسال     : https://rest.payamak-panel.com/api/SendSMS/SendSMS
 *       OTP        : https://rest.payamak-panel.com/api/SendSMS/SendOtp
 *       اعتبار    : https://rest.payamak-panel.com/api/SendSMS/GetCredit
 *  ۲. کنسول با API Key (بدون username/password)
 *       ارسال     : https://console.melipayamak.com/api/send
 *  ۳. SOAP (فقط به‌عنوان fallback، با SoapClient در صورت نصب بودن)
 *
 * @package Zarincode\Harir\Sms\Gateways
 */

namespace Zarincode\Harir\Sms\Gateways;

defined( 'ABSPATH' ) || exit;

use Zarincode\Harir\Sms\Contracts\GatewayInterface;
use Zarincode\Harir\Sms\SmsResult;
use Zarincode\Harir\Helpers\Mobile;

/**
 * پیاده‌سازی ملی پیامک.
 */
final class Melipayamak implements GatewayInterface {

	const ID = 'melipayamak';

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
		return __( 'ملی پیامک', 'zarincode-harir-core' );
	}

	/**
	 * @return bool
	 */
	public function supports_pattern() {
		return 'pattern' === $this->method();
	}

	/**
	 * روش احراز هویت: `credentials` یا `apikey`.
	 *
	 * @return string
	 */
	private function auth_mode() {
		$mode = (string) get_option( 'zch_sms_melipayamak_auth', 'credentials' );

		return in_array( $mode, [ 'credentials', 'apikey' ], true ) ? $mode : 'credentials';
	}

	/**
	 * نوع ارسال: `rest` یا `soap`.
	 *
	 * @return string
	 */
	private function method() {
		$method = (string) get_option( 'zch_sms_melipayamak_method', 'rest' );

		return in_array( $method, [ 'rest', 'soap', 'pattern' ], true ) ? $method : 'rest';
	}

	/**
	 * @return string
	 */
	private function username() {
		return (string) get_option( 'zch_sms_melipayamak_username', '' );
	}

	/**
	 * @return string
	 */
	private function password() {
		return (string) get_option( 'zch_sms_melipayamak_password', '' );
	}

	/**
	 * @return string
	 */
	private function api_key() {
		return (string) get_option( 'zch_sms_melipayamak_api_key', '' );
	}

	/**
	 * @return string
	 */
	private function sender() {
		return (string) get_option( 'zch_sms_melipayamak_sender', '' );
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
			return SmsResult::failure( __( 'شمارهٔ فرستنده در تنظیمات ملی پیامک وارد نشده است.', 'zarincode-harir-core' ) );
		}

		$is_flash = ! empty( $args['is_flash'] ) ? 'true' : 'false';

		if ( 'soap' === $this->method() && class_exists( '\SoapClient' ) ) {
			return $this->send_soap( $receptors, $message, $sender, $is_flash );
		}

		if ( 'apikey' === $this->auth_mode() ) {
			return $this->send_console( $receptors, $message, $sender );
		}

		return $this->request(
			'https://rest.payamak-panel.com/api/SendSMS/SendSMS',
			[
				'from'   => $sender,
				'to'     => $receptors,
				'text'   => $message,
				'isFlash' => $is_flash,
			],
			'json'
		);
	}

	/**
	 * ارسال کد یک‌بارمصرف.
	 *
	 * مسیر REST اختصاصی `SendOtp` از متن ثابت پنل استفاده می‌کند
	 * («کد تأیید شما: 123456») و از فیلتر تبلیغاتی عبور می‌کند.
	 *
	 * @param string              $to
	 * @param string              $code
	 * @param array<string,mixed> $args
	 * @return SmsResult
	 */
	public function send_otp( $to, $code, array $args = [] ) {
		$mobile = Mobile::normalize( $to );

		if ( '' === $mobile ) {
			return SmsResult::failure( __( 'شمارهٔ موبایل معتبر نیست.', 'zarincode-harir-core' ) );
		}

		$sender = ! empty( $args['sender'] ) ? $args['sender'] : $this->sender();

		if ( '' === $sender ) {
			return SmsResult::failure( __( 'شمارهٔ فرستنده در تنظیمات ملی پیامک وارد نشده است.', 'zarincode-harir-core' ) );
		}

		// حالت الگوی ثبت‌شده (pattern) برای عبور از بلک‌لیست تبلیغاتی.
		if ( 'pattern' === $this->method() ) {
			$pattern_code = ! empty( $args['pattern_code'] ) ? $args['pattern_code'] : (string) get_option( 'zch_sms_melipayamak_pattern_code', '' );

			if ( '' !== $pattern_code ) {
				return $this->request(
					'https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber',
					[
						'PatternCode' => $pattern_code,
						'PatternValues' => [ [ 'PatternValue' => $code ] ],
						'Mobile'      => $mobile,
					],
					'json'
				);
			}
		}

		// مسیر استاندارد SendOtp.
		return $this->request(
			'https://rest.payamak-panel.com/api/SendSMS/SendOtp',
			[
				'username' => $this->username(),
				'password' => $this->password(),
				'from'     => $sender,
				'to'       => $mobile,
				'code'     => (int) $code,
			],
			'form'
		);
	}

	/**
	 * @param string $message_id
	 * @return array{status:string,label:string,code:string}
	 */
	public function status( $message_id ) {
		/**
		 * آدرس بررسی وضعیت — چون در پنل‌های مختلف ملی پیامک متفاوت است،
		 * قابل فیلتر گذاشته شده تا مدیر بتواند آن را دقیق تنظیم کند.
		 *
		 * @param string $url
		 * @param string $message_id
		 */
		$url = (string) apply_filters(
			'zch/sms/melipayamak/status_url',
			'https://rest.payamak-panel.com/api/SendSMS/GetMessageStatus',
			$message_id
		);

		if ( '' === $url ) {
			return [
				'status' => 'unknown',
				'label'  => __( 'نامشخص', 'zarincode-harir-core' ),
				'code'   => '',
			];
		}

		$result = $this->request( $url, [ 'recId' => $message_id ], 'json' );

		if ( ! $result->ok ) {
			return [
				'status' => 'unknown',
				'label'  => __( 'نامشخص', 'zarincode-harir-core' ),
				'code'   => $result->code,
			];
		}

		$value = isset( $result->raw['Value'] ) ? (int) $result->raw['Value'] : 0;

		return [
			'status' => $this->map_status( $value ),
			'label'  => $this->status_label( $value ),
			'code'   => (string) $value,
		];
	}

	/**
	 * @return float|null
	 */
	public function credit() {
		if ( 'apikey' === $this->auth_mode() ) {
			return null;
		}

		$result = $this->request( 'https://rest.payamak-panel.com/api/SendSMS/GetCredit', [], 'json' );

		if ( ! $result->ok || ! isset( $result->raw['Value'] ) ) {
			return null;
		}

		return (float) $result->raw['Value'];
	}

	/**
	 * @return array{ok:bool,message:string}
	 */
	public function health_check() {
		if ( 'apikey' === $this->auth_mode() && '' === $this->api_key() ) {
			return [
				'ok'      => false,
				'message' => __( 'کلید API ملی پیامک وارد نشده است.', 'zarincode-harir-core' ),
			];
		}

		if ( 'credentials' === $this->auth_mode() && ( '' === $this->username() || '' === $this->password() ) ) {
			return [
				'ok'      => false,
				'message' => __( 'نام کاربری یا رمز عبور ملی پیامک وارد نشده است.', 'zarincode-harir-core' ),
			];
		}

		$credit = $this->credit();

		if ( null !== $credit ) {
			return [
				'ok'      => true,
				/* translators: %s: account credit */
				'message' => sprintf( __( 'اتصال برقرار است. اعتبار پنل: %s ریال.', 'zarincode-harir-core' ), number_format_i18n( $credit ) ),
			];
		}

		if ( 'apikey' === $this->auth_mode() ) {
			return [
				'ok'      => true,
				'message' => __( 'حالت کنسول (API Key) فعال است؛ اعتبار از این مسیر قابل خواندن نیست.', 'zarincode-harir-core' ),
			];
		}

		return [
			'ok'      => false,
			'message' => __( 'اتصال به ملی پیامک برقرار نشد. نام کاربری و رمز عبور را بررسی کنید.', 'zarincode-harir-core' ),
		];
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function settings_fields() {
		return [
			[
				'id'      => 'zch_sms_melipayamak_auth',
				'label'   => __( 'روش احراز هویت', 'zarincode-harir-core' ),
				'type'    => 'select',
				'options' => [
					'credentials' => __( 'نام کاربری و رمز عبور', 'zarincode-harir-core' ),
					'apikey'      => __( 'کلید API (کنسول)', 'zarincode-harir-core' ),
				],
			],
			[
				'id'   => 'zch_sms_melipayamak_username',
				'label' => __( 'نام کاربری', 'zarincode-harir-core' ),
				'type' => 'text',
			],
			[
				'id'   => 'zch_sms_melipayamak_password',
				'label' => __( 'رمز عبور', 'zarincode-harir-core' ),
				'type' => 'password',
			],
			[
				'id'   => 'zch_sms_melipayamak_api_key',
				'label' => __( 'کلید API کنسول', 'zarincode-harir-core' ),
				'type' => 'password',
				'hint' => __( 'فقط در حالت کنسول لازم است.', 'zarincode-harir-core' ),
			],
			[
				'id'   => 'zch_sms_melipayamak_sender',
				'label' => __( 'شمارهٔ فرستنده', 'zarincode-harir-core' ),
				'type' => 'text',
			],
			[
				'id'      => 'zch_sms_melipayamak_method',
				'label'   => __( 'روش ارسال', 'zarincode-harir-core' ),
				'type'    => 'select',
				'options' => [
					'rest'    => __( 'REST (پیشنهادی)', 'zarincode-harir-core' ),
					'pattern' => __( 'الگوی ثبت‌شده (عبور از بلک‌لیست)', 'zarincode-harir-core' ),
					'soap'    => __( 'SOAP', 'zarincode-harir-core' ),
				],
			],
			[
				'id'   => 'zch_sms_melipayamak_pattern_code',
				'label' => __( 'کد الگو (Pattern)', 'zarincode-harir-core' ),
				'type' => 'text',
				'hint' => __( 'فقط در روش «الگوی ثبت‌شده» لازم است.', 'zarincode-harir-core' ),
			],
		];
	}

	/* ------------------------------------------------------------------ */

	/**
	 * اجرای یک درخواست.
	 *
	 * @param string               $url
	 * @param array<string,mixed>  $body
	 * @param string               $format 'json' | 'form'
	 * @return SmsResult
	 */
	private function request( $url, array $body, $format = 'json' ) {
		$headers = [ 'Accept' => 'application/json' ];

		if ( 'apikey' === $this->auth_mode() && '' !== $this->api_key() ) {
			$headers['Authorization'] = 'Bearer ' . $this->api_key();
			$headers['api-key']       = $this->api_key();
		} elseif ( '' !== $this->username() ) {
			$headers['Authorization'] = 'Basic ' . base64_encode( $this->username() . ':' . $this->password() );
		}

		$args = [
			'timeout' => 15,
			'method'  => 'POST',
			'headers' => $headers,
		];

		if ( 'form' === $format ) {
			$headers['Content-Type'] = 'application/x-www-form-urlencoded';
			$args['headers']         = $headers;
			$args['body']            = http_build_query( $body );
		} else {
			$headers['Content-Type'] = 'application/json';
			$args['headers']         = $headers;
			$args['body']            = wp_json_encode( $body );
		}

		/**
		 * فیلتر آرگومان‌های درخواست ملی پیامک.
		 *
		 * @param array  $args
		 * @param string $url
		 */
		$args = apply_filters( 'zch/sms/melipayamak/request_args', $args, $url );

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return SmsResult::failure( $response->get_error_message(), 'http_error' );
		}

		$http_code = (int) wp_remote_retrieve_response_code( $response );
		$raw_body  = wp_remote_retrieve_body( $response );
		$decoded   = json_decode( $raw_body, true );

		if ( ! is_array( $decoded ) ) {
			// پاسخ SOAP/XML با SimpleXML پارس می‌شود.
			$decoded = $this->parse_xml( $raw_body );
		}

		if ( empty( $decoded ) ) {
			return SmsResult::failure( __( 'پاسخ نامعتبر از ملی پیامک.', 'zarincode-harir-core' ), (string) $http_code );
		}

		$is_successful = isset( $decoded['IsSuccessful'] ) ? (bool) $decoded['IsSuccessful'] : null;
		$str_result    = isset( $decoded['StrRetStatus'] ) ? (string) $decoded['StrRetStatus'] : '';

		if ( false === $is_successful ) {
			return SmsResult::failure(
				$str_result ? $str_result : __( 'ارسال از طریق ملی پیامک ناموفق بود.', 'zarincode-harir-core' ),
				isset( $decoded['StrRetStatus'] ) ? (string) $decoded['StrRetStatus'] : (string) $http_code,
				$decoded
			);
		}

		// پاسخ عددی ساده (مثل SendSimpleSMS که RecId برمی‌گرداند).
		if ( isset( $decoded['Value'] ) && ! is_array( $decoded['Value'] ) ) {
			$value = (string) $decoded['Value'];

			// در وب‌سرویس قدیمی، مقدار ≤ ۱۰۰ یعنی کد خطا.
			if ( is_numeric( $value ) && (float) $value <= 100 && 'GetCredit' === substr( $url, -9 ) ) {
				return SmsResult::failure( __( 'پنل ملی پیامک خطا برگرداند.', 'zarincode-harir-core' ), $value, $decoded );
			}

			$result       = SmsResult::success( $value, $decoded );
			$result->code = $value;

			return $result;
		}

		$result       = SmsResult::success( isset( $decoded['StrRetString'] ) ? (string) $decoded['StrRetString'] : '', $decoded );
		$result->code = $str_result;

		return $result;
	}

	/**
	 * ارسال از طریق SOAP (fallback).
	 *
	 * @param string[] $receptors
	 * @param string   $message
	 * @param string   $sender
	 * @param string   $is_flash
	 * @return SmsResult
	 */
	private function send_soap( array $receptors, $message, $sender, $is_flash ) {
		if ( ! class_exists( '\SoapClient' ) ) {
			return SmsResult::failure( __( 'افزونهٔ SoapClient روی سرور فعال نیست. روش ارسال را به REST تغییر دهید.', 'zarincode-harir-core' ), 'no_soap' );
		}

		try {
			/**
			 * فیلتر آدرس WSDL.
			 *
			 * @param string $wsdl
			 */
			$wsdl = (string) apply_filters( 'zch/sms/melipayamak/wsdl', 'http://api.payamak-panel.com/post/send.asmx?wsdl' );

			$client = new \SoapClient(
				$wsdl,
				[
					'encoding'            => 'UTF-8',
					'connection_timeout'  => 15,
					'exceptions'          => true,
					'trace'               => false,
				]
			);

			$response = $client->SendSimpleSMS( // phpcs:ignore WordPress.NamingConventions
				[
					'username' => $this->username(),
					'password' => $this->password(),
					'from'     => $sender,
					'to'       => $receptors,
					'text'     => $message,
					'isflash'  => $is_flash,
				]
			);

			$value = isset( $response->SendSimpleSMSResult ) ? (string) $response->SendSimpleSMSResult : '';

			if ( '' === $value || ( is_numeric( $value ) && (float) $value <= 100 ) ) {
				return SmsResult::failure( __( 'ارسال SOAP ناموفق بود.', 'zarincode-harir-core' ), $value );
			}

			return SmsResult::success( $value, [ 'soap' => $value ] );
		} catch ( \SoapFault $e ) {
			return SmsResult::failure( $e->getMessage(), 'soap_fault' );
		} catch ( \Exception $e ) {
			return SmsResult::failure( $e->getMessage(), 'exception' );
		}
	}

	/**
	 * ارسال از طریق کنسول (API Key).
	 *
	 * @param string[] $receptors
	 * @param string   $message
	 * @param string   $sender
	 * @return SmsResult
	 */
	private function send_console( array $receptors, $message, $sender ) {
		/**
		 * فیلتر آدرس کنسول ملی پیامک.
		 *
		 * @param string $url
		 */
		$url = (string) apply_filters( 'zch/sms/melipayamak/console_url', 'https://console.melipayamak.com/api/send' );

		return $this->request(
			$url,
			[
				'from'   => $sender,
				'to'     => implode( ',', $receptors ),
				'text'   => $message,
				'isFlash' => false,
			],
			'json'
		);
	}

	/**
	 * پارس پاسخ XML.
	 *
	 * @param string $xml
	 * @return array<string,mixed>
	 */
	private function parse_xml( $xml ) {
		if ( '' === trim( (string) $xml ) || false === strpos( (string) $xml, '<' ) ) {
			return [];
		}

		$previous = libxml_use_internal_errors( true );

		try {
			$parsed = new \SimpleXMLElement( $xml ); // phpcs:ignore WordPress.NamingConventions
		} catch ( \Exception $e ) {
			libxml_clear_errors();
			libxml_use_internal_errors( $previous );
			return [];
		}

		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		$decoded = json_decode( wp_json_encode( $parsed ), true );

		return is_array( $decoded ) ? $decoded : [];
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
		if ( 2 === $code ) {
			return 'sent';
		}
		if ( 3 === $code ) {
			return 'delivered';
		}
		if ( $code >= 4 ) {
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
			1 => __( 'در صف ارسال', 'zarincode-harir-core' ),
			2 => __( 'ارسال شد', 'zarincode-harir-core' ),
			3 => __( 'تحویل شد', 'zarincode-harir-core' ),
			4 => __( 'ارسال به اپراتور', 'zarincode-harir-core' ),
			5 => __( 'تحویل به گوشی ناموفق', 'zarincode-harir-core' ),
		];

		return isset( $labels[ $code ] ) ? $labels[ $code ] : __( 'نامشخص', 'zarincode-harir-core' );
	}
}
