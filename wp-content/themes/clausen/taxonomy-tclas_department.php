<?php
/**
 * Newsletter topic archive — tclas_department taxonomy
 *
 * Displays all posts tagged with a specific newsletter department/topic.
 * Styled as a TOC list matching the newsletter archive "By Topic" treatment.
 * URL structure: /newsletter/topic/{slug}/
 *
 * @package TCLAS
 */

get_header();

$term             = get_queried_object();
$term_name        = $term->name ?? '';
$term_description = $term->description ?? '';

// Query all posts for this department, newest first
$dept_posts = get_posts( [
	'post_type'      => 'post',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'tax_query'      => [ [
		'taxonomy' => 'tclas_department',
		'field'    => 'term_id',
		'terms'    => $term->term_id,
	] ],
	'orderby'        => 'date',
	'order'          => 'DESC',
] );

// Helpers
$_byline = function( WP_Post $p ): string {
	if ( get_post_meta( $p->ID, 'tclas_hide_byline', true ) ) { return ''; }
	$custom = get_post_meta( $p->ID, 'tclas_byline', true );
	return $custom ? $custom : get_the_author_meta( 'display_name', $p->post_author );
};

$_excerpt = function( WP_Post $p, int $words = 25 ): string {
	return has_excerpt( $p->ID )
		? wp_trim_words( get_the_excerpt( $p ), $words, '&hellip;' )
		: wp_trim_words( $p->post_content, $words, '&hellip;' );
};
?>

<div class="tclas-page-header">
	<div class="container-tclas">
		<?php
		echo '<nav class="tclas-breadcrumb" aria-label="' . esc_attr__( 'Breadcrumb', 'tclas' ) . '">';
		echo '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'tclas' ) . '</a>';
		echo '<span class="tclas-breadcrumb__sep" aria-hidden="true">›</span>';
		echo '<a href="' . esc_url( home_url( '/newsletter/' ) ) . '">' . esc_html__( 'Newsletter', 'tclas' ) . '</a>';
		echo '<span class="tclas-breadcrumb__sep" aria-hidden="true">›</span>';
		echo '<span class="tclas-breadcrumb__current" aria-current="page">' . esc_html( $term_description ?: $term_name ) . '</span>';
		echo '</nav>';
		?>
		<h1 class="tclas-page-header__title">
			<span lang="lb"><?php echo esc_html( $term_name ); ?></span>
			<?php if ( $term_description ) : ?>
				<span class="tclas-page-header__title-en"><?php echo esc_html( $term_description ); ?></span>
			<?php endif; ?>
		</h1>
	</div>
</div>

<section class="tclas-section">
	<div class="container-tclas">

		<?php if ( ! empty( $dept_posts ) ) : ?>

		<div class="tclas-issue-toc">
			<?php foreach ( $dept_posts as $_p ) :
				$_ex        = $_excerpt( $_p, 25 );
				$_by        = $_byline( $_p );
				$_has_img   = has_post_thumbnail( $_p->ID );
				$_issue     = get_post_meta( $_p->ID, 'tclas_issue_date', true );
				$_dt        = $_issue ? DateTime::createFromFormat( 'Y-m', $_issue ) : null;
				$_issue_lbl = $_dt ? $_dt->format( 'M Y' ) : '';
			?>
			<article class="tclas-issue-toc__card<?php echo $_has_img ? '' : ' tclas-issue-toc__card--no-img'; ?>">
				<a href="<?php echo esc_url( get_permalink( $_p->ID ) ); ?>" class="tclas-issue-toc__link">

					<?php if ( $_has_img ) : ?>
					<div class="tclas-issue-toc__image">
						<?php echo get_the_post_thumbnail( $_p->ID, 'medium_large', [ 'alt' => '' ] ); ?>
					</div>
					<?php endif; ?>

					<div class="tclas-issue-toc__body">
						<?php if ( $_issue_lbl ) : ?>
						<span class="tclas-ie-dept-label"><?php echo esc_html( $_issue_lbl ); ?></span>
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

		<?php else : ?>

		<div class="tclas-empty-state">
			<p><?php esc_html_e( 'No posts found for this topic yet.', 'tclas' ); ?></p>
			<a href="<?php echo esc_url( home_url( '/newsletter/' ) ); ?>" class="btn btn-outline-ardoise">
				<?php esc_html_e( '← Back to newsletter', 'tclas' ); ?>
			</a>
		</div>

		<?php endif; ?>

	</div>
</section>

<?php get_footer(); ?>
