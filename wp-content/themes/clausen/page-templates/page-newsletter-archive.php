<?php
/**
 * Template Name: Newsletter Archive (Loon & Lion)
 *
 * Magazine-style table of contents for the Loon & Lion newsletter.
 * Articles are grouped by `tclas_issue_date` (YYYY-MM), displayed newest
 * first. Within each issue group, articles are sorted by `tclas_issue_order`.
 * The "Main Story" department post provides the cover hero image for each issue.
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

// Sort within each issue by tclas_issue_order (ascending)
foreach ( $issues as $date => &$articles ) {
	usort( $articles, fn( $a, $b ) => $a['order'] <=> $b['order'] );
}
unset( $articles );

// Issues are already newest-first (meta_value DESC in query)
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

			<?php foreach ( $issues as $issue_date => $articles ) :

				// ── Format issue date header ─────────────────────────────
				$dt          = DateTime::createFromFormat( 'Y-m', $issue_date );
				$label       = $dt ? $dt->format( 'F Y' ) : esc_html( $issue_date );

				// ── Find the Main Story for the cover image ──────────────
				$cover_post  = null;
				$main_story  = get_term_by( 'slug', 'main-story', 'tclas_department' );
				foreach ( $articles as $a ) {
					if ( $main_story ) {
						$terms = wp_get_post_terms( $a['post']->ID, 'tclas_department', [ 'fields' => 'slugs' ] );
						if ( in_array( 'main-story', $terms, true ) ) {
							$cover_post = $a['post'];
							break;
						}
					}
				}
			?>

			<div class="tclas-issue-group">

				<!-- ── Issue masthead ──────────────────────────────────── -->
				<header class="tclas-issue-masthead">
					<span class="tclas-eyebrow"><?php esc_html_e( 'Issue', 'tclas' ); ?></span>
					<h2 class="tclas-issue-masthead__date"><?php echo esc_html( $label ); ?></h2>
				</header>

				<div class="tclas-issue-body">

					<?php if ( $cover_post && has_post_thumbnail( $cover_post->ID ) ) : ?>
					<!-- ── Cover image (Main Story featured image) ─────── -->
					<div class="tclas-issue-cover-wrap">
						<a href="<?php echo esc_url( get_permalink( $cover_post->ID ) ); ?>" class="tclas-issue-cover-link" aria-hidden="true" tabindex="-1">
							<?php echo get_the_post_thumbnail( $cover_post->ID, 'large', [ 'class' => 'tclas-issue-cover', 'alt' => '' ] ); ?>
						</a>
					</div>
					<?php endif; ?>

					<!-- ── Table of contents ───────────────────────────── -->
					<ol class="tclas-toc-list">
						<?php foreach ( $articles as $a ) :
							$p           = $a['post'];
							$dept_terms  = wp_get_post_terms( $p->ID, 'tclas_department' );
							// Skip 'main-story' (structural term) when showing the visible label.
							$dept_label  = '';
							if ( ! is_wp_error( $dept_terms ) ) {
								foreach ( $dept_terms as $t ) {
									if ( $t->slug !== 'main-story' ) {
										$dept_label = $t->name;
										break;
									}
								}
							}
							$excerpt     = has_excerpt( $p->ID )
								? wp_trim_words( get_the_excerpt( $p ), 20, '&hellip;' )
								: wp_trim_words( $p->post_content, 20, '&hellip;' );

							// Read-time estimate (~200 wpm)
							$word_count = str_word_count( wp_strip_all_tags( $p->post_content ) );
							$read_mins  = max( 1, round( $word_count / 200 ) );
						?>
						<li class="tclas-toc-item">
							<?php if ( $dept_label ) : ?>
							<span class="tclas-toc-department tclas-eyebrow"><?php echo esc_html( $dept_label ); ?></span>
							<?php endif; ?>
							<h3 class="tclas-toc-title">
								<a href="<?php echo esc_url( get_permalink( $p->ID ) ); ?>">
									<?php echo esc_html( get_the_title( $p ) ); ?>
								</a>
							</h3>
							<?php if ( $excerpt ) : ?>
							<p class="tclas-toc-excerpt"><?php echo esc_html( $excerpt ); ?></p>
							<?php endif; ?>
							<span class="tclas-toc-meta">
								<?php printf(
									/* translators: %d: read time in minutes */
									esc_html__( '%d min read', 'tclas' ),
									(int) $read_mins
								); ?>
							</span>
						</li>
						<?php endforeach; ?>
					</ol>

				</div><!-- .tclas-issue-body -->

			</div><!-- .tclas-issue-group -->

			<?php endforeach; ?>

		<?php endif; ?>


	</div><!-- .container-tclas -->
</section>

<?php get_footer(); ?>
