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
			'link_label' => __( 'Browse profiles →', 'tclas' ),
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
			'link'  => home_url( '/forums/luxembourg-connections/' ),
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
	$story_url   = home_url( '/member-hub/my-story/' );
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
				<a href="<?php echo esc_url( $story_url ); ?>" class="tclas-conn-panel__edit-link">
					<?php esc_html_e( 'Edit my story →', 'tclas' ); ?>
				</a>
			<?php endif; ?>
		</div>

		<?php if ( ! $complete ) : ?>
			<!-- Empty state A: profile not started -->
			<div class="tclas-conn-panel__empty">
				<p>
					<?php esc_html_e( 'Add your ancestral communes and Luxembourg surnames to find members who share your roots.', 'tclas' ); ?>
				</p>
				<a href="<?php echo esc_url( $story_url ); ?>" class="btn btn-primary btn-sm">
					<?php esc_html_e( 'Add my Luxembourg story', 'tclas' ); ?>
				</a>
			</div>

		<?php elseif ( empty( $visible ) ) : ?>
			<!-- Empty state B: profile complete, no matches yet -->
			<div class="tclas-conn-panel__empty">
				<p>
					<?php esc_html_e( 'No connections found yet. As more members complete their profiles, we\'ll surface matches here.', 'tclas' ); ?>
				</p>
				<div style="display:flex;gap:.75rem;flex-wrap:wrap;margin-top:.75rem;">
					<a href="<?php echo esc_url( $story_url ); ?>" class="btn btn-outline-ardoise btn-sm">
						<?php esc_html_e( 'Update my story', 'tclas' ); ?>
					</a>
					<a href="<?php echo esc_url( home_url( '/forums/luxembourg-connections/' ) ); ?>" class="btn btn-outline-ardoise btn-sm">
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
					<a href="<?php echo esc_url( $story_url ); ?>">
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
