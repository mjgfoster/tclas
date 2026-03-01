<?php
/**
 * "How Are We Connected" — matching engine, AJAX handlers, cron, and admin UI.
 *
 * Data flow:
 *   1. Member saves profile via page-my-story.php (POST form).
 *   2. tclas_save_member_story() normalises input and stores two user meta keys
 *      per data type: raw (preserved for display) and normalised (used for comparison).
 *   3. tclas_compute_connections() runs immediately after save, then nightly via
 *      WP-Cron for the full member base.
 *   4. Results are cached in _tclas_connections_cache user meta.
 *   5. The dashboard panel reads from cache; AJAX endpoints handle dismiss/seen.
 *
 * User meta keys:
 *   _tclas_communes_raw         string[]   Original user input (display)
 *   _tclas_communes_norm        string[]   Canonical commune slugs (match)
 *   _tclas_surnames_raw         string[]   Original user input (display)
 *   _tclas_surnames_norm        string[]   Canonical cluster heads (match)
 *   _tclas_visibility           string     'members' | 'board' | 'hidden'
 *   _tclas_open_to_contact      int        1 | 0
 *   _tclas_profile_complete     int        1 once ≥1 commune or surname saved
 *   _tclas_connections_cache    array      Computed connection objects
 *   _tclas_connections_computed int        Unix timestamp of last compute run
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 1 — Normalisation pipeline
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Strip diacritics from a string using a hand-written map.
 * Handles the full set of characters found in Luxembourgish, French, and German names.
 */
function tclas_strip_diacritics( string $s ): string {
	$from = [ 'à','á','â','ã','ä','å','æ','ç','è','é','ê','ë','ì','í','î','ï',
	          'ð','ñ','ò','ó','ô','õ','ö','ø','ù','ú','û','ü','ý','ÿ','þ','ß',
	          'À','Á','Â','Ã','Ä','Å','Æ','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï',
	          'Ð','Ñ','Ò','Ó','Ô','Õ','Ö','Ø','Ù','Ú','Û','Ü','Ý','Þ','Ÿ',
	          'ë','ä','ö','ü','Ë','Ä','Ö','Ü', // ensure Luxembourgish chars handled
	];
	$to   = [ 'a','a','a','a','a','a','ae','c','e','e','e','e','i','i','i','i',
	          'd','n','o','o','o','o','o','o','u','u','u','u','y','y','th','ss',
	          'A','A','A','A','A','A','AE','C','E','E','E','E','I','I','I','I',
	          'D','N','O','O','O','O','O','O','U','U','U','U','Y','TH','Y',
	          'e','a','o','u','E','A','O','U',
	];
	return str_replace( $from, $to, $s );
}

/**
 * Apply the normalisation pipeline shared by communes and surnames:
 * lowercase → strip diacritics → collapse whitespace/hyphens → trim.
 */
function tclas_normalize_string( string $raw ): string {
	$s = mb_strtolower( $raw, 'UTF-8' );
	$s = tclas_strip_diacritics( $s );
	$s = preg_replace( '/[\s\-_\/]+/', ' ', $s );
	$s = trim( $s );
	return $s;
}

/**
 * Resolve a free-text commune input to a canonical slug.
 *
 * Resolution order:
 *   1. Exact match in alias map.
 *   2. Levenshtein ≤ threshold against all known aliases.
 *   3. NULL — caller queues for admin review.
 */
function tclas_resolve_commune( string $raw ): ?string {
	$norm = tclas_normalize_string( $raw );
	if ( '' === $norm ) {
		return null;
	}

	$alias_map = tclas_commune_alias_map();

	// 1. Exact alias match.
	if ( isset( $alias_map[ $norm ] ) ) {
		return $alias_map[ $norm ];
	}

	// 2. Levenshtein fuzzy match (threshold by name length).
	$threshold = tclas_levenshtein_threshold( $norm );
	$best_dist = PHP_INT_MAX;
	$best_slug = null;

	foreach ( $alias_map as $alias => $slug ) {
		$dist = levenshtein( $norm, $alias );
		if ( $dist <= $threshold && $dist < $best_dist ) {
			$best_dist = $dist;
			$best_slug = $slug;
		}
	}

	return $best_slug; // null if nothing within threshold
}

/**
 * Resolve a free-text surname input to a canonical cluster head.
 *
 * Resolution order:
 *   1. Exact match in variant map.
 *   2. Umlaut-expansion retry (ü→ue etc.) then exact match.
 *   3. Levenshtein ≤ threshold against all known variants.
 *   4. NULL — caller queues for admin review.
 */
