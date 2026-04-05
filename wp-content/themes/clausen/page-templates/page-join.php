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
$is_member    = tclas_is_member();
?>

<!-- ── Page header ──────────────────────────────────────────────────────── -->
<div class="tclas-page-header">
	<div class="container-tclas">
		<?php tclas_breadcrumb(); ?>
		<h1 class="tclas-page-header__title"><?php the_title(); ?></h1>
	</div>
</div>

<!-- ── Intro ─────────────────────────────────────────────────────────────── -->
<section class="tclas-section tclas-join-intro">
	<div class="container-tclas">
		<?php
		$join_lede = function_exists( 'get_field' ) ? get_field( 'join_lede' ) : '';
		if ( $join_lede ) {
			echo '<div class="tclas-join-lede">' . wp_kses_post( $join_lede ) . '</div>';
		} else {
		?>
		<div class="tclas-join-lede">
			<p><?php esc_html_e( 'Whether your Luxembourg story goes back five generations or five months — or you simply married into one — you belong here. Membership connects you to a warm, curious community that spans the Atlantic.', 'tclas' ); ?></p>
		</div>
		<?php } ?>
	</div>
</section>

<?php if ( $is_member ) : ?>

<!-- ── Member referral banner ───────────────────────────────────────────── -->
<section class="tclas-join-referral" aria-labelledby="referral-heading">
	<div class="container-tclas">
		<div class="tclas-join-referral__inner">
			<div class="tclas-join-referral__text">
				<h2 class="tclas-join-referral__heading" id="referral-heading">
					<?php esc_html_e( 'Know someone who&rsquo;d love this?', 'tclas' ); ?>
				</h2>
				<p><?php esc_html_e( "You're already a member &mdash; thank you! Share your personal invitation link and bring someone new to the community.", 'tclas' ); ?></p>
			</div>
			<div class="tclas-join-referral__action">
				<div class="tclas-join-referral__input-wrap">
					<label for="join-referral-url" class="screen-reader-text"><?php esc_html_e( 'Your referral link', 'tclas' ); ?></label>
					<input
						type="text"
						class="tclas-join-referral__url"
						id="join-referral-url"
						value="<?php echo esc_attr( tclas_get_referral_url() ); ?>"
						readonly
						onfocus="this.select()"
					/>
					<button
						class="btn tclas-join-referral__copy"
						data-referral-copy
						type="button"
					><?php esc_html_e( 'Copy link', 'tclas' ); ?></button>
				</div>
			</div>
		</div>
	</div>
</section>

<?php endif; ?>

