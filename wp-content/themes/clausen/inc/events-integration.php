<?php
/**
 * The Events Calendar integration
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Return upcoming events as an array of simplified objects.
 *
 * @param int $limit  Number of events to return.
 */
function tclas_get_upcoming_events( int $limit = 3 ): array {
	if ( ! class_exists( 'Tribe__Events__Main' ) ) {
		return [];
	}

	$events = tribe_get_events( [
		'posts_per_page' => $limit,
		'start_date'     => date( 'Y-m-d' ),
		'orderby'        => 'event_date',
		'order'          => 'ASC',
	] );

	return is_array( $events ) ? $events : [];
}

/**
 * Render a single event card.
 *
 * @param WP_Post $event  TEC event post object.
 */
function tclas_render_event_card( WP_Post $event ): void {
	$start     = tribe_get_start_date( $event->ID, false, 'U' );
	$day       = date_i18n( 'j',    $start );
	$month     = date_i18n( 'M',   $start );
	$year      = date_i18n( 'Y',    $start );
	$time      = tribe_get_start_time( $event->ID );
	$venue     = tribe_get_venue( $event->ID );
	$permalink = get_permalink( $event->ID );
	$title     = get_the_title( $event->ID );
	$members   = get_post_meta( $event->ID, '_tclas_members_only', true );
	?>
	<article class="tclas-event-card">
		<div class="tclas-event-card__date" aria-hidden="true">
			<span class="day"><?php echo esc_html( $day ); ?></span>
			<span class="month"><?php echo esc_html( $month ); ?></span>
			<span class="year"><?php echo esc_html( $year ); ?></span>
		</div>
		<div class="tclas-event-card__body">
			<time class="sr-only" datetime="<?php echo esc_attr( date( 'Y-m-d', $start ) ); ?>">
				<?php echo esc_html( date_i18n( get_option( 'date_format' ), $start ) ); ?>
			</time>
			<h3 class="tclas-event-card__title">
				<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
			</h3>
			<div class="tclas-event-card__meta">
				<?php if ( $time ) : ?><span><?php echo esc_html( $time ); ?></span><?php endif; ?>
				<?php if ( $venue ) : ?><span><?php echo esc_html( $venue ); ?></span><?php endif; ?>
			</div>
			<?php if ( $members ) : ?>
				<span class="tclas-event-card__members"><?php esc_html_e( 'Members only', 'tclas' ); ?></span>
			<?php endif; ?>
		</div>
	</article>
	<?php
}

// ── Members-only meta box ──────────────────────────────────────────────────

/**
 * Register a "Members only" meta box on TEC event edit screens.
 */
function tclas_events_meta_box_register(): void {
	if ( ! class_exists( 'Tribe__Events__Main' ) ) {
		return;
	}
	add_meta_box(
		'tclas_event_members_only',
		__( 'TCLAS Access', 'tclas' ),
		'tclas_events_meta_box_render',
		Tribe__Events__Main::POSTTYPE,
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'tclas_events_meta_box_register' );

/**
 * Render the "Members only" meta box.
 *
 * @param WP_Post $post  The event post.
 */
function tclas_events_meta_box_render( WP_Post $post ): void {
	wp_nonce_field( 'tclas_event_members_only', 'tclas_event_members_only_nonce' );
	$checked = (bool) get_post_meta( $post->ID, '_tclas_members_only', true );
	?>
	<label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
		<input
			type="checkbox"
			name="tclas_members_only"
			value="1"
			<?php checked( $checked ); ?>
		>
		<?php esc_html_e( 'Members only event', 'tclas' ); ?>
	</label>
	<p class="description" style="margin-top:6px;">
		<?php esc_html_e( 'When checked, a "Members only" badge is shown on the event card.', 'tclas' ); ?>
	</p>
	<?php
}

/**
 * Save the "Members only" meta when the event is saved.
 *
 * @param int $post_id  The event post ID.
 */
function tclas_events_meta_box_save( int $post_id ): void {
	if (
		! isset( $_POST['tclas_event_members_only_nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tclas_event_members_only_nonce'] ) ), 'tclas_event_members_only' )
	) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( ! empty( $_POST['tclas_members_only'] ) ) {
		update_post_meta( $post_id, '_tclas_members_only', '1' );
	} else {
		delete_post_meta( $post_id, '_tclas_members_only' );
	}
}
add_action( 'save_post', 'tclas_events_meta_box_save' );

/**
 * Render the events empty state.
 */
// ── AP-style date/time formatting ─────────────────────────────────────────────

/**
 * Adjust TEC date/time display to AP style.
 * Changes "@ 6:30 pm" to "at 6:30 p.m."
 */
function tclas_tec_ap_style_time( string $text ): string {
	$text = str_replace( '@ ', 'at ', $text );
	$text = preg_replace( '/\bam\b/i', 'a.m.', $text );
	$text = preg_replace( '/\bpm\b/i', 'p.m.', $text );
	return $text;
}
add_filter( 'tribe_events_event_schedule_details', 'tclas_tec_ap_style_time', 20 );
add_filter( 'tribe_get_start_time', 'tclas_tec_ap_style_time', 20 );

/**
 * Render the events empty state.
 */
function tclas_render_events_empty(): void {
	?>
	<div class="tclas-events-empty">
		<span class="tclas-events-empty__ltz">
			<?php echo tclas_ltz( 'Déi nächst Veranstaltung kënnt geschwënn.', 'The next event is coming soon.', false ); ?>
		</span>
		<span class="tclas-events-empty__translation">
			<?php esc_html_e( 'The next event is coming soon.', 'tclas' ); ?>
		</span>
	</div>
	<?php
}
