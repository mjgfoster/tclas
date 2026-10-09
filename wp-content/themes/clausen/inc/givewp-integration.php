<?php
/**
 * Donate page integration
 *
 * Donations go through a Stripe Payment Link (ACF option `donate_payment_link`)
 * hosted on Stripe's checkout. GiveWP's bundled stripe-php shadowed PMPro's and
 * crashed the membership webhook (Oct 2026), so GiveWP is being retired; while
 * the link is unset the page still falls back to the GiveWP form.
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Render the donation call to action.
 *
 * Prefers the Stripe Payment Link; falls back to the GiveWP shortcode
 * (`donate_form_id`) when no link is set and GiveWP is active.
 */
function tclas_donate_form(): void {
	$link    = '';
	$form_id = 0;
	if ( function_exists( 'get_field' ) ) {
		$link    = (string) get_field( 'donate_payment_link', 'option' );
		$form_id = (int) get_field( 'donate_form_id', 'option' );
	}

	if ( $link ) {
		// Prefill the logged-in member's email on Stripe's checkout.
		$user = wp_get_current_user();
		if ( $user->exists() && $user->user_email ) {
			$link = add_query_arg( 'prefilled_email', rawurlencode( $user->user_email ), $link );
		}
		?>
		<div class="tclas-donate-form tclas-donate-form--stripe">
			<div class="tclas-donate-thanks" id="donate-thanks" role="status" hidden>
				<h2><?php echo tclas_ltz( 'Villmools merci!', 'Thank you so much!', false ); ?></h2>
				<p><?php esc_html_e( 'Your gift has gone through. A receipt for your tax records is on its way to your inbox.', 'tclas' ); ?></p>
			</div>
			<p class="tclas-donate-button">
				<a href="<?php echo esc_url( $link ); ?>" class="btn btn-primary btn-lg">
					<?php esc_html_e( 'Donate', 'tclas' ); ?>
				</a>
			</p>
			<p class="tclas-donate-secure"><?php esc_html_e( 'You’ll choose your amount on Stripe’s secure checkout page.', 'tclas' ); ?></p>
		</div>
		<script>
		// Stripe sends donors back to /donate/?thanks=1. Revealed client-side so a
		// cached copy of the page can't show (or hide) the message wrongly.
		if ( /[?&]thanks=1\b/.test( location.search ) ) {
			document.getElementById( 'donate-thanks' ).hidden = false;
		}
		</script>
		<?php
		return;
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
		<p class="text-muted"><?php esc_html_e( 'The donate button will appear here once a Stripe Payment Link is set in Theme Options.', 'tclas' ); ?></p>
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
