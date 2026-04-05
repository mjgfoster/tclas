<?php
/**
 * Taxonomy template: tclas_commune
 *
 * Commune profile page — dashboard-style layout with fact sidebar (mini map,
 * pronunciation, metadata) and members grouped by ancestral surname.
 *
 * URL: /member-hub/ancestral-map/commune/{slug}/
 *
 * @package TCLAS
 */

get_header();

$term = get_queried_object();
if ( ! $term || ! ( $term instanceof WP_Term ) ) {
	wp_redirect( home_url( '/' ) );
	exit;
}

$slug     = $term->slug;
$communes = function_exists( 'tclas_get_communes' ) ? tclas_get_communes() : [];
$commune  = $communes[ $slug ] ?? null;

// Commune metadata
$name         = $commune['name']         ?? ucwords( str_replace( '-', ' ', $slug ) );
$lux_name     = $commune['lux']          ?? $name;
$municipality = $commune['municipality'] ?? '';
$canton       = $commune['canton']       ?? '';
$lat          = $commune['lat']          ?? null;
$lng          = $commune['lng']          ?? null;

// ACF term meta (requires ACF Pro)
$wikipedia_url = function_exists( 'get_field' )
	? get_field( 'tclas_commune_wikipedia_url', $term )
	: '';
$lux_website   = function_exists( 'get_field' )
	? get_field( 'tclas_commune_lux_website_url', $term )
	: '';

// ── Members with this commune, grouped by surname ───────────────────────────
$surname_groups = []; // surname => [ user objects ]
$ungrouped      = []; // members at this commune but no surname recorded for it
$member_count   = 0;

$all_users = get_users( [
	'meta_key'     => '_tclas_lineages',
	'meta_compare' => 'EXISTS',
	'fields'       => [ 'ID', 'display_name', 'user_login' ],
	'number'       => -1,
] );

foreach ( $all_users as $u ) {
	$visibility = get_user_meta( $u->ID, '_tclas_visibility', true );
	if ( 'hidden' === $visibility ) {
		continue;
	}

	$lineages = get_user_meta( $u->ID, '_tclas_lineages', true );
	$lineages = is_array( $lineages ) ? $lineages : maybe_unserialize( $lineages );
	if ( ! is_array( $lineages ) ) {
		continue;
	}

	// Find lineage entries for this commune
	$found_surnames = [];
	foreach ( $lineages as $l ) {
		if ( ( $l['commune_norm'] ?? '' ) !== $slug ) {
			continue;
		}
		$raw = is_array( $l['surnames_raw'] ?? null ) ? $l['surnames_raw'] : [];
		foreach ( $raw as $s ) {
			$s = trim( $s );
			if ( '' !== $s ) {
				$found_surnames[] = $s;
			}
		}
	}

	if ( empty( $found_surnames ) ) {
		// Check _tclas_communes_norm as fallback (member has commune but no surnames)
		$cnorm = get_user_meta( $u->ID, '_tclas_communes_norm', true );
		$cnorm = is_array( $cnorm ) ? $cnorm : maybe_unserialize( $cnorm );
		if ( is_array( $cnorm ) && in_array( $slug, $cnorm, true ) ) {
			$ungrouped[] = $u;
			$member_count++;
		}
		continue;
	}

	$member_count++;
	foreach ( $found_surnames as $s ) {
		$surname_groups[ $s ][] = $u;
	}
}

// Sort surname groups alphabetically
ksort( $surname_groups, SORT_NATURAL | SORT_FLAG_CASE );

// Mapbox config for mini map
$mapbox_token    = function_exists( 'get_field' ) ? get_field( 'mapbox_access_token', 'option' ) : '';
$mapbox_style    = function_exists( 'get_field' ) ? ( get_field( 'mapbox_style_url', 'option' ) ?: 'mapbox://styles/tclas/cmmhutark001u01s98p0uakek' ) : '';
$mapbox_tile_url = '';
if ( $mapbox_token && preg_match( '#^mapbox://styles/(.+)$#', $mapbox_style, $m ) ) {
	$mapbox_tile_url = 'https://api.mapbox.com/styles/v1/' . $m[1] . '/tiles/256/{z}/{x}/{y}@2x?access_token=' . $mapbox_token;
}

