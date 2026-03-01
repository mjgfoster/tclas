<?php
/**
 * TCLAS managed 301 redirects
 *
 * Add permanent redirects here rather than in .htaccess so they work in both
 * local (sub-directory) and production (root) environments without path changes.
 *
 * Format:  'from-slug/' => 'to-slug/'   — both relative to home_url()
 *
 * @package TCLAS
 */

add_action( 'template_redirect', function () {
	$redirects = [
		// OD-3: /quiz/ renamed to /citizenship/ (quiz shortcode lives there now)
		'quiz/'                     => 'citizenship/',
		// Legacy standalone pages replaced by member-hub structure
		'directory/'                => 'member-hub/profiles/',
		'profile/'                  => 'member-hub/profiles/',
		// Old member-hub child duplicate of /ancestry/
		'member-hub/ancestral-map/' => 'ancestry/',
	];

	// Strip query string and normalise to trailing-slash for comparison.
	$request = rtrim( strtok( $_SERVER['REQUEST_URI'], '?' ), '/' ) . '/';
	$base    = rtrim( parse_url( home_url( '/' ), PHP_URL_PATH ), '/' ) . '/';

	foreach ( $redirects as $from => $to ) {
		if ( $request === $base . $from ) {
			wp_safe_redirect( home_url( '/' . $to ), 301 );
			exit;
		}
	}
}, 1 );
