<?php
/**
 * 404 page.
 *
 * @package stolze
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$stolze_404_year = stolze_latest_year();
$stolze_404_logo = $stolze_404_year ? stolze_image_url( get_field( 'logo', $stolze_404_year->ID ), 'large' ) : '';
?>
<main class="not-found-page">
	<?php stolze_section_title( '404', $stolze_404_logo ); ?>
	<div class="empty-state empty-state--large">
		<h1>Diese Seite gibt es nicht</h1>
		<p>Vielleicht findest du über eine dieser Seiten weiter.</p>
		<div class="empty-state__actions">
			<a class="empty-state__action" href="<?php echo esc_url( home_url( '/' ) ); ?>">Aktuelles Festival</a>
			<a class="empty-state__action" href="<?php echo esc_url( get_post_type_archive_link( 'artist' ) ); ?>">Artist-Archiv</a>
			<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
				<a class="empty-state__action" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Shop</a>
			<?php endif; ?>
		</div>
	</div>
</main>
<?php
get_footer();