function tclas_resolve_surname( string $raw ): ?string {
	$norm = tclas_normalize_string( $raw );
	if ( '' === $norm ) {
		return null;
	}

	$variant_map = tclas_surname_variant_map();

	// 1. Exact match.
	if ( isset( $variant_map[ $norm ] ) ) {
		return $variant_map[ $norm ];
	}

	// 2. Umlaut expansion (ü→ue, ö→oe, ä→ae) then retry.
	$expanded = strtr( $norm, [ 'u' => 'ue', 'o' => 'oe', 'a' => 'ae' ] );
	// Only apply if the expansion actually differs and is in the map.
	if ( $expanded !== $norm && isset( $variant_map[ $expanded ] ) ) {
		return $variant_map[ $expanded ];
	}

	// 3. Levenshtein fuzzy match.
	$threshold = tclas_levenshtein_threshold( $norm );
	$best_dist = PHP_INT_MAX;
	$best_head = null;

	foreach ( $variant_map as $variant => $head ) {
		$dist = levenshtein( $norm, $variant );
		if ( $dist <= $threshold && $dist < $best_dist ) {
			$best_dist = $dist;
			$best_head = $head;
		}
	}

	return $best_head;
}

/**
 * Levenshtein edit-distance threshold scaled to name length.
 * Short names (≤4 chars) require exact match to prevent false positives.
 */
function tclas_levenshtein_threshold( string $s ): int {
	$len = strlen( $s );
	if ( $len <= 4 ) { return 0; }
	if ( $len <= 7 ) { return 1; }
	if ( $len <= 11 ) { return 2; }
	return 3;
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 2 — Profile save
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Persist a member's Luxembourg story fields.
 *
 * Called both from the frontend POST handler (page-my-story.php) and the
 * WP admin user-edit screen.
 *
 * @param int      $user_id         WP user ID.
 * @param string[] $communes_raw    Free-text commune inputs (unsanitised).
 * @param string[] $surnames_raw    Free-text surname inputs (unsanitised).
 * @param string   $visibility      'members' | 'board' | 'hidden'.
 * @param bool     $open_to_contact Whether the member accepts hub messages.
 */
function tclas_save_member_story(
	int    $user_id,
	array  $communes_raw,
	array  $surnames_raw,
	string $visibility      = 'members',
	bool   $open_to_contact = true
): void {

	// ── Sanitise and resolve communes ──────────────────────────────────
	$communes_clean = [];
	$communes_norm  = [];
	$unresolved     = [];

	foreach ( $communes_raw as $raw ) {
		$raw = sanitize_text_field( $raw );
		if ( '' === $raw ) {
			continue;
		}
		$communes_clean[] = $raw;
		$slug = tclas_resolve_commune( $raw );
		if ( $slug ) {
			$communes_norm[] = $slug;
		} else {
			$unresolved[] = [ 'type' => 'commune', 'value' => $raw, 'user_id' => $user_id ];
			// Store raw as lowercase-stripped so it still matches exact duplicates.
			$communes_norm[] = 'unresolved:' . tclas_normalize_string( $raw );
		}
	}

	// ── Sanitise and resolve surnames ──────────────────────────────────
	$surnames_clean = [];
	$surnames_norm  = [];

	foreach ( $surnames_raw as $raw ) {
		$raw = sanitize_text_field( $raw );
		if ( '' === $raw ) {
			continue;
		}
		$surnames_clean[] = $raw;
		$head = tclas_resolve_surname( $raw );
		if ( $head ) {
			$surnames_norm[] = $head;
		} else {
			$unresolved[] = [ 'type' => 'surname', 'value' => $raw, 'user_id' => $user_id ];
			$surnames_norm[] = 'unresolved:' . tclas_normalize_string( $raw );
		}
	}

	// ── Persist user meta ──────────────────────────────────────────────
	update_user_meta( $user_id, '_tclas_communes_raw',  $communes_clean );
	update_user_meta( $user_id, '_tclas_communes_norm', array_unique( $communes_norm ) );
	update_user_meta( $user_id, '_tclas_surnames_raw',  $surnames_clean );
	update_user_meta( $user_id, '_tclas_surnames_norm', array_unique( $surnames_norm ) );

	$allowed_vis = [ 'members', 'board', 'hidden' ];
	update_user_meta( $user_id, '_tclas_visibility',   in_array( $visibility, $allowed_vis, true ) ? $visibility : 'members' );
	update_user_meta( $user_id, '_tclas_open_to_contact', $open_to_contact ? 1 : 0 );

	$complete = ( ! empty( $communes_clean ) || ! empty( $surnames_clean ) ) ? 1 : 0;
	update_user_meta( $user_id, '_tclas_profile_complete', $complete );

	// ── Queue unresolved entries for admin review ──────────────────────
	if ( ! empty( $unresolved ) ) {
		$queue = get_option( 'tclas_unresolved_entries', [] );
		foreach ( $unresolved as $entry ) {
			// Deduplicate by value.
			$key = $entry['type'] . ':' . $entry['value'];
			if ( ! isset( $queue[ $key ] ) ) {
				$queue[ $key ] = array_merge( $entry, [ 'submitted_at' => time() ] );
			}
		}
		update_option( 'tclas_unresolved_entries', $queue, false );
	}

	// ── Re-compute connections immediately ─────────────────────────────
	tclas_compute_connections( $user_id );

	// ── Notify existing matched members of the update ──────────────────
	tclas_invalidate_connections_for_matches( $user_id );

	// ── Bust the ancestor-map commune-count transient ───────────────────
	do_action( 'tclas_member_story_saved', $user_id );
}

/**
 * Force matched members to re-compute on their next dashboard load.
 * Called after a profile is saved so they see updated connection data.
 */
function tclas_invalidate_connections_for_matches( int $user_id ): void {
	$connections = get_user_meta( $user_id, '_tclas_connections_cache', true );
	if ( ! is_array( $connections ) ) {
		return;
	}
	foreach ( array_keys( $connections ) as $matched_user_id ) {
		delete_user_meta( (int) $matched_user_id, '_tclas_connections_computed_at' );
	}
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 3 — Match engine
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Return active member IDs who have completed their Luxembourg story profile,
 * excluding $exclude_user_id.
 *
 * @return int[]
 */
function tclas_get_members_with_profiles( int $exclude_user_id = 0 ): array {
	global $wpdb;

	// Pull all active PMPro member IDs (or all users if PMPro absent).
	if ( function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
		$ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT user_id
			 FROM {$wpdb->prefix}pmpro_memberships_users
			 WHERE status = 'active'
			   AND user_id != %d",
			$exclude_user_id
		) );
	} else {
		$ids = get_users( [
			'fields'  => 'ID',
			'exclude' => [ $exclude_user_id ],
			'number'  => 1000,
		] );
	}

	// Filter to those with complete profiles and visible settings.
	return array_values( array_filter(
		array_map( 'intval', $ids ),
		function( int $uid ): bool {
			if ( ! get_user_meta( $uid, '_tclas_profile_complete', true ) ) {
				return false;
			}
			$vis = get_user_meta( $uid, '_tclas_visibility', true ) ?: 'members';
			return 'hidden' !== $vis;
		}
	) );
}

