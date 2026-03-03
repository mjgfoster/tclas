<?php
/**
 * Template Name: Member Profiles
 *
 * Handles both the member directory (/member-hub/profiles/) and individual
 * profile pages (/member-hub/profiles/{username}/).
 *
 * The rewrite rule in inc/member-profiles.php maps the {username} segment to
 * the query var 'tclas_profile_username'. When that var is set, this template
 * renders the individual profile view; otherwise it renders the directory.
 *
 * Setup: create a WP page titled "Profiles" with slug "profiles" as a child of
 * the Member Hub page, assign this template, then flush permalinks.
 *
 * @package TCLAS
 */

get_header();

// Member gate — both views require an active membership.
if ( ! tclas_is_member() ) :
?>
<div class="tclas-page-header tclas-page-header--ardoise">
	<div class="container-tclas">
		<span class="tclas-eyebrow tclas-eyebrow--light"><?php esc_html_e( 'Member hub', 'tclas' ); ?></span>
		<h1 class="tclas-page-header__title"><?php esc_html_e( 'Member Profiles', 'tclas' ); ?></h1>
	</div>
</div>
<section class="tclas-section">
	<div class="container-tclas">
		<div class="tclas-member-gate">
			<?php tclas_illustration( 'member_gate_illustration', __( 'Member area', 'tclas' ), 'tclas-member-gate__illustration' ); ?>
			<h2 class="tclas-member-gate__title"><?php esc_html_e( 'Members only.', 'tclas' ); ?></h2>
			<p class="tclas-member-gate__desc">
				<?php esc_html_e( 'The member directory is available to TCLAS members. Join or log in to browse and connect.', 'tclas' ); ?>
			</p>
			<div class="tclas-member-gate__actions">
				<a href="<?php echo esc_url( home_url( '/join/' ) ); ?>" class="btn btn-primary">
					<?php esc_html_e( 'Join TCLAS', 'tclas' ); ?>
				</a>
				<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="btn btn-outline-ardoise">
					<?php esc_html_e( 'Log in', 'tclas' ); ?>
				</a>
			</div>
		</div>
	</div>
</section>
<?php
	get_footer();
	return;
endif;

// ── Route: individual profile or directory? ─────────────────────────────────
$profile_username = get_query_var( 'tclas_profile_username' );

if ( $profile_username ) :
	// ════════════════════════════════════════════════════════════════════════
	// INDIVIDUAL PROFILE VIEW
	// ════════════════════════════════════════════════════════════════════════

	$profile_user = get_user_by( 'slug', sanitize_title( $profile_username ) );

	if ( ! $profile_user ) :
?>
<div class="tclas-page-header tclas-page-header--ardoise">
	<div class="container-tclas">
		<a href="<?php echo esc_url( home_url( '/member-hub/profiles/' ) ); ?>" class="tclas-back-link">
			← <?php esc_html_e( 'Member Profiles', 'tclas' ); ?>
		</a>
		<h1 class="tclas-page-header__title"><?php esc_html_e( 'Profile not found', 'tclas' ); ?></h1>
	</div>
</div>
<section class="tclas-section">
	<div class="container-tclas container--narrow">
		<p><?php esc_html_e( 'That member profile could not be found.', 'tclas' ); ?></p>
		<a href="<?php echo esc_url( home_url( '/member-hub/profiles/' ) ); ?>" class="btn btn-outline-ardoise">
			← <?php esc_html_e( 'Back to directory', 'tclas' ); ?>
		</a>
	</div>
</section>
<?php
		get_footer();
		return;
	endif;

	// Check that the profile user is an active member and not hidden.
	$vis = get_user_meta( $profile_user->ID, '_tclas_visibility', true ) ?: 'members';
	$is_board = function_exists( 'pmpro_getMembershipLevelForUser' )
		&& ( pmpro_getMembershipLevelForUser( get_current_user_id() )->name ?? '' ) === 'Board';

	if ( 'hidden' === $vis || ( 'board' === $vis && ! $is_board && ! current_user_can( 'manage_options' ) ) ) :
?>
<div class="tclas-page-header tclas-page-header--ardoise">
	<div class="container-tclas">
		<a href="<?php echo esc_url( home_url( '/member-hub/profiles/' ) ); ?>" class="tclas-back-link">
			← <?php esc_html_e( 'Member Profiles', 'tclas' ); ?>
		</a>
		<h1 class="tclas-page-header__title"><?php esc_html_e( 'Profile not available', 'tclas' ); ?></h1>
	</div>
