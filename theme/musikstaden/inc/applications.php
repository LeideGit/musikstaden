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
add_action( 'admin_post_musikstaden_create_application_band', 'musikstaden_create_application_band_handler' );
add_filter( 'manage_artist_application_posts_columns', 'musikstaden_application_columns' );
add_action( 'manage_artist_application_posts_custom_column', 'musikstaden_application_column_content', 10, 2 );
add_filter( 'views_edit-artist_application', 'musikstaden_application_views' );
add_action( 'pre_get_posts', 'musikstaden_filter_application_list' );

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
	$gig_slugs = array_values( array_filter( array_map( 'sanitize_title', (array) ( $_POST['app_gig_types'] ?? array() ) ) ) );

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
		update_field( 'app_gig_types', implode( ',', $gig_slugs ), $post_id );
		update_field( 'app_status', 'pending', $post_id );
	}
	update_post_meta( $post_id, 'app_email', $email );
	update_post_meta( $post_id, 'app_band_name', $band_name );
	update_post_meta( $post_id, 'app_city', $city );
	update_post_meta( $post_id, 'app_genre', $genre );
	update_post_meta( $post_id, 'app_pitch', $pitch );
	update_post_meta( $post_id, 'app_gig_types', implode( ',', $gig_slugs ) );
	update_post_meta( $post_id, 'app_status', 'pending' );

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
	$status = musikstaden_get_application_status( $post->ID );
	$email  = musikstaden_get_application_field( $post->ID, 'app_email' );
	$band_id = (int) get_post_meta( $post->ID, 'app_band_id', true );
	$user_id = (int) get_post_meta( $post->ID, 'app_user_id', true );
	?>
	<p><strong><?php esc_html_e( 'Status:', 'musikstaden' ); ?></strong> <?php echo esc_html( ucfirst( (string) $status ) ); ?></p>
	<?php if ( $email ) : ?>
		<p><strong><?php esc_html_e( 'Email:', 'musikstaden' ); ?></strong> <?php echo esc_html( (string) $email ); ?></p>
	<?php endif; ?>
	<?php
	$gig_labels = musikstaden_get_application_gig_type_labels( $post->ID );
	if ( ! empty( $gig_labels ) ) :
		?>
		<p><strong><?php esc_html_e( 'Booking types:', 'musikstaden' ); ?></strong> <?php echo esc_html( implode( ', ', $gig_labels ) ); ?></p>
	<?php endif; ?>
	<?php if ( $user_id ) : ?>
		<p>
			<a href="<?php echo esc_url( get_edit_user_link( $user_id ) ); ?>"><?php esc_html_e( 'View user account', 'musikstaden' ); ?></a>
		</p>
	<?php endif; ?>
	<?php if ( $band_id ) : ?>
		<p>
			<a href="<?php echo esc_url( get_edit_post_link( $band_id ) ); ?>"><?php esc_html_e( 'Edit band page', 'musikstaden' ); ?></a>
			<?php
			$band_status = get_post_status( $band_id );
			if ( 'draft' === $band_status ) {
				echo ' <em>(' . esc_html__( 'Draft', 'musikstaden' ) . ')</em>';
			}
			?>
		</p>
	<?php elseif ( 'approved' === $status ) : ?>
		<p class="description"><?php esc_html_e( 'No band linked yet.', 'musikstaden' ); ?></p>
		<p>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=musikstaden_create_application_band&application_id=' . $post->ID ), 'musikstaden_create_band_' . $post->ID ) ); ?>" class="button button-primary" style="width:100%;text-align:center;">
				<?php esc_html_e( 'Create band now', 'musikstaden' ); ?>
			</a>
		</p>
	<?php endif; ?>
	<?php if ( 'approved' === $status && $user_id ) : ?>
		<p>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=musikstaden_resend_approval_email&application_id=' . $post->ID ), 'musikstaden_resend_email_' . $post->ID ) ); ?>" class="button" style="width:100%;text-align:center;">
				<?php esc_html_e( 'Resend welcome email', 'musikstaden' ); ?>
			</a>
		</p>
	<?php endif; ?>
	<?php if ( 'pending' === $status ) : ?>
		<p>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=musikstaden_approve_application&application_id=' . $post->ID ), 'musikstaden_approve_' . $post->ID ) ); ?>" class="button button-primary" style="width:100%;margin-bottom:8px;text-align:center;">
				<?php esc_html_e( 'Approve — create user & band', 'musikstaden' ); ?>
			</a>
		</p>
		<p>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=musikstaden_reject_application&application_id=' . $post->ID ), 'musikstaden_reject_' . $post->ID ) ); ?>" class="button" style="width:100%;text-align:center;">
				<?php esc_html_e( 'Reject', 'musikstaden' ); ?>
			</a>
		</p>
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

	$app_id = (int) ( $_REQUEST['application_id'] ?? $_POST['application_id'] ?? 0 );
	if ( ! $app_id || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ?? $_POST['_wpnonce'] ?? '' ) ), 'musikstaden_approve_' . $app_id ) ) {
		wp_die( esc_html__( 'Invalid request.', 'musikstaden' ) );
	}

	if ( 'pending' !== musikstaden_get_application_status( $app_id ) ) {
		wp_die( esc_html__( 'This application has already been processed.', 'musikstaden' ) );
	}

	$result  = musikstaden_process_application_approval( $app_id );
	$user_id = $result['user_id'];
	$band_id = $result['band_id'];

	if ( function_exists( 'musikstaden_send_application_approved_email' ) ) {
		musikstaden_send_application_approved_email( $user_id, $app_id, $band_id );
	}

	wp_safe_redirect(
		add_query_arg(
			array(
				'approved' => '1',
				'band_id'  => $band_id,
			),
			admin_url( 'edit.php?post_type=band&post_status=draft&band_created=1' )
		)
	);
	exit;
}

