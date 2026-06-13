	</main>
	<footer class="site-footer">
		<div class="container site-footer__grid">
			<div class="footer-col">
				<h3><?php ms_e( 'footer.about', 'About' ); ?></h3>
				<ul>
					<li><a href="<?php echo esc_url( home_url( '/privacy/' ) ); ?>"><?php echo esc_html( musikstaden_get_lang() === 'sv' ? 'Integritet' : 'Privacy' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/cookies/' ) ); ?>">Cookies</a></li>
				</ul>
			</div>
			<div class="footer-col">
				<h3><?php ms_e( 'footer.artists', 'For Artists' ); ?></h3>
				<ul>
					<li><a href="<?php echo esc_url( home_url( '/for-artists/' ) ); ?>"><?php ms_e( 'nav.for_artists', 'For Artists' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/logga-in/' ) ); ?>"><?php ms_e( 'nav.login', 'Log in' ); ?></a></li>
				</ul>
			</div>
			<div class="footer-col">
				<h3><?php ms_e( 'footer.venues', 'For Venues' ); ?></h3>
				<ul>
					<li><a href="<?php echo esc_url( home_url( '/for-venues/' ) ); ?>"><?php ms_e( 'nav.for_venues', 'For Venues' ); ?></a></li>
				</ul>
			</div>
			<div class="footer-col">
				<h3><?php ms_e( 'footer.support', 'Support' ); ?></h3>
				<ul>
					<li><a href="mailto:hello@musikstaden.se">hello@musikstaden.se</a></li>
				</ul>
			</div>
		</div>
		<div class="site-footer__bottom">
			<div class="container">
				<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> Musikstaden</p>
			</div>
		</div>
	</footer>
</div>
<?php wp_footer(); ?>
</body>
</html>
