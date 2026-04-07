<?php
/**
 * Member hub
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Redirect non-members away from member-hub pages.
 */
function tclas_hub_access_check(): void {
	if ( ! is_page_template( 'page-templates/page-member-hub.php' ) ) {
		return;
	}
	if ( tclas_is_member() ) {
		return;
	}

	$join_url = get_page_link( get_option( 'pmpro_levels_page_id' ) ) ?: home_url( '/join/' );
	wp_safe_redirect( add_query_arg( 'tclas_gate', '1', $join_url ) );
	exit;
}
add_action( 'template_redirect', 'tclas_hub_access_check' );

/**
 * Return an array of hub dashboard cards for the current user.
 */
function tclas_hub_dashboard_cards(): array {
	$cards = [
		[
			'title' => __( 'Upcoming events', 'tclas' ),
			'icon'  => '📅',
			'color' => 'crimson',
			'content' => tclas_hub_upcoming_events_snippet(),
			'link'  => tribe_get_events_link() ?: home_url( '/events/' ),
			'link_label' => __( 'All events →', 'tclas' ),
		],
		[
			'title' => __( 'Member directory', 'tclas' ),
			'icon'  => '👥',
			'color' => 'aigue',
			'content' => __( 'Search and connect with fellow TCLAS members.', 'tclas' ),
			'link'  => home_url( '/member-hub/profiles/' ),
			'link_label' => __( 'Browse directory →', 'tclas' ),
		],
		[
			'title' => __( 'Documents & resources', 'tclas' ),
			'icon'  => '📄',
			'color' => 'vert',
			'content' => __( 'Members-only documents, forms, and links.', 'tclas' ),
			'link'  => home_url( '/member-hub/documents/' ),
			'link_label' => __( 'View documents →', 'tclas' ),
		],
		[
			'title' => __( 'Luxembourg Connections', 'tclas' ),
			'icon'  => '🌳',
			'color' => 'crimson',
			'content' => __( 'The members-only genealogy and family search forum.', 'tclas' ),
			'link'  => home_url( '/member-hub/forums/luxembourg-connections/' ),
			'link_label' => __( 'Join the conversation →', 'tclas' ),
		],
	];

	return apply_filters( 'tclas_hub_dashboard_cards', $cards );
}

/**
 * Return a short snippet of upcoming events for the dashboard card.
 */
function tclas_hub_upcoming_events_snippet(): string {
	$events = tclas_get_upcoming_events( 2 );
	if ( empty( $events ) ) {
		return esc_html__( 'No upcoming events — check back soon.', 'tclas' );
	}
	$out = '<ul style="list-style:none;padding:0;margin:0;font-size:0.85rem;">';
	foreach ( $events as $event ) {
		$date  = tribe_get_start_date( $event->ID, false, 'M j' );
		$title = get_the_title( $event->ID );
		$url   = get_permalink( $event->ID );
		$out  .= '<li style="padding:0.2rem 0;"><strong>' . esc_html( $date ) . '</strong> — <a href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a></li>';
	}
	$out .= '</ul>';
	return $out;
}

/**
 * Render the "How Are We Connected" connections panel for the dashboard.
 *
 * Shows up to 3 connection cards (sorted by score), with unseen ones highlighted.
 * Dismissed connections are hidden.  Empty states guide the member toward
 * completing their profile or visiting the forum.
 */
