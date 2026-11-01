/**
 * حریر رویا — ناوبری.
 *
 * مسئولیت‌ها:
 *  - کشوی منوی موبایل (data-zch-toggle="nav-drawer")
 *  - زیرمنوهای بازشو در دسکتاپ و موبایل
 *  - پرش فوکوس و تلهٔ فوکوس در کشو
 *  - بستن با ESC و کلیک بیرون
 *  - همگام‌سازی aria-expanded / aria-hidden
 *
 * @package Zarincode_Harir
 */
( function () {
	'use strict';

	var doc = document;
	var body = doc.body;
	var openDrawer = null;
	var lastFocus = null;

	var FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), ' +
		'select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

	function focusables( el ) {
		return Array.prototype.slice.call( el.querySelectorAll( FOCUSABLE ) )
			.filter( function ( n ) { return n.offsetParent !== null; } );
	}

	function trap( e ) {
		if ( ! openDrawer || e.key !== 'Tab' ) { return; }

		var list = focusables( openDrawer );
		if ( ! list.length ) { return; }

		var first = list[ 0 ];
		var last = list[ list.length - 1 ];

		if ( e.shiftKey && doc.activeElement === first ) {
			e.preventDefault();
			last.focus();
		} else if ( ! e.shiftKey && doc.activeElement === last ) {
			e.preventDefault();
			first.focus();
		}
	}

	function open( drawer, trigger ) {
		if ( openDrawer && openDrawer !== drawer ) { close( openDrawer ); }

		lastFocus = trigger || doc.activeElement;
		openDrawer = drawer;

		drawer.hidden = false;
		drawer.classList.add( 'is-open' );
		body.classList.add( 'zch-drawer-open' );

		if ( trigger ) {
			trigger.setAttribute( 'aria-expanded', 'true' );
		}

		// اجازهٔ رندر انیمیشن.
		window.requestAnimationFrame( function () {
			var list = focusables( drawer );
			if ( list.length ) { list[ 0 ].focus(); }
		} );

		doc.addEventListener( 'keydown', trap );
	}

	function close( drawer ) {
		drawer = drawer || openDrawer;
		if ( ! drawer ) { return; }

		drawer.classList.remove( 'is-open' );
		body.classList.remove( 'zch-drawer-open' );
		openDrawer = null;

		doc.removeEventListener( 'keydown', trap );

		var trigger = doc.querySelector( '[data-zch-toggle="nav-drawer"]' );

		window.setTimeout( function () {
			if ( ! drawer.classList.contains( 'is-open' ) ) {
				drawer.hidden = true;
			}
		}, 220 );

		if ( trigger ) {
			trigger.setAttribute( 'aria-expanded', 'false' );
			if ( lastFocus === trigger ) { trigger.focus(); }
		}
		lastFocus = null;
	}

	/* ---------------------------------------------------------- کشوی منو */

	doc.addEventListener( 'click', function ( e ) {
		var trigger = e.target.closest ? e.target.closest( '[data-zch-toggle]' ) : null;

		if ( ! trigger ) { return; }

		var id = trigger.getAttribute( 'data-zch-toggle' );
		var drawer = doc.getElementById( 'zch-' + id ) || doc.getElementById( id );

		if ( ! drawer ) { return; }

		e.preventDefault();

		if ( drawer.classList.contains( 'is-open' ) ) {
			close( drawer );
		} else {
			open( drawer, trigger );
		}
	} );

	// کلیک روی پس‌زمینهٔ کشو.
	doc.addEventListener( 'click', function ( e ) {
		if ( ! openDrawer ) { return; }
		if ( e.target === openDrawer || e.target.closest( '.zch-drawer-backdrop' ) ) {
			close( openDrawer );
		}
	} );

	doc.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Escape' && openDrawer ) { close( openDrawer ); }
	} );

	/* -------------------------------------------------- زیرمنوی بازشو (موبایل) */

	function initSubmenus() {
		var items = doc.querySelectorAll( '.nav-links .has-children' );

		Array.prototype.forEach.call( items, function ( li ) {
			var link = li.querySelector( 'a' );
			var sub = li.querySelector( '.zch-submenu, .nav-sub' );

			if ( ! link || ! sub || li.dataset.zchSub === '1' ) { return; }
			li.dataset.zchSub = '1';

			var btn = doc.createElement( 'button' );
			btn.type = 'button';
			btn.className = 'zch-sub-toggle';
			btn.setAttribute( 'aria-expanded', 'false' );
			btn.setAttribute( 'aria-label', 'نمایش زیرمنو' );
			btn.innerHTML = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" ' +
				'stroke="currentColor" stroke-width="2" stroke-linecap="round" ' +
				'stroke-linejoin="round" aria-hidden="true" focusable="false">' +
				'<path d="m6 9 6 6 6-6"/></svg>';

			link.insertAdjacentElement( 'afterend', btn );
			sub.hidden = true;

			btn.addEventListener( 'click', function () {
				var isOpen = btn.getAttribute( 'aria-expanded' ) === 'true';
				btn.setAttribute( 'aria-expanded', isOpen ? 'false' : 'true' );
				li.classList.toggle( 'is-open', ! isOpen );
				sub.hidden = isOpen;
			} );
		} );
	}

	initSubmenus();

	/* --------------------------------------------- فعال‌سازی آیتم جاری */

	( function markCurrent() {
		var path = window.location.pathname.replace( /\/$/, '' );
		if ( ! path ) { return; }

		Array.prototype.forEach.call( doc.querySelectorAll( '.nav-links a' ), function ( a ) {
			try {
				var href = new URL( a.href, window.location.href ).pathname.replace( /\/$/, '' );
				if ( href === path ) {
					a.classList.add( 'active' );
					var li = a.closest( 'li' );
					if ( li ) { li.classList.add( 'active' ); }
				}
			} catch ( err ) { /* پیوند نامعتبر نادیده گرفته می‌شود */ }
		} );
	}() );

	window.zchNav = { open: open, close: close, initSubmenus: initSubmenus };
}() );
