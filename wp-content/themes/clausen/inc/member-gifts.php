<?php
/**
 * Annual member gift — mailing list + per-year send log
 *
 * The society mails every member household one gift a year (a single envelope
 * per household, with one item per adult in it). This module answers the two
 * questions that come with that:
 *
 *   1. "Where do I mail it?"  — mailing addresses are captured at checkout into
 *      _tclas_mail_* meta (see inc/pmpro-checkout.php) and maintained by the
 *      member on the Edit Profile screen. This module reads them back out as a
 *      distribution list, with CSV export and a print-ready label sheet.
 *   2. "Did I already send it?" — a per-household, per-year log in
 *      {prefix}tclas_gift_log, recording status, item count, and a snapshot of
 *      the address the gift actually went to.
 *
 * The gift unit is the HOUSEHOLD, not the user account. A Household-tier owner
 * (TCLAS_LEVEL_HOUSEHOLD) gets one envelope holding one item per adult — the
 * owner plus their active sub-accounts. Sub-accounts (TCLAS_LEVEL_HOUSEHOLD_MEMBER)
 * never appear in the list on their own; they're counted inside the owner's row.
 *
 * Addresses are PII: they never surface on the front end except to the member
 * who owns them. Admin screens are manage_options only.
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Levels that receive a gift in their own right (i.e. not sub-accounts).
const TCLAS_GIFT_PRIMARY_LEVELS = [
	TCLAS_LEVEL_INDIVIDUAL,
	TCLAS_LEVEL_HOUSEHOLD,
	TCLAS_LEVEL_STUDENT,
	TCLAS_LEVEL_BENEFACTOR,
];

// Log statuses. 'skipped' covers "no usable address" and "asked not to receive".
const TCLAS_GIFT_STATUSES = [ 'sent', 'returned', 'skipped' ];

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 1 — Database table (per-household, per-year send log)
// ═══════════════════════════════════════════════════════════════════════════

define( 'TCLAS_GIFT_LOG_DB_VERSION', '1.0' );

/**
 * Return the gift log table name.
 */
function tclas_gift_log_table(): string {
	global $wpdb;
	return $wpdb->prefix . 'tclas_gift_log';
}

/**
 * Create or update the gift log table via dbDelta.
 *
 * One row per (household, year). The address/name snapshot is deliberate: a
 * member who moves in 2028 shouldn't rewrite where the 2026 gift was mailed.
 */
function tclas_gift_create_table(): void {
	if ( TCLAS_GIFT_LOG_DB_VERSION === get_option( 'tclas_gift_log_db_version', '' ) ) {
		return;
	}

	global $wpdb;
	$table   = tclas_gift_log_table();
	$charset = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE {$table} (
		id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		household_id   BIGINT UNSIGNED NOT NULL,
		gift_year      SMALLINT UNSIGNED NOT NULL,
		status         VARCHAR(20) NOT NULL DEFAULT 'sent',
		item_count     SMALLINT UNSIGNED NOT NULL DEFAULT 1,
		recipient_name VARCHAR(190) NOT NULL DEFAULT '',
		address_snapshot TEXT NULL,
		notes          TEXT NULL,
		sent_at        DATETIME NULL DEFAULT NULL,
		recorded_by    BIGINT UNSIGNED NULL DEFAULT NULL,
		created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		UNIQUE KEY household_year (household_id, gift_year),
		KEY gift_year (gift_year)
	) {$charset};";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );

	update_option( 'tclas_gift_log_db_version', TCLAS_GIFT_LOG_DB_VERSION );
}
add_action( 'after_switch_theme', 'tclas_gift_create_table' );
add_action( 'admin_init', 'tclas_gift_create_table' );

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 2 — Address helpers
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Meta key map for the mailing address. Also used by the checkout capture in
 * inc/pmpro-checkout.php and the member-facing form on Edit Profile.
 *
 * @return array<string,string> field slug => meta key
 */
function tclas_gift_address_fields(): array {
	return [
		'address1' => '_tclas_mail_address1',
		'address2' => '_tclas_mail_address2',
		'city'     => '_tclas_mail_city',
		'state'    => '_tclas_mail_state',
		'zip'      => '_tclas_mail_zip',
		'country'  => '_tclas_mail_country',
	];
}

/**
 * Read a member's mailing address.
 *
 * @return array<string,string> field slug => value (always all keys, possibly '')
 */
function tclas_gift_get_address( int $user_id ): array {
	$out = [];
	foreach ( tclas_gift_address_fields() as $slug => $meta_key ) {
		$out[ $slug ] = (string) ( get_user_meta( $user_id, $meta_key, true ) ?: '' );
	}
	return $out;
}

/**
 * Whether an address has enough to actually mail something.
 *
 * @param array<string,string> $addr
 */
function tclas_gift_address_is_mailable( array $addr ): bool {
	return '' !== trim( $addr['address1'] ?? '' )
		&& '' !== trim( $addr['city'] ?? '' )
		&& '' !== trim( $addr['zip'] ?? '' );
}

/**
 * When the member last confirmed their address (checkout counts as a confirm).
 *
 * @return string MySQL datetime, or '' if never.
 */
