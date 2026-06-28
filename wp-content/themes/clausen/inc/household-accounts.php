<?php
/**
 * Household sub-accounts
 *
 * A Household-tier member (TCLAS_LEVEL_HOUSEHOLD) can invite up to 3 other
 * ADULT household members. Each invitee sets their own password on an
 * acceptance page and becomes a full member on a free "Household Member"
 * level (TCLAS_LEVEL_HOUSEHOLD_MEMBER) — their own profile, ancestral map and
 * directory listing. Sub-account access is governed by the owner's Household
 * status via a cascade (immediate hook + daily reconcile backstop), NOT by the
 * sub-account's own clock, so PMPro renewal/expiry emails never fire for them.
 *
 * Setup note: create a WP page titled "Join Household" with slug
 * "join-household" as a child of the Member Hub page, then assign template
 * page-templates/page-join-household.php. Flush permalinks (Settings →
 * Permalinks → Save) afterwards so /member-hub/join-household/{token}/ resolves.
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Max invited sub-accounts (owner + 3 = "up to four household members").
const TCLAS_HOUSEHOLD_MAX_SEATS   = 3;
// How long an invitation link stays valid.
const TCLAS_HOUSEHOLD_INVITE_TTL  = 14; // days

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 1 — Database table (pending invitations)
// ═══════════════════════════════════════════════════════════════════════════

define( 'TCLAS_HOUSEHOLD_INVITES_DB_VERSION', '1.0' );

/**
 * Return the invites table name.
 */
function tclas_household_invites_table(): string {
	global $wpdb;
	return $wpdb->prefix . 'tclas_household_invites';
}

/**
 * Create or update the invites table via dbDelta.
 */
function tclas_household_create_table(): void {
	if ( TCLAS_HOUSEHOLD_INVITES_DB_VERSION === get_option( 'tclas_household_invites_db_version', '' ) ) {
		return;
	}

	global $wpdb;
	$table   = tclas_household_invites_table();
	$charset = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE {$table} (
		id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		owner_id          BIGINT UNSIGNED NOT NULL,
		invitee_name      VARCHAR(190) NOT NULL DEFAULT '',
		invitee_email     VARCHAR(190) NOT NULL DEFAULT '',
		token_hash        CHAR(64) NOT NULL DEFAULT '',
		status            VARCHAR(20) NOT NULL DEFAULT 'pending',
		adult_attested_at DATETIME NULL DEFAULT NULL,
		created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		expires_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		accepted_user_id  BIGINT UNSIGNED NULL DEFAULT NULL,
		PRIMARY KEY  (id),
		KEY owner_id (owner_id),
		KEY token_hash (token_hash),
		KEY invitee_email (invitee_email)
	) {$charset};";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );

	update_option( 'tclas_household_invites_db_version', TCLAS_HOUSEHOLD_INVITES_DB_VERSION );
}
add_action( 'after_switch_theme', 'tclas_household_create_table' );
add_action( 'admin_init', 'tclas_household_create_table' );

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 2 — Link helpers (owner ⇄ children)
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Whether a user currently holds the Household (owner) level.
 */
function tclas_household_is_owner( int $user_id ): bool {
	if ( ! $user_id || ! function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
		return false;
	}
	$level = pmpro_getMembershipLevelForUser( $user_id );
	return $level && (int) $level->id === TCLAS_LEVEL_HOUSEHOLD;
}

/**
 * Active child user IDs for an owner.
 *
 * @return int[]
 */
function tclas_household_member_ids( int $owner_id ): array {
	return array_values( array_filter( array_map( 'intval', (array) ( get_user_meta( $owner_id, '_tclas_household_members', true ) ?: [] ) ) ) );
}

/**
 * Revoked-but-retained child user IDs for an owner (kept for clean restore).
 *
 * @return int[]
 */
function tclas_household_revoked_ids( int $owner_id ): array {
	return array_values( array_filter( array_map( 'intval', (array) ( get_user_meta( $owner_id, '_tclas_household_revoked_members', true ) ?: [] ) ) ) );
}

/**
 * Pending (unexpired) invitations for an owner.
 *
 * @return object[]
 */
function tclas_household_pending_invites( int $owner_id ): array {
	global $wpdb;
	$table = tclas_household_invites_table();
	return (array) $wpdb->get_results( $wpdb->prepare(
		"SELECT * FROM {$table} WHERE owner_id = %d AND status = 'pending' AND expires_at > %s ORDER BY created_at DESC",
		$owner_id,
		current_time( 'mysql' )
	) );
}

