<?php
/**
 * Template Name: About page
 *
 * @package TCLAS
 */

get_header();
?>

<div class="tclas-page-header">
	<div class="container-tclas">
		<?php tclas_breadcrumb(); ?>
		<h1 class="tclas-page-header__title"><?php the_title(); ?></h1>
	</div>
</div>

<section class="tclas-section">
	<div class="container-tclas">
		<div class="tclas-about-layout">

			<!-- Main column -->
			<div class="tclas-about-layout__main">
				<div class="tclas-prose">
					<?php the_content(); ?>
				</div>
			</div>

			<!-- Sidebar — board of directors -->
			<?php
			$board_members = new WP_Query( [
				'post_type'      => 'tclas_board',
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			] );

			if ( $board_members->have_posts() ) :
			?>
			<aside class="tclas-about-layout__sidebar">
				<span class="tclas-eyebrow"><?php esc_html_e( 'Leadership', 'tclas' ); ?></span>
				<h2 class="tclas-about-layout__sidebar-title"><?php esc_html_e( 'Board of directors', 'tclas' ); ?></h2>

				<div class="tclas-board-grid">
					<?php while ( $board_members->have_posts() ) : $board_members->the_post(); ?>
						<?php
						$role  = function_exists( 'get_field' ) ? get_field( 'board_role' )  : '';
						$email = function_exists( 'get_field' ) ? get_field( 'board_email' ) : '';
						?>
						<div class="tclas-board-card">
							<?php if ( has_post_thumbnail() ) : ?>
								<div class="tclas-board-card__photo">
									<?php the_post_thumbnail( 'tclas-square', [ 'class' => 'tclas-board-card__img', 'alt' => get_the_title() ] ); ?>
								</div>
							<?php else : ?>
								<div class="tclas-board-card__photo tclas-illustration-placeholder" aria-hidden="true"></div>
							<?php endif; ?>
							<div class="tclas-board-card__info">
								<strong class="tclas-board-card__name"><?php the_title(); ?></strong>
								<?php if ( $role ) : ?>
									<span class="tclas-board-card__role"><?php echo esc_html( $role ); ?></span>
								<?php endif; ?>
								<?php if ( $email ) : ?>
									<a href="mailto:<?php echo esc_attr( $email ); ?>" class="tclas-board-card__email">
										<?php echo esc_html( $email ); ?>
									</a>
								<?php endif; ?>
							</div>
						</div>
					<?php endwhile; wp_reset_postdata(); ?>
				</div>
			</aside>
			<?php endif; ?>

		</div>
	</div>
</section>

<?php get_footer(); ?>
