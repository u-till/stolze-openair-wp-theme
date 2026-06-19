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
	echo '<main class="page-main"><p>Kein Jahr gefunden.</p></main>';
}

get_footer();
