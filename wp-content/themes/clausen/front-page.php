<?php
/**
 * Homepage template
 *
 * Sections (alternating light/dark):
 *  1. Hero            — ardoise (dark)
 *  2. Mission         — or-pale (warm light)
 *  3. Events          — white (light)
 *  4. Citizenship CTA — ardoise (dark)
 *  5. Join bar        — gold
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

			<h1 class="tclas-hero__greeting" aria-label="<?php esc_attr_e( 'Welcome', 'tclas' ); ?>">
				<span class="tclas-hero__greeting-stage" data-stage="0" data-active="true" lang="lb">W&euml;llkomm</span>
				<span class="tclas-hero__greeting-stage" data-stage="1" lang="fr">Bienvenue</span>
				<span class="tclas-hero__greeting-stage" data-stage="2"><?php esc_html_e( 'Welcome', 'tclas' ); ?></span>
			</h1>

			<div class="tclas-hero__greeting-ctas tclas-hero__actions" hidden>
				<?php if ( ! tclas_is_member() ) : ?>
					<a href="<?php echo esc_url( home_url( '/join/' ) ); ?>" class="btn btn-primary btn-lg">
						<?php esc_html_e( 'Join us', 'tclas' ); ?>
					</a>
					<a href="<?php echo esc_url( home_url( '/about/' ) ); ?>" class="btn btn-outline-light">
						<?php esc_html_e( 'Learn more about TCLAS', 'tclas' ); ?>
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

<!-- ── 2. MISSION ───────────────────────────────────────────────────────── -->
<section class="tclas-mission" id="about">
	<div class="container-tclas container--narrow">
		<span class="tclas-eyebrow"><?php esc_html_e( 'About TCLAS', 'tclas' ); ?></span>
		<h2><?php esc_html_e( 'Who we are', 'tclas' ); ?></h2>
		<p>TCLAS&mdash;the Twin Cities Luxembourg American Society&mdash;is a group based in the Minneapolis&ndash;Saint Paul, Minnesota, metro that brings together Americans of Luxembourgish descent, dual citizens of Luxembourg and the United States, and expatriate Luxembourgers living in the Upper Midwest.</p>
		<a href="<?php echo esc_url( home_url( '/about/' ) ); ?>" class="btn btn-outline-ardoise">
			<?php esc_html_e( 'Learn more', 'tclas' ); ?>
		</a>
	</div>
</section>

<!-- ── 3. EVENTS ────────────────────────────────────────────────────────── -->
<section class="tclas-events" id="events">
	<div class="container-tclas">
		<div class="tclas-events__header">
			<div>
				<span class="tclas-eyebrow"><?php esc_html_e( 'Happening soon', 'tclas' ); ?></span>
				<h2><?php esc_html_e( 'Upcoming events', 'tclas' ); ?></h2>
			</div>
			<a href="<?php echo esc_url( home_url( '/events/' ) ); ?>" class="tclas-events__view-all tclas-events__view-all--desktop">
				<?php esc_html_e( 'View all events', 'tclas' ); ?>
				<svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
			</a>
		</div>

		<?php
		$events = tclas_get_upcoming_events( 3 );
		if ( $events ) :
		?>
			<div class="tclas-events__grid">
				<?php foreach ( $events as $event ) : ?>
					<?php tclas_render_event_card( $event ); ?>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<?php tclas_render_events_empty(); ?>
		<?php endif; ?>

		<div class="tclas-events__view-all-mobile">
			<a href="<?php echo esc_url( home_url( '/events/' ) ); ?>" class="tclas-events__view-all-btn">
				<?php esc_html_e( 'View all events', 'tclas' ); ?>
			</a>
		</div>
	</div>
</section>

<!-- ── 4. CITIZENSHIP CTA ────────────────────────────────────────────────── -->
<section class="tclas-quiz-cta">
	<div class="container-tclas container--narrow">
		<span class="tclas-eyebrow tclas-eyebrow--light"><?php esc_html_e( 'Luxembourg citizenship', 'tclas' ); ?></span>
		<h2><?php esc_html_e( 'Think you might qualify?', 'tclas' ); ?></h2>
		<p>Luxembourg recognizes citizenship through ancestry going back multiple generations. Our eligibility quiz walks you through the criteria for Articles 7, 23, and 7+23&mdash;in plain English.</p>
		<a href="<?php echo esc_url( home_url( '/citizenship/' ) ); ?>" class="btn btn-primary btn-lg">
			<?php esc_html_e( 'Check your eligibility', 'tclas' ); ?>
		</a>
	</div>
</section>

<!-- ── 5. JOIN BAR ───────────────────────────────────────────────────────── -->
<?php if ( ! tclas_is_member() ) : ?>
<section class="tclas-join-bar">
	<div class="container-tclas">
		<h2><?php esc_html_e( 'Join the community', 'tclas' ); ?></h2>
		<div class="tclas-join-bar__actions">
			<a href="<?php echo esc_url( home_url( '/join/' ) ); ?>" class="btn btn-secondary btn-lg">
				<?php esc_html_e( 'Become a member', 'tclas' ); ?>
			</a>
			<a href="<?php echo esc_url( wp_login_url() ); ?>" class="btn btn-outline-ardoise">
				<?php esc_html_e( 'Member log in', 'tclas' ); ?>
			</a>
		</div>
	</div>
</section>
<?php endif; ?>

<?php get_footer(); ?>
