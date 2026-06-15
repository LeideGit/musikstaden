<?php
/**
 * Front page template.
 *
 * @package Musikstaden
 */

get_header();

$title = ms__( 'hero.title', 'Lokala artister i din stad, för ditt event' );
$h1    = ms__( 'hero.title_highlight_1', 'Lokala artister' );
$h2    = ms__( 'hero.title_highlight_2', 'ditt event' );
$title_html = preg_replace(
	array( '/(' . preg_quote( $h1, '/' ) . ')/iu', '/(' . preg_quote( $h2, '/' ) . ')/iu' ),
	array( '<span class="hero-banner__highlight">$1</span>', '<span class="hero-banner__highlight">$1</span>' ),
	esc_html( $title )
);
?>

<section class="hero-banner">
	<div class="hero-banner__backdrop" aria-hidden="true"></div>
	<div class="hero-banner__glow hero-banner__glow--left" aria-hidden="true"></div>
	<div class="hero-banner__glow hero-banner__glow--right" aria-hidden="true"></div>

	<div class="container hero-banner__content">
		<img
			class="hero-banner__logo"
			src="<?php echo esc_url( MUSIKSTADEN_URI . '/assets/images/logo.png' ); ?>"
			alt="<?php bloginfo( 'name' ); ?>"
			width="717"
			height="270"
		>
		<h1 class="hero-banner__title"><?php echo wp_kses_post( $title_html ); ?></h1>
		<p class="hero-banner__subtitle"><?php ms_e( 'hero.subtitle', 'Upptäck lokala band och artister — sök efter stad, event och genre.' ); ?></p>
	</div>

	<div class="container hero-banner__search">
		<div class="search-box search-box--hero">
			<?php
			$action = home_url( '/sok/' );
			include locate_template( 'template-parts/search-form.php' );
			?>
		</div>
	</div>
</section>

<?php musikstaden_ad_slot( 'ad-home' ); ?>

<section class="featured section">
	<div class="container">
		<h2 class="section__title"><?php ms_e( 'featured.title', 'Utvalda artister' ); ?></h2>
		<p class="section__subtitle"><?php ms_e( 'featured.subtitle', 'Artister tillgängliga i Stockholm' ); ?></p>
		<div class="artist-grid">
			<?php
			$featured = new WP_Query(
				array(
					'post_type'      => 'band',
					'posts_per_page' => 8,
					'post_status'    => 'publish',
					'tax_query'      => array(
						array(
							'taxonomy' => 'city',
							'field'    => 'slug',
							'terms'    => 'stockholm',
						),
					),
				)
			);
			if ( $featured->have_posts() ) :
				while ( $featured->have_posts() ) :
					$featured->the_post();
					musikstaden_render_band_card( get_post() );
				endwhile;
				wp_reset_postdata();
			else :
				$all = new WP_Query(
					array(
						'post_type'      => 'band',
						'posts_per_page' => 8,
						'post_status'    => 'publish',
					)
				);
				while ( $all->have_posts() ) :
					$all->the_post();
					musikstaden_render_band_card( get_post() );
				endwhile;
				wp_reset_postdata();
			endif;
			?>
		</div>
		<div class="section__actions">
			<a href="<?php echo esc_url( home_url( '/sok/' ) ); ?>" class="btn btn--outline"><?php ms_e( 'featured.more', 'VISA FLER ARTISTER' ); ?></a>
		</div>
	</div>
</section>

<?php
get_footer();
