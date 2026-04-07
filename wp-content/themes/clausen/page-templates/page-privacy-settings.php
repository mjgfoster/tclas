<?php
/**
 * Template Name: Privacy Settings
 *
 * Frontend privacy settings page where members control what is visible
 * to other members on their profile and in the directory.
 *
 * @package TCLAS
 */

get_header();

// ── Handle form POST ────────────────────────────────────────────────────────
$save_message = '';
$save_error   = '';

if ( tclas_is_member() && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
	if (
		isset( $_POST['tclas_privacy_nonce'] )
		&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tclas_privacy_nonce'] ) ), 'tclas_save_privacy_' . get_current_user_id() )
	) {
		$uid = get_current_user_id();

		// All toggles: checkbox present = ON (true), absent = OFF (false).
		$toggles = [
			'_tclas_privacy_show_in_directory'          => true,   // default ON
			'_tclas_privacy_show_bio'                   => true,   // default ON
			'_tclas_privacy_show_surnames'              => true,   // default ON
			'_tclas_privacy_show_communes'              => true,   // default ON
			'_tclas_privacy_allow_contact'              => true,   // default ON
			'_tclas_privacy_show_email'                 => false,  // default OFF
			'_tclas_privacy_show_phone'                 => false,  // default OFF
			'_tclas_privacy_in_community_stats'         => true,   // default ON
			'_tclas_privacy_allow_newsletter_feature'   => true,   // default ON
			'_tclas_privacy_weekly_digest'              => true,   // default ON
		];

		foreach ( $toggles as $meta_key => $default ) {
			$post_key = str_replace( '_tclas_', 'tclas_', $meta_key );
			$value    = ! empty( $_POST[ $post_key ] ) ? 1 : 0;
			update_user_meta( $uid, $meta_key, $value );
		}

		// ── Sync legacy privacy fields for backward compat ────────────────
		// Keep _tclas_field_privacy and _tclas_visibility in sync so existing
		// template code continues to work during transition.
		$field_privacy = [
			'bio'      => get_user_meta( $uid, '_tclas_privacy_show_bio', true ) ? 'members' : 'hidden',
			'city'     => get_user_meta( $uid, '_tclas_privacy_show_bio', true ) ? 'members' : 'hidden', // city shares bio privacy
			'ancestry' => ( get_user_meta( $uid, '_tclas_privacy_show_surnames', true ) || get_user_meta( $uid, '_tclas_privacy_show_communes', true ) ) ? 'members' : 'hidden',
			'social'   => 'members', // social links are always visible if filled; users control by leaving them empty
			'family'   => 'members', // family names always visible for now
		];
		update_user_meta( $uid, '_tclas_field_privacy', $field_privacy );

		$visibility = get_user_meta( $uid, '_tclas_privacy_show_in_directory', true ) ? 'members' : 'hidden';
		update_user_meta( $uid, '_tclas_visibility', $visibility );

		update_user_meta( $uid, '_tclas_open_to_contact', get_user_meta( $uid, '_tclas_privacy_allow_contact', true ) ? 1 : 0 );

		// Bust directory cache.
		delete_transient( 'tclas_directory_members' );

		$save_message = __( 'Privacy settings saved.', 'tclas' );
	} else {
		$save_error = __( 'Security check failed. Please try again.', 'tclas' );
	}
}

// ── Load current user data ──────────────────────────────────────────────────
$user_id = get_current_user_id();

/**
 * Helper to read a privacy toggle, with fallback to legacy fields for members
 * who haven't saved the new privacy screen yet.
 */
$get_toggle = function ( string $meta_key, bool $default ) use ( $user_id ): bool {
	$val = get_user_meta( $user_id, $meta_key, true );
	if ( '' === $val || false === $val ) {
		// Not set yet — derive from legacy fields if possible.
		$legacy_privacy = (array) ( get_user_meta( $user_id, '_tclas_field_privacy', true ) ?: [] );
		$legacy_vis     = get_user_meta( $user_id, '_tclas_visibility', true ) ?: 'members';

		switch ( $meta_key ) {
			case '_tclas_privacy_show_in_directory':
				return 'hidden' !== $legacy_vis;
			case '_tclas_privacy_show_bio':
				return ( $legacy_privacy['bio'] ?? 'members' ) !== 'hidden';
			case '_tclas_privacy_show_surnames':
			case '_tclas_privacy_show_communes':
				return ( $legacy_privacy['ancestry'] ?? 'members' ) !== 'hidden';
			case '_tclas_privacy_allow_contact':
				return (bool) get_user_meta( $user_id, '_tclas_open_to_contact', true );
			default:
				return $default;
		}
	}
	return (bool) $val;
};

