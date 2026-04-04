<?php
/**
 * Template Name: Newsletter — Current Issue
 *
 * Loon & Lion newsletter homepage. Four sections:
 *  1. Current issue masthead + article TOC (two-column, sticky cover)
 *  2. Browse by Topic (nine topic cards)
 *  3. Email signup CTA (gradient)
 *  4. Previous issues (up to 3, compact) + archive link
 *
 * @package TCLAS
 */

get_header();

// ── Gather issue data ────────────────────────────────────────────────────────
global $wpdb;

// phpcs:disable WordPress.DB.DirectDatabaseQuery
$_all_dates = $wpdb->get_col( $wpdb->prepare(
	"SELECT DISTINCT meta_value FROM {$wpdb->postmeta}
	 WHERE meta_key = %s AND meta_value != ''
	 ORDER BY meta_value DESC
	 LIMIT 5",
	'tclas_issue_date'
) );
// phpcs:enable

$issue_date  = ! empty( $_all_dates ) ? $_all_dates[0] : '';
$prev_dates  = array_slice( $_all_dates, 1, 3 ); // up to 3 previous issues

$issue_label = '';
$issue_posts = [];

if ( $issue_date ) {
	$_dt         = DateTime::createFromFormat( 'Y-m', $issue_date );
	$issue_label = $_dt ? $_dt->format( 'F Y' ) : $issue_date;

	$issue_posts = get_posts( [
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'meta_query'     => [ [ 'key' => 'tclas_issue_date', 'value' => $issue_date ] ],
		'meta_key'       => 'tclas_issue_order',
		'orderby'        => 'meta_value_num',
		'order'          => 'ASC',
	] );
}

// Cover post for current issue: first Main Story term + featured image
$cover_post_id = 0;
foreach ( $issue_posts as $_p ) {
	$_terms = wp_get_post_terms( $_p->ID, 'tclas_department', [ 'fields' => 'slugs' ] );
	if ( in_array( 'main-story', (array) $_terms, true ) && has_post_thumbnail( $_p->ID ) ) {
		$cover_post_id = $_p->ID;
		break;
	}
}
$has_cover = (bool) $cover_post_id;

// Archive page URL
$_archive_ids = get_posts( [
	'post_type'      => 'page',
	'meta_key'       => '_wp_page_template',
	'meta_value'     => 'page-templates/page-newsletter-archive.php',
	'posts_per_page' => 1,
	'fields'         => 'ids',
	'no_found_rows'  => true,
] );
$archive_url = $_archive_ids ? get_permalink( $_archive_ids[0] ) : '';

// Single-issue page URL for current issue
$issue_page_url = $issue_date
	? home_url( '/newsletter/issue/' . rawurlencode( $issue_date ) . '/' )
	: '';

// ── Department terms for Browse by Topic ────────────────────────────────────
$_dept_terms = get_terms( [
	'taxonomy'   => 'tclas_department',
	'hide_empty' => false,
	'orderby'    => 'name',
	'order'      => 'ASC',
] );
$dept_terms = is_wp_error( $_dept_terms )
	? []
	: array_values( array_filter( (array) $_dept_terms, fn( $t ) => $t->slug !== 'main-story' ) );

// Fallback inline SVG icons by term slug (used when no image uploaded via ACF)
$_s = 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"';
$dept_icon_svgs = [
	'wellkomm'       => "<svg {$_s}><path d='M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2'/><circle cx='9' cy='7' r='4'/><path d='M23 21v-2a4 4 0 00-3-3.87'/><path d='M16 3.13a4 4 0 010 7.75'/></svg>",
	'communauteit'   => "<svg {$_s}><path d='M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2'/><circle cx='9' cy='7' r='4'/><path d='M23 21v-2a4 4 0 00-3-3.87'/><path d='M16 3.13a4 4 0 010 7.75'/></svg>",
	'an-der-kichen'  => "<svg {$_s}><path d='M3 2v7c0 1.1.9 2 2 2h4a2 2 0 002-2V2'/><path d='M7 2v20'/><path d='M21 15V2a5 5 0 00-5 5v6c0 1.1.9 2 2 2h3zm0 0v7'/></svg>",
	'geschicht'      => "<svg {$_s}><path d='M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'/></svg>",
	'zu-letzebuerg'  => "<svg {$_s}><polygon points='3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21'/><line x1='9' y1='3' x2='9' y2='18'/><line x1='15' y1='6' x2='15' y2='21'/></svg>",
	'traditiounen'   => "<svg {$_s}><path d='M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.437-1.125a1.64 1.64 0 011.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z'/></svg>",
	'eist-sprooch'   => "<svg {$_s}><path d='M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z'/></svg>",
	'evenementer'    => "<svg {$_s}><rect x='3' y='4' width='18' height='18' rx='2' ry='2'/><line x1='16' y1='2' x2='16' y2='6'/><line x1='8' y1='2' x2='8' y2='6'/><line x1='3' y1='10' x2='21' y2='10'/></svg>",
	'spezialbericht' => "<svg {$_s}><path d='M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z'/></svg>",
];