/**
 * Compute (or refresh) connections for a given user and cache results.
 *
 * @return array Connection objects keyed by matched user ID.
 */
function tclas_compute_connections( int $user_id ): array {
	$my_communes = (array) ( get_user_meta( $user_id, '_tclas_communes_norm', true ) ?: [] );
	$my_surnames = (array) ( get_user_meta( $user_id, '_tclas_surnames_norm', true ) ?: [] );

	$connections = [];

	if ( empty( $my_communes ) && empty( $my_surnames ) ) {
		update_user_meta( $user_id, '_tclas_connections_cache', [] );
		update_user_meta( $user_id, '_tclas_connections_computed_at', time() );
		return [];
	}

	foreach ( tclas_get_members_with_profiles( $user_id ) as $candidate_id ) {
		$their_communes = (array) ( get_user_meta( $candidate_id, '_tclas_communes_norm', true ) ?: [] );
		$their_surnames = (array) ( get_user_meta( $candidate_id, '_tclas_surnames_norm', true ) ?: [] );

		$shared_comm = array_values( array_intersect( $my_communes, $their_communes ) );
		$shared_surv = array_values( array_intersect( $my_surnames, $their_surnames ) );

		if ( empty( $shared_comm ) && empty( $shared_surv ) ) {
			continue;
		}

		// Preserve existing seen/dismissed state when re-computing.
		$existing = get_user_meta( $user_id, '_tclas_connections_cache', true );
		$prev     = is_array( $existing ) ? ( $existing[ $candidate_id ] ?? [] ) : [];

		$connections[ $candidate_id ] = [
			'user_id'           => $candidate_id,
			'shared_communes'   => $shared_comm,  // canonical slugs
			'shared_surnames'   => $shared_surv,  // canonical cluster heads
			'score'             => tclas_connection_score( $shared_comm, $shared_surv ),
			'computed_at'       => time(),
			'seen'              => $prev['seen']      ?? false,
			'dismissed'         => $prev['dismissed'] ?? false,
			'variant_commune'   => tclas_has_variant_commune( $user_id, $candidate_id, $shared_comm ),
			'variant_surname'   => tclas_has_variant_surname( $user_id, $candidate_id, $shared_surv ),
		];
	}

	// Sort by score descending.
	uasort( $connections, fn( $a, $b ) => $b['score'] <=> $a['score'] );

	update_user_meta( $user_id, '_tclas_connections_cache', $connections );
	update_user_meta( $user_id, '_tclas_connections_computed_at', time() );

	return $connections;
}

