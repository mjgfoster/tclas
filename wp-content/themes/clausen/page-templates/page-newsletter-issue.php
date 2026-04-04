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

// Use custom issue title if any article in this issue defines one
foreach ( $issue_posts as $_tp ) {
	$_ct = get_post_meta( $_tp->ID, 'tclas_issue_title', true );
	if ( $_ct ) { $issue_label = $_ct; break; }
}

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

// ── Separate welcome letter from TOC articles ─────────────────────────────────
$welcome_post = null;
$toc_posts    = [];

foreach ( $issue_posts as $_p ) {
	$_dept_slugs = wp_get_post_terms( $_p->ID, 'tclas_department', [ 'fields' => 'slugs' ] );
	if ( null === $welcome_post && in_array( 'wellkomm', (array) $_dept_slugs, true ) ) {
		$welcome_post = $_p;
	} else {
		$toc_posts[] = $_p;
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
 * Returns the byline for a post: custom field, then WP author, or empty if hidden.
 */
$_byline = function( WP_Post $p ): string {
	if ( get_post_meta( $p->ID, 'tclas_hide_byline', true ) ) { return ''; }
	$custom = get_post_meta( $p->ID, 'tclas_byline', true );
	return $custom ? $custom : get_the_author_meta( 'display_name', $p->post_author );
};

get_header();
?>

<div class="tclas-page-header">
	<div class="container-tclas">
		<?php tclas_breadcrumb( $issue_label ); ?>
		<h1 class="tclas-page-header__title"><?php echo esc_html( $issue_label ); ?></h1>
		<p class="tclas-issue-header__sub"><?php esc_html_e( 'The Loon & The Lion', 'tclas' ); ?></p>
	</div>
</div>

<?php
// ════════════════════════════════════════════════════════════════════════════
// WELCOME LETTER
// ════════════════════════════════════════════════════════════════════════════
if ( $welcome_post ) :
	$_w_byline = $_byline( $welcome_post );
?>
<section class="tclas-section bg-white">
	<div class="container-tclas container--medium">
		<article class="tclas-issue-welcome">
			<h2 class="tclas-issue-welcome__title"><?php echo esc_html( get_the_title( $welcome_post ) ); ?></h2>
			<div class="tclas-issue-welcome__body entry-content">
				<?php echo apply_filters( 'the_content', $welcome_post->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<?php if ( $_w_byline ) : ?>
			<p class="tclas-issue-welcome__byline">— <?php echo esc_html( $_w_byline ); ?></p>
			<?php endif; ?>
		</article>
	</div>
</section>
<?php endif; ?>

<?php
// ════════════════════════════════════════════════════════════════════════════
// TABLE OF CONTENTS — Rich article cards
// ════════════════════════════════════════════════════════════════════════════
if ( ! empty( $toc_posts ) ) :
?>
<section class="tclas-section" aria-label="<?php esc_attr_e( 'Articles in this issue', 'tclas' ); ?>">
	<div class="container-tclas">
		<h2 class="tclas-issue-toc__heading"><?php esc_html_e( 'In this issue', 'tclas' ); ?></h2>

		<div class="tclas-issue-toc">
			<?php foreach ( $toc_posts as $_p ) :
				$_d       = $_dept( $_p );
				$_ex      = $_excerpt( $_p, 30 );
				$_by      = $_byline( $_p );
				$_minutes = $_read_time( $_p );
				$_has_img = has_post_thumbnail( $_p->ID );
			?>
			<article class="tclas-issue-toc__card<?php echo $_has_img ? '' : ' tclas-issue-toc__card--no-img'; ?>">
				<a href="<?php echo esc_url( get_permalink( $_p->ID ) ); ?>" class="tclas-issue-toc__link">

					<?php if ( $_has_img ) : ?>
					<div class="tclas-issue-toc__image">
						<?php echo get_the_post_thumbnail( $_p->ID, 'medium', [ 'alt' => '' ] ); ?>
					</div>
					<?php endif; ?>

					<div class="tclas-issue-toc__body">
						<?php if ( $_d['lux'] ) : ?>
						<span class="tclas-ie-dept-label">
							<span lang="lb"><?php echo esc_html( $_d['lux'] ); ?></span><?php if ( $_d['en'] ) : ?><span class="tclas-ie-dept-label__en"><?php echo esc_html( $_d['en'] ); ?></span><?php endif; ?>
						</span>
						<?php endif; ?>

						<h3 class="tclas-issue-toc__title"><?php echo esc_html( get_the_title( $_p ) ); ?></h3>

						<?php if ( $_ex ) : ?>
						<p class="tclas-issue-toc__excerpt"><?php echo esc_html( $_ex ); ?></p>
						<?php endif; ?>

						<div class="tclas-issue-toc__meta">
							<?php if ( $_by ) : ?>
							<span class="tclas-issue-toc__byline"><?php echo esc_html( $_by ); ?></span>
							<?php endif; ?>
							<span class="tclas-issue-toc__read-time"><?php printf(
								esc_html__( '%d min read', 'tclas' ),
								(int) $_minutes
							); ?></span>
							<?php tclas_members_only_badge( $_p->ID ); ?>
						</div>
					</div>

				</a>
			</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php get_footer(); ?>
