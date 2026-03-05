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
		<div class="tclas-hero__content tclas-hero__content--center">

			<h1 class="tclas-hero__greeting" aria-label="Bonjour. Hello. Moien.">
				<span class="tclas-hero__greeting-stage" data-stage="0" lang="fr">Bonjour.</span>
				<span class="tclas-hero__greeting-stage" data-stage="1">Hello.</span>
				<span class="tclas-hero__greeting-stage" data-stage="2" lang="lb">Moien.</span>
			</h1>

			<div class="tclas-hero__greeting-ctas tclas-hero__actions">
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
<section class="tclas-mission" id="about" aria-labelledby="about-heading">
	<div class="container-tclas container--narrow">
		<span class="tclas-eyebrow"><?php esc_html_e( 'About TCLAS', 'tclas' ); ?></span>
		<h2 id="about-heading"><?php esc_html_e( 'Who we are', 'tclas' ); ?></h2>
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

<!-- ── 3b. NEWSLETTER PREVIEW ────────────────────────────────────────────── -->
<?php
// Find the most-recent issue date
$_nl_seed = get_posts( [
	'post_type'      => 'post',
	'post_status'    => 'publish',
	'posts_per_page' => 1,
	'meta_key'       => 'tclas_issue_date',
	'orderby'        => 'meta_value',
	'order'          => 'DESC',
	'fields'         => 'ids',
] );
$_nl_date = $_nl_seed ? (string) get_post_meta( $_nl_seed[0], 'tclas_issue_date', true ) : '';
?>
<?php if ( $_nl_date ) : ?>
<?php
$_nl_posts = get_posts( [
	'post_type'      => 'post',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'meta_query'     => [ [ 'key' => 'tclas_issue_date', 'value' => $_nl_date ] ],
	'meta_key'       => 'tclas_issue_order',
	'orderby'        => 'meta_value_num',
	'order'          => 'ASC',
] );

// Issue display title
$_nl_title = (string) get_post_meta( $_nl_seed[0], 'tclas_issue_title', true );
if ( ! $_nl_title ) {
	$_nl_dt    = DateTime::createFromFormat( 'Y-m', $_nl_date );
	$_nl_title = $_nl_dt ? $_nl_dt->format( 'F Y' ) : $_nl_date;
}

// Cover image: main-story post with a featured image
$_nl_cover_id = 0;
foreach ( $_nl_posts as $_nlp ) {
	$_nl_terms = wp_get_post_terms( $_nlp->ID, 'tclas_department', [ 'fields' => 'slugs' ] );
	if ( in_array( 'main-story', (array) $_nl_terms, true ) && has_post_thumbnail( $_nlp->ID ) ) {
		$_nl_cover_id = $_nlp->ID;
		break;
	}
}
?>
<section class="tclas-nl-preview" id="newsletter" aria-label="<?php esc_attr_e( 'Latest newsletter issue', 'tclas' ); ?>">
	<div class="container-tclas">

		<div class="tclas-nl-preview__header">
			<div>
				<span class="tclas-eyebrow"><?php esc_html_e( 'From the newsletter', 'tclas' ); ?></span>
				<h2 class="tclas-nl-preview__title"><?php echo esc_html( $_nl_title ); ?></h2>
			</div>
			<a href="<?php echo esc_url( home_url( '/newsletter/' ) ); ?>" class="tclas-nl-preview__archives-link">
				<?php esc_html_e( 'Browse all issues', 'tclas' ); ?>
				<svg aria-hidden="true" focusable="false" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
			</a>
		</div>

		<div class="tclas-nl-preview__layout<?php echo $_nl_cover_id ? '' : ' tclas-nl-preview__layout--no-cover'; ?>">

			<ol class="tclas-nl-preview__toc" role="list">
				<?php foreach ( $_nl_posts as $_nlp ) :
					$_nl_dept_terms = wp_get_post_terms( $_nlp->ID, 'tclas_department' );
					$_nl_dept_lux   = '';
					$_nl_dept_en    = '';
					if ( ! is_wp_error( $_nl_dept_terms ) ) {
						foreach ( $_nl_dept_terms as $_t ) {
							if ( 'main-story' !== $_t->slug ) {
								$_nl_dept_lux = $_t->name;
								$_nl_dept_en  = $_t->description;
								break;
							}
						}
					}
				?>
				<li class="tclas-nl-preview__article">
					<a href="<?php echo esc_url( get_permalink( $_nlp->ID ) ); ?>"
					   class="tclas-nl-preview__article-link"
					   aria-label="<?php echo esc_attr( sprintf( __( 'Read: %s', 'tclas' ), get_the_title( $_nlp ) ) ); ?>"
					>
						<?php if ( $_nl_dept_lux ) : ?>
						<span class="tclas-issue-dept">
							<span lang="lb"><?php echo esc_html( $_nl_dept_lux ); ?></span>
							<?php if ( $_nl_dept_en ) : ?><span class="tclas-issue-dept__en"><?php echo esc_html( $_nl_dept_en ); ?></span><?php endif; ?>
						</span>
						<?php endif; ?>
						<span class="tclas-nl-preview__article-title"><?php echo esc_html( get_the_title( $_nlp ) ); ?></span>
					</a>
				</li>
				<?php endforeach; ?>
			</ol>

			<?php if ( $_nl_cover_id ) : ?>
			<div class="tclas-nl-preview__cover">
				<a href="<?php echo esc_url( get_permalink( $_nl_cover_id ) ); ?>" tabindex="-1" aria-hidden="true">
					<?php echo get_the_post_thumbnail( $_nl_cover_id, 'large', [
						'class' => 'tclas-nl-preview__cover-img',
						'alt'   => '',
					] ); ?>
				</a>
			</div>
			<?php endif; ?>

		</div><!-- /.tclas-nl-preview__layout -->
	</div><!-- /.container-tclas -->
</section>
<?php endif; ?>

<!-- ── 4. CITIZENSHIP CTA ────────────────────────────────────────────────── -->
<section class="tclas-quiz-cta" aria-labelledby="quiz-cta-heading">
	<div class="container-tclas container--narrow">
		<span class="tclas-eyebrow"><?php esc_html_e( 'Luxembourg citizenship', 'tclas' ); ?></span>
		<h2 id="quiz-cta-heading"><?php esc_html_e( 'Think you might qualify?', 'tclas' ); ?></h2>
		<p>Luxembourg recognizes citizenship through ancestry going back multiple generations. Our eligibility quiz walks you through the criteria for Articles 7, 23, and 7+23&mdash;in plain English.</p>
		<a href="<?php echo esc_url( home_url( '/citizenship/' ) ); ?>" class="btn btn-primary btn-lg">
			<?php esc_html_e( 'Check your eligibility', 'tclas' ); ?>
		</a>
	</div>
</section>

<!-- ── 5. JOIN BAR ───────────────────────────────────────────────────────── -->
<?php if ( ! tclas_is_member() ) : ?>
<section class="tclas-join-bar" aria-labelledby="join-bar-heading">
	<div class="container-tclas">
		<h2 id="join-bar-heading"><?php esc_html_e( 'Join the community', 'tclas' ); ?></h2>
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
