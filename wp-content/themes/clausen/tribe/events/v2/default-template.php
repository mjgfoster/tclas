<?php
/**
 * TEC v2 — Default template override (TCLAS / Clausen theme)
 *
 * Adds the TCLAS ardoise page header above the TEC events view.
 * Original: the-events-calendar/src/views/v2/default-template.php
 * Override:  [theme]/tribe/events/v2/default-template.php
 *
 * @link https://evnt.is/1aiy
 * @version 5.0.0 (TCLAS override)
 * @package TCLAS
 */

use Tribe\Events\Views\V2\Template_Bootstrap;

get_header();

$featured = function_exists( 'tclas_get_featured_event' ) ? tclas_get_featured_event() : null;

// ── Single event page ──────────────────────────────────────────────────────
if ( is_singular( 'tribe_events' ) ) :

	$eid          = get_queried_object_id();
	$members_only = (bool) get_post_meta( $eid, '_tclas_members_only', true );
	$reg_url      = get_post_meta( $eid, '_tclas_registration_url', true );
	$events_url   = class_exists( 'Tribe__Events__Main' )
		? get_post_type_archive_link( Tribe__Events__Main::POSTTYPE )
		: home_url( '/events/' );

	// Date & time
	$start_ts   = (int) tribe_get_start_date( $eid, false, 'U' );
	$date_str   = date_i18n( 'l, F j, Y', $start_ts );
	$start_time = tribe_get_start_time( $eid ); // AP style via tribe_get_start_time filter
	$end_time   = tribe_get_end_time( $eid );
	if ( function_exists( 'tclas_tec_ap_style_time' ) ) {
		$end_time = tclas_tec_ap_style_time( $end_time );
	}
	$time_str = $start_time . ( $end_time ? ' – ' . $end_time : '' );

	// Venue info
	$venue_name    = tribe_get_venue( $eid );
	$venue_addr    = tribe_get_address( $eid );
	$venue_city    = tribe_get_city( $eid );
	$venue_state   = tribe_get_stateprovince( $eid );
	$venue_country = tribe_get_country( $eid );

	// Omit state if Minnesota; omit country if USA
	$show_state   = $venue_state
		&& ! in_array( strtolower( trim( $venue_state ) ), [ 'mn', 'minnesota' ], true );
	$show_country = $venue_country
		&& ! in_array( strtolower( trim( $venue_country ) ), [ 'us', 'usa', 'united states', 'united states of america' ], true );

	// Featured image + caption
	$thumb_id = get_post_thumbnail_id( $eid );
	$caption  = $thumb_id ? wp_get_attachment_caption( $thumb_id ) : '';

	// Description
	$description = apply_filters( 'the_content', get_post_field( 'post_content', $eid ) );

	// Add to Calendar URLs
	$end_date_meta = get_post_meta( $eid, '_EventEndDate', true ); // stored as 'Y-m-d H:i:s' local time
	$end_ts        = $end_date_meta ? (int) strtotime( $end_date_meta ) : $start_ts + 7200;
	$cal_title     = rawurlencode( get_the_title( $eid ) );
	$cal_location  = rawurlencode( trim( implode( ', ', array_filter( [ $venue_name, $venue_addr, $venue_city ] ) ) ) );
	$cal_details   = rawurlencode( wp_strip_all_tags( get_the_excerpt( $eid ) ) );
	$gc_dates      = date( 'Ymd', $start_ts ) . 'T' . date( 'His', $start_ts )
	               . '/' . date( 'Ymd', $end_ts ) . 'T' . date( 'His', $end_ts );
	$url_gcal      = 'https://calendar.google.com/calendar/r/eventedit?text=' . $cal_title
	               . '&dates=' . $gc_dates
	               . '&details=' . $cal_details
	               . '&location=' . $cal_location;
	$url_ical      = add_query_arg( 'ical', '1', get_permalink( $eid ) );
	$url_outlook   = 'https://outlook.live.com/calendar/0/deeplink/compose?subject=' . $cal_title
	               . '&startdt=' . rawurlencode( date( 'Y-m-d\TH:i:s', $start_ts ) )
	               . '&enddt=' . rawurlencode( date( 'Y-m-d\TH:i:s', $end_ts ) )
	               . '&body=' . $cal_details
	               . '&location=' . $cal_location;

	// Icons
	$icon_cal   = '<svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>';
	$icon_clock = '<svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
	$icon_pin   = '<svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>';
	$icon_lock  = '<svg aria-hidden="true" focusable="false" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>';
	?>

