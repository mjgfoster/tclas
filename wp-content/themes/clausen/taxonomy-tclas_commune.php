<?php
/**
 * Taxonomy template: tclas_commune
 *
 * Commune Profile page — renders the 33%/66% fact column + member grid layout
 * for each Luxembourg commune that has the `tclas_commune` taxonomy term.
 *
 * URL: /commune/{slug}/
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
$name        = $commune['name']         ?? ucwords( str_replace( '-', ' ', $slug ) );
$lux_name    = $commune['lux']          ?? $name;
$municipality = $commune['municipality'] ?? '';
$canton      = $commune['canton']        ?? '';
$lat         = $commune['lat']           ?? null;
$lng         = $commune['lng']           ?? null;

// ACF term meta (requires ACF Pro)
$wikipedia_url = function_exists( 'get_field' )
	? get_field( 'tclas_commune_wikipedia_url', $term )
	: '';
$lux_website   = function_exists( 'get_field' )
	? get_field( 'tclas_commune_lux_website_url', $term )
	: '';

// ── Members with this commune ─────────────────────────────────────────────────
$members = [];
$all_users = get_users( [
	'meta_key'     => '_tclas_communes_norm',
	'meta_compare' => 'EXISTS',
	'fields'       => [ 'ID', 'display_name', 'user_login' ],
	'number'       => -1,
] );
foreach ( $all_users as $u ) {
	$visibility = get_user_meta( $u->ID, '_tclas_visibility', true );
	if ( 'hidden' === $visibility ) {
		continue;
	}
	$norm = get_user_meta( $u->ID, '_tclas_communes_norm', true );
	$norm = is_array( $norm ) ? $norm : maybe_unserialize( $norm );
	if ( is_array( $norm ) && in_array( $slug, $norm, true ) ) {
		$members[] = $u;
	}
}
?>

<div class="tclas-page-header">
	<div class="container-tclas">
		<span class="tclas-eyebrow">
			<?php echo esc_html( $canton ); ?>
		</span>
		<h1 class="tclas-page-header__title">
			<?php echo esc_html( $name ); ?>
			<?php if ( $lux_name && $lux_name !== $name ) : ?>
				<small style="font-size:.55em;opacity:.75;display:block;font-weight:400">
					<span lang="lb"><?php echo esc_html( $lux_name ); ?></span>
				</small>
			<?php endif; ?>
		</h1>
	</div>
</div>

<section class="tclas-section">
	<div class="container-tclas">
		<div class="tclas-commune-layout">

			<!-- ── Fact Column (33%) ─────────────────────────────────── -->
			<aside class="tclas-commune-fact-col">

				<?php if ( $lat && $lng ) : ?>
				<!-- Mini Leaflet map centred on this commune -->
				<div id="tclas-commune-mini-map" class="tclas-commune-mini-map"
					data-lat="<?php echo esc_attr( $lat ); ?>"
					data-lng="<?php echo esc_attr( $lng ); ?>">
				</div>
				<?php endif; ?>

				<?php if ( $municipality ) : ?>
				<span class="tclas-commune-fact-label"><?php esc_html_e( 'Municipality', 'tclas' ); ?></span>
				<p style="margin:0 0 .75rem;font-size:.9rem;"><?php echo esc_html( $municipality ); ?></p>
				<?php endif; ?>

				<?php if ( $canton ) : ?>
				<span class="tclas-commune-fact-label"><?php esc_html_e( 'Canton', 'tclas' ); ?></span>
				<p style="margin:0 0 .75rem;font-size:.9rem;"><?php echo esc_html( $canton ); ?></p>
				<?php endif; ?>

				<?php
				// LOD.lu audio — uses Luxembourgish name for best match
				echo tclas_commune_audio_html( $lux_name ?: $name, $slug ); // phpcs:ignore
				?>

				<?php if ( $wikipedia_url || $lux_website ) : ?>
				<span class="tclas-commune-fact-label"><?php esc_html_e( 'Links', 'tclas' ); ?></span>
				<div class="tclas-commune-ext-links">
					<?php if ( $wikipedia_url ) : ?>
					<a href="<?php echo esc_url( $wikipedia_url ); ?>" class="tclas-commune-ext-link" target="_blank" rel="noopener noreferrer">
						📖 <?php esc_html_e( 'Wikipedia', 'tclas' ); ?>
					</a>
					<?php endif; ?>
					<?php if ( $lux_website ) : ?>
					<a href="<?php echo esc_url( $lux_website ); ?>" class="tclas-commune-ext-link" target="_blank" rel="noopener noreferrer">
						🌐 <?php esc_html_e( 'Official website', 'tclas' ); ?>
					</a>
					<?php endif; ?>
				</div>
				<?php endif; ?>

				<div style="margin-top:1.5rem">
					<a href="<?php echo esc_url( home_url( '/ancestry/' ) ); ?>" class="btn btn-sm btn-outline-ardoise">
						<?php esc_html_e( '← View all communes on map', 'tclas' ); ?>
					</a>
				</div>

			</aside><!-- .tclas-commune-fact-col -->

			<!-- ── Member Grid (66%) ─────────────────────────────────── -->
			<div class="tclas-commune-member-col">

				<h2 class="tclas-story-legend" style="margin-bottom:1rem">
					<?php
					if ( empty( $members ) ) {
						esc_html_e( 'TCLAS members with ancestry here', 'tclas' );
					} else {
						printf(
							/* translators: %1$s: count, %2$s: commune name */
							esc_html( _n(
								'%1$s TCLAS member traces ancestry to %2$s',
								'%1$s TCLAS members trace ancestry to %2$s',
								count( $members ),
								'tclas'
							) ),
							number_format_i18n( count( $members ) ),
							esc_html( $name )
						);
					}
					?>
				</h2>

				<?php if ( empty( $members ) ) : ?>

				<div class="tclas-commune-empty">
					<p><?php esc_html_e( 'No members have recorded this commune yet.', 'tclas' ); ?></p>
					<?php if ( tclas_is_member() ) : ?>
					<a href="<?php echo esc_url( home_url( '/member-hub/my-story/' ) ); ?>" class="btn btn-sm btn-primary">
						<?php printf(
							/* translators: %s: commune name */
							esc_html__( '+ Add %s to my story', 'tclas' ),
							esc_html( $name )
						); ?>
					</a>
					<?php else : ?>
					<a href="<?php echo esc_url( home_url( '/member-hub/my-story/' ) ); ?>" class="btn btn-sm btn-outline-ardoise">
						<?php esc_html_e( 'Join TCLAS to add your ancestry', 'tclas' ); ?>
					</a>
					<?php endif; ?>
				</div>

				<?php else : ?>

				<div class="tclas-commune-member-grid">
					<?php foreach ( $members as $u ) : ?>
					<a href="<?php echo esc_url( home_url( '/member-hub/profiles/' . rawurlencode( $u->user_login ) . '/' ) ); ?>"
					   class="tclas-commune-member-card">
						<?php echo get_avatar( $u->ID, 40, '', '', [ 'class' => '' ] ); ?>
						<span><?php echo esc_html( $u->display_name ); ?></span>
					</a>
					<?php endforeach; ?>
				</div>

				<?php if ( tclas_is_member() ) : ?>
				<?php
				// Check if current user is already in the list
				$current_id = get_current_user_id();
				$in_list    = in_array( $current_id, array_column( $members, 'ID' ), true );
				if ( ! $in_list ) :
				?>
				<p style="margin-top:1rem;font-size:.88rem;color:var(--c-muted)">
					<?php esc_html_e( 'Don\'t see yourself? ', 'tclas' ); ?>
					<a href="<?php echo esc_url( home_url( '/member-hub/my-story/' ) ); ?>">
						<?php printf(
							/* translators: %s: commune name */
							esc_html__( 'Add %s to your story →', 'tclas' ),
							esc_html( $name )
						); ?>
					</a>
				</p>
				<?php endif; ?>
				<?php endif; ?>

				<?php endif; ?>

			</div><!-- .tclas-commune-member-col -->

		</div><!-- .tclas-commune-layout -->
	</div><!-- .container-tclas -->
</section>

<?php if ( $lat && $lng ) : ?>
<!-- Mini map init — only if we have coordinates -->
<script>
(function () {
  'use strict';
  document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('tclas-commune-mini-map');
    if (!el || typeof L === 'undefined') return;
    var lat = parseFloat(el.dataset.lat);
    var lng = parseFloat(el.dataset.lng);
    var miniMap = L.map('tclas-commune-mini-map', {
      center:          [lat, lng],
      zoom:            13,
      zoomControl:     false,
      scrollWheelZoom: false,
      dragging:        false,
      doubleClickZoom: false,
      boxZoom:         false,
      keyboard:        false,
      tap:             false,
    });
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
      attribution: '&copy; CARTO',
      subdomains: 'abcd',
      maxZoom: 19,
    }).addTo(miniMap);
    L.circleMarker([lat, lng], {
      radius: 9, fillColor: '#E31E26', color: '#0A2540',
      weight: 2, opacity: 1, fillOpacity: 0.85
    }).addTo(miniMap);
  });
})();
</script>
<?php endif; ?>

<?php
// Enqueue Leaflet for the mini map
wp_enqueue_style( 'leaflet' );
wp_enqueue_script( 'leaflet' );
?>

<?php get_footer(); ?>
