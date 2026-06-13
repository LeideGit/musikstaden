<?php
/**
 * Fallback index template.
 *
 * @package Musikstaden
 */

get_header();
?>

<section class="section">
	<div class="container">
		<?php if ( have_posts() ) : ?>
			<?php while ( have_posts() ) : the_post(); ?>
				<article <?php post_class(); ?>>
					<h1><?php the_title(); ?></h1>
					<?php the_content(); ?>
				</article>
			<?php endwhile; ?>
		<?php else : ?>
			<p><?php esc_html_e( 'Nothing found.', 'musikstaden' ); ?></p>
		<?php endif; ?>
	</div>
</section>

<?php
get_footer();
