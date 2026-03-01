<?php
/**
 * Search results template
 *
 * @package TCLAS
 */

get_header();
?>

<div class="tclas-page-header tclas-page-header--ardoise">
	<div class="container-tclas">
		<span class="tclas-eyebrow tclas-eyebrow--light"><?php esc_html_e( 'Search', 'tclas' ); ?></span>
		<h1 class="tclas-page-header__title">
			<?php
			if ( get_search_query() ) {
				/* translators: %s: search query */
				printf( esc_html__( 'Results for "%s"', 'tclas' ), get_search_query() );
			} else {
				esc_html_e( 'Search', 'tclas' );
			}
			?>
		</h1>
	</div>
</div>

<section class="tclas-search tclas-section">
	<div class="container-tclas">

		<!-- Search form -->
		<div class="tclas-search__form mb-5">
			<?php get_search_form(); ?>
		</div>

		<?php if ( have_posts() ) : ?>

			<p class="tclas-search__count">
				<?php
				/* translators: %d: number of results */
				printf( esc_html( _n( '%d result', '%d results', (int) $wp_query->found_posts, 'tclas' ) ), (int) $wp_query->found_posts );
				?>
			</p>

			<div class="tclas-grid-3">
				<?php while ( have_posts() ) : the_post(); ?>
					<?php get_template_part( 'template-parts/content/card', 'post' ); ?>
				<?php endwhile; ?>
			</div>

			<nav class="tclas-pagination" aria-label="<?php esc_attr_e( 'Search results navigation', 'tclas' ); ?>">
				<?php
				the_posts_pagination( [
					'mid_size'  => 2,
					'prev_text' => '&larr; ' . __( 'Previous', 'tclas' ),
					'next_text' => __( 'Next', 'tclas' ) . ' &rarr;',
				] );
				?>
			</nav>

		<?php else : ?>

			<div class="tclas-empty-state">
				<p>
					<?php
					if ( get_search_query() ) {
						/* translators: %s: search query */
						printf( esc_html__( 'Nothing matched "%s". Try a different search.', 'tclas' ), get_search_query() );
					} else {
						esc_html_e( 'Enter a term above to search the site.', 'tclas' );
					}
					?>
				</p>
			</div>

		<?php endif; ?>
	</div>
</section>

<?php get_footer(); ?>
