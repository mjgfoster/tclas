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
		'rewrite'           => [ 'slug' => 'member-hub/ancestral-map/commune', 'with_front' => false ],
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

	// Newsletter department + topic (unified: structural layout + public browsing)
	// `main-story` slug is reserved — detected by templates to select the cover image.
	// All other terms are bilingual (Luxembourgish name, English description) and
	// browseable at /newsletter/topic/{slug}/.
	register_taxonomy( 'tclas_department', [ 'post' ], [
		'labels' => [
			'name'          => __( 'Newsletter topics', 'tclas' ),
			'singular_name' => __( 'Newsletter topic', 'tclas' ),
			'search_items'  => __( 'Search topics', 'tclas' ),
			'all_items'     => __( 'All topics', 'tclas' ),
			'edit_item'     => __( 'Edit topic', 'tclas' ),
			'add_new_item'  => __( 'Add topic', 'tclas' ),
			'menu_name'     => __( 'NL Topics', 'tclas' ),
		],
		'hierarchical'      => false,
		'public'            => true,
		'show_ui'           => true,
		'show_in_rest'      => true,
		'show_admin_column' => true,
		'rewrite'           => [ 'slug' => 'newsletter/topic', 'with_front' => false ],
	] );
}
add_action( 'init', 'tclas_register_taxonomies' );

/**
 * Seed tclas_department terms on init.
 *
 * `main-story` is a structural term used by newsletter templates to identify
 * the lead article (cover image source). All other terms are bilingual:
 * name = Luxembourgish display name, description = English equivalent.
 */
function tclas_seed_department_terms(): void {
	$terms = [
		'main-story'     => [ 'name' => 'Main Story',     'desc' => ''               ],
		'wellkomm'       => [ 'name' => 'Wëllkomm',       'desc' => 'Welcome'        ],
		'communauteit'   => [ 'name' => 'Communautéit',   'desc' => 'Community'      ],
		'an-der-kichen'  => [ 'name' => 'An der Kichen',  'desc' => 'In the Kitchen' ],
		'geschicht'      => [ 'name' => 'Geschicht',      'desc' => 'History'        ],
		'zu-letzebuerg'  => [ 'name' => 'Zu Lëtzebuerg',  'desc' => 'In Luxembourg'  ],
		'traditiounen'   => [ 'name' => 'Traditiounen',   'desc' => 'Traditions'     ],
		'eist-sprooch'   => [ 'name' => 'Eist Sprooch',   'desc' => 'Our Language'   ],
		'evenementer'    => [ 'name' => 'Evenementer',    'desc' => 'Events'         ],
		'spezialbericht' => [ 'name' => 'Spezialbericht', 'desc' => 'Special Report' ],
	];
	foreach ( $terms as $slug => $data ) {
		if ( ! term_exists( $slug, 'tclas_department' ) ) {
			wp_insert_term( $data['name'], 'tclas_department', [
				'slug'        => $slug,
				'description' => $data['desc'],
			] );
		}
	}
}
add_action( 'init', 'tclas_seed_department_terms', 20 );

/**
 * Seed tclas_commune terms from the commune-data.php lookup table.
 *
 * Runs once and sets a transient so the 583-entry loop doesn't fire on every
 * page load. Delete the transient (`wp transient delete tclas_communes_seeded`)
 * to re-seed after data changes.
 */
function tclas_seed_commune_terms(): void {
	if ( get_transient( 'tclas_communes_seeded' ) ) {
		return;
	}

	if ( ! function_exists( 'tclas_get_communes' ) ) {
		return;
	}

	$communes = tclas_get_communes();

	foreach ( $communes as $slug => $c ) {
		if ( ! term_exists( $slug, 'tclas_commune' ) ) {
			wp_insert_term( $c['name'], 'tclas_commune', [
				'slug'        => $slug,
				'description' => $c['lux'] ?? '',
			] );
		}
	}

	// Mark as seeded; flush rewrites so /commune/{slug}/ URLs resolve.
	set_transient( 'tclas_communes_seeded', '1', YEAR_IN_SECONDS );
	flush_rewrite_rules();
}
add_action( 'init', 'tclas_seed_commune_terms', 25 );
