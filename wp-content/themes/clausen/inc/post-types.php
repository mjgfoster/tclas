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
