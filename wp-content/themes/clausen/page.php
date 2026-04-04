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
		<?php tclas_breadcrumb(); ?>
		<h1 class="tclas-page-header__title"><?php the_title(); ?></h1>
	</div>
</div>

<section class="tclas-section bg-white">
	<div class="container-tclas container--medium">
		<div class="entry-content">
			<?php the_content(); ?>
		</div>
	</div>
</section>

<?php
endwhile;
get_footer();
?>
