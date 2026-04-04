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
<div class="tclas-page-header">
	<div class="container-tclas">
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
<div class="tclas-page-header">
	<div class="container-tclas">
		<a href="<?php echo esc_url( home_url( '/member-hub/profiles/' ) ); ?>" class="tclas-back-link">
			← <?php esc_html_e( 'Member Profiles', 'tclas' ); ?>
		</a>
		<h1 class="tclas-page-header__title"><?php esc_html_e( 'Profile not found', 'tclas' ); ?></h1>
	</div>
</div>
<section class="tclas-section">
	<div class="container-tclas container--medium">
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
<div class="tclas-page-header">
	<div class="container-tclas">
		<a href="<?php echo esc_url( home_url( '/member-hub/profiles/' ) ); ?>" class="tclas-back-link">
			← <?php esc_html_e( 'Member Profiles', 'tclas' ); ?>
		</a>
		<h1 class="tclas-page-header__title"><?php esc_html_e( 'Profile not available', 'tclas' ); ?></h1>
	</div>
</div>
<section class="tclas-section">
	<div class="container-tclas container--medium">
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

	// Map data for profile mini-map (empty if no resolved communes or privacy-hidden).
	$map_communes = tclas_get_profile_map_data( $profile_user->ID );
	$has_roots    = ! empty( $p['lineages'] ) || ! empty( $p['unassigned_surnames_raw'] );
?>

<!-- ── Page header ─────────────────────────────────────────────────────── -->
<div class="tclas-page-header">
	<div class="container-tclas">
		<?php tclas_breadcrumb( __( 'Member Profiles', 'tclas' ) ); ?>
		<h1 class="tclas-page-header__title"><?php echo esc_html( $p['display_name'] ); ?></h1>

		<?php // Edit button (own profile) ?>
		<?php if ( $is_own_profile ) : ?>
			<a href="<?php echo esc_url( home_url( '/member-hub/my-story/' ) ); ?>" class="btn btn-primary btn-sm" style="margin-bottom: 1.5rem;">
				<i class="bi bi-pencil-square" aria-hidden="true"></i> <?php esc_html_e( 'Edit my profile', 'tclas' ); ?>
			</a>
		<?php endif; ?>

		<?php // Membership meta line. ?>
		<p class="tclas-page-header__membership">
			<?php
			$meta_parts = [];
			if ( $p['membership_level'] && $p['member_since'] ) {
				/* translators: %1$s = level name, %2$s = year */
				$meta_parts[] = sprintf( __( '%1$s member since %2$s', 'tclas' ), esc_html( $p['membership_level'] ), esc_html( $p['member_since'] ) );
			} elseif ( $p['membership_level'] ) {
				$meta_parts[] = esc_html( $p['membership_level'] ) . ' ' . __( 'member', 'tclas' );
			}
			if ( $p['city'] ) {
				$meta_parts[] = esc_html( $p['city'] );
			}
			if ( ! empty( $p['pronouns'] ) ) {
				$meta_parts[] = esc_html( $p['pronouns'] );
			}
			echo implode( ' · ', $meta_parts );
			?>
		</p>

		<?php // Badges. ?>
		<?php
		$badge_reg    = function_exists( 'tclas_badge_registry' ) ? tclas_badge_registry() : [];
		$active_slugs = $p['badges'] ?? [];
		$has_public_badges = false;
		foreach ( $active_slugs as $_s ) {
			if ( isset( $badge_reg[ $_s ] ) && ( $badge_reg[ $_s ]['public'] ?? true ) ) {
				$has_public_badges = true;
				break;
			}
		}
		?>
		<?php if ( $has_public_badges ) : ?>
			<div class="tclas-profile-badges">
				<?php foreach ( $active_slugs as $slug ) :
					if ( ! isset( $badge_reg[ $slug ] ) ) continue;
					$_def = $badge_reg[ $slug ];
					if ( ! ( $_def['public'] ?? true ) ) continue;
				?>
					<span class="tclas-badge tclas-badge--member-badge">
						<i class="bi <?php echo esc_attr( $_def['icon'] ); ?>" aria-hidden="true"></i> <?php echo esc_html( $_def['label'] ); ?>
					</span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</div>

