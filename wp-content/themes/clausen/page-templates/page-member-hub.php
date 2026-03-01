<?php
/**
 * Template Name: Member hub
 *
 * @package TCLAS
 */

get_header();
$user = wp_get_current_user();
?>

<div class="tclas-hub">

	<!-- Sidebar backdrop (mobile) -->
	<div class="tclas-hub-sidebar-backdrop" aria-hidden="true"></div>

	<!-- Sidebar -->
	<aside class="tclas-hub-sidebar" aria-label="<?php esc_attr_e( 'Member hub navigation', 'tclas' ); ?>">
		<div class="tclas-hub-sidebar__user">
			<?php echo get_avatar( $user->ID, 42, '', '', [ 'class' => 'tclas-hub-sidebar__avatar' ] ); ?>
			<div>
				<p class="tclas-hub-sidebar__name"><?php echo esc_html( $user->display_name ); ?></p>
				<?php if ( function_exists( 'pmpro_getMembershipLevelForUser' ) ) :
					$level = pmpro_getMembershipLevelForUser( $user->ID );
					if ( $level ) :
				?>
					<span class="tclas-hub-sidebar__level"><?php echo esc_html( $level->name ); ?></span>
				<?php endif; endif; ?>
			</div>
		</div>

		<nav class="tclas-hub-sidebar__nav">
			<?php
			wp_nav_menu( [
				'theme_location' => 'hub',
				'container'      => false,
				'fallback_cb'    => function() {
					$links = [
						[ 'label' => __( 'Dashboard',  'tclas' ), 'url' => home_url( '/member-hub/' ),            'icon' => '🏠' ],
						[ 'label' => __( 'Directory',  'tclas' ), 'url' => home_url( '/member-hub/directory/' ),   'icon' => '👥' ],
						[ 'label' => __( 'Documents',  'tclas' ), 'url' => home_url( '/member-hub/documents/' ),   'icon' => '📄' ],
						[ 'label' => __( 'My story',   'tclas' ), 'url' => home_url( '/member-hub/my-story/' ),   'icon' => '🌳' ],
						[ 'label' => __( 'Forum',      'tclas' ), 'url' => home_url( '/forums/' ),                 'icon' => '💬' ],
						[ 'label' => __( 'Map',        'tclas' ), 'url' => home_url( '/member-hub/commune-map/' ), 'icon' => '🗺️' ],
					];
					echo '<ul>';
					foreach ( $links as $link ) {
						$active = rtrim( $_SERVER['REQUEST_URI'], '/' ) === rtrim( parse_url( $link['url'], PHP_URL_PATH ), '/' );
						echo '<li><a href="' . esc_url( $link['url'] ) . '" class="' . ( $active ? 'active' : '' ) . '">' . esc_html( $link['icon'] ) . ' ' . esc_html( $link['label'] ) . '</a></li>';
					}
					echo '</ul>';
				},
			] );
			?>
		</nav>

		<div class="tclas-hub-sidebar__footer">
			<a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="tclas-hub-sidebar__logout">
				<?php esc_html_e( 'Log out', 'tclas' ); ?>
			</a>
		</div>
	</aside>

	<!-- Main content -->
	<div class="tclas-hub-content">
		<div class="tclas-hub-content__header">
			<button class="btn btn-outline-light btn-sm tclas-hub-mobile-toggle" aria-label="<?php esc_attr_e( 'Open sidebar', 'tclas' ); ?>">
				☰ <?php esc_html_e( 'Menu', 'tclas' ); ?>
			</button>
			<h1 class="tclas-hub-content__title"><?php esc_html_e( 'Member hub', 'tclas' ); ?></h1>
			<p class="tclas-hub-content__sub">
				<?php
				printf(
					/* translators: %s: member first name */
					esc_html__( 'Welcome back, %s.', 'tclas' ),
					esc_html( $user->first_name ?: $user->display_name )
				);
				?>
			</p>
		</div>

		<div class="tclas-hub-content__body">

			<?php
			// Renew / expiry notice
			$status = tclas_membership_status();
			if ( in_array( $status, [ 'expiring', 'expired' ], true ) ) :
				$days      = tclas_days_to_expiry();
				$renew_url = function_exists( 'pmpro_url' ) ? pmpro_url( 'checkout' ) : '#';
			?>
				<div class="tclas-alert tclas-alert--<?php echo $status === 'expired' ? 'error' : 'warning'; ?>" style="margin-bottom:2rem;">
					<?php if ( $status === 'expired' ) : ?>
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

			<!-- Connections panel -->
			<?php tclas_render_connections_panel(); ?>

			<!-- Dashboard cards -->
			<div class="tclas-hub-grid">
				<?php foreach ( tclas_hub_dashboard_cards() as $card ) : ?>
					<div class="tclas-hub-card<?php echo isset( $card['color'] ) ? ' tclas-hub-card--' . esc_attr( $card['color'] ) : ''; ?>">
						<h2 class="tclas-hub-card__title"><?php echo esc_html( $card['icon'] . ' ' . $card['title'] ); ?></h2>
						<div class="tclas-hub-card__body">
							<?php echo wp_kses_post( $card['content'] ); ?>
						</div>
						<?php if ( ! empty( $card['link'] ) ) : ?>
							<p style="margin-top:1rem;">
								<a href="<?php echo esc_url( $card['link'] ); ?>" class="btn btn-outline-ardoise btn-sm">
									<?php echo esc_html( $card['link_label'] ?? __( 'View →', 'tclas' ) ); ?>
								</a>
							</p>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>

			<!-- Referral card -->
			<?php tclas_render_referral_card(); ?>

		</div><!-- .tclas-hub-content__body -->
	</div><!-- .tclas-hub-content -->

</div><!-- .tclas-hub -->

<?php get_footer(); ?>
