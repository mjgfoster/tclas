<?php
/**
 * Migration: fall 2026 events (2026-10-07).
 *
 * Creates three events:
 *   - Béier fir Bierger at Dual Citizen (Oct 22) — public, no RSVP, reuses the
 *     existing Dual Citizen venue and the series artwork. Becomes the featured
 *     event (the hero on /events/).
 *   - Francis of Delirium at 7th St Entry (Nov 13) — public listing; the
 *     registration button points at First Avenue and reads "Get tickets".
 *   - Prost, Kleeschen! (Dec 6) — members-only teaser (_tclas_members_only):
 *     description public, the hosts' home address and the RSVP form shown only
 *     to members. Free RSVP capped at 20 (the board doesn't count toward it).
 *     The venue is flagged _tclas_private_venue so it stays out of the venue
 *     REST endpoints — that theme code must be deployed BEFORE this runs.
 *
 * Each event gets a hand-written excerpt for its card rather than a trimmed
 * description. Also replaces the RSVP confirmation email's additional content,
 * which is global to every RSVP event and still carried the Schwätzt mat! text.
 *
 * Images: import these on each site BEFORE running (a missing one is a
 * warning, not an error — set it by hand afterwards):
 *   prost-kleeschen-illustration.jpg      → slug prost-kleeschen-illustration
 *   francis-of-delirium-shade-cumini.jpg  → slug francis-of-delirium-shade-cumini
 *
 * Idempotent — events, venues and tickets are skipped if they already exist;
 * excerpts, flags, images and the email option are (re)asserted.
 *
 * Run:  bin/migrate.sh bin/migrations/2026-10-07-fall-events.php
 *       bin/migrate.sh --prod bin/migrations/2026-10-07-fall-events.php
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

if ( ! class_exists( 'Tribe__Tickets__RSVP' ) ) {
	WP_CLI::error( 'Event Tickets is not active.' );
}
if ( ! function_exists( 'tribe_create_event' ) ) {
	WP_CLI::error( 'The Events Calendar is not active.' );
}
if ( ! function_exists( 'tclas_private_venue_ids' ) || ! function_exists( 'tclas_registration_label' ) ) {
	WP_CLI::error( 'Deploy the theme first — it needs the private-venue and button-text code.' );
}

/**
 * Find a venue by title fragment, or create it.
 */
$tclas_venue = function ( string $needle, array $args ): int {
	foreach ( get_posts( array( 'post_type' => 'tribe_venue', 'posts_per_page' => -1, 'post_status' => 'any' ) ) as $v ) {
		if ( false !== stripos( $v->post_title, $needle ) ) {
			WP_CLI::log( "Venue \"{$v->post_title}\" already exists (ID {$v->ID})." );
			return $v->ID;
		}
	}
	$id = tribe_create_venue( $args );
	if ( ! $id || is_wp_error( $id ) ) {
		WP_CLI::error( "Could not create venue {$args['Venue']}." );
	}
	WP_CLI::success( "Created venue {$args['Venue']} (ID {$id})." );
	return (int) $id;
};

/**
 * Create an event unless its slug already exists. Returns the event ID.
 */
$tclas_event = function ( string $slug, array $args ): int {
	$existing = get_page_by_path( $slug, OBJECT, 'tribe_events' );
	if ( $existing ) {
		WP_CLI::log( "Event {$slug} already exists (ID {$existing->ID}) — skipping creation." );
		return $existing->ID;
	}
	$id = tribe_create_event( array_merge( array( 'post_name' => $slug, 'post_status' => 'publish' ), $args ) );
	if ( ! $id || is_wp_error( $id ) ) {
		WP_CLI::error( "Could not create event {$slug}." );
	}
	WP_CLI::success( "Created event {$slug} (ID {$id})." );
	return (int) $id;
};

/**
 * Set an event's featured image from an attachment slug, unless it has one.
 */
$tclas_thumb = function ( int $event_id, string $attachment_slug ): void {
	if ( has_post_thumbnail( $event_id ) ) {
		return;
	}
	$att = get_posts( array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'name'           => $attachment_slug,
		'posts_per_page' => 1,
		'fields'         => 'ids',
	) );
	if ( $att ) {
		set_post_thumbnail( $event_id, $att[0] );
		WP_CLI::success( "Set {$attachment_slug} (attachment {$att[0]}) as event {$event_id}'s image." );
	} else {
		WP_CLI::warning( "{$attachment_slug} not found in the media library — set event {$event_id}'s image by hand." );
	}
};

/**
 * Set an event's card excerpt.
 */
