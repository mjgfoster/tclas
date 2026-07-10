<?php
/**
 * TCLAS Groups & Events — external Luxembourg organizations map + events sidebar
 *
 * Shortcode: [tclas_orgs_map]
 *
 * A directory of Luxembourg organizations beyond TCLAS (`tclas_org` posts) on
 * the shared map shell: Leaflet map + list, rows fly to markers, popups link
 * out to each org's website. Orgs without coordinates (online-only) appear in
 * the list but not on the map.
 *
 * Also provides tclas_get_upcoming_ext_events() for the self-hiding upcoming
 * events sidebar on page-lux-groups.php: only future-dated `tclas_ext_event`
 * posts are returned, so the page can never show a stale event — no events,
 * no sidebar, no markup.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── Enqueue ───────────────────────────────────────────────────────────────────

add_action( 'wp_enqueue_scripts', 'tclas_register_orgs_map_assets' );
function tclas_register_orgs_map_assets(): void {
	// Depends on the ancestral-map sheet (leaflet → ancestor) for the shared
	// split-layout/list/popup chrome; adds its own map + sidebar styles.
	wp_register_style(
		'tclas-orgs-map',
		get_template_directory_uri() . '/assets/css/tclas-orgs-map.css',
		[ 'tclas-ancestor-map' ],
		filemtime( get_template_directory() . '/assets/css/tclas-orgs-map.css' )
	);
	wp_register_script(
		'tclas-orgs-map',
		get_template_directory_uri() . '/assets/js/tclas-orgs-map.js',
		[ 'leaflet' ],
		filemtime( get_template_directory() . '/assets/js/tclas-orgs-map.js' ),
		true
	);
}

// ── Data ──────────────────────────────────────────────────────────────────────

/**
 * Build the orgs payload. Coordinates optional — online-only orgs get null
 * lat/lng and render in the list only.
 */
function tclas_build_orgs_data(): array {
	$orgs = [];

	$query = new WP_Query( [
		'post_type'      => 'tclas_org',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'no_found_rows'  => true,
	] );

	foreach ( $query->posts as $post ) {
		$lat = get_field( 'org_lat', $post->ID );
		$lng = get_field( 'org_lng', $post->ID );

		$orgs[] = [
			'name'    => html_entity_decode( get_the_title( $post ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
			'city'    => (string) get_field( 'org_city', $post->ID ),
			'lat'     => ( $lat && $lng ) ? (float) $lat : null,
			'lng'     => ( $lat && $lng ) ? (float) $lng : null,
			'blurb'   => (string) get_field( 'org_blurb', $post->ID ),
			'website' => (string) get_field( 'org_website', $post->ID ),
		];
	}

	return $orgs;
}

/**
 * Upcoming external (non-TCLAS) events, soonest first. Past events are
 * excluded in the query itself — ACF date_picker stores Ymd, so a plain
 * string comparison against today works.
 *
 * @return WP_Post[]
 */
function tclas_get_upcoming_ext_events( int $limit = 5 ): array {
	return get_posts( [
		'post_type'      => 'tclas_ext_event',
		'post_status'    => 'publish',
		'posts_per_page' => $limit,
		'meta_key'       => 'event_date',
		'orderby'        => 'meta_value',
		'order'          => 'ASC',
		'meta_query'     => [
			[
				'key'     => 'event_date',
				'value'   => current_time( 'Ymd' ),
				'compare' => '>=',
			],
		],
	] );
}

// ── Shortcode ─────────────────────────────────────────────────────────────────

add_shortcode( 'tclas_orgs_map', 'tclas_orgs_map_shortcode' );

function tclas_orgs_map_shortcode( array $atts = [] ): string {
	$orgs = tclas_build_orgs_data();

	wp_enqueue_style( 'tclas-orgs-map' );
	wp_enqueue_script( 'tclas-orgs-map' );

	// Same tile config as the other maps (Theme Options → Mapbox).
	$mapbox_token    = get_field( 'mapbox_access_token', 'option' );
	$mapbox_style    = get_field( 'mapbox_style_url', 'option' ) ?: 'mapbox://styles/tclas/cmmhutark001u01s98p0uakek';
	$mapbox_tile_url = '';
	if ( $mapbox_token && preg_match( '#^mapbox://styles/(.+)$#', $mapbox_style, $m ) ) {
		$mapbox_tile_url = 'https://api.mapbox.com/styles/v1/' . $m[1] . '/tiles/256/{z}/{x}/{y}@2x?access_token=' . $mapbox_token;
	}

	wp_localize_script( 'tclas-orgs-map', 'tclasOrgsData', [
		'orgs'          => $orgs,
		'mapboxTileUrl' => $mapbox_tile_url,
	] );

	ob_start();
	?>
	<div class="tclas-map-wrapper tclas-map-wrapper--split tclas-orgs-wrapper">
		<div class="tclas-map-split">
			<div class="tclas-map-split__map">
				<div id="tclas-orgs-map"
				     class="tclas-orgs-map"
				     role="img"
				     aria-label="<?php esc_attr_e( 'Map of Luxembourg organizations in North America', 'tclas' ); ?>"></div>
				<p class="tclas-map-caption">
					<?php esc_html_e( 'Luxembourg organizations across North America. Tap a marker or a row to learn more — each links to the group\'s own site.', 'tclas' ); ?>
				</p>
			</div>
			<div class="tclas-map-split__list">
				<div class="tclas-map-split__list-header">
					<span class="tclas-map-split__count" id="tclas-orgs-list-count"></span>
				</div>
				<div class="tclas-map-split__list-scroll">
					<table class="tclas-map-list__table" role="table">
						<thead>
							<tr>
								<th scope="col"><?php esc_html_e( 'Organization', 'tclas' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Location', 'tclas' ); ?></th>
							</tr>
						</thead>
						<tbody id="tclas-orgs-list-body"></tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
