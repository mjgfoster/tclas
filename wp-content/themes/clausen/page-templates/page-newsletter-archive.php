<?php
/**
 * Template Name: Newsletter Archive (Loon & Lion)
 *
 * Two-tab archive: "By Issue" (chronological) and "By Topic" (by department).
 *
 * @package TCLAS
 */

get_header();

// ── Query all newsletter posts ───────────────────────────────────────────────
$all_posts = get_posts( [
	'post_type'      => 'post',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'meta_key'       => 'tclas_issue_date',
	'orderby'        => 'meta_value',
	'order'          => 'DESC',
] );

// ── Group posts by issue date ────────────────────────────────────────────────
$issues = [];
foreach ( $all_posts as $post ) {
	$issue_date  = get_post_meta( $post->ID, 'tclas_issue_date', true );
	$issue_order = (int) ( get_post_meta( $post->ID, 'tclas_issue_order', true ) ?: 99 );
	if ( ! $issue_date ) {
		continue;
	}
	$issues[ $issue_date ][] = [
		'post'  => $post,
		'order' => $issue_order,
	];
}

// Sort within each issue by order (ascending)
foreach ( $issues as $date => &$articles ) {
	usort( $articles, fn( $a, $b ) => $a['order'] <=> $b['order'] );
}
unset( $articles );

// ── Group posts by department ────────────────────────────────────────────────
$by_topic = [];
foreach ( $all_posts as $post ) {
	$dept_terms = wp_get_post_terms( $post->ID, 'tclas_department' );
	if ( is_wp_error( $dept_terms ) ) { continue; }
	foreach ( $dept_terms as $t ) {
		if ( $t->slug === 'main-story' ) { continue; }
		if ( ! isset( $by_topic[ $t->slug ] ) ) {
			$by_topic[ $t->slug ] = [
				'term'  => $t,
				'posts' => [],
			];
		}
		$by_topic[ $t->slug ]['posts'][] = $post;
		break; // one department per article
	}
}

// Sort topics alphabetically by English description
uasort( $by_topic, fn( $a, $b ) => strcmp( $a['term']->description, $b['term']->description ) );

// ── Helpers ──────────────────────────────────────────────────────────────────
$_excerpt = function( WP_Post $p, int $words = 20 ): string {
	return has_excerpt( $p->ID )
		? wp_trim_words( get_the_excerpt( $p ), $words, '&hellip;' )
		: wp_trim_words( $p->post_content, $words, '&hellip;' );
};

