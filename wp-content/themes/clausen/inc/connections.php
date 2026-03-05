<?php
/**
 * "How Are We Connected" — matching engine, AJAX handlers, cron, and admin UI.
 *
 * Data flow:
 *   1. Member saves profile via page-my-story.php (POST form).
 *   2. tclas_save_member_story() normalises input and stores structured lineage
 *      data (commune→surname pairings) plus derived flat arrays.
 *   3. tclas_compute_connections() runs immediately after save, then nightly via
 *      WP-Cron for the full member base.
 *   4. Results are cached in _tclas_connections_cache user meta.
 *   5. The dashboard panel reads from cache; AJAX endpoints handle dismiss/seen.
 *
 * User meta keys:
 *   _tclas_lineages             array[]    [{commune_raw, commune_norm, surnames_raw[], surnames_norm[]}]
 *   _tclas_unassigned_surnames_raw  string[]  Surnames without a known commune (display)
 *   _tclas_unassigned_surnames_norm string[]  Normalised cluster heads (match)
 *   _tclas_communes_norm        string[]   Derived flat array of commune slugs (map + queries)
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
 * Persist a member's Luxembourg story fields using the lineage model.
 *
 * Lineage model: each commune has zero or more paired surnames. Surnames
 * without a known commune go in the "unassigned" bucket.
 *
 * Called both from the frontend POST handler (page-my-story.php) and the
 * WP admin user-edit screen.
 *
 * @param int      $user_id              WP user ID.
 * @param array    $lineages_input       [{commune_raw: string, surnames_raw: string[]}]
 * @param string[] $unassigned_raw       Surnames without a known commune (unsanitised).
 * @param string   $visibility           'members' | 'board' | 'hidden'.
 * @param bool     $open_to_contact      Whether the member accepts hub messages.
 */