$tclas_excerpt = function ( int $event_id, string $excerpt ): void {
	wp_update_post( array( 'ID' => $event_id, 'post_excerpt' => $excerpt ) );
};

$celebrations = get_term_by( 'slug', 'celebrations', 'tribe_events_cat' );

// ── Béier fir Bierger at Dual Citizen (Oct 22) ──────────────────────────────
$dual_citizen = $tclas_venue( 'Dual Citizen', array(
	'Venue'   => 'Dual Citizen Brewing Company',
	'Address' => '725 Raymond Ave',
	'City'    => 'Saint Paul',
	'State'   => 'MN',
	'Zip'     => '55114',
	'Country' => 'United States',
	'URL'     => 'https://dcbc.com/',
) );

$beier_id = $tclas_event( 'beier-fir-bierger-dual-citizen-2026-10', array(
	'post_title'       => 'Béier fir Bierger at Dual Citizen',
	'post_content'     => <<<HTML
<p>Come have a drink with the Twin Cities Luxembourg American Society! We'll be up on the mezzanine at Dual Citizen Brewing from 6 to 8 p.m.</p>
<p>Members, friends, the Lux-curious and anyone with a grandmother from Echternach are all welcome. No RSVP needed — just head up the stairs and look for the red, white and sky blue.</p>
HTML,
	'EventStartDate'   => '2026-10-22',
	'EventStartHour'   => '18',
	'EventStartMinute' => '00',
	'EventEndDate'     => '2026-10-22',
	'EventEndHour'     => '20',
	'EventEndMinute'   => '00',
	'Venue'            => array( 'VenueID' => $dual_citizen ),
) );

$tclas_thumb( $beier_id, 'beier-fir-bierger' );
$tclas_excerpt( $beier_id, 'Come have a drink with TCLAS on the mezzanine at Dual Citizen Brewing. Members, friends and the Lux-curious are all welcome — no RSVP needed.' );
update_post_meta( $beier_id, '_tclas_featured_event', '1' );
WP_CLI::success( 'Made Béier fir Bierger the featured event.' );
if ( $celebrations ) {
	wp_set_object_terms( $beier_id, array( $celebrations->term_id ), 'tribe_events_cat' );
}

// ── Francis of Delirium at 7th St Entry (Nov 13) ────────────────────────────
$entry = $tclas_venue( '7th St', array(
	'Venue'   => '7th St Entry',
	'Address' => '701 N 1st Ave',
	'City'    => 'Minneapolis',
	'State'   => 'MN',
	'Zip'     => '55403',
	'Country' => 'United States',
	'URL'     => 'https://first-avenue.com/venue/7th-st-entry/',
) );

$fod_tickets = 'https://first-avenue.com/event/2026-11-francis-of-delirium/';
$fod_id      = $tclas_event( 'francis-of-delirium-7th-st-entry-2026', array(
	'post_title'       => 'Francis of Delirium at 7th St Entry',
	'post_content'     => <<<HTML
<p>Luxembourg indie rock is coming to Minneapolis, and we'd love to fill the room.</p>
<p>Francis of Delirium is the Luxembourg band led by singer and guitarist Jana Bahrich — loud, emotionally direct indie rock with a streak of '90s grunge. They've toured Europe and North America, supporting The 1975, Soccer Mommy and Blondshell. Their debut album, <em>Lighthouse</em>, came out in 2024; their latest release is <em>Run, Run Pure Beauty</em>.</p>
<p>They're playing the 7th St Entry at First Avenue on Friday, November 13. Doors open at 7:00, the show starts at 8:00, and it's 18+. Tickets are sold through First Avenue.</p>
<p>A few TCLAS members are already going. Come say moien, and help give a Luxembourger artist a warm Minneapolis welcome.</p>
HTML,
	'EventStartDate'   => '2026-11-13',
	'EventStartHour'   => '20',
	'EventStartMinute' => '00',
	'EventEndDate'     => '2026-11-13',
	'EventEndHour'     => '23',
	'EventEndMinute'   => '00',
	'EventURL'         => $fod_tickets,
	'Venue'            => array( 'VenueID' => $entry ),
) );

update_post_meta( $fod_id, '_tclas_registration_url', $fod_tickets );
update_post_meta( $fod_id, '_tclas_registration_label', 'tickets' );
$tclas_thumb( $fod_id, 'francis-of-delirium-shade-cumini' );
$tclas_excerpt( $fod_id, 'Luxembourg indie rockers Francis of Delirium play the 7th St Entry. Come say moien and give them a warm welcome.' );