// ── Shared: render one article TOC row ───────────────────────────────────────
if ( ! function_exists( 'tclas_nl_toc_row' ) ) {
	/**
	 * Renders a single <li> TOC item. Called for both current and previous issues.
	 */
	function tclas_nl_toc_row( WP_Post $p, int $cover_id ): void {
		$dept_terms = wp_get_post_terms( $p->ID, 'tclas_department' );
		$dept_lux   = '';
		$dept_en    = '';
		if ( ! is_wp_error( $dept_terms ) ) {
			foreach ( $dept_terms as $t ) {
				if ( $t->slug !== 'main-story' ) {
					$dept_lux = $t->name;
					$dept_en  = $t->description;
					break;
				}
			}
		}
		$is_lead   = ( $p->ID === $cover_id );
		$excerpt   = has_excerpt( $p->ID )
			? wp_trim_words( get_the_excerpt( $p ), 25, '&hellip;' )
			: wp_trim_words( $p->post_content, 25, '&hellip;' );
		$words     = str_word_count( wp_strip_all_tags( $p->post_content ) );
		$read_mins = max( 1, round( $words / 200 ) );
		?>
		<li class="tclas-issue-article<?php echo $is_lead ? ' tclas-issue-article--lead' : ''; ?>">
			<a
				href="<?php echo esc_url( get_permalink( $p->ID ) ); ?>"
				class="tclas-issue-article-link"
				aria-label="<?php echo esc_attr( sprintf(
					/* translators: %s: article title */
					__( 'Read article: %s', 'tclas' ),
					get_the_title( $p )
				) ); ?>"
			>
				<?php if ( $dept_lux ) : ?>
				<span class="tclas-issue-dept">
					<span lang="lb"><?php echo esc_html( $dept_lux ); ?></span><?php if ( $dept_en ) : ?><span class="tclas-issue-dept__en"><?php echo esc_html( $dept_en ); ?></span><?php endif; ?>
				</span>
				<?php endif; ?>
				<h3 class="tclas-issue-title"><?php echo esc_html( get_the_title( $p ) ); ?></h3>
				<?php if ( $excerpt ) : ?>
				<p class="tclas-issue-excerpt"><?php echo esc_html( $excerpt ); ?></p>
				<?php endif; ?>
				<?php tclas_members_only_badge( $p->ID ); ?>
				<span class="tclas-issue-meta"><?php printf(
					/* translators: %d: estimated read time in minutes */
					esc_html__( '%d min read', 'tclas' ),
					(int) $read_mins
				); ?></span>
			</a>
		</li>
		<?php
	}
}
?>


<!-- ══════════════════════════════════════════════════════════════════════════
     SECTION 1 — Current Issue
     ════════════════════════════════════════════════════════════════════════ -->
<section class="tclas-section tclas-nl-current-section" id="nl-current">
	<div class="container-tclas">

		<?php if ( empty( $issue_posts ) ) : ?>

			<!-- Empty state -->
			<div class="tclas-nl-empty">
				<p><?php esc_html_e( 'The first issue of the Loon & Lion is on its way. Subscribe below so you don\'t miss it!', 'tclas' ); ?></p>
			</div>

			<?php else : ?>

			<div class="tclas-issue-layout<?php echo $has_cover ? '' : ' tclas-issue-layout--no-cover'; ?>">

			<!-- LEFT: Masthead + article TOC -->
			<div class="tclas-issue-toc-col">

				<p class="tclas-nl-current-eyebrow">
					<?php esc_html_e( 'TCLAS Newsletter', 'tclas' ); ?>
				</p>

				<h1 class="tclas-issue-masthead tclas-issue-masthead--date">
					<?php echo esc_html( $issue_label ); ?>
				</h1>

				<ol class="tclas-issue-toc">
					<?php foreach ( $issue_posts as $_p ) : tclas_nl_toc_row( $_p, $cover_post_id ); endforeach; ?>
				</ol>

				<?php if ( $issue_page_url ) : ?>
				<div class="tclas-nl-view-issue-wrap">
					<a href="<?php echo esc_url( $issue_page_url ); ?>" class="tclas-nl-view-issue-link">
						<?php esc_html_e( 'View full issue', 'tclas' ); ?>
						<svg aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
					</a>
				</div>
				<?php endif; ?>

			</div><!-- .tclas-issue-toc-col -->

			<?php if ( $has_cover ) : ?>
			<!-- RIGHT: Sticky cover image -->
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
										/* translators: %s: issue title */
										__( 'Cover of %s', 'tclas' ),
										$issue_label
									),
								]
							); ?>
						</div>
					</a>
				</div>
			</div>
			<?php endif; ?>

			</div><!-- .tclas-issue-layout -->

			<?php endif; ?>

	</div><!-- .container-tclas -->
