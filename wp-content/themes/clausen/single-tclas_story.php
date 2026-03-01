<?php
/**
 * Single Luxembourg Story template
 *
 * @package TCLAS
 */

get_header();
?>

<?php while ( have_posts() ) : the_post(); ?>

	<?php
	// ACF fields
	$member_name  = function_exists( 'get_field' ) ? get_field( 'story_member_name' )  : '';
	$commune      = function_exists( 'get_field' ) ? get_field( 'story_commune' )       : '';
	$generation   = function_exists( 'get_field' ) ? get_field( 'story_generation' )    : '';
	$arrival_year = function_exists( 'get_field' ) ? get_field( 'story_arrival_year' )  : '';
	$surnames     = get_the_terms( get_the_ID(), 'tclas_surname' );
	$communes     = get_the_terms( get_the_ID(), 'tclas_commune' );
	?>

	<!-- Page header -->
	<div class="tclas-page-header tclas-page-header--orpale">
		<div class="container-tclas">
			<a href="<?php echo esc_url( home_url( '/stories/' ) ); ?>" class="tclas-back-link">
				&larr; <?php esc_html_e( 'Luxembourg stories', 'tclas' ); ?>
			</a>
			<span class="tclas-eyebrow"><?php esc_html_e( 'Luxembourg story', 'tclas' ); ?></span>
			<h1 class="tclas-page-header__title"><?php the_title(); ?></h1>

			<?php if ( $member_name || $arrival_year ) : ?>
				<div class="tclas-story__meta">
					<?php if ( $member_name ) : ?>
						<span class="tclas-story__author"><?php echo esc_html( $member_name ); ?></span>
					<?php endif; ?>
					<?php if ( $arrival_year ) : ?>
						<span class="tclas-story__year">
							<?php
							/* translators: %s: year */
							printf( esc_html__( 'Family arrived %s', 'tclas' ), esc_html( $arrival_year ) );
							?>
						</span>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( $communes || $surnames ) : ?>
				<div class="tclas-story__terms">
					<?php if ( $communes ) : ?>
						<?php foreach ( $communes as $term ) : ?>
							<a href="<?php echo esc_url( get_term_link( $term ) ); ?>" class="tclas-tag tclas-tag--commune">
								<?php echo esc_html( $term->name ); ?>
							</a>
						<?php endforeach; ?>
					<?php endif; ?>
					<?php if ( $surnames ) : ?>
						<?php foreach ( $surnames as $term ) : ?>
							<span class="tclas-tag tclas-tag--surname"><?php echo esc_html( $term->name ); ?></span>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<!-- Featured image -->
	<?php if ( has_post_thumbnail() ) : ?>
		<div class="tclas-story__hero">
			<?php the_post_thumbnail( 'tclas-hero', [ 'class' => 'tclas-story__hero-img', 'loading' => 'eager' ] ); ?>
		</div>
	<?php endif; ?>

	<!-- Story content -->
	<article class="tclas-section">
		<div class="container-tclas">
			<div class="tclas-prose">
				<?php the_content(); ?>
			</div>

			<!-- Navigation -->
			<nav class="tclas-post-nav" aria-label="<?php esc_attr_e( 'Story navigation', 'tclas' ); ?>">
				<?php
				the_post_navigation( [
					'prev_text' => '<span class="tclas-post-nav__label">' . __( 'Previous story', 'tclas' ) . '</span><span class="tclas-post-nav__title">%title</span>',
					'next_text' => '<span class="tclas-post-nav__label">' . __( 'Next story', 'tclas' ) . '</span><span class="tclas-post-nav__title">%title</span>',
				] );
				?>
			</nav>
		</div>
	</article>

<?php endwhile; ?>

<?php get_footer(); ?>
