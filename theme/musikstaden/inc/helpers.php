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
 * Split pasted embed textarea into individual Spotify/YouTube blocks.
 *
 * @return string[]
 */
function musikstaden_split_embed_input( string $raw ): array {
	$raw = trim( str_replace( array( "\r\n", "\r" ), "\n", $raw ) );
	if ( '' === $raw ) {
		return array();
	}

	$blocks = preg_split( '/\n\s*\n/', $raw ) ?: array();
	$inputs = array();

	foreach ( $blocks as $block ) {
		$block = trim( (string) $block );
		if ( '' !== $block ) {
			$inputs[] = $block;
		}
	}

	if ( 1 === count( $inputs ) && false === strpos( $inputs[0], '<iframe' ) ) {
		$lines = array_values( array_filter( array_map( 'trim', explode( "\n", $inputs[0] ) ) ) );
		if ( count( $lines ) > 1 ) {
			$all_embeds = true;
			foreach ( $lines as $line ) {
				if ( ! musikstaden_line_looks_like_embed( $line ) ) {
					$all_embeds = false;
					break;
				}
			}
			if ( $all_embeds ) {
				return $lines;
			}
		}
	}

	return $inputs;
}

/**
 * Whether a line looks like an embed URL or iframe snippet.
 */
function musikstaden_line_looks_like_embed( string $line ): bool {
	return false !== strpos( $line, '<iframe' )
		|| false !== strpos( $line, 'http://' )
		|| false !== strpos( $line, 'https://' );
}

/**
 * Get Spotify and YouTube embed inputs for a band.
 *
 * @return array{spotify: string[], youtube: string[]}
 */
function musikstaden_get_band_embeds_by_platform( int $post_id ): array {
	$platforms = array(
		'spotify' => musikstaden_split_embed_input( (string) musikstaden_get_field( 'embed_spotify', $post_id ) ),
		'youtube' => musikstaden_split_embed_input( (string) musikstaden_get_field( 'embed_youtube', $post_id ) ),
	);

	if ( ! empty( $platforms['spotify'] ) || ! empty( $platforms['youtube'] ) ) {
		return $platforms;
	}

	$legacy_inputs = musikstaden_get_legacy_band_embed_inputs( $post_id );
	foreach ( $legacy_inputs as $input ) {
		$platform = musikstaden_detect_embed_platform( musikstaden_extract_embed_src( $input ) );
		if ( 'spotify' === $platform ) {
			$platforms['spotify'][] = $input;
		} elseif ( 'youtube' === $platform ) {
			$platforms['youtube'][] = $input;
		}
	}

	return $platforms;
}

/**
 * Legacy combined embed fields (media_embeds textarea + ACF repeater).
 *
 * @return string[]
 */
function musikstaden_get_legacy_band_embed_inputs( int $post_id ): array {
	$raw = musikstaden_get_field( 'media_embeds', $post_id );
	if ( is_string( $raw ) && '' !== trim( $raw ) ) {
		return musikstaden_split_embed_input( $raw );
	}

	$legacy = musikstaden_get_field( 'embeds', $post_id );
	if ( ! is_array( $legacy ) ) {
		return array();
	}

	$inputs = array();
	foreach ( $legacy as $row ) {
		$value = is_array( $row ) ? (string) ( $row['url'] ?? '' ) : (string) $row;
		$value = trim( $value );
		if ( '' !== $value ) {
			$inputs[] = $value;
		}
	}

	return $inputs;
}

/**
 * Get all media embed inputs for a band (flat list, all platforms).
 *
 * @return string[]
 */
function musikstaden_get_band_embeds( int $post_id ): array {
	$by_platform = musikstaden_get_band_embeds_by_platform( $post_id );

	return array_merge( $by_platform['spotify'], $by_platform['youtube'] );
}

/**
 * Detect embed platform from URL or iframe src.
 */
