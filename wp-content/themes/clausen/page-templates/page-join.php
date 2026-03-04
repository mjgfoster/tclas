<?php
/**
 * Template Name: Join / Membership
 *
 * Branded membership page with tier cards linking to PMPro checkout.
 * If ?level=X is present, redirects straight to checkout.
 *
 * @package TCLAS
 */

// Redirect ?level=X straight to PMPro checkout.
if ( isset( $_GET['level'] ) && absint( $_GET['level'] ) > 0 ) {
	$checkout = function_exists( 'pmpro_url' ) ? pmpro_url( 'checkout' ) : '';
	if ( $checkout ) {
		wp_safe_redirect( add_query_arg( 'level', absint( $_GET['level'] ), $checkout ) );
		exit;
	}
}

get_header();

$price_individual = function_exists( 'get_field' ) ? (int) get_field( 'price_individual', 'option' ) : 0;
$price_family     = function_exists( 'get_field' ) ? (int) get_field( 'price_family',     'option' ) : 0;
$price_student    = function_exists( 'get_field' ) ? (int) get_field( 'price_student',    'option' ) : 0;
$price_individual = $price_individual ?: 30;
$price_family     = $price_family     ?: 45;
$price_student    = $price_student    ?: 15;

$checkout_url = function_exists( 'pmpro_url' ) ? pmpro_url( 'checkout' ) : home_url( '/membership-checkout/' );
?>

<!-- Page header -->
<div class="tclas-page-header">
	<div class="container-tclas">
		<span class="tclas-eyebrow"><?php esc_html_e( 'Membership', 'tclas' ); ?></span>
		<h1 class="tclas-page-header__title"><?php esc_html_e( 'Find your people.', 'tclas' ); ?></h1>
	</div>
</div>

<!-- Intro -->
<section class="tclas-section bg-white tclas-join-intro">
	<div class="container-tclas container--narrow">
		<p class="tclas-join-lede">
			<?php esc_html_e( 'Whether your Luxembourg story goes back five generations or five months — or you simply married into one — you belong here. Membership connects you to a warm, curious community that spans the Atlantic.', 'tclas' ); ?>
		</p>
	</div>
</section>

