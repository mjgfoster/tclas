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
        TCLAS_VERSION
    );
    wp_register_script(
        'tclas-ancestor-map',
        get_template_directory_uri() . '/assets/js/tclas-ancestor-map.js',
        [ 'leaflet' ],
        TCLAS_VERSION,
        true
    );
}

// ── Shortcode ─────────────────────────────────────────────────────────────────

add_shortcode( 'tclas_ancestor_map', 'tclas_ancestor_map_shortcode' );

function tclas_ancestor_map_shortcode( array $atts = [] ): string {
    $atts = shortcode_atts( [ 'height' => '520px' ], $atts, 'tclas_ancestor_map' );

    // Build commune → count index
    $commune_counts = tclas_build_commune_counts();

    // Build map data payload (only communes with ≥1 member)
    $all_communes = tclas_get_communes();
    $map_communes = [];

    foreach ( $commune_counts as $slug => $count ) {
        if ( ! isset( $all_communes[ $slug ] ) ) continue;
        $c = $all_communes[ $slug ];
        $map_communes[ $slug ] = [
            'name'   => $c['name'],
            'canton' => $c['canton'],
            'lat'    => (float) $c['lat'],
            'lng'    => (float) $c['lng'],
            'count'  => (int) $count,
        ];
    }

    wp_enqueue_style( 'tclas-ancestor-map' );
    wp_enqueue_script( 'tclas-ancestor-map' );
    wp_localize_script( 'tclas-ancestor-map', 'tclasMapData', [
        'communes'       => $map_communes,
        'storyUrl'       => home_url( '/member-hub/my-story/' ),
        'communeBaseUrl' => home_url( '/commune/' ),
        'totalCount'     => array_sum( $commune_counts ),
    ] );

    $height = esc_attr( $atts['height'] );

    ob_start();
    ?>
    <div class="tclas-map-wrapper">
        <div id="tclas-ancestor-map"
             class="tclas-ancestor-map"
             style="height:<?php echo $height; ?>"
             role="img"
             aria-label="Map of ancestral communes in Luxembourg"></div>
        <p class="tclas-map-caption">
            Circles mark Luxembourg villages where TCLAS members trace their ancestry.
            Larger circles = more members. Tap or hover for details.
        </p>
    </div>
    <?php
    return ob_get_clean();
}

// ── Data helpers ──────────────────────────────────────────────────────────────

/**
 * Query all users with commune data and return a slug → count array.
 * Results are cached in a transient for 1 hour.
 */
function tclas_build_commune_counts(): array {
    $cached = get_transient( 'tclas_commune_counts' );
    if ( is_array( $cached ) ) {
        return $cached;
    }

    $users = get_users( [
        'meta_key'     => '_tclas_communes_norm',
        'meta_compare' => 'EXISTS',
        'fields'       => 'ids',
        'number'       => -1,
    ] );

    $counts = [];

    foreach ( $users as $user_id ) {
        // Exclude hidden profiles from the aggregate ('hidden' is the canonical _tclas_visibility value)
        $visibility = get_user_meta( $user_id, '_tclas_visibility', true );
        if ( 'hidden' === $visibility ) {
            continue;
        }

        $raw = get_user_meta( $user_id, '_tclas_communes_norm', true );
        $communes = is_array( $raw ) ? $raw : maybe_unserialize( $raw );
        if ( ! is_array( $communes ) ) {
            continue;
        }

        foreach ( $communes as $slug ) {
            $slug = sanitize_key( $slug );
            if ( '' === $slug || str_starts_with( $slug, 'unresolved:' ) ) {
                continue;
            }
            $counts[ $slug ] = ( $counts[ $slug ] ?? 0 ) + 1;
        }
    }

    arsort( $counts );
    set_transient( 'tclas_commune_counts', $counts, HOUR_IN_SECONDS );
    return $counts;
}

/**
 * Bust the commune counts transient whenever a member updates their story.
 * Hooked in connections.php after profile save.
 */
add_action( 'tclas_member_story_saved', 'tclas_bust_commune_counts_cache' );
function tclas_bust_commune_counts_cache(): void {
    delete_transient( 'tclas_commune_counts' );
}
