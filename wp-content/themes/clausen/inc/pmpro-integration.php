<?php
/**
 * Paid Memberships Pro integration
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// PMPro membership level IDs (must match wp_pmpro_membership_levels).
const TCLAS_LEVEL_INDIVIDUAL = 1;
const TCLAS_LEVEL_HOUSEHOLD  = 2;
const TCLAS_LEVEL_STUDENT    = 3;
const TCLAS_LEVEL_BENEFACTOR = 4;
// Free, linked sub-account level held by invited household members. $0, no
// expiration of its own; access is governed by the owner's Household status
// via the cascade in inc/household-accounts.php.
const TCLAS_LEVEL_HOUSEHOLD_MEMBER = 5;
const TCLAS_BENEFACTOR_MIN   = 1000;

/**
 * Redirect to member hub after login if user is a member.
 */
function tclas_pmpro_after_login_redirect( string $redirect, string $requested_redirect, WP_User|WP_Error $user ): string {
	if ( ! ( $user instanceof WP_User ) ) {
		return $redirect;
	}
	if ( function_exists( 'pmpro_hasMembershipLevel' ) && pmpro_hasMembershipLevel( null, $user->ID ) ) {
		$hub_page = get_page_by_path( 'member-hub' );
		if ( $hub_page ) {
			return get_permalink( $hub_page->ID );
		}
	}
	return $redirect;
}
add_filter( 'login_redirect', 'tclas_pmpro_after_login_redirect', 10, 3 );

/**
 * Credit referral after checkout (Brevo member sync handled by FuseWP).
 */
function tclas_pmpro_after_checkout( int $user_id, object $morder ): void {
	tclas_credit_referral( $user_id );
}
add_action( 'pmpro_after_checkout', 'tclas_pmpro_after_checkout', 10, 2 );

/**
 * Add a renew prompt to PMPro account page for expiring/expired members.
 */
function tclas_pmpro_account_page_notices(): void {
	$status = tclas_membership_status();
	if ( ! in_array( $status, [ 'expiring', 'expired' ], true ) ) {
		return;
	}

	$days      = tclas_days_to_expiry();
	$renew_url = function_exists( 'pmpro_url' ) ? pmpro_url( 'checkout' ) : '#';

	if ( $status === 'expired' ) {
		$message = sprintf(
			/* translators: %s: renew URL */
			__( 'Your TCLAS membership has expired. <a href="%s">Renew now</a> to keep access to the member hub, directory, and events.', 'tclas' ),
			esc_url( $renew_url )
		);
	} else {
		$message = sprintf(
			/* translators: 1: days, 2: renew URL */
			_n(
				'Your TCLAS membership expires in %1$d day. <a href="%2$s">Renew now</a> to keep access.',
				'Your TCLAS membership expires in %1$d days. <a href="%2$s">Renew now</a> to keep access.',
				$days,
				'tclas'
			),
			$days,
			esc_url( $renew_url )
		);
	}

	echo '<div class="tclas-alert tclas-alert--info">' . wp_kses_post( $message ) . '</div>';
}
add_action( 'pmpro_account_bullets_top', 'tclas_pmpro_account_page_notices' );

/**
 * Filter the PMPro levels page to use our custom tier template.
 * Return false to let the shortcode handle it normally,
 * or return a string to completely replace the output.
 */
function tclas_pmpro_levels_shortcode( string $content ): string {
	// We let PMPro render normally but add our own wrapper class.
	return '<div class="tclas-pmpro-levels-wrap">' . $content . '</div>';
}
add_filter( 'pmpro_levels_page_content', 'tclas_pmpro_levels_shortcode' );

// Member subscribe/unsubscribe sync is handled by FuseWP → Brevo.
// No custom Mailchimp hooks needed.

/**
 * Members log in with their email (email = username), so relabel the login
 * field from "Username or Email Address" to just "Email".
 */
add_filter( 'gettext', function ( $translation, $text, $domain ) {
	switch ( $text ) {
		case 'Username or Email Address':
		case 'Username or Email':
			return 'Email';
		case 'Billing Address':
			// We use this address to mail members things (e.g. the annual gift).
			return 'Mailing Address';
	}
	return $translation;
}, 10, 3 );