<!-- Tier cards -->
<section class="tclas-section bg-ardoise" id="tiers">
	<div class="container-tclas">
		<div class="tclas-tiers">

			<!-- Individual -->
			<div class="tclas-tier">
				<div class="tclas-tier__header">
					<span class="tclas-tier__invite"><?php esc_html_e( 'Just you — and everyone here.', 'tclas' ); ?></span>
					<span class="tclas-tier__name"><?php esc_html_e( 'Individual', 'tclas' ); ?></span>
					<span class="tclas-tier__price">$<?php echo esc_html( $price_individual ); ?></span>
					<span class="tclas-tier__period"><?php esc_html_e( 'per year', 'tclas' ); ?></span>
				</div>
				<div class="tclas-tier__body">
					<ul class="tclas-tier__features">
						<li><?php esc_html_e( 'Events access', 'tclas' ); ?></li>
						<li><?php esc_html_e( 'Member directory', 'tclas' ); ?></li>
						<li><?php esc_html_e( 'Newsletter (members edition)', 'tclas' ); ?></li>
						<li><?php esc_html_e( 'Document library', 'tclas' ); ?></li>
						<li><?php esc_html_e( 'Forum access', 'tclas' ); ?></li>
						<li><?php esc_html_e( 'Ancestral commune map', 'tclas' ); ?></li>
					</ul>
				</div>
				<div class="tclas-tier__cta">
					<a href="<?php echo esc_url( add_query_arg( 'level', '1', $checkout_url ) ); ?>" class="btn btn-outline-light">
						<?php esc_html_e( 'Join as individual', 'tclas' ); ?>
					</a>
				</div>
			</div>

			<!-- Family — featured -->
			<div class="tclas-tier tclas-tier--featured">
				<div class="tclas-tier__badge"><?php esc_html_e( 'Most popular', 'tclas' ); ?></div>
				<div class="tclas-tier__header">
					<span class="tclas-tier__invite"><?php esc_html_e( 'Bring the people you love.', 'tclas' ); ?></span>
					<span class="tclas-tier__name"><?php esc_html_e( 'Family', 'tclas' ); ?></span>
					<span class="tclas-tier__price">$<?php echo esc_html( $price_family ); ?></span>
					<span class="tclas-tier__period"><?php esc_html_e( 'per year', 'tclas' ); ?></span>
				</div>
				<div class="tclas-tier__body">
					<ul class="tclas-tier__features">
						<li><?php esc_html_e( 'Everything in Individual', 'tclas' ); ?></li>
						<li><?php esc_html_e( 'Up to 4 household members', 'tclas' ); ?></li>
						<li><?php esc_html_e( 'Family profile on directory', 'tclas' ); ?></li>
					</ul>
				</div>
				<div class="tclas-tier__cta">
					<a href="<?php echo esc_url( add_query_arg( 'level', '2', $checkout_url ) ); ?>" class="btn btn-primary">
						<?php esc_html_e( 'Join as family', 'tclas' ); ?>
					</a>
				</div>
			</div>

			<!-- Student -->
			<div class="tclas-tier">
				<div class="tclas-tier__header">
					<span class="tclas-tier__invite"><?php esc_html_e( 'Your Luxembourg story is just beginning.', 'tclas' ); ?></span>
					<span class="tclas-tier__name"><?php esc_html_e( 'Student', 'tclas' ); ?></span>
					<span class="tclas-tier__price">$<?php echo esc_html( $price_student ); ?></span>
					<span class="tclas-tier__period"><?php esc_html_e( 'per year', 'tclas' ); ?></span>
				</div>
				<div class="tclas-tier__body">
					<ul class="tclas-tier__features">
						<li><?php esc_html_e( 'Everything in Individual', 'tclas' ); ?></li>
						<li><?php esc_html_e( 'Valid with student ID', 'tclas' ); ?></li>
					</ul>
				</div>
				<div class="tclas-tier__cta">
					<a href="<?php echo esc_url( add_query_arg( 'level', '3', $checkout_url ) ); ?>" class="btn btn-outline-light">
						<?php esc_html_e( 'Join as student', 'tclas' ); ?>
					</a>
				</div>
			</div>

		</div><!-- .tclas-tiers -->
	</div>
</section>

<!-- What's included -->
<section class="tclas-section bg-white">
	<div class="container-tclas container--narrow">
		<span class="tclas-eyebrow"><?php esc_html_e( 'What you get', 'tclas' ); ?></span>
		<h2 class="tclas-ruled"><?php esc_html_e( 'More than a card in your wallet.', 'tclas' ); ?></h2>
		<div class="tclas-join-features">
			<div class="tclas-join-feature">
				<h3><?php esc_html_e( 'Events & gatherings', 'tclas' ); ?></h3>
				<p><?php esc_html_e( 'Schueberfouer picnics, National Day celebrations, genealogy workshops, and more — throughout the year.', 'tclas' ); ?></p>
			</div>
			<div class="tclas-join-feature">
				<h3><?php esc_html_e( 'Citizenship resources', 'tclas' ); ?></h3>
				<p><?php esc_html_e( 'Step-by-step guides, document templates, and a community of people who have been through the process.', 'tclas' ); ?></p>
			</div>
			<div class="tclas-join-feature">
				<h3><?php esc_html_e( 'Community & connection', 'tclas' ); ?></h3>
				<p><?php esc_html_e( 'A member directory, private forum, ancestral commune map, and a newsletter written for people like you.', 'tclas' ); ?></p>
			</div>
		</div>
	</div>
</section>

<!-- Final CTA -->
<section class="tclas-join-cta">
	<div class="container-tclas">
		<h2><?php echo tclas_ltz( 'Komm mat.', 'Come along.', false ); ?></h2>
		<p><?php esc_html_e( 'Membership is open to anyone with a Luxembourg connection — and open to anyone curious enough to find one.', 'tclas' ); ?></p>
		<a href="#tiers" class="btn btn-outline-light btn-lg">
			<?php esc_html_e( 'Choose your membership', 'tclas' ); ?>
		</a>
	</div>
</section>

<?php get_footer(); ?>