function tclas_render_connections_panel(): void {
	$user_id     = get_current_user_id();
	$complete    = (bool) get_user_meta( $user_id, '_tclas_profile_complete', true );
	$connections = $complete ? tclas_get_connections( $user_id ) : [];
	$visible     = array_filter( $connections, fn( $c ) => ! $c['dismissed'] );
	$new_count   = count( array_filter( $visible, fn( $c ) => ! $c['seen'] ) );
	$ancestry_url = home_url( '/member-hub/map-entries/' );
	?>
	<div
		class="tclas-conn-panel<?php echo $new_count > 0 ? ' tclas-conn-panel--has-new' : ''; ?>"
		id="tclas-connections-panel"
		data-new="<?php echo (int) $new_count; ?>"
	>
		<div class="tclas-conn-panel__header">
			<h2 class="tclas-conn-panel__title">
				🌳 <?php esc_html_e( 'How are we connected?', 'tclas' ); ?>
				<?php if ( $new_count > 0 ) : ?>
					<span class="tclas-conn-badge"><?php echo (int) $new_count; ?></span>
				<?php endif; ?>
			</h2>
			<?php if ( ! empty( $visible ) ) : ?>
				<a href="<?php echo esc_url( $ancestry_url ); ?>" class="tclas-conn-panel__edit-link">
					<?php esc_html_e( 'Edit my ancestry →', 'tclas' ); ?>
				</a>
			<?php endif; ?>
		</div>

		<?php if ( ! $complete ) : ?>
			<!-- Empty state A: profile not started -->
			<div class="tclas-conn-panel__empty">
				<p>
					<?php esc_html_e( 'Add your ancestral communes and Luxembourg surnames to find members who share your roots.', 'tclas' ); ?>
				</p>
				<a href="<?php echo esc_url( $ancestry_url ); ?>" class="btn btn-primary btn-sm">
					<?php esc_html_e( 'Add my Luxembourg ancestry', 'tclas' ); ?>
				</a>
			</div>

		<?php elseif ( empty( $visible ) ) : ?>
			<!-- Empty state B: profile complete, no matches yet -->
			<div class="tclas-conn-panel__empty">
				<p>
					<?php esc_html_e( 'No connections found yet. As more members complete their profiles, we\'ll surface matches here.', 'tclas' ); ?>
				</p>
				<div style="display:flex;gap:.75rem;flex-wrap:wrap;margin-top:.75rem;">
					<a href="<?php echo esc_url( $ancestry_url ); ?>" class="btn btn-outline-ardoise btn-sm">
						<?php esc_html_e( 'Update my ancestry', 'tclas' ); ?>
					</a>
					<a href="<?php echo esc_url( home_url( '/member-hub/forums/luxembourg-connections/' ) ); ?>" class="btn btn-outline-ardoise btn-sm">
						<?php esc_html_e( 'Luxembourg Connections forum →', 'tclas' ); ?>
					</a>
				</div>
			</div>

		<?php else : ?>
			<!-- Connection cards -->
			<div class="tclas-conn-cards" role="list">
				<?php
				$shown = 0;
				foreach ( $visible as $other_id => $conn ) :
					if ( $shown >= 3 ) { break; }
					$strength   = tclas_connection_strength( $conn['score'] );
					$other_user = get_userdata( (int) $other_id );
					if ( ! $other_user ) { continue; }
					$shown++;
					?>
					<div
						class="tclas-conn-card tclas-conn-card--<?php echo esc_attr( $strength['class'] ); ?><?php echo ! $conn['seen'] ? ' tclas-conn-card--new' : ''; ?>"
						role="listitem"
						data-other-id="<?php echo (int) $other_id; ?>"
					>
						<div class="tclas-conn-card__top">
							<span class="tclas-conn-card__strength"><?php echo esc_html( $strength['label'] ); ?></span>
							<button
								type="button"
								class="tclas-conn-dismiss"
								data-other-id="<?php echo (int) $other_id; ?>"
								aria-label="<?php esc_attr_e( 'Dismiss this connection', 'tclas' ); ?>"
							>×</button>
						</div>

						<div class="tclas-conn-card__who">
							<?php echo get_avatar( $other_id, 40, '', '', [ 'class' => 'tclas-conn-card__avatar' ] ); ?>
							<div>
								<strong class="tclas-conn-card__name"><?php echo esc_html( $other_user->display_name ); ?></strong>
								<?php if ( function_exists( 'pmpro_getMembershipLevelForUser' ) ) :
									$lvl = pmpro_getMembershipLevelForUser( (int) $other_id );
									if ( $lvl ) :
								?>
									<span class="tclas-hub-sidebar__level" style="display:inline-block;margin-top:.15rem;"><?php echo esc_html( $lvl->name ); ?></span>
								<?php endif; endif; ?>
							</div>
						</div>

						<p class="tclas-conn-card__sentence">
							<?php echo tclas_connection_sentence( $user_id, (int) $other_id, $conn ); ?>
						</p>

						<!-- Shared data pills (three-tier) -->
						<?php if ( ! empty( $conn['paired_matches'] ) ) : ?>
							<div class="tclas-conn-card__pills">
								<?php foreach ( $conn['paired_matches'] as $pm ) :
									$c_label = tclas_display_commune( $pm['commune'], $user_id );
									$s_label = tclas_display_surname( $pm['surname'], $user_id );
									?>
									<span class="tclas-conn-pill tclas-conn-pill--paired">🏛👤 <?php echo esc_html( $s_label . ' — ' . $c_label ); ?></span>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
						<?php if ( ! empty( $conn['commune_only'] ) ) : ?>
							<div class="tclas-conn-card__pills">
								<?php foreach ( $conn['commune_only'] as $slug ) :
									$label = tclas_display_commune( $slug, $user_id );
									?>
									<span class="tclas-conn-pill tclas-conn-pill--commune">🏛 <?php echo esc_html( $label ); ?></span>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
						<?php if ( ! empty( $conn['surname_only'] ) ) : ?>
							<div class="tclas-conn-card__pills">
								<?php foreach ( $conn['surname_only'] as $head ) :
									$label = tclas_display_surname( $head, $user_id );
									?>
									<span class="tclas-conn-pill tclas-conn-pill--surname">👤 <?php echo esc_html( $label ); ?></span>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<?php if ( (bool) get_user_meta( (int) $other_id, '_tclas_open_to_contact', true ) ) : ?>
							<div class="tclas-conn-card__actions">
								<a
									href="<?php
								$other_nicename = get_userdata( (int) $other_id )->user_nicename ?? '';
								echo esc_url( home_url( '/member-hub/profiles/' . rawurlencode( $other_nicename ) . '/' ) );
								?>"
									class="btn btn-outline-ardoise btn-sm"
								>
									<?php esc_html_e( 'View profile →', 'tclas' ); ?>
								</a>
							</div>
						<?php endif; ?>

					</div><!-- .tclas-conn-card -->
				<?php endforeach; ?>
			</div><!-- .tclas-conn-cards -->

			<?php if ( count( $visible ) > 3 ) : ?>
				<p class="tclas-conn-panel__see-all">
					<a href="<?php echo esc_url( $ancestry_url ); ?>">
						<?php
						printf(
							/* translators: %d: total connection count */
							esc_html__( 'See all %d connections →', 'tclas' ),
							count( $visible )
						);
						?>
					</a>
				</p>
			<?php endif; ?>

		<?php endif; ?>

	</div><!-- .tclas-conn-panel -->
	<?php
}

