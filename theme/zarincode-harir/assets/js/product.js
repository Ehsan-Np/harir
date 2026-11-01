/**
 * حریر رویا — صفحهٔ محصول.
 *
 *  - گام‌شمار تعداد: آگاه از min / max / step نقش کاربر
 *  - گالری بندانگشتی
 *  - به‌روزرسانی قیمت و قاعدهٔ تعداد هنگام تغییر واریانت
 *
 * قواعد تعداد از سرور می‌آیند (`woocommerce_available_variation.zch_quantity_rule`)
 * تا منطق همیشه با PHP هم‌خوان بماند.
 *
 * @package Zarincode_Harir
 */
( function () {
	'use strict';

	var doc = document;

	function fa( n ) {
		return String( n ).replace( /[0-9]/g, function ( d ) {
			return '۰۱۲۳۴۵۶۷۸۹'[ d ];
		} );
	}

	/* --------------------------------------------------------- گام‌شمار تعداد */

	function rules( input ) {
		return {
			min: parseFloat( input.getAttribute( 'data-min' ) || input.min || '1' ) || 1,
			max: parseFloat( input.getAttribute( 'data-max' ) || input.max || '0' ) || 0,
			step: parseFloat( input.getAttribute( 'data-step' ) || input.step || '1' ) || 1,
			rounding: input.getAttribute( 'data-rounding' ) || 'down'
		};
	}

	/**
	 * گرد کردن مقدار به مضربی از step با توجه به کف و سقف.
	 *
	 * @param {number} value
	 * @param {Object} r
	 * @return {number}
	 */
	function snap( value, r ) {
		var base = r.min > 0 ? r.min : r.step;
		var steps = ( value - base ) / r.step;

		switch ( r.rounding ) {
			case 'up':
				steps = Math.ceil( steps );
				break;
			case 'nearest':
				steps = Math.round( steps );
				break;
			default:
				steps = Math.floor( steps );
		}

		var out = base + ( steps * r.step );

		if ( out < r.min ) { out = r.min; }
		if ( r.max > 0 && out > r.max ) { out = r.max; }

		return Math.round( out * 10000 ) / 10000;
	}

	function initQty( wrap ) {
		if ( wrap.dataset.zchQtyReady === '1' ) { return; }
		wrap.dataset.zchQtyReady = '1';

		var input = wrap.querySelector( 'input.qty' );
		if ( ! input ) { return; }

		var minus = wrap.querySelector( '[data-zch-qty-step="-1"]' );
		var plus = wrap.querySelector( '[data-zch-qty-step="1"]' );

		function apply( next, silent ) {
			var r = rules( input );
			var val = snap( next, r );

			input.value = val;
			input.dispatchEvent( new Event( 'change', { bubbles: true } ) );

			if ( minus ) { minus.disabled = val <= r.min; }
			if ( plus ) { plus.disabled = r.max > 0 && val >= r.max; }

			if ( ! silent ) {
				wrap.setAttribute( 'data-qty', val );
			}
		}

		if ( minus ) {
			minus.addEventListener( 'click', function () {
				var r = rules( input );
				var cur = parseFloat( input.value ) || r.min;
				apply( cur - r.step );
			} );
		}

		if ( plus ) {
			plus.addEventListener( 'click', function () {
				var r = rules( input );
				var cur = parseFloat( input.value ) || r.min;
				apply( cur + r.step );
			} );
		}

		input.addEventListener( 'change', function () {
			apply( parseFloat( input.value ) || rules( input ).min );
		} );

		input.addEventListener( 'blur', function () {
			apply( parseFloat( input.value ) || rules( input ).min );
		} );

		apply( parseFloat( input.value ) || rules( input ).min, true );
	}

	Array.prototype.forEach.call( doc.querySelectorAll( '[data-zch-qty], .quantity' ), initQty );

	/* ----------------------------------------------------------- گالری تصویر */

	( function initGallery() {
		var gallery = doc.querySelector( '.pd-gallery' );
		if ( ! gallery ) { return; }

		var main = gallery.querySelector( '.pd-main-image, .woocommerce-product-gallery__image img' );
		var thumbs = gallery.querySelectorAll( '.pd-thumb, .flex-control-thumbs img, [data-zch-thumb]' );

		if ( ! main || ! thumbs.length ) { return; }

		Array.prototype.forEach.call( thumbs, function ( thumb, i ) {
			var src = thumb.getAttribute( 'data-full' ) || thumb.src ||
				( thumb.querySelector( 'img' ) && thumb.querySelector( 'img' ).src );

			if ( ! src ) { return; }

			thumb.setAttribute( 'tabindex', '0' );
			thumb.setAttribute( 'role', 'button' );
			thumb.setAttribute( 'aria-label', 'تصویر ' + fa( i + 1 ) );

			var pick = function () {
				Array.prototype.forEach.call( thumbs, function ( t ) {
					t.classList.remove( 'active' );
					t.setAttribute( 'aria-selected', 'false' );
				} );
				thumb.classList.add( 'active' );
				thumb.setAttribute( 'aria-selected', 'true' );

				if ( main.tagName === 'IMG' ) {
					main.src = src;
					if ( main.parentElement ) { main.parentElement.setAttribute( 'href', src ); }
				} else {
					var img = main.querySelector( 'img' );
					if ( img ) { img.src = src; }
				}
			};

			thumb.addEventListener( 'click', function ( e ) { e.preventDefault(); pick(); } );
			thumb.addEventListener( 'keydown', function ( e ) {
				if ( e.key === 'Enter' || e.key === ' ' ) { e.preventDefault(); pick(); }
			} );
			thumb.addEventListener( 'mouseenter', pick );
		} );
	}() );

	/* ------------------------------------------------ تغییر واریانت (Woo) */

	doc.body && doc.body.addEventListener( 'found_variation', function ( e, variation ) {
		var wrap = doc.querySelector( '.pd-buy .quantity, form.cart .quantity' );
		var input = wrap ? wrap.querySelector( 'input.qty' ) : null;

		if ( input && variation.zch_quantity_rule ) {
			var r = variation.zch_quantity_rule;

			input.setAttribute( 'data-min', r.min || 1 );
			input.setAttribute( 'data-max', r.max || 0 );
			input.setAttribute( 'data-step', r.step || 1 );
			input.setAttribute( 'data-rounding', r.rounding || 'down' );
			input.min = r.min || 1;
			input.step = r.step || 1;
			if ( r.max > 0 ) { input.max = r.max; }

			input.value = r.min || 1;
			input.dispatchEvent( new Event( 'change', { bubbles: true } ) );

			var hint = wrap.querySelector( '.zch-qty-hint' );
			if ( hint ) {
				hint.hidden = ( r.step || 1 ) <= 1;
				hint.textContent = 'مضربی از ' + fa( r.step );
			}
		}

		if ( variation.zch_price_html ) {
			var box = doc.querySelector( '.pd-price' );
			if ( box ) { box.innerHTML = variation.zch_price_html; }
		}

		if ( variation.zch_price_note !== undefined ) {
			var note = doc.querySelector( '.pd-price-note' );

			if ( note ) {
				note.innerHTML = variation.zch_price_note;
				note.hidden = ! variation.zch_price_note;
			}
		}

		if ( variation.zch_is_wholesale ) {
			var priceWrap = doc.querySelector( '.pd-price' );
			if ( priceWrap ) {
				priceWrap.classList.add( 'pd-price-wholesale' );
				priceWrap.classList.remove( 'pd-price-retail' );
			}
		}
	} );

	doc.body && doc.body.addEventListener( 'woocommerce_variation_has_changed', function () {
		Array.prototype.forEach.call( doc.querySelectorAll( '[data-zch-qty], .quantity' ), function ( w ) {
			delete w.dataset.zchQtyReady;
			initQty( w );
		} );
	} );

	window.zchProduct = { snap: snap, initQty: initQty };
}() );
