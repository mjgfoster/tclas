<?php
/**
 * 404 template
 *
 * @package TCLAS
 */

get_header();
?>

<section class="tclas-hero tclas-hero--page bg-ardoise">
	<div class="tclas-hero__overlay" aria-hidden="true"></div>
	<div class="container-tclas">
		<div class="tclas-hero__content">
			<span class="tclas-hero__eyebrow">404</span>
			<h1 class="tclas-hero__title">
				<?php echo tclas_ltz( 'Hoppla.', 'Oops.', false ); ?>
			</h1>
			<p class="tclas-hero__subtitle">
				<?php esc_html_e( "That page seems to have moved — perhaps it emigrated to Luxembourg. Let's get you somewhere useful.", 'tclas' ); ?>
			</p>
			<div class="tclas-hero__actions">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn-primary">
					<?php esc_html_e( 'Back to home', 'tclas' ); ?>
				</a>
				<a href="<?php echo esc_url( home_url( '/events/' ) ); ?>" class="btn btn-outline-light">
					<?php esc_html_e( 'Upcoming events', 'tclas' ); ?>
				</a>
			</div>
		</div>
	</div>
</section>

<?php get_footer(); ?>