/**
 * Render the referral card for the member hub dashboard.
 */
function tclas_render_referral_card(): void {
	$url   = tclas_get_referral_url();
	$count = tclas_get_referral_count();
	if ( ! $url ) {
		return;
	}
	?>
	<div class="tclas-referral-card">
		<div class="tclas-referral-card__illustration">
			<?php tclas_illustration( 'referral_lion_illustration', __( 'Lion holding an envelope', 'tclas' ) ); ?>
		</div>
		<div class="tclas-referral-card__content">
			<h3 class="tclas-referral-card__title"><?php esc_html_e( 'Know someone who belongs here?', 'tclas' ); ?></h3>
			<p class="tclas-referral-card__desc">
				<?php
				if ( $count > 0 ) {
					printf(
						/* translators: %d: number of referrals */
						esc_html( _n( 'You have introduced %d person to TCLAS. Merci!', 'You have introduced %d people to TCLAS. Merci vill Mol!', $count, 'tclas' ) ),
						(int) $count
					);
					echo ' ';
				}
				esc_html_e( 'Share your personal link and invite a friend.', 'tclas' );
				?>
			</p>
			<div class="tclas-referral-card__url-row">
				<span class="tclas-referral-card__url"><?php echo esc_html( $url ); ?></span>
				<button
					class="btn btn-primary btn-sm tclas-referral-copy-btn"
					data-url="<?php echo esc_attr( $url ); ?>"
					type="button"
				>
					<?php esc_html_e( 'Copy link', 'tclas' ); ?>
				</button>
			</div>
		</div>
	</div>
	<?php
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION: Profile Completion Widget
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Calculate profile completion score and missing items for a user.
 *
 * @return array { is_private: bool, score: int (0-105), missing: array }
 */
function tclas_get_profile_completion( int $user_id ): array {
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return [ 'is_private' => false, 'score' => 0, 'missing' => [] ];
	}

	// Check privacy state.
	$new_toggle = get_user_meta( $user_id, '_tclas_privacy_show_in_directory', true );
	if ( '' !== $new_toggle && false !== $new_toggle ) {
		$is_private = ! (bool) $new_toggle;
	} else {
		$vis = get_user_meta( $user_id, '_tclas_visibility', true ) ?: 'members';
		$is_private = ( 'hidden' === $vis );
	}

	$edit_url    = home_url( '/member-hub/edit-profile/' );
	$map_url     = home_url( '/member-hub/map-entries/' );
	$score       = 0;
	$missing     = [];

	// Bio (15%) — must exist and be >50 chars.
	$bio = (string) ( get_user_meta( $user_id, '_tclas_bio', true ) ?: '' );
	if ( mb_strlen( wp_strip_all_tags( $bio ) ) > 50 ) {
		$score += 15;
	} else {
		$missing[] = [ 'label' => __( 'Add a bio', 'tclas' ), 'link' => $edit_url, 'weight' => 15 ];
	}

	// First name (8%).
	if ( ! empty( $user->first_name ) ) {
		$score += 8;
	} else {
		$missing[] = [ 'label' => __( 'Add your first name', 'tclas' ), 'link' => admin_url( 'profile.php' ), 'weight' => 8 ];
	}

	// Last name (8%).
	if ( ! empty( $user->last_name ) ) {
		$score += 8;
	} else {
		$missing[] = [ 'label' => __( 'Add your last name', 'tclas' ), 'link' => admin_url( 'profile.php' ), 'weight' => 8 ];
	}

	// Current location (8%).
	$city = (string) ( get_user_meta( $user_id, '_tclas_city', true ) ?: '' );
	if ( '' !== $city ) {
		$score += 8;
	} else {
		$missing[] = [ 'label' => __( 'Add your current location', 'tclas' ), 'link' => $edit_url, 'weight' => 8 ];
	}

	// Profile photo (28%).
	$photo_id = (int) get_user_meta( $user_id, '_tclas_profile_photo', true );
	if ( $photo_id ) {
		$score += 28;
	} else {
		$missing[] = [ 'label' => __( 'Add your photo', 'tclas' ), 'link' => $edit_url, 'weight' => 28 ];
	}

	// Surnames ≥1 (15%).
	$lineages       = (array) ( get_user_meta( $user_id, '_tclas_lineages', true ) ?: [] );
	$unassigned     = (array) ( get_user_meta( $user_id, '_tclas_unassigned_surnames_raw', true ) ?: [] );
	$has_surnames   = false;
	foreach ( $lineages as $l ) {
		$snames = array_filter( (array) ( $l['surnames_raw'] ?? [] ), 'strlen' );
		if ( ! empty( $snames ) ) { $has_surnames = true; break; }
	}
	if ( ! $has_surnames ) {
		$has_surnames = ! empty( array_filter( $unassigned, 'strlen' ) );
	}
	if ( $has_surnames ) {
		$score += 15;
	} else {
		$missing[] = [ 'label' => __( 'Add your family surnames', 'tclas' ), 'link' => $map_url, 'weight' => 15 ];
	}

	// Communes ≥1 (18%).
	$communes_norm = (array) ( get_user_meta( $user_id, '_tclas_communes_norm', true ) ?: [] );
	$has_communes  = ! empty( array_filter( $communes_norm, fn( $s ) => '' !== $s && ! str_starts_with( $s, 'unresolved:' ) ) );
	if ( $has_communes ) {
		$score += 18;
	} else {
		$missing[] = [ 'label' => __( 'Add your ancestral communes', 'tclas' ), 'link' => $map_url, 'weight' => 18 ];
	}

	// Social profiles ≥1 (5% bonus).
	$social_keys = [ '_tclas_facebook_url', '_tclas_instagram_url', '_tclas_linkedin_url', '_tclas_pinterest_url', '_tclas_ancestry_url', '_tclas_familytree_url' ];
	$has_social  = false;
	foreach ( $social_keys as $sk ) {
		if ( '' !== (string) ( get_user_meta( $user_id, $sk, true ) ?: '' ) ) {
			$has_social = true;
			break;
		}
	}
	if ( $has_social ) {
		$score += 5;
	}

	return [
		'is_private' => $is_private,
		'score'      => $score,
		'missing'    => $missing,
	];
}

