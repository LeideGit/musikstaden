<?php
/**
 * User ↔ band membership and roles.
 *
 * @package Musikstaden
 */

declare(strict_types=1);

const MUSIKSTADEN_BANDS_META = 'musikstaden_bands';

/**
 * Get all band memberships for a user.
 *
 * @return array<int, string> band_id => role
 */
function musikstaden_get_user_bands( int $user_id ): array {
	$bands = get_user_meta( $user_id, MUSIKSTADEN_BANDS_META, true );
	if ( ! is_array( $bands ) ) {
		return array();
	}
	return array_map( 'strval', $bands );
}

/**
 * Get role for user on a band.
 */
function musikstaden_get_user_band_role( int $user_id, int $band_id ): ?string {
	$bands = musikstaden_get_user_bands( $user_id );
	$key   = (string) $band_id;
	return isset( $bands[ $key ] ) ? $bands[ $key ] : null;
}

/**
 * Set role for user on a band.
 */
function musikstaden_set_user_band_role( int $user_id, int $band_id, string $role ): void {
	$bands           = musikstaden_get_user_bands( $user_id );
	$bands[ (string) $band_id ] = $role;
	update_user_meta( $user_id, MUSIKSTADEN_BANDS_META, $bands );
}

/**
 * Remove user from band.
 */
function musikstaden_remove_user_from_band( int $user_id, int $band_id ): void {
	$bands = musikstaden_get_user_bands( $user_id );
	unset( $bands[ (string) $band_id ] );
	update_user_meta( $user_id, MUSIKSTADEN_BANDS_META, $bands );
}

/**
 * Count bands user belongs to.
 */
function musikstaden_count_user_bands( int $user_id ): int {
	return count( musikstaden_get_user_bands( $user_id ) );
}

/**
 * Can user create another band?
 */
function musikstaden_user_can_create_band( int $user_id ): bool {
	if ( user_can( $user_id, 'manage_options' ) ) {
		return true;
	}
	return musikstaden_count_user_bands( $user_id ) < MUSIKSTADEN_MAX_BANDS;
}

/**
 * Can user edit band page?
 */
function musikstaden_user_can_edit_band( int $user_id, int $band_id ): bool {
	if ( user_can( $user_id, 'manage_options' ) ) {
		return true;
	}
	$role = musikstaden_get_user_band_role( $user_id, $band_id );
	return in_array( $role, array( 'owner', 'admin' ), true );
}

/**
 * Can user invite members to band?
 */
function musikstaden_user_can_invite_to_band( int $user_id, int $band_id ): bool {
	return musikstaden_user_can_edit_band( $user_id, $band_id );
}

/**
 * Can user delete band?
 */
function musikstaden_user_can_delete_band( int $user_id, int $band_id ): bool {
	if ( user_can( $user_id, 'manage_options' ) ) {
		return true;
	}
	return 'owner' === musikstaden_get_user_band_role( $user_id, $band_id );
}

/**
 * Get members listed on band page (all roles).
 *
 * @return array<int, array{user_id: int, role: string, name: string}>
 */
function musikstaden_get_band_members( int $band_id ): array {
	global $wpdb;

	$members = array();
	$users   = get_users(
		array(
			'meta_key'     => MUSIKSTADEN_BANDS_META,
			'meta_compare' => 'EXISTS',
			'fields'       => array( 'ID', 'display_name' ),
		)
	);

	foreach ( $users as $user ) {
		$role = musikstaden_get_user_band_role( (int) $user->ID, $band_id );
		if ( $role ) {
			$members[] = array(
				'user_id' => (int) $user->ID,
				'role'    => $role,
				'name'    => $user->display_name,
			);
		}
	}

	return $members;
}

/**
 * Admin meta box for band members on band edit screen.
 */
add_action( 'add_meta_boxes', 'musikstaden_band_members_meta_box' );
function musikstaden_band_members_meta_box(): void {
	add_meta_box(
		'musikstaden_band_members',
		__( 'Band Members', 'musikstaden' ),
		'musikstaden_render_band_members_box',
		'band',
		'side',
		'default'
	);
}

/**
 * @param WP_Post $post Post object.
 */
function musikstaden_render_band_members_box( WP_Post $post ): void {
	$members = musikstaden_get_band_members( $post->ID );
	if ( empty( $members ) ) {
		echo '<p>' . esc_html__( 'No members assigned yet.', 'musikstaden' ) . '</p>';
		return;
	}
	echo '<ul>';
	foreach ( $members as $member ) {
		printf(
			'<li><strong>%s</strong> — %s</li>',
			esc_html( $member['name'] ),
			esc_html( ucfirst( $member['role'] ) )
		);
	}
	echo '</ul>';
}
