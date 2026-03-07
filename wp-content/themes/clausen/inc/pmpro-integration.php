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