/**
 * Seats used = active children + pending invites. Authoritative count.
 */
function tclas_household_seats_used( int $owner_id ): int {
	return count( tclas_household_member_ids( $owner_id ) ) + count( tclas_household_pending_invites( $owner_id ) );
}

/**
 * Remaining seats (never negative).
 */
function tclas_household_seats_remaining( int $owner_id ): int {
	return max( 0, TCLAS_HOUSEHOLD_MAX_SEATS - tclas_household_seats_used( $owner_id ) );
}

/**
 * Whether the owner has an active Household membership right now.
 */
function tclas_household_owner_active( int $owner_id ): bool {
	global $wpdb;
	$row = $wpdb->get_var( $wpdb->prepare(
		"SELECT membership_id FROM {$wpdb->prefix}pmpro_memberships_users
		 WHERE user_id = %d AND status = 'active' AND membership_id = %d
		 LIMIT 1",
		$owner_id,
		TCLAS_LEVEL_HOUSEHOLD
	) );
	return ! empty( $row );
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 3 — Invitations (create, resend, cancel) + email
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Create an invitation and email the link.
 *
 * @return array{ok:bool,error:string}
 */
function tclas_household_create_invite( int $owner_id, string $name, string $email, bool $adult_attested ): array {
	$name  = sanitize_text_field( $name );
	$email = sanitize_email( $email );

	if ( ! tclas_household_is_owner( $owner_id ) ) {
		return [ 'ok' => false, 'error' => __( 'Only Household members can invite household members.', 'tclas' ) ];
	}
	if ( ! $adult_attested ) {
		return [ 'ok' => false, 'error' => __( 'Please confirm this household member is 18 or older.', 'tclas' ) ];
	}
	if ( ! is_email( $email ) ) {
		return [ 'ok' => false, 'error' => __( 'Please enter a valid email address.', 'tclas' ) ];
	}
	if ( '' === $name ) {
		return [ 'ok' => false, 'error' => __( 'Please enter the household member’s name.', 'tclas' ) ];
	}
	if ( tclas_household_seats_remaining( $owner_id ) < 1 ) {
		return [ 'ok' => false, 'error' => __( 'You have used all of your household member seats.', 'tclas' ) ];
	}
	if ( email_exists( $email ) ) {
		return [ 'ok' => false, 'error' => __( 'That email already has a TCLAS account.', 'tclas' ) ];
	}

	// Reject a duplicate pending invite to the same address.
	global $wpdb;
	$table = tclas_household_invites_table();
	$dupe  = $wpdb->get_var( $wpdb->prepare(
		"SELECT id FROM {$table} WHERE owner_id = %d AND invitee_email = %s AND status = 'pending' AND expires_at > %s LIMIT 1",
		$owner_id,
		$email,
		current_time( 'mysql' )
	) );
	if ( $dupe ) {
		return [ 'ok' => false, 'error' => __( 'You already have a pending invitation for that email.', 'tclas' ) ];
	}

	$raw_token = bin2hex( random_bytes( 32 ) );
	// Stored in site-local time to match the current_time('mysql') comparisons.
	$expires   = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) + TCLAS_HOUSEHOLD_INVITE_TTL * DAY_IN_SECONDS );

	$inserted = $wpdb->insert(
		$table,
		[
			'owner_id'          => $owner_id,
			'invitee_name'      => $name,
			'invitee_email'     => $email,
			'token_hash'        => hash( 'sha256', $raw_token ),
			'status'            => 'pending',
			'adult_attested_at' => current_time( 'mysql' ),
			'created_at'        => current_time( 'mysql' ),
			'expires_at'        => $expires,
		],
		[ '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
	);

	if ( ! $inserted ) {
		return [ 'ok' => false, 'error' => __( 'Could not create the invitation. Please try again.', 'tclas' ) ];
	}

	tclas_household_send_invite_email( $owner_id, $name, $email, $raw_token );

	return [ 'ok' => true, 'error' => '' ];
}

/**
 * Email the invitation link. wp_mail delivers live on Local — test with
 * +suffix Gmail aliases only.
 */
