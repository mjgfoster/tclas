<?php
/**
 * Template Name: Ancestry
 *
 * Sections:
 *  1. Page header   — white, simple
 *  2. Main          — two-column grid (intro left, map right)
 *  3. Resources     — two-column link list
 *  4. Join bar      — gold, non-members only
 *
 * @package TCLAS
 */

// Members go straight to the full map (skip the marketing page).
if ( function_exists( 'tclas_is_member' ) && tclas_is_member() && empty( $_GET['preview'] ) ) {
	wp_safe_redirect( home_url( '/member-hub/ancestral-map/' ), 302 );
	exit;
}

get_header();
?>

<!-- ── Page header ──────────────────────────────────────────────────────── -->
<div class="tclas-page-header">
	<div class="container-tclas">
		<?php tclas_breadcrumb(); ?>
		<h1 class="tclas-page-header__title"><?php the_title(); ?></h1>
	</div>
</div>

<!-- ── Main: intro + map ───────────────────────────────────────────────── -->
<section class="tclas-section tclas-ancestry-main">
	<div class="container-tclas">
		<div class="tclas-ancestry-grid">

			<!-- LEFT: Intro + context -->
			<div class="tclas-ancestry-grid__content">
				<?php
				$anc_lede = function_exists( 'get_field' ) ? get_field( 'anc_lede' ) : '';
				if ( $anc_lede ) {
					echo wp_kses_post( $anc_lede );
				} else {
				?>
				<p>
					<?php esc_html_e( 'Tens of thousands of Luxembourgers settled in Minnesota between the 1840s and early 1900s. If your family is among them, the records are out there — and more accessible than you might think.', 'tclas' ); ?>
				</p>
				<p>
					<?php esc_html_e( 'The ancestral commune map shows the Luxembourg communes that TCLAS members trace their roots to. Find your commune and discover who else shares your ancestry.', 'tclas' ); ?>
				</p>
				<?php } ?>
				<?php if ( tclas_is_member() ) : ?>
					<a href="<?php echo esc_url( home_url( '/member-hub/ancestral-map/' ) ); ?>" class="btn btn-outline-ardoise">
						<?php esc_html_e( 'Open full map →', 'tclas' ); ?>
					</a>
				<?php endif; ?>
			</div>

			<!-- RIGHT: Map -->
			<div class="tclas-ancestry-grid__map">
				<?php echo do_shortcode( '[tclas_ancestor_map public="true"]' ); ?>
			</div>

		</div>
	</div>
</section>

<!-- ── Resources ─────────────────────────────────────────────────────────── -->
<section class="tclas-section" aria-labelledby="ancestry-resources-heading">
	<div class="container-tclas">

		<span class="tclas-eyebrow"><?php esc_html_e( 'Dig deeper', 'tclas' ); ?></span>
		<h2 id="ancestry-resources-heading"><?php esc_html_e( 'Research resources', 'tclas' ); ?></h2>

		<div class="tclas-ancestry-resources">

			<div class="tclas-ancestry-resources__col">
				<h3><?php esc_html_e( 'Luxembourg archives', 'tclas' ); ?></h3>
				<ul class="tclas-msp-resources__list">
				<?php if ( function_exists( 'have_rows' ) && have_rows( 'anc_resources_lux' ) ) : ?>
					<?php while ( have_rows( 'anc_resources_lux' ) ) : the_row(); ?>
					<li><a href="<?php echo esc_url( get_sub_field( 'res_url' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( get_sub_field( 'res_label' ) ); ?></a></li>
					<?php endwhile; ?>
				<?php else : ?>
					<li><a href="https://anlux.public.lu/fr/rechercher/genealogie.html" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Archives nationales de Luxembourg (ANLux)', 'tclas' ); ?></a></li>
					<li><a href="https://data.matricula-online.eu/en/LU/luxemburg/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Matricula — parish records online', 'tclas' ); ?></a></li>
					<li><a href="https://luxembourg.public.lu/en/society-and-culture/population/genealogy.html" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Luxembourg.public.lu — government genealogy guide', 'tclas' ); ?></a></li>
					<li><a href="https://www.luxroots.org/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'LuxRoots — genealogy community', 'tclas' ); ?></a></li>
				<?php endif; ?>
				</ul>
			</div>

			<div class="tclas-ancestry-resources__col">
				<h3><?php esc_html_e( 'U.S. &amp; Minnesota', 'tclas' ); ?></h3>
				<ul class="tclas-msp-resources__list">
				<?php if ( function_exists( 'have_rows' ) && have_rows( 'anc_resources_us' ) ) : ?>
					<?php while ( have_rows( 'anc_resources_us' ) ) : the_row(); ?>
					<li><a href="<?php echo esc_url( get_sub_field( 'res_url' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( get_sub_field( 'res_label' ) ); ?></a></li>
					<?php endwhile; ?>
				<?php else : ?>
					<li><a href="https://www.familysearch.org/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'FamilySearch — free records database', 'tclas' ); ?></a></li>
					<li><a href="https://www.ancestry.com/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Ancestry.com — immigration & census records', 'tclas' ); ?></a></li>
					<li><a href="https://www.mnhs.org/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Minnesota Historical Society — state records', 'tclas' ); ?></a></li>
					<li><a href="https://www.exploreminnesota.com/profile/rollingstone-luxembourg-heritage-museum/2655" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Luxembourg Heritage Museum, Rollingstone', 'tclas' ); ?></a></li>
				<?php endif; ?>
				</ul>
			</div>

		</div>
	</div>
</section>

<!-- ── Join bar (non-members only) ──────────────────────────────────────── -->
<?php if ( ! tclas_is_member() ) : ?>
<section class="tclas-join-bar">
	<div class="container-tclas">
		<h2><?php esc_html_e( 'Add your roots to the map', 'tclas' ); ?></h2>
		<div class="tclas-join-bar__actions">
			<a href="<?php echo esc_url( home_url( '/join/' ) ); ?>" class="btn btn-secondary btn-lg">
				<?php esc_html_e( 'Become a member', 'tclas' ); ?>
			</a>
			<a href="<?php echo esc_url( wp_login_url() ); ?>" class="btn btn-outline-ardoise">
				<?php esc_html_e( 'Member log in', 'tclas' ); ?>
			</a>
		</div>
	</div>
</section>
<?php endif; ?>

<?php get_footer(); ?>
