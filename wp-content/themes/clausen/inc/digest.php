<?php
/**
 * Weekly digest email
 *
 * Sends a weekly summary of unread activity to opted-in members:
 * forum @mentions, unread messages, new connections, new matching members.
 *
 * Schedule and day/time are configurable via Theme Options → Member Hub.
 * Members opt out via Privacy Settings → "Send me a weekly digest".
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 1 — Cron scheduling
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Register the weekly digest cron event if not already scheduled.
 */
function tclas_schedule_digest_cron(): void {
	// Check if admin has disabled digest system-wide.
	if ( function_exists( 'get_field' ) ) {
		$enabled = get_field( 'tclas_digest_enabled', 'option' );
		if ( false === $enabled || '0' === $enabled || 0 === $enabled ) {
			// Unschedule if it was previously scheduled.
			$timestamp = wp_next_scheduled( 'tclas_weekly_digest_cron' );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, 'tclas_weekly_digest_cron' );
			}
			return;
		}
	}

	// Register custom 'weekly' interval if not already registered.
	add_filter( 'cron_schedules', function ( array $schedules ): array {
		if ( ! isset( $schedules['weekly'] ) ) {
			$schedules['weekly'] = [
				'interval' => WEEK_IN_SECONDS,
				'display'  => __( 'Once Weekly', 'tclas' ),
			];
		}
		return $schedules;
	} );

	if ( ! wp_next_scheduled( 'tclas_weekly_digest_cron' ) ) {
		$next = tclas_digest_next_send_time();
		wp_schedule_event( $next, 'weekly', 'tclas_weekly_digest_cron' );
	}
}
add_action( 'init', 'tclas_schedule_digest_cron' );

/**
 * Calculate the next send timestamp based on admin settings.
 * Defaults to Monday 10:00 AM Central.
 */
