<?php
/**
 * Seed the Luxembourg in America map: the /places/ page + starter places.
 *
 * Run: wp eval-file wp-content/themes/clausen/bin/seed-places.php
 *
 * Idempotent — existing posts (matched by slug) are left untouched, so
 * Matthew's edits in the block editor are never overwritten. Starter history
 * paragraphs are intentionally brief; they're a scaffold for fuller write-ups.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── The map page at /msp-lux/places/ ──────────────────────────────────────────

$msp_lux = get_page_by_path( 'msp-lux' );
$page    = get_page_by_path( $msp_lux ? 'msp-lux/places' : 'places' );
if ( ! $page ) {
	$page_id = wp_insert_post( [
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'Luxembourgers in North America',
		'post_name'    => 'places',
		'post_parent'  => $msp_lux ? $msp_lux->ID : 0, // nest under MSP + LUX
		'post_content' => '<!-- wp:paragraph --><p>Luxembourg is small; its American footprint is not. Across the Upper Midwest and beyond, Luxembourgish immigrants founded towns, built stone churches and barns, and left their name on the map itself. Explore the places below — and if you know one we\'re missing, tell us.</p><!-- /wp:paragraph -->',
	] );
	update_post_meta( $page_id, '_wp_page_template', 'page-templates/page-lux-places.php' );
	WP_CLI::log( "Created /places/ page (ID $page_id)." );
} else {
	WP_CLI::log( 'Page /places/ already exists — skipped.' );
}

// ── Starter places ────────────────────────────────────────────────────────────

$places = [
	[
		'slug'    => 'belgium-wisconsin',
		'title'   => 'Belgium, Wisconsin',
		'lat'     => 43.4997,
		'lng'     => -87.8481,
		'county'  => 'Ozaukee County',
		'state'   => 'Wisconsin',
		'types'   => [ 'luxembourgish-roots' ],
		'excerpt' => 'Heart of America\'s largest Luxembourgish settlement region — and, despite the name, home of the Luxembourg American Cultural Society.',
		'content' => <<<'HTML'
<!-- wp:paragraph --><p>Don't let the name fool you: Belgium, Wisconsin sits at the center of one of the largest concentrations of Luxembourgish immigrants and their descendants in the United States. Luxembourgers began settling this stretch of Ozaukee County in the 1840s, and the surrounding communities — Lake Church, Dacada, Holy Cross — remain thick with Luxembourgish family names to this day.</p><!-- /wp:paragraph -->
<!-- wp:paragraph --><p>Belgium is home to the Luxembourg American Cultural Society &amp; Center, whose Roots and Leaves Immigration Museum occupies the Mamer-Hansen stone barn, built in 1872 by immigrant Jacob Mamer. Each August the village hosts Luxembourg Fest, drawing visitors from across the country — and from the Grand Duchy itself.</p><!-- /wp:paragraph -->
HTML,
		'links'   => [
			[ 'link_label' => 'Luxembourg American Cultural Society & Center', 'link_url' => 'https://www.lacs.lu/' ],
		],
	],
	[
		'slug'    => 'rollingstone-minnesota',
		'title'   => 'Rollingstone, Minnesota',
		'lat'     => 44.0972,
		'lng'     => -91.8171,
		'county'  => 'Winona County',
		'state'   => 'Minnesota',
		'types'   => [ 'founded-by-luxembourgers', 'luxembourgish-roots' ],
		'excerpt' => 'A Winona County village founded by Luxembourgish immigrants, with a museum devoted entirely to its Luxembourg heritage.',
		'content' => <<<'HTML'
<!-- wp:paragraph --><p>Tucked into the bluff country of southeastern Minnesota, Rollingstone was founded by Luxembourgish immigrants in the 1850s and has stayed remarkably true to those roots — for generations the village was among the most thoroughly Luxembourgish communities in the state.</p><!-- /wp:paragraph -->
<!-- wp:paragraph --><p>The Rollingstone-Luxembourg Heritage Museum, housed in the village's 1899–1900 former city hall, is believed to be the first museum in the United States established specifically to preserve and celebrate Luxembourgish culture. The building was added to the National Register of Historic Places in 2021.</p><!-- /wp:paragraph -->
HTML,
		'links'   => [
			[ 'link_label' => 'Rollingstone-Luxembourg Heritage Museum (Facebook)', 'link_url' => 'https://www.facebook.com/rollingstonemuseum/' ],
			[ 'link_label' => 'Museum profile — Explore Minnesota', 'link_url' => 'https://www.exploreminnesota.com/profile/rollingstone-luxembourg-heritage-museum/2655' ],
		],
	],
	[
		'slug'    => 'st-donatus-iowa',
		'title'   => 'St. Donatus, Iowa',
		'lat'     => 42.3614,
		'lng'     => -90.5424,
		'county'  => 'Jackson County',
		'state'   => 'Iowa',
		'types'   => [ 'founded-by-luxembourgers', 'luxembourgish-roots' ],
		'excerpt' => 'One of America\'s earliest Luxembourgish settlements, famed for its limestone architecture and the first outdoor Way of the Cross in the United States.',
		'content' => <<<'HTML'
<!-- wp:paragraph --><p>St. Donatus is one of the oldest Luxembourgish settlements in America. The first Luxembourger, John Noel, arrived in 1838; Peter Gehlen followed in 1846 and founded the village, naming it for Saint Donatus, patron saint of protection from lightning and storms. Fleeing poverty and famine at home, Luxembourgish families made this corner of Jackson County a "little Luxembourg" on the Mississippi.</p><!-- /wp:paragraph -->
<!-- wp:paragraph --><p>Some eighteen original limestone buildings from the 1840s and '50s still stand, including the Gehlen House and Barn — one of Iowa's few stone barns, listed on the National Register of Historic Places. Behind St. Donatus Catholic Church winds the Outdoor Way of the Cross, completed in the early 1860s under Father Michael Flammang and recognized as the first of its kind in the United States; the Pietà Chapel at its crest is modeled on the Chapelle du Bildchen in Vianden, Luxembourg.</p><!-- /wp:paragraph -->
HTML,
		'links'   => [
			[ 'link_label' => 'Gehlen House Inn & Barn — explore St. Donatus', 'link_url' => 'https://www.gehlenhouseandbarn.com/explore' ],
		],
	],
];

foreach ( $places as $p ) {
	$existing = get_page_by_path( $p['slug'], OBJECT, 'tclas_place' );
	if ( $existing ) {
		WP_CLI::log( "Place {$p['slug']} already exists — skipped." );
		continue;
	}

	$post_id = wp_insert_post( [
		'post_type'    => 'tclas_place',
		'post_status'  => 'publish',
		'post_title'   => $p['title'],
		'post_name'    => $p['slug'],
		'post_content' => $p['content'],
		'post_excerpt' => $p['excerpt'],
	] );

	if ( is_wp_error( $post_id ) ) {
		WP_CLI::warning( "Failed to create {$p['slug']}: " . $post_id->get_error_message() );
		continue;
	}

	// ACF fields via field keys so the _meta references are written too.
	update_field( 'field_place_lat', $p['lat'], $post_id );
	update_field( 'field_place_lng', $p['lng'], $post_id );
	update_field( 'field_place_county', $p['county'], $post_id );
	update_field( 'field_place_state', $p['state'], $post_id );
	update_field( 'field_place_links', $p['links'], $post_id );

	wp_set_object_terms( $post_id, $p['types'], 'tclas_place_type' );

	WP_CLI::log( "Created {$p['title']} (ID $post_id)." );
}

flush_rewrite_rules();
WP_CLI::success( 'Luxembourg in America seed complete. Rewrites flushed.' );
