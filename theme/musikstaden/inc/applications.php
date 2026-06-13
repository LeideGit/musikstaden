<?php
/**
 * Artist waitlist applications.
 *
 * @package Musikstaden
 */

declare(strict_types=1);

add_action( 'init', 'musikstaden_register_application_page' );
add_action( 'admin_post_nopriv_musikstaden_apply', 'musikstaden_handle_application' );
add_action( 'admin_post_musikstaden_apply', 'musikstaden_handle_application' );
add_action( 'add_meta_boxes', 'musikstaden_application_actions_meta_box' );
add_action( 'admin_post_musikstaden_approve_application', 'musikstaden_approve_application' );
add_action( 'admin_post_musikstaden_reject_application', 'musikstaden_reject_application' );

/**
 * Register rewrite for /sok/ search page handled by template.
 */
function musikstaden_register_application_page(): void {
	add_rewrite_rule( '^sok/?$', 'index.php?musikstaden_search=1', 'top' );
}

add_filter( 'query_vars', 'musikstaden_search_query_var' );
function musikstaden_search_query_var( array $vars ): array {
	$vars[] = 'musikstaden_search';
	return $vars;
}

add_filter( 'template_include', 'musikstaden_search_template' );
function musikstaden_search_template( string $template ): string {
	if ( get_query_var( 'musikstaden_search' ) ) {
		$custom = locate_template( 'page-search.php' );
		if ( $custom ) {
			return $custom;
		}
	}
	return $template;
}

/**
 * Handle waitlist form submission.
 */
function musikstaden_handle_application(): void {
	if ( ! isset( $_POST['musikstaden_apply_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['musikstaden_apply_nonce'] ) ), 'musikstaden_apply' ) ) {
		wp_die( esc_html__( 'Invalid submission.', 'musikstaden' ) );
	}

	$name      = sanitize_text_field( wp_unslash( $_POST['app_name'] ?? '' ) );
	$email     = sanitize_email( wp_unslash( $_POST['app_email'] ?? '' ) );
	$band_name = sanitize_text_field( wp_unslash( $_POST['app_band_name'] ?? '' ) );
	$city      = sanitize_text_field( wp_unslash( $_POST['app_city'] ?? '' ) );
	$genre     = sanitize_text_field( wp_unslash( $_POST['app_genre'] ?? '' ) );
	$pitch     = sanitize_textarea_field( wp_unslash( $_POST['app_pitch'] ?? '' ) );

	if ( ! $name || ! is_email( $email ) || ! $band_name ) {
		wp_safe_redirect( add_query_arg( 'apply', 'error', wp_get_referer() ?: home_url( '/for-artists/' ) ) );
		exit;
	}

	$post_id = wp_insert_post(
		array(
			'post_type'   => 'artist_application',
			'post_title'  => $name . ' — ' . $band_name,
			'post_status' => 'publish',
		)
	);

	if ( is_wp_error( $post_id ) ) {
		wp_safe_redirect( add_query_arg( 'apply', 'error', wp_get_referer() ?: home_url( '/for-artists/' ) ) );
		exit;
	}

	if ( function_exists( 'update_field' ) ) {
		update_field( 'app_email', $email, $post_id );
		update_field( 'app_band_name', $band_name, $post_id );
		update_field( 'app_city', $city, $post_id );
		update_field( 'app_genre', $genre, $post_id );
		update_field( 'app_pitch', $pitch, $post_id );
		update_field( 'app_status', 'pending', $post_id );
	} else {
		update_post_meta( $post_id, 'app_email', $email );
		update_post_meta( $post_id, 'app_band_name', $band_name );
		update_post_meta( $post_id, 'app_city', $city );
		update_post_meta( $post_id, 'app_genre', $genre );
		update_post_meta( $post_id, 'app_pitch', $pitch );
		update_post_meta( $post_id, 'app_status', 'pending' );
	}

	wp_safe_redirect( add_query_arg( 'apply', 'success', wp_get_referer() ?: home_url( '/for-artists/' ) ) );
	exit;
}

/**
 * Application approve/reject meta box.
 */
function musikstaden_application_actions_meta_box(): void {
	add_meta_box(
		'musikstaden_app_actions',
		__( 'Review Application', 'musikstaden' ),
		'musikstaden_render_application_actions',
		'artist_application',
		'side',
		'high'
	);
}

/**
 * @param WP_Post $post Post object.
 */
function musikstaden_render_application_actions( WP_Post $post ): void {
	$status = musikstaden_get_field( 'app_status', $post->ID ) ?: 'pending';
	$email  = musikstaden_get_field( 'app_email', $post->ID );
	?>
	<p><strong><?php esc_html_e( 'Status:', 'musikstaden' ); ?></strong> <?php echo esc_html( ucfirst( (string) $status ) ); ?></p>
	<?php if ( $email ) : ?>
		<p><strong><?php esc_html_e( 'Email:', 'musikstaden' ); ?></strong> <?php echo esc_html( (string) $email ); ?></p>
	<?php endif; ?>
	<?php if ( 'pending' === $status ) : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'musikstaden_approve_' . $post->ID ); ?>
			<input type="hidden" name="action" value="musikstaden_approve_application">
			<input type="hidden" name="application_id" value="<?php echo esc_attr( (string) $post->ID ); ?>">
			<button type="submit" class="button button-primary" style="width:100%;margin-bottom:8px;">
				<?php esc_html_e( 'Approve & Create User', 'musikstaden' ); ?>
			</button>
		</form>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'musikstaden_reject_' . $post->ID ); ?>
			<input type="hidden" name="action" value="musikstaden_reject_application">
			<input type="hidden" name="application_id" value="<?php echo esc_attr( (string) $post->ID ); ?>">
			<button type="submit" class="button" style="width:100%;">
				<?php esc_html_e( 'Reject', 'musikstaden' ); ?>
			</button>
		</form>
	<?php endif; ?>
	<?php
}

