<?php
/**
 * Template Name: Referral welcome page
 *
 * Shown when a prospective member arrives via a personalised referral link.
 * URL: twincities.lu/welcome/?ref=username
 *
 * @package TCLAS
 */

get_header();

$referrer      = tclas_get_referrer();
$referrer_name = $referrer ? $referrer->first_name ?: $referrer->display_name : '';
$join_url      = get_page_link( get_option( 'pmpro_levels_page_id' ) ) ?: home_url( '/join/' );
if ( $referrer ) {
	$join_url = add_query_arg( 'ref', $referrer->user_login, $join_url );
}

$price_individual = function_exists( 'get_field' ) ? (int) get_field( 'price_individual', 'option' ) : 0;
$price_family     = function_exists( 'get_field' ) ? (int) get_field( 'price_family',     'option' ) : 0;
$price_student    = function_exists( 'get_field' ) ? (int) get_field( 'price_student',    'option' ) : 0;
$price_individual = $price_individual ?: 30;
$price_family     = $price_family     ?: 45;
$price_student    = $price_student    ?: 15;
?>

<div class="tclas-referral-landing">

	<!-- Intro -->
	<div class="tclas-referral-landing__intro">
		<div class="container-tclas container--narrow">

			<?php if ( $referrer_name ) : ?>
				<div class="tclas-referral-landing__referred-by">
					<?php
					printf(
						/* translators: %s: referrer first name */
						esc_html__( '%s thought you'd like to meet us.', 'tclas' ),
						esc_html( $referrer_name )
					);
					?>
				</div>
			<?php endif; ?>

			<div class="tclas-referral-landing__hug">
				<img
					src="<?php echo esc_url( TCLAS_ASSETS . '/images/hug-cover.png' ); ?>"
					alt="<?php esc_attr_e( 'Minnesota and Luxembourg — a warm welcome', 'tclas' ); ?>"
					loading="lazy"
				>
			</div>

			<h1><?php esc_html_e( 'Where the Twin Cities meet the Grand Duchy.', 'tclas' ); ?></h1>
			<p class="tclas-referral-landing__lede">
				<?php esc_html_e( "We're Minnesotans with Luxembourg in our bones, passports and hearts.", 'tclas' ); ?>
				<?php echo tclas_ltz( 'Mir sinn hei', 'We are here', false ); ?> &mdash;
				<?php esc_html_e( "and we'd love to meet you.", 'tclas' ); ?>
			</p>

			<div class="tclas-referral-landing__cta-row">
				<a href="<?php echo esc_url( $join_url ); ?>" class="btn btn-primary btn-lg">
					<?php esc_html_e( 'Join TCLAS', 'tclas' ); ?>
				</a>
				<a href="<?php echo esc_url( home_url( '/about/' ) ); ?>" class="btn btn-outline-ardoise">
					<?php esc_html_e( 'Learn more', 'tclas' ); ?>
				</a>
			</div>

		</div>
	</div>

	<!-- What we are -->
	<div class="tclas-section bg-white">
		<div class="container-tclas container--narrow">
			<span class="tclas-eyebrow"><?php esc_html_e( 'About TCLAS', 'tclas' ); ?></span>
			<h2 class="tclas-ruled"><?php esc_html_e( 'A community in two places at once.', 'tclas' ); ?></h2>
			<p><?php esc_html_e( 'The Twin Cities Luxembourg American Society connects Minnesotans with Luxembourg — through ancestry, citizenship, travel, language, food, and the occasional glass of Moselle Riesling.', 'tclas' ); ?></p>
			<p><?php esc_html_e( 'We are emphatically not a heritage museum. We are a living community of curious, warm, well-traveled people who happen to share a connection to a small, extraordinary country.', 'tclas' ); ?></p>
			<p><?php esc_html_e( 'Whether your Luxembourg story goes back five generations or five months — or whether you simply married into one — you belong here.', 'tclas' ); ?></p>
		</div>
	</div>

	<!-- Mini membership tiers -->
	<div class="tclas-section bg-ardoise">
		<div class="container-tclas container--narrow">
			<span class="tclas-eyebrow tclas-eyebrow--light"><?php esc_html_e( 'Membership', 'tclas' ); ?></span>
			<h2><?php esc_html_e( 'Simple, affordable, and worth every penny.', 'tclas' ); ?></h2>
			<div class="tclas-tiers-mini">
				<div class="tclas-tier-mini tclas-tier-mini--individual">
					<span class="tclas-tier-mini__name"><?php esc_html_e( 'Individual', 'tclas' ); ?></span>
					<span class="tclas-tier-mini__price">$<?php echo esc_html( $price_individual ); ?></span>
					<span class="tclas-tier-mini__period"><?php esc_html_e( '/year', 'tclas' ); ?></span>
				</div>
				<div class="tclas-tier-mini tclas-tier-mini--family">
					<span class="tclas-tier-mini__name"><?php esc_html_e( 'Family', 'tclas' ); ?></span>
					<span class="tclas-tier-mini__price">$<?php echo esc_html( $price_family ); ?></span>
					<span class="tclas-tier-mini__period"><?php esc_html_e( '/year', 'tclas' ); ?></span>
				</div>
				<div class="tclas-tier-mini tclas-tier-mini--student">
					<span class="tclas-tier-mini__name"><?php esc_html_e( 'Student', 'tclas' ); ?></span>
					<span class="tclas-tier-mini__price">$<?php echo esc_html( $price_student ); ?></span>
					<span class="tclas-tier-mini__period"><?php esc_html_e( '/year', 'tclas' ); ?></span>
				</div>
			</div>
			<p class="tclas-referral-landing__tier-cta">
				<a href="<?php echo esc_url( $join_url ); ?>" class="btn btn-primary btn-lg">
					<?php esc_html_e( 'Join us today', 'tclas' ); ?>
				</a>
			</p>
		</div>
	</div>

</div>

<?php get_footer(); ?>
