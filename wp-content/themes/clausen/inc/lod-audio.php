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
 * Query LOD.lu via SPARQL to find an audio recording for a place name.
 *
 * The LOD.lu endpoint uses the Luxembourgish Geonames-linked dataset which
 * may include foaf:depiction or dbp:pronounciation links for some entries.
 * We query for rdfs:label matching the commune name and look for any audio
 * property (mo:recording, schema:audio, etc.).
 *
 * @param string $name  Commune name to look up.
 * @return string|null  Audio URL or null.
 */
function tclas_lod_lu_fetch( string $name ): ?string {
	// URL-encode the SPARQL query
	$sparql = sprintf(
		'PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX schema: <https://schema.org/>
SELECT ?audio WHERE {
  ?place rdfs:label "%s"@lb .
  { ?place schema:audio ?audio . }
  UNION { ?place <http://purl.org/ontology/mo/recording> ?audio . }
} LIMIT 1',
		esc_sql( $name )
	);

	$endpoint = 'https://data.lod.lu/sparql';
	$url      = add_query_arg( [
		'query'  => $sparql,
		'format' => 'application/sparql-results+json',
	], $endpoint );

	$response = wp_remote_get( $url, [
		'timeout' => 8,
		'headers' => [ 'Accept' => 'application/sparql-results+json' ],
	] );

	if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
		return null;
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	$bindings = $body['results']['bindings'] ?? [];

	if ( ! empty( $bindings ) && ! empty( $bindings[0]['audio']['value'] ) ) {
		return esc_url_raw( $bindings[0]['audio']['value'] );
	}

	return null;
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
 * Render an audio player HTML block for a commune, or nothing if unavailable.
 *
 * @param string $commune_name  Official Luxembourgish name.
 * @param string $slug          Commune slug.
 * @return string               HTML string (may be empty).
 */
function tclas_commune_audio_html( string $commune_name, string $slug ): string {
	$audio_url = tclas_get_commune_audio( $commune_name, $slug );
	if ( ! $audio_url ) {
		return '';
	}

	ob_start();
	?>
	<div class="tclas-commune-audio">
		<audio controls preload="none" aria-label="<?php echo esc_attr( sprintf( __( 'Pronunciation of %s', 'tclas' ), $commune_name ) ); ?>">
			<source src="<?php echo esc_url( $audio_url ); ?>">
			<a href="<?php echo esc_url( $audio_url ); ?>" target="_blank" rel="noopener">
				<?php esc_html_e( 'Listen to pronunciation', 'tclas' ); ?>
			</a>
		</audio>
		<span class="tclas-commune-fact-label"><?php esc_html_e( 'Native pronunciation', 'tclas' ); ?></span>
	</div>
	<?php
	return ob_get_clean();
}
