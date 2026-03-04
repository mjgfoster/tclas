/**
 * TCLAS Hero Greeting Sequence
 *
 * Bonjour. → Hello. → Moien.
 *
 * Each word fades in one at a time and stays visible (accumulates).
 * Opacity targets are controlled by CSS via data-stage + data-active.
 * CTAs are always visible — no animation needed.
 * Respects prefers-reduced-motion: shows all words immediately if set.
 */
( function () {
	'use strict';

	var STAGE_DWELL   = 900;  // ms between each word appearing
	var FADE_DURATION = 500;  // must match CSS transition duration

	function init() {
		var stages = document.querySelectorAll( '.tclas-hero__greeting-stage' );
		if ( ! stages.length ) return;

		var reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

		// Reduced motion: reveal all words immediately
		if ( reduced ) {
			stages.forEach( function ( s ) {
				s.setAttribute( 'data-active', 'true' );
			} );
			return;
		}

		var current = 0;

		function showNext() {
			if ( current >= stages.length ) return;
			stages[ current ].setAttribute( 'data-active', 'true' );
			current++;
			if ( current < stages.length ) {
				setTimeout( showNext, STAGE_DWELL );
			}
		}

		// Two rAF frames so the initial opacity:0 renders before the first
		// transition fires (avoids a flash-of-visible on fast connections).
		requestAnimationFrame( function () {
			requestAnimationFrame( showNext );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

} )();
