<?php
/**
 * Template Name: Donate
 *
 * Donation page with GiveWP form embedded via shortcode.
 * Form ID stored in ACF Theme Options (donate_form_id).
 *
 * @package TCLAS
 */

get_header();
$is_member = tclas_is_member();
?>

<!-- ── Page header ──────────────────────────────────────────────────────── -->
<div class="tclas-page-header">
	<div class="container-tclas">
		<?php tclas_breadcrumb(); ?>
		<span class="tclas-eyebrow"><?php esc_html_e( 'Support TCLAS', 'tclas' ); ?></span>
		<h1 class="tclas-page-header__title"><?php the_title(); ?></h1>
	</div>
</div>

<!-- ── Introduction ─────────────────────────────────────────────────────── -->
<section class="tclas-section tclas-donate-intro">
	<div class="container-tclas container--medium">
		<?php
		$donate_lede = function_exists( 'get_field' ) ? get_field( 'donate_lede' ) : '';
		if ( $donate_lede ) {
			echo '<div class="tclas-donate-lede">' . wp_kses_post( $donate_lede ) . '</div>';
		} else {
		?>
		<p class="tclas-donate-lede">
			TCLAS is a volunteer-run 501(c)(3) nonprofit that preserves and celebrates the Luxembourg heritage of the Twin Cities. Your tax-deductible gift helps us host events, maintain our research resources, and grow the community.
		</p>
		<?php } ?>
	</div>
</section>

<!-- ── Where your gift goes ─────────────────────────────────────────────── -->
<section class="tclas-section tclas-bg-warm">
	<div class="container-tclas">
		<span class="tclas-eyebrow"><?php esc_html_e( 'Where your gift goes', 'tclas' ); ?></span>
		<h2><?php esc_html_e( 'Every dollar stays close to home.', 'tclas' ); ?></h2>
		<div class="tclas-donate-impact">
		<?php if ( function_exists( 'have_rows' ) && have_rows( 'donate_impact_items' ) ) : ?>
			<?php while ( have_rows( 'donate_impact_items' ) ) : the_row(); ?>
			<div class="tclas-donate-impact__item">
				<h3><?php echo esc_html( get_sub_field( 'impact_title' ) ); ?></h3>
				<p><?php echo esc_html( get_sub_field( 'impact_desc' ) ); ?></p>
			</div>
			<?php endwhile; ?>
		<?php else : ?>

			<div class="tclas-donate-impact__item">
				<h3><?php esc_html_e( 'Events and gatherings', 'tclas' ); ?></h3>
				<p><?php esc_html_e( 'Receptions, celebrations, and informal meetups that bring the community together throughout the year.', 'tclas' ); ?></p>
			</div>

			<div class="tclas-donate-impact__item">
				<h3><?php esc_html_e( 'Citizenship resources', 'tclas' ); ?></h3>
				<p><?php esc_html_e( 'Research tools, guides, and the eligibility quiz that help members navigate the Luxembourg citizenship process.', 'tclas' ); ?></p>
			</div>

			<div class="tclas-donate-impact__item">
				<h3><?php esc_html_e( 'Cultural preservation', 'tclas' ); ?></h3>
				<p><?php esc_html_e( 'The ancestral commune map, member stories, and educational content that keep our shared heritage alive.', 'tclas' ); ?></p>
			</div>

			<div class="tclas-donate-impact__item">
				<h3><?php esc_html_e( 'Community growth', 'tclas' ); ?></h3>
				<p><?php esc_html_e( 'Outreach, the newsletter, and partnerships that help more Luxembourgers in the Twin Cities find their people.', 'tclas' ); ?></p>
			</div>

		<?php endif; ?>
		</div>
	</div>
</section>

<!-- ── Donation form ────────────────────────────────────────────────────── -->
<section class="tclas-section" id="donate-form">
	<div class="container-tclas container--medium">
		<h2 class="tclas-donate-form-heading"><?php esc_html_e( 'Make a gift', 'tclas' ); ?></h2>
		<?php tclas_donate_form(); ?>
		<?php
		$tax_note = function_exists( 'get_field' ) ? get_field( 'donate_tax_note' ) : '';
		if ( ! $tax_note ) {
			$tax_note = 'TCLAS is a 501(c)(3) nonprofit organization. Contributions are tax-deductible to the extent allowed by law. You will receive a receipt for your records.';
		}
		?>
		<p class="tclas-donate-tax-note"><?php echo esc_html( $tax_note ); ?></p>
	</div>
</section>

<!-- ── Bottom CTA ──────────────────────────────────────────────────────── -->
<section class="tclas-section bg-ardoise">
	<div class="container-tclas container--medium tclas-donate-cta">
		<?php if ( $is_member ) : ?>
			<h2><?php echo tclas_ltz( 'Merci.', 'Thank you.', false ); ?></h2>
			<p><?php esc_html_e( 'Your membership and your generosity keep this community going. Share the word with someone who might want to join.', 'tclas' ); ?></p>
			<a href="<?php echo esc_url( home_url( '/join/' ) ); ?>" class="btn btn-outline-light">
				<?php esc_html_e( 'Invite a friend to join', 'tclas' ); ?>
			</a>
		<?php else : ?>
			<h2><?php echo tclas_ltz( 'Komm mat.', 'Come along.', false ); ?></h2>
			<p><?php esc_html_e( 'Donations keep TCLAS running, but membership makes you part of the community. Join and connect with others who share your Luxembourg roots.', 'tclas' ); ?></p>
			<a href="<?php echo esc_url( home_url( '/join/' ) ); ?>" class="btn btn-primary btn-lg">
				<?php esc_html_e( 'Become a member', 'tclas' ); ?>
			</a>
		<?php endif; ?>
	</div>
</section>

<?php get_footer(); ?>
