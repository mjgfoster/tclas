<?php
/**
 * Template Name: Member hub
 *
 * Minimalist three-column dashboard:
 *   Col 1 — Your membership (account + privacy + legal + referral + logout)
 *   Col 2 — You (ancestral map + connections, profile preview, directory, completion)
 *   Col 3 — What's happening (events + newsletter / fresh content)
 *
 * Full-width, sans-serif, no emoji, no card chrome. The renew/expiry banner
 * still spans full width at the top when applicable.
 *
 * @package TCLAS
 */

get_header();

$user        = wp_get_current_user();
$uid         = $user->ID;
$first_name  = trim( (string) $user->user_firstname ) ?: $user->display_name;
$display     = (string) ( get_user_meta( $uid, '_tclas_display_name_override', true ) ?: $user->display_name );
$profile_url = home_url( '/member-hub/profiles/' . rawurlencode( $user->user_nicename ) . '/' );
$edit_url    = home_url( '/member-hub/edit-profile/' );
$photo_id    = (int) get_user_meta( $uid, '_tclas_profile_photo', true );
$photo_url   = $photo_id ? wp_get_attachment_image_url( $photo_id, 'medium' ) : '';

// Membership state.
$level      = function_exists( 'pmpro_getMembershipLevelForUser' ) ? pmpro_getMembershipLevelForUser( $uid ) : null;
$level_name = $level && ! empty( $level->name ) ? $level->name : __( 'Member', 'tclas' );

global $wpdb;
$mu = $wpdb->get_row( $wpdb->prepare(
	"SELECT enddate FROM {$wpdb->prefix}pmpro_memberships_users WHERE user_id = %d AND status = 'active' ORDER BY id DESC LIMIT 1",
	$uid
) );
$sub = $wpdb->get_row( $wpdb->prepare(
	"SELECT next_payment_date FROM {$wpdb->prefix}pmpro_subscriptions WHERE user_id = %d AND status = 'active' ORDER BY id DESC LIMIT 1",
	$uid
) );

$renewal_line = '';
if ( $sub && ! empty( $sub->next_payment_date ) ) {
	// Recurring: keeps renewing.
	$renewal_line = sprintf( __( 'Renews %s', 'tclas' ), date_i18n( 'F j, Y', strtotime( $sub->next_payment_date ) ) );
} elseif ( $mu && ! empty( $mu->enddate ) && '0000-00-00 00:00:00' !== $mu->enddate ) {
	$renewal_line = sprintf( __( 'Expires %s', 'tclas' ), date_i18n( 'F j, Y', strtotime( $mu->enddate ) ) );
}

$status     = tclas_membership_status();
$is_admin   = current_user_can( 'manage_options' );
$account_url = function_exists( 'pmpro_url' ) ? pmpro_url( 'account' ) : home_url( '/membership-account/' );
$renew_url   = function_exists( 'pmpro_url' ) ? pmpro_url( 'checkout' ) : '#';
$logout_url  = wp_logout_url( home_url() );
?>

<div class="tclas-page-header">
	<div class="container-tclas">
		<h1 class="tclas-page-header__title"><?php the_title(); ?></h1>
	</div>
</div>

