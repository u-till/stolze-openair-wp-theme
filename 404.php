<?php
/**
 * 404 (mirrors src/app/not-found.tsx — centred message).
 *
 * @package stolze
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main class="page-main" style="min-height:60vh;display:flex;align-items:center;justify-content:center;flex-direction:column;text-align:center;">
	<h1>404</h1>
	<p>Diese Seite wurde nicht gefunden.</p>
	<p><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Zurück zur Startseite</a></p>
</main>
<?php
get_footer();
