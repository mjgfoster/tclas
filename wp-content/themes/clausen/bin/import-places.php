<?php
/**
 * Import vetted places into the Luxembourgers in North America map as DRAFTS.
 *
 * Run: wp eval-file wp-content/themes/clausen/bin/import-places.php
 *
 * Reads bin/data/places-import.json (array of objects: slug, title, lat, lng,
 * county, state, types[], excerpt, body, links[{link_label,link_url}],
 * vetting_note). Every post is created as a DRAFT — the public map only
 * queries published posts, so nothing appears until Matthew reviews and
 * publishes each entry. Idempotent: existing slugs (any status) are skipped,
 * so edits made in the block editor are never overwritten.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$json_path = get_template_directory() . '/bin/data/places-import.json';
if ( ! file_exists( $json_path ) ) {
	WP_CLI::error( "Missing $json_path" );
}

$rows = json_decode( file_get_contents( $json_path ), true );
if ( ! is_array( $rows ) ) {
	WP_CLI::error( 'places-import.json did not parse to an array.' );
}

$created = 0;
$skipped = 0;

foreach ( $rows as $p ) {
	$existing = get_page_by_path( $p['slug'], OBJECT, 'tclas_place' );
	if ( $existing ) {
		WP_CLI::log( "  – {$p['slug']} exists ({$existing->post_status}) — skipped." );
		$skipped++;
		continue;
	}

	$content = '';
	foreach ( (array) ( $p['body'] ?? [] ) as $para ) {
		$content .= '<!-- wp:paragraph --><p>' . $para . '</p><!-- /wp:paragraph -->' . "\n";
	}
	// Vetting note surfaces at the top of the draft for review, styled as an
	// editor-facing callout; delete it when publishing.
	if ( ! empty( $p['vetting_note'] ) ) {
		$content = '<!-- wp:paragraph --><p><strong>[REVIEW NOTE — delete before publishing]</strong> '
			. esc_html( $p['vetting_note'] ) . '</p><!-- /wp:paragraph -->' . "\n" . $content;
	}

	$post_id = wp_insert_post( [
		'post_type'    => 'tclas_place',
		'post_status'  => 'draft',
		'post_title'   => $p['title'],
		'post_name'    => $p['slug'],
		'post_content' => $content,
		'post_excerpt' => $p['excerpt'] ?? '',
	] );

	if ( is_wp_error( $post_id ) ) {
		WP_CLI::warning( "  ! {$p['slug']}: " . $post_id->get_error_message() );
		continue;
	}

	update_field( 'field_place_lat', $p['lat'], $post_id );
	update_field( 'field_place_lng', $p['lng'], $post_id );
	update_field( 'field_place_county', $p['county'] ?? '', $post_id );
	update_field( 'field_place_state', $p['state'], $post_id );
	update_field( 'field_place_links', $p['links'] ?? [], $post_id );
	wp_set_object_terms( $post_id, (array) ( $p['types'] ?? [] ), 'tclas_place_type' );

	WP_CLI::log( "  + {$p['title']} (draft $post_id)" );
	$created++;
}

WP_CLI::success( "Import done: $created created, $skipped skipped. All new entries are DRAFTS — review under Places in wp-admin." );
