<?php
/**
 * Taxonomies for bands.
 *
 * @package Musikstaden
 */

declare(strict_types=1);

add_action( 'init', 'musikstaden_register_taxonomies' );
add_action( 'after_switch_theme', 'musikstaden_run_taxonomy_cleanup' );
add_action( 'admin_init', 'musikstaden_run_taxonomy_cleanup' );

/**
 * Register city, genre, and gig_type (booking type) taxonomies.
 */
function musikstaden_register_taxonomies(): void {
	$taxonomies = array(
		'city'     => array(
			'label'       => __( 'Cities', 'musikstaden' ),
			'singular'    => __( 'City', 'musikstaden' ),
			'description' => __( 'Swedish cities where the band is based.', 'musikstaden' ),
		),
		'genre'    => array(
			'label'       => __( 'Genres', 'musikstaden' ),
			'singular'    => __( 'Genre', 'musikstaden' ),
			'description' => __( 'Musical genres.', 'musikstaden' ),
		),
		'gig_type' => array(
			'label'       => __( 'Bokningstyper', 'musikstaden' ),
			'singular'    => __( 'Bokningstyp', 'musikstaden' ),
			'description' => __( 'What the band is available for — weddings, festivals, corporate events, etc.', 'musikstaden' ),
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
 * Canonical booking-type terms for seeding and migration.
 *
 * @return array<string, string> slug => label
 */
function musikstaden_get_booking_type_terms(): array {
	return array(
		'brollop'        => 'Bröllop',
		'foretagsevent'  => 'Företagsevent',
		'festival'       => 'Festival',
		'klubb-bar'      => 'Klubb / bar',
		'privat-fest'    => 'Privat fest',
		'restaurang'     => 'Restaurang / lounge',
	);
}

/**
 * Map legacy gig_type slugs to current booking slugs.
 */
function musikstaden_map_legacy_gig_slug( string $slug ): string {
	$map = array(
		'club'            => 'klubb-bar',
		'wedding'         => 'brollop',
		'corporate'       => 'foretagsevent',
		'festival'        => 'festival',
		'private-party'   => 'privat-fest',
		'restaurant'      => 'restaurang',
		'weddings'        => 'brollop',
		'private-events'  => 'privat-fest',
		'club-gigs'       => 'klubb-bar',
		'festivals'       => 'festival',
	);

	return $map[ $slug ] ?? $slug;
}

/**
 * Ensure canonical booking-type terms exist.
 */
function musikstaden_seed_booking_types(): void {
	foreach ( musikstaden_get_booking_type_terms() as $slug => $label ) {
		if ( ! term_exists( $slug, 'gig_type' ) ) {
			wp_insert_term( $label, 'gig_type', array( 'slug' => $slug ) );
		}
	}
}

/**
 * Get term slugs for a post, including unregistered legacy taxonomies.
 *
 * @return string[]
 */
function musikstaden_get_post_term_slugs( int $post_id, string $taxonomy ): array {
	if ( taxonomy_exists( $taxonomy ) ) {
		$terms = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'slugs' ) );
		return is_wp_error( $terms ) ? array() : $terms;
	}

	global $wpdb;

	$slugs = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT t.slug FROM {$wpdb->terms} t
			INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
			INNER JOIN {$wpdb->term_relationships} tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
			WHERE tr.object_id = %d AND tt.taxonomy = %s",
			$post_id,
			$taxonomy
		)
	);

	return is_array( $slugs ) ? array_map( 'strval', $slugs ) : array();
}

/**
 * One-time migration: merge event_type into gig_type, then drop old assignments.
 */
function musikstaden_migrate_event_types_to_gig_types(): void {
	if ( get_option( 'musikstaden_gig_type_migration_v1' ) ) {
		return;
	}

	musikstaden_seed_booking_types();

	$bands = get_posts(
		array(
			'post_type'      => 'band',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	foreach ( $bands as $band_id ) {
		$slugs = array();

		foreach ( array( 'event_type', 'gig_type' ) as $taxonomy ) {
			foreach ( musikstaden_get_post_term_slugs( (int) $band_id, $taxonomy ) as $slug ) {
				$slugs[] = musikstaden_map_legacy_gig_slug( $slug );
			}
		}

		$slugs = array_values( array_unique( array_filter( $slugs ) ) );
		if ( ! empty( $slugs ) ) {
			wp_set_object_terms( (int) $band_id, $slugs, 'gig_type' );
		}

		if ( taxonomy_exists( 'event_type' ) ) {
			wp_set_object_terms( (int) $band_id, array(), 'event_type' );
		}
	}

	update_option( 'musikstaden_gig_type_migration_v1', 1, false );
}

/**
 * Delete all event_type terms and taxonomy rows from the database.
 */
function musikstaden_remove_event_type_taxonomy(): void {
	if ( get_option( 'musikstaden_event_type_removed_v1' ) ) {
		return;
	}

	global $wpdb;

	$term_taxonomy_ids = $wpdb->get_col(
		"SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'event_type'"
	);

	if ( ! empty( $term_taxonomy_ids ) ) {
		$placeholders = implode( ', ', array_fill( 0, count( $term_taxonomy_ids ), '%d' ) );
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN ($placeholders)",
				...array_map( 'intval', $term_taxonomy_ids )
			)
		);
		$wpdb->delete( $wpdb->term_taxonomy, array( 'taxonomy' => 'event_type' ) );
	}

	// Remove terms that no longer belong to any taxonomy.
	$wpdb->query(
		"DELETE t FROM {$wpdb->terms} t
		LEFT JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
		WHERE tt.term_id IS NULL"
	);

	update_option( 'musikstaden_event_type_removed_v1', 1, false );
}

/**
 * Remove obsolete English gig_type terms after migration to Swedish booking slugs.
 */
function musikstaden_remove_obsolete_booking_terms(): void {
	if ( get_option( 'musikstaden_obsolete_gig_terms_removed_v1' ) ) {
		return;
	}

	musikstaden_seed_booking_types();

	$canonical = array_keys( musikstaden_get_booking_type_terms() );
	$terms     = get_terms(
		array(
			'taxonomy'   => 'gig_type',
			'hide_empty' => false,
		)
	);

	if ( is_wp_error( $terms ) ) {
		return;
	}

	foreach ( $terms as $term ) {
		if ( in_array( $term->slug, $canonical, true ) ) {
			continue;
		}

		$mapped = musikstaden_map_legacy_gig_slug( $term->slug );
		if ( ! in_array( $mapped, $canonical, true ) ) {
			continue;
		}

		$bands = get_posts(
			array(
				'post_type'      => 'band',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'tax_query'      => array(
					array(
						'taxonomy' => 'gig_type',
						'field'    => 'term_id',
						'terms'    => (int) $term->term_id,
					),
				),
			)
		);

		foreach ( $bands as $band_id ) {
			$existing = musikstaden_get_post_term_slugs( (int) $band_id, 'gig_type' );
			$existing[] = $mapped;
			wp_set_object_terms( (int) $band_id, array_values( array_unique( $existing ) ), 'gig_type' );
		}

		wp_delete_term( (int) $term->term_id, 'gig_type' );
	}

	update_option( 'musikstaden_obsolete_gig_terms_removed_v1', 1, false );
}

/**
 * Migrate booking data and remove unused event_type taxonomy.
 */
function musikstaden_run_taxonomy_cleanup(): void {
	musikstaden_migrate_event_types_to_gig_types();
	musikstaden_remove_event_type_taxonomy();
	musikstaden_remove_obsolete_booking_terms();
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
