<?php
/**
 * Template Name: My Luxembourg Story
 *
 * Frontend profile edit page where members manage their profile photo, bio,
 * city, ancestral communes/surnames, travel log, social links, family info,
 * and per-field privacy settings.
 *
 * @package TCLAS
 */

get_header();

// ── Handle form POST ────────────────────────────────────────────────────────
$save_message = '';
$save_error   = '';

if ( tclas_is_member() && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
	if (
		isset( $_POST['tclas_story_nonce'] )
		&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tclas_story_nonce'] ) ), 'tclas_save_story_' . get_current_user_id() )
	) {
		$uid = get_current_user_id();

		// ── Bio ───────────────────────────────────────────────────────────────
		$bio_raw = sanitize_textarea_field( $_POST['tclas_bio'] ?? '' );
		update_user_meta( $uid, '_tclas_bio', mb_substr( $bio_raw, 0, 800 ) );

		// ── City ──────────────────────────────────────────────────────────────
		update_user_meta( $uid, '_tclas_city', sanitize_text_field( $_POST['tclas_city'] ?? '' ) );

		// ── Family names ──────────────────────────────────────────────────────
		$family_names = array_values( array_filter(
			array_map( 'sanitize_text_field', (array) ( $_POST['tclas_family_names'] ?? [] ) ),
			'strlen'
		) );
		update_user_meta( $uid, '_tclas_family_names', $family_names );

		// ── Has children ──────────────────────────────────────────────────────
		update_user_meta( $uid, '_tclas_has_children', ! empty( $_POST['tclas_has_children'] ) ? 1 : 0 );

		// ── Bierger (self-reported Luxembourg citizenship) ────────────────────
		update_user_meta( $uid, '_tclas_badge_bierger', ! empty( $_POST['tclas_badge_bierger'] ) ? 1 : 0 );

		// ── Travel log ────────────────────────────────────────────────────────
		$raw_months  = (array) ( $_POST['tclas_trip_month_year'] ?? [] );
		$raw_purpose = (array) ( $_POST['tclas_trip_purpose']    ?? [] );
		$raw_notes   = (array) ( $_POST['tclas_trip_notes']      ?? [] );
		$trips_to_save = [];
		foreach ( $raw_months as $i => $my ) {
			$my    = sanitize_text_field( $my );
			$notes = sanitize_textarea_field( $raw_notes[ $i ] ?? '' );
			if ( '' === $my && '' === $notes ) {
				continue;
			}
			if ( '' !== $my && ! preg_match( '/^\d{4}-\d{2}$/', $my ) ) {
				continue;
			}
			$trips_to_save[] = [
				'month_year' => $my,
				'purpose'    => sanitize_text_field( $raw_purpose[ $i ] ?? '' ),
				'notes'      => $notes,
			];
		}
		update_user_meta( $uid, '_tclas_trips', $trips_to_save );

		// ── Social URLs ───────────────────────────────────────────────────────
		$social_map = [
			'_tclas_facebook_url'  => [ 'post_key' => 'tclas_facebook_url',  'domain' => 'facebook.com' ],
			'_tclas_linkedin_url'  => [ 'post_key' => 'tclas_linkedin_url',  'domain' => 'linkedin.com' ],
			'_tclas_instagram_url' => [ 'post_key' => 'tclas_instagram_url', 'domain' => 'instagram.com' ],
		];
		foreach ( $social_map as $meta_key => $cfg ) {
			$raw_url = esc_url_raw( $_POST[ $cfg['post_key'] ] ?? '' );
			if ( '' === $raw_url || false !== strpos( $raw_url, $cfg['domain'] ) ) {
				update_user_meta( $uid, $meta_key, $raw_url );
			}
		}

		// ── Per-field privacy ─────────────────────────────────────────────────
		$allowed_fields = [ 'bio', 'city', 'ancestry', 'social', 'family' ];
		$allowed_vals   = [ 'members', 'hidden' ];
		$raw_privacy    = (array) ( $_POST['tclas_field_privacy'] ?? [] );
		$field_privacy  = [];
		foreach ( $allowed_fields as $f ) {
			$val              = sanitize_key( $raw_privacy[ $f ] ?? 'members' );
			$field_privacy[ $f ] = in_array( $val, $allowed_vals, true ) ? $val : 'members';
		}
		update_user_meta( $uid, '_tclas_field_privacy', $field_privacy );

		// ── Ancestral communes + surnames (+ connection engine) ───────────────
		$communes = array_filter( (array) ( $_POST['tclas_communes'] ?? [] ), 'strlen' );
		$surnames = array_filter( (array) ( $_POST['tclas_surnames'] ?? [] ), 'strlen' );
		$visibility      = sanitize_text_field( $_POST['tclas_visibility']    ?? 'members' );
		$open_to_contact = ! empty( $_POST['tclas_open_to_contact'] );
		tclas_save_member_story( $uid, $communes, $surnames, $visibility, $open_to_contact );

		$count        = count( tclas_get_connections( $uid ) );
		$save_message = $count > 0
			? sprintf(
				_n(
					'Profile saved — and we found %d connection! Scroll down to see it.',
					'Profile saved — and we found %d connections! Scroll down to see them.',
					$count,
					'tclas'
				),
				$count
			)
			: esc_html__( 'Profile saved. As more members complete their profiles, connections will appear on your dashboard.', 'tclas' );
	} else {
		$save_error = esc_html__( 'Security check failed. Please try again.', 'tclas' );
	}
}

