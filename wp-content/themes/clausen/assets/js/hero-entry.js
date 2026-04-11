/**
 * TCLAS Entry Animation (Welcome Sequence)
 *
 * Full-screen overlay:
 *   Phase 1 — Bonjour. → Hello. → Moien. (build in on ardoise)
 *   Phase 2 — Fade to or-pale, logo appears, then
 *             "vous souhaite la bienvenue" → "welcomes you" → "begréisst Iech"
 *   Phase 3 — Curtain opens to reveal page
 *
 * - Plays once per session (sessionStorage gate).
 * - Skippable via click, scroll, or Escape key.
 * - Respects prefers-reduced-motion: skipped entirely via inline <head> script.
 */
( function () {
	'use strict';

	var STORAGE_KEY      = 'tclas_entry_seen';
	var WORD_DELAY       = 600;  // ms between each greeting
	var MOIEN_HOLD       = 800;  // hold after last greeting before transition
	var FADE_OUT_MS      = 500;  // greetings fade-out duration
	var WELCOME_DELAY    = 450;  // ms between each welcome line
	var IDENTITY_HOLD    = 3000; // how long identity phase shows before curtain
	var CURTAIN_MS       = 800;  // must match CSS animation duration

	var overlay = document.getElementById( 'tclas-entry' );
	if ( ! overlay ) return;

	// If the inline <head> script already flagged skip, clean up the DOM.
	if ( document.documentElement.classList.contains( 'tclas-entry-skip' ) ) {
		overlay.remove();
		return;
	}

	// Prevent body scroll while overlay is visible.
	document.body.style.overflow = 'hidden';

	var words        = overlay.querySelectorAll( '.tclas-entry__word' );
	var identity     = overlay.querySelector( '.tclas-entry__identity' );
	var greetings    = overlay.querySelector( '.tclas-entry__greetings' );
	var welcomeLines = overlay.querySelectorAll( '.tclas-entry__welcome-line' );
	var dismissed    = false;
	var timers       = [];

	function later( fn, ms ) {
		var id = setTimeout( fn, ms );
		timers.push( id );
		return id;
	}

	// ── Dismiss logic ────────────────────────────────────────────────────

	function dismiss() {
		if ( dismissed ) return;
		dismissed = true;

		timers.forEach( function ( id ) { clearTimeout( id ); } );

		try { sessionStorage.setItem( STORAGE_KEY, '1' ); } catch ( e ) {}
		document.body.style.overflow = '';

		overlay.setAttribute( 'data-phase', 'reveal' );

		setTimeout( function () {
			overlay.remove();
		}, CURTAIN_MS + 50 );
	}

	overlay.addEventListener( 'click', dismiss );
	window.addEventListener( 'wheel', dismiss, { once: true, passive: true } );
	window.addEventListener( 'touchmove', dismiss, { once: true, passive: true } );
	document.addEventListener( 'keydown', function onKey( e ) {
		if ( e.key === 'Escape' ) {
			dismiss();
			document.removeEventListener( 'keydown', onKey );
		}
	} );

	// ── Phase 1: Greetings build in ──────────────────────────────────────

	var t = 0;

	for ( var i = 0; i < words.length; i++ ) {
		( function ( word, delay ) {
			later( function () {
				if ( dismissed ) return;
				word.setAttribute( 'data-visible', '' );
			}, delay );
		} )( words[ i ], t );
		t += WORD_DELAY;
	}

	// ── Phase 2: Transition to identity ──────────────────────────────────

	var fadeStart = t + MOIEN_HOLD;

	// Fade out greetings first, then shift background after they're gone.
	later( function () {
		if ( dismissed ) return;
		greetings.style.opacity = '0';
		greetings.style.transition = 'opacity ' + FADE_OUT_MS + 'ms ease';
	}, fadeStart );

	// Background shifts only after greetings are fully transparent.
	var bgShift = fadeStart + FADE_OUT_MS + 50;
	later( function () {
		if ( dismissed ) return;
		overlay.setAttribute( 'data-phase', 'identity' );
	}, bgShift );

	// Logo + identity container fades in after background has settled.
	var identityStart = bgShift + 500;
	later( function () {
		if ( dismissed ) return;
		identity.setAttribute( 'data-visible', '' );
		identity.setAttribute( 'aria-hidden', 'false' );
	}, identityStart );

	// Welcome lines build in with staggered delay (mirroring greetings).
	var welcomeStart = identityStart + 400; // slight pause after logo appears
	for ( var j = 0; j < welcomeLines.length; j++ ) {
		( function ( line, delay ) {
			later( function () {
				if ( dismissed ) return;
				line.setAttribute( 'data-visible', '' );
			}, delay );
		} )( welcomeLines[ j ], welcomeStart + j * WELCOME_DELAY );
	}

	// ── Phase 3: Auto-dismiss ────────────────────────────────────────────

	later( function () {
		dismiss();
	}, identityStart + IDENTITY_HOLD );

} )();
