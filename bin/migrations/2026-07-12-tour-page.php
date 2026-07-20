<?php
/**
 * Migration: create the /tour/ launch-party landing page (2026-07-12).
 *
 * Companion to the page-templates/page-tour.php theme template. The page
 * is the QR-code target on the printed launch-party postcard, then stays
 * as an evergreen "start here" tour (the party greeting in the template
 * self-expires after 2026-07-22).
 *
 * Idempotent — skips creation if a page with slug "tour" already exists,
 * but always (re)asserts the template assignment.
 *
 * Run:  bin/migrate.sh bin/migrations/2026-07-12-tour-page.php
 *       bin/migrate.sh --prod bin/migrations/2026-07-12-tour-page.php
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

$tour = get_page_by_path( 'tour' );

if ( $tour ) {
	WP_CLI::log( "Tour page already exists (ID {$tour->ID}) — skipping creation." );
} else {
	$tour_id = wp_insert_post(
		array(
			'post_type'   => 'page',
			'post_status' => 'publish',
			'post_title'  => 'Wëllkomm!',
			'post_name'   => 'tour',
		),
		true
	);
	if ( is_wp_error( $tour_id ) ) {
		WP_CLI::error( 'Could not create tour page: ' . $tour_id->get_error_message() );
	}
	$tour = get_post( $tour_id );
	WP_CLI::success( "Created tour page (ID {$tour_id})." );
}

if ( get_post_meta( $tour->ID, '_wp_page_template', true ) !== 'page-templates/page-tour.php' ) {
	update_post_meta( $tour->ID, '_wp_page_template', 'page-templates/page-tour.php' );
	WP_CLI::success( 'Assigned page-tour.php template.' );
} else {
	WP_CLI::log( 'Template already assigned.' );
}
