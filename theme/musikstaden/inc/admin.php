<?php
/**
 * Admin notices and tweaks.
 *
 * @package Musikstaden
 */

declare(strict_types=1);

add_action( 'admin_notices', 'musikstaden_acf_notice' );

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
