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

			// Site-wide toggle: off by default so board members' emails aren't exposed to spam harvesters.
			// Toggle in WP Admin → Theme options → "Show board emails publicly".
			$show_board_emails = function_exists( 'get_field' ) ? (bool) get_field( 'show_board_emails', 'option' ) : false;

			if ( $board_members->have_posts() ) :
			?>
			<aside class="tclas-about-layout__sidebar">
				<span class="tclas-eyebrow"><?php esc_html_e( 'Leadership', 'tclas' ); ?></span>
				<h2 class="tclas-about-layout__sidebar-title"><?php esc_html_e( 'Board of directors', 'tclas' ); ?></h2>

				<div class="tclas-board-grid">
					<?php while ( $board_members->have_posts() ) : $board_members->the_post();
						$role     = function_exists( 'get_field' ) ? get_field( 'board_role' )  : '';
						$email    = function_exists( 'get_field' ) ? get_field( 'board_email' ) : '';
						$bio      = function_exists( 'get_field' ) ? get_field( 'board_bio' )   : '';
						$slug     = sanitize_title( get_post_field( 'post_name' ) );
						$photo    = get_the_post_thumbnail( get_the_ID(), 'thumbnail', [ 'class' => 'tclas-board-card__photo-img' ] );
						$bio_link = $bio ? home_url( '/about/board/#' . $slug ) : '';
						?>
						<div class="tclas-board-card">
							<?php if ( $bio_link ) : ?>
								<a class="tclas-board-card__link" href="<?php echo esc_url( $bio_link ); ?>">
							<?php endif; ?>
							<div class="tclas-board-card__photo">
								<?php if ( $photo ) : ?>
									<?php echo $photo; // already escaped by WP ?>
								<?php else : ?>
									<span class="tclas-board-card__photo-placeholder" aria-hidden="true"></span>
								<?php endif; ?>
							</div>
							<div class="tclas-board-card__info">
								<strong class="tclas-board-card__name"><?php the_title(); ?></strong>
								<?php if ( $role ) : ?>
									<span class="tclas-board-card__role"><?php echo esc_html( $role ); ?></span>
								<?php endif; ?>
							</div>
							<?php if ( $bio_link ) : ?>
								</a>
							<?php endif; ?>
							<?php if ( $show_board_emails && $email ) : ?>
								<a class="tclas-board-card__email" href="mailto:<?php echo esc_attr( $email ); ?>">
									<?php echo esc_html( $email ); ?>
								</a>
							<?php endif; ?>
						</div>
					<?php endwhile; wp_reset_postdata(); ?>
				</div>
			</aside>
			<?php endif; ?>

		</div>
	</div>
</section>

<?php get_footer(); ?>
