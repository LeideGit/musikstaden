<?php
/**
 * Similar artists query.
 *
 * @package Musikstaden
 */

declare(strict_types=1);

/**
 * Query similar bands by taxonomies.
 *
 * @param int      $band_id Band post ID.
 * @param int      $limit   Max results.
 * @param string[] $taxonomies Taxonomies that must all match.
 * @return WP_Post[]
 */
function musikstaden_query_similar_bands( int $band_id, int $limit, array $taxonomies ): array {
	$tax_query = array( 'relation' => 'AND' );

	foreach ( $taxonomies as $taxonomy ) {
		$terms = wp_get_post_terms( $band_id, $taxonomy, array( 'fields' => 'ids' ) );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return array();
		}
		$tax_query[] = array(
			'taxonomy' => $taxonomy,
			'field'    => 'term_id',
			'terms'    => $terms,
		);
	}

	$query = new WP_Query(
		array(
			'post_type'      => 'band',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'post__not_in'   => array( $band_id ),
			'orderby'        => 'rand',
			'tax_query'      => $tax_query,
		)
	);

	return $query->posts;
}

/**
 * Find similar bands: same city + genre, then fallback to same genre.
 *
 * @return array{posts: WP_Post[], match: string}
 */
function musikstaden_get_similar_bands( int $band_id, int $limit = 4 ): array {
	$city_genre = musikstaden_query_similar_bands( $band_id, $limit, array( 'city', 'genre' ) );
	if ( ! empty( $city_genre ) ) {
		return array(
			'posts' => $city_genre,
			'match' => 'city_genre',
		);
	}

	$genre_only = musikstaden_query_similar_bands( $band_id, $limit, array( 'genre' ) );

	return array(
		'posts' => $genre_only,
		'match' => 'genre',
	);
}

/**
 * Render similar artists sidebar block.
 */
function musikstaden_render_similar_artists( int $band_id ): void {
	$result  = musikstaden_get_similar_bands( $band_id );
	$similar = $result['posts'];

	if ( empty( $similar ) ) {
		return;
	}

	$subtitle_key = 'city_genre' === $result['match'] ? 'band.similar_city_genre' : 'band.similar_genre';
	?>
	<div class="similar-artists">
		<h2 class="similar-artists__title"><?php ms_e( 'band.similar', 'Similar Artists' ); ?></h2>
		<p class="similar-artists__sub"><?php ms_e( $subtitle_key, 'More artists you might like' ); ?></p>
		<div class="similar-artists__grid">
			<?php foreach ( $similar as $band ) : ?>
				<?php musikstaden_render_band_card( $band ); ?>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}
