<?php
/**
 * Newsletter article card
 *
 * Styled to match event cards with issue date, byline, title, and excerpt.
 *
 * @package TCLAS
 */

// Get issue date and format it
$issue_date = get_post_meta( get_the_ID(), 'tclas_issue_date', true );
$issue_label = '';
if ( $issue_date ) {
	$dt = DateTime::createFromFormat( 'Y-m', $issue_date );
	$issue_label = $dt ? $dt->format( 'F Y' ) : esc_html( $issue_date );
}

// Get author
$author = get_the_author();
$author_url = get_author_posts_url( get_the_author_meta( 'ID' ) );
?>
<a href="<?php the_permalink(); ?>" class="tclas-event-card">
	<article>
		<div class="tclas-event-card__image">
			<?php if ( has_post_thumbnail() ) : ?>
				<?php the_post_thumbnail( 'tclas-card', [ 'loading' => 'lazy', 'alt' => '' ] ); ?>
			<?php else : ?>
				<div class="tclas-event-card__image-placeholder" aria-hidden="true">
					📰
				</div>
			<?php endif; ?>
		</div>

		<div class="tclas-event-card__content">
			<h3 class="tclas-event-card__title">
				<?php the_title(); ?>
			</h3>

			<p class="tclas-event-card__excerpt">
				<?php echo esc_html( wp_trim_words( get_the_excerpt(), 22 ) ); ?>
			</p>

			<div class="tclas-event-card__meta">
				<?php if ( $issue_label ) : ?>
					<div class="tclas-event-card__meta-row">
						<span><?php echo esc_html( $issue_label ); ?></span>
					</div>
				<?php endif; ?>
				<?php if ( $author ) : ?>
					<div class="tclas-event-card__meta-row">
						<span><?php esc_html_e( 'By', 'tclas' ); ?> <a href="<?php echo esc_url( $author_url ); ?>" class="tclas-event-card__meta-link"><?php echo esc_html( $author ); ?></a></span>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</article>
</a>
