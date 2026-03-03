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
?>

<div class="tclas-page-header tclas-page-header--ardoise">
	<div class="container-tclas">
		<span class="tclas-eyebrow tclas-eyebrow--light"><?php esc_html_e( 'Community', 'tclas' ); ?></span>
		<h1 class="tclas-page-header__title"><?php esc_html_e( 'Events', 'tclas' ); ?></h1>
	</div>
</div>

<?php if ( $featured && ! is_singular( 'tribe_events' ) ) :
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

<?php
get_footer();