/**
 * Render the profile completion widget.
 */
function tclas_render_profile_completion_widget(): void {
	$user_id    = get_current_user_id();
	$completion = tclas_get_profile_completion( $user_id );
	$pct        = min( 100, $completion['score'] );
	?>
	<div class="tclas-hub-widget tclas-hub-completion">
		<?php if ( $completion['is_private'] ) : ?>
			<div class="tclas-hub-widget__icon">
				<i class="bi bi-lock-fill" aria-hidden="true"></i>
			</div>
			<h2 class="tclas-hub-widget__title"><?php esc_html_e( 'Your profile is set to private', 'tclas' ); ?></h2>
			<p class="tclas-hub-widget__desc"><?php esc_html_e( 'No one can find you in the directory. You should change that!', 'tclas' ); ?></p>
			<a href="<?php echo esc_url( home_url( '/member-hub/privacy/' ) ); ?>" class="btn btn-sm btn-primary">
				<?php esc_html_e( 'Edit Privacy Settings', 'tclas' ); ?>
			</a>
		<?php else : ?>
			<h2 class="tclas-hub-widget__title">
				<?php esc_html_e( 'Profile Completion', 'tclas' ); ?>
				<span class="tclas-hub-completion__pct"><?php echo (int) $pct; ?>%</span>
			</h2>
			<div class="tclas-hub-completion__bar" role="progressbar" aria-valuenow="<?php echo (int) $pct; ?>" aria-valuemin="0" aria-valuemax="100">
				<div class="tclas-hub-completion__fill" style="width:<?php echo (int) $pct; ?>%;"></div>
			</div>
			<?php if ( ! empty( $completion['missing'] ) ) : ?>
				<p class="tclas-hub-widget__desc">
					<?php echo $pct >= 80
						? esc_html__( 'Almost there! Complete these sections:', 'tclas' )
						: esc_html__( 'Complete these sections to help members find you:', 'tclas' ); ?>
				</p>
				<ul class="tclas-hub-completion__list">
					<?php foreach ( $completion['missing'] as $item ) : ?>
						<li>
							<a href="<?php echo esc_url( $item['link'] ); ?>">
								<?php echo esc_html( $item['label'] ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="tclas-hub-widget__desc"><?php esc_html_e( 'Your profile is complete! Looking great.', 'tclas' ); ?></p>
			<?php endif; ?>
			<a href="<?php echo esc_url( home_url( '/member-hub/edit-profile/' ) ); ?>" class="btn btn-sm btn-outline-ardoise">
				<?php echo empty( $completion['missing'] )
					? esc_html__( 'View Profile', 'tclas' )
					: esc_html__( 'Complete Profile →', 'tclas' ); ?>
			</a>
		<?php endif; ?>
	</div>
	<?php
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION: Membership Status Widget
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Render the membership status widget.
 */
function tclas_render_membership_status_widget(): void {
	$user_id = get_current_user_id();
	$status  = tclas_membership_status();
	$days    = tclas_days_to_expiry();
	$level   = function_exists( 'pmpro_getMembershipLevelForUser' )
		? pmpro_getMembershipLevelForUser( $user_id )
		: null;

	$level_name = $level ? $level->name : __( 'Member', 'tclas' );
	$end_date   = ( $level && ! empty( $level->enddate ) && '0000-00-00 00:00:00' !== $level->enddate )
		? wp_date( 'F j, Y', (int) $level->enddate )
		: '';
	$renew_url  = function_exists( 'pmpro_url' ) ? pmpro_url( 'checkout' ) : home_url( '/join/' );
	$account_url = function_exists( 'pmpro_url' ) ? pmpro_url( 'account' ) : home_url( '/membership-account/' );

	$state_class = 'active';
	if ( 'expiring' === $status ) { $state_class = 'expiring'; }
	if ( 'expired'  === $status ) { $state_class = 'expired'; }
	?>
	<div class="tclas-hub-widget tclas-hub-membership tclas-hub-membership--<?php echo esc_attr( $state_class ); ?>">
		<?php if ( 'expired' === $status ) : ?>
			<div class="tclas-hub-widget__icon tclas-hub-widget__icon--error">
				<i class="bi bi-x-circle-fill" aria-hidden="true"></i>
			</div>
			<h2 class="tclas-hub-widget__title"><?php esc_html_e( 'Your Membership Has Expired', 'tclas' ); ?></h2>
			<p class="tclas-hub-widget__meta">
				<?php echo esc_html( $level_name ); ?>
				<?php if ( $end_date ) : ?>
					· <?php printf( esc_html__( 'Expired %s', 'tclas' ), esc_html( $end_date ) ); ?>
				<?php endif; ?>
			</p>
			<a href="<?php echo esc_url( $renew_url ); ?>" class="btn btn-sm btn-primary">
				<?php esc_html_e( 'Renew Now', 'tclas' ); ?>
			</a>

		<?php elseif ( 'expiring' === $status ) : ?>
			<div class="tclas-hub-widget__icon tclas-hub-widget__icon--warning">
				<i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
			</div>
			<h2 class="tclas-hub-widget__title"><?php esc_html_e( 'Your Membership Expires Soon', 'tclas' ); ?></h2>
			<p class="tclas-hub-widget__meta">
				<?php echo esc_html( $level_name ); ?>
				<?php if ( $end_date ) : ?>
					· <?php printf( esc_html__( 'Expires %s (%d days)', 'tclas' ), esc_html( $end_date ), (int) $days ); ?>
				<?php endif; ?>
			</p>
			<div class="tclas-hub-widget__actions">
				<a href="<?php echo esc_url( $renew_url ); ?>" class="btn btn-sm btn-primary">
					<?php esc_html_e( 'Renew Now', 'tclas' ); ?>
				</a>
				<a href="<?php echo esc_url( $account_url ); ?>" class="btn btn-sm btn-outline-ardoise">
					<?php esc_html_e( 'Manage Membership', 'tclas' ); ?>
				</a>
			</div>

		<?php else : ?>
			<div class="tclas-hub-widget__icon tclas-hub-widget__icon--success">
				<i class="bi bi-check-circle-fill" aria-hidden="true"></i>
			</div>
			<h2 class="tclas-hub-widget__title"><?php esc_html_e( 'Your Membership', 'tclas' ); ?></h2>
			<p class="tclas-hub-widget__meta">
				<?php echo esc_html( $level_name ); ?>
				<?php if ( $end_date ) : ?>
					· <?php printf( esc_html__( 'Renews %s', 'tclas' ), esc_html( $end_date ) ); ?>
				<?php endif; ?>
			</p>
			<a href="<?php echo esc_url( $account_url ); ?>" class="btn btn-sm btn-outline-ardoise">
				<?php esc_html_e( 'Manage Membership', 'tclas' ); ?>
			</a>
		<?php endif; ?>
	</div>
	<?php
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION: Fresh Content Block
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Get fresh content items (admin-featured + fallback events/newsletters).
 *
 * @return array of { type, title, date, url, icon }
 */
function tclas_get_fresh_content( int $limit = 5 ): array {
	$items = [];

	// 1. Check for admin-featured items (ACF repeater on theme options).
	if ( function_exists( 'get_field' ) ) {
		$featured = get_field( 'tclas_featured_content', 'option' );
		if ( is_array( $featured ) ) {
			foreach ( $featured as $row ) {
				$post = $row['content_id'] ?? null;
				if ( ! $post instanceof WP_Post ) {
					$post = is_numeric( $post ) ? get_post( (int) $post ) : null;
				}
				if ( ! $post ) { continue; }
				$type = $row['content_type'] ?? 'newsletter';
				$items[] = [
					'type'  => $type,
					'title' => get_the_title( $post ),
					'date'  => get_the_date( 'M j, Y', $post ),
					'url'   => get_permalink( $post ),
					'icon'  => 'event' === $type ? '🎉' : '📰',
				];
			}
		}
	}

	// 2. If we have enough featured items, return them.
	$show_fallback = true;
	if ( function_exists( 'get_field' ) ) {
		$show_fallback = get_field( 'tclas_hub_show_fallback', 'option' ) ?? true;
	}
	if ( count( $items ) >= $limit || ( ! $show_fallback && ! empty( $items ) ) ) {
		return array_slice( $items, 0, $limit );
	}

	// 3. Fill remaining with fallback content.
	$remaining = $limit - count( $items );
	$fallback  = [];
	$featured_ids = array_map( fn( $i ) => $i['url'], $items ); // avoid duplicates

	// Upcoming events.
	if ( function_exists( 'tclas_get_upcoming_events' ) ) {
		$events = tclas_get_upcoming_events( $remaining );
		foreach ( $events as $event ) {
			$url = get_permalink( $event->ID );
			if ( in_array( $url, $featured_ids, true ) ) { continue; }
			$start = function_exists( 'tribe_get_start_date' )
				? tribe_get_start_date( $event->ID, false, 'M j, Y' )
				: get_the_date( 'M j, Y', $event );
			$fallback[] = [
				'type'      => 'event',
				'title'     => get_the_title( $event ),
				'date'      => $start,
				'url'       => $url,
				'icon'      => '🎉',
				'sort_date' => function_exists( 'tribe_get_start_date' )
					? (int) tribe_get_start_date( $event->ID, false, 'U' )
					: (int) get_post_time( 'U', false, $event ),
			];
		}
	}

	// Recent newsletter issues.
	$newsletter_posts = get_posts( [
		'post_type'      => 'post',
		'meta_key'       => 'tclas_issue_date',
		'posts_per_page' => $remaining,
		'orderby'        => 'date',
		'order'          => 'DESC',
	] );
	foreach ( $newsletter_posts as $np ) {
		$url = get_permalink( $np->ID );
		if ( in_array( $url, $featured_ids, true ) ) { continue; }
		// Deduplicate by issue date — only show one post per issue.
		static $seen_issues = [];
		$issue_date = get_post_meta( $np->ID, 'tclas_issue_date', true );
		if ( isset( $seen_issues[ $issue_date ] ) ) { continue; }
		$seen_issues[ $issue_date ] = true;

		$issue_url = home_url( '/newsletter/issue/' . $issue_date . '/' );
		$fallback[] = [
			'type'      => 'newsletter',
			'title'     => get_the_title( $np ),
			'date'      => get_the_date( 'M j, Y', $np ),
			'url'       => $issue_url,
			'icon'      => '📰',
			'sort_date' => (int) get_post_time( 'U', false, $np ),
		];
	}

	// Sort fallback by date (events first on tie).
	usort( $fallback, function( $a, $b ) {
		if ( $a['sort_date'] === $b['sort_date'] ) {
			return ( 'event' === $a['type'] ) ? -1 : 1;
		}
		// Future events sort ascending, past content sort descending.
		$now = time();
		if ( $a['sort_date'] > $now && $b['sort_date'] > $now ) {
			return $a['sort_date'] - $b['sort_date']; // soonest first
		}
		return $b['sort_date'] - $a['sort_date']; // newest first
	} );

	// Merge and trim.
	$items = array_merge( $items, array_slice( $fallback, 0, $remaining ) );

	// Remove sort_date from output.
	return array_map( function( $item ) {
		unset( $item['sort_date'] );
		return $item;
	}, array_slice( $items, 0, $limit ) );
}

/**
 * Render the fresh content block.
 */
function tclas_render_fresh_content_block(): void {
	$count = 5;
	if ( function_exists( 'get_field' ) ) {
		$count = (int) ( get_field( 'tclas_hub_featured_count', 'option' ) ?: 5 );
	}
	$items = tclas_get_fresh_content( $count );
	if ( empty( $items ) ) {
		return;
	}
	?>
	<div class="tclas-hub-fresh-content">
		<h2 class="tclas-hub-section-title"><?php esc_html_e( 'What\'s New', 'tclas' ); ?></h2>
		<ul class="tclas-hub-fresh-content__list">
			<?php foreach ( $items as $item ) : ?>
				<li class="tclas-hub-fresh-content__item">
					<span class="tclas-hub-fresh-content__icon" aria-hidden="true"><?php echo esc_html( $item['icon'] ); ?></span>
					<a href="<?php echo esc_url( $item['url'] ); ?>" class="tclas-hub-fresh-content__link">
						<?php echo esc_html( $item['title'] ); ?>
					</a>
					<span class="tclas-hub-fresh-content__date"><?php echo esc_html( $item['date'] ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION: Activity Alerts
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Get personalized activity alerts for a user.
 *
 * @return array of { type, icon, title, description, link, link_label }
 */
function tclas_get_activity_alerts( int $user_id ): array {
	$alerts  = [];
	$cutoff  = strtotime( '-7 days' );

	// User's genealogy data.
	$communes_norm = (array) ( get_user_meta( $user_id, '_tclas_communes_norm', true ) ?: [] );
	$resolved      = array_filter( $communes_norm, fn( $s ) => '' !== $s && ! str_starts_with( $s, 'unresolved:' ) );
	$lineages      = (array) ( get_user_meta( $user_id, '_tclas_lineages', true ) ?: [] );
	$user_surnames  = [];
	foreach ( $lineages as $l ) {
		foreach ( (array) ( $l['surnames_norm'] ?? [] ) as $sn ) {
			if ( '' !== $sn ) { $user_surnames[] = $sn; }
		}
	}
	$unassigned_norm = (array) ( get_user_meta( $user_id, '_tclas_unassigned_surnames_norm', true ) ?: [] );
	$user_surnames   = array_unique( array_merge( $user_surnames, array_filter( $unassigned_norm, 'strlen' ) ) );

	$has_genealogy = ! empty( $resolved ) || ! empty( $user_surnames );

	if ( ! $has_genealogy ) {
		// Prompt to complete genealogy.
		$alerts[] = [
			'type'        => 'prompt',
			'icon'        => '🌳',
			'title'       => __( 'Discover your connections', 'tclas' ),
			'description' => __( 'Add your ancestral communes and surnames to find members who share your roots.', 'tclas' ),
			'link'        => home_url( '/member-hub/map-entries/' ),
			'link_label'  => __( 'Add my ancestry →', 'tclas' ),
		];
		return $alerts;
	}

	// ── New members matching communes (last 7 days) ─────────────────────
	if ( ! empty( $resolved ) && function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
		global $wpdb;
		$recent_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT user_id
			 FROM {$wpdb->prefix}pmpro_memberships_users
			 WHERE status = 'active' AND startdate >= %s AND user_id != %d",
			gmdate( 'Y-m-d H:i:s', $cutoff ),
			$user_id
		) );

		$matching_members = [];
		foreach ( $recent_ids as $rid ) {
			$their_communes = (array) ( get_user_meta( (int) $rid, '_tclas_communes_norm', true ) ?: [] );
			$overlap = array_intersect( $resolved, $their_communes );
			if ( ! empty( $overlap ) ) {
				$matching_members[] = (int) $rid;
			}
		}
		if ( ! empty( $matching_members ) ) {
			$count = count( $matching_members );
			$names = [];
			foreach ( array_slice( $matching_members, 0, 2 ) as $mid ) {
				$mu = get_userdata( $mid );
				if ( $mu ) { $names[] = $mu->display_name; }
			}
			$desc = implode( ', ', $names );
			if ( $count > 2 ) {
				$desc .= sprintf( __( ', and %d other', 'tclas' ), $count - 2 );
			}
			$alerts[] = [
				'type'        => 'new_members',
				'icon'        => '👥',
				'title'       => sprintf(
					_n( '%d new member shares your communes!', '%d new members share your communes!', $count, 'tclas' ),
					$count
				),
				'description' => $desc,
				'link'        => home_url( '/member-hub/profiles/' ),
				'link_label'  => __( 'View Members →', 'tclas' ),
			];
		}
	}

	// ── New stories about user's communes (last 7 days) ─────────────────
	if ( ! empty( $resolved ) ) {
		$commune_terms = [];
		foreach ( $resolved as $slug ) {
			$term = get_term_by( 'slug', $slug, 'tclas_commune' );
			if ( $term ) { $commune_terms[] = $term->term_id; }
		}
		if ( ! empty( $commune_terms ) ) {
			$stories = get_posts( [
				'post_type'      => 'tclas_story',
				'posts_per_page' => 3,
				'date_query'     => [ [ 'after' => gmdate( 'Y-m-d', $cutoff ) ] ],
				'tax_query'      => [ [
					'taxonomy' => 'tclas_commune',
					'terms'    => $commune_terms,
				] ],
			] );
			if ( ! empty( $stories ) ) {
				$alerts[] = [
					'type'        => 'new_stories',
					'icon'        => '📖',
					'title'       => sprintf(
						_n( '%d new story about your communes', '%d new stories about your communes', count( $stories ), 'tclas' ),
						count( $stories )
					),
					'description' => sprintf(
						/* translators: %s: story title */
						__( '"%s"', 'tclas' ),
						get_the_title( $stories[0] )
					),
					'link'        => home_url( '/stories/' ),
					'link_label'  => __( 'Read Stories →', 'tclas' ),
				];
			}
		}
	}

	// ── New members with matching surnames (last 7 days) ─────────────────
	if ( ! empty( $user_surnames ) && ! empty( $recent_ids ?? [] ) ) {
		$surname_matches = [];
		foreach ( $recent_ids as $rid ) {
			if ( in_array( (int) $rid, $matching_members ?? [], true ) ) { continue; } // already counted
			$their_lineages = (array) ( get_user_meta( (int) $rid, '_tclas_lineages', true ) ?: [] );
			$their_surnames = [];
			foreach ( $their_lineages as $l ) {
				foreach ( (array) ( $l['surnames_norm'] ?? [] ) as $sn ) {
					if ( '' !== $sn ) { $their_surnames[] = $sn; }
				}
			}
			if ( ! empty( array_intersect( $user_surnames, $their_surnames ) ) ) {
				$surname_matches[] = (int) $rid;
			}
		}
		if ( ! empty( $surname_matches ) ) {
			$alerts[] = [
				'type'        => 'new_surnames',
				'icon'        => '👪',
				'title'       => sprintf(
					_n( '%d new member with your surname', '%d new members with your surnames', count( $surname_matches ), 'tclas' ),
					count( $surname_matches )
				),
				'description' => '',
				'link'        => home_url( '/member-hub/profiles/' ),
				'link_label'  => __( 'View Members →', 'tclas' ),
			];
		}
	}

	// ── Unread messages ─────────────────────────────────────────────────
	if ( function_exists( 'tclas_get_unread_count' ) ) {
		$unread = tclas_get_unread_count( $user_id );
		if ( $unread > 0 ) {
			$alerts[] = [
				'type'        => 'unread_messages',
				'icon'        => '💌',
				'title'       => sprintf(
					_n( 'You have %d unread message', 'You have %d unread messages', $unread, 'tclas' ),
					$unread
				),
				'description' => '',
				'link'        => home_url( '/member-hub/messages/' ),
				'link_label'  => __( 'View Messages →', 'tclas' ),
			];
		}
	}

	// ── Forum @mentions (last 7 days) ───────────────────────────────────
	if ( function_exists( 'bbp_get_reply_post_type' ) ) {
		$user     = get_userdata( $user_id );
		$username = $user ? $user->user_login : '';
		if ( $username ) {
			global $wpdb;
			$mention_count = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts}
				 WHERE post_type = %s
				   AND post_status = 'publish'
				   AND post_date >= %s
				   AND post_author != %d
				   AND post_content LIKE %s",
				bbp_get_reply_post_type(),
				gmdate( 'Y-m-d H:i:s', $cutoff ),
				$user_id,
				'%@' . $wpdb->esc_like( $username ) . '%'
			) );
			if ( $mention_count > 0 ) {
				$alerts[] = [
					'type'        => 'forum_mention',
					'icon'        => '💬',
					'title'       => sprintf(
						_n( 'You were mentioned in %d forum reply', 'You were mentioned in %d forum replies', $mention_count, 'tclas' ),
						$mention_count
					),
					'description' => '',
					'link'        => home_url( '/member-hub/forums/luxembourg-connections/' ),
					'link_label'  => __( 'View Forum →', 'tclas' ),
				];
			}
		}
	}

	return array_slice( $alerts, 0, 4 );
}

