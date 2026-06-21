<?php
/**
 * Front page — the most recent festival year.
 *
 * @package stolze
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$jahr = stolze_latest_year();
if ( $jahr ) {
	get_template_part( 'template-parts/year-content', null, array( 'jahr' => $jahr ) );
} else {
	?>
	<main class="page-main">
		<div class="empty-state empty-state--large">
			<h1>Festivaljahr noch nicht veröffentlicht</h1>
			<p>Die nächste Ausgabe wird hier veröffentlicht, sobald sie bereit ist.</p>
		</div>
	</main>
	<?php
}

get_footer();
