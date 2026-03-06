<?php
/**
 * Template Name: Ancestry
 *
 * Sections:
 *  1. Page header   — ardoise
 *  2. Intro         — white, narrow
 *  3. Steps         — or-pale, numbered research guide
 *  4. Map CTA       — ardoise, links to /map/
 *  5. Resources     — white, two-column link list
 *  6. Join bar      — gold, non-members only
 *
 * @package TCLAS
 */

get_header();
?>

<!-- ── Page header ──────────────────────────────────────────────────────── -->
<div class="tclas-page-header tclas-page-header--ardoise">
	<div class="container-tclas">
		<?php tclas_breadcrumb( '', true ); ?>
		<span class="tclas-eyebrow tclas-eyebrow--light"><?php esc_html_e( 'Luxembourg ancestry', 'tclas' ); ?></span>
		<h1 class="tclas-page-header__title"><?php esc_html_e( 'Trace your roots.', 'tclas' ); ?></h1>
	</div>
</div>

<!-- ── Intro ─────────────────────────────────────────────────────────────── -->
<section class="tclas-section tclas-ancestry-intro">
	<div class="container-tclas container--narrow">
		<?php
		$anc_lede = function_exists( 'get_field' ) ? get_field( 'anc_lede' ) : '';
		if ( $anc_lede ) {
			echo '<div class="tclas-ancestry-lede">' . wp_kses_post( $anc_lede ) . '</div>';
		} else {
		?>
		<p class="tclas-ancestry-lede">
			<?php esc_html_e( 'Tens of thousands of Luxembourgers settled in Minnesota between the 1840s and early 1900s. If your family is among them, the records are out there &mdash; and more accessible than you might think.', 'tclas' ); ?>
		</p>
		<?php } ?>
	</div>
</section>

<!-- ── Research steps ────────────────────────────────────────────────────── -->
<section class="tclas-section tclas-ancestry-steps" aria-labelledby="ancestry-steps-heading">
	<div class="container-tclas">

		<span class="tclas-eyebrow"><?php esc_html_e( 'How to get started', 'tclas' ); ?></span>
		<h2 id="ancestry-steps-heading"><?php esc_html_e( 'Research your Luxembourg roots', 'tclas' ); ?></h2>

		<ol class="tclas-ancestry-step-list" role="list">
		<?php if ( function_exists( 'have_rows' ) && have_rows( 'anc_steps' ) ) : ?>
			<?php $anc_step_n = 0; while ( have_rows( 'anc_steps' ) ) : the_row(); $anc_step_n++; ?>
			<li class="tclas-ancestry-step">
				<div class="tclas-ancestry-step__num" aria-hidden="true"><?php echo esc_html( $anc_step_n ); ?></div>
				<div class="tclas-ancestry-step__content">
					<h3><?php echo esc_html( get_sub_field( 'step_title' ) ); ?></h3>
					<div><?php echo wp_kses_post( get_sub_field( 'step_body' ) ); ?></div>
				</div>
			</li>
			<?php endwhile; ?>
		<?php else : ?>

			<li class="tclas-ancestry-step">
				<div class="tclas-ancestry-step__num" aria-hidden="true">1</div>
				<div class="tclas-ancestry-step__content">
					<h3><?php esc_html_e( 'Start with what you know', 'tclas' ); ?></h3>
					<p><?php esc_html_e( 'Gather family documents, photos, and stories before you search online. Names, approximate dates, and any mention of a Luxembourg town or region are the most valuable starting points. Ask older relatives &mdash; even vague details help narrow the search.', 'tclas' ); ?></p>
				</div>
			</li>

			<li class="tclas-ancestry-step">
				<div class="tclas-ancestry-step__num" aria-hidden="true">2</div>
				<div class="tclas-ancestry-step__content">
					<h3><?php esc_html_e( 'Search U.S. records', 'tclas' ); ?></h3>
					<p><?php esc_html_e( 'Immigration manifests, naturalization papers, and census records often name the exact Luxembourg commune your ancestors came from. FamilySearch (free) and Ancestry.com are the best places to start. The Minnesota Historical Society holds state-specific records including early territorial census data.', 'tclas' ); ?></p>
				</div>
			</li>

			<li class="tclas-ancestry-step">
				<div class="tclas-ancestry-step__num" aria-hidden="true">3</div>
				<div class="tclas-ancestry-step__content">
					<h3><?php esc_html_e( 'Find the commune', 'tclas' ); ?></h3>
					<p><?php esc_html_e( 'Knowing which Luxembourg commune your ancestors came from is the key that unlocks everything. Once you have it, you can search civil registration records, church registers, and military rolls. Common sources for the commune name: ship passenger lists, naturalization declarations, and obituaries.', 'tclas' ); ?></p>
				</div>
			</li>

			<li class="tclas-ancestry-step">
				<div class="tclas-ancestry-step__num" aria-hidden="true">4</div>
				<div class="tclas-ancestry-step__content">
					<h3><?php esc_html_e( 'Explore Luxembourg archives', 'tclas' ); ?></h3>
					<p><?php esc_html_e( 'Civil registration records from 1796 onward are digitized and searchable online through the Archives nationales de Luxembourg. Pre-1796 records &mdash; parish registers &mdash; are held at ANLux and partially digitized. Most records are freely accessible without an account.', 'tclas' ); ?></p>
				</div>
			</li>

			<li class="tclas-ancestry-step">
				<div class="tclas-ancestry-step__num" aria-hidden="true">5</div>
				<div class="tclas-ancestry-step__content">
					<h3><?php esc_html_e( 'Connect with the community', 'tclas' ); ?></h3>
					<p><?php esc_html_e( 'TCLAS members have traced hundreds of Luxembourg family lines across Minnesota. The ancestral commune map shows where our members&rsquo; roots lie &mdash; and often, people from the same commune find each other here. Your ancestors may have been neighbors.', 'tclas' ); ?></p>
				</div>
			</li>

		<?php endif; ?>
		</ol>
	</div>
</section>

<!-- ── Ancestral commune map ────────────────────────────────────────────── -->
<section class="tclas-ancestry-map-cta" aria-labelledby="ancestry-map-heading">
	<div class="container-tclas">
		<div class="tclas-ancestry-map-cta__inner">
			<span class="tclas-eyebrow tclas-eyebrow--accent"><?php esc_html_e( 'Community tool', 'tclas' ); ?></span>
			<h2 id="ancestry-map-heading"><?php esc_html_e( 'Where do our roots lie?', 'tclas' ); ?></h2>
			<p><?php esc_html_e( 'The TCLAS ancestral commune map shows the Luxembourg communes that our members trace their roots to. Find your commune and discover who else shares your ancestry.', 'tclas' ); ?></p>
			<?php echo do_shortcode( '[tclas_ancestor_map public="true" height="420px"]' ); ?>
			<?php if ( tclas_is_member() ) : ?>
				<a href="<?php echo esc_url( home_url( '/map/' ) ); ?>" class="btn btn-outline-light" style="margin-top:1.5rem;">
					<?php esc_html_e( 'Open full map →', 'tclas' ); ?>
				</a>
			<?php endif; ?>
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
