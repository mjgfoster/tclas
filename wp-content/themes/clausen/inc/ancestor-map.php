<?php
/**
 * TCLAS Ancestral Commune Map
 *
 * Shortcode: [tclas_ancestor_map]
 *
 * Renders a Leaflet map centred on Luxembourg with a circle marker for every
 * commune where at least one non-private member has recorded ancestors.
 * Circle radius scales with member count.  Aggregate counts only — no
 * individual names are exposed.
 *
 * Relies on commune coordinate data from inc/commune-data.php (tclas_get_communes()).
 * Uses member `_tclas_communes_norm` user meta (PHP-serialized string[]).
 * Respects `_tclas_visibility`: users set to 'private' are excluded from counts.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── Enqueue ───────────────────────────────────────────────────────────────────

add_action( 'wp_enqueue_scripts', 'tclas_register_ancestor_map_assets' );
function tclas_register_ancestor_map_assets(): void {
    // Leaflet 1.9.4 from cdnjs
    wp_register_style(
        'leaflet',
        'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css',
        [],
        '1.9.4'
    );
    wp_register_script(
        'leaflet',
        'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js',
        [],
        '1.9.4',
        true
    );

    // Our map script + styles
    wp_register_style(
        'tclas-ancestor-map',
        get_template_directory_uri() . '/assets/css/tclas-ancestor-map.css',
        [ 'leaflet' ],
        filemtime( get_template_directory() . '/assets/css/tclas-ancestor-map.css' )
    );
    wp_register_script(
        'tclas-ancestor-map',
        get_template_directory_uri() . '/assets/js/tclas-ancestor-map.js',
        [ 'leaflet' ],
        filemtime( get_template_directory() . '/assets/js/tclas-ancestor-map.js' ),
        true
    );
}

// ── Shortcode ─────────────────────────────────────────────────────────────────

add_shortcode( 'tclas_ancestor_map', 'tclas_ancestor_map_shortcode' );

function tclas_ancestor_map_shortcode( array $atts = [] ): string {
    $atts = shortcode_atts( [ 'height' => '520px', 'public' => false, 'layout' => '' ], $atts, 'tclas_ancestor_map' );
    $is_public  = filter_var( $atts['public'], FILTER_VALIDATE_BOOLEAN );
    $is_split   = ( 'split' === $atts['layout'] );

    // Build commune → {count, surnames} index
    $commune_data = tclas_build_commune_data();

    // Build map data payload (only communes with ≥1 member)
    $all_communes = tclas_get_communes();
    $map_communes = [];

    foreach ( $commune_data as $slug => $d ) {
        if ( ! isset( $all_communes[ $slug ] ) ) continue;
        $c = $all_communes[ $slug ];
        $map_communes[ $slug ] = [
            'name'     => $c['name'],
            'canton'   => $c['canton'],
            'lat'      => (float) $c['lat'],
            'lng'      => (float) $c['lng'],
            'count'    => (int) $d['count'],
            'surnames' => array_values( array_slice( $d['surnames'], 0, 12 ) ), // cap at 12 for popup
        ];
    }

    wp_enqueue_style( 'tclas-ancestor-map' );
    wp_enqueue_script( 'tclas-ancestor-map' );
    // Mapbox config from Theme Options (falls back to CartoDB Positron if not set).
    $mapbox_token = get_field( 'mapbox_access_token', 'option' );
    $mapbox_style = get_field( 'mapbox_style_url', 'option' ) ?: 'mapbox://styles/tclas/cmmhutark001u01s98p0uakek';

    // Convert mapbox://styles/user/id → tile URL path components.
    $mapbox_tile_url = '';
    if ( $mapbox_token && preg_match( '#^mapbox://styles/(.+)$#', $mapbox_style, $m ) ) {
        $mapbox_tile_url = 'https://api.mapbox.com/styles/v1/' . $m[1] . '/tiles/256/{z}/{x}/{y}@2x?access_token=' . $mapbox_token;
    }

    wp_localize_script( 'tclas-ancestor-map', 'tclasMapData', [
        'communes'       => $map_communes,
        'isPublic'       => $is_public,
        'layout'         => $is_split ? 'split' : 'default',
        'joinUrl'        => home_url( '/join/' ),
        'storyUrl'       => home_url( '/member-hub/map-entries/' ),
        'communeBaseUrl' => home_url( '/member-hub/ancestral-map/commune/' ),
        'totalCount'     => array_sum( array_column( $commune_data, 'count' ) ),
        'mapboxTileUrl'  => $mapbox_tile_url,
    ] );

    $height     = esc_attr( $atts['height'] );
    $wrap_class = 'tclas-map-wrapper' . ( $is_split ? ' tclas-map-wrapper--split' : '' );

    ob_start();
    ?>
    <div class="<?php echo esc_attr( $wrap_class ); ?>">

        <?php if ( $is_split ) : ?>
        <!-- Split layout: map + live-filtered list side by side -->
        <div class="tclas-map-split">
            <div class="tclas-map-split__map">
                <div id="tclas-ancestor-map"
                     class="tclas-ancestor-map"
                     role="img"
                     aria-label="<?php esc_attr_e( 'Map of ancestral communes in Luxembourg', 'tclas' ); ?>"></div>
                <p class="tclas-map-caption">
                    <?php esc_html_e( 'Circles mark where TCLAS members trace their ancestors. Larger circles represent a greater concentration of records. Tap or hover for details.', 'tclas' ); ?>
                </p>
            </div>
            <div class="tclas-map-split__list">
                <div class="tclas-map-split__list-header">
                    <input type="search" id="tclas-map-list-search"
                           class="tclas-map-split__search"
                           placeholder="<?php esc_attr_e( 'Search communes or surnames…', 'tclas' ); ?>"
                           autocomplete="off" />
                    <span class="tclas-map-split__count" id="tclas-map-list-count"></span>
                </div>
                <div class="tclas-map-split__list-scroll">
                    <table class="tclas-map-list__table" role="table">
                        <thead>
                            <tr>
                                <th scope="col"><?php esc_html_e( 'Commune', 'tclas' ); ?></th>
                                <th scope="col"><?php esc_html_e( 'Canton', 'tclas' ); ?></th>
                                <th scope="col"><?php esc_html_e( 'Surnames', 'tclas' ); ?></th>
                                <th scope="col"><?php esc_html_e( 'Members', 'tclas' ); ?></th>
                            </tr>
                        </thead>
                        <tbody id="tclas-map-list-body"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php else : ?>
        <!-- Default layout: toggle between map and list -->
        <div class="tclas-map-toolbar">
            <button type="button" class="tclas-map-view-toggle" id="tclas-map-view-toggle"
                    aria-pressed="false"
                    aria-label="<?php esc_attr_e( 'View as list', 'tclas' ); ?>">
                <svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                <span><?php esc_html_e( 'View as list', 'tclas' ); ?></span>
            </button>
        </div>
        <div id="tclas-ancestor-map"
             class="tclas-ancestor-map"
             style="height:<?php echo $height; ?>"
             role="img"
             aria-label="<?php esc_attr_e( 'Map of ancestral communes in Luxembourg', 'tclas' ); ?>"></div>
        <div id="tclas-map-list" class="tclas-map-list" hidden>
            <table class="tclas-map-list__table" role="table">
                <thead>
                    <tr>
                        <th scope="col"><?php esc_html_e( 'Commune', 'tclas' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Canton', 'tclas' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Surnames', 'tclas' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Members', 'tclas' ); ?></th>
                    </tr>
                </thead>
                <tbody id="tclas-map-list-body"></tbody>
            </table>
        </div>
        <?php endif; ?>

    </div>
    <?php
    return ob_get_clean();
}

// ── Data helpers ──────────────────────────────────────────────────────────────

/**
 * Query all users with lineage data and return a slug → {count, surnames[]} array.
 * Collects associated surnames per commune (aggregated, anonymous).
 * Results are cached in a transient for 1 hour.
 *
 * @return array<string, array{count: int, surnames: string[]}>
 */
