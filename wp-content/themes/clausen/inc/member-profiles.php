<?php
/**
 * Member Profiles & Directory
 *
 * - Rewrite rule: /member-hub/profiles/{username}/ → individual profile
 * - Profile data helpers (founding member, photo, field privacy)
 * - Photo upload / remove AJAX handlers
 * - Admin founding-member flag field
 * - Asset enqueue (loaded on both page-my-story.php and page-member-profiles.php)
 *
 * Setup note: create a WP page titled "Profiles" with slug "profiles" as a child
 * of the Member Hub page, then assign template page-templates/page-member-profiles.php.
 * Flush permalinks (Settings → Permalinks → Save) after creating the page.
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 1 — Rewrite rules
// ═══════════════════════════════════════════════════════════════════════════

add_action( 'init', 'tclas_profiles_rewrite_rules' );
function tclas_profiles_rewrite_rules(): void {
	add_rewrite_rule(
		'^member-hub/profiles/([^/]+)/?$',
		'index.php?pagename=member-hub/profiles&tclas_profile_username=$matches[1]',
		'top'
	);
}

add_filter( 'query_vars', 'tclas_profiles_query_vars' );
function tclas_profiles_query_vars( array $vars ): array {
	$vars[] = 'tclas_profile_username';
	return $vars;
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 2 — Founding member
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Whether a user should display the Founding Member badge.
 * True if admin-set flag OR earliest active PMPro startdate is in 2026.
 */
