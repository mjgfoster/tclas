<?php
/**
 * Template Name: Luxembourg in America map
 *
 * Public map of Luxembourgish places in the United States — towns founded by
 * Luxembourgers, places with deep Luxembourgish roots, and Luxembourg
 * namesakes. Page content renders as the intro above the map; the places
 * themselves are `tclas_place` posts.
 *
 * Lives at /msp-lux/places/ under the MSP + LUX section (tclas_place singles
 * nest under it at /msp-lux/places/{slug}/).
 *
 * @package TCLAS
 */

get_header();
?>

<div class="tclas-page-header">
	<div class="container-tclas">
		<?php tclas_breadcrumb(); ?>
		<h1 class="tclas-page-header__title"><?php the_title(); ?></h1>
	</div>
</div>

<section class="tclas-section">
	<div class="container-tclas">

		<?php while ( have_posts() ) : the_post(); ?>
			<?php if ( get_the_content() ) : ?>
				<div class="tclas-member-map-intro">
					<?php the_content(); ?>
				</div>
			<?php endif; ?>
		<?php endwhile; ?>

		<?php echo do_shortcode( '[tclas_places_map]' ); ?>

	</div>
</section>

<?php get_footer(); ?>
