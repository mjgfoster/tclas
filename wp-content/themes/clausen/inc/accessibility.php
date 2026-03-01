<?php
/**
 * Accessibility helpers
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Add skip-to-content link immediately after <body>.
 */
function tclas_skip_link(): void {
	echo '<a class="skip-link" href="#main-content">' . esc_html__( 'Skip to main content', 'tclas' ) . '</a>';
}
add_action( 'wp_body_open', 'tclas_skip_link' );

/**
 * Remove WordPress default emojis (performance + cleanliness).
 */
function tclas_disable_emojis(): void {
	remove_action( 'wp_head',             'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles',     'print_emoji_styles' );
	remove_action( 'admin_print_styles',  'print_emoji_styles' );
	remove_filter( 'the_content_feed',    'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss',    'wp_staticize_emoji' );
	remove_filter( 'wp_mail',             'wp_staticize_emoji_for_email' );
}
add_action( 'init', 'tclas_disable_emojis' );

/**
 * Clean up wp_head output.
 */
function tclas_cleanup_head(): void {
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
}
add_action( 'init', 'tclas_cleanup_head' );

/**
 * Add lang attribute to <html> for Lëtzebuergesch content.
 * Defaults to en but makes it easy to toggle.
 */
add_filter( 'language_attributes', function( string $output ): string {
	return $output; // WordPress handles this via get_language_attributes()
} );
