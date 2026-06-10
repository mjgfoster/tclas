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
