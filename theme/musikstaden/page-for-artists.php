<?php
/**
 * For Artists page template.
 *
 * Template Name: For Artists
 *
 * @package Musikstaden
 */

get_header();
?>

<section class="info-page section">
	<div class="container container--narrow">
		<h1><?php ms_e( 'for_artists.title', 'For Artists' ); ?></h1>
		<p class="info-page__intro"><?php ms_e( 'for_artists.intro', 'Create a dedicated page for your band.' ); ?></p>
		<h2><?php echo esc_html( musikstaden_get_lang() === 'sv' ? 'Ansök om beta-access' : 'Apply for beta access' ); ?></h2>
		<?php musikstaden_render_application_form(); ?>
	</div>
</section>

<?php
get_footer();