<!-- ── Two-column profile layout ───────────────────────────────────────── -->
<section class="tclas-section tclas-section--sm">
	<div class="container-tclas">

		<div class="tclas-profile-view">

			<!-- ── Sidebar ─────────────────────────────────────────── -->
			<aside class="tclas-profile-sidebar">

				<!-- Photo (square) -->
				<div class="tclas-profile-sidebar__photo-wrap">
					<?php echo tclas_get_profile_photo_img( $profile_user->ID, 'medium_large', 'tclas-profile-sidebar__photo' ); ?>
				</div>

				<!-- Social links -->
				<?php if ( ! empty( $p['social'] ) && array_filter( $p['social'] ) ) : ?>
					<div class="tclas-profile-social">
						<?php if ( $p['social']['facebook'] ) : ?>
							<a href="<?php echo esc_url( $p['social']['facebook'] ); ?>" class="tclas-profile-social__link" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
								<i class="bi bi-facebook" aria-hidden="true"></i>
							</a>
						<?php endif; ?>
						<?php if ( $p['social']['linkedin'] ) : ?>
							<a href="<?php echo esc_url( $p['social']['linkedin'] ); ?>" class="tclas-profile-social__link" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn">
								<i class="bi bi-linkedin" aria-hidden="true"></i>
							</a>
						<?php endif; ?>
						<?php if ( $p['social']['instagram'] ) : ?>
							<a href="<?php echo esc_url( $p['social']['instagram'] ); ?>" class="tclas-profile-social__link" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
								<i class="bi bi-instagram" aria-hidden="true"></i>
							</a>
						<?php endif; ?>
					</div>
				<?php endif; ?>

			</aside>

			<!-- ── Main column ─────────────────────────────────────── -->
			<div class="tclas-profile-main">

				<!-- Bio (no heading — just prose) -->
				<?php if ( $p['bio'] ) : ?>
					<div class="tclas-profile-bio">
						<?php echo nl2br( esc_html( $p['bio'] ) ); ?>
					</div>
				<?php endif; ?>

				<!-- Luxembourg roots -->
				<?php if ( $has_roots ) : ?>
					<div class="tclas-profile-roots">
						<h3 class="tclas-profile-roots__heading">
							<?php
							/* translators: %s = member's first name */
							printf( esc_html__( '%s\'s Luxembourg roots', 'tclas' ), esc_html( $p['first_name'] ?: $p['display_name'] ) );
							?>
						</h3>

						<?php // Mini-map (only if communes with coordinates exist). ?>
						<?php if ( ! empty( $map_communes ) ) : ?>
							<div id="tclas-profile-map" class="tclas-profile-map" aria-label="<?php esc_attr_e( 'Map of ancestral communes in Luxembourg', 'tclas' ); ?>"></div>
						<?php endif; ?>

						<?php // Commune / canton table — grouped by canton with rowspan. ?>
						<?php if ( ! empty( $map_communes ) ) :
							// Sort communes by canton, then by name within each canton.
							$sorted = $map_communes;
							usort( $sorted, function ( $a, $b ) {
								$c = strcmp( $a['canton'], $b['canton'] );
								return $c !== 0 ? $c : strcmp( $a['name'], $b['name'] );
							} );

							// Group by canton for rowspan counts.
							$canton_groups = [];
							foreach ( $sorted as $mc ) {
								$canton_groups[ $mc['canton'] ][] = $mc;
							}
						?>
							<table class="tclas-profile-commune-table">
								<thead>
									<tr>
										<th scope="col"><?php esc_html_e( 'Canton', 'tclas' ); ?></th>
										<th scope="col"><?php esc_html_e( 'Commune', 'tclas' ); ?></th>
										<th scope="col"><?php esc_html_e( 'Surnames', 'tclas' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $canton_groups as $canton => $communes ) :
										$count = count( $communes );
										foreach ( $communes as $i => $mc ) : ?>
											<tr>
												<?php if ( 0 === $i ) : ?>
													<th scope="rowgroup" rowspan="<?php echo esc_attr( $count ); ?>" class="tclas-profile-commune-table__canton">
														<?php echo esc_html( $canton ); ?>
													</th>
												<?php endif; ?>
												<td><?php echo esc_html( $mc['name'] ); ?></td>
												<td>
													<?php if ( ! empty( $mc['surnames'] ) ) : ?>
														<ul class="tclas-profile-surname-list">
															<?php foreach ( $mc['surnames'] as $sn ) : ?>
																<li><?php echo esc_html( $sn ); ?></li>
															<?php endforeach; ?>
														</ul>
													<?php else : ?>
														<span class="text-muted">—</span>
													<?php endif; ?>
												</td>
											</tr>
										<?php endforeach;
									endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>

						<?php // Lineages without coordinates (commune_raw only). ?>
						<?php foreach ( $p['lineages'] as $lineage ) :
							$commune_label = $lineage['commune_raw'] ?? '';
							$norm          = $lineage['commune_norm'] ?? '';
							$paired_names  = array_filter( (array) ( $lineage['surnames_raw'] ?? [] ), 'strlen' );
							if ( '' === $commune_label ) continue;
							// Skip if already shown in table.
							if ( '' !== $norm && ! str_starts_with( $norm, 'unresolved:' ) ) continue;
						?>
							<div class="tclas-profile-lineage">
								<h4 class="tclas-profile-lineage__commune">
									<i class="bi bi-geo" aria-hidden="true"></i> <?php echo esc_html( $commune_label ); ?>
								</h4>
								<?php if ( ! empty( $paired_names ) ) : ?>
									<div class="tclas-profile-lineage__surnames">
										<?php foreach ( $paired_names as $sname ) : ?>
											<span class="tclas-profile-surname-pill"><?php echo esc_html( $sname ); ?></span>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>

						<?php // Unassigned surnames. ?>
						<?php
						$ua_names = array_filter( (array) ( $p['unassigned_surnames_raw'] ?? [] ), 'strlen' );
						if ( ! empty( $ua_names ) ) : ?>
							<div class="tclas-profile-unassigned">
								<h4 class="tclas-profile-roots__label"><?php esc_html_e( 'Other family surnames', 'tclas' ); ?></h4>
								<div class="tclas-profile-pills">
									<?php foreach ( $ua_names as $sname ) : ?>
										<span class="tclas-profile-surname-pill"><?php echo esc_html( $sname ); ?></span>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endif; ?>

						<?php
						// CTA for logged-in members who haven't added their own ancestry yet.
						$viewer_id = get_current_user_id();
						if ( $viewer_id && ! $is_own_profile ) :
							$viewer_communes = (array) ( get_user_meta( $viewer_id, '_tclas_communes_norm', true ) ?: [] );
							$viewer_has_map  = ! empty( array_filter(
								$viewer_communes,
								fn( $s ) => '' !== $s && ! str_starts_with( $s, 'unresolved:' )
							) );
							if ( ! $viewer_has_map ) : ?>
								<div class="tclas-profile-roots-cta">
									<p>
										<?php esc_html_e( 'Think this map is cool? Add your Luxembourg ancestors and get one of your own.', 'tclas' ); ?>
									</p>
									<a href="<?php echo esc_url( home_url( '/member-hub/my-story/' ) ); ?>" class="btn btn-sm btn-outline-ardoise">
										<?php esc_html_e( 'Add my ancestors', 'tclas' ); ?>
									</a>
								</div>
							<?php endif;
						endif;
						?>
					</div>
				<?php endif; ?>

				<!-- Family members -->
				<?php if ( ! empty( $p['family_names'] ) ) : ?>
					<div class="tclas-profile-family">
						<h3 class="tclas-profile-section__title"><?php esc_html_e( 'Family membership', 'tclas' ); ?></h3>
						<p class="tclas-profile-family-names">
							<?php
							$names = array_map( 'esc_html', $p['family_names'] );
							echo implode( ', ', $names );
							?>
						</p>
					</div>
				<?php endif; ?>

			</div><!-- .tclas-profile-main -->

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

