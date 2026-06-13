<?php
/**
 * Band member email invites.
 *
 * @package Musikstaden
 */

declare(strict_types=1);

const MUSIKSTADEN_INVITES_OPTION = 'musikstaden_pending_invites';

add_action( 'admin_post_musikstaden_send_invite', 'musikstaden_send_invite' );
add_action( 'template_redirect', 'musikstaden_handle_invite_accept' );

/**
 * Get pending invites.
 *
 * @return array<string, array{band_id: int, email: string, role: string, token: string, expires: int}>
 */
function musikstaden_get_pending_invites(): array {
	$invites = get_option( MUSIKSTADEN_INVITES_OPTION, array() );
	return is_array( $invites ) ? $invites : array();
}

/**
 * Save pending invites.
 *
 * @param array<string, array{band_id: int, email: string, role: string, token: string, expires: int}> $invites Invites.
 */
function musikstaden_save_pending_invites( array $invites ): void {
	update_option( MUSIKSTADEN_INVITES_OPTION, $invites, false );
}

/**
 * Send band invite email.
 */
function musikstaden_send_invite(): void {
	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( wp_login_url() );
		exit;
	}

	$band_id = (int) ( $_POST['band_id'] ?? 0 );
	$email   = sanitize_email( wp_unslash( $_POST['invite_email'] ?? '' ) );
	$role    = sanitize_text_field( wp_unslash( $_POST['invite_role'] ?? 'member' ) );
	$user_id = get_current_user_id();

	if ( ! in_array( $role, array( 'admin', 'member' ), true ) ) {
		$role = 'member';
	}

	if ( ! $band_id || ! is_email( $email ) || ! musikstaden_user_can_invite_to_band( $user_id, $band_id ) ) {
		wp_safe_redirect( add_query_arg( 'invite', 'error', wp_get_referer() ?: home_url( '/dashboard/' ) ) );
		exit;
	}

	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['musikstaden_invite_nonce'] ?? '' ) ), 'musikstaden_invite' ) ) {
		wp_die( esc_html__( 'Invalid request.', 'musikstaden' ) );
	}

	$invitee = get_user_by( 'email', $email );
	if ( $invitee && musikstaden_get_user_band_role( (int) $invitee->ID, $band_id ) ) {
		wp_safe_redirect( add_query_arg( 'invite', 'exists', wp_get_referer() ?: home_url( '/dashboard/' ) ) );
		exit;
	}

	if ( $invitee && musikstaden_count_user_bands( (int) $invitee->ID ) >= MUSIKSTADEN_MAX_BANDS ) {
		wp_safe_redirect( add_query_arg( 'invite', 'limit', wp_get_referer() ?: home_url( '/dashboard/' ) ) );
		exit;
	}

	$token   = wp_generate_password( 32, false );
	$invites = musikstaden_get_pending_invites();
	$invites[ $token ] = array(
		'band_id' => $band_id,
		'email'   => $email,
		'role'    => $role,
		'token'   => $token,
		'expires' => time() + WEEK_IN_SECONDS,
	);
	musikstaden_save_pending_invites( $invites );

	$band_name   = get_the_title( $band_id );
	$accept_url  = add_query_arg( 'ms_invite', $token, home_url( '/dashboard/' ) );
	$subject     = sprintf( '[Musikstaden] Invitation to join %s', $band_name );
	$message     = sprintf(
		"You have been invited to join \"%s\" on Musikstaden as %s.\n\nAccept invitation:\n%s\n\nThis link expires in 7 days.",
		$band_name,
		$role,
		$accept_url
	);

	wp_mail( $email, $subject, $message );

	wp_safe_redirect( add_query_arg( 'invite', 'sent', wp_get_referer() ?: home_url( '/dashboard/' ) ) );
	exit;
}

/**
 * Accept invite via token.
 */
function musikstaden_handle_invite_accept(): void {
	if ( ! is_user_logged_in() ) {
		return;
	}

	$token = sanitize_text_field( wp_unslash( $_GET['ms_invite'] ?? '' ) );
	if ( ! $token ) {
		return;
	}

	$invites = musikstaden_get_pending_invites();
	if ( ! isset( $invites[ $token ] ) ) {
		wp_safe_redirect( add_query_arg( 'invite', 'invalid', home_url( '/dashboard/' ) ) );
		exit;
	}

	$invite = $invites[ $token ];
	if ( $invite['expires'] < time() ) {
		unset( $invites[ $token ] );
		musikstaden_save_pending_invites( $invites );
		wp_safe_redirect( add_query_arg( 'invite', 'expired', home_url( '/dashboard/' ) ) );
		exit;
	}

	$user    = wp_get_current_user();
	$email   = strtolower( $user->user_email );
	$inv_email = strtolower( $invite['email'] );

	if ( $email !== $inv_email ) {
		wp_safe_redirect( add_query_arg( 'invite', 'wrong_account', home_url( '/dashboard/' ) ) );
		exit;
	}

	if ( musikstaden_count_user_bands( (int) $user->ID ) >= MUSIKSTADEN_MAX_BANDS ) {
		wp_safe_redirect( add_query_arg( 'invite', 'limit', home_url( '/dashboard/' ) ) );
		exit;
	}

	musikstaden_set_user_band_role( (int) $user->ID, (int) $invite['band_id'], $invite['role'] );
	unset( $invites[ $token ] );
	musikstaden_save_pending_invites( $invites );

	wp_safe_redirect( add_query_arg( 'invite', 'accepted', home_url( '/dashboard/' ) ) );
	exit;
}

/**
 * Render invite form for dashboard.
 *
 * @param int $band_id Band post ID.
 */
function musikstaden_render_invite_form( int $band_id ): void {
	if ( ! musikstaden_user_can_invite_to_band( get_current_user_id(), $band_id ) ) {
		return;
	}
	?>
	<form class="invite-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'musikstaden_invite', 'musikstaden_invite_nonce' ); ?>
		<input type="hidden" name="action" value="musikstaden_send_invite">
		<input type="hidden" name="band_id" value="<?php echo esc_attr( (string) $band_id ); ?>">
		<div class="form-row form-row--inline">
			<input type="email" name="invite_email" placeholder="<?php echo esc_attr( ms__( 'invite.email', 'Email address' ) ); ?>" required>
			<select name="invite_role">
				<option value="member"><?php ms_e( 'role.member', 'Member' ); ?></option>
				<option value="admin"><?php ms_e( 'role.admin', 'Admin' ); ?></option>
			</select>
			<button type="submit" class="btn btn--outline btn--sm"><?php ms_e( 'invite.send', 'Send Invite' ); ?></button>
		</div>
	</form>
	<?php
}

/**
 * Get pending invites for current user's email.
 *
 * @return array<int, array{band_id: int, role: string, band_name: string}>
 */
function musikstaden_get_user_pending_invites( int $user_id ): array {
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return array();
	}

	$email   = strtolower( $user->user_email );
	$invites = musikstaden_get_pending_invites();
	$mine    = array();

	foreach ( $invites as $invite ) {
		if ( strtolower( $invite['email'] ) === $email && $invite['expires'] >= time() ) {
			$mine[] = array(
				'band_id'   => (int) $invite['band_id'],
				'role'      => $invite['role'],
				'band_name' => get_the_title( (int) $invite['band_id'] ),
				'token'     => $invite['token'],
			);
		}
	}

	return $mine;
}
