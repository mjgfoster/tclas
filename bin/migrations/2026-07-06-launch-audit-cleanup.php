<?php
/**
 * Migration: launch-audit + forum-removal DB changes (2026-07-06).
 *
 * Companion to theme commits 64fb733…4c8aa28 on fix/launch-audit-tier2.
 * Idempotent — every step verifies state before changing it.
 *
 * What it does:
 *   1. Citizenship page: fix the two next-steps texts (forum mention →
 *      "member community"; processing times → per-pathway 4–6 / 12–24 months)
 *   2. Designate the Privacy Policy page (Settings → Privacy), which makes the
 *      footer + cookie-banner privacy links render
 *   3. Trash orphaned PMPro default pages (membership-levels, your-profile)
 *      and the empty "news" page — trashed, not deleted, so it's reversible
 *   4. Remove bbPress leftovers (forum/topic/reply posts, _bbp_* options)
 *
 * NOT done here (run manually, in this order, after this migration):
 *   wp plugin delete bbpress pmpro-bbpress
 *   wp plugin uninstall complianz-gdpr siteground-migrator --deactivate
 *   (optional) drop leftover cmplz/shortpixel/wpforms tables — see the
 *   consolidated checklist; take a backup first.
 *
 * Run:  bin/migrate.sh bin/migrations/2026-07-06-launch-audit-cleanup.php
 *       bin/migrate.sh --prod bin/migrations/2026-07-06-launch-audit-cleanup.php
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

// ── 1. Citizenship next-steps text ──────────────────────────────────────────

$cit = get_page_by_path( 'citizenship' );
if ( ! $cit ) {
	WP_CLI::warning( 'Citizenship page not found — skipping step texts.' );
} else {
	$canonical_times = "Applications are processed by Luxembourg's SCAS (Service Central d'Assistance Sociale). Processing times vary by pathway — plan for about 4 to 6 months for Article 23 (maternal-line) cases and 12 to 24 months for Article 7 (direct descent). The consulate will guide you through required forms and notarization.";

	for ( $i = 0; $i < 10; $i++ ) {
		$key = "cit_next_steps_{$i}_step_body";
		$val = get_post_meta( $cit->ID, $key, true );
		if ( ! is_string( $val ) || '' === $val ) {
			continue;
		}

		// 1a. Forum mention → member community.
		if ( false !== strpos( $val, 'community forums' ) ) {
			$new = str_replace(
				'connect with experienced researchers in our community forums',
				'connect with experienced researchers in our member community',
				$val
			);
			update_post_meta( $cit->ID, $key, $new );
			WP_CLI::success( "$key: replaced forum mention." );
			$val = $new;
		}

		// 1b. Processing times → per-pathway breakdown (whatever variant is there).
		if ( false !== stripos( $val, 'SCAS' ) || false !== strpos( $val, 'Applications are processed' ) ) {
			if ( false !== strpos( $val, 'Article 23' ) ) {
				WP_CLI::log( "$key: processing times already per-pathway — OK." );
			} else {
				update_post_meta( $cit->ID, $key, $canonical_times );
				WP_CLI::success( "$key: set per-pathway processing times." );
			}
		}
	}
}

// ── 2. Privacy Policy page designation ──────────────────────────────────────

if ( (int) get_option( 'wp_page_for_privacy_policy' ) > 0 ) {
	WP_CLI::log( 'Privacy policy page already designated — OK.' );
} else {
	$candidates = get_posts( [
		'name'        => 'privacy',
		'post_type'   => 'page',
		'post_status' => 'publish',
		'numberposts' => -1,
	] );
	$policy = null;
	foreach ( $candidates as $p ) {
		// The real policy, not the member-hub "Privacy Settings" page.
		if ( 'Privacy Policy' === $p->post_title ) {
			$policy = $p;
			break;
		}
	}
	if ( $policy ) {
		update_option( 'wp_page_for_privacy_policy', $policy->ID );
		WP_CLI::success( "Designated page {$policy->ID} ({$policy->post_title}) as the Privacy Policy page." );
	} else {
		WP_CLI::warning( 'No published "Privacy Policy" page with slug "privacy" found — designate manually in Settings → Privacy.' );
	}
}

// ── 3. Trash orphaned pages ──────────────────────────────────────────────────

$orphans = [
	[ 'slug' => 'membership-levels', 'expect_content' => '[pmpro_levels]',              'unless_option' => 'pmpro_levels_page_id' ],
	[ 'slug' => 'your-profile',      'expect_content' => '[pmpro_member_profile_edit]', 'unless_option' => 'pmpro_member_profile_edit_page_id' ],
	[ 'slug' => 'news',              'expect_content' => '',                            'unless_option' => null ],
];

foreach ( $orphans as $o ) {
	// Lookup by slug (not path) — some of these are child pages.
	$found = get_posts( [
		'name'        => $o['slug'],
		'post_type'   => 'page',
		'post_status' => 'publish',
		'numberposts' => 1,
	] );
	$page = $found ? $found[0] : null;
	if ( ! $page ) {
		WP_CLI::log( "Page '{$o['slug']}': not found or not published — OK." );
		continue;
	}
	if ( trim( $page->post_content ) !== $o['expect_content'] ) {
		WP_CLI::warning( "Page '{$o['slug']}' ({$page->ID}): content isn't what this migration expects — left alone, review manually." );
		continue;
	}
	if ( $o['unless_option'] && (int) get_option( $o['unless_option'] ) === (int) $page->ID ) {
		WP_CLI::warning( "Page '{$o['slug']}' ({$page->ID}) is assigned in {$o['unless_option']} — left alone." );
		continue;
	}
	// Not referenced by any nav menu item?
	$in_menu = get_posts( [
		'post_type'   => 'nav_menu_item',
		'numberposts' => 1,
		'fields'      => 'ids',
		'meta_query'  => [ [ 'key' => '_menu_item_object_id', 'value' => (string) $page->ID ] ],
	] );
	if ( $in_menu ) {
		WP_CLI::warning( "Page '{$o['slug']}' ({$page->ID}) is linked from a nav menu — left alone." );
		continue;
	}
	wp_trash_post( $page->ID );
	WP_CLI::success( "Page '{$o['slug']}' ({$page->ID}) moved to trash." );
}

// ── 4. bbPress leftovers ────────────────────────────────────────────────────

$bbp_posts = get_posts( [
	'post_type'   => [ 'forum', 'topic', 'reply' ],
	'post_status' => 'any',
	'numberposts' => -1,
	'fields'      => 'ids',
] );
foreach ( $bbp_posts as $pid ) {
	wp_delete_post( $pid, true );
	WP_CLI::success( "Deleted bbPress post $pid." );
}
if ( ! $bbp_posts ) {
	WP_CLI::log( 'No bbPress posts — OK.' );
}

global $wpdb;
$bbp_options = $wpdb->get_col(
	"SELECT option_name FROM {$wpdb->options}
	 WHERE option_name LIKE '\_bbp\_%' OR option_name LIKE 'widget\_bbp\_%'"
);
foreach ( $bbp_options as $name ) {
	delete_option( $name );
	WP_CLI::success( "Deleted option $name." );
}
if ( ! $bbp_options ) {
	WP_CLI::log( 'No bbPress options — OK.' );
}

WP_CLI::success( 'Migration complete.' );
