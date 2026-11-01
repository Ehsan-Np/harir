<?php
/**
 * registry آیکون‌های SVG حریر رویا.
 *
 * این ۴۲ آیکون مستقیماً از منبع HTML طرح استخراج شدند.
 * در طرح اصلی ۸۹۰ بار SVG درون‌خطی تکرار شده بود که حجم صفحه را
 * بی‌دلیل بالا می‌برد؛ اینجا یک‌جا ثبت و با `zch_icon()` فراخوانی می‌شوند.
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

/**
 * @return array<string,array{viewBox:string,body:string}>
 */
function zch_icons() {
	static $icons = null;

	if ( null !== $icons ) {
		return $icons;
	}

	$icons = [
		'wholesale' => [ 'viewBox' => '0 0 24 24', 'body' => '<path d="m4 7 8-4 8 4v10l-8 4-8-4zM4 7l8 4 8-4M12 11v10"/>' ],
		'arrow-start' => [ 'viewBox' => '0 0 24 24', 'body' => '<path d="M19 12H5m0 0 5-5m-5 5 5 5"/>' ],
		'shield-check' => [ 'viewBox' => '0 0 24 24', 'body' => '<path d="M12 3 4.5 6v5c0 5 3 8 7.5 10 4.5-2 7.5-5 7.5-10V6z"/><path d="m9 12 2 2 4-4"/>' ],
		'bag' => [ 'viewBox' => '0 0 24 24', 'body' => '<path d="M5.5 8.5h13l1 11h-15z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/>' ],
		'headset' => [ 'viewBox' => '0 0 24 24', 'body' => '<path d="M4 13v-1a8 8 0 0 1 16 0v1M4 13h3v5H5a1 1 0 0 1-1-1zM20 13h-3v5h2a1 1 0 0 0 1-1zM17 19c-.5 1-1.7 1.5-3 1.5h-2"/>' ],
		'sparkle' => [ 'viewBox' => '0 0 24 24', 'body' => '<path d="M12 3c.6 4.6 2.4 6.4 7 7-4.6.6-6.4 2.4-7 7-.6-4.6-2.4-6.4-7-7 4.6-.6 6.4-2.4 7-7ZM19 16c.3 1.8 1.2 2.7 3 3-1.8.3-2.7 1.2-3 3-.3-1.8-1.2-2.7-3-3 1.8-.3 2.7-1.2 3-3Z"/>' ],
		'search' => [ 'viewBox' => '0 0 24 24', 'body' => '<circle cx="11" cy="11" r="7"/><path d="m16.2 16.2 4 4"/>' ],
		'user' => [ 'viewBox' => '0 0 24 24', 'body' => '<circle cx="12" cy="8" r="3.3"/><path d="M5.5 20c.5-4 2.8-6 6.5-6s6 2 6.5 6"/>' ],
		'brand' => [ 'viewBox' => '0 0 52 52', 'body' => '<circle cx="26" cy="26" r="22.5" stroke="currentColor" stroke-width="1.2"/><path d="M15.2 31.5c8.1-2 11.8-8.5 14.1-17 1.5 8.7 3.7 14.2 8.6 17-7.7 4.6-15.2 4.7-22.7 0Z" stroke="currentColor" stroke-width="1.45" stroke-linecap="round" stroke-linejoin="round"/><path d="M18 34.5c5.5 3 11.4 3.4 17.2.1M35.9 15.5v4.8m-2.4-2.4h4.8" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>' ],
		'menu' => [ 'viewBox' => '0 0 24 24', 'body' => '<path d="M4 7h16M4 12h16M4 17h16"/>' ],
		'chevron-down' => [ 'viewBox' => '0 0 24 24', 'body' => '<path d="m7 10 5 5 5-5"/>' ],
		'chat' => [ 'viewBox' => '0 0 24 24', 'body' => '<path d="M20 11.3a7.7 7.7 0 0 1-8 7.5 9 9 0 0 1-3.2-.6L5 20l.8-3a7.2 7.2 0 0 1-1.8-4.7C4 8 7.6 5 12 5s8 2.8 8 6.3Z"/><path d="M8.5 11.5h7"/>' ],
		'mail' => [ 'viewBox' => '0 0 24 24', 'body' => '<rect x="3" y="5.5" width="18" height="13" rx="2"/><path d="m4 7 8 6 8-6"/>' ],
		'grid' => [ 'viewBox' => '0 0 24 24', 'body' => '<rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><rect x="14" y="14" width="6" height="6" rx="1"/>' ],
		'chevron-right' => [ 'viewBox' => '0 0 24 24', 'body' => '<path d="m9 18 6-6-6-6"/>' ],
		'truck' => [ 'viewBox' => '0 0 24 24', 'body' => '<path d="M3 6h11v11H3zM14 10h3l4 4v3h-7z"/><circle cx="7.5" cy="18" r="1.7"/><circle cx="17.5" cy="18" r="1.7"/>' ],
		'home' => [ 'viewBox' => '0 0 24 24', 'body' => '<path d="m3.5 11 8.5-7 8.5 7M6 9v11h12V9M10 20v-6h4v6"/>' ],
		'heart' => [ 'viewBox' => '0 0 24 24', 'body' => '<path d="M20.7 8.5c0 4.1-8.7 9.8-8.7 9.8S3.3 12.6 3.3 8.5a4.1 4.1 0 0 1 7.1-2.7L12 7.4l1.6-1.6a4.1 4.1 0 0 1 7.1 2.7Z"/>' ],
		'chevron-up' => [ 'viewBox' => '0 0 24 24', 'body' => '<path d="M12 5v14M5 12h14"/>' ],
		'instagram' => [ 'viewBox' => '0 0 24 24', 'body' => '<path d="m6 14 6-6 6 6"/>' ],
		'send' => [ 'viewBox' => '0 0 24 24', 'body' => '<rect x="4" y="4" width="16" height="16" rx="4"/><circle cx="12" cy="12" r="3.5"/><circle cx="17" cy="7" r=".7"/>' ],
		'plus' => [ 'viewBox' => '0 0 24 24', 'body' => '<path d="M21 4 3 11l7 2 2 7 9-16ZM10 13l5-5"/>' ],
		'check-circle' => [ 'viewBox' => '0 0 24 24', 'body' => '<circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.5 2.5 5-5"/>' ],
		'document' => [ 'viewBox' => '0 0 24 24', 'body' => '<path d="M6 3h8l4 4v14H6zM14 3v5h4M9 12h6M9 16h6"/>' ],
		'pin' => [ 'viewBox' => '0 0 24 24', 'body' => '<path d="M19 10c0 5-7 10-7 10S5 15 5 10a7 7 0 1 1 14 0Z"/><circle cx="12" cy="10" r="2"/>' ],
		'palette' => [ 'viewBox' => '0 0 24 24', 'body' => '<path d="M12 3a9 9 0 1 0 0 18h1.5a2 2 0 0 0 1.3-3.5c-.7-.6-.5-1.7.4-2l2.3-.7A5 5 0 0 0 21 10c0-4-4-7-9-7Z"/><circle cx="7.5" cy="10" r=".7"/><circle cx="10" cy="6.8" r=".7"/><circle cx="14" cy="6.8" r=".7"/><circle cx="17" cy="9.3" r=".7"/>' ],
		'list' => [ 'viewBox' => '0 0 24 24', 'body' => '<path d="M10 4H5v16h5M13 8l4 4-4 4M8 12h9"/>' ],
		'store' => [ 'viewBox' => '0 0 24 24', 'body' => '<path d="m5 12 4 4L19 6"/>' ],
		'logout' => [ 'viewBox' => '0 0 24 24', 'body' => '<path d="M4 10v10h16V10M3 10l2-6h14l2 6c0 1.5-1 2-2 2s-2-.5-2-2c0 1.5-1 2-2 2s-2-.5-2-2c0 1.5-1 2-2 2s-2-.5-2-2c0 1.5-1 2-2 2s-2-.5-2-2ZM9 20v-5h6v5"/>' ],
		'check' => [ 'viewBox' => '0 0 24 24', 'body' => '<path d="M9 6h11M9 12h11M9 18h11M4 6h1M4 12h1M4 18h1"/>' ],
		'clock' => [ 'viewBox' => '0 0 24 24', 'body' => '<rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18M6 15h4"/>' ],
		'download' => [ 'viewBox' => '0 0 24 24', 'body' => '<path d="M4 6h16v4a2 2 0 0 0 0 4v4H4v-4a2 2 0 0 0 0-4zM12 6v12"/>' ],
		'ticket' => [ 'viewBox' => '0 0 24 24', 'body' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.2 2"/>' ],
		'calendar' => [ 'viewBox' => '0 0 24 24', 'body' => '<path d="M12 3v12m-4-4 4 4 4-4M4 18v3h16v-3"/>' ],
		'share' => [ 'viewBox' => '0 0 24 24', 'body' => '<circle cx="18" cy="5" r="2.2"/><circle cx="6" cy="12" r="2.2"/><circle cx="18" cy="19" r="2.2"/><path d="m8 11 8-5M8 13l8 5"/>' ],
		'bell' => [ 'viewBox' => '0 0 24 24', 'body' => '<path d="m4 17 13-13 3 3L7 20zM12 9l2 2m-5 1 2 2m-5 1 2 2"/>' ],
		'refresh' => [ 'viewBox' => '0 0 24 24', 'body' => '<path d="M18 9a6 6 0 0 0-12 0c0 6-2 6-2 8h16c0-2-2-2-2-8ZM10 20h4"/>' ],
		'tag' => [ 'viewBox' => '0 0 24 24', 'body' => '<path d="M20 7v5h-5M4 17v-5h5M6 9a7 7 0 0 1 12-2l2 5M4 12l2 5a7 7 0 0 0 12-2"/>' ],
		'star' => [ 'viewBox' => '0 0 24 24', 'body' => '<path d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"/>' ],
		'phone' => [ 'viewBox' => '0 0 24 24', 'body' => '<path d="m4 7 8-4 8 4v10l-8 4-8-4zM4 7l8 4 8-4M12 11v10"/><path d="m8 15 1.5 1.5L12 14"/>' ],
		'cube-check' => [ 'viewBox' => '0 0 24 24', 'body' => '<path d="M7 4 5.2 5.8c-.6.6.2 3.7 3.8 7.3s6.6 4.4 7.2 3.8L18 15l-3.6-2-1.5 1c-1.4-.6-3.3-2.5-4-4l1-1.4Z"/>' ],
		'pencil' => [ 'viewBox' => '0 0 24 24', 'body' => '<path d="m4 17 1 3 3-1L19 8l-3-3L5 16zM14.5 6.5l3 3"/>' ],
	];

	/**
	 * فیلتر registry آیکون‌ها — برای افزودن آیکون سفارشی توسط Child Theme.
	 *
	 * @param array $icons
	 */
	return apply_filters( 'zch/icons', $icons );
}