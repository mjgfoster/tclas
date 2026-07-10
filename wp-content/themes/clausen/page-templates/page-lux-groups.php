<?php
/**
 * Template Name: Luxembourg Groups & Events
 *
 * Directory map of Luxembourg organizations beyond TCLAS (`tclas_org` posts)
 * with a conditionally-rendered sidebar of upcoming external events
 * (`tclas_ext_event`). The sidebar renders ONLY when at least one un-expired
 * event exists — no events, no markup — so the page can never look stale.
 *
 * Lives at /msp-lux/groups/ under the MSP + LUX section.
 *
 * @package TCLAS
 */

get_header();

$upcoming = tclas_get_upcoming_ext_events();
?>

<div class="tclas-page-header">
	<div class="container-tclas">
		<?php tclas_breadcrumb(); ?>
		<h1 class="tclas-page-header__title"><?php the_title(); ?></h1>
	</div>
</div>

<section class="tclas-section">
	<div class="container-tclas">

		<?php while ( have_posts() ) : the_post(); ?>
			<?php if ( get_the_content() ) : ?>
				<div class="tclas-member-map-intro">
					<?php the_content(); ?>
				</div>
			<?php endif; ?>
		<?php endwhile; ?>

		<div class="tclas-groups-layout<?php echo $upcoming ? ' tclas-groups-layout--has-events' : ''; ?>">

			<div class="tclas-groups-main">
				<?php echo do_shortcode( '[tclas_orgs_map]' ); ?>
			</div>

			<?php if ( $upcoming ) : ?>
			<aside class="tclas-groups-events" aria-label="<?php esc_attr_e( 'Upcoming events', 'tclas' ); ?>">
				<h2 class="tclas-groups-events__title"><?php esc_html_e( 'Upcoming events', 'tclas' ); ?></h2>
				<ul class="tclas-groups-events__list">
					<?php foreach ( $upcoming as $event ) :
						$date = get_field( 'event_date', $event->ID );
						$host = get_field( 'event_host', $event->ID );
						$city = get_field( 'event_city', $event->ID );
						$url  = get_field( 'event_url', $event->ID );
						?>
						<li class="tclas-groups-events__item">
							<span class="tclas-groups-events__date"><?php echo esc_html( $date ); ?></span>
							<a class="tclas-groups-events__name" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener">
								<?php echo esc_html( get_the_title( $event ) ); ?>
							</a>
							<?php if ( $host || $city ) : ?>
								<span class="tclas-groups-events__meta"><?php echo esc_html( implode( ' · ', array_filter( [ $host, $city ] ) ) ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
				<p class="tclas-groups-events__note">
					<?php
					printf(
						/* translators: %s: TCLAS events page URL */
						wp_kses_post( __( 'Events from the wider Luxembourg-American community. Looking for ours? See <a href="%s">TCLAS events</a>.', 'tclas' ) ),
						esc_url( home_url( '/events/' ) )
					);
					?>
				</p>
			</aside>
			<?php endif; ?>

		</div>

	</div>
</section>

<?php get_footer(); ?>
