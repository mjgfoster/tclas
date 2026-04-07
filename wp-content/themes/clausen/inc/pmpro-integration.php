<?php
/**
 * Paid Memberships Pro integration
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Redirect to member hub after login if user is a member.
 */
function tclas_pmpro_after_login_redirect( string $redirect, string $requested_redirect, WP_User|WP_Error $user ): string {
	if ( ! ( $user instanceof WP_User ) ) {
		return $redirect;
	}
	if ( function_exists( 'pmpro_hasMembershipLevel' ) && pmpro_hasMembershipLevel( null, $user->ID ) ) {
		$hub_page = get_page_by_path( 'member-hub' );
		if ( $hub_page ) {
			return get_permalink( $hub_page->ID );
		}
	}
	return $redirect;
}
add_filter( 'login_redirect', 'tclas_pmpro_after_login_redirect', 10, 3 );

/**
 * Credit referral after checkout (Brevo member sync handled by FuseWP).
 */
function tclas_pmpro_after_checkout( int $user_id, object $morder ): void {
	tclas_credit_referral( $user_id );
}
add_action( 'pmpro_after_checkout', 'tclas_pmpro_after_checkout', 10, 2 );

/**
 * Add a renew prompt to PMPro account page for expiring/expired members.
 */
function tclas_pmpro_account_page_notices(): void {
	$status = tclas_membership_status();
	if ( ! in_array( $status, [ 'expiring', 'expired' ], true ) ) {
		return;
	}

	$days      = tclas_days_to_expiry();
	$renew_url = function_exists( 'pmpro_url' ) ? pmpro_url( 'checkout' ) : '#';

	if ( $status === 'expired' ) {
		$message = sprintf(
			/* translators: %s: renew URL */
			__( 'Your TCLAS membership has expired. <a href="%s">Renew now</a> to keep access to the member hub, directory, and events.', 'tclas' ),
			esc_url( $renew_url )
		);
	} else {
		$message = sprintf(
			/* translators: 1: days, 2: renew URL */
			_n(
				'Your TCLAS membership expires in %1$d day. <a href="%2$s">Renew now</a> to keep access.',
				'Your TCLAS membership expires in %1$d days. <a href="%2$s">Renew now</a> to keep access.',
				$days,
				'tclas'
			),
			$days,
			esc_url( $renew_url )
		);
	}

	echo '<div class="tclas-alert tclas-alert--info">' . wp_kses_post( $message ) . '</div>';
}
add_action( 'pmpro_account_bullets_top', 'tclas_pmpro_account_page_notices' );

/**
 * Filter the PMPro levels page to use our custom tier template.
 * Return false to let the shortcode handle it normally,
 * or return a string to completely replace the output.
 */
function tclas_pmpro_levels_shortcode( string $content ): string {
	// We let PMPro render normally but add our own wrapper class.
	return '<div class="tclas-pmpro-levels-wrap">' . $content . '</div>';
}
add_filter( 'pmpro_levels_page_content', 'tclas_pmpro_levels_shortcode' );

// Member subscribe/unsubscribe sync is handled by FuseWP → Brevo.
// No custom Mailchimp hooks needed.

// ═══════════════════════════════════════════════════════════════════════════
// Family membership section on account page
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Handle family membership form POST on account page.
 */
function tclas_pmpro_save_family_members(): void {
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		return;
	}
	if ( ! isset( $_POST['tclas_family_nonce'] ) ) {
		return;
	}
	$uid = get_current_user_id();
	if ( ! $uid || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tclas_family_nonce'] ) ), 'tclas_save_family_' . $uid ) ) {
		return;
	}

	$family_names = array_values( array_filter(
		array_map( 'sanitize_text_field', (array) ( $_POST['tclas_family_names'] ?? [] ) ),
		'strlen'
	) );
	update_user_meta( $uid, '_tclas_family_names', $family_names );
	update_user_meta( $uid, '_tclas_has_children', ! empty( $_POST['tclas_has_children'] ) ? 1 : 0 );

	delete_transient( 'tclas_directory_members' );
}
add_action( 'wp', 'tclas_pmpro_save_family_members' );

/**
 * Render a "Family members" section on the PMPro account page.
 */