function tclas_household_send_invite_email( int $owner_id, string $name, string $email, string $raw_token ): void {
	$owner      = get_userdata( $owner_id );
	$owner_name = $owner ? ( $owner->display_name ?: $owner->user_email ) : __( 'A TCLAS member', 'tclas' );
	$accept_url = home_url( '/member-hub/join-household/' . $raw_token . '/' );

	$subject = sprintf(
		/* translators: %s: owner display name */
		__( '%s invited you to join their TCLAS household', 'tclas' ),
		$owner_name
	);

	$body = sprintf(
		/* translators: 1: invitee first name, 2: owner name, 3: accept URL, 4: number of days */
		__( "Hi %1\$s,\n\n%2\$s has invited you to join their household membership in the Twin Cities Luxembourg American Society. You'll get your own login, member profile, and a place on the ancestral map.\n\nAccept your invitation and set a password here:\n%3\$s\n\nThis link expires in %4\$d days.\n\n—\nTwin Cities Luxembourg American Society", 'tclas' ),
		$name,
		$owner_name,
		$accept_url,
		TCLAS_HOUSEHOLD_INVITE_TTL
	);

	wp_mail( $email, $subject, $body );
}

/**
 * Resend the email for a pending invite (rotates the token).
 *
 * @return array{ok:bool,error:string}
 */
function tclas_household_resend_invite( int $owner_id, int $invite_id ): array {
	global $wpdb;
	$table  = tclas_household_invites_table();
	$invite = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM {$table} WHERE id = %d AND owner_id = %d AND status = 'pending' LIMIT 1",
		$invite_id,
		$owner_id
	) );
	if ( ! $invite ) {
		return [ 'ok' => false, 'error' => __( 'That invitation could not be found.', 'tclas' ) ];
	}

	$raw_token = bin2hex( random_bytes( 32 ) );
	$wpdb->update(
		$table,
		[
			'token_hash' => hash( 'sha256', $raw_token ),
			'expires_at' => gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) + TCLAS_HOUSEHOLD_INVITE_TTL * DAY_IN_SECONDS ),
		],
		[ 'id' => $invite_id ],
		[ '%s', '%s' ],
		[ '%d' ]
	);

	tclas_household_send_invite_email( $owner_id, $invite->invitee_name, $invite->invitee_email, $raw_token );
	return [ 'ok' => true, 'error' => '' ];
}

/**
 * Cancel a pending invite.
 *
 * @return array{ok:bool,error:string}
 */
function tclas_household_cancel_invite( int $owner_id, int $invite_id ): array {
	global $wpdb;
	$table   = tclas_household_invites_table();
	$updated = $wpdb->update(
		$table,
		[ 'status' => 'revoked' ],
		[ 'id' => $invite_id, 'owner_id' => $owner_id, 'status' => 'pending' ],
		[ '%s' ],
		[ '%d', '%d', '%s' ]
	);
	if ( ! $updated ) {
		return [ 'ok' => false, 'error' => __( 'That invitation could not be cancelled.', 'tclas' ) ];
	}
	return [ 'ok' => true, 'error' => '' ];
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 4 — Acceptance (token lookup + account creation)
// ═══════════════════════════════════════════════════════════════════════════

add_action( 'init', 'tclas_household_rewrite_rules' );
function tclas_household_rewrite_rules(): void {
	add_rewrite_rule(
		'^member-hub/join-household/([^/]+)/?$',
		'index.php?pagename=member-hub/join-household&tclas_household_token=$matches[1]',
		'top'
	);
}

add_filter( 'query_vars', 'tclas_household_query_vars' );
function tclas_household_query_vars( array $vars ): array {
	$vars[] = 'tclas_household_token';
	return $vars;
}

/**
 * Look up a pending, unexpired invite by raw token. Returns null on any miss
 * (caller must show a single generic message — never reveal which check failed).
 */
function tclas_household_invite_by_token( string $raw_token ): ?object {
	$raw_token = trim( $raw_token );
	if ( '' === $raw_token || ! ctype_xdigit( $raw_token ) ) {
		return null;
	}
	global $wpdb;
	$table  = tclas_household_invites_table();
	$invite = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM {$table} WHERE token_hash = %s AND status = 'pending' AND expires_at > %s LIMIT 1",
		hash( 'sha256', $raw_token ),
		current_time( 'mysql' )
	) );
	return $invite ?: null;
}

/**
 * Validate a chosen password to the same standard as pmpro-strong-passwords.
 *
 * @return array{ok:bool,message:string}
 */
