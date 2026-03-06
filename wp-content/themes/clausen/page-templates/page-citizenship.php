<?php
/**
 * Template Name: Citizenship page
 *
 * Combined citizenship eligibility quiz + resources page.
 * Canonical URL: /citizenship/
 * /quiz/ redirects here via inc/redirects.php.
 *
 * Post content should contain [luxembourg_eligibility_quiz] so the plugin
 * enqueues its assets via has_shortcode(). The quiz is rendered here via
 * do_shortcode() so we control placement.
 *
 * @package TCLAS
 */

get_header();
?>

<!-- ── Page header ──────────────────────────────────────────────────────── -->
<div class="tclas-page-header">
	<div class="container-tclas">
		<?php tclas_breadcrumb(); ?>
		<span class="tclas-eyebrow"><?php esc_html_e( 'Luxembourg citizenship', 'tclas' ); ?></span>
		<h1 class="tclas-page-header__title"><?php esc_html_e( 'Do you qualify for Luxembourg citizenship?', 'tclas' ); ?></h1>
	</div>
</div>

<!-- ── Introduction ─────────────────────────────────────────────────────── -->
<section class="tclas-section bg-white">
	<div class="container-tclas container--narrow">
		<?php
		$cit_lede   = function_exists( 'get_field' ) ? get_field( 'cit_lede' ) : '';
		$cit_lede_2 = function_exists( 'get_field' ) ? get_field( 'cit_lede_2' ) : '';
		?>
		<?php if ( $cit_lede ) : ?>
			<div class="tclas-citizenship-lede"><?php echo wp_kses_post( $cit_lede ); ?></div>
		<?php else : ?>
			<p class="tclas-citizenship-lede">
				<?php esc_html_e( 'Under the Law of June 8, 2017 on Luxembourgish nationality — amended and expanded in 2021 — descendants of Luxembourg citizens may be eligible to recover or obtain citizenship. The pathway is open to multiple generations of descendants, whether your ancestor emigrated in the 1880s or the 1950s.', 'tclas' ); ?>
			</p>
		<?php endif; ?>
		<?php if ( $cit_lede_2 ) : ?>
			<?php echo wp_kses_post( $cit_lede_2 ); ?>
		<?php else : ?>
			<p>
				<?php esc_html_e( 'The quiz below walks through your family history generation by generation and gives you a personalized assessment of your eligibility. It is not a legal opinion, but it reflects the published criteria as they apply to most cases. After the quiz, you\'ll find guidance on what to do next.', 'tclas' ); ?>
			</p>
		<?php endif; ?>
		<p>
			<a href="#quiz" class="btn btn-primary">
				<?php esc_html_e( 'Take the quiz ↓', 'tclas' ); ?>
			</a>
			<a href="#resources" class="btn btn-outline-ardoise">
				<?php esc_html_e( 'Skip to resources', 'tclas' ); ?>
			</a>
		</p>
	</div>
</section>

<!-- ── Eligibility quiz ──────────────────────────────────────────────────── -->
<section class="tclas-section tclas-bg-warm" id="quiz">
	<div class="container-tclas">
		<div class="tclas-quiz-intro">
			<span class="tclas-eyebrow"><?php esc_html_e( 'Eligibility quiz', 'tclas' ); ?></span>
			<h2 class="tclas-ruled"><?php esc_html_e( 'Find out in a few steps.', 'tclas' ); ?></h2>
			<p><?php esc_html_e( 'Answer a few questions about your Luxembourg ancestry to get a personalized assessment. Most people complete it in under two minutes.', 'tclas' ); ?></p>
		</div>
		<div class="tclas-quiz-wrapper">
			<?php echo do_shortcode( '[luxembourg_eligibility_quiz]' ); ?>
		</div>
	</div>
</section>