/**
 * Approve application: create WP user and send password reset.
 */
function musikstaden_approve_application(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Unauthorized.', 'musikstaden' ) );
	}

	$app_id = (int) ( $_POST['application_id'] ?? 0 );
	if ( ! $app_id || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'musikstaden_approve_' . $app_id ) ) {
		wp_die( esc_html__( 'Invalid request.', 'musikstaden' ) );
	}

	$email = (string) musikstaden_get_field( 'app_email', $app_id );
	$name  = get_the_title( $app_id );
	$name  = explode( ' — ', $name )[0] ?? $name;

	if ( ! is_email( $email ) ) {
		wp_die( esc_html__( 'Invalid email on application.', 'musikstaden' ) );
	}

	$existing = get_user_by( 'email', $email );
	if ( $existing ) {
		$user_id = (int) $existing->ID;
	} else {
		$username = sanitize_user( current( explode( '@', $email ) ), true );
		if ( username_exists( $username ) ) {
			$username .= wp_rand( 100, 999 );
		}
		$user_id = wp_insert_user(
			array(
				'user_login'   => $username,
				'user_email'   => $email,
				'display_name' => $name,
				'user_pass'    => wp_generate_password( 16 ),
				'role'         => 'musikstaden_artist',
			)
		);
		if ( is_wp_error( $user_id ) ) {
			wp_die( esc_html( $user_id->get_error_message() ) );
		}
	}

	if ( function_exists( 'update_field' ) ) {
		update_field( 'app_status', 'approved', $app_id );
	} else {
		update_post_meta( $app_id, 'app_status', 'approved' );
	}

	// Mark user as approved for New User Approve plugin compatibility.
	update_user_meta( $user_id, 'pw_user_status', 'approved' );
	update_user_meta( $user_id, 'musikstaden_approved', '1' );

	wp_new_user_notification( $user_id, null, 'user' );

	wp_safe_redirect( admin_url( 'post.php?post=' . $app_id . '&action=edit&approved=1' ) );
	exit;
}

/**
 * Reject application.
 */
function musikstaden_reject_application(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Unauthorized.', 'musikstaden' ) );
	}

	$app_id = (int) ( $_POST['application_id'] ?? 0 );
	if ( ! $app_id || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'musikstaden_reject_' . $app_id ) ) {
		wp_die( esc_html__( 'Invalid request.', 'musikstaden' ) );
	}

	if ( function_exists( 'update_field' ) ) {
		update_field( 'app_status', 'rejected', $app_id );
	} else {
		update_post_meta( $app_id, 'app_status', 'rejected' );
	}

	wp_safe_redirect( admin_url( 'post.php?post=' . $app_id . '&action=edit&rejected=1' ) );
	exit;
}

/**
 * Render application form partial.
 */
function musikstaden_render_application_form(): void {
	$status = sanitize_text_field( wp_unslash( $_GET['apply'] ?? '' ) );
	?>
	<?php if ( 'success' === $status ) : ?>
		<div class="notice notice-success"><?php ms_e( 'apply.success', 'Application submitted! We will review it soon.' ); ?></div>
	<?php elseif ( 'error' === $status ) : ?>
		<div class="notice notice-error"><?php ms_e( 'apply.error', 'Please fill in all required fields.' ); ?></div>
	<?php endif; ?>
	<form class="application-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'musikstaden_apply', 'musikstaden_apply_nonce' ); ?>
		<input type="hidden" name="action" value="musikstaden_apply">
		<div class="form-row">
			<label for="app_name"><?php ms_e( 'apply.name', 'Your name' ); ?> *</label>
			<input type="text" id="app_name" name="app_name" required>
		</div>
		<div class="form-row">
			<label for="app_email"><?php ms_e( 'apply.email', 'Email' ); ?> *</label>
			<input type="email" id="app_email" name="app_email" required>
		</div>
		<div class="form-row">
			<label for="app_band_name"><?php ms_e( 'apply.band', 'Band / project name' ); ?> *</label>
			<input type="text" id="app_band_name" name="app_band_name" required>
		</div>
		<div class="form-row">
			<label for="app_city"><?php ms_e( 'apply.city', 'City' ); ?></label>
			<input type="text" id="app_city" name="app_city">
		</div>
		<div class="form-row">
			<label for="app_genre"><?php ms_e( 'apply.genre', 'Genre' ); ?></label>
			<input type="text" id="app_genre" name="app_genre">
		</div>
		<div class="form-row">
			<label for="app_pitch"><?php ms_e( 'apply.pitch', 'Short pitch' ); ?></label>
			<textarea id="app_pitch" name="app_pitch" rows="4"></textarea>
		</div>
		<button type="submit" class="btn btn--primary btn--glow"><?php ms_e( 'apply.submit', 'Submit Application' ); ?></button>
	</form>
	<?php
}
