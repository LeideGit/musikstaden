<?php
/**
 * Front page template.
 *
 * @package Musikstaden
 */

get_header();
?>

<section class="hero">
	<div class="container">
		<h1 class="hero__title">
			<?php
			$title = ms__( 'hero.title', 'Find the right local artist for your city and event' );
			$h1    = ms__( 'hero.title_highlight_1', 'local artist' );
			$h2    = ms__( 'hero.title_highlight_2', 'your city and event' );
			echo wp_kses_post(
				preg_replace(
					array( '/(' . preg_quote( $h1, '/' ) . ')/iu', '/(' . preg_quote( $h2, '/' ) . ')/iu' ),
					array( '<span class="hero__highlight">$1</span>', '<span class="hero__highlight">$1</span>' ),
					esc_html( $title )
				)
			);
			?>
		</h1>
		<div class="search-box">
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
		<h2 class="section__title"><?php ms_e( 'featured.title', 'Featured Artists' ); ?></h2>
		<p class="section__subtitle"><?php ms_e( 'featured.subtitle', 'Artists available in Stockholm' ); ?></p>
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
			<a href="<?php echo esc_url( home_url( '/sok/' ) ); ?>" class="btn btn--outline"><?php ms_e( 'featured.more', 'VIEW MORE ARTISTS' ); ?></a>
		</div>
	</div>
</section>

<?php
get_footer();
