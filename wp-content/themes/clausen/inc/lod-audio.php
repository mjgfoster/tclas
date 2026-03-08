<?php
/**
 * LOD.lu Pronunciation Audio Integration
 *
 * Fetches native Luxembourgish audio pronunciation for commune names from the
 * Linked Open Data Luxembourg (LOD.lu) API. Results are cached per commune
 * in a 7-day transient so the API is not hit on every page load.
 *
 * LOD.lu is a public endpoint maintained by LIST (Luxembourg Institute of
 * Science and Technology) — no API key required.
 *
 * Forvo fallback: when LOD.lu has no audio, optionally falls back to the
 * Forvo pronunciation API. Requires `tclas_forvo_api_key` in ACF Theme Options
 * once a key is available. If no key is set, the fallback is silently skipped.
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Return the audio URL (mp3/ogg) for a Luxembourg commune name from LOD.lu.
 *
 * @param string $commune_name  Official Luxembourgish name (e.g. "Ëchternach").
 * @param string $slug          Commune slug for cache key (e.g. "echternach").
 * @return string|null          Audio file URL, or null if unavailable.
 */
function tclas_get_commune_audio( string $commune_name, string $slug ): ?string {
	$transient_key = 'tclas_lod_audio_' . sanitize_key( $slug );

	$cached = get_transient( $transient_key );
	if ( $cached !== false ) {
		// Cached null stored as empty string to distinguish from cache miss
		return '' === $cached ? null : $cached;
	}

	$audio_url = null;

	// ── Primary: LOD.lu SPARQL endpoint ───────────────────────────────────────
	$audio_url = tclas_lod_lu_fetch( $commune_name );

	// ── Fallback: Forvo API ───────────────────────────────────────────────────
	if ( null === $audio_url ) {
		$forvo_key = function_exists( 'get_field' )
			? get_field( 'tclas_forvo_api_key', 'option' )
			: '';
		if ( ! empty( $forvo_key ) ) {
			$audio_url = tclas_forvo_fetch( $commune_name, (string) $forvo_key );
		}
	}

	// Cache for 7 days (or empty string if no audio found)
	set_transient( $transient_key, $audio_url ?? '', 7 * DAY_IN_SECONDS );

	return $audio_url;
}

/**
 * Fetch pronunciation audio from LOD.lu REST API.
 *
 * Two-step lookup:
 * 1. Search: GET /api/lb/advanced-search?query={name} → find the LOD article ID
 * 2. Entry:  GET /api/lb/entry/{id} → extract entry.audioFiles.ogg
 *
 * @param string $name  Luxembourgish commune name (e.g. "Conter").
 * @return string|null  Audio file URL (OGG), or null if unavailable.
 */
function tclas_lod_lu_fetch( string $name ): ?string {
	// Step 1: Search for the article by Luxembourgish name
	$search_url = add_query_arg( 'query', $name, 'https://lod.lu/api/lb/advanced-search' );

	$search_response = wp_remote_get( $search_url, [ 'timeout' => 8 ] );
	if ( is_wp_error( $search_response ) || wp_remote_retrieve_response_code( $search_response ) !== 200 ) {
		return null;
	}

	$search_data = json_decode( wp_remote_retrieve_body( $search_response ), true );
	$results     = $search_data['results'] ?? [];

	if ( empty( $results ) ) {
		return null;
	}

	// Find an exact lemma match (case-insensitive) to avoid false positives
	$lod_id = null;
	foreach ( $results as $r ) {
		if ( mb_strtolower( $r['word_lb'] ?? '' ) === mb_strtolower( $name ) ) {
			$lod_id = $r['id'] ?? $r['article_id'] ?? null;
			break;
		}
	}
	if ( ! $lod_id ) {
		return null;
	}

	// Step 2: Fetch the full entry to get the headword audio
	$entry_url = 'https://lod.lu/api/lb/entry/' . rawurlencode( $lod_id );

	$entry_response = wp_remote_get( $entry_url, [ 'timeout' => 8 ] );
	if ( is_wp_error( $entry_response ) || wp_remote_retrieve_response_code( $entry_response ) !== 200 ) {
		return null;
	}

	$entry_data = json_decode( wp_remote_retrieve_body( $entry_response ), true );
	$audio      = $entry_data['entry']['audioFiles'] ?? [];

	// Prefer OGG (wider browser support for <audio>), fall back to AAC
	$audio_url = $audio['ogg'] ?? $audio['aac'] ?? null;

	return $audio_url ? esc_url_raw( $audio_url ) : null;
}

/**
 * Query Forvo API v2 for a pronunciation of the commune name.
 *
 * @param string $name     Commune name.
 * @param string $api_key  Forvo API key.
 * @return string|null     Audio URL (mp3) or null.
 */
function tclas_forvo_fetch( string $name, string $api_key ): ?string {
	$url = sprintf(
		'https://apiv2.forvo.com/key/%s/format/json/action/standard-pronunciation/word/%s/language/lb/',
		rawurlencode( $api_key ),
		rawurlencode( $name )
	);

	$response = wp_remote_get( $url, [ 'timeout' => 8 ] );

	if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
		return null;
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	$items = $data['items'] ?? [];

	if ( ! empty( $items[0]['pathmp3'] ) ) {
		return esc_url_raw( $items[0]['pathmp3'] );
	}

	return null;
}

/**
 * Render the Luxembourgish name with an inline play button and LOD attribution.
 *
 * If no audio is available, renders just the name without a button.
 *
 * @param string $commune_name  Luxembourgish commune name (e.g. "Conter").
 * @param string $slug          Commune slug for cache key.
 * @return string               HTML string.
 */
function tclas_commune_audio_html( string $commune_name, string $slug ): string {
	$audio_url = tclas_get_commune_audio( $commune_name, $slug );

	ob_start();
	?>
	<div class="tclas-commune-pronun">
		<span class="tclas-commune-pronun__row">
			<span class="tclas-commune-pronun__name" lang="lb"><?php echo esc_html( $commune_name ); ?></span>
			<?php if ( $audio_url ) : ?>
			<button type="button" class="tclas-commune-pronun__play"
				data-audio-src="<?php echo esc_url( $audio_url ); ?>"
				aria-label="<?php echo esc_attr( sprintf( __( 'Listen to pronunciation of %s', 'tclas' ), $commune_name ) ); ?>">
				<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><polygon points="4,2 14,8 4,14"/></svg>
			</button>
			<?php endif; ?>
		</span>
		<?php if ( $audio_url ) : ?>
		<span class="tclas-commune-pronun__credit">
			<?php printf(
				/* translators: %s: link to LOD.lu */
				esc_html__( 'Audio from %s', 'tclas' ),
				'<a href="https://lod.lu" target="_blank" rel="noopener noreferrer">Lëtzebuerger Online Dictionnaire</a>'
			); ?>
		</span>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}
