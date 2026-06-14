<?php
/**
 * Band Studio — frontend band create/edit.
 *
 * Template Name: Band Studio
 *
 * @package Musikstaden
 */

declare(strict_types=1);

if ( ! is_user_logged_in() ) {
	wp_safe_redirect( add_query_arg( 'redirect_to', rawurlencode( (string) ( $_SERVER['REQUEST_URI'] ?? '' ) ), home_url( '/logga-in/' ) ) );
	exit;
}

$slug      = get_post_field( 'post_name', get_queried_object_id() );
$is_create = ( 'nytt-band' === $slug );
$band_id   = isset( $_GET['band'] ) ? (int) $_GET['band'] : 0;
$user_id   = get_current_user_id();

if ( $is_create ) {
	if ( ! musikstaden_user_can_create_band( $user_id ) ) {
		wp_safe_redirect( musikstaden_dashboard_url( 'limit' ) );
		exit;
	}
} else {
	if ( ! $band_id || 'band' !== get_post_type( $band_id ) ) {
		wp_safe_redirect( musikstaden_dashboard_url() );
		exit;
	}
	if ( ! musikstaden_user_can_edit_band( $user_id, $band_id ) ) {
		wp_safe_redirect( musikstaden_dashboard_url( 'error' ) );
		exit;
	}
}

get_header();
musikstaden_render_band_studio_form( $band_id, $is_create );
get_footer();