// ── Load current user data ──────────────────────────────────────────────────
$user_id       = get_current_user_id();
$communes      = (array) ( get_user_meta( $user_id, '_tclas_communes_raw',  true ) ?: [] );
$surnames      = (array) ( get_user_meta( $user_id, '_tclas_surnames_raw',  true ) ?: [] );
$visibility    = get_user_meta( $user_id, '_tclas_visibility', true ) ?: 'members';
$open_to_contact = (bool) get_user_meta( $user_id, '_tclas_open_to_contact', true );

while ( count( $communes ) < 2 ) { $communes[] = ''; }
while ( count( $surnames ) < 2 ) { $surnames[] = ''; }

$trips = (array) ( get_user_meta( $user_id, '_tclas_trips', true ) ?: [] );
if ( empty( $trips ) ) {
	$trips[] = [ 'month_year' => '', 'purpose' => '', 'notes' => '' ];
}

$bio           = (string) ( get_user_meta( $user_id, '_tclas_bio',           true ) ?: '' );
$city          = (string) ( get_user_meta( $user_id, '_tclas_city',          true ) ?: '' );
$facebook_url  = (string) ( get_user_meta( $user_id, '_tclas_facebook_url',  true ) ?: '' );
$linkedin_url  = (string) ( get_user_meta( $user_id, '_tclas_linkedin_url',  true ) ?: '' );
$instagram_url = (string) ( get_user_meta( $user_id, '_tclas_instagram_url', true ) ?: '' );
$family_names  = (array)  ( get_user_meta( $user_id, '_tclas_family_names',  true ) ?: [] );
$has_children  = (bool)     get_user_meta( $user_id, '_tclas_has_children',  true );
$is_bierger    = (bool)     get_user_meta( $user_id, '_tclas_badge_bierger', true );
$field_privacy = (array)  ( get_user_meta( $user_id, '_tclas_field_privacy', true ) ?: [] );

// Ensure at least one family name slot.
if ( empty( $family_names ) ) { $family_names[] = ''; }

// Helper: get current privacy setting for a field.
$fp = fn( string $field ): string => ( $field_privacy[ $field ] ?? 'members' );

// Photo.
$profile_photo_id  = (int) get_user_meta( $user_id, '_tclas_profile_photo', true );
$profile_photo_url = $profile_photo_id
	? (string) wp_get_attachment_image_url( $profile_photo_id, 'medium' )
	: '';
?>

<div class="tclas-page-header tclas-page-header--ardoise">
	<div class="container-tclas">
		<span class="tclas-eyebrow tclas-eyebrow--light"><?php esc_html_e( 'Your profile', 'tclas' ); ?></span>
		<h1 class="tclas-page-header__title"><?php esc_html_e( 'My Luxembourg Story', 'tclas' ); ?></h1>
	</div>
