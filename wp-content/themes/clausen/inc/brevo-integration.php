<?php
/**
 * Brevo (email platform) integration
 *
 * Handles:
 * - Footer newsletter signup form (Brevo plugin shortcode)
 * - Brevo API helper for subscribing contacts with tags
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Render the footer newsletter signup form.
 * Uses ACF option field for form ID; falls back to guidance text.
 */
function tclas_footer_newsletter_form(): void {
	$form_id = 0;
	if ( function_exists( 'get_field' ) ) {
		$form_id = (int) get_field( 'footer_newsletter_form_id', 'option' );
	}

	if ( $form_id > 0 && shortcode_exists( 'sibwp_form' ) ) {
		echo do_shortcode( '[sibwp_form id="' . $form_id . '"]' );
		return;
	}

	// Fallback — admin reminder
	?>
	<p class="text-muted" style="font-size:0.75rem;"><?php esc_html_e( 'Configure a Brevo form in Theme Options to enable newsletter signup.', 'tclas' ); ?></p>
	<?php
}

/**
 * Subscribe a contact to Brevo and optionally apply attributes/tags.
 *
 * Requires the Brevo plugin to be active (stores the API key in wp_options).
 * Falls back gracefully if the plugin is missing or unconfigured.
 *
 * @param string $email       Contact email address.
 * @param array  $attributes  Brevo contact attributes, e.g. ['FIRSTNAME' => 'Jo'].
 * @param array  $list_ids    Brevo list IDs to add the contact to.
 * @param string $tag         Optional tag to apply (e.g. 'quiz-completer').
 * @return bool True on success, false on failure.
 */
function tclas_brevo_subscribe( string $email, array $attributes = [], array $list_ids = [], string $tag = '' ): bool {
	$api_key = get_option( 'sib_api_key', '' );
	if ( ! $api_key || ! is_email( $email ) ) {
		return false;
	}

	$body = [
		'email'            => $email,
		'updateEnabled'    => true,
	];

	if ( $attributes ) {
		$body['attributes'] = $attributes;
	}

	if ( $list_ids ) {
		$body['listIds'] = array_map( 'intval', $list_ids );
	}

	$response = wp_remote_post( 'https://api.brevo.com/v3/contacts', [
		'headers' => [
			'accept'       => 'application/json',
			'content-type' => 'application/json',
			'api-key'      => $api_key,
		],
		'body'    => wp_json_encode( $body ),
		'timeout' => 10,
	] );

	if ( is_wp_error( $response ) ) {
		error_log( 'TCLAS Brevo subscribe failed for ' . $email . ': ' . $response->get_error_message() );
		return false;
	}

	$code = wp_remote_retrieve_response_code( $response );
	if ( $code >= 400 ) {
		error_log( 'TCLAS Brevo subscribe failed for ' . $email . ': HTTP ' . $code . ' — ' . wp_remote_retrieve_body( $response ) );
		return false;
	}

	// Apply tag if provided (separate API call)
	if ( $tag ) {
		tclas_brevo_apply_tag( $email, $tag, $api_key );
	}

	return true;
}

/**
 * Apply a tag to a Brevo contact.
 *
 * @param string $email   Contact email.
 * @param string $tag     Tag name to apply.
 * @param string $api_key Brevo API key (pass to avoid re-fetching).
 */
function tclas_brevo_apply_tag( string $email, string $tag, string $api_key = '' ): void {
	if ( ! $api_key ) {
		$api_key = get_option( 'sib_api_key', '' );
	}
	if ( ! $api_key ) {
		return;
	}

	// Brevo uses "contacts/lists" or JSONATTR for tags depending on plan.
	// The simplest portable approach: store the tag as a contact attribute.
	// This requires a TEXT attribute named TAG_{normalized} to exist in Brevo,
	// or we can append to a multi-value TEXT attribute named TAGS.
	// For now, we set a boolean-style attribute: QUIZ_COMPLETER = true.
	// Adjust attribute names in Brevo dashboard to match.
	$attr_name = strtoupper( str_replace( '-', '_', $tag ) );

	wp_remote_request( 'https://api.brevo.com/v3/contacts/' . rawurlencode( $email ), [
		'method'  => 'PUT',
		'headers' => [
			'accept'       => 'application/json',
			'content-type' => 'application/json',
			'api-key'      => $api_key,
		],
		'body'    => wp_json_encode( [
			'attributes' => [ $attr_name => true ],
		] ),
		'timeout' => 10,
	] );
}
