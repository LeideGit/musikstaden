<?php
/**
 * Frontend band editor (Band Studio) — no WordPress admin for artists.
 *
 * @package Musikstaden
 */

declare(strict_types=1);

/** @var list<string> */
const MUSIKSTADEN_ARTIST_PAGE_SLUGS = array( 'dashboard', 'redigera-band', 'nytt-band' );

add_action( 'init', 'musikstaden_ensure_studio_pages', 25 );
add_action( 'admin_post_musikstaden_save_band', 'musikstaden_handle_band_studio_save' );
add_action( 'admin_post_musikstaden_delete_band', 'musikstaden_handle_band_studio_delete' );
add_action( 'load-post.php', 'musikstaden_redirect_band_admin_to_studio' );
add_action( 'load-post-new.php', 'musikstaden_redirect_band_create_to_studio' );
add_action( 'admin_init', 'musikstaden_redirect_artists_from_wp_admin' );
add_filter( 'show_admin_bar', 'musikstaden_hide_admin_bar_for_artists' );
add_action( 'wp_enqueue_scripts', 'musikstaden_enqueue_studio_assets' );

/**
 * Edit URL for a band in Band Studio.
 */
function musikstaden_band_edit_url( int $band_id ): string {
	return add_query_arg( 'band', $band_id, home_url( '/redigera-band/' ) );
}

/**
 * Create URL for a new band.
 */
function musikstaden_band_create_url(): string {
	return home_url( '/nytt-band/' );
}

/**
 * Dashboard URL with optional notice query arg.
 */
function musikstaden_dashboard_url( string $notice = '' ): string {
	$url = home_url( '/dashboard/' );
	return $notice ? add_query_arg( 'studio', $notice, $url ) : $url;
}

/**
 * Create studio pages on existing installs (seed handles fresh installs).
 */
function musikstaden_ensure_studio_pages(): void {
	if ( get_option( 'musikstaden_studio_pages_v1' ) ) {
		return;
	}

	$pages = array(
		array(
			'title'    => 'Redigera band',
			'slug'     => 'redigera-band',
			'template' => 'page-band-studio.php',
		),
		array(
			'title'    => 'Nytt band',
			'slug'     => 'nytt-band',
			'template' => 'page-band-studio.php',
		),
	);

	foreach ( $pages as $page ) {
		if ( get_page_by_path( $page['slug'] ) ) {
			continue;
		}
		$id = wp_insert_post(
			array(
				'post_title'  => $page['title'],
				'post_name'   => $page['slug'],
				'post_status' => 'publish',
				'post_type'   => 'page',
			)
		);
		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, '_wp_page_template', $page['template'] );
		}
	}

	update_option( 'musikstaden_studio_pages_v1', 1 );
}

/**
 * Enqueue editor styles on studio pages.
 */
function musikstaden_enqueue_studio_assets(): void {
	if ( ! is_page( array( 'redigera-band', 'nytt-band' ) ) ) {
		return;
	}
	wp_enqueue_editor();
}

/**
 * Redirect band edit in wp-admin to Band Studio for non-admins.
 */
function musikstaden_redirect_band_admin_to_studio(): void {
	if ( ! isset( $_GET['post'] ) ) {
		return;
	}
	$post_id = (int) $_GET['post'];
	if ( 'band' !== get_post_type( $post_id ) ) {
		return;
	}
	if ( current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! musikstaden_user_can_edit_band( get_current_user_id(), $post_id ) ) {
		wp_die( esc_html__( 'You do not have permission to edit this band.', 'musikstaden' ) );
	}
	wp_safe_redirect( musikstaden_band_edit_url( $post_id ) );
	exit;
}

/**
 * Redirect band create in wp-admin to Band Studio for non-admins.
 */
function musikstaden_redirect_band_create_to_studio(): void {
	if ( ! isset( $_GET['post_type'] ) || 'band' !== $_GET['post_type'] ) {
		return;
	}
	if ( current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! musikstaden_user_can_create_band( get_current_user_id() ) ) {
		wp_die( esc_html__( 'You have reached the maximum number of bands (5).', 'musikstaden' ) );
	}
	wp_safe_redirect( musikstaden_band_create_url() );
	exit;
}

/**
 * Keep artists on the public site — no wp-admin dashboard.
 */