</div>

<section class="tclas-section">
	<div class="container-tclas">

		<?php if ( ! tclas_is_member() ) : ?>

			<div class="tclas-member-gate">
				<?php tclas_illustration( 'member_gate_illustration', __( 'Member area', 'tclas' ), 'tclas-member-gate__illustration' ); ?>
				<h2 class="tclas-member-gate__title"><?php esc_html_e( 'Members only.', 'tclas' ); ?></h2>
				<p class="tclas-member-gate__desc">
					<?php esc_html_e( 'Your Luxembourg story is part of your TCLAS membership. Join or log in to add your ancestral communes and surnames.', 'tclas' ); ?>
				</p>
				<div class="tclas-member-gate__actions">
					<a href="<?php echo esc_url( home_url( '/join/' ) ); ?>" class="btn btn-primary">
						<?php esc_html_e( 'Join TCLAS', 'tclas' ); ?>
					</a>
					<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="btn btn-outline-ardoise">
						<?php esc_html_e( 'Log in', 'tclas' ); ?>
					</a>
				</div>
			</div>

		<?php else : ?>

			<div class="tclas-story-layout">

				<!-- ── Profile form ──────────────────────────────────────── -->
				<div class="tclas-story-form-col">

					<?php if ( $save_message ) : ?>
						<div class="tclas-alert tclas-alert--success" role="alert">
							<?php echo esc_html( $save_message ); ?>
						</div>
					<?php endif; ?>

					<?php if ( $save_error ) : ?>
						<div class="tclas-alert tclas-alert--error" role="alert">
							<?php echo esc_html( $save_error ); ?>
						</div>
					<?php endif; ?>

					<form
						id="tclas-my-story-form"
						class="tclas-my-story-form"
						method="post"
						action="<?php the_permalink(); ?>"
						novalidate
					>
						<?php wp_nonce_field( 'tclas_save_story_' . $user_id, 'tclas_story_nonce' ); ?>

						<!-- ── About me ─────────────────────────────────────── -->
						<fieldset class="tclas-story-fieldset">
							<legend class="tclas-story-legend">
								<?php esc_html_e( 'About me', 'tclas' ); ?>
							</legend>

							<!-- Photo upload -->
							<div class="tclas-photo-upload" id="tclas-photo-upload-widget">
								<div class="tclas-photo-upload__preview">
									<img
										src="<?php echo esc_url( $profile_photo_url ?: get_avatar_url( $user_id, [ 'size' => 150 ] ) ); ?>"
										alt="<?php esc_attr_e( 'Your profile photo', 'tclas' ); ?>"
										class="tclas-photo-upload__img"
										id="tclas-photo-preview"
									>
								</div>
								<div class="tclas-photo-upload__controls">
									<label for="tclas-photo-file" class="btn btn-sm btn-outline-ardoise tclas-photo-choose-btn">
										<?php echo $profile_photo_url
											? esc_html__( 'Change photo', 'tclas' )
											: esc_html__( 'Upload photo', 'tclas' ); ?>
									</label>
									<input
										type="file"
										id="tclas-photo-file"
										name="tclas_profile_photo"
										accept="image/jpeg,image/png,image/webp"
										class="sr-only"
									>
									<?php if ( $profile_photo_url ) : ?>
										<button
											type="button"
											class="btn btn-sm tclas-photo-remove-btn"
											id="tclas-photo-remove"
										><?php esc_html_e( 'Remove', 'tclas' ); ?></button>
									<?php endif; ?>
									<p class="tclas-story-hint"><?php esc_html_e( 'JPEG, PNG, or WebP — max 5 MB.', 'tclas' ); ?></p>
									<p class="tclas-photo-status" id="tclas-photo-status" aria-live="polite"></p>
								</div>
							</div>

							<!-- Bio -->
							<p class="tclas-story-hint tclas-story-hint--mt-lg">
								<?php esc_html_e( "A short introduction for other members — your Luxembourg connection, what you're researching, or what you love about Lëtzebuergesch culture. Max 800 characters.", 'tclas' ); ?>
							</p>
							<textarea
								name="tclas_bio"
								class="tclas-story-input"
								rows="4"
								maxlength="800"
								id="tclas-bio-field"
								placeholder="<?php esc_attr_e( 'My great-great-grandfather emigrated from Esch-sur-Alzette in 1882…', 'tclas' ); ?>"
							><?php echo esc_textarea( $bio ); ?></textarea>
							<p class="tclas-story-hint tclas-bio-counter" aria-live="polite">
								<span id="tclas-bio-chars"><?php echo (int) mb_strlen( $bio ); ?></span>/800
							</p>

							<!-- City -->
							<label class="tclas-story-social-label tclas-story-social-label--mt" for="tclas-city-field">
								<?php esc_html_e( 'City you currently live in', 'tclas' ); ?>
							</label>
							<input
								type="text"
								id="tclas-city-field"
								name="tclas_city"
								value="<?php echo esc_attr( $city ); ?>"
								class="tclas-story-input"
								placeholder="<?php esc_attr_e( 'e.g. Minneapolis, MN', 'tclas' ); ?>"
								autocomplete="address-level2"
							>

							<!-- Privacy toggle -->
							<?php tclas_story_privacy_toggle( 'bio', $fp( 'bio' ), __( 'bio and city', 'tclas' ) ); ?>

						</fieldset>

						<!-- ── Communes ─────────────────────────────────────── -->
						<fieldset class="tclas-story-fieldset">
							<legend class="tclas-story-legend">
								<?php esc_html_e( 'Ancestral communes', 'tclas' ); ?>
							</legend>
							<p class="tclas-story-hint">
								<?php esc_html_e( 'The Luxembourg communes (towns or villages) where your ancestors were born or lived. Try French, German, or Luxembourgish spellings — we\'ll match them automatically. e.g. "Clerf" or "Clervaux" are the same.', 'tclas' ); ?>
							</p>

							<div id="tclas-communes-list" class="tclas-repeater-list">
								<?php foreach ( $communes as $i => $commune ) : ?>
									<div class="tclas-repeater-row" data-index="<?php echo (int) $i; ?>">
										<input
											type="text"
											name="tclas_communes[]"
											value="<?php echo esc_attr( $commune ); ?>"
											class="tclas-story-input"
											placeholder="<?php esc_attr_e( 'e.g. Echternach', 'tclas' ); ?>"
											autocomplete="off"
											list="tclas-commune-options"
											aria-label="<?php esc_attr_e( 'Ancestral commune', 'tclas' ); ?>"
										>
										<?php if ( $i > 0 ) : ?>
											<button
												type="button"
												class="tclas-repeater-remove"
												aria-label="<?php esc_attr_e( 'Remove this commune', 'tclas' ); ?>"
											>×</button>
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
							</div>

							<button
								type="button"
								class="btn btn-sm btn-outline-ardoise tclas-repeater-add"
								data-target="tclas-communes-list"
								data-placeholder="<?php esc_attr_e( 'e.g. Remich', 'tclas' ); ?>"
								data-name="tclas_communes[]"
								data-list="tclas-commune-options"
							>
								<?php esc_html_e( '+ Add another commune', 'tclas' ); ?>
							</button>

							<?php tclas_story_privacy_toggle( 'ancestry', $fp( 'ancestry' ), __( 'ancestral communes and surnames', 'tclas' ) ); ?>

						</fieldset>

						<!-- ── Surnames ─────────────────────────────────────── -->
						<fieldset class="tclas-story-fieldset">
							<legend class="tclas-story-legend">
								<?php esc_html_e( 'Luxembourg surnames in your family tree', 'tclas' ); ?>
							</legend>
							<p class="tclas-story-hint">
								<?php esc_html_e( 'Include original spellings and any Americanised versions you know — e.g. both "Schmitt" and "Smith". We check known variants automatically, so one entry is often enough.', 'tclas' ); ?>
							</p>

							<div id="tclas-surnames-list" class="tclas-repeater-list">
								<?php foreach ( $surnames as $i => $surname ) : ?>
									<div class="tclas-repeater-row" data-index="<?php echo (int) $i; ?>">
										<input
											type="text"
											name="tclas_surnames[]"
											value="<?php echo esc_attr( $surname ); ?>"
											class="tclas-story-input"
											placeholder="<?php esc_attr_e( 'e.g. Kieffer', 'tclas' ); ?>"
											autocomplete="off"
											aria-label="<?php esc_attr_e( 'Luxembourg surname', 'tclas' ); ?>"
										>
										<?php if ( $i > 0 ) : ?>
											<button
												type="button"
												class="tclas-repeater-remove"
												aria-label="<?php esc_attr_e( 'Remove this surname', 'tclas' ); ?>"
											>×</button>
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
							</div>

							<button
								type="button"
								class="btn btn-sm btn-outline-ardoise tclas-repeater-add"
								data-target="tclas-surnames-list"
								data-placeholder="<?php esc_attr_e( 'e.g. Wagner', 'tclas' ); ?>"
								data-name="tclas_surnames[]"
							>
								<?php esc_html_e( '+ Add another surname', 'tclas' ); ?>
							</button>

							<p class="tclas-story-hint tclas-story-hint--mt">
								<?php esc_html_e( 'Ancestry privacy is shared with the communes section above.', 'tclas' ); ?>
							</p>

						</fieldset>

						<!-- ── Travel Log ───────────────────────────────────── -->
						<fieldset class="tclas-story-fieldset">
							<legend class="tclas-story-legend">
								<?php esc_html_e( 'My trips to Luxembourg', 'tclas' ); ?>
							</legend>
							<p class="tclas-story-hint">
								<?php esc_html_e( 'Record your visits to Luxembourg — when you went, why, and anything you\'d like to remember.', 'tclas' ); ?>
							</p>

							<div id="tclas-trips-list" class="tclas-trip-list">
								<?php
								$trip_purposes = [
									'heritage' => __( 'Heritage research',      'tclas' ),
									'family'   => __( 'Family visit',            'tclas' ),
									'tourism'  => __( 'Tourism / vacation',      'tclas' ),
									'tclas'    => __( 'TCLAS / society event',   'tclas' ),
									'business' => __( 'Business',                'tclas' ),
									'other'    => __( 'Other',                   'tclas' ),
								];
								foreach ( $trips as $i => $trip ) :
								?>
								<div class="tclas-trip-item">
									<div class="tclas-trip-fields">
										<div class="tclas-trip-field-group">
											<label class="tclas-trip-label" for="trip-month-<?php echo (int) $i; ?>">
												<?php esc_html_e( 'Month & Year', 'tclas' ); ?>
											</label>
											<input
												type="month"
												id="trip-month-<?php echo (int) $i; ?>"
												name="tclas_trip_month_year[]"
												value="<?php echo esc_attr( $trip['month_year'] ); ?>"
												class="tclas-story-input tclas-trip-month"
												min="1900-01"
												max="<?php echo esc_attr( gmdate( 'Y-m' ) ); ?>"
											>
										</div>
										<div class="tclas-trip-field-group">
											<label class="tclas-trip-label" for="trip-purpose-<?php echo (int) $i; ?>">
												<?php esc_html_e( 'Purpose', 'tclas' ); ?>
											</label>
											<select
												id="trip-purpose-<?php echo (int) $i; ?>"
												name="tclas_trip_purpose[]"
												class="tclas-story-input tclas-trip-purpose"
											>
												<option value=""><?php esc_html_e( '— select —', 'tclas' ); ?></option>
												<?php foreach ( $trip_purposes as $val => $label ) : ?>
												<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $trip['purpose'], $val ); ?>>
													<?php echo esc_html( $label ); ?>
												</option>
												<?php endforeach; ?>
											</select>
										</div>
									</div>
									<div class="tclas-trip-field-group tclas-trip-field-group--full">
										<label class="tclas-trip-label" for="trip-notes-<?php echo (int) $i; ?>">
											<?php esc_html_e( 'Notes / highlights', 'tclas' ); ?>
										</label>
										<textarea
											id="trip-notes-<?php echo (int) $i; ?>"
											name="tclas_trip_notes[]"
											class="tclas-story-input tclas-trip-notes"
											rows="2"
											placeholder="<?php esc_attr_e( 'Villages visited, archives searched, relatives met…', 'tclas' ); ?>"
										><?php echo esc_textarea( $trip['notes'] ); ?></textarea>
									</div>
									<?php if ( $i > 0 ) : ?>
									<button
										type="button"
										class="tclas-trip-remove tclas-repeater-remove"
										aria-label="<?php esc_attr_e( 'Remove this trip', 'tclas' ); ?>"
									>×</button>
									<?php endif; ?>
								</div>
								<?php endforeach; ?>
							</div>

							<button type="button" id="tclas-trip-add" class="btn btn-sm btn-outline-ardoise">
								<?php esc_html_e( '+ Add another trip', 'tclas' ); ?>
							</button>
						</fieldset>

						<!-- ── Social profiles ──────────────────────────────── -->
						<fieldset class="tclas-story-fieldset">
							<legend class="tclas-story-legend">
								<?php esc_html_e( 'Social profiles', 'tclas' ); ?>
							</legend>
							<p class="tclas-story-hint">
								<?php esc_html_e( 'Shown on your profile page so other members can connect with you. Leave blank to hide.', 'tclas' ); ?>
							</p>

							<div class="tclas-story-social-group">
								<label class="tclas-story-social-label" for="tclas-facebook-url"><?php esc_html_e( 'Facebook', 'tclas' ); ?></label>
								<input
									type="url"
									id="tclas-facebook-url"
									name="tclas_facebook_url"
									value="<?php echo esc_attr( $facebook_url ); ?>"
									class="tclas-story-input"
									placeholder="https://www.facebook.com/yourname"
									autocomplete="url"
								>
							</div>

							<div class="tclas-story-social-group">
								<label class="tclas-story-social-label" for="tclas-linkedin-url"><?php esc_html_e( 'LinkedIn', 'tclas' ); ?></label>
								<input
									type="url"
									id="tclas-linkedin-url"
									name="tclas_linkedin_url"
									value="<?php echo esc_attr( $linkedin_url ); ?>"
									class="tclas-story-input"
									placeholder="https://www.linkedin.com/in/yourname"
									autocomplete="url"
								>
							</div>

							<div class="tclas-story-social-group">
								<label class="tclas-story-social-label" for="tclas-instagram-url"><?php esc_html_e( 'Instagram', 'tclas' ); ?></label>
								<input
									type="url"
									id="tclas-instagram-url"
									name="tclas_instagram_url"
									value="<?php echo esc_attr( $instagram_url ); ?>"
									class="tclas-story-input"
									placeholder="https://www.instagram.com/yourname"
									autocomplete="url"
								>
							</div>

							<?php tclas_story_privacy_toggle( 'social', $fp( 'social' ), __( 'social links', 'tclas' ) ); ?>

						</fieldset>

						<!-- ── Family ────────────────────────────────────────── -->
						<fieldset class="tclas-story-fieldset">
							<legend class="tclas-story-legend">
								<?php esc_html_e( 'Family membership', 'tclas' ); ?>
							</legend>
							<p class="tclas-story-hint">
								<?php esc_html_e( 'If your membership covers additional family members, you can list their names here. Only first names or full names — no information about minors.', 'tclas' ); ?>
							</p>

							<div id="tclas-family-names-list" class="tclas-repeater-list">
								<?php foreach ( $family_names as $i => $fname ) : ?>
									<div class="tclas-repeater-row" data-index="<?php echo (int) $i; ?>">
										<input
											type="text"
											name="tclas_family_names[]"
											value="<?php echo esc_attr( $fname ); ?>"
											class="tclas-story-input"
											placeholder="<?php esc_attr_e( 'e.g. Jane Smith', 'tclas' ); ?>"
											aria-label="<?php esc_attr_e( 'Family member name', 'tclas' ); ?>"
										>
										<?php if ( $i > 0 ) : ?>
											<button
												type="button"
												class="tclas-repeater-remove"
												aria-label="<?php esc_attr_e( 'Remove this name', 'tclas' ); ?>"
											>×</button>
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
							</div>

							<button
								type="button"
								class="btn btn-sm btn-outline-ardoise tclas-repeater-add"
								data-target="tclas-family-names-list"
								data-placeholder="<?php esc_attr_e( 'e.g. John Smith Jr.', 'tclas' ); ?>"
								data-name="tclas_family_names[]"
							>
								<?php esc_html_e( '+ Add family member', 'tclas' ); ?>
							</button>

							<div class="tclas-story-check-row tclas-story-check-row--mt">
								<label class="tclas-story-checkbox">
									<input
										type="checkbox"
										name="tclas_has_children"
										value="1"
										<?php checked( $has_children ); ?>
									>
									<?php esc_html_e( 'This membership includes children under 18 (no names displayed).', 'tclas' ); ?>
								</label>
							</div>

							<?php tclas_story_privacy_toggle( 'family', $fp( 'family' ), __( 'family member names', 'tclas' ) ); ?>

						</fieldset>

						<!-- ── Luxembourg Citizenship ────────────────────────── -->
						<fieldset class="tclas-story-fieldset">
							<legend class="tclas-story-legend"><?php esc_html_e( 'Luxembourg Citizenship', 'tclas' ); ?></legend>
							<p class="tclas-story-hint">
								<?php esc_html_e( 'Check this if you are a Luxembourg citizen. A Bierger badge will appear on your member profile.', 'tclas' ); ?>
							</p>
							<div class="tclas-story-check-row">
								<label class="tclas-story-checkbox">
									<input
										type="checkbox"
										name="tclas_badge_bierger"
										value="1"
										<?php checked( $is_bierger ); ?>
									>
									<?php esc_html_e( 'I am a Luxembourg citizen (Bierger/Biergesch).', 'tclas' ); ?>
								</label>
							</div>
						</fieldset>

						<!-- ── Privacy ──────────────────────────────────────── -->
						<fieldset class="tclas-story-fieldset">
							<legend class="tclas-story-legend"><?php esc_html_e( 'Directory visibility', 'tclas' ); ?></legend>
							<p class="tclas-story-hint">
								<?php esc_html_e( 'Controls whether you appear in the member directory and the ancestral map counts.', 'tclas' ); ?>
							</p>

							<div class="tclas-story-privacy-row">
								<label class="tclas-story-privacy-label"><?php esc_html_e( 'Who can see my profile?', 'tclas' ); ?></label>
								<div class="tclas-story-radio-group" role="radiogroup">
									<?php
									$vis_options = [
										'members' => __( 'All members', 'tclas' ),
										'board'   => __( 'Board only', 'tclas' ),
										'hidden'  => __( 'Hidden (opt out of directory)', 'tclas' ),
									];
									foreach ( $vis_options as $val => $label ) :
									?>
									<label class="tclas-story-radio">
										<input
											type="radio"
											name="tclas_visibility"
											value="<?php echo esc_attr( $val ); ?>"
											<?php checked( $visibility, $val ); ?>
										>
										<?php echo esc_html( $label ); ?>
									</label>
									<?php endforeach; ?>
								</div>
							</div>

							<div class="tclas-story-check-row">
								<label class="tclas-story-checkbox">
									<input
										type="checkbox"
										name="tclas_open_to_contact"
										value="1"
										<?php checked( $open_to_contact ); ?>
									>
									<?php esc_html_e( 'Allow matched members to send me a message through the hub.', 'tclas' ); ?>
								</label>
							</div>
						</fieldset>

						<div class="tclas-story-actions">
							<button type="submit" class="btn btn-primary">
								<?php esc_html_e( 'Save my story', 'tclas' ); ?>
							</button>
							<a href="<?php echo esc_url( home_url( '/member-hub/' ) ); ?>" class="btn btn-outline-ardoise">
								<?php esc_html_e( '← Back to hub', 'tclas' ); ?>
							</a>
						</div>

					</form>

				</div><!-- .tclas-story-form-col -->

				<!-- ── Sidebar: current connections ─────────────────────── -->
				<aside class="tclas-story-sidebar-col">
					<?php
					$connections = tclas_get_connections( $user_id );
					$visible     = array_filter( $connections, fn( $c ) => ! $c['dismissed'] );
					?>

					<?php if ( ! empty( $visible ) ) : ?>
						<div class="tclas-story-connections-preview">
							<h2 class="tclas-story-sidebar-title">
								<?php
								printf(
									esc_html( _n( 'Your %d connection', 'Your %d connections', count( $visible ), 'tclas' ) ),
									count( $visible )
								);
								?>
							</h2>
							<?php foreach ( array_slice( $visible, 0, 3, true ) as $other_id => $conn ) :
								$strength = tclas_connection_strength( $conn['score'] );
								?>
								<div class="tclas-conn-card tclas-conn-card--<?php echo esc_attr( $strength['class'] ); ?> tclas-conn-card--compact">
									<div class="tclas-conn-card__who">
										<?php echo get_avatar( $other_id, 32, '', '', [ 'class' => 'tclas-conn-card__avatar' ] ); ?>
										<strong><?php echo esc_html( get_the_author_meta( 'display_name', $other_id ) ); ?></strong>
									</div>
									<p class="tclas-conn-card__sentence">
										<?php echo tclas_connection_sentence( $user_id, (int) $other_id, $conn ); ?>
									</p>
								</div>
							<?php endforeach; ?>
							<?php if ( count( $visible ) > 3 ) : ?>
								<p class="tclas-story-connections-more">
									<a href="<?php echo esc_url( home_url( '/member-hub/' ) ); ?>">
										<?php
										printf(
											esc_html__( 'See all %d connections on your dashboard →', 'tclas' ),
											count( $visible )
										);
										?>
									</a>
								</p>
							<?php endif; ?>
						</div>
					<?php elseif ( get_user_meta( $user_id, '_tclas_profile_complete', true ) ) : ?>
						<div class="tclas-story-no-connections">
							<p class="tclas-story-hint">
								<?php esc_html_e( 'No connections found yet. As more members complete their profiles, we\'ll surface matches on your dashboard.', 'tclas' ); ?>
							</p>
							<p class="tclas-story-hint">
								<a href="<?php echo esc_url( home_url( '/forums/luxembourg-connections/' ) ); ?>">
									<?php esc_html_e( 'Visit the Luxembourg Connections forum →', 'tclas' ); ?>
								</a>
							</p>
						</div>
					<?php endif; ?>

					<div class="tclas-story-tip-box">
						<h3 class="tclas-story-tip-title">💡 <?php esc_html_e( 'Tips for better matches', 'tclas' ); ?></h3>
						<ul>
							<li><?php esc_html_e( 'Add all communes you know, even uncertain ones.', 'tclas' ); ?></li>
							<li><?php esc_html_e( 'Try both the French and German/Lëtzebuergesch spelling of a commune.', 'tclas' ); ?></li>
							<li><?php esc_html_e( 'Include original Luxembourg spellings of surnames — "Schmitt" will also match "Schmidt" and "Smith".', 'tclas' ); ?></li>
							<li><?php esc_html_e( 'Research as far back as you can — 4th-generation connections are still real connections.', 'tclas' ); ?></li>
						</ul>
					</div>

				</aside><!-- .tclas-story-sidebar-col -->

			</div><!-- .tclas-story-layout -->

		<?php endif; ?>

	</div><!-- .container-tclas -->
</section>

<!-- Datalist for commune autocomplete -->
<datalist id="tclas-commune-options">
	<?php foreach ( tclas_commune_labels() as $label ) : ?>
		<option value="<?php echo esc_attr( $label ); ?>">
	<?php endforeach; ?>
</datalist>

<?php get_footer(); ?>