<div class="tclas-page-header">
	<div class="container-tclas">
		<nav class="tclas-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'tclas' ); ?>">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'tclas' ); ?></a>
			<span class="tclas-breadcrumb__sep" aria-hidden="true">›</span>
			<a href="<?php echo esc_url( $events_url ); ?>"><?php esc_html_e( 'Events', 'tclas' ); ?></a>
			<span class="tclas-breadcrumb__sep" aria-hidden="true">›</span>
			<span class="tclas-breadcrumb__current" aria-current="page"><?php echo esc_html( get_the_title( $eid ) ); ?></span>
		</nav>
		<div class="tclas-event-header__row">
			<h1 class="tclas-page-header__title"><?php echo esc_html( get_the_title( $eid ) ); ?></h1>
		</div>
	</div>
</div>

<div class="tclas-event-body">
	<div class="container-tclas">
		<div class="tclas-event-layout">

			<aside class="tclas-event-sidebar">
				<?php if ( $reg_url ) : ?>
					<a
						href="<?php echo esc_url( $reg_url ); ?>"
						class="tclas-event-register-link"
						target="_blank"
						rel="noopener noreferrer"
					>
						<?php esc_html_e( 'Register now', 'tclas' ); ?> &#8594;
					</a>
				<?php endif; ?>

				<ul class="tclas-event-meta">
					<li>
						<?php echo $icon_cal; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php echo esc_html( $date_str ); ?></span>
					</li>
					<?php if ( $time_str ) : ?>
					<li>
						<?php echo $icon_clock; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php echo esc_html( $time_str ); ?></span>
					</li>
					<?php endif; ?>
					<?php if ( $venue_name ) : ?>
					<li class="tclas-event-meta__venue">
						<?php echo $icon_pin; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<address>
							<strong><?php echo esc_html( $venue_name ); ?></strong>
							<?php if ( $venue_addr ) : ?>
								<span><?php echo esc_html( $venue_addr ); ?></span>
							<?php endif; ?>
							<?php
							$city_line = $venue_city;
							if ( $show_state ) {
								$city_line .= ', ' . $venue_state;
							}
							if ( $show_country ) {
								$city_line .= $city_line ? ', ' . $venue_country : $venue_country;
							}
							if ( $city_line ) : ?>
								<span><?php echo esc_html( $city_line ); ?></span>
							<?php endif; ?>
						</address>
					</li>
					<?php endif; ?>
				</ul>

			<div class="tclas-event-atc">
				<p class="tclas-event-atc__label"><?php esc_html_e( 'Add to calendar', 'tclas' ); ?></p>
				<div class="tclas-event-atc__links">
					<a href="<?php echo esc_url( $url_gcal ); ?>" target="_blank" rel="noopener noreferrer" class="tclas-event-atc__link">
						<?php esc_html_e( 'Google Calendar', 'tclas' ); ?>
					</a>
					<a href="<?php echo esc_url( $url_ical ); ?>" class="tclas-event-atc__link">
						<?php esc_html_e( 'iCal / Apple Calendar', 'tclas' ); ?>
					</a>
					<a href="<?php echo esc_url( $url_outlook ); ?>" target="_blank" rel="noopener noreferrer" class="tclas-event-atc__link">
						<?php esc_html_e( 'Outlook', 'tclas' ); ?>
					</a>
				</div>
			</div>
			</aside>

			<div class="tclas-event-main">
				<?php if ( $thumb_id ) : ?>
					<figure class="tclas-event-image">
						<?php echo get_the_post_thumbnail( $eid, 'large', [ 'class' => 'tclas-event-image__img', 'loading' => 'eager' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php if ( $caption ) : ?>
							<figcaption class="tclas-event-image__caption"><?php echo esc_html( $caption ); ?></figcaption>
						<?php endif; ?>
					</figure>
				<?php endif; ?>

				<?php if ( $members_only ) : ?>
					<div class="tclas-event-members-notice">
						<?php echo $icon_lock; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php esc_html_e( 'Members only', 'tclas' ); ?></span>
					</div>
				<?php endif; ?>

				<div class="tclas-event-description">
					<?php echo $description; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>

		</div>
	</div>
</div>

<?php
// ── Events archive page ────────────────────────────────────────────────────
else :
?>

<div class="tclas-page-header">
	<div class="container-tclas">
		<span class="tclas-eyebrow"><?php esc_html_e( 'Community', 'tclas' ); ?></span>
		<h1 class="tclas-page-header__title"><?php esc_html_e( 'Events', 'tclas' ); ?></h1>
	</div>
</div>

<?php if ( $featured ) :
	$f_id       = $featured->ID;
	$f_title    = get_the_title( $f_id );
	$f_start    = tribe_get_start_date( $f_id, false, 'U' );
	$f_date     = date_i18n( 'l, F j, Y', $f_start );
	$f_time     = tribe_get_start_time( $f_id );
	$f_end_time = tribe_get_end_time( $f_id );
	$f_time_str = $f_time . ( $f_end_time ? '–' . $f_end_time : '' );
	$f_venue    = tribe_get_venue( $f_id );
	$f_excerpt  = get_the_excerpt( $f_id );
	$f_img      = get_the_post_thumbnail_url( $f_id, 'large' );
	$f_reg_url  = get_post_meta( $f_id, '_tclas_registration_url', true );
	$f_link     = $f_reg_url ?: get_permalink( $f_id );
	$f_external = (bool) $f_reg_url;

	$icon_cal   = '<svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>';
	$icon_clock = '<svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
	$icon_pin   = '<svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>';
?>
<section class="tclas-featured-event" aria-label="<?php esc_attr_e( 'Featured event', 'tclas' ); ?>">
	<div class="container-tclas">
		<div class="tclas-featured-event__card">

			<div class="tclas-featured-event__image">
				<?php if ( $f_img ) : ?>
					<img src="<?php echo esc_url( $f_img ); ?>" alt="<?php echo esc_attr( $f_title ); ?>">
				<?php else : ?>
					<div class="tclas-featured-event__image-placeholder" aria-hidden="true"></div>
				<?php endif; ?>
				<span class="tclas-featured-event__badge"><?php esc_html_e( 'Featured event', 'tclas' ); ?></span>
			</div>

			<div class="tclas-featured-event__body">
				<h2 class="tclas-featured-event__title">
					<a href="<?php echo esc_url( get_permalink( $f_id ) ); ?>"><?php echo esc_html( $f_title ); ?></a>
				</h2>

				<?php if ( $f_excerpt ) : ?>
					<p class="tclas-featured-event__excerpt"><?php echo esc_html( wp_trim_words( $f_excerpt, 30 ) ); ?></p>
				<?php endif; ?>

				<div class="tclas-featured-event__meta">
					<div class="tclas-featured-event__meta-row">
						<?php echo $icon_cal; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php echo esc_html( $f_date ); ?></span>
					</div>
					<?php if ( $f_time_str ) : ?>
					<div class="tclas-featured-event__meta-row">
						<?php echo $icon_clock; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php echo esc_html( $f_time_str ); ?></span>
					</div>
					<?php endif; ?>
					<?php if ( $f_venue ) : ?>
					<div class="tclas-featured-event__meta-row">
						<?php echo $icon_pin; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php echo esc_html( $f_venue ); ?></span>
					</div>
					<?php endif; ?>
				</div>

				<a
					href="<?php echo esc_url( $f_link ); ?>"
					class="btn btn-primary"
					<?php if ( $f_external ) : ?>target="_blank" rel="noopener noreferrer"<?php endif; ?>
				>
					<?php echo $f_external ? esc_html__( 'Register now', 'tclas' ) : esc_html__( 'View event details', 'tclas' ); ?>
				</a>
			</div>

		</div>
	</div>
</section>
<?php endif; ?>

<?php echo tribe( Template_Bootstrap::class )->get_view_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

<?php endif; ?>

<?php
get_footer();
