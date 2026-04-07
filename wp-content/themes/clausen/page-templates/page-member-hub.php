<?php
/**
 * Template Name: Member hub
 *
 * Personalized member dashboard with profile completion, membership status,
 * fresh content, activity alerts, admin screen cards, and community sections.
 *
 * @package TCLAS
 */

get_header();
?>

<div class="tclas-page-header">
	<div class="container-tclas">
		<h1 class="tclas-page-header__title"><?php the_title(); ?></h1>
	</div>
</div>

<section class="tclas-section">
	<div class="container-tclas">
		<div style="max-width: 1000px; margin: 0 auto;">

			<?php
			// Renew / expiry notice (prominent banner).
			$status = tclas_membership_status();
			if ( in_array( $status, [ 'expiring', 'expired' ], true ) ) :
				$days      = tclas_days_to_expiry();
				$renew_url = function_exists( 'pmpro_url' ) ? pmpro_url( 'checkout' ) : '#';
			?>
				<div class="tclas-alert tclas-alert--<?php echo $status === 'expired' ? 'error' : 'warning'; ?> tclas-alert--prominent">
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

			<!-- ── Status Widgets (side-by-side) ─────────────────────────────── -->
			<div class="tclas-hub-status-row">
				<?php tclas_render_profile_completion_widget(); ?>
				<?php tclas_render_membership_status_widget(); ?>
			</div>

			<!-- ── Connections panel ──────────────────────────────────────────── -->
			<?php tclas_render_connections_panel(); ?>

			<!-- ── Fresh content ──────────────────────────────────────────────── -->
			<?php tclas_render_fresh_content_block(); ?>

			<!-- ── Activity alerts ────────────────────────────────────────────── -->
			<?php tclas_render_activity_alerts(); ?>

			<!-- ── Admin screen cards ─────────────────────────────────────────── -->
			<?php tclas_render_admin_screen_cards(); ?>

			<!-- ── Community sections ─────────────────────────────────────────── -->
			<div class="tclas-hub-grid">
				<?php foreach ( tclas_hub_dashboard_cards() as $card ) : ?>
					<div class="tclas-hub-card<?php echo isset( $card['color'] ) ? ' tclas-hub-card--' . esc_attr( $card['color'] ) : ''; ?>">
						<h2 class="tclas-hub-card__title"><?php echo esc_html( $card['icon'] . ' ' . $card['title'] ); ?></h2>
						<div class="tclas-hub-card__body">
							<?php echo wp_kses_post( $card['content'] ); ?>
						</div>
						<?php if ( ! empty( $card['link'] ) ) : ?>
							<p class="tclas-hub-card__link-wrap">
								<a href="<?php echo esc_url( $card['link'] ); ?>" class="btn btn-outline-ardoise btn-sm">
									<?php echo esc_html( $card['link_label'] ?? __( 'View →', 'tclas' ) ); ?>
								</a>
							</p>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>

			<!-- ── Referral card ──────────────────────────────────────────────── -->
			<?php tclas_render_referral_card(); ?>

		</div>
	</div>
</section>

<?php get_footer(); ?>
