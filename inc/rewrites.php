<?php
/**
 * /year/{YYYY} routing.
 *
 * The Next.js frontend addressed years by their 4-digit title (e.g. /year/2024)
 * even though the jahr slugs are y2024, 2026-2, etc. We reproduce that URL with
 * a rewrite rule that resolves the title at template time — no DB changes.
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
	$GLOBALS['post']        = $post;
	$wp_query->queried_object    = $post;
	$wp_query->queried_object_id = $post->ID;
	$wp_query->is_404            = false;
	setup_postdata( $post );

	$single = locate_template( 'single-jahr.php' );
	return $single ? $single : $template;
}
add_filter( 'template_include', 'stolze_year_template' );
