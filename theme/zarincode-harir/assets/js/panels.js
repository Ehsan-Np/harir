/**
 * حریر رویا — پنل کاربری.
 *
 *  - تب‌های پنل با همگام‌سازی هش نشانی (#dashbaord)
 *  - باز/بسته کردن کارت‌های آکاردئونی
 *  - شمارشگر معکوس OTP
 *  - کپی متن (کلید سفارش، کد همکاری)
 *  - گردونهٔ شانس (فقط چرخش بصری؛ نتیجه از سرور می‌آید)
 *
 * @package Zarincode_Harir
 */
( function () {
	'use strict';

	var doc = document;
	var data = window.zchData || {};

	function fa( n ) {
		return String( n ).replace( /[0-9]/g, function ( d ) {
			return '۰۱۲۳۴۵۶۷۸۹'[ d ];
		} );
	}

	function toast( msg ) {
		if ( window.zch && window.zch.toast ) { window.zch.toast( msg ); return; }

		var box = doc.querySelector( '.zch-toast' );
		if ( ! box ) {
			box = doc.createElement( 'div' );
			box.className = 'zch-toast';
			doc.body.appendChild( box );
		}
		box.textContent = msg;
		box.classList.add( 'is-visible' );
		window.setTimeout( function () { box.classList.remove( 'is-visible' ); }, 3000 );
	}

	/* -------------------------------------------------------- تب‌های پنل */

	( function panelTabs() {
		var links = doc.querySelectorAll( '.panel-tabs a, [data-zch-panel-tab]' );
		var panes = doc.querySelectorAll( '[data-zch-panel-pane]' );

		if ( ! links.length || ! panes.length ) { return; }

		function activate( key ) {
			var found = false;

			Array.prototype.forEach.call( panes, function ( pane ) {
				var match = pane.getAttribute( 'data-zch-panel-pane' ) === key;
				pane.hidden = ! match;
				pane.classList.toggle( 'is-active', match );
				if ( match ) { found = true; }
			} );

			Array.prototype.forEach.call( links, function ( link ) {
				var match = ( link.getAttribute( 'data-zch-panel-tab' ) === key ) ||
					( link.getAttribute( 'href' ) === '#' + key );

				link.classList.toggle( 'active', match );
				link.setAttribute( 'aria-current', match ? 'page' : 'false' );
			} );

			return found;
		}

		Array.prototype.forEach.call( links, function ( link ) {
			link.addEventListener( 'click', function ( e ) {
				var key = link.getAttribute( 'data-zch-panel-tab' ) ||
					( link.getAttribute( 'href' ) || '' ).replace( '#', '' );

				if ( ! key ) { return; }

				e.preventDefault();
				activate( key );
				window.history.replaceState( null, '', '#' + key );
			} );
		} );

		var hash = window.location.hash.replace( '#', '' );
		if ( hash && ! activate( hash ) ) { activate( '' ); }
	}() );

	/* ------------------------------------------------------------- آکاردئون */

	doc.addEventListener( 'click', function ( e ) {
		var head = e.target.closest ? e.target.closest( '[data-zch-accordion]' ) : null;
		if ( ! head ) { return; }

		var box = head.nextElementSibling;
		if ( ! box ) { return; }

		var isOpen = head.getAttribute( 'aria-expanded' ) === 'true';

		head.setAttribute( 'aria-expanded', isOpen ? 'false' : 'true' );
		box.hidden = isOpen;
		head.parentElement.classList.toggle( 'is-open', ! isOpen );
	} );

	/* ------------------------------------------------------- شمارشگر OTP */

	function startOtp( btn, seconds ) {
		var left = seconds;
		btn.disabled = true;
		btn.dataset.zchOtp = '1';

		var tick = window.setInterval( function () {
			left--;

			if ( left <= 0 ) {
				window.clearInterval( tick );
				btn.disabled = false;
				btn.textContent = btn.dataset.zchOtpLabel || 'ارسال دوبارهٔ کد';
				delete btn.dataset.zchOtp;
				return;
			}

			btn.textContent = fa( left ) + ' ثانیه';
		}, 1000 );
	}

	doc.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest ? e.target.closest( '[data-zch-otp-resend]' ) : null;
		if ( ! btn || btn.disabled ) { return; }

		btn.dataset.zchOtpLabel = btn.textContent;
		startOtp( btn, parseInt( btn.getAttribute( 'data-zch-otp-resend' ), 10 ) || 120 );
	} );

	doc.addEventListener( 'zch:otp_sent', function ( e ) {
		var btn = doc.querySelector( '[data-zch-otp-resend]' );
		if ( btn ) {
			btn.dataset.zchOtpLabel = btn.dataset.zchOtpLabel || btn.textContent;
			startOtp( btn, ( e.detail && e.detail.ttl ) || 120 );
		}
	} );

	/* ------------------------------------------------------------ کپی متن */

	doc.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest ? e.target.closest( '[data-zch-copy]' ) : null;
		if ( ! btn ) { return; }

		var text = btn.getAttribute( 'data-zch-copy' );

		var done = function () { toast( 'کپی شد' ); };
		var fail = function () { toast( 'کپی نشد؛ متن را دستی انتخاب کنید' ); };

		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( text ).then( done ).catch( fail );
			return;
		}

		try {
			var ta = doc.createElement( 'textarea' );
			ta.value = text;
			ta.setAttribute( 'readonly', '' );
			ta.style.position = 'fixed';
			ta.style.opacity = '0';
			doc.body.appendChild( ta );
			ta.select();
			doc.execCommand( 'copy' );
			doc.body.removeChild( ta );
			done();
		} catch ( err ) {
			fail();
		}
	} );

	/* --------------------------------------------------------- گردونه شانس */

	doc.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest ? e.target.closest( '[data-zch-wheel-spin]' ) : null;
		if ( ! btn || btn.classList.contains( 'is-spinning' ) ) { return; }

		var wheel = doc.querySelector( '[data-zch-wheel]' );
		if ( ! wheel ) { return; }

		btn.classList.add( 'is-spinning' );
		btn.disabled = true;

		var body = new URLSearchParams();
		body.append( 'action', 'zch_wheel_spin' );
		body.append( '_wpnonce', data.nonce || '' );

		window.fetch( data.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString()
		} ).then( function ( r ) { return r.json(); } ).then( function ( res ) {
			var deg = 360 * 5 + ( ( res && res.data && res.data.degrees ) || 0 );

			wheel.style.transition = 'transform 4.2s cubic-bezier(.12,.72,.08,1)';
			wheel.style.transform = 'rotate(' + deg + 'deg)';

			window.setTimeout( function () {
				btn.classList.remove( 'is-spinning' );

				if ( res && res.success && res.data ) {
					var out = doc.querySelector( '[data-zch-wheel-result]' );

					if ( out ) {
						out.innerHTML = res.data.html || '';
						out.hidden = false;
					}

					if ( typeof res.data.spins_left !== 'undefined' ) {
						var left = doc.querySelector( '[data-zch-wheel-left]' );
						if ( left ) { left.textContent = fa( res.data.spins_left ); }
					}

					if ( ! res.data.spins_left ) { btn.disabled = true; }
				} else {
					btn.disabled = false;
					toast( ( res && res.data && res.data.message ) || 'امکان چرخش وجود ندارد' );
				}
			}, 4300 );
		} ).catch( function () {
			btn.classList.remove( 'is-spinning' );
			btn.disabled = false;
			toast( 'خطا در ارتباط با سرور' );
		} );
	} );

	/* --------------------------------------------------------- تیکت: پیوست */

	doc.addEventListener( 'change', function ( e ) {
		var input = e.target;

		if ( ! input.matches || ! input.matches( '[data-zch-ticket-files]' ) ) { return; }

		var list = doc.querySelector( '[data-zch-ticket-filelist]' );
		if ( ! list ) { return; }

		var names = Array.prototype.map.call( input.files || [], function ( f ) {
			return f.name;
		} );

		list.innerHTML = names.length
			? names.map( function ( n ) { return '<span>' + n + '</span>'; } ).join( '' )
			: '';
		list.hidden = ! names.length;
	} );

	window.zchPanel = { toast: toast };
}() );
