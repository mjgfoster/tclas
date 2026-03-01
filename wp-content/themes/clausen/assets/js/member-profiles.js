/* global tclasProfiles */
/**
 * Member Profiles & Directory JS
 *
 * Handles:
 *  1. Profile photo upload / remove (My Story form)
 *  2. Directory search + filter (Profiles page)
 *  3. Per-field privacy toggle label sync (My Story form)
 */

( function () {
	'use strict';

	const cfg = window.tclasProfiles || {};
	const str = cfg.strings || {};

	// ── 1. Profile photo upload ─────────────────────────────────────────────

	const photoFile   = document.getElementById( 'tclas-photo-file' );
	const photoPreview = document.getElementById( 'tclas-photo-preview' );
	const photoStatus  = document.getElementById( 'tclas-photo-status' );
	const photoRemove  = document.getElementById( 'tclas-photo-remove' );
	const chooseBtn    = document.querySelector( '.tclas-photo-choose-btn' );

	if ( photoFile && photoPreview ) {

		photoFile.addEventListener( 'change', function () {
			const file = this.files[ 0 ];
			if ( ! file ) return;

			// Show local preview immediately.
			const reader = new FileReader();
			reader.onload = ( e ) => { photoPreview.src = e.target.result; };
			reader.readAsDataURL( file );

			// AJAX upload.
			setPhotoStatus( str.uploading || 'Uploading…', '' );

			const data = new FormData();
			data.append( 'action', 'tclas_upload_profile_photo' );
			data.append( 'nonce',  cfg.photoNonce );
			data.append( 'tclas_profile_photo', file );

			fetch( cfg.ajaxUrl, { method: 'POST', body: data } )
				.then( ( r ) => r.json() )
				.then( ( res ) => {
					if ( res.success ) {
						photoPreview.src = res.data.url;
						setPhotoStatus( '', '' );
						showRemoveBtn( true );
						if ( chooseBtn ) chooseBtn.textContent = str.changePhoto || 'Change photo';
					} else {
						const msg = res.data && res.data.message ? res.data.message : ( str.uploadError || 'Upload failed.' );
						setPhotoStatus( msg, 'error' );
					}
				} )
				.catch( () => setPhotoStatus( str.uploadError || 'Upload failed.', 'error' ) );

			// Reset input so the same file can be re-selected if needed.
			this.value = '';
		} );
	}

	if ( photoRemove ) {
		photoRemove.addEventListener( 'click', function () {
			const data = new FormData();
			data.append( 'action', 'tclas_remove_profile_photo' );
			data.append( 'nonce',  cfg.photoNonce );

			fetch( cfg.ajaxUrl, { method: 'POST', body: data } )
				.then( ( r ) => r.json() )
				.then( () => {
					// Fallback to gravatar: reload the src from the hidden data attribute
					// or just set to the default avatar (gravatar) — simplest is to reload.
					photoPreview.src = photoPreview.dataset.gravatar || photoPreview.src;
					showRemoveBtn( false );
					if ( chooseBtn ) chooseBtn.textContent = str.changePhoto ? str.changePhoto.replace( 'Change', 'Upload' ) : 'Upload photo';
					setPhotoStatus( '', '' );
				} );
		} );
	}

	function setPhotoStatus( msg, type ) {
		if ( ! photoStatus ) return;
		photoStatus.textContent = msg;
		photoStatus.className   = 'tclas-photo-status' + ( type ? ' tclas-photo-status--' + type : '' );
	}

	function showRemoveBtn( show ) {
		if ( ! photoRemove ) return;
		photoRemove.style.display = show ? '' : 'none';
	}

	// ── 2. Per-field privacy label sync ────────────────────────────────────

	document.querySelectorAll( '.tclas-field-privacy' ).forEach( function ( details ) {
		const currentLabel = details.querySelector( '.tclas-field-privacy__current' );
		if ( ! currentLabel ) return;

		details.querySelectorAll( 'input[type="radio"]' ).forEach( function ( radio ) {
			radio.addEventListener( 'change', function () {
				if ( this.checked ) {
					currentLabel.textContent = this.dataset.privacyLabel || this.value;
				}
			} );
		} );
	} );

	// ── 3. Directory search + filter ───────────────────────────────────────

	const grid      = document.getElementById( 'tclas-dir-grid' );
	const noResults = document.getElementById( 'tclas-dir-no-results' );
	const countEl   = document.getElementById( 'tclas-dir-count' );
	const searchEl  = document.getElementById( 'tclas-dir-search' );
	const cityEl    = document.getElementById( 'tclas-dir-city' );
	const ancestorsEl = document.getElementById( 'tclas-dir-ancestors' );

	if ( grid && ( searchEl || cityEl || ancestorsEl ) ) {
		const cards = Array.from( grid.querySelectorAll( '.tclas-dir-card' ) );

		function applyFilters() {
			const query     = searchEl     ? searchEl.value.trim().toLowerCase()     : '';
			const city      = cityEl       ? cityEl.value.trim().toLowerCase()       : '';
			const ancestors = ancestorsEl  ? ancestorsEl.checked                     : false;

			let visible = 0;
			cards.forEach( function ( card ) {
				const nameMatch     = ! query     || card.dataset.name.includes( query );
				const cityMatch     = ! city      || card.dataset.city === city;
				const ancestorMatch = ! ancestors || card.dataset.ancestors === '1';

				const show = nameMatch && cityMatch && ancestorMatch;
				card.hidden = ! show;
				if ( show ) visible++;
			} );

			// Update count label.
			if ( countEl ) {
				countEl.textContent = visible === 1
					? visible + ' member'
					: visible + ' members';
			}

			// Show / hide empty state.
			if ( noResults ) {
				noResults.hidden = visible > 0;
			}
		}

		if ( searchEl )    searchEl.addEventListener( 'input', applyFilters );
		if ( cityEl )      cityEl.addEventListener( 'change', applyFilters );
		if ( ancestorsEl ) ancestorsEl.addEventListener( 'change', applyFilters );
	}

} )();