function musikstaden_redirect_artists_from_wp_admin(): void {
	if ( current_user_can( 'manage_options' ) || ! is_user_logged_in() ) {
		return;
	}
	if ( wp_doing_ajax() ) {
		return;
	}

	global $pagenow;
	$allowed = array( 'admin-ajax.php', 'async-upload.php', 'admin-post.php' );
	if ( in_array( $pagenow, $allowed, true ) ) {
		return;
	}

	wp_safe_redirect( musikstaden_dashboard_url() );
	exit;
}

/**
 * Hide admin bar for artist role on the frontend.
 *
 * @param bool $show Whether to show the admin bar.
 */
function musikstaden_hide_admin_bar_for_artists( bool $show ): bool {
	if ( is_admin() ) {
		return $show;
	}
	if ( is_user_logged_in() && ! current_user_can( 'manage_options' ) ) {
		return false;
	}
	return $show;
}

/**
 * Whether the current user is a logged-in artist (non-admin).
 */
function musikstaden_is_artist_user(): bool {
	return is_user_logged_in() && ! current_user_can( 'manage_options' );
}

/**
 * Update a band ACF/meta field.
 *
 * @param mixed $value Field value.
 */
function musikstaden_update_band_field( string $key, $value, int $post_id ): void {
	if ( function_exists( 'update_field' ) ) {
		update_field( $key, $value, $post_id );
		return;
	}
	update_post_meta( $post_id, $key, $value );
}

/**
 * Completion checklist for a band profile.
 *
 * @return array<string, array{label: string, done: bool, required: bool}>
 */
function musikstaden_get_band_studio_checklist( int $band_id ): array {
	$band = get_post( $band_id );
	if ( ! $band || 'band' !== $band->post_type ) {
		return array();
	}

	$city   = wp_get_post_terms( $band_id, 'city', array( 'fields' => 'ids' ) );
	$genres = wp_get_post_terms( $band_id, 'genre', array( 'fields' => 'ids' ) );
	$bio    = trim( wp_strip_all_tags( (string) musikstaden_get_field( 'biography', $band_id ) ) );

	return array(
		'name'    => array(
			'label'    => ms__( 'studio.check_name', 'Bandnamn' ),
			'done'     => '' !== trim( $band->post_title ),
			'required' => true,
		),
		'hero'    => array(
			'label'    => ms__( 'studio.check_hero', 'Profilbild (hero)' ),
			'done'     => (bool) get_post_thumbnail_id( $band_id ),
			'required' => true,
		),
		'city'    => array(
			'label'    => ms__( 'studio.check_city', 'Stad' ),
			'done'     => ! empty( $city ) && ! is_wp_error( $city ),
			'required' => true,
		),
		'genre'   => array(
			'label'    => ms__( 'studio.check_genre', 'Minst en genre' ),
			'done'     => ! empty( $genres ) && ! is_wp_error( $genres ),
			'required' => true,
		),
		'bio'     => array(
			'label'    => ms__( 'studio.check_bio', 'Biografi' ),
			'done'     => '' !== $bio,
			'required' => true,
		),
		'booking' => array(
			'label'    => ms__( 'studio.check_booking', 'Boknings-e-post' ),
			'done'     => is_email( (string) musikstaden_get_field( 'booking_email', $band_id ) ),
			'required' => false,
		),
	);
}

/**
 * Whether all required checklist items are complete.
 */
function musikstaden_band_studio_can_publish( int $band_id ): bool {
	foreach ( musikstaden_get_band_studio_checklist( $band_id ) as $item ) {
		if ( ! empty( $item['required'] ) && empty( $item['done'] ) ) {
			return false;
		}
	}
	return true;
}

/**
 * Handle hero image upload; returns attachment ID, 0 if unchanged, or WP_Error.
 *
 * @return int|WP_Error
 */