<!-- ── Resources ────────────────────────────────────────────────────────── -->
<section class="tclas-section tclas-bg-or-pale" id="resources">
	<div class="container-tclas">
		<span class="tclas-eyebrow"><?php esc_html_e( 'After the quiz', 'tclas' ); ?></span>
		<h2 class="tclas-ruled"><?php esc_html_e( 'What to do next.', 'tclas' ); ?></h2>

		<div class="tclas-grid-2 tclas-citizenship-resources">

			<!-- Next steps -->
			<div>
				<h3><?php esc_html_e( 'If you qualify — your next steps', 'tclas' ); ?></h3>
				<ol class="tclas-steps">
					<?php if ( function_exists( 'have_rows' ) && have_rows( 'cit_next_steps' ) ) : ?>
						<?php $cit_step_n = 0; while ( have_rows( 'cit_next_steps' ) ) : the_row(); $cit_step_n++; ?>
						<li class="tclas-step">
							<span class="tclas-step__num" aria-hidden="true"><?php echo esc_html( $cit_step_n ); ?></span>
							<div class="tclas-step__body">
								<strong class="tclas-step__title"><?php echo esc_html( get_sub_field( 'step_title' ) ); ?></strong>
								<div class="tclas-step__text"><?php echo wp_kses_post( get_sub_field( 'step_body' ) ); ?></div>
							</div>
						</li>
						<?php endwhile; ?>
					<?php else : ?>

						<li class="tclas-step">
							<span class="tclas-step__num" aria-hidden="true">1</span>
							<div class="tclas-step__body">
								<strong class="tclas-step__title"><?php esc_html_e( 'Gather your documentation', 'tclas' ); ?></strong>
								<p class="tclas-step__text">
									<?php esc_html_e( 'You\'ll need birth, marriage, and death records for yourself and each Luxembourg ancestor in your lineage. Your local vital records office handles U.S. records; Luxembourg\'s National Archives (ANLux) holds records from the Grand Duchy.', 'tclas' ); ?>
								</p>
							</div>
						</li>

						<li class="tclas-step">
							<span class="tclas-step__num" aria-hidden="true">2</span>
							<div class="tclas-step__body">
								<strong class="tclas-step__title"><?php esc_html_e( 'Research your ancestry', 'tclas' ); ?></strong>
								<p class="tclas-step__text">
									<?php
									printf(
										/* translators: %s: ancestral map URL */
										wp_kses( __( 'TCLAS members can use the <a href="%s">ancestral commune map</a> to find others with roots in the same communes, and connect with experienced researchers in our community forums.', 'tclas' ), [ 'a' => [ 'href' => [] ] ] ),
										esc_url( home_url( '/ancestry/' ) )
									);
									?>
								</p>
							</div>
						</li>

						<li class="tclas-step">
							<span class="tclas-step__num" aria-hidden="true">3</span>
							<div class="tclas-step__body">
								<strong class="tclas-step__title"><?php esc_html_e( 'Contact the Luxembourg consulate', 'tclas' ); ?></strong>
								<p class="tclas-step__text">
									<?php esc_html_e( 'U.S. residents apply through a Luxembourg diplomatic post. Minnesota residents are typically served by the Consulate General in Chicago, or the embassy in Washington, D.C. for some cases.', 'tclas' ); ?>
								</p>
							</div>
						</li>

						<li class="tclas-step">
							<span class="tclas-step__num" aria-hidden="true">4</span>
							<div class="tclas-step__body">
								<strong class="tclas-step__title"><?php esc_html_e( 'Submit your application', 'tclas' ); ?></strong>
								<p class="tclas-step__text">
									<?php esc_html_e( 'Applications are processed by Luxembourg\'s SCAS (Service Central d\'Assistance Sociale). Processing times vary — plan for 12 to 24 months. The consulate will guide you through required forms and notarization.', 'tclas' ); ?>
								</p>
							</div>
						</li>

					<?php endif; ?>
				</ol>
			</div>

			<!-- Official resources -->
			<div>
				<h3><?php esc_html_e( 'Official resources', 'tclas' ); ?></h3>
				<div class="tclas-doc-list">
					<?php if ( function_exists( 'have_rows' ) && have_rows( 'cit_resources' ) ) : ?>
						<?php while ( have_rows( 'cit_resources' ) ) : the_row(); ?>
						<a href="<?php echo esc_url( get_sub_field( 'res_url' ) ); ?>"
						   class="tclas-doc-item"
						   target="_blank"
						   rel="noopener noreferrer">
							<div class="tclas-doc-item__icon" aria-hidden="true"><?php echo esc_html( get_sub_field( 'res_icon' ) ); ?></div>
							<div>
								<span class="tclas-doc-item__name"><?php echo esc_html( get_sub_field( 'res_name' ) ); ?></span>
								<span class="tclas-doc-item__meta"><?php echo esc_html( get_sub_field( 'res_desc' ) ); ?></span>
							</div>
							<span class="tclas-doc-item__download" aria-hidden="true">↗</span>
						</a>
						<?php endwhile; ?>
					<?php else : ?>

						<a href="https://guichet.public.lu/en/citoyens/citoyennete/nationalite-luxembourgeoise.html"
						   class="tclas-doc-item"
						   target="_blank"
						   rel="noopener noreferrer">
							<div class="tclas-doc-item__icon" aria-hidden="true">🇱🇺</div>
							<div>
								<span class="tclas-doc-item__name"><?php esc_html_e( 'Guichet.lu — Nationality', 'tclas' ); ?></span>
								<span class="tclas-doc-item__meta"><?php esc_html_e( 'Luxembourg\'s official e-government portal — citizenship eligibility, required documents, and application process.', 'tclas' ); ?></span>
							</div>
							<span class="tclas-doc-item__download" aria-hidden="true">↗</span>
						</a>

						<a href="https://anlux.public.lu/en.html"
						   class="tclas-doc-item"
						   target="_blank"
						   rel="noopener noreferrer">
							<div class="tclas-doc-item__icon" aria-hidden="true">📚</div>
							<div>
								<span class="tclas-doc-item__name"><?php esc_html_e( 'ANLux — National Archives', 'tclas' ); ?></span>
								<span class="tclas-doc-item__meta"><?php esc_html_e( 'Luxembourg\'s National Archives hold vital records, civil registration documents, and genealogical research databases.', 'tclas' ); ?></span>
							</div>
							<span class="tclas-doc-item__download" aria-hidden="true">↗</span>
						</a>

						<a href="https://chicago.mae.lu/en.html"
						   class="tclas-doc-item"
						   target="_blank"
						   rel="noopener noreferrer">
							<div class="tclas-doc-item__icon" aria-hidden="true">🏛️</div>
							<div>
								<span class="tclas-doc-item__name"><?php esc_html_e( 'Consulate General — Chicago', 'tclas' ); ?></span>
								<span class="tclas-doc-item__meta"><?php esc_html_e( 'The consulate serving Minnesota and the Midwest. Contact them to begin the application process and confirm document requirements.', 'tclas' ); ?></span>
							</div>
							<span class="tclas-doc-item__download" aria-hidden="true">↗</span>
						</a>

						<a href="https://maee.gouvernement.lu/en/directions/consulaires/nationalite.html"
						   class="tclas-doc-item"
						   target="_blank"
						   rel="noopener noreferrer">
							<div class="tclas-doc-item__icon" aria-hidden="true">📋</div>
							<div>
								<span class="tclas-doc-item__name"><?php esc_html_e( 'Ministry of Foreign Affairs', 'tclas' ); ?></span>
								<span class="tclas-doc-item__meta"><?php esc_html_e( 'Official guidance on the recovery of nationality, including the 2021 amendments to the Law of June 8, 2017.', 'tclas' ); ?></span>
							</div>
							<span class="tclas-doc-item__download" aria-hidden="true">↗</span>
						</a>

					<?php endif; ?>
				</div>
			</div>

		</div><!-- .tclas-grid-2 -->
	</div>