</div>
<section class="tclas-section">
	<div class="container-tclas container--narrow">
		<p><?php esc_html_e( 'This member has chosen to keep their profile private.', 'tclas' ); ?></p>
		<a href="<?php echo esc_url( home_url( '/member-hub/profiles/' ) ); ?>" class="btn btn-outline-ardoise">
			← <?php esc_html_e( 'Back to directory', 'tclas' ); ?>
		</a>
	</div>
</section>
<?php
		get_footer();
		return;
	endif;

	$p = tclas_get_profile_data( $profile_user->ID );
	$is_own_profile = ( get_current_user_id() === $profile_user->ID );
?>

<div class="tclas-page-header tclas-page-header--ardoise">
	<div class="container-tclas">
		<a href="<?php echo esc_url( home_url( '/member-hub/profiles/' ) ); ?>" class="tclas-back-link">
			← <?php esc_html_e( 'Member Profiles', 'tclas' ); ?>
		</a>
		<h1 class="tclas-page-header__title"><?php echo esc_html( $p['display_name'] ); ?></h1>
	</div>
</div>

<section class="tclas-section tclas-section--sm">
	<div class="container-tclas">

		<div class="tclas-profile-view">

			<!-- ── Profile header card ─────────────────────────────────── -->
			<div class="tclas-profile-header">
				<div class="tclas-profile-header__photo">
					<?php echo tclas_get_profile_photo_img( $profile_user->ID, 'medium', 'tclas-profile-header__img' ); ?>
				</div>
				<div class="tclas-profile-header__meta">
					<h2 class="tclas-profile-header__name"><?php echo esc_html( $p['display_name'] ); ?></h2>

					<!-- Badges -->
					<div class="tclas-profile-badges">
						<?php if ( $p['is_founding'] ) : ?>
							<span class="tclas-badge tclas-badge--founding">★ <?php esc_html_e( 'Founding Member', 'tclas' ); ?></span>
						<?php endif; ?>
						<?php if ( $p['has_ancestors'] ) : ?>
							<span class="tclas-badge tclas-badge--ancestors">🗺 <?php esc_html_e( 'Ancestors on map', 'tclas' ); ?></span>
						<?php endif; ?>
						<?php if ( $p['has_children'] ) : ?>
							<span class="tclas-badge tclas-badge--family"><?php esc_html_e( 'Includes young members', 'tclas' ); ?></span>
						<?php endif; ?>
					</div>

					<!-- Membership info -->
					<dl class="tclas-profile-meta-list">
						<?php if ( $p['membership_level'] ) : ?>
							<dt><?php esc_html_e( 'Membership', 'tclas' ); ?></dt>
							<dd><?php echo esc_html( $p['membership_level'] ); ?></dd>
						<?php endif; ?>
						<?php if ( $p['member_since'] ) : ?>
							<dt><?php esc_html_e( 'Member since', 'tclas' ); ?></dt>
							<dd><?php echo esc_html( $p['member_since'] ); ?></dd>
						<?php endif; ?>
						<?php if ( $p['city'] ) : ?>
							<dt><?php esc_html_e( 'Based in', 'tclas' ); ?></dt>
							<dd><?php echo esc_html( $p['city'] ); ?></dd>
						<?php endif; ?>
					</dl>

					<!-- Social links -->
					<?php if ( ! empty( $p['social'] ) && array_filter( $p['social'] ) ) : ?>
						<div class="tclas-profile-social">
							<?php if ( $p['social']['facebook'] ) : ?>
								<a href="<?php echo esc_url( $p['social']['facebook'] ); ?>" class="tclas-profile-social__link" target="_blank" rel="noopener noreferrer">
									<span class="sr-only">Facebook</span>
									<svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
								</a>
							<?php endif; ?>
							<?php if ( $p['social']['linkedin'] ) : ?>
								<a href="<?php echo esc_url( $p['social']['linkedin'] ); ?>" class="tclas-profile-social__link" target="_blank" rel="noopener noreferrer">
									<span class="sr-only">LinkedIn</span>
									<svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
								</a>
							<?php endif; ?>
							<?php if ( $p['social']['instagram'] ) : ?>
								<a href="<?php echo esc_url( $p['social']['instagram'] ); ?>" class="tclas-profile-social__link" target="_blank" rel="noopener noreferrer">
									<span class="sr-only">Instagram</span>
									<svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
								</a>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<?php if ( $is_own_profile ) : ?>
						<a href="<?php echo esc_url( home_url( '/member-hub/my-story/' ) ); ?>" class="btn btn-sm btn-outline-ardoise tclas-profile-own-link">
							<?php esc_html_e( 'Edit my profile →', 'tclas' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div><!-- .tclas-profile-header -->

			<!-- ── Bio ─────────────────────────────────────────────────── -->
			<?php if ( $p['bio'] ) : ?>
				<div class="tclas-profile-section">
					<h3 class="tclas-profile-section__title"><?php esc_html_e( 'About', 'tclas' ); ?></h3>
					<p class="tclas-profile-bio"><?php echo nl2br( esc_html( $p['bio'] ) ); ?></p>
				</div>
			<?php endif; ?>

			<!-- ── Ancestral communes & surnames ───────────────────────── -->
			<?php if ( ! empty( $p['communes_raw'] ) || ! empty( $p['surnames_raw'] ) ) : ?>
				<div class="tclas-profile-section">
					<h3 class="tclas-profile-section__title"><?php esc_html_e( 'Luxembourg roots', 'tclas' ); ?></h3>

					<?php if ( ! empty( $p['communes_raw'] ) ) : ?>
						<div class="tclas-profile-roots">
							<h4 class="tclas-profile-roots__label"><?php esc_html_e( 'Ancestral communes', 'tclas' ); ?></h4>
							<div class="tclas-profile-pills">
								<?php foreach ( $p['communes_raw'] as $commune ) : ?>
									<span class="tclas-conn-pill tclas-conn-pill--commune">🏛 <?php echo esc_html( $commune ); ?></span>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $p['surnames_raw'] ) ) : ?>
						<div class="tclas-profile-roots">
							<h4 class="tclas-profile-roots__label"><?php esc_html_e( 'Family surnames', 'tclas' ); ?></h4>
							<div class="tclas-profile-pills">
								<?php foreach ( $p['surnames_raw'] as $surname ) : ?>
									<span class="tclas-conn-pill tclas-conn-pill--surname">👤 <?php echo esc_html( $surname ); ?></span>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<!-- ── Family members ──────────────────────────────────────── -->
			<?php if ( ! empty( $p['family_names'] ) ) : ?>
				<div class="tclas-profile-section">
					<h3 class="tclas-profile-section__title"><?php esc_html_e( 'Family membership', 'tclas' ); ?></h3>
					<p class="tclas-profile-family-names">
						<?php
						$names = array_map( 'esc_html', $p['family_names'] );
						echo implode( ', ', $names );
						?>
					</p>
				</div>
			<?php endif; ?>

		</div><!-- .tclas-profile-view -->

	</div><!-- .container-tclas -->
