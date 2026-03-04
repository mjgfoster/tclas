<?php
/**
 * Standard page template
 *
 * @package TCLAS
 */

get_header();

while ( have_posts() ) :
	the_post();
?>

<div class="tclas-page-header">
	<div class="container-tclas">
		<?php
		$ancestors = get_ancestors( get_the_ID(), 'page' );
		if ( $ancestors ) :
			$parent = get_post( array_pop( $ancestors ) );
		?>
			<a href="<?php echo esc_url( get_permalink( $parent->ID ) ); ?>" class="tclas-eyebrow" style="text-decoration:none;">
				&larr; <?php echo esc_html( get_the_title( $parent->ID ) ); ?>
			</a>
		<?php endif; ?>
		<h1 class="tclas-page-header__title"><?php the_title(); ?></h1>
	</div>
</div>

<section class="tclas-section bg-white">
	<div class="container-tclas container--narrow">
		<?php
		// Breadcrumb via AIOSEO or manual
		if ( function_exists( 'aioseo_breadcrumbs' ) ) {
			aioseo_breadcrumbs();
		}
		?>
		<div class="entry-content">
			<?php the_content(); ?>
		</div>
	</div>
</section>

<?php
endwhile;
get_footer();
?>
