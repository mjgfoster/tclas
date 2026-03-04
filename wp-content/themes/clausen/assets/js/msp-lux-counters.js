/**
 * MSP+LUX counter animations
 *
 * Animates .tclas-msp-stat__value[data-count] elements when they scroll
 * into view. Uses IntersectionObserver + requestAnimationFrame.
 *
 * data-count  — target number (integer or float)
 * data-format — one of: int | pct | year | m1 | m2 | usd-k
 */
( function () {
	'use strict';

	// ── Easing — ease-out cubic ─────────────────────────────────────────────
	function easeOut( t ) {
		return 1 - Math.pow( 1 - t, 3 );
	}

	// ── Formatter ───────────────────────────────────────────────────────────
	function formatValue( value, fmt ) {
		var rounded = Math.round( value );
		switch ( fmt ) {
			case 'pct':
				return rounded + '%';
			case 'year':
				return rounded.toString();
			case 'm1':
				return ( value / 1e6 ).toFixed( 1 ) + 'M';
			case 'm2':
				return ( value / 1e6 ).toFixed( 2 ) + 'M';
			case 'usd-k':
				return '$' + Math.round( value / 1000 ) + 'K';
			case 'int':
			default:
				return rounded.toLocaleString( 'en-US' );
		}
	}

	// ── Animate a single counter element ────────────────────────────────────
	function animateCounter( el ) {
		var target   = parseFloat( el.getAttribute( 'data-count' ) || '0' );
		var fmt      = el.getAttribute( 'data-format' ) || 'int';
		var duration = 1400; // ms
		var start    = null;

		// Respect reduced-motion preference — show final value immediately.
		if ( window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
			el.textContent = formatValue( target, fmt );
			return;
		}

		function step( timestamp ) {
			if ( ! start ) { start = timestamp; }
			var elapsed  = timestamp - start;
			var progress = Math.min( elapsed / duration, 1 );
			var current  = target * easeOut( progress );
			el.textContent = formatValue( current, fmt );
			if ( progress < 1 ) {
				requestAnimationFrame( step );
			}
		}

		requestAnimationFrame( step );
	}

	// ── Wire up IntersectionObserver ────────────────────────────────────────
	var counters = document.querySelectorAll( '.tclas-msp-stat__value[data-count]' );
	if ( ! counters.length ) { return; }

	if ( 'IntersectionObserver' in window ) {
		var observer = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( entry.isIntersecting ) {
					animateCounter( entry.target );
					observer.unobserve( entry.target );
				}
			} );
		}, { threshold: 0.25 } );

		counters.forEach( function ( el ) {
			observer.observe( el );
		} );
	} else {
		// Fallback: set values immediately without animation.
		counters.forEach( function ( el ) {
			var target = parseFloat( el.getAttribute( 'data-count' ) || '0' );
			el.textContent = formatValue( target, el.getAttribute( 'data-format' ) || 'int' );
		} );
	}
} )();