/**
 * Score a set of shared communes and surnames.
 *
 * Scoring matrix (from architecture doc):
 *   1 shared commune   → 2 pts
 *   Each additional    → +1 pt
 *   1 shared surname   → 2 pts
 *   Each additional    → +1 pt
 *   Same commune AND surname on same person → +4 bonus
 */
function tclas_connection_score( array $communes, array $surnames ): int {
	$score = 0;

	if ( count( $communes ) > 0 ) {
		$score += 2 + max( 0, count( $communes ) - 1 );
	}
	if ( count( $surnames ) > 0 ) {
		$score += 2 + max( 0, count( $surnames ) - 1 );
	}
	if ( count( $communes ) > 0 && count( $surnames ) > 0 ) {
		$score += 4; // bonus for sharing both
	}

	return $score;
}

/**
 * Return true if the match was made via a variant-resolved commune
 * (i.e. the two users used different spellings of the same commune).
 */
function tclas_has_variant_commune( int $uid_a, int $uid_b, array $shared_slugs ): bool {
	if ( empty( $shared_slugs ) ) {
		return false;
	}
	$raw_a = (array) ( get_user_meta( $uid_a, '_tclas_communes_raw', true ) ?: [] );
	$raw_b = (array) ( get_user_meta( $uid_b, '_tclas_communes_raw', true ) ?: [] );
	// If they stored the same raw value we're not showing a variant notice.
	return $raw_a !== $raw_b;
}

/**
 * Return true if the surname match was made across variant spellings.
 */
function tclas_has_variant_surname( int $uid_a, int $uid_b, array $shared_heads ): bool {
	if ( empty( $shared_heads ) ) {
		return false;
	}
	$raw_a = (array) ( get_user_meta( $uid_a, '_tclas_surnames_raw', true ) ?: [] );
	$raw_b = (array) ( get_user_meta( $uid_b, '_tclas_surnames_raw', true ) ?: [] );
	return $raw_a !== $raw_b;
}

/**
 * Get cached connections, recomputing if the cache is stale (>24 h old).
 */
function tclas_get_connections( int $user_id ): array {
	$computed_at = (int) get_user_meta( $user_id, '_tclas_connections_computed_at', true );
	$cache       = get_user_meta( $user_id, '_tclas_connections_cache', true );

	if ( ! is_array( $cache ) || ( time() - $computed_at ) > DAY_IN_SECONDS ) {
		$cache = tclas_compute_connections( $user_id );
	}

	return $cache;
}

/**
 * Count unseen, non-dismissed connections.
 */
