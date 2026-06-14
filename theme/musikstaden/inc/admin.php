<?php
/**
 * Admin notices and tweaks.
 *
 * @package Musikstaden
 */

declare(strict_types=1);

add_action( 'admin_notices', 'musikstaden_acf_notice' );
add_action( 'admin_notices', 'musikstaden_band_edit_help_notice' );
add_action( 'admin_notices', 'musikstaden_application_admin_notices' );
add_action( 'admin_notices', 'musikstaden_band_admin_notices' );
add_filter( 'theme_row_meta', 'musikstaden_theme_row_meta', 10, 3 );
add_filter( 'admin_footer_text', 'musikstaden_admin_footer_version' );

/**
 * Show release name under the theme on Appearance → Themes.
 *
 * @param string[] $links Row meta links.
 */
function musikstaden_theme_row_meta( array $links, string $stylesheet, $theme ): array {
	if ( 'musikstaden' !== $stylesheet ) {
		return $links;
	}

	$links[] = sprintf(
		'<strong>%s</strong> <code>%s</code>',
		esc_html( MUSIKSTADEN_VERSION_NAME ),
		esc_html( MUSIKSTADEN_VERSION )
	);

	return $links;
}

/**
 * Show active theme version in WP Admin footer (admins only).
 */
function musikstaden_admin_footer_version( string $text ): string {
	if ( ! current_user_can( 'manage_options' ) ) {
		return $text;
	}

	$active = wp_get_theme();
	if ( 'musikstaden' !== $active->get_stylesheet() ) {
		return $text;
	}

	return sprintf(
		/* translators: %s: theme version label */
		__( 'Musikstaden theme %s', 'musikstaden' ),
		musikstaden_version_label()
	);
}

/**
 * Notices on the Bands list after creating from an application.
 */
function musikstaden_band_admin_notices(): void {
	if ( empty( $_GET['band_created'] ) ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'edit-band' !== $screen->id ) {
		return;
	}

	$band_id = (int) ( $_GET['band_id'] ?? 0 );
	$message = __( 'Band created as a draft. Open it, add details, then click Publish.', 'musikstaden' );
	if ( $band_id ) {
		$message .= ' <a href="' . esc_url( get_edit_post_link( $band_id ) ) . '">' . esc_html__( 'Edit band now', 'musikstaden' ) . '</a>';
	}

	echo '<div class="notice notice-success is-dismissible"><p>' . wp_kses_post( $message ) . '</p></div>';
}

/**
 * Help artists find the media embed field on the band editor.
 */
function musikstaden_band_edit_help_notice(): void {
	if ( ! function_exists( 'get_field' ) ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'band' !== $screen->post_type || ! in_array( $screen->base, array( 'post', 'post-new' ), true ) ) {
		return;
	}

	$sync_url = admin_url( 'edit.php?post_type=acf-field-group' );
	?>
	<div class="notice notice-info is-dismissible">
		<p>
			<strong>Musikstaden:</strong>
			<?php esc_html_e( 'Scroll to Bandinformation → Spotify and YouTube. Paste embed code in the matching field (Share → Embed).', 'musikstaden' ); ?>
			<?php
			printf(
				' %s',
				sprintf(
					/* translators: %s: ACF field groups admin URL */
					__( 'If you do not see that field, update the theme and sync field groups under %s.', 'musikstaden' ),
					'<a href="' . esc_url( $sync_url ) . '">Custom Fields</a>'
				)
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Show feedback after approve/reject actions.
 */
function musikstaden_application_admin_notices(): void {
	if ( ! empty( $_GET['approved'] ) ) {
		$band_id = (int) ( $_GET['band_id'] ?? 0 );
		$message = __( 'Application approved. User account created.', 'musikstaden' );
		if ( $band_id ) {
			$message .= ' ';
			$message .= sprintf(
				/* translators: %s: edit band link */
				__( 'Draft band created: %s', 'musikstaden' ),
				'<a href="' . esc_url( get_edit_post_link( $band_id ) ) . '">' . esc_html( get_the_title( $band_id ) ) . '</a>'
			);
		}
		echo '<div class="notice notice-success is-dismissible"><p>' . wp_kses_post( $message ) . '</p></div>';
	}
	if ( ! empty( $_GET['rejected'] ) ) {
		echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__( 'Application rejected.', 'musikstaden' ) . '</p></div>';
	}
	if ( isset( $_GET['email_resent'] ) ) {
		$sent = '1' === $_GET['email_resent'];
		echo '<div class="notice notice-' . ( $sent ? 'success' : 'error' ) . ' is-dismissible"><p>';
		echo esc_html(
			$sent
				? __( 'Welcome email sent.', 'musikstaden' )
				: __( 'Could not send welcome email. Check WP Mail SMTP.', 'musikstaden' )
		);
		echo '</p></div>';
	}
}

/**
 * Prompt to install ACF if missing.
 */
function musikstaden_acf_notice(): void {
	if ( function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="notice notice-warning">
		<p>
			<strong>Musikstaden:</strong>
			<?php esc_html_e( 'Install the free Advanced Custom Fields plugin for band embeds and social links.', 'musikstaden' ); ?>
			<a href="<?php echo esc_url( admin_url( 'plugin-install.php?s=advanced+custom+fields&tab=search' ) ); ?>">
				<?php esc_html_e( 'Install now', 'musikstaden' ); ?>
			</a>
		</p>
	</div>
	<?php
}

/**
 * Hide band CPT from menu for users with no bands and no create permission.
 */
add_action( 'admin_menu', 'musikstaden_trim_admin_menu', 999 );
function musikstaden_trim_admin_menu(): void {
	if ( current_user_can( 'manage_options' ) ) {
		return;
	}
	// Artists only need Bands + Profile — remove Posts, Comments, etc.
	remove_menu_page( 'edit.php' );
	remove_menu_page( 'edit-comments.php' );
}
