<?php
/**
 * Seed the Groups & Events page: /msp-lux/groups/ + starter organizations.
 *
 * Run: wp eval-file wp-content/themes/clausen/bin/seed-orgs.php
 *
 * Idempotent — existing page/orgs (matched by slug) are skipped. All org
 * links were web-verified 2026-07-10. No events are seeded: the sidebar
 * self-hides until a future-dated tclas_ext_event is published.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── The page at /msp-lux/groups/ ──────────────────────────────────────────────

$msp_lux = get_page_by_path( 'msp-lux' );
$page    = get_page_by_path( $msp_lux ? 'msp-lux/groups' : 'groups' );
if ( ! $page ) {
	$page_id = wp_insert_post( [
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'Luxembourg Groups & Events',
		'post_name'    => 'groups',
		'post_parent'  => $msp_lux ? $msp_lux->ID : 0,
		'post_content' => '<!-- wp:paragraph --><p>TCLAS is one branch of a bigger family tree. Across North America, Luxembourg societies, museums, and clubs keep the culture alive — here\'s where to find them. Know a group we\'re missing? Tell us.</p><!-- /wp:paragraph -->',
	] );
	update_post_meta( $page_id, '_wp_page_template', 'page-templates/page-lux-groups.php' );
	WP_CLI::log( "Created Groups & Events page (ID $page_id)." );
} else {
	WP_CLI::log( 'Groups & Events page already exists — skipped.' );
}

// ── Starter organizations ─────────────────────────────────────────────────────

$orgs = [
	[
		'slug'    => 'luxembourg-american-cultural-society',
		'title'   => 'Luxembourg American Cultural Society & Center',
		'lat'     => 43.4997, 'lng' => -87.8481,
		'city'    => 'Belgium, Wisconsin',
		'website' => 'https://www.lacs.lu/',
		'blurb'   => 'Cultural center and home of the Roots and Leaves Immigration Museum; hosts the annual Luxembourg Fest each August.',
	],
	[
		'slug'    => 'luxembourg-brotherhood-of-america',
		'title'   => 'Luxembourg Brotherhood of America',
		'lat'     => 41.99, 'lng' => -87.66,
		'city'    => 'Chicago, Illinois',
		'website' => 'https://www.luxbrotherhood.org/',
		'blurb'   => 'America\'s oldest Luxembourg mutual-aid society, founded 1887, with active sections around Chicagoland.',
	],
	[
		'slug'    => 'luxie-club-aurora',
		'title'   => 'American Luxemburger Independent Club ("Luxie Club")',
		'lat'     => 41.76, 'lng' => -88.32,
		'city'    => 'Aurora, Illinois',
		'website' => 'https://luxembourglegacy.com/settlement-spotlight-aurora-illinois/',
		'blurb'   => 'Founded 1890; its 1917 Luxemburger Hall is the only functioning Luxembourg clubhouse in the United States.',
	],
	[
		'slug'    => 'rollingstone-luxembourg-heritage-museum',
		'title'   => 'Rollingstone-Luxembourg Heritage Museum',
		'lat'     => 44.0972, 'lng' => -91.8171,
		'city'    => 'Rollingstone, Minnesota',
		'website' => 'https://www.facebook.com/rollingstonemuseum/',
		'blurb'   => 'Believed to be the first US museum devoted to Luxembourg heritage, in the village\'s 1899 city hall.',
	],
	[
		'slug'    => 'embassy-of-luxembourg-washington',
		'title'   => 'Embassy of Luxembourg in Washington, D.C.',
		'lat'     => 38.9115, 'lng' => -77.0509,
		'city'    => 'Washington, D.C.',
		'website' => 'https://washington.mae.lu/en.html',
		'blurb'   => 'The Grand Duchy\'s diplomatic home in the US; publishes the "Luxembourgers in the United States" heritage series.',
	],
	[
		'slug'    => 'luxembourg-legacy',
		'title'   => 'Luxembourg Legacy',
		'lat'     => null, 'lng' => null,
		'city'    => 'Online',
		'website' => 'https://luxembourglegacy.com/',
		'blurb'   => 'Online project documenting Luxembourgish settlement and family history across America.',
	],
];

foreach ( $orgs as $o ) {
	$existing = get_page_by_path( $o['slug'], OBJECT, 'tclas_org' );
	if ( $existing ) {
		WP_CLI::log( "  – {$o['slug']} exists — skipped." );
		continue;
	}

	$post_id = wp_insert_post( [
		'post_type'   => 'tclas_org',
		'post_status' => 'publish',
		'post_title'  => $o['title'],
		'post_name'   => $o['slug'],
	] );

	if ( is_wp_error( $post_id ) ) {
		WP_CLI::warning( "  ! {$o['slug']}: " . $post_id->get_error_message() );
		continue;
	}

	if ( null !== $o['lat'] ) {
		update_field( 'field_org_lat', $o['lat'], $post_id );
		update_field( 'field_org_lng', $o['lng'], $post_id );
	}
	update_field( 'field_org_city', $o['city'], $post_id );
	update_field( 'field_org_website', $o['website'], $post_id );
	update_field( 'field_org_blurb', $o['blurb'], $post_id );

	WP_CLI::log( "  + {$o['title']} ($post_id)" );
}

WP_CLI::success( 'Groups & Events seed complete.' );