/**
 * Create band for an already-approved application (fix / retry).
 */
function musikstaden_create_application_band_handler(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Unauthorized.', 'musikstaden' ) );
	}

	$app_id = (int) ( $_REQUEST['application_id'] ?? 0 );
	if ( ! $app_id || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ?? '' ) ), 'musikstaden_create_band_' . $app_id ) ) {
		wp_die( esc_html__( 'Invalid request.', 'musikstaden' ) );
	}

	if ( 'approved' !== musikstaden_get_application_status( $app_id ) ) {
		wp_die( esc_html__( 'Only approved applications can have a band created.', 'musikstaden' ) );
	}

	$user_id = musikstaden_resolve_application_user_id( $app_id );
	if ( ! $user_id ) {
		wp_die( esc_html__( 'No user account found for this application. Approve the application first or create the user manually.', 'musikstaden' ) );
	}

	$band_id = musikstaden_create_band_from_application( $app_id, $user_id );
	update_post_meta( $app_id, 'app_user_id', $user_id );
	update_post_meta( $app_id, 'app_band_id', $band_id );

	wp_safe_redirect(
		add_query_arg(
			array(
				'band_created' => '1',
				'band_id'      => $band_id,
			),
			admin_url( 'edit.php?post_type=band&post_status=draft' )
		)
	);
	exit;
}

/**
 * Approve application: create user + band.
 *
 * @return array{user_id: int, band_id: int, user_created: bool}
 */
function musikstaden_process_application_approval( int $app_id ): array {
	$email = (string) musikstaden_get_application_field( $app_id, 'app_email' );
	$name  = explode( ' — ', get_the_title( $app_id ) )[0] ?? get_the_title( $app_id );

	if ( ! is_email( $email ) ) {
		wp_die( esc_html__( 'Invalid email on application. Check the Email field in Application Details.', 'musikstaden' ) );
	}

	$user_created = false;
	$existing     = get_user_by( 'email', $email );
	if ( $existing ) {
		$user_id = (int) $existing->ID;
	} else {
		$username = sanitize_user( current( explode( '@', $email ) ), true );
		if ( username_exists( $username ) ) {
			$username .= wp_rand( 100, 999 );
		}

		musikstaden_ensure_roles();

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
		$user_created = true;
	}

	$band_id = musikstaden_create_band_from_application( $app_id, $user_id );

	musikstaden_set_application_status( $app_id, 'approved' );
	update_post_meta( $app_id, 'app_user_id', $user_id );
	update_post_meta( $app_id, 'app_band_id', $band_id );

	update_user_meta( $user_id, 'pw_user_status', 'approved' );
	update_user_meta( $user_id, 'musikstaden_approved', '1' );

	return array(
		'user_id'      => $user_id,
		'band_id'      => $band_id,
		'user_created' => $user_created,
	);
}

