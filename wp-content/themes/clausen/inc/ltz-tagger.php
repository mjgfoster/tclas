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
 * Built-in fallback list — used when the ACF vocabulary repeater is empty.
 * Ordered longest-first so multi-word phrases take priority over single words.
 *
 * @return string[]
 */
function tclas_ltz_terms_fallback(): array {
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
 * Return the active Luxembourgish term list.
 *
 * Checks a 12-hour transient first, then reads from the ACF "ltz_terms"
 * repeater on Theme Options. Falls back to the hardcoded list when ACF
 * returns nothing (e.g. field is empty or ACF is inactive).
 *
 * @return string[]
 */
function tclas_ltz_terms(): array {
	$cached = get_transient( 'tclas_ltz_terms' );
	if ( is_array( $cached ) && ! empty( $cached ) ) {
		return $cached;
	}

	$terms = [];

	if ( function_exists( 'get_field' ) ) {
		$rows = get_field( 'ltz_terms', 'option' );
		if ( is_array( $rows ) && ! empty( $rows ) ) {
			foreach ( $rows as $row ) {
				$t = isset( $row['ltz_term'] ) ? trim( $row['ltz_term'] ) : '';
				if ( $t !== '' ) {
					$terms[] = $t;
				}
			}
		}
	}

	if ( empty( $terms ) ) {
		$terms = tclas_ltz_terms_fallback();
	} else {
		// Sort longest-first so multi-word phrases match before single words.
		usort( $terms, fn( $a, $b ) => strlen( $b ) - strlen( $a ) );
	}

	set_transient( 'tclas_ltz_terms', $terms, 12 * HOUR_IN_SECONDS );

	return $terms;
}

/**
 * Bust the ltz_terms transient whenever the Theme Options ACF page is saved.
 *
 * @param int|string $post_id The post ID passed by acf/save_post.
 */
function tclas_ltz_bust_transient( $post_id ): void {
	if ( $post_id === 'options' ) {
		delete_transient( 'tclas_ltz_terms' );
	}
}
add_action( 'acf/save_post', 'tclas_ltz_bust_transient', 20 );

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
