<?php
/**
 * Template Name: Board of directors
 *
 * Lists all tclas_board posts (in menu_order) with headshots, names,
 * roles, and bios. Each member is anchorable by post slug, e.g.
 *   /about/board/#rebecca-foster
 *
 * Linked from the About page sidebar.
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
	<div class="container-tclas container--medium">

		<?php if ( get_the_content() ) : ?>
			<div class="tclas-prose tclas-board-intro">
				<?php the_content(); ?>
			</div>
		<?php endif; ?>

		<?php
		$board_members = new WP_Query( [
			'post_type'      => 'tclas_board',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		] );

		if ( $board_members->have_posts() ) : ?>
			<div class="tclas-board-list">
				<?php while ( $board_members->have_posts() ) : $board_members->the_post();
					$role  = function_exists( 'get_field' ) ? get_field( 'board_role' ) : '';
					$bio   = function_exists( 'get_field' ) ? get_field( 'board_bio' ) : '';
					$photo = get_the_post_thumbnail( get_the_ID(), 'medium', [ 'class' => 'tclas-board-member__photo-img' ] );
					$slug  = sanitize_title( get_post_field( 'post_name' ) );
					?>
					<article class="tclas-board-member" id="<?php echo esc_attr( $slug ); ?>">
						<div class="tclas-board-member__photo">
							<?php if ( $photo ) : ?>
								<?php echo $photo; // already escaped by WP ?>
							<?php else : ?>
								<span class="tclas-board-member__photo-placeholder" aria-hidden="true"></span>
							<?php endif; ?>
						</div>
						<div class="tclas-board-member__body">
							<h2 class="tclas-board-member__name"><?php the_title(); ?></h2>
							<?php if ( $role ) : ?>
								<p class="tclas-board-member__role"><?php echo esc_html( $role ); ?></p>
							<?php endif; ?>
							<?php if ( $bio ) : ?>
								<div class="tclas-board-member__bio tclas-prose">
									<?php echo wp_kses_post( wpautop( $bio ) ); ?>
								</div>
							<?php endif; ?>
						</div>
					</article>
				<?php endwhile; wp_reset_postdata(); ?>
			</div>
		<?php endif; ?>

	</div>
</section>

<?php get_footer(); ?>