/**
 * Find WP user linked to an application (meta or email lookup).
 */
function musikstaden_resolve_application_user_id( int $app_id ): int {
	$user_id = (int) get_post_meta( $app_id, 'app_user_id', true );
	if ( $user_id ) {
		return $user_id;
	}

	$email = musikstaden_get_application_field( $app_id, 'app_email' );
	if ( ! is_email( $email ) ) {
		return 0;
	}

	$user = get_user_by( 'email', $email );
	if ( ! $user ) {
		return 0;
	}

	update_post_meta( $app_id, 'app_user_id', (int) $user->ID );
	update_user_meta( (int) $user->ID, 'musikstaden_approved', '1' );

	return (int) $user->ID;
}

/**
 * Reject application.
 */
function musikstaden_reject_application(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Unauthorized.', 'musikstaden' ) );
	}

	$app_id = (int) ( $_REQUEST['application_id'] ?? $_POST['application_id'] ?? 0 );
	if ( ! $app_id || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ?? $_POST['_wpnonce'] ?? '' ) ), 'musikstaden_reject_' . $app_id ) ) {
		wp_die( esc_html__( 'Invalid request.', 'musikstaden' ) );
	}

	musikstaden_set_application_status( $app_id, 'rejected' );

	wp_safe_redirect( admin_url( 'post.php?post=' . $app_id . '&action=edit&rejected=1' ) );
	exit;
}

/**
 * Persist application status (ACF + post meta for reliability).
 */
function musikstaden_set_application_status( int $app_id, string $status ): void {
	update_post_meta( $app_id, 'app_status', $status );
	if ( function_exists( 'update_field' ) ) {
		update_field( 'app_status', $status, $app_id );
	}
}

/**
 * Read application status.
 */
function musikstaden_get_application_status( int $app_id ): string {
	$status = get_post_meta( $app_id, 'app_status', true );
	if ( is_string( $status ) && '' !== $status ) {
		return $status;
	}
	$acf_status = musikstaden_get_field( 'app_status', $app_id );
	return is_string( $acf_status ) && '' !== $acf_status ? $acf_status : 'pending';
}

/**
 * Read a field from application post meta or ACF.
 */
function musikstaden_get_application_field( int $app_id, string $key ): string {
	$value = get_post_meta( $app_id, $key, true );
	if ( is_string( $value ) && '' !== $value ) {
		return $value;
	}
	$acf = musikstaden_get_field( $key, $app_id );
	return is_string( $acf ) ? $acf : ( is_scalar( $acf ) ? (string) $acf : '' );
}

/**
 * Create a draft band from application data and assign owner.
 */
function musikstaden_create_band_from_application( int $app_id, int $user_id ): int {
	$existing_band = (int) get_post_meta( $app_id, 'app_band_id', true );
	if ( $existing_band && 'band' === get_post_type( $existing_band ) ) {
		return $existing_band;
	}

	$band_name = musikstaden_get_application_field( $app_id, 'app_band_name' );
	if ( '' === $band_name ) {
		$title_parts = explode( ' — ', get_the_title( $app_id ) );
		$band_name   = $title_parts[1] ?? $title_parts[0] ?? __( 'New Band', 'musikstaden' );
	}

	$pitch = musikstaden_get_application_field( $app_id, 'app_pitch' );
	$city  = musikstaden_get_application_field( $app_id, 'app_city' );
	$genre = musikstaden_get_application_field( $app_id, 'app_genre' );

	$band_id = wp_insert_post(
		array(
			'post_type'   => 'band',
			'post_title'  => $band_name,
			'post_status' => 'draft',
			'post_author' => $user_id,
		),
		true
	);

	if ( is_wp_error( $band_id ) ) {
		wp_die( esc_html__( 'Could not create band:', 'musikstaden' ) . ' ' . esc_html( $band_id->get_error_message() ) );
	}

	if ( ! $band_id ) {
		wp_die( esc_html__( 'Could not create band. Unknown error.', 'musikstaden' ) );
	}

	musikstaden_set_user_band_role( $user_id, $band_id, 'owner' );

	if ( $pitch ) {
		update_post_meta( $band_id, 'biography', wp_kses_post( $pitch ) );
		if ( function_exists( 'update_field' ) ) {
			update_field( 'biography', $pitch, $band_id );
		}
	}

	musikstaden_assign_application_term( $band_id, 'city', $city );
	musikstaden_assign_application_term( $band_id, 'genre', $genre );

	$gig_slugs = musikstaden_get_application_gig_type_slugs( $app_id );
	if ( ! empty( $gig_slugs ) ) {
		wp_set_object_terms( $band_id, $gig_slugs, 'gig_type' );
	}

	return $band_id;
}