// ── Prost, Kleeschen! (Dec 6) — members-only teaser + RSVP ──────────────────
$faust = $tclas_venue( 'Faust', array(
	'Venue'   => 'Home of Ray & Carrie Faust',
	'Address' => '2191 Princeton Ave',
	'City'    => 'Saint Paul',
	'State'   => 'MN',
	'Zip'     => '55105',
	'Country' => 'United States',
) );

// A private home: keep the hosts' names out of the slug, and flag the venue
// so the theme drops it from the venue REST endpoints for non-members
// (tclas_private_venue_ids in inc/events-integration.php).
wp_update_post( array( 'ID' => $faust, 'post_name' => 'member-home-st-paul' ) );
update_post_meta( $faust, '_tclas_private_venue', 1 );
WP_CLI::success( 'Flagged the hosts\' home as a private venue.' );

$kleeschen_id = $tclas_event( 'prost-kleeschen-2026', array(
	'post_title'       => 'Prost, Kleeschen!',
	'post_content'     => <<<HTML
<p>In Luxembourg, December 6 belongs to Kleeschen — St. Nicholas, the patron saint who brings treats to good children. We're marking it the grown-up way.</p>
<p>Join fellow TCLAS members for a festive winter afternoon sampling a selection of Luxembourgish wines and ciders. A representative of Ansay International will lead the tasting.</p>
<p>We're gathering at a member's home in St. Paul. Space is limited to <strong>20 guests</strong>, so please RSVP. <em>Full?</em> <a href="/contact/">Get in touch</a> and we'll add you to the waitlist.</p>
HTML,
	'EventStartDate'   => '2026-12-06',
	'EventStartHour'   => '14',
	'EventStartMinute' => '30',
	'EventEndDate'     => '2026-12-06',
	'EventEndHour'     => '16',
	'EventEndMinute'   => '30',
	'Venue'            => array( 'VenueID' => $faust ),
) );

// Public description; venue + RSVP gated to members (see default-template.php
// and tclas_gate_rsvp_ajax in pmpro-integration.php).
update_post_meta( $kleeschen_id, '_tclas_members_only', 1 );
WP_CLI::success( 'Set _tclas_members_only on Prost, Kleeschen! (venue + RSVP gated).' );

$tclas_thumb( $kleeschen_id, 'prost-kleeschen-illustration' );
$tclas_excerpt( $kleeschen_id, 'Celebrate St. Nicholas Day with a tasting of Luxembourgish wines and ciders, led by Ansay International. Members only; 20 seats.' );
if ( $celebrations ) {
	wp_set_object_terms( $kleeschen_id, array( $celebrations->term_id ), 'tribe_events_cat' );
}

$existing_ticket = get_posts( array(
	'post_type'      => 'tribe_rsvp_tickets',
	'posts_per_page' => 1,
	'meta_key'       => '_tribe_rsvp_for_event',
	'meta_value'     => $kleeschen_id,
	'fields'         => 'ids',
) );
if ( $existing_ticket ) {
	WP_CLI::log( "RSVP ticket already exists (ID {$existing_ticket[0]}) — skipping." );
} else {
	$ticket_id = Tribe__Tickets__RSVP::get_instance()->ticket_add( $kleeschen_id, array(
		'ticket_name'             => 'Prost, Kleeschen! RSVP',
		'ticket_description'      => 'Free for TCLAS members. Please RSVP for each person attending.',
		'ticket_show_description' => 1,
		'ticket_end_date'         => '2026-12-04',
		'ticket_end_time'         => '23:59:00',
		'tribe-ticket'            => array(
			'capacity'  => 20,
			'not_going' => 'no',
		),
	) );
	if ( ! $ticket_id ) {
		WP_CLI::error( 'Could not create RSVP ticket.' );
	}
	WP_CLI::success( "Created RSVP ticket (ID {$ticket_id}, capacity 20, closes Dec 4)." );
}

// ── RSVP confirmation email: generic text for every RSVP event ──────────────
// Subject ("Ugemellt! …") and heading ("Moien {attendee_name} — you're in!")
// already work for any event and are left as they are.
tribe_update_option(
	'tec-tickets-emails-rsvp-additional-content',
	'<p>If your plans change, please let us know through <a href="https://twincities.lu/contact/">twincities.lu/contact</a> so we can offer your seat to someone on the waitlist.</p><p>Bis geschwënn — see you soon!</p>'
);
WP_CLI::success( 'Replaced the Schwätzt mat! text in the RSVP confirmation email.' );

WP_CLI::log( 'Done. Verify: /events/ hero, Prost, Kleeschen! logged out (no address, no RSVP form) and as a member (both visible).' );