function tclas_gift_address_confirmed_at( int $user_id ): string {
	return (string) ( get_user_meta( $user_id, '_tclas_mail_confirmed_at', true ) ?: '' );
}

/**
 * Save a mailing address for a member and stamp it as confirmed.
 *
 * @param array<string,string> $input Raw (unsanitised) field slug => value.
 */
function tclas_gift_save_address( int $user_id, array $input ): void {
	foreach ( tclas_gift_address_fields() as $slug => $meta_key ) {
		if ( ! array_key_exists( $slug, $input ) ) {
			continue;
		}
		update_user_meta( $user_id, $meta_key, sanitize_text_field( wp_unslash( (string) $input[ $slug ] ) ) );
	}
	update_user_meta( $user_id, '_tclas_mail_confirmed_at', current_time( 'mysql' ) );
}

/**
 * Format an address as lines for a label or CSV.
 *
 * @param array<string,string> $addr
 * @return string[]
 */
function tclas_gift_address_lines( array $addr, string $recipient = '' ): array {
	$lines = [];
	if ( '' !== trim( $recipient ) ) {
		$lines[] = trim( $recipient );
	}
	foreach ( [ 'address1', 'address2' ] as $slug ) {
		if ( '' !== trim( $addr[ $slug ] ?? '' ) ) {
			$lines[] = trim( $addr[ $slug ] );
		}
	}

	$city    = trim( $addr['city']  ?? '' );
	$state   = trim( $addr['state'] ?? '' );
	$zip     = trim( $addr['zip']   ?? '' );
	$country = strtoupper( trim( $addr['country'] ?? '' ) );

	$is_us     = '' === $country || in_array( $country, [ 'US', 'USA', 'UNITED STATES' ], true );
	$is_canada = in_array( $country, [ 'CA', 'CAN', 'CANADA' ], true );

	if ( $is_us ) {
		// "Saint Paul, MN 55105"
		$last = trim( $city . ( $city && $state ? ', ' : '' ) . $state . ( $zip ? ' ' . $zip : '' ) );
	} elseif ( $is_canada ) {
		// Canada Post wants no comma and a wider gap: "Toronto ON  M5H 1A1"
		$last = trim( $city . ( $city && $state ? ' ' : '' ) . $state . ( $zip ? '  ' . $zip : '' ) );
	} else {
		// Most of Europe, Luxembourg included: postal code before the town,
		// e.g. "L-1247 Luxembourg". Any region gets its own line above.
		if ( '' !== $state ) {
			$lines[] = $state;
		}
		$last = trim( ( $zip ? $zip . ' ' : '' ) . $city );
	}
	if ( '' !== $last ) {
		$lines[] = $last;
	}

	// Country line only when it isn't the domestic default.
	if ( ! $is_us ) {
		$lines[] = $country;
	}

	return $lines;
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 3 — Building the distribution list
// ═══════════════════════════════════════════════════════════════════════════

/**
 * How many items go in one household's envelope.
 *
 * Household tier = the owner plus their active sub-accounts. Everyone else = 1.
 */
function tclas_gift_item_count( int $user_id, int $level_id ): int {
	if ( TCLAS_LEVEL_HOUSEHOLD !== $level_id || ! function_exists( 'tclas_household_member_ids' ) ) {
		return 1;
	}
	$children = count( tclas_household_member_ids( $user_id ) );
	return 1 + min( $children, TCLAS_HOUSEHOLD_MAX_SEATS );
}

/**
 * Every household due a gift, with address, item count, and this year's log row.
 *
 * One row per household (never per sub-account). Sorted by last name so the
 * screen, the CSV, and the label sheet all come out in the same order.
 *
 * @return array<int,array<string,mixed>>
 */
function tclas_gift_distribution_list( int $year ): array {
	global $wpdb;

	$levels       = implode( ',', array_map( 'intval', TCLAS_GIFT_PRIMARY_LEVELS ) );
	$members_tbl  = $wpdb->prefix . 'pmpro_memberships_users';

	// GROUP BY guards against a member holding more than one active row.
	// phpcs:disable WordPress.DB.DirectDatabaseQuery
	$rows = (array) $wpdb->get_results(
		"SELECT mu.user_id, MAX(mu.membership_id) AS membership_id
		 FROM {$members_tbl} mu
		 INNER JOIN {$wpdb->users} u ON u.ID = mu.user_id
		 WHERE mu.status = 'active' AND mu.membership_id IN ({$levels})
		 GROUP BY mu.user_id"
	);
	// phpcs:enable

	$log  = tclas_gift_log_for_year( $year );
	$list = [];

	foreach ( $rows as $row ) {
		$user_id = (int) $row->user_id;
		$user    = get_userdata( $user_id );
		if ( ! $user ) {
			continue;
		}

		$level_id = (int) $row->membership_id;
		$addr     = tclas_gift_get_address( $user_id );

		$list[] = [
			'user_id'      => $user_id,
			'level_id'     => $level_id,
			'level_name'   => tclas_gift_level_name( $level_id ),
			'name'         => tclas_gift_recipient_name( $user ),
			'last_name'    => (string) $user->last_name,
			'email'        => (string) $user->user_email,
			'address'      => $addr,
			'mailable'     => tclas_gift_address_is_mailable( $addr ),
			'confirmed_at' => tclas_gift_address_confirmed_at( $user_id ),
			'item_count'   => tclas_gift_item_count( $user_id, $level_id ),
			'log'          => $log[ $user_id ] ?? null,
		];
	}

	usort( $list, function ( $a, $b ) {
		$cmp = strcasecmp( $a['last_name'] ?: $a['name'], $b['last_name'] ?: $b['name'] );
		return 0 !== $cmp ? $cmp : strcasecmp( $a['name'], $b['name'] );
	} );

	return $list;
}

/**
 * Name to put on the envelope. Household tier gets "The Schmidt Household"
 * treatment only if we know a last name; otherwise the display name stands.
 */
function tclas_gift_recipient_name( WP_User $user ): string {
	$full = trim( $user->first_name . ' ' . $user->last_name );
	return '' !== $full ? $full : $user->display_name;
}

/**
 * Human label for a membership level.
 */
function tclas_gift_level_name( int $level_id ): string {
	$names = [
		TCLAS_LEVEL_INDIVIDUAL => __( 'Individual', 'tclas' ),
		TCLAS_LEVEL_HOUSEHOLD  => __( 'Household', 'tclas' ),
		TCLAS_LEVEL_STUDENT    => __( 'Student', 'tclas' ),
		TCLAS_LEVEL_BENEFACTOR => __( 'Benefactor', 'tclas' ),
	];
	return $names[ $level_id ] ?? sprintf( __( 'Level %d', 'tclas' ), $level_id );
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 4 — The log itself (read + write)
// ═══════════════════════════════════════════════════════════════════════════

/**
 * All log rows for a year, keyed by household id.
 *
 * @return array<int,object>
 */
function tclas_gift_log_for_year( int $year ): array {
	global $wpdb;
	$table = tclas_gift_log_table();

	// phpcs:disable WordPress.DB.DirectDatabaseQuery
	$rows = (array) $wpdb->get_results( $wpdb->prepare(
		"SELECT * FROM {$table} WHERE gift_year = %d",
		$year
	) );
	// phpcs:enable

	$out = [];
	foreach ( $rows as $row ) {
		$out[ (int) $row->household_id ] = $row;
	}
	return $out;
}

/**
 * Years that have any log rows, newest first — used for the year picker.
 *
 * @return int[]
 */
function tclas_gift_logged_years(): array {
	global $wpdb;
	$table = tclas_gift_log_table();
	// phpcs:disable WordPress.DB.DirectDatabaseQuery
	$years = $wpdb->get_col( "SELECT DISTINCT gift_year FROM {$table} ORDER BY gift_year DESC" );
	// phpcs:enable
	return array_map( 'intval', (array) $years );
}

/**
 * Record (or update) a household's gift for a year.
 *
 * Snapshots the recipient name and address as they stand right now, so the log
 * still tells the truth after the member moves.
 *
 * @param string $status One of TCLAS_GIFT_STATUSES.
 * @return bool Whether the row was written.
 */
function tclas_gift_record( int $household_id, int $year, string $status = 'sent', string $notes = '' ): bool {
	global $wpdb;

	if ( ! in_array( $status, TCLAS_GIFT_STATUSES, true ) ) {
		return false;
	}
	$user = get_userdata( $household_id );
	if ( ! $user ) {
		return false;
	}

	$level_id = 0;
	if ( function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
		$level    = pmpro_getMembershipLevelForUser( $household_id );
		$level_id = $level ? (int) $level->id : 0;
	}

	$name  = tclas_gift_recipient_name( $user );
	$addr  = tclas_gift_get_address( $household_id );
	$table = tclas_gift_log_table();

	$data = [
		'household_id'     => $household_id,
		'gift_year'        => $year,
		'status'           => $status,
		'item_count'       => tclas_gift_item_count( $household_id, $level_id ),
		'recipient_name'   => $name,
		'address_snapshot' => implode( "\n", tclas_gift_address_lines( $addr ) ),
		'notes'            => $notes,
		'sent_at'          => current_time( 'mysql' ),
		'recorded_by'      => get_current_user_id(),
	];

	$existing = $wpdb->get_var( $wpdb->prepare(
		"SELECT id FROM {$table} WHERE household_id = %d AND gift_year = %d",
		$household_id,
		$year
	) );

	if ( $existing ) {
		// Keep the original snapshot; only the status/notes are being revised.
		unset( $data['address_snapshot'], $data['recipient_name'], $data['household_id'], $data['gift_year'] );
		// An empty note means "no new note", not "erase the note that's there".
		if ( '' === $notes ) {
			unset( $data['notes'] );
		}
		return false !== $wpdb->update( $table, $data, [ 'id' => (int) $existing ] );
	}

	return false !== $wpdb->insert( $table, $data );
}

/**
 * Remove a household's log row for a year (undo a mistaken "sent").
 */
function tclas_gift_unrecord( int $household_id, int $year ): bool {
	global $wpdb;
	return false !== $wpdb->delete(
		tclas_gift_log_table(),
		[ 'household_id' => $household_id, 'gift_year' => $year ],
		[ '%d', '%d' ]
	);
}

/**
 * Drop log rows for a deleted user so the table doesn't accumulate orphans.
 */
function tclas_gift_on_delete_user( int $user_id ): void {
	global $wpdb;
	$wpdb->delete( tclas_gift_log_table(), [ 'household_id' => $user_id ], [ '%d' ] );
}
add_action( 'delete_user', 'tclas_gift_on_delete_user' );

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 5 — Admin screen
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Register the admin screen under Users.
 */
function tclas_gift_admin_menu(): void {
	add_users_page(
		__( 'Annual member gift', 'tclas' ),
		__( 'Member gifts', 'tclas' ),
		'manage_options',
		'tclas-member-gifts',
		'tclas_gift_admin_page'
	);
}
add_action( 'admin_menu', 'tclas_gift_admin_menu' );

/**
 * Resolve the year being viewed from the request.
 */
function tclas_gift_current_year(): int {
	$year = isset( $_GET['gift_year'] ) ? (int) $_GET['gift_year'] : 0;
	if ( $year < 2000 || $year > 2100 ) {
		$year = (int) current_time( 'Y' );
	}
	return $year;
}

/**
 * Resolve the active filter (all | unsent | missing).
 */
function tclas_gift_current_filter(): string {
	$filter = isset( $_GET['gift_filter'] ) ? sanitize_key( wp_unslash( $_GET['gift_filter'] ) ) : 'all';
	return in_array( $filter, [ 'all', 'unsent', 'missing' ], true ) ? $filter : 'all';
}

/**
 * Apply the screen filter to a distribution list.
 *
 * @param array<int,array<string,mixed>> $list
 * @return array<int,array<string,mixed>>
 */
function tclas_gift_apply_filter( array $list, string $filter ): array {
	if ( 'unsent' === $filter ) {
		return array_values( array_filter( $list, fn( $r ) => empty( $r['log'] ) ) );
	}
	if ( 'missing' === $filter ) {
		return array_values( array_filter( $list, fn( $r ) => ! $r['mailable'] ) );
	}
	return $list;
}

/**
 * Handle mark/unmark actions posted from the admin screen.
 */
function tclas_gift_handle_admin_post(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to do that.', 'tclas' ) );
	}
	check_admin_referer( 'tclas_gift_mark' );

	$year   = isset( $_POST['gift_year'] ) ? (int) $_POST['gift_year'] : (int) current_time( 'Y' );
	$action = isset( $_POST['gift_action'] ) ? sanitize_key( wp_unslash( $_POST['gift_action'] ) ) : '';
	$ids    = array_values( array_filter( array_map( 'intval', (array) ( $_POST['household_ids'] ?? [] ) ) ) );
	$notes  = isset( $_POST['gift_notes'] ) ? sanitize_text_field( wp_unslash( $_POST['gift_notes'] ) ) : '';
	$filter = isset( $_POST['gift_filter'] ) ? sanitize_key( wp_unslash( $_POST['gift_filter'] ) ) : 'all';
	$filter = in_array( $filter, [ 'all', 'unsent', 'missing' ], true ) ? $filter : 'all';
	$count  = 0;

	// "Mark everything currently listed" — resolve the list server-side rather
	// than trusting a bag of IDs from the form.
	if ( 'mark_all' === $action ) {
		$list   = tclas_gift_apply_filter( tclas_gift_distribution_list( $year ), $filter );
		$ids    = array_column( array_filter( $list, fn( $r ) => $r['mailable'] && empty( $r['log'] ) ), 'user_id' );
		$action = 'sent';
	}

	foreach ( $ids as $id ) {
		if ( 'unmark' === $action ) {
			$count += tclas_gift_unrecord( $id, $year ) ? 1 : 0;
			continue;
		}
		$status = in_array( $action, TCLAS_GIFT_STATUSES, true ) ? $action : 'sent';
		$count += tclas_gift_record( $id, $year, $status, $notes ) ? 1 : 0;
	}

	wp_safe_redirect( add_query_arg(
		[
			'page'        => 'tclas-member-gifts',
			'gift_year'   => $year,
			'gift_filter' => $filter,
			'gift_done'   => $count,
			'gift_undo'   => 'unmark' === $action ? 1 : 0,
		],
		admin_url( 'users.php' )
	) );
	exit;
}
add_action( 'admin_post_tclas_gift_mark', 'tclas_gift_handle_admin_post' );

/**
 * Render the admin screen.
 */
function tclas_gift_admin_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$year   = tclas_gift_current_year();
	$filter = tclas_gift_current_filter();
	$all    = tclas_gift_distribution_list( $year );
	$list   = tclas_gift_apply_filter( $all, $filter );

	// Summary counts are always for the whole year, not the filtered view.
	$households = count( $all );
	$items      = array_sum( array_column( $all, 'item_count' ) );
	$sent       = count( array_filter( $all, fn( $r ) => $r['log'] && 'sent' === $r['log']->status ) );
	$unmailable = count( array_filter( $all, fn( $r ) => ! $r['mailable'] ) );

	$years = tclas_gift_logged_years();
	$this_year = (int) current_time( 'Y' );
	if ( ! in_array( $this_year, $years, true ) ) {
		array_unshift( $years, $this_year );
	}
	if ( ! in_array( $year, $years, true ) ) {
		array_unshift( $years, $year );
	}

	$base_url = admin_url( 'users.php?page=tclas-member-gifts' );
	$done     = isset( $_GET['gift_done'] ) ? (int) $_GET['gift_done'] : 0;
	?>
	<div class="wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Annual member gift', 'tclas' ); ?></h1>
		<hr class="wp-header-end">

		<?php if ( $done > 0 ) : ?>
			<div class="notice notice-success is-dismissible"><p>
				<?php
				printf(
					esc_html(
						empty( $_GET['gift_undo'] )
							/* translators: %d: number of households */
							? _n( '%d household marked.', '%d households marked.', $done, 'tclas' )
							/* translators: %d: number of households */
							: _n( '%d household un-marked.', '%d households un-marked.', $done, 'tclas' )
					),
					(int) $done
				);
				?>
			</p></div>
		<?php endif; ?>

		<p class="description" style="max-width:46em">
			<?php esc_html_e( 'One envelope per household. Household-tier members get one item per adult on the account; everyone else gets one. Addresses come from checkout and from what members maintain on their Edit Profile screen.', 'tclas' ); ?>
		</p>

		<!-- ── Year + filter ─────────────────────────────────────────────── -->
		<form method="get" action="<?php echo esc_url( admin_url( 'users.php' ) ); ?>" style="margin:1rem 0">
			<input type="hidden" name="page" value="tclas-member-gifts">
			<label for="tclas-gift-year"><strong><?php esc_html_e( 'Gift year', 'tclas' ); ?></strong></label>
			<select name="gift_year" id="tclas-gift-year">
				<?php foreach ( $years as $y ) : ?>
					<option value="<?php echo esc_attr( $y ); ?>" <?php selected( $y, $year ); ?>><?php echo esc_html( $y ); ?></option>
				<?php endforeach; ?>
			</select>
			<select name="gift_filter">
				<option value="all"     <?php selected( $filter, 'all' ); ?>><?php esc_html_e( 'All households', 'tclas' ); ?></option>
				<option value="unsent"  <?php selected( $filter, 'unsent' ); ?>><?php esc_html_e( 'Not yet sent', 'tclas' ); ?></option>
				<option value="missing" <?php selected( $filter, 'missing' ); ?>><?php esc_html_e( 'Missing an address', 'tclas' ); ?></option>
			</select>
			<button type="submit" class="button"><?php esc_html_e( 'Show', 'tclas' ); ?></button>
		</form>

		<!-- ── Summary ───────────────────────────────────────────────────── -->
		<div class="tclas-gift-summary" style="display:flex;gap:2rem;flex-wrap:wrap;margin:1rem 0;padding:1rem;background:#fff;border:1px solid #c3c4c7">
			<div><strong style="font-size:1.6em;display:block"><?php echo esc_html( number_format_i18n( $households ) ); ?></strong><?php esc_html_e( 'households', 'tclas' ); ?></div>
			<div><strong style="font-size:1.6em;display:block"><?php echo esc_html( number_format_i18n( $items ) ); ?></strong><?php esc_html_e( 'items to pack', 'tclas' ); ?></div>
			<div><strong style="font-size:1.6em;display:block"><?php echo esc_html( number_format_i18n( $sent ) ); ?></strong><?php printf( esc_html__( 'sent in %d', 'tclas' ), (int) $year ); ?></div>
			<div><strong style="font-size:1.6em;display:block;<?php echo $unmailable ? 'color:#b32d2e' : ''; ?>"><?php echo esc_html( number_format_i18n( $unmailable ) ); ?></strong><?php esc_html_e( 'missing an address', 'tclas' ); ?></div>
		</div>

		<!-- ── Export actions ────────────────────────────────────────────── -->
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" target="_blank" style="margin:1rem 0;display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
			<?php wp_nonce_field( 'tclas_gift_export' ); ?>
			<input type="hidden" name="gift_year"   value="<?php echo esc_attr( $year ); ?>">
			<input type="hidden" name="gift_filter" value="<?php echo esc_attr( $filter ); ?>">
			<button type="submit" name="action" value="tclas_gift_export" class="button">
				<?php esc_html_e( 'Export CSV', 'tclas' ); ?>
			</button>
			<button type="submit" name="action" value="tclas_gift_labels" class="button">
				<?php esc_html_e( 'Print labels', 'tclas' ); ?>
			</button>
			<select name="label_sheet">
				<option value="5160"><?php esc_html_e( 'Avery 5160 — 30 per sheet (2⅝ × 1 in)', 'tclas' ); ?></option>
				<option value="5163"><?php esc_html_e( 'Avery 5163 — 10 per sheet (4 × 2 in)', 'tclas' ); ?></option>
			</select>
			<span class="description"><?php esc_html_e( 'Exports what the filter above is showing. Households without a mailable address are left out of the label sheet.', 'tclas' ); ?></span>
		</form>

		<!-- ── The list ──────────────────────────────────────────────────── -->
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'tclas_gift_mark' ); ?>
			<input type="hidden" name="action"      value="tclas_gift_mark">
			<input type="hidden" name="gift_year"   value="<?php echo esc_attr( $year ); ?>">
			<input type="hidden" name="gift_filter" value="<?php echo esc_attr( $filter ); ?>">

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<td class="check-column"><input type="checkbox" onclick="document.querySelectorAll('.tclas-gift-cb').forEach(cb=>cb.checked=this.checked)"></td>
						<th><?php esc_html_e( 'Member', 'tclas' ); ?></th>
						<th style="width:26%"><?php esc_html_e( 'Mailing address', 'tclas' ); ?></th>
						<th style="width:90px"><?php esc_html_e( 'Level', 'tclas' ); ?></th>
						<th style="width:70px"><?php esc_html_e( 'Items', 'tclas' ); ?></th>
						<th style="width:20%"><?php printf( esc_html__( '%d status', 'tclas' ), (int) $year ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php if ( empty( $list ) ) : ?>
					<tr><td colspan="6"><?php esc_html_e( 'No households match this view.', 'tclas' ); ?></td></tr>
				<?php endif; ?>

				<?php foreach ( $list as $row ) :
					$log   = $row['log'];
					$lines = tclas_gift_address_lines( $row['address'] );
					?>
					<tr>
						<th scope="row" class="check-column">
							<input type="checkbox" class="tclas-gift-cb" name="household_ids[]" value="<?php echo esc_attr( $row['user_id'] ); ?>">
						</th>
						<td>
							<strong><a href="<?php echo esc_url( get_edit_user_link( $row['user_id'] ) ); ?>"><?php echo esc_html( $row['name'] ); ?></a></strong><br>
							<span class="description"><?php echo esc_html( $row['email'] ); ?></span>
						</td>
						<td>
							<?php if ( $row['mailable'] ) : ?>
								<?php echo esc_html( implode( ' · ', $lines ) ); ?>
								<?php if ( $row['confirmed_at'] ) : ?>
									<br><span class="description"><?php
										/* translators: %s: human-readable time difference */
										printf( esc_html__( 'updated %s ago', 'tclas' ), esc_html( human_time_diff( strtotime( $row['confirmed_at'] ), current_time( 'timestamp' ) ) ) );
									?></span>
								<?php endif; ?>
							<?php else : ?>
								<em style="color:#b32d2e"><?php esc_html_e( 'No mailable address', 'tclas' ); ?></em>
								<br><a class="description" href="<?php echo esc_url( get_edit_user_link( $row['user_id'] ) ); ?>"><?php esc_html_e( 'Add one →', 'tclas' ); ?></a>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $row['level_name'] ); ?></td>
						<td><?php echo esc_html( number_format_i18n( $row['item_count'] ) ); ?></td>
						<td>
							<?php if ( $log ) : ?>
								<?php
								// Distinct icon per status — a returned envelope isn't a success.
								$marks = [
									'sent'     => [ 'dashicons-yes-alt',   '#00a32a', __( 'Sent', 'tclas' ) ],
									'returned' => [ 'dashicons-undo',      '#b32d2e', __( 'Returned', 'tclas' ) ],
									'skipped'  => [ 'dashicons-minus',     '#8c8f94', __( 'Skipped', 'tclas' ) ],
								];
								$mark = $marks[ $log->status ] ?? [ 'dashicons-marker', '#8c8f94', $log->status ];
								?>
								<span class="dashicons <?php echo esc_attr( $mark[0] ); ?>" style="color:<?php echo esc_attr( $mark[1] ); ?>"></span>
								<?php echo esc_html( $mark[2] ); ?>
								<span class="description">
									<?php echo esc_html( mysql2date( get_option( 'date_format' ), $log->sent_at ) ); ?>
								</span>
								<?php if ( ! empty( $log->notes ) ) : ?>
									<br><span class="description"><?php echo esc_html( $log->notes ); ?></span>
								<?php endif; ?>
							<?php else : ?>
								<span class="description"><?php esc_html_e( 'Not yet sent', 'tclas' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<div style="margin:1rem 0;display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
				<label for="tclas-gift-notes" class="screen-reader-text"><?php esc_html_e( 'Note', 'tclas' ); ?></label>
				<input type="text" id="tclas-gift-notes" name="gift_notes" class="regular-text" placeholder="<?php esc_attr_e( 'Optional note (e.g. “mailed with fall newsletter”)', 'tclas' ); ?>">
				<button type="submit" name="gift_action" value="sent" class="button button-primary"><?php esc_html_e( 'Mark selected as sent', 'tclas' ); ?></button>
				<button type="submit" name="gift_action" value="returned" class="button"><?php esc_html_e( 'Returned', 'tclas' ); ?></button>
				<button type="submit" name="gift_action" value="skipped" class="button"><?php esc_html_e( 'Skipped', 'tclas' ); ?></button>
				<button type="submit" name="gift_action" value="unmark" class="button"><?php esc_html_e( 'Clear', 'tclas' ); ?></button>
			</div>

			<p>
				<button type="submit" name="gift_action" value="mark_all" class="button">
					<?php printf( esc_html__( 'Mark every mailable household in this view as sent for %d', 'tclas' ), (int) $year ); ?>
				</button>
			</p>
		</form>
	</div>
	<?php
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 6 — CSV export + label sheet
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Shared guard for the export endpoints. Returns the resolved list.
 *
 * @return array<int,array<string,mixed>>
 */
function tclas_gift_export_guard( int &$year ): array {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to do that.', 'tclas' ) );
	}
	check_admin_referer( 'tclas_gift_export' );

	$year   = isset( $_POST['gift_year'] ) ? (int) $_POST['gift_year'] : (int) current_time( 'Y' );
	$filter = isset( $_POST['gift_filter'] ) ? sanitize_key( wp_unslash( $_POST['gift_filter'] ) ) : 'all';
	$filter = in_array( $filter, [ 'all', 'unsent', 'missing' ], true ) ? $filter : 'all';

	return tclas_gift_apply_filter( tclas_gift_distribution_list( $year ), $filter );
}

