<?php
/**
 * Mailchimp for WP integration
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Render the footer newsletter signup form.
 * Uses ACF option field for form ID, graceful fallback to plain email input.
 */
function tclas_footer_newsletter_form(): void {
	$form_id = 0;
	if ( function_exists( 'get_field' ) ) {
		$form_id = (int) get_field( 'footer_mc4wp_form_id', 'option' );
	}

	if ( $form_id > 0 && function_exists( 'mc4wp_show_form' ) ) {
		echo do_shortcode( '[mc4wp_form id="' . $form_id . '"]' );
		return;
	}

	// Fallback — plain form (no JS, no list integration)
	?>
	<p class="text-muted" style="font-size:0.75rem;"><?php esc_html_e( 'Configure a Mailchimp for WP form in Theme Options to enable newsletter signup.', 'tclas' ); ?></p>
	<?php
}
