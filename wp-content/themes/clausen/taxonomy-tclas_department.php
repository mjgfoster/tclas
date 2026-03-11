<?php
/**
 * Newsletter topic archive — tclas_department taxonomy
 *
 * Displays all posts tagged with a specific newsletter topic.
 * URL structure: /newsletter/topic/{slug}/
 *
 * @package TCLAS
 */

get_header();

$term = get_queried_object();
$term_name = $term->name ?? '';
$term_description = $term->description ?? '';
?>

<div class="tclas-page-header">
	<div class="container-tclas">
		<?php
		// Breadcrumbs: Home › Newsletter › Topics › Topic Name
		echo '<nav class="tclas-breadcrumb" aria-label="' . esc_attr__( 'Breadcrumb', 'tclas' ) . '">';
		echo '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'tclas' ) . '</a>';
		echo ' › <a href="' . esc_url( home_url( '/newsletter/' ) ) . '">' . esc_html__( 'Newsletter', 'tclas' ) . '</a>';
		echo ' › <a href="' . esc_url( home_url( '/newsletter/' ) ) . '#nl-topics">' . esc_html__( 'Topics', 'tclas' ) . '</a>';
		echo ' › <span>' . esc_html( $term_name ) . '</span>';
		echo '</nav>';
		?>
		<span class="tclas-eyebrow"><?php esc_html_e( 'The Loon & The Lion', 'tclas' ); ?></span>
		<h1 class="tclas-page-header__title">
			<span lang="lb"><?php echo esc_html( $term_name ); ?></span>
			<?php if ( $term_description ) : ?>
				<span class="tclas-page-header__title-en"><?php echo esc_html( $term_description ); ?></span>
			<?php endif; ?>
		</h1>
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
				<p><?php esc_html_e( 'No posts found for this topic yet.', 'tclas' ); ?></p>
				<a href="<?php echo esc_url( home_url( '/newsletter/' ) ); ?>" class="btn btn-outline-ardoise">
					<?php esc_html_e( '← Back to newsletter', 'tclas' ); ?>
				</a>
			</div>

		<?php endif; ?>
	</div>
</section>

<?php get_footer(); ?>