<!-- ── Tier cards ────────────────────────────────────────────────────────── -->
<section class="tclas-section bg-ardoise" id="tiers">
	<div class="container-tclas">
		<div class="tclas-tiers">

			<?php
			$join_tiers = function_exists( 'get_field' ) ? get_field( 'join_tiers' ) : [];
			$tier_0 = ! empty( $join_tiers[0] ) ? $join_tiers[0] : [];
			$tier_1 = ! empty( $join_tiers[1] ) ? $join_tiers[1] : [];
			$tier_2 = ! empty( $join_tiers[2] ) ? $join_tiers[2] : [];
			?>

			<!-- Individual -->
			<div class="tclas-tier">
				<div class="tclas-tier__header">
					<span class="tclas-tier__invite"><?php echo esc_html( ! empty( $tier_0['tier_invite'] ) ? $tier_0['tier_invite'] : 'Just you — and everyone here.' ); ?></span>
					<span class="tclas-tier__name"><?php esc_html_e( 'Individual', 'tclas' ); ?></span>
					<span class="tclas-tier__price">$<?php echo esc_html( $price_individual ); ?></span>
					<span class="tclas-tier__period"><?php esc_html_e( 'per year', 'tclas' ); ?></span>
				</div>
				<?php if ( ! empty( $tier_0['tier_note'] ) ) : ?>
				<div class="tclas-tier__body">
					<p class="tclas-tier__note"><?php echo esc_html( $tier_0['tier_note'] ); ?></p>
				</div>
				<?php endif; ?>
				<div class="tclas-tier__cta">
					<?php if ( $is_member ) : ?>
						<span class="tclas-tier__member-note"><?php esc_html_e( "You're a member &mdash; welcome!", 'tclas' ); ?></span>
					<?php else : ?>
						<a href="<?php echo esc_url( add_query_arg( 'level', '1', $checkout_url ) ); ?>" class="btn btn-outline-light">
							<?php esc_html_e( 'Join as individual', 'tclas' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>

			<!-- Family -->
			<div class="tclas-tier tclas-tier--featured">
				<div class="tclas-tier__header">
					<span class="tclas-tier__invite"><?php echo esc_html( ! empty( $tier_1['tier_invite'] ) ? $tier_1['tier_invite'] : 'Bring the people you love.' ); ?></span>
					<span class="tclas-tier__name"><?php esc_html_e( 'Family', 'tclas' ); ?></span>
					<span class="tclas-tier__price">$<?php echo esc_html( $price_family ); ?></span>
					<span class="tclas-tier__period"><?php esc_html_e( 'per year', 'tclas' ); ?></span>
				</div>
				<div class="tclas-tier__body">
					<p class="tclas-tier__note"><?php echo esc_html( ! empty( $tier_1['tier_note'] ) ? $tier_1['tier_note'] : 'Covers up to four household members.' ); ?></p>
				</div>
				<div class="tclas-tier__cta">
					<?php if ( $is_member ) : ?>
						<span class="tclas-tier__member-note"><?php esc_html_e( "You're a member &mdash; welcome!", 'tclas' ); ?></span>
					<?php else : ?>
						<a href="<?php echo esc_url( add_query_arg( 'level', '2', $checkout_url ) ); ?>" class="btn btn-primary">
							<?php esc_html_e( 'Join as family', 'tclas' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>

			<!-- Student/Senior -->
			<div class="tclas-tier">
				<div class="tclas-tier__header">
					<span class="tclas-tier__invite"><?php echo esc_html( ! empty( $tier_2['tier_invite'] ) ? $tier_2['tier_invite'] : 'Same community, adjusted rate.' ); ?></span>
					<span class="tclas-tier__name"><?php esc_html_e( 'Student / Senior', 'tclas' ); ?></span>
					<span class="tclas-tier__price">$<?php echo esc_html( $price_student ); ?></span>
					<span class="tclas-tier__period"><?php esc_html_e( 'per year', 'tclas' ); ?></span>
				</div>
				<div class="tclas-tier__body">
					<p class="tclas-tier__note"><?php echo esc_html( ! empty( $tier_2['tier_note'] ) ? $tier_2['tier_note'] : 'For full-time students and seniors.' ); ?></p>
				</div>
				<div class="tclas-tier__cta">
					<?php if ( $is_member ) : ?>
						<span class="tclas-tier__member-note"><?php esc_html_e( "You're a member &mdash; welcome!", 'tclas' ); ?></span>
					<?php else : ?>
						<a href="<?php echo esc_url( add_query_arg( 'level', '3', $checkout_url ) ); ?>" class="btn btn-outline-light">
							<?php esc_html_e( 'Join as student/senior', 'tclas' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>

		</div><!-- .tclas-tiers -->
	</div>
</section>

<!-- ── Member perks ──────────────────────────────────────────────────────── -->
<section class="tclas-section">
	<div class="container-tclas">
		<span class="tclas-eyebrow"><?php esc_html_e( 'What you get', 'tclas' ); ?></span>
		<h2><?php esc_html_e( 'More than a card in your wallet.', 'tclas' ); ?></h2>
		<div class="tclas-join-perks">
		<?php if ( function_exists( 'have_rows' ) && have_rows( 'join_perks' ) ) : ?>
			<?php while ( have_rows( 'join_perks' ) ) : the_row(); ?>
			<div class="tclas-join-perk">
				<h3><?php echo esc_html( get_sub_field( 'perk_title' ) ); ?></h3>
				<p><?php echo esc_html( get_sub_field( 'perk_desc' ) ); ?></p>
			</div>
			<?php endwhile; ?>
		<?php else : ?>

			<div class="tclas-join-perk">
				<h3><?php esc_html_e( 'The member hub', 'tclas' ); ?></h3>
				<p><?php esc_html_e( 'A private corner of the site just for members. Connect with others through the member directory, explore the ancestral commune map, and share your story.', 'tclas' ); ?></p>
			</div>

			<div class="tclas-join-perk">
				<h3><?php esc_html_e( 'Member events', 'tclas' ); ?></h3>
				<p><?php esc_html_e( 'Receptions, celebrations, and informal gatherings &mdash; when and where they happen. We&rsquo;re a young organization, and the calendar is growing.', 'tclas' ); ?></p>
			</div>

			<div class="tclas-join-perk">
				<h3><?php esc_html_e( 'Citizenship resources', 'tclas' ); ?></h3>
				<p><?php esc_html_e( 'Guides, links, and a community of people who have navigated the citizenship process &mdash; and are happy to share what they learned.', 'tclas' ); ?></p>
			</div>

			<div class="tclas-join-perk">
				<h3><?php esc_html_e( 'Annual member gift', 'tclas' ); ?></h3>
				<p><?php esc_html_e( 'A limited-edition design each year, for members who like a tangible reminder of where they come from.', 'tclas' ); ?></p>
			</div>

			<div class="tclas-join-perk">
				<h3><?php esc_html_e( 'Partner discounts', 'tclas' ); ?></h3>
				<p><?php esc_html_e( 'We&rsquo;re building relationships with language and cultural organizations in the Twin Cities. Member perks are coming.', 'tclas' ); ?></p>
			</div>

			<div class="tclas-join-perk">
				<h3><?php esc_html_e( 'Community', 'tclas' ); ?></h3>
				<p><?php esc_html_e( 'The main thing. People who understand why this matters to you &mdash; because it matters to them, too.', 'tclas' ); ?></p>
			</div>

		<?php endif; ?>
		</div>
	</div>
</section>

<!-- ── Volunteer placeholder ─────────────────────────────────────────────── -->
<section class="tclas-section tclas-join-volunteer">
	<div class="container-tclas container--medium">
		<span class="tclas-eyebrow"><?php esc_html_e( 'Get involved', 'tclas' ); ?></span>
		<h2><?php esc_html_e( 'Volunteer with TCLAS', 'tclas' ); ?></h2>
		<?php
		$vol_body  = function_exists( 'get_field' ) ? get_field( 'join_volunteer_body' ) : '';
		$vol_email = function_exists( 'get_field' ) ? get_field( 'join_volunteer_email' ) : '';
		$vol_email = $vol_email ?: 'info@tclas.org';
		if ( $vol_body ) {
			echo wp_kses_post( $vol_body );
		} else {
		?>
		<p class="tclas-join-volunteer__placeholder">
			<?php esc_html_e( '[Volunteer description coming soon.]', 'tclas' ); ?>
		</p>
		<?php } ?>
		<a href="mailto:<?php echo esc_attr( $vol_email ); ?>" class="btn btn-outline-ardoise">
			<?php esc_html_e( 'Get in touch', 'tclas' ); ?>
		</a>
	</div>
</section>

<!-- ── Bottom CTA (non-members only) ────────────────────────────────────── -->
<?php if ( ! $is_member ) : ?>
<section class="tclas-join-cta">
	<div class="container-tclas">
		<h2><?php echo tclas_ltz( 'Komm mat.', 'Come along.', false ); ?></h2>
		<?php
		$join_cta_body = function_exists( 'get_field' ) ? get_field( 'join_bottom_cta' ) : '';
		?>
		<p><?php echo esc_html( $join_cta_body ?: 'Membership is open to anyone with a Luxembourg connection — and to anyone curious enough to find one.' ); ?></p>
		<a href="#tiers" class="btn btn-outline-light btn-lg">
			<?php esc_html_e( 'Choose your membership', 'tclas' ); ?>
		</a>
	</div>
</section>
<?php endif; ?>

<?php if ( $is_member ) : ?>
<script>
( function () {
	var btn = document.querySelector( '[data-referral-copy]' );
	if ( ! btn ) { return; }
	var label = btn.textContent;
	btn.addEventListener( 'click', function () {
		var input = document.getElementById( 'join-referral-url' );
		if ( ! input ) { return; }
		var url = input.value;
		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( url ).then( function () {
				btn.textContent = '<?php echo esc_js( __( 'Copied!', 'tclas' ) ); ?>';
				setTimeout( function () { btn.textContent = label; }, 2000 );
			} );
		} else {
			input.select();
			document.execCommand( 'copy' );
			btn.textContent = '<?php echo esc_js( __( 'Copied!', 'tclas' ) ); ?>';
			setTimeout( function () { btn.textContent = label; }, 2000 );
		}
	} );
} )();
</script>
<?php endif; ?>

<?php get_footer(); ?>
