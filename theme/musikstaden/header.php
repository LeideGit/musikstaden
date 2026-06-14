<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="site">
	<header class="site-header">
		<div class="container site-header__inner">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-logo">
				<img src="<?php echo esc_url( MUSIKSTADEN_URI . '/assets/images/logo.png' ); ?>" alt="<?php bloginfo( 'name' ); ?>" width="717" height="270">
			</a>
			<button
				type="button"
				class="site-nav-toggle"
				aria-expanded="false"
				aria-controls="site-nav"
				aria-label="<?php echo esc_attr( ms__( 'nav.menu_open', 'Öppna meny' ) ); ?>"
				data-open-label="<?php echo esc_attr( ms__( 'nav.menu_open', 'Öppna meny' ) ); ?>"
				data-close-label="<?php echo esc_attr( ms__( 'nav.menu_close', 'Stäng meny' ) ); ?>"
			>
				<span class="site-nav-toggle__bar" aria-hidden="true"></span>
				<span class="site-nav-toggle__bar" aria-hidden="true"></span>
				<span class="site-nav-toggle__bar" aria-hidden="true"></span>
			</button>
			<nav id="site-nav" class="site-nav" aria-label="<?php esc_attr_e( 'Huvudnavigation', 'musikstaden' ); ?>">
				<a href="<?php echo esc_url( home_url( '/for-artists/' ) ); ?>" class="btn btn--outline btn--sm"><?php ms_e( 'nav.apply', 'Ansök' ); ?></a>
				<a href="<?php echo esc_url( home_url( '/logga-in/' ) ); ?>" class="btn btn--primary btn--sm btn--glow"><?php ms_e( 'nav.login', 'Logga in' ); ?></a>
			</nav>
		</div>
	</header>
	<main class="site-main">
