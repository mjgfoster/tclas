<?php
/**
 * Archive template — posts, categories, tags, dates
 *
 * @package TCLAS
 */

get_header();
?>

<div class="tclas-page-header tclas-page-header--ardoise">
	<div class="container-tclas">
		<span class="tclas-eyebrow tclas-eyebrow--light">
			<?php
			if ( is_category() ) {
				esc_html_e( 'Category', 'tclas' );
			} elseif ( is_tag() ) {
				esc_html_e( 'Tag', 'tclas' );
			} elseif ( is_date() ) {
				esc_html_e( 'Archive', 'tclas' );
			} elseif ( is_author() ) {
				esc_html_e( 'Author', 'tclas' );
			} else {
				esc_html_e( 'Archives', 'tclas' );
			}
			?>
		</span>
		<h1 class="tclas-page-header__title">
			<?php the_archive_title(); ?>
		</h1>
		<?php if ( get_the_archive_description() ) : ?>
			<div class="tclas-page-header__desc">
				<?php the_archive_description(); ?>
			</div>
		<?php endif; ?>
	</div>
</div>

<section class="tclas-archive tclas-section">
	<div class="container-tclas">
		<?php if ( have_posts() ) : ?>

			<div class="tclas-grid-3">
				<?php while ( have_posts() ) : the_post(); ?>
					<?php get_template_part( 'template-parts/content/card', 'post' ); ?>
				<?php endwhile; ?>
			</div>

			<nav class="tclas-pagination" aria-label="<?php esc_attr_e( 'Posts navigation', 'tclas' ); ?>">
				<?php
				the_posts_pagination( [
					'mid_size'  => 2,
					'prev_text' => '&larr; ' . __( 'Older', 'tclas' ),
					'next_text' => __( 'Newer', 'tclas' ) . ' &rarr;',
				] );
				?>
			</nav>

		<?php else : ?>

			<div class="tclas-empty-state">
				<p><?php esc_html_e( 'No posts found. Perhaps they emigrated to Luxembourg.', 'tclas' ); ?></p>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn-outline-ardoise">
					<?php esc_html_e( '← Back home', 'tclas' ); ?>
				</a>
			</div>

		<?php endif; ?>
	</div>
</section>

<?php get_footer(); ?>
