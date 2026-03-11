<?php
/**
 * Template Name: MSP+LUX Subsection
 *
 * Custom template for MSP+LUX subsection pages (Our History, The Language, Culture & Life)
 *
 * @package TCLAS
 */

get_header();

while ( have_posts() ) :
	the_post();
?>

<div class="tclas-page-header">
	<div class="container-tclas">
		<?php tclas_breadcrumb(); ?>
		<span class="tclas-eyebrow"><?php esc_html_e( 'MSP+LUX', 'tclas' ); ?></span>
		<h1 class="tclas-page-header__title"><?php the_title(); ?></h1>
	</div>
</div>

<section class="tclas-section">
	<div class="container-tclas container--narrow">
		<div class="entry-content">
			<?php the_content(); ?>
		</div>
	</div>
</section>

<?php
endwhile;

get_footer();
