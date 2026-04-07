<?php
/**
 * Template Name: My Ancestral Map
 *
 * Frontend genealogy edit page where members manage their ancestral lineages
 * (commune + surname pairings), unassigned surnames, and travel log.
 *
 * Save triggers the connection engine via tclas_save_member_story().
 *
 * @package TCLAS
 */

get_header();

// ── Handle form POST ────────────────────────────────────────────────────────
$save_message = '';
$save_error   = '';

if ( tclas_is_member() && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
	if (
		isset( $_POST['tclas_ancestry_nonce'] )
		&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tclas_ancestry_nonce'] ) ), 'tclas_save_ancestry_' . get_current_user_id() )
	) {
		$uid = get_current_user_id();

		// ── Ancestral lineages + connection engine ────────────────────────
		$lineages_input  = tclas_parse_lineage_post_data( $_POST );
		$unassigned_raw  = array_filter( (array) ( $_POST['tclas_unassigned_surnames'] ?? [] ), 'strlen' );
		$visibility      = get_user_meta( $uid, '_tclas_visibility', true ) ?: 'members';
		$open_to_contact = (bool) get_user_meta( $uid, '_tclas_open_to_contact', true );
		tclas_save_member_story( $uid, $lineages_input, $unassigned_raw, $visibility, $open_to_contact );

		// ── Travel log ────────────────────────────────────────────────────
		$raw_months  = (array) ( $_POST['tclas_trip_month_year'] ?? [] );
		$raw_purpose = (array) ( $_POST['tclas_trip_purpose']    ?? [] );
		$raw_notes   = (array) ( $_POST['tclas_trip_notes']      ?? [] );
		$trips_to_save = [];
		foreach ( $raw_months as $i => $my ) {
			$my    = sanitize_text_field( $my );
			$notes = sanitize_textarea_field( $raw_notes[ $i ] ?? '' );
			if ( '' === $my && '' === $notes ) {
				continue;
			}
			if ( '' !== $my && ! preg_match( '/^\d{4}-\d{2}$/', $my ) ) {
				continue;
			}
			$trips_to_save[] = [
				'month_year' => $my,
				'purpose'    => sanitize_text_field( $raw_purpose[ $i ] ?? '' ),
				'notes'      => $notes,
			];
		}
		update_user_meta( $uid, '_tclas_trips', $trips_to_save );

		$count        = count( tclas_get_connections( $uid ) );
		$save_message = $count > 0
			? sprintf(
				_n(
					'Ancestry saved — and we found %d connection! Check your dashboard to see it.',
					'Ancestry saved — and we found %d connections! Check your dashboard to see them.',
					$count,
					'tclas'
				),
				$count
			)
			: esc_html__( 'Ancestry saved. As more members complete their profiles, connections will appear on your dashboard.', 'tclas' );
	} else {
		$save_error = esc_html__( 'Security check failed. Please try again.', 'tclas' );
	}
}

// ── Load current user data ──────────────────────────────────────────────────
$user_id        = get_current_user_id();
$lineages       = (array) ( get_user_meta( $user_id, '_tclas_lineages',                true ) ?: [] );
$unassigned_raw = (array) ( get_user_meta( $user_id, '_tclas_unassigned_surnames_raw',  true ) ?: [] );

// Ensure at least one empty lineage card for new users.
if ( empty( $lineages ) ) {
	$lineages[] = [ 'commune_raw' => '', 'surnames_raw' => [ '' ] ];
}
foreach ( $lineages as &$_l ) {
	if ( empty( $_l['surnames_raw'] ) ) {
		$_l['surnames_raw'] = [ '' ];
	}
}
unset( $_l );
if ( empty( $unassigned_raw ) ) {
	$unassigned_raw[] = '';
}

$trips = (array) ( get_user_meta( $user_id, '_tclas_trips', true ) ?: [] );
if ( empty( $trips ) ) {
	$trips[] = [ 'month_year' => '', 'purpose' => '', 'notes' => '' ];
}

// ── Discovery data ──────────────────────────────────────────────────────────
$communes_norm = (array) ( get_user_meta( $user_id, '_tclas_communes_norm', true ) ?: [] );
$resolved_communes = array_filter( $communes_norm, fn( $s ) => '' !== $s && ! str_starts_with( $s, 'unresolved:' ) );
$map_communes = tclas_get_profile_map_data( $user_id );
?>

<div class="tclas-page-header">
	<div class="container-tclas">
		<?php tclas_breadcrumb( __( 'My Ancestral Map', 'tclas' ) ); ?>
		<h1 class="tclas-page-header__title"><?php esc_html_e( 'My Ancestral Map', 'tclas' ); ?></h1>
	</div>
</div>

