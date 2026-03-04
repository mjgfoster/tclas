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
	$start    = tribe_get_start_date( $event->ID, false, 'U' );
	$date_str = date_i18n( 'l, F j', $start );
	$time_str = tribe_get_start_time( $event->ID );
	$venue    = tribe_get_venue( $event->ID );
	$permalink = get_permalink( $event->ID );
	$title    = get_the_title( $event->ID );
	$members  = get_post_meta( $event->ID, '_tclas_members_only', true );
	$excerpt  = get_the_excerpt( $event->ID );

	// Inline SVG icons — avoids external icon library dependency
	$icon_cal   = '<svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>';
	$icon_clock = '<svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
	$icon_pin   = '<svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>';
	$icon_lock  = '<svg aria-hidden="true" focusable="false" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>';
	?>
	<a href="<?php echo esc_url( $permalink ); ?>" class="tclas-event-card" aria-label="<?php echo esc_attr( $title ); ?>">
		<article>
			<div class="tclas-event-card__image">
				<?php if ( has_post_thumbnail( $event->ID ) ) : ?>
					<?php echo get_the_post_thumbnail( $event->ID, 'medium_large' ); ?>
				<?php else : ?>
					<div class="tclas-event-card__image-placeholder" aria-hidden="true">
						<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
					</div>
				<?php endif; ?>
			</div>

			<div class="tclas-event-card__content">
				<time class="sr-only" datetime="<?php echo esc_attr( date( 'Y-m-d', $start ) ); ?>">
					<?php echo esc_html( $date_str ); ?>
				</time>

				<h3 class="tclas-event-card__title"><?php echo esc_html( $title ); ?></h3>

				<?php if ( $excerpt ) : ?>
					<p class="tclas-event-card__excerpt"><?php echo esc_html( wp_trim_words( $excerpt, 20 ) ); ?></p>
				<?php endif; ?>

				<div class="tclas-event-card__meta">
					<div class="tclas-event-card__meta-row">
						<span class="tclas-event-card__meta-icon"><?php echo $icon_cal; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<span><?php echo esc_html( $date_str ); ?></span>
					</div>
					<?php if ( $time_str ) : ?>
						<div class="tclas-event-card__meta-row">
							<span class="tclas-event-card__meta-icon"><?php echo $icon_clock; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
							<span><?php echo esc_html( $time_str ); ?></span>
						</div>
					<?php endif; ?>
					<?php if ( $venue ) : ?>
						<div class="tclas-event-card__meta-row">
							<span class="tclas-event-card__meta-icon"><?php echo $icon_pin; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
							<span><?php echo esc_html( $venue ); ?></span>
						</div>
					<?php endif; ?>
				</div>

				<?php if ( $members ) : ?>
					<div class="tclas-event-card__members-badge">
						<?php echo $icon_lock; // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<span><?php esc_html_e( 'Members only', 'tclas' ); ?></span>
					</div>
				<?php endif; ?>
			</div>
		</article>
	</a>
	<?php
}

// ── Event settings meta box ────────────────────────────────────────────────

/**
 * Register the "TCLAS Event Settings" meta box on TEC event edit screens.
 * Covers: featured event flag, members-only flag, external registration URL.
 */
