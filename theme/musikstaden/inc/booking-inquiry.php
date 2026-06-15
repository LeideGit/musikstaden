<?php
/**
 * Booking inquiry form on band pages (email relay — no public contact info).
 *
 * @package Musikstaden
 */

declare(strict_types=1);

const MUSIKSTADEN_BOOKING_RATE_BAND = 3;
const MUSIKSTADEN_BOOKING_RATE_IP   = 10;
const MUSIKSTADEN_BOOKING_RATE_TTL  = HOUR_IN_SECONDS;

add_action( 'admin_post_nopriv_musikstaden_booking_inquiry', 'musikstaden_handle_booking_inquiry' );
add_action( 'admin_post_musikstaden_booking_inquiry', 'musikstaden_handle_booking_inquiry' );

/**
 * Whether booking inquiries are enabled on the public band page.
 */
function musikstaden_band_booking_inquiries_enabled( int $band_id ): bool {
	$value = musikstaden_get_field( 'booking_inquiries_enabled', $band_id );
	if ( '' === $value || null === $value ) {
		return true;
	}
	return '1' === (string) $value || filter_var( $value, FILTER_VALIDATE_BOOLEAN );
}

/**
 * Email address that receives booking inquiries for a band.
 * Uses the private booking_email field, then falls back to the band owner's account email.
 */
function musikstaden_get_band_booking_email( int $band_id ): ?string {
	$booking_email = sanitize_email( (string) musikstaden_get_field( 'booking_email', $band_id ) );
	if ( is_email( $booking_email ) ) {
		return $booking_email;
	}

	return musikstaden_get_band_owner_email( $band_id );
}

/**
 * Email address for the band owner (or admin member).
 */
function musikstaden_get_band_owner_email( int $band_id ): ?string {
	$members = musikstaden_get_band_members( $band_id );
	$roles   = array( 'owner', 'admin' );

	foreach ( $roles as $role ) {
		foreach ( $members as $member ) {
			if ( $role !== $member['role'] ) {
				continue;
			}
			$user = get_userdata( $member['user_id'] );
			if ( $user && is_email( $user->user_email ) ) {
				return $user->user_email;
			}
		}
	}

	$post = get_post( $band_id );
	if ( $post ) {
		$user = get_userdata( (int) $post->post_author );
		if ( $user && is_email( $user->user_email ) ) {
			return $user->user_email;
		}
	}

	return null;
}

/**
 * Client IP for rate limiting (best effort behind proxies).
 */
function musikstaden_get_client_ip(): string {
	$candidates = array(
		'HTTP_CF_CONNECTING_IP',
		'HTTP_X_FORWARDED_FOR',
		'REMOTE_ADDR',
	);

	foreach ( $candidates as $key ) {
		if ( empty( $_SERVER[ $key ] ) ) {
			continue;
		}
		$raw = sanitize_text_field( wp_unslash( (string) $_SERVER[ $key ] ) );
		if ( str_contains( $raw, ',' ) ) {
			$raw = trim( explode( ',', $raw )[0] );
		}
		if ( filter_var( $raw, FILTER_VALIDATE_IP ) ) {
			return $raw;
		}
	}

	return '0.0.0.0';
}

/**
 * Format a name + email pair for mail headers.
 */
function musikstaden_format_mailbox( string $name, string $email ): string {
	$name = trim( str_replace( array( '"', "\r", "\n" ), '', $name ) );
	if ( $name !== '' ) {
		return sprintf( '%s <%s>', $name, $email );
	}
	return $email;
}

/**
 * @return array{band: int, ip: int}
 */
function musikstaden_get_booking_rate_counts( int $band_id, string $ip ): array {
	$ip_hash  = wp_hash( $ip );
	$band_key = 'ms_booking_band_' . $band_id . '_' . $ip_hash;
	$ip_key   = 'ms_booking_ip_' . $ip_hash;

	return array(
		'band' => (int) get_transient( $band_key ),
		'ip'   => (int) get_transient( $ip_key ),
	);
}

/**
 * Increment rate-limit counters after a successful send.
 */
function musikstaden_bump_booking_rate_counts( int $band_id, string $ip ): void {
	$ip_hash  = wp_hash( $ip );
	$band_key = 'ms_booking_band_' . $band_id . '_' . $ip_hash;
	$ip_key   = 'ms_booking_ip_' . $ip_hash;
	$counts   = musikstaden_get_booking_rate_counts( $band_id, $ip );

	set_transient( $band_key, $counts['band'] + 1, MUSIKSTADEN_BOOKING_RATE_TTL );
	set_transient( $ip_key, $counts['ip'] + 1, MUSIKSTADEN_BOOKING_RATE_TTL );
}

/**
 * Handle booking inquiry form submission.
 */
