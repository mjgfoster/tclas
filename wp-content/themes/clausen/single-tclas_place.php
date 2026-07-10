<?php
/**
 * Single Luxembourgish place (tclas_place)
 *
 * The history write-up for one place on the Luxembourg in America map:
 * locality line + type badges, the story, a "Learn more" links box, and a
 * path back to the map.
 *
 * @package TCLAS
 */

get_header();

while ( have_posts() ) :
	the_post();

	$county = get_field( 'place_county' );
	$state  = get_field( 'place_state' );
	$links  = get_field( 'place_links' );
	$types  = get_the_terms( get_the_ID(), 'tclas_place_type' );

	// Places-map CSS carries the badge/meta styles used below.
	wp_enqueue_style( 'tclas-places-map' );
	?>

	<div class="tclas-page-header">
		<div class="container-tclas">
			<?php tclas_breadcrumb(); ?>
			<h1 class="tclas-page-header__title"><?php the_title(); ?></h1>
		</div>
	</div>

	<section class="tclas-section">
		<div class="container-tclas container--medium">

			<div class="tclas-place-meta">
				<?php if ( $county || $state ) : ?>
					<span><?php echo esc_html( implode( ', ', array_filter( [ $county, $state ] ) ) ); ?></span>
				<?php endif; ?>
				<?php if ( $types && ! is_wp_error( $types ) ) : ?>
					<?php foreach ( $types as $type ) : ?>
						<span class="tclas-places-badge"><?php echo esc_html( $type->name ); ?></span>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<?php if ( has_post_thumbnail() ) : ?>
				<?php the_post_thumbnail( 'large' ); ?>
			<?php endif; ?>

			<div class="tclas-place-story">
				<?php the_content(); ?>
			</div>

			<?php
			// Newsletter/blog stories linked to this place via the
			// `related_places` relationship field on posts.
			$stories = get_posts( [
				'post_type'      => 'post',
				'posts_per_page' => 5,
				'meta_query'     => [
					[
						'key'     => 'related_places',
						'value'   => '"' . get_the_ID() . '"',
						'compare' => 'LIKE',
					],
				],
			] );
			?>
			<?php if ( $stories ) : ?>
				<aside class="tclas-place-links tclas-place-stories">
					<h2><?php esc_html_e( 'Stories about this place', 'tclas' ); ?></h2>
					<ul>
						<?php foreach ( $stories as $story ) : ?>
							<li>
								<a href="<?php echo esc_url( get_permalink( $story ) ); ?>">
									<?php echo esc_html( get_the_title( $story ) ); ?>
								</a>
								<span class="tclas-place-stories__date"><?php echo esc_html( get_the_date( '', $story ) ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				</aside>
			<?php endif; ?>

			<?php if ( $links ) : ?>
				<aside class="tclas-place-links">
					<h2><?php esc_html_e( 'Learn more', 'tclas' ); ?></h2>
					<ul>
						<?php foreach ( $links as $link ) : ?>
							<?php if ( empty( $link['link_url'] ) ) continue; ?>
							<li>
								<a href="<?php echo esc_url( $link['link_url'] ); ?>" target="_blank" rel="noopener">
									<?php echo esc_html( $link['link_label'] ?: $link['link_url'] ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</aside>
			<?php endif; ?>

			<p class="tclas-place-back">
				<a href="<?php echo esc_url( home_url( '/msp-lux/places/' ) ); ?>">
					&larr; <?php esc_html_e( 'Back to the Luxembourgers in North America map', 'tclas' ); ?>
				</a>
			</p>

		</div>
	</section>

	<?php
endwhile;

get_footer();
