<?php
/**
 * ACF field groups (requires Advanced Custom Fields plugin).
 *
 * @package Musikstaden
 */

declare(strict_types=1);

add_action( 'acf/init', 'musikstaden_register_acf_fields' );

/**
 * Register local ACF field groups when ACF is active.
 */
function musikstaden_register_acf_fields(): void {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		array(
			'key'    => 'group_band_details',
			'title'  => 'Bandinformation',
			'fields' => array(
				array(
					'key'   => 'field_biography',
					'label' => 'Biografi',
					'name'  => 'biography',
					'type'  => 'wysiwyg',
					'tabs'  => 'visual',
					'toolbar' => 'basic',
					'media_upload' => 0,
				),
				array(
					'key'          => 'field_booking_email',
					'label'        => 'Boknings-e-post',
					'name'         => 'booking_email',
					'type'         => 'email',
					'instructions' => 'E-postadress för bokningsförfrågningar från artistprofilen. Visas inte publikt. Om tomt används bandägarens kontoe-post.',
					'placeholder'  => 'bokning@band.se',
				),
				array(
					'key'           => 'field_embed_spotify',
					'label'         => 'Spotify',
					'name'          => 'embed_spotify',
					'type'          => 'textarea',
					'rows'          => 6,
					'new_lines'     => '',
					'instructions'  => 'Klistra in Spotify-inbäddningskod (Delas → Bädda in) eller en Spotify-länk. Flera låtar? Separera med en tom rad.',
					'placeholder'   => '<iframe ... spotify.com ...></iframe>',
				),
				array(
					'key'           => 'field_embed_youtube',
					'label'         => 'YouTube',
					'name'          => 'embed_youtube',
					'type'          => 'textarea',
					'rows'          => 6,
					'new_lines'     => '',
					'instructions'  => 'Klistra in YouTube-inbäddningskod (Delas → Bädda in) eller en YouTube-länk. Flera videor? Separera med en tom rad.',
					'placeholder'   => '<iframe ... youtube.com ...></iframe>',
				),
				array(
					'key'   => 'field_social_instagram',
					'label' => 'Instagram URL',
					'name'  => 'social_instagram',
					'type'  => 'url',
				),
				array(
					'key'   => 'field_social_facebook',
					'label' => 'Facebook URL',
					'name'  => 'social_facebook',
					'type'  => 'url',
				),
				array(
					'key'   => 'field_social_spotify',
					'label' => 'Spotify URL',
					'name'  => 'social_spotify',
					'type'  => 'url',
				),
				array(
					'key'   => 'field_social_youtube',
					'label' => 'YouTube URL',
					'name'  => 'social_youtube',
					'type'  => 'url',
				),
				array(
					'key'   => 'field_social_website',
					'label' => 'Website URL',
					'name'  => 'social_website',
					'type'  => 'url',
				),
			),
			'location' => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'band',
					),
				),
			),
		)
	);

	acf_add_local_field_group(
		array(
			'key'    => 'group_application',
			'title'  => 'Application Details',
			'fields' => array(
				array(
					'key'   => 'field_app_email',
					'label' => 'Email',
					'name'  => 'app_email',
					'type'  => 'email',
				),
				array(
					'key'   => 'field_app_band_name',
					'label' => 'Band Name',
					'name'  => 'app_band_name',
					'type'  => 'text',
				),
				array(
					'key'   => 'field_app_city',
					'label' => 'City',
					'name'  => 'app_city',
					'type'  => 'text',
				),
				array(
					'key'   => 'field_app_genre',
					'label' => 'Genre',
					'name'  => 'app_genre',
					'type'  => 'text',
				),
				array(
					'key'   => 'field_app_pitch',
					'label' => 'Pitch',
					'name'  => 'app_pitch',
					'type'  => 'textarea',
				),
				array(
					'key'           => 'field_app_status',
					'label'         => 'Status',
					'name'          => 'app_status',
					'type'          => 'select',
					'choices'       => array(
						'pending'  => 'Pending',
						'approved' => 'Approved',
						'rejected' => 'Rejected',
					),
					'default_value' => 'pending',
				),
			),
			'location' => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'artist_application',
					),
				),
			),
		)
	);
}

/**
 * Fallback meta boxes when ACF is not installed.
 */
add_action( 'add_meta_boxes', 'musikstaden_fallback_meta_boxes' );
function musikstaden_fallback_meta_boxes(): void {
	if ( function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	add_meta_box(
		'musikstaden_band_details',
		'Band Details',
		'musikstaden_render_fallback_band_meta_box',
		'band',
		'normal',
		'high'
	);
}

/**
 * @param WP_Post $post Post object.
 */
function musikstaden_render_fallback_band_meta_box( WP_Post $post ): void {
	wp_nonce_field( 'musikstaden_band_meta', 'musikstaden_band_nonce' );
	$biography      = get_post_meta( $post->ID, 'biography', true );
	$booking_email  = get_post_meta( $post->ID, 'booking_email', true );
	$embed_spotify  = get_post_meta( $post->ID, 'embed_spotify', true );
	$embed_youtube  = get_post_meta( $post->ID, 'embed_youtube', true );
	?>
	<p>
		<label for="biography"><strong>Biografi</strong></label><br>
		<textarea id="biography" name="biography" rows="6" style="width:100%"><?php echo esc_textarea( (string) $biography ); ?></textarea>
	</p>
	<p>
		<label for="booking_email"><strong>Boknings-e-post</strong></label><br>
		<input type="email" id="booking_email" name="booking_email" value="<?php echo esc_attr( (string) $booking_email ); ?>" style="width:100%" placeholder="bokning@band.se">
	</p>
	<p>
		<label for="embed_spotify"><strong>Spotify</strong></label><br>
		<textarea id="embed_spotify" name="embed_spotify" rows="6" style="width:100%" placeholder="Spotify-inbäddningskod"><?php echo esc_textarea( (string) $embed_spotify ); ?></textarea>
	</p>
	<p>
		<label for="embed_youtube"><strong>YouTube</strong></label><br>
		<textarea id="embed_youtube" name="embed_youtube" rows="6" style="width:100%" placeholder="YouTube-inbäddningskod"><?php echo esc_textarea( (string) $embed_youtube ); ?></textarea>
	</p>
	<?php
}

add_action( 'save_post_band', 'musikstaden_save_fallback_band_meta', 10, 2 );
function musikstaden_save_fallback_band_meta( int $post_id, WP_Post $post ): void {
	if ( function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}
	if ( ! isset( $_POST['musikstaden_band_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['musikstaden_band_nonce'] ) ), 'musikstaden_band_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( isset( $_POST['biography'] ) ) {
		update_post_meta( $post_id, 'biography', wp_kses_post( wp_unslash( $_POST['biography'] ) ) );
	}
	if ( isset( $_POST['booking_email'] ) ) {
		update_post_meta( $post_id, 'booking_email', sanitize_email( wp_unslash( $_POST['booking_email'] ) ) );
	}
	if ( isset( $_POST['embed_spotify'] ) ) {
		update_post_meta( $post_id, 'embed_spotify', sanitize_textarea_field( wp_unslash( $_POST['embed_spotify'] ) ) );
	}
	if ( isset( $_POST['embed_youtube'] ) ) {
		update_post_meta( $post_id, 'embed_youtube', sanitize_textarea_field( wp_unslash( $_POST['embed_youtube'] ) ) );
	}
}
