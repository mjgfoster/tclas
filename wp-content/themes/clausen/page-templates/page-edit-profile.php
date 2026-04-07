<?php
/**
 * Template Name: Edit Profile
 *
 * Frontend profile edit page where members manage their identity, bio, photo,
 * social links, family membership, and citizenship.
 *
 * Genealogy data is managed on the Ancestral Map screen.
 * Privacy settings are managed on the Privacy Settings screen.
 *
 * @package TCLAS
 */

get_header();

// ── Handle form POST ────────────────────────────────────────────────────────
$save_message = '';
$save_error   = '';

if ( tclas_is_member() && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
	if (
		isset( $_POST['tclas_profile_nonce'] )
		&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tclas_profile_nonce'] ) ), 'tclas_save_profile_' . get_current_user_id() )
	) {
		$uid = get_current_user_id();

		// ── Display name override ─────────────────────────────────────────
		update_user_meta( $uid, '_tclas_display_name_override', sanitize_text_field( $_POST['tclas_display_name_override'] ?? '' ) );

		// ── Bio (wysiwyg) ─────────────────────────────────────────────────
		$bio_raw = wp_kses_post( $_POST['tclas_bio'] ?? '' );
		// Enforce ~3000 char limit on the raw HTML.
		update_user_meta( $uid, '_tclas_bio', mb_substr( $bio_raw, 0, 5000 ) );

		// ── Current location ──────────────────────────────────────────────
		update_user_meta( $uid, '_tclas_city', sanitize_text_field( $_POST['tclas_city'] ?? '' ) );

		// ── Pronouns ──────────────────────────────────────────────────────
		update_user_meta( $uid, '_tclas_pronouns', sanitize_text_field( $_POST['tclas_pronouns'] ?? '' ) );

		// ── Citizenship tag ───────────────────────────────────────────────
		update_user_meta( $uid, '_tclas_citizenship_tag', sanitize_text_field( $_POST['tclas_citizenship_tag'] ?? '' ) );

		// ── Social URLs ───────────────────────────────────────────────────
		$social_fields = [
			'_tclas_facebook_url'   => 'tclas_facebook_url',
			'_tclas_instagram_url'  => 'tclas_instagram_url',
			'_tclas_linkedin_url'   => 'tclas_linkedin_url',
			'_tclas_pinterest_url'  => 'tclas_pinterest_url',
			'_tclas_ancestry_url'   => 'tclas_ancestry_url',
			'_tclas_familytree_url' => 'tclas_familytree_url',
		];
		foreach ( $social_fields as $meta_key => $post_key ) {
			$raw_url = esc_url_raw( $_POST[ $post_key ] ?? '' );
			update_user_meta( $uid, $meta_key, $raw_url );
		}

		// ── Bierger (self-reported Luxembourg citizenship) ────────────────
		update_user_meta( $uid, '_tclas_badge_bierger', ! empty( $_POST['tclas_badge_bierger'] ) ? 1 : 0 );

		// Bust directory cache so changes appear immediately.
		delete_transient( 'tclas_directory_members' );

		$save_message = __( 'Profile saved.', 'tclas' );
	} else {
		$save_error = __( 'Security check failed. Please try again.', 'tclas' );
	}
}

// ── Load current user data ──────────────────────────────────────────────────
$user_id = get_current_user_id();
$user    = get_userdata( $user_id );

$display_name_override = (string) ( get_user_meta( $user_id, '_tclas_display_name_override', true ) ?: '' );
$bio                   = (string) ( get_user_meta( $user_id, '_tclas_bio',                   true ) ?: '' );
$city                  = (string) ( get_user_meta( $user_id, '_tclas_city',                  true ) ?: '' );
$pronouns              = (string) ( get_user_meta( $user_id, '_tclas_pronouns',              true ) ?: '' );
$citizenship_tag       = (string) ( get_user_meta( $user_id, '_tclas_citizenship_tag',       true ) ?: '' );
$facebook_url          = (string) ( get_user_meta( $user_id, '_tclas_facebook_url',          true ) ?: '' );
$instagram_url         = (string) ( get_user_meta( $user_id, '_tclas_instagram_url',         true ) ?: '' );
$linkedin_url          = (string) ( get_user_meta( $user_id, '_tclas_linkedin_url',          true ) ?: '' );
$pinterest_url         = (string) ( get_user_meta( $user_id, '_tclas_pinterest_url',         true ) ?: '' );
$ancestry_url          = (string) ( get_user_meta( $user_id, '_tclas_ancestry_url',          true ) ?: '' );
$familytree_url        = (string) ( get_user_meta( $user_id, '_tclas_familytree_url',        true ) ?: '' );
$is_bierger            = (bool)     get_user_meta( $user_id, '_tclas_badge_bierger',         true );