</section>

<?php

else :
	// ════════════════════════════════════════════════════════════════════════
	// DIRECTORY VIEW
	// ════════════════════════════════════════════════════════════════════════

	$members = tclas_get_directory_members();

	// Build unique sorted city list for filter dropdown.
	$cities = array_values( array_unique( array_filter(
		array_column( $members, 'city' ),
		'strlen'
	) ) );
	sort( $cities );
?>

<div class="tclas-page-header tclas-page-header--ardoise">
	<div class="container-tclas">
		<a href="<?php echo esc_url( home_url( '/member-hub/' ) ); ?>" class="tclas-back-link">
			← <?php esc_html_e( 'Member Hub', 'tclas' ); ?>
		</a>
		<span class="tclas-eyebrow tclas-eyebrow--light"><?php esc_html_e( 'Member hub', 'tclas' ); ?></span>
		<h1 class="tclas-page-header__title"><?php esc_html_e( 'Member Profiles', 'tclas' ); ?></h1>
	</div>
</div>

<section class="tclas-section tclas-section--sm">
	<div class="container-tclas">

		<!-- ── Filters ───────────────────────────────────────────────────── -->
		<div class="tclas-dir-filters" role="search" aria-label="<?php esc_attr_e( 'Filter members', 'tclas' ); ?>">

			<div class="tclas-dir-filters__search">
				<label for="tclas-dir-search" class="sr-only"><?php esc_html_e( 'Search by name', 'tclas' ); ?></label>
				<input
					type="search"
					id="tclas-dir-search"
					class="tclas-dir-filter-input"
					placeholder="<?php esc_attr_e( 'Search by name…', 'tclas' ); ?>"
					aria-controls="tclas-dir-grid"
				>
			</div>

			<?php if ( ! empty( $cities ) ) : ?>
			<div class="tclas-dir-filters__city">
				<label for="tclas-dir-city" class="sr-only"><?php esc_html_e( 'Filter by city', 'tclas' ); ?></label>
				<select id="tclas-dir-city" class="tclas-dir-filter-input" aria-controls="tclas-dir-grid">
					<option value=""><?php esc_html_e( 'All cities', 'tclas' ); ?></option>
					<?php foreach ( $cities as $city ) : ?>
						<option value="<?php echo esc_attr( $city ); ?>"><?php echo esc_html( $city ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<?php endif; ?>

			<div class="tclas-dir-filters__toggle">
				<label class="tclas-dir-filter-check">
					<input type="checkbox" id="tclas-dir-ancestors" aria-controls="tclas-dir-grid">
					<?php esc_html_e( 'Has ancestors on map', 'tclas' ); ?>
				</label>
			</div>

			<p class="tclas-dir-count" id="tclas-dir-count" aria-live="polite">
				<?php
				printf(
					/* translators: %d: member count */
					esc_html( _n( '%d member', '%d members', count( $members ), 'tclas' ) ),
					count( $members )
				);
				?>
			</p>

		</div><!-- .tclas-dir-filters -->

		<!-- ── Member grid ───────────────────────────────────────────────── -->
		<?php if ( empty( $members ) ) : ?>
			<p><?php esc_html_e( 'No member profiles are visible yet.', 'tclas' ); ?></p>
		<?php else : ?>
			<div
				class="tclas-dir-grid"
				id="tclas-dir-grid"
				data-total="<?php echo (int) count( $members ); ?>"
			>
				<?php foreach ( $members as $m ) :
					$profile_url = home_url( '/member-hub/profiles/' . rawurlencode( $m['username'] ) . '/' );
				?>
				<a
					href="<?php echo esc_url( $profile_url ); ?>"
					class="tclas-dir-card"
					data-name="<?php echo esc_attr( strtolower( $m['display_name'] ) ); ?>"
					data-city="<?php echo esc_attr( strtolower( $m['city'] ) ); ?>"
					data-ancestors="<?php echo $m['has_ancestors'] ? '1' : '0'; ?>"
				>
					<div class="tclas-dir-card__photo">
						<img
							src="<?php echo esc_url( $m['photo_url'] ); ?>"
							alt="<?php echo esc_attr( $m['display_name'] ); ?>"
							class="tclas-dir-card__img"
							loading="lazy"
							width="80"
							height="80"
						>
					</div>
					<div class="tclas-dir-card__body">
						<p class="tclas-dir-card__name"><?php echo esc_html( $m['display_name'] ); ?></p>
						<?php if ( $m['city'] ) : ?>
							<p class="tclas-dir-card__city"><?php echo esc_html( $m['city'] ); ?></p>
						<?php endif; ?>
						<div class="tclas-dir-card__indicators">
							<?php if ( $m['is_founding'] ) : ?>
								<span class="tclas-badge tclas-badge--founding tclas-badge--sm" title="<?php esc_attr_e( 'Founding Member', 'tclas' ); ?>">★</span>
							<?php endif; ?>
							<?php if ( $m['has_ancestors'] ) : ?>
								<span class="tclas-dir-card__indicator tclas-dir-card__indicator--ancestors" title="<?php esc_attr_e( 'Has ancestors on the map', 'tclas' ); ?>">🗺</span>
							<?php endif; ?>
							<?php if ( $m['has_bio'] ) : ?>
								<span class="tclas-dir-card__indicator tclas-dir-card__indicator--bio" title="<?php esc_attr_e( 'Has filled out a bio', 'tclas' ); ?>">📝</span>
							<?php endif; ?>
						</div>
					</div>
				</a>
				<?php endforeach; ?>
			</div><!-- .tclas-dir-grid -->

			<p class="tclas-dir-no-results" id="tclas-dir-no-results" hidden>
				<?php esc_html_e( 'No members match your filters.', 'tclas' ); ?>
			</p>
		<?php endif; ?>

	</div><!-- .container-tclas -->
</section>

<?php
endif;
get_footer();
?>
