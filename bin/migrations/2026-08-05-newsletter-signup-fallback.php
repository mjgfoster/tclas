<?php
/**
 * Migration: newsletter signup fallback URL (2026-08-05).
 *
 * Companion to the clausen `feature/brevo-newsletter` work
 * (inc/brevo-integration.php, inc/acf-fields.php, front-page.php).
 *
 * Why: the /newsletter/ signup CTA was publicly rendering the admin string
 * "Configure a Brevo form in Theme Options to enable newsletter signup."
 * because footer_newsletter_form_id was never set. The theme change hides that
 * from visitors, but without a fallback the CTA would simply be empty. This
 * seeds the hosted-signup link so the page has a working button the moment the
 * deploy lands.
 *
 * The seeded URL is the CURRENT MailChimp hosted form. That is deliberate and
 * temporary: it keeps signup working during the MailChimp -> Brevo cutover.
 * Once the Brevo form exists, set "Footer newsletter form ID" in Theme Options
 * and CLEAR "Newsletter signup fallback URL" — the embed then takes over with
 * no code deploy. Precedence is: Brevo form ID > fallback URL > admin notice.
 *
 * Idempotent — only writes if the option is empty, so it will not stomp a
 * value someone has already set by hand.
 *
 * Run:  bin/migrate.sh bin/migrations/2026-08-05-newsletter-signup-fallback.php
 *       bin/migrate.sh --prod bin/migrations/2026-08-05-newsletter-signup-fallback.php
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

if ( ! function_exists( 'get_field' ) ) {
	WP_CLI::error( 'ACF is not loaded — cannot set theme options.' );
}

if ( ! function_exists( 'tclas_footer_newsletter_form' ) ) {
	WP_CLI::error( 'inc/brevo-integration.php is not loaded — deploy the theme before running this migration.' );
}

// ── Fallback signup URL ─────────────────────────────────────────────────────

const TCLAS_NL_FALLBACK = 'https://eepurl.com/iB7Skg'; // MailChimp hosted form, https (was http in code).

$form_id  = (int) get_field( 'footer_newsletter_form_id', 'option' );
$existing = (string) get_field( 'newsletter_signup_fallback_url', 'option' );

if ( $form_id > 0 ) {
	WP_CLI::log( "Brevo form ID {$form_id} is already set — the embed wins; no fallback needed. Skipping." );
} elseif ( '' !== $existing ) {
	WP_CLI::log( "Fallback URL already set to {$existing} — leaving it alone." );
} else {
	update_field( 'newsletter_signup_fallback_url', TCLAS_NL_FALLBACK, 'option' );
	WP_CLI::log( 'Set newsletter_signup_fallback_url to ' . TCLAS_NL_FALLBACK . ' — OK.' );
}

// ── Report ──────────────────────────────────────────────────────────────────

WP_CLI::log( '' );
WP_CLI::log( 'Newsletter signup state:' );
WP_CLI::log( '  Brevo form ID ......... ' . ( $form_id > 0 ? $form_id : 'not set' ) );
WP_CLI::log( '  Fallback URL .......... ' . ( (string) get_field( 'newsletter_signup_fallback_url', 'option' ) ?: 'not set' ) );
WP_CLI::log( '  Brevo API key ......... ' . ( get_option( 'sib_api_key', '' ) ? 'configured' : 'NOT configured' ) );
WP_CLI::log( '' );
WP_CLI::log( 'Cutover reminder: once the Brevo form is built, set the form ID and clear the fallback URL.' );

WP_CLI::success( 'Newsletter signup fallback migration complete.' );
