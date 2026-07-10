<?php
/**
 * Seed the surname explorer: the /ancestry/surnames/ page + one tclas_surname
 * post per cluster from the connections engine's curated table.
 *
 * Run: wp eval-file wp-content/themes/clausen/bin/seed-surnames.php
 *
 * Idempotent — existing page/surnames (matched by slug) are skipped, so
 * enrichments made in wp-admin (attested places, sources, write-ups) are
 * never overwritten. Seeded entries are PUBLISHED: cluster labels, variants,
 * and notes are public-safe editorial data (diaspora research, not member
 * data). Phase B growth (Gonner 1889, ANLux inventory) happens in wp-admin —
 * no code, no deploys.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── The finder page at /ancestry/surnames/ ────────────────────────────────────

$ancestry = get_page_by_path( 'ancestry' );
$page     = get_page_by_path( $ancestry ? 'ancestry/surnames' : 'surnames' );
if ( ! $page ) {
	$page_id = wp_insert_post( [
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'Ass Ären Numm lëtzebuergesch?',
		'post_name'    => 'surnames',
		'post_parent'  => $ancestry ? $ancestry->ID : 0, // nest under Ancestry
		'post_content' => '<!-- wp:paragraph --><p>Is your name Luxembourgish? Schmitt became Smith, Becker became Baker, Müller became Miller — and thousands of families lost the thread. Type a family name below and see whether it appears among Luxembourg\'s emigrants to America.</p><!-- /wp:paragraph -->',
	] );
	update_post_meta( $page_id, '_wp_page_template', 'page-templates/page-surnames.php' );
	WP_CLI::log( "Created surname finder page (ID $page_id)." );
} else {
	WP_CLI::log( 'Surname finder page already exists — skipped.' );
}

// ── Surname entries from the connections-engine cluster table ─────────────────

if ( ! function_exists( 'tclas_surname_clusters' ) ) {
	WP_CLI::error( 'tclas_surname_clusters() not available — is connection-data.php loaded?' );
}

$created = 0;
$skipped = 0;

foreach ( tclas_surname_clusters() as $head => $cluster ) {
	$slug = sanitize_title( $head );
	if ( get_page_by_path( $slug, OBJECT, 'tclas_surname' ) ) {
		$skipped++;
		continue;
	}

	$post_id = wp_insert_post( [
		'post_type'   => 'tclas_surname',
		'post_status' => 'publish',
		'post_title'  => $cluster['label'],
		'post_name'   => $slug,
	] );

	if ( is_wp_error( $post_id ) ) {
		WP_CLI::warning( "  ! $slug: " . $post_id->get_error_message() );
		continue;
	}

	// Variants: display-capitalize the pre-normalized cluster strings; put the
	// canonical label first, skipping its own normalized form to avoid a dupe.
	$label_norm = tclas_normalize_string( $cluster['label'] );
	$rows       = [ [ 'variant' => $cluster['label'] ] ];
	foreach ( $cluster['variants'] as $v ) {
		if ( $v === $label_norm ) {
			continue;
		}
		$rows[] = [ 'variant' => ucfirst( $v ) ];
	}

	update_field( 'field_surname_variants', $rows, $post_id );
	if ( ! empty( $cluster['notes'] ) ) {
		update_field( 'field_surname_note', $cluster['notes'], $post_id );
	}
	update_field( 'field_surname_shared', 1, $post_id ); // honesty note on by default
	update_field( 'field_surname_sources', [
		[ 'source_label' => 'TCLAS member research & Minnesota Historical Society immigration records', 'source_url' => '' ],
	], $post_id );

	$created++;
}

flush_rewrite_rules();
WP_CLI::success( "Surname seed complete: $created created, $skipped skipped. Rewrites flushed." );