function musikstaden_studio_handle_hero_upload( int $band_id, int $user_id ) {
	if ( ! empty( $_POST['remove_hero'] ) ) {
		delete_post_thumbnail( $band_id );
		return 0;
	}

	if ( empty( $_FILES['hero_image']['name'] ) ) {
		return 0;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$file = $_FILES['hero_image'];
	if ( ! empty( $file['error'] ) ) {
		return new WP_Error( 'upload', ms__( 'studio.error_upload', 'Kunde inte ladda upp bilden.' ) );
	}

	$allowed = array( 'image/jpeg', 'image/png', 'image/webp' );
	$type    = wp_check_filetype( $file['name'] );
	if ( ! in_array( $type['type'], $allowed, true ) ) {
		return new WP_Error( 'upload_type', ms__( 'studio.error_upload_type', 'Endast JPG, PNG eller WebP.' ) );
	}

	$upload = wp_handle_upload( $file, array( 'test_form' => false ) );
	if ( isset( $upload['error'] ) ) {
		return new WP_Error( 'upload', $upload['error'] );
	}

	$attachment = array(
		'post_mime_type' => $upload['type'],
		'post_title'     => sanitize_file_name( pathinfo( $file['name'], PATHINFO_FILENAME ) ),
		'post_content'   => '',
		'post_status'    => 'inherit',
		'post_author'    => $user_id,
	);
	$attach_id  = wp_insert_attachment( $attachment, $upload['file'], $band_id );
	if ( is_wp_error( $attach_id ) ) {
		return $attach_id;
	}

	$meta = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
	wp_update_attachment_metadata( $attach_id, $meta );
	set_post_thumbnail( $band_id, $attach_id );

	return (int) $attach_id;
}

/**
 * Save band from Band Studio form.
 */
function musikstaden_handle_band_studio_save(): void {
	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( home_url( '/logga-in/' ) );
		exit;
	}

	if ( ! isset( $_POST['musikstaden_studio_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['musikstaden_studio_nonce'] ) ), 'musikstaden_studio_save' ) ) {
		wp_safe_redirect( musikstaden_dashboard_url( 'error' ) );
		exit;
	}

	$user_id   = get_current_user_id();
	$band_id   = (int) ( $_POST['band_id'] ?? 0 );
	$is_create = 0 === $band_id;
	$intent    = sanitize_key( wp_unslash( $_POST['studio_intent'] ?? 'draft' ) );

	if ( $is_create ) {
		if ( ! musikstaden_user_can_create_band( $user_id ) ) {
			wp_safe_redirect( musikstaden_dashboard_url( 'limit' ) );
			exit;
		}
	} elseif ( ! musikstaden_user_can_edit_band( $user_id, $band_id ) ) {
		wp_safe_redirect( musikstaden_dashboard_url( 'error' ) );
		exit;
	}

	$title = sanitize_text_field( wp_unslash( $_POST['band_title'] ?? '' ) );
	if ( '' === $title ) {
		wp_safe_redirect( add_query_arg( 'studio', 'missing_name', wp_get_referer() ?: musikstaden_band_create_url() ) );
		exit;
	}

	if ( $is_create ) {
		$band_id = wp_insert_post(
			array(
				'post_type'   => 'band',
				'post_title'  => $title,
				'post_status' => 'draft',
				'post_author' => $user_id,
			),
			true
		);
		if ( is_wp_error( $band_id ) ) {
			wp_safe_redirect( musikstaden_dashboard_url( 'error' ) );
			exit;
		}
		musikstaden_set_user_band_role( $user_id, (int) $band_id, 'owner' );
	} else {
		wp_update_post(
			array(
				'ID'         => $band_id,
				'post_title' => $title,
			)
		);
	}

	$upload_result = musikstaden_studio_handle_hero_upload( $band_id, $user_id );
	if ( is_wp_error( $upload_result ) ) {
		wp_safe_redirect( add_query_arg( 'studio', 'upload', musikstaden_band_edit_url( $band_id ) ) );
		exit;
	}

	$city_slug = sanitize_title( wp_unslash( $_POST['band_city'] ?? '' ) );
	if ( $city_slug ) {
		wp_set_object_terms( $band_id, $city_slug, 'city' );
	} else {
		wp_set_object_terms( $band_id, array(), 'city' );
	}

	$genre_slugs = array_map( 'sanitize_title', (array) ( $_POST['band_genres'] ?? array() ) );
	wp_set_object_terms( $band_id, array_filter( $genre_slugs ), 'genre' );

	$gig_slugs = array_map( 'sanitize_title', (array) ( $_POST['band_gig_types'] ?? array() ) );
	wp_set_object_terms( $band_id, array_filter( $gig_slugs ), 'gig_type' );

	musikstaden_update_band_field( 'biography', wp_kses_post( wp_unslash( $_POST['biography'] ?? '' ) ), $band_id );
	musikstaden_update_band_field( 'booking_email', sanitize_email( wp_unslash( $_POST['booking_email'] ?? '' ) ), $band_id );
	musikstaden_update_band_field( 'embed_spotify', sanitize_textarea_field( wp_unslash( $_POST['embed_spotify'] ?? '' ) ), $band_id );
	musikstaden_update_band_field( 'embed_youtube', sanitize_textarea_field( wp_unslash( $_POST['embed_youtube'] ?? '' ) ), $band_id );

	$social_fields = array( 'social_instagram', 'social_facebook', 'social_spotify', 'social_youtube', 'social_website' );
	foreach ( $social_fields as $field ) {
		$url = esc_url_raw( wp_unslash( $_POST[ $field ] ?? '' ) );
		musikstaden_update_band_field( $field, $url, $band_id );
	}

	$new_status = 'draft';
	if ( 'publish' === $intent ) {
		if ( musikstaden_band_studio_can_publish( $band_id ) ) {
			$new_status = 'publish';
		} else {
			wp_safe_redirect( add_query_arg( 'studio', 'incomplete', musikstaden_band_edit_url( $band_id ) ) );
			exit;
		}
	} elseif ( 'unpublish' === $intent ) {
		$new_status = 'draft';
	}

	wp_update_post(
		array(
			'ID'          => $band_id,
			'post_status' => $new_status,
		)
	);

	$notice = 'publish' === $new_status ? 'published' : ( 'unpublish' === $intent ? 'unpublished' : 'saved' );
	wp_safe_redirect( add_query_arg( 'studio', $notice, musikstaden_band_edit_url( $band_id ) ) );
	exit;
}

/**
 * Delete (trash) a band — owners only.
 */
function musikstaden_handle_band_studio_delete(): void {
	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( home_url( '/logga-in/' ) );
		exit;
	}

	if ( ! isset( $_POST['musikstaden_studio_delete_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['musikstaden_studio_delete_nonce'] ) ), 'musikstaden_studio_delete' ) ) {
		wp_safe_redirect( musikstaden_dashboard_url( 'error' ) );
		exit;
	}

	$band_id = (int) ( $_POST['band_id'] ?? 0 );
	if ( ! $band_id || 'band' !== get_post_type( $band_id ) ) {
		wp_safe_redirect( musikstaden_dashboard_url( 'error' ) );
		exit;
	}

	if ( ! musikstaden_user_can_delete_band( get_current_user_id(), $band_id ) ) {
		wp_safe_redirect( musikstaden_dashboard_url( 'error' ) );
		exit;
	}

	wp_trash_post( $band_id );
	wp_safe_redirect( musikstaden_dashboard_url( 'deleted' ) );
	exit;
}

/**
 * Render the Band Studio form.
 */
function musikstaden_render_band_studio_form( int $band_id, bool $is_create ): void {
	$band       = $is_create ? null : get_post( $band_id );
	$checklist  = $is_create ? array() : musikstaden_get_band_studio_checklist( $band_id );
	$can_pub    = $is_create ? false : musikstaden_band_studio_can_publish( $band_id );
	$status     = $band ? $band->post_status : 'draft';
	$hero_id    = $band ? (int) get_post_thumbnail_id( $band_id ) : 0;
	$hero_url   = $hero_id ? wp_get_attachment_image_url( $hero_id, 'band-hero' ) : '';
	$city_terms = $band ? wp_get_post_terms( $band_id, 'city', array( 'fields' => 'slugs' ) ) : array();
	$genre_terms = $band ? wp_get_post_terms( $band_id, 'genre', array( 'fields' => 'slugs' ) ) : array();
	$gig_terms  = $band ? wp_get_post_terms( $band_id, 'gig_type', array( 'fields' => 'slugs' ) ) : array();
	$notice     = sanitize_key( wp_unslash( $_GET['studio'] ?? '' ) );

	$notice_messages = array(
		'saved'       => ms__( 'studio.notice_saved', 'Ändringar sparade.' ),
		'published'   => ms__( 'studio.notice_published', 'Bandet är publicerat och synligt i sökningen!' ),
		'unpublished' => ms__( 'studio.notice_unpublished', 'Bandet är avpublicerat (utkast).' ),
		'incomplete'  => ms__( 'studio.notice_incomplete', 'Fyll i alla obligatoriska fält innan publicering.' ),
		'upload'      => ms__( 'studio.error_upload', 'Kunde inte ladda upp bilden.' ),
		'missing_name' => ms__( 'studio.error_name', 'Ange ett bandnamn.' ),
		'error'       => ms__( 'studio.error_generic', 'Något gick fel. Försök igen.' ),
	);
	?>
	<section class="band-studio section">
		<div class="container">
			<header class="band-studio__header">
				<div>
					<p class="band-studio__back">
						<a href="<?php echo esc_url( musikstaden_dashboard_url() ); ?>">&larr; <?php ms_e( 'studio.back', 'Tillbaka till panelen' ); ?></a>
					</p>
					<h1>
						<?php
						if ( $is_create ) {
							ms_e( 'studio.create_title', 'Skapa nytt band' );
						} else {
							echo esc_html(
								sprintf(
									ms__( 'studio.edit_title', 'Redigera %s' ),
									$band ? $band->post_title : ''
								)
							);
						}
						?>
					</h1>
					<?php if ( ! $is_create && $band ) : ?>
						<p class="band-studio__status">
							<span class="tag tag--status tag--status-<?php echo esc_attr( $status ); ?>">
								<?php
								echo 'publish' === $status
									? esc_html( ms__( 'studio.status_live', 'Publicerad' ) )
									: esc_html( ms__( 'studio.status_draft', 'Utkast' ) );
								?>
							</span>
							<?php if ( 'publish' === $status ) : ?>
								<a href="<?php echo esc_url( musikstaden_band_url( $band ) ); ?>" class="band-studio__preview-link" target="_blank" rel="noopener">
									<?php ms_e( 'studio.view_live', 'Visa live-sida' ); ?> ↗
								</a>
							<?php endif; ?>
						</p>
					<?php endif; ?>
				</div>
			</header>

			<?php if ( $notice && isset( $notice_messages[ $notice ] ) ) : ?>
				<div class="notice notice-<?php echo in_array( $notice, array( 'saved', 'published', 'unpublished' ), true ) ? 'success' : 'error'; ?>">
					<?php echo esc_html( $notice_messages[ $notice ] ); ?>
				</div>
			<?php endif; ?>

			<div class="band-studio__layout">
				<?php if ( ! $is_create && ! empty( $checklist ) ) : ?>
				<aside class="band-studio__sidebar" aria-label="<?php esc_attr_e( 'Checklista', 'musikstaden' ); ?>">
					<div class="band-studio__checklist">
						<h2><?php ms_e( 'studio.checklist_title', 'Innan du publicerar' ); ?></h2>
						<ul>
							<?php foreach ( $checklist as $item ) : ?>
								<li class="<?php echo $item['done'] ? 'is-done' : 'is-pending'; ?><?php echo $item['required'] ? ' is-required' : ' is-optional'; ?>">
									<span class="band-studio__check-icon" aria-hidden="true"><?php echo $item['done'] ? '✓' : '○'; ?></span>
									<?php echo esc_html( $item['label'] ); ?>
									<?php if ( ! $item['required'] ) : ?>
										<span class="band-studio__optional"><?php ms_e( 'studio.optional', 'valfritt' ); ?></span>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ul>
						<?php if ( ! $can_pub ) : ?>
							<p class="band-studio__checklist-hint"><?php ms_e( 'studio.checklist_hint', 'Alla obligatoriska punkter måste vara klara för att publicera.' ); ?></p>
						<?php endif; ?>
					</div>
				</aside>
				<?php endif; ?>

				<div class="band-studio__main">
					<form class="band-studio__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
						<?php wp_nonce_field( 'musikstaden_studio_save', 'musikstaden_studio_nonce' ); ?>
						<input type="hidden" name="action" value="musikstaden_save_band">
						<input type="hidden" name="band_id" value="<?php echo esc_attr( (string) $band_id ); ?>">

						<fieldset class="band-studio__panel">
							<legend><?php ms_e( 'studio.section_basics', 'Grundinfo' ); ?></legend>
							<div class="form-row">
								<label for="band_title"><?php ms_e( 'studio.field_name', 'Bandnamn' ); ?> <span class="required">*</span></label>
								<input type="text" id="band_title" name="band_title" required value="<?php echo esc_attr( $band ? $band->post_title : '' ); ?>">
							</div>
							<div class="form-row">
								<label for="hero_image"><?php ms_e( 'studio.field_hero', 'Profilbild (hero)' ); ?> <span class="required">*</span></label>
								<p class="field-hint"><?php ms_e( 'studio.field_hero_hint', 'Bilden visas högst upp på er artistsida. Rekommenderat: bred landskapsbild, minst 1200 px bredd.' ); ?></p>
								<?php if ( $hero_url ) : ?>
									<div class="band-studio__hero-preview">
										<img src="<?php echo esc_url( $hero_url ); ?>" alt="">
									</div>
									<label class="form-row--checkbox">
										<input type="checkbox" name="remove_hero" value="1">
										<?php ms_e( 'studio.remove_hero', 'Ta bort nuvarande bild' ); ?>
									</label>
								<?php endif; ?>
								<input type="file" id="hero_image" name="hero_image" accept="image/jpeg,image/png,image/webp">
							</div>
						</fieldset>

						<fieldset class="band-studio__panel">
							<legend><?php ms_e( 'studio.section_location', 'Plats & genre' ); ?></legend>
							<div class="form-row">
								<label for="band_city"><?php ms_e( 'studio.field_city', 'Stad' ); ?> <span class="required">*</span></label>
								<select id="band_city" name="band_city" required>
									<option value=""><?php ms_e( 'studio.city_placeholder', 'Välj stad' ); ?></option>
									<?php
									$current_city = ! empty( $city_terms ) && ! is_wp_error( $city_terms ) ? $city_terms[0] : '';
									foreach ( musikstaden_get_filter_terms( 'city' ) as $term ) :
										?>
										<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $current_city, $term->slug ); ?>>
											<?php echo esc_html( $term->name ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="form-row">
								<span class="label"><?php ms_e( 'studio.field_genres', 'Genrer' ); ?> <span class="required">*</span></span>
								<div class="checkbox-grid">
									<?php
									$genre_slugs = ! is_wp_error( $genre_terms ) ? $genre_terms : array();
									foreach ( musikstaden_get_filter_terms( 'genre' ) as $term ) :
										?>
										<label class="checkbox-grid__item">
											<input type="checkbox" name="band_genres[]" value="<?php echo esc_attr( $term->slug ); ?>" <?php checked( in_array( $term->slug, $genre_slugs, true ) ); ?>>
											<?php echo esc_html( $term->name ); ?>
										</label>
									<?php endforeach; ?>
								</div>
							</div>
							<div class="form-row">
								<span class="label"><?php ms_e( 'studio.field_gig_types', 'Bokningstyper' ); ?></span>
								<p class="field-hint"><?php ms_e( 'studio.field_gig_hint', 'Välj vilka typer av gig ni tar — bröllop, företag, festival m.m.' ); ?></p>
								<div class="checkbox-grid">
									<?php
									$gig_slugs = ! is_wp_error( $gig_terms ) ? $gig_terms : array();
									foreach ( musikstaden_get_filter_terms( 'gig_type' ) as $term ) :
										?>
										<label class="checkbox-grid__item">
											<input type="checkbox" name="band_gig_types[]" value="<?php echo esc_attr( $term->slug ); ?>" <?php checked( in_array( $term->slug, $gig_slugs, true ) ); ?>>
											<?php echo esc_html( $term->name ); ?>
										</label>
									<?php endforeach; ?>
								</div>
							</div>
						</fieldset>

						<fieldset class="band-studio__panel">
							<legend><?php ms_e( 'studio.section_bio', 'Om bandet' ); ?></legend>
							<div class="form-row">
								<label for="biography"><?php ms_e( 'studio.field_bio', 'Biografi' ); ?> <span class="required">*</span></label>
								<?php
								wp_editor(
									$band ? (string) musikstaden_get_field( 'biography', $band_id ) : '',
									'biography',
									array(
										'textarea_name' => 'biography',
										'media_buttons' => false,
										'teeny'         => true,
										'quicktags'     => true,
										'textarea_rows' => 8,
									)
								);
								?>
							</div>
							<div class="form-row">
								<label for="booking_email"><?php ms_e( 'studio.field_booking', 'Boknings-e-post' ); ?></label>
								<p class="field-hint"><?php ms_e( 'studio.field_booking_hint', 'Privat adress för bokningsförfrågningar. Visas inte publikt.' ); ?></p>
								<input type="email" id="booking_email" name="booking_email" placeholder="bokning@band.se" value="<?php echo esc_attr( $band ? (string) musikstaden_get_field( 'booking_email', $band_id ) : '' ); ?>">
							</div>
						</fieldset>

						<fieldset class="band-studio__panel">
							<legend><?php ms_e( 'studio.section_media', 'Musik & video' ); ?></legend>
							<div class="form-row">
								<label for="embed_spotify"><?php ms_e( 'studio.field_spotify', 'Spotify' ); ?></label>
								<p class="field-hint"><?php ms_e( 'studio.field_embed_hint', 'Klistra in Spotify-inbäddningskod (Delas → Bädda in) eller länk. Flera? Separera med tom rad.' ); ?></p>
								<textarea id="embed_spotify" name="embed_spotify" rows="4"><?php echo esc_textarea( $band ? (string) musikstaden_get_field( 'embed_spotify', $band_id ) : '' ); ?></textarea>
							</div>
							<div class="form-row">
								<label for="embed_youtube"><?php ms_e( 'studio.field_youtube', 'YouTube' ); ?></label>
								<textarea id="embed_youtube" name="embed_youtube" rows="4"><?php echo esc_textarea( $band ? (string) musikstaden_get_field( 'embed_youtube', $band_id ) : '' ); ?></textarea>
							</div>
						</fieldset>

						<fieldset class="band-studio__panel">
							<legend><?php ms_e( 'studio.section_social', 'Sociala länkar' ); ?></legend>
							<?php
							$social_labels = array(
								'social_instagram' => ms__( 'studio.social_instagram', 'Instagram' ),
								'social_facebook'  => ms__( 'studio.social_facebook', 'Facebook' ),
								'social_spotify'   => ms__( 'studio.social_spotify', 'Spotify' ),
								'social_youtube'   => ms__( 'studio.social_youtube', 'YouTube' ),
								'social_website'   => ms__( 'studio.social_website', 'Webbplats' ),
							);
							foreach ( $social_labels as $field => $label ) :
								?>
								<div class="form-row">
									<label for="<?php echo esc_attr( $field ); ?>"><?php echo esc_html( $label ); ?></label>
									<input type="url" id="<?php echo esc_attr( $field ); ?>" name="<?php echo esc_attr( $field ); ?>" placeholder="https://" value="<?php echo esc_url( $band ? (string) musikstaden_get_field( $field, $band_id ) : '' ); ?>">
								</div>
							<?php endforeach; ?>
						</fieldset>

						<div class="band-studio__actions">
							<button type="submit" name="studio_intent" value="draft" class="btn btn--outline">
								<?php ms_e( 'studio.save_draft', 'Spara utkast' ); ?>
							</button>
							<?php if ( ! $is_create && 'publish' === $status ) : ?>
								<button type="submit" name="studio_intent" value="unpublish" class="btn btn--outline">
									<?php ms_e( 'studio.unpublish', 'Avpublicera' ); ?>
								</button>
							<?php endif; ?>
							<button type="submit" name="studio_intent" value="publish" class="btn btn--primary btn--glow" <?php disabled( ! $is_create && ! $can_pub ); ?>>
								<?php ms_e( 'studio.publish', 'Publicera' ); ?>
							</button>
						</div>
					</form>

					<?php if ( ! $is_create && musikstaden_user_can_delete_band( get_current_user_id(), $band_id ) ) : ?>
						<form class="band-studio__delete" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( ms__( 'studio.delete_confirm', 'Är du säker? Bandet flyttas till papperskorgen.' ) ); ?>');">
							<?php wp_nonce_field( 'musikstaden_studio_delete', 'musikstaden_studio_delete_nonce' ); ?>
							<input type="hidden" name="action" value="musikstaden_delete_band">
							<input type="hidden" name="band_id" value="<?php echo esc_attr( (string) $band_id ); ?>">
							<button type="submit" class="btn btn--danger btn--sm"><?php ms_e( 'studio.delete', 'Ta bort band' ); ?></button>
						</form>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</section>
	<?php
}
