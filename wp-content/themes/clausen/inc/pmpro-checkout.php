<?php
/**
 * PMPro checkout customizations
 *
 * - Optional annual auto-renew (all levels)
 * - Benefactor (level 4): member-chosen amount, minimum enforced server-side
 * - Benefactor acknowledgement / anonymity preference (captured at checkout)
 *
 * Level IDs and TCLAS_BENEFACTOR_MIN are defined in inc/pmpro-integration.php.
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Read the benefactor amount from the request, clamped to the minimum.
 */
function tclas_benefactor_amount_from_request(): float {
	$raw = isset( $_REQUEST['tclas_benefactor_amount'] ) ? (float) $_REQUEST['tclas_benefactor_amount'] : 0;
	return max( (float) TCLAS_BENEFACTOR_MIN, $raw );
}

/**
 * Render the auto-renew / benefactor fields inside the checkout form.
 */
function tclas_checkout_fields(): void {
	global $pmpro_level;
	if ( empty( $pmpro_level ) || empty( $pmpro_level->id ) ) {
		return;
	}
	$lid = (int) $pmpro_level->id;
	?>
	<div class="tclas-checkout-options pmpro_checkout-fields">
		<?php if ( TCLAS_LEVEL_BENEFACTOR === $lid ) : ?>
			<p class="pmpro_checkout-field">
				<label for="tclas_benefactor_amount"><?php esc_html_e( 'Your annual gift ($)', 'tclas' ); ?></label>
				<input
					type="number"
					id="tclas_benefactor_amount"
					name="tclas_benefactor_amount"
					min="<?php echo esc_attr( TCLAS_BENEFACTOR_MIN ); ?>"
					step="1"
					value="<?php echo esc_attr( tclas_benefactor_amount_from_request() ); ?>"
				/>
				<span class="lite">
					<?php
					/* translators: %s: minimum benefactor amount */
					printf( esc_html__( 'Minimum $%s. Give more if you can — thank you.', 'tclas' ), esc_html( number_format( TCLAS_BENEFACTOR_MIN ) ) );
					?>
				</span>
			</p>
			<p class="pmpro_checkout-field">
				<label>
					<input type="checkbox" name="tclas_benefactor_anon" value="1" <?php checked( ! empty( $_REQUEST['tclas_benefactor_anon'] ) ); ?> />
					<?php esc_html_e( 'List me as an anonymous benefactor (hide my name on the website).', 'tclas' ); ?>
				</label>
			</p>
		<?php endif; ?>

		<p class="pmpro_checkout-field">
			<label>
				<input type="checkbox" name="tclas_autorenew" value="1" <?php checked( ! empty( $_REQUEST['tclas_autorenew'] ) ); ?> />
				<?php esc_html_e( 'Automatically renew my membership each year (you can cancel anytime).', 'tclas' ); ?>
			</label>
		</p>
	</div>
	<?php
}
add_action( 'pmpro_checkout_after_level_cost', 'tclas_checkout_fields' );

/**
 * Apply the member's choices to the level being checked out:
 *   - Benefactor: set the amount to their (clamped) gift.
 *   - Auto-renew opt-in: convert to a recurring annual subscription.
 *     Base levels are one-time + 1-year expiry; recurring is opt-in only.
 */
function tclas_checkout_level( $level ) {
	if ( empty( $level ) || empty( $level->id ) ) {
		return $level;
	}

	if ( TCLAS_LEVEL_BENEFACTOR === (int) $level->id ) {
		$amount                  = tclas_benefactor_amount_from_request();
		$level->initial_payment  = $amount;
		$level->billing_amount   = $amount;
	}

	if ( ! empty( $_REQUEST['tclas_autorenew'] ) ) {
		// Recurring: charge the same amount yearly; the subscription keeps
		// access active, so clear the fixed expiration to avoid double logic.
		$level->billing_amount    = $level->initial_payment;
		$level->cycle_number      = 1;
		$level->cycle_period      = 'Year';
		$level->expiration_number = 0;
		$level->expiration_period = '';
	} else {
		// One-time annual: a single charge that expires after a year.
		$level->billing_amount = 0;
		$level->cycle_number   = 0;
	}

	return $level;
}
add_filter( 'pmpro_checkout_level', 'tclas_checkout_level' );

/**
 * Persist the benefactor acknowledgement preference after a successful checkout.
 * The public "Our Benefactors" acknowledgement list is a planned fast-follow.
 */