/**
 * Render the activity alerts section.
 */
function tclas_render_activity_alerts(): void {
	$alerts = tclas_get_activity_alerts( get_current_user_id() );
	if ( empty( $alerts ) ) {
		return;
	}
	?>
	<div class="tclas-hub-alerts">
		<h2 class="tclas-hub-section-title"><?php esc_html_e( 'Activity', 'tclas' ); ?></h2>
		<div class="tclas-hub-alerts__list">
			<?php foreach ( $alerts as $alert ) : ?>
				<div class="tclas-hub-alert tclas-hub-alert--<?php echo esc_attr( $alert['type'] ); ?>">
					<span class="tclas-hub-alert__icon" aria-hidden="true"><?php echo esc_html( $alert['icon'] ); ?></span>
					<div class="tclas-hub-alert__body">
						<p class="tclas-hub-alert__title"><?php echo esc_html( $alert['title'] ); ?></p>
						<?php if ( $alert['description'] ) : ?>
							<p class="tclas-hub-alert__desc"><?php echo esc_html( $alert['description'] ); ?></p>
						<?php endif; ?>
					</div>
					<a href="<?php echo esc_url( $alert['link'] ); ?>" class="btn btn-sm btn-outline-ardoise">
						<?php echo esc_html( $alert['link_label'] ); ?>
					</a>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION: Admin Screen Cards
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Render the prominent admin screen cards (Edit Profile, Ancestral Map, Privacy).
 */
function tclas_render_admin_screen_cards(): void {
	$cards = [
		[
			'title' => __( 'Edit Your Profile', 'tclas' ),
			'icon'  => 'bi-pencil-square',
			'desc'  => __( 'Tell your story, add a photo, manage your information.', 'tclas' ),
			'url'   => home_url( '/member-hub/edit-profile/' ),
			'color' => 'aigue',
		],
		[
			'title' => __( 'My Ancestral Map', 'tclas' ),
			'icon'  => 'bi-map',
			'desc'  => __( 'Track your family surnames and ancestral communes.', 'tclas' ),
			'url'   => home_url( '/member-hub/map-entries/' ),
			'color' => 'vert',
		],
		[
			'title' => __( 'Privacy Settings', 'tclas' ),
			'icon'  => 'bi-shield-lock',
			'desc'  => __( 'Control what other members can see about you.', 'tclas' ),
			'url'   => home_url( '/member-hub/privacy/' ),
			'color' => 'crimson',
		],
	];
	?>
	<div class="tclas-hub-admin-cards">
		<?php foreach ( $cards as $card ) : ?>
			<a href="<?php echo esc_url( $card['url'] ); ?>" class="tclas-hub-admin-card tclas-hub-admin-card--<?php echo esc_attr( $card['color'] ); ?>">
				<i class="bi <?php echo esc_attr( $card['icon'] ); ?> tclas-hub-admin-card__icon" aria-hidden="true"></i>
				<h3 class="tclas-hub-admin-card__title"><?php echo esc_html( $card['title'] ); ?></h3>
				<p class="tclas-hub-admin-card__desc"><?php echo esc_html( $card['desc'] ); ?></p>
			</a>
		<?php endforeach; ?>
	</div>
	<?php
}