function tclas_build_commune_data(): array {
    $cached = get_transient( 'tclas_commune_data' );
    if ( is_array( $cached ) ) {
        return $cached;
    }

    // Only count active PMPro members (not expired/cancelled).
    global $wpdb;
    if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}pmpro_memberships_users'" ) ) {
        $users = $wpdb->get_col(
            "SELECT DISTINCT mu.user_id
             FROM {$wpdb->prefix}pmpro_memberships_users mu
             INNER JOIN {$wpdb->usermeta} um
               ON um.user_id = mu.user_id AND um.meta_key = '_tclas_lineages'
             WHERE mu.status = 'active'"
        );
    } else {
        $users = get_users( [
            'meta_key'     => '_tclas_lineages',
            'meta_compare' => 'EXISTS',
            'fields'       => 'ids',
            'number'       => -1,
        ] );
    }

    $data = []; // slug → ['count' => int, 'surnames_set' => [name => true]]

    foreach ( $users as $user_id ) {
        $visibility = get_user_meta( $user_id, '_tclas_visibility', true );
        if ( 'hidden' === $visibility ) {
            continue;
        }

        $lineages = get_user_meta( $user_id, '_tclas_lineages', true );
        $lineages = is_array( $lineages ) ? $lineages : maybe_unserialize( $lineages );
        if ( ! is_array( $lineages ) ) {
            continue;
        }

        foreach ( $lineages as $l ) {
            $slug = sanitize_key( $l['commune_norm'] ?? '' );
            if ( '' === $slug || str_starts_with( $slug, 'unresolved:' ) ) {
                continue;
            }

            if ( ! isset( $data[ $slug ] ) ) {
                $data[ $slug ] = [ 'count' => 0, 'surnames_set' => [] ];
            }
            $data[ $slug ]['count']++;

            // Collect raw surname labels (use the raw display form, not normalised).
            $s_raw = is_array( $l['surnames_raw'] ?? null ) ? $l['surnames_raw'] : [];
            foreach ( $s_raw as $sname ) {
                $sname = trim( $sname );
                if ( '' !== $sname ) {
                    $data[ $slug ]['surnames_set'][ $sname ] = true;
                }
            }
        }
    }

    // Flatten to final shape.
    $result = [];
    foreach ( $data as $slug => $d ) {
        $surnames = array_keys( $d['surnames_set'] );
        sort( $surnames );
        $result[ $slug ] = [
            'count'    => $d['count'],
            'surnames' => $surnames,
        ];
    }

    // Sort by count descending.
    uasort( $result, fn( $a, $b ) => $b['count'] <=> $a['count'] );
    set_transient( 'tclas_commune_data', $result, HOUR_IN_SECONDS );
    return $result;
}

/**
 * Backward-compat wrapper: return just the slug → count array.
 */
function tclas_build_commune_counts(): array {
    $data = tclas_build_commune_data();
    return array_map( fn( $d ) => $d['count'], $data );
}

/**
 * Bust the commune data transient whenever a member updates their story.
 * Hooked in connections.php after profile save.
 */
add_action( 'tclas_member_story_saved', 'tclas_bust_commune_counts_cache' );
function tclas_bust_commune_counts_cache(): void {
    delete_transient( 'tclas_commune_data' );
    delete_transient( 'tclas_commune_counts' ); // legacy key
}
