/**
 * حریر رویا — جست‌وجو.
 *
 *  - لایهٔ جست‌وجوی تمام‌صفحه (data-zch-search-open)
 *  - پیشنهاد زنده از REST (جست‌وجوی محصول/نوشته)
 *  - کلید میان‌بر: «/» برای باز کردن جست‌وجو
 *
 * @package Zarincode_Harir
 */
( function () {
	'use strict';

	var doc = document;
	var data = window.zchData || {};
	var overlay = null;
	var timer = null;
	var lastQuery = '';

	function fa( n ) {
		return String( n ).replace( /[0-9]/g, function ( d ) {
			return '۰۱۲۳۴۵۶۷۸۹'[ d ];
		} );
	}

	function escapeHtml( s ) {
		return String( s ).replace( /[&<>"']/g, function ( c ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ];
		} );
	}

	/* --------------------------------------------------------- ساخت لایه */

	function build() {
		if ( overlay ) { return overlay; }

		overlay = doc.createElement( 'div' );
		overlay.className = 'login-overlay zch-search-overlay';
		overlay.id = 'zch-search-overlay';
		overlay.setAttribute( 'role', 'dialog' );
		overlay.setAttribute( 'aria-modal', 'true' );
		overlay.setAttribute( 'aria-label', 'جست‌وجو' );
		overlay.hidden = true;

		overlay.innerHTML =
			'<div class="zch-search-box">' +
				'<button type="button" class="zch-search-close" aria-label="بستن جست‌وجو">&times;</button>' +
				'<form role="search" method="get" action="' + escapeHtml( data.homeUrl || '/' ) + '">' +
					'<input type="search" name="s" class="zch-search-input" ' +
						'placeholder="به دنبال چه رویایی هستید؟" autocomplete="off" dir="rtl">' +
					'<input type="hidden" name="post_type" value="product">' +
					'<button type="submit" class="btn primary"><span>جست‌وجو</span></button>' +
				'</form>' +
				'<div class="zch-search-suggest" role="listbox" aria-live="polite"></div>' +
			'</div>';

		doc.body.appendChild( overlay );

		overlay.querySelector( '.zch-search-close' )
			.addEventListener( 'click', closeSearch );

		overlay.addEventListener( 'click', function ( e ) {
			if ( e.target === overlay ) { closeSearch(); }
		} );

		overlay.querySelector( '.zch-search-input' )
			.addEventListener( 'input', onInput );

		return overlay;
	}

	function openSearch() {
		var box = build();
		box.hidden = false;
		doc.body.classList.add( 'zch-overlay-open' );

		window.requestAnimationFrame( function () {
			var input = box.querySelector( '.zch-search-input' );
			input.focus();
		} );

		doc.addEventListener( 'keydown', onEsc );
	}

	function closeSearch() {
		if ( ! overlay ) { return; }

		overlay.hidden = true;
		doc.body.classList.remove( 'zch-overlay-open' );
		doc.removeEventListener( 'keydown', onEsc );

		var trigger = doc.querySelector( '[data-zch-search-open]' );
		if ( trigger ) { trigger.focus(); }
	}

	function onEsc( e ) {
		if ( e.key === 'Escape' ) { closeSearch(); }
	}

	/* -------------------------------------------------------- پیشنهاد زنده */

	function render( items ) {
		var box = overlay.querySelector( '.zch-search-suggest' );

		if ( ! items.length ) {
			box.innerHTML = '<p class="zch-search-empty">نتیجه‌ای یافت نشد</p>';
			return;
		}

		box.innerHTML = items.map( function ( it ) {
			var thumb = it.thumb
				? '<img src="' + escapeHtml( it.thumb ) + '" alt="" loading="lazy">'
				: '<span class="zch-placeholder"></span>';

			var price = it.price
				? '<span class="zch-suggest-price">' + escapeHtml( it.price ) + '</span>'
				: '';

			return '<a class="zch-suggest-item" role="option" href="' + escapeHtml( it.url ) + '">' +
				thumb +
				'<span class="zch-suggest-body">' +
					'<strong>' + escapeHtml( it.title ) + '</strong>' +
					'<small>' + escapeHtml( it.type || '' ) + '</small>' +
				'</span>' +
				price +
			'</a>';
		} ).join( '' );
	}

	function onInput( e ) {
		var q = e.target.value.trim();

		window.clearTimeout( timer );

		if ( q.length < 2 ) {
			overlay.querySelector( '.zch-search-suggest' ).innerHTML = '';
			lastQuery = '';
			return;
		}

		if ( q === lastQuery ) { return; }

		timer = window.setTimeout( function () {
			lastQuery = q;
			var url = ( data.restUrl || '/wp-json/zch/v1/' ) +
				'search?keyword=' + encodeURIComponent( q ) + '&limit=6';

			window.fetch( url, {
				credentials: 'same-origin',
				headers: { 'X-WP-Nonce': data.restNonce || '' }
			} ).then( function ( r ) {
				return r.ok ? r.json() : [];
			} ).then( function ( res ) {
				if ( ! Array.isArray( res ) ) { res = ( res && res.items ) || []; }
				render( res );
			} ).catch( function () {
				render( [] );
			} );
		}, 260 );
	}

	/* --------------------------------------------------------- اتصال رویداد */

	doc.addEventListener( 'click', function ( e ) {
		var trigger = e.target.closest ? e.target.closest( '[data-zch-search-open]' ) : null;

		if ( ! trigger ) { return; }

		// اگر پیوند واقعی جست‌وجو باشد (مثلاً فرم)، جلوی آن گرفته نمی‌شود.
		if ( trigger.tagName === 'FORM' ) { return; }

		e.preventDefault();
		openSearch();
	} );

	doc.addEventListener( 'keydown', function ( e ) {
		if ( e.key !== '/' || e.metaKey || e.ctrlKey || e.altKey ) { return; }

		var tag = ( doc.activeElement && doc.activeElement.tagName ) || '';

		if ( [ 'INPUT', 'TEXTAREA', 'SELECT' ].indexOf( tag ) > -1 ) { return; }

		e.preventDefault();
		openSearch();
	} );

	window.zchSearch = { open: openSearch, close: closeSearch };
}() );
