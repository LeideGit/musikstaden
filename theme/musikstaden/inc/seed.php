<?php
/**
 * Seed taxonomy terms and demo bands.
 *
 * @package Musikstaden
 */

declare(strict_types=1);

add_action( 'admin_init', 'musikstaden_maybe_run_seed' );

/**
 * Run seed when admin visits ?musikstaden_seed=1
 */
function musikstaden_maybe_run_seed(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! isset( $_GET['musikstaden_seed'] ) ) {
		return;
	}

	musikstaden_seed_taxonomies();
	musikstaden_seed_bands();
	musikstaden_seed_pages();

	add_action( 'admin_notices', function () {
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Musikstaden seed data created.', 'musikstaden' ) . '</p></div>';
	} );
}

/**
 * Seed cities, genres, and booking types (gig_type).
 */
function musikstaden_seed_taxonomies(): void {
	$cities = array( 'Stockholm', 'Göteborg', 'Malmö', 'Uppsala', 'Linköping', 'Örebro', 'Västerås', 'Umeå', 'Luleå', 'Gävle' );
	foreach ( $cities as $city ) {
		if ( ! term_exists( $city, 'city' ) ) {
			wp_insert_term( $city, 'city', array( 'slug' => sanitize_title( $city ) ) );
		}
	}

	$genres = array( 'Jazz', 'Soul', 'Rock', 'Pop', 'Folk', 'Indie', 'Electronic', 'Classical', 'Hip-Hop', 'Metal', 'Blues', 'Reggae' );
	foreach ( $genres as $genre ) {
		if ( ! term_exists( $genre, 'genre' ) ) {
			wp_insert_term( $genre, 'genre', array( 'slug' => sanitize_title( $genre ) ) );
		}
	}

	musikstaden_seed_booking_types();
}

/**
 * Seed 12 demo bands matching prototype names.
 */
