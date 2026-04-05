<?php
/**
 * WP Recipe Maker — custom template override
 *
 * Replaces WPRM's plugin template with clean, semantic HTML styled
 * entirely by the theme. Keeps the plugin for editor UI, structured
 * data (JSON-LD), and the recipe CPT — only the frontend output changes.
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Format minutes into a human-readable string.
 */
function tclas_wprm_format_time( int $minutes ): string {
	if ( $minutes <= 0 ) { return ''; }
	$hours = floor( $minutes / 60 );
	$mins  = $minutes % 60;
	$parts = [];
	if ( $hours ) { $parts[] = sprintf( _n( '%d hour', '%d hours', $hours, 'tclas' ), $hours ); }
	if ( $mins )  { $parts[] = sprintf( _n( '%d min', '%d mins', $mins, 'tclas' ), $mins ); }
	return implode( ' ', $parts );
}

/**
 * Replace WPRM template output with clean theme markup.
 */
add_filter( 'wprm_get_template', function ( string $output, $recipe ) {
	if ( ! $recipe || ! is_object( $recipe ) ) {
		return $output;
	}

	$name        = $recipe->name();
	$summary     = $recipe->summary();
	$image       = $recipe->image( 'medium_large' );
	$servings    = $recipe->servings();
	$servings_u  = $recipe->servings_unit();
	$prep        = (int) $recipe->prep_time();
	$cook        = (int) $recipe->cook_time();
	$total       = (int) $recipe->total_time();
	$ingredients = $recipe->ingredients();
	$instructions = $recipe->instructions();
	$notes       = $recipe->notes();

	ob_start();
	?>
	<div class="tclas-recipe">

		<?php if ( $name ) : ?>
		<h2 class="tclas-recipe__title"><?php echo esc_html( $name ); ?></h2>
		<?php endif; ?>

		<?php if ( $summary ) : ?>
		<div class="tclas-recipe__summary"><?php echo wp_kses_post( $summary ); ?></div>
		<?php endif; ?>

		<?php if ( $image ) : ?>
		<div class="tclas-recipe__image"><?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		<?php endif; ?>

		<?php if ( $servings || $prep || $cook || $total ) : ?>
		<dl class="tclas-recipe__meta">
			<?php if ( $servings ) : ?>
			<div class="tclas-recipe__meta-item">
				<dt><?php esc_html_e( 'Servings', 'tclas' ); ?></dt>
				<dd><?php echo esc_html( $servings . ( $servings_u ? ' ' . $servings_u : '' ) ); ?></dd>
			</div>
			<?php endif; ?>
			<?php if ( $prep ) : ?>
			<div class="tclas-recipe__meta-item">
				<dt><?php esc_html_e( 'Prep', 'tclas' ); ?></dt>
				<dd><?php echo esc_html( tclas_wprm_format_time( $prep ) ); ?></dd>
			</div>
			<?php endif; ?>
			<?php if ( $cook ) : ?>
			<div class="tclas-recipe__meta-item">
				<dt><?php esc_html_e( 'Cook', 'tclas' ); ?></dt>
				<dd><?php echo esc_html( tclas_wprm_format_time( $cook ) ); ?></dd>
			</div>
			<?php endif; ?>
			<?php if ( $total ) : ?>
			<div class="tclas-recipe__meta-item">
				<dt><?php esc_html_e( 'Total', 'tclas' ); ?></dt>
				<dd><?php echo esc_html( tclas_wprm_format_time( $total ) ); ?></dd>
			</div>
			<?php endif; ?>
		</dl>
		<?php endif; ?>

		<?php if ( ! empty( $ingredients ) ) : ?>
		<div class="tclas-recipe__section">
			<h3 class="tclas-recipe__section-title"><?php esc_html_e( 'Ingredients', 'tclas' ); ?></h3>
			<?php foreach ( $ingredients as $group ) : ?>
				<?php if ( ! empty( $group['name'] ) ) : ?>
				<h4 class="tclas-recipe__group-title"><?php echo esc_html( $group['name'] ); ?></h4>
				<?php endif; ?>
				<?php if ( ! empty( $group['ingredients'] ) ) : ?>
				<ul class="tclas-recipe__ingredients">
					<?php foreach ( $group['ingredients'] as $ing ) :
						$parts = array_filter( [
							trim( $ing['amount'] ?? '' ),
							trim( $ing['unit'] ?? '' ),
							trim( $ing['name'] ?? '' ),
						] );
						if ( ! empty( $ing['notes'] ) ) {
							$parts[] = '<span class="tclas-recipe__ing-note">(' . esc_html( $ing['notes'] ) . ')</span>';
						}
					?>
					<li><?php echo wp_kses_post( implode( ' ', $parts ) ); ?></li>
					<?php endforeach; ?>
				</ul>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<?php if ( ! empty( $instructions ) ) : ?>
		<div class="tclas-recipe__section">
			<h3 class="tclas-recipe__section-title"><?php esc_html_e( 'Instructions', 'tclas' ); ?></h3>
			<?php foreach ( $instructions as $group ) : ?>
				<?php if ( ! empty( $group['name'] ) ) : ?>
				<h4 class="tclas-recipe__group-title"><?php echo esc_html( $group['name'] ); ?></h4>
				<?php endif; ?>
				<?php if ( ! empty( $group['instructions'] ) ) : ?>
				<ol class="tclas-recipe__instructions">
					<?php foreach ( $group['instructions'] as $step ) :
						$is_tip = isset( $step['type'] ) && 'tip' === $step['type'];
					?>
						<?php if ( $is_tip ) : ?>
						<li class="tclas-recipe__tip"><?php echo wp_kses_post( $step['text'] ); ?></li>
						<?php else : ?>
						<li>
							<?php echo wp_kses_post( $step['text'] ); ?>
							<?php if ( ! empty( $step['image'] ) ) : ?>
							<div class="tclas-recipe__step-image">
								<?php echo wp_get_attachment_image( $step['image'], 'medium' ); ?>
							</div>
							<?php endif; ?>
						</li>
						<?php endif; ?>
					<?php endforeach; ?>
				</ol>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<?php if ( $notes ) : ?>
		<div class="tclas-recipe__section">
			<h3 class="tclas-recipe__section-title"><?php esc_html_e( 'Notes', 'tclas' ); ?></h3>
			<div class="tclas-recipe__notes"><?php echo wp_kses_post( $notes ); ?></div>
		</div>
		<?php endif; ?>

	</div>
	<?php
	return ob_get_clean();
}, 10, 2 );

/**
 * Dequeue WPRM's public stylesheets — we handle all styling.
 */
add_action( 'wp_enqueue_scripts', function () {
	wp_dequeue_style( 'wprm-public' );
	wp_dequeue_style( 'wprm-public-modern' );
	wp_dequeue_style( 'wprm-template' );
}, 99 );
