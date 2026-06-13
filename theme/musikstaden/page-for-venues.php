<?php
/**
 * For Venues page template.
 *
 * Template Name: For Venues
 *
 * @package Musikstaden
 */

get_header();
?>

<section class="info-page section">
	<div class="container container--narrow">
		<h1><?php ms_e( 'for_venues.title', 'For Venues' ); ?></h1>
		<p class="badge badge--coming"><?php ms_e( 'for_venues.coming', 'Coming soon' ); ?></p>
		<p class="info-page__intro"><?php ms_e( 'for_venues.intro', 'We are building venue profile pages.' ); ?></p>
		<form class="application-form" method="post" action="mailto:hello@musikstaden.se">
			<div class="form-row">
				<label for="venue_email"><?php ms_e( 'apply.email', 'Email' ); ?></label>
				<input type="email" id="venue_email" name="email" required>
			</div>
			<div class="form-row">
				<label for="venue_name"><?php echo esc_html( musikstaden_get_lang() === 'sv' ? 'Spelställets namn' : 'Venue name' ); ?></label>
				<input type="text" id="venue_name" name="venue" required>
			</div>
			<button type="submit" class="btn btn--outline"><?php echo esc_html( musikstaden_get_lang() === 'sv' ? 'Anmäl intresse' : 'Register interest' ); ?></button>
		</form>
	</div>
</section>

<?php
get_footer();
