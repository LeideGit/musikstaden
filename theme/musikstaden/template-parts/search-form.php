<?php
/**
 * Search filter form partial.
 *
 * @package Musikstaden
 */

declare(strict_types=1);

$current_city  = sanitize_title( wp_unslash( $_GET['city'] ?? '' ) );
$current_gig   = musikstaden_get_search_gig_slug();
$current_genre = sanitize_title( wp_unslash( $_GET['genre'] ?? '' ) );
$action        = $action ?? musikstaden_search_url();
?>
<form class="search-form" method="get" action="<?php echo esc_url( $action ); ?>">
	<div class="search-form__grid">
		<div class="search-form__field">
			<label for="city"><?php ms_e( 'search.city', 'Stad' ); ?></label>
			<select id="city" name="city">
				<option value=""><?php ms_e( 'search.city_placeholder', 'Välj stad' ); ?></option>
				<?php foreach ( musikstaden_get_filter_terms( 'city' ) as $term ) : ?>
					<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $current_city, $term->slug ); ?>>
						<?php echo esc_html( $term->name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="search-form__field">
			<label for="genre"><?php ms_e( 'search.genre', 'Genre' ); ?></label>
			<select id="genre" name="genre">
				<option value=""><?php ms_e( 'search.genre_placeholder', 'Alla genrer' ); ?></option>
				<?php foreach ( musikstaden_get_filter_terms( 'genre' ) as $term ) : ?>
					<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $current_genre, $term->slug ); ?>>
						<?php echo esc_html( $term->name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="search-form__field">
			<label for="gig"><?php ms_e( 'search.gig', 'Bokningstyp' ); ?></label>
			<select id="gig" name="gig">
				<option value=""><?php ms_e( 'search.gig_placeholder', 'Välj typ' ); ?></option>
				<?php foreach ( musikstaden_get_filter_terms( 'gig_type' ) as $term ) : ?>
					<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $current_gig, $term->slug ); ?>>
						<?php echo esc_html( $term->name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<p class="search-form__hint"><?php ms_e( 'search.gig_hint', 'Bröllop, företag, festival, klubb...' ); ?></p>
		</div>
	</div>
	<button type="submit" class="btn btn--primary btn--glow btn--search">
		<span class="btn__icon" aria-hidden="true">&#128269;</span>
		<?php ms_e( 'search.submit', 'SÖK ARTISTER' ); ?>
	</button>
</form>
