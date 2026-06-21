<?php
/**
 * /year/{YYYY} routing.
 *
 * Public year URLs address years by their 4-digit title (e.g. /year/2024),
 * even though the jahr slugs are y2024, 2026-2, etc. A rewrite rule resolves
 * the title at template time.
 *
 * @package stolze
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the rewrite rule + query var.
 */
function stolze_register_year_route() {
	add_rewrite_rule( '^year/([0-9]{4})/?$', 'index.php?stolze_year=$matches[1]', 'top' );
}
add_action( 'init', 'stolze_register_year_route' );

/**
 * Expose the custom query var.
 *
 * @param string[] $vars Query vars.
 * @return string[]
 */
function stolze_query_vars( $vars ) {
	$vars[] = 'stolze_year';
	return $vars;
}
add_filter( 'query_vars', 'stolze_query_vars' );

/**
 * When /year/{YYYY} matches, set up the resolved jahr post and load the
 * single-jahr template (or 404 if there is no such year).
 *
 * @param string $template Template path.
 * @return string
 */
function stolze_year_template( $template ) {
	$year = get_query_var( 'stolze_year' );
	if ( '' === $year || null === $year ) {
		return $template;
	}

	$post = stolze_year_by_title( $year );
	if ( ! $post ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		return get_query_template( '404' );
	}

	global $wp_query;
	$GLOBALS['post']             = $post;
	$wp_query->queried_object    = $post;
	$wp_query->queried_object_id = $post->ID;
	$wp_query->is_404            = false;

	// Declare this as a singular post so plugins (RankMath, breadcrumbs, etc.)
	// generate per-year titles, descriptions, and schema — not the homepage fallback.
	// header.php already handles is_front_page() returning false via the stolze_year_qv check.
	$wp_query->is_singular   = true;
	$wp_query->is_single     = true;
	$wp_query->is_home       = false;
	$wp_query->is_front_page = false;

	setup_postdata( $post );

	$single = locate_template( 'single-jahr.php' );
	return $single ? $single : $template;
}
add_filter( 'template_include', 'stolze_year_template' );
