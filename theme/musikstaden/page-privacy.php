<?php
/**
 * Privacy policy page template.
 *
 * Template Name: Privacy Policy
 *
 * @package Musikstaden
 */

get_header();
$is_sv = musikstaden_get_lang() === 'sv';
?>

<section class="legal-page section">
	<div class="container container--narrow">
		<h1><?php echo esc_html( $is_sv ? 'Integritetspolicy' : 'Privacy Policy' ); ?></h1>
		<p><em><?php echo esc_html( $is_sv ? 'Senast uppdaterad: ' : 'Last updated: ' ); ?><?php echo esc_html( gmdate( 'Y-m-d' ) ); ?></em></p>

		<?php if ( $is_sv ) : ?>
		<h2>1. Personuppgansvarig</h2>
		<p>Musikstaden (musikstaden.se) är personuppgansvarig för behandling av personuppgifter enligt GDPR.</p>

		<h2>2. Vilka uppgifter vi samlar in</h2>
		<ul>
			<li>Kontouppgifter: namn, e-postadress (artister med konto)</li>
			<li>Bandinformation: biografi, bilder, inbäddad media, sociala länkar</li>
			<li>Tekniska uppgifter: IP-adress, cookies (se cookiepolicy)</li>
			<li>Ansökningsuppgifter: information du skickar via ansökningsformuläret</li>
		</ul>

		<h2>3. Syfte och rättslig grund</h2>
		<p>Vi behandlar uppgifter för att tillhandahålla plattformen, hantera konton, granska ansökningar och förbättra tjänsten. Rättslig grund: avtal, berättigat intresse och samtycke (cookies/annonser).</p>

		<h2>4. Delning med tredje part</h2>
		<ul>
			<li>SiteGround (hosting)</li>
			<li>Google AdSense (annonser, efter samtycke)</li>
			<li>Inbäddad media: YouTube, SoundCloud, Spotify (deras egna policies gäller)</li>
		</ul>

		<h2>5. Dina rättigheter</h2>
		<p>Du har rätt till tillgång, rättelse, radering, begränsning och invändning. Kontakta hello@musikstaden.se.</p>

		<h2>6. Lagring</h2>
		<p>Kontouppgifter sparas så länge kontot är aktivt. Ansökningar sparas upp till 24 månader.</p>
		<?php else : ?>
		<h2>1. Data Controller</h2>
		<p>Musikstaden (musikstaden.se) is the data controller under GDPR.</p>

		<h2>2. Data We Collect</h2>
		<ul>
			<li>Account data: name, email (artist accounts)</li>
			<li>Band information: biography, images, embedded media, social links</li>
			<li>Technical data: IP address, cookies (see cookie policy)</li>
			<li>Application data submitted via the waitlist form</li>
		</ul>

		<h2>3. Purpose and Legal Basis</h2>
		<p>We process data to provide the platform, manage accounts, review applications, and improve the service. Legal basis: contract, legitimate interest, and consent (cookies/ads).</p>

		<h2>4. Third Parties</h2>
		<ul>
			<li>SiteGround (hosting)</li>
			<li>Google AdSense (ads, after consent)</li>
			<li>Embedded media: YouTube, SoundCloud, Spotify (their policies apply)</li>
		</ul>

		<h2>5. Your Rights</h2>
		<p>You have the right to access, rectify, erase, restrict, and object. Contact hello@musikstaden.se.</p>

		<h2>6. Retention</h2>
		<p>Account data is kept while the account is active. Applications are kept up to 24 months.</p>
		<?php endif; ?>
	</div>
</section>

<?php
get_footer();