// Photo.
$profile_photo_id  = (int) get_user_meta( $user_id, '_tclas_profile_photo', true );
$profile_photo_url = $profile_photo_id
	? (string) wp_get_attachment_image_url( $profile_photo_id, 'medium' )
	: '';
?>

<div class="tclas-page-header">
	<div class="container-tclas">
		<?php tclas_breadcrumb( __( 'Edit Profile', 'tclas' ) ); ?>
		<h1 class="tclas-page-header__title"><?php esc_html_e( 'Edit Profile', 'tclas' ); ?></h1>
	</div>
</div>

<section class="tclas-section">
	<div class="container-tclas">

		<?php if ( ! tclas_is_member() ) : ?>

			<div class="tclas-member-gate">
				<?php tclas_illustration( 'member_gate_illustration', __( 'Member area', 'tclas' ), 'tclas-member-gate__illustration' ); ?>
				<h2 class="tclas-member-gate__title"><?php esc_html_e( 'Members only.', 'tclas' ); ?></h2>
				<p class="tclas-member-gate__desc">
					<?php esc_html_e( 'Your profile is part of your TCLAS membership. Join or log in to edit your profile.', 'tclas' ); ?>
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
						id="tclas-edit-profile-form"
						class="tclas-my-story-form"
						method="post"
						action="<?php the_permalink(); ?>"
						novalidate
					>
						<?php wp_nonce_field( 'tclas_save_profile_' . $user_id, 'tclas_profile_nonce' ); ?>

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

							<!-- Display name override -->
							<label class="tclas-story-social-label tclas-story-social-label--mt" for="tclas-display-name-field">
								<?php esc_html_e( 'Display name (optional)', 'tclas' ); ?>
							</label>
							<p class="tclas-story-hint">
								<?php
								printf(
									/* translators: %s: user's default name */
									esc_html__( 'If set, this replaces "%s" on your profile and in the directory.', 'tclas' ),
									esc_html( $user->first_name . ' ' . $user->last_name )
								);
								?>
							</p>
							<input
								type="text"
								id="tclas-display-name-field"
								name="tclas_display_name_override"
								value="<?php echo esc_attr( $display_name_override ); ?>"
								class="tclas-story-input"
								placeholder="<?php esc_attr_e( 'e.g. Katie M. Schmidt', 'tclas' ); ?>"
								maxlength="100"
							>

							<!-- Bio (wysiwyg) -->
							<label class="tclas-story-social-label tclas-story-social-label--mt">
								<?php esc_html_e( 'Bio', 'tclas' ); ?>
							</label>
							<p class="tclas-story-hint">
								<?php esc_html_e( "A short introduction for other members — your Luxembourg connection, what you're researching, or what you love about Lëtzebuergesch culture.", 'tclas' ); ?>
							</p>
							<?php
							wp_editor( $bio, 'tclas_bio', [
								'textarea_name' => 'tclas_bio',
								'media_buttons' => false,
								'textarea_rows' => 8,
								'teeny'         => true,
								'quicktags'     => [ 'buttons' => 'strong,em,link,ul,ol,li' ],
								'tinymce'       => [
									'toolbar1'      => 'bold,italic,link,bullist,numlist,undo,redo',
									'toolbar2'      => '',
									'block_formats' => 'Paragraph=p',
								],
							] );
							?>

							<!-- Current location -->
							<label class="tclas-story-social-label tclas-story-social-label--mt" for="tclas-city-field">
								<?php esc_html_e( 'Current location', 'tclas' ); ?>
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

							<!-- Pronouns -->
							<label class="tclas-story-social-label tclas-story-social-label--mt" for="tclas-pronouns-field">
								<?php esc_html_e( 'Pronouns (optional)', 'tclas' ); ?>
							</label>
							<input
								type="text"
								id="tclas-pronouns-field"
								name="tclas_pronouns"
								value="<?php echo esc_attr( $pronouns ); ?>"
								class="tclas-story-input"
								placeholder="<?php esc_attr_e( 'e.g. she/her, he/him, they/them', 'tclas' ); ?>"
								maxlength="50"
							>

							<!-- Citizenship tag -->
							<label class="tclas-story-social-label tclas-story-social-label--mt" for="tclas-citizenship-tag-field">
								<?php esc_html_e( 'Citizenship (optional)', 'tclas' ); ?>
							</label>
							<p class="tclas-story-hint">
								<?php esc_html_e( 'Shown on your profile if filled.', 'tclas' ); ?>
							</p>
							<input
								type="text"
								id="tclas-citizenship-tag-field"
								name="tclas_citizenship_tag"
								value="<?php echo esc_attr( $citizenship_tag ); ?>"
								class="tclas-story-input"
								placeholder="<?php esc_attr_e( 'e.g. US citizen, Dual US/Luxembourg', 'tclas' ); ?>"
								maxlength="100"
							>

						</fieldset>

						<!-- ── Social profiles ──────────────────────────────── -->
						<fieldset class="tclas-story-fieldset">
							<legend class="tclas-story-legend">
								<?php esc_html_e( 'Social profiles', 'tclas' ); ?>
							</legend>
							<p class="tclas-story-hint">
								<?php esc_html_e( 'Shown on your profile page so other members can connect with you. Leave blank to hide.', 'tclas' ); ?>
							</p>

							<?php
							$social_config = [
								[ 'id' => 'tclas-facebook-url',   'name' => 'tclas_facebook_url',   'label' => 'Facebook',    'value' => $facebook_url,  'placeholder' => 'https://www.facebook.com/yourname' ],
								[ 'id' => 'tclas-instagram-url',  'name' => 'tclas_instagram_url',  'label' => 'Instagram',   'value' => $instagram_url, 'placeholder' => 'https://www.instagram.com/yourname' ],
								[ 'id' => 'tclas-linkedin-url',   'name' => 'tclas_linkedin_url',   'label' => 'LinkedIn',    'value' => $linkedin_url,  'placeholder' => 'https://www.linkedin.com/in/yourname' ],
								[ 'id' => 'tclas-pinterest-url',  'name' => 'tclas_pinterest_url',  'label' => 'Pinterest',   'value' => $pinterest_url, 'placeholder' => 'https://www.pinterest.com/yourname' ],
								[ 'id' => 'tclas-ancestry-url',   'name' => 'tclas_ancestry_url',   'label' => 'Ancestry',    'value' => $ancestry_url,  'placeholder' => 'https://www.ancestry.com/family-tree/...' ],
								[ 'id' => 'tclas-familytree-url', 'name' => 'tclas_familytree_url', 'label' => 'Family Tree', 'value' => $familytree_url,'placeholder' => 'https://www.familysearch.org/tree/...' ],
							];
							foreach ( $social_config as $s ) :
							?>
							<div class="tclas-story-social-group">
								<label class="tclas-story-social-label" for="<?php echo esc_attr( $s['id'] ); ?>">
									<?php echo esc_html( $s['label'] ); ?>
								</label>
								<input
									type="url"
									id="<?php echo esc_attr( $s['id'] ); ?>"
									name="<?php echo esc_attr( $s['name'] ); ?>"
									value="<?php echo esc_attr( $s['value'] ); ?>"
									class="tclas-story-input"
									placeholder="<?php echo esc_attr( $s['placeholder'] ); ?>"
									autocomplete="url"
								>
							</div>
							<?php endforeach; ?>

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

						<div class="tclas-story-actions">
							<button type="submit" class="btn btn-primary">
								<?php esc_html_e( 'Save profile', 'tclas' ); ?>
							</button>
							<a href="<?php echo esc_url( home_url( '/member-hub/' ) ); ?>" class="btn btn-outline-ardoise">
								<?php esc_html_e( '← Back to hub', 'tclas' ); ?>
							</a>
						</div>

					</form>

				</div><!-- .tclas-story-form-col -->

				<!-- ── Sidebar ──────────────────────────────────────────── -->
				<aside class="tclas-story-sidebar-col">
					<div class="tclas-story-tip-box">
						<h3 class="tclas-story-tip-title"><?php esc_html_e( 'Your profile screens', 'tclas' ); ?></h3>
						<ul>
							<li><strong><?php esc_html_e( 'Edit Profile', 'tclas' ); ?></strong> — <?php esc_html_e( 'You are here', 'tclas' ); ?></li>
							<li><a href="<?php echo esc_url( home_url( '/member-hub/map-entries/' ) ); ?>"><?php esc_html_e( 'My Ancestral Map', 'tclas' ); ?></a> — <?php esc_html_e( 'Communes, surnames & trips', 'tclas' ); ?></li>
							<li><a href="<?php echo esc_url( home_url( '/member-hub/privacy/' ) ); ?>"><?php esc_html_e( 'Privacy Settings', 'tclas' ); ?></a> — <?php esc_html_e( 'Control what others see', 'tclas' ); ?></li>
						</ul>
					</div>

					<div class="tclas-story-tip-box">
						<h3 class="tclas-story-tip-title"><?php esc_html_e( 'Preview your profile', 'tclas' ); ?></h3>
						<p class="tclas-story-hint">
							<?php esc_html_e( 'See how your profile looks to other members.', 'tclas' ); ?>
						</p>
						<?php
						$profile_url = home_url( '/member-hub/profiles/' . rawurlencode( $user->user_nicename ) . '/' );
						?>
						<a href="<?php echo esc_url( $profile_url ); ?>" class="btn btn-sm btn-outline-ardoise">
							<?php esc_html_e( 'View my profile →', 'tclas' ); ?>
						</a>
					</div>
				</aside>

			</div><!-- .tclas-story-layout -->

		<?php endif; ?>

	</div><!-- .container-tclas -->
</section>

<?php get_footer(); ?>