<div class="tclas-hub2-page">

	<?php if ( in_array( $status, [ 'expiring', 'expired' ], true ) ) :
		$days = tclas_days_to_expiry(); ?>
		<div class="tclas-hub2-banner tclas-hub2-banner--<?php echo 'expired' === $status ? 'expired' : 'expiring'; ?>" role="alert">
			<?php if ( 'expired' === $status ) : ?>
				<?php
				printf(
					wp_kses( __( 'Your membership has expired. <a href="%s">Renew now</a> to keep access to the hub, directory, and events.', 'tclas' ), [ 'a' => [ 'href' => [] ] ] ),
					esc_url( $renew_url )
				);
				?>
			<?php else : ?>
				<?php
				printf(
					wp_kses(
						_n( 'Your membership expires in %1$d day. <a href="%2$s">Renew now →</a>', 'Your membership expires in %1$d days. <a href="%2$s">Renew now →</a>', $days, 'tclas' ),
						[ 'a' => [ 'href' => [] ] ]
					),
					(int) $days,
					esc_url( $renew_url )
				);
				?>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<h2 class="tclas-hub2-greeting">
		<?php
		/* translators: %s: member's first name */
		printf( esc_html__( 'Moien, %s!', 'tclas' ), esc_html( $first_name ) );
		?>
	</h2>

	<div class="tclas-hub2-grid">

		<!-- ── Col 1: Your membership ─────────────────────────────────────── -->
		<section class="tclas-hub2-col tclas-hub2-col--membership" aria-labelledby="tclas-hub2-h-membership">
			<h3 id="tclas-hub2-h-membership" class="tclas-hub2-col__title"><?php esc_html_e( 'Your membership', 'tclas' ); ?></h3>

			<p class="tclas-hub2-meta">
				<span class="tclas-hub2-meta__level"><?php echo esc_html( $level_name ); ?></span><?php if ( $renewal_line ) : ?>
				<br><span class="tclas-hub2-meta__renewal"><?php echo esc_html( $renewal_line ); ?></span>
				<?php endif; ?>
			</p>

			<p class="tclas-hub2-action">
				<a class="tclas-hub2-link tclas-hub2-link--primary" href="<?php echo esc_url( $account_url ); ?>">
					<?php esc_html_e( 'Manage my membership →', 'tclas' ); ?>
				</a>
			</p>

			<hr class="tclas-hub2-rule">

			<p class="tclas-hub2-action">
				<a class="tclas-hub2-link" href="<?php echo esc_url( home_url( '/member-hub/privacy/' ) ); ?>">
					<?php esc_html_e( 'Privacy settings →', 'tclas' ); ?>
				</a>
			</p>

			<hr class="tclas-hub2-rule tclas-hub2-rule--big">

			<ul class="tclas-hub2-legal">
				<li><a href="<?php echo esc_url( home_url( '/about/privacy/' ) ); ?>"><?php esc_html_e( 'Privacy Policy', 'tclas' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>"><?php esc_html_e( 'Terms of Use', 'tclas' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/accessibility/' ) ); ?>"><?php esc_html_e( 'Accessibility Statement', 'tclas' ); ?></a></li>
			</ul>

			<div class="tclas-hub2-referral">
				<?php tclas_render_referral_card(); ?>
			</div>

			<p class="tclas-hub2-logout">
				<a href="<?php echo esc_url( $logout_url ); ?>"><?php esc_html_e( 'Log out', 'tclas' ); ?></a>
			</p>
		</section>

		<!-- ── Col 2: You (ancestral map + profile) ───────────────────────── -->
		<section class="tclas-hub2-col tclas-hub2-col--you">

			<div class="tclas-hub2-block tclas-hub2-block--ancestry">
				<p class="tclas-hub2-action">
					<a class="tclas-hub2-link tclas-hub2-link--primary" href="<?php echo esc_url( home_url( '/member-hub/map-entries/' ) ); ?>">
						<?php esc_html_e( 'Edit my ancestry →', 'tclas' ); ?>
					</a>
				</p>
				<div class="tclas-hub2-connections">
					<?php tclas_render_connections_panel(); ?>
				</div>
			</div>

			<div class="tclas-hub2-block tclas-hub2-block--profile">
				<a class="tclas-hub2-profile-card" href="<?php echo esc_url( $profile_url ); ?>">
					<?php if ( $photo_url ) : ?>
						<img class="tclas-hub2-profile-card__photo" src="<?php echo esc_url( $photo_url ); ?>" alt="" width="96" height="96">
					<?php else : ?>
						<span class="tclas-hub2-profile-card__photo tclas-hub2-profile-card__photo--placeholder" aria-hidden="true"></span>
					<?php endif; ?>
					<span class="tclas-hub2-profile-card__name"><?php echo esc_html( $display ); ?></span>
					<span class="tclas-hub2-profile-card__view"><?php esc_html_e( 'View my profile', 'tclas' ); ?></span>
				</a>
				<ul class="tclas-hub2-action-list">
					<li><a class="tclas-hub2-link" href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit profile →', 'tclas' ); ?></a></li>
					<li><a class="tclas-hub2-link" href="<?php echo esc_url( home_url( '/member-hub/profiles/' ) ); ?>"><?php esc_html_e( 'Member directory →', 'tclas' ); ?></a></li>
				</ul>
			</div>

			<div class="tclas-hub2-block tclas-hub2-block--completion">
				<?php tclas_render_profile_completion_widget(); ?>
			</div>
		</section>

		<!-- ── Col 3: What's happening ────────────────────────────────────── -->
		<section class="tclas-hub2-col tclas-hub2-col--happening">
			<?php tclas_render_fresh_content_block(); ?>
		</section>

	</div><!-- .tclas-hub2-grid -->

	<?php if ( $is_admin ) : ?>
		<section class="tclas-hub2-admin">
			<?php tclas_render_admin_screen_cards(); ?>
		</section>
	<?php endif; ?>

</div><!-- .tclas-hub2-page -->

<?php get_footer(); ?>
