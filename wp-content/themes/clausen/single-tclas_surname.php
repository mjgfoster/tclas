<?php
/**
 * Single Luxembourgish surname (tclas_surname)
 *
 * One page per variant cluster: variants, anglicisation note, attested
 * places (cross-links to the Luxembourgers in North America map when that
 * feature is active), sources, and the honesty note about shared names.
 * Optional editor content renders as a longer write-up.
 *
 * PRIVACY: historical data only — never member data or member counts.
 *
 * @package TCLAS
 */

get_header();

while ( have_posts() ) :
	the_post();

	$variants = (array) ( get_field( 'surname_variants' ) ?: [] );
	$note     = get_field( 'surname_note' );
	$shared   = get_field( 'surname_shared' );
	$attested = get_field( 'surname_attested' );
	$sources  = (array) ( get_field( 'surname_sources' ) ?: [] );

	// Attested places degrade gracefully when the places feature is inactive.
	$attested = array_filter(
		is_array( $attested ) ? $attested : [],
		fn( $p ) => $p instanceof WP_Post && 'publish' === $p->post_status
	);

	wp_enqueue_style( 'tclas-surname-finder' );
	?>

	<div class="tclas-page-header">
		<div class="container-tclas">
			<?php tclas_breadcrumb(); ?>
			<p class="tclas-surname-single__eyebrow"><?php esc_html_e( 'Luxembourgish family name', 'tclas' ); ?></p>
			<h1 class="tclas-page-header__title"><?php the_title(); ?></h1>
		</div>
	</div>

	<section class="tclas-section">
		<div class="container-tclas container--medium">

			<?php if ( $variants ) : ?>
				<div class="tclas-surname-single__variants">
					<?php foreach ( $variants as $row ) : ?>
						<?php if ( ! empty( $row['variant'] ) ) : ?>
							<span class="tclas-surname-variant-badge"><?php echo esc_html( $row['variant'] ); ?></span>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( $note ) : ?>
				<p class="tclas-surname-single__note"><?php echo esc_html( $note ); ?></p>
			<?php endif; ?>

			<?php if ( get_the_content() ) : ?>
				<div class="tclas-surname-single__story">
					<?php the_content(); ?>
				</div>
			<?php endif; ?>

			<?php if ( $attested ) : ?>
				<div class="tclas-surname-single__section">
					<h2><?php esc_html_e( 'Attested in', 'tclas' ); ?></h2>
					<ul>
						<?php foreach ( $attested as $place ) : ?>
							<li><a href="<?php echo esc_url( get_permalink( $place ) ); ?>"><?php echo esc_html( get_the_title( $place ) ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( $sources ) : ?>
				<div class="tclas-surname-single__section">
					<h2><?php esc_html_e( 'Sources', 'tclas' ); ?></h2>
					<ul>
						<?php foreach ( $sources as $src ) : ?>
							<?php if ( empty( $src['source_label'] ) ) continue; ?>
							<li>
								<?php if ( ! empty( $src['source_url'] ) ) : ?>
									<a href="<?php echo esc_url( $src['source_url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $src['source_label'] ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $src['source_label'] ); ?>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( $shared ) : ?>
				<p class="tclas-surname-single__shared">
					<?php esc_html_e( 'Family names cross borders: this name is attested among Luxembourgish emigrants, and is also found in Germany, Belgium, or France. Appearing here is a clue, not a certificate — records tell the full story.', 'tclas' ); ?>
				</p>
			<?php endif; ?>

			<div class="tclas-surname-single__cta">
				<p>
					<?php echo esc_html( sprintf(
						/* translators: %s: surname */
						__( 'Carry the name %s — or one of its variants? TCLAS members trace their families to specific Luxembourg communes on our ancestral map, and our "How Are We Connected" tool finds members whose roots cross yours.', 'tclas' ),
						get_the_title()
					) ); ?>
				</p>
				<a href="<?php echo esc_url( home_url( '/join/' ) ); ?>" class="btn btn-primary"><?php esc_html_e( 'Join TCLAS', 'tclas' ); ?></a>
			</div>

			<p class="tclas-surname-single__back">
				<a href="<?php echo esc_url( home_url( '/ancestry/surnames/' ) ); ?>">
					&larr; <?php esc_html_e( 'Back to the surname finder', 'tclas' ); ?>
				</a>
			</p>

		</div>
	</section>

	<?php
endwhile;

get_footer();
