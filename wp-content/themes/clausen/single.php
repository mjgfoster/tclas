<?php
/**
 * Single post template
 *
 * Newsletter articles (those with tclas_issue_date) get an editorial layout:
 *   – Full-bleed photo hero with bottom gradient and title overlaid
 *   – Left sidebar (newsletter navigation) + readable-width prose column
 *   – "More from this issue" using newsletter-aware department cards
 *
 * All other posts use the standard dark-ardoise page header layout.
 *
 * @package TCLAS
 */

get_header();

while ( have_posts() ) :
	the_post();

	// ── Department (newsletter topic) ──────────────────────────────────────
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

	// ── Newsletter issue ────────────────────────────────────────────────────
	$issue_date  = get_post_meta( get_the_ID(), 'tclas_issue_date', true );
	$issue_title = get_post_meta( get_the_ID(), 'tclas_issue_title', true );
	$issue_link  = $issue_date ? home_url( '/newsletter/issue/' . $issue_date . '/' ) : '';
	if ( ! $issue_title && $issue_date ) {
		$ts          = strtotime( $issue_date . '-01' );
		$issue_title = $ts ? date_i18n( 'F Y', $ts ) : $issue_date;
	}

	$is_newsletter  = ! empty( $issue_date );
	$hide_byline    = get_post_meta( get_the_ID(), 'tclas_hide_byline', true );
	$custom_byline  = get_post_meta( get_the_ID(), 'tclas_byline', true );
	$author_name    = $custom_byline ? $custom_byline : get_the_author();
	$is_members_only = (bool) get_post_meta( get_the_ID(), '_tclas_members_only', true );
	$can_read        = ! $is_members_only || tclas_is_member();

	// ── Featured image caption ──────────────────────────────────────────────
	$thumb_id = get_post_thumbnail_id();
	$caption  = $thumb_id ? wp_get_attachment_caption( $thumb_id ) : '';

	if ( $is_newsletter ) :
	// ════════════════════════════════════════════════════════════════════════
	// NEWSLETTER ARTICLE LAYOUT
	// ════════════════════════════════════════════════════════════════════════
?>