function tclas_save_member_story(
	int    $user_id,
	array  $lineages_input,
	array  $unassigned_raw,
	string $visibility      = 'members',
	bool   $open_to_contact = true
): void {

	$lineages       = [];
	$communes_norm  = [];
	$unresolved     = [];
	$seen_communes  = [];

	// ── Process lineage cards (commune + paired surnames) ─────────────
	foreach ( $lineages_input as $card ) {
		$commune_raw = sanitize_text_field( $card['commune_raw'] ?? '' );
		if ( '' === $commune_raw ) {
			continue;
		}

		// Deduplicate communes (case-insensitive).
		$key = mb_strtolower( trim( $commune_raw ) );
		if ( isset( $seen_communes[ $key ] ) ) {
			continue;
		}
		$seen_communes[ $key ] = true;

		// Resolve commune.
		$commune_slug = tclas_resolve_commune( $commune_raw );
		if ( ! $commune_slug ) {
			$unresolved[]  = [ 'type' => 'commune', 'value' => $commune_raw, 'user_id' => $user_id ];
			$commune_slug  = 'unresolved:' . tclas_normalize_string( $commune_raw );
		}
		$communes_norm[] = $commune_slug;

		// Process paired surnames.
		$surnames_raw_list  = (array) ( $card['surnames_raw'] ?? [] );
		$s_raw_clean        = [];
		$s_norm_clean       = [];
		$seen_surnames_card = [];

		foreach ( $surnames_raw_list as $sraw ) {
			$sraw = sanitize_text_field( $sraw );
			if ( '' === $sraw ) {
				continue;
			}
			$skey = mb_strtolower( trim( $sraw ) );
			if ( isset( $seen_surnames_card[ $skey ] ) ) {
				continue;
			}
			$seen_surnames_card[ $skey ] = true;

			$s_raw_clean[] = $sraw;
			$head = tclas_resolve_surname( $sraw );
			if ( $head ) {
				$s_norm_clean[] = $head;
			} else {
				$unresolved[]   = [ 'type' => 'surname', 'value' => $sraw, 'user_id' => $user_id ];
				$s_norm_clean[] = 'unresolved:' . tclas_normalize_string( $sraw );
			}
		}

		$lineages[] = [
			'commune_raw'   => $commune_raw,
			'commune_norm'  => $commune_slug,
			'surnames_raw'  => $s_raw_clean,
			'surnames_norm' => array_values( array_unique( $s_norm_clean ) ),
		];
	}

	// ── Process unassigned surnames ───────────────────────────────────
	$ua_raw_clean    = [];
	$ua_norm_clean   = [];
	$seen_unassigned = [];

	foreach ( $unassigned_raw as $sraw ) {
		$sraw = sanitize_text_field( $sraw );
		if ( '' === $sraw ) {
			continue;
		}
		$skey = mb_strtolower( trim( $sraw ) );
		if ( isset( $seen_unassigned[ $skey ] ) ) {
			continue;
		}
		$seen_unassigned[ $skey ] = true;

		$ua_raw_clean[] = $sraw;
		$head = tclas_resolve_surname( $sraw );
		if ( $head ) {
			$ua_norm_clean[] = $head;
		} else {
			$unresolved[]    = [ 'type' => 'surname', 'value' => $sraw, 'user_id' => $user_id ];
			$ua_norm_clean[] = 'unresolved:' . tclas_normalize_string( $sraw );
		}
	}

	// ── Persist user meta ──────────────────────────────────────────────
	update_user_meta( $user_id, '_tclas_lineages',                $lineages );
	update_user_meta( $user_id, '_tclas_unassigned_surnames_raw',  $ua_raw_clean );
	update_user_meta( $user_id, '_tclas_unassigned_surnames_norm', array_values( array_unique( $ua_norm_clean ) ) );
	update_user_meta( $user_id, '_tclas_communes_norm',           array_values( array_unique( $communes_norm ) ) );

	// Clean up legacy flat keys.
	delete_user_meta( $user_id, '_tclas_communes_raw' );
	delete_user_meta( $user_id, '_tclas_surnames_raw' );
	delete_user_meta( $user_id, '_tclas_surnames_norm' );

	$allowed_vis = [ 'members', 'board', 'hidden' ];
	update_user_meta( $user_id, '_tclas_visibility',      in_array( $visibility, $allowed_vis, true ) ? $visibility : 'members' );
	update_user_meta( $user_id, '_tclas_open_to_contact',  $open_to_contact ? 1 : 0 );

	$has_communes  = ! empty( $lineages );
	$has_surnames  = ! empty( $ua_raw_clean )
		|| ! empty( array_filter( $lineages, fn( $l ) => ! empty( $l['surnames_raw'] ) ) );
	$complete      = ( $has_communes || $has_surnames ) ? 1 : 0;
	update_user_meta( $user_id, '_tclas_profile_complete', $complete );

	// ── Queue unresolved entries for admin review ──────────────────────
	if ( ! empty( $unresolved ) ) {
		$queue = get_option( 'tclas_unresolved_entries', [] );
		foreach ( $unresolved as $entry ) {
			$qkey = $entry['type'] . ':' . $entry['value'];
			if ( ! isset( $queue[ $qkey ] ) ) {
				$queue[ $qkey ] = array_merge( $entry, [ 'submitted_at' => time() ] );
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
 * Helper: collect all surname norms from a user's lineages + unassigned bucket.
 *
 * @return string[] Flat array of normalised surname heads.
 */
function tclas_get_all_surname_norms( int $user_id ): array {
	$lineages   = (array) ( get_user_meta( $user_id, '_tclas_lineages', true ) ?: [] );
	$unassigned = (array) ( get_user_meta( $user_id, '_tclas_unassigned_surnames_norm', true ) ?: [] );

	$all = $unassigned;
	foreach ( $lineages as $l ) {
		if ( ! empty( $l['surnames_norm'] ) && is_array( $l['surnames_norm'] ) ) {
			$all = array_merge( $all, $l['surnames_norm'] );
		}
	}
	return array_values( array_unique( $all ) );
}

/**
 * Helper: build a commune_slug → [surname_norm, …] map from lineages.
 *
 * @return array<string, string[]> Commune slug to array of normalised surnames.
 */
function tclas_lineage_pairs( int $user_id ): array {
	$lineages = (array) ( get_user_meta( $user_id, '_tclas_lineages', true ) ?: [] );
	$pairs    = [];
	foreach ( $lineages as $l ) {
		$slug = $l['commune_norm'] ?? '';
		if ( '' === $slug ) {
			continue;
		}
		$pairs[ $slug ] = array_values( array_unique(
			is_array( $l['surnames_norm'] ?? null ) ? $l['surnames_norm'] : []
		) );
	}
	return $pairs;
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
 * Three-tier matching:
 *   - Paired:       same surname associated with the same commune by both members.
 *   - Commune-only: shared commune, no paired surname overlap.
 *   - Surname-only: shared surname (from any lineage or unassigned), not already paired.
 *
 * Uses a single bulk meta query to avoid N+1 overhead.
 *
 * @return array Connection objects keyed by matched user ID.
 */
function tclas_compute_connections( int $user_id ): array {
	global $wpdb;

	$my_communes  = (array) ( get_user_meta( $user_id, '_tclas_communes_norm', true ) ?: [] );
	$my_surnames  = tclas_get_all_surname_norms( $user_id );
	$my_pairs     = tclas_lineage_pairs( $user_id );

	$connections = [];

	if ( empty( $my_communes ) && empty( $my_surnames ) ) {
		update_user_meta( $user_id, '_tclas_connections_cache', [] );
		update_user_meta( $user_id, '_tclas_connections_computed_at', time() );
		return [];
	}

	// Get candidate IDs (active members with completed profiles).
	if ( function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
		$ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT user_id
			 FROM {$wpdb->prefix}pmpro_memberships_users
			 WHERE status = 'active'
			   AND user_id != %d",
			$user_id
		) );
	} else {
		$ids = get_users( [
			'fields'  => 'ID',
			'exclude' => [ $user_id ],
			'number'  => 1000,
		] );
	}

	$ids = array_map( 'intval', $ids );
	if ( empty( $ids ) ) {
		update_user_meta( $user_id, '_tclas_connections_cache', [] );
		update_user_meta( $user_id, '_tclas_connections_computed_at', time() );
		return [];
	}

	// Bulk-load all relevant meta for candidates in a single query.
	$meta_keys = [
		'_tclas_profile_complete', '_tclas_visibility',
		'_tclas_communes_norm', '_tclas_lineages',
		'_tclas_unassigned_surnames_norm',
	];
	$id_placeholders  = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
	$key_placeholders = implode( ',', array_fill( 0, count( $meta_keys ), '%s' ) );
	// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
	$meta_rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT user_id, meta_key, meta_value
		 FROM {$wpdb->usermeta}
		 WHERE user_id IN ({$id_placeholders})
		   AND meta_key IN ({$key_placeholders})",
		...array_merge( $ids, $meta_keys )
	) );

	// Index meta by user_id → meta_key → meta_value.
	$meta_index = [];
	foreach ( $meta_rows as $row ) {
		$meta_index[ (int) $row->user_id ][ $row->meta_key ] = $row->meta_value;
	}

	// Load existing cache once (not per candidate).
	$existing = get_user_meta( $user_id, '_tclas_connections_cache', true );
	if ( ! is_array( $existing ) ) {
		$existing = [];
	}

	foreach ( $ids as $candidate_id ) {
		$cmeta = $meta_index[ $candidate_id ] ?? [];

		// Filter: must have complete profile and not be hidden.
		if ( empty( $cmeta['_tclas_profile_complete'] ) ) {
			continue;
		}
		$vis = $cmeta['_tclas_visibility'] ?? 'members';
		if ( 'hidden' === $vis ) {
			continue;
		}

		// Deserialize candidate data.
		$their_communes = maybe_unserialize( $cmeta['_tclas_communes_norm'] ?? '' );
		$their_communes = is_array( $their_communes ) ? $their_communes : [];

		$their_lineages = maybe_unserialize( $cmeta['_tclas_lineages'] ?? '' );
		$their_lineages = is_array( $their_lineages ) ? $their_lineages : [];

		$their_unassigned = maybe_unserialize( $cmeta['_tclas_unassigned_surnames_norm'] ?? '' );
		$their_unassigned = is_array( $their_unassigned ) ? $their_unassigned : [];

		// Build their pairs + all-surname set.
		$their_pairs = [];
		$their_all_surnames = $their_unassigned;
		foreach ( $their_lineages as $l ) {
			$slug = $l['commune_norm'] ?? '';
			if ( '' !== $slug && ! empty( $l['surnames_norm'] ) && is_array( $l['surnames_norm'] ) ) {
				$their_pairs[ $slug ] = array_values( array_unique( $l['surnames_norm'] ) );
				$their_all_surnames   = array_merge( $their_all_surnames, $l['surnames_norm'] );
			}
		}
		$their_all_surnames = array_values( array_unique( $their_all_surnames ) );

		// ── Compute three tiers ──────────────────────────────────────
		$paired_matches   = []; // [{commune: slug, surname: head}, …]
		$commune_only     = []; // [slug, …]
		$surname_only     = []; // [head, …]
		$paired_surnames  = []; // Track which surnames are already paired.

		// Shared communes.
		$shared_comm = array_values( array_intersect( $my_communes, $their_communes ) );

		// For each shared commune, look for paired surname overlaps.
		foreach ( $shared_comm as $slug ) {
			$my_surv_in_comm    = $my_pairs[ $slug ] ?? [];
			$their_surv_in_comm = $their_pairs[ $slug ] ?? [];
			$paired_in_comm     = array_values( array_intersect( $my_surv_in_comm, $their_surv_in_comm ) );

			if ( ! empty( $paired_in_comm ) ) {
				foreach ( $paired_in_comm as $head ) {
					$paired_matches[]             = [ 'commune' => $slug, 'surname' => $head ];
					$paired_surnames[ $head ]      = true;
				}
			} else {
				$commune_only[] = $slug;
			}
		}

		// Surname-only: shared surnames not already counted as paired.
		$shared_surv = array_values( array_intersect( $my_surnames, $their_all_surnames ) );
		foreach ( $shared_surv as $head ) {
			if ( ! isset( $paired_surnames[ $head ] ) ) {
				$surname_only[] = $head;
			}
		}

		if ( empty( $paired_matches ) && empty( $commune_only ) && empty( $surname_only ) ) {
			continue;
		}

		// Preserve existing seen/dismissed state when re-computing.
		$prev = $existing[ $candidate_id ] ?? [];

		$connections[ $candidate_id ] = [
			'user_id'         => $candidate_id,
			'paired_matches'  => $paired_matches,
			'commune_only'    => $commune_only,
			'surname_only'    => $surname_only,
			'score'           => tclas_connection_score( $paired_matches, $commune_only, $surname_only ),
			'computed_at'     => time(),
			'seen'            => $prev['seen']      ?? false,
			'dismissed'       => $prev['dismissed'] ?? false,
		];
	}

	// Sort by score descending.
	uasort( $connections, fn( $a, $b ) => $b['score'] <=> $a['score'] );

	update_user_meta( $user_id, '_tclas_connections_cache', $connections );
	update_user_meta( $user_id, '_tclas_connections_computed_at', time() );

	return $connections;
}

/**
 * Score a connection using the three-tier model.
 *
 * Scoring matrix:
 *   Paired (commune+surname): 6 pts first, +3 each additional.
 *   Commune-only:             2 pts first, +1 each additional.
 *   Surname-only:             2 pts first, +1 each additional.
 */
function tclas_connection_score( array $paired, array $commune_only, array $surname_only ): int {
	$score = 0;
	$pc = count( $paired );
	$cc = count( $commune_only );
	$sc = count( $surname_only );

	if ( $pc > 0 ) {
		$score += 6 + max( 0, $pc - 1 ) * 3;
	}
	if ( $cc > 0 ) {
		$score += 2 + max( 0, $cc - 1 );
	}
	if ( $sc > 0 ) {
		$score += 2 + max( 0, $sc - 1 );
	}

	return $score;
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
 * Build the display label for a single canonical commune slug,
 * using the user's own raw entry if available.
 */
function tclas_display_commune( string $slug, int $for_user_id ): string {
	$lineages = (array) ( get_user_meta( $for_user_id, '_tclas_lineages', true ) ?: [] );
	foreach ( $lineages as $l ) {
		if ( ( $l['commune_norm'] ?? '' ) === $slug ) {
			return $l['commune_raw'] ?? '';
		}
	}
	$all = tclas_commune_aliases();
	if ( isset( $all[ $slug ] ) ) {
		return $all[ $slug ]['label'];
	}
	return ucwords( str_replace( '-', ' ', $slug ) );
}

/**
 * Build the display labels (raw values) for a list of canonical commune slugs.
 */
function tclas_display_communes( array $slugs, int $for_user_id ): array {
	return array_map( fn( $slug ) => tclas_display_commune( $slug, $for_user_id ), $slugs );
}

/**
 * Build the display label for a single canonical surname cluster head,
 * using the user's own raw entry if available.
 */
function tclas_display_surname( string $head, int $for_user_id ): string {
	// Search lineages first.
	$lineages = (array) ( get_user_meta( $for_user_id, '_tclas_lineages', true ) ?: [] );
	foreach ( $lineages as $l ) {
		$norms = is_array( $l['surnames_norm'] ?? null ) ? $l['surnames_norm'] : [];
		$raws  = is_array( $l['surnames_raw']  ?? null ) ? $l['surnames_raw']  : [];
		$idx   = array_search( $head, $norms, true );
		if ( false !== $idx && isset( $raws[ $idx ] ) ) {
			return $raws[ $idx ];
		}
	}
	// Search unassigned.
	$ua_raw  = (array) ( get_user_meta( $for_user_id, '_tclas_unassigned_surnames_raw',  true ) ?: [] );
	$ua_norm = (array) ( get_user_meta( $for_user_id, '_tclas_unassigned_surnames_norm', true ) ?: [] );
	$idx     = array_search( $head, $ua_norm, true );
	if ( false !== $idx && isset( $ua_raw[ $idx ] ) ) {
		return $ua_raw[ $idx ];
	}
	$all = tclas_surname_clusters();
	if ( isset( $all[ $head ] ) ) {
		return $all[ $head ]['label'];
	}
	return ucfirst( $head );
}

/**
 * Build display labels for canonical surname cluster heads.
 */
function tclas_display_surnames( array $heads, int $for_user_id ): array {
	return array_map( fn( $head ) => tclas_display_surname( $head, $for_user_id ), $heads );
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
 * Uses the three-tier model: paired, commune-only, surname-only.
 *
 * @return string  Plain text (already escaped).
 */
function tclas_connection_sentence(
	int   $my_id,
	int   $their_id,
	array $connection
): string {
	$their_name = esc_html( get_the_author_meta( 'display_name', $their_id ) );

	$paired   = $connection['paired_matches'] ?? [];
	$c_only   = $connection['commune_only']   ?? [];
	$s_only   = $connection['surname_only']   ?? [];

	$parts = [];

	// ── Paired matches (strongest signal) ──────────────────────────────
	if ( ! empty( $paired ) ) {
		// Group by commune for natural phrasing.
		$by_commune = [];
		foreach ( $paired as $p ) {
			$by_commune[ $p['commune'] ][] = $p['surname'];
		}

		$pair_phrases = [];
		foreach ( $by_commune as $slug => $heads ) {
			$commune_label = esc_html( tclas_display_commune( $slug, $my_id ) );
			$surname_list  = tclas_human_list( tclas_display_surnames( $heads, $my_id ) );
			$pair_phrases[] = sprintf(
				/* translators: 1: surname(s) 2: commune */
				esc_html__( 'the %1$s name in %2$s', 'tclas' ),
				$surname_list,
				$commune_label
			);
		}
		$parts[] = sprintf(
			/* translators: 1: their name 2: lineage details */
			esc_html__( 'You and %1$s both trace %2$s — probable kinship!', 'tclas' ),
			$their_name,
			implode( '; ', $pair_phrases )
		);
	}

	// ── Commune-only matches ───────────────────────────────────────────
	if ( ! empty( $c_only ) ) {
		$comm_labels = tclas_display_communes( $c_only, $my_id );
		$parts[]     = empty( $parts )
			? sprintf(
				esc_html__( 'You and %1$s both have ancestry in %2$s.', 'tclas' ),
				$their_name,
				tclas_human_list( $comm_labels )
			)
			: sprintf(
				esc_html__( 'You also share roots in %s.', 'tclas' ),
				tclas_human_list( $comm_labels )
			);
	}

	// ── Surname-only matches ───────────────────────────────────────────
	if ( ! empty( $s_only ) ) {
		$surv_labels = tclas_display_surnames( $s_only, $my_id );
		$parts[]     = empty( $parts )
			? sprintf(
				esc_html__( 'You and %1$s share the surname %2$s.', 'tclas' ),
				$their_name,
				tclas_human_list( $surv_labels )
			)
			: sprintf(
				esc_html__( 'You also share the surname %s.', 'tclas' ),
				tclas_human_list( $surv_labels )
			);
	}

	return implode( ' ', $parts );
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 5 — WP-Cron nightly batch
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Register a daily cron event if not already scheduled.
 */
function tclas_schedule_connection_cron(): void {
	if ( ! wp_next_scheduled( 'tclas_connection_cron' ) ) {
		$tomorrow_2am = wp_date( 'U', strtotime( 'tomorrow 02:00:00', current_datetime()->getTimestamp() ) );
		wp_schedule_event( (int) $tomorrow_2am, 'daily', 'tclas_connection_cron' );
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

	// Build lineage input from POST.
	$lineages_input = tclas_parse_lineage_post_data( $_POST );
	$unassigned_raw = array_filter( (array) ( $_POST['tclas_unassigned_surnames'] ?? [] ), 'strlen' );
	$visibility     = sanitize_text_field( $_POST['visibility'] ?? 'members' );
	$open_to_contact = ! empty( $_POST['open_to_contact'] );

	tclas_save_member_story( $user_id, $lineages_input, $unassigned_raw, $visibility, $open_to_contact );

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

/**
 * Parse lineage POST data from the form into the structured format.
 *
 * Expected POST keys:
 *   tclas_lineage_commune[]        — one commune per card
 *   tclas_lineage_surnames[0][]    — surnames for card 0
 *   tclas_lineage_surnames[1][]    — surnames for card 1, etc.
 *
 * @return array [{commune_raw: string, surnames_raw: string[]}, …]
 */
function tclas_parse_lineage_post_data( array $post ): array {
	$communes_raw = (array) ( $post['tclas_lineage_commune'] ?? [] );
	$surnames_all = (array) ( $post['tclas_lineage_surnames'] ?? [] );

	$lineages = [];
	foreach ( $communes_raw as $i => $commune ) {
		$commune = trim( (string) $commune );
		if ( '' === $commune ) {
			continue;
		}
		$lineages[] = [
			'commune_raw'  => $commune,
			'surnames_raw' => array_filter( (array) ( $surnames_all[ $i ] ?? [] ), 'strlen' ),
		];
	}
	return $lineages;
}

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

	$lineages        = (array) ( get_user_meta( $user->ID, '_tclas_lineages',                true ) ?: [] );
	$unassigned_raw  = (array) ( get_user_meta( $user->ID, '_tclas_unassigned_surnames_raw',  true ) ?: [] );
	$visibility      = get_user_meta( $user->ID, '_tclas_visibility', true ) ?: 'members';
	$open_to_contact = (bool) get_user_meta( $user->ID, '_tclas_open_to_contact', true );
	?>
	<h2><?php esc_html_e( 'Luxembourg Story (TCLAS)', 'tclas' ); ?></h2>
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Ancestral lineages', 'tclas' ); ?></th>
			<td>
				<p class="description"><?php esc_html_e( 'Commune → paired surnames. One commune per row, comma-separated surnames.', 'tclas' ); ?></p>
				<?php foreach ( $lineages as $i => $l ) : ?>
					<p style="margin-bottom:.5rem;">
						<input type="text" name="tclas_lineage_commune[]"
							   value="<?php echo esc_attr( $l['commune_raw'] ?? '' ); ?>"
							   class="regular-text" placeholder="<?php esc_attr_e( 'Commune', 'tclas' ); ?>"
							   style="width:180px;">
						→
						<input type="text" name="tclas_lineage_surnames_csv[]"
							   value="<?php echo esc_attr( implode( ', ', (array) ( $l['surnames_raw'] ?? [] ) ) ); ?>"
							   class="regular-text" placeholder="<?php esc_attr_e( 'Surnames (comma-separated)', 'tclas' ); ?>"
							   style="width:320px;">
					</p>
				<?php endforeach; ?>
				<p style="margin-bottom:.5rem;">
					<input type="text" name="tclas_lineage_commune[]" value="" class="regular-text"
						   placeholder="<?php esc_attr_e( 'Add commune…', 'tclas' ); ?>" style="width:180px;">
					→
					<input type="text" name="tclas_lineage_surnames_csv[]" value="" class="regular-text"
						   placeholder="<?php esc_attr_e( 'Surnames (comma-separated)', 'tclas' ); ?>" style="width:320px;">
				</p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Unassigned surnames', 'tclas' ); ?></th>
			<td>
				<?php foreach ( $unassigned_raw as $s ) : ?>
					<p><input type="text" name="tclas_unassigned_surnames[]" value="<?php echo esc_attr( $s ); ?>" class="regular-text"></p>
				<?php endforeach; ?>
				<p><input type="text" name="tclas_unassigned_surnames[]" value="" class="regular-text" placeholder="<?php esc_attr_e( 'Add surname…', 'tclas' ); ?>"></p>
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

	// Build lineage input from admin fields (CSV surnames per commune).
	$admin_communes = (array) ( $_POST['tclas_lineage_commune']      ?? [] );
	$admin_csvs     = (array) ( $_POST['tclas_lineage_surnames_csv'] ?? [] );

	$lineages_input = [];
	foreach ( $admin_communes as $i => $commune ) {
		$commune = trim( (string) $commune );
		if ( '' === $commune ) {
			continue;
		}
		$csv     = trim( (string) ( $admin_csvs[ $i ] ?? '' ) );
		$surnames = '' !== $csv
			? array_values( array_filter( array_map( 'trim', explode( ',', $csv ) ), 'strlen' ) )
			: [];
		$lineages_input[] = [
			'commune_raw'  => $commune,
			'surnames_raw' => $surnames,
		];
	}

	$unassigned_raw  = array_filter( (array) ( $_POST['tclas_unassigned_surnames'] ?? [] ), 'strlen' );
	$visibility      = sanitize_text_field( $_POST['tclas_visibility'] ?? 'members' );
	$open_to_contact = ! empty( $_POST['tclas_open_to_contact'] );

	tclas_save_member_story( $user_id, $lineages_input, $unassigned_raw, $visibility, $open_to_contact );
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
