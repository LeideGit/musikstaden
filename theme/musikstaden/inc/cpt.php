<?php
/**
 * Custom post types.
 *
 * @package Musikstaden
 */

declare(strict_types=1);

add_action( 'init', 'musikstaden_register_post_types' );

/**
 * Register band and artist_application CPTs.
 */
function musikstaden_register_post_types(): void {
	register_post_type(
		'band',
		array(
			'labels'              => array(
				'name'               => __( 'Bands', 'musikstaden' ),
				'singular_name'        => __( 'Band', 'musikstaden' ),
				'add_new'              => __( 'Add Band', 'musikstaden' ),
				'add_new_item'         => __( 'Add New Band', 'musikstaden' ),
				'edit_item'            => __( 'Edit Band', 'musikstaden' ),
				'new_item'             => __( 'New Band', 'musikstaden' ),
				'view_item'            => __( 'View Band', 'musikstaden' ),
				'search_items'         => __( 'Search Bands', 'musikstaden' ),
				'not_found'            => __( 'No bands found.', 'musikstaden' ),
				'not_found_in_trash'   => __( 'No bands found in Trash.', 'musikstaden' ),
				'all_items'            => __( 'All Bands', 'musikstaden' ),
			),
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_icon'           => 'dashicons-album',
			'menu_position'       => 5,
			'has_archive'         => false,
			'rewrite'             => false,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'author' ),
			'show_in_rest'        => true,
			'capability_type'     => 'post',
		)
	);

	register_post_type(
		'artist_application',
		array(
			'labels'              => array(
				'name'               => __( 'Applications', 'musikstaden' ),
				'singular_name'      => __( 'Application', 'musikstaden' ),
				'add_new_item'       => __( 'Add Application', 'musikstaden' ),
				'edit_item'          => __( 'Edit Application', 'musikstaden' ),
				'all_items'          => __( 'Artist Applications', 'musikstaden' ),
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_icon'           => 'dashicons-id',
			'menu_position'       => 6,
			'supports'            => array( 'title' ),
			'capability_type'     => 'post',
			'capabilities'        => array(
				'create_posts' => 'do_not_allow',
			),
			'map_meta_cap'        => true,
		)
	);
}

/**
 * Filter band permalinks to /artist/{slug}.
 *
 * @param string  $post_link Permalink.
 * @param WP_Post $post Post object.
 */
add_filter( 'post_type_link', 'musikstaden_band_permalink', 10, 2 );
function musikstaden_band_permalink( string $post_link, WP_Post $post ): string {
	if ( 'band' !== $post->post_type ) {
		return $post_link;
	}
	return musikstaden_band_url( $post );
}

/**
 * Restrict band editing in admin to owners/admins.
 */
add_action( 'load-post.php', 'musikstaden_restrict_band_edit' );
add_action( 'load-post-new.php', 'musikstaden_restrict_band_create' );

function musikstaden_restrict_band_edit(): void {
	if ( ! isset( $_GET['post'] ) ) {
		return;
	}
	$post_id = (int) $_GET['post'];
	if ( 'band' !== get_post_type( $post_id ) ) {
		return;
	}
	if ( current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! musikstaden_user_can_edit_band( get_current_user_id(), $post_id ) ) {
		wp_die( esc_html__( 'You do not have permission to edit this band.', 'musikstaden' ) );
	}
}

function musikstaden_restrict_band_create(): void {
	if ( ! isset( $_GET['post_type'] ) || 'band' !== $_GET['post_type'] ) {
		return;
	}
	if ( current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! musikstaden_user_can_create_band( get_current_user_id() ) ) {
		wp_die( esc_html__( 'You have reached the maximum number of bands (5).', 'musikstaden' ) );
	}
}

/**
 * Auto-assign owner on band creation.
 */
add_action( 'save_post_band', 'musikstaden_assign_band_owner', 10, 3 );
function musikstaden_assign_band_owner( int $post_id, WP_Post $post, bool $update ): void {
	if ( $update || wp_is_post_revision( $post_id ) ) {
		return;
	}
	$user_id = (int) $post->post_author;
	if ( $user_id && ! musikstaden_get_user_band_role( $user_id, $post_id ) ) {
		musikstaden_set_user_band_role( $user_id, $post_id, 'owner' );
	}
}
