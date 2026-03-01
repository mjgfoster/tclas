<?php
/**
 * Paid Memberships Pro integration
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

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
 * Auto-subscribe new members to Mailchimp members list via MC4WP.
 */
function tclas_pmpro_after_checkout( int $user_id, object $morder ): void {
	if ( ! function_exists( 'mc4wp_get_api_v3' ) ) {
		return;
	}

	$list_id = function_exists( 'get_field' )
		? sanitize_text_field( (string) get_field( 'mailchimp_members_list_id', 'option' ) )
		: '';

	if ( ! $list_id ) {
		return;
	}

	$user  = get_userdata( $user_id );
	$email = $user ? $user->user_email : '';
	if ( ! $email ) {
		return;
	}

	try {
		$api = mc4wp_get_api_v3();
		$api->add_list_member( $list_id, [
			'email_address' => $email,
			'status'        => 'subscribed',
			'merge_fields'  => [
				'FNAME' => $user->first_name ?? '',
				'LNAME' => $user->last_name ?? '',
			],
		] );
	} catch ( Exception $e ) {
		// Fail silently — not critical
	}

	// Credit referral if present
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

/**
 * Unsubscribe cancelled members from Mailchimp members list.
 */
function tclas_pmpro_after_change_membership_level( int $level_id, int $user_id ): void {
	if ( $level_id !== 0 ) {
		return; // Only act on cancellation (level_id = 0)
	}

	if ( ! function_exists( 'mc4wp_get_api_v3' ) ) {
		return;
	}

	$list_id = function_exists( 'get_field' )
		? sanitize_text_field( (string) get_field( 'mailchimp_members_list_id', 'option' ) )
		: '';

	if ( ! $list_id ) {
		return;
	}

	$user  = get_userdata( $user_id );
	$email = $user ? $user->user_email : '';
	if ( ! $email ) {
		return;
	}

	try {
		$api    = mc4wp_get_api_v3();
		$hash   = md5( strtolower( $email ) );
		$api->update_list_member( $list_id, $hash, [ 'status' => 'unsubscribed' ] );
	} catch ( Exception $e ) {
		// Fail silently
	}
}
add_action( 'pmpro_after_change_membership_level', 'tclas_pmpro_after_change_membership_level', 10, 2 );
