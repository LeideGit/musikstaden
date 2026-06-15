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
			<div class="site-header__tools">
				<a href="<?php echo esc_url( musikstaden_search_url() ); ?>" class="site-nav__search" aria-label="<?php echo esc_attr( ms__( 'nav.search', 'Sök' ) ); ?>">
					<svg class="site-nav__search-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
						<circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
						<path d="M20 20L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
					</svg>
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
					<?php if ( is_user_logged_in() ) : ?>
						<a href="<?php echo esc_url( home_url( '/dashboard/' ) ); ?>" class="btn btn--outline btn--sm"><?php ms_e( 'nav.dashboard', 'Dashboard' ); ?></a>
						<a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" class="btn btn--primary btn--sm btn--glow"><?php ms_e( 'nav.logout', 'Logga ut' ); ?></a>
					<?php else : ?>
						<a href="<?php echo esc_url( home_url( '/for-artists/' ) ); ?>" class="btn btn--outline btn--sm"><?php ms_e( 'nav.apply', 'Ansök' ); ?></a>
						<a href="<?php echo esc_url( home_url( '/logga-in/' ) ); ?>" class="btn btn--primary btn--sm btn--glow"><?php ms_e( 'nav.login', 'Logga in' ); ?></a>
					<?php endif; ?>
				</nav>
			</div>
		</div>
	</header>
	<main class="site-main">
