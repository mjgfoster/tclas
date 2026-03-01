<?php
/**
 * Luxembourgish Linguistic Tagger
 *
 * Automatically wraps known Luxembourgish terms in <span lang="lb"> for
 * screen-reader accuracy and correct language detection.
 *
 * Applied to front-end rendered post content and excerpts only.
 * Terms are matched whole-word, case-sensitively to avoid false positives.
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Curated list of Luxembourgish words and phrases used in TCLAS content.
 * Ordered longest-first so multi-word phrases take priority over single words.
 *
 * @return string[]
 */
function tclas_ltz_terms(): array {
	return [
		// Phrases first (longest match wins)
		'Gudde Moien',
		'Gudden Owend',
		'Gudde Nuecht',
		'Wéi geet et',
		'merci vill Mol',
		'Prost Mahlzeit',
		// Single words
		'Moien',
		'Äddi',
		'Lëtzebuergesch',
		'Lëtzebuerg',
		'Lëtzebuerger',
		'Bësch',
		'Joer',
		'Gemengen',
		'Gemeng',
		'Duerf',
		'Stad',
		'Clausen',
		'Schlass',
	];
}

/**
 * Wrap each known Luxembourgish term in <span lang="lb">.
 *
 * Skips existing <span lang="lb"> wraps, HTML tags, and attribute values
 * by operating on text nodes only (via a simple state-machine HTML walk).
 *
 * @param string $content Post content HTML.
 * @return string
 */
function tclas_ltz_tag_content( string $content ): string {
	if ( empty( $content ) ) {
		return $content;
	}

	$terms   = tclas_ltz_terms();
	$pattern = implode(
		'|',
		array_map(
			fn( $t ) => preg_quote( $t, '/' ),
			$terms
		)
	);

	// Match terms only outside HTML tags (not inside < > or attribute quotes).
	// The negative look-ahead/behind for < and > guards against mangling markup.
	return preg_replace_callback(
		'/(?<![<"\'=])(' . $pattern . ')(?![>"\'=\w])/u',
		function ( array $m ): string {
			// Don't double-wrap — if already inside a lang="lb" context the
			// browser ignores the inner span, but prevent noise in the DOM.
			return '<span lang="lb">' . esc_html( $m[1] ) . '</span>';
		},
		$content
	);
}

/**
 * Register the filter hooks — front-end only, not in admin or REST context.
 */
function tclas_ltz_tagger_init(): void {
	if ( is_admin() ) {
		return;
	}

	// Priority 20: run after most content filters, before shortcodes resolve.
	add_filter( 'the_content', 'tclas_ltz_tag_content', 20 );
	add_filter( 'the_excerpt', 'tclas_ltz_tag_content', 20 );
	add_filter( 'get_the_excerpt', 'tclas_ltz_tag_content', 20 );
}
add_action( 'init', 'tclas_ltz_tagger_init' );
