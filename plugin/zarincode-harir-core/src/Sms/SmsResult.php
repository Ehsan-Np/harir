<?php
/**
 * نتیجهٔ یک ارسال پیامک.
 *
 * @package Zarincode\Harir\Sms
 */

namespace Zarincode\Harir\Sms;

defined( 'ABSPATH' ) || exit;

/**
 * ظرف نتیجهٔ ارسال.
 */
final class SmsResult {

	/** @var bool */
	public $ok = false;

	/** @var string شناسهٔ پیامک در پنل. */
	public $message_id = '';

	/** @var string کد وضعیت خام پنل. */
	public $code = '';

	/** @var string پیام خطای خوانا. */
	public $error = '';

	/** @var float هزینهٔ ارسال. */
	public $cost = 0.0;

	/** @var array<string,mixed> پاسخ خام برای دیباگ. */
	public $raw = [];

	/**
	 * @param array<string,mixed> $data
	 */
	public function __construct( array $data = [] ) {
		foreach ( $data as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->$key = $value;
			}
		}
	}

	/**
	 * ساخت نتیجهٔ موفق.
	 *
	 * @param string $message_id
	 * @param array<string,mixed> $raw
	 * @return self
	 */
	public static function success( $message_id = '', array $raw = [] ) {
		return new self(
			[
				'ok'         => true,
				'message_id' => (string) $message_id,
				'raw'        => $raw,
			]
		);
	}

	/**
	 * ساخت نتیجهٔ ناموفق.
	 *
	 * @param string $error
	 * @param string $code
	 * @param array<string,mixed> $raw
	 * @return self
	 */
	public static function failure( $error, $code = '', array $raw = [] ) {
		return new self(
			[
				'ok'    => false,
				'error' => (string) $error,
				'code'  => (string) $code,
				'raw'   => $raw,
			]
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	public function to_array() {
		return [
			'ok'         => $this->ok,
			'message_id' => $this->message_id,
			'code'       => $this->code,
			'error'      => $this->error,
			'cost'       => $this->cost,
		];
	}
}