// Enqueue Leaflet
wp_enqueue_style( 'leaflet' );
wp_enqueue_script( 'leaflet' );
?>

<div class="tclas-page-header">
	<div class="container-tclas">
		<nav class="tclas-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'tclas' ); ?>">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'tclas' ); ?></a>
			<span class="tclas-breadcrumb__sep" aria-hidden="true">›</span>
			<a href="<?php echo esc_url( home_url( '/member-hub/' ) ); ?>"><?php esc_html_e( 'Member Hub', 'tclas' ); ?></a>
			<span class="tclas-breadcrumb__sep" aria-hidden="true">›</span>
			<a href="<?php echo esc_url( home_url( '/member-hub/ancestral-map/' ) ); ?>"><?php esc_html_e( 'Ancestral Map', 'tclas' ); ?></a>
			<span class="tclas-breadcrumb__sep" aria-hidden="true">›</span>
			<a href="<?php echo esc_url( home_url( '/member-hub/ancestral-map/commune/' ) ); ?>"><?php esc_html_e( 'Communes', 'tclas' ); ?></a>
			<span class="tclas-breadcrumb__sep" aria-hidden="true">›</span>
			<span class="tclas-breadcrumb__current" aria-current="page"><?php echo esc_html( $name ); ?></span>
		</nav>
		<h1 class="tclas-page-header__title"><?php echo esc_html( $name ); ?></h1>
		<?php if ( $canton ) : ?>
		<p class="tclas-commune-subtitle"><?php
			// Show municipality in subtitle when it differs from the place name
			if ( $municipality && mb_strtolower( $municipality ) !== mb_strtolower( $name ) ) {
				echo esc_html( $municipality ) . ', ';
			}
			echo esc_html( $canton );
		?> Canton</p>
		<?php endif; ?>
	</div>
</div>

