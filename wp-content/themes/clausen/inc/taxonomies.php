<?php
/**
 * Custom taxonomies
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function tclas_register_taxonomies(): void {

	// Ancestral commune
	register_taxonomy( 'tclas_commune', [ 'tclas_story', 'post' ], [
		'labels' => [
			'name'          => __( 'Ancestral communes', 'tclas' ),
			'singular_name' => __( 'Ancestral commune', 'tclas' ),
			'search_items'  => __( 'Search communes', 'tclas' ),
			'all_items'     => __( 'All communes', 'tclas' ),
			'edit_item'     => __( 'Edit commune', 'tclas' ),
			'add_new_item'  => __( 'Add commune', 'tclas' ),
			'menu_name'     => __( 'Communes', 'tclas' ),
		],
		'hierarchical'      => false,
		'public'            => true,
		'show_in_rest'      => true,
		'rewrite'           => [ 'slug' => 'commune', 'with_front' => false ],
		'show_admin_column' => true,
	] );

	// Family surname
	register_taxonomy( 'tclas_surname', [ 'tclas_story', 'post' ], [
		'labels' => [
			'name'          => __( 'Family surnames', 'tclas' ),
			'singular_name' => __( 'Surname', 'tclas' ),
			'search_items'  => __( 'Search surnames', 'tclas' ),
			'all_items'     => __( 'All surnames', 'tclas' ),
			'edit_item'     => __( 'Edit surname', 'tclas' ),
			'add_new_item'  => __( 'Add surname', 'tclas' ),
			'menu_name'     => __( 'Surnames', 'tclas' ),
		],
		'hierarchical'      => false,
		'public'            => true,
		'show_in_rest'      => true,
		'rewrite'           => [ 'slug' => 'surname', 'with_front' => false ],
		'show_admin_column' => true,
	] );

	// Immigration generation
	register_taxonomy( 'tclas_generation', [ 'tclas_story' ], [
		'labels' => [
			'name'          => __( 'Immigration generations', 'tclas' ),
			'singular_name' => __( 'Generation', 'tclas' ),
			'menu_name'     => __( 'Generation', 'tclas' ),
		],
		'hierarchical'      => true,
		'public'            => false,
		'show_ui'           => true,
		'show_admin_column' => true,
		'rewrite'           => false,
	] );

	// Newsletter department (editorial section labels for Loon & Lion)
	register_taxonomy( 'tclas_department', [ 'post' ], [
		'labels' => [
			'name'          => __( 'Departments', 'tclas' ),
			'singular_name' => __( 'Department', 'tclas' ),
			'search_items'  => __( 'Search departments', 'tclas' ),
			'all_items'     => __( 'All departments', 'tclas' ),
			'edit_item'     => __( 'Edit department', 'tclas' ),
			'add_new_item'  => __( 'Add department', 'tclas' ),
			'menu_name'     => __( 'Departments', 'tclas' ),
		],
		'hierarchical'      => false,
		'public'            => false,   // editorial metadata; no public archive
		'show_ui'           => true,
		'show_in_rest'      => true,
		'show_admin_column' => true,
		'rewrite'           => false,
	] );

	// News category (content pillars)
	register_taxonomy( 'tclas_category', [ 'post' ], [
		'labels' => [
			'name'          => __( 'Content categories', 'tclas' ),
			'singular_name' => __( 'Category', 'tclas' ),
			'menu_name'     => __( 'TCLAS categories', 'tclas' ),
		],
		'hierarchical'      => true,
		'public'            => true,
		'show_in_rest'      => true,
		'rewrite'           => [ 'slug' => 'category', 'with_front' => false ],
		'show_admin_column' => true,
	] );
}
add_action( 'init', 'tclas_register_taxonomies' );

/**
 * Seed the five fixed tclas_department terms on init.
 * Uses wp_insert_term() which is a no-op if the term already exists.
 */
function tclas_seed_department_terms(): void {
	$departments = [
		'intro'      => 'Intro',
		'main-story' => 'Main Story',
		'community'  => 'Community',
		'recipe'     => 'Recipe',
		'news'       => 'News',
	];
	foreach ( $departments as $slug => $name ) {
		if ( ! term_exists( $slug, 'tclas_department' ) ) {
			wp_insert_term( $name, 'tclas_department', [ 'slug' => $slug ] );
		}
	}
}
add_action( 'init', 'tclas_seed_department_terms', 20 );
