/**
 * TCLAS Hero Slideshow
 *
 * Drives the split-screen hero on the homepage.
 *
 * Desktop: MN slides DOWN (translateY), LUX slides UP.
 *   Each side has all slides stacked; only data-active="true" is visible.
 *   data-previous="true" stays at z-index 5 during the outgoing animation.
 *
 * Mobile: single crossfade inside .tclas-hero__background-mobile.
 *
 * Respects prefers-reduced-motion (opacity fade instead of translateY).
 * Pauses on mouse-enter and focusin; resumes on mouse-leave / focusout.
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

	// ── Brightness detection ───────────────────────────────────────────────
	// Samples a downscaled canvas of an img element and returns perceived
	// luminance (0 = black, 1 = white). Returns null on failure (CORS, etc).

	function sampleBrightness( imgEl ) {
		if ( ! imgEl || ! imgEl.complete || ! imgEl.naturalWidth ) return null;
		try {
			var canvas = document.createElement( 'canvas' );
			canvas.width  = 40;
			canvas.height = 40;
			var ctx = canvas.getContext( '2d' );
			ctx.drawImage( imgEl, 0, 0, 40, 40 );
			var data  = ctx.getImageData( 0, 0, 40, 40 ).data;
			var total = 0;
			var count = data.length / 4;
			for ( var i = 0; i < data.length; i += 4 ) {
				// Perceived luminance (ITU-R BT.601)
				total += 0.299 * data[ i ] + 0.587 * data[ i + 1 ] + 0.114 * data[ i + 2 ];
			}
			return total / count / 255; // normalise to 0–1
		} catch ( e ) {
			return null; // cross-origin or other error — fail silently
		}
	}

	function updateContrast( index ) {
		var hero = document.querySelector( '.tclas-hero' );
		if ( ! hero ) return;

		var slides      = getAllSlides( index );
		var brightnesses = [];
		slides.forEach( function ( slide ) {
			var img = slide.querySelector( 'img' );
			if ( img ) {
				var b = sampleBrightness( img );
				if ( b !== null ) brightnesses.push( b );
			}
		} );

		if ( ! brightnesses.length ) return;
		var avg = brightnesses.reduce( function ( a, b ) { return a + b; }, 0 ) / brightnesses.length;

		// Threshold: > 0.45 perceived luminance = treat as light background
		if ( avg > 0.45 ) {
			hero.classList.add( 'tclas-hero--light-bg' );
		} else {
			hero.classList.remove( 'tclas-hero--light-bg' );
		}
	}

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

		// Detect brightness of incoming slides; update contrast class.
		updateContrast( currentIndex );

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

		// Pause when tab is hidden (saves cycles, avoids queued transitions).
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

		// Count distinct pair indices from the left side (desktop).
		// Fall back to mobile slide count if no desktop sides exist.
		var leftSlides = document.querySelectorAll(
			'.tclas-hero__side--left .tclas-hero__image-slide'
		);
		if ( leftSlides.length ) {
			totalPairs = leftSlides.length;
		} else {
			var mobileSlides = document.querySelectorAll(
				'.tclas-hero__background-mobile .tclas-hero__image-slide'
			);
			totalPairs = mobileSlides.length;
		}

		if ( totalPairs <= 1 ) return; // Nothing to cycle.

		bindPause();
		start();

		// Run brightness check on first slide after images have a chance to load.
		var firstImgs = document.querySelectorAll( '.tclas-hero__image-slide[data-active="true"] img' );
		var pending = firstImgs.length;
		if ( ! pending ) return;
		firstImgs.forEach( function ( img ) {
			if ( img.complete ) {
				pending--;
				if ( pending === 0 ) updateContrast( 0 );
			} else {
				img.addEventListener( 'load', function () {
					pending--;
					if ( pending === 0 ) updateContrast( 0 );
				}, { once: true } );
			}
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

	window.addEventListener( 'beforeunload', stop );

} )();
