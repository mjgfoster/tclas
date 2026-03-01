<?php
/**
 * Navigation walkers
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Primary nav walker — renders the .tclas-nav structure.
 */
class TCLAS_Nav_Walker extends Walker_Nav_Menu {

	public function start_lvl( &$output, $depth = 0, $args = null ): void {
		$output .= '<ul class="tclas-nav__dropdown">';
	}

	public function end_lvl( &$output, $depth = 0, $args = null ): void {
		$output .= '</ul>';
	}

	public function start_el( &$output, $data_object, $depth = 0, $args = null, $id = 0 ): void {
		$item   = $data_object;
		$classes = empty( $item->classes ) ? [] : (array) $item->classes;
		$classes[] = 'tclas-nav__item';

		if ( in_array( 'menu-item-has-children', $classes, true ) ) {
			$classes[] = 'has-dropdown';
		}

		$class_str = implode( ' ', array_filter( array_unique( $classes ) ) );
		$output   .= '<li class="' . esc_attr( $class_str ) . '">';

		$atts             = [];
		$atts['class']    = $depth === 0 ? 'tclas-nav__link' : '';
		$atts['href']     = ! empty( $item->url ) ? $item->url : '#';
		$atts['target']   = ! empty( $item->target ) ? $item->target : '';
		$atts['rel']      = ! empty( $item->xfn ) ? $item->xfn : '';
		$atts['title']    = ! empty( $item->attr_title ) ? $item->attr_title : '';
		$atts['aria-current'] = in_array( 'current-menu-item', $classes, true ) ? 'page' : '';

		if ( in_array( 'menu-item-has-children', (array) $item->classes, true ) && $depth === 0 ) {
			$atts['aria-haspopup'] = 'true';
			$atts['aria-expanded'] = 'false';
		}

		$atts = array_filter( $atts );
		$attr_str = '';
		foreach ( $atts as $attr => $val ) {
			$attr_str .= ' ' . $attr . '="' . esc_attr( $val ) . '"';
		}

		$title = apply_filters( 'the_title', $item->title, $item->ID );
		$output .= '<a' . $attr_str . '>' . esc_html( $title ) . '</a>';
	}

	public function end_el( &$output, $data_object, $depth = 0, $args = null ): void {
		$output .= '</li>';
	}
}

/**
 * Footer nav walker — simple flat list.
 */
class TCLAS_Footer_Nav_Walker extends Walker_Nav_Menu {

	public function start_lvl( &$output, $depth = 0, $args = null ): void {}
	public function end_lvl( &$output, $depth = 0, $args = null ): void {}

	public function start_el( &$output, $data_object, $depth = 0, $args = null, $id = 0 ): void {
		$item    = $data_object;
		$title   = apply_filters( 'the_title', $item->title, $item->ID );
		$url     = ! empty( $item->url ) ? $item->url : '#';
		$output .= '<li><a href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a></li>';
	}

	public function end_el( &$output, $data_object, $depth = 0, $args = null ): void {
		$output .= '';
	}
}
