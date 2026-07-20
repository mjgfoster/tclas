<?php
/**
 * Template Name: Site Tour (Wëllkomm)
 *
 * QR-code landing page for the July 22, 2026 launch party, then an
 * evergreen "start here" tour. The party greeting self-expires after
 * 7/22 — no cleanup needed.
 *
 * Sections:
 *  1. Page header   — white, simple
 *  2. Intro         — party greeting (until 7/23) + framing
 *  3. Start here    — 2×3 card grid of features
 *  4. Coming this fall — teasers + email-list CTA
 *  5. Join bar      — gold, non-members only
 *
 * @package TCLAS
 */

get_header();

$tour_is_party_week = ( new DateTimeImmutable( 'now', wp_timezone() ) )->format( 'Y-m-d' ) <= '2026-07-22';
?>

<!-- ── Page header ──────────────────────────────────────────────────────── -->
<div class="tclas-page-header">
	<div class="container-tclas">
		<?php tclas_breadcrumb(); ?>
		<h1 class="tclas-page-header__title"><?php echo tclas_ltz( get_the_title(), __( 'Welcome!', 'tclas' ), false ); ?></h1>
	</div>
</div>

<!-- ── Intro ────────────────────────────────────────────────────────────── -->
<section class="tclas-section" aria-label="<?php esc_attr_e( 'Welcome', 'tclas' ); ?>">
	<div class="container-tclas">
		<div class="tclas-ancestry-intro">
			<?php if ( $tour_is_party_week ) : ?>
			<p>
				<?php
				printf(
					/* translators: 1: "wëllkomm" with tooltip, 2: "Prost!" with tooltip */
					esc_html__( 'If you\'re reading this at Yoerg Brewing with a beer in hand — %1$s to the web launch of twincities.lu, and thanks for celebrating with us. %2$s Here\'s the tour, right from your phone.', 'tclas' ),
					tclas_ltz( 'wëllkomm', __( 'welcome', 'tclas' ), false ),
					tclas_ltz( 'Prost!', __( 'Cheers!', 'tclas' ), false )
				);
				?>
			</p>
			<?php endif; ?>
			<p>
				<?php esc_html_e( 'The Twin Cities Luxembourg American Society brings together Americans of Luxembourgish descent, dual citizens, and expat Luxembourgers across the Upper Midwest. This site is built around two questions: where do you come from, and what does Luxembourg mean to you today? Here\'s where to start.', 'tclas' ); ?>
			</p>
			<p>
				<?php
				printf(
					/* translators: %s: "Lëtzebuergesch" with tooltip */
					esc_html__( 'One thing before you go exploring: anywhere you see a word with a dotted underline — like %s — hover over it, or tap it, and it translates itself. The site speaks both languages, and so can you.', 'tclas' ),
					tclas_ltz( 'Lëtzebuergesch', __( 'Luxembourgish', 'tclas' ), false )
				);
				?>
			</p>
			<p><?php tclas_ltz_showall_button(); ?></p>
		</div>
	</div>
</section>