/**
 * Gig type slugs stored on an application.
 *
 * @return string[]
 */
function musikstaden_get_application_gig_type_slugs( int $app_id ): array {
	$raw = musikstaden_get_application_field( $app_id, 'app_gig_types' );
	if ( '' === $raw ) {
		return array();
	}

	return array_values(
		array_filter(
			array_map(
				static function ( string $slug ): string {
					return musikstaden_map_legacy_gig_slug( sanitize_title( $slug ) );
				},
				array_map( 'trim', explode( ',', $raw ) )
			)
		)
	);
}

/**
 * Human-readable gig type labels for an application.
 *
 * @return string[]
 */
function musikstaden_get_application_gig_type_labels( int $app_id ): array {
	$labels = array();
	foreach ( musikstaden_get_application_gig_type_slugs( $app_id ) as $slug ) {
		$term = get_term_by( 'slug', $slug, 'gig_type' );
		$labels[] = ( $term && ! is_wp_error( $term ) ) ? $term->name : $slug;
	}
	return $labels;
}

/**
 * Assign taxonomy term to band if a matching term exists.
 */
function musikstaden_assign_application_term( int $band_id, string $taxonomy, string $name ): void {
	$name = trim( $name );
	if ( '' === $name ) {
		return;
	}

	$term = get_term_by( 'name', $name, $taxonomy );
	if ( ! $term ) {
		$term = get_term_by( 'slug', sanitize_title( $name ), $taxonomy );
	}
	if ( $term && ! is_wp_error( $term ) ) {
		wp_set_object_terms( $band_id, (int) $term->term_id, $taxonomy );
	}
}

/**
 * Admin list columns for applications.
 *
 * @param string[] $columns Columns.
 * @return string[]
 */
function musikstaden_application_columns( array $columns ): array {
	$new = array();
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['app_status'] = __( 'Status', 'musikstaden' );
			$new['app_band']   = __( 'Band', 'musikstaden' );
		}
	}
	return $new;
}

/**
 * Render custom application list columns.
 */
function musikstaden_application_column_content( string $column, int $post_id ): void {
	if ( 'app_status' === $column ) {
		echo esc_html( ucfirst( musikstaden_get_application_status( $post_id ) ) );
		return;
	}

	if ( 'app_band' === $column ) {
		$band_id = (int) get_post_meta( $post_id, 'app_band_id', true );
		if ( $band_id ) {
			printf(
				'<a href="%s">%s</a>',
				esc_url( get_edit_post_link( $band_id ) ),
				esc_html( get_the_title( $band_id ) )
			);
		} else {
			echo '—';
		}
	}
}

/**
 * Filter tabs on applications list (Pending / Approved / Rejected).
 *
 * @param string[] $views Views.
 * @return string[]
 */