function tclas_is_founding_member( int $user_id ): bool {
	if ( (bool) get_user_meta( $user_id, '_tclas_founding_member', true ) ) {
		return true;
	}
	if ( function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
		global $wpdb;
		$startdate = $wpdb->get_var( $wpdb->prepare(
			"SELECT startdate
			 FROM {$wpdb->prefix}pmpro_memberships_users
			 WHERE user_id = %d AND status = 'active'
			 ORDER BY startdate ASC LIMIT 1",
			$user_id
		) );
		if ( $startdate && '2026' === date( 'Y', strtotime( $startdate ) ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Return the year the user first became an active PMPro member, or ''.
 */
function tclas_get_member_since_year( int $user_id ): string {
	if ( ! function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
		return '';
	}
	global $wpdb;
	$startdate = $wpdb->get_var( $wpdb->prepare(
		"SELECT startdate
		 FROM {$wpdb->prefix}pmpro_memberships_users
		 WHERE user_id = %d AND status = 'active'
		 ORDER BY startdate ASC LIMIT 1",
		$user_id
	) );
	return $startdate ? date( 'Y', strtotime( $startdate ) ) : '';
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 3 — Profile photo
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Return the URL for a user's profile photo.
 * Priority: custom upload → Gravatar.
 *
 * @param string $size WP image size name (thumbnail = 150 px, medium = 300 px).
 */
function tclas_get_profile_photo_url( int $user_id, string $size = 'thumbnail' ): string {
	$att_id = (int) get_user_meta( $user_id, '_tclas_profile_photo', true );
	if ( $att_id ) {
		$url = wp_get_attachment_image_url( $att_id, $size );
		if ( $url ) {
			return $url;
		}
	}
	return get_avatar_url( $user_id, [ 'size' => 150 ] );
}

/**
 * Return an <img> tag for a user's profile photo.
 */
function tclas_get_profile_photo_img( int $user_id, string $size = 'thumbnail', string $extra_class = '' ): string {
	$base_class = 'tclas-profile-photo' . ( $extra_class ? ' ' . $extra_class : '' );
	$att_id     = (int) get_user_meta( $user_id, '_tclas_profile_photo', true );
	$user       = get_userdata( $user_id );
	$alt        = $user ? esc_attr( $user->display_name ) : '';

	if ( $att_id ) {
		return wp_get_attachment_image( $att_id, $size, false, [
			'class' => $base_class,
			'alt'   => $alt,
		] );
	}

	// Gravatar fallback.
	return '<img src="' . esc_url( get_avatar_url( $user_id, [ 'size' => 150 ] ) ) . '" '
		. 'alt="' . $alt . '" '
		. 'class="' . esc_attr( $base_class ) . '" '
		. 'loading="lazy">';
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 4 — Field privacy
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Whether a profile field is visible (not set to 'hidden' by the member).
 * Field keys: bio, city, ancestry, social, family.
 */
function tclas_profile_field_visible( int $user_id, string $field ): bool {
	$privacy = (array) ( get_user_meta( $user_id, '_tclas_field_privacy', true ) ?: [] );
	return ( $privacy[ $field ] ?? 'members' ) !== 'hidden';
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 5 — Profile data
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Return a complete profile data array for a user, with field privacy applied.
 * Hidden fields return empty strings / empty arrays — safe to pass to templates.
 */
function tclas_get_profile_data( int $user_id ): array {
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return [];
	}

	$data = [
		'user_id'      => $user_id,
		'username'     => $user->user_nicename,
		'display_name' => $user->display_name,
		'first_name'   => $user->first_name,
		'last_name'    => $user->last_name,
		'photo_url'    => tclas_get_profile_photo_url( $user_id ),
		'is_founding'  => tclas_is_founding_member( $user_id ),
		'member_since' => tclas_get_member_since_year( $user_id ),
		'has_bio'      => ! empty( get_user_meta( $user_id, '_tclas_bio', true ) ),
	];

	// Membership level from PMPro.
	if ( function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
		$level = pmpro_getMembershipLevelForUser( $user_id );
		$data['membership_level'] = $level ? $level->name : '';
	} else {
		$data['membership_level'] = '';
	}

	// Privacy-gated fields.
	$data['bio']  = tclas_profile_field_visible( $user_id, 'bio' )
		? (string) ( get_user_meta( $user_id, '_tclas_bio',  true ) ?: '' )
		: '';

	$data['city'] = tclas_profile_field_visible( $user_id, 'city' )
		? (string) ( get_user_meta( $user_id, '_tclas_city', true ) ?: '' )
		: '';

	$data['social'] = tclas_profile_field_visible( $user_id, 'social' )
		? [
			'facebook'  => (string) ( get_user_meta( $user_id, '_tclas_facebook_url',  true ) ?: '' ),
			'linkedin'  => (string) ( get_user_meta( $user_id, '_tclas_linkedin_url',  true ) ?: '' ),
			'instagram' => (string) ( get_user_meta( $user_id, '_tclas_instagram_url', true ) ?: '' ),
		]
		: [];

	$family_visible       = tclas_profile_field_visible( $user_id, 'family' );
	$data['family_names'] = $family_visible
		? array_values( array_filter( (array) ( get_user_meta( $user_id, '_tclas_family_names', true ) ?: [] ), 'strlen' ) )
		: [];
	$data['has_children'] = $family_visible
		? (bool) get_user_meta( $user_id, '_tclas_has_children', true )
		: false;

	// Ancestry (separate field privacy check).
	$data['communes_raw'] = tclas_profile_field_visible( $user_id, 'ancestry' )
		? (array) ( get_user_meta( $user_id, '_tclas_communes_raw', true ) ?: [] )
		: [];
	$data['surnames_raw'] = tclas_profile_field_visible( $user_id, 'ancestry' )
		? (array) ( get_user_meta( $user_id, '_tclas_surnames_raw', true ) ?: [] )
		: [];

	// Has-ancestors indicator: always computed (based on normalized slugs, independent of privacy).
	// This shows on directory cards even when ancestry field privacy is 'hidden'.
	$communes_norm        = (array) ( get_user_meta( $user_id, '_tclas_communes_norm', true ) ?: [] );
	$data['has_ancestors'] = ! empty( array_filter(
		$communes_norm,
		fn( $s ) => '' !== $s && ! str_starts_with( $s, 'unresolved:' )
	) );

	return $data;
}

/**
 * Return a lean array of all visible members for the directory, sorted by last name.
 * Only the fields needed for directory cards are fetched — full profile data is
 * loaded on-demand for individual profile views.
 */
function tclas_get_directory_members(): array {
	if ( ! function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
		return [];
	}

	global $wpdb;
	$ids = $wpdb->get_col(
		"SELECT DISTINCT user_id
		 FROM {$wpdb->prefix}pmpro_memberships_users
		 WHERE status = 'active'"
	);

	$members = [];
	foreach ( $ids as $raw_id ) {
		$id  = (int) $raw_id;
		$vis = get_user_meta( $id, '_tclas_visibility', true ) ?: 'members';
		if ( 'hidden' === $vis ) {
			continue;
		}
		$user = get_userdata( $id );
		if ( ! $user ) {
			continue;
		}

		$communes_norm = (array) ( get_user_meta( $id, '_tclas_communes_norm', true ) ?: [] );
		$members[] = [
			'user_id'      => $id,
			'username'     => $user->user_nicename,
			'display_name' => $user->display_name,
			'first_name'   => $user->first_name,
			'last_name'    => $user->last_name,
			'photo_url'    => tclas_get_profile_photo_url( $id, 'thumbnail' ),
			'city'         => tclas_profile_field_visible( $id, 'city' )
			                  ? (string) ( get_user_meta( $id, '_tclas_city', true ) ?: '' )
			                  : '',
			'is_founding'  => tclas_is_founding_member( $id ),
			'has_ancestors'=> ! empty( array_filter(
				$communes_norm,
				fn( $s ) => '' !== $s && ! str_starts_with( $s, 'unresolved:' )
			) ),
			'has_bio'      => ! empty( get_user_meta( $id, '_tclas_bio', true ) ),
		];
	}

	usort( $members, function( array $a, array $b ): int {
		$la = strtolower( $a['last_name'] ?: $a['display_name'] );
		$lb = strtolower( $b['last_name'] ?: $b['display_name'] );
		if ( $la !== $lb ) {
			return strcmp( $la, $lb );
		}
		return strcmp( strtolower( $a['first_name'] ), strtolower( $b['first_name'] ) );
	} );

	return $members;
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 6 — Form helper: per-field privacy toggle
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Render a collapsible "Who can see this?" privacy toggle for a form fieldset.
 *
 * @param string $field       Field key (bio, city, ancestry, social, family).
 * @param string $current     Current value ('members' or 'hidden').
 * @param string $field_label Human-readable name of the field, e.g. "bio and city".
 */
function tclas_story_privacy_toggle( string $field, string $current, string $field_label ): void {
	$label_visible = __( 'All members', 'tclas' );
	$label_hidden  = __( 'Hidden from other members', 'tclas' );
	$current_label = ( 'hidden' === $current ) ? $label_hidden : $label_visible;
	?>
	<details class="tclas-field-privacy" <?php echo ( 'hidden' === $current ) ? 'open' : ''; ?>>
		<summary class="tclas-field-privacy__toggle">
			<?php
			printf(
				/* translators: %s: field label e.g. "bio and city" */
				esc_html__( 'Who can see %s?', 'tclas' ),
				esc_html( $field_label )
			);
			?>
			<span class="tclas-field-privacy__current"><?php echo esc_html( $current_label ); ?></span>
		</summary>
		<div class="tclas-field-privacy__options">
			<label class="tclas-story-radio">
				<input
					type="radio"
					name="tclas_field_privacy[<?php echo esc_attr( $field ); ?>]"
					value="members"
					<?php checked( $current, 'members' ); ?>
					data-privacy-label="<?php echo esc_attr( $label_visible ); ?>"
				>
				<?php echo esc_html( $label_visible ); ?>
			</label>
			<label class="tclas-story-radio">
				<input
					type="radio"
					name="tclas_field_privacy[<?php echo esc_attr( $field ); ?>]"
					value="hidden"
					<?php checked( $current, 'hidden' ); ?>
					data-privacy-label="<?php echo esc_attr( $label_hidden ); ?>"
				>
				<?php echo esc_html( $label_hidden ); ?>
			</label>
		</div>
	</details>
	<?php
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 7 — Photo upload / remove AJAX
// ═══════════════════════════════════════════════════════════════════════════

add_action( 'wp_ajax_tclas_upload_profile_photo', 'tclas_ajax_upload_profile_photo' );
function tclas_ajax_upload_profile_photo(): void {
	check_ajax_referer( 'tclas_photo_upload', 'nonce' );

	$user_id = get_current_user_id();
	if ( ! $user_id || ! tclas_is_member() ) {
		wp_send_json_error( [ 'message' => __( 'Members only.', 'tclas' ) ] );
	}

	if ( empty( $_FILES['tclas_profile_photo'] ) || UPLOAD_ERR_OK !== (int) $_FILES['tclas_profile_photo']['error'] ) {
		wp_send_json_error( [ 'message' => __( 'No file received.', 'tclas' ) ] );
	}

	// Validate MIME type via extension.
	$allowed = [ 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp' ];
	$ext     = strtolower( pathinfo( $_FILES['tclas_profile_photo']['name'], PATHINFO_EXTENSION ) );
	if ( ! array_key_exists( $ext, $allowed ) ) {
		wp_send_json_error( [ 'message' => __( 'Please upload a JPEG, PNG, or WebP image.', 'tclas' ) ] );
	}

	// Enforce 5 MB size limit.
	if ( $_FILES['tclas_profile_photo']['size'] > 5 * 1024 * 1024 ) {
		wp_send_json_error( [ 'message' => __( 'Photo must be under 5 MB.', 'tclas' ) ] );
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';

	// Delete previous photo attachment to avoid orphaned media.
	$old_id = (int) get_user_meta( $user_id, '_tclas_profile_photo', true );
	if ( $old_id ) {
		wp_delete_attachment( $old_id, true );
	}

	$att_id = media_handle_upload( 'tclas_profile_photo', 0 );
	if ( is_wp_error( $att_id ) ) {
		wp_send_json_error( [ 'message' => $att_id->get_error_message() ] );
	}

	update_user_meta( $user_id, '_tclas_profile_photo', $att_id );

	wp_send_json_success( [
		'url' => tclas_get_profile_photo_url( $user_id, 'medium' ),
	] );
}

add_action( 'wp_ajax_tclas_remove_profile_photo', 'tclas_ajax_remove_profile_photo' );
function tclas_ajax_remove_profile_photo(): void {
	check_ajax_referer( 'tclas_photo_upload', 'nonce' );

	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		wp_send_json_error();
	}

	$old_id = (int) get_user_meta( $user_id, '_tclas_profile_photo', true );
	if ( $old_id ) {
		wp_delete_attachment( $old_id, true );
		delete_user_meta( $user_id, '_tclas_profile_photo' );
	}

	wp_send_json_success();
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 7 — Asset enqueue
// ═══════════════════════════════════════════════════════════════════════════

add_action( 'wp_enqueue_scripts', 'tclas_enqueue_profile_assets' );
function tclas_enqueue_profile_assets(): void {
	$on_profiles = is_page_template( 'page-templates/page-member-profiles.php' );
	$on_story    = is_page_template( 'page-templates/page-my-story.php' );

	if ( ! $on_profiles && ! $on_story ) {
		return;
	}

	wp_enqueue_style(
		'tclas-member-profiles',
		TCLAS_ASSETS . '/css/member-profiles.css',
		[ 'tclas-main' ],
		TCLAS_VERSION
	);

	wp_enqueue_script(
		'tclas-member-profiles',
		TCLAS_ASSETS . '/js/member-profiles.js',
		[ 'tclas-main' ],
		TCLAS_VERSION,
		true
	);

	wp_localize_script( 'tclas-member-profiles', 'tclasProfiles', [
		'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
		'photoNonce' => wp_create_nonce( 'tclas_photo_upload' ),
		'strings'    => [
			'uploading'   => __( 'Uploading…', 'tclas' ),
			'uploadError' => __( 'Upload failed. Please try again.', 'tclas' ),
			'removePhoto' => __( 'Remove photo', 'tclas' ),
			'changePhoto' => __( 'Change photo', 'tclas' ),
			'noResults'   => __( 'No members match your filters.', 'tclas' ),
		],
	] );
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 8 — Admin: founding member flag
// ═══════════════════════════════════════════════════════════════════════════

add_action( 'show_user_profile', 'tclas_admin_founding_member_field' );
add_action( 'edit_user_profile', 'tclas_admin_founding_member_field' );
function tclas_admin_founding_member_field( WP_User $user ): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$is_founding   = (bool) get_user_meta( $user->ID, '_tclas_founding_member', true );
	$year          = tclas_get_member_since_year( $user->ID );
	$auto_founding = ( '2026' === $year );
	?>
	<h2><?php esc_html_e( 'TCLAS Membership', 'tclas' ); ?></h2>
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Founding Member', 'tclas' ); ?></th>
			<td>
				<label>
					<input
						type="checkbox"
						name="tclas_founding_member"
						value="1"
						<?php checked( $is_founding ); ?>
					>
					<?php esc_html_e( 'Display Founding Member badge (admin override)', 'tclas' ); ?>
				</label>
				<p class="description">
					<?php if ( $auto_founding ) : ?>
						<?php esc_html_e( 'Badge already displays automatically — this member joined in 2026.', 'tclas' ); ?>
					<?php elseif ( $year ) : ?>
						<?php
						printf(
							/* translators: %s: year */
							esc_html__( 'Website join year: %s. Check to award Founding Member badge manually.', 'tclas' ),
							esc_html( $year )
						);
						?>
					<?php else : ?>
						<?php esc_html_e( 'No active PMPro membership found. Check to award badge manually.', 'tclas' ); ?>
					<?php endif; ?>
				</p>
			</td>
		</tr>
	</table>
	<?php wp_nonce_field( 'tclas_founding_' . $user->ID, 'tclas_founding_nonce' ); ?>
	<?php
}

add_action( 'personal_options_update',  'tclas_admin_save_founding_member' );
add_action( 'edit_user_profile_update', 'tclas_admin_save_founding_member' );
function tclas_admin_save_founding_member( int $user_id ): void {
	if ( ! isset( $_POST['tclas_founding_nonce'] )
		|| ! wp_verify_nonce(
			sanitize_text_field( wp_unslash( $_POST['tclas_founding_nonce'] ) ),
			'tclas_founding_' . $user_id
		)
	) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	update_user_meta( $user_id, '_tclas_founding_member', ! empty( $_POST['tclas_founding_member'] ) ? 1 : 0 );
}