function tclas_digest_next_send_time(): int {
	$day  = 'monday';
	$time = '10:00';

	if ( function_exists( 'get_field' ) ) {
		$day  = get_field( 'tclas_digest_day',  'option' ) ?: 'monday';
		$time = get_field( 'tclas_digest_time', 'option' ) ?: '10:00';
	}

	// Build a timestamp for next occurrence of the configured day/time.
	$tz       = wp_timezone();
	$now      = new DateTimeImmutable( 'now', $tz );
	$target   = new DateTimeImmutable( "next {$day} {$time}", $tz );

	// If the target is in the past (same day but time passed), push to next week.
	if ( $target <= $now ) {
		$target = $target->modify( '+1 week' );
	}

	return $target->getTimestamp();
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 2 — Cron handler (batch processing)
// ═══════════════════════════════════════════════════════════════════════════

add_action( 'tclas_weekly_digest_cron', 'tclas_run_weekly_digest' );

/**
 * Process digest emails in batches of 50.
 */
function tclas_run_weekly_digest(): void {
	global $wpdb;

	$batch_size = 50;
	$offset     = (int) get_option( 'tclas_digest_batch_offset', 0 );

	// Get active member IDs.
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
		// All done — reset offset for next week.
		update_option( 'tclas_digest_batch_offset', 0 );
		return;
	}

	foreach ( $ids as $uid ) {
		$uid = (int) $uid;

		// Check user opt-in.
		$opted_in = get_user_meta( $uid, '_tclas_privacy_weekly_digest', true );
		if ( '' !== $opted_in && ! (bool) $opted_in ) {
			continue; // user opted out
		}

		$digest = tclas_build_digest( $uid );
		if ( ! $digest ) {
			continue; // nothing to report
		}

		tclas_send_digest_email( $uid, $digest );
	}

	// Schedule next batch if there might be more users.
	if ( count( $ids ) >= $batch_size ) {
		update_option( 'tclas_digest_batch_offset', $offset + $batch_size );
		// Re-trigger immediately for next batch.
		wp_schedule_single_event( time() + 10, 'tclas_weekly_digest_cron' );
	} else {
		update_option( 'tclas_digest_batch_offset', 0 );
	}
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 3 — Digest content builder
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Build digest content for a user.
 *
 * @return array|false  Array of sections, or false if nothing to report.
 */
function tclas_build_digest( int $user_id ) {
	$sections = [];

	// ── Unread messages ─────────────────────────────────────────────────
	if ( function_exists( 'tclas_get_unread_count' ) ) {
		$unread = tclas_get_unread_count( $user_id );
		if ( $unread > 0 ) {
			// Get sender names.
			$conversations = function_exists( 'tclas_get_conversations' )
				? tclas_get_conversations( $user_id )
				: [];
			$senders = [];
			foreach ( $conversations as $conv ) {
				if ( $conv['unread_count'] > 0 ) {
					$senders[] = $conv['other_name'];
				}
			}

			$sections['messages'] = [
				'title' => sprintf(
					_n( 'You have %d unread message', 'You have %d unread messages', $unread, 'tclas' ),
					$unread
				),
				'items' => $senders,
				'link'  => home_url( '/member-hub/messages/' ),
				'cta'   => __( 'Read Messages', 'tclas' ),
			];
		}
	}

	// ── Forum @mentions ─────────────────────────────────────────────────
	if ( function_exists( 'bbp_get_reply_post_type' ) ) {
		$user     = get_userdata( $user_id );
		$username = $user ? $user->user_login : '';
		if ( $username ) {
			global $wpdb;
			$cutoff  = gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) );
			$mentions = $wpdb->get_results( $wpdb->prepare(
				"SELECT p.ID, p.post_parent, p.post_author
				 FROM {$wpdb->posts} p
				 WHERE p.post_type = %s
				   AND p.post_status = 'publish'
				   AND p.post_date >= %s
				   AND p.post_author != %d
				   AND p.post_content LIKE %s
				 ORDER BY p.post_date DESC
				 LIMIT 5",
				bbp_get_reply_post_type(),
				$cutoff,
				$user_id,
				'%@' . $wpdb->esc_like( $username ) . '%'
			) );

			if ( ! empty( $mentions ) ) {
				$items = [];
				foreach ( $mentions as $m ) {
					$author = get_userdata( (int) $m->post_author );
					$topic  = get_post( (int) $m->post_parent );
					if ( $author && $topic ) {
						$items[] = sprintf(
							/* translators: 1: author name, 2: topic title */
							__( '%1$s mentioned you in "%2$s"', 'tclas' ),
							$author->display_name,
							get_the_title( $topic )
						);
					}
				}
				if ( ! empty( $items ) ) {
					$sections['forum'] = [
						'title' => sprintf(
							_n( '%d forum mention this week', '%d forum mentions this week', count( $mentions ), 'tclas' ),
							count( $mentions )
						),
						'items' => $items,
						'link'  => home_url( '/member-hub/forums/luxembourg-connections/' ),
						'cta'   => __( 'View Forum', 'tclas' ),
					];
				}
			}
		}
	}

	// ── New connections ──────────────────────────────────────────────────
	$connections_cache = (array) ( get_user_meta( $user_id, '_tclas_connections_cache', true ) ?: [] );
	$new_connections   = array_filter( $connections_cache, fn( $c ) => ! ( $c['seen'] ?? false ) && ! ( $c['dismissed'] ?? false ) );
	if ( ! empty( $new_connections ) ) {
		$items = [];
		foreach ( array_slice( $new_connections, 0, 3, true ) as $other_id => $conn ) {
			$other = get_userdata( (int) $other_id );
			if ( $other ) {
				$items[] = $other->display_name;
			}
		}
		$sections['connections'] = [
			'title' => sprintf(
				_n( '%d new connection found', '%d new connections found', count( $new_connections ), 'tclas' ),
				count( $new_connections )
			),
			'items' => $items,
			'link'  => home_url( '/member-hub/' ),
			'cta'   => __( 'View Connections', 'tclas' ),
		];
	}

	if ( empty( $sections ) ) {
		return false;
	}

	return $sections;
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 4 — Email sender
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Send the digest email to a user.
 */
function tclas_send_digest_email( int $user_id, array $sections ): void {
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return;
	}

	$first_name  = $user->first_name ?: $user->display_name;
	$total_items = 0;
	foreach ( $sections as $s ) {
		$total_items += count( $s['items'] );
	}

	$subject = sprintf(
		/* translators: %d: number of updates */
		_n(
			'You have %d update waiting — TCLAS community',
			'You have %d updates waiting — TCLAS community',
			$total_items,
			'tclas'
		),
		$total_items
	);

	$hub_url     = home_url( '/member-hub/' );
	$privacy_url = home_url( '/member-hub/privacy/' );
	$site_name   = get_bloginfo( 'name' );

	// Build HTML email.
	$html = tclas_digest_email_html( $first_name, $sections, $hub_url, $privacy_url, $site_name );

	// Build plain-text fallback.
	$text = tclas_digest_email_text( $first_name, $sections, $hub_url, $privacy_url );

	// Send as HTML.
	$headers = [
		'Content-Type: text/html; charset=UTF-8',
		'From: ' . $site_name . ' <' . get_option( 'admin_email' ) . '>',
	];

	wp_mail( $user->user_email, $subject, $html, $headers );
}

