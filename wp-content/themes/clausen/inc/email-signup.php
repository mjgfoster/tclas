<?php
/**
 * Public email-list signup with native double opt-in
 *
 * Lives at /email-list/ via the [tclas_email_signup] shortcode. NOTE this is the
 * general "hear from us" list — /newsletter/ is reserved for The Loon & the Lion.
 *
 * Why we roll our own double opt-in instead of using Brevo's:
 * Brevo's DOI endpoint requires a template flagged as double opt-in, and those can
 * only be created in their web UI — POST /v3/smtp/templates makes an ordinary
 * transactional template, and the DOI endpoint rejects it ("An active DOI template
 * does not exist"). Doing it ourselves also means an unconfirmed address NEVER
 * reaches Brevo: pending signups sit in a transient here and are only pushed to the
 * list once the person clicks through. Nothing to clean up if they never do.
 *
 * Flow:
 *   1. Visitor submits the form  → admin-post, validated, rate-limited, honeypotted
 *   2. Pending record stored in a transient under a random token (48h)
 *   3. Confirmation email sent via Brevo transactional template (params.confirm_url)
 *   4. Visitor clicks           → token consumed, contact pushed to Brevo, done
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

const TCLAS_SIGNUP_ACTION      = 'tclas_email_signup';
const TCLAS_SIGNUP_TTL         = 48 * HOUR_IN_SECONDS;
const TCLAS_SIGNUP_RATE_MAX    = 5;               // submissions per IP per window
const TCLAS_SIGNUP_RATE_WINDOW = HOUR_IN_SECONDS;
const TCLAS_SIGNUP_MIN_SECONDS = 3;               // faster than this is a bot

/**
 * Slug of the page holding the form. Filterable so the page can move without
 * hunting through templates.
 */
function tclas_signup_page_slug(): string {
	return (string) apply_filters( 'tclas_signup_page_slug', 'email-list' );
}

function tclas_signup_page_url( array $args = [] ): string {
	$url = home_url( '/' . tclas_signup_page_slug() . '/' );
	return $args ? add_query_arg( $args, $url ) : $url;
}

/**
 * Brevo transactional template ID used for the confirmation email.
 * Set in Theme Options; falls back to the one created 2026-08-05.
 */
function tclas_signup_template_id(): int {
	$id = 0;
	if ( function_exists( 'get_field' ) ) {
		$id = (int) get_field( 'signup_confirm_template_id', 'option' );
	}
	return $id > 0 ? $id : 6;
}

/**
 * Brevo list the confirmed contact joins.
 */
function tclas_signup_list_id(): int {
	$id = 0;
	if ( function_exists( 'get_field' ) ) {
		$id = (int) get_field( 'brevo_members_list_id', 'option' );
	}
	return $id > 0 ? $id : 2;
}

// ── Assets ──────────────────────────────────────────────────────────────────

/**
 * Enqueued only where the shortcode renders — this is one page, not a site-wide cost.
 * Deliberately a standalone file: clausen's main.css carries hand-written rules that
 * are not in the SCSS source, so it must never be regenerated to add styles.
 */
function tclas_signup_enqueue(): void {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;
	$rel  = '/assets/css/email-signup.css';
	$path = TCLAS_DIR . $rel;
	wp_enqueue_style(
		'tclas-email-signup',
		TCLAS_URI . $rel,
		[],
		file_exists( $path ) ? (string) filemtime( $path ) : null
	);
}

// ── Form ────────────────────────────────────────────────────────────────────

add_shortcode( 'tclas_email_signup', 'tclas_email_signup_shortcode' );

/**
 * Render the signup form, or the outcome of a submission/confirmation.
 */