function tclas_pmpro_family_section(): void {
	$uid = get_current_user_id();
	if ( ! $uid ) {
		return;
	}

	// Only show for family-level memberships, or if they already have family names saved.
	$family_names = (array) ( get_user_meta( $uid, '_tclas_family_names', true ) ?: [] );
	$has_children = (bool) get_user_meta( $uid, '_tclas_has_children', true );
	$level        = function_exists( 'pmpro_getMembershipLevelForUser' ) ? pmpro_getMembershipLevelForUser( $uid ) : null;
	$is_family    = $level && stripos( $level->name, 'family' ) !== false;

	if ( ! $is_family && empty( $family_names ) ) {
		return;
	}

	if ( empty( $family_names ) ) {
		$family_names[] = '';
	}

	$saved = isset( $_POST['tclas_family_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tclas_family_nonce'] ) ), 'tclas_save_family_' . $uid );
	?>
	<div id="tclas-family-section" class="pmpro_actionlinks" style="margin-top:2rem;">
		<h2><?php esc_html_e( 'Family Members', 'tclas' ); ?></h2>

		<?php if ( $saved ) : ?>
			<div class="tclas-alert tclas-alert--success" role="alert" style="margin-bottom:1rem;">
				<?php esc_html_e( 'Family members saved.', 'tclas' ); ?>
			</div>
		<?php endif; ?>

		<p class="tclas-story-hint">
			<?php esc_html_e( 'If your membership covers additional family members, list their names here. These appear on your member profile.', 'tclas' ); ?>
		</p>

		<form method="post" action="">
			<?php wp_nonce_field( 'tclas_save_family_' . $uid, 'tclas_family_nonce' ); ?>

			<div id="tclas-family-names-list" class="tclas-repeater-list">
				<?php foreach ( $family_names as $i => $fname ) : ?>
					<div class="tclas-repeater-row" data-index="<?php echo (int) $i; ?>" style="margin-bottom:.5rem;">
						<input
							type="text"
							name="tclas_family_names[]"
							value="<?php echo esc_attr( $fname ); ?>"
							class="tclas-story-input"
							placeholder="<?php esc_attr_e( 'e.g. Jane Smith', 'tclas' ); ?>"
							aria-label="<?php esc_attr_e( 'Family member name', 'tclas' ); ?>"
							style="max-width:300px;"
						>
						<?php if ( $i > 0 ) : ?>
							<button
								type="button"
								class="tclas-repeater-remove"
								aria-label="<?php esc_attr_e( 'Remove this name', 'tclas' ); ?>"
							>&times;</button>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>

			<button
				type="button"
				class="btn btn-sm btn-outline-ardoise tclas-repeater-add"
				data-target="tclas-family-names-list"
				data-placeholder="<?php esc_attr_e( 'e.g. John Smith Jr.', 'tclas' ); ?>"
				data-name="tclas_family_names[]"
				style="margin-bottom:1rem;"
			>
				<?php esc_html_e( '+ Add family member', 'tclas' ); ?>
			</button>

			<div style="margin-bottom:1rem;">
				<label class="tclas-story-checkbox">
					<input
						type="checkbox"
						name="tclas_has_children"
						value="1"
						<?php checked( $has_children ); ?>
					>
					<?php esc_html_e( 'This membership includes children under 18 (no names displayed).', 'tclas' ); ?>
				</label>
			</div>

			<button type="submit" class="btn btn-primary btn-sm">
				<?php esc_html_e( 'Save family members', 'tclas' ); ?>
			</button>
		</form>
	</div>
	<?php
}
add_action( 'pmpro_account_after_member_links', 'tclas_pmpro_family_section' );

/**
 * Disable PMPro's per-post content restriction for posts.
 *
 * The theme handles article-level gating via the _tclas_members_only
 * meta field, which shows a branded teaser + gate instead of PMPro's
 * generic paywall. This prevents the two systems from conflicting.
 */
add_filter( 'pmpro_has_membership_access_filter', function ( $hasaccess, $mypost, $myuser, $post_membership_levels ) {
	if ( $mypost && 'post' === get_post_type( $mypost->ID ) ) {
		return true; // Always grant access at the PMPro level; our template handles gating.
	}
	return $hasaccess;
}, 10, 4 );
