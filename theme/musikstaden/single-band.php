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
	$embeds    = musikstaden_get_band_embeds_by_platform( $post_id );
	$socials   = array(
		'instagram' => musikstaden_get_field( 'social_instagram', $post_id ),
		'facebook'  => musikstaden_get_field( 'social_facebook', $post_id ),
		'spotify'   => musikstaden_get_field( 'social_spotify', $post_id ),
		'youtube'   => musikstaden_get_field( 'social_youtube', $post_id ),
		'website'   => musikstaden_get_field( 'social_website', $post_id ),
	);
	$has_social = array_filter( $socials );
	?>

<article class="band-page">
	<header class="band-hero-full">
		<div class="band-hero-full__media">
			<?php if ( has_post_thumbnail() ) : ?>
				<?php the_post_thumbnail( 'band-hero', array( 'class' => 'band-hero-full__image' ) ); ?>
			<?php else : ?>
				<div class="band-hero-full__placeholder" aria-hidden="true"></div>
			<?php endif; ?>
		</div>
		<div class="band-hero-full__overlay" aria-hidden="true"></div>
		<div class="band-hero-full__glow band-hero-full__glow--left" aria-hidden="true"></div>
		<div class="band-hero-full__glow band-hero-full__glow--right" aria-hidden="true"></div>

		<div class="container band-hero-full__content">
			<p class="band-hero-full__label"><?php ms_e( 'band.profile', 'Artist Profile' ); ?></p>
			<h1 class="band-hero-full__title"><?php the_title(); ?></h1>
			<div class="band-hero-full__tags">
				<?php echo musikstaden_term_tags( $post_id, 'city', 'tag tag--city' ); ?>
				<?php echo musikstaden_term_tags( $post_id, 'genre', 'tag tag--genre' ); ?>
				<?php echo musikstaden_term_tags( $post_id, 'gig_type', 'tag tag--gig_type' ); ?>
			</div>
		</div>
	</header>

	<div class="container band-page__layout">
		<div class="band-page__main">
			<?php if ( $biography ) : ?>
			<section class="band-section band-section--lead">
				<h2><?php ms_e( 'band.biography', 'Biography' ); ?></h2>
				<div class="band-section__content"><?php echo wp_kses_post( $biography ); ?></div>
			</section>
			<?php endif; ?>

			<?php if ( ! empty( $embeds['spotify'] ) ) : ?>
			<section class="band-section">
				<h2><?php ms_e( 'band.spotify', 'Spotify' ); ?></h2>
				<div class="embeds embeds--spotify">
					<?php foreach ( $embeds['spotify'] as $embed_input ) : ?>
						<?php echo musikstaden_render_embed( $embed_input ); ?>
					<?php endforeach; ?>
				</div>
			</section>
			<?php endif; ?>

			<?php if ( ! empty( $embeds['youtube'] ) ) : ?>
			<section class="band-section">
				<h2><?php ms_e( 'band.youtube', 'YouTube' ); ?></h2>
				<div class="embeds embeds--youtube">
					<?php foreach ( $embeds['youtube'] as $embed_input ) : ?>
						<?php echo musikstaden_render_embed( $embed_input ); ?>
					<?php endforeach; ?>
				</div>
			</section>
			<?php endif; ?>

			<footer class="band-page__footer">
				<?php musikstaden_render_booking_form( $post_id ); ?>

				<?php if ( ! empty( $has_social ) ) : ?>
				<section class="band-footer-block band-footer-block--social">
					<h2 class="band-footer-block__title"><?php ms_e( 'band.social', 'Social Links' ); ?></h2>
					<div class="social-links social-links--footer">
						<?php foreach ( $has_social as $key => $url ) : ?>
							<a
								href="<?php echo esc_url( (string) $url ); ?>"
								class="social-links__item social-links__item--<?php echo esc_attr( $key ); ?>"
								target="_blank"
								rel="noopener noreferrer"
								title="<?php echo esc_attr( musikstaden_social_label( $key ) ); ?>"
							>
								<span class="social-links__icon"><?php echo musikstaden_social_icon( $key ); ?></span>
								<span class="social-links__label"><?php echo esc_html( musikstaden_social_label( $key ) ); ?></span>
							</a>
						<?php endforeach; ?>
					</div>
				</section>
				<?php endif; ?>
			</footer>
		</div>

		<aside class="band-page__sidebar">
			<?php musikstaden_render_similar_artists( $post_id ); ?>
		</aside>
	</div>
</article>

	<?php
endwhile;

get_footer();
