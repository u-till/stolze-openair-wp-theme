<?php
/**
 * Data access — mirrors the GraphQL queries the Next.js frontend used.
 *
 * @package stolze
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The "latest" year — most recently created jahr post.
 *
 * Matches GraphQL `jahre(first: 1)` whose default order is post_date DESC.
 *
 * @return WP_Post|null
 */
function stolze_latest_year() {
	$cached = wp_cache_get( 'latest_year', 'stolze' );
	if ( false !== $cached ) {
		return $cached;
	}

	$q = new WP_Query(
		array(
			'post_type'      => 'jahr',
			'posts_per_page' => 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		)
	);
	$year = $q->have_posts() ? $q->posts[0] : null;
	wp_cache_set( 'latest_year', $year, 'stolze' );
	return $year;
}

/**
 * Resolve a jahr post by its 4-digit title (used by the /year/{YYYY} route).
 *
 * @param string $year Four-digit year.
 * @return WP_Post|null
 */
function stolze_year_by_title( $year ) {
	$year = preg_replace( '/[^0-9]/', '', (string) $year );
	if ( '' === $year ) {
		return null;
	}

	$cache_key = 'year_by_title_' . $year;
	$cached    = wp_cache_get( $cache_key, 'stolze' );
	if ( false !== $cached ) {
		return $cached;
	}

	$q = new WP_Query(
		array(
			'post_type'      => 'jahr',
			'title'          => $year,
			'posts_per_page' => 1,
			'no_found_rows'  => true,
		)
	);
	$post = $q->have_posts() ? $q->posts[0] : null;
	wp_cache_set( $cache_key, $post, 'stolze' );
	return $post;
}

/**
 * All years for the year-selector, sorted by title descending.
 *
 * @return WP_Post[]
 */
function stolze_all_years() {
	$cached = wp_cache_get( 'all_years', 'stolze' );
	if ( false !== $cached ) {
		return $cached;
	}

	$q = new WP_Query(
		array(
			'post_type'      => 'jahr',
			'posts_per_page' => 200,
			'no_found_rows'  => true,
		)
	);
	$years = $q->posts;
	usort(
		$years,
		static function ( $a, $b ) {
			return (int) $b->post_title <=> (int) $a->post_title;
		}
	);
	wp_cache_set( 'all_years', $years, 'stolze' );
	return $years;
}

/**
 * Posts of a CPT linked to a given year via the ACF `jahr` relationship.
 *
 * The relationship is stored as a serialized array of post IDs, so we match
 * the quoted id with a LIKE meta query (same selectivity as WPGraphQL's
 * `where: { jahrId }`).
 *
 * @param string $post_type Post type (artist|sponsor|foodtruck).
 * @param int    $year_id   jahr post ID.
 * @param string $orderby   Optional orderby.
 * @return WP_Post[]
 */
function stolze_by_year( $post_type, $year_id, $orderby = 'date' ) {
	$allowed_post_types = array( 'artist', 'sponsor', 'foodtruck' );
	if ( ! in_array( $post_type, $allowed_post_types, true ) ) {
		return array();
	}

	$year_id   = (int) $year_id;
	$cache_key = 'by_year_' . $post_type . '_' . $year_id . '_' . sanitize_key( $orderby );
	$cached    = wp_cache_get( $cache_key, 'stolze' );
	if ( false !== $cached ) {
		return $cached;
	}

	$args = array(
		'post_type'      => $post_type,
		'posts_per_page' => 200,
		'orderby'        => $orderby,
		'order'          => 'ASC',
		'no_found_rows'  => true,
		'meta_query'     => array(
			array(
				'key'     => 'jahr',
				'value'   => '"' . $year_id . '"',
				'compare' => 'LIKE',
			),
		),
	);

	if ( 'slot' === $orderby ) {
		$args['meta_key'] = 'slot';
		$args['orderby']  = 'meta_value';
	}

	$q = new WP_Query(
		$args
	);
	wp_cache_set( $cache_key, $q->posts, 'stolze' );
	return $q->posts;
}

/**
 * Artists for a year, sorted by slot ascending (the Lineup order).
 *
 * @param int $year_id jahr post ID.
 * @return WP_Post[]
 */
function stolze_artists_for_year( $year_id ) {
	return stolze_by_year( 'artist', $year_id, 'slot' );
}

/**
 * Every artist, newest slot first (the /artists archive).
 *
 * @return WP_Post[]
 */
function stolze_all_artists() {
	$cached = wp_cache_get( 'all_artists', 'stolze' );
	if ( false !== $cached ) {
		return $cached;
	}

	$q = new WP_Query(
		array(
			'post_type'      => 'artist',
			'posts_per_page' => 300,
			'meta_key'       => 'slot',
			'orderby'        => 'meta_value',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		)
	);
	wp_cache_set( 'all_artists', $q->posts, 'stolze' );
	return $q->posts;
}

/**
 * The most recent year title an artist played (for the modal back-link).
 *
 * @param int $artist_id Artist post ID.
 * @return string Four-digit year, or ''.
 */
function stolze_artist_latest_year_title( $artist_id ) {
	$related = get_field( 'jahr', $artist_id );
	if ( empty( $related ) || ! is_array( $related ) ) {
		return '';
	}
	$titles = array();
	foreach ( $related as $node ) {
		$title = is_object( $node ) ? $node->post_title : get_the_title( (int) $node );
		if ( $title ) {
			$titles[] = (int) $title;
		}
	}
	if ( empty( $titles ) ) {
		return '';
	}
	rsort( $titles );
	return (string) $titles[0];
}

/**
 * Flat list of items for a WP menu looked up by slug.
 *
 * @param string $slug Menu slug.
 * @return array<int, array{label:string, url:string}>
 */
function stolze_menu_items( $slug ) {
	$menu = wp_get_nav_menu_object( $slug );
	if ( ! $menu ) {
		return array();
	}
	$items = wp_get_nav_menu_items( $menu->term_id );
	if ( ! $items ) {
		return array();
	}
	$out = array();
	foreach ( $items as $item ) {
		$out[] = array(
			'label' => $item->title,
			'url'   => $item->url,
		);
	}
	return $out;
}

/**
 * The href for a year in the selector: most-recent → '/', otherwise /year/{title}.
 *
 * @param WP_Post $year        jahr post.
 * @param string  $latest_title Title of the most-recent year.
 * @return string
 */
function stolze_year_href( $year, $latest_title ) {
	if ( (string) $year->post_title === (string) $latest_title ) {
		return home_url( '/' );
	}
	return home_url( '/year/' . $year->post_title );
}
