<?php
/**
 * Migration: IA rethink nav changes (2026-07-17).
 *
 * Restructures the primary navigation per the 2026-07-10 IA:
 *   - "MSP + LUX"  → "Luxembourg & Us"   (present-day Luxembourg)
 *   - "Ancestry"   → "Your Roots"        (the past / heritage)
 *   - "Luxembourg-Minnesota history" moves under Your Roots
 *   - "Citizenship" folds from top level into Your Roots
 *   - Adds: Luxembourgers in North America (Your Roots),
 *           Groups & events (Luxembourg & Us)
 *
 * Surname finder HELD BACK (2026-07-20): launches in the fall with the
 * expanded Gonner dataset — add its nav item in that release's migration.
 *
 * Items are located by the page they link to (never by db_id), so this is
 * robust to prod-side menu edits. New items are only added if no item in
 * the menu already links to that page.
 *
 * PREREQUISITE: run 2026-07-17-july-release-content.php first — the three
 * new nav items link to pages that migration creates.
 *
 * Run:  bin/migrate.sh bin/migrations/2026-07-17-ia-nav.php
 *       bin/migrate.sh --prod bin/migrations/2026-07-17-ia-nav.php
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

$menu = wp_get_nav_menu_object( 'primary-navigation' );
if ( ! $menu ) {
	WP_CLI::error( 'Menu "primary-navigation" not found.' );
}
$items = wp_get_nav_menu_items( $menu->term_id );

/**
 * Find the menu item linking to a page path (post_type items only).
 */
function tclas_nav_item_for_path( array $items, string $path ): ?WP_Post {
	$page = get_page_by_path( $path );
	if ( ! $page ) {
		return null;
	}
	foreach ( $items as $i ) {
		if ( 'post_type' === $i->type && (int) $i->object_id === $page->ID ) {
			return $i;
		}
	}
	return null;
}

$lux_us = tclas_nav_item_for_path( $items, 'msp-lux' );
$roots  = tclas_nav_item_for_path( $items, 'ancestry' );
if ( ! $lux_us || ! $roots ) {
	WP_CLI::error( 'Could not locate the msp-lux / ancestry top-level items in the menu.' );
}

// ── Renames ──────────────────────────────────────────────────────────────────
foreach ( [
	[ $lux_us, 'Luxembourg & Us' ],
	[ $roots, 'Your Roots' ],
] as [ $item, $new_title ] ) {
	if ( $item->title !== $new_title ) {
		wp_update_nav_menu_item( $menu->term_id, $item->db_id, [
			'menu-item-title'     => $new_title,
			'menu-item-object'    => $item->object,
			'menu-item-object-id' => $item->object_id,
			'menu-item-type'      => $item->type,
			'menu-item-status'    => 'publish',
			'menu-item-parent-id' => (int) $item->menu_item_parent,
			'menu-item-position'  => $item->menu_order,
		] );
		WP_CLI::success( "Renamed \"{$item->title}\" → \"{$new_title}\"." );
	} else {
		WP_CLI::log( "\"{$new_title}\" already named." );
	}
}

// ── Reparent history + citizenship under Your Roots ─────────────────────────
foreach ( [ 'msp-lux/history', 'citizenship' ] as $path ) {
	$item = tclas_nav_item_for_path( $items, $path );
	if ( ! $item ) {
		WP_CLI::warning( "No menu item links to /{$path}/ — skipping reparent." );
		continue;
	}
	if ( (int) $item->menu_item_parent === (int) $roots->db_id ) {
		WP_CLI::log( "/{$path}/ already under Your Roots." );
		continue;
	}
	wp_update_nav_menu_item( $menu->term_id, $item->db_id, [
		'menu-item-title'     => $item->title,
		'menu-item-object'    => $item->object,
		'menu-item-object-id' => $item->object_id,
		'menu-item-type'      => $item->type,
		'menu-item-status'    => 'publish',
		'menu-item-parent-id' => (int) $roots->db_id,
		'menu-item-position'  => $item->menu_order,
	] );
	WP_CLI::success( "Moved \"{$item->title}\" under Your Roots." );
}

// ── New items ────────────────────────────────────────────────────────────────
$new_items = [
	[ 'msp-lux/places', 'Luxembourgers in North America', $roots->db_id ],
	[ 'msp-lux/groups', 'Groups & events',                $lux_us->db_id ],
];
foreach ( $new_items as [ $path, $title, $parent_db_id ] ) {
	if ( tclas_nav_item_for_path( $items, $path ) ) {
		WP_CLI::log( "\"{$title}\" already in menu." );
		continue;
	}
	$page = get_page_by_path( $path );
	if ( ! $page ) {
		WP_CLI::error( "Page /{$path}/ not found — run 2026-07-17-july-release-content.php first." );
	}
	$res = wp_update_nav_menu_item( $menu->term_id, 0, [
		'menu-item-title'     => $title,
		'menu-item-object'    => 'page',
		'menu-item-object-id' => $page->ID,
		'menu-item-type'      => 'post_type',
		'menu-item-status'    => 'publish',
		'menu-item-parent-id' => (int) $parent_db_id,
	] );
	if ( is_wp_error( $res ) ) {
		WP_CLI::error( "Adding \"{$title}\": " . $res->get_error_message() );
	}
	WP_CLI::success( "Added \"{$title}\"." );
}

WP_CLI::log( 'Done. Spot-check both dropdowns on the front end (logged out).' );