function tclas_get_new_connection_count( int $user_id ): int {
	$connections = get_user_meta( $user_id, '_tclas_connections_cache', true );
	if ( ! is_array( $connections ) ) {
		return 0;
	}
	return count( array_filter( $connections, fn( $c ) => ! $c['dismissed'] && ! $c['seen'] ) );
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 4 — Connection sentence generator
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Return the human-readable strength label and a dot colour class.
 *
 * @return array{label: string, class: string}
 */
function tclas_connection_strength( int $score ): array {
	if ( $score >= 8 ) {
		return [ 'label' => __( 'Remarkable connection', 'tclas' ), 'class' => 'remarkable' ];
	}
	if ( $score >= 5 ) {
		return [ 'label' => __( 'Strong connection', 'tclas' ), 'class' => 'strong' ];
	}
	return [ 'label' => __( 'Possible connection', 'tclas' ), 'class' => 'possible' ];
}

/**
 * Build the display labels (raw values) for a list of canonical slugs/heads
 * as entered by user $for_user_id — or, if not found, fall back to the
 * canonical label from the data tables.
 */
function tclas_display_communes( array $slugs, int $for_user_id ): array {
	$raw   = (array) ( get_user_meta( $for_user_id, '_tclas_communes_raw', true ) ?: [] );
	$norms = (array) ( get_user_meta( $for_user_id, '_tclas_communes_norm', true ) ?: [] );
	$all   = tclas_commune_aliases();

	$labels = [];
	foreach ( $slugs as $slug ) {
		// Try to find the user's own raw entry for this slug.
		$idx = array_search( $slug, $norms, true );
		if ( false !== $idx && isset( $raw[ $idx ] ) ) {
			$labels[] = $raw[ $idx ];
		} elseif ( isset( $all[ $slug ] ) ) {
			$labels[] = $all[ $slug ]['label'];
		} else {
			$labels[] = ucwords( str_replace( '-', ' ', $slug ) );
		}
	}
	return $labels;
}

/**
 * Build display labels for canonical surname cluster heads.
 */
function tclas_display_surnames( array $heads, int $for_user_id ): array {
	$raw   = (array) ( get_user_meta( $for_user_id, '_tclas_surnames_raw', true ) ?: [] );
	$norms = (array) ( get_user_meta( $for_user_id, '_tclas_surnames_norm', true ) ?: [] );
	$all   = tclas_surname_clusters();

	$labels = [];
	foreach ( $heads as $head ) {
		$idx = array_search( $head, $norms, true );
		if ( false !== $idx && isset( $raw[ $idx ] ) ) {
			$labels[] = $raw[ $idx ];
		} elseif ( isset( $all[ $head ] ) ) {
			$labels[] = $all[ $head ]['label'];
		} else {
			$labels[] = ucfirst( $head );
		}
	}
	return $labels;
}

/**
 * Format an array into a natural-language list: "A, B and C".
 */
function tclas_human_list( array $items ): string {
	$items = array_map( 'esc_html', $items );
	$last  = array_pop( $items );
	return empty( $items )
		? $last
		: implode( ', ', $items ) . ' ' . __( 'and', 'tclas' ) . ' ' . $last;
}

/**
 * Generate the connection sentence for a pair of users.
 *
 * When the match was made via variant spellings (Smith/Schmitt), the sentence
 * discloses both forms rather than silently merging them.
 *
 * @return string  Plain text (already escaped).
 */
function tclas_connection_sentence(
	int   $my_id,
	int   $their_id,
	array $connection
): string {
	$their_name = esc_html( get_the_author_meta( 'display_name', $their_id ) );
	$c_count    = count( $connection['shared_communes'] );
	$s_count    = count( $connection['shared_surnames'] );

	// Build display labels from each user's own raw values.
	$my_comm_labels    = tclas_display_communes( $connection['shared_communes'], $my_id );
	$their_comm_labels = tclas_display_communes( $connection['shared_communes'], $their_id );
	$my_surv_labels    = tclas_display_surnames( $connection['shared_surnames'], $my_id );
	$their_surv_labels = tclas_display_surnames( $connection['shared_surnames'], $their_id );

	// Detect variant spellings (user A and B stored different raw values for same canonical).
	$comm_variants = ( $my_comm_labels !== $their_comm_labels );
	$surv_variants = ( $my_surv_labels !== $their_surv_labels );

	// ── Both commune and surname shared ────────────────────────────────
	if ( $c_count > 0 && $s_count > 0 ) {
		$commune_str = tclas_human_list( $my_comm_labels );
		$surname_str = tclas_human_list( $my_surv_labels );

		if ( $surv_variants ) {
			$their_surv_str = tclas_human_list( $their_surv_labels );
			return sprintf(
				/* translators: 1: their name 2: commune 3: my surname 4: their surname */
				esc_html__( 'You and %1$s both have roots in %2$s and may share family through the %3$s / %4$s name.', 'tclas' ),
				$their_name, $commune_str, $surname_str, $their_surv_str
			);
		}

		return sprintf(
			/* translators: 1: their name 2: commune 3: surname */
			esc_html__( 'You and %1$s both have roots in %2$s and share the surname %3$s.', 'tclas' ),
			$their_name, $commune_str, $surname_str
		);
	}

	// ── Commune only ───────────────────────────────────────────────────
	if ( $c_count > 0 ) {
		if ( $comm_variants ) {
			$commune_str       = tclas_human_list( $my_comm_labels );
			$their_commune_str = tclas_human_list( $their_comm_labels );
			return sprintf(
				esc_html__( 'You and %1$s both have ancestry in %2$s (also known as %3$s).', 'tclas' ),
				$their_name, $commune_str, $their_commune_str
			);
		}
		return sprintf(
			esc_html__( 'You and %1$s both have ancestry in %2$s.', 'tclas' ),
			$their_name, tclas_human_list( $my_comm_labels )
		);
	}

	// ── Surname only ───────────────────────────────────────────────────
	if ( $surv_variants ) {
		return sprintf(
			esc_html__( 'You and %1$s may share family through the %2$s / %3$s name.', 'tclas' ),
			$their_name,
			tclas_human_list( $my_surv_labels ),
			tclas_human_list( $their_surv_labels )
		);
	}

	return sprintf(
		esc_html__( 'You and %1$s share the surname %2$s.', 'tclas' ),
		$their_name,
		tclas_human_list( $my_surv_labels )
	);
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 5 — WP-Cron nightly batch
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Register a daily cron event if not already scheduled.
 */
function tclas_schedule_connection_cron(): void {
	if ( ! wp_next_scheduled( 'tclas_connection_cron' ) ) {
		wp_schedule_event( strtotime( 'tomorrow 02:00:00' ), 'daily', 'tclas_connection_cron' );
	}
}
add_action( 'init', 'tclas_schedule_connection_cron' );

/**
 * Process connections in batches of 40 to avoid timeouts.
 */
function tclas_run_connection_cron(): void {
	global $wpdb;

	$batch_size = 40;
	$offset     = (int) get_option( 'tclas_connection_batch_offset', 0 );

	if ( function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
		$ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT user_id
			 FROM {$wpdb->prefix}pmpro_memberships_users
			 WHERE status = 'active'
			 LIMIT %d OFFSET %d",
			$batch_size,
			$offset
		) );
	} else {
		$ids = get_users( [
			'fields' => 'ID',
			'number' => $batch_size,
			'offset' => $offset,
		] );
	}

	if ( empty( $ids ) ) {
		update_option( 'tclas_connection_batch_offset', 0 ); // reset
		return;
	}

	foreach ( $ids as $uid ) {
		if ( get_user_meta( (int) $uid, '_tclas_profile_complete', true ) ) {
			tclas_compute_connections( (int) $uid );
		}
	}

	update_option( 'tclas_connection_batch_offset', $offset + $batch_size );
}
add_action( 'tclas_connection_cron', 'tclas_run_connection_cron' );

