<?php
/**
 * Archive template for Luxembourg Stories (tclas_story CPT)
 *
 * @package TCLAS
 */

get_header();
?>

<div class="tclas-page-header tclas-page-header--orpale">
	<div class="container-tclas">
		<span class="tclas-eyebrow"><?php esc_html_e( 'Community voices', 'tclas' ); ?></span>
		<h1 class="tclas-page-header__title"><?php esc_html_e( 'Luxembourg stories', 'tclas' ); ?></h1>
		<p class="tclas-page-header__desc" style="max-width:52ch;">
			<?php esc_html_e( 'Members share the stories that brought them here — through ancestry, citizenship, travel, and sometimes, an unexpected bottle of Crémant.', 'tclas' ); ?>
		</p>
	</div>
</div>

<section class="tclas-section">
	<div class="container-tclas">

		<!-- Commune filter -->
		<?php
		$communes = get_terms( [ 'taxonomy' => 'tclas_commune', 'hide_empty' => true ] );
		if ( $communes && ! is_wp_error( $communes ) ) :
		?>
			<div class="tclas-filter-bar mb-4">
				<a href="<?php echo esc_url( get_post_type_archive_link( 'tclas_story' ) ); ?>"
				   class="tclas-filter-tag<?php echo ! is_tax( 'tclas_commune' ) ? ' tclas-filter-tag--active' : ''; ?>">
					<?php esc_html_e( 'All communes', 'tclas' ); ?>
				</a>
				<?php foreach ( $communes as $commune ) : ?>
					<a href="<?php echo esc_url( get_term_link( $commune ) ); ?>"
					   class="tclas-filter-tag<?php echo ( is_tax( 'tclas_commune' ) && get_queried_object_id() === $commune->term_id ) ? ' tclas-filter-tag--active' : ''; ?>">
						<?php echo esc_html( $commune->name ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( have_posts() ) : ?>

			<div class="tclas-grid-3">
				<?php while ( have_posts() ) : the_post(); ?>
					<?php get_template_part( 'template-parts/content/card', 'post' ); ?>
				<?php endwhile; ?>
			</div>

			<nav class="tclas-pagination" aria-label="<?php esc_attr_e( 'Stories navigation', 'tclas' ); ?>">
				<?php
				the_posts_pagination( [
					'mid_size'  => 2,
					'prev_text' => '&larr; ' . __( 'Older stories', 'tclas' ),
					'next_text' => __( 'Newer stories', 'tclas' ) . ' &rarr;',
				] );
				?>
			</nav>

		<?php else : ?>

			<div class="tclas-empty-state">
				<p><?php esc_html_e( 'No stories yet — but every member has one. Be the first to share yours.', 'tclas' ); ?></p>
			</div>

		<?php endif; ?>

	</div>
</section>

<?php get_footer(); ?>