/**
 * CSV of the distribution list. Columns are mail-merge friendly — one column
 * per address part, so it drops straight into Word/Pages label merge as well.
 */
function tclas_gift_export_csv(): void {
	$year = 0;
	$list = tclas_gift_export_guard( $year );

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=tclas-member-gifts-' . $year . '.csv' );

	$out = fopen( 'php://output', 'w' );
	// BOM so Excel opens UTF-8 names (Schmitz, Hoffmann…) correctly.
	fwrite( $out, "\xEF\xBB\xBF" );

	fputcsv( $out, [
		'Name', 'First name', 'Last name', 'Email', 'Level', 'Items',
		'Address 1', 'Address 2', 'City', 'State', 'ZIP', 'Country',
		'Mailable', 'Address updated', "Status {$year}", "Sent {$year}", 'Notes',
	] );

	foreach ( $list as $row ) {
		$user = get_userdata( $row['user_id'] );
		$log  = $row['log'];
		fputcsv( $out, [
			$row['name'],
			$user ? $user->first_name : '',
			$user ? $user->last_name : '',
			$row['email'],
			$row['level_name'],
			$row['item_count'],
			$row['address']['address1'],
			$row['address']['address2'],
			$row['address']['city'],
			$row['address']['state'],
			$row['address']['zip'],
			$row['address']['country'],
			$row['mailable'] ? 'yes' : 'no',
			$row['confirmed_at'],
			$log ? $log->status : '',
			$log && $log->sent_at ? $log->sent_at : '',
			$log ? (string) $log->notes : '',
		] );
	}

	fclose( $out );
	exit;
}
add_action( 'admin_post_tclas_gift_export', 'tclas_gift_export_csv' );

