/**
 * حریر رویا — اسکریپت اصلی قالب.
 *
 * مسئولیت‌ها:
 *  - نوار چسبان (sticky header) و کلاس اسکرول
 *  - دکمهٔ بازگشت به بالا
 *  - تب‌ها (pd-tabs، پنل)
 *  - منوی کشویی دسته‌بندی
 *  - افزودن به سبد با AJAX
 *  - علاقه‌مندی‌ها
 *  - دسترسی‌پذیری: ESC، مدیریت فوکوس، aria-expanded
 *
 * بدون وابستگی به jQuery.
 *
 * @package Zarincode_Harir
 */
( function () {
	'use strict';

	var doc = document;
	var root = doc.documentElement;
	var data = window.zchData || {};

	var reduceMotion = window.matchMedia &&
		window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	/* -------------------------------------------------------------- ابزار */

	function on( sel, evt, fn, opts ) {
		doc.addEventListener( evt, function ( e ) {
			var t = e.target.closest ? e.target.closest( sel ) : null;
			if ( t ) { fn.call( t, e, t ); }
		}, opts || false );
	}

	function q( sel, ctx ) {
		return Array.prototype.slice.call( ( ctx || doc ).querySelectorAll( sel ) );
	}

	function fa( n ) {
		return String( n ).replace( /[0-9]/g, function ( d ) {
			return '۰۱۲۳۴۵۶۷۸۹'[ d ];
		} ).replace( /\B(?=(\d{3})+(?!\d))/g, '٬' );
	}

	function announce( msg ) {
		var live = doc.getElementById( 'zch-live' );
		if ( ! live ) {
			live = doc.createElement( 'div' );
			live.id = 'zch-live';
			live.className = 'screen-reader-text';
			live.setAttribute( 'role', 'status' );
			live.setAttribute( 'aria-live', 'polite' );
			doc.body.appendChild( live );
		}
		live.textContent = '';
		window.setTimeout( function () { live.textContent = msg; }, 60 );
	}

	function toast( msg ) {
		var box = doc.querySelector( '.zch-toast' );
		if ( ! box ) {
			box = doc.createElement( 'div' );
			box.className = 'zch-toast';
			box.setAttribute( 'role', 'status' );
			doc.body.appendChild( box );
		}
		box.textContent = msg;
		box.classList.add( 'is-visible' );
		window.clearTimeout( box._t );
		box._t = window.setTimeout( function () {
			box.classList.remove( 'is-visible' );
		}, 3200 );
	}

	/* ------------------------------------------------------- سربرگ چسبان */

	var header = doc.querySelector( '.header' );
	var toTop = doc.querySelector( '[data-zch-scroll-top]' );
	var last = -1;

	function onScroll() {
		var y = window.pageYOffset || root.scrollTop || 0;

		if ( y === last ) { return; }
		last = y;

		root.classList.toggle( 'is-scrolled', y > 8 );

		if ( header ) {
			header.classList.toggle( 'is-sticky', y > 160 );
			header.classList.toggle( 'is-hidden', y > 420 && y > last && ! header.matches( ':hover' ) );
		}

		if ( toTop ) {
			toTop.classList.toggle( 'is-visible', y > 500 );
		}
	}

	window.addEventListener( 'scroll', onScroll, { passive: true } );
	onScroll();

	if ( toTop ) {
		toTop.addEventListener( 'click', function () {
			window.scrollTo( { top: 0, behavior: reduceMotion ? 'auto' : 'smooth' } );
		} );
	}

	/* --------------------------------------------------------- تب‌های محتوا */

	function initTabs( wrap ) {
		var buttons = q( '.tab, [role="tab"], .pd-tab-btn', wrap );
		var panels = q( '.tab-panel, [role="tabpanel"], .panel', wrap );

		if ( ! buttons.length ) { return; }

		buttons.forEach( function ( btn, i ) {
			var target = btn.getAttribute( 'data-tab' ) || btn.getAttribute( 'aria-controls' );
			var panel = target ? doc.getElementById( target ) : panels[ i ];

			btn.setAttribute( 'role', 'tab' );
			if ( panel ) {
				if ( ! panel.id ) { panel.id = 'zch-tab-' + i; }
				btn.setAttribute( 'aria-controls', panel.id );
			}

			btn.addEventListener( 'click', function () {
				buttons.forEach( function ( b ) {
					b.classList.remove( 'active' );
					b.setAttribute( 'aria-selected', 'false' );
					b.setAttribute( 'tabindex', '-1' );
				} );
				panels.forEach( function ( p ) { p.classList.remove( 'active' ); p.hidden = true; } );

				btn.classList.add( 'active' );
				btn.setAttribute( 'aria-selected', 'true' );
				btn.removeAttribute( 'tabindex' );

				if ( panel ) {
					panel.classList.add( 'active' );
					panel.hidden = false;
				}
			} );

			btn.addEventListener( 'keydown', function ( e ) {
				var idx = buttons.indexOf( btn );
				var next = -1;

				// در RTL، فلش چپ = بعدی.
				if ( e.key === 'ArrowLeft' ) { next = ( idx + 1 ) % buttons.length; }
				if ( e.key === 'ArrowRight' ) { next = ( idx - 1 + buttons.length ) % buttons.length; }
				if ( e.key === 'Home' ) { next = 0; }
				if ( e.key === 'End' ) { next = buttons.length - 1; }

				if ( next > -1 ) {
					e.preventDefault();
					buttons[ next ].focus();
					buttons[ next ].click();
				}
			} );
		} );

		if ( buttons[ 0 ] && ! wrap.querySelector( '.active[role="tab"], .tab.active' ) ) {
			buttons[ 0 ].click();
		}
	}

	q( '.pd-tabs, .tabs, [data-zch-tabs], .panel-tabs' ).forEach( initTabs );

	/* ------------------------------------------ منوی کشویی دسته‌بندی */

	on( '[data-zch-dropdown]', 'click', function ( e, trigger ) {
		var name = trigger.getAttribute( 'data-zch-dropdown' );
		var box = doc.getElementById( 'zch-' + name + '-dropdown' ) ||
			doc.querySelector( '.zch-' + name + '-dropdown' );

		if ( ! box ) { return; }

		var open = box.hidden;

		q( '[data-zch-dropdown]' ).forEach( function ( b ) {
			b.setAttribute( 'aria-expanded', 'false' );
		} );

		box.hidden = ! open;
		box.classList.toggle( 'is-open', open );
		trigger.setAttribute( 'aria-expanded', open ? 'true' : 'false' );

		e.preventDefault();
	} );

	doc.addEventListener( 'click', function ( e ) {
		if ( e.target.closest( '[data-zch-dropdown]' ) ) { return; }

		q( '.zch-cat-dropdown.is-open' ).forEach( function ( box ) {
			box.hidden = true;
			box.classList.remove( 'is-open' );
		} );
		q( '[data-zch-dropdown]' ).forEach( function ( b ) {
			b.setAttribute( 'aria-expanded', 'false' );
		} );
	} );

	doc.addEventListener( 'keydown', function ( e ) {
		if ( e.key !== 'Escape' ) { return; }

		q( '.zch-cat-dropdown.is-open' ).forEach( function ( box ) {
			box.hidden = true;
			box.classList.remove( 'is-open' );
		} );
		q( '[data-zch-dropdown]' ).forEach( function ( b ) {
			b.setAttribute( 'aria-expanded', 'false' );
			b.focus();
		} );
	} );

	/* -------------------------------------------------- افزودن به سبد AJAX */

	function setBagCount( n ) {
		q( '.action-bag i, .mobile-trade i' ).forEach( function ( el ) {
			el.textContent = fa( n );
		} );
		q( '.action-bag .screen-reader-text' ).forEach( function ( el ) {
			el.textContent = n + ' کالا در سبد';
		} );
	}

	on( '[data-zch-add-to-cart]', 'click', function ( e, btn ) {
		// محصولات متغیر باید به صفحهٔ محصول بروند تا انتخاب انجام شود.
		if ( btn.hasAttribute( 'data-variable' ) ) { return; }

		if ( typeof wc_add_to_cart_params === 'undefined' ) { return; }

		e.preventDefault();

		if ( btn.classList.contains( 'loading' ) ) { return; }

		var id = btn.getAttribute( 'data-zch-add-to-cart' );
		var qtyEl = btn.closest( '.product' ) ?
			btn.closest( '.product' ).querySelector( '.quantity input.qty, input.qty' ) : null;
		var qty = qtyEl ? parseInt( qtyEl.value, 10 ) || 1 : 1;

		btn.classList.add( 'loading' );
		btn.setAttribute( 'aria-busy', 'true' );

		var body = new URLSearchParams();
		body.append( 'action', 'zch_add_to_cart' );
		body.append( 'product_id', id );
		body.append( 'quantity', qty );
		body.append( '_wpnonce', data.nonce || '' );

		window.fetch( data.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString()
		} ).then( function ( r ) {
			return r.json();
		} ).then( function ( res ) {
			btn.classList.remove( 'loading' );
			btn.removeAttribute( 'aria-busy' );

			if ( res && res.success ) {
				if ( res.data && typeof res.data.count !== 'undefined' ) {
					setBagCount( res.data.count );
				}
				toast( ( res.data && res.data.message ) || ( data.i18n || {} ).addedToCart || 'به سبد خرید افزوده شد' );
				announce( 'به سبد خرید افزوده شد' );

				doc.body.dispatchEvent( new CustomEvent( 'zch:added_to_cart', { detail: res.data } ) );

				if ( doc.body.classList.contains( 'woocommerce-cart' ) ) {
					window.location.reload();
				}
			} else {
				toast( ( res && res.data && res.data.message ) || ( data.i18n || {} ).error || 'خطا' );
			}
		} ).catch( function () {
			btn.classList.remove( 'loading' );
			btn.removeAttribute( 'aria-busy' );
			window.location.href = btn.getAttribute( 'href' );
		} );
	} );

	/* -------------------------------------------------------- علاقه‌مندی */

	on( '[data-zch-wishlist]', 'click', function ( e, btn ) {
		e.preventDefault();

		var id = btn.getAttribute( 'data-zch-wishlist' );
		var on_ = btn.getAttribute( 'aria-pressed' ) === 'true';

		btn.setAttribute( 'aria-pressed', on_ ? 'false' : 'true' );
		btn.classList.toggle( 'is-active', ! on_ );

		var body = new URLSearchParams();
		body.append( 'action', 'zch_toggle_wishlist' );
		body.append( 'product_id', id );
		body.append( '_wpnonce', data.nonce || '' );

		window.fetch( data.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString()
		} ).then( function ( r ) { return r.json(); } ).then( function ( res ) {
			if ( res && res.success && res.data ) {
				btn.setAttribute( 'aria-pressed', res.data.added ? 'true' : 'false' );
				btn.classList.toggle( 'is-active', !! res.data.added );
			}
		} ).catch( function () { /* بی‌صدا */ } );
	} );

	/* -------------------------------------------------- پیمایش نرم لنگرها */

	on( '[data-zch-toc], a[href^="#"]', 'click', function ( e, a ) {
		var hash = a.getAttribute( 'href' );

		if ( ! hash || hash.length < 2 || hash === '#' ) { return; }

		var target = doc.getElementById( hash.slice( 1 ) );

		if ( ! target ) { return; }

		e.preventDefault();

		var top = target.getBoundingClientRect().top + ( window.pageYOffset || 0 ) -
			( ( header && header.offsetHeight ) || 0 ) - 16;

		window.scrollTo( { top: top, behavior: reduceMotion ? 'auto' : 'smooth' } );
		target.setAttribute( 'tabindex', '-1' );
		target.focus( { preventScroll: true } );
		window.history.replaceState( null, '', hash );
	} );

	/* ----------------------------------------------------------- رویدادها */

	doc.addEventListener( 'zch:init', function () {
		q( '.pd-tabs, .tabs, [data-zch-tabs], .panel-tabs' ).forEach( initTabs );
	} );

	window.zch = {
		fa: fa,
		toast: toast,
		announce: announce,
		setBagCount: setBagCount,
		initTabs: initTabs,
		reduceMotion: reduceMotion
	};
}() );
