<?php
/**
 * Search filter form partial.
 *
 * @package Musikstaden
 */

declare(strict_types=1);

$current_city  = sanitize_title( wp_unslash( $_GET['city'] ?? '' ) );
$current_event = sanitize_title( wp_unslash( $_GET['event'] ?? '' ) );
$current_genre = sanitize_title( wp_unslash( $_GET['genre'] ?? '' ) );
$action        = $action ?? musikstaden_search_url();
?>
<form class="search-form" method="get" action="<?php echo esc_url( $action ); ?>">
	<div class="search-form__grid">
		<div class="search-form__field">
			<label for="city"><?php ms_e( 'search.city', 'City' ); ?></label>
			<select id="city" name="city">
				<option value=""><?php ms_e( 'search.city_placeholder', 'Select city' ); ?></option>
				<?php foreach ( musikstaden_get_filter_terms( 'city' ) as $term ) : ?>
					<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $current_city, $term->slug ); ?>>
						<?php echo esc_html( $term->name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="search-form__field">
			<label for="event"><?php ms_e( 'search.event', 'Event Type' ); ?></label>
			<select id="event" name="event">
				<option value=""><?php ms_e( 'search.event_placeholder', 'Select event' ); ?></option>
				<?php foreach ( musikstaden_get_filter_terms( 'event_type' ) as $term ) : ?>
					<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $current_event, $term->slug ); ?>>
						<?php echo esc_html( $term->name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<p class="search-form__hint"><?php ms_e( 'search.event_hint', 'Club, Wedding, Corporate, Festival...' ); ?></p>
		</div>
		<div class="search-form__field">
			<label for="genre"><?php ms_e( 'search.genre', 'Genre' ); ?></label>
			<select id="genre" name="genre">
				<option value=""><?php ms_e( 'search.genre_placeholder', 'All genres' ); ?></option>
				<?php foreach ( musikstaden_get_filter_terms( 'genre' ) as $term ) : ?>
					<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $current_genre, $term->slug ); ?>>
						<?php echo esc_html( $term->name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>
	<button type="submit" class="btn btn--primary btn--glow btn--search">
		<span class="btn__icon" aria-hidden="true">&#128269;</span>
		<?php ms_e( 'search.submit', 'SEARCH ARTISTS' ); ?>
	</button>
</form>
