<?php
/**
 * Keep PMPro membership state in sync with Brevo contact attributes
 *
 * Attributes written (created in Brevo 2026-08-05):
 *   MEMBER            boolean  currently holds any active level
 *   MEMBER_LEVEL      text     level name, e.g. "Household Member"
 *   MEMBER_SINCE      date     first join date, never moved backwards
 *   HOUSEHOLD_MEMBER  boolean  a household sub-account (level 5), not the payer
 *
 * Segments this makes possible in Brevo:
 *   everyone who is a member          MEMBER = true
 *   members, excluding sub-accounts   MEMBER = true AND HOUSEHOLD_MEMBER = false
 *   household sub-accounts only       HOUSEHOLD_MEMBER = true
 *
 * Attributes rather than a second list, deliberately: memberships lapse and renew,
 * and two lists would mean two things to keep in step with people drifting between
 * them. One list plus attributes keeps a single source of truth, and segments
 * re-evaluate themselves.
 *
 * Consent rules, which the whole module is built around:
 *   - We never CREATE a Brevo contact for someone who did not opt in. Membership is
 *     not consent to marketing email; see the checkout box in inc/pmpro-checkout.php.
 *   - We always UPDATE someone who is already a contact, so segments stay truthful.
 *   - We never un-blocklist. Someone who unsubscribed stays unsubscribed, even if
 *     they join, renew, or are added to a household.
 *   - A lapsed membership sets MEMBER = false but does NOT remove them from the list.
 *     Letting a membership expire is not a request to stop hearing from you.
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

const TCLAS_HOUSEHOLD_MEMBER_LEVEL = 5;              // "Household Member" sub-account
const TCLAS_OPTIN_META             = '_tclas_email_optin';
const TCLAS_MEMBER_SINCE_META      = '_tclas_member_since';

/**
 * Build the attribute set describing a user's current membership.
 *
 * @return array<string,mixed>
 */
function tclas_member_attributes( int $user_id ): array {
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return [];
	}

	$level     = function_exists( 'pmpro_getMembershipLevelForUser' ) ? pmpro_getMembershipLevelForUser( $user_id ) : null;
	$has_level = ! empty( $level->ID );

	// First-join date is sticky: renewals and level changes must not reset it.
	$since = get_user_meta( $user_id, TCLAS_MEMBER_SINCE_META, true );
	if ( ! $since && $has_level ) {
		$since = ! empty( $level->startdate )
			? gmdate( 'Y-m-d', (int) $level->startdate )
			: gmdate( 'Y-m-d', strtotime( $user->user_registered ) );
		update_user_meta( $user_id, TCLAS_MEMBER_SINCE_META, $since );
	}

	$attributes = [
		'MEMBER'           => $has_level,
		'MEMBER_LEVEL'     => $has_level ? (string) $level->name : '',
		'HOUSEHOLD_MEMBER' => $has_level && (int) $level->ID === TCLAS_HOUSEHOLD_MEMBER_LEVEL,
	];

	if ( $since ) {
		$attributes['MEMBER_SINCE'] = $since;
	}

	if ( $user->first_name ) {
		$attributes['FIRSTNAME'] = $user->first_name;
	}
	if ( $user->last_name ) {
		$attributes['LASTNAME'] = $user->last_name;
	}

	return $attributes;
}

/**
 * Push a user's membership state to Brevo.
 *
 * @param int  $user_id     User to sync.
 * @param bool $allow_create Create the contact if absent. Only ever true when the
 *                           person has actually opted in.
 * @return string One of: synced, created, skipped-no-consent, skipped-blocklisted,
 *                skipped-no-key, skipped-no-email, failed.
 */
function tclas_brevo_sync_member( int $user_id, bool $allow_create = false ): string {
	$api_key = get_option( 'sib_api_key', '' );
	if ( ! $api_key ) {
		return 'skipped-no-key';
	}

	$user = get_userdata( $user_id );
	if ( ! $user || ! is_email( $user->user_email ) ) {
		return 'skipped-no-email';
	}

	$attributes = tclas_member_attributes( $user_id );
	if ( ! $attributes ) {
		return 'skipped-no-email';
	}

	$existing = tclas_brevo_get_contact( $user->user_email, $api_key );

	if ( null === $existing ) {
		// Not a contact. Only bring them in if they said yes.
		if ( ! $allow_create || ! get_user_meta( $user_id, TCLAS_OPTIN_META, true ) ) {
			return 'skipped-no-consent';
		}

		$list_id = function_exists( 'tclas_signup_list_id' ) ? tclas_signup_list_id() : 2;
		$ok      = function_exists( 'tclas_brevo_subscribe' )
			&& tclas_brevo_subscribe( $user->user_email, $attributes, [ $list_id ] );

		return $ok ? 'created' : 'failed';
	}

	// Already a contact — keep their attributes honest either way. Note we send no
	// listIds and no emailBlacklisted, so this can neither re-subscribe someone who
	// left nor quietly resurrect a blocklisted address.
	$response = wp_remote_request( 'https://api.brevo.com/v3/contacts/' . rawurlencode( $user->user_email ), [
		'method'  => 'PUT',
		'headers' => [
			'accept'       => 'application/json',
			'content-type' => 'application/json',
			'api-key'      => $api_key,
		],
		'body'    => wp_json_encode( [ 'attributes' => $attributes ] ),
		'timeout' => 15,
	] );

	if ( is_wp_error( $response ) ) {
		error_log( 'TCLAS member sync failed for user ' . $user_id . ': ' . $response->get_error_message() );
		return 'failed';
	}

	if ( (int) wp_remote_retrieve_response_code( $response ) >= 400 ) {
		error_log( 'TCLAS member sync failed for user ' . $user_id . ': HTTP '
			. wp_remote_retrieve_response_code( $response ) . ' ' . wp_remote_retrieve_body( $response ) );
		return 'failed';
	}

	return ! empty( $existing['emailBlacklisted'] ) ? 'skipped-blocklisted' : 'synced';
}