</section>


<!-- ══════════════════════════════════════════════════════════════════════════
     SECTION 2 — Browse by Topic
     ════════════════════════════════════════════════════════════════════════ -->
<?php if ( ! empty( $dept_terms ) ) : ?>
<section class="tclas-section tclas-nl-topics-section" id="nl-topics" aria-labelledby="tclas-nl-topics-heading">
	<div class="container-tclas">

		<h2 id="tclas-nl-topics-heading" class="tclas-nl-section-heading">
			<?php esc_html_e( 'Browse by Topic', 'tclas' ); ?>
		</h2>

		<div class="tclas-nl-topics-grid">
			<?php foreach ( $dept_terms as $_t ) :
				$_topic_url = get_term_link( $_t );
				if ( is_wp_error( $_topic_url ) ) { continue; }
				$_icon_img  = function_exists( 'get_field' )
					? get_field( 'department_icon', 'tclas_department_' . $_t->term_id )
					: null;
				$_svg       = $dept_icon_svgs[ $_t->slug ] ?? $dept_icon_svgs['communauteit'];
			?>
			<a
				href="<?php echo esc_url( $_topic_url ); ?>"
				class="tclas-nl-topic-card"
				aria-label="<?php echo esc_attr( sprintf(
					/* translators: %s: English topic name, e.g. "Community" */
					__( 'Browse %s articles', 'tclas' ),
					$_t->description ?: $_t->name
				) ); ?>"
			>
				<div class="tclas-nl-topic-icon-wrap" aria-hidden="true">
					<?php if ( ! empty( $_icon_img['url'] ) ) : ?>
						<img src="<?php echo esc_url( $_icon_img['url'] ); ?>" alt="" class="tclas-nl-topic-icon-img">
					<?php else : ?>
						<?php echo $_svg; // phpcs:ignore WordPress.Security.EscapeOutput — SVG is from code, not user input ?>
					<?php endif; ?>
				</div>

				<div>
					<div class="tclas-nl-topic-name" lang="lb">
						<?php echo esc_html( $_t->name ); ?>
					</div>
					<?php if ( $_t->description ) : ?>
					<div class="tclas-nl-topic-name-en">
						<?php echo esc_html( $_t->description ); ?>
					</div>
					<?php endif; ?>
				</div>
			</a>
			<?php endforeach; ?>
		</div><!-- .tclas-nl-topics-grid -->

	</div><!-- .container-tclas -->
</section>
<?php endif; ?>


<!-- ══════════════════════════════════════════════════════════════════════════
     SECTION 3 — Email Signup CTA
     ════════════════════════════════════════════════════════════════════════ -->
<section class="tclas-section tclas-nl-signup-section" aria-labelledby="tclas-nl-signup-heading">
	<div class="container-tclas">
		<div class="tclas-nl-signup tclas-nl-signup--gradient">

			<h2 id="tclas-nl-signup-heading" class="tclas-nl-signup__heading">
				<?php esc_html_e( 'Stories from both shores, straight to your inbox', 'tclas' ); ?>
			</h2>

			<div class="tclas-nl-signup__divider" aria-hidden="true"></div>

			<p class="tclas-nl-signup__sub">
				<?php esc_html_e( 'Never miss an issue of The Loon & The Lion. Quarterly stories, events, and Luxembourg connections — delivered to TCLAS members and friends.', 'tclas' ); ?>
			</p>

			<?php if ( function_exists( 'tclas_footer_newsletter_form' ) ) {
				tclas_footer_newsletter_form();
			} ?>

			<div class="tclas-nl-cta-buttons">
				<a href="<?php echo esc_url( home_url( '/join/' ) ); ?>" class="tclas-nl-cta-btn tclas-nl-cta-btn--outline">
					<?php esc_html_e( 'Become a member', 'tclas' ); ?>
				</a>
			</div>

		</div><!-- .tclas-nl-signup -->
	</div><!-- .container-tclas -->
