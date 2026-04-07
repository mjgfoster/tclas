<?php
/**
 * Member-to-member messaging
 *
 * Custom table for message storage, core CRUD functions, AJAX endpoints,
 * rewrite rules, and email notifications.
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 1 — Database table
// ═══════════════════════════════════════════════════════════════════════════

define( 'TCLAS_MESSAGES_DB_VERSION', '1.0' );

/**
 * Create or update the messages table via dbDelta.
 */
function tclas_messaging_create_table(): void {
	$installed = get_option( 'tclas_messages_db_version', '' );
	if ( TCLAS_MESSAGES_DB_VERSION === $installed ) {
		return;
	}

	global $wpdb;
	$table   = $wpdb->prefix . 'tclas_messages';
	$charset = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE {$table} (
		id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		sender_id     BIGINT UNSIGNED NOT NULL,
		recipient_id  BIGINT UNSIGNED NOT NULL,
		message       TEXT NOT NULL,
		read_status   TINYINT(1) NOT NULL DEFAULT 0,
		created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY sender_id (sender_id),
		KEY recipient_id (recipient_id),
		KEY conversation (sender_id, recipient_id)
	) {$charset};";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );

	update_option( 'tclas_messages_db_version', TCLAS_MESSAGES_DB_VERSION );
}
add_action( 'after_switch_theme', 'tclas_messaging_create_table' );
add_action( 'admin_init', 'tclas_messaging_create_table' );

/**
 * Return the messages table name.
 */
function tclas_messages_table(): string {
	global $wpdb;
	return $wpdb->prefix . 'tclas_messages';
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 2 — Core functions
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Send a message from one member to another.
 *
 * @return int|false  The message ID on success, false on failure.
 */
function tclas_send_message( int $from, int $to, string $message ) {
	if ( $from === $to || ! $from || ! $to ) {
		return false;
	}

	$message = sanitize_textarea_field( $message );
	if ( '' === $message ) {
		return false;
	}

	// Check recipient allows contact.
	$allow = get_user_meta( $to, '_tclas_privacy_allow_contact', true );
	if ( '' !== $allow && ! (bool) $allow ) {
		// Fall back to legacy key.
		$allow = get_user_meta( $to, '_tclas_open_to_contact', true );
		if ( '' !== $allow && ! (bool) $allow ) {
			return false;
		}
	}

	global $wpdb;
	$table = tclas_messages_table();

	$inserted = $wpdb->insert( $table, [
		'sender_id'    => $from,
		'recipient_id' => $to,
		'message'      => $message,
		'read_status'  => 0,
		'created_at'   => current_time( 'mysql', true ),
	], [ '%d', '%d', '%s', '%d', '%s' ] );

	if ( ! $inserted ) {
		return false;
	}

	$message_id = (int) $wpdb->insert_id;

	// Send email notification.
	tclas_message_email_notification( $from, $to, $message );

	do_action( 'tclas_message_sent', $from, $to, $message_id );

	return $message_id;
}

/**
 * Get all conversations for a user, sorted by most recent message.
 *
 * @return array of { other_id, other_name, other_username, other_photo, last_message, last_date, unread_count }
 */
function tclas_get_conversations( int $user_id ): array {
	global $wpdb;
	$table = tclas_messages_table();

	// Get the most recent message per conversation partner.
	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT
			CASE WHEN sender_id = %d THEN recipient_id ELSE sender_id END AS other_id,
			MAX(id) AS last_msg_id,
			MAX(created_at) AS last_date,
			SUM( CASE WHEN recipient_id = %d AND read_status = 0 THEN 1 ELSE 0 END ) AS unread_count
		 FROM {$table}
		 WHERE sender_id = %d OR recipient_id = %d
		 GROUP BY other_id
		 ORDER BY last_date DESC",
		$user_id, $user_id, $user_id, $user_id
	) );

	if ( ! $rows ) {
		return [];
	}

	$conversations = [];
	foreach ( $rows as $row ) {
		$other_user = get_userdata( (int) $row->other_id );
		if ( ! $other_user ) { continue; }

		// Get the last message text.
		$last_msg = $wpdb->get_var( $wpdb->prepare(
			"SELECT message FROM {$table} WHERE id = %d",
			(int) $row->last_msg_id
		) );

		$display_override = (string) ( get_user_meta( (int) $row->other_id, '_tclas_display_name_override', true ) ?: '' );
		$display_name     = '' !== $display_override ? $display_override : $other_user->display_name;

		$conversations[] = [
			'other_id'       => (int) $row->other_id,
			'other_name'     => $display_name,
			'other_username' => $other_user->user_nicename,
			'other_photo'    => tclas_get_profile_photo_url( (int) $row->other_id, 'thumbnail' ),
			'last_message'   => mb_substr( wp_strip_all_tags( $last_msg ), 0, 120 ),
			'last_date'      => $row->last_date,
			'unread_count'   => (int) $row->unread_count,
		];
	}

	return $conversations;
}

