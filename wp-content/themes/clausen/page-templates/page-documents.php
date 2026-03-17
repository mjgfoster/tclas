<?php
/**
 * Template Name: Documents
 *
 * Members-only documents and resources page.
 * Lives at /member-hub/documents/.
 *
 * @package TCLAS
 */

get_header();
?>

<div class="tclas-page-header">
	<div class="container-tclas">
		<?php tclas_breadcrumb(); ?>
		<span class="tclas-eyebrow"><?php esc_html_e( 'Member hub', 'tclas' ); ?></span>
		<h1 class="tclas-page-header__title"><?php the_title(); ?></h1>
	</div>
</div>

<section class="tclas-section">
	<div class="container-tclas container--medium">
		<?php
		if ( have_posts() ) {
			while ( have_posts() ) {
				the_post();
				the_content();
			}
		}
		?>
	</div>
</section>

<?php get_footer(); ?>