function musikstaden_detect_embed_platform( string $url ): string {
	$host = wp_parse_url( $url, PHP_URL_HOST ) ?? '';
	$host = strtolower( (string) $host );

	if ( false !== strpos( $host, 'youtube.com' ) || false !== strpos( $host, 'youtu.be' ) ) {
		return 'youtube';
	}
	if ( false !== strpos( $host, 'soundcloud.com' ) ) {
		return 'soundcloud';
	}
	if ( false !== strpos( $host, 'spotify.com' ) ) {
		return 'spotify';
	}
	return 'unknown';
}

/**
 * Hosts allowed in band media embeds.
 *
 * @return string[]
 */
function musikstaden_allowed_embed_hosts(): array {
	return array(
		'www.youtube.com',
		'youtube.com',
		'www.youtube-nocookie.com',
		'youtube-nocookie.com',
		'youtu.be',
		'open.spotify.com',
		'embed.spotify.com',
		'w.soundcloud.com',
	);
}

/**
 * Whether an embed src URL is from Spotify, YouTube, or SoundCloud.
 */
function musikstaden_is_allowed_embed_src( string $src ): bool {
	$host = strtolower( (string) ( wp_parse_url( $src, PHP_URL_HOST ) ?? '' ) );
	if ( '' === $host ) {
		return false;
	}

	foreach ( musikstaden_allowed_embed_hosts() as $allowed ) {
		if ( $host === $allowed ) {
			return true;
		}
		$suffix = '.' . $allowed;
		if ( strlen( $host ) > strlen( $suffix ) && substr( $host, -strlen( $suffix ) ) === $suffix ) {
			return true;
		}
	}

	return false;
}

/**
 * Extract iframe src or return a plain URL from pasted embed input.
 */
function musikstaden_extract_embed_src( string $input ): string {
	$input = trim( $input );
	if ( '' === $input ) {
		return '';
	}

	if ( preg_match( '/<iframe[^>]+src=(["\'])([^"\']+)\1/i', $input, $matches ) ) {
		return html_entity_decode( $matches[2], ENT_QUOTES, 'UTF-8' );
	}

	return esc_url_raw( $input ) ?: $input;
}

/**
 * Extract a YouTube video ID from common URL formats.
 */
function musikstaden_youtube_video_id( string $url ): string {
	if ( preg_match( '#(?:embed/|v=|youtu\.be/)([a-zA-Z0-9_-]{11})#', $url, $matches ) ) {
		return $matches[1];
	}

	return '';
}

/**
 * Build a Spotify embed iframe src from share or embed URLs.
 */
function musikstaden_spotify_embed_src( string $url ): string {
	if ( preg_match( '#https?://open\.spotify\.com/embed/([a-z]+)/([a-zA-Z0-9]+)#', $url, $matches ) ) {
		return 'https://open.spotify.com/embed/' . $matches[1] . '/' . $matches[2];
	}

	if ( preg_match( '#https?://open\.spotify\.com/(track|album|playlist|episode|artist|show)/([a-zA-Z0-9]+)#', $url, $matches ) ) {
		return 'https://open.spotify.com/embed/' . $matches[1] . '/' . $matches[2];
	}

	return $url;
}

/**
 * Build sanitized iframe markup for supported providers.
 */
function musikstaden_build_embed_iframe( string $src ): string {
	if ( ! musikstaden_is_allowed_embed_src( $src ) ) {
		return '';
	}

	$platform = musikstaden_detect_embed_platform( $src );

	if ( 'spotify' === $platform ) {
		$embed_src = musikstaden_spotify_embed_src( $src );

		return sprintf(
			'<iframe src="%s" width="100%%" height="352" style="border-radius:12px;border:0;" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy" title="Spotify"></iframe>',
			esc_url( $embed_src )
		);
	}

	if ( 'youtube' === $platform ) {
		$video_id = musikstaden_youtube_video_id( $src );
		if ( '' === $video_id ) {
			return '';
		}

		return sprintf(
			'<iframe src="%s" width="100%%" height="315" style="border:0;" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen loading="lazy" title="YouTube video"></iframe>',
			esc_url( 'https://www.youtube.com/embed/' . $video_id )
		);
	}

	if ( 'soundcloud' === $platform ) {
		$html = wp_oembed_get( $src );
		return is_string( $html ) ? $html : '';
	}

	return '';
}