<section class="tclas-section">
	<div class="container-tclas">
		<div class="tclas-commune-layout">

			<!-- ── Col 1: Fact Column ─────────────────────────────────── -->
			<aside class="tclas-commune-fact-col">

				<?php
				// LOD.lu audio — uses Luxembourgish name for best match
				echo tclas_commune_audio_html( $lux_name ?: $name, $slug ); // phpcs:ignore
				?>

				<dl class="tclas-commune-facts">
					<?php if ( $municipality ) : ?>
					<dt><span lang="lb">Gemeng</span> <span class="tclas-commune-facts__en">Municipality</span></dt>
					<dd>
						<?php echo esc_html( $municipality ); ?>
						<?php if ( $wikipedia_url || $lux_website ) : ?>
						<span class="tclas-commune-gemeng-links">
							<?php if ( $lux_website ) : ?>
							<a href="<?php echo esc_url( $lux_website ); ?>" class="tclas-commune-ext-link" target="_blank" rel="noopener noreferrer">
								Official site ↗
							</a>
							<?php endif; ?>
							<?php if ( $wikipedia_url ) : ?>
							<a href="<?php echo esc_url( $wikipedia_url ); ?>" class="tclas-commune-ext-link" target="_blank" rel="noopener noreferrer">
								Wikipedia ↗
							</a>
							<?php endif; ?>
						</span>
						<?php endif; ?>
					</dd>
					<?php endif; ?>

					<?php if ( $canton ) : ?>
					<dt><span lang="lb">Kanton</span> <span class="tclas-commune-facts__en">Canton</span></dt>
					<dd><?php echo esc_html( $canton ); ?></dd>
					<?php endif; ?>

					<dt><span lang="lb">Memberen</span> <span class="tclas-commune-facts__en">Members</span></dt>
					<dd><?php echo (int) $member_count; ?></dd>

					<?php if ( count( $surname_groups ) > 0 ) : ?>
					<dt><span lang="lb">Familljennimm</span> <span class="tclas-commune-facts__en">Family names</span></dt>
					<dd><?php echo count( $surname_groups ); ?></dd>
					<?php endif; ?>
				</dl>

				<a href="<?php echo esc_url( home_url( '/member-hub/ancestral-map/' ) ); ?>" class="tclas-commune-back-link">
					&larr; <?php esc_html_e( 'Back to map', 'tclas' ); ?>
				</a>

			</aside>

			<!-- ── Col 2: Map ─────────────────────────────────────────── -->
			<div class="tclas-commune-map-col">
				<?php if ( $lat && $lng ) : ?>
				<div id="tclas-commune-mini-map" class="tclas-commune-mini-map"
					data-lat="<?php echo esc_attr( $lat ); ?>"
					data-lng="<?php echo esc_attr( $lng ); ?>"
					data-tile-url="<?php echo esc_attr( $mapbox_tile_url ); ?>">
				</div>
				<?php endif; ?>
			</div>

			<!-- ── Col 3: Members grouped by surname ──────────────────── -->
			<div class="tclas-commune-member-col">

				<h2 class="tclas-commune-section-title">
					<?php
					if ( 0 === $member_count ) {
						esc_html_e( 'TCLAS members with ancestry here', 'tclas' );
					} else {
						printf(
							/* translators: %1$s: count, %2$s: commune name */
							esc_html( _n(
								'%1$s TCLAS member traces ancestry to %2$s',
								'%1$s TCLAS members trace ancestry to %2$s',
								$member_count,
								'tclas'
							) ),
							number_format_i18n( $member_count ),
							esc_html( $name )
						);
					}
					?>
				</h2>

				<?php if ( 0 === $member_count ) : ?>

				<div class="tclas-commune-empty">
					<p><?php esc_html_e( 'No members have recorded this commune yet.', 'tclas' ); ?></p>
					<?php if ( tclas_is_member() ) : ?>
					<a href="<?php echo esc_url( home_url( '/member-hub/my-story/' ) ); ?>" class="btn btn-sm btn-primary">
						<?php printf(
							esc_html__( '+ Add %s to my story', 'tclas' ),
							esc_html( $name )
						); ?>
					</a>
					<?php else : ?>
					<a href="<?php echo esc_url( home_url( '/join/' ) ); ?>" class="btn btn-sm btn-outline-ardoise">
						<?php esc_html_e( 'Join TCLAS to add your ancestry', 'tclas' ); ?>
					</a>
					<?php endif; ?>
				</div>

				<?php else : ?>

				<?php foreach ( $surname_groups as $surname => $users ) : ?>
				<div class="tclas-surname-group">
					<h3 class="tclas-surname-group__heading"><?php echo esc_html( $surname ); ?></h3>
					<ul class="tclas-surname-group__list">
						<?php
						// Deduplicate (a member can appear under multiple surnames)
						$seen = [];
						foreach ( $users as $u ) :
							if ( isset( $seen[ $u->ID ] ) ) continue;
							$seen[ $u->ID ] = true;
						?>
						<li class="tclas-surname-group__member">
							<a href="<?php echo esc_url( home_url( '/member-hub/profiles/' . rawurlencode( $u->user_login ) . '/' ) ); ?>"
							   class="tclas-surname-group__link"><?php echo esc_html( $u->display_name ); ?></a>
						</li>
						<?php endforeach; ?>
					</ul>
				</div>
				<?php endforeach; ?>

				<?php if ( ! empty( $ungrouped ) ) : ?>
				<div class="tclas-surname-group tclas-surname-group--other">
					<h3 class="tclas-surname-group__heading"><?php esc_html_e( 'Other members', 'tclas' ); ?></h3>
					<ul class="tclas-surname-group__list">
						<?php foreach ( $ungrouped as $u ) : ?>
						<li class="tclas-surname-group__member">
							<a href="<?php echo esc_url( home_url( '/member-hub/profiles/' . rawurlencode( $u->user_login ) . '/' ) ); ?>"
							   class="tclas-surname-group__link"><?php echo esc_html( $u->display_name ); ?></a>
						</li>
						<?php endforeach; ?>
					</ul>
				</div>
				<?php endif; ?>

				<?php if ( tclas_is_member() ) : ?>
				<?php
				$current_id = get_current_user_id();
				// Check if current user is in any group
				$in_list = false;
				foreach ( $surname_groups as $users ) {
					foreach ( $users as $u ) {
						if ( (int) $u->ID === $current_id ) { $in_list = true; break 2; }
					}
				}
				if ( ! $in_list ) {
					foreach ( $ungrouped as $u ) {
						if ( (int) $u->ID === $current_id ) { $in_list = true; break; }
					}
				}
				if ( ! $in_list ) :
				?>
				<p class="tclas-commune-add-prompt">
					<?php esc_html_e( 'Have roots here? ', 'tclas' ); ?>
					<a href="<?php echo esc_url( home_url( '/member-hub/my-story/' ) ); ?>">
						<?php printf(
							esc_html__( 'Add %s to your story &rarr;', 'tclas' ),
							esc_html( $name )
						); ?>
					</a>
				</p>
				<?php endif; ?>
				<?php endif; ?>

				<?php endif; ?>

			</div>

		</div>
	</div>
