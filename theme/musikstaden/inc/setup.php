<?php
/**
 * Theme setup, assets, rewrites.
 *
 * @package Musikstaden
 */

declare(strict_types=1);

add_action( 'after_setup_theme', 'musikstaden_setup' );
add_action( 'wp_enqueue_scripts', 'musikstaden_enqueue_assets' );
add_filter( 'locale', 'musikstaden_frontend_locale' );
add_action( 'init', 'musikstaden_register_rewrites' );
add_filter( 'query_vars', 'musikstaden_query_vars' );
add_action( 'widgets_init', 'musikstaden_register_ad_areas' );

/**
 * Use WordPress site language on the public site (Swedish by default in Settings).
 */
function musikstaden_frontend_locale( string $locale ): string {
	if ( is_admin() ) {
		return $locale;
	}

	$wp_locale = get_option( 'WPLANG' );
	if ( is_string( $wp_locale ) && $wp_locale !== '' ) {
		return $wp_locale;
	}

	return $locale ?: 'sv_SE';
}

/**
 * Theme supports and menus.
 */
function musikstaden_setup(): void {
	load_theme_textdomain( 'musikstaden', MUSIKSTADEN_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );

	add_image_size( 'band-card', 400, 300, true );
	add_image_size( 'band-hero', 1200, 600, true );

	register_nav_menus(
		array(
			'footer-about'   => __( 'Footer: About', 'musikstaden' ),
			'footer-artists' => __( 'Footer: For Artists', 'musikstaden' ),
			'footer-venues'  => __( 'Footer: For Venues', 'musikstaden' ),
			'footer-support' => __( 'Footer: Support', 'musikstaden' ),
		)
	);
}

/**
 * Enqueue styles and scripts.
 */
function musikstaden_enqueue_assets(): void {
	wp_enqueue_style(
		'musikstaden-fonts',
		'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'musikstaden-main',
		MUSIKSTADEN_URI . '/assets/css/main.css',
		array( 'musikstaden-fonts' ),
		MUSIKSTADEN_VERSION
	);

	wp_enqueue_script(
		'musikstaden-main',
		MUSIKSTADEN_URI . '/assets/js/main.js',
		array(),
		MUSIKSTADEN_VERSION,
		true
	);

	wp_localize_script(
		'musikstaden-main',
		'musikstadenData',
		array(
			'lang'    => musikstaden_get_lang(),
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		)
	);
}

/**
 * Register /artist/{slug} rewrite for band pages.
 */
function musikstaden_register_rewrites(): void {
	add_rewrite_rule(
		'^artist/([^/]+)/?$',
		'index.php?post_type=band&name=$matches[1]',
		'top'
	);
}

/**
 * @param string[] $vars Query vars.
 * @return string[]
 */
function musikstaden_query_vars( array $vars ): array {
	$vars[] = 'ms_city';
	$vars[] = 'ms_genre';
	$vars[] = 'ms_invite';
	return $vars;
}

/**
 * Ad widget areas (search/home only).
 */
function musikstaden_register_ad_areas(): void {
	register_sidebar(
		array(
			'name'          => __( 'Home Ad Slot', 'musikstaden' ),
			'id'            => 'ad-home',
			'description'   => __( 'Programmatic or sponsored ads on the home page.', 'musikstaden' ),
			'before_widget' => '<div class="ad-slot ad-slot--home">',
			'after_widget'  => '</div>',
			'before_title'  => '<span class="ad-slot__label">',
			'after_title'   => '</span>',
		)
	);

	register_sidebar(
		array(
			'name'          => __( 'Search Ad Slot', 'musikstaden' ),
			'id'            => 'ad-search',
			'description'   => __( 'Programmatic or sponsored ads on search results.', 'musikstaden' ),
			'before_widget' => '<div class="ad-slot ad-slot--search">',
			'after_widget'  => '</div>',
			'before_title'  => '<span class="ad-slot__label">',
			'after_title'   => '</span>',
		)
	);
}

/**
 * Flush rewrites and purge SiteGround cache on theme switch.
 */
add_action( 'after_switch_theme', 'flush_rewrite_rules' );
add_action( 'after_switch_theme', 'musikstaden_purge_host_cache' );

/**
 * Purge SiteGround Speed Optimizer cache after theme updates.
 */
function musikstaden_purge_host_cache(): void {
	if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
		sg_cachepress_purge_cache();
		return;
	}
	if ( function_exists( 'sg_cachepress_purge_everything' ) ) {
		sg_cachepress_purge_everything();
	}
}