function musikstaden_application_views( array $views ): array {
	global $wpdb;

	$counts = array(
		'pending'  => 0,
		'approved' => 0,
		'rejected' => 0,
	);

	$rows = $wpdb->get_results(
		"SELECT pm.meta_value AS status, COUNT(*) AS total
		FROM {$wpdb->posts} p
		INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'app_status'
		WHERE p.post_type = 'artist_application' AND p.post_status = 'publish'
		GROUP BY pm.meta_value",
		ARRAY_A
	);

	$without_status = (int) $wpdb->get_var(
		"SELECT COUNT(*) FROM {$wpdb->posts} p
		LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'app_status'
		WHERE p.post_type = 'artist_application' AND p.post_status = 'publish' AND pm.meta_id IS NULL"
	);

	foreach ( $rows as $row ) {
		$status = $row['status'] ?? '';
		if ( isset( $counts[ $status ] ) ) {
			$counts[ $status ] = (int) $row['total'];
		}
	}
	$counts['pending'] += $without_status;

	$total      = array_sum( $counts );
	$current    = sanitize_text_field( wp_unslash( $_GET['app_status'] ?? 'pending' ) );
	$base_url   = admin_url( 'edit.php?post_type=artist_application' );

	$custom = array(
		'pending' => sprintf(
			'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
			esc_url( $base_url . '&app_status=pending' ),
			'pending' === $current ? ' class="current"' : '',
			esc_html__( 'Pending', 'musikstaden' ),
			$counts['pending']
		),
		'approved' => sprintf(
			'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
			esc_url( $base_url . '&app_status=approved' ),
			'approved' === $current ? ' class="current"' : '',
			esc_html__( 'Approved', 'musikstaden' ),
			$counts['approved']
		),
		'rejected' => sprintf(
			'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
			esc_url( $base_url . '&app_status=rejected' ),
			'rejected' === $current ? ' class="current"' : '',
			esc_html__( 'Rejected', 'musikstaden' ),
			$counts['rejected']
		),
		'all' => sprintf(
			'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
			esc_url( $base_url . '&app_status=all' ),
			'all' === $current ? ' class="current"' : '',
			esc_html__( 'All', 'musikstaden' ),
			$total
		),
	);

	return $custom;
}

/**
 * Default applications list to Pending only.
 */
function musikstaden_filter_application_list( WP_Query $query ): void {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( 'artist_application' !== $query->get( 'post_type' ) ) {
		return;
	}

	$filter = sanitize_text_field( wp_unslash( $_GET['app_status'] ?? 'pending' ) );
	if ( 'all' === $filter ) {
		return;
	}

	if ( ! in_array( $filter, array( 'pending', 'approved', 'rejected' ), true ) ) {
		$filter = 'pending';
	}

	if ( 'pending' === $filter ) {
		$query->set(
			'meta_query',
			array(
				'relation' => 'OR',
				array(
					'key'   => 'app_status',
					'value' => 'pending',
				),
				array(
					'key'     => 'app_status',
					'compare' => 'NOT EXISTS',
				),
			)
		);
		return;
	}

	$query->set(
		'meta_query',
		array(
			array(
				'key'   => 'app_status',
				'value' => $filter,
			),
		)
	);
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
			<label for="app_gig_types-trigger"><?php ms_e( 'apply.gig_types', 'Bokningstyper' ); ?></label>
			<p class="field-hint"><?php ms_e( 'apply.gig_types_hint', 'Vilka typer av spelningar söker ni? Bröllop, festival, företagsevent m.m.' ); ?></p>
			<?php
			musikstaden_render_studio_checkbox_dropdown(
				'app_gig_types',
				'app_gig_types',
				musikstaden_get_filter_terms( 'gig_type' ),
				array(),
				ms__( 'apply.gig_types_placeholder', 'Välj bokningstyper' )
			);
			?>
		</div>
		<div class="form-row">
			<label for="app_pitch"><?php ms_e( 'apply.pitch', 'Short pitch' ); ?></label>
			<textarea id="app_pitch" name="app_pitch" rows="4"></textarea>
		</div>
		<button type="submit" class="btn btn--primary btn--glow"><?php ms_e( 'apply.submit', 'Submit Application' ); ?></button>
	</form>
	<?php
}

/**
 * Send a clear welcome email when an artist application is approved.
 */