// ═══════════════════════════════════════════════════════════════════════════
// Family membership section on account page
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Handle family membership form POST on account page.
 */
function tclas_pmpro_save_family_members(): void {
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		return;
	}
	if ( ! isset( $_POST['tclas_family_nonce'] ) ) {
		return;
	}
	$uid = get_current_user_id();
	if ( ! $uid || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tclas_family_nonce'] ) ), 'tclas_save_family_' . $uid ) ) {
		return;
	}

	// Adult household members are now managed as real linked accounts in the
	// member hub (see inc/household-accounts.php). We no longer write the legacy
	// _tclas_family_names text list — existing values are left untouched so no
	// data is orphaned. This form now only saves the "covers children" flag.
	update_user_meta( $uid, '_tclas_has_children', ! empty( $_POST['tclas_has_children'] ) ? 1 : 0 );

	delete_transient( 'tclas_directory_members' );
}
add_action( 'wp', 'tclas_pmpro_save_family_members' );

/**
 * Render a "Family members" section on the PMPro account page.
 */
function tclas_pmpro_family_section(): void {
	$uid = get_current_user_id();
	if ( ! $uid ) {
		return;
	}

	// Only show for Household memberships, or if they have legacy family names saved.
	$family_names = (array) ( get_user_meta( $uid, '_tclas_family_names', true ) ?: [] );
	$has_children = (bool) get_user_meta( $uid, '_tclas_has_children', true );
	$level        = function_exists( 'pmpro_getMembershipLevelForUser' ) ? pmpro_getMembershipLevelForUser( $uid ) : null;
	// Household is level ID 2 (formerly "Family"); key off ID so renames don't break this.
	$is_family    = $level && (int) $level->id === TCLAS_LEVEL_HOUSEHOLD;

	if ( ! $is_family && empty( $family_names ) ) {
		return;
	}

	$saved = isset( $_POST['tclas_family_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tclas_family_nonce'] ) ), 'tclas_save_family_' . $uid );
	?>
	<div id="tclas-family-section" class="pmpro_actionlinks" style="margin-top:2rem;">
		<h2><?php esc_html_e( 'Family Members', 'tclas' ); ?></h2>

		<?php if ( $saved ) : ?>
			<div class="tclas-alert tclas-alert--success" role="alert" style="margin-bottom:1rem;">
				<?php esc_html_e( 'Family members saved.', 'tclas' ); ?>
			</div>
		<?php endif; ?>

		<p class="tclas-story-hint">
			<?php esc_html_e( 'Adult household members now get their own login and member profile. Invite them from the “Household members” section of your member hub.', 'tclas' ); ?>
		</p>

		<p class="tclas-hub2-action">
			<a class="btn btn-sm btn-outline-ardoise" href="<?php echo esc_url( home_url( '/member-hub/' ) . '#tclas-household' ); ?>">
				<?php esc_html_e( 'Manage household members →', 'tclas' ); ?>
			</a>
		</p>

		<?php
		$legacy_names = array_values( array_filter( array_map( 'trim', (array) $family_names ), 'strlen' ) );
		if ( $legacy_names ) :
			?>
			<p class="tclas-story-hint">
				<?php
				printf(
					/* translators: %s: comma-separated list of previously entered names */
					esc_html__( 'Previously listed (text only, not accounts): %s. Send each adult an invitation from the hub so they get their own profile.', 'tclas' ),
					esc_html( implode( ', ', $legacy_names ) )
				);
				?>
			</p>
		<?php endif; ?>

		<form method="post" action="">
			<?php wp_nonce_field( 'tclas_save_family_' . $uid, 'tclas_family_nonce' ); ?>

			<div style="margin-bottom:1rem;">
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

			<button type="submit" class="btn btn-primary btn-sm">
				<?php esc_html_e( 'Save', 'tclas' ); ?>
			</button>
		</form>
	</div>
	<?php
}
add_action( 'pmpro_account_after_member_links', 'tclas_pmpro_family_section' );

/**
 * Disable PMPro's per-post content restriction for posts.
 *
 * The theme handles article-level gating via the _tclas_members_only
 * meta field, which shows a branded teaser + gate instead of PMPro's
 * generic paywall. This prevents the two systems from conflicting.
 */