/**
 * Get all messages between two users, oldest first.
 *
 * @return array of { id, sender_id, message, created_at, is_mine }
 */
function tclas_get_conversation( int $user_id, int $other_id ): array {
	global $wpdb;
	$table = tclas_messages_table();

	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT id, sender_id, message, created_at
		 FROM {$table}
		 WHERE (sender_id = %d AND recipient_id = %d)
		    OR (sender_id = %d AND recipient_id = %d)
		 ORDER BY created_at ASC",
		$user_id, $other_id, $other_id, $user_id
	) );

	$messages = [];
	foreach ( $rows as $row ) {
		$messages[] = [
			'id'         => (int) $row->id,
			'sender_id'  => (int) $row->sender_id,
			'message'    => $row->message,
			'created_at' => $row->created_at,
			'is_mine'    => ( (int) $row->sender_id === $user_id ),
		];
	}

	return $messages;
}

/**
 * Get total unread message count for a user.
 */
function tclas_get_unread_count( int $user_id ): int {
	global $wpdb;
	$table = tclas_messages_table();

	return (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$table}
		 WHERE recipient_id = %d AND read_status = 0",
		$user_id
	) );
}

/**
 * Mark all messages in a conversation as read for the current user.
 */
function tclas_mark_conversation_read( int $user_id, int $other_id ): void {
	global $wpdb;
	$table = tclas_messages_table();

	$wpdb->update(
		$table,
		[ 'read_status' => 1 ],
		[
			'sender_id'    => $other_id,
			'recipient_id' => $user_id,
			'read_status'  => 0,
		],
		[ '%d' ],
		[ '%d', '%d', '%d' ]
	);
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 3 — Email notification
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Send an email notification when a message is received.
 */
function tclas_message_email_notification( int $from_id, int $to_id, string $message_text ): void {
	$recipient = get_userdata( $to_id );
	$sender    = get_userdata( $from_id );
	if ( ! $recipient || ! $sender ) {
		return;
	}

	$sender_override = (string) ( get_user_meta( $from_id, '_tclas_display_name_override', true ) ?: '' );
	$sender_name     = '' !== $sender_override ? $sender_override : $sender->display_name;

	$conversation_url = home_url( '/member-hub/messages/' . rawurlencode( $sender->user_nicename ) . '/' );

	$subject = sprintf(
		/* translators: %s: sender name */
		__( '%s sent you a message on TCLAS', 'tclas' ),
		$sender_name
	);

	$body = sprintf(
		/* translators: 1: recipient first name, 2: sender name, 3: message preview, 4: URL */
		__( "Hi %1\$s,\n\n%2\$s sent you a message:\n\n\"%3\$s\"\n\nReply here: %4\$s\n\n—\nTwin Cities Luxembourg American Society", 'tclas' ),
		$recipient->first_name ?: $recipient->display_name,
		$sender_name,
		mb_substr( wp_strip_all_tags( $message_text ), 0, 200 ),
		$conversation_url
	);

	wp_mail( $recipient->user_email, $subject, $body );
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 4 — Rewrite rules
// ═══════════════════════════════════════════════════════════════════════════

add_action( 'init', 'tclas_messages_rewrite_rules' );
function tclas_messages_rewrite_rules(): void {
	add_rewrite_rule(
		'^member-hub/messages/([^/]+)/?$',
		'index.php?pagename=member-hub/messages&tclas_message_username=$matches[1]',
		'top'
	);
}

add_filter( 'query_vars', 'tclas_messages_query_vars' );
function tclas_messages_query_vars( array $vars ): array {
	$vars[] = 'tclas_message_username';
	return $vars;
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 5 — AJAX endpoints
// ═══════════════════════════════════════════════════════════════════════════

add_action( 'wp_ajax_tclas_send_message', 'tclas_ajax_send_message' );
function tclas_ajax_send_message(): void {
	check_ajax_referer( 'tclas_nonce', 'nonce' );

	$from = get_current_user_id();
	if ( ! $from || ! tclas_is_member() ) {
		wp_send_json_error( [ 'message' => __( 'Members only.', 'tclas' ) ] );
	}

	$to      = (int) ( $_POST['recipient_id'] ?? 0 );
	$message = sanitize_textarea_field( $_POST['message'] ?? '' );

	if ( ! $to || '' === $message ) {
		wp_send_json_error( [ 'message' => __( 'Recipient and message are required.', 'tclas' ) ] );
	}

	$msg_id = tclas_send_message( $from, $to, $message );
	if ( ! $msg_id ) {
		wp_send_json_error( [ 'message' => __( 'Could not send message. The recipient may not accept messages.', 'tclas' ) ] );
	}

	wp_send_json_success( [
		'message_id' => $msg_id,
		'html'       => tclas_render_message_bubble( $from, $message, current_time( 'mysql', true ) ),
	] );
}

add_action( 'wp_ajax_tclas_mark_messages_read', 'tclas_ajax_mark_messages_read' );
function tclas_ajax_mark_messages_read(): void {
	check_ajax_referer( 'tclas_nonce', 'nonce' );

	$user_id  = get_current_user_id();
	$other_id = (int) ( $_POST['other_id'] ?? 0 );

	if ( $user_id && $other_id ) {
		tclas_mark_conversation_read( $user_id, $other_id );
	}

	wp_send_json_success();
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 6 — Template helpers
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Render a single message bubble HTML string.
 */
function tclas_render_message_bubble( int $sender_id, string $message, string $date ): string {
	$is_mine = ( $sender_id === get_current_user_id() );
	$class   = $is_mine ? 'tclas-msg--mine' : 'tclas-msg--theirs';
	$time    = wp_date( 'M j, g:i a', strtotime( $date ) );

	return sprintf(
		'<div class="tclas-msg %s"><div class="tclas-msg__bubble">%s</div><span class="tclas-msg__time">%s</span></div>',
		esc_attr( $class ),
		nl2br( esc_html( $message ) ),
		esc_html( $time )
	);
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 7 — Asset enqueue for messages page
// ═══════════════════════════════════════════════════════════════════════════

add_action( 'wp_enqueue_scripts', 'tclas_enqueue_messaging_assets' );
function tclas_enqueue_messaging_assets(): void {
	if ( ! is_page_template( 'page-templates/page-messages.php' ) ) {
		return;
	}

	wp_enqueue_style(
		'tclas-messaging',
		TCLAS_ASSETS . '/css/messaging.css',
		[ 'tclas-main' ],
		file_exists( get_template_directory() . '/assets/css/messaging.css' )
			? filemtime( get_template_directory() . '/assets/css/messaging.css' )
			: TCLAS_VERSION
	);
}