function tclas_email_signup_shortcode(): string {
	tclas_signup_enqueue();

	$state = isset( $_GET['tclas'] ) ? sanitize_key( wp_unslash( $_GET['tclas'] ) ) : '';

	$messages = [
		'check'     => [ 'ok',  __( 'Almost there — check your inbox', 'tclas' ), __( 'We have sent you a message with a confirmation link. Click it and you are on the list. If it has not arrived in a few minutes, look in your spam folder.', 'tclas' ) ],
		'confirmed' => [ 'ok',  __( 'You are on the list', 'tclas' ), __( 'Thank you for confirming. You will hear from us about events, gatherings, traditions, and stories from the Twin Cities Luxembourg-American community.', 'tclas' ) ],
		'already'   => [ 'ok',  __( 'You are already subscribed', 'tclas' ), __( 'That address is already on our list — nothing more to do.', 'tclas' ) ],
		'expired'   => [ 'err', __( 'That link has expired', 'tclas' ), __( 'Confirmation links are good for 48 hours. Please sign up again below and we will send a fresh one.', 'tclas' ) ],
		'invalid'   => [ 'err', __( 'That link is not valid', 'tclas' ), __( 'It may have already been used. Please sign up again below.', 'tclas' ) ],
		'error'     => [ 'err', __( 'Something went wrong', 'tclas' ), __( 'We could not complete your signup. Please try again, or email us if it keeps happening.', 'tclas' ) ],
		'bademail'  => [ 'err', __( 'That email address looks wrong', 'tclas' ), __( 'Please check it and try again.', 'tclas' ) ],
		'slowdown'  => [ 'err', __( 'Too many attempts', 'tclas' ), __( 'Please wait a little while before trying again.', 'tclas' ) ],
	];

	ob_start();

	if ( isset( $messages[ $state ] ) ) {
		[ $kind, $heading, $body ] = $messages[ $state ];
		printf(
			'<div class="tclas-signup-notice tclas-signup-notice--%s" role="status"><p class="tclas-signup-notice__heading">%s</p><p class="tclas-signup-notice__body">%s</p></div>',
			esc_attr( $kind ),
			esc_html( $heading ),
			esc_html( $body )
		);

		// On success there is nothing left to do — don't show the form again.
		if ( 'ok' === $kind ) {
			return (string) ob_get_clean();
		}
	}
	?>
	<form class="tclas-signup" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="<?php echo esc_attr( TCLAS_SIGNUP_ACTION ); ?>" />
		<?php wp_nonce_field( TCLAS_SIGNUP_ACTION, 'tclas_signup_nonce' ); ?>
		<input type="hidden" name="tclas_t" value="<?php echo esc_attr( (string) time() ); ?>" />

		<p class="tclas-signup__field">
			<label for="tclas-signup-email"><?php esc_html_e( 'Email address', 'tclas' ); ?> <span class="tclas-signup__req" aria-hidden="true">*</span></label>
			<input type="email" id="tclas-signup-email" name="tclas_email" required autocomplete="email" inputmode="email" />
		</p>

		<div class="tclas-signup__row">
			<p class="tclas-signup__field">
				<label for="tclas-signup-first"><?php esc_html_e( 'First name', 'tclas' ); ?></label>
				<input type="text" id="tclas-signup-first" name="tclas_first" autocomplete="given-name" />
			</p>
			<p class="tclas-signup__field">
				<label for="tclas-signup-last"><?php esc_html_e( 'Last name', 'tclas' ); ?></label>
				<input type="text" id="tclas-signup-last" name="tclas_last" autocomplete="family-name" />
			</p>
		</div>

		<?php // Honeypot. Hidden from people, irresistible to bots. ?>
		<div class="tclas-signup__hp" aria-hidden="true">
			<label for="tclas-signup-website"><?php esc_html_e( 'Leave this field empty', 'tclas' ); ?></label>
			<input type="text" id="tclas-signup-website" name="tclas_website" tabindex="-1" autocomplete="off" />
		</div>

		<p class="tclas-signup__consent"><?php esc_html_e( 'We will send you a confirmation link before adding you. You can unsubscribe from any email we send.', 'tclas' ); ?></p>

		<p class="tclas-signup__submit">
			<button type="submit" class="btn btn-primary btn-lg"><?php esc_html_e( 'Join the email list', 'tclas' ); ?></button>
		</p>
	</form>
	<?php
	return (string) ob_get_clean();
}

// ── Submission ──────────────────────────────────────────────────────────────

add_action( 'admin_post_nopriv_' . TCLAS_SIGNUP_ACTION, 'tclas_email_signup_submit' );
add_action( 'admin_post_' . TCLAS_SIGNUP_ACTION, 'tclas_email_signup_submit' );

function tclas_email_signup_submit(): void {
	$back = static function ( string $state ): void {
		wp_safe_redirect( tclas_signup_page_url( [ 'tclas' => $state ] ) );
		exit;
	};

	if ( ! isset( $_POST['tclas_signup_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tclas_signup_nonce'] ) ), TCLAS_SIGNUP_ACTION ) ) {
		$back( 'error' );
	}

	// Bots fill hidden fields and submit instantly. Both are silent successes —
	// telling a bot why it failed only helps it try again.
	if ( ! empty( $_POST['tclas_website'] ) ) {
		$back( 'check' );
	}
	$started = isset( $_POST['tclas_t'] ) ? (int) $_POST['tclas_t'] : 0;
	if ( $started && ( time() - $started ) < TCLAS_SIGNUP_MIN_SECONDS ) {
		$back( 'check' );
	}

	if ( ! tclas_signup_rate_ok() ) {
		$back( 'slowdown' );
	}

	$email = isset( $_POST['tclas_email'] ) ? sanitize_email( wp_unslash( $_POST['tclas_email'] ) ) : '';
	if ( ! is_email( $email ) ) {
		$back( 'bademail' );
	}

	$first = isset( $_POST['tclas_first'] ) ? sanitize_text_field( wp_unslash( $_POST['tclas_first'] ) ) : '';
	$last  = isset( $_POST['tclas_last'] ) ? sanitize_text_field( wp_unslash( $_POST['tclas_last'] ) ) : '';

	// Already on the list? Say so rather than emailing them again.
	if ( tclas_signup_already_subscribed( $email ) ) {
		$back( 'already' );
	}

	$token = bin2hex( random_bytes( 16 ) );
	set_transient( 'tclas_signup_' . $token, [
		'email' => $email,
		'first' => $first,
		'last'  => $last,
		'time'  => time(),
	], TCLAS_SIGNUP_TTL );

	if ( ! tclas_signup_send_confirmation( $email, $first, $token ) ) {
		delete_transient( 'tclas_signup_' . $token );
		$back( 'error' );
	}

	$back( 'check' );
}