</section>


<!-- ══════════════════════════════════════════════════════════════════════════
     SECTION 4 — Previous Issues + Archive link
     ════════════════════════════════════════════════════════════════════════ -->
<?php if ( ! empty( $prev_dates ) ) : ?>
<section class="tclas-section" id="nl-prev" aria-labelledby="tclas-nl-prev-heading">
	<div class="container-tclas">

		<h2 id="tclas-nl-prev-heading" class="tclas-nl-section-heading">
			<?php esc_html_e( 'Previous Issues', 'tclas' ); ?>
		</h2>

		<div class="tclas-nl-prev-list">
			<?php foreach ( $prev_dates as $_prev_date ) :

				$_prev_dt    = DateTime::createFromFormat( 'Y-m', $_prev_date );
				$_prev_label = $_prev_dt ? $_prev_dt->format( 'F Y' ) : $_prev_date;
				$_prev_posts = get_posts( [
					'post_type'      => 'post',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'meta_query'     => [ [ 'key' => 'tclas_issue_date', 'value' => $_prev_date ] ],
					'meta_key'       => 'tclas_issue_order',
					'orderby'        => 'meta_value_num',
					'order'          => 'ASC',
				] );

				if ( empty( $_prev_posts ) ) { continue; }

				$_prev_cover = 0;
				foreach ( $_prev_posts as $_pp ) {
					$_pp_terms = wp_get_post_terms( $_pp->ID, 'tclas_department', [ 'fields' => 'slugs' ] );
					if ( in_array( 'main-story', (array) $_pp_terms, true ) && has_post_thumbnail( $_pp->ID ) ) {
						$_prev_cover = $_pp->ID;
						break;
					}
				}

				$_prev_url = home_url( '/newsletter/issue/' . rawurlencode( $_prev_date ) . '/' );
			?>
			<div class="tclas-nl-prev-issue">
				<div class="tclas-nl-prev-issue-grid<?php echo $_prev_cover ? '' : ' tclas-nl-prev-issue-grid--no-cover'; ?>">

					<!-- Articles: 2/3 width -->
					<div class="tclas-nl-prev-issue-toc">
						<h3 class="tclas-nl-prev-issue-date"><?php echo esc_html( $_prev_label ); ?></h3>

						<ol class="tclas-issue-toc">
							<?php foreach ( $_prev_posts as $_pp ) : tclas_nl_toc_row( $_pp, $_prev_cover ); endforeach; ?>
						</ol>

						<div class="tclas-nl-view-issue-wrap">
							<a href="<?php echo esc_url( $_prev_url ); ?>" class="tclas-nl-view-issue-link">
								<?php esc_html_e( 'View full issue', 'tclas' ); ?>
								<svg aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
							</a>
						</div>
					</div>

					<?php if ( $_prev_cover ) : ?>
					<!-- Cover image: 1/3 width, sticky -->
					<div class="tclas-nl-prev-issue-cover">
						<div class="tclas-nl-prev-cover-wrap">
							<a
								href="<?php echo esc_url( get_permalink( $_prev_cover ) ); ?>"
								class="tclas-issue-cover-link"
								aria-hidden="true"
								tabindex="-1"
							>
								<div class="tclas-issue-cover-frame">
									<?php echo get_the_post_thumbnail(
										$_prev_cover,
										'medium_large',
										[
											'class' => 'tclas-issue-cover-img',
											'alt'   => sprintf(
												/* translators: %s: issue title */
												__( 'Cover of %s', 'tclas' ),
												$_prev_label
											),
										]
									); ?>
								</div>
							</a>
						</div>
					</div>
					<?php endif; ?>

				</div><!-- .tclas-nl-prev-issue-grid -->
			</div><!-- .tclas-nl-prev-issue -->
			<?php endforeach; ?>
		</div><!-- .tclas-nl-prev-list -->

		<?php if ( $archive_url ) : ?>
		<div class="tclas-nl-archive-cta">
			<a href="<?php echo esc_url( $archive_url ); ?>" class="tclas-nl-archive-link-lg">
				<?php esc_html_e( 'Explore the full archive', 'tclas' ); ?> &rarr;
			</a>
		</div>
		<?php endif; ?>

	</div><!-- .container-tclas -->
</section>
<?php endif; ?>

<?php get_footer(); ?>
