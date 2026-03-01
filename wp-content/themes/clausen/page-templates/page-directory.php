<?php
/**
 * Template Name: Member directory
 *
 * Searchable member directory — member-gated.
 *
 * @package TCLAS
 */

get_header();
?>

<div class="tclas-page-header tclas-page-header--ardoise">
	<div class="container-tclas">
		<span class="tclas-eyebrow tclas-eyebrow--light"><?php esc_html_e( 'Community', 'tclas' ); ?></span>
		<h1 class="tclas-page-header__title"><?php the_title(); ?></h1>
	</div>
</div>

<section class="tclas-section">
	<div class="container-tclas">

		<?php if ( ! tclas_is_member() ) : ?>

			<!-- Member gate -->
			<div class="tclas-member-gate">
				<div class="tclas-member-gate__inner">
					<?php tclas_illustration( 'member_gate_illustration', __( 'Member directory', 'tclas' ), 'tclas-member-gate__illustration' ); ?>
					<h2><?php esc_html_e( 'Members connect here.', 'tclas' ); ?></h2>
					<p>
						<?php esc_html_e( 'The member directory is available to TCLAS members. Join to find and connect with other Minnesotans with Luxembourg roots.', 'tclas' ); ?>
					</p>
					<div class="tclas-member-gate__actions">
						<a href="<?php echo esc_url( home_url( '/join/' ) ); ?>" class="btn btn-primary">
							<?php esc_html_e( 'Join TCLAS', 'tclas' ); ?>
						</a>
						<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="btn btn-outline-ardoise">
							<?php esc_html_e( 'Log in', 'tclas' ); ?>
						</a>
					</div>
				</div>
			</div>

		<?php else : ?>

			<!-- Directory search -->
			<div class="tclas-directory">
				<form class="tclas-directory__search mb-4" method="get" action="">
					<div class="input-group">
						<input
							type="search"
							name="member_search"
							class="form-control"
							placeholder="<?php esc_attr_e( 'Search by name or commune…', 'tclas' ); ?>"
							value="<?php echo esc_attr( get_query_var( 'member_search', '' ) ); ?>"
						>
						<button class="btn btn-primary" type="submit">
							<?php esc_html_e( 'Search', 'tclas' ); ?>
						</button>
					</div>
				</form>

				<?php
				// Member directory: list WordPress users with an active PMPro membership.
				// Full directory search and pagination to be wired once PMPro is configured.
				$member_args = [
					'number'  => 40,
					'orderby' => 'display_name',
					'order'   => 'ASC',
				];

				if ( function_exists( 'pmpro_hasMembershipLevel' ) ) {
					// Only users with any membership level.
					$member_args['meta_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery
						[
							'key'     => 'pmpro_membership_level',
							'compare' => 'EXISTS',
						],
					];
				}

				$members = get_users( $member_args );
				?>

				<?php if ( $members ) : ?>
					<div class="tclas-directory__grid">
						<?php foreach ( $members as $member ) : ?>
							<a
								href="<?php echo esc_url( home_url( '/member-hub/profile/?member=' . $member->ID ) ); ?>"
								class="tclas-directory__card tclas-directory__card--link"
							>
								<?php echo get_avatar( $member->ID, 64, '', '', [ 'class' => 'tclas-directory__avatar' ] ); ?>
								<div>
									<strong class="tclas-directory__name">
										<?php echo esc_html( $member->display_name ); ?>
									</strong>
									<span class="tclas-directory__view-link"><?php esc_html_e( 'View profile →', 'tclas' ); ?></span>
								</div>
							</a>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<p><?php esc_html_e( 'No members found.', 'tclas' ); ?></p>
				<?php endif; ?>
			</div>

			<!-- Page content -->
			<?php if ( get_the_content() ) : ?>
				<div class="tclas-prose mt-5">
					<?php the_content(); ?>
				</div>
			<?php endif; ?>

		<?php endif; ?>

	</div>
</section>

<?php get_footer(); ?>
