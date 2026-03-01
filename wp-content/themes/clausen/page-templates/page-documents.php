<?php
/**
 * Template Name: Documents library
 *
 * Member-gated document archive (citizenship guides, bylaws, newsletters, etc.)
 *
 * @package TCLAS
 */

get_header();
?>

<div class="tclas-page-header tclas-page-header--ardoise">
	<div class="container-tclas">
		<span class="tclas-eyebrow tclas-eyebrow--light"><?php esc_html_e( 'Member resources', 'tclas' ); ?></span>
		<h1 class="tclas-page-header__title"><?php the_title(); ?></h1>
	</div>
</div>

<section class="tclas-section">
	<div class="container-tclas">

		<?php if ( ! tclas_is_member() ) : ?>

			<!-- Member gate -->
			<div class="tclas-member-gate">
				<div class="tclas-member-gate__inner">
					<?php tclas_illustration( 'member_gate_illustration', __( 'Documents library', 'tclas' ), 'tclas-member-gate__illustration' ); ?>
					<h2><?php esc_html_e( 'Members only.', 'tclas' ); ?></h2>
					<p>
						<?php esc_html_e( 'Citizenship guides, bylaws, archived newsletters, and genealogy resources are available to TCLAS members.', 'tclas' ); ?>
					</p>
					<div class="tclas-member-gate__actions">
						<a href="<?php echo esc_url( home_url( '/join/' ) ); ?>" class="btn btn-primary">
							<?php esc_html_e( 'Join TCLAS', 'tclas' ); ?>
						</a>
						<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="btn btn-outline-ardoise">
							<?php esc_html_e( 'Log in', 'tclas' ); ?>
						</a>
					</div>
				</div>
			</div>

		<?php else : ?>

			<?php if ( get_the_content() ) : ?>
				<div class="tclas-prose mb-5">
					<?php the_content(); ?>
				</div>
			<?php endif; ?>

			<!-- Document categories — managed via page content blocks or ACF repeater (future) -->
			<div class="tclas-documents">

				<div class="tclas-documents__category">
					<h2 class="tclas-ruled"><?php esc_html_e( 'Citizenship & nationality', 'tclas' ); ?></h2>
					<p class="tclas-documents__placeholder text-muted">
						<?php esc_html_e( 'Documents will appear here once uploaded via the Media Library and linked in the page editor.', 'tclas' ); ?>
					</p>
				</div>

				<div class="tclas-documents__category">
					<h2 class="tclas-ruled"><?php esc_html_e( 'Bylaws & governance', 'tclas' ); ?></h2>
					<p class="tclas-documents__placeholder text-muted">
						<?php esc_html_e( 'Documents will appear here once uploaded.', 'tclas' ); ?>
					</p>
				</div>

				<div class="tclas-documents__category">
					<h2 class="tclas-ruled"><?php esc_html_e( 'Archived newsletters', 'tclas' ); ?></h2>
					<p class="tclas-documents__placeholder text-muted">
						<?php esc_html_e( 'Documents will appear here once uploaded.', 'tclas' ); ?>
					</p>
				</div>

			</div>

		<?php endif; ?>

	</div>
</section>

<?php get_footer(); ?>
