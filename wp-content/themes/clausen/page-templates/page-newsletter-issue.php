<?php
/**
 * Single Newsletter Issue — Editorial Layout
 *
 * Presents one issue of The Loon & Lion as an editorial email-style page.
 * Three-tier layout within a left sidebar:
 *  1. Hero    — the main-story article, full-width, large image, 45-word teaser
 *  2. Feature — the next two articles, two-column, image, 30-word teaser
 *  3. Grid    — remaining articles, two-column text-only compact cards
 *
 * Loaded via template_redirect when /newsletter/issue/{YYYY-MM}/ is requested.
 *
 * @package TCLAS
 */

$issue_date = get_query_var( 'tclas_newsletter_issue' );

// Redirect malformed slugs back to the newsletter homepage
if ( ! $issue_date || ! preg_match( '/^\d{4}-\d{2}$/', $issue_date ) ) {
	wp_safe_redirect( home_url( '/newsletter/' ), 301 );
	exit;
}

$dt          = DateTime::createFromFormat( 'Y-m', $issue_date );
$issue_label = $dt ? $dt->format( 'F Y' ) : $issue_date;

// ── Query all posts for this issue, ordered by tclas_issue_order ─────────────
$issue_posts = get_posts( [
	'post_type'      => 'post',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'meta_query'     => [ [ 'key' => 'tclas_issue_date', 'value' => $issue_date ] ],
	'meta_key'       => 'tclas_issue_order',
	'orderby'        => 'meta_value_num',
	'order'          => 'ASC',
] );

// Serve 404 if the issue has no published posts
if ( empty( $issue_posts ) ) {
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	get_header();
	get_template_part( '404' );
	get_footer();
	exit;
}

// ── Find "Previous Issues" archive page URL ───────────────────────────────────
$_archive_ids = get_posts( [
	'post_type'      => 'page',
	'meta_key'       => '_wp_page_template',
	'meta_value'     => 'page-templates/page-newsletter-archive.php',
	'posts_per_page' => 1,
	'fields'         => 'ids',
	'no_found_rows'  => true,
] );
$archive_url = $_archive_ids ? get_permalink( $_archive_ids[0] ) : home_url( '/newsletter/' );

// ── Bucket posts into three editorial tiers ───────────────────────────────────
// Tier 1 — Hero: the post tagged main-story (exactly one expected per issue)
// Tier 2 — Feature: up to 2 remaining posts (two-column, image teasers)
// Tier 3 — Grid: all remaining posts (two-column, text-only compact cards)

$lead_post     = null;
$feature_posts = [];
$grid_posts    = [];
$remaining     = []; // posts that aren't the lead

foreach ( $issue_posts as $_p ) {
	$_dept_slugs = wp_get_post_terms( $_p->ID, 'tclas_department', [ 'fields' => 'slugs' ] );
	if ( null === $lead_post && in_array( 'main-story', (array) $_dept_slugs, true ) ) {
		$lead_post = $_p;
	} else {
		$remaining[] = $_p;
	}
}

// If no post is tagged main-story, promote the first post
if ( null === $lead_post && ! empty( $remaining ) ) {
	$lead_post = array_shift( $remaining );
}

// Split remaining into feature (up to 2) and grid (rest)
foreach ( $remaining as $_p ) {
	if ( count( $feature_posts ) < 2 ) {
		$feature_posts[] = $_p;
	} else {
		$grid_posts[] = $_p;
	}
}

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Returns a trimmed excerpt: manual excerpt first, then content fallback.
 */
$_excerpt = function( WP_Post $p, int $words ): string {
	return has_excerpt( $p->ID )
		? wp_trim_words( get_the_excerpt( $p ), $words, '&hellip;' )
		: wp_trim_words( $p->post_content, $words, '&hellip;' );
};

/**
 * Returns estimated read time in minutes.
 */
$_read_time = function( WP_Post $p ): int {
	return max( 1, round( str_word_count( wp_strip_all_tags( $p->post_content ) ) / 200 ) );
};

/**
 * Returns department label terms (skipping structural 'main-story' term).
 * Returns array ['lux' => string, 'en' => string, 'slug' => string].
 */