/**
 * Schedule an immediate connection compute when a new member joins.
 */
function tclas_on_new_member( int $level_id, int $user_id ): void {
	if ( $level_id > 0 && get_user_meta( $user_id, '_tclas_profile_complete', true ) ) {
		tclas_compute_connections( $user_id );
	}
}
add_action( 'pmpro_after_change_membership_level', 'tclas_on_new_member', 10, 2 );

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 6 — AJAX endpoints
// ═══════════════════════════════════════════════════════════════════════════

/**
 * AJAX: dismiss a single connection.
 * Payload: nonce, other_user_id
 */
function tclas_ajax_dismiss_connection(): void {
	check_ajax_referer( 'tclas_nonce', 'nonce' );

	$user_id      = get_current_user_id();
	$other_id     = (int) filter_input( INPUT_POST, 'other_user_id', FILTER_VALIDATE_INT );

	if ( ! $user_id || ! $other_id ) {
		wp_send_json_error( 'invalid' );
	}

	$cache = get_user_meta( $user_id, '_tclas_connections_cache', true );
	if ( is_array( $cache ) && isset( $cache[ $other_id ] ) ) {
		$cache[ $other_id ]['dismissed'] = true;
		update_user_meta( $user_id, '_tclas_connections_cache', $cache );
	}

	wp_send_json_success();
}
add_action( 'wp_ajax_tclas_dismiss_connection', 'tclas_ajax_dismiss_connection' );

/**
 * AJAX: mark all visible connections as seen (clears notification dot).
 * Payload: nonce
 */
function tclas_ajax_mark_connections_seen(): void {
	check_ajax_referer( 'tclas_nonce', 'nonce' );

	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		wp_send_json_error( 'not_logged_in' );
	}

	$cache = get_user_meta( $user_id, '_tclas_connections_cache', true );
	if ( is_array( $cache ) ) {
		foreach ( $cache as &$conn ) {
			if ( ! $conn['dismissed'] ) {
				$conn['seen'] = true;
			}
		}
		unset( $conn );
		update_user_meta( $user_id, '_tclas_connections_cache', $cache );
	}

	wp_send_json_success();
}
add_action( 'wp_ajax_tclas_mark_connections_seen', 'tclas_ajax_mark_connections_seen' );

/**
 * AJAX: save the "My Luxembourg Story" form.
 * Handles the frontend POST form submission from page-my-story.php.
 */