add_filter( 'pmpro_has_membership_access_filter', function ( $hasaccess, $mypost, $myuser, $post_membership_levels ) {
	if ( $mypost && 'post' === get_post_type( $mypost->ID ) ) {
		return true; // Always grant access at the PMPro level; our template handles gating.
	}
	return $hasaccess;
}, 10, 4 );

/**
 * Backstop: re-apply PMPro's members-only search/archive exclusions after
 * every other pre_get_posts callback has run.
 *
 * PMPro adds its exclusion list at priority 10, but any plugin that set()s
 * post__not_in outright afterwards (GiveWP's give_remove_pages_from_search
 * did exactly this) silently wipes it, leaking restricted content into
 * search results. pmpro_search_filter caches its hidden-post list in a
 * static and merges with the query's current post__not_in, so re-running
 * it last is cheap and idempotent.
 */
add_action( 'pre_get_posts', function ( $query ) {
	if ( function_exists( 'pmpro_search_filter' ) && get_option( 'pmpro_filterqueries' ) ) {
		pmpro_search_filter( $query );
	}
}, PHP_INT_MAX );

/**
 * Server-side membership gate for Event Tickets RSVP AJAX.
 *
 * The RSVP form is only rendered to users who can access the event, but the
 * admin-ajax handler (tribe_tickets_rsvp_handle) only verifies a nonce — a
 * visitor could still walk through the RSVP steps by POSTing directly. Deny
 * every step when the ticket belongs to an event the current user can't see.
 */
function tclas_gate_rsvp_ajax(): void {
	if ( ! function_exists( 'pmpro_has_membership_access' ) ) {
		return;
	}

	$ticket_id = isset( $_POST['ticket_id'] ) ? absint( $_POST['ticket_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( ! $ticket_id ) {
		return;
	}

	$event_id = (int) get_post_meta( $ticket_id, '_tribe_rsvp_for_event', true );
	if ( $event_id && ! pmpro_has_membership_access( $event_id ) ) {
		wp_send_json_error( [
			'html' => esc_html__( 'This event is open to TCLAS members. Please log in or join to RSVP.', 'tclas' ),
		] );
	}
}
add_action( 'wp_ajax_tribe_tickets_rsvp_handle', 'tclas_gate_rsvp_ajax', 5 );
add_action( 'wp_ajax_nopriv_tribe_tickets_rsvp_handle', 'tclas_gate_rsvp_ajax', 5 );

/**
 * Close the REST leak for members-only events.
 *
 * PMPro gates the_content (which covers core /wp/v2 responses), but The
 * Events Calendar's and Event Tickets' own REST APIs build payloads from raw
 * post data — anonymous requests to /tribe/events/v1 and /tribe/tickets/v1
 * received the full description, venue, and ticket availability of
 * restricted events. Returning WP_Error here drops the item from archive
 * responses and turns single-item requests into an auth error.
 */
add_filter( 'tribe_rest_event_data', function ( $data, $event ) {
	if (
		function_exists( 'pmpro_has_membership_access' )
		&& is_array( $data )
		&& ! empty( $data['id'] )
		&& ! pmpro_has_membership_access( (int) $data['id'] )
	) {
		return new WP_Error(
			'rest_forbidden',
			__( 'You are not authorized to see this event.', 'tclas' ),
			[ 'status' => rest_authorization_required_code() ]
		);
	}
	return $data;
}, 9999, 2 );

add_filter( 'tribe_tickets_rest_api_ticket_data', function ( $data, $ticket_id ) {
	if ( ! function_exists( 'pmpro_has_membership_access' ) || ! is_array( $data ) ) {
		return $data;
	}
	$event_id = ! empty( $data['post_id'] ) ? (int) $data['post_id'] : 0;
	if ( $event_id && ! pmpro_has_membership_access( $event_id ) ) {
		return new WP_Error(
			'rest_forbidden',
			__( 'You are not authorized to see this ticket.', 'tclas' ),
			[ 'status' => rest_authorization_required_code() ]
		);
	}
	return $data;
}, 9999, 2 );
