<?php
/**
 * Single Newsletter Issue
 *
 * Magazine-style table of contents for one issue of the Loon & Lion.
 * Loaded via template_redirect when /newsletter/issue/{YYYY-MM}/ is requested.
 *
 * Layout: two-column grid — left: masthead + ordered article list;
 *         right: sticky portrait cover image.
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

// Serve 404 if the issue date exists but has no published posts
if ( empty( $issue_posts ) ) {
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	get_header();
	get_template_part( '404' );
	get_footer();
	exit;
}

// ── Find the cover post: Main Story term + featured image ─────────────────────
$cover_post_id = 0;
foreach ( $issue_posts as $_p ) {
	$_terms = wp_get_post_terms( $_p->ID, 'tclas_department', [ 'fields' => 'slugs' ] );
	if ( in_array( 'main-story', (array) $_terms, true ) && has_post_thumbnail( $_p->ID ) ) {
		$cover_post_id = $_p->ID;
		break;
	}
}
$has_cover = (bool) $cover_post_id;

// ── Find the "Previous Issues" archive page URL ───────────────────────────────
$_archive_ids = get_posts( [
	'post_type'      => 'page',
	'meta_key'       => '_wp_page_template',
	'meta_value'     => 'page-templates/page-newsletter-archive.php',
	'posts_per_page' => 1,
	'fields'         => 'ids',
	'no_found_rows'  => true,
] );
$archive_url = $_archive_ids ? get_permalink( $_archive_ids[0] ) : home_url( '/newsletter/' );

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

		<!-- ── Two-column layout ───────────────────────────────────────── -->
		<div class="tclas-issue-layout<?php echo $has_cover ? '' : ' tclas-issue-layout--no-cover'; ?>">

			<!-- LEFT: Magazine masthead + article TOC -->
			<div class="tclas-issue-toc-col">

				<h2
					class="tclas-issue-masthead"
					aria-label="<?php esc_attr_e( 'The Loon & The Lion', 'tclas' ); ?>"
				>
					<span class="tclas-issue-masthead__loon"><?php esc_html_e( 'The Loon', 'tclas' ); ?></span>
					<span class="tclas-issue-masthead__amp"> &amp; </span>
					<span class="tclas-issue-masthead__lion"><?php esc_html_e( 'The Lion', 'tclas' ); ?></span>
				</h2>

				<p class="tclas-issue-date"><?php echo esc_html( $issue_label ); ?></p>

				<!-- Article list -->
				<ol class="tclas-issue-toc">
					<?php foreach ( $issue_posts as $_p ) :

						// Department label: skip 'main-story' (structural), use topical term
						$_dept_terms = wp_get_post_terms( $_p->ID, 'tclas_department' );
						$_dept_lux   = '';
						$_dept_en    = '';
						if ( ! is_wp_error( $_dept_terms ) ) {
							foreach ( $_dept_terms as $_t ) {
								if ( $_t->slug !== 'main-story' ) {
									$_dept_lux = $_t->name;
									$_dept_en  = $_t->description;
									break;
								}
							}
						}

						$_is_lead   = ( $_p->ID === $cover_post_id );
						$_excerpt   = has_excerpt( $_p->ID )
							? wp_trim_words( get_the_excerpt( $_p ), 25, '&hellip;' )
							: wp_trim_words( $_p->post_content, 25, '&hellip;' );
						$_words     = str_word_count( wp_strip_all_tags( $_p->post_content ) );
						$_read_mins = max( 1, round( $_words / 200 ) );
					?>
					<li class="tclas-issue-article<?php echo $_is_lead ? ' tclas-issue-article--lead' : ''; ?>">
						<a
							href="<?php echo esc_url( get_permalink( $_p->ID ) ); ?>"
							class="tclas-issue-article-link"
							aria-label="<?php echo esc_attr( sprintf(
								/* translators: %s: article title */
								__( 'Read article: %s', 'tclas' ),
								get_the_title( $_p )
							) ); ?>"
						>
							<?php if ( $_dept_lux ) : ?>
							<span class="tclas-issue-dept">
								<span lang="lb"><?php echo esc_html( $_dept_lux ); ?></span><?php if ( $_dept_en ) : ?><span class="tclas-issue-dept__en"><?php echo esc_html( $_dept_en ); ?></span><?php endif; ?>
							</span>
							<?php endif; ?>

							<h3 class="tclas-issue-title">
								<?php echo esc_html( get_the_title( $_p ) ); ?>
							</h3>

							<?php if ( $_excerpt ) : ?>
							<p class="tclas-issue-excerpt"><?php echo esc_html( $_excerpt ); ?></p>
							<?php endif; ?>

							<span class="tclas-issue-meta">
								<?php printf(
									/* translators: %d: estimated read time in minutes */
									esc_html__( '%d min read', 'tclas' ),
									(int) $_read_mins
								); ?>
							</span>
						</a>
					</li>
					<?php endforeach; ?>
				</ol>

				<!-- Back bar -->
				<div class="tclas-issue-back-bar">
					<a href="<?php echo esc_url( $archive_url ); ?>" class="tclas-issue-back-link">
						&larr; <?php esc_html_e( 'All Issues', 'tclas' ); ?>
					</a>
					<span class="tclas-issue-count">
						<?php printf(
							/* translators: %d: number of articles */
							esc_html( _n( '%d article', '%d articles', count( $issue_posts ), 'tclas' ) ),
							count( $issue_posts )
						); ?>
					</span>
				</div>

			</div><!-- .tclas-issue-toc-col -->

			<?php if ( $has_cover ) : ?>
			<!-- RIGHT: Sticky cover image (Main Story featured image) -->
			<div class="tclas-issue-cover-col">
				<div class="tclas-issue-cover-wrap">
					<a
						href="<?php echo esc_url( get_permalink( $cover_post_id ) ); ?>"
						class="tclas-issue-cover-link"
						aria-hidden="true"
						tabindex="-1"
					>
						<div class="tclas-issue-cover-frame">
							<?php echo get_the_post_thumbnail(
								$cover_post_id,
								'large',
								[
									'class' => 'tclas-issue-cover-img',
									'alt'   => sprintf(
										/* translators: %s: issue title, e.g. "March 2025" */
										__( 'Cover of %s', 'tclas' ),
										$issue_label
									),
								]
							); ?>
						</div>
					</a>
				</div>
			</div><!-- .tclas-issue-cover-col -->
			<?php endif; ?>

		</div><!-- .tclas-issue-layout -->

	</div><!-- .container-tclas -->
</section>

<?php get_footer(); ?>
