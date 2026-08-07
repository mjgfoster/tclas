<?php
/**
 * 404 template
 *
 * A recovery page, not a banner: the job is to get someone back on course, so
 * it leads with search and real destinations rather than two guesses.
 *
 * Deliberately does NOT use .tclas-hero--page. That component belongs to
 * single.php's article header, and the two pages want to look nothing alike.
 *
 * @package TCLAS
 */

get_header();

/**
 * Recovery destinations, ordered by how likely they are to be what was wanted.
 */
$tclas_404_links = [
	[ 'label' => __( 'Events', 'tclas' ),         'path' => '/events/' ],
	[ 'label' => __( 'Join TCLAS', 'tclas' ),     'path' => '/join/' ],
	[ 'label' => __( 'Ancestral map', 'tclas' ),  'path' => '/ancestry/' ],
	[ 'label' => __( 'Surname finder', 'tclas' ), 'path' => '/ancestry/surnames/' ],
	[ 'label' => __( 'Citizenship', 'tclas' ),    'path' => '/citizenship/' ],
	[ 'label' => __( 'About', 'tclas' ),          'path' => '/about/' ],
	[ 'label' => __( 'Contact', 'tclas' ),        'path' => '/contact/' ],
];
?>

<section class="tclas-404">
	<div class="container-tclas container--medium">

		<span class="tclas-eyebrow tclas-404__code">404</span>

		<?php
		// Plain lang-tagged text rather than tclas_ltz(): that helper adds a
		// tabindex="0" <abbr> for its hover tooltip, and at display size its
		// tint + dotted underline read as an error highlight. The gloss below
		// shows the translation outright, so the tooltip — and the focus stop
		// it would create — are both redundant.
		?>
		<h1 class="tclas-404__title">
			<span lang="lb"><?php esc_html_e( 'Hoppla.', 'tclas' ); ?></span>
		</h1>
		<p class="tclas-404__gloss">&ldquo;<?php esc_html_e( 'Oops.', 'tclas' ); ?>&rdquo;</p>

		<p class="tclas-404__lede">
			<?php esc_html_e( "That page seems to have moved — perhaps it emigrated to Luxembourg. Let's get you somewhere useful.", 'tclas' ); ?>
		</p>

		<form role="search" method="get" class="tclas-search tclas-404__search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<label class="sr-only" for="tclas-404-search"><?php esc_html_e( 'Search', 'tclas' ); ?></label>
			<input
				type="search"
				id="tclas-404-search"
				name="s"
				placeholder="<?php esc_attr_e( 'Search the site…', 'tclas' ); ?>"
				value="<?php echo esc_attr( get_search_query() ); ?>"
			>
			<button type="submit" class="tclas-search__btn" aria-label="<?php esc_attr_e( 'Search', 'tclas' ); ?>">
				<svg aria-hidden="true" focusable="false" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
			</button>
		</form>

		<nav class="tclas-404__wayfinding" aria-labelledby="tclas-404-or">
			<span class="tclas-eyebrow tclas-404__or" id="tclas-404-or"><?php esc_html_e( 'Or try one of these', 'tclas' ); ?></span>
			<ul class="tclas-404__links">
				<?php foreach ( $tclas_404_links as $tclas_404_link ) : ?>
					<li>
						<a href="<?php echo esc_url( home_url( $tclas_404_link['path'] ) ); ?>">
							<?php echo esc_html( $tclas_404_link['label'] ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</nav>

	</div>
</section>

<?php get_footer(); ?>
