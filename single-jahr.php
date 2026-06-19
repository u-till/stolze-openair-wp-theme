<?php
/**
 * Single year — /year/{YYYY} (resolved in inc/rewrites.php) and the canonical
 * /jahr/{slug} permalink both render through here.
 *
 * @package stolze
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$jahr = get_queried_object();
if ( $jahr instanceof WP_Post ) {
	get_template_part( 'template-parts/year-content', null, array( 'jahr' => $jahr ) );
}

get_footer();