function tclas_household_validate_password( string $password, string $username ): array {
	$min_len = (int) apply_filters( 'pmprosp_minimum_password_length', 12 );

	if ( strlen( $password ) < $min_len ) {
		return [ 'ok' => false, 'message' => sprintf(
			/* translators: %d: minimum length */
			__( 'Your password must be at least %d characters long.', 'tclas' ),
			$min_len
		) ];
	}
	if ( $password === $username ) {
		return [ 'ok' => false, 'message' => __( 'Your password must not match your email address.', 'tclas' ) ];
	}

	// Prefer the zxcvbn strength check the rest of the site uses.
	$zxcvbn_file = WP_PLUGIN_DIR . '/pmpro-strong-passwords/vendor/autoload.php';
	if ( is_readable( $zxcvbn_file ) ) {
		require_once $zxcvbn_file;
		if ( class_exists( '\ZxcvbnPhp\Zxcvbn' ) ) {
			$zxcvbn   = new \ZxcvbnPhp\Zxcvbn();
			$strength = $zxcvbn->passwordStrength( $password, [ $username ] );
			$min_score = (int) apply_filters( 'pmprosp_minimum_password_score', 2 );
			if ( isset( $strength['score'] ) && $strength['score'] <= $min_score ) {
				$suggestions = $strength['feedback']['suggestions'] ?? [];
				$hint = $suggestions ? implode( ' ', $suggestions ) : __( 'Please choose a stronger password.', 'tclas' );
				return [ 'ok' => false, 'message' => __( 'Your password is too weak.', 'tclas' ) . ' ' . $hint ];
			}
			return [ 'ok' => true, 'message' => '' ];
		}
	}

	// Fallback composition rules if zxcvbn is unavailable.
	if ( ! preg_match( '/[a-z]/', $password ) || ! preg_match( '/[A-Z]/', $password )
		|| ! preg_match( '/[0-9]/', $password ) || ! preg_match( '/[\W]/', $password ) ) {
		return [ 'ok' => false, 'message' => __( 'Use a mix of upper- and lower-case letters, a number, and a symbol.', 'tclas' ) ];
	}
	return [ 'ok' => true, 'message' => '' ];
}

/**
 * Accept an invitation: create the WP user, assign L5, link to the owner,
 * consume the invite, and log the new user in.
 *
 * @return array{ok:bool,error:string,user_id:int}
 */