function musikstaden_seed_bands(): void {
	$bands = array(
		array( 'name' => 'Luna Andersson', 'city' => 'stockholm', 'genres' => array( 'jazz', 'soul' ), 'gigs' => array( 'brollop', 'foretagsevent' ), 'bio' => 'Prisbelönt jazzsångerska baserad i Stockholm. Luna blandar klassisk jazz med modern soul.' ),
		array( 'name' => 'The Northern Lights', 'city' => 'stockholm', 'genres' => array( 'rock', 'indie' ), 'gigs' => array( 'klubb-bar', 'festival' ), 'bio' => 'Energisk rockkvartett med norrländska rötter och arena-ready refränger.' ),
		array( 'name' => 'Sofia Nilsson', 'city' => 'stockholm', 'genres' => array( 'classical', 'jazz' ), 'gigs' => array( 'brollop', 'privat-fest' ), 'bio' => 'Prisbelönt violinist som specialiserar sig på klassiska bröllop och företagsevent.' ),
		array( 'name' => 'Echo Chamber', 'city' => 'goteborg', 'genres' => array( 'electronic', 'indie' ), 'gigs' => array( 'klubb-bar', 'festival' ), 'bio' => 'Elektroniskt duo från Göteborg med drömsk ambient och dansgolvshits.' ),
		array( 'name' => 'Malmö Soul Collective', 'city' => 'malmo', 'genres' => array( 'soul', 'jazz' ), 'gigs' => array( 'klubb-bar', 'restaurang' ), 'bio' => 'Åtta personer stark soul-orkester med rötter i Skånes jazzscen.' ),
		array( 'name' => 'Fjäderlight', 'city' => 'uppsala', 'genres' => array( 'folk', 'indie' ), 'gigs' => array( 'festival', 'privat-fest' ), 'bio' => 'Akustisk folkduo med harmonier inspirerade av svensk vistradition.' ),
		array( 'name' => 'Neon Harbor', 'city' => 'goteborg', 'genres' => array( 'pop', 'electronic' ), 'gigs' => array( 'foretagsevent', 'klubb-bar' ), 'bio' => 'Synthpop-trio perfekt för företagsevent och klubbkvällar.' ),
		array( 'name' => 'Ironwood', 'city' => 'orebro', 'genres' => array( 'metal', 'rock' ), 'gigs' => array( 'festival', 'klubb-bar' ), 'bio' => 'Tung rock från Örebro med melodiska hooks och mäktiga riff.' ),
		array( 'name' => 'Blue Line Trio', 'city' => 'stockholm', 'genres' => array( 'blues', 'jazz' ), 'gigs' => array( 'restaurang', 'klubb-bar' ), 'bio' => 'Bluesjamtrio med decenniers erfarenhet av Stockholms scener.' ),
		array( 'name' => 'Aurora Pulse', 'city' => 'lulea', 'genres' => array( 'electronic', 'pop' ), 'gigs' => array( 'festival', 'klubb-bar' ), 'bio' => 'Norrbottens svar på electropop — kallt klimat, varma syntar.' ),
		array( 'name' => 'Kustlinjen', 'city' => 'gavle', 'genres' => array( 'reggae', 'folk' ), 'gigs' => array( 'festival', 'privat-fest' ), 'bio' => 'Reggae-fusion med svenska texter och sommarvibe året om.' ),
		array( 'name' => 'Västerås Beat Club', 'city' => 'vasteras', 'genres' => array( 'hip-hop', 'soul' ), 'gigs' => array( 'klubb-bar', 'foretagsevent' ), 'bio' => 'Live hip-hop ensemble med hornsektion och soulful sång.' ),
	);

	$embeds = array(
		'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
		'https://open.spotify.com/track/4cOdK2wGLETKBW3PvgPWoT',
	);

	foreach ( $bands as $band ) {
		$slug = sanitize_title( $band['name'] );
		$existing = get_page_by_path( $slug, OBJECT, 'band' );
		if ( $existing ) {
			continue;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => 'band',
				'post_title'  => $band['name'],
				'post_name'   => $slug,
				'post_status' => 'publish',
			)
		);

		if ( is_wp_error( $post_id ) ) {
			continue;
		}

		wp_set_object_terms( $post_id, $band['city'], 'city' );
		wp_set_object_terms( $post_id, $band['genres'], 'genre' );
		wp_set_object_terms( $post_id, $band['gigs'], 'gig_type' );

		if ( function_exists( 'update_field' ) ) {
			update_field( 'biography', $band['bio'], $post_id );
			update_field( 'embed_spotify', $embeds[0], $post_id );
			update_field( 'social_spotify', 'https://open.spotify.com', $post_id );
			update_field( 'social_instagram', 'https://instagram.com', $post_id );
		} else {
			update_post_meta( $post_id, 'biography', $band['bio'] );
		}
	}
}

/**
 * Create required WordPress pages with templates.
 */
function musikstaden_seed_pages(): void {
	$pages = array(
		array( 'title' => 'Dashboard', 'slug' => 'dashboard', 'template' => 'page-dashboard.php' ),
		array( 'title' => 'Logga in', 'slug' => 'logga-in', 'template' => 'page-login.php' ),
		array( 'title' => 'För artister', 'slug' => 'for-artists', 'template' => 'page-for-artists.php' ),
		array( 'title' => 'För spelställen', 'slug' => 'for-venues', 'template' => 'page-for-venues.php' ),
		array( 'title' => 'Integritetspolicy', 'slug' => 'privacy', 'template' => 'page-privacy.php' ),
		array( 'title' => 'Cookies', 'slug' => 'cookies', 'template' => 'page-cookies.php' ),
	);

	foreach ( $pages as $page ) {
		$existing = get_page_by_path( $page['slug'] );
		if ( $existing ) {
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

	// Set static front page if not set.
	if ( ! get_option( 'page_on_front' ) ) {
		$front = get_page_by_path( 'home' );
		if ( ! $front ) {
			$front_id = wp_insert_post(
				array(
					'post_title'  => 'Home',
					'post_name'   => 'home',
					'post_status' => 'publish',
					'post_type'   => 'page',
				)
			);
		} else {
			$front_id = $front->ID;
		}
		if ( $front_id && ! is_wp_error( $front_id ) ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $front_id );
		}
	}
}
