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
				<img src="<?php echo esc_url( MUSIKSTADEN_URI . '/assets/images/logo.svg' ); ?>" alt="<?php bloginfo( 'name' ); ?>" width="160" height="48">
			</a>
			<nav class="site-nav" aria-label="<?php esc_attr_e( 'Main navigation', 'musikstaden' ); ?>">
				<a href="<?php echo esc_url( home_url( '/for-artists/' ) ); ?>" class="btn btn--outline btn--sm"><?php ms_e( 'nav.for_artists', 'For Artists' ); ?></a>
				<a href="<?php echo esc_url( home_url( '/for-venues/' ) ); ?>" class="btn btn--outline btn--sm"><?php ms_e( 'nav.for_venues', 'For Venues' ); ?></a>
				<?php musikstaden_render_lang_toggle(); ?>
				<?php if ( is_user_logged_in() ) : ?>
					<a href="<?php echo esc_url( home_url( '/dashboard/' ) ); ?>" class="btn btn--outline btn--sm"><?php ms_e( 'nav.dashboard', 'Dashboard' ); ?></a>
					<a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" class="btn btn--primary btn--sm btn--glow"><?php ms_e( 'nav.logout', 'Log out' ); ?></a>
				<?php else : ?>
					<a href="<?php echo esc_url( home_url( '/logga-in/' ) ); ?>" class="btn btn--primary btn--sm btn--glow"><?php ms_e( 'nav.login', 'Log in' ); ?></a>
				<?php endif; ?>
			</nav>
		</div>
	</header>
	<main class="site-main">
