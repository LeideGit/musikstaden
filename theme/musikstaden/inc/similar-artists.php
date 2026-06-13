<?php
/**
 * Similar artists query.
 *
 * @package Musikstaden
 */

declare(strict_types=1);

/**
 * Find similar bands: same city + shared genre.
 *
 * @return WP_Post[]
 */
function musikstaden_get_similar_bands( int $band_id, int $limit = 4 ): array {
	$cities  = wp_get_post_terms( $band_id, 'city', array( 'fields' => 'ids' ) );
	$genres  = wp_get_post_terms( $band_id, 'genre', array( 'fields' => 'ids' ) );

	if ( empty( $cities ) || empty( $genres ) || is_wp_error( $cities ) || is_wp_error( $genres ) ) {
		return array();
	}

	$query = new WP_Query(
		array(
			'post_type'      => 'band',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'post__not_in'   => array( $band_id ),
			'orderby'        => 'rand',
			'tax_query'      => array(
				'relation' => 'AND',
				array(
					'taxonomy' => 'city',
					'field'    => 'term_id',
					'terms'    => $cities,
				),
				array(
					'taxonomy' => 'genre',
					'field'    => 'term_id',
					'terms'    => $genres,
				),
			),
		)
	);

	return $query->posts;
}

/**
 * Render similar artists sidebar block.
 */
function musikstaden_render_similar_artists( int $band_id ): void {
	$similar = musikstaden_get_similar_bands( $band_id );
	if ( empty( $similar ) ) {
		return;
	}
	?>
	<aside class="similar-artists">
		<h2 class="similar-artists__title"><?php ms_e( 'band.similar', 'Similar Artists' ); ?></h2>
		<div class="similar-artists__grid">
			<?php foreach ( $similar as $band ) : ?>
				<?php musikstaden_render_band_card( $band ); ?>
			<?php endforeach; ?>
		</div>
	</aside>
	<?php
}