/**
 * Simple per-IP throttle. Not bulletproof, but it stops the lazy flooding that a
 * public form on a small site actually attracts.
 */
function tclas_signup_rate_ok(): bool {
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$key = 'tclas_signup_rate_' . md5( $ip . wp_salt() );
	$n   = (int) get_transient( $key );
	if ( $n >= TCLAS_SIGNUP_RATE_MAX ) {
		return false;
	}
	set_transient( $key, $n + 1, TCLAS_SIGNUP_RATE_WINDOW );
	return true;
}

/**
 * Is this address already a (non-blocklisted) Brevo contact?
 * Fails open — a Brevo hiccup should not block a genuine signup.
 */
function tclas_signup_already_subscribed( string $email ): bool {
	$api_key = get_option( 'sib_api_key', '' );
	if ( ! $api_key ) {
		return false;
	}

	$response = wp_remote_get( 'https://api.brevo.com/v3/contacts/' . rawurlencode( $email ), [
		'headers' => [ 'accept' => 'application/json', 'api-key' => $api_key ],
		'timeout' => 10,
	] );

	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return false;
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $body ) || ! empty( $body['emailBlacklisted'] ) ) {
		// Blocklisted people are not "already subscribed" — but we must not quietly
		// re-add them either. Treat as subscribed so the flow ends without a mail.
		return true;
	}

	return in_array( tclas_signup_list_id(), array_map( 'intval', $body['listIds'] ?? [] ), true );
}

/**
 * Send the confirmation email through Brevo's transactional API.
 */
function tclas_signup_send_confirmation( string $email, string $first, string $token ): bool {
	$api_key = get_option( 'sib_api_key', '' );
	if ( ! $api_key ) {
		error_log( 'TCLAS signup: no Brevo API key configured.' );
		return false;
	}

	$response = wp_remote_post( 'https://api.brevo.com/v3/smtp/email', [
		'headers' => [
			'accept'       => 'application/json',
			'content-type' => 'application/json',
			'api-key'      => $api_key,
		],
		'body'    => wp_json_encode( [
			'to'         => [ [ 'email' => $email, 'name' => $first ?: $email ] ],
			'templateId' => tclas_signup_template_id(),
			'params'     => [
				'confirm_url' => tclas_signup_page_url( [ 'tclas_confirm' => $token ] ),
				'first_name'  => $first,
			],
		] ),
		'timeout' => 20,
	] );

	if ( is_wp_error( $response ) ) {
		error_log( 'TCLAS signup: confirmation send failed — ' . $response->get_error_message() );
		return false;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	if ( $code >= 400 ) {
		error_log( 'TCLAS signup: confirmation send failed — HTTP ' . $code . ' ' . wp_remote_retrieve_body( $response ) );
		return false;
	}

	return true;
}

// ── Confirmation ────────────────────────────────────────────────────────────

add_action( 'template_redirect', 'tclas_email_signup_confirm' );

/**
 * Handle the click-through, then redirect to a clean URL so a refresh cannot
 * re-run the token.
 */
function tclas_email_signup_confirm(): void {
	if ( empty( $_GET['tclas_confirm'] ) ) {
		return;
	}

	$token = sanitize_text_field( wp_unslash( $_GET['tclas_confirm'] ) );
	if ( ! preg_match( '/^[a-f0-9]{32}$/', $token ) ) {
		wp_safe_redirect( tclas_signup_page_url( [ 'tclas' => 'invalid' ] ) );
		exit;
	}

	$pending = get_transient( 'tclas_signup_' . $token );
	if ( ! is_array( $pending ) || empty( $pending['email'] ) ) {
		// Transients vanish on expiry, so "not found" is overwhelmingly "expired".
		wp_safe_redirect( tclas_signup_page_url( [ 'tclas' => 'expired' ] ) );
		exit;
	}

	// Consume the token first — a double-click must not double-subscribe.
	delete_transient( 'tclas_signup_' . $token );

	$attributes = [];
	if ( ! empty( $pending['first'] ) ) {
		$attributes['FIRSTNAME'] = $pending['first'];
	}
	if ( ! empty( $pending['last'] ) ) {
		$attributes['LASTNAME'] = $pending['last'];
	}
	$attributes['OPTIN_DATE'] = gmdate( 'Y-m-d' );

	$ok = function_exists( 'tclas_brevo_subscribe' )
		&& tclas_brevo_subscribe( $pending['email'], $attributes, [ tclas_signup_list_id() ] );

	wp_safe_redirect( tclas_signup_page_url( [ 'tclas' => $ok ? 'confirmed' : 'error' ] ) );
	exit;
}
