<?php
/**
 * Export the July-release content (terms, CPT posts, pages) from the LOCAL
 * DB to JSON for bin/migrations/2026-07-17-july-release-content.php.
 * Run locally: ~/tclas-wp eval-file export-july-content.php > data.json
 */

$out = [ 'generated' => gmdate( 'c' ), 'terms' => [], 'posts' => [], 'pages' => [] ];

$skip_meta = [ '_edit_lock', '_edit_last', '_wp_old_slug', '_wp_old_date' ];

// Terms (flat taxonomies, but keep parent slug just in case).
foreach ( [ 'tclas_place_type', 'tclas_department' ] as $tax ) {
	foreach ( get_terms( [ 'taxonomy' => $tax, 'hide_empty' => false ] ) as $t ) {
		$out['terms'][] = [
			'taxonomy'    => $tax,
			'slug'        => $t->slug,
			'name'        => $t->name,
			'description' => $t->description,
			'parent_slug' => $t->parent ? get_term( $t->parent )->slug : '',
		];
	}
}

// CPT posts.
foreach ( [ 'tclas_place', 'tclas_surname', 'tclas_org', 'tclas_ext_event' ] as $pt ) {
	$posts = get_posts( [ 'post_type' => $pt, 'posts_per_page' => -1, 'post_status' => 'any', 'orderby' => 'ID', 'order' => 'ASC' ] );
	foreach ( $posts as $p ) {
		$meta = [];
		foreach ( get_post_meta( $p->ID ) as $k => $vals ) {
			if ( in_array( $k, $skip_meta, true ) ) {
				continue;
			}
			$meta[ $k ] = maybe_unserialize( $vals[0] );
		}
		$terms = [];
		foreach ( [ 'tclas_place_type', 'tclas_department' ] as $tax ) {
			$tt = wp_get_object_terms( $p->ID, $tax, [ 'fields' => 'slugs' ] );
			if ( $tt && ! is_wp_error( $tt ) ) {
				$terms[ $tax ] = $tt;
			}
		}
		$out['posts'][] = [
			'post_type'    => $pt,
			'slug'         => $p->post_name,
			'title'        => $p->post_title,
			'status'       => $p->post_status,
			'content'      => $p->post_content,
			'excerpt'      => $p->post_excerpt,
			'menu_order'   => $p->menu_order,
			'meta'         => $meta,
			'terms'        => $terms,
		];
	}
}

// Pages (tour has its own migration; parent pages exist on prod as 97/98).
foreach ( [ 389, 425, 435 ] as $pid ) {
	$p = get_post( $pid );
	$meta = [];
	foreach ( get_post_meta( $pid ) as $k => $vals ) {
		if ( in_array( $k, $skip_meta, true ) ) {
			continue;
		}
		$meta[ $k ] = maybe_unserialize( $vals[0] );
	}
	$out['pages'][] = [
		'slug'        => $p->post_name,
		'parent_path' => get_page_uri( $p->post_parent ),
		'title'       => $p->post_title,
		'status'      => $p->post_status,
		'content'     => $p->post_content,
		'menu_order'  => $p->menu_order,
		'meta'        => $meta,
	];
}

echo json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
