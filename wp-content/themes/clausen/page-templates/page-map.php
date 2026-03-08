<?php
/**
 * Template Name: Ancestral Map page
 *
 * Member-gated full map showing ancestral communes of TCLAS members.
 * Lives at /member-hub/ancestral-map/.
 *
 * @package TCLAS
 */

get_header();
?>

<div class="tclas-page-header">
	<div class="container-tclas">
		<?php tclas_breadcrumb(); ?>
		<span class="tclas-eyebrow"><?php esc_html_e( 'Member community', 'tclas' ); ?></span>
		<h1 class="tclas-page-header__title"><?php the_title(); ?></h1>
	</div>
</div>

<section class="tclas-section">
	<div class="container-tclas">

		<?php if ( ! tclas_is_member() ) : ?>

			<!-- Member gate -->
			<div class="tclas-member-gate">
				<div class="tclas-member-gate__inner">
					<?php tclas_illustration( 'member_gate_illustration', __( 'Member-only content', 'tclas' ), 'tclas-member-gate__illustration' ); ?>
					<h2><?php esc_html_e( 'Members only.', 'tclas' ); ?></h2>
					<p>
						<?php esc_html_e( 'The ancestral commune map is available to TCLAS members. Join us to pin your Luxembourg roots and see where fellow members come from.', 'tclas' ); ?>
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

			<div class="tclas-member-map-intro">
				<p>
					<?php esc_html_e( 'Explore the Luxembourg communes where TCLAS members trace their roots. Tap a commune to see surnames and connect with fellow members.', 'tclas' ); ?>
				</p>
			</div>

			<?php echo do_shortcode( '[tclas_ancestor_map layout="split"]' ); ?>

		<?php endif; ?>

	</div>
</section>

<?php get_footer(); ?>