<!-- ── Start here ───────────────────────────────────────────────────────── -->
<section class="tclas-section" aria-labelledby="tour-start-heading">
	<div class="container-tclas">

		<span class="tclas-eyebrow"><?php esc_html_e( 'Start here', 'tclas' ); ?></span>
		<h2 id="tour-start-heading"><?php esc_html_e( 'Six things to try', 'tclas' ); ?></h2>

		<div class="tclas-grid-3">

			<article class="tclas-card tclas-card--accented">
				<div class="tclas-card__body">
					<h3 class="tclas-card__title">
						<a href="<?php echo esc_url( home_url( '/msp-lux/places/' ) ); ?>"><?php esc_html_e( 'Luxembourgers in North America', 'tclas' ); ?></a>
					</h3>
					<p class="tclas-card__excerpt">
						<?php esc_html_e( 'An interactive map of where emigrant families put down roots — the towns, churches, and monuments that carry Luxembourg\'s story, from Minnesota to both coasts.', 'tclas' ); ?>
					</p>
				</div>
			</article>

			<article class="tclas-card tclas-card--accented">
				<div class="tclas-card__body">
					<h3 class="tclas-card__title">
						<a href="<?php echo esc_url( home_url( '/citizenship/' ) ); ?>"><?php esc_html_e( 'Could you be a Luxembourg citizen?', 'tclas' ); ?></a>
					</h3>
					<p class="tclas-card__excerpt">
						<?php esc_html_e( 'Thousands of Americans have reclaimed citizenship through an ancestor. A two-minute quiz walks through your family history and gives you a personalized read.', 'tclas' ); ?>
					</p>
				</div>
			</article>

			<article class="tclas-card tclas-card--accented">
				<div class="tclas-card__body">
					<h3 class="tclas-card__title">
						<a href="<?php echo esc_url( home_url( '/#culture-corner' ) ); ?>"><?php esc_html_e( 'Wuert vun der Woch', 'tclas' ); ?></a>
					</h3>
					<p class="tclas-card__excerpt">
						<?php
						printf(
							/* translators: %s: "Elo zu Lëtzebuerg" with tooltip */
							esc_html__( 'One Luxembourgish word each week, with audio so you can hear how it\'s really said — plus %s, what\'s happening in the Grand Duchy right now.', 'tclas' ),
							tclas_ltz( 'Elo zu Lëtzebuerg', __( 'Now in Luxembourg', 'tclas' ), false )
						);
						?>
					</p>
				</div>
			</article>

			<article class="tclas-card tclas-card--accented">
				<div class="tclas-card__body">
					<h3 class="tclas-card__title">
						<a href="<?php echo esc_url( home_url( '/msp-lux/' ) ); ?>"><?php esc_html_e( 'Minnesota\'s Luxembourg story', 'tclas' ); ?></a>
					</h3>
					<p class="tclas-card__excerpt">
						<?php esc_html_e( 'Why so many Luxembourgish names around the Twin Cities? History, side-by-side stats, culture, and the Lëtzebuergesch language itself.', 'tclas' ); ?>
					</p>
				</div>
			</article>

			<article class="tclas-card tclas-card--accented">
				<div class="tclas-card__body">
					<h3 class="tclas-card__title">
						<a href="<?php echo esc_url( home_url( '/events/' ) ); ?>"><?php esc_html_e( 'Come to something', 'tclas' ); ?></a>
					</h3>
					<p class="tclas-card__excerpt">
						<?php
						printf(
							/* translators: %s: "Moien" with tooltip */
							esc_html__( 'Happy hours, heritage events, traditions worth keeping alive. We gather regularly — come say %s.', 'tclas' ),
							tclas_ltz( 'Moien', __( 'Hi', 'tclas' ), false )
						);
						?>
					</p>
				</div>
			</article>

			<article class="tclas-card tclas-card--accented">
				<div class="tclas-card__body">
					<h3 class="tclas-card__title">
						<a href="<?php echo esc_url( home_url( '/join/' ) ); ?>"><?php esc_html_e( 'The members\' corner', 'tclas' ); ?></a>
					</h3>
					<p class="tclas-card__excerpt">
						<?php esc_html_e( 'Members get a private corner of the site: a member directory, a map of members\' ancestral communes, and member-only events. A Household membership covers everyone at your address.', 'tclas' ); ?>
					</p>
				</div>
			</article>

		</div>
	</div>
</section>

<!-- ── Coming this fall ─────────────────────────────────────────────────── -->
<section class="tclas-section" aria-labelledby="tour-fall-heading">
	<div class="container-tclas">

		<span class="tclas-eyebrow tclas-eyebrow--accent"><?php esc_html_e( 'Coming this fall', 'tclas' ); ?></span>
		<h2 id="tour-fall-heading"><?php esc_html_e( 'The site keeps growing', 'tclas' ); ?></h2>

		<div class="tclas-ancestry-intro">
			<p>
				<?php
				printf(
					/* translators: %s: "Ass Ären Numm lëtzebuergesch?" with tooltip */
					esc_html__( 'This fall brings The Loon & the Lion, our email newsletter — and %s: look up your family name in a searchable index of the surnames Luxembourgers carried to America. Members hear about every launch first.', 'tclas' ),
					tclas_ltz( 'Ass Ären Numm lëtzebuergesch?', __( 'Is your name Luxembourgish?', 'tclas' ), false )
				);
				?>
			</p>
			<a href="<?php echo esc_url( home_url( '/#email-signup' ) ); ?>" class="btn btn-outline-ardoise">
				<?php esc_html_e( 'Get on the email list', 'tclas' ); ?>
			</a>
		</div>

	</div>
</section>

<!-- ── Join bar (non-members only) ──────────────────────────────────────── -->
<?php if ( ! tclas_is_member() ) : ?>
<section class="tclas-join-bar">
	<div class="container-tclas">
		<h2><?php echo tclas_ltz( 'Komm mat.', __( 'Come along.', 'tclas' ), false ); ?></h2>
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