function tclas_ajax_save_my_story(): void {
	check_ajax_referer( 'tclas_my_story_nonce', 'nonce' );

	$user_id = get_current_user_id();
	if ( ! $user_id || ! tclas_is_member() ) {
		wp_send_json_error( 'not_member' );
	}

	$communes        = array_filter( (array) ( $_POST['communes'] ?? [] ), 'strlen' );
	$surnames        = array_filter( (array) ( $_POST['surnames'] ?? [] ), 'strlen' );
	$visibility      = sanitize_text_field( $_POST['visibility'] ?? 'members' );
	$open_to_contact = ! empty( $_POST['open_to_contact'] );

	tclas_save_member_story( $user_id, $communes, $surnames, $visibility, $open_to_contact );

	$count = count( tclas_get_connections( $user_id ) );

	wp_send_json_success( [
		'connections_found' => $count,
		/* translators: %d: number of connections */
		'message'           => sprintf(
			_n(
				'Profile saved. %d connection found!',
				'Profile saved. %d connections found!',
				$count,
				'tclas'
			),
			$count
		),
	] );
}
add_action( 'wp_ajax_tclas_save_my_story', 'tclas_ajax_save_my_story' );

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 7 — WP Admin: user profile fields + unresolved queue
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Render Luxembourg story fields on the WP admin user edit screen.
 */
function tclas_admin_user_profile_fields( WP_User $user ): void {
	if ( ! current_user_can( 'manage_options' ) && get_current_user_id() !== $user->ID ) {
		return;
	}

	$communes        = (array) ( get_user_meta( $user->ID, '_tclas_communes_raw',  true ) ?: [] );
	$surnames        = (array) ( get_user_meta( $user->ID, '_tclas_surnames_raw',  true ) ?: [] );
	$visibility      = get_user_meta( $user->ID, '_tclas_visibility', true ) ?: 'members';
	$open_to_contact = (bool) get_user_meta( $user->ID, '_tclas_open_to_contact', true );
	?>
	<h2><?php esc_html_e( 'Luxembourg Story (TCLAS)', 'tclas' ); ?></h2>
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Ancestral Communes', 'tclas' ); ?></th>
			<td>
				<?php foreach ( $communes as $i => $c ) : ?>
					<p><input type="text" name="tclas_communes[]" value="<?php echo esc_attr( $c ); ?>" class="regular-text"></p>
				<?php endforeach; ?>
				<p><input type="text" name="tclas_communes[]" value="" class="regular-text" placeholder="<?php esc_attr_e( 'Add commune…', 'tclas' ); ?>"></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Luxembourg Surnames', 'tclas' ); ?></th>
			<td>
				<?php foreach ( $surnames as $i => $s ) : ?>
					<p><input type="text" name="tclas_surnames[]" value="<?php echo esc_attr( $s ); ?>" class="regular-text"></p>
				<?php endforeach; ?>
				<p><input type="text" name="tclas_surnames[]" value="" class="regular-text" placeholder="<?php esc_attr_e( 'Add surname…', 'tclas' ); ?>"></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Profile visibility', 'tclas' ); ?></th>
			<td>
				<select name="tclas_visibility">
					<option value="members" <?php selected( $visibility, 'members' ); ?>><?php esc_html_e( 'All members', 'tclas' ); ?></option>
					<option value="board"   <?php selected( $visibility, 'board' );   ?>><?php esc_html_e( 'Board only', 'tclas' ); ?></option>
					<option value="hidden"  <?php selected( $visibility, 'hidden' );  ?>><?php esc_html_e( 'Hidden', 'tclas' ); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Open to contact', 'tclas' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="tclas_open_to_contact" value="1" <?php checked( $open_to_contact ); ?>>
					<?php esc_html_e( 'Allow matched members to reach out via the hub.', 'tclas' ); ?>
				</label>
			</td>
		</tr>
	</table>
	<?php
	wp_nonce_field( 'tclas_admin_profile_' . $user->ID, 'tclas_admin_profile_nonce' );
}
add_action( 'show_user_profile', 'tclas_admin_user_profile_fields' );
add_action( 'edit_user_profile', 'tclas_admin_user_profile_fields' );

/**
 * Save the admin user profile fields.
 */
function tclas_admin_save_user_profile( int $user_id ): void {
	if ( ! isset( $_POST['tclas_admin_profile_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tclas_admin_profile_nonce'] ) ), 'tclas_admin_profile_' . $user_id )
	) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) && get_current_user_id() !== $user_id ) {
		return;
	}

	$communes        = array_filter( (array) ( $_POST['tclas_communes'] ?? [] ), 'strlen' );
	$surnames        = array_filter( (array) ( $_POST['tclas_surnames'] ?? [] ), 'strlen' );
	$visibility      = sanitize_text_field( $_POST['tclas_visibility'] ?? 'members' );
	$open_to_contact = ! empty( $_POST['tclas_open_to_contact'] );

	tclas_save_member_story( $user_id, $communes, $surnames, $visibility, $open_to_contact );
}
add_action( 'personal_options_update',  'tclas_admin_save_user_profile' );
add_action( 'edit_user_profile_update', 'tclas_admin_save_user_profile' );

