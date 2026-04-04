<?php
/**
 * Theme setup
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function tclas_setup(): void {
	load_theme_textdomain( 'tclas', TCLAS_DIR . '/languages' );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', [ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ] );
	add_theme_support( 'customize-selective-refresh-widgets' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'responsive-embeds' );

	// Custom logo
	add_theme_support( 'custom-logo', [
		'height'      => 80,
		'width'       => 80,
		'flex-height' => true,
		'flex-width'  => true,
		'header-text' => [ 'site-title', 'site-description' ],
	] );

	// Image sizes
	add_image_size( 'tclas-card',     800,  533, true );
	add_image_size( 'tclas-hero',    1440,  640, true );
	add_image_size( 'tclas-hero-mobile', 640, 640, true );
	add_image_size( 'tclas-square',   600,  600, true );
	add_image_size( 'tclas-wide',    1200,  400, true );

	// Navigation menus
	register_nav_menus( [
		'primary'     => __( 'Primary navigation', 'tclas' ),
		'footer-main' => __( 'Footer: main links', 'tclas' ),
		'footer-org'  => __( 'Footer: organisation links', 'tclas' ),
		'hub'         => __( 'Member hub sidebar', 'tclas' ),
	] );
}
add_action( 'after_setup_theme', 'tclas_setup' );

/**
 * Sidebar / widget areas.
 */
function tclas_widgets_init(): void {
	register_sidebar( [
		'name'          => __( 'Member hub sidebar widgets', 'tclas' ),
		'id'            => 'hub-sidebar',
		'before_widget' => '<div class="tclas-hub-card mb-4" id="%1$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h2 class="tclas-hub-card__title">',
		'after_title'   => '</h2>',
	] );
}
add_action( 'widgets_init', 'tclas_widgets_init' );

/**
 * Set content width.
 */
function tclas_content_width(): void {
	$GLOBALS['content_width'] = 1200;
}
add_action( 'after_setup_theme', 'tclas_content_width', 0 );

/**
 * Hide the WordPress admin bar for users below author role.
 */
add_filter( 'show_admin_bar', function (): bool {
	return current_user_can( 'publish_posts' );
} );
