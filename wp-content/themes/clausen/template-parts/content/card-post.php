<?php
/**
 * Post card partial
 *
 * @package TCLAS
 */
$cats     = get_the_category();
$cat      = $cats ? $cats[0] : null;
$cat_slug = $cat ? $cat->slug : 'default';
$cat_icon = tclas_category_icon( $cat_slug );
$minutes  = tclas_reading_time();
?>
<article class="tclas-card tclas-card--accented">
	<div class="tclas-card__image">
		<?php if ( has_post_thumbnail() ) : ?>
			<a href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
				<?php the_post_thumbnail( 'tclas-card', [ 'loading' => 'lazy', 'alt' => '' ] ); ?>
			</a>
		<?php else : ?>
			<div class="tclas-card__image--placeholder" aria-hidden="true">
				<?php if ( $cat_icon ) echo esc_html( $cat_icon ); ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="tclas-card__body">
		<div class="tclas-card__meta">
			<?php if ( $cat ) : ?>
				<a href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>" class="tclas-card__category">
					<?php echo esc_html( $cat->name ); ?>
				</a>
				<span aria-hidden="true">&middot;</span>
			<?php endif; ?>
			<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>" class="text-muted">
				<?php echo esc_html( get_the_date() ); ?>
			</time>
			<span class="text-muted"><?php printf( esc_html( _n( '%d min read', '%d min read', $minutes, 'tclas' ) ), $minutes ); ?></span>
		</div>

		<h3 class="tclas-card__title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h3>

		<p class="tclas-card__excerpt">
			<?php echo esc_html( wp_trim_words( get_the_excerpt(), 22 ) ); ?>
		</p>
	</div>
</article>
