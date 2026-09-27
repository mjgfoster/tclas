<?php
/**
 * Migration: donate links in the nav (2026-09-27).
 *
 * The donate page (/donate/, GiveWP form) shipped in v1.6 but nothing linked
 * to it. Adds a "Donate" item:
 *   - primary navigation, top level, directly after "Join" (Matthew: prominent
 *     for now, ahead of Kevin Wester's donation push; may be demoted later —
 *     just delete the item in Appearance → Menus)
 *   - footer "About" column (footer-organisation-links), last
 *
 * Idempotent: skips a menu that already has an item linking to the page.
 *
 * Run:  bin/migrate.sh bin/migrations/2026-09-27-donate-links.php
 *       bin/migrate.sh --prod bin/migrations/2026-09-27-donate-links.php
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

$donate = get_page_by_path( 'donate' );
if ( ! $donate || 'publish' !== $donate->post_status ) {
	WP_CLI::error( 'Published page "donate" not found.' );
}

/**
 * Add a top-level Donate item to a menu, positioned right after the item
 * linking to $after_path (or last if null). Later items shift down one.
 */
function tclas_donate27_add( string $menu_slug, WP_Post $donate, ?string $after_path ): void {
	$menu = wp_get_nav_menu_object( $menu_slug );
	if ( ! $menu ) {
		WP_CLI::error( "Menu \"{$menu_slug}\" not found." );
	}

	$items = wp_get_nav_menu_items( $menu->term_id );
	foreach ( $items as $i ) {
		if ( 'post_type' === $i->type && (int) $i->object_id === $donate->ID ) {
			WP_CLI::log( "{$menu_slug}: already has a Donate item (#{$i->db_id}), skipping." );
			return;
		}
	}

	$position = count( $items ) + 1;
	if ( $after_path ) {
		$after = get_page_by_path( $after_path );
		foreach ( $items as $i ) {
			if ( $after && 'post_type' === $i->type && (int) $i->object_id === $after->ID && 0 === (int) $i->menu_item_parent ) {
				$position = (int) $i->menu_order + 1;
				break;
			}
		}
		if ( $position === count( $items ) + 1 ) {
			WP_CLI::warning( "{$menu_slug}: \"{$after_path}\" item not found, appending Donate last." );
		}
	}

	// Make room: shift everything at or after the target position down one.
	foreach ( $items as $i ) {
		if ( (int) $i->menu_order >= $position ) {
			wp_update_post( [ 'ID' => $i->db_id, 'menu_order' => (int) $i->menu_order + 1 ] );
		}
	}

	$id = wp_update_nav_menu_item( $menu->term_id, 0, [
		'menu-item-title'     => __( 'Donate', 'tclas' ),
		'menu-item-object'    => 'page',
		'menu-item-object-id' => $donate->ID,
		'menu-item-type'      => 'post_type',
		'menu-item-status'    => 'publish',
		'menu-item-parent-id' => 0,
		'menu-item-position'  => $position,
	] );
	if ( is_wp_error( $id ) ) {
		WP_CLI::error( "{$menu_slug}: " . $id->get_error_message() );
	}
	WP_CLI::log( "{$menu_slug}: added Donate (#{$id}) at position {$position}." );
}

tclas_donate27_add( 'primary-navigation', $donate, 'join' );
tclas_donate27_add( 'footer-organisation-links', $donate, null );

WP_CLI::success( 'Donate links in place.' );
