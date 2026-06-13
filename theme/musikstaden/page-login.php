<?php
/**
 * Login page template.
 *
 * Template Name: Login
 *
 * @package Musikstaden
 */

get_header();
?>

<section class="auth-page section">
	<div class="container container--narrow">
		<h1><?php ms_e( 'nav.login', 'Log in' ); ?></h1>
		<?php musikstaden_render_login_form(); ?>
		<p class="auth-page__alt">
			<a href="<?php echo esc_url( home_url( '/for-artists/' ) ); ?>">
				<?php echo esc_html( musikstaden_get_lang() === 'sv' ? 'Ansök om konto →' : 'Apply for an account →' ); ?>
			</a>
		</p>
	</div>
</section>

<?php
get_footer();