// ── Admin unresolved-entry queue ──────────────────────────────────────────

/**
 * Register the admin submenu page for unresolved entries.
 */
function tclas_admin_connections_menu(): void {
	add_users_page(
		__( 'Unresolved genealogy entries', 'tclas' ),
		__( 'Unresolved entries', 'tclas' ),
		'manage_options',
		'tclas-unresolved-entries',
		'tclas_admin_unresolved_page'
	);
}
add_action( 'admin_menu', 'tclas_admin_connections_menu' );

/**
 * Handle admin approval of an unresolved entry.
 * When approved: add to the alias/variant data and re-normalise for that user.
 */
function tclas_admin_unresolved_page(): void {
	// Handle approve action.
	if (
		isset( $_POST['tclas_approve_nonce'], $_POST['approve_key'] )
		&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tclas_approve_nonce'] ) ), 'tclas_approve_entry' )
	) {
		$key     = sanitize_text_field( wp_unslash( $_POST['approve_key'] ) );
		$queue   = get_option( 'tclas_unresolved_entries', [] );
		if ( isset( $queue[ $key ] ) ) {
			unset( $queue[ $key ] );
			update_option( 'tclas_unresolved_entries', $queue, false );

			// Note: permanently adding to the alias table requires editing connection-data.php.
			// Show the admin a reminder message with what to add.
			$parts = explode( ':', $key, 2 );
			echo '<div class="notice notice-success"><p>';
			printf(
				/* translators: 1: type (commune/surname) 2: value */
				esc_html__( 'Entry removed from queue. To permanently resolve this %1$s, add "%2$s" to the appropriate alias/cluster in inc/connection-data.php.', 'tclas' ),
				esc_html( $parts[0] ?? '' ),
				esc_html( $parts[1] ?? '' )
			);
			echo '</p></div>';
		}
	}

	// Handle dismiss action.
	if (
		isset( $_POST['tclas_dismiss_nonce'], $_POST['dismiss_key'] )
		&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tclas_dismiss_nonce'] ) ), 'tclas_dismiss_entry' )
	) {
		$key   = sanitize_text_field( wp_unslash( $_POST['dismiss_key'] ) );
		$queue = get_option( 'tclas_unresolved_entries', [] );
		unset( $queue[ $key ] );
		update_option( 'tclas_unresolved_entries', $queue, false );
	}

	$queue = get_option( 'tclas_unresolved_entries', [] );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Unresolved Genealogy Entries', 'tclas' ); ?></h1>
		<p><?php esc_html_e( 'These commune and surname entries could not be auto-resolved to the canonical list. Review and either dismiss (not a valid entry) or approve (add to connection-data.php for future users).', 'tclas' ); ?></p>

		<?php if ( empty( $queue ) ) : ?>
			<p><em><?php esc_html_e( 'No unresolved entries — the queue is clear.', 'tclas' ); ?></em></p>
		<?php else : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Type', 'tclas' ); ?></th>
						<th><?php esc_html_e( 'Entered value', 'tclas' ); ?></th>
						<th><?php esc_html_e( 'User', 'tclas' ); ?></th>
						<th><?php esc_html_e( 'Submitted', 'tclas' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'tclas' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $queue as $key => $entry ) :
						$u = get_userdata( (int) ( $entry['user_id'] ?? 0 ) );
						?>
					<tr>
						<td><strong><?php echo esc_html( ucfirst( $entry['type'] ?? '' ) ); ?></strong></td>
						<td><?php echo esc_html( $entry['value'] ?? '' ); ?></td>
						<td><?php echo $u ? esc_html( $u->display_name ) : '<em>Unknown</em>'; ?></td>
						<td><?php echo isset( $entry['submitted_at'] ) ? esc_html( date_i18n( get_option( 'date_format' ), $entry['submitted_at'] ) ) : '—'; ?></td>
						<td style="display:flex;gap:.5rem;">
							<form method="post">
								<?php wp_nonce_field( 'tclas_approve_entry', 'tclas_approve_nonce' ); ?>
								<input type="hidden" name="approve_key" value="<?php echo esc_attr( $key ); ?>">
								<button class="button button-primary" type="submit"><?php esc_html_e( 'Approve & add to data', 'tclas' ); ?></button>
							</form>
							<form method="post">
								<?php wp_nonce_field( 'tclas_dismiss_entry', 'tclas_dismiss_nonce' ); ?>
								<input type="hidden" name="dismiss_key" value="<?php echo esc_attr( $key ); ?>">
								<button class="button" type="submit"><?php esc_html_e( 'Dismiss', 'tclas' ); ?></button>
							</form>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
	<?php
}
