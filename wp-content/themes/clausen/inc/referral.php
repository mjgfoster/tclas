<?php
/**
 * Referral feature
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ── URL helpers ────────────────────────────────────────────────────────────

/**
 * Return the referral URL for the current or given user.
 */
function tclas_get_referral_url( int $user_id = 0 ): string {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return '';
	}

	$base = '';
	if ( function_exists( 'get_field' ) ) {
		$base = sanitize_text_field( (string) get_field( 'referral_base_url', 'option' ) );
	}
	if ( ! $base ) {
		$page = get_page_by_path( 'welcome' );
		$base = $page ? trailingslashit( get_permalink( $page->ID ) ) : trailingslashit( home_url( '/welcome/' ) );
	}

	$username = get_userdata( $user_id )->user_login ?? '';
	return add_query_arg( 'ref', $username, $base );
}

/**
 * Return the referral count for a user — how many members they have referred.
 */
function tclas_get_referral_count( int $user_id = 0 ): int {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	return (int) get_user_meta( $user_id, '_tclas_referral_count', true );
}

// ── Cookie / tracking ─────────────────────────────────────────────────────

/**
 * On page load, store ?ref= parameter in a cookie (expires 30 days).
 */
function tclas_capture_referral_cookie(): void {
	if ( empty( $_GET['ref'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	$ref = sanitize_text_field( wp_unslash( $_GET['ref'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	if ( $ref && ! headers_sent() ) {
		setcookie( 'tclas_referral', $ref, [
			'expires'  => time() + 30 * DAY_IN_SECONDS,
			'path'     => COOKIEPATH,
			'domain'   => COOKIE_DOMAIN,
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		] );
	}
}
add_action( 'init', 'tclas_capture_referral_cookie' );

/**
 * On new membership checkout, credit the referring member.
 *
 * @param int $new_user_id  The ID of the newly joined member.
 */
function tclas_credit_referral( int $new_user_id ): void {
	$ref_username = sanitize_text_field( (string) ( $_COOKIE['tclas_referral'] ?? '' ) );
	if ( ! $ref_username ) {
		return;
	}

	$referrer = get_user_by( 'login', $ref_username );
	if ( ! $referrer || $referrer->ID === $new_user_id ) {
		return;
	}

	// Increment referral count for referrer
	$count = tclas_get_referral_count( $referrer->ID );
	update_user_meta( $referrer->ID, '_tclas_referral_count', $count + 1 );

	// Store who referred the new user
	update_user_meta( $new_user_id, '_tclas_referred_by', $referrer->ID );

	// Clear cookie
	if ( ! headers_sent() ) {
		setcookie( 'tclas_referral', '', [
			'expires'  => time() - 3600,
			'path'     => COOKIEPATH,
			'domain'   => COOKIE_DOMAIN,
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		] );
	}
}

// ── Referral landing page template ────────────────────────────────────────

/**
 * Set the referring member name in a global for use in templates.
 */
function tclas_set_referral_context(): void {
	if ( ! is_page( 'welcome' ) ) {
		return;
	}
	$ref = sanitize_text_field( (string) ( $_GET['ref'] ?? $_COOKIE['tclas_referral'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	if ( $ref ) {
		$user = get_user_by( 'login', $ref );
		if ( $user ) {
			global $tclas_referrer;
			$tclas_referrer = $user;
		}
	}
}
add_action( 'wp', 'tclas_set_referral_context' );

/**
 * Get the referrer user object (if on a referral page).
 */
function tclas_get_referrer(): ?WP_User {
	global $tclas_referrer;
	return $tclas_referrer ?? null;
}

// ── AJAX: handle copy-to-clipboard analytics (optional) ──────────────────

function tclas_ajax_referral_copy(): void {
	check_ajax_referer( 'tclas_nonce', 'nonce' );

	$user_id = get_current_user_id();
	if ( $user_id ) {
		$count = (int) get_user_meta( $user_id, '_tclas_referral_copy_count', true );
		update_user_meta( $user_id, '_tclas_referral_copy_count', $count + 1 );
	}

	wp_send_json_success();
}
add_action( 'wp_ajax_tclas_referral_copy', 'tclas_ajax_referral_copy' );
