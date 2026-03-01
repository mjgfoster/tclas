<?php
/**
 * Homepage template
 *
 * Sections (alternating light/dark):
 *  1. Hero            — ardoise (dark)
 *  2. Events strip    — white (light)
 *  3. Welcome / About — or-pale (warm light)
 *  4. News            — off-white (light)
 *  5. Membership      — ardoise (dark)
 *  6. Join CTA        — crimson
 *  Footer             — ardoise (dark)
 *
 * @package TCLAS
 */

get_header();
?>

<!-- ── 1. HERO ──────────────────────────────────────────────────────────── -->
<section class="tclas-hero<?php echo tclas_is_national_day_season() ? ' tclas-hero--national-day' : ''; ?>" aria-label="<?php esc_attr_e( 'Welcome', 'tclas' ); ?>">

	<?php tclas_render_hero_bg(); ?>
	<div class="tclas-hero__overlay" aria-hidden="true"></div>

	<div class="container-tclas">
		<div class="tclas-hero__content">
			<span class="tclas-hero__eyebrow">
				<?php
				echo tclas_ltz( 'Mir sinn hei', 'We are here', false );
				?> &mdash; Twin Cities Luxembourg American Society
			</span>

			<h1 class="tclas-hero__title">
				<?php esc_html_e( 'Where the Twin Cities meet the Grand Duchy.', 'tclas' ); ?>
			</h1>

			<p class="tclas-hero__subtitle">
				<?php esc_html_e( "We're Minnesotans with Luxembourg in our bones, passports and hearts.", 'tclas' ); ?>
				<?php echo tclas_ltz( 'Mir sinn hei', 'We are here', false ); ?> &mdash;
				<?php esc_html_e( "and we'd love to meet you.", 'tclas' ); ?>
			</p>

			<div class="tclas-hero__actions">
				<?php if ( ! tclas_is_member() ) : ?>
					<a href="<?php echo esc_url( home_url( '/join/' ) ); ?>" class="btn btn-primary btn-lg">
						<?php esc_html_e( 'Join us', 'tclas' ); ?>
					</a>
					<a href="<?php echo esc_url( home_url( '/about/' ) ); ?>" class="btn btn-outline-light">
						<?php esc_html_e( 'Learn more', 'tclas' ); ?>
					</a>
				<?php else : ?>
					<a href="<?php echo esc_url( home_url( '/events/' ) ); ?>" class="btn btn-primary btn-lg">
						<?php esc_html_e( 'Upcoming events', 'tclas' ); ?>
					</a>
					<a href="<?php echo esc_url( home_url( '/member-hub/' ) ); ?>" class="btn btn-outline-light">
						<?php esc_html_e( 'Member hub', 'tclas' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</div>

</section>

<!-- ── 2. EVENTS STRIP ──────────────────────────────────────────────────── -->
<section class="tclas-events tclas-section--sm" id="events">
	<div class="container-tclas">
		<div class="tclas-events__header">
			<div>
				<span class="tclas-eyebrow"><?php esc_html_e( 'What\'s happening', 'tclas' ); ?></span>
				<h2><?php esc_html_e( 'Upcoming events', 'tclas' ); ?></h2>
			</div>
			<a href="<?php echo esc_url( home_url( '/events/' ) ); ?>" class="btn btn-outline-ardoise btn-sm">
				<?php esc_html_e( 'All events', 'tclas' ); ?>
			</a>
		</div>

		<?php
		$events = tclas_get_upcoming_events( 3 );
		if ( $events ) :
		?>
			<div class="tclas-events-grid">
				<?php foreach ( $events as $event ) : ?>
					<?php tclas_render_event_card( $event ); ?>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<?php tclas_render_events_empty(); ?>
		<?php endif; ?>

	</div>
</section>

<!-- ── 3. WELCOME / ABOUT ────────────────────────────────────────────────── -->
<section class="tclas-welcome" id="about">
	<div class="container-tclas">
		<div class="tclas-welcome__inner">
			<div class="tclas-welcome__content" data-reveal>
				<span class="tclas-eyebrow"><?php esc_html_e( 'About TCLAS', 'tclas' ); ?></span>
				<h2 class="tclas-ruled"><?php esc_html_e( 'A community in two places at once.', 'tclas' ); ?></h2>
				<p>
					<?php esc_html_e( 'The Twin Cities Luxembourg American Society connects Minnesotans with Luxembourg — through ancestry, citizenship, travel, language, and love of a very good Moselle Riesling.', 'tclas' ); ?>
				</p>
				<p>
					<?php esc_html_e( 'We are emphatically not a heritage museum. We are a living community of curious, warm, well-traveled people who happen to share a connection to the Grand Duchy.', 'tclas' ); ?>
				</p>
				<p>
					<?php esc_html_e( 'Whether your Luxembourg story goes back five generations or five months, you belong here.', 'tclas' ); ?>
				</p>
				<a href="<?php echo esc_url( home_url( '/about/' ) ); ?>" class="btn btn-outline-ardoise">
					<?php esc_html_e( 'Our story', 'tclas' ); ?>
				</a>
			</div>

			<div class="tclas-welcome__illustration" data-reveal>
				<?php tclas_illustration( 'welcome_illustration', __( 'TCLAS members gathered around a table', 'tclas' ) ); ?>
			</div>
		</div>
	</div>
</section>

<!-- ── 4. NEWS ──────────────────────────────────────────────────────────── -->
<section class="tclas-news" id="news">
	<div class="container-tclas">
		<div class="tclas-news__header">
			<div>
				<span class="tclas-eyebrow"><?php esc_html_e( 'From the community', 'tclas' ); ?></span>
				<h2><?php esc_html_e( 'Latest news & stories', 'tclas' ); ?></h2>
			</div>
			<a href="<?php echo esc_url( home_url( '/news/' ) ); ?>" class="btn btn-outline-ardoise btn-sm">
				<?php esc_html_e( 'All posts', 'tclas' ); ?>
			</a>
		</div>

		<?php
		$posts = get_transient( 'tclas_homepage_posts' );
		if ( false === $posts ) {
			$posts = get_posts( [
				'numberposts' => 3,
				'post_status' => 'publish',
				'orderby'     => 'date',
				'order'       => 'DESC',
			] );
			set_transient( 'tclas_homepage_posts', $posts, 12 * HOUR_IN_SECONDS );
		}
		if ( $posts ) :
		?>
			<div class="tclas-grid-3">
				<?php foreach ( $posts as $post ) : setup_postdata( $post ); ?>
					<?php get_template_part( 'template-parts/content/card', 'post' ); ?>
				<?php endforeach; wp_reset_postdata(); ?>
			</div>
		<?php endif; ?>

	</div>
</section>

<!-- ── 5. MEMBERSHIP ────────────────────────────────────────────────────── -->
<section class="tclas-membership" id="membership">
	<div class="container-tclas">
		<span class="tclas-eyebrow tclas-eyebrow--light"><?php esc_html_e( 'Join the community', 'tclas' ); ?></span>
		<h2><?php echo tclas_ltz( 'Wëllkomm', 'Welcome', false ); ?></h2>
		<p style="max-width:52ch;"><?php esc_html_e( 'Membership is open to anyone with a connection to Luxembourg — however that connection looks.', 'tclas' ); ?></p>

		<!-- Membership illustration placeholder -->
		<div class="tclas-membership__illustration" data-reveal>
			<?php tclas_illustration( 'membership_illustration', __( 'TCLAS members at an outdoor summer gathering', 'tclas' ) ); ?>
		</div>

		<?php
		$price_individual = function_exists( 'get_field' ) ? (int) get_field( 'price_individual', 'option' ) : 0;
		$price_family     = function_exists( 'get_field' ) ? (int) get_field( 'price_family',     'option' ) : 0;
		$price_student    = function_exists( 'get_field' ) ? (int) get_field( 'price_student',    'option' ) : 0;
		$price_individual = $price_individual ?: 30;
		$price_family     = $price_family     ?: 45;
		$price_student    = $price_student    ?: 15;
		?>

		<!-- Tier cards -->
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
					<a href="<?php echo esc_url( add_query_arg( 'level', '1', home_url( '/join/' ) ) ); ?>" class="btn btn-outline-light">
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
					<a href="<?php echo esc_url( add_query_arg( 'level', '2', home_url( '/join/' ) ) ); ?>" class="btn btn-primary">
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
					<a href="<?php echo esc_url( add_query_arg( 'level', '3', home_url( '/join/' ) ) ); ?>" class="btn btn-outline-light">
						<?php esc_html_e( 'Join as student', 'tclas' ); ?>
					</a>
				</div>
			</div>

		</div><!-- .tclas-tiers -->
	</div>
</section>

<!-- ── 6. JOIN CTA ──────────────────────────────────────────────────────── -->
<?php if ( ! tclas_is_member() ) : ?>
<section class="tclas-join-cta">
	<div class="container-tclas">
		<h2><?php esc_html_e( 'Find your people.', 'tclas' ); ?></h2>
		<p><?php esc_html_e( 'Membership is open to anyone with a Luxembourg connection — and open to anyone curious enough to find one.', 'tclas' ); ?></p>
		<a href="<?php echo esc_url( home_url( '/join/' ) ); ?>" class="btn btn-outline-light btn-lg">
			<?php esc_html_e( 'Join TCLAS', 'tclas' ); ?>
		</a>
	</div>
</section>
<?php endif; ?>

<?php get_footer(); ?>
