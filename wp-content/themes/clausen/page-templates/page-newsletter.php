<?php
/**
 * Template Name: Newsletter — Current Issue
 *
 * Loon & Lion newsletter homepage. Sections:
 *  1. Display masthead + welcome letter + current issue TOC
 *  2. Browse by Topic (nine topic cards)
 *  3. Email signup CTA (gradient)
 *  4. Previous issues (compact) + archive link
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
$prev_dates  = array_slice( $_all_dates, 1, 3 );

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

	// Custom issue title
	foreach ( $issue_posts as $_tp ) {
		$_ct = get_post_meta( $_tp->ID, 'tclas_issue_title', true );
		if ( $_ct ) { $issue_label = $_ct; break; }
	}
}

// Separate welcome letter from TOC articles
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

// Welcome cover image
$welcome_cover_id = $welcome_post ? get_post_thumbnail_id( $welcome_post->ID ) : 0;

// Issue page URL
$issue_page_url = $issue_date
	? home_url( '/newsletter/issue/' . rawurlencode( $issue_date ) . '/' )
	: '';

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

// ── Helpers ──────────────────────────────────────────────────────────────────
$_excerpt = function( WP_Post $p, int $words = 30 ): string {
	return has_excerpt( $p->ID )
		? wp_trim_words( get_the_excerpt( $p ), $words, '&hellip;' )
		: wp_trim_words( $p->post_content, $words, '&hellip;' );
};

$_dept = function( WP_Post $p ): array {
	$terms = wp_get_post_terms( $p->ID, 'tclas_department' );
	if ( is_wp_error( $terms ) ) { return [ 'lux' => '', 'en' => '' ]; }
	foreach ( $terms as $t ) {
		if ( $t->slug !== 'main-story' ) {
			return [ 'lux' => $t->name, 'en' => $t->description ];
		}
	}
	return [ 'lux' => '', 'en' => '' ];
};

$_byline = function( WP_Post $p ): string {
	if ( get_post_meta( $p->ID, 'tclas_hide_byline', true ) ) { return ''; }
	$custom = get_post_meta( $p->ID, 'tclas_byline', true );
	return $custom ? $custom : get_the_author_meta( 'display_name', $p->post_author );
};

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
?>


<!-- ══════════════════════════════════════════════════════════════════════════
     SECTION 1 — Display Masthead
     ════════════════════════════════════════════════════════════════════════ -->
<div class="tclas-nl-hero">
	<div class="container-tclas">
		<div class="tclas-nl-hero__masthead">
			<span class="tclas-nl-hero__loon"><?php esc_html_e( 'The Loon', 'tclas' ); ?></span>
			<span class="tclas-nl-hero__amp"> &amp; </span>
			<span class="tclas-nl-hero__lion"><?php esc_html_e( 'The Lion', 'tclas' ); ?></span>
		</div>
		<p class="tclas-nl-hero__sub"><?php esc_html_e( 'The newsletter of the Twin Cities Luxembourg American Society', 'tclas' ); ?></p>
	</div>
</div>


<?php if ( ! empty( $issue_posts ) ) : ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     SECTION 2 — Welcome Letter
     ════════════════════════════════════════════════════════════════════════ -->
<?php if ( $welcome_post ) :
	$_w_byline = $_byline( $welcome_post );
?>
<div class="container-tclas tclas-issue-welcome-wrap">
	<div class="tclas-issue-welcome<?php echo $welcome_cover_id ? ' tclas-issue-welcome--has-cover' : ''; ?>">

		<?php if ( $welcome_cover_id ) : ?>
		<div class="tclas-issue-welcome__cover">
			<?php echo get_the_post_thumbnail( $welcome_post->ID, 'large', [ 'alt' => esc_attr( $issue_label ) ] ); ?>
		</div>
		<?php endif; ?>

		<article class="tclas-issue-welcome__content">
			<span class="tclas-eyebrow"><?php echo esc_html( $issue_label ); ?></span>
			<h2 class="tclas-issue-welcome__title"><?php echo esc_html( get_the_title( $welcome_post ) ); ?></h2>
			<div class="tclas-issue-welcome__body entry-content">
				<?php echo apply_filters( 'the_content', $welcome_post->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<?php if ( $_w_byline ) : ?>
			<p class="tclas-issue-welcome__byline">— <?php echo esc_html( $_w_byline ); ?></p>
			<?php endif; ?>
		</article>

	</div>
</div>
<?php endif; ?>


<!-- ══════════════════════════════════════════════════════════════════════════
     SECTION 3 — Current Issue TOC
     ════════════════════════════════════════════════════════════════════════ -->
<?php if ( ! empty( $toc_posts ) ) : ?>
<section class="tclas-section bg-white" aria-label="<?php esc_attr_e( 'Current issue articles', 'tclas' ); ?>">
	<div class="container-tclas">
		<h2 class="tclas-issue-toc__heading"><?php esc_html_e( 'In the current issue', 'tclas' ); ?></h2>

		<div class="tclas-issue-toc">
			<?php foreach ( $toc_posts as $_p ) :
				$_d       = $_dept( $_p );
				$_ex      = $_excerpt( $_p, 30 );
				$_by      = $_byline( $_p );
				$_has_img = has_post_thumbnail( $_p->ID );
			?>
			<article class="tclas-issue-toc__card<?php echo $_has_img ? '' : ' tclas-issue-toc__card--no-img'; ?>">
				<a href="<?php echo esc_url( get_permalink( $_p->ID ) ); ?>" class="tclas-issue-toc__link">

					<?php if ( $_has_img ) : ?>
					<div class="tclas-issue-toc__image">
						<?php echo get_the_post_thumbnail( $_p->ID, 'medium_large', [ 'alt' => '' ] ); ?>
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
							<span class="tclas-issue-toc__byline"><?php printf( esc_html__( 'By %s', 'tclas' ), esc_html( $_by ) ); ?></span>
							<?php endif; ?>
							<?php tclas_members_only_badge( $_p->ID ); ?>
						</div>
					</div>

				</a>
			</article>
			<?php endforeach; ?>
		</div>

		<?php if ( $issue_page_url ) : ?>
		<div class="tclas-nl-view-issue-wrap" style="margin-top: 1.5rem;">
			<a href="<?php echo esc_url( $issue_page_url ); ?>" class="btn btn-solid-ardoise">
				<?php esc_html_e( 'Read full issue', 'tclas' ); ?> →
			</a>
		</div>
		<?php endif; ?>

	</div>
</section>
<?php endif; ?>
<?php endif; ?>


<!-- ══════════════════════════════════════════════════════════════════════════
     SECTION 4 — Browse by Topic
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
					__( 'Browse %s articles', 'tclas' ),
					$_t->description ?: $_t->name
				) ); ?>"
			>
				<div class="tclas-nl-topic-icon-wrap" aria-hidden="true">
					<?php if ( ! empty( $_icon_img['url'] ) ) : ?>
						<img src="<?php echo esc_url( $_icon_img['url'] ); ?>" alt="" class="tclas-nl-topic-icon-img">
					<?php else : ?>
						<?php echo $_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
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
		</div>

	</div>
</section>
<?php endif; ?>


<!-- ══════════════════════════════════════════════════════════════════════════
     SECTION 5 — Email Signup CTA
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

		</div>
	</div>
</section>


<!-- ══════════════════════════════════════════════════════════════════════════
     SECTION 6 — Previous Issues + Archive link
     ════════════════════════════════════════════════════════════════════════ -->
<?php if ( ! empty( $prev_dates ) ) : ?>
<section class="tclas-section" id="nl-prev" aria-labelledby="tclas-nl-prev-heading">
	<div class="container-tclas">

		<h2 id="tclas-nl-prev-heading" class="tclas-nl-section-heading">
			<?php esc_html_e( 'Previous Issues', 'tclas' ); ?>
		</h2>

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

			// Custom issue title
			foreach ( $_prev_posts as $_pp ) {
				$_ct = get_post_meta( $_pp->ID, 'tclas_issue_title', true );
				if ( $_ct ) { $_prev_label = $_ct; break; }
			}

			$_prev_url = home_url( '/newsletter/issue/' . rawurlencode( $_prev_date ) . '/' );
		?>
		<div class="tclas-archive-issue">
			<header class="tclas-archive-issue__header">
				<h3 class="tclas-archive-issue__title">
					<a href="<?php echo esc_url( $_prev_url ); ?>"><?php echo esc_html( $_prev_label ); ?></a>
				</h3>
				<span class="tclas-archive-issue__count"><?php printf(
					esc_html( _n( '%d article', '%d articles', count( $_prev_posts ), 'tclas' ) ),
					count( $_prev_posts )
				); ?></span>
			</header>

			<ul class="tclas-archive-issue__list">
				<?php foreach ( $_prev_posts as $_pp ) :
					$_d  = $_dept( $_pp );
					$_by = $_byline( $_pp );
				?>
				<li>
					<a href="<?php echo esc_url( get_permalink( $_pp->ID ) ); ?>" class="tclas-archive-article">
						<?php if ( $_d['lux'] ) : ?>
						<span class="tclas-ie-dept-label">
							<span lang="lb"><?php echo esc_html( $_d['lux'] ); ?></span><?php if ( $_d['en'] ) : ?><span class="tclas-ie-dept-label__en"><?php echo esc_html( $_d['en'] ); ?></span><?php endif; ?>
						</span>
						<?php endif; ?>
						<span class="tclas-archive-article__title"><?php echo esc_html( get_the_title( $_pp ) ); ?></span>
						<?php if ( $_by ) : ?>
						<span class="tclas-archive-article__byline"><?php printf( esc_html__( 'By %s', 'tclas' ), esc_html( $_by ) ); ?></span>
						<?php endif; ?>
						<?php tclas_members_only_badge( $_pp->ID ); ?>
					</a>
				</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php endforeach; ?>

		<?php if ( $archive_url ) : ?>
		<div style="margin-top: 1.5rem;">
			<a href="<?php echo esc_url( $archive_url ); ?>" class="btn btn-outline-ardoise">
				<?php esc_html_e( 'Explore the full archive', 'tclas' ); ?> →
			</a>
		</div>
		<?php endif; ?>

	</div>
</section>
<?php endif; ?>

<?php get_footer(); ?>
