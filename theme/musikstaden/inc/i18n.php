<?php
/**
 * Internationalization (SV/EN toggle).
 *
 * @package Musikstaden
 */

declare(strict_types=1);

const MUSIKSTADEN_LANG_COOKIE = 'musikstaden_lang';

add_action( 'init', 'musikstaden_handle_lang_switch' );

/**
 * Handle ?lang=sv|en query param.
 */
function musikstaden_handle_lang_switch(): void {
	if ( ! isset( $_GET['lang'] ) ) {
		return;
	}
	$lang = sanitize_text_field( wp_unslash( $_GET['lang'] ) );
	if ( ! in_array( $lang, array( 'sv', 'en' ), true ) ) {
		return;
	}
	setcookie( MUSIKSTADEN_LANG_COOKIE, $lang, time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
	$_COOKIE[ MUSIKSTADEN_LANG_COOKIE ] = $lang;

	$redirect = remove_query_arg( 'lang' );
	wp_safe_redirect( $redirect ?: home_url( '/' ) );
	exit;
}

/**
 * Get current language code.
 */
function musikstaden_get_lang(): string {
	$lang = $_COOKIE[ MUSIKSTADEN_LANG_COOKIE ] ?? 'sv';
	return in_array( $lang, array( 'sv', 'en' ), true ) ? $lang : 'sv';
}

/**
 * Load translation strings from JSON.
 *
 * @return array<string, array{sv: string, en: string}>
 */
function musikstaden_get_strings(): array {
	static $strings = null;
	if ( null !== $strings ) {
		return $strings;
	}

	$file = MUSIKSTADEN_DIR . '/languages/strings.json';
	if ( ! file_exists( $file ) ) {
		$strings = array();
		return $strings;
	}

	$json = file_get_contents( $file );
	$data = json_decode( $json ?: '{}', true );
	$strings = is_array( $data ) ? $data : array();
	return $strings;
}

/**
 * Translate a key.
 */
function musikstaden_translate( string $key, string $fallback = '' ): string {
	$strings = musikstaden_get_strings();
	$lang    = musikstaden_get_lang();

	if ( isset( $strings[ $key ][ $lang ] ) ) {
		return $strings[ $key ][ $lang ];
	}
	if ( isset( $strings[ $key ]['sv'] ) ) {
		return $strings[ $key ]['sv'];
	}
	return $fallback ?: $key;
}

/**
 * Render language toggle in header.
 */
function musikstaden_render_lang_toggle(): void {
	$current = musikstaden_get_lang();
	$sv_url  = add_query_arg( 'lang', 'sv' );
	$en_url  = add_query_arg( 'lang', 'en' );
	?>
	<div class="lang-toggle" role="navigation" aria-label="<?php esc_attr_e( 'Language', 'musikstaden' ); ?>">
		<a href="<?php echo esc_url( $sv_url ); ?>" class="lang-toggle__link <?php echo 'sv' === $current ? 'is-active' : ''; ?>" hreflang="sv">SV</a>
		<span class="lang-toggle__sep">|</span>
		<a href="<?php echo esc_url( $en_url ); ?>" class="lang-toggle__link <?php echo 'en' === $current ? 'is-active' : ''; ?>" hreflang="en">EN</a>
	</div>
	<?php
}

/**
 * Cookie consent banner.
 */
add_action( 'wp_footer', 'musikstaden_cookie_banner' );
function musikstaden_cookie_banner(): void {
	?>
	<div id="cookie-banner" class="cookie-banner" hidden>
		<div class="cookie-banner__inner">
			<p><?php ms_e( 'cookie.message', 'We use essential cookies to run the site. Analytics and ads require your consent.' ); ?>
				<a href="<?php echo esc_url( home_url( '/cookies/' ) ); ?>"><?php ms_e( 'cookie.learn', 'Learn more' ); ?></a>
			</p>
			<div class="cookie-banner__actions">
				<button type="button" class="btn btn--outline btn--sm" data-cookie="essential"><?php ms_e( 'cookie.essential', 'Essential only' ); ?></button>
				<button type="button" class="btn btn--primary btn--sm" data-cookie="accept"><?php ms_e( 'cookie.accept', 'Accept all' ); ?></button>
			</div>
		</div>
	</div>
	<?php
}
