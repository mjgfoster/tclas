<?php
/**
 * Custom post types
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function tclas_register_post_types(): void {

	// Luxembourg Story
	register_post_type( 'tclas_story', [
		'labels' => [
			'name'               => __( 'Luxembourg Stories', 'tclas' ),
			'singular_name'      => __( 'Luxembourg Story', 'tclas' ),
			'add_new_item'       => __( 'Add new story', 'tclas' ),
			'edit_item'          => __( 'Edit story', 'tclas' ),
			'new_item'           => __( 'New story', 'tclas' ),
			'view_item'          => __( 'View story', 'tclas' ),
			'search_items'       => __( 'Search stories', 'tclas' ),
			'not_found'          => __( 'No stories found.', 'tclas' ),
			'not_found_in_trash' => __( 'No stories found in trash.', 'tclas' ),
			'menu_name'          => __( 'Stories', 'tclas' ),
		],
		'public'             => true,
		'has_archive'        => true,
		'rewrite'            => [ 'slug' => 'stories', 'with_front' => false ],
		'supports'           => [ 'title', 'editor', 'author', 'thumbnail', 'excerpt' ],
		'menu_icon'          => 'dashicons-book-alt',
		'menu_position'      => 5,
		'show_in_rest'       => true,
	] );

	// Newsletter submission
	register_post_type( 'tclas_nl_submit', [
		'labels' => [
			'name'               => __( 'Newsletter Submissions', 'tclas' ),
			'singular_name'      => __( 'Newsletter Submission', 'tclas' ),
			'add_new_item'       => __( 'Add submission', 'tclas' ),
			'edit_item'          => __( 'Edit submission', 'tclas' ),
			'new_item'           => __( 'New submission', 'tclas' ),
			'view_item'          => __( 'View submission', 'tclas' ),
			'search_items'       => __( 'Search submissions', 'tclas' ),
			'not_found'          => __( 'No submissions found.', 'tclas' ),
			'not_found_in_trash' => __( 'No submissions found in trash.', 'tclas' ),
			'menu_name'          => __( 'Newsletter Subs', 'tclas' ),
		],
		'public'        => false,
		'show_ui'       => true,
		'show_in_menu'  => true,
		'supports'      => [ 'title' ],
		'menu_icon'     => 'dashicons-email-alt',
		'menu_position' => 26,
		'rewrite'       => false,
		'capabilities'  => [
			'edit_post'          => 'manage_options',
			'read_post'          => 'manage_options',
			'delete_post'        => 'manage_options',
			'edit_posts'         => 'manage_options',
			'edit_others_posts'  => 'manage_options',
			'publish_posts'      => 'manage_options',
			'read_private_posts' => 'manage_options',
		],
	] );

	// Board member
	register_post_type( 'tclas_board', [
		'labels' => [
			'name'          => __( 'Board members', 'tclas' ),
			'singular_name' => __( 'Board member', 'tclas' ),
			'add_new_item'  => __( 'Add board member', 'tclas' ),
			'edit_item'     => __( 'Edit board member', 'tclas' ),
			'menu_name'     => __( 'Board', 'tclas' ),
		],
		'public'       => false,
		'show_ui'      => true,
		'show_in_menu' => true,
		'supports'     => [ 'title', 'thumbnail', 'page-attributes' ],
		'menu_icon'    => 'dashicons-groups',
		'menu_position'=> 6,
		'rewrite'      => false,
	] );
}
add_action( 'init', 'tclas_register_post_types' );
