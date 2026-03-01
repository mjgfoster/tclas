<?php
/**
 * Template Name: Member Profile
 *
 * Public (member-gated) view of a single member's profile.
 * URL: /member-hub/profile/?member={ID}
 *
 * Layout: 33% identity sidebar / 66% story main column.
 *
 * @package TCLAS
 */

get_header();

// ── Guards ────────────────────────────────────────────────────────────────────

// Resolve directory URL dynamically to avoid hardcoding.
$_dir_page = get_page_by_path( 'directory' );
$_dir_url  = $_dir_page ? get_permalink( $_dir_page->ID ) : home_url( '/directory/' );

if ( ! tclas_is_member() ) {
	wp_redirect( $_dir_url );
	exit;
}

$member_id = (int) ( $_GET['member'] ?? 0 );

if ( $member_id <= 0 ) {
	wp_redirect( $_dir_url );
	exit;
}

$member = get_userdata( $member_id );
if ( ! $member ) {
	wp_redirect( $_dir_url );
	exit;
}

// Respect privacy — hidden members are not viewable (admins bypass).
$visibility = get_user_meta( $member_id, '_tclas_visibility', true ) ?: 'members';
if ( 'hidden' === $visibility && ! current_user_can( 'manage_options' ) ) {
	wp_redirect( $_dir_url );
	exit;
}

// ── Pull member data ──────────────────────────────────────────────────────────

$bio           = (string) ( get_user_meta( $member_id, '_tclas_bio',           true ) ?: '' );
$surnames      = array_filter( (array) ( get_user_meta( $member_id, '_tclas_surnames_raw', true ) ?: [] ) );
$communes_raw  = array_filter( (array) ( get_user_meta( $member_id, '_tclas_communes_raw', true ) ?: [] ) );
$trips         = (array) ( get_user_meta( $member_id, '_tclas_trips', true ) ?: [] );
$facebook_url  = (string) ( get_user_meta( $member_id, '_tclas_facebook_url',  true ) ?: '' );
$linkedin_url  = (string) ( get_user_meta( $member_id, '_tclas_linkedin_url',  true ) ?: '' );
$instagram_url = (string) ( get_user_meta( $member_id, '_tclas_instagram_url', true ) ?: '' );

// ── Member since ──────────────────────────────────────────────────────────────

$member_since = '';
if ( function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
	$level = pmpro_getMembershipLevelForUser( $member_id );
	if ( $level ) {
		$start_ts = (int) get_user_meta( $member_id, 'pmpro_member_startdate', true );
		if ( $start_ts ) {
			$member_since = date_i18n( 'F Y', $start_ts );
		}
	}
}
if ( ! $member_since ) {
	$member_since = date_i18n( 'F Y', strtotime( $member->user_registered ) );
}

// ── Resolve commune slugs for profile links ───────────────────────────────────

$all_communes  = function_exists( 'tclas_get_communes' ) ? tclas_get_communes() : [];
$commune_links = []; // [ slug => display_name ]

foreach ( $communes_raw as $raw ) {
	$raw = trim( $raw );
	if ( '' === $raw ) {
		continue;
	}
	$slug       = sanitize_title( $raw );
	$found_name = $raw;

	foreach ( $all_communes as $cslug => $cdata ) {
		if (
			$cslug === $slug
			|| 0 === strcasecmp( $cdata['name'] ?? '', $raw )
			|| 0 === strcasecmp( $cdata['lux']  ?? '', $raw )
		) {
			$slug       = $cslug;
			$found_name = $cdata['name'] ?? $raw;
			break;
		}
	}

	$commune_links[ $slug ] = $found_name;
}

// ── Recent trips (last 3 with a date, newest-ish last) ────────────────────────

$dated_trips  = array_filter( $trips, fn( $t ) => '' !== ( $t['month_year'] ?? '' ) );
$recent_trips = array_slice( array_reverse( $dated_trips ), 0, 3 );

$purpose_labels = [
	'heritage' => __( 'Heritage research',    'tclas' ),
	'family'   => __( 'Family visit',         'tclas' ),
	'tourism'  => __( 'Tourism / vacation',   'tclas' ),
	'tclas'    => __( 'TCLAS event',          'tclas' ),
	'business' => __( 'Business',             'tclas' ),
	'other'    => __( 'Other',                'tclas' ),
];