/**
 * Fetch a Brevo contact, or null if they are not one.
 *
 * @return array<string,mixed>|null
 */
function tclas_brevo_get_contact( string $email, string $api_key = '' ): ?array {
	if ( ! $api_key ) {
		$api_key = get_option( 'sib_api_key', '' );
	}
	if ( ! $api_key ) {
		return null;
	}

	$response = wp_remote_get( 'https://api.brevo.com/v3/contacts/' . rawurlencode( $email ), [
		'headers' => [ 'accept' => 'application/json', 'api-key' => $api_key ],
		'timeout' => 10,
	] );

	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return null;
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	return is_array( $body ) ? $body : null;
}

// ── Triggers ────────────────────────────────────────────────────────────────

/**
 * Any level change: join, upgrade, downgrade, cancellation, expiry.
 * Creation is allowed here because this is the moment consent was just given.
 */
add_action( 'pmpro_after_change_membership_level', function ( $level_id, $user_id ): void {
	tclas_brevo_sync_member( (int) $user_id, true );
}, 20, 2 );

/**
 * Household sub-accounts do not go through checkout, so their level change fires
 * outside a consent moment. Sync attributes, but never create a contact for them —
 * being added to someone else's household is emphatically not opting in.
 */
add_action( 'tclas_household_member_linked', function ( $owner_id, $child_id ): void {
	tclas_brevo_sync_member( (int) $child_id, false );
}, 10, 2 );

/**
 * Email changes would otherwise strand the old address holding stale attributes.
 */
add_action( 'profile_update', function ( $user_id, $old_user_data ): void {
	$user = get_userdata( $user_id );
	if ( ! $user || ! $old_user_data || $user->user_email === $old_user_data->user_email ) {
		return;
	}
	tclas_brevo_sync_member( (int) $user_id, false );
}, 20, 2 );

// ── WP-CLI ──────────────────────────────────────────────────────────────────

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Sync every member's state to Brevo.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Report what would happen without writing to Brevo.
	 *
	 * [--create]
	 * : Also create contacts for members who opted in but are not in Brevo yet.
	 *
	 * ## EXAMPLES
	 *
	 *     wp tclas sync-members --dry-run
	 *     wp tclas sync-members --create
	 */
	WP_CLI::add_command( 'tclas sync-members', function ( $args, $assoc_args ): void {
		$dry    = ! empty( $assoc_args['dry-run'] );
		$create = ! empty( $assoc_args['create'] );

		$users   = get_users( [ 'fields' => 'ID' ] );
		$tally   = [];
		$members = 0;

		foreach ( $users as $user_id ) {
			$attributes = tclas_member_attributes( (int) $user_id );
			if ( ! $attributes ) {
				continue;
			}
			if ( ! empty( $attributes['MEMBER'] ) ) {
				$members++;
			}

			if ( $dry ) {
				$user   = get_userdata( $user_id );
				$exists = null !== tclas_brevo_get_contact( $user->user_email );
				$key    = $exists ? 'would-sync' : ( get_user_meta( $user_id, TCLAS_OPTIN_META, true ) ? 'would-create' : 'no-consent' );
			} else {
				$key = tclas_brevo_sync_member( (int) $user_id, $create );
			}

			$tally[ $key ] = ( $tally[ $key ] ?? 0 ) + 1;
		}

		WP_CLI::log( 'Users examined : ' . count( $users ) );
		WP_CLI::log( 'Active members : ' . $members );
		foreach ( $tally as $key => $n ) {
			WP_CLI::log( sprintf( '  %-22s %d', $key, $n ) );
		}
		WP_CLI::success( $dry ? 'Dry run complete — nothing written.' : 'Member sync complete.' );
	} );
}
