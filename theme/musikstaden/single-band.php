<?php
/**
 * Single band page template.
 *
 * @package Musikstaden
 */

get_header();

while ( have_posts() ) :
	the_post();
	$post_id   = get_the_ID();
	$biography = musikstaden_get_field( 'biography', $post_id );
	$embeds    = musikstaden_get_field( 'embeds', $post_id ) ?: array();
	$socials   = array(
		'instagram' => musikstaden_get_field( 'social_instagram', $post_id ),
		'facebook'  => musikstaden_get_field( 'social_facebook', $post_id ),
		'spotify'   => musikstaden_get_field( 'social_spotify', $post_id ),
		'youtube'   => musikstaden_get_field( 'social_youtube', $post_id ),
		'website'   => musikstaden_get_field( 'social_website', $post_id ),
	);
	?>

<article class="band-page">
	<div class="container band-page__layout">
		<div class="band-page__main">
			<div class="band-hero">
				<?php if ( has_post_thumbnail() ) : ?>
					<?php the_post_thumbnail( 'band-hero', array( 'class' => 'band-hero__image' ) ); ?>
				<?php else : ?>
					<div class="band-hero__placeholder"><?php ms_e( 'card.image', 'IMAGE' ); ?></div>
				<?php endif; ?>
			</div>

			<header class="band-header">
				<p class="band-header__label"><?php ms_e( 'band.profile', 'Artist Profile' ); ?></p>
				<h1 class="band-header__title"><?php the_title(); ?></h1>
				<div class="band-header__tags">
					<?php echo musikstaden_term_tags( $post_id, 'city', 'tag tag--city' ); ?>
					<?php echo musikstaden_term_tags( $post_id, 'genre', 'tag tag--genre' ); ?>
					<?php echo musikstaden_term_tags( $post_id, 'gig_type', 'tag tag--gig' ); ?>
				</div>
			</header>

			<?php if ( $biography ) : ?>
			<section class="band-section">
				<h2><?php ms_e( 'band.biography', 'Biography' ); ?></h2>
				<p class="band-section__sub"><?php ms_e( 'band.biography_sub', 'About the artist' ); ?></p>
				<div class="band-section__content"><?php echo wp_kses_post( $biography ); ?></div>
			</section>
			<?php endif; ?>

			<?php if ( ! empty( $embeds ) ) : ?>
			<section class="band-section">
				<h2><?php ms_e( 'band.samples', 'Music Samples' ); ?></h2>
				<p class="band-section__sub"><?php ms_e( 'band.samples_sub', 'Listen to recordings' ); ?></p>
				<div class="embeds">
					<?php foreach ( $embeds as $row ) : ?>
						<?php
						$url = is_array( $row ) ? ( $row['url'] ?? '' ) : $row;
						echo musikstaden_render_embed( (string) $url );
						?>
					<?php endforeach; ?>
				</div>
			</section>
			<?php endif; ?>

			<?php
			$has_social = array_filter( $socials );
			if ( ! empty( $has_social ) ) :
			?>
			<section class="band-section">
				<h2><?php ms_e( 'band.social', 'Social Links' ); ?></h2>
				<p class="band-section__sub"><?php ms_e( 'band.social_sub', 'Connect with this artist' ); ?></p>
				<div class="social-links">
					<?php foreach ( $has_social as $key => $url ) : ?>
						<a href="<?php echo esc_url( (string) $url ); ?>" class="social-links__item" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( musikstaden_social_label( $key ) ); ?>">
							<span class="social-links__icon"><?php echo esc_html( strtoupper( substr( $key, 0, 1 ) ) ); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			</section>
			<?php endif; ?>
		</div>

		<div class="band-page__sidebar">
			<?php musikstaden_render_similar_artists( $post_id ); ?>
		</div>
	</div>
</article>

	<?php
endwhile;

get_footer();
