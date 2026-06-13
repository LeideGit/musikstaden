<?php
/**
 * Authentication helpers and login restrictions.
 *
 * @package Musikstaden
 */

declare(strict_types=1);

add_action( 'init', 'musikstaden_disable_registration' );
add_filter( 'authenticate', 'musikstaden_check_user_approved', 30, 3 );
add_filter( 'login_redirect', 'musikstaden_login_redirect', 10, 3 );
add_action( 'admin_post_nopriv_musikstaden_login', 'musikstaden_custom_login' );
add_action( 'admin_post_musikstaden_login', 'musikstaden_custom_login' );

/**
 * Disable public registration.
 */
function musikstaden_disable_registration(): void {
	add_filter( 'option_users_can_register', '__return_false' );
}

/**
 * Block login for unapproved users.
 *
 * @param WP_User|WP_Error|null $user User or error.
 * @param string                $username Username.
 * @param string                $password Password.
 * @return WP_User|WP_Error|null
 */
function musikstaden_check_user_approved( $user, string $username, string $password ) {
	if ( ! $user instanceof WP_User ) {
		return $user;
	}

	if ( user_can( $user, 'manage_options' ) ) {
		return $user;
	}

	$approved = get_user_meta( $user->ID, 'musikstaden_approved', true );
	if ( '1' !== $approved ) {
		return new WP_Error(
			'musikstaden_not_approved',
			__( 'Your account is pending approval. We will email you when approved.', 'musikstaden' )
		);
	}

	return $user;
}

/**
 * Redirect to dashboard after login.
 *
 * @param string  $redirect_to Redirect URL.
 * @param string  $request Requested redirect.
 * @param WP_User $user User object.
 */
function musikstaden_login_redirect( string $redirect_to, string $request, WP_User $user ): string {
	if ( user_can( $user, 'manage_options' ) ) {
		return admin_url();
	}
	return home_url( '/dashboard/' );
}

/**
 * Handle custom login form on /logga-in page.
 */
function musikstaden_custom_login(): void {
	if ( ! isset( $_POST['musikstaden_login_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['musikstaden_login_nonce'] ) ), 'musikstaden_login' ) ) {
		wp_safe_redirect( add_query_arg( 'login', 'error', home_url( '/logga-in/' ) ) );
		exit;
	}

	$email    = sanitize_email( wp_unslash( $_POST['log_email'] ?? '' ) );
	$password = wp_unslash( $_POST['log_password'] ?? '' );
	$remember = ! empty( $_POST['remember'] );

	$user = get_user_by( 'email', $email );
	if ( ! $user ) {
		$user = get_user_by( 'login', $email );
	}

	if ( ! $user ) {
		wp_safe_redirect( add_query_arg( 'login', 'error', home_url( '/logga-in/' ) ) );
		exit;
	}

	$signed = wp_signon(
		array(
			'user_login'    => $user->user_login,
			'user_password' => $password,
			'remember'      => $remember,
		),
		is_ssl()
	);

	if ( is_wp_error( $signed ) ) {
		wp_safe_redirect( add_query_arg( 'login', 'error', home_url( '/logga-in/' ) ) );
		exit;
	}

	wp_safe_redirect( home_url( '/dashboard/' ) );
	exit;
}

/**
 * Render login form.
 */
function musikstaden_render_login_form(): void {
	if ( is_user_logged_in() ) {
		echo '<p>' . esc_html( ms__( 'login.already', 'You are already logged in.' ) ) . ' <a href="' . esc_url( home_url( '/dashboard/' ) ) . '">' . esc_html( ms__( 'login.dashboard', 'Go to dashboard' ) ) . '</a></p>';
		return;
	}

	$error = sanitize_text_field( wp_unslash( $_GET['login'] ?? '' ) );
	if ( 'error' === $error ) {
		echo '<div class="notice notice-error">' . esc_html( ms__( 'login.error', 'Invalid email or password.' ) ) . '</div>';
	}
	?>
	<form class="login-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'musikstaden_login', 'musikstaden_login_nonce' ); ?>
		<input type="hidden" name="action" value="musikstaden_login">
		<div class="form-row">
			<label for="log_email"><?php ms_e( 'login.email', 'Email' ); ?></label>
			<input type="email" id="log_email" name="log_email" required autocomplete="username">
		</div>
		<div class="form-row">
			<label for="log_password"><?php ms_e( 'login.password', 'Password' ); ?></label>
			<input type="password" id="log_password" name="log_password" required autocomplete="current-password">
		</div>
		<div class="form-row form-row--checkbox">
			<label>
				<input type="checkbox" name="remember" value="1">
				<?php ms_e( 'login.remember', 'Remember me' ); ?>
			</label>
		</div>
		<button type="submit" class="btn btn--primary btn--glow"><?php ms_e( 'nav.login', 'Log in' ); ?></button>
		<p class="login-form__help">
			<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php ms_e( 'login.forgot', 'Forgot password?' ); ?></a>
		</p>
	</form>
	<?php
}

/**
 * Require login for dashboard.
 */
add_action( 'template_redirect', 'musikstaden_protect_dashboard' );
function musikstaden_protect_dashboard(): void {
	if ( ! is_page() ) {
		return;
	}
	$slug = get_post_field( 'post_name', get_queried_object_id() );
	if ( 'dashboard' !== $slug ) {
		return;
	}
	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( wp_login_url( home_url( '/dashboard/' ) ) );
		exit;
	}
}