function tclas_household_accept_invite( object $invite, string $password ): array {
	$email = sanitize_email( $invite->invitee_email );

	// Unique-email requirement (WP): an existing account can't become a sub-account.
	if ( email_exists( $email ) ) {
		return [ 'ok' => false, 'error' => __( 'This email already has an account. Please log in instead.', 'tclas' ), 'user_id' => 0 ];
	}

	$check = tclas_household_validate_password( $password, $email );
	if ( ! $check['ok'] ) {
		return [ 'ok' => false, 'error' => $check['message'], 'user_id' => 0 ];
	}

	// Re-check the owner still has room and active Household (guards a stale link).
	if ( ! tclas_household_owner_active( (int) $invite->owner_id ) ) {
		return [ 'ok' => false, 'error' => __( 'This household membership is no longer active.', 'tclas' ), 'user_id' => 0 ];
	}
	if ( tclas_household_seats_remaining( (int) $invite->owner_id ) < 1 ) {
		return [ 'ok' => false, 'error' => __( 'This household has no available seats.', 'tclas' ), 'user_id' => 0 ];
	}

	$user_id = wp_insert_user( [
		'user_login'   => $email, // email = username, matching the checkout convention.
		'user_email'   => $email,
		'user_pass'    => $password,
		'display_name' => $invite->invitee_name,
		'first_name'   => $invite->invitee_name,
		'role'         => 'subscriber',
	] );

	if ( is_wp_error( $user_id ) ) {
		return [ 'ok' => false, 'error' => __( 'Could not create your account. Please try again.', 'tclas' ), 'user_id' => 0 ];
	}

	// Free Household Member level — assigned directly, never through checkout.
	if ( function_exists( 'pmpro_changeMembershipLevel' ) ) {
		pmpro_changeMembershipLevel( TCLAS_LEVEL_HOUSEHOLD_MEMBER, $user_id );
	}

	tclas_household_link_member( (int) $invite->owner_id, (int) $user_id );

	// Consume the invite (single-use: any reuse now fails the status check).
	global $wpdb;
	$wpdb->update(
		tclas_household_invites_table(),
		[ 'status' => 'accepted', 'accepted_user_id' => $user_id ],
		[ 'id' => (int) $invite->id ],
		[ '%s', '%d' ],
		[ '%d' ]
	);

	delete_transient( 'tclas_directory_members' );

	wp_set_current_user( $user_id );
	wp_set_auth_cookie( $user_id, true );

	return [ 'ok' => true, 'error' => '', 'user_id' => (int) $user_id ];
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 5 — Linking + seat management
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Set the bidirectional owner ⇄ child link (active seat).
 */
function tclas_household_link_member( int $owner_id, int $child_id ): void {
	$members = tclas_household_member_ids( $owner_id );
	if ( ! in_array( $child_id, $members, true ) ) {
		$members[] = $child_id;
		update_user_meta( $owner_id, '_tclas_household_members', $members );
	}
	// If it was sitting in the revoked list, take it out.
	$revoked = array_values( array_diff( tclas_household_revoked_ids( $owner_id ), [ $child_id ] ) );
	update_user_meta( $owner_id, '_tclas_household_revoked_members', $revoked );

	update_user_meta( $child_id, '_tclas_household_parent', $owner_id );
}

/**
 * Revoke a child's access (cascade): cancel L5, move to the revoked list but
 * keep the link for a clean restore. Profile/lineage data is untouched.
 */
function tclas_household_revoke_member( int $owner_id, int $child_id ): void {
	if ( function_exists( 'pmpro_cancelMembershipLevel' ) ) {
		pmpro_cancelMembershipLevel( TCLAS_LEVEL_HOUSEHOLD_MEMBER, $child_id, 'inactive' );
	}
	$members = array_values( array_diff( tclas_household_member_ids( $owner_id ), [ $child_id ] ) );
	update_user_meta( $owner_id, '_tclas_household_members', $members );

	$revoked = tclas_household_revoked_ids( $owner_id );
	if ( ! in_array( $child_id, $revoked, true ) ) {
		$revoked[] = $child_id;
		update_user_meta( $owner_id, '_tclas_household_revoked_members', $revoked );
	}
}

/**
 * Restore a previously-revoked child to active L5.
 */
function tclas_household_restore_member( int $owner_id, int $child_id ): void {
	if ( function_exists( 'pmpro_changeMembershipLevel' ) ) {
		pmpro_changeMembershipLevel( TCLAS_LEVEL_HOUSEHOLD_MEMBER, $child_id );
	}
	tclas_household_link_member( $owner_id, $child_id );
}

/**
 * Permanently remove a seat (owner-initiated): cancel L5, drop both links.
 * Frees the seat for a new invite. The user account + data are preserved.
 */
function tclas_household_remove_member( int $owner_id, int $child_id ): void {
	if ( function_exists( 'pmpro_cancelMembershipLevel' ) ) {
		pmpro_cancelMembershipLevel( TCLAS_LEVEL_HOUSEHOLD_MEMBER, $child_id, 'inactive' );
	}
	update_user_meta( $owner_id, '_tclas_household_members', array_values( array_diff( tclas_household_member_ids( $owner_id ), [ $child_id ] ) ) );
	update_user_meta( $owner_id, '_tclas_household_revoked_members', array_values( array_diff( tclas_household_revoked_ids( $owner_id ), [ $child_id ] ) ) );
	delete_user_meta( $child_id, '_tclas_household_parent' );
	delete_transient( 'tclas_directory_members' );
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 6 — Lifecycle cascade
// ═══════════════════════════════════════════════════════════════════════════

/**
 * When an OWNER's level changes, cascade to their children. Cancelling a
 * child's level re-fires this hook for the child, so we no-op for children and
 * use an in-flight guard against recursion.
 */
function tclas_household_cascade_on_change( $level_id, $user_id, $cancel_level = null ): void {
	static $in_flight = false;

	$user_id  = (int) $user_id;
	$level_id = (int) $level_id;

	// Children never drive owner logic.
	if ( get_user_meta( $user_id, '_tclas_household_parent', true ) ) {
		return;
	}

	$members = tclas_household_member_ids( $user_id );
	$revoked = tclas_household_revoked_ids( $user_id );
	if ( empty( $members ) && empty( $revoked ) ) {
		return; // Not an owner.
	}

	if ( $in_flight ) {
		return;
	}
	$in_flight = true;

	if ( TCLAS_LEVEL_HOUSEHOLD === $level_id ) {
		// Renewed / re-upgraded: restore everyone we previously revoked.
		foreach ( $revoked as $child_id ) {
			tclas_household_restore_member( $user_id, $child_id );
		}
	} else {
		// Lost / changed / cancelled Household: revoke every active child.
		foreach ( $members as $child_id ) {
			tclas_household_revoke_member( $user_id, $child_id );
		}
	}

	delete_transient( 'tclas_directory_members' );
	$in_flight = false;
}
add_action( 'pmpro_after_change_membership_level', 'tclas_household_cascade_on_change', 10, 3 );

/**
 * Daily reconciliation backstop. Expirations run through PMPro's own cron and
 * may not emit pmpro_after_change_membership_level, so this is the correctness
 * guarantee: converge every owner's children to match the owner's status.
 */
function tclas_household_schedule_reconcile_cron(): void {
	if ( ! wp_next_scheduled( 'tclas_household_reconcile_cron' ) ) {
		$start = (int) wp_date( 'U', strtotime( 'tomorrow 03:00:00', current_datetime()->getTimestamp() ) );
		wp_schedule_event( $start, 'daily', 'tclas_household_reconcile_cron' );
	}
}
add_action( 'init', 'tclas_household_schedule_reconcile_cron' );

function tclas_household_run_reconcile(): void {
	$owners = get_users( [
		'fields'       => 'ID',
		'meta_key'     => '_tclas_household_members',
		'meta_compare' => 'EXISTS',
	] );
	// Owners whose active list is empty but who still hold revoked links.
	$with_revoked = get_users( [
		'fields'       => 'ID',
		'meta_key'     => '_tclas_household_revoked_members',
		'meta_compare' => 'EXISTS',
	] );
	$owners = array_unique( array_map( 'intval', array_merge( $owners, $with_revoked ) ) );

	foreach ( $owners as $owner_id ) {
		$active = tclas_household_owner_active( $owner_id );
		if ( $active ) {
			foreach ( tclas_household_revoked_ids( $owner_id ) as $child_id ) {
				tclas_household_restore_member( $owner_id, $child_id );
			}
		} else {
			foreach ( tclas_household_member_ids( $owner_id ) as $child_id ) {
				tclas_household_revoke_member( $owner_id, $child_id );
			}
		}
	}

	delete_transient( 'tclas_directory_members' );
}
add_action( 'tclas_household_reconcile_cron', 'tclas_household_run_reconcile' );

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 7 — Account deletion cleanup
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Keep links consistent when an account is deleted (fires before deletion, so
 * the user is still queryable).
 */
function tclas_household_on_delete_user( int $deleted_id ): void {
	global $wpdb;
	$table = tclas_household_invites_table();

	// If the deleted user is an OWNER: revoke all children, unlink, drop invites.
	$members = tclas_household_member_ids( $deleted_id );
	$revoked = tclas_household_revoked_ids( $deleted_id );
	if ( $members || $revoked ) {
		foreach ( array_unique( array_merge( $members, $revoked ) ) as $child_id ) {
			if ( function_exists( 'pmpro_cancelMembershipLevel' ) ) {
				pmpro_cancelMembershipLevel( TCLAS_LEVEL_HOUSEHOLD_MEMBER, $child_id, 'inactive' );
			}
			delete_user_meta( $child_id, '_tclas_household_parent' );
		}
		$wpdb->delete( $table, [ 'owner_id' => $deleted_id ], [ '%d' ] );
	}

	// If the deleted user is a CHILD: free the seat on the owner's lists.
	$parent_id = (int) get_user_meta( $deleted_id, '_tclas_household_parent', true );
	if ( $parent_id ) {
		update_user_meta( $parent_id, '_tclas_household_members', array_values( array_diff( tclas_household_member_ids( $parent_id ), [ $deleted_id ] ) ) );
		update_user_meta( $parent_id, '_tclas_household_revoked_members', array_values( array_diff( tclas_household_revoked_ids( $parent_id ), [ $deleted_id ] ) ) );
	}

	delete_transient( 'tclas_directory_members' );
}
add_action( 'delete_user', 'tclas_household_on_delete_user' );

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 8 — "Manage Household" panel + POST handler (member hub, Col 1)
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Handle the panel's POST actions (PRG: redirect back with a status flag).
 * Runs on the member-hub template only.
 */
function tclas_household_handle_post(): void {
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		return;
	}
	if ( ! isset( $_POST['tclas_household_action'] ) ) {
		return;
	}
	$owner_id = get_current_user_id();
	if ( ! $owner_id ) {
		return;
	}
	if ( ! isset( $_POST['tclas_household_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tclas_household_nonce'] ) ), 'tclas_household_' . $owner_id ) ) {
		return;
	}
	if ( ! tclas_household_is_owner( $owner_id ) ) {
		return;
	}

	$action = sanitize_key( wp_unslash( $_POST['tclas_household_action'] ) );
	$result = [ 'ok' => false, 'error' => __( 'Unknown action.', 'tclas' ) ];

	switch ( $action ) {
		case 'invite':
			$result = tclas_household_create_invite(
				$owner_id,
				sanitize_text_field( wp_unslash( $_POST['tclas_household_name'] ?? '' ) ),
				sanitize_email( wp_unslash( $_POST['tclas_household_email'] ?? '' ) ),
				! empty( $_POST['tclas_household_adult'] )
			);
			break;
		case 'resend':
			$result = tclas_household_resend_invite( $owner_id, (int) ( $_POST['tclas_household_invite_id'] ?? 0 ) );
			break;
		case 'cancel_invite':
			$result = tclas_household_cancel_invite( $owner_id, (int) ( $_POST['tclas_household_invite_id'] ?? 0 ) );
			break;
		case 'remove':
			$child_id = (int) ( $_POST['tclas_household_child_id'] ?? 0 );
			// Only allow removing one of this owner's own children.
			if ( $child_id && in_array( $child_id, tclas_household_member_ids( $owner_id ), true ) ) {
				tclas_household_remove_member( $owner_id, $child_id );
				$result = [ 'ok' => true, 'error' => '' ];
			} else {
				$result = [ 'ok' => false, 'error' => __( 'That household member could not be found.', 'tclas' ) ];
			}
			break;
	}

	$flag = $result['ok'] ? 'ok' : 'err';
	$args = [ 'household' => $flag . '_' . $action ];
	if ( ! $result['ok'] && ! empty( $result['error'] ) ) {
		set_transient( 'tclas_household_msg_' . $owner_id, $result['error'], 60 );
	}
	wp_safe_redirect( add_query_arg( $args, home_url( '/member-hub/' ) ) . '#tclas-household' );
	exit;
}
add_action( 'template_redirect', 'tclas_household_handle_post', 5 );

