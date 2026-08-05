<?php
/**
 * Brevo API key keepalive + health check
 *
 * Brevo expires an API key after 90 days with no API calls, independently of the
 * key's own expiry date. Our key is only exercised by newsletter *signups* —
 * campaign sends go through Brevo's web UI and never touch it — so a quiet
 * quarter on a small list is enough to kill it. The failure is silent: signups
 * simply stop registering and nothing surfaces an error.
 *
 * This pings GET /v3/account on a schedule. That call is free, reads nothing
 * sensitive, and counts as activity — so it keeps the key alive AND tells us
 * whether the key still works (revoked, expired, or blocked by the IP allowlist
 * all show up here).
 *
 * Two independent triggers, deliberately:
 *   - `wp tclas brevo-health` from a real server cron (primary)
 *   - a weekly WP-Cron event (backup, since WP-Cron only fires on page visits
 *     and this site's traffic is too thin to rely on)
 * Both call the same function; the failure email is throttled so the overlap
 * cannot spam.
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

const TCLAS_BREVO_HEALTH_OPTION    = 'tclas_brevo_health_last';
const TCLAS_BREVO_HEALTH_MAILED    = 'tclas_brevo_health_last_mailed';
const TCLAS_BREVO_HEALTH_MAIL_GAP  = DAY_IN_SECONDS; // Don't re-notify more than once a day.
const TCLAS_BREVO_HEALTH_HOOK      = 'tclas_brevo_health_check';

/**
 * Ping Brevo and record the result.
 *
 * @param bool $notify Send an email if the check fails. Cron passes true.
 * @return array{ok:bool,code:int,message:string,plan:string}
 */
function tclas_brevo_health_ping( bool $notify = false ): array {
	$result = [ 'ok' => false, 'code' => 0, 'message' => '', 'plan' => '' ];

	$api_key = get_option( 'sib_api_key', '' );
	if ( ! $api_key ) {
		$result['message'] = 'No Brevo API key is configured (option sib_api_key is empty).';
		tclas_brevo_health_record( $result, $notify );
		return $result;
	}

	$response = wp_remote_get( 'https://api.brevo.com/v3/account', [
		'headers' => [
			'accept'  => 'application/json',
			'api-key' => $api_key,
		],
		'timeout' => 15,
	] );

	if ( is_wp_error( $response ) ) {
		$result['message'] = 'Could not reach Brevo: ' . $response->get_error_message();
		tclas_brevo_health_record( $result, $notify );
		return $result;
	}

	$result['code'] = (int) wp_remote_retrieve_response_code( $response );
	$body           = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( $result['code'] >= 400 ) {
		$detail = is_array( $body ) && ! empty( $body['message'] ) ? $body['message'] : 'no detail returned';
		$result['message'] = sprintf( 'Brevo rejected the key: HTTP %d — %s', $result['code'], $detail );

		// 401 on a key that used to work is nearly always the IP allowlist or a
		// dead key, so spell out both rather than making someone guess.
		if ( 401 === $result['code'] ) {
			$result['message'] .= ' (Likely causes: the key expired, was revoked, lapsed after 90 days'
				. ' of inactivity, or the server IP is missing from Brevo → Security → Authorized IPs.'
				. ' Authorize the OUTBOUND IP, which is not the site A record.)';
		}

		tclas_brevo_health_record( $result, $notify );
		return $result;
	}

	$result['ok'] = true;
	if ( is_array( $body ) ) {
		$result['plan'] = $body['plan'][0]['type'] ?? '';
	}
	$result['message'] = 'Key is live.';

	tclas_brevo_health_record( $result, $notify );
	return $result;
}

/**
 * Persist the last result and, on failure, tell a human.
 *
 * @param array $result Result array from the ping.
 * @param bool  $notify Whether an email is allowed.
 */
function tclas_brevo_health_record( array $result, bool $notify ): void {
	update_option( TCLAS_BREVO_HEALTH_OPTION, [
		'time'    => time(),
		'ok'      => $result['ok'],
		'code'    => $result['code'],
		'message' => $result['message'],
	], false );

	if ( $result['ok'] || ! $notify ) {
		return;
	}

	$last_mailed = (int) get_option( TCLAS_BREVO_HEALTH_MAILED, 0 );
	if ( $last_mailed && ( time() - $last_mailed ) < TCLAS_BREVO_HEALTH_MAIL_GAP ) {
		return;
	}

	$to      = get_option( 'admin_email' );
	$subject = '[TCLAS] Brevo API key check FAILED';
	$body    = "The scheduled Brevo health check failed on " . home_url() . ".\n\n"
		. $result['message'] . "\n\n"
		. "Newsletter signups on the site are probably not reaching Brevo right now.\n"
		. "Existing contacts and scheduled campaigns are unaffected — this is the\n"
		. "WordPress → Brevo connection only.\n\n"
		. "Checked: " . wp_date( 'Y-m-d H:i T' ) . "\n";

	if ( wp_mail( $to, $subject, $body ) ) {
		update_option( TCLAS_BREVO_HEALTH_MAILED, time(), false );
	}
}

// ── Scheduling ──────────────────────────────────────────────────────────────

/**
 * Weekly, not monthly: one call a week costs nothing and gives ~12 chances to
 * land inside the 90-day window instead of 3.
 */
add_action( 'after_setup_theme', function (): void {
	if ( ! wp_next_scheduled( TCLAS_BREVO_HEALTH_HOOK ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'weekly', TCLAS_BREVO_HEALTH_HOOK );
	}
} );

add_action( TCLAS_BREVO_HEALTH_HOOK, function (): void {
	tclas_brevo_health_ping( true );
} );

// WordPress ships daily/twicedaily/hourly; add weekly if nothing else has.
add_filter( 'cron_schedules', function ( array $schedules ): array {
	if ( ! isset( $schedules['weekly'] ) ) {
		$schedules['weekly'] = [
			'interval' => WEEK_IN_SECONDS,
			'display'  => __( 'Once Weekly', 'tclas' ),
		];
	}
	return $schedules;
} );

// ── WP-CLI ──────────────────────────────────────────────────────────────────

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Check that the Brevo API key still works, and keep it from lapsing.
	 *
	 * Exits non-zero on failure so a server cron can detect it.
	 *
	 * ## OPTIONS
	 *
	 * [--quiet-mail]
	 * : Do not send the failure email (useful when testing).
	 *
	 * ## EXAMPLES
	 *
	 *     wp tclas brevo-health
	 *     wp tclas brevo-health --quiet-mail
	 */
	WP_CLI::add_command( 'tclas brevo-health', function ( $args, $assoc_args ): void {
		$notify = empty( $assoc_args['quiet-mail'] );
		$result = tclas_brevo_health_ping( $notify );

		if ( $result['ok'] ) {
			WP_CLI::success( 'Brevo key is live.' . ( $result['plan'] ? ' Plan: ' . $result['plan'] : '' ) );
			return;
		}

		WP_CLI::error( $result['message'] );
	} );
}
