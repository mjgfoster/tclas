<?php
/**
 * TCLAS Luxembourg in America — places map
 *
 * Shortcode: [tclas_places_map]
 *
 * Renders a Leaflet map of Luxembourgish places in the United States — towns
 * founded by Luxembourgers, places with deep Luxembourgish roots, and
 * Luxembourg namesakes — alongside a live-filtered list. Each place is a
 * `tclas_place` post; its `tclas_place_type` terms drive the category layer
 * filters above the map.
 *
 * Fully public: places are editorial content, no member data involved.
 * Reuses the ancestral-map stylesheet for the shared split-layout/list/popup
 * chrome; places-specific styles live in tclas-places-map.css.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── Type registry ─────────────────────────────────────────────────────────────

/**
 * Marker colors per place-type slug (theme palette). Term names/descriptions
 * are editable in admin and flow through; slugs are seeded in taxonomies.php
 * and must stay stable.
 */
function tclas_place_type_registry(): array {
	return [
		'founded-by-luxembourgers' => '#8B3A3A', // crimson
		'luxembourgish-roots'      => '#3D6B4F', // vert
		'namesake'                 => '#2B6282', // ardoise
		'religious-site'           => '#C07C32', // gold (focus shade — F4A460 is too light for markers)
		'memorial'                 => '#626D78', // muted slate
		'museum-archive'           => '#1E4A66', // ardoise-dk
	];
}

// ── Enqueue ───────────────────────────────────────────────────────────────────

add_action( 'wp_enqueue_scripts', 'tclas_register_places_map_assets' );
function tclas_register_places_map_assets(): void {
	// Leaflet handles are registered by ancestor-map.php (same hook); the
	// places styles depend on the ancestral-map sheet for shared map chrome.
	wp_register_style(
		'tclas-places-map',
		get_template_directory_uri() . '/assets/css/tclas-places-map.css',
		[ 'tclas-ancestor-map' ],
		filemtime( get_template_directory() . '/assets/css/tclas-places-map.css' )
	);
	wp_register_script(
		'tclas-places-map',
		get_template_directory_uri() . '/assets/js/tclas-places-map.js',
		[ 'leaflet' ],
		filemtime( get_template_directory() . '/assets/js/tclas-places-map.js' ),
		true
	);
}

// ── Data ──────────────────────────────────────────────────────────────────────

/**
 * Build the map payload from published tclas_place posts.
 *
 * @return array{places: array[], types: array<string, array{label: string, color: string}>}
 */
function tclas_build_places_data(): array {
	$colors = tclas_place_type_registry();
	$places = [];
	$types  = [];

	$query = new WP_Query( [
		'post_type'      => 'tclas_place',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'no_found_rows'  => true,
	] );

	foreach ( $query->posts as $post ) {
		$lat = get_field( 'place_lat', $post->ID );
		$lng = get_field( 'place_lng', $post->ID );
		if ( ! $lat || ! $lng ) {
			continue; // unmappable until coordinates are set
		}

		$term_slugs = [];
		$terms      = get_the_terms( $post->ID, 'tclas_place_type' );
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$term_slugs[] = $term->slug;
				if ( ! isset( $types[ $term->slug ] ) ) {
					$types[ $term->slug ] = [
						// Term names are stored entity-escaped ("&amp;"); decode for
						// the JS payload — the JS escapes on output.
						'label' => wp_specialchars_decode( $term->name, ENT_QUOTES ),
						'color' => $colors[ $term->slug ] ?? '#8B3A3A',
					];
				}
			}
		}

		$places[] = [
			// the_title filters texturize quotes/dashes into numeric entities;
			// decode so the payload carries plain UTF-8 (JS escapes on output).
			'name'    => html_entity_decode( get_the_title( $post ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
			'state'   => (string) get_field( 'place_state', $post->ID ),
			'county'  => (string) get_field( 'place_county', $post->ID ),
			'lat'     => (float) $lat,
			'lng'     => (float) $lng,
			'types'   => $term_slugs,
			'excerpt' => wp_strip_all_tags( get_the_excerpt( $post ) ),
			'url'     => get_permalink( $post ),
		];
	}

	// Present filter chips in registry order (registered types first).
	$ordered = [];
	foreach ( array_keys( $colors ) as $slug ) {
		if ( isset( $types[ $slug ] ) ) {
			$ordered[ $slug ] = $types[ $slug ];
		}
	}
	$types = $ordered + $types;

	return [ 'places' => $places, 'types' => $types ];
}

