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

// Get author (custom byline field takes priority, then WP author, hidden if flagged)
$hide_byline   = get_post_meta( get_the_ID(), 'tclas_hide_byline', true );
$custom_byline = get_post_meta( get_the_ID(), 'tclas_byline', true );
$author     = $hide_byline ? '' : ( $custom_byline ? $custom_byline : get_the_author() );
$author_url = ( $hide_byline || $custom_byline ) ? '' : get_author_posts_url( get_the_author_meta( 'ID' ) );
?>
<article class="tclas-card tclas-card--accented">
	<div class="tclas-card__image">
		<a href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
			<?php if ( has_post_thumbnail() ) : ?>
				<?php the_post_thumbnail( 'tclas-card', [ 'loading' => 'lazy', 'alt' => '' ] ); ?>
			<?php else : ?>
				<div class="tclas-card__image--placeholder" aria-hidden="true">
					📰
				</div>
			<?php endif; ?>
		</a>
	</div>

	<div class="tclas-card__body">
		<div class="tclas-card__meta">
			<?php if ( $issue_label ) : ?>
				<span><?php echo esc_html( $issue_label ); ?></span>
				<span aria-hidden="true">&middot;</span>
			<?php endif; ?>
			<?php if ( $author ) : ?>
				<span><?php esc_html_e( 'By', 'tclas' ); ?> <a href="<?php echo esc_url( $author_url ); ?>" class="tclas-card__meta-link"><?php echo esc_html( $author ); ?></a></span>
			<?php endif; ?>
		</div>

		<h3 class="tclas-card__title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h3>

		<p class="tclas-card__excerpt">
			<?php echo esc_html( wp_trim_words( get_the_excerpt(), 22 ) ); ?>
		</p>
	</div>
</article>