function tclas_events_meta_box_register(): void {
	if ( ! class_exists( 'Tribe__Events__Main' ) ) {
		return;
	}
	add_meta_box(
		'tclas_event_settings',
		__( 'TCLAS Event Settings', 'tclas' ),
		'tclas_events_meta_box_render',
		Tribe__Events__Main::POSTTYPE,
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'tclas_events_meta_box_register' );

/**
 * Render the TCLAS Event Settings meta box.
 *
 * @param WP_Post $post  The event post.
 */
function tclas_events_meta_box_render( WP_Post $post ): void {
	wp_nonce_field( 'tclas_event_settings', 'tclas_event_settings_nonce' );
	$featured     = (bool) get_post_meta( $post->ID, '_tclas_featured_event', true );
	$members_only = (bool) get_post_meta( $post->ID, '_tclas_members_only', true );
	$reg_url      = esc_url( get_post_meta( $post->ID, '_tclas_registration_url', true ) );
	?>
	<p style="margin:0 0 10px;font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#50575e;">
		<?php esc_html_e( 'Display', 'tclas' ); ?>
	</p>

	<label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-bottom:6px;">
		<input type="checkbox" name="tclas_featured_event" value="1" <?php checked( $featured ); ?>>
		<?php esc_html_e( 'Featured event', 'tclas' ); ?>
	</label>
	<p class="description" style="margin:0 0 12px 24px;">
		<?php esc_html_e( 'Highlights this event in the hero at the top of the Events page. One at a time.', 'tclas' ); ?>
	</p>

	<label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-bottom:6px;">
		<input type="checkbox" name="tclas_members_only" value="1" <?php checked( $members_only ); ?>>
		<?php esc_html_e( 'Members only', 'tclas' ); ?>
	</label>
	<p class="description" style="margin:0 0 16px 24px;">
		<?php esc_html_e( 'Shows a "Members only" badge on the event card.', 'tclas' ); ?>
	</p>

	<p style="margin:0 0 6px;font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#50575e;">
		<?php esc_html_e( 'Registration', 'tclas' ); ?>
	</p>
	<input
		type="url"
		name="tclas_registration_url"
		value="<?php echo esc_attr( $reg_url ); ?>"
		placeholder="https://"
		style="width:100%;box-sizing:border-box;"
	>
	<p class="description" style="margin-top:5px;">
		<?php esc_html_e( 'External registration link (Eventbrite, Google Forms, etc.). Leave blank to link to this event page.', 'tclas' ); ?>
	</p>
	<?php
}

/**
 * Save TCLAS event settings when the event is saved.
 *
 * @param int $post_id  The event post ID.
 */
function tclas_events_meta_box_save( int $post_id ): void {
	if (
		! isset( $_POST['tclas_event_settings_nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tclas_event_settings_nonce'] ) ), 'tclas_event_settings' )
	) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Featured event
	if ( ! empty( $_POST['tclas_featured_event'] ) ) {
		update_post_meta( $post_id, '_tclas_featured_event', '1' );
	} else {
		delete_post_meta( $post_id, '_tclas_featured_event' );
	}

	// Members only
	if ( ! empty( $_POST['tclas_members_only'] ) ) {
		update_post_meta( $post_id, '_tclas_members_only', '1' );
	} else {
		delete_post_meta( $post_id, '_tclas_members_only' );
	}

	// Registration URL
	$reg_url = isset( $_POST['tclas_registration_url'] )
		? esc_url_raw( wp_unslash( $_POST['tclas_registration_url'] ) )
		: '';
	if ( $reg_url ) {
		update_post_meta( $post_id, '_tclas_registration_url', $reg_url );
	} else {
		delete_post_meta( $post_id, '_tclas_registration_url' );
	}
}
add_action( 'save_post', 'tclas_events_meta_box_save' );

// ── Featured event helper ──────────────────────────────────────────────────

/**
 * Return the current featured upcoming event, or null if none is set.
 */
function tclas_get_featured_event(): ?WP_Post {
	if ( ! class_exists( 'Tribe__Events__Main' ) ) {
		return null;
	}

	$events = tribe_get_events( [
		'posts_per_page' => 1,
		'start_date'     => date( 'Y-m-d' ),
		'orderby'        => 'event_date',
		'order'          => 'ASC',
		'meta_key'       => '_tclas_featured_event',
		'meta_value'     => '1',
	] );

	return ( is_array( $events ) && ! empty( $events ) ) ? $events[0] : null;
}

// ── Members-only badge in TEC list view ───────────────────────────────────

/**
 * Inject a "Members only" badge after the event title in TEC's list view.
 */
add_action( 'tribe_template_after_include', function ( $file, $name, $template ): void {
	// TEC may pass $name as an array of path segments or as a string.
	$name_str = is_array( $name ) ? implode( '/', $name ) : (string) $name;
	if ( 'v2/list/event/title' !== $name_str ) {
		return;
	}

	$event = $template->get( 'event' );
	if ( ! $event instanceof WP_Post ) {
		return;
	}

	if ( ! get_post_meta( $event->ID, '_tclas_members_only', true ) ) {
		return;
	}

	$icon = '<svg aria-hidden="true" focusable="false" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>';
	echo '<div class="tclas-tec-members-badge">' . $icon . '<span>' . esc_html__( 'Members only', 'tclas' ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}, 10, 3 );

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
add_filter( 'tribe_get_start_time',               'tclas_tec_ap_style_time', 20 );
add_filter( 'tribe_get_end_time',                 'tclas_tec_ap_style_time', 20 );

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
