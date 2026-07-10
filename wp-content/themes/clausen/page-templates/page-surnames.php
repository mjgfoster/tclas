<?php
/**
 * Template Name: Surname finder
 *
 * Public "Ass Ären Numm lëtzebuergesch?" surname explorer. Page content
 * renders as the intro; the finder (instant search + crawlable A–Z index)
 * comes from [tclas_surname_finder]. Surname entries are `tclas_surname`
 * posts, nested at /ancestry/surnames/{slug}/.
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
	<div class="container-tclas container--medium">

		<?php while ( have_posts() ) : the_post(); ?>
			<?php if ( get_the_content() ) : ?>
				<div class="tclas-member-map-intro">
					<?php the_content(); ?>
				</div>
			<?php endif; ?>
		<?php endwhile; ?>

		<?php echo do_shortcode( '[tclas_surname_finder]' ); ?>

	</div>
</section>

<?php get_footer(); ?>
