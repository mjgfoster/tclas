<?php
/**
 * Newsletter Left Sidebar
 *
 * Sticky left sidebar for newsletter pages. Includes:
 * - "The Loon & The Lion" masthead in three colours
 * - Site search form (searches newsletter articles by name/keyword)
 * - Current Issue quick link
 * - Collapsible on mobile
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$newsletter_home = home_url( '/newsletter/' );
$search_action   = home_url( '/' );

// Determine current section for active state
$current_section = '';
if ( is_page_template( 'page-templates/page-newsletter.php' ) ) {
	$current_section = 'current';
}
?>

<aside class="tclas-newsletter-sidebar" id="newsletter-sidebar" aria-label="<?php esc_attr_e( 'Newsletter navigation', 'tclas' ); ?>">

	<!-- Mobile Toggle Button (hidden on desktop) -->
	<button
		class="tclas-newsletter-sidebar__toggle"
		aria-expanded="false"
		aria-controls="newsletter-sidebar-content"
	>
		<span class="toggle-label"><?php esc_html_e( 'Newsletter', 'tclas' ); ?></span>
		<svg class="toggle-icon" aria-hidden="true" focusable="false" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
			<line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
		</svg>
	</button>

	<!-- Sidebar Content -->
	<div class="tclas-newsletter-sidebar__content" id="newsletter-sidebar-content">

		<!-- Masthead -->
		<a
			href="<?php echo esc_url( $newsletter_home ); ?>"
			class="tclas-sidebar-masthead"
			aria-label="<?php esc_attr_e( 'The Loon & The Lion — Newsletter home', 'tclas' ); ?>"
		>
			<span class="tclas-sidebar-masthead__loon"><?php esc_html_e( 'The Loon', 'tclas' ); ?></span>
			<span class="tclas-sidebar-masthead__amp"> &amp; </span>
			<span class="tclas-sidebar-masthead__lion"><?php esc_html_e( 'The Lion', 'tclas' ); ?></span>
		</a>

		<!-- Search -->
		<form
			class="tclas-sidebar-search"
			role="search"
			method="get"
			action="<?php echo esc_url( $search_action ); ?>"
		>
			<div class="tclas-sidebar-search__wrap">
				<input
					type="search"
					name="s"
					class="tclas-sidebar-search__input"
					placeholder="<?php esc_attr_e( 'Search articles…', 'tclas' ); ?>"
					aria-label="<?php esc_attr_e( 'Search newsletter articles', 'tclas' ); ?>"
				>
				<input type="hidden" name="post_type" value="post">
				<button type="submit" class="tclas-sidebar-search__btn" aria-label="<?php esc_attr_e( 'Search', 'tclas' ); ?>">
					<svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
					</svg>
				</button>
			</div>
		</form>

		<!-- Navigation -->
		<nav class="tclas-sidebar-nav" aria-label="<?php esc_attr_e( 'Newsletter navigation', 'tclas' ); ?>">
			<ul class="tclas-sidebar-links">
				<li>
					<a
						href="<?php echo esc_url( $newsletter_home ); ?>"
						class="tclas-sidebar-link<?php echo $current_section === 'current' ? ' is-active' : ''; ?>"
					>
						<?php esc_html_e( 'Current Issue', 'tclas' ); ?>
					</a>
				</li>
			</ul>
		</nav>

	</div><!-- .tclas-newsletter-sidebar__content -->

</aside><!-- .tclas-newsletter-sidebar -->

<script>
(function() {
	'use strict';
	const toggle  = document.querySelector( '.tclas-newsletter-sidebar__toggle' );
	const content = document.querySelector( '.tclas-newsletter-sidebar__content' );
	if ( ! toggle || ! content ) { return; }

	toggle.addEventListener( 'click', function() {
		const isOpen = content.classList.contains( 'is-open' );
		content.classList.toggle( 'is-open', ! isOpen );
		toggle.setAttribute( 'aria-expanded', String( ! isOpen ) );
	} );

	// Close on link click (mobile UX)
	content.querySelectorAll( 'a' ).forEach( function( link ) {
		link.addEventListener( 'click', function() {
			if ( window.innerWidth < 1024 ) {
				content.classList.remove( 'is-open' );
				toggle.setAttribute( 'aria-expanded', 'false' );
			}
		} );
	} );
} )();
</script>
