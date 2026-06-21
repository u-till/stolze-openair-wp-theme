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
		?>
		<div class="empty-state empty-state--large">
			<h1>Nichts gefunden</h1>
			<p>Für diese Ansicht sind noch keine Inhalte veröffentlicht.</p>
			<a class="empty-state__action" href="<?php echo esc_url( home_url( '/' ) ); ?>">Zur Startseite</a>
		</div>
		<?php
	endif;
	?>
</main>
<?php
get_footer();
