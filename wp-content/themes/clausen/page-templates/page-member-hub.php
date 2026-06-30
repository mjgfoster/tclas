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

			<?php
			// "Manage Household" panel (renders only for Household owners).
			if ( function_exists( 'tclas_household_panel' ) ) {
				tclas_household_panel( $uid );
			}
			?>

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

			<?php
			$referral_url = function_exists( 'tclas_get_referral_url' ) ? tclas_get_referral_url() : '';
			if ( $referral_url ) :
				?>
				<hr class="tclas-hub2-rule tclas-hub2-rule--big">
				<p class="tclas-hub2-action">
					<button
						type="button"
						class="tclas-hub2-link tclas-hub2-link--button tclas-referral-copy-btn"
						data-url="<?php echo esc_attr( $referral_url ); ?>"
					><?php esc_html_e( 'Invite a friend →', 'tclas' ); ?></button>
				</p>
			<?php endif; ?>

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
				<?php
				$profile_complete = (bool) get_user_meta( $uid, '_tclas_profile_complete', true );
				$connections      = $profile_complete && function_exists( 'tclas_get_connections' )
					? array_filter( tclas_get_connections( $uid ), fn( $c ) => ! $c['dismissed'] )
					: [];
				?>
				<?php if ( ! empty( $connections ) ) : ?>
					<ul class="tclas-hub2-connections">
						<?php
						$shown = 0;
						foreach ( $connections as $other_id => $conn ) :
							if ( $shown >= 3 ) { break; }
							$other = get_userdata( (int) $other_id );
							if ( ! $other ) { continue; }
							$shown++;
							$strength = function_exists( 'tclas_connection_strength' )
								? tclas_connection_strength( $conn['score'] )
								: [ 'label' => '', 'class' => '' ];
							$other_url = home_url( '/member-hub/profiles/' . rawurlencode( $other->user_nicename ) . '/' );
							?>
							<li class="tclas-hub2-connections__item">
								<a class="tclas-hub2-connections__link" href="<?php echo esc_url( $other_url ); ?>">
									<?php echo get_avatar( (int) $other_id, 32, '', '', [ 'class' => 'tclas-hub2-connections__avatar' ] ); ?>
									<span class="tclas-hub2-connections__name"><?php echo esc_html( $other->display_name ); ?></span>
								</a>
								<?php if ( ! empty( $strength['label'] ) ) : ?>
									<span class="tclas-hub2-connections__strength"><?php echo esc_html( $strength['label'] ); ?></span>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php elseif ( ! $profile_complete ) : ?>
					<p class="tclas-hub2-muted"><?php esc_html_e( 'Add your ancestry to discover connections with other members.', 'tclas' ); ?></p>
				<?php else : ?>
					<p class="tclas-hub2-muted"><?php esc_html_e( 'No connections yet — matches will appear here as more members add their ancestry.', 'tclas' ); ?></p>
				<?php endif; ?>
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

			<?php
			$completion = function_exists( 'tclas_get_profile_completion' ) ? tclas_get_profile_completion( $uid ) : null;
			if ( $completion ) :
				$pct = min( 100, (int) $completion['score'] );
				if ( ! empty( $completion['is_private'] ) ) : ?>
					<div class="tclas-hub2-block tclas-hub2-block--completion">
						<p class="tclas-hub2-muted">
							<?php esc_html_e( 'Your profile is private — other members can\'t find you in the directory.', 'tclas' ); ?>
						</p>
						<p class="tclas-hub2-action">
							<a class="tclas-hub2-link" href="<?php echo esc_url( home_url( '/member-hub/privacy/' ) ); ?>">
								<?php esc_html_e( 'Privacy settings →', 'tclas' ); ?>
							</a>
						</p>
					</div>
				<?php elseif ( $pct < 100 ) : ?>
					<div class="tclas-hub2-block tclas-hub2-block--completion">
						<p class="tclas-hub2-completion__label">
							<?php
							/* translators: %d: completion percentage */
							printf( esc_html__( 'Profile %d%% complete', 'tclas' ), (int) $pct );
							?>
						</p>
						<div class="tclas-hub2-completion__bar" role="progressbar" aria-valuenow="<?php echo (int) $pct; ?>" aria-valuemin="0" aria-valuemax="100">
							<div class="tclas-hub2-completion__fill" style="width:<?php echo (int) $pct; ?>%;"></div>
						</div>
						<p class="tclas-hub2-action">
							<a class="tclas-hub2-link" href="<?php echo esc_url( $edit_url ); ?>">
								<?php esc_html_e( 'Complete profile →', 'tclas' ); ?>
							</a>
						</p>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</section>

		<!-- ── Col 3: What's happening ────────────────────────────────────── -->
		<?php
		$hub_events = function_exists( 'tclas_get_upcoming_events' ) ? tclas_get_upcoming_events( 3 ) : [];
		$hub_newsletter = get_posts( [
			'post_type'      => 'post',
			'meta_key'       => 'tclas_issue_date',
			'posts_per_page' => 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		] );
		$hub_newsletter = $hub_newsletter ? $hub_newsletter[0] : null;
		?>
		<section class="tclas-hub2-col tclas-hub2-col--happening">

			<?php if ( ! empty( $hub_events ) ) : ?>
				<h3 class="tclas-hub2-col__title"><?php esc_html_e( 'Upcoming events', 'tclas' ); ?></h3>
				<ul class="tclas-hub2-events">
					<?php foreach ( $hub_events as $event ) :
						$ts = function_exists( 'tribe_get_start_date' )
							? (int) tribe_get_start_date( $event->ID, false, 'U' )
							: (int) get_post_time( 'U', false, $event );
						?>
						<li class="tclas-hub2-events__item">
							<span class="tclas-hub2-events__date" aria-hidden="true">
								<span class="tclas-hub2-events__mon"><?php echo esc_html( strtoupper( date_i18n( 'M', $ts ) ) ); ?></span>
								<span class="tclas-hub2-events__day"><?php echo esc_html( date_i18n( 'j', $ts ) ); ?></span>
							</span>
							<a class="tclas-hub2-events__title" href="<?php echo esc_url( get_permalink( $event->ID ) ); ?>">
								<?php echo esc_html( get_the_title( $event->ID ) ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
				<p class="tclas-hub2-action">
					<a class="tclas-hub2-link" href="<?php echo esc_url( home_url( '/events/' ) ); ?>">
						<?php esc_html_e( 'All events →', 'tclas' ); ?>
					</a>
				</p>
			<?php endif; ?>

			<?php if ( $hub_newsletter ) :
				$issue_date = get_post_meta( $hub_newsletter->ID, 'tclas_issue_date', true );
				$issue_url  = $issue_date ? home_url( '/newsletter/issue/' . $issue_date . '/' ) : get_permalink( $hub_newsletter->ID );
				$excerpt    = get_the_excerpt( $hub_newsletter );
				?>
				<?php if ( ! empty( $hub_events ) ) : ?>
					<hr class="tclas-hub2-rule tclas-hub2-rule--big">
				<?php endif; ?>
				<h3 class="tclas-hub2-col__title"><?php esc_html_e( 'From the newsletter', 'tclas' ); ?></h3>
				<p class="tclas-hub2-newsletter__title"><?php echo esc_html( get_the_title( $hub_newsletter ) ); ?></p>
				<?php if ( $excerpt ) : ?>
					<p class="tclas-hub2-newsletter__excerpt"><?php echo esc_html( $excerpt ); ?></p>
				<?php endif; ?>
				<p class="tclas-hub2-action">
					<a class="tclas-hub2-link" href="<?php echo esc_url( $issue_url ); ?>">
						<?php esc_html_e( 'Read newsletter →', 'tclas' ); ?>
					</a>
				</p>
			<?php endif; ?>

		</section>

	</div><!-- .tclas-hub2-grid -->

</div><!-- .tclas-hub2-page -->

<?php get_footer(); ?>
