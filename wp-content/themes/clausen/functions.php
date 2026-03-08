<?php
/**
 * Clausen v1.2 — functions.php
 * Ciel Bleu · Twin Cities Luxembourg American Society
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ── Constants ──────────────────────────────────────────────────────────────
define( 'TCLAS_VERSION', '1.2.0' );
define( 'TCLAS_DIR',     get_template_directory() );
define( 'TCLAS_URI',     get_template_directory_uri() );
define( 'TCLAS_ASSETS',  TCLAS_URI . '/assets' );

// ── Load modules ──────────────────────────────────────────────────────────
$tclas_modules = [
	'inc/setup.php',
	'inc/enqueue.php',
	'inc/nav-walkers.php',
	'inc/template-functions.php',
	'inc/post-types.php',
	'inc/taxonomies.php',
	'inc/accessibility.php',
	'inc/acf-fields.php',
	'inc/pmpro-integration.php',
	'inc/brevo-integration.php',
	'inc/givewp-integration.php',
	'inc/events-integration.php',
	'inc/member-hub.php',
	'inc/national-day.php',
	'inc/referral.php',
	// ── "How Are We Connected" feature ──────────────────────────────────
	'inc/connection-data.php',    // canonical commune + surname data
	'inc/connections.php',        // normalisation engine, matching, AJAX, cron
	// ── Ancestral Commune Map ────────────────────────────────────────────
	'inc/commune-data.php',       // 534 villages from official LU place-name index
	'inc/ancestor-map.php',       // [tclas_ancestor_map] shortcode + Leaflet assets
	// ── Content filters ──────────────────────────────────────────────────
	'inc/ltz-tagger.php',         // auto-wrap Luxembourgish terms in <span lang="lb">
	// ── Commune profile API integrations ─────────────────────────────────
	'inc/lod-audio.php',          // LOD.lu pronunciation audio + Forvo fallback
	// ── Member profiles & directory ───────────────────────────────────────
	'inc/member-profiles.php',    // profile helpers, rewrite, photo upload, founding badge
	'inc/member-badges.php',      // member badge registry: founding, board, bierger/citizen
	// ── Permanent redirects ───────────────────────────────────────────────
	'inc/redirects.php',          // 301s: /quiz/, /directory/, /profile/, /member-hub/ancestral-map/
	// ── Newsletter admin + routing ────────────────────────────────────────
	'inc/newsletter-admin.php',   // Issues dashboard, Posts list column/filter, ACF helpers
	'inc/newsletter-rewrite.php', // /newsletter/issue/{YYYY-MM}/ virtual URL
];

foreach ( $tclas_modules as $module ) {
	$path = TCLAS_DIR . '/' . $module;
	if ( file_exists( $path ) ) {
		require_once $path;
	}
}
