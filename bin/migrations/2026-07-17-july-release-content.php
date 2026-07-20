<?php
/**
 * Migration: July 2026 release content (2026-07-17).
 *
 * Upserts everything the places-map / groups / surname-explorer features
 * need in the DB, from the JSON export alongside this script:
 *   - tclas_place_type + tclas_department terms
 *   - tclas_place (26 publish + 6 draft), tclas_org (6),
 *     tclas_ext_event (1 draft) posts with ACF meta and term assignments
 *   - the /msp-lux/places/ and /msp-lux/groups/ pages
 *
 * Surname finder HELD BACK (2026-07-20): the 40 tclas_surname posts and the
 * /ancestry/surnames/ page in the JSON are SKIPPED below. It launches in the
 * fall with the expanded Gonner dataset — re-export and lift the holdback
 * (or write that release's own migration) when it ships.
 *
 * ACF field groups are code-registered (inc/acf-fields.php), so meta rows
 * are all the fields need. No featured images exist on any of this content.
 *
 * Idempotent — every term/post/page is matched by slug (within its
 * taxonomy/post_type/parent) and updated in place if it already exists.
 * Existing prod-side edits to matched items WILL be overwritten with the
 * exported values; items added on prod that aren't in the export are
 * left alone.
 *
 * Run:  bin/migrate.sh bin/migrations/2026-07-17-july-release-content.php
 *       bin/migrate.sh --prod bin/migrations/2026-07-17-july-release-content.php
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

$data_file = __DIR__ . '/data/2026-07-17-july-release-content.json';
if ( ! file_exists( $data_file ) ) {
	WP_CLI::error( "Data file missing: {$data_file}" );
}
$data = json_decode( file_get_contents( $data_file ), true );
if ( ! is_array( $data ) || empty( $data['posts'] ) ) {
	WP_CLI::error( 'Data file unreadable or empty.' );
}

// ── Terms ────────────────────────────────────────────────────────────────────
$term_created = $term_existing = 0;
foreach ( $data['terms'] as $t ) {
	$existing = get_term_by( 'slug', $t['slug'], $t['taxonomy'] );
	if ( $existing ) {
		$term_existing++;
		continue;
	}
	$args = [ 'slug' => $t['slug'], 'description' => $t['description'] ];
	if ( ! empty( $t['parent_slug'] ) ) {
		$parent = get_term_by( 'slug', $t['parent_slug'], $t['taxonomy'] );
		if ( $parent ) {
			$args['parent'] = $parent->term_id;
		}
	}
	$res = wp_insert_term( $t['name'], $t['taxonomy'], $args );
	if ( is_wp_error( $res ) ) {
		WP_CLI::error( "Term {$t['taxonomy']}/{$t['slug']}: " . $res->get_error_message() );
	}
	$term_created++;
}
WP_CLI::success( "Terms: {$term_created} created, {$term_existing} already present." );

/**
 * Upsert one post (CPT or page) by slug, returning [ID, created?].
 */
function tclas_mig_upsert_post( array $item, string $post_type, int $parent_id = 0 ): array {
	$existing = get_posts( [
		'post_type'      => $post_type,
		'name'           => $item['slug'],
		'post_status'    => 'any',
		'posts_per_page' => 1,
		'post_parent'    => $parent_id ?: null,
		'fields'         => 'ids',
	] );

	$args = [
		'post_type'    => $post_type,
		'post_name'    => $item['slug'],
		'post_title'   => $item['title'],
		'post_status'  => $item['status'],
		'post_content' => $item['content'],
		'post_excerpt' => $item['excerpt'] ?? '',
		'menu_order'   => $item['menu_order'] ?? 0,
		'post_parent'  => $parent_id,
	];

	if ( $existing ) {
		$args['ID'] = $existing[0];
		$post_id    = wp_update_post( wp_slash( $args ), true );
		$created    = false;
	} else {
		$post_id = wp_insert_post( wp_slash( $args ), true );
		$created = true;
	}
	if ( is_wp_error( $post_id ) ) {
		WP_CLI::error( "{$post_type}/{$item['slug']}: " . $post_id->get_error_message() );
	}

	foreach ( $item['meta'] ?? [] as $k => $v ) {
		update_post_meta( $post_id, $k, $v );
	}
	foreach ( $item['terms'] ?? [] as $tax => $slugs ) {
		wp_set_object_terms( $post_id, $slugs, $tax );
	}

	return [ $post_id, $created ];
}

// ── CPT posts ────────────────────────────────────────────────────────────────
$hold_back_types = [ 'tclas_surname' ];
$hold_back_pages = [ 'surnames' ];

$counts  = [];
$skipped = 0;
foreach ( $data['posts'] as $item ) {
	if ( in_array( $item['post_type'], $hold_back_types, true ) ) {
		$skipped++;
		continue;
	}
	[ , $created ] = tclas_mig_upsert_post( $item, $item['post_type'] );
	$key = $item['post_type'] . ( $created ? ':created' : ':updated' );
	$counts[ $key ] = ( $counts[ $key ] ?? 0 ) + 1;
}
foreach ( $counts as $k => $n ) {
	WP_CLI::log( "  {$k} = {$n}" );
}
if ( $skipped ) {
	WP_CLI::log( "  HELD BACK (surname finder, fall launch): {$skipped} posts skipped" );
}
WP_CLI::success( 'CPT content done.' );

// ── Pages ────────────────────────────────────────────────────────────────────
foreach ( $data['pages'] as $item ) {
	if ( in_array( $item['slug'], $hold_back_pages, true ) ) {
		WP_CLI::log( "  page {$item['parent_path']}/{$item['slug']} HELD BACK (surname finder, fall launch)" );
		continue;
	}
	$parent = get_page_by_path( $item['parent_path'] );
	if ( ! $parent ) {
		WP_CLI::error( "Parent page '{$item['parent_path']}' not found for page '{$item['slug']}'." );
	}
	[ $pid, $created ] = tclas_mig_upsert_post( $item, 'page', $parent->ID );
	WP_CLI::log( sprintf( '  page %s/%s → ID %d (%s)', $item['parent_path'], $item['slug'], $pid, $created ? 'created' : 'updated' ) );
}
WP_CLI::success( 'Pages done.' );

flush_rewrite_rules();
WP_CLI::success( 'Rewrite rules flushed. Verify: /msp-lux/places/, /msp-lux/groups/. (/ancestry/surnames/ held back — should 404.)' );
