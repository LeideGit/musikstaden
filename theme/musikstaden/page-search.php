<?php
/**
 * Search results page (/sok/).
 *
 * @package Musikstaden
 */

get_header();

$query   = new WP_Query( musikstaden_band_query_args() );
$count   = $query->found_posts;
$has_filters = ! empty( $_GET['city'] ) || ! empty( $_GET['gig'] ) || ! empty( $_GET['genre'] );
?>

<section class="search-page section">
	<div class="container">
		<div class="search-page__filters">
			<?php
			$action = home_url( '/sok/' );
			include locate_template( 'template-parts/search-form.php' );
			?>
		</div>

		<?php musikstaden_ad_slot( 'ad-search' ); ?>

		<h1 class="search-page__title">
			<strong><?php echo esc_html( (string) $count ); ?></strong>
			<?php ms_e( 'search.results', 'Artists Found' ); ?>
		</h1>

		<?php if ( $query->have_posts() ) : ?>
			<div class="artist-grid">
				<?php
				while ( $query->have_posts() ) :
					$query->the_post();
					musikstaden_render_band_card( get_post() );
				endwhile;
				wp_reset_postdata();
				?>
			</div>
		<?php else : ?>
			<p class="search-page__empty">
				<?php
				echo esc_html(
					musikstaden_get_lang() === 'sv'
						? 'Inga artister hittades. Prova att ändra filter.'
						: 'No artists found. Try changing your filters.'
				);
				?>
			</p>
		<?php endif; ?>
	</div>
</section>

<?php
get_footer();