/**
 * Turn pasted embed code or a media URL into safe iframe HTML.
 */
function musikstaden_parse_embed_input( string $input ): string {
	$input = trim( $input );
	if ( '' === $input ) {
		return '';
	}

	$src = musikstaden_extract_embed_src( $input );
	if ( '' === $src ) {
		return '';
	}

	$iframe = musikstaden_build_embed_iframe( $src );
	if ( '' !== $iframe ) {
		return $iframe;
	}

	if ( false === strpos( $input, '<iframe' ) ) {
		$oembed = wp_oembed_get( $src );
		if ( is_string( $oembed ) && '' !== $oembed ) {
			return $oembed;
		}
	}

	return '';
}

/**
 * Render Spotify/YouTube/SoundCloud embed from URL or pasted iframe code.
 */
function musikstaden_render_embed( string $input ): string {
	$input = trim( $input );
	if ( '' === $input ) {
		return '';
	}

	$html     = musikstaden_parse_embed_input( $input );
	$src      = musikstaden_extract_embed_src( $input );
	$platform = musikstaden_detect_embed_platform( $src );

	if ( '' === $html ) {
		return '<p class="embed-error">' . esc_html( ms__( 'embed.error', 'Kunde inte bädda in detta. Klistra in en Spotify- eller YouTube-länk/inbäddningskod.' ) ) . '</p>';
	}

	return sprintf(
		'<div class="embed-item embed-item--%s">%s</div>',
		esc_attr( $platform ),
		$html
	);
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
 * Selected booking type (gig_type) from search query params.
 */
function musikstaden_get_search_gig_slug(): string {
	$gig = sanitize_title( wp_unslash( $_GET['gig'] ?? '' ) );
	if ( '' === $gig ) {
		return '';
	}

	return musikstaden_map_legacy_gig_slug( $gig );
}

/**
 * Search URL with filters.
 *
 * @param array<string, string> $filters Filter slugs.
 */
function musikstaden_search_url( array $filters = array() ): string {
	$base  = home_url( '/sok/' );
	$query = array_filter(
		array(
			'city'  => $filters['city'] ?? '',
			'gig'   => $filters['gig'] ?? '',
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

	$city_slug = sanitize_title( wp_unslash( $_GET['city'] ?? '' ) );
	if ( $city_slug ) {
		$tax_query[] = array(
			'taxonomy' => 'city',
			'field'    => 'slug',
			'terms'    => $city_slug,
		);
	}

	$gig_slug = musikstaden_get_search_gig_slug();
	if ( $gig_slug ) {
		$tax_query[] = array(
			'taxonomy' => 'gig_type',
			'field'    => 'slug',
			'terms'    => $gig_slug,
		);
	}

	$genre_slug = sanitize_title( wp_unslash( $_GET['genre'] ?? '' ) );
	if ( $genre_slug ) {
		$tax_query[] = array(
			'taxonomy' => 'genre',
			'field'    => 'slug',
			'terms'    => $genre_slug,
		);
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
		'website'   => ms__( 'social.website', 'Webbplats' ),
	);
	return $labels[ $key ] ?? ucfirst( $key );
}

/**
 * Inline SVG icon for a social platform.
 */
function musikstaden_social_icon( string $key ): string {
	$icons = array(
		'instagram' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" role="img" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>',
		'facebook'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" role="img" aria-hidden="true"><path d="M24 12.073C24 5.405 18.627.032 11.959.032 5.291.032-.082 5.405-.082 12.073c0 6.017 4.388 11.01 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385c5.737-.844 10.125-5.837 10.125-11.854z"/></svg>',
		'spotify'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" role="img" aria-hidden="true"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 17.302c-.218.356-.681.467-1.037.249-2.851-1.749-6.437-2.102-10.668-1.177-.406.093-.812-.16-.906-.566-.093-.406.16-.812.566-.906 4.605-1.049 8.625-.653 11.812 1.177.375.218.486.681.233 1.023zm1.482-3.351c-.279.451-.868.591-1.319.312-3.259-2.003-8.237-2.584-12.1-1.411-.503.152-1.036-.131-1.187-.634-.152-.503.131-1.036.634-1.187 4.463-1.356 9.915-.716 13.719 1.411.466.279.606.868.253 1.319zm.127-3.471C15.713 8.488 8.941 8.273 5.067 9.557c-.602.183-1.239-.157-1.422-.759-.183-.602.157-1.239.759-1.422 4.437-1.345 11.921-1.093 16.462 1.633.543.326.718 1.034.392 1.577-.326.543-1.034.718-1.577.392z"/></svg>',
		'youtube'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" role="img" aria-hidden="true"><path d="M23.498 6.186a2.969 2.969 0 0 0-2.09-2.103C19.505 3.546 12 3.546 12 3.546s-7.505 0-9.408.537A2.969 2.969 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a2.97 2.97 0 0 0 2.09 2.103c1.903.537 9.408.537 9.408.537s7.505 0 9.408-.537a2.97 2.97 0 0 0 2.09-2.103C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12 9.545 15.568z"/></svg>',
		'website'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" role="img" aria-hidden="true"><path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm7.931 9h-3.764a15.933 15.933 0 0 0-1.792-6.243A8.013 8.013 0 0 1 19.931 11zM12 4c.944 1.464 1.593 3.124 1.887 4.983H10.11C10.405 7.124 11.054 5.464 12 4zM8.622 4.757A15.933 15.933 0 0 0 6.831 11H3.069a8.013 8.013 0 0 1 5.553-6.243zM3.069 13h3.762a15.933 15.933 0 0 0 1.791 6.243A8.013 8.013 0 0 1 3.069 13zm4.553 6.243A15.933 15.933 0 0 0 10.11 13h3.777a15.933 15.933 0 0 0-1.792 6.243A8.017 8.017 0 0 1 7.622 19.243zM13.889 19.243A15.933 15.933 0 0 0 15.68 13h3.762a8.013 8.013 0 0 1-5.553 6.243zM17.168 11a15.933 15.933 0 0 0-1.887-4.983h3.75a8.013 8.013 0 0 1-1.863 4.983z"/></svg>',
	);

	$fallback = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" role="img" aria-hidden="true"><path d="M10.59 13.41c.41.39.41 1.03 0 1.42-.39.39-1.03.39-1.42 0a5.003 5.003 0 0 1 0-7.07l3.54-3.54a5.003 5.003 0 0 1 7.07 0 5.003 5.003 0 0 1 0 7.07l-1.49 1.49c.01-.82-.01-1.64-.09-2.47l.47-.48a2.982 2.982 0 0 0 0-4.24 2.982 2.982 0 0 0-4.24 0l-3.53 3.53a2.982 2.982 0 0 0 0 4.24zm2.82-4.24c.39-.39 1.03-.39 1.42 0a5.003 5.003 0 0 1 0 7.07l-3.54 3.54a5.003 5.003 0 0 1-7.07 0 5.003 5.003 0 0 1 0-7.07l1.49-1.49c-.01.82.01 1.64.09 2.47l-.47.48a2.982 2.982 0 0 0 0 4.24 2.982 2.982 0 0 0 4.24 0l3.53-3.53a2.982 2.982 0 0 0 0-4.24.973.973 0 0 1 0-1.42z"/></svg>';

	return $icons[ $key ] ?? $fallback;
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
