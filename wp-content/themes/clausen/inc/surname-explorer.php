<?php
/**
 * TCLAS Surname Explorer — public "Is your name Luxembourgish?" finder
 *
 * Shortcode: [tclas_surname_finder]
 *
 * Instant client-side search over published `tclas_surname` posts (one post
 * per variant cluster: canonical head + all known spellings). The JS mirrors
 * the connections-engine matching ladder — normalize, exact, umlaut
 * expansion, Levenshtein with length-scaled thresholds — so "Smyth" finds
 * Schmitt. Below the search box, a server-rendered A–Z index links every
 * surname page (crawlable: search boxes are invisible to crawlers; this list
 * is why individual pages get indexed).
 *
 * PRIVACY: strictly historical/editorial data. No member data, no member
 * counts — surname + small count would be identifying. Member features stay
 * behind the join wall (How Are We Connected).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── Enqueue ───────────────────────────────────────────────────────────────────

add_action( 'wp_enqueue_scripts', 'tclas_register_surname_finder_assets' );
function tclas_register_surname_finder_assets(): void {
	wp_register_style(
		'tclas-surname-finder',
		get_template_directory_uri() . '/assets/css/tclas-surname-finder.css',
		[],
		filemtime( get_template_directory() . '/assets/css/tclas-surname-finder.css' )
	);
	wp_register_script(
		'tclas-surname-finder',
		get_template_directory_uri() . '/assets/js/tclas-surname-finder.js',
		[],
		filemtime( get_template_directory() . '/assets/js/tclas-surname-finder.js' ),
		true
	);
}

// ── Data ──────────────────────────────────────────────────────────────────────

/**
 * All published surname clusters, alphabetical by label.
 *
 * @return array<int, array{label: string, url: string, variants: string[], norms: string[]}>
 */
function tclas_build_surname_data(): array {
	$query = new WP_Query( [
		'post_type'      => 'tclas_surname',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'no_found_rows'  => true,
	] );

	$out = [];
	foreach ( $query->posts as $post ) {
		$variants = [];
		$norms    = [];
		foreach ( (array) ( get_field( 'surname_variants', $post->ID ) ?: [] ) as $row ) {
			$v = trim( (string) ( $row['variant'] ?? '' ) );
			if ( '' === $v ) continue;
			$variants[] = $v;
			$norms[]    = tclas_normalize_string( $v );
		}

		$out[] = [
			'label'    => html_entity_decode( get_the_title( $post ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
			'url'      => get_permalink( $post ),
			'variants' => $variants,
			'norms'    => array_values( array_unique( array_filter( $norms ) ) ),
		];
	}

	return $out;
}

// ── Shortcode ─────────────────────────────────────────────────────────────────

add_shortcode( 'tclas_surname_finder', 'tclas_surname_finder_shortcode' );

function tclas_surname_finder_shortcode( array $atts = [] ): string {
	$surnames = tclas_build_surname_data();

	wp_enqueue_style( 'tclas-surname-finder' );
	wp_enqueue_script( 'tclas-surname-finder' );
	wp_localize_script( 'tclas-surname-finder', 'tclasSurnameData', [
		'surnames' => $surnames,
	] );

	ob_start();
	?>
	<div class="tclas-surname-finder">

		<div class="tclas-surname-finder__search">
			<label class="tclas-surname-finder__label" for="tclas-surname-input">
				<?php esc_html_e( 'Enter a family name', 'tclas' ); ?>
			</label>
			<input type="search" id="tclas-surname-input" class="tclas-surname-finder__input"
			       placeholder="<?php esc_attr_e( 'Schmitt, Miller, Kieffer…', 'tclas' ); ?>"
			       autocomplete="off" autocapitalize="off" spellcheck="false" />
			<div id="tclas-surname-results" class="tclas-surname-finder__results" aria-live="polite"></div>

			<!-- Not-found template: cloned by JS so copy stays in PHP/translatable -->
			<template id="tclas-surname-notfound">
				<div class="tclas-surname-notfound">
					<p>
						<strong><?php esc_html_e( 'Not in our index — yet.', 'tclas' ); ?></strong>
						<?php esc_html_e( 'That doesn\'t mean the name isn\'t Luxembourgish: our index grows as we work through emigration records, and many names changed spelling in America.', 'tclas' ); ?>
					</p>
					<p>
						<a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Ask us to research your name', 'tclas' ); ?></a>
						<?php esc_html_e( '— or', 'tclas' ); ?>
						<a href="<?php echo esc_url( home_url( '/join/' ) ); ?>"><?php esc_html_e( 'join TCLAS', 'tclas' ); ?></a>
						<?php esc_html_e( 'and add your family\'s story to our ancestral map.', 'tclas' ); ?>
					</p>
				</div>
			</template>
		</div>

		<p class="tclas-surname-finder__disclaimer">
			<?php esc_html_e( 'Family names cross borders. A name appearing here means it is attested among Luxembourgish emigrants and their descendants in our sources — many of these names are also found in Germany, Belgium, and France.', 'tclas' ); ?>
		</p>

		<?php if ( $surnames ) : ?>
			<?php
			// A–Z index, grouped by first letter — the crawlable path to every
			// surname page.
			$groups = [];
			foreach ( $surnames as $s ) {
				$letter = strtoupper( tclas_strip_diacritics( mb_substr( $s['label'], 0, 1 ) ) );
				$groups[ $letter ][] = $s;
			}
			ksort( $groups );
			?>
			<div class="tclas-surname-index">
				<h2 class="tclas-surname-index__title"><?php esc_html_e( 'Browse all names', 'tclas' ); ?></h2>
				<?php foreach ( $groups as $letter => $names ) : ?>
					<div class="tclas-surname-index__group">
						<h3 class="tclas-surname-index__letter"><?php echo esc_html( $letter ); ?></h3>
						<ul class="tclas-surname-index__list">
							<?php foreach ( $names as $s ) : ?>
								<li>
									<a href="<?php echo esc_url( $s['url'] ); ?>"><?php echo esc_html( $s['label'] ); ?></a>
									<?php
									$others = array_diff( $s['variants'], [ $s['label'] ] );
									if ( $others ) {
										echo '<span class="tclas-surname-index__variants">' . esc_html( implode( ', ', array_slice( $others, 0, 4 ) ) ) . '</span>';
									}
									?>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

	</div>
	<?php
	return ob_get_clean();
}

// ── SEO ───────────────────────────────────────────────────────────────────────

/**
 * Title tag for surname pages: match the question people actually search.
 * Filtered twice: WP core's generator, and The SEO Framework's (which
 * bypasses document_title_parts when active).
 */
add_filter( 'document_title_parts', 'tclas_surname_document_title' );
function tclas_surname_document_title( array $parts ): array {
	if ( is_singular( 'tclas_surname' ) ) {
		$parts['title'] = tclas_surname_seo_title( get_the_title() );
	}
	return $parts;
}

add_filter( 'the_seo_framework_title_from_generation', 'tclas_surname_tsf_title', 10, 2 );
function tclas_surname_tsf_title( $title, $args ) {
	if ( null === $args && is_singular( 'tclas_surname' ) ) {
		return tclas_surname_seo_title( get_the_title() );
	}
	return $title;
}

function tclas_surname_seo_title( string $name ): string {
	/* translators: %s: surname */
	return sprintf( __( 'Is %s a Luxembourgish surname?', 'tclas' ), $name );
}