/**
 * Build the HTML email body.
 */
function tclas_digest_email_html( string $name, array $sections, string $hub_url, string $privacy_url, string $site_name ): string {
	$bg      = '#f5f5f5';
	$card_bg = '#ffffff';
	$text    = '#2c3e50';
	$muted   = '#7f8c8d';
	$accent  = '#e31e26';

	ob_start();
	?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:<?php echo $bg; ?>;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:<?php echo $bg; ?>;padding:24px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:<?php echo $card_bg; ?>;border-radius:4px;overflow:hidden;max-width:100%;">

<!-- Header -->
<tr><td style="background:<?php echo $text; ?>;padding:24px 32px;">
	<h1 style="margin:0;color:#fff;font-size:20px;"><?php echo esc_html( $site_name ); ?></h1>
</td></tr>

<!-- Greeting -->
<tr><td style="padding:28px 32px 8px;">
	<p style="margin:0;color:<?php echo $text; ?>;font-size:16px;">
		<?php printf( esc_html__( 'Hi %s,', 'tclas' ), esc_html( $name ) ); ?>
	</p>
	<p style="margin:8px 0 0;color:<?php echo $muted; ?>;font-size:14px;">
		<?php esc_html_e( 'Here\'s what\'s waiting for you this week:', 'tclas' ); ?>
	</p>
</td></tr>

<!-- Sections -->
<?php foreach ( $sections as $key => $section ) : ?>
<tr><td style="padding:16px 32px;">
	<h2 style="margin:0 0 8px;color:<?php echo $text; ?>;font-size:15px;font-weight:700;border-bottom:1px solid #eee;padding-bottom:8px;">
		<?php echo esc_html( $section['title'] ); ?>
	</h2>
	<?php if ( ! empty( $section['items'] ) ) : ?>
	<ul style="margin:0;padding:0 0 0 20px;color:<?php echo $muted; ?>;font-size:14px;line-height:1.6;">
		<?php foreach ( array_slice( $section['items'], 0, 5 ) as $item ) : ?>
		<li><?php echo esc_html( $item ); ?></li>
		<?php endforeach; ?>
	</ul>
	<?php endif; ?>
	<p style="margin:12px 0 0;">
		<a href="<?php echo esc_url( $section['link'] ); ?>" style="color:<?php echo $accent; ?>;font-size:14px;font-weight:600;text-decoration:none;">
			<?php echo esc_html( $section['cta'] ); ?> →
		</a>
	</p>
</td></tr>
<?php endforeach; ?>

<!-- CTA -->
<tr><td align="center" style="padding:24px 32px;">
	<a href="<?php echo esc_url( $hub_url ); ?>" style="display:inline-block;background:<?php echo $accent; ?>;color:#fff;padding:12px 28px;font-size:14px;font-weight:700;text-decoration:none;border-radius:3px;">
		<?php esc_html_e( 'Visit Member Hub', 'tclas' ); ?>
	</a>
</td></tr>

<!-- Footer -->
<tr><td style="padding:20px 32px;border-top:1px solid #eee;">
	<p style="margin:0;color:<?php echo $muted; ?>;font-size:12px;line-height:1.5;">
		<?php esc_html_e( 'This is an automated weekly summary of your unread activity.', 'tclas' ); ?><br>
		<a href="<?php echo esc_url( $privacy_url ); ?>" style="color:<?php echo $muted; ?>;">
			<?php esc_html_e( 'Manage email preferences', 'tclas' ); ?>
		</a>
	</p>
</td></tr>

</table>
</td></tr>
</table>
</body>
</html>
	<?php
	return ob_get_clean();
}

/**
 * Build the plain-text email body (fallback).
 */
function tclas_digest_email_text( string $name, array $sections, string $hub_url, string $privacy_url ): string {
	$lines = [];
	$lines[] = sprintf( __( 'Hi %s,', 'tclas' ), $name );
	$lines[] = '';
	$lines[] = __( 'Here\'s what\'s waiting for you this week:', 'tclas' );
	$lines[] = '';

	foreach ( $sections as $section ) {
		$lines[] = strtoupper( $section['title'] );
		foreach ( $section['items'] as $item ) {
			$lines[] = '  - ' . $item;
		}
		$lines[] = $section['cta'] . ': ' . $section['link'];
		$lines[] = '';
	}

	$lines[] = '---';
	$lines[] = __( 'Visit Member Hub', 'tclas' ) . ': ' . $hub_url;
	$lines[] = __( 'Manage email preferences', 'tclas' ) . ': ' . $privacy_url;

	return implode( "\n", $lines );
}
