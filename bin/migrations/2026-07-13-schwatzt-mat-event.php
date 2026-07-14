<?php
/**
 * Migration: Schwätzt mat! members-only study group event (2026-07-13).
 *
 * Creates the Wilder Center venue, the single event listing for the 13-week
 * fall 2026 series, restricts it to all PMPro levels, attaches an RSVP
 * ticket capped at 10 (window: publish → first session), and sets the
 * RSVP confirmation email overrides (subject / heading / additional content).
 *
 * PREREQUISITE: the Event Tickets plugin (free) must be installed and active
 * before running — this migration errors out if it isn't:
 *     wp plugin install event-tickets --activate
 *
 * Idempotent — skips event creation if the slug already exists; email
 * options are always (re)asserted.
 *
 * Run:  bin/migrate.sh bin/migrations/2026-07-13-schwatzt-mat-event.php
 *       bin/migrate.sh --prod bin/migrations/2026-07-13-schwatzt-mat-event.php
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

if ( ! class_exists( 'Tribe__Tickets__RSVP' ) ) {
	WP_CLI::error( 'Event Tickets is not active. Run: wp plugin install event-tickets --activate' );
}
if ( ! function_exists( 'tribe_create_event' ) ) {
	WP_CLI::error( 'The Events Calendar is not active.' );
}

global $wpdb;

// ── Venue ────────────────────────────────────────────────────────────────────
$venue_id = 0;
foreach ( get_posts( array( 'post_type' => 'tribe_venue', 'posts_per_page' => -1 ) ) as $v ) {
	if ( false !== stripos( $v->post_title, 'Wilder' ) ) {
		$venue_id = $v->ID;
		break;
	}
}
if ( $venue_id ) {
	WP_CLI::log( "Wilder Center venue already exists (ID {$venue_id})." );
} else {
	$venue_id = tribe_create_venue( array(
		'Venue'   => 'Wilder Center',
		'Address' => '451 Lexington Parkway North',
		'City'    => 'Saint Paul',
		'State'   => 'MN',
		'Zip'     => '55104',
		'Country' => 'United States',
	) );
	WP_CLI::success( "Created Wilder Center venue (ID {$venue_id})." );
}

// ── Event ────────────────────────────────────────────────────────────────────
$event = get_page_by_path( 'schwatzt-mat-fall-2026', OBJECT, 'tribe_events' );

if ( $event ) {
	WP_CLI::log( "Event already exists (ID {$event->ID}) — skipping creation." );
	$event_id = $event->ID;
} else {
	$content = <<<HTML
<p><strong>Schwätzt mat!</strong> ("Join the conversation!") is a free, members-only peer study group for absolute beginners in Lëtzebuergesch. No prior experience needed — just curiosity and a willingness to try out your first words of Luxembourgish.</p>
<p>We meet <strong>Tuesdays from 6:30–8:00 p.m., September 1 through November 24, 2026</strong> (13 sessions) at the Wilder Center in St. Paul. You register once for the whole series.</p>
<h3>Session dates</h3>
<p>September 1, 8, 15, 22, 29 &middot; October 6, 13, 20, 27 &middot; November 3, 10, 17, 24</p>
<h3>What to expect</h3>
<p>Space is limited to <strong>10 participants</strong> so everyone gets plenty of speaking time. Registration is free with your TCLAS membership. A materials list will be emailed to registered participants before the first session.</p>
<p><em>Group full?</em> <a href="/contact/">Contact us to join the waitlist</a> — if there's enough interest, we'll open a winter cohort.</p>
HTML;

	$event_id = tribe_create_event( array(
		'post_title'       => 'Schwätzt mat! — Beginner Lëtzebuergesch Study Group',
		'post_name'        => 'schwatzt-mat-fall-2026',
		'post_content'     => $content,
		'post_status'      => 'publish',
		'EventStartDate'   => '2026-09-01',
		'EventStartHour'   => '18',
		'EventStartMinute' => '30',
		'EventEndDate'     => '2026-09-01',
		'EventEndHour'     => '20',
		'EventEndMinute'   => '00',
		'Venue'            => array( 'VenueID' => $venue_id ),
	) );
	if ( ! $event_id || is_wp_error( $event_id ) ) {
		WP_CLI::error( 'Could not create event.' );
	}
	WP_CLI::success( "Created event (ID {$event_id})." );
}

// ── PMPro restriction: every membership level ────────────────────────────────
$level_ids = $wpdb->get_col( "SELECT id FROM {$wpdb->pmpro_membership_levels}" );
foreach ( $level_ids as $level_id ) {
	$wpdb->replace(
		$wpdb->pmpro_memberships_pages,
		array( 'membership_id' => (int) $level_id, 'page_id' => $event_id ),
		array( '%d', '%d' )
	);
}
WP_CLI::success( 'Restricted event to levels: ' . implode( ', ', $level_ids ) . '.' );

// ── RSVP ticket, capacity 10 ─────────────────────────────────────────────────
$existing_ticket = get_posts( array(
	'post_type'      => 'tribe_rsvp_tickets',
	'posts_per_page' => 1,
	'meta_key'       => '_tribe_rsvp_for_event',
	'meta_value'     => $event_id,
	'fields'         => 'ids',
) );

if ( $existing_ticket ) {
	WP_CLI::log( "RSVP ticket already exists (ID {$existing_ticket[0]}) — skipping." );
} else {
	$ticket_id = Tribe__Tickets__RSVP::get_instance()->ticket_add( $event_id, array(
		'ticket_name'             => 'Schwätzt mat! — Fall 2026 series (all 13 sessions)',
		'ticket_description'      => 'One RSVP covers the whole series. Free with TCLAS membership.',
		'ticket_show_description' => 1,
		'ticket_end_date'         => '2026-09-01',
		'ticket_end_time'         => '18:30:00',
		'tribe-ticket'            => array(
			'capacity'  => 10,
			'not_going' => 'no',
		),
	) );
	if ( ! $ticket_id ) {
		WP_CLI::error( 'Could not create RSVP ticket.' );
	}
	WP_CLI::success( "Created RSVP ticket (ID {$ticket_id}, capacity 10)." );
}

// ── RSVP confirmation email overrides (global to all RSVP emails) ────────────
tribe_update_option( 'tec-tickets-emails-rsvp-use-ticket-email', false );
tribe_update_option( 'tec-tickets-emails-rsvp-subject', 'Ugemellt! Your TCLAS RSVP is confirmed' );
tribe_update_option( 'tec-tickets-emails-rsvp-heading', "Moien {attendee_name} — you're in!" );
tribe_update_option( 'tec-tickets-emails-rsvp-additional-content', '<p><strong>Schwätzt mat!</strong> meets Tuesdays from 6:30 to 8:00 p.m., September 1 through November 24, 2026 (13 sessions) at the Wilder Center, 451 Lexington Parkway North, St. Paul. Your RSVP covers the whole series — no need to register for individual weeks.</p><p>We will email registered participants a short materials list before the first session. Questions, or can no longer make it? Use the contact form at twincities.lu/contact so we can offer your spot to the waitlist.</p>' );
WP_CLI::success( 'Set RSVP confirmation email subject/heading/content.' );

WP_CLI::log( 'Done. Verify: event page logged out (gated), as member (RSVP form), and /?s= search (hidden).' );
