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
		// /map/ slug moved into member-hub (not yet needed pre-launch)
		// 'map/'                   => 'member-hub/ancestral-map/',
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

	// Prefix-based redirects: /commune/{slug}/ → /member-hub/ancestral-map/commune/{slug}/
	$prefix_redirects = [
		'commune/' => 'member-hub/ancestral-map/commune/',
	];
	foreach ( $prefix_redirects as $old_prefix => $new_prefix ) {
		if ( strpos( $request, $base . $old_prefix ) === 0 ) {
			$remainder = substr( $request, strlen( $base . $old_prefix ) );
			wp_safe_redirect( home_url( '/' . $new_prefix . $remainder ), 301 );
			exit;
		}
	}
}, 1 );
