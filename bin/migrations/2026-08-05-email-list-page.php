<?php
/**
 * Migration: /email-list/ signup page (2026-08-05).
 *
 * Companion to the clausen `feature/email-list-signup` work
 * (inc/email-signup.php, assets/css/email-signup.css).
 *
 * Creates the public page that carries [tclas_email_signup] — the general
 * "hear from us" list. Deliberately NOT /newsletter/: that slug is reserved for
 * The Loon & the Lion when it launches this autumn, and mixing the two would
 * mean renaming a public URL later.
 *
 * Also repoints the newsletter-page signup fallback from the MailChimp hosted
 * form to /email-list/, which removes the last MailChimp link from the site.
 *
 * Idempotent — re-running finds the existing page by slug and leaves it alone.
 *
 * Run:  bin/migrate.sh bin/migrations/2026-08-05-email-list-page.php
 *       bin/migrate.sh --prod bin/migrations/2026-08-05-email-list-page.php
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

if ( ! function_exists( 'tclas_signup_page_slug' ) ) {
	WP_CLI::error( 'inc/email-signup.php is not loaded — deploy the theme before running this migration.' );
}

$slug = tclas_signup_page_slug();

// ── 1. The page ─────────────────────────────────────────────────────────────

$existing = get_page_by_path( $slug, OBJECT, 'page' );

$content = "<!-- wp:paragraph -->\n<p>Hear about events, gatherings, traditions, and stories from the Twin Cities Luxembourg-American community. We send a handful of emails a year — never more than we would want to receive ourselves.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:shortcode -->\n[tclas_email_signup]\n<!-- /wp:shortcode -->";

if ( $existing instanceof WP_Post ) {
	WP_CLI::log( "Page /{$slug}/ already exists (ID {$existing->ID}, {$existing->post_status}) — leaving content alone." );
	$page_id = $existing->ID;
} else {
	$page_id = wp_insert_post( [
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'Join our email list',
		'post_name'    => $slug,
		'post_content' => $content,
	], true );

	if ( is_wp_error( $page_id ) ) {
		WP_CLI::error( 'Could not create the page: ' . $page_id->get_error_message() );
	}
	WP_CLI::log( "Created page /{$slug}/ (ID {$page_id}) — OK." );
}

// ── 2. Retire the MailChimp fallback link ───────────────────────────────────

if ( function_exists( 'get_field' ) ) {
	$current = (string) get_field( 'newsletter_signup_fallback_url', 'option' );
	$target  = tclas_signup_page_url();

	if ( $current === $target ) {
		WP_CLI::log( 'Newsletter fallback already points at the signup page — OK.' );
	} elseif ( '' === $current ) {
		WP_CLI::log( 'Newsletter fallback is empty — nothing to retire.' );
	} else {
		update_field( 'newsletter_signup_fallback_url', $target, 'option' );
		WP_CLI::log( "Repointed newsletter fallback:\n    was: {$current}\n    now: {$target}" );
	}
} else {
	WP_CLI::warning( 'ACF not loaded — could not update the newsletter fallback URL.' );
}

// ── 3. Report ───────────────────────────────────────────────────────────────

WP_CLI::log( '' );
WP_CLI::log( 'Signup page   : ' . get_permalink( $page_id ) );
WP_CLI::log( 'Brevo list ID : ' . tclas_signup_list_id() );
WP_CLI::log( 'Confirm tmpl  : ' . tclas_signup_template_id() );
WP_CLI::log( 'Brevo API key : ' . ( get_option( 'sib_api_key', '' ) ? 'configured' : 'NOT configured' ) );

$remaining = get_posts( [
	'post_type'      => [ 'page', 'post' ],
	'post_status'    => [ 'publish', 'draft', 'pending', 'private' ],
	'posts_per_page' => -1,
	's'              => 'eepurl.com',
	'fields'         => 'ids',
] );
WP_CLI::log( 'MailChimp links left in content: ' . ( $remaining ? implode( ', ', $remaining ) : 'none' ) );

WP_CLI::success( 'Email-list page migration complete.' );