/**
 * Render the "Manage Household" panel. Call from the member-hub template
 * (Column 1) for Household owners.
 */
function tclas_household_panel( int $owner_id ): void {
	if ( ! tclas_household_is_owner( $owner_id ) ) {
		return;
	}

	$members   = tclas_household_member_ids( $owner_id );
	$pending   = tclas_household_pending_invites( $owner_id );
	$used      = count( $members ) + count( $pending );
	$remaining = max( 0, TCLAS_HOUSEHOLD_MAX_SEATS - $used );

	// Flash message (errors carried via transient; success via query flag).
	$notice = '';
	$is_err = false;
	if ( isset( $_GET['household'] ) ) {
		$flag = sanitize_key( wp_unslash( $_GET['household'] ) );
		if ( str_starts_with( $flag, 'ok_invite' ) ) {
			$notice = __( 'Invitation sent.', 'tclas' );
		} elseif ( str_starts_with( $flag, 'ok_resend' ) ) {
			$notice = __( 'Invitation re-sent.', 'tclas' );
		} elseif ( str_starts_with( $flag, 'ok_cancel_invite' ) ) {
			$notice = __( 'Invitation cancelled.', 'tclas' );
		} elseif ( str_starts_with( $flag, 'ok_remove' ) ) {
			$notice = __( 'Household member removed.', 'tclas' );
		} elseif ( str_starts_with( $flag, 'err' ) ) {
			$is_err = true;
			$notice = get_transient( 'tclas_household_msg_' . $owner_id ) ?: __( 'Something went wrong. Please try again.', 'tclas' );
			delete_transient( 'tclas_household_msg_' . $owner_id );
		}
	}
	?>
	<hr class="tclas-hub2-rule tclas-hub2-rule--big">
	<div id="tclas-household" class="tclas-hub2-household">
		<h4 class="tclas-hub2-household__title"><?php esc_html_e( 'Household members', 'tclas' ); ?></h4>
		<p class="tclas-hub2-household__count">
			<?php
			printf(
				/* translators: 1: seats used, 2: total seats */
				esc_html__( '%1$d of %2$d seats used', 'tclas' ),
				(int) $used,
				(int) TCLAS_HOUSEHOLD_MAX_SEATS
			);
			?>
		</p>

		<?php if ( $notice ) : ?>
			<div class="tclas-alert tclas-alert--<?php echo $is_err ? 'error' : 'success'; ?>" role="alert" style="margin:.5rem 0;">
				<?php echo esc_html( $notice ); ?>
			</div>
		<?php endif; ?>

		<?php if ( $members ) : ?>
			<ul class="tclas-hub2-household__list">
				<?php foreach ( $members as $child_id ) :
					$child = get_userdata( $child_id );
					if ( ! $child ) { continue; } ?>
					<li class="tclas-hub2-household__item">
						<span class="tclas-hub2-household__name"><?php echo esc_html( $child->display_name ?: $child->user_email ); ?></span>
						<form method="post" action="" class="tclas-hub2-household__inline" onsubmit="return confirm('<?php echo esc_js( __( 'Remove this household member? They will lose their membership access.', 'tclas' ) ); ?>');">
							<?php wp_nonce_field( 'tclas_household_' . $owner_id, 'tclas_household_nonce' ); ?>
							<input type="hidden" name="tclas_household_action" value="remove">
							<input type="hidden" name="tclas_household_child_id" value="<?php echo (int) $child_id; ?>">
							<button type="submit" class="tclas-hub2-link tclas-hub2-link--button"><?php esc_html_e( 'Remove', 'tclas' ); ?></button>
						</form>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( $pending ) : ?>
			<ul class="tclas-hub2-household__list tclas-hub2-household__list--pending">
				<?php foreach ( $pending as $invite ) : ?>
					<li class="tclas-hub2-household__item">
						<span class="tclas-hub2-household__name">
							<?php echo esc_html( $invite->invitee_name ); ?>
							<em class="tclas-hub2-household__pending">
								<?php
								printf(
									/* translators: %s: expiry date */
									esc_html__( 'pending — expires %s', 'tclas' ),
									esc_html( date_i18n( 'M j', strtotime( $invite->expires_at ) ) )
								);
								?>
							</em>
						</span>
						<span class="tclas-hub2-household__actions">
							<form method="post" action="" class="tclas-hub2-household__inline">
								<?php wp_nonce_field( 'tclas_household_' . $owner_id, 'tclas_household_nonce' ); ?>
								<input type="hidden" name="tclas_household_action" value="resend">
								<input type="hidden" name="tclas_household_invite_id" value="<?php echo (int) $invite->id; ?>">
								<button type="submit" class="tclas-hub2-link tclas-hub2-link--button"><?php esc_html_e( 'Resend', 'tclas' ); ?></button>
							</form>
							<form method="post" action="" class="tclas-hub2-household__inline">
								<?php wp_nonce_field( 'tclas_household_' . $owner_id, 'tclas_household_nonce' ); ?>
								<input type="hidden" name="tclas_household_action" value="cancel_invite">
								<input type="hidden" name="tclas_household_invite_id" value="<?php echo (int) $invite->id; ?>">
								<button type="submit" class="tclas-hub2-link tclas-hub2-link--button"><?php esc_html_e( 'Cancel', 'tclas' ); ?></button>
							</form>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( $remaining > 0 ) : ?>
			<form method="post" action="" class="tclas-hub2-household__invite">
				<?php wp_nonce_field( 'tclas_household_' . $owner_id, 'tclas_household_nonce' ); ?>
				<input type="hidden" name="tclas_household_action" value="invite">
				<p>
					<label for="tclas_household_name"><?php esc_html_e( 'Name', 'tclas' ); ?></label>
					<input type="text" id="tclas_household_name" name="tclas_household_name" required>
				</p>
				<p>
					<label for="tclas_household_email"><?php esc_html_e( 'Email', 'tclas' ); ?></label>
					<input type="email" id="tclas_household_email" name="tclas_household_email" required>
				</p>
				<p class="tclas-hub2-household__attest">
					<label>
						<input type="checkbox" name="tclas_household_adult" value="1" required>
						<?php esc_html_e( 'This household member is 18 or older.', 'tclas' ); ?>
					</label>
				</p>
				<button type="submit" class="btn btn-primary btn-sm"><?php esc_html_e( 'Send invitation', 'tclas' ); ?></button>
			</form>
		<?php else : ?>
			<p class="tclas-hub2-household__full"><?php esc_html_e( 'All household member seats are in use.', 'tclas' ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}
