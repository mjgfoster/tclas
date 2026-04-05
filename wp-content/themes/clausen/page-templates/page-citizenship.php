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
		<h1 class="tclas-page-header__title"><?php the_title(); ?></h1>
	</div>
</div>

<!-- ── Introduction ─────────────────────────────────────────────────────── -->
<section class="tclas-section bg-white">
	<div class="container-tclas container--medium">
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

<!-- ── How it works (Accordion FAQs) + Quiz ────────────────────────────────── -->
<section class="tclas-section bg-white">
	<div class="container-tclas">
		<div class="tclas-citizenship-faq-quiz-grid">
			<!-- Left: FAQ Section -->
			<div class="tclas-citizenship-faq-col">
				<span class="tclas-eyebrow"><?php esc_html_e( 'Before you start', 'tclas' ); ?></span>
				<h2 class="tclas-ruled"><?php esc_html_e( 'Common questions about the rules.', 'tclas' ); ?></h2>

				<div class="tclas-faq-accordion">

			<!-- FAQ Item 1: The 1969 Rule -->
			<div class="tclas-faq-item">
				<button class="tclas-faq-header" aria-expanded="false">
					<i class="bi bi-calendar-event tclas-faq-icon" aria-hidden="true"></i>
					<span class="tclas-faq-question">What's the 1969 rule?</span>
					<span class="tclas-faq-toggle" aria-hidden="true">+</span>
				</button>
				<div class="tclas-faq-content">
					<div class="tclas-faq-body">
						<p><strong>In 1969, Luxembourg law changed.</strong> Before that year, mothers could not pass citizenship to their children. After 1969, they could.</p>

						<div class="tclas-faq-demo">
							<div class="tclas-faq-demo-row">
								<div class="tclas-faq-demo-col">
									<h4><i class="bi bi-check-lg" aria-hidden="true"></i> Male Line</h4>
									<div class="tclas-faq-demo-box">
										<div class="tclas-faq-demo-gen"><i class="bi bi-person-fill" aria-hidden="true"></i> Grandfather <span class="tclas-faq-demo-year">b. 1920</span></div>
										<div class="tclas-faq-demo-arrow"><i class="bi bi-arrow-down" aria-hidden="true"></i></div>
										<div class="tclas-faq-demo-gen"><i class="bi bi-person-fill" aria-hidden="true"></i> Father <span class="tclas-faq-demo-year">b. 1950</span></div>
										<div class="tclas-faq-demo-arrow"><i class="bi bi-arrow-down" aria-hidden="true"></i></div>
										<div class="tclas-faq-demo-gen"><i class="bi bi-person-fill" aria-hidden="true"></i> You</div>
										<div class="tclas-faq-demo-result"><i class="bi bi-check-lg" aria-hidden="true"></i> Direct path</div>
									</div>
								</div>
								<div class="tclas-faq-demo-col">
									<h4><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> Female Line (Pre-1969)</h4>
									<div class="tclas-faq-demo-box">
										<div class="tclas-faq-demo-gen"><i class="bi bi-person-fill" aria-hidden="true"></i> Grandmother <span class="tclas-faq-demo-year">b. 1920</span></div>
										<div class="tclas-faq-demo-arrow-broken"><i class="bi bi-x-lg" aria-hidden="true"></i> Can't pass</div>
										<div class="tclas-faq-demo-gen tclas-faq-demo-gen--broken"><i class="bi bi-person-fill" aria-hidden="true"></i> Mother <span class="tclas-faq-demo-year">b. 1955</span></div>
										<div class="tclas-faq-demo-arrow"><i class="bi bi-arrow-down" aria-hidden="true"></i></div>
										<div class="tclas-faq-demo-gen"><i class="bi bi-person-fill" aria-hidden="true"></i> You</div>
										<div class="tclas-faq-demo-result tclas-faq-demo-result--alt"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> Extra step needed</div>
									</div>
								</div>
							</div>
						</div>

						<p class="tclas-faq-note"><strong>TL;DR:</strong> Same person, two different routes. One is straightforward (Article 7). One requires an extra step (Article 23). The quiz will figure out which one applies to you.</p>
					</div>
				</div>
			</div>

			<!-- FAQ Item 2: Language -->
			<div class="tclas-faq-item">
				<button class="tclas-faq-header" aria-expanded="false">
					<i class="bi bi-chat-dots tclas-faq-icon" aria-hidden="true"></i>
					<span class="tclas-faq-question">Do I need to speak French? German? Lëtzebuergesch?</span>
					<span class="tclas-faq-toggle" aria-hidden="true">+</span>
				</button>
				<div class="tclas-faq-content">
					<div class="tclas-faq-body">
						<div class="tclas-faq-checklist">
							<div class="tclas-faq-check tclas-faq-check--no">
								<i class="bi bi-x-lg tclas-faq-check-icon" aria-hidden="true"></i>
								<span class="tclas-faq-check-text"><strong>French?</strong> No language test</span>
							</div>
							<div class="tclas-faq-check tclas-faq-check--no">
								<i class="bi bi-x-lg tclas-faq-check-icon" aria-hidden="true"></i>
								<span class="tclas-faq-check-text"><strong>German?</strong> No language test</span>
							</div>
							<div class="tclas-faq-check tclas-faq-check--no">
								<i class="bi bi-x-lg tclas-faq-check-icon" aria-hidden="true"></i>
								<span class="tclas-faq-check-text"><strong>Lëtzebuergesch?</strong> No language test</span>
							</div>
							<div class="tclas-faq-check tclas-faq-check--yes">
								<i class="bi bi-check-lg tclas-faq-check-icon" aria-hidden="true"></i>
								<span class="tclas-faq-check-text"><strong>English</strong> works fine with consulates</span>
							</div>
						</div>
						<p class="tclas-faq-note"><strong>Why?</strong> Luxembourg citizenship by descent is about proving your ancestry, not integration. No civics exam, no residency requirement, no language barrier.</p>
					</div>
				</div>
			</div>

			<!-- FAQ Item 3: Civics Exam -->
			<div class="tclas-faq-item">
				<button class="tclas-faq-header" aria-expanded="false">
					<i class="bi bi-file-text tclas-faq-icon" aria-hidden="true"></i>
					<span class="tclas-faq-question">Do I have to take a civics exam?</span>
					<span class="tclas-faq-toggle" aria-hidden="true">+</span>
				</button>
				<div class="tclas-faq-content">
					<div class="tclas-faq-body">
						<div class="tclas-faq-checklist">
							<div class="tclas-faq-check tclas-faq-check--no">
								<i class="bi bi-x-lg tclas-faq-check-icon" aria-hidden="true"></i>
								<span class="tclas-faq-check-text">No civics exam</span>
							</div>
							<div class="tclas-faq-check tclas-faq-check--no">
								<i class="bi bi-x-lg tclas-faq-check-icon" aria-hidden="true"></i>
								<span class="tclas-faq-check-text">No integration test</span>
							</div>
							<div class="tclas-faq-check tclas-faq-check--no">
								<i class="bi bi-x-lg tclas-faq-check-icon" aria-hidden="true"></i>
								<span class="tclas-faq-check-text">No residency requirement</span>
							</div>
							<div class="tclas-faq-check tclas-faq-check--yes">
								<i class="bi bi-check-lg tclas-faq-check-icon" aria-hidden="true"></i>
								<span class="tclas-faq-check-text">Just documentation of your lineage</span>
							</div>
						</div>
						<p class="tclas-faq-note"><strong>Why?</strong> You're claiming citizenship through ancestry, not applying for naturalization. Luxembourg recognizes your right to it through descent alone—no assimilation required.</p>
					</div>
				</div>
			</div>

			<!-- FAQ Item 4: Two Pathways -->
			<div class="tclas-faq-item">
				<button class="tclas-faq-header" aria-expanded="false">
					<i class="bi bi-signpost-split tclas-faq-icon" aria-hidden="true"></i>
					<span class="tclas-faq-question">Article 7 or Article 23—what's the difference?</span>
					<span class="tclas-faq-toggle" aria-hidden="true">+</span>
				</button>
				<div class="tclas-faq-content">
					<div class="tclas-faq-body">
						<div class="tclas-faq-pathway">
							<div class="tclas-faq-pathway-col">
								<h4>Article 7 (Direct Descent)</h4>
								<div class="tclas-faq-pathway-box tclas-faq-pathway-box--primary">
									<div class="tclas-faq-pathway-step">
										<span class="tclas-faq-pathway-num">1</span>
										<span>Unbroken male line from ancestor</span>
									</div>
									<div class="tclas-faq-pathway-step">
										<span class="tclas-faq-pathway-num">2</span>
										<span>Apply to consulate by mail</span>
									</div>
									<div class="tclas-faq-pathway-step">
										<span class="tclas-faq-pathway-num">3</span>
										<span>Citizenship granted (no travel needed)</span>
									</div>
								</div>
								<p class="tclas-faq-pathway-note"><i class="bi bi-clock" aria-hidden="true"></i> ~12–24 months</p>
							</div>
							<div class="tclas-faq-pathway-col">
								<h4>Article 23 (Female Ancestor)</h4>
								<div class="tclas-faq-pathway-box tclas-faq-pathway-box--secondary">
									<div class="tclas-faq-pathway-step">
										<span class="tclas-faq-pathway-num">1</span>
										<span>Female ancestor born before 1969</span>
									</div>
									<div class="tclas-faq-pathway-step">
										<span class="tclas-faq-pathway-num">2</span>
										<span>In-person appointment in Luxembourg</span>
									</div>
									<div class="tclas-faq-pathway-step">
										<span class="tclas-faq-pathway-num">3</span>
										<span>Citizenship granted after interview</span>
									</div>
								</div>
								<p class="tclas-faq-pathway-note"><i class="bi bi-clock" aria-hidden="true"></i> ~4–6 months (if eligible)</p>
							</div>
						</div>
						<p class="tclas-faq-note"><strong>The quiz will tell you which one applies.</strong></p>
					</div>
				</div>
			</div>

			</div><!-- .tclas-faq-accordion -->
			</div><!-- .tclas-citizenship-faq-col -->

			<!-- Right: Quiz Section -->
			<div class="tclas-citizenship-quiz-col">
				<div class="tclas-citizenship-quiz-sticky">
					<div class="tclas-quiz-intro">
						<span class="tclas-eyebrow"><?php esc_html_e( 'Eligibility quiz', 'tclas' ); ?></span>
						<h2 class="tclas-ruled"><?php esc_html_e( 'Find out in a few steps.', 'tclas' ); ?></h2>
						<p><?php esc_html_e( 'Answer a few questions about your Luxembourg ancestry to get a personalized assessment. Most people complete it in under two minutes.', 'tclas' ); ?></p>
					</div>
					<div class="tclas-quiz-wrapper" id="quiz">
						<?php echo do_shortcode( '[luxembourg_eligibility_quiz]' ); ?>
					</div>
				</div>
			</div><!-- .tclas-citizenship-quiz-col -->
		</div><!-- .tclas-citizenship-faq-quiz-grid -->
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
							<div class="tclas-doc-item__icon" aria-hidden="true"><i class="bi bi-<?php echo esc_attr( get_sub_field( 'res_icon' ) ); ?>"></i></div>
							<div>
								<span class="tclas-doc-item__name"><?php echo esc_html( get_sub_field( 'res_name' ) ); ?></span>
								<span class="tclas-doc-item__meta"><?php echo esc_html( get_sub_field( 'res_desc' ) ); ?></span>
							</div>
							<i class="bi bi-box-arrow-up-right tclas-doc-item__download" aria-hidden="true"></i>
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
							<i class="bi bi-box-arrow-up-right tclas-doc-item__download" aria-hidden="true"></i>
						</a>

						<a href="https://anlux.public.lu/en.html"
						   class="tclas-doc-item"
						   target="_blank"
						   rel="noopener noreferrer">
							<div class="tclas-doc-item__icon" aria-hidden="true"><i class="bi bi-journal-text"></i></div>
							<div>
								<span class="tclas-doc-item__name"><?php esc_html_e( 'ANLux — National Archives', 'tclas' ); ?></span>
								<span class="tclas-doc-item__meta"><?php esc_html_e( 'Luxembourg\'s National Archives hold vital records, civil registration documents, and genealogical research databases.', 'tclas' ); ?></span>
							</div>
							<i class="bi bi-box-arrow-up-right tclas-doc-item__download" aria-hidden="true"></i>
						</a>

						<a href="https://chicago.mae.lu/en.html"
						   class="tclas-doc-item"
						   target="_blank"
						   rel="noopener noreferrer">
							<div class="tclas-doc-item__icon" aria-hidden="true"><i class="bi bi-building-columns"></i></div>
							<div>
								<span class="tclas-doc-item__name"><?php esc_html_e( 'Consulate General — Chicago', 'tclas' ); ?></span>
								<span class="tclas-doc-item__meta"><?php esc_html_e( 'The consulate serving Minnesota and the Midwest. Contact them to begin the application process and confirm document requirements.', 'tclas' ); ?></span>
							</div>
							<i class="bi bi-box-arrow-up-right tclas-doc-item__download" aria-hidden="true"></i>
						</a>

						<a href="https://maee.gouvernement.lu/en/directions/consulaires/nationalite.html"
						   class="tclas-doc-item"
						   target="_blank"
						   rel="noopener noreferrer">
							<div class="tclas-doc-item__icon" aria-hidden="true"><i class="bi bi-clipboard-check"></i></div>
							<div>
								<span class="tclas-doc-item__name"><?php esc_html_e( 'Ministry of Foreign Affairs', 'tclas' ); ?></span>
								<span class="tclas-doc-item__meta"><?php esc_html_e( 'Official guidance on the recovery of nationality, including the 2021 amendments to the Law of June 8, 2017.', 'tclas' ); ?></span>
							</div>
							<i class="bi bi-box-arrow-up-right tclas-doc-item__download" aria-hidden="true"></i>
						</a>

					<?php endif; ?>
				</div>
			</div>

		</div><!-- .tclas-grid-2 -->
	</div>
</section>

<!-- ── Community CTA ─────────────────────────────────────────────────────── -->
<section class="tclas-section bg-ardoise">
	<div class="container-tclas container--medium tclas-citizenship-cta">
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
				<a href="<?php echo esc_url( home_url( '/member-hub/forums/' ) ); ?>" class="btn btn-outline-light">
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
