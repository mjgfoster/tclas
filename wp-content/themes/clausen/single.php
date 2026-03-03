<?php
/**
 * Single post template
 *
 * @package TCLAS
 */

get_header();

while ( have_posts() ) :
	the_post();
	$cats    = get_the_category();
	$cat     = $cats ? $cats[0] : null;
	$minutes = tclas_reading_time();
?>

<article class="tclas-hero tclas-hero--page bg-ardoise" aria-label="<?php esc_attr_e( 'Article header', 'tclas' ); ?>">
	<?php if ( has_post_thumbnail() ) : ?>
		<div class="tclas-hero__bg">
			<?php the_post_thumbnail( 'tclas-hero', [ 'alt' => '', 'loading' => 'eager' ] ); ?>
		</div>
		<div class="tclas-hero__overlay" aria-hidden="true"></div>
	<?php endif; ?>
	<div class="container-tclas">
		<div class="tclas-hero__content">
			<?php if ( $cat ) : ?>
				<a href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>" class="tclas-hero__eyebrow">
					<?php echo esc_html( $cat->name ); ?>
				</a>
			<?php endif; ?>
			<h1 class="tclas-hero__title"><?php the_title(); ?></h1>
			<p class="tclas-hero__subtitle">
				<?php
				printf(
					/* translators: 1: author, 2: date, 3: reading time */
					esc_html__( 'By %1$s · %2$s · %3$s min read', 'tclas' ),
					esc_html( get_the_author() ),
					esc_html( get_the_date() ),
					(int) $minutes
				);
				?>
			</p>
		</div>
	</div>
</article>

<section class="tclas-section bg-white">
	<div class="container-tclas container--narrow">
		<div class="entry-content">
			<?php the_content(); ?>
		</div>

		<?php
		// Related posts — cached per post for 6 hours
		$cache_key = 'tclas_related_' . get_the_ID();
		$related   = get_transient( $cache_key );
		if ( false === $related ) {
			$related = get_posts( [
				'posts_per_page'      => 3,
				'category__in'        => wp_get_post_categories( get_the_ID() ),
				'post__not_in'        => [ get_the_ID() ],
				'orderby'             => 'rand',
				'ignore_sticky_posts' => true,
			] );
			set_transient( $cache_key, $related, 6 * HOUR_IN_SECONDS );
		}
		if ( $related ) :
		?>
			<hr class="tclas-single__divider">
			<h2 class="tclas-single__related-heading"><?php esc_html_e( 'More from TCLAS', 'tclas' ); ?></h2>
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
