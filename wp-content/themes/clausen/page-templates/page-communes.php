<?php
/**
 * Template Name: Communes Index
 *
 * A–Z directory of all Luxembourg communes with official name,
 * Luxembourgish name, and canton. Lives at /member-hub/ancestral-map/commune/.
 *
 * @package TCLAS
 */

get_header();

$communes = function_exists( 'tclas_get_communes' ) ? tclas_get_communes() : [];
$commune_base = home_url( '/member-hub/ancestral-map/commune/' );

// Group by first letter of official name
$grouped = [];
foreach ( $communes as $slug => $c ) {
	$letter = mb_strtoupper( mb_substr( $c['name'], 0, 1 ) );
	$grouped[ $letter ][] = [ 'slug' => $slug ] + $c;
}
ksort( $grouped );
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
			<span class="tclas-breadcrumb__current" aria-current="page"><?php esc_html_e( 'Communes', 'tclas' ); ?></span>
		</nav>
		<span class="tclas-eyebrow"><?php esc_html_e( 'Directory', 'tclas' ); ?></span>
		<h1 class="tclas-page-header__title"><?php esc_html_e( 'Communes of Luxembourg', 'tclas' ); ?></h1>
		<p class="tclas-commune-subtitle"><?php echo count( $communes ); ?> <?php esc_html_e( 'localities', 'tclas' ); ?></p>
	</div>
</div>

<section class="tclas-section">
	<div class="container-tclas">

		<!-- Letter nav -->
		<nav class="tclas-commune-index__letters" aria-label="<?php esc_attr_e( 'Jump to letter', 'tclas' ); ?>">
			<?php foreach ( $grouped as $letter => $_ ) : ?>
			<a href="#letter-<?php echo esc_attr( $letter ); ?>"><?php echo esc_html( $letter ); ?></a>
			<?php endforeach; ?>
		</nav>

		<!-- Table -->
		<table class="tclas-commune-index__table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'tclas' ); ?></th>
					<th><?php esc_html_e( 'Lëtzebuergesch', 'tclas' ); ?></th>
					<th><?php esc_html_e( 'Canton', 'tclas' ); ?></th>
				</tr>
			</thead>
			<?php foreach ( $grouped as $letter => $entries ) : ?>
			<tbody id="letter-<?php echo esc_attr( $letter ); ?>">
				<tr class="tclas-commune-index__letter-row">
					<td colspan="3"><?php echo esc_html( $letter ); ?></td>
				</tr>
				<?php foreach ( $entries as $c ) :
				$row_url = esc_url( $commune_base . $c['slug'] . '/' );
			?>
				<tr data-href="<?php echo $row_url; ?>">
					<td><a href="<?php echo $row_url; ?>"><?php echo esc_html( $c['name'] ); ?></a></td>
					<td lang="lb"><?php echo esc_html( $c['lux'] ?? '' ); ?></td>
					<td><?php echo esc_html( $c['canton'] ?? '' ); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
			<?php endforeach; ?>
		</table>

		<a href="<?php echo esc_url( home_url( '/member-hub/ancestral-map/' ) ); ?>" class="tclas-commune-back-link">
			&larr; <?php esc_html_e( 'Back to map', 'tclas' ); ?>
		</a>

	</div>
</section>

<script>
document.querySelectorAll('.tclas-commune-index__table tr[data-href]').forEach(function (tr) {
  tr.addEventListener('click', function (e) {
    if (e.target.tagName === 'A') return;
    window.location = tr.dataset.href;
  });
});
</script>

<?php get_footer(); ?>
