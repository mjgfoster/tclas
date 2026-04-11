/**
 * TCLAS Hero Slideshow — Quadrant Layout
 *
 * Drives the photo-pair cycling in the four-quadrant hero.
 *
 * Desktop (≥768px):
 *   MN photos slide UP   (translateY 100% → 0)
 *   LUX photos slide DOWN (translateY -100% → 0)
 *
 * Mobile (<768px): opacity crossfade.
 *
 * Respects prefers-reduced-motion (opacity fade on all viewports).
 * Pauses on hover, focusin, and tab visibility change.
 */
( function () {
	'use strict';

	var SLIDE_INTERVAL   = 10000; // ms between advances
	var TRANSITION_MS    = 1800;  // must match CSS 1.8s
	var currentIndex     = 0;
	var totalPairs       = 0;
	var slideTimer       = null;
	var prevCleanupTimer = null;
	var reduced          = false;

	// ── Detect prefers-reduced-motion ─────────────────────────────────────
	var motionQuery = window.matchMedia( '(prefers-reduced-motion: reduce)' );
	reduced = motionQuery.matches;
	motionQuery.addEventListener( 'change', function ( e ) {
		reduced = e.matches;
	} );

	// ── Slide logic ────────────────────────────────────────────────────────

	function getAllSlides( index ) {
		return document.querySelectorAll(
			'.tclas-hero__image-slide[data-index="' + index + '"]'
		);
	}

	function advance() {
		var prevIndex = currentIndex;
		currentIndex = ( currentIndex + 1 ) % totalPairs;

		// Mark previous slides as leaving.
		var prevSlides = getAllSlides( prevIndex );
		prevSlides.forEach( function ( slide ) {
			slide.removeAttribute( 'data-active' );
			if ( ! reduced && window.innerWidth >= 768 ) {
				slide.setAttribute( 'data-previous', 'true' );
			}
		} );

		// Activate new slides.
		var nextSlides = getAllSlides( currentIndex );
		nextSlides.forEach( function ( slide ) {
			slide.setAttribute( 'data-active', 'true' );
		} );

		// Clean up data-previous after transition completes.
		if ( prevCleanupTimer ) clearTimeout( prevCleanupTimer );
		prevCleanupTimer = setTimeout( function () {
			prevSlides.forEach( function ( slide ) {
				slide.removeAttribute( 'data-previous' );
			} );
		}, TRANSITION_MS + 100 );
	}

	// ── Timer control ──────────────────────────────────────────────────────

	function start() {
		if ( totalPairs <= 1 ) return;
		if ( slideTimer ) return;
		slideTimer = setInterval( advance, SLIDE_INTERVAL );
	}

	function stop() {
		if ( slideTimer ) {
			clearInterval( slideTimer );
			slideTimer = null;
		}
	}

	// ── Interaction: pause on hover / focus / tab visibility ──────────────

	function bindPause() {
		var hero = document.querySelector( '.tclas-hero' );
		if ( ! hero ) return;
		hero.addEventListener( 'mouseenter', stop );
		hero.addEventListener( 'mouseleave', start );
		hero.addEventListener( 'focusin',    stop );
		hero.addEventListener( 'focusout',   start );

		document.addEventListener( 'visibilitychange', function () {
			if ( document.hidden ) {
				stop();
			} else {
				start();
			}
		} );
	}

	// ── Init ───────────────────────────────────────────────────────────────

	function init() {
		var hero = document.querySelector( '.tclas-hero' );
		if ( ! hero ) return;

		// Count distinct pair indices from the MN photo stack.
		var mnSlides = document.querySelectorAll(
			'.tclas-hero__photo-stack[data-side="minnesota"] .tclas-hero__image-slide'
		);
		if ( ! mnSlides.length ) {
			// Fallback: count from any photo stack present.
			mnSlides = document.querySelectorAll( '.tclas-hero__image-slide' );
		}
		totalPairs = mnSlides.length;

		if ( totalPairs <= 1 ) return;

		bindPause();
		start();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

	window.addEventListener( 'beforeunload', stop );

} )();