function tclas_save_benefactor_pref( int $user_id, $morder ): void {
	$level_id = 0;
	if ( is_object( $morder ) && ! empty( $morder->membership_id ) ) {
		$level_id = (int) $morder->membership_id;
	}
	if ( TCLAS_LEVEL_BENEFACTOR !== $level_id ) {
		return;
	}
	update_user_meta( $user_id, '_tclas_benefactor', 1 );
	update_user_meta( $user_id, '_tclas_benefactor_anonymous', ! empty( $_REQUEST['tclas_benefactor_anon'] ) ? 1 : 0 );
	update_user_meta( $user_id, '_tclas_benefactor_amount', tclas_benefactor_amount_from_request() );
}
add_action( 'pmpro_after_checkout', 'tclas_save_benefactor_pref', 20, 2 );

// ═══════════════════════════════════════════════════════════════════════════
// Redesigned multi-step checkout: personal fields, display name, email login
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Phone isn't collected in the redesigned flow, so don't require it.
 */
add_filter( 'pmpro_required_billing_fields', function ( $fields ) {
	if ( is_array( $fields ) ) {
		unset( $fields['bphone'] );
	}
	return $fields;
} );

/**
 * Render the display-name picker. The radio options are generated client-side
 * from the first/last name fields (see checkout-wizard.js); this just provides
 * the container and the hidden input that holds the resolved choice.
 */
function tclas_display_name_picker(): void {
	?>
	<div class="pmpro_checkout-fields tclas-display-name" id="tclas-display-name-picker">
		<p class="pmpro_checkout-field">
			<label for="tclas_display_name"><?php esc_html_e( 'How should your name appear to other members?', 'tclas' ); ?></label>
			<span class="tclas-dn-options" data-tclas-dn-options></span>
			<input type="text" name="tclas_display_name_custom" id="tclas_display_name_custom" class="tclas-dn-custom" placeholder="<?php esc_attr_e( 'Type how you’d like to be shown', 'tclas' ); ?>" hidden />
			<input type="hidden" name="tclas_display_name" id="tclas_display_name" value="" />
		</p>
	</div>
	<?php
}
add_action( 'pmpro_checkout_after_billing_fields', 'tclas_display_name_picker' );

/**
 * Use the email as the username. The wizard JS copies bemail → the hidden
 * username field; this is the server-side safety net before validation.
 */
function tclas_email_as_username(): void {
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		return;
	}
	if ( empty( $_REQUEST['bemail'] ) || ! empty( $_REQUEST['username'] ) ) {
		return;
	}
	$email = sanitize_email( wp_unslash( $_REQUEST['bemail'] ) );
	if ( $email ) {
		$_REQUEST['username'] = $email;
		$_POST['username']    = $email;
	}
}
add_action( 'pmpro_checkout_preheader', 'tclas_email_as_username', 1 );

/**
 * Save personal info after checkout: WP first/last name, the chosen display
 * name, and the mailing address (kept distinct from the profile's "current
 * location" fields so we don't clobber what members set in the hub).
 */
function tclas_save_member_personal( int $user_id, $morder ): void {
	$first = isset( $_REQUEST['bfirstname'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['bfirstname'] ) ) : '';
	$last  = isset( $_REQUEST['blastname'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['blastname'] ) ) : '';

	$userdata = [ 'ID' => $user_id ];
	if ( '' !== $first ) {
		$userdata['first_name'] = $first;
	}
	if ( '' !== $last ) {
		$userdata['last_name'] = $last;
	}

	$display = isset( $_REQUEST['tclas_display_name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['tclas_display_name'] ) ) : '';
	if ( '' === $display ) {
		$display = trim( "$first $last" );
	}
	if ( '' !== $display ) {
		$userdata['display_name'] = $display;
		update_user_meta( $user_id, '_tclas_display_name_override', $display );
	}

	if ( count( $userdata ) > 1 ) {
		wp_update_user( $userdata );
	}

	// Mailing address (for rough geography + the annual member gift).
	$address_map = [
		'baddress1' => '_tclas_mail_address1',
		'baddress2' => '_tclas_mail_address2',
		'bcity'     => '_tclas_mail_city',
		'bstate'    => '_tclas_mail_state',
		'bzipcode'  => '_tclas_mail_zip',
	];
	foreach ( $address_map as $req => $meta ) {
		if ( isset( $_REQUEST[ $req ] ) ) {
			update_user_meta( $user_id, $meta, sanitize_text_field( wp_unslash( $_REQUEST[ $req ] ) ) );
		}
	}
}
add_action( 'pmpro_after_checkout', 'tclas_save_member_personal', 20, 2 );