$show_in_directory        = $get_toggle( '_tclas_privacy_show_in_directory',        true );
$show_bio                 = $get_toggle( '_tclas_privacy_show_bio',                 true );
$show_surnames            = $get_toggle( '_tclas_privacy_show_surnames',            true );
$show_communes            = $get_toggle( '_tclas_privacy_show_communes',            true );
$allow_contact            = $get_toggle( '_tclas_privacy_allow_contact',            true );
$show_email               = $get_toggle( '_tclas_privacy_show_email',               false );
$show_phone               = $get_toggle( '_tclas_privacy_show_phone',               false );
$in_community_stats       = $get_toggle( '_tclas_privacy_in_community_stats',       true );
$allow_newsletter_feature = $get_toggle( '_tclas_privacy_allow_newsletter_feature', true );
$weekly_digest            = $get_toggle( '_tclas_privacy_weekly_digest',            true );
?>

<div class="tclas-page-header">
	<div class="container-tclas">
		<?php tclas_breadcrumb( __( 'Privacy Settings', 'tclas' ) ); ?>
		<h1 class="tclas-page-header__title"><?php esc_html_e( 'Privacy Settings', 'tclas' ); ?></h1>
	</div>
</div>

<section class="tclas-section">
	<div class="container-tclas">

		<?php if ( ! tclas_is_member() ) : ?>

			<div class="tclas-member-gate">
				<?php tclas_illustration( 'member_gate_illustration', __( 'Member area', 'tclas' ), 'tclas-member-gate__illustration' ); ?>
				<h2 class="tclas-member-gate__title"><?php esc_html_e( 'Members only.', 'tclas' ); ?></h2>
				<p class="tclas-member-gate__desc">
					<?php esc_html_e( 'Privacy settings are part of your TCLAS membership. Join or log in to manage your settings.', 'tclas' ); ?>
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
						id="tclas-privacy-form"
						class="tclas-my-story-form"
						method="post"
						action="<?php the_permalink(); ?>"
						novalidate
					>
						<?php wp_nonce_field( 'tclas_save_privacy_' . $user_id, 'tclas_privacy_nonce' ); ?>

						<!-- ── Profile Visibility ───────────────────────────── -->
						<fieldset class="tclas-story-fieldset">
							<legend class="tclas-story-legend">
								<?php esc_html_e( 'Profile Visibility', 'tclas' ); ?>
							</legend>

							<div class="tclas-story-check-row">
								<label class="tclas-story-checkbox">
									<input
										type="checkbox"
										name="tclas_privacy_show_in_directory"
										value="1"
										<?php checked( $show_in_directory ); ?>
									>
									<?php esc_html_e( 'Show my profile in the member directory', 'tclas' ); ?>
								</label>
							</div>

							<div class="tclas-story-check-row">
								<label class="tclas-story-checkbox">
									<input
										type="checkbox"
										name="tclas_privacy_show_bio"
										value="1"
										<?php checked( $show_bio ); ?>
									>
									<?php esc_html_e( 'Allow other members to see my bio', 'tclas' ); ?>
								</label>
							</div>
						</fieldset>

						<!-- ── Genealogy Visibility ─────────────────────────── -->
						<fieldset class="tclas-story-fieldset">
							<legend class="tclas-story-legend">
								<?php esc_html_e( 'Genealogy Visibility', 'tclas' ); ?>
							</legend>
							<p class="tclas-story-hint">
								<?php esc_html_e( 'These are visible by default to help others discover shared ancestry.', 'tclas' ); ?>
							</p>

							<div class="tclas-story-check-row">
								<label class="tclas-story-checkbox">
									<input
										type="checkbox"
										name="tclas_privacy_show_surnames"
										value="1"
										<?php checked( $show_surnames ); ?>
									>
									<?php esc_html_e( 'Show my surnames to other members', 'tclas' ); ?>
								</label>
							</div>

							<div class="tclas-story-check-row">
								<label class="tclas-story-checkbox">
									<input
										type="checkbox"
										name="tclas_privacy_show_communes"
										value="1"
										<?php checked( $show_communes ); ?>
									>
									<?php esc_html_e( 'Show my ancestral communes to other members', 'tclas' ); ?>
								</label>
							</div>
						</fieldset>

						<!-- ── Contact & Communication ──────────────────────── -->
						<fieldset class="tclas-story-fieldset">
							<legend class="tclas-story-legend">
								<?php esc_html_e( 'Contact & Communication', 'tclas' ); ?>
							</legend>

							<div class="tclas-story-check-row">
								<label class="tclas-story-checkbox">
									<input
										type="checkbox"
										name="tclas_privacy_allow_contact"
										value="1"
										<?php checked( $allow_contact ); ?>
									>
									<?php esc_html_e( 'Allow members to contact me via the hub', 'tclas' ); ?>
								</label>
							</div>

							<div class="tclas-story-check-row">
								<label class="tclas-story-checkbox">
									<input
										type="checkbox"
										name="tclas_privacy_show_email"
										value="1"
										<?php checked( $show_email ); ?>
									>
									<?php esc_html_e( 'Show my email address publicly', 'tclas' ); ?>
								</label>
							</div>

							<div class="tclas-story-check-row">
								<label class="tclas-story-checkbox">
									<input
										type="checkbox"
										name="tclas_privacy_show_phone"
										value="1"
										<?php checked( $show_phone ); ?>
									>
									<?php esc_html_e( 'Show my phone number publicly', 'tclas' ); ?>
								</label>
							</div>
						</fieldset>

						<!-- ── Data & Participation ─────────────────────────── -->
						<fieldset class="tclas-story-fieldset">
							<legend class="tclas-story-legend">
								<?php esc_html_e( 'Data & Participation', 'tclas' ); ?>
							</legend>

							<div class="tclas-story-check-row">
								<label class="tclas-story-checkbox">
									<input
										type="checkbox"
										name="tclas_privacy_in_community_stats"
										value="1"
										<?php checked( $in_community_stats ); ?>
									>
									<?php esc_html_e( 'Include me in community statistics and maps', 'tclas' ); ?>
								</label>
							</div>

							<div class="tclas-story-check-row">
								<label class="tclas-story-checkbox">
									<input
										type="checkbox"
										name="tclas_privacy_allow_newsletter_feature"
										value="1"
										<?php checked( $allow_newsletter_feature ); ?>
									>
									<?php esc_html_e( 'Allow my profile to be featured in the newsletter', 'tclas' ); ?>
								</label>
								<p class="tclas-story-hint">
									<?php esc_html_e( 'If enabled, we may reach out to feature your story.', 'tclas' ); ?>
								</p>
							</div>

							<div class="tclas-story-check-row">
								<label class="tclas-story-checkbox">
									<input
										type="checkbox"
										name="tclas_privacy_weekly_digest"
										value="1"
										<?php checked( $weekly_digest ); ?>
									>
									<?php esc_html_e( 'Send me a weekly digest of unread activity', 'tclas' ); ?>
								</label>
								<p class="tclas-story-hint">
									<?php esc_html_e( 'A Monday summary of new messages, forum mentions, and member matches.', 'tclas' ); ?>
								</p>
							</div>
						</fieldset>

						<div class="tclas-story-actions">
							<button type="submit" class="btn btn-primary">
								<?php esc_html_e( 'Save privacy settings', 'tclas' ); ?>
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
							<li><a href="<?php echo esc_url( home_url( '/member-hub/edit-profile/' ) ); ?>"><?php esc_html_e( 'Edit Profile', 'tclas' ); ?></a> — <?php esc_html_e( 'Bio, photo & social', 'tclas' ); ?></li>
							<li><a href="<?php echo esc_url( home_url( '/member-hub/map-entries/' ) ); ?>"><?php esc_html_e( 'My Ancestral Map', 'tclas' ); ?></a> — <?php esc_html_e( 'Communes, surnames & trips', 'tclas' ); ?></li>
							<li><strong><?php esc_html_e( 'Privacy Settings', 'tclas' ); ?></strong> — <?php esc_html_e( 'You are here', 'tclas' ); ?></li>
						</ul>
					</div>

					<div class="tclas-story-tip-box">
						<h3 class="tclas-story-tip-title"><?php esc_html_e( 'About your privacy', 'tclas' ); ?></h3>
						<p class="tclas-story-hint">
							<?php esc_html_e( 'Your information is only visible to other TCLAS members, never to the public. These settings let you further control what fellow members can see.', 'tclas' ); ?>
						</p>
					</div>

				</aside>

			</div><!-- .tclas-story-layout -->

		<?php endif; ?>

	</div><!-- .container-tclas -->
</section>

<?php get_footer(); ?>
