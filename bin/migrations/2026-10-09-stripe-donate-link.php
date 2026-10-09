<?php
/**
 * Migration: donate page → Stripe Payment Link (2026-10-09).
 *
 * GiveWP's bundled stripe-php 7.x loads ahead of PMPro's 18.x and crashes
 * PMPro's webhook on charge.failed. Donations move to a Stripe Payment Link;
 * this sets Theme Options → donate_payment_link so /donate/ shows the button
 * instead of the GiveWP form. GiveWP itself is deactivated by hand after the
 * new flow is verified live.
 *
 * Idempotent: skips if the option already holds this link.
 *
 * Run:  bin/migrate.sh bin/migrations/2026-10-09-stripe-donate-link.php
 *       bin/migrate.sh --prod bin/migrations/2026-10-09-stripe-donate-link.php
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

const TCLAS_DONATE_LINK = 'https://donate.stripe.com/00w6oH2fB9TP1NqgSOeIw00'; // plink_1UOhdLDJNhrm1srHNg9t9JJh

if ( ! TCLAS_DONATE_LINK || ! str_starts_with( TCLAS_DONATE_LINK, 'https://donate.stripe.com/' ) ) {
	WP_CLI::error( 'TCLAS_DONATE_LINK is not set to a donate.stripe.com URL.' );
}
if ( ! function_exists( 'update_field' ) ) {
	WP_CLI::error( 'ACF is not active.' );
}

if ( get_field( 'donate_payment_link', 'option' ) === TCLAS_DONATE_LINK ) {
	WP_CLI::log( 'donate_payment_link already set, skipping.' );
} else {
	update_field( 'donate_payment_link', TCLAS_DONATE_LINK, 'option' );
	WP_CLI::success( 'donate_payment_link set to ' . TCLAS_DONATE_LINK );
}
