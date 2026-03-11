<?php
/**
 * Single post template
 *
 * @package TCLAS
 */

get_header();

while ( have_posts() ) :
	the_post();
	$minutes = tclas_reading_time();
	$hide_byline = get_post_meta( get_the_ID(), 'tclas_hide_byline', true );

	// ── Department (newsletter topic) ─────────────────────────────────────
	// Exclude the structural 'main-story' term; take the first real topic.
	$dept_terms = get_the_terms( get_the_ID(), 'tclas_department' );
	$dept       = null;
	if ( $dept_terms && ! is_wp_error( $dept_terms ) ) {
		foreach ( $dept_terms as $term ) {
			if ( 'main-story' !== $term->slug ) {
				$dept = $term;
				break;
			}
		}
	}

	// ── Newsletter issue ───────────────────────────────────────────────────
	$issue_date  = get_post_meta( get_the_ID(), 'tclas_issue_date', true );  // YYYY-MM
	$issue_title = get_post_meta( get_the_ID(), 'tclas_issue_title', true ); // human-readable
	$issue_link  = $issue_date ? home_url( '/newsletter/issue/' . $issue_date . '/' ) : '';
	if ( ! $issue_title && $issue_date ) {
		$ts          = strtotime( $issue_date . '-01' );
		$issue_title = $ts ? date_i18n( 'F Y', $ts ) : $issue_date;
	}

	// ── Featured image caption ─────────────────────────────────────────────
	$thumb_id = get_post_thumbnail_id();
	$caption  = $thumb_id ? wp_get_attachment_caption( $thumb_id ) : '';
?>

<article class="tclas-hero tclas-hero--page bg-ardoise" aria-label="<?php esc_attr_e( 'Article header', 'tclas' ); ?>">
	<div class="container-tclas">
		<div class="tclas-hero__content">

			<?php if ( $dept || $issue_link ) : ?>
			<div class="tclas-article-eyebrow">
				<?php if ( $dept ) : ?>
					<a href="<?php echo esc_url( get_term_link( $dept ) ); ?>" class="tclas-article-eyebrow__dept">
						<strong><?php echo esc_html( $dept->name ); ?></strong><?php if ( $dept->description ) : ?><span class="tclas-article-eyebrow__en"> / <?php echo esc_html( $dept->description ); ?></span><?php endif; ?>
					</a>
				<?php endif; ?>
				<?php if ( $dept && $issue_link ) : ?>
					<span class="tclas-article-eyebrow__sep" aria-hidden="true">&middot;</span>
				<?php endif; ?>
				<?php if ( $issue_link ) : ?>
					<a href="<?php echo esc_url( $issue_link ); ?>" class="tclas-article-eyebrow__issue">
						<?php echo esc_html( $issue_title ); ?>
					</a>
				<?php endif; ?>
			</div>
			<?php endif; ?>

			<h1 class="tclas-hero__title"><?php the_title(); ?></h1>

		<?php if ( ! $hide_byline ) : ?>
			<p class="tclas-hero__subtitle">
				<?php
				printf(
					/* translators: 1: author name, 2: published date, 3: reading time in minutes */
					esc_html__( 'By %1$s · Posted %2$s · %3$s minute read', 'tclas' ),
					esc_html( get_the_author() ),
					esc_html( get_the_date() ),
					(int) $minutes
				);
				?>
			</p>
		<?php endif; ?>

		</div>
	</div>
</article>

<?php if ( has_post_thumbnail() ) : ?>
<figure class="tclas-article-hero-image">
	<?php the_post_thumbnail( 'full', [ 'alt' => esc_attr( get_the_title() ) ] ); ?>
	<?php if ( $caption ) : ?>
		<figcaption class="tclas-article-hero-image__caption"><?php echo esc_html( $caption ); ?></figcaption>
	<?php endif; ?>
</figure>
<?php endif; ?>

<section class="tclas-section bg-white">
	<div class="container-tclas container--narrow">
		<div class="entry-content">
			<?php the_content(); ?>
		</div>

		<?php
		// Related posts from the same issue — cached per post for 6 hours.
		// Falls back to an empty array when no issue date is set.
		$cache_key = 'tclas_related_' . get_the_ID();
		$related   = get_transient( $cache_key );
		if ( false === $related ) {
			$related = [];
			if ( $issue_date ) {
				$related = get_posts( [
					'posts_per_page'      => 3,
					'post__not_in'        => [ get_the_ID() ],
					'orderby'             => 'rand',
					'ignore_sticky_posts' => true,
					'meta_query'          => [ [
						'key'   => 'tclas_issue_date',
						'value' => $issue_date,
					] ],
				] );
			}
			set_transient( $cache_key, $related, 6 * HOUR_IN_SECONDS );
		}
		if ( $related ) :
		?>
			<hr class="tclas-single__divider">
			<h2 class="tclas-single__related-heading"><?php esc_html_e( 'More from this issue', 'tclas' ); ?></h2>
			<div class="tclas-grid-3">
				<?php foreach ( $related as $post ) : setup_postdata( $post ); ?>
					<?php get_template_part( 'template-parts/content/card', 'post' ); ?>
				<?php endforeach; wp_reset_postdata(); ?>
			</div>
		<?php endif; ?>

	</div>
</section>

<?php
endwhile;
get_footer();
?>
