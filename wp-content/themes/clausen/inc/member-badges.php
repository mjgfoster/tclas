<?php
/**
 * Member badge registry and helpers
 *
 * Defines the badge registry, a helper to fetch active badges for a user,
 * and the admin UI for admin-only badges (Board Member).
 *
 * Adding a new badge type: add one entry to tclas_badge_registry().
 * The profile template and admin UI loop through the registry automatically.
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// =============================================================================
// Badge registry
// =============================================================================

/**
 * Returns the full badge registry.
 *
 * Each entry:
 *   label       — translated display name
 *   icon        — character/SVG shown before the label (swap later)
 *   meta_key    — user meta key that stores the value
 *   admin_only  — true  → only admins can set/unset this via the WP Users screen
 *                 false → member can self-report via My Story
 *   self_report — true  → member controls this on their own profile form
 *   auto        — true  → value is derived programmatically (see tclas_get_user_badges)
 *
 * @return array<string, array<string, mixed>>
 */
function tclas_badge_registry(): array {
	return [
		'founding' => [
			'label'       => __( 'Founding Member', 'tclas' ),
			'icon'        => '★',
			'meta_key'    => '_tclas_founding_member', // existing key — backward compat
			'admin_only'  => true,
			'self_report' => false,
			'auto'        => true,  // evaluated via tclas_is_founding_member()
		],
		'board' => [
			'label'       => __( 'Board Member', 'tclas' ),
			'icon'        => '★',
			'meta_key'    => '_tclas_badge_board',
			'admin_only'  => true,
			'self_report' => false,
			'auto'        => false,
		],
		'bierger' => [
			'label'       => __( 'Bierger', 'tclas' ),
			'icon'        => '★',
			'meta_key'    => '_tclas_badge_bierger',
			'admin_only'  => false,
			'self_report' => true,
			'auto'        => false,
		],
	];
}

// =============================================================================
// Helper — active badges for a user
// =============================================================================

/**
 * Returns the slugs of all active badges for a given user.
 *
 * @param  int $user_id
 * @return string[]  e.g. ['founding', 'board']
 */
function tclas_get_user_badges( int $user_id ): array {
	$active = [];

	foreach ( tclas_badge_registry() as $slug => $def ) {
		if ( 'founding' === $slug ) {
			// Delegate to the existing helper so auto-detection + manual override
			// both work without duplicating logic.
			if ( function_exists( 'tclas_is_founding_member' ) && tclas_is_founding_member( $user_id ) ) {
				$active[] = $slug;
			}
		} else {
			if ( get_user_meta( $user_id, $def['meta_key'], true ) ) {
				$active[] = $slug;
			}
		}
	}

	return $active;
}

// =============================================================================
// Admin UI — Board Member (and Bierger read-only display)
// =============================================================================

/**
 * Render the "TCLAS Badges" section on the WP user profile/edit screen.
 * Admins can toggle the Board Member badge.
 * Bierger is shown as read-only (self-reported by the member).
 */
function tclas_admin_badges_field( WP_User $user ): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$is_board   = (bool) get_user_meta( $user->ID, '_tclas_badge_board',   true );
	$is_bierger = (bool) get_user_meta( $user->ID, '_tclas_badge_bierger', true );
	?>
	<h2><?php esc_html_e( 'TCLAS Badges', 'tclas' ); ?></h2>
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Board Member', 'tclas' ); ?></th>
			<td>
				<label>
					<input
						type="checkbox"
						name="tclas_badge_board"
						value="1"
						<?php checked( $is_board ); ?>
					>
					<?php esc_html_e( 'Display Board Member badge on this profile', 'tclas' ); ?>
				</label>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Bierger (citizenship)', 'tclas' ); ?></th>
			<td>
				<?php if ( $is_bierger ) : ?>
					<span class="tclas-badge-admin-status tclas-badge-admin-status--yes">
						<?php esc_html_e( '✓ Self-reported by member', 'tclas' ); ?>
					</span>
				<?php else : ?>
					<span class="tclas-badge-admin-status tclas-badge-admin-status--no">
						<?php esc_html_e( '— Not reported', 'tclas' ); ?>
					</span>
				<?php endif; ?>
				<p class="description"><?php esc_html_e( 'Member self-reports this on their My Story page. Admins cannot change it here.', 'tclas' ); ?></p>
			</td>
		</tr>
	</table>
	<?php wp_nonce_field( 'tclas_badges_' . $user->ID, 'tclas_badges_nonce' ); ?>
	<?php
}
add_action( 'show_user_profile', 'tclas_admin_badges_field' );
add_action( 'edit_user_profile', 'tclas_admin_badges_field' );

/**
 * Save the Board Member badge from the WP user profile/edit screen.
 */
function tclas_admin_save_badges( int $user_id ): void {
	if ( ! isset( $_POST['tclas_badges_nonce'] )
		|| ! wp_verify_nonce(
			sanitize_text_field( wp_unslash( $_POST['tclas_badges_nonce'] ) ),
			'tclas_badges_' . $user_id
		)
	) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	update_user_meta( $user_id, '_tclas_badge_board', ! empty( $_POST['tclas_badge_board'] ) ? 1 : 0 );
}
add_action( 'personal_options_update',  'tclas_admin_save_badges' );
add_action( 'edit_user_profile_update', 'tclas_admin_save_badges' );