$_dept = function( WP_Post $p ): array {
	$terms = wp_get_post_terms( $p->ID, 'tclas_department' );
	if ( is_wp_error( $terms ) ) { return [ 'lux' => '', 'en' => '', 'slug' => '' ]; }
	foreach ( $terms as $t ) {
		if ( $t->slug !== 'main-story' ) {
			return [ 'lux' => $t->name, 'en' => $t->description, 'slug' => $t->slug ];
		}
	}
	return [ 'lux' => '', 'en' => '', 'slug' => '' ];
};

/**
 * Returns CSS placeholder class for articles without a featured image.
 */
$_bg_class = function( string $slug ): string {
	$known = [
		'main-story', 'wellkomm', 'communauteit', 'an-der-kichen',
		'geschicht', 'zu-letzebuerg', 'traditiounen', 'eist-sprooch',
		'evenementer', 'spezialbericht',
	];
	return 'tclas-ie-dept-bg--' . ( in_array( $slug, $known, true ) ? $slug : 'default' );
};

get_header();
?>

<section class="tclas-section">
	<div class="container-tclas">

		<!-- ── Breadcrumbs ─────────────────────────────────────────────── -->
		<nav class="tclas-breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'tclas' ); ?>">
			<ol class="tclas-breadcrumbs__list">
				<li class="tclas-breadcrumbs__item">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'tclas' ); ?></a>
				</li>
				<li class="tclas-breadcrumbs__item">
					<a href="<?php echo esc_url( home_url( '/newsletter/' ) ); ?>"><?php esc_html_e( 'Newsletter', 'tclas' ); ?></a>
				</li>
				<li class="tclas-breadcrumbs__item tclas-breadcrumbs__item--current" aria-current="page">
					<?php echo esc_html( $issue_label ); ?>
				</li>
			</ol>
		</nav>

		<!-- ── Two-column layout: sidebar (left) + editorial (right) ───── -->
		<div class="tclas-nl-with-sidebar">

			<!-- Sidebar -->
			<?php get_template_part( 'template-parts/newsletter-sidebar' ); ?>

			<!-- Editorial content column -->
			<div class="tclas-issue-editorial">

				<!-- Issue header -->
				<header class="tclas-issue-editorial__header">
					<p class="tclas-nl-current-eyebrow"><?php esc_html_e( 'TCLAS Newsletter', 'tclas' ); ?></p>
					<h2 class="tclas-issue-editorial__date"><?php echo esc_html( $issue_label ); ?></h2>
				</header>


				<?php
				// ════════════════════════════════════════════════════════
				// TIER 1 — HERO
				// ════════════════════════════════════════════════════════
				if ( $lead_post ) :
					$_d = $_dept( $lead_post );
				?>
				<article class="tclas-ie-hero" aria-labelledby="tclas-ie-hero-title">
					<a href="<?php echo esc_url( get_permalink( $lead_post->ID ) ); ?>" class="tclas-ie-hero__link">

						<!-- Image -->
						<div class="tclas-ie-hero__image-wrap">
							<?php if ( has_post_thumbnail( $lead_post->ID ) ) : ?>
								<?php echo get_the_post_thumbnail(
									$lead_post->ID,
									'large',
									[ 'class' => 'tclas-ie-hero__img', 'alt' => '' ]
								); ?>
							<?php else : ?>
								<div class="tclas-ie-hero__placeholder <?php echo esc_attr( $_bg_class( $_d['slug'] ) ); ?>"></div>
							<?php endif; ?>
						</div>

						<!-- Body -->
						<div class="tclas-ie-hero__body">
							<?php if ( $_d['lux'] ) : ?>
							<span class="tclas-ie-dept-label">
								<span lang="lb"><?php echo esc_html( $_d['lux'] ); ?></span><?php if ( $_d['en'] ) : ?><span class="tclas-ie-dept-label__en"><?php echo esc_html( $_d['en'] ); ?></span><?php endif; ?>
							</span>
							<?php endif; ?>

							<h3 class="tclas-ie-hero__title" id="tclas-ie-hero-title">
								<?php echo esc_html( get_the_title( $lead_post ) ); ?>
							</h3>

							<?php $_ex = $_excerpt( $lead_post, 45 ); if ( $_ex ) : ?>
							<p class="tclas-ie-hero__excerpt"><?php echo esc_html( $_ex ); ?></p>
							<?php endif; ?>
						</div>

					</a>
				</article>
				<?php endif; ?>


				<?php
				// ════════════════════════════════════════════════════════
				// TIER 2 — FEATURE ROW
				// ════════════════════════════════════════════════════════
				if ( ! empty( $feature_posts ) ) :
					$_count = count( $feature_posts );
				?>
				<div class="tclas-ie-feature-row tclas-ie-feature-row--count-<?php echo (int) $_count; ?>">
					<?php foreach ( $feature_posts as $_p ) :
						$_d = $_dept( $_p );
					?>
					<article class="tclas-ie-feature-card">
						<a href="<?php echo esc_url( get_permalink( $_p->ID ) ); ?>" class="tclas-ie-feature-card__link">

							<!-- Image -->
							<div class="tclas-ie-feature-card__image-wrap">
								<?php if ( has_post_thumbnail( $_p->ID ) ) : ?>
									<?php echo get_the_post_thumbnail(
										$_p->ID,
										'medium_large',
										[ 'class' => 'tclas-ie-feature-card__img', 'alt' => '' ]
									); ?>
								<?php else : ?>
									<div class="tclas-ie-feature-card__placeholder <?php echo esc_attr( $_bg_class( $_d['slug'] ) ); ?>"></div>
								<?php endif; ?>
							</div>

							<!-- Body -->
							<div class="tclas-ie-feature-card__body">
								<?php if ( $_d['lux'] ) : ?>
								<span class="tclas-ie-dept-label">
									<span lang="lb"><?php echo esc_html( $_d['lux'] ); ?></span><?php if ( $_d['en'] ) : ?><span class="tclas-ie-dept-label__en"><?php echo esc_html( $_d['en'] ); ?></span><?php endif; ?>
								</span>
								<?php endif; ?>

								<h3 class="tclas-ie-feature-card__title">
									<?php echo esc_html( get_the_title( $_p ) ); ?>
								</h3>

								<?php $_ex = $_excerpt( $_p, 30 ); if ( $_ex ) : ?>
								<p class="tclas-ie-feature-card__excerpt"><?php echo esc_html( $_ex ); ?></p>
								<?php endif; ?>
							</div>

						</a>
					</article>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>


				<?php
				// ════════════════════════════════════════════════════════
				// TIER 3 — DEPARTMENT GRID (text-only compact cards)
				// ════════════════════════════════════════════════════════
				if ( ! empty( $grid_posts ) ) :
				?>
				<div class="tclas-ie-dept-grid">
					<?php foreach ( $grid_posts as $_p ) :
						$_d = $_dept( $_p );
					?>
					<article class="tclas-ie-dept-card">
						<a href="<?php echo esc_url( get_permalink( $_p->ID ) ); ?>" class="tclas-ie-dept-card__link">

							<?php if ( $_d['lux'] ) : ?>
							<span class="tclas-ie-dept-label">
								<span lang="lb"><?php echo esc_html( $_d['lux'] ); ?></span><?php if ( $_d['en'] ) : ?><span class="tclas-ie-dept-label__en"><?php echo esc_html( $_d['en'] ); ?></span><?php endif; ?>
							</span>
							<?php endif; ?>

							<h3 class="tclas-ie-dept-card__title">
								<?php echo esc_html( get_the_title( $_p ) ); ?>
							</h3>

							<?php $_ex = $_excerpt( $_p, 20 ); if ( $_ex ) : ?>
							<p class="tclas-ie-dept-card__excerpt"><?php echo esc_html( $_ex ); ?></p>
							<?php endif; ?>

						</a>
					</article>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>


				</div><!-- .tclas-issue-editorial -->

		</div><!-- .tclas-nl-with-sidebar -->

	</div><!-- .container-tclas -->
</section>

<?php get_footer(); ?>
