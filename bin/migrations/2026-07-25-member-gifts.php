<?php
/**
 * Migration: annual member gift log (2026-07-25).
 *
 * Companion to the clausen `feature/member-gifts` work (inc/member-gifts.php).
 * Idempotent — every step verifies state before changing it.
 *
 * What it does:
 *   1. Create {prefix}tclas_gift_log via the theme's own dbDelta routine.
 *      (The theme also creates it on admin_init; this makes the change explicit
 *      and lets us run it right after the deploy without waiting for an admin
 *      page load.)
 *   2. Backfill _tclas_mail_country from PMPro's stored billing country for
 *      members who checked out before the country field was captured.
 *   3. Report address coverage: how many gift households can actually be
 *      mailed today, and who can't. Read-only — fixing those is a human job
 *      (nudge the member, or fill it in on their user profile screen).
 *
 * Run:  bin/migrate.sh bin/migrations/2026-07-25-member-gifts.php
 *       bin/migrate.sh --prod bin/migrations/2026-07-25-member-gifts.php
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

global $wpdb;

// ── 1. Gift log table ───────────────────────────────────────────────────────

if ( ! function_exists( 'tclas_gift_create_table' ) ) {
	WP_CLI::error( 'inc/member-gifts.php is not loaded — deploy the theme before running this migration.' );
}

$table  = tclas_gift_log_table();
$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

if ( $exists ) {
	WP_CLI::log( "Table {$table} already exists — OK." );
	// Still run the creator: dbDelta is the thing that applies column changes,
	// and the version option may be behind on a site created by an older build.
	delete_option( 'tclas_gift_log_db_version' );
	tclas_gift_create_table();
	WP_CLI::log( 'Ran dbDelta to reconcile columns — OK.' );
} else {
	tclas_gift_create_table();
	$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	if ( $exists ) {
		WP_CLI::success( "Created table {$table}." );
	} else {
		WP_CLI::error( "Failed to create {$table} — check DB privileges." );
	}
}

// ── 2. Backfill country from PMPro billing ──────────────────────────────────
//
// PMPro keeps its own copy of the billing country in user meta. Members who
// joined before the checkout captured bcountry have an empty _tclas_mail_country.

$backfilled = 0;
$candidates = $wpdb->get_col(
	"SELECT DISTINCT um.user_id
	 FROM {$wpdb->usermeta} um
	 WHERE um.meta_key = 'pmpro_bcountry' AND um.meta_value != ''"
);

foreach ( (array) $candidates as $uid ) {
	$uid = (int) $uid;
	if ( '' !== (string) get_user_meta( $uid, '_tclas_mail_country', true ) ) {
		continue;
	}
	$country = (string) get_user_meta( $uid, 'pmpro_bcountry', true );
	if ( '' === $country ) {
		continue;
	}
	update_user_meta( $uid, '_tclas_mail_country', sanitize_text_field( $country ) );
	$backfilled++;
}

if ( $backfilled ) {
	WP_CLI::success( "Backfilled _tclas_mail_country for {$backfilled} member(s) from PMPro billing." );
} else {
	WP_CLI::log( 'No countries to backfill — OK.' );
}

// ── 3. Address coverage report (read-only) ──────────────────────────────────

$year = (int) current_time( 'Y' );
$list = tclas_gift_distribution_list( $year );

$mailable = array_filter( $list, fn( $r ) => $r['mailable'] );
$missing  = array_filter( $list, fn( $r ) => ! $r['mailable'] );
$items    = array_sum( array_column( $mailable, 'item_count' ) );

WP_CLI::log( '' );
WP_CLI::log( sprintf( 'Gift households (%d): %d total, %d mailable, %d missing an address.', $year, count( $list ), count( $mailable ), count( $missing ) ) );
WP_CLI::log( sprintf( 'Items to pack for the mailable households: %d.', $items ) );

if ( $missing ) {
	WP_CLI::log( '' );
	WP_CLI::warning( 'These households have no mailable address yet:' );
	foreach ( $missing as $row ) {
		WP_CLI::log( sprintf( '  · %s (%s) — user %d, %s', $row['name'], $row['level_name'], $row['user_id'], $row['email'] ) );
	}
	WP_CLI::log( '' );
	WP_CLI::log( 'Fix by asking them to fill in Edit Profile → Mailing address, or enter it' );
	WP_CLI::log( 'yourself under Users → (member) → Mailing address (annual gift).' );
}

WP_CLI::success( 'Migration complete.' );
