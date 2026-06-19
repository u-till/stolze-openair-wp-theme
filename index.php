<?php
/**
 * Generic fallback template.
 *
 * @package stolze
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main class="page-main">
	<?php
	if ( have_posts() ) :
		while ( have_posts() ) :
			the_post();
			?>
			<article>
				<h1><?php the_title(); ?></h1>
				<div class="entry-content"><?php the_content(); ?></div>
			</article>
			<?php
		endwhile;
	else :
		echo '<p>Nichts gefunden.</p>';
	endif;
	?>
</main>
<?php
get_footer();
