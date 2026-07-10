<?php
/**
 * Custom post types
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function tclas_register_post_types(): void {

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

	// Luxembourgish surname (public surname explorer — one entry per variant cluster)
	register_post_type( 'tclas_surname', [
		'labels' => [
			'name'               => __( 'Surnames', 'tclas' ),
			'singular_name'      => __( 'Surname', 'tclas' ),
			'add_new_item'       => __( 'Add surname', 'tclas' ),
			'edit_item'          => __( 'Edit surname', 'tclas' ),
			'new_item'           => __( 'New surname', 'tclas' ),
			'view_item'          => __( 'View surname', 'tclas' ),
			'search_items'       => __( 'Search surnames', 'tclas' ),
			'not_found'          => __( 'No surnames found.', 'tclas' ),
			'not_found_in_trash' => __( 'No surnames found in trash.', 'tclas' ),
			'menu_name'          => __( 'Surnames', 'tclas' ),
		],
		'public'        => true,
		'show_in_rest'  => true, // block editor for optional longer write-ups
		'menu_icon'     => 'dashicons-id-alt',
		'menu_position' => 7,
		'supports'      => [ 'title', 'editor' ],
		'has_archive'   => false, // the finder page at /ancestry/surnames/ is the archive
		'rewrite'       => [ 'slug' => 'ancestry/surnames', 'with_front' => false ],
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