</section>

<?php if ( $lat && $lng ) : ?>
<script>
(function () {
  'use strict';
  document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('tclas-commune-mini-map');
    if (!el || typeof L === 'undefined') return;
    var lat = parseFloat(el.dataset.lat);
    var lng = parseFloat(el.dataset.lng);
    var tileUrl = el.dataset.tileUrl;
    // Show all of Luxembourg for geographic context, with a marker on this commune
    var luxCenter = [49.815, 6.13];
    var miniMap = L.map('tclas-commune-mini-map', {
      center:          luxCenter,
      zoom:            9,
      zoomControl:     false,
      scrollWheelZoom: false,
      dragging:        false,
      doubleClickZoom: false,
      boxZoom:         false,
      keyboard:        false,
      tap:             false,
    });
    if (tileUrl) {
      L.tileLayer(tileUrl, {
        attribution: '&copy; Mapbox &copy; OpenStreetMap',
        tileSize: 256, maxZoom: 18,
      }).addTo(miniMap);
    } else {
      L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; CARTO',
        subdomains: 'abcd', maxZoom: 19,
      }).addTo(miniMap);
    }
    L.circleMarker([lat, lng], {
      radius: 8, fillColor: '#8B3A3A', color: '#FFFFFF',
      weight: 2, opacity: 1, fillOpacity: 0.9
    }).addTo(miniMap);
  });
})();
</script>
<?php endif; ?>

<script>
(function () {
  'use strict';
  document.addEventListener('DOMContentLoaded', function () {
    var currentAudio = null;
    var currentBtn   = null;
    document.querySelectorAll('.tclas-commune-pronun__play').forEach(function (btn) {
      btn.addEventListener('click', function () {
        if (currentAudio && currentBtn === btn) {
          currentAudio.pause();
          currentAudio = null;
          currentBtn   = null;
          btn.classList.remove('is-playing');
          return;
        }
        if (currentAudio) {
          currentAudio.pause();
          currentBtn.classList.remove('is-playing');
        }
        var audio = new Audio(btn.dataset.audioSrc);
        btn.classList.add('is-playing');
        currentAudio = audio;
        currentBtn   = btn;
        audio.addEventListener('ended', function () {
          btn.classList.remove('is-playing');
          currentAudio = null;
          currentBtn   = null;
        });
        audio.play();
      });
    });
  });
})();
</script>

<?php get_footer(); ?>
