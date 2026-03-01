/**
 * TCLAS Hero Greeting Sequence
 *
 * Wëllkomm → Bienvenue → Welcome (with CTAs)
 *
 * Each stage dwells for STAGE_DWELL ms then fades out while the next fades in.
 * CTAs appear after the final stage completes its fade-in.
 * Respects prefers-reduced-motion — skips straight to stage 2 if set.
 */
( function () {
	'use strict';

	var STAGE_DWELL   = 1200; // ms each stage is fully visible before advancing
	var FADE_DURATION = 500;  // must match CSS transition duration

	function init() {
		var stages = document.querySelectorAll( '.tclas-hero__greeting-stage' );
		if ( ! stages.length ) return;

		var ctas    = document.querySelector( '.tclas-hero__greeting-ctas' );
		var reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

		function showCTAs() {
			if ( ! ctas ) return;
			ctas.removeAttribute( 'hidden' );
			// Two rAF frames to ensure display:none has cleared before transition fires
			requestAnimationFrame( function () {
				requestAnimationFrame( function () {
					ctas.classList.add( 'is-visible' );
				} );
			} );
		}

		// Reduced motion: jump straight to final stage, no transitions
		if ( reduced ) {
			stages.forEach( function ( s ) { s.removeAttribute( 'data-active' ); } );
			stages[ stages.length - 1 ].setAttribute( 'data-active', 'true' );
			showCTAs();
			return;
		}

		var current = 0;

		function advance() {
			if ( current >= stages.length - 1 ) return;
			stages[ current ].removeAttribute( 'data-active' );
			current++;
			stages[ current ].setAttribute( 'data-active', 'true' );

			if ( current === stages.length - 1 ) {
				// Final stage — reveal CTAs after its fade-in completes
				setTimeout( showCTAs, FADE_DURATION + 100 );
			} else {
				setTimeout( advance, STAGE_DWELL );
			}
		}

		setTimeout( advance, STAGE_DWELL );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

} )();
