<?php
/**
 * Template Name: Home (launch)
 *
 * Pared-down homepage for the May 9 MVP launch.
 * Sections: entry overlay, hero, events, newsletter preview (auto-hides when no issues).
 * Phase-2 sections (citizenship CTA, join bar) are intentionally omitted.
 *
 * @package TCLAS
 */

get_header();
?>

<!-- ── 0. ENTRY OVERLAY (once per session) ──────────────────────────────── -->
<div class="tclas-entry" id="tclas-entry" aria-label="<?php esc_attr_e( 'Welcome', 'tclas' ); ?>" role="dialog" aria-modal="true">
	<div class="tclas-entry__greetings" aria-label="Moien. Hello. Bonjour.">
		<span class="tclas-entry__word" data-step="0" lang="lb">Moien.</span>
		<span class="tclas-entry__word" data-step="1">Hello.</span>
		<span class="tclas-entry__word" data-step="2" lang="fr">Bonjour.</span>
	</div>
	<div class="tclas-entry__identity" aria-hidden="true">
		<img class="tclas-entry__logo" src="<?php echo esc_url( get_theme_file_uri( 'assets/images/tclas-welcome.svg' ) ); ?>" alt="Twin Cities Luxembourg American Society" width="2051" height="2198">
		<div class="tclas-entry__welcomes">
			<span class="tclas-entry__welcome-line" data-step="0" lang="lb">begréisst Iech</span>
			<span class="tclas-entry__welcome-line" data-step="1">welcomes you</span>
			<span class="tclas-entry__welcome-line" data-step="2" lang="fr">vous souhaite la bienvenue</span>
		</div>
	</div>
	<button class="tclas-entry__skip" type="button" aria-label="<?php esc_attr_e( 'Skip intro', 'tclas' ); ?>">
		<?php esc_html_e( 'Skip', 'tclas' ); ?>
	</button>
</div>

<!-- ── 1. HERO (four-quadrant layout) ──────────────────────────────────── -->
<?php
$hero_tagline     = function_exists( 'get_field' ) ? get_field( 'hp_hero_tagline' ) : '';
$hero_welcome     = function_exists( 'get_field' ) ? get_field( 'hp_hero_welcome' ) : '';
$hero_cta1_label  = function_exists( 'get_field' ) ? get_field( 'hp_hero_cta1_label' ) : '';
$hero_cta1_url    = function_exists( 'get_field' ) ? get_field( 'hp_hero_cta1_url' ) : '';
$hero_cta2_label  = function_exists( 'get_field' ) ? get_field( 'hp_hero_cta2_label' ) : '';
$hero_cta2_url    = function_exists( 'get_field' ) ? get_field( 'hp_hero_cta2_url' ) : '';

// Launch defaults retarget the primary CTA away from /join/ (Phase 2).
if ( ! $hero_tagline )    $hero_tagline    = 'A heritage that connects to modern Europe';
if ( ! $hero_welcome )    $hero_welcome    = 'TCLAS — the Twin Cities Luxembourg American Society — is a group based in the Minneapolis–Saint Paul, Minnesota, metro that brings together Americans of Luxembourgish descent, dual citizens of Luxembourg and the United States, and expatriate Luxembourgers living in the Upper Midwest.';
if ( ! $hero_cta1_label ) $hero_cta1_label = 'See upcoming events';
if ( ! $hero_cta1_url )   $hero_cta1_url   = '/events/';
if ( ! $hero_cta2_label ) $hero_cta2_label = 'About TCLAS';
if ( ! $hero_cta2_url )   $hero_cta2_url   = '/about/';
?>
<section class="tclas-hero<?php echo tclas_is_national_day_season() ? ' tclas-hero--national-day' : ''; ?>" aria-label="<?php esc_attr_e( 'Welcome', 'tclas' ); ?>">
	<div class="tclas-hero__grid">

		<!-- Row 1: MN photo (left ~62%) + Copy block 1 (right ~38%) -->
		<div class="tclas-hero__cell tclas-hero__cell--mn-photo">
			<?php tclas_render_hero_photo_stack( 'minnesota' ); ?>
		</div>
		<div class="tclas-hero__cell tclas-hero__cell--copy1">
			<div class="tclas-hero__copy">
				<h1 class="tclas-hero__tagline"><?php echo esc_html( $hero_tagline ); ?></h1>
			</div>
		</div>

		<!-- Row 2: Copy block 2 (left ~38%) + LUX photo (right ~62%) -->
		<div class="tclas-hero__cell tclas-hero__cell--copy2">
			<div class="tclas-hero__copy">
				<?php
				$hp_mission = function_exists( 'get_field' ) ? get_field( 'hp_mission_body' ) : '';
				if ( $hp_mission ) {
					echo '<div class="tclas-hero__welcome">' . wp_kses_post( $hp_mission ) . '</div>';
				} else {
				?>
				<p class="tclas-hero__welcome"><?php echo esc_html( $hero_welcome ); ?></p>
				<?php } ?>
				<div class="tclas-hero__ctas">
					<a href="<?php echo esc_url( home_url( $hero_cta1_url ) ); ?>" class="btn btn-primary btn-lg">
						<?php echo esc_html( $hero_cta1_label ); ?>
					</a>
					<a href="<?php echo esc_url( home_url( $hero_cta2_url ) ); ?>" class="btn btn-outline-ardoise btn-lg">
						<?php echo esc_html( $hero_cta2_label ); ?>
					</a>
				</div>
			</div>
		</div>
		<div class="tclas-hero__cell tclas-hero__cell--lux-photo">
			<?php tclas_render_hero_photo_stack( 'luxembourg' ); ?>
		</div>

	</div>
</section>

<!-- ── 2. EVENTS ────────────────────────────────────────────────────────── -->
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

<!-- ── 3. NEWSLETTER PREVIEW (auto-hides when no issues are published) ──── -->
<?php
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

$_nl_title = (string) get_post_meta( $_nl_seed[0], 'tclas_issue_title', true );
if ( ! $_nl_title ) {
	$_nl_dt    = DateTime::createFromFormat( 'Y-m', $_nl_date );
	$_nl_title = $_nl_dt ? $_nl_dt->format( 'F Y' ) : $_nl_date;
}

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
						<?php tclas_members_only_badge( $_nlp->ID ); ?>
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

<?php get_footer(); ?>