// ── Shortcode ─────────────────────────────────────────────────────────────────

add_shortcode( 'tclas_places_map', 'tclas_places_map_shortcode' );

function tclas_places_map_shortcode( array $atts = [] ): string {
	$data = tclas_build_places_data();

	wp_enqueue_style( 'tclas-places-map' );
	wp_enqueue_script( 'tclas-places-map' );

	// Same tile config as the ancestral map (Theme Options → Mapbox), with the
	// CartoDB Positron fallback handled in JS.
	$mapbox_token    = get_field( 'mapbox_access_token', 'option' );
	$mapbox_style    = get_field( 'mapbox_style_url', 'option' ) ?: 'mapbox://styles/tclas/cmmhutark001u01s98p0uakek';
	$mapbox_tile_url = '';
	if ( $mapbox_token && preg_match( '#^mapbox://styles/(.+)$#', $mapbox_style, $m ) ) {
		$mapbox_tile_url = 'https://api.mapbox.com/styles/v1/' . $m[1] . '/tiles/256/{z}/{x}/{y}@2x?access_token=' . $mapbox_token;
	}

	wp_localize_script( 'tclas-places-map', 'tclasPlacesData', [
		'places'        => $data['places'],
		'types'         => $data['types'],
		'mapboxTileUrl' => $mapbox_tile_url,
	] );

	ob_start();
	?>
	<div class="tclas-map-wrapper tclas-map-wrapper--split tclas-places-wrapper">

		<?php if ( $data['types'] ) : ?>
		<div class="tclas-places-filters" role="group" aria-label="<?php esc_attr_e( 'Filter places by type', 'tclas' ); ?>">
			<?php foreach ( $data['types'] as $slug => $t ) : ?>
				<button type="button" class="tclas-places-chip" data-type="<?php echo esc_attr( $slug ); ?>"
				        aria-pressed="true" style="--chip-color: <?php echo esc_attr( $t['color'] ); ?>;">
					<span class="tclas-places-chip__dot" aria-hidden="true"></span>
					<?php echo esc_html( $t['label'] ); ?>
				</button>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<div class="tclas-map-split">
			<div class="tclas-map-split__map">
				<div id="tclas-places-map"
				     class="tclas-places-map"
				     role="img"
				     aria-label="<?php esc_attr_e( 'Map of Luxembourgish places in the United States', 'tclas' ); ?>"></div>
				<p class="tclas-map-caption">
					<?php esc_html_e( 'Markers show towns and townships across America with Luxembourgish stories to tell. Tap a marker or a row for details.', 'tclas' ); ?>
				</p>
			</div>
			<div class="tclas-map-split__list">
				<div class="tclas-map-split__list-header">
					<span class="tclas-map-split__count" id="tclas-places-list-count"></span>
				</div>
				<div class="tclas-map-split__list-scroll">
					<table class="tclas-map-list__table" role="table">
						<thead>
							<tr>
								<th scope="col"><?php esc_html_e( 'Place', 'tclas' ); ?></th>
								<th scope="col"><?php esc_html_e( 'State', 'tclas' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Type', 'tclas' ); ?></th>
							</tr>
						</thead>
						<tbody id="tclas-places-list-body"></tbody>
					</table>
				</div>
			</div>
		</div>

	</div>
	<?php
	return ob_get_clean();
}