$_dept_label = function( WP_Post $p ): array {
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
?>

<div class="tclas-page-header">
	<div class="container-tclas">
		<?php tclas_breadcrumb( __( 'Newsletter Archive', 'tclas' ) ); ?>
		<h1 class="tclas-page-header__title"><?php esc_html_e( 'Newsletter Archive', 'tclas' ); ?></h1>
	</div>
</div>

<section class="tclas-section">
	<div class="container-tclas">

		<?php if ( empty( $issues ) ) : ?>

			<p class="tclas-story-hint">
				<?php esc_html_e( 'No issues have been published yet. Check back soon!', 'tclas' ); ?>
			</p>

		<?php else : ?>

		<!-- ── Tabs ───────────────────────────────────────────────────────── -->
		<div class="tclas-tabs" role="tablist">
			<button
				class="tclas-tab is-active"
				role="tab"
				aria-selected="true"
				aria-controls="archive-by-issue"
				id="tab-by-issue"
			><?php esc_html_e( 'By Issue', 'tclas' ); ?></button>
			<button
				class="tclas-tab"
				role="tab"
				aria-selected="false"
				aria-controls="archive-by-topic"
				id="tab-by-topic"
			><?php esc_html_e( 'By Topic', 'tclas' ); ?></button>
		</div>

		<!-- ══════════════════════════════════════════════════════════════════
		     TAB 1: BY ISSUE (chronological)
		     ══════════════════════════════════════════════════════════════════ -->
		<div class="tclas-tab-panel" id="archive-by-issue" role="tabpanel" aria-labelledby="tab-by-issue">

			<?php foreach ( $issues as $issue_date => $issue_articles ) :
				$dt    = DateTime::createFromFormat( 'Y-m', $issue_date );
				$label = $dt ? $dt->format( 'F Y' ) : esc_html( $issue_date );

				// Check for custom issue title
				foreach ( $issue_articles as $a ) {
					$_ct = get_post_meta( $a['post']->ID, 'tclas_issue_title', true );
					if ( $_ct ) { $label = $_ct; break; }
				}

				$issue_url = home_url( '/newsletter/issue/' . $issue_date . '/' );
			?>
			<div class="tclas-archive-issue">
				<header class="tclas-archive-issue__header">
					<h2 class="tclas-archive-issue__title">
						<a href="<?php echo esc_url( $issue_url ); ?>"><?php echo esc_html( $label ); ?></a>
					</h2>
					<span class="tclas-archive-issue__count"><?php printf(
						esc_html( _n( '%d article', '%d articles', count( $issue_articles ), 'tclas' ) ),
						count( $issue_articles )
					); ?></span>
				</header>

				<ul class="tclas-archive-issue__list">
					<?php foreach ( $issue_articles as $a ) :
						$p   = $a['post'];
						$_d  = $_dept_label( $p );
						$_by = $_byline( $p );
					?>
					<li>
						<a href="<?php echo esc_url( get_permalink( $p->ID ) ); ?>" class="tclas-archive-article">
							<?php if ( $_d['lux'] ) : ?>
							<span class="tclas-ie-dept-label">
								<span lang="lb"><?php echo esc_html( $_d['lux'] ); ?></span><?php if ( $_d['en'] ) : ?><span class="tclas-ie-dept-label__en"><?php echo esc_html( $_d['en'] ); ?></span><?php endif; ?>
							</span>
							<?php endif; ?>
							<span class="tclas-archive-article__title"><?php echo esc_html( get_the_title( $p ) ); ?></span>
							<?php if ( $_by ) : ?>
							<span class="tclas-archive-article__byline"><?php printf( esc_html__( 'By %s', 'tclas' ), esc_html( $_by ) ); ?></span>
							<?php endif; ?>
							<?php tclas_members_only_badge( $p->ID ); ?>
						</a>
					</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php endforeach; ?>

		</div><!-- #archive-by-issue -->

		<!-- ══════════════════════════════════════════════════════════════════
		     TAB 2: BY TOPIC (grouped by department)
		     ══════════════════════════════════════════════════════════════════ -->
		<div class="tclas-tab-panel" id="archive-by-topic" role="tabpanel" aria-labelledby="tab-by-topic" hidden>

			<?php foreach ( $by_topic as $slug => $group ) :
				$term = $group['term'];
			?>
			<div class="tclas-archive-topic">
				<header class="tclas-archive-topic__header">
					<h2 class="tclas-archive-topic__title">
						<span lang="lb"><?php echo esc_html( $term->name ); ?></span>
						<?php if ( $term->description ) : ?>
						<span class="tclas-archive-topic__en"><?php echo esc_html( $term->description ); ?></span>
						<?php endif; ?>
					</h2>
					<span class="tclas-archive-topic__count"><?php printf(
						esc_html( _n( '%d article', '%d articles', count( $group['posts'] ), 'tclas' ) ),
						count( $group['posts'] )
					); ?></span>
				</header>

				<ul class="tclas-archive-topic__list">
					<?php foreach ( $group['posts'] as $p ) :
						$_issue = get_post_meta( $p->ID, 'tclas_issue_date', true );
						$_dt    = $_issue ? DateTime::createFromFormat( 'Y-m', $_issue ) : null;
						$_issue_label = $_dt ? $_dt->format( 'M Y' ) : '';
						$_by    = $_byline( $p );
					?>
					<li>
						<a href="<?php echo esc_url( get_permalink( $p->ID ) ); ?>" class="tclas-archive-article">
							<span class="tclas-archive-article__title"><?php echo esc_html( get_the_title( $p ) ); ?></span>
							<span class="tclas-archive-article__meta">
								<?php if ( $_by ) : ?>
								<span class="tclas-archive-article__byline"><?php printf( esc_html__( 'By %s', 'tclas' ), esc_html( $_by ) ); ?></span>
								<?php endif; ?>
								<?php if ( $_issue_label ) : ?>
								<span class="tclas-archive-article__issue"><?php echo esc_html( $_issue_label ); ?></span>
								<?php endif; ?>
							</span>
							<?php tclas_members_only_badge( $p->ID ); ?>
						</a>
					</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php endforeach; ?>

		</div><!-- #archive-by-topic -->

		<?php endif; ?>

	</div><!-- .container-tclas -->
</section>

<?php get_footer(); ?>