$is_own_profile = ( get_current_user_id() === $member_id );
?>

<div class="tclas-page-header tclas-page-header--ardoise">
	<div class="container-tclas">
		<span class="tclas-eyebrow tclas-eyebrow--light"><?php esc_html_e( 'Community', 'tclas' ); ?></span>
		<h1 class="tclas-page-header__title"><?php echo esc_html( $member->display_name ); ?></h1>
	</div>
</div>

<section class="tclas-section">
	<div class="container-tclas">

		<div class="tclas-profile-layout">

			<!-- ── Identity sidebar (33%) ──────────────────────────────── -->
			<aside class="tclas-profile-sidebar">

				<!-- Avatar -->
				<div class="tclas-profile-avatar-wrap">
					<?php echo get_avatar( $member_id, 96, '', esc_attr( $member->display_name ), [ 'class' => 'tclas-profile-avatar' ] ); ?>
				</div>

				<!-- Display name + greeting -->
				<h2 class="tclas-profile-name"><?php echo esc_html( $member->display_name ); ?></h2>
				<p class="tclas-profile-greeting">
					<?php echo tclas_lux_greeting(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</p>

				<!-- Ancestral surnames as pill tags -->
				<?php if ( $surnames ) : ?>
				<div class="tclas-profile-surnames">
					<h3 class="tclas-profile-section-label"><?php esc_html_e( 'Family surnames', 'tclas' ); ?></h3>
					<div class="tclas-profile-pill-group">
						<?php foreach ( $surnames as $surname ) : ?>
							<span class="tclas-profile-pill"><?php echo esc_html( $surname ); ?></span>
						<?php endforeach; ?>
					</div>
				</div>
				<?php endif; ?>

				<!-- Ancestral communes — clickable tags linking to /commune/[slug]/ -->
				<?php if ( $commune_links ) : ?>
				<div class="tclas-profile-communes">
					<h3 class="tclas-profile-section-label"><?php esc_html_e( 'Ancestral communes', 'tclas' ); ?></h3>
					<div class="tclas-profile-commune-tags">
						<?php foreach ( $commune_links as $cslug => $cname ) : ?>
							<a
								href="<?php echo esc_url( home_url( '/commune/' . $cslug . '/' ) ); ?>"
								class="tclas-profile-commune-tag"
							><?php echo esc_html( $cname ); ?></a>
						<?php endforeach; ?>
					</div>
				</div>
				<?php endif; ?>

				<!-- Social icon links (only shown when URL is saved) -->
				<?php if ( $facebook_url || $linkedin_url || $instagram_url ) : ?>
				<div class="tclas-profile-social">
					<?php if ( $facebook_url ) : ?>
					<a href="<?php echo esc_url( $facebook_url ); ?>" class="tclas-social-icon" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Facebook', 'tclas' ); ?>">
						<svg aria-hidden="true" viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
					</a>
					<?php endif; ?>
					<?php if ( $linkedin_url ) : ?>
					<a href="<?php echo esc_url( $linkedin_url ); ?>" class="tclas-social-icon" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'LinkedIn', 'tclas' ); ?>">
						<svg aria-hidden="true" viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
					</a>
					<?php endif; ?>
					<?php if ( $instagram_url ) : ?>
					<a href="<?php echo esc_url( $instagram_url ); ?>" class="tclas-social-icon" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Instagram', 'tclas' ); ?>">
						<svg aria-hidden="true" viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>
					</a>
					<?php endif; ?>
				</div>
				<?php endif; ?>

				<!-- Member since -->
				<?php if ( $member_since ) : ?>
				<p class="tclas-profile-meta">
					<?php
					printf(
						/* translators: %s: formatted month/year, e.g. "January 2023" */
						esc_html__( 'Member since %s', 'tclas' ),
						esc_html( $member_since )
					);
					?>
				</p>
				<?php endif; ?>

				<!-- Back / edit links -->
				<div class="tclas-profile-sidebar-actions">
					<a href="<?php echo esc_url( $_dir_url ); ?>" class="tclas-profile-back-link">
						&larr; <?php esc_html_e( 'Back to directory', 'tclas' ); ?>
					</a>
					<?php if ( $is_own_profile ) : ?>
					<a href="<?php echo esc_url( home_url( '/member-hub/my-story/' ) ); ?>" class="tclas-profile-edit-link">
						<?php esc_html_e( 'Edit my story', 'tclas' ); ?>
					</a>
					<?php endif; ?>
				</div>

			</aside><!-- .tclas-profile-sidebar -->

			<!-- ── Story main column (66%) ─────────────────────────────── -->
			<div class="tclas-profile-main">

				<!-- Bio -->
				<?php if ( $bio ) : ?>
				<div class="tclas-profile-bio">
					<h2 class="tclas-profile-section-title"><?php esc_html_e( 'About', 'tclas' ); ?></h2>
					<div class="tclas-prose">
						<?php echo wpautop( esc_html( $bio ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				</div>

				<?php elseif ( $is_own_profile ) : ?>

				<div class="tclas-profile-bio-cta">
					<p class="tclas-story-hint">
						<?php esc_html_e( 'Add a bio to tell other members about your Luxembourg connection.', 'tclas' ); ?>
					</p>
					<a href="<?php echo esc_url( home_url( '/member-hub/my-story/' ) ); ?>" class="btn btn-sm btn-outline-ardoise">
						<?php esc_html_e( 'Add bio →', 'tclas' ); ?>
					</a>
				</div>

				<?php endif; ?>

				<!-- Travel log highlights (last 3 dated trips) -->
				<?php if ( $recent_trips ) : ?>
				<div class="tclas-profile-trips">
					<h2 class="tclas-profile-section-title"><?php esc_html_e( 'Trips to Luxembourg', 'tclas' ); ?></h2>
					<ol class="tclas-profile-trip-list">
						<?php foreach ( $recent_trips as $trip ) :
							$dt_label      = '';
							$purpose_label = $purpose_labels[ $trip['purpose'] ?? '' ] ?? '';
							if ( ! empty( $trip['month_year'] ) ) {
								$dt       = DateTime::createFromFormat( 'Y-m', $trip['month_year'] );
								$dt_label = $dt ? $dt->format( 'F Y' ) : esc_html( $trip['month_year'] );
							}
						?>
						<li class="tclas-profile-trip-item">
							<div class="tclas-profile-trip-meta">
								<?php if ( $dt_label ) : ?>
									<time class="tclas-profile-trip-date" datetime="<?php echo esc_attr( $trip['month_year'] ); ?>">
										<?php echo esc_html( $dt_label ); ?>
									</time>
								<?php endif; ?>
								<?php if ( $purpose_label ) : ?>
									<span class="tclas-eyebrow"><?php echo esc_html( $purpose_label ); ?></span>
								<?php endif; ?>
							</div>
							<?php if ( ! empty( $trip['notes'] ) ) : ?>
								<p class="tclas-profile-trip-notes"><?php echo esc_html( $trip['notes'] ); ?></p>
							<?php endif; ?>
						</li>
						<?php endforeach; ?>
					</ol>
				</div>
				<?php endif; ?>

				<!-- If own profile and nothing is filled in yet, show a prompt -->
				<?php if ( $is_own_profile && ! $bio && ! $recent_trips && ! $surnames && ! $commune_links ) : ?>
				<div class="tclas-profile-empty-cta">
					<p class="tclas-story-hint">
						<?php esc_html_e( 'Your profile is blank right now. Fill out your Luxembourg story so other members can connect with you!', 'tclas' ); ?>
					</p>
					<a href="<?php echo esc_url( home_url( '/member-hub/my-story/' ) ); ?>" class="btn btn-primary">
						<?php esc_html_e( 'Complete my story →', 'tclas' ); ?>
					</a>
				</div>
				<?php endif; ?>

			</div><!-- .tclas-profile-main -->

		</div><!-- .tclas-profile-layout -->

	</div><!-- .container-tclas -->
</section>

<?php get_footer(); ?>