/**
 * Print-ready label sheet.
 *
 * Laid out in inches against @page so the browser's print dialog maps 1:1 onto
 * the stock. Set margins to "None"/"Default" and scale to 100% — then run one
 * plain-paper proof over a real sheet before committing the labels.
 */
function tclas_gift_export_labels(): void {
	$year = 0;
	$list = tclas_gift_export_guard( $year );
	$list = array_values( array_filter( $list, fn( $r ) => $r['mailable'] ) );

	$sheet = isset( $_POST['label_sheet'] ) ? sanitize_key( wp_unslash( $_POST['label_sheet'] ) ) : '5160';

	// Avery specs: label size, gutter, grid, and the sheet's own top/left margins.
	$specs = [
		'5160' => [ 'w' => 2.625, 'h' => 1.0, 'cols' => 3, 'rows' => 10, 'gap' => 0.125,  'top' => 0.5, 'left' => 0.1875,  'font' => 10 ],
		'5163' => [ 'w' => 4.0,   'h' => 2.0, 'cols' => 2, 'rows' => 5,  'gap' => 0.1875, 'top' => 0.5, 'left' => 0.15625, 'font' => 12 ],
	];
	$s = $specs[ $sheet ] ?? $specs['5160'];

	// Chunk into explicit pages rather than letting the printer decide where the
	// grid breaks — a label sliced across a page boundary wastes a whole sheet.
	$pages = array_chunk( $list, $s['cols'] * $s['rows'] );

	nocache_headers();
	header( 'Content-Type: text/html; charset=utf-8' );
	?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<title><?php printf( esc_html__( 'TCLAS member gift labels — %d', 'tclas' ), (int) $year ); ?></title>
	<style>
		@page {
			size: letter portrait;
			margin: <?php echo esc_html( $s['top'] ); ?>in <?php echo esc_html( $s['left'] ); ?>in;
		}
		* { box-sizing: border-box; }
		body {
			margin: 0;
			font-family: Helvetica, Arial, sans-serif;
			color: #000;
			background: #f6f7f7;
		}
		.toolbar {
			padding: 1rem;
			background: #fff;
			border-bottom: 1px solid #c3c4c7;
			font-size: 14px;
			line-height: 1.5;
		}
		.toolbar button { font: inherit; padding: .4rem .9rem; cursor: pointer; }
		.sheet {
			display: grid;
			grid-template-columns: repeat(<?php echo (int) $s['cols']; ?>, <?php echo esc_html( $s['w'] ); ?>in);
			column-gap: <?php echo esc_html( $s['gap'] ); ?>in;
			grid-auto-rows: <?php echo esc_html( $s['h'] ); ?>in;
			background: #fff;
			margin: 1rem auto;
			padding: <?php echo esc_html( $s['top'] ); ?>in <?php echo esc_html( $s['left'] ); ?>in;
			width: 8.5in;
		}
		.sheet + .sheet { margin-top: 2rem; }
		.label {
			padding: 0.13in 0.18in;
			overflow: hidden;
			font-size: <?php echo (int) $s['font']; ?>pt;
			line-height: 1.22;
			display: flex;
			flex-direction: column;
			justify-content: center;
		}
		.label .name { font-weight: 700; }
		@media print {
			body { background: #fff; }
			.toolbar { display: none; }
			.sheet { margin: 0; padding: 0; width: auto; break-after: page; page-break-after: always; }
			.sheet:last-child { break-after: auto; page-break-after: auto; }
			.label { break-inside: avoid; page-break-inside: avoid; }
		}
	</style>
</head>
<body>
	<div class="toolbar">
		<button onclick="window.print()"><?php esc_html_e( 'Print', 'tclas' ); ?></button>
		&nbsp;
		<?php
		printf(
			/* translators: 1: number of labels, 2: Avery sheet number, 3: gift year */
			esc_html__( '%1$d labels · Avery %2$s · %3$d gift mailing', 'tclas' ),
			count( $list ),
			esc_html( $sheet ),
			(int) $year
		);
		?>
		<br>
		<small><?php esc_html_e( 'Print at 100% scale with page margins set to None. Proof one sheet on plain paper against a real label sheet before printing the stock.', 'tclas' ); ?></small>
	</div>

	<?php foreach ( $pages as $page ) : ?>
		<div class="sheet">
			<?php foreach ( $page as $row ) : ?>
				<div class="label">
					<?php
					$lines = tclas_gift_address_lines( $row['address'], $row['name'] );
					foreach ( $lines as $i => $line ) :
						?>
						<div class="<?php echo 0 === $i ? 'name' : ''; ?>"><?php echo esc_html( $line ); ?></div>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endforeach; ?>
</body>
</html>
	<?php
	exit;
}
add_action( 'admin_post_tclas_gift_labels', 'tclas_gift_export_labels' );

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 7 — Address on the admin user-profile screen
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Mailing address fields on the WP user edit screen — the fix-it path when an
 * envelope comes back marked undeliverable.
 */
function tclas_gift_user_profile_fields( WP_User $user ): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$addr         = tclas_gift_get_address( $user->ID );
	$confirmed_at = tclas_gift_address_confirmed_at( $user->ID );
	?>
	<h2><?php esc_html_e( 'Mailing address (annual gift)', 'tclas' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Where this member’s annual gift is mailed. Captured at checkout and maintained by the member on their Edit Profile screen.', 'tclas' ); ?>
		<?php if ( $confirmed_at ) : ?>
			<?php
			/* translators: %s: date */
			printf( esc_html__( 'Last updated %s.', 'tclas' ), esc_html( mysql2date( get_option( 'date_format' ), $confirmed_at ) ) );
			?>
		<?php endif; ?>
	</p>
	<table class="form-table" role="presentation">
		<?php
		$labels = [
			'address1' => __( 'Street address', 'tclas' ),
			'address2' => __( 'Apt / Suite', 'tclas' ),
			'city'     => __( 'City', 'tclas' ),
			'state'    => __( 'State', 'tclas' ),
			'zip'      => __( 'ZIP', 'tclas' ),
			'country'  => __( 'Country', 'tclas' ),
		];
		foreach ( $labels as $slug => $label ) :
			?>
			<tr>
				<th><label for="tclas_mail_<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></label></th>
				<td>
					<input
						type="text"
						id="tclas_mail_<?php echo esc_attr( $slug ); ?>"
						name="tclas_mail_<?php echo esc_attr( $slug ); ?>"
						value="<?php echo esc_attr( $addr[ $slug ] ); ?>"
						class="regular-text"
					>
				</td>
			</tr>
		<?php endforeach; ?>
	</table>
	<?php wp_nonce_field( 'tclas_gift_address_' . $user->ID, 'tclas_gift_address_nonce' );
}
add_action( 'show_user_profile', 'tclas_gift_user_profile_fields' );
add_action( 'edit_user_profile', 'tclas_gift_user_profile_fields' );

/**
 * Save the admin-side mailing address fields.
 */
function tclas_gift_save_user_profile_fields( int $user_id ): void {
	if ( ! isset( $_POST['tclas_gift_address_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tclas_gift_address_nonce'] ) ), 'tclas_gift_address_' . $user_id )
	) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$input = [];
	foreach ( array_keys( tclas_gift_address_fields() ) as $slug ) {
		if ( isset( $_POST[ 'tclas_mail_' . $slug ] ) ) {
			$input[ $slug ] = $_POST[ 'tclas_mail_' . $slug ]; // sanitised in tclas_gift_save_address()
		}
	}
	if ( $input ) {
		tclas_gift_save_address( $user_id, $input );
	}
}
add_action( 'personal_options_update',  'tclas_gift_save_user_profile_fields' );
add_action( 'edit_user_profile_update', 'tclas_gift_save_user_profile_fields' );
