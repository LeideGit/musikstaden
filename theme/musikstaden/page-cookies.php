<?php
/**
 * Cookie policy page template.
 *
 * Template Name: Cookie Policy
 *
 * @package Musikstaden
 */

get_header();
$is_sv = musikstaden_get_lang() === 'sv';
?>

<section class="legal-page section">
	<div class="container container--narrow">
		<h1><?php echo esc_html( $is_sv ? 'Cookiepolicy' : 'Cookie Policy' ); ?></h1>

		<?php if ( $is_sv ) : ?>
		<h2>Nödvändiga cookies</h2>
		<p>Krävs för inloggning, språkval och säkerhet. Kan inte stängas av.</p>
		<ul>
			<li><strong>musikstaden_lang</strong> — sparar språkval (SV/EN)</li>
			<li><strong>WordPress session cookies</strong> — inloggning</li>
			<li><strong>musikstaden_cookie_consent</strong> — ditt cookieval</li>
		</ul>

		<h2>Analys och annonser (kräver samtycke)</h2>
		<p>Om du accepterar alla cookies kan vi använda Google AdSense och analysverktyg.</p>

		<h2>Hantera cookies</h2>
		<p>Du kan när som helst ändra ditt val genom att rensa cookies i webbläsaren och ladda om sidan.</p>
		<?php else : ?>
		<h2>Essential Cookies</h2>
		<p>Required for login, language preference, and security. Cannot be disabled.</p>
		<ul>
			<li><strong>musikstaden_lang</strong> — stores language choice (SV/EN)</li>
			<li><strong>WordPress session cookies</strong> — authentication</li>
			<li><strong>musikstaden_cookie_consent</strong> — your cookie choice</li>
		</ul>

		<h2>Analytics and Ads (requires consent)</h2>
		<p>If you accept all cookies, we may use Google AdSense and analytics tools.</p>

		<h2>Manage Cookies</h2>
		<p>You can change your choice anytime by clearing browser cookies and reloading the page.</p>
		<?php endif; ?>
	</div>
</section>

<?php
get_footer();
