<?php
/**
 * Shared helpers.
 *
 * @package Musikstaden
 */

declare(strict_types=1);

/**
 * Get translated string from JSON lang files.
 *
 * @param string $key Dot-notation key.
 * @param string $fallback Fallback text.
 */
function ms__( string $key, string $fallback = '' ): string {
	return musikstaden_translate( $key, $fallback );
}

/**
 * Echo translated string.
 *
 * @param string $key Dot-notation key.
 * @param string $fallback Fallback text.
 */
function ms_e( string $key, string $fallback = '' ): void {
	echo esc_html( ms__( $key, $fallback ) );
}

/**
 * Get band permalink with /artist/ prefix.
 *
 * @param int|WP_Post $post Post ID or object.
 */
function musikstaden_band_url( $post ): string {
	$post = get_post( $post );
	if ( ! $post ) {
		return home_url( '/' );
	}
	return home_url( '/artist/' . $post->post_name . '/' );
}

/**
 * Detect embed platform from URL.
 */
function musikstaden_detect_embed_platform( string $url ): string {
	$host = wp_parse_url( $url, PHP_URL_HOST ) ?? '';
	$host = strtolower( (string) $host );

	if ( str_contains( $host, 'youtube.com' ) || str_contains( $host, 'youtu.be' ) ) {
		return 'youtube';
	}
	if ( str_contains( $host, 'soundcloud.com' ) ) {
		return 'soundcloud';
	}
	if ( str_contains( $host, 'spotify.com' ) ) {
		return 'spotify';
	}
	return 'unknown';
}

/**
 * Render oEmbed HTML for a URL.
 */
function musikstaden_render_embed( string $url ): string {
	if ( '' === trim( $url ) ) {
		return '';
	}

	$html = wp_oembed_get( $url );
	if ( ! $html ) {
		return '<p class="embed-error">' . esc_html( ms__( 'embed.error', 'Could not embed this URL.' ) ) . '</p>';
	}

	return '<div class="embed-item">' . $html . '</div>';
}

/**
 * Get taxonomy terms as tag markup.
 *
 * @param int    $post_id Post ID.
 * @param string $taxonomy Taxonomy slug.
 * @param string $class CSS class for tags.
 */
function musikstaden_term_tags( int $post_id, string $taxonomy, string $class = 'tag' ): string {
	$terms = get_the_terms( $post_id, $taxonomy );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return '';
	}

	$output = '';
	foreach ( $terms as $term ) {
		$output .= sprintf(
			'<span class="%s %s--%s">%s</span>',
			esc_attr( $class ),
			esc_attr( $class ),
			esc_attr( $taxonomy ),
			esc_html( strtoupper( $term->name ) )
		);
	}
	return $output;
}

/**
 * Search URL with filters.
 *
 * @param array<string, string> $filters Filter slugs.
 */
function musikstaden_search_url( array $filters = array() ): string {
	$base = home_url( '/sok/' );
	$query = array_filter(
		array(
			'city'  => $filters['city'] ?? '',
			'event' => $filters['event'] ?? '',
			'genre' => $filters['genre'] ?? '',
		)
	);
	if ( empty( $query ) ) {
		return $base;
	}
	return add_query_arg( $query, $base );
}

/**
 * Build band search query args from GET params.
 *
 * @return array<string, mixed>
 */
function musikstaden_band_query_args(): array {
	$args = array(
		'post_type'      => 'band',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	);

	$tax_query = array( 'relation' => 'AND' );

	foreach (
		array(
			'city'       => 'city',
			'event'      => 'event_type',
			'genre'      => 'genre',
		) as $param => $taxonomy
	) {
		$slug = sanitize_title( wp_unslash( $_GET[ $param ] ?? '' ) );
		if ( $slug ) {
			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => $slug,
			);
		}
	}

	if ( count( $tax_query ) > 1 ) {
		$args['tax_query'] = $tax_query;
	}

	return $args;
}

/**
 * Render artist card partial.
 *
 * @param WP_Post $post Band post.
 */
function musikstaden_render_band_card( WP_Post $post ): void {
	$city_terms = get_the_terms( $post->ID, 'city' );
	$city_name  = ( $city_terms && ! is_wp_error( $city_terms ) ) ? $city_terms[0]->name : '';
	?>
	<article class="artist-card">
		<a href="<?php echo esc_url( musikstaden_band_url( $post ) ); ?>" class="artist-card__link">
			<div class="artist-card__image">
				<?php if ( has_post_thumbnail( $post ) ) : ?>
					<?php echo get_the_post_thumbnail( $post, 'band-card', array( 'alt' => esc_attr( get_the_title( $post ) ) ) ); ?>
				<?php else : ?>
					<div class="artist-card__placeholder"><?php ms_e( 'card.image', 'IMAGE' ); ?></div>
				<?php endif; ?>
			</div>
			<div class="artist-card__body">
				<h3 class="artist-card__title"><?php echo esc_html( get_the_title( $post ) ); ?></h3>
				<div class="artist-card__tags">
					<?php echo musikstaden_term_tags( $post->ID, 'genre', 'tag tag--genre' ); ?>
				</div>
				<?php if ( $city_name ) : ?>
					<p class="artist-card__city"><?php echo esc_html( $city_name ); ?></p>
				<?php endif; ?>
			</div>
		</a>
	</article>
	<?php
}

/**
 * Social link icon label.
 */
function musikstaden_social_label( string $key ): string {
	$labels = array(
		'instagram' => 'Instagram',
		'facebook'  => 'Facebook',
		'spotify'   => 'Spotify',
		'youtube'   => 'YouTube',
		'website'   => ms__( 'social.website', 'Website' ),
	);
	return $labels[ $key ] ?? ucfirst( $key );
}

/**
 * Get ACF or post meta value with fallback.
 *
 * @param string $key Meta key.
 * @param int    $post_id Post ID.
 * @return mixed
 */
function musikstaden_get_field( string $key, int $post_id ) {
	if ( function_exists( 'get_field' ) ) {
		return get_field( $key, $post_id );
	}
	return get_post_meta( $post_id, $key, true );
}

/**
 * Ad slot placeholder markup.
 */
function musikstaden_ad_slot( string $sidebar_id ): void {
	if ( is_active_sidebar( $sidebar_id ) ) {
		dynamic_sidebar( $sidebar_id );
		return;
	}
	?>
	<div class="ad-slot ad-slot--empty" aria-hidden="true">
		<span class="ad-slot__placeholder"><?php ms_e( 'ad.placeholder', 'Ad space' ); ?></span>
	</div>
	<?php
}
