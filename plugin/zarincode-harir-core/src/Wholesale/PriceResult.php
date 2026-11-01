<?php
/**
 * ظرف تغییرناپذیر نتیجهٔ محاسبهٔ قیمت.
 *
 * @package Zarincode\Harir\Wholesale
 */

namespace Zarincode\Harir\Wholesale;

defined( 'ABSPATH' ) || exit;

/**
 * نتیجهٔ محاسبهٔ سه‌قیمتی یک محصول.
 *
 * سازگار با PHP 7.4 — بدون constructor promotion و بدون readonly.
 */
final class PriceResult {

	/** @var float قیمت بدون تخفیف. */
	public $regular = 0.0;

	/** @var float قیمت نهایی مشتری عام. */
	public $customer = 0.0;

	/** @var float قیمت همکار عمده. */
	public $wholesale = 0.0;

	/** @var float مبلغ صرفه‌جویی همکار نسبت به قیمت مشتری. */
	public $saved = 0.0;

	/** @var int درصد تخفیف همکار نسبت به قیمت مشتری. */
	public $percent = 0;

	/** @var int درصد تخفیف همکار نسبت به قیمت بدون تخفیف. */
	public $percent_of_regular = 0;

	/** @var bool آیا قیمت عمده برای این محصول تعریف شده؟ */
	public $available = false;

	/** @var array<string,mixed> قانون اعمال‌شده. */
	public $rule = [];

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
	 * نتیجهٔ «قیمت عمده‌ای تعریف نشده».
	 */
	public static function not_available(): self {
		$result              = new self();
		$result->available   = false;
		return $result;
	}

	/**
	 * آیا تخفیف همکار واقعاً مثبت است؟
	 */
	public function has_discount(): bool {
		return $this->available && $this->wholesale > 0 && $this->wholesale < $this->customer;
	}

	/**
	 * خروجی آرایه‌ای برای JSON و قالب.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return [
			'regular'            => $this->regular,
			'customer'           => $this->customer,
			'wholesale'          => $this->wholesale,
			'saved'              => $this->saved,
			'percent'            => $this->percent,
			'percent_of_regular' => $this->percent_of_regular,
			'available'          => $this->available,
			'rule'               => $this->rule,
		];
	}
}