<!-- ── Article body ────────────────────────────────────────────────────────── -->
<section class="tclas-section bg-white">
	<div class="container-tclas container--medium">
		<article class="tclas-article-body">

				<!-- Article header -->
				<header class="tclas-article-body__header">
					<?php if ( $dept || $issue_link ) : ?>
					<div class="tclas-article-body__eyebrow">
						<?php if ( $dept ) : ?>
							<a href="<?php echo esc_url( get_term_link( $dept ) ); ?>" class="tclas-article-body__dept">
								<span lang="lb"><?php echo esc_html( $dept->name ); ?></span><?php if ( $dept->description ) : ?><span class="tclas-article-body__dept-en"><?php echo esc_html( $dept->description ); ?></span><?php endif; ?>
							</a>
						<?php endif; ?>
						<?php if ( $dept && $issue_link ) : ?>
							<span class="tclas-article-body__sep" aria-hidden="true">&middot;</span>
						<?php endif; ?>
						<?php if ( $issue_link ) : ?>
							<a href="<?php echo esc_url( $issue_link ); ?>" class="tclas-article-body__issue">
								<?php echo esc_html( $issue_title ); ?>
							</a>
						<?php endif; ?>
					</div>
					<?php endif; ?>
					<?php tclas_members_only_badge( get_the_ID() ); ?>

					<h1 class="tclas-article-body__title"><?php the_title(); ?></h1>
				</header>

				<!-- Featured image (if set) -->
				<?php if ( has_post_thumbnail() ) : ?>
				<figure class="tclas-article-body__image">
					<?php the_post_thumbnail( 'large', [ 'alt' => esc_attr( get_the_title() ) ] ); ?>
					<?php if ( $caption ) : ?>
					<figcaption class="tclas-article-body__caption"><?php echo esc_html( $caption ); ?></figcaption>
					<?php endif; ?>
				</figure>
				<?php endif; ?>

				<?php if ( $can_read ) : ?>
				<div class="entry-content">
					<?php the_content(); ?>
				</div>
				<?php else : ?>
				<div class="entry-content">
					<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 50, '&hellip;' ) ); ?></p>
				</div>
				<aside class="tclas-member-gate" aria-label="<?php esc_attr_e( 'Members-only content', 'tclas' ); ?>">
					<div class="tclas-member-gate__icon" aria-hidden="true">
						<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
					</div>
					<h2 class="tclas-member-gate__title"><?php esc_html_e( 'This article is for TCLAS members', 'tclas' ); ?></h2>
					<p class="tclas-member-gate__desc"><?php esc_html_e( 'Join the Twin Cities Luxembourg American Society to read this and other members-only content, access the member directory, and connect with your Luxembourg heritage.', 'tclas' ); ?></p>
					<div class="tclas-member-gate__actions">
						<a href="<?php echo esc_url( home_url( '/join/' ) ); ?>" class="btn btn-primary"><?php esc_html_e( 'Join TCLAS', 'tclas' ); ?></a>
						<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="btn btn-secondary"><?php esc_html_e( 'Member log in', 'tclas' ); ?></a>
					</div>
				</aside>
				<?php endif; ?>

				<!-- More from this issue ──────────────────────────────── -->
				<?php
				$cache_key = 'tclas_related_' . get_the_ID();
				$related   = get_transient( $cache_key );
				if ( false === $related ) {
					$related = [];
					if ( $issue_date ) {
						$related = get_posts( [
							'posts_per_page'      => 4,
							'post__not_in'        => [ get_the_ID() ],
							'orderby'             => 'meta_value_num',
							'meta_key'            => 'tclas_issue_order',
							'order'               => 'ASC',
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
				<div class="tclas-article-related">
					<h2 class="tclas-article-related__heading">
						<?php esc_html_e( 'More from this issue', 'tclas' ); ?>
					</h2>
					<div class="tclas-ie-dept-grid">
						<?php foreach ( $related as $_rp ) :
							$_r_terms = get_the_terms( $_rp->ID, 'tclas_department' );
							$_r_dept  = null;
							if ( $_r_terms && ! is_wp_error( $_r_terms ) ) {
								foreach ( $_r_terms as $_rt ) {
									if ( 'main-story' !== $_rt->slug ) { $_r_dept = $_rt; break; }
								}
							}
							$_r_ex = has_excerpt( $_rp->ID )
								? wp_trim_words( get_the_excerpt( $_rp ), 20, '&hellip;' )
								: wp_trim_words( $_rp->post_content, 20, '&hellip;' );
						?>
						<article class="tclas-ie-dept-card">
							<a href="<?php echo esc_url( get_permalink( $_rp->ID ) ); ?>" class="tclas-ie-dept-card__link">
								<?php if ( $_r_dept ) : ?>
								<span class="tclas-ie-dept-label">
									<span lang="lb"><?php echo esc_html( $_r_dept->name ); ?></span><?php if ( $_r_dept->description ) : ?><span class="tclas-ie-dept-label__en"><?php echo esc_html( $_r_dept->description ); ?></span><?php endif; ?>
								</span>
								<?php endif; ?>
								<h3 class="tclas-ie-dept-card__title"><?php echo esc_html( get_the_title( $_rp ) ); ?></h3>
								<?php if ( $_r_ex ) : ?>
								<p class="tclas-ie-dept-card__excerpt"><?php echo esc_html( $_r_ex ); ?></p>
								<?php endif; ?>
							</a>
						</article>
						<?php endforeach; ?>
					</div>
				</div>
				<?php endif; ?>

			</article><!-- .tclas-article-body -->

	</div><!-- .container-tclas -->
</section>

<?php
	else :
	// ════════════════════════════════════════════════════════════════════════
	// STANDARD POST LAYOUT (non-newsletter)
	// ════════════════════════════════════════════════════════════════════════
	$minutes = tclas_reading_time();
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
				<?php printf(
					/* translators: 1: author name, 2: published date, 3: reading time in minutes */
					esc_html__( 'By %1$s · Posted %2$s · %3$s minute read', 'tclas' ),
					esc_html( $author_name ),
					esc_html( get_the_date() ),
					(int) $minutes
				); ?>
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
	<div class="container-tclas container--medium">
		<div class="entry-content">
			<?php the_content(); ?>
		</div>

		<?php
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
	endif; // end if $is_newsletter

endwhile;
get_footer();
?>