function musikstaden_send_application_approved_email( int $user_id, int $app_id, int $band_id ): bool {
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return false;
	}

	$band_name = $band_id ? get_the_title( $band_id ) : musikstaden_get_application_field( $app_id, 'app_band_name' );
	$login_url = home_url( '/logga-in/' );
	$dash_url  = home_url( '/dashboard/' );
	$edit_url  = $band_id ? musikstaden_band_edit_url( $band_id ) : '';
	$site      = get_bloginfo( 'name' );

	$key = get_password_reset_key( $user );
	if ( is_wp_error( $key ) ) {
		$setup_url       = wp_lostpassword_url( $login_url );
		$setup_url_label = __( 'Begär en länk för att välja lösenord:', 'musikstaden' );
	} else {
		$setup_url = network_site_url(
			'wp-login.php?login=' . rawurlencode( $user->user_login ) . '&key=' . $key . '&action=rp',
			'login'
		);
		$setup_url_label = __( 'Besök följande adress för att ange ditt lösenord:', 'musikstaden' );
	}

	$subject = sprintf(
		/* translators: %s: site name */
		__( '[%s] Din ansökan är godkänd — välkommen!', 'musikstaden' ),
		$site
	);

	$lines = array(
		sprintf( __( 'Hej %s,', 'musikstaden' ), $user->display_name ),
		'',
		__( 'Goda nyheter! Din ansökan till Musikstaden beta är godkänd.', 'musikstaden' ),
		'',
		__( 'Musikstaden är en plattform där lokala artister och band kan ha en egen sida — så fans och spelställen hittar dig utan att du försvinner i bruset på sociala medier.', 'musikstaden' ),
		'',
	);

	if ( $band_name ) {
		$lines[] = sprintf(
			/* translators: %s: band name */
			__( 'Vi har skapat ett utkast till band-sida: %s', 'musikstaden' ),
			$band_name
		);
		$lines[] = '';
	}

	$lines[] = __( 'Nästa steg:', 'musikstaden' );
	$lines[] = __( '1. Klicka länken nedan för att välja ditt lösenord', 'musikstaden' );
	$lines[] = __( '2. Logga in på musikstaden.se', 'musikstaden' );
	$lines[] = __( '3. Gå till Min panel och fyll i band-sidan (bild, bio, länkar till Spotify/YouTube m.m.)', 'musikstaden' );
	$lines[] = __( '4. Publicera sidan när du är nöjd', 'musikstaden' );
	$lines[] = '';
	$lines[] = $setup_url_label;
	$lines[] = $setup_url;
	$lines[] = '';
	$lines[] = __( 'Logga in:', 'musikstaden' );
	$lines[] = $login_url;
	$lines[] = '';
	$lines[] = __( 'Min panel:', 'musikstaden' );
	$lines[] = $dash_url;
	if ( $edit_url ) {
		$lines[] = '';
		$lines[] = __( 'Redigera band-sida:', 'musikstaden' );
		$lines[] = $edit_url;
	}
	$lines[] = '';
	$lines[] = __( 'Har du frågor? Svara på detta mail eller skriv till hello@musikstaden.se', 'musikstaden' );
	$lines[] = '';
	$lines[] = sprintf(
		/* translators: %s: site name */
		__( 'Välkommen till %s!', 'musikstaden' ),
		$site
	);

	$body    = implode( "\n", $lines );
	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

	return wp_mail( $user->user_email, $subject, $body, $headers );
}

add_action( 'admin_post_musikstaden_resend_approval_email', 'musikstaden_resend_approval_email_handler' );
function musikstaden_resend_approval_email_handler(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Unauthorized.', 'musikstaden' ) );
	}

	$app_id = (int) ( $_REQUEST['application_id'] ?? 0 );
	if ( ! $app_id || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ?? '' ) ), 'musikstaden_resend_email_' . $app_id ) ) {
		wp_die( esc_html__( 'Invalid request.', 'musikstaden' ) );
	}

	if ( 'approved' !== musikstaden_get_application_status( $app_id ) ) {
		wp_die( esc_html__( 'Only approved applications can receive this email.', 'musikstaden' ) );
	}

	$user_id = musikstaden_resolve_application_user_id( $app_id );
	if ( ! $user_id ) {
		wp_die( esc_html__( 'No user account linked to this application.', 'musikstaden' ) );
	}

	$band_id = (int) get_post_meta( $app_id, 'app_band_id', true );
	$sent    = musikstaden_send_application_approved_email( $user_id, $app_id, $band_id );

	wp_safe_redirect(
		add_query_arg(
			'email_resent',
			$sent ? '1' : '0',
			admin_url( 'post.php?post=' . $app_id . '&action=edit' )
		)
	);
	exit;
}