<section class="tclas-section">
	<div class="container-tclas">

		<?php if ( ! tclas_is_member() ) : ?>

			<div class="tclas-member-gate">
				<?php tclas_illustration( 'member_gate_illustration', __( 'Member area', 'tclas' ), 'tclas-member-gate__illustration' ); ?>
				<h2 class="tclas-member-gate__title"><?php esc_html_e( 'Members only.', 'tclas' ); ?></h2>
				<p class="tclas-member-gate__desc">
					<?php esc_html_e( 'Your ancestral map is part of your TCLAS membership. Join or log in to add your communes and surnames.', 'tclas' ); ?>
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

		<?php else : ?>

			<div class="tclas-story-layout">

				<!-- ── Ancestry form ─────────────────────────────────────── -->
				<div class="tclas-story-form-col">

					<?php if ( $save_message ) : ?>
						<div class="tclas-alert tclas-alert--success" role="alert">
							<?php echo esc_html( $save_message ); ?>
						</div>
					<?php endif; ?>

					<?php if ( $save_error ) : ?>
						<div class="tclas-alert tclas-alert--error" role="alert">
							<?php echo esc_html( $save_error ); ?>
						</div>
					<?php endif; ?>

					<?php // Discovery: show current communes and connection counts. ?>
					<?php if ( ! empty( $map_communes ) ) : ?>
						<div class="tclas-story-fieldset tclas-discovery-preview">
							<h3 class="tclas-story-legend"><?php esc_html_e( 'Your communes on the map', 'tclas' ); ?></h3>
							<ul class="tclas-discovery-list">
								<?php foreach ( $map_communes as $mc ) : ?>
									<li>
										<strong><?php echo esc_html( $mc['name'] ); ?></strong>
										<span class="text-muted">(<?php echo esc_html( $mc['canton'] ); ?>)</span>
										<?php if ( ! empty( $mc['surnames'] ) ) : ?>
											— <?php echo esc_html( implode( ', ', $mc['surnames'] ) ); ?>
										<?php endif; ?>
									</li>
								<?php endforeach; ?>
							</ul>
							<?php
							// Show connection discovery count.
							$connections = tclas_get_connections( $user_id );
							$visible_connections = array_filter( $connections, fn( $c ) => ! $c['dismissed'] );
							if ( ! empty( $visible_connections ) ) :
							?>
								<p class="tclas-discovery-count">
									<?php
									printf(
										esc_html( _n(
											'We found %d member who shares your roots!',
											'We found %d members who share your roots!',
											count( $visible_connections ),
											'tclas'
										) ),
										count( $visible_connections )
									);
									?>
									<a href="<?php echo esc_url( home_url( '/member-hub/' ) ); ?>">
										<?php esc_html_e( 'View on dashboard →', 'tclas' ); ?>
									</a>
								</p>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<form
						id="tclas-ancestry-form"
						class="tclas-my-story-form"
						method="post"
						action="<?php the_permalink(); ?>"
						novalidate
					>
						<?php wp_nonce_field( 'tclas_save_ancestry_' . $user_id, 'tclas_ancestry_nonce' ); ?>

						<!-- ── Ancestral lineages ──────────────────────────── -->
						<fieldset class="tclas-story-fieldset">
							<legend class="tclas-story-legend">
								<?php esc_html_e( 'Ancestral lineages', 'tclas' ); ?>
							</legend>
							<p class="tclas-story-hint">
								<?php esc_html_e( 'Pair each commune with the family surnames you trace there. Try French, German, or Luxembourgish spellings — we match them automatically. e.g. "Clerf" and "Clervaux" are the same.', 'tclas' ); ?>
							</p>

							<div id="tclas-lineage-list" class="tclas-lineage-list">
								<?php foreach ( $lineages as $ci => $lineage ) : ?>
									<div class="tclas-lineage-card" data-card-index="<?php echo (int) $ci; ?>">
										<div class="tclas-lineage-card__header">
											<input
												type="text"
												name="tclas_lineage_commune[]"
												value="<?php echo esc_attr( $lineage['commune_raw'] ?? '' ); ?>"
												class="tclas-story-input tclas-lineage-commune-input"
												placeholder="<?php esc_attr_e( 'e.g. Echternach', 'tclas' ); ?>"
												autocomplete="off"
												list="tclas-commune-options"
												aria-label="<?php esc_attr_e( 'Ancestral commune', 'tclas' ); ?>"
											>
											<?php if ( $ci > 0 ) : ?>
												<button
													type="button"
													class="tclas-repeater-remove tclas-lineage-remove-card"
													aria-label="<?php esc_attr_e( 'Remove this lineage', 'tclas' ); ?>"
												>×</button>
											<?php endif; ?>
										</div>
										<div class="tclas-lineage-card__surnames">
											<?php foreach ( ( (array) ( $lineage['surnames_raw'] ?? [] ) ) as $si => $sraw ) : ?>
												<div class="tclas-repeater-row">
													<input
														type="text"
														name="tclas_lineage_surnames[<?php echo (int) $ci; ?>][]"
														value="<?php echo esc_attr( $sraw ); ?>"
														class="tclas-story-input"
														placeholder="<?php esc_attr_e( 'e.g. Kieffer', 'tclas' ); ?>"
														autocomplete="off"
														aria-label="<?php esc_attr_e( 'Paired surname', 'tclas' ); ?>"
													>
													<?php if ( $si > 0 ) : ?>
														<button
															type="button"
															class="tclas-repeater-remove"
															aria-label="<?php esc_attr_e( 'Remove this surname', 'tclas' ); ?>"
														>×</button>
													<?php endif; ?>
												</div>
											<?php endforeach; ?>
										</div>
										<button
											type="button"
											class="btn btn-sm btn-link tclas-lineage-add-surname"
										>
											<?php esc_html_e( '+ Add surname', 'tclas' ); ?>
										</button>
									</div>
								<?php endforeach; ?>
							</div>

							<button
								type="button"
								class="btn btn-sm btn-outline-ardoise"
								id="tclas-lineage-add-card"
							>
								<?php esc_html_e( '+ Add another commune lineage', 'tclas' ); ?>
							</button>

						</fieldset>

						<!-- ── Unassigned surnames ─────────────────────────── -->
						<fieldset class="tclas-story-fieldset">
							<legend class="tclas-story-legend">
								<?php esc_html_e( 'Other family surnames', 'tclas' ); ?>
							</legend>
							<p class="tclas-story-hint">
								<?php esc_html_e( 'Surnames you haven\'t tied to a specific commune yet. We\'ll still match them with other members.', 'tclas' ); ?>
							</p>

							<div id="tclas-unassigned-list" class="tclas-repeater-list">
								<?php foreach ( $unassigned_raw as $ui => $ua ) : ?>
									<div class="tclas-repeater-row" data-index="<?php echo (int) $ui; ?>">
										<input
											type="text"
											name="tclas_unassigned_surnames[]"
											value="<?php echo esc_attr( $ua ); ?>"
											class="tclas-story-input"
											placeholder="<?php esc_attr_e( 'e.g. Wagner', 'tclas' ); ?>"
											autocomplete="off"
											aria-label="<?php esc_attr_e( 'Unassigned surname', 'tclas' ); ?>"
										>
										<?php if ( $ui > 0 ) : ?>
											<button
												type="button"
												class="tclas-repeater-remove"
												aria-label="<?php esc_attr_e( 'Remove this surname', 'tclas' ); ?>"
											>×</button>
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
							</div>

							<button
								type="button"
								class="btn btn-sm btn-outline-ardoise tclas-repeater-add"
								data-target="tclas-unassigned-list"
								data-placeholder="<?php esc_attr_e( 'e.g. Schmitt', 'tclas' ); ?>"
								data-name="tclas_unassigned_surnames[]"
							>
								<?php esc_html_e( '+ Add another surname', 'tclas' ); ?>
							</button>
						</fieldset>

						<!-- ── Travel Log ───────────────────────────────────── -->
						<fieldset class="tclas-story-fieldset">
							<legend class="tclas-story-legend">
								<?php esc_html_e( 'My trips to Luxembourg', 'tclas' ); ?>
							</legend>
							<p class="tclas-story-hint">
								<?php esc_html_e( 'Record your visits to Luxembourg — when you went, why, and anything you\'d like to remember.', 'tclas' ); ?>
							</p>

							<div id="tclas-trips-list" class="tclas-trip-list">
								<?php
								$trip_purposes = [
									'heritage' => __( 'Heritage research',      'tclas' ),
									'family'   => __( 'Family visit',            'tclas' ),
									'tourism'  => __( 'Tourism / vacation',      'tclas' ),
									'tclas'    => __( 'TCLAS / society event',   'tclas' ),
									'business' => __( 'Business',                'tclas' ),
									'other'    => __( 'Other',                   'tclas' ),
								];
								foreach ( $trips as $i => $trip ) :
								?>
								<div class="tclas-trip-item">
									<div class="tclas-trip-fields">
										<div class="tclas-trip-field-group">
											<label class="tclas-trip-label" for="trip-month-<?php echo (int) $i; ?>">
												<?php esc_html_e( 'Month & Year', 'tclas' ); ?>
											</label>
											<input
												type="month"
												id="trip-month-<?php echo (int) $i; ?>"
												name="tclas_trip_month_year[]"
												value="<?php echo esc_attr( $trip['month_year'] ); ?>"
												class="tclas-story-input tclas-trip-month"
												min="1900-01"
												max="<?php echo esc_attr( gmdate( 'Y-m' ) ); ?>"
											>
										</div>
										<div class="tclas-trip-field-group">
											<label class="tclas-trip-label" for="trip-purpose-<?php echo (int) $i; ?>">
												<?php esc_html_e( 'Purpose', 'tclas' ); ?>
											</label>
											<select
												id="trip-purpose-<?php echo (int) $i; ?>"
												name="tclas_trip_purpose[]"
												class="tclas-story-input tclas-trip-purpose"
											>
												<option value=""><?php esc_html_e( '— select —', 'tclas' ); ?></option>
												<?php foreach ( $trip_purposes as $val => $label ) : ?>
												<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $trip['purpose'], $val ); ?>>
													<?php echo esc_html( $label ); ?>
												</option>
												<?php endforeach; ?>
											</select>
										</div>
									</div>
									<div class="tclas-trip-field-group tclas-trip-field-group--full">
										<label class="tclas-trip-label" for="trip-notes-<?php echo (int) $i; ?>">
											<?php esc_html_e( 'Notes / highlights', 'tclas' ); ?>
										</label>
										<textarea
											id="trip-notes-<?php echo (int) $i; ?>"
											name="tclas_trip_notes[]"
											class="tclas-story-input tclas-trip-notes"
											rows="2"
											placeholder="<?php esc_attr_e( 'Villages visited, archives searched, relatives met…', 'tclas' ); ?>"
										><?php echo esc_textarea( $trip['notes'] ); ?></textarea>
									</div>
									<?php if ( $i > 0 ) : ?>
									<button
										type="button"
										class="tclas-trip-remove tclas-repeater-remove"
										aria-label="<?php esc_attr_e( 'Remove this trip', 'tclas' ); ?>"
									>×</button>
									<?php endif; ?>
								</div>
								<?php endforeach; ?>
							</div>

							<button type="button" id="tclas-trip-add" class="btn btn-sm btn-outline-ardoise">
								<?php esc_html_e( '+ Add another trip', 'tclas' ); ?>
							</button>
						</fieldset>

						<div class="tclas-story-actions">
							<button type="submit" class="btn btn-primary">
								<?php esc_html_e( 'Save ancestry', 'tclas' ); ?>
							</button>
							<a href="<?php echo esc_url( home_url( '/member-hub/' ) ); ?>" class="btn btn-outline-ardoise">
								<?php esc_html_e( '← Back to hub', 'tclas' ); ?>
							</a>
						</div>

					</form>

				</div><!-- .tclas-story-form-col -->

				<!-- ── Sidebar ──────────────────────────────────────────── -->
				<aside class="tclas-story-sidebar-col">

					<div class="tclas-story-tip-box">
						<h3 class="tclas-story-tip-title"><?php esc_html_e( 'Your profile screens', 'tclas' ); ?></h3>
						<ul>
							<li><a href="<?php echo esc_url( home_url( '/member-hub/edit-profile/' ) ); ?>"><?php esc_html_e( 'Edit Profile', 'tclas' ); ?></a> — <?php esc_html_e( 'Bio, photo & social', 'tclas' ); ?></li>
							<li><strong><?php esc_html_e( 'My Ancestral Map', 'tclas' ); ?></strong> — <?php esc_html_e( 'You are here', 'tclas' ); ?></li>
							<li><a href="<?php echo esc_url( home_url( '/member-hub/privacy/' ) ); ?>"><?php esc_html_e( 'Privacy Settings', 'tclas' ); ?></a> — <?php esc_html_e( 'Control what others see', 'tclas' ); ?></li>
						</ul>
					</div>

					<div class="tclas-story-tip-box">
						<h3 class="tclas-story-tip-title"><?php esc_html_e( 'Tips for better matches', 'tclas' ); ?></h3>
						<ul>
							<li><?php esc_html_e( 'Add all communes you know, even uncertain ones.', 'tclas' ); ?></li>
							<li><?php esc_html_e( 'Try both the French and German/Lëtzebuergesch spelling of a commune.', 'tclas' ); ?></li>
							<li><?php esc_html_e( 'Include original Luxembourg spellings of surnames — "Schmitt" will also match "Schmidt" and "Smith".', 'tclas' ); ?></li>
							<li><?php esc_html_e( 'Research as far back as you can — 4th-generation connections are still real connections.', 'tclas' ); ?></li>
						</ul>
					</div>

				</aside>

			</div><!-- .tclas-story-layout -->

		<?php endif; ?>

	</div><!-- .container-tclas -->
</section>

<!-- Datalist for commune autocomplete -->
<datalist id="tclas-commune-options">
	<?php foreach ( tclas_commune_labels() as $label ) : ?>
		<option value="<?php echo esc_attr( $label ); ?>">
	<?php endforeach; ?>
</datalist>

<?php get_footer(); ?>
