<?php
/**
 * Blog index / loop fallback
 *
 * @package TCLAS
 */

get_header();
?>

<div class="tclas-page-header">
	<div class="container-tclas">
		<span class="tclas-eyebrow"><?php esc_html_e( 'From the community', 'tclas' ); ?></span>
		<h1 class="tclas-page-header__title"><?php esc_html_e( 'News & stories', 'tclas' ); ?></h1>
	</div>
</div>

<section class="tclas-section bg-off-white">
	<div class="container-tclas">
		<?php if ( have_posts() ) : ?>
			<div class="tclas-grid-3">
				<?php while ( have_posts() ) : the_post(); ?>
					<?php get_template_part( 'template-parts/content/card', 'post' ); ?>
				<?php endwhile; ?>
			</div>
			<nav class="tclas-pagination" aria-label="<?php esc_attr_e( 'Pagination', 'tclas' ); ?>">
				<?php
				echo wp_kses_post( paginate_links( [
					'type'      => 'list',
					'prev_text' => '&larr;',
					'next_text' => '&rarr;',
				] ) );
				?>
			</nav>
		<?php else : ?>
			<p><?php esc_html_e( 'No posts found.', 'tclas' ); ?></p>
		<?php endif; ?>
	</div>
</section>

<?php get_footer(); ?>
