/**
 * TCLAS checkout enhancements (single-page).
 *
 * - Email is the username (the username field is hidden, mirrored from email).
 * - Phone and country are hidden (US-only for now; country defaults to US).
 * - "State" becomes a dropdown of US states.
 * - Email is shown above password.
 * - Display name is built live from the first/last name fields.
 *
 * No step wizard — PMPro's form renders as a single page; this only adjusts
 * and enriches the existing fields.
 */
( function () {
	'use strict';

	function init() {
		var form = document.getElementById( 'pmpro_form' );
		if ( ! form ) { return; }

		// Hide fields we handle automatically / don't collect.
		[ 'username', 'bphone', 'bcountry' ].forEach( function ( name ) {
			var wrap = form.querySelector( '.pmpro_form_field-' + name );
			if ( wrap ) { wrap.style.display = 'none'; }
		} );

		var country = document.getElementById( 'bcountry' );
		if ( country && ! country.value ) { country.value = 'US'; }

		// Email → username.
		var email = document.getElementById( 'bemail' );
		var username = document.getElementById( 'username' );
		function syncUsername() {
			if ( email && username ) { username.value = email.value.trim(); }
		}
		if ( email ) {
			email.addEventListener( 'input', syncUsername );
			email.addEventListener( 'change', syncUsername );
		}
		form.addEventListener( 'submit', function () {
			syncUsername();
			syncDisplayName();
		} );

		// Show email above password in the account fieldset (guarded — field
		// wrappers aren't direct children of the fieldset).
		var userFields = document.getElementById( 'pmpro_user_fields' );
		if ( userFields ) {
			var emailWrap = userFields.querySelector( '.pmpro_form_field-bemail' );
			var confirmWrap = userFields.querySelector( '.pmpro_form_field-bconfirmemail' );
			var pwWrap = userFields.querySelector( '.pmpro_form_field-password' );
			try {
				if ( emailWrap && pwWrap && pwWrap.parentNode ) {
					pwWrap.parentNode.insertBefore( emailWrap, pwWrap );
					if ( confirmWrap ) { pwWrap.parentNode.insertBefore( confirmWrap, pwWrap ); }
				}
			} catch ( e ) {}
		}

		buildStateDropdown();
		buildDisplayNamePicker();
	}

	// ── US state dropdown ────────────────────────────────────────────────────

	var US_STATES = [
		[ 'AL', 'Alabama' ], [ 'AK', 'Alaska' ], [ 'AZ', 'Arizona' ], [ 'AR', 'Arkansas' ],
		[ 'CA', 'California' ], [ 'CO', 'Colorado' ], [ 'CT', 'Connecticut' ], [ 'DE', 'Delaware' ],
		[ 'DC', 'District of Columbia' ], [ 'FL', 'Florida' ], [ 'GA', 'Georgia' ], [ 'HI', 'Hawaii' ],
		[ 'ID', 'Idaho' ], [ 'IL', 'Illinois' ], [ 'IN', 'Indiana' ], [ 'IA', 'Iowa' ],
		[ 'KS', 'Kansas' ], [ 'KY', 'Kentucky' ], [ 'LA', 'Louisiana' ], [ 'ME', 'Maine' ],
		[ 'MD', 'Maryland' ], [ 'MA', 'Massachusetts' ], [ 'MI', 'Michigan' ], [ 'MN', 'Minnesota' ],
		[ 'MS', 'Mississippi' ], [ 'MO', 'Missouri' ], [ 'MT', 'Montana' ], [ 'NE', 'Nebraska' ],
		[ 'NV', 'Nevada' ], [ 'NH', 'New Hampshire' ], [ 'NJ', 'New Jersey' ], [ 'NM', 'New Mexico' ],
		[ 'NY', 'New York' ], [ 'NC', 'North Carolina' ], [ 'ND', 'North Dakota' ], [ 'OH', 'Ohio' ],
		[ 'OK', 'Oklahoma' ], [ 'OR', 'Oregon' ], [ 'PA', 'Pennsylvania' ], [ 'RI', 'Rhode Island' ],
		[ 'SC', 'South Carolina' ], [ 'SD', 'South Dakota' ], [ 'TN', 'Tennessee' ], [ 'TX', 'Texas' ],
		[ 'UT', 'Utah' ], [ 'VT', 'Vermont' ], [ 'VA', 'Virginia' ], [ 'WA', 'Washington' ],
		[ 'WV', 'West Virginia' ], [ 'WI', 'Wisconsin' ], [ 'WY', 'Wyoming' ]
	];

	function buildStateDropdown() {
		var input = document.getElementById( 'bstate' );
		if ( ! input || 'SELECT' === input.tagName ) { return; }

		var select = document.createElement( 'select' );
		select.id = 'bstate';
		select.name = 'bstate';
		select.className = input.className;
		if ( input.getAttribute( 'autocomplete' ) ) {
			select.setAttribute( 'autocomplete', input.getAttribute( 'autocomplete' ) );
		}

		var placeholder = document.createElement( 'option' );
		placeholder.value = '';
		placeholder.textContent = 'Select a state…';
		select.appendChild( placeholder );

		var current = ( input.value || '' ).trim().toLowerCase();
		US_STATES.forEach( function ( s ) {
			var opt = document.createElement( 'option' );
			opt.value = s[ 0 ];
			opt.textContent = s[ 1 ];
			if ( current && ( current === s[ 0 ].toLowerCase() || current === s[ 1 ].toLowerCase() ) ) {
				opt.selected = true;
			}
			select.appendChild( opt );
		} );

		input.parentNode.replaceChild( select, input );
	}

	// ── Display-name picker (built live from first/last) ─────────────────────

	var dnOptions, dnCustom, dnHidden;

	function syncDisplayName() {
		if ( ! dnHidden || ! dnOptions ) { return; }
		var sel = dnOptions.querySelector( 'input[name="tclas_dn_choice"]:checked' );
		if ( sel && '__custom__' === sel.value ) {
			dnHidden.value = dnCustom ? dnCustom.value.trim() : '';
		} else if ( sel ) {
			dnHidden.value = sel.value;
		} else {
			dnHidden.value = '';
		}
	}

	function onChoiceChange() {
		var sel = dnOptions.querySelector( 'input[name="tclas_dn_choice"]:checked' );
		var isCustom = sel && '__custom__' === sel.value;
		if ( dnCustom ) {
			dnCustom.hidden = ! isCustom;
			if ( isCustom ) { dnCustom.focus(); }
		}
		syncDisplayName();
	}

	function buildOptions() {
		if ( ! dnOptions ) { return; }
		var firstInput = document.getElementById( 'bfirstname' );
		var lastInput = document.getElementById( 'blastname' );
		var f = firstInput ? firstInput.value.trim() : '';
		var l = lastInput ? lastInput.value.trim() : '';
		var labels = [];
		if ( f && l ) { labels = [ f + ' ' + l, f + ' ' + l.charAt( 0 ) + '.', f ]; }
		else if ( f ) { labels = [ f ]; }
		else if ( l ) { labels = [ l ]; }

		var prev = dnHidden ? dnHidden.value : '';
		var customChecked = !! dnOptions.querySelector( 'input[value="__custom__"]:checked' );

		dnOptions.innerHTML = '';
		labels.forEach( function ( label, i ) {
			var lab = document.createElement( 'label' );
			lab.className = 'tclas-dn-option';
			var r = document.createElement( 'input' );
			r.type = 'radio';
			r.name = 'tclas_dn_choice';
			r.value = label;
			if ( ( prev && prev === label ) || ( ! prev && ! customChecked && 0 === i ) ) { r.checked = true; }
			lab.appendChild( r );
			lab.appendChild( document.createTextNode( ' ' + label ) );
			dnOptions.appendChild( lab );
		} );

		var labC = document.createElement( 'label' );
		labC.className = 'tclas-dn-option';
		var rc = document.createElement( 'input' );
		rc.type = 'radio';
		rc.name = 'tclas_dn_choice';
		rc.value = '__custom__';
		if ( customChecked ) { rc.checked = true; }
		labC.appendChild( rc );
		labC.appendChild( document.createTextNode( ' Something else' ) );
		dnOptions.appendChild( labC );

		dnOptions.querySelectorAll( 'input[name="tclas_dn_choice"]' ).forEach( function ( r ) {
			r.addEventListener( 'change', onChoiceChange );
		} );
		syncDisplayName();
	}

	function buildDisplayNamePicker() {
		dnOptions = document.querySelector( '[data-tclas-dn-options]' );
		dnCustom = document.getElementById( 'tclas_display_name_custom' );
		dnHidden = document.getElementById( 'tclas_display_name' );
		if ( ! dnOptions ) { return; }

		var firstInput = document.getElementById( 'bfirstname' );
		var lastInput = document.getElementById( 'blastname' );
		if ( dnCustom ) { dnCustom.addEventListener( 'input', syncDisplayName ); }
		if ( firstInput ) { firstInput.addEventListener( 'input', buildOptions ); }
		if ( lastInput ) { lastInput.addEventListener( 'input', buildOptions ); }
		buildOptions();
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
