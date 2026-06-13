<?php
/**
 * Custom artist role and capabilities.
 *
 * @package Musikstaden
 */

declare(strict_types=1);

add_action( 'after_switch_theme', 'musikstaden_setup_roles' );
add_action( 'init', 'musikstaden_ensure_roles' );

/**
 * Create artist role on theme activation.
 */
function musikstaden_setup_roles(): void {
	musikstaden_register_artist_role();
}

/**
 * Ensure role exists (also runs on init for existing installs).
 */
function musikstaden_ensure_roles(): void {
	if ( ! get_role( 'musikstaden_artist' ) ) {
		musikstaden_register_artist_role();
	}
}

/**
 * Register artist role with band editing capabilities.
 */
function musikstaden_register_artist_role(): void {
	remove_role( 'musikstaden_artist' );

	add_role(
		'musikstaden_artist',
		__( 'Artist', 'musikstaden' ),
		array(
			'read'                   => true,
			'upload_files'           => true,
			'edit_posts'             => true,
			'edit_published_posts'   => true,
			'publish_posts'          => true,
			'delete_posts'           => true,
			'delete_published_posts' => true,
		)
	);
}

/**
 * Limit artists to editing their own band posts unless admin.
 */
add_filter( 'map_meta_cap', 'musikstaden_map_band_caps', 10, 4 );
function musikstaden_map_band_caps( array $caps, string $cap, int $user_id, array $args ): array {
	if ( ! in_array( $cap, array( 'edit_post', 'delete_post', 'read_post' ), true ) ) {
		return $caps;
	}

	$post_id = $args[0] ?? 0;
	if ( ! $post_id || 'band' !== get_post_type( $post_id ) ) {
		return $caps;
	}

	if ( user_can( $user_id, 'manage_options' ) ) {
		return $caps;
	}

	if ( musikstaden_user_can_edit_band( $user_id, (int) $post_id ) ) {
		return array( 'edit_posts' );
	}

	if ( 'delete_post' === $cap && musikstaden_user_can_delete_band( $user_id, (int) $post_id ) ) {
		return array( 'delete_posts' );
	}

	return array( 'do_not_allow' );
}

/**
 * Restrict band list in admin to user's bands for non-admins.
 */
add_action( 'pre_get_posts', 'musikstaden_filter_admin_band_list' );
function musikstaden_filter_admin_band_list( WP_Query $query ): void {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( 'band' !== $query->get( 'post_type' ) ) {
		return;
	}
	if ( current_user_can( 'manage_options' ) ) {
		return;
	}

	$user_bands = array_keys( musikstaden_get_user_bands( get_current_user_id() ) );
	if ( empty( $user_bands ) ) {
		$user_bands = array( 0 );
	}
	$query->set( 'post__in', array_map( 'intval', $user_bands ) );
}