</section>

<!-- ── Community CTA ─────────────────────────────────────────────────────── -->
<section class="tclas-section bg-ardoise">
	<div class="container-tclas container--narrow tclas-citizenship-cta">
		<span class="tclas-eyebrow"><?php esc_html_e( 'You\'re not alone in this', 'tclas' ); ?></span>
		<?php
		$cit_comm_heading = function_exists( 'get_field' ) ? get_field( 'cit_community_heading' ) : '';
		$cit_comm_body    = function_exists( 'get_field' ) ? get_field( 'cit_community_body' ) : '';
		?>
		<h2><?php echo esc_html( $cit_comm_heading ?: 'TCLAS members have been through it.' ); ?></h2>
		<?php if ( $cit_comm_body ) : ?>
			<?php echo wp_kses_post( $cit_comm_body ); ?>
		<?php else : ?>
		<p>
			<?php esc_html_e( 'Dozens of our members are pursuing or have completed the citizenship process. Whether you\'re at the \'just curious\' stage or deep in the paperwork — their experience is part of what membership unlocks.', 'tclas' ); ?>
		</p>
		<?php endif; ?>
		<div class="tclas-citizenship-cta__actions">
			<a href="<?php echo esc_url( home_url( '/join/' ) ); ?>" class="btn btn-primary btn-lg">
				<?php esc_html_e( 'Join TCLAS', 'tclas' ); ?>
			</a>
			<?php if ( tclas_is_member() ) : ?>
				<a href="<?php echo esc_url( home_url( '/forums/' ) ); ?>" class="btn btn-outline-light">
					<?php esc_html_e( 'Visit the forums →', 'tclas' ); ?>
				</a>
			<?php else : ?>
				<a href="<?php echo esc_url( home_url( '/about/' ) ); ?>" class="btn btn-outline-light">
					<?php esc_html_e( 'Learn about TCLAS', 'tclas' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
</section>

<?php get_footer(); ?>
