<?php
/**
 * Taxonomies for bands.
 *
 * @package Musikstaden
 */

declare(strict_types=1);

add_action( 'init', 'musikstaden_register_taxonomies' );

/**
 * Register city, genre, event_type, gig_type taxonomies.
 */
function musikstaden_register_taxonomies(): void {
	$taxonomies = array(
		'city'       => array(
			'label'       => __( 'Cities', 'musikstaden' ),
			'singular'    => __( 'City', 'musikstaden' ),
			'description' => __( 'Swedish cities where the band is based.', 'musikstaden' ),
		),
		'genre'      => array(
			'label'       => __( 'Genres', 'musikstaden' ),
			'singular'    => __( 'Genre', 'musikstaden' ),
			'description' => __( 'Musical genres.', 'musikstaden' ),
		),
		'event_type' => array(
			'label'       => __( 'Event Types', 'musikstaden' ),
			'singular'    => __( 'Event Type', 'musikstaden' ),
			'description' => __( 'Types of events the band plays.', 'musikstaden' ),
		),
		'gig_type'   => array(
			'label'       => __( 'Gig Types', 'musikstaden' ),
			'singular'    => __( 'Gig Type', 'musikstaden' ),
			'description' => __( 'Booking categories (weddings, corporate, etc.).', 'musikstaden' ),
		),
	);

	foreach ( $taxonomies as $slug => $config ) {
		register_taxonomy(
			$slug,
			array( 'band' ),
			array(
				'labels'            => array(
					'name'          => $config['label'],
					'singular_name' => $config['singular'],
					'search_items'  => sprintf( __( 'Search %s', 'musikstaden' ), $config['label'] ),
					'all_items'     => sprintf( __( 'All %s', 'musikstaden' ), $config['label'] ),
					'edit_item'     => sprintf( __( 'Edit %s', 'musikstaden' ), $config['singular'] ),
					'add_new_item'  => sprintf( __( 'Add New %s', 'musikstaden' ), $config['singular'] ),
				),
				'description'       => $config['description'],
				'public'            => true,
				'hierarchical'      => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => $slug ),
			)
		);
	}
}

/**
 * Get terms for search dropdown.
 *
 * @return WP_Term[]
 */
function musikstaden_get_filter_terms( string $taxonomy ): array {
	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);
	return is_wp_error( $terms ) ? array() : $terms;
}
