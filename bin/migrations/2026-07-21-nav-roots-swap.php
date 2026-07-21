<?php
/**
 * Migration: nav axis swap + ancestral map entry point (2026-07-21).
 *
 * Reframes the two dropdowns from past-vs-present to places-vs-people
 * (Matthew, 2026-07-21):
 *   - "Luxembourg-Minnesota history" moves Our roots → LUX + MSP
 *     (the page is about the LUX↔MSP connection; matches the label)
 *   - "Groups and events" moves LUX + MSP → Our roots
 *     (diaspora orgs across North America; sibling of the places map)
 *   - Adds "Ancestral map" under Our roots → /ancestry/. Dropdown parents
 *     are <button> toggles (TCLAS_Nav_Walker), so /ancestry/ — the public
 *     map landing, which upsells members to the full gated map — was
 *     unreachable from the nav. Mirrors the "Stats and facts" pattern
 *     (child item linking to the parent's own page).
 *
 * Finishes by renumbering the whole menu into a canonical order, because
 * local and prod item positions have drifted (adds always appended). Items
 * are located by linked page (never db_id); items not in the canonical list
 * (e.g. local-only Surname finder) keep their position, which leaves them
 * last within their dropdown.
 *
 * Run:  bin/migrate.sh bin/migrations/2026-07-21-nav-roots-swap.php
 *       bin/migrate.sh --prod bin/migrations/2026-07-21-nav-roots-swap.php
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

$menu = wp_get_nav_menu_object( 'primary-navigation' );
if ( ! $menu ) {
	WP_CLI::error( 'Menu "primary-navigation" not found.' );
}

/**
 * Find the menu item linking to a page path. Two items may link to the same
 * page (a top-level toggle and its first child), so $top_level disambiguates.
 */
function tclas_nav21_item_for_path( array $items, string $path, ?bool $top_level = null ): ?WP_Post {
	$page = get_page_by_path( $path );
	if ( ! $page ) {
		return null;
	}
	foreach ( $items as $i ) {
		if ( 'post_type' !== $i->type || (int) $i->object_id !== $page->ID ) {
			continue;
		}
		if ( null !== $top_level && ( 0 === (int) $i->menu_item_parent ) !== $top_level ) {
			continue;
		}
		return $i;
	}
	return null;
}

function tclas_nav21_update( WP_Term $menu, WP_Post $item, array $overrides ): void {
	$res = wp_update_nav_menu_item( $menu->term_id, $item->db_id, array_merge( [
		'menu-item-title'     => $item->title,
		'menu-item-object'    => $item->object,
		'menu-item-object-id' => $item->object_id,
		'menu-item-type'      => $item->type,
		'menu-item-url'       => $item->url,
		'menu-item-status'    => 'publish',
		'menu-item-parent-id' => (int) $item->menu_item_parent,
		'menu-item-position'  => $item->menu_order,
	], $overrides ) );
	if ( is_wp_error( $res ) ) {
		WP_CLI::error( "Updating \"{$item->title}\": " . $res->get_error_message() );
	}
}

$items = wp_get_nav_menu_items( $menu->term_id );
$lux   = tclas_nav21_item_for_path( $items, 'msp-lux', true );
$roots = tclas_nav21_item_for_path( $items, 'ancestry', true );
if ( ! $lux || ! $roots ) {
	WP_CLI::error( 'Could not locate the msp-lux / ancestry top-level items in the menu.' );
}

// ── Reparent: history → LUX + MSP, groups → Our roots ───────────────────────
foreach ( [
	[ 'msp-lux/history', $lux, 'LUX + MSP' ],
	[ 'msp-lux/groups', $roots, 'Our roots' ],
] as [ $path, $parent, $parent_label ] ) {
	$item = tclas_nav21_item_for_path( $items, $path );
	if ( ! $item ) {
		WP_CLI::warning( "No menu item links to /{$path}/ — skipping reparent." );
		continue;
	}
	if ( (int) $item->menu_item_parent === (int) $parent->db_id ) {
		WP_CLI::log( "\"{$item->title}\" already under {$parent_label}." );
		continue;
	}
	tclas_nav21_update( $menu, $item, [ 'menu-item-parent-id' => (int) $parent->db_id ] );
	WP_CLI::success( "Moved \"{$item->title}\" under {$parent_label}." );
}

// ── Add "Ancestral map" child under Our roots → /ancestry/ ──────────────────
if ( tclas_nav21_item_for_path( $items, 'ancestry', false ) ) {
	WP_CLI::log( '"Ancestral map" already in menu.' );
} else {
	$page = get_page_by_path( 'ancestry' );
	$res  = wp_update_nav_menu_item( $menu->term_id, 0, [
		'menu-item-title'     => 'Ancestral map',
		'menu-item-object'    => 'page',
		'menu-item-object-id' => $page->ID,
		'menu-item-type'      => 'post_type',
		'menu-item-status'    => 'publish',
		'menu-item-parent-id' => (int) $roots->db_id,
	] );
	if ( is_wp_error( $res ) ) {
		WP_CLI::error( 'Adding "Ancestral map": ' . $res->get_error_message() );
	}
	WP_CLI::success( 'Added "Ancestral map" under Our roots.' );
}

// ── Renumber into canonical order ───────────────────────────────────────────
// Refetch: parents and the new item are stale in $items.
$items = wp_get_nav_menu_items( $menu->term_id );

// [path, top-level?] — null path matches the one custom-link item (Events).
$canonical = [
	[ 'join', true ],
	[ null, true ],
	[ 'msp-lux', true ],
	[ 'msp-lux', false ],          // Stats and facts
	[ 'msp-lux/culture', false ],
	[ 'msp-lux/language', false ],
	[ 'msp-lux/history', false ],
	[ 'ancestry', true ],
	[ 'ancestry', false ],         // Ancestral map
	[ 'citizenship', false ],
	[ 'msp-lux/places', false ],
	[ 'msp-lux/groups', false ],
	[ 'about', true ],
	[ 'contact', true ],
];

$position = 0;
foreach ( $canonical as [ $path, $top_level ] ) {
	if ( null === $path ) {
		$item = null;
		foreach ( $items as $i ) {
			if ( 'custom' === $i->type ) {
				$item = $i;
				break;
			}
		}
	} else {
		$item = tclas_nav21_item_for_path( $items, $path, $top_level );
	}
	if ( ! $item ) {
		WP_CLI::warning( 'No menu item for [' . ( $path ?? 'custom' ) . '] — skipping position.' );
		continue;
	}
	$position++;
	if ( (int) $item->menu_order === $position ) {
		continue;
	}
	tclas_nav21_update( $menu, $item, [ 'menu-item-position' => $position ] );
	WP_CLI::log( "Position {$position}: {$item->title}" );
}

WP_CLI::log( 'Done. Spot-check both dropdowns on the front end (logged out and as a member).' );
