/**
 * Cookie Consent Banner — TCLAS
 *
 * Lightweight GDPR/CCPA cookie consent with localStorage persistence.
 * Gates analytics and marketing scripts behind user consent.
 *
 * Consent states stored in localStorage as JSON:
 *   tclas_consent = { essential: true, analytics: bool, marketing: bool, timestamp: ISO }
 *
 * @package TCLAS
 */
( function () {
	'use strict';

	var STORAGE_KEY   = 'tclas_consent';
	var CONSENT_EVENT = 'tclas:consent';
	var EXPIRY_DAYS   = 365;

	// ── Helpers ───────────────────────────────────────────────────────────

	function getConsent() {
		try {
			var raw = localStorage.getItem( STORAGE_KEY );
			if ( ! raw ) return null;
			var data = JSON.parse( raw );
			// Check expiry
			if ( data.timestamp ) {
				var age = ( Date.now() - new Date( data.timestamp ).getTime() ) / 86400000;
				if ( age > EXPIRY_DAYS ) {
					localStorage.removeItem( STORAGE_KEY );
					return null;
				}
			}
			return data;
		} catch ( e ) {
			return null;
		}
	}

	function setConsent( analytics, marketing ) {
		var data = {
			essential: true,
			analytics: !! analytics,
			marketing: !! marketing,
			timestamp: new Date().toISOString()
		};
		localStorage.setItem( STORAGE_KEY, JSON.stringify( data ) );
		window.dispatchEvent( new CustomEvent( CONSENT_EVENT, { detail: data } ) );
		return data;
	}

	// ── Script loading ───────────────────────────────────────────────────

	function loadAnalytics() {
		// Google Analytics — only if gtag ID is provided by the theme
		if ( window.tclasConsent && window.tclasConsent.gaId ) {
			var id = window.tclasConsent.gaId;
			// Load gtag.js
			var s = document.createElement( 'script' );
			s.src = 'https://www.googletagmanager.com/gtag/js?id=' + id;
			s.async = true;
			document.head.appendChild( s );
			// Initialize
			window.dataLayer = window.dataLayer || [];
			function gtag() { window.dataLayer.push( arguments ); }
			window.gtag = gtag;
			gtag( 'js', new Date() );
			gtag( 'config', id, { anonymize_ip: true } );
		}
	}

	function loadMarketing() {
		// Brevo tracking — only if tracking key is provided
		if ( window.tclasConsent && window.tclasConsent.brevoKey ) {
			var s = document.createElement( 'script' );
			s.src = 'https://sibautomation.com/sa.js?key=' + window.tclasConsent.brevoKey;
			s.async = true;
			document.head.appendChild( s );
		}
	}

	function applyConsent( data ) {
		if ( data.analytics ) loadAnalytics();
		if ( data.marketing ) loadMarketing();
	}

	// ── Banner UI ────────────────────────────────────────────────────────

	function showBanner() {
		var banner = document.getElementById( 'tclas-consent-banner' );
		if ( banner ) banner.removeAttribute( 'hidden' );
	}

	function hideBanner() {
		var banner = document.getElementById( 'tclas-consent-banner' );
		if ( banner ) banner.setAttribute( 'hidden', '' );
	}

	function showPrefs() {
		var modal = document.getElementById( 'tclas-consent-prefs' );
		if ( ! modal ) return;
		// Populate toggles from current consent
		var data = getConsent() || { analytics: false, marketing: false };
		var analyticsToggle = modal.querySelector( '#tclas-pref-analytics' );
		var marketingToggle = modal.querySelector( '#tclas-pref-marketing' );
		if ( analyticsToggle ) analyticsToggle.checked = data.analytics;
		if ( marketingToggle ) marketingToggle.checked = data.marketing;
		modal.removeAttribute( 'hidden' );
		modal.querySelector( '.tclas-consent-prefs__panel' ).focus();
	}

	function hidePrefs() {
		var modal = document.getElementById( 'tclas-consent-prefs' );
		if ( modal ) modal.setAttribute( 'hidden', '' );
	}

	// ── Event bindings ───────────────────────────────────────────────────

	function bindBanner() {
		// Accept all
		document.addEventListener( 'click', function ( e ) {
			if ( e.target.closest( '#tclas-consent-accept' ) ) {
				var data = setConsent( true, true );
				hideBanner();
				applyConsent( data );
			}
		} );

		// Reject non-essential
		document.addEventListener( 'click', function ( e ) {
			if ( e.target.closest( '#tclas-consent-reject' ) ) {
				setConsent( false, false );
				hideBanner();
			}
		} );

		// Open preferences modal
		document.addEventListener( 'click', function ( e ) {
			if ( e.target.closest( '.tclas-consent-manage' ) ) {
				e.preventDefault();
				hideBanner();
				showPrefs();
			}
		} );

		// Save preferences
		document.addEventListener( 'click', function ( e ) {
			if ( e.target.closest( '#tclas-pref-save' ) ) {
				var modal     = document.getElementById( 'tclas-consent-prefs' );
				var analytics = modal.querySelector( '#tclas-pref-analytics' ).checked;
				var marketing = modal.querySelector( '#tclas-pref-marketing' ).checked;
				var data      = setConsent( analytics, marketing );
				hidePrefs();
				applyConsent( data );
			}
		} );

		// Close preferences modal
		document.addEventListener( 'click', function ( e ) {
			if ( e.target.closest( '#tclas-pref-close' ) ) {
				hidePrefs();
			}
		} );

		// Close modal on backdrop click
		document.addEventListener( 'click', function ( e ) {
			if ( e.target.id === 'tclas-consent-prefs' ) {
				hidePrefs();
			}
		} );

		// Close modal on Escape
		document.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' ) {
				var modal = document.getElementById( 'tclas-consent-prefs' );
				if ( modal && ! modal.hasAttribute( 'hidden' ) ) {
					hidePrefs();
				}
			}
		} );
	}

	// ── Init ─────────────────────────────────────────────────────────────

	function init() {
		bindBanner();

		var existing = getConsent();
		if ( existing ) {
			// Returning visitor — apply stored preferences silently
			applyConsent( existing );
		} else {
			// First visit — show the banner
			showBanner();
		}
	}

	// Run on DOMContentLoaded or immediately if already loaded
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