function musikstaden_handle_booking_inquiry(): void {
	$band_id  = absint( $_POST['band_id'] ?? 0 );
	$redirect = $band_id ? get_permalink( $band_id ) : home_url( '/sok/' );
	$redirect = is_string( $redirect ) ? $redirect : home_url( '/sok/' );

	if ( ! isset( $_POST['musikstaden_booking_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['musikstaden_booking_nonce'] ) ), 'musikstaden_booking_' . $band_id ) ) {
		wp_safe_redirect( add_query_arg( 'booking', 'error', $redirect ) );
		exit;
	}

	$band = get_post( $band_id );
	if ( ! $band || 'band' !== $band->post_type || 'publish' !== $band->post_status ) {
		wp_safe_redirect( add_query_arg( 'booking', 'error', $redirect ) );
		exit;
	}

	if ( ! musikstaden_band_booking_inquiries_enabled( $band_id ) ) {
		wp_safe_redirect( add_query_arg( 'booking', 'unavailable', $redirect ) );
		exit;
	}

	// Honeypot — pretend success for bots.
	if ( ! empty( $_POST['inquiry_website'] ) ) {
		wp_safe_redirect( add_query_arg( 'booking', 'success', $redirect ) );
		exit;
	}

	$name    = sanitize_text_field( wp_unslash( $_POST['inquiry_name'] ?? '' ) );
	$email   = sanitize_email( wp_unslash( $_POST['inquiry_email'] ?? '' ) );
	$event   = sanitize_text_field( wp_unslash( $_POST['inquiry_event'] ?? '' ) );
	$message = sanitize_textarea_field( wp_unslash( $_POST['inquiry_message'] ?? '' ) );

	if ( ! $name || ! is_email( $email ) || ! $message || strlen( $message ) > 2000 ) {
		wp_safe_redirect( add_query_arg( 'booking', 'error', $redirect ) );
		exit;
	}

	$band_email = musikstaden_get_band_booking_email( $band_id );
	if ( ! $band_email ) {
		wp_safe_redirect( add_query_arg( 'booking', 'unavailable', $redirect ) );
		exit;
	}

	$ip     = musikstaden_get_client_ip();
	$counts = musikstaden_get_booking_rate_counts( $band_id, $ip );
	if ( $counts['band'] >= MUSIKSTADEN_BOOKING_RATE_BAND || $counts['ip'] >= MUSIKSTADEN_BOOKING_RATE_IP ) {
		wp_safe_redirect( add_query_arg( 'booking', 'rate', $redirect ) );
		exit;
	}

	$band_name = get_the_title( $band_id );
	$site      = get_bloginfo( 'name' );
	$profile   = get_permalink( $band_id );
	$reply_to  = musikstaden_format_mailbox( $name, $email );

	$subject = sprintf(
		/* translators: 1: band name, 2: site name */
		__( '[%2$s] Bokningsförfrågan för %1$s', 'musikstaden' ),
		$band_name,
		$site
	);

	$lines = array(
		__( 'Du har fått en bokningsförfrågan via Musikstaden.', 'musikstaden' ),
		'',
		sprintf( __( 'Band: %s', 'musikstaden' ), $band_name ),
		sprintf( __( 'Profil: %s', 'musikstaden' ), $profile ),
		'',
		sprintf( __( 'Från: %1$s', 'musikstaden' ), $name ),
		sprintf( __( 'E-post att svara till: %s', 'musikstaden' ), $email ),
	);

	if ( $event ) {
		$lines[] = sprintf( __( 'Event / datum: %s', 'musikstaden' ), $event );
	}

	$lines[] = '';
	$lines[] = __( 'Meddelande:', 'musikstaden' );
	$lines[] = $message;
	$lines[] = '';
	$lines[] = __( 'Klicka Svara/Reply i din e-postklient — ditt svar skickas direkt till avsändarens adress ovan.', 'musikstaden' );

	$from_email = get_option( 'admin_email' );
	if ( ! is_email( $from_email ) ) {
		$from_email = 'hello@musikstaden.se';
	}

	$band_headers = array(
		'Content-Type: text/plain; charset=UTF-8',
		'Reply-To: ' . $reply_to,
		'From: ' . $site . ' <' . $from_email . '>',
	);

	$sent = wp_mail( $band_email, $subject, implode( "\n", $lines ), $band_headers );

	if ( ! $sent ) {
		wp_safe_redirect( add_query_arg( 'booking', 'error', $redirect ) );
		exit;
	}

	$booker_subject = sprintf(
		/* translators: 1: site name, 2: band name */
		__( '[%1$s] Din bokningsförfrågan till %2$s', 'musikstaden' ),
		$site,
		$band_name
	);

	$booker_lines = array(
		sprintf( __( 'Hej %s,', 'musikstaden' ), $name ),
		'',
		sprintf(
			/* translators: %s: band name */
			__( 'Vi har skickat din bokningsförfrågan till %s.', 'musikstaden' ),
			$band_name
		),
		'',
		__( 'Kopia av ditt meddelande:', 'musikstaden' ),
		'',
	);

	if ( $event ) {
		$booker_lines[] = sprintf( __( 'Event / datum: %s', 'musikstaden' ), $event );
	}

	$booker_lines[] = $message;
	$booker_lines[] = '';
	$booker_lines[] = __( 'Artistens svar kommer till den här e-postadressen.', 'musikstaden' );

	wp_mail(
		$email,
		$booker_subject,
		implode( "\n", $booker_lines ),
		array( 'Content-Type: text/plain; charset=UTF-8' )
	);

	musikstaden_bump_booking_rate_counts( $band_id, $ip );
	wp_safe_redirect( add_query_arg( 'booking', 'success', $redirect ) );
	exit;
}

/**
 * Render booking inquiry form on a band profile.
 */
function musikstaden_render_booking_form( int $band_id ): void {
	if ( 'publish' !== get_post_status( $band_id ) ) {
		return;
	}

	if ( ! musikstaden_band_booking_inquiries_enabled( $band_id ) ) {
		return;
	}

	$band_email = musikstaden_get_band_booking_email( $band_id );
	$status     = sanitize_text_field( wp_unslash( $_GET['booking'] ?? '' ) );
	$expanded   = in_array( $status, array( 'success', 'error', 'rate', 'unavailable' ), true );
	?>
	<section id="booking" class="band-footer-block band-booking">
		<button
			type="button"
			class="band-booking__toggle"
			aria-expanded="<?php echo $expanded ? 'true' : 'false'; ?>"
			aria-controls="booking-panel"
			id="booking-toggle"
		>
			<span class="band-booking__toggle-label"><?php ms_e( 'band.booking', 'Bokning' ); ?></span>
			<span class="band-booking__toggle-hint"><?php ms_e( 'booking.toggle_hint', 'Skicka bokningsförfrågan' ); ?></span>
			<span class="band-booking__toggle-icon" aria-hidden="true"></span>
		</button>

		<div
			id="booking-panel"
			class="band-booking__panel"
			role="region"
			aria-labelledby="booking-toggle"
			<?php echo $expanded ? '' : 'hidden'; ?>
		>
			<p class="band-booking__intro"><?php ms_e( 'band.booking_sub', 'Skicka en bokningsförfrågan — artistens kontaktuppgifter visas inte publikt.' ); ?></p>

			<?php if ( 'success' === $status ) : ?>
				<div class="notice notice-success"><?php ms_e( 'booking.success', 'Din förfrågan har skickats! Artisten svarar till din e-postadress.' ); ?></div>
			<?php elseif ( 'error' === $status ) : ?>
				<div class="notice notice-error"><?php ms_e( 'booking.error', 'Kunde inte skicka förfrågan. Kontrollera fälten och försök igen.' ); ?></div>
			<?php elseif ( 'rate' === $status ) : ?>
				<div class="notice notice-error"><?php ms_e( 'booking.rate', 'För många förfrågningar. Vänta en stund och försök igen.' ); ?></div>
			<?php elseif ( 'unavailable' === $status ) : ?>
				<div class="notice notice-error"><?php ms_e( 'booking.unavailable', 'Bokningsförfrågningar är inte tillgängliga för den här artisten just nu.' ); ?></div>
			<?php endif; ?>

			<?php if ( ! $band_email ) : ?>
				<p class="booking-form__unavailable"><?php ms_e( 'booking.closed', 'Bokningsförfrågningar är inte tillgängliga för den här artisten just nu.' ); ?></p>
			<?php else : ?>
				<form class="booking-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'musikstaden_booking_' . $band_id, 'musikstaden_booking_nonce' ); ?>
					<input type="hidden" name="action" value="musikstaden_booking_inquiry">
					<input type="hidden" name="band_id" value="<?php echo esc_attr( (string) $band_id ); ?>">

					<div class="form-row booking-form__honeypot" aria-hidden="true">
						<label for="inquiry_website"><?php esc_html_e( 'Website', 'musikstaden' ); ?></label>
						<input type="text" id="inquiry_website" name="inquiry_website" tabindex="-1" autocomplete="off">
					</div>

					<div class="form-row">
						<label for="inquiry_name"><?php ms_e( 'booking.name', 'Ditt namn' ); ?> *</label>
						<input type="text" id="inquiry_name" name="inquiry_name" required maxlength="120" autocomplete="name">
					</div>
					<div class="form-row">
						<label for="inquiry_email"><?php ms_e( 'booking.email', 'Din e-post' ); ?> *</label>
						<input type="email" id="inquiry_email" name="inquiry_email" required maxlength="120" autocomplete="email">
					</div>
					<div class="form-row">
						<label for="inquiry_event"><?php ms_e( 'booking.event', 'Event / datum' ); ?></label>
						<input type="text" id="inquiry_event" name="inquiry_event" maxlength="160" placeholder="<?php echo esc_attr( ms__( 'booking.event_placeholder', 't.ex. Bröllop 15 augusti, Stockholm' ) ); ?>">
					</div>
					<div class="form-row">
						<label for="inquiry_message"><?php ms_e( 'booking.message', 'Meddelande' ); ?> *</label>
						<textarea id="inquiry_message" name="inquiry_message" rows="5" required maxlength="2000"></textarea>
					</div>
					<button type="submit" class="btn btn--primary btn--glow"><?php ms_e( 'booking.submit', 'Skicka bokningsförfrågan' ); ?></button>
				</form>
			<?php endif; ?>
		</div>
	</section>
	<?php
}