<div class="tclas-page-header">
	<div class="container-tclas">
		<?php tclas_breadcrumb( __( 'Member Profiles', 'tclas' ) ); ?>
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
						<p class="tclas-dir-card__name">
							<?php echo esc_html( $m['display_name'] ); ?>
							<?php if ( ! empty( $m['pronouns'] ) ) : ?>
								<span class="tclas-dir-card__pronouns"><?php echo esc_html( $m['pronouns'] ); ?></span>
							<?php endif; ?>
						</p>
						<?php if ( $m['city'] ) : ?>
							<p class="tclas-dir-card__city"><?php echo esc_html( $m['city'] ); ?></p>
						<?php endif; ?>
						<div class="tclas-dir-card__indicators">
							<?php
							$_card_badge_reg = function_exists( 'tclas_badge_registry' ) ? tclas_badge_registry() : [];
							foreach ( $m['badges'] ?? [] as $_card_badge ) :
								// Directory builder stores ['key'=>...,'label'=>...]; normalize to slug string.
								$_card_slug = is_array( $_card_badge ) ? ( $_card_badge['key'] ?? '' ) : $_card_badge;
								if ( ! $_card_slug || ! isset( $_card_badge_reg[ $_card_slug ] ) ) continue;
								$_card_def = $_card_badge_reg[ $_card_slug ];
								if ( ! ( $_card_def['public'] ?? true ) ) continue;
							?>
								<span class="tclas-badge tclas-badge--member-badge tclas-badge--sm" title="<?php echo esc_attr( $_card_def['label'] ); ?>"><i class="bi <?php echo esc_attr( $_card_def['icon'] ); ?>" aria-hidden="true"></i></span>
							<?php endforeach; ?>
							<?php if ( $m['has_ancestors'] ) : ?>
								<span class="tclas-dir-card__indicator tclas-dir-card__indicator--ancestors" title="<?php esc_attr_e( 'Has ancestors on the map', 'tclas' ); ?>"><i class="bi bi-map-fill" aria-hidden="true"></i></span>
							<?php endif; ?>
							<?php if ( $m['has_bio'] ) : ?>
								<span class="tclas-dir-card__indicator tclas-dir-card__indicator--bio" title="<?php esc_attr_e( 'Has filled out a bio', 'tclas' ); ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i></span>
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
