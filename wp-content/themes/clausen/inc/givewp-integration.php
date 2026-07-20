<?php
/**
 * GiveWP (donation platform) integration
 *
 * Renders the donation form using a shortcode ID stored in ACF Theme Options.
 * Falls back gracefully when GiveWP is not active or unconfigured.
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Render the GiveWP donation form.
 *
 * Uses ACF option field `donate_form_id` for the GiveWP form ID.
 * Checks shortcode_exists() before rendering so the page degrades
 * gracefully when GiveWP is deactivated.
 */
function tclas_donate_form(): void {
	$form_id = 0;
	if ( function_exists( 'get_field' ) ) {
		$form_id = (int) get_field( 'donate_form_id', 'option' );
	}

	if ( $form_id > 0 && shortcode_exists( 'give_form' ) ) {
		echo '<div class="tclas-donate-form">';
		echo do_shortcode( '[give_form id="' . $form_id . '" show_title="false"]' );
		echo '</div>';
		return;
	}

	// Fallback — admin reminder
	?>
	<div class="tclas-donate-form tclas-donate-form--fallback">
		<p class="text-muted"><?php esc_html_e( 'The donation form will appear here once GiveWP is installed and a form ID is set in Theme Options.', 'tclas' ); ?></p>
	</div>
	<?php
}

/**
 * Merge — don't replace — GiveWP's search-page exclusions.
 *
 * give_remove_pages_from_search() calls $query->set( 'post__not_in', … ),
 * clobbering exclusions added earlier on pre_get_posts. Notably that wiped
 * PMPro's pmpro_search_filter list, leaking members-only titles/excerpts
 * (e.g. restricted events) into logged-out search results. Give passes its
 * list through this filter right before the set() call, so merging the
 * query's existing exclusions back in here restores them.
 */
add_filter( 'give_remove_pages_from_search', function ( $args, $query ) {
	return array_unique( array_merge( (array) $args, (array) $query->get( 'post__not_in' ) ) );
}, 10, 2 );
